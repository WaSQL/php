# WaSQL Framework Instructions for Claude

## Overview
This document instructs Claude on how to assist users with WaSQL, a database-driven PHP web development framework. **Always read and reference `architecture.md` for comprehensive technical details.**

## ⚠️ CRITICAL: Developer Preferences

**NEVER use `git commit` or `git push` commands under any circumstances. The developer handles ALL git commits and pushes manually.**

- Do NOT commit changes
- Do NOT push to remote repositories
- Do NOT create commits even when asked to "commit this"
- Make changes to files as requested, but ALWAYS let the developer commit them

## Key Understanding Points

### WaSQL is Different from Traditional MVC
- **All logic is stored in database records**, not files
- Pages are records in the `_pages` table with fields: `name`, `body`, `functions`, `controller`, `js`, `css`
- This provides enhanced security (harder to hack) and instant portability (MySQL dump = entire site)

### Critical Syntax Rules
- **No `setValue()` function** - set variables directly: `$message = 'Hello';`
- **Use `$_REQUEST` not `$_POST`** for form data
- **Use `global $PASSTHRU;` not `$_REQUEST['passthru']`** for URL routing
- **All inline PHP needs semicolons**: `<?=$variable;?>` not `<?=$variable?>`
- **No PHP if/endif in views** - use `renderViewIf()` with `<view:name>` blocks
- **Use `global $USER;`** for built-in user management
- **Variables set in controller are available in views** directly
- **Use `data-displayif` to conditionally show/hide form sections** - add `data-displayif="fieldname:value"` to a container div; WaSQL will show it when the named field equals the value and hide it otherwise. Works with nested JSON fields (e.g. `data-displayif="shipto>is_gift:Y"`). Do NOT use manual `onchange` JS for this purpose:
  ```html
  <!-- CORRECT - WaSQL handles show/hide automatically -->
  <div id="gift_message_row" data-displayif="shipto>is_gift:Y">
      <label>Gift Message</label>
      <div>[shipto>gift_message]</div>
  </div>
  ```
- **No PHP tags inside heredocs** - `<?php ?>` inside a heredoc causes a syntax error. Instead, compute the conditional value into a variable BEFORE the heredoc, then interpolate with `{$variable}`:
  ```php
  // WRONG - causes syntax error:
  $msg = <<<EOT
  <?php if($x): ?>some text<?php endif; ?>
  EOT;

  // CORRECT:
  $conditional = $x ? 'some text' : '';
  $msg = <<<EOT
  {$conditional}
  EOT;
  ```

### Development Workflow
1. **Database First**: New pages/components must be created via WaSQL web interface
2. **PostEdit Sync**: Use `p.bat alias` to download database records as local files
3. **Modern Editing**: Edit with VS Code, PhpStorm, etc. with full IDE support
4. **Auto-Sync**: Changes automatically update database records
5. **Environment Promotion**: Use synchronization feature to promote changes through dev → stage → prod

## When Helping Users

### Code Generation Rules
- **Always use proper WaSQL syntax** from the corrected patterns
- **Set variables in controller**: `$users = getDBRecords(['-table' => 'users']);`
- **Use variables in views**: `<?=renderEach('user_row', $users, 'user');?>`
- **Use `renderViewIf()` for conditionals**: Never `<?php if: ?><?php endif; ?>`
- **All conditional content goes in `<view:name>` blocks**

### Common User Scenarios

#### "How do I create a new page?"
```php
// 1. Create page record via WaSQL web interface
// 2. Run PostEdit to sync: p.bat your-alias
// 3. Edit the generated files in your IDE
// 4. Changes auto-sync back to database
```

#### "How do I display data?"
```php
// In controller field:
$users = getDBRecords(['-table' => 'users', '-limit' => 10]);

// In body field:
<?=renderEach('user_card', $users, 'user');?>

<view:user_card>
<div class="user"><?=encodeHtml($user['name']);?></div>
</view:user_card>
```

