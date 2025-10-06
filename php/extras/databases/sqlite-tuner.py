
#!/usr/bin/env python3
"""
sqlite_tuner.py — A production-hardened SQLite health & configuration advisor (read-only by default).

- Safe: defaults to read-only mode using SQLite URI (file:db?mode=ro).
- Accurate: inspects PRAGMAs, schema, stats tables, and page-level metrics for evidence-based suggestions.
- Portable: Python 3 stdlib only (sqlite3).
- DevOps-friendly: JSON or text output; exit codes 0 (ok), 1 (warnings), 2 (critical/errors).

USAGE (examples):
  python3 sqlite_tuner.py --db /path/app.db --output text
  python3 sqlite_tuner.py --db /path/app.db --output json > report.json
  python3 sqlite_tuner.py --db /path/app.db --integrity-check

NOTES
- Integrity checks and analysis are read-only (PRAGMA integrity_check is read-only but can be expensive).
- For live, write-heavy apps, consider copying the DB file and running checks off-box or during low-traffic windows.
"""

import argparse
import datetime as dt
import json
import os
import sqlite3
import sys
from typing import Any, Dict, List, Optional, Tuple

def parse_args(argv=None):
    ap = argparse.ArgumentParser(description="Read-only SQLite health & configuration advisor.")
    ap.add_argument("--db", required=True, help="Path to SQLite database file")
    ap.add_argument("--output", choices=["text","json"], default="text", help="Output format (default: text)")
    ap.add_argument("--report-file", default=None, help="Optional file to write full report")
    ap.add_argument("--json-indent", type=int, default=2, help="JSON indentation (default: 2)")
    ap.add_argument("--integrity-check", action="store_true", help="Run PRAGMA integrity_check (can be slow on large DBs)")
    ap.add_argument("--schema-sample", type=int, default=40, help="How many CREATE statements to sample in report")
    ap.add_argument("--warn-only", action="store_true", help="Exit 0 even if warnings exist")
    return ap.parse_args(argv)

# ------------------------
# DB access
# ------------------------
class DB:
    def __init__(self, path: str):
        # read-only URI; prevents accidental writes
        uri = f"file:{path}?mode=ro"
        self.conn = sqlite3.connect(uri, uri=True, timeout=5)
        self.conn.row_factory = sqlite3.Row
        # Safer pragmas for inspection
        self.conn.execute("PRAGMA query_only = ON;")
        # Busy timeout (read-only but helpful in shared scenarios)
        self.conn.execute("PRAGMA busy_timeout = 5000;")

    def q(self, sql: str, params: Tuple=()) -> List[Dict[str,Any]]:
        cur = self.conn.execute(sql, params)
        rows = cur.fetchall()
        return [dict(r) for r in rows]

    def q1(self, sql: str, params: Tuple=()) -> Optional[Dict[str,Any]]:
        r = self.q(sql, params)
        return r[0] if r else None

# ------------------------
# Collection
# ------------------------
def collect_pragmas(db: DB) -> Dict[str, Any]:
    def p(name: str, cast=lambda x: x):
        try:
            row = db.q1(f"PRAGMA {name};")
            if row is None:
                return None
            # PRAGMAs typically return single column named after pragma
            val = list(row.values())[0] if row else None
            try:
                return cast(val)
            except Exception:
                return val
        except Exception:
            return None

    prag = {
        "journal_mode": p("journal_mode", str),
        "synchronous": p("synchronous", int),
        "wal_autocheckpoint": p("wal_autocheckpoint", int),
        "wal_checkpoint": None,  # on-demand info
        "cache_size": p("cache_size", int),              # pages (negative => KiB)
        "page_size": p("page_size", int),
        "temp_store": p("temp_store", int),
        "mmap_size": p("mmap_size", int),
        "foreign_keys": p("foreign_keys", int),
        "ignore_check_constraints": p("ignore_check_constraints", int),
        "locking_mode": p("locking_mode", str),
        "read_uncommitted": p("read_uncommitted", int),
        "auto_vacuum": p("auto_vacuum", int),
        "user_version": p("user_version", int),
        "schema_version": p("schema_version", int),
        "busy_timeout": p("busy_timeout", int),
        "cache_spill": p("cache_spill", int),
        "automatic_index": p("automatic_index", int),
        "secure_delete": p("secure_delete", int),
        "threads": p("threads", int),
    }
    # Attempt to read wal metadata if WAL in use
    try:
        if (prag.get("journal_mode","").upper() == "WAL"):
            row = db.q1("PRAGMA wal_checkpoint(PASSIVE);")  # returns (busy, log, checkpointed)
            if row is not None:
                prag["wal_checkpoint"] = list(row.values())
    except Exception:
        prag["wal_checkpoint"] = None
    return prag

