
#!/usr/bin/env python3
"""
mssql-tuner.py — A production-hardened Microsoft SQL Server (2012+ / Azure SQL) health & configuration advisor.

- Safe: read-only DMV queries only (no DBCC that changes anything, no ALTERs).
- Accurate: evidence-based checks from sys.configurations, DMVs (waits, IO, memory), tempdb layout, and perf counters.
- Portable: works with SQL Server 2012+ and Azure SQL Database. Uses pyodbc (preferred) or pymssql as a fallback.
- DevOps-friendly: JSON or text output; exit codes 0 (ok), 1 (warnings), 2 (critical/errors).

USAGE (examples):
  # Windows Auth (Trusted Connection) with ODBC driver
  python3 mssql-tuner.py --server "tcp:sql01,1433" --driver "ODBC Driver 18 for SQL Server" --trusted

  # SQL Auth
  python3 mssql-tuner.py --server "tcp:sql01,1433" --user sa --password '***' --database master --output json > report.json

  # Azure SQL with encryption
  python3 mssql-tuner.py --server "tcp:yourserver.database.windows.net,1433" --database yourdb --user user --password '***' --encrypt --trustservercert
"""

import argparse
import datetime as dt
import json
import os
import sys
from typing import Any, Dict, List, Optional, Tuple

CLIENTS_TRIED = []
IMPORT_ERR = None

def _import_sql_client():
    """Try pyodbc then pymssql."""
    global IMPORT_ERR
    try:
        import pyodbc
        CLIENTS_TRIED.append("pyodbc")
        return ("pyodbc", pyodbc)
    except Exception as e:
        IMPORT_ERR = e
    try:
        import pymssql
        CLIENTS_TRIED.append("pymssql")
        return ("pymssql", pymssql)
    except Exception as e:
        IMPORT_ERR = e
    return (None, None)

CLIENT_NAME, SQLMOD = _import_sql_client()

def parse_args(argv=None):
    ap = argparse.ArgumentParser(description="Read-only SQL Server health & configuration advisor.")
    ap.add_argument("--server", required=True, help="Server endpoint, e.g., tcp:host,1433")
    ap.add_argument("--database", default="master", help="Database to connect to (default: master)")
    ap.add_argument("--user", default=None, help="SQL login user")
    ap.add_argument("--password", default=os.getenv("MSSQL_PASSWORD"), help="SQL login password (or MSSQL_PASSWORD env)")
    ap.add_argument("--trusted", action="store_true", help="Use Windows Auth / Trusted_Connection (pyodbc only)")
    ap.add_argument("--driver", default="ODBC Driver 18 for SQL Server", help="ODBC Driver name (pyodbc)")
    ap.add_argument("--encrypt", action="store_true", help="Encrypt=True in connection string")
    ap.add_argument("--trustservercert", action="store_true", help="TrustServerCertificate=Yes (pyodbc)")
    ap.add_argument("--output", choices=["text","json"], default="text")
    ap.add_argument("--report-file", default=None)
    ap.add_argument("--json-indent", type=int, default=2)
    ap.add_argument("--warn-only", action="store_true", help="Exit 0 even if warnings exist")
    ap.add_argument("--deep", action="store_true", help="Run deeper checks (may be slower)")
    return ap.parse_args(argv)

class DB:
    def __init__(self, args: argparse.Namespace):
        if CLIENT_NAME is None:
            raise SystemExit(
                "No SQL Server client found. Install one of:\n"
                "  pip install pyodbc        # preferred\n"
                "  pip install pymssql       # fallback\n"
                f"Import error: {IMPORT_ERR!r} (clients tried: {CLIENTS_TRIED})"
            )
        self.args = args
        self.conn = None

    def connect(self):
        if CLIENT_NAME == "pyodbc":
            # Build ODBC connection string
            parts = [
                f"DRIVER={{{self.args.driver}}}",
                f"SERVER={self.args.server}",
                f"DATABASE={self.args.database}",
            ]
            if self.args.trusted and os.name == "nt":
                parts.append("Trusted_Connection=Yes")
            else:
                parts.append(f"UID={self.args.user or ''}")
                parts.append(f"PWD={self.args.password or ''}")
            if self.args.encrypt:
                parts.append("Encrypt=Yes")
            if self.args.trustservercert:
                parts.append("TrustServerCertificate=Yes")
            # For ODBC 18+, default is stricter encryption; allow TDS fallback
            parts.append("MARS_Connection=Yes")
            cs = ";".join(parts)
            self.conn = SQLMOD.connect(cs, timeout=5)
        else:
            # pymssql
            server = self.args.server.replace("tcp:","")
            host, port = (server.split(",")+["1433"])[:2]
            self.conn = SQLMOD.connect(server=host, port=int(port), user=self.args.user, password=self.args.password, database=self.args.database, timeout=5)
        # row as dict-ish
        try:
            self.conn.autocommit = True
        except Exception:
            pass

    def q(self, sql: str, params: Tuple=()) -> List[Dict[str,Any]]:
        if self.conn is None:
            self.connect()
        cur = self.conn.cursor()
        cur.execute(sql, params)
        # pyodbc: cursor.description exists; fetchall returns tuples
        cols = [d[0] for d in cur.description] if cur.description else []
        rows = cur.fetchall()
        out = []
        for r in rows:
            # Access by index
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

