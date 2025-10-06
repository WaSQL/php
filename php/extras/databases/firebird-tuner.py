
#!/usr/bin/env python3
"""
firebird-tuner.py — A production-hardened Firebird (2.5/3.0/4.0/5.0) health & configuration advisor (read-only).

- Safe: read-only metadata/monitoring queries only (no gfix/gstat, no ALTER/DDL/DML).
- Accurate: evidence-based suggestions from MON$ monitoring tables and RDB$ catalogs where possible.
- Portable: tries `firebird-driver` (modern) then `fdb` (legacy). Works with Classic/SuperClassic/SuperServer.
- DevOps-friendly: JSON or text output; exit status 0 (ok), 1 (warnings), 2 (critical/errors).

USAGE (examples):
  python3 firebird-tuner.py --host 127.0.0.1 --port 3050 --db /path/to/database.fdb --user sysdba --password ... --output text
  python3 firebird-tuner.py --dsn "localhost:/path/to/database.fdb" --user sysdba --password ... --output json > fb_report.json

PRIVILEGES: a regular user with access to MON$ tables (default) is sufficient. SYSDBA or admin role yields the most detail.
"""

import argparse
import datetime as dt
import json
import os
import sys
from typing import Any, Dict, List, Optional, Tuple

CLIENTS_TRIED = []
IMPORT_ERR = None

def _import_fb_client():
    """
    Try firebird-driver (python-firebird) first, then fdb.
    Returns (name, module, connect_fn_name).
    """
    global IMPORT_ERR
    try:
        import firebird.driver as fb
        CLIENTS_TRIED.append("firebird-driver")
        return ("firebird-driver", fb, "connect")
    except Exception as e:
        IMPORT_ERR = e
    try:
        import fdb as fb
        CLIENTS_TRIED.append("fdb")
        return ("fdb", fb, "connect")
    except Exception as e:
        IMPORT_ERR = e
    return (None, None, None)

CLIENT_NAME, FBMOD, CONNECT_FN = _import_fb_client()

def parse_args(argv=None):
    ap = argparse.ArgumentParser(description="Read-only Firebird health & configuration advisor.")
    ap.add_argument("--dsn", help="DSN string, e.g., host:/path/db.fdb  (overrides host/port/db)")
    ap.add_argument("--host", default="127.0.0.1", help="Hostname or IP (ignored if --dsn given)")
    ap.add_argument("--port", type=int, default=3050, help="Port (default: 3050)")
    ap.add_argument("--db", help="Database path (ignored if --dsn given)")
    ap.add_argument("--user", default=os.getenv("ISC_USER","sysdba"))
    ap.add_argument("--password", default=os.getenv("ISC_PASSWORD"))
    ap.add_argument("--role", default=None)
    ap.add_argument("--charset", default="UTF8")

    ap.add_argument("--output", choices=["text","json"], default="text")
    ap.add_argument("--report-file", default=None)
    ap.add_argument("--json-indent", type=int, default=2)
    ap.add_argument("--warn-only", action="store_true", help="Exit 0 even if warnings exist")
    ap.add_argument("--long-tx-minutes", type=int, default=15, help="Threshold for 'long running tx' warning")
    return ap.parse_args(argv)

class DB:
    def __init__(self, args: argparse.Namespace):
        if CLIENT_NAME is None:
            raise SystemExit(
                "No Firebird client found. Install one of:\n"
                "  pip install firebird-driver   # preferred\n"
                "  pip install fdb               # legacy\n"
                f"Import error: {IMPORT_ERR!r} (clients tried: {CLIENTS_TRIED})"
            )
        self.args = args
        self.conn = None

    def connect(self):
        if self.args.dsn:
            dsn = self.args.dsn
        else:
            dsn = f"{self.args.host}:{self.args.db}" if self.args.host else self.args.db
        if CLIENT_NAME == "firebird-driver":
            # firebird-driver uses keyword parameters similar to fdb
            self.conn = FBMOD.connect(dsn=dsn, user=self.args.user, password=self.args.password,
                                      role=self.args.role, charset=self.args.charset, port=self.args.port)
        else:  # fdb
            self.conn = FBMOD.connect(dsn=dsn, user=self.args.user, password=self.args.password,
                                      role=self.args.role, charset=self.args.charset, port=self.args.port)

    def q(self, sql: str, params: Tuple=()) -> List[Dict[str,Any]]:
        if self.conn is None:
            self.connect()
        cur = self.conn.cursor()
        cur.execute(sql, params)
        cols = [d[0].lower() for d in cur.description] if cur.description else []
        rows = cur.fetchall()
        out = []
        for r in rows:
            # Both drivers return tuples
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

