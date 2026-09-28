# SCM for WaSQL — Promotion Pipeline Design

> **Status: design / not yet built.** This document specifies how to extend
> [scm](scm.md) so WaSQL projects can move through a normal
> dev → int1 → prf1 → stage → prod cycle. Nothing here exists in `scm.py` yet.

---

## The problem

A WaSQL site has no code files. Page logic lives in `_pages` records (`body`,
`controller`, `functions`, `js`, `css`), templates in `_templates`, field
behavior in `_fielddata`/`_tabledata`, and so on. A MySQL dump **is** the site.
That leaves nothing obvious to commit, review, version, or promote.

The built-in **Synchronize** (`php/admin/synchronize_*`) pushes records from a
master to a slave, but it doesn't work as a multi-stage pipeline:

- It only sees two databases at a time. Chaining it across five environments
  means each hop diffs against whatever the next environment currently holds,
  so nothing guarantees prod gets exactly what prf1 tested.
- There's no versioned release. You can't answer "what was in prod on Sept 1."
- Rollback only works by reverting from the target.
- It can't tell a real change from a hotfix made directly downstream, and it
  silently overwrites the hotfix.

## The approach

**Develop only in dev. Capture what changed into an scm migration file. Promote
that same file through every environment.**

scm already provides everything except the capture step:

| Need | scm today |
|---|---|
| One migration set applied to many environments | `--db <name>` picks `.env.<name>` and falls back to the shared `./migrations` folder |
| Knowing what each environment has applied | Each database has its own tracking table; `scm --db prf1 status` |
| Promotion | `scm --db int1 up`, then `prf1`, `stage`, `prod`, same files in the same order |
| Preview, audit, rollback | `--dry-run`, `history`, `report`, `down`, `goto` |
| Adopting existing environments | `scm baseline` |
| Connection details | `env-from-config <name>` builds `.env.<name>` from `config.xml` |

