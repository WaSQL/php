# hana.php — SAP HANA driver

Queries SAP HANA over ODBC. One of the heavier drivers: alongside the usual CRUD it carries prepared-statement builders for bulk insert/replace/update/delete, CSV bulk loading, and session management.

## At a glance
| | |
|---|---|
| `dbtype` | `hana` |
| Connects via | `odbc_pconnect()` (persistent) / `odbc_connect()` (with `-single`) |
| Requires | PHP `odbc` extension + the SAP HANA ODBC driver (`libodbcHDB`) and a DSN |
| Handle global | `$dbh_hana` |
| File | `php/extras/databases/hana.php` (~3125 lines, 44 functions) |

## config.xml
```xml
<database name="hana_t1" dbtype="hana"
    dbname="HANA_T1_DSN" dbuser="SVC" dbpass="…" cursor="SQL_CUR_USE_ODBC" />
```

| attribute | used for |
|---|---|
| `dbname` | the **ODBC DSN name** |
| `connect` | a full ODBC connect string, used instead of `dbname` |
| `dbuser` / `dbpass` | credentials |
| `cursor` | `SQL_CUR_USE_ODBC` / `SQL_CUR_USE_IF_NEEDED` / `SQL_CUR_USE_DRIVER` |

`CONFIG` fallbacks exist in both spellings (`dbuser_hana` / `hana_dbuser`), and per-user attribute overrides work.

## Calling it
```php
$recs = dbQueryResults('hana_t1', "SELECT TOP 50 * FROM SCHEMA.TABLE");
$rec  = dbGetRecord('hana_t1', "SELECT * FROM M_DATABASE");
$n    = dbGetCount('hana_t1', ['-table'=>'SCHEMA.TABLE']);
```

## Function reference

**Query / execute**
| function | notes |
|---|---|
| `hanaQueryResults($query,$params)` | main entry. `-filename` writes CSV instead of returning rows |
| `hanaQueryHeader($query,$params)` | column names only |
| `hanaExecuteSQL($query,$params)` | non-SELECT |
| `hanaManageDBSessions($params)` | lists / cancels HANA sessions |
| `hanaNamedQueryList()` / `hanaNamedQuery($name)` | canned queries for the admin console |

**Records**
| function |
|---|
| `hanaGetDBRecords($params)`, `hanaGetDBRecordById()` |
| `hanaGetDBCount($params)` |
| `hanaAddDBRecord()`, `hanaAddDBRecords()` (bulk), `hanaAddDBRecordsProcess()`, `hanaAddDBRecordsFromCSV()` |
| `hanaEditDBRecord()`, `hanaEditDBRecordById()`, `hanaReplaceDBRecord()` |
| `hanaDelDBRecord()`, `hanaDelDBRecordById()` |
| `hanaListRecords($params)` → `databaseListRecords` HTML grid |
| `hanaBuildPreparedInsertStatement()`, `…ReplaceStatement()`, `…UpdateStatement()`, `…DeleteStatement()` | prepared-statement builders used by the bulk paths |

**Schema / metadata / DDL**
| function |
|---|
| `hanaGetDBTables()`, `hanaGetDBSystemTables()`, `hanaGetDBSchemas()`, `hanaIsDBTable()` |
| `hanaGetDBSchema()`, `hanaGetDBFieldInfo()`, `hanaGetAllTableFields()` |
| `hanaGetDBIndexes()`, `hanaGetDBTableIndexes()`, `hanaGetAllTableIndexes()`, `hanaGetDBTablePrimaryKeys()` |
| `hanaGetTableDDL()`, `hanaCreateDBTable()`, `hanaAlterDBTable()`, `hanaAddDBFields()`, `hanaDropDBFields()` |

**Connection / internal**
| function |
|---|
| `hanaParseConnectParams($params)`, `hanaDBConnect($params)` (`-single` supported, retries after a 5s sleep), `hanaClearConnection()` |
| `hanaEscapeString()`, `hanaConvert2UTF8()` |

## Notes & gotchas
- **Identifiers are effectively upper-case.** HANA folds unquoted identifiers to upper case, so the metadata functions compare against upper-cased schema/table names. Pass table names as `SCHEMA.TABLE` in upper case unless you know the object was created quoted.
- **No TLS/cert attributes are implemented.** HANA's ODBC driver supports `encrypt`, `sslValidateCertificate`, `sslKeyStore` and `sslTrustStore` — put them in the DSN (`odbc.ini`) or in a full `connect` attribute, which is passed through unchanged.
- `hanaConvert2UTF8()` exists because the driver can return non-UTF8 payloads; use it when text arrives mangled.
- `M_*` monitoring views (e.g. `M_DATABASE`, `M_SERVICE_MEMORY`) are the usual source for health dashboards — see the reference links at the top of the driver.

## See also
`odbc.md` for the generic ODBC behaviour this driver specialises. `hana-tuner.md` / `hana-tuner.py` in this directory are a separate server-tuning tool.