#### "How do I handle forms?"

**Option 1: Using addEditDBForm (Recommended - Automatic Processing)**
```php
// In functions field:
function pageAddEdit($id = 0) {
    $opts = array(
        '-table' => 'users',
        '-fields' => getView('form_fields'),
        'name_options' => array('required' => 1),
        'email_options' => array('required' => 1, 'inputtype' => 'email')
    );
    if($id > 0) {
        $opts['_id'] = $id;
    }
    return addEditDBForm($opts); // Automatically handles add/update
}

// In controller field:
global $PASSTHRU;
$id = $PASSTHRU[1] ?? 0;
// No manual save handling needed - addEditDBForm does it automatically

// In body field:
<?=pageAddEdit($id);?>
```

**Option 2: Manual Form Processing**
```php
// In controller field:
if($_REQUEST['action'] == 'save_user') {
    // Manual processing when coding forms manually in view
    $result = addDBRecord([
        '-table' => 'users',
        'name' => $_REQUEST['name'],
        'email' => $_REQUEST['email']
    ]);
    if(is_numeric($result)) {
        $success = 'User saved successfully';
        setView('success');
    } else {
        $error = $result;
        setView('error');
    }
}

// In body field: Manual HTML form
<form method="post">
    <input type="hidden" name="action" value="save_user">
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <button type="submit">Save</button>
</form>
```

#### "How do I check if user is logged in?"
```php
// In controller field:
global $USER;
if(!isUser()) {
    setView('login_required');
    return;
}

$currentUser = $USER;
$userName = $USER['name'];
```

#### "How do I handle URL routing?"
```php
// In controller field:
global $PASSTHRU;
switch(strtolower($PASSTHRU[0])){
    case 'edit':
        $id = $PASSTHRU[1]; // From URL: /page/edit/123
        $record = getDBRecord(['-table' => 'items', '_id' => $id]);
        setView('edit_form');
    break;
    case 'list':
        $items = getDBRecords(['-table' => 'items']);
        setView('item_list');
    break;
    default:
        // Default view automatically loads if no setView() called
        $welcomeMsg = 'Welcome to our site';
    break;
}
```

#### "How do I use AJAX navigation?"
```html
<!-- In body field: -->
<a href="#" data-div="content" data-nav="/page/list" onclick="return wacss.nav(this);">
    Load List
</a>

<div id="content">
    <!-- AJAX content loads here -->
</div>
```

### Architecture Guidance
- **Reference `architecture.md`** for detailed technical information
- **Explain the database-driven approach** when users seem confused
- **Emphasize the PostEdit workflow** for modern development experience
- **Highlight security benefits** of database-stored logic

### File Structure (PostEdit)
When users ask about local development, explain the PostEdit file naming:
`{name}.{table}.{field}.{_id}.{extension}`

Example: `about._pages.body.13.html`

### Multi-Language Support
WaSQL supports PHP, Python, Node.js, Ruby, Perl, VBScript, Lua, Bash in the same application.
All languages receive the same request/session/server variables.

## What NOT to Do
- Don't suggest `setValue()` - it doesn't exist
- Don't use `pageValue()` for variables - only for page fields
- Don't suggest `$_POST` - use `$_REQUEST`
- Don't use `$_REQUEST['passthru']` - use `global $PASSTHRU;`
- Don't suggest PHP if/endif syntax in views
- Don't forget semicolons in inline PHP
- Don't suggest traditional file-based MVC patterns
- Don't recommend creating separate PHP files for models/controllers
- Don't suggest frameworks like Laravel/Symfony as alternatives
- Don't ignore WaSQL's database-first architecture

## What TO Do
- Always use correct WaSQL syntax from `ai_patterns.md`
- Set variables directly in controller: `$var = 'value';`
- Use `renderViewIf()` and `<view:name>` blocks for conditionals
- Use `global $PASSTHRU;` for URL routing
- Use `global $USER;` for user management
- Suggest using PostEdit for local development
- Recommend proper WaSQL functions and patterns
- Explain the security and portability benefits
- Reference `architecture.md` for detailed information

