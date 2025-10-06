
# mysql-tuner.py (Python)

A production‑hardened, read‑only MySQL/MariaDB configuration and health tuner. It takes inspiration from `mysqltuner.pl`, but is a clean Python implementation with modern defaults and JSON output.

## Highlights
- **Safe**: read‑only queries only--no config changes.
- **Accurate**: evidence‑based rules from GLOBAL STATUS/VARIABLES & information_schema.
- **Portable**: works with MySQL 5.7/8.x and MariaDB 10.x/11.x.
- **DevOps‑friendly**: JSON or human‑readable text; non‑zero exit codes on findings.
- **Secure**: optional SSL with `--require-ssl`; supports UNIX socket authentication.

## Install
```bash
pip install mysql-connector-python  # or: pip install PyMySQL
```

## Usage
```bash
# TCP
python3 mysql-tuner.py --host 127.0.0.1 --user audit --password secret --output text

# Local socket
python3 mysql-tuner.py --socket /var/run/mysqld/mysqld.sock --output json > report.json

# With SSL
python3 mysql-tuner.py --host db --user audit --password secret --require-ssl --ssl-ca /path/ca.pem
```

Recommended minimal privileges for the `audit` user:
```
GRANT PROCESS, REPLICATION CLIENT ON *.* TO 'audit'@'%';
GRANT SELECT ON performance_schema.* TO 'audit'@'%';
GRANT SELECT ON information_schema.* TO 'audit'@'%';
```

## What it checks (sample)
- Peak connection headroom & thread cache churn
- Temp table spill ratio and effective tmp_table_size
- Joins without indexes (SELECT_FULL_JOIN / SELECT_RANGE_CHECK)
- Sort merge passes (sort_buffer_size considerations)
- Table cache efficiency
- InnoDB buffer pool sizing hints
- Durability settings (innodb_flush_log_at_trx_commit, sync_binlog)
- Binlog expiration hygiene
- Slow query log status and long_query_time
- open_files_limit sanity
- performance_schema status
- Largest tables & engine usage (top 25) for capacity planning

## Output & Exit Codes
- `--output text` prints a human‑readable report.
- `--output json` prints a machine‑readable object with `meta`, `findings`, and `sections`.
- Exit codes: `0` (ok), `1` (warnings), `2` (errors). Use `--warn-only` to force `0`.

## Notes
- This script **does not** modify server settings. Apply suggested changes in staging first.
- For deep dive query analysis, pair this tool with slow query logs and `performance_schema` digests.
```

