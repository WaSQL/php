# WaSQL core bugs — fix-later list

Framework bugs found while building on WaSQL, written down so they can be fixed
deliberately rather than worked around forever. Everything here lives in **core files
shared by every WaSQL site** (`php/common.php`, `php/database.php`,
`wfiles/js/extras/wacss.js`), which is why none of it was changed as part of the site
work that found it.

**Before fixing anything here:**

- These files are shared by hundreds of sites — a fix is a framework change, not a site
  change. Each entry below is scoped to be as small as possible for that reason.
- **JS fixes require re-minifying `wacss.min.js`** through the normal build step, or
  nothing changes for any site (pages load the minified bundle).
- Page `css`/`js` field changes on a live site also need `?_menu=clearmin` to bust the
  `w_min` bundle. Not relevant to the core files themselves, but relevant to testing.
- Line numbers are from the working copy on **2026-08-17** for bug 1, and **2026-08-18**
  for bugs 2-8; re-grep before editing in case the file has moved on.

Severity key: **A** = silently produces a wrong/broken result with no error anywhere.
**B** = throws or breaks only in a specific configuration. **C** = wart, works but
surprises whoever hits it.

---

## Still open

Eight. Bugs 1, 2 and 3 are one family and worth reading together: **the data layer's
failure mode is an empty result**, so a broken query, a syntax error and "no rows" are the
same value. Bug 4 is unrelated and much smaller.

Bugs 1 and 3 are deliberate decisions rather than defects — read the reasoning before
"fixing" them. Bug 2 is a straightforward defect and, notably, would be *survivable* if
1 and 3 were addressed: it only costs hours because it fails silently. Bugs 4 and 5 are
independent: 4 is a missing option, 5 is a UI collecting a value nothing reads. 6 is the
one to fix first of the rest — a documented timeout that does nothing is the difference
between a slow third party being an annoyance and being an outage. 7 and 8 are latent:
neither is biting today, both will bite whoever hits them next.

### 1. `dbQueryResults()` reports a failed SELECT as an empty result set — severity A (arguably by design)

**Where:** e.g. `php/extras/databases/postgresql.php:3100-3128` —
`postgresqlQueryResults()` does `return array()` for both a failed connect and a failed
`pg_query`, leaving the reason in `$DATABASE['_lastquery']['error']`.

**Symptom:** `select * from no_such_table` is indistinguishable from a table with no
rows. A page reports "0 rows" and looks like it worked. This was live in the dexpdq `mcp`
server's `run_query` tool until 2026-08-17, and it also meant a circuit breaker watching
for backend failures could never see one on postgres/hana/mysql.

**Cause:** the documented contract ("returns an error STRING on failure") holds for some
paths but not the ordinary query-failed path.

**Status:** half fixed. The accessor side is done — `dbLastError()` now works (see the
fixed list below), so a caller *can* tell the two apart. The engines' return contract is
unchanged and is the larger question: returning the error string as documented would
break every caller that `foreach`es the result, so it has to land in all ~12 engine files
at once, as a deliberate decision, not one file at a time.

**Current workaround (in pages):** check `dbLastError()` immediately after the call; `''`
means genuinely zero rows.

### 2. A reserved word as a column name makes the whole table unreadable — severity A

**Where:** `php/database.php:11076` `getDBQuery()` — line **11086**
`else{$loopfields=array_keys($info);}` and line **11091** `array_push($fields,$field);`.

**Symptom:** every read of the table through `getDBRecord()`/`getDBRecords()` returns an
**empty array**, while `select * from that_table` over raw SQL returns the rows perfectly.
It reads as *"the record does not exist"*.

Reproduced 2026-08-18: `imago_import_jobs` had a column named **`cursor`**. The row was
plainly there in raw SQL; `getDBRecord(array('-table'=>'imago_import_jobs','_id'=>1))`
answered empty every time. Renaming the column to `page_cursor` fixed it outright.

**Cause:** `getDBQuery()` never issues `select *`. With no `-fields` it enumerates **every**
column name out of `getDBFieldInfo()` and pushes each one into the select list
**unquoted**, so the generated statement is:

```sql
select _id,_cdate,_cuser,...,cursor,options,... from imago_import_jobs where _id=1
```

