# WaSQL Reference (deep detail — read on demand)

This is the companion to `CLAUDE.md`. `CLAUDE.md` holds the always-relevant rules and gotchas; **this file holds the deep, feature-specific detail** you pull in only when the task touches that feature. All of it is verified against ~100 production WaSQL sites in `postedit/postEditFiles`.

**Read the section that matches your task:**

| If you are… | Read |
|---|---|
| Building a list grid / passing query options | [Data access — options & grids](#data-access--options--grids) |
| Querying a second DB (postgres/hana/mssql/c-tree) | [Named / secondary DB connections](#named--secondary-db-connections) |
| Working with views / templates / getView | [Views & templates (deep)](#views--templates-deep) |
| Building or saving a form | [Form building](#form-building) · [How addEditDBForm saves](#how-addeditdbform-saves--the-save-is-global) |
| Making a section reload via AJAX | [Section-refresh pattern](#section-refresh-pattern) |
| Stopping a refresh from blanking the section it reloads | [Kill the refresh flicker: `setprocessing`](#kill-the-refresh-flicker-point-setprocessing-at-the-button-not-the-section) |
| Making a value/row open a detail modal (no form) | [Read-only detail popup (centerpop)](#read-only-detail-popup-centerpop) |
| Building a "manage {things}" admin tab | [CRUD-tab pattern](#crud-tab-pattern) |
| Adding a chart | [Chart.js extra](#chartjs-the-chartjs-extra) |
| Building a server/DB health or monitoring page | [Server / system health in PHP](#server--system-health-in-php) |
| Writing DB record hooks / crons / query consoles | [_triggers](#_triggers--db-record-lifecycle-hooks) · [cron_*](#cron_-pages) · [_prompts](#_prompts--saved-querycode-console-not-ai-prompts) |
| Resizing/converting/compressing an uploaded image | [File upload processing](#file-upload-processing-resize--convert--maxkb--reencode) |
| Documenting functions | [PHPDoc / JSDoc convention](#phpdoc--jsdoc-convention) |
| Want copy-paste starters | [Common scenarios](#common-scenarios-copy-paste-starters) |
| Building a REST/JSON-RPC/MCP endpoint page | [Machine-facing pages](#machine-facing-pages-rest--json-rpc--mcp-endpoints) |
| Reading a remote host's OS/DB health, or SFTPing a file | [Server / system health](#server--system-health-in-php) · [SFTP](#sftp--which-extra-to-use-verified-2026-08) |
| Backing up/restoring a database | [Database backup & restore](#database-backup--restore-dumpdb--shdb_importsh) |
| A table/column that reads back empty for no reason | [Reserved word as a column name](#a-reserved-word-as-a-column-name-makes-a-table-unreadable-verified-2026-08-18) |
| A page's own css/js not applying where expected | [Page css/js are page-scoped](#a-pages-cssjs-are-page-scoped--shared-styles-belong-in-the-template) |
| Calling an external JSON REST API from PHP | [Calling a JSON REST API](#calling-a-json-rest-api--use-postjson-its-limit-is-post-only-verified-2026-08-18) |

---

## Data access — options & grids

### `-` option keys for data access
- `-table` — table name. `-fields` — comma list of columns to SELECT. `-where` — raw SQL WHERE fragment.
- `-order` — ORDER BY (supports `desc`). `-limit` / `-start` — paging.
- `-index` — re-key the returned array by a column (e.g. `'-index'=>'username'`).
- `-query` — supply full raw SQL, bypassing the `-table`/`-where` builder.
- `-relate` — array `fk_column => related_table`; auto-expands foreign keys into the result.
- `-upsert` / `-upserton` — comma list of columns; turns add/edit into an upsert keyed on them.
- `-nocache` — bypass the query cache (use in crons and after writes).
- `-debug` — dump the generated SQL.
- `-results_eval` / `-results_eval_params` — callback run over the result set to compute extra columns.

### `databaseListRecords` — HTML data grid
(Formerly `listDBRecords` — that alias still works but is being replaced; write `databaseListRecords`.) Two modes: `['-list'=>$prefetchedRecs]`, or query mode with `-table`. Presentation keys: `-listfields` (columns to show), `-action`, `-tableclass`, `-tableheight`, `-hidesearch`, `-searchfields`, `-sorting`, `-export`. Per-column overrides use a dashless `fieldname_options` array with HTML attrs and `%_id%` token substitution. **Keep the (often lengthy) option array in a `functions` helper, not inline in the controller:**
```php
// in the page's functions field (the "model"):
function pageScriptsGrid(){
  return databaseListRecords(['-table'=>'modq_scripts','-listfields'=>'_id,name,_cdate',
    'name_options'=>['data-nav'=>'/t/1/modq/scripts/addedit/%_id%']]);
}
// controller just calls it:
$grid = pageScriptsGrid();
```

### System tables & audit columns
- **Leading-underscore tables are framework/system tables:** `_pages`, `_templates`, `_users`, `_cron`, `_fielddata`, `_translations`, `_tabledata`. App/business tables have no leading underscore, and are often module-prefixed (`sb_task`, `modq_scripts`, `wcommerce_orders`).
- **Every record carries audit columns:** `_id` (PK), `_cdate` (created), `_edate` (edited), `_cuser` (creating user id), `_euser` (editing user id).

### ⚠ `executeSQL()` returns an ARRAY on success — never test it with `isNum()`
`executeSQL($sql)` returns `['query'=>…,'result'=>…]` when the statement ran, and an **error string** when it failed (`php/database.php` ~8728, via `setWasqlError`). The add/edit helpers return an **id**, so the usual `if(!isNum($id))` idiom is right for *those* — but applying it to `executeSQL` inverts the test: every successful statement looks like a failure, and every failure looks... also like a failure. Symptom seen in practice: a bulk importer reported "0 rows written" on runs that had in fact written 25,000 rows, so a genuinely broken batch was indistinguishable from a good one.
```php
$ret=executeSQL($sql);
if(!is_array($ret)){ /* $ret is the error string */ }
// one helper that covers both shapes:
function sqlOk($ret){ return is_array($ret) || isNum($ret); }
```
Two more traps in the same area, both of which fail **silently**:
- **MySQL error 1093** — `UPDATE t SET c=(SELECT count(*) FROM t WHERE …)` is rejected outright ("can't specify target table for update in FROM clause"). Wrap the aggregate in a derived table and JOIN it: `UPDATE t o JOIN (SELECT k, count(*) n FROM t GROUP BY k) x ON x.k=o.k SET o.c=x.n`. Reset the column first — a JOIN only touches rows that still match. Combined with the `isNum` bug above, this left a counter column at 0 for every row with no error anywhere.
- **A multi-row `INSERT` is all-or-nothing.** One oversized value (a `varchar(600)` handed 900 characters) aborts the whole statement under strict mode, so a 200-row batch loses 200 rows. Clamp values to the column width before building the SQL — `information_schema.columns` gives you `data_type` and `character_maximum_length` at runtime, so no hardcoded map is needed — and on failure retry the batch row-by-row to identify the offender instead of dropping the batch.

### DB result keys are always lowercased
WaSQL's MySQL layer lowercases **every** result-row key before returning it (`php/extras/databases/mysql.php`: `$key=strtolower($key); $rec[$key]=$val;`), regardless of the SQL's column casing. This matters most for raw `SHOW COLUMNS FROM t` results: the column name comes back as `$c['field']`, NOT `$c['Field']` — also `$c['null']`, `$c['key']`, `$c['default']`, `$c['extra']`, all lowercase. Reading the wrong case doesn't error, it just returns `null`, which silently breaks any guard built on it (see "Self-healing DB columns" below for the concrete failure mode).

### Self-healing DB columns
When a feature depends on a new column that may not exist yet on every install, check for it at runtime inside the function that needs it and `ALTER TABLE` inline instead of requiring a separate migration step:
```php
function myFeatureNeedsColumn(){
  $cols=getDBRecords("SHOW COLUMNS FROM _users");
  $have=[]; foreach($cols as $c){ $have[$c['field']]=1; }   // lowercase 'field' — see above
  if(!isset($have['my_new_col'])){
    executeSQL("ALTER TABLE _users ADD my_new_col TEXT NULL");
  }
}
```
- Use **`executeSQL($sql)`** for the DDL — it runs on the site's own default DB connection. (`sqlQueryResults` does not exist; don't confuse it with the per-engine internal helpers like `mysqlQueryResults`.)
- **⚠️ Get the casing right or the guard silently no-ops forever.** A guard written as `$have[strtolower($c['Field'])]=1` reads `$c['Field']` as null every time (since it's actually `$c['field']`), so `$have` is always empty and the guarded block runs on **every request**, not once. If that block contains a destructive statement (a cleanup `DELETE`, a re-seed), it fires repeatedly — this is exactly what caused a real bug where every newly-suggested record got wiped on every page load.

### `getDBFieldInfo($table)` — validate against the real column limits BEFORE the write
`getDBFieldInfo('mytable')` returns one entry per column, keyed by the **lowercased** column name (see "DB result keys are always lowercased"), and is cached per table — cheap enough to call on every write path. The keys worth knowing:
- **`_dbtype`** — the bare type (`varchar`, `int`, `decimal`). Note it is only split off the declaration when the type ends in `)`, so an unsigned column arrives whole as `int(11) unsigned`; pull the leading word with `preg_match('/^([a-z]+)/',...)` rather than comparing `_dbtype` directly.
- **`_dbtype_ex`** — the FULL declaration plus flags: `decimal(6,2)`, `varchar(50) NOT NULL`, `int(11) unsigned`. This is the only key carrying **precision/scale and signedness**, so it's what you parse for a numeric range.
- **`length`** — for `varchar`/`char` the max characters; for `decimal(6,2)` it is the **precision only** (`6`), and for `int(11)` it is the display width (`11`), which is *not* a value limit.
```php
$info=getDBFieldInfo('my_table');
// decimal(p,s) max = 10^(p-s) - 10^-s   →   decimal(6,2) = 9999.99
if(preg_match('/\((\d+)\s*,\s*(\d+)\)/',$info['story_points']['_dbtype_ex'],$m)){
  $max=pow(10,(int)$m[1]-(int)$m[2])-pow(10,-(int)$m[2]);
}
```
**Why bother:** MySQL here runs **`STRICT_TRANS_TABLES`**, so an over-length string or out-of-range number is **not** truncated/clamped — MySQL refuses the **whole statement**, meaning one bad field silently voids every other field written with it. `editDBRecord` then returns the DB message *with the full SQL appended* (`Out of range value for column 'story_points' at row 1: update … set story_points='546464…'`), and any code that surfaces that string hands the end user your UPDATE statement. Validate ahead of the write, message on the column name, and drive the matching HTML `max`/`maxlength` from the same helper so client and server can't disagree.

### Writing a real NULL: `'<sql>NULL</sql>'`, never PHP `null` and never `''`
An optional FK or date that the user left blank has to reach the DB as `NULL`. Both obvious ways are wrong, and they fail in opposite directions:

| what you pass | what happens |
|---|---|
| PHP `null` (or any non-numeric) on an **int/tinyint/real** column | `addDBRecord` refuses the whole write: `addDBRecord Datatype Mismatch: numeric field "org_id" is type "int" and requires a numeric value`. At least it's loud. |
| `''` on a **date/datetime** column | Silently stored as **1970-01-01**. The date branch runs `date('Y-m-d',strtotime($val))`, and `strtotime('')` is `false`, which `date()` treats as epoch. |

The framework's own escape hatch is a literal `<sql>…</sql>` wrapper, matched **before** every datatype branch in `addDBRecord` (`php/database.php` ~6393) and in `editDBRecord` (~8461), and emitted into the statement raw:
```php
$null='<sql>NULL</sql>';
$rec['owner_id']    = isNum($opts['owner_id'])   ? (int)$opts['owner_id']  : $null;
$rec['warranty_end']= isDate($opts['warranty_end'])? $opts['warranty_end'] : $null;
$rec['cost']        = isNum($opts['cost'])       ? $opts['cost']           : $null;
```
- Works uniformly for `int`, `date`, `datetime` and `decimal`, on **both** add and edit — one idiom, no per-type special cases.
- On an **edit** this is the only way to *clear* a value: simply omitting the key means "leave it alone", so a user who empties a field would see it silently keep its old value.
- The int columns also accept the literal string `'null'` (case-insensitive), but that does **not** help the date columns, so prefer the `<sql>` form everywhere rather than remembering which type takes which.
- `<sql>…</sql>` is general-purpose raw SQL, not NULL-specific — `'<sql>NOW()</sql>'`, `'<sql>col+1</sql>'` work the same way.

### `_pages.settings` — per-page config a site admin can edit at runtime
`_pages` has a **`settings`** column intended for exactly this: JSON config *belonging to one page*, so a feature can be tuned from the page's own UI with no DDL, no new table, and no deploy. Read/write it like any other column, from the page itself:
```php
$rec=getDBRecord(['-table'=>'_pages','-fields'=>'settings','name'=>pageValue('name'),'-nocache'=>1]);
$settings=strlen(trim($rec['settings']))?json_decode($rec['settings'],true):[];
$settings['my_feature']['some_key']=$value;                     // namespace under ONE key
$ok=editDBRecord(['-table'=>'_pages','-where'=>"name='mypage'",
	'settings'=>json_encode($settings,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)]);
if(!isNum($ok)){ /* $ok is the error string */ }
```
- **Namespace your keys and merge, never replace** — the field belongs to the page, not to your feature; a blind overwrite eats anything added later.
- **Type varies by install.** Newer cores provision it as `JSON` (`php/admin/appstore_functions.php` does `ALTER TABLE _pages ADD settings JSON NULL`), older ones as `mediumtext`. On a **mediumtext** install there is **no automatic `settings_ex`** sibling — you must `json_decode(...,true)` yourself. Handle both: prefer `settings_ex` when present, else decode.
- **Read with `-nocache`.** Two writers exist (your UI, and PostEdit) so a cached copy goes stale within a request; re-read after writing before re-rendering.
- **PostEdit mirrors `settings` as a local json file once the field holds a value** — so write it **pretty-printed**, or the mirrored file is one unreadable line that nobody can hand-edit or diff.
- `pageValue('settings')` also works, but it reads the request-time `$PAGE` global, so it is **stale immediately after your own write** — use it for display, not for read-modify-write.

## Named / secondary DB connections
The standard `getDBRecord`/`getDBRecords`/`getDBCount`/`addDBRecord`/`editDBRecord`/`executeSQL` functions hit the **current site DB**. To query a *different* connection registered in `config.xml` (e.g. a PostgreSQL warehouse named `pg_warehouse`, a HANA/c-tree source), use the **`db*` wrapper family** — the connection name is always the **first argument**. It dispatches through `dbFunctionCall()`, which looks the name up in the global `$DATABASE` array, detects its `dbtype` (postgres/hana/mssql/mysql…), loads the right engine, and runs the type-specific query. This — not `-dbname` — is the idiom real multi-source sites use.
```php
$rows = dbQueryResults('pg_warehouse', "SELECT ... LIMIT 50"); // raw SQL -> array of rows
$rec  = dbGetRecord('pg_warehouse', "SELECT pg_postmaster_start_time() AS started"); // one row
$n    = dbGetCount('pg_warehouse', ['-table'=>'foo','status'=>1]);
$grid = dbListRecords('pg_warehouse', ['-query'=>"SELECT ...", '-tableclass'=>'wacss_table is-striped']);
$ok   = dbExecuteSQL('pg_warehouse', $sql); // non-SELECT
```
- Returns an **error STRING** on failure (test `is_array($rec)` / `isNum()`, never truthiness).
- ⚠️ **…but a failed SELECT usually returns an EMPTY ARRAY, not a string** — so "no rows" and "the query blew up" are indistinguishable from the return value alone. Verified in `php/extras/databases/postgresql.php`: `postgresqlQueryResults()` `return array()` on both a failed connect and a failed `pg_query`. The real reason is left in **`$DATABASE['_lastquery']['error']`**, which every driver blanks on entry and fills on failure. **Checking it after every `db*` read is mandatory.** Use the accessor:
```php
$rows = dbQueryResults('pg_warehouse', $sql);
if(strlen(dbLastError())){ /* real failure - report it */ }   // '' = genuinely 0 rows
```
  `dbLastError()` / `dbGetLastError()` / `dbGetLast('error')` are the same call. **Fixed 2026-08-17 in core:** `dbGetLast()` read `$DATABASE['_last_']`, which is only ever written by `dbSetLast()` — a function nothing in the tree calls — so `dbGetLastError()` returned `''` for every failure ever. It now falls back to `$DATABASE['_lastquery']`, where the engine files actually record. On a core older than that date, read the array directly: `global $DATABASE; $err=trim((string)($DATABASE['_lastquery']['error'] ?? ''));`.
  `dbGetLast()` with no key returns the whole set — it also carries `query`, `start`/`stop`/`time` and the calling `function`, which is the cheapest way to time a remote query. Without this check a page reports `select * from no_such_table` as a successful empty result, and that bug survives for months because "broken query" and "legitimately empty table" look identical to the caller.
- PostgreSQL is a first-class `dbtype`; the engine lives in `php/extras/postgresql.php` and is auto-loaded — no manual `loadExtras`.
- Enumerate configured connections via `global $DATABASE; foreach($DATABASE as $name=>$info){ /* $info['dbtype'],['dbhost'],['dbname']… — NEVER print ['dbpass'] */ }`. Active connection = `$CONFIG['db']`.
- (Legacy: `getDBRecords(['-dbname'=>'x',...])` also exists — older ODBC-oriented path; prefer the `db*` wrappers in new code.)

### c-tree (FairCom): two separate paths — PHP/ODBC or Groovy/JDBC

A `dbtype="ctree"` connection can be reached **two** ways, and they share nothing:

1. **PHP over ODBC** — `php/extras/databases/ctree.php` (`ctreeQueryResults`/`ctreeExecuteSQL`, reached normally via `dbQueryResults($conn,$sql)`). Works wherever the **FairCom ODBC Driver** is installed and the `<database>` tag carries a full `connect="Driver={Faircom ODBC Driver};Host=…;Port=6597;…"` string. This is what `php/admin/sqlprompt*` uses.
2. **Groovy/JDBC** — the `<?groovy … ?>` island below. Use it when there is no ODBC driver on the box, or when you want ctreedb's stream-to-CSV behaviour.

> ⚠️ **PHP 8.4 turned every `odbc_*` handle into an object** (`Odbc\Connection` / `Odbc\Result`) instead of a resource, and a handle that has been **freed or closed stays an object**. So the framework's `commonIsResourceOrObject($h)` guard — used all over `ctree.php`, `hana.php`, `odbc.php`, `snowflake.php` — no longer tells you whether a handle is still *open*, and the second `odbc_free_result()`/`odbc_close()` now **throws** `Error: ODBC result has already been closed` (or `…connection has already been closed`) where PHP ≤8.3 quietly returned false. Two consequences:
> - **Never free a result in both the enumerator and its caller.** `ctreeQueryResults` did exactly that (it freed the handle `ctreeEnumQueryResults` had already freed) and every query fatal'd at `ctree.php:991`. Free through **`ctreeFreeResult()`**, which try/catches the double free.
> - **Always null the cached global after closing it.** `$dbh_ctree` is memoised by `ctreeDBConnect()` (`if(commonIsResourceOrObject($dbh_ctree)){return $dbh_ctree;}`), so a close that leaves the closed object in place hands the *next* caller a dead connection and `odbc_prepare()` throws on it.
> The same double-free shape is still present in `hana.php` (caller at ~327 frees what the enumerator at ~1813 already freed) — expect the identical fatal there under 8.4.

**Connection reuse on the PHP/ODBC path is opt-in per connection.** By default every `ctreeQueryResults()`/`ctreeExecuteSQL()` call opens its own `odbc_connect()` and closes it on the way out. To try pooling, add `persistent="1"` to that one `<database>` tag in `config.xml` (every attribute on the tag arrives in the driver as `-{attr}`); `ctreeReleaseConnection()` then leaves the handle open for the next request instead of closing it, and a reused handle is liveness-probed with `SQLTables` before being handed back.

> ⚠️ **The ceiling is workers × connections, not concurrent queries.** FairCom licenses concurrent *sessions*, and a persistent handle is held for the whole life of the php worker whether or not it is being used — so 50 apache/php-fpm workers against a 20-seat licence gives you `Maximum users exceeded` on an idle system. (That is what `ctree.php` writes `C:\bin\ctree_failed.txt` for.) Only turn it on where the worker count is at or below the seat count, and turn it on for one connection at a time.

> Also note `ctreeDBConnect()`'s legacy `odbc_pconnect()` block is **unreachable**, and not the way to enable this: `ctreeParseConnectParams()` sets `-single` unconditionally to `0`, and the branch above it tests `isset($params['-single'])` — which is true for a `0`. "Fixing" that `isset()` would silently flip every c-tree query on every site to a pooled connection. Use the `persistent="1"` attribute.

**The Groovy/JDBC path — with two hard limits.**

Where the FairCom ODBC driver is not installed, `dbQueryResults()` cannot reach the connection at all. The idiom there is to build a `<?groovy … ?>` island and hand it to `evalPHP()`, which shells out to `groovy` (or `groovyclient`) → `groovy/db.groovy` → `groovy/ctreedb.groovy` → the FairCom JDBC jar in `groovy/lib`:
```php
// NOTE: the tags MUST be built by concatenation - a literal php close tag anywhere
// in a page field silently truncates the rest of that field (CLAUDE.md gotcha #2b).
$groovy  = '<'.'?'.'groovy'.PHP_EOL;
$groovy .= "def sql = \"\"\"\n{$sql}\n\"\"\"\n";
$groovy .= "recs = db.queryResults('{$conn}', sql, [:])\nprintln(recs)";
$groovy .= PHP_EOL.'?'.'>';
$rows = decodeJSON(evalPHP($groovy));   // db.queryResults merges $DATABASE[$conn] into the params map
```
Two ceilings bite on anything large:

| limit | value | where |
|---|---|---|
| JDBC statement timeout | **600 s** default | `groovy/ctreedb.groovy` — pass `querytimeout: N` in the params map to change it, `0` to disable. **Precedence trap:** `db.groovy` copies the connection's `config.xml` attributes over the params map *after* the caller's, so a `querytimeout` attribute on the `<database>` tag overrides what the caller asked for. |
| result materialisation | every row exists **3×** | groovy list → JSON string on the stdout pipe → decoded PHP array |

Measured against a production c-tree source: **~40 s per 100,000 single-column rows** end to end. A few hundred thousand rows will blow past any HTTP client's read timeout long before the query itself is the problem.

**For anything that could be big, stream to CSV instead.** `ctreedb.queryResults` writes rows straight to disk as it fetches them when you pass `filename`, and returns only the path — nothing but a filename crosses the pipe:
```php
$params = "[filename: '".addslashes($csvfile)."', fetchsize: 5000]";
// …same island, with $params in place of [:]
```
Then walk the file with `fgetcsv()` — you get an exact row count for the cost of one pass while materialising only the rows you actually display. Note the header row carries a **UTF-8 BOM** (ctreedb writes one for Excel), so strip `\xEF\xBB\xBF` off the first column name. Split the work into two helpers — one that returns rows in memory for small results, one that streams to CSV for large ones — so each caller picks per query.

> ⚠️ **Never treat the CSV existing as success.** ctreedb opens the writer and stamps the BOM *before* it calls `executeQuery`, so any failure — bad column, statement timeout — leaves a 3-byte file on disk. `file_exists()` then passes, the file parses as a header row with no data, and a hard error is reported to your caller as a perfectly ordinary **empty result set**. Success is signalled by ctreedb *printing the filename it wrote*: compare the **last line** of `evalPHP()`'s output against your path. Don't substring-match — the failure path returns `evalGroovyCode`'s HTML block, which quotes the generated script and therefore contains the path too, just never at the end.
>
> This is also why `ORDER BY` is where a c-tree statement timeout bites: it forces the engine to sort the entire result before yielding row one, so the timeout fires inside `executeQuery` rather than partway through the fetch loop.

### TLS / certificate authentication on a connection
Every attribute on a `<database>` tag is copied into the driver's params as `-{attr}` (`snowflakeParseConnectParams`, `postgresqlParseConnectParams`, `mysqlParseConnectParams`), so cert auth is pure `config.xml` — no code per site. Shared attribute vocabulary:

| attribute | meaning |
|---|---|
| `dbcert` | client cert (mysql/pg) **or** the PKCS#8 private key (snowflake key-pair/JWT) |
| `dbkey` | client private key, when the driver wants cert and key separately (mysql/pg) |
| `dbcertpass` | passphrase protecting the key |
| `dbca` | CA bundle used to verify the *server* |
| `dbcapath` | directory of CA certs (mysql only) |
| `dbcipher` | cipher list (mysql only) |
| `dbauth` | snowflake authenticator — defaults to `SNOWFLAKE_JWT` whenever `dbcert` is set |
| `dbsslmode` | pg `sslmode`. Auto: `verify-full` if `dbca` set, `require` if only cert/key set, else the old `disable` |
| `dbssl` / `dbsslverify` | mysql: `dbssl="1"` forces TLS with no client cert; `dbsslverify="0"` accepts a self-signed server cert |

**A hand-written `connect` attribute always wins.** Both snowflake and postgres only *add* keys the connect string doesn't already name (case-insensitive), so existing `connect="…"` connections behave exactly as before.

```xml
<!-- snowflake key-pair auth: dbpass is no longer required -->
<database name="snowflake_prod" dbtype="snowflake" dbname="SNOW_DSN" dbuser="SVC_WASQL"
    dbcert="/etc/wasql/certs/snowflake_prod.p8" dbcertpass="xxx"
    dbaccount="ab12345" dbwarehouse="WH" dbschema="PUBLIC" dbrole="RPT" />
<!-- postgres client cert -->
<database name="pg_ods" dbtype="postgresql" dbhost="db.x.com" dbname="ods" dbuser="svc"
    dbcert="/etc/wasql/certs/ods.crt" dbkey="/etc/wasql/certs/ods.key" dbca="/etc/wasql/certs/ca.crt" />
```
Gotchas, all real:
- **The key must be readable by the apache user** — `0400 root:root` fails as a generic auth error. All three drivers now pre-check and log the true reason (missing vs. unreadable) via `debugValue`.
- **Snowflake:** the private key can't be passed as a password, so it goes into the ODBC connect string (`AUTHENTICATOR=SNOWFLAKE_JWT;PRIV_KEY_FILE=…;PRIV_KEY_FILE_PWD=…`) and user/pass are then passed to `odbc_pconnect` as **empty strings** — PHP wraps the string as `DSN=<string>;UID=…;PWD=…` if they aren't, which the driver manager misreads as a DSN name. The *public* key must be registered on the Snowflake user (`ALTER USER x SET RSA_PUBLIC_KEY='…'`).
- **Snowflake `snowsql="1"` connections** use the CLI, not ODBC: the generated config gets `private_key_path=` + `authenticator=SNOWFLAKE_JWT`, and the passphrase goes through the `SNOWSQL_PRIVATE_KEY_PASSPHRASE` env var (snowsql refuses to read it from the config file).
- **Postgres:** libpq **rejects a key file that is group/world readable** — `chmod 0600`. `pg_hba.conf` needs `cert` or `clientcert=verify-full`. `sslpassword` needs libpq 14+; on older libpq an encrypted key fails with "invalid connection option".
- **MySQL:** mysqli has no connect string, so `mysqli_ssl_set()` runs between `mysqli_init()` and `mysqli_real_connect()` (`mysqlSetConnectSSL`). Client-cert *auth* also needs `ALTER USER … REQUIRE X509`. mysqlnd does **not** read `[client]` from `my.cnf`, so the attributes are the only route.
- Passwords/passphrases are masked in snowflake's connect-error dumps (`snowflakeMaskConnectParams`) — keep using it if you add error paths.
- **Not covered:** the *main* site DB connection for mysql (`databaseConnect()` in `php/database.php`) takes only host/user/pass/dbname and has no TLS support. Named connections only. Also untouched: hana/mssql/oracle (each needs a different mechanism), and file-based drivers where certs are meaningless.

---

## Polyglot islands — running another language inside a page field

Any page field that `evalPHP()` processes (`body`, `functions`, `controller`, a view) can contain an island in a language **other than PHP**. Syntax: the language token is the first word inside the `<? … ?>` block.

```
<?ruby
  puts "hello from ruby #{RUBY_VERSION}, logged in as " + wasqlUser("username")
?>
```

- **Recognized tokens** (regex in `php/common.php` ~L10597): `python`/`py`, `perl`/`pl`, `ruby`/`rb`, `lua`, `node`/`nodejs`, `R`/`rscript`, `tcl`, `julia`/`jl`, `groovy`, `bash`, `sh`, `powershell`/`pwsh`/`ps1`, `vbscript`/`vbs`. `<script type="php">…</script>` is also accepted as a PHP island.
- **How it runs:** `evalPHP` strips the token, calls `commonGetLangInfo()` for `{exe, ext, comment, shebang}`, then dispatches to a per-language `eval<Lang>Code()` (Julia, Tcl, R, **Ruby**, Python, Perl, Lua, Node, Bash, PowerShell, Groovy, VBScript). Anything without a dedicated function falls through to a generic "write temp file, shell out to `exe`, splice `stdout` back in" runner. **The island's stdout replaces the `<? … ?>` block** — so `print`/`puts`/`println`, not `return`.
- **WaSQL globals are injected** as native values before your code runs: `USER`, `CONFIG`, `PAGE`, `TEMPLATE`, `PASSTHRU`, `DATABASE`, `REQUEST`, `SESSION`, `SERVER`, `CRONTHRU` (JSON-serialized in PHP, base64'd onto the script, decoded there). Convenience accessors `wasqlUser("k")`, `wasqlConfig("k")`, `wasqlPage("k")`, `wasqlPassthru(0)`, … are defined too. Add more with `$CONFIG['eval_globals']`.
- **Prerequisite:** the interpreter must be on the server's `PATH` (`ruby`, `python3`, `julia`, `groovy`, …). Not installed by WaSQL — same bar as the Julia/Groovy ports.
- **Cost:** a fresh interpreter process per island, per request (no daemon). Fine for glue; don't put one in a tight loop.
- **`$CONFIG['includes'][<ext>]`** lets a site preload one script file into every island of that language (imported as `page`).
- **Single-pass scan:** `evalPHP` matches every `<? … ?>` island in a field *once*, up front, then substitutes results. An island **emitted by a helper's return value** (e.g. a `functions` builder that returns a string containing `<?python … ?>`) is spliced in *after* the scan and is **never executed** — it renders as literal text. To run another language from model code, call `commonGetLangInfo($tag)` + the matching `eval<Lang>Code($lang,$code)` directly (set `$lang['evalcode_md5']` first). The admin Tests → Languages tab (`php/admin/test_functions.php` → `testLangRun()`) does exactly this.

### Database access from an island

Each language that ships a driver set has a sibling directory — `ruby/`, `python/`, `R/`, `Tcl/`, `lua/`, `julia/`, `groovy/` — with the same shape: `common` (helpers), `config` (`config.xml` reader), `db` (dispatcher), `{mysql,postgres,sqlite,mssql,snowflake}db` drivers, `<lang>info`. The dispatcher resolves `conn_name` against `config.xml` and loads the driver for that `dbtype` **on demand**, so a page that never queries needs none of the driver libraries installed. **The call name and return shape are NOT uniform across the ports** (verified against the source):

| lang | call | returns |
|---|---|---|
| Ruby | `dbQueryResults(conn, sql)` / `db_query_results` | `{ "columns"=>[…], "rows"=>[{col=>val},…], "count"=>n }` — plus `results_as_table` / `results_as_csv` |
| Lua | `dbQueryResults(conn, sql)` | `{ columns={…}, rows={{…}}, count=n }` |
| R | `dbQueryResults(conn, sql)` | a `data.frame` (`rows$colname`) |
| Tcl | `dbQueryResults conn sql` | `array get` form: `$res(rows)` = count, `$res(<i>,<col>)` = value |
| Python | `db.queryResults(conn, sql)` | a `list` of row `dict`s (`db`, `common`, `config` are auto-imported) |
| Julia | `db.queryResults(conn, sql)` | a JSON **string** by default → `JSON3.read(res)` |
| Groovy | `db.queryResults(conn, sql, [:])` | a JSON **string** by default → `new JsonSlurper().parseText(json)` |

```
<?ruby
  res = dbQueryResults("mydb", "SELECT username, email FROM _users ORDER BY _cdate DESC LIMIT 10")
  puts results_as_table(res)          # or results_as_csv(res)
?>
```

The admin **Tests → Languages** tab (`/php/admin.php?_menu=test`) has a live, per-language sub-tab demonstrating each of these against `_users`.

Driver libraries are **not** bundled (same as R's `install.packages` / Lua's `luarocks`):

| lang | mysql | postgres | sqlite | mssql | snowflake |
|---|---|---|---|---|---|
| Ruby | `gem install mysql2` | `gem install pg` | `gem install sqlite3` | `gem install tiny_tds` | `gem install ruby-odbc` |

> ⚠️ A failed island returns an HTML error block (red banner + the generated script), not your data — check for it the way the c-tree/Groovy note above describes. A **DB** call inside the island raises in that language and surfaces in that block.

---

## Views & templates (deep)
- A `body` field is a set of named `<view:name>...</view:name>` blocks. **Defining a view never outputs it** — WaSQL extracts every block into a registry and the block text is removed from where it sits. It only appears when a `renderView`/`renderViewIf`/`renderEach` call names it, or `setView()` selects it as the page output.
- **Nesting `<view:>` blocks is purely cosmetic** (for readability). An inner block still only renders where something explicitly calls it. Don't expect an inline nested view to appear in place.
- Render functions: `renderView($name[,$data[,$var]])`, `renderViewIf($cond,$name[,$data[,$var]])`, `renderViewIfElse($cond,$a,$b,...)`, `renderViewSwitch($val,$map,...)`, `renderEach($name,$array[,$var])`. The view name and loop-var name are independent: `renderEach('photo',$product['photos'],'photo')`.
- `getView($name)` returns a view's raw string (used to feed `-fields`/`-listview` in form options). Wrap in `evalPHP(getView(...))` when the view layout contains PHP to evaluate.
- **`renderView($name,$data)` exposes `$data` inside the view as `$params`** (the whole passed value — array, string, etc.). Access it as `$params['title']` etc., or rename it with the `-alias` opt (`renderView('addedit',$data,'row')` → `$row` in the view). There is also a `-format=>'addeditdbform'` opt that renders the view's `[fieldname]` layout as an `addEditDBForm` (pass `-table`).
- **⚠️ A view is evaluated in an ISOLATED scope, not the body's scope — even though the body syntax makes it look like plain inline PHP.** `renderView`/`renderViewIf`/`renderEach` build a brand-new PHP snippet containing only `global $USER;`/`$PAGE;`/`$TEMPLATE;`/`$key = $VIEW_KEY;`/`$params (or your -alias) = $VIEW_PARAMS;`, then `evalPHP()` that (verified `php/common.php` ~L8702-8713). **Only those five names are visible inside `<view:...>` markup** — any other variable computed earlier in the body (`<?php $count=...; ?>` sitting above the view block) is invisible inside it, silently evaluating to `null`/empty rather than erroring. Fix: pass it through explicitly.
  ```php
  <?php $survey_abandoned_count=portalSurveyAbandonedCount(); ?>
  <view:survey_cleanup_btn>
    <button ...><?=$survey_abandoned_count;?></button>  <!-- ✗ undefined inside the view -->
  <\view:survey_cleanup_btn>
  <?=renderViewIf($cond,'survey_cleanup_btn');?>                                          <!-- ✗ nothing passed -->
  <?=renderViewIf($cond,'survey_cleanup_btn',$survey_abandoned_count,'survey_abandoned_count');?> <!-- ✓ passed + aliased -->
  ```
  This is the same rule as the `-alias` bullet above, stated as a trap: it's not enough for the variable to merely be *in scope near* the view in the body — it must be an explicit `renderView*` argument.
- **⚠️ `<view:>` blocks are only registered in `$VIEWS` during BODY rendering, NOT when the controller/functions run.** So a function that calls `getView('..._fields')` or `renderView(...)` must be invoked from the **body** (inside a `<view:>` block), not precomputed in the controller — calling too early throws `renderView Error: There is no view named X` and `getView` returns empty. Idiom: the controller just routes and `setView('form',1); return;`, and the body block builds it: `<view:form><?=pageAddEditForm($tab,$id);?></view:form>`. Grid/`databaseListRecords` builders that don't touch `getView` are fine to call from the controller.
- **Everything else — a plain variable the view just reads — belongs in the controller, not inline in the body.** Don't drop a bare `<?php $x = ...; ?>` island into a `<view:>` block to compute a value the markup below it consumes; add the assignment to the matching controller branch (usually `default`) alongside the page's other variables, and let the body just reference `$x`. This keeps the MVC split real: controller/functions = data, body = presentation only. (The `getView`/`renderView`-from-body rule above is the one narrow exception, because that registry genuinely doesn't exist yet in the controller.)
  ```php
  // ✗ computed inline in the body, next to the markup that uses it
  <div class="hero-actions">
    <?php $groupme=function_exists('indexSiteSocialLinks')?trim(indexSiteSocialLinks()['groupme'] ?? ''):''; ?>
    <?=renderViewIf(strlen($groupme),'hero_groupme_tile',$groupme,'groupme');?>
  </div>

  // ✓ controller (default case), body just reads $groupme
  case default:
    $groupme=function_exists('indexSiteSocialLinks')?trim(indexSiteSocialLinks()['groupme'] ?? ''):'';
    setView('default');
  break;
  ```

### Conditional views: prefer `renderViewIf`/`renderViewIfElse` over inline ternary HTML
**Prefer a named `<view:>` + a conditional render over an inline `<?=$cond ? '<html…>' : '<html…>';?>` ternary.** The ternary crams two chunks of escaped HTML onto one line — unreadable and easy to break. A view keeps the markup as real, editable HTML and the branch as a one-liner. Exact signatures (verified in `php/common.php`):
- `renderViewIf($cond, $view[, $params[, $opts]])` — renders `$view` when `$cond` is truthy, else returns `''`.
- `renderViewIfElse($cond, $viewIf, $viewElse[, $params[, $opts]])` — renders `$viewIf` when truthy, otherwise `$viewElse`.
- `$params` is the value exposed inside the view. **The trailing string arg is the alias — the variable name the passed data is bound to *inside* the view** (no leading `$`); default is `$params`. It's the same mechanism as `renderView`'s 3rd arg and `renderEach`'s loop-var name. So `renderViewIfElse($cond,'a','b',$info,'info')` → read `$info[key]` inside; `renderViewIfElse($cond,'a','b',$info,'rec')` → read `$rec[key]` inside (same data, different variable name). Whatever you pass as the data becomes that variable, so pass a sub-array (`$info['1st_counselor']`) when you want a generic view to read generic keys (`$info['picture']`).
- **The data arg doesn't have to be an array — a plain scalar works too**, and shows up inside the view as that exact scalar under the alias name (no need to wrap a single value in `array('key'=>$val)` just to unwrap it again inside the view). `renderViewIf(strlen($groupme),'hero_groupme_tile',$groupme,'groupme')` → inside the view, `$groupme` is the string directly (e.g. `<a href="<?=encodeHtml($groupme);?>">`), not `$groupme['groupme']`. Reach for the array form only when the view needs more than one field.
- To pass **extra keys** to a reused view (e.g. per-card `initials`/`avatar` for one generic `avatar` view), use the array form of `$opts`: `-alias` sets the variable name and any **dashless keys are merged into the params** (only when that key is empty in the data): `renderViewIfElse($cond,'leader_picture','avatar',$person,array('-alias'=>'info','initials'=>'B','avatar'=>'ward-avatar-bishop'))` → inside, `$info['picture']` (from `$person`) and `$info['initials']`/`$info['avatar']` (from opts).

**Instead of** an inline ternary:
```php
<?=strlen($info['1st_counselor']['picture']) ? '<img src="'.encodeHtml($info['1st_counselor']['picture']).'" alt="">' : '<div class="ward-avatar ward-avatar-c1">1</div>';?>
```
**do** this:
```php
<view:leader_picture>
  <img src="<?=encodeHtml($info['picture']);?>" alt="" onclick="wacss.showFilePreview(this);">
</view:leader_picture>
<view:avatar><div class="ward-avatar"><?=encodeHtml($info['initials']);?></div></view:avatar>

<?=renderViewIfElse(strlen($info['1st_counselor']['picture']),'leader_picture','avatar',$info['1st_counselor'],'info');?>
```
- **⚠️ The closing tag MUST be the named form `</view:name>`, NOT a generic `</view>`.** The parser matches `<view:name>(.+?)</view:\1>` with a backreference (`php/common.php` ~L8043), so a bare `</view>` silently fails to register the block and you get `renderView Error: There is no view named X`.
- **Reuse one generic view for many callers.** Because the alias renames the passed data, a single `<view:avatar>`/`<view:leader_picture>` can serve all four bishopric cards — pass each person as the params and let the view read `$info` (or whatever alias). Don't clone a near-identical view per card.

### `setView($name)` vs `setView($name, 1)`
- **The second arg `1` clears all previously-set views**, making `$name` the only view rendered. `setView($name)` (no `1`) is **cumulative** — it ADDS to the list, so multiple `setView` calls render multiple views in order.
  ```php
  setView('default');              // list = [default]
  if(isUser()){ setView('user'); } // list = [default, user] — BOTH render
  setView('login', 1);             // list = [login] — clears the rest, only login renders
  ```
- **The first arg can be a string (one view) OR an array (multiple views)** — `setView(['header','content','footer'])` sets several at once (combine with `1`: `setView(['header','content'], 1)`).
- For AJAX/partial responses and login/error short-circuits use `setView($name, 1)` so only that one view renders; always follow with `return;`. (The chrome-less output for AJAX comes from the `/t/1/` blank template, not from this arg.)

### ⚠️ Controller variables do NOT reach a NESTED view — pass them under their own name
A view selected by `setView()` renders in the body's scope, so it reads controller variables directly (`$myModel`). A view reached through `renderView()` from inside another view does **not** — `renderView` evaluates the view in its own scope where only `$params` (plus any `-alias`) exists. The symptom is silent: no error, no "no view named X", just an **empty block where `renderEach` should have output rows**, because the array it was handed was undefined.

This bites hardest on the standard "one partial, two routes" shape — a `tiles` view rendered inline on the full page AND returned on its own for an AJAX refresh. Make it work in both paths by aliasing the params back to **the same name the controller used**:
```php
// controller — the model's variable name is the contract
$rearviewModel = ['periods'=>..., 'extras'=>...];
setView('tiles',1); return;                     // AJAX: body scope, $rearviewModel is visible
```
```php
<view:default>
  <div id="tiles_div">
    <?=renderView('tiles',$rearviewModel,'rearviewModel');?>  <!-- alias == controller var name -->
  </div>
</view:default>
```
Now the `tiles` view body reads `$rearviewModel` unchanged in both paths. Without the third argument it would have to say `$params` — which then breaks the `setView` route.

### Templates
- A page is wrapped by the `_templates` record named in its `template_id` field. Templates have the same `body`/`functions`/`css`/`js` fields and drop the page in with `<?=pageValue('body');?>`.
- Template meta helpers (`templateMetaTitle/Description/Keywords/Image/Site`, `templateActiveMenu`) are **per-site copies defined in each template's `functions`**, not framework built-ins — they read from `global $PAGE`.
- **⚠️ The template's `<head>` renders BEFORE the page's `controller` runs.** Verified independently across four sites (curlyqr, wired, byuward, the Idea Garden). A controller doing `global $PAGE; $PAGE['title']='...';` has **zero effect** on `<title>`/og/twitter tags — by the time the controller executes, the template body (which includes `<head>`) has already been emitted using whatever `$PAGE['title']`/`$PAGE['meta_description']` held at bootstrap (typically a stale/blank DB column). All per-page SEO meta — including *dynamic* per-record titles like an article or product page — must be computed in the **template's `functions`**, switching on `$PAGE['name']` (and `global $PASSTHRU;` for the id/slug segment) rather than relying on anything the controller set. For a dynamic page, look the record up directly inside the template function itself (`getDBRecord(...)` keyed off `$PASSTHRU[0]`, static-cached so title/description helpers don't each re-query) — don't assume you can push that data over from the controller. Symptom when this bites you: every page's `<title>` shows a short, generic, or stale string (often literally the page's raw `name` or an old admin-seeded default) no matter what the controller sets.
- A page's own `css` and `js` fields are **auto-injected by the framework** when the page renders. Do NOT manually `<link>`/`<script>` a page's own css/js. (Framework asset bundles and extras like `chart.min.js` are a different thing and fine to include.)

### Page routing: `name` vs `permalink` — both are live URLs
- `_pages` has two independent, equally-live routes to the same record: the URL segment is matched against `name` **OR** `permalink` (`WHERE permalink='{$view}' OR name='{$view}'` in `php/index.php`'s page lookup). Either one resolves the page — `permalink` isn't just a cosmetic/SEO field, it's a second exact-match route.
- **This bites raw-text endpoints with a dotted extension** (`robots.txt`, `sitemap.xml`, `llms.txt`, a `.well-known/...` file, etc.). If the page's `name` is the bare word (`sitemap`) and its `permalink` happens to hold a *different* extension than the URL being requested (e.g. `permalink='sitemap.txt'` but the crawler/browser requests `/sitemap.xml`), the lookup matches neither `name` nor `permalink` and 404s — even though `/sitemap` (no extension, matching `name` directly) works fine. The fix is exact: set `permalink` to the *literal full string* the URL will actually contain (`sitemap.xml`, not `sitemap` or `sitemap.txt`).
- **A page added via WaMCP's `addpage` tool (or the admin "Add New" screen) may seed `permalink` with a stub value that doesn't match your intended route** — don't assume it's blank. Always check/set `permalink` explicitly for any page meant to be reached by a URL with a file extension, and verify live (`curl`/`Invoke-WebRequest` the exact URL you documented, e.g. in `robots.txt`'s `Sitemap:` line) rather than trusting that the bare page name resolving means the extensioned URL does too.
- **A bare `href="sms:…"`/`href="tel:…"` link can spawn unbounded thin/duplicate pages under an SEO crawler.** Some crawlers (confirmed with a real AIO auditor) don't recognize less-common URI schemes like `sms:` and resolve the href as a *relative path off the current directory* instead of an absolute URI — `href="sms:5551234567"` on `/doc/callings` becomes a crawled `/doc/sms:5551234567`. If that action falls through to a page's `default:` case, every phone number on the page becomes its own indexable-looking blank page (one per record — unbounded). Fix at the **template-function** level (same head-before-controller access to `global $PASSTHRU;` as `templateMetaTitle`): have `templateMetaRobots()` check `$PASSTHRU[0]` against the page's known/whitelisted actions and return `'noindex, nofollow'` for anything else, so the flood is excluded regardless of how many a crawler manages to guess. Belt-and-suspenders: also add `rel="nofollow"` to the `sms:`/`tel:` anchors themselves. Note the audit tool itself will likely keep "failing" those URLs in its own report the same way it flags an intentionally-`noindex`'d private page (e.g. a login-gated portal) — that's the tool not honoring the robots meta, not a sign the fix didn't work; verify with `curl` against the live `<meta name="robots">` output instead of re-running the audit.
- **`wardClamp($s,$min,$max,$pad)`-style length-clamp helpers: an empty-string `$pad` silently disables padding.** A common pattern (`if(strlen($s)<$min && strlen($pad)){...pad...}`) means passing `''` as pad makes the `strlen($pad)` check always false, so a too-short string can never reach `$min` — it'll keep failing a "title too short" SEO check no matter what. Always pass real fallback text as the pad argument, even in a branch you don't expect to hit the minimum often.

### ⚠ A URL path segment can never contain a space — Apache 403s before PHP runs
The rewrite folds the request path into the **`passthru` query string**, and that decodes `%20` back into a real space. Apache 2.4 then refuses the request outright: `AH10411: Rewritten query string contains control characters or spaces` — a **403 "You don't have permission to access this resource"** that never reaches PHP, so there is nothing in the PHP error log and the page/controller is never entered. Confirmed live: `/t/1/mypage/filter/(not%20set)` → 403, `/t/1/mypage/filter/Corporate` → 200.

This bites the moment a **data value** travels as a path segment — a chart label drilled down on, a department name, a status like "This week", an OS string like "Windows 11 Pro". Percent-encoding does **not** save you, and neither does `rawurlencode()`. Options:
- **Encode to a path-safe token:** base64url with a marker prefix, so the value can hold spaces, slashes, accents, anything.
```php
function pageEncodeKey($v){ return 'k_'.rtrim(strtr(base64_encode((string)$v),'+/','-_'),'='); }
function pageDecodeKey($v){
  if(strpos($v,'k_')!==0){return urldecode($v);}         // tolerate a hand-typed value
  $b=strtr(substr($v,2),'-_','+/'); $p=strlen($b)%4; if($p){$b.=str_repeat('=',4-$p);}
  $out=base64_decode($b,true); return ($out===false)?urldecode($v):$out;
}
```
  The JS side must match: `'k_'+btoa(unescape(encodeURIComponent(val))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'')` — the `unescape(encodeURIComponent(...))` dance is what makes `btoa` safe for non-ASCII.
- **Or pass the value as a query parameter** (`?v=…`) instead of a path segment — the query string is not re-decoded by the rewrite. Check first that whatever builds the URL (e.g. `wacss.nav`) handles a `?` already being present.

Path segments that are safe by construction — record ids, GUIDs, slugs, list keys, dates — are fine as-is; it's only free text that needs encoding.

### Cross-page includes & shared functions
- `includePage('topnav')` / `includePage('webhooks/add/.../{$id}')` — render another page (with optional passthru segments) inline.
- `loadDBFunctions('functions_common')` (or an array of names) — load another page's helpers into scope. **Convention: put shared helpers in a page like `functions_common` and `loadDBFunctions` it** (commonly done in the template's `functions`).
  - ⚠️ **It loads the `body` field, not `functions`.** The signature is `loadDBFunctions($names,$field='body')` (`php/database.php`). That's why real `functions_common` pages across the PostEdit sites have only a `…_pages.body.N.php` file on disk — the helpers live in **`body`**. Pass the 2nd arg explicitly (`loadDBFunctions('locations','functions')`) if you really want the `functions` field. Writing your helpers into `functions` and calling `loadDBFunctions('x')` silently loads nothing, and every helper is then an undefined-function fatal.
- **A function defined in one `_templates` record's `functions` field is invisible to a page using a *different* template.** Multi-tenant/branding helpers, AJAX-partial helpers, etc. commonly get written once in a site's main template (e.g. `wardLabel()` in a "Main" template) and work fine there — until a raw-output page (webmanifest, robots.txt-style, an AJAX endpoint) that renders through the blank `/t/1/` template calls the same helper and gets a fatal/undefined-function or silent fallback. If a helper is needed by more than one template (or by anything that might render blank/AJAX), it belongs in `functions_common` (loaded by every template that calls `loadDBFunctions('functions_common')`), not in a single template's `functions` field.
- Asset bundles: `minifyCssFile('wacss,bulma')` / `minifyJsFile('wacss,bulma')` return a URL to a combined minified bundle. `wacss` (the framework CSS/JS) is almost always included.
- `<translate>Text</translate>` wraps UI strings for localization; the framework substitutes the translated string.

---

## Form building
Two signatures across the `buildForm*` family:
- Simple: `buildForm{Type}($name, $paramsArray)` — Text, Textarea, Date, Time, Datetime, Hidden, File, Signature, etc.
- Choice: `buildForm{Type}($name, $optsArray, $paramsArray)` where `$optsArray` is a `value=>label` map — Select, MultiSelect, Checkbox, Radio, Combo, ButtonSelect, StarRating.

`$paramsArray` keys: `class`, `style`, `required`, `placeholder`, `value`, `message` (select prompt), `onchange`, `readonly`, `disabled`, `width`, `height`, and for files `path`/`autonumber`. Most common: `buildFormSelect` (~700×), `buildFormText`, `buildFormTextarea`, `buildFormDate`, `buildFormMultiSelect`, `buildFormCheckbox`, `buildFormFile`. (No `buildFormEmail`/`buildFormNumber`/`buildFormSubmit` — submit is a plain `<button type="submit">`.)

### `addEditDBForm($opts)` option keys
- Form-level (`-`): `-table`, `-fields`, `-action`, `-focus`/`-nofocus`, `-order`, `-where`, `-format`, `-formname`, `-hidefields`, `-editfields`, `-preform`/`-postform`, `-pretable`/`-posttable`, `-onsubmit`, `-noguid`. Bare `_id` toggles edit vs insert mode (present = edit).
- Per-field: `fieldname_options => [...]` (richest — keys `inputtype`, `class`, `style`, `displayname`, `required`, `message`, `tvals`, `dvals`, `value`, `values`, `width`, `height`, `-display`, `-format`, `readonly`, `onchange`, `placeholder`), or the shorthands `fieldname_class`, `fieldname_required`, `fieldname_style`, `fieldname_value`, `fieldname_displayname`, `fieldname_readonly`/`fieldname_viewonly`. **That shorthand list is finite — it is NOT a general `fieldname_anyattribute` passthrough.** `addEditDBForm` merges `fieldname_{attr}` only for the list returned by **`getDBFormForcedAtts()`** (`php/database.php`, just above `addEditDBForm`). Anything off the list vanishes with no error. *(Fixed 2026-08-17: the function's two field-render loops each carried their own copy of this list and they had drifted — `fieldname_autofocus`, `_autocomplete`, `_help`, `_text`, `_path`, `_autonumber`, `_data-labelmap`, `_min_displayname`, `_max_displayname`, `_onmousedown` and `_onmouseup` worked in one branch and were silently dropped in the other. Both now call the one function, whose list is the union of the two.)* Two things that DO work generically: `fieldname_data-{anything}` (handled by its own regex pass, ~5568) and `fieldname_options`, which merges arbitrary keys. When in doubt write `'db_name_options'=>array('placeholder'=>'…')` — always correct, no list to check. (`placeholder` itself *is* on both lists, so `'db_name_placeholder'=>'…'` does reach the control; if you see it have no effect, check the field isn't rendering as a `select`, where the attribute means nothing.)
- **`tvals`/`dvals`** = the true-values / display-values for a select/radio/checkbox (newline- or comma-separated, parallel lists). Field metadata is often seeded in the **`_fielddata`** table (`tablename`, `fieldname`, `inputtype`, `tvals`, `dvals`, `displayname`, `required`, `mask`, `defaultval`, `width`).
- **Do NOT re-specify in `fieldname_options` (or `databaseListRecords`) anything already defined in `_fielddata`.** `_fielddata` is the authoritative default source for `inputtype`, `tvals`/`dvals`, `required`, `mask`, `defaultval`, `displayname`, etc. — `addEditDBForm`/`databaseListRecords` read it automatically. Only pass a `*_options` key when the value must **differ** from the `_fielddata` default. If a field's metadata is wrong for *every* page, fix it once in `_fielddata` (which also fixes the WaSQL backend), not per-form.
- The `[fieldname]` bracket placeholder inside a `*_fields` view is replaced with that field's rendered input control.
- **Auto-hidden passthrough — don't hand-roll hidden fields.** Any dashless `$opts` key that is NOT one of the `-fields` (and isn't a `{field}_{option}`/`{field}_options` for a rendered field, a reserved `_`/`-` key, or blank) is emitted **automatically** as a hidden passthrough input so it posts with the form (`addEditDBForm` leftover-params loop, `database.php`). So to inject e.g. `site_id`, just pass `'site_id'=>intval(commonSiteId())` — **do NOT** also append `[site_id]` (or `,site_id`) to `-fields` **nor** set `'site_id_options'=>array('inputtype'=>'hidden')`. That pattern is redundant; the bare dashless value alone becomes the hidden field (numeric/array → `<input type="hidden">`, string → `<textarea>`). Works in edit mode too (stamps the passed value). This is the correct way to scope forms by ward.

### Form processing helpers
- `processFileUploads()` — call in the controller before saving; moves `$_FILES` to disk and populates `$_REQUEST['file_abspath']`.
- `getCSVFileContents($file)` — CSV file → array of record arrays. `arrays2CSV($recs[,'-noheader'=>1])` — inverse. `setFileContents($file,$data)` — write (auto-serializes arrays).
- `verifyForm` is NOT used — validation is `required` attributes plus manual controller checks.

### How `addEditDBForm` saves — the save is GLOBAL
`addEditDBForm` only *renders* the form. On submit it carries hidden `_table` + `_action` fields, and the framework saves the record at **bootstrap** (`index.php`: `if(isset($_REQUEST['_action'])){ processActions(); }`) — **before** the page controller runs and regardless of which page/route the form posts to. Consequences:
- **`-action` can point at ANY route.** The save happens no matter what; the action route's only job is to render the *response* (typically a refreshed list/section). You do NOT need to call `addEditDBForm` again on the receiving page to persist the edit.
- The idiomatic "edit → refresh a section" flow: point `-action` at a route that re-queries and re-renders, and `wacss.ajaxPost` the form into the div you want replaced. Use `-nocache` on that re-query so the just-saved row is included.
- **One form builder can serve multiple host pages** by swapping only `-action`/`-onsubmit` target. Pass a "source" hint (e.g. an extra passthru segment `/addedit/{id}/index`) into the builder and branch:
  ```php
  function fooAddedit($id,$src=''){
    $action="/t/1/portal/foo/list"; $target='portal_content';   // default host
    if(strtolower($src)=='index'){ $action="/t/1/index/foo"; $target='foo'; }  // alt host
    $opts=['-table'=>'foo','-action'=>$action,'-onsubmit'=>"return wacss.ajaxPost(this,'{$target}');", ...];
    if($id>0){$opts['_id']=$id;}
    return addEditDBForm($opts);
  }
  ```

### `addEditDBForm` add-mode gotchas: unset text field defaults to its OWN field name, and the Save button (not the form) sets `_action`
Two related traps hit together when scripting/automating an `addEditDBForm` add (e.g. driving it via CDP instead of a real click), or when a new add-form's defaults look wrong on load:
- **A text field with no explicit value in `$opts` for add-mode (`$id` absent/0) renders with its VALUE defaulting to its own field name** — a field called `name` with no `$opts['name']` set shows/submits the literal string `"name"`, not blank. The working fix already used by `pageSitesAddedit()` (sites page) is to explicitly blank every such field in the add-mode branch: `$opts['name']=''; $opts['label']='';` etc. Forgetting this on a NEW addEditDBForm (e.g. a from-scratch CRUD tab) silently saves garbage literal field-name strings on the first add.
- **The rendered Save button's `onclick` — not `-onsubmit`/`addEditDBForm` itself — sets the hidden `_action` field**: `<button type="submit" onclick="document.{formname}._action.value='Add';">`. `-onsubmit`'s `wacss.ajaxPost(this,'target')` posts whatever `_action` currently holds; a real click always sets it first, but anything that calls `wacss.ajaxPost(document.{formname},...)` directly (skipping the click) submits with `_action` empty and the global-save bootstrap hook silently does nothing — no error, just a 200 response re-rendering the (unchanged) list. When automating a save (tests, seed scripts), set `document.{formname}._action.value='Add';` (or `'Edit'`) immediately before the `ajaxPost` call.

### Bounding a numeric input: `maxlength` is IGNORED on `type="number"`
Neither attribute you'd reach for actually stops the typing on `<input type="number">`: **`maxlength` does not apply to number inputs at all** (the spec only honours it on text/search/url/tel/email/password), and **`max` only marks the field `:invalid`** — it never blocks a keystroke, and outside a submitted `<form>` (i.e. every `onchange="…ajax…"` field) nothing ever *reads* that invalid state. So a box with `min="0" max="9999.99"` still accepts `3313646464646464`, fires `onchange`, and posts it. Same for `pattern` and `step` — form-validation-only, dead markup on a standalone AJAX input.
- **To actually cap entry:** `type="text" inputmode="decimal" maxlength="7"`. `inputmode` keeps the numeric keypad on a phone, and `maxlength` is now honoured as you type. You lose the spinner arrows and `step`, which an inline grid field never wanted anyway.
- **Then re-add server-side what the number type was quietly giving you.** `min="0"` disappears with the type change, so a rule like "an estimate is never negative" has to move into the save path — where it belongs regardless, since the API never went through the input at all. Derive `maxlength` from the column (`strlen` of the column's max value) so the box and the validator can't drift apart.

### `data-*` conditional attributes (show / hide / require / read-only)
A field (or any wrapping element) can react to *other* fields' current values. Put the `data-*if` attribute on the element you want to affect; its value is a **condition string** referring to controls elsewhere in the same form. `wacss.formIsIfTrue(form, condition)` evaluates it. The family:

| attribute | effect when the condition is **true** |
|---|---|
| `data-displayif` | element is **shown** (else `display:none`); fires `data-ondisplay`/`data-onhide` on transitions |
| `data-hideif` | element is **hidden** (the inverse of displayif) |
| `data-readonlyif` | inputs inside get `readonly` (and clicks blocked) |
| `data-requiredif` | inputs inside get `required` |
| `data-blankif` | inputs inside are blanked (value stashed in `data-blankx`, restored when false) |
| `data-classif="cls:condition"` | toggles CSS class `cls` (first `:`-segment is the class, the rest is the condition) |

**Condition grammar** (`wacss.formIsIfTrue`):
- `field` — bare name, true when that control has a **non-empty value** (a "0" counts as present — it has length).
- `field:value` — true when the control's value **==** `value`.
- `field:a,b,c` — comma-separated list = **OR** (true if the value matches any).
- Combine multiple clauses with ` and ` / ` && ` (all must hold) **or** ` or ` / ` || ` (any). Don't mix and/or in one string.
- The referenced control must **exist in the form** and be literally named `field` (matched by `[name="field"]`, `[name="field[]"]`, or `[id="field"]`). Selects use the selected option's value; checkboxes/radios use checked values. If the named control is absent the clause is simply skipped (contributes nothing) — a frequent cause of "my condition never fires".
  - **Plain field:** for a normal field named `mood`, use `data-displayif="mood:curious"` — NOT `data>mood`.
  - **Nested JSON field (`scope>field`):** the `>` addresses an attribute *inside* a JSON field, because `addEditDBForm` names such inputs `scope>attr`. `data-displayif="data>send_email:Y"` needs an actual JSON field named `data` with sub-attribute `send_email`. For a flat field use the bare name.

**When conditions are evaluated (timing):**
- **On change** — the form must call `wacss.formChanged(this,event)` from its `onchange` (`addEditDBForm` wires this automatically; hand-built forms need `onchange="wacss.formChanged(this,event);"` or the fields never react to edits).
- **On load** — `wacss.initFormConditionals()` runs one `formChanged` pass per form so the **initial** state is correct without touching anything. It is invoked from `wacss.init()` (full-page forms) and from the end of `wacss.initWacssEdit()` (which is the `data-onload` handler on `addEditDBForm` output, so AJAX-inserted forms — e.g. a centerpop edit — get it too). The on-load pass passes no event, so it never flags the form/centerpop as "changed".
  - This is what makes the **`data-hideif="_id"` idiom** work: `addEditDBForm` emits a hidden `_id` field that is populated on **edit** and empty on **add**, so `data-hideif="_id"` hides a field (e.g. an auto-assigned `site_id`) on the edit form yet shows it when adding — correctly, right on load.
  - ⚠️ Historically these only toggled on the *first change*; if you're on a site whose `wacss.js`/`wacss.min.js` predates `initFormConditionals`, the initial state won't apply until the user edits a field — rebuild/redeploy the bundle to pick up the on-load behavior.
- `data-confirm` (confirm dialog), `data-format` (input formatting), `data-required` (conditional required), `data-toggle`/`data-target`, `data-tab`, `data-tip`/`data-tooltip`.

---

## Section-refresh pattern
To make a page section independently reloadable (e.g. after an inline edit), factor its markup into a **standalone `<view:section_name>` block** and render it two ways:
- **Full page:** inside the section wrapper, `<section id="thething"><?=renderView('section_name',$data,'var');?></section>`.
- **AJAX partial:** add a `PASSTHRU` case that re-fetches the data and `setView('section_name',1)` (the `1` clears other views so only the section renders inside the blank `/t/1/` template), then `return;`.
- **Refresh trigger:** any `wacss.nav`/`wacss.ajaxPost` to `/t/1/{page}/{that_action}` with `data-div` / target = the section wrapper's `id`. The section's `id` and the target div are the same thing — the AJAX response replaces the wrapper's inner HTML.
```php
// controller
case 'calendar':
    $events=indexUpcomingEvents();   // re-query (use -nocache)
    setView('calendar_section',1);
    return;
break;
```
```html
<!-- body: full-page render -->
<section id="calendar" class="section"><?=renderView('calendar_section',$events,'events');?></section>
<!-- body: the reusable block -->
<view:calendar_section> ... <?=renderEach('event_card',$events,'event');?> ... </view:calendar_section>
```
Combined with the global-save behavior above, an edit form whose `-action` is `/t/1/index/calendar` and whose `ajaxPost` target is `calendar` will save, then drop the freshly-rendered section back into `#calendar`.
- **Close the launching modal on success via `data-onload` on the reloaded partial.** When the form was opened in the `centerpop` modal, put `data-onload="wacss.centerpopClose();"` on the section's root element. `data-onload` runs after the AJAX content is injected, so on a *successful* save the fresh section re-renders (closing the modal); if the save fails the form re-renders instead (modal stays open). This beats calling close in the form's `onsubmit`, which would close the modal before knowing the result.
  - **Rule of thumb: put `data-onload="wacss.centerpopClose();"` on the POST-SUBMIT refresh response** — the section/grid the form reloads into (`ajaxPost` target) — **NOT on the form's own open-response.** ⚠️ If you put it on the add/edit form's own root, the modal closes itself the instant it opens. `wacss.centerpopClose()` is a safe no-op when no centerpop is open, so the same refreshed section can carry this attribute and be reused for non-modal refreshes (tab switch, delete) without harm.
  - **Point the form's `ajaxPost` target at the *scoped inner* refresh div, NOT the whole tab container.** If a list/section's CSS/JS is scoped to a wrapper id (e.g. `#thing_list .foo{...}`) and you refresh by replacing the *outer* container, the re-rendered markup lands **outside** that id, so scoped rules stop applying (symptom: action icons collapsed, a sticky table lost stickiness after edit-submit). Target the inner scoped div (`wacss.ajaxPost(this,'thing_list')`) to keep scoped CSS working and preserve sibling controls.

### Kill the refresh flicker: point `setprocessing` at the button, not the section
**By default `wacss.ajaxGet(url,div)` replaces the TARGET div's markup with the spinner** (`params.setprocessing === undefined` → `responseObj.div.innerHTML = wacss.processing`, `wacss.js`). So a "Refresh" button over a list blanks the list you were reading and flicks it back — pure flicker on a fast response, and worse if the list is tall enough to move the page. Never hand-roll a skeleton into the target either; that is the same flicker with extra markup.

**Give the trigger an `id` and name it in `setprocessing`:**
```html
<button class="button is-small" type="button" id="refresh_assigned"
	onclick="pageRefreshPanel('assigned');">Refresh</button>
```
```js
// the spinner runs INSIDE the button; the panel keeps its content until the new content lands
wacss.ajaxGet('/t/1/index/panel/assigned', 'panel_assigned', {setprocessing:'refresh_assigned'});
```
`wacss` stashes that element's markup in `el.previous`, swaps in the spinner, and restores it when the response arrives — so the button reads as busy and re-enables itself with no extra code.

Three values, three meanings:

| `setprocessing` | Effect | Use for |
| --- | --- | --- |
| *(omitted)* | spinner replaces the **target div's** content | a target that is empty anyway, or a genuinely slow first load |
| `'<elementId>'` | spinner replaces **that element's** content, restored on return | any user-clicked refresh/submit — the button, a toolbar, a card header |
| `0` | **no spinner anywhere** | a refresh the user did not ask for (a stat row re-read after a write) — otherwise the numbers flash away under them |

Also accepts the aliases `centerpop_processing` / `centerpop1_processing` … which map to the matching `wacss_centerpop*_processing` ids. On a **form**, the same thing is read off the form itself — `data-setprocessing="<elementId>"` (or a hidden input named `setprocessing`) — so `wacss.ajaxPost` can show the spinner in the Save button rather than wiping the grid it is about to refresh.

### Prefer `data-onload` on a rendered element over `buildOnLoad()`
**When an element is already being drawn, attach load-time JS as a `data-onload` attribute on that element instead of emitting a separate `<?=buildOnLoad("…");?>` script block.** `data-onload` runs after the element (including AJAX-injected content) is inserted, keeps the behavior co-located with the markup it acts on, and avoids a stray trailing script island. Use `this` inside the attribute to reference the element itself.
```html
<!-- preferred: behavior lives on the form being drawn -->
<form name="jiraform" data-onload="wacss.initTabs();if(this.comment){this.comment.focus();}" …>…</form>
<!-- avoid: a separate script island doing the same thing -->
<form name="jiraform" …>…</form>
<?=buildOnLoad("wacss.initTabs();document.jiraform.comment.focus();");?>
```
Reserve `buildOnLoad()` for load-time JS that isn't tied to a specific element you're rendering (e.g. a one-off `window.history.pushState(...)`, or initializing something global). If the natural host element doesn't exist yet, add a minimal wrapper `<div data-onload="…">` rather than a bare script block.

## Read-only detail popup (centerpop)
To let a value/row **open a modal showing more detail** (a drill-down list, a query result, a record's full data) — no form, just display — pair a `wacss.nav` link with the self-creating `centerpop` div and an AJAX partial that renders a functions-built HTML string. This is the read-only sibling of the CRUD-tab pattern (which puts a *form* in the centerpop).
- **Link:** `data-nav="/t/1/{page}/{action}"` + `data-div="centerpop"` + `onclick="return wacss.nav(this);"`. Add **`data-title="…"`** — `wacss.nav` reads it off the anchor and passes it to `wacss.createCenterpop` as the modal's title bar; the modal self-provides its ✕ close, so a pure viewer needs **no** `centerpopClose` wiring (that's only for closing after a successful *form* submit — see Section-refresh).
- **⚠️ Titling the modal a FORM posts into is a different attribute — `data-title` on the `<form>`.** `wacss.ajaxPost(frm,'centerpop')` resolves the title as `frm.title.value || frm.dataset.title || 'Information'`, so a form whose response opens/replaces the centerpop needs **`data-title="…"` on the `<form>` tag** (or a field literally named `title`). A hidden **`cp_title`** field does nothing here — that's the *legacy* `ajaxSubmitForm`/`form.js` mechanism (`theform.cp_title.value`), and mixing them up silently leaves the modal titled **"Information"**.
- **Controller:** one PASSTHRU case per popup → `setView('pop_x',1); return;` (the blank `/t/1/` template makes it chrome-less).
- **Body:** a `<view:pop_x>` block that just echoes a **functions** helper returning the HTML string. Build the table/markup in the model so the view has no brace-spanning `<?php ?>` islands (CLAUDE.md gotcha #1). The helper is where the query lives — `dbQueryResults('conn',$sql)` for a named connection, `getDBRecords(...)` for the site DB — and it returns an error/empty-state notification string when there are no rows.
- **Make the trigger conditional:** only wrap the value in the link when there's something to show (e.g. count > 0); otherwise render the plain value with no link.
```php
// functions: a reusable link helper + a conditional trigger
function xPopLink($nav,$title,$label){
  return '<a href="#" data-nav="'.$nav.'" data-div="centerpop" data-title="'.encodeHtml($title).'"'
    .' onclick="return wacss.nav(this);">'.$label.'</a>';
}
$locksVal = $n>0 ? xPopLink('/t/1/index/pglocks','Sessions Waiting on Locks',$n) : $n; // link only when non-zero
// controller:  case 'pglocks': setView('pop_pglocks',1); return;
// body:        <view:pop_pglocks><?=xLocksHtml();?></view:pop_pglocks>   (xLocksHtml() runs the query, returns a table string)
```

---

## CRUD-tab pattern
The **preferred structure for any admin/config screen that manages one or more tables in tabs** (list rows, click to add/edit in a modal, save-and-refresh). Keeps the controller a pure router and puts each table's list/form in its own named views + functions. Build every new such tab this way.

**Controller = generic router by view name** (each route just `setView`s a block and `return`s):
```php
switch(strtolower($PASSTHRU[0])){
  case 'locations': case 'ou': case 'emails': case 'groups':   // one case per tab
    switch(strtolower($PASSTHRU[1])){
      case 'list':    setView("{$PASSTHRU[0]}_{$PASSTHRU[1]}",1); return;   // {tab}_list
      case 'addedit': $id=(integer)$PASSTHRU[2];
                      setView("{$PASSTHRU[0]}_{$PASSTHRU[1]}",1); return;   // {tab}_addedit
    }
    setView($PASSTHRU[0],1); return;                                       // {tab} (list wrapper)
  default: setView('default'); break;                                     // full page
}
```

**Functions = per-tab trio** (`functions` field): `manage{Tab}List()` → `databaseListRecords`, optional `manage{Tab}ListExtra()` → a `-results_eval` that builds friendly/combined display columns, and `manage{Tab}Addedit($id)` → `addEditDBForm`. The **whole row** opens the edit modal, and search/paging + the add/edit submit all target the inner `{tab}_list` div:
```php
function manageThingsList(){
  return databaseListRecords(array(
    '-table'=>'st_things','-tableclass'=>'table bordered striped is-hoverable',
    '-listfields'=>'_id,active,mapfield,label',      // computed cols allowed (built in ListExtra)
    '-results_eval'=>'manageThingsListExtra',
    '-nocache'=>1,'active_checkmark'=>1,'-order'=>'is_default desc,name',
    '-action'=>'/t/1/manage/things/list',            // search/paging posts here...
    '-onsubmit'=>"return wacss.pagingSubmit(this,'things_list');",   // ...and refreshes the div
    '-tr_data-nav'=>"/t/1/manage/things/addedit/%_id%",             // whole-row → centerpop edit
    '-tr_data-div'=>'centerpop','-tr_onclick'=>"return wacss.nav(this);",
    '-tr_data-title'=>"Edit Thing %_id%"
  ));
}
function manageThingsAddedit($id=0){
  $opts=array('-table'=>'st_things','-style_all'=>'width:100%',
    '-action'=>'/t/1/manage/things/list',                          // save → re-render the list
    '-onsubmit'=>"return wacss.ajaxPost(this,'things_list');",
    '-fields'=>getView('things_addedit_fields'),                   // layout view (see getView gotcha)
    'active_options'=>array('inputtype'=>'checkbox','tvals'=>1,'dvals'=>'&nbsp;'));
  if($id>0){$opts['_id']=$id;}
  return addEditDBForm($opts);
}
```

**Views = four named blocks per tab** (`body` field):
```html
<view:things>                                        <!-- list wrapper: New button + list div -->
  <div class="align-right"><a class="button is-info is-outlined is-small" href="#"
    data-nav="/t/1/manage/things/addedit/0" data-div="centerpop" data-title="Add Thing"
    onclick="return wacss.nav(this);"><span class="icon-plus right5"></span> New Thing</a></div>
  <div id="things_list"><?=manageThingsList();?></div>
</view:things>
<view:things_list><div data-onload="wacss.centerpopClose();"><?=manageThingsList();?></div></view:things_list>
<view:things_addedit><?=manageThingsAddedit($id);?></view:things_addedit>
<view:things_addedit_fields> ...layout with [field] placeholders, root has data-onload="wacss.centerpopCenter();"... </view:things_addedit_fields>
```
Why it composes: the add/edit form posts to `/t/1/manage/things/list`, so the **global save** fires and the response re-renders `{tab}_list` into `#things_list`; because that list view's root carries `data-onload="wacss.centerpopClose();"`, a **successful** save closes the modal while a validation failure re-renders the form inside the still-open centerpop. The full page's `#manage_content` starts on the first tab with `<?=renderView('things');?>`. Tabs are `wacss.nav` + `data-tab="1"` anchors. **Do not** reintroduce a single shared `panel`/`form` view or per-cell `_onclick` edit handlers — the per-tab views + whole-row `-tr_data-nav` are the pattern.

---

## File upload processing (resize / convert / maxkb / reencode)
`commonProcessFileActions($name,$afile)` in `php/common.php` runs on every upload and chains four independent, opt-in steps in this order: **process → convert → resize → maxkb → reencode**. Each step reads its trigger from (in priority order) `$_REQUEST['{fieldname}_{step}']`, then `$_REQUEST['data-{step}']`, then `$CONFIG['{step}']` — so a form field can override a site-wide default set in `config.xml`. Every step that fires overwrites `$afile`/`$_REQUEST['{name}_size']`/`_abspath` and deletes the prior intermediate file.

- **`convert`** — format conversion via ImageMagick `convert`, e.g. `data-convert="heic-jpg,bmp-jpg"` (comma list of `from-to` pairs). This is generic — **`jpg-webp`/`png-webp` already works** with no extra code, as long as the server's ImageMagick was built with the WebP delegate (`convert -list format | grep -i webp`).
- **`resize`** — dimension-only, via `convert -thumbnail`, e.g. `data-resize="1920x1920>"`. Does **not** control file size — a correctly-dimensioned photo can still be several hundred KB.
- **`maxkb`** — targets a **byte-size budget** for lossy formats, e.g. `data-maxkb="300"` → ≤300KB. Only applies when the current file (after any resize/convert already ran) is `jpg`/`jpeg` or `webp`; other formats are left untouched with `{name}_maxkb_applied` set to an explanatory string instead of a command result. Implementation is ImageMagick's quality-search defines rather than a fixed `-quality` guess:
  - JPEG: `-define jpeg:extent=300kb`
  - WebP: `-define webp:target-size=307200` (bytes, not KB)
  Both make `convert` iterate quality settings until the encode fits the budget, so you get the best quality achievable for that size rather than an arbitrary fixed compression level. PNG has no lossy quality knob, so there's no equivalent — convert to `webp` first if a PNG needs a byte-size cap.
- **`reencode`** — audio/video via `ffmpeg`, e.g. `data-reencode="wav-mp3"`.

**SEO-driven web images:** to land an upload under a KB budget as WebP, combine resize + convert + maxkb on the field: `data-resize="1920x1920>" data-convert="jpg-webp,png-webp" data-maxkb="300"` — dimensions first, then format, then the byte-size search runs last against the already-converted file.

---

## Core helper traps (verified)
- **PHP close-tag truncation / unclosed-final-block — full mechanism in CLAUDE.md gotchas #2b/#2d** (truncation on any literal close tag incl. in strings/`//` comments; an unclosed final `<?php` gets echoed as literal text with no PHP error). Not covered there: **a `/* … */` BLOCK comment is safe** — the PHP lexer ignores a close tag inside one, which is why `@usage <?=pageFooBar($x);?>` lines in PHPDoc blocks are fine as-is; don't "fix" those. Copy-paste-safe XML declaration: `'<'.'?xml encoding="UTF-8" ?'.'>'`.
- **`verboseTime()` returns a TRAILING SPACE.** Harmless where HTML collapses whitespace (`verboseTime($s).' ago'`), but visible the moment punctuation follows — `'every '.verboseTime($s).'.'` renders `every 21 days .` — and it doubles up inside a `title`/`alt` attribute, where whitespace is *not* collapsed. `trim(verboseTime($s))` whenever you concatenate punctuation or build an attribute.
- **FIXED 2026-08-17 in core — `verboseTime($secs,1)` (notate mode) mis-rendered every unit boundary.** It is the ready-made `hh:mm:ss` formatter (`7`→`00:00:07`, `892`→`00:14:52`), but the internal comparisons were `>` where they should be `>=` (`php/common.php` ~26389-26409), so a value sitting exactly on a boundary stayed in the smaller unit: **`60`→`00:00:60`**, **`3600`→`00:60:00`**, **`86400`→`24:00:00`** instead of `1d 00:00:00`. All five comparisons are now `>=` (verified: `60`→`00:01:00`, `3600`→`01:00:00`, `86400`→`1d 00:00:00`). The same edit initialises `$months` — the notate branch's `if($months)` raised `Undefined variable $months` on PHP 8 for every duration under a month — and switches the verbose branch's `if(isset($months))` to `if($months)` so short durations don't start printing `0 months`. On a core older than that date, format durations yourself when the boundary matters: `sprintf('%02d:%02d:%02d',intval($s/3600),intval(($s%3600)/60),$s%60)`. That local form is still what you want when hours should **accumulate** (`25:00:00`) rather than roll into `1d 01:00:00` mid-column.
- **`wacss` is a `let`-scoped global, not a property of `window`.** `window.wacss` is `undefined` while the bare identifier `wacss` resolves normally — so inline `onclick="wacss.ajaxPost(...)"` works fine, but probing from the console or CDP `Runtime.evaluate` with `typeof (window.wacss||{}).ajaxPost` reports `undefined` and makes you think the method doesn't exist on that site's build. Probe with the **bare name** (`typeof wacss.ajaxPost`) or `Object.keys(wacss)`.
- **Don't trust a clicked submit button's `name`/`value` to reach the server** when the form is posted by `wacss.ajaxPost`/`ajaxSubmitForm` — whether the activating button is serialised depends on the serialiser. For a form with two actions (Save / Reset-to-default), use `type="button"` handlers that set a **hidden input** and then post, rather than two named submit buttons.
- **Hand-rolled HTML5 drag-and-drop: bind the handlers by DELEGATION, never as inline `ondragstart`/`ondrop` attributes.** The drag source is whichever descendant the user actually grabbed, and `<a href>` / `<img>` are **natively draggable** — so grabbing a card's title link starts a *link* drag whose `dragstart` fires on the anchor. An `ondragstart` on the card element never runs, your drag-id stays 0, and the drop silently bails: the lane highlights (that's just `dragover`), the card snaps back, and **no request is ever sent**, so there's no error anywhere to find. Fix: `document.addEventListener('dragstart', …)` + `e.target.closest('.card')`, and put `draggable="false"` on inner links. Delegation also survives the AJAX refresh that replaces the list.
  - **Testing this needs a *real* drag.** Calling `myDragStart(fakeEvent, el)` / `myDrop(fakeEvent, col)` directly passes happily while the feature is broken in the browser, because it skips the part that's actually wrong (which element becomes the drag source). Drive it over CDP instead: `Input.setInterceptDrags{enabled:true}`, `Input.dispatchMouseEvent` mousePressed + two mouseMoved to start the drag, catch the `Input.dragIntercepted` event for its `data`, then `Input.dispatchDragEvent` dragEnter/dragOver/drop at the target. Both the source and target must be **inside the viewport** — emulate a tall enough window or the press never starts a drag.
- **`truncateWords($str,$maxlen)`'s 2nd arg is a MAX CHARACTER count, not a word count** (despite the name — it just avoids cutting mid-word). `truncateWords($summary,6)` fits no whole word and returns an **empty string**, which looks like a missing value rather than a truncation bug. Pass a character budget (`truncateWords($summary,40)`).
  - **The 3rd arg (`$dots`) appends `...` UNCONDITIONALLY** — it only checks whether the result already ends in `.?!`, *not* whether anything was actually cut. So `truncateWords('Engineering home',40,1)` returns `Engineering home...`, and every short title in a breadcrumb or `<option>` grows a false ellipsis. Only pass `1` where the string is known to be long; otherwise wrap it: `if(commonStrlen($s)<=$max){return $s;} return truncateWords($s,$max).'…';`
- **`loadDBFunctions($name)` loads the page's `body` field, not `functions`** — see *Cross-page includes & shared functions* above. Helpers written into `functions` are silently never loaded.
- **Always pass a `title` when opening a centerpop, or the header literally reads "undefined".** `wacss.ajaxGet(url,'centerpop')` reaches `wacss.createCenterpop(params.title)` with `params` defaulted to `{}`, so the missing title is rendered as the string `undefined` in the popup's header bar. Pass it as the third argument: `wacss.ajaxGet(url,'centerpop',{title:'New sprint'})`. (`wacss.ajaxPost` is safer — it falls back to the form's `data-title` and then to `'Information'` — but set `data-title` anyway.) Since the header now carries the title, drop any duplicate heading from the partial you inject.
- **`setView('partial',1)` on a page whose body has no `<view:partial>` renders an EMPTY STRING — silently.** No warning, no error, HTTP 200. The AJAX target div is simply blanked, which reads as "the action deleted my content" or "the server returned nothing". Easy to hit when you add AJAX routes to a page that previously only had full-page views. If an AJAX route comes back empty, check the **body has the view you selected** before debugging the controller or the SQL.
- **A page's own `functions` field is in scope for THAT PAGE ONLY.** The moment a second page needs a helper it must move to the shared `functions_common`-style page — and be **deleted from the original**, or the page that loads both fatals on `Cannot redeclare`. Worth knowing because of how the failure presents: the fatal usually happens inside an **AJAX partial**, so it never reaches the browser as a visible error page — `wacss` injects the `PHP Uncaught Error` block straight into your target div, which typically reads as "the feature silently did nothing" or "my list disappeared". When an AJAX action mysteriously blanks its target, dump that div's `innerHTML` before theorising.
- **`getDBRecords`/`getDBRecord` cache by `sha1(dbname+query)` for the life of the request.** Any query whose result changes when re-run with *identical SQL* must pass `'-nocache'=>1` — most importantly `select LAST_INSERT_ID()` and `select max(x)+1` inside a loop, which otherwise hand back the first call's value and quietly assign every row the same id/sequence number.
- **A failed DB write returns the database's message WITH THE SQL STATEMENT APPENDED — never concatenate it into a user-facing message.** `addDBRecord`/`editDBRecord` fail as `Out of range value for column 'story_points' at row 1: update my_table set story_points='546464…', _euser=2 where _id=48`. The habit of writing `'Unable to save: '.$ok` therefore hands whoever mistyped a value your table names, column names, WHERE clause and column types — and on a JSON API it ships them to any token holder. Return a fixed sentence and send the detail to **`debugValue($arr)`**, which is the right sink because `php/wasql.php` renders it **only when `isDBStage()` is true, on `localhost`, or with `?debug=1`** — so a developer still sees the cause and a production user never does. (The framework's own `wasqlDebug` block, which also carries the statement, is gated by exactly that check — which is why a DB error you can see on stage vanishes in production.)
- **A single boolean `inputtype=>'checkbox'` field needs `tvals`/`dvals` explicitly, and an empty-string `dvals` is NOT the same as a blank label.** `getDBFieldSelections()` (`database.php`) only builds the checkbox's option list when `tvals` is set at all — omit it and the function falls through to `return '';` (a bare string, not an array). *(Checked 2026-08-12: every current call site — `database.php`, `common.php` — already guards this with `is_array($selections['tvals'])`/`isset()`, so an unset `tvals` renders an empty option list rather than crashing; the "Uncaught TypeError" this bullet used to describe no longer reproduces on the current core. Leaving this bullet for the still-real part below.)* Once `tvals` is supplied, a **missing or empty-string `dvals`** doesn't render blank either — `!strlen($info['dvals'])` treats `''` as "not provided" and silently copies `dvals=$tvals`, so a plain `'tvals'=>'1','dvals'=>''` checkbox displays a literal **"1"** next to it (this is why `active_options` renders `[x] 1` by default whenever a table's boolean column has no `-fielddata` override). The fix for a bare checkbox with no visible value text is `'dvals'=>'&nbsp;'` (a real, non-empty string) — already the working incantation in the CRUD-tab recipe above (`active_options=>array('inputtype'=>'checkbox','tvals'=>1,'dvals'=>'&nbsp;')`), just without this explanation of why it has to be `&nbsp;` and not `''`. Verified adding `_users.show_homepage` to byuwards.
- **FIXED 2026-08-12 in core (`wacss.js`):** a `class="wacssedit"` textarea with no `id` attribute used to never sync its typed content back. `wacss.initWacssEdit()`'s auto-id fallback checked `if(undefined == list[i].id){list[i].id='wacssedit_'+…}` — but a DOM element's `.id` property is `''` (empty string) when the attribute is absent, never `undefined`, so the condition never fired and the textarea kept `id=""`. The editor div it built was then named `'_wacsseditor'` and tagged `data-editor=""`, so its `input` listener's `document.getElementById(this.dataset.editor)` looked up `document.getElementById('')` → `null`, and the "sync the contenteditable HTML back into the real textarea" step silently no-op'd — the field posted as an **empty string** every time, with nothing erroring and the toolbar appearing to work. The check is now `if(!list[i].id){…}`, so a hand-built `class="wacssedit"` textarea (not going through `addEditDBForm`, which always assigns an id) no longer needs an explicit `id` to work around it — though giving one is still harmless. Originally found building byuwards' Email Blast composer.
- **FIXED 2026-08-15 in core (`php/admin.php`):** a `case 'backup':` inserted into the middle of `admin.php`'s big shared-fallthrough `switch(strtolower($_REQUEST['_menu']))` list (both the AJAX and full-page copies of it) meant EVERY menu listed above it — `website_grader` among ~26 others — silently ran a full synchronous `mysqldump` backup and rendered the Backup/Restore page instead of the one actually requested. PHP `case` labels with no code between them fall through with no implicit `break`, so `case 'backup':`'s side effect (`$_REQUEST['func']="backup"; $_REQUEST['_menu']="backups";`) executed for every case stacked above it too. Symptom looked exactly like "the page hangs" (mysqldump of a real-sized DB takes real, blocking time inside the request) with no clue in the requested page's own code — the tell was that the *rendered* page was Backup/Restore, not the one in the URL. Verified by driving a CDP-attached Chrome tab and reading `document.body.innerText`/screenshot, not by reasoning about the PHP alone. Fix: give `case 'backup':`/`case 'backups':` their own `break`-terminated block, separate from the generic shared-render list, in both switch copies (`admin.php` has this pattern twice — once for `isAjax()`, once for the full page).
- **A long-running request can make an UNRELATED tab in the same browser session appear to hang, if `config.xml` has `database_sessions="0"`.** That leaves PHP on its default file-based session store, which holds an exclusive lock on the session file for a script's entire runtime (`session_start()` to script end). Any other request sharing that session cookie — including just loading a totally different page — blocks on the lock until the slow request finishes. Any endpoint that can legitimately run long (an external crawl, a big report, a bulk import) should `session_write_close()` before the slow part and `session_start()` again right before it needs to write `$_SESSION` — see `website_grader_controller.php`'s `case 'run':` for the pattern. Diagnose by checking `config.xml`'s `database_sessions` value and Apache's error log for worker threads that "failed to exit" during a restart (a sign something really was stuck server-side, not just slow to render).
- **FIXED 2026-08-12 in core (`php/user.php`):** `userLoginForm()`'s placeholder text used to be silently clobbered unless you passed `-username_title`/`-password_title`. A "backward compatibility" shim (right after the defaults-fill loop) unconditionally ran `if(isset($params['-username_title'])){$params['-username_text']=$params['-username_title'];}` — and `-username_title`/`-password_title` themselves defaulted to the literal strings `'loginform_username'`/`'loginform_password'` (the field **id**, not display text) in the `$defaults` array, contradicting that same function's own docblock (`No Default`). Since the defaults-fill loop set them before the shim ran, `isset()` was true either way, so the fields' placeholder always ended up showing `loginform_username`/`loginform_password` instead of the `-username_text`/`-password_text` actually configured (or the "Username"/"password" builtin default) — verified building the Idea Garden's topnav/login forms. The stray `'-username_title'=>'loginform_username'`/`'-password_title'=>'loginform_password'` defaults have been removed from `$defaults`, matching the documented "No Default" contract — the shim itself is untouched and still works correctly for callers who *do* pass `-username_title`/`-password_title` explicitly. Also still worth knowing: consider blanking `-password_post_class` (defaults to `icon-eye`, an unstyled show/hide toggle that renders as an empty bordered box on sites without that icon font loaded).

## Bulma / wacss layout traps (verified)
- **A `.column` width class does nothing unless the row is flex at that breakpoint.** Bulma's `.columns` is `display:block` **below tablet** — it only becomes flex from 769px up, or at every width if you add **`is-mobile`**. So `<div class="columns is-multiline"><div class="column is-6-mobile">` puts every tile on **its own line** on a phone (each one 50% wide, stacked) rather than two per row. The working combination for a wrapping tile grid is all three: **`columns is-multiline is-mobile`** on the row + a per-breakpoint width on each column (`is-6-mobile is-3-tablet` → 2 per row on a phone, 4 from tablet up).
  - The converse also bites: `columns is-multiline is-mobile` with **bare** `.column` children (no width class) gives equal-flex columns that **do not wrap** — with 7-8 tiles the last one is clipped at the box edge on a desktop. Bare `.column` is fine up to ~5 items; past that, size them.
- **Check how the site styles a bare HTML tag before using one inside a sentence.** Site bundles here set several inline-by-default tags to `display:block`, which silently breaks a paragraph into pieces: `<code>` inside a sentence renders as its own full-width band (add `display:inline` in the page `css`), and a `<a>` inside a table cell pushes an adjacent inline icon onto its own line (a `.mybox table td a{display:inline;}` rule in the page `css` is the fix). Same lesson as the `.title`/`.subtitle` `!important` trap in `CLAUDE.md`: when markup misbehaves, look at the bundle before adding your own layout.
- **`<progress class="progress is-small">` is the cheap in-table bar** — real Bulma, colour it with the same `is-danger`/`is-warning`/`is-success` modifier the rest of your states use, and give it a `min-width` or it collapses in a narrow column. Drop the whole cell on phones with `is-hidden-mobile` rather than letting a 6-column table squeeze.

## Chart.js (the `chartjs` extra)
The bundled chart library is **`/wfiles/js/extras/chart.min.js` — Chart.js v2.8.0** (use v2 option syntax: `options.legend`, `options.title`, `scales.yAxes:[{ticks:{beginAtZero,max}}]`, `cutoutPercentage`, `maintainAspectRatio`; NOT v3+). There is **no PHP charting engine** — rendering is client-side.

### The WaSQL way: the `chartjs` tag (start here)
Write a **`chartjs` tag** in the page body (or return one from a `functions` helper) and the framework does the rest. `commonProcessChartjsTags()` (`php/common.php`) runs in the render pipeline **after `evalPHP`**, so helper-returned tags are processed normally, and it runs for `/t/1/` AJAX partials too. It rewrites the tag into `<div data-behavior="chartjs" id="…">` + a hidden `#{id}_data` block, then `wacss` builds the Chart.js instance client-side and registers it as `wacss.chartjs[divId]`.

```html
<!-- SQL form: the framework runs the query. data-db picks the connection (default = site DB) -->
<chartjs data-type="bar" data-db="pg_warehouse" data-onclick="myChartClick"
    data-popurl="/t/1/status/day/{key}" class="my-chart-box">
    SELECT to_char(d,'YYYY-MM-DD') AS label, count(*) AS value, uname AS dataset FROM … GROUP BY 1,3
    <options>{"responsive":true,"maintainAspectRatio":false}</options>
</chartjs>
```
- **Query must return `label` and `value`**; an optional `dataset` column splits it into series, optional `color`/`bcolor` columns become the palette. Otherwise you get `<error>Query Must return label, and value</error>`.
- **`[key]` substitution:** any `[foo]` in the SQL is replaced with `$_REQUEST['foo']` before it runs.
- **`data-process="funcName"`** post-processes the recordset in PHP before it becomes chart data.
- **JSON form** (data already computed in PHP — `/proc` metrics, counters): give each `<dataset>` a JSON array and supply `<labels>` yourself, plus optional `<colors>`/`<bcolors>`/`<options>`:
  ```html
  <chartjs data-type="doughnut" class="my-chart-box">
      <dataset data-label="Memory" data-borderwidth="2">[3.7,121.6]</dataset>
      <labels>["Used","Available"]</labels>
      <colors>["#48c774","#eceffa"]</colors>
      <bcolors>["#ffffff","#ffffff"]</bcolors>
      <options>{…}</options>
  </chartjs>
  ```
- Every attribute on the tag lands on the generated div, which is also the **sized box** — put your height class there and set `maintainAspectRatio:false`.
- **⚠️ SQL left in the tag runs AFTER every PHP island on the page — so PHP can never see its result.** `commonProcessChartjsTags()` is a regex pass over the **already-rendered** HTML, which means a tag's query does not run until `evalPHP` has finished the whole body. Anything you want PHP to *say about* that query — how long it took, how many rows came back, "hide this box if the result is empty" — is impossible from a tag, because the island that would print it has already run. Symptom: a timing/count printed next to a chart always reads zero. **Fix: run the query yourself in `functions` and hand the tag the JSON form above.** It's the same query and the same chart, just executed before the title instead of after it — and it puts the data fetch in the model layer where it belongs. Two details to match so the chart looks unchanged: labels and series are ordered **first-seen** (order your `ORDER BY` accordingly), and the processor only assigns `data-backgroundcolor`/`data-bordercolor` per dataset when there is **more than one** series — a single series is deliberately left uncoloured so the client-side builder can colour each slice/bar from the palette. Its palette starts `rgba(255,159,64,0.4)` (orange), `rgba(75,192,192,0.4)` (teal), with `rgb(...)` at full opacity for the borders.
- A tag's SQL also runs **once per render, unconditionally** — a chart whose numbers the page already fetched for something else is a duplicate round trip, and an expensive function (`SELECT … FROM some_fn()`) is easy to call twice this way without noticing. Deriving the chart from data already in scope costs nothing.

### Click-through: `data-onclick`
`data-onclick="myFunc"` makes bars/slices clickable; wacss calls `window.myFunc(params)` with `params.xvalue` (the clicked **label**), `params.yvalue`, `params.dataset`, `params.parent_id` (the chart div's id — `params.parent`, the element, from the other builder), plus `color`/`x`/`y`. There is no per-point payload, so **carry the drill-down config in your own `data-*` attributes on the tag** and read them back off `parent_id`, and make the **label the key you need** (e.g. `YYYY-MM-DD`, or the raw role name — not a prettified version):
```js
function myChartClick(params){
    var div=document.getElementById(params.parent_id)||params.parent;
    var url=div.getAttribute('data-popurl').replace('{key}',encodeURIComponent(params.xvalue));
    return dstPop(url,'',div.getAttribute('data-popdiv'));  // see wacss.nav(el,{nav,div,title})
}
```
Hit-testing uses **`'point'` (intersect) mode** — the click must land on the bar itself, not just its column.

**Opening a centerpop from JS** (no anchor in the markup): `wacss.nav(el,{nav:url,div:'centerpop',title:t})` takes everything from `opts`, so append a **bare** throwaway `<a>` and pass that (nav forwards every `data-*` on the element as a request parameter, so don't hand it the chart div). Targets `centerpop`, `centerpop1`, `centerpop2`, `centerpop3` **stack**, so a popup's chart can drill into `centerpop1` and leave the first popup open behind it.

### Refreshing a whole board of charts (AJAX section-refresh, no blank flash)
Reload just the chart section into its div and let the injected partial rebuild the charts from its own `data-onload`. Two things make it clean:
- **Give every chart an explicit `data-id`.** Without it the processor generates a random id per render, so each refresh registers a *new* `wacss.chartjs[…]` entry and the old ones are never reclaimed. Stable ids also sidestep the identical-tag id collision entirely.
- **Destroy orphans in the `data-onload`, not before the request** — that's what keeps the previous render on screen while the new numbers are in flight (the anti-pattern is blanking the cards and flashing a skeleton). After the swap, the old canvases are detached but still referenced, so sweep by DOM-connectedness:
```js
for(var id in wacss.chartjs){                       // in the injected partial's data-onload
    var c=wacss.chartjs[id];
    if(!c || (c.canvas && document.body.contains(c.canvas))){continue;}
    try{c.destroy();}catch(e){}
    delete wacss.chartjs[id];
}
wacss.initChartJs();                                // idempotent per div; builds only the new ones
```
Fire the refresh with `wacss.ajaxGet('/t/1/page/section','divid',{setprocessing:0})` (`setprocessing:0` stops the spinner replacing the div's contents) and dim the div with a class while it's in flight.

### How the two builders interact (not a bug — worth knowing)
`wacss.initChartJs()` first calls `initChartJsBehavior()` (which builds every `[data-behavior="chartjs"]` element and marks it `data-initialized`), then runs its own richer builder over `div.chartjs, div[data-behavior="chartjs"]`. That second pass is **not** a duplicate build: when `wacss.chartjs[id]` already exists and the div has a canvas it takes an **update path** — it rewrites the existing chart's config from the `_data` block and calls `.update()`. So charts stay at one canvas, re-calling `initChartJs()` is how you refresh a chart in place (`wacss.initChartJs('mychartid')` targets one), and `initChartJs` owns the extra types/features (`gauge`, `data-recs`, per-point colours). Putting both `class="chartjs"` and `data-behavior="chartjs"` on one container is harmless — `querySelectorAll` returns each element once.

### Gotchas (all verified the hard way)
- **FIXED 2026-08-17 in core — a `null` in a dataset's data array used to kill the whole chart.** Both builders loop the points doing `dataset.data[ds].pointbackgroundcolor` (`wacss.js` ~4573 / ~4737). On a number that's a harmless `undefined`; on `null` it threw `TypeError: Cannot read properties of null (reading 'pointBackgroundColor')`, the build aborted and you got an **empty sized box with a canvas in it** — no visible error. Both loops now skip any point that isn't an object, so padding a series with `null` to line it up with the labels (the obvious way to stop a burndown's "actual" line at today) is safe. On an older core, send a **shorter data array** than the labels array instead — Chart.js plots what it's given and stops.
- **Per-point colour keys are case-insensitive as of 2026-08-17.** The two loops disagreed: the update path read `pointBackgroundColor`/`backgroundColor` (camel), the build path read `pointbackgroundcolor`/`backgroundcolor` (lower), so per-point colours in your JSON applied on the first build and vanished on refresh, or vice versa, depending on how you cased the key. Both now accept either.
- **FIXED 2026-08-12 in core (`wacss.js` ~4550):** on the update path, `data-bordercolor` used to be ignored — every LINE chart came out default grey. When `wacss.chartjs[id]` already exists (i.e. `initChartJsBehavior` built it first, which is the normal sequence), `initChartJs` takes an update path that rebuilt each dataset as `{backgroundColor, type, data, fill, pointBackgroundColor:[], pointBorderColor:[]}` — **no `borderColor`**. Bar/doughnut were unaffected (they're coloured by `backgroundColor`), but lines lost their colour entirely on every refresh. The update-path dataset now also reads `borderColor: udatasets[ud].getAttribute('data-bordercolor') || udatasets[ud].getAttribute('data-borderColor')`, matching the other builder. (A separate typo in the same area — `dataset.pointBorderBolor[ds]=…` silently no-op'ing per-point border-color overrides — was fixed to `pointBorderColor` at the same time.)
- **FIXED 2026-08-17 in core — the literal tag name inside an HTML comment used to break the page.** The processors are regexes over the rendered HTML, so `<!-- the <chartjs> tag builds the dashboard -->` was read as a real opening tag and swallowed everything up to the next real closing tag — your markup *and* the real chart — into a hidden `_data` div. `commonProcessChartjsTags` and `commonProcessDBListRecordsTags` now mask HTML comments (`commonMaskHtmlComments`/`commonUnmaskHtmlComments`) before matching, and their tag regexes were tightened so `<chartjsfoo>` can't match `<chartjs`. Note the trigger was always the **literal `<chartjs`** — prose mentioning "the chartjs tag" with no angle bracket was never affected. On an older core, or in any tag processor added since, use a PHP comment (`<?php /* … */ ?>`) instead.
- **An `<options>` block replaces wacss's default options wholesale.** `initChartJsBehavior`'s defaults set `events:false` and `tooltips.enabled:false`; `initChartJs`'s second pass then replaces the whole object with either your `<options>` or `{responsive:true}` — which is why `line`/`bar`/`horizontalbar`/`doughnut` charts have working tooltips even with no options at all. **`pie` is the exception**: it is built once by the first pass and its update branch never re-options it, so it kept `events:false` for its whole life. **Fixed 2026-08-17** — pie is now interactive by default, and the rule lives in `wacss.chartjsWantsTooltips(el)`: **`data-tooltips="1"`** turns hover tooltips on for any chart type, **`data-tooltips="0"`** gives back a display-only pie. It is applied to the *defaults* only, so an `<options>` block you supply is still used verbatim — if you supply options and want hover, set `tooltips` yourself. `data-stacked` and `data-beginatzero` mutate `options` *after* yours is parsed but are safe as of **2026-08-17** — both now route through `wacss.chartjsAxes(options)`, which creates `scales.xAxes[0]`/`yAxes[0]`/`.ticks` only if missing and leaves everything you supplied intact. Before that date `data-beginatzero` could never work at all (four inverted `undefined ==` guards plus an out-of-scope `lconfig` reference) and `data-stacked` threw a `TypeError` on any chart with no `<options>`. Still avoid `data-title` / `data-legenddisplay` / `data-render` alongside your own options — those remain unguarded (`data-render` assumes `options.plugins.labels` exists).
- **A chart in a fixed-height box fills it as of 2026-08-17 — on older cores, set `maintainAspectRatio:false` yourself.** Nothing in core used to set `maintainAspectRatio`, so Chart.js applied its own default of `true` and held a 2:1 ratio regardless of your `style="height:260px"`. Both builders now set it to `false` when the chart div carries an **explicit inline height** and your `<options>` didn't already specify it. Note the gate: a chart sized by a CSS class or by its parent is deliberately untouched, because `maintainAspectRatio:false` on an auto-height container collapses the canvas — so if you size charts by class, keep setting the option yourself. Make the option set **complete** (`responsive`, `maintainAspectRatio`, `legend`, `tooltips`, `scales`, …), not a patch. Everything is a plain PHP array up to the `json_encode`, so per-chart tweaks are just array writes on the shared builder's return value (`$o['scales']['xAxes'][0]['display']=false;`).
- **Data labels come from `chartjs-plugin-datalabels`, and it draws by default.** The plugin registers itself globally with `display:true`, so labels appear on every chart whether or not you configure them; turn them off per chart with `plugins:{datalabels:{display:false}}` in your `<options>`. Note that core's own default option set configures **`plugins.labels`** (`wacss.js` ~4054) — that is the key for a *different* plugin, `chartjs-plugin-labels`, which is **not shipped in this repo**. That block is dead config; don't copy it into your options expecting it to do anything.
- **⚠ A dataset-level `data-backgroundcolor` SILENTLY SUPPRESSES the `<colors>` array on a doughnut/pie.** wacss reads the dataset attribute first and only falls back to `<colors>` when it is absent (`wacss.js` ~4877: `udatasets[ud].getAttribute('data-backgroundColor') || ((type=='doughnut'||type=='pie') ? colors : colors[ud])`). Emit both — a habit that's harmless on bar/line, where one colour per dataset is correct — and every slice **and every legend swatch** comes out the same colour: a solid ring with a legend that looks broken. Note this is NOT the 2026-07-28 core solid-ring bug (that one was core assigning a single colour itself); this is the caller handing wacss a single colour and winning. Fix: for `doughnut`/`pie` emit only `<colors>`, never `data-backgroundcolor` on the dataset.
- **One colour per *dataset*, in every form — you cannot colour bars individually.** `<dataset data-backgroundcolor="#hex">` is read with `getAttribute`, so it is a string; the update path's per-point `data[ds].backgroundColor` branch mutates that string and silently does nothing. Only `doughnut`/`pie` take the whole `<colors>` array (one colour per slice). For a bar chart where each bar wants its own colour, either switch form or hand-roll the canvas.
- **Colours in the single-query SQL form:** with more than one `dataset` value, core assigns each series a colour from a fixed **15-entry** rgba palette, so you can't get semantic colours (green success / red error) that way — use per-`<dataset>` `data-backgroundcolor` (JSON form, or SQL inside each `<dataset>`). The palette **wraps** as of 2026-08-17 (`$colors[$i % count($colors)]`, `php/common.php` ~8358); before that a query returning 16+ distinct `dataset` values emitted PHP 8 `Undefined array key` warnings and left the 16th series onward uncoloured, which is why older pages cap categorical queries at `LIMIT 15`.
- **AJAX-refreshed sections:** only `wacss.initOnloads()` runs after an AJAX injection, not `wacss.init()` — so re-run the chart init from the injected partial's `data-onload` (`data-onload="wacss.initChartJs();"`). Builds are idempotent per div, so this is safe on every refresh.
- **Two identical chart tags used to collapse onto one DOM id.** The processor looped over each matched tag and replaced it with `str_replace($chartjs_tag,…)`, which rewrites *every* identical occurrence — so two tags whose markup matched character-for-character (easy: two cards showing the same numbers) produced two divs sharing one generated id, and the later loop pass found nothing left to replace. Fixed 2026-07-28 with `commonReplaceFirst()`. Symptom to recognise on an older core: `document.querySelectorAll('#'+id)` returns two elements, and anything that builds **by id** (e.g. `wacss.initChartJsBehavior(divId)`) draws a second canvas into the first div's box, overflowing onto whatever follows. Build with the **no-argument** call instead — it walks elements and honours `data-initialized`, so it is immune.
- **Deploying to a site with an older core** — four bugs were fixed 2026-07-28; a site whose `php/common.php` / `wacss.js` predate that will still show them, and `wacss.min.js` must be re-minified for the JS fixes to take effect:
  - doughnut/pie built by `initChartJs` (either path) got **one** `backgroundColor` instead of the per-slice array → **solid rings**. Bar/line were unaffected.
  - the multi-dataset SQL form died on **PHP 8**: `json_encode(array_values($labels,JSON_…))` passed the flags as a second argument to `array_values()` → `ArgumentCountError` (`php/common.php` 8410/8413/8419).
  - `chart.min.js` lazy-load race: `wacss.loadScript` is async and both initializers bailed on the same call with no retry, so charts could silently never draw. Now they re-try every 250ms up to `wacss.chartjsWaitsMax`. Belt-and-braces on an old core: load it with a plain synchronous `<script src="/wfiles/js/extras/chart.min.js">` in the page body (framework asset — fine to include; the page's own css/js fields are the auto-injected ones).
  - identical tags sharing a generated id (see the bullet above).
  - The `dblistrecords` tag processor had the same whole-string `str_replace` and so had the same shared-id/skipped-tag behaviour with byte-identical tags — **fixed 2026-08-12** by switching it to `commonReplaceFirst()` too (`php/common.php` ~8639), same as `chartjs`. Any *other* tag processor found with the same whole-string `str_replace` pattern still needs the same one-line fix applied when someone hits it.
  - Page-level workaround if you can't update core: from a `data-onload`, deferred a tick so it runs after `wacss.init()`, destroy `wacss.chartjs[div.id]`, remove the div's canvases, `removeAttribute('data-initialized')`, then `wacss.initChartJsBehavior(div.id)` — that builder honours `<colors>`. Flag each div as done so refreshes only build new ones.
- **Fully hand-rolled alternative** (what to do if you want nothing to do with the above): give canvases a non-triggering class, stash the v2 config in a `data-chart` attribute via `htmlspecialchars(json_encode($cfg),ENT_QUOTES)`, and in your own JS destroy prior instances then `new Chart(cv.getContext('2d'),cfg)`. Race-free and idempotent, but you give up `data-onclick`, `data-db` and the SQL forms.

## Machine-facing pages (REST / JSON-RPC / MCP endpoints)

A WaSQL page makes a perfectly good API endpoint — routing, auth and DB access are already there — but four framework behaviours will bite you, and each one produces a response that *looks* fine to a browser and is unusable to a client. All verified building page-based `/api` and `/mcp` endpoints (2026-08-06).

- **Auth is already solved — do not build tokens.** `php/user.php`'s bootstrap accepts **`WaSQL_auth: {token}`**, **`Authorization: Bearer {token}`** and `_auth=`, decodes via `userDecodeAuthCode()` and populates `$USER` *before your controller runs*. So `isUser()`, `$USER` and every permission helper behave exactly as they do in a browser session, and the request genuinely **is** that user — no second authorization model to get wrong. (This is how `wamcp` and `dasql` authenticate.) The trade-off to state plainly: a WaSQL token is full user access — no scopes, no expiry, and revoking means disabling the account.
- **The controller MUST `exit` after emitting output.** Otherwise the view and template render after it and wrap your JSON in page chrome; every client then fails to parse a 200. Same rule for non-JSON output: a page action that streams an image must `exit` immediately after the bytes.
- **The page's `body` must be EMPTY, not a stub.** A body containing anything (even `stub body`) is emitted *before* the controller's output, so the response begins with that text and is no longer valid JSON. This collides with the PostEdit empty-field rule (`postedit.md`) — stub the body to get the file mirrored, then set it back to `''` in the DB once the page works.
- **WaSQL answers HTTP 200 by default.** Nothing sets a status for you, so `http_response_code(4xx)` has to be called explicitly or every error is a 200 carrying an error-shaped body — which most HTTP clients treat as success.
- **Reaching shared code:** `loadDBFunctions($name,$field)` (see *Cross-page includes*) is what lets several endpoint pages share one core — e.g. an `mcp` page loading the `api` page's `functions`. A shared library does not need a page of its own, which matters because PostEdit will not mirror a page created mid-session.
- **If you are speaking MCP**, copy the response shapes from `php/admin/wamcp_functions.php` rather than the spec: `initialize` → `protocolVersion`/`capabilities`/`serverInfo`/`instructions`; `notifications/initialized` → literally `{}`; a tool result is `array('content'=>array(array('type'=>'text','text'=>…)),'isError'=>bool)`. Answer auth failures with **HTTP 200 + a JSON-RPC error**, never 401 — a 401 makes MCP clients start an OAuth discovery flow and buries the real cause.

## Server / system health in PHP
The web PHP runs **as apache on the site's own Linux host**, so for a health/monitoring page you can read metrics locally without SSH:
- **Direct `/proc` reads** (most reliable): `file_get_contents('/proc/meminfo')` (MemTotal/MemAvailable, KB), `/proc/loadavg` (1/5/15m + running procs), `/proc/uptime` (secs since boot), `/proc/cpuinfo` (`model name`, count `^processor` lines). Plus native `disk_free_space($path)` / `disk_total_space($path)`.
- **Framework helper:** `loadExtras('system'); $info=getServerInfoLinux();` returns `load_averages`, `uptime`/`uptime_verbose`, `cpu_info`, `memory_usage` (with shell fallbacks). `php/extras/system.php`.
- **Shell when needed:** `cmdResults($cmd)` → `['stdout','stderr','rtncode','runtime']` (via `proc_open`).
- **Caveat:** apache often can't stat a DB's data dir (e.g. postgres `0700 /var/lib/pgsql`), so `disk_*_space` there returns false — fall back to reporting logical size (`pg_database_size`, `SUM(data_length+index_length)`).
- **DB server health:** MySQL uptime/threads via `performance_schema.global_status` (or `SHOW GLOBAL STATUS LIKE 'Uptime'` → `$rec['value']`), `@@max_connections`. PostgreSQL via a named connection: `dbGetRecord('conn', "SELECT pg_postmaster_start_time(), (SELECT count(*) FROM pg_stat_activity) …")`.

### A REMOTE host's OS metrics, when it exposes `file_fdw` probe tables
Some Postgres deployments publish `public.system_*` foreign tables (`file_fdw` over a `PROGRAM` option) so a plain `SELECT` returns that **database host's** `/proc`, `df`, `ps`, `ss`, `systemctl` and `journalctl` state — no SSH and no agent. Where present the set runs to roughly 17 tables; a *Server Health* panel on a dashboard page is the typical consumer. Four rules generalise to any such table set, and they shape the code more than the SQL does:
- **Every `SELECT` forks a command on the far host and there is no stored history.** So nothing can be a trend line without your own sampler, and any panel must be honest that a counter is a since-boot total, not a rate. Roughly half the columns are counters (`system_stat_cpu`, `system_diskstats`, `system_net_dev`, `system_vmstat`); plotting them raw draws a line climbing forever.
- **Probe cost is per-SELECT, and the round trip dominates it** (~265ms each on a LAN vs ~1-25ms of actual shell work). Batch aggressively: pull each table into a **CTE once** and emit metrics as a `UNION ALL` of `(metric, value::text)` rows, and flatten the multi-row tables onto one common `(kind,label,v1,v2,v3,txt)` shape in a second query. Four queries beat fourteen by a second of page load. Give each UNION a **fixed literal first branch** (`SELECT 'probe' AS metric, 'ok' AS value`) — it pins the column names and types no matter which branches you composed in, and its arrival is your proof the query ran.
- **The table set differs per host, so ask the catalog first** and compose from what exists (`SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_name LIKE 'system%'` — match on `system%` and filter the underscore in PHP rather than fighting LIKE escaping in a heredoc). One missing table otherwise fails a whole batched query.
- **An empty result is ambiguous and must never render green.** The probes that shell out to optional tooling return **no rows** rather than erroring when the tool is missing or unreadable by the `postgres` OS user, so "0 failed units" and "could not look" are indistinguishable from SQL. Render those as neutral, say "returned nothing", and list them in a footnote.

---

## SFTP — which extra to use (verified 2026-08)
Four SFTP extras exist and they are **not interchangeable**; picking wrong wastes an afternoon on an error that looks like bad credentials but is not. Decide by what the *server* runs, not by which file looks newest:
- **`sftp3.php`** (phpseclib 3.0.56, vendored at `php/extras/phpseclib3/` with a hand-written PSR-4 `autoload.php` — no composer). **Default choice.** Pure PHP: needs no extension, no root, and — the reason it exists — its crypto is **not governed by system crypto-policies**. Functions follow the `-param` convention and return an **error STRING** on failure, never throw: `sftp3Connect`, `sftp3PutFile`, `sftp3GetFile`, `sftp3ListFiles`, `sftp3DeleteFile`, `sftp3HostKeyFingerprint`. **⚠️ Test failures with `sftp3IsError($rtn)`, not `is_string($rtn)`** — `sftp3GetFile` returns the local path (a string) on *success*, so `is_string()` reports a successful download as a failure. Error strings always start `sftp3<Function> Error: `, which is what `sftp3IsError()` matches. Pass `-remote_dir` + plain filenames (it `chdir`s first, so names read like WinSCP's). `-part` uploads to `name.part` then renames, so a polling consumer never sees a partial file. `-fingerprint` verifies the host key **before** the password is sent. Tester: `php php/extras/sftp3Test.php --host=… --user=… --hostkey-only` prints the fingerprint to pin.
- **`nativeSFTP.php`** — object API (`new NativeSFTP($cfg)` → `connect`/`chdir`/`rawlist`/`upload`/`exec`), **throws exceptions**, and since 2026-08 it **auto-selects a driver**: ext-ssh2 when loaded (that code path is untouched), else the same vendored phpseclib 3. So it now works on servers without the extension, and code written against it keeps running unchanged. Force with `'driver'=>'ssh2'|'phpseclib'`, read back via `getDriver()`. Two driver differences: `pwd()` shells out under ssh2 (fails on SFTP-only servers) but uses the SFTP protocol under phpseclib; a **relative path stays relative to the login dir under phpseclib, while ssh2 forces every path absolute from `/`**. Use this when you want the class API or `chdir`-style stateful sessions; use `sftp3.php` for one-shot pushes. To install the extension anyway, pin **`ssh2-1.4.1`** — bare `pecl install ssh2` resolves to the PHP 7-only 1.3.1 and won't build on PHP 8.
- **`sftp.php`** (the phpseclib **0.3.x** copy in `php/extras/phpseclib`, headers say "PHP versions 4 and 5"). Legacy only. Its kex/hostkey list is `diffie-hellman-group1/14-sha1` + `ssh-rsa`/`ssh-dss`, so it fails against most current servers. Don't build on it; don't confuse the directory with `phpseclib3/`.
- **php-curl `sftp://`** — tempting (RHEL's curl *is* built with sftp, so it costs nothing to install) but has **no per-request kex/hostkey control**, so it is unfixable when negotiation fails. Diagnostic script kept at `php/extras/sftpCurlTest.php`.

**⚠️ RHEL 9 + a legacy appliance = a handshake failure that is not your password.** RHEL 9's DEFAULT crypto policy sets `rh-allow-sha1-signatures = no`, which disables SHA-1 signature verification **inside OpenSSL**. Anything verifying host keys through OpenSSL — the OpenSSH CLI, libssh, and therefore php-curl — then refuses a server whose only host key is `ssh-rsa` (RSA/SHA-1), and **`-o HostKeyAlgorithms=+ssh-rsa` does not help**: OpenSSH drops an algorithm it cannot verify with, so you get the same `no matching host key type found. Their offer: ssh-rsa`. php-curl reports it as `curl_errno` **2, "Failure establishing ssh session"** — before authentication, so credentials are never tried. WinSCP connects fine from Windows, which makes the endpoint look healthy and sends you hunting the wrong thing.
- **Confirm in one line:** `ssh-keyscan -p 22 host` shows what the server offers (keyscan doesn't validate, so it prints a key `ssh` will refuse); `ssh -vv` says `kex: host key algorithm: (no match)`.
- **Fix:** use `sftp3.php` — phpseclib verifies RSA/SHA-1 with its own bignum math and plain `hash('sha1')` digests, which the policy doesn't govern. Do **not** reach for `update-crypto-policies --set DEFAULT:SHA1`: it re-enables SHA-1 signatures host-wide for every SSH and TLS consumer to accommodate one vendor's appliance. It is useful *temporarily* to prove credentials and paths by hand (`sftp -P 22 user@host`, then `update-crypto-policies --set DEFAULT` to revert).
- phpseclib wants **gmp or bcmath**; without either, key exchange runs on pure-PHP bignums and the handshake gets slow. `sftp3Test.php` reports which engine is in use.

## Database backup & restore (`dumpDB` / `sh/db_import.sh`)
Every admin backup path (`/admin/export?func=db`, the Tables page's *Backup now*, `php/admin.php`'s `backup` action) calls **`dumpDB($table='')`** in `php/database.php` — it shells `mysqldump` (overridable via the `backup_command` config), writes to **`sh/backups/{dbname}__{Y-m-d_H-i-s}.sql`**, and returns `['afile','command','result','success'|'error']`. Two platform facts matter: on **Linux** the command is piped `| gzip -9 > file` (so backups are **`.sql.gz`**), while on **Windows** `proc_open` runs with `bypass_shell`, so `>`/`|` would be passed to mysqldump as literal arguments — the dump is captured from stdout and written by PHP instead.
- **⚠️ A MySQL 8 dump will not import into MySQL 5.7 / MariaDB.** MySQL 8's default table collation is `utf8mb4_0900_ai_ci`, mysqldump stamps it into every `CREATE TABLE`, and an older server stops on the first one: `ERROR 1273 (HY000) at line 25: Unknown collation: 'utf8mb4_0900_ai_ci'`. **No mysqldump flag fixes this** — `--compatible` doesn't touch collations. It is a text rewrite (`utf8mb4_0900_*` → `utf8mb4_unicode_ci`, and `utf8mb3` → `utf8` for the 8.0-renamed charset).
- **Prefer to downgrade at the destination**, which is the side that knows what it supports: **`sh/db_import.sh {dbname} {file.sql|.sql.gz}`** queries `information_schema.collations` on the target and pipes the dump through `sed` only for the names that server lacks (it also picks the `CREATE DATABASE` charset to match, reads `.gz` directly, and passes the password via `MYSQL_PWD`).
- **Portable dumps at the source** are opt-in: set config **`backup_portable=1`** and `dumpDB` applies the same rewrite (`dumpDBPortableSQL()` in-process on Windows, `dumpDBPortableFilter()` as a pipeline `sed` on Linux). Off by default because it also changes the collation when restoring back onto MySQL 8. The local dev box runs **MySQL 9.2**, so every dump it produces needs this to reach the older stage servers.
- **⚠️ A new row in `php/schema/config.csv` does NOT appear on existing sites.** `schema.php`'s `case '_config'` loads that CSV only when it **creates** the table, so a setting added later is invisible in the admin Config page and stays unset. `configCheckSchema()` (run by the config page's default view) now upserts the missing rows with an empty `current_value`; until a site loads that page, set the value in **`config.xml`** instead. Precedence: `config.xml` first, then `_config` rows **override** it (`php/database.php:48`) — but only rows whose `current_value` is non-empty.

## `_triggers` — DB record lifecycle hooks
A table can have `_triggers/{table}/{table}._triggers.functions.{id}.php` defining specially-named functions the framework auto-calls around writes and reads. **Naming: `{table}{Event}`** where Event ∈ `AddBefore`/`AddSuccess`/`AddFailure`, `EditBefore`/`EditSuccess`/`EditFailure`, `DeleteBefore`/`DeleteSuccess`/`DeleteFailure`, `GetRecord`. Special `_users` hooks: `_usersLogin`/`_usersLogout`. **Each function receives an array (`$params`) and MUST return it.**
```php
function calendar_eventsAddBefore($params=array()){
    $params['content']=encodeJson(commonParseIni($params['content_ini']));
    return $params;   // MUST return
}
```

## `_prompts` — saved query/code console (NOT AI prompts)
`_prompts/{name}/{name}._prompts.body.{id}.{ext}` are the saved bodies of WaSQL's built-in query/code runner console: `sql_prompt_{dbname}` (SQL for a named DB connection), `php_prompt` / `code_prompt` (PHP/Python scratch snippets). They are NOT LLM prompts.

## `cron_*` pages
Scheduled tasks are pages named `cron_*` whose logic lives in the **`body`** field (`.php`), run headless via a `/t/1/{page}` URL. Pattern:
```php
$ok=commonCronLogInit('cron_sendmail');
$recs=getDBRecords(['-table'=>'sendmail','sent'=>0,'-limit'=>60,'-nocache'=>1]);
if(!isset($recs[0]['_id'])){ commonCronLog('nothing to do'); return; }
foreach($recs as $rec){
    $ok=sendMail(decodeJson($rec['sendopts']));
    editDBRecordById('sendmail',$rec['_id'],['sent'=>1,'sent_date'=>'NOW()']);
    commonCronLog($ok);
}
```
A `_cron` table tracks jobs (`run_cmd` URL, `run_every`, `cron_pid`, `running`); `cron_check_crons` is a watchdog that resets dead jobs.

---

## A reserved word as a column name makes a table unreadable (verified 2026-08-18)

A column called **`cursor`** made `getDBRecord()`/`getDBRecords()` return an **empty
array** for every row in the table, while `select * from that_table` over raw SQL read
the rows perfectly.

`CURSOR` is a MySQL reserved word, and WaSQL's read path enumerates column names into the
SELECT **without backticking them**, so the generated statement is a syntax error. The
error goes to the DB layer and the helper hands back an empty array — which is
indistinguishable from "no rows".

That is the expensive part: it presents as *"the record does not exist"*, so you go
looking at permissions, `-nocache`, and whether the insert committed, and none of that is
where the problem is.

**Rule: never use a reserved word as a column name.** The ones most likely to be reached
for in app schema: `cursor`, `order`, `group`, `key`, `range`, `rank`, `system`, `status`
is fine but `condition`, `interval`, `lead`, `lag`, `usage`, `read`, `write` are not.
When a table reads as empty through the helpers but fine over raw SQL, check the column
names before anything else.

Live example: an import-jobs table whose `cursor` column had to be renamed `page_cursor` before the helpers would read it.

## A page's `css`/`js` are page-scoped — shared styles belong in the template

The same rule as `functions`, and just as easy to forget: a page's **`css` and `js` fields
only load on that page**. Reuse another page's markup and you get the markup with none of
its styling — no error, no console warning, just an unstyled block.

It presents as "the CSS didn't load", which sends you to `?_menu=clearmin` and the `w_min`
bundle. The bundle is fine; the rules were never in it for *this* page.

**Where things belong:**

| shared by | put it in |
|---|---|
| one page | that page's `css` / `js` / `functions` |
| two or more pages | the **template's** `css` / `js`, or `functions_common` |

**Move it, don't copy it.** Two copies of a CSS rule drift; two copies of a *function* are
fatal — `Cannot redeclare` the moment one page loads both. When a shared helper moves to
`functions_common`, delete it from the page it came from in the same edit.

**Take the media queries with it.** A responsive rule left behind in the page that
originally owned the layout is a bug waiting for the next page that reuses the markup.

After editing a template's `css`/`js`, clear `css_min`/`js_min` on the template row and
hit `?_menu=clearmin`, or the old bundle keeps serving.

Live example: a new page reused an existing page's list markup and rendered unstyled
until that list/card/chip/tile layout moved out of the page `css` and into the shared
template.

## Calling a JSON REST API — use `postJSON()`; its limit is POST-only (verified 2026-08-18)

**`postJSON($url,$json,$params)` sends a raw JSON body.** It is the right first choice and
it does more than it looks like: it wraps `postBody()`, which handles content-type,
encoding, timeouts, SSL, cookies, redirects and **HTTP basic auth**:

```php
$res=postJSON($url,encodeJson($body),array(
    '-authuser'         => $user,          // basic auth, no manual base64 needed
    '-authpass'         => $token,
    '-headers'          => array('Accept: application/json'),
    '-timeout'          => 60,
    '-timeout_connect'  => 15
));
// $res['body'] is the response; $res['headers'] the parsed response headers
```

There is also `postXML()` for the same job with an XML body.

**Do NOT reach for `postURL()` for a JSON API** — that one builds its payload with
`http_build_query()` on the dashless params and has no raw-body option, so it can only
send form-encoded data.

### The real limitation: one verb

`postBody()` sets `CURLOPT_POST` unconditionally and never sets `CURLOPT_CUSTOMREQUEST`,
so **`postJSON()` can only POST**. That is fine for a webhook or a one-way push, and not
enough for a REST *client*, which needs:

| verb | typical use |
|---|---|
| GET | every read |
| PUT | Jira issue update |
| PATCH | ServiceNow record update |
| DELETE | removing a sub-resource |

So for a full client, call curl directly from the page layer rather than patching core:

```php
$ch=curl_init($url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_CUSTOMREQUEST,strtoupper($method));   // the reason for going direct
curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,15);
curl_setopt($ch,CURLOPT_TIMEOUT,60);      // never let a remote API hang a cron
if($body!==null){curl_setopt($ch,CURLOPT_POSTFIELDS,encodeJson($body));}
$raw=curl_exec($ch);
$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
curl_close($ch);
```

Two things worth copying with it:

- **Always set both timeouts.** A remote API with no timeout is how one slow vendor turns
  a five-minute cron slot into a hung PHP process.
- **Translate the vendor's error body into one sentence.** Every API reports errors
  differently (Jira `errorMessages[]`/`errors{}`, ServiceNow `error.message`, most others
  `message`), and surfacing a bare `HTTP 400` sends somebody to read a log they cannot
  reach. Write one `…SyncHttpError()` helper that maps each vendor's error shape to a
  sentence, and call it from every request.

A `-method` option on `postBody()` would remove the need for any of this — it is a
one-line change to core and a deliberate decision for the framework developer, not
something to bundle into site work.


## PHPDoc / JSDoc convention
**Every function gets a docblock — PHP in `functions`, JS in the `js` field.** A `/** ... */` block above the signature: a one-line summary, then `@param {type} $name description` for each argument and `@return {type} description`. WaSQL harvests these to build its documentation, and they make the fields self-explanatory. Prefer this over loose `//` comments for describing what a function does (inline `//` notes inside the body are still fine). Keep section-separator comments (`//--Members--`) — put the docblock below the separator, above the function. Mark an internal helper to leave OUT of the generated manual with an `@exclude` tag (`/** @exclude - excluded from the manual */`).
```php
/**
 * Build the "Stay Connected" cards for the current ward.
 *
 * @param array $bishopric Bishopric records (from commonGetBishopric()); used to find the bishop's email.
 * @return array Connect-card arrays; empty when nothing is configured.
 */
function indexGetSiteConnections($bishopric){ ... }
```
```js
/**
 * Add dropped/selected files to the pending upload and re-render the list.
 *
 * @param {FileList} files Files from the input change or a drop event.
 * @return {void}
 */
function indexActivityAddFiles(files){ ... }
```

---

## Common scenarios (copy-paste starters)

**Create a new page:** create the page record via the WaSQL web interface → "work on {alias} {page}" (`php workon.php {alias} {page}`, which starts the watcher via `php postedit/postedit.php {alias}` — see `postedit.md`; do NOT use `p.bat`, it's unreliable when launched via `Start-Process`) → edit the generated files in your IDE → changes auto-sync back to the DB.

**Display data:**
```php
// controller:
$users = getDBRecords(['-table' => 'users', '-limit' => 10]);
// body:
<?=renderEach('user_card', $users, 'user');?>
<view:user_card><div class="user"><?=encodeHtml($user['name']);?></div></view:user_card>
```

**Form (recommended — `addEditDBForm` auto-processes):**
```php
// functions:
function pageAddEdit($id = 0){
    $opts = ['-table'=>'users','-fields'=>getView('form_fields'),
             'name_options'=>['required'=>1], 'email_options'=>['required'=>1,'inputtype'=>'email']];
    if($id > 0){ $opts['_id'] = $id; }
    return addEditDBForm($opts);
}
// body:  <view:form><?=pageAddEdit($id);?></view:form>   (build forms from the body — see getView gotcha)
```

**Form (manual processing):**
```php
// controller:
if($_REQUEST['action'] == 'save_user'){
    $result = addDBRecord(['-table'=>'users','name'=>$_REQUEST['name'],'email'=>$_REQUEST['email']]);
    if(isNum($result)){ $success='Saved'; setView('success'); }
    else{ $error=$result; setView('error'); }
}
```

**Logged-in check:**
```php
global $USER;
if(!isUser()){ setView('login_required',1); return; }
$userName = $USER['firstname'];
```
- **`$USER` already IS the current user's full `_users` record** — every column is on it (`_id`, `username`, `email`, `firstname`, `lastname`, `utype`, `perms`, custom cols…). **Never re-fetch the logged-in user** with `getDBRecordById('_users',$USER['_id'])` / `getDBRecord(['-table'=>'_users','_id'=>$USER['_id']])` — read the field straight off `$USER`.
- **JSON columns decode into a sibling `*_ex` array on the same record** (`perms`→`perms_ex`, `data`→`data_ex`, `meta`→`meta_ex`). Prefer the `_ex` variant; if it isn't populated yet, decode once and cache it back onto the global so later reads reuse it — don't build a separate static cache or query the DB:
  ```php
  function commonUserPerms(){
    if(!isUser()){return array();}
    global $USER;
    if(!isset($USER['perms_ex'])){ $USER['perms_ex']=decodeJSON($USER['perms']); }
    return $USER['perms_ex'];
  }
  ```
  Writing back to a JSON column still targets the raw column (`perms`), not `perms_ex` (see CLAUDE.md gotcha #7).

**URL routing:**
```php
global $PASSTHRU;
switch(strtolower($PASSTHRU[0])){
    case 'edit': $rec = getDBRecord(['-table'=>'items','_id'=>$PASSTHRU[1]]); setView('edit_form',1); break;
    case 'list': $items = getDBRecords(['-table'=>'items']); setView('item_list',1); break;
    default:     setView('default'); break;
}
```

**AJAX nav:**
```html
<a href="#" data-div="content" data-nav="/t/1/page/list" onclick="return wacss.nav(this);">Load List</a>
<div id="content"></div>
```
