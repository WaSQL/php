
#!/usr/bin/env python3
"""
mysql-tuner.py — A production-hardened MySQL/MariaDB health & configuration advisor.

- Safe: read-only queries only (no config or data changes).
- Accurate: uses server status, variables, and information_schema to form evidence-based suggestions.
- Portable: works with MySQL (5.7, 8.x) and MariaDB (10.x/11.x) using mysql-connector-python or PyMySQL.
- Pragmatic: emits actionable recommendations with rationale and suggested next steps.
- DevOps-friendly: JSON or text output; exit code 0 (ok), 1 (warnings found), 2 (errors).

USAGE (examples):
  python3 mysql-tuner.py --host 127.0.0.1 --user root --password ... --output text
  python3 mysql-tuner.py --socket /var/run/mysqld/mysqld.sock --output json > report.json
  MYSQL_PWD=secret python3 mysql-tuner.py --host db --user audit --require-ssl

PRIVILEGES: Prefer a read-only account with: PROCESS, REPLICATION CLIENT, SELECT on performance_schema & information_schema.
"""

import argparse
import contextlib
import datetime as dt
import json
import os
import sys
import socket
import statistics as stats
from typing import Any, Dict, List, Optional, Tuple

# ------------------------
# Optional client imports
# ------------------------
CLIENTS_TRIED = []
CONN_ERR = None

def _import_mysql_client():
    """
    Try mysql-connector-python first, then PyMySQL as a fallback.
    Returns a tuple (client_name, module, connect_fn_name, dict_cursor_kw).
    """
    global CONN_ERR
    try:
        import mysql.connector as _mc
        from mysql.connector.cursor import MySQLCursorDict
        CLIENTS_TRIED.append("mysql-connector-python")
        return ("mysql-connector-python", _mc, "connect", {"dictionary": True})
    except Exception as e:
        CONN_ERR = e
    try:
        import pymysql as _pm
        CLIENTS_TRIED.append("PyMySQL")
        return ("PyMySQL", _pm, "connect", {"cursorclass": _pm.cursors.DictCursor})
    except Exception as e:
        CONN_ERR = e
    return (None, None, None, None)

CLIENT_NAME, DBMOD, CONNECT_FN, CURSOR_KW = _import_mysql_client()

# ------------------------
# Utilities
# ------------------------
def sizeof_fmt(num: Optional[float], suffix="B") -> str:
    if num is None:
        return "N/A"
    for unit in ["","K","M","G","T","P"]:
        if abs(num) < 1024.0:
            return f"{num:,.1f}{unit}{suffix}"
        num /= 1024.0
    return f"{num:.1f}E{suffix}"

def pct(n: Optional[float], d: Optional[float]) -> str:
    try:
        if n is None or d in (None, 0):
            return "N/A"
        return f"{(100.0 * n / d):.1f}%"
    except Exception:
        return "N/A"

def safe_float(x) -> Optional[float]:
    try:
        return float(x)
    except Exception:
        return None

def safe_int(x) -> Optional[int]:
    try:
        return int(x)
    except Exception:
        return None

def guess_is_mariadb(version_comment: str) -> bool:
    return "mariadb" in (version_comment or "").lower()

def parse_args(argv=None):
    ap = argparse.ArgumentParser(
        description="Read-only MySQL/MariaDB health & configuration advisor."
    )
    ap.add_argument("--host", default=None, help="Hostname or IP")
    ap.add_argument("--port", type=int, default=None, help="TCP port (default: 3306)")
    ap.add_argument("--user", default=None, help="Username")
    ap.add_argument("--password", default=os.getenv("MYSQL_PWD"), help="Password (or use MYSQL_PWD env var)")
    ap.add_argument("--socket", default=None, help="UNIX socket path (overrides host/port)")
    ap.add_argument("--connect-timeout", type=int, default=8, help="Connection timeout seconds (default: 8)")
    ap.add_argument("--require-ssl", action="store_true", help="Require SSL for connection (recommended in prod)")
    ap.add_argument("--ssl-ca", default=None, help="Path to CA cert")
    ap.add_argument("--ssl-cert", default=None, help="Path to client cert")
    ap.add_argument("--ssl-key", default=None, help="Path to client key")

    ap.add_argument("--output", choices=["text","json"], default="text", help="Output format (default: text)")
    ap.add_argument("--report-file", default=None, help="Optional file to write full report")
    ap.add_argument("--max-runtime", type=int, default=25, help="Max time (seconds) for heavy queries (default: 25)")
    ap.add_argument("--warn-only", action="store_true", help="Exit code 0 regardless of warnings")
    ap.add_argument("--only-sections", nargs="*", default=[], help="Limit to these sections (lowercase ids)")
    ap.add_argument("--skip-sections", nargs="*", default=[], help="Skip these sections (lowercase ids)")
    ap.add_argument("--include-performance-schema", action="store_true", help="Query performance_schema extras if available")
    ap.add_argument("--json-indent", type=int, default=2, help="JSON indentation (default: 2)")
    return ap.parse_args(argv)

