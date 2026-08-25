# Rearview — build guide

A Chrome-history dashboard for a local WaSQL site: **top 5 visited today / last 30 days / last 90 days**, headline figures, an hourly activity strip, a domain rollup, a per-device breakdown, and one-click or bulk **erase from real Chrome history**.

This document is written so you can hand it to Claude Code on your own machine and say *"build this on my localhost WaSQL"*. It is a specification, not a tarball — it explains the constraints and the traps, because those are what make this non-obvious.

**How to use this file:** drop it in your WaSQL repo and tell Claude Code *"build Rearview on my localhost WaSQL, following rearview.md"*. Work through the parts in order; section 8 is how you prove it works.

Built and verified on Windows 11 + Chrome 151 + WaSQL on `http://localhost` + PHP 8.4.

---

## 1. What you need first

Check all of these before writing any code. Each one has bitten this build.

```bash
# 1. PHP must have sqlite3 (BOTH the CLI and the Apache SAPI - they can differ)
php -r 'var_dump(class_exists("SQLite3"), extension_loaded("pdo_sqlite"));'

# 2. Chrome's history db exists and is ~tens of MB
ls -la "$LOCALAPPDATA/Google/Chrome/User Data/Default/History"

# 3. Your WaSQL site answers on localhost
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/

# 4. A writable working folder (C:/temp is used if present, else the system temp)
ls -d /c/temp
```

If the Apache SAPI lacks sqlite3 the dashboard renders but every panel is empty — the page reports this explicitly, so trust the error rather than assuming you have no history.

---

## 2. The three constraints that dictate the whole design

Do not design around these until you have read them; every "obvious" simpler approach founders on one of them.

**a. Chrome holds an exclusive lock on `History` while it is running.** Not a write lock — you cannot even *read* it. A `SQLITE3_OPEN_READONLY` open fails with `database is locked`. Verify it yourself:

```bash
php -r '$d=new SQLite3("C:/Users/YOU/AppData/Local/Google/Chrome/User Data/Default/History",SQLITE3_OPEN_READONLY); echo $d->querySingle("SELECT count(*) FROM urls");'
```

So the dashboard **never touches the live file except to copy it**. It clones to a working db and reads the clone. A clone taken while Chrome runs passes `PRAGMA integrity_check` — this is safe.

**b. Because of (a), PHP can never delete anything.** Deleting has to happen inside Chrome, which means a **Chrome extension** calling `chrome.history.deleteUrl()`. There is no way around this: no admin rights, no scheduled task, no "apply when Chrome closes" trick. On a locked-down corporate box `schtasks /create` and `auditpol` are both denied, and you will be viewing the dashboard *in Chrome* anyway, so any "wait until Chrome exits" scheme never fires.

**c. Chrome Sync means this is not one machine's history.** Every signed-in device is merged into that one file, and Chrome only retains **90 days**. Deletions propagate back out to every device on their next sync. That is usually what you want, but it must be stated in the UI — it is not a local-only operation.

---

## 3. Architecture

```
Chrome (live History.db, locked)
   │  hourly file copy                    ┌───────────────────────────┐
   ├─────────────────────────────────────►│ C:/temp/rearview_<prof>.db│
   │                                      └───────────┬───────────────┘
   │                                                  │ reads
   │                                      ┌───────────▼───────────────┐
   │   chrome.history.deleteUrl()         │ WaSQL page  _pages/index  │
   ◄──────────────────────┐               │ controller/functions/body │
                          │               └───────────┬───────────────┘
              ┌───────────┴──────────┐                │ renders
              │ Rearview extension   │◄───postMessage─┤
              │ background.js (MV3)  │────reply──────►│ page js
              │ bridge.js (content)  │                └───────────────┘
              └──────────────────────┘
```

Reads go through the clone (fast, safe, no lock fight). Writes go through the extension (the only thing allowed to delete). The page owns all orchestration.

---

## 4. Part A — the Chrome extension

Put it somewhere **stable** — Chrome reloads it from disk at every start, so `C:/temp` is a bad choice. Use `C:/Users/<you>/chrome-extensions/rearview/`.

### Why postMessage and not `externally_connectable`

An unpacked extension gets a **new random id every time it is loaded**. `chrome.runtime.sendMessage(EXTENSION_ID, …)` would therefore need reconfiguring on every machine and after every reload. Instead the extension injects a content script into `http://localhost/*` that relays `window.postMessage` to its service worker. **The page never needs to know the extension id** — which is exactly what makes this shareable with no per-person setup.

