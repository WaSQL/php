<?php
/*
	Groovy Manager - admin page (Database > Groovy Manager) for the persistent groovy/server.groovy daemon
	that dbGroovyQueryResults() routes through. Monitors it (state, threads, memory, in-flight and STUCK
	requests, log) and starts / stops / restarts / reloads / force-kills it.
	The server paths and token are derived exactly as dbGroovyQueryResults() derives them (php/database.php).
*/

//---------- begin function groovyAdminConfig
/**
* @describe the Groovy server's port, URL and runtime file paths, derived the same way dbGroovyQueryResults() does
* @return array - port, base_url, groovy_dir, token_file, pid_file, log_file, log_max_mb
* @usage $cfg=groovyAdminConfig();
*/
function groovyAdminConfig(){
	global $CONFIG;
	$port=isset($CONFIG['groovy_port']) && (int)$CONFIG['groovy_port'] ? (int)$CONFIG['groovy_port'] : 7070;
	$tempDir=getWaSQLPath('php/temp');
	$logMaxMb=isset($CONFIG['groovy_log_max_mb']) && strlen(trim($CONFIG['groovy_log_max_mb'])) ? (int)$CONFIG['groovy_log_max_mb'] : 10;
	return array(
		'port'      =>$port,
		'base_url'  =>"http://127.0.0.1:{$port}",
		'groovy_dir'=>rtrim(str_replace('\\','/',getWasqlPath('/groovy')),'/'),
		'token_file'=>$tempDir.DIRECTORY_SEPARATOR.'wasql-groovy-server.token',
		'pid_file'  =>$tempDir.DIRECTORY_SEPARATOR.'wasql-groovy-server.pid',
		'log_file'  =>dbGroovyLogFile(),
		'log_max_mb'=>$logMaxMb
	);
}

//---------- begin function groovyAdminPid
/**
* @describe the pid recorded in the server's pid file
* @return int - the pid, or 0 if there is no pid file
* @usage $pid=groovyAdminPid();
*/
function groovyAdminPid(){
	$cfg=groovyAdminConfig();
	if(!is_file($cfg['pid_file'])){return 0;}
	return (int)trim(@file_get_contents($cfg['pid_file']));
}

//---------- begin function groovyAdminCall
/**
* @describe calls a Groovy server endpoint with the stored token and a short timeout
* @param $path string - endpoint path, e.g. '/status'
* @param $timeout int - seconds to wait (default 3) - kept short so a hung server cannot hang this page
* @return array - ok (bool), data (decoded JSON array or null), error (string)
* @usage $r=groovyAdminCall('/status');
*/
function groovyAdminCall($path,$timeout=3){
	$cfg=groovyAdminConfig();
	if(!is_file($cfg['token_file'])){return array('ok'=>false,'data'=>null,'error'=>'no token file');}
	$token=trim(@file_get_contents($cfg['token_file']));
	if(!strlen($token)){return array('ok'=>false,'data'=>null,'error'=>'token file is empty');}
	$resp=dbGroovyHttp('GET',$cfg['base_url'].$path,$token,null,null,$timeout);
	if($resp===false){
		global $dbGroovyHttpError;
		$err=isset($dbGroovyHttpError['error']) ? $dbGroovyHttpError['error'] : 'no response';
		return array('ok'=>false,'data'=>null,'error'=>$err);
	}
	$data=json_decode($resp,true);
	if(!is_array($data)){return array('ok'=>false,'data'=>null,'error'=>'not JSON: '.substr($resp,0,200));}
	return array('ok'=>true,'data'=>$data,'error'=>'');
}

//---------- begin function groovyAdminWaitStopped
/**
* @describe waits until the server is gone - nothing on the port and no live pid
* @param $seconds int - max seconds to wait
* @return bool - true if it stopped in time
* @usage if(!groovyAdminWaitStopped(10)){...}
*/
function groovyAdminWaitStopped($seconds=10){
	$cfg=groovyAdminConfig();
	$until=microtime(true)+$seconds;
	while(microtime(true) < $until){
		if(!dbGroovyPortInUse($cfg['base_url']) && !dbGroovyServerAlive()){return true;}
		usleep(250000);
	}
	return false;
}