def to_float(x) -> Optional[float]:
    try:
        return float(x)
    except Exception:
        return None

# ------------------------
# Collection
# ------------------------
def collect_version(db: DB) -> Dict[str,Any]:
    row = db.q1("SELECT @@VERSION AS version;") or {}
    sp = db.q1("""
        SELECT
          SERVERPROPERTY('ProductVersion') AS product_version,
          SERVERPROPERTY('ProductLevel')   AS product_level,
          SERVERPROPERTY('Edition')        AS edition,
          SERVERPROPERTY('EngineEdition')  AS engine_edition,
          SERVERPROPERTY('IsHadrEnabled')  AS hadr_enabled
    """) or {}
    return {"version": row.get("version",""), **sp}

def collect_sysinfo(db: DB) -> Dict[str,Any]:
    row = db.q1("""
        SELECT cpu_count, hyperthread_ratio, physical_memory_kb, sqlserver_start_time
        FROM sys.dm_os_sys_info
    """) or {}
    return row

def collect_config(db: DB) -> Dict[str,Any]:
    rows = db.q("""
        SELECT name, value, value_in_use, is_dynamic, is_advanced
        FROM sys.configurations
    """)
    return {r["name"]: r for r in rows}

def collect_perf_counters(db: DB) -> Dict[str,Any]:
    # Buffer cache hit ratio / Page life expectancy (PLE) if available
    rows = db.q("""
        SELECT RTRIM(counter_name) AS counter_name, RTRIM(instance_name) AS instance_name, cntr_value
        FROM sys.dm_os_performance_counters
        WHERE object_name LIKE '%Buffer Manager%'
          AND counter_name IN ('Buffer cache hit ratio', 'Page life expectancy')
    """)
    return {"buffer_mgr": rows}

def collect_waits(db: DB) -> List[Dict[str,Any]]:
    # Exclude benign waits per common guidance
    rows = db.q("""
        SELECT TOP 20 wait_type, waiting_tasks_count, wait_time_ms, signal_wait_time_ms
        FROM sys.dm_os_wait_stats
        WHERE wait_type NOT IN (
          'SLEEP_TASK','SLEEP_SYSTEMTASK','WAITFOR','BROKER_TASK_STOP','BROKER_TO_FLUSH',
          'SQLTRACE_BUFFER_FLUSH','CLR_AUTO_EVENT','CLR_MANUAL_EVENT','LAZYWRITER_SLEEP','BROKER_EVENTHANDLER',
          'XE_TIMER_EVENT','XE_DISPATCHER_WAIT','FT_IFTS_SCHEDULER_IDLE_WAIT','LOGMGR_QUEUE','REQUEST_FOR_DEADLOCK_SEARCH',
          'CHECKPOINT_QUEUE','BROKER_TRANSMITTER','DBMIRROR_EVENTS_QUEUE','FT_IFTSHC_MUTEX','RESOURCE_QUEUE',
          'XE_DISPATCHER_JOIN','BROKER_RECEIVE_WAITFOR','ONDEMAND_TASK_QUEUE','DBMIRRORING_CMD','HADR_FILESTREAM_IOMGR_IOCOMPLETION',
          'DIRTY_PAGE_POLL','SP_SERVER_DIAGNOSTICS_SLEEP','HADR_TIMER_TASK','HADR_WORK_QUEUE','XE_LIVE_TARGET_TVF'
        )
        ORDER BY wait_time_ms DESC
    """)
    return rows

