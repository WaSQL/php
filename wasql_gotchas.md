# WaSQL gotchas and limits (field notes)

Core-level traps hit while building and running the dexPDQ prod site (bd-dis-pdqprd01) through go-live, 2026-11.

- **Scope:** behaviour of shared WaSQL core and tooling (php/, wamcp, dasql/sqlprompt, PostEdit, wacss), not site code.
- **Tracker:** the "Tracker" column points at `wasql_bugs.db` (the `/bugs` page). "—" means not recorded there. Record a real defect in the tracker, not in this file.
- **Related docs:** the always-loaded rules are in `CLAUDE.md`, and the per-feature detail is in `wasql_reference.md`.

## Data access and connections
| # | Gotcha | What to do | Tracker |
|---|---|---|---|
| 1 | A **failed SELECT through `dbQueryResults()`/`db*` returns an empty array**, which looks the same as "0 rows". | Check `dbLastError()` right after every read. | #1 (open) |
| 2 | **Persistent connections keep session state.** A `SET` (TimeZone, enable_*, search_path…) outlives the request, and `current_setting('TimeZone')` can be anything. The Live dashboard showed 0s until every query used an explicit `AT TIME ZONE 'America/Denver'`. | Never rely on session settings. Write the time zone into the SQL, and don't issue a bare `SET` on a shared connection. | — |
| 3 | **DB writes return the new id, or an error string** on failure. | Test with `isNum()`, never truthiness. | #3 (open) |
| 4 | **Snowflake over PHP ODBC:** the default `SQL_CUR_USE_ODBC` cursor segfaults php-fpm at shutdown (a 503), and unsized VARCHAR is reported as 512 MB (out of memory). | On the `<database>` tag: `cursor="SQL_CUR_USE_DRIVER"`, and `;default_varchar_size=16384` in `connect`. | #65 (complete) |
| 5 | **Snowflake `PUT`/`COPY INTO` don't work over ODBC** as a multi-statement batch. | Use the **snowsql CLI**. It needs a writable config dir for the web user (`/usr/share/httpd/.snowsql`). | — |
| 6 | **Snowflake stage names resolve against the connection's default schema,** so an unqualified `@stage` landed in the wrong schema (DTX). | Always fully qualify: `db.schema.stage`. | — |
| 7 | **`postURL()` sets no timeout by default.** A `_pages` trigger that GETs a URL on every save hung for ~300s. | **Fixed 2026-11-09:** `postURL`, `postBody`, `getRemoteFile`, `getRemoteImage` and `getRemoteFileInfo` now default to a 30s **connect** timeout (`$CONFIG['curl_connect_timeout']` overrides; a per-call `-timeout_connect` still wins). The ~300s hang was curl's built-in connect timeout. Total timeouts are unchanged, so still pass `-timeout` for a chatty trigger. | #78 (complete; related #7) |
| 8 | **`isDBTable($t,1)`: the force flag is ignored,** so a table created in this request still reads as missing. | Use a forced `getDBTables()` list instead. | #68 (fixed 2026-11-09; was ignored on MySQL) |
| 9 | **`getCachedValue()` with a closure producer never caches,** because the key comes from `spl_object_hash`. | Fixed for closures in page `functions` (keyed on file+line+use vars). Closures in controller/body islands still don't cache: put them in `functions` or pass a string. | #69 (complete) |
| 33 | **A column named with a reserved word** (`cursor`, `rank`, `order`, …) made every `getDBRecord(s)` read of the table return nothing, and `addDBRecord`/`editDBRecord` fail. | **Fixed 2026-11-09** on MySQL: real columns are backtick-quoted in generated selects, filters, inserts and updates. Raw SQL you write yourself still needs the quoting. | #2 (complete) |

## cTREE (via WaSQL Groovy)
| # | Gotcha | What to do | Tracker |
|---|---|---|---|
| 10 | **cTREE queries have no enforced timeout,** and `kill <pid>` in SQL Prompt is a no-op. Only an Apache restart stops a runaway query. | Keep queries narrow (filter on indexed columns first). Groovy's `querytimeout` defaults to 600s; set it on the tag. | #40, #43 (wontfix) |
| 11 | **After a credentials change, cTREE says "Invalid user id"** until Groovy is reloaded (`dbuser` beats the connect UID). | **Fixed 2026-11-09:** on a login-rejected error, `dbGroovyQueryResults` reloads Groovy and retries once, if `config.xml` changed since the last such reload. Otherwise hit Groovy `/reload`. | #81 (complete) |
| 12 | **A table can be visible in the catalog yet unreadable:** wamcp `dexpdq-ctree` shows `odhdb`/`hodhdb` in `systables` and returns their columns, but every SELECT, even `count(*)`, comes back "No rows". | Treat "No rows" on `count(*)` as an error. Compare with a sibling table (`odh1db` works). | — |

