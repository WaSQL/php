# postedit — how AI should work on a PostEdit-mirrored WaSQL site

This file tells an AI assistant (e.g. Claude) how to work on **any PostEdit-mirrored WaSQL site**: launch Chrome in debug mode, open the site named by `postedit.xml`, screenshot the rendered page, and make prompted edits by updating the local PostEdit files (which auto-sync to the database). Read it before starting PostEdit work.

Anything specific to one machine or one site (exact executable paths, a developer's blanket-permission grant, per-site logs) does **not** belong here — keep it in a personal, un-committed companion file outside the repo (see "Personal / machine-specific notes" at the bottom).

## Session startup — when the user says "work on {alias} {page}"

The prompt form is **`work on {alias} {page}`** (or "let's work on {alias} {page}"). Examples:
- `lets work on acme home`
- `work on demosite index`

Do these first:

1. **Resolve the alias → host.** Look up `{alias}` in `postedit/postedit.xml` among the `<host ... alias="{alias}">` entries and read that host's `name` attribute (the domain). The site URL is `https://{name}/{page}`. Hosts with `insecure="1"` use a self-signed cert (still `https://`; `curl -k`, Chrome ignores it). Some `name`s are bare hosts, IPs, or `host:port` — use them verbatim.
2. **Point the DB tools at the site:** call `mcp__wamcp__setdb` with `dbname: "{alias}"`. The wamcp db name usually matches the alias; if it errors, call `mcp__wamcp__databases` to list valid names and pick the match.
3. **Recreate the screenshot helper** if it's not already on disk: write `shot.js` (from the Appendix) to the session scratchpad — it's intentionally not stored in the repo. Node 22+ with built-in `fetch`/`WebSocket` is assumed.
4. **Start Chrome yourself** — launch a *visible* Chrome with the debug port on a **dedicated profile directory** so it works even if the user's normal Chrome is already open, then attach to it (both sides see the same window). Launch it in the background, non-headless, pointed at the resolved URL:
   ```
   <chrome-exe> --remote-debugging-port=9222 --user-data-dir="<chrome-debug-profile>" --no-first-run --no-default-browser-check --new-window "https://{name}/{page}"
   ```
   - `<chrome-exe>` — the Chrome/Chromium executable for this OS (Windows: `C:\Program Files\Google\Chrome\Application\chrome.exe`; macOS: `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`; Linux: `google-chrome` or `chromium`).
   - `<chrome-debug-profile>` — a **dedicated** profile dir **outside the repo and separate from your normal Chrome profile** (e.g. a temp dir). A dedicated dir (a) guarantees the debug port takes effect even if Chrome is already running, and (b) persists the site login/session across runs.

   Wait ~5s, then confirm with `curl -s http://localhost:9222/json` that a page target for `{name}` exists. If a Chrome is already running on port 9222 for a different site, just `Page.navigate` its existing target to the new URL instead of launching a second instance. If the user prefers their own already-open Chrome, they can launch it with `--remote-debugging-port=9222` and you attach to that.
5. **Edit → auto-sync → refresh:** edit the PostEdit files; PostEdit auto-syncs to the DB; refresh/re-screenshot with a `?cb=<n>` cache-buster. (See "The edit loop".)
6. Everything else (WaSQL syntax, where things live, gotchas) is below and in the repo `CLAUDE.md`.

> **Permissions:** launching Chrome, calling `setdb`/querying the DB, and editing files under `postedit/postEditFiles/{alias}` all run under whatever permission mode the user has set — respect it. A developer may pre-authorize this routine so you don't re-prompt each time; if so, that grant lives in their personal companion file, not here. The one hard prohibition from the repo `CLAUDE.md` always holds: **never `git commit` / `git push`.**

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

**A running PostEdit watcher auto-syncs file saves back into the DB.** So the loop is:

1. **Edit** the local PostEdit file.
2. PostEdit **auto-updates the matching DB record** — no manual step. (Verify with a `wamcp` query if unsure, e.g. `SELECT css FROM _pages WHERE _id=<id>`.)
3. **Refresh Chrome** on the affected page and re-screenshot to see the result.

The user usually has Chrome open on the page being edited; after a save they can just refresh to see the change (the DB is already updated). Add a cache-buster query param (`?cb=1`) when checking, since CSS/JS are served as a hashed minified bundle (`/w_min/minify_...css`) — the page's own `css`/`js` fields are compiled into that bundle, not inlined.

### ⚠️ Nudge the window by 1px before screenshotting (force a reflow)

**After a change/refresh, resize the browser by 1 pixel (then back) before you screenshot.** Some WaSQL layouts don't settle until a `resize` event fires — AJAX-loaded tab content, sticky/frozen tables, flex reflow, and (notably) a window that was resized while the page thought it was another width. Without the nudge you can screenshot a **stale/unsettled layout** and diagnose it as a real bug when it isn't.

This is easy to prove: navigate with the window set to a new width and the page can still report the *old* `innerWidth` (e.g. window at 800px but `window.innerWidth === 390`); a 1px nudge makes it recompute to the true width. Nudge via CDP `Browser.getWindowForTarget` → `Browser.setWindowBounds` ({width: w+1}, then {width: w}). The reusable `nudge.js` helper is in the **Appendix**. Sequence: **edit → PostEdit syncs → navigate/refresh → nudge 1px → screenshot.**

## Database access (wamcp)

- `mcp__wamcp__setdb` with `dbname: "{alias}"` once per session, then `mcp__wamcp__query` (read-only SQL) / `mcp__wamcp__tables` / `mcp__wamcp__fields`. If the alias isn't a valid db name, `mcp__wamcp__databases` lists them.
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
// Faithful mobile screenshot via CDP device emulation.
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

  await send('Page.enable');
  await send('Runtime.enable');
  await send('Emulation.setDeviceMetricsOverride', { width, height: 844, deviceScaleFactor: 2, mobile: true });
  await send('Page.navigate', { url: URL });
  await new Promise(r => setTimeout(r, 2500));
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

To find horizontal-overflow offenders, reuse the same connection boilerplate but `Emulation.setDeviceMetricsOverride` to the target width, then `Runtime.evaluate` a snippet returning every element whose `getBoundingClientRect().right > document.documentElement.clientWidth`.

## Appendix — `nudge.js` (1px reflow helper)

Also not stored in the repo — write it to the scratchpad. Resizes the visible debug Chrome window by 1px and back to fire a `resize` event so the layout settles. Run **after refreshing, before screenshotting** (see "Nudge the window by 1px" above). Usage: `node nudge.js 9222`.

```js
// Resize the visible Chrome window by 1px (then back) to force a reflow.
// usage: node nudge.js <port>
const [,, PORT] = process.argv;
async function main() {
  const list = await (await fetch(`http://localhost:${PORT}/json`)).json();
  const page = list.find(t => t.type === 'page' && t.webSocketDebuggerUrl);
  const ws = new WebSocket(page.webSocketDebuggerUrl);
  let id = 0; const pending = new Map();
  const send = (m, p = {}) => new Promise(res => { const i = ++id; pending.set(i, res); ws.send(JSON.stringify({ id: i, method: m, params: p })); });
  ws.addEventListener('message', ev => { const msg = JSON.parse(ev.data); if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result); pending.delete(msg.id); } });
  await new Promise(res => ws.addEventListener('open', res));

  const { windowId, bounds } = await send('Browser.getWindowForTarget', { targetId: page.id });
  const w = bounds.width;
  await send('Browser.setWindowBounds', { windowId, bounds: { width: w + 1 } });
  await new Promise(r => setTimeout(r, 300));
  await send('Browser.setWindowBounds', { windowId, bounds: { width: w } });
  console.log('nudged window', windowId, 'from', w, '->', w + 1, '->', w);
  ws.close();
}
main().catch(e => { console.error(e); process.exit(1); });
```
