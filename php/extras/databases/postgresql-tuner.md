
# pg_tuner.py (Python)

A production-hardened, read-only PostgreSQL 11+ configuration and health tuner. It’s a clean Python implementation with modern defaults and JSON/text outputs.

## Highlights
- **Safe**: read-only queries only—no changes.
- **Accurate**: pulls from `pg_settings`, `pg_stat_database`, `pg_stat_bgwriter`, `pg_stat_activity`, and `pg_stat_user_tables`.
- **Portable**: PostgreSQL 11+ (tested up to 16). Uses newer metrics when available.
- **DevOps-friendly**: JSON or human text, with exit codes for CI pipelines.

## Install
```bash
# Choose one:
pip install "psycopg[binary]"   # psycopg v3
# or
pip install psycopg2-binary     # psycopg2
```

## Usage
```bash
python3 pg_tuner.py --host 127.0.0.1 --port 5432 --user audit --password '...' --dbname postgres --output text

# JSON output to a file
python3 pg_tuner.py --output json > pg_report.json

# Using env vars (PGHOST, PGUSER, PGDATABASE) and SSL
PGHOST=db.example.com PGUSER=audit PGDATABASE=postgres PGSSLMODE=require \
  python3 pg_tuner.py --output text
```

Recommended role:
```sql
GRANT pg_monitor TO audit;
```

## Checks included (sample)
- Connection headroom (active vs `max_connections`)
- Buffer cache effectiveness (hit ratio), `shared_buffers` sanity
- Temp file churn vs `work_mem`
- Autovacuum status & dead tuples (top offenders)
- Checkpoint behavior via `pg_stat_bgwriter`
- WAL/durability (`synchronous_commit`, `full_page_writes`, `wal_compression`)
- Planner stats target sanity
- Observability: `track_io_timing`
- Database sizes & top tables by `n_dead_tup` for capacity planning

## Exit codes
- `0` = OK
- `1` = Warnings present
- `2` = Critical/error
```