def collect_io(db: DB) -> List[Dict[str,Any]]:
    rows = db.q("""
        SELECT
          DB_NAME(vfs.database_id) AS db_name,
          mf.type_desc,
          mf.physical_name,
          vfs.num_of_reads, vfs.io_stall_read_ms,
          vfs.num_of_writes, vfs.io_stall_write_ms,
          vfs.size_on_disk_bytes
        FROM sys.dm_io_virtual_file_stats(NULL, NULL) AS vfs
        JOIN sys.master_files AS mf
          ON mf.database_id = vfs.database_id AND mf.file_id = vfs.file_id
        ORDER BY (vfs.io_stall_read_ms + vfs.io_stall_write_ms) DESC
    """)
    return rows

def collect_tempdb_layout(db: DB) -> List[Dict[str,Any]]:
    rows = db.q("""
        SELECT name, type_desc, size*8*1024 AS size_bytes, growth, is_percent_growth, physical_name
        FROM tempdb.sys.database_files
        ORDER BY type_desc, name
    """)
    return rows

def collect_db_log_info(db: DB) -> List[Dict[str,Any]]:
    # VLF count (2016 SP2+/Azure) via sys.dm_db_log_info
    try:
        rows = db.q("""
            DECLARE @t TABLE (db_id int, db_name sysname, vlf_count int);
            INSERT INTO @t(db_id, db_name, vlf_count)
            SELECT d.database_id, d.name, COUNT(*) AS vlf_count
            FROM sys.databases AS d
            CROSS APPLY sys.dm_db_log_info(d.database_id)
            GROUP BY d.database_id, d.name;

            SELECT * FROM @t ORDER BY vlf_count DESC;
        """)
        return rows
    except Exception:
        return []

def collect_db_space(db: DB) -> List[Dict[str,Any]]:
    rows = db.q("""
        SELECT
          d.name AS db_name,
          SUM(CASE WHEN mf.type_desc='ROWS' THEN mf.size END)*8*1024 AS data_size_bytes,
          SUM(CASE WHEN mf.type_desc='LOG'  THEN mf.size END)*8*1024 AS log_size_bytes
        FROM sys.databases d
        JOIN sys.master_files mf ON mf.database_id = d.database_id
        GROUP BY d.name
        ORDER BY data_size_bytes DESC
    """)
    return rows

