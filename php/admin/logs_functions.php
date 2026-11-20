<?php
function logsGetLogs($includes=array(),$excludes=array()){
	global $CONFIG;
	$logs=array();
	$rowcount=isset($CONFIG['logs_rowcount'])?(int)$CONFIG['logs_rowcount']:500;
	$tempdir=getWasqlPath('php/temp');
	foreach($CONFIG as $k=>$v){
		$lk=strtolower($k);
		if(strtolower($k)=='logs_rowcount'){continue;}
		if(strtolower($k)=='logs_refresh'){continue;}

		if(preg_match('/^logs\_(.+)$/is',$k,$m)){
			if(!is_file($v)){
				echo "Logs File error for {$k}  - no such file or no access: {$v}<br>";
				continue;
			}
			$fname=getFileName($v);
			$aname="{$tempdir}/{$fname}";
			if(!is_file($aname)){
				echo "Logs File error for {$k} - cron must not be running: {$aname}<br>";
				continue;
			}
			$results=getFileContents($aname);
			$lines=preg_split('/[\r\n]+/',$results);
			$lines=array_reverse($lines);
			foreach($lines as $i=>$line){
				if(count($includes)){
					$found=0;
					foreach($includes as $include){
						if(stringContains($line,$include)){
							$found+=1;
						}
					}
					if($found==0){
						unset($lines[$i]);
						continue;
					}
				}
				if(count($excludes)){
					$found=0;
					foreach($excludes as $exclude){
						if(stringContains($line,$exclude)){
							$found+=1;
						}
					}
					if($found!=0){
						unset($lines[$i]);
						continue;
					}
				}
				$flag='';
				if(stringContains($line,'fatal errror:') || stringContains($line,':fatal')){
					$flag='<span class="icon-circle w_danger w_blink"></span> ';
				}
				elseif(stringContains($line,'warning:') || stringContains($line,':warn')){
					$flag='<span class="icon-circle w_warning"></span> ';
				}
				elseif(stringContains($line,'error:') || stringContains($line,':error')){
					$flag='<span class="icon-circle w_danger"></span> ';
				}
				elseif(stringContains($line,'notice:') || stringContains($line,':notice')){
					$flag='<span class="icon-circle w_info"></span> ';
				}
				$lines[$i]="<div style=\"padding:2px;margin-bottom:3px;\">{$flag}{$line}</div>";
			}
			//echo $v.printValue($out);exit;
			$logs[$lk]=array(
				'name'=>$m[1],
				'file'=>$aname,
				'mtime'=>filemtime($aname),
				'key'=>$lk,
				'tail'=>implode(PHP_EOL,$lines)
			);
			$logs[$lk]['age']=time()-$logs[$lk]['mtime'];
			$logs[$lk]['age_verbose']=verboseTime($logs[$lk]['age']);
		}
	}
	return $logs;
}
//==================== User Log viewer (func=userlog) ====================
//---------- begin function logsUserLogLoad ----
/**
* @describe parses logs/user.log into classified entries (oldest first). Consecutive duplicate
*	lines (same message within 2 seconds) are collapsed into one entry with a count, multi-line
*	detail blocks (printValue dumps, SQL) are attached to the entry above them and redacted.
*	Cached for the rest of the request.
* @return array - ['exists'=>bool,'file'=>string,'size'=>int,'mtime'=>int,'raw_lines'=>int,'entries'=>array]
*	each entry: ts,when,type(passed|failed|error|logout|trace),method,user,msg,reason,detail,count
* @usage $userlog=logsUserLogLoad();
*/
function logsUserLogLoad(){
	static $cache=null;
	if(is_array($cache)){return $cache;}
	$file=getWaSQLPath('logs').'/user.log';
	$out=array('exists'=>is_file($file),'file'=>$file,'size'=>0,'mtime'=>0,'raw_lines'=>0,'entries'=>array());
	if(!$out['exists'] || !($fh=fopen($file,'r'))){
		$out['exists']=false;
		$cache=$out;
		return $out;
	}
	$out['size']=filesize($file);
	$out['mtime']=filemtime($file);
	$entries=array();
	$state=array('clock'=>array(),'lastreq'=>null,'reason'=>null);
	$cur=null;
	while(($line=fgets($fh))!==false){
		$out['raw_lines']++;
		$line=rtrim($line,"\r\n");
		if(preg_match('/^time:(\d{4}-\d{2}-\d{2}) (\d{1,2}):(\d{2}):(\d{2}),\s?(.*)$/',$line,$m)){
			if(is_array($cur)){logsUserLogFinish($entries,$state,$cur);}
			$ts=logsUserLogTime($m[1],(int)$m[2],(int)$m[3],(int)$m[4],$state['clock']);
			$msg=trim($m[5]);
			$ip='';
			//current cores prefix the message with the client IP
			if(preg_match('/^ip:([0-9a-f\.\:]+),\s*(.*)$/is',$msg,$p)){
				$ip=$p[1];
				$msg=trim($p[2]);
			}
			$cur=array('ts'=>$ts,'ip'=>$ip,'msg'=>$msg,'detail'=>'');
		}
		elseif(is_array($cur) && strlen(trim($line))){
			if(strlen($cur['detail']) < 6000){$cur['detail'].=$line.PHP_EOL;}
		}
	}
	if(is_array($cur)){logsUserLogFinish($entries,$state,$cur);}
	fclose($fh);
	$out['entries']=$entries;
	$cache=$out;
	return $out;
}
//---------- begin function logsUserLogTime ----
/**
* @describe converts a user.log timestamp to a unix time. Older cores wrote the hour with date('h')
*	(12-hour, no AM/PM), so within each date the hour is assumed AM until the clock jumps
*	backwards (11:59 -> 12:09), then PM. Hours above 12 mean the date is already 24-hour.
* @param date string - Y-m-d
* @param h int - hour as written
* @param i int - minutes
* @param s int - seconds
* @param clock array - per-date state, passed by reference
* @return int unix timestamp
* @usage $ts=logsUserLogTime('2026-11-20',1,9,19,$clock);
*/
function logsUserLogTime($date,$h,$i,$s,&$clock){
	if(!isset($clock[$date])){$clock[$date]=array('pm'=>false,'h24'=>false,'last'=>0);}
	$c=&$clock[$date];
	if($h > 12){$c['h24']=true;}
	if($c['h24']){
		$hh=$h;
	}
	else{
		$hh=($h % 12)+($c['pm']?12:0);
		$secs=$hh*3600+$i*60+$s;
		if(!$c['pm'] && $secs < $c['last']-600){
			$c['pm']=true;
			$hh+=12;
		}
	}
	$secs=$hh*3600+$i*60+$s;
	if($secs > $c['last']){$c['last']=$secs;}
	return strtotime($date)+$secs;
}
//---------- begin function logsUserLogFinish ----
/**
* @describe classifies a parsed entry, fills in a missing user from its detail block or the LDAP
*	request just before it, links an LDAP failure reason to the "Auth failed" line that follows it,
*	collapses duplicates, and appends it to $entries
* @param entries array - by reference
* @param state array - by reference (lastreq/reason carry-over)
* @param cur array - ts,msg,detail
* @return void
*/
function logsUserLogFinish(&$entries,&$state,$cur){
	$cur['detail']=logsUserLogRedact($cur['detail']);
	$c=logsUserLogClassify($cur['msg']);
	$cur=array_merge($cur,$c);
	if(!strlen($cur['user']) && preg_match('/"-username":\s*"([^"]*)"/',$cur['detail'],$m)){
		$cur['user']=$m[1];
	}
	if(!strlen($cur['user']) && is_array($state['lastreq']) && $cur['ts']-$state['lastreq']['ts'] <= 5){
		$cur['user']=$state['lastreq']['user'];
	}
	if(stringBeginsWith($cur['msg'],'LDAP Auth Request')){
		$state['lastreq']=array('user'=>$cur['user'],'ts'=>$cur['ts']);
	}
	if(strlen($cur['reason']) && $cur['type']!='failed'){
		$state['reason']=array('user'=>strtolower($cur['user']),'reason'=>$cur['reason'],'ts'=>$cur['ts']);
	}
	elseif($cur['type']=='failed' && !strlen($cur['reason']) && is_array($state['reason'])
		&& $state['reason']['user']==strtolower($cur['user']) && $cur['ts']-$state['reason']['ts'] <= 5){
		$cur['reason']=$state['reason']['reason'];
		$state['reason']=null;
	}
	if($cur['type']=='failed' && !strlen($cur['reason'])){
		$defaults=array(
			'Auth code'=>'Invalid or expired _auth token',
			'Apikey'=>'Invalid apikey, or user inactive',
			'Session'=>'Invalid or expired session code',
			'Temp'=>'Invalid or expired temp code'
		);
		if(isset($defaults[$cur['method']])){$cur['reason']=$defaults[$cur['method']];}
	}
	//token/API auths (Auth code, Apikey, Session, Temp) happen on every request - only the rest are people signing in
	$cur['interactive']=in_array($cur['method'],array('LDAP','Okta','Username','Email','Phone'));
	$n=count($entries);
	if($n && !strlen($cur['detail']) && !strlen($entries[$n-1]['detail'])
		&& $entries[$n-1]['msg']===$cur['msg'] && $entries[$n-1]['ip']===$cur['ip'] && $cur['ts']-$entries[$n-1]['ts'] <= 2){
		$entries[$n-1]['count']++;
		return;
	}
	$cur['when']=date('Y-m-d H:i:s',$cur['ts']);
	$cur['count']=1;
	$entries[]=$cur;
}
//---------- begin function logsUserLogClassify ----
/**
* @describe classifies one user.log message
* @param msg string
* @return array - type (passed|failed|error|logout|trace), method, user, reason
* @usage $c=logsUserLogClassify('LDAP Auth failed for user bob');
*/
function logsUserLogClassify($msg){
	$r=array('type'=>'trace','method'=>'','user'=>'','reason'=>'');
	if(preg_match('/^userLogout\s*-\s*(.*)$/i',$msg,$m)){
		$r['type']='logout';
		$r['user']=trim($m[1]);
	}
	elseif(preg_match('/^(?:(\w+)\s+)?Auth passed for user\s*(.*)$/i',$msg,$m)){
		$r['type']='passed';
		$r['method']=logsUserLogMethodName($m[1]);
		$r['user']=trim($m[2]);
	}
	elseif(preg_match('/^(?:(\w+)\s+)?Auth failed(?::\s*(.*)|\s+for user\s*(.*))?$/i',$msg,$m)){
		$r['type']='failed';
		$r['method']=logsUserLogMethodName($m[1]);
		$r['user']=isset($m[3])?trim($m[3]):'';
		$r['reason']=isset($m[2])?trim($m[2]):'';
	}
	elseif(preg_match('/^LDAP Auth Request from\s*(.*)$/i',$msg,$m)){
		$r['method']='LDAP';
		$r['user']=trim($m[1]);
	}
	elseif(preg_match('/^--\s*(\S+)\s+is an admin/i',$msg,$m)){
		$r['user']=$m[1];
	}
	elseif(preg_match('/^user(?:Decode)?(LDAP|Okta|Apikey|Email|Phone|Username|Temp|Session)?\w*\s+(.*)$/i',$msg,$m)){
		$r['method']=logsUserLogMethodName($m[1]);
		$rest=$m[2];
		if(preg_match('/^passed for (\S+?)\.?(\s|$)/i',$rest,$p)){
			$r['user']=$p[1];
		}
		elseif(preg_match('/\bFailed\b\s*[\.\-]?\s*(.*)$/s',$rest,$f)){
			$why=trim($f[1]);
			//current format: "... Failed for {user}: {reason}" (older cores: "... Failed.{reason}")
			if(preg_match('/^for\s+(.+?)(?::\s+(.*))?$/is',$why,$u)){
				$r['user']=trim($u[1]);
				$why=isset($u[2])?trim($u[2]):'';
			}
			if(strlen($why)){
				$r['reason']=logsUserLogReason($why);
				//a wrong password is reported again by the "Auth failed" line - only real faults are errors
				if(!preg_match('/Err:49|Invalid credentials/i',$why)){$r['type']='error';}
			}
		}
	}
	elseif(preg_match('/\b(fail|failed|error)\b/i',$msg)){
		$r['type']='error';
		$r['reason']=logsUserLogReason($msg);
	}
	return $r;
}
//---------- begin function logsUserLogMethodName ----
/**
* @describe normalizes an auth method prefix (Apikey, LDAP, OKTA...) - a bare "Auth" line is an _auth code login
* @param prefix string
* @return string
*/
function logsUserLogMethodName($prefix){
	$map=array('apikey'=>'Apikey','okta'=>'Okta','temp'=>'Temp','session'=>'Session','ldap'=>'LDAP','username'=>'Username','email'=>'Email','phone'=>'Phone');
	$k=strtolower(trim((string)$prefix));
	if(!strlen($k)){return 'Auth code';}
	return isset($map[$k])?$map[$k]:ucfirst($k);
}
//---------- begin function logsUserLogReason ----
/**
* @describe shortens a failure message to a groupable reason: drops trailing SQL and dots, maps
*	LDAP error 49 to "Invalid credentials (wrong username or password)"
* @param text string
* @return string
*/
function logsUserLogReason($text){
	$text=trim(preg_replace('/:\s*(INSERT|UPDATE|SELECT|DELETE|REPLACE)\b.*$/is','',$text));
	$text=trim(preg_replace('/\s*\.+\s*$/','',$text));
	if(preg_match('/Err:49|Invalid credentials/i',$text)){return 'Invalid credentials (wrong username or password)';}
	if(preg_match('/Err:-1\b|Can.t contact LDAP/i',$text)){return "Can't contact LDAP server";}
	if(preg_match('/Timed out|Err:-5\b/i',$text)){return 'LDAP server timed out';}
	return commonStrlen($text) > 160?substr($text,0,160).'...':$text;
}
//---------- begin function logsUserLogRedact ----
/**
* @describe strips HTML from a detail block and masks passwords - including the password value inside
*	a logged INSERT ... VALUES (...) statement, which older cores write on an LDAP addDBRecord failure
* @param text string
* @return string
*/
function logsUserLogRedact($text){
	if(!strlen($text)){return '';}
	$text=trim(html_entity_decode(strip_tags($text),ENT_QUOTES));
	$text=preg_replace('/("-?password"\s*:\s*")[^"]*"/i','$1********"',$text);
	$text=preg_replace("/(`?password`?\s*=\s*)'[^']*'/i","$1'********'",$text);
	$text=preg_replace_callback('/\(([^()]*)\)\s*VALUES\s*\((.*?)\)(?=\s*(AS\s+new|ON\s+DUPLICATE|;|$))/is',function($m){
		$cols=array_map(function($c){return strtolower(trim($c," `\t\r\n"));},explode(',',$m[1]));
		$idx=array_search('password',$cols);
		if($idx===false){return $m[0];}
		$vals=str_getcsv($m[2],',',"'","\\");
		if(count($vals)==count($cols) && strlen(trim($vals[$idx]))){
			$masked=str_replace("'".$vals[$idx]."'","'********'",$m[0]);
			if($masked!==$m[0]){return $masked;}
		}
		return '('.$m[1].') VALUES (...redacted...)';
	},$text);
	return $text;
}
//---------- begin function logsUserLogKnownUsers ----
/**
* @describe lowercase username => active flag for every _users record, so the viewer can flag
*	failed logins for usernames that do not exist
* @return array
*/
function logsUserLogKnownUsers(){
	static $known=null;
	if(is_array($known)){return $known;}
	$known=array();
	$recs=getDBRecords(array('-query'=>"select username,active from _users"));
	if(is_array($recs)){
		foreach($recs as $rec){
			$known[strtolower((string)$rec['username'])]=(int)$rec['active'];
		}
	}
	return $known;
}
//---------- begin function logsUserLogStats ----
/**
* @describe overview numbers: totals, last-24h counts, per-method and per-day breakdowns, and the
*	usernames failing most over the last 7 days
* @param userlog array - from logsUserLogLoad()
* @return array
* @usage $stats=logsUserLogStats($userlog);
*/
function logsUserLogStats($userlog){
	$now=time();
	$s=array(
		'passed'=>0,'token'=>0,'failed'=>0,'errors'=>0,'logouts'=>0,
		'passed_24'=>0,'token_24'=>0,'failed_24'=>0,'errors_24'=>0,
		'users'=>array(),'users_24'=>array(),
		'first'=>'','last'=>'','methods'=>array(),'days'=>array(),'topfail'=>array()
	);
	for($d=13;$d>=0;$d--){
		$key=date('Y-m-d',$now-$d*86400);
		$s['days'][$key]=array('day'=>date('D M j',strtotime($key)),'passed'=>0,'token'=>0,'failed'=>0,'errors'=>0,'users'=>array());
	}
	$fail7=array();
	foreach($userlog['entries'] as $e){
		if($s['first']===''){$s['first']=$e['when'];}
		$s['last']=$e['when'];
		$recent=($now-$e['ts']) <= 86400;
		$day=substr($e['when'],0,10);
		$lu=strtolower($e['user']);
		switch($e['type']){
			case 'passed':
				if(strlen($lu)){$s['users'][$lu]=1;}
				if($recent && strlen($lu)){$s['users_24'][$lu]=1;}
				if(isset($s['days'][$day]) && strlen($lu)){$s['days'][$day]['users'][$lu]=1;}
				if($e['interactive']){
					$s['passed']++;
					if($recent){$s['passed_24']++;}
					if(isset($s['days'][$day])){$s['days'][$day]['passed']++;}
				}
				else{
					$s['token']+=$e['count'];
					if($recent){$s['token_24']+=$e['count'];}
					if(isset($s['days'][$day])){$s['days'][$day]['token']+=$e['count'];}
				}
				if(!isset($s['methods'][$e['method']])){$s['methods'][$e['method']]=array('method'=>$e['method'],'passed'=>0,'failed'=>0);}
				$s['methods'][$e['method']]['passed']+=$e['count'];
			break;
			case 'failed':
				$s['failed']++;
				if($recent){$s['failed_24']++;}
				if(isset($s['days'][$day])){$s['days'][$day]['failed']++;}
				if(!isset($s['methods'][$e['method']])){$s['methods'][$e['method']]=array('method'=>$e['method'],'passed'=>0,'failed'=>0);}
				$s['methods'][$e['method']]['failed']+=$e['count'];
				if(($now-$e['ts']) <= 7*86400){
					$k=strlen($lu)?$lu:'(blank)';
					if(!isset($fail7[$k])){$fail7[$k]=array('user'=>strlen($e['user'])?$e['user']:'(blank)','attempts'=>0,'last'=>'','reason'=>'');}
					$fail7[$k]['attempts']++;
					$fail7[$k]['last']=$e['when'];
					if(strlen($e['reason'])){$fail7[$k]['reason']=$e['reason'];}
				}
			break;
			case 'error':
				$s['errors']++;
				if($recent){$s['errors_24']++;}
				if(isset($s['days'][$day])){$s['days'][$day]['errors']++;}
			break;
			case 'logout':
				$s['logouts']++;
			break;
		}
	}
	$s['users']=count($s['users']);
	$s['users_24']=count($s['users_24']);
	foreach($s['days'] as $k=>$d){$s['days'][$k]['users']=count($d['users']);}
	$s['days']=array_reverse(array_values($s['days']));
	$s['methods']=array_values($s['methods']);
	usort($s['methods'],function($a,$b){return ($b['passed']+$b['failed'])-($a['passed']+$a['failed']);});
	usort($fail7,function($a,$b){return $b['attempts']-$a['attempts'];});
	$s['topfail']=array_slice($fail7,0,10);
	$s['failing_now']=0;
	$s['failing_now']=count(logsUserLogUnresolved(logsUserLogFailures($userlog)));
	return $s;
}
//---------- begin function logsUserLogUsers ----
/**
* @describe one row per username seen in the log: successful/failed logins, last of each, methods
*	used, and whether the user's most recent attempt failed
* @param userlog array - from logsUserLogLoad()
* @param q string - optional substring filter on username
* @return array rows sorted by last activity, newest first
*/
function logsUserLogUsers($userlog,$q=''){
	$known=logsUserLogKnownUsers();
	$users=array();
	$q=strtolower(trim($q));
	foreach($userlog['entries'] as $e){
		if(!in_array($e['type'],array('passed','failed','logout')) || !strlen($e['user'])){continue;}
		$lu=strtolower($e['user']);
		if(strlen($q) && strpos($lu,$q)===false){continue;}
		if(!isset($users[$lu])){
			$users[$lu]=array('user'=>$e['user'],'passed'=>0,'failed'=>0,'last_pass'=>'','last_fail'=>'','last'=>'','methods'=>array(),'reason'=>'');
		}
		$u=&$users[$lu];
		$u['last']=$e['when'];
		if($e['type']=='passed'){
			$u['passed']++;
			$u['last_pass']=$e['when'];
			$u['methods'][$e['method']]=1;
		}
		elseif($e['type']=='failed'){
			$u['failed']++;
			$u['last_fail']=$e['when'];
			if(strlen($e['reason'])){$u['reason']=$e['reason'];}
		}
		unset($u);
	}
	foreach($users as $lu=>$u){
		$users[$lu]['methods']=implode(', ',array_keys($u['methods']));
		$users[$lu]['last_pass']=strlen($u['last_pass'])?$u['last_pass']:'-';
		$users[$lu]['last_fail']=strlen($u['last_fail'])?$u['last_fail']:'-';
		$failing=strlen($u['last_fail']) && $u['last_fail'] > $u['last_pass'];
		$users[$lu]['status']=$failing?'Last attempt failed':'OK';
		$users[$lu]['status_class']=$failing?'is-danger':'is-success';
		$users[$lu]['account']=isset($known[$lu])?($known[$lu]?'active':'inactive'):'not in _users';
	}
	usort($users,function($a,$b){return strcmp($b['last'],$a['last']);});
	return $users;
}
//---------- begin function logsUserLogFailures ----
/**
* @describe failed logins grouped by username, with the likely cause: reason from the LDAP bind, whether
*	the username exists, email-style or spaced usernames, a repeating (scripted) failure, and whether
*	the user later logged in successfully
* @param userlog array - from logsUserLogLoad()
* @return array rows sorted by last attempt, newest first
*/
function logsUserLogFailures($userlog){
	static $cache=null;
	if(is_array($cache)){return $cache;}
	$known=logsUserLogKnownUsers();
	$now=time();
	$rows=array();
	$lastpass=array();
	foreach($userlog['entries'] as $e){
		$lu=strtolower($e['user']);
		if($e['type']=='passed'){$lastpass[$lu]=$e['when'];continue;}
		if($e['type']!='failed'){continue;}
		$k=strlen($lu)?$lu:'(blank)';
		if(!isset($rows[$k])){
			$rows[$k]=array('user'=>strlen($e['user'])?$e['user']:'(blank)','attempts'=>0,'attempts_24'=>0,'first'=>$e['when'],'last'=>'','methods'=>array(),'reasons'=>array(),'ips'=>array());
		}
		$rows[$k]['attempts']+=$e['count'];
		if(($now-$e['ts']) <= 86400){$rows[$k]['attempts_24']+=$e['count'];}
		$rows[$k]['last']=$e['when'];
		$rows[$k]['methods'][$e['method']]=1;
		if(strlen($e['ip'])){$rows[$k]['ips'][$e['ip']]=1;}
		$reason=strlen($e['reason'])?$e['reason']:'(no reason logged)';
		$rows[$k]['reasons'][$reason]=isset($rows[$k]['reasons'][$reason])?$rows[$k]['reasons'][$reason]+1:1;
	}
	foreach($rows as $k=>$r){
		$hints=array();
		$recovered=isset($lastpass[$k]) && $lastpass[$k] >= $r['last'];
		if($k=='(blank)'){$hints[]='no username sent';}
		elseif(!isset($known[$k])){$hints[]='not in _users';}
		elseif(!$known[$k]){$hints[]='inactive account';}
		if(strpos($k,'@')!==false){$hints[]='typed an email address';}
		if(strpos(trim($k),' ')!==false){$hints[]='contains a space';}
		if($r['attempts_24'] >= 6 && !$recovered){$hints[]='repeating - likely a script or service with a bad credential';}
		arsort($r['reasons']);
		$reasons=array();
		foreach($r['reasons'] as $reason=>$cnt){$reasons[]=$reason.($cnt>1?" ({$cnt})":'');}
		$rows[$k]['methods']=implode(', ',array_keys($r['methods']));
		$rows[$k]['reasons']=implode('; ',$reasons);
		$ips=array_keys($r['ips']);
		$rows[$k]['ips']=count($ips)?implode(', ',array_slice($ips,0,5)).(count($ips)>5?' +'.(count($ips)-5).' more':''):'-';
		$rows[$k]['hints']=implode('; ',$hints);
		$rows[$k]['recovered']=$recovered;
		$rows[$k]['status']=$recovered?('Logged in '.$lastpass[$k]):'Not resolved';
		$rows[$k]['status_class']=$recovered?'is-success':'is-danger';
	}
	usort($rows,function($a,$b){return strcmp($b['last'],$a['last']);});
	$cache=$rows;
	return $rows;
}
//---------- begin function logsUserLogUnresolved ----
/**
* @describe narrows logsUserLogFailures() rows to the "Still failing" set: no successful login since the
*	last failure, and that failure was within the last 7 days (older ones are history, not actionable)
* @param failures array - from logsUserLogFailures()
* @return array
* @usage $failing=logsUserLogUnresolved(logsUserLogFailures($userlog));
*/
function logsUserLogUnresolved($failures){
	$since=date('Y-m-d H:i:s',time()-7*86400);
	$rows=array();
	foreach($failures as $f){
		if(!$f['recovered'] && $f['last'] >= $since){$rows[]=$f;}
	}
	return $rows;
}
//---------- begin function logsUserLogErrors ----
/**
* @describe system-side failures (LDAP timeouts, user upsert failures, ...) grouped by reason, with the
*	users affected and the most recent detail block
* @param userlog array - from logsUserLogLoad()
* @return array rows sorted by last occurrence, newest first
*/
function logsUserLogErrors($userlog){
	$rows=array();
	foreach($userlog['entries'] as $e){
		if($e['type']!='error'){continue;}
		$k=strlen($e['reason'])?$e['reason']:$e['msg'];
		if(!isset($rows[$k])){
			$rows[$k]=array('reason'=>$k,'count'=>0,'first'=>$e['when'],'last'=>'','users'=>array(),'detail'=>'');
		}
		$rows[$k]['count']+=$e['count'];
		$rows[$k]['last']=$e['when'];
		if(strlen($e['user'])){$rows[$k]['users'][strtolower($e['user'])]=$e['user'];}
		if(strlen($e['detail'])){$rows[$k]['detail']=$e['detail'];}
	}
	foreach($rows as $k=>$r){
		$users=array_values($r['users']);
		$rows[$k]['users']=implode(', ',array_slice($users,0,8)).(count($users)>8?' +'.(count($users)-8).' more':'');
	}
	usort($rows,function($a,$b){return strcmp($b['last'],$a['last']);});
	return $rows;
}
//---------- begin function logsUserLogFilters ----
/**
* @describe reads the search form from $_REQUEST with defaults
* @return array q,user,type,method,from,to,trace,limit
*/
function logsUserLogFilters(){
	$f=array();
	foreach(array('q','user','type','method','from','to') as $k){
		$f[$k]=isset($_REQUEST[$k])?trim((string)$_REQUEST[$k]):'';
	}
	if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$f['from'])){$f['from']='';}
	if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$f['to'])){$f['to']='';}
	$f['trace']=!empty($_REQUEST['trace'])?1:0;
	$f['limit']=isset($_REQUEST['limit']) && isNum($_REQUEST['limit'])?(int)$_REQUEST['limit']:250;
	if($f['limit'] < 25 || $f['limit'] > 5000){$f['limit']=250;}
	return $f;
}
//---------- begin function logsUserLogSearch ----
/**
* @describe searches the parsed entries newest-first. Trace lines (LDAP request / calling host /
*	fields-updated) are hidden unless type=trace or trace=1.
* @param userlog array - from logsUserLogLoad()
* @param f array - from logsUserLogFilters()
* @return array ['rows'=>array,'total'=>int,'shown'=>int,'limit'=>int]
*/
function logsUserLogSearch($userlog,$f){
	$rows=array();
	$total=0;
	$q=strtolower($f['q']);
	$user=strtolower($f['user']);
	$from=strlen($f['from'])?strtotime($f['from']):0;
	$to=strlen($f['to'])?strtotime($f['to'])+86400:0;
	$labels=array('passed'=>'Login','failed'=>'Failed','error'=>'Error','logout'=>'Logout','trace'=>'Trace');
	$classes=array('passed'=>'is-success','failed'=>'is-danger','error'=>'is-warning','logout'=>'is-info','trace'=>'');
	for($i=count($userlog['entries'])-1;$i>=0;$i--){
		$e=$userlog['entries'][$i];
		if(strlen($f['type'])){
			if($e['type']!=$f['type']){continue;}
		}
		elseif($e['type']=='trace' && !$f['trace']){continue;}
		if(strlen($user) && strtolower($e['user'])!==$user){continue;}
		if(strlen($f['method']) && $e['method']!==$f['method']){continue;}
		if($from && $e['ts'] < $from){continue;}
		if($to && $e['ts'] >= $to){continue;}
		if(strlen($q) && strpos(strtolower($e['msg'].' '.$e['user'].' '.$e['ip'].' '.$e['reason'].' '.$e['detail']),$q)===false){continue;}
		$total++;
		if(count($rows) < $f['limit']){
			$e['label']=$labels[$e['type']];
			$e['class']=$classes[$e['type']];
			$rows[]=$e;
		}
	}
	return array('rows'=>$rows,'total'=>$total,'shown'=>count($rows),'limit'=>$f['limit']);
}
//---------- begin function logsUserLogOptions ----
/**
* @describe option lists for the search form selects/datalist, with the current selection marked
* @param userlog array - from logsUserLogLoad()
* @param f array - from logsUserLogFilters()
* @return array ['types'=>[],'methods'=>[],'limits'=>[],'users'=>[]] each row value,label,selected
*/
function logsUserLogOptions($userlog,$f){
	$opts=array('types'=>array(),'methods'=>array(),'limits'=>array(),'users'=>array());
	$types=array(''=>'All events','passed'=>'Logins','failed'=>'Failed logins','error'=>'System errors','logout'=>'Logouts','trace'=>'Trace lines only');
	foreach($types as $v=>$l){$opts['types'][]=array('value'=>$v,'label'=>$l,'selected'=>$f['type']===(string)$v?'selected':'');}
	$methods=array();
	$users=array();
	foreach($userlog['entries'] as $e){
		if(strlen($e['method'])){$methods[$e['method']]=1;}
		if(strlen($e['user']) && $e['type']!='trace'){$users[strtolower($e['user'])]=$e['user'];}
	}
	ksort($methods);
	ksort($users);
	$opts['methods'][]=array('value'=>'','label'=>'All methods','selected'=>'');
	foreach(array_keys($methods) as $m){$opts['methods'][]=array('value'=>$m,'label'=>$m,'selected'=>$f['method']===$m?'selected':'');}
	foreach(array(100,250,500,1000,2500) as $n){$opts['limits'][]=array('value'=>$n,'label'=>"{$n} rows",'selected'=>$f['limit']==$n?'selected':'');}
	foreach($users as $u){$opts['users'][]=array('value'=>$u);}
	return $opts;
}
//---------- begin function logsUserLogRaw ----
/**
* @describe last N raw lines of user.log, with passwords redacted
* @param userlog array - from logsUserLogLoad()
* @param lines int
* @return string
*/
function logsUserLogRaw($userlog,$lines=1000){
	return logsUserLogRedact(tailFile($userlog['file'],$lines));
}
?>