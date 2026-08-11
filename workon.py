#!/usr/bin/env python3
"""
workon.py - Python port of workon.php: one-shot prep for "work on {site} {page}"
PostEdit sessions. See workon.md for when to use this vs. workon.php - either
works, pick whichever interpreter (php/python) is on the machine you're on.

Does every step that would otherwise each trigger a separate permission
prompt, in a single process:
  1. Resolve {alias} -> host from postedit/postedit.xml (build the site URL).
  2. Ensure the PostEdit watcher for {alias} is running (launch if not,
     filtered to the named page), in a new Windows Terminal tab when possible.
  3. Ensure a debug Chrome is up on the debug port and showing the page.
  4. Confirm the Chrome target for the page exists (retry once, else print
     the open tabs).
  5. (optional) Capture a mobile screenshot via Node CDP + a 1px reflow nudge.
  6. Print a mirror inventory: the named page's record id and the local path
     + size of each of its fields, plus the other records on disk.

It does NOT resolve a wamcp db_id or call any wamcp tool - the assistant
resolves {alias} to a db_id via the wamcp `databases` tool itself.

Usage:
  python workon.py <alias> [page] [options]

`python workon.py --help` prints the full capability + option reference; that
help text (see usage() below) is the single source of truth.

Exit code 0 on success, 2 if the page target could not be confirmed, 1 on a
usage/setup error.
"""

import json
import os
import re
import shutil
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request

IS_WIN = os.name == "nt"
ROOT = os.path.dirname(os.path.abspath(__file__))
XML = os.path.join(ROOT, "postedit", "postedit.xml")

LOGFILE = None
JSON_MODE = False


def sanitize(s):
    return re.sub(r"[^a-z0-9_\-]", "", str(s), flags=re.IGNORECASE)


def log_line(msg):
    if LOGFILE:
        try:
            with open(LOGFILE, "a", encoding="utf-8") as f:
                f.write(msg + "\n")
        except Exception:
            pass


def out(msg):
    print(msg)
    log_line(msg)


def err(msg):
    print(msg, file=sys.stderr)
    log_line(msg)


def fail(msg):
    err("ERROR: " + msg)
    sys.exit(1)


def step(msg):
    if not JSON_MODE:
        out(msg)
    else:
        log_line(msg)


