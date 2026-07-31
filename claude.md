# WaSQL Framework Instructions for Claude

WaSQL is a **database-driven** PHP framework: page logic lives in `_pages` table records (fields `name`, `body`, `functions`, `controller`, `js`, `css`), not files — a MySQL dump is the entire site. This file is the **always-loaded core**: the rules and gotchas that apply to nearly every task. Deep, feature-specific detail lives in **`wasql_reference.md`** (read the section that matches your task, including its "Common scenarios" copy-paste starters). Other docs: `architecture.md` (full technical), `postedit.md` (working on a live PostEdit-mirrored site), `quick_reference.md` (examples — note its `isLoggedIn`/`hasPermission` are WRONG; see Corrections). `ai_patterns.md` was removed as redundant/inaccurate — use `wasql_reference.md`'s "Common scenarios" instead.

## ⚠️ CRITICAL: Developer Preferences
**NEVER `git commit` or `git push`** — the developer handles ALL commits/pushes manually. Make file changes as requested, but never commit them, even when asked to "commit this."

**NEVER modify core platform / framework files when building a site or app.** The WaSQL core under `php/` (`php/common.php`, `php/database.php`, `php/user.php`, `php/extras/**`) is shared by hundreds of sites — a change for one site can break all of them. Keep ALL site/app logic in the **database**: a page's `functions`/`controller`/`body`, shared helpers in **`functions_common`** (loaded via `loadDBFunctions`), or a **template's `functions`**. If a feature seems to *need* a core hook, do it in the DB layer instead and surface the limitation to the developer. Genuine framework enhancements are a separate, deliberate decision the developer makes — never bundle them into site work.

## Session working modes — how "work on …" starts a session
Two startup phrases select how a session works; recognize them on the first message.

**⚠️ ACT, don't narrate.** Recognizing the phrase means *doing* the startup steps in the SAME turn — launch the debug Chrome, attach, and screenshot the target page **before** you ask the user what to work on. Do NOT reply with only a description of the mode ("I'm in X mode, here's how it works, what would you like to do?") — that is the wrong response and wastes a round-trip. The correct first reply already contains the launched browser + a screenshot of the current state, then asks what to work on.

**✅ One-command startup — `workon.php` (do this first).** Both phrases are automated by a single script in the repo root, so the whole startup is **one** approval instead of separate Chrome/watcher/confirm/screenshot prompts:
```
php workon.php {alias|wasql} [page] --shot=<scratchpad>/shot.png
```
It resolves `{alias}` → host from `postedit/postedit.xml` (or handles the local `wasql` case: `http://localhost/php/admin.php`, no watcher), ensures the PostEdit watcher for the alias is running (PostEdit sites only; it warns about the destructive startup re-sync) **filtered to the named page** — `work on dexpdq sapcc` starts `postedit.php dexpdq sapcc`, so only records whose name contains `sapcc` are synced/watched (fast startup, no unrelated files; a watcher that's already running is reported, never restarted) — then ensures a debug Chrome is up on port **9222** (reuses any running instance — never spawns a full duplicate); the watcher is started first so its background re-sync overlaps the Chrome boot instead of running back-to-back. Use `--no-filter` when you also need the page's `_templates` record or other pages. It then confirms the page's Chrome target, and writes a mobile screenshot via CDP with the **1px reflow nudge** baked in. Then **Read the PNG** and set the DB with the wamcp `setdb` tool (`setdb {alias}`; `setdb localhost` optional for `wasql`) — `setdb` is a separate MCP call the script does not make. Default page = `index` (PostEdit) / `php/admin.php` (`wasql`). Options: `--no-watcher`, `--filter=a,b`, `--no-filter`, `--width=N`, `--port=N`, `--chrome=PATH`, `--json`. Fall back to the manual steps below only if the script fails.

- **"work on {site} {page}"** (or "let's work on {site} {page}") → a **PostEdit-mirrored live site**. Resolve `{site}` and follow **`postedit.md`** (launch debug Chrome, edit the mirrored `postEditFiles`, which auto-sync to the site DB, then refresh/screenshot). Default page = `index` when omitted. All logic stays in the DB — the "never modify core" rule above is in **full force**.
- **"work on wasql"** → **local WaSQL framework development.** Site = **`http://localhost/php/admin.php`** (plain http; local Apache serves this repo as its docroot). Edit the **WaSQL core/framework files directly in the repo** (`php/`, `php/extras/**`, `wfiles/`, …) — there is **no PostEdit** in this mode (no mirrored files, no watcher, no DB sync; nothing needs to be running), so saving a repo file changes what `localhost` serves immediately. This is the **deliberate exception** to "never modify core files": here you ARE the framework developer, so core edits are expected — but they still affect every site built on WaSQL, so change with care. Optionally point the DB tools at the local install (`setdb localhost`).
  - **On the first message, immediately** (per the ACT rule above): (1) find Chrome, (2) write `shot.js` to the scratchpad if absent, (3) `Start-Process` a detached debug Chrome (`--remote-debugging-port=9222`, dedicated `--user-data-dir`) at `http://localhost/php/admin.php`, (4) confirm the target via `curl -s http://localhost:9222/json`, (5) run `shot.js` and Read the PNG. Follow `postedit.md` for the exact commands — **minus the auto-sync wait** (no watcher in this mode). Then ask what to work on.

## ⚠️ Gotchas that will bite you (skim before every task)
1. **Each `<?…?>` block in a view is eval'd SEPARATELY** — a control structure CANNOT span islands. `<?php foreach(...){ ?>…<?php } ?>` → `Parse error: Unclosed '{'` (a 500). Keep a loop/if inside ONE block, use `renderEach`/`renderViewIf`, or **build the HTML in a `functions` helper that returns a string** (what `databaseListRecords` does).
2. **No `<?php ?>` inside heredocs** — compute the value into a variable *before* the heredoc, interpolate `{$var}`.
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
- **`postedit.md`** — working on a live PostEdit-mirrored site: "work on {alias} {page}" startup, resolve the alias from `postedit.xml`, `setdb`, launch debug Chrome, edit→auto-sync→refresh→**nudge 1px**→screenshot.
- **`architecture.md`** — complete technical documentation.
- **`php/admin/wamcp.md`** — the wamcp MCP tool surface (`query`, `schema`, `pagesrc`, `tables`, `fields`, `ddl`, …). **Prefer `pagesrc`** (with `grep`/`lines`) **over `query`-ing `_pages` directly** when reading a page's `body`/`functions`/`controller`/`js`/`css` — it's the cheaper, purpose-built path; `query`/`schema` also cap output by default (`maxrows`/`maxchars`/`all`) to control token usage.

Remember: WaSQL's database-driven architecture and unique syntax are its strengths — work with them, not against them.