//---------- begin function groovyAdminStatus
/**
* @describe snapshot of the server for the status panel. state is one of running, legacy (an older server.groovy
*	without /status - restart it to get full monitoring), hung (process up but not answering), foreign (the port
*	is held by something WaSQL did not start) or stopped
* @return array - state, label, class, hint, pid, facts (label/value/sub rows), pool, inflight rows, databases rows
* @usage $status=groovyAdminStatus();
*/
function groovyAdminStatus(){
	$cfg=groovyAdminConfig();
	$pid=groovyAdminPid();
	$status=array(
		'state'=>'stopped','label'=>'Stopped','class'=>'is-stopped','hint'=>'',
		'pid'=>$pid,'facts'=>array(),'inflight'=>array(),'databases'=>array(),'stuck_count'=>0,
		'checked'=>date('H:i:s'),'message'=>'','message_class'=>''
	);
	$r=groovyAdminCall('/status');
	if($r['ok'] && isset($r['data']['status']) && $r['data']['status']=='ok'){
		$d=$r['data'];
		$status['state']='running';
		$status['label']='Running';
		$status['class']='is-running';
		$status['pid']=$d['pid'];
		$status['facts']=groovyAdminFacts($d,$cfg);
		$status['inflight']=groovyAdminInflightRows($d['inflight']);
		foreach($status['inflight'] as $row){
			if($row['stuck']){$status['stuck_count']++;}
		}
		if($status['stuck_count'] > 0){
			$status['class']='is-warning';
			$status['label']="Running - {$status['stuck_count']} stuck";
			$status['hint']='One or more requests have run past the stuck threshold. See In-flight requests below for where they are blocked.';
		}
		$status['databases']=groovyAdminDatabaseRows();
		$status['actions']=groovyAdminActions($status['state'],(int)$status['pid']);
		return $status;
	}
	//no /status: an older server.groovy still answers /ping
	$p=groovyAdminCall('/ping');
	if($p['ok'] && isset($p['data']['status']) && $p['data']['status']=='ok'){
		$status['state']='legacy';
		$status['label']='Running (older version)';
		$status['class']='is-warning';
		$status['pid']=$p['data']['pid'];
		$status['hint']='This server predates the /status endpoint, so threads, memory and in-flight requests are not available. Restart it to load the current server.groovy.';
		$status['facts']=array(
			array('label'=>'PID','value'=>$p['data']['pid'],'sub'=>''),
			array('label'=>'Uptime','value'=>groovyAdminDuration($p['data']['uptime']),'sub'=>''),
			array('label'=>'Port','value'=>$cfg['port'],'sub'=>$cfg['base_url'])
		);
		$status['databases']=groovyAdminDatabaseRows();
		$status['actions']=groovyAdminActions($status['state'],(int)$status['pid']);
		return $status;
	}
	$alive=dbGroovyServerAlive();
	$portInUse=dbGroovyPortInUse($cfg['base_url']);
	if($alive){
		$status['state']='hung';
		$status['label']='Not responding';
		$status['class']='is-danger';
		$status['hint']="The process (pid {$pid}) is alive but did not answer within 3 seconds ({$r['error']}). Every handler thread may be blocked. "
			."The log's STUCK entries show where. Stop asks it to exit; if it is fully hung only Force Kill will work.";
	}
	elseif($portInUse){
		$who=dbGroovyPortProcess($cfg['port']);
		$status['state']='foreign';
		$status['label']='Port in use';
		$status['class']='is-danger';
		$status['hint']="Port {$cfg['port']} is held by ".(strlen($who)?$who:'another process')
			.", which WaSQL did not start (or whose pid/token files are gone). WaSQL cannot start its server until that process is stopped.";
	}
	else{
		$status['hint']='Not running. It starts automatically on the next Groovy query, or use Start.';
	}
	$status['facts']=array(
		array('label'=>'PID file','value'=>$pid ? $pid : 'none','sub'=>$alive ? 'process alive' : 'no live process'),
		array('label'=>'Port','value'=>$cfg['port'],'sub'=>$portInUse ? 'in use' : 'free')
	);
	$status['actions']=groovyAdminActions($status['state'],(int)$status['pid']);
		return $status;
}

