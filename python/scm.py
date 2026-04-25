#!/usr/bin/env python3
"""
scm.py - Extensible database migration tool.

Supports two file styles:
  - Single-file (dbmate):    000001_name.sql  with -- migrate:up / -- migrate:down markers
  - Two-file (golang-migrate): 000001_name.up.sql + 000001_name.down.sql

Commands:
  scm.py init                    Create migrations dir and .env stub
  scm.py env-from-config [name]  Set DATABASE_URL from WaSQL config.xml + run init
  scm.py up [N]                  Apply all (or N) pending migrations
  scm.py down [N]                Roll back N migrations (default 1)
  scm.py status                  Show applied/pending status
  scm.py new <name>              Create new migration file(s)
  scm.py reset [--force]         Clear migration history and delete all migration files
  scm.py undo                    Interactively delete pending (unapplied) migration files
  scm.py learn                   Print a quick-start reference
  scm.py version                 Print version and exit

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
  WASQL_PATH            Directory containing config.xml (used by env-from-config)
  DBMATE_MIGRATIONS_DIR      alias for MIGRATIONS_DIR
  DBMATE_MIGRATIONS_TABLE    alias for MIGRATIONS_TABLE

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
import xml.etree.ElementTree as ET
from datetime import datetime, timedelta, timezone
from pathlib import Path
from urllib.parse import urlparse, urlunparse, quote

__version__ = '1.26.5'


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

    with path.open(encoding='utf-8') as f:
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

    def __init__(self, url, table='schema_migrations'):
        self.url = url
        self.table = table
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

    def commit(self):
        self.conn.commit()

    def rollback(self):
        self.conn.rollback()

    def ensure_migrations_table(self):
        raise NotImplementedError

    def applied_versions(self):
        """Return a set of version ints that have been applied."""
        raise NotImplementedError

    def record_migration(self, version):
        raise NotImplementedError

    def remove_migration(self, version):
        raise NotImplementedError


# ---------------------------------------------------------------------------
# Built-in drivers
# ---------------------------------------------------------------------------

@register_driver(['postgres', 'postgresql'])
class PostgresDriver(BaseDriver):

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

    def execute_script(self, sql):
        """Pass the full script directly — handles dollar-quoting and multi-statement."""
        cur = self.conn.cursor()
        cur.execute(sql)
        return cur

    def ensure_migrations_table(self):
        self.execute(f"""
            CREATE TABLE IF NOT EXISTS {self.table} (
                version varchar(128) PRIMARY KEY NOT NULL,
                applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        """)
        self.commit()

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (%s)", [str(version)])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = %s", [str(version)])


@register_driver(['mysql', 'mariadb', 'mysqli'])
class MySQLDriver(BaseDriver):

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

    def ensure_migrations_table(self):
        self.execute(f"""
            CREATE TABLE IF NOT EXISTS {self.table} (
                version varchar(128) PRIMARY KEY NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        """)
        self.commit()

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (%s)", [str(version)])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = %s", [str(version)])


@register_driver(['mssql', 'sqlserver'])
class SQLServerDriver(BaseDriver):
    """
    Requires pyodbc + ODBC Driver 17/18, or pymssql.
    pip install pyodbc
    pip install pymssql
    """

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
            self._ph = '%s'
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
            self._ph = '?'
            return
        except ImportError:
            pass
        sys.exit("No SQL Server driver found. Install one:\n  pip install pymssql\n  pip install pyodbc")

    def ensure_migrations_table(self):
        self.execute(f"""
            IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = '{self.table}')
            CREATE TABLE {self.table} (
                version varchar(128) NOT NULL PRIMARY KEY,
                applied_at DATETIME2 NOT NULL DEFAULT GETUTCDATE()
            )
        """)
        self.commit()

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES ({self._ph})", [str(version)])

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

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = {self._ph}", [str(version)])


@register_driver(['sqlite', 'sqlite3'])
class SQLiteDriver(BaseDriver):

    def connect(self):
        import sqlite3
        p = urlparse(self.url)
        db_path = p.netloc + p.path  # handles sqlite:///path and sqlite://path
        self.conn = sqlite3.connect(db_path or ':memory:')
        self.conn.isolation_level = 'DEFERRED'

    def ensure_migrations_table(self):
        self.execute(f"""
            CREATE TABLE IF NOT EXISTS {self.table} (
                version varchar(128) PRIMARY KEY NOT NULL,
                applied_at TEXT NOT NULL DEFAULT (datetime('now'))
            )
        """)
        self.commit()

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (?)", [str(version)])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = ?", [str(version)])


@register_driver(['ctree'])
class CTreeDriver(BaseDriver):
    """
    Requires pyodbc + Faircom ODBC Driver installed on the system.
    pip install pyodbc
    URL: ctree://user:pass@host:6597/dbname
    """

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
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
                )
            """)
            self.commit()
        except Exception:
            self.rollback()  # table already exists

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (?)", [str(version)])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = ?", [str(version)])


