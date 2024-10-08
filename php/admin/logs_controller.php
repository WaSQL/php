<?php
	global $CONFIG;
	switch(strtolower($_REQUEST['func'])){
		case 'filter':
			$tail['name']=$_REQUEST['name'];
			$tail['filter']=$_REQUEST['filter'];
			$path=getWaSQLPath('logs');
			$logfile="{$path}/{$tail['name']}.log";
			$lines=filterFile($logfile,$tail['filter']);
			$tail['data']=implode(PHP_EOL,$lines);
			setView('tail',1);
			return;
		break;
		case 'tail':
			$tail['name']=$_REQUEST['name'];
			$tail['filter']=$_REQUEST['filter'];
			$path=getWaSQLPath('logs');
			$logfile="{$path}/{$tail['name']}.log";
			if(strlen($tail['filter'])){
				$lines=filterFile($logfile,$tail['filter']);
				$tail['data']=implode(PHP_EOL,$lines);
			}
			else{
				$tail['data']=tailFile($logfile,1000);
			}
			
			setView('tail',1);
			return;
		break;
		case 'tail_refresh':
			$tail['name']=$_REQUEST['name'];
			$tail['filter']=$_REQUEST['filter'];
			$path=getWaSQLPath('logs');
			$logfile="{$path}/{$tail['name']}.log";
			if(strlen($tail['filter'])){
				$lines=filterFile($logfile,$tail['filter']);
				$tail['data']=implode(PHP_EOL,$lines);
			}
			else{
				$tail['data']=tailFile($logfile,300);
			}
			setView('tail_refresh',1);
			return;
		break;
	}
	$refresh=isset($CONFIG['logs_refresh'])?(integer)$CONFIG['logs_refresh']:60;
	$includes=array();
	if(isset($_REQUEST['includes']) && strlen(trim($_REQUEST['includes']))){
		$includes=preg_split('/\s/',trim($_REQUEST['includes']));
	}
	$excludes=array();
	if(isset($_REQUEST['excludes']) && strlen(trim($_REQUEST['excludes']))){
		$excludes=preg_split('/\s/',trim($_REQUEST['excludes']));
	}
	$logs=logsGetLogs($includes,$excludes);
	setView('default');
	if($refresh >= 10){setView('refresh');}
?>