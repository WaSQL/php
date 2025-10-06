
#!/usr/bin/env python3
"""
hana_tuner.py — A production-hardened SAP HANA (1.0 SPS12+ / HANA 2.0) health & configuration advisor (read-only).

- Safe: read-only system view queries only (no ALTER/DDL/DML).
- Accurate: evidence-based suggestions from SYS/M_* monitoring views and ini parameters.
- Portable: uses hdbcli (official) or pyhdb as a fallback.
- DevOps-friendly: JSON or text output; exit codes 0 (ok), 1 (warnings), 2 (critical/errors).

USAGE (examples):
  python3 hana_tuner.py --host hana.example.com --port 30015 --user MONITOR --password '***' --output text
  HDB_HOST=hana HDB_PORT=30013 HDB_USER=MONITOR HDB_PASSWORD=*** python3 hana_tuner.py --output json > report.json

PRIVILEGES:
- A monitoring user with roles like MONITORING or CATALOG READ is preferred.
- Some sections (ini parameters) may require additional privileges (e.g., INIFILE ADMIN or SYSTEM).
"""

import argparse
import datetime as dt
import json
import os
import sys
from typing import Any, Dict, List, Optional, Tuple

CLIENTS_TRIED = []
IMPORT_ERR = None

def _import_hana_client():
    """Try hdbcli first, then pyhdb."""
    global IMPORT_ERR
    try:
        import hdbcli.dbapi as hdbapi
        CLIENTS_TRIED.append("hdbcli")
        return ("hdbcli", hdbapi)
    except Exception as e:
        IMPORT_ERR = e
    try:
        import pyhdb
        CLIENTS_TRIED.append("pyhdb")
        return ("pyhdb", pyhdb)
    except Exception as e:
        IMPORT_ERR = e
    return (None, None)

CLIENT_NAME, HANAMOD = _import_hana_client()

def parse_args(argv=None):
    ap = argparse.ArgumentParser(description="Read-only SAP HANA health & configuration advisor.")
    ap.add_argument("--host", default=os.getenv("HDB_HOST", "127.0.0.1"))
    ap.add_argument("--port", type=int, default=int(os.getenv("HDB_PORT", "30015")))
    ap.add_argument("--user", default=os.getenv("HDB_USER", ""))
    ap.add_argument("--password", default=os.getenv("HDB_PASSWORD", ""))
    ap.add_argument("--encrypt", action="store_true", help="Enable TLS/SSL if supported by driver")
    ap.add_argument("--output", choices=["text","json"], default="text")
    ap.add_argument("--report-file", default=None)
    ap.add_argument("--json-indent", type=int, default=2)
    ap.add_argument("--warn-only", action="store_true", help="Exit 0 even if warnings exist")
    ap.add_argument("--expensive-window-min", type=int, default=60, help="Window (minutes) to look for expensive statements")
    ap.add_argument("--long-tx-minutes", type=int, default=30, help="Threshold for long-running transactions")
    return ap.parse_args(argv)

class DB:
    def __init__(self, args: argparse.Namespace):
        if CLIENT_NAME is None:
            raise SystemExit(
                "No SAP HANA client found. Install one of:\n"
                "  pip install hdbcli      # preferred official driver\n"
                "  pip install pyhdb       # fallback (limited)\n"
                f"Import error: {IMPORT_ERR!r} (clients tried: {CLIENTS_TRIED})"
            )
        self.args = args
        self.conn = None

    def connect(self):
        if CLIENT_NAME == "hdbcli":
            self.conn = HANAMOD.connect(address=self.args.host, port=self.args.port,
                                        user=self.args.user, password=self.args.password, encrypt=self.args.encrypt)
        else:
            # pyhdb
            self.conn = HANAMOD.connect(host=self.args.host, port=self.args.port,
                                        user=self.args.user, password=self.args.password, encrypt=self.args.encrypt)

    def q(self, sql: str, params: Tuple=()) -> List[Dict[str,Any]]:
        if self.conn is None:
            self.connect()
        cur = self.conn.cursor()
        cur.execute(sql, params)
        cols = [d[0] for d in cur.description] if cur.description else []
        rows = cur.fetchall()
        out = []
        for r in rows:
            out.append({cols[i]: r[i] for i in range(len(cols))})
        cur.close()
        return out

    def q1(self, sql: str, params: Tuple=()) -> Optional[Dict[str,Any]]:
        rows = self.q(sql, params)
        return rows[0] if rows else None