def collect_file_stats(db: DB) -> Dict[str, Any]:
    # page_count and freelist_count help estimate size and free space
    row_pc = db.q1("PRAGMA page_count;") or {}
    row_fl = db.q1("PRAGMA freelist_count;") or {}
    row_ps = db.q1("PRAGMA page_size;") or {}
    pages = list(row_pc.values())[0] if row_pc else 0
    freep = list(row_fl.values())[0] if row_fl else 0
    psize = list(row_ps.values())[0] if row_ps else 4096
    total = pages * psize
    free = freep * psize
    return {
        "page_count": pages,
        "freelist_count": freep,
        "page_size": psize,
        "approx_file_bytes": total,
        "approx_free_bytes": free,
        "approx_used_bytes": max(0, total - free),
        "free_ratio": (free / total) if total else 0.0,
    }

def collect_schema(db: DB, limit: int=40) -> Dict[str, Any]:
    # Basic schema inventory and presence of sqlite_stat1 (ANALYZE)
    objs = db.q("""
        SELECT type, name, tbl_name, sql
        FROM sqlite_schema
        WHERE name NOT LIKE 'sqlite_%'
        ORDER BY type, name
        LIMIT ?
    """, (limit,))
    stat1 = db.q1("SELECT name FROM sqlite_master WHERE type='table' AND name='sqlite_stat1';")
    stat4 = db.q1("SELECT name FROM sqlite_master WHERE type='table' AND name='sqlite_stat4';")
    idx_cnt = db.q1("SELECT count(*) AS c FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_%';") or {"c": 0}
    tbl_cnt = db.q1("SELECT count(*) AS c FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';") or {"c": 0}
    views = db.q1("SELECT count(*) AS c FROM sqlite_master WHERE type='view';") or {"c": 0}
    triggers = db.q1("SELECT count(*) AS c FROM sqlite_master WHERE type='trigger';") or {"c": 0}
    return {
        "objects_sample": objs,
        "has_sqlite_stat1": bool(stat1),
        "has_sqlite_stat4": bool(stat4),
        "tables": tbl_cnt.get("c", 0),
        "indexes": idx_cnt.get("c", 0),
        "views": views.get("c", 0),
        "triggers": triggers.get("c", 0),
    }

def run_integrity_check(db: DB) -> Dict[str, Any]:
    try:
        rows = db.q("PRAGMA integrity_check;")
        # returns one row per issue or 'ok'
        issues = [list(r.values())[0] for r in rows]
        ok = (len(issues) == 1 and str(issues[0]).lower() == "ok")
        return {"ran": True, "ok": ok, "issues": [] if ok else issues[:200]}
    except Exception as e:
        return {"ran": False, "ok": False, "error": str(e)}

# ------------------------
# Findings
# ------------------------
class Finding:
    def __init__(self, severity: str, title: str, detail: str, advice: str = "", tags: Optional[List[str]] = None):
        self.severity = severity  # ok|info|warn|crit
        self.title = title
        self.detail = detail
        self.advice = advice
        self.tags = tags or []

    def as_dict(self):
        return dict(severity=self.severity, title=self.title, detail=self.detail, advice=self.advice, tags=self.tags)

