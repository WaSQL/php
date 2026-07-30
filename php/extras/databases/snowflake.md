# snowflake.php — Snowflake driver

Queries Snowflake through the **Snowflake ODBC driver** (`odbc_pconnect`), with an optional fallback path that shells out to the **`snowsql` CLI** for connections that set `snowsql="1"`. Everything is driven by a `<database>` tag in `config.xml`; there is no per-site code.

## At a glance
| | |
|---|---|
| `dbtype` | `snowflake` |
| Connects via | `odbc_pconnect()` (persistent) / `odbc_connect()` (with `-single`), or the `snowsql` CLI |
| Requires | PHP `odbc` extension + the Snowflake ODBC driver, a DSN in `odbc.ini` (or a DSN-less connect string). `snowsql` on PATH only for `snowsql="1"` connections |
| Handle global | `$dbh_snowflake` |
| Auth | user/password **or** key-pair (JWT) via `dbcert` |
| File | `php/extras/databases/snowflake.php` (~1796 lines, 30 functions) |

## config.xml

Password auth:
```xml
<database name="snowflake_prod" dbtype="snowflake"
    dbname="SNOW_DSN" dbuser="SVC_WASQL" dbpass="…"
    dbaccount="ab12345" dbwarehouse="WH" dbschema="PUBLIC" dbrole="RPT" />
```

Key-pair (JWT) auth — `dbpass` is not required:
```xml
<database name="snowflake_prod" dbtype="snowflake"
    dbname="SNOW_DSN" dbuser="SVC_WASQL"
    dbcert="/etc/wasql/certs/snowflake_prod.p8" dbcertpass="…"
    dbaccount="ab12345" dbwarehouse="WH" dbschema="PUBLIC" dbrole="RPT" />
```

| attribute | used for |
|---|---|
| `dbname` | the **ODBC DSN name** (not a Snowflake database) unless `connect` is given |
| `connect` | a full ODBC connect string, used instead of `dbname` — see below |
| `dbuser` / `dbpass` | credentials. `dbpass` is optional when `dbcert` is set |
| `dbcert` | path to the PKCS#8 private key (`rsa_key.p8`) for key-pair auth |
| `dbcertpass` | passphrase protecting that key. Omit for an unencrypted key |
| `dbauth` | authenticator. Defaults to `SNOWFLAKE_JWT` whenever `dbcert` is set |
| `cursor` | `SQL_CUR_USE_ODBC` (default) / `SQL_CUR_USE_IF_NEEDED` / `SQL_CUR_USE_DRIVER` |
| `dbaccount` | Snowflake account name. Falls back to `dbhost` with `.snowflakecomputing.com` stripped |
| `dbschema` | default schema. Also used by `snowflakeGetDBFieldInfo`/table lookups to filter `table_schema` |
| `dbwarehouse`, `dbrole` | used by the `snowsql` path and useful documentation for the ODBC DSN |
| `snowsql` | `1` = run queries through the `snowsql` CLI instead of ODBC |

Any attribute can also be **per-user** by suffixing the username (`dbuser_slloyd="…"`) — `snowflakeParseConnectParams` strips the suffix for the logged-in user. `CONFIG` fallbacks exist for every key in both spellings (`dbuser_snowflake` and `snowflake_dbuser`).

## Calling it
Use the `db*` family with the connection name — dispatch is by `dbtype`, so you rarely call `snowflake*` directly:
```php
$recs = dbQueryResults('snowflake_prod', "SELECT * FROM sales LIMIT 50");
$rec  = dbGetRecord('snowflake_prod', "SELECT CURRENT_VERSION() AS v");
$n    = dbGetCount('snowflake_prod', ['-table'=>'sales']);
$grid = dbListRecords('snowflake_prod', ['-query'=>"SELECT …"]);
```

## Function reference

**Query / execute**
| function | notes |
|---|---|
| `snowflakeQueryResults($query,$params)` | main entry. `-filename` streams results to CSV instead of returning them, `-count` returns just the row count, `-forceheader` returns one empty row so column names survive a 0-row result |
| `snowflakeExecuteSQL($query,$params)` | non-SELECT |
| `snowflakeQueryHeader($query,$params)` | column names only |
| `snowflakeEnumQueryResults` *(via `odbc`-style helpers)* | — |
| `snowflakeNamedQueryList()` / `snowflakeNamedQuery($name)` | the canned queries offered in the admin DB console |

