---
name: wasql-workon
description: Starts a WaSQL development session with the one-command workon.php/workon.py script. Trigger on the literal phrases "work on {alias} [page]" (a PostEdit-mirrored live site — resolve {alias} against postedit.xml's alias list, exactly as typed, not by pattern-matching wording) and "work on wasql [page]" (local WaSQL core/framework dev on localhost/php/admin.php, no PostEdit watcher — the deliberate exception to CLAUDE.md's never-modify-core rule), either optionally suffixed "using {browser}" (chrome/firefox/edge). Also trigger on "review wasql bugs" (runs workon wasql bugs --browse). Do NOT trigger just because the word "localhost" appears — "work on localhost" is a real PostEdit alias, distinct from "work on wasql". Not for generic "start/run/screenshot the app" requests outside WaSQL sessions — that's the separate "run" skill.
---

# WaSQL session startup (workon)

## ACT, don't narrate
Recognizing a "work on ..." (or "review wasql bugs") phrase means *doing* the startup command in the SAME turn — before asking the user what to work on. Do not reply with only a description of the mode ("I'm in X mode, here's how it works...") — that wastes a round-trip. No browser launch at startup (see below), so there's nothing to screenshot yet either.

## The command
```
php workon.php {alias|wasql} [page]
python workon.py {alias|wasql} [page]
```
Two implementations, identical behavior — kept in lockstep by design. Use whichever interpreter (`php` or `python`) is on the machine; if both are available, prefer `workon.php`. **`--help` on either is the authoritative flag reference**, kept current in the script itself.

No browser and no `--shot` by default — startup launches the PostEdit watcher (PostEdit sites only) and prints the mirror inventory, and assumes a browser is already open. Add `--browse` (drive a debug browser to the page) or `--shot=<scratchpad>/shot.png` (implies `--browse`, then Read the PNG) only once the developer has actually asked to see the page — this matches CLAUDE.md's "Ask before doing post-change QA."

`"work on {site} [page] using {browser}"` (e.g. "work on dexpdq using firefox") → append `--browser={browser}` (chrome/firefox/edge, case-insensitive) to the same command. Omit "using ..." and the script picks one itself via the precedence chain in `workon.md`'s "Browser choice" section.

**wamcp has no `setdb`/session-default database.** Call the wamcp `databases` tool to resolve `{alias}` (or `localhost` for `wasql`) to a `db_id`, then pass that `db_id` explicitly on every subsequent wamcp call (`query`, `schema`, `pagesrc`, `tables`, `fields`, `ddl`, `indexes`, `getdb`).

## Alias resolution is literal, not semantic
`{alias}` in "work on {alias} [page]" is looked up against `postedit.xml`'s `alias` list first, exactly as typed — don't pattern-match on wording. `postedit.xml` can have an alias literally named `localhost` — a real PostEdit-mirrored site, distinct from the `wasql` local-framework trigger even though both ultimately serve from `http://localhost`. "work on localhost" means that PostEdit alias; only the exact phrase "work on wasql" selects local framework mode.

## After startup: which mode you're now in

**"work on {alias} {page}"** → a PostEdit-mirrored live site. Default page = `index` when omitted. All logic stays in the DB — "never modify core" is in full force.
- Confirm the PostEdit watcher is actively running before editing — edits only sync to the DB while `postedit.php {alias}` is running (the workon command above starts it for you and reports its status).
- Once the mirror exists on disk, it — not wamcp — is your source for that site's page code. `workon`'s own output lists every mirrored page/field and the mirror root (`postedit/postEditFiles/{alias}/_pages/<page>/<page>._pages.<field>.<id>.<ext>`). Read/Edit those files directly; reserve wamcp for row *data*, schema/DDL, or unmirrored tables.
- Full edit loop (auto-sync, refresh, the 1px reflow nudge before screenshotting) → `postedit.md`.

**"work on wasql"** → local WaSQL framework development. Site = `http://localhost/php/admin.php`. Edit core/framework files directly in the repo (`php/`, `php/extras/**`, `wfiles/`, …) — no PostEdit, no watcher, no DB sync; saving a repo file changes what localhost serves immediately. This is the deliberate exception to "never modify core files": here you ARE the framework developer, but changes still affect every site built on WaSQL, so change with care.

**"review wasql bugs"** → a "work on wasql" session. Run `php workon.php wasql bugs --browse` (or the `.py` equivalent) to launch localhost at `/bugs`, and in the same turn review the open bugs (`curl http://localhost/bugs/json` is the cheapest read) so you're ready to start fixing them. Don't screenshot unless asked — the browser launch is the act.

## When you finish a task
Navigate the debug browser to `/php/admin.php?_menu=synchronize` once the task is done (edited, and QA'd or explicitly skipped per the developer's answer to "Ask before doing post-change QA"). This is a sanctioned, deliberate exception to "never use the backend admin UI" — not a general invitation to browse it.

## Deeper reference
- **`workon.md`** — every flag (`--no-watcher`, `--filter`, `--reshoot`, `--width`, `--port`, Firefox's `--ff-*` flags, …), the full browser-choice precedence chain, gotchas (wamcp db_id ≠ alias, tab-memory behavior, the `tail`/`head` hang fix, missing-permission troubleshooting), and the manual fallback if the script itself fails.
- **`postedit.md`** — the PostEdit edit loop once a session has started (mirror files, auto-sync, refresh, screenshot).
- **`workon_firefox.md`** — raw BiDi protocol findings behind Firefox's one-session-per-process limit, if the Firefox broker needs debugging.
