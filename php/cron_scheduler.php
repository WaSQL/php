<?php
/*
	Instructions:
		run cron_scheduler.php from a command-line every minute as follows
		On linux, add to the crontab
* * * * * php /var/www/wasql/php/cron_scheduler.php >/var/www/wasql/php/cron_scheduler.log 2>&1
* * * * * /var/www/wasql/php/cron_worker.sh >/var/www/wasql/php/cron_worker.log 2>&1
		On Windows, add it as a scheduled task - Command Prompt as administrator:
			https://www.windowscentral.com/how-create-task-using-task-scheduler-command-prompt
			Create:
				SCHTASKS /CREATE /SC MINUTE /MO 1 /TN "WaSQL\WaSQL_Cron_1" /TR "php.exe d:\wasql\php\cron_scheduler.php" /RU administrator
			List:
				SCHTASKS /QUERY
			Delete:
				SCHTASKS /DELETE /TN "WaSQL\WaSQL_Cron_1"


	Note: cron.php cannot be run from a URL, it is a command line app only.
*/
//set time limit to a large number so the cron does not time out
ini_set('max_execution_time', 72000);
set_time_limit(72000);
error_reporting(E_ALL & ~E_NOTICE);
$posturl_timeout=72000; //allow crons that call posturl to run for up to 24 hours
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
	cronMessage("Cron.php is a command line app only.");
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
	cronMessage("ERROR - running mysql version {$dbversion}. cron_scheduler.php requires mysql version 5.7 or greater.  For older mysql versions run cron_old.php instead.");
	exit;
}

global $databaseCache;
$etime=microtime(true)-$starttime;
$etime=(integer)$etime;
$pid_check=1;
$wherestr_all=cronBuildWhere();
if(!count($ConfigXml)){exit;}
$tpath=getWaSQLPath('php/temp');
$cron_tail_log="{$tpath}/cron_tail.log";
$cron_pid=getmypid();

