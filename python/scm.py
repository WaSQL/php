#!/usr/bin/env python3
"""
scm.py - Extensible database migration tool.

Supports two file styles:
  - Single-file (dbmate):    000001_name.sql  with -- migrate:up / -- migrate:down markers.
                             Either marker may add "transaction:false" (dbmate
                             convention) to run that direction's SQL outside a
                             transaction, statement-by-statement — needed for things
                             like Postgres CREATE INDEX CONCURRENTLY.
  - Two-file (golang-migrate): 000001_name.up.sql + 000001_name.down.sql

Commands:
  scm.py init                    Create migrations dir and .env stub
  scm.py env-from-config [name]  Set DATABASE_URL from WaSQL config.xml + run init
  scm.py new <name>              Create new migration file(s)
  scm.py up [N] [--dry-run]      Apply all (or N) pending migrations
  scm.py down [N] [--dry-run]    Roll back N migrations (default 1)
  scm.py goto <version>          Migrate to a specific version (forward or backward)
  scm.py status                  Show applied/pending status
  scm.py history                 Show applied migrations with timestamps
  scm.py report                  Activity report: who applied what, and when
  scm.py ddl                     Verify the tracking table has all expected columns
  scm.py show <version>          Print SQL for a specific migration (no DB required)
  scm.py baseline [version]      Mark migrations applied without running SQL
  scm.py repair                  Remove orphaned tracking records from the database
  scm.py reset [--force]         Clear migration history and delete all migration files
  scm.py undo                    Interactively delete pending (unapplied) migration files
  scm.py learn                   Print a quick-start reference
  scm.py version                 Print version and exit
  scm.py who                     Show which database the current config points to
  scm.py dbs                     Alias for who

Connection (first match wins):
  --url flag > DATABASE_URL env var > DATABASE_URL in .env

  postgres://user:pass@host:5432/dbname
  mysql://user:pass@host:3306/dbname
  mssql://user:pass@host:1433/dbname
  sqlite:///path/to/db.sqlite3
  oracle://user:pass@host:1521/service
  hana://user:pass@host:39015/
  snowflake://user:pass@account/db?warehouse=X&schema=Y&role=Z
  ctree://user:pass@host:6597/dbname
  firebird://user:pass@host/dbname

.env variables:
  DATABASE_URL          Connection URL
  MIGRATION_STYLE       one|dbmate|two|golang-migrate  (default: one)
  MIGRATIONS_DIR        Path to migrations directory   (default: ./migrations)
  MIGRATIONS_TABLE      Tracking table name            (default: schema_migrations)
  MIGRATIONS_SCHEMA     Tracking table schema          (default: public on Postgres)
  WASQL_PATH            Directory containing config.xml (used by env-from-config)
  SCM_TIMESTAMPS        0/false to drop start+elapsed times from up/down/goto output
  DBMATE_MIGRATIONS_DIR      alias for MIGRATIONS_DIR
  DBMATE_MIGRATIONS_TABLE    alias for MIGRATIONS_TABLE
  DBMATE_MIGRATIONS_SCHEMA   alias for MIGRATIONS_SCHEMA

Adding a new database driver:
  1. Subclass BaseDriver
  2. Decorate with @register_driver(['yourscheme'])
  3. Implement: connect(), ensure_migrations_table(),
                applied_versions(), record_migration(), remove_migration()
  4. Use self.table (not 'schema_migrations') in all SQL so MIGRATIONS_TABLE is respected
"""

import argparse
import os
import re
import sys
import time
import xml.etree.ElementTree as ET
from datetime import datetime, timedelta, timezone
from pathlib import Path
from urllib.parse import urlparse, urlunparse, quote

__version__ = '1.32.0'

# Progress output carries wall-clock timestamps and elapsed times so a long
# up/down/goto run shows when each migration started and how long it took.
# Turned off by --no-timestamps (or SCM_TIMESTAMPS=0) for byte-stable output.
SHOW_TIMESTAMPS = True


def current_user():
    """OS user recorded in applied_by / migrated_by.

    Derived from the USERNAME environment variable (Windows), falling back to
    USER on POSIX shells. Returns '' when neither is set.
    """
    return os.environ.get('USERNAME') or os.environ.get('USER') or ''


# ---------------------------------------------------------------------------
# .env file loader
# ---------------------------------------------------------------------------

def load_env_file(env_file='.env'):
    """
    Load a .env file into os.environ. Existing environment variables are NOT
    overwritten — same behavior as dbmate.

    Supports:
      - KEY=value
      - KEY="quoted value"
      - KEY='single quoted'
      - # comments (full line or inline after value)
      - export KEY=value
      - Blank lines ignored
    """
    path = Path(env_file)
    if not path.exists():
        return

    with path.open(encoding='utf-8-sig') as f:
        for line in f:
            line = line.rstrip('\n')

            # Strip optional 'export ' prefix
            line = re.sub(r'^\s*export\s+', '', line)

            # Skip blank lines and comments
            stripped = line.strip()
            if not stripped or stripped.startswith('#'):
                continue

            # Match KEY=value
            m = re.match(r'^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)', line)
            if not m:
                continue

            key, value = m.group(1), m.group(2)

            # Strip inline comments (outside quotes)
            # Strip surrounding quotes
            if (value.startswith('"') and value.endswith('"')) or \
               (value.startswith("'") and value.endswith("'")):
                value = value[1:-1]
            else:
                # Remove inline comment
                value = re.sub(r'\s+#.*$', '', value).strip()

            # Existing env vars take precedence
            if key not in os.environ:
                os.environ[key] = value


# ---------------------------------------------------------------------------
# URL credential redaction
# ---------------------------------------------------------------------------

def redact_url(url):
    """Return the URL with the password replaced by *** for safe display.

    Reconstructs the netloc from parsed components so percent-encoded
    characters in the password (e.g. %40 for @) are handled correctly.
    """
    try:
        p = urlparse(url)
        if p.password:
            userinfo = (p.username or '') + ':***'
            host = p.hostname or ''
            if p.port:
                host += f':{p.port}'
            return urlunparse((p.scheme, f'{userinfo}@{host}', p.path, p.params, p.query, p.fragment))
        return url
    except Exception:
        return '<connection string>'


# ---------------------------------------------------------------------------
# SQL statement splitter
# ---------------------------------------------------------------------------

def split_sql_statements(sql):
    """
    Split a SQL script into individual statements.

    Correctly handles:
      - Line comments:   -- text ; with semicolons
      - Block comments:  /* text ; with semicolons */
      - Single-quoted string literals: 'O''Brien' (doubled-quote escape)

    Does NOT handle PostgreSQL dollar-quoting ($$...$$); use
    PostgresDriver.execute_script() which passes the full script directly.
    """
    statements = []
    buf = []
    i, n = 0, len(sql)

    while i < n:
        ch = sql[i]

        # Line comment: consume through end of line
        if ch == '-' and i + 1 < n and sql[i + 1] == '-':
            end = sql.find('\n', i)
            end = end if end != -1 else n
            buf.append(sql[i:end])
            i = end
            continue

        # Block comment: consume through */
        if ch == '/' and i + 1 < n and sql[i + 1] == '*':
            end = sql.find('*/', i + 2)
            end = (end + 2) if end != -1 else n
            buf.append(sql[i:end])
            i = end
            continue

        # Single-quoted string literal: handle '' as escaped quote
        if ch == "'":
            j = i + 1
            while j < n:
                if sql[j] == "'":
                    if j + 1 < n and sql[j + 1] == "'":
                        j += 2   # escaped ''
                    else:
                        j += 1   # closing quote
                        break
                else:
                    j += 1
            buf.append(sql[i:j])
            i = j
            continue

        # Statement separator
        if ch == ';':
            stmt = ''.join(buf).strip()
            if stmt:
                statements.append(stmt)
            buf = []
            i += 1
            continue

        buf.append(ch)
        i += 1

    # Trailing statement without a terminating semicolon
    stmt = ''.join(buf).strip()
    if stmt:
        statements.append(stmt)

    return statements


# ---------------------------------------------------------------------------
# Driver registry
# ---------------------------------------------------------------------------

DRIVERS = {}


def register_driver(schemes):
    """Decorator to register a driver for one or more URL schemes."""
    def decorator(cls):
        for scheme in schemes:
            DRIVERS[scheme] = cls
        return cls
    return decorator