`CURSOR` is a MySQL reserved word, so that is a syntax error. The failure then travels the
same path as bug 1 — the error is left in the driver and the helper hands back an empty
array — so nothing anywhere says "syntax error".

**Why it costs so much time:** the symptom points at the wrong layer entirely. "The record
does not exist" sends you to permissions, `-nocache`, and whether the insert committed.
Nobody's first thought is the column *name*, and the table works fine in every SQL client
you check it with.

**Fix (small):** backtick the identifiers in `getDBQuery()` when building the select list —
per engine, since the quoting character differs (MySQL `` ` ``, Postgres/ANSI `"`,
MSSQL `[ ]`). `databaseDataType()` is the existing precedent for per-engine differences.
Care needed: the same loop also emits computed columns (`UNIX_TIMESTAMP(x) as x_utime`),
which must **not** be quoted as a whole — only the bare column names.

**Cheaper partial fix:** if quoting is too invasive, make the failure loud (bug 4) and this
one stops being a mystery even though it still breaks.

**Workaround until then:** do not name a column with a reserved word. The ones most likely
to be reached for in app schema: `cursor`, `order`, `group`, `key`, `range`, `rank`,
`condition`, `interval`, `lead`, `lag`, `usage`, `read`, `write`, `system`.

---

### 3. `addDBRecord()`/`editDBRecord()` report failure as a *string* — severity A

**Where:** `php/database.php` — the documented contract: these return the new id (or
affected-row count) on success, and the database's own **message as a string** on failure.

**Symptom:** success and failure are the same PHP expression, distinguishable only by
*type*, and a naive `if($id){...}` treats every failure as a success. Worse, the obvious
defensive check is also wrong: sniffing the string for the word "error" misses most real
MySQL messages —

```
Data too long for column 'resolution' at row 1
Duplicate entry '3' for key 'uidx_x'
Incorrect date value: '' for column 'due_date'
Cannot add or update a child row
Out of range value for column 'story_points' at row 1
```

None of those contain "error". Imago shipped a version that reported all of them as
successes, so the API answered `200` for rows that were never written.

**Cause:** deliberate, and the same design decision as bug 1 on the write side.

**What the workaround costs:** every serious app ends up writing this, which is a fair
signal it belongs in core —

```php
function imagoWriteFailed($result){
    if($result===false || $result===null){return true;}
    if(is_string($result) && !isNum($result)){return true;}   // any non-numeric string
    return false;
}
```

…plus a second helper to keep the DB's message out of the user's face, because that string
ends with the **whole failing statement** — table, columns, and WHERE clause — and
concatenating it onto a toast or a 422 hands all of that to whoever mistyped a number.
(Imago's `imagoWriteError()` sends it to `debugValue()` instead, which only renders on a
stage DB or with `?debug=1`.)

**Fix:** whatever shape is chosen for bug 1 should cover this too — the two are one
decision about how the data layer reports failure, not two. An exception, or a
distinguishable error object, removes both workarounds and the "is it a number?" question
with them.

---

### 4. `postBody()`/`postJSON()` can only POST — severity B

**Where:** `php/common.php:22453` `postBody()` — line **22548**
`curl_setopt($process,CURLOPT_POST, true);`, and no `CURLOPT_CUSTOMREQUEST` anywhere in the
function. `postJSON()` (22383) and `postXML()` both route through it.

**Symptom:** a full REST **client** cannot be built on core. `postJSON()` is otherwise
exactly right — raw JSON body, content-type, encoding, timeouts, SSL, cookies, redirects,
and HTTP basic auth via `-authuser`/`-authpass` — but four of the five verbs a REST API
needs are unreachable:

| verb | needed for |
|---|---|
| GET | every read |
| PUT | Jira issue update |
| PATCH | ServiceNow record update |
| DELETE | removing a sub-resource |

**Consequence:** Imago's connectors call curl directly in the page layer
(`imagoSyncHttp()`), duplicating auth, timeout and error handling that `postBody()` already
does properly. Not a hardship for one app; it is the wrong default for the framework, and
the next integration will duplicate it again.

**Fix (genuinely one line, plus a guard):** honour a `-method` param —

```php
if(isset($params['-method']) && strlen($params['-method'])){
    curl_setopt($process,CURLOPT_CUSTOMREQUEST,strtoupper($params['-method']));
}
else{
    curl_setopt($process,CURLOPT_POST, true);   // unchanged default
}
```

