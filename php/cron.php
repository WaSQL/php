<?php
/*
	Instructions:
		run cron.php from a command-line every minute as follows
		you can run multiple to handle heavy loads - it will handle the queue
		On linux, add to the crontab
		* * * * * /var/www/wasql_live/php/cron.sh >/var/www/wasql_live/php/cron.log 2>&1
		On Windows, add it as a scheduled task
	Note: cron.php cannot be run from a URL, it is a command line app only.
*/
//set time limit to a large number so the cron does not time out
ini_set('max_execution_time', 72000);
set_time_limit(72000);
error_reporting(E_ALL & ~E_NOTICE);

$progpath=dirname(__FILE__);
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("$progpath/common.php");
//only allow this to be run from CLI
if(!isCLI()){
	cronMessage("Cron.php is a command line app only.");
	exit;
}
$_SERVER['TIME_START']=microtime(true);
global $ConfigXml;
global $allhost;
global $dbh;
global $sel;
global $CONFIG;
$_SERVER['HTTP_HOST']='localhost';
include_once("$progpath/config.php");
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("$progpath/wasql.php");
include_once("$progpath/database.php");
include_once("$progpath/user.php");
global $databaseCache;
cronMessage(count($ConfigXml).' hosts in config file');
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
	if(isset($CONFIG['cron']) && $CONFIG['cron']==0){
		cronMessage("Cron set to 0");
    	continue;
	}
	ksort($CONFIG);
	//echo printValue($CONFIG);
	//connect to this database.
	$dbh='';
	cronMessage("connecting");
	$ok=cronDBConnect();
	if($ok != 1){
    	cronMessage("failed to connect: {$ok}");
    	continue;
	}
	//if any crons are set to active and running and have been running for over 3 hours then they are not running anymore
	$ok=editDBRecord(array(
		'-table'	=> '_cron',
		'-where'	=> "running=1 and active=1 and run_date < (NOW() - INTERVAL 4 HOUR)",
		'running'	=> 0,
		'cron_pid'	=> 0
	));
	//get page names to determine if cron is a page
	$pages=getDBRecords(array(
		'-table'	=> '_pages',
		'-fields'	=> 'name,_id',
		'-index'	=> 'name'
	));
	//echo "Checking {$CONFIG['name']}\n";
$wherestr=<<<ENDOFWHERE
active=1 and running != 1 and run_cmd is not null
	and (date(begin_date) >= date(now()) or begin_date is null)
	and (date(end_date) <= date(now()) or end_date is null)
