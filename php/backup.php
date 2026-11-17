<?php
/*
	Database backups for WaSQL.

	TWO MODES
	1. <database> tags in config.xml  (preferred - runs automatically from cron_scheduler.php)
		<database name="wasql" dbtype="mysqli" dbhost="localhost" dbuser="u" dbpass="p" dbname="wasql"
			backup="daily"                 - REQUIRED - daily | weekly | monthly
			backup_dir="/mnt/dbbackup"     - REQUIRED - directory must already exist and be writable. Backup FAILS if it does not.
			backup_time="02:30"            - optional - 24h HH:MM, do not start before this time (default 00:00)
			backup_email="you@site.com"    - optional - emailed when a backup fails (at most once per 24 hours per database)
			backup_require_mount="1"       - optional - fail unless backup_dir is a mount point (guards against an unmounted NAS/USB)
		/>
		Supports mysql/mysqli (mysqldump), postgres (pg_dump) and sqlite (VACUUM INTO). Files are named
			{dbname}_{YYYY-MM-DD}.sql.gz    (sqlite: .sqlite.gz)
		Retention (also pruned only after a successful backup):
			daily   - newest 10 files + newest file of each of the last 5 prior months
			weekly  - newest 5  files + newest file of each of the last 5 prior months
			monthly - newest 10 files
		Do NOT point backup_dir at a directory used by the legacy mode below - its age-based cleanup would delete these files.
		Passwords never appear on a command line or in a log: mysql reads a temp 0600 option file, postgres a temp pgpass file.
		A failed backup is retried at most once per hour.
		cron_scheduler.php checks once a minute and launches this script detached when something is due - no extra cron needed.

	2. LEGACY - the site (hostname) block in config.xml with backup="1"
		backup="1"                        - REQUIRED - will skip if not set to 1 or true
		backup_dir="/var/www/bkup"        - directory to backup files to (defaults to sh/backup)
		backup_retain_days="10"           - keeps backup for 10 days (defaults to 5 days)
		backup_folder[_x]="/var/www/shared" - folder to backup. change x to a number or comma separate folders. No default
		backup_folder[_x]_exts="php,jpg,png" - only backup these file extensions in this folder
		backup_email="you@yourdomain.com" - if set, send backup files to this email also
		backup_email_days="1,15"          - if set only send backup files to email on these days
		backup_email_subject="backup for mysite" - defaults to backup for {sitename}

	COMMAND LINE (CLI only)
		php backup.php                   - due <database> backups, then legacy site backups (old crontab entries keep working)
		php backup.php databases         - only the <database> backups (what cron_scheduler.php launches)
		php backup.php force             - ignore schedule/time/retry limits and back up every <database> backup job now
		php backup.php force wasql       - same, but only the <database> whose name or dbname is "wasql"
*/
if(backupIsEntryScript()){
	backupMain(isset($_SERVER['argv'])?$_SERVER['argv']:array());
}

