# postgresql.php — PostgreSQL driver

Queries PostgreSQL through **libpq** (`pg_connect`). The largest driver in this directory and a first-class `dbtype` — it can serve both a secondary connection and a whole WaSQL site's main database (`isPostgreSQL()`).

## At a glance
| | |
|---|---|
| `dbtype` | `postgresql` |
| Connects via | `pg_connect()` with a libpq conninfo string |
| Requires | PHP `pgsql` extension |
| Handle global | `$dbh_postgresql` |
| Auth | user/password, `sslmode`, or client-certificate auth |
| File | `php/extras/databases/postgresql.php` (~4617 lines, 59 functions) |

## config.xml
```xml
<database name="postgres_ods" dbtype="postgresql"
    dbhost="db.x.com" dbport="5432" dbname="ods"
    dbuser="svc" dbpass="…" dbschema="public" />
```

With a client certificate:
```xml
<database name="postgres_ods" dbtype="postgresql"
    dbhost="db.x.com" dbname="ods" dbuser="svc"
    dbcert="/etc/wasql/certs/ods.crt"
    dbkey="/etc/wasql/certs/ods.key"
    dbca="/etc/wasql/certs/ca.crt" />
```

Or hand-write the whole conninfo string, which is passed to libpq **verbatim**:
```xml
<database name="postgres_ods" dbtype="postgresql"
    connect="host=db.x.com port=5432 dbname=ods user=svc sslmode=verify-full sslcert=/etc/wasql/certs/ods.crt sslkey=/etc/wasql/certs/ods.key sslrootcert=/etc/wasql/certs/ca.crt connect_timeout=5" />
```

| attribute | maps to |
|---|---|
| `dbhost` / `dbport` / `dbname` / `dbuser` / `dbpass` | `host` / `port` / `dbname` / `user` / `password` |
| `dbschema` | default schema, used by the metadata functions |
| `connect` | a complete conninfo string, used instead of building one |
| `dbcert` | `sslcert` (client certificate) |
| `dbkey` | `sslkey` (client private key) |
| `dbca` | `sslrootcert` (CA used to verify the server) |
| `dbcertpass` | `sslpassword` — **libpq 14+ only** |
| `dbsslmode` | `sslmode`. Auto: `verify-full` with a `dbca`, `require` with only cert/key, otherwise `disable` |

## Calling it
```php
$recs = dbQueryResults('postgres_ods', "SELECT … LIMIT 50");
$rec  = dbGetRecord('postgres_ods', "SELECT pg_postmaster_start_time() AS started");
$n    = dbGetCount('postgres_ods', ['-table'=>'foo','status'=>1]);
$grid = dbListRecords('postgres_ods', ['-query'=>"SELECT …", '-tableclass'=>'wacss_table is-striped']);
$ok   = dbExecuteSQL('postgres_ods', $sql);
```

## Function reference

**Query / execute**
| function | notes |
|---|---|
| `postgresqlQueryResults($query,$params)` | main entry |
| `postgresqlEnumQueryResults()` | row-at-a-time |
| `postgresqlExecuteSQL($query,$params)` | non-SELECT |
| `postgresqlQueryExplainResults()` | runs `EXPLAIN` and returns the plan |
| `postgresqlIsEfficientQueryPlan()`, `postgresqlAnalyzeExplainNode()`, `postgresqlExtractColumnsFromFilter()` | plan analysis — flags seq scans / missing indexes |
| `postgresqlCancelQuery()` | cancels a running query |
| `postgresqlNamedQueryList()` / `postgresqlNamedQuery($name)` | canned queries for the admin console |
| `postgresqlOptimizations()` | tuning observations |