@register_driver(['firebird'])
class FirebirdDriver(BaseDriver):
    """
    Requires fdb. pip install fdb
    URL: firebird://user:pass@host/dbname
    """

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
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
                )
            """)
            self.commit()
        except Exception:
            self.rollback()  # table already exists

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (?)", [str(version)])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = ?", [str(version)])


@register_driver(['hana'])
class HanaDriver(BaseDriver):
    """
    Requires hdbcli. pip install hdbcli
    URL: hana://user:pass@host:39015/
    """

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
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
                )
            """)
            self.commit()
        except Exception:
            self.rollback()

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (?)", [str(version)])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = ?", [str(version)])


@register_driver(['snowflake'])
class SnowflakeDriver(BaseDriver):
    """
    Requires snowflake-connector-python. pip install snowflake-connector-python
    URL: snowflake://user:pass@account/database?warehouse=X&schema=Y&role=Z
    """

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
                applied_at TIMESTAMP_TZ NOT NULL DEFAULT CURRENT_TIMESTAMP()
            )
        """)
        self.commit()

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (%s)", [str(version)])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = %s", [str(version)])


@register_driver(['oracle'])
class OracleDriver(BaseDriver):
    """
    Requires oracledb (modern) or cx_Oracle (legacy).
    pip install oracledb
    URL: oracle://user:pass@host:1521/service_name
    """

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
                    applied_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL
                )
            """)
            self.commit()
        except Exception:
            self.rollback()  # ORA-00955: table already exists

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {int(row[0]) for row in cur.fetchall()}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (:1)", [str(version)])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = :1", [str(version)])


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
            return filepath.read_text(encoding='utf-8')
        except UnicodeDecodeError:
            sys.exit(f"Cannot read {filepath.name}: file is not valid UTF-8.")

    # Two-file style takes precedence
    for version, (label, up_path) in up_files.items():
        up_sql = _read(up_path).strip()
        if not up_sql:
            sys.exit(f"Empty migration file: {up_path.name}")
        down_sql = None
        if version in down_files:
            down_sql = _read(down_files[version][1]).strip() or None
        migrations.append((version, label, up_sql, down_sql))

    # Single-file style (skip if version already found in two-file)
    for version, (label, filepath) in single_files.items():
        if version in up_files:
            continue
        up_sql, down_sql = parse_single_file(_read(filepath), filepath.name)
        migrations.append((version, label, up_sql, down_sql))

    return sorted(migrations, key=lambda x: x[0])


def parse_single_file(content, filename=''):
    """Split dbmate-style single file on -- migrate:up / -- migrate:down markers."""
    up_lines   = []
    down_lines = []
    current    = None

    for line in content.splitlines(keepends=True):
        stripped = line.strip().lower()
        if stripped == '-- migrate:up':
            current = 'up'
        elif stripped == '-- migrate:down':
            current = 'down'
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

    return up_sql, down_sql


