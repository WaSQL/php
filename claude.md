# WaSQL Framework Instructions for Claude

**Pronunciation:** when speaking aloud (voice/TTS output), "WaSQL" is pronounced "waskul" — not spelled out letter-by-letter and not "wa-sequel". (The logo at wasql.com prints "(wäh-skul)" as the intended sound, but plain "waskul" is what actually comes out closest via SAPI.) See `claudetalk.md` for the actual `Convert-ForSpeech` implementation.

WaSQL is a **database-driven** PHP framework: page logic lives in `_pages` table records (fields `name`, `body`, `functions`, `controller`, `js`, `css`), not files — a MySQL dump is the entire site. This file is the **always-loaded core**: the rules and gotchas that apply to nearly every task. Deep, feature-specific detail lives in **`wasql_reference.md`** (read the section that matches your task, including its "Common scenarios" copy-paste starters). Other docs: `architecture.md` (full technical), `workon.md` (the `workon.php`/`workon.py` one-command session-startup script — same behavior, use whichever interpreter, `php` or `python`, is on the machine), `postedit.md` (working on a live PostEdit-mirrored site), `quick_reference.md` (examples — note its `isLoggedIn`/`hasPermission` are WRONG; see Corrections). `ai_patterns.md` was removed as redundant/inaccurate — use `wasql_reference.md`'s "Common scenarios" instead.

## ⚠️ CRITICAL: Developer Preferences
**NEVER `git commit` or `git push`** — the developer handles ALL commits/pushes manually. Make file changes as requested, but never commit them, even when asked to "commit this."

**NEVER modify core platform / framework files when building a site or app.** The WaSQL core under `php/` (`php/common.php`, `php/database.php`, `php/user.php`, `php/extras/**`) is shared by hundreds of sites — a change for one site can break all of them. Keep ALL site/app logic in the **database**: a page's `functions`/`controller`/`body`, shared helpers in **`functions_common`** (loaded via `loadDBFunctions`), or a **template's `functions`**. If a feature seems to *need* a core hook, do it in the DB layer instead and surface the limitation to the developer. Genuine framework enhancements are a separate, deliberate decision the developer makes — never bundle them into site work.

**NEVER use the backend admin UI (`/php/admin.php`) for anything** — not browsing tables, not editing/deleting records, not even cleaning up a test row you created. Only interact with a site through its normal front-end pages/routes. Two exceptions: `?_menu=clearmin` (busts the `w_min` minify-bundle cache after a `css`/`js` edit — see `postedit.md`) and `?_menu=synchronize` (navigate here when you finish a task — see `workon.md`). If a task seems to need direct table/record admin, stop and ask the developer to do it themselves.

**NEVER edit minified assets** (`*.min.css`, `*.min.js`) — edit the human-readable source only (e.g. `wacss.css`, not `wacss.min.css`). The developer rebuilds minified bundles with their own build step manually.

**Ask before doing post-change QA — and don't screenshot by default at all.** Once file edits are made (PostEdit sync, a local `work on wasql` edit, anything), **ask the developer whether they want to verify it themselves or have you drive the browser and screenshot/test it** — don't auto-screenshot after every change, and don't even screenshot at session startup (see "ACT, don't narrate" below — the browser launch itself is the act; the developer is already looking at it). Capturing a screenshot and reading it back is the most token-expensive step in this whole workflow, so don't spend it without asking first; a quick "want me to verify this, or will you check it?" is cheap by comparison. Skip the ask only when the developer has already told you, for the current task, to keep testing/iterating on your own until it looks right.

## Session working modes — how "work on …" starts a session
Two startup phrases select how a session works; recognize them on the first message.

**⚠️ ACT, don't narrate.** Recognizing the phrase means *doing* the startup steps in the SAME turn — launch the debug browser, attach, and confirm the target/watcher are up **before** you ask the user what to work on. Do NOT reply with only a description of the mode ("I'm in X mode, here's how it works, what would you like to do?") — that is the wrong response and wastes a round-trip. The correct first reply already contains the launched browser (confirmed up, no screenshot needed — see below), then asks what to work on. The developer has the browser right in front of them and will say if something looks wrong.

