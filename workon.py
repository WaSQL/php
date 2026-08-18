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
  3. (opt-in: --browse/--shot/--reshoot) Ensure a debug browser is up on the
     debug port and showing the page. Chrome by default; --browser=firefox
     drives a resident broker instead (Firefox allows only one BiDi session per
     process, ever). SKIPPED BY DEFAULT: launching + confirming a browser is the
     slowest part of startup and the developer usually already has the page
     open, so a plain run does the watcher/inventory work only.
  4. (same opt-in) Confirm the page's browser target exists (retry once for
     Chrome, else print the open tabs; poll + resolve via the broker for Firefox).
  5. (optional) Capture a mobile screenshot with a 1px reflow nudge - Chrome
     via a short-lived Node+CDP helper, Firefox via the broker.
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
  3. ONLY WITH --browse (or --shot / --reshoot, which imply it): ensures a
     debug browser is up on the debug port and showing the page. Without one of
     those flags NO browser is launched, contacted or confirmed - the run
     assumes the page is already open in front of you, which is what makes a
     plain startup cheap. Ask for --browse when you actually need to look.
     Chrome/Edge (Edge is Chromium-based - identical CDP flow, different exe):
     reuses an already-running debug instance (opens a tab in it via PUT on
     /json/new, with GET + curl fallbacks); only launches the browser if none.
     Firefox (--browser=firefox): launches Firefox + a resident Node "broker"
     process that holds Firefox's ONE-per-process WebDriver BiDi session (see
     the Firefox section below) and reuses both if already up.
  4. (same opt-in as 3) Confirms the page's browser target exists. Chrome/Edge: retries the
     tab-open once, and if it still fails, PRINTS THE OPEN TABS so you can
     diagnose without a curl. Firefox: polls the broker until its session is
     ready, then asks it to resolve (find-or-create) the tab. Either way the
     confirmed tab/context id is remembered per alias (temp/wasql-workon-tab-
     {alias}.json) so a re-run for the same alias reliably comes back to the
     SAME tab even when other "work on" sessions have other tabs open in the
     same shared debug instance - screenshotting targets that exact id instead
     of guessing "the first page tab" in the whole instance.
  5. Optional mobile screenshot, with the 1px reflow nudge baked in.
     Chrome/Edge: a short-lived Node+CDP helper. Firefox: a POST to the broker (see below
     for why Firefox can't use a short-lived-script approach). Creates the
     --shot parent directory if it doesn't exist either way.
  6. Mirror inventory: the named page's record id and the full local path +
     size of every one of its fields (body/controller/functions/css/js), then
     the other records on disk with their ids. Read the field you need straight
     from this - it replaces the find/ls over postEditFiles.

FIREFOX SUPPORT (--browser=firefox)
  Firefox's remote protocol (WebDriver BiDi) allows exactly ONE session per
  browser process, EVER - a second session.new after the first is abandoned
  (crash, Ctrl-C, a timeout) permanently wedges the process; no BiDi call from
  a fresh connection can recover it, only killing the process can. So unlike
  Chrome - where any number of short-lived debugger connections can come and go
  freely, which is what the Chrome screenshot helper relies on - Firefox is
  driven through a RESIDENT broker (temp/wasql_ff_broker.js, launched once and
  left running for the life of the debug Firefox instance) that holds the one
  session and answers plain HTTP calls from this script instead. Ending the
  broker's session cleanly (Ctrl-C, or --ff-shutdown) is the only shutdown path
  that doesn't wedge the process - see --ff-shutdown below.

ARGUMENTS
  <alias>            postedit.xml alias (e.g. dexpdq), or 'wasql' for local mode
  [page]             page/record to open and filter on (default: index, or
                     php/admin.php in local mode)

OPTIONS
  --page=NAME        page to open (overrides the positional arg)
  --browser=NAME     'chrome', 'firefox', or 'edge' for this run only.
                     Precedence: --browser > WASQL_BROWSER env var > the
                     persisted default (--set-default) > your OS's own
                     default browser (auto-detected - unless it's something
                     else entirely, e.g. Safari, in which case: 'chrome'.
  --set-default=NAME persist 'chrome'/'firefox'/'edge' as your default browser
                     (writes {HOME}/.wasql-browser) and exit - no alias needed.
  --port=N           Chrome/Edge debug port (default: 9222) - both are CDP,
                     so this one flag covers either.
  --chrome=PATH      explicit Chrome executable
  --edge=PATH        explicit Edge executable (Edge is Chromium-based - same
                     CDP flow as Chrome, just a different exe/profile/port
                     default; no Firefox-style broker needed)
  --firefox=PATH     explicit Firefox executable
  --ff-port=N        Firefox WebDriver BiDi port (default: 9333)
  --ff-broker-port=N Firefox broker's own HTTP control port (default: 9334)
  --ff-shutdown      cleanly end the Firefox broker's session and exit the
                     broker (the only non-wedging shutdown path) - no alias
                     needed. Uses --ff-broker-port if given.
  --profile=PATH     browser profile dir (default: temp/wasql-chrome-debug,
                     temp/wasql-firefox-debug, or temp/wasql-edge-debug,
                     matching --browser)
  --browse           launch/confirm the debug browser on the page (steps 3+4).
                     OFF BY DEFAULT - a plain run assumes the browser is
                     already open. --open is a synonym. Implied by --shot and
                     --reshoot, so you never need both.
  --open             synonym for --browse
  --shot[=PATH]      write a screenshot PNG (bare --shot -> a temp path).
                     Implies --browse.
  --no-shot          never screenshot (the default when --shot is absent)
  --width=N          screenshot viewport width (default: 390 = mobile)
  --reshoot=URL      lightweight follow-up shot during an already-open session:
                     reuse the remembered tab (no watcher launch, no inventory)
                     and navigate+screenshot URL. Implies a shot even without
                     --shot (falls back to a temp path). Use this instead of a
                     standalone screenshot script for "did my edit work" checks
                     after the initial "work on" call.
  --no-chrome        pure alias/watcher status check: no browser (already the
                     default) AND no mirror inventory - just "is the watcher
                     still running?". Overrides --browse/--shot. Mutually
                     exclusive with --reshoot. --no-browser is a clearer synonym.
  --no-browser       synonym for --no-chrome (browser-agnostic name)
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
  * Firefox only allows ONE BiDi session per process, ever - see the FIREFOX
    SUPPORT section above. Always prefer --ff-shutdown / Ctrl-C on the broker
    over killing Firefox directly; an unclean kill still needs a manual
    taskkill + relaunch (there's no API to un-wedge a session from outside).
  * Exit code: 0 = target confirmed (or no browser was asked for), 2 = a
    browser WAS asked for but its target could not be confirmed, 1 = usage/setup
    error.
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


def http_get_json(url, timeout=2):
    """GET + json.loads in one call, for the Firefox broker's JSON HTTP API."""
    body = http_get(url, timeout)
    if body is None:
        return None
    try:
        return json.loads(body)
    except Exception:
        return None


def http_post_json(url, body_dict, timeout=15):
    """POST a JSON body to the Firefox broker and json.loads the response.
    timeout bounds the whole call - the broker's own per-BiDi-call timeouts are
    shorter, but this is the backstop if the broker process itself is wedged/gone."""
    data = json.dumps(body_dict or {}).encode("utf-8")
    req = urllib.request.Request(url, data=data, method="POST",
                                  headers={"Content-Type": "application/json", "Connection": "close"})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            body = resp.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        try:
            body = e.read().decode("utf-8", "replace")
        except Exception:
            return None
    except Exception:
        return None
    try:
        return json.loads(body)
    except Exception:
        return None


def wasql_valid_browsers():
    """Valid --browser / --set-default values. Edge is Chromium-based and
    shares Chrome's entire CDP code path (see step 3+4 in main()) - it's a
    third value here, not a third implementation."""
    return ("chrome", "firefox", "edge")


def wasql_browser_pref_file():
    """Path to the persisted default-browser preference file - a standing user
    setting, so it lives next to the user's profile, not in the sweepable temp dir."""
    home = os.environ.get("USERPROFILE") or os.environ.get("HOME") or tempfile.gettempdir()
    return os.path.join(home, ".wasql-browser")


def detect_os_default_browser(is_win):
    """Best-effort detection of the OS's own default web browser, mapped to one
    of wasql_valid_browsers(). Returns None (not chrome) when it can't be
    determined, so callers can fall through to the hardcoded default instead
    of guessing.

    Windows: the UserChoice registry key is the same place Explorer/Settings
    reads "Default apps" from - ProgId contains the browser's identity
    ('ChromeHTML', 'FirefoxURL-xxxx', 'MSEdgeHTM', etc.), so a substring match
    is enough without hardcoding every ProgId variant a browser has shipped.
    Linux: xdg-settings is the freedesktop.org standard for this. macOS has no
    equally simple CLI query, so it's left undetected here (falls through)."""
    if is_win:
        try:
            res = subprocess.run(
                ["reg", "query", r"HKCU\Software\Microsoft\Windows\Shell\Associations\UrlAssociations\http\UserChoice", "/v", "ProgId"],
                capture_output=True, text=True,
            )
            text = res.stdout or ""
        except Exception:
            text = ""
        m = re.search(r"ProgId\s+REG_SZ\s+(\S+)", text, re.IGNORECASE)
        if m:
            prog_id = m.group(1).lower()
            if "firefox" in prog_id:
                return "firefox"
            if "edge" in prog_id:
                return "edge"
            if "chrome" in prog_id:
                return "chrome"
        return None
    try:
        res = subprocess.run(["xdg-settings", "get", "default-web-browser"], capture_output=True, text=True)
        text = (res.stdout or "").strip().lower()
    except Exception:
        text = ""
    if text:
        if "firefox" in text:
            return "firefox"
        if "edge" in text:
            return "edge"
        if "chrome" in text:
            return "chrome"
    return None


def resolve_browser_pref(opts, is_win):
    """Resolve which browser to drive this run, high to low precedence:
    1. --browser=chrome|firefox|edge (this run only)
    2. WASQL_BROWSER env var
    3. the persisted default (see --set-default / wasql_browser_pref_file())
    4. the OS's own default browser, when it's one wasql_valid_browsers()
       recognizes (see detect_os_default_browser()) - "unless specified" is
       the whole point of this tier being LAST: it only ever applies once none
       of 1-3 said anything, so an explicit choice always wins.
    5. hardcoded 'chrome' - so nothing changes for existing callers on a
       machine where the OS default can't be determined or isn't one of these.
    """
    valid = wasql_valid_browsers()
    if "browser" in opts and opts["browser"] is not True:
        b = str(opts["browser"]).strip().lower()
        if b not in valid:
            fail("--browser must be one of: %s (got '%s')" % (", ".join(valid), b))
        return b
    env = os.environ.get("WASQL_BROWSER")
    if env:
        env = env.strip().lower()
        if env in valid:
            return env
    pref_file = wasql_browser_pref_file()
    if os.path.isfile(pref_file):
        try:
            with open(pref_file, encoding="utf-8") as f:
                saved = f.read().strip().lower()
            if saved in valid:
                return saved
        except Exception:
            pass
    detected = detect_os_default_browser(is_win)
    if detected is not None:
        return detected
    return "chrome"


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
    # Standalone maintenance actions - neither needs an alias, so handle both
    # before the "no alias given" check below.
    if "set-default" in opts:
        if opts["set-default"] is True:
            fail("--set-default needs a value: %s" % ", ".join(wasql_valid_browsers()))
        b = str(opts["set-default"]).strip().lower()
        if b not in wasql_valid_browsers():
            fail("--set-default must be one of: %s (got '%s')" % (", ".join(wasql_valid_browsers()), b))
        pref_file = wasql_browser_pref_file()
        try:
            with open(pref_file, "w", encoding="utf-8") as f:
                f.write(b)
        except Exception:
            fail("could not write default browser to %s" % pref_file)
        out("Default browser set to '%s' (%s)" % (b, pref_file))
        sys.exit(0)
    if "ff-shutdown" in opts:
        ff_broker_port_arg = int_opt(opts, "ff-broker-port", 9334)
        result = http_post_json("http://127.0.0.1:%s/shutdown" % ff_broker_port_arg, {}, 10)
        if isinstance(result, dict) and result.get("ok"):
            out("Firefox broker shut down cleanly (port %s)." % ff_broker_port_arg)
            sys.exit(0)
        fail("could not reach the Firefox broker on port %s to shut it down (already stopped?)" % ff_broker_port_arg)
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

    browser = resolve_browser_pref(opts, IS_WIN)
    port = int_opt(opts, "port", 9222)              # Chrome debug port
    ff_port = int_opt(opts, "ff-port", 9333)         # Firefox BiDi port
    ff_broker_port = int_opt(opts, "ff-broker-port", 9334)
    width = int_opt(opts, "width", 390)

    reshoot_given = "reshoot" in opts and opts["reshoot"] is not True
    if "reshoot" in opts and not reshoot_given:
        fail("--reshoot needs a URL, e.g. --reshoot=https://host/page")
    # --no-browser is a clearer name now that Chrome isn't the only option;
    # --no-chrome stays as the original, still-working name. It now means
    # "status check only" (no browser AND no inventory), since skipping the
    # browser is the default rather than something you have to ask for.
    no_browser_given = "no-chrome" in opts or "no-browser" in opts
    if no_browser_given and reshoot_given:
        fail("--no-chrome/--no-browser and --reshoot are contradictory (reshoot needs a browser).")
    # The browser is OPT-IN: launching it and confirming its target is the
    # slowest part of startup, and the developer normally already has the page
    # open. So a plain run does watcher + inventory only, and the browser steps
    # happen when they're actually asked for - explicitly (--browse/--open) or
    # implicitly by asking for a picture (--shot/--reshoot).
    want_browser = "browse" in opts or "open" in opts or "shot" in opts or reshoot_given
    no_chrome = no_browser_given or not want_browser
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
    profile_defaults = {"chrome": "wasql-chrome-debug", "firefox": "wasql-firefox-debug", "edge": "wasql-edge-debug"}
    default_profile_name = profile_defaults[browser]
    profile = opts.get("profile", os.path.join(tempfile.gettempdir(), default_profile_name))
    if profile is True:
        profile = os.path.join(tempfile.gettempdir(), default_profile_name)

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

    # ---- 3+4. ensure debug browser + confirm target ---------------------------
    matched_target = None
    confirmed = None
    chrome_up = False       # "the debug browser was already up" (kept named for JSON-field compat)
    json_url = "http://127.0.0.1:%s/json" % port
    targets = None
    ff_broker_url = "http://127.0.0.1:%s" % ff_broker_port
    if no_chrome:
        step("\u2022 browser : skipped (--no-chrome/--no-browser - watcher status only)"
             if no_browser_given else
             "\u2022 browser : not launched (default - assuming it's already open; add --browse to launch/confirm it, or --shot=PATH to also capture it)")
    elif browser == "firefox":
        # See workon_firefox.md / usage()'s FIREFOX SUPPORT section: Firefox
        # allows exactly ONE BiDi session per process, ever, so a resident
        # broker holds it and this script talks to the broker over plain HTTP
        # instead of driving Firefox directly per call.
        status = http_get_json("%s/status" % ff_broker_url, 2)
        chrome_up = isinstance(status, dict)
        if chrome_up and status.get("ready"):
            step("\u2022 firefox : broker already up (port %s, session %s)" % (ff_broker_port, status.get("sessionId")))
        elif chrome_up:
            step("\u2022 firefox : broker already running but not ready (%s)" % (status.get("error") or "still connecting"))
        else:
            # Nothing answering on the broker port - launch Firefox + the broker fresh.
            firefox = opts.get("firefox") if opts.get("firefox") is not True else None
            if firefox is None:
                if IS_WIN:
                    for c in (r"C:\Program Files\Mozilla Firefox\firefox.exe",
                              r"C:\Program Files (x86)\Mozilla Firefox\firefox.exe"):
                        if os.path.isfile(c):
                            firefox = c
                            break
                    if firefox is None:
                        try:
                            reg = subprocess.run(
                                ["reg", "query", r"HKLM\SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\firefox.exe", "/ve"],
                                capture_output=True, text=True,
                            )
                            m = re.search(r"REG_SZ\s+(.+\.exe)", reg.stdout, re.IGNORECASE)
                            if m:
                                firefox = m.group(1).strip()
                        except Exception:
                            pass
                else:
                    for c in ("/Applications/Firefox.app/Contents/MacOS/firefox", "/usr/bin/firefox"):
                        if os.path.isfile(c):
                            firefox = c
                            break
            if firefox is None:
                fail("could not locate the Firefox executable (pass --firefox=PATH)")
            if not os.path.isdir(profile):
                try:
                    os.makedirs(profile)
                except Exception:
                    pass

            ff_args = '-profile "%s" -no-remote --remote-debugging-port=%s -new-window "%s"' % (profile, ff_port, url)
            launch_detached('"%s" %s' % (firefox, ff_args), IS_WIN, "wasql-firefox")
            step("\u2022 firefox : launched debug instance (port %s, profile %s)" % (ff_port, profile))

            # Write + launch the resident broker. Content mirrors the prototype
            # verified live against Firefox 153 - see workon_firefox.md.
            broker_js = os.path.join(tempfile.gettempdir(), "wasql_ff_broker.js")
            with open(broker_js, "w", encoding="utf-8") as f:
                f.write(r"""// wasql_ff_broker.js - resident BiDi session holder + HTTP control API, the
// Firefox equivalent of Chrome's short-lived wasql_shot.js.
//
// WHY THIS EXISTS (see workon_firefox.md): Firefox's WebDriver BiDi allows
// exactly ONE session per browser process, ever. A second session.new after
// the first is abandoned (crash, Ctrl-C, a sandboxed tool's own timeout - all
// realistic for a per-call script) permanently wedges the process; no BiDi
// call from a fresh connection can recover it, only killing the process can.
// So unlike Chrome - where any number of short-lived debugger connections can
// come and go freely - this process holds the ONE session for the life of the
// debug Firefox instance, and workon.php/workon.py talk to it over plain HTTP
// instead of spawning a fresh Node process per screenshot.
//
// Usage: node wasql_ff_broker.js <brokerHttpPort> <firefoxBidiPort> [logFile]
const http = require('http');
const fs = require('fs');

const [, , BROKER_PORT_ARG, FF_PORT_ARG, LOG_FILE] = process.argv;
const BROKER_PORT = parseInt(BROKER_PORT_ARG, 10);
const FF_PORT = parseInt(FF_PORT_ARG, 10);

function log(msg) {
	const line = '[' + new Date().toISOString() + '] ' + msg;
	if (LOG_FILE) { try { fs.appendFileSync(LOG_FILE, line + '\n'); } catch (_) {} }
}

let ws = null;
let msgId = 0;
const pending = new Map();
let sessionId = null;
let ready = false;
let fatalError = null;

/** Send one BiDi command over the held session; rejects on timeout so a stuck
 * Firefox call surfaces as an HTTP error instead of hanging the caller forever
 * (the exact class of bug just fixed in the Chrome path's shot.js/shell_exec). */
function send(method, params, timeoutMs) {
	params = params || {};
	timeoutMs = timeoutMs || 15000;
	return new Promise(function (resolve, reject) {
		if (!ws || ws.readyState !== 1) { reject(new Error('not connected to Firefox BiDi')); return; }
		const i = ++msgId;
		const timer = setTimeout(function () {
			pending.delete(i);
			reject(new Error(method + ' timed out after ' + timeoutMs + 'ms'));
		}, timeoutMs);
		pending.set(i, function (msg) {
			clearTimeout(timer);
			if (msg.type === 'error' || msg.error) { reject(new Error(msg.message || JSON.stringify(msg))); }
			else { resolve(msg.result); }
		});
		ws.send(JSON.stringify({ id: i, method: method, params: params }));
	});
}

/** Connect to the Firefox BiDi endpoint and claim the ONE session, retrying
 * for a while since Firefox may still be starting up. Called exactly once. */
async function connectWithRetry() {
	const deadline = Date.now() + 20000;
	let lastErr = null;
	while (Date.now() < deadline) {
		try {
			ws = await new Promise(function (resolve, reject) {
				const sock = new WebSocket('ws://127.0.0.1:' + FF_PORT + '/session');
				sock.addEventListener('open', function () { resolve(sock); }, { once: true });
				sock.addEventListener('error', function () { reject(new Error('ws connect failed')); }, { once: true });
			});
			ws.addEventListener('message', function (ev) {
				const msg = JSON.parse(ev.data);
				if (msg.id && pending.has(msg.id)) {
					const cb = pending.get(msg.id);
					pending.delete(msg.id);
					cb(msg);
				}
			});
			ws.addEventListener('close', function () {
				// A close AFTER we were ready means Firefox itself went away (closed by
				// the user, crashed, killed) - not recoverable from here either way.
				if (ready) { fatalError = 'Firefox BiDi connection closed unexpectedly'; ready = false; }
			});
			const result = await send('session.new', { capabilities: {} }, 10000);
			sessionId = result.sessionId;
			ready = true;
			log('session.new OK, sessionId=' + sessionId);
			return;
		} catch (e) {
			lastErr = e;
			log('connect attempt failed: ' + e.message);
			if (ws) { try { ws.close(); } catch (_) {} ws = null; }
			await new Promise(function (r) { setTimeout(r, 500); });
		}
	}
	fatalError = 'could not establish a Firefox BiDi session: ' + (lastErr ? lastErr.message : 'unknown error');
	log('FATAL: ' + fatalError);
}

/** Flatten browsingContext.getTree's nested contexts and find a match: the
 * remembered id first (exact re-entry into the same tab across runs, same
 * reasoning as workon.php's findTarget()), else the first context whose url
 * contains $host. */
function findContext(tree, host, rememberedId) {
	const flat = [];
	(function walk(list) {
		for (const c of list) { flat.push(c); if (c.children && c.children.length) { walk(c.children); } }
	})(tree.contexts || []);
	if (rememberedId) {
		const m = flat.find(function (c) { return c.context === rememberedId; });
		if (m) { return m; }
	}
	return flat.find(function (c) { return c.url && c.url.indexOf(host) !== -1; }) || null;
}

async function handleResolve(body) {
	const host = body.host, url = body.url, rememberedId = body.rememberedId;
	const tree = await send('browsingContext.getTree', {});
	let ctx = findContext(tree, host, rememberedId);
	let created = false;
	if (!ctx) {
		const made = await send('browsingContext.create', { type: 'tab' });
		await send('browsingContext.navigate', { context: made.context, url: url, wait: 'complete' });
		ctx = { context: made.context, url: url };
		created = true;
	}
	return { context: ctx.context, url: ctx.url, created: created };
}

async function handleShot(body) {
	const context = body.context, url = body.url, out = body.out;
	const w = parseInt(body.width, 10) || 390;
	function setViewport(ww) {
		return send('browsingContext.setViewport', { context: context, viewport: { width: ww, height: 844 }, devicePixelRatio: 2 });
	}
	await setViewport(w);
	await send('browsingContext.navigate', { context: context, url: url, wait: 'complete' });
	await new Promise(function (r) { setTimeout(r, 300); });
	// 1px reflow nudge - same reasoning as the Chrome path: some layouts don't
	// settle correctly until the viewport actually changes once.
	await setViewport(w + 1); await new Promise(function (r) { setTimeout(r, 200); });
	await setViewport(w);     await new Promise(function (r) { setTimeout(r, 400); });
	const evalResult = await send('script.evaluate', {
		expression: 'document.documentElement.scrollHeight',
		target: { context: context }, awaitPromise: false, resultOwnership: 'none',
	});
	const height = Math.min((evalResult.result && evalResult.result.value) || 844, 4000);
	const shot = await send('browsingContext.captureScreenshot', {
		context: context, origin: 'document', format: { type: 'image/png' },
		clip: { type: 'box', x: 0, y: 0, width: w, height: height },
	});
	fs.writeFileSync(out, Buffer.from(shot.data, 'base64'));
	return { ok: true, out: out, width: w, height: height };
}

/** Best-effort clean session.end - the ONLY shutdown path that doesn't
 * permanently wedge the Firefox process for the next "work on" session. */
async function endSessionAndExit(code) {
	try { await send('session.end', {}, 5000); log('session.end OK'); }
	catch (e) { log('session.end failed (process may need a manual kill): ' + e.message); }
	process.exit(code);
}

process.on('SIGINT', function () { endSessionAndExit(0); });
process.on('SIGTERM', function () { endSessionAndExit(0); });

const server = http.createServer(function (req, res) {
	const chunks = [];
	req.on('data', function (c) { chunks.push(c); });
	req.on('end', async function () {
		let body = {};
		try { body = chunks.length ? JSON.parse(Buffer.concat(chunks).toString('utf8')) : {}; } catch (_) {}
		function respond(status, obj) {
			res.writeHead(status, { 'Content-Type': 'application/json' });
			res.end(JSON.stringify(obj));
		}
		try {
			if (req.method === 'GET' && req.url === '/status') {
				respond(200, { ready: ready, sessionId: sessionId, error: fatalError });
				return;
			}
			if (!ready) { respond(503, { ok: false, error: fatalError || 'not ready yet' }); return; }
			if (req.method === 'POST' && req.url === '/resolve') {
				respond(200, await handleResolve(body));
			} else if (req.method === 'POST' && req.url === '/shot') {
				respond(200, await handleShot(body));
			} else if (req.method === 'POST' && req.url === '/shutdown') {
				respond(200, { ok: true });
				setTimeout(function () { endSessionAndExit(0); }, 50);
			} else {
				respond(404, { ok: false, error: 'no such route' });
			}
		} catch (e) {
			respond(500, { ok: false, error: e.message });
		}
	});
});
server.listen(BROKER_PORT, '127.0.0.1', function () {
	log('broker listening on ' + BROKER_PORT + ', connecting to Firefox BiDi on ' + FF_PORT);
});

connectWithRetry();
""")
            if IS_WIN:
                nc = r"C:\Program Files\nodejs\node.exe"
                node = nc if os.path.isfile(nc) else "node"
            else:
                node = "node"
            broker_log = os.path.join(tempfile.gettempdir(), "wasql-ff-broker-%s.log" % sanitize(alias))
            try:
                with open(broker_log, "w", encoding="utf-8"):
                    pass
            except Exception:
                pass
            launch_detached('"%s" "%s" %s %s "%s"' % (node, broker_js, ff_broker_port, ff_port, broker_log),
                             IS_WIN, "wasql-ff-broker")
            step("\u2022 firefox : launched broker (port %s, log %s)" % (ff_broker_port, broker_log))

        # Poll the broker until its one-time session.new succeeds (or fails for good).
        deadline = time.time() + 20
        broker_ready = False
        while time.time() < deadline:
            status = http_get_json("%s/status" % ff_broker_url, 2)
            if isinstance(status, dict):
                if status.get("ready"):
                    broker_ready = True
                    break
                if status.get("error"):
                    step("\u2022 firefox : broker reported an error: %s" % status["error"])
                    break
            time.sleep(0.3)
        step("\u2022 firefox : broker ready \u2713" if broker_ready else "\u2022 firefox : broker NOT ready after wait \u2717")

        confirmed = False
        if broker_ready:
            resolved = http_post_json("%s/resolve" % ff_broker_url,
                                       {"host": host, "url": url, "rememberedId": remembered_tab_id}, 15)
            if isinstance(resolved, dict) and resolved.get("context"):
                matched_target = {"id": resolved["context"], "url": resolved.get("url")}
                confirmed = True
                step("\u2022 target  : confirmed via Firefox broker%s \u2713" % (
                    " (opened new tab)" if resolved.get("created") else " (reused existing tab)"))
            else:
                step("\u2022 target  : Firefox broker /resolve failed%s" % (
                    (" (%s)" % resolved.get("error", "unknown error")) if isinstance(resolved, dict) else " (no response)"))
        if confirmed and matched_target and matched_target.get("id"):
            try:
                with open(tab_state_file, "w", encoding="utf-8") as f:
                    json.dump({"id": matched_target["id"], "url": matched_target.get("url"), "browser": "firefox"}, f)
            except Exception:
                pass
            step("\u2022 tab id  : %s (remembered in %s)" % (matched_target["id"], tab_state_file))
    else:
        # Chrome and Edge share this whole branch: Edge is Chromium-based and
        # speaks the exact same CDP protocol (same /json endpoint, same
        # --remote-debugging-port flag) - only executable discovery differs,
        # so browser == "edge" just picks a different exe/option/registry key
        # below instead of a second implementation.
        browser_label = "edge" if browser == "edge" else "chrome"
        if port_open("127.0.0.1", port):
            for _ in range(4):
                targets = http_get(json_url, 3)
                if targets is not None:
                    break
                time.sleep(0.5)
        chrome_up = targets is not None

        if chrome_up:
            step("\u2022 %s  : debug instance already up on port %s" % (browser_label, port))
            matched_target = find_target(targets, host, remembered_tab_id)
            if not matched_target:
                step(("\u2022 %s  : opened new tab -> %s" % (browser_label, url)) if new_tab(json_url, url)
                     else "\u2022 %s  : could NOT open a new tab (tried PUT, GET and curl on /json/new)" % browser_label)
            else:
                step("\u2022 %s  : existing tab already on %s%s" % (
                    browser_label, host, " (this session's remembered tab)" if remembered_tab_id and matched_target.get("id") == remembered_tab_id else ""))
        else:
            exe_opt = "edge" if browser == "edge" else "chrome"
            browser_exe = opts.get(exe_opt) if opts.get(exe_opt) is not True else None
            if browser_exe is None:
                if IS_WIN:
                    cands = (r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
                             r"C:\Program Files\Microsoft\Edge\Application\msedge.exe") if browser == "edge" else (
                             r"C:\Program Files\Google\Chrome\Application\chrome.exe",
                             r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe")
                    for c in cands:
                        if os.path.isfile(c):
                            browser_exe = c
                            break
                    if browser_exe is None:
                        reg_key = "msedge.exe" if browser == "edge" else "chrome.exe"
                        try:
                            reg = subprocess.run(
                                ["reg", "query", r"HKLM\SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\%s" % reg_key, "/ve"],
                                capture_output=True, text=True,
                            )
                            m = re.search(r"REG_SZ\s+(.+\.exe)", reg.stdout, re.IGNORECASE)
                            if m:
                                browser_exe = m.group(1).strip()
                        except Exception:
                            pass
                else:
                    cands = ("/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge",
                             "/usr/bin/microsoft-edge", "/usr/bin/microsoft-edge-stable") if browser == "edge" else (
                             "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
                             "/usr/bin/google-chrome", "/usr/bin/chromium", "/usr/bin/chromium-browser")
                    for c in cands:
                        if os.path.isfile(c):
                            browser_exe = c
                            break
            if browser_exe is None:
                fail("could not locate the %s executable (pass --%s=PATH)" % (browser_label.capitalize(), exe_opt))
            if not os.path.isdir(profile):
                try:
                    os.makedirs(profile)
                except Exception:
                    pass

            args = ('--remote-debugging-port=%s --user-data-dir="%s" '
                    '--no-first-run --no-default-browser-check --new-window "%s"') % (port, profile, url)
            launch_detached('"%s" %s' % (browser_exe, args), IS_WIN, "wasql-" + browser_label)
            step("\u2022 %s  : launched debug instance (port %s, profile %s)" % (browser_label, port, profile))

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

    if do_shot and confirmed and browser == "firefox":
        # The broker already holds the session and does viewport+navigate+nudge+
        # scrollHeight+capture as one call - see /shot in the broker source above.
        shot_url = url + ("?cb=1" if "?" not in url else "&cb=1")
        result = http_post_json("%s/shot" % ff_broker_url,
                                 {"context": matched_target["id"], "url": shot_url, "width": width, "out": shot_out}, 30)
        if isinstance(result, dict) and result.get("ok") and os.path.isfile(shot_out):
            shot_written = shot_out
            step("\u2022 shot    : %s" % shot_out)
        else:
            step("\u2022 shot    : failed (%s)" % (
                (result.get("error", "unknown error") if isinstance(result, dict) else "no response from broker")))

    if do_shot and confirmed and browser != "firefox":
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
// Hard backstop: a stale/closed tab can leave the WebSocket connect (or any
// later CDP response) waiting on an event that never fires. Force an exit well
// inside the caller's own subprocess timeout instead of trusting every
// individual await to fail cleanly.
setTimeout(() => { console.error('timeout: CDP session did not complete in time'); process.exit(1); }, 20000);
async function main() {
  const list = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
  const page = (TARGET_ID && list.find(t => t.id === TARGET_ID))
    || list.find(t => t.type === 'page' && t.webSocketDebuggerUrl && /https?:/.test(t.url));
  if (!page) { throw new Error('no matching Chrome target found (tab may have closed)'); }
  const ws = new WebSocket(page.webSocketDebuggerUrl.replace('localhost', '127.0.0.1'));
  let id = 0; const pending = new Map();
  const send = (m, p = {}) => new Promise(res => { const i = ++id; pending.set(i, res); ws.send(JSON.stringify({ id: i, method: m, params: p })); });
  ws.addEventListener('message', ev => { const msg = JSON.parse(ev.data); if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result); pending.delete(msg.id); } });
  // A failed connect fires 'error'/'close', never 'open' - without the reject
  // branch this wait never settles and the 20s watchdog above was the only exit.
  await new Promise((res, rej) => {
    ws.addEventListener('open', res, { once: true });
    ws.addEventListener('error', () => rej(new Error('WebSocket failed to connect to ' + page.webSocketDebuggerUrl)), { once: true });
  });
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
main().then(() => process.exit(0)).catch(e => { console.error(e); process.exit(1); });
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
    # Gated on no_browser_given, not no_chrome: the inventory is the whole point
    # of a default (browser-less) run - only an explicit --no-chrome/--no-browser
    # status check skips it.
    if not local_mode and "no-inventory" not in opts and not reshoot_given and not no_browser_given:
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
    elif not no_browser_given:
        step("No browser was launched (the default). To look at the page, re-run with --browse,\n"
             "  or --shot=PATH to launch it and capture a screenshot too.")
    if LOGFILE:
        step("Output copied to: %s" % LOGFILE)

    if JSON_MODE:
        out(json.dumps({
            "alias": alias, "host": host, "page": page, "url": url,
            "browser": browser, "port": port, "insecure": insecure,
            "chrome_up": bool(chrome_up or confirmed),  # kept named for compat; means "browser was up"
            "watcher_pid": watcher_pid, "watcher_launched": bool(not watcher_pid and do_watch and not local_mode),
            "watcher_launch": watcher_launch,
            "filters": filters, "watcher_running_filters": watcher_running_filters,
            "target_confirmed": confirmed, "chrome_skipped": no_chrome,
            "browser_requested": bool(want_browser and not no_browser_given),  # False = default browser-less run
            "shot": shot_written,
            "tab_id": (matched_target.get("id") if matched_target else None), "tab_state_file": tab_state_file,
            "log": LOGFILE, "inventory": inventory,
        }))

    sys.exit(0 if (no_chrome or confirmed) else 2)


if __name__ == "__main__":
    main()
