# oracle.php — Oracle driver

Queries Oracle through **OCI8**. Notable for building a full TNS descriptor from `<database>` attributes, explicit transaction control, and a large DDL/metadata surface including a reserved-word checker.

## At a glance
| | |
|---|---|
| `dbtype` | `oracle` |
| Connects via | `oci_pconnect()` (persistent) / `oci_connect()` (with `-single`) |
| Requires | PHP `oci8` extension + Oracle Instant Client |
| Handle global | `$dbh_oracle` |
| File | `php/extras/databases/oracle.php` (~3346 lines, 49 functions) |

The driver sets OCI8 ini values on load — worth knowing because they are process-wide:
```
oci8.connection_class    = WaSQL   (enables server-side connection pooling / DRCP)
oci8.events              = ON      (FAN — fast application notification)
oci8.max_persistent      = 50
oci8.persistent_timeout  = -1      (never expire)
oci8.default_prefetch    = 100     (rows per round trip)
oci8.statement_cache_size= 20
```

## config.xml
```xml
<database name="erp_ora" dbtype="oracle"
    dbhost="ora01.x.com" dbport="1521" dbservice_name="XEPDB1"
    dbuser="svc" dbpass="…" />
```

| attribute | used for |
|---|---|
| `dbhost` / `dbport` | `ADDRESS=(HOST=…)(PORT=…)`. Port defaults are applied when omitted |
| `dbservice_name` | `CONNECT_DATA=(SERVICE_NAME=…)` — the modern form |
| `dbsid` | `CONNECT_DATA=(SID=…)` — the legacy form |
| `connect` | a complete TNS descriptor, used instead of building one |
| `dbuser` / `dbpass` | credentials |
| `dbsysdba` | connect as SYSDBA (passed as OCI8's session mode) |
| `charset` | character set passed to `oci_connect` |

If no `dbhost` can be resolved from attributes or `CONFIG`, `oracleParseConnectParams` returns the params **unchanged** — the connect then fails with no descriptor, which is the usual cause of a silent Oracle failure.

The built descriptor looks like:
```
(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=ora01.x.com)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=XEPDB1)))
```

## Calling it
```php
$recs = dbQueryResults('erp_ora', "SELECT * FROM orders FETCH FIRST 50 ROWS ONLY");
$rec  = dbGetRecord('erp_ora', "SELECT * FROM v\$version");
$n    = dbGetCount('erp_ora', ['-table'=>'orders']);
```

## Function reference

**Query / execute / transactions**
| function | notes |
|---|---|
| `oracleQueryResults($query,$params)`, `oracleEnumQueryResults()`, `oracleExecuteSQL()` | — |
| `oracleAutoCommit($on)`, `oracleCommit()` | OCI8 does **not** auto-commit by default; see gotchas |
| `oracleGetActiveSessionCount()` | session count for health dashboards |
| `oracleNamedQueryList()` / `oracleNamedQuery($name)` | canned queries for the admin console |

**Records**
| function |
|---|
| `oracleGetDBRecord()`, `oracleGetDBRecords()`, `oracleGetDBRecordById()`, `oracleGetDBCount()`, `oracleGetDBFields()` |
| `oracleAddDBRecord()`, `oracleAddDBRecords()` (bulk), `oracleAddDBRecordsProcess()` |
| `oracleEditDBRecord()`, `oracleEditDBRecordById()` |
| `oracleDelDBRecord()`, `oracleDelDBRecordById()`, `oracleTruncateDBTable()` |
| `oracleListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata / DDL**
| function |
|---|
| `oracleGetDBTables()`, `oracleIsDBTable()`, `oracleGetDBTablePrimaryKeys()`, `oracleGetDBSchema()`, `oracleGetDBFieldInfo()`, `oracleGetAllTableFields()` |
| `oracleGetAllTableIndexes()`, `oracleGetDBTableIndexes()`, `oracleGetAllTableConstraints()` |
| `oracleGetDDL()`, `oracleGetTableDDL()`, `oracleGetPackageDDL()`, `oracleGetFunctionDDL()`, `oracleGetTriggerDDL()`, `oracleGetProcedureText()`, `oracleGetAllProcedures()` |
| `oracleCreateDBTable()`, `oracleAlterDBTable()`, `oracleAddDBFields()`, `oracleDropDBFields()`, `oracleAddDBIndex()`, `oracleDropDBIndex()` |
| `oracleIsReservedWord($word)`, `oracleReservedWordsList()` | guard before generating DDL with user-supplied names |

**Connection / internal**
| function |
|---|
| `oracleParseConnectParams($params)`, `oracleDBConnect($params)` (`-single` supported), `oracleEscapeString()` |

## Notes & gotchas
- **Commits are explicit.** OCI8 opens a transaction implicitly and `oracleExecuteSQL` does not commit for you in every path — use `oracleAutoCommit(1)` or call `oracleCommit()` after writes, or your changes vanish when the connection is reused.
- **Persistent connections never expire** (`oci8.persistent_timeout = -1`) and up to 50 are kept per process. Combined with `oci8.connection_class = WaSQL` this is designed for DRCP; on a non-DRCP server it means long-lived sessions holding server resources.
- **`dbservice_name` and `dbsid` are mutually exclusive** — service name wins if both are present.
- **TLS/wallet auth is not implemented as attributes.** Oracle does certificate auth via a wallet configured through `TNS_ADMIN` / `sqlnet.ora` and a `TCPS` protocol in the descriptor, which is environment-level rather than per-connection. A hand-written `connect` descriptor with `PROTOCOL=TCPS` plus a `TNS_ADMIN` env var is the route.
- Identifiers are upper-case unless created quoted; the metadata functions reflect that.
- `$` in Oracle view names (`v$version`, `v$session`) must be escaped in double-quoted PHP strings.

## See also
`wasql_reference.md` → *Named / secondary DB connections*. `oracle-tuner.md` / `oracle-tuner.py` in this directory are a separate server-tuning tool.