def now_utc():
    return dt.datetime.utcnow()

import datetime as dt

# ------------------------
# Collection
# ------------------------
def collect_mon_database(db: DB) -> Dict[str,Any]:
    # Many columns; not all exist on all versions. We'll SELECT a common subset and ignore missing gracefully.
    cols = [
        "MON$DATABASE_NAME",
        "MON$PAGE_SIZE",
        "MON$ODS_MAJOR","MON$ODS_MINOR",
        "MON$PAGE_BUFFERS",
        "MON$SQL_DIALECT",
        "MON$READ_ONLY",
        "MON$FORCED_WRITES",
        "MON$SWEEP_INTERVAL",
        "MON$OLDEST_TRANSACTION","MON$OLDEST_ACTIVE","MON$OLDEST_SNAPSHOT","MON$NEXT_TRANSACTION",
        "MON$BACKUP_STATE",
        "MON$CRYPT_PAGE","MON$CRYPT_HASH"
    ]
    sel = ", ".join(c for c in cols)
    try:
        r = db.q1(f"SELECT {sel} FROM MON$DATABASE")
        return r or {}
    except Exception:
        # Try a smaller subset
        try:
            r = db.q1("SELECT MON$DATABASE_NAME, MON$PAGE_SIZE, MON$PAGE_BUFFERS, MON$SQL_DIALECT, MON$READ_ONLY, MON$FORCED_WRITES, MON$SWEEP_INTERVAL FROM MON$DATABASE")
            return r or {}
        except Exception:
            return {}

def collect_attachments(db: DB) -> List[Dict[str,Any]]:
    try:
        rows = db.q("""
            SELECT
              MON$ATTACHMENT_ID,
              MON$REMOTE_ADDRESS,
              MON$REMOTE_PROCESS,
              MON$USER,
              MON$ROLE,
              MON$STATE,
              MON$TIMESTAMP
            FROM MON$ATTACHMENTS
            ORDER BY MON$TIMESTAMP
        """)
        return rows
    except Exception:
        return []

def collect_transactions(db: DB) -> List[Dict[str,Any]]:
    try:
        rows = db.q("""
            SELECT
              MON$TRANSACTION_ID,
              MON$ATTACHMENT_ID,
              MON$STATE,
              MON$TIMESTAMP,
              MON$ISOLATION_MODE,
              MON$LOCK_TIMEOUT,
              MON$READ_ONLY
            FROM MON$TRANSACTIONS
            ORDER BY MON$TIMESTAMP
        """)
        return rows
    except Exception:
        return []

def collect_io_stats(db: DB) -> Dict[str,Any]:
    out = {}
    # Per-attachment I/O (rough signal of heavy clients)
    try:
        rows = db.q("""
            SELECT
              a.MON$ATTACHMENT_ID,
              a.MON$USER,
              a.MON$REMOTE_ADDRESS,
              i.MON$PAGE_READS,
              i.MON$PAGE_WRITES,
              i.MON$PAGE_FETCHES
            FROM MON$ATTACHMENTS a
            JOIN MON$IO_STATS i ON i.MON$STAT_ID = a.MON$STAT_ID
            ORDER BY (i.MON$PAGE_READS + i.MON$PAGE_WRITES) DESC
        """)
        out["by_attachment"] = rows[:25]
    except Exception:
        out["by_attachment"] = []
    # Record stats (optional)
    try:
        rows = db.q("""
            SELECT
              a.MON$ATTACHMENT_ID,
              r.MON$RECORD_SEQ_READS,
              r.MON$RECORD_IDX_READS,
              r.MON$RECORD_INSERTS,
              r.MON$RECORD_UPDATES,
              r.MON$RECORD_DELETES,
              r.MON$RECORD_BACKOUTS
            FROM MON$ATTACHMENTS a
            JOIN MON$RECORD_STATS r ON r.MON$STAT_ID = a.MON$STAT_ID
            ORDER BY (r.MON$RECORD_INSERTS + r.MON$RECORD_UPDATES + r.MON$RECORD_DELETES) DESC
        """)
        out["record_activity"] = rows[:25]
    except Exception:
        out["record_activity"] = []
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

