<?php
/*
	Instructions:
		run cron.php from a command-line every minute as follows
		you can run multiple to handle heavy loads - it will handle the queue
		On linux, add to the crontab
		* * * * * /var/www/wasql_live/php/cron.sh >/var/www/wasql_live/php/cron.log 2>&1
		On Windows, add it as a scheduled task - Command Prompt as administrator:
			https://www.windowscentral.com/how-create-task-using-task-scheduler-command-prompt
			Create:
				SCHTASKS /CREATE /SC MINUTE /MO 1 /TN "WaSQL\WaSQL_Cron_1" /TR "php.exe d:\wasql\php\cron.php" /RU administrator
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
$starttime=microtime(true);
$progpath=dirname(__FILE__);
global $logfile;
$scriptname=basename(__FILE__, '.php');
$wpath=dirname( dirname(__FILE__) );
if(PHP_OS == 'WINNT' || PHP_OS == 'WIN32' || PHP_OS == 'Windows'){
	$logfile="{$wpath}\\logs\\{$scriptname}.log";
	$logfile=str_replace("/","\\",$logfile);
}
else{
   	$logfile="{$wpath}/logs/{$scriptname}.log";
}
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
//cronMessage("Logfile: {$logfile}");
global $ConfigXml;
global $allhost;
global $dbh;
global $sel;
global $CONFIG;
global $cron_id;
global $CRONTHRU;
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
$apache_log=1;
$tail=1;
$wherestr_all=cronBuildWhere();
if(!count($ConfigXml)){exit;}
//check for wasql.update file
if(file_exists("{$wpath}/php/temp/wasql.update")){
	cronMessage("STARTED *** WaSQL update ***");
	unlink("{$wpath}/php/temp/wasql.update");
	$out=cmdResults('git pull');
	cronMessage("FINISHED *** WaSQL update ***");
	$message="Cmd: {$out['cmd']}<br><pre style=\"margin-bottom:0px;margin-left:10px;padding:10px;background:#f0f0f0;display:inline-block;border:1px solid #ccc;border-radius:3px;\">{$out['stdout']}".PHP_EOL.$out['stderr']."</pre>";
	$ok=setFileContents("{$wpath}/php/temp/wasql.update.log",$message);
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
	$cron_tail_log="{$wpath}/php/temp/cron_tail.log";
	if($CONFIG['cron']==1 && $tail==1){
		if(!file_exists($cron_tail_log)){
			$ok=setFileContents($cron_tail_log,time());
			$ok=cronLogTails();
		}
		elseif(filemtime($cron_tail_log)-time() > 60){
			$ok=setFileContents($cron_tail_log,time());
			$ok=cronLogTails();
		}
	}
	//ksort($CONFIG);
	//echo printValue($CONFIG);
	//connect to this database.
	$dbh='';
	//cronMessage("connecting");
	$ok=cronDBConnect();
	if($ok != 1){
    	//cronMessage("failed to connect: {$ok}");
    	unset($ConfigXml[$name]);
    	continue;
	}
	$ok=executeSQL("update _cron set frequency_max='minute' where frequency_max is null or frequency_max =''");
	//check for apache_access_log
	if($apache_log==1 && isset($CONFIG['apache_access_log']) && file_exists($CONFIG['apache_access_log'])){
		$apache_log=0;
		loadExtras('apache');
		cronMessage("STARTED *** apacheParseLogFile *** -- ".$CONFIG['apache_access_log']);
		$msg=apacheParseLogFile();
		if(strlen($msg)){
			cronMessage(" -- [apacheParseLogFile] -- {$msg}");
		}
		cronMessage("FINISHED *** apacheParseLogFile *** -- ".$CONFIG['apache_access_log']);
	}
	if($CONFIG['cron']==0){
		continue;
	}
	//cronMessage("checking.  {$CONFIG['cron']}");
	$ok=commonCronCheckSchema();
	//update crons that say they are running but the pids are no longer active
	$ok=commonCronCleanup();
	//get page names to determine if cron is a page
	$pages=getDBRecords(array(
		'-table'	=> '_pages',
		'-fields'	=> 'name,_id',
		'-index'	=> 'name'
	));
	//echo "Checking {$CONFIG['name']}\n";
	//see if there is file called {dbname}_runnow.txt.  If so extract
	$wherestr=$wherestr_all;
	$recopts=array(
		'-table'	=> '_cron',
		'-fields'	=> '_id,name,run_cmd,running,run_date,unix_timestamp(now())-unix_timestamp(run_date) as last_run,frequency,run_now,run_format,run_values',
		'-where'	=> $wherestr,
		'-nocache'	=> 1,
		'-order'	=> 'run_date'
	);
	//echo $wherestr;exit;
	$cronfields=getDBFields('_cron');
	if(!is_array($cronfields)){
		unset($ConfigXml[$name]);
		cronMessage("ERROR -- cronfields in _cron is empty.".PHP_EOL);
		continue;
	}
	if(in_array('run_as',$cronfields)){$recopts['-fields'].=',run_as';}
	$recs=getDBRecords($recopts);
	//echo printValue($recs);exit;
	if(!is_array($recs) && strlen($recs)){
		cronMessage("ERROR -- {$recs}".PHP_EOL);
		exit;
	}
	$rcnt=is_array($recs)?count($recs):0;
	if($rcnt==0){
		unset($ConfigXml[$name]);
		//cronMessage("{$rcnt} crons ready".PHP_EOL);
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
		$pcnt=$rcnt=count($recs);
		//cronMessage("{$pcnt} processed. No other crons ready".PHP_EOL);
        //cronMessage("No crons found.");
        continue;
	}
	if(is_array($recs) && count($recs)){
		$cnt=count($recs);
		//cronMessage("{$cnt} crons found. Checking...");
		foreach($recs as $ri=>$rec){
			if(isset($ConfigXml[$name]['processed'][$rec['_id']])){continue;}
			$ConfigXml[$name]['processed'][$rec['_id']]=1;
		
			//skip if it has been run in the last minute
			if(strlen($rec['run_date'])){
				$ctime=time();
            	$lastruntime=strtotime($rec['run_date']);
            	$diff=$ctime-$lastruntime;
            	if($diff < 60){
            		//cronMessage("skipping - ran in the last minute:{$rec['name']}");
            		$run=0;
            	}
			}
			//reset running if it has been over post timeout
			if($rec['running']==1){
				if(strlen($rec['last_run']) && isNum($rec['last_run'])){
	            	if($rec['last_run'] > $posturl_timeout){
                    	$ok=editDBRecordById('_cron',$rec['_id'],array('running'=>0));
					}
				}
			}
			//get record again to insure another process is not running it.
			$rec=getDBRecord(array(
				'-table'	=> '_cron',
				'_id'		=> $rec['_id'],
				'-nocache'	=> 1,
				'-fields'	=> '_id,running,cron_pid'
			));
			//skip if running
			if($rec['running']==1){continue;}
			//skip if paused
			if($rec['paused']==1){continue;}
			//update record to show we are now running
			$start=$CRONTHRU['start']=microtime(true);
			$cron_pid=getmypid();
			$editopts=array(
				'-table'	=> '_cron',
				'-where'	=> "running=0 and _id={$rec['_id']}",
				'cron_pid'	=> $cron_pid,
				'running'	=> 1,
				'run_now'	=> 0,
				'run_date'	=> 'NOW()',
				'run_error'	=> '',
			);
			$ok=editDBRecord($editopts);
			//echo $ok.printValue($editopts);exit;
			//make sure only one cron runs this entry
			$rec=getDBRecord(array(
				'-table'	=> '_cron',
				'_id'		=> $rec['_id'],
				'-nocache'	=> 1,
				'-fields'	=> '_id,running,cron_pid'
			));
			//echo $ok.printValue($rec);
			if($rec['cron_pid'] != $cron_pid){
				cronMessage("ERROR -- handshaking {$rec['name']} failed. {$rec['cron_pid']} != {$cron_pid}");
				continue;
			}
			$rec=getDBRecord(array(
				'-table'	=> '_cron',
				'_id'		=> $rec['_id'],
				'-nocache'	=> 1
			));
			$cron_id=$CRONTHRU['cron_id']=$rec['_id'];
			$CRONTHRU['cron_pid']=$cron_pid;
			$CRONTHRU['cron_run_date']=$rec['run_date'];
			$CRONTHRU['cron_name']=$rec['name'];
			$CRONTHRU['cron_run_cmd']=$rec['run_cmd'];
			$path=getWaSQLPath('php/temp');
			$commonCronLogFile="{$path}/{$CONFIG['name']}_cronlog_{$rec['_id']}.txt";
			if(file_exists($commonCronLogFile)){
				unlink($commonCronLogFile);
			}
			//cronMessage("cleaning {$rec['name']}");
			$ok=cronCleanRecords($rec);
			$cmd=$rec['run_cmd'];
			$lcmd=strtolower(trim($cmd));
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
				//cronMessage("cron is a page");
				$crontype='Page';
			}
			elseif(preg_match('/^<\?\=/',$cmd)){
            	//cron is a php command
            	$crontype='PHP Command';
			}
			else{
            	//cron is a command
            	$crontype='OS Command';
			}
        	cronMessage("STARTED  *** {$rec['name']} *** - Crontype: {$crontype}".PHP_EOL);
        	$cron_result='';
			$cron_result .= 'StartTime: '.date('Y-m-d H:i:s').PHP_EOL; 
			$cron_result .= "CronType: {$crontype} ".PHP_EOL;
			$CRONTHRU['cron_guid']=generateGUID();
			if(strtolower($crontype)=='page'){
            	//cron is a page.
            	$cmd=preg_replace('/^\/+/','',$cmd);
            	$prefix='https';
            	if(isset($CONFIG['insecure']) && $CONFIG['insecure']==1){
            		$prefix='http';
            	}
            	$url="{$prefix}://{$CONFIG['name']}/{$cmd}";
                $cron_result .= "CronURL: {$url}".PHP_EOL;
                $CRONTHRU['cron_result']=$cron_result;
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
				//echo $url.printValue($postopts).printValue($CRONTHRU);exit;
				cronMessage(" -- [{$rec['name']}] -- calling {$url}");
            	$post=postURL($url,$postopts);
            	$cron_result .= '----- Content Received -----'.PHP_EOL;
            	$cron_result .= $post['body'].PHP_EOL;
            	if(stringContains($post['body'],'__cronlog_delete__')){
            		$_REQUEST['cronlog_delete']=1;
            	}
            	if(isset($post['headers_out'][0])){
	            	$cron_result .= '----- Headers Sent -----'.PHP_EOL;
	            	$cron_result .= printValue($post['headers_out']).PHP_EOL;
	            }
            	$cron_result .= '----- CURL Info -----'.PHP_EOL;
            	$cron_result .= printValue($post['curl_info']).PHP_EOL;
            	if(isset($post['headers'][0])){
	            	$cron_result .= '----- Headers Received -----'.PHP_EOL;
	            	$cron_result .= printValue($post['headers']).PHP_EOL;
	            }
            	
			}
			elseif(strtolower($crontype)=='php command'){
            	//cron is a php command
            	cronMessage(" -- [{$rec['name']}] --running eval code");
                $cron_result .= '----- Output Received -----'.PHP_EOL;

            	$out=evalPHP($cmd).PHP_EOL;
            	if(is_array($out)){$cron_result.=printValue($out).PHP_EOL;}
            	else{$cron_result.=$out.PHP_EOL;}
			}
			elseif(strtolower($crontype)=='url'){
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
            	cronMessage(" -- [{$rec['name']}] --calling $cmd");
            	$post=postURL($cmd,$postopts);
            	$cron_result .= '----- Content Received -----'.PHP_EOL;
            	$cron_result .= $post['body'].PHP_EOL;
            	if(stringContains($post['body'],'__cronlog_delete__')){
            		$_REQUEST['cronlog_delete']=1;
            	}
            	if(isset($post['headers_out'][0])){
	            	$cron_result .= '----- Headers Sent -----'.PHP_EOL;
					$cron_result .= printValue($post['headers_out']).PHP_EOL;
				}
				$cron_result .= '----- CURL Info -----'.PHP_EOL;
            	$cron_result .= printValue($post['curl_info']).PHP_EOL;
            	if(isset($post['headers'][0])){
	            	$cron_result .= '----- Headers Received -----'.PHP_EOL;
	            	$cron_result .= printValue($post['headers']).PHP_EOL;
	            }
            	
			}
			else{
            	//cron is an OS Command
            	cronMessage(" -- [{$rec['name']}] --running $cmd");
            	$out=cmdResults($cmd);
            	$cron_result .= '----- Content Received -----'.PHP_EOL;
            	$cron_result .= printValue($out).PHP_EOL;
			}

			$stop=microtime(true);
			$run_length=number_format(($stop-$start),3);
            $cron_result .= PHP_EOL;
            $cron_result .= 'EndTime: '.date('Y-m-d H:i:s').PHP_EOL;
            //limit $cron_result to 65535 chars
            if(strlen($cron_result) > 65535){
            	$cron_result=substr($cron_result,0,65535);
            }
			//update record to show we are now finished
			$run_memory=memory_get_usage();
			$eopts=array(
				'running'		=> 0,
				'cron_pid'		=> 0,
				'run_length'	=> $run_length,
				'run_result'	=> $cron_result,
				'run_memory'	=> $run_memory
			);
			$ok=editDBRecordById('_cron',$rec['_id'],$eopts);
			//echo PHP_EOL."OK".printValue($ok)."ID".$rec['_id'].printValue($eopts).PHP_EOL.PHP_EOL;
			cronMessage("FINISHED *** {$rec['name']} *** - Run Length: {$run_length} seconds".PHP_EOL);
			//cleanup _cronlog older than 1 year or $CONFIG['cronlog_max']
			if(!isset($CONFIG['cronlog_max']) || !isNum($CONFIG['cronlog_max'])){$CONFIG['cronlog_max']=365;}
			$ok=cleanupDBRecords('_cronlog',$CONFIG['cronlog_max']);
			if(file_exists($commonCronLogFile)){
				unlink($commonCronLogFile);
			}
			//add to the _cronlog table
			$opts=array(
				'-table'	=> '_cronlog',
				'cron_id'	=> $rec['_id'],
				'cron_pid'	=> $cron_pid,
				'name'		=> $rec['name'],
				'run_cmd'	=> $rec['run_cmd'],
				'run_date'	=> 'NOW()'
			);
			$lrec=getDBRecord($opts);
			if(isset($_REQUEST['cronlog_delete'])){
				//cronlog_delete was set in the script
				if(isset($lrec['_id'])){
					$ok=delDBRecordById('_cronlog',$lrec['_id']);
				}
			}
			elseif(isset($lrec['_id'])){
				$opts=array(
					'-table'=>'_cronlog'
				);
				$opts['-where']="_id={$lrec['_id']}";
				$opts['run_length']=$run_length;
				$opts['run_result']=$cron_result;
				$ok=editDBRecord($opts);
			}
			elseif(!isset($_REQUEST['cronlog_delete'])){
				$opts['run_length']=$run_length;
				$opts['run_result']=$cron_result;
				$ok=addDBRecord($opts);
			}
			//clean up result before looping
			unset($cron_result);
			break;
		}
	}	
	$etime=microtime(true)-$starttime;
	$etime=(integer)$etime;
	$tail=0;
}
exit;
/* cron functions */
/** --- function cronCleanRecords
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronBuildWhere(){
	return <<<ENDOFWHERE
run_now = 1 or 
(
	active=1 
	and paused != 1 
	and running != 1 
	and run_cmd is not null
	and length(run_cmd) > 0
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
		run_format->'\$.minute[0]'=-1
		or MINUTE(now()) in (
			run_format->'\$.minute[1]',
			run_format->'\$.minute[2]',
			run_format->'\$.minute[3]',
			run_format->'\$.minute[4]',
			run_format->'\$.minute[5]',
			run_format->'\$.minute[6]',
			run_format->'\$.minute[7]',
			run_format->'\$.minute[8]',
			run_format->'\$.minute[9]',
			run_format->'\$.minute[10]',
			run_format->'\$.minute[11]',
			run_format->'\$.minute[12]',
			run_format->'\$.minute[13]',
			run_format->'\$.minute[14]',
			run_format->'\$.minute[15]',
			run_format->'\$.minute[16]',
			run_format->'\$.minute[17]',
			run_format->'\$.minute[18]',
			run_format->'\$.minute[19]',
			run_format->'\$.minute[20]',
			run_format->'\$.minute[21]',
			run_format->'\$.minute[22]',
			run_format->'\$.minute[23]',
			run_format->'\$.minute[24]',
			run_format->'\$.minute[25]',
			run_format->'\$.minute[26]',
			run_format->'\$.minute[27]',
			run_format->'\$.minute[28]',
			run_format->'\$.minute[29]',
			run_format->'\$.minute[30]',
			run_format->'\$.minute[31]',
			run_format->'\$.minute[32]',
			run_format->'\$.minute[33]',
			run_format->'\$.minute[34]',
			run_format->'\$.minute[35]',
			run_format->'\$.minute[36]',
			run_format->'\$.minute[37]',
			run_format->'\$.minute[38]',
			run_format->'\$.minute[39]',
			run_format->'\$.minute[40]',
			run_format->'\$.minute[41]',
			run_format->'\$.minute[42]',
			run_format->'\$.minute[43]',
			run_format->'\$.minute[44]',
			run_format->'\$.minute[45]',
			run_format->'\$.minute[46]',
			run_format->'\$.minute[47]',
			run_format->'\$.minute[48]',
			run_format->'\$.minute[49]',
			run_format->'\$.minute[50]',
			run_format->'\$.minute[51]',
			run_format->'\$.minute[52]',
			run_format->'\$.minute[53]',
			run_format->'\$.minute[54]',
			run_format->'\$.minute[55]',
			run_format->'\$.minute[56]',
			run_format->'\$.minute[57]',
			run_format->'\$.minute[58]',
			run_format->'\$.minute[59]'
			)
		)
	and
		(
		run_format->'\$.hour[0]'=-1
		or HOUR(NOW()) in (
			run_format->'\$.hour[0]',
			run_format->'\$.hour[1]',
			run_format->'\$.hour[2]',
			run_format->'\$.hour[3]',
			run_format->'\$.hour[4]',
			run_format->'\$.hour[5]',
			run_format->'\$.hour[6]',
			run_format->'\$.hour[7]',
			run_format->'\$.hour[8]',
			run_format->'\$.hour[9]',
			run_format->'\$.hour[10]',
			run_format->'\$.hour[11]',
			run_format->'\$.hour[12]',
			run_format->'\$.hour[13]',
			run_format->'\$.hour[14]',
			run_format->'\$.hour[15]',
			run_format->'\$.hour[16]',
			run_format->'\$.hour[17]',
			run_format->'\$.hour[18]',
			run_format->'\$.hour[19]',
			run_format->'\$.hour[20]',
			run_format->'\$.hour[21]',
			run_format->'\$.hour[22]',
			run_format->'\$.hour[23]'
			)
		)
	and
		(
		run_format->'\$.day[0]'=-1
		or DAY(curdate()) in (
			run_format->'\$.day[0]',
			run_format->'\$.day[1]',
			run_format->'\$.day[2]',
			run_format->'\$.day[3]',
			run_format->'\$.day[4]',
			run_format->'\$.day[5]',
			run_format->'\$.day[6]',
			run_format->'\$.day[7]',
			run_format->'\$.day[8]',
			run_format->'\$.day[9]',
			run_format->'\$.day[10]',
			run_format->'\$.day[11]',
			run_format->'\$.day[12]',
			run_format->'\$.day[13]',
			run_format->'\$.day[14]',
			run_format->'\$.day[15]',
			run_format->'\$.day[16]',
			run_format->'\$.day[17]',
			run_format->'\$.day[18]',
			run_format->'\$.day[19]',
			run_format->'\$.day[20]',
			run_format->'\$.day[21]',
			run_format->'\$.day[22]',
			run_format->'\$.day[23]',
			run_format->'\$.day[24]',
			run_format->'\$.day[25]',
			run_format->'\$.day[26]',
			run_format->'\$.day[27]',
			run_format->'\$.day[28]',
			run_format->'\$.day[29]',
			run_format->'\$.day[30]'
			)
		)
	and
		(
		run_format->'\$.month[0]'=-1
		or MONTH(curdate()) in (
			run_format->'\$.month[0]',
			run_format->'\$.month[1]',
			run_format->'\$.month[2]',
			run_format->'\$.month[3]',
			run_format->'\$.month[4]',
			run_format->'\$.month[5]',
			run_format->'\$.month[6]',
			run_format->'\$.month[7]',
			run_format->'\$.month[8]',
			run_format->'\$.month[9]',
			run_format->'\$.month[10]',
			run_format->'\$.month[11]'
			)
		)
	and
		(
		ifnull(run_format->'\$.dayname[0]','')=''
		or run_format->'\$.dayname[0]'=-1
		or WEEKDAY(curdate()) in (
			run_format->'\$.dayname[0]',
			run_format->'\$.dayname[1]',
			run_format->'\$.dayname[2]',
			run_format->'\$.dayname[3]',
			run_format->'\$.dayname[4]',
			run_format->'\$.dayname[5]',
			run_format->'\$.dayname[6]'
			)
		)
)
ENDOFWHERE;
}
function cronCleanRecords($cron=array()){
	if(!isset($cron['_id'])){return false;}
	if(!isNum($cron['records_to_keep'])){return false;}
	//get the 
	$recs=getDBRecords(array(
		'-table'=>'_cronlog',
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

/** --- function cronMessage
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronMessage($msg){
	global $CONFIG;
	global $mypid;
	global $logfile;
	if(!strlen($mypid)){$mypid=getmypid();}
	$ctime=time();
	$cdate=date('Y-m-d h:i:s',$ctime);
	$msg="{$cdate},{$ctime},{$mypid},{$CONFIG['name']},{$msg}".PHP_EOL;
	echo $msg;
	if(!file_exists($logfile) || filesize($logfile) > 1000000 ){
        setFileContents($logfile,$msg);
    }
    else{
        appendFileContents($logfile,$msg);
    }
	return;
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