def evaluate(prag: Dict[str,Any], filest: Dict[str,Any], schema: Dict[str,Any], integ: Optional[Dict[str,Any]]) -> List[Finding]:
    F: List[Finding] = []

    # Journal & durability
    jm = str(prag.get("journal_mode") or "").upper()
    sync = prag.get("synchronous")
    if jm == "WAL":
        F.append(Finding("ok", "WAL mode enabled", "journal_mode=WAL.", tags=["journal"]))
        # Synchronous best practice in WAL is NORMAL for many apps, FULL for stronger durability
        if sync is not None and sync < 1:
            F.append(Finding("warn", "synchronous too low", f"synchronous={sync}.",
                             advice="Set synchronous=NORMAL (1) or FULL (2) to reduce corruption risk.", tags=["durability"]))
        else:
            F.append(Finding("ok", "Synchronous level", f"synchronous={sync}.", tags=["durability"]))
        # WAL autocheckpoint sanity
        autockp = prag.get("wal_autocheckpoint")
        if autockp is not None and autockp < 1000:
            F.append(Finding("info", "Low wal_autocheckpoint", f"wal_autocheckpoint={autockp} pages.",
                             advice="Increase to 2000–4000 pages (~8–16MB) to reduce checkpoint churn for busy DBs.",
                             tags=["checkpoint"]))
    else:
        F.append(Finding("info", "Not using WAL", f"journal_mode={jm}.",
                         advice="Use WAL for concurrency and read performance unless you rely on rollback journal semantics.",
                         tags=["journal"]))

    # Cache size & page size
    page_size = prag.get("page_size") or 4096
    cache_sz = prag.get("cache_size")
    # cache_size can be negative => KiB
    if cache_sz is not None:
        if cache_sz < 0:
            bytes_est = abs(cache_sz) * 1024
        else:
            bytes_est = cache_sz * page_size
        if bytes_est < 8 * 1024 * 1024:
            F.append(Finding("info", "Small page cache", f"cache_size≈{bytes_est/1024/1024:.1f} MiB.",
                             advice="Increase cache_size (or set PRAGMA cache_size=-N for KiB) to improve read locality.",
                             tags=["cache"]))
        else:
            F.append(Finding("ok", "Page cache", f"cache_size≈{bytes_est/1024/1024:.1f} MiB.", tags=["cache"]))

    # Free space / fragmentation
    free_ratio = filest.get("free_ratio", 0.0)
    freelist = filest.get("freelist_count", 0)
    if free_ratio >= 0.2 and freelist >= 1000:
        F.append(Finding("info", "High free space in freelist",
                         f"Free pages≈{freelist} (~{free_ratio*100:.1f}% of file).",
                         advice="Consider VACUUM (or incremental + auto_vacuum=incremental) to reclaim space.",
                         tags=["vacuum","space"]))
    else:
        F.append(Finding("ok", "Free space", f"Free ratio ~{free_ratio*100:.1f}% (freelist={freelist}).", tags=["space"]))

    # auto_vacuum
    av = prag.get("auto_vacuum")
    if av == 0:
        F.append(Finding("info", "auto_vacuum OFF", "auto_vacuum=0 (none).",
                         advice="Enable auto_vacuum=FULL or INCREMENTAL to manage free space growth over time.",
                         tags=["vacuum"]))
    elif av == 1:
        F.append(Finding("ok", "auto_vacuum FULL", "auto_vacuum=1.", tags=["vacuum"]))
    elif av == 2:
        F.append(Finding("ok", "auto_vacuum INCREMENTAL", "auto_vacuum=2.", tags=["vacuum"]))

    # Foreign keys & constraints
    fk = prag.get("foreign_keys")
    if fk in (None, 0):
        F.append(Finding("info", "Foreign keys disabled", f"foreign_keys={fk}.",
                         advice="Enable PRAGMA foreign_keys=ON for relational integrity unless application enforces it.",
                         tags=["constraints"]))
    else:
        F.append(Finding("ok", "Foreign keys enforced", "foreign_keys=ON.", tags=["constraints"]))

    # Temp store
    ts = prag.get("temp_store")
    if ts == 0:
        F.append(Finding("info", "temp_store default", "temp_store=0 (default).",
                         advice="Consider temp_store=MEMORY to reduce disk I/O for small intermediates.",
                         tags=["temp"]))

    # mmap_size
    mm = prag.get("mmap_size") or 0
    if mm == 0:
        F.append(Finding("info", "mmap disabled", "mmap_size=0.",
                         advice="Enable mmap_size (e.g., 64–512 MiB) on 64-bit to reduce syscalls; test for workload.",
                         tags=["mmap"]))

    # busy_timeout
    bt = prag.get("busy_timeout") or 0
    if bt < 2000:
        F.append(Finding("info", "Low busy_timeout", f"busy_timeout={bt} ms.",
                         advice="Set busy_timeout to 2000–5000 ms to reduce 'database is locked' errors under contention.",
                         tags=["concurrency"]))
    else:
        F.append(Finding("ok", "Busy timeout", f"busy_timeout={bt} ms.", tags=["concurrency"]))

    # automatic_index
    ai = prag.get("automatic_index")
    if ai in (None, 0):
        F.append(Finding("info", "automatic_index off", f"automatic_index={ai}.",
                         advice="Enable automatic_index=ON to allow SQLite to create transient indexes for joins.",
                         tags=["planner"]))
    else:
        F.append(Finding("ok", "automatic_index on", "automatic_index=ON.", tags=["planner"]))

    # Stats presence
    if schema.get("has_sqlite_stat1"):
        F.append(Finding("ok", "ANALYZE stats present", "sqlite_stat1 exists.", tags=["planner"]))
    else:
        F.append(Finding("info", "No ANALYZE stats", "sqlite_stat1 missing.",
                         advice="Run ANALYZE to create sqlite_stat1 and improve query planning.", tags=["planner"]))

    # Integrity check result
    if integ is not None and integ.get("ran"):
        if integ.get("ok"):
            F.append(Finding("ok", "Integrity check OK", "PRAGMA integrity_check returned 'ok'.", tags=["integrity"]))
        else:
            F.append(Finding("crit", "Integrity check FAILED",
                             f"Issues: {len(integ.get('issues', []))} (showing up to 200).",
                             advice="Restore from backup or investigate corruption; see PRAGMA quick_check for faster probe.",
                             tags=["integrity"]))

    return F

