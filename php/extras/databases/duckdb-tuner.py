
#!/usr/bin/env python3
"""
duckdb_tuner.py — A production-hardened DuckDB health & configuration advisor (read-only).

- Safe: opens the database in read-only mode and runs introspection queries only.
- Accurate: evidence-based suggestions from duckdb_settings(), PRAGMA database_size, and system catalogs.
- Portable: Python 3 only (uses the official duckdb Python package).
- DevOps-friendly: JSON or text output; exit codes 0 (ok), 1 (warnings), 2 (critical/errors).

USAGE (examples):
  python3 duckdb_tuner.py --db /path/to/analytics.duckdb --output text
  python3 duckdb_tuner.py --db /path/to/analytics.duckdb --output json > report.json

Notes:
- Some settings differ across DuckDB versions; the script degrades gracefully if a setting/view is missing.
"""

import argparse
import datetime as dt
import json
import os
import sys
import platform
from typing import Any, Dict, List, Optional, Tuple

CLIENT_ERR = None

def _import_duckdb():
    global CLIENT_ERR
    try:
        import duckdb as ddb
        return ddb
    except Exception as e:
        CLIENT_ERR = e
        return None

duckdb = _import_duckdb()

def parse_args(argv=None):
    ap = argparse.ArgumentParser(description="Read-only DuckDB health & configuration advisor.")
    ap.add_argument("--db", required=True, help="Path to DuckDB database file")
    ap.add_argument("--output", choices=["text","json"], default="text")
    ap.add_argument("--report-file", default=None)
    ap.add_argument("--json-indent", type=int, default=2)
    ap.add_argument("--warn-only", action="store_true", help="Exit 0 even if warnings exist")
    ap.add_argument("--schema-sample", type=int, default=50, help="Rows to sample from duckdb_tables/columns in report")
    return ap.parse_args(argv)

# ------------------------
# DB access
# ------------------------
class DB:
    def __init__(self, path: str):
        if duckdb is None:
            raise SystemExit(
                "DuckDB Python package not found. Install with:\n"
                "  pip install duckdb\n"
                f"Import error: {CLIENT_ERR!r}"
            )
        self.path = path
        # Open in read-only mode. (DuckDB supports read_only kwarg in Python API.)
        self.con = duckdb.connect(database=path, read_only=True)

    def q(self, sql: str, params: Tuple=()) -> List[Dict[str,Any]]:
        try:
            cur = self.con.execute(sql, params)
            df = cur.fetch_df()
            return df.to_dict("records") if df is not None else []
        except Exception as e:
            # Fallback: run and fetch as tuples
            try:
                cur = self.con.execute(sql, params)
                rows = cur.fetchall()
                cols = [d[0] for d in cur.description] if cur.description else []
                return [ {cols[i]: r[i] for i in range(len(cols))} for r in rows ]
            except Exception:
                return []

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
        if x is None: return None
        if isinstance(x, (int,)): return int(x)
        # try parse strings like '1GB', '512MB', '80%'
        s = str(x).strip().upper()
        if s.endswith("%"):
            # percentage — caller needs to interpret
            return None
        mult = 1
        if s.endswith("KB"): mult, s = 1024, s[:-2]
        elif s.endswith("MB"): mult, s = 1024**2, s[:-2]
        elif s.endswith("GB"): mult, s = 1024**3, s[:-2]
        elif s.endswith("TB"): mult, s = 1024**4, s[:-2]
        return int(float(s) * mult)
    except Exception:
        return None

def cpu_count() -> int:
    try:
        import os
        return os.cpu_count() or 1
    except Exception:
        return 1

# ------------------------
# Collection
# ------------------------
def collect_version(db: DB) -> Dict[str,Any]:
    row = db.q1("SELECT duckdb_version() AS version") or {}
    return row

def collect_settings(db: DB) -> List[Dict[str,Any]]:
    # duckdb_settings() returns setting name, value, default, description (varies by version)
    rows = db.q("SELECT * FROM duckdb_settings()")
    return rows

def collect_database_size(db: DB) -> Dict[str,Any]:
    row = db.q1("PRAGMA database_size") or {}
    # Columns include: database_size, block_size, total_blocks, used_blocks, free_blocks, etc (varies by version)
    return row