**✅ One-command startup — `workon.php` or `workon.py` (do this first).** Both phrases are automated by a single script in the repo root, so the whole startup is **one** approval instead of separate Chrome/watcher/confirm/screenshot prompts. Two implementations, identical behavior — **use whichever interpreter (`php` or `python`) the customer's machine has**; if both are available, prefer `workon.php`:
```
php workon.php {alias|wasql} [page]
python workon.py {alias|wasql} [page]
```
No `--shot` by default — per "Ask before doing post-change QA" above, don't screenshot at startup. Add `--shot=<scratchpad>/shot.png` yourself only once the developer has actually asked you to look at the page.

**"work on {site} [page] using {browser}"** (e.g. "work on dexpdq using firefox") → same command, plus `--browser={browser}` (`chrome`/`firefox`/`edge`, case-insensitive). Omit "using ..." and the script picks one itself — an explicit `--browser`/`WASQL_BROWSER`/persisted `--set-default` always wins, and failing those it auto-detects the user's own OS-default browser (falling back to `chrome` only if that can't be determined) — see `workon.md`'s "Browser choice" section for the full precedence and why Firefox needs a resident broker where Chrome/Edge don't.

Full mechanics, every flag's gotchas, and the manual fallback if the script fails → **`workon.md`**. `php workon.php --help` / `python workon.py --help` is the authoritative flag reference, kept current in the scripts themselves. **wamcp has no `setdb`/session-default database** — call the wamcp `databases` tool to resolve `{alias}` (or `localhost` for `wasql`) to a `db_id`, then pass that `db_id` explicitly on every subsequent wamcp call (`query`, `schema`, `pagesrc`, `tables`, `fields`, `ddl`, `indexes`, `getdb`).

**⚠️ `workon.php` and `workon.py` must stay in sync.** Any fix, new flag, or behavior change made to one gets ported to the other in the same turn — never edit just one and move on (see `workon.md`).

- **"work on {site} {page}"** (or "let's work on {site} {page}") → a **PostEdit-mirrored live site**. Resolve `{site}` and follow **`postedit.md`** (launch the debug browser, edit the mirrored `postEditFiles`, which auto-sync to the site DB, then refresh/screenshot). Default page = `index` when omitted. All logic stays in the DB — the "never modify core" rule above is in **full force**.
  - **⚠️ Confirm the PostEdit watcher is actively running before making edits.** Edits only sync to the DB while `postedit.php {alias}` is running. Always verify the watcher process is live (or launch it as a persistent process/task) before editing files, so saves auto-sync in real-time.
  - **⚠️ Once the mirror exists on disk, it — not wamcp — is your source for that site's page code.** `workon.php`'s/`workon.py`'s output already lists every mirrored page/field and the mirror root (`postedit/postEditFiles/{alias}/_pages/<page>/<page>._pages.<field>.<id>.<ext>` — see `postedit.md`'s "The edit loop"). To **read** a page's `body`/`controller`/`functions`/`js`/`css`, use `Read`/`Grep` on that file, not `wamcp pagesrc`/`query`. To **edit**, use `Edit` on that file — the watcher auto-syncs it to the DB. Reserve `wamcp` for what the mirror can't give you: querying/verifying actual row *data* (not page source), schema/DDL lookups, or a table that isn't mirrored.
- **"work on wasql"** → **local WaSQL framework development.** Site = **`http://localhost/php/admin.php`** (plain http; local Apache serves this repo as its docroot). Edit the **WaSQL core/framework files directly in the repo** (`php/`, `php/extras/**`, `wfiles/`, …) — there is **no PostEdit** in this mode (no mirrored files, no watcher, no DB sync; nothing needs to be running), so saving a repo file changes what `localhost` serves immediately. This is the **deliberate exception** to "never modify core files": here you ARE the framework developer, so core edits are expected — but they still affect every site built on WaSQL, so change with care. Optionally resolve `localhost` to a `db_id` via wamcp's `databases` tool and pass it on each call. `workon.php wasql` / `workon.py wasql` handles this mode's startup too (see above); its manual fallback is in `workon.md`.

