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
	cronMessage("ERROR - running mysql version {$dbversion}. Cron.php now requires mysql version 5.7 or greater.  For older mysql versions run cron_old.php instead.");
	exit;
}

global $databaseCache;
$etime=microtime(true)-$starttime;
$etime=(integer)$etime;
$pid_check=1;
if(!count($ConfigXml)){exit;}
$tpath=getWaSQLPath('php/temp');
$cron_tail_log="{$tpath}/cron_tail.log";
$cron_pid=getmypid();

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
	if($CONFIG['cron']==0){
		continue;
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
		//secure a cron record with run_now set to 1
		$secureSQL=<<<ENDOFSQL
		UPDATE _cron 
		SET 
			cron_pid={$cron_pid},
			running=1,
			run_now=0,
			stop_now=0,
			run_date=NOW()
		WHERE 
			run_now=1 
			and cron_pid=0 
			and running=0 
		LIMIT 1
ENDOFSQL;
		$ok=executeSQL($secureSQL);
		$rec=getDBRecord(array(
			'-table'	=> '_cron',
			'cron_pid'	=> $cron_pid,
			'running'	=> 1,
			'run_now'	=> 0,
			'-nocache'	=> 1
		));
		//break if no records to run
		if(!isset($rec['_id'])){
			//$ok=cronMessage("{$CONFIG['name']} - no crons are ready");
			break;
		}
		$ok=cronMessage("db:{$CONFIG['name']}, cron_id:{$rec['_id']}, cron_name:{$rec['name']}, msg: running");
		$CRONTHRU=array();
		$CRONTHRU['cron_id']=$rec['_id'];
		unset($_REQUEST['cronlog_id']);
		$cronlog_id=commonCronLogInit($rec['_id']);
		//only keep the last x records
		$ok=cronCleanRecords($rec);
		$cmd=$rec['run_cmd'];
		$lcmd=strtolower(trim($cmd));

		//get page names to determine if cron is a page
		$pages=getDBRecords(array(
			'-table'	=> '_pages',
			'-fields'	=> 'name,_id',
			'-index'	=> 'name'
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
		$ok=executeSQL("update _cron_log set header=JSON_SET(header,'$.crontype','{$crontype}') where _id={$cronlog_id}");
		//start the job
		$start=microtime(true);
		$CRONTHRU['cron_guid']=generateGUID();
		if(strtolower($crontype)=='page'){
	    	//cron is a page.
	    	$crontype='page';
	    	$cmd=preg_replace('/^\/+/','',$cmd);
	    	$prefix='https';
	    	if(isset($CONFIG['insecure']) && $CONFIG['insecure']==1){
	    		$prefix='http';
	    	}
	    	$url="{$prefix}://{$CONFIG['name']}/{$cmd}";
	    	$postopts=array(
	    		'-method'=>'GET',
	    		'-follow'=>1,
	    		'-nossl'=>1,
	    		'-timeout'=>$posturl_timeout
	    	);
	    	foreach($CRONTHRU as $k=>$v){
	    		$postopts[$k]=$v;
	    	}
	    	//if they have specified a run_as then login as that person
	    	if(isset($rec['run_as']) && isNum($rec['run_as'])){
	        	$urec=getDBRecord(array(
					'-table'=>'_users',
					'_id'	=> $rec['run_as'],
					'-fields'=>'_id,username'
				));
				if(isset($urec['_id'])){
	            	$postopts['_tauth']=userGetTempAuthCode($urec['_id']);
	            	$postopts['_noguid']=1;
				}
			}
	    	$post=postURL($url,$postopts);
	    	$ok=editDBRecordById('_cron_log',$cronlog_id,array('body'=>encodeJson($post)));
	    	if(stringContains($post['body'],'__cron_log_delete__')){
	    		$ok=commonCronLogDelete();
	    	}
		}
		elseif(strtolower($crontype)=='eval'){
	    	//cron is a eval
	    	$crontype='eval';
	    	$out=array(
	    		'code'=>$cmd,
	    		'output'=>evalPHP($cmd)
	    	);
	    	$ok=editDBRecordById('_cron_log',$cronlog_id,array('body'=>encodeJson($out)));
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
	    	$post=postURL($cmd,$postopts);
	    	$ok=editDBRecordById('_cron_log',$cronlog_id,array('body'=>encodeJson($post)));
	    	if(stringContains($post['body'],'__cron_log_delete__')){
	    		$ok=commonCronLogDelete();
	    	}
		}
		else{
	    	//cron is an OS Command
	    	$out=cmdResults($cmd);
	    	$ok=editDBRecordById('_cron_log',$cronlog_id,array('body'=>encodeJson($out)));
		}
		//update record to show we are now finished
		$footer=array(
			'timestamp'=>getDBTime(),
			'crontype'=>$crontype
		);
		$ok=editDBRecordById('_cron_log',$cronlog_id,array('footer'=>encodeJson($footer)));
		$run_length=microtime(true)-$starttime;
		$run_memory=memory_get_usage();
		$eopts=array(
			'running'		=> 0,
			'cron_pid'		=> 0,
			'run_length'	=> str_replace(',','',$run_length),
			'run_memory'	=> str_replace(',','',$run_memory)
		);
		$ok=editDBRecordById('_cron',$CRONTHRU['cron_id'],$eopts);
		//
		if(!isNum($ok)){
			$eopts=array(
				'running'		=> 0,
				'cron_pid'		=> 0,
				'run_length'	=> str_replace(',','',$run_length),
				'run_memory'	=> str_replace(',','',$run_memory)
			);
			$ok=editDBRecordById('_cron',$CRONTHRU['cron_id'],$eopts);
		} 
	}
}
exit;
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
		'cron_id'=>$cron['_id']
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
function cronLogTails(){
	global $CONFIG;
	$logs=array();
	$rowcount=isset($CONFIG['logs_rowcount'])?(integer)$CONFIG['logs_rowcount']:100;
	$tempdir=getWasqlPath('php/temp');
	foreach($CONFIG as $k=>$v){
		$lk=strtolower($k);
		if(strtolower($k)=='logs_rowcount'){continue;}
		if(strtolower($k)=='logs_refresh'){continue;}
		if(preg_match('/^logs\_(.+)$/is',$k,$m)){
			if(!is_file($v)){
				continue;
			}
			$fname=getFileName($v);
			$afile="{$tempdir}/{$fname}";
			//skip if file has been updated within the last 10 seconds
			$skip=0;
			if(is_file($afile)){
				$mtime=filemtime($afile);
				$etime=time()-$mtime;
				if((integer)$etime < 10){
					$skip=1;
				}
			}
			if($skip==0){
				$results='';
				$cmd="tail -n {$rowcount} \"{$v}\"";
				$ok=cronMessage($cmd);
				$out=cmdResults($cmd);
				//$results=$cmd.PHP_EOL;
				if(strlen($out['stdout'])){
					$results.=$out['stdout'];
				}
				if(strlen($out['stderr'])){
					$results.=PHP_EOL.$out['stderr'];
				}
				setFileContents($afile,$results);
			}
		}
	}
}

/**
* @exclude  - this function is for internal use only- excluded from docs
*/
function cronMessage($msg,$separate=0){
	global $cronlog_id;
	if($cronlog_id != 0){
		$ok=commonCronLog($msg);
	}
	return commonLogMessage('cron_worker',$msg,$separate,1);
}
/**
* @exclude  - this function is for internal use only- excluded from docs
*/
function cronUpdate($id,$params){
	$params['-table']='_cron';
	$params['-where']="_id={$id}";
	$ok=editDBRecord($params);
	//echo "cronUpdate".printValue($ok).printValue($params);
	return $ok;
}
/**
* @exclude  - this function is for internal use only- excluded from docs
*/
function cronDBConnect(){
	global $CONFIG;
	global $dbh;
	global $sel;
	//$ok=cronMessage("{$CONFIG['name']} - Connecting to {$CONFIG['dbname']} on {$CONFIG['dbhost']}");
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
