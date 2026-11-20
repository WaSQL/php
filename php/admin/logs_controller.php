<?php
	global $CONFIG;
	switch(strtolower($_REQUEST['func'])){
		case 'userlog':
			//User (auth) log viewer - opened in a modal from the _users list
			if(!isAdmin()){
				$userlog_error='Admin access required.';
				setView('userlog_error',1);
				return;
			}
			$userlog=logsUserLogLoad();
			if(!$userlog['exists']){
				$userlog_error='No user log found at '.$userlog['file'];
				setView('userlog_error',1);
				return;
			}
			switch(strtolower($_REQUEST['view'] ?? '')){
				case 'search':
					$filters=logsUserLogFilters();
					$options=logsUserLogOptions($userlog,$filters);
					$search=logsUserLogSearch($userlog,$filters);
					setView('userlog_search',1);
				break;
				case 'results':
					$filters=logsUserLogFilters();
					$search=logsUserLogSearch($userlog,$filters);
					setView('userlog_results',1);
				break;
				case 'users':
					$userlog_q=trim((string)($_REQUEST['q'] ?? ''));
					$users=logsUserLogUsers($userlog,$userlog_q);
					setView('userlog_users',1);
				break;
				case 'failures':
					$failures=logsUserLogFailures($userlog);
					//only=unresolved: the "Still failing" tile - no successful login since failing, last 7 days
					$failures_filtered=(($_REQUEST['only'] ?? '')=='unresolved');
					if($failures_filtered){
						$failures=logsUserLogUnresolved($failures);
					}
					setView('userlog_failures',1);
				break;
				case 'errors':
					$errors=logsUserLogErrors($userlog);
					setView('userlog_errors',1);
				break;
				case 'raw':
					$raw=logsUserLogRaw($userlog,1000);
					setView('userlog_raw',1);
				break;
				case 'overview':
					$stats=logsUserLogStats($userlog);
					setView('userlog_overview',1);
				break;
				default:
					$stats=logsUserLogStats($userlog);
					$userlog['size_verbose']=verboseSize($userlog['size']);
					$userlog['age_verbose']=verboseTime(time()-$userlog['mtime']);
					setView('userlog',1);
				break;
			}
			return;
		break;
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
	$refresh=isset($CONFIG['logs_refresh'])?(int)$CONFIG['logs_refresh']:60;
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