## Code Review Checklist
When reviewing or generating WaSQL code, verify:

✅ **Variables set in controller**: `$items = getDBRecords(...);`  
✅ **Proper inline PHP syntax**: `<?=$variable;?>`  
✅ **Use $_REQUEST not $_POST**: `$_REQUEST['name']`  
✅ **Global $PASSTHRU for routing**: `global $PASSTHRU;`  
✅ **Global $USER for auth**: `global $USER;`  
✅ **renderViewIf for conditionals**: Never `<?php if: ?>`  
✅ **All conditionals in view blocks**: `<view:name>content</view:name>`  
✅ **encodeHtml for user data**: `<?=encodeHtml($user['name']);?>`  
✅ **Semicolons in inline PHP**: Required for all statements  

---

# Real-World Patterns (verified against ~100 production WaSQL sites)

The following is distilled from the actual sites in `postedit/postEditFiles`. These are the idioms that appear across real, shipped WaSQL applications. Prefer them over the simpler examples above when they conflict.

## ⚠️ Corrections to older docs
- **`isLoggedIn()` and `hasPermission()` do NOT exist.** They appear in `quick_reference.md` but are used in **zero** real sites and are not defined in the framework. Use `isUser()` (login check) and `isAdmin()` (admin check) — these are the real functions (used in ~700 and ~300 files respectively).
- **`getDBRecord`/`getDBRecords` filter by `_id`, not `id`.** Primary keys are `_id` (leading underscore). `getDBRecord(['-table'=>'items','_id'=>$id])`.
- **`is_numeric()` works, but the framework idiom is `isNum()`** (used ~4000× vs 48× for `is_numeric`). See the DB-write gotcha below.

## Database access — the core mental model
Options are ONE associative array. **Keys starting with `-` are directives; keys without a dash are data** — an implicit `column = value` WHERE filter for reads/counts/deletes, or the column values to write for add/edit.

```php
// dashless _id is a WHERE filter:
$rec  = getDBRecord(['-table'=>'items','_id'=>$id]);
// dashless keys are the columns being written:
$ok   = editDBRecord(['-table'=>'items','-where'=>"_id={$id}",'status_id'=>3,'name'=>$name]);
$newid= addDBRecord(['-table'=>'items','name'=>$name,'active'=>1]);
```

Full data-access function set (all have a verbose `database*` alias, e.g. `databaseGetRecords`; the short `*DB*` form is standard):
- `getDBRecord($opts)` / `getDBRecordById($table,$id)` — one record.
- `getDBRecords($opts)` — array of records.
- `getDBCount($opts)` — integer count.
- `addDBRecord($opts)` — insert; **returns the new numeric id, or an error STRING on failure.**
- `editDBRecord($opts)` / `editDBRecordById($table,$id,$values)` — update.
- `delDBRecord($opts)` / `delDBRecordById($table,$id)` — delete.
- `dbAddRecords($db,$table,['-recs'=>$rows])` — bulk insert.
- `databaseListRecords($opts)` — render an HTML data-grid (see below). **Preferred over the older `listDBRecords` alias**, which is being phased out; use `databaseListRecords` in new code.
- `executeSQL($sql)` — run raw SQL (used in crons/DDL).

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
(Formerly `listDBRecords` — that alias still works but is being replaced; write `databaseListRecords`.) Two modes: `['-list'=>$prefetchedRecs]`, or query mode with `-table`. Presentation keys: `-listfields` (columns to show), `-action`, `-tableclass`, `-tableheight`, `-hidesearch`, `-searchfields`, `-sorting`, `-export`. Per-column overrides use a dashless `fieldname_options` array with HTML attrs and `%_id%` token substitution. **Keep the (often lengthy) option array in a `functions` helper, not inline in the controller — see "Thin controller" below:**
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