Defaulting to POST when `-method` is absent keeps every existing caller behaving exactly
as it does now, so this is additive rather than a behaviour change.

**Note:** `postURL()` is a separate thing and is *not* the one to extend for this — it
builds its payload with `http_build_query()` and is correct as a form-encoded poster.

---

### 5. `buildFormFrequency()` collects daynames that `cron.php` never reads — severity A

**Where:** `php/cron.php:196-229` evaluates the `run_format` JSON and checks
**month → day → hour → minute**. It never looks at `dayname`.

`php/common.php:5248` `buildFormFrequency()` renders a **Daynames** row (Mon-Sun
checkboxes, `class="frequency_dayname"`) and writes them into the JSON as
`"dayname":[...]`, so the value is collected and stored and then ignored.

*(The widget itself was namespaced to `wacss.formSetFrequency` on 2026-08-18 — markup and
JS now agree — but that change is **local and not yet committed**, so a fresh clone will
not have it. This entry is only about the scheduler half, which is unaffected either way:
re-checked against the working copy and `cron.php` still contains no `dayname`.)*

**Symptom:** a schedule of "weekdays only, every 10 minutes" ticks Mon-Fri in the UI,
saves without complaint, and then **runs every day**. Nothing reports anything: the job
fires, the log looks healthy, and the only clue is that it also ran on Sunday.

Every `run_format` WaSQL writes carries a `dayname` key, so the data model advertises
weekday scheduling that does not exist.

**Cause:** the check was never added. Note `day` in the JSON is `date('j')` — day of the
**month** — so it is not a substitute; there is currently no way to express a weekday at
all through the JSON path. (The third scheduling mode, `run_format` as a `date()` format
plus `run_values`, can match a weekday with format `N` and values `1,2,3,4,5`, but it is
whole-value matching, so it cannot be combined with "every 10 minutes".)

**Fix (small, and symmetrical with the checks either side of it):** add a dayname branch
inside the existing nest, between day and hour —

```php
if(isset($json['dayname'][0])){
    // buildFormFrequency numbers Mon=0..Sun=6; date('N') is Mon=1..Sun=7
    $cdayname=(int)date('N')-1;
    if($json['dayname'][0]==-1 || in_array($cdayname,$json['dayname'])){
        // ... existing hour check goes here
    }
}
else{ /* ... existing hour check, unchanged, for rows with no dayname key ... */ }
```

⚠ **Mind the numbering.** `buildFormFrequency()` emits `0=>Mon … 6=>Sun`
(`common.php:5361`), which matches neither `date('w')` (Sun=0) nor `date('N')` (Mon=1).
Getting this off by one would silently shift every weekday schedule by a day, which is
worse than the current bug because it would look like it worked.

**Also check:** rows written before the fix carry `"dayname":[-1]`, which the branch above
treats as "any day" — so existing schedules keep their current behaviour.

**Workaround until then:** do not tick daynames; they do nothing. Imago's connector
schedule card says so in the UI rather than letting somebody set one and trust it.

---

### 6. `postBody()` throws away the caller's `-timeout` — severity B

**Where:** `php/common.php:22453` `postBody()`. Line **22478** honours the parameter —

```php
if(isset($params['-timeout'])){
    curl_setopt($process, CURLOPT_TIMEOUT, $params['-timeout']);
}
```

— and then line **22549**, near the end of the same function, overrides it
unconditionally:

```php
curl_setopt($process,CURLOPT_TIMEOUT, 600);
```

**Symptom:** every call gets a **ten minute** timeout no matter what it asked for.
`postJSON($url,$json,array('-timeout'=>30))` documents a 30 second limit, sets it, and
then waits ten minutes. Because `postJSON()` and `postXML()` both route through
`postBody()`, this affects every JSON and XML poster in the framework.

**Why it matters more than it looks:** a timeout is the one thing standing between a slow
third party and a wedged process. A cron scheduled every five minutes that calls a
hanging endpoint will pile up runs for ten minutes each. The parameter existing and being
documented makes it worse, not better — the caller believes they are protected.

`CURLOPT_CONNECTTIMEOUT` (line 22474) is **not** overridden, so `-timeout_connect` does
work. Only the total-time limit is lost, which is the one that matters for a server that
accepts the connection and then stalls.

**Fix:** make the late line a default rather than an override —

