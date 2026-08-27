# WaSQL core bugs — moved to a database

This list now lives in **`wasql_bugs.db`** (SQLite, repo root) and is managed through
the **`/bugs`** page on localhost — a dashboard + checklist with add / update / mark-fixed
/ won't-fix actions.

- **Read / triage:** open `http://localhost/bugs` (or `GET http://localhost/bugs/json`
  for the raw rows).
- **Add or change a bug:** use the `/bugs` page. Do **not** append to this file.
- **Rebuild the db from scratch** (wipes edits): `python python/build_bugs_db.py --force`
  — the seed script that created `wasql_bugs.db` from this file's old contents.

Everything that was written here through 2026-08-18 (5 open bugs — 1, 2, 3, 7, 9 — plus
the fixed history back to 2026-07-28) was migrated into `wasql_bugs.db` on 2026-08-26,
one row per bug.

## Still true, and worth keeping in front of you before fixing anything

- These are **core files shared by hundreds of sites** (`php/common.php`,
  `php/database.php`, `php/cron.php`, `wfiles/js/extras/wacss.js`). A fix is a framework
  change, not a site change — keep each one as small as possible.
- **JS fixes require re-minifying `wacss.min.js`** through the normal build step, or
  nothing changes for any site (pages load the minified bundle).
- Page `css`/`js` changes on a live site also need `?_menu=clearmin` to bust the `w_min`
  bundle.
- Re-grep line numbers before editing — the working copy moves on.

Severity key: **A** = silently produces a wrong/broken result with no error anywhere.
**B** = throws or breaks only in a specific configuration. **C** = wart, works but
surprises whoever hits it.
