<?php
/**
 * workon.php - one-shot prep for "work on {site} {page}" PostEdit sessions.
 *
 * Does every step that would otherwise each trigger a separate permission
 * prompt, in a single process:
 *   1. Resolve {alias} -> host from postedit/postedit.xml (build the site URL).
 *   2. Ensure a debug Chrome is up on the debug port and showing the page
 *      (reuse an already-running debug instance; only launch if none).
 *   3. Ensure the PostEdit watcher for {alias} is running (launch if not),
 *      filtered to the named page so it only syncs/watches that page.
 *   4. Confirm the Chrome target for the page exists.
 *   5. (optional) Capture a mobile screenshot via Node CDP + a 1px reflow nudge.
 *
 * It does NOT call the wamcp `setdb` MCP tool - that is a separate MCP call the
 * assistant makes itself. Everything else lives here so the whole routine is a
 * single approval.
 *
 * Usage:
 *   php workon.php <alias> [page] [options]
 *
 * Options:
 *   --page=NAME        page to open (overrides positional; default: index)
 *   --port=N           Chrome debug port (default: 9222)
 *   --width=N          screenshot viewport width (default: 390)
 *   --shot=PATH        write a screenshot PNG to PATH (skipped if omitted)
 *   --no-watcher       do not launch the PostEdit watcher if it's missing
 *   --filter=a,b       explicit watcher filter(s) (default: the named page)
 *   --no-filter        watcher watches ALL records, not just the named page
 *   --no-shot          never screenshot (default when --shot not given)
 *   --chrome=PATH      explicit Chrome executable
 *   --profile=PATH     Chrome debug --user-data-dir (default: temp/wasql-chrome-debug)
 *   --json             emit a machine-readable JSON summary as the last line
 *
 * Exit code 0 on success, non-zero if the page target could not be confirmed.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$IS_WIN = (stripos(PHP_OS, 'WIN') === 0);
$ROOT   = __DIR__;                       // repo root (this file lives there)
$XML    = $ROOT . DIRECTORY_SEPARATOR . 'postedit' . DIRECTORY_SEPARATOR . 'postedit.xml';

// ---- tiny helpers ---------------------------------------------------------
function out($msg){ fwrite(STDOUT, $msg . PHP_EOL); }
function err($msg){ fwrite(STDERR, $msg . PHP_EOL); }
function fail($msg){ err('ERROR: ' . $msg); exit(1); }

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

/** Launch a detached process on Windows via `start`, or `nohup ... &` elsewhere. */
function launchDetached($cmd, $isWin, $title = ''){
	if($isWin){
		// `start "title" program args` - the empty/first quoted arg is the window title.
		$full = 'start "' . $title . '" ' . $cmd;
		pclose(popen($full, 'r'));
	} else {
		pclose(popen('nohup ' . $cmd . ' >/dev/null 2>&1 &', 'r'));
	}
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
if($alias === null){ fail('usage: php workon.php <alias> [page] [--options]'); }
if(isset($opts['page'])){ $page = $opts['page']; }
$pageGiven = ($page !== null && $page !== '');
if(!$pageGiven){ $page = 'index'; }

$port    = isset($opts['port'])  ? (int)$opts['port']  : 9222;
$width   = isset($opts['width']) ? (int)$opts['width'] : 390;
$doShot  = isset($opts['shot']) && !isset($opts['no-shot']);
$shotOut = $doShot ? $opts['shot'] : null;
$doWatch = !isset($opts['no-watcher']);
$profile = isset($opts['profile']) ? $opts['profile']
	: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql-chrome-debug';

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

out("• alias   : $alias" . ($localMode ? '  (local framework mode)' : ''));
out("• host    : $host" . ($insecure ? ' (self-signed)' : ''));
out("• url     : $url");

// ---- 2. ensure debug Chrome ----------------------------------------------
// Probe a few times before concluding Chrome is absent: a warm instance answers
// instantly, but a busy/just-started one can miss a single 3s probe, which would
// otherwise make us spawn a redundant duplicate instance.
// Use 127.0.0.1, NOT localhost: on Windows, PHP's stream wrapper tries IPv6 ::1
// first (which Chrome doesn't listen on) and stalls ~12s before falling back to
// IPv4. An IP literal is fast and Chrome's DNS-rebind check still accepts it.
$jsonUrl  = "http://127.0.0.1:$port/json";
$targets  = null;
for($i = 0; $i < 4; $i++){
	$targets = httpGet($jsonUrl, 3);
	if($targets !== null){ break; }
	usleep(500000);
}
$chromeUp = ($targets !== null);

/** Does any debug target's URL contain the host? */
$targetForHost = function($json, $host){
	if(!$json){ return false; }
	$list = json_decode($json, true);
	if(!is_array($list)){ return false; }
	foreach($list as $t){
		if(!empty($t['url']) && stripos($t['url'], $host) !== false){ return true; }
	}
	return false;
};

if($chromeUp){
	out("• chrome  : debug instance already up on port $port");
	if(!$targetForHost($targets, $host)){
		// Open the page as a new tab in the existing debug instance.
		$new = httpGet($jsonUrl . '/new?' . $url, 3);         // legacy GET form
		if($new === null){
			// Newer Chrome requires PUT; fall back to curl if available.
			@exec('curl -s -X PUT "' . $jsonUrl . '/new?' . $url . '" 2>&1');
		}
		out("• chrome  : opened new tab -> $url");
	} else {
		out("• chrome  : existing tab already on $host");
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
	out("• chrome  : launched debug instance (port $port, profile $profile)");
}

// ---- 3. ensure the PostEdit watcher --------------------------------------
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

$watcherPid = null; $watcherCmd = ''; $watcherRunningFilters = null;
if($localMode){
	out("• watcher : n/a (local framework mode - no PostEdit)");
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
	out("• watcher : running (pid $watcherPid, filters: "
		. ($watcherRunningFilters === null ? 'unknown' : filterLabel($watcherRunningFilters)) . ")");
	// A watcher already up was started with whatever filters it was started with -
	// we do NOT restart it, because postedit.php's startup re-sync is destructive
	// (backs up + deletes + re-downloads the working files). Just flag the mismatch.
	if(is_array($watcherRunningFilters) && $watcherRunningFilters != $filters){
		out("  ⚠  wanted filters: " . filterLabel($filters) . " - the running watcher differs.");
		if(count($filters) && !count($watcherRunningFilters)){
			out("     (it's watching everything, which still covers '" . implode("','", $filters) . "')");
		} else {
			out("     Stop it (Ctrl-C in its console) and re-run workon.php to re-filter.");
		}
	}
} elseif(!$doWatch){
	out("• watcher : NOT running (left alone; --no-watcher)");
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
		launchDetached('cmd /k "' . $bat . '"', true, 'postedit-' . $alias);
	} else {
		launchDetached('"' . $php . '" ' . $ROOT . '/postedit/postedit.php ' . $alias . $filterArgs, false);
	}
	out("• watcher : launched for '$alias' (filters: " . filterLabel($filters) . ")");
	if(count($filters)){
		out("     Only records whose name contains '" . implode("' or '", $filters) . "' are synced/watched");
		out("     (that includes _templates - re-run with --no-filter to work on the template too).");
	}
	out("  ⚠  startup re-syncs from the DB: it backs up postEditFiles/$alias to");
	out("     {$alias}_bak, deletes the working files, and re-downloads them fresh.");
	out("     Any un-synced local edits are in {$alias}_bak.");
}
}