The content script also stamps `data-rearview="<version>"` on `<html>` at `document_start`, giving the page a synchronous presence check.

### `manifest.json`

```json
{
  "manifest_version": 3,
  "name": "Rearview",
  "version": "1.3.0",
  "description": "Bridges the local WaSQL history dashboard to Chrome's history API so it can erase entries from live history.",
  "permissions": ["history"],
  "host_permissions": ["http://localhost/*", "http://127.0.0.1/*"],
  "background": { "service_worker": "background.js" },
  "content_scripts": [
    { "matches": ["http://localhost/*", "http://127.0.0.1/*"], "js": ["bridge.js"], "run_at": "document_start" }
  ],
  "action": {
    "default_title": "Rearview - open the history dashboard",
    "default_icon": { "16": "icons/icon16.png", "32": "icons/icon32.png", "64": "icons/icon64.png" }
  },
  "icons": { "16": "icons/icon16.png", "32": "icons/icon32.png", "64": "icons/icon64.png" }
}
```

Two things people miss: without an `"action"` block the extension has **no toolbar button at all** and cannot be pinned; and `chrome.tabs.query({url:…})` silently returns nothing without `host_permissions`, because Chrome hides tab urls otherwise.

The icon sizes are **16/32/64**, not Chrome's usual 16/32/48/128, because that is what the source iconset ships (see below). Chrome accepts any set of sizes and scales for the slots you omit. Whatever you declare, make sure every referenced png actually exists: Chrome **refuses to load an unpacked extension** whose manifest names an icon file it cannot find.

### `bridge.js` (content script)

```js
(function () {
	const CHANNEL = 'rearview';
	try {
		document.documentElement.setAttribute('data-rearview', chrome.runtime.getManifest().version);
	} catch (e) { /* documentElement not ready */ }

	window.addEventListener('message', function (ev) {
		if (ev.source !== window) { return; }
		const d = ev.data;
		if (!d || d.channel !== CHANNEL || d.direction !== 'request') { return; }
		// forward the payload wholesale so new actions need no change here
		const msg = Object.assign({}, d.payload || {}, { channel: CHANNEL });
		chrome.runtime.sendMessage(msg, function (resp) {
			const err = chrome.runtime.lastError;
			window.postMessage({
				channel: CHANNEL, direction: 'response', id: d.id,
				payload: err ? { ok: false, error: err.message } : resp
			}, window.location.origin);
		});
	}, false);
})();
```

### `background.js` (service worker)

The **concurrency pool is the important part**. The first version used `Promise.all` over a 150-url batch; that swamped the history service, batches ran past the page's timeout, and **5,050 of 7,367 urls silently survived an "erase all"**. Bound it:

```js
const CHANNEL = 'rearview';
const POOL = 8;

function eraseOne(url) {
	return new Promise(function (resolve) {
		chrome.history.deleteUrl({ url: url }, function () {
			const err = chrome.runtime.lastError;
			resolve(err ? { url: url, ok: false, error: err.message } : { url: url, ok: true });
		});
	});
}

async function erasePool(urls) {
	let next = 0, erased = 0, failed = 0, firstError = '';
	const worker = async function () {
		while (next < urls.length) {
			const res = await eraseOne(urls[next++]);
			if (res.ok) { erased++; }
			else { failed++; if (!firstError) { firstError = res.error; } }
		}
	};
	const crew = [];
	for (let i = 0; i < Math.min(POOL, urls.length); i++) { crew.push(worker()); }
	await Promise.all(crew);
	return { ok: true, erased: erased, failed: failed, error: firstError };
}

chrome.runtime.onMessage.addListener(function (msg, sender, sendResponse) {
	if (!msg || msg.channel !== CHANNEL) { return false; }
	switch (msg.action) {
		case 'ping':
			sendResponse({ ok: true, version: chrome.runtime.getManifest().version });
			return false;
		case 'deleteUrl':
			eraseOne(msg.url).then(function (r) { sendResponse(r.ok ? { ok: true } : { ok: false, error: r.error }); });
			return true;   // MUST return true to keep the channel open for an async reply
		case 'deleteUrls':
			erasePool(msg.urls).then(sendResponse);
			return true;
		default:
			sendResponse({ ok: false, error: 'unknown action: ' + msg.action });
			return false;
	}
});

chrome.action.onClicked.addListener(function () {
	chrome.tabs.query({ url: 'http://localhost/*' }, function (tabs) {
		const hit = (tabs || []).find(function (t) { return t.url && t.url.indexOf('/index') !== -1; });
		if (hit) { chrome.tabs.update(hit.id, { active: true }); }
		else { chrome.tabs.create({ url: 'http://localhost/index' }); }
	});
});
```

