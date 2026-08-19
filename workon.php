<?php
/**
 * workon.php - one-shot prep for "work on {site} {page}" PostEdit sessions.
 *
 * Does every step that would otherwise each trigger a separate permission
 * prompt, in a single process:
 *   1. Resolve {alias} -> host from postedit/postedit.xml (build the site URL).
 *   2. Ensure the PostEdit watcher for {alias} is running (launch if not),
 *      filtered to the named page so it only syncs/watches that page - in a new
 *      tab of the current Windows Terminal window when there is one. Done
 *      before the Chrome step so its background re-sync overlaps Chrome
 *      booting/confirming instead of running back-to-back with it.
 *   3. (opt-in: --browse/--shot/--reshoot) Ensure a debug browser is up on the
 *      debug port and showing the page (reuse an already-running debug
 *      instance; only launch if none). Chrome by default; --browser=firefox
 *      drives a resident broker instead (see the FIREFOX SUPPORT section in
 *      usage() - Firefox allows only one BiDi session per process, ever, so a
 *      per-call script like Chrome's would wedge it on any interrupted call).
 *      SKIPPED BY DEFAULT: launching + confirming a browser is the slowest,
 *      most expensive part of startup and the developer usually already has
 *      the page open, so a plain run assumes the browser is there and only
 *      does the watcher/inventory work. Ask for a browser explicitly (or
 *      implicitly, by asking for a screenshot) when you actually need to look.
 *   4. (same opt-in) Confirm the page's browser target exists (retrying the
 *      tab-open for Chrome, polling the broker + resolving for Firefox;
 *      printing the open Chrome tabs if it still fails).
 *   5. (optional) Capture a mobile screenshot with a 1px reflow nudge -
 *      Chrome via a short-lived Node+CDP helper, Firefox via the broker -
 *      creating the output directory if needed.
 *   6. Print a mirror inventory: the named page's record id and the local path
 *      + size of each of its fields, plus the other records on disk.
 *
 * It does NOT resolve a wamcp db_id or call any wamcp tool - wamcp has no
 * setdb/session-default database, so the assistant resolves {alias} to a
 * db_id via the wamcp `databases` tool itself and passes it on every wamcp
 * call. Everything else lives here so the whole routine is a single approval.
 *
 * Usage:
 *   php workon.php <alias> [page] [options]
 *
 * `php workon.php --help` prints the full capability + option reference; that
 * help text (see usage() below) is the single source of truth, so add new
 * options there rather than duplicating the list in this header.
 *
 * Exit code 0 on success, 2 if the page target could not be confirmed, 1 on a
 * usage/setup error.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$IS_WIN = (stripos(PHP_OS, 'WIN') === 0);
$ROOT   = __DIR__;                       // repo root (this file lives there)
$XML    = $ROOT . DIRECTORY_SEPARATOR . 'postedit' . DIRECTORY_SEPARATOR . 'postedit.xml';

// ---- tiny helpers ---------------------------------------------------------
// Every line is also appended to a per-alias log file. Callers are told never
// to pipe this script through a pager (detached children hold the pipe open and
// the output is never seen) - the log is the recovery path when that happens
// anyway, or when a caller redirects to a file it then can't find.
$LOGFILE = null;
function logLine($msg){
	global $LOGFILE;
	if($LOGFILE){ @file_put_contents($LOGFILE, $msg . PHP_EOL, FILE_APPEND); }
}
function out($msg){ fwrite(STDOUT, $msg . PHP_EOL); logLine($msg); }
function err($msg){ fwrite(STDERR, $msg . PHP_EOL); logLine($msg); }
function fail($msg){ err('ERROR: ' . $msg); exit(1); }

/** Full capability/option reference - `php workon.php --help`. */
function usage(){
	out(<<<'TXT'
workon.php - one-shot startup for a "work on {site} {page}" session.

  php workon.php <alias|wasql> [page] [options]

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
     Chrome/Edge: a short-lived Node+CDP helper script. Firefox: a POST to the
     broker (see below for why Firefox can't use a short-lived-script
     approach). Creates the --shot parent directory if it doesn't exist either way.
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

AFTERWARDS (the assistant does these - workon.php cannot)
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
TXT
	);
}

/**
 * GET a debug-endpoint URL, return body or null on failure.
 * Forces HTTP/1.1 so the Host header is sent - Chrome's DevTools endpoint
 * rejects Host-less HTTP/1.0 requests (DNS-rebinding protection), which
 * otherwise makes an already-running instance look absent.
 */
function httpGet($url, $timeout = 2){
	$ctx = stream_context_create(['http' => [
		'protocol_version' => 1.1,
		'timeout'          => $timeout,
		'ignore_errors'    => true,
		'header'           => "Connection: close\r\n",
	]]);
	$body = @file_get_contents($url, false, $ctx);
	return ($body === false) ? null : $body;
}

/** GET + json_decode in one call, for the Firefox broker's JSON HTTP API. */
function httpGetJson($url, $timeout = 2){
	$body = httpGet($url, $timeout);
	return ($body === null) ? null : json_decode($body, true);
}

/**
 * POST a JSON body to the Firefox broker and json_decode the response.
 * $timeout bounds the whole call - the broker's own per-BiDi-call timeouts are
 * shorter, but this is the backstop if the broker process itself is wedged/gone.
 */
function httpPostJson($url, $bodyArray, $timeout = 15){
	$ctx = stream_context_create(['http' => [
		'method'           => 'POST',
		'protocol_version' => 1.1,
		'timeout'          => $timeout,
		'ignore_errors'    => true,
		'header'           => "Content-Type: application/json\r\nConnection: close\r\n",
		'content'          => json_encode($bodyArray === null ? [] : $bodyArray),
	]]);
	$body = @file_get_contents($url, false, $ctx);
	return ($body === false) ? null : json_decode($body, true);
}

/**
 * Best-effort detection of the OS's own default web browser, mapped to one of
 * wasqlValidBrowsers(). Returns null (not chrome) when it can't be determined,
 * so callers can fall through to the hardcoded default instead of guessing.
 *
 * Windows: the UserChoice registry key is the same place Explorer/Settings
 * reads "Default apps" from - ProgId contains the browser's identity
 * ('ChromeHTML', 'FirefoxURL-xxxx', 'MSEdgeHTM', etc.), so a substring match
 * is enough without hardcoding every ProgId variant a browser has shipped.
 * Linux: xdg-settings is the freedesktop.org standard for this. macOS has no
 * equally simple CLI query, so it's left undetected here (falls through).
 */
function detectOsDefaultBrowser($isWin){
	if($isWin){
		$out = @shell_exec('reg query "HKCU\Software\Microsoft\Windows\Shell\Associations\UrlAssociations\http\UserChoice" /v ProgId 2>nul');
		if($out && preg_match('/ProgId\s+REG_SZ\s+(\S+)/i', $out, $m)){
			$progId = strtolower($m[1]);
			if(strpos($progId, 'firefox') !== false){ return 'firefox'; }
			if(strpos($progId, 'edge') !== false){ return 'edge'; }
			if(strpos($progId, 'chrome') !== false){ return 'chrome'; }
		}
		return null;
	}
	$out = @shell_exec('xdg-settings get default-web-browser 2>/dev/null');
	if($out){
		$out = strtolower(trim($out));
		if(strpos($out, 'firefox') !== false){ return 'firefox'; }
		if(strpos($out, 'edge') !== false){ return 'edge'; }
		if(strpos($out, 'chrome') !== false){ return 'chrome'; }
	}
	return null;
}

/** Path to the persisted default-browser preference file - a standing user
 * setting, so it lives next to the user's profile, not in the sweepable temp dir. */
function wasqlBrowserPrefFile(){
	$home = getenv('USERPROFILE');
	if(!$home){ $home = getenv('HOME'); }
	if(!$home){ $home = sys_get_temp_dir(); }
	return $home . DIRECTORY_SEPARATOR . '.wasql-browser';
}

