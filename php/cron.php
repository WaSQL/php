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
$starttime=microtime(true);
$progpath=dirname(__FILE__);
global $logfile;
$scriptname=basename(__FILE__, '.php');
$logfile="{$progpath}/{$scriptname}.log";
//echo $logfile;exit;
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("{$progpath}/common.php");
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
include_once("{$progpath}/config.php");
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("{$progpath}/wasql.php");
include_once("{$progpath}/database.php");
include_once("{$progpath}/user.php");
global $databaseCache;
$etime=microtime(true)-$starttime;
$etime=(integer)$etime;
while($etime < 55){
	if(!count($ConfigXml)){break;}
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
			//cronMessage("Cron set to 0");
			unset($ConfigXml[$name]);
	    	continue;
		}
		//ksort($CONFIG);
		//echo printValue($CONFIG);
		//connect to this database.
		$dbh='';
		//cronMessage("connecting");
		$ok=cronDBConnect();
		if($ok != 1){
	    	cronMessage("failed to connect: {$ok}");
	    	unset($ConfigXml[$name]);
	    	continue;
		}
		//check for apache_access_log
		if(isset($CONFIG['apache_access_log']) && file_exists($CONFIG['apache_access_log'])){
			loadExtras('apache');
			cronMessage("running apacheParseLogFile...".$CONFIG['apache_access_log']);
			$msg=apacheParseLogFile();
			if(strlen($msg)){cronMessage($msg);}
			cronMessage("apacheParseLogFile completed");
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
		and (date(now()) >= date(begin_date) or begin_date is null or length(begin_date)=0)
		and (date(end_date) <= date(now()) or end_date is null or length(end_date)=0)
	    and (run_date < date_sub(now(), interval 1 minute) or run_date is null or length(run_date)=0)
ENDOFWHERE;
		$recopts=array(
			'-table'	=> '_cron',
			'-fields'	=> '_id,name,run_cmd,running,run_date,frequency,run_format,run_values',
			'-where'	=> $wherestr,
			'-nocache'	=> 1
		);
		$cronfields=getDBFields('_cron');
		if(!is_array($cronfields)){
			unset($ConfigXml[$name]);
			cronMessage("cronfields in _cron is empty.");
			continue;
		}
		if(in_array('run_as',$cronfields)){$recopts['-fields'].=',run_as';}
		$recs=getDBRecords($recopts);
		if(!is_array($recs) || count($recs)==0){
			unset($ConfigXml[$name]);
	        //cronMessage("No crons found.");
	        continue;
		}
		foreach($recs as $ri=>$rec){
			if(isset($ConfigXml[$name]['processed'][$rec['_id']])){
				unset($recs[$ri]);
			}
		}
		if(count($recs)==0){
			unset($ConfigXml[$name]);
	        //cronMessage("No crons found.");
	        continue;
		}
		if(is_array($recs) && count($recs)){
			$cnt=count($recs);
			//cronMessage("{$cnt} crons found. Checking...");
			foreach($recs as $ri=>$rec){
				if(isset($ConfigXml[$name]['processed'][$rec['_id']])){continue;}
				$ConfigXml[$name]['processed'][$rec['_id']]=1;
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
						//cronMessage("cron name:{$rec['name']} run_format value:{$value}, current value:{$cvalue}, run: {$run}");
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
					'_id'		=> $rec['_id'],
					'-nocache'	=> 1,
					'-fields'	=> '_id,running,cron_pid'
				));
				//skip if running
				if($rec['running']==1){continue;}
				//update record to show we are now running
				$start=time();
				$run_date=date('Y-m-d H:i:s');
				$cron_pid=getmypid();
				//echo $ok.printValue($rec);
				cronMessage("handshaking {$rec['name']}");
				$editopts=array(
					'-table'	=> '_cron',
					'-where'	=> "running=0 and _id={$rec['_id']}",
					'cron_pid'	=> $cron_pid,
					'running'	=> 1,
					'run_date'	=> $run_date
				);
				$ok=editDBRecord($editopts);
				//echo $ok.printValue($editopts);
				//make sure only one cron runs this entry
				$rec=getDBRecord(array(
					'-table'	=> '_cron',
					'_id'		=> $rec['_id'],
					'-nocache'	=> 1,
					'-fields'	=> '_id,running,cron_pid'
				));
				//echo $ok.printValue($rec);
				if($rec['cron_pid'] != $cron_pid){
					cronMessage("handshaking {$rec['name']} failed. {$rec['cron_pid']} != {$cron_pid}");
					continue;
				}
				$rec=getDBRecord(array(
					'-table'	=> '_cron',
					'_id'		=> $rec['_id'],
					'-nocache'	=> 1
				));
				$cmd=$rec['run_cmd'];
				$lcmd=strtolower(trim($cmd));
				if(isset($pages[$lcmd])){
					//cronMessage("cron is a page");
					$crontype='Page';
				}
				elseif(preg_match('/^<\?\=/',$cmd)){
	            	//cron is a php command
	            	$crontype='PHP Command';
				}
				elseif(preg_match('/^http/i',$cmd)){
	            	//cron is a URL.
	            	$crontype='URL';
				}
				else{
	            	//cron is a command
	            	$crontype='OS Command';
				}
				cronMessage("running {$crontype} {$rec['name']}");
	        	
	        	$result='';
				$result .= 'StartTime: '.date('Y-m-d H:i:s').PHP_EOL; 
				$result .= "CronType: {$crontype} ".PHP_EOL;
				
				$crontype='unknown';
				if(isset($pages[$lcmd])){
	            	//cron is a page.
	            	$url="http://{$CONFIG['name']}/{$cmd}";
	                $result .= "CronURL: {$url}".PHP_EOL;
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
	            	$result .= 'Headers Sent:'.PHP_EOL.printValue($post['headers_out']).PHP_EOL;
	            	$result .= 'CURL Info:'.PHP_EOL.printValue($post['curl_info']).PHP_EOL;
	            	$result .= 'Headers Received:'.PHP_EOL.printValue($post['headers']).PHP_EOL;
	            	$result .= 'Content Received:'.PHP_EOL.$post['body'].PHP_EOL;
				}
				elseif(preg_match('/^<\?\=/',$cmd)){
	            	//cron is a php command
	                $result .= 'Output Received:'.PHP_EOL;
	            	$out=evalPHP($cmd).PHP_EOL;
	            	if(is_array($out)){$result.=printValue($out).PHP_EOL;}
	            	else{$result.=$out.PHP_EOL;}
				}
				elseif(preg_match('/^http/i',$cmd)){
	            	//cron is a URL.
	            	$post=postURL($cmd,array('-method'=>'GET','-follow'=>1,'-ssl'=>1,'cron_id'=>$rec['_id'],'cron_name'=>$rec['name'],'cron_guid'=>generateGUID()));
					$result .= 'Headers Sent:'.PHP_EOL.printValue($post['headers_out']).PHP_EOL;
	            	$result .= 'CURL Info:'.PHP_EOL.printValue($post['curl_info']).PHP_EOL;
	            	$result .= 'Headers Received:'.PHP_EOL.printValue($post['headers']).PHP_EOL;
	            	$result .= 'Content Received:'.PHP_EOL.$post['body'].PHP_EOL;
				}
				else{
	            	//cron is an OS Command
	            	$out=cmdResults($cmd);
	            	$result .= 'Output Received:'.PHP_EOL;
	            	$result .= printValue($out).PHP_EOL;
				}

				$stop=time();
				$run_length=$stop-$start;
	            $result .= PHP_EOL;
	            $result .= 'EndTime: '.date('Y-m-d H:i:s').PHP_EOL;
				//update record to show wer are now finished
				$ok=cronUpdate($rec['_id'],array(
					'running'		=> 0,
					'cron_pid'		=> 0,
					'run_length'	=> $run_length,
					'run_result'	=> $result
				));
				$runtime=verboseTime($run_length);
				cronMessage("finished {$rec['name']}. Run Length:{$runtime}");
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
				break;
			}
		}
		
		$etime=microtime(true)-$starttime;
		$etime=(integer)$etime;
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
	global $logfile;
	if(!strlen($mypid)){$mypid=getmypid();}
	$ctime=time();
	$msg="{$ctime},{$mypid},{$CONFIG['name']},{$msg}".PHP_EOL;
	echo $msg;
	if(!file_exists($logfile) || filesize($logfile) > 1000000 ){
        setFileContents($logfile,$msg);
    }
    else{
        appendFileContents($logfile,$msg);
    }
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
