# elastic.php — Elasticsearch driver

Queries Elasticsearch **with SQL** through its `_sql` REST endpoint, so an ES cluster can be browsed and reported on like any other WaSQL connection. There is no persistent connection — every call is an HTTP request via WaSQL's `postURL()`.

## At a glance
| | |
|---|---|
| `dbtype` | `elastic` |
| Connects via | HTTP `POST` to `https://{dbhost}/_sql` (WaSQL's `postURL`) |
| Requires | nothing beyond curl; Elasticsearch with the SQL API available |
| Query language | Elasticsearch SQL (a subset of SQL) |
| File | `php/extras/databases/elastic.php` (~452 lines, 13 functions) |

## config.xml
```xml
<database
    name="elasticsearch_dev"
    dbhost="https://logs.abccompany.com"
    dbport="8200"
    dbtype="elastic"
    dbuser="{username}"
    dbpass="{password}" />
```

| attribute | used for |
|---|---|
| `dbhost` | cluster base URL |
| `dbport` | port |
| `dbuser` / `dbpass` | basic auth |

## Calling it
Indices are the "tables":
```php
$recs = dbQueryResults('elasticsearch_dev', "SELECT * FROM \"logs-2026.07\" LIMIT 50");
$n    = dbGetCount('elasticsearch_dev', ['-query'=>"SELECT count(*) FROM \"logs-*\""]);
```

## Function reference
| function | notes |
|---|---|
| `elasticQueryResults($query,$params)` | posts the SQL to `_sql` and normalises the response into rows |
| `elasticGetDBRecords($params)` | thin wrapper over `elasticQueryResults` using `-query` |
| `elasticGetDBRecordsCount($params)` | count form |
| `elasticGetDBTables()` | `SHOW TABLES` — the indices |
| `elasticIsDBTable($table)` | index exists? |
| `elasticGetDBFieldInfo($tablename)` | `SHOW COLUMNS` — name/type/scale/precision/length per field |
| `elasticGetTableDDL($table,$schema)` | best-effort "create script" for an index |
| `elasticGetDBIndexes()`, `elasticGetDBTableIndexes()` | present for API parity |
| `elasticNamedQueryList()` / `elasticNamedQuery($name)` | canned queries for the admin console |
| `elasticLowerKeys()`, `elasticRandomUserAgent()` | internal helpers |

## Notes & gotchas
- **Elasticsearch SQL is a subset.** No `JOIN`s across indices, limited subquery support, and aggregations map onto ES aggregations with their own quirks. Complex reporting SQL that works on Postgres will not port unchanged.
- **Index names usually need double quotes** — `"logs-2026.07"` — because of the dots and dashes, and wildcards (`"logs-*"`) are allowed where a plain SQL engine would reject them.
- **Result-set size is bounded by the SQL API**, which pages via a cursor; large exports need paging rather than one big `LIMIT`.
- **Every call is a fresh HTTP request** — there is no connection reuse, so latency is per-query and a slow cluster shows up directly in page render time.
- **No DDL and no writes.** This driver is read/report oriented; there are no add/edit/delete record functions.
- **Credentials travel over HTTP basic auth**, so `dbhost` should always be `https://`.
- `elasticRandomUserAgent()` exists to vary the UA header on requests — relevant if something upstream rate-limits by user agent.

## See also
Reference links at the top of the driver: the [SQL search API](https://www.elastic.co/guide/en/elasticsearch/reference/current/sql-search-api.html), [SHOW TABLES](https://www.elastic.co/guide/en/elasticsearch/reference/current/sql-syntax-show-tables.html) and [SHOW COLUMNS](https://www.elastic.co/guide/en/elasticsearch/reference/current/sql-syntax-show-columns.html) docs. `splunk.md`, `gigya.md` and `ccv2.md` follow the same HTTP-as-a-database pattern.
