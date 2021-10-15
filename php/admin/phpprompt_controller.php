<?php
	global $CONFIG;
	if(!isset($CONFIG['phpprompt_path'])){
		$CONFIG['phpprompt_path']='/php/admin.php';
	}
	//check for _prompts record
	switch(strtolower($_REQUEST['func'])){
		case 'php_prompt_load':
			$php_full=phppromptGetValue();
			setView(array('php_prompt_load'),1);
			return;
		break;
		case 'php_prompt':
			$php_full=phppromptGetValue();
			$results=evalPHP($php_full);
			setView(array('results','php_prompt'),1);
			return;
		break;
		case 'php':
			$php_full=stripslashes($_REQUEST['php_full']);
			$ok=phppromptSetValue($php_full);
			$results=evalPHP($php_full);
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
