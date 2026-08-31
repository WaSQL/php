<?php
/*
	Admin  Tests > Languages  tab.

	WaSQL can run ~13 languages inline via <?token ... ?> islands (evalPHP() in php/common.php,
	dispatch table ~L10597 -> commonGetLangInfo() -> eval<Lang>Code()).  This tab gives every
	one of them its own sub-tab with: live version probe, an explanation of how the island runs,
	a hello-world demo, the WaSQL globals/accessor bridge, and (for languages that ship a sibling
	driver dir) a live dbQueryResults() call.

	Everything is generated from testLanguageDefs() - add a language in one place.

	Key mechanic: evalPHP() scans a field for islands ONCE, so islands emitted from a helper's
	return value are never executed.  We therefore run each snippet ourselves through
	testLangRun() -> eval<Lang>Code() and splice the finished output into the HTML.
*/

//---------- begin function testLanguageDefs
/**
* @describe Ordered definition of every embedded language shown on the admin Tests > Languages tab.
* @return array keyed by sub-tab key. Fields: name, tokens[], comment, dir (sibling driver dir or ''),
*   blurb (HTML), hello, bridge, db (snippet or ''), install (HTML). {CONN} in a snippet is replaced
*   with the running site's default DB connection name before it is shown / executed.
* @usage $defs=testLanguageDefs();
*/
function testLanguageDefs(){
	return array(

'php'=>array(
	'name'=>'PHP','tokens'=>array('?php','?='),'comment'=>'//','dir'=>'',
	'blurb'=>'PHP is the native language &ndash; a page\'s <code>body</code>, <code>controller</code>, <code>functions</code> and every <code>&lt;view:&gt;</code> block are PHP that <code>evalPHP()</code> runs. Use <code>&lt;?php &hellip; ?&gt;</code> for statements or <code>&lt;?= &hellip;; ?&gt;</code> to echo a single value (keep the semicolon). Data access is the native <code>getDBRecord()/getDBRecords()/addDBRecord()&hellip;</code> family.',
	'hello'=><<<'EOT'
echo 'Hello, world!';
EOT,
	'bridge'=><<<'EOT'
echo 'user via helper:     '.userValue('username')."\n";
echo 'user id via helper:  '.userValue('_id')."\n";
echo 'default db (CONFIG):  '.$CONFIG['database'];
EOT,
	'db'=><<<'EOT'
$rows = getDBRecords(array(
  '-table'  => '_users',
  '-fields' => 'username',
  '-order'  => 'username',
  '-limit'  => 3,
));
if(!is_array($rows)){ echo 'query error: '.$rows; }
else{ foreach($rows as $r){ echo $r['username']."\n"; } }
EOT,
	'install'=>'Ships with WaSQL. Nothing to install.',
),

'python'=>array(
	'name'=>'Python','tokens'=>array('?python','?py'),'comment'=>'#','dir'=>'python/',
	'blurb'=>'<code>&lt;?python &hellip; ?&gt;</code> shells out to <code>python3</code>. WaSQL writes a temp script, injects your globals, imports <code>common</code>, <code>config</code> and <code>db</code> from the <code>python/</code> sibling dir, runs it, and splices <b>stdout</b> back in place of the island &ndash; so <code>print(&hellip;)</code>, never <code>return</code>. One fresh interpreter per island, per request.',
	'hello'=><<<'EOT'
print('Hello, world!')
EOT,
	'bridge'=><<<'EOT'
print('user via accessor:', wasql.user('username'))
print('default db:       ', wasql.config('database'))
EOT,
	'db'=><<<'EOT'
import db
rows = db.queryResults('{CONN}', 'SELECT username FROM _users ORDER BY username LIMIT 3')
for row in rows:
    print(row['username'])
EOT,
	'install'=>'Needs <code>python3</code> on <code>PATH</code>. DB drivers as needed: <code>pip install PyMySQL psycopg2-binary</code>. <code>db</code>, <code>common</code>, <code>config</code> are auto-imported into every island.',
),

'nodejs'=>array(
	'name'=>'NodeJs','tokens'=>array('?node','?nodejs'),'comment'=>'//','dir'=>'',
	'blurb'=>'<code>&lt;?node &hellip; ?&gt;</code> (or <code>&lt;?nodejs&gt;</code>) runs under <code>node</code>. Globals arrive as a generated <code>wasql</code> module &ndash; <code>wasql.user(&hellip;)</code>, <code>wasql.config(&hellip;)</code>, &hellip; Island <b>stdout</b> replaces the block. No <code>node/</code> driver dir yet, so hand DB work to PHP or a Python / Ruby island.',
	'hello'=><<<'EOT'
console.log('Hello, world!');
EOT,
	'bridge'=><<<'EOT'
console.log('user via accessor:', wasql.user('username'));
console.log('default db:       ', wasql.config('database'));
EOT,
	'db'=>'',
	'install'=>'Needs <code>node</code> on <code>PATH</code>.',
),

'perl'=>array(
	'name'=>'Perl','tokens'=>array('?perl','?pl'),'comment'=>'#','dir'=>'',
	'blurb'=>'<code>&lt;?perl &hellip; ?&gt;</code> (or <code>&lt;?pl&gt;</code>) runs under <code>perl</code>. Accessors are plain subs: <code>wasqlUser(\'username\')</code>, <code>wasqlConfig(&hellip;)</code>. Whatever you <code>print</code> to STDOUT replaces the island.',
	'hello'=><<<'EOT'
print "Hello, world!";
EOT,
	'bridge'=><<<'EOT'
print "user via accessor: ".wasqlUser('username')."\n";
print "default db:        ".wasqlConfig('database')."\n";
EOT,
	'db'=>'',
	'install'=>'Needs <code>perl</code> on <code>PATH</code>.',
),

'ruby'=>array(
	'name'=>'Ruby','tokens'=>array('?ruby','?rb'),'comment'=>'#','dir'=>'ruby/',
	'blurb'=>'<code>&lt;?ruby &hellip; ?&gt;</code> (or <code>&lt;?rb&gt;</code>) runs under <code>ruby</code>. The <code>ruby/</code> sibling dir gives you <code>USER</code>/<code>CONFIG</code>/&hellip; as native hashes plus <code>wasqlUser(&hellip;)</code>, and <code>dbQueryResults(conn, sql)</code> (alias <code>db_query_results</code>) with <code>results_as_table</code> / <code>results_as_csv</code> helpers. <code>puts</code> to fill the island.',
	'hello'=><<<'EOT'
puts 'Hello, world!'
EOT,
	'bridge'=><<<'EOT'
puts "user via accessor: #{wasqlUser('username')}"
puts "user via global:   #{USER['username']}"
puts "default db:        #{wasqlConfig('database')}"
EOT,
	'db'=><<<'EOT'
res = dbQueryResults('{CONN}', 'SELECT username FROM _users ORDER BY username LIMIT 3')
# res => { "columns" => [...], "rows" => [ {col=>val}, ... ], "count" => n }
res['rows'].each { |r| puts r['username'] }
EOT,
	'install'=>'Needs <code>ruby</code> on <code>PATH</code>. DB drivers: <code>gem install mysql2 pg sqlite3</code>. Helpers: <code>results_as_table</code>, <code>results_as_csv</code>.',
),

'lua'=>array(
	'name'=>'Lua','tokens'=>array('?lua'),'comment'=>'--','dir'=>'lua/',
	'blurb'=>'<code>&lt;?lua &hellip; ?&gt;</code> runs under <code>lua</code>. <code>wasqlUser(&hellip;)</code> &amp; friends are injected; <code>dbQueryResults(conn, sql)</code> comes from the <code>lua/</code> sibling dir (needs LuaSQL + a <code>luarocks</code> driver). <code>print(&hellip;)</code> replaces the block; comment marker is <code>--</code>.',
	'hello'=><<<'EOT'
print('Hello, world!')
EOT,
	'bridge'=><<<'EOT'
print('user via accessor: ' .. wasqlUser('username'))
print('default db:        ' .. wasqlConfig('database'))
EOT,
	'db'=><<<'EOT'
local res = dbQueryResults('{CONN}', 'SELECT username FROM _users ORDER BY username LIMIT 3')
-- res => { columns = {..}, rows = { {col=val,..}, .. }, count = n }
for _, row in ipairs(res.rows) do
  print(row.username)
end
EOT,
	'install'=>'Needs <code>lua</code> on <code>PATH</code>. DB: <code>luarocks install luasql-mysql</code> (or <code>-postgres</code>, <code>-sqlite3</code>).',
),

'groovy'=>array(
	'name'=>'Groovy','tokens'=>array('?groovy'),'comment'=>'//','dir'=>'groovy/',
	'blurb'=>'<code>&lt;?groovy &hellip; ?&gt;</code> runs under <code>groovy</code> (<code>groovy.bat</code> on Windows; <code>groovyclient</code> when a Groovy daemon is running, to skip JVM start-up). <code>wasqlUser("username")</code> etc. are injected; <code>dbQueryResults(&hellip;)</code> ships in <code>groovy/</code> (JDBC jar on the classpath). <code>println</code> replaces the island.',
	'hello'=><<<'EOT'
println "Hello, world!"
EOT,
	'bridge'=><<<'EOT'
println "user via accessor: " + wasqlUser("username")
println "default db:        " + wasqlConfig("database")
EOT,
	'db'=><<<'EOT'
import groovy.json.JsonSlurper
// db.queryResults returns a JSON string by default
def json = db.queryResults("{CONN}", "SELECT username FROM _users ORDER BY username LIMIT 3", [:])
new JsonSlurper().parseText(json).each { row -> println row.username }
EOT,
	'install'=>'Needs <code>groovy</code> on <code>PATH</code> (JDK + Groovy). The matching JDBC jar is auto-downloaded via Grape on first use.',
),

'julia'=>array(
	'name'=>'Julia','tokens'=>array('?julia','?jl'),'comment'=>'#','dir'=>'julia/',
	'blurb'=>'<code>&lt;?julia &hellip; ?&gt;</code> (or <code>&lt;?jl&gt;</code>) runs under <code>julia</code>. <code>wasqlUser("username")</code> accessors are injected; <code>dbQueryResults(&hellip;)</code> lives in the <code>julia/</code> sibling dir. <code>println(&hellip;)</code> replaces the block. The first island in a session pays Julia\'s JIT warm-up.',
	'hello'=><<<'EOT'
println("Hello, world!")
EOT,
	'bridge'=><<<'EOT'
println("user via accessor: ", wasqlUser("username"))
println("default db:        ", wasqlConfig("database"))
EOT,
	'db'=><<<'EOT'
using JSON3
# db.queryResults returns a JSON string by default
res = db.queryResults("{CONN}", "SELECT username FROM _users ORDER BY username LIMIT 3")
for row in JSON3.read(res)
    println(row["username"])
end
EOT,
	'install'=>'Needs <code>julia</code> on <code>PATH</code>. DB packages auto-install on first use (<code>MySQL</code> / <code>LibPQ</code> / <code>SQLite</code>).',
),

'r'=>array(
	'name'=>'R','tokens'=>array('?r','?rscript'),'comment'=>'#','dir'=>'R/',
	'blurb'=>'<code>&lt;?r &hellip; ?&gt;</code> (or <code>&lt;?rscript&gt;</code>) runs under <code>Rscript</code>. Accessors are upper-cased: <code>wasqlUSER(\'username\')</code>, <code>wasqlCONFIG(&hellip;)</code>. <code>dbQueryResults(&hellip;)</code> is in <code>R/</code> (needs <code>install.packages(\'RMySQL\')</code> etc.). <code>cat(&hellip;)</code> / <code>print(&hellip;)</code> replaces the island.',
	'hello'=><<<'EOT'
cat("Hello, world!")
EOT,
	'bridge'=><<<'EOT'
cat("user via accessor:", wasqlUSER("username"), "\n")
cat("default db:       ", wasqlCONFIG("database"), "\n")
EOT,
	'db'=><<<'EOT'
rows <- dbQueryResults("{CONN}", "SELECT username FROM _users ORDER BY username LIMIT 3")
# rows is a data.frame
print(rows$username)
EOT,
	'install'=>'Needs <code>Rscript</code> on <code>PATH</code>. DB: <code>install.packages(c("RMySQL","RPostgres","RSQLite"))</code>.',
),

'tcl'=>array(
	'name'=>'Tcl','tokens'=>array('?tcl'),'comment'=>'#','dir'=>'Tcl/',
	'blurb'=>'<code>&lt;?tcl &hellip; ?&gt;</code> runs under <code>tclsh</code>. Accessors: <code>wasqlUSER key</code>, <code>wasqlCONFIG key</code>. <code>dbQueryResults conn sql</code> is in the <code>Tcl/</code> sibling dir (needs the <code>tdbc::*</code> packages). <code>puts</code> replaces the block.',
	'hello'=><<<'EOT'
puts "Hello, world!"
EOT,
	'bridge'=><<<'EOT'
puts "user via accessor: [wasqlUSER username]"
puts "default db:        [wasqlCONFIG database]"
EOT,
	'db'=><<<'EOT'
array set res [dbQueryResults {CONN} {SELECT username FROM _users ORDER BY username LIMIT 3}]
for {set i 0} {$i < $res(rows)} {incr i} {
  puts $res($i,username)
}
EOT,
	'install'=>'Needs <code>tclsh</code> on <code>PATH</code>. DB: the <code>tdbc::mysql</code> / <code>tdbc::postgres</code> / <code>tdbc::sqlite3</code> packages.',
),

'powershell'=>array(
	'name'=>'PowerShell','tokens'=>array('?powershell','?pwsh','?ps1'),'comment'=>'#','dir'=>'',
	'blurb'=>'<code>&lt;?powershell &hellip; ?&gt;</code> (also <code>&lt;?pwsh&gt;</code> / <code>&lt;?ps1&gt;</code>) runs under <code>powershell</code>. <code>wasqlUser username</code> etc. are injected as functions. <code>Write-Output</code> / <code>Write-Host</code> replace the island. No <code>powershell/</code> DB dir &ndash; return to PHP for data.',
	'hello'=><<<'EOT'
Write-Output "Hello, world!"
EOT,
	'bridge'=><<<'EOT'
Write-Output ("user via accessor: " + (wasqlUser username))
Write-Output ("default db:        " + (wasqlConfig database))
EOT,
	'db'=>'',
	'install'=>'Ships with Windows. On Linux/macOS install <code>pwsh</code>.',
),

'bash'=>array(
	'name'=>'Bash','tokens'=>array('?bash'),'comment'=>'#','dir'=>'',
	'blurb'=>'<code>&lt;?bash &hellip; ?&gt;</code> runs under <code>bash</code>. Accessors are shell functions: <code>$(wasqlUser username)</code>. <code>echo</code> replaces the island. Ideal for calling system tools; no DB dir.',
	'hello'=><<<'EOT'
echo "Hello, world!"
EOT,
	'bridge'=><<<'EOT'
echo "user via accessor: $(wasqlUser username)"
echo "default db:        $(wasqlConfig database)"
EOT,
	'db'=>'',
	'install'=>'On <code>PATH</code> on Linux/macOS; on Windows use Git Bash / WSL <code>bash</code>.',
),

'vbscript'=>array(
	'name'=>'VBScript','tokens'=>array('?vbscript','?vbs'),'comment'=>"'",'dir'=>'',
	'blurb'=>'<code>&lt;?vbscript &hellip; ?&gt;</code> (or <code>&lt;?vbs&gt;</code>) runs under <code>cscript //Nologo</code> (Windows only). <code>wasqlUser("username")</code> is injected. <code>WScript.Echo</code> replaces the block.',
	'hello'=><<<'EOT'
WScript.Echo "Hello, world!"
EOT,
	'bridge'=><<<'EOT'
WScript.Echo "user via accessor: " & wasqlUser("username")
WScript.Echo "default db:        " & wasqlConfig("database")
EOT,
	'db'=>'',
	'install'=>'Ships with Windows (<code>cscript.exe</code>). Not available on Linux/macOS.',
),

	);
}