What's new: **`scm capture`** (generate a migration from dev's changes) and
**`scm pipeline`** (see every migration's status across every environment).

---

## Ground rules

1. **Dev is the only place code/config changes are made.** Building through the
   WaSQL UI in dev is normal development. Nothing reaches int1 or above except
   through a captured migration.
2. **int1 and above are deploy-only.** Remove page/template/schema edit rights
   there. Deploys run through scm as a dedicated migration user, and nobody else
   gets the WaSQL admin UI on those environments. Enforce this; don't just agree
   on it.
3. **Hotfixes go through the same path, fast.** Fix in dev → capture → `up` on
   each environment in quick succession. If the sanctioned path is slow, people
   will patch prod directly.
4. **Captured migrations are immutable once applied anywhere.** Same as scm's
   existing rule: fix a bad one with a new migration.

---

## Environment layout

One project folder per WaSQL project (it can live in the project's own git repo):

```
myproject/
  .env               # shared settings (WASQL_PATH, MIGRATIONS_TABLE, ...)
  .env.dev
  .env.int1
  .env.prf1
  .env.stage
  .env.prod
  migrations/        # shared, no per-env subfolders
```

`.env` (shared):
```bash
MIGRATION_STYLE=one
MIGRATIONS_TABLE=_schema_migrations   # leading underscore = WaSQL system table
```

Each `.env.<env>` holds only that environment's `DATABASE_URL`, generated with
`scm env-from-config <name>`. `.env*` files stay out of git.

**Do not** create `migrations/<env>/` folders. Their absence is what makes every
environment run the same shared files.

---

## Day-to-day workflow

```
1. Build/fix in dev (WaSQL UI, PostEdit, etc.)
2. scm --db dev capture <name> --against int1     # writes migrations/<ts>_<name>.sql
3. git branch + commit + PR, review the migration
4. scm --db int1 up        → test
5. scm --db prf1 up        → perf test
6. scm --db stage up       → UAT
7. scm --db prod up
```

`scm pipeline` shows where each migration currently stands.

---

## Table classes

`capture` must know which tables are code and which are data. Only code is ever
promoted by `_id`.

| Class | Examples | Rows created downstream? | Captured? |
|---|---|---|---|
| **Code/config** | `_pages`, `_templates`, `_fielddata`, `_tabledata`, `_triggers`, cron definitions | Never (rule 1) | **Yes, by default** |
| **Lookup/seed** | status lists, categories | Depends on who owns it | **Only if listed explicitly** via `--tables`, and only when developers own the table. If the business edits it in prod, it's runtime data. |
| **Runtime data** | `_users`, orders, logs, `_synchronize`, `_schema_migrations`, anything users fill in | Constantly | **Never.** `capture` refuses these even when listed. |

Also never captured: `_settings` / `commonGetSetting` values, and anything else
that differs per environment or holds secrets. Connections and secrets stay in
each host's `config.xml`. Code that must behave differently per environment
branches on `isDBStage()`.

Default code-table list, overridable per project in `.env`:
```bash
SCM_WASQL_TABLES=_pages,_templates,_fielddata,_tabledata,_triggers
SCM_WASQL_NEVER=_users,_settings,_synchronize,_schema_migrations,_sessions
```

---

## Record identity: carry `_id` forward, with a tripwire

Because of rule 1, dev is the only place code/config rows are created, so dev's
`_id` values are safe to keep in every environment. Keeping them means:

- ids are the same everywhere,
- references between records (e.g. `_pages._template`) stay valid without being
  looked up by name,
- the files stay compatible with Synchronize during the transition.

Each record also has a **natural key** used for verification:

| Table | Natural key |
|---|---|
| `_pages` | `name` |
| `_templates` | `name` |
| `_fielddata` | `tablename` + `fieldname` |
| `_tabledata` | `tablename` |
| other captured tables | `name`, then the first of the Synchronize "marker" fields (`fieldname`, `tablename`, `title`, …), overridable per table |

**The tripwire.** Before a migration touches a record, the target must match
one of these cases:

| Target state | Action |
|---|---|
| Natural key exists with the **same** `_id` | UPDATE |
| Natural key absent and `_id` free | INSERT with dev's `_id` |
| Natural key exists under a **different** `_id` | **Abort** — someone created it downstream |
| `_id` taken by a **different** natural key | **Abort** — someone created a record downstream |

An abort means rule 1 was broken. It should fail loudly at int1 rather than
overwrite the wrong record in prod.

### Enforcing the guard

Plain SQL can't express "abort if…" cleanly on MySQL, so the guard is a set of
directives scm checks **before** executing the migration:

```sql
-- scm:wasql-guard _pages 412 name=orders
-- scm:wasql-guard _pages 518 name=orders_report new
```

- Without `new`: the row with `_id=412` must exist and have `name='orders'`.
- With `new`: `_id=518` must be free **and** no row may have
  `name='orders_report'`.

`up` / `down` / `goto` (and `--dry-run`) parse these lines, run the checks
against the target, and refuse to start if any fail, listing every failure.
Drivers that don't understand the directive still see an ordinary comment.

This is the one change to existing commands. Everything else is additive.

Backstop even without the guard: inserts use plain `INSERT` (never upsert by
`_id`), so an id collision still fails on the primary key, and InnoDB rolls back
the migration's DML.

---

## `scm capture` — specification

```
scm --db <dev> capture <name> --against <env>
    [--tables t1,t2]        # add lookup/seed tables to the default code tables
    [--only n1,n2]          # restrict to these natural keys (page names, etc.)
    [--schema-only | --records-only]
    [--yes]                 # skip the interactive picker
```

### Steps

1. **Connect** to `--db` (source, normally dev) and `--against` (baseline,
   normally int1). Refuse if `--against` resolves to the same database.
2. **Record diff.** For each captured table, compare source and baseline:
   - Fields compared: text-type columns, as in `adminGetSynchronizeFields()`
     (`php/admin.php`). Honor `_fielddata.synchronize=0` exclusions, and skip
     `css_min`, `js_min`, the audit columns (`_cuser`, `_cdate`, `_euser`,
     `_edate`), and `*_utime` columns.
   - Hash per row for speed (Synchronize uses md5), then pull full values only
     for rows that differ.
   - Classify each row: **new** (not in baseline), **changed** (same `_id`,
     different content), **deleted** (in baseline, not in source), or
     **conflict** (the tripwire cases above, already true between dev and int1).
     Conflicts stop the capture.
3. **Schema diff.** For every non-runtime table, compare columns
   (name, type, nullability, default) and indexes, source vs baseline. New
   tables are included only if they're listed in `--tables` or are referenced by
   a captured `_tabledata`/`_fielddata` row.
4. **Picker.** Show a numbered summary grouped by table:
   `_pages  orders  changed: controller, functions  (slloyd, 2h ago)`.
   Let the user choose, since dev usually holds half-finished work too. Use the
   same selection syntax as `scm undo` (`1,3`, `1-3`). `--yes` takes everything.
5. **Write the migration(s)** (format below). If both schema and record changes
   were picked, write **two** files, schema first (timestamps N and N+1). MySQL
   DDL isn't transactional, so keeping it separate from the record changes means
   a DDL failure doesn't leave record changes half-applied.
6. **Mark applied in the source.** Record each new version in dev's tracking
   table without running it (a one-version `baseline`). Dev already has the
   change, so its `status` stays accurate.
7. **Print** the file path(s) and the next step (`scm --db int1 up --dry-run`).

### Generated file format

```sql
-- migrate:up
-- scm:captured source=dev against=int1 by=<user> at=2026-09-28T14:03:11
-- scm:wasql-guard _pages 412 name=orders
-- scm:wasql-guard _pages 518 name=orders_report new
-- scm:wasql-guard _fielddata 2291 tablename=orders fieldname=status_id

UPDATE _pages SET
  controller = '...',
  functions  = '...',
  _edate = NOW()
WHERE _id = 412 AND name = 'orders';

INSERT INTO _pages (_id, name, _template, body, controller, functions, js, css, _cdate)
VALUES (518, 'orders_report', 2, '...', '...', '...', '', '', NOW());

UPDATE _fielddata SET inputtype = 'select', tvals = '...'
WHERE _id = 2291 AND tablename = 'orders' AND fieldname = 'status_id';

-- migrate:down
-- scm:wasql-guard _pages 412 name=orders
-- scm:wasql-guard _pages 518 name=orders_report
-- scm:wasql-guard _fielddata 2291 tablename=orders fieldname=status_id

UPDATE _pages SET
  controller = '...',
  functions  = '...'
WHERE _id = 412 AND name = 'orders';

DELETE FROM _pages WHERE _id = 518 AND name = 'orders_report';

UPDATE _fielddata SET inputtype = 'text', tvals = NULL
WHERE _id = 2291 AND tablename = 'orders' AND fieldname = 'status_id';
```

- **up** = source (dev) values. **down** = baseline (int1) values at capture
  time. Under rule 1 every downstream environment's prior state equals int1's,
  so the down section is correct everywhere. The guard enforces that assumption.
- **Deleted** rows: up = `DELETE … WHERE _id=… AND <natural key>`, down = full
  `INSERT` of the baseline row.
- Every `UPDATE`/`DELETE` carries both `_id` and the natural key in its `WHERE`,
  so even without the guard it can't touch the wrong row.
- Only changed columns appear in `UPDATE` (smaller diffs in review).
- Schema files use the same up/down shape: `ALTER TABLE … ADD COLUMN` /
  `DROP COLUMN`, `CREATE TABLE` / `DROP TABLE IF EXISTS`, `CREATE INDEX` /
  `DROP INDEX`, following scm.md's "Writing Safe Migrations". A dropped column's
  down section can restore the column but not its data; flag it with a comment.

---

## `scm pipeline` — specification

```
scm pipeline [--envs dev,int1,prf1,stage,prod]
```

Reads every `.env.<name>` (or the `--envs` list, in that order, which defaults
to `SCM_PIPELINE` in `.env`) and prints a table of migration × environment:

```
Version          Label                    dev   int1  prf1  stage prod
------------------------------------------------------------------------
20260920101500   orders_status_field      ✓     ✓     ✓     ✓     ✓
20260927093000   orders_report_page       ✓     ✓     ✓     ·     ·
20260928140311   orders_hotfix            ✓     ·     ·     ·     ·
```

Also flag out-of-order states (e.g. applied in prod but not stage) and orphaned
versions, using the same colors as `status`. Makes no changes.

`.env`:
```bash
SCM_PIPELINE=dev,int1,prf1,stage,prod
```

---

## Traps `capture` must handle

1. **Escaping.** Page fields are full of `\`, `'`, `;`, `--`, `/* */` and
   `$`. MySQL treats backslash as an escape character by default, so both `\`
   and `'` must be escaped or PHP regexes get corrupted. Either escape both, or
   emit `SET SESSION sql_mode = CONCAT(@@sql_mode, ',NO_BACKSLASH_ESCAPES');` at
   the top of each direction and double only `'`, then restore the mode at the
   end. **Required test:** round-trip every `_pages` row of a real site
   (capture → apply to an empty copy → byte-compare).
2. **Statement splitting.** scm's splitter handles comments and string
   literals. Test it against captured bodies that contain `;` inside strings,
   `--` inside JS, and `?>`.
3. **Encoding.** Read and write as `utf8mb4`. No BOMs in generated files.
4. **Null vs empty string.** Preserve the difference; WaSQL treats them
   differently in places.
5. **Large fields.** Some `functions` fields run to hundreds of KB. Check
   `max_allowed_packet` on targets and warn when a statement exceeds it.
6. **Stale bundles after deploy.** `css`/`js` changes won't show until the
   `w_min` minify cache is busted (`?_menu=clearmin`). Add an optional
   post-`up` hook (`SCM_POST_UP=<command or URL>` in `.env.<env>`), or print a
   reminder whenever an applied migration touched `css`/`js`.
7. **PII / secrets.** The never-capture list is enforced in code, not left to
   convention. Also scan captured values for obvious secrets (tokens,
   passwords) and warn.
8. **Code review readability.** A page body inside a SQL string shows up in a
   PR as a whole-string replacement. `capture` should also write a sidecar
   `migrations/<ts>_<name>.diff` (unified diff per changed field, dev vs int1)
   for reviewers. scm ignores non-`.sql` files, so this is safe.

---

## Required changes to `scm.md`

- **"Rules for Claude" rule 1** ("Never run DDL directly") gets a WaSQL
  carve-out: in a WaSQL project's dev environment, building through the WaSQL
  UI is normal. Nothing above dev is ever changed by hand, and dev changes
  reach other environments only through `scm capture`.
- Add a short **"WaSQL promotion pipeline"** section linking here.
- Document the `-- scm:wasql-guard` directive under `up`/`down`/`goto`.
- Add `capture` and `pipeline` to the command list, `learn` output, and the
  comparison table.

---

## Rollout for an existing project

1. **Align the environments once.** Use Synchronize (or a dump of dev's code
   tables) to make int1 → prod match dev for all code/config tables. Resolve any
   records that exist only downstream: move them into dev with the **same**
   `_id`, or delete them.
2. **Verify** with `scm --db dev capture baseline_check --against <env>
   --dry-run` for each environment. It should report nothing.
3. **Baseline** every environment: `scm --db <env> baseline`.
4. **Lock down** int1 and above (rule 2).
5. From here on, every change is a captured migration.

---

## Open questions

- Are all five environments MySQL? The spec assumes MySQL/MariaDB for the
  first version (escaping, DDL shape, `information_schema` queries). Other
  WaSQL backends would need their own schema-diff and quoting code.
- Which lookup/seed tables per project are developer-owned (capturable) vs
  business-owned (runtime)?
- Can the deploy runner reach every environment's database directly, or do
  prod/stage need to go over HTTPS (e.g. an authenticated endpoint like
  Synchronize's)? scm assumes direct DB connections.
- Should `capture` default `--against` to the next environment in
  `SCM_PIPELINE` so the flag can be omitted?
- Do we want `scm promote <from> <to>` as sugar for "apply to `<to>` exactly
  what `<from>` has", or is `up` enough?