/** Valid --browser / --set-default values. Edge is Chromium-based and shares
 * Chrome's entire CDP code path (see step 3+4 below) - it's a third value
 * here, not a third implementation. */
function wasqlValidBrowsers(){ return ['chrome', 'firefox', 'edge']; }

/**
 * Resolve which browser to drive this run, high to low precedence:
 *   1. --browser=chrome|firefox|edge (this run only)
 *   2. WASQL_BROWSER env var
 *   3. the persisted default (see --set-default / wasqlBrowserPrefFile())
 *   4. the OS's own default browser, when it's one wasqlValidBrowsers()
 *      recognizes (see detectOsDefaultBrowser()) - "unless specified" is the
 *      whole point of this tier being LAST: it only ever applies once none of
 *      1-3 said anything, so an explicit choice always wins.
 *   5. hardcoded 'chrome' - so nothing changes for existing callers on a
 *      machine where the OS default can't be determined or isn't one of these.
 */
function resolveBrowserPref($opts, $isWin){
	$valid = wasqlValidBrowsers();
	if(isset($opts['browser']) && $opts['browser'] !== true){
		$b = strtolower(trim($opts['browser']));
		if(!in_array($b, $valid, true)){ fail("--browser must be one of: " . implode(', ', $valid) . " (got '$b')"); }
		return $b;
	}
	$env = getenv('WASQL_BROWSER');
	if($env){
		$env = strtolower(trim($env));
		if(in_array($env, $valid, true)){ return $env; }
	}
	$prefFile = wasqlBrowserPrefFile();
	if(is_file($prefFile)){
		$saved = strtolower(trim((string)@file_get_contents($prefFile)));
		if(in_array($saved, $valid, true)){ return $saved; }
	}
	$detected = detectOsDefaultBrowser($isWin);
	if($detected !== null){ return $detected; }
	return 'chrome';
}

/**
 * Open a URL as a new tab in an already-running debug Chrome.
 *
 * Chrome 111+ only accepts PUT on /json/new (a GET returns 405), so PUT is
 * tried first, then the legacy GET, then curl as a last resort.
 *
 * IMPORTANT: "the request returned a body" is NOT a success test. httpGet()
 * sets ignore_errors, so a 405 comes back as a normal body string - which is
 * why the old `if($new === null)` fallback never fired and the tab silently
 * never got created. Success = a parseable target object with an id.
 */
/**
 * Return the debug target array (id/url/webSocketDebuggerUrl) matching a
 * remembered target id, or failing that the first target whose URL contains
 * $host, else null.
 *
 * Checking $rememberedId FIRST (not just host) matters once multiple Claude
 * sessions share one debug Chrome instance: a plain host-substring search
 * only disambiguates when each session is on a different site, and a caller
 * (e.g. the screenshot helper) that just grabs "the first tab" is order-
 * dependent and can grab a different session's tab. Matching the exact id
 * this alias saved last run makes repeat runs land on the same tab
 * regardless of what else is open.
 */
function findTarget($json, $host, $rememberedId = null){
	$list = json_decode((string)$json, true);
	if(!is_array($list)){ return null; }
	if($rememberedId !== null){
		foreach($list as $t){ if(!empty($t['id']) && $t['id'] === $rememberedId){ return $t; } }
	}
	foreach($list as $t){
		if(!empty($t['url']) && stripos($t['url'], $host) !== false){ return $t; }
	}
	return null;
}

/**
 * Run $cmd, capturing combined stdout+stderr, but never block longer than
 * $timeoutSec - shell_exec() has no timeout at all, and the screenshot node
 * script it used to run could hang forever on a stale Chrome tab (WebSocket
 * connect that never fires 'open'), taking this whole PHP process down with
 * it. proc_open's PID is the `cmd /c` wrapper's, not node's underneath it, so
 * a timeout kills the wrapper's whole process TREE via taskkill /T, not just
 * the wrapper.
 */
function runWithTimeout($cmd, $timeoutSec, $isWin){
	$spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
	$p = @proc_open($cmd, $spec, $pipes);
	if(!is_resource($p)){ return ['out' => '', 'timedOut' => false]; }
	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);
	$out = ''; $deadline = microtime(true) + $timeoutSec; $status = proc_get_status($p);
	while($status['running'] && microtime(true) < $deadline){
		$out .= stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
		usleep(100000);
		$status = proc_get_status($p);
	}
	$timedOut = !!$status['running'];
	if($timedOut){
		if($isWin && !empty($status['pid'])){ @shell_exec('taskkill /F /T /PID ' . (int)$status['pid'] . ' 2>nul'); }
		@proc_terminate($p, 9);
	}
	$out .= stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
	fclose($pipes[1]); fclose($pipes[2]);
	proc_close($p);
	return ['out' => $out, 'timedOut' => $timedOut];
}

function newTab($jsonUrl, $url){
	$endpoint = $jsonUrl . '/new?' . $url;
	$isTarget = function($body){
		if(!$body){ return false; }
		$j = json_decode($body, true);
		return (is_array($j) && !empty($j['id']));
	};
	foreach(['PUT', 'GET'] as $method){
		$ctx = stream_context_create(['http' => [
			'method'           => $method,
			'protocol_version' => 1.1,
			'timeout'          => 5,
			'ignore_errors'    => true,
			'header'           => "Connection: close\r\n",
		]]);
		if($isTarget(@file_get_contents($endpoint, false, $ctx))){ return true; }
	}
	return $isTarget(@shell_exec('curl -s -X PUT "' . $endpoint . '" 2>&1'));
}

/**
 * Resolved path to wt.exe if this process is running inside a Windows Terminal
 * window, else null. Cached - the `where` probe is a subprocess.
 *
 * WT_SESSION is set by Windows Terminal in every shell it hosts, and env vars
 * are inherited by grandchildren - so it is still visible here even though this
 * script was started by an assistant/tool that the terminal launched, several
 * processes down. A legacy conhost console does NOT set it, which is exactly the
 * distinction needed: conhost has no tabs, so there is nothing to add a tab to.
 *
 * The returned path is for detection/reporting only - the command line uses a
 * bare `wt.exe` (see launchWtTab) - so `where` succeeding, i.e. wt.exe being on
 * PATH, is the real requirement. It normally is: it's an app-execution alias in
 * %LOCALAPPDATA%\Microsoft\WindowsApps, which is on an interactive user's PATH.
 */
function wtExe($isWin, &$why = null){
	static $cached = false;                                  // false = not probed yet
	static $reason = null;
	if($cached !== false){ $why = $reason; return $cached; }
	$cached = null;
	if(!$isWin){ $reason = 'not Windows'; }
	elseif(!getenv('WT_SESSION')){ $reason = 'WT_SESSION is not set - this console is not a Windows Terminal tab'; }
	else { $reason = 'wt.exe is not on PATH (`where wt.exe` found nothing)'; }
	if($isWin && getenv('WT_SESSION')){
		// `where` succeeding IS the test - do NOT add an is_file()/file_exists()
		// check on the result. wt.exe is an app-execution ALIAS: a zero-byte
		// APPEXECLINK reparse point, which PHP's stat cannot follow, so both
		// is_file() and file_exists() return false for a wt.exe that runs
		// perfectly well (only lstat() sees it). An existence check here silently
		// disables tabs on every machine.
		$where = @shell_exec('where wt.exe 2>nul');
		if($where && preg_match('/^(.*wt\.exe)\s*$/mi', $where, $m)){
			$cached = trim($m[1]);
			$reason = null;
		}
	}
	$why = $reason;
	return $cached;
}