# ------------------------
# Output formatting
# ------------------------
def summarize(findings: List[Finding]) -> Dict[str,int]:
    s = {"ok":0, "info":0, "warn":0, "crit":0}
    for f in findings:
        s[f.severity] = s.get(f.severity, 0) + 1
    return s

def human_bytes(n: Optional[int]) -> str:
    if n is None:
        return "N/A"
    units = ["B","KiB","MiB","GiB","TiB"]
    i = 0
    f = float(n)
    while f >= 1024 and i < len(units)-1:
        f /= 1024.0
        i += 1
    return f"{f:,.1f} {units[i]}"

def print_text_report(meta: Dict[str,Any], findings: List[Finding], sections: Dict[str,Any]):
    print("# SQLite Tuner Report")
    print(f"Generated: {dt.datetime.utcnow().isoformat()}Z")
    print(f"Database: {meta.get('db_path')}")
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
        db = DB(args.db)
        prag = collect_pragmas(db)
        filest = collect_file_stats(db)
        schema = collect_schema(db, args.schema_sample)
        integ = run_integrity_check(db) if args.integrity_check else None

        findings = evaluate(prag, filest, schema, integ)

        sections: Dict[str,Any] = {}
        sections["PRAGMAs (subset)"] = [
            {"name":"journal_mode", "value": prag.get("journal_mode")},
            {"name":"synchronous", "value": prag.get("synchronous")},
            {"name":"wal_autocheckpoint", "value": prag.get("wal_autocheckpoint")},
            {"name":"cache_size", "value": prag.get("cache_size")},
            {"name":"page_size", "value": prag.get("page_size")},
            {"name":"temp_store", "value": prag.get("temp_store")},
            {"name":"mmap_size", "value": prag.get("mmap_size")},
            {"name":"foreign_keys", "value": prag.get("foreign_keys")},
            {"name":"auto_vacuum", "value": prag.get("auto_vacuum")},
            {"name":"busy_timeout", "value": prag.get("busy_timeout")},
            {"name":"automatic_index", "value": prag.get("automatic_index")},
        ]
        sections["File stats"] = {
            "page_size": filest.get("page_size"),
            "page_count": filest.get("page_count"),
            "freelist_count": filest.get("freelist_count"),
            "approx_file_size": human_bytes(filest.get("approx_file_bytes")),
            "approx_free": human_bytes(filest.get("approx_free_bytes")),
            "approx_used": human_bytes(filest.get("approx_used_bytes")),
            "free_ratio_pct": round(filest.get("free_ratio", 0.0)*100.0, 2),
        }
        sections["Schema sample"] = schema.get("objects_sample", [])
        if args.integrity_check:
            sections["Integrity check"] = integ

        out = {
            "meta": {"db_path": os.path.abspath(args.db)},
            "findings": [f.as_dict() for f in findings],
            "sections": sections,
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
            print(json.dumps({"error": msg}, indent=args.json_indent))
        else:
            print(msg)
        return 2

if __name__ == "__main__":
    sys.exit(main())