## Page-field roles — think MVC (thin controller)
The page record's fields map onto MVC roles; respect the separation:
- **`controller` = the Controller.** Keep it THIN — it decides *what* happens: routes on `$PASSTHRU`, checks auth, picks the view with `setView()`, and calls functions to fetch/build data. It should read like a table of contents, not an implementation.
- **`functions` = the Model.** Put the real work here: DB queries, `databaseListRecords` grids, computed data, business logic — especially anything with lengthy option arrays. The controller calls a named helper (`$grid = pageThingGrid();`) instead of inlining a 10-line `['-table'=>…]` array.
- **`body` (`<view:...>` blocks) = the View.** Presentation only; it consumes variables the controller set and calls `renderView`/`renderEach`.
```php
// controller (thin): route + delegate
switch(strtolower($PASSTHRU[0])){
  case 'pages': $grid = pageSamplePagesGrid(); setView('tab_pages',1); break;  // lengthy params live in functions
  default:      setView('default'); break;
}
// functions (model): the lengthy call lives here
function pageSamplePagesGrid(){
  return databaseListRecords(['-table'=>'_pages','-listfields'=>'_id,name,_edate','-order'=>'_edate desc','-limit'=>12]);
}
```

## Views & templates (deeper than the basics)
- A `body` field is a set of named `<view:name>...</view:name>` blocks. **Defining a view never outputs it** — WaSQL extracts every block into a registry and the block text is removed from where it sits. It only appears when a `renderView`/`renderViewIf`/`renderEach` call names it, or `setView()` selects it as the page output.
- **Nesting `<view:>` blocks is purely cosmetic** (for readability). An inner block still only renders where something explicitly calls it. Don't expect an inline nested view to appear in place.
- Render functions: `renderView($name[,$data[,$var]])`, `renderViewIf($cond,$name[,$data[,$var]])`, `renderViewIfElse($cond,$a,$b,...)`, `renderViewSwitch($val,$map,...)`, `renderEach($name,$array[,$var])`. The view name and loop-var name are independent: `renderEach('photo',$product['photos'],'photo')`.
- `getView($name)` returns a view's raw string (used to feed `-fields`/`-listview` in form options). Wrap in `evalPHP(getView(...))` when the view layout contains PHP to evaluate.

### `setView($name)` vs `setView($name, 1)` — critical
- **The second arg `1` clears all previously-set views**, making `$name` the only view rendered. `setView($name)` (no `1`) is **cumulative** — it ADDS to the list, so multiple `setView` calls render multiple views in order.
  ```php
  setView('default');              // list = [default]
  if(isUser()){ setView('user'); } // list = [default, user] — BOTH render
  setView('login', 1);             // list = [login] — clears the rest, only login renders
  ```
- **The first arg can be a string (one view) OR an array (multiple views)** — `setView(['header','content','footer'])` sets several at once (combine with `1` to also clear anything set before: `setView(['header','content'], 1)`).
- For AJAX/partial responses and login/error short-circuits you use `setView($name, 1)` so only that one view is rendered; always follow with `return;`. (The chrome-less output for AJAX comes from the `/t/1/` blank template, not from this arg — see below.)

### Templates
- A page is wrapped by the `_templates` record named in its `template_id` field. Templates have the same `body`/`functions`/`css`/`js` fields and drop the page in with `<?=pageValue('body');?>`.
- Template meta helpers (`templateMetaTitle/Description/Keywords/Image/Site`, `templateActiveMenu`) are **per-site copies defined in each template's `functions`**, not framework built-ins — they read from `global $PAGE`.
- A page's own `css` and `js` fields are **auto-injected by the framework** when the page renders. Do NOT manually `<link>`/`<script>` a page's own css/js.