/**
 * Add a tab to the Windows Terminal window hosting this process and run $cmd in
 * it. Returns true only if wt.exe accepted the request.
 *
 * `-w 0` means "the current window", which wt resolves via WT_SESSION - so the
 * tab lands in the window the user typed `claude "work on ..."` in rather than
 * opening yet another window.
 *
 * Two advantages over `start` in a separate window:
 *   - The tab's process is a child of WindowsTerminal.exe, not of this script,
 *     so it cannot inherit a duplicate handle to the caller's stdio pipe - the
 *     hazard launchDetached() below has to sidestep by launching via
 *     PowerShell's Start-Process (NUL-ing the child's own stdio is not enough).
 *   - `; focus-tab -p` hands focus straight back, so the new tab doesn't yank
 *     the user out of the session they're typing in. This part IS a heuristic:
 *     the new tab is appended last and focused, so "previous" is whichever tab
 *     was last before it - correct when the session's tab is the rightmost, the
 *     normal case. --no-chase turns it off.
 *
 * Quoting notes, both learned the hard way in this file:
 *   - wt splits its OWN command line on ';', so a literal semicolon inside an
 *     argument has to be escaped as '\;' or it silently truncates the command.
 *   - PHP's proc_open wraps the command in `cmd /c "..."` on Windows, and cmd
 *     mangles a command that STARTS with a quote. Hence bare `wt.exe` (found on
 *     PATH by wtExe) instead of a quoted absolute path; inner quoted args are
 *     fine because they aren't first.
 */