# ---------------------------------------------------------------------------
# Commands
# ---------------------------------------------------------------------------

def cmd_up(driver, migrations, n=None):
    applied = driver.applied_versions()
    pending = [m for m in migrations if m[0] not in applied]

    if not pending:
        print("No pending migrations.")
        return

    if n is not None:
        pending = pending[:n]

    for version, label, up_sql, _ in pending:
        print(f"Applying  {version}_{label} ...", end=' ', flush=True)
        try:
            driver.execute_script(up_sql)
            driver.record_migration(version)
            driver.commit()
            print("OK")
        except Exception as e:
            driver.rollback()
            print("FAILED")
            sys.exit(f"Error: {e}")


def cmd_down(driver, migrations, n=1):
    applied = driver.applied_versions()
    applied_migrations = [m for m in reversed(migrations) if m[0] in applied]

    if not applied_migrations:
        print("Nothing to roll back.")
        return

    targets = applied_migrations[:n]

    for version, label, _, down_sql in targets:
        if not down_sql:
            sys.exit(
                f"No down migration for {version}_{label} — cannot roll back.\n"
                "Add a down migration or roll back manually."
            )
        print(f"Rollback  {version}_{label} ...", end=' ', flush=True)
        try:
            driver.execute_script(down_sql)
            driver.remove_migration(version)
            driver.commit()
            print("OK")
        except Exception as e:
            driver.rollback()
            print("FAILED")
            sys.exit(f"Error: {e}")


