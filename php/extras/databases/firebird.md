# firebird.php — Firebird driver

Queries Firebird through PHP's **interbase/ibase** extension.

## At a glance
| | |
|---|---|
| `dbtype` | `firebird` |
| Connects via | `ibase_connect($connect, $dbuser, $dbpass)` |
| Requires | PHP `interbase` (or `pdo_firebird` companion) extension + Firebird client library |
| Handle global | `$dbh_firebird` |
| File | `php/extras/databases/firebird.php` (~1705 lines, 38 functions) |

## config.xml
**Set `connect` explicitly** (see gotchas — the auto-built string does not work):
```xml
<database name="legacy_fb" dbtype="firebird"
    connect="localhost:d:/data/examples.fdb"
    dbuser="SYSDBA" dbpass="…" />
```

| attribute | used for |
|---|---|
| `connect` | the Firebird database path in `host[/port]:/path/to/db.fdb` form — passed to `ibase_connect` verbatim |
| `dbuser` / `dbpass` | credentials |
| `dbhost`, `dbport`, `dbname`, `dbschema` | read by the parser and used for the (broken) auto-built string; harmless when `connect` is set |

## Calling it
```php
$recs = dbQueryResults('legacy_fb', "SELECT FIRST 50 * FROM CUSTOMER");
$n    = dbGetCount('legacy_fb', ['-table'=>'CUSTOMER']);
```

## Function reference

**Query / execute**
| function |
|---|
| `firebirdQueryResults($query,$params)`, `firebirdEnumQueryResults()`, `firebirdExecuteSQL($query,$params)` |
| `firebirdNamedQueryList()` / `firebirdNamedQuery($name)`, `firebirdOptimizations()` |

**Records**
| function |
|---|
| `firebirdGetDBRecords()`, `firebirdGetDBRecordById()`, `firebirdGetDBCount()` |
| `firebirdAddDBRecords()` (bulk), `firebirdAddDBRecordsProcess()` |
| `firebirdEditDBRecordById()`, `firebirdDelDBRecordById()` |
| `firebirdListRecords($params)` → `databaseListRecords` HTML grid |
| `firebirdGetDBQuery()`, `firebirdGetDBWhere()`, `firebirdGetDBExpression()` | query-building internals |

**Schema / metadata / DDL**
| function |
|---|
| `firebirdGetDBTables()`, `firebirdGetDBViews()`, `firebirdIsDBTable()`, `firebirdGetDBName()`, `firebirdGetDBSchema()`, `firebirdGetDBFieldInfo()`, `firebirdGetAllTableFields()` |
| `firebirdGetAllTableIndexes()`, `firebirdGetDBTableIndexes()`, `firebirdGetAllProcedures()` |
| `firebirdGetDDL()`, `firebirdGetTableDDL()`, `firebirdGetFunctionDDL()`, `firebirdGetProcedureDDL()`, `firebirdGetPackageDDL()`, `firebirdGetTriggerDDL()` |
| `firebirdAddDBFields()`, `firebirdDropDBFields()` |

**Connection / internal**
| function |
|---|
| `firebirdParseConnectParams($params)`, `firebirdDBConnect($params)`, `firebirdEscapeString()` |

## Notes & gotchas
- **⚠️ The auto-built connect string is wrong for this driver.** When no `connect` attribute is supplied, `firebirdParseConnectParams` builds `host=… port=… dbname=… user=… password=…` — a **PostgreSQL** conninfo string (the code and its comments are copy-pasted from `postgresql.php`, including an `application_name` option that means nothing to Firebird). `ibase_connect` expects `host:/path/to/file.fdb`, so that string cannot connect. **Always set `connect` explicitly.**
- **Paging syntax is `FIRST n` / `SKIP n`** or `OFFSET n ROWS FETCH NEXT n ROWS ONLY` (Firebird 3+) — not `LIMIT`.
- **No client certificates.** Firebird 3+ supports wire encryption but not X.509 client-cert auth, so none of the `dbcert`/`dbkey`/`dbca` vocabulary is implemented here.
- The `@usage` block in `firebirdDBConnect`'s docblock references `firebird_autocommit`/`firebird_exec`/`firebird_commit` — those functions do **not** exist (another copy-paste artefact). The real API is `ibase_*`.
- Identifiers created unquoted are stored upper-case; quoted identifiers are case-sensitive.

## See also
`wasql_reference.md` → *Named / secondary DB connections*. `firebird-tuner.md` / `firebird-tuner.py` are a separate tuning tool.