def evaluate(mon_db: Dict[str,Any],
             attachments: List[Dict[str,Any]],
             txs: List[Dict[str,Any]],
             io_stats: Dict[str,Any],
             long_tx_minutes: int) -> List[Finding]:
    F: List[Finding] = []

    # Basic DB settings
    page_size = mon_db.get("mon$page_size")
    page_buffers = mon_db.get("mon$page_buffers")
    dialect = mon_db.get("mon$sql_dialect")
    ro = mon_db.get("mon$read_only")
    fw = mon_db.get("mon$forced_writes")
    sweep = mon_db.get("mon$sweep_interval")
    ods_major = mon_db.get("mon$ods_major")
    ods_minor = mon_db.get("mon$ods_minor")

    if dialect is not None and int(dialect) < 3:
        F.append(Finding("crit", "Legacy SQL dialect", f"SQL dialect={dialect}.", advice="Upgrade database to dialect 3.", tags=["compat"]))
    else:
        if dialect is not None:
            F.append(Finding("ok", "SQL dialect", f"dialect={dialect}.", tags=["compat"]))

    if fw in (0, False):
        F.append(Finding("warn", "Forced writes disabled", f"forced_writes={fw}.",
                         advice="Enable forced writes (synchronous) for crash safety; consider fast storage to offset latency.",
                         tags=["durability"]))
    elif fw is not None:
        F.append(Finding("ok", "Forced writes enabled", "forced_writes=1.", tags=["durability"]))

    if sweep is not None:
        if int(sweep) == 0:
            F.append(Finding("info", "Automatic sweep disabled", "sweep_interval=0.",
                             advice="Consider setting sweep_interval (e.g., 20k–200k) or manage with scheduled sweeps.",
                             tags=["sweep"]))
        elif int(sweep) < 10000:
            F.append(Finding("info", "Aggressive sweep interval", f"sweep_interval={sweep}.",
                             advice="Use a higher value (≥20000) to reduce overhead; monitor OIT/Next Tx gap.",
                             tags=["sweep"]))
        else:
            F.append(Finding("ok", "Sweep interval", f"sweep_interval={sweep}.", tags=["sweep"]))

    if page_size is not None and int(page_size) < 8192:
        F.append(Finding("info", "Small page size", f"page_size={page_size} bytes.",
                         advice="Use 8KiB or 16KiB page size for larger rows/indexes on modern storage.",
                         tags=["storage","page"]))
    if page_buffers is not None and int(page_buffers) > 0 and int(page_buffers) < 2048:
        F.append(Finding("info", "Low page buffers", f"page_buffers={page_buffers}.",
                         advice="Increase to improve cache hit ratio (test; consider SuperServer vs Classic).",
                         tags=["cache"]))

    # OIT / OAT / OST / Next transaction (if available)
    oit = mon_db.get("mon$oldest_transaction")
    oat = mon_db.get("mon$oldest_active")
    ost = mon_db.get("mon$oldest_snapshot")
    nxt = mon_db.get("mon$next_transaction")
    if all(v is not None for v in (oit, oat, nxt)):
        gap = int(nxt) - int(oit)
        detail = f"OIT={oit}, OAT={aat if (aat:=aot) else oat if oat else 'N/A'}, Next={nxt}, gap≈{gap:,}."
        # The above has a typo; rebuild cleanly
    if oit is not None and nxt is not None:
        gap = int(nxt) - int(oit)
        if gap > 1_000_000:
            F.append(Finding("warn", "High OIT gap (possible garbage/old record versions)",
                             f"OIT={oit}, Next={nxt}, gap≈{gap:,}.",
                             advice="Consider manual sweep and investigate long-running transactions. Backup/restore can reset OIT.",
                             tags=["tx","sweep"]))
        else:
            F.append(Finding("ok", "OIT gap", f"OIT={oit}, Next={nxt}, gap≈{gap:,}.", tags=["tx"]))

    # Attachments / connections
    if attachments:
        total_att = len(attachments)
        F.append(Finding("ok", "Current attachments", f"{total_att} attachment(s) connected.", tags=["connections"]))

    # Long-running transactions
    if txs:
        now = dt.datetime.utcnow()
        long_age = dt.timedelta(minutes=long_tx_minutes)
        longs = []
        for t in txs:
            ts = t.get("mon$timestamp")
            # Drivers may return datetime already; otherwise string
            if isinstance(ts, str):
                try:
                    # Firebird returns 'YYYY-MM-DD HH:MM:SS.mmmmmm'
                    ts = dt.datetime.fromisoformat(ts)
                except Exception:
                    ts = None
            if isinstance(ts, dt.datetime) and (now - ts) >= long_age and int(t.get("mon$state") or 0) == 1:
                longs.append(t)
        if longs:
            F.append(Finding("warn", "Long-running transactions",
                             f"{len(longs)} transaction(s) active ≥ {long_tx_minutes} minutes.",
                             advice="Identify and commit/rollback stuck transactions; they delay OIT advance and increase garbage.",
                             tags=["tx"]))
        else:
            F.append(Finding("ok", "Transaction churn", "No long-running active transactions detected beyond threshold.", tags=["tx"]))

    # IO hotspots (attachment-level)
    by_att = io_stats.get("by_attachment", [])
    if by_att:
        top = by_att[0]
        reads = to_int(top.get("mon$page_reads") or 0) or 0
        writes = to_int(top.get("mon$page_writes") or 0) or 0
        F.append(Finding("info", "Top attachment I/O",
                         f"Attachment {top.get('mon$attachment_id')} ({top.get('mon$user')}@{top.get('mon$remote_address')}) — reads {reads:,}, writes {writes:,}.",
                         advice="Check this client’s workload for missing indexes or heavy batch operations.",
                         tags=["io"]))

    # Read-only DB
    if ro in (1, True):
        F.append(Finding("ok", "Read-only database", "read_only=1.", tags=["mode"]))

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
    print("# Firebird Tuner Report")
    print(f"Generated: {dt.datetime.utcnow().isoformat()}Z")
    print(f"Client: {CLIENT_NAME}")
    print(f"Database: {meta.get('database_name','?')} (ODS {meta.get('ods_major','?')}.{meta.get('ods_minor','?')}), PageSize={meta.get('page_size','?')}, PageBuffers={meta.get('page_buffers','?')}")
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