def usage():
    out(
        """workon.py - one-shot startup for a "work on {site} {page}" session.
(Python port of workon.php - same behavior, either works; see workon.md.)

  python workon.py <alias|wasql> [page] [options]

Everything below happens in ONE process, so the whole startup costs a single
permission approval instead of one per step (no separate curl / mkdir / find).

WHAT IT DOES
  1. Resolves {alias} -> host from postedit/postedit.xml and builds the URL.
     Alias 'wasql' = local framework mode: http://localhost/php/admin.php,
     no PostEdit, no watcher, repo files are edited directly.
  2. Ensures the PostEdit watcher is running for {alias}, FILTERED to the named
     page (only records whose name contains it are synced/watched -> fast
     startup). An already-running watcher is reported, never restarted, because
     postedit.php's startup re-sync is destructive: it backs up postEditFiles/
     {alias} to {alias}_bak, deletes the working files and re-downloads them.
     When launched from inside Windows Terminal the watcher goes in a NEW TAB
     of the current window (focus is handed straight back), so the whole
     session stays in one window - see --no-tab / --no-chase.
  3. Ensures a debug Chrome is up on the debug port and showing the page.
     Reuses an already-running debug instance (opens a tab in it via PUT on
     /json/new, with GET + curl fallbacks); only launches Chrome if none.
  4. Confirms the page's Chrome target exists, retrying the tab-open once. If it
     still fails, it PRINTS THE OPEN TABS so you can diagnose without a curl.
     The confirmed tab's id is remembered per alias (temp/wasql-workon-tab-
     {alias}.json) so a re-run for the same alias reliably comes back to the
     SAME tab even when other "work on" sessions have other tabs open in the
     same shared debug Chrome instance - screenshotting targets that exact
     tab id instead of guessing "the first page tab" in the whole instance.
  5. Optional mobile screenshot via Node + CDP, with the 1px reflow nudge baked
     in. Creates the --shot parent directory if it doesn't exist.
  6. Mirror inventory: the named page's record id and the full local path +
     size of every one of its fields (body/controller/functions/css/js), then
     the other records on disk with their ids. Read the field you need straight
     from this - it replaces the find/ls over postEditFiles.

ARGUMENTS
  <alias>            postedit.xml alias (e.g. dexpdq), or 'wasql' for local mode
  [page]             page/record to open and filter on (default: index, or
                     php/admin.php in local mode)

OPTIONS
  --page=NAME        page to open (overrides the positional arg)
  --port=N           Chrome debug port (default: 9222)
  --chrome=PATH      explicit Chrome executable
  --profile=PATH     Chrome --user-data-dir (default: temp/wasql-chrome-debug)
  --shot[=PATH]      write a screenshot PNG (bare --shot -> a temp path)
  --no-shot          never screenshot (the default when --shot is absent)
  --width=N          screenshot viewport width (default: 390 = mobile)
  --reshoot=URL      lightweight follow-up shot during an already-open session:
                     reuse the remembered tab (no watcher launch, no inventory)
                     and navigate+screenshot URL. Implies a shot even without
                     --shot (falls back to a temp path). Use this instead of a
                     standalone node/CDP screenshot script for "did my edit
                     work" checks after the initial "work on" call.
  --no-chrome        skip Chrome entirely - a pure alias/watcher status check
                     (is the watcher still running?), no tab, no screenshot.
                     Mutually exclusive with --reshoot.
  --no-watcher       do not launch the PostEdit watcher if it's missing
  --no-tab           always give the watcher its OWN console window, even when
                     running inside Windows Terminal (default: add a tab to the
                     current WT window instead of opening another window)
  --no-chase         leave focus on the new watcher tab instead of handing it
                     back to the tab you were working in
  --filter=a,b       explicit watcher filter(s) instead of the page name
  --no-filter        watcher syncs/watches ALL records - needed when you also
                     need the page's _templates record or other pages
  --inv-max=N        max "other records" listed in the inventory (default: 40)
  --no-inventory     skip the mirror inventory entirely
  --json             emit ONLY a machine-readable JSON summary (last line)
  --log=PATH         copy all output here (default: temp/wasql-workon-{alias}.log,
                     truncated each run); --no-log disables it
  --help             this text

AFTERWARDS (the assistant does these - workon.py cannot)
  * Resolve db_id    wamcp has no setdb/session-default database. Call the
                     wamcp `databases` tool to resolve <name> to a db_id (it
                     often differs from the postedit alias, e.g. dexpdq ->
                     dexpdq_mysql), then pass that db_id on every wamcp call.
  * Read the PNG     the screenshot is only written, never displayed.

GOTCHAS
  * NEVER pipe this through tail/head - it launches detached children and a
    pager will buffer forever, showing nothing. Redirect to a file instead.
    Every run also copies its output to temp/wasql-workon-{alias}.log.
  * Exit code: 0 = target confirmed, 2 = not confirmed, 1 = usage/setup error.
"""
    )


def http_get(url, timeout=2):
    """GET a debug-endpoint URL, return body (even on non-2xx) or None on failure."""
    req = urllib.request.Request(url, headers={"Connection": "close"})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return resp.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        try:
            return e.read().decode("utf-8", "replace")
        except Exception:
            return None
    except Exception:
        return None


def find_target(json_text, host, remembered_id=None):
    """Return the debug target dict matching remembered_id, else the first
    whose url contains host, else None. remembered_id checked first so
    concurrent "work on" sessions sharing one debug Chrome land on their own
    tab rather than each other's."""
    try:
        lst = json.loads(json_text) if json_text else None
    except Exception:
        lst = None
    if not isinstance(lst, list):
        return None
    if remembered_id is not None:
        for t in lst:
            if isinstance(t, dict) and t.get("id") == remembered_id:
                return t
    if host:
        for t in lst:
            if isinstance(t, dict) and host.lower() in str(t.get("url", "")).lower():
                return t
    return None


def new_tab(json_url, url):
    """Open url as a new tab in an already-running debug Chrome. Chrome 111+
    only accepts PUT on /json/new (GET returns 405), so PUT is tried first,
    then GET, then curl as a last resort."""
    endpoint = json_url + "/new?" + url

    def is_target(body):
        if not body:
            return False
        try:
            j = json.loads(body)
        except Exception:
            return False
        return isinstance(j, dict) and bool(j.get("id"))

    for method in ("PUT", "GET"):
        try:
            req = urllib.request.Request(endpoint, method=method, headers={"Connection": "close"})
            with urllib.request.urlopen(req, timeout=5) as resp:
                if is_target(resp.read().decode("utf-8", "replace")):
                    return True
        except urllib.error.HTTPError as e:
            try:
                if is_target(e.read().decode("utf-8", "replace")):
                    return True
            except Exception:
                pass
        except Exception:
            pass
    try:
        res = subprocess.run(["curl", "-s", "-X", "PUT", endpoint], capture_output=True, text=True, timeout=5)
        if is_target(res.stdout):
            return True
    except Exception:
        pass
    return False