//---------- begin function groovyAdminActions
/**
* @describe the action buttons that make sense for a server state
* @param $state string - running, legacy, hung, foreign or stopped
* @param $pid int - pid from the pid file (Force Kill needs one)
* @return array - rows of func, label, icon, class, confirm
* @usage $status['actions']=groovyAdminActions($status['state'],$pid);
*/
function groovyAdminActions($state,$pid){
	$all=array(
		'start'  =>array('func'=>'start','label'=>'Start','icon'=>'icon-play','class'=>'is-success','confirm'=>''),
		'restart'=>array('func'=>'restart','label'=>'Restart','icon'=>'icon-history','class'=>'is-warning','confirm'=>'Restart the Groovy server? Requests in progress are cut off.'),
		'reload' =>array('func'=>'reload','label'=>'Reload Modules','icon'=>'icon-code','class'=>'is-info','confirm'=>''),
		'stop'   =>array('func'=>'stop','label'=>'Stop','icon'=>'icon-power','class'=>'is-light','confirm'=>'Stop the Groovy server? Requests in progress are cut off. It restarts automatically on the next Groovy query.'),
		'kill'   =>array('func'=>'kill','label'=>'Force Kill','icon'=>'icon-cancel','class'=>'is-danger','confirm'=>'Force-kill the Groovy server process? Use this only when it is hung and Stop does not work.')
	);
	switch($state){
		case 'running':
		case 'legacy':  $keys=array('restart','reload','stop','kill'); break;
		case 'hung':    $keys=array('restart','stop','kill'); break;
		case 'stopped': $keys=array('start'); break;
		default:        $keys=array(); break;
	}
	$rows=array();
	foreach($keys as $key){
		if($key=='kill' && $pid <= 0){continue;}
		$rows[]=$all[$key];
	}
	return $rows;
}

//---------- begin function groovyAdminFacts
/**
* @describe shapes a /status response into the label/value/sub rows of the status cards
* @param $d array - decoded /status response
* @param $cfg array - groovyAdminConfig()
* @return array - rows of label, value, sub
* @usage $facts=groovyAdminFacts($d,$cfg);
*/
function groovyAdminFacts($d,$cfg){
	$pool=is_array($d['pool']) ? $d['pool'] : array('active'=>0,'max'=>0,'queued'=>0,'completed'=>0,'largest'=>0);
	$mem=$d['memory'];
	$c=$d['config'];
	$logSize=is_file($cfg['log_file']) ? verboseSize(filesize($cfg['log_file'])) : 'none';
	$idle=(int)$c['idle_minutes'] > 0 ? "auto-shutdown after {$c['idle_minutes']} min idle" : 'auto-shutdown disabled';
	return array(
		array('label'=>'PID','value'=>$d['pid'],'sub'=>'port '.$c['port']),
		array('label'=>'Uptime','value'=>groovyAdminDuration($d['uptime_ms']),'sub'=>'started '.date('Y-m-d H:i:s',(int)($d['started']/1000))),
		array('label'=>'Threads busy','value'=>"{$pool['active']} / {$pool['max']}",'sub'=>"peak {$pool['largest']}"),
		array('label'=>'Queued','value'=>$pool['queued'],'sub'=>"of {$c['queue_cap']} max"),
		array('label'=>'Requests served','value'=>number_format((float)$pool['completed']),'sub'=>'idle for '.groovyAdminDuration($d['idle_ms']).', '.$idle),
		array('label'=>'JVM memory','value'=>"{$mem['used_mb']} MB",'sub'=>"of {$mem['total_mb']} MB allocated, {$mem['max_mb']} MB max",'help'=>'jvm'),
		array('label'=>'Versions','value'=>'Groovy '.$d['versions']['groovy'],'sub'=>'Java '.$d['versions']['java'].' on '.$d['versions']['os']),
		array('label'=>'Stuck threshold','value'=>(int)$c['stuck_minutes'] > 0 ? "{$c['stuck_minutes']} min" : 'disabled','sub'=>'max body '.$c['max_body_mb'].' MB','help'=>'stuck'),
		array('label'=>'Log','value'=>$logSize,'sub'=>(int)$c['log_max_mb'] > 0 && strlen($c['log_file']) ? "rolls at {$c['log_max_mb']} MB" : 'no size cap'),
		array('label'=>'Modules','value'=>count($d['modules']),'sub'=>implode(', ',$d['modules']))
	);
}

