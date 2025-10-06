
#!/usr/bin/env python3
"""
oracle_tuner.py — A production-hardened Oracle Database 12c+ health & configuration advisor (read-only).

- Safe: read-only queries only (no ALTER/DDL/DML).
- Accurate: evidence-based suggestions from V$ views, DBA_* views (when permitted), and basic heuristics.
- Portable: works with Oracle 12c–23ai; uses python-oracledb (thin) or cx_Oracle as fallback.
- DevOps-friendly: JSON or text output; exit codes 0 (ok), 1 (warnings), 2 (critical/errors).

USAGE (examples):
  # EZCONNECT
  python3 oracle_tuner.py --user audit --password '***' --dsn "dbhost:1521/orclpdb1" --output text

  # Using TNS name from tnsnames.ora
  python3 oracle_tuner.py --user audit --password '***' --dsn "ORCLPDB1" --output json > report.json

PRIVILEGES
- For full checks: GRANT SELECT_CATALOG_ROLE TO audit;  (or) GRANT SELECT ANY DICTIONARY TO audit;
- Minimal mode works with limited access but some sections will be empty.
"""

import argparse
import datetime as dt
import json
import os
import sys
from typing import Any, Dict, List, Optional, Tuple

CLIENTS_TRIED = []
IMPORT_ERR = None

def _import_ora_client():
    """Try python-oracledb (aka oracledb) thin mode first, then cx_Oracle."""
    global IMPORT_ERR
    try:
        import oracledb
        CLIENTS_TRIED.append("python-oracledb")
        return ("python-oracledb", oracledb)
    except Exception as e:
        IMPORT_ERR = e
    try:
        import cx_Oracle
        CLIENTS_TRIED.append("cx_Oracle")
        return ("cx_Oracle", cx_Oracle)
    except Exception as e:
        IMPORT_ERR = e
    return (None, None)

CLIENT_NAME, ORA = _import_ora_client()

def parse_args(argv=None):
    ap = argparse.ArgumentParser(description="Read-only Oracle DB health & configuration advisor.")
    ap.add_argument("--user", default=os.getenv("ORA_USER"))
    ap.add_argument("--password", default=os.getenv("ORA_PASSWORD"))
    ap.add_argument("--dsn", required=True, help="EZCONNECT string host:port/service OR TNS alias")
    ap.add_argument("--mode", choices=["thin","thick","auto"], default="auto", help="python-oracledb mode")
    ap.add_argument("--wallet-dir", default=None, help="Wallet directory for TCPS (optional)")
    ap.add_argument("--config-dir", default=None, help="Oracle Client config dir (tnsnames/sqlnet) for thick mode")
    ap.add_argument("--output", choices=["text","json"], default="text")
    ap.add_argument("--report-file", default=None)
    ap.add_argument("--json-indent", type=int, default=2)
    ap.add_argument("--warn-only", action="store_true", help="Exit 0 even if warnings exist")
    return ap.parse_args(argv)

class DB:
    def __init__(self, args: argparse.Namespace):
        if CLIENT_NAME is None:
            raise SystemExit(
                "No Oracle client found. Install one of:\n"
                "  pip install oracledb          # preferred\n"
                "  pip install cx_Oracle         # fallback\n"
                f"Import error: {IMPORT_ERR!r} (clients tried: {CLIENTS_TRIED})"
            )
        self.args = args
        self.conn = None

    def connect(self):
        if CLIENT_NAME == "python-oracledb":
            if self.args.mode in ("thick","auto"):
                try:
                    ORA.init_oracle_client(config_dir=self.args.config_dir or None)
                except Exception:
                    # If init fails, continue in thin if mode==auto
                    if self.args.mode == "thick":
                        raise
            conn = ORA.connect(user=self.args.user, password=self.args.password, dsn=self.args.dsn, config_dir=self.args.config_dir or None, wallet_location=self.args.wallet-dir if hasattr(self.args,'wallet-dir') else None)
        else:
            if self.args.config_dir:
                ORA.init_oracle_client(config_dir=self.args.config_dir)
            conn = ORA.connect(self.args.user, self.args.password, self.args.dsn)
        self.conn = conn

    def q(self, sql: str, params: Tuple=()) -> List[Dict[str,Any]]:
        if self.conn is None:
            self.connect()
        cur = self.conn.cursor()
        cur.execute(sql, params)
        cols = [d[0].lower() for d in cur.description] if cur.description else []
        out = []
        for row in cur.fetchall():
            out.append({cols[i]: row[i] for i in range(len(cols))})
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
    units = ["B","KB","MB","GB","TB","PB"]
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

def ratio(a: Optional[float], b: Optional[float]) -> Optional[float]:
    try:
        if a is None or b in (None, 0): return None
        return a / b
    except Exception:
        return None

# ------------------------
# Collection
# ------------------------
def collect_version(db: DB) -> Dict[str,Any]:
    try:
        rows = db.q("select banner_full from v$version")
        banner = rows[0]["banner_full"] if rows else ""
    except Exception:
        banner = ""
    try:
        row = db.q1("select name, db_unique_name, open_mode, database_role from v$database")
    except Exception:
        row = {}
    return {"banner": banner, **(row or {})}