## ⚠️ Gotchas that will bite you (skim before every task)
1. **Each `<?…?>` block in a view is eval'd SEPARATELY** — a control structure CANNOT span islands. `<?php foreach(...){ ?>…<?php } ?>` → `Parse error: Unclosed '{'` (a 500). Keep a loop/if inside ONE block, use `renderEach`/`renderViewIf`, or **build the HTML in a `functions` helper that returns a string** (what `databaseListRecords` does).
2. **No `<?php ?>` inside heredocs** — compute the value into a variable *before* the heredoc, interpolate `{$var}`.
2b. **A literal php CLOSE TAG anywhere in a page field silently truncates the rest of that field** — even inside a single-quoted string, even inside a `//` comment. Bites HTML regexes (`[^>]*/?>` → write `[^>]*/{0,1}>`) and `'<?xml … ?>'` (build it by concatenation). Symptom: `PHP Syntax Error` in `php/temp/{host}_php_*.php` quoting a fragment of your own regex. → `wasql_reference.md` → *Core helper traps*.
2c. **A UTF-8 BOM at the start of a `.php` file `include_once`'d before `ob_start("compress")` shorts the response by 3 bytes per BOM** (the BOM itself leaks into the buffer, but `Content-Length` is computed from the buffer *after* the callback runs) — browsers then truncate that many bytes off the end of the JS/CSS response. Symptom: minified JS/CSS served via `minify_js.php`/`min-css.php` cuts off mid-statement. Check for BOMs (`\xEF\xBB\xBF` as the file's first 3 bytes) in any file included ahead of the `ob_start` call, especially after a bulk edit made with a Windows editor.
3. **DB writes return the new id on success or an error STRING on failure** — test `isNum($id)`, never truthiness: `if(!isNum($id)){ $error=$id; … }`.
4. **`<view:>` blocks are only registered during BODY rendering**, not when controller/functions run — so `getView()`/`renderView()` for a form must be called **from the body** (`<view:form><?=pageAddEdit($id);?></view:form>`), not precomputed in the controller (else "no view named X").
5. **Inline PHP needs semicolons**: `<?=$var;?>` not `<?=$var?>`.
6. **`$_REQUEST` not `$_POST`; `global $PASSTHRU;` not `$_REQUEST['passthru']`** for routing.
7. **Primary key is `_id` (not `id`).** JSON text columns are decoded into a sibling `*_ex` array (`$PAGE['meta_ex']`) — read the `_ex`, write back the raw column; always `json_decode(...,true)`.
8. **`data-displayif`/`data-readonlyif` need `onchange="wacss.formChanged(this,event);"` on the `<form>`** or they never toggle.
9. **`setView($name,1)`** (2nd arg `1`) clears other views — use it for AJAX partials & login/error short-circuits, then `return;`. Plain `setView($name)` is cumulative (adds).
10. **AJAX partials go through the `/t/1/` blank template** (no chrome). Every `data-nav`/`ajaxGet`/AJAX form action that loads a div uses `/t/1/…`.
11. **`sendMail` body key is `'message'`** (not `body`/`html`) or mail sends empty silently.
12. **Don't invent class names or DOM helpers** — use `wacss_v2.css`/Bulma classes and `wacss.*` JS (see below).
13. **PostEdit screenshots:** after edit→auto-sync→refresh, **nudge the window 1px before screenshotting** so the layout settles (baked into the `shot.js` helper in `postedit.md` — use it).

## Page-field roles — think MVC (thin controller)
- **`controller` = Controller.** Keep it THIN: route on `$PASSTHRU`, check auth, pick the view with `setView()`, call functions to fetch/build data. Reads like a table of contents.
- **`functions` = Model.** The real work: DB queries, grids, computed data, business logic, and **any HTML-string building** (lengthy option arrays, loops/conditionals). Controller calls a named helper (`$grid = pageThingGrid();`).
- **`body` (`<view:name>` blocks) = View.** Presentation only; consumes controller variables, calls `renderView`/`renderEach`/`renderViewIf`.
- **Fetch data in the branch that needs it** — only put a query ABOVE the `switch($PASSTHRU[0])` if EVERY branch (incl. AJAX partials) uses it; data for the full-page `default` view belongs inside `case default:`.
- **Name variables for the reader** (`$bishopric`, not `$b`, in outer scope). Every function gets a PHPDoc/JSDoc block (see `wasql_reference.md` → PHPDoc convention).
```php
// controller (thin): route + delegate
switch(strtolower($PASSTHRU[0])){
  case 'pages': $grid = pageSamplePagesGrid(); setView('tab_pages',1); return;
  default:      setView('default'); break;
}
// functions (model): lengthy call lives here
function pageSamplePagesGrid(){
  return databaseListRecords(['-table'=>'_pages','-listfields'=>'_id,name,_edate','-order'=>'_edate desc','-limit'=>12]);
}
```