class BaseDriver:
    """
    Base class for database drivers. Subclass this to add support for a new database.
    All drivers use DB-API 2.0 compatible connections where possible.
    """

    # DB-API parameter placeholder for this driver. Postgres/MySQL/Snowflake use
    # '%s', Oracle uses ':1'; '?' (the default) suits the rest.
    placeholder = '?'

    # Template for adding a column to a pre-existing tracking table. SQL Server,
    # Oracle, and HANA need their own syntax (overridden in those drivers).
    ADD_COLUMN_SQL = "ALTER TABLE {table} ADD COLUMN {col} varchar(255)"

    # Template for healing a tracking table that is missing applied_at. Uses the
    # same type + default as ensure_migrations_table so a healed table matches a
    # freshly created one, and each driver sets it. None means the driver cannot
    # safely add applied_at via ALTER (e.g. SQLite/Snowflake disallow a
    # non-constant default on ADD COLUMN) — in that case `ddl` reports it and it
    # must be added by hand. Existing rows are stamped with the healing time,
    # which is why applied_at is only ever missing on a hand-built table.
    ADD_APPLIED_AT_SQL = None

    # Columns added on top of the original dbmate (version, applied_at) schema.
    EXTRA_COLUMNS = ('name', 'applied_by', 'migrated_by')

    # Full set of columns the tracking table should have. Used by the `ddl`
    # command to verify nothing is missing.
    EXPECTED_COLUMNS = ('version', 'applied_at', 'name', 'applied_by', 'migrated_by')

    # Schema to create the tracking table in by default. Postgres overrides this
    # with 'public' so search_path never decides where schema_migrations lands;
    # every other driver leaves it unset (None) and uses the connection default.
    DEFAULT_SCHEMA = None

    def __init__(self, url, table='schema_migrations', schema=None):
        self.url = url
        self.table_name = table          # bare, unqualified name (for catalog lookups)
        self.schema = schema
        # Qualified identifier used in all DML/DDL so the table is resolved
        # explicitly rather than via the connection's search_path.
        self.table = f'{schema}.{table}' if schema else table
        self.conn = None

    def connect(self):
        raise NotImplementedError

    def close(self):
        if self.conn:
            try:
                self.conn.close()
            except Exception:
                pass

    def execute(self, sql, params=None):
        cur = self.conn.cursor()
        if params:
            cur.execute(sql, params)
        else:
            cur.execute(sql)
        return cur

    def execute_script(self, sql):
        """Execute a multi-statement SQL script. Override if driver needs special handling."""
        statements = split_sql_statements(sql)
        cur = self.conn.cursor()
        for stmt in statements:
            cur.execute(stmt)
        return cur

    def set_autocommit(self, value):
        """Toggle the connection's autocommit mode.

        Used for `transaction:false` migrations so a statement that refuses to run
        inside any transaction block (e.g. Postgres CREATE INDEX CONCURRENTLY) is
        sent outside one. Best-effort: on a driver whose connection object doesn't
        expose a plain settable `.autocommit` (overridden below for MySQL/SQL Server,
        which use a method instead), this silently no-ops and the statement still
        runs — just still wrapped in the connection's normal transaction.
        """
        try:
            self.conn.autocommit = value
        except Exception:
            pass

    def execute_script_no_transaction(self, sql):
        """Execute a multi-statement script with each statement committed on its own,
        outside any transaction. Override if driver needs special handling (see
        SQLServerDriver for GO-batch splitting).

        Does NOT handle PostgreSQL dollar-quoting, same as split_sql_statements() —
        a transaction:false migration should normally be a single statement anyway,
        since that's the whole point: something the database refuses to run
        alongside anything else in a transaction.
        """
        statements = split_sql_statements(sql)
        self.set_autocommit(True)
        try:
            cur = self.conn.cursor()
            for stmt in statements:
                cur.execute(stmt)
            return cur
        finally:
            self.set_autocommit(False)

    def commit(self):
        self.conn.commit()

    def rollback(self):
        self.conn.rollback()

    def set_application_name(self, name):
        pass

    def ensure_migrations_table(self):
        raise NotImplementedError

    def table_columns(self):
        """Return the lowercased column names present in the tracking table.

        Uses a no-op SELECT so it works uniformly across every DB-API driver
        (cursor.description is populated even for an empty result set).
        """
        cur = self.execute(f"SELECT * FROM {self.table} WHERE 1=0")
        return [d[0].lower() for d in cur.description]

    def ensure_columns(self):
        """Heal a tracking table that is missing scm's columns — add applied_at
        (typed timestamp + default) and name/applied_by/migrated_by if absent.

        New tables already include every column via ensure_migrations_table, so
        this is a no-op there: each column is only added when genuinely missing.
        Every ALTER runs in its own transaction and rolls back on failure, so a
        driver that can't add a column (or a permission error) never aborts the
        run — it just leaves the column for `ddl` to report.
        """
        try:
            existing = set(self.table_columns())
        except Exception:
            self.rollback()
            existing = set()

        # applied_at needs a typed default, so it has its own per-driver SQL.
        if 'applied_at' not in existing and self.ADD_APPLIED_AT_SQL:
            try:
                self.execute(self.ADD_APPLIED_AT_SQL.format(table=self.table))
                self.commit()
            except Exception:
                self.rollback()

        for col in self.EXTRA_COLUMNS:
            if col.lower() in existing:
                continue
            try:
                self.execute(self.ADD_COLUMN_SQL.format(table=self.table, col=col))
                self.commit()
            except Exception:
                self.rollback()

    def applied_versions(self):
        """Return a set of version ints that have been applied."""
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def applied_names(self):
        """Return {version:int -> name:str} recorded in the tracking table.

        Lets status/repair/history report what an orphaned migration was after its
        file has been deleted from disk.
        """
        cur = self.execute(f"SELECT version, name FROM {self.table}")
        out = {}
        for row in cur.fetchall():
            try:
                v = int(row[0])
            except (TypeError, ValueError):
                continue
            if row[1]:
                out[v] = str(row[1])
        return out

    def applied_history(self):
        """Return list of (version_str, applied_at_str, applied_by_str, name_str)."""
        cur = self.execute(
            f"SELECT version, applied_at, applied_by, name FROM {self.table} ORDER BY version"
        )
        return [(str(r[0]), str(r[1]), (r[2] or ''), (r[3] or '')) for r in cur.fetchall()]

    def record_migration(self, version, name=None):
        ph = self.placeholder
        user = current_user()
        self.execute(
            f"INSERT INTO {self.table} (version, name, applied_by, migrated_by) "
            f"VALUES ({ph}, {ph}, {ph}, {ph})",
            [str(version), name, user, user],
        )

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = {self.placeholder}", [str(version)])

    def backfill_names(self, migrations):
        """Populate name for already-applied rows that predate the name column.

        Matches applied versions to migration files on disk and fills in any row
        whose name is still NULL/empty. Never overwrites an existing name, so it is
        safe to run on every command.
        """
        label_map = {m[0]: m[1] for m in migrations}
        if not label_map:
            return
        ph = self.placeholder
        # Fast path: if no row still needs a name, skip the per-version UPDATE loop.
        # No LIMIT/TOP here — this runs on every driver and the dialects disagree;
        # fetchone() reads a single row, which is all the probe needs.
        cur = self.execute(f"SELECT 1 FROM {self.table} WHERE name IS NULL OR name = ''")
        if not cur.fetchone():
            return
        changed = False
        for version in self.applied_versions():
            name = label_map.get(version)
            if not name:
                continue
            self.execute(
                f"UPDATE {self.table} SET name = {ph} "
                f"WHERE version = {ph} AND (name IS NULL OR name = '')",
                [name, str(version)],
            )
            changed = True
        if changed:
            self.commit()


# ---------------------------------------------------------------------------
# Built-in drivers
# ---------------------------------------------------------------------------

@register_driver(['postgres', 'postgresql'])
class PostgresDriver(BaseDriver):

    placeholder = '%s'

    # Pin the tracking table to a fixed schema so the connection's search_path
    # can't scatter schema_migrations across schemas. Overridable via
    # MIGRATIONS_SCHEMA in .env.
    DEFAULT_SCHEMA = 'public'

    ADD_APPLIED_AT_SQL = "ALTER TABLE {table} ADD COLUMN applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()"

    def connect(self):
        pg = None
        try:
            import psycopg as pg       # psycopg3
        except ImportError:
            pass
        if pg is None:
            try:
                import psycopg2 as pg  # psycopg2
            except ImportError:
                sys.exit("No PostgreSQL driver found. Install one:\n  pip install \"psycopg[binary]\"\n  pip install psycopg2-binary")
        self.conn = pg.connect(self.url)
        self.conn.autocommit = False
        # Surface RAISE NOTICE from migrations to stdout.
        if pg.__name__ == 'psycopg':
            self.conn.add_notice_handler(lambda d: sys.stdout.write(f"NOTICE:  {d.message_primary}\n"))

    def _drain_notices(self):
        # psycopg2 buffers notices on conn.notices; psycopg3's handler is push-based.
        notices = getattr(self.conn, 'notices', None)
        if notices:
            for n in notices:
                sys.stdout.write(n if n.endswith('\n') else n + '\n')
            try:
                notices.clear()
            except AttributeError:
                del notices[:]

    def execute_script(self, sql):
        """Pass the full script directly — handles dollar-quoting and multi-statement."""
        cur = self.conn.cursor()
        cur.execute(sql)
        self._drain_notices()
        return cur

    def execute_script_no_transaction(self, sql):
        """Statement-by-statement with autocommit on — required for statements like
        CREATE INDEX CONCURRENTLY, which Postgres refuses to run inside a transaction
        block even an implicit one formed by sending several statements in one call.
        """
        statements = split_sql_statements(sql)
        self.set_autocommit(True)
        try:
            cur = self.conn.cursor()
            for stmt in statements:
                cur.execute(stmt)
            self._drain_notices()
            return cur
        finally:
            self.set_autocommit(False)

    # application_name is a `name`-typed GUC, so the server hard-caps it at
    # NAMEDATALEN-1 bytes and emits an "identifier ... will be truncated"
    # NOTICE for anything longer. Trim it client-side instead so long
    # migration labels don't spray notices through the apply output.
    APPLICATION_NAME_MAX = 63

    def set_application_name(self, name):
        # SET does not accept bind parameters (psycopg3 sends real $1 → syntax error).
        name = name.encode('utf-8')[:self.APPLICATION_NAME_MAX].decode('utf-8', 'ignore')
        safe = name.replace("'", "''")
        self.execute(f"SET application_name = '{safe}'")
        self.conn.commit()

    def ensure_migrations_table(self):
        # Postgres runs the CREATE privilege check before IF NOT EXISTS short-circuits,
        # so a role without CREATE on the schema fails even when the table exists.
        # Pre-check via pg_class + pg_namespace (search_path-independent) and only
        # issue CREATE when the table is genuinely missing.
        cur = self.execute(
            "SELECT EXISTS (SELECT 1 FROM pg_class c "
            "JOIN pg_namespace n ON n.oid = c.relnamespace "
            "WHERE n.nspname = %s AND c.relname = %s)",
            [self.schema or 'public', self.table_name],
        )
        if cur.fetchone()[0]:
            return
        if self.schema:
            self.execute(f'CREATE SCHEMA IF NOT EXISTS "{self.schema}"')
            self.commit()
        self.execute(f"""
            CREATE TABLE IF NOT EXISTS {self.table} (
                version varchar(128) PRIMARY KEY NOT NULL,
                applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                name varchar(255),
                applied_by varchar(255),
                migrated_by varchar(255)
            )
        """)
        self.commit()


@register_driver(['mysql', 'mariadb', 'mysqli'])
class MySQLDriver(BaseDriver):

    placeholder = '%s'

    ADD_APPLIED_AT_SQL = "ALTER TABLE {table} ADD COLUMN applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"

    def connect(self):
        p = urlparse(self.url)
        try:
            import mysql.connector
            self.conn = mysql.connector.connect(
                host=p.hostname,
                port=p.port or 3306,
                user=p.username,
                password=p.password,
                database=p.path.lstrip('/'),
                charset='utf8mb4',
                auth_plugin='mysql_native_password',
                autocommit=False,
            )
            self._mysql_buffered = True
            return
        except ImportError:
            pass
        try:
            import pymysql
            self.conn = pymysql.connect(
                host=p.hostname,
                port=p.port or 3306,
                user=p.username,
                password=p.password,
                database=p.path.lstrip('/'),
                charset='utf8mb4',
                autocommit=False,
            )
            self._mysql_buffered = False
            return
        except ImportError:
            pass
        sys.exit("No MySQL driver found. Install one:\n  pip install mysql-connector-python\n  pip install pymysql")

    def execute(self, sql, params=None):
        # mysql.connector needs buffered=True to allow multiple cursors on one connection
        cur = self.conn.cursor(buffered=True) if getattr(self, '_mysql_buffered', False) else self.conn.cursor()
        if params:
            cur.execute(sql, params)
        else:
            cur.execute(sql)
        return cur

    def set_autocommit(self, value):
        # mysql.connector exposes a settable `.autocommit` property; pymysql instead
        # exposes autocommit as a *method* — plain attribute assignment would just
        # shadow it on the instance without touching the connection.
        if getattr(self, '_mysql_buffered', False):
            self.conn.autocommit = value
        else:
            self.conn.autocommit(value)

    def ensure_migrations_table(self):
        self.execute(f"""
            CREATE TABLE IF NOT EXISTS {self.table} (
                version varchar(128) PRIMARY KEY NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                name varchar(255),
                applied_by varchar(255),
                migrated_by varchar(255)
            )
        """)
        self.commit()


