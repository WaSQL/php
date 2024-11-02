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
			echo PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_py':
			echo "<?";
			echo "py".PHP_EOL;
			echo PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_pl':
			echo "<?";
			echo "pl".PHP_EOL;
			echo PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_r':
			echo "<?";
			echo "r".PHP_EOL;
			echo PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_tcl':
			echo "<?";
			echo "tcl".PHP_EOL;
			echo PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_node':
			echo "<?";
			echo "node".PHP_EOL;
			echo PHP_EOL;
			echo '?';
			echo ">";exit;
		break;
		case 'setlang_lua':
			echo "<?";
			echo "lua".PHP_EOL;
			echo PHP_EOL;
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
