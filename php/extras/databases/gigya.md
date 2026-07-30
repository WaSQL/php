# gigya.php — SAP Customer Data Cloud (Gigya) driver

Queries SAP Customer Data Cloud (formerly Gigya) using its **SQL-like search API**, so accounts, the audit log, data store and index can be reported on like tables. HTTP-based, no persistent connection.

## At a glance
| | |
|---|---|
| `dbtype` | `gigya` |
| Connects via | HTTPS POST to the `*.search` endpoints of the configured data center (WaSQL's `postURL`) |
| Auth | `userKey` + `secretKey` + `apiKey`, or a Bearer `access_token` |
| Query language | Gigya's SQL-like search syntax |
| File | `php/extras/databases/gigya.php` (~910 lines, 13 functions) |

## config.xml
```xml
<database
    name="gigya"
    dbhost="{data-center}"
    dbtype="gigya"
    dbuser="{userKey}"
    dbpass="{secretKey}"
    dbkey="{apiKey}" />
```

| attribute | used for |
|---|---|
| `dbhost` | the **data center**, e.g. `us1.gigya.com` — it is substituted into the endpoint hostname |
| `dbuser` | `userKey` |
| `dbpass` | `secretKey` |
| `dbkey` | `apiKey` |
| `access_token` | optional. When set (or passed as `-access_token`), the driver sends `Authorization: Bearer …` and **drops `userKey`** from the request |

## Endpoints — the "table" you query selects the URL
| table in your SQL | endpoint |
|---|---|
| `accounts` | `https://accounts.{dc}/accounts.search` |
| `auditLog` | `https://audit.{dc}/audit.search` |
| data store | `https://ds.{dc}/ds.search` |
| index | `https://idx.{dc}/idx.search` |
| *(anything else)* | falls back to `accounts.search` |

## Calling it
```php
$recs = dbQueryResults('gigya', "SELECT UID, profile.email, lastLoginTimestamp FROM accounts LIMIT 50");

// audit log, from the examples at the top of the driver
$recs = dbQueryResults('gigya', 'SELECT uid AS dist_id, params.loginid AS loginid, timestamp,
    err_code, err_message, endpoint, http_req.country AS country, ip, user_agent.browser AS browser
  FROM auditLog WHERE endpoint = "accounts.login" ORDER BY @timestamp LIMIT 10');

$n = dbGetCount('gigya', ['-query'=>"select count(*) as cnt from auditlog
  WHERE @timestamp >= '2026-01-01T00:00:00.000Z' and @timestamp <= '2026-12-31T23:59:59.000Z'"]);
```

## Function reference
| function | notes |
|---|---|
| `gigyaQueryResults($query,$params)` | picks the endpoint from the `FROM` table, signs the request, normalises the response |
| `gigyaGetDBRecords($params)`, `gigyaGetDBRecordsCount($params)` | record/count wrappers over `-query` |
| `gigyaGetDBTables()` | the queryable collections |
| `gigyaIsDBTable($table)` | — |
| `gigyaGetDBFieldInfo($tablename)` | field discovery |
| `gigyaGetTableDDL($table,$schema)` | best-effort "create script" |
| `gigyaGetDBIndexes()`, `gigyaGetDBTableIndexes()` | present for API parity |
| `gigyaNamedQueryList()` / `gigyaNamedQuery($name)` | canned queries for the admin console |
| `gigyaLowerKeys()`, `gigyaRandomUserAgent()` | internal helpers |

## Notes & gotchas
- **⚠️ There is a destructive endpoint in this file.** `accounts.deleteAccount` is referenced in the code — deleting a customer account is irreversible and has legal/GDPR weight. Never wire it to a page action without an explicit confirmation step and an audit trail.
- **String literals in Gigya SQL use double quotes** (`endpoint = "accounts.login"`), unlike ANSI SQL. Timestamps are ISO-8601 with `Z`, and `@timestamp` (with the `@`) is the audit log's time field.
- **Dotted field paths are normal** (`profile.email`, `http_req.country`, `user_agent.browser`) because the underlying documents are nested. Alias them (`AS country`) so the returned keys are usable in a grid.
- **`dbkey` here is the apiKey**, not a TLS key — the same attribute name means a private key in `mysql.php`/`postgresql.php`. Dispatch is by `dbtype`, so they never collide.
- **Bearer token mode changes the request shape**: when `access_token` is present the driver removes `userKey`. Setting both is not additive — the token wins.
- **Results are capped and paged by the API**; a wide `accounts` query needs cursor paging rather than a large `LIMIT`.
- Data-center mismatch (`us1` vs `eu1`) is the most common cause of authentication failures — the keys are per-data-center.

## See also
The SAP CDC documentation links at the top of the driver (audit log, accounts search, data store). `elastic.md`, `splunk.md`, `ccv2.md` — the other HTTP-API-as-a-database drivers.