# ------------------------
# DB access
# ------------------------
class DB:
    def __init__(self, args: argparse.Namespace):
        if CLIENT_NAME is None:
            raise SystemExit(
                "No supported MySQL client found. Please install one of:\n"
                "  pip install mysql-connector-python\n"
                "  pip install PyMySQL\n"
                f"Import error: {CONN_ERR!r} (clients tried: {CLIENTS_TRIED})"
            )
        self.args = args
        self.conn = None

    def connect(self):
        kw = {}
        if self.args.socket:
            kw["unix_socket"] = self.args.socket
            # socket path implies local
            kw.setdefault("host", "localhost")
            kw.setdefault("port", 3306)
        else:
            kw["host"] = self.args.host or "127.0.0.1"
            kw["port"] = self.args.port or 3306
        kw["user"] = self.args.user or "root"
        kw["password"] = self.args.password or ""
        kw["connection_timeout"] = self.args.connect_timeout
        # SSL
        if self.args.require_ssl or any([self.args.ssl_ca, self.args.ssl_cert, self.args.ssl_key]):
            if CLIENT_NAME == "mysql-connector-python":
                kw["ssl_ca"] = self.args.ssl_ca
                kw["ssl_cert"] = self.args.ssl_cert
                kw["ssl_key"] = self.args.ssl_key
                kw["ssl_verify_cert"] = True if self.args.require_ssl else False
            else:  # PyMySQL
                kw["ssl"] = {}
                if self.args.ssl_ca: kw["ssl"]["ca"] = self.args.ssl_ca
                if self.args.ssl_cert: kw["ssl"]["cert"] = self.args.ssl_cert
                if self.args.ssl_key: kw["ssl"]["key"] = self.args.ssl_key
        # Connect
        connect = getattr(DBMOD, CONNECT_FN)
        self.conn = connect(**kw)
        # Dict cursor support
        self.cursor_kwargs = CURSOR_KW

    def query(self, sql: str, params: Optional[Tuple]=None) -> List[Dict[str,Any]]:
        if self.conn is None:
            self.connect()
        with self.conn.cursor(**self.cursor_kwargs) as cur:
            cur.execute(sql, params or ())
            rows = cur.fetchall()
        return rows

    def query_one(self, sql: str, params: Optional[Tuple]=None) -> Optional[Dict[str,Any]]:
        rows = self.query(sql, params)
        return rows[0] if rows else None

    def server_version(self) -> Tuple[int,int,int]:
        row = self.query_one("SELECT VERSION() AS v")
        if not row or not row.get("v"):
            return (0,0,0)
        v = row["v"]
        # Parse '8.0.36-0ubuntu0.22.04.1' or '10.11.7-MariaDB'
        import re
        m = re.search(r"(\d+)\.(\d+)\.(\d+)", v)
        if not m:
            return (0,0,0)
        return tuple(int(g) for g in m.groups()) # type: ignore

# ------------------------
# Data collection
# ------------------------
def collect_status_vars(db: DB) -> Tuple[Dict[str,Any], Dict[str,Any], Dict[str,Any]]:
    status_rows = db.query("SHOW /*!50002 GLOBAL */ STATUS")
    variables_rows = db.query("SHOW /*!50002 GLOBAL */ VARIABLES")
    status = {r["Variable_name"].lower(): r["Value"] for r in status_rows}
    variables = {r["Variable_name"].lower(): r["Value"] for r in variables_rows}

    ver_comment_row = db.query_one("SHOW VARIABLES LIKE 'version_comment'")
    version_comment = (ver_comment_row or {}).get("Value", "")
    return status, variables, {"version_comment": version_comment}