//---------- begin function backupIsEntryScript ----------
/**
* @describe true when backup.php itself is the running script (vs. being included by cron_scheduler.php for its functions)
* @return boolean
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupIsEntryScript(){
	if(!isset($_SERVER['SCRIPT_FILENAME'])){return false;}
	return realpath($_SERVER['SCRIPT_FILENAME'])===realpath(__FILE__);
}
//---------- begin function backupMain ----------
/**
* @describe entry point when backup.php is run from the command line
* @param argv array - command line arguments
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupMain($argv){
	if(php_sapi_name()!=='cli'){
		echo "backup.php is a command line app only.";
		exit(1);
	}
	ini_set('max_execution_time', 72000);
	set_time_limit(72000);
	error_reporting(E_ALL & ~E_NOTICE);
	$progpath=dirname(__FILE__);
	global $CONFIG;
	global $ALLCONFIG;
	$_SERVER['HTTP_HOST']='localhost';
	include_once("{$progpath}/common.php");
	include_once("{$progpath}/config.php");
	//same timezone rule as cron_scheduler.php so both agree on what "due" means
	date_default_timezone_set('America/Denver');
	if(isset($CONFIG['timezone'])){@date_default_timezone_set($CONFIG['timezone']);}
	$args=array_slice($argv,1);
	$force=in_array('force',$args);
	$dbonly=in_array('databases',$args);
	$only='';
	foreach($args as $arg){
		if(!in_array($arg,array('force','databases'))){$only=$arg;}
	}
	$lock=backupLockAcquire();
	if(!$lock){
		backupMessage("backup already running - exiting");
		exit(0);
	}
	$failures=backupRunDatabases($force,$only);
	if(!$dbonly){
		backupLegacy();
	}
	exit($failures?1:0);
}
//---------- begin function backupPolicy ----------
/**
* @describe retention policy by frequency: array(newest files to keep, prior months to keep)
* @return array
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupPolicy(){
	return array('daily'=>array(10,5),'weekly'=>array(5,5),'monthly'=>array(10,0));
}
//---------- begin function backupMessage ----------
/**
* @describe echo and append a line to logs/backup.log (rotates at 1MB). Never pass secrets in $msg.
* @param msg string
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupMessage($msg){
	$logfile=dirname(dirname(__FILE__)).'/logs/backup.log';
	$ctime=time();
	$cdate=date('Y-m-d H:i:s',$ctime);
	$msg="{$cdate},{$ctime},{$msg}".PHP_EOL;
	echo $msg;
	if(!is_file($logfile) || filesize($logfile) > 1000000 ){
		@file_put_contents($logfile,$msg);
	}
	else{
		@file_put_contents($logfile,$msg,FILE_APPEND);
	}
	return;
}
//---------- begin function backupTempPath ----------
/**
* @describe directory for the lock and state files
* @return string
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupTempPath(){
	$path=dirname(dirname(__FILE__)).'/php/temp';
	if(!is_dir($path)){@mkdir($path,0777,true);}
	return $path;
}
//---------- begin function backupLockAcquire ----------
/**
* @describe take the exclusive backup lock (released by the OS when the process ends, so it can never go stale)
* @return resource|false lock handle - keep it referenced for the life of the process
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupLockAcquire(){
	global $backupLockHandle;
	$fp=@fopen(backupTempPath().'/backup.lock','c');
	if(!$fp){return false;}
	if(!flock($fp,LOCK_EX|LOCK_NB)){fclose($fp);return false;}
	$backupLockHandle=$fp;
	return $fp;
}
//---------- begin function backupLockHeld ----------
/**
* @describe true when another process is currently running a backup
* @return boolean
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupLockHeld(){
	$fp=@fopen(backupTempPath().'/backup.lock','c');
	if(!$fp){return false;}
	if(flock($fp,LOCK_EX|LOCK_NB)){
		flock($fp,LOCK_UN);fclose($fp);
		return false;
	}
	fclose($fp);
	return true;
}
//---------- begin function backupStateLoad ----------
/**
* @describe load the per-database failure/notification state
* @return array
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupStateLoad(){
	$file=backupTempPath().'/backup_state.json';
	if(!is_file($file)){return array();}
	$state=json_decode((string)@file_get_contents($file),true);
	return is_array($state)?$state:array();
}
//---------- begin function backupStateSave ----------
/**
* @describe save the per-database failure/notification state
* @param state array
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupStateSave($state){
	@file_put_contents(backupTempPath().'/backup_state.json',json_encode($state));
}
//---------- begin function backupJobs ----------
/**
* @describe build the list of backup jobs from <database backup="daily|weekly|monthly"> tags in config.xml
* @return array list of job arrays (duplicates of the same server+database collapsed)
* @usage $jobs=backupJobs();
*/
function backupJobs(){
	global $DATABASE;
	$policy=backupPolicy();
	$raw=array();
	if(!is_array($DATABASE)){return array();}
	foreach($DATABASE as $db){
		if(!is_array($db)){continue;}
		$freq=isset($db['backup'])?strtolower(trim($db['backup'])):'';
		if(!isset($policy[$freq])){continue;}
		if(!isset($db['dbname']) || !strlen($db['dbname'])){continue;}
		$dbtype=isset($db['dbtype'])?strtolower(trim($db['dbtype'])):'mysql';
		$type='';
		if(strpos($dbtype,'mysql')===0){$type='mysql';}
		elseif(strpos($dbtype,'postgres')===0){$type='postgres';}
		elseif(strpos($dbtype,'sqlite')===0){$type='sqlite';}
		$time='00:00';
		if(isset($db['backup_time']) && preg_match('/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/',trim($db['backup_time']),$m)){
			$time=sprintf('%02d:%s',$m[1],$m[2]);
		}
		$dir=isset($db['backup_dir'])?trim($db['backup_dir']):'';
		if(strlen($dir)>1){$dir=rtrim($dir,'/\\');}
		$job=array(
			'name'=>isset($db['name'])?$db['name']:$db['dbname'],
			'type'=>$type,
			'dbtype'=>$dbtype,
			'freq'=>$freq,
			'dir'=>$dir,
			'time'=>$time,
			'email'=>isset($db['backup_email'])?trim($db['backup_email']):'',
			'require_mount'=>(isset($db['backup_require_mount']) && in_array(strtolower(trim($db['backup_require_mount'])),array('1','true','yes')))?1:0,
			'dbname'=>$db['dbname'],
			'dbhost'=>isset($db['dbhost'])?$db['dbhost']:'localhost',
			'dbport'=>isset($db['dbport'])?$db['dbport']:'',
			'dbuser'=>isset($db['dbuser'])?$db['dbuser']:'',
			'dbpass'=>isset($db['dbpass'])?$db['dbpass']:'',
			'ext'=>$type=='sqlite'?'sqlite.gz':'sql.gz'
		);
		//same database to two different backup_dir values is allowed; the same database+dir twice is collapsed
		$job['key']=(($type=='sqlite')?"sqlite|{$job['dbname']}":"{$type}|{$job['dbhost']}|{$job['dbport']}|{$job['dbname']}")."|{$dir}";
		if(!isset($raw[$job['key']])){$raw[$job['key']]=$job;}
	}
	//file prefix: dbname if unique across jobs, otherwise the config name. sqlite always uses the name (dbname is a path)
	$counts=array();
	foreach($raw as $job){$counts[$job['dbname']]=isset($counts[$job['dbname']])?$counts[$job['dbname']]+1:1;}
	$jobs=array();
	foreach($raw as $job){
		$prefix=($job['type']=='sqlite' || $counts[$job['dbname']] > 1)?$job['name']:$job['dbname'];
		$job['prefix']=preg_replace('/[^A-Za-z0-9_.-]+/','_',$prefix);
		$jobs[]=$job;
	}
	return $jobs;
}
//---------- begin function backupDirCheck ----------
/**
* @describe verify a job's backup_dir. Never creates the directory.
* @param job array
* @return string empty when ok, otherwise the reason it can not be used
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupDirCheck($job){
	if(!strlen($job['dir'])){return 'backup_dir is not set';}
	if(!is_dir($job['dir'])){return "backup_dir does not exist: {$job['dir']}";}
	if(!is_writable($job['dir'])){return "backup_dir is not writable: {$job['dir']}";}
	if($job['require_mount'] && !backupIsMount($job['dir'])){return "backup_dir is not a mount point (backup_require_mount=1): {$job['dir']}";}
	return '';
}
//---------- begin function backupIsMount ----------
/**
* @describe true when a directory is a mount point (its device differs from its parent's)
* @param dir string
* @return boolean
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupIsMount($dir){
	$real=realpath($dir);
	if($real===false){return false;}
	$parent=dirname($real);
	if($parent===$real){return true;}
	$a=@stat($real);$b=@stat($parent);
	if(!$a || !$b){return false;}
	return $a['dev']!==$b['dev'];
}
//---------- begin function backupFiles ----------
/**
* @describe list a job's existing backups, newest first
* @param job array
* @return array of array(Y-m-d string, full path)
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupFiles($job){
	$out=array();
	$list=@scandir($job['dir']);
	if(!is_array($list)){return $out;}
	$rx='/^'.preg_quote($job['prefix'],'/').'_(\d{4}-\d{2}-\d{2})\.'.preg_quote($job['ext'],'/').'$/';
	foreach($list as $fn){
		if(preg_match($rx,$fn,$m)){$out[]=array($m[1],$job['dir'].'/'.$fn);}
	}
	usort($out,function($a,$b){return strcmp($b[0],$a[0]);});
	return $out;
}
//---------- begin function backupIsDue ----------
/**
* @describe true when a job should run now: its time of day has passed, its frequency has elapsed and it is not in the 1 hour retry back-off
* @param job array
* @param force boolean ignore schedule, time and back-off
* @return boolean
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupIsDue($job,$force=false){
	if($force){return true;}
	if(date('H:i') < $job['time']){return false;}
	$state=backupStateLoad();
	$sk=md5($job['key']);
	if(isset($state[$sk]['last_fail']) && time()-$state[$sk]['last_fail'] < 3600){return false;}
	if(strlen(backupDirCheck($job))){return true;}	//run it so the failure is logged and reported
	$files=backupFiles($job);
	if(!count($files)){return true;}
	$last=$files[0][0];
	$today=date('Y-m-d');
	if($job['freq']=='daily'){return $last < $today;}
	if($job['freq']=='weekly'){return (strtotime($today)-strtotime($last)) >= 7*86400;}
	return substr($last,0,7) != substr($today,0,7);	//monthly
}
//---------- begin function backupSchedulerCheck ----------
/**
* @describe called once per cron_scheduler.php process: launch a detached backup.php if any <database> backup is due. Never throws.
* @return integer 1 when a backup process was launched
* @usage $ok=backupSchedulerCheck();
*/
function backupSchedulerCheck(){
	try{
		$jobs=backupJobs();
		if(!count($jobs)){return 0;}
		if(backupLockHeld()){return 0;}
		foreach($jobs as $job){
			if(backupIsDue($job)){
				backupSpawn();
				return 1;
			}
		}
	}
	catch(Throwable $e){}
	return 0;
}
//---------- begin function backupSpawn ----------
/**
* @describe start "php backup.php databases" detached from the caller
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupSpawn(){
	$php=PHP_BINARY;
	$script=__FILE__;
	if(DIRECTORY_SEPARATOR==='\\'){
		$h=@popen('start "" /B "'.$php.'" "'.$script.'" databases >NUL 2>&1','r');
		if($h){pclose($h);}
	}
	else{
		@exec(escapeshellarg($php).' '.escapeshellarg($script).' databases >/dev/null 2>&1 &');
	}
}
//---------- begin function backupRunDatabases ----------
/**
* @describe run every due <database> backup job
* @param force boolean ignore schedule/time/back-off
* @param only string limit to the job whose name or dbname matches
* @return integer number of failed jobs
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupRunDatabases($force=false,$only=''){
	$failures=0;
	foreach(backupJobs() as $job){
		if(strlen($only) && strtolower($only)!=strtolower($job['name']) && strtolower($only)!=strtolower($job['dbname'])){continue;}
		if(!backupIsDue($job,$force)){continue;}
		if(!backupRunJob($job)){$failures++;}
	}
	return $failures;
}
//---------- begin function backupRunJob ----------
/**
* @describe back up one job: dump to a .part file, verify the gzip, rename into place, then prune. Never prunes after a failure.
* @param job array
* @return boolean
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupRunJob($job){
	$err=backupDirCheck($job);
	$part='';
	if(!strlen($err)){
		$final=$job['dir'].'/'.$job['prefix'].'_'.date('Y-m-d').'.'.$job['ext'];
		$part=$final.'.part';
		$err=backupDump($job,$part);
		if(!strlen($err)){$err=backupVerifyGzip($part);}
		if(!strlen($err)){
			if(is_file($final)){@unlink($final);}
			if(!@rename($part,$final)){$err="could not rename {$part} into place";}
		}
	}
	$sk=md5($job['key']);
	$state=backupStateLoad();
	if(strlen($err)){
		if(strlen($part) && is_file($part)){@unlink($part);}
		backupMessage("FAIL {$job['name']}: {$err}");
		$state[$sk]['last_fail']=time();
		if(!isset($state[$sk]['last_notify']) || time()-$state[$sk]['last_notify'] > 86400){
			if(backupNotify($job,$err)){$state[$sk]['last_notify']=time();}
		}
		backupStateSave($state);
		return false;
	}
	backupMessage(sprintf('OK   %s -> %s (%.1f MB)',$job['name'],basename($final),filesize($final)/1048576));
	unset($state[$sk]);
	backupStateSave($state);
	backupPrune($job);
	return true;
}
//---------- begin function backupNotify ----------
/**
* @describe email a failure notice to the job's backup_email
* @param job array
* @param err string
* @return boolean true when a message was sent
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupNotify($job,$err){
	if(!strlen($job['email']) || !function_exists('isEmail') || !isEmail($job['email']) || !function_exists('sendMail')){return false;}
	$host=function_exists('gethostname')?gethostname():'';
	$ok=sendMail(array(
		'to'=>$job['email'],
		'subject'=>"Backup FAILED: {$job['name']}",
		'message'=>"The {$job['freq']} backup of database '{$job['name']}' failed on {$host}.".PHP_EOL.PHP_EOL.$err.PHP_EOL.PHP_EOL."It will be retried hourly. See logs/backup.log."
	));
	return true;
}
//---------- begin function backupPrune ----------
/**
* @describe apply the retention policy: newest N files plus the newest file of each of the last M prior months
* @param job array
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupPrune($job){
	$policy=backupPolicy();
	list($keep_n,$keep_months)=$policy[$job['freq']];
	$files=backupFiles($job);
	$keep=array();
	foreach(array_slice($files,0,$keep_n) as $f){$keep[$f[1]]=1;}
	$seen=array();
	$current=date('Y-m');
	foreach($files as $f){
		$ym=substr($f[0],0,7);
		if($ym==$current || isset($seen[$ym])){continue;}
		if(count($seen) >= $keep_months){break;}
		$seen[$ym]=1;
		$keep[$f[1]]=1;
	}
	foreach($files as $f){
		if(!isset($keep[$f[1]])){
			if(@unlink($f[1])){backupMessage('PRUNE '.basename($f[1]));}
		}
	}
	//abandoned partial files from a crashed run
	$list=@scandir($job['dir']);
	if(is_array($list)){
		foreach($list as $fn){
			if(strpos($fn,$job['prefix'].'_')===0 && substr($fn,-5)=='.part'){
				$p=$job['dir'].'/'.$fn;
				if(is_file($p) && time()-filemtime($p) > 86400){@unlink($p);}
			}
		}
	}
}
//---------- begin function backupDump ----------
/**
* @describe dump a database into a gzip file. Secrets go through temp 0600 files, never the command line or logs.
* @param job array
* @param outfile string
* @return string empty on success, otherwise the error
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupDump($job,$outfile){
	if($job['type']=='sqlite'){return backupDumpSqlite($job,$outfile);}
	$secret='';
	$env=null;
	if($job['type']=='mysql'){
		$opt="[client]".PHP_EOL.'user="'.backupEscapeOpt($job['dbuser']).'"'.PHP_EOL.'password="'.backupEscapeOpt($job['dbpass']).'"'.PHP_EOL.'host="'.backupEscapeOpt($job['dbhost']).'"'.PHP_EOL;
		if(strlen($job['dbport'])){$opt.='port='.(int)$job['dbport'].PHP_EOL;}
		$secret=backupSecretFile($opt);
		if(!$secret){return 'could not create temp option file';}
		$cmd=array('mysqldump','--defaults-extra-file='.$secret,'--single-transaction','--routines','--triggers','--events','--max_allowed_packet=128M',$job['dbname']);
	}
	elseif($job['type']=='postgres'){
		$port=strlen($job['dbport'])?(int)$job['dbport']:5432;
		$cmd=array('pg_dump','-h',$job['dbhost'],'-p',(string)$port,'-U',$job['dbuser'],'--no-password',$job['dbname']);
		$esc=function($v){return str_replace(array('\\',':'),array('\\\\','\\:'),$v);};
		$secret=backupSecretFile($esc($job['dbhost']).':'.$port.':'.$esc($job['dbname']).':'.$esc($job['dbuser']).':'.$esc($job['dbpass']).PHP_EOL);
		if(!$secret){return 'could not create temp pgpass file';}
		$env=array_merge(getenv(),array('PGPASSFILE'=>$secret));
	}
	else{
		return "unsupported dbtype '{$job['dbtype']}'";
	}
	$err=backupRunProcess($cmd,$env,$outfile);
	@unlink($secret);
	return $err;
}
//---------- begin function backupEscapeOpt ----------
/**
* @describe escape a value for a double-quoted mysql option file entry
* @param v string
* @return string
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupEscapeOpt($v){
	return str_replace(array('\\','"'),array('\\\\','\\"'),$v);
}
//---------- begin function backupSecretFile ----------
/**
* @describe write content to a new temp file readable only by the current user
* @param content string
* @return string|false path
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupSecretFile($content){
	$f=tempnam(sys_get_temp_dir(),'wbk');
	if(!$f){return false;}
	@chmod($f,0600);
	if(file_put_contents($f,$content)===false){@unlink($f);return false;}
	return $f;
}
//---------- begin function backupRunProcess ----------
/**
* @describe run a command (no shell) and gzip its stdout into outfile
* @param cmd array command and arguments
* @param env array|null environment
* @param outfile string
* @return string empty on success, otherwise the error
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupRunProcess($cmd,$env,$outfile){
	$errfile=tempnam(sys_get_temp_dir(),'wbe');
	$spec=array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('file',$errfile,'w'));
	if(PHP_VERSION_ID < 70400){
		$cmd=implode(' ',array_map('escapeshellarg',$cmd));
	}
	$proc=@proc_open($cmd,$spec,$pipes,null,$env);
	if(!is_resource($proc)){
		@unlink($errfile);
		return 'unable to start '.(is_array($cmd)?$cmd[0]:'process').' - is it installed and on the PATH?';
	}
	fclose($pipes[0]);
	$gz=gzopen($outfile,'wb9');
	if(!$gz){
		fclose($pipes[1]);proc_terminate($proc);proc_close($proc);@unlink($errfile);
		return "could not write {$outfile}";
	}
	$bytes=0;
	while(!feof($pipes[1])){
		$chunk=fread($pipes[1],1048576);
		if($chunk===false){break;}
		if(strlen($chunk)){gzwrite($gz,$chunk);$bytes+=strlen($chunk);}
	}
	fclose($pipes[1]);
	gzclose($gz);
	$exit=-1;
	for($i=0;$i<600;$i++){
		$st=proc_get_status($proc);
		if(!$st['running']){$exit=$st['exitcode'];break;}
		usleep(100000);
	}
	proc_close($proc);
	$stderr=trim(substr((string)@file_get_contents($errfile),0,2000));
	@unlink($errfile);
	if($exit!==0){return "exit code {$exit}".(strlen($stderr)?": {$stderr}":'');}
	if($bytes==0){return 'dump produced no output'.(strlen($stderr)?": {$stderr}":'');}
	return '';
}
//---------- begin function backupDumpSqlite ----------
/**
* @describe consistent snapshot of a sqlite file via VACUUM INTO (safe while in use), then gzip it
* @param job array
* @param outfile string
* @return string empty on success, otherwise the error
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupDumpSqlite($job,$outfile){
	if(!class_exists('SQLite3')){return 'the PHP sqlite3 extension is not loaded';}
	if(!is_file($job['dbname'])){return "sqlite file not found: {$job['dbname']}";}
	$snap=$job['dir'].'/'.$job['prefix'].'_snapshot_'.getmypid().'.part';
	if(is_file($snap)){@unlink($snap);}
	try{
		$db=new SQLite3($job['dbname'],SQLITE3_OPEN_READONLY);
		$db->enableExceptions(true);
		$db->busyTimeout(30000);
		$db->exec("VACUUM INTO '".SQLite3::escapeString($snap)."'");
		$db->close();
	}
	catch(Throwable $e){
		if(is_file($snap)){@unlink($snap);}
		return 'sqlite snapshot failed: '.$e->getMessage();
	}
	$in=@fopen($snap,'rb');
	$gz=@gzopen($outfile,'wb9');
	if(!$in || !$gz){
		if($in){fclose($in);}
		@unlink($snap);
		return "could not write {$outfile}";
	}
	while(!feof($in)){
		$chunk=fread($in,1048576);
		if($chunk===false){break;}
		if(strlen($chunk)){gzwrite($gz,$chunk);}
	}
	fclose($in);
	gzclose($gz);
	@unlink($snap);
	return '';
}
//---------- begin function backupVerifyGzip ----------
/**
* @describe read a gzip file end to end to prove it is intact and non-empty
* @param file string
* @return string empty when ok, otherwise the error
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupVerifyGzip($file){
	$gz=@gzopen($file,'rb');
	if(!$gz){return 'could not reopen backup to verify it';}
	$n=0;
	while(!gzeof($gz)){
		$d=@gzread($gz,1048576);
		if($d===false){gzclose($gz);return 'gzip verification failed - backup is corrupt';}
		$n+=strlen($d);
	}
	gzclose($gz);
	return $n>0?'':'backup verified empty';
}
//---------- begin function backupLegacy ----------
/**
* @describe legacy mode: back up every site (hostname) block in config.xml that has backup="1" - dump, folder tarballs, retention by age, optional email
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupLegacy(){
	global $CONFIG;
	global $ALLCONFIG;
	$backupfile=dirname(__FILE__).'/backup.last';
	if(is_file($backupfile)){
		unlink($backupfile);
	}
	foreach($ALLCONFIG as $site=>$host){
		$sendopts=array();
		$filenames=array();
		if(!isset($host['backup'])){continue;}
		if(!in_array($host['backup'],array(1,'true'))){continue;}
		//check for timezone
		if(isset($host['timezone'])){
			@date_default_timezone_set($host['timezone']);
		}
		//check for backup_email_days
		$cday=date('d');
		if(isset($host['backup_email_days'])){
			$found=0;
			$days=preg_split('/\,/',$host['backup_email_days']);
			foreach($days as $day){
				if((int)$day==(int)$cday){$found=1;break;}
			}
			if($found==0){continue;}
		}
		$CONFIG=$host;
		$cdate=date('Ymd_his');
		$ok=backupMessage("*** Begin backup for {$site} ***");
		if(!isset($host['backup_dir'])){
			$host['backup_dir']=getWasqlPath('sh/backup');
		}
		if(!is_dir($host['backup_dir'])){
			$ok=buildDir($host['backup_dir'],0777,true);
		}
		//same dump code as the <database> mode - the password never touches a command line or the log
		$jobs=array(
			'name'=>$site,
			'type'=>'mysql',
			'dbtype'=>isset($host['dbtype'])?$host['dbtype']:'mysql',
			'dir'=>$host['backup_dir'],
			'prefix'=>$host['dbname'],
			'dbname'=>$host['dbname'],
			'dbhost'=>isset($host['dbhost'])?$host['dbhost']:'localhost',
			'dbport'=>isset($host['dbport'])?$host['dbport']:'',
			'dbuser'=>isset($host['dbuser'])?$host['dbuser']:'',
			'dbpass'=>isset($host['dbpass'])?$host['dbpass']:''
		);
		$dbtype=strtolower($jobs['dbtype']);
		if(strpos($dbtype,'postgres')===0){$jobs['type']='postgres';}
		elseif(strpos($dbtype,'sqlite')===0){$jobs['type']='sqlite';}
		$jobs['ext']=$jobs['type']=='sqlite'?'sqlite.gz':'sql.gz';
		$filename="{$host['dbname']}_{$cdate}.{$jobs['ext']}";
		if($jobs['type']=='sqlite'){$filename=preg_replace('/[^A-Za-z0-9_.-]+/','_',$site)."_{$cdate}.{$jobs['ext']}";}
		$outfile="{$host['backup_dir']}/{$filename}";
		$ok=backupMessage("dumping {$jobs['type']} database {$host['dbname']} to {$filename}");
		$err=backupDump($jobs,$outfile);
		if(strlen($err)){
			$ok=backupMessage("FAIL {$site}: {$err}");
			if(is_file($outfile)){@unlink($outfile);}
		}
		if(is_file($outfile)){
			file_put_contents($backupfile,$filename.PHP_EOL,FILE_APPEND);
			if(isset($host['backup_email']) && isEmail($host['backup_email'])){
				$sendopts['attach']=array($outfile);
				$filenames[]=$filename;
			}
		}
		//folders?
		$folders=array();
		foreach($host as $k=>$v){
			if(stringBeginsWith($k,'backup_folder') && !stringEndsWith($k,'_exts')){
				$cdirs=preg_split('/[\,\;]+/',$v);
				foreach($cdirs as $cdir){
					if(is_dir($cdir) && !in_array($cdir,$folders)){
						$folders[]=$cdir;
						$filename=str_replace('/',' ',$cdir);
						$filename=trim($filename);
						$filename=str_replace(' ','_',$filename);
						$filename.="_{$cdate}.tar.gz";
						$outfile="{$host['backup_dir']}/{$filename}";
						if(isset($host["{$k}_exts"])){
							//find {$cdir} -name "*.php" -o -name "*.html" | tar -cf my_archive -T -
							$exts=preg_split('/[;,\ ]+/',$host["{$k}_exts"]);
							$parts=array();
							foreach($exts as $i=>$ext){
								//use -o -name [pattern] for each new condition
								if($i==0){
									$parts[]="-name *.{$ext}";
								}
								else{
									$parts[]="-o -name *.{$ext}";
								}
							}
							$partstr=implode(' ',$parts);
							$cmd="find {$cdir} {$partstr} | tar -czf {$outfile} -T -";
						}
						else{
							$cmd="tar -czf {$outfile} {$cdir}/";
						}
						$ok=backupMessage($cmd);
						$out=cmdResults($cmd);
						if(is_file($outfile)){
							file_put_contents($backupfile,$filename.PHP_EOL,FILE_APPEND);
							if(isset($host['backup_email']) && isEmail($host['backup_email'])){
								$sendopts['attach'][]=$outfile;
							}
						}
					}
				}
			}
		}
		//cleanup
		if(!isset($host['backup_retain_days'])){$host['backup_retain_days']=5;}
		$days=(int)$host['backup_retain_days'];
		if($days < 1){$days=1;}
		$ok=cleanupDirectory($host['backup_dir'],$days);
		//email
		if(isset($host['backup_email']) && isEmail($host['backup_email'])){
			$send=1;
			$sendopts['to']=$host['backup_email'];
			if(isset($host['backup_days'])){
				$cday=date('j');
				$hdays=preg_split('/\,/',$host['backup_days']);
				if(!in_array($cday,$hdays)){$send=0;}
			}
			if($send==1){
				if(!isset($host['backup_email_subject'])){
					$host['backup_email_subject']="Backup for {$site}";
				}
				$sendopts['subject']=$host['backup_email_subject'];
				$sendopts['message']="Files Attached:".PHP_EOL.implode(PHP_EOL,$filenames);
				$ok=sendMail($sendopts);
				$ok=backupMessage("sendmail - {$ok}");
			}
		}
		$ok=backupMessage("*** End backup for {$site} ***");
	}
}
?>