@register_driver(['mssql', 'sqlserver'])
class SQLServerDriver(BaseDriver):
    """
    Requires pyodbc + ODBC Driver 17/18, or pymssql.
    pip install pyodbc
    pip install pymssql
    """

    # SQL Server's ALTER TABLE ADD has no COLUMN keyword.
    ADD_COLUMN_SQL = "ALTER TABLE {table} ADD {col} varchar(255)"
    ADD_APPLIED_AT_SQL = "ALTER TABLE {table} ADD applied_at DATETIME2 NOT NULL DEFAULT GETUTCDATE()"

    def connect(self):
        p = urlparse(self.url)
        try:
            import pymssql
            self.conn = pymssql.connect(
                server=p.hostname,
                port=p.port or 1433,
                user=p.username,
                password=p.password,
                database=p.path.lstrip('/'),
            )
            self.placeholder = '%s'
            self._mssql_flavor = 'pymssql'
            return
        except ImportError:
            pass
        try:
            import pyodbc
            conn_str = (
                f"DRIVER={{ODBC Driver 17 for SQL Server}};"
                f"SERVER={p.hostname},{p.port or 1433};"
                f"DATABASE={p.path.lstrip('/')};"
                f"UID={p.username};PWD={p.password}"
            )
            self.conn = pyodbc.connect(conn_str, autocommit=False)
            self.placeholder = '?'
            self._mssql_flavor = 'pyodbc'
            return
        except ImportError:
            pass
        sys.exit("No SQL Server driver found. Install one:\n  pip install pymssql\n  pip install pyodbc")

    def set_autocommit(self, value):
        # pymssql exposes autocommit as a *method*, not a settable attribute like pyodbc.
        if getattr(self, '_mssql_flavor', None) == 'pymssql':
            self.conn.autocommit(value)
        else:
            self.conn.autocommit = value

    def ensure_migrations_table(self):
        self.execute(f"""
            IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = '{self.table_name}')
            CREATE TABLE {self.table} (
                version varchar(128) NOT NULL PRIMARY KEY,
                applied_at DATETIME2 NOT NULL DEFAULT GETUTCDATE(),
                name varchar(255),
                applied_by varchar(255),
                migrated_by varchar(255)
            )
        """)
        self.commit()

    def execute_script(self, sql):
        """
        SQL Server uses GO as a batch separator (a client directive, not valid SQL).
        Normalise line endings first so GO is recognised on Windows files, then split
        each batch further using split_sql_statements to handle semicolons inside
        string literals and comments correctly.
        """
        sql = sql.replace('\r\n', '\n').replace('\r', '\n')
        batches = re.split(r'^\s*GO\s*$', sql, flags=re.IGNORECASE | re.MULTILINE)
        cur = self.conn.cursor()
        for batch in batches:
            for stmt in split_sql_statements(batch):
                cur.execute(stmt)
        return cur

    def execute_script_no_transaction(self, sql):
        """Same GO-batch handling as execute_script(), with autocommit on so each
        batch/statement runs and commits on its own."""
        sql = sql.replace('\r\n', '\n').replace('\r', '\n')
        batches = re.split(r'^\s*GO\s*$', sql, flags=re.IGNORECASE | re.MULTILINE)
        self.set_autocommit(True)
        try:
            cur = self.conn.cursor()
            for batch in batches:
                for stmt in split_sql_statements(batch):
                    cur.execute(stmt)
            return cur
        finally:
            self.set_autocommit(False)


@register_driver(['sqlite', 'sqlite3'])
class SQLiteDriver(BaseDriver):

    def connect(self):
        import sqlite3
        p = urlparse(self.url)
        db_path = p.netloc + p.path  # handles sqlite:///path and sqlite://path
        self.conn = sqlite3.connect(db_path or ':memory:')
        self.conn.isolation_level = 'DEFERRED'

    def set_autocommit(self, value):
        # sqlite3's autocommit mode is controlled via isolation_level, not a
        # settable .autocommit attribute (pre-3.12 DB-API surface).
        self.conn.isolation_level = None if value else 'DEFERRED'

    def ensure_migrations_table(self):
        self.execute(f"""
            CREATE TABLE IF NOT EXISTS {self.table} (
                version varchar(128) PRIMARY KEY NOT NULL,
                applied_at TEXT NOT NULL DEFAULT (datetime('now')),
                name varchar(255),
                applied_by varchar(255),
                migrated_by varchar(255)
            )
        """)
        self.commit()


@register_driver(['ctree'])
class CTreeDriver(BaseDriver):
    """
    Requires pyodbc + Faircom ODBC Driver installed on the system.
    pip install pyodbc
    URL: ctree://user:pass@host:6597/dbname
    """

    # FairCom's ALTER TABLE ADD has no COLUMN keyword.
    ADD_COLUMN_SQL = "ALTER TABLE {table} ADD {col} varchar(255)"

    def connect(self):
        try:
            import pyodbc
        except ImportError:
            sys.exit("pyodbc not installed: pip install pyodbc")
        p = urlparse(self.url)
        conn_str = ';'.join([
            "Driver={Faircom ODBC Driver}",
            f"Host={p.hostname}",
            f"Database={p.path.lstrip('/')}",
            f"Port={p.port or 6597}",
            "charset=UTF-8",
            f"UID={p.username or ''}",
            f"PWD={p.password or ''}",
        ])
        self.conn = pyodbc.connect(conn_str, ansi=True, autocommit=False)

    def ensure_migrations_table(self):
        try:
            self.execute(f"""
                CREATE TABLE {self.table} (
                    version varchar(128) NOT NULL PRIMARY KEY,
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                    name varchar(255),
                    applied_by varchar(255),
                    migrated_by varchar(255)
                )
            """)
            self.commit()
        except Exception:
            self.rollback()  # table already exists


@register_driver(['firebird'])
class FirebirdDriver(BaseDriver):
    """
    Requires fdb. pip install fdb
    URL: firebird://user:pass@host/dbname
    """

    # Firebird's ALTER TABLE ADD has no COLUMN keyword.
    ADD_COLUMN_SQL = "ALTER TABLE {table} ADD {col} varchar(255)"
    ADD_APPLIED_AT_SQL = "ALTER TABLE {table} ADD applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL"

    def connect(self):
        try:
            import fdb
        except ImportError:
            sys.exit("fdb not installed: pip install fdb")
        p = urlparse(self.url)
        self.conn = fdb.connect(
            host=p.hostname,
            database=p.path.lstrip('/'),
            user=p.username or '',
            password=p.password or '',
            charset='UTF8',
        )

    def ensure_migrations_table(self):
        try:
            self.execute(f"""
                CREATE TABLE {self.table} (
                    version varchar(128) NOT NULL PRIMARY KEY,
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                    name varchar(255),
                    applied_by varchar(255),
                    migrated_by varchar(255)
                )
            """)
            self.commit()
        except Exception:
            self.rollback()  # table already exists


@register_driver(['hana'])
class HanaDriver(BaseDriver):
    """
    Requires hdbcli. pip install hdbcli
    URL: hana://user:pass@host:39015/
    """

    # HANA's ALTER TABLE ADD wraps the column definition in parentheses.
    ADD_COLUMN_SQL = "ALTER TABLE {table} ADD ({col} varchar(255))"
    ADD_APPLIED_AT_SQL = "ALTER TABLE {table} ADD (applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL)"

    def connect(self):
        try:
            from hdbcli import dbapi
        except ImportError:
            sys.exit("hdbcli not installed: pip install hdbcli")
        p = urlparse(self.url)
        self.conn = dbapi.connect(
            address=p.hostname,
            port=p.port or 39015,
            user=p.username or '',
            password=p.password or '',
        )
        self.conn.setautocommit(False)

    def ensure_migrations_table(self):
        try:
            self.execute(f"""
                CREATE TABLE IF NOT EXISTS {self.table} (
                    version varchar(128) NOT NULL PRIMARY KEY,
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                    name varchar(255),
                    applied_by varchar(255),
                    migrated_by varchar(255)
                )
            """)
            self.commit()
        except Exception:
            self.rollback()


@register_driver(['snowflake'])
class SnowflakeDriver(BaseDriver):
    """
    Requires snowflake-connector-python. pip install snowflake-connector-python
    URL: snowflake://user:pass@account/database?warehouse=X&schema=Y&role=Z
    """

    placeholder = '%s'

    def connect(self):
        try:
            import snowflake.connector as sfc
        except ImportError:
            sys.exit("snowflake-connector-python not installed: pip install snowflake-connector-python")
        from urllib.parse import parse_qs
        p = urlparse(self.url)
        qs = parse_qs(p.query)
        kwargs = dict(
            account=p.hostname,
            user=p.username or '',
            password=p.password or '',
            database=p.path.lstrip('/') or None,
        )
        for key in ('warehouse', 'schema', 'role'):
            if key in qs:
                kwargs[key] = qs[key][0]
        self.conn = sfc.connect(**{k: v for k, v in kwargs.items() if v is not None})

    def ensure_migrations_table(self):
        self.execute(f"""
            CREATE TABLE IF NOT EXISTS {self.table} (
                version varchar(128) PRIMARY KEY NOT NULL,
                applied_at TIMESTAMP_TZ NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                name varchar(255),
                applied_by varchar(255),
                migrated_by varchar(255)
            )
        """)
        self.commit()


@register_driver(['oracle'])
class OracleDriver(BaseDriver):
    """
    Requires oracledb (modern) or cx_Oracle (legacy).
    pip install oracledb
    URL: oracle://user:pass@host:1521/service_name
    """

    placeholder = ':1'
    # Oracle uses VARCHAR2 and has no COLUMN keyword on ALTER TABLE ADD.
    ADD_COLUMN_SQL = "ALTER TABLE {table} ADD {col} varchar2(255)"
    ADD_APPLIED_AT_SQL = "ALTER TABLE {table} ADD applied_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL"

    def connect(self):
        cx = None
        try:
            import oracledb as cx
        except ImportError:
            try:
                import cx_Oracle as cx
            except ImportError:
                sys.exit("Oracle driver not installed: pip install oracledb")
        p = urlparse(self.url)
        dsn = cx.makedsn(p.hostname, p.port or 1521, service_name=p.path.lstrip('/') or None)
        self.conn = cx.connect(user=p.username or '', password=p.password or '', dsn=dsn)

    def ensure_migrations_table(self):
        try:
            self.execute(f"""
                CREATE TABLE {self.table} (
                    version varchar(128) NOT NULL PRIMARY KEY,
                    applied_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
                    name varchar2(255),
                    applied_by varchar2(255),
                    migrated_by varchar2(255)
                )
            """)
            self.commit()
        except Exception:
            self.rollback()  # ORA-00955: table already exists

    def record_migration(self, version, name=None):
        user = current_user()
        self.execute(
            f"INSERT INTO {self.table} (version, name, applied_by, migrated_by) "
            f"VALUES (:1, :2, :3, :4)",
            [str(version), name, user, user],
        )

    def backfill_names(self, migrations):
        label_map = {m[0]: m[1] for m in migrations}
        if not label_map:
            return
        changed = False
        for version in self.applied_versions():
            name = label_map.get(version)
            if not name:
                continue
            self.execute(
                f"UPDATE {self.table} SET name = :1 "
                f"WHERE version = :2 AND (name IS NULL OR name = '')",
                [name, str(version)],
            )
            changed = True
        if changed:
            self.commit()


# ---------------------------------------------------------------------------
# Migration file discovery
# ---------------------------------------------------------------------------