//---------- begin function groovyAdminHelp
/**
* @describe data for a tile's "how do I change this" popup: which environment variable sets it, an example value and the current value
* @param $topic string - jvm or stuck
* @return array - topic, title, var, example, current, default (empty array for an unknown topic)
* @usage $help=groovyAdminHelp('jvm');
*/
function groovyAdminHelp($topic){
	$r=groovyAdminCall('/status');
	$d=$r['ok'] ? $r['data'] : array();
	switch($topic){
		case 'jvm':
			$current=isset($d['memory']['max_mb']) ? "{$d['memory']['max_mb']} MB max heap ({$d['memory']['used_mb']} MB in use)" : 'unknown - the server is not answering';
			return array('topic'=>'jvm','title'=>'JVM memory','var'=>'JAVA_OPTS','example'=>'-Xmx2g','current'=>$current,
				'default'=>'Java picks a maximum heap of about a quarter of the machine\'s RAM');
		case 'stuck':
			$current=isset($d['config']['stuck_minutes']) ? ((int)$d['config']['stuck_minutes'] > 0 ? "{$d['config']['stuck_minutes']} minutes" : 'disabled') : 'unknown - the server is not answering';
			return array('topic'=>'stuck','title'=>'Stuck threshold','var'=>'WASQL_GROOVY_STUCK_MINUTES','example'=>'10','current'=>$current,
				'default'=>'5 minutes');
	}
	return array();
}

//---------- begin function groovyAdminInflightRows
/**
* @describe shapes /status inflight entries for the in-flight table: age as text, stack as one string
* @param $inflight array - inflight list from /status
* @return array - rows of thread, user, host, path, detail, age, state, stuck, stack
* @usage $rows=groovyAdminInflightRows($d['inflight']);
*/
function groovyAdminInflightRows($inflight){
	$rows=array();
	if(!is_array($inflight)){return $rows;}
	foreach($inflight as $req){
		$rows[]=array(
			'thread'=>$req['thread'],
			'user'  =>isset($req['user']) ? $req['user'] : '',
			'host'  =>isset($req['host']) ? $req['host'] : '',
			'path'  =>$req['path'],
			'detail'=>$req['detail'],
			'age'   =>groovyAdminDuration($req['age_ms']),
			'state' =>$req['state'],
			'stuck' =>!empty($req['stuck']),
			'stack' =>is_array($req['stack']) ? implode("\n",$req['stack']) : ''
		);
	}
	return $rows;
}

//---------- begin function groovyAdminStats
/**
* @describe request statistics from /stats, shaped for the stats view
* @param $reset bool - reset the server's counters first
* @return array - ok, error, since, cards (label/value/sub), endpoints, dbs, users, slowest, errors (rows ready to render)
* @usage $stats=groovyAdminStats();
*/
function groovyAdminStats($reset=false){
	$stats=array('ok'=>false,'error'=>'','since'=>'','cards'=>array(),'endpoints'=>array(),'dbs'=>array(),'users'=>array(),'slowest'=>array(),'errors'=>array(),'checked'=>date('H:i:s'));
	$r=groovyAdminCall($reset ? '/stats?reset=1' : '/stats');
	if(!$r['ok'] || !isset($r['data']['totals'])){
		$stats['error']=$r['ok']
			? 'This server predates the /stats endpoint. Restart it to load the current server.groovy.'
			: "No statistics - the server is not answering ({$r['error']}).";
		return $stats;
	}
	$d=$r['data'];
	$t=$d['totals'];
	$since=(int)($d['since']/1000);
	$stats['ok']=true;
	$stats['since']=date('Y-m-d H:i:s',$since).' ('.groovyAdminDuration($d['now']-$d['since']).' ago)';
	$errPct=$t['requests'] ? round(100*$t['errors']/$t['requests'],1) : 0;
	$stats['cards']=array(
		array('label'=>'Requests','value'=>number_format((float)$t['requests']),'sub'=>'queries, executes, meta and eval'),
		array('label'=>'Users','value'=>number_format((float)(isset($t['users']) ? $t['users'] : 0)),'sub'=>'distinct callers since '.date('H:i',$since)),
		array('label'=>'Errors','value'=>number_format((float)$t['errors']),'sub'=>"{$errPct}% of requests"),
		array('label'=>'Average time','value'=>groovyAdminDuration($t['avg_ms']),'sub'=>'per request'),
		array('label'=>'Last minute','value'=>number_format((float)$t['last_1m']),'sub'=>'requests'),
		array('label'=>'Last 5 / 15 min','value'=>number_format((float)$t['last_5m']).' / '.number_format((float)$t['last_15m']),'sub'=>'requests'),
		array('label'=>'Last hour','value'=>number_format((float)$t['last_60m']),'sub'=>'requests')
	);
	$stats['endpoints']=groovyAdminCounterRows($d['by_endpoint'],'endpoint');
	$stats['dbs']=groovyAdminCounterRows($d['by_db'],'db');
	$stats['users']=groovyAdminCounterRows(isset($d['by_user']) ? $d['by_user'] : array(),'user');
	$stats['slowest']=groovyAdminRequestRows($d['slowest']);
	$stats['errors']=groovyAdminRequestRows($d['errors']);
	return $stats;
}

