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
 *   3. Ensure a debug Chrome is up on the debug port and showing the page
 *      (reuse an already-running debug instance; only launch if none).
 *   4. Confirm the Chrome target for the page exists (retrying the tab-open,
 *      and printing the open tabs if it still fails).
 *   5. (optional) Capture a mobile screenshot via Node CDP + a 1px reflow nudge,
 *      creating the output directory if needed.
 *   6. Print a mirror inventory: the named page's record id and the local path
 *      + size of each of its fields, plus the other records on disk.
 *
 * It does NOT call the wamcp `setdb` MCP tool - that is a separate MCP call the
 * assistant makes itself. Everything else lives here so the whole routine is a
 * single approval.
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

AFTERWARDS (the assistant does these - workon.php cannot)
  * setdb <name>     wamcp MCP call. The wamcp DB name often differs from the
                     postedit alias (e.g. dexpdq -> dexpdq_mysql); if the alias
                     isn't found, list names with the wamcp `databases` tool.
  * Read the PNG     the screenshot is only written, never displayed.

GOTCHAS
  * NEVER pipe this through tail/head - it launches detached children and a
    pager will buffer forever, showing nothing. Redirect to a file instead.
    Every run also copies its output to temp/wasql-workon-{alias}.log.
  * Exit code: 0 = target confirmed, 2 = not confirmed, 1 = usage/setup error.
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
 *     hazard launchDetached() below has to work around with NUL redirection.
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
 * Launch a detached process on Windows via `start`, or `nohup ... &` elsewhere.
 * Explicitly redirects the spawned process's own stdio to the null device via
 * proc_open() instead of popen(), which shares THIS PHP process's real stdio.
 * On Windows that stdio is the CALLER's pipe (e.g. the tool that ran
 * `php workon.php ...`); plain `start` doesn't sever it, so a long-lived
 * grandchild (Chrome, the watcher) inherits a duplicate handle to that pipe
 * and keeps it open forever - the caller's read never sees EOF, which is why
 * callers used to have to redirect workon.php's own output to a file. Routing
 * the immediate child's stdio to NUL/dev-null here means anything it spawns
 * inherits NUL, not the caller's pipe, so the caller gets a clean EOF.
 */
