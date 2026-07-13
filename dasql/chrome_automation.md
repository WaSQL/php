# Chrome Automation via the DevTools Protocol (CDP)

This document explains the method we use to **drive a real Chrome browser from a script** —
launch it, navigate it, screenshot it, click things, read the DOM, and emulate a mobile
device — without any third-party automation library. Everything here uses the
**Chrome DevTools Protocol (CDP)** directly over a WebSocket, plus a small HTTP discovery
API. Node 22+ (which has built-in `fetch` and `WebSocket`) is all you need.

This is the mechanism behind the PostEdit "edit → screenshot → refresh" loop described in
`postedit.md`. Read this when you want to understand *how* the browser control actually
works, or to write your own automation scripts.

---

## 1. The big picture

```
┌─────────────┐   HTTP  ┌──────────────────────┐   WebSocket (JSON-RPC)  ┌───────────────┐
│ your script │ ──────► │ http://localhost:9222 │ ──────────────────────► │  Chrome tab    │
│  (Node.js)  │ ◄────── │  /json  (discovery)   │ ◄────────────────────── │  (a "target")  │
└─────────────┘         └──────────────────────┘                          └───────────────┘
```

Two steps:

1. **Start Chrome with a debugging port open** (`--remote-debugging-port=9222`). Chrome now
   exposes a small **HTTP API** on that port that lists the open tabs ("targets") and, for
   each, a **WebSocket URL**.
2. **Open the WebSocket for a tab** and send it CDP commands as JSON messages. Chrome
   replies with results and streams events. That WebSocket is the actual control channel —
   `Page.navigate`, `Page.captureScreenshot`, `Runtime.evaluate`, etc.

CDP is the same protocol Chrome's own DevTools, Puppeteer, and Playwright use. We're just
speaking it raw, which keeps the toolchain to "Node + a browser" with zero dependencies.

---

## 2. Starting Chrome

### 2a. Attach to a visible browser (shared view — preferred for dev)

Launch a normal, visible Chrome pointed at your page, with the debug port on a **dedicated
profile directory** so it works even if you already have Chrome open:

```bash
# Windows path shown; use the Chrome/Chromium binary for your OS
"C:\Program Files\Google\Chrome\Application\chrome.exe" \
  --remote-debugging-port=9222 \
  --user-data-dir="C:\path\to\a\dedicated\debug-profile" \
  --no-first-run --no-default-browser-check \
  --new-window "http://localhost/php/admin.php"
```

- **`--remote-debugging-port=9222`** — opens the HTTP + WebSocket API on port 9222.
- **`--user-data-dir=...`** — a *separate* profile dir (outside your repo, not your normal
  Chrome profile). This guarantees the debug port takes effect even if Chrome is already
  running, and it persists the site's login/session between runs.
- Because it's a real visible window, a screenshot is exactly what the user sees, and you
  can navigate/refresh it for them.

Confirm it's up and find your tab:

```bash
curl -s http://localhost:9222/json
```

### 2b. Headless (for pure screenshotting / CI)

```bash
chrome --headless --disable-gpu \
  --remote-debugging-port=9224 \
  --user-data-dir="C:\path\to\scratch-profile" about:blank
```

Then connect to `http://localhost:9224/json`. Headless is fine for captures but you don't
get a window to watch.

> **Chrome binary locations**
> - Windows: `C:\Program Files\Google\Chrome\Application\chrome.exe`
>   (sometimes `C:\Program Files (x86)\...`)
> - macOS: `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`
> - Linux: `google-chrome` or `chromium`

---

## 3. The HTTP discovery API

Before you can open a WebSocket, you ask the HTTP API what tabs exist. These endpoints live
on the debug port (`http://localhost:9222`). They are **GET** requests (a couple are `PUT`).

| Endpoint | Method | What it does |
|---|---|---|
| `/json` or `/json/list` | GET | List all targets (tabs). **This is the one you use most.** |
| `/json/version` | GET | Browser version + the **browser-level** `webSocketDebuggerUrl`. |
| `/json/new?<url>` | PUT | Open a new tab at `<url>`; returns its target object. |
| `/json/activate/<targetId>` | GET | Bring a tab to the foreground. |
| `/json/close/<targetId>` | GET | Close a tab. |
| `/json/protocol` | GET | The full CDP schema (every domain, command, and event) as JSON. |