//---------- begin function testLangConn
/**
* @describe The running site's default DB connection name (used in the Database-access demos).
* @return string
*/
function testLangConn(){
	global $CONFIG;
	return (isset($CONFIG['database']) && strlen($CONFIG['database'])) ? $CONFIG['database'] : 'default';
}

//---------- begin function testLangRun
/**
* @describe Executes a snippet in one of WaSQL's embedded interpreters and reports success/failure.
* @param key string - a testLanguageDefs() key (php, python, nodejs, perl, ruby, lua, groovy, julia, r, tcl, powershell, bash, vbscript)
* @param code string - the source to run
* @return array ok (bool - false when the interpreter is missing or the script errored), out (string - trimmed output or error text)
* @usage $res=testLangRun('python',"print('hi')");
*/
function testLangRun($key,$code){
	$key=strtolower(trim($key));
	if(!strlen(trim($code))){return array('ok'=>true,'out'=>'');}
	if($key=='php'){
		$out=evalPHP('<'.'?php '.$code.PHP_EOL.' ?'.'>');
		return array('ok'=>!stringContains($out,'evalPHP Error'),'out'=>trim($out));
	}
	$lang=commonGetLangInfo($key);
	if(!is_array($lang) || !count($lang)){
		return array('ok'=>false,'out'=>"Unknown language: {$key}");
	}
	$lang['evalcode_md5']=md5($key.'|'.$code.'|'.microtime());
	$out='';
	switch(strtolower($lang['name'])){
		case 'python':		$out=evalPythonCode($lang,$code);	break;
		case 'nodejs':		$out=evalNodejsCode($lang,$code);	break;
		case 'perl':		$out=evalPerlCode($lang,$code);		break;
		case 'ruby':		$out=evalRubyCode($lang,$code);		break;
		case 'lua':			$out=evalLuaCode($lang,$code);		break;
		case 'groovy':		$out=evalGroovyCode($lang,$code);	break;
		case 'julia':		$out=evalJuliaCode($lang,$code);		break;
		case 'r':			$out=evalRCode($lang,$code);			break;
		case 'tcl':			$out=evalTclCode($lang,$code);		break;
		case 'powershell':	$out=evalPowershellCode($lang,$code);break;
		case 'bash':		$out=evalBashCode($lang,$code);		break;
		case 'vbscript':	$out=evalVBScriptCode($lang,$code);	break;
		default:
			return array('ok'=>false,'out'=>"No evaluator wired for {$lang['name']}");
	}
	$out=is_string($out)?$out:json_encode($out);
	//every eval*Code failure path returns one of these markers (see php/common.php)
	$failed=stringContains($out,'!! Embedded')
		|| stringContains($out,'Script Error. Return Code')
		|| stringContains($out,'embeded script failed')
		|| stringContains($out,'Import Error:');
	return array('ok'=>!$failed,'out'=>trim($out));
}