Forgetting `return true` in an async case is the classic MV3 bug: the callback fires into a closed channel and the page just times out.

### Icon

No drawing and no build step — reuse an icon **already in your WaSQL repo**. `wfiles/iconsets/` is a stock icon set bundled with the framework, so the file you need is on disk the moment you clone WaSQL. From the extension folder:

```bash
WASQL=/c/wasql                      # your repo root
mkdir -p icons
for S in 16 32 64; do cp "$WASQL/wfiles/iconsets/$S/info.png" "icons/icon$S.png"; done
```

That set has no 48 or 128, which is why the manifest declares 16/32/64. Any other name in `wfiles/iconsets/64/` works the same way — `history.png` (a clock with a rewind arrow) fits this tool better than `info.png` if you would rather it looked purpose-built.

### Loading it

`chrome://extensions` → **Developer mode** on → **Load unpacked** → pick the folder → reload the dashboard tab. Content scripts only inject at page load, so an already-open tab will keep reporting "not loaded" until you refresh it.

---

## 5. Part B — the WaSQL page

One page (`_pages` record named `index`, or any name you like) using all five fields. Standard WaSQL MVC: **controller routes, functions model, body views**.

### Chrome's schema — the three facts that matter

```sql
-- the visits FK column is named "url", NOT "url_id"
CREATE TABLE urls(id INTEGER PRIMARY KEY, url LONGVARCHAR, title LONGVARCHAR,
                  visit_count INTEGER, typed_count INTEGER, last_visit_time INTEGER, hidden INTEGER);
CREATE TABLE visits(id INTEGER PRIMARY KEY, url INTEGER NOT NULL, visit_time INTEGER NOT NULL,
                    transition INTEGER, app_id TEXT, originator_cache_guid TEXT, ...);
```

1. **`visits.url` is the foreign key**, not `url_id`. Joining on `url_id` returns "no rows" with no error.
2. **Timestamps are WebKit microseconds since 1601-01-01 UTC**: `unix = wk/1000000 - 11644473600`. The intermediate fits comfortably in a 64-bit int.
3. **`urls.visit_count` is a LIFETIME total across every synced device.** Per-window counts must be `COUNT(v.id)` over `visits`, or all three cards show near-identical numbers.

### The device dimension

`visits.originator_cache_guid` is **empty for visits made on this machine** and carries the originating sync client's guid for anything synced in. Chrome stores no device *names* here (they live in the Sync LevelDB), so infer labels:

| guid | label |
|---|---|
| empty | This computer |
| has `app_id LIKE '%android%'` | Android phone |
| anything else | Synced device, labelled by the host it visited most in 30 days |

Filter clause, bound rather than interpolated:

```php
function rearviewDeviceClause($device,$alias='v'){
	if(!strlen($device) || $device==='all'){return array('sql'=>'','guid'=>null);}
	if($device==='local'){return array('sql'=>" AND COALESCE({$alias}.originator_cache_guid,'')=''",'guid'=>null);}
	return array('sql'=>" AND {$alias}.originator_cache_guid=:guid",'guid'=>$device);
}
```

### What the search deliberately excludes

Two filters that are not obvious but matter:

- **`u.hidden = 0`** — Chrome marks some rows hidden (redirect targets and similar). They are never shown to the user, so offering to erase them is noise.
- **The dashboard's own origin.** Searching puts the term into this page's own query string (`http://localhost/?q=facebook`), which Chrome then records. Without excluding `http://<host>/%` the tool matches itself and offers to erase its own trail. Bind it as a parameter alongside `:like`.

### The core query

```sql
SELECT u.id AS url_id, u.url, u.title, COUNT(v.id) AS hits, MAX(v.visit_time) AS last_wk
FROM visits v JOIN urls u ON u.id = v.url
WHERE v.visit_time >= :startWebkit AND u.hidden = 0 AND u.url LIKE 'http%' {deviceClause}
GROUP BY u.id
ORDER BY hits DESC, last_wk DESC
LIMIT 5
```

### Config — derive it, never hardcode it