//---------- begin function groovyAdminCounterRows
/**
* @describe shapes /stats counter entries (by_endpoint / by_db) into table rows
* @param $counters array - counter list from /stats
* @param $kind string - endpoint, db or user: which /errors filter the row's error-count link uses
* @return array - rows of kind, name, count, errors, avg, max, last
* @usage $rows=groovyAdminCounterRows($d['by_db'],'db');
*/
function groovyAdminCounterRows($counters,$kind){
	$rows=array();
	if(!is_array($counters)){return $rows;}
	foreach($counters as $c){
		$rows[]=array(
			'kind'  =>$kind,
			'name'  =>$c['name'],
			'count' =>number_format((float)$c['count']),
			'errors'=>number_format((float)$c['errors']),
			'has_errors'=>$c['errors'] > 0,
			'avg'   =>groovyAdminDuration($c['avg_ms']),
			'max'   =>groovyAdminDuration($c['max_ms']),
			'last'  =>$c['last_at'] ? date('H:i:s',(int)($c['last_at']/1000)) : ''
		);
	}
	return $rows;
}

//---------- begin function groovyAdminRequestRows
/**
* @describe shapes /stats slowest / errors entries into table rows
* @param $requests array - request list from /stats
* @return array - rows of at, endpoint, db, user, host, time, code, detail, error
* @usage $rows=groovyAdminRequestRows($d['slowest']);
*/
function groovyAdminRequestRows($requests){
	$rows=array();
	if(!is_array($requests)){return $rows;}
	foreach($requests as $req){
		$rows[]=array(
			'at'      =>date('m/d H:i:s',(int)($req['at']/1000)),
			'endpoint'=>$req['endpoint'],
			'db'      =>$req['db'],
			'user'    =>isset($req['user']) ? $req['user'] : '',
			'host'    =>isset($req['host']) ? $req['host'] : '',
			'time'    =>groovyAdminDuration($req['ms']),
			'code'    =>$req['code'],
			'detail'  =>$req['detail'],
			'error'   =>isset($req['error']) ? $req['error'] : ''
		);
	}
	return $rows;
}

//---------- begin function groovyAdminDatabaseRows
/**
* @describe the databases the server can query, from /databases
* @return array - rows of type, names (comma separated)
* @usage $rows=groovyAdminDatabaseRows();
*/
function groovyAdminDatabaseRows(){
	$r=groovyAdminCall('/databases');
	$rows=array();
	if(!$r['ok'] || !isset($r['data']['data']) || !is_array($r['data']['data'])){return $rows;}
	foreach($r['data']['data'] as $type=>$names){
		$rows[]=array('type'=>$type,'names'=>implode(', ',$names));
	}
	return $rows;
}