//---------- begin function testLanguagesStyle
/**
* @describe The <style> block for the Languages tab. Injected once by <view:languages>; a <style> in
*   AJAX-loaded innerHTML is honored (unlike <script> - see CLAUDE.md gotcha 14), so panels need not carry it.
* @return string
*/
function testLanguagesStyle(){
	return <<<'EOT'
<style>
.test-lang-tabs{display:flex;flex-wrap:wrap;gap:4px;border-bottom:2px solid #d0d0d0;margin-bottom:18px;padding:0;list-style:none;}
.test-lang-tabs li a{display:block;padding:6px 14px;border:1px solid transparent;border-bottom:none;border-radius:6px 6px 0 0;color:#555;text-decoration:none;font-weight:600;font-size:0.9rem;}
.test-lang-tabs li a:hover{background:#ececec;color:#000;}
.test-lang-tabs li.is-active a,.test-lang-tabs li.active a{background:#fff;border-color:#d0d0d0;color:#000;position:relative;top:2px;}
.test-lang-panel{animation:testLangFade .18s ease-in;}
@keyframes testLangFade{from{opacity:0;transform:translateY(3px);}to{opacity:1;transform:none;}}
.test-lang-head{display:flex;align-items:baseline;flex-wrap:wrap;gap:10px;border-bottom:1px solid #e2e2e2;padding-bottom:8px;}
.test-lang-head h2{margin:0;font-size:1.7rem;}
.test-lang-badge{font-size:0.8rem;font-weight:700;padding:2px 9px;border-radius:11px;font-family:monospace;}
.test-lang-badge.is-ok{background:#e6f4ea;color:#1a7f37;}
.test-lang-badge.is-off{background:#f1f1f1;color:#999;}
.test-lang-meta{margin:6px 0 0;font-size:0.82rem;color:#777;}
.test-lang-meta code{background:#f4f4f4;padding:1px 5px;border-radius:3px;font-size:0.95em;}
.test-lang-blurb{margin:12px 0 20px;line-height:1.55;max-width:70ch;}
.test-lang-blurb code{background:#f4f4f4;padding:1px 5px;border-radius:3px;}
.test-lang-section{margin:0 0 26px;}
.test-lang-section h3{margin:0 0 10px;font-size:1.05rem;color:#333;border-left:3px solid #7a9;padding-left:8px;}
.test-lang-section.is-note h3{border-left-color:#ccc;}
.test-lang-cols{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media (max-width:1100px){.test-lang-cols{grid-template-columns:1fr;}}
.test-lang-col-label{font-size:0.72rem;letter-spacing:.06em;text-transform:uppercase;color:#999;margin-bottom:4px;font-weight:700;}
.test-lang-code,.test-lang-out{background:#1e1e2e;color:#e0e0e0;border-radius:7px;padding:12px 14px;font-family:'Cascadia Code',Consolas,monospace;font-size:0.82rem;white-space:pre-wrap;overflow-x:auto;line-height:1.45;margin:0;}
.test-lang-out{background:#12241a;color:#c8e6c9;}
.test-lang-out.is-off{background:#2a2320;color:#e6b8a8;}
.test-lang-tok{background:#f4f4f4;border-radius:3px;padding:1px 6px;font-family:monospace;font-size:0.85rem;margin-right:5px;}
.test-lang-install{background:#fafafa;border:1px solid #ececec;border-radius:7px;padding:12px 14px;font-size:0.88rem;line-height:1.5;}
.test-lang-install code{background:#eee;padding:1px 5px;border-radius:3px;}
details.test-lang-err{margin-top:8px;}
details.test-lang-err summary{cursor:pointer;font-size:0.78rem;color:#999;}
details.test-lang-err pre{background:#2a2320;color:#e6b8a8;border-radius:6px;padding:10px;font-size:0.74rem;white-space:pre-wrap;margin-top:6px;max-height:280px;overflow:auto;}
</style>
EOT;
}

//---------- begin function testLanguagesTabBar
/**
* @describe The horizontal sub-tab bar for the Languages tab - one wacss.nav tab per language, PHP active.
* @param active string - the key of the tab to mark active (default 'php')
* @return string
*/
function testLanguagesTabBar($active='php'){
	$active=strtolower($active);
	$lis='';
	foreach(testLanguageDefs() as $key=>$d){
		$cls=($key==$active)?' class="is-active"':'';
		$lis.='<li'.$cls.'><a href="#lang-'.$key.'" data-tab="1" data-div="languages_content"'
			.' data-nav="/php/admin.php" data-_menu="test" data-test="languages" data-lang="'.$key.'"'
			.' onclick="return wacss.nav(this);">'.encodeHtml($d['name']).'</a></li>'.PHP_EOL;
	}
	return '<ul class="test-lang-tabs">'.PHP_EOL.$lis.'</ul>'.PHP_EOL;
}

//---------- begin function testLangDemoBlock
/**
* @describe Renders one "page source | live output" two-column demo for a language snippet.
* @param key   string - testLanguageDefs() key
* @param tok   string - island token to show around the source (e.g. '?python')
* @param code  string - the snippet (already {CONN}-substituted)
* @return string
*/
function testLangDemoBlock($key,$tok,$code){
	$code=trim($code);
	$src=encodeHtml('<'.$tok.PHP_EOL.$code.PHP_EOL.'?'.'>');
	$res=testLangRun($key,$code);
	if($res['ok']){
		$out='<pre class="test-lang-out">'.encodeHtml(strlen($res['out'])?$res['out']:'(no output)').'</pre>';
	}
	else{
		$err=$res['out'];
		if(commonStrlen($err)>1600){$err=commonSubstr($err,0,1600).' ...';}
		$out='<pre class="test-lang-out is-off">not available on this server</pre>'
			.'<details class="test-lang-err"><summary>why?</summary><pre>'.encodeHtml($err).'</pre></details>';
	}
	return '<div class="test-lang-cols">'
		.'<div><div class="test-lang-col-label">page source</div><pre class="test-lang-code">'.$src.'</pre></div>'
		.'<div><div class="test-lang-col-label">live output</div>'.$out.'</div>'
		.'</div>';
}

//---------- begin function testLanguagePanel
/**
* @describe Full content for one language sub-tab: version probe, how-it-works, hello world, the WaSQL
*   globals/accessor bridge, and (for languages with a sibling driver dir) a live dbQueryResults() call.
* @param key string - a testLanguageDefs() key; unknown/empty falls back to 'php'
* @return string
*/
function testLanguagePanel($key){
	$defs=testLanguageDefs();
	$key=strtolower(trim($key));
	if(!isset($defs[$key])){$key='php';}
	$d=$defs[$key];
	$conn=testLangConn();

	//version probe
	$verProbe=array(
		'php'=>'','python'=>"import platform;print('v'+platform.python_version())",
		'nodejs'=>'console.log(process.version)','perl'=>'print "v".$];',
		'ruby'=>"puts 'v'+RUBY_VERSION",'lua'=>'print(_VERSION)',
		'groovy'=>"println 'v'+GroovySystem.version",'julia'=>'println("v", VERSION)',
		'r'=>'cat(R.version.string)','tcl'=>'puts "v[info patchlevel]"',
		'powershell'=>'"v"+$PSVersionTable.PSVersion.ToString()','bash'=>'echo "v$BASH_VERSION"',
		'vbscript'=>'WScript.Echo "v" & ScriptEngineMajorVersion & "." & ScriptEngineMinorVersion',
	);
	if($key=='php'){$badge='<span class="test-lang-badge is-ok">v'.phpversion().'</span>';}
	else{
		$v=testLangRun($key,$verProbe[$key]);
		$badge=($v['ok'] && strlen($v['out']))
			? '<span class="test-lang-badge is-ok">'.encodeHtml(trim($v['out'])).'</span>'
			: '<span class="test-lang-badge is-off">not installed here</span>';
	}

	//island tokens (encodeHtml escapes < > " but passes &hellip; through unchanged)
	$toks='';
	foreach($d['tokens'] as $t){$toks.='<span class="test-lang-tok">'.encodeHtml('<'.$t.' &hellip; ?>').'</span>';}

	$meta='island: '.$toks.' &nbsp;&middot;&nbsp; comment: <code>'.encodeHtml($d['comment']).'</code>';
	if(strlen($d['dir'])){$meta.=' &nbsp;&middot;&nbsp; driver dir: <code>'.$d['dir'].'</code>';}

	//sections - PHP demos always render with the ?php token; other langs use their primary token
	$demoTok=($key=='php')?'?php':$d['tokens'][0];
	$hello=testLangDemoBlock($key,$demoTok,$d['hello']);
	$bridge=testLangDemoBlock($key,$demoTok,$d['bridge']);

	if($key=='php'){
		$dbsection='<div class="test-lang-section"><h3>3 &middot; Data access &mdash; native <code>getDBRecord() / getDBRecords()</code></h3>'
			.'<p class="test-lang-blurb" style="margin:0 0 12px;">PHP talks to the site DB directly through the framework\'s data layer &ndash; one options array, <code>-</code> keys are directives, dashless keys are <code>column = value</code>. Full family: <code>getDBRecord(s) / getDBCount / addDBRecord / editDBRecord / delDBRecord / databaseListRecords / executeSQL</code>.</p>'
			.testLangDemoBlock($key,$demoTok,$d['db'])
			.'</div>';
	}
	elseif(strlen($d['db'])){
		$dbcode=str_replace('{CONN}',$conn,$d['db']);
		//the dispatcher name and return shape are not uniform across the ports
		$dbcall=in_array($key,array('python','julia','groovy'))?'db.queryResults':'dbQueryResults';
		$shape=($key=='python')?'a list of row dicts'
			:(($key=='r')?'a data.frame'
			:((in_array($key,array('julia','groovy')))?'a JSON string (parse it)'
			:'<code>{columns, rows, count}</code>'));
		$dbsection='<div class="test-lang-section"><h3>3 &middot; Database access &mdash; <code>'.$dbcall.'('."'".encodeHtml($conn)."'".', &hellip;)</code></h3>'
			.'<p class="test-lang-blurb" style="margin:0 0 12px;">The <code>'.$d['dir'].'</code> sibling dir ships <code>db</code> + per-<code>dbtype</code> drivers. <code>'.$dbcall.'(conn, sql)</code> resolves the connection name against <code>config.xml</code> and loads only the driver it needs, on demand. '.ucfirst($key).' returns '.$shape.'. A failed island returns the red error block, not data, so check for it.</p>'
			.testLangDemoBlock($key,$d['tokens'][0],$dbcode)
			.'</div>';
	}
	else{
		$dbsection='<div class="test-lang-section is-note"><h3>3 &middot; Database access</h3>'
			.'<p class="test-lang-blurb" style="margin:0;">No <code>'.$key.'/</code> driver set ships for '.encodeHtml($d['name']).' yet. Do DB work in a Python / Ruby / Lua / R / Tcl / Julia / Groovy island, or return values to PHP and query there.</p></div>';
	}

	//section 2 blurb differs for native PHP vs a shelled-out island
	$bridgeBlurb=($key=='php')
		? 'In PHP these are just live superglobals/globals &ndash; <code>$USER</code>, <code>$CONFIG</code>, <code>$PAGE</code>, &hellip; (<code>global $USER;</code> to reach them inside a function). Helpers like <code>userValue(\'username\')</code> read the same data.'
		: 'Before your code runs, WaSQL serializes <code>USER CONFIG PAGE TEMPLATE PASSTHRU DATABASE REQUEST SESSION SERVER CRONTHRU</code> and rebuilds them as native values in the target language, plus accessor helpers. Add more with <code>$CONFIG[\'eval_globals\']</code>. Values are base64\'d onto the generated script &ndash; click <b>Show full generated script</b> above to see it.';

	//PHP is native - there is no generated wrapper script / injected-globals preamble to show
	$btns='';
	if($key!='php'){
		$btns='<div class="align-right" style="margin-bottom:14px;">'
			.'<button class="wacss_button is-small" onclick="return wacss.ajaxGet(\'/php/admin.php\',\'centerpop\',{_menu:\'test\',test:\'language_includes\',lang:\''.$key.'\'});">Show injected globals</button> '
			.'<button class="wacss_button is-small" onclick="return wacss.ajaxGet(\'/php/admin.php\',\'centerpop\',{_menu:\'test\',test:\'script\',lang:\''.$key.'\'});">Show full generated script</button>'
			.'</div>';
	}

	return <<<EOT
<div class="test-lang-panel" id="lang-{$key}">
	<div class="test-lang-head">
		<h2>{$d['name']}</h2>
		{$badge}
	</div>
	<div class="test-lang-meta">{$meta}</div>
	<p class="test-lang-blurb">{$d['blurb']}</p>
	{$btns}
	<div class="test-lang-section">
		<h3>1 &middot; Hello world</h3>
		{$hello}
	</div>
	<div class="test-lang-section">
		<h3>2 &middot; WaSQL bridge &mdash; globals &amp; accessors</h3>
		<p class="test-lang-blurb" style="margin:0 0 12px;">{$bridgeBlurb}</p>
		{$bridge}
	</div>
	{$dbsection}
	<div class="test-lang-section">
		<h3>Install</h3>
		<div class="test-lang-install">{$d['install']}</div>
	</div>
</div>
EOT;
}

//---------- begin function testLanguagesTab
/**
* @describe The whole Languages tab: style + sub-tab bar + the default (PHP) panel container.
*   Sub-tab clicks AJAX-load testLanguagePanel() into #languages_content (controller case 'languages').
* @return string
* @usage <?=testLanguagesTab();?>
*/
function testLanguagesTab(){
	return testLanguagesStyle()
		.'<p class="test-lang-blurb" style="margin:0 0 16px;">Every language below runs live through its <code>&lt;?token &hellip; ?&gt;</code> island handler (<code>evalPHP()</code> &rarr; <code>eval&lt;Lang&gt;Code()</code> in <code>php/common.php</code>). Pick a tab. "not installed here" just means that interpreter is not on this server\'s <code>PATH</code>.</p>'
		.testLanguagesTabBar('php')
		.'<div id="languages_content">'.testLanguagePanel('php').'</div>';
}
?>