## wamcp and dasql (SQL tooling)
| # | Gotcha | What to do | Tracker |
|---|---|---|---|
| 13 | **wamcp `query` reports some errors as "Query OK. No rows returned."** (a bad column name, an unreadable cTREE table). | Fixed: `query` now returns "Query failed (N ms): <driver error>" when an error was recorded. Still treat a `count(*)` with no row as suspect on cTREE (see #12). | #70 (complete) |
| 14 | **A wamcp query that times out keeps running on the server.** On prod PDQ it blocked a purge, and later a nested-loop plan ran until cancelled. | **Fixed 2026-11-09:** `query` sets a server-side statement timeout (default 120s; db attr `wamcp_timeout`, config `wamcp_query_timeout`, or the `timeout` argument) on Postgres and MySQL/MariaDB, so the database cancels it. Remote (dasql-type) databases are not covered. Still run plain `EXPLAIN` before `EXPLAIN ANALYZE`. | #75 (complete) |
| 15 | **wamcp doesn't report elapsed time.** | Fixed: every `query` result now shows elapsed time. | #70 (complete) |
| 16 | **dasql/sqlprompt runs `stripslashes()`** on the SQL, so every `\` disappears (it broke a HANA regex), and it **splits on every `;`**, even inside comments. | **Fixed 2026-11-09:** the server only runs `stripslashes()` when the client sends `bs_escaped=1` (updated `dasql.py` doubles every backslash and sends it, so it also works against older cores), and the run-at-cursor split ignores `;` inside quotes, comments and `$$` bodies. Until a server has the new core, keep doubling backslashes in the web SQL Prompt. Update `dasql.py` along with the core: the old one doubles on `php>`/`cmd>` without the flag. | #71 (complete) |

## Cron and scripts
| # | Gotcha | What to do | Tracker |
|---|---|---|---|
| 17 | **Cron fails with "missing dbname for localhost"** unless config.xml maps localhost. | **Fixed 2026-11-09:** on the CLI, `config.php` falls back to the first host with a `dbname` (prefers `cron="1"`) instead of aborting. Older cores still need `<host name="localhost" sameas="{site host}" cron="0" />`. | #80 (complete) |
| 18 | **`cron.php` lowercases `run_cmd`,** so the passthru loses its case. | Use lowercase routes. | #4 (wontfix) |
| 19 | **Time limits differ by entry point:** Apache `ProxyTimeout`, the web-only `set_time_limit` in a cron page, and the cTREE `-timeout`. A browser "run now" and a cron run die at different times. | Set all three explicitly for long jobs. | #41 (complete) |
| 32 | **An `Error` thrown by page code (undefined function such as `gmp_init()` with no php-gmp, TypeError, ParseError) escaped `evalPHP` uncaught** and killed the request: exit 255 from cron/CLI, "Server Unavailable" on the web. Since 2026-11-08 `evalPHP` catches `\Throwable`, renders the error in place of the failing block, `error_log`s it, and keeps going. Side effect: a cron run that hits one now **exits 0**. | Don't judge cron success by exit code alone; look for "PHP Uncaught Error" in the output. Check a new box has the extensions a site needs (`php -m`). | #67 (complete) |

## Views, templates, JS and CSS
| # | Gotcha | What to do | Tracker |
|---|---|---|---|
| 20 | **`renderViewIf($cond,$view,$params,$opts)` / `renderViewIfElse($cond,$view,$viewelse,$params,$opts)`:** the alias name goes in `$opts` (a string works), not in a 3rd/4th "name" slot. | Match the signature exactly. | — |
| 21 | **The renderView/renderEach alias can collide with evalPHP's own locals** (`$g`, `$e`, …). | Use descriptive aliases. | #53 (fixed) |
| 22 | **An inline `<script>` in AJAX/centerpop-loaded content never runs.** | Use `data-onload="fn(this)"` and carry data in the DOM. | CLAUDE.md #14 |
| 23 | **Charts in AJAX-loaded content aren't built,** because `wacss.init()` doesn't run on AJAX loads. Repeated refreshes also leak Chart.js instances. | **Fixed 2026-11-09:** every AJAX load now destroys charts whose canvas left the page, then builds any unbuilt chart in the new content. A fragment's own `data-onload` `initChartJs()` still works (the second pass just updates). On sites still running an old `wacss.min.js`, use the `data-onload` workaround. | #72 (complete) |
| 24 | **A chartjs `<options>` block replaces wacss's default chart options entirely** (no merge). | Send a complete option set (legend, scales, tooltips, elements). | — |
| 25 | **`wacss.nav` with `data-tab` only clears the active class among siblings** of the same parent. Two menu boxes leave two items highlighted. | **Fixed 2026-11-09:** give the tabs a shared name, `data-tab="mainmenu"`, and every `[data-tab="mainmenu"]` on the page acts as one group. `data-tab="1"` (any number) keeps the siblings-only behavior. | #73 (complete) |
| 26 | **The right-align class is `wacss_align-right`.** A bare `align-right` is legacy (wasql.css) and isn't in wacss_v2. | Use `wacss_*` classes in new markup. | — |
| 27 | **A literal PHP close tag anywhere in a page field truncates the rest of the field,** and an unterminated final `<?php` in a body is echoed as text. | See CLAUDE.md 2b/2d. | #59 (controller only) |

## PostEdit
| # | Gotcha | What to do | Tracker |
|---|---|---|---|
| 28 | **PostEdit only mirrors fields whose `_fielddata` inputtype is textarea** (or a blob/json/text type). `scripts.body` needed `_fielddata` rows. | Add `_fielddata` before expecting a mirror. | — |
| 29 | **A field that was empty at mirror time never syncs back,** even after you create its file. | **Fixed 2026-11-09:** a watcher started from the current `postedit.php` adopts the file if it is named `{name}.{table}.{field}.{id}.{ext}` and another field of that record is mirrored. A page created mid-session still needs a watcher restart. | #82 (complete) |
| 30 | **Starting postedit.php overwrites every mirrored file from the DB.** | Back up a mirror that holds unsynced work before restarting the watcher. | — |
| 31 | **css/js edits don't show until the `w_min` bundle is rebuilt.** | **Fixed 2026-11-09:** bundle URLs now carry `?v=<token>`, and any `_pages`/`_templates` save (PostEdit included) or `?_menu=clearmin` changes the token, so browsers fetch the new bundle. Sites on an older core still need `clearmin` plus a cache-disabled reload. | #77 (complete) |

## Not WaSQL core, but they bite on WaSQL + PDQ
- **PDQ `message` is large TOASTed jsonb:** every `message->>'x'` unpacks the whole document again (4 keys × 200k rows ≈ 4.5s). Read each message once with `CROSS JOIN LATERAL jsonb_to_record(message) AS x(...)`, or use an `OFFSET 0` subquery fence. One pass cut the Live dashboard refresh from ~20s to an estimated ~5s.
- **Postgres estimates a `jsonb_to_record`-derived set at 1 row,** so a join against it becomes a nested loop that never finishes. Stack the sets with `UNION ALL` and `GROUP BY` instead of joining.

- **PDQ `pdq.fn_topic_get` swallows every error** (`exception when others then raise notice; return;`) and returns zero rows. A consumer then logs "Nothing to do" / sets `no_more` exactly as if the topic were empty. When a consumer reports `no_more` with rows past its `q_offset`, call it read-only first: `SELECT * FROM pdq.fn_topic_get(p_user_name=>'script_<id>', p_topic_id=><tid>, p_row_count=>5, p_preview=>1)`. Then compare the unread rows' `entry_date` with the consumer's last run time. On 2026-11-09 the "stalled" MATDOC/EDIDC consumers had simply run a minute before SLT delivered.
- **MySQL `max_connections` (default 151) can be exhausted by concurrent crons:** each running cron holds a sleeping connection. Symptom: the whole site shows "Failed to connect. DBType: mysqli" and crons stop. Fix live on MySQL 8 with `SET PERSIST max_connections = 500` (survives restarts, no my.cnf edit). Log in as root with `-p` to get the reserved admin connection.
