# workon.php / workon.py — one-command session startup

Referenced by `CLAUDE.md`'s "work on {site} [page]" / "work on wasql" trigger recognition. Read this for the full mechanics, a flag beyond the common case, or the manual fallback for when the script itself fails.

**Two implementations, identical behavior: `workon.php` and `workon.py`.** They're kept in lockstep — same flags, same defaults, same output, same exit codes, same temp-file layout (log/screenshot/tab-state files use the same names regardless of which one wrote them, so switching between them mid-session is safe). Use whichever interpreter is available on the machine:
```
php workon.php {alias|wasql} [page]
python workon.py {alias|wasql} [page]
```
If both `php` and `python` are on PATH, prefer `workon.php` (it's the original; `workon.py` is a port kept in sync with it) unless the user's environment favors Python. **`workon.php --help` / `workon.py --help` is the authoritative option/capability list** for either — it documents every flag (`--no-watcher`, `--filter=a,b`, `--no-filter`, `--browse`/`--open`, `--shot`, `--width=N`, `--port=N`, `--chrome=PATH`, `--reshoot=URL`, `--no-chrome`/`--no-browser`, `--inv-max=N`, `--no-inventory`, `--log=PATH`, `--json`, plus the Firefox flags below) and is kept current in the script itself — this file explains *why* and *when*, `--help` explains *what*.

**⚠️ Whenever you change `workon.php` or `workon.py`, make the same change in the other.** They must stay behaviorally identical (flags, defaults, output text, exit codes, temp-file names) — a fix or new flag added to only one silently breaks parity and whichever script the next session picks depends on the machine, not on which one you happened to edit. Port the change immediately, in the same turn, not as a follow-up.

## What it does

```
php workon.php {alias|wasql} [page]
python workon.py {alias|wasql} [page]
```

It resolves `{alias}` → host from `postedit/postedit.xml` (or handles the local `wasql` case: `http://localhost/php/admin.php`, no watcher), ensures the PostEdit watcher for the alias is running (PostEdit sites only; it warns about the destructive startup re-sync) **filtered to the named page** — `work on dexpdq sapcc` starts `postedit.php dexpdq sapcc`, so only records whose name contains `sapcc` are synced/watched (fast startup, no unrelated files; a watcher that's already running is reported, never restarted; it goes in a **new tab of the current Windows Terminal window**, and when it can't, the output says why), then prints the mirror inventory. Use `--no-filter` when you also need the page's `_templates` record or other pages.

**No browser by default** (and therefore no screenshot). Launching a debug browser and confirming its target is the slowest, most permission-heavy part of startup, and the developer normally already has the page open in front of them — so a plain `workon` run is *watcher + inventory only* and simply assumes the browser is there. Add **`--browse`** (synonym `--open`) when you actually need a browser tab driven to the page: it ensures a debug browser is up and showing the page (Chrome by default, on port **9222**; reuses any running instance — never spawns a full duplicate) and confirms the page's target. **`--shot=<scratchpad>/shot.png`** and **`--reshoot=URL`** imply `--browse`, so "the developer asked to see it" is a single flag: the shot carries the **1px reflow nudge** baked in — then Read the PNG. This matches "Ask before doing post-change QA" in `CLAUDE.md`: startup ends at the watcher, and the browser only comes up when someone actually wants to look. **wamcp has no `setdb`/session-default database** — call the wamcp `databases` tool to resolve `{alias}` (or `localhost` for `wasql`) to a `db_id`, and pass that `db_id` explicitly on every subsequent wamcp call; the script itself never calls wamcp. Default page = `index` (PostEdit) / `php/admin.php` (`wasql`).

## Browser choice: Chrome (default), Edge, or Firefox

`--browser=chrome|firefox|edge` picks the browser for one run; precedence is `--browser` > `WASQL_BROWSER` env var > the persisted default (`--set-default=chrome|firefox|edge`, written to `{HOME}/.wasql-browser`) > **your OS's own default browser**, auto-detected (Windows: the `UserChoice` registry key; Linux: `xdg-settings`; not detected on macOS) when it's one of these three > hardcoded `chrome` as the final fallback. That OS-default tier only ever applies when nothing more specific said otherwise — an explicit `--browser`, env var, or `--set-default` always wins.

**Edge needs no explanation beyond the flag list above** — it's Chromium-based and speaks the exact same CDP protocol Chrome does, so it reuses Chrome's entire code path; only the executable candidates/registry key differ (`--edge=PATH` to override, profile defaults to `temp/wasql-edge-debug`). No broker, no session-limit quirk.

**Firefox is architecturally different and worth understanding before you reach for it**:

Firefox's remote protocol (WebDriver BiDi) allows **exactly ONE session per browser process, ever** — verified live against a real install (see `workon_firefox.md` for the raw protocol findings). A second `session.new` after the first is abandoned (crash, Ctrl-C, a tool's own timeout — all realistic for a short-lived per-call script) **permanently wedges the process**; no BiDi call from a fresh connection can recover it, only killing the process can. Chrome/Edge have no such limit — any number of short-lived debugger connections can come and go freely, which is exactly what the Chrome/Edge screenshot helper relies on.