## Data access (essentials)
Options are ONE associative array. **Keys starting with `-` are directives; keys without a dash are data** — an implicit `column = value` WHERE filter for reads/counts/deletes, or the column values to write for add/edit.
```php
$rec  = getDBRecord(['-table'=>'items','_id'=>$id]);                 // dashless _id = WHERE filter
$ok   = editDBRecord(['-table'=>'items','-where'=>"_id={$id}",'status_id'=>3]); // dashless = columns written
$newid= addDBRecord(['-table'=>'items','name'=>$name,'active'=>1]);  // returns new id, or error STRING
```
Function set (all have a verbose `database*` alias; short form is standard): `getDBRecord`/`getDBRecordById`, `getDBRecords`, `getDBCount`, `addDBRecord`, `editDBRecord`/`editDBRecordById`, `delDBRecord`/`delDBRecordById`, `dbAddRecords` (bulk), `databaseListRecords` (HTML grid — preferred over the phased-out `listDBRecords`), `executeSQL` (raw). A raw SQL string also works: `getDBRecord("SHOW GLOBAL STATUS LIKE 'Uptime'")` → `$rec['value']`.
- Framework idiom is **`isNum()`** (not `is_numeric`), **`isUser()`/`isAdmin()`** (not `isLoggedIn`/`hasPermission`).
- **Querying a different DB connection** (postgres/hana/mssql/c-tree registered in `config.xml`) uses the **`db*` family** with the connection name first: `dbQueryResults('conn',$sql)`, `dbGetRecord('conn',$sql)`, `dbGetCount`, `dbListRecords`, `dbExecuteSQL`. → full detail in `wasql_reference.md`.
- Full `-` option keys (`-fields`, `-where`, `-order`, `-limit`, `-index`, `-query`, `-relate`, `-upsert`, `-nocache`, `-results_eval`…) and the `databaseListRecords` grid → `wasql_reference.md`.
- System tables have a leading underscore (`_pages`, `_users`, `_fielddata`…). Every record has audit cols `_id/_cdate/_edate/_cuser/_euser`.

## Routing, views & templates (essentials)
- URLs: `/{page}/{action}/{arg1}…` → `$PASSTHRU[0]`, `[1]`… A `/t/{templateId}/` prefix picks a template; **template id 1 is blank** (chrome-less) — that's what makes AJAX partials return only inner content.
- Views: a `<view:name>…</view:name>` block is registered but **not output** until a `renderView`/`renderViewIf`/`renderEach` names it or `setView()` selects it. `renderView($name,$data)` exposes `$data` inside as `$params` (rename with a 3rd arg: `renderView('x',$d,'row')` → `$row`).
- Pages are wrapped by their `template_id` `_templates` record (`<?=pageValue('body');?>`). A page's own `css`/`js` fields are **auto-injected** — don't `<link>`/`<script>` them yourself (framework bundles/extras are fine).
- `loadDBFunctions('functions_common')` loads another page's `functions` into scope (convention for shared helpers). `includePage('name/arg1/…')` renders another page inline. `minifyCssFile('wacss,bulma')`/`minifyJsFile(...)` return combined bundle URLs.
- Deeper: setView semantics, the getView/BODY-render gotcha, section-refresh, CRUD-tab recipe → `wasql_reference.md`.

