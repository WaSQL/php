# ccv2.php — SAP Commerce Cloud (CCv2) driver

Queries an SAP Commerce Cloud environment by driving the **Backoffice HAC Flexible Search console** over HTTP — it logs into `/hac`, then POSTs raw SQL to the flexsearch execute endpoint. That makes a Commerce environment's database readable from WaSQL without direct DB access.

## At a glance
| | |
|---|---|
| `dbtype` | `ccv2` |
| Connects via | HTTP: `GET {dbhost}/hac/login` for a session, then `POST {dbhost}/hac/console/flexsearch/execute` |
| Auth | HAC username + password (`dbuser` / `dbpass`), session headers cached per request |
| Query language | SQL, executed as HAC's `sqlQuery` (raw SQL, not Flexible Search FQL) |
| File | `php/extras/databases/ccv2.php` (~564 lines, 12 functions) |

## config.xml
```xml
<database
    name="ccv2"
    dbhost="{backoffice URL}"
    dbtype="ccv2"
    dbuser="{username}"
    dbpass="{password}" />
```
e.g. `dbhost="https://backoffice.c1kdh3pw2n-doterrain1-d1-public.model-t.cc.commerce.ondemand.com"`

| attribute | used for |
|---|---|
| `dbhost` | the Backoffice/HAC base URL — **required**, no trailing slash |
| `dbuser` / `dbpass` | HAC login. `ccv2GetAuthHeaders()` returns an error string listing whichever of `dbuser`/`dbpass`/`dbhost` is missing |

## Calling it
```php
$recs = dbQueryResults('ccv2', "SELECT TOP 50 p.code, p.name FROM products p");
$n    = dbGetCount('ccv2', ['-query'=>"SELECT count(*) AS cnt FROM orders"]);
```

## Function reference
| function | notes |
|---|---|
| `ccv2QueryResults($query,$params)` | main entry. POSTs `sqlQuery` with `commit=False`, `maxCount=5000`, `locale=en`, a 5s timeout. Supports `-filename` to stream results to a file |
| `ccv2GetDBRecords($params)`, `ccv2GetDBRecordsCount($params)` | record/count wrappers over `-query` |
| `ccv2GetDBTables()` | the queryable tables |
| `ccv2IsDBTable($table)` | — |
| `ccv2GetDBFieldInfo($table)` | name/type/scale/precision/length per column |
| `ccv2GetTableDDL($table,$schema)` | best-effort create script |
| `ccv2GetDBIndexes()`, `ccv2GetDBTableIndexes()` | present for API parity |
| `ccv2NamedQueryList()` / `ccv2NamedQuery($name)` | canned queries for the admin console |
| `ccv2GetAuthHeaders()` | logs into HAC and returns the session headers. Cached in `$ccv2GetAuthHeadersCache` for the request |

## Notes & gotchas
- **`maxCount` is hardcoded to 5000.** Anything larger is silently truncated by the endpoint, so aggregate server-side (`count(*)`, `GROUP BY`) rather than pulling rows and counting in PHP.
- **`commit=False` is hardcoded** — the console executes the SQL without committing, so this is a **read path**. Do not assume a write took effect.
- **The 5-second timeout is aggressive** for Commerce, whose tables are large. A heavy query will fail on timeout rather than return slowly; narrow the query rather than raising the timeout globally.
- **You are querying the underlying tables, not the Commerce type system.** Table and column names are the physical ones (`products`, `orders`, with `p_`-prefixed columns and numeric `PK`s), not the item types you'd use in Flexible Search. Use `ccv2GetDBFieldInfo()` to discover real column names.
- **HAC login is screen-scraped.** `ccv2GetAuthHeaders()` fetches `/hac/login` to establish a session — an SSO change, a redirect, or a Commerce upgrade that alters that page will break this driver even though credentials are still valid. Errors surface as `ERROR:` / `ERRORS:` strings, so test `is_array()` on results.
- **Errors come back as strings, and SQL errors are nested** in the response as `exception.sqlserverError` — the driver surfaces that as `'ERROR: …'`.
- HAC access is usually a privileged admin credential. Scope it as tightly as the environment allows and keep it in `config.xml`.

## See also
`elastic.md`, `splunk.md`, `gigya.md` — the other HTTP-API-as-a-database drivers.