### Cross-page includes & shared functions
- `includePage('topnav')` / `includePage('bugshoney/webhook/add/.../{$id}')` — render another page (with optional passthru segments) inline.
- `loadDBFunctions('functions_common')` (or an array of names) — load another page's `functions` field into scope so its helpers are callable. **Convention: put shared helpers in a page like `functions_common` and `loadDBFunctions` it** (commonly done in the template's `functions`).
- Asset bundles: `minifyCssFile('wacss,bulma')` / `minifyJsFile('wacss,bulma')` return a URL to a combined minified bundle. `wacss` (the framework CSS/JS) is almost always included.
- `<translate>Text</translate>` wraps UI strings for localization; the framework substitutes the translated string.

## The `/t/{id}/` route (essential for AJAX)
URLs take the form `/{page}/{action}/{arg1}/{arg2}...` → `$PASSTHRU[0]`, `[1]`, `[2]`... A `/t/{templateId}/` prefix selects a specific template by id. **Template id 1 is a blank template** (no header/footer/chrome), so **`/t/1/` is what makes AJAX partials return only the inner content** — every `data-nav` / `ajaxGet` / AJAX form `action` that loads into a div uses `/t/1/...`. Pair it with `setView($view, 1)` in the controller so only that one view renders inside the blank template.

## Auth reality
```php
global $USER; global $PASSTHRU;
if(!isUser()){ setView('login',1); return; }      // not logged in
if(!isAdmin()){ setView('no_access',1); return; }  // logged in but not admin
```
- `$USER` fields: `_id`, `username`, `email`, `firstname`, `lastname`, `user_type`, plus JSON `_ex` variants (`user_type_ex`, `filters_ex`). Read `$USER['field']` directly.
- `userLoginForm(['-action'=>'/'.pageValue('name')])` renders the login form. `userValue('field')` echoes a user field in templates.
- There is **no group/role function** in real use; per-page allow-lists are done with `in_array($USER['username'], [...])` or a `$PAGE['settings_ex']['admins']` map. Logoff via URL `/?_logoff=1`.

## Client-side JS
- **Standardize on the `wacss.*` library.** All new client-side JS should call the framework's `wacss.*` methods rather than the older bare global helpers or hand-rolled functions. When a `wacss.*` method exists for what you need (nav, form post, tabs, conditional fields, modals, toasts…), use it — do NOT reinvent it. Bare legacy globals (`ajaxSubmitForm`, `ajaxGet`, `submitForm`) still work for existing code but are not for new code, and writing your own DOM helpers when a `wacss.*` one exists is a mistake.
- **AJAX nav (the workhorse):** `<a data-nav="/t/1/page/action" data-div="target_id" onclick="return wacss.nav(this);">`. Optional `data-setprocessing="0"` (spinner toggle), `data-onload="jsToRunAfterLoad();"`.
- **Tabbed navigation — use the built-in, do NOT hand-roll a highlight helper.** Add `data-tab="1"` to a `wacss.nav` anchor and it does two things: AJAX-loads `data-nav` into `data-div` AND calls `wacss.setActiveTab(this)` to move the active-class among the sibling tabs automatically. Never write your own `setActiveTab`/`swapTab` JS — that was a past mistake:
  ```html
  <nav class="tabs">
    <a data-tab="1" data-nav="/t/1/page/greeting" data-div="content" onclick="return wacss.nav(this);">Greeting</a>
    <a data-tab="1" data-nav="/t/1/page/features" data-div="content" onclick="return wacss.nav(this);">Features</a>
  </nav>
  <div id="content"></div>
  ```
  `setActiveTab` accepts either the classic `w_tabs` structure (`<ul><li><a>…</a></li></ul>` — toggles the `<li>`) OR flat anchor/button tabs (`<nav><a>…</a></nav>` — toggles the `<a>` itself). It auto-detects whether siblings use the `is-active` or `active` class and toggles that one.