```php
if(!isset($params['-timeout'])){
    curl_setopt($process,CURLOPT_TIMEOUT, 600);
}
```

Ten minutes is a long default for a fallback, but changing it is a separate decision;
this fix alone makes the documented parameter work without altering any existing
behaviour that did not pass one.

---

### 7. `cron.php` lowercases `run_cmd`, so a cron's passthru loses its case — severity C

**Where:** `php/cron.php:332` `$lcmd=strtolower(trim($cmd));`, then line **345**
`$parts=preg_split('/\/+/',$lcmd);` and line **358** `if($stripped){$tmp[]=$part;}` — the parts pushed
into `$PASSTHRU` come from the **lowercased** string, not from `$cmd`.

**Symptom:** `run_cmd` of `cron_import/CustomerFeed/AB12cd` delivers
`$PASSTHRU = ['customerfeed','ab12cd']`. Anything case-sensitive downstream — a record
key, a base64 or hex token, a filename on a case-sensitive filesystem, a project key
compared with `===` — silently gets the wrong value. The job runs, so nothing looks wrong.

**Cause:** the lowercasing exists to match the page name against `$pages` case-insensitively,
which is correct. Splitting the *already lowercased* copy to build the passthru is the
overreach.

**Fix:** match on the lowercased copy, but take the passthru from the original —

```php
$parts=preg_split('/\/+/',$cmd);          // original case
foreach($parts as $part){
    $part=trim($part);
    if(!strlen($part)){continue;}
    if(isset($pages[strtolower($part)])){   // compare lowercased
        $stripped=1; $crontype='Page'; continue;
    }
    if($stripped){$tmp[]=$part;}            // preserve case
}
```

**Impact today:** low — Imago's `cron_sync/dis` is matched case-insensitively on purpose,
so it is unaffected. This is recorded because the next cron that passes an identifier will
be bitten, and the symptom (a job that runs and quietly does the wrong thing) is expensive
to trace back to here.

---

### 8. `commonStrlen()` has no `commonSubstr()` counterpart — severity C

**Where:** `php/common.php`. `commonStrlen()` exists and is the documented,
multibyte-safe way to measure a string. There is no matching substring helper — a grep
for `function commonSubstr` finds nothing.

**Symptom:** any app that needs to truncate a string safely writes its own
`mb_substr`-with-fallback wrapper. Imago now has one (`commonSubstr_imagoSync()`), named
awkwardly precisely to avoid colliding with the core function if it ever arrives.

**Fix:** add the obvious sibling —

```php
function commonSubstr($str,$start,$length=null){
    if(function_exists('mb_substr')){
        return ($length===null)?mb_substr($str,$start):mb_substr($str,$start,$length);
    }
    return ($length===null)?substr($str,$start):substr($str,$start,$length);
}
```

Not urgent, and worth doing next time the string helpers are touched: an app that reaches
for plain `substr()` on user text because no framework helper exists will cut a multibyte
character in half, and that only shows up in the one language nobody tested in.

---

## Already fixed in core (for context, do not re-fix)

These were the same class of problem and were repaired earlier; listed so nobody chases
them again from an old bug report or an out-of-date site. **A site whose core predates
these dates will still show them, and `wacss.min.js` must be re-minified for the JS ones
to take effect.**

### 2026-08-17

- **`pie` charts had interaction hard-disabled — hovering a slice did nothing.** Core's
  default option set is deliberately lightweight (`events:false`,
  `tooltips:{enabled:false}`, `wacss.js` ~4050), which is harmless for `line`/`bar`/
  `horizontalbar`/`doughnut` because `initChartJs`'s update path replaces `config.options`
  moments after `initChartJsBehavior` builds them. **Pie is the exception:** pass 1 builds
  and registers it (`wacss.js` ~4255) and `initChartJs`'s `case 'pie':` update branch only
  rewrites data and labels, never options — so a pie kept `events:false` for its whole life,
  and a pie slice carries no value at all until you hover it. New helper
  **`wacss.chartjsWantsTooltips(el)`** holds the rule in one place: `data-tooltips="1"` /
  `data-tooltips="0"` wins if present, otherwise only `pie` is interactive. Applied in both
  builders — `initChartJsBehavior` (in the **defaults branch only**, so a page that supplied
  its own `<options>` still gets them verbatim) and the hardcoded `pconfig` in
  `initChartJs`'s pie branch, which no longer hardcodes `events:false`. Any chart type can
  now opt in with `data-tooltips="1"`, and a display-only pie opts out with
  `data-tooltips="0"`.