function launchDetached($cmd, $isWin, $title = ''){
	if($isWin){
		// `start "title" program args` - the empty/first quoted arg is the window
		// title. No `/b`: the watcher deliberately gets its own visible console
		// window, and Chrome is a GUI app that ignores the window-title anyway.
		$full = 'start "' . $title . '" ' . $cmd;
		$null = 'NUL';
	} else {
		$full = 'nohup ' . $cmd . ' >/dev/null 2>&1 &';
		$null = '/dev/null';
	}
	$spec = [0 => ['file', $null, 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']];
	$p = @proc_open($full, $spec, $pipes);
	if(is_resource($p)){ proc_close($p); }
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

$port    = isset($opts['port'])  ? (int)$opts['port']  : 9222;
$width   = isset($opts['width']) ? (int)$opts['width'] : 390;
// --reshoot=URL is a lightweight follow-up screenshot during an already-open
// session (a different page, or the same page after an edit) - it implies a
// shot even without an explicit --shot, so it needs to be known before $doShot
// is decided below.
$reshootGiven = isset($opts['reshoot']) && $opts['reshoot'] !== true;
if(isset($opts['reshoot']) && !$reshootGiven){ fail('--reshoot needs a URL, e.g. --reshoot=https://host/page'); }
$noChrome = isset($opts['no-chrome']);
if($noChrome && $reshootGiven){ fail('--no-chrome and --reshoot are contradictory (reshoot needs Chrome).'); }
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
$profile = isset($opts['profile']) ? $opts['profile']
	: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql-chrome-debug';

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

// ---- 3. ensure debug Chrome ------------------------------------------------
$matchedTarget = null;                  // set once a target is confirmed; drives step 5's screenshot
$confirmed     = null;                  // null = not attempted (--no-chrome); bool once step 4 runs
$chromeUp      = false;
if($noChrome){
	step("• chrome  : skipped (--no-chrome - watcher status only)");
} else {
// Probe a few times before concluding Chrome is absent: a warm instance answers
// instantly, but a busy/just-started one can miss a single 3s probe, which would
// otherwise make us spawn a redundant duplicate instance.
// Use 127.0.0.1, NOT localhost: on Windows, PHP's stream wrapper tries IPv6 ::1
// first (which Chrome doesn't listen on) and stalls ~12s before falling back to
// IPv4. An IP literal is fast and Chrome's DNS-rebind check still accepts it.
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
	step("• chrome  : debug instance already up on port $port");
	$matchedTarget = findTarget($targets, $host, $rememberedTabId);
	if(!$matchedTarget){
		// Open the page as a new tab in the existing debug instance.
		step(newTab($jsonUrl, $url)
			? "• chrome  : opened new tab -> $url"
			: "• chrome  : could NOT open a new tab (tried PUT, GET and curl on /json/new)");
	} else {
		step("• chrome  : existing tab already on $host"
			. (($rememberedTabId && $matchedTarget['id'] === $rememberedTabId) ? ' (this session\'s remembered tab)' : ''));
	}
} else {
	// Locate Chrome and launch a fresh detached debug instance.
	$chrome = isset($opts['chrome']) ? $opts['chrome'] : null;
	if($chrome === null){
		if($IS_WIN){
			$cands = [
				'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
				'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
			];
			foreach($cands as $c){ if(is_file($c)){ $chrome = $c; break; } }
			if($chrome === null){
				$reg = @shell_exec('reg query "HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths\\chrome.exe" /ve 2>nul');
				if($reg && preg_match('/REG_SZ\s+(.+\.exe)/i', $reg, $rm)){ $chrome = trim($rm[1]); }
			}
		} else {
			foreach(['/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
			         '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser'] as $c){
				if(is_file($c)){ $chrome = $c; break; }
			}
		}
	}
	if($chrome === null){ fail('could not locate the Chrome executable (pass --chrome=PATH)'); }
	if(!is_dir($profile)){ @mkdir($profile, 0777, true); }

	$args = '--remote-debugging-port=' . $port
	      . ' --user-data-dir="' . $profile . '"'
	      . ' --no-first-run --no-default-browser-check --new-window "' . $url . '"';
	launchDetached('"' . $chrome . '" ' . $args, $IS_WIN, 'wasql-chrome');
	step("• chrome  : launched debug instance (port $port, profile $profile)");
}

// ---- 4. confirm the page target ------------------------------------------
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
if($doShot && $confirmed){
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
async function main() {
  const list = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
  // Target the exact confirmed tab by id when given - a shared debug Chrome
  // instance can have other sessions' tabs open, so "the first page tab in
  // the list" is order-dependent and can grab the wrong session's tab.
  // TARGET_ID absent (older caller) falls back to that old heuristic.
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
main().catch(e => { console.error(e); process.exit(1); });
JS);
	$shotUrl = $url . (strpos($url, '?') === false ? '?cb=1' : '&cb=1');
	$cmd = '"' . $node . '" "' . $shotJs . '" ' . $port . ' "' . $shotUrl . '" "' . $shotOut . '" ' . $width
	     . ($matchedTarget && !empty($matchedTarget['id']) ? ' "' . $matchedTarget['id'] . '"' : '');
	$so  = @shell_exec($cmd . ' 2>&1');
	if(is_file($shotOut)){
		$shotWritten = $shotOut;
		step("• shot    : $shotOut");
	} else {
		step("• shot    : failed (" . trim((string)$so) . ")");
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
if(!$localMode && !isset($opts['no-inventory']) && !$reshootGiven && !$noChrome){
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
	? "Ready. Local framework mode - edit repo files directly. Optionally: setdb localhost (wamcp)"
	: "Ready. In the assistant, set the DB with: setdb $alias   (wamcp)\n"
	. "  (the wamcp name often differs from the postedit alias - e.g. '{$alias}_mysql';\n"
	. "   if '$alias' isn't found, list names with the wamcp `databases` tool.)");
if(!$noChrome){
	step("Then read the screenshot" . ($shotWritten ? " at:\n  $shotWritten" : " (re-run with --shot=PATH)."));
}
if($LOGFILE){ step("Output copied to: $LOGFILE"); }

if($jsonMode){
	out(json_encode([
		'alias' => $alias, 'host' => $host, 'page' => $page, 'url' => $url,
		'port' => $port, 'insecure' => $insecure,
		'chrome_up' => $chromeUp || $confirmed,
		'watcher_pid' => $watcherPid, 'watcher_launched' => (!$watcherPid && $doWatch && !$localMode),
		'watcher_launch' => $watcherLaunch,                  // 'wt-tab' | 'window' | null (not launched)
		'filters' => $filters, 'watcher_running_filters' => $watcherRunningFilters,
		'target_confirmed' => $confirmed, 'chrome_skipped' => $noChrome, 'shot' => $shotWritten,
			'tab_id' => $matchedTarget ? ($matchedTarget['id'] ?? null) : null, 'tab_state_file' => $tabStateFile,
		'log' => $LOGFILE, 'inventory' => $inventory,
	]));
}

exit($noChrome || $confirmed ? 0 : 2);
