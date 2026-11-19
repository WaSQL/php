<?php
/*
	Instructions:
		run cron_worker.php from a command-line every minute as follows
		you can run multiple to handle heavy loads - it will handle the queue
		On linux, add to the crontab
		* * * * * /var/www/wasql_live/php/cron_worker.sh >/var/www/wasql_live/php/cron_worker.log 2>&1
		On Windows, add it as a scheduled task - Command Prompt as administrator:
			https://www.windowscentral.com/how-create-task-using-task-scheduler-command-prompt
			Create:
				SCHTASKS /CREATE /SC MINUTE /MO 1 /TN "WaSQL\WaSQL_Worder_10" /TR "php.exe c:\wasql\php\cron_worker.php" /RU administrator
			List:
				SCHTASKS /QUERY
			Delete:
				SCHTASKS /DELETE /TN "WaSQL\WaSQL_Worder_10"


	Note: cron_worker.php cannot be run from a URL, it is a command line app only.
*/
//set time limit to a large number so the cron does not time out
ini_set('max_execution_time', 86400);
set_time_limit(86400);
error_reporting(E_ALL & ~E_NOTICE);
$posturl_timeout=86400; //allow crons that call posturl to run for up to 24 hours
global $starttime;
$starttime=microtime(true);
$progpath=dirname(__FILE__);

$scriptname=basename(__FILE__, '.php');
$wpath=dirname( dirname(__FILE__) );

//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("{$progpath}/common.php");
//only allow this to be run from CLI
if(!isCLI()){
	cronMessage("cron_worker.php is a command line app only.");
	exit;
}
global $ConfigXml;
global $allhost;
global $dbh;
global $sel;
global $CONFIG;
global $cron_id;
global $CRONTHRU;
global $cronlog_id;
$cronlog_id=0;
$CRONTHRU=array();
$starttime=microtime(true);
$_SERVER['HTTP_HOST']='localhost';
include_once("{$progpath}/config.php");
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("{$progpath}/wasql.php");
include_once("{$progpath}/database.php");
include_once("{$progpath}/user.php");
include_once("{$progpath}/extras/system.php");

//this cron requires mysql version 5.7 or newer
$dbversion=getDBVersion();
//cronMessage("Mysql Version: {$dbversion}");
if($dbversion < 5.7){
	cronMessage("ERROR - running mysql version {$dbversion}. Cron_worker.php now requires mysql version 5.7 or greater.  For older mysql versions run cron.php instead.");
	exit;
}

global $databaseCache;
$etime=microtime(true)-$starttime;
$etime=(int)$etime;
$pid_check=1;
if(!count($ConfigXml)){exit;}
$tpath=getWaSQLPath('php/temp');
$cron_tail_log="{$tpath}/cron_tail.log";
$cron_pid=getmypid();
/*
	Child-process isolation: a page cron defines its functions in the PHP process that runs it, and PHP can never
	undeclare a function. When one worker ran jobs for several sites the second site's same-named function was a
	"Cannot redeclare" fatal that killed the worker for every site. So each Page/eval job now runs in its own child
	process: `php cron_worker.php --child={cron_id} --host={hostname} --ppid={parent pid}`.
	If the OS/PHP cannot spawn children (proc_open disabled or no php binary) we fall back to running in-process
	but stop the worker as soon as it would move to a different host after loading page code (a fresh worker is
	started by cron_worker.sh every few seconds and picks up the rest).
*/
global $cronChildren;
$cronChildren=array();
$cron_child_id=0;
$cron_child_host='';
$cron_child_ppid=0;
if(isset($argv) && is_array($argv)){
	foreach($argv as $__arg){
		if(preg_match('/^--child=(\d+)$/',$__arg,$__m)){$cron_child_id=(int)$__m[1];}
		elseif(preg_match('/^--host=(.+)$/',$__arg,$__m)){$cron_child_host=$__m[1];}
		elseif(preg_match('/^--ppid=(\d+)$/',$__arg,$__m)){$cron_child_ppid=(int)$__m[1];}
	}
}
if($cron_child_id && (!strlen($cron_child_host) || !$cron_child_ppid)){
	cronMessage("child worker started without --host/--ppid - exiting");
	exit(2);
}
$cron_child_ok=$cron_child_id?false:cronChildSupported();
$cron_inproc_host='';
if(!$cron_child_id){
	if($cron_child_ok){register_shutdown_function('cronChildrenKill');}
	else{cronMessage("NOTICE - child processes are not supported here; page crons run in-process and the worker exits when it would switch hosts");}
	if($cron_child_ok && function_exists('pcntl_async_signals') && function_exists('pcntl_signal')){
		//make SIGTERM/SIGINT run the shutdown function so running children are terminated
		pcntl_async_signals(true);
		pcntl_signal(SIGTERM,function(){exit(143);});
		pcntl_signal(SIGINT,function(){exit(130);});
	}
}