def find_migrations(migrations_dir):
    """
    Discover and parse migration files from migrations_dir.

    Supports:
      - Two-file:   000001_name.up.sql + 000001_name.down.sql
      - Single-file: 000001_name.sql with -- migrate:up / -- migrate:down markers

    Returns sorted list of (version:int, label:str, up_sql:str, down_sql:str|None).
    """
    path = Path(migrations_dir)
    if not path.exists():
        sys.exit(f"Migrations directory not found: {migrations_dir}")

    up_files = {}      # version -> (label, Path)
    down_files = {}    # version -> (label, Path)
    single_files = {}  # version -> (label, Path)

    re_two    = re.compile(r'^(\d+)[_\-](.+?)\.(up|down)\.sql$', re.IGNORECASE)
    re_single = re.compile(r'^(\d+)[_\-](.+?)\.sql$',            re.IGNORECASE)

    for f in sorted(path.iterdir()):
        if not f.is_file():
            continue
        m = re_two.match(f.name)
        if m:
            version, label, direction = int(m.group(1)), m.group(2), m.group(3).lower()
            bucket = up_files if direction == 'up' else down_files
            if version in bucket:
                sys.exit(
                    f"Duplicate migration version {version}: "
                    f"{bucket[version][1].name} and {f.name}"
                )
            bucket[version] = (label, f)
            continue
        m = re_single.match(f.name)
        if m:
            version, label = int(m.group(1)), m.group(2)
            if version in single_files:
                sys.exit(
                    f"Duplicate migration version {version}: "
                    f"{single_files[version][1].name} and {f.name}"
                )
            single_files[version] = (label, f)

    migrations = []

    def _read(filepath):
        try:
            # utf-8-sig transparently drops a leading BOM (common when files are
            # written by PowerShell/Windows editors) so '-- migrate:up' still matches
            return filepath.read_text(encoding='utf-8-sig')
        except UnicodeDecodeError:
            sys.exit(f"Cannot read {filepath.name}: file is not valid UTF-8.")

    # Two-file style takes precedence. No -- migrate:up marker line exists to carry
    # transaction:false, so these always run inside a transaction.
    for version, (label, up_path) in up_files.items():
        up_sql = _read(up_path).strip()
        if not up_sql:
            sys.exit(f"Empty migration file: {up_path.name}")
        down_sql = None
        if version in down_files:
            down_sql = _read(down_files[version][1]).strip() or None
        migrations.append((version, label, up_sql, down_sql, True, True))

    # Single-file style (skip if version already found in two-file)
    for version, (label, filepath) in single_files.items():
        if version in up_files:
            continue
        up_sql, down_sql, up_tx, down_tx = parse_single_file(_read(filepath), filepath.name)
        migrations.append((version, label, up_sql, down_sql, up_tx, down_tx))

    return sorted(migrations, key=lambda x: x[0])


_RE_MARKER = re.compile(r'^--\s*migrate:(up|down)(?:\s+transaction:(true|false))?\s*$')


def parse_single_file(content, filename=''):
    """Split dbmate-style single file on -- migrate:up / -- migrate:down markers.

    Either marker may carry a trailing `transaction:false` (dbmate convention) to
    mark that direction's SQL as unsafe to run inside a transaction — e.g. Postgres
    `CREATE INDEX CONCURRENTLY`. Defaults to transaction:true when omitted.

    Returns (up_sql, down_sql, up_transaction, down_transaction).
    """
    up_lines   = []
    down_lines = []
    current    = None
    up_tx      = True
    down_tx    = True

    for line in content.splitlines(keepends=True):
        stripped = line.strip().lower()
        m = _RE_MARKER.match(stripped)
        if m:
            current = m.group(1)
            tx = m.group(2) != 'false'
            if current == 'up':
                up_tx = tx
            else:
                down_tx = tx
        elif current == 'up':
            up_lines.append(line)
        elif current == 'down':
            down_lines.append(line)

    up_sql   = ''.join(up_lines).strip()
    down_sql = ''.join(down_lines).strip() or None

    if current is None:
        sys.exit(
            f"No -- migrate:up marker found in {filename}. "
            "Single-file migrations must contain -- migrate:up."
        )
    if not up_sql:
        sys.exit(f"Empty -- migrate:up section in {filename}.")

    return up_sql, down_sql, up_tx, down_tx


# ---------------------------------------------------------------------------
# Commands
# ---------------------------------------------------------------------------

def _fmt_elapsed(seconds):
    """Human-readable duration: '0.4s', '42s', '3m 06s', '1h 04m 09s'."""
    # round() first so 9.99s formats as '10s', not '10.0s'
    if round(seconds, 1) < 10:
        return f"{seconds:.1f}s"
    total = int(round(seconds))
    hours, rem   = divmod(total, 3600)
    minutes, sec = divmod(rem, 60)
    if hours:
        return f"{hours}h {minutes:02d}m {sec:02d}s"
    if minutes:
        return f"{minutes}m {sec:02d}s"
    return f"{sec}s"


def _stamp():
    """'[HH:MM:SS] ' prefix for a progress line — empty when timestamps are off."""
    return f"[{datetime.now().strftime('%H:%M:%S')}] " if SHOW_TIMESTAMPS else ''


def _took(t0):
    """' (1m 12s)' suffix for a finished step — empty when timestamps are off."""
    return f" ({_fmt_elapsed(time.monotonic() - t0)})" if SHOW_TIMESTAMPS else ''


def _run_header(summary):
    """Announce the start of a multi-migration run: 'Started <date time>  <summary>'."""
    if SHOW_TIMESTAMPS:
        print(f"Started   {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}  {summary}")


def _run_footer(summary, t0):
    """Close out a multi-migration run with the finish time and total elapsed."""
    if SHOW_TIMESTAMPS:
        stamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        print(f"Finished  {stamp}  {summary} in {_fmt_elapsed(time.monotonic() - t0)}")


def _apply_migration(driver, version, label, up_sql, up_tx):
    """Run one migration's up SQL and record it. Exits the process on failure.

    When up_tx is False (dbmate's transaction:false marker), statements run and
    commit one at a time outside any transaction — required for things like
    Postgres CREATE INDEX CONCURRENTLY. That also means a failure partway through
    leaves earlier statements applied but unrecorded; see scm.md.
    """
    print(f"{_stamp()}Applying  {version}_{label} ...", end=' ', flush=True)
    started = time.monotonic()
    try:
        driver.set_application_name(f"scm:{version}_{label}")
        if up_tx:
            driver.execute_script(up_sql)
        else:
            driver.execute_script_no_transaction(up_sql)
        driver.record_migration(version, name=label)
        driver.commit()
        print(f"OK{_took(started)}")
    except Exception as e:
        driver.rollback()
        print(f"FAILED{_took(started)}")
        sys.exit(f"Error: {e}")


def _rollback_migration(driver, version, label, down_sql, down_tx):
    """Run one migration's down SQL and remove its record. Exits the process on failure."""
    if not down_sql:
        sys.exit(
            f"No down migration for {version}_{label} — cannot roll back.\n"
            "Add a down migration or roll back manually."
        )
    print(f"{_stamp()}Rollback  {version}_{label} ...", end=' ', flush=True)
    started = time.monotonic()
    try:
        if down_tx:
            driver.execute_script(down_sql)
        else:
            driver.execute_script_no_transaction(down_sql)
        driver.remove_migration(version)
        driver.commit()
        print(f"OK{_took(started)}")
    except Exception as e:
        driver.rollback()
        print(f"FAILED{_took(started)}")
        sys.exit(f"Error: {e}")


def _tx_tag(tx):
    return '' if tx else '  -- transaction:false'


def cmd_up(driver, migrations, n=None, dry_run=False):
    applied = driver.applied_versions()
    pending = [m for m in migrations if m[0] not in applied]

    if not pending:
        print("No pending migrations.")
        return

    if n is not None:
        pending = pending[:n]

    if dry_run:
        for version, label, up_sql, _, up_tx, _ in pending:
            print(f"-- {version}_{label}{_tx_tag(up_tx)}")
            print(up_sql)
            print()
        return

    run_started = time.monotonic()
    _run_header(f"{len(pending)} migration(s) to apply")
    for version, label, up_sql, _, up_tx, _ in pending:
        _apply_migration(driver, version, label, up_sql, up_tx)
    _run_footer(f"{len(pending)} applied", run_started)


def cmd_down(driver, migrations, n=1, dry_run=False):
    applied = driver.applied_versions()
    applied_migrations = [m for m in reversed(migrations) if m[0] in applied]

    if not applied_migrations:
        print("Nothing to roll back.")
        return

    targets = applied_migrations[:n]

    if dry_run:
        for version, label, _, down_sql, _, down_tx in targets:
            if not down_sql:
                sys.exit(
                    f"No down migration for {version}_{label} — cannot roll back.\n"
                    "Add a down migration or roll back manually."
                )
            print(f"-- {version}_{label}{_tx_tag(down_tx)}")
            print(down_sql)
            print()
        return

    run_started = time.monotonic()
    _run_header(f"{len(targets)} migration(s) to roll back")
    for version, label, _, down_sql, _, down_tx in targets:
        _rollback_migration(driver, version, label, down_sql, down_tx)
    _run_footer(f"{len(targets)} rolled back", run_started)


def cmd_goto(driver, migrations, target_version, dry_run=False):
    """Migrate to exactly target_version — forward or backward, whatever is needed."""
    version_nums = [m[0] for m in migrations]
    if target_version not in version_nums:
        sys.exit(
            f"Version {target_version} not found in the migrations directory.\n"
            "Run 'scm status' to see available versions."
        )

    applied = driver.applied_versions()

    # Newest-first rollback candidates: applied and above target
    to_rollback = [m for m in reversed(migrations) if m[0] in applied and m[0] > target_version]
    # Oldest-first apply candidates: not applied and at or below target
    to_apply    = [m for m in migrations          if m[0] not in applied and m[0] <= target_version]

    if not to_rollback and not to_apply:
        print(f"Already at version {target_version}. Nothing to do.")
        return

    if dry_run:
        for version, label, _, down_sql, _, down_tx in to_rollback:
            if not down_sql:
                sys.exit(
                    f"No down migration for {version}_{label} — cannot roll back.\n"
                    "Add a down migration or roll back manually."
                )
            print(f"-- rollback {version}_{label}{_tx_tag(down_tx)}")
            print(down_sql)
            print()
        for version, label, up_sql, _, up_tx, _ in to_apply:
            print(f"-- apply {version}_{label}{_tx_tag(up_tx)}")
            print(up_sql)
            print()
        return

    run_started = time.monotonic()
    steps = []
    if to_rollback:
        steps.append(f"{len(to_rollback)} to roll back")
    if to_apply:
        steps.append(f"{len(to_apply)} to apply")
    _run_header(f"goto {target_version} — {', '.join(steps)}")

    for version, label, _, down_sql, _, down_tx in to_rollback:
        _rollback_migration(driver, version, label, down_sql, down_tx)

    for version, label, up_sql, _, up_tx, _ in to_apply:
        _apply_migration(driver, version, label, up_sql, up_tx)

    done = []
    if to_rollback:
        done.append(f"{len(to_rollback)} rolled back")
    if to_apply:
        done.append(f"{len(to_apply)} applied")
    _run_footer(', '.join(done), run_started)


def cmd_show(migrations, target_version):
    """Print the up and down SQL for a specific migration version."""
    for version, label, up_sql, down_sql, up_tx, down_tx in migrations:
        if version == target_version:
            print(f"-- {version}_{label} (up){_tx_tag(up_tx)}")
            print(up_sql)
            if down_sql:
                print(f"\n-- {version}_{label} (down){_tx_tag(down_tx)}")
                print(down_sql)
            return
    sys.exit(
        f"Version {target_version} not found in the migrations directory.\n"
        "Run 'scm status' to see available versions."
    )