## Auth
```php
global $USER; global $PASSTHRU;
if(!isUser()){ setView('login',1); return; }        // not logged in
if(!isAdmin()){ setView('no_access',1); return; }    // logged in but not admin
```
`$USER` fields (`_id`, `username`, `email`, `firstname`, `lastname`, `utype` — numeric, `0` = admin, that's what `isAdmin()` checks — JSON `_ex` variants) read directly; there is no `role`/`user_type`/`name` field. `userLoginForm(['-action'=>'/'.pageValue('name')])` renders login. No group/role function in real use — per-page allow-lists via `in_array($USER['username'],[...])`. Logoff: `/?_logoff=1`.

## Client-side JS (essentials)
- **Standardize on `wacss.*`** — don't reinvent nav/post/tabs/modals/toasts or hand-roll DOM helpers when a `wacss.*` method exists. Legacy bare globals (`ajaxSubmitForm`, `ajaxGet`) work for old code but not for new.
- **AJAX nav:** `<a data-nav="/t/1/page/action" data-div="target" onclick="return wacss.nav(this);">`. **Tabs:** add `data-tab="1"` to a `wacss.nav` anchor — it AJAX-loads AND moves the active class (never hand-roll `setActiveTab`). **AJAX form:** `<form action="/t/1/page/action" onsubmit="return wacss.ajaxPost(this,'target_div');">`.
- **Centerpop modal:** target a div named `centerpop` (`data-div="centerpop"`) — it self-creates the modal; close with `wacss.centerpopClose()` (safe no-op if none open). Others: `wacss.toast`, `wacss.copy2Clipboard`, `wacss.initDatePicker`, `wacss.pagingSubmit`. `pushData($data,'csv','file.csv')` streams a download.

## Styling (essentials)
- **Use framework classes; don't invent them.** Canonical stylesheet **`wfiles/css/extras/wacss_v2.css`** — `wacss_`-prefixed classes with **Bulma-style `is-*` modifiers**; if the site loads **Bulma**, use Bulma's equivalents. `w_btn`/`w_input`/`w_table` do NOT exist; avoid legacy `wasql.css` (`.w_button`…) in new code.
- Button `class="wacss_button is-primary"` (Bulma `button is-primary`); input `wacss_input` (Bulma `input`); table `wacss_table is-striped` (add `is-sticky` for pinned header — needs the `wacss_table` base, survives section-refresh). Only write custom page `css` for genuinely page-specific layout.
- **⚠️ Don't use Bulma's `.title`/`.subtitle` for page headings.** wacss's "bulma css helpers" block ships `.title,.wacss-title{font-size:2.5rem !important}` — that beats `is-4`/`is-6` *and* any page rule, and Bulma's `.title + .subtitle` −1.25rem pull then collapses the two into each other. Give the heading a page-owned class (`.rpt-title`) and size it yourself. Same lesson generally: if a class won't take, check the bundle for an `!important` before adding specificity.

## Config & environment
- **`isDBStage()` is the ONLY environment switch** (no `isDev`/`isProd`) — true on staging/dev DB.
- **Two config sources:** static/secret/connection config in **`config.xml`** → `$CONFIG['...']` / `$DATABASE['DBNAME']` (never hardcode tokens). Site-editable branding/settings in the DB → `commonGetSetting('group','key')`.

## Utility helpers worth knowing
`printValue($x)` (dump — universal debug), `debugValue($x)`, `sortArrayByKeys`, `truncateWords`, `removeHtml`, `encodeURL`, `formatMoney`, `commonFormatPhone`, `getFileExtension`, `getFileContents`/`setFileContents`, `isNum`/`isEmail`/`isDate`/`isAjax`, `commonStrlen` (multibyte — prefer over `strlen`), `encodeJson`/`decodeJson`, `encodeHtml` (always escape user data in views). Email: `sendMail(['to'=>,'subject'=>,'message'=>])` or branded `commonSendMail(...)`. Dates: native `strtotime()`/`date()`.

## Corrections to older docs
- **`isLoggedIn()` / `hasPermission()` do NOT exist** (they appear in `quick_reference.md` — wrong). Use `isUser()` / `isAdmin()`.
- **Filter by `_id`, not `id`.** **`setValue()` does not exist** — set variables directly.

## Where to find more
- **`wasql_reference.md`** — deep per-feature detail: data-access options & grids, named DB connections, views/templates internals, form building & `addEditDBForm` global-save, section-refresh, CRUD-tab recipe, **Chart.js extra**, **server/system health**, `_triggers`/`_prompts`/`cron_*`, PHPDoc convention, copy-paste scenarios. Open the section matching your task.
- **`workon.md`** — full mechanics of the `workon.php` one-command session-startup script: what each step does, gotchas, the permission-wildcard check, `--reshoot`/`--no-chrome`, and the manual fallback if the script fails.
- **`postedit.md`** — working on a live PostEdit-mirrored site: "work on {alias} {page}" startup, resolve the alias from `postedit.xml`, resolve its wamcp `db_id`, launch the debug browser, edit→auto-sync→refresh→**nudge 1px**→screenshot.
- **`architecture.md`** — complete technical documentation.
- **`php/admin/wamcp.md`** — the wamcp MCP tool surface (`query`, `schema`, `pagesrc`, `tables`, `fields`, `ddl`, …). **Prefer `pagesrc`** (with `grep`/`lines`) **over `query`-ing `_pages` directly** when reading a page's `body`/`functions`/`controller`/`js`/`css` — it's the cheaper, purpose-built path; `query`/`schema` also cap output by default (`maxrows`/`maxchars`/`all`) to control token usage.

Remember: WaSQL's database-driven architecture and unique syntax are its strengths — work with them, not against them.