//---------- begin function groovyAdminDuration
/**
* @describe milliseconds as short readable text ("3 secs", "2 mins 5 secs")
* @param $ms int - milliseconds
* @return string
* @usage echo groovyAdminDuration(125000);
*/
function groovyAdminDuration($ms){
	$secs=(int)floor($ms/1000);
	if($secs < 1){return ((int)$ms).' ms';}
	return trim(verboseTime($secs));
}

//---------- begin function groovyAdminStart
/**
* @describe starts the server (no-op if it already answers), waiting for it to become ready
* @return string - empty on success, otherwise the error text
* @usage $err=groovyAdminStart();
*/
function groovyAdminStart(){
	$cfg=groovyAdminConfig();
	$err=dbGroovyEnsureServer($cfg['groovy_dir'],$cfg['token_file'],$cfg['base_url']);
	return $err===null ? '' : $err;
}

//---------- begin function groovyAdminStop
/**
* @describe asks the server to shut down (/shutdown) and waits up to 10 seconds for it to exit
* @return string - empty on success, otherwise why it is still running
* @usage $err=groovyAdminStop();
*/
function groovyAdminStop(){
	$r=groovyAdminCall('/shutdown');
	if(groovyAdminWaitStopped(10)){return '';}
	if(!$r['ok']){
		return "The server did not answer /shutdown ({$r['error']}). If it is hung, use Force Kill.";
	}
	return 'The server accepted /shutdown but is still running after 10 seconds. Use Force Kill if it does not exit.';
}

//---------- begin function groovyAdminKill
/**
* @describe force-kills the server process from its pid file (SIGKILL / taskkill /F) and removes the pid/token
*	files, which a killed JVM cannot clean up itself. For a hung server that ignores /shutdown
* @return string - empty on success, otherwise the error text
* @usage $err=groovyAdminKill();
*/
function groovyAdminKill(){
	$cfg=groovyAdminConfig();
	$pid=groovyAdminPid();
	if($pid <= 0){return 'There is no pid file, so there is no WaSQL-started process to kill.';}
	if(PHP_OS_FAMILY==='Windows'){
		$out=array();
		@exec('taskkill /F /T /PID '.$pid.' 2>&1',$out);
	}
	elseif(function_exists('posix_kill')){
		@posix_kill($pid,9);
	}
	else{
		@exec('kill -9 '.$pid.' 2>&1');
	}
	if(!groovyAdminWaitStopped(5)){
		return "pid {$pid} is still running - the web server user may not have permission to kill it. Kill it as root / an administrator.";
	}
	@unlink($cfg['pid_file']);
	@unlink($cfg['token_file']);
	return '';
}

//---------- begin function groovyAdminCancel
/**
* @describe cancels one in-flight request (/cancel), then checks whether it actually ended
* @param $thread string - handler thread name from the in-flight table, e.g. wasql-handler-7
* @return array - class (is-success / is-warning / is-danger) and message for the status panel
* @usage $res=groovyAdminCancel('wasql-handler-7');
*/
function groovyAdminCancel($thread){
	if(!preg_match('/^[A-Za-z0-9_\-]+$/',$thread)){
		return array('class'=>'is-danger','message'=>'Invalid thread name.');
	}
	//aborting each connection can take up to 5s server-side, so allow more than the default 3s
	$r=groovyAdminCall('/cancel?thread='.rawurlencode($thread),20);
	if(!$r['ok']){return array('class'=>'is-danger','message'=>"Cancel failed: {$r['error']}");}
	$d=$r['data'];
	if(empty($d['cancelled'])){
		$err=isset($d['error']) ? $d['error'] : 'unknown error';
		return array('class'=>'is-warning','message'=>$err);
	}
	$conns=isset($d['connections']) && count($d['connections']) ? implode(', ',$d['connections']) : 'none tracked';
	if(!empty($d['interrupted'])){$conns.='; thread interrupted';}
	//give the request a moment to unwind, then see if that thread is still on the same request
	usleep(1500000);
	$s=groovyAdminCall('/status');
	if($s['ok'] && isset($s['data']['inflight'])){
		foreach($s['data']['inflight'] as $req){
			if($req['thread']==$thread && $req['path']==$d['path'] && $req['detail']==$d['detail']){
				return array('class'=>'is-warning','message'=>"Cancel sent to {$thread} (connections: {$conns}), but the request is still running. "
					."The driver did not give up when its connection was aborted. If it stays stuck, Restart or Force Kill the server.");
			}
		}
	}
	return array('class'=>'is-success','message'=>"Cancelled {$d['path']} on {$thread} (connections: {$conns}).");
}