def cmd_history(driver, migrations):
    """Show applied migrations with timestamps and who applied them."""
    rows = driver.applied_history()
    if not rows:
        print("No migrations have been applied.")
        return

    tty = sys.stdout.isatty()
    def bold(s):   return f'\033[1m{s}\033[0m'               if tty else s
    def blue(s):   return f'\033[38;2;95;143;211m{s}\033[0m' if tty else s
    def yellow(s): return f'\033[33m{s}\033[0m'              if tty else s

    label_map = {m[0]: m[1] for m in migrations}

    def label_for(version_str, stored_name):
        # Prefer the on-disk label; fall back to the name stored at apply time so
        # an orphaned (file-deleted) migration is still identifiable.
        return label_map.get(int(version_str)) or stored_name or '<file missing>'

    col_v = max(len('Version'), max(len(str(r[0])) for r in rows))
    col_l = max(len('Label'),   max(len(label_for(r[0], r[3])) for r in rows))
    col_b = max(len('By'),      max(len(r[2]) for r in rows))

    header = f"{'Version':<{col_v}}  {'Label':<{col_l}}  {'By':<{col_b}}  Applied At"
    print(bold(blue(header)))
    print('-' * len(header))

    for version_str, applied_at, applied_by, stored_name in rows:
        label = label_for(version_str, stored_name)
        padded_label = f"{label:<{col_l}}"
        if label_map.get(int(version_str)) is None:
            padded_label = yellow(padded_label)
        print(f"{version_str:<{col_v}}  {padded_label}  {applied_by:<{col_b}}  {applied_at}")

    print(f"\n{len(rows)} applied migration(s).")


def cmd_ddl(driver):
    """Verify the tracking table has every column scm expects, and report the schema."""
    tty = sys.stdout.isatty()
    def bold(s):   return f'\033[1m{s}\033[0m'                if tty else s
    def blue(s):   return f'\033[38;2;95;143;211m{s}\033[0m'  if tty else s
    def green(s):  return f'\033[32m{s}\033[0m'               if tty else s
    def red(s):    return f'\033[31m{s}\033[0m'               if tty else s
    def dim(s):    return f'\033[2m{s}\033[0m'                if tty else s

    try:
        present = set(driver.table_columns())
    except Exception as e:
        driver.rollback()
        sys.exit(f"Could not read the tracking table {driver.table}: {e}")

    print(bold(blue('Tracking table')))
    print(dim('─' * 40))
    print(f"  {'table':<10}  {driver.table}")
    print(f"  {'schema':<10}  {driver.schema or '(connection default)'}")
    print()

    print(bold(blue('Columns')))
    print(dim('─' * 40))
    missing = []
    for col in driver.EXPECTED_COLUMNS:
        if col.lower() in present:
            print(f"  {green('present')}  {col}")
        else:
            print(f"  {red('MISSING')}  {col}")
            missing.append(col)

    # Any extra columns beyond what scm manages — informational only.
    extra = sorted(present - {c.lower() for c in driver.EXPECTED_COLUMNS})
    if extra:
        print()
        for col in extra:
            print(f"  {dim('extra')}    {col}")

    print()
    if missing:
        print(red(f"{len(missing)} expected column(s) missing: {', '.join(missing)}."))
        if 'version' in missing:
            print(dim("  version is the primary key — scm cannot add it; the table is unusable "
                      "without it. Recreate the tracking table."))
        if 'applied_at' in missing and not driver.ADD_APPLIED_AT_SQL:
            print(dim("  applied_at cannot be added via ALTER on this database "
                      "(no non-constant default on ADD COLUMN) — add it by hand to match "
                      "a fresh table, or recreate the table."))
        print(dim("  scm self-heals missing columns on every run (up/status/...); "
                  "columns still shown above could not be added automatically."))
    else:
        print(green("All expected columns present."))


def cmd_report(driver, migrations):
    """Activity report on the tracking table: who applied what, and when."""
    rows = driver.applied_history()   # (version, applied_at, applied_by, name)

    tty = sys.stdout.isatty()
    def bold(s):   return f'\033[1m{s}\033[0m'                if tty else s
    def blue(s):   return f'\033[38;2;95;143;211m{s}\033[0m'  if tty else s
    def green(s):  return f'\033[32m{s}\033[0m'               if tty else s
    def yellow(s): return f'\033[33m{s}\033[0m'               if tty else s
    def dim(s):    return f'\033[2m{s}\033[0m'                if tty else s

    known    = {m[0] for m in migrations}
    applied  = {int(r[0]) for r in rows if str(r[0]).lstrip('-').isdigit()}
    pending  = [m for m in migrations if m[0] not in applied]
    orphaned = sorted(applied - known)
    label_map = {m[0]: m[1] for m in migrations}

    def label_for(version_str, stored_name):
        try:
            v = int(version_str)
        except (TypeError, ValueError):
            v = None
        return label_map.get(v) or stored_name or '<file missing>'

    print(bold(blue('Migration Activity Report')))
    print(dim('─' * 44))
    print(f"  {'database':<14}  {driver.table}")
    print(f"  {'applied':<14}  {len(rows)}")
    print(f"  {'pending':<14}  {len(pending)}")
    if orphaned:
        print(f"  {'orphaned':<14}  {yellow(str(len(orphaned)))}")

    if not rows:
        print()
        print("No migrations have been applied yet.")
        return

    # First / last applied, ordered by the recorded timestamp.
    by_time = sorted(rows, key=lambda r: (r[1] or ''))
    first, last = by_time[0], by_time[-1]
    print(f"  {'first applied':<14}  {first[1]}  {dim(f'{first[0]} {label_for(first[0], first[3])}')}")
    print(f"  {'last applied':<14}  {last[1]}  {dim(f'{last[0]} {label_for(last[0], last[3])}')}")

    # ---- By user ----------------------------------------------------------
    users = {}
    for version, applied_at, applied_by, _ in rows:
        who = applied_by or '(unknown)'
        u = users.setdefault(who, {'count': 0, 'first': applied_at, 'last': applied_at})
        u['count'] += 1
        if (applied_at or '') < (u['first'] or ''):
            u['first'] = applied_at
        if (applied_at or '') > (u['last'] or ''):
            u['last'] = applied_at

    print()
    print(bold(blue('By user')))
    col_u = max(len('User'), max(len(u) for u in users))
    col_c = max(len('Count'), max(len(str(v['count'])) for v in users.values()))
    header = f"{'User':<{col_u}}  {'Count':>{col_c}}  {'First':<26}  Last"
    print(header)
    print('-' * len(header))
    for who in sorted(users, key=lambda k: (-users[k]['count'], k)):
        u = users[who]
        print(f"{who:<{col_u}}  {u['count']:>{col_c}}  {str(u['first']):<26}  {u['last']}")

    # ---- Recent activity --------------------------------------------------
    recent = sorted(rows, key=lambda r: (r[1] or ''), reverse=True)[:10]
    print()
    print(bold(blue(f'Recent activity (last {len(recent)})')))
    col_v = max(len('Version'), max(len(str(r[0])) for r in recent))
    col_l = max(len('Label'),   max(len(label_for(r[0], r[3])) for r in recent))
    col_b = max(len('By'),      max(len(r[2] or '') for r in recent))
    header = f"{'Version':<{col_v}}  {'Label':<{col_l}}  {'By':<{col_b}}  Applied At"
    print(header)
    print('-' * len(header))
    for version, applied_at, applied_by, stored_name in recent:
        label = label_for(version, stored_name)
        padded = f"{label:<{col_l}}"
        if label_map.get(int(version) if str(version).lstrip('-').isdigit() else None) is None:
            padded = yellow(padded)
        print(f"{version:<{col_v}}  {padded}  {(applied_by or ''):<{col_b}}  {applied_at}")

    if pending:
        print()
        print(green(f"{len(pending)} migration(s) pending — run 'scm up' to apply."))


def cmd_baseline(driver, migrations, target_version=None):
    """Mark migrations as applied without running the SQL."""
    applied = driver.applied_versions()

    if target_version is not None:
        version_nums = [m[0] for m in migrations]
        if target_version not in version_nums:
            sys.exit(
                f"Version {target_version} not found in the migrations directory.\n"
                "Run 'scm status' to see available versions."
            )
        to_mark = [m for m in migrations if m[0] <= target_version and m[0] not in applied]
    else:
        to_mark = [m for m in migrations if m[0] not in applied]

    if not to_mark:
        print("Nothing to baseline — all migrations already marked as applied.")
        return

    for version, label, _, _, _, _ in to_mark:
        driver.record_migration(version, name=label)
        print(f"  Baselined  {version}_{label}")
    driver.commit()
    print(f"\n{len(to_mark)} migration(s) marked as applied.")


def cmd_repair(driver, migrations):
    """Remove orphaned tracking records (versions in DB with no file on disk)."""
    applied  = driver.applied_versions()
    known    = {m[0] for m in migrations}
    orphaned = sorted(applied - known)

    if not orphaned:
        print("No orphaned versions found. Nothing to repair.")
        return

    tty = sys.stdout.isatty()
    def yellow(s): return f'\033[33m{s}\033[0m' if tty else s

    names = driver.applied_names()
    print(f"Found {len(orphaned)} orphaned version(s) in {driver.table} with no file on disk:")
    for v in orphaned:
        nm = names.get(v)
        print(f"  {yellow(str(v))}  {nm}" if nm else f"  {yellow(str(v))}")
    print()

    ans = input("Remove these from the tracking table? Type 'yes' to confirm: ")
    if ans.strip().lower() != 'yes':
        print("Aborted.")
        return

    for v in orphaned:
        driver.remove_migration(v)
    driver.commit()
    print(f"Removed {len(orphaned)} orphaned record(s) from {driver.table}.")


def cmd_status(driver, migrations):
    applied  = driver.applied_versions()
    known    = {m[0] for m in migrations}
    orphaned = sorted(applied - known)  # in DB but no file on disk
    names    = driver.applied_names() if orphaned else {}

    if not migrations and not orphaned:
        print("No migration files found.")
        return

    tty = sys.stdout.isatty()
    def gray(s):   return f'\033[2m{s}\033[0m'                if tty else s
    def green(s):  return f'\033[32m{s}\033[0m'               if tty else s
    def yellow(s): return f'\033[33m{s}\033[0m'               if tty else s
    def bold(s):   return f'\033[1m{s}\033[0m'                if tty else s
    def blue(s):   return f'\033[38;2;95;143;211m{s}\033[0m'  if tty else s

    col_v = len("Version")
    col_l = len("Label")
    if migrations:
        col_v = max(col_v, max(len(str(m[0])) for m in migrations))
        col_l = max(col_l, max(len(m[1])       for m in migrations))
    if orphaned:
        col_v = max(col_v, max(len(str(v)) for v in orphaned))
        col_l = max(col_l, len("<file missing>"))
        col_l = max(col_l, max((len(names.get(v, '')) for v in orphaned), default=0))

    header = f"{'Version':<{col_v}}  {'Label':<{col_l}}  {'Status':<10}  Down?"
    print(bold(blue(header)))
    print("-" * len(header))

    for version, label, _, down_sql, _, _ in migrations:
        is_applied = version in applied
        status   = "applied" if is_applied else "pending"
        has_down = "yes"     if down_sql   else "no"
        row = f"{version:<{col_v}}  {label:<{col_l}}  {status:<10}  {has_down}"
        print(gray(row) if is_applied else green(row))

    for version in orphaned:
        label = names.get(version, '<file missing>')
        row = f"{version:<{col_v}}  {label:<{col_l}}  {'orphaned':<10}  ?"
        print(yellow(row))

    total  = len(migrations)
    n_app  = len([m for m in migrations if m[0] in applied])
    n_pend = total - n_app
    print(f"\n{total} migrations: {gray(f'{n_app} applied')}, {green(f'{n_pend} pending')}.")
    if orphaned:
        print(yellow(f"{len(orphaned)} orphaned (applied in DB but no file on disk)."))


