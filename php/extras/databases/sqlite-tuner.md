
# sqlite-tuner.py (Python)

A production-hardened, **read-only by default** SQLite configuration and health tuner, inspired by MySQL/Postgres counterparts but tailored to SQLite's architecture.

## Highlights
- **Safe & simple**: uses SQLite URI with `mode=ro`, enables `query_only`.
- **Accurate**: evidence-based checks from PRAGMA settings (journal, synchronous, cache size, etc.), page/file stats, and schema inspection.
- **No extras needed**: pure Python stdlib (`sqlite3`). Runs anywhere Python does.
- **DevOps-friendly**: JSON or human-readable text; exit codes for CI: `0` ok, `1` warnings, `2` critical/errors.

## Install
No dependencies beyond Python 3:
```bash
python3 --version  # 3.x
```

## Usage
```bash
python3 sqlite-tuner.py --db /path/app.db --output text
python3 sqlite-tuner.py --db /path/app.db --output json > sqlite_report.json

# Optional: integrity check (can be slow on very large DBs)
python3 sqlite-tuner.py --db /path/app.db --integrity-check
```

## What it checks
- Journal/durability: `journal_mode` (WAL recommended) and `synchronous` levels
- WAL hygiene: `wal_autocheckpoint` and checkpoint stats
- Cache & pages: `cache_size`, `page_size` (with byte estimation)
- Space health: `page_count`, `freelist_count`, free space ratio (suggest `VACUUM`/incremental vacuum if high)
- Constraints & planner: `foreign_keys`, `automatic_index`, presence of `sqlite_stat1` (via `ANALYZE`)
- Concurrency: `busy_timeout`, `locking_mode`, `read_uncommitted`
- Performance touches: `temp_store`, `mmap_size`, `threads`
- Optional: `PRAGMA integrity_check`

## Exit Codes
- `0` = OK (no warnings/criticals)
- `1` = Warnings present
- `2` = Critical findings or runtime error

## Notes
- For busy production systems, consider running the tuner on a copy of the DB file (or a snapshot) to avoid read locks.
- Some recommendations (e.g., switching to WAL, changing `synchronous`) must be tested against your durability and power-loss requirements.
- To persist PRAGMA changes, apply them in your application or via a one-time migration script (PRAGMAs are not always sticky without modifying `sqlite` file header where applicable).
```