- **AJAX form submit (preferred idiom): `wacss.ajaxPost`.** `<form action="/t/1/page/action" onsubmit="return wacss.ajaxPost(this,'target_div');">` — response injected into the div. We are standardizing on the `wacss.*` library, so use `wacss.ajaxPost(this,'div')`, NOT the older bare `ajaxSubmitForm(this,'div')` (still works, but don't write new code with it). Full-page submit: `submitForm(this)`.
- **`data-displayif`/`data-readonlyif` require `wacss.formChanged` wired to the form's `onchange`** — the conditional show/hide is re-evaluated on change, so the form tag needs `onchange="wacss.formChanged(this,event);"`. Without it the fields never toggle:
  ```html
  <form action="/t/1/page/action" onchange="wacss.formChanged(this,event);" onsubmit="return wacss.ajaxPost(this,'target_div');">
  ```
- Direct calls: `ajaxGet(url, targetDivId, {setprocessing:0})`, `wacss.ajaxGet(url,div,params)`.
- Other useful `wacss.*`: `wacss.modalPopup`/`modalClose`, `wacss.toast`, `wacss.copy2Clipboard`, `wacss.initTabs`/`setActiveTab`, `wacss.pagingExport`, `wacss.initDatePicker`, `wacss.initSignaturePad`, `wacss.speak`.
- `pushData($data, 'csv', 'file.csv')` — stream a typed download to the browser (CSV export is the main use).

## Styling / CSS
- **Use framework classes, don't invent them.** Just like the `wacss.*` JS push, we are standardizing styling onto the framework's own classes. The canonical stylesheet is **`wfiles/css/extras/wacss_v2.css`** — `wacss_`-prefixed classes that use **Bulma-style `is-*` modifiers**. If the site loads **Bulma**, prefer Bulma's equivalents (same `is-*` modifier convention). Never hand-invent class names (e.g. `w_btn`, `w_input`, `w_table` do NOT exist), and don't reach for the **legacy** `wfiles/css/wasql.css` classes (`.w_button`, `.w_button_black`, …) in new code.
- Canonical `wacss_v2.css` classes:
  - **Button:** `class="wacss_button is-primary"` (modifiers: `is-primary`/`is-info`/`is-success`/`is-warning`/`is-danger`/`is-link`/`is-light`/`is-dark`, sizes `is-small`/`is-medium`/`is-large`, plus `is-rounded`/`is-fullwidth`/`is-outlined`). Group buttons in a `.wacss_buttons` container (gap utilities `wacss_gap3/5/10/15`). Bulma equivalent: `class="button is-primary"`.
  - **Input / control:** `class="wacss_input"` (pass via the `buildForm*` `'class'` param), wrappers `.wacss_control`. Bulma: `class="input"`.
  - **Table:** `class="wacss_table is-striped"` (also `is-bordered`/`is-hoverable`/`is-fullwidth`), wrapper `.wacss_table-container`. For `databaseListRecords` set `'-tableclass'=>'wacss_table is-striped'`. Bulma: `class="table is-striped"`.
  - **Utilities:** spacing `wacss_top10`/`wacss_bot20`…, alignment `wacss_align-left`/`-center`/`-right`.
- **Only write custom page `css` for genuinely page-specific layout** that no framework class covers — reach for `wacss_v2.css`/Bulma first, and remember the page's own `css` field is auto-injected (don't `<link>` it yourself).

## Form building
Two signatures across the `buildForm*` family:
- Simple: `buildForm{Type}($name, $paramsArray)` — Text, Textarea, Date, Time, Datetime, Hidden, File, Signature, etc.
- Choice: `buildForm{Type}($name, $optsArray, $paramsArray)` where `$optsArray` is a `value=>label` map — Select, MultiSelect, Checkbox, Radio, Combo, ButtonSelect, StarRating.

`$paramsArray` keys: `class`, `style`, `required`, `placeholder`, `value`, `message` (select prompt), `onchange`, `readonly`, `disabled`, `width`, `height`, and for files `path`/`autonumber`. Most common: `buildFormSelect` (~700×), `buildFormText`, `buildFormTextarea`, `buildFormDate`, `buildFormMultiSelect`, `buildFormCheckbox`, `buildFormFile`. (No `buildFormEmail`/`buildFormNumber`/`buildFormSubmit` — submit is a plain `<button type="submit">`.)