def collect_resource_limits(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.q("""
            select resource_name, current_utilization, max_utilization, limit_value
            from v$resource_limit
            where resource_name in ('processes','sessions')
        """)
        out["limits"] = rows
    except Exception:
        out["limits"] = []
    try:
        sess = db.q1("select count(*) as total, sum(case when status='ACTIVE' then 1 else 0 end) as active from v$session")
        out["sessions"] = sess or {}
    except Exception:
        out["sessions"] = {}
    return out

def collect_memory(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.q("select * from v$sga")
        out["sga"] = rows
    except Exception:
        out["sga"] = []
    try:
        rows = db.q("select name, value from v$pgastat")
        out["pga"] = rows
    except Exception:
        out["pga"] = []
    try:
        params = db.q("""
            select name, value, isdefault, issys_modifiable
            from v$parameter
            where name in ('memory_target','sga_target','pga_aggregate_target','optimizer_adaptive_features',
                           'optimizer_adaptive_plans','optimizer_adaptive_statistics','db_cache_size','shared_pool_size')
        """)
        out["params"] = params
    except Exception:
        out["params"] = []
    return out

def collect_sysstat(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.q("""
            select name, value
            from v$sysstat
            where name in ('session logical reads','db block gets','consistent gets','physical reads')
        """)
        out["sysstat"] = rows
    except Exception:
        out["sysstat"] = []
    try:
        row = db.q1("""
            select wait_class, sum(time_waited) as time_waited
            from v$system_wait_class
            where wait_class <> 'Idle'
            group by wait_class
            order by sum(time_waited) desc
        """)
        out["top_wait_class"] = row or {}
    except Exception:
        out["top_wait_class"] = {}
    return out

def collect_redo(db: DB) -> Dict[str,Any]:
    out = {}
    try:
        rows = db.q("""
            select count(*) as switches_last_24h
            from v$log_history
            where first_time >= sysdate - 1
        """)
        out["log_switch_24h"] = rows[0]["switches_last_24h"] if rows else None
    except Exception:
        out["log_switch_24h"] = None
    return out

def collect_tablespace(db: DB) -> Dict[str,Any]:
    out = {"tbs": [], "temp": [], "undo": []}
    # Regular tablespaces free/used
    try:
        rows = db.q("""
            select
              df.tablespace_name,
              round(sum(df.bytes)) as bytes_total,
              round(sum(df.bytes) - nvl(sum(fs.bytes),0)) as bytes_used,
              round(nvl(sum(fs.bytes),0)) as bytes_free
            from dba_data_files df
            left join (
              select tablespace_name, sum(bytes) bytes
              from dba_free_space
              group by tablespace_name
            ) fs using (tablespace_name)
            group by df.tablespace_name
            order by 1
        """)
        out["tbs"] = rows
    except Exception:
        pass
    # Temp usage (requires DBA views)
    try:
        rows = db.q("""
            select ts.name as tablespace_name,
                   sum(tt.used_blocks*tt.block_size) as bytes_used
            from v$sort_segment tt
            join v$tablespace ts on ts.ts# = tt.tablespace_id
            group by ts.name
        """)
        out["temp"] = rows
    except Exception:
        pass
    # Undo tablespace
    try:
        rows = db.q("""
            select tablespace_name, status from dba_undo_extents
        """)
        out["undo"] = rows[:1] if rows else []
    except Exception:
        pass
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

def evaluate(meta: Dict[str,Any],
             limits: Dict[str,Any],
             mem: Dict[str,Any],
             sysstat: Dict[str,Any],
             redo: Dict[str,Any],
             tbs: Dict[str,Any]) -> List[Finding]:
    F: List[Finding] = []

    # Connections/process headroom
    lims = {r["resource_name"].lower(): r for r in limits.get("limits", [])}
    for res in ("sessions","processes"):
        r = lims.get(res)
        if r:
            curr = to_int(r.get("current_utilization"))
            maxu = to_int(r.get("max_utilization"))
            limit_val = r.get("limit_value")
            d = f"{res}: current={curr}, max={maxu}, limit={limit_val}."
            if limit_val not in ("UNLIMITED", None):
                try:
                    lim = int(limit_val)
                    if curr is not None and curr/lim >= 0.85:
                        F.append(Finding("warn", f"High {res} utilization", d,
                                         advice=f"Increase {res} (sizing) or add pooling; investigate long-idle sessions.",
                                         tags=["connections"]))
                    else:
                        F.append(Finding("ok", f"{res.capitalize()} headroom", d, tags=["connections"]))
                except Exception:
                    F.append(Finding("info", f"{res.capitalize()} limits", d, tags=["connections"]))

    # Memory: SGA/PGA targets and AMM/ASMM
    params = {p["name"]: p for p in mem.get("params", []) if "name" in p}
    sga_t = to_int(params.get("sga_target",{}).get("value") or 0)
    pga_t = to_int(params.get("pga_aggregate_target",{}).get("value") or 0)
    mem_t = to_int(params.get("memory_target",{}).get("value") or 0)
    if mem_t and mem_t > 0:
        F.append(Finding("ok", "Automatic Memory Management", f"memory_target={fmt_bytes(mem_t)}.", tags=["memory"]))
    else:
        if sga_t and pga_t and sga_t < 1*1024**3:
            F.append(Finding("info", "Small SGA target", f"sga_target={fmt_bytes(sga_t)}.",
                             advice="Consider larger SGA for cache-heavy workloads; ensure hugepages configured on Linux.",
                             tags=["memory","sga"]))
        if pga_t and pga_t < 512*1024**2:
            F.append(Finding("info", "Small PGA target", f"pga_aggregate_target={fmt_bytes(pga_t)}.",
                             advice="Increase pga_aggregate_target for sorts/hash joins if you see excessive TEMP usage.",
                             tags=["memory","pga"]))

    # Buffer cache hit ratio (heuristic)
    stats = {r["name"]: to_int(r["value"]) for r in sysstat.get("sysstat", []) if "name" in r}
    cg = stats.get("consistent gets", 0) or 0
    dbg = stats.get("db block gets", 0) or 0
    pr = stats.get("physical reads", 0) or 0
    denom = cg + dbg
    if denom > 0:
        bchr = 1.0 - (pr / denom)
        detail = f"Buffer cache hit ratio ≈ {bchr*100:.1f}% (phys reads={pr:,}, logical={denom:,})."
        if bchr < 0.90:
            F.append(Finding("info", "Low-ish buffer cache hit ratio", detail,
                             advice="Increase DB cache (db_cache_size/SGA) and review I/O; validate with AWR.",
                             tags=["cache","io"]))
        else:
            F.append(Finding("ok", "Healthy buffer cache hit ratio", detail, tags=["cache"]))

    # Wait profile
    tw = sysstat.get("top_wait_class", {})
    if tw:
        wc = tw.get("wait_class")
        t = tw.get("time_waited")
        F.append(Finding("info", "Top wait class", f"{wc}: time_waited={t}.",
                         advice="Use AWR/ASH to drill into top events; address IO/CPU/Concurrency as indicated.",
                         tags=["waits"]))

    # Redo log switching
    sw = to_int(redo.get("log_switch_24h"))
    if sw is not None:
        if sw >= 144:  # every 10 minutes on average
            F.append(Finding("info", "Frequent redo log switches",
                             f"{sw} switches in last 24h.",
                             advice="Increase redo log size to reduce checkpointing; aim for 15–20 min per switch.",
                             tags=["redo","checkpoint"]))
        else:
            F.append(Finding("ok", "Redo log switch cadence", f"{sw} in last 24h.", tags=["redo"]))

    # Tablespace free space
    for r in tbs.get("tbs", []):
        total = to_int(r.get("bytes_total") or 0) or 0
        freeb = to_int(r.get("bytes_free") or 0) or 0
        name = r.get("tablespace_name")
        pct_free = (freeb / total) if total else 0
        if total and pct_free < 0.15:
            F.append(Finding("warn", f"Low free space in {name}", f"Free {pct_free*100:.1f}% ({fmt_bytes(freeb)}/{fmt_bytes(total)}).",
                             advice="Add datafiles/resize or purge/archival; watch autoextend.",
                             tags=["space","tablespace"]))
        else:
            F.append(Finding("ok", f"{name} space", f"Free {pct_free*100:.1f}%.", tags=["space","tablespace"]))

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
    print("# Oracle DB Tuner Report")
    print(f"Generated: {dt.datetime.utcnow().isoformat()}Z")
    print(f"Server: {meta.get('banner','')}  DB={meta.get('name','?')} Role={meta.get('database_role','?')} OpenMode={meta.get('open_mode','?')}")
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
                print("  - " + ", ".join(f"{k}={row[k]}" for k in row.keys()))
        else:
            print(json.dumps(data, indent=2, default=str))

def main(argv=None) -> int:
    args = parse_args(argv)
    try:
        db = DB(args)
        meta = collect_version(db)
        limits = collect_resource_limits(db)
        mem = collect_memory(db)
        sysstat = collect_sysstat(db)
        redo = collect_redo(db)
        tbs = collect_tablespace(db)

        findings = evaluate(meta, limits, mem, sysstat, redo, tbs)

        sections: Dict[str,Any] = {}
        sections["Resource limits"] = limits
        sections["Memory (SGA/PGA/params)"] = mem
        sections["System stats"] = sysstat
        sections["Redo"] = redo
        sections["Tablespaces"] = tbs

        out = {
            "meta": meta,
            "findings": [f.as_dict() for f in findings],
            "sections": sections,
            "client": CLIENT_NAME,
            "clients_tried": CLIENTS_TRIED
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
                    print_text_report(meta, findings, sections)
                    sys.stdout = sys_stdout
            print_text_report(meta, findings, sections)

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