**Records**
| function |
|---|
| `snowflakeGetDBRecords($params)`, `snowflakeGetDBRecordById($table,$id)` |
| `snowflakeGetDBCount($params)` |
| `snowflakeAddDBRecord($params)`, `snowflakeAddDBRecords($params)` (bulk), `snowflakeAddDBRecordsProcess()` |
| `snowflakeEditDBRecord($params)`, `snowflakeEditDBRecordById()`, `snowflakeReplaceDBRecord()` |
| `snowflakeDelDBRecordById()` |
| `snowflakeListRecords($params)` — hands off to `databaseListRecords` for the HTML grid |

**Schema / metadata**
| function |
|---|
| `snowflakeGetDBTables()`, `snowflakeGetDBSchemas()`, `snowflakeIsDBTable($table)` |
| `snowflakeGetDBFieldInfo($table)` — name/type/scale/precision/length per column |
| `snowflakeAddDBFields()`, `snowflakeDropDBFields()` |

**Connection / internal**
| function | notes |
|---|---|
| `snowflakeParseConnectParams($params)` | merges `<database>` attributes → `-key` params, applies per-user overrides and `CONFIG` fallbacks. Returns an **error string** if neither `dbpass` nor `dbcert` is set |
| `snowflakeDBConnect($params)` | returns the ODBC resource, or a JSON error string. `-single` uses a non-persistent connection. Retries once after a 5s sleep |
| `snowflakeClearConnection()` | drops the cached handle |
| `snowflakeCertConnectString($params)` | builds the key-pair ODBC connect string |
| `snowflakeCertFileCheck($path)` | returns why a key file is unusable (missing vs. unreadable), or `''` |
| `snowflakeMaskConnectParams($params)` | masks `-dbpass`, `-dbcertpass` and `PRIV_KEY_FILE_PWD` before logging |
| `snowflakeEscapeString()`, `snowflakeConvert2UTF8()` | — |

## Key-pair (JWT) auth — how it works
The private key cannot be passed as a password, so `snowflakeDBConnect` builds an ODBC connect string instead:

```
DSN=SNOW_DSN;UID=SVC_WASQL;AUTHENTICATOR=SNOWFLAKE_JWT;PRIV_KEY_FILE=/etc/wasql/certs/x.p8;PRIV_KEY_FILE_PWD=…
```

and passes **empty** user/password to `odbc_pconnect`. That last detail matters: PHP wraps the string as `DSN=<string>;UID=…;PWD=…` when a user *and* password are supplied, which the driver manager then misreads as a DSN name.

Setup on the Snowflake side:
```sql
ALTER USER SVC_WASQL SET RSA_PUBLIC_KEY='MIIBIjANBg…';
```

## Notes & gotchas
- **The key file must be readable by the apache user.** `0400 root:root` fails as a generic auth error. `snowflakeCertFileCheck` pre-checks and logs the real reason via `debugValue`.
- **A hand-written `connect` attribute always wins.** If it is a full connect string it gets *extended* with only the keys it doesn't already name (case-insensitive); if it's a bare DSN name it becomes `DSN=name;…`. Existing `connect="…"` connections are unaffected.
- **`snowsql="1"` connections ignore `connect` entirely** — that path writes its own config file (`snowsql_<hash>.conf`, `chmod 0600`) with `private_key_path=` + `authenticator=SNOWFLAKE_JWT`, and passes the passphrase through the `SNOWSQL_PRIVATE_KEY_PASSPHRASE` env var because snowsql refuses to read it from the file.
- The snowsql path takes an **exclusive flock per query hash** (default 120s wait, `-lockwait` to change) and writes results to CSV, then parses them — so two identical concurrent queries serialize instead of duplicating work. `PUT file://…json.gz` statements lock by target filename.
- `snowflakeQueryResults` recovers from `session not connected` / `Receive Error` by calling `odbc_close_all()` and reconnecting once.
- `snowflakeParseConnectParams` **unsets every `$CONFIG` key starting with `snowflake`** when a `<database>` entry exists, so per-connection attributes always beat leftover globals.

## See also
`wasql_reference.md` → *Named / secondary DB connections* and *TLS / certificate authentication on a connection* for the framework-wide attribute vocabulary. `odbc.md` documents the generic ODBC driver this one is modelled on.
