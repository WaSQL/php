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

## Views & templates (deep)
- A `body` field is a set of named `<view:name>...</view:name>` blocks. **Defining a view never outputs it** — WaSQL extracts every block into a registry and the block text is removed from where it sits. It only appears when a `renderView`/`renderViewIf`/`renderEach` call names it, or `setView()` selects it as the page output.
- **Nesting `<view:>` blocks is purely cosmetic** (for readability). An inner block still only renders where something explicitly calls it. Don't expect an inline nested view to appear in place.
- Render functions: `renderView($name[,$data[,$var]])`, `renderViewIf($cond,$name[,$data[,$var]])`, `renderViewIfElse($cond,$a,$b,...)`, `renderViewSwitch($val,$map,...)`, `renderEach($name,$array[,$var])`. The view name and loop-var name are independent: `renderEach('photo',$product['photos'],'photo')`.
- `getView($name)` returns a view's raw string (used to feed `-fields`/`-listview` in form options). Wrap in `evalPHP(getView(...))` when the view layout contains PHP to evaluate.
- **`renderView($name,$data)` exposes `$data` inside the view as `$params`** (the whole passed value — array, string, etc.). Access it as `$params['title']` etc., or rename it with the `-alias` opt (`renderView('addedit',$data,'row')` → `$row` in the view). There is also a `-format=>'addeditdbform'` opt that renders the view's `[fieldname]` layout as an `addEditDBForm` (pass `-table`).
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

### Templates
- A page is wrapped by the `_templates` record named in its `template_id` field. Templates have the same `body`/`functions`/`css`/`js` fields and drop the page in with `<?=pageValue('body');?>`.
- Template meta helpers (`templateMetaTitle/Description/Keywords/Image/Site`, `templateActiveMenu`) are **per-site copies defined in each template's `functions`**, not framework built-ins — they read from `global $PAGE`.
- A page's own `css` and `js` fields are **auto-injected by the framework** when the page renders. Do NOT manually `<link>`/`<script>` a page's own css/js. (Framework asset bundles and extras like `chart.min.js` are a different thing and fine to include.)

### Cross-page includes & shared functions
- `includePage('topnav')` / `includePage('bugshoney/webhook/add/.../{$id}')` — render another page (with optional passthru segments) inline.
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