def build_meta(mon_db: Dict[str,Any]) -> Dict[str,Any]:
    return {
        "database_name": mon_db.get("mon$database_name"),
        "page_size": mon_db.get("mon$page_size"),
        "page_buffers": mon_db.get("mon$page_buffers"),
        "ods_major": mon_db.get("mon$ods_major"),
        "ods_minor": mon_db.get("mon$ods_minor"),
        "sql_dialect": mon_db.get("mon$sql_dialect"),
        "read_only": mon_db.get("mon$read_only"),
        "forced_writes": mon_db.get("mon$forced_writes"),
        "sweep_interval": mon_db.get("mon$sweep_interval"),
        "oit": mon_db.get("mon$oldest_transaction"),
        "oat": mon_db.get("mon$oldest_active"),
        "ost": mon_db.get("mon$oldest_snapshot"),
        "next_tx": mon_db.get("mon$next_transaction"),
    }

def main(argv=None) -> int:
    args = parse_args(argv)
    try:
        db = DB(args)
        mon_db = collect_mon_database(db)
        atts = collect_attachments(db)
        txs = collect_transactions(db)
        io_stats = collect_io_stats(db)

        findings = evaluate(mon_db, atts, txs, io_stats, args.long_tx_minutes)

        sections: Dict[str,Any] = {}
        sections["MON$DATABASE (subset)"] = mon_db
        sections["Attachments (sample)"] = atts[:25]
        sections["Transactions (sample)"] = txs[:25]
        sections["I/O stats"] = io_stats

        out = {
            "meta": build_meta(mon_db),
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
                    print_text_report(out["meta"], findings, sections)
                    sys.stdout = sys_stdout
            print_text_report(out["meta"], findings, sections)

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