The single biggest portability trap. Apache may run as a service account, so `LOCALAPPDATA` can point at the wrong profile; fall back to scanning. `Local State` is a JSON profile registry that also holds the **friendly profile names** shown in Chrome's profile menu, so you get a proper picker for free.

```php
function rearviewChromeRoot(){
	$local=getenv('LOCALAPPDATA');
	if(strlen((string)$local)){
		$try=str_replace(chr(92),'/',$local).'/Google/Chrome/User Data';
		if(is_dir($try) && is_file($try.'/Local State')){return $try;}
	}
	$best=''; $bestTime=0;
	foreach((array)glob('C:/Users/*/AppData/Local/Google/Chrome/User Data',GLOB_ONLYDIR) as $dir){
		if(!is_file($dir.'/Local State')){continue;}
		if(filemtime($dir.'/Local State') > $bestTime){$bestTime=filemtime($dir.'/Local State'); $best=$dir;}
	}
	return $best;
}
// profiles: glob "$root/*/History", name them from Local State -> profile.info_cache[dir].name
```

Show the profile picker only when more than one profile has a `History` file.

### Cloning — guard it with a stamp file

Clone hourly, not per request. Guard with a sidecar file holding **the live file's mtime at the moment you cloned**:

```php
$stampFile=$cfg['work'].'.stamp';
$liveStamp=(int)filemtime($cfg['live']);
$lastStamp=is_file($stampFile) ? (int)trim(file_get_contents($stampFile)) : 0;
if(($force || $stale) && $liveStamp !== $lastStamp){ copy(...); file_put_contents($stampFile,$liveStamp); }
```

Do **not** compare against the clone's own mtime: erasing a single tile writes to the clone, making it look newer than Chrome's file and suppressing a clone you genuinely needed.

Copy `History-journal` alongside if present (and delete a stale one if not) so sqlite can recover a copy taken mid-transaction. Journal mode is `delete`, not WAL.

### Controller routes

```php
$rearviewQ=isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
$rearviewDevice=isset($_REQUEST['device']) ? trim($_REQUEST['device']) : 'all';
switch(strtolower($PASSTHRU[0])){
	case 'clear':      /* drop from clone after the extension erased it */ setView('tiles',1); return;
	case 'refresh':    /* force re-clone */                                setView('tiles',1); return;
	case 'device':     /* redraw for another device, no re-clone */        setView('tiles',1); return;
	case 'search':     /* preview panel */                                 setView('search',1); return;
	case 'searchurls': /* JSON url list for bulk erase */                  setView('searchjson',1); return;
	default:                                                               setView('default'); break;
}
```

All AJAX routes are called through the **blank template**: `/t/1/index/<action>`.

---

## 6. The bulk erase — get this right or it lies to you

The first working version reported success having erased **2,317 of 7,367** urls. Three things fix it.

**1. The url list comes from the server, never from the extension.** The page previews what matches, then hands the extension that exact list. The extension does no matching of its own, so a bulk erase can never remove more than what the user was shown.

**2. Verify and repeat.** A single sweep is not proof. After each pass, re-ask the server what still matches and run again while the count keeps falling. Deleting is idempotent, so re-sending an already-erased url is harmless — that is what makes the retry safe.

```js
erasePass: function (el, q, urls, pass, tally) {
	var verify = function () {
		rearview.searchUrls(q).then(function (data) {
			var left = data.count;
			// stop when clean, when a pass made no headway, or at the cap
			if (!left || left >= urls_this_pass_total || pass >= rearview.maxPasses) {
				rearview.eraseDone(el, q, tally, left, pass); return;
			}
			rearview.erasePass(el, q, data.urls, pass + 1, tally);
		});
	};
	// ...walk this pass in batches of 60 with a 120s timeout, then verify()
}
```

Two details decide whether this actually converges:

- **Chrome commits history deletions to disk asynchronously.** Verifying the instant the last batch returns can still see rows that are already gone. Wait ~2.5s before asking.
- **One fruitless pass is not proof of a floor.** A slow commit is indistinguishable from a url Chrome refuses, so only stop after **two consecutive** passes with no progress. Stopping on the first is what makes users click "Erase all" over and over.
- **`force` must genuinely force the re-clone.** If the clone is guarded by a stamp file (see below), the verification path has to bypass it, or it re-reads a stale copy and concludes nothing happened.

Simulated against a stub honouring only 70% per sweep AND reporting one snapshot behind reality, this converges to zero; the single-strike version stopped on the first verification and claimed thousands still matched.