def _unique_timestamp(migrations_dir):
    """Return a YYYYMMDDHHmmSS timestamp that doesn't collide with any existing migration file."""
    path = Path(migrations_dir)
    existing = set()
    if path.exists():
        re_version = re.compile(r'^(\d+)[_\-]')
        for f in path.iterdir():
            m = re_version.match(f.name)
            if m:
                existing.add(m.group(1))
    ts = datetime.now(timezone.utc).strftime('%Y%m%d%H%M%S')
    while ts in existing:
        ts = (datetime.strptime(ts, '%Y%m%d%H%M%S').replace(tzinfo=timezone.utc)
              + timedelta(seconds=1)).strftime('%Y%m%d%H%M%S')
    return ts


def cmd_learn():
    """Print a quick-start reference for scm."""
    import shutil

    tty = sys.stdout.isatty()
    def bold(s):   return f'\033[1m{s}\033[0m'             if tty else s
    def blue(s):   return f'\033[38;2;95;143;211m{s}\033[0m' if tty else s
    def green(s):  return f'\033[32m{s}\033[0m'             if tty else s
    def yellow(s): return f'\033[33m{s}\033[0m'             if tty else s
    def dim(s):    return f'\033[2m{s}\033[0m'              if tty else s

    width = min(shutil.get_terminal_size((80, 24)).columns, 100)
    rule  = dim('─' * width)

    def section(title):
        print(f'\n{bold(blue(title))}')
        print(rule)

    print(bold(f'\n  SCM {__version__} — Quick Reference Guide'))
    print(rule)

    section('1. SETUP')
    print('  Create a .env file in your project directory:')
    print(f'    {green("DATABASE_URL")}=postgres://user:pass@host:5432/mydb')
    print(f'    {green("MIGRATIONS_DIR")}=./migrations      {dim("# default")}')
    print(f'    {green("MIGRATIONS_SCHEMA")}=public          {dim("# Postgres: schema for the tracking table")}')
    print(f'    {green("MIGRATION_STYLE")}=one              {dim("# one=single file  two=separate up/down files")}')
    print()
    print('  Or pull settings directly from WaSQL config.xml:')
    print(f'    {yellow("scm env-from-config")} <dbname>')

    section('2. DAILY WORKFLOW')
    rows = [
        ('scm new <name>',        'Create a timestamped migration file in ./migrations'),
        ('scm up',                'Apply all pending migrations'),
        ('scm up 1',              'Apply only the next pending migration'),
        ('scm up --dry-run',      'Preview SQL for pending migrations without applying'),
        ('scm down',              'Roll back the last applied migration'),
        ('scm down 3',            'Roll back the last 3 migrations'),
        ('scm down --dry-run',    'Preview rollback SQL without executing'),
        ('scm who',               'Show which database the current config points to'),
        ('scm status',            'Show what is applied vs pending'),
        ('scm history',           'Show applied migrations with timestamps'),
        ('scm report',            'Activity report: who applied what, and when'),
        ('scm ddl',               'Verify the tracking table has all expected columns'),
        ('scm show <version>',    'Print SQL for a specific migration (no DB needed)'),
        ('scm goto <version>',    'Migrate to a specific version (forward or backward)'),
        ('scm baseline',          'Mark all migrations applied without running SQL'),
        ('scm baseline <version>','Mark up to a specific version as applied'),
        ('scm repair',            'Remove orphaned tracking records from the database'),
        ('scm undo',              'Interactively delete pending (unapplied) migration files'),
        ('scm reset --force',     'Wipe history + delete migration files (dev only)'),
    ]
    for cmd, desc in rows:
        print(f'  {yellow(f"{cmd:<38}")}  {desc}')

    section('3. MIGRATION FILE  (single-file style, MIGRATION_STYLE=one)')
    print(f'  {dim("-- migrate:up")}')
    print(f'  CREATE TABLE orders (')
    print(f'      id         bigserial PRIMARY KEY,')
    print(f'      created_at timestamptz NOT NULL DEFAULT now()')
    print(f'  );')
    print()
    print(f'  {dim("-- migrate:down")}')
    print(f'  DROP TABLE IF EXISTS orders;')

    section('4. TIPS')
    tips = [
        ('--path is a GLOBAL flag — place it before the subcommand:',
         '  scm --path ./db/migrations new create_orders'),
        ('Timestamps never collide — running new 5× in one second gives',
         '  …12, …13, …14, …15, …16 automatically.'),
        ('Guard against re-runs after partial failures:',
         '  CREATE TABLE IF NOT EXISTS …   /   DROP TABLE IF EXISTS …'),
        ('Statement cannot run inside a transaction (e.g. Postgres CREATE',
         '  INDEX CONCURRENTLY)? Add "transaction:false" after -- migrate:up.'),
        ('Never edit an applied migration.',
         '  Create a new one to correct it instead.'),
    ]
    for line1, line2 in tips:
        print(f'  {line1}')
        print(f'  {dim(line2)}')
        print()

    section('5. GLOBAL FLAGS')
    flags = [
        ('--path DIR',       'Migrations directory  (default: ./migrations)'),
        ('--db NAME',        'Scopes --path → ./migrations/<name> and --env-file → .env.<name>'),
        ('--url URL',        'DB connection URL — overrides .env and $DATABASE_URL'),
        ('--env-file FILE',  'Alternative .env file  (default: .env)'),
        ('--no-timestamps',  'Drop the [HH:MM:SS] start + elapsed times from up/down/goto'),
    ]
    for flag, desc in flags:
        print(f'  {yellow(f"{flag:<20}")}  {desc}')

    print()


def cmd_reset(driver, migrations_dir, force=False):
    """Truncate the schema_migrations tracking table and delete all migration files."""
    if not force:
        ans = input(
            f"This will clear {driver.table} and delete all files in '{migrations_dir}'. "
            "Type 'yes' to confirm: "
        )
        if ans.strip().lower() != 'yes':
            print("Aborted.")
            return

    driver.execute(f"DELETE FROM {driver.table}")
    driver.commit()
    print(f"Migration history cleared from {driver.table}.")

    path = Path(migrations_dir)
    if path.exists():
        deleted = 0
        for f in sorted(path.iterdir()):
            if f.is_file() and f.suffix.lower() == '.sql':
                f.unlink()
                print(f"  Deleted {f.name}")
                deleted += 1
        print(f"{deleted} migration file(s) deleted from {migrations_dir}.")
    else:
        print(f"Migrations directory '{migrations_dir}' does not exist — nothing to delete.")


def cmd_undo(driver, migrations, migrations_dir):
    """List pending migrations, prompt for selection, then delete files + DB records."""
    applied = driver.applied_versions()
    pending = [m for m in migrations if m[0] not in applied]

    if not pending:
        print("No pending migrations to undo.")
        return

    tty = sys.stdout.isatty()
    def yellow(s): return f'\033[33m{s}\033[0m' if tty else s
    def bold(s):   return f'\033[1m{s}\033[0m'  if tty else s
    def red(s):    return f'\033[31m{s}\033[0m'  if tty else s

    print(bold("Pending migrations:"))
    for i, (version, label, _, _, _, _) in enumerate(pending, 1):
        print(f"  {yellow(str(i))}. {version}_{label}")
    print()

    raw = input("Enter number(s) to undo (e.g. 1  or  1,3  or  1-3), blank to cancel: ").strip()
    if not raw:
        print("Aborted.")
        return

    selected_indices = set()
    for part in re.split(r'[\s,]+', raw):
        part = part.strip()
        if not part:
            continue
        range_m = re.match(r'^(\d+)-(\d+)$', part)
        if range_m:
            lo, hi = int(range_m.group(1)), int(range_m.group(2))
            selected_indices.update(range(lo, hi + 1))
        elif re.match(r'^\d+$', part):
            selected_indices.add(int(part))
        else:
            print(f"Invalid selection '{part}'. Aborted.")
            return

    invalid = [i for i in selected_indices if i < 1 or i > len(pending)]
    if invalid:
        print(f"Invalid number(s): {', '.join(str(i) for i in sorted(invalid))}. Aborted.")
        return

    targets = [pending[i - 1] for i in sorted(selected_indices)]
    path = Path(migrations_dir)
    re_prefix = re.compile(r'^(\d+)[_\-]')

    for version, label, _, _, _, _ in targets:
        version_str = str(version)
        deleted = []
        for f in sorted(path.iterdir()):
            if not f.is_file():
                continue
            fm = re_prefix.match(f.name)
            if fm and fm.group(1) == version_str:
                f.unlink()
                deleted.append(f.name)

        try:
            driver.remove_migration(version)
            driver.commit()
        except Exception:
            driver.rollback()

        if deleted:
            for fname in deleted:
                print(f"  Deleted  {fname}")
        else:
            print(f"  {red('Warning:')} no files found for version {version_str}")


def cmd_new(name, migrations_dir, style='two'):
    """Generate new migration file(s) with a timestamp-based version prefix."""
    if not re.match(r'^[A-Za-z0-9][A-Za-z0-9_-]*$', name):
        sys.exit(
            f"Invalid migration name '{name}'. "
            "Use only letters, digits, underscores, and hyphens."
        )
    ts   = _unique_timestamp(migrations_dir)
    path = Path(migrations_dir)
    path.mkdir(parents=True, exist_ok=True)

    if style == 'one':
        filepath = path / f"{ts}_{name}.sql"
        filepath.write_text("-- migrate:up\n\n\n-- migrate:down\n\n", encoding='utf-8')
        print(f"Created {filepath}")
    else:
        up   = path / f"{ts}_{name}.up.sql"
        down = path / f"{ts}_{name}.down.sql"
        up.write_text(  f"-- {ts}_{name}.up.sql\n\n",   encoding='utf-8')
        down.write_text(f"-- {ts}_{name}.down.sql\n\n", encoding='utf-8')
        print(f"Created {up}")
        print(f"Created {down}")


# ---------------------------------------------------------------------------
# env-from-config
# ---------------------------------------------------------------------------

_DBTYPE_SCHEME = {
    'postgres':   'postgres',
    'postgresql': 'postgres',
    'mysql':      'mysql',
    'mysqli':     'mysql',
    'mariadb':    'mysql',
    'mssql':      'mssql',
    'sqlserver':  'mssql',
    'sqlite':     'sqlite',
    'sqlite3':    'sqlite',
    'ctree':      'ctree',
    'firebird':   'firebird',
    'hana':       'hana',
    'snowflake':  'snowflake',
    'oracle':     'oracle',
}


