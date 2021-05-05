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
global $databaseCache;
$etime=microtime(true)-$starttime;
$etime=(integer)$etime;
$pid_check=1;
$apache_log=1;
$tail=1;
$wherestr_all=cronBuildWhere();
while($etime < 55){
	if(!count($ConfigXml)){break;}
	//check for wasql.update file
	if(file_exists("{$wpath}/php/temp/wasql.update")){
		cronMessage("updating WaSQL..");
		unlink("{$wpath}/php/temp/wasql.update");
		$out=cmdResults('git pull');
		$message="Cmd: {$out['cmd']}<br><pre style=\"margin-bottom:0px;margin-left:10px;padding:10px;background:#f0f0f0;display:inline-block;border:1px solid #ccc;border-radius:3px;\">{$out['stdout']}".PHP_EOL.$out['stderr']."</pre>";
		cronMessage($message);
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
		if($CONFIG['cron']==1 && $tail==1){$ok=cronLogTails();}
		$runnow=0;
		$runnow_afile="{$progpath}/temp/{$CONFIG['name']}_runnow.txt";
		//echo $runnow_afile.PHP_EOL;
		if(!file_exists($runnow_afile) && isset($CONFIG['cron']) && $CONFIG['cron']==0){
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
		cronMessage("checking");
		//check for apache_access_log
		if($apache_log==1 && isset($CONFIG['apache_access_log']) && file_exists($CONFIG['apache_access_log'])){
			$apache_log=0;
			loadExtras('apache');
			cronMessage("running apacheParseLogFile...".$CONFIG['apache_access_log']);
			$msg=apacheParseLogFile();
			if(strlen($msg)){cronMessage($msg);}
			cronMessage("apacheParseLogFile completed");
		}
		$ok=cronCheckSchema();
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
		if(file_exists($runnow_afile)){
			$runid=getfileContents($runnow_afile);
			unlink($runnow_afile);
			$runid=(integer)$runid;
			$wherestr="_id={$runid} and running != 1";
			$ok=cronMessage("Run Now File found. Set wherestr: {$wherestr}");
			$runnow=1;
		}
		$recopts=array(
			'-table'	=> '_cron',
			'-fields'	=> '_id,name,run_cmd,running,run_date,frequency,run_format,run_values',
			'-where'	=> $wherestr,
			'-nocache'	=> 1,
			'-order'	=> 'run_date'
		);
		$cronfields=getDBFields('_cron');
		if(!is_array($cronfields)){
			unset($ConfigXml[$name]);
			cronMessage("cronfields in _cron is empty.".PHP_EOL);
			continue;
		}
		if(in_array('run_as',$cronfields)){$recopts['-fields'].=',run_as';}
		if(!in_array('frequency_max',$cronfields)){
			$query="ALTER TABLE _cron ADD frequency_max varchar(25) NULL";
			$ok=executeSQL($query);
			$id=addDBRecord(array('-table'=>"_fielddata",
				'tablename'		=> '_cron',
				'fieldname'		=> 'frequency_max',
				'inputtype'		=> 'select',
				'displayname'	=> "Frequency Max",
				'-upsert'		=> 'tvals,dvals,inputtype,displayname',
				'tvals'			=> "hourly\r\ndaily\r\nweekly\r\nmonthly\r\nquarterly",
				'dvals'			=> "Once Per Hour\r\nOnce Per Day\r\nOnce Per Week\r\nOnce Per Month\r\nOnce Per Quarter"
			));
		}
		$recs=getDBRecords($recopts);
		if(!is_array($recs) && strlen($recs)){
			cronMessage("ERROR: {$recs}".PHP_EOL);
			exit;
		}
		$rcnt=is_array($recs)?count($recs):0;
		//echo $runnow.printValue($recs).PHP_EOL;
		if($rcnt==0){
			unset($ConfigXml[$name]);
			cronMessage("{$rcnt} crons ready".PHP_EOL);
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
			cronMessage("{$pcnt} processed. No other crons ready".PHP_EOL);
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
				//is run_format a json string?  If so, parse and check
				if(strlen($rec['run_format']) && preg_match('/\{/', trim($rec['run_format']))){
					$json=json_decode($rec['run_format'],true);
					if(is_array($json)){
						//check month
						if(isset($json['month'][0])){
							$cmon=date('n');
							if($json['month'][0]==-1 || in_array($cmon,$json['month'])){
								//echo $rec['name']." month passed";exit;
								//month passed. check day
								if(isset($json['day'][0])){
									$cday=date('j');
									if($json['day'][0]==-1 || in_array($cday,$json['day'])){
										//day passed. check hour
										//echo $rec['name']." day passed";exit;
										if(isset($json['hour'][0])){
											$chour=date('G');
											//echo $rec['name'].$chour.printValue($json['hour']);exit;
											if($json['hour'][0]==-1 || in_array($chour,$json['hour'])){
												//hour passed. check minute
												//echo $rec['name']." hour passed";exit;
												if(isset($json['minute'][0])){
													$cmin=(integer)date('i');
													if($json['minute'][0]==-1 || in_array($cmin,$json['minute'])){
														//minute passed
														$run=1;
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
				elseif($rec['frequency'] > 0){
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
				if($runnow==0 && strlen($rec['run_date'])){
					$ctime=time();
	            	$lastruntime=strtotime($rec['run_date']);
	            	$diff=$ctime-$lastruntime;
	            	if($diff < 60){
	            		cronMessage("skipping - ran in the last minute:{$rec['name']}");
	            		$run=0;
	            	}
				}
				//reset running if it has been over post timeout
				if($rec['running']==1){
					if(strlen($rec['run_date'])){
						$ctime=time();
		            	$lastruntime=strtotime($rec['run_date']);
		            	$diff=$ctime-$lastruntime;
		            	if($diff > $posturl_timeout){
	                    	$ok=editDBRecordById('_cron',$rec['_id'],array('running'=>0));
						}
					}
				}
				if($runnow==1){$run=1;}
				if($run==0){
					cronMessage("cron name:{$rec['name']} run_format value:{$value}, current value:{$cvalue}, run: {$run}");
					continue;
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
				$start=$CRONTHRU['start']=time();
				$run_date=date('Y-m-d H:i:s');
				$cron_pid=getmypid();
				//echo $ok.printValue($rec);
				//cronMessage("handshaking {$rec['name']}");
				$editopts=array(
					'-table'	=> '_cron',
					'-where'	=> "running=0 and _id={$rec['_id']}",
					'cron_pid'	=> $cron_pid,
					'running'	=> 1,
					'run_date'	=> $run_date,
					'run_error'	=> ''
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
				$cron_id=$CRONTHRU['cron_id']=$rec['_id'];
				$CRONTHRU['cron_pid']=$cron_pid;
				$CRONTHRU['cron_run_date']=$run_date;
				$CRONTHRU['cron_name']=$rec['name'];
				$CRONTHRU['cron_run_cmd']=$rec['run_cmd'];
				$path=getWaSQLPath('php/temp');
				$commonCronLogFile="{$path}/{$CONFIG['name']}_cronlog_{$rec['_id']}.txt";
				if(file_exists($commonCronLogFile)){
					unlink($commonCronLogFile);
				}
				cronMessage("cleaning {$rec['name']}");
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
				cronMessage("running {$crontype} {$rec['run_cmd']}");
	        	
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
					cronMessage("calling {$url}");
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
	            	cronMessage("running eval code");
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
	            	cronMessage("calling $cmd");
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
	            	cronMessage("running $cmd");
	            	$out=cmdResults($cmd);
	            	$cron_result .= '----- Content Received -----'.PHP_EOL;
	            	$cron_result .= printValue($out).PHP_EOL;
				}

				$stop=time();
				$run_length=$stop-$start;
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
				$runtime=$run_length > 0?verboseTime($run_length):0;

				cronMessage("finished {$rec['name']}. Run Length:{$runtime}");
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
					'run_date'	=> $run_date
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
}
exit;
/* cron functions */
/** --- function cronCleanRecords
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronBuildWhere(){
	return <<<ENDOFWHERE
active=1 
and paused != 1 
and running != 1 
and run_cmd is not null
and length(run_cmd) > 0
and (
	ifnull(begin_date,'')=''
	or length(begin_date)=0
	or date(now()) >= date(begin_date)
	)
and (
	ifnull(end_date,'')=''
	or length(end_date)=0
	or date(end_date) <= date(now())
	)
and (
	ifnull(run_date,'')=''
	or length(run_date)=0
	or run_date < date_sub(now(), interval 1 minute) 
	)
and 
	(
	ifnull(frequency_max,'')='' 
	or (frequency_max='hourly' and date(run_date) != hour(now()))
	or (frequency_max='daily' and date(run_date) != date(now()))
	or (frequency_max='weekly' and week(run_date) != week(now()))
	or (frequency_max='monthly' and month(run_date) != month(now()))
	or (frequency_max='quarterly' and quarter(run_date) != quarter(now()))
	)
and
	(
	run_format->'\$.minute[0]'=-1
	or run_format->'\$.minute[0]'=MINUTE(NOW())
	or run_format->'\$.minute[1]'=MINUTE(NOW())
	or run_format->'\$.minute[2]'=MINUTE(NOW())
	or run_format->'\$.minute[3]'=MINUTE(NOW())
	or run_format->'\$.minute[4]'=MINUTE(NOW())
	or run_format->'\$.minute[5]'=MINUTE(NOW())
	)
and
	(
	run_format->'\$.hour[0]'=-1
	or run_format->'\$.hour[0]'=HOUR(NOW())
	or run_format->'\$.hour[1]'=HOUR(NOW())
	or run_format->'\$.hour[2]'=HOUR(NOW())
	or run_format->'\$.hour[3]'=HOUR(NOW())
	or run_format->'\$.hour[4]'=HOUR(NOW())
	or run_format->'\$.hour[5]'=HOUR(NOW())
	)
and
	(
	run_format->'\$.day[0]'=-1
	or run_format->'\$.day[0]'=day(curdate())
	or run_format->'\$.day[1]'=day(curdate())
	or run_format->'\$.day[2]'=day(curdate())
	or run_format->'\$.day[3]'=day(curdate())
	or run_format->'\$.day[4]'=day(curdate())
	or run_format->'\$.day[5]'=day(curdate())
	or run_format->'\$.day[6]'=day(curdate())
	)
and
	(
	run_format->'\$.month[0]'=-1
	or run_format->'\$.month[0]'=MONTH(curdate())
	or run_format->'\$.month[1]'=MONTH(curdate())
	or run_format->'\$.month[2]'=MONTH(curdate())
	or run_format->'\$.month[3]'=MONTH(curdate())
	or run_format->'\$.month[4]'=MONTH(curdate())
	or run_format->'\$.month[5]'=MONTH(curdate())
	or run_format->'\$.month[6]'=MONTH(curdate())
	)
and
	(
	ifnull(run_format->'\$.dayname[0]','')=''
	or run_format->'\$.dayname[0]'=-1
	or run_format->'\$.dayname[0]'=WEEKDAY(curdate())
	or run_format->'\$.dayname[1]'=WEEKDAY(curdate())
	or run_format->'\$.dayname[2]'=WEEKDAY(curdate())
	or run_format->'\$.dayname[3]'=WEEKDAY(curdate())
	or run_format->'\$.dayname[4]'=WEEKDAY(curdate())
	or run_format->'\$.dayname[5]'=WEEKDAY(curdate())
	or run_format->'\$.dayname[6]'=WEEKDAY(curdate())
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

/**  --- function cronCheckSchema
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function cronCheckSchema(){
	$cronfields=getDBFieldInfo('_cron');
	//add paused and groupname fields?
	//paused
	if(!isset($cronfields['paused'])){
		$query="ALTER TABLE _cron ADD paused ".databaseDataType('integer(1)')." NOT NULL Default 0;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> '_cron',
			'fieldname'		=> 'paused',
			'inputtype'		=> 'checkbox',
			'synchronize'	=> 0,
			'tvals'			=> '1',
			'editlist'		=> 1,
			'required'		=> 0
		));
		$ok=addDBIndex(array('-table'=>'_cron','-fields'=>"paused"));
	}
	//groupname
	if(!isset($cronfields['groupname'])){
		$query="ALTER TABLE _cron ADD groupname ".databaseDataType('varchar(150)')." NULL;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'groupname',
			'inputtype'		=> 'text',
			'width'			=> 150,
			'required'		=> 0
		));
		$ok=addDBIndex(array('-table'=>'_cron','-fields'=>"groupname"));
	}
	//records_to_keep
	if(!isset($cronfields['records_to_keep'])){
		$query="ALTER TABLE _cron ADD records_to_keep ".databaseDataType('integer')." NOT NULL Default 1000;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'records_to_keep',
			'inputtype'		=> 'text',
			'width'			=> 100,
			'mask'			=> 'integer',
			'required'		=> 1
		));
	}
	//run_memory
	if(!isset($cronfields['run_memory'])){
		$query="ALTER TABLE _cron ADD run_memory ".databaseDataType('integer')." NULL";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'run_memory',
			'inputtype'		=> 'text',
			'width'			=> 100,
			'mask'			=> 'integer',
			'required'		=> 1
		));
	}
	return true;
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