**3. Never fire the refresh and the re-search together.** Refresh forces a re-clone; a search landing mid-rewrite reads a half-copied file and leaves a stale panel on screen. `wacss.ajaxGet` takes no completion callback, so rather than guessing with a delay, watch the target div:

```js
var obs = new MutationObserver(function () { obs.disconnect(); clearTimeout(guard); done(); });
obs.observe(document.getElementById('rearview_tiles'), { childList: true });
```

Show a real `<progress>` with a **measured** ETA (derived from completed batches, not a guess), count failures separately, and advance the bar on *handled* (`done + failed`) so it cannot stall at 95%.

---

## 7. WaSQL traps this build actually hit

Beyond the standard ones in `CLAUDE.md` and `wasql_reference.md`:

**Controller variables do NOT reach a nested view.** A view chosen by `setView()` renders in body scope and sees controller variables. A view reached via `renderView()` from inside another view sees **only `$params`**. It fails silently — `renderEach` gets an undefined array and emits an empty string, no error at all. Pass the model under **the same name the controller used**, so one view works in both paths:

```php
<?=renderView('tiles',$rearviewModel,'rearviewModel');?>
```

**A literal backslash may not survive being written into a page field.** Depending on how the file is written, `'\'` can collapse to `'\'` and silently break the next string. Build backslashes with `chr(92)` instead — this matters for the LIKE escape in the search:

```php
$bs=chr(92);
$like='%'.str_replace(array($bs,'%','_'),array($bs.$bs,$bs.'%',$bs.'_'),trim($q)).'%';
$where="u.url LIKE :like ESCAPE '{$bs}' OR u.title LIKE :like ESCAPE '{$bs}'";
```

**A page field's final `<?php` must be closed.** `evalPHP` matches islands with `/\<\?(.+?)\?\>/sm`, so an unterminated trailing block is never executed — it is **echoed as literal source** with no PHP error. `php -l` passes either way.

**`css`/`js` edits do not appear until the minify bundle is rebuilt.** Hit `?_menu=clearmin` after every style or script change, or you will debug CSS that was never served.

**Bulma's `.title`/`.subtitle` are overridden to 2.5rem** by wacss's helper block. Give headings a page-owned class.

**An inline `<script>` inside AJAX-loaded content never runs.** Everything here is driven by the page's own js reading elements by id, and `onclick=` attributes (which do work).

---

## 8. Verification — prove it, do not assume it

```bash
# page renders with no PHP errors and the expected component counts
curl -s http://localhost/index -o /tmp/rv.html
grep -icE "parse error|fatal|no view named|Undefined" /tmp/rv.html   # expect 0
grep -c "rv-tile" /tmp/rv.html                                       # expect 15 (3 cards x 5)

# AJAX partials come back without page chrome
curl -s "http://localhost/t/1/index/refresh" | grep -c navbar        # expect 0

# the bulk-erase JSON parses (use a term you know you have, e.g. google)
curl -s "http://localhost/t/1/index/searchurls?q=google" | php -r '$d=json_decode(trim(stream_get_contents(STDIN)),true); echo $d["count"]," urls\n";'

# the clone is not torn despite being copied from a locked file
php -r '$d=new SQLite3("C:/temp/rearview_default.db",SQLITE3_OPEN_READONLY); echo $d->querySingle("PRAGMA integrity_check"),"\n";'
```

Reference timings on the build machine: index **~0.9s**, device switch ~0.75s, forced re-clone ~0.9s, `searchurls` over 7,400 urls ~0.5s.

The extension half cannot be verified from a shell — load it and confirm the header badge reads **"Rearview vX.Y.Z connected"** in green. If it says "not loaded", you did not reload the tab after loading the extension.

---

## 9. Safety — put this in front of anyone you share it with

- Erasing is **irreversible**.
- It **propagates to every signed-in device** on their next sync. This is not a local-only operation.
- It does **not** touch Google's own record: if Web & App Activity is enabled, the same browsing is separately retained at `myactivity.google.com`.
- The dashboard has **no authentication**. It is intended for `http://localhost` on a single-user machine, where anything that can reach it is already running as you. If you serve it anywhere else, add `if(!isUser()){ setView('login',1); return; }` to the controller and bind Apache to `127.0.0.1` — the page displays and can destroy your entire browsing history.
- Chrome retains only **90 days**, so "all time" here means 90 days. A "This Year" card was built and removed for exactly this reason — it just duplicated the 90-day numbers.
