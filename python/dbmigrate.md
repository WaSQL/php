# dbmigrate.py

A lightweight, extensible database migration tool written in Python. Supports plain SQL migration
files and works with any database that has a Python DB-API 2.0 driver. Inspired by
[dbmate](https://github.com/amacneil/dbmate) and [golang-migrate](https://github.com/golang-migrate/migrate).

---

## Features

- Plain SQL migration files — no proprietary DSL
- Two file styles: single-file (dbmate) or two-file (golang-migrate)
- `init`, `up`, `down`, `status`, `new`, `version`, and `env-from-config` commands
- Timestamp-versioned migrations to avoid conflicts across developers
- Tracks applied migrations in a configurable table (default: `schema_migrations`)
- Built-in drivers for PostgreSQL, MySQL/MariaDB, SQL Server, SQLite, Oracle, SAP HANA, Snowflake, FairCom cTree, and Firebird
- `.env` file support compatible with dbmate
- `env-from-config` pulls connection settings directly from WaSQL's `config.xml`
- Easily extended to support any database with a Python driver

---

## Requirements

Python 3.8+. Install the driver package for your database:

| Database         | Package                          | Install                                       |
|------------------|----------------------------------|-----------------------------------------------|
| PostgreSQL       | psycopg2                         | `pip install psycopg2-binary`                 |
| MySQL / MariaDB  | mysql-connector-python or pymysql| `pip install mysql-connector-python`          |
| SQL Server       | pyodbc                           | `pip install pyodbc`                          |
| SQLite           | built-in                         | nothing                                       |
| Oracle           | oracledb or cx_Oracle            | `pip install oracledb`                        |
| SAP HANA         | hdbcli                           | `pip install hdbcli`                          |
| Snowflake        | snowflake-connector-python       | `pip install snowflake-connector-python`      |
| FairCom cTree    | pyodbc + Faircom ODBC Driver     | `pip install pyodbc` + install Faircom driver |
| Firebird         | fdb                              | `pip install fdb`                             |

MySQL connections try `mysql-connector-python` first and fall back to `pymysql` automatically —
install whichever you have. Both use `charset='utf8mb4'` by default.

---

## Installation

No installation required. Copy `dbmigrate.py` into your project or onto your `$PATH`.

```bash
cp dbmigrate.py /usr/local/bin/dbmigrate
chmod +x /usr/local/bin/dbmigrate
```

---

## Quick Start

The fastest way to get started is with `env-from-config` (WaSQL projects) or `init`:

```bash
# WaSQL project: pull settings from config.xml, create migrations folder and .env in one step
python dbmigrate.py env-from-config wasql_test_17

# Non-WaSQL project: create migrations folder and .env stub
python dbmigrate.py init

# Edit .env to set DATABASE_URL, then:
python dbmigrate.py new create_users_table
python dbmigrate.py up
```

---

## .env File

`dbmigrate.py` automatically loads `.env` from the current directory before running.
All settings can be placed here so you never have to pass flags on every command.

```bash
# .env — minimum required
DATABASE_URL=mysql://user:pass@localhost/mydb

# Optional settings (shown with defaults)
MIGRATION_STYLE=one
# MIGRATIONS_DIR=./migrations
# MIGRATIONS_TABLE=schema_migrations
```

### Supported syntax

```bash
# Plain
DATABASE_URL=postgres://user:pass@host/mydb

# Quoted
DATABASE_URL="postgres://user:pass@host/mydb"

# export prefix (optional)
export DATABASE_URL="postgres://user:pass@host/mydb"

# Inline comments
DATABASE_URL=postgres://user:pass@host/mydb  # production db
```

**Existing environment variables always take precedence over `.env`.** Override for one
command by exporting in the shell:

```bash
DATABASE_URL="postgres://user:pass@staging-host/mydb" python dbmigrate.py status
```

To use a different `.env` file:

```bash
python dbmigrate.py --env-file .env.production status
python dbmigrate.py --env-file .env.staging up
```

### .env variables reference

| Variable | Aliases | Default | Description |
|---|---|---|---|
| `DATABASE_URL` | — | *(required)* | Database connection URL |
| `MIGRATION_STYLE` | — | `one` | File style for `new`: `one`/`dbmate` = single file, `two`/`golang-migrate` = separate up/down files |
| `MIGRATIONS_DIR` | `DBMATE_MIGRATIONS_DIR` | `./migrations` | Path to migrations directory |
| `MIGRATIONS_TABLE` | `DBMATE_MIGRATIONS_TABLE` | `schema_migrations` | Name of the tracking table in the database |

---

## Connection URL

The database connection URL is resolved in this order (first match wins):

1. `--url` flag
2. `DATABASE_URL` environment variable
3. `DATABASE_URL` in `.env`

```bash
# PostgreSQL
DATABASE_URL="postgres://user:pass@localhost:5432/mydb"
DATABASE_URL="postgres://user:pass@db-host:5432/mydb?sslmode=require"

# MySQL / MariaDB (mysqli is also accepted)
DATABASE_URL="mysql://user:pass@localhost:3306/mydb"

# SQL Server
DATABASE_URL="mssql://user:pass@localhost:1433/mydb"

# SQLite
DATABASE_URL="sqlite:///path/to/db.sqlite3"

# Oracle (path is the service name)
DATABASE_URL="oracle://user:pass@host:1521/myservice"

# SAP HANA
DATABASE_URL="hana://user:pass@host:39015/"

# Snowflake (warehouse, schema, role are optional query params)
DATABASE_URL="snowflake://user:pass@myaccount.snowflakecomputing.com/mydb?warehouse=COMPUTE_WH&schema=PUBLIC&role=SYSADMIN"

# FairCom cTree (requires Faircom ODBC Driver installed)
DATABASE_URL="ctree://user:pass@host:6597/mydb"

# Firebird
DATABASE_URL="firebird://user:pass@host/path/to/database.fdb"
```

The `--url` flag overrides everything for a single command:

```bash
python dbmigrate.py --url postgres://user:pass@host/mydb status
```

---

## Commands

### `init` — Set up a new project

```bash
python dbmigrate.py init
python dbmigrate.py --path ./db/migrations init
```

Creates the migrations directory and a `.env` stub if they don't already exist. Existing
files are left untouched. Shows next steps when done.

```
  created migrations
  created .env

Next steps:
  Edit .env and set DATABASE_URL, or run:
    dbmigrate.py env-from-config <name>
  dbmigrate.py new <migration_name>
  dbmigrate.py up
```

The generated `.env` stub:

```bash
DATABASE_URL=
MIGRATION_STYLE=one
# MIGRATIONS_DIR=./migrations
# MIGRATIONS_TABLE=schema_migrations
```

---

### `env-from-config` — Configure from WaSQL config.xml

```bash
# List all supported databases defined in config.xml
python dbmigrate.py env-from-config

# Write DATABASE_URL to .env and run init
python dbmigrate.py env-from-config <name>
```

Reads `../config.xml` (relative to `dbmigrate.py`) and builds a `DATABASE_URL` from the
named `<database>` entry's attributes (`dbtype`, `dbhost`, `dbport`, `dbuser`, `dbpass`,
`dbname`). Creates or updates `DATABASE_URL` in `.env` in-place, then automatically runs
`init` to create the migrations folder.