def cmd_status(driver, migrations):
    applied  = driver.applied_versions()
    known    = {m[0] for m in migrations}
    orphaned = sorted(applied - known)  # in DB but no file on disk

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

    header = f"{'Version':<{col_v}}  {'Label':<{col_l}}  {'Status':<10}  Down?"
    print(bold(blue(header)))
    print("-" * len(header))

    for version, label, _, down_sql in migrations:
        is_applied = version in applied
        status   = "applied" if is_applied else "pending"
        has_down = "yes"     if down_sql   else "no"
        row = f"{version:<{col_v}}  {label:<{col_l}}  {status:<10}  {has_down}"
        print(gray(row) if is_applied else green(row))

    for version in orphaned:
        row = f"{version:<{col_v}}  {'<file missing>':<{col_l}}  {'orphaned':<10}  ?"
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

    print(bold(f'\n  DBmigrate {__version__} — Quick Reference Guide'))
    print(rule)

    section('1. SETUP')
    print('  Create a .env file in your project directory:')
    print(f'    {green("DATABASE_URL")}=postgres://user:pass@host:5432/mydb')
    print(f'    {green("MIGRATIONS_DIR")}=./migrations      {dim("# default")}')
    print(f'    {green("MIGRATION_STYLE")}=one              {dim("# one=single file  two=separate up/down files")}')
    print()
    print('  Or pull settings directly from WaSQL config.xml:')
    print(f'    {yellow("scm env-from-config")} <dbname>')

    section('2. DAILY WORKFLOW')
    rows = [
        ('scm new <name>',   'Create a timestamped migration file in ./migrations'),
        ('scm up',           'Apply all pending migrations'),
        ('scm up 1',         'Apply only the next pending migration'),
        ('scm down',         'Roll back the last applied migration'),
        ('scm down 3',       'Roll back the last 3 migrations'),
        ('scm status',       'Show what is applied vs pending'),
        ('scm reset',        'Wipe history + delete migration files (dev only)'),
        ('scm reset --force','Same but skip the confirmation prompt'),
        ('scm undo',         'Interactively delete pending (unapplied) migration files'),
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
        ('PostgreSQL: CREATE INDEX CONCURRENTLY cannot run inside a',
         '  transaction — put it alone in its own migration file.'),
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
    for i, (version, label, _, _) in enumerate(pending, 1):
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

    for version, label, _, _ in targets:
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
            '# MIGRATIONS_TABLE=schema_migrations\n',
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
# Driver resolution
# ---------------------------------------------------------------------------

def get_driver(url, table='schema_migrations'):
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

    driver = DRIVERS[scheme](url, table=table)
    try:
        driver.connect()
    except Exception as e:
        sys.exit(f"Failed to connect ({redact_url(url)}): {e}")
    return driver


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------

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

    # Default config.xml path: one directory above this script (same as config.py)
    _script_dir = Path(__file__).resolve().parent
    _default_config = str(_script_dir.parent / 'config.xml')

    parser.add_argument(
        '--config', default=None, metavar='FILE',
        help=f'Path to config.xml. Priority: this flag > WASQL_PATH in .env > {_default_config}',
    )
    parser.add_argument(
        '--db', default=None, metavar='NAME',
        help='Database name. Sets --env-file to .env.<name> and --path to ./migrations/<name> '
             'unless those are explicitly provided.',
    )

    sub = parser.add_subparsers(dest='command', metavar='command')

    p_up = sub.add_parser('up', help='Apply pending migrations')
    p_up.add_argument('n', nargs='?', type=int, metavar='N',
                      help='Max number of migrations to apply (default: all)')

    p_down = sub.add_parser('down', help='Roll back migrations')
    p_down.add_argument('n', nargs='?', type=int, default=1, metavar='N',
                        help='Number of migrations to roll back (default: 1)')

    sub.add_parser('init',    help='Create migrations directory and .env stub')
    sub.add_parser('status',  help='Show applied/pending status of all migrations')
    sub.add_parser('version', help='Print scm.py version and exit')
    sub.add_parser('learn',   help='Print a quick-start reference')

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

    sub.add_parser('undo', help='Interactively delete pending (unapplied) migration files')

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

    if args.path is None:
        if db:
            args.path = f'./migrations/{db}'
        elif args.command == 'env-from-config' and args.name:
            args.path = f'./migrations/{args.name}'

    # Load .env — existing env vars and --url always win
    load_env_file(args.env_file)

    # Resolve config.xml: --config flag > WASQL_PATH in .env > default
    if args.config is None:
        wasql_path = os.environ.get('WASQL_PATH', '').strip()
        if wasql_path:
            args.config = str(Path(wasql_path) / 'config.xml')
        else:
            args.config = _default_config

    # Resolve URL: --url flag > DATABASE_URL env var (possibly just loaded from .env)
    url = args.url or os.environ.get('DATABASE_URL')

    # Resolve migrations directory: resolved above > MIGRATIONS_DIR / DBMATE_MIGRATIONS_DIR > default
    if args.path is None:
        args.path = (
            os.environ.get('MIGRATIONS_DIR')
            or os.environ.get('DBMATE_MIGRATIONS_DIR')
            or './migrations'
        )

    # Resolve migrations table: MIGRATIONS_TABLE / DBMATE_MIGRATIONS_TABLE > default
    migrations_table = (
        os.environ.get('MIGRATIONS_TABLE')
        or os.environ.get('DBMATE_MIGRATIONS_TABLE')
        or 'schema_migrations'
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

    if args.command == 'learn':
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

    if not url:
        sys.exit(
            "No database URL provided.\n"
            "Options:\n"
            "  1. Add DATABASE_URL=... to your .env file\n"
            "  2. Set the DATABASE_URL environment variable\n"
            "  3. Pass --url postgres://user:pass@host/dbname"
        )

    driver = get_driver(url, table=migrations_table)
    try:
        driver.ensure_migrations_table()

        if args.command == 'reset':
            cmd_reset(driver, migrations_dir=args.path, force=args.force)
            return

        migrations = find_migrations(args.path)

        if args.command == 'up':
            cmd_up(driver, migrations, n=args.n)
        elif args.command == 'down':
            cmd_down(driver, migrations, n=args.n)
        elif args.command == 'status':
            cmd_status(driver, migrations)
        elif args.command == 'undo':
            cmd_undo(driver, migrations, migrations_dir=args.path)
    finally:
        driver.close()


if __name__ == '__main__':
    main()