### `addEditDBForm($opts)` option keys
- Form-level (`-`): `-table`, `-fields`, `-action`, `-focus`/`-nofocus`, `-order`, `-where`, `-format`, `-formname`, `-hidefields`, `-editfields`, `-preform`/`-postform`, `-pretable`/`-posttable`, `-onsubmit`, `-noguid`. Bare `_id` toggles edit vs insert mode (present = edit).
- Per-field: `fieldname_options => [...]` (richest — keys `inputtype`, `class`, `style`, `displayname`, `required`, `message`, `tvals`, `dvals`, `value`, `values`, `width`, `height`, `-display`, `-format`, `readonly`, `onchange`, `placeholder`), or the shorthands `fieldname_class`, `fieldname_required`, `fieldname_style`, `fieldname_value`, `fieldname_displayname`, etc.
- **`tvals`/`dvals`** = the true-values / display-values for a select/radio/checkbox (newline- or comma-separated, parallel lists). Field metadata is often seeded in the **`_fielddata`** table (`tablename`, `fieldname`, `inputtype`, `tvals`, `dvals`, `width`).
- The `[fieldname]` bracket placeholder inside a `*_fields` view is replaced with that field's rendered input control.

### Form processing helpers
- `processFileUploads()` — call in the controller before saving; moves `$_FILES` to disk and populates `$_REQUEST['file_abspath']`.
- `getCSVFileContents($file)` — CSV file → array of record arrays. `arrays2CSV($recs[,'-noheader'=>1])` — inverse. `setFileContents($file,$data)` — write (auto-serializes arrays).
- `verifyForm` is NOT used — validation is `required` attributes plus manual controller checks.

### How `addEditDBForm` saves — the save is GLOBAL, not tied to the target page
`addEditDBForm` only *renders* the form. On submit it carries hidden `_table` + `_action` fields, and the framework saves the record at **bootstrap** (`index.php`: `if(isset($_REQUEST['_action'])){ processActions(); }`) — **before** the page controller runs and regardless of which page/route the form posts to. Consequences:
- **`-action` can point at ANY route.** The save happens no matter what; the action route's only job is to render the *response* (typically a refreshed list/section). You do NOT need to call `addEditDBForm` again on the receiving page to persist the edit.
- So the idiomatic "edit → refresh a section" flow is: point `-action` at a route that re-queries and re-renders, and `wacss.ajaxPost` the form into the div you want replaced. Use `-nocache` on that re-query so the just-saved row is included.
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

### Section-refresh pattern (one view serves full-page render AND AJAX partial)
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
- **Close the launching modal on success via `data-onload` on the reloaded partial.** When the form was opened in the `centerpop` modal, put `data-onload="wacss.centerpopClose();"` on the section's root element. `data-onload` runs after the AJAX content is injected, so on a *successful* save the fresh section re-renders (closing the modal); if the save fails the form re-renders instead (modal stays open). This beats calling close in the form's `onsubmit`, which would close the modal before knowing the result:
  ```html
  <view:calendar_section>
    <div class="container" data-onload="wacss.centerpopClose();">
      ...
    </div>
  </view:calendar_section>
  ```