def cmd_env_from_config(config_file, db_name, env_file, migrations_dir='./migrations'):
    """
    Read database settings from config.xml and write DATABASE_URL to .env.

    If db_name is empty, list available database entries and exit.
    """
    config_path = Path(config_file)
    if not config_path.exists():
        sys.exit(f"config.xml not found: {config_path}")

    try:
        tree = ET.parse(str(config_path))
    except ET.ParseError as e:
        sys.exit(f"Failed to parse {config_path}: {e}")

    root = tree.getroot()

    # Collect all <database> entries — root may be <hosts> or the root itself
    search_root = root if root.tag != 'hosts' else root
    databases = {}
    for db in search_root.iter('database'):
        name = db.get('name')
        if name:
            databases[name] = dict(db.attrib)

    if not databases:
        sys.exit(f"No <database> entries found in {config_path}")

    if not db_name:
        print(f"Available databases in {config_path}:\n")
        for name in sorted(databases):
            dbtype = databases[name].get('dbtype', '').lower()
            if dbtype in _DBTYPE_SCHEME:
                print(f"  {name} ({dbtype})")
        print(f"\nUsage: scm.py env-from-config <name>")
        return

    if db_name not in databases:
        sys.exit(
            f"Database '{db_name}' not found in config.xml.\n"
            "Run 'scm.py env-from-config' (no name) to list available entries."
        )

    db = databases[db_name]
    dbtype  = db.get('dbtype', '').lower()
    dbhost  = db.get('dbhost', 'localhost')
    dbport  = db.get('dbport', '')
    dbuser  = db.get('dbuser', '')
    dbpass  = db.get('dbpass', '')
    dbname_val = db.get('dbname', '')

    scheme = _DBTYPE_SCHEME.get(dbtype)
    if not scheme:
        supported = ', '.join(sorted(_DBTYPE_SCHEME))
        sys.exit(
            f"Unsupported dbtype '{dbtype}' for entry '{db_name}'.\n"
            f"Supported types: {supported}"
        )

    # Build URL components
    user_part = ''
    if dbuser:
        user_part = quote(dbuser, safe='')
        if dbpass:
            user_part += ':' + quote(dbpass, safe='')
        user_part += '@'

    host_part = dbhost
    if dbport:
        host_part += f':{dbport}'

    # Snowflake: encode warehouse/schema/role as query params
    if scheme == 'snowflake':
        from urllib.parse import urlencode
        qs_parts = {}
        if db.get('dbschema'):
            qs_parts['schema'] = db['dbschema']
        if db.get('dbwarehouse'):
            qs_parts['warehouse'] = db['dbwarehouse']
        if db.get('dbrole'):
            qs_parts['role'] = db['dbrole']
        qs = ('?' + urlencode(qs_parts)) if qs_parts else ''
        url = f"{scheme}://{user_part}{host_part}/{dbname_val}{qs}"
    else:
        url = f"{scheme}://{user_part}{host_part}/{dbname_val}"

    # Write or update .env
    env_path = Path(env_file)
    DATABASE_URL_RE = re.compile(r'^\s*(?:export\s+)?DATABASE_URL\s*=')

    if env_path.exists():
        lines = env_path.read_text(encoding='utf-8').splitlines(keepends=True)
        replaced = False
        new_lines = []
        for line in lines:
            if DATABASE_URL_RE.match(line):
                new_lines.append(f'DATABASE_URL={url}\n')
                replaced = True
            else:
                new_lines.append(line)
        if not replaced:
            # Ensure file ends with newline before appending
            if new_lines and not new_lines[-1].endswith('\n'):
                new_lines[-1] += '\n'
            new_lines.append(f'DATABASE_URL={url}\n')
        env_path.write_text(''.join(new_lines), encoding='utf-8')
        action = 'Updated'
    else:
        env_path.write_text(f'DATABASE_URL={url}\n', encoding='utf-8')
        action = 'Created'

    print(f"{action} {env_path}")
    print(f"  DATABASE_URL={redact_url(url)}")
    cmd_init(migrations_dir, env_file)


# ---------------------------------------------------------------------------
# init
# ---------------------------------------------------------------------------

def cmd_init(migrations_dir, env_file):
    """Create the migrations directory and a .env stub if they don't exist."""
    created = []

    mpath = Path(migrations_dir)
    if mpath.exists():
        print(f"  exists  {mpath}")
    else:
        mpath.mkdir(parents=True)
        print(f"  created {mpath}")
        created.append(str(mpath))

    epath = Path(env_file)
    if epath.exists():
        print(f"  exists  {epath}")
    else:
        epath.write_text(
            'DATABASE_URL=\n'
            'MIGRATION_STYLE=one\n'
            '# MIGRATIONS_DIR=./migrations\n'
            '# MIGRATIONS_TABLE=schema_migrations\n'
            '# MIGRATIONS_SCHEMA=public\n',
            encoding='utf-8',
        )
        print(f"  created {epath}")
        created.append(str(epath))

    if created:
        print("\nNext steps:")
        if str(epath) in created:
            print(f"  Edit {epath} and set DATABASE_URL, or run:")
            print(f"    scm env-from-config <name>")
        print(f"  scm new <migration_name>")
        print(f"  scm up")


# ---------------------------------------------------------------------------
# who / dbs commands
# ---------------------------------------------------------------------------

def _parse_url_fields(url):
    """Return (scheme, host, port_str, dbname, user) from a URL string, or None if unparseable."""
    if not url:
        return None
    try:
        p = urlparse(url)
        scheme = re.sub(r'\+.*$', '', p.scheme.lower()).replace('postgresql', 'postgres')
        return (
            scheme,
            p.hostname or '',
            f':{p.port}' if p.port else '',
            p.path.lstrip('/') or '',
            p.username or '',
        )
    except Exception:
        return None


def cmd_who(url, env_file, db=None, table='schema_migrations', schema=None):
    """Show which database the current configuration points to."""
    tty = sys.stdout.isatty()
    def bold(s):  return f'\033[1m{s}\033[0m'               if tty else s
    def blue(s):  return f'\033[38;2;95;143;211m{s}\033[0m' if tty else s
    def dim(s):   return f'\033[2m{s}\033[0m'               if tty else s
    def green(s): return f'\033[32m{s}\033[0m'              if tty else s

    if not url:
        print("No DATABASE_URL configured.")
        print(f"  env file: {env_file}")
        print(f"  Set DATABASE_URL in {env_file} or pass --url.")
        return

    fields = _parse_url_fields(url)
    if not fields:
        print(f"Could not parse DATABASE_URL in {env_file}.")
        return
    scheme, host, port, dbname, user = fields

    # Resolve the effective tracking schema for display: explicit MIGRATIONS_SCHEMA
    # wins, else Postgres defaults to 'public' (matching get_driver).
    eff_schema = schema or ('public' if scheme == 'postgres' else None)
    tracking = f'{eff_schema}.{table}' if eff_schema else table

    label_w = 10
    print(bold(blue('Current database')))
    print(dim('─' * 40))
    if db:
        print(f"  {'--db':<{label_w}}  {green(db)}")
    print(f"  {'env file':<{label_w}}  {env_file}")
    print(f"  {'driver':<{label_w}}  {scheme}")
    print(f"  {'host':<{label_w}}  {host}{port}")
    print(f"  {'database':<{label_w}}  {green(dbname or '(none)')}")
    print(f"  {'user':<{label_w}}  {user or '(none)'}")
    print(f"  {'tracking':<{label_w}}  {tracking}")
    print(f"  {'url':<{label_w}}  {dim(redact_url(url))}")


def cmd_dbs():
    """List all configured databases by scanning .env and .env.* files."""
    tty = sys.stdout.isatty()
    def bold(s):  return f'\033[1m{s}\033[0m'               if tty else s
    def blue(s):  return f'\033[38;2;95;143;211m{s}\033[0m' if tty else s
    def dim(s):   return f'\033[2m{s}\033[0m'               if tty else s
    def green(s): return f'\033[32m{s}\033[0m'              if tty else s
    def yellow(s):return f'\033[33m{s}\033[0m'              if tty else s

    # Collect .env and .env.<name> files in the current directory
    cwd = Path('.')
    env_files = []
    base = cwd / '.env'
    if base.exists():
        env_files.append(('.env', None))           # (filename, --db name)
    for f in sorted(cwd.iterdir()):
        if f.name.startswith('.env.') and f.is_file():
            db_name = f.name[len('.env.'):]
            env_files.append((f.name, db_name))

    if not env_files:
        print("No .env files found in the current directory.")
        return

    # Parse each file independently (don't let one bleed into the next)
    rows = []
    for filename, db_name in env_files:
        env_vars = {}
        path = Path(filename)
        with path.open(encoding='utf-8-sig') as fh:
            for line in fh:
                line = line.rstrip('\n')
                line = re.sub(r'^\s*export\s+', '', line)
                stripped = line.strip()
                if not stripped or stripped.startswith('#'):
                    continue
                m = re.match(r'^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)', line)
                if not m:
                    continue
                key, value = m.group(1), m.group(2)
                if (value.startswith('"') and value.endswith('"')) or \
                   (value.startswith("'") and value.endswith("'")):
                    value = value[1:-1]
                else:
                    value = re.sub(r'\s+#.*$', '', value).strip()
                env_vars[key] = value

        url = env_vars.get('DATABASE_URL', '')
        fields = _parse_url_fields(url) if url else None
        rows.append((filename, db_name, url, fields))

    # Column widths
    col_db  = max(len('--db'),      max(len(db or '(default)') for _, db, _, _ in rows))
    col_env = max(len('env file'),  max(len(fn)                 for fn, _, _, _ in rows))
    col_drv = max(len('driver'),    max(len(f[0]) if f else len('?') for _, _, _, f in rows))
    col_hst = max(len('host'),      max(len((f[1]+f[2]) if f else '?') for _, _, _, f in rows))
    col_dbn = max(len('database'),  max(len(f[3]) if f else len('(none)') for _, _, _, f in rows))

    header = (f"{'--db':<{col_db}}  {'env file':<{col_env}}  "
              f"{'driver':<{col_drv}}  {'host':<{col_hst}}  {'database':<{col_dbn}}")
    print(bold(blue(header)))
    print(dim('─' * len(header)))

    for filename, db_name, url, fields in rows:
        label = db_name or '(default)'
        if fields:
            scheme, host, port, dbname, _ = fields
            print(f"{green(label):<{col_db + 9}}  "   # +9 for ANSI codes
                  f"{filename:<{col_env}}  "
                  f"{scheme:<{col_drv}}  "
                  f"{(host+port):<{col_hst}}  "
                  f"{(dbname or '(none)'):<{col_dbn}}")
        else:
            print(f"{yellow(label):<{col_db + 9}}  "
                  f"{filename:<{col_env}}  "
                  f"{'(not configured)'}")

    print(dim(f"\n{len(rows)} env file(s) found.  Use --db <name> to target one."))


# ---------------------------------------------------------------------------
# Driver resolution
# ---------------------------------------------------------------------------

def get_driver(url, table='schema_migrations', schema=None):
    parsed = urlparse(url)
    scheme = parsed.scheme.lower()

    # Normalize common aliases
    scheme = re.sub(r'\+.*$', '', scheme)          # strip e.g. +psycopg2
    scheme = scheme.replace('postgresql', 'postgres')

    if scheme not in DRIVERS:
        supported = ', '.join(sorted(DRIVERS))
        sys.exit(
            f"Unsupported database scheme '{scheme}'.\n"
            f"Built-in drivers: {supported}\n"
            f"See source to add a custom driver."
        )

    cls = DRIVERS[scheme]
    # An explicit MIGRATIONS_SCHEMA wins; otherwise fall back to the driver's
    # default (Postgres → 'public', everything else → connection default).
    if schema is None:
        schema = cls.DEFAULT_SCHEMA
    driver = cls(url, table=table, schema=schema)
    try:
        driver.connect()
    except Exception as e:
        sys.exit(f"Failed to connect ({redact_url(url)}): {e}")
    return driver


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------

def _resolve_db_migrations_dir(name):
    """Migrations directory for --db <name>.

    Uses ./migrations/<name> when that per-database folder exists; otherwise falls
    back to the shared ./migrations. This lets one set of migrations be applied to
    many systems (dev/stage/prod) — each selected by its own .env.<name> — without
    keeping a separate per-database migrations folder. An explicit --path or a
    MIGRATIONS_DIR in the .env always overrides this.
    """
    per_db = f'./migrations/{name}'
    if os.path.isdir(per_db):
        return per_db
    if os.path.isdir('./migrations'):
        return './migrations'
    return per_db