Only database entries with supported dbtypes are listed: `postgres`, `mysql`, `mysqli`,
`mariadb`, `mssql`, `sqlserver`, `sqlite`, `ctree`, `firebird`, `hana`, `snowflake`,
`oracle`.

Snowflake entries automatically encode `dbschema`, `dbwarehouse`, and `dbrole` as query
parameters in the URL. Passwords with special characters are percent-encoded.

```bash
python dbmigrate.py env-from-config wasql_test_17
# Created .env
#   DATABASE_URL=mysql://wasql_dbuser:***@localhost/wasql_test_17
#   created migrations
#   exists  .env
```

Use `--config` to point at a different `config.xml`:

```bash
python dbmigrate.py --config /path/to/config.xml env-from-config mydb
```

---

### `new` — Create a migration

```bash
python dbmigrate.py new <name> [--style one|dbmate|two|golang-migrate]
```

Generates a timestamped migration file (or pair) in the migrations directory.
The style defaults to `MIGRATION_STYLE` in `.env` (which defaults to `one`).

```bash
# Single-file style (default when MIGRATION_STYLE=one or MIGRATION_STYLE=dbmate)
python dbmigrate.py new create_users_table
# → migrations/20240601120000_create_users_table.sql

# Two-file style (use --style two or set MIGRATION_STYLE=two in .env)
python dbmigrate.py new create_users_table --style two
# → migrations/20240601120000_create_users_table.up.sql
# → migrations/20240601120000_create_users_table.down.sql

# Custom migrations path
python dbmigrate.py --path ./db/migrations new create_orders
```