**Records**
| function |
|---|
| `postgresqlGetDBRecord($params)`, `postgresqlGetDBRecords($params)`, `postgresqlGetDBRecordById()` |
| `postgresqlGetDBCount($params)` |
| `postgresqlAddDBRecord()`, `postgresqlAddDBRecords()` (bulk), `postgresqlAddDBRecordsProcess()` |
| `postgresqlEditDBRecord()`, `postgresqlEditDBRecordById()` |
| `postgresqlDelDBRecord()`, `postgresqlDelDBRecordById()` |
| `postgresqlListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata / DDL**
| function |
|---|
| `postgresqlGetDBTables()`, `postgresqlGetDBViews()`, `postgresqlIsDBTable()`, `postgresqlGrepDBTables()`, `postgresqlGetDBDatabases()`, `postgresqlGetDBVersion()` |
| `postgresqlGetDBSchema()`, `postgresqlGetDBFields()`, `postgresqlGetDBFieldInfo()`, `postgresqlGetAllTableFields()` |
| `postgresqlGetDBIndexes()`, `postgresqlGetDBTableIndexes()`, `postgresqlGetAllTableIndexes()`, `postgresqlGetAllTableConstraints()`, `postgresqlGetDBTablePrimaryKeys()` |
| `postgresqlGetTableDDL()`, `postgresqlGetAllProcedures()`, `postgresqlGetProcedureText()` |
| `postgresqlCreateDBTable()`, `postgresqlDropDBTable()`, `postgresqlAlterDBTable()`, `postgresqlAddDBFields()`, `postgresqlDropDBFields()`, `postgresqlAddDBIndex()`, `postgresqlDropDBIndex()` |
| `postgresqlListDBDatatypes()`, `postgresqlTranslateDataType()` | MySQL→Postgres type mapping |
| `postgresqlGetConfigValue($name)` | reads a server GUC |

**Connection / internal**
| function | notes |
|---|---|
| `postgresqlParseConnectParams($params)` | builds the conninfo string, applies per-user overrides and `CONFIG` fallbacks |
| `postgresqlAddConnectSSL($params)` | adds TLS keys to the conninfo string. **Additive only** |
| `postgresqlCertFileCheck($path,$label)` | returns why a cert/key/CA path is unusable, or `''` |
| `postgresqlDBConnect()` | connects with up to **3 attempts** (1s then 3s backoff), sets `log_error_verbosity = verbose` |
| `postgresExceptionErrorHandler()` | converts libpq warnings into `ErrorException` so the retries can catch them |
| `postgresqlEscapeString()`, `postgresqlValidateIdentifier()` | — |

## What the driver adds to your connect string
When it *builds* the string it appends, unless already present:
- `options='--application_name=WaSQL_on_<host>'` (override with `-application_name` or `$CONFIG['postgres_application_name']`)
- `connect_timeout=5` — also re-checked in `postgresqlDBConnect`, so it is guaranteed even for a hand-written string
- `sslmode=disable` — **skipped when any TLS attribute is set**

`postgresqlAddConnectSSL()` then runs for both built and hand-written strings, but only ever *adds* keys the string doesn't already name (case-insensitive). So a `connect="… sslmode=prefer"` attribute is never overridden.

## Notes & gotchas
- **libpq rejects a key file that is group- or world-readable** — `chmod 0600`, owned by the apache user. `postgresqlCertFileCheck` catches missing/unreadable and logs it, but the 0600 rule is enforced by libpq itself.
- Client-cert *authentication* needs `pg_hba.conf` to use `cert`, or `clientcert=verify-full` on the row.
- `sslpassword` (from `dbcertpass`) needs **libpq 14+**; older clients fail the whole connect with "invalid connection option".
- Set **`pgsql.auto_reset_persistent = On`** in `php.ini` to avoid "server closed the connection unexpectedly" (noted at the top of the driver).
- `postgresqlParseConnectParams` **unsets every `$CONFIG` key starting with `postgres`** when a `<database>` entry exists, so per-connection attributes win over leftover globals.
- Pre-existing quirk: an "Undefined array key `-dbschema`" warning can fire from the parse function when a connection has no `dbschema` attribute. Harmless, but it will show up in a strict error log.
- `postgresqlAddDBRecordsProcessOLD()` is a superseded copy kept for reference.

## See also
`wasql_reference.md` → *Named / secondary DB connections* and *TLS / certificate authentication on a connection*. `postgresql-tuner.md` / `.py` and `postgresqltuner.pl` in this directory are separate server-tuning tools.
