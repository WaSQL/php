
# hana_tuner.py (Python)

A production-hardened, read-only SAP HANA (HANA 1.0 SPS12+ and HANA 2.0) configuration & health tuner. It mirrors the MySQL/Postgres/SQLite/Oracle/SQL Server/Firebird tuners with HANA-specific checks against SYS.M_* monitoring views.

## Highlights
- **Safe**: read-only queries only—no configuration changes.
- **Accurate**: uses `SYS.M_DATABASE`, `M_HOST_INFORMATION`, `M_HOST_RESOURCE_UTILIZATION`, `M_SERVICE_MEMORY`, `M_INIFILE_CONTENTS`, `M_CS_TABLES`, `M_EXPENSIVE_STATEMENTS`, `M_TRANSACTIONS`, `M_VOLUME_IO_TOTAL_STATISTICS`, `M_BACKUP_CATALOG`, etc.
- **Portable**: works with `hdbcli` (official) or `pyhdb` as fallback.
- **DevOps-friendly**: JSON or human-readable text; exit codes: `0` ok, `1` warn, `2` critical/error.

## Install
```bash
# Preferred official SAP driver
pip install hdbcli
# Fallback
pip install pyhdb
```

## Usage
```bash
python3 hana_tuner.py --host hana.example.com --port 30015 --user MONITOR --password '...' --output text

# JSON report
python3 hana_tuner.py --host hana --port 30013 --user MONITOR --password '...' --output json > hana_report.json

# Tune windows
python3 hana_tuner.py --expensive-window-min 120 --long-tx-minutes 45
```

## Checks included (sample)
- **Memory**: host usage vs RAM; `global_allocation_limit` sanity; `statement_memory_limit` bounds.
- **Persistence**: `savepoint_interval_s` heuristics.
- **Column store**: delta vs main memory; flags large delta needing merges.
- **Workload**: presence of `M_EXPENSIVE_STATEMENTS` in a time window.
- **Transactions**: long-running transactions beyond a threshold.
- **I/O**: volume I/O statistics and disk usage snapshots.
- **Backups**: shows recent backup catalog entries.
- **Connections**: active connection count.

## Exit codes
- `0` = OK
- `1` = Warnings present
- `2` = Critical/error
Use `--warn-only` to force 0.

## Notes
- Some views/columns differ across HANA versions. The tuner degrades gracefully if a view isn’t available.
- For deep analysis (PlanViz exports, workload management queue stats, delta merge optimization hints), extend the script with system-specific rules.
- Always validate recommendations in a non-production system before changing parameters.
```

