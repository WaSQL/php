
# duckdb_tuner.py (Python)

A production-hardened, read-only DuckDB configuration & health tuner. It mirrors the other RDBMS tuners but uses DuckDB’s built‑in system tables and pragmas.

## Highlights
- **Safe**: opens the database in read‑only mode and runs introspection queries only.
- **Accurate**: relies on `duckdb_settings()`, `PRAGMA database_size`, and `duckdb_*()` catalog functions.
- **Portable**: pure Python + `duckdb` package.
- **DevOps-friendly**: JSON or human-readable text; exit codes: `0` ok, `1` warn, `2` critical/error.

## Install
```bash
pip install duckdb
```

## Usage
```bash
python3 duckdb_tuner.py --db /path/to/analytics.duckdb --output text

# JSON
python3 duckdb_tuner.py --db /path/to/analytics.duckdb --output json > duckdb_report.json
```

## Checks included (sample)
- **Parallelism**: `threads` vs host CPU count (auto/low/manual).
- **Memory**: `memory_limit` (bytes or %) sanity.
- **Temp**: `temp_directory` presence for large spill workloads.
- **Caching**: `enable_object_cache` suggestion for repeated external file scans.
- **Security posture**: `autoinstall_known_extensions` warning in locked‑down environments.
- **I/O hints**: `filesystem_read_ahead` low values flagged for object storage.
- **Capacity**: quick `PRAGMA database_size` snapshot.
- **Catalog**: samples from `duckdb_tables()`, `duckdb_columns()`, and `PRAGMA storage_info` on a few tables.

## Exit codes
- `0` = OK
- `1` = Warnings present
- `2` = Critical/error
Use `--warn-only` to force 0.

## Notes
- DuckDB evolves quickly; some settings may be absent on older builds. The tuner handles missing fields gracefully.
- For S3/HTTP sources, consider enabling the `httpfs` extension and tuning read‑ahead; install extensions explicitly in production.
- Test any configuration changes against your workload (local file vs object store, single‑user vs concurrent BI, etc.).
```