function launchWtTab($cmd, $title, $cwd, $chase = true, &$why = null){
	$esc  = function($s){ return str_replace(';', '\\;', $s); };
	$full = 'wt.exe -w 0 new-tab --title "' . $esc($title) . '" -d "' . $esc($cwd) . '" ' . $esc($cmd)
		. ($chase ? ' ; focus-tab -p' : '');
	// stderr goes to a temp FILE, not a pipe: wt's complaint is worth reporting,
	// but a pipe could be inherited by whatever wt spawns and then never sees EOF
	// (the same hazard launchDetached documents). A file has no such lifetime.
	$errFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql-wt-err.txt';
	$spec = [0 => ['file', 'NUL', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', $errFile, 'w']];
	$p = @proc_open($full, $spec, $pipes);
	if(!is_resource($p)){ $why = 'proc_open could not start wt.exe'; return false; }
	// wt.exe hands the request to the already-running terminal and exits at once;
	// it does NOT wait on the tab's process (that one belongs to
	// WindowsTerminal.exe), so this close returns immediately rather than
	// blocking for the life of the watcher.
	//
	// The exit code is what makes the fallback safe: non-zero means the tab was
	// never created, so falling back to a separate window cannot double-start the
	// watcher - and postedit.php's startup re-sync is destructive, so starting it
	// twice is the one outcome to avoid.
	$rc = proc_close($p);
	if($rc === 0){ $why = null; return true; }
	$err = @file_get_contents($errFile);
	$err = $err === false ? '' : trim(preg_replace('/\s+/', ' ', $err));
	$why = "wt.exe refused the request (exit $rc)" . ($err !== '' ? ": $err" : '');
	return false;
}

/**
 * Launch a detached process: Windows via PowerShell's Start-Process, everything
 * else via `nohup ... &`.
 *
 * Windows is the interesting case. The hazard is that a long-lived grandchild
 * (the watcher, Chrome, the Firefox broker) can hold a duplicate handle to the
 * CALLER's stdout pipe - the pipe `php workon.php ... | tail` hands us - and
 * keep it open for its whole life. The caller's read then never sees EOF, so
 * workon.php looks like it hangs forever even though it finished its work and
 * exited. That is what made piped/tool-driven runs hang on the FIRST run for an
 * alias - the only run where the watcher still has to be launched.
 *
 * Pointing the immediate child's OWN stdio at NUL - what this function used to
 * do - does NOT fix that. proc_open() calls CreateProcess with
 * bInheritHandles=TRUE, which duplicates EVERY inheritable handle in this PHP
 * process into the child, not just the three it designates as std handles. So
 * the caller's pipe rides along regardless, and `start`'s grandchild inherits it
 * in turn. Measured with the NUL specs in place: a `cmd /k` grandchild (one that
 * never exits - i.e. the watcher) left `php workon.php imago | tail` hanging
 * indefinitely, while an otherwise identical `cmd /c` grandchild released the
 * pipe the moment it exited.
 *
 * Start-Process is the fix: PowerShell launches the target itself without
 * passing our handles down, so nothing that outlives this script can hold the
 * caller's pipe. The powershell.exe hop is short-lived and Start-Process does
 * not wait on what it started, so proc_close() below returns in about a second
 * rather than blocking for the life of the watcher. The one-liner goes through a
 * temp .ps1 file to dodge nested-quote mangling via `cmd /c` - the same trick
 * the watcher launch uses for its .bat. Requires powershell.exe, which this
 * script already depends on for watcher detection.
 *
 * $title is kept for call-site compatibility but is no longer applied: it only
 * ever set a `start "title"` console title, and the one launch that gets a
 * visible console (the watcher) already titles its own window from its .bat.
 */
function launchDetached($cmd, $isWin, $title = ''){
	if(!$isWin){
		$spec = [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']];
		$p = @proc_open('nohup ' . $cmd . ' >/dev/null 2>&1 &', $spec, $pipes);
		if(is_resource($p)){ proc_close($p); }
		return;
	}
	list($exe, $args) = splitExeArgs($cmd);
	if($exe === ''){ return; }
	// Single-quoted PowerShell strings are literal, so nothing in a path or a
	// browser flag gets expanded on the way through (a .bat wrapper would expand
	// %VAR%). '' is the escape for an embedded single quote.
	$q = function($s){ return "'" . str_replace("'", "''", $s) . "'"; };
	$ps = 'Start-Process -FilePath ' . $q($exe) . ($args === '' ? '' : ' -ArgumentList ' . $q($args));
	$psFile = tempnam(sys_get_temp_dir(), 'wld') . '.ps1';
	file_put_contents($psFile, $ps . "\r\n");
	$spec = [0 => ['file', 'NUL', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']];
	$p = @proc_open('powershell -NoProfile -ExecutionPolicy Bypass -File "' . $psFile . '"', $spec, $pipes);
	// proc_close waits only for powershell, so the temp file is safe to drop here.
	if(is_resource($p)){ proc_close($p); }
	@unlink($psFile);
}

/**
 * Split a Windows command line into [exe, argument-string] for Start-Process.
 * Call sites pass the executable first, usually quoted ('"C:\...\chrome.exe"
 * --flags'), but the watcher passes it bare ('cmd /k "...bat"').
 */
function splitExeArgs($cmd){
	$cmd = trim($cmd);
	if($cmd === ''){ return ['', '']; }
	if($cmd[0] === '"'){
		$end = strpos($cmd, '"', 1);
		if($end !== false){ return [substr($cmd, 1, $end - 1), ltrim(substr($cmd, $end + 1))]; }
	}
	$sp = strpos($cmd, ' ');
	return $sp === false ? [$cmd, ''] : [substr($cmd, 0, $sp), ltrim(substr($cmd, $sp + 1))];
}

// ---- parse args -----------------------------------------------------------
$alias = null; $page = null; $opts = [];
foreach(array_slice($argv, 1) as $a){
	if(substr($a, 0, 2) === '--'){
		$kv = explode('=', substr($a, 2), 2);
		$opts[$kv[0]] = isset($kv[1]) ? $kv[1] : true;
	} elseif($alias === null){ $alias = $a; }
	elseif($page === null){ $page = $a; }
}
if(isset($opts['help']) || isset($opts['h']) || $alias === '?'){ usage(); exit(0); }
// Standalone maintenance actions - neither needs an alias, so handle both
// before the "no alias given" check below.
if(isset($opts['set-default'])){
	if($opts['set-default'] === true){ fail("--set-default needs a value: " . implode(', ', wasqlValidBrowsers())); }
	$b = strtolower(trim($opts['set-default']));
	if(!in_array($b, wasqlValidBrowsers(), true)){ fail("--set-default must be one of: " . implode(', ', wasqlValidBrowsers()) . " (got '$b')"); }
	$prefFile = wasqlBrowserPrefFile();
	if(@file_put_contents($prefFile, $b) === false){ fail("could not write default browser to $prefFile"); }
	out("Default browser set to '$b' ($prefFile)");
	exit(0);
}
if(isset($opts['ff-shutdown'])){
	$ffBrokerPortArg = isset($opts['ff-broker-port']) ? (int)$opts['ff-broker-port'] : 9334;
	$result = httpPostJson("http://127.0.0.1:$ffBrokerPortArg/shutdown", [], 10);
	if(is_array($result) && !empty($result['ok'])){
		out("Firefox broker shut down cleanly (port $ffBrokerPortArg).");
		exit(0);
	}
	fail("could not reach the Firefox broker on port $ffBrokerPortArg to shut it down (already stopped?)");
}
if($alias === null){
	err('ERROR: no alias given.');
	usage();
	exit(1);
}
if(isset($opts['page'])){ $page = $opts['page']; }
$pageGiven = ($page !== null && $page !== '');
if(!$pageGiven){ $page = ''; }

// Log file: --log=PATH, or temp/wasql-workon-{alias}.log. Truncated per run so
// it always holds exactly the last run, never an ever-growing pile.
if(!isset($opts['no-log'])){
	$LOGFILE = (isset($opts['log']) && $opts['log'] !== true)
		? $opts['log']
		: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql-workon-'
			. preg_replace('/[^a-z0-9_\-]/i', '', $alias) . '.log';
	@file_put_contents($LOGFILE, '');
}

$browser = resolveBrowserPref($opts, $IS_WIN);
$port    = isset($opts['port'])  ? (int)$opts['port']  : 9222;         // Chrome debug port
$ffPort  = isset($opts['ff-port']) ? (int)$opts['ff-port'] : 9333;      // Firefox BiDi port
$ffBrokerPort = isset($opts['ff-broker-port']) ? (int)$opts['ff-broker-port'] : 9334;
$width   = isset($opts['width']) ? (int)$opts['width'] : 390;
// --reshoot=URL is a lightweight follow-up screenshot during an already-open
// session (a different page, or the same page after an edit) - it implies a
// shot even without an explicit --shot, so it needs to be known before $doShot
// is decided below.
$reshootGiven = isset($opts['reshoot']) && $opts['reshoot'] !== true;
if(isset($opts['reshoot']) && !$reshootGiven){ fail('--reshoot needs a URL, e.g. --reshoot=https://host/page'); }
// --no-browser is a clearer name now that Chrome isn't the only option;
// --no-chrome stays as the original, still-working name. It now means "status
// check only" (no browser AND no inventory), since skipping the browser is the
// default rather than something you have to ask for.
$noBrowserGiven = isset($opts['no-chrome']) || isset($opts['no-browser']);
if($noBrowserGiven && $reshootGiven){ fail('--no-chrome/--no-browser and --reshoot are contradictory (reshoot needs a browser).'); }
// The browser is OPT-IN: launching it and confirming its target is the slowest
// part of startup, and the developer normally already has the page open. So a
// plain run does watcher + inventory only, and the browser steps happen when
// they're actually asked for - explicitly (--browse/--open) or implicitly by
// asking for a picture (--shot/--reshoot).
$wantBrowser = isset($opts['browse']) || isset($opts['open']) || isset($opts['shot']) || $reshootGiven;
$noChrome = $noBrowserGiven || !$wantBrowser;
$doShot  = (isset($opts['shot']) || $reshootGiven) && !isset($opts['no-shot']) && !$noChrome;
// `--shot` with no =PATH still means "take one" - fall back to a temp file
// rather than using the boolean `true` as a filename.
$shotOut = !$doShot ? null
	: ((!isset($opts['shot']) || $opts['shot'] === true)
		? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql-shot-' . preg_replace('/[^a-z0-9_\-]/i', '', $alias) . '.png'
		: $opts['shot']);
// --reshoot assumes an earlier full "work on" call already started the
// watcher for this alias - re-launching it here would trigger postedit.php's
// destructive startup re-sync for what's meant to be a quick follow-up shot.
// --no-chrome is a pure status check, so there is nothing for it to screenshot.
$doWatch = !isset($opts['no-watcher']) && !$reshootGiven;
// Watcher window placement: a tab in the current Windows Terminal window when
// we're running in one, else its own console window. --no-tab forces the window.
$useTab  = !isset($opts['no-tab']);
$doChase = !isset($opts['no-chase']);
$profileDefaults = ['chrome' => 'wasql-chrome-debug', 'firefox' => 'wasql-firefox-debug', 'edge' => 'wasql-edge-debug'];
$profile = isset($opts['profile']) ? $opts['profile']
	: sys_get_temp_dir() . DIRECTORY_SEPARATOR . $profileDefaults[$browser];

// --json means "only the JSON line" - human step-by-step lines would just
// duplicate the same information in a second, ambiguous representation.
$jsonMode = isset($opts['json']);
/** Print a human-readable progress line, unless --json suppresses it. */
function step($msg){
	global $jsonMode;
	// Suppressed on stdout under --json, but still logged - the log stays a
	// complete record of the run regardless of output mode.
	if(!$jsonMode){ out($msg); } else { logLine($msg); }
}

// ---- watcher filters ------------------------------------------------------
// postedit.php takes positional filters after the alias ("postedit.php dexpdq
// sapcc") and only syncs/watches records whose NAME contains one of them
// (case-insensitive substring). So "work on dexpdq sapcc" -> filter 'sapcc':
// the startup re-sync pulls down just that page instead of the whole site, and
// the watcher only listens to those files.
//   - no page named  -> no filter (watch everything, as before)
//   - --filter=a,b   -> use those instead of the page
//   - --no-filter    -> watch everything even though a page was named
// Filters are reduced to a safe token set: they end up on a command line, and
// postedit.php splits them on whitespace anyway.
/** Strip a page/filter down to a single shell-safe token ('sapcc/edit/3?x=1' -> 'sapcc'). */
function filterToken($s){
	$s = explode('/', ltrim(strtok((string)$s, '?'), '/'));
	return strtolower(preg_replace('/[^a-z0-9_\-\.]/i', '', $s[0]));
}
$filters = [];
if(isset($opts['no-filter'])){ $filters = []; }
elseif(isset($opts['filter']) && $opts['filter'] !== true){
	foreach(explode(',', $opts['filter']) as $f){
		$t = filterToken($f);
		if($t !== ''){ $filters[] = $t; }
	}
} elseif($pageGiven){
	$t = filterToken($page);                 // filter on the page name, not its action args
	if($t !== ''){ $filters = [$t]; }
}

// ---- 1. resolve target ----------------------------------------------------
// "work on wasql" is local framework dev: plain http://localhost/php/admin.php,
// no PostEdit and no watcher. Any other alias resolves to a PostEdit host.
$localMode = (strcasecmp($alias, 'wasql') === 0);
$host = null; $insecure = false;
if($localMode){
	$host    = 'localhost';
	$doWatch = false;                                    // no PostEdit watcher in local mode
	$target  = $pageGiven ? ltrim($page, '/') : 'php/admin.php';
	$url     = 'http://localhost/' . $target;
} else {
	if(!is_file($XML)){ fail("postedit.xml not found at $XML"); }
	$xmlText = file_get_contents($XML);
	if(preg_match_all('/<host\b([^>]*?)\/?>/s', $xmlText, $blocks)){
		foreach($blocks[1] as $attrs){
			if(preg_match('/\balias\s*=\s*"([^"]*)"/', $attrs, $m) && strcasecmp($m[1], $alias) === 0){
				if(preg_match('/\bname\s*=\s*"([^"]*)"/', $attrs, $n)){ $host = $n[1]; }
				if(preg_match('/\binsecure\s*=\s*"([^"]*)"/', $attrs, $i)){ $insecure = ($i[1] === '1'); }
				break;
			}
		}
	}
	if($host === null){ fail("alias '$alias' not found in postedit.xml"); }
	$url = 'https://' . $host . '/' . ltrim($page, '/');
}
// $host stays whatever the alias resolved to (used to find the session's tab
// below) - only the navigation target changes to the requested URL.
if($reshootGiven){ $url = $opts['reshoot']; }

step("• alias   : $alias" . ($localMode ? '  (local framework mode)' : '') . ($reshootGiven ? '  (reshoot)' : ''));
step("• host    : $host" . ($insecure ? ' (self-signed)' : ''));
step("• url     : $url");

// ---- tab memory ------------------------------------------------------------
// One debug Chrome instance is shared by every concurrent "work on" session
// (same default port/profile), so without this a session has no way to tell
// its own tab apart from another session's once more than one tab is open.
// Remembering the last confirmed target id per alias lets a session reliably
// come back to the SAME tab next run instead of matching "the first tab for
// this host" (wrong once two sessions share a host) or "the first page tab in
// the list" (wrong the instant a second session's tab exists at all).
$tabStateFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql-workon-tab-'
	. preg_replace('/[^a-z0-9_\-]/i', '', $alias) . '.json';
$rememberedTabId = null;
if(is_file($tabStateFile)){
	$decoded = json_decode((string)@file_get_contents($tabStateFile), true);
	if(is_array($decoded) && !empty($decoded['id'])){ $rememberedTabId = $decoded['id']; }
}

// ---- 2. ensure the PostEdit watcher ---------------------------------------
// Launched before Chrome: the watcher's startup re-sync runs in its own
// detached process, so starting it first lets that background work overlap
// with the Chrome boot/confirm wait below instead of running back-to-back.
/** Filter args of a running watcher: the tokens after "postedit.php {alias}". null = couldn't parse. */
function runningFilters($cmdline, $alias){
	if(!preg_match('/postedit\.php["\']?\s+' . preg_quote($alias, '/') . '(?:\s+(.*))?$/i', trim($cmdline), $m)){
		return null;
	}
	$rest = isset($m[1]) ? trim($m[1]) : '';
	if($rest === ''){ return []; }
	return array_values(array_filter(array_map('strtolower', preg_split('/\s+/', $rest)), 'strlen'));
}
/** Human-readable filter list. */
function filterLabel($f){ return count($f) ? implode(', ', $f) : 'none (all records)'; }

$watcherPid = null; $watcherCmd = ''; $watcherRunningFilters = null; $watcherLaunch = null;
if($localMode){
	step("• watcher : n/a (local framework mode - no PostEdit)");
} else {
if($IS_WIN){
	// Query php.exe command lines via PowerShell (wmic is gone on Win11).
	// Emit "pid|commandline" so we can also see which filters it was started with.
	$ps = 'Get-CimInstance Win32_Process -Filter "Name=' . "'php.exe'" . '" '
	    . '| Where-Object { $_.CommandLine -like ' . "'*postedit.php " . $alias . "*'" . ' } '
	    . '| ForEach-Object { "$($_.ProcessId)|$($_.CommandLine)" }';
	$psFile = tempnam(sys_get_temp_dir(), 'wpe') . '.ps1';
	file_put_contents($psFile, $ps);
	$res = @shell_exec('powershell -NoProfile -ExecutionPolicy Bypass -File "' . $psFile . '" 2>nul');
	@unlink($psFile);
	if($res && preg_match('/^\s*(\d+)\|(.*)$/m', $res, $pm)){
		$watcherPid = (int)$pm[1]; $watcherCmd = trim($pm[2]);
	}
} else {
	$res = @shell_exec('pgrep -af "postedit.php ' . $alias . '" 2>/dev/null');
	if($res && preg_match('/^\s*(\d+)\s+(.*)$/m', $res, $pm)){
		$watcherPid = (int)$pm[1]; $watcherCmd = trim($pm[2]);
	}
}

if($watcherPid){
	$watcherRunningFilters = runningFilters($watcherCmd, $alias);
	step("• watcher : running (pid $watcherPid, filters: "
		. ($watcherRunningFilters === null ? 'unknown' : filterLabel($watcherRunningFilters)) . ")");
	// A watcher already up was started with whatever filters it was started with -
	// we do NOT restart it, because postedit.php's startup re-sync is destructive
	// (backs up + deletes + re-downloads the working files). Just flag the mismatch.
	if(is_array($watcherRunningFilters) && $watcherRunningFilters != $filters){
		step("  ⚠  wanted filters: " . filterLabel($filters) . " - the running watcher differs.");
		if(count($filters) && !count($watcherRunningFilters)){
			step("     (it's watching everything, which still covers '" . implode("','", $filters) . "')");
		} else {
			step("     Stop it (Ctrl-C in its console) and re-run workon.php to re-filter.");
		}
	}
} elseif(!$doWatch){
	step("• watcher : NOT running (left alone; --no-watcher)");
} else {
	// Launch the watcher in its own persistent console. Use the SAME php that
	// runs this script (PHP_BINARY) so PATH quirks don't matter and no php path
	// is ever hard-coded.
	$php = PHP_BINARY;
	// Filters are appended positionally after the alias; already reduced to safe
	// tokens above, so no quoting is needed (and postedit.php wants them split).
	$filterArgs = count($filters) ? ' ' . implode(' ', $filters) : '';
	if($IS_WIN){
		// Nesting quotes through popen -> cmd /c -> start -> cmd /k is fragile:
		// a quoted php path combined with '&&' on one line trips cmd's quote
		// parser (it then tries to run the quoted php path as a bare command).
		// Emit a tiny launcher .bat and run THAT instead - one clean quoted arg,
		// no nested quoting. Fixed per-alias name so temp files don't accumulate.
		$bat = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql-watch-' . $alias . '.bat';
		file_put_contents($bat,
			"@echo off\r\n"
			. 'cd /d "' . $ROOT . '"' . "\r\n"
			. 'title postedit-' . $alias . "\r\n"
			. '"' . $php . '" postedit\\postedit.php ' . $alias . $filterArgs . "\r\n"
		);
		// Prefer a TAB in the Windows Terminal window the user is already working
		// in, so `claude "work on {alias}"` doesn't scatter windows across the
		// desktop. Falls back to a separate console window when this isn't
		// Windows Terminal, --no-tab was given, or wt refused the request.
		// `cmd /k` either way: the console stays open after the watcher exits so
		// its error output is still readable.
		$run = 'cmd /k "' . $bat . '"';
		// $tabWhy explains any fall back to a separate window. Without it a
		// silent fallback is indistinguishable from the feature being absent,
		// which is exactly the question that gets asked afterwards.
		$tabWhy = null; $ok = false;
		if(!$useTab){ $tabWhy = '--no-tab was given'; }
		elseif(!wtExe($IS_WIN, $tabWhy)){ /* wtExe filled $tabWhy */ }
		else { $ok = launchWtTab($run, 'postedit-' . $alias, $ROOT, $doChase, $tabWhy); }
		if(empty($ok)){ launchDetached($run, true, 'postedit-' . $alias); }
		$watcherLaunch = !empty($ok) ? 'wt-tab' : 'window';
	} else {
		launchDetached('"' . $php . '" ' . $ROOT . '/postedit/postedit.php ' . $alias . $filterArgs, false);
		$watcherLaunch = 'window';
	}
	step("• watcher : launched for '$alias' "
		. ($watcherLaunch === 'wt-tab'
			? 'in a new tab of THIS terminal window' . ($doChase ? ' (focus kept here)' : '')
			: 'in its own console window')
		. " (filters: " . filterLabel($filters) . ")");
	if($watcherLaunch === 'window' && !empty($tabWhy)){
		step("     (no terminal tab: $tabWhy)");
	}
	if(count($filters)){
		step("     Only records whose name contains '" . implode("' or '", $filters) . "' are synced/watched");
		step("     (that includes _templates - re-run with --no-filter to work on the template too).");
	}
	step("  ⚠  startup re-syncs from the DB: it backs up postEditFiles/$alias to");
	step("     {$alias}_bak, deletes the working files, and re-downloads them fresh.");
	step("     Any un-synced local edits are in {$alias}_bak.");
}
}

// ---- 3+4. ensure debug browser + confirm target ---------------------------
$matchedTarget = null;                  // {id,url} once a target is confirmed - same shape for both browsers
$confirmed     = null;                  // null = not attempted (--no-chrome/--no-browser); bool once this step runs
$chromeUp      = false;                 // "the debug browser was already up" (kept named for JSON-field compat)
$ffBrokerUrl   = "http://127.0.0.1:$ffBrokerPort";
if($noChrome){
	step($noBrowserGiven
		? "• browser : skipped (--no-chrome/--no-browser - watcher status only)"
		: "• browser : not launched (default - assuming it's already open; add --browse to launch/confirm it, or --shot=PATH to also capture it)");
} elseif($browser === 'firefox'){
	// See workon_firefox.md / usage()'s FIREFOX SUPPORT section: Firefox allows
	// exactly ONE BiDi session per process, ever, so a resident broker holds it
	// and this script talks to the broker over plain HTTP instead of driving
	// Firefox directly per call.
	$status = httpGetJson("$ffBrokerUrl/status", 2);
	$chromeUp = is_array($status);
	if($chromeUp && !empty($status['ready'])){
		step("• firefox : broker already up (port $ffBrokerPort, session {$status['sessionId']})");
	} elseif($chromeUp){
		step("• firefox : broker already running but not ready ("
			. (!empty($status['error']) ? $status['error'] : 'still connecting') . ')');
	} else {
		// Nothing answering on the broker port - launch Firefox + the broker fresh.
		$firefox = isset($opts['firefox']) ? $opts['firefox'] : null;
		if($firefox === null){
			if($IS_WIN){
				$cands = [
					'C:\\Program Files\\Mozilla Firefox\\firefox.exe',
					'C:\\Program Files (x86)\\Mozilla Firefox\\firefox.exe',
				];
				foreach($cands as $c){ if(is_file($c)){ $firefox = $c; break; } }
				if($firefox === null){
					$reg = @shell_exec('reg query "HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths\\firefox.exe" /ve 2>nul');
					if($reg && preg_match('/REG_SZ\s+(.+\.exe)/i', $reg, $rm)){ $firefox = trim($rm[1]); }
				}
			} else {
				foreach(['/Applications/Firefox.app/Contents/MacOS/firefox', '/usr/bin/firefox'] as $c){
					if(is_file($c)){ $firefox = $c; break; }
				}
			}
		}
		if($firefox === null){ fail('could not locate the Firefox executable (pass --firefox=PATH)'); }
		if(!is_dir($profile)){ @mkdir($profile, 0777, true); }

		$ffArgs = '-profile "' . $profile . '" -no-remote --remote-debugging-port=' . $ffPort
		        . ' -new-window "' . $url . '"';
		launchDetached('"' . $firefox . '" ' . $ffArgs, $IS_WIN, 'wasql-firefox');
		step("• firefox : launched debug instance (port $ffPort, profile $profile)");

		// Write + launch the resident broker. Content mirrors the prototype
		// verified live against Firefox 153 - see workon_firefox.md.
		$brokerJs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql_ff_broker.js';
		file_put_contents($brokerJs, <<<'JS'
// wasql_ff_broker.js - resident BiDi session holder + HTTP control API, the
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
JS);
		$node = null;
		if($IS_WIN){
			$nc = 'C:\\Program Files\\nodejs\\node.exe';
			$node = is_file($nc) ? $nc : 'node';
		} else { $node = 'node'; }
		$brokerLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql-ff-broker-'
			. preg_replace('/[^a-z0-9_\-]/i', '', $alias) . '.log';
		@file_put_contents($brokerLog, '');
		launchDetached('"' . $node . '" "' . $brokerJs . '" ' . $ffBrokerPort . ' ' . $ffPort . ' "' . $brokerLog . '"',
			$IS_WIN, 'wasql-ff-broker');
		step("• firefox : launched broker (port $ffBrokerPort, log $brokerLog)");
	}

	// Poll the broker until its one-time session.new succeeds (or fails for good).
	$deadline = microtime(true) + 20;
	$brokerReady = false;
	while(microtime(true) < $deadline){
		$status = httpGetJson("$ffBrokerUrl/status", 2);
		if(is_array($status)){
			if(!empty($status['ready'])){ $brokerReady = true; break; }
			if(!empty($status['error'])){ step("• firefox : broker reported an error: {$status['error']}"); break; }
		}
		usleep(300000);
	}
	step($brokerReady ? "• firefox : broker ready ✓" : "• firefox : broker NOT ready after wait ✗");

	$confirmed = false;
	if($brokerReady){
		$resolved = httpPostJson("$ffBrokerUrl/resolve", ['host' => $host, 'url' => $url, 'rememberedId' => $rememberedTabId], 15);
		if(is_array($resolved) && !empty($resolved['context'])){
			$matchedTarget = ['id' => $resolved['context'], 'url' => $resolved['url']];
			$confirmed = true;
			step("• target  : confirmed via Firefox broker"
				. (!empty($resolved['created']) ? ' (opened new tab)' : ' (reused existing tab)') . ' ✓');
		} else {
			step('• target  : Firefox broker /resolve failed'
				. (is_array($resolved) ? ' (' . (isset($resolved['error']) ? $resolved['error'] : 'unknown error') . ')' : ' (no response)'));
		}
	}
	if($confirmed && $matchedTarget && !empty($matchedTarget['id'])){
		// Same tab-memory file as Chrome (opaque id, browser tag for clarity) -
		// a re-run for this alias comes back to the SAME context either way.
		@file_put_contents($tabStateFile, json_encode(['id' => $matchedTarget['id'], 'url' => $matchedTarget['url'], 'browser' => 'firefox']));
		step("• tab id  : {$matchedTarget['id']} (remembered in $tabStateFile)");
	}
} else {
// Chrome and Edge share this whole branch: Edge is Chromium-based and speaks
// the exact same CDP protocol (same /json endpoint, same --remote-debugging-port
// flag) - only executable discovery differs, so $browser === 'edge' just picks
// a different exe/option/registry-key below instead of a second implementation.
$browserLabel = ($browser === 'edge') ? 'edge' : 'chrome';
// Probe a few times before concluding the browser is absent: a warm instance
// answers instantly, but a busy/just-started one can miss a single 3s probe,
// which would otherwise make us spawn a redundant duplicate instance.
// Use 127.0.0.1, NOT localhost: on Windows, PHP's stream wrapper tries IPv6 ::1
// first (which Chrome/Edge don't listen on) and stalls ~12s before falling
// back to IPv4. An IP literal is fast and the DNS-rebind check still accepts it.
$jsonUrl  = "http://127.0.0.1:$port/json";
$targets  = null;
// A closed port refuses a TCP connect almost instantly; only pay for the
// slower HTTP retry loop (which exists for a warm-but-busy instance that might
// miss a single probe) when something is actually listening on the port.
$portOpen = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 0.3);
if($portOpen){
	fclose($portOpen);
	for($i = 0; $i < 4; $i++){
		$targets = httpGet($jsonUrl, 3);
		if($targets !== null){ break; }
		usleep(500000);
	}
}
$chromeUp = ($targets !== null);

if($chromeUp){
	step("• $browserLabel  : debug instance already up on port $port");
	$matchedTarget = findTarget($targets, $host, $rememberedTabId);
	if(!$matchedTarget){
		// Open the page as a new tab in the existing debug instance.
		step(newTab($jsonUrl, $url)
			? "• $browserLabel  : opened new tab -> $url"
			: "• $browserLabel  : could NOT open a new tab (tried PUT, GET and curl on /json/new)");
	} else {
		step("• $browserLabel  : existing tab already on $host"
			. (($rememberedTabId && $matchedTarget['id'] === $rememberedTabId) ? ' (this session\'s remembered tab)' : ''));
	}
} else {
	// Locate the executable and launch a fresh detached debug instance.
	$exeOpt = ($browser === 'edge') ? 'edge' : 'chrome';
	$browserExe = isset($opts[$exeOpt]) ? $opts[$exeOpt] : null;
	if($browserExe === null){
		if($IS_WIN){
			$cands = ($browser === 'edge')
				? ['C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
				   'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe']
				: ['C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
				   'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'];
			foreach($cands as $c){ if(is_file($c)){ $browserExe = $c; break; } }
			if($browserExe === null){
				$regKey = ($browser === 'edge') ? 'msedge.exe' : 'chrome.exe';
				$reg = @shell_exec('reg query "HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths\\' . $regKey . '" /ve 2>nul');
				if($reg && preg_match('/REG_SZ\s+(.+\.exe)/i', $reg, $rm)){ $browserExe = trim($rm[1]); }
			}
		} else {
			$cands = ($browser === 'edge')
				? ['/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge',
				   '/usr/bin/microsoft-edge', '/usr/bin/microsoft-edge-stable']
				: ['/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
				   '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser'];
			foreach($cands as $c){ if(is_file($c)){ $browserExe = $c; break; } }
		}
	}
	if($browserExe === null){ fail("could not locate the " . ucfirst($browserLabel) . " executable (pass --$exeOpt=PATH)"); }
	if(!is_dir($profile)){ @mkdir($profile, 0777, true); }

	$args = '--remote-debugging-port=' . $port
	      . ' --user-data-dir="' . $profile . '"'
	      . ' --no-first-run --no-default-browser-check --new-window "' . $url . '"';
	launchDetached('"' . $browserExe . '" ' . $args, $IS_WIN, 'wasql-' . $browserLabel);
	step("• $browserLabel  : launched debug instance (port $port, profile $profile)");
}

// confirm the page target
$confirmed = false;
$deadline  = microtime(true) + 20;          // ~20s ceiling (cold Chrome start); exits early once up
$retried   = false;
while(microtime(true) < $deadline){
	$targets = httpGet($jsonUrl);
	$matchedTarget = findTarget($targets, $host, $rememberedTabId);
	if($matchedTarget){ $confirmed = true; break; }
	// One retry after ~8s: if the debug port is answering but our tab still
	// isn't there, the open request itself failed - re-asking beats waiting out
	// the rest of the deadline for a tab that was never created.
	if(!$retried && $targets !== null && microtime(true) > $deadline - 12){
		$retried = true;
		newTab($jsonUrl, $url);
	}
	usleep(250000);
}
step($confirmed
	? "• target  : confirmed on port $port ✓"
	: "• target  : NOT confirmed after wait ✗");
if($confirmed && $matchedTarget && !empty($matchedTarget['id'])){
	// Remember this tab for next run so a re-invocation of this session's
	// "work on {alias}" lands back on the SAME tab instead of guessing among
	// whatever other sessions' tabs are open in the shared debug Chrome.
	@file_put_contents($tabStateFile, json_encode(['id' => $matchedTarget['id'], 'url' => $matchedTarget['url']]));
	step("• tab id  : {$matchedTarget['id']} (remembered in $tabStateFile)");
}
if(!$confirmed){
	// Dump what IS open so the caller can diagnose from this output instead of
	// having to run a separate curl against the debug endpoint.
	$list = json_decode((string)$targets, true);
	if(!is_array($list)){
		step("     debug port $port is not answering - Chrome may still be booting.");
	} else {
		$pages = [];
		foreach($list as $t){
			if(!empty($t['url']) && preg_match('#^https?:#i', $t['url'])){ $pages[] = $t['url']; }
		}
		step(count($pages) ? '     open tabs: ' . implode("\n                ", $pages)
		                   : '     no http(s) tabs are open in the debug instance.');
	}
}
}                                                            // end of the !$noChrome block opened at step 3

// ---- 5. optional screenshot ----------------------------------------------
$shotWritten = null;
if($doShot && $confirmed){
	// The caller's --shot path usually lives in a per-session scratchpad dir that
	// doesn't exist yet. Create it here so the caller needs no separate mkdir.
	$shotDir = dirname($shotOut);
	if($shotDir !== '' && $shotDir !== '.' && !is_dir($shotDir)){ @mkdir($shotDir, 0777, true); }
	if($shotDir !== '' && $shotDir !== '.' && !is_dir($shotDir)){
		step("• shot    : cannot create directory $shotDir");
		$doShot = false;
	}
}
if($doShot && $confirmed && $browser === 'firefox'){
	// The broker already holds the session and does viewport+navigate+nudge+
	// scrollHeight+capture as one call - see /shot in the broker source above.
	$shotUrl = $url . (strpos($url, '?') === false ? '?cb=1' : '&cb=1');
	$result = httpPostJson("$ffBrokerUrl/shot",
		['context' => $matchedTarget['id'], 'url' => $shotUrl, 'width' => $width, 'out' => $shotOut], 30);
	if(is_array($result) && !empty($result['ok']) && is_file($shotOut)){
		$shotWritten = $shotOut;
		step("• shot    : $shotOut");
	} else {
		step('• shot    : failed ('
			. (is_array($result) ? (isset($result['error']) ? $result['error'] : 'unknown error') : 'no response from broker') . ')');
	}
}
if($doShot && $confirmed && $browser !== 'firefox'){
	$node = null;
	if($IS_WIN){
		$nc = 'C:\\Program Files\\nodejs\\node.exe';
		$node = is_file($nc) ? $nc : 'node';
	} else { $node = 'node'; }

	// Write the CDP screenshot helper (mobile emulation + 1px reflow nudge) to a temp file.
	$shotJs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql_shot.js';
	file_put_contents($shotJs, <<<'JS'
const [,, PORT, URL, OUT, W, TARGET_ID] = process.argv;
const width = parseInt(W || '390', 10);
const fs = require('fs');
// Hard backstop: a stale/closed tab can leave the WebSocket connect (or any
// later CDP response) waiting on an event that never fires. Force an exit well
// inside the caller's own timeout instead of trusting every individual await
// to fail cleanly - this is what used to make the whole workon.php process
// (and whatever invoked it) hang forever with no diagnostic.
setTimeout(() => { console.error('timeout: CDP session did not complete in time'); process.exit(1); }, 20000);
async function main() {
  const list = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
  // Target the exact confirmed tab by id when given - a shared debug Chrome
  // instance can have other sessions' tabs open, so "the first page tab in
  // the list" is order-dependent and can grab the wrong session's tab.
  // TARGET_ID absent (older caller) falls back to that old heuristic.
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
  // Wait for the actual load event instead of a blind fixed sleep: correct on
  // a slow page (a fixed sleep can fire before the page is ready, producing a
  // blank/half-rendered screenshot), and faster on the common fast-page case.
  // Bounded by an 8s fallback in case a page never fires 'load' (e.g. an SPA
  // that keeps streaming in content).
  const loaded = new Promise(res => {
    const onMsg = ev => {
      const msg = JSON.parse(ev.data);
      if (msg.method === 'Page.loadEventFired') { ws.removeEventListener('message', onMsg); res(); }
    };
    ws.addEventListener('message', onMsg);
  });
  await send('Page.navigate', { url: URL });
  await Promise.race([loaded, new Promise(r => setTimeout(r, 8000))]);
  await new Promise(r => setTimeout(r, 300));   // brief settle after load fires
  await metrics(width + 1); await new Promise(r => setTimeout(r, 200));
  await metrics(width);     await new Promise(r => setTimeout(r, 400));
  const h = await send('Runtime.evaluate', { expression: 'document.documentElement.scrollHeight', returnByValue: true });
  const height = Math.min(h.result.value, 4000);
  const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width, height, scale: 1 } });
  fs.writeFileSync(OUT, Buffer.from(shot.data, 'base64'));
  // IMPORTANT: we attached to the user's VISIBLE Chrome tab, so the mobile
  // device-metrics override must be cleared or their live view stays stuck at
  // the emulated (narrow) width. Capture first, then restore full-width.
  await send('Emulation.clearDeviceMetricsOverride');
  console.log('wrote', OUT, 'width', width, 'height', height);
  ws.close();
}
main().then(() => process.exit(0)).catch(e => { console.error(e); process.exit(1); });
JS);
	$shotUrl = $url . (strpos($url, '?') === false ? '?cb=1' : '&cb=1');
	$cmd = '"' . $node . '" "' . $shotJs . '" ' . $port . ' "' . $shotUrl . '" "' . $shotOut . '" ' . $width
	     . ($matchedTarget && !empty($matchedTarget['id']) ? ' "' . $matchedTarget['id'] . '"' : '');
	// 30s ceiling: the node script's own 20s watchdog should always fire first
	// and exit cleanly - this is the backstop for node/Chrome never starting or
	// the CDP session hanging before that watchdog even registers.
	$result = runWithTimeout($cmd, 30, $IS_WIN);
	if(is_file($shotOut)){
		$shotWritten = $shotOut;
		step("• shot    : $shotOut");
	} elseif($result['timedOut']){
		step("• shot    : failed (timed out after 30s - killed the node process)");
	} else {
		step("• shot    : failed (" . trim((string)$result['out']) . ")");
	}
}

// ---- 6. mirror inventory --------------------------------------------------
// The first thing anyone does after startup is "where are this page's files?" -
// otherwise a find/ls over postEditFiles. Do it here so it's part of the same
// approval, and so the answer arrives with the record ids already attached.
//
// Mirror layout: postEditFiles/{alias}/{table}/{record}/{record}.{table}.{field}.{id}.{ext}
$inventory = ['page' => [], 'others' => [], 'truncated' => 0, 'root' => null];
$invMax    = isset($opts['inv-max']) ? (int)$opts['inv-max'] : 40;
// Gated on $noBrowserGiven, not $noChrome: the inventory is the whole point of a
// default (browser-less) run - only an explicit --no-chrome/--no-browser status
// check skips it.
if(!$localMode && !isset($opts['no-inventory']) && !$reshootGiven && !$noBrowserGiven){
	$mirror = $ROOT . DIRECTORY_SEPARATOR . 'postedit' . DIRECTORY_SEPARATOR
	        . 'postEditFiles' . DIRECTORY_SEPARATOR . $alias;
	$inventory['root'] = $mirror;
	step('');
	step("• mirror  : $mirror");
	if(!is_dir($mirror)){
		step('     (nothing on disk yet - the watcher may still be re-syncing)');
	} else {
		$token = $pageGiven ? filterToken($page) : '';
		/**
		 * Walk the mirror into [recordKey => ['table','name','id','files'=>[field=>[path,size]]]].
		 * Filenames carry the metadata, so parse them rather than re-querying the DB.
		 */
		$collect = function($dir) use (&$collect){
			$found = [];
			foreach((array)@scandir($dir) as $e){
				if($e === '.' || $e === '..' || $e === '.claude'){ continue; }
				$p = $dir . DIRECTORY_SEPARATOR . $e;
				if(is_dir($p)){ $found = array_merge($found, $collect($p)); }
				elseif(is_file($p)){ $found[] = $p; }
			}
			return $found;
		};
		// A just-launched watcher re-syncs in the background; give the named
		// page's files a short window to land before reporting them missing.
		$recs = [];
		$waitUntil = microtime(true) + (($watcherPid || !$doWatch) ? 0 : 15);
		do {
			$recs = [];
			foreach($collect($mirror) as $f){
				// {record}.{table}.{field}.{id}.{ext}
				if(!preg_match('/^(.+)\.([^.]+)\.([^.]+)\.(\d+)\.[^.]+$/', basename($f), $m)){ continue; }
				$key = $m[2] . '/' . $m[1];
				if(!isset($recs[$key])){
					$recs[$key] = ['table' => $m[2], 'name' => $m[1], 'id' => $m[4], 'files' => []];
				}
				$recs[$key]['files'][$m[3]] = ['path' => $f, 'size' => (int)@filesize($f)];
			}
			$hit = false;
			foreach($recs as $r){
				if($token !== '' && strcasecmp($r['name'], $token) === 0){ $hit = true; break; }
			}
			if($hit || $token === '' || microtime(true) >= $waitUntil){ break; }
			usleep(500000);
		} while(true);
		ksort($recs);

		// The named page first, in full - that's the record being worked on.
		foreach($recs as $r){
			if($token !== '' && strcasecmp($r['name'], $token) === 0){
				$inventory['page'][] = $r;
			}
		}
		foreach($inventory['page'] as $r){
			step("     {$r['table']}: {$r['name']}  (id {$r['id']})");
			ksort($r['files']);
			foreach($r['files'] as $field => $info){
				step(sprintf('       %-12s %7d bytes  %s', $field, $info['size'], $info['path']));
			}
		}
		if($token !== '' && !count($inventory['page'])){
			step("     ⚠  no record named '$token' on disk yet.");
		}

		// Everything else, one compact line per record.
		$others = [];
		foreach($recs as $key => $r){
			$isPage = false;
			foreach($inventory['page'] as $p){ if($p['table'] === $r['table'] && $p['name'] === $r['name']){ $isPage = true; } }
			if(!$isPage){ $others[] = $r; }
		}
		$inventory['others'] = $others;
		if(count($others)){
			step('     other records on disk (' . count($others) . '):');
			$shown = array_slice($others, 0, $invMax);
			foreach($shown as $r){
				step("       {$r['table']}/{$r['name']} (id {$r['id']}) [" . implode(',', array_keys($r['files'])) . ']');
			}
			$inventory['truncated'] = count($others) - count($shown);
			if($inventory['truncated'] > 0){
				step("       ... and {$inventory['truncated']} more not listed (--inv-max=N to raise, --no-inventory to skip)");
			}
		}
	}
}

// ---- summary --------------------------------------------------------------
step('');
step($localMode
	? "Ready. Local framework mode - edit repo files directly. Optionally resolve 'localhost' to a db_id via the wamcp `databases` tool."
	: "Ready. wamcp has no setdb/session-default database - resolve '$alias' to a\n"
	. "  db_id via the wamcp `databases` tool, then pass that db_id on every wamcp call.\n"
	. "  (the wamcp id often differs from the postedit alias - e.g. '{$alias}_mysql';\n"
	. "   if '$alias' isn't found, list ids with the wamcp `databases` tool.)");
if(!$noChrome){
	step("Then read the screenshot" . ($shotWritten ? " at:\n  $shotWritten" : " (re-run with --shot=PATH)."));
} elseif(!$noBrowserGiven){
	step("No browser was launched (the default). To look at the page, re-run with --browse,\n"
		. "  or --shot=PATH to launch it and capture a screenshot too.");
}
if($LOGFILE){ step("Output copied to: $LOGFILE"); }

if($jsonMode){
	out(json_encode([
		'alias' => $alias, 'host' => $host, 'page' => $page, 'url' => $url,
		'browser' => $browser, 'port' => $port, 'insecure' => $insecure,
		'chrome_up' => $chromeUp || $confirmed,               // kept named for compat; means "browser was up"
		'watcher_pid' => $watcherPid, 'watcher_launched' => (!$watcherPid && $doWatch && !$localMode),
		'watcher_launch' => $watcherLaunch,                  // 'wt-tab' | 'window' | null (not launched)
		'filters' => $filters, 'watcher_running_filters' => $watcherRunningFilters,
		'target_confirmed' => $confirmed, 'chrome_skipped' => $noChrome,
		'browser_requested' => $wantBrowser && !$noBrowserGiven,   // false = default browser-less run
		'shot' => $shotWritten,
			'tab_id' => $matchedTarget ? ($matchedTarget['id'] ?? null) : null, 'tab_state_file' => $tabStateFile,
		'log' => $LOGFILE, 'inventory' => $inventory,
	]));
}

exit($noChrome || $confirmed ? 0 : 2);
