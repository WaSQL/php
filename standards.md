# WaSQL site coding standards — "the WaSQL way"

Applies to every WaSQL **site/app** you build or change (not to core `php/` work). Unless the developer says otherwise for the task, follow all of it. Check it before creating any page, table, function, email, cron, or stylesheet reference. Referenced from `CLAUDE.md`.

## CSS / front end
- **WaCSS + BulmaCSS are the default CSS.** Any other CSS/JS library only when the developer asks.
- Every stylesheet/script a site relies on is **loaded from the main template** (`minifyCssFile`/`minifyJsFile` in the `_templates` record), never `<link>`/`<script>`'d from individual page bodies. A page's own `css`/`js` fields are auto-injected — leave those alone.
- **Load order is `wacss,wacss_v2,bulma`** (`minifyCssFile('wacss,wacss_v2,bulma')`) — later wins on conflicts, so Bulma overrides WaCSS. When both are available prefer the Bulma class; use `wacss_*` only where Bulma has no equivalent. Use real framework classes (CLAUDE.md → Styling); don't invent any.

## MVC — thin controller / model in functions / view in `<view:>`
- **A page controller never echoes** (no `echo`/`print`/`header()`/`exit`). It may call functions and assign variables freely. Shape of a well-built controller (this is how our admin/"manage"-style pages are structured):
  1. **Gate first, by what the page needs:**
     - public page → **no gate at all**;
     - page that needs a login → `if(!isUser()){ setView('login',1); return; }`;
     - admin page → `if(!isAdmin()){ setView('login',1); return; }`.
     The `login` view is just `<?=userLoginForm(['-action'=>'/'.pageValue('name')]);?>`. Then `global $USER; global $PASSTHRU;`.
  2. **`switch(strtolower($PASSTHRU[0]))`** is the router — one `case` per section/action. Sections with sub-actions use a **nested `switch(strtolower($PASSTHRU[1]))`** on the verb (`list`, `addedit`, `toggle`, `delete`, `import`, …), with the section's own landing view as the fall-through after the inner switch.
  3. **Every case does the same three things, in order**: (a) sanitize inputs from `$PASSTHRU`/`$_REQUEST` (`(integer)$PASSTHRU[2]`, `preg_replace('/[^0-9]+/','',…)`, `isset($_REQUEST['x'])?…:''`) into named variables; (b) call a page function that does the work or sets the data (`$ok=manageThingToggle($id);`, `$order=manageOrder($id);`); (c) `setView('name',1); return;`. Action cases (toggle/delete/save/send) mutate, then **re-render the list view** so the AJAX target refreshes.
  4. **`default:` → `setView('default')`** — the full-page shell. Section content is loaded into a div by AJAX via `/t/1/{page}/{section}[/list|/addedit/{id}]`, so each section/list/form/result is its own view (`orders`, `orders_list`, `orders_addedit`, `cart_email`, `cart_email_sent`, …) and the controller just maps URL → view.
  5. Controller-set variables (`$id`, `$order`, `$period`) are what the views consume; views call page functions for grids/KPIs (`<?=manageListOrders();?>`) and use `renderViewIf`/`renderViewIfElse` for conditionals (`isDBStage()` dev tags, goto-live/dev links).
  6. **File/image/JSON/download output** (posters, QR codes, CSV) lives in a page *function* that sends its own headers and exits; the controller case is just `manageAffiliatePoster($id); return;`.
- Logic/queries/business rules go in `functions`; markup goes in `<view:>` blocks rendered with `renderView`/`renderEach`/`renderViewIf`. Never split a PHP control structure across `<?php ?>` islands (CLAUDE.md gotcha #1).

## Functions
- **A function-only page (no view) — e.g. `functions_common` — keeps its functions in the `body` field**, loaded with `loadDBFunctions('functions_common')`. Pages with views keep theirs in `functions`.
- **Name = the full page name, camelCased, as the prefix.** Going forward: a function on page `functions_common` is `functions_commonTestFunction()`; on page `manage` it's `manageTestFunction()`; on `functions_respond` it's `functions_respondThing()`. Never a product/site prefix. (Existing `common*` helpers stay as they are; don't rename them unasked.)
- **Every function has a PHPDoc-style docblock** (`@describe`/`@param`/`@return`/`@usage` — see `wasql_reference.md` → PHPDoc convention). JS functions get JSDoc.

## Data
- **No project prefix on table names** (`forms`, not `mysite_forms`). System tables keep their leading underscore.
- Test DB write results with `isNum($id)` (never truthiness); `encodeHtml()` user data in views.
- New columns/tables a feature needs are self-healed at runtime in the function that needs them (check, then `ALTER TABLE` via `executeSQL`).

## Email & SMS — always queued, never sent inline
Outgoing email and SMS are **inserted into a queue table and delivered by a cron page**. Never call `sendMail`/`amazonSendTextMsg` directly from request code, and never invent a per-site queue table. (In-app notifications that aren't email/SMS are out of scope.)

- **`emails`** table → drained by **`cron_sendmail`**. At least:
  ```
  error      varchar(1000)
  message    json                      -- sendMail() array format
  sent       tinyint(1) NOT NULL DEFAULT 0
  sent_date  datetime
  ```
- **`texts`** table → drained by **`cron_sendtexts`**. At least:
  ```
  error      varchar(1000)
  message    varchar(1600) NOT NULL
  phone      varchar(32) NOT NULL
  sent       tinyint(1) NOT NULL DEFAULT 0
  sent_date  datetime
  ```
- Build the `cron_*` *pages* only — never create the `_cron` rows (the developer schedules them). Cron pages log with `commonCronLog($msg)`, not echo/print; test them by hitting the URL (dev crons never fire on their own).

## SEO / AIO
**When building a new site, ask the developer whether to build the SEO/AIO pages.** If yes: `robots.txt`, `llms.txt` (and `llms-full.txt` when useful), a dynamic `sitemap.xml`, per-page `<title>`/meta description/canonical/Open Graph/Twitter tags plus relevant JSON-LD, set from the template `functions`; IndexNow when the site has a key. Pages are real `_pages` records whose `permalink` matches the served filename (a mismatch 404s). Private pages (`/manage`, `/portal`, login) get `noindex`.

## Process
- Dev + live: build/fix on dev; code changes reach live by the developer's promotion process, not raw SQL.
- Record durable learnings in the site's `{alias}.md` and in `wasql_reference.md`.
- Everything in CLAUDE.md "Developer Preferences" still applies (no core edits, no admin UI, no minified assets, no commits).