_WT_CACHE = {"checked": False, "path": None, "reason": None}


def wt_exe(is_win):
    """Path to wt.exe if this process runs inside a Windows Terminal window,
    else (None, reason). Cached - the `where` probe is a subprocess."""
    if _WT_CACHE["checked"]:
        return _WT_CACHE["path"], _WT_CACHE["reason"]
    _WT_CACHE["checked"] = True
    if not is_win:
        _WT_CACHE["reason"] = "not Windows"
        return None, _WT_CACHE["reason"]
    if not os.environ.get("WT_SESSION"):
        _WT_CACHE["reason"] = "WT_SESSION is not set - this console is not a Windows Terminal tab"
        return None, _WT_CACHE["reason"]
    try:
        res = subprocess.run(["where", "wt.exe"], capture_output=True, text=True, timeout=5)
        text = res.stdout or ""
    except Exception:
        text = ""
    m = re.search(r"^(.*wt\.exe)\s*$", text, re.MULTILINE | re.IGNORECASE)
    if m:
        _WT_CACHE["path"] = m.group(1).strip()
        _WT_CACHE["reason"] = None
    else:
        _WT_CACHE["reason"] = "wt.exe is not on PATH (`where wt.exe` found nothing)"
    return _WT_CACHE["path"], _WT_CACHE["reason"]


def launch_wt_tab(cmd, title, cwd, chase=True):
    """Add a tab to the Windows Terminal window hosting this process and run
    cmd in it. Returns (ok, why). `-w 0` targets the current window; wt splits
    its own command line on ';' so literal semicolons must be escaped."""
    def esc(s):
        return s.replace(";", "\\;")

    full = 'wt.exe -w 0 new-tab --title "%s" -d "%s" %s' % (esc(title), esc(cwd), esc(cmd))
    if chase:
        full += " ; focus-tab -p"
    err_file = os.path.join(tempfile.gettempdir(), "wasql-wt-err.txt")
    try:
        with open(err_file, "w", encoding="utf-8") as ef:
            p = subprocess.run(full, shell=True, stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL, stderr=ef)
        rc = p.returncode
    except Exception:
        return False, "could not start wt.exe"
    if rc == 0:
        return True, None
    try:
        with open(err_file, encoding="utf-8", errors="replace") as ef:
            errtext = ef.read()
    except Exception:
        errtext = ""
    errtext = re.sub(r"\s+", " ", errtext).strip()
    why = "wt.exe refused the request (exit %s)" % rc + (": %s" % errtext if errtext else "")
    return False, why


