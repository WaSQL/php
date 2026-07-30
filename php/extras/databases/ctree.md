# ctree.php — FairCom c-tree driver

Queries FairCom c-tree (c-treeACE / c-treeRTG) over ODBC, **plus** a second, independent path that talks to FairCom's JSON/REST DB API over HTTP.

## At a glance
| | |
|---|---|
| `dbtype` | `ctree` |
| Connects via | `odbc_pconnect()` / `odbc_connect()` (with `-single`); separately, HTTP for the `ctreeJsonDB*` functions |
| Requires | PHP `odbc` extension + the FairCom ODBC driver and a DSN |
| Handle global | `$dbh_ctree` |
| File | `php/extras/databases/ctree.php` (~1624 lines, 32 functions) |

The driver raises its own execution limits on load — c-tree scans can be slow:
```php
ini_set('max_execution_time', 1800);
set_time_limit(1800);
```

## config.xml
```xml
<database name="rtg" dbtype="ctree"
    dbname="CTREE_DSN" dbuser="admin" dbpass="…"
    cursor="SQL_CUR_USE_ODBC" dbpool="1" />
```

| attribute | used for |
|---|---|
| `dbname` | the **ODBC DSN name** |
| `connect` | a full ODBC connect string, used instead of `dbname` |
| `dbuser` / `dbpass` | credentials |
| `cursor` / `dbcursor` | `SQL_CUR_USE_ODBC` (the default this driver forces) / `SQL_CUR_USE_IF_NEEDED` / `SQL_CUR_USE_DRIVER` |
| `dbpool` | connection-pool flag honoured by the parser |
| `dbhost`, `dbport`, `dbschema` | read into params for use by the metadata/JSON paths |
| `dbfetch` | rows per fetch batch in the enumeration paths (defaults **1000**, or **5000** in the bulk path) |

## Calling it
```php
$recs = dbQueryResults('rtg', "SELECT * FROM customer");
$n    = dbGetCount('rtg', ['-table'=>'customer']);
```

## Function reference

**Query / execute**
| function |
|---|
| `ctreeQueryResults($query,$params)`, `ctreeEnumQueryResults()`, `ctreeExecuteSQL($query,$params)` |
| `ctreeNamedQueryList()` / `ctreeNamedQuery($name)` |

**Records**
| function |
|---|
| `ctreeGetDBRecord()`, `ctreeGetDBRecords()`, `ctreeGetDBCount()`, `ctreeGetDBFields()` |
| `ctreeListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata**
| function |
|---|
| `ctreeGetDBTables()`, `ctreeIsDBTable()`, `ctreeGetDBSchema()`, `ctreeGetDBFieldInfo()`, `ctreeGetAllTableFields()` |
| `ctreeGetDBIndexes()`, `ctreeGetDBTableIndexes()`, `ctreeGetAllTableIndexes()`, `ctreeGetDBTablePrimaryKeys()` |
| `ctreeGetTableDDL()`, `ctreeGetConfigValue($name)` |

**JSON / REST DB API** (separate transport, not ODBC)
| function | notes |
|---|---|
| `ctreeJsonDBGetAuthToken()` | authenticates and caches the token |
| `ctreeJsonDBBaseURL()`, `ctreeJsonDBRequestId()` | endpoint / request-id helpers |
| `ctreeJsonDBCallAPI()` | generic API call |
| `ctreeJsonDBQueryResults()` | run a query through the REST API |
| `ctreeJsonDBDumpTable()` | bulk table export |
| `ctreeJsonDBCloseCursor()` | **call this** — server-side cursors persist otherwise |

**Connection / internal**
| function | notes |
|---|---|
| `ctreeParseConnectParams($params)`, `ctreeDBConnect($params)` (`-single` supported) | — |
| `ctreeSetTimeouts($dbh)` | **currently a no-op.** Its comment records why: `SQL_ATTR_CONNECTION_TIMEOUT` (113) only covers connection establishment, so a query timeout has to be set with `QUERY_TIMEOUT=` in the connect string instead |
| `ctreeDBConnectOLD()` | superseded, kept for reference |

## Notes & gotchas
- **Query timeouts must go in the connect string** (`QUERY_TIMEOUT=…`), not through ODBC attributes — `ctreeSetTimeouts()` is intentionally empty. Without it a runaway query runs for the full 1800s limit the driver sets.
- **Always close JSON-API cursors** with `ctreeJsonDBCloseCursor()`; the REST API keeps them server-side until closed or timed out.
- **No cert/TLS attributes.** Anything the FairCom ODBC driver supports can be written into the DSN or a full `connect` attribute.
- The two transports are independent — a working ODBC DSN says nothing about the JSON API being reachable, and vice versa.
- SQL support is a subset: expect gaps around joins, subqueries and DDL compared with a general-purpose engine. See the SQL reference links at the top of the driver.

## See also
`odbc.md` for the generic ODBC behaviour this driver specialises.