def _find_config_xml():
    """Locate config.xml by walking up from the current working directory.

    WaSQL keeps config.xml at the site root, one level above the language
    folder (python/, php/, groovy/, ...). Walking up from CWD lets a single
    scm installed on PATH operate on whichever site you're standing in, rather
    than always reading the config.xml next to the script.

    Falls back to <script_dir>/../config.xml (the original behavior) when no
    config.xml is found at or above the current directory.
    """
    cwd = Path.cwd().resolve()
    for d in (cwd, *cwd.parents):
        candidate = d / 'config.xml'
        if candidate.exists():
            return str(candidate)
    return str(Path(__file__).resolve().parent.parent / 'config.xml')


def main():
    parser = argparse.ArgumentParser(
        prog='scm.py',
        description='Extensible database migration tool (postgres, mysql, sqlite, mssql).',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    parser.add_argument(
        '--env-file', default=None, metavar='FILE',
        help='Path to .env file. Defaults to .env.<db> if --db is set, otherwise .env.',
    )
    parser.add_argument(
        '--url', default=None,
        help='Database connection URL. Overrides .env and $DATABASE_URL.',
    )
    parser.add_argument(
        '--path', default=None,
        help='Path to migrations directory. Defaults to MIGRATIONS_DIR in .env, then ./migrations',
    )

    # Default config.xml path: search from the current directory upward so scm
    # works from any WaSQL site when installed on PATH. WaSQL keeps config.xml at
    # the site root, one level above the language folder (python/, php/, ...).
    # Falls back to the script's own layout if no config.xml is found above CWD.
    _default_config = _find_config_xml()

    parser.add_argument(
        '--config', default=None, metavar='FILE',
        help=f'Path to config.xml. Priority: this flag > WASQL_PATH in .env > {_default_config}',
    )
    parser.add_argument(
        '--db', default=None, metavar='NAME',
        help='Database name. Sets --env-file to .env.<name> and --path to ./migrations/<name> '
             'unless those are explicitly provided.',
    )
    parser.add_argument(
        '--no-timestamps', action='store_true',
        help='Omit the [HH:MM:SS] start time and elapsed time from up/down/goto output. '
             'Same as SCM_TIMESTAMPS=0.',
    )

    sub = parser.add_subparsers(dest='command', metavar='command')

    p_up = sub.add_parser('up', help='Apply pending migrations')
    p_up.add_argument('n', nargs='?', type=int, metavar='N',
                      help='Max number of migrations to apply (default: all)')
    p_up.add_argument('--dry-run', action='store_true',
                      help='Print the SQL that would be executed without applying anything')

    p_down = sub.add_parser('down', help='Roll back migrations')
    p_down.add_argument('n', nargs='?', type=int, default=1, metavar='N',
                        help='Number of migrations to roll back (default: 1)')
    p_down.add_argument('--dry-run', action='store_true',
                        help='Print the SQL that would be executed without rolling back anything')

    sub.add_parser('init',    help='Create migrations directory and .env stub')
    sub.add_parser('status',  help='Show applied/pending status of all migrations')
    sub.add_parser('version', help='Print scm.py version and exit')
    sub.add_parser('learn',   help='Print a quick-start reference')
    sub.add_parser('help',    help='Print a quick-start reference (alias for learn)')

    p_reset = sub.add_parser('reset', help='Clear all rows from the migrations tracking table')
    p_reset.add_argument('--force', action='store_true',
                         help='Skip confirmation prompt')

    p_new = sub.add_parser('new', help='Create a new migration')
    p_new.add_argument('name', help='Migration name in snake_case (e.g. create_users_table)')
    p_new.add_argument(
        '--style', choices=['one', 'two'], default=None,
        help='one = single file with markers (dbmate style), two = separate up/down files. '
             'Defaults to MIGRATION_STYLE in .env, then "two".',
    )

    p_goto = sub.add_parser('goto', help='Migrate to a specific version (forward or backward)')
    p_goto.add_argument('version', type=int, metavar='VERSION',
                        help='Target migration version (numeric prefix of the filename)')
    p_goto.add_argument('--dry-run', action='store_true',
                        help='Print the SQL that would be executed without applying anything')

    p_show = sub.add_parser('show', help='Print SQL for a specific migration version')
    p_show.add_argument('version', type=int, metavar='VERSION',
                        help='Migration version (numeric prefix of the filename)')

    sub.add_parser('history', help='Show applied migrations with timestamps')

    sub.add_parser('report', help='Activity report: who applied what, and when')

    sub.add_parser('ddl', help='Verify the tracking table has all expected columns')

    p_baseline = sub.add_parser('baseline', help='Mark migrations applied without running SQL')
    p_baseline.add_argument('version', nargs='?', type=int, default=None, metavar='VERSION',
                            help='Mark up to this version (default: mark all)')

    sub.add_parser('repair', help='Remove orphaned tracking records from the database')

    sub.add_parser('undo', help='Interactively delete pending (unapplied) migration files')

    sub.add_parser('who', help='Show which database the current --db / .env points to')
    sub.add_parser('dbs', help='List all configured databases (.env and .env.* files)')

    p_efc = sub.add_parser(
        'env-from-config',
        help='Create or update .env with DATABASE_URL from config.xml',
    )
    p_efc.add_argument(
        'name', nargs='?', default='',
        help='Database entry name from config.xml (omit to list available entries)',
    )

    args = parser.parse_args()

    # Resolve env-file and path using --db, then env-from-config name, then defaults.
    # Priority: explicit flag > --db derivation > env-from-config name derivation > fallback.
    db = args.db

    if args.env_file is None:
        if db:
            args.env_file = f'.env.{db}'
        elif args.command == 'env-from-config' and args.name:
            args.env_file = f'.env.{args.name}'
        else:
            args.env_file = '.env'

    # Migrations directory is resolved after .env is loaded (below), so a
    # MIGRATIONS_DIR in the env can override the --db derivation.

    # Load .env — existing env vars and --url always win.
    # load_env_file is first-wins (it never overwrites an already-set var), so the
    # db-specific file (e.g. .env.ccv2_int1) MUST be loaded first to take precedence
    # for DATABASE_URL and friends. The base .env is loaded afterward only to fill in
    # globals like WASQL_PATH that the db-specific file doesn't define.
    load_env_file(args.env_file)
    if args.env_file != '.env':
        load_env_file('.env')

    # Progress timestamps: --no-timestamps flag > SCM_TIMESTAMPS in env/.env > on.
    global SHOW_TIMESTAMPS
    if args.no_timestamps:
        SHOW_TIMESTAMPS = False
    elif os.environ.get('SCM_TIMESTAMPS', '').strip().lower() in ('0', 'false', 'no', 'off'):
        SHOW_TIMESTAMPS = False

    # Resolve config.xml: --config flag > WASQL_PATH in .env > default
    if args.config is None:
        wasql_path = os.environ.get('WASQL_PATH', '').strip()
        if wasql_path:
            args.config = str(Path(wasql_path) / 'config.xml')
        else:
            args.config = _default_config

    # Resolve URL: --url flag > DATABASE_URL env var (possibly just loaded from .env)
    url = args.url or os.environ.get('DATABASE_URL')

    # Resolve migrations directory (after .env is loaded):
    #   explicit --path  >  MIGRATIONS_DIR / DBMATE_MIGRATIONS_DIR  >  --db derivation  >  ./migrations
    #
    # The --db derivation uses ./migrations/<name> when that per-database folder
    # exists, otherwise the shared ./migrations — so the same migration set can be
    # applied across many systems (dev/stage/prod), each with its own .env.<name>,
    # without keeping a separate per-database migrations folder.
    if args.path is None:
        env_dir = os.environ.get('MIGRATIONS_DIR') or os.environ.get('DBMATE_MIGRATIONS_DIR')
        if env_dir:
            args.path = env_dir
        elif db:
            args.path = _resolve_db_migrations_dir(db)
        elif args.command == 'env-from-config' and args.name:
            args.path = _resolve_db_migrations_dir(args.name)
        else:
            args.path = './migrations'

    # Resolve migrations table: MIGRATIONS_TABLE / DBMATE_MIGRATIONS_TABLE > default
    migrations_table = (
        os.environ.get('MIGRATIONS_TABLE')
        or os.environ.get('DBMATE_MIGRATIONS_TABLE')
        or 'schema_migrations'
    )

    # Resolve tracking schema: MIGRATIONS_SCHEMA / DBMATE_MIGRATIONS_SCHEMA > driver default.
    # Left as None here so each driver can pick its own default (Postgres → 'public').
    migrations_schema = (
        os.environ.get('MIGRATIONS_SCHEMA')
        or os.environ.get('DBMATE_MIGRATIONS_SCHEMA')
        or None
    )

    # Resolve migration style: --style flag > MIGRATION_STYLE env var > 'two'
    # Aliases: 'dbmate' = 'one', 'golang-migrate' = 'two'
    _STYLE_ALIASES = {'dbmate': 'one', 'golang-migrate': 'two', 'one': 'one', 'two': 'two'}
    if args.command == 'new' and args.style is None:
        env_style = os.environ.get('MIGRATION_STYLE', '').strip().lower()
        args.style = _STYLE_ALIASES.get(env_style, 'one')

    if args.command is None:
        parser.print_help()
        sys.exit(0)

    if args.command == 'version':
        print(f"scm.py {__version__}")
        return

    if args.command in ('learn', 'help'):
        cmd_learn()
        return

    if args.command == 'init':
        cmd_init(args.path, args.env_file)
        return

    if args.command == 'new':
        cmd_new(args.name, args.path, style=args.style)
        return

    if args.command == 'env-from-config':
        cmd_env_from_config(args.config, args.name, args.env_file, args.path)
        return

    if args.command == 'show':
        cmd_show(find_migrations(args.path), target_version=args.version)
        return

    if args.command == 'who':
        cmd_who(url, args.env_file, db=args.db,
                table=migrations_table, schema=migrations_schema)
        return

    if args.command == 'dbs':
        cmd_dbs()
        return

    if not url:
        sys.exit(
            "No database URL provided.\n"
            "Options:\n"
            "  1. Add DATABASE_URL=... to your .env file\n"
            "  2. Set the DATABASE_URL environment variable\n"
            "  3. Pass --url postgres://user:pass@host/dbname"
        )

    driver = get_driver(url, table=migrations_table, schema=migrations_schema)
    try:
        driver.ensure_migrations_table()
        driver.ensure_columns()  # add name/applied_by/migrated_by to pre-existing tables

        if args.command == 'reset':
            cmd_reset(driver, migrations_dir=args.path, force=args.force)
            return

        migrations = find_migrations(args.path)
        driver.backfill_names(migrations)  # record name for rows applied before the column existed

        if args.command == 'up':
            cmd_up(driver, migrations, n=args.n, dry_run=args.dry_run)
        elif args.command == 'down':
            cmd_down(driver, migrations, n=args.n, dry_run=args.dry_run)
        elif args.command == 'goto':
            cmd_goto(driver, migrations, target_version=args.version, dry_run=args.dry_run)
        elif args.command == 'history':
            cmd_history(driver, migrations)
        elif args.command == 'report':
            cmd_report(driver, migrations)
        elif args.command == 'ddl':
            cmd_ddl(driver)
        elif args.command == 'baseline':
            cmd_baseline(driver, migrations, target_version=args.version)
        elif args.command == 'repair':
            cmd_repair(driver, migrations)
        elif args.command == 'status':
            cmd_status(driver, migrations)
        elif args.command == 'undo':
            cmd_undo(driver, migrations, migrations_dir=args.path)
    finally:
        driver.close()


if __name__ == '__main__':
    main()