//should switch to ALLCONFIG
foreach($ConfigXml as $name=>$host){
	//allhost, then, sameas, then hostname
	$CONFIG=$allhost;
	if(isset($host['sameas']) && isset($ConfigXml[$host['sameas']])){
		foreach($ConfigXml[$host['sameas']] as $k=>$v){
			if($k=='name'){continue;}
        	$CONFIG[$k]=$v;
		}
	}
	foreach($host as $k=>$v){
    	$CONFIG[$k]=$v;
	}
	if(!isset($CONFIG['cron'])){$CONFIG['cron']=0;}
	//a child worker only ever handles its one host
	if($cron_child_id && $CONFIG['name'] !== $cron_child_host){continue;}
	if($CONFIG['cron']==0){
		continue;
	}
	//in-process fallback: never mix page code from two hosts in one process (see note above)
	if(!$cron_child_id && strlen($cron_inproc_host) && $cron_inproc_host !== $CONFIG['name']){
		cronMessage("pid {$cron_pid} exiting before switching from {$cron_inproc_host} to {$CONFIG['name']} - page functions already loaded; a new worker will continue");
		exit;
	}	
	//connect to this database.
	$dbh='';
	//cronMessage("connecting");
	$ok=cronDBConnect();
	if($ok != 1){
    	cronMessage("failed to connect: {$ok}");
    	unset($ConfigXml[$name]);
    	continue;
	}
	/*
		secure a record with run_now=1 and run it
		if no records to run check for apache_access_log 
	*/
	while($etime <= 60){
		//secure a cron record with run_now set to 1 (a child instead takes over the row its parent already secured)
		if($cron_child_id){
			$secureWhere="_id={$cron_child_id} and running=1 and cron_pid={$cron_child_ppid}";
		}
		else{
			$secureWhere="run_now=1 and cron_pid=0 and running=0";
		}
		$secureSQL=<<<ENDOFSQL
		UPDATE _cron 
		SET 
			cron_pid={$cron_pid},
			running=1,
			run_now=0,
			stop_now=0,
			run_date=NOW()
		WHERE
			{$secureWhere}
		LIMIT 1
ENDOFSQL;
		$ok=executeSQL($secureSQL);
		$cronrec=getDBRecord(array(
			'-table'	=> '_cron',
			'cron_pid'	=> $cron_pid,
			'running'	=> 1,
			'run_now'	=> 0,
			'-nocache'	=> 1
		));
		//break if no records to run
		if(!isset($cronrec['_id'])){
			//$ok=cronMessage("{$CONFIG['name']} - no crons are ready");
			if($cron_child_id){cronMessage("child pid {$cron_pid} could not take over cron {$cron_child_id} for {$CONFIG['name']} - exiting");}
			break;
		}
		//add a cronlog (a child reuses the cronlog its parent already created)
		$header=array(
			'timestamp'=>getDBTime(),
			'cron_name'=>$cronrec['name'],
			'cron_id'=>$cronrec['_id'],
			'user_id'=>0,
			'cron_pid'=>$cron_pid
		);
		if(!$cron_child_id){
			$cronrec['cronlog_id']=addDBRecord(array(
				'-table'=>'_cron_log',
				'cron_id'=>$cronrec['_id'],
				'delete_me'=>0,
				'header'=>encodeJson($header)
			));
			if(isNum($cronrec['cronlog_id'])){
				$ok=editDBRecordById('_cron',$cronrec['_id'],array('cronlog_id'=>$cronrec['cronlog_id']));
			}
			//only keep the last x records
			$ok=cronCleanRecords($cronrec);
		}
		//set cmd
		$cmd=$cronrec['run_cmd'];
		$lcmd=strtolower(trim($cmd));
		//get page names to determine if cron is a page
		$pages=getDBRecords(array(
			'-table'	=> '_pages',
			'-fields'	=> 'name,_id',
			'-index'	=> 'name',
			'-nocache'=>1
		));
		/*
			look for passthru
				/cron_test/a/b
				/t/1/cron_test/a/b
		*/
		$crontype='';
		global $PASSTHRU;
		if(preg_match('/^http/i',$cmd)){
	    	//cron is a URL.
	    	$crontype='URL';
		}
		else{
			$parts=preg_split('/\/+/',$lcmd);
			if(count($parts) > 1){
				//remove all parts before $view and set passthru
				$stripped=0;
				$tmp=array();
				foreach($parts as $part){
			        $part=trim($part);
			        if(!strlen($part)){continue;}
			        if(isset($pages[$part])){
						$stripped=1;
						$_REQUEST['_view']=$part;
						$crontype='Page';
						continue;
					}
					if($stripped){$tmp[]=$part;}
				}
				$_REQUEST['passthru']=$PASSTHRU=$tmp;
			}
		}
		if(strlen($crontype)){}
		elseif(isset($pages[$lcmd])){
			//cron is a page
			$crontype='Page';
		}
		elseif(preg_match('/^<\?\=/',$cmd)){
	    	//cron is a eval
	    	$crontype='eval';
		}
		else{
	    	//cron is a command
	    	$crontype='OS Command';
		}
		//update the cronlog header with crontype
		$header['crontype']=$crontype;
		cronMessage("Cron ID:{$cronrec['_id']} - Name:{$cronrec['name']}, cronlog_id:{$cronrec['cronlog_id']}, crontype:{$crontype}, cmd:{$cmd}, cron_pid:{$cron_pid}");
		$ok=cronLogUpdate(array('header'=>encodeJson($header)));
		//Page and eval jobs define functions in the running process, so give each its own child process
		if(!$cron_child_id && $cron_child_ok && in_array(strtolower($crontype),array('page','eval'))){
			$cron_job_start=microtime(true);
			$child=cronRunChild($cronrec['_id'],$CONFIG['name'],$cron_pid);
			if($child['started']){
				cronMessage("Cron ID:{$cronrec['_id']} - Name:{$cronrec['name']}, child pid {$child['pid']} finished with exit code {$child['code']} in ".round(microtime(true)-$cron_job_start,2)."s");
				//if the child died without releasing the cron row (fatal, killed) release it here so it is not stuck running
				$left=getDBRecord(array('-table'=>'_cron','_id'=>$cronrec['_id'],'-fields'=>'_id,running,cron_pid','-nocache'=>1));
				if(isset($left['_id']) && $left['running']==1 && ($left['cron_pid']==$child['pid'] || $left['cron_pid']==$cron_pid)){
					cronMessage("Cron ID:{$cronrec['_id']} - Name:{$cronrec['name']}, child pid {$child['pid']} exited with code {$child['code']} without finishing - releasing the cron");
					$ok=editDBRecordById('_cron',$cronrec['_id'],array('running'=>0,'cron_pid'=>0,'cronlog_id'=>0,'run_length'=>round(microtime(true)-$cron_job_start,4)));
				}
				$etime=(int)(microtime(true)-$starttime);
				continue;
			}
			//could not spawn - fall back to in-process for the rest of this worker's life
			cronMessage("Cron ID:{$cronrec['_id']} - Name:{$cronrec['name']}, child process failed to start ({$child['error']}) - running in-process and disabling children");
			$cron_child_ok=false;
		}
		//start the job
		//per-job timer for _cron.run_length ($starttime is the worker start / 60s loop budget)
		$cron_job_start=microtime(true);
		$CRONTHRU['cron_guid']=generateGUID();
		if(strtolower($crontype)=='page'){
	    	//cron is a page.
	    	$crontype='page';
	    	$cmd=preg_replace('/^\/t\/1+/','',$cmd);
	    	$cmd=preg_replace('/^\/+/','',$cmd);
	    	//if they have specified a run_as then login as that person
	    	if(isset($cronrec['run_as']) && isNum($cronrec['run_as'])){
	    		global $USER;
	        	$urec=getDBRecord(array(
					'-table'=>'_users',
					'_id'	=> $cronrec['run_as'],
					'-fields'=>'_id,username',
					'-nocache'=>1
				));
				if(isset($urec['_id'])){
					$USER=$urec;
				}
			}
			global $PAGE;
			$PAGE=array('_id'=>0,'name'=>'--cron--','permalink'=>'--cron--');
			//echo "PAGE: [{$cmd}]".printValue($_REQUEST);exit;
			$ctime=microtime(true);
			//$ok=commonCronLog("<b>LOADING PAGE:</b> {$cmd}");
			//load any functions in the blank template
			$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"name='blank' or _id=1"));
			//load the page
			$out=includePage($cmd,$_REQUEST);
			$rtime=microtime(true)-$ctime;
	    	//$ok=commonCronLog("<b>RETURNED.</b>  Runtime:{$rtime}");
			$out=array('body'=>$out);
	    	$ok=cronLogUpdate(array('body'=>encodeJson($out)));
	    	if(stringContains($out,'__cron_log_delete__')){
	    		$ok=commonCronLogDelete();
	    	}
		}
		elseif(strtolower($crontype)=='eval'){
	    	//cron is a eval
	    	$crontype='eval';
	    	$ctime=microtime(true);
	    	$ok=commonCronLog("<b>RUNNING EVAL:</b> <xmp>{$cmd}</xmp>");
	    	$out=array(
	    		'code'=>$cmd,
	    		'output'=>evalPHP($cmd)
	    	);
	    	$ok=commonCronLog($out['output']);
	    	$out['runtime']=microtime(true)-$ctime;
	    	$ok=commonCronLog("<b>RETURNED.</b>  Runtime:{$out['runtime']}");
	    	$ok=cronLogUpdate(array('body'=>encodeJson($out)));
		}
		elseif(strtolower($crontype)=='url'){
			$crontype='url';
	    	//cron is a URL.
	    	$postopts=array(
	    		'-method'=>'GET',
	    		'-follow'=>1,
	    		'-nossl'=>1,
	    		'-timeout'=>$posturl_timeout
	    	);
	    	foreach($CRONTHRU as $k=>$v){
	    		$postopts[$k]=$v;
	    	}
	    	$ok=commonCronLog("<b>CALLING URL:</b> {$cmd}");
	    	$post=postURL($cmd,$postopts);
	    	$ok=commonCronLog("<b>RETURNED.</b> HTTP Code: {$post['curl_info']['http_code']}. Total Time:{$post['curl_info']['total_time']}");
	    	if(isXML($post['body'])){
	    		$post['body']='<xmp>'.PHP_EOL.$post['body'].PHP_EOL.'</xmp>';
	    	}
	    	$ok=cronLogUpdate(array('body'=>encodeJson($post)));
	    	if(stringContains($post['body'],'__cron_log_delete__')){
	    		$ok=commonCronLogDelete();
	    	}
		}
		else{
	    	//cron is an OS Command
	    	$ok=commonCronLog("<b>RUNNING OS COMMAND:</b> {$cmd}");
	    	$out=cmdResults($cmd);
	    	if($out['rtncode']==0){
	    		$ok=commonCronLog($out['stdout']);	
	    	}
	    	else{
	    		$ok=commonCronLog($out['stderr']);
	    	}
	    	$ok=commonCronLog("<b>RETURNED:</b> Rtncode: {$out['rtncode']}. Runtime:{$out['runtime']}");
	    	$ok=cronLogUpdate(array('body'=>encodeJson($out)));
		}
		if(in_array(strtolower($crontype),array('page','eval'))){$cron_inproc_host=$CONFIG['name'];}
		//update record to show we are now finished
		$footer=array(
			'timestamp'=>getDBTime(),
			'crontype'=>$crontype
		);
		$ok=cronLogUpdate(array('footer'=>encodeJson($footer)));
    	
		$run_length=microtime(true)-$cron_job_start;
		$run_memory=memory_get_usage();
		$eopts=array(
			'running'		=> 0,
			'cron_pid'		=> 0,
			'cronlog_id'	=> 0,
			'run_length'	=> str_replace(',','',$run_length),
			'run_memory'	=> str_replace(',','',$run_memory)
		);
		$ok=editDBRecordById('_cron',$cronrec['_id'],$eopts);
		$etime=microtime(true)-$starttime;
		$etime=(int)$etime;
		$cron_pid=getmypid();
		cronMessage("Cron ID:{$cronrec['_id']} - Name:{$cronrec['name']}, process_pid: {$cron_pid} finished");
		//a child runs exactly one job
		if($cron_child_id){exit(0);}
	}
	if($etime > 60){break;}
}
exit;