# ------------------------
# Helpers
# ------------------------
def fmt_bytes(n: Optional[int]) -> str:
    if n is None: return "N/A"
    units = ["B","KiB","MiB","GiB","TiB","PiB"]
    i = 0
    f = float(n)
    while f >= 1024 and i < len(units)-1:
        f /= 1024.0
        i += 1
    return f"{f:,.1f} {units[i]}"

def to_int(x) -> Optional[int]:
    try:
        return int(x)
    except Exception:
        return None

def to_float(x) -> Optional[float]:
    try:
        return float(x)
    except Exception:
        return None

# ------------------------
# Collection
# ------------------------
def collect_version(db: DB) -> Dict[str,Any]:
    try:
        row = db.q1("SELECT * FROM SYS.M_DATABASE")
        return row or {}
    except Exception:
        try:
            row = db.q1("SELECT * FROM SYS.M_SYSTEM_OVERVIEW")
            return row or {}
        except Exception:
            return {}

def collect_host_info(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.q("SELECT * FROM SYS.M_HOST_INFORMATION")
        out["host_info"] = rows
    except Exception:
        out["host_info"] = []
    try:
        rows = db.q("SELECT * FROM SYS.M_HOST_RESOURCE_UTILIZATION")
        out["host_util"] = rows
    except Exception:
        out["host_util"] = []
    return out

def collect_memory(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.q("SELECT HOST, SERVICE_NAME, COMPONENT, USED_MEMORY_SIZE, ALLOCATED_MEMORY_SIZE FROM SYS.M_SERVICE_MEMORY ORDER BY USED_MEMORY_SIZE DESC")
        out["service_memory"] = rows
    except Exception:
        out["service_memory"] = []
    try:
        rows = db.q("SELECT * FROM SYS.M_HOST_RESOURCE_UTILIZATION")
        out["host_resource"] = rows
    except Exception:
        out["host_resource"] = []
    return out

def collect_parameters(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        # Pull a subset of commonly tuned params
        rows = db.q("""
            SELECT FILE_NAME, SECTION, KEY, LAYER_NAME, VALUE, SYSTEM_VALUE, DATABASE_VALUE, EFFECTIVE_VALUE
            FROM SYS.M_INIFILE_CONTENTS
            WHERE (KEY IN ('max_concurrency','statement_memory_limit','global_allocation_limit','savepoint_interval_s','log_segment_size_mb')
                   OR (SECTION='persistence' AND KEY IN ('log_mode','savepoint_interval_s'))
                   OR (SECTION='sql' AND KEY IN ('statement_memory_limit'))
                   OR (SECTION='memorymanager' AND KEY IN ('global_allocation_limit')))
        """)
        out["ini_subset"] = rows
    except Exception:
        out["ini_subset"] = []
    return out

def collect_cs_tables(db: DB) -> List[Dict[str,Any]]:
    try:
        rows = db.q("""
            SELECT SCHEMA_NAME, TABLE_NAME, RECORD_COUNT, MEMORY_SIZE_IN_TOTAL, MAIN_MEMORY_SIZE_IN_TOTAL, DELTA_MEMORY_SIZE_IN_TOTAL,
                   LAST_MERGE_TIME, LOAD_STATUS
            FROM SYS.M_CS_TABLES
            ORDER BY DELTA_MEMORY_SIZE_IN_TOTAL DESC
        """)
        return rows[:50]
    except Exception:
        return []

def collect_expensive(db: DB, minutes: int) -> List[Dict[str,Any]]:
    try:
        rows = db.q(f"""
            SELECT TOP 50 START_TIME, USER_NAME, STATEMENT_STRING, DURATION_MICROSEC/1000 AS DURATION_MS, MEMORY_SIZE/1024/1024 AS MEM_MB
            FROM SYS.M_EXPENSIVE_STATEMENTS
            WHERE START_TIME >= ADD_SECONDS(CURRENT_TIMESTAMP, {-60*minutes})
            ORDER BY DURATION_MICROSEC DESC
        """)
        return rows
    except Exception:
        return []

def collect_tx(db: DB, minutes: int) -> List[Dict[str,Any]]:
    try:
        rows = db.q("""
            SELECT CONNECTION_ID, TRANSACTION_ID, UPDATE_TRANSACTION_ID, START_TIME, DURATION_MICROSEC/1000000 AS DURATION_S, WAITING,
                   LOCK_WAIT_COUNT, ISOLATION_LEVEL
            FROM SYS.M_TRANSACTIONS
            ORDER BY START_TIME
        """)
        # filter long-running
        cutoff_s = minutes * 60
        out = [r for r in rows if (to_float(r.get("DURATION_S") or 0) or 0) >= cutoff_s]
        return out[:50]
    except Exception:
        return []

def collect_io(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.q("""
            SELECT * FROM SYS.M_VOLUME_IO_TOTAL_STATISTICS
        """)
        out["volume_io"] = rows
    except Exception:
        out["volume_io"] = []
    try:
        rows = db.q("""
            SELECT * FROM SYS.M_DISK_USAGE
        """)
        out["disk_usage"] = rows
    except Exception:
        out["disk_usage"] = []
    return out

def collect_backups(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.q("""
            SELECT TOP 5 * FROM SYS.M_BACKUP_CATALOG ORDER BY SYS_START_TIME DESC
        """)
        out["recent_backups"] = rows
    except Exception:
        out["recent_backups"] = []
    return out

def collect_connections(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        row = db.q1("SELECT COUNT(*) AS CNT FROM SYS.M_CONNECTIONS WHERE CONNECTION_STATUS='CONNECT'")
        out["active_connections"] = row.get("CNT") if row else None
    except Exception:
        out["active_connections"] = None
    return out

# ------------------------
# Findings
# ------------------------
class Finding:
    def __init__(self, severity: str, title: str, detail: str, advice: str = "", tags: Optional[List[str]] = None):
        self.severity = severity  # ok | info | warn | crit
        self.title = title
        self.detail = detail
        self.advice = advice
        self.tags = tags or []

    def as_dict(self):
        return dict(severity=self.severity, title=self.title, detail=self.detail, advice=self.advice, tags=self.tags)

def evaluate(version: Dict[str,Any],
             host: Dict[str,Any],
             memory: Dict[str,Any],
             params: Dict[str,Any],
             cs_tables: List[Dict[str,Any]],
             expensive: List[Dict[str,Any]],
             tx_long: List[Dict[str,Any]],
             io: Dict[str,Any],
             conns: Dict[str,Any]) -> List[Finding]:

    F: List[Finding] = []

    # Memory sanity (service vs host)
    host_util = host.get("host_util", [])
    phys_mem = None
    if host_util:
        # M_HOST_RESOURCE_UTILIZATION has PHYSICAL_MEMORY_SIZE, USED_PHYSICAL_MEMORY_SIZE
        row0 = host_util[0]
        phys_mem = to_int(row0.get("PHYSICAL_MEMORY_SIZE") or row0.get("TOTAL_MEMORY") or 0) or None
        used_mem = to_int(row0.get("USED_PHYSICAL_MEMORY_SIZE") or 0) or 0
        if phys_mem:
            used_pct = 100.0 * used_mem / max(1, phys_mem)
            if used_pct > 90:
                F.append(Finding("warn", "High host memory usage", f"Host memory in use ~{used_pct:.1f}% ({fmt_bytes(used_mem)}/{fmt_bytes(phys_mem)}).",
                                 advice="Review HANA global_allocation_limit and service memory; check other co-located services.",
                                 tags=["memory"]))
            else:
                F.append(Finding("ok", "Host memory usage", f"~{used_pct:.1f}% in use.", tags=["memory"]))

    # Global allocation / statement memory
    ini = { (r.get("SECTION"), r.get("KEY")): r for r in params.get("ini_subset", []) }
    gal = ini.get(("memorymanager","global_allocation_limit"), {}) or ini.get((None,"global_allocation_limit"), {})
    if gal:
        eff = gal.get("EFFECTIVE_VALUE") or gal.get("VALUE")
        if eff:
            try:
                # Value is usually bytes
                val = int(eff)
                if phys_mem and val > phys_mem * 0.95:
                    F.append(Finding("info", "global_allocation_limit close to host memory",
                                     f"global_allocation_limit={fmt_bytes(val)}; host={fmt_bytes(phys_mem)}.",
                                     advice="Leave headroom for OS/other services; consider setting to ~80-90% of RAM.",
                                     tags=["memory"]))
                else:
                    F.append(Finding("ok", "global_allocation_limit", f"EFFECTIVE={fmt_bytes(val)}.", tags=["memory"]))
            except Exception:
                F.append(Finding("info", "global_allocation_limit", f"EFFECTIVE={eff}.", tags=["memory"]))

    sm = ini.get(("sql","statement_memory_limit"), {}) or ini.get((None,"statement_memory_limit"), {})
    if sm and sm.get("EFFECTIVE_VALUE"):
        try:
            sval = int(sm.get("EFFECTIVE_VALUE"))
            if sval == 0:
                F.append(Finding("info", "statement_memory_limit is unlimited", "statement_memory_limit=0.",
                                 advice="Consider a non-zero limit to contain runaway queries.", tags=["memory","queries"]))
            elif sval < (1<<30):  # < 1 GiB
                F.append(Finding("info", "Low statement_memory_limit", f"{fmt_bytes(sval)}.",
                                 advice="Increase for heavy analytic workloads; monitor for out-of-memory errors.", tags=["memory","queries"]))
            else:
                F.append(Finding("ok", "statement_memory_limit", f"{fmt_bytes(sval)}.", tags=["memory","queries"]))
        except Exception:
            F.append(Finding("info", "statement_memory_limit", f"EFFECTIVE={sm.get('EFFECTIVE_VALUE')}.", tags=["memory","queries"]))

    # Savepoint/log parameters
    sp = ini.get(("persistence","savepoint_interval_s"), {})
    if sp and sp.get("EFFECTIVE_VALUE"):
        try:
            sval = int(sp.get("EFFECTIVE_VALUE"))
            if sval > 600:
                F.append(Finding("info", "Long savepoint interval", f"savepoint_interval_s={sval}.",
                                 advice="Shorter intervals (e.g., 300s) reduce recovery time at cost of IO.", tags=["persistence"]))
            else:
                F.append(Finding("ok", "Savepoint interval", f"{sval} s.", tags=["persistence"]))
        except Exception:
            pass

    # Delta merge pressure
    if cs_tables:
        heavy_delta = [t for t in cs_tables if (to_int(t.get("DELTA_MEMORY_SIZE_IN_TOTAL") or 0) or 0) > (to_int(t.get("MAIN_MEMORY_SIZE_IN_TOTAL") or 0) or 1)]
        if heavy_delta:
            top = heavy_delta[0]
            F.append(Finding("info", "Large delta storage on some column tables",
                             f"Top table {top.get('SCHEMA_NAME')}.{top.get('TABLE_NAME')} has DELTA>{'MAIN'} (Δ≈{fmt_bytes(to_int(top.get('DELTA_MEMORY_SIZE_IN_TOTAL') or 0) or 0)}).",
                             advice="Increase delta merge frequency, batch sizes, or trigger manual merges during off-peak.",
                             tags=["columnstore","delta-merge"]))
        else:
            F.append(Finding("ok", "Delta storage", "No tables with delta >> main detected in top sample.", tags=["columnstore"]))

    # Expensive statements
    if expensive:
        worst = expensive[0]
        F.append(Finding("info", "Expensive statements present",
                         f"Top statement ~{int(worst.get('DURATION_MS') or 0):,} ms; mem≈{int(worst.get('MEM_MB') or 0):,} MB.",
                         advice="Use PlanViz/expensive statements tracing; consider workload management or query tuning.", tags=["queries"]))
    else:
        F.append(Finding("ok", "Expensive statements", "No entries in recent window.", tags=["queries"]))

    # Long transactions
    if tx_long:
        F.append(Finding("warn", "Long-running transactions",
                         f"{len(tx_long)} transaction(s) ≥ threshold.",
                         advice="Investigate for open cursors/idle sessions holding row versions; may block merges/cleanup.", tags=["transactions"]))
    else:
        F.append(Finding("ok", "Transaction age", "No long-running transactions found.", tags=["transactions"]))

    # IO overview
    vol = io.get("volume_io", [])
    if vol:
        # Look for volumes with high avg latency if columns exist
        row = vol[0]
        # We don't compute exact latency without per-op counts; present existence
        F.append(Finding("info", "Volume I/O stats available", "Review volume_io section for hot volumes.", tags=["io"]))

    # Connections
    ac = conns.get("active_connections")
    if ac is not None:
        F.append(Finding("ok", "Active connections", f"{ac} active connections.", tags=["connections"]))

    return F

# ------------------------
# Reporting
# ------------------------
def summarize(findings: List[Finding]) -> Dict[str,int]:
    s = {"ok":0, "info":0, "warn":0, "crit":0}
    for f in findings:
        s[f.severity] = s.get(f.severity, 0) + 1
    return s

def print_text_report(meta: Dict[str,Any], findings: List[Finding], sections: Dict[str,Any]):
    print("# SAP HANA Tuner Report")
    print(f"Generated: {dt.datetime.utcnow().isoformat()}Z")
    sysname = meta.get("SYSTEM_ID") or meta.get("NAME") or "?"
    print(f"System: {sysname}  Host: {meta.get('HOST') or ''}")
    print("")
    print("Summary:", summarize(findings))
    print("-"*80)
    for f in findings:
        badge = {"ok":"[OK ]","info":"[INFO]","warn":"[WARN]","crit":"[CRIT]"}[f.severity]
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
                try:
                    print("  - " + ", ".join(f"{k}={row[k]}" for k in row.keys()))
                except Exception:
                    print("  - row")
        else:
            print(json.dumps(data, indent=2, default=str))

def main(argv=None) -> int:
    args = parse_args(argv)
    try:
        db = DB(args)
        version = collect_version(db)
        host = collect_host_info(db)
        memory = collect_memory(db)
        params = collect_parameters(db)
        cs_tables = collect_cs_tables(db)
        expensive = collect_expensive(db, args.expensive_window_min)
        tx_long = collect_tx(db, args.long_tx_minutes)
        io = collect_io(db)
        backups = collect_backups(db)
        conns = collect_connections(db)

        findings = evaluate(version, host, memory, params, cs_tables, expensive, tx_long, io, conns)

        sections: Dict[str,Any] = {}
        sections["M_DATABASE / System overview"] = version
        sections["Host info"] = host
        sections["Memory (service/host)"] = memory
        sections["INI parameters (subset)"] = params.get("ini_subset", [])
        sections["Column-store tables (top by delta memory)"] = cs_tables
        sections["Expensive statements"] = expensive
        sections["Long-running transactions"] = tx_long
        sections["IO"] = io
        sections["Backups (recent)"] = backups.get("recent_backups", [])

        out = {
            "meta": version,
            "findings": [f.as_dict() for f in findings],
            "sections": sections,
            "client": CLIENT_NAME,
            "clients_tried": CLIENTS_TRIED,
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
                    sys_stdout = sys.stdout
                    sys.stdout = f
                    print_text_report(version, findings, sections)
                    sys.stdout = sys_stdout
            print_text_report(version, findings, sections)

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
