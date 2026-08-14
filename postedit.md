# postedit — how AI should work on a PostEdit-mirrored WaSQL site

This file tells an AI assistant (e.g. Claude) how to work on **any PostEdit-mirrored WaSQL site**: launch Chrome in debug mode, open the site named by `postedit.xml`, and make prompted edits by updating the local PostEdit files (which auto-sync to the database). Read it before starting PostEdit work. Screenshotting is opt-in, not part of routine startup or every edit — see "Ask before doing post-change QA" in `CLAUDE.md`.

Anything specific to one machine or one site (exact executable paths, a developer's blanket-permission grant, per-site logs) does **not** belong here — keep it in a personal, un-committed companion file outside the repo (see "Personal / machine-specific notes" at the bottom).

## Session startup — when the user says "work on {alias} {page}"

The prompt form is **`work on {alias} {page}`** (or "let's work on {alias} {page}"). Examples:
- `lets work on acme home`
- `work on demosite index`

Do these first:

1. **Resolve the alias → host.** Look up `{alias}` in `postedit/postedit.xml` among the `<host ... alias="{alias}">` entries and read that host's `name` attribute (the domain). The site URL is `https://{name}/{page}`. Hosts with `insecure="1"` use a self-signed cert (still `https://`; `curl -k`, Chrome ignores it). Some `name`s are bare hosts, IPs, or `host:port` — use them verbatim.
2. **Resolve the site's `db_id`:** call `mcp__wamcp__databases` to find the id matching `{alias}` (it usually matches, but not always — e.g. `dexpdq` → `dexpdq_mysql`). wamcp has no session-default database, so pass this `db_id` explicitly on every subsequent `mcp__wamcp__*` call (`query`, `schema`, `pagesrc`, `tables`, `fields`, `ddl`, `indexes`, `getdb`).
3. **(Only once actually needed)** recreate the screenshot helper: write `shot.js` (from the Appendix) to the session scratchpad — it's intentionally not stored in the repo, and not needed just to start the session. Node 22+ with built-in `fetch`/`WebSocket` is assumed.
4. **Start Chrome yourself** — launch a *visible* Chrome with the debug port on a **dedicated profile directory** so it works even if the user's normal Chrome is already open, then attach to it (both sides see the same window). Launch it in the background, non-headless, pointed at the resolved URL:
   ```
   <chrome-exe> --remote-debugging-port=9222 --user-data-dir="<chrome-debug-profile>" --no-first-run --no-default-browser-check --new-window "https://{name}/{page}"
   ```
   - `<chrome-exe>` — the Chrome/Chromium executable for this OS. On **Windows** it may be under **either** `C:\Program Files\Google\Chrome\Application\chrome.exe` **or** `C:\Program Files (x86)\Google\Chrome\Application\chrome.exe` — check both, or read the authoritative path from the registry `HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe`. macOS: `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`; Linux: `google-chrome` or `chromium`.
   - `<chrome-debug-profile>` — a **dedicated** profile dir **outside the repo and separate from your normal Chrome profile** (e.g. a temp dir). A dedicated dir (a) guarantees the debug port takes effect even if Chrome is already running, and (b) persists the site login/session across runs.
   - **Launch it as a detached, persistent process so the debug port actually binds** — NOT as a shell background job (`chrome … &`) that a wrapping agent/tool may reap before Chrome finishes initializing (a reaped launch = no port on 9222). On **Windows** use PowerShell `Start-Process "<chrome-exe>" -ArgumentList @("--remote-debugging-port=9222", …)`; on macOS/Linux use `nohup <chrome-exe> … &` / `setsid` / `open -a`.

   Wait ~5s, then confirm with `curl -s http://localhost:9222/json` that a page target for `{name}` exists. If a Chrome is already running on port 9222 for a different site, just `Page.navigate` its existing target to the new URL instead of launching a second instance. If the user prefers their own already-open Chrome, they can launch it with `--remote-debugging-port=9222` and you attach to that.
5. **Make sure the watcher is running** (edits only sync while it is) — see *Ensure the PostEdit watcher is running* below; launch it for `{alias}` if it isn't.
6. **Browser up + watcher running → ask what to work on.** No screenshot needed here — the developer already has the browser in front of them and will flag anything that looks off.
7. **Edit → auto-sync → ask before refreshing/screenshotting:** edit the PostEdit files; PostEdit auto-syncs to the DB; ask the developer whether they want to check it or want you to refresh/re-screenshot with a `?cb=<n>` cache-buster. (See "The edit loop".)
8. Everything else (WaSQL syntax, where things live, gotchas) is below and in the repo `CLAUDE.md`.

> **Permissions:** launching Chrome, querying the DB via wamcp, and editing files under `postedit/postEditFiles/{alias}` all run under whatever permission mode the user has set — respect it. A developer may pre-authorize this routine so you don't re-prompt each time; if so, that grant lives in their personal companion file, not here. The one hard prohibition from the repo `CLAUDE.md` always holds: **never `git commit` / `git push`.**

## Ensure the PostEdit watcher is running (auto-launch if not)

Edits sync to the DB **only while the watcher for that alias is running** (`php postedit\postedit.php {alias}` — a blocking loop that mirrors the DB down and pushes local saves back; the repo `p.bat`/`p` wrapper is just this one line, but **do NOT rely on `p.bat`** — launched via `Start-Process cmd` it often fails with `'p.bat' is not recognized`, so invoke `php` directly). Before editing:

1. **Detect it by process, not by the lock file.** A watcher is live for `{alias}` iff a `php` process's command line contains `postedit.php {alias}`:
   - Windows: `Get-CimInstance Win32_Process -Filter "Name='php.exe'" | ? { $_.CommandLine -like '*postedit.php {alias}*' }`
   - macOS/Linux: `pgrep -af "postedit.php {alias}"`
   There is also a PID lock file `postedit/{sanitized-host}_lock.txt` (host with non-alphanumerics stripped), but **lock files go stale** — a hard-killed watcher can leave its file behind (older builds orphaned it on CTRL-C). Treat the lock file as a hint only; confirm the PID inside it is a live `postedit.php` process before trusting it.
2. **If not running, launch it in its OWN persistent console** — never inside a blocking tool call: it loops forever and also reads STDIN to answer conflict prompts (`Overwrite? Y/N`, `Refresh Now? Y/N`).
   - Windows: from the repo root, `Start-Process cmd -ArgumentList '/k',"cd /d $(Get-Location) && php postedit\postedit.php {alias}"` (run the `php` command directly — the `cd /d` guarantees the relative `postedit\postedit.php` path resolves; `php` just needs to be on PATH — don't hard-code its install path, and use `PHP_BINARY` / `where php` if you ever need the exact executable). Do **not** use `p.bat {alias}` here — it fails to resolve in that spawned cmd. Then verify with the process check above (the lock file may lag a few seconds behind the running process).
   - macOS/Linux: run `php postedit/postedit.php {alias}` in a new terminal / `tmux` pane.
3. **⚠️ Launching re-syncs from the DB, destructively.** On startup the watcher **backs up `postEditFiles/{alias}` to `{alias}_bak`, deletes the working files, and re-downloads them fresh from the DB.** So auto-launching **discards any un-synced local edits** (recoverable from `{alias}_bak`). Only auto-launch when the local mirror has no pending changes; if it might, tell the user and confirm first.

## What a PostEdit site is

- These are **WaSQL sites**. WaSQL is database-driven: page logic lives in DB records (`_pages` table: `name`, `body`, `controller`, `functions`, `css`, `js`), not in files.
- **Use the full WaSQL guidance in `CLAUDE.md`** (repo root) to understand how to build and edit WaSQL pages — controller/functions/body/css/js roles, `renderView`, `setView`, `databaseListRecords`, `data-displayif`, `wacss.*` JS, `wacss_v2.css`/Bulma classes, the `/t/1/` blank-template AJAX route, etc. That file is the source of truth for WaSQL patterns.
- Live dev sites are often self-signed/local (`curl -k` / Chrome ignores it) and resolve via the local hosts file.

## The edit loop (PostEdit)

WaSQL records are mirrored to local files via **PostEdit**, under:

```
postedit/postEditFiles/{alias}/_pages/<page>/<page>._pages.<field>.<id>.<ext>
```

e.g. a page body = `postedit/postEditFiles/{alias}/_pages/<page>/<page>._pages.body.<id>.html`,
its css = `...<page>._pages.css.<id>.css`, controller = `...controller.<id>.php`, etc.
(Content tables work the same, e.g. `<table>/<name>/<name>.<table>.body.<id>.html`.) Which tables are mirrored is set by the host's `tables=` attribute in `postedit.xml` (defaults to `_models,_pages,_tables`).

### ⚠️ Mirror files are a MIX of CRLF and LF — never anchor a scripted edit on `\n` alone
Line endings come from whatever wrote the record, so on one site `desk._pages.functions.17.php` and `portal._pages.functions.16.php` are **CRLF** while `functions_sd._pages.body.15.php` and `reports._pages.functions.10.php` in the same mirror are **LF**. Consequences:

- A multi-line `old_string` copied out of a `Read` (which shows LF) silently matches **zero** times in a CRLF file. The `Edit` tool handles this for you; a hand-rolled `python`/`sed` replacement does not — it just reports "0 matches" and you go looking for a typo that isn't there.
- For scripted edits, normalise first and restore after: read with `newline=''`, remember `crlf = '\r\n' in s`, `s = s.replace('\r\n','\n')`, do the work against `\n` anchors, then convert back before writing. A regex anchor works either way if you separate lines with `\n\s*` (`\s` eats the stray `\r`).
- Check before you write, not after: `python -c "import io,sys;print('CRLF' if '\r\n' in io.open(sys.argv[1],encoding='utf-8',newline='').read() else 'LF')" <file>`.
- **Don't "fix" a whole file's endings** — that rewrites every line of the DB field for no behaviour change. Match the file you are in, and if you paste an LF block into a CRLF file, convert that file back to CRLF as a whole so it stays internally consistent.

**A running PostEdit watcher auto-syncs file saves back into the DB.** So the loop is:

1. **Edit** the local PostEdit file.
2. PostEdit **auto-updates the matching DB record** — no manual step. (Verify with a `wamcp` query if unsure, e.g. `SELECT css FROM _pages WHERE _id=<id>`.)
3. **Ask before refresh-and-screenshot.** Per `CLAUDE.md`'s "Ask before doing post-change QA" — check whether the developer wants to eyeball the result themselves or have you refresh Chrome and re-screenshot it. Only screenshot without asking if they've already said to keep iterating on your own for this task.

The user usually has Chrome open on the page being edited; after a save they can just refresh to see the change (the DB is already updated). **`body`/`controller`/`functions` edits show up on a plain refresh** — those are read from the DB on every request.

### ⚠️ PostEdit only syncs fields that were **non-empty at mirror time**

The watcher mirrors DB→disk on startup and then watches the files it wrote. A record field that was `NULL`/empty when it synced produces **no file**, and PostEdit has no idea that field exists — so **creating that file yourself does nothing**: the file sits on disk unsynced and a `wamcp` query shows the column still empty. (Verified: a brand-new `…_pages.css.6.css` never reached the DB, while an edit to an already-mirrored file synced within seconds.)

So when you have new `_pages`/`_templates` records created for you, ask for a **placeholder in every field you intend to use** (`<!-- stub -->`, `//stub`, `.stub{}`) before the watcher starts. If you discover a missing field mid-session: put a stub in it via the admin UI (the running watcher pulls the change down and creates the file), then edit the file. Restarting the watcher also works, but remember it re-syncs destructively.

### ⚠️ `css`/`js` edits need the `w_min` bundle busted (a page `?cb=1` does NOT do it)

A page's own `css`/`js` fields are compiled into a hashed minified bundle (`/w_min/minify_<a>_<b>.css` / `.js`), **not inlined**. Two caches sit in front of your edit and a cache-buster on the *page* URL defeats neither:

1. **The server-side static file.** `minify_css.php`/`minify_js.php` read the page/template records live from the DB but then **write the result to `{docroot}/w_min/minify_<a>_<b>.css`**, which Apache serves directly on every later request. The hash is derived from the *set of sources*, not their content — so it does **not** change when you edit `css`/`js`, and the stale file just keeps being served. `editDBRecord` on `_pages`/`_templates` tries to clear this (`minifyCleanMin()` in `php/database.php`) but only `if(function_exists('minifyCleanMin'))` — during a PostEdit sync the minify extra usually isn't loaded, so **nothing gets cleared**.
2. **The browser cache.** The bundle is served with `Cache-Control: public`, so Chrome reuses its copy even after the server file is regenerated.

Fix both, in order (run from the page's own context so the session cookies come along):

```js
// 1. regenerate the server-side bundle — take the hash straight out of the live tag
var l = [...document.querySelectorAll('link[rel=stylesheet]')].map(x=>x.href).find(x=>x.includes('w_min'));
var h = l.replace(/^.*minify_/,'').replace(/\.css.*$/,'');
await fetch('/php/minify_css.php?_minify_='+h, {cache:'no-store'});   // same for /php/minify_js.php with a script[src] hash
```
2. **Reload with the browser cache off** — CDP `Network.setCacheDisabled {cacheDisabled:true}` before `Page.navigate` (the `nav.js` pattern), *not* a query param on the page URL.

Symptom to recognize: new CSS rules have no effect (`getComputedStyle` shows the un-styled default) or a new JS function is `ReferenceError: … is not defined`, while a `wamcp` query confirms the `_pages` record already holds the new code.

### ⚠️ The 1px reflow nudge (baked into `shot.js` — you don't do it by hand)

Some WaSQL layouts don't settle until a `resize` event fires — AJAX-loaded tab content, sticky/frozen tables, flex reflow, Chart.js canvases, and (notably) a window navigated while the page still thinks it's another width. Without a nudge you can capture a **stale/unsettled layout** and misdiagnose it as a real bug.

**The canonical `shot.js` (Appendix) performs the nudge automatically** right before every capture — it toggles the emulated width +1px then back, firing `resize`. So the sequence is simply **edit → PostEdit syncs → navigate/refresh → run `shot.js`**; the settle step can't be forgotten because it lives inside the helper. (Proof it matters: a page navigated at 800px can still report `window.innerWidth === 390` until a 1px nudge recomputes it.) If you ever screenshot the user's own visible window some other way, replicate the toggle via CDP `Browser.setWindowBounds` ({width: w+1} then {width: w}).

## Adding a NEW page to a mirrored site (the mirror can't create records)

PostEdit only round-trips records that **already existed when it mirrored**. Writing a new file into `postEditFiles/{alias}/_pages/newpage/…` does nothing — there is no `_pages` row for it, so the watcher ignores it. And wamcp is read-only, so `INSERT` isn't available there either. The working sequence:

1. **Insert the `_pages` row directly** (a small `mysqli` script in the scratchpad; creds come from the host's `<database>` block in `config.xml`). Copy the column values from a sibling page — for an Imago-style site: `name`, `title`, `_template` (the UI template id, **not** 1), `page_type=0`, `postedit=1`, `synchronize=1`. Give **every** field (`body`/`controller`/`functions`/`css`/`js`) a non-empty stub: a field that is empty at mirror time never gets a file (see the empty-field rule above).
2. **Author the five fields as files** named exactly as PostEdit names them — `{page}._pages.{field}.{id}.{ext}` — and push their contents into the row with the same script (`UPDATE _pages SET {field}=? WHERE _id={id}`). Also `UPDATE _pages SET css_min=null, js_min=null` so the bundles regenerate.
3. **Restart the watcher** so it starts tracking the new record: kill the `postedit.php {alias}` process and relaunch it (see *Ensure the PostEdit watcher is running*). Its startup re-sync pulls the new page down into the mirror.
   - ⚠️ **Wait for the re-sync to actually finish before writing anything** — on a full mirror it takes **minutes**, not seconds. Poll for the new page's file to appear (`postEditFiles/{alias}/_pages/{page}/{page}._pages.body.{id}.html`) rather than sleeping a fixed 30s and assuming. Until the watcher is tracking the record, files you write there are silently ignored: the DB keeps the stub, `_edate` never moves, and it looks exactly like a broken watcher. (Verified 2026-08-06: a page created mid-session stayed on its 9-byte stub through two restarts because each check came before the re-sync completed.)
   - **Fallback when the mirror will not cooperate:** put the code in a page whose fields *already* sync and reach it with `loadDBFunctions('thatpage','functions')`. A shared helper library does not have to live in a page of its own.
   - ⚠️ **Do not try to push page source through `dasql`.** Its transport mangles payloads: HTML-ish tags are stripped, `\n` becomes a literal `n`, and a bare `/` anywhere in the payload is a PHP syntax error — which also rules out plain base64 until you switch to a URL-safe alphabet and build paths with `chr(47)`. Use the `mysqli` script from step 1.
4. **From that point the mirror is the source** — edit `postEditFiles/{alias}/_pages/{page}/…` and let it sync. Keep editing your scratchpad copies and you're maintaining a fork; diff before you copy anything over.

Verify each step in the DB rather than assuming (`select length(functions) from _pages where name='…'`), and before restarting the watcher confirm every mirrored file you edited already matches the DB — the restart **deletes and re-downloads** the working files.

## Database access (wamcp)

- No `setdb`/session default: call `mcp__wamcp__databases` once to resolve `{alias}` to a `db_id`, then pass that `db_id` on every `mcp__wamcp__query` (read-only SQL) / `mcp__wamcp__tables` / `mcp__wamcp__fields` call. If the alias isn't a valid db id, `mcp__wamcp__databases` lists the real ones.
- **wamcp is READ-ONLY.** Schema changes (`CREATE TABLE`, `ALTER`) and any `INSERT`/`UPDATE` need another path: the WaSQL admin SQL console, `mysql` CLI, or a one-off `mysqli` script. Keep the DDL in a versioned `.sql` file in the repo (as `imago_schema.sql` / `imago_wiki_schema.sql` do) rather than only in a throwaway script.
- Primary keys are `_id`. Audit cols `_cdate/_edate/_cuser/_euser`. System tables have a leading underscore (`_pages`, `_templates`, ...).

## Screenshots — DO THIS, NOT THAT

**Correct, reliable method: drive Chrome via the DevTools Protocol with device emulation.** Node 22+ has built-in `fetch`/`WebSocket`. The reusable `shot.js` is in the **Appendix** — write it to the scratchpad at session start. Pattern:

1. Launch: `<chrome-exe> --headless --disable-gpu --remote-debugging-port=9224 about:blank &` (wait ~3s).
2. Connect Node to `http://localhost:9224/json`, open the page's `webSocketDebuggerUrl`.
3. `Emulation.setDeviceMetricsOverride {width:390,height:844,deviceScaleFactor:2,mobile:true}` → `Page.navigate` → `Page.captureScreenshot {captureBeyondViewport:true, clip:{...scrollHeight}}`.
   - On Windows, the output path must be a **native Windows path** (e.g. `C:\...\out.png`), not a Git-Bash `/c/...` path, or Chrome errors "Access is denied".
4. To find horizontal-overflow culprits, `Runtime.evaluate` a snippet that lists elements whose `getBoundingClientRect().right > clientWidth`.

### Attach to the user's visible Chrome (shared view)

Best setup so **both sides see the same thing**: attach to the same visible instance from startup step 4 (or the user's own Chrome launched with a remote-debugging port) instead of a separate headless one. Then a screenshot is of the exact window the user is looking at, and you can navigate/refresh it for them.

- Connect Node to `http://localhost:9222/json`, pick the site's page target, drive it over its `webSocketDebuggerUrl` — `Page.navigate` to refresh after a save, `Page.captureScreenshot` to see the result. No `--headless`, no separate instance, no device-emulation needed unless you want to force a mobile viewport (`Emulation.setDeviceMetricsOverride`, then clear it so the user's view returns to normal).
- Don't kill this instance — it's the user's own browser (see the warning below).

If no Chrome is on port 9222, fall back to your own `--headless --remote-debugging-port=9224` instance for screenshots (CDP emulation method above), and the user refreshes their own window manually to follow along.

**Do NOT** rely on `chrome --headless --screenshot --window-size=390,...` for mobile checks. On Windows, OS DPI scaling can make the real CSS viewport wider than requested (e.g. ~485px while the PNG is cropped to 390px) — content looks cut off on the right when it actually isn't. This caused a wrong diagnosis once. Always emulate the viewport via CDP instead.

## ⚠️ Never broadly kill Chrome processes

To clean up headless Chrome, **do not** filter by empty window title (e.g. `Get-Process chrome | where MainWindowTitle -eq '' | Stop-Process`). Chrome's renderer/GPU/utility subprocesses all have an empty `MainWindowTitle`, so that filter kills the **user's visible Chrome** too. Instead: run headless Chrome and either let it exit on its own, or kill only by the specific PID you launched (capture the PID at launch), never by a title/name-wide match. For headless cleanup, kill by the **unique `--user-data-dir`** (see Appendix).

## Personal / machine-specific notes

Keep these **out of this committed file** and in a personal companion file outside the repo:
- your machine's exact Chrome executable path, dedicated debug-profile dir, and scratchpad path;
- any standing/blanket permission you've granted the AI for the routine PostEdit workflow;
- per-site facts, layout quirks, and a running log (one subsection per site).

## Appendix — `shot.js` (CDP screenshot helper)

Not stored in the repo (don't put it under `postedit/postEditFiles/**` — that would sync it into the DB). Write it to the session scratchpad at startup, then run with Node 22+.

- **Headless mode:** `<chrome-exe> --headless --disable-gpu --remote-debugging-port=9225 --user-data-dir=<unique-scratch-dir> about:blank &` (wait ~3s), then `node shot.js 9225 "https://{name}/<page>?cb=1" <out.png> 390`.
- **Attach-to-visible-Chrome mode:** with the debug Chrome from startup step 4 (or the user's own on `--remote-debugging-port=9222`), run `node shot.js 9222 "<url>" <out.png> 390` — it navigates that tab and screenshots it. To leave their view normal afterwards, either drop the `setDeviceMetricsOverride` line (desktop screenshot) or add a follow-up `Emulation.clearDeviceMetricsOverride`.
- **Cleanup (headless only), by unique `--user-data-dir`, never by name/title.** On Windows PowerShell:
  `Get-CimInstance Win32_Process -Filter "Name='chrome.exe'" | ? { $_.CommandLine -like '*<unique-dir>*' } | % { Stop-Process -Id $_.ProcessId -Force }`

```js
// Faithful mobile screenshot via CDP device emulation, WITH an automatic 1px
// reflow nudge so layouts settle (AJAX tabs, sticky tables, flex, Chart.js) — the
// settle step lives in the helper so it can't be forgotten. See "The 1px reflow nudge".
// usage: node shot.js <port> <url> <outfile> [width]
const [,, PORT, URL, OUT, W] = process.argv;
const width = parseInt(W || '390', 10);
const fs = require('fs');

async function main() {
  const list = await (await fetch(`http://localhost:${PORT}/json`)).json();
  const page = list.find(t => t.type === 'page' && t.webSocketDebuggerUrl);
  const ws = new WebSocket(page.webSocketDebuggerUrl);
  let id = 0; const pending = new Map();
  const send = (m, p = {}) => new Promise(res => { const i = ++id; pending.set(i, res); ws.send(JSON.stringify({ id: i, method: m, params: p })); });
  ws.addEventListener('message', ev => { const msg = JSON.parse(ev.data); if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result); pending.delete(msg.id); } });
  await new Promise(res => ws.addEventListener('open', res));

  const metrics = w => send('Emulation.setDeviceMetricsOverride', { width: w, height: 844, deviceScaleFactor: 2, mobile: true });
  await send('Page.enable');
  await send('Runtime.enable');
  await metrics(width);
  await send('Page.navigate', { url: URL });
  await new Promise(r => setTimeout(r, 2500));
  // --- automatic 1px nudge: toggle width +1 then back to fire a resize so the layout settles ---
  await metrics(width + 1);
  await new Promise(r => setTimeout(r, 200));
  await metrics(width);
  await new Promise(r => setTimeout(r, 400));
  const h = await send('Runtime.evaluate', { expression: 'document.documentElement.scrollHeight', returnByValue: true });
  const height = Math.min(h.result.value, 4000);
  const shot = await send('Page.captureScreenshot', {
    format: 'png', captureBeyondViewport: true,
    clip: { x: 0, y: 0, width, height, scale: 1 }
  });
  fs.writeFileSync(OUT, Buffer.from(shot.data, 'base64'));
  console.log('wrote', OUT, 'width', width, 'height', height);
  ws.close();
}
main().catch(e => { console.error(e); process.exit(1); });
```

(Attaching to the user's **visible** Chrome? The device-metrics override — and its nudge — temporarily forces a mobile viewport on their tab. For a desktop capture set a desktop `width` with `mobile:false`, or add a final `Emulation.clearDeviceMetricsOverride` so their view returns to normal.)

To find horizontal-overflow offenders, reuse the same connection boilerplate but `Emulation.setDeviceMetricsOverride` to the target width, then `Runtime.evaluate` a snippet returning every element whose `getBoundingClientRect().right > document.documentElement.clientWidth`.