## Core helper traps (verified)
- **A literal PHP close tag (`?` immediately followed by `>`) ANYWHERE in a page field truncates the rest of that field — including inside a single-quoted string, and including inside a `//` comment.** A page's `controller`/`functions`/`body` is source that WaSQL writes to `php/temp/{host}_php_{hash}.php`, so the ordinary PHP lexer rule applies: the close tag ends the PHP block, and everything after it in the field is dropped. The failure presents as `PHP Syntax Error … "unexpected string content"` on the *temp* file, with a line number that maps to nothing you recognise, and the token quoted back at you is a fragment of your own regex.
  - Bites hardest in HTML-processing regexes, where `/?>` is idiomatic: `preg_replace('#<\s*(script|style)\b[^>]*/?>#is',…)` truncates the field at that string. Write the optional slash as `/{0,1}` — identical semantics, no close-tag sequence: `'#<\s*(script|style)\b[^>]*/{0,1}>#is'`.
  - Same for a literal `'<?xml encoding="UTF-8" ?>'` (the standard `DOMDocument::loadHTML` UTF-8 hint): build it as `'<'.'?xml encoding="UTF-8" ?'.'>'`.
  - **And in `//` comments** — a `//` comment merely *describing* the trap is enough to trigger it. Spell it out in words ("a php close tag") rather than typing the characters.
  - **Exception: a `/* … */` BLOCK comment is safe** — the PHP lexer does not honour a close tag inside one. That's why the `@usage <?=pageFooBar($x);?>` line in a `/** … */` PHPDoc block is fine and why existing pages are full of them; don't "fix" those, and do keep new docblocks consistent with them. Everywhere else (strings, `//` comments, live code) the trap is real.
  - Confirm a suspected truncation by reading the generated file named in the error: `php/temp/{host}_php_{hash}.php`. It ends mid-statement exactly where your field went quiet. (WaSQL also strips `/** */` blocks when generating it, so line numbers won't match your source.)
- **`verboseTime()` returns a TRAILING SPACE.** Harmless where HTML collapses whitespace (`verboseTime($s).' ago'`), but visible the moment punctuation follows — `'every '.verboseTime($s).'.'` renders `every 21 days .` — and it doubles up inside a `title`/`alt` attribute, where whitespace is *not* collapsed. `trim(verboseTime($s))` whenever you concatenate punctuation or build an attribute.
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

## Bulma / wacss layout traps (verified)
- **A `.column` width class does nothing unless the row is flex at that breakpoint.** Bulma's `.columns` is `display:block` **below tablet** — it only becomes flex from 769px up, or at every width if you add **`is-mobile`**. So `<div class="columns is-multiline"><div class="column is-6-mobile">` puts every tile on **its own line** on a phone (each one 50% wide, stacked) rather than two per row. The working combination for a wrapping tile grid is all three: **`columns is-multiline is-mobile`** on the row + a per-breakpoint width on each column (`is-6-mobile is-3-tablet` → 2 per row on a phone, 4 from tablet up).
  - The converse also bites: `columns is-multiline is-mobile` with **bare** `.column` children (no width class) gives equal-flex columns that **do not wrap** — with 7-8 tiles the last one is clipped at the box edge on a desktop. Bare `.column` is fine up to ~5 items; past that, size them.
- **Check how the site styles a bare HTML tag before using one inside a sentence.** These sites set several inline-by-default tags to `display:block`, which silently breaks a paragraph into pieces: `<code>` inside a sentence renders as its own full-width band (add `display:inline` in the page `css`), and a `<a>` inside a table cell pushes an adjacent inline icon onto its own line (the `.sapcc_box table td a{display:inline;}` rule in the SAPCC page exists for exactly this). Same lesson as the `.title`/`.subtitle` `!important` trap in `CLAUDE.md`: when markup misbehaves, look at the bundle before adding your own layout.
- **`<progress class="progress is-small">` is the cheap in-table bar** — real Bulma, colour it with the same `is-danger`/`is-warning`/`is-success` modifier the rest of your states use, and give it a `min-width` or it collapses in a narrow column. Drop the whole cell on phones with `is-hidden-mobile` rather than letting a 6-column table squeeze.

## Chart.js (the `chartjs` extra)
The bundled chart library is **`/wfiles/js/extras/chart.min.js` — Chart.js v2.8.0** (use v2 option syntax: `options.legend`, `options.title`, `scales.yAxes:[{ticks:{beginAtZero,max}}]`, `cutoutPercentage`, `maintainAspectRatio`; NOT v3+). There is **no PHP charting engine** — rendering is client-side.

### The WaSQL way: the `chartjs` tag (start here)
Write a **`chartjs` tag** in the page body (or return one from a `functions` helper) and the framework does the rest. `commonProcessChartjsTags()` (`php/common.php`) runs in the render pipeline **after `evalPHP`**, so helper-returned tags are processed normally, and it runs for `/t/1/` AJAX partials too. It rewrites the tag into `<div data-behavior="chartjs" id="…">` + a hidden `#{id}_data` block, then `wacss` builds the Chart.js instance client-side and registers it as `wacss.chartjs[divId]`.

```html
<!-- SQL form: the framework runs the query. data-db picks the connection (default = site DB) -->
<chartjs data-type="bar" data-db="ddfa_postgres" data-onclick="myChartClick"
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
- **A `null` in a dataset's data array kills the whole chart.** Both builders loop the points doing `dataset.data[ds].pointbackgroundcolor` (`wacss.js` ~4573 / ~4737). On a number that's a harmless `undefined`; on `null` it throws `TypeError: Cannot read properties of null (reading 'pointBackgroundColor')`, the build aborts and you get an **empty sized box with a canvas in it** — no visible error. So never pad a series with nulls to line it up with the labels (the obvious way to stop a burndown's "actual" line at today). Just send a **shorter data array** than the labels array — Chart.js plots what it's given and stops.
- **On the update path, `data-bordercolor` is ignored — every LINE chart comes out default grey.** When `wacss.chartjs[id]` already exists (i.e. `initChartJsBehavior` built it first, which is the normal sequence), `initChartJs` takes an update path that rebuilds each dataset as `{backgroundColor, type, data, fill, pointBackgroundColor:[], pointBorderColor:[]}` (`wacss.js` ~4550) — **no `borderColor`**. Bar/doughnut are unaffected (they're coloured by `backgroundColor`), but lines lose their colour entirely. Page-level fix that doesn't touch core: after the build, read the colours back off the `#{id}_data` block and assign them yourself, then `chart.update()` — retry it on a couple of timers, since `chart.min.js` is lazy-loaded and the charts may not exist on the first tick.
- **NEVER write the literal tag name in an HTML comment** (or any output text). The processor is a regex over the rendered HTML: `<!-- builds the chartjs tags -->` is read as a real opening tag and swallows everything up to the next real closing tag, dumping your page markup into a hidden `_data` div. Use a PHP comment (`<?php /* … */ ?>`) instead. Same hazard for the other tag processors (`dblistrecords`, …).
- **An `<options>` block replaces wacss's default options wholesale.** Its defaults set `events:false` and `tooltips.enabled:false`, so supplying options is usually what you want — but then don't also use `data-stacked` / `data-title` / `data-beginatzero` / `data-legenddisplay` / `data-render`, which mutate `options` *after* yours is parsed (`data-stacked` replaces `options.scales` entirely, `data-render` assumes `options.plugins.labels` exists). Put stacking and axis config in your own JSON.
- **Supply `<options>` or the second pass silently flattens your chart.** `initChartJs`'s update path assigns `config.options=options` outright, and with no `<options>` block that `options` is just `{responsive:true}` — so `maintainAspectRatio:false` is dropped, Chart.js reverts to its 2:1 aspect ratio and ignores your sized box. Whenever the chart lives in a fixed-height container, the option set must be **complete** (`responsive`, `maintainAspectRatio`, `legend`, `tooltips`, `scales`, …), not a patch. Everything is a plain PHP array up to the `json_encode`, so per-chart tweaks are just array writes on the shared builder's return value (`$o['scales']['xAxes'][0]['display']=false;`).
- **One colour per *dataset*, in every form — you cannot colour bars individually.** `<dataset data-backgroundcolor="#hex">` is read with `getAttribute`, so it is a string; the update path's per-point `data[ds].backgroundColor` branch mutates that string and silently does nothing. Only `doughnut`/`pie` take the whole `<colors>` array (one colour per slice). For a bar chart where each bar wants its own colour, either switch form or hand-roll the canvas.
- **Colours in the single-query SQL form:** with more than one `dataset` value, core assigns each series a colour from a fixed rgba palette, so you can't get semantic colours (green success / red error) that way — use per-`<dataset>` `data-backgroundcolor` (JSON form, or SQL inside each `<dataset>`).
- **AJAX-refreshed sections:** only `wacss.initOnloads()` runs after an AJAX injection, not `wacss.init()` — so re-run the chart init from the injected partial's `data-onload` (`data-onload="wacss.initChartJs();"`). Builds are idempotent per div, so this is safe on every refresh.
- **Two identical chart tags used to collapse onto one DOM id.** The processor looped over each matched tag and replaced it with `str_replace($chartjs_tag,…)`, which rewrites *every* identical occurrence — so two tags whose markup matched character-for-character (easy: two cards showing the same numbers) produced two divs sharing one generated id, and the later loop pass found nothing left to replace. Fixed 2026-07-28 with `commonReplaceFirst()`. Symptom to recognise on an older core: `document.querySelectorAll('#'+id)` returns two elements, and anything that builds **by id** (e.g. `wacss.initChartJsBehavior(divId)`) draws a second canvas into the first div's box, overflowing onto whatever follows. Build with the **no-argument** call instead — it walks elements and honours `data-initialized`, so it is immune.
- **Deploying to a site with an older core** — four bugs were fixed 2026-07-28; a site whose `php/common.php` / `wacss.js` predate that will still show them, and `wacss.min.js` must be re-minified for the JS fixes to take effect:
  - doughnut/pie built by `initChartJs` (either path) got **one** `backgroundColor` instead of the per-slice array → **solid rings**. Bar/line were unaffected.
  - the multi-dataset SQL form died on **PHP 8**: `json_encode(array_values($labels,JSON_…))` passed the flags as a second argument to `array_values()` → `ArgumentCountError` (`php/common.php` 8410/8413/8419).
  - `chart.min.js` lazy-load race: `wacss.loadScript` is async and both initializers bailed on the same call with no retry, so charts could silently never draw. Now they re-try every 250ms up to `wacss.chartjsWaitsMax`. Belt-and-braces on an old core: load it with a plain synchronous `<script src="/wfiles/js/extras/chart.min.js">` in the page body (framework asset — fine to include; the page's own css/js fields are the auto-injected ones).
  - identical tags sharing a generated id (see the bullet above).
  - The other tag processors (`dblistrecords`, …) still use the same whole-string `str_replace` and so still have the shared-id/skipped-tag behaviour with byte-identical tags — `commonReplaceFirst()` is there to fix them the same way when someone hits it.
  - Page-level workaround if you can't update core: from a `data-onload`, deferred a tick so it runs after `wacss.init()`, destroy `wacss.chartjs[div.id]`, remove the div's canvases, `removeAttribute('data-initialized')`, then `wacss.initChartJsBehavior(div.id)` — that builder honours `<colors>`. Flag each div as done so refreshes only build new ones.
- **Fully hand-rolled alternative** (what to do if you want nothing to do with the above): give canvases a non-triggering class, stash the v2 config in a `data-chart` attribute via `htmlspecialchars(json_encode($cfg),ENT_QUOTES)`, and in your own JS destroy prior instances then `new Chart(cv.getContext('2d'),cfg)`. Race-free and idempotent, but you give up `data-onclick`, `data-db` and the SQL forms.

## Server / system health in PHP
The web PHP runs **as apache on the site's own Linux host**, so for a health/monitoring page you can read metrics locally without SSH:
- **Direct `/proc` reads** (most reliable): `file_get_contents('/proc/meminfo')` (MemTotal/MemAvailable, KB), `/proc/loadavg` (1/5/15m + running procs), `/proc/uptime` (secs since boot), `/proc/cpuinfo` (`model name`, count `^processor` lines). Plus native `disk_free_space($path)` / `disk_total_space($path)`.
- **Framework helper:** `loadExtras('system'); $info=getServerInfoLinux();` returns `load_averages`, `uptime`/`uptime_verbose`, `cpu_info`, `memory_usage` (with shell fallbacks). `php/extras/system.php`.
- **Shell when needed:** `cmdResults($cmd)` → `['stdout','stderr','rtncode','runtime']` (via `proc_open`).
- **Caveat:** apache often can't stat a DB's data dir (e.g. postgres `0700 /var/lib/pgsql`), so `disk_*_space` there returns false — fall back to reporting logical size (`pg_database_size`, `SUM(data_length+index_length)`).
- **DB server health:** MySQL uptime/threads via `performance_schema.global_status` (or `SHOW GLOBAL STATUS LIKE 'Uptime'` → `$rec['value']`), `@@max_connections`. PostgreSQL via a named connection: `dbGetRecord('conn', "SELECT pg_postmaster_start_time(), (SELECT count(*) FROM pg_stat_activity) …")`.

### A REMOTE host's OS metrics, when it exposes `file_fdw` probe tables
Some Postgres databases here publish `public.system_*` foreign tables (`file_fdw` over a `PROGRAM` option) so a plain `SELECT` returns that **database host's** `/proc`, `df`, `ps`, `ss`, `systemctl` and `journalctl` state — no SSH and no agent. `ccv2_dbsync` is the example (see `C:\git\sapcc\system_function.md` for the 17 tables and their columns); the SAPCC dashboard's *Server Health* panel is a worked implementation. Four rules generalise to any such table set, and they shape the code more than the SQL does:
- **Every `SELECT` forks a command on the far host and there is no stored history.** So nothing can be a trend line without your own sampler, and any panel must be honest that a counter is a since-boot total, not a rate. Roughly half the columns are counters (`system_stat_cpu`, `system_diskstats`, `system_net_dev`, `system_vmstat`); plotting them raw draws a line climbing forever.
- **Probe cost is per-SELECT, and the round trip dominates it** (~265ms each on a LAN vs ~1-25ms of actual shell work). Batch aggressively: pull each table into a **CTE once** and emit metrics as a `UNION ALL` of `(metric, value::text)` rows, and flatten the multi-row tables onto one common `(kind,label,v1,v2,v3,txt)` shape in a second query. Four queries beat fourteen by a second of page load. Give each UNION a **fixed literal first branch** (`SELECT 'probe' AS metric, 'ok' AS value`) — it pins the column names and types no matter which branches you composed in, and its arrival is your proof the query ran.
- **The table set differs per host, so ask the catalog first** and compose from what exists (`SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_name LIKE 'system%'` — match on `system%` and filter the underscore in PHP rather than fighting LIKE escaping in a heredoc). One missing table otherwise fails a whole batched query.
- **An empty result is ambiguous and must never render green.** The probes that shell out to optional tooling return **no rows** rather than erroring when the tool is missing or unreadable by the `postgres` OS user, so "0 failed units" and "could not look" are indistinguishable from SQL. Render those as neutral, say "returned nothing", and list them in a footnote.

---

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