A `/json` entry looks like:

```json
[
  {
    "id": "A1B2C3...",
    "type": "page",
    "title": "WaSQL - localhost",
    "url": "http://localhost/php/admin.php",
    "webSocketDebuggerUrl": "ws://127.0.0.1:9222/devtools/page/A1B2C3..."
  }
]
```

You want the entry where `type === "page"` and that has a `webSocketDebuggerUrl`. Filter by
`url` or `title` if several tabs are open. That `webSocketDebuggerUrl` is your control
channel for that tab.

There are two "levels" of WebSocket:
- **Page-level** (`/devtools/page/<id>`, from `/json`): controls one tab. This is what we use.
- **Browser-level** (`/devtools/browser/...`, from `/json/version`): controls the whole
  browser (create/close targets, etc.). Rarely needed for our workflow.

---

## 4. The WebSocket protocol (how CDP messages work)

Once you open the page's `webSocketDebuggerUrl`, communication is a simple JSON-RPC:

**You send** a command — an object with a unique `id`, a `method` (`"Domain.command"`), and
`params`:

```json
{ "id": 1, "method": "Page.navigate", "params": { "url": "https://example.com" } }
```

**Chrome replies** with a matching `id` and a `result`:

```json
{ "id": 1, "result": { "frameId": "..." } }
```

**Chrome also streams events** (no `id`, just `method` + `params`) — e.g. `Page.loadEventFired`,
`Network.responseReceived`. You only receive events for domains you've **enabled**
(`Page.enable`, `Runtime.enable`, `Network.enable`, …).

So the pattern is: keep a counter for `id`, keep a map of `id → resolve()` so each command
returns a Promise that resolves when its reply arrives.

### The connection helper (the boilerplate every script reuses)

```js
// Connect to a tab and return a `send(method, params)` that returns a Promise of the result.
async function connect(port, matchUrl) {
  const targets = await (await fetch(`http://localhost:${port}/json`)).json();
  const page = targets.find(t =>
    t.type === 'page' && t.webSocketDebuggerUrl &&
    (!matchUrl || (t.url || '').includes(matchUrl))
  );
  if (!page) throw new Error('no matching page target found');

  const ws = new WebSocket(page.webSocketDebuggerUrl);
  let id = 0;
  const pending = new Map();   // id -> resolve
  const listeners = [];        // event handlers: (method, params) => void

  const send = (method, params = {}) => new Promise(resolve => {
    const i = ++id;
    pending.set(i, resolve);
    ws.send(JSON.stringify({ id: i, method, params }));
  });

  ws.addEventListener('message', ev => {
    const msg = JSON.parse(ev.data);
    if (msg.id && pending.has(msg.id)) {         // a command reply
      pending.get(msg.id)(msg.result);
      pending.delete(msg.id);
    } else if (msg.method) {                     // a streamed event
      for (const fn of listeners) fn(msg.method, msg.params);
    }
  });

  await new Promise(res => ws.addEventListener('open', res));
  return { send, onEvent: fn => listeners.push(fn), close: () => ws.close() };
}
```

That ~25 lines is the entire "library." Everything else is just calling `send(...)`.

---

## 5. The CDP domains & methods we actually use

CDP has dozens of domains; here are the handful that cover screenshotting, interaction, and
emulation. **Enable a domain before using it or waiting for its events.**

### Page — navigation & screenshots
- `Page.enable` — turn on Page events.
- `Page.navigate { url }` — go to a URL (like typing in the address bar / refreshing).
- `Page.captureScreenshot { format, clip, captureBeyondViewport }` — returns
  `{ data: "<base64 PNG>" }`. `clip: { x, y, width, height, scale }` captures a region;
  `captureBeyondViewport: true` lets you capture below the fold (full-page).
- `Page.loadEventFired` — event that fires when the page finishes loading (better than a
  fixed sleep).

### Runtime — run JavaScript in the page
- `Runtime.enable`
- `Runtime.evaluate { expression, returnByValue, awaitPromise }` — execute JS in the page
  and get the result. This is the workhorse: click elements, read the DOM, measure element
  rectangles, check computed styles.
  - `returnByValue: true` → serialize the result back to your script (use JSON-serializable
    values; we usually `return JSON.stringify(...)`).
  - `awaitPromise: true` → if the expression is `async`/returns a Promise, wait for it (e.g.
    an in-page `fetch`).

### Emulation — pretend to be a phone
- `Emulation.setDeviceMetricsOverride { width, height, deviceScaleFactor, mobile }` — force a
  CSS viewport. **Use this for mobile checks** — it's reliable, unlike `--window-size`
  (see gotchas).
- `Emulation.clearDeviceMetricsOverride` — undo it (important when attached to the user's
  visible browser, so their view returns to normal).

### Network — cache control
- `Network.enable`
- `Network.setCacheDisabled { cacheDisabled: true }` — bypass the browser cache so edited
  CSS/JS is re-fetched instead of served from a `304`/memory cache.

> Want the full menu of everything CDP can do? `curl http://localhost:9222/json/protocol`
> dumps every domain/command/event, or see the online "Chrome DevTools Protocol" reference.

