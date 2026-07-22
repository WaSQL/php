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
| Making a value/row open a detail modal (no form) | [Read-only detail popup (centerpop)](#read-only-detail-popup-centerpop) |
| Building a "manage {things}" admin tab | [CRUD-tab pattern](#crud-tab-pattern) |
| Adding a chart | [Chart.js extra](#chartjs-the-chartjs-extra) |
| Building a server/DB health or monitoring page | [Server / system health in PHP](#server--system-health-in-php) |
| Writing DB record hooks / crons / query consoles | [_triggers](#_triggers--db-record-lifecycle-hooks) · [cron_*](#cron_-pages) · [_prompts](#_prompts--saved-querycode-console-not-ai-prompts) |
| Documenting functions | [PHPDoc / JSDoc convention](#phpdoc--jsdoc-convention) |
| Want copy-paste starters | [Common scenarios](#common-scenarios-copy-paste-starters) |

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

## Named / secondary DB connections
The standard `getDBRecord`/`getDBRecords`/`getDBCount`/`addDBRecord`/`editDBRecord`/`executeSQL` functions hit the **current site DB**. To query a *different* connection registered in `config.xml` (e.g. a PostgreSQL warehouse named `postgres_ods`, a HANA/c-tree source), use the **`db*` wrapper family** — the connection name is always the **first argument**. It dispatches through `dbFunctionCall()`, which looks the name up in the global `$DATABASE` array, detects its `dbtype` (postgres/hana/mssql/mysql…), loads the right engine, and runs the type-specific query. This — not `-dbname` — is the idiom real multi-source sites use.
```php
$rows = dbQueryResults('postgres_ods', "SELECT ... LIMIT 50"); // raw SQL -> array of rows
$rec  = dbGetRecord('postgres_ods', "SELECT pg_postmaster_start_time() AS started"); // one row
$n    = dbGetCount('postgres_ods', ['-table'=>'foo','status'=>1]);
$grid = dbListRecords('postgres_ods', ['-query'=>"SELECT ...", '-tableclass'=>'wacss_table is-striped']);
$ok   = dbExecuteSQL('postgres_ods', $sql); // non-SELECT
```
- Returns an **error STRING** on failure (test `is_array($rec)` / `isNum()`, never truthiness).
- PostgreSQL is a first-class `dbtype`; the engine lives in `php/extras/postgresql.php` and is auto-loaded — no manual `loadExtras`.
- Enumerate configured connections via `global $DATABASE; foreach($DATABASE as $name=>$info){ /* $info['dbtype'],['dbhost'],['dbname']… — NEVER print ['dbpass'] */ }`. Active connection = `$CONFIG['db']`.
- (Legacy: `getDBRecords(['-dbname'=>'x',...])` also exists — older ODBC-oriented path; prefer the `db*` wrappers in new code.)

---

## Views & templates (deep)
- A `body` field is a set of named `<view:name>...</view:name>` blocks. **Defining a view never outputs it** — WaSQL extracts every block into a registry and the block text is removed from where it sits. It only appears when a `renderView`/`renderViewIf`/`renderEach` call names it, or `setView()` selects it as the page output.
- **Nesting `<view:>` blocks is purely cosmetic** (for readability). An inner block still only renders where something explicitly calls it. Don't expect an inline nested view to appear in place.
- Render functions: `renderView($name[,$data[,$var]])`, `renderViewIf($cond,$name[,$data[,$var]])`, `renderViewIfElse($cond,$a,$b,...)`, `renderViewSwitch($val,$map,...)`, `renderEach($name,$array[,$var])`. The view name and loop-var name are independent: `renderEach('photo',$product['photos'],'photo')`.
- `getView($name)` returns a view's raw string (used to feed `-fields`/`-listview` in form options). Wrap in `evalPHP(getView(...))` when the view layout contains PHP to evaluate.
- **`renderView($name,$data)` exposes `$data` inside the view as `$params`** (the whole passed value — array, string, etc.). Access it as `$params['title']` etc., or rename it with the `-alias` opt (`renderView('addedit',$data,'row')` → `$row` in the view). There is also a `-format=>'addeditdbform'` opt that renders the view's `[fieldname]` layout as an `addEditDBForm` (pass `-table`).
- **⚠️ `<view:>` blocks are only registered in `$VIEWS` during BODY rendering, NOT when the controller/functions run.** So a function that calls `getView('..._fields')` or `renderView(...)` must be invoked from the **body** (inside a `<view:>` block), not precomputed in the controller — calling too early throws `renderView Error: There is no view named X` and `getView` returns empty. Idiom: the controller just routes and `setView('form',1); return;`, and the body block builds it: `<view:form><?=pageAddEditForm($tab,$id);?></view:form>`. Grid/`databaseListRecords` builders that don't touch `getView` are fine to call from the controller.

### Conditional views: prefer `renderViewIf`/`renderViewIfElse` over inline ternary HTML
**Prefer a named `<view:>` + a conditional render over an inline `<?=$cond ? '<html…>' : '<html…>';?>` ternary.** The ternary crams two chunks of escaped HTML onto one line — unreadable and easy to break. A view keeps the markup as real, editable HTML and the branch as a one-liner. Exact signatures (verified in `php/common.php`):
- `renderViewIf($cond, $view[, $params[, $opts]])` — renders `$view` when `$cond` is truthy, else returns `''`.
- `renderViewIfElse($cond, $viewIf, $viewElse[, $params[, $opts]])` — renders `$viewIf` when truthy, otherwise `$viewElse`.
- `$params` is the value exposed inside the view. **The trailing string arg is the alias — the variable name the passed data is bound to *inside* the view** (no leading `$`); default is `$params`. It's the same mechanism as `renderView`'s 3rd arg and `renderEach`'s loop-var name. So `renderViewIfElse($cond,'a','b',$info,'info')` → read `$info[key]` inside; `renderViewIfElse($cond,'a','b',$info,'rec')` → read `$rec[key]` inside (same data, different variable name). Whatever you pass as the data becomes that variable, so pass a sub-array (`$info['1st_counselor']`) when you want a generic view to read generic keys (`$info['picture']`).
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

### Templates
- A page is wrapped by the `_templates` record named in its `template_id` field. Templates have the same `body`/`functions`/`css`/`js` fields and drop the page in with `<?=pageValue('body');?>`.
- Template meta helpers (`templateMetaTitle/Description/Keywords/Image/Site`, `templateActiveMenu`) are **per-site copies defined in each template's `functions`**, not framework built-ins — they read from `global $PAGE`.
- A page's own `css` and `js` fields are **auto-injected by the framework** when the page renders. Do NOT manually `<link>`/`<script>` a page's own css/js. (Framework asset bundles and extras like `chart.min.js` are a different thing and fine to include.)

### Cross-page includes & shared functions
- `includePage('topnav')` / `includePage('bugshoney/webhook/add/.../{$id}')` — render another page (with optional passthru segments) inline.
- `loadDBFunctions('functions_common')` (or an array of names) — load another page's `functions` field into scope so its helpers are callable. **Convention: put shared helpers in a page like `functions_common` and `loadDBFunctions` it** (commonly done in the template's `functions`).
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
- Per-field: `fieldname_options => [...]` (richest — keys `inputtype`, `class`, `style`, `displayname`, `required`, `message`, `tvals`, `dvals`, `value`, `values`, `width`, `height`, `-display`, `-format`, `readonly`, `onchange`, `placeholder`), or the shorthands `fieldname_class`, `fieldname_required`, `fieldname_style`, `fieldname_value`, `fieldname_displayname`, etc.
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

## Chart.js (the `chartjs` extra)
The bundled chart library is **`/wfiles/js/extras/chart.min.js` — Chart.js v2.8.0** (use v2 option syntax: `options.legend`, `options.title`, `scales.yAxes:[{ticks:{beginAtZero,max}}]`, `cutoutPercentage`, `maintainAspectRatio`; NOT v3+). There is **no PHP charting engine** — it's client-side, driven by `wacss.initChartJs()`.

**wacss contract (the normal path):** a container `<div class="chartjs" id="X" data-type="pie|doughnut|bar|line|time|gauge">` plus a sibling hidden `<div id="X_data">` holding custom tags parsed by the initializer — `<options>{JSON}</options>`, `<labels>[JSON]</labels>`, optional `<colors>`/`<bcolors>`, and one or more `<dataset data-label="…">[JSON]</dataset>`. Kick off with `<?=buildOnLoad("wacss.initChartJs();");?>`. Build the labels/datasets in PHP and `json_encode` them into the tags.

**Gotchas (verified):**
- `wacss.loadScript` is **async** and `initChartJs` bails when `Chart` is still undefined → the first call can silently no-op (race). If you need charts guaranteed on first paint, load `chart.min.js` with a plain synchronous `<script src=…>` in the page body (it's a framework asset, fine to include — not the page's own css/js field).
- `initChartJs`'s loop over `.chartjs` has **no idempotency guard**, and `wacss.init()` calls it directly *and* via `initOnloads` — so repeated calls (initial load + a `data-onload`) **double-render** (duplicate canvases). Do NOT put both `class="chartjs"` and `data-behavior="chartjs"` on one container either (also double-builds).
- **For a section that AJAX-refreshes** (e.g. an auto-refreshing dashboard), don't fight the auto-parser: give canvases a **non-triggering** class (e.g. `app-chart`, no `chartjs`/`data-behavior`), stash the full v2 config in a `data-chart` attr via `htmlspecialchars(json_encode($cfg),ENT_QUOTES)`, and run your own JS that **destroys prior instances then `new Chart(cv.getContext('2d'),cfg)`**, wired through the refreshed partial's `data-onload`. Idempotent and race-free.

## Server / system health in PHP
The web PHP runs **as apache on the site's own Linux host**, so for a health/monitoring page you can read metrics locally without SSH:
- **Direct `/proc` reads** (most reliable): `file_get_contents('/proc/meminfo')` (MemTotal/MemAvailable, KB), `/proc/loadavg` (1/5/15m + running procs), `/proc/uptime` (secs since boot), `/proc/cpuinfo` (`model name`, count `^processor` lines). Plus native `disk_free_space($path)` / `disk_total_space($path)`.
- **Framework helper:** `loadExtras('system'); $info=getServerInfoLinux();` returns `load_averages`, `uptime`/`uptime_verbose`, `cpu_info`, `memory_usage` (with shell fallbacks). `php/extras/system.php`.
- **Shell when needed:** `cmdResults($cmd)` → `['stdout','stderr','rtncode','runtime']` (via `proc_open`).
- **Caveat:** apache often can't stat a DB's data dir (e.g. postgres `0700 /var/lib/pgsql`), so `disk_*_space` there returns false — fall back to reporting logical size (`pg_database_size`, `SUM(data_length+index_length)`).
- **DB server health:** MySQL uptime/threads via `performance_schema.global_status` (or `SHOW GLOBAL STATUS LIKE 'Uptime'` → `$rec['value']`), `@@max_connections`. PostgreSQL via a named connection: `dbGetRecord('conn', "SELECT pg_postmaster_start_time(), (SELECT count(*) FROM pg_stat_activity) …")`.

---

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

**Create a new page:** create the page record via the WaSQL web interface → `p.bat {alias}` to sync it down → edit the generated files in your IDE → changes auto-sync back to the DB.

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
