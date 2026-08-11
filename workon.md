# workon.php / workon.py — one-command session startup

Referenced by `CLAUDE.md`'s "work on {site} [page]" / "work on wasql" trigger recognition. Read this for the full mechanics, a flag beyond the common case, or the manual fallback for when the script itself fails.

**Two implementations, identical behavior: `workon.php` and `workon.py`.** They're kept in lockstep — same flags, same defaults, same output, same exit codes, same temp-file layout (log/screenshot/tab-state files use the same names regardless of which one wrote them, so switching between them mid-session is safe). Use whichever interpreter is available on the machine:
```
php workon.php {alias|wasql} [page] --shot=<scratchpad>/shot.png
python workon.py {alias|wasql} [page] --shot=<scratchpad>/shot.png
```
If both `php` and `python` are on PATH, prefer `workon.php` (it's the original; `workon.py` is a port kept in sync with it) unless the user's environment favors Python. **`workon.php --help` / `workon.py --help` is the authoritative option/capability list** for either — it documents every flag (`--no-watcher`, `--filter=a,b`, `--no-filter`, `--shot`, `--width=N`, `--port=N`, `--chrome=PATH`, `--reshoot=URL`, `--no-chrome`, `--inv-max=N`, `--no-inventory`, `--log=PATH`, `--json`) and is kept current in the script itself — this file explains *why* and *when*, `--help` explains *what*.

**⚠️ Whenever you change `workon.php` or `workon.py`, make the same change in the other.** They must stay behaviorally identical (flags, defaults, output text, exit codes, temp-file names) — a fix or new flag added to only one silently breaks parity and whichever script the next session picks depends on the machine, not on which one you happened to edit. Port the change immediately, in the same turn, not as a follow-up.

## What it does

```
php workon.php {alias|wasql} [page] --shot=<scratchpad>/shot.png
python workon.py {alias|wasql} [page] --shot=<scratchpad>/shot.png
```

It resolves `{alias}` → host from `postedit/postedit.xml` (or handles the local `wasql` case: `http://localhost/php/admin.php`, no watcher), ensures the PostEdit watcher for the alias is running (PostEdit sites only; it warns about the destructive startup re-sync) **filtered to the named page** — `work on dexpdq sapcc` starts `postedit.php dexpdq sapcc`, so only records whose name contains `sapcc` are synced/watched (fast startup, no unrelated files; a watcher that's already running is reported, never restarted; it goes in a **new tab of the current Windows Terminal window**, and when it can't, the output says why) — then ensures a debug Chrome is up on port **9222** (reuses any running instance — never spawns a full duplicate); the watcher is started first so its background re-sync overlaps the Chrome boot instead of running back-to-back. Use `--no-filter` when you also need the page's `_templates` record or other pages. It then confirms the page's Chrome target, and writes a mobile screenshot via CDP with the **1px reflow nudge** baked in. Then **Read the PNG**. **wamcp has no `setdb`/session-default database** — call the wamcp `databases` tool to resolve `{alias}` (or `localhost` for `wasql`) to a `db_id`, and pass that `db_id` explicitly on every subsequent wamcp call; the script itself never calls wamcp. Default page = `index` (PostEdit) / `php/admin.php` (`wasql`).

## Gotchas

- **The wamcp db id is often NOT the postedit alias** (`dexpdq` → **`dexpdq_mysql`**). Call the wamcp `databases` tool to find the matching id, then pass it as `db_id` on every wamcp call — there's no session default to set once.
- **It fixes its own Chrome tab.** If the target isn't confirmed it retries the open and then prints the debug instance's open tabs — read that output rather than running `curl http://localhost:9222/json` yourself. It also creates the `--shot` parent directory, so no separate `mkdir` is needed.
- **It ends with a mirror inventory — read it instead of hunting.** The last block lists the named page's record id and the **full local path + size of every one of its fields** (`body`/`controller`/`functions`/`css`/`js`), then every other record on disk with its id (incl. `_templates`). That is the `find`/`_pages`-query step you would otherwise do first, so go straight from the inventory to Reading the field you need. (Mirror root is `postedit/postEditFiles/{alias}` — under `postedit/`, not the repo root.) If the watcher was just launched it waits for the re-sync to write that page before reporting.
- **⚠️ Never pipe `workon.php`/`workon.py` through `tail`/`head`** — either launches detached children that hold the pipe open, so a pager buffers forever and you see **nothing at all**. Redirect to a file (`> out.txt 2>&1`) or read stdout raw. Every run also copies its output to `%TEMP%/wasql-workon-{alias}.log`, so a swallowed run can be read back rather than re-run.
- **If it still asks for approval every session**, check `.claude/settings.json` / `.claude/settings.local.json` for `Bash(php workon.php:*)` / `Bash(python workon.py:*)` and `PowerShell(php workon.php:*)` / `PowerShell(python workon.py:*)` allow rules — without them, each run's unique `--shot=<scratchpad>/...` path makes every invocation a "new" command to approve. If missing, offer to add them (the `update-config` skill can do this) rather than eating a prompt every session.
- **It remembers its Chrome tab.** The confirmed tab's id is saved per alias (`%TEMP%/wasql-workon-tab-{alias}.json`), so a later run for the same alias reliably comes back to the same tab even when other "work on" sessions have other tabs open in the same shared debug Chrome instance — screenshots target that exact tab id instead of guessing "the first page tab" in the whole instance.
- **`--reshoot=URL`** is the lightweight follow-up for "did my edit work" checks later in the same session — it reuses the tab already confirmed this session and just re-navigates + screenshots, skipping the watcher/inventory steps. Prefer it over hand-rolling a separate `node`/CDP screenshot script. **`--no-chrome`** gives a pure watcher-status check (no browser touch) when you just need to confirm the watcher is still alive.

## Manual fallback (only if the script fails)

- **PostEdit-mirrored site**: follow `postedit.md`'s manual steps (resolve the alias, launch debug Chrome, confirm the target, screenshot).
- **Local wasql framework mode**: (1) find Chrome, (2) write `shot.js` to the scratchpad if absent, (3) `Start-Process` a detached debug Chrome (`--remote-debugging-port=9222`, dedicated `--user-data-dir`) at `http://localhost/php/admin.php`, (4) confirm the target via `curl -s http://localhost:9222/json`, (5) run `shot.js` and Read the PNG. Follow `postedit.md` for the exact commands — **minus the auto-sync wait** (no watcher in this mode).