def collect_tables(db: DB, sample: int=50) -> Dict[str,Any]:
    out = {}
    try:
        out["tables"] = db.q(f"SELECT * FROM duckdb_tables() ORDER BY database, schema, name LIMIT {sample}")
    except Exception:
        out["tables"] = []
    try:
        out["columns"] = db.q(f"SELECT * FROM duckdb_columns() ORDER BY database, schema, table_name LIMIT {sample}")
    except Exception:
        out["columns"] = []
    try:
        out["views"] = db.q(f"SELECT * FROM duckdb_views() ORDER BY database, schema, view_name LIMIT {sample}")
    except Exception:
        out["views"] = []
    # Try to sample storage info on a few largest known tables if available
    try:
        # Without stats, we just sample first few tables
        tbls = out.get("tables", [])[:5]
        sinfo = []
        for t in tbls:
            sch = t.get("schema") or t.get("table_schema") or "main"
            name = t.get("name") or t.get("table_name")
            if name:
                rows = db.q(f"PRAGMA storage_info('{sch}.{name}')")
                # Add table name to rows
                for r in rows:
                    r["_table"] = f"{sch}.{name}"
                sinfo.extend(rows)
        out["storage_info_sample"] = sinfo[:200]
    except Exception:
        out["storage_info_sample"] = []
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

def get_setting(settings: List[Dict[str,Any]], key: str) -> Optional[Dict[str,Any]]:
    key_lower = key.lower()
    for s in settings:
        # duckdb_settings columns: name, value, default_value, description (names vary; access defensively)
        name = (s.get("name") or s.get("setting_name") or "").lower()
        if name == key_lower:
            return s
    return None