Migration names must contain only letters, digits, underscores, and hyphens, and must
start with a letter or digit.

---

### `status` — Show migration status

```bash
python dbmigrate.py status
```

Lists all migration files and whether each has been applied. Also reports versions
recorded in the database that have no file on disk (orphaned).

```
Version          Label                                Status      Down?
------------------------------------------------------------------------
20240601120000   create_users_table                   applied     yes
20240602083000   add_email_index                      applied     yes
20240603094500   add_orders_table                     pending     yes
20240604110000   add_audit_log                        pending     no
20240530000000   <file missing>                       orphaned    ?

4 migrations: 2 applied, 2 pending.
1 orphaned (applied in DB but no file on disk).
```

The `Down?` column indicates whether a rollback migration exists. Orphaned entries
mean a version was applied but the file was later deleted — investigate before
running `up` or `down`.

---

### `up` — Apply migrations

```bash
# Apply all pending migrations
python dbmigrate.py up

# Apply the next N pending migrations
python dbmigrate.py up 1
python dbmigrate.py up 3
```

Each migration runs in a transaction. On failure the transaction rolls back and the
command exits immediately — migrations applied before the failure are preserved.

SQL statements are split by a context-aware parser that handles semicolons inside
`-- line comments`, `/* block comments */`, and `'string literals'`. PostgreSQL migrations
are passed as a single string so dollar-quoted blocks (`$$...$$`) work natively. SQL Server
migrations are split on `GO` batch separators in addition to semicolons.

---

### `down` — Roll back migrations

```bash
# Roll back the last migration (default)
python dbmigrate.py down

# Roll back the last N migrations
python dbmigrate.py down 3
```

If a migration has no down script, `down` exits with an error rather than silently skipping.

---

### `version` — Print version

```bash
python dbmigrate.py version
# dbmigrate.py 1.0.0
```

Requires no database connection.

---

## Global Flags

| Flag | Default | Description |
|---|---|---|
| `--url URL` | — | Database URL. Overrides `DATABASE_URL` and `.env` |
| `--env-file FILE` | `.env` | Path to `.env` file |
| `--path DIR` | `./migrations` (or `MIGRATIONS_DIR`) | Migrations directory |
| `--config FILE` | `../config.xml` | Path to WaSQL `config.xml` (used by `env-from-config`) |

---

## File Naming

Migration files are sorted and applied by their numeric version prefix. Timestamps
(recommended) or plain integers both work.

```
20240601120000_create_users.up.sql
20240601120000_create_users.down.sql
20240602083000_add_email_index.up.sql
```

Rules:

- Version prefix must be all digits
- Separator is `_` or `-`
- Two-file style: suffixes are `.up.sql` and `.down.sql`
- Single-file style: suffix is `.sql` with `-- migrate:up` / `-- migrate:down` markers
- If both styles exist for the same version, two-file takes precedence
- Duplicate version numbers are an error and reported with both filenames

---

## File Styles

### Single-file style (dbmate) — default

`20240601120000_create_users.sql`
```sql
-- migrate:up
CREATE TABLE users (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- migrate:down
DROP TABLE users;
```

### Two-file style (golang-migrate)

