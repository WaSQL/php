# sqlite.php — SQLite driver

Queries a SQLite 3 database **file** through PHP's bundled `SQLite3` class. There is no server, no host and no credentials — the whole connection is a file path.

## At a glance
| | |
|---|---|
| `dbtype` | `sqlite` |
| Connects via | `new SQLite3($path, …)` |
| Requires | PHP `sqlite3` extension (bundled by default) |
| Handle global | `$dbh_sqlite` |
| File | `php/extras/databases/sqlite.php` (~1877 lines, 39 functions) |

## config.xml
```xml
<database group="SQLite" name="local_cache" dbtype="sqlite"
    dbname="d:/data/cache.sqlite" />
```

| attribute | used for |
|---|---|
| `dbname` | **path to the database file** — the only required attribute |
| `dbmode` | open mode. Read-only opens with `SQLITE3_OPEN_READONLY`; otherwise `SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE` |

A **relative `dbname` is resolved against the docroot**: `sqliteDBConnect` tries `$_SERVER['DOCUMENT_ROOT']/<dbname>`, then one and two directories above it, before giving up with `$CONFIG['sqlite_error'] = "dbname does not exist"`.

## Calling it
```php
$recs = dbQueryResults('local_cache', "SELECT * FROM items LIMIT 50");
$n    = dbGetCount('local_cache', ['-table'=>'items']);
$ok   = dbExecuteSQL('local_cache', "UPDATE items SET status=1 WHERE _id=5");
```

## Function reference

**Query / execute**
| function |
|---|
| `sqliteQueryResults($query,$params)`, `sqliteEnumQueryResults()`, `sqliteExecuteSQL($query,$params)` |
| `sqliteNamedQueryList()` / `sqliteNamedQuery($name)` |

**Records**
| function |
|---|
| `sqliteGetDBRecord()`, `sqliteGetDBRecords()`, `sqliteGetDBRecordById()`, `sqliteGetDBRecordsCount()`, `sqliteGetDBCount()` |
| `sqliteAddDBRecord()`, `sqliteAddDBRecords()` (bulk), `sqliteAddDBRecordsProcess()` |
| `sqliteEditDBRecord()`, `sqliteEditDBRecordById()` |
| `sqliteDelDBRecord()`, `sqliteDelDBRecordById()`, `sqliteTruncateDBTable()` |
| `sqliteListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata / DDL**
| function |
|---|
| `sqliteGetDBTables()`, `sqliteIsDBTable()`, `sqliteGetDBSchema()`, `sqliteGetDBFieldInfo()`, `sqliteGetAllTableFields()` |
| `sqliteGetDBIndexes()`, `sqliteGetDBTableIndexes()`, `sqliteGetAllTableIndexes()`, `sqliteAddDBIndex()` |
| `sqliteGetTableDDL()`, `sqliteCreateDBTable()`, `sqliteAlterDBTable()`, `sqliteAddDBFields()`, `sqliteDropDBFields()`, `sqliteListDBDatatypes()` |

**Connection / internal**
| function |
|---|
| `sqliteParseConnectParams($params)`, `sqliteDBConnect($params)`, `sqliteClearConnection()` |
| `sqliteEscapeString()`, `sqliteQuoteIdentifier()` |

## Notes & gotchas
- **The apache user needs write access to the *directory*, not just the file** — SQLite creates `-wal` / `-journal` siblings next to the database. A writable file in a read-only directory still fails.
- **Never put the database file under the docroot** unless you block it in the web server; `cache.sqlite` served over HTTP is your whole dataset.
- **Writers serialize.** SQLite takes a database-level write lock, so a slow write blocks every other request touching that file. It suits caches, lookups and single-user tools — not a busy multi-user site.
- **Types are advisory.** SQLite uses dynamic typing, so `sqliteGetDBFieldInfo` reports the declared type while the stored value may be anything. Don't rely on it for validation.
- **No cert/TLS attributes** — meaningless for a local file.
- `ALTER TABLE` support in SQLite is limited (no drop-column on older versions), so `sqliteDropDBFields()` may need a table rebuild depending on the SQLite version.

## See also
`duckdb.md` — the other file-based engine here, better suited to analytical queries over CSV/Parquet. `sqlite-tuner.md` / `sqlite-tuner.py` are a separate tuning tool.