//should switch to ALLCONFIG
$loop=2;
$loop_cnt=0;
if(isset($argv[1])){
	$loop=(integer)$argv[1];
}
while(1){
	$loop_cnt+=1;
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
	    	cronMessage("{$CONFIG['name']} - failed to connect: {$ok}");
	    	unset($ConfigXml[$name]);
	    	continue;
		}
		/*
			fix any issues with the cron table
			fix any cron record issues
			set any crons that are ready to run_now 
		*/
		//fix any issues with the cron table
		$ok=commonCronCheckSchema();
		//fix any cron record issues
		$ok=executeSQL("UPDATE _cron set frequency_max='minute' WHERE frequency_max is null or frequency_max =''");
		//2. set any crons that are ready to run_now
		$recs=getDBRecords(array(
			'-table'=>'_cron',
			'-where'=>$wherestr_all,
			'-fields'=>'_id,name',
			'-nocache'=>1
		));
		if(is_array($recs)){
			$cnt=count($recs);
			//$ok=cronMessage("setting the following crons to run_now");
			$ids=array();
			foreach($recs as $rec){
				$ids[]=$rec['_id'];
				$ok=cronMessage("db:{$CONFIG['name']}, cron_id:{$rec['_id']}, cron_name:{$rec['name']}, msg:  - run_now set to 1");
			}
			$idstr=implode(',',$ids);
			$query=<<<ENDOFSQL
		UPDATE _cron 
		SET 
			cron_pid=0,
			running=0,
			run_now=1,
			stop_now=0 
		WHERE 
			running=0 
			and _id in ({$idstr})
ENDOFSQL;
			$ok=executeSQL($query);
			//echo $query.printValue($ok);exit;
		}
		
		//check for wasql.update file
		if(file_exists("{$tpath}/wasql.update")){
			cronMessage("db:{$CONFIG['name']}, cron_id:{$rec['_id']}, cron_name:{$rec['name']}, msg: WaSQL update",1);
			unlink("{$tpath}/wasql.update");
			$out=cmdResults('git pull');
			$message="Cmd: {$out['cmd']}<br><pre style=\"margin-bottom:0px;margin-left:10px;padding:10px;background:#f0f0f0;display:inline-block;border:1px solid #ccc;border-radius:3px;\">{$out['stdout']}".PHP_EOL.$out['stderr']."</pre>";
			$ok=setFileContents("{$tpath}/wasql.update.log",$message);
		}
		//cleanup _cronlog older than 1 year or $CONFIG['cronlog_max']
		if(!isset($CONFIG['cronlog_max']) || !isNum($CONFIG['cronlog_max'])){$CONFIG['cronlog_max']=365;}
		$ok=cleanupDBRecords('_cronlog',$CONFIG['cronlog_max']);
		//cronlog tails?
		if(!file_exists($cron_tail_log)){
			$ok=setFileContents($cron_tail_log,time());
			$ok=cronLogTails();
		}
		elseif(filemtime($cron_tail_log)-time() > 60){
			$ok=setFileContents($cron_tail_log,time());
			$ok=cronLogTails();
		}
		//apache?
		if($apache_log==1 && isset($CONFIG['apache_access_log']) && file_exists($CONFIG['apache_access_log'])){
			$apache_log=0;
			loadExtras('apache');
			cronMessage("db:{$CONFIG['name']}, cron_id:{$rec['_id']}, cron_name:{$rec['name']}, msg: apacheParseLogFile");
			$msg=apacheParseLogFile();
			if(strlen($msg)){
				cronMessage("db:{$CONFIG['name']}, cron_id:{$rec['_id']}, cron_name:{$rec['name']}, msg: apacheParseLogFile -- {$msg}");
			}
			//cronMessage("FINISHED *** apacheParseLogFile *** -- ".$CONFIG['apache_access_log'],1);
		}
	}
	if($loop_cnt >= $loop || $loop_cnt >= 10){
		break;
	}
	//cronMessage('------ sleeping ----');
	sleep(30);
}
exit;
/* cron functions */
/** --- function cronBuildWhere
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronBuildWhere(){
	return <<<ENDOFWHERE
active = 1 
and paused != 1 
and running != 1 
and run_now != 1
and run_cmd is not null
and cron_pid=0
and length(run_cmd) > 0
and json_valid(run_format)=1
and (
	ifnull(begin_date,'')=''
	or date(now()) >= date(begin_date)
	)
and (
	ifnull(end_date,'')=''
	or date(end_date) <= date(now())
	)
and 
	(
	ifnull(frequency_max,'')='' 
	or ifnull(run_date,'')=''
	or (frequency_max='minute' and minute(run_date) != minute(now()))
	or (frequency_max='hourly' and hour(run_date) != hour(now()))
	or (frequency_max='daily' and date(run_date) != date(now()))
	or (frequency_max='weekly' and week(run_date) != week(now()))
	or (frequency_max='monthly' and month(run_date) != month(now()))
	or (frequency_max='quarterly' and quarter(run_date) != quarter(now()))
	or (frequency_max='yearly' and year(run_date) != year(now()))
	)
and
	(
	run_format->>'\$.minute[0]'=-1
	or MINUTE(now()) in (
		run_format->>'\$.minute[0]',
		run_format->>'\$.minute[1]',
		run_format->>'\$.minute[2]',
		run_format->>'\$.minute[3]',
		run_format->>'\$.minute[4]',
		run_format->>'\$.minute[5]',
		run_format->>'\$.minute[6]',
		run_format->>'\$.minute[7]',
		run_format->>'\$.minute[8]',
		run_format->>'\$.minute[9]',
		run_format->>'\$.minute[10]',
		run_format->>'\$.minute[11]',
		run_format->>'\$.minute[12]',
		run_format->>'\$.minute[13]',
		run_format->>'\$.minute[14]',
		run_format->>'\$.minute[15]',
		run_format->>'\$.minute[16]',
		run_format->>'\$.minute[17]',
		run_format->>'\$.minute[18]',
		run_format->>'\$.minute[19]',
		run_format->>'\$.minute[20]',
		run_format->>'\$.minute[21]',
		run_format->>'\$.minute[22]',
		run_format->>'\$.minute[23]',
		run_format->>'\$.minute[24]',
		run_format->>'\$.minute[25]',
		run_format->>'\$.minute[26]',
		run_format->>'\$.minute[27]',
		run_format->>'\$.minute[28]',
		run_format->>'\$.minute[29]',
		run_format->>'\$.minute[30]',
		run_format->>'\$.minute[31]',
		run_format->>'\$.minute[32]',
		run_format->>'\$.minute[33]',
		run_format->>'\$.minute[34]',
		run_format->>'\$.minute[35]',
		run_format->>'\$.minute[36]',
		run_format->>'\$.minute[37]',
		run_format->>'\$.minute[38]',
		run_format->>'\$.minute[39]',
		run_format->>'\$.minute[40]',
		run_format->>'\$.minute[41]',
		run_format->>'\$.minute[42]',
		run_format->>'\$.minute[43]',
		run_format->>'\$.minute[44]',
		run_format->>'\$.minute[45]',
		run_format->>'\$.minute[46]',
		run_format->>'\$.minute[47]',
		run_format->>'\$.minute[48]',
		run_format->>'\$.minute[49]',
		run_format->>'\$.minute[50]',
		run_format->>'\$.minute[51]',
		run_format->>'\$.minute[52]',
		run_format->>'\$.minute[53]',
		run_format->>'\$.minute[54]',
		run_format->>'\$.minute[55]',
		run_format->>'\$.minute[56]',
		run_format->>'\$.minute[57]',
		run_format->>'\$.minute[58]',
		run_format->>'\$.minute[59]'
		)
	)
and
	(
	run_format->>'\$.hour[0]'=-1
	or HOUR(NOW()) in (
		run_format->>'\$.hour[0]',
		run_format->>'\$.hour[1]',
		run_format->>'\$.hour[2]',
		run_format->>'\$.hour[3]',
		run_format->>'\$.hour[4]',
		run_format->>'\$.hour[5]',
		run_format->>'\$.hour[6]',
		run_format->>'\$.hour[7]',
		run_format->>'\$.hour[8]',
		run_format->>'\$.hour[9]',
		run_format->>'\$.hour[10]',
		run_format->>'\$.hour[11]',
		run_format->>'\$.hour[12]',
		run_format->>'\$.hour[13]',
		run_format->>'\$.hour[14]',
		run_format->>'\$.hour[15]',
		run_format->>'\$.hour[16]',
		run_format->>'\$.hour[17]',
		run_format->>'\$.hour[18]',
		run_format->>'\$.hour[19]',
		run_format->>'\$.hour[20]',
		run_format->>'\$.hour[21]',
		run_format->>'\$.hour[22]',
		run_format->>'\$.hour[23]'
		)
	)
and
	(
	run_format->>'\$.day[0]'=-1
	or DAY(curdate()) in (
		run_format->>'\$.day[0]',
		run_format->>'\$.day[1]',
		run_format->>'\$.day[2]',
		run_format->>'\$.day[3]',
		run_format->>'\$.day[4]',
		run_format->>'\$.day[5]',
		run_format->>'\$.day[6]',
		run_format->>'\$.day[7]',
		run_format->>'\$.day[8]',
		run_format->>'\$.day[9]',
		run_format->>'\$.day[10]',
		run_format->>'\$.day[11]',
		run_format->>'\$.day[12]',
		run_format->>'\$.day[13]',
		run_format->>'\$.day[14]',
		run_format->>'\$.day[15]',
		run_format->>'\$.day[16]',
		run_format->>'\$.day[17]',
		run_format->>'\$.day[18]',
		run_format->>'\$.day[19]',
		run_format->>'\$.day[20]',
		run_format->>'\$.day[21]',
		run_format->>'\$.day[22]',
		run_format->>'\$.day[23]',
		run_format->>'\$.day[24]',
		run_format->>'\$.day[25]',
		run_format->>'\$.day[26]',
		run_format->>'\$.day[27]',
		run_format->>'\$.day[28]',
		run_format->>'\$.day[29]',
		run_format->>'\$.day[30]'
		)
	)
and
	(
	run_format->>'\$.month[0]'=-1
	or MONTH(curdate()) in (
		run_format->>'\$.month[0]',
		run_format->>'\$.month[1]',
		run_format->>'\$.month[2]',
		run_format->>'\$.month[3]',
		run_format->>'\$.month[4]',
		run_format->>'\$.month[5]',
		run_format->>'\$.month[6]',
		run_format->>'\$.month[7]',
		run_format->>'\$.month[8]',
		run_format->>'\$.month[9]',
		run_format->>'\$.month[10]',
		run_format->>'\$.month[11]'
		)
	)
and
	(
	ifnull(run_format->>'\$.dayname[0]','')=''
	or run_format->>'\$.dayname[0]'=-1
	or WEEKDAY(curdate()) in (
		run_format->>'\$.dayname[0]',
		run_format->>'\$.dayname[1]',
		run_format->>'\$.dayname[2]',
		run_format->>'\$.dayname[3]',
		run_format->>'\$.dayname[4]',
		run_format->>'\$.dayname[5]',
		run_format->>'\$.dayname[6]'
		)
	)
ENDOFWHERE;
}
/** --- function cronCleanRecords
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronCleanRecords($cron=array()){
	if(!isset($cron['_id'])){return false;}
	if(!isNum($cron['records_to_keep'])){return false;}
	//get the 
	$recs=getDBRecords(array(
		'-table'=>'_cronlog',
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
		'-table'=>'_cronlog',
		'-where'=>"_id < {$min} and cron_id='{$cron['_id']}'"
	));
	return $ok;
}
/** --- function cronLogTails
* @exclude  - this function is for internal use only and thus excluded from the manual
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
			if(!file_exists($v)){
				continue;
			}
			$fname=getFileName($v);
			$afile="{$tempdir}/{$fname}";
			//skip if file has been updated within the last 10 seconds
			$skip=0;
			if(file_exists($afile)){
				$mtime=filemtime($afile);
				$etime=time()-$mtime;
				if((integer)$etime < 10){
					$skip=1;
				}
			}
			if($skip==0){
				$results='';
				$cmd="tail -n {$rowcount} \"{$v}\"";
				//$ok=cronMessage($cmd);
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

/** --- function cronMessage
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronMessage($msg,$separate=0){
	global $cronlog_id;
	if($cronlog_id != 0){
		$ok=commonCronLog($msg);
	}
	return commonLogMessage('cron_scheduler',$msg,$separate,1);
}
/** --- function cronUpdate
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronUpdate($id,$params){
	$params['-table']='_cron';
	$params['-where']="_id={$id}";
	$ok=editDBRecord($params);
	//echo "cronUpdate".printValue($ok).printValue($params);
	return $ok;
}
/** --- function cronDBConnect
* @exclude  - this function is for internal use only and thus excluded from the manual
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
		if(isPostgreSQL()){$error .= "<br>PostgreSQL does not allow CREATE DATABASE inside a transaction block. Create the database first.";}
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
