<?php
	global $CONFIG;
	//echo printValue($CONFIG);exit;
	if(!isset($CONFIG['phpprompt_path'])){
		$CONFIG['phpprompt_path']='/php/admin.php';
	}
	switch(strtolower($_REQUEST['func'])){
		case 'php':
			$_SESSION['php_full']=$_REQUEST['php_full'];
			$php_select=stripslashes($_REQUEST['php_select']);
			$php_full=stripslashes($_REQUEST['php_full']);
			if(strlen($php_select) && $php_select != $php_full){
				$_SESSION['php_last']=$php_select;
				$results=evalPHP($php_select);
				setView('results',1);
			}
			else{
				$_SESSION['php_last']=$php_full;
				$results=evalPHP($php_full);
				setView('results',1);
			}

			return;
		break;
		case 'export':
			$recs=getDBRecords($_SESSION['php_last']);
			$csv=arrays2CSV($recs);
			pushData($csv,'csv');
			exit;
		break;
		case 'fields':
			$table=addslashes($_REQUEST['table']);
			$fields=getDBFieldInfo($table);
			//echo printValue($fields);exit;
			setView('fields',1);
			return;
		break;
		case 'export':
			$id=addslashes($_REQUEST['id']);
			$report=getDBRecord(array('-table'=>'_reports','active'=>1,'_id'=>$id));
			$report=reportsRunReport($report);
			$csv=arrays2CSV($report['recs']);
			pushData($csv,'csv',$report['name'].'.csv');
			exit;
			return;
		break;
		default:
			$tables=getDBTables();
			if(!isset($_SESSION['php_full']) || !strlen($_SESSION['php_full'])){
                $_SESSION['php_full']='<?'.'php'.PHP_EOL.PHP_EOL.'?'.'>'.PHP_EOL;
			}
			setView('default',1);
		break;
	}
	setView('default',1);
?>
