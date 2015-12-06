<?php
/*
	Instructions:
		run cron.php from a command-line every minute.
		you can run multiple to handle heavy loads - it will handle the queue
		On linux, add to the crontab ./php cron.php
		On Windows, add it as a scheduled task
	Note: cron.php cannot be run from a URL, it is a command line app only.
*/
//set time limit to a large number so the cron does not time out
ini_set('max_execution_time', 7200);
set_time_limit(7200);
error_reporting(E_ALL & ~E_NOTICE);
global $TIME_START;
$TIME_START=microtime(true);
$progpath=dirname(__FILE__);
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("$progpath/common.php");
//only allow this to be run from CLI
if(!isCLI()){
	echo "Cron.php is a command line app only.";
	exit;
}
global $ConfigXml;
global $allhost;
global $dbh;
global $sel;
global $CONFIG;
include_once("$progpath/config.php");
include_once("$progpath/wasql.php");
include_once("$progpath/database.php");
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
    	continue;
	}
	ksort($CONFIG);
	//echo printValue($CONFIG);
	//connect to this database.
	$ok=cronDBConnect();
	if($ok != 1){
    	echo "[{$CONFIG['name']}] {$ok}\n";
    	continue;
	}
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
	$recs=getDBRecords(array(
		'-table'	=> '_cron',
		'-where'	=> $wherestr
	));
	if(is_array($recs) && count($recs)){
		$cnt=count($recs);
		//echo  "[{$CONFIG['name']}] {$cnt} crons found\n";
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
				$values=preg_split('//',$rec['run_values']);
				foreach($values as $value){
                	if($cvalue==$value){$run=1;break;}
				}

			}
			//skip if it has been run in the last 5 minutes
			if(strlen($rec['run_date'])){
				$ctime=time();
            	$lastruntime=strtotime($rec['run_date']);
            	$diff=$ctime-$lastruntime;
            	if($diff < 300){$run=0;}
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
			$rec=getDBRecord(array('-table'=>'_cron','_id'=>$rec['_id']));
			//skip if running
			if($rec['running']==1){continue;}
			//update record to show wer are now running
			$start=time();
			$run_date=date('Y-m-d H:i:s');
			$cron_pid=getmypid();
 			$ok=cronUpdate($rec['_id'],array(
				'cron_pid'	=> $cron_pid,
				'running'	=> 1,
				'run_date'	=> $run_date
			));
        	$cmd=$rec['run_cmd'];
        	$result='';
			if(isset($pages[$cmd])){
            	//cron is a page.
            	$url="http://{$CONFIG['name']}/{$cmd}";
            	$post=postURL($url,array('-method'=>'GET'));
            	$result=$post['body'];
			}
			elseif(preg_match('/^<\?\=/',$cmd)){
            	//cron is a php command
            	$result=evalPHP($cmd);
            	if(is_array($result)){$result=printValue($result);}
			}
			elseif(preg_match('/^http/',$cmd)){
            	//cron is a URL.
            	$post=postURL($cmd,array('-method'=>'GET','-follow'=>1,'-ssl'=>1));
            	$result="CURL INFO:\r\n";
            	foreach($post['curl_info'] as $k=>$v){
                	if(is_array($v)){$v=implode("\r\n",$v);}
                	$v=trim($v);
                	$result .="\t{$k} = {$v}\r\n";
				}
				$result .= "\r\nRETURN RESULT:\r\n";
				$result .= $post['body'];
			}
			else{
            	//cron is a command
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
		}
	}
}
exit;

function cronUpdate($id,$params){
	$params['-table']='_cron';
	$params['-where']="_id={$id}";
	$ok=editDBRecord($params);
	//echo "cronUpdate".printValue($ok).printValue($params);
	return $ok;
}
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