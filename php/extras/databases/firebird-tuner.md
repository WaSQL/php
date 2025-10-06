
# firebird-tuner.py (Python)

A production-hardened, read-only Firebird (2.5/3.0/4.0/5.0) configuration & health tuner. It mirrors the MySQL/Postgres/SQLite/Oracle/SQL Server tuners with Firebird-specific checks on MON$ tables.

## Highlights
- **Safe**: read-only queries--no `gfix`/`gstat` or settings changes.
- **Accurate**: uses `MON$DATABASE`, `MON$ATTACHMENTS`, `MON$TRANSACTIONS`, `MON$IO_STATS`, and `MON$RECORD_STATS` when available.
- **Portable**: works with Classic/SuperClassic/SuperServer; tries `firebird-driver` first, `fdb` as fallback.
- **DevOps-friendly**: JSON or human-readable text; exit codes: `0` ok, `1` warn, `2` critical/error.

## Install
```bash
pip install firebird-driver   # preferred modern driver
# or legacy:
pip install fdb
```

## Usage
```bash
# Basic
python3 firebird-tuner.py --host 127.0.0.1 --port 3050 --db /path/to/database.fdb --user sysdba --password '...'

# Using DSN string
python3 firebird-tuner.py --dsn "localhost:/path/to/database.fdb" --user sysdba --password '...' --output json > fb_report.json

# Long-running tx threshold
python3 firebird-tuner.py --dsn "host:/path/db.fdb" --user ... --password ... --long-tx-minutes 30
```

## Checks included (sample)
- **Durability**: `MON$FORCED_WRITES` (warn if off).
- **Sweep hygiene**: `MON$SWEEP_INTERVAL` (warn if 0 or very small).
- **Page/cache sizing**: `MON$PAGE_SIZE` (warn < 8KiB), `MON$PAGE_BUFFERS` (warn < 2048).
- **Dialect**: flag legacy dialect < 3.
- **Transaction health**: OIT/OAT/Next Tx gap (if exposed), long-running active transactions (threshold configurable).
- **Connections**: current attachments; top I/O attachment from `MON$IO_STATS`.
- **I/O & record activity**: attachment-level page reads/writes, record ops (if available).

## Exit Codes
- `0` = OK
- `1` = Warnings present
- `2` = Critical/error
Use `--warn-only` to force 0.

## Notes
- Some columns vary by Firebird version; the tuner degrades gracefully if a column or MON$ view is missing.
- For deep engine-level details (e.g., header stats, ODS flags), `gstat -h` is authoritative. This script avoids shelling out for portability/safety.
- Always test recommended changes in staging. Engine type (Classic vs SuperServer), platform, and workload (OLTP vs batch) influence the best settings.
```

