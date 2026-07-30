# duckdb.php — DuckDB driver

Queries DuckDB by shelling out to the **`duckdb` CLI** (`cmdResults`) rather than through a PHP extension. Its distinguishing feature is that a "database" can be a `.duckdb` file, a single data file, or a *folder* of data files that are exposed as tables.

## At a glance
| | |
|---|---|
| `dbtype` | `duckdb` |
| Connects via | the `duckdb` command-line binary, invoked through `cmdResults()` |
| Requires | `duckdb` on the web server's PATH. No PHP extension needed |
| File | `php/extras/databases/duckdb.php` (~2460 lines, 48 functions) |

## config.xml
```xml
<!-- a duckdb database file -->
<database group="DuckDB" name="analytics" dbtype="duckdb" dbname="d:/data/analytics.duckdb" />

<!-- a single data file as the database -->
<database name="sales_csv" dbtype="duckdb" dbname="d:/data/sales.csv" />

<!-- a FOLDER: every supported file inside becomes a table -->
<database name="datalake" dbtype="duckdb" dbname="d:/data/lake" />
```

| attribute | used for |
|---|---|
| `dbname` | path to a database file, a data file, or a directory |
| `dbmode` | optional mode override (`CONFIG` fallbacks `dbmode_duckdb` / `duckdb_dbmode`) |

### The three modes are derived from the path
| helper | true when |
|---|---|
| `duckdbIsFolderMode()` | `dbname` is a directory — `duckdbGetFiles()` lists it and each file is a table |
| `duckdbIsFileMode()` / `duckdbIsCSVMode()` | `dbname` (or the target within a folder) has extension `csv`, `json`, `parquet`, `xlsx` or `xls` |
| neither | `dbname` is treated as a real DuckDB database file |

In file/folder mode the driver wraps the path in the right reader (`read_csv_auto`, `read_parquet`, …) via `duckdbGetReadFunction()` and `duckdbBuildReadQuery()`.

## Calling it
```php
$recs = dbQueryResults('datalake', "SELECT * FROM 'sales.parquet' LIMIT 50");
$n    = dbGetCount('analytics', ['-table'=>'orders']);
$tbls = dbGetRecords('datalake', ['-listtables'=>1]);
```

## Function reference

**Query / execute**
| function | notes |
|---|---|
| `duckdbQueryResults($query,$params)` | writes the SQL to a temp file and runs `duckdb -csv -c ".read <file>"`, then parses the output. Retries once with an adjusted command on failure |
| `duckdbExecuteSQL($query,$params)` | non-SELECT |
| `duckdbNamedQueryList()` / `duckdbNamedQuery($name)` | canned queries for the admin console |

**Records**
| function |
|---|
| `duckdbGetDBRecord()`, `duckdbGetDBRecords()`, `duckdbGetDBRecordById()`, `duckdbGetDBRecordsCount()`, `duckdbGetDBCount()` |
| `duckdbAddDBRecord()`, `duckdbAddDBRecords()` (bulk), `duckdbAddDBRecordsProcess()` |
| `duckdbEditDBRecord()`, `duckdbEditDBRecordById()` |
| `duckdbDelDBRecord()`, `duckdbDelDBRecordById()`, `duckdbTruncateDBTable()` |
| `duckdbListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata / DDL**
| function |
|---|
| `duckdbGetDBTables()`, `duckdbIsDBTable()`, `duckdbGetDBSchema()`, `duckdbGetDBFieldInfo()`, `duckdbGetAllTableFields()` |
| `duckdbGetDBIndexes()`, `duckdbGetDBTableIndexes()`, `duckdbAddDBIndex()`, `duckdbGetAllTableIndexes()` |
| `duckdbGetTableDDL()`, `duckdbCreateDBTable()`, `duckdbAlterDBTable()`, `duckdbAddDBFields()`, `duckdbDropDBFields()`, `duckdbListDBDatatypes()` |

**Files / modes / internal**
| function |
|---|
| `duckdbIsFolderMode()`, `duckdbIsFileMode()`, `duckdbIsCSVMode()`, `duckdbGetFiles()`, `duckdbGetDataFilePath()`, `duckdbGetCSVFilePath()` |
| `duckdbGetReadFunction()`, `duckdbBuildReadQuery()`, `duckdbCheckAndRemoveBOM()`, `duckdbSetDBName()` |
| `duckdbParseConnectParams()`, `duckdbDBConnect()`, `duckdbClearConnection()`, `duckdbEscapeString()`, `duckdbQuoteIdentifier()` |

## Notes & gotchas
- **It is a subprocess, not a connection.** Every query forks `duckdb`, so per-query overhead is much higher than an in-process driver, and nothing is shared between queries (no session state, no temp tables across calls).
- **`duckdb` must be on the PATH of the *web server* user**, not your shell. A driver that works from CLI and fails under apache is almost always this.
- All paths and identifiers are passed through `escapeshellarg()` / `duckdbQuoteIdentifier()`; keep it that way when extending the file — this is a shell boundary.
- **BOM handling is explicit** (`duckdbCheckAndRemoveBOM`) because a UTF-8 BOM makes the first CSV column name unmatchable.
- In folder mode the *table name is the filename*. Files with spaces or unusual characters need quoting in your SQL.
- The header comment references the [odbc-scanner-duckdb-extension](https://github.com/rupurt/odbc-scanner-duckdb-extension), which lets DuckDB read *other* databases via ODBC — useful for cross-engine joins, unrelated to how WaSQL connects here.
- **No cert/TLS attributes** — meaningless for a local binary.

## See also
`sqlite.md` — the other file-based engine. `duckdb-tuner.md` / `duckdb-tuner.py` are a separate tuning tool.
