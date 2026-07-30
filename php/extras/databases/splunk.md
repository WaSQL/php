# splunk.php — Splunk driver

Runs **SPL** (Splunk's search language) through the Splunk REST search-jobs API and returns the results as records. The smallest driver in this directory — read-only, six functions, no connection object.

## At a glance
| | |
|---|---|
| `dbtype` | `splunk` |
| Connects via | HTTP `POST` to `https://{dbhost}/services/search/v2/jobs` (WaSQL's `postURL`) |
| Auth | `Authorization: Bearer {dbkey}` |
| Query language | **SPL**, not SQL |
| File | `php/extras/databases/splunk.php` (~238 lines, 6 functions) |

## config.xml
```xml
<database
    name="my_splunk"
    dbhost="{yoursplunkID}.splunkcloud.com:8089"
    dbtype="splunk"
    dbkey="{apiKey or token}" />
```

| attribute | used for |
|---|---|
| `dbhost` | Splunk management host **including the port** (8089 is the management port, not the web UI's 8000) |
| `dbkey` | API token, sent as a Bearer token. No `dbuser`/`dbpass` path |

## Calling it
```php
$recs = dbQueryResults('my_splunk', 'search index=web status=500 | head 50');
$tbls = dbGetRecords('my_splunk', ['-listtables'=>1]);   // sourcetypes
```

## Function reference
| function | notes |
|---|---|
| `splunkQueryResults($query,$params)` | creates a search job, polls it, then pages the results |
| `splunkGetDBRecords($params)` | wrapper that runs `$params['-query']` |
| `splunkGetDBTables()` | `\| metadata type=sourcetypes index=* \| table sourcetype` — the **sourcetypes** presented as tables |
| `splunkGetDBFieldInfo($table)` | field discovery for a sourcetype |
| `splunkNamedQueryList()` / `splunkNamedQuery($name)` | canned queries for the admin console |

## Notes & gotchas
- **SPL, not SQL.** Queries are pipelines (`search … | stats … | table …`). None of WaSQL's SQL-building helpers (`-where`, `-order`, DDL functions) apply — pass `-query` explicitly.
- **Port 8089, not 8000.** `dbhost` must point at the *management* API. Pointing it at the web UI port produces HTML instead of JSON and fails opaquely.
- **A search is a job, not a request.** The driver creates the job, waits for it, then pages results — so a broad search over a long window can take a long time and there is no partial return. Constrain with `earliest`/`latest` in the SPL.
- **Results paging is built in** and the driver walks pages until exhausted; a huge search can therefore pull a lot into memory.
- **Read-only.** No add/edit/delete, no DDL.
- `dbkey` is a bearer token — treat it like a password, keep it in `config.xml` (never in a page), and prefer a token with a least-privilege role.
- `dbkey` is also the attribute name `gigya.php` uses for its API key and `mysql.php`/`postgresql.php` use (as `dbkey`) for a TLS private key. Dispatch is by `dbtype`, so they don't collide.

## See also
`elastic.md`, `gigya.md`, `ccv2.md` — the other HTTP-API-as-a-database drivers.
