# Teaching SCM — Outline

A top-to-bottom guide for explaining SCM to others. Builds from concept → mechanics →
the two ways to actually use it (by hand, and with Claude/AI).

## 1. The concept (why SCM exists)
- **What it is:** SCM = "version control for your database schema." Every schema change is a plain SQL file, applied in order and tracked.
- **The problem it solves:** hand-run SQL gets forgotten, run twice, or lost; dev/stage/prod drift apart; no history; rollbacks are guesswork.
- **The mental model:** `SQL migration files → scm → database`. Files live in git; SCM applies only what's missing so every environment ends up identical.
- **What a "migration" is:** one change, with an **up** (apply) and a **down** (reverse).

## 2. How it tracks state (the one thing to really understand)
- SCM keeps a `schema_migrations` table in the database — a list of versions already applied.
- `up` inserts a row; `down` deletes one. If a version has a row, `up` skips it.
- This is *how* "run it anywhere, applies only what's missing" works — worth demoing live.
- "Orphaned" = a row exists but the file is gone; "pending" = file exists but no row yet.
- Each row also records **who** applied it and **when** (`applied_by`, `applied_at`) — `scm report` turns this into an activity summary, and `scm ddl` verifies the table has all those columns. scm **self-heals** older/hand-built tables by adding any missing tracking columns on the next run (see `scm.md` for the per-database details).
- On PostgreSQL the table is pinned to the **`public`** schema by default so `search_path` differences can't scatter it; override with `MIGRATIONS_SCHEMA` in `.env`.

## 3. The migration file
- **File name:** `20240601120000_create_users.sql` → digits = **version** (what's tracked), text = **label** (for humans). Files run in version order.
- **Single-file style (default):** one file, `-- migrate:up` / `-- migrate:down` markers.
- **Two-file style:** `.up.sql` + `.down.sql`.
- **Two golden rules:**
  - Always write a **down** (or a comment saying why it's irreversible).
  - **Never edit an applied migration** — write a new one to fix it. Once run anywhere, it's immutable.

## 4. Setup (once per database)
- WaSQL project: `scm env-from-config <name>` — pulls the connection from `config.xml`, writes `.env`, creates `migrations/`.
- Any project: `scm init`, then edit `.env` to set `DATABASE_URL`.
- `scm who` — confirm which database you're pointed at **before** running anything.
- Note the `.env` file holds the connection; never commit real credentials.

## 5. The everyday loop
- `scm new <name>` → creates the timestamped file
- edit the file → write your `up` and `down` SQL
- `scm status` → see pending vs applied
- `scm up` → apply pending migrations
- `scm down` → roll back the last one; `scm goto <version>` → jump to an exact point
- `--dry-run` → preview the SQL without touching the DB

## 6. The core command set (keep this short for beginners)
- **Daily:** `new`, `status`, `up`, `down`
- **Handy:** `goto`, `history`, `report`, `ddl`, `show`, `learn`
- **Careful (destructive/repair):** `reset`, `undo`, `repair`, `baseline`

---

## 7. Using SCM **without** AI (by hand)
- You are the author: you decide the change, run `scm new`, and **write the SQL yourself**.
- Typical session:
  1. `git checkout -b migration/add_orders_table`
  2. `scm new add_orders_table`
  3. Open the file, write `CREATE TABLE …` under `-- migrate:up` and `DROP TABLE …` under `-- migrate:down`.
  4. `scm status` to confirm it's pending → `scm up 1` to apply just it.
  5. Verify in the DB, then commit the file.
- You own correctness: use `IF EXISTS`/`IF NOT EXISTS`, test the `down`, watch DDL that isn't transactional (MySQL). If a statement refuses to run inside a transaction at all (e.g. Postgres `CREATE INDEX CONCURRENTLY`), add `transaction:false` after the marker (see `scm.md`).
- Good for: people who know SQL well, or changes too sensitive to delegate.

## 8. Using SCM **with** AI / Claude
- **The key rule that changes everything:** Claude is told **never to run DDL directly** (no `CREATE`/`ALTER`/`DROP` through a DB tool). It must always produce an SCM **migration file** instead. This is what keeps AI-made changes tracked, reviewable, and reversible.
- **What Claude does for you:**
  - Creates a git branch first (`migration/<name>`).
  - Runs `scm new <name>` (never hand-names files — lets the timestamp handle ordering).
  - Writes the `up` **and** `down` SQL, including `IF EXISTS` guards.
  - Hands you the file path and reminds you to run `scm up`.
- **What stays with you (the human):** reviewing the generated SQL, and actually running `scm up` to apply it. Claude writes; you approve and apply.
- **MCP angle (if your team uses it):** Claude can inspect the live schema through the MCP server (list tables, fields, indexes) to write an accurate migration — *reading* the DB to inform the file, not *writing* to the DB directly.
- **Why this is safer than "just ask AI to change the DB":** every AI change becomes a reviewed SQL file in git with a rollback, instead of an invisible one-off command.

## 9. Wrap-up talking points
- Same files, same commands whether a human or Claude wrote them — SCM is the common contract.
- The workflow is identical across all supported databases (Postgres, MySQL, SQL Server, Oracle, HANA, Snowflake, etc.); only the connection string changes.
- "If it's not a migration, it didn't happen" — the discipline, for both people and AI, is that schema only changes through SCM.