`20240601120000_create_users.up.sql`
```sql
CREATE TABLE users (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

`20240601120000_create_users.down.sql`
```sql
DROP TABLE users;
```

Both styles can coexist in the same migrations directory across different versions.

---

## Schema Migrations Table

`dbmigrate.py` creates and manages the tracking table automatically. The table name
defaults to `schema_migrations` and can be changed via `MIGRATIONS_TABLE` (or
`DBMATE_MIGRATIONS_TABLE`) in `.env`.

```sql
-- PostgreSQL
CREATE TABLE schema_migrations (
    version    BIGINT PRIMARY KEY,
    applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- MySQL / MariaDB
CREATE TABLE schema_migrations (
    version    BIGINT PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- SQL Server
CREATE TABLE schema_migrations (
    version    BIGINT PRIMARY KEY,
    applied_at DATETIME2 NOT NULL DEFAULT GETUTCDATE()
);
```

The version stored is the numeric prefix of the migration filename (e.g. `20240601120000`).

---

## Multiple Repos / Multiple Databases

Each repo keeps its own `.env` with its own `DATABASE_URL`. Running `dbmigrate.py` from
inside a repo picks up that repo's `.env` automatically.

```
repo-a/
  .env               # DATABASE_URL=postgres://...prod-a.../app_a
  migrations/

repo-b/
  .env               # DATABASE_URL=mysql://...prod-b.../app_b
  migrations/
```

### Makefile pattern

```makefile
MIGRATE = python /path/to/dbmigrate.py --path ./migrations

.PHONY: migrate-new migrate-up migrate-down migrate-status

migrate-new:
	$(MIGRATE) new $(name)

migrate-up:
	$(MIGRATE) up

migrate-down:
	$(MIGRATE) down $(n)

migrate-status:
	$(MIGRATE) status
```

---

## Adding a Custom Driver

Subclass `BaseDriver`, implement five methods, and register with `@register_driver`.
Use `self.table` (not a hardcoded string) for the tracking table name so `MIGRATIONS_TABLE`
in `.env` is respected.

```python
@register_driver(['mydb'])
class MyDBDriver(BaseDriver):

    def connect(self):
        import mydb_driver
        p = urlparse(self.url)
        self.conn = mydb_driver.connect(
            host=p.hostname,
            port=p.port or 5000,
            user=p.username,
            password=p.password,
            database=p.path.lstrip('/'),
        )

    def ensure_migrations_table(self):
        try:
            self.execute(f"""
                CREATE TABLE {self.table} (
                    version    BIGINT PRIMARY KEY,
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
                )
            """)
            self.commit()
        except Exception:
            self.rollback()  # table already exists

    def applied_versions(self):
        cur = self.execute(f"SELECT version FROM {self.table} ORDER BY version")
        return {{row[0] for row in cur.fetchall()}}

    def record_migration(self, version):
        self.execute(f"INSERT INTO {self.table} (version) VALUES (?)", [version])

    def remove_migration(self, version):
        self.execute(f"DELETE FROM {self.table} WHERE version = ?", [version])
```

The driver is activated automatically when its URL scheme is detected in `DATABASE_URL`.

---

## Comparison with dbmate and golang-migrate

| Feature                       | dbmigrate.py       | dbmate         | golang-migrate |
|-------------------------------|--------------------|----------------|----------------|
| Language                      | Python             | Go             | Go             |
| PostgreSQL                    | yes                | yes            | yes            |
| MySQL / MariaDB               | yes                | yes            | yes            |
| SQL Server (+ GO batches)     | yes                | no             | yes            |
| SQLite                        | yes                | yes            | yes            |
| Oracle                        | yes (built-in)     | no             | no             |
| SAP HANA                      | yes (built-in)     | no             | no             |
| Snowflake                     | yes (built-in)     | no             | no             |
| FairCom cTree                 | yes (built-in)     | no             | no             |
| Firebird                      | yes (built-in)     | no             | no             |
| Single-file migrations        | yes                | yes            | no             |
| Two-file migrations           | yes                | no             | yes            |
| Down migrations               | yes                | yes            | yes            |
| `.env` support                | yes                | yes            | no             |
| `new` command                 | yes                | yes            | no             |
| `init` command                | yes                | yes            | no             |
| Configurable tracking table   | yes                | yes            | yes            |
| WaSQL config.xml integration  | yes                | no             | no             |
| `goto` version                | no                 | no             | yes            |
| Extensible drivers            | yes                | no             | limited        |
| Dependencies                  | pip packages only  | none (binary)  | none (binary)  |

---

## Writing Safe Migrations

### Always required

- **Write a down migration.** Every `up` should have a matching `down` unless the operation
  is truly irreversible. If skipping, add a comment explaining why.
- **Use `IF EXISTS` / `IF NOT EXISTS` guards** so re-runs after partial failure don't error:
  ```sql
  CREATE TABLE IF NOT EXISTS orders (...);
  DROP TABLE IF EXISTS old_sessions;
  DROP INDEX IF EXISTS idx_old;
  ```
- **Never modify an applied migration.** Once run anywhere, treat it as immutable. Create a
  new migration to correct it.
- **Never include secrets, passwords, or PII** in migration SQL.

### Adding columns to existing tables

Adding a `NOT NULL` column without a default fails on tables that already have rows:

```sql
-- Safe: add nullable first, backfill, then constrain in a later migration
ALTER TABLE users ADD COLUMN tier VARCHAR(20);

-- Also safe: default that applies retroactively
ALTER TABLE users ADD COLUMN tier VARCHAR(20) NOT NULL DEFAULT 'free';
```

### Destructive operations

| Operation | Risk | Mitigation |
|-----------|------|------------|
| `DROP TABLE` | Permanent data loss | Always `IF EXISTS`; ensure down recreates it |
| `DROP COLUMN` | Permanent data loss | Deploy app changes first |
| `TRUNCATE` | Empties entire table | Rarely appropriate in a migration |
| `DELETE` without `WHERE` | Empties entire table | Always add a `WHERE` clause |
| Type change | May truncate data | Test on a production-volume copy first |
| Rename table/column | Breaks running app | Must coordinate with a code deploy |

### Database-specific DDL behaviour

**PostgreSQL** — DDL is transactional. `CREATE INDEX CONCURRENTLY` cannot run inside a
transaction; put it alone in its own migration file.

**MySQL / MariaDB** — DDL is **not transactional**. A failed migration may partially apply
and cannot be automatically rolled back. Keep each DDL change in its own file.

**SQL Server** — DDL is transactional. Use `GO` to separate batches that must run
independently.

**SQLite** — DDL is transactional. Does not support `ADD COLUMN IF NOT EXISTS` or
`DROP COLUMN` before version 3.35.0.

### Data migrations

- Keep data migrations separate from schema migrations.
- Add a `WHERE` clause to every `UPDATE` and `DELETE`.
- For large tables, batch updates in chunks to avoid long locks.

---

## Security Considerations

### Credentials

- Store `DATABASE_URL` in `.env`, never hardcoded in scripts or committed to version control.
- Add `.env` to `.gitignore`.
- Use a dedicated migration user with only the permissions needed — not the application's
  runtime user or a superuser.
- The `--url` flag exposes credentials in shell history and process lists. Prefer `.env`.

### Principle of least privilege

```sql
-- PostgreSQL: migration user
GRANT CONNECT ON DATABASE mydb TO migrator;
GRANT USAGE, CREATE ON SCHEMA public TO migrator;
GRANT INSERT, DELETE ON schema_migrations TO migrator;

-- MySQL: migration user
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES ON mydb.* TO 'migrator'@'%';
GRANT INSERT, DELETE ON mydb.schema_migrations TO 'migrator'@'%';
```

### Concurrency

`dbmigrate.py` does not acquire an advisory lock. Running two `up` commands simultaneously
against the same database will cause the second to fail on the duplicate-key insert — but
on MySQL any DDL already executed is permanent. Ensure only one migration process runs at
a time (CI serialization, deployment locks, etc.).

### Running in CI/CD

- Mask `DATABASE_URL` in CI settings so it never appears in logs.
- Run `status` before `up` so the log shows exactly what will be applied.
- Consider `up 1` in automated pipelines so a bad migration fails fast.

---

## Troubleshooting

**`Migrations directory not found`**
Run `python dbmigrate.py init` (or `env-from-config`) to create it.

**`No -- migrate:up marker found`**
Single-file migrations must contain a `-- migrate:up` line. Check for typos.

**`No down migration for X — cannot roll back`**
The migration has no down script. Add one or roll back manually.

**`Unsupported database scheme`**
The URL scheme is not recognized. Check your `DATABASE_URL` prefix.

**`Duplicate migration version N: file1 and file2`**
Two files share the same numeric prefix. Rename one before running any commands.

**`Invalid migration name`**
Names must use only letters, digits, underscores, and hyphens, starting with a letter or digit.

**`Failed to connect (...): ...`**
Connection failed. The password is masked in the error. Check host, port, credentials,
and network reachability.

**`No MySQL driver found`**
Install either: `pip install mysql-connector-python` or `pip install pymysql`.

**`Database 'X' not found in config.xml`**
Run `python dbmigrate.py env-from-config` (no name) to list supported entries.

**Migration applied but schema change is missing**
The migration may have partially succeeded. Check the database manually. If the tracking
row exists but the schema change did not apply, remove the row and re-run `up` after
fixing the SQL.
