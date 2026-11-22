# Firefox support for workon.php / workon.py — design notes (IMPLEMENTED)

**Status: shipped.** All BiDi calls below were verified live (including the ones originally marked "not yet verified" - see that section, now updated) and the full flow (browser-preference resolution, Firefox launch, the resident broker, screenshot, `--ff-shutdown`) is implemented in both `workon.php` and `workon.py`, documented in `workon.md`'s "Browser choice" section and both scripts' `--help`. This file is kept as the design/protocol-verification record - read it for the *why* behind the broker architecture; read `workon.md` / `--help` for current usage. Read `workon.md` first for the existing Chrome mechanics this extends.

## Why this exists

`workon.php`/`workon.py` currently assume Chrome: they drive it entirely through Chrome's CDP (`GET /json` to list targets, `PUT /json/new` to open a tab, a short-lived `shot.js` Node helper that connects, screenshots, and exits). A user asked for Firefox support. That turned out to be a real fork in the automation layer, not a flag tweak — Firefox's remote protocol (WebDriver BiDi) is wire-incompatible with CDP and has a sharp reliability gotcha (see below).

## Decisions made (via AskUserQuestion with the user)

1. **Scope: full parity**, not launch-only. `--shot` must work for Firefox the same way it does for Chrome (mobile viewport, 1px reflow nudge, PNG written to `--shot=PATH`).
2. **Preference model: standing default + per-run override.** A one-time setting picks the user's default browser; `--browser=chrome|firefox` overrides it for a single run.
3. **Given the session-limit gotcha found during prototyping (below), build a persistent broker** rather than a per-call short-lived script (the naive approach that mirrors Chrome's `shot.js` pattern is explicitly rejected — it wedges the shared Firefox instance on any interrupted run).

## Critical finding: Firefox BiDi allows exactly ONE session per browser process, ever

Verified live against the user's actual install (Firefox 153.0.3 on Windows) before writing any implementation:

- Launch: `firefox.exe -profile "<profile>" -no-remote --remote-debugging-port=<port> -new-window "<url>"` — works, confirmed by watching the process and its profile dir.
- Firefox does **not** expose an HTTP `/json` target-list endpoint like Chrome. Discovery is: connect a WebSocket directly to `ws://127.0.0.1:<port>/session` (verified via raw TCP handshake — `GET /` on that port just serves a static "httpd.js" placeholder page; `GET /session` with the WebSocket upgrade headers returns `101 Switching Protocols`). The profile's `WebDriverBiDiServer.json` only confirms host/port, not the path — that had to be found by trial.
- Protocol handshake that works: send `{"id":1,"method":"session.new","params":{"capabilities":{}}}` over that socket → returns `sessionId` + capabilities (browserName, version, etc.).
- **Then it broke on purpose, to find the limit:** a test script called `session.new` + `browsingContext.getTree` and exited *without* calling `session.end`. A second, completely separate connection to the same Firefox process then got:
  - `session.new` → `{"error":"session not created","message":"Maximum number of active sessions"}`
  - `session.status` → `{"ready":false,"message":"Session already started"}`
  - `session.end` with the previously-issued sessionId → `{"error":"invalid session id","message":"WebDriver session does not exist, or is not active"}`
  - **No API call from a fresh connection can recover it.** The only fix was `taskkill` on the whole Firefox process tree and relaunching.

This is fundamentally different from Chrome's CDP, which has no server-side "session" state to leak — any number of short-lived debugger connections can come and go freely, which is exactly what `shot.js` relies on. For Firefox, any interrupted screenshot call (crash, Ctrl-C, a sandboxed tool timeout — all realistic) would permanently wedge the *shared* debug Firefox instance for the rest of the "work on" session, and killing it drops whatever else the user had open in it too. The user was told this explicitly and chose to proceed with a broker anyway (full parity, accepting that an *unclean* kill of the broker itself still wedges Firefox — same residual risk, just contained to "broker crashes" instead of "every single screenshot call").

## What's verified to work (safe to reuse directly, no need to re-test)

All tested live via a throwaway Node script talking to the WS endpoint above, over the same one BiDi session:

- `browsingContext.getTree` → lists open contexts (tabs), each with a `context` id and `url`. This is Firefox's equivalent of Chrome's `findTarget()` matching — a fresh Firefox launched with `-new-window "<url>"` shows up here as the initial context, so "reuse the existing tab" logic can work the same way Chrome's does (match by host substring, prefer a remembered id).
- `browsingContext.setViewport` with `{context, viewport:{width,height}, devicePixelRatio}` — tested with `390x844`, dpr `2` (mobile-equivalent of Chrome's `Emulation.setDeviceMetricsOverride`). Succeeded.
- `browsingContext.navigate` with `{context, url, wait:'complete'}` — tested against `https://example.com`, succeeded and blocks until load, so no manual `Page.loadEventFired` event-listening dance is needed (BiDi's `wait` param does it inline, simpler than the CDP version in the current `shot.js`).
- `browsingContext.captureScreenshot` with `{context, origin:'document', format:{type:'image/png'}}` — returned valid base64 PNG data, written to disk and confirmed non-empty.

## What's now verified (was the "not yet verified" list - all confirmed live before wiring in)

- `browsingContext.create` — confirmed: creates a second tab/context (`type: 'tab'`) within the one session, shows up in a subsequent `getTree`. Used by the broker's `/resolve` when no existing context matches.
- `script.evaluate` — confirmed against the real server with `{expression, target: {context}, awaitPromise: false, resultOwnership: 'none'}`; returns `{result: {type, value}}`, used to measure `document.documentElement.scrollHeight` exactly like the Chrome path's `Runtime.evaluate`.
- The **1px reflow nudge** (`setViewport` at width+1, settle, width again) — confirmed back-to-back, no errors, screenshot after the nudge renders correctly.
- `browsingContext.captureScreenshot`'s `clip` param — confirmed shape is `{type: 'box', x, y, width, height}` (not the flat `{x,y,width,height,scale}` Chrome's CDP uses); produces a correctly-cropped PNG at the requested width/height.
- Clean `session.end` genuinely releases the process for a fresh `session.new` (verified twice: once standalone, once via the broker's `/shutdown` route end-to-end) - confirms the SIGINT/SIGTERM/`--ff-shutdown` shutdown path is real, not just theoretical.
- Multiple contexts within one session don't interfere: created a second context, navigated/screenshotted the first, closed the second - no cross-talk.

## Planned architecture (not yet built)

**Browser preference resolution** (in both scripts, precedence high→low):
1. `--browser=chrome|firefox` (this run only)
2. `WASQL_BROWSER` env var
3. A persisted default — plain text file, one line (`chrome` or `firefox`), written by a new `--set-default=X` flag. Proposed location: `{USERPROFILE or HOME}/.wasql-browser` (not the temp dir — a standing preference shouldn't live somewhere that gets swept).
4. Hardcoded default: `chrome` (today's only behavior, so nothing changes for existing users who never touch the new flags).

**Firefox launch + broker lifecycle**, mirrored against the existing Chrome flow in `workon.php`/`workon.py`:
- Firefox executable discovery, same shape as the existing Chrome candidate-path/registry probe (`C:\Program Files\Mozilla Firefox\firefox.exe`, `(x86)` variant, `HKLM\...\App Paths\firefox.exe`; mac `/Applications/Firefox.app/Contents/MacOS/firefox`; linux `/usr/bin/firefox`).
- A **dedicated Firefox profile dir**, separate from Chrome's `wasql-chrome-debug` (e.g. `wasql-firefox-debug`), created the same way (`is_dir`/`mkdir` guard already in the script).
- A **broker script** (new, e.g. `wasql_ff_broker.js`, written to a temp path the same way `wasql_shot.js` is today) that:
  - On startup, connects to the Firefox debug WS endpoint and calls `session.new` exactly once, then **does not exit** — it stays resident for the life of the debug Firefox instance.
  - Listens on its own local HTTP port (Node's built-in `http` module — no npm install needed, confirmed no `ws` package is installed globally on this machine so everything must ride on Node's built-in global `WebSocket` client and `http` server, both of which were used successfully in the prototype).
  - Exposes: `GET /status` (only answers once the session is live — mirrors "the Chrome `/json` endpoint answering" as the readiness check), `POST /resolve {host,url,rememberedId}` → find-or-create a browsingContext (Chrome's `findTarget`/`newTab` equivalent), `POST /shot {id,url,width,out}` → viewport+navigate+nudge+screenshot+write-to-disk, `POST /shutdown` → best-effort `session.end` then exit.
  - Registers `SIGINT`/`SIGTERM` handlers that call `session.end` before exiting, to make the *clean*-shutdown path safe. (An unclean kill still wedges Firefox — accepted residual risk, see above.)
  - `workon.php`/`workon.py` talk to the broker with plain HTTP POSTs (`file_get_contents` + stream context, or `curl`) instead of spawning a fresh Node process per screenshot — spawning fresh is exactly the pattern that's unsafe here.
- Tab-memory equivalent: today's `wasql-workon-tab-{alias}.json` stores a Chrome target id; for Firefox this needs the same idea (remember the browsingContext id per alias) but the lookup logic itself now lives in the broker's `/resolve`, since only the broker holds the live context list.

**Everything else in `workon.php`/`workon.py` — watcher launch/filtering, mirror inventory, `--reshoot` — is browser-agnostic and needs no change.**

## Remaining work — DONE

1. ~~Finish prototyping the untested BiDi calls~~ - done, see "What's now verified" above.
2. ~~Add the browser-preference resolution~~ - done in `workon.php` (`resolveBrowserPref()`, `--set-default`, `WASQL_BROWSER`).
3. ~~Wire Firefox executable discovery, profile creation, and the broker start/attach/screenshot flow into `workon.php`~~ - done.
4. ~~Port into `workon.py`~~ - done, verified behaviorally identical.
5. ~~Update `workon.md` and both scripts' `--help` text~~ - done.
6. ~~End-to-end test~~ - done: `--browser=firefox --shot=` produced a correct mobile screenshot in both `workon.php` and `workon.py`; a second call reused the same broker + remembered context (~1.7s vs. the ~5-7s first-launch cost); `--reshoot` worked; `--no-browser` correctly skipped Firefox entirely; `--ff-shutdown` cleanly ended the session and a fresh `session.new` succeeded afterward, proving no wedge. Test profiles/processes cleaned up afterward.

No further work is planned. If a future session extends this (e.g. Safari, or juggling multiple Firefox contexts per alias the way Chrome shares tabs), start from the broker's `/resolve`+`/shot` HTTP contract in `workon.php`'s embedded broker source (search for `wasql_ff_broker.js`) rather than this history.

## Test scaffolding already thrown away (recreate if picking this back up)

The prototype scripts used to verify the protocol (`bidi_test.js`, `bidi_test2.js`, `bidi_end.js`, `raw_handshake.js`, a throwaway profile dir) were all written to the session scratchpad and deleted along with the test Firefox process once the findings above were confirmed — none of that is left on disk. Recreate quickly from the verified snippets in this file rather than hunting for it.