/**
* @exclude  - this function is for internal use only- excluded from docs
* returns true if this PHP can launch child worker processes (proc_open enabled and a php binary available)
*/
function cronChildSupported(){
	if(!function_exists('proc_open') || !function_exists('proc_get_status') || !function_exists('proc_close')){return false;}
	$disabled=array_map('trim',explode(',',strtolower((string)ini_get('disable_functions'))));
	foreach(array('proc_open','proc_get_status','proc_close','proc_terminate') as $fn){
		if(in_array($fn,$disabled)){return false;}
	}
	if(!defined('PHP_BINARY') || !strlen(PHP_BINARY) || !is_file(PHP_BINARY)){return false;}
	if(!defined('STDOUT') || !defined('STDERR')){return false;}
	return true;
}
/**
* @exclude  - this function is for internal use only- excluded from docs
* runs one cron in a child worker process and waits for it. the child inherits our stdout/stderr so its log lines
* land in the same cron_worker log. returns array(started,pid,code,error)
*/
function cronRunChild($cron_id,$host,$ppid){
	global $cronChildren;
	global $progpath;
	$cmd=array(PHP_BINARY,__FILE__,"--child={$cron_id}","--host={$host}","--ppid={$ppid}");
	$spec=array(0=>array('pipe','r'),1=>STDOUT,2=>STDERR);
	$pipes=array();
	$proc=@proc_open($cmd,$spec,$pipes,$progpath);
	if(!is_resource($proc)){
		return array('started'=>false,'pid'=>0,'code'=>-1,'error'=>'proc_open failed');
	}
	if(isset($pipes[0]) && is_resource($pipes[0])){fclose($pipes[0]);}
	$status=proc_get_status($proc);
	$pid=$status['pid'];
	$cronChildren[$pid]=$proc;
	cronMessage("Cron ID:{$cron_id} - started child pid {$pid} for {$host}");
	$code=-1;
	while(true){
		$status=proc_get_status($proc);
		if(!$status['running']){
			//exitcode is only reported on the first call after the process ends
			$code=$status['exitcode'];
			break;
		}
		usleep(100000);
	}
	proc_close($proc);
	unset($cronChildren[$pid]);
	return array('started'=>true,'pid'=>$pid,'code'=>$code,'error'=>'');
}
/**
* @exclude  - this function is for internal use only- excluded from docs
* shutdown handler: terminate any child still running if the parent worker exits or is signalled
*/
function cronChildrenKill(){
	global $cronChildren;
	if(!is_array($cronChildren)){return;}
	foreach($cronChildren as $pid=>$proc){
		if(is_resource($proc)){
			$st=proc_get_status($proc);
			if($st['running']){
				cronMessage("parent worker exiting - terminating child pid {$pid}");
				@proc_terminate($proc);
				usleep(500000);
				$st=proc_get_status($proc);
				if($st['running']){@proc_terminate($proc,9);}
			}
			@proc_close($proc);
		}
		unset($cronChildren[$pid]);
	}
}
/**
* @exclude  - this function is for internal use only- excluded from docs
*/
function cronCleanRecords($cron=array()){
	if(!isset($cron['_id'])){return false;}
	if(!isNum($cron['records_to_keep'])){return false;}
	//get the 
	$recs=getDBRecords(array(
		'-table'=>'_cron_log',
		'-order'=>'_id desc',
		'-limit'=>$cron['records_to_keep'],
		'-fields'=>'_id',
		'cron_id'=>$cron['_id'],
		'-nocache'=>1
	));
	if(!isset($recs[0])){return;}
	$min=0;
	foreach($recs as $rec){
		if($min==0 || $rec['_id'] < $min){$min=$rec['_id'];}
	}
	$ok=delDBRecord(array(
		'-table'=>'_cron_log',
		'-where'=>"_id < {$min} and cron_id='{$cron['_id']}'"
	));
	return $ok;
}