### `data-*` attributes
- `data-displayif="field:value"` / `data-readonlyif="..."` (938× — very common) — conditional show / read-only, re-evaluated by `wacss.formChanged` (the form's `onchange` must call it — see the Client-side JS section). Value optional (bare = truthy), comma-separated = OR. **Requires the form to have a control literally named `field`.**
  - **Plain field:** for a normal form field named `mood`, it is just `data-displayif="mood:curious"` — NOT `data>mood`.
  - **Nested JSON field (the `scope>field` form):** the `>` addresses an attribute *inside* a JSON field. `data-displayif="data>send_email:Y"` means "the JSON field named `data` has attribute `send_email` == Y" — so `scope>attr` requires an actual JSON field named `scope` (commonly `data`, `account`, or `meta`). Only use `scope>` when the control really is a JSON field with sub-attributes; for a flat field use the bare name. E.g. `data-displayif="meta>type:gb,gb_notes"`.
- `data-confirm` (confirm dialog before action), `data-format` (input formatting), `data-required` (conditional required), `data-toggle`/`data-target` (show/hide), `data-tab`, `data-tip`/`data-tooltip`.

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

## Config & environment
- **`isDBStage()` is the ONLY environment switch** (no `isDev`/`isProd`). Returns true on the staging/dev DB. Branch data caps, mail redirection, and hardcoded IDs on it: `if(isDBStage()){ $params['to']=$USER['email']; }`.
- **Two config sources, don't confuse them:** static/secret/connection config lives in **`config.xml`** → read via `$CONFIG['...']` / `$DATABASE['DBNAME']` (never hardcode API tokens). Site-editable branding/company/website settings live in the DB → read via `commonGetSetting('group','key')` (e.g. `commonGetSetting('company','name')`).

## Utility helpers worth knowing
`printValue($x)` (dump to HTML — the universal debug tool), `debugValue($x)` (log without halting), `sortArrayByKeys($recs,['col'=>SORT_ASC])`, `truncateWords($str,160)`, `removeHtml($str)`, `encodeURL($str)`, `formatMoney($n)`, `commonFormatPhone($x)`, `getFileExtension($path)`, `getFileContents`/`setFileContents`, `isNum`/`isEmail`/`isDate`/`isAjax`/`isSpider`/`isMobileDevice`, `commonStrlen` (multibyte-safe — prefer over `strlen`, ~1900×), `encodeJson`/`decodeJson` (framework wrappers over json_encode/decode). Email: `sendMail(['to'=>..,'subject'=>..,'message'=>..])` or the branded wrapper `commonSendMail(...)`. Dates: there is no framework wrapper — native `strtotime()`/`date()` is the norm.

## Critical gotchas
- **DB writes return the new numeric id on success, or an error STRING on failure. Never test truthiness — test `isNum()`:**
  ```php
  $id = addDBRecord([...]);
  if(!isNum($id)){ $error = $id; setView('error'); return; }  // $id holds the error message
  ```
- **`_ex` columns are JSON decoded to arrays.** A DB text column holding JSON (`meta`, `settings`, `user_type`, `filters`) is decoded into a sibling `*_ex` key: `$PAGE['meta_ex']=json_decode($PAGE['meta'],true);`. Read the `_ex` array; write back the encoded raw column. **Always pass `true` to `json_decode`** — the whole codebase assumes associative arrays.
- **`sendMail` body key is `'message'`, not `'body'`/`'html'`.** Wrong key sends empty mail silently.
- **Debug breadcrumbs are left commented in production** (`//echo printValue($_REQUEST);exit;`) — idiomatic, re-enabled ad hoc; don't "clean them up".
- **`$_REQUEST`, `$PAGE`, `$USER`, `$PASSTHRU` are all accessed as arrays** and (except `$_REQUEST`) require `global` in functions.

---

## Additional Resources
- `ai_patterns.md` - Corrected code patterns and examples
- `quick_reference.md` - Function reference and common patterns (note: its `isLoggedIn`/`hasPermission` are wrong — see corrections above)
- `architecture.md` - Complete technical documentation
- `postedit.md` - How AI should work on a PostEdit-mirrored WaSQL site: launch Chrome in debug mode, resolve a site from `postedit.xml`, screenshot it via the DevTools Protocol, and make edits through the auto-syncing PostEdit file mirror

Remember: WaSQL's database-driven architecture and unique syntax are its strengths. Help users embrace this approach with the correct syntax patterns rather than fighting against it.