//---------- begin function groovyAdminErrors
/**
* @describe recent errors for one endpoint or database (/errors), shaped for the error popup
* @param $endpoint string - e.g. /query (blank = any)
* @param $db string - database name (blank = any)
* @param $user string - caller username (blank = any)
* @return array - ok, error, title, kept, rows (at, endpoint, db, user, time, code, detail, error)
* @usage $errors=groovyAdminErrors('/query','');
*/
function groovyAdminErrors($endpoint,$db,$user=''){
	$q=array();
	if(strlen($endpoint)){$q['endpoint']=$endpoint;}
	if(strlen($db)){$q['db']=$db;}
	if(strlen($user)){$q['user']=$user;}
	$r=groovyAdminCall('/errors'.(count($q) ? '?'.http_build_query($q) : ''));
	$errors=array('ok'=>false,'error'=>'','title'=>strlen($user) ? "user {$user}" : (strlen($db) ? "database {$db}" : "endpoint {$endpoint}"),'kept'=>200,'rows'=>array());
	if(!$r['ok'] || !isset($r['data']['errors'])){
		$errors['error']=$r['ok'] ? 'This server predates the /errors endpoint. Restart it to load the current server.groovy.' : "The server is not answering ({$r['error']}).";
		return $errors;
	}
	$errors['ok']=true;
	$errors['kept']=(int)$r['data']['kept'];
	$errors['rows']=groovyAdminRequestRows($r['data']['errors']);
	return $errors;
}

//---------- begin function groovyAdminReload
/**
* @describe /reload: re-reads config.xml and clears the module cache so the db modules recompile from disk.
*	Does not reload server.groovy itself - that needs a restart
* @return string - empty on success, otherwise the error text
* @usage $err=groovyAdminReload();
*/
function groovyAdminReload(){
	$r=groovyAdminCall('/reload',30);
	if(!$r['ok']){return "Reload failed: {$r['error']}";}
	return '';
}

//---------- begin function groovyAdminLog
/**
* @describe the tail of the server log, optionally filtered, shaped for the log view
* @param $lines int - number of lines to return (100-5000)
* @param $filter string - all, stuck (STUCK entries with their stack lines), errors (errors, warnings, cancels and STUCK entries), or requests
* @return array - file, size, exists, lines (rows of text, class)
* @usage $log=groovyAdminLog(300,'all');
*/
function groovyAdminLog($lines=300,$filter='all'){
	$cfg=groovyAdminConfig();
	$lines=max(100,min(5000,(int)$lines));
	$log=array('file'=>$cfg['log_file'],'size'=>'','exists'=>false,'lines'=>array(),'filter'=>$filter,'count'=>$lines);
	if(!is_file($cfg['log_file'])){return $log;}
	$log['exists']=true;
	$size=filesize($cfg['log_file']);
	$log['size']=verboseSize($size);
	//filtered views scan more of the file; cap the read at the newest 20 MB
	$fh=@fopen($cfg['log_file'],'rb');
	if($fh===false){return $log;}
	$max=20*1024*1024;
	if($size > $max){fseek($fh,$size-$max);}
	$data=stream_get_contents($fh);
	fclose($fh);
	$all=preg_split('/\r\n|\r|\n/',rtrim($data));
	$keep=array();
	$inStuck=false;
	//the watchdog's real entries only - case-sensitive, so the startup line "Stuck-request report: after 5 min" is not one
	$stuckRe='/\bSTUCK (request|summary)\b/';
	foreach($all as $line){
		$isStack=preg_match('/^\s+at\s/',$line);
		$isStuck=preg_match($stuckRe,$line);
		switch($filter){
			case 'stuck':
				if($isStuck){$inStuck=true;$keep[]=$line;}
				elseif($inStuck && $isStack){$keep[]=$line;}
				else{$inStuck=false;}
			break;
			case 'errors':
				if($isStuck || preg_match('/error|warning|exception|fatal|could not|failed|CANCEL requested/i',$line)){$inStuck=$isStuck;$keep[]=$line;}
				elseif($inStuck && $isStack){$keep[]=$line;}
				else{$inStuck=false;}
			break;
			case 'requests':
				if(preg_match('/\]\s[0-9\:]+\s\/(query|queryfile|execute|executeps|meta|eval)\b/',$line)){$keep[]=$line;}
			break;
			default:
				$keep[]=$line;
			break;
		}
	}
	if(count($keep) > $lines){$keep=array_slice($keep,-$lines);}
	foreach($keep as $line){
		$class='';
		if(preg_match($stuckRe,$line)){$class='is-stuck';}
		elseif(preg_match('/^\s+at\s/',$line)){$class='is-stack';}
		elseif(preg_match('/error|exception|fatal/i',$line)){$class='is-error';}
		elseif(preg_match('/warning|skipped|CANCEL requested/i',$line)){$class='is-warn';}
		elseif(preg_match('/^---\s.*(starting|trimmed)/',$line) || stringContains($line,'Listening on')){$class='is-start';}
		$log['lines'][]=array('text'=>$line,'class'=>$class);
	}
	return $log;
}