---

## 6. Complete example: faithful mobile screenshot

This is the reusable `shot.js`. It emulates a phone viewport, navigates, and captures the
full page height.

```js
// usage: node shot.js <port> <url> <outfile> [width]
const [, , PORT, URL, OUT, W] = process.argv;
const width = parseInt(W || '390', 10);
const fs = require('fs');

async function main() {
  const list = await (await fetch(`http://localhost:${PORT}/json`)).json();
  const page = list.find(t => t.type === 'page' && t.webSocketDebuggerUrl);
  const ws = new WebSocket(page.webSocketDebuggerUrl);

  let id = 0; const pending = new Map();
  const send = (m, p = {}) => new Promise(res => {
    const i = ++id; pending.set(i, res);
    ws.send(JSON.stringify({ id: i, method: m, params: p }));
  });
  ws.addEventListener('message', ev => {
    const msg = JSON.parse(ev.data);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result); pending.delete(msg.id); }
  });
  await new Promise(res => ws.addEventListener('open', res));

  await send('Page.enable');
  await send('Runtime.enable');
  await send('Emulation.setDeviceMetricsOverride',
    { width, height: 844, deviceScaleFactor: 2, mobile: true });
  await send('Page.navigate', { url: URL });
  await new Promise(r => setTimeout(r, 2500));                   // let it render

  const h = await send('Runtime.evaluate',
    { expression: 'document.documentElement.scrollHeight', returnByValue: true });
  const height = Math.min(h.result.value, 4000);

  const shot = await send('Page.captureScreenshot', {
    format: 'png', captureBeyondViewport: true,
    clip: { x: 0, y: 0, width, height, scale: 1 }
  });
  fs.writeFileSync(OUT, Buffer.from(shot.data, 'base64'));
  console.log('wrote', OUT, `${width}x${height}`);
  ws.close();
}
main().catch(e => { console.error(e); process.exit(1); });
```

Run it:

```bash
node shot.js 9222 "http://localhost/php/admin.php?cb=1" out.png 390
```

---

## 7. Complete example: interact, then verify

Screenshots show you *what* it looks like; `Runtime.evaluate` lets you **act and measure**.
This is how we tested the mobile navbar — click the burger, then read back state to confirm
the menu opened and that two elements don't overlap.

```js
const [, , PORT, URL] = process.argv;