def collect_activity(db: DB) -> Dict[str,Any]:
    row = db.q1("""
        SELECT COUNT(*) AS sessions,
               SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) AS running
        FROM sys.dm_exec_sessions
        WHERE is_user_process = 1
    """) or {}
    req = db.q1("""
        SELECT COUNT(*) AS requests FROM sys.dm_exec_requests WHERE session_id <> @@SPID
    """) or {}
    return {"sessions": row.get("sessions"), "running": row.get("running"), "requests": req.get("requests")}

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
             sysinfo: Dict[str,Any],
             config: Dict[str,Any],
             perf: Dict[str,Any],
             waits: List[Dict[str,Any]],
             io: List[Dict[str,Any]],
             tempdb: List[Dict[str,Any]],
             vlf: List[Dict[str,Any]],
             activity: Dict[str,Any]) -> List[Finding]:

    F: List[Finding] = []

    # Connection headroom (sessions/requests as a simple proxy)
    sess = to_int(activity.get("sessions") or 0) or 0
    reqs = to_int(activity.get("requests") or 0) or 0
    F.append(Finding("ok", "Current activity", f"user sessions={sess}, active requests={reqs}.", tags=["connections"]))

    # Memory settings: max server memory (MB)
    maxmem = config.get("max server memory (mb)", {}).get("value_in_use")
    minmem = config.get("min server memory (mb)", {}).get("value_in_use")
    total_kb = to_int(sysinfo.get("physical_memory_kb") or 0) or 0
    total_mb = total_kb // 1024 if total_kb else None
    if total_mb and maxmem:
        # If maxmem is near or above total memory, warn. Leave 4-6 GB or ~20% headroom
        headroom_mb = total_mb - int(maxmem)
        if headroom_mb < max(6144, int(total_mb*0.2)):
            F.append(Finding("warn", "Low OS memory headroom",
                             f"max server memory={int(maxmem):,} MB, host ~{total_mb:,} MB (headroom ~{headroom_mb:,} MB).",
                             advice="Lower 'max server memory (MB)' to leave 4–6GB or ~20% for OS/other processes.",
                             tags=["memory"]))
        else:
            F.append(Finding("ok", "Max server memory sane",
                             f"max server memory={int(maxmem):,} MB, host ~{total_mb:,} MB.", tags=["memory"]))
    else:
        F.append(Finding("info", "Memory sizing unknown",
                         "Could not read host memory or max server memory.", tags=["memory"]))

    # MAXDOP and Cost Threshold
    maxdop = config.get("max degree of parallelism", {}).get("value_in_use")
    costthr = config.get("cost threshold for parallelism", {}).get("value_in_use")
    cpu_count = to_int(sysinfo.get("cpu_count") or 0) or 0
    if maxdop is not None:
        md = int(maxdop)
        if md == 0:
            F.append(Finding("info", "MAXDOP=0 (unlimited)", "MAXDOP is unlimited.",
                             advice="Set MAXDOP per Microsoft guidance (often <= 8, align to cores/NUMA and workload).",
                             tags=["parallelism"]))
        elif md > 8:
            F.append(Finding("info", "High MAXDOP", f"MAXDOP={md}.",
                             advice="Consider MAXDOP between 2–8 for OLTP; validate for DW/analytics.", tags=["parallelism"]))
        else:
            F.append(Finding("ok", "MAXDOP reasonable", f"MAXDOP={md}.", tags=["parallelism"]))
    if costthr is not None:
        ct = int(costthr)
        if ct <= 5:
            F.append(Finding("info", "Default cost threshold for parallelism",
                             f"cost threshold for parallelism={ct}.",
                             advice="Raise to 25–50 to avoid parallelism for trivial queries.", tags=["parallelism"]))
        else:
            F.append(Finding("ok", "Cost threshold reasonable", f"cost threshold={ct}.", tags=["parallelism"]))

    # Optimize for ad hoc workloads
    ad_hoc = config.get("optimize for ad hoc workloads", {}).get("value_in_use")
    if ad_hoc is not None and int(ad_hoc) == 0:
        F.append(Finding("info", "Optimize for ad hoc workloads OFF",
                         "optimize for ad hoc workloads=0.",
                         advice="Enable to reduce plan cache bloat from single-use queries.", tags=["plan cache"]))
    elif ad_hoc is not None:
        F.append(Finding("ok", "Optimize for ad hoc workloads ON", "optimize for ad hoc workloads=1.", tags=["plan cache"]))

    # TempDB layout
    temp_data_files = [r for r in tempdb if r["type_desc"] == "ROWS"]
    nfiles = len(temp_data_files)
    if cpu_count and nfiles < min(cpu_count, 8):
        F.append(Finding("info", "TempDB may be under-provisioned",
                         f"{nfiles} data file(s) for tempdb; cpu_count={cpu_count}.",
                         advice="Match number of tempdb data files to CPU count up to 8; keep equal size/autogrowth.",
                         tags=["tempdb"]))
    else:
        F.append(Finding("ok", "TempDB files", f"{nfiles} data file(s).", tags=["tempdb"]))
    # Check uniform growth settings
    if temp_data_files:
        growths = {(r["growth"], r["is_percent_growth"]) for r in temp_data_files}
        if len(growths) > 1:
            F.append(Finding("info", "TempDB growth not uniform",
                             "TempDB files have mixed growth settings.",
                             advice="Align initial size and growth for all tempdb data files.", tags=["tempdb"]))

    # Waits quick profile
    if waits:
        top = waits[0]
        wt = top.get("wait_type")
        F.append(Finding("info", "Top wait", f"{wt}. See waits section for more.", tags=["waits"]))
        # Heuristics
        wnames = [w["wait_type"] for w in waits[:5]]
        if any(w.startswith("PAGEIOLATCH") for w in wnames):
            F.append(Finding("info", "IO-bound waits detected",
                             "PAGEIOLATCH_* among top waits.",
                             advice="Check storage latency (see IO section) and buffer pool sizing.", tags=["io","waits"]))
        if "WRITELOG" in wnames:
            F.append(Finding("info", "Log write pressure",
                             "WRITELOG among top waits.",
                             advice="Ensure fast log storage, pre-size logs to reduce VLFs, monitor instant file init.", tags=["log","waits"]))
        if "CXPACKET" in wnames or "CXCONSUMER" in wnames:
            F.append(Finding("info", "Parallelism waits",
                             "CXPACKET/CXCONSUMER among top waits.",
                             advice="Tune MAXDOP and cost threshold; review skewed parallel plans.", tags=["parallelism","waits"]))

    # IO stalls
    if io:
        # Compute simple average stalls per operation for worst file
        worst = io[0]
        rd = to_int(worst.get("num_of_reads") or 0) or 0
        wr = to_int(worst.get("num_of_writes") or 0) or 0
        rstall = (to_int(worst.get("io_stall_read_ms") or 0) or 0) / max(1, rd)
        wstall = (to_int(worst.get("io_stall_write_ms") or 0) or 0) / max(1, wr)
        F.append(Finding("info", "Worst-file IO latency (avg)",
                         f"{worst.get('db_name')}:{worst.get('physical_name')} — reads ~{rstall:.1f} ms/IO, writes ~{wstall:.1f} ms/IO.",
                         advice="Sustained >20ms is concerning; investigate storage tier, queueing, file placement.", tags=["io"]))

    # VLF counts
    if vlf:
        worst = max(vlf, key=lambda r: to_int(r.get("vlf_count") or 0) or 0)
        c = to_int(worst.get("vlf_count") or 0) or 0
        if c > 1000:
            F.append(Finding("info", "High VLF count",
                             f"{worst.get('db_name')} has ~{c} VLFs.",
                             advice="Shrink and re-grow log with proper size to reduce VLF fragmentation.", tags=["log"]))
        else:
            F.append(Finding("ok", "VLF counts reasonable", f"Max observed VLFs ~{c}.", tags=["log"]))

    # Buffer cache metrics
    buf = perf.get("buffer_mgr", [])
    if buf:
        # Buffer cache hit ratio comes in as two counters: base & ratio? On SQL Server, cntr_value is ratio * base. Simpler: display raw.
        ple = next((r for r in buf if r["counter_name"] == "Page life expectancy"), None)
        bchr = next((r for r in buf if r["counter_name"] == "Buffer cache hit ratio"), None)
        if bchr:
            F.append(Finding("info", "Buffer cache hit ratio (raw counter)", f"cntr_value={bchr.get('cntr_value')}.",
                             advice="Interpret with care; sustained low values may indicate memory pressure.", tags=["memory","cache"]))
        if ple:
            F.append(Finding("info", "Page life expectancy (PLE)", f"cntr_value={ple.get('cntr_value')} seconds.",
                             advice="Very low PLE can indicate memory pressure or churn; trend over time matters.", tags=["memory","cache"]))

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
    print("# SQL Server Tuner Report")
    print(f"Generated: {dt.datetime.utcnow().isoformat()}Z")
    print(f"Client: {CLIENT_NAME}")
    print(f"Server: {meta.get('product_version','?')} {meta.get('product_level','')} — {meta.get('edition','')}")
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
        sysinfo = collect_sysinfo(db)
        config = collect_config(db)
        perf = collect_perf_counters(db)
        waits = collect_waits(db)
        io = collect_io(db)
        tempdb = collect_tempdb_layout(db)
        vlf = collect_db_log_info(db)
        dbspace = collect_db_space(db)
        activity = collect_activity(db)

        findings = evaluate(meta, sysinfo, config, perf, waits, io, tempdb, vlf, activity)

        sections: Dict[str,Any] = {}
        sections["Version"] = meta
        sections["System info"] = sysinfo
        sections["Key configuration (subset)"] = [
            {"name":"max server memory (MB)", "value_in_use": config.get("max server memory (mb)",{}).get("value_in_use")},
            {"name":"min server memory (MB)", "value_in_use": config.get("min server memory (mb)",{}).get("value_in_use")},
            {"name":"max degree of parallelism", "value_in_use": config.get("max degree of parallelism",{}).get("value_in_use")},
            {"name":"cost threshold for parallelism", "value_in_use": config.get("cost threshold for parallelism",{}).get("value_in_use")},
            {"name":"optimize for ad hoc workloads", "value_in_use": config.get("optimize for ad hoc workloads",{}).get("value_in_use")},
        ]
        sections["Activity"] = activity
        sections["Top waits"] = waits
        sections["IO by file (stall summary)"] = io[:25]
        sections["TempDB layout"] = tempdb
        sections["Database sizes"] = dbspace
        if vlf:
            sections["VLF counts"] = vlf

        out = {
            "meta": meta,
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