//---------- begin function groovyAdminClearLog
/**
* @describe empties the Groovy server log so only what happens from now on shows. The old contents are kept in
*	{log}.prev (replacing the previous .prev), so clearing by mistake loses nothing. The file is truncated IN PLACE,
*	never deleted: the running server holds it open in append mode, and a deleted/recreated file would silently stop
*	receiving its lines (Linux) or fail (Windows). A marker line records who cleared it and when.
* @return string - empty on success, otherwise the error text
* @usage $err=groovyAdminClearLog();
*/
function groovyAdminClearLog(){
	global $USER;
	$cfg=groovyAdminConfig();
	$file=$cfg['log_file'];
	if(!is_file($file)){return '';}
	$prev=$file.'.prev';
	if(!@copy($file,$prev)){
		return "Could not save the current log to {$prev} - the log was NOT cleared. Check that the web server user can write to ".dirname($file).'.';
	}
	$fh=@fopen($file,'r+');
	if($fh===false){return "Could not open {$file} for writing - the log was NOT cleared (the previous contents are also in {$prev}).";}
	$ok=flock($fh,LOCK_EX) && ftruncate($fh,0);
	if($ok){
		$who=isset($USER['username']) && strlen($USER['username']) ? $USER['username'] : 'admin';
		rewind($fh);
		fwrite($fh,'--- '.date('Y-m-d H:i:s')." log cleared by {$who} from Groovy Manager - the previous log is in ".basename($prev)." ---".PHP_EOL);
		fflush($fh);
	}
	flock($fh,LOCK_UN);
	fclose($fh);
	clearstatcache(true,$file);
	if(!$ok){return "Could not truncate {$file} - the log was NOT cleared (a copy is in {$prev}).";}
	return '';
}

//---------- begin function groovyAdminStartFresh
/**
* @describe "I fixed it - show me only what is new": clears the log (see groovyAdminClearLog) AND resets the server's
*	statistics, which also empties its recent-errors list. The stats reset needs the server running; the log clear does not.
* @return array - message, class (is-success / is-warning / is-danger)
* @usage $result=groovyAdminStartFresh();
*/
function groovyAdminStartFresh(){
	$logErr=groovyAdminClearLog();
	$r=groovyAdminCall('/stats?reset=1');
	$parts=array();
	$parts[]=strlen($logErr) ? $logErr : 'Log cleared (previous log saved as '.basename(groovyAdminConfig()['log_file']).'.prev).';
	$parts[]=$r['ok'] ? 'Statistics and recent errors reset.' : "Statistics not reset - the server is not answering ({$r['error']}); they start empty when it next starts anyway.";
	if(strlen($logErr)){$class='is-danger';}
	elseif(!$r['ok']){$class='is-warning';}
	else{$class='is-success';}
	return array('message'=>implode(' ',$parts),'class'=>$class);
}
?>