- **A chart in a fixed-height box ignored its height.** Nothing in core set
  `maintainAspectRatio`, so Chart.js held its own 2:1 ratio no matter what
  `style="height:260px"` said. Both passes (`initChartJsBehavior` ~4076,
  `initChartJs` ~4422) now set `maintainAspectRatio:false` **only** when the div carries an
  explicit inline height and the page's own `<options>` didn't already specify it — charts
  sized by a CSS class or their parent are deliberately left alone, since
  `maintainAspectRatio:false` on an auto-height container collapses the canvas to zero.
  > **Do not** "fix" the related `config.options=options` overwrite at ~4589 by making
  > `initChartJs`'s default match `initChartJsBehavior`'s. Two false leads were chased and
  > written up before this was understood: (a) `maintainAspectRatio` was never in core at
  > all, and (b) the `plugins.labels` block in the 4048-4075 default is **dead config** —
  > that key belongs to `chartjs-plugin-labels`, which is not in this repo; the plugin
  > actually loaded is `chartjs-plugin-datalabels`, which reads `plugins.datalabels` and
  > registers globally with `display:true`, so it draws regardless of `options`. What the
  > overwrite really discards is `events:false` / `tooltips.enabled:false` — it *restores*
  > interaction, and is the only reason line/bar/horizontalbar/doughnut charts with no
  > `<options>` have working tooltips. Matching the defaults would kill hover on all of
  > them.
- **`options` was an implicit global in `initChartJs`** — `4397`/`4400` assigned to a bare
  `options` that was never declared (the function declares only `let colors`/`let bcolors`),
  so non-strict mode created `window.options` and collided with any page variable of that
  name. Now `let options={};` alongside the colors.
- **A tag name inside an HTML comment broke the page.** The tag processors are plain regexes
  over the whole rendered document, so `<!-- the <chartjs> tag builds the dashboard -->` was
  matched as a real opening tag and everything up to the next genuine closing tag —
  including real markup and the real chart tag — was swallowed into the hidden `_data` div.
  Two new helpers, **`commonMaskHtmlComments($htm,&$map)`** / **`commonUnmaskHtmlComments($htm,$map)`**
  (`php/common.php`, just above `commonProcessChartjsTags`), swap comments for placeholders
  before matching and restore them on the way out. Applied to both
  `commonProcessChartjsTags` and `commonProcessDBListRecordsTags`, which had the identical
  flaw. Each processor has exactly two returns — an early `stringContains` bail that runs
  *before* masking, and one final return — so nothing can leak; the unmask deliberately
  happens **after** the "malformed tag detected" check so a commented-out tag name can't
  trip that either. Both tag regexes were tightened to `(\s[^\>]*?)?` at the same time, so
  `<chartjsfoo>` no longer matches `<chartjs`. Placeholders are themselves comments, so even
  a hypothetical unrestored one leaves valid, invisible markup. **Any other tag processor
  added later should mask the same way.**
- **`addEditDBForm`'s two `$forcedatts` lists had drifted.** The function renders fields in
  two loops and each carried its own copy of the `{fieldname}_{attr}` shorthand list, so
  `name_autofocus`, `x_autocomplete`, `x_help`, `x_text`, `x_path`, `x_autonumber`,
  `x_data-labelmap`, `x_min_displayname`, `x_max_displayname`, `x_onmousedown` and
  `x_onmouseup` worked in one branch and were silently dropped in the other. Both now call
  one **`getDBFormForcedAtts()`** (`php/database.php`, just above `addEditDBForm`). The
  merged list is the exact union of the two originals — verified nothing was lost from
  either — with the handful of within-list duplicates (`mask`, `required`, `readonly`)
  removed, which is a no-op since the list is only ever walked with an `isset()` test. The
  list is still deliberately **finite**: a general `{fieldname}_{anything}` passthrough would
  emit mistyped option keys as real HTML attributes with no error.