/**
* @exclude  - this function is for internal use only- excluded from docs
*/
function cronMessage($msg,$separate=0){
	return commonLogMessage('cron_worker',$msg,$separate,1);
}
/**
* @exclude  - this function is for internal use only- excluded from docs
*/
function cronLogUpdate($params=array()){
	if(!is_array($params) || !count($params)){
		return false;
	}
	$cron_pid=getmypid();
	$cron=getDBRecord(array(
		'-table'=>'_cron',
		'-where'=>"cron_pid={$cron_pid}",
		'-fields'=>'_id,cron_pid,cronlog_id',
		'-nocache'=>1
	));
	if(!isset($cron['cronlog_id']) || !isNum($cron['cronlog_id'])){
		return false;
	}
	$params['-table']='_cron_log';
	$params['-where']="_id={$cron['cronlog_id']}";
	//echo "cronLogUpdate".printValue($params);exit;
	$ok=editDBRecord($params);
	return $ok;
}
/**
* @exclude  - this function is for internal use only- excluded from docs
*/
function cronDBConnect(){
	global $CONFIG;
	global $dbh;
	global $sel;
	try{
		$dbh=databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
	}
	catch(Exception $e){
		$dbh=false;
	}
	if(!$dbh){
		$error=databaseError();
		$ok=cronMessage("{$CONFIG['name']} - cronDBConnect Error {$error}");
		return $error;
	}
	//select database
	$sel=databaseSelectDb($CONFIG['dbname']);
	//create the db if it does not exist
	if(!$sel){
		if(databaseQuery("create database {$CONFIG['dbname']}")){
			if(isPostgreSQL()){
	        	$dbh=databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
			}
			$sel=databaseSelectDb($CONFIG['dbname']);
		}
	}
	if(!$sel){
		$error=databaseError();
		return $error;
	}
	return true;
}