async function main() {
  const list = await (await fetch(`http://localhost:${PORT}/json`)).json();
  const page = list.find(t => t.type === 'page' && t.webSocketDebuggerUrl);
  const ws = new WebSocket(page.webSocketDebuggerUrl);
  let id = 0; const pending = new Map();
  const send = (m, p = {}) => new Promise(res => {
    const i = ++id; pending.set(i, res); ws.send(JSON.stringify({ id: i, method: m, params: p }));
  });
  ws.addEventListener('message', ev => {
    const msg = JSON.parse(ev.data);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result); pending.delete(msg.id); }
  });
  await new Promise(res => ws.addEventListener('open', res));

  await send('Page.enable');
  await send('Runtime.enable');
  await send('Network.enable');
  await send('Network.setCacheDisabled', { cacheDisabled: true });   // always fetch fresh CSS/JS
  await send('Emulation.setDeviceMetricsOverride',
    { width: 390, height: 844, deviceScaleFactor: 2, mobile: true });
  await send('Page.navigate', { url: URL });
  await new Promise(r => setTimeout(r, 3000));

  // helper: run JS in the page and get the value back
  const evalJs = async expr =>
    (await send('Runtime.evaluate', { expression: expr, returnByValue: true })).result.value;

  // 1) click an element
  await evalJs(`document.querySelector('.wacss_navbar-burger').click()`);
  await new Promise(r => setTimeout(r, 300));

  // 2) read back DOM state + geometry to verify the result
  const report = await evalJs(`(function () {
    const r = sel => {
      const el = document.querySelector(sel);
      const b = el.getBoundingClientRect();
      return { top: Math.round(b.top), bottom: Math.round(b.bottom) };
    };
    const L = r('.wacss_navbar > ul.left');
    const R = r('.wacss_navbar > ul.right');
    return JSON.stringify({
      menuOpen: document.querySelector('.wacss_navbar').classList.contains('is-active'),
      overlap: (L.top < R.bottom && R.top < L.bottom)   // do the two menus overlap?
    });
  })()`);

  console.log(report);   // e.g. {"menuOpen":true,"overlap":false}

  await send('Emulation.clearDeviceMetricsOverride');   // restore the user's view
  ws.close();
}
main().catch(e => { console.error(e); process.exit(1); });
```

The pattern to internalize: **`Runtime.evaluate` with `return JSON.stringify(...)`** turns the
live page into a data source. You can read `getBoundingClientRect()`, `getComputedStyle()`,
class lists, attribute values, element counts — anything the DOM exposes — and assert on it.

### Bonus: run an in-page `fetch` (needs `awaitPromise`)

Because the code runs *inside the page*, it has the page's cookies/session. Handy for hitting
an authenticated endpoint (e.g. to read the served CSS bundle, or trigger an admin action):

```js
const evalAsync = async expr =>
  (await send('Runtime.evaluate',
    { expression: expr, returnByValue: true, awaitPromise: true })).result.value;