def collect_innodb_metrics(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.query("SHOW ENGINE INNODB STATUS")
        if rows and "Status" in rows[0]:
            out["innodb_status_text"] = rows[0]["Status"]
    except Exception:
        pass
    # Pull useful metrics from variables/status
    return out

def collect_info_schema(db: DB, max_runtime: int=25) -> Dict[str,Any]:
    d: Dict[str,Any] = {}
    try:
        d["largest_tables"] = db.query("""
            SELECT TABLE_SCHEMA, TABLE_NAME, ENGINE,
                   DATA_LENGTH + INDEX_LENGTH AS total_bytes,
                   DATA_LENGTH AS data_bytes, INDEX_LENGTH AS index_bytes,
                   TABLE_ROWS
            FROM information_schema.tables
            WHERE TABLE_SCHEMA NOT IN ('mysql','performance_schema','information_schema','sys')
              AND TABLE_TYPE='BASE TABLE'
            ORDER BY total_bytes DESC
            LIMIT 25
        """)
    except Exception:
        d["largest_tables"] = []

    try:
        d["engine_usage"] = db.query("""
            SELECT ENGINE, COUNT(*) AS tables, SUM(DATA_LENGTH+INDEX_LENGTH) AS total_bytes
            FROM information_schema.tables
            WHERE TABLE_SCHEMA NOT IN ('mysql','performance_schema','information_schema','sys')
              AND TABLE_TYPE='BASE TABLE'
            GROUP BY ENGINE
            ORDER BY total_bytes DESC
        """)
    except Exception:
        d["engine_usage"] = []

    return d

# ------------------------
# Heuristics & rules
# ------------------------
class Finding:
    def __init__(self, severity: str, title: str, detail: str, advice: Optional[str]=None, tags: Optional[List[str]]=None):
        self.severity = severity  # "ok"|"info"|"warn"|"crit"
        self.title = title
        self.detail = detail
        self.advice = advice or ""
        self.tags = tags or []

    def to_dict(self):
        return dict(severity=self.severity, title=self.title, detail=self.detail, advice=self.advice, tags=self.tags)

def boolvar(v: Any) -> Optional[bool]:
    if v is None: return None
    s = str(v).strip().lower()
    if s in ("1","on","true","yes"): return True
    if s in ("0","off","false","no"): return False
    return None

def intvar(v: Any) -> Optional[int]:
    try:
        return int(str(v).split()[0])
    except Exception:
        return None

def bytesvar(v: Any) -> Optional[int]:
    """Parse my.cnf style memory values like '128M', '1G', or raw bytes"""
    if v is None: return None
    s = str(v).strip().upper()
    try:
        if s.endswith("K"): return int(float(s[:-1]) * 1024)
        if s.endswith("M"): return int(float(s[:-1]) * 1024**2)
        if s.endswith("G"): return int(float(s[:-1]) * 1024**3)
        if s.endswith("T"): return int(float(s[:-1]) * 1024**4)
        return int(float(s))
    except Exception:
        return None

def rule_conn_capacity(status, vars, findings: List[Finding]):
    max_conn = intvar(vars.get("max_connections"))
    threads_connected = intvar(status.get("threads_connected"))
    max_used = intvar(status.get("max_used_connections"))
    if max_conn and max_used:
        use_pct = 100.0 * max_used / max_conn
        detail = f"Max used connections {max_used}/{max_conn} ({use_pct:.1f}%)."
        if use_pct >= 85:
            findings.append(Finding("warn", "High peak connection usage", detail,
                advice="Raise max_connections cautiously, or use pooling / limit client concurrency." ,
                tags=["connections"]))
        else:
            findings.append(Finding("ok", "Connection headroom", detail, tags=["connections"]))

    thr_cache = intvar(vars.get("thread_cache_size"))
    thr_created = intvar(status.get("threads_created"))
    if thr_cache is not None and thr_created is not None:
        # Rough heuristic: during long uptimes, too many thread creations suggest raising thread_cache_size
        uptime = intvar(status.get("uptime")) or 0
        rate = (thr_created / max(uptime,1)) if uptime>0 else thr_created
        if uptime > 600 and rate > 0.01:
            findings.append(Finding("info", "Thread cache may be small",
                f"{thr_created:,} threads created over {uptime:,}s (rate ~{rate:.3f}/s).",
                advice="Consider increasing thread_cache_size to reduce thread creation churn.",
                tags=["threads"]))


def rule_tmp_tables(status, vars, findings: List[Finding]):
    on_disk = intvar(status.get("created_tmp_disk_tables")) or 0
    total = intvar(status.get("created_tmp_tables")) or 1
    ratio = 100.0 * on_disk / max(total,1)
    tmp = bytesvar(vars.get("tmp_table_size"))
    heap = bytesvar(vars.get("max_heap_table_size"))
    eff = min([x for x in [tmp, heap] if x is not None], default=None)
    d = f"Tmp disk ratio {ratio:.1f}% (disk {on_disk:,} / total {total:,}). Effective tmp_table_size={sizeof_fmt(eff) if eff else 'N/A'}."
    if ratio > 25 and eff and eff < 64*1024**2:
        findings.append(Finding("warn", "Many temp tables spilled to disk", d,
            advice="Increase tmp_table_size and max_heap_table_size (keep equal). Review queries creating large tmp tables.",
            tags=["tmp","performance"]))
    else:
        findings.append(Finding("ok", "Tmp tables", d, tags=["tmp","performance"]))


def rule_joins_indexes(status, vars, findings: List[Finding]):
    full_join = intvar(status.get("select_full_join")) or 0
    range_check = intvar(status.get("select_range_check")) or 0
    if full_join > 0 or range_check > 0:
        findings.append(Finding("warn", "Joins without proper indexes detected",
            f"SELECT_FULL_JOIN={full_join:,}, SELECT_RANGE_CHECK={range_check:,}.",
            advice="Add missing indexes for join conditions and WHERE clauses. Check slow queries for table scans.",
            tags=["query","indexes"]))


def rule_sorts(status, vars, findings: List[Finding]):
    merge_passes = intvar(status.get("sort_merge_passes")) or 0
    if merge_passes > 0:
        findings.append(Finding("info", "Sorts require merge passes",
            f"SORT_MERGE_PASSES={merge_passes:,}.",
            advice="Increase sort_buffer_size cautiously and ensure enough tmp space.",
            tags=["sort","buffers"]))


def rule_table_cache(status, vars, findings: List[Finding]):
    opened = intvar(status.get("opened_tables")) or 0
    opens = intvar(status.get("table_open_cache")) or None
    if opened > 400 and opens and opens < 1024:
        findings.append(Finding("info", "Table cache might be small",
            f"OPENED_TABLES={opened:,}, table_open_cache={opens}.",
            advice="Increase table_open_cache if sustained workload causes frequent table reopen.",
            tags=["cache"]))


def rule_innodb_buffer(status, vars, findings: List[Finding]):
    bp_size = bytesvar(vars.get("innodb_buffer_pool_size"))
    data_reads = intvar(status.get("innodb_buffer_pool_reads")) or 0
    d = f"InnoDB buffer pool size {sizeof_fmt(bp_size)}. Buffer pool reads (disk)={data_reads:,}."
    if bp_size and bp_size < 1*1024**3:
        findings.append(Finding("info", "Small InnoDB buffer pool", d,
            advice="Consider setting innodb_buffer_pool_size to ~50-75% of system memory for dedicated DB servers.",
            tags=["innodb","memory"]))
    else:
        findings.append(Finding("ok", "InnoDB buffer pool", d, tags=["innodb","memory"]))


def rule_flush_durability(status, vars, findings: List[Finding]):
    trx = intvar(vars.get("innodb_flush_log_at_trx_commit"))
    syncb = intvar(vars.get("sync_binlog"))
    if trx == 1:
        detail = "innodb_flush_log_at_trx_commit=1 (full ACID)."
        findings.append(Finding("ok", "InnoDB durability (ACID)", detail, tags=["durability","innodb"]))
    elif trx in (2, 0):
        detail = f"innodb_flush_log_at_trx_commit={trx} (reduced fsyncs, risk of data loss on crash)."
        findings.append(Finding("info", "InnoDB durability reduced", detail,
            advice="Use 1 for full durability, or 2 on low-latency SSD with UPS for balance.", tags=["durability","innodb"]))

    if syncb is not None:
        if syncb == 1:
            findings.append(Finding("ok", "Binlog durability", "sync_binlog=1.", tags=["durability","binlog"]))
        elif syncb == 0:
            findings.append(Finding("warn", "Binlog not synchronized", "sync_binlog=0 (risk binlog loss on crash).",
                advice="Set sync_binlog=1 for full safety, or >=100 for throughput with acceptable risk.",
                tags=["durability","binlog"]))


def rule_binlog_rotation(status, vars, findings: List[Finding]):
    exp_days = intvar(vars.get("expire_logs_days"))
    exp_sec = intvar(vars.get("binlog_expire_logs_seconds"))
    if exp_days or exp_sec:
        findings.append(Finding("ok", "Binlog expiration configured",
            f"expire_logs_days={exp_days}, binlog_expire_logs_seconds={exp_sec}.",
            tags=["binlog","maintenance"]))
    else:
        findings.append(Finding("info", "Binlog expiration not set",
            "Consider setting binlog_expire_logs_seconds (or expire_logs_days) to control disk usage.",
            tags=["binlog","maintenance"]))


def rule_slow_log(status, vars, findings: List[Finding]):
    slow_on = boolvar(vars.get("slow_query_log"))
    long_time = safe_float(vars.get("long_query_time"))
    slow_cnt = intvar(status.get("slow_queries")) or 0
    if slow_on:
        findings.append(Finding("ok", "Slow query log enabled",
            f"long_query_time={long_time}, slow_queries={slow_cnt:,}.",
            tags=["observability"]))
    else:
        findings.append(Finding("info", "Slow query log disabled",
            "Enable slow_query_log to capture problematic queries; set long_query_time (e.g., 0.5-1s).",
            tags=["observability"]))


def rule_open_files(vars, findings: List[Finding]):
    ofl = intvar(vars.get("open_files_limit"))
    if ofl and ofl < 1024:
        findings.append(Finding("warn", "Low open_files_limit", f"open_files_limit={ofl}.",
            advice="Increase to at least 4096 or more depending on workload.",
            tags=["limits"]))


def rule_performance_schema(vars, findings: List[Finding]):
    ps = boolvar(vars.get("performance_schema"))
    if ps:
        findings.append(Finding("ok", "Performance schema enabled", "performance_schema=ON.", tags=["observability"]))
    else:
        findings.append(Finding("info", "Performance schema disabled",
            "Consider enabling performance_schema for deeper diagnostics (minor overhead).",
            tags=["observability"]))


def evaluate(status: Dict[str,Any], vars: Dict[str,Any], extras: Dict[str,Any]) -> List[Finding]:
    findings: List[Finding] = []
    rule_conn_capacity(status, vars, findings)
    rule_tmp_tables(status, vars, findings)
    rule_joins_indexes(status, vars, findings)
    rule_sorts(status, vars, findings)
    rule_table_cache(status, vars, findings)
    rule_innodb_buffer(status, vars, findings)
    rule_flush_durability(status, vars, findings)
    rule_binlog_rotation(status, vars, findings)
    rule_slow_log(status, vars, findings)
    rule_open_files(vars, findings)
    rule_performance_schema(vars, findings)
    return findings

# ------------------------
# Output formatting
# ------------------------
def summarize(findings: List[Finding]) -> Dict[str,int]:
    counts = {"ok":0, "info":0, "warn":0, "crit":0}
    for f in findings:
        counts[f.severity] = counts.get(f.severity, 0) + 1
    return counts

def print_text_report(meta: Dict[str,Any], findings: List[Finding], sections: Dict[str,Any]):
    print("# MySQL Tuner Report")
    print(f"Generated: {dt.datetime.utcnow().isoformat()}Z")
    print(f"Client: {CLIENT_NAME}")
    print(f"Server: {meta.get('version_string','?')} ({meta.get('version_comment','')})")
    print(f"Uptime: {meta.get('uptime_human','N/A')}")
    print("")

    counts = summarize(findings)
    print("Summary:", counts)
    print("-"*80)
    for f in findings:
        badge = {"ok":"[OK ]", "info":"[INFO]", "warn":"[WARN]", "crit":"[CRIT]"}[f.severity]
        print(f"{badge} {f.title}")
        print(f"      {f.detail}")
        if f.advice:
            print(f"      Advice: {f.advice}")
        if f.tags:
            print(f"      Tags: {', '.join(f.tags)}")
        print("-"*80)

    print("\nSections:")
    for name, data in sections.items():
        print(f"== {name} ==")
        if isinstance(data, list):
            for row in data[:25]:
                print("  - " + ", ".join(f"{k}={row[k]}" for k in row.keys()))
        else:
            print(json.dumps(data, indent=2, default=str))


def build_meta(db: DB, status: Dict[str,Any], vars: Dict[str,Any], extras: Dict[str,Any]) -> Dict[str,Any]:
    version_tuple = db.server_version()
    version_string = ".".join(str(x) for x in version_tuple if x is not None)
    uptime = safe_int(status.get("uptime")) or 0
    uptime_h = f"{uptime//86400}d {(uptime%86400)//3600}h"
    return {
        "version_tuple": version_tuple,
        "version_string": version_string,
        "version_comment": extras.get("version_comment",""),
        "uptime_seconds": uptime,
        "uptime_human": uptime_h,
    }

def main(argv=None) -> int:
    args = parse_args(argv)
    try:
        db = DB(args)
        status, vars, extras = collect_status_vars(db)
        info = collect_info_schema(db, args.max_runtime)
        meta = build_meta(db, status, vars, extras)
        findings = evaluate(status, vars, extras)

        sections: Dict[str,Any] = {}
        sections["Variables (subset)"] = [
            {"name": "innodb_buffer_pool_size", "value": vars.get("innodb_buffer_pool_size")},
            {"name": "max_connections", "value": vars.get("max_connections")},
            {"name": "thread_cache_size", "value": vars.get("thread_cache_size")},
            {"name": "tmp_table_size", "value": vars.get("tmp_table_size")},
            {"name": "max_heap_table_size", "value": vars.get("max_heap_table_size")},
            {"name": "open_files_limit", "value": vars.get("open_files_limit")},
            {"name": "slow_query_log", "value": vars.get("slow_query_log")},
            {"name": "long_query_time", "value": vars.get("long_query_time")},
        ]
        sections["Status (subset)"] = [
            {"name": "Threads_connected", "value": status.get("threads_connected")},
            {"name": "Max_used_connections", "value": status.get("max_used_connections")},
            {"name": "Threads_created", "value": status.get("threads_created")},
            {"name": "Created_tmp_tables", "value": status.get("created_tmp_tables")},
            {"name": "Created_tmp_disk_tables", "value": status.get("created_tmp_disk_tables")},
            {"name": "Select_full_join", "value": status.get("select_full_join")},
            {"name": "Select_range_check", "value": status.get("select_range_check")},
            {"name": "Sort_merge_passes", "value": status.get("sort_merge_passes")},
            {"name": "Slow_queries", "value": status.get("slow_queries")},
        ]
        sections["Largest tables (top 25)"] = info.get("largest_tables", [])
        sections["Engine usage"] = info.get("engine_usage", [])

        out = {
            "meta": meta,
            "findings": [f.to_dict() for f in findings],
            "sections": sections,
            "client": CLIENT_NAME,
        }

        if args.output == "json":
            j = json.dumps(out, indent=args.json_indent, default=str)
            if args.report_file:
                with open(args.report_file, "w") as f:
                    f.write(j)
            print(j)
        else:
            if args.report_file:
                with open(args.report_file, "w") as f:
                    # Write text report to file as well
                    sys.stdout = f
                    print_text_report(meta, findings, sections)
                    sys.stdout = sys.__stdout__
            print_text_report(meta, findings, sections)

        # exit code policy
        sev = summarize(findings)
        if args.warn_only:
            return 0
        if sev.get("crit",0) > 0:
            return 2
        if sev.get("warn",0) > 0:
            return 1
        return 0

    except Exception as e:
        msg = f"ERROR: {e.__class__.__name__}: {e}"
        if args.output == "json":
            print(json.dumps({"error": msg, "clients_tried": CLIENTS_TRIED}, indent=args.json_indent))
        else:
            print(msg)
        return 2

if __name__ == "__main__":
    sys.exit(main())
