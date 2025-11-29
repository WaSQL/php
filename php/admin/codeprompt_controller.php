<?php
	global $CONFIG;
	if(!isset($CONFIG['codeprompt_path'])){
		$CONFIG['codeprompt_path']='/php/admin.php';
	}
	//check for _prompts record
	switch(strtolower($_REQUEST['func'])){
		case 'setlang_php':
			echo "<?";
			echo "php".PHP_EOL;
			echo "global \$USER;".PHP_EOL;
			echo "global \$CONFIG;".PHP_EOL;
			echo "# PHP Example - Accessing PHP Globals".PHP_EOL;
			echo 'echo "<h3>Language: PHP</h3>".PHP_EOL;'.PHP_EOL;
			echo 'echo "<div>Version: ".phpversion()."</div>".PHP_EOL;'.PHP_EOL;
			echo 'echo "<div>OS: ".PHP_OS."</div>".PHP_EOL;'.PHP_EOL;
			echo 'echo "<h4>Accessing PHP Globals:</h4>".PHP_EOL;'.PHP_EOL;
			echo 'echo "<div>REQUEST _menu: {$_REQUEST[\'_menu\']}</div>".PHP_EOL;'.PHP_EOL;
			echo 'echo "<div>SERVER SERVER_ADDR: {$_SERVER[\'SERVER_ADDR\']}</div>".PHP_EOL;'.PHP_EOL;
			echo 'echo "<div>USER username: {$USER[\'username\']}</div>".PHP_EOL;'.PHP_EOL;
			echo 'echo "<div>CONFIG name: {$CONFIG[\'name\']}</div>".PHP_EOL;'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_py':
			echo "<?";
			echo "py".PHP_EOL;
			echo "# Python Example - Accessing PHP Globals".PHP_EOL;
			echo "import sys".PHP_EOL;
			echo 'print("<h3>Language: Python</h3>")'.PHP_EOL;
			echo 'print(f"<div>Version: {sys.version}</div>")'.PHP_EOL;
			echo 'print(f"<div>OS: {sys.platform}</div>")'.PHP_EOL;
			echo 'print("<h4>Accessing PHP Globals:</h4>")'.PHP_EOL;
			echo 'print(f"<div>REQUEST _menu: {wasql.request(\'_menu\')}</div>")'.PHP_EOL;
			echo 'print(f"<div>SERVER SERVER_ADDR: {wasql.server(\'SERVER_ADDR\')}</div>")'.PHP_EOL;
			echo 'print(f"<div>USER username: {wasql.user(\'username\')}</div>")'.PHP_EOL;
			echo 'print(f"<div>CONFIG name: {wasql.config(\'name\')}</div>")'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_pl':
			echo "<?";
			echo "pl".PHP_EOL;
			echo "# Perl Example - Accessing PHP Globals".PHP_EOL;
			echo 'print "<h3>Language: Perl</h3>";'.PHP_EOL;
			echo 'print "<div>Version: $^V</div>";'.PHP_EOL;
			echo 'print "<div>OS: $^O</div>";'.PHP_EOL;
			echo 'print "<h4>Accessing PHP Globals:</h4>";'.PHP_EOL;
			echo 'print "<div>REQUEST _menu: " . wasqlRequest("_menu") . "</div>";'.PHP_EOL;
			echo 'print "<div>SERVER SERVER_ADDR: " . wasqlServer("SERVER_ADDR") . "</div>";'.PHP_EOL;
			echo 'print "<div>USER username: " . wasqlUser("username") . "</div>";'.PHP_EOL;
			echo 'print "<div>CONFIG name: " . wasqlConfig("name") . "</div>";'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_r':
			echo "<?";
			echo "r".PHP_EOL;
			echo "# R Example - Accessing PHP Globals".PHP_EOL;
			echo 'cat("<h3>Language: R</h3>")'.PHP_EOL;
			echo 'cat(paste0("<div>Version: ", R.version.string, "</div>"))'.PHP_EOL;
			echo 'cat(paste0("<div>OS: ", R.version$platform, "</div>"))'.PHP_EOL;
			echo 'cat("<h4>Accessing PHP Globals:</h4>")'.PHP_EOL;
			echo 'cat(paste0("<div>REQUEST _menu: ", REQUEST$`_menu`, "</div>"))'.PHP_EOL;
			echo 'cat(paste0("<div>SERVER SERVER_ADDR: ", SERVER$SERVER_ADDR, "</div>"))'.PHP_EOL;
			echo 'cat(paste0("<div>USER username: ", USER$username, "</div>"))'.PHP_EOL;
			echo 'cat(paste0("<div>CONFIG name: ", CONFIG$name, "</div>"))'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_tcl':
			echo "<?";
			echo "tcl".PHP_EOL;
			echo "# Tcl Example - Accessing PHP Globals".PHP_EOL;
			echo 'puts "<h3>Language: Tcl</h3>"'.PHP_EOL;
			echo 'puts "<div>Version: [info patchlevel]</div>"'.PHP_EOL;
			echo 'puts "<div>OS: $tcl_platform(os) $tcl_platform(osVersion)</div>"'.PHP_EOL;
			echo 'puts "<h4>Accessing PHP Globals:</h4>"'.PHP_EOL;
			echo 'puts "<div>REQUEST _menu: [wasqlREQUEST _menu]</div>"'.PHP_EOL;
			echo 'puts "<div>SERVER SERVER_ADDR: [wasqlSERVER SERVER_ADDR]</div>"'.PHP_EOL;
			echo 'puts "<div>USER username: [wasqlUSER username]</div>"'.PHP_EOL;
			echo 'puts "<div>CONFIG name: [wasqlCONFIG name]</div>"'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_node':
			echo "<?";
			echo "node".PHP_EOL;
			echo "// Node.js Example - Accessing PHP Globals".PHP_EOL;
			echo 'console.log("<h3>Language: Node.js</h3>");'.PHP_EOL;
			echo 'console.log(`<div>Version: ${process.version}</div>`);'.PHP_EOL;
			echo 'console.log(`<div>OS: ${process.platform} ${process.arch}</div>`);'.PHP_EOL;
			echo 'console.log("<h4>Accessing PHP Globals:</h4>");'.PHP_EOL;
			echo 'console.log(`<div>REQUEST _menu: ${wasql.request("_menu")}</div>`);'.PHP_EOL;
			echo 'console.log(`<div>SERVER SERVER_ADDR: ${wasql.server("SERVER_ADDR")}</div>`);'.PHP_EOL;
			echo 'console.log(`<div>USER username: ${wasql.user("username")}</div>`);'.PHP_EOL;
			echo 'console.log(`<div>CONFIG name: ${wasql.config("name")}</div>`);'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_lua':
			echo "<?";
			echo "lua".PHP_EOL;
			echo "-- Lua Example - Accessing PHP Globals".PHP_EOL;
			echo 'local pathsep = package.config:sub(1,1)'.PHP_EOL;
			echo 'local os_name = (string.byte(pathsep) == 92 and "Windows" or "Unix")'.PHP_EOL;
			echo 'print("<h3>Language: Lua</h3>")'.PHP_EOL;
			echo 'print("<div>Version: " .. _VERSION .. "</div>")'.PHP_EOL;
			echo 'print("<div>OS: " .. os_name .. "</div>")'.PHP_EOL;
			echo 'print("<h4>Accessing PHP Globals:</h4>")'.PHP_EOL;
			echo 'print("<div>REQUEST _menu: " .. wasqlRequest("_menu") .. "</div>")'.PHP_EOL;
			echo 'print("<div>SERVER SERVER_ADDR: " .. wasqlServer("SERVER_ADDR") .. "</div>")'.PHP_EOL;
			echo 'print("<div>USER username: " .. wasqlUser("username") .. "</div>")'.PHP_EOL;
			echo 'print("<div>CONFIG name: " .. wasqlConfig("name") .. "</div>")'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_rb':
		case 'setlang_ruby':
			echo "<?";
			echo "ruby".PHP_EOL;
			echo "# Ruby Example - Accessing PHP Globals".PHP_EOL;
			echo 'puts "<h3>Language: Ruby</h3>"'.PHP_EOL;
			echo 'puts "<div>Version: #{RUBY_VERSION}</div>"'.PHP_EOL;
			echo 'puts "<div>OS: #{RUBY_PLATFORM}</div>"'.PHP_EOL;
			echo 'puts "<h4>Accessing PHP Globals:</h4>"'.PHP_EOL;
			echo 'puts "<div>REQUEST _menu: #{REQUEST[\'_menu\']}</div>"'.PHP_EOL;
			echo 'puts "<div>SERVER SERVER_ADDR: #{SERVER[\'SERVER_ADDR\']}</div>"'.PHP_EOL;
			echo 'puts "<div>USER username: #{USER[\'username\']}</div>"'.PHP_EOL;
			echo 'puts "<div>CONFIG name: #{CONFIG[\'name\']}</div>"'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_jl':
		case 'setlang_julia':
			echo "<?";
			echo "julia".PHP_EOL;
			echo "# Julia Example - Accessing PHP Globals".PHP_EOL;
			echo 'println("<h3>Language: Julia</h3>")'.PHP_EOL;
			echo 'println("<div>Version: ", VERSION, "</div>")'.PHP_EOL;
			echo 'println("<div>OS: ", Sys.MACHINE, "</div>")'.PHP_EOL;
			echo 'println("<h4>Accessing PHP Globals:</h4>")'.PHP_EOL;
			echo 'println("<div>REQUEST _menu: ", wasqlRequest("_menu"), "</div>")'.PHP_EOL;
			echo 'println("<div>SERVER SERVER_ADDR: ", wasqlServer("SERVER_ADDR"), "</div>")'.PHP_EOL;
			echo 'println("<div>USER username: ", wasqlUser("username"), "</div>")'.PHP_EOL;
			echo 'println("<div>CONFIG name: ", wasqlConfig("name"), "</div>")'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_bash':
		case 'setlang_sh':
			echo "<?";
			echo "bash".PHP_EOL;
			echo "# Bash Example - Accessing PHP Globals".PHP_EOL;
			echo 'echo "<h3>Language: Bash</h3>"'.PHP_EOL;
			echo 'echo "<div>Version: $BASH_VERSION</div>"'.PHP_EOL;
			echo 'echo "<div>OS: $(uname -s) $(uname -m)</div>"'.PHP_EOL;
			echo 'echo "<h4>Accessing PHP Globals:</h4>"'.PHP_EOL;
			echo 'echo "<div>REQUEST _menu: $(wasqlRequest _menu)</div>"'.PHP_EOL;
			echo 'echo "<div>SERVER SERVER_ADDR: $(wasqlServer SERVER_ADDR)</div>"'.PHP_EOL;
			echo 'echo "<div>USER username: $(wasqlUser username)</div>"'.PHP_EOL;
			echo 'echo "<div>CONFIG name: $(wasqlConfig name)</div>"'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_powershell':
		case 'setlang_pwsh':
		case 'setlang_ps1':
			echo "<?";
			echo "powershell".PHP_EOL;
			echo "# PowerShell Example - Accessing PHP Globals".PHP_EOL;
			echo 'Write-Output "<h3>Language: PowerShell</h3>"'.PHP_EOL;
			echo 'Write-Output "<div>Version: $($PSVersionTable.PSVersion)</div>"'.PHP_EOL;
			echo 'Write-Output "<div>OS: $([System.Environment]::OSVersion.Platform)</div>"'.PHP_EOL;
			echo 'Write-Output "<h4>Accessing PHP Globals:</h4>"'.PHP_EOL;
			echo 'Write-Output "<div>REQUEST _menu: $(wasqlRequest \"_menu\")</div>"'.PHP_EOL;
			echo 'Write-Output "<div>SERVER SERVER_ADDR: $(wasqlServer \"SERVER_ADDR\")</div>"'.PHP_EOL;
			echo 'Write-Output "<div>USER username: $(wasqlUser \"username\")</div>"'.PHP_EOL;
			echo 'Write-Output "<div>CONFIG name: $(wasqlConfig \"name\")</div>"'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_groovy':
			echo "<?";
			echo "groovy".PHP_EOL;
			echo "// Groovy Example - Accessing PHP Globals".PHP_EOL;
			echo 'println "<h3>Language: Groovy</h3>"'.PHP_EOL;
			echo 'println "<div>Version: ${GroovySystem.version}</div>"'.PHP_EOL;
			echo 'println "<div>OS: ${System.getProperty(\'os.name\')}</div>"'.PHP_EOL;
			echo 'println "<h4>Accessing PHP Globals:</h4>"'.PHP_EOL;
			echo 'println "<div>REQUEST _menu: ${wasqlRequest(\'_menu\')}</div>"'.PHP_EOL;
			echo 'println "<div>SERVER SERVER_ADDR: ${wasqlServer(\'SERVER_ADDR\')}</div>"'.PHP_EOL;
			echo 'println "<div>USER username: ${wasqlUser(\'username\')}</div>"'.PHP_EOL;
			echo 'println "<div>CONFIG name: ${wasqlConfig(\'name\')}</div>"'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_vbs':
		case 'setlang_vbscript':
			echo "<?";
			echo "vbscript".PHP_EOL;
			echo "' VBScript Example - Accessing PHP Globals".PHP_EOL;
			echo 'WScript.Echo "<h3>Language: VBScript</h3>"'.PHP_EOL;
			echo 'WScript.Echo "<div>Version: VBScript (Windows only)</div>"'.PHP_EOL;
			echo 'WScript.Echo "<div>OS: Windows</div>"'.PHP_EOL;
			echo 'WScript.Echo "<h4>Accessing PHP Globals:</h4>"'.PHP_EOL;
			echo 'WScript.Echo "<div>REQUEST _menu: " & wasqlRequest("_menu") & "</div>"'.PHP_EOL;
			echo 'WScript.Echo "<div>SERVER SERVER_ADDR: " & wasqlServer("SERVER_ADDR") & "</div>"'.PHP_EOL;
			echo 'WScript.Echo "<div>USER username: " & wasqlUser("username") & "</div>"'.PHP_EOL;
			echo 'WScript.Echo "<div>CONFIG name: " & wasqlConfig("name") & "</div>"'.PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'code_prompt_load':
			$code_full=codepromptGetValue();
			setView(array('code_prompt_load'),1);
			return;
		break;
		case 'code_prompt':
			$code_full=codepromptGetValue();
			$results=evalPHP($code_full);
			setView(array('results','code_prompt'),1);
			return;
		break;
		case 'code':
			$code_full=stripslashes($_REQUEST['code_full']);
			$ok=codepromptSetValue($code_full);
			$results=evalPHP($code_full);
			setView('results',1);
			return;
		break;
		default:
			$tables=getDBTables();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