- **A `null` inside a dataset's data array killed the whole chart** (`wacss.js` update
  path ~4573, build path ~4738). Both loops treated every data point as a possible object
  carrying per-point colours; on a number `(5).pointBackgroundColor` is a harmless
  `undefined`, but on `null` the property read threw
  `TypeError: Cannot read properties of null (reading 'pointBackgroundColor')`, aborting
  the build and leaving an **empty sized box with a canvas in it** — no visible error.
  Both loops now `continue` on any point that isn't an object, so padding a series with
  `null` to line it up with the labels array is safe.
- **The two per-point-colour loops read different key casing** — update path read
  `pointBackgroundColor`/`backgroundColor` (camel), build path read
  `pointbackgroundcolor`/`backgroundcolor` (lower), so per-point colours applied on first
  build and vanished on refresh (or vice versa) depending on how the key was cased. Both
  now accept either.
- **`data-beginatzero` could never work, and `data-stacked` threw without `<options>`**
  (`wacss.js` update path 4593-4618, build path 4633-4664). All four `beginAtZero` guards
  were `undefined ==` where `!=` was meant, so the assignment only ran in the case where
  the thing it dereferences does not exist — and on the update path it wrote to
  **`lconfig`**, which is not in scope there (the only `let lconfig` in `initChartJs` is
  block-scoped inside the `if(foundchart==0){…}` build branch), raising
  `ReferenceError: lconfig is not defined`. `data-stacked` dereferenced
  `config.options.scales.yAxes[0]` with no check, throwing a `TypeError` whenever the page
  gave no `<options>`. Both attributes on both paths now go through one new helper,
  **`wacss.chartjsAxes(options)`**, which creates `scales.xAxes[0]`/`yAxes[0]`/`.ticks`
  only if missing, returns `options.scales`, and leaves page-supplied axis config intact.
- **The series colour palette is indexed without wrapping** (`php/common.php` ~8358) —
  `$colors`/`$bcolors` are fixed 15-entry arrays and `$i` is the dataset ordinal, so a
  chart whose SQL returned more than 15 distinct `dataset` values emitted PHP 8
  `Undefined array key` warnings and left the 16th series onward uncoloured. Now
  `$colors[$i % count($colors)]` / `$bcolors[$i % count($bcolors)]`.
- **`verboseTime($secs,1)` mis-rendered every unit boundary** (`php/common.php`
  ~26389-26409) — the comparisons were `>` where `>=` was meant, so a value sitting exactly
  on a boundary stayed in the smaller unit: `60`→`00:00:60`, `3600`→`00:60:00`,
  `86400`→`24:00:00`. All five are now `>=` (verified `60`→`00:01:00`, `3600`→`01:00:00`,
  `86400`→`1d 00:00:00`, `2629743`→`1m 00:00:00`, `31536000`→`1y 00:00:00`). Exact-minute
  values are the norm wherever a timeout or polling interval is displayed, so this was not
  a rare corner. Same edit: `$months` is now initialised (the notate branch's `if($months)`
  raised `Undefined variable $months` on PHP 8 for every duration under a month) and the
  verbose branch's `if(isset($months))` became `if($months)` so short durations don't start
  printing `0 months`.
- **`dbGetLastError()` could never return anything** (`php/database.php` ~885). It reads
  `$DATABASE['_last_']`, which is only ever written by `dbSetLast()` — a function nothing
  in the tree calls — while every engine file records into `$DATABASE['_lastquery']`. So
  the one documented way to tell "query failed" from "zero rows" was dead code.
  `dbGetLast()` now falls back to `_lastquery`, and **`dbLastError()`** was added as an
  alias, matching the header comment that already named it and the existing
  `dbLastQuery()` precedent.

### 2026-08-12

- `data-bordercolor` was ignored on the update path, so every line chart came out grey
  after a refresh (`wacss.js` ~4550); `pointBorderBolor` typo silently no-op'ing per-point
  border colours; the `dblistrecords` tag processor got the same `commonReplaceFirst()`
  fix as `chartjs` (`php/common.php` ~8639).

### 2026-07-28

- doughnut/pie built by `initChartJs` got a single `backgroundColor` instead of the
  per-slice array (solid rings); the multi-dataset SQL form died on PHP 8
  (`array_values($labels,JSON_…)` passing flags as a second argument, `php/common.php`
  8410/8413/8419); a `chart.min.js` lazy-load race left charts silently unbuilt; two
  byte-identical tags collapsed onto one generated DOM id (fixed with
  `commonReplaceFirst()`).