def evaluate(version: Dict[str,Any], settings: List[Dict[str,Any]], dbsize: Dict[str,Any]) -> List[Finding]:
    F: List[Finding] = []

    # Threads
    s_threads = get_setting(settings, "threads")
    cpu = cpu_count()
    if s_threads:
        val = s_threads.get("value")
        ival = to_int(val)
        # In DuckDB, 0 or NULL may indicate auto; treat non-positive as auto
        if ival is None or (isinstance(val, str) and val.strip().lower() in ("", "null", "auto")) or (isinstance(ival, int) and ival <= 0):
            F.append(Finding("ok", "Threads (auto)", f"threads={val} (auto) with {cpu} CPU(s).", tags=["parallelism"]))
        else:
            if ival < min(cpu, 4):
                F.append(Finding("info", "Low threads setting", f"threads={ival}, host CPUs≈{cpu}.",
                                 advice="Increase threads to use available cores, unless constrained by IO/latency.", tags=["parallelism"]))
            else:
                F.append(Finding("ok", "Threads setting", f"threads={ival}, CPUs≈{cpu}.", tags=["parallelism"]))
    else:
        F.append(Finding("info", "Threads setting not visible", "duckdb_settings() missing 'threads'.", tags=["parallelism"]))

    # Memory limit
    s_mem = get_setting(settings, "memory_limit")
    if s_mem:
        val = s_mem.get("value")
        ival = to_int(val)
        if val in (None, "", "NULL", "null") or (isinstance(val,str) and val.endswith("%")):
            F.append(Finding("ok", "Memory limit (auto/percent)", f"memory_limit={val}.", tags=["memory"]))
        else:
            if ival is not None and ival < (1<<30):  # <1GiB
                F.append(Finding("info", "Small memory_limit", f"memory_limit≈{fmt_bytes(ival)}.",
                                 advice="Increase memory_limit for large analytic queries or set as a percentage (e.g., 80%).",
                                 tags=["memory"]))
            else:
                F.append(Finding("ok", "Memory limit", f"memory_limit={val}.", tags=["memory"]))
    else:
        F.append(Finding("info", "Memory limit not visible", "duckdb_settings() missing 'memory_limit'.", tags=["memory"]))

    # Temp directory
    s_tmp = get_setting(settings, "temp_directory") or get_setting(settings, "temp_directory_name")
    if s_tmp:
        val = s_tmp.get("value")
        if val in (None, "", "NULL", "null"):
            F.append(Finding("info", "No temp_directory set", "Temporary files may spill to OS default or in-memory.",
                             advice="Set temp_directory to fast local SSD when running large sorts/aggregations.", tags=["temp"]))
        else:
            F.append(Finding("ok", "Temp directory configured", f"temp_directory={val}.", tags=["temp"]))
    else:
        F.append(Finding("info", "Temp directory not visible", "duckdb_settings() missing temp_directory.", tags=["temp"]))

    # Object cache (beneficial for repeated scans of Parquet/CSV catalog objects)
    s_cache = get_setting(settings, "enable_object_cache")
    if s_cache:
        val = str(s_cache.get("value")).lower()
        if val in ("1","true","on"):
            F.append(Finding("ok", "Object cache enabled", "enable_object_cache=ON.", tags=["cache"]))
        else:
            F.append(Finding("info", "Object cache disabled", "enable_object_cache=OFF.",
                             advice="Enable for workloads with repeated reads of external files (parquet/csv/http).", tags=["cache"]))

    # Autoinstall known extensions (security posture)
    s_autoext = get_setting(settings, "autoinstall_known_extensions")
    if s_autoext:
        val = str(s_autoext.get("value")).lower()
        if val in ("1","true","on"):
            F.append(Finding("info", "Autoinstall known extensions enabled",
                             "autoinstall_known_extensions=ON.",
                             advice="Consider disabling in locked-down prod; install required extensions explicitly.", tags=["security"]))
        else:
            F.append(Finding("ok", "Autoinstall known extensions disabled", "autoinstall_known_extensions=OFF.", tags=["security"]))

    # Filesystem read-ahead (if exposed)
    s_reada = get_setting(settings, "filesystem_read_ahead")
    if s_reada and s_reada.get("value") not in (None, "", "NULL", "null"):
        ival = to_int(s_reada.get("value"))
        if ival is not None and ival < (512*1024):
            F.append(Finding("info", "Low filesystem_read_ahead", f"{fmt_bytes(ival)}.",
                             advice="Increase for high-latency object storage reads (e.g., set to a few MiB).", tags=["io"]))

    # Database size (sanity/info only)
    if dbsize:
        # Try common columns
        size = dbsize.get("database_size") or dbsize.get("total_blocks")
        if isinstance(size, int):
            F.append(Finding("ok", "Database size", f"database_size≈{fmt_bytes(size)}.", tags=["capacity"]))
        else:
            # Could be bytes in 'total_size' etc.
            for k in ("total_size","database_size","size"):
                v = dbsize.get(k)
                if isinstance(v, int):
                    F.append(Finding("ok", "Database size", f"{k}≈{fmt_bytes(v)}.", tags=["capacity"]))
                    break

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
    print("# DuckDB Tuner Report")
    print(f"Generated: {dt.datetime.utcnow().isoformat()}Z")
    print(f"DuckDB version: {meta.get('version','?')}")
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
            for row in data[:50]:
                try:
                    print("  - " + ", ".join(f"{k}={row[k]}" for k in row.keys()))
                except Exception:
                    print("  - row")
        else:
            print(json.dumps(data, indent=2, default=str))

def main(argv=None) -> int:
    args = parse_args(argv)
    try:
        db = DB(args.db)
        version = collect_version(db)
        settings = collect_settings(db)
        dbsize = collect_database_size(db)
        cats = collect_tables(db, args.schema_sample)

        findings = evaluate(version, settings, dbsize)

        sections: Dict[str,Any] = {}
        # Show subset of interesting settings if present
        def sval(k):
            s = get_setting(settings, k)
            return {"name": k, "value": s.get("value") if s else None}
        sections["Settings (subset)"] = [sval(k) for k in [
            "threads","memory_limit","temp_directory","autoinstall_known_extensions","enable_object_cache","filesystem_read_ahead"
        ]]
        sections["database_size (PRAGMA)"] = dbsize
        sections["Catalog sample: tables"] = cats.get("tables", [])
        sections["Catalog sample: columns"] = cats.get("columns", [])
        sections["Storage info (sample)"] = cats.get("storage_info_sample", [])

        out = {
            "meta": version,
            "findings": [f.as_dict() for f in findings],
            "sections": sections,
            "client": "duckdb"
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
            print(json.dumps({"error": msg}, indent=args.json_indent))
        else:
            print(msg)
        return 2

if __name__ == "__main__":
    sys.exit(main())