const cssHasRule = await evalAsync(`(async function () {
  const href = document.querySelector('link[rel=stylesheet]').href;
  const text = await (await fetch(href, { cache: 'no-store' })).text();
  return text.includes('wacss_navbar-always');
})()`);
```

### Bonus: find horizontal-overflow culprits

```js
const offenders = await evalJs(`(function () {
  const w = document.documentElement.clientWidth;
  return JSON.stringify([...document.querySelectorAll('*')]
    .filter(el => el.getBoundingClientRect().right > w + 1)
    .slice(0, 20)
    .map(el => el.tagName + (el.className ? '.' + String(el.className).split(' ')[0] : '')));
})()`);
```

---

## 8. Two ways to click

For most cases, calling the element's `.click()` from `Runtime.evaluate` (as above) is the
simplest and most reliable — it dispatches a real DOM click and runs the site's handlers.

If you specifically need a *trusted* OS-level input event (e.g. to satisfy code that checks
`event.isTrusted`, or to test real pointer coordinates), use the **Input** domain instead:

```js
// dispatch a real mouse click at page coordinates (x, y)
await send('Input.dispatchMouseEvent', { type: 'mousePressed', x, y, button: 'left', clickCount: 1 });
await send('Input.dispatchMouseEvent', { type: 'mouseReleased', x, y, button: 'left', clickCount: 1 });
```

You'd typically get `x, y` from an element's `getBoundingClientRect()` via `Runtime.evaluate`.
For 99% of UI testing, the `.click()` approach is enough.

---

## 9. Gotchas learned the hard way

- **Windows: screenshot output must be a native Windows path.** Chrome writes with the OS
  file API, so pass `C:\...\out.png`, not a Git-Bash `/c/...` path, or you'll get
  "Access is denied." (Also: some shells translate `/c/...` paths inconsistently — prefer
  native `C:\...` paths and PowerShell for filesystem ops on Windows.)

- **Don't trust `--headless --screenshot --window-size` for mobile.** On Windows, OS DPI
  scaling can make the real CSS viewport wider than requested (e.g. ~485px while the PNG is
  cropped to 390px), so content *looks* cut off when it isn't. **Always emulate the viewport
  via `Emulation.setDeviceMetricsOverride`** — it sets the true CSS viewport.

- **Cache-bust when checking edits.** Two layers bite you:
  1. *Browser cache* — add `?cb=<n>` to the page URL and/or call
     `Network.setCacheDisabled { cacheDisabled: true }`.
  2. *Server/build caches* — e.g. a framework may serve a **pre-minified bundle** and only
     read your edited source if minification is off, or it may write a **static combined
     bundle file** that's served until deleted (WaSQL's `w_min/` cache — clear it, or the
     browser will happily fetch a fresh copy of a stale file). If your change doesn't show,
     verify *what's actually served* by fetching the bundle URL from inside the page (the
     in-page `fetch` trick above) and grepping it for your change.

- **Wait for render, but prefer events over fixed sleeps.** A `setTimeout` is fine for quick
  scripts, but for reliability enable `Page` and resolve on `Page.loadEventFired`:

  ```js
  const waitForLoad = () => new Promise(res => {
    const off = api.onEvent((m) => { if (m === 'Page.loadEventFired') res(); });
  });
  await send('Page.navigate', { url: URL });
  await waitForLoad();
  ```

- **Reset emulation when attached to the user's real browser.** After a mobile check, call
  `Emulation.clearDeviceMetricsOverride` so their window goes back to desktop.

- **⚠️ Never kill Chrome by window-title or process-name.** Chrome's renderer/GPU/utility
  subprocesses all share an empty `MainWindowTitle`, so a broad filter like
  `Get-Process chrome | where MainWindowTitle -eq '' | Stop-Process` will **kill the user's
  visible browser too.** To clean up a *headless* instance you launched, kill only by the
  specific PID you captured at launch, or by its unique `--user-data-dir`:

  ```powershell
  Get-CimInstance Win32_Process -Filter "Name='chrome.exe'" |
    Where-Object { $_.CommandLine -like '*<your-unique-user-data-dir>*' } |
    ForEach-Object { Stop-Process -Id $_.ProcessId -Force }
  ```

---

## 10. Quick reference

**Discover targets**
```bash
curl -s http://localhost:9222/json            # list tabs (+ webSocketDebuggerUrl each)
curl -s http://localhost:9222/json/version    # browser version + browser-level WS
```

**Message shapes**
```
send:   { "id": N, "method": "Domain.command", "params": { ... } }
reply:  { "id": N, "result": { ... } }
event:  { "method": "Domain.eventName", "params": { ... } }     // only after Domain.enable
```

**Most-used commands**
```
Page.enable
Page.navigate                     { url }
Page.captureScreenshot            { format:'png', captureBeyondViewport:true, clip:{x,y,width,height,scale} }
Runtime.enable
Runtime.evaluate                  { expression, returnByValue:true, awaitPromise?:true }
Emulation.setDeviceMetricsOverride{ width, height, deviceScaleFactor, mobile:true }
Emulation.clearDeviceMetricsOverride
Network.enable
Network.setCacheDisabled          { cacheDisabled:true }
Input.dispatchMouseEvent          { type, x, y, button:'left', clickCount:1 }
```

**Skeleton**
```js
const t = await (await fetch(`http://localhost:${PORT}/json`)).json();
const page = t.find(x => x.type === 'page' && x.webSocketDebuggerUrl);
const ws = new WebSocket(page.webSocketDebuggerUrl);
// ...id/pending map + send()...  then: await send('Page.navigate', { url });
```

That's the whole method: **one HTTP call to find the tab, one WebSocket to control it, and
`Runtime.evaluate` to read/act on the page.** No frameworks, just Node and Chrome.

---

## 11. How this differs from the "Claude for Chrome" extension

People often ask whether this raw-CDP method is "the same thing" as the **Claude for Chrome**
browser extension. Short answer: they share DNA but solve different problems.

The surprising part: **the extension also uses the Chrome DevTools Protocol under the hood** —
it drives the browser with "full mouse, keyboard, and screenshot control" via CDP, the same
protocol this document is about. So the difference is *not* the transport. The real
differences are **who's driving, which browser/session, and what guardrails exist.**

### What the extension is (current as of Dec 2025)

- A **Manifest V3 browser extension** (Chrome and Microsoft Edge only) that is **generally
  available to paid plans** (Pro, Max, Team, Enterprise) — no longer a research preview.
- It runs an **agentic loop**: you ask Claude (Sonnet 4.5) in **natural language**, and the
  model decides which of its ~21 browser tools to call (click, type, navigate, read DOM,
  screenshot, manage tabs, handle dialogs, upload files…), iterating until done.
- It reads the page as an **accessibility tree** (ARIA roles/labels/structure) plus
  screenshots — not just raw pixels.
- It acts inside **your real, logged-in browser session**, so it inherits your existing
  auth to every site you're signed into (Gmail, Jira, AWS, your local dev server…).
- It has a **safety model** you don't get from raw CDP: three permission modes
  (ask-per-action by default, follow-a-plan pre-authorization, or skip-all-checks),
  per-domain allow/deny, mandatory confirmation on irreversible/harmful actions
  (purchases, password changes), content blocking for high-risk categories, and
  prompt-injection defenses (Anthropic reports residual injection success dropping from
  ~23.6% to ~11.2% with defenses — explicitly "not foolproof," so start with trusted sites).
- It **pauses for you** on CAPTCHAs and login screens.
- It is **not user-scriptable** — there's no imperative "call these tools in this order" API;
  you steer it with prompts and it chooses the steps.

> There's also a middle option: **Claude Code's own Chrome integration** (`code.claude.com/docs/en/chrome`),
> which lets the CLI/VS Code agent use a Chrome instance. That's agent-driven like the
> extension, but wired into the coding agent.

### What our CDP method is

- **You** write an imperative script (`send('Page.navigate', …)`, `send('Runtime.evaluate', …)`).
  It's **deterministic and repeatable** — the same script does the same thing every run,
  which is exactly what you want for regression checks and CI.
- It typically drives a **dedicated debug-profile Chrome** you launched (or attaches to a
  visible one you started with `--remote-debugging-port`) — not your everyday profile.
- **No guardrails and no model in the loop** — the script has full, unmediated control.
  That's a feature for automation you wrote and trust, and a footgun if you point it at
  something destructive.
- It lives in the **same local agent that edits your code**, enabling the tight
  "edit source → clear cache → re-screenshot → assert" loop this repo uses. It has no
  dependency on a Claude subscription, cloud round-trips, or the extension being installed.

### Side by side

| Aspect | Claude for Chrome (extension) | Raw CDP (this doc) |
|---|---|---|
| Underlying protocol | **CDP** (same as us) | **CDP** |
| Who decides the steps | Claude, agentically (you prompt in natural language) | You, imperatively (you write the script) |
| Determinism | Non-deterministic (model chooses) | Deterministic / repeatable |
| Browser & session | Your **real, logged-in** browser (Chrome/Edge) | A **dedicated debug-profile** browser (or an attached one) |
| Authentication | Reuses your existing logins | You log in / manage session yourself |
| Safety model | Permission gates, per-domain allow-lists, confirmations, injection defenses | None — full control, you own the risk |
| Scriptable / CI-able | No (prompt-driven) | Yes |
| Setup | Install extension, sign in (paid plan) | Launch Chrome with a debug port; connect a WebSocket |
| Availability | Paid Claude plans; Chrome/Edge; no WSL; not via Bedrock/Vertex/Foundry | Anywhere you can run Chrome + Node |
| Handles CAPTCHA/login | Pauses and asks you | Your script must handle it |
| Best for | Ad-hoc "go do this task in my browser" using your real sessions | Precise, reproducible automation tied to your codebase |

### Which to use

- **This CDP method** when you need a *reproducible* result driven by *your* code — the
  screenshot/verify/regression loop against a site you're editing (our whole use case here).
- **The extension** when you want to hand Claude a fuzzy, one-off web task ("book this,"
  "pull the numbers from that dashboard") and let it figure out the steps in your own
  authenticated browser, with confirmation prompts guarding the risky bits.

*Sources: [claude.com/claude-for-chrome](https://claude.com/claude-for-chrome),
[claude.com/blog/claude-for-chrome](https://claude.com/blog/claude-for-chrome),
[Claude Code + Chrome docs](https://code.claude.com/docs/en/chrome),
[Get started with Claude in Chrome (Help Center)](https://support.claude.com/en/articles/12012173-get-started-with-claude-in-chrome).
Extension details reflect Dec 2025 documentation and may change.*
