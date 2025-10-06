
# mssql_tuner.py (Python)

A production-hardened, **read-only** SQL Server (2012+ and Azure SQL) configuration & health tuner. It mirrors the MySQL/Postgres/SQLite/Oracle tuners with SQL Server–specific checks and DMVs.

## Highlights
- **Safe**: DMV queries only—no config changes.
- **Accurate**: pulls from `sys.configurations`, `sys.dm_os_sys_info`, `sys.dm_os_wait_stats`, `sys.dm_io_virtual_file_stats`, `sys.dm_os_performance_counters`, `tempdb.sys.database_files`, and more.
- **Portable**: SQL Server 2012+ and Azure SQL Database. Uses `pyodbc` or `pymssql`.
- **DevOps-friendly**: JSON or human-readable text; exit codes for CI: `0` ok, `1` warn, `2` critical/error.

## Install
```bash
# Preferred client
pip install pyodbc
# (Linux/macOS may require unixODBC + Microsoft ODBC Driver for SQL Server)

# Fallback
pip install pymssql
```

## Usage
```bash
# Windows Auth
python3 mssql_tuner.py --server "tcp:sql01,1433" --driver "ODBC Driver 18 for SQL Server" --trusted

# SQL Auth
python3 mssql_tuner.py --server "tcp:sql01,1433" --database master --user sa --password '...' --output text

# Azure SQL
python3 mssql_tuner.py --server "tcp:yourserver.database.windows.net,1433" --database yourdb --user user --password '...' --encrypt --trustservercert --output json > report.json
```

## Checks included (sample)
- **Memory**: `max server memory (MB)` vs host RAM (leave OS headroom).
- **Parallelism**: `MAXDOP` and `cost threshold for parallelism` sanity.
- **Plan cache**: `optimize for ad hoc workloads`.
- **TempDB**: number of data files vs CPU up to 8; uniform growth settings.
- **Waits**: top waits excluding benign; hints for IO (`PAGEIOLATCH_*`), log (`WRITELOG`), parallelism (`CXPACKET/CXCONSUMER`).
- **IO**: per-file average stall (ms/IO) from `sys.dm_io_virtual_file_stats`.
- **VLFs**: counts via `sys.dm_db_log_info` (2016 SP2+/Azure).
- **Buffer cache**: Buffer cache hit ratio & PLE (raw counters; trend over time recommended).
- **Sizes**: database data/log sizes.

## Exit codes
- `0` = OK
- `1` = Warnings present
- `2` = Critical/error
Use `--warn-only` to force 0.

## Notes
- Some guidance (MAXDOP, cost threshold, tempdb files) depends on workload (OLTP vs DW) and NUMA; validate in staging.
- For deeper analysis (query store, top resource consumers, index/heap health), extend with `--deep` logic using Query Store and `sys.dm_db_index_physical_stats` (left out here for speed).
- On Azure SQL Database, certain DMV columns may be scoped per DB; the script handles it gracefully.
```