def launch_detached(cmd, is_win, title=""):
    """Launch a detached process: `start` on Windows, `nohup ... &` elsewhere.
    stdio is redirected to the null device so a long-lived grandchild (Chrome,
    the watcher) never inherits a handle to this process's caller's pipe."""
    if is_win:
        full = 'start "%s" %s' % (title, cmd)
    else:
        full = "nohup %s >/dev/null 2>&1 &" % cmd
    try:
        subprocess.run(full, shell=True, stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except Exception:
        pass


def filter_token(s):
    """Strip a page/filter down to a single shell-safe token."""
    s = str(s).split("?", 1)[0].lstrip("/")
    first = s.split("/", 1)[0]
    return re.sub(r"[^a-z0-9_\-.]", "", first, flags=re.IGNORECASE).lower()


def running_filters(cmdline, alias):
    """Filter args of a running watcher: tokens after 'postedit.php {alias}'.
    None means the command line couldn't be parsed."""
    m = re.search(r'postedit\.php["\']?\s+' + re.escape(alias) + r"(?:\s+(.*))?$", cmdline.strip(), re.IGNORECASE)
    if not m:
        return None
    rest = (m.group(1) or "").strip()
    if rest == "":
        return []
    return [t.lower() for t in re.split(r"\s+", rest) if t]


def filter_label(f):
    return ", ".join(f) if f else "none (all records)"


def port_open(host, port, timeout=0.3):
    try:
        with socket.create_connection((host, port), timeout=timeout):
            return True
    except Exception:
        return False


def int_opt(opts, key, default):
    if key not in opts:
        return default
    v = opts[key]
    if v is True:
        return default
    try:
        return int(v)
    except (TypeError, ValueError):
        return default


def main():
    global LOGFILE, JSON_MODE

    # ---- parse args ---------------------------------------------------------
    alias = None
    page = None
    opts = {}
    for a in sys.argv[1:]:
        if a.startswith("--"):
            kv = a[2:].split("=", 1)
            opts[kv[0]] = kv[1] if len(kv) > 1 else True
        elif alias is None:
            alias = a
        elif page is None:
            page = a

    if "help" in opts or "h" in opts or alias == "?":
        usage()
        sys.exit(0)
    if alias is None:
        err("ERROR: no alias given.")
        usage()
        sys.exit(1)
    if "page" in opts and opts["page"] is not True:
        page = opts["page"]
    page_given = page is not None and page != ""
    if not page_given:
        page = ""

    if "no-log" not in opts:
        LOGFILE = (opts["log"] if "log" in opts and opts["log"] is not True
                   else os.path.join(tempfile.gettempdir(), "wasql-workon-%s.log" % sanitize(alias)))
        try:
            with open(LOGFILE, "w", encoding="utf-8"):
                pass
        except Exception:
            pass

    port = int_opt(opts, "port", 9222)
    width = int_opt(opts, "width", 390)

    reshoot_given = "reshoot" in opts and opts["reshoot"] is not True
    if "reshoot" in opts and not reshoot_given:
        fail("--reshoot needs a URL, e.g. --reshoot=https://host/page")
    no_chrome = "no-chrome" in opts
    if no_chrome and reshoot_given:
        fail("--no-chrome and --reshoot are contradictory (reshoot needs Chrome).")
    do_shot = ("shot" in opts or reshoot_given) and "no-shot" not in opts and not no_chrome
    if not do_shot:
        shot_out = None
    elif "shot" not in opts or opts["shot"] is True:
        shot_out = os.path.join(tempfile.gettempdir(), "wasql-shot-%s.png" % sanitize(alias))
    else:
        shot_out = opts["shot"]

    do_watch = "no-watcher" not in opts and not reshoot_given
    use_tab = "no-tab" not in opts
    do_chase = "no-chase" not in opts
    profile = opts.get("profile", os.path.join(tempfile.gettempdir(), "wasql-chrome-debug"))
    if profile is True:
        profile = os.path.join(tempfile.gettempdir(), "wasql-chrome-debug")

    JSON_MODE = "json" in opts

    # ---- watcher filters -----------------------------------------------------
    filters = []
    if "no-filter" in opts:
        filters = []
    elif "filter" in opts and opts["filter"] is not True:
        for f in str(opts["filter"]).split(","):
            t = filter_token(f)
            if t != "":
                filters.append(t)
    elif page_given:
        t = filter_token(page)
        if t != "":
            filters = [t]

    # ---- 1. resolve target ----------------------------------------------------
    local_mode = alias.lower() == "wasql"
    host = None
    insecure = False
    if local_mode:
        host = "localhost"
        do_watch = False
        target = page.lstrip("/") if page_given else "php/admin.php"
        url = "http://localhost/" + target
    else:
        if not os.path.isfile(XML):
            fail("postedit.xml not found at %s" % XML)
        with open(XML, encoding="utf-8", errors="replace") as f:
            xml_text = f.read()
        for attrs in re.findall(r"<host\b([^>]*?)/?>", xml_text, re.DOTALL):
            m = re.search(r'\balias\s*=\s*"([^"]*)"', attrs)
            if m and m.group(1).lower() == alias.lower():
                n = re.search(r'\bname\s*=\s*"([^"]*)"', attrs)
                if n:
                    host = n.group(1)
                i = re.search(r'\binsecure\s*=\s*"([^"]*)"', attrs)
                if i:
                    insecure = i.group(1) == "1"
                break
        if host is None:
            fail("alias '%s' not found in postedit.xml" % alias)
        url = "https://" + host + "/" + page.lstrip("/")
    if reshoot_given:
        url = opts["reshoot"]

    step("\u2022 alias   : %s%s%s" % (alias, "  (local framework mode)" if local_mode else "", "  (reshoot)" if reshoot_given else ""))
    step("\u2022 host    : %s%s" % (host, " (self-signed)" if insecure else ""))
    step("\u2022 url     : %s" % url)

    # ---- tab memory ------------------------------------------------------------
    tab_state_file = os.path.join(tempfile.gettempdir(), "wasql-workon-tab-%s.json" % sanitize(alias))
    remembered_tab_id = None
    if os.path.isfile(tab_state_file):
        try:
            with open(tab_state_file, encoding="utf-8") as f:
                decoded = json.load(f)
            if isinstance(decoded, dict) and decoded.get("id"):
                remembered_tab_id = decoded["id"]
        except Exception:
            pass

    # ---- 2. ensure the PostEdit watcher ---------------------------------------
    watcher_pid = None
    watcher_cmd = ""
    watcher_running_filters = None
    watcher_launch = None
    if local_mode:
        step("\u2022 watcher : n/a (local framework mode - no PostEdit)")
    else:
        if IS_WIN:
            ps = ("Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" "
                  "| Where-Object { $_.CommandLine -like '*postedit.php %s*' } "
                  '| ForEach-Object { "$($_.ProcessId)|$($_.CommandLine)" }') % alias
            ps_file = tempfile.NamedTemporaryFile(suffix=".ps1", delete=False, mode="w", encoding="utf-8")
            try:
                ps_file.write(ps)
                ps_file.close()
                res = subprocess.run(
                    ["powershell", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", ps_file.name],
                    capture_output=True, text=True, timeout=15,
                )
                text = res.stdout or ""
            except Exception:
                text = ""
            finally:
                try:
                    os.unlink(ps_file.name)
                except Exception:
                    pass
            m = re.search(r"^\s*(\d+)\|(.*)$", text, re.MULTILINE)
            if m:
                watcher_pid = int(m.group(1))
                watcher_cmd = m.group(2).strip()
        else:
            try:
                res = subprocess.run(["pgrep", "-af", "postedit.php %s" % alias], capture_output=True, text=True)
                text = res.stdout or ""
            except Exception:
                text = ""
            m = re.search(r"^\s*(\d+)\s+(.*)$", text, re.MULTILINE)
            if m:
                watcher_pid = int(m.group(1))
                watcher_cmd = m.group(2).strip()

        if watcher_pid:
            watcher_running_filters = running_filters(watcher_cmd, alias)
            step("\u2022 watcher : running (pid %s, filters: %s)" % (
                watcher_pid, "unknown" if watcher_running_filters is None else filter_label(watcher_running_filters)))
            if isinstance(watcher_running_filters, list) and watcher_running_filters != filters:
                step("  \u26a0  wanted filters: %s - the running watcher differs." % filter_label(filters))
                if filters and not watcher_running_filters:
                    step("     (it's watching everything, which still covers '%s')" % "','".join(filters))
                else:
                    step("     Stop it (Ctrl-C in its console) and re-run workon.py to re-filter.")
        elif not do_watch:
            step("\u2022 watcher : NOT running (left alone; --no-watcher)")
        else:
            php_bin = shutil.which("php") or "php"
            filter_args = (" " + " ".join(filters)) if filters else ""
            tab_why = None
            ok = False
            if IS_WIN:
                bat = os.path.join(tempfile.gettempdir(), "wasql-watch-%s.bat" % alias)
                with open(bat, "w", encoding="utf-8") as f:
                    f.write("@echo off\r\n")
                    f.write('cd /d "%s"\r\n' % ROOT)
                    f.write("title postedit-%s\r\n" % alias)
                    f.write('"%s" postedit\\postedit.php %s%s\r\n' % (php_bin, alias, filter_args))
                run_cmd = 'cmd /k "%s"' % bat
                if not use_tab:
                    tab_why = "--no-tab was given"
                else:
                    wt_path, tab_why = wt_exe(IS_WIN)
                    if wt_path:
                        ok, tab_why = launch_wt_tab(run_cmd, "postedit-" + alias, ROOT, do_chase)
                if not ok:
                    launch_detached(run_cmd, True, "postedit-" + alias)
                watcher_launch = "wt-tab" if ok else "window"
            else:
                launch_detached('"%s" %s/postedit/postedit.php %s%s' % (php_bin, ROOT, alias, filter_args), False)
                watcher_launch = "window"

            step("\u2022 watcher : launched for '%s' %s (filters: %s)" % (
                alias,
                ("in a new tab of THIS terminal window" + (" (focus kept here)" if do_chase else "")) if watcher_launch == "wt-tab" else "in its own console window",
                filter_label(filters),
            ))
            if watcher_launch == "window" and tab_why:
                step("     (no terminal tab: %s)" % tab_why)
            if filters:
                step("     Only records whose name contains '%s' are synced/watched" % "' or '".join(filters))
                step("     (that includes _templates - re-run with --no-filter to work on the template too).")
            step("  \u26a0  startup re-syncs from the DB: it backs up postEditFiles/%s to" % alias)
            step("     %s_bak, deletes the working files, and re-downloads them fresh." % alias)
            step("     Any un-synced local edits are in %s_bak." % alias)

    # ---- 3. ensure debug Chrome ------------------------------------------------
    matched_target = None
    confirmed = None
    chrome_up = False
    json_url = "http://127.0.0.1:%s/json" % port
    targets = None
    if no_chrome:
        step("\u2022 chrome  : skipped (--no-chrome - watcher status only)")
    else:
        if port_open("127.0.0.1", port):
            for _ in range(4):
                targets = http_get(json_url, 3)
                if targets is not None:
                    break
                time.sleep(0.5)
        chrome_up = targets is not None

        if chrome_up:
            step("\u2022 chrome  : debug instance already up on port %s" % port)
            matched_target = find_target(targets, host, remembered_tab_id)
            if not matched_target:
                step("\u2022 chrome  : opened new tab -> %s" % url if new_tab(json_url, url)
                     else "\u2022 chrome  : could NOT open a new tab (tried PUT, GET and curl on /json/new)")
            else:
                step("\u2022 chrome  : existing tab already on %s%s" % (
                    host, " (this session's remembered tab)" if remembered_tab_id and matched_target.get("id") == remembered_tab_id else ""))
        else:
            chrome = opts.get("chrome") if opts.get("chrome") is not True else None
            if chrome is None:
                if IS_WIN:
                    for c in (r"C:\Program Files\Google\Chrome\Application\chrome.exe",
                              r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"):
                        if os.path.isfile(c):
                            chrome = c
                            break
                    if chrome is None:
                        try:
                            reg = subprocess.run(
                                ["reg", "query", r"HKLM\SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe", "/ve"],
                                capture_output=True, text=True,
                            )
                            m = re.search(r"REG_SZ\s+(.+\.exe)", reg.stdout, re.IGNORECASE)
                            if m:
                                chrome = m.group(1).strip()
                        except Exception:
                            pass
                else:
                    for c in ("/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
                              "/usr/bin/google-chrome", "/usr/bin/chromium", "/usr/bin/chromium-browser"):
                        if os.path.isfile(c):
                            chrome = c
                            break
            if chrome is None:
                fail("could not locate the Chrome executable (pass --chrome=PATH)")
            if not os.path.isdir(profile):
                try:
                    os.makedirs(profile)
                except Exception:
                    pass

            args = ('--remote-debugging-port=%s --user-data-dir="%s" '
                    '--no-first-run --no-default-browser-check --new-window "%s"') % (port, profile, url)
            launch_detached('"%s" %s' % (chrome, args), IS_WIN, "wasql-chrome")
            step("\u2022 chrome  : launched debug instance (port %s, profile %s)" % (port, profile))

        # ---- 4. confirm the page target ------------------------------------------
        confirmed = False
        deadline = time.time() + 20
        retried = False
        while time.time() < deadline:
            targets = http_get(json_url)
            matched_target = find_target(targets, host, remembered_tab_id)
            if matched_target:
                confirmed = True
                break
            if not retried and targets is not None and time.time() > deadline - 12:
                retried = True
                new_tab(json_url, url)
            time.sleep(0.25)
        step("\u2022 target  : confirmed on port %s \u2713" % port if confirmed else "\u2022 target  : NOT confirmed after wait \u2717")
        if confirmed and matched_target and matched_target.get("id"):
            try:
                with open(tab_state_file, "w", encoding="utf-8") as f:
                    json.dump({"id": matched_target["id"], "url": matched_target.get("url")}, f)
            except Exception:
                pass
            step("\u2022 tab id  : %s (remembered in %s)" % (matched_target["id"], tab_state_file))
        if not confirmed:
            try:
                lst = json.loads(targets) if targets else None
            except Exception:
                lst = None
            if not isinstance(lst, list):
                step("     debug port %s is not answering - Chrome may still be booting." % port)
            else:
                pages = [t.get("url") for t in lst if isinstance(t, dict) and re.match(r"^https?:", str(t.get("url", "")), re.IGNORECASE)]
                step("     open tabs: " + "\n                ".join(pages) if pages else "     no http(s) tabs are open in the debug instance.")

    # ---- 5. optional screenshot ----------------------------------------------
    shot_written = None
    if do_shot and confirmed:
        shot_dir = os.path.dirname(shot_out)
        if shot_dir and shot_dir != "." and not os.path.isdir(shot_dir):
            try:
                os.makedirs(shot_dir)
            except Exception:
                pass
        if shot_dir and shot_dir != "." and not os.path.isdir(shot_dir):
            step("\u2022 shot    : cannot create directory %s" % shot_dir)
            do_shot = False

    if do_shot and confirmed:
        if IS_WIN:
            nc = r"C:\Program Files\nodejs\node.exe"
            node = nc if os.path.isfile(nc) else "node"
        else:
            node = "node"

        shot_js = os.path.join(tempfile.gettempdir(), "wasql_shot.js")
        with open(shot_js, "w", encoding="utf-8") as f:
            f.write(r"""const [,, PORT, URL, OUT, W, TARGET_ID] = process.argv;
const width = parseInt(W || '390', 10);
const fs = require('fs');
async function main() {
  const list = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
  const page = (TARGET_ID && list.find(t => t.id === TARGET_ID))
    || list.find(t => t.type === 'page' && t.webSocketDebuggerUrl && /https?:/.test(t.url));
  const ws = new WebSocket(page.webSocketDebuggerUrl.replace('localhost', '127.0.0.1'));
  let id = 0; const pending = new Map();
  const send = (m, p = {}) => new Promise(res => { const i = ++id; pending.set(i, res); ws.send(JSON.stringify({ id: i, method: m, params: p })); });
  ws.addEventListener('message', ev => { const msg = JSON.parse(ev.data); if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result); pending.delete(msg.id); } });
  await new Promise(res => ws.addEventListener('open', res));
  const metrics = w => send('Emulation.setDeviceMetricsOverride', { width: w, height: 844, deviceScaleFactor: 2, mobile: true });
  await send('Page.enable'); await send('Runtime.enable');
  await metrics(width);
  const loaded = new Promise(res => {
    const onMsg = ev => {
      const msg = JSON.parse(ev.data);
      if (msg.method === 'Page.loadEventFired') { ws.removeEventListener('message', onMsg); res(); }
    };
    ws.addEventListener('message', onMsg);
  });
  await send('Page.navigate', { url: URL });
  await Promise.race([loaded, new Promise(r => setTimeout(r, 8000))]);
  await new Promise(r => setTimeout(r, 300));
  await metrics(width + 1); await new Promise(r => setTimeout(r, 200));
  await metrics(width);     await new Promise(r => setTimeout(r, 400));
  const h = await send('Runtime.evaluate', { expression: 'document.documentElement.scrollHeight', returnByValue: true });
  const height = Math.min(h.result.value, 4000);
  const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width, height, scale: 1 } });
  fs.writeFileSync(OUT, Buffer.from(shot.data, 'base64'));
  await send('Emulation.clearDeviceMetricsOverride');
  console.log('wrote', OUT, 'width', width, 'height', height);
  ws.close();
}
main().catch(e => { console.error(e); process.exit(1); });
""")
        shot_url = url + ("?cb=1" if "?" not in url else "&cb=1")
        cmd = [node, shot_js, str(port), shot_url, shot_out, str(width)]
        if matched_target and matched_target.get("id"):
            cmd.append(matched_target["id"])
        try:
            res = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
            so = (res.stdout or "") + (res.stderr or "")
        except Exception as e:
            so = str(e)
        if os.path.isfile(shot_out):
            shot_written = shot_out
            step("\u2022 shot    : %s" % shot_out)
        else:
            step("\u2022 shot    : failed (%s)" % so.strip())

    # ---- 6. mirror inventory --------------------------------------------------
    inventory = {"page": [], "others": [], "truncated": 0, "root": None}
    inv_max = int_opt(opts, "inv-max", 40)
    if not local_mode and "no-inventory" not in opts and not reshoot_given and not no_chrome:
        mirror = os.path.join(ROOT, "postedit", "postEditFiles", alias)
        inventory["root"] = mirror
        step("")
        step("\u2022 mirror  : %s" % mirror)
        if not os.path.isdir(mirror):
            step("     (nothing on disk yet - the watcher may still be re-syncing)")
        else:
            token = filter_token(page) if page_given else ""

            def collect(d):
                found = []
                try:
                    entries = os.listdir(d)
                except Exception:
                    entries = []
                for e in entries:
                    if e in (".", "..", ".claude"):
                        continue
                    p = os.path.join(d, e)
                    if os.path.isdir(p):
                        found.extend(collect(p))
                    elif os.path.isfile(p):
                        found.append(p)
                return found

            wait_until = time.time() + (0 if (watcher_pid or not do_watch) else 15)
            recs = {}
            while True:
                recs = {}
                for f in collect(mirror):
                    m = re.match(r"^(.+)\.([^.]+)\.([^.]+)\.(\d+)\.[^.]+$", os.path.basename(f))
                    if not m:
                        continue
                    name, table, field, rid = m.group(1), m.group(2), m.group(3), m.group(4)
                    key = table + "/" + name
                    if key not in recs:
                        recs[key] = {"table": table, "name": name, "id": rid, "files": {}}
                    try:
                        size = os.path.getsize(f)
                    except Exception:
                        size = 0
                    recs[key]["files"][field] = {"path": f, "size": size}
                hit = any(token != "" and r["name"].lower() == token.lower() for r in recs.values())
                if hit or token == "" or time.time() >= wait_until:
                    break
                time.sleep(0.5)

            page_recs = [recs[k] for k in sorted(recs) if token != "" and recs[k]["name"].lower() == token.lower()]
            inventory["page"] = page_recs
            for r in page_recs:
                step("     %s: %s  (id %s)" % (r["table"], r["name"], r["id"]))
                for field in sorted(r["files"]):
                    info = r["files"][field]
                    step("       %-12s %7d bytes  %s" % (field, info["size"], info["path"]))
            if token != "" and not page_recs:
                step("     \u26a0  no record named '%s' on disk yet." % token)

            page_keys = {(r["table"], r["name"]) for r in page_recs}
            others = [recs[k] for k in sorted(recs) if (recs[k]["table"], recs[k]["name"]) not in page_keys]
            inventory["others"] = others
            if others:
                step("     other records on disk (%d):" % len(others))
                shown = others[:inv_max]
                for r in shown:
                    step("       %s/%s (id %s) [%s]" % (r["table"], r["name"], r["id"], ",".join(r["files"].keys())))
                inventory["truncated"] = len(others) - len(shown)
                if inventory["truncated"] > 0:
                    step("       ... and %d more not listed (--inv-max=N to raise, --no-inventory to skip)" % inventory["truncated"])

    # ---- summary --------------------------------------------------------------
    step("")
    if local_mode:
        step("Ready. Local framework mode - edit repo files directly. Optionally resolve 'localhost' to a db_id via the wamcp `databases` tool.")
    else:
        step("Ready. wamcp has no setdb/session-default database - resolve '%s' to a\n"
             "  db_id via the wamcp `databases` tool, then pass that db_id on every wamcp call.\n"
             "  (the wamcp id often differs from the postedit alias - e.g. '%s_mysql';\n"
             "   if '%s' isn't found, list ids with the wamcp `databases` tool.)" % (alias, alias, alias))
    if not no_chrome:
        step("Then read the screenshot" + ((" at:\n  %s" % shot_written) if shot_written else " (re-run with --shot=PATH)."))
    if LOGFILE:
        step("Output copied to: %s" % LOGFILE)

    if JSON_MODE:
        out(json.dumps({
            "alias": alias, "host": host, "page": page, "url": url,
            "port": port, "insecure": insecure,
            "chrome_up": bool(chrome_up or confirmed),
            "watcher_pid": watcher_pid, "watcher_launched": bool(not watcher_pid and do_watch and not local_mode),
            "watcher_launch": watcher_launch,
            "filters": filters, "watcher_running_filters": watcher_running_filters,
            "target_confirmed": confirmed, "chrome_skipped": no_chrome, "shot": shot_written,
            "tab_id": (matched_target.get("id") if matched_target else None), "tab_state_file": tab_state_file,
            "log": LOGFILE, "inventory": inventory,
        }))

    sys.exit(0 if (no_chrome or confirmed) else 2)


if __name__ == "__main__":
    main()
