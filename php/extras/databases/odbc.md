# odbc.php — generic ODBC driver

The catch-all ODBC engine: anything with an ODBC driver and a DSN can be reached through it without a dedicated WaSQL driver file. `snowflake.php`, `hana.php` and `ctree.php` are all specialisations of this same shape.

## At a glance
| | |
|---|---|
| `dbtype` | `odbc` |
| Connects via | `odbc_pconnect()` (persistent) / `odbc_connect()` (with `-single`) |
| Requires | PHP `odbc` extension + a working DSN (unixODBC `odbc.ini` on Linux, ODBC Data Sources on Windows) |
| Handle global | `$dbh_odbc` |
| File | `php/extras/databases/odbc.php` (~1330 lines, 24 functions) |

## config.xml
```xml
<database name="legacy_erp" dbtype="odbc"
    dbname="ERP_DSN" dbuser="svc" dbpass="…" cursor="SQL_CUR_USE_ODBC" />
```

| attribute | used for |
|---|---|
| `dbname` | the **ODBC DSN name** |
| `connect` | a full ODBC connect string, used instead of `dbname` |
| `dbuser` / `dbpass` | credentials passed to `odbc_pconnect` |
| `cursor` | `SQL_CUR_USE_ODBC` (default) / `SQL_CUR_USE_IF_NEEDED` / `SQL_CUR_USE_DRIVER` |

`CONFIG` fallbacks: `odbc_connect`, `dbname_odbc` / `odbc_dbname`, `dbuser_odbc` / `odbc_dbuser`, `dbpass_odbc` / `odbc_dbpass`, `odbc_cursor`. Per-user attribute overrides (`dbuser_slloyd="…"`) work here too.

## Calling it
```php
$recs = dbQueryResults('legacy_erp', "SELECT * FROM orders");
$n    = dbGetCount('legacy_erp', ['-table'=>'orders']);
```

## Function reference

**Query / execute**
| function | notes |
|---|---|
| `odbcQueryResults($query,$params)` | main entry. `-filename` writes CSV instead of returning rows |
| `odbcQueryHeader($query,$params)` | column names only |
| `odbcExecuteSQL($query,$params)` | non-SELECT |
| `odbcNamedQuery($name)` | canned queries for the admin console |

**Records**
| function |
|---|
| `odbcGetDBRecords($params)`, `odbcGetDBRecordById()` |
| `odbcGetDBCount($params)` |
| `odbcAddDBRecord()`, `odbcAddDBRecords()` (bulk), `odbcAddDBRecordsProcess()` |
| `odbcEditDBRecord()`, `odbcEditDBRecordById()`, `odbcReplaceDBRecord()` |
| `odbcDelDBRecordById()` |
| `odbcListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata**
| function |
|---|
| `odbcGetDBTables()`, `odbcGetDBSchemas()`, `odbcIsDBTable($table)`, `odbcGetDBFieldInfo($table)` |

**Connection / internal**
| function |
|---|
| `odbcParseConnectParams($params)`, `odbcDBConnect($params)` (`-single` supported, one retry after a 5s sleep), `odbcClearConnection()` |
| `odbcEscapeString()`, `odbcConvert2UTF8()` |

## Notes & gotchas
- **A DSN name and a connect string are not interchangeable at the PHP level.** PHP switches from `SQLConnect` to `SQLDriverConnect` only when the string contains `;`, and it wraps the string as `DSN=<string>;UID=…;PWD=…` when a user *and* password are both non-empty. If you need a DSN-less connect string, put the credentials **inside** the string and leave `dbuser`/`dbpass` empty — this is exactly what `snowflake.php` does for key-pair auth.
- No TLS/cert attributes are implemented here. Because the whole connect string is passed through, driver-specific TLS keywords can simply be written into the `connect` attribute.
- Text encoding: `odbcConvert2UTF8()` exists because several ODBC drivers return Latin-1; call it if you see mangled accents.
- This driver has **no DDL support** (no create/alter table). Use the engine-specific driver if you need it.

## See also
`snowflake.md`, `hana.md`, `ctree.md`, `msaccess.md`, `mscsv.md`, `msexcel.md` — all ODBC-based, each with its own connect-string quirks.