ENDOFWHERE;
	$recopts=array(
		'-table'	=> '_cron',
		'-fields'	=> '_id,name,run_cmd,running,run_date,frequency,run_format,run_values',
		'-where'	=> $wherestr
	);
	$cronfields=getDBFields('_cron');
	if(in_array('run_as',$cronfields)){$recopts['-fields'].=',run_as';}
	$recs=getDBRecords($recopts);
	//echo printValue($recs);exit;
	if(is_array($recs) && count($recs)){
		$cnt=count($recs);
		cronMessage("{$cnt} crons found. Checking...");
		foreach($recs as $rec){
			$run=0;
			//should this cron be run now?  check frequency...
			$ctime=time();
			if(strlen($rec['run_date'])){
				$runtime=strtotime($rec['run_date']);
			}
			else{$runtime=0;}
			//frequency or run format
			if($rec['frequency'] > 0){
				$seconds=$rec['frequency']*60;
				$diff=$ctime-$runtime;
				if($diff > $seconds){
                	$run=1;
				}
			}
			elseif(strlen($rec['run_format']) && strlen($rec['run_values'])){
				$cvalue=date($rec['run_format']);
				$values=preg_split('/\,/',$rec['run_values']);
				foreach($values as $value){
					//echo "cron name:{$rec['name']} run value:{$value}, current value:{$cvalue}<br>\n";
                	if($cvalue==$value){$run=1;break;}
				}

			}
			//skip if it has been run in the last minute
			if(strlen($rec['run_date'])){
				$ctime=time();
            	$lastruntime=strtotime($rec['run_date']);
            	$diff=$ctime-$lastruntime;
            	if($diff < 60){$run=0;}
			}
			//reset running if it has been over an hour
			if($rec['running']==1){
				if(strlen($rec['run_date'])){
					$ctime=time();
	            	$lastruntime=strtotime($rec['run_date']);
	            	$diff=$ctime-$lastruntime;
	            	if($diff > 7200){
                    	$ok=cronUpdate($rec['_id'],array('running'=>0));
					}
				}
			}
			if($run==0){continue;}
			//get record again to insure another process is not running it.
			$rec=getDBRecord(array(
				'-table'	=> '_cron',
				'_id'		=> $rec['_id']
			));
			//skip if running
			if($rec['running']==1){continue;}
			cronMessage("running {$rec['name']}");
			//update record to show we are now running
			$start=time();
			$run_date=date('Y-m-d H:i:s');
			$cron_pid=getmypid();
			$ok=executeSQL("update _cron set cron_pid={$cron_pid},running=1,run_date='{$run_date}' where running=0 and _id={$rec['_id']}");

			//make sure only one cron runs this entry
			$rec=getDBRecord(array(
				'-table'	=> '_cron',
				'_id'		=> $rec['_id']
			));
			if($rec['cron_pid'] != $cron_pid){
				continue;
			}
        	$cmd=$rec['run_cmd'];
        	$result='';
			if(isset($pages[$cmd])){
				cronMessage("cron is a page");
            	//cron is a page.
            	$url="http://{$CONFIG['name']}/{$cmd}";
            	$postopts=array('-method'=>'GET','-follow'=>1,'-ssl'=>1,'cron_id'=>$rec['_id'],'cron_name'=>$rec['name'],'cron_guid'=>generateGUID());
            	//if they have specified a run_as then login as that person
            	if(isset($rec['run_as']) && isNum($rec['run_as'])){
                	$urec=getDBRecord(array(
						'-table'=>'_users',
						'_id'	=> $rec['run_as'],
						'-fields'=>'_id,username'
					));
					if(isset($urec['_id'])){
                    	$postopts['apikey']=encodeUserAuthCode($urec['_id']);
                    	$postopts['_noguid']=1;
                    	$postopts['_auth']=1;
                    	$postopts['username']=$urec['username'];
					}
				}
				//echo $url.printValue($postopts);
            	$post=postURL($url,$postopts);
            	$result=$post['body'];
			}
			elseif(preg_match('/^<\?\=/',$cmd)){
            	//cron is a php command
            	cronMessage("cron is a PHP command");
            	$result=evalPHP($cmd);
            	if(is_array($result)){$result=printValue($result);}
			}
			elseif(preg_match('/^http/i',$cmd)){
            	//cron is a URL.
            	cronMessage("cron is a url");
            	$post=postURL($cmd,array('-method'=>'GET','-follow'=>1,'-ssl'=>1,'cron_id'=>$rec['_id'],'cron_name'=>$rec['name'],'cron_guid'=>generateGUID()));
				$result = $post['body'];
			}
			else{
            	//cron is a command
            	cronMessage("cron is a command");
            	$cmd=cmdResults($cmd);
            	if(isset($cmd['stdout']) && strlen($cmd['stdout'])){
            		$result=$cmd['stdout'];
				}
			}
			$stop=time();
			$run_length=$stop-$start;
			//update record to show wer are now finished
			$ok=cronUpdate($rec['_id'],array(
				'running'		=> 0,
				'run_length'	=> $run_length,
				'run_result'	=> $result
			));
			cronMessage("set running to zero".printValue($ok));
			//cleanup _cronlog older than 1 year or $CONFIG['cronlog_max']
			if(!isset($CONFIG['cronlog_max']) || !isNum($CONFIG['cronlog_max'])){$CONFIG['cronlog_max']=365;}
			$ok=cleanupDBRecords('_cronlog',$CONFIG['cronlog_max']);
			//add to the _cronlog table
			$opts=array(
				'-table'	=> '_cronlog',
				'cron_id'	=> $rec['_id'],
				'cron_pid'	=> $cron_pid,
				'name'		=> $rec['name'],
				'run_cmd'	=> $rec['run_cmd'],
				'run_date'	=> $run_date,
				'run_length'=> $run_length,
				'run_result'=> $result
			);
			$ok=addDBRecord($opts);
			cronMessage("finished");
			exit;
		}
	}
}
exit;
//---------- begin function cronUpdate
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronMessage($msg){
	global $CONFIG;
	global $mypid;
	$ctime=time();
	echo "{$ctime},{$mypid},{$CONFIG['name']},{$msg}".PHP_EOL;
	return;
}
//---------- begin function cronUpdate
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronUpdate($id,$params){
	$params['-table']='_cron';
	$params['-where']="_id={$id}";
	$ok=editDBRecord($params);
	//echo "cronUpdate".printValue($ok).printValue($params);
	return $ok;
}
//---------- begin function cronUpdate
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
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
