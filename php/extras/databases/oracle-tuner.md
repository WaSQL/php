
# oracle_tuner.py (Python)

A production-hardened, read-only Oracle Database health & configuration tuner. It mirrors the MySQL/Postgres/SQLite tuners with Oracle-centric checks.

## Highlights
- **Safe**: read-only queries against V$ and DBA_* views (no changes).
- **Accurate**: evidence-based heuristics from resource limits, SGA/PGA stats, system statistics, wait classes, redo activity, and tablespace capacity.
- **Portable**: works with Oracle 12c through 23ai using `python-oracledb` (thin) or `cx_Oracle`.

## Install
```bash
# Preferred:
pip install oracledb

# Or fallback:
pip install cx_Oracle
```

## Usage
```bash
# EZCONNECT style
python3 oracle_tuner.py --user audit --password '...' --dsn "dbhost:1521/orclpdb1" --output text

# TNS alias (requires config in ORACLE_HOME/network/admin or --config-dir)
python3 oracle_tuner.py --user audit --password '...' --dsn "ORCLPDB1" --output json > ora_report.json
```

### Recommended privileges
For full visibility, grant one of:
```sql
GRANT SELECT_CATALOG_ROLE TO audit;
-- or
GRANT SELECT ANY DICTIONARY TO audit;
```

## Checks included (sample)
- **Connections/Processes**: utilization vs. `v$resource_limit` (sessions/processes)
- **Memory**: AMM/ASMM and targets (`memory_target`, `sga_target`, `pga_aggregate_target`), SGA/PGA quick sanity
- **Cache effectiveness**: Buffer Cache Hit Ratio from `v$sysstat`
- **Wait profile**: top non-idle `v$system_wait_class`
- **Redo cadence**: log switches in the last 24h from `v$log_history`
- **Tablespace capacity**: free vs total from `dba_data_files` + `dba_free_space`; temp usage; undo presence

## Output & Exit Codes
- `--output text` prints a human-readable report.
- `--output json` prints a structured object with `meta`, `findings`, and `sections`.
- Exit codes: `0` ok, `1` warnings, `2` critical/errors (`--warn-only` to force 0).

## Notes
- Some sections require DBA-level views. If you see empty sections, grant the privileges above or run as a user with appropriate rights.
- For deeper analysis, pair this with AWR/ASH (licensed features). This tuner avoids AWR queries by default.
- Always validate recommendations in staging before changing production parameters.
```