So Firefox is driven through a **resident broker** (`node`, written to `temp/wasql_ff_broker.js` and left running for the life of the debug Firefox instance) instead of a per-call script: it claims the one BiDi session once and answers plain HTTP from workon.php/workon.py (`GET /status`, `POST /resolve`, `POST /shot`, `POST /shutdown`) for as many "work on" calls as come in. New flags: `--firefox=PATH`, `--ff-port=N` (BiDi port, default 9333), `--ff-broker-port=N` (broker's own HTTP port, default 9334), `--ff-shutdown` (cleanly ends the broker's session — **always prefer this or Ctrl-C over killing Firefox directly**; an unclean kill still needs a manual `taskkill` + relaunch, since there's no API to un-wedge a session from outside).

## Saying which browser in the "work on" phrase

`work on {site} [page] using {browser}` (e.g. "work on dexpdq using firefox") maps `{browser}` (chrome/firefox/edge, case-insensitive) straight onto `--browser={browser}` on the same command line — see `CLAUDE.md`'s trigger recognition for the exact phrasing this covers. Omit "using ..." to fall back to the precedence chain above.

## Gotchas

- **The wamcp db id is often NOT the postedit alias** (`dexpdq` → **`dexpdq_mysql`**). Call the wamcp `databases` tool to find the matching id, then pass it as `db_id` on every wamcp call — there's no session default to set once.
- **It fixes its own browser tab.** Chrome: if the target isn't confirmed it retries the open and then prints the debug instance's open tabs — read that output rather than running `curl http://localhost:9222/json` yourself. Firefox: it polls the broker until ready and asks it to resolve (find-or-create) the tab. It also creates the `--shot` parent directory, so no separate `mkdir` is needed.
- **It ends with a mirror inventory — read it instead of hunting.** The last block lists the named page's record id and the **full local path + size of every one of its fields** (`body`/`controller`/`functions`/`css`/`js`), then every other record on disk with its id (incl. `_templates`). That is the `find`/`_pages`-query step you would otherwise do first, so go straight from the inventory to Reading the field you need. (Mirror root is `postedit/postEditFiles/{alias}` — under `postedit/`, not the repo root.) If the watcher was just launched it waits for the re-sync to write that page before reporting.
- **⚠️ Never pipe `workon.php`/`workon.py` through `tail`/`head`** — either launches detached children that hold the pipe open, so a pager buffers forever and you see **nothing at all**. Redirect to a file (`> out.txt 2>&1`) or read stdout raw. Every run also copies its output to `%TEMP%/wasql-workon-{alias}.log`, so a swallowed run can be read back rather than re-run.
- **If it still asks for approval every session**, check `.claude/settings.json` / `.claude/settings.local.json` for `Bash(php workon.php:*)` / `Bash(python workon.py:*)` and `PowerShell(php workon.php:*)` / `PowerShell(python workon.py:*)` allow rules. Startup normally omits both `--browse` and `--shot` now (see above), so the plain `{alias|wasql} [page]` invocation should match a wildcard rule cleanly; it's mainly a later `--browse` / `--shot=<scratchpad>/...` call (once actually needed) whose unique path makes that particular invocation a "new" command to approve. If the allow rules are missing, offer to add them (the `update-config` skill can do this) rather than eating a prompt every session.
- **It remembers its browser tab.** The confirmed tab/context id is saved per alias (`%TEMP%/wasql-workon-tab-{alias}.json`), so a later run for the same alias reliably comes back to the same tab even when other "work on" sessions have other tabs open in the same shared debug instance — screenshots target that exact id instead of guessing "the first page tab" in the whole instance.
- **`--reshoot=URL`** is the lightweight follow-up for "did my edit work" checks later in the same session — it reuses the tab already confirmed this session and just re-navigates + screenshots, skipping the watcher/inventory steps. Prefer it over hand-rolling a separate screenshot script. **`--no-chrome`/`--no-browser`** is now a pure *status* check — it also skips the mirror inventory, leaving just "is the watcher still alive?" (the browser itself is skipped by default anyway, so you only need this flag when you want the inventory suppressed too).
- **Firefox only ever gets ONE BiDi session per process.** If `--ff-shutdown` reports it can't reach the broker, it's already stopped (not an error to chase). If a broker crashed uncleanly and left Firefox wedged (a fresh `session.new` fails with "Maximum number of active sessions"), the only fix is killing the Firefox debug process and letting the next `work on ... --browser=firefox` relaunch it fresh — there's no API-level recovery, by design of the protocol, not a bug in these scripts.

## When you finish a task

**Navigate the debug browser to `/php/admin.php?_menu=synchronize`** once the task itself is done (edits made, and QA'd or explicitly skipped per the developer's answer to CLAUDE.md's "Ask before doing post-change QA"). This is the second sanctioned exception to CLAUDE.md's "never use the backend admin UI" rule (the first being `?_menu=clearmin`) — it's a deliberate end-of-task step, not general admin-UI browsing, so it doesn't reopen that door for anything else.

## Manual fallback (only if the script fails)

- **PostEdit-mirrored site**: follow `postedit.md`'s manual steps (resolve the alias, launch debug Chrome, confirm the target, screenshot).
- **Local wasql framework mode**: (1) find Chrome, (2) write `shot.js` to the scratchpad if absent, (3) `Start-Process` a detached debug Chrome (`--remote-debugging-port=9222`, dedicated `--user-data-dir`) at `http://localhost/php/admin.php`, (4) confirm the target via `curl -s http://localhost:9222/json`, (5) run `shot.js` and Read the PNG. Follow `postedit.md` for the exact commands — **minus the auto-sync wait** (no watcher in this mode).
- **Firefox, if `--browser=firefox` itself fails**: don't hand-roll a per-call BiDi script — that's exactly the pattern that wedges Firefox on any interrupted call (see above). Fall back to plain `--browser=chrome` for the session instead, and debug the broker via its log (`temp/wasql-ff-broker-{alias}.log`) and `--ff-shutdown` rather than driving Firefox directly.