// ---- 4. confirm the page target ------------------------------------------
$confirmed = false;
for($i = 0; $i < 30; $i++){                 // up to ~30s (cold Chrome start); breaks early once up
	$targets = httpGet($jsonUrl);
	if($targetForHost($targets, $host)){ $confirmed = true; break; }
	sleep(1);
}
out($confirmed
	? "• target  : confirmed on port $port ✓"
	: "• target  : NOT confirmed after wait (check Chrome manually) ✗");

// ---- 5. optional screenshot ----------------------------------------------
$shotWritten = null;
if($doShot && $confirmed){
	$node = null;
	if($IS_WIN){
		$nc = 'C:\\Program Files\\nodejs\\node.exe';
		$node = is_file($nc) ? $nc : 'node';
	} else { $node = 'node'; }

	// Write the CDP screenshot helper (mobile emulation + 1px reflow nudge) to a temp file.
	$shotJs = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wasql_shot.js';
	file_put_contents($shotJs, <<<'JS'
const [,, PORT, URL, OUT, W] = process.argv;
const width = parseInt(W || '390', 10);
const fs = require('fs');
async function main() {
  const list = await (await fetch(`http://127.0.0.1:${PORT}/json`)).json();
  const page = list.find(t => t.type === 'page' && t.webSocketDebuggerUrl && /https?:/.test(t.url));
  const ws = new WebSocket(page.webSocketDebuggerUrl.replace('localhost', '127.0.0.1'));
  let id = 0; const pending = new Map();
  const send = (m, p = {}) => new Promise(res => { const i = ++id; pending.set(i, res); ws.send(JSON.stringify({ id: i, method: m, params: p })); });
  ws.addEventListener('message', ev => { const msg = JSON.parse(ev.data); if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg.result); pending.delete(msg.id); } });
  await new Promise(res => ws.addEventListener('open', res));
  const metrics = w => send('Emulation.setDeviceMetricsOverride', { width: w, height: 844, deviceScaleFactor: 2, mobile: true });
  await send('Page.enable'); await send('Runtime.enable');
  await metrics(width);
  await send('Page.navigate', { url: URL });
  await new Promise(r => setTimeout(r, 2500));
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
	$cmd = '"' . $node . '" "' . $shotJs . '" ' . $port . ' "' . $shotUrl . '" "' . $shotOut . '" ' . $width;
	$so  = @shell_exec($cmd . ' 2>&1');
	if(is_file($shotOut)){
		$shotWritten = $shotOut;
		out("• shot    : $shotOut");
	} else {
		out("• shot    : failed (" . trim((string)$so) . ")");
	}
}

// ---- summary --------------------------------------------------------------
out('');
out($localMode
	? "Ready. Local framework mode - edit repo files directly. Optionally: setdb localhost (wamcp)"
	: "Ready. In the assistant, set the DB with: setdb $alias   (wamcp)");
out("Then read the screenshot" . ($shotWritten ? " at:\n  $shotWritten" : " (re-run with --shot=PATH)."));

if(isset($opts['json'])){
	out(json_encode([
		'alias' => $alias, 'host' => $host, 'page' => $page, 'url' => $url,
		'port' => $port, 'insecure' => $insecure,
		'chrome_up' => $chromeUp || $confirmed,
		'watcher_pid' => $watcherPid, 'watcher_launched' => (!$watcherPid && $doWatch && !$localMode),
		'filters' => $filters, 'watcher_running_filters' => $watcherRunningFilters,
		'target_confirmed' => $confirmed, 'shot' => $shotWritten,
	]));
}

exit($confirmed ? 0 : 2);
