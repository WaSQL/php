<?php
//------------ getallheaders replacement for PHP-FPM ---------------------------------------
/**
* @exclude  - this function is excluded from the manual
* 
* Functions to move to wacss.js
		pagingAddFilters
		pagingSetProcessing
		pagingSetOffset
		pagingAddFilter
		pagingBulkEdit
		pagingExport
		pagingClearFilters
		DONE: removeId
		DONE:checkAllElements
		colorSelector
		DONE:simulateEvent
		formShowPassword
		formRecorderAudio
		formRecorderAudioControl
		formSetMultiSelectStatus
		formSetFrequency
		formSetFrequencyDisplay
		DONE:setStarRating
		w_shareButton
		setCustomValidity
		ajaxExpand
		expand
		filemanagerEdit
		DONE: imagePreview
		ajaxEditField
* 
*/
if (!function_exists('getallheaders')) {
	/* @exclude  - this function is excluded from the manual */
    function getallheaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
    }
}
function commonConvert2Python($fname='common'){
	$wpath=getWasqlPath();
	switch(strtolower($fname)){
		case 'common':
			$phplines=file("{$wpath}/php/common.php");
			$pylines=file("{$wpath}/python/common.py");
			$strip='common';
		break;
		case 'database':
		case 'db':
			$phplines=file("{$wpath}/php/database.php");
			$pylines=file("{$wpath}/python/db.py");
			$strip='db';
		break;
		case 'ctree':
			$phplines=file("{$wpath}/php/extras/databases/ctree.php");
			$pylines=file("{$wpath}/python/ctreedb.py");
			$strip='ctree';
		break;
		case 'postgres':
		case 'pg':
			$phplines=file("{$wpath}/php/extras/databases/postgresql.php");
			$pylines=file("{$wpath}/python/postgresdb.py");
			$strip='postgresql';
		break;
		case 'hana':
			$phplines=file("{$wpath}/php/extras/databases/hana.php");
			$pylines=file("{$wpath}/python/hanadb.py");
			$strip='hana';
		break;
		case 'msaccess':
		case 'access':
			$phplines=file("{$wpath}/php/extras/databases/msaccess.php");
			$pylines=file("{$wpath}/python/msaccessdb.py");
			$strip='msaccess';
		break;
		case 'mscsv':
		case 'csv':
			$phplines=file("{$wpath}/php/extras/databases/mscsv.php");
			$pylines=file("{$wpath}/python/mscsvdb.py");
			$strip='mscsv';
		break;
		case 'msexcel':
		case 'excel':
			$phplines=file("{$wpath}/php/extras/databases/msexcel.php");
			$pylines=file("{$wpath}/python/msexceldb.py");
			$strip='msexcel';
		break;
		case 'sqlsrv':
		case 'mssql':
			$phplines=file("{$wpath}/php/extras/databases/mssql.php");
			$pylines=file("{$wpath}/python/mssqldb.py");
			$strip='mssql';
		break;
		case 'mysql':
			$phplines=file("{$wpath}/php/extras/databases/mysql.php");
			$pylines=file("{$wpath}/python/mysqldb.py");
			$strip='mysql';
		break;
		case 'snowflake':
			$phplines=file("{$wpath}/php/extras/databases/snowflake.php");
			$pylines=file("{$wpath}/python/snowflakedb.py");
			$strip='snowflake';
		break;
		case 'sqlite':
			$phplines=file("{$wpath}/php/extras/databases/sqlite.php");
			$pylines=file("{$wpath}/python/sqlitedb.py");
			$strip='sqlite';
		break;
	}
	
	$funcs=array();
	foreach($phplines as $line){
		if(preg_match('/^function\ (.+?)\(/is',$line,$m)){
			$name=str_ireplace($strip,'',$m[1]);
			$name=strtolower($name);
			$funcs['php'][$name]=$m[1];
		}
	}
	foreach($pylines as $line){
		if(preg_match('/^def\ (.+?)\(/is',$line,$m)){
			$name=strtolower($m[1]);
			$funcs['python'][$name]=$m[1];
		}
	}
	foreach($funcs['php'] as $name=>$cnt){
		if(!isset($funcs['python'][$name])){
			echo "{$funcs['php'][$name]}<br>";
		}
	}
	echo "DONE";
}
//---------- begin function commonCoalesce
/**
* @describe returns the first non-null, non-blank value in a list
* @param args
* @return str
* @usage $privateToken=commonCoalesce($params['token'],$CONFIG['gitlab_token'],'');
*/
function commonCoalesce(){
	$args=func_get_args();
	foreach($args as $arg){
		if(is_null($arg)){continue;}
		if(!strlen($arg)){continue;}
		return $arg;
	}
	return null;
}
//---------- begin function convertExtendedCharacters
/**
* @describe converts extended characters to ASCII equivilent
* @param params str mixed
* @return str
* @reference https://docs.oracle.com/cd/E29584_01/webhelp/mdex_basicDev/src/rbdv_chars_mapping.html
* @usage
*	$str=convertExtendedCharacters($str);
*/
function convertExtendedCharacters($string){
	$normalizeChars = array(
		'Å'=>'A', 'Æ'=>'A','À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A','Ă'=>'A','Ā'=>'A','Ą'=>'A',
		'Ç'=>'C','Ć'=>'C','Ĉ'=>'C','Ċ'=>'C','Č'=>'C',
		'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ð'=>'E','Ē'=>'E','Ĕ'=>'E','Ė'=>'E','Ę'=>'E','Ě'=>'E',
		'Ƒ'=>'F',
		'Ğ'=>'G','Ġ'=>'G','Ģ'=>'G',
		'Ĥ'=>'H','Ħ'=>'H',
		'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I','Ĩ'=>'I','Ī'=>'I','Ĭ'=>'I','Į'=>'I','İ'=>'I','Ĳ'=>'I',
		'Ĵ'=>'J',
		'Ķ'=>'K','ĸ'=>'K',
		'Ĺ'=>'L','Ļ'=>'L','Ľ'=>'L','Ŀ'=>'L','Ł'=>'L',
		'Ñ'=>'N', 'Ń'=>'N','Ņ'=>'N','Ň'=>'N','ʼN'=>'N','Ŋ'=>'N', 
		'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O','Ŏ'=>'O','Ő'=>'O','Œ'=>'O',
		'Þ'=>'P',
		'Ŕ'=>'R','Ŗ'=>'R','Ř'=>'R',
	    'Š'=>'S', 'Ș'=>'S','Ś'=>'S','Ŝ'=>'S','Ş'=>'S','ſ'=>'S',
	    'Ț'=>'T','Ţ'=>'T','Ť'=>'T','Ŧ'=>'T',
	    'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U','Ũ'=>'U','Ū'=>'U','Ŭ'=>'U','Ů'=>'U','Ű'=>'U','Ų'=>'U',
	    'Ŵ'=>'W',
	    'Ý'=>'Y','Ÿ'=>'Y','Ŷ'=>'Y',
	    'Ž'=>'Z','Ź'=>'Z','Ż'=>'Z',

	    'å'=>'a', 'æ'=>'a','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a','ă'=>'a','ā'=>'a','ą'=>'a',
		'ç'=>'c','ć'=>'c','ĉ'=>'c','ċ'=>'c','č'=>'c',
		'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ð'=>'e','ē'=>'e','ĕ'=>'e','ė'=>'e','ę'=>'e','ě'=>'e',
		'ƒ'=>'f',
		'ğ'=>'g','ġ'=>'g','ģ'=>'g',
		'ĥ'=>'h','ħ'=>'h',
		'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i','ĩ'=>'i','ī'=>'i','ĭ'=>'i','į'=>'i','i̇'=>'i','ĳ'=>'i',
		'ĵ'=>'j',
		'ķ'=>'k','ĸ'=>'k',
		'ĺ'=>'l','ļ'=>'l','ľ'=>'l','ŀ'=>'l','ł'=>'l',
		'ñ'=>'n', 'ń'=>'n','ņ'=>'n','ň'=>'n','ŉ'=>'n','ŋ'=>'n', 
		'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o','ŏ'=>'o','ő'=>'o','œ'=>'o',
		'þ'=>'p',
		'ŕ'=>'r','ŗ'=>'r','ř'=>'r',
	    'š'=>'s', 'ș'=>'s', 'ß'=>'ss','ś'=>'s','ŝ'=>'s','ş'=>'s','ſ'=>'s',
	    'ț'=>'t','ţ'=>'t','ť'=>'t','ŧ'=>'t',
	    'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u','ũ'=>'u','ū'=>'u','ŭ'=>'u','ů'=>'u','ű'=>'u','ų'=>'u',
	    'ŵ'=>'w',
	    'ý'=>'y','ÿ'=>'y','ŷ'=>'y',
	    'ž'=>'z','ź'=>'z','ż'=>'z',  
	);
	return strtr($string, $normalizeChars);
}
//---------- begin function commonObjectInfo
/**
* @describe returns type, class, and parent_class of an object.
* @param params str mixed
* @return array
* @usage
*	$info=commonObjectInfo($obj);
*/
function commonObjectInfo($obj){
	if(!is_object($obj)){
		$info=array(
			'type'=>gettype($obj),
			'class'=>'na',
			'parent_class'=>'na'
		);
	}
	else{
		$info=array(
			'type'=>gettype($obj),
			'class'=>get_class($obj),
			'parent_class'=>get_parent_class($obj)
		);
	}
	
	return $info;
}
//---------- begin function commonStrlen
/**
* @describe wrapper for strlen function to handle arrays, objects, etc.
* @param params str mixed
* @return integer
* @usage
*	if(commonStrlen($x)){...}
*/
function commonStrlen($s){
	if(is_null($s)){return 0;}
	if(is_string($s) || is_numeric($s)){return strlen($s);}
	if(is_array($s) || is_object($s)){$s=encodeJson($s);}
	return strlen($s);
}
//---------- begin function commonBuildTerminal
/**
* @describe returns an HTML5 based terminal window to the server. requires websocketd path to be set in config.xml
* @param params array
*	[-shortcuts] array - an array of shortcuts with name and cmd specified for each shortcut. These show up on the right
* @usage
*	commonBuildTerminal(array('-shortcuts'=>$shortcuts));
*/
function commonBuildTerminal($opts=array()){
	$menu='terminal';
	$progpath=dirname(__FILE__);
	$menu=strtolower($menu);
	global $params;
	$params=$opts;
	if(is_file("{$progpath}/admin/{$menu}_functions.php")){
    	include_once("{$progpath}/admin/{$menu}_functions.php");
	}
	$body=getFileContents("{$progpath}/admin/{$menu}_body.htm");
	$controller=getFileContents("{$progpath}/admin/{$menu}_controller.php");
	return evalPHP(array($controller,$body));
}
/**  --- function commonLogMessage
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonLogMessage($name,$msg,$separate=0,$echo=0){
	global $CONFIG;
	global $CRONTHRU;
	$caller=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	$caller=$caller[1];
	$caller['file']=getFileName($caller['file']);
	$logpath=getWasqlPath('logs');
	$logfile="{$logpath}/{$name}.log";
	$mypid=commonGetMyPid();
	$cdate=date('Y-m-d h:i:s');
	$msg="time:{$cdate}, {$msg}".PHP_EOL;
	if($separate==1){
		$msg = PHP_EOL.$msg.PHP_EOL;
	}
	if($echo==1){
    	echo $msg;
    }
	if(!is_file($logfile) || filesize($logfile) > 5000000 ){
        setFileContents($logfile,$msg);
    }
    else{
        appendFileContents($logfile,$msg);
    }
	return 1;
}
/**  --- function commonCronCheckSchema
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonCronCheckSchema(){
	//_cron
	$cronfields=getDBFieldInfo('_cron');
	//run_now
	if(!isset($cronfields['run_now'])){
		$query="ALTER TABLE _cron ADD run_now ".databaseDataType('integer(1)')." NOT NULL Default 0";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> '_cron',
			'fieldname'		=> 'run_now',
			'inputtype'		=> 'checkbox',
			'synchronize'	=> 0,
			'tvals'			=> '1',
			'defaultval'	=> 0,
			'required'		=> 0,
			'-upsert'		=> 'inputtype,synchronize,tvals,required,defaultval'
		));
	}
	//stop_now
	if(!isset($cronfields['stop_now'])){
		$query="ALTER TABLE _cron ADD stop_now ".databaseDataType('integer(1)')." NOT NULL Default 0";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> '_cron',
			'fieldname'		=> 'stop_now',
			'inputtype'		=> 'checkbox',
			'synchronize'	=> 0,
			'defaultval'	=> 0,
			'tvals'			=> '1',
			'required'		=> 0,
			'-upsert'		=> 'inputtype,synchronize,tvals,required,defaultval'
		));
	}
	//run_as
	if(!isset($cronfields['run_as'])){
		$query="ALTER TABLE _cron ADD run_as int NOT NULL Default 0";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> '_cron',
			'fieldname'		=> 'run_as',
			'inputtype'		=> 'select',
			'required'		=> 0,
			'displayname'	=> "Run As",
			'tvals'			=> "SELECT _id FROM _users WHERE active=1 order by firstname,lastname,_id",
			'dvals'			=> "SELECT firstname,lastname FROM _users WHERE active=1 ORDER BY firstname,lastname,_id",
			'-upsert'		=> 'inputtype,width,required,displayname,tvals,dvals'
		));
	}
	//paused
	if(!isset($cronfields['paused'])){
		$query="ALTER TABLE _cron ADD paused ".databaseDataType('integer(1)')." NOT NULL Default 0";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> '_cron',
			'fieldname'		=> 'paused',
			'inputtype'		=> 'checkbox',
			'synchronize'	=> 0,
			'tvals'			=> '1',
			'editlist'		=> 1,
			'required'		=> 0,
			'-upsert'		=> 'inputtype,synchronize,tvals,editlist,required'
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
			'required'		=> 0,
			'-upsert'		=> 'inputtype,width,required'
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
			'required'		=> 1,
			'-upsert'		=> 'inputtype,width,mask,required'
		));
	}
	//cronlog_id
	if(!isset($cronfields['cronlog_id'])){
		$query="ALTER TABLE _cron ADD cronlog_id ".databaseDataType('integer')." NOT NULL Default 0;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'cronlog_id',
			'inputtype'		=> 'text',
			'width'			=> 100,
			'mask'			=> 'integer',
			'required'		=> 0,
			'-upsert'		=> 'inputtype,width,mask,required'
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
			'required'		=> 1,
			'-upsert'		=> 'inputtype,width,mask,required',
		));
	}
	//frequency_max
	if(!isset($cronfields['frequency_max'])){
		$query="ALTER TABLE _cron ADD frequency_max varchar(25) NULL";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'frequency_max',
			'inputtype'		=> 'select',
			'displayname'	=> "Frequency Max",
			'tvals'			=> "minute\r\nhourly\r\ndaily\r\nweekly\r\nmonthly\r\nquarterly\r\nyearly",
			'dvals'			=> "Once per Minute\r\nOnce Per Hour\r\nOnce Per Day\r\nOnce Per Week\r\nOnce Per Month\r\nOnce Per Quarter\r\nOnce Per Year",
			'-upsert'		=> 'tvals,dvals,inputtype,displayname',
		));
	}
	return true;
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonCronCleanup(){
	return;
	loadExtras('system');
	global $CONFIG;
	//look for killfiles
	$path=getWaSQLPath('php/temp');
	$files=listFilesEx($path,array('ext'=>'txt','name'=>"{$CONFIG['name']}_cronkill"));
	$killids=array();
	foreach($files as $file){
		if(preg_match('/cronkill\_([0-9]+)\.txt/i',$file['name'],$m)){
			$killids[]=$m[1];
		}
	}
	if(count($killids)){
		$killidstr=implode(',',$killids);
		$precs=getDBRecords(array(
			'-table'	=> '_cron',
			'-where'	=> "_id in ({$killidstr})",
			'-fields'	=> '_id,cron_pid'
		));
		if(isset($precs[0])){
			foreach($precs as $prec){
				$logfile="{$path}/{$CONFIG['name']}_cron_log_{$prec['_id']}.txt";
				$killfile="{$path}/{$CONFIG['name']}_cronkill_{$prec['_id']}.txt";
				if($prec['cron_pid'] != 0 && is_file($killfile)){
					if(isWindows()){
						$cmd="taskkill /F /PID {$prec['cron_pid']}";
					}
					else{
						$cmd="kill -9 {$prec['cron_pid']}";	
					}
					$ok=cmdResults($cmd);
				}
				usleep(250);
				unlink($killfile);
				unlink($logfile);
			}
		}
	}
	
	$precs=getDBRecords(array(
		'-table'	=> '_cron',
		'-where'	=> "running=1 or cron_pid != 0",
		'-fields'	=> '_id,cron_pid'
	));
	if(isset($precs[0])){
		$pids=array();
		foreach($precs as $prec){
			$pids[$prec['cron_pid']]=$prec['_id'];
		}
		if(count($pids)){
			$irecs=systemGetPidInfo($pids);
			if(isset($irecs[0])){
				foreach($irecs as $irec){
					unset($pids[$irec['pid']]);
				}
			}
		}
		if(count($pids)){
			$idstr=implode(',',array_values($pids));
			$ok=editDBRecord(array(
				'-table'=>'_cron',
				'-where'=>"cron_pid = 0 or _id in ({$idstr})",
				'running'	=> 0
			));
		}
	}
}
//---------- begin function commonCronError
/**
* @describe records an error on this cron run
* @param msg string  - error message to record
* @param [email] string  - if email is passed in then it also pauses the cron  - email to notify of the pause
* @param [params] additional parameters to include in the message.
* @return ok boolean
* @usage 
*	if(!$success){$ok=commonCronError($msg);}
*	if(!$success){$ok=commonCronError($msg,'bob@mysite.com');}
*/
function commonCronError($err,$email='',$params=array()){
	$cron=commonCronGetCronByPid();
	if(!isset($cron['_id']) || !isNum($cron['_id'])){
		return false;
	}
	$ok=editDBRecordById('_cron',$cron['_id'],array('paused'=>1,'stop_now'=>1,'run_error'=>$err));
	if(isEmail($email)){
		$ccp=commonCronPause($email,$params);
	}
	return 1;
}
/**  --- function commonCronLogCheckSchema
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonCronLogCheckSchema(){
	$cronfields=getDBFieldInfo('_cron_log');
	//run_now
	if(!isset($cronfields['delete_me'])){
		$query="ALTER TABLE _cron_log ADD delete_me ".databaseDataType('integer(1)')." NOT NULL Default 0";
		$ok=executeSQL($query);
		$ok=addDBIndex(array('-table'=>'_cron_log','-fields'=>"delete_me"));
	}
}
//---------- begin function commonCronLogInit
/**
* @describe initializes the cron log and returns the cronlog_id
* @return id integer
* @usage 
*	$ok=commonCronLogInit();
*/
function commonCronLogInit(){
	$cron=commonCronGetCronByPid();
	if(!isset($cron['_id'])){return false;}
	if(isset($cron['cronlog_id']) &&  isNum($cron['cronlog_id'])){
		return false;
	}	
	/* New Log method - initialize run_result */
	$header=array(
		'timestamp'=>getDBTime(),
		'cron_name'=>$cron['name'],
		'cron_id'=>$cron['_id'],
		'user_id'=>0,
		'cron_pid'=>$cron['cron_pid']
	);
	$cronlog_id=addDBRecord(array(
		'-table'=>'_cron_log',
		'cron_id'=>$cron['_id'],
		'delete_me'=>0,
		'header'=>encodeJson($header)
	));
	if(isNum($cronlog_id)){
		$ok=editDBRecordById('_cron',$cron['_id'],array('cronlog_id'=>$cronlog_id));
	}
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonCronLogDelete(){
	$cron=commonCronGetCronByPid();
	if(!isset($cron['_id'])){return false;}
	if(!isset($cron['cronlog_id']) &&  !isNum($cron['cronlog_id'])){
		return false;
	}
	$ok=editDBRecordById('_cron_log',$cron['cronlog_id'],array('delete_me'=>1));
	return true;
}
//---------- begin function commonCronLog
/**
* @describe records an error on this cron run
* @param msg string  - error message to record
* @return ok boolean
* @usage 
*	$ok=commonCronLog($msg);
*	$ok=commonCronLog($PASSTHRU);
*/
function commonCronLog($msg,$echomsg=1){
	if(!is_string($msg) && !isNum($msg)){
		$msg=encodeJson($msg);
	}
	$current_time=getDBTime();
	$cron=commonCronGetCronByPid();
	if(!isset($cron['_id'])){
		echo "{$current_time}:{$msg}".PHP_EOL;
		return false;
	}
	//echo "commonCronLog".printValue($cron);exit;
	if(isset($cron['stop_now']) && $cron['stop_now']==1){
		$editopts=array();
		$editopts['run_result'].="processed stop_now action".PHP_EOL;
		$editopts['stop_now']=0;
		$editopts['running']=0;
		$editopts['cron_pid']=0;
		$editopts['cronlog_id']=0;
		$run_memory=memory_get_usage();
		$editopts['run_memory']=$run_memory;
		$editopts['run_length']=microtime(true)-$CRONTHRU['start'];
		$ok=editDBRecordById('_cron',$cron['_id'],$editopts);
		exit;
	}
	if(!isset($cron['cronlog_id']) || !isNum($cron['cronlog_id'])){
		if($echomsg==1){
			echo "{$current_time}:{$msg}".PHP_EOL;
		}
		return false;
	}	
	$cronlog=getDBRecord(array(
		'-table'=>'_cron_log',
		'_id'=>$cron['cronlog_id'],
		'-fields'=>'_id,log',
		'-nocache'=>1
	));
	if(!isset($cronlog['_id'])){
		if($echomsg==1){
			echo "{$current_time}:{$msg}".PHP_EOL;
		}
		return false;
	}
	$log=decodeJson($cronlog['log']);
	if(!is_array($log)){$log=[];}
	//get the current time from the DB so we are not reliant on timezone
	
	$log[]=array(
		'timestamp'=>$current_time,
		'message'=>$msg
	);
	$ok=editDBRecordById('_cron_log',$cronlog['_id'],array('log'=>encodeJson($log)));
	if($echomsg==1){
		echo "{$current_time}:{$msg}".PHP_EOL;
	}
	return true;
}
function commonGetMyPid(){
	global $CRONTHRU;
	if(isset($CRONTHRU['pid']) && isNum($CRONTHRU['pid'])){
		return (integer)$CRONTHRU['pid'];
	}
	else{
		return getmypid();
	}
}
function commonCronGetCronByPid(){
	$cron_pid=commonGetMyPid();
	
	return getDBRecord(array(
		'-table'=>'_cron',
		'-where'=>"cron_pid={$cron_pid}",
		'-fields'=>'_id,run_cmd,cron_pid,cronlog_id,stop_now',
		'-nocache'=>1
	));
}
//---------- begin function commonCronCheckStopNow
/**
* @describe checks for stop_now=1 and exits processs
* @return n/a
* @usage 
*	$ok=commonCronStopCheck();
*/
function commonCronCheckStopNow($cron=array()){
	if(!isset($cron['_id'])){
		$cron=commonCronGetCronByPid();
	}
	if(!isset($cron['stop_now'])){
		return false;
	}
	if($cron['stop_now']==1){
		$editopts=array();
		$editopts['run_result'].="processed stop_now action".PHP_EOL;
		$editopts['stop_now']=0;
		$editopts['running']=0;
		$editopts['cron_pid']=0;
		$run_memory=memory_get_usage();
		$editopts['run_memory']=$run_memory;
		$editopts['run_length']=microtime(true)-$CRONTHRU['start'];
		$ok=editDBRecordById('_cron',$rec['_id'],$editopts);
		exit;
	}
}
//---------- begin function commonCronPause
/**
* @describe sets pause to 1 on the cron id
* @param email string  - email to notify of the pause
* @param [params] additional parameters to include in the message.
* @return ok boolean
* @usage 
*	if(!$success){$ok=commonCronPause();}
*	if(!$success){$ok=commonCronPause('bob@mysite.com');}
*/
function commonCronPause($email='',$params=array()){
	$rec=commonCronGetCronByPid();
	if(!isset($rec['_id'])){
		$err="commonCronPause error: No cron found";
		debugValue($err);
		return $err;
	}
	$ok=editDBRecordById('_cron',$rec['_id'],array('paused'=>1,'stop_now'=>1));
	
	if(isEmail($email)){
		if(isset($params['subject'])){
			$subject=$params['subject'];
		}
		else{
			$subject="Cron Paused - %name%";
		}
		if(isset($params['message'])){
			$message=$params['message'];
		}
		else{
			$message.='<h3>Cron Paused: You will need to un-pause this cron before it will run again.</h3>'.PHP_EOL;
			$message.='<table border="1" style="border-collapse:collapse;border:1px solid #000;">'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Group</th><td style="text-align:left;padding:3px 5px;">%groupname%</td></tr>'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Name</th><td style="text-align:left;padding:3px 5px;">%name%</td></tr>'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Run Cmd</th><td style="text-align:left;padding:3px 5px;">%run_cmd%</td></tr>'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Run Date</th><td style="text-align:left;padding:3px 5px;">%run_date%</td></tr>'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Error</th><td style="text-align:left;padding:3px 5px;">%error%</td></tr>'.PHP_EOL;
			$message.='</table>'.PHP_EOL;
			$message.='<h4>Run Result</h4>'.PHP_EOL;
			$message.='<div style="border:1px solid #ccc;border-radius:5px;background-color:#f0f0f0;padding:5px;display:block;font-family:monospace;unicode-bidi:embed;white-space:pre-wrap;">%run_result%</div>'.PHP_EOL;
		}
		foreach($rec as $k=>$v){
			$subject=str_replace("%{$k}%",$v,$subject);
			$message=str_replace("%{$k}%",$v,$message);
		}
		foreach($params as $k=>$v){
			$subject=str_replace("%{$k}%",$v,$subject);
			$message=str_replace("%{$k}%",$v,$message);
		}
		$sendopts=array(
			'to'=>$email,
			'subject'=>$subject,
			'message'=>$message
		);
		if(isset($params['from']) && isEmail($params['from'])){
			$sendopts['from']=$params['from'];
		}
		$ok=sendMail($sendopts);
	}
	return $ok;
}

//---------- begin function commonCronPauseGroup
/**
* @describe sets pause to 1 for a group
* @param group string - groupname in cron table
* @param email string  - email to notify of the pause
* @param [params] additional parameters to include in the message.
* @return ok boolean
* @usage 
*	if(!$success){$ok=commonCronPauseGroup('weekly');}
*	if(!$success){$ok=commonCronPauseGroup('weekly','bob@mysite.com');}
*/
function commonCronPauseGroup($group,$email='',$params=array()){
	$editopts=array(
		'-table'	=> '_cron',
		'-where'	=> "groupname='{$group}'",
		'paused'	=> 1,
	);
	$ok=editDBRecord($editopts);
	if(isEmail($email)){
		if(isset($params['subject'])){
			$subject=$params['subject'];
		}
		else{
			$subject="Cron Group Paused - {$group}";
		}
		if(isset($params['message'])){
			$message=$params['message'];
		}
		else{
			$message.='<h3>Cron Paused: You will need to un-pause this cron before it will run again.</h3>'.PHP_EOL;
			$message.='<table border="1" style="border-collapse:collapse;border:1px solid #000;">'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Group</th><td style="text-align:left;padding:3px 5px;">%groupname%</td></tr>'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Name</th><td style="text-align:left;padding:3px 5px;">%name%</td></tr>'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Run Cmd</th><td style="text-align:left;padding:3px 5px;">%run_cmd%</td></tr>'.PHP_EOL;
			$message.='<tr><th style="text-align:left;padding:3px 5px;">Run Date</th><td style="text-align:left;padding:3px 5px;">%run_date%</td></tr>'.PHP_EOL;
			$message.='</table>'.PHP_EOL;
			$message.='<h4>Run Result</h4>'.PHP_EOL;
			$message.='<div style="border:1px solid #ccc;border-radius:5px;background-color:#f0f0f0;padding:5px;display:block;font-family:monospace;unicode-bidi:embed;white-space:pre-wrap;">%run_result%</div>'.PHP_EOL;
		}
		foreach($params as $k=>$v){
			$subject=str_replace("%{$k}%",$v,$subject);
			$message=str_replace("%{$k}%",$v,$message);
		}
		$sendopts=array(
			'to'=>$email,
			'subject'=>$subject,
			'message'=>$message
		);
		if(isset($params['from']) && isEmail($params['from'])){
			$sendopts['from']=$params['from'];
		}
		$ok=sendMail($sendopts);
	}
	return $ok;
}
//---------- begin function commonCronRunNow
/**
* @describe sets cron(s) to run now
* @param crons mixed - id, array of ids, comma separated list of ids, name, comma separated list of names, or array of names
* @return bool boolean - 1 or 0 based on if it succeeded
* @usage 
*	$cnt=commonCronRunNow(1);
* 	$cnt=commonCronRunNow(array(1,4));
* 	$cnt=commonCronRunNow('1,5');
* 	$cnt=commonCronRunNow('cron_one');
* 	$cnt=commonCronRunNow(array('cron_one','cron_two'));
* 	$cnt=commonCronRunNow('cron_one,cron_two');
*/
function commonCronRunNow($crons){
	if(!is_array($crons)){
		$crons=preg_split('/\,/',$crons);
	}
	if(!count($crons)){return 0;}
	$ids=array();
	$names=array();
	foreach($crons as $cron){
		$cron=trim($cron);
		if(!strlen($cron)){continue;}
		if(isNum($cron)){$ids[$cron]=1;}
		else{$names[$cron]=1;}
	}
	$ors=array();
	if(count($ids)){
		$idstr=implode(',',array_keys($ids));
		$ors[]="_id in ({$idstr})";
	}
	if(count($names)){
		$namestr=implode("','",array_keys($names));
		$ors[]="name in ('{$namestr}')";
	}
	if(!count($ors)){return 0;}
	return editDBRecord(array(
		'-table'=>'_cron',
		'-where'=>implode(' or ',$ors),
		'run_now'=>1
	));
}
//---------- begin function commonCronUnpause
/**
* @describe sets pause to 0 on the cron id
* @return ok boolean
* @usage 
*	$ok=commonCronUnpause();
*/
function commonCronUnpause(){
	$rec=commonCronGetCronByPid();
	if(!isset($rec['_id'])){
		$err="commonCronUnpause error: No cron found";
		debugValue($err);
		return $err;
	}
	$ok=editDBRecordById('_cron',$rec['_id'],array('paused'=>0));
	return $ok;
}
//---------- begin function commonCronUnpauseGroup
/**
* @describe sets pause to 0 on the group
* @param group string - name of group to unpause
* @return ok boolean
* @usage 
*	$ok=commonCronUnpauseGroup('weekly');
*/
function commonCronUnpauseGroup($group){
	$editopts=array(
		'-table'	=> '_cron',
		'-where'	=> "groupname='{$group}'",
		'paused'	=> 0,
	);
	$ok=editDBRecord($editopts);
	return $ok;
}
//---------- begin function commonFormatPhone
/**
* @describe formats a phone number
* @param string phone number
* @return string - formatted phone number (xxx) xxx-xxxx
* @usage commonFormatPhone('8014584741');
*/
function commonFormatPhone($phone) {
	// note: making sure we have something
	if(!isset($phone[3])) { return ''; }
	// note: strip out everything but numbers 
	$phone = preg_replace("/[^0-9]/", "", $phone);
	$length = strlen($phone);
	switch($length) {
		case 7:
			return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
		break;
		case 10:
			return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
		break;
		case 11:
			return preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1($2) $3-$4", $phone);
		break;
		default:
			return $phone;
		break;
	}
}

//---------- begin function commonSearchFiltersForm
/**
* @describe returns an HTML search/filters form
* @param params array
*	[-form{class|style|id|...}] string - sets specified attribute on the form
*	[-filters] array or string - filter sets of field-oper-value in an array or comma separated. i.e. name-ct-bob
*	[-limit] integer - number of records to show
*	[-navonly] integer - 1=only show navigation buttons, not search
*	[-simplesearch] str - search field - shows simple search bar
*	[-searchview] htm - custom view to use for search.  Possible view fields:  field, oper, value, search, add, export, bulkedit, first, prev, stats, next, last
*	[-offset] integer - number to start with - defaults to 0
*	[-total] integer - number of total records - required to show pagination buttons
*	['-formname'] - formname. defaults to searchfiltersform
*	[-quickfilters] - creates quickfilter button name=>string pairs where string is field oper val, semicolon separate pairs. Can also be an array with the following values: icon,name,filter,data-,onclick,class - or any other attribute you want to be included in the button
*	[-quickfilters_class] - set quickfilter button class
*	[-search_select_class]  - sets the class for the select fields in the search form
*	[-search_input_class]  - sets the class for the input fields in the search form
*	[-search_button_class]  - sets the class for the button fields in the search form
* @return string - html table to display
* @usage
*	commonSearchFiltersForm(array('-table'=>'notes'));
*/
function commonSearchFiltersForm($params=array()){
	global $PAGE;
	if(empty($params['-formname'])){
		$params['-formname']='searchfiltersform';
	}
	$params['class']='browser-default';
	//beginning Form tag
	$rtn = '<form method="post"';
	//add any attributes pass in with -form
	$atts=array();
	foreach($params as $k=>$v){
		if(preg_match('/^-form(.+)$/',$k,$m)){
			$atts[$m[1]]=$v;
		}
	}
	//action override?
	if(isset($params['-action'])){
		$atts['action']=$params['-action'];
	}
	if(!isset($atts['action'])){
		$atts['action']="/{$PAGE['name']}";
	}
	//onsubmit override?
	if(isset($params['-onsubmit'])){
		$atts['onsubmit']=$params['-onsubmit'];
	}
	if(!isset($atts['onsubmit'])){
		$atts['onsubmit']="return pagingSubmit(this);";
	}
	$rtn .= setTagAttributes($atts);
	$rtn .= '>'.PHP_EOL;
	if(empty($params['-offset'])){
		$params['-offset']=!empty($_REQUEST['filter_offset'])?$_REQUEST['filter_offset']:0;
	}
	$rtn .= '<div style="display:none;">'.PHP_EOL;
	$rtn .= '	<input type="hidden" name="filter_offset" value="'.$params['-offset'].'" >'.PHP_EOL;
	if(isset($params['-simplesearch']) || isset($params['-navonly'])){
		$rtn .= '	<input type="hidden" name="filter_order" value="'.$params['-order'].'" >'.PHP_EOL;
	}
	if(isset($params['-filters'])){
		$filters=is_array($params['-filters'])?implode("\r\n",$params['-filters']):$params['-filters'];
	}
	else{$filters='';}
	$rtn .= '	<textarea name="_filters">'.$filters.'</textarea>'.PHP_EOL;
	if(isset($params['-bulkedit'])){
		$rtn .= '	<input type="hidden" name="filter_bulkedit" value="" >'.PHP_EOL;
	}
	if(isset($params['-export'])){
		$export_params=$params;
		if(isset($export_params['-table'])){
			unset($export_params['-list']);
		}
		if(isset($export_params['-bulkedit'])){
			unset($export_params['-bulkedit']);
		}
		if(isset($export_params['-searchfields'])){
			unset($export_params['-searchfields']);
		}
		foreach($export_params as $k=>$v){
			if(stringEndsWith($k,'_class')){unset($export_params[$k]);}
			if(stringEndsWith($k,'_style')){unset($export_params[$k]);}
			if(stringEndsWith($k,'_onclick')){unset($export_params[$k]);}
			if(stringEndsWith($k,'_image')){unset($export_params[$k]);}
		}
		if(isset($PAGE['_id'])){
			$export_params['-page_id']=$PAGE['_id'];
			$export_params['-page_name']=$PAGE['name'];
			$export_params['-page_template']=$PAGE['_template'];
		}
		$rtn .= '	<textarea name="_export_params_">'.base64_encode(json_encode($export_params,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)).'</textarea>'.PHP_EOL;
		$rtn .= '	<input type="hidden" name="_export_formname" value="'.$params['-formname'].'" >'.PHP_EOL;
		$rtn .= '	<input type="hidden" name="filter_export" value="" >'.PHP_EOL;
	}
	//other fields
	foreach($params as $k=>$v){
		if(is_array($v)){continue;}
		if(preg_match('/^\-/',$k)){continue;}
		if(preg_match('/\_(onclick|eval|href|class|style|dateage|verbosetime|displayname)$/i',$k)){continue;}
		if(preg_match('/^(class|style)$/i',$k)){continue;}
		if(is_array($params['-searchfields']) && in_array($k,$params['-searchfields'])){
			continue;
		}
		$rtn .= '	<textarea name="'.$k.'">'.$v.'</textarea>'.PHP_EOL;
	}
	$rtn .= '</div>'.PHP_EOL;
	//default class to w_form-control
	if(empty($params['class'])){$params['class']='w_form-control';}
	//if(empty($params['style'])){$params['style']='min-width:75px';}
	if(isset($params['-quickfilters'])){
		//button => filter_field filter_operator filter_value, ...
		$buttons=array();
		$quickclass=isset($params['-quickfilters_class'])?$params['-quickfilters_class']:'button btn is-info btn-primary';
		foreach($params['-quickfilters'] as $name=>$str){
			if(is_array($str)){
				if(isset($str['class'])){
					$class=$str['class'];
					unset($str['class']);
				}
				else{$class=$quickclass;}
				$btn='<button type="button" style="margin:0 4px 4px 0;" class="'.$class.'"';
				if(isset($str['filter'])){
					$btn .=' data-filter="'.$str['filter'].'"';
					unset($str['filter']);
				}
				if(isset($str['onclick'])){
					$btn .=' onclick="'.$str['onclick'].'"';
					unset($str['onclick']);
				}
				else{
					$filter=$str;
					$btn .=' onclick="pagingAddFilters(getParent(this,\'form\'),this.dataset.filter,1);"';
				}
				if(isset($str['name'])){
					$cname=$str['name'];
					unset($str['name']);
				}
				else{$cname=$name;}
				if(isset($str['icon'])){
					if(strlen($cname)){
						if(stringContains($cname,'<')){
							$cname='<span class="'.$str['icon'].'"></span> '.$cname;
						}
						else{
							$cname='<span class="'.$str['icon'].'" style="margin-right:3px;"></span> '.$cname;
						}	
					}
					else{
						$cname='<span class="'.$str['icon'].'"></span> ';
					}
					unset($str['icon']);
				}
				//add any other attributes
				if(count($str)){
					foreach($str as $strk=>$strv){
						$btn.=" {$strk}=\"{$strv}\"";
					}
				}
				$btn.='>'.$cname.'</button>';
				$buttons[]=$btn;
			}
			else{
				$class=$quickclass;
				$btn='<button type="button" style="margin:0 4px 4px 0;" class="'.$class.'"';
				if(stringBeginsWith($str,'javascript:')){
					$str=str_replace('javascript:','',$str);
					$btn .=' onclick="'.$str.'"';
				}
				else{
					$filter=$str;
					$btn .=' data-filter="'.$filter.'"';
					$btn .=' onclick="pagingAddFilters(getParent(this,\'form\'),this.dataset.filter,1);"';
				}
				if(isNum($name)){$cname='';}
				else{$cname=$name;}
				$btn.='>'.$cname.'</button>';
				$buttons[]=$btn;
			}		
		}
		$rtn .= '<div style="margin-bottom:5px;display:flex;flex-direction:row;flex-wrap:wrap;justify-content:flex-end;align-items:center;">'.implode(' ',$buttons).'</div>';
	}
	//flex wrapper
	$rtn .= '	<div class="w_flex w_flexgroup w_flexwrap">'.PHP_EOL;
	//search fields
	if(!empty($params['-searchfields'])){
		if(!is_array($params['-searchfields'])){
			$params['-searchfields']=preg_split('/\,/',$params['-searchfields']);
		}
		$vals=array();
		foreach($params['-searchfields'] as $field){
			if(isset($params["{$field}_displayname"])){$dname=$params["{$field}_displayname"];}
			elseif(isset($params["{$field}_options"]['displayname'])){$dname=$params["{$field}_options"]['displayname'];}
			else{$dname=ucwords(str_replace('_',' ',$field));}
	    	$vals[$field]=$dname;
		}
	}
	elseif(!empty($params['-table'])){
		$fields=getDBFields($params['-table'],1);
		$vals=array('*'=>'Any Field');
		foreach($fields as $field){
	    	if(isWasqlField($field) && $field != '_id'){continue;}
	    	if(isset($params["{$field}_displayname"])){$dname=$params["{$field}_displayname"];}
			elseif(isset($params["{$field}_options"]['displayname'])){$dname=$params["{$field}_options"]['displayname'];}
			else{$dname=ucwords(str_replace('_',' ',$field));}
	    	$vals[$field]=$dname;
		}
	}
	$btnclass='btn';
	if(isset($params['-search_button_class'])){
		$btnclass=$params['-search_button_class'];
	}
	//keep field and operator together (nowrap)
	if(!isset($params['-navonly'])){
		$rtn .= '	<div data-set="1" class="w_flex w_flexrow w_flexnowrap">'.PHP_EOL;
		if(!isset($params['-simplesearch'])){
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$cparams=array();
			if(isset($params['-search_select_class'])){
				$cparams['class']=$params['-search_select_class'];
			}
			if(isset($params['-formname'])){
				$cparams['-formname']=$params['-formname'];
			}
			$rtn .= buildFormSelect('filter_field',$vals,$cparams);
			$rtn .= '			</div>'.PHP_EOL;
			//operators
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$vals=array(
				'eq'	=> 'Equals',
				'ea'	=> 'Equals Any of These',
				'ct'	=> 'Contains',
				'ca'	=> 'Contains Any of These',
				'nct'	=> 'Not Contains',
				'nca'	=> 'Not Contain Any of These',
				'neq'	=> 'Not Equals',
				'nea'	=> 'Not Equals Any of These',
				'gt'	=> 'Greater Than',
				'lt'	=> 'Less Than',
				'egt'	=> 'Equals or Greater than',
				'elt'	=> 'Less than or Equals',
				'ib'	=> 'Is Blank',
				'nb'	=> 'Is Not Blank',
				'db'	=> 'Is Between (date1,date2)'
			);
			if(!empty($params['-searchopers'])){
				if(!is_array($params['-searchopers'])){
					$params['-searchopers']=preg_split('/\,/',$params['-searchopers']);
				}
				$tmp=array();
				foreach($params['-searchopers'] as $k){
					$tmp[$k]=$vals[$k];
				}
				$vals=$tmp;
			}
			$cparams=array();
			if(isset($params['-search_select_class'])){
				$cparams['class']=$params['-search_select_class'];
			}
			if(isset($params['-formname'])){
				$cparams['-formname']=$params['-formname'];
			}
			$rtn .= buildFormSelect('filter_operator',$vals,$cparams);
			$rtn .= '			</div>'.PHP_EOL;

			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$cparams=array();
			if(isset($params['-search_input_class'])){
				$cparams['class']=$params['-search_input_class'];
			}
			if(isset($params['-formname'])){
				$cparams['-formname']=$params['-formname'];
			}
			$cparams['placeholder']='value';
			//$cparams['autofocus']='autofocus';
			$rtn .= buildFormText('filter_value',$cparams);
			unset($params['autofocus']);
			$rtn .= '			</div>'.PHP_EOL;
			$rtn .= '		</div>'.PHP_EOL;
			$rtn .= '		<div data-set="2" class="w_flex w_flexrow w_flexnowrap">'.PHP_EOL;
			//order
			if(!empty($params['-showorder']) && $params['-showorder']==1){
				$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
				$vals=array();
				foreach($params['-listfields'] as $fld){
					$dname=ucwords(trim(str_replace('_',' ',$fld)));
					$vals[$fld]='Order By '.$dname;
					$vals["{$fld} desc"]='Order By '.$dname.' desc';
				}
				$cparams=array();
				if(isset($params['-search_select_class'])){
					$cparams['class']=$params['-search_select_class'];
				}
				if(isset($params['-formname'])){
					$cparams['-formname']=$params['-formname'];
				}
				$rtn .= buildFormSelect('filter_order',$vals,$cparams);
				$rtn .= '			</div>'.PHP_EOL;	
			}
			else{
				$rtn .= '	<input type="hidden" name="filter_order" value="'.$_REQUEST['filter_order'].'" >'.PHP_EOL;
			}
		}
		else{
			$rtn .='<input type="hidden" name="filter_field" value="'.$params['-simplesearch'].'" >'.PHP_EOL;
			$rtn .='<input type="hidden" name="filter_operator" value="ct" >'.PHP_EOL;
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$cparams=array();
			if(isset($params['-search_input_class'])){
				$cparams['class']=$params['-search_input_class'];
			}
			if(isset($params['-formname'])){
				$cparams['-formname']=$params['-formname'];
			}
			$cparams['placeholder']='value';
			//$cparams['autofocus']='autofocus';
			$rtn .= buildFormText('filter_value',$cparams);
			unset($params['autofocus']);
			$rtn .= '			</div>'.PHP_EOL;
		}
		//search button
		$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
		$rtn .= '				<button type="submit" id="'.$params['-formname'].'_search_button" class="'.$btnclass.'" onclick="pagingSetProcessing(this);pagingSetOffset(document.'.$params['-formname'].',0);return false;"><span class="icon-search" style="margin-right:5px;"></span> Search</button>'.PHP_EOL;
		$rtn .= '			</div>'.PHP_EOL;
		if(!isset($params['-simplesearch'])){
			//add filter
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$rtn .= '				<button type="button" class="'.$btnclass.'" title="Add Filter" onclick="pagingAddFilter(document.'.$params['-formname'].');"><span class="icon-filter-add"></span></button>'.PHP_EOL;
			$rtn .= '			</div>'.PHP_EOL;
			//bulkedit
			if(!empty($params['-bulkedit'])){
				$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
		    	$rtn .= '				<button type="button" title="Bulk Edit" class="'.$btnclass.'" onclick="pagingBulkEdit(document.'.$params['-formname'].');"><span class="icon-edit w_bold"></span></button>'.PHP_EOL;
		    	$rtn .= '			</div>'.PHP_EOL;
			}
		}
		$rtn .= '		</div>'.PHP_EOL;
	}

	//Paging buttons - first, prev, next, and last
	if(!empty($params['-total'])){
		//keep pagination buttons together (now wrapping)
		$rtn .= '	<div data-set="3" class="w_flex w_flexrow w_flexnowrap">'.PHP_EOL;
		if(empty($params['-limit'])){$params['-limit']=15;}
		$rtn .= '		<input type="hidden" name="filter_total" value="'.$params['-total'].'" >'.PHP_EOL;
		//show first if offset minus limit is not 0
		if($params['-offset']-$params['-limit'] > 0){
			$offset=0;
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$rtn .= '				<button type="button" class="'.$btnclass.'" onclick="pagingSetProcessing(this);pagingSetOffset(document.'.$params['-formname'].','.$offset.')"><span class="icon-nav-first"></span></button>'.PHP_EOL;
			$rtn .= '			</div>'.PHP_EOL;
		}
		//show prev if offset is not 0
		if($params['-offset'] > 0){
			$offset=$params['-offset']-$params['-limit'];
			if($offset < 0){$offset=0;}
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$rtn .= '				<button type="button" class="'.$btnclass.'" onclick="pagingSetProcessing(this);pagingSetOffset(document.'.$params['-formname'].','.$offset.')"><span class="icon-nav-prev"></span></button>'.PHP_EOL;
			$rtn .= '			</div>'.PHP_EOL;
		}
		$rtn .= '			<div style="margin:0 4px 4px 0;display:flex;align-items: center;justify-content: center;">'.PHP_EOL;
		$x=$params['-offset']+1;
		$y=$x+$params['-limit']-1;
		if($y > $params['-total']){$y=$params['-total'];}
		$rtn .= "				{$x}-{$y} of {$params['-total']}".PHP_EOL;
		$rtn .= '			</div>'.PHP_EOL;
		//show next if offset+limit < total
		if($params['-offset']+$params['-limit'] < $params['-total']){
			$offset=$params['-offset']+$params['-limit'];
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$rtn .= '				<button type="button" class="'.$btnclass.'" onclick="pagingSetProcessing(this);pagingSetOffset(document.'.$params['-formname'].','.$offset.')"><span class="icon-nav-next"></span></button>'.PHP_EOL;
			$rtn .= '			</div>'.PHP_EOL;
		}
		if($params['-offset']+$params['-limit'] < $params['-total']){
			$offset=$params['-total']-$params['-limit'];
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
			$rtn .= '				<button type="button" class="'.$btnclass.'" onclick="pagingSetProcessing(this);pagingSetOffset(document.'.$params['-formname'].','.$offset.')"><span class="icon-nav-last"></span></button>'.PHP_EOL;
			$rtn .= '			</div>'.PHP_EOL;
		}
		$rtn .= '		</div>'.PHP_EOL;
		if(!empty($params['-export'])){
			$rtn .= '			<div style="margin:0 4px 4px 0;">'.PHP_EOL;
	    	$rtn .= '				<button type="button" title="Export current results to CSV file" class="'.$btnclass.'" onclick="pagingExport(document.'.$params['-formname'].');"><span class="icon-export w_bold"></span></button>'.PHP_EOL;
	    	$rtn .= '			</div>'.PHP_EOL;
	    	$rtn .= '			<div style="margin:0 4px 4px 0;display:none;" id="'.$params['-formname'].'_exportbutton"></div>'.PHP_EOL;
	    	// if(!empty($params['-export_file'])){
	    	// 	$rtn .= '			<div style="margin:0 4px 4px 0;" onclick="removeDiv(this);">'.PHP_EOL;
		    // 	$rtn .= '				<a href="'.$params['-export_file'].'" style="text-decoration:none;padding-top:7px;" title="Download CSV Export" class="btn" ><span class="icon-download  w_warning w_bold w_blink"></span></a>'.PHP_EOL;
		    // 	$rtn .= '			</div>'.PHP_EOL;
	    	// }
		}
	}
	
	//end flex wrapper
	$rtn .= '	</div>'.PHP_EOL;
	//send_to_filters list
	$rtn .= '	<div id="send_to_filters" style="min-height:30px;max-height:120px;overflow:auto;">'.PHP_EOL;
	if(!empty($params['-filters'])){
        //field-oper-value
        if(is_array($params['-filters'])){$sets=$params['-filters'];}
    	else{
    		$params['-filters']=trim($params['-filters']);
    		$params['-filters']=preg_replace('/\,+$/','',$params['-filters']);
    		$sets=preg_split('/[\r\n\,]+/',$params['-filters']);
    	}
    	foreach($sets as $s=>$set){
    		if(!strlen(trim($set))){
    			unset($sets[$s]);
    			continue;
    		}
        	list($field,$oper,$val)=preg_split('/\-/',trim($set),3);
        	if($field=='null' || $val=='null' || $oper=='null' || strlen($field)==0 || strlen($oper)==0 || strlen($val)==0){
        		if(!strlen($oper) || !in_array($oper,array('ib','nb'))){continue;}
        	}
        	$dfield=$field;
			if($dfield=='*'){$dfield='Any Field';}
        	$doper=$oper;
        	$fid=$field.$oper.$val;
        	$fid=preg_replace('/[^a-zA-Z0-9\_]+/','',$fid);
        	$fid='id_'.strtolower($fid);
			$dval="'{$val}'";
			switch($oper){
	        	case 'ct': $doper='Contains';break;
	        	case 'nct': $doper='Not Contains';break;
	        	case 'ca': $doper='Contains Any of These';break;
	        	case 'nca': $doper='Not Contain Any of These';break;
				case 'eq': $doper='Equals';break;
				case 'neq': $doper='Not Equals';break;
				case 'ea': $doper='Equals Any of These';break;
				case 'nea': $doper='Not Equals Any of These';break;
				case 'gt': $doper='Greater Than';break;
				case 'lt': $doper='Less Than';break;
				case 'egt': $doper='Equals or Greater than';break;
				case 'elt': $doper='Less than or Equals';break;
				case 'ib': $doper='Is Blank';$dval='';break;
				case 'nb': $doper='Is Not Blank';$dval='';break;
			}
			$dfield=ucwords(str_replace('_',' ',$dfield));
			$dstr="{$dfield} {$doper} {$dval}";
			//$dstr='hello doob';
        	$rtn .= '<div class="w_pagingfilter" data-field="'.$field.'" data-operator="'.$oper.'" data-value="'.$val.'" id="'.$fid.'">'.$dstr.' <span class="icon-cancel w_danger w_pointer" onclick="removeId(\''.$fid.'\');"></span></div>'.PHP_EOL;
		}
		if(count($sets)){
			$rtn .= '<div id="paging_clear_filters" class="w_pagingfilter icon-erase w_big w_danger" title="Clear All Filters" onclick="pagingClearFilters(getParent(this,\'form\'));"></div>'.PHP_EOL;
		}
	}
	$rtn .= '	</div>'.PHP_EOL;
	$rtn .= '</form>'.PHP_EOL;
	return $rtn;
}
//---------- begin function getWebsiteMeta ----------
/**
* @describe gets the headers, meta, and link data from a website URL
* @param url string - website URL
* @return array key/value pairs.  title, url, headers, meta, and link
* @usage $meta=getWebsiteMeta('https://www.google.com');
*/
function getWebsiteMeta($url){
    $result = false;
    $post=postURL($url,array(
		'-method'=>'GET',
		'-follow'=>1,
		'-nossl'=>1
	));
	$contents=$post['body'];
    $meta=array('headers'=>$post['headers']);
    $meta['summary']['url']=$url;
    $p=preg_split('/\#/',$url,2);
	$meta['base_url']=$p[0];
    if(preg_match('/<title>([^>]*)<\/title>/si', $contents, $m)){
		$meta['summary']['title']=$m[1];
	}
    preg_match_all('/\<(meta|link)\ (.+?)\>/si',$contents,$m);
    foreach($m[2] as $i=>$str){
		$attr=parseHtmlTagAttributes($str);
		switch(strtolower($m[1][$i])){
			case 'link':
				$k=$attr['rel'];
				$v=$attr['href'];
				$g='link';
			break;
			case 'meta':
				if(isset($attr['name'])){$k=$attr['name'];}
				elseif(isset($attr['property'])){$k=$attr['property'];}
				elseif(isset($attr['http-equiv'])){$k=$attr['http-equiv'];}
				else{$k='';}
				$v=$attr['content'];
				$g='meta';
			break;
		}
		if(strlen($k) && $k != 'stylesheet'){
			$k=strtolower($k);
			$meta[$g][$k]=$v;
		}
	}
	//name, title, description, image

	if(!isset($meta['summary']['title']) || !strlen($meta['summary']['title'])){
		if(isset($meta['meta']['title']) && strlen($meta['meta']['title'])){$meta['title']=$meta['summary']['title']['title'];}
		elseif(isset($meta['meta']['og:title']) && strlen($meta['meta']['og:title'])){$meta['title']=$meta['summary']['title']['og:title'];}
		elseif(isset($meta['meta']['twitter:title']) && strlen($meta['meta']['twitter:title'])){$meta['summary']['title']=$meta['meta']['twitter:title'];}
	}
	//replace em dash with a dash
	$meta['summary']['title']=trim($meta['summary']['title']);
	$meta['summary']['title']=preg_replace('~\xe2\x80\x93~','-',$meta['summary']['title']);
	if(!isset($meta['summary']['name']) || !strlen($meta['summary']['name'])){
		if(isset($meta['meta']['name']) && strlen($meta['meta']['name'])){$meta['summary']['name']=$meta['meta']['name'];}
		elseif(isset($meta['meta']['og:site_name']) && strlen($meta['meta']['og:site_name'])){$meta['summary']['name']=$meta['meta']['og:site_name'];}
		elseif(isset($meta['meta']['twitter:site']) && strlen($meta['meta']['twitter:site'])){$meta['summary']['name']=$meta['meta']['twitter:site'];}
		elseif(isset($meta['summary']['title']) && strlen($meta['summary']['title'])){
			$p=preg_split('/(\-|\:|\.\.\.)/',$meta['summary']['title'],2);
			$meta['summary']['name']=trim($p[0]);
		}
		else{$meta['summary']['name']=getUniqueHost($meta['base_url']);}
	}
	if(!isset($meta['summary']['description']) || !strlen($meta['summary']['description'])){
		if(isset($meta['meta']['description']) && strlen($meta['meta']['description'])){$meta['summary']['description']=$meta['meta']['description'];}
		elseif(isset($meta['meta']['og:description']) && strlen($meta['meta']['og:description'])){$meta['summary']['description']=$meta['meta']['og:description'];}
		elseif(isset($meta['meta']['twitter:description']) && strlen($meta['meta']['twitter:description'])){$meta['summary']['description']=$meta['meta']['twitter:description'];}
		elseif(isset($meta['meta']['abstract']) && strlen($meta['meta']['abstract'])){$meta['summary']['description']=$meta['meta']['abstract'];}
	}
	if(!isset($meta['summary']['image']) || !strlen($meta['summary']['image'])){
		if(isset($meta['meta']['image']) && strlen($meta['meta']['image'])){$meta['summary']['image']=$meta['meta']['image'];}
		elseif(isset($meta['meta']['og:image']) && strlen($meta['meta']['og:image'])){$meta['summary']['image']=$meta['meta']['og:image'];}
		elseif(isset($meta['meta']['twitter:image:src']) && strlen($meta['meta']['twitter:image:src'])){$meta['summary']['image']=$meta['meta']['twitter:image:src'];}
		elseif(isset($meta['link']['apple-touch-icon']) && strlen($meta['link']['apple-touch-icon'])){$meta['summary']['image']=$meta['link']['apple-touch-icon'];}
		elseif(isset($meta['link']['apple-touch-icon-precomposed']) && strlen($meta['link']['apple-touch-icon-precomposed'])){$meta['summary']['image']=$meta['link']['apple-touch-icon-precomposed'];}
		elseif(isset($meta['meta']['msapplication-tileimage']) && strlen($meta['meta']['msapplication-tileimage'])){$meta['summary']['image']=$meta['meta']['msapplication-tileimage'];}
		elseif(isset($meta['link']['icon']) && strlen($meta['link']['icon'])){$meta['summary']['image']=$meta['link']['icon'];}
		elseif(isset($meta['link']['shortcut icon']) && strlen($meta['link']['shortcut icon'])){$meta['summary']['image']=$meta['link']['shortcut icon'];}
		else{
			//get the first src= image on the page
			preg_match_all('/src=\"(.+?)\"/si',$contents,$im);
			foreach($im[1] as $img){
				if(preg_match('/(png|jpg|gif)$/i',$img)){
					$meta['summary']['image']=$img;
					break;
				}
			}
		}
	}
	if(isset($meta['summary']['image']) && strlen($meta['summary']['image']) && !preg_match('/^(http|https|\/\/)/',$meta['summary']['image'])){
		//remove any anchors
		$meta['summary']['image']=$meta['base_url'].$meta['summary']['image'];
	}
	$meta['summary']['image']=preg_replace('/^http\:\/\//i',"[!a!]",$meta['summary']['image']);
	$meta['summary']['image']=preg_replace('/^https\:\/\//i',"[!b!]",$meta['summary']['image']);
	$meta['summary']['image']=preg_replace('/^\/\//i',"[!c!]",$meta['summary']['image']);
	$meta['summary']['image']=str_replace("//","/",$meta['summary']['image']);
	$meta['summary']['image']=str_replace("[!a!]","http://",$meta['summary']['image']);
	$meta['summary']['image']=str_replace("[!b!]","https://",$meta['summary']['image']);
	$meta['summary']['image']=str_replace("[!c!]","//",$meta['summary']['image']);

	//sort
	ksort($meta);
	foreach($meta as $k=>$r){
		if(is_array($meta[$k])){
			ksort($meta[$k]);
		}
	}
    return $meta;
}
//---------- begin function parseAttributes ----------
/**
* @describe parses an html tag attributes
* @param txt html tag string
* @return array key/value pairs for each attribute found in the html tag
* @usage $attrs=parseHtmlTagAttributes($tag);
*/
function parseHtmlTagAttributes($text) {
    $attributes = array();
    $pattern = '#(?(DEFINE)
            (?<name>[a-zA-Z][a-zA-Z0-9\-\:\_]*)
            (?<value_double>"[^"]+")
            (?<value_single>\'[^\']+\')
            (?<value_none>[^\s>]+)
            (?<value>((?&value_double)|(?&value_single)|(?&value_none)))
        )
        (?<n>(?&name))(=(?<v>(?&value)))?#xs';
    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
			$k=strtolower($match['n']);
            $attributes[$k] = isset($match['v'])?trim($match['v'], '\'"'): null;
        }
    }
    return $attributes;
}


//---------- begin function abort ----------
/**
* @describe aborts all processing and exits with an abort message
* @param obj object
*	any object (array, string, etc) you want to show in the abort message
* @return
*	prints the abort message to the screen and exits
* @usage if(!isNum($ok)){abort(array($ok,$recopts,$rec));
*/
function abort($obj,$title='',$subtitle=''){
	global $CONFIG;
	//just print the message if on command line
	if(isCLI()){
		echo removeHTML(printValue($obj));
		exit(361);
	}
	if(isset($_REQUEST['ping']) && count($_REQUEST)==1){
		if(is_string($obj)){
			$obj=preg_replace('/[\r\n\"\']+/','',$obj);
			$obj=removeHtml($obj);
		}
		else{$obj=json_encode($obj,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}
		$json=array(
			'status'=>'failed',
			'site'=>$_SERVER['HTTP_HOST'],
			'hostname'=>gethostname(),
			'error'=>$obj
		);
		//if linux add loadavg
		if(!isWindows()){
			$out=cmdResults('cat /proc/loadavg');
			$json['loadavg']=$out['stdout'];
			$out=cmdResults('cat /proc/uptime');
			$json['uptime']=$out['stdout'];
		}
		
		header("Content-Type: application/json; charset=UTF-8");
		echo json_encode($json,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT);
		exit(362);
	}

	$rtn='';
	$rtn .= '<!DOCTYPE HTML>'.PHP_EOL;
	$rtn .= '<html lang="en">'.PHP_EOL;
	$rtn .= '<head>'.PHP_EOL;
	$rtn .= '	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >'.PHP_EOL;
	$rtn .= '	<meta name="robots" content="noindex, nofollow, noarchive" >'.PHP_EOL;
	$rtn .= '	<meta name="viewport" content="width=device-width, initial-scale=1" >'.PHP_EOL;
	if(function_exists('minifyCssFile')){
		$rtn .= '	<link type="text/css" rel="stylesheet" href="'.minifyCssFile('wacss').'" >'.PHP_EOL;
		$rtn .= '	<script type="text/javascript" src="'.minifyJsFile('wacss').'"></script>'.PHP_EOL;
	}
	$rtn .= '</head>'.PHP_EOL;
	$rtn .= '<body>'.PHP_EOL;
	$headstr = '<div class="w_bigger w_bold w_dblue">'.$title.'</div>'.PHP_EOL;
	$headstr .= '<div class="w_big w_dblue">' . $subtitle . '</div>'.PHP_EOL;
	$heading=array();
	if(isset($CONFIG['logo']) && strlen($CONFIG['logo'])){
		$heading[]='<img src="'.$CONFIG['logo'].'">';
	}
	else{
		$heading[]='<img src="/wfiles/iconsets/32/wasql.png" style="padding:10px;" >';
	}
	$heading[]=$headstr;
	$rtn .= '<table class="table">'.PHP_EOL;
	$rtn .= buildTableTD($heading);
	$rtn .= buildTableEnd();
	$rtn .= '<div class="w_left w_pad">'.PHP_EOL;
	if(is_string($obj)){$rtn .= $obj;}
	else{$rtn .= printValue($obj);}
	$rtn .= '</div>'.PHP_EOL;
	$rtn .= '</body></html>'.PHP_EOL;
	echo $rtn;
	exit(363);
	}

//---------- begin function addEditForm---------------------------------------
/**
* @deprecated use addEditDBForm instead
* @exclude  - this function is deprecated and thus excluded from the manual
*/
function addEditForm($params=array()){return addEditDBForm($params);}

//---------- begin function arrayAverage--------------------------------------
/**
* @describe returns the average of values in an array
* @param arr array
*	the array you want to be split into columns
* @param decimals int 2
*	the number of decimal places to return, defaults to 2
* @return
*	a number representing the average of values in the array
* @usage
*	$arr=array(4,7,3,9,2);
*	echo arrayAverage($arr);
*	// returns 5
*/
function arrayAverage($arr=array(),$decimal=2){
	if(!is_array($arr)){return '';}
	if(!count($arr)){return '';}
	$sum = array_sum($arr);
	$num = sizeof($arr);
	return round(($sum/$num),$decimal);
	}

//---------- begin function arrayColumns--------------------------------------
/**
* @describe splits an array into a specified number of column arrays
* @param arr array
*	the array you want to be split into columns
* @param c int
*	the number of columns
* @return
*	an array of c length, each with an equal number of items from arr
* @usage
* $colsets=arrayColumns($list,3);
*/
function arrayColumns( $list, $c=2, $preserve_keys=false ) {
	if(!is_array($list)){return 'arrayColumns error: list is not an array';}
	if(!isNum($c) || $c < 1){return "arrayColumns error: invalid number of colums".printValue($c);}
	//use array_chunk instead
	$cnt=count($list);
	$x=ceil($cnt/$c);
	return array_chunk($list,$x,$preserve_keys);
	//-----------------
	$listlen = count( $list );
    $partlen = floor( $listlen / $p );
    $partrem = $listlen % $p;
    //determine number of items to put in each column
    $counts=array();
    for($x=0;$x<$p;$x++){
		$counts[$x]=$partlen;
		}
	$remcnt=0;
	for($y=0;$y<$partrem;$y++){
		for($x=0;$x<$p;$x++){
			if($remcnt==$partrem){break;}
			$remcnt++;
			$counts[$x]+=1;
			}
		}
	//populate cols array
	$cols=array();
	for($x=0;$x<$p;$x++){
		for($c=0;$c<$counts[$x];$c++){
			if(count($list)){
				$val=array_shift($list);
				$cols[$x][]=$val;
				}
			}
    	}
	return $cols;
    }

//---------- begin function arraySearchKeys--------------------------------------
/**
* @describe searches keys of an array for a value
* @param needle string
*	string to search for
* @param arr array
*	array to search in
* @return
*	returns an array where the value is now the key and the count of the times it was found is the value.
* 	<p>$ages[35]=3  would mean I found three keys called age that had a value of 35
* @usage $ages=arraySearchKeys('age',$arr);
*/
function arraySearchKeys( $needle, $arr ){
	$vals=array();
	foreach($arr as $key=>$val){
    	if($key == $needle && !is_array($val)){$vals[$val]+=1;}
    	if(is_array($val)){
			$xvals=arraySearchKeys($needle,$val);
			foreach($xvals as $xval=>$cnt){$vals[$xval]=$cnt;}
		}
    }
	return $vals;
}
//---------- begin function asciiArt ----------
/**
* @describe created asciiArt from and image
* @param file string - full path to image file
* @param [force] boolean - force a redraw
* @return string
* @usage return asciiArt($logo);
* @reference https://donatstudios.com/Damn-Simple-PHP-ASCII-Art-Generator
*/
function asciiArt($file,$force=false){
	$path=getWasqlPath();
	$temp="{$path}/php/temp";
	$cfile=sha1($file).'.ascii';
	$afile="{$temp}/{$cfile}";
	if(is_file($afile) && !$force){
    	return getFileContents($afile);
	}
	$img = imagecreatefromstring(file_get_contents($file));
	list($width, $height) = getimagesize($file);
	$scale = 20;
	$chars = array(' ', '\'', '.', ':','|', 'T',  'X', '0','#',);
	$chars = array_reverse($chars);
	$c_count = count($chars);
	$ascii='';
	for($y = 0; $y <= $height - $scale - 1; $y += $scale) {
		for($x = 0; $x <= $width - ($scale / 2) - 1; $x += ($scale / 2)) {
			$rgb = imagecolorat($img, $x, $y);
			$r = (($rgb >> 16) & 0xFF);
			$g = (($rgb >> 8) & 0xFF);
			$b = ($rgb & 0xFF);
			$sat = ($r + $g + $b) / (255 * 3);
			$ascii .= $chars[ (int)( $sat * ($c_count - 1) ) ];
		}
		$ascii .= PHP_EOL;
	}
	setFileContents($afile,$ascii);
	return $ascii;
}
//---------- begin function asciiEncode--------------------------------------
/**
* @deprecated use encodeAscii instead
* @exclude - depreciated
*/
function asciiEncode($str=''){return encodeAscii($str);}

//---------- begin function buildIECompatible ----------
/**
* @describe build X-UA-Compatible meta tag for IE
* @param version string
* @return string
* @usage return buildIECompatible();
*/
function buildIECompatible($version=0){
	if(!isset($_SERVER['REMOTE_BROWSER']) || strtolower($_SERVER['REMOTE_BROWSER']) != 'msie'){
    	return '';
	}
	if($version==0){
		$version=(integer)$_SERVER['REMOTE_BROWSER_VERSION'];
		if($version < 7){return '';}
	}
	return '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE'.$version.'" >';
	}
//---------- begin function buildChartJsData--------------------
/**
* @deprecated use chartjs tag instead
* @exclude - depreciated
*/
function buildChartJsData($recs,$params=array()){
	//if the first parameter is a string, assume it is a query
	if(!is_array($recs) || !count($recs)){
    	return 'buildChartJsData Error: no recs';
	}
	//each record set needs three fields: xval, yval, and setval
	if(!isset($recs[0]['xval']) || !isset($recs[0]['xval']) || !isset($recs[0]['setval'])){
    	return 'buildChartJsData Error: each record set needs three fields: xval, yval, and setval';
	}
	/*
		labels: xvals array
		data in datasets: yvals array
		label in datasets: set
	*/
	$data=array(
		'labels'	=> array(),
		'datasets'	=> array()
	);
	$xkeys=array();
	//group into setval groups
	$vrecs=array();
	foreach($recs as $rec){
    	$vrecs[$rec['setval']][]=$rec;
    	if(!in_array($rec['xval'],$data['labels'])){$data['labels'][]=$rec['xval'];}
	}
	$xkeys=array_flip($data['labels']);
	foreach($vrecs as $setval=>$recs){
    	$dataset=array('label'=>$setval);
    	//add any params for this dataset - fill, borderDash, hidden
    	foreach($params as $k=>$v){
        	if(preg_match('/^'.$setval.'_(.+)$/i',$k,$m)){
            	$dataset[$m[1]]=$v;
			}
		}
    	$cdata=array();
    	//fill in in case
    	foreach($xkeys as $i){
			$dataset['data'][$i]=0;
		}
    	foreach($recs as $rec){
			if(!isset($xkeys[$rec['xval']])){continue;}
			$i=$xkeys[$rec['xval']];
        	$dataset['data'][$i]=$rec['yval'];
		}
		$data['datasets'][]=$dataset;
	}
	return json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
}
//---------- begin function buildDir ----------
/**
* @describe recursive folder generator
* @param dir string
* @param mode number
* @param recurse boolean
* @return boolean
* @usage if(!buildDir('/var/www/test/folder/sub/test')){return 'failed to build dir';}
*/
function buildDir($dir='',$mode=0777,$recursive=true){
	if(is_dir($dir)){return 0;}
	return @mkdir($dir,$mode,$recursive);
	}

//---------- begin function buildFakeContent--------------------
/**
* @describe fake content generator
* @param title string
* @return string
* @usage echo buildFakeContent($title);
*/
function buildFakeContent($title='FAKE for'){
	global $PAGE;
	$rtn .= '<h1><span>'.$title.'</span></h1>'.PHP_EOL;
	$rtn .= '<p>'.loremIpsum(500).'</p>'.PHP_EOL;
	$rtn .= '<div class="line"></div>'.PHP_EOL;
	$rtn .= '<p><div class="w_bold">'.loremIpsum(30).'</div> '.loremIpsum(300).'</p>'.PHP_EOL;
	return $rtn;
}
//---------- begin function parseWacssEditFormTags--------------------
/**
* @describe converts wacssedit form tags into HTML input fields
* @param body html 
* @param params array
*	[answers]  array key/value pairs of answers
*	[sections]  sections to show.  defaults to all sections
*	[customcode] array key/value pairs of customcode to replace
* @return string
* @usage echo parseWacssEditFormTags($htm);
*/
function parseWacssEditFormTags($body,$params=array()){
	/*
		return an array for each element
			section: defaults to 0 in none are found
			fieldname:
			displayname:
			btag - html body tag to replace with the formfield
			htm - html field
	*/
	$tags=array();
	//wacssform_section
	$section_names=array();
	preg_match_all('/\<div\>\<span class=\"wacssform\_section\"\>(.+?)\<\/span\>\<\/div\>/is', $body, $m);
	for($i=0;$i<count($m[0]);$i++){
		$section_names[$i]=$m[1][$i];
		$body=str_replace($m[0][$i],'[WACSSFORM_SECTION]',$body);
	}
	$sections=preg_split('/\[WACSSFORM_SECTION\]/is', $body);
	if(count($sections)==0){
		$sections_names=array('default');
		$sections=array($body);
	}
	else{
		if(count($section_names) < count($sections)){
			array_unshift($section_names,'default');
		}
	}
	$body=str_replace('[WACSSFORM_SECTION]','',$body);
	foreach($sections as $sid=>$sbody){
		//wacssform_date
		preg_match_all('/\<span class=\"wacssform\_date\"\>(.+?)\<\/span\>/is', $sbody, $m);
		for($i=0;$i<count($m[0]);$i++){
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[1][$i];
			$tparams=array();
			if(preg_match('/\{(.+?)\}$/',trim($tag['displayname']),$p)){
				$tag['displayname']=str_replace($p[0],'',$tag['displayname']);
				$pairs=preg_split('/\,/',$p[1]);
				foreach($pairs as $pair){
					list($k,$v)=preg_split('/\:/',$pair,2);
					$k=strtolower(trim($k));
					$k=preg_replace('/^\"/','',$k);
					$k=preg_replace('/\"$/','',$k);
					if($k=='default'){$k='value';}
					$v=trim($v);
					$v=preg_replace('/^\"/','',$v);
					$v=preg_replace('/\"$/','',$v);
					$tparams[$k]=$v;
				}
			}
			$tag['fieldname']='wacssform_date_'.encodeCRC($tag['displayname']);
			if(isset($params['answers'][$tag['fieldname']])){
				$tparams['value']=$tag['answer']=$params['answers'][$tag['fieldname']];
			}
			if(isset($params['format']) && $params['format']=='readonly'){
				$tparams['readonly']='readonly';
			}
			$tag['btag']=$m[0][$i];
			$tag['htm']='<label>'.$tag['displayname'].'</label>'.buildFormDate($tag['fieldname'],$tparams);
			$tags[$sid][]=$tag;
		}
		//wacssform_ratenX - on a scale of 1 to X
		preg_match_all('/\<span class=\"wacssform\_raten([0-9]+?)\"\>(.+?)\<\/span\>/is', $sbody, $m);
		for($i=0;$i<count($m[2]);$i++){
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[2][$i];			
			$tparams=array(
				'class'=>'w_orange',
				'1_class'=>'w_red',
				'10_class'=>'w_green',
				'min'=>1,
				'max'=>$m[1][$i],
				'pre'=>'Disagree',
				'post'=>'Agree'
			);
			if(preg_match('/\{(.+?)\}$/',trim($tag['displayname']),$p)){
				$tag['displayname']=str_replace($p[0],'',$tag['displayname']);
				$pairs=preg_split('/\,/',$p[1]);
				foreach($pairs as $pair){
					list($k,$v)=preg_split('/\:/',$pair,2);
					$k=strtolower(trim($k));
					$k=preg_replace('/^\"/','',$k);
					$k=preg_replace('/\"$/','',$k);
					if($k=='default'){$k='value';}
					$v=trim($v);
					$v=preg_replace('/^\"/','',$v);
					$v=preg_replace('/\"$/','',$v);
					$tparams[$k]=$v;
				}
			}
			$vals=range($tparams['min'],$tparams['max']);
			unset($tparams['min']);
			unset($tparams['max']);
			$tparams['width']=count($vals);
			$topts=array();
			foreach($vals as $val){
				$topts[$val]=$val;
			}

			$tag['fieldname']='wacssform_raten'.$m[1][$i].'_'.encodeCRC($tag['displayname']);
			
			if(isset($params['answers'][$tag['fieldname']])){
				$tparams['value']=$tag['answer']=$params['answers'][$tag['fieldname']];
			}
			if(isset($params['format']) && $params['format']=='readonly'){
				$tparams['readonly']='readonly';
			}
			$tag['btag']=$m[0][$i];
			$tag['htm']  ='<div style="font-weight:bold;">'.$tag['displayname'].'</div>';
			$tag['htm'] .='<div style="display:flex;justify-content:flex-start;align-items:center;">';
			$tag['htm'] .= '<span class="w_nowrap" style="padding-right:10px;">'.$tparams['pre'].' </span>';
			$tag['htm'] .= buildFormRadio($tag['fieldname'],$topts,$tparams);
			$tag['htm'] .= ' <span class="w_nowrap" style="padding-left:10px;"> '.$tparams['post'].'</span>';
			$tag['htm'] .= '</div>';
			$tags[$sid][]=$tag;
		}
		//wacssform_ratesX - X star rating
		preg_match_all('/\<span class=\"wacssform\_rates([0-9]+?)\"\>(.+?)\<\/span\>/is', $sbody, $m);
		for($i=0;$i<count($m[2]);$i++){
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[2][$i];
			$tparams=array(
				'min'=>1,
				'max'=>$m[1][$i],
				'pre'=>'Poor',
				'post'=>'Excellent'
			);
			if(preg_match('/\{(.+?)\}$/',trim($tag['displayname']),$p)){
				$tag['displayname']=str_replace($p[0],'',$tag['displayname']);
				$pairs=preg_split('/\,/',$p[1]);
				foreach($pairs as $pair){
					list($k,$v)=preg_split('/\:/',$pair,2);
					$k=strtolower(trim($k));
					$k=preg_replace('/^\"/','',$k);
					$k=preg_replace('/\"$/','',$k);
					if($k=='default'){$k='value';}
					$v=trim($v);
					$v=preg_replace('/^\"/','',$v);
					$v=preg_replace('/\"$/','',$v);
					$tparams[$k]=$v;
				}
			}
			$tag['fieldname']='wacssform_rates'.$m[1][$i].'_'.encodeCRC($tag['displayname']);
			
			if(isset($params['answers'][$tag['fieldname']])){
				$tparams['value']=$tag['answer']=$params['answers'][$tag['fieldname']];
			}
			if(isset($params['format']) && $params['format']=='readonly'){
				$tparams['readonly']='readonly';
			}
			$tag['btag']=$m[0][$i];
			$tag['htm']  ='<div style="font-weight:bold;">'.$tag['displayname'].'</div>';
			$tag['htm'] .='<div style="display:flex;justify-content:flex-start;align-items:center;">';
			$tag['htm'] .= '<span class="w_nowrap" style="padding-right:10px;">'.$tparams['pre'].' </span>';
			$tag['htm'] .= buildFormStarRating($tag['fieldname'],$tparams);
			$tag['htm'] .= ' <span class="w_nowrap" style="padding-left:10px;"> '.$tparams['post'].'</span>';
			$tag['htm'] .= '</div>';
			$tags[$sid][]=$tag;
		}
		//wacssform_select_one
		preg_match_all('/\<span class=\"wacssform\_one\"\>(.+?)\<\/span\>.+?\<ul\>(.+?)\<\/ul\>/is', $sbody, $m);
		for($i=0;$i<count($m[0]);$i++){
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[1][$i];
			$tparams=array(
				'width'=>1
			);
			if(preg_match('/\{(.+?)\}$/',trim($tag['displayname']),$p)){
				$tag['displayname']=str_replace($p[0],'',$tag['displayname']);
				$pairs=preg_split('/\,/',$p[1]);
				foreach($pairs as $pair){
					list($k,$v)=preg_split('/\:/',$pair,2);
					$k=strtolower(trim($k));
					$k=preg_replace('/^\"/','',$k);
					$k=preg_replace('/\"$/','',$k);
					if($k=='default'){$k='value';}
					$v=trim($v);
					$v=preg_replace('/^\"/','',$v);
					$v=preg_replace('/\"$/','',$v);
					$tparams[$k]=$v;
				}
			}
			$tag['fieldname']='wacssform_one_'.encodeCRC($tag['displayname']);
			$topts=array();
			preg_match_all('/\<li.*?>(.+?)\<\/li\>/',$m[2][$i],$ms);
			for($s=0;$s<count($ms[0]);$s++){
				//check for tval:dval
				$parts=preg_split('/\:/',$ms[1][$s],2);
				if(count($parts)==2){
					$topts[$parts[0]]=$parts[1];
				}
				else{
					$topts[$ms[1][$s]]=$ms[1][$s];
				}
			}
			if(isset($params['answers'][$tag['fieldname']])){
				$tparams['value']=$tag['answer']=$params['answers'][$tag['fieldname']];
			}
			if(isset($params['format']) && $params['format']=='readonly'){
				$tparams['readonly']='readonly';
			}
			$tag['btag']=$m[0][$i];
			$tag['htm']='<label>'.$tag['displayname'].'</label>';
			switch(strtolower($tparams['type'])){
				case 'dropdown':
					$tag['htm'] .= buildFormSelect($tag['fieldname'],$topts,$tparams);
				break;
				default:
					$tag['htm'] .= buildFormRadio($tag['fieldname'],$topts,$tparams);
				break;
			}
			$tags[$sid][]=$tag;
		}
		//wacssform_select_many
		preg_match_all('/\<span class=\"wacssform\_many\"\>(.+?)\<\/span\>.+?\<ul\>(.+?)\<\/ul\>/is', $sbody, $m);
		for($i=0;$i<count($m[0]);$i++){
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[1][$i];
			$tparams=array('width'=>1);
			if(preg_match('/\{(.+?)\}$/',trim($tag['displayname']),$p)){
				$tag['displayname']=str_replace($p[0],'',$tag['displayname']);
				$pairs=preg_split('/\,/',$p[1]);
				foreach($pairs as $pair){
					list($k,$v)=preg_split('/\:/',$pair,2);
					$k=strtolower(trim($k));
					$k=preg_replace('/^\"/','',$k);
					$k=preg_replace('/\"$/','',$k);
					if($k=='default'){$k='value';}
					$v=trim($v);
					$v=preg_replace('/^\"/','',$v);
					$v=preg_replace('/\"$/','',$v);
					$tparams[$k]=$v;
				}
			}
			$tag['fieldname']='wacssform_many_'.encodeCRC($tag['displayname']);
			$topts=array();
			preg_match_all('/\<li.*?>(.+?)\<\/li\>/',$m[2][$i],$ms);
			for($s=0;$s<count($ms[0]);$s++){
				//check for tval:dval
				$parts=preg_split('/\:/',$ms[1][$s],2);
				if(count($parts)==2){
					$topts[$parts[0]]=$parts[1];
				}
				else{
					$topts[$ms[1][$s]]=$ms[1][$s];
				}
			}
			if(isset($params['answers'][$tag['fieldname']])){
				$tparams['value']=$tag['answer']=$params['answers'][$tag['fieldname']];
			}
			if(isset($params['format']) && $params['format']=='readonly'){
				$tparams['readonly']='readonly';
			}
			$tag['btag']=$m[0][$i];
			$tag['htm']='<label>'.$tag['displayname'].'</label>';
			switch(strtolower($tparams['type'])){
				case 'dropdown':
					$tparams['displayname']=' --- Select One or Multiple --- ';
					$tag['htm'] .= buildFormMultiSelect($tag['fieldname'],$topts,$tparams);
				break;
				default:
					$tag['htm'] .= buildFormCheckbox($tag['fieldname'],$topts,$tparams);
				break;
			}
			$tags[$sid][]=$tag;
		}
		//wacssform_text
		preg_match_all('/\<span class=\"wacssform\_text\"\>(.+?)\<\/span\>/is', $sbody, $m);
		for($i=0;$i<count($m[0]);$i++){
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[1][$i];
			$tparams=array();
			if(preg_match('/\{(.+?)\}$/',trim($tag['displayname']),$p)){
				$tag['displayname']=str_replace($p[0],'',$tag['displayname']);
				$pairs=preg_split('/\,/',$p[1]);
				foreach($pairs as $pair){
					list($k,$v)=preg_split('/\:/',$pair,2);
					$k=strtolower(trim($k));
					$k=preg_replace('/^\"/','',$k);
					$k=preg_replace('/\"$/','',$k);
					if($k=='default'){$k='value';}
					$v=trim($v);
					$v=preg_replace('/^\"/','',$v);
					$v=preg_replace('/\"$/','',$v);
					$tparams[$k]=$v;
				}
			}
			$tag['fieldname']='wacssform_text_'.encodeCRC($tag['displayname']);
			if(isset($params['answers'][$tag['fieldname']])){
				$tparams['value']=$tag['answer']=$params['answers'][$tag['fieldname']];
			}
			if(isset($params['format']) && $params['format']=='readonly'){
				$tparams['readonly']='readonly';
			}
			$tag['btag']=$m[0][$i];
			$tag['htm']='<label>'.$tag['displayname'].'</label>'.buildFormText($tag['fieldname'],$tparams);
			$tags[$sid][]=$tag;
		}
		//wacssform_textarea
		preg_match_all('/\<span class=\"wacssform\_textarea\"\>(.+?)\<\/span\>/is', $sbody, $m);
		for($i=0;$i<count($m[0]);$i++){
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[1][$i];
			$tparams=array();
			if(preg_match('/\{(.+?)\}$/',trim($tag['displayname']),$p)){
				$tag['displayname']=str_replace($p[0],'',$tag['displayname']);
				$pairs=preg_split('/\,/',$p[1]);
				foreach($pairs as $pair){
					list($k,$v)=preg_split('/\:/',$pair,2);
					$k=strtolower(trim($k));
					$k=preg_replace('/^\"/','',$k);
					$k=preg_replace('/\"$/','',$k);
					if($k=='default'){$k='value';}
					$v=trim($v);
					$v=preg_replace('/^\"/','',$v);
					$v=preg_replace('/\"$/','',$v);
					$tparams[$k]=$v;
				}
			}
			$tag['fieldname']='wacssform_textarea_'.encodeCRC($tag['displayname']);
			
			if(isset($params['answers'][$tag['fieldname']])){
				$tparams['value']=$tag['answer']=$params['answers'][$tag['fieldname']];
			}
			if(isset($params['format']) && $params['format']=='readonly'){
				$tparams['readonly']='readonly';
			}
			$tag['btag']=$m[0][$i];
			$tag['htm']='<label>'.$tag['displayname'].'</label>'.buildFormTextarea($tag['fieldname'],$tparams);
			$tags[$sid][]=$tag;
		}
		//wacssform_signature
		preg_match_all('/\<span class=\"wacssform\_signature\"\>(.+?)\<\/span\>/is', $sbody, $m);
		for($i=0;$i<count($m[0]);$i++){
			if($i==0){
				loadExtrasJs('html5');
			}
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[1][$i];
			$tparams=array(
				'style'=>'width:100%;',
				'displayname'=>$tag['displayname']
			);
			if(preg_match('/\{(.+?)\}$/',trim($tag['displayname']),$p)){
				$tag['displayname']=str_replace($p[0],'',$tag['displayname']);
				$pairs=preg_split('/\,/',$p[1]);
				foreach($pairs as $pair){
					list($k,$v)=preg_split('/\:/',$pair,2);
					$k=strtolower(trim($k));
					$k=preg_replace('/^\"/','',$k);
					$k=preg_replace('/\"$/','',$k);
					if($k=='default'){$k='value';}
					$v=trim($v);
					$v=preg_replace('/^\"/','',$v);
					$v=preg_replace('/\"$/','',$v);
					$tparams[$k]=$v;
				}
			}
			$tag['fieldname']='wacssform_text_'.encodeCRC($tag['displayname']);
			
			if(isset($params['answers'][$tag['fieldname']])){
				$tparams['value']=$tag['answer']=$params['answers'][$tag['fieldname']];
			}
			if(isset($params['format']) && $params['format']=='readonly'){
				$tparams['readonly']='readonly';
			}
			$tag['btag']=$m[0][$i];
			$tag['htm']=buildFormSignature($tag['fieldname'],$tparams);
			$tags[$sid][]=$tag;
		}
		//wacssform_customcode
		preg_match_all('/\<span class=\"wacssform\_customcode\"\>(.+?)\<\/span\>/is', $sbody, $m);
		for($i=0;$i<count($m[0]);$i++){
			$tag=array();
			$tag['section_name']=$section_names[$sid];
			$tag['section_id']=$sid;
			$tag['displayname']=$m[1][$i];
			$tag['fieldname']='wacssform_customcode_'.encodeCRC($tag['displayname']);
			$tparams=array();
			if(isset($params['customcode'][$tag['displayname']])){
				$tag['htm']=$params['customcode'][$tag['displayname']];
			}
			else{
				$tag['htm']='<!--wacss_customcode '.$tag['displayname'].' missing value -->';
			}
			$tag['btag']=$m[0][$i];
			$tags[$sid][]=$tag;
		}
	}
	if(isset($params['sections'])){
		if(!is_array($params['sections'])){
			$params['sections']=preg_split('/\,/',$params['sections']);
		}
	}
	else{
		$params['sections']=array();
	}
	switch(strtolower($params['format'])){
		case 'data':
			return $tags;
		break;
		default:
			$bodies=array();
			foreach($sections as $sid=>$sbody){
				if(count($params['sections']) && !in_array($sid,$params['sections'])){
					$sbody=str_replace($sbody,PHP_EOL.'<!--wacss_section '.$sid.' skipped -->'.PHP_EOL,$sbody);
				}
				else{
					foreach($tags[$sid] as $tag){
						$sbody=str_replace($tag['btag'],$tag['htm'],$sbody);
					}
				}
				$bodies[]=$sbody;
			}
			return implode('',$bodies);
		break;
	}
	//replace the tags in the body
	
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function buildFormValueParam($name,$params=array(),$arr=0){
	if(!isset($params['value'])){
		if(isset($params['-value'])){$params['value']=$params['-value'];}
		elseif(isset($params[$name])){$params['value']=$params[$name];}
		elseif(isset($params["{$name}[]"])){$params['value']=$params["{$name}[]"];}
		elseif(isset($_REQUEST[$name])){$params['value']=$_REQUEST[$name];}
		elseif(isset($_REQUEST["{$name}[]"])){$params['value']=$_REQUEST["{$name}[]"];}
	}
	if($arr==1){
		$val=[];
		if(isset($params['value'])){
			if(is_array($params['value']) && count($params['value'])){
				$val=$params['value'];
			}
			elseif(is_string($params['value']) && strlen($params['value'])){
				//json?
				$json=decodeJson($params['value']);
				if(is_array($json)){
					$val=$json;
				}
				else{
					if(stringContains($params['value'],':')){
						$val=preg_split('/\:/',$params['value']);
					}
					elseif(stringContains($params['value'],',')){
						$val=preg_split('/\,/',$params['value']);
					}
					else{
						$val=array($params['value']);
					}
				}
			}
			elseif(isNum($params['value'])){
				$val=array($params['value']);
			}
		}
		//remove blank values
		foreach($val as $i=>$v){
			if(isNum($v)){continue;}
			if(!is_string($v) || !strlen(trim($v))){
				unset($val[$i]);
			}
		}
	}
	else{
		$val='';
		if(!isset($params['value'])){
			if(isset($params['-value'])){$params['value']=$params['-value'];}
			elseif(isset($_REQUEST[$name])){$params['value']=$_REQUEST[$name];}
			elseif(isset($_REQUEST["{$name}[]"])){$params['value']=$_REQUEST["{$name}[]"];}
		}
		if(isset($params['value'])){
			if(is_array($params['value']) && count($params['value'])){
				$val=$params['value'][0];
			}
			elseif(is_string($params['value']) && strlen($params['value'])){
				$val=$params['value'];
			}
			elseif(isNum($params['value'])){
				$val=$params['value'];
			}
		}
		if(strtoupper($val)=='NULL'){$val='';}
	}
	return $val;
}
//---------- begin function buildFormButtonSelect--------------------
/**
* @describe creates an button selection field
* @param name string
* @param opts array - true value/display value pairs.
* @param params array
*	[value] - sets default selection
*	[name] - name override
*	[class] - string - w_green, w_red, etc..
* @return string
* @usage echo buildFormButtonSelect('color',array('red'=>'Red','blue'=>'Blue','green'=>'Green'),$params);
*/
function buildFormButtonSelect($name,$opts=array(),$params=array()){
	$params['-format']="button";
	$params['-type']='radio';
	$params['-display']='flex';
	return buildFormRadioCheckbox($name,$opts,$params);
}
//---------- begin function buildFormButtonSelectMultiple--------------------
/**
* @describe creates an button selection field
* @param name string
* @param opts array - true value/display value pairs.
* @param params array
*	[value] - sets default selection
*	[name] - name override
*	[class] - string - w_green, w_red, etc..
* @return string
* @usage echo buildFormButtonSelectMultiple('color',array('red'=>'Red','blue'=>'Blue','green'=>'Green'),array('required'=>1));
*/
function buildFormButtonSelectMultiple($name,$opts=array(),$params=array()){
	$params['value']=buildFormValueParam($name,$params,1);
	if(!isset($params['-button'])){
		$params['-button']='btn-default';
	}
	//override name
	if(isset($params['name'])){
		$name=$params['name'];
		unset($params['name']);
	}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}

	$tag='<div class="w_flexgroup" data-display="inline-flex"';
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	$tag .='>'.PHP_EOL;
	foreach($opts as $tval=>$dval){
		$checked='';
		if(in_array($tval,$params['value']) || in_array($dval,$params['value'])){
			$checked=' checked';
		}
		$id="{$name}_{$tval}";
		$class='';
		if(isset($params["{$tval}_class"])){$class=$params["{$tval}_class"];}
		elseif(isset($params["{$dval}_class"])){$class=$params["{$dval}_class"];}
		elseif(isset($params['class'])){$class=$params['class'];}
		$tag .= '<input type="checkbox" data-type="checkbox" class="btn '.$class.'" style="display:none"';
		if(isset($params['onclick'])){
			$tag .= ' onclick="'.$params['onclick'].'"';
		}
		$tag .= ' name="'.$name.'[]"  id="'.$id.'" value="'.$tval.'" '.$checked.' >'.PHP_EOL;
        $tag .= '<label for="'.$id.'">'.$dval.'</label>'.PHP_EOL;
	}
	$tag .= '</div>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormCalendar--------------------
/**
* @describe creates an HTML calendar control
* @describe uses the HTML5 date control if the browser supports it
* @param name string - name of the input field to create
* @param params array
* @return string
* @usage echo buildFormCalendar('mydate')
*/
function buildFormCalendar($name,$params=array()){
	return buildFormDate($name,$params);
}
//---------- begin function buildFormCheckAll--------------------
/**
* @describe creates a checkbox that checks other checkboxes
* @param att string
* @param attval string
* @return boolean
* @usage echo buildFormCheckAll('id','users');
*/
function buildFormCheckAll($att,$attval,$params=array()){
	if(isset($params['-label'])){
		$name=$params['-label'];
		unset($params['-label']);
	}
	else{
		$name='Checkall';
	}

	$id='checkall_'.crc32($att.$attval);
	$tag="<input id=\"{$id}\" type=\"checkbox\" onclick=\"checkAllElements('{$att}','{$attval}',this.checked);\" >";
	if(isset($params['for'])){unset($params['for']);}
	$tag .= '<label for="'.$id.'" ';
	$tag .= setTagAttributes($params);
	$tag .= '>'.$name.'</label>';
	return $tag;
	}
//---------- begin function buildFormCheckbox--------------------------------------
/**
* @describe creates an HTML Form checkbox
* @param name string
*	The name of the checkbox
* @param opts array tval/dval pairs to display
* @param params array - options
*	[-values] array - an array of tval values to mark as checked
*	[width] how many to show in a row - default to 6
* 	[-icon] string  - icon to use. Valid options are mark, blank, cancel, close, circle, minus, plus, star. Defaults to mark
* 	[size] string - size of button. Valid options are small, smaller, smallest, tiny, big, bigger, biggest, huge.
* @return
*	HTML Form checkbox for each pair passed in
* @usage buildFormCheckbox('colors',$opts);
*/
function buildFormCheckbox($name, $opts=array(), $params=array()){
	$params['-type']='checkbox';
	return buildFormRadioCheckbox($name, $opts, $params);
}
//---------- begin function buildFormColor-------------------
/**
* @describe creates an HTML color control
* @param name string - field name
* @param params array
*	[-formname] string - specify the form name - defaults to addedit
*	[value] string - specify the current value
*	[required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
* @return string - html color control
* @usage echo buildFormColor('color');
*/
function buildFormColor($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['displayif'])){$params['data-displayif']=$params['displayif'];}
	$params['value']=buildFormValueParam($name,$params);
	$tag='';
	$tag.='<div class="w_colorfield"';
	if(isset($params['data-displayif']) && is_string($params['data-displayif']) && strlen($params['data-displayif'])){
		$tag.=' data-displayif="'.$params['data-displayif'].'"';
		unset($params['data-displayif']);
	}
	$tag.='>'.PHP_EOL;
	$tag.='	<div>'.PHP_EOL;
	$tag .= '	<input type="text" name="'.$name.'" value="'.$params['value'].'"';
	$tag .= setTagAttributes($params);
	$tag .= ' >'.PHP_EOL;
	$tag.='		<label for="'.$name.'_check"';
	if(isset($params['value']) && is_string($params['value']) && strlen($params['value'])){
		$tag.=' style="background-color:'.$params['value'].'"';
	}
	$tag.='></label>'.PHP_EOL;
	$tag.='	</div>'.PHP_EOL;
	$tag.='	<input type="checkbox" id="'.$name.'_check">'.PHP_EOL;
	$tag.= buildFormColorWheelMap($name.'_map').PHP_EOL;
	$tag.='</div>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormColorWheelMap-------------------
/**
* @exclude  - this function in only used internally by buildFormColor
*/
function buildFormColorWheelMap($name){
	$wpath=getWasqlPath('wfiles');
	$body=getFileContents("{$wpath}/color_wheel_map.htm");
	preg_match_all('/title\=\"(.+?)\".+?data\-color\=\"(.+?)\"/',$body,$m);
	$opts=array();
	$sparams=array(
		'onchange'=>"wacss.colorboxSelect(this)",
		'class'=>'select',
		'message'=>'-- Color By Name --',
		'style'=>'border-top-right-radius:0px;border-top-left-radius:0px;'
	);
	//echo printValue($m);exit;
	foreach($m[0] as $i=>$c){
		$opts[$m[2][$i]]=$m[1][$i];
		$contrast_ratio=commonGetContrastRatio($m[2][$i]);
		if($contrast_ratio > 3){$contrast_color='#000000';}
		else{$contrast_color='#FFFFFF';}
		$sparams["{$m[2][$i]}_style"]="background-color:{$m[2][$i]};color:{$contrast_color};";
	}
	$selectmap=buildFormSelect($name.'_select',$opts,$sparams);
	$map=<<<ENDOFMAP
<nav class="colorboxmap">
	<img class="wheel" src="/wfiles/color_wheel.png" usemap="#{$name}_map" style="width:100%;height:auto;">
	{$selectmap}
	<map name="{$name}_map" style="display:none;">
	    {$body}
	</map>
</nav>
ENDOFMAP;
	return $map;
}
//---------- begin function buildFormColorHexagon-------------------
/**
* @describe creates an HTML color control using the color_hexagon.gif in wfiles
* @param name string - field name
* @param params array
*	[-formname] string - specify the form name - defaults to addedit
*	[value] string - specify the current value
*	[required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
* @return string - html color control
* @usage echo buildFormColorHexagon('color');
*/
function buildFormColorHexagon($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['displayif'])){$params['data-displayif']=$params['displayif'];}
	$params['value']=buildFormValueParam($name,$params);
	$tag='';
	$tag.='<div class="w_colorfield"';
	if(isset($params['data-displayif']) && is_string($params['data-displayif']) && strlen($params['data-displayif'])){
		$tag.=' data-displayif="'.$params['data-displayif'].'"';
		unset($params['data-displayif']);
	}
	$tag.='>'.PHP_EOL;
	$tag.='	<div>'.PHP_EOL;
	$tag .= '	<input type="text" name="'.$name.'" value="'.$params['value'].'"';
	$tag .= setTagAttributes($params);
	$tag .= ' >'.PHP_EOL;
	$tag.='		<label for="'.$name.'_check"';
	if(isset($params['value']) && is_string($params['value']) && strlen($params['value'])){
		$tag.=' style="background-color:'.$params['value'].'"';
	}
	$tag.='></label>'.PHP_EOL;
	$tag.='	</div>'.PHP_EOL;
	$tag.='	<input type="checkbox" id="'.$name.'_check">'.PHP_EOL;
	$tag.= buildFormColorHexagonMap($name,$params).PHP_EOL;
	$tag.='</div>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormColorWheelMap-------------------
/**
* @exclude  - this function in only used internally by buildFormColor
*/
function buildFormColorHexagonMap($name){
	$wpath=getWasqlPath('wfiles');
	$body=getFileContents("{$wpath}/color_hexagon_map.htm");
	preg_match_all('/data\-color\=\"(.+?)\"\ title\=\"(.+?)\"/',$body,$m);
	$opts=array();
	$sparams=array(
		'onchange'=>"wacss.colorboxSelect(this)",
		'class'=>'select',
		'message'=>'-- Color By Name --',
		'style'=>'margin-top:3px;border-top-right-radius:0px;border-top-left-radius:0px;'
	);
	//echo printValue($m);exit;
	foreach($m[0] as $i=>$c){
		$opts[$m[1][$i]]=$m[2][$i];
		$contrast_ratio=commonGetContrastRatio($m[1][$i]);
		if($contrast_ratio > 3){$contrast_color='#000000';}
		else{$contrast_color='#FFFFFF';}
		$sparams["{$m[1][$i]}_style"]="background-color:{$m[1][$i]};color:{$contrast_color};";
	}
	$selectmap=buildFormSelect($name.'_select',$opts,$sparams);
	$map=<<<ENDOFMAP
<nav class="colorboxmap hexagon">
	<img class="hexagon" src="/wfiles/color_hexagon.gif" usemap="#{$name}_map" style="width:234px;height:199px;"">
	{$selectmap}
	<map name="{$name}_map">
	    {$body}
	</map>
</nav>
ENDOFMAP;
	return $map;
}
//---------- begin function buildFormColorBox-------------------
/**
* @describe creates an HTML color control using the color box built from color_names.csv
* @param name string - field name
* @param params array
*	[-formname] string - specify the form name - defaults to addedit
*	[value] string - specify the current value
*	[required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
* @return string - html color control
* @usage echo buildFormColorBox('color');
*/
function buildFormColorBox($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['displayif'])){$params['data-displayif']=$params['displayif'];}
	$params['value']=buildFormValueParam($name,$params);
	$tag='';
	$tag.='<div class="w_colorfield"';
	if(isset($params['data-displayif']) && is_string($params['data-displayif']) && strlen($params['data-displayif'])){
		$tag.=' data-displayif="'.$params['data-displayif'].'"';
		unset($params['data-displayif']);
	}
	$tag.='>'.PHP_EOL;
	$tag.='	<div>'.PHP_EOL;
	$tag .= '	<input type="text" name="'.$name.'" value="'.$params['value'].'"';
	$tag .= setTagAttributes($params);
	$tag .= ' >'.PHP_EOL;
	$tag.='		<label for="'.$name.'_check"';
	if(isset($params['value']) && is_string($params['value']) && strlen($params['value'])){
		$tag.=' style="background-color:'.$params['value'].'"';
	}
	$tag.='></label>'.PHP_EOL;
	$tag.='	</div>'.PHP_EOL;
	$tag.='	<input type="checkbox" id="'.$name.'_check">'.PHP_EOL;
	//$tag.='	<div style="padding:5px;"><img src="/wfiles/color_hexagon.gif" style="border-bottom-left-radius:15px;border-bottom-right-radius:15px;" usemap="#'.$name.'_map"></div>'.PHP_EOL;
	$tag.= buildFormColorBoxMap($name.'_map',$params).PHP_EOL;
	$tag.='</div>'.PHP_EOL;
	return $tag;
}
function buildFormColorBoxMap($name){
	$wpath=getWasqlPath('wfiles');
	$recs=getCSVRecords("{$wpath}/color_names.csv");
	$map='<nav class="colorboxmap" name="'.$name.'">'.PHP_EOL;
	$opts=array();
	$sparams=array(
		'onchange'=>"wacss.colorboxSelect(this)",
		'class'=>'select',
		'message'=>'-- Color By Name --',
		'style'=>'border-top-right-radius:0px;border-top-left-radius:0px;'
	);
	foreach($recs as $rec){
		$map.=<<<ENDOFIMG
<img src="/wfiles/clear.gif" title="{$rec['name']}" style="background-color:{$rec['hex']};" onclick="wacss.colorboxSet(this);" data-color="{$rec['hex']}">
ENDOFIMG;
		$opts[$rec['hex']]="{$rec['name']}";
		$sparams["{$rec['hex']}_style"]="background-color:{$rec['hex']};color:{$rec['contrast_color']};";
	}
	$map.=buildFormSelect($name.'colorbox_select',$opts,$sparams);
	$map.='</nav>'.PHP_EOL;
	
	//return printValue($opts);
	return $map;
}

//---------- begin function buildFormCombo--------------------
/**
* @describe creates an HTML combo field
* @param name string
* @param opts array
* @param params array
* @return string
* @usage echo buildFormCombo('mydate',$opts,$params);
*/
function buildFormCombo($name,$opts=array(),$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['displayif'])){$params['data-displayif']=$params['displayif'];}
	$params['list']=$params['id'].'_datalist';
	$params['value']=buildFormValueParam($name,$params,0);

	$params['name']=$name;
	$tag .= '	<input type="text" value="'.encodeHtml($params['value']).'"';
	$tag .= setTagAttributes($params);
	$tag .= ' >'.PHP_EOL;
	$tag .= '<datalist id="'.$params['list'].'">'.PHP_EOL;
	foreach($opts as $tval=>$dval){
		$tag .= '	<option value="'.$tval.'">'.$dval.'</option>'.PHP_EOL;
	}
	$tag .= '</datalist>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormDate-------------------
/**
* @describe creates an HTML date control
* @param action string
* @param params array
*	[data-enabletime] - boolean - enables a time picker, defaults to false
*	[data-dateformat] - string
* 	[data-timeformat] - string - H (00 to 23), h (1 to 12), i (00 to 59), K (AM or PM)
* 	[data-mindate] - string
*  	[data-maxdate] - string
*  	[data-disable] - array - dates to disable using intervals
*  	[data-altinput] - boolean - Show the user a readable date (as per altFormat), but return something totally different to the server.
* 	[data-altformat] - string
* 	[data-inline] - boolean - Display the calendar inline.
* 	[data-shorthandcurrentmonth] - boolean - Show the month using the shorthand version.
* 	[data-onchange] - function name - A function that gets triggered on every date selection
* 	[data-hourincrement] - integer - Adjusts the step for the hour input (incl. scrolling)
* 	[data-minuteincrement] - integer - Adjusts the step for the minute input (incl. scrolling)
* @return string
* @usage echo buildFormDate('mydate');
*/
function buildFormDate($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name']) && strlen(trim($params['name']))){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['-required']) && $params['-required']){$params['required']=1;}
	elseif(isset($params['required']) && $params['required']){$params['required']=1;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	$params['data-behavior']='flatpickr';
	$params['value']=buildFormValueParam($name,$params);
	if(strlen($params['value'])){
		if(preg_match('/^[a-z\-\+]/i',$params['value'])){
			//+21 days  or +3 months
			$params['value']=date('Y-m-d',strtotime($params['value']));
		}
		elseif(isNum($params['value'])){
			$params['value']=date('Y-m-d',$params['value']);
		}
	}
	if(isset($params['data-mindate'])){
		if(isNum($params['data-mindate'])){
			if(!stringBeginsWith($params['data-mindate'],'-')){
				$params['data-mindate']='+ '.$params['data-mindate'];
			}
			$params['data-mindate']=date('Y-m-d',strtotime($params['data-mindate'].' days'));
		}
		elseif(strtolower($params['data-mindate'])=='today'){
			$params['data-mindate']=date('Y-m-d');
		}
	}
	if(isset($params['data-maxdate'])){
		if(isNum($params['data-maxdate'])){
			if(!stringBeginsWith($params['data-maxdate'],'-')){
				$params['data-maxdate']='+ '.$params['data-maxdate'];
			}
			$params['data-maxdate']=date('Y-m-d',strtotime($params['data-maxdate'].' days'));
		}
		elseif(strtolower($params['data-maxdate'])=='today'){
			$params['data-maxdate']=date('Y-m-d');
		}
	}
	//placeholder
	if(isset($params['data-dateformat']) && !isset($params['placeholder'])){
		$params['placeholder']=$params['data-dateformat'];
	}
	if(!isset($params['placeholder'])){
		if(isset($params['data-noCalendar']) && isset($params['data-enableTime'])){
			$params['placeholder']='HH:MM';
		}
		else{
			$params['placeholder']='YYYY-MM-DD';
		}
	}
	if(!isset($params['data-allowInput'])){
		$params['data-allowInput']=1;
	}
	switch(strtolower($params['readonly'])){
		case 'readonly':
		case '1':
			$params['data-behavior']='flatpickr_readonly';
		break;
	}
	$tag='';
	$tag .= '<div class="w_flexgroup" data-display="inline-flex" style="position:relative;margin-top:0px;"';
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	unset($params['name']);
	$tag .='>'.PHP_EOL;
	$tag .= '	<input type="text" data-inputtype="date" name="'.$name.'" autocomplete="off"';
	$pstyle='';
	if(isset($params['style'])){$pstyle=$params['style'];}
	$params['style']='min-width:100px;'.$pstyle;
	unset($params['width']);
	$tag .= setTagAttributes($params);
	$tag .= '  value="'.encodeHtml($params['value']).'" >'.PHP_EOL;
	$attrs='';
	if(isset($params['data-readonlyif'])){
		$attrs.=' data-readonlyif="'.$params['data-readonlyif'].'"';
	}
	//hide calendar icon if readonly or disabled
	$show=1;
	if(isset($params['readonly']) && in_array(strtolower($params['readonly']),array('1','readonly'))){$show=0;}
	if(isset($params['disabled']) && in_array(strtolower($params['disabled']),array('1','disabled'))){$show=0;}
	if(isset($params['data-noCalendar']) && isset($params['data-enableTime'])){
		$tag .= '	<span class="icon-clock w_gray w_biggest w_pointer" '.$attrs.' onclick="if(this.hasAttribute(\'readonly\')){return false;} return simulateEvent(getObject(\''.$params['id'].'\'),\'focus\');"></span>'.PHP_EOL;
	}
	elseif($show==1){
		$tag .= '	<span class="icon-calendar w_gray w_biggest w_pointer" '.$attrs.' onclick="if(this.hasAttribute(\'readonly\')){return false;} return simulateEvent(getObject(\''.$params['id'].'\'),\'focus\');"></span>'.PHP_EOL;
	}
	$tag .= '</div>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormDateTime-------------------
/**
* @describe creates an HTML date and time control
* @param action string
* @param params array
* @return string
* @usage echo buildFormDateTime('mydate');
*/
function buildFormDateTime($name,$params=array()){
	$params['data-enableTime']=1;
	//return printValue($params);
	return buildFormDate($name,$params);
}
//---------- begin function buildFormGender--------------------
/**
* @describe creates an Button Select field for Gender
* @param name string
* @param params array
* @return string
* @usage echo buildFormGender('gender',$params);
*/
function buildFormGender($name='gender',$params=array()){
	$opts=array(
		'M'=>'Male',
		'F'=>'Female'
	);
	$params['M_class']='w_blue';
	$params['F_class']='w_red';
	return buildFormButtonSelect($name,$opts,$params);
}
//---------- begin function buildFormHidden--------------------
/**
* @describe creates an HTML hidden field
* @param name string
* @param opts array
* @param params array
* @return string
* @usage echo buildFormHidden('mydate',$params);
*/
function buildFormHidden($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	$params['value']=buildFormValueParam($name,$params);
	$params['name']=$name;
	$tag .= '	<input type="hidden" value="'.encodeHtml($params['value']).'"';
	$tag .= setTagAttributes($params);
	$tag .= ' >'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormPassword--------------------
/**
* @describe creates an HTML password field
* @param name string
* @param params array
* 	[-formname] - name of the parent form  - used to set a default id if one is not given
* 	[name] - name of the input if you want to override the one passed in
* 	[id] - id of the input - defaults to formname_name
* 	[class] - css class
* 	[onfocus] - javascript to run on focus
* 	[requiredif] - set required based on another field
* 	[value] - set value
* 	[data-lock_icon] - prepend lock icon as part of the input
* 	[data-show_icon] - append show icon to show password on hover     
* @return string
* @usage echo buildFormPassword('password',$params);
*/
function buildFormPassword($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	//if(!isset($params['onfocus'])){$params['onfocus']='this.select();';}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	$params['value']=buildFormValueParam($name,$params);
	$params['name']=$name;
	$tag='<div class="flexbutton" data-display="inline-flex" style="display:flex;flex-direction:row;justify-content:flex-start;"';
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	$tag .='>'.PHP_EOL;
	if(isset($params['data-lock_icon'])){
		$tag .= '	<span class="btn w_white"><span class="icon-lock"></span></span>'.PHP_EOL;
	}
	$tag .= '	<input type="password" value="'.encodeHtml($params['value']).'"';
	$tag .= setTagAttributes($params);
	$tag .= ' >'.PHP_EOL; 
	if(isset($params['data-show_icon'])){
		$tag .= '<input type="checkbox" value="1" id="showpassword" onclick="formShowPassword(\''.$params['id'].'\',this.checked);" style="display:none;" ><span title="show password"><label for="showpassword" class="w_pointer"><span class="icon-eye"></span></label></span>'.PHP_EOL;
	}	
	$tag .= '</div>'.PHP_EOL;

	return $tag;
}
//---------- begin function buildFormRecorderAudio--------------------
/**
* @describe creates an HTML recorder-audio field
* @param name string
* @param params array
* 	[-formname] - name of the parent form  - used to set a default id if one is not given
* 	[name] - name of the input if you want to override the one passed in
* 	[id] - id of the input - defaults to formname_name
* 	[requiredif] - set required based on another field
* 	[record] - text to display when NOT recording - defaults to Click to Record
* 	[stop] - text to display when IS recording - defaults to Click to Stop
* 	[value] - set value    
* 	[data-mix] - url of mp3 to play in the background during recording
* @return string
* @usage echo buildFormRecorderAudio('my_speach',$params);
*/
function buildFormRecorderAudio($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	//if(!isset($params['onfocus'])){$params['onfocus']='this.select();';}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(!isset($params['style'])){$params['style']='';}
	if(!isset($params['record']) || !strlen($params['record'])){$params['record']='Click To Record';}
	if(!isset($params['stop']) || !strlen($params['stop'])){$params['stop']='Click To Stop';}
	if(!isset($params['class']) || !strlen($params['class'])){$params['class']='w_black';}
	$params['value']=buildFormValueParam($name,$params);
	$params['name']=$name.'_button';
	$params['data-behavior']='recorder_audio';
	$params['data-id']=$params['id'];
	$params['data-base64']=$params['id'].'_base64';
	$params['data-audio']=$params['id'].'_audio';
	$params['data-audiobox']=$params['id'].'_audiobox';
	$params['style'].='display:flex;justify-content:center;align-items:center';
	$params['value']=preg_replace('/^\/\//','/',$params['value']);
	//set path of where to store this file in
	if(!isset($params['path'])){
    	if(isset($params['data-path']) && strlen($params['data-path'])){$params['path']=$params['data-path'];}
    	elseif(isset($_REQUEST["{$name}_path"]) && strlen($_REQUEST["{$name}_path"])){$params['path']=$_REQUEST["{$name}_path"];}
    	else{$params['path']="/files/{$name}";}
	}
	$params['path']=preg_replace('/^\/+/','',$params['path']);
	//create path if it does not exist
	$apath="{$_SERVER['DOCUMENT_ROOT']}/{$params['path']}";
	if(!is_dir($apath)){
		buildDir($apath);
	}
	unset($params['onclick']);
	$id=$params['id'];
	unset($params['id']);
	//return $name.printValue($params);
	//audiobox
	$tag='<div id="'.$id.'_audiobox" style="display:inline-flex;align-items:center;justify-content:flex-start;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;white-space:nowrap;font-size:1rem;">';
	//click
	if(isset($params['viewonly']) || isset($params['readonly']) || isset($params['disabled'])){
		$tag.='<a href="#record" id="'.$id.'_click" data-id="'.$id.'" style="display:block;text-decoration:none;padding:0.375rem 0.75rem;" onclick="return false;"';
		$tag .= setTagAttributes($params);
		if(strlen($params['value'])){
			$tag .='>'.getFileName($params['value']).'</a>';
		}
		else{
			$tag .='>'.$params['record'].'</a>';
		}
	}
	else{
		$tag.='<a href="#record" id="'.$id.'_click" data-id="'.$id.'" data-record="'.$params['record'].'" data-stop="'.$params['stop'].'" style="display:block;text-decoration:none;padding:0.375rem 0.75rem;" onclick="return formRecorderAudio(this);"';
		$tag .= setTagAttributes($params);
		$tag .='>'.$params['record'].'</a>';
	}
	
	//mic
	$tag .= '<span id="'.$id.'_mic" data-id="'.$id.'" class="icon-mic" style="padding:0.375rem 0.75rem;border-left:1px solid #CCC;"></span>';
	//play
	if(strlen($params['value'])){
		$tag .= '<span id="'.$id.'_play" title="play" data-id="'.$id.'" class="icon-play w_pointer" style="padding:0.375rem 0.75rem;border-left:1px solid #CCC;" onclick="formRecorderAudioControl(this);"></span>';
	}
	$tag.=buildFormHidden($name,array('value'=>1));
	$tag.=buildFormHidden("{$name}_path",array('value'=>$params['path']));
	//autonumber?
	if(isset($params['autonumber']) || isset($params['data-autonumber']) || $params['tvals'] == 'autonumber' || $params['behavior'] == 'autonumber'){
		$tag.=buildFormHidden("{$name}_autonumber",array('value'=>1));
    }
    //resize after upload?
    if(isset($params['resize']) || isset($params['data-resize'])){
    	$resize=isset($params['resize'])?$params['resize']:$params['data-resize'];
		$tag.=buildFormHidden("{$name}_resize",array('value'=>$resize));
		unset($params['data-resize']);
    }
    $params['name']=$name.'_base64';
	$params['style']='display:none;';
	$params['id']=$id.'_base64';
	$tag.=buildFormTextarea($name,$params);
	if(strlen($params['value'])){
		$params['name']=$name.'_prev';
		$params['style']='display:none;';
		$params['id']=$id.'_prev';
		$tag.=buildFormTextarea($name,$params);
	}
	$tag .= '</div>';
	return $tag;
}
//---------- begin FUNCTION buildFormGeoLocationMap-------------------
/**
* @describe creates an Map form element where user can select latitude,longitude from a map
* @param name string
* @param params array
* [zoom] integer - zoom level defaults to 13
* @return string
* @usage echo buildFormGeoLocationMap('work_location',$params);
*/
function buildFormGeoLocationMap($name,$params=array()){
	global $CONFIG;
	//return $name.printValue($params);
	$name=preg_replace('/[\[\]]+$/','',$name);
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name']) && strlen($params['name'])){$name=$params['name'];}
	$params['name']=$name;
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	$params['width']=isNum($params['width'])?$params['width']:300;
	if(!isset($params['class']) || !strlen($params['class'])){$params['class']='input';}
	if(!isset($params['autocomplete'])){$params['autocomplete']='off';}
	if(!isset($params['-apikey']) && isset($CONFIG['google_apikey'])){
		$params['-apikey']=$CONFIG['google_apikey'];
	}
	if(!isset($params['style'])){$params['style']='';}
	if(!isset($params['zoom'])){$params['zoom']=13;}
	$params['style'].='pointer-events: none;font-size:0.8rem;border-right:0px !important;border-top-right-radius: 0px;border-bottom-right-radius: 0px;';
	if(isset($params['displayname'])){$dname=$params['displayname'];}
	else{$dname=ucwords(trim(str_replace('_',' ',$name)));}
	if(!isset($params['placeholder'])){$params['placeholder']=$dname;}
	$params['value']=buildFormValueParam($name,$params);
	$onclick="wacss.geoLocation('{$params['id']}',{showmap:1,displayname:'{$dname}'});";
	$latlon=array();
	if(isset($params['viewonly']) || isset($params['readonly']) || isset($params['disabled'])){
		if(strlen($params['value']) && preg_match('/([0-9\-\.]+?)([\,\ ]+?)([0-9\-\.]+)/is',$params['value'],$m)){
			$latlon=array($m[1],$m[3]);
			$onclick="wacss.geoLocationMap(this.dataset.lat,this.dataset.lon,{showmap:1,zoom:{$params['zoom']},displayname:'{$dname}',readonly:1});";
		}
		else{
			$onclick="wacss.geoLocation('{$params['id']}',{showmap:1,zoom:{$params['zoom']},displayname:'{$dname}',readonly:1});";
			//$onclick="alert('{$params['value']}');";
		}
	}
	elseif(strlen($params['value']) && preg_match('/([0-9\-\.]+?)([\,\ ]+?)([0-9\-\.]+)/is',$params['value'],$m)){
		$latlon=array($m[1],$m[3]);
		$onclick="wacss.geoLocationMap(this.dataset.lat,this.dataset.lon,{showmap:1,zoom:{$params['zoom']},displayname:'{$dname}',input:'{$params['id']}'});";
	}
	elseif(strlen($params['start_value']) && preg_match('/([0-9\-\.]+?)([\,\ ]+?)([0-9\-\.]+)/is',$params['start_value'],$m)){
		$latlon=array($m[1],$m[3]);
		$onclick="wacss.geoLocationMap(this.dataset.lat,this.dataset.lon,{showmap:1,zoom:{$params['zoom']},displayname:'{$dname}',input:'{$params['id']}'});";
		$params['data-start_value']=$params['start_value'];
	}
	elseif(strlen($params['data-start_value']) && preg_match('/([0-9\-\.]+?)([\,\ ]+?)([0-9\-\.]+)/is',$params['data-start_value'],$m)){
		$latlon=array($m[1],$m[3]);
		$onclick="wacss.geoLocationMap(this.dataset.lat,this.dataset.lon,{showmap:1,zoom:{$params['zoom']},displayname:'{$dname}',input:'{$params['id']}'});";
	}
	$params['pattern']='\[[+-]?[0-9]*\.?[0-9]+,[+-]?[0-9]*\.?[0-9]+\]';
	$params['title']='[latitude,longitude] - click on google maps icon to select a location';
	$params['data-tip']='Click on google maps icon to select a location';
	$params['data-tip_position']='bottom';
	unset($params['onclick']);
	$atts = setTagAttributes($params);
	//return $atts.printValue($params);
	//make sure wacss and google map api are loaded
	global $buildFormGeoLocationMapLoaded;
	if(!isset($buildFormGeoLocationMapLoaded) || $buildFormGeoLocationMapLoaded!=1){
		$buildFormGeoLocationMapLoaded=1;
		$tag=<<<ENDOFLOAD
	<script async
    src="https://maps.googleapis.com/maps/api/js?key={$params['-apikey']}">
</script>
ENDOFLOAD;
	}
	//loadExtrasJs(array("https://maps.googleapis.com/maps/api/js?key={$params['-apikey']}",'wacss'));
	//return printValue($_SESSION);
	$tag.='<div';
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	elseif(isset($params['data-displayif'])){
		$tag .= ' data-displayif="'.$params['data-displayif'].'"';
		unset($params['data-displayif']);
	}
	$tag .=<<<ENDOFTAG
	><div style="display:inline-flex;align-items: center;width:{$params['width']}px;";>
	<input type="text" class="{$params['class']}" {$atts}  value="{$params['value']}" >
	<button type="button" id="{$params['id']}_clickdiv" onclick="{$onclick}" data-lat="{$latlon[0]}" data-lon="{$latlon[1]}" class="btn" style="font-size:0.8rem;background:#b4b6b5;background-image:url('/wfiles/svg/google-maps.svg');background-size: cover;border-left:0px !important;border-top-left-radius: 0px;border-bottom-left-radius: 0px;">&nbsp;</button>
</div>
</div>
ENDOFTAG;
	return $tag;
}
//---------- begin FUNCTION buildFormLatLon-------------------
/**
* @describe creates an latlon form element where user can select latitude,longitude from a map
* @param name string
* @param params array
* [zoom] integer - zoom level defaults to 13
* @return string
* @usage echo buildFormGeoLocationMap('work_location',$params);
*/
function buildFormLatLon($name,$params=array()){
	global $CONFIG;
	//return $name.printValue($params);
	$name=preg_replace('/[\[\]]+$/','',$name);
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name']) && strlen($params['name'])){$name=$params['name'];}
	$params['name']=$name;
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	$params['width']=isNum($params['width'])?$params['width']:300;
	if(!isset($params['class']) || !strlen($params['class'])){$params['class']='input';}
	if(!isset($params['autocomplete'])){$params['autocomplete']='off';}
	if(!isset($params['-apikey']) && isset($CONFIG['google_apikey'])){
		$params['-apikey']=$CONFIG['google_apikey'];
	}
	if(!isset($params['style'])){$params['style']='';}
	if(!isset($params['zoom'])){$params['zoom']=13;}
	$params['style'].='pointer-events: none;font-size:0.8rem;border-right:0px !important;border-top-right-radius: 0px;border-bottom-right-radius: 0px;';
	if(isset($params['displayname'])){$dname=$params['displayname'];}
	else{$dname=ucwords(trim(str_replace('_',' ',$name)));}
	if(!isset($params['placeholder'])){$params['placeholder']=$dname;}
	$params['value']=buildFormValueParam($name,$params);
	$onclick="return wacss.latlon('{$params['id']}','{$params['id']}_ico');";
	if(isset($params['viewonly']) || isset($params['readonly']) || isset($params['disabled'])){
		$onclick="return false;";
	}	
	$params['pattern']='\[[+-]?[0-9]*\.?[0-9]+,[+-]?[0-9]*\.?[0-9]+\,[0-9\.]+?\,[0-9]+?\]';
	$params['title']='[latitude,longitude,accuracy,trycount] - click to set';
	$params['data-tip']='Click to set a location';
	$params['data-tip_position']='bottom';
	unset($params['onclick']);
	$atts = setTagAttributes($params);
	$tag.='<div';
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	elseif(isset($params['data-displayif'])){
		$tag .= ' data-displayif="'.$params['data-displayif'].'"';
		unset($params['data-displayif']);
	}
	$tag .=<<<ENDOFTAG
	><div style="display:inline-flex;align-items: center;width:{$params['width']}px;";>
	<input type="text" class="{$params['class']}" {$atts}  value="{$params['value']}" >
	<button type="button" onclick="{$onclick}" data-lat="{$latlon[0]}" data-lon="{$latlon[1]}" class="btn"><span class="icon-map-marker" id="{$params['id']}_ico"></span></button>
</div>
</div>
ENDOFTAG;
	return $tag;
}
//---------- begin FUNCTION buildFormMultiInput-------------------
/**
* @describe creates an HTML multi-input control - a control where the user enters the tval and is stores as a JSON string with the dval as the key and their entry as the value
* @param name string
* @param opts array  tval/dval pairs. tval will be stored in the json
* @param params array
* 	[-column-width] str - sets column-width on column div
* @return JSON string
* @usage echo buildFormMultiInput('states',array('carrots','peas','oranges'),$params);
*/
function buildFormMultiInput($name,$opts=array(),$params=array()){
	if(isset($params['name'])){$name=$params['name'];}
	$name=preg_replace('/[\[\]]+$/','',$name);
	if(!is_array($opts) || !count($opts)){return 'buildFormMultiInput Error: no opts';}
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['group'])){$params['group']=$params['-formname'].'_'.$name.'_group';}
	$params['value']=buildFormValueParam($name,$params,1);
	//remove any characters in width
	$params['width']=preg_replace('/[^0-9]+/','',$params['width']);
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	//return printValue($params);
	if(count($dvals)==1){
		$tag  = '<div id="'.$params['id'].'"';	
	}
	else{
		if(isset($params['-column-width'])){
			$tag  = '<div id="'.$params['id'].'" style="column-count:'.$params['width'].';column-width:'.$params['-column-width'].';"';
		}
		elseif(isset($params['-stretch'])){
			$tag  = '<div id="'.$params['id'].'" style="column-count:'.$params['width'].';width:'.$params['-stretch'].';"';
		}
		else{
			$tag  = '<div id="'.$params['id'].'" style="column-count:'.$params['width'].';"';
		}
		
	}
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	if(isset($params['display'])){
		$tag .= ' data-display="'.$params['display'].'"';
		unset($params['display']);
	}
	elseif(isset($params['data-display'])){
		$tag .= ' data-display="'.$params['data-display'].'"';
		unset($params['data-display']);
	}
	$tag .='>'.PHP_EOL;
	$style=count($opts) > 4?'width:100%;':'';
	unset($params['width']);
	
	//data-inputwidth
	$inputwidth='60px';
	if(isset($params['data-inputwidth'])){
		$inputwidth=$params['data-inputwidth'];
	}
	//data-inputafter
	$inputafter=0;
	if(isset($params['data-inputafter']) && $params['data-inputafter']){$inputafter=1;}
	//loop through opts
	foreach($opts as $tval=>$dval){
		$tag.='<div style="display: flex;justify-content: flex-start;align-items: flex-end;width:100%;padding:'.$inputpadding.';break-inside: avoid;page-break-inside: avoid;-webkit-column-break-inside: avoid;">';
		if($inputafter==1){
			$tag.='<label class="multiinput_text w_nowrap">'.$dval.'</label>';
		}
		$tag.='<input name="'."{$name}>{$tval}".'" type="text" style="border:0px;border-bottom:2px solid #CCC;width:'.$inputwidth.';border-radius:0px;padding-bottom:0px;margin:0 2px;" value="'.$params['value'][$tval].'" >';
		if($inputafter==0){
			$tag.='<label class="multiinput_text w_nowrap">'.$dval.'</label>';
		}
		$tag.='</div>';
	}
	$tag .= '</div>'.PHP_EOL;
	return $tag;
}
//---------- begin FUNCTION buildFormMultiSelect-------------------
/**
* @describe creates an HTML multi-select control
* @param name string
* @param pairs array  key=>value pairs
* @param params array
* @return string
* @usage echo buildFormMultiSelect('states',$pairs,$params);
*/
function buildFormMultiSelect($name,$pairs=array(),$params=array()){
	$name=preg_replace('/[\[\]]+$/','',$name);
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	$params['width']=isNum($params['width'])?$params['width']:200;
	$params['-checkall']=isset($params['-checkall'])?$params['-checkall']:'Select All';
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['group'])){$params['group']=$params['-formname'].'_'.$name.'_group';}
	//check for size
	$params['-size']='';
	switch(strtolower($params['-size'])){
    	case 'sm':
    	case 'small':
			$params['-size']='w_btn-sm';
		break;
		case 'lg':
		case 'large':
			$params['-size']='w_btn-lg';
		break;
	}
	$mid=$name.'_options';
	$params['value']=buildFormValueParam($name,$params,1);
	if(isset($params['-formname'])){$mid .= "_{$params['-formname']}";}
	$icon=isset($checked_cnt) && $checked_cnt>0?'icon-checkbox':'icon-checkbox-empty';
	if(isset($params['displayname'])){$dname=$params['displayname'];}
	else{$dname=ucwords(trim(str_replace('_',' ',$name)));}
	$dname="-- {$dname} --";
	//class
	$class='';
	if(isset($params['class'])){$class=str_replace('w_form-control','',$params['class']);}
	if(isset($params['size'])){
		switch(strtolower($params['size'])){
	    	case 'small':$class='w_small';break;
	    	case 'smaller':$class='w_smaller';break;
	    	case 'smallest':$class='w_smallest';break;
	    	case 'tiny':$class='w_tiny';break;
	    	case 'big':$class='w_big';break;
	    	case 'bigger':$class='w_bigger';break;
	    	case 'biggest':$class='w_biggest';break;
	    	case 'huge':$class='w_huge';break;
	    	default:$class='';break;
		}
	}
	if(strlen($class)){$class=' '.trim($class);}
	$dropdown_classid=$params['id'].'_dropdown';
	$checked_cnt=0;
	$litags='';
	$checked_vals=array();
	foreach($pairs as $tval=>$dval){
		$id=$params['id'].'_'.$tval;
    	$litags .= '		<div style="display:flex;justify-content:flex-start;align-items:center;">';
    	if(!isNum($tval) && $tval=='--'){
			$litags .= '--------</div>'.PHP_EOL;
			continue;
		}
    	$litags .= '<input data-id="'.$params['id'].'" data-group="'.$params['group'].'" id="'.$id.'" data-type="checkbox" type="checkbox" name="'.$name.'[]" value="'.$tval.'"';
    	if((isset($params['required']) && $params['required']) || (isset($params['_required']) && $params['_required'])){$litags.=' data-required="1"';}
    	//add class
		$class='';
		if(isset($params["{$tval}_class"])){$class=$params["{$tval}_class"];}
		elseif(isset($params["{$dval}_class"])){$class=$params["{$dval}_class"];}
		elseif(isset($params['class'])){$class=$params['class'];}
		if(strlen($class)){
			$litags .= ' class="'.$class.'"';
		}
    	$onclick="formSetMultiSelectStatus(this);";
    	if(isset($params['onchange']) && strlen($params['onchange'])){
			$onclick .= $params['onchange'];
		}
		elseif(isset($params['onclick']) && strlen($params['onclick'])){
        	$onclick .= $params['onchange'];
		}
		$litags .= ' onclick="'.$onclick.'"';
    	if(in_array($tval,$params['value'])){
        	$litags .= ' checked';
        	$checked_cnt++;
        	$checked_vals[]=$dval;
		}
    	$litags .= ' ><label for="'.$id.'" style="margin-left:10px;white-space:nowrap"> '.$dval.'</label></div>'.PHP_EOL;
	}


	$tag='';
	$tag .= '<div class="dropdown"';
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	$tag .= '>'.PHP_EOL;
	$tag .= ' 	<button data-dname="'.$dname.'"';
	if(isset($params['width']) && isNum($params['width'])){
		$tag .=' style="max-width:'.$params['width'].'px;overflow:hidden;text-overflow:ellipsis;"';
	}
	$button_name=$dname;
	if(count($checked_vals)){
		$cnt=count($checked_vals);
		$button_name="({$cnt}) ".implode(', ',$checked_vals);
	}
	$tag .= ' data-toggle="dropdown" id="'.$params['id'].'_button" class="btn '.$params['-size'].'" type="button">'.$button_name.'</button>'.PHP_EOL;
	$onclose='';
	if(isset($params['onblur']) && strlen($params['onblur'])){
		$onclose = $params['onblur'];
	}
	elseif(isset($params['onleave']) && strlen($params['onleave'])){
    	$onclose = $params['onleave'];
	}
	elseif(isset($params['onclose']) && strlen($params['onclose'])){
    	$onclose = $params['onclose'];
	}
	if(strlen($onclose)){
		$onclose="if(commonCloseDropdownMenu(this)){{$onclose}}";
	}
	else{
		$onclose="commonCloseDropdownMenu(this);";
	}
	$tag .= ' 	<div id="'.$params['id'].'_options" class="dropdown-menu" onmouseleave="'.$onclose.'">'.PHP_EOL;
	$tag .= <<<ENDOFDIV
		<div class="align-center" style="white-space:nowrap;font-size:1.0rem;line-height:1.2;color:#343a4080;display:flex;">
			<div style="flex:1">{$dname}</div>
			<input type="checkbox" data-id="{$params['id']}" onclick="checkAllElements('data-group','{$params['group']}',this.checked);formSetMultiSelectStatus(this);">
		</div>
ENDOFDIV;
	$tag .= $litags;
	$tag .= '	</div>'.PHP_EOL;
	$tag .= '</div>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormRadio--------------------------------------
/**
* @describe creates an HTML Form radio button
* @param name string
*	The name of the radio
* @param opts array tval/dval pairs to display
* @param params array - options
*	[-values] array - an array of tval values to mark as checked
*	[width] how many to show in a row - default to 6
*  	[-icon] string  - icon to use. Valid options are mark, blank, cancel, close, circle, minus, plus, star. Defaults to mark
*   [size] string - size of button. Valid options are small, smaller, smallest, tiny, big, bigger, biggest, huge.
* @return
*	HTML Form radio button for each pair passed in
* @usage buildFormRadio('color',$opts);
*/
function buildFormRadio($name, $opts=array(), $params=array()){
	$params['-type']='radio';
	return buildFormRadioCheckbox($name, $opts, $params);
}
//---------- begin function buildFormRadioCheckbox
/**
* @exclude  - this function in only used internally
* $opts options are as follows:
* 	tval=>dval
* 	Optional way to customize - only display is not optional
* 	tval=>array(
* 		'display'=>'...',
* 		'format'=>'...', 
*		'color'=>'#...',
*		'bgcolor'=>'#...',
*		'checked_color'=>'#...',
*		'checked_bgcolor'=>'#...',
*		'image'=>'/...'
* 	)
* @params options are as follows:
* 	[name] name
* 	[-formname]
* 	[id]
* 	[group]
* 	[-format] top, bottom, topbottom, left, right, oval, button. Defaults to right
* 	[-display] flex or column. Defaults to column
* 	[-values]
* 	[displayif]
* 	[display]
* 	[requiredif]
* 	[readonly]
* 	[disabled]
*/
function buildFormRadioCheckbox($name, $opts=array(), $params=array()){
	//return printValue($params);
	if(isset($params['data-type'])){$params['-type']=$params['data-type'];}
	if(!isset($params['-type'])){return 'buildFormRadioCheckbox Error: no type';}
	if(!strlen(trim($name))){return 'buildFormRadioCheckbox Error: no name';}
	if(isset($params['name'])){$name=$params['name'];}
	$name=preg_replace('/[\[\]]+$/','',$name);
	if(!is_array($opts) || !count($opts)){return 'buildFormRadioCheckbox Error: no opts';}
	if(isset($params['data-formname'])){$params['-formname']=$params['data-formname'];}
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['data-id'])){$params['-id']=$params['data-id'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['data-group'])){$params['-group']=$params['data-group'];}
	if(!isset($params['group'])){$params['group']=$params['-formname'].'_'.$name.'_group';}
	//format: top, bottom, topbottom, left, right, oval, button
	if(isset($params['data-format'])){$params['-format']=$params['data-format'];}
	if(!isset($params['-format'])){$params['-format']='right';}
	//display:  flex or column
	if(isset($params['data-display'])){$params['-display']=$params['data-display'];}
	if(!isset($params['-display'])){$params['-display']=count($opts)==1?'flex':'column';}
	if(isset($params['data-style'])){$params['style']=$params['data-style'];}
	if(!isset($params['style'])){$params['style']='';}
	if(isset($params['data-values'])){$params['value']=$params['data-values'];}
	if(strlen($params['style']) && !stringEndsWith($params['style'],';')){$params['style'].=';';}
	$params['value']=buildFormValueParam($name,$params,1);
	switch(strtolower($params['-display'])){
		case 'flex':
			$params['style'].='display:flex; justify-content: flex-start;flex-wrap:wrap;align-items:flex-start;';
		break;
		case 'column':
			if(isset($params['-column-width'])){
				$params['style'].='height:auto;column-count:'.$params['width'].';column-width:'.$params['-column-width'].';';
			}
			elseif(isset($params['-stretch'])){
				$params['style'].='height:auto;column-count:'.$params['width'].';width:'.$params['-stretch'].';';
			}
			else{
				$params['style'].='height:auto;column-count:'.$params['width'].';';
			}
		break;
	}
	$rtn='';
	$rtn.='<div style="'.$params['style'].'"';
	//displayif
	if(isset($params['displayif'])){
		$rtn .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	if(isset($params['display'])){
		$rtn .= ' data-display="'.$params['display'].'"';
		unset($params['display']);
	}
	elseif(isset($params['data-display'])){
		$rtn .= ' data-display="'.$params['data-display'].'"';
		unset($params['data-display']);
	}
	$rtn.='>'.PHP_EOL;
	$input_name=$params['-type']=='radio'?$name:"{$name}[]";
	$i=0;
	foreach($opts as $tval=>$dval){
		$display='';
		$color='';
		$bgcolor='';
		$checked_color='';
		$checked_bgcolor='';
		$image='';
		$format=$params['-format'];
		$displayif='';
		//look for data-checked_color_10 for backward compatibility
		if(isset($params["data-color_{$tval}"])){
			$color=$params["data-color_{$tval}"];
		}
		if(isset($params["data-bgcolor_{$tval}"])){
			$bgcolor=$params["data-bgcolor_{$tval}"];
		}
		if(isset($params["data-checked_color_{$tval}"])){
			$checked_color=$params["data-checked_color_{$tval}"];
		}
		if(isset($params["data-checked_bgcolor_{$tval}"])){
			$checked_bgcolor=$params["data-checked_bgcolor_{$tval}"];
		}
		//check for dval array for custom options
		if(is_array($dval)){
			//display
			if(isset($dval['dval'])){$display=$dval['dval'];}
			elseif(isset($dval['display'])){$display=$dval['display'];}
			//color
			if(isset($dval['color'])){$color=$dval['color'];}
			//bgcolor
			if(isset($dval['bgcolor'])){$color=$dval['bgcolor'];}
			//checked_color
			if(isset($dval['checked_color'])){$checked_color=$dval['checked_color'];}
			//checked_bgcolor
			if(isset($dval['checked_bgcolor'])){$checked_color=$dval['checked_bgcolor'];}
			//displayif
			if(isset($dval['displayif'])){$displayif=$dval['displayif'];}
			//image
			if(isset($dval['image'])){$image=$dval['image'];}
			//icon
			if(isset($dval['icon'])){$icon=$dval['icon'];}
			//format
			if(isset($dval['format'])){$format=$dval['format'];}
		}
		else{
			$display=$dval;
		}

		if(!strlen($display)){$display=$tval;}
		$opt_id=$params['id'].'_'.$tval;
		$opt_id=preg_replace('/[^a-z0-9\_]+/i','',$opt_id);
		//topbottom
		if(strtolower($format)=='topbottom'){
			$format=$i%2==0?'top':'bottom';
		}
		$rtn.='	<div data-type="checkradio" data-display="initial" data-format="'.$format.'"';
		if(strlen($color) && stringBeginsWith($color,'w_')){
			$rtn.=' data-color="'.$color.'"';
		}
		if(strlen($image)){
			$rtn.=' data-image="1"';
		}

		//displayif
		if(isset($params["{$tval}_displayif"])){
			$rtn .= ' data-displayif="'.$params["{$tval}_displayif"].'"';
			unset($params["{$tval}_displayif"]);
		}
		elseif(strlen($displayif)){
			$rtn .= ' data-displayif="'.$displayif.'"';
		}
		//hideif
		if(isset($params["{$tval}_hideif"])){
			$rtn .= ' data-hideif="'.$params["{$tval}_hideif"].'"';
			unset($params["{$tval}_hideif"]);
		}
		$rtn.='>'.PHP_EOL;
		$rtn.='		<input style="width:1px;height:1px;appearance:none;display:block;align-self:center;" type="'.$params['-type'].'" id="'.$opt_id.'" name="'.$input_name.'" value="'.$tval.'"';
		//required
		if(isset($params['required']) && $params['required']){
			//$rtn .= ' required="required"';
			$rtn .= ' data-required="1"';
		}
		elseif(isset($params['_required']) && $params['_required']){
			//$rtn .= ' required="required"';
			$rtn .= ' data-required="1"';
		}
		//onchange
		if(isset($params['onchange']) && strlen($params['onchange'])){
			$rtn .= ' onchange="'.$params['onchange'].'"';
		}
		//onclick
		if(isset($params['onclick']) && strlen($params['onclick'])){
			$rtn .= ' onclick="'.$params['onclick'].'"';
		}
		//data-
		foreach($params as $k=>$v){
			if(stringBeginsWith($k,'data-')){
				$rtn .=" {$k}=\"{$v}\"";
			}
		}
		//requiredif
		if($params['requiredif']){
			$rtn .= ' data-requiredif="'.$params['requiredif'].'"';
			//unset($params['requiredif']);
		}
		//readonlyif
		if($params['data-readonlyif']){
			$rtn .= ' data-readonlyif="'.$params['data-readonlyif'].'"';
			//unset($params['readonlyif']);
		}
		//checked?
		if(in_array($tval,$params['value'])){
    		$rtn .= ' checked';
    		$checked_cnt++;
		}
		//readonly?
		if(isset($params['readonly'])){
			$rtn .= ' onclick="return false;"';
		}
		//disabled?
		if(isset($params['disabled'])){
			$rtn .= ' disabled="disabled"';
		}
		$rtn.=' >'.PHP_EOL;
		//styles and classes
		$classes=array();
		$styles=array();
		if(strlen($color) && !stringBeginsWith($color,'w_')){
			$styles[]="--color:{$color}";
		}
		if(strlen($bgcolor) && !stringBeginsWith($bgcolor,'w_')){
			$styles[]="--bgcolor:{$bgcolor}";
		}
		if(strlen($checked_color) && !stringBeginsWith($checked_color,'w_')){
			$styles[]="--checked_color:{$checked_color}";
		}
		if(strlen($checked_bgcolor) && !stringBeginsWith($checked_bgcolor,'w_')){
			$styles[]="--checked_bgcolor:{$checked_bgcolor}";
		}
		if(strlen($image)){
			$styles[]="background-image:url('{$image}')";
		}
		if(isset($params["{$tval}_style"])){
			$styles[]=$params["{$tval}_style"];
		}
		if(isset($params["{$tval}_class"])){
			$classes[]=$params["{$tval}_class"];
		}
		//label
		$rtn.='		<label for="'.$opt_id.'"';
		if(count($styles)){
			$stylestr=implode(';',$styles);
			$rtn .= ' style="'.$stylestr.'"';
		}
		if(count($classes)){
			$classestr=implode(';',$classes);
			$rtn .= ' class="'.$classestr.'"';
		}
		$rtn.= '>'.$display.'</label>'.PHP_EOL;
		$rtn.='	</div>'.PHP_EOL;
		$i+=1;
	}
	$rtn.='</div>'.PHP_EOL;
	return $rtn;
}
//---------- begin function buildFormText--------------------
/**
* @describe creates an HTML text field
* @param name string
* @param params array
* @return string
* @usage echo buildFormText('name',$params);
*/
function buildFormText($name,$params=array()){
	//return $name.printValue($params);
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['inputtype'])){$params['-type']=$params['inputtype'];}
	if(!isset($params['-type'])){$params['-type']='text';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	$params['value']=buildFormValueParam($name,$params);
	//ksort($params);return printValue($params);
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['displayif'])){$params['data-displayif']=$params['displayif'];}
	$params['name']=$name;
	//ksort($params);return printValue($params);
	if(isset($params['viewonly'])){
		return '<div class="w_viewonly" id="'.$params['id'].'">'.nl2br($params['value']).'</div>'.PHP_EOL;
	}
	$tag = '	<input type="'.$params['-type'].'" value="'.encodeHtml($params['value']).'"';
	$tag .= setTagAttributes($params);
	//check for tvals and build a datalist if present
	$selections=getDBFieldSelections($params);
	if(isset($selections['tvals']) && is_array($selections['tvals']) && count($selections['tvals'])){
		$list_id=$name.'_datalist';
        $tag .= ' list="'.$list_id.'"';
        $tag .= ' >'.PHP_EOL;
        $tag .= '	<datalist id="'.$list_id.'">'.PHP_EOL;
		$cnt=count($selections['tvals']);
		for($x=0;$x<$cnt;$x++){
			$tval=$selections['tvals'][$x];
			$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
			$tag .= '	<option value="'.$tval.'">'.$dval.'</option>'.PHP_EOL;
		}
	    $tag .= '	</datalist>'.PHP_EOL;
	}
	else{
		$tag .= ' >'.PHP_EOL;
		return $tag;
	}
	return $tag;
}
//---------- begin function buildFormTextarea--------------------
/**
* @describe creates an HTML textarea field
* @param name string
* @param params array
* [-background]
* @return string
* @usage echo buildFormTextarea('name',$params);
*/
function buildFormTextarea($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['wrapper_id'])){$params['wrapper_id']=$params['id'].'_wrapper';}
	if(!isset($params['wrapper_style'])){$params['wrapper_style']='';}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	$params['value']=buildFormValueParam($name,$params);
	//echo '<textarea class="textarea">'.$params['value'].'</textarea>';exit;
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['displayif'])){$params['data-displayif']=$params['displayif'];}
	if(isset($params['-bootstrap'])){
		unset($params['width']);
	}
	if(isset($params['height'])){
		if(isNum($params['height'])){$params['height'].='px';}
		if(!stringContains($params['style'],'height')){
			$params['style'].=";height:{$params['height']};";
		}
	}
	if(isset($params['behavior']) && strlen($params['behavior'])){
		$params['data-behavior']=strtolower($params['behavior']);
		
		switch(strtolower($params['behavior'])){
	    	case 'nowrap':
	    		$params['behavior']='';
				$params['wrap']="off";
			break;
			case 'autogrow':
				if(!isset($params['wrap'])){$params['wrap']="soft";}
			break;
			case 'editor':
			case 'tinymce':
			case 'nicedit':
			case 'richtext':
			case 'wysiwyg':
			case 'quill':
				$params['wrapper_style'].='background:#FFF;';
				$params['data-behavior']="quill";
				loadExtrasJs(array('quill'));
				$params['wrap']="off";
			break;
			default:
				loadExtrasJs(array('codemirror'));
				$params['wrapper_style'].='background:#FFF;';
			break;
		}
	}
	if(!isset($params['wrap'])){$params['wrap']="off";}
	$params['name']=$name;
	$tag='';
	$tag.='<div ';
	foreach($params as $k=>$v){
		if(preg_match('/^wrapper_(.+)$/',$k,$m)){
			$tag.=" {$m[1]}=\"{$v}\"";
			unset($params[$k]);
		}
	}
	$tag.='>'.PHP_EOL;
	if(isset($params['data-behavior']) && $params['data-behavior']=='autogrow' && isset($params['disabled'])){
		$params['style']="padding:10px 15px;border:1px solid #ccc;background-color:inherit;border-radius:4px;";
		$tag .= '	<div';
		$tag .= setTagAttributes($params);
		$tag .= ' >';
		if(isset($params['-fixms'])){
			$params['value']=fixMicrosoft($params['value']);
		}
		$tag .= nl2br(encodeHtml($params['value']));
		$tag .= '</div>'.PHP_EOL;
	}
	else{
		$tag .= '	<textarea';
		$tag .= setTagAttributes($params);
		$tag .= ' >';
		if(isset($params['-fixms'])){
			$params['value']=fixMicrosoft($params['value']);
		}
		$tag .= encodeHtml($params['value']);
		$tag .= '</textarea>'.PHP_EOL;
	}
	$tag.='</div>'.PHP_EOL;
	//return printValue($params);
	return $tag;
}
//---------- begin function buildFormTime-------------------
/**
* @describe creates an HTML time control
* @param name string - field name
* @param params array
*	[-formname] string - specify the form name - defaults to addedit
*	[-interval] integer - specify the time interval. 1,5,10,15,30,60 - defaults to 30
* 	[-begin] string - begin time in military format - defaults to 00:00
* 	[-end] string - end time in military format - defaults to 24:00
*	[-tformat] string - specify the time format of the true value. defaults to H:i (15:05)
*	[-dformat] string - specify the time format of the display value. defaults to g:i a (3:05 pm)
*	[-value] string - specify the current value
*	[-required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
*	[data-icon] string - icon to show on right side. defaults to &#128348;
* @return string - html time control
* @usage echo buildFormTime('mytime');
*/
function buildFormTime($name,$params=array()){
	$params['data-enableTime']=1;
	$params['data-noCalendar']=1;
	//return printValue($params);
	return buildFormDate($name,$params);
	
}
//---------- begin function buildFormTranslate--------------------
/**
* @describe creates a language Selection
* @param name string
* @param params array
* @return string
* @usage echo buildFormTranslate($params);
*/
function buildFormTranslate($params=array()){
	switch(strtolower($params['-framework'])){
		case 'bulma':
			return buildFormTranslateBulma($params);
		break;
	}
	loadExtras('translate');
	if(!isset($params['-icon'])){$params['-icon']='icon-translate';}
	if(!isset($params['-class'])){$params['-class']='dropdown';}
	if(!isset($params['-text'])){$params['-text']='Lang';}
	if(!isset($params['-showflag'])){$params['-showflag']=1;}
	$locales=translateGetLocalesUsed();
	//return printValue($locales);
	$tag='<div class="nav w_formtranslate" style="background:transparent;">'.PHP_EOL;
  	$tag.='	<ul>'.PHP_EOL;
    $tag.='		<li><a name="language" class="'.$params['-class'].'"><span class="'.$params['-icon'].'"></span> '.$params['-text'].'</a>'.PHP_EOL;
    $tag.='			<ul style="left: calc(-100% - 100px);padding:10px 0;background:#fff;box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;min-width:auto;right:0px;">'.PHP_EOL;
    //remove dropdown from class
    $params['-class']=str_replace('dropdown','',$params['-class']);
	$opts=array();
	foreach($locales as $locale){
		if($params['-showflag']==1){
			$img='<img src="'.$locale['flag4x3'].'" alt="Language: '.$locale['name'].'" style="height:16px;width:auto;border-radius:2px;" >';
			$lang="{$img} {$locale['name']}";
		}
		else{
			$lang=$locale['name'];
		}
		$tag.='		<li><a class="'.$params['-class'].'" href="?_locale_='.$locale['locale'].'">'.$lang.'</a></li>'.PHP_EOL;
	}
	$tag.='			</ul>'.PHP_EOL;
	$tag.='		</li>'.PHP_EOL;
	$tag.='	</ul>'.PHP_EOL;
	$tag.='</div>'.PHP_EOL;
	return $tag;
}
/**
* @exclude  - this function is for internal use only
*/
function buildFormTranslateBulma($params=array()){
	loadExtras('translate');
	if(!isset($params['-icon'])){$params['-icon']='icon-translate';}
	if(!isset($params['-class'])){$params['-class']='dropdown';}
	if(!isset($params['-text'])){$params['-text']='Lang';}
	if(!isset($params['-showflag'])){$params['-showflag']=1;}
	$locales=translateGetLocalesUsed();
	$locale=$params['-locale'] ?? $_SESSION['REMOTE_LANG'] ?? $_SERVER['REMOTE_LANG'] ?? 'en-us';
	$info=translateGetLocaleInfo($locale);
	//echo printValue($info);exit;
	$tag='';
	$tag.='<div class="navbar-item has-dropdown is-hoverable">'.PHP_EOL;
	if($params['-showflag']==1){
		$img='<img src="'.$info['flag4x3'].'" alt="Language: '.$info['lang'].'" style="height:16px;width:auto;border-radius:2px;margin-right:5px;" >';
		$lang="{$img} {$info['lang']}";
	}
	else{
		$lang=$info['lang'];
	}
	$tag.=' 	<a class="navbar-link">'.$lang.'</a>'.PHP_EOL;
	$tag.=' 	<div class="navbar-dropdown is-right">'.PHP_EOL;
	foreach($locales as $locale){
		if($params['-showflag']==1){
			$img='<img src="'.$locale['flag4x3'].'" alt="Language: '.$locale['name'].'" style="height:16px;width:auto;border-radius:2px;margin-right:5px;" >';
			$lang="{$img} {$locale['name']}";
		}
		else{
			$lang=$locale['name'];
		}
		$tag.='		<a class="navbar-item" href="?_locale_='.$locale['locale'].'">'.$lang.'</a>'.PHP_EOL;
	}
    $tag.='    </div>'.PHP_EOL;
	$tag.='</div>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormWhiteboard --------------------------------------
/**
* @describe creates an HTML whiteboard field the works on mobile and PC - user can use the mouse or finger to draw
* @param name string
*	The name of the field
* @param params array
*	options are as follows
*	- [displayname]. Defaults to "Please Sign Below:"
*	- [width]. Defaults to 300
*	- [height]. Defaults to 75
* 	- [style] - set width or height in style to override defaults
* @return HTML Form signature field the works on mobile and PC - user can use the mouse or finger to sign
* @usage buildFormWhiteboard('board',$params);
*/
function buildFormWhiteboard($name,$params=array()){
	$params['data-behavior']="whiteboard";
	$rtn=buildFormHidden("{$name}_inline",array('value'=>1));
	$rtn.=buildFormHidden("{$name}_autonumber",array('value'=>1));
	//return printValue($params);
	$rtn.=buildFormTextarea($name,$params).buildOnLoad("wacss.init();");
	return $rtn;
}
//---------- begin function buildFormYesNo--------------------
/**
* @describe creates an Button Select field for Yes/No
* @param name string
* @param params array
* @return string
* @usage echo buildFormYesNo('yesno',$params);
*/
function buildFormYesNo($name='yesno',$params=array()){
	$opts=array(
		'Y'=>'Yes',
		'N'=>'No'
	);
	$params['Y_class']='w_green';
	$params['N_class']='w_red';
	return buildFormButtonSelect($name,$opts,$params);
}
//---------- begin function buildFormToggleButton--------------------
/**
* @describe creates an toggle Button Field for two choices only -  yes/no, Y/N, on/off, T/F, etc
*	Note: only the first tval is stored so default your field schema to the off value - yesno char(1) NOT NULL Default 'n'
* @param name string
* @param opts array - specifies the value when on and off. for example, array('y'=>'Yes','n'=>'No')
* @param params array
*	[-formname] string - specify the form name - defaults to addedit
*	[-format] string - specify toggle format - flip or round - defaults to round
*	[-value] string - specify the current value
*	[-required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
* @return string
* @usage echo buildFormToggleButton('yesno',array('Y'=>'Yes','N'=>'No'),$params);
*/
function buildFormToggleButton($name,$opts=array(),$params=array()){
	if($params['requiredif']){$params['data-requiredif']=$params['requiredif'];}
	if(!is_array($opts) || !count($opts)){
    	$opts=array('y'=>'Yes','n'=>'No');
	}
	$tvals=array_keys($opts);
	$dvals=array_values($opts);
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	$params['value']=buildFormValueParam($name,$params);
	if(strtolower($params['value'])==strtolower($tvals[0])){$checked=' checked';}
	else{$checked='';}
	if($params['required']){$required=' required';}
	else{$required='';}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	switch(strtolower($params['-format'])){
    	case 'flip':$format='flip';break;
    	default:$format='round';break;
	}
	$onclick='';
	if(isset($params['-onclick'])){
    	$onclick=' onclick="'.$params['-onclick'].'"';
	}
	if(isset($params['data-readonlyif'])){
    	$onclick.=' data-readonlyif="'.$params['data-readonlyif'].'"';
	}
	$title='';
	if(isset($params['title'])){
    	$title=' title="'.$params['title'].'"';
	}
	if(isset($params['displayif'])){
		$title .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	$class='';
	if(isset($params['class'])){
    	$class=' class="'.$params['class'].'"';
	}
	$style='';
	if(isset($params['style'])){
    	$style=' style="'.$params['style'].'"';
	}

	$tag=<<<ENDOFTAG
	<div class="switch"{$title}>
        <input id="{$params['id']}" name="{$name}" value="{$tvals[0]}" {$onclick} class="w_toggle w_toggle-{$format}" type="checkbox"{$required}{$checked}>
        <label for="{$params['id']}" data-on="{$dvals[0]}" data-off="{$dvals[1]}" {$class}{$style}></label>
    </div>
ENDOFTAG;
	return $tag;
}
//---------- begin function buildFormBegin-------------------
/**
* @describe creates a beginning HTML form tag
* @param action string
* @param params array
* @return string
* @usage echo buildFormBegin('/test');
*/
function buildFormBegin($action='',$params=array()){
	global $PAGE;
	if(!strlen($action)){$action="/{$PAGE['name']}";}
	if(!isset($params['-method'])){$params['-method']="POST";}
	if(!isset($params['-class'])){
		$params['-class']="w_form";
		if(isExtraCss('bootstrap')){$params['-class'] .= ' form-inline';}
		}
	if(!isset($params['-onsubmit'])){$params['-onsubmit']="return submitForm(this);";}
	$rtn='';
	$rtn .= '<form method="'.$params['-method'].'" action="'.$action.'"';
	if(isset($params['-name'])){$rtn .= ' name="'.$params['-name'].'"';}
	if(isset($params['-target'])){$rtn .= ' target="'.$params['-target'].'"';}
	if(isset($params['-multipart'])){$rtn .= ' enctype="multipart/form-data"';}
	elseif(isset($params['-enctype'])){$rtn .= ' enctype="'.$params['-enctype'].'"';}
	if(isset($params['-charset'])){$rtn .= ' accept-charset="'.$params['-charset'].'"';}
	$rtn .= ' class="'.$params['-class'].'" onsubmit="'.$params['-onsubmit'].'">'.PHP_EOL;
	if(isset($params['-auth_required']) && $params['-auth_required']){
		$rtn .= '	<input type="hidden" name="_auth_required" value="1">'.PHP_EOL;
	}
	foreach($params as $key=>$val){
		if(preg_match('/^\-/',$key)){continue;}
		if(is_array($val)){continue;}
		if(isXml($val)){continue;}
		if(strlen($val) > 255 && $key != '_fields'){continue;}
		$rtn .= '	<input type="hidden" name="'.$key.'" value="'.$val.'">'.PHP_EOL;
    	}
    //populate $_REQUEST array if _table and _id are set and _action equals EDIT
    if(isset($params['_table']) && isset($params['_id']) && isNum($params['_id']) && isset($params['_action']) && strtolower($params['_action'])=='edit'){
		$getopts=array('-table'=>$params['_table'],'_id'=>$params['_id']);
		if(isset($params['_fields'])){$getopts['-fields']=$params['_fields'];}
    	$rec=getDBRecord($getopts);
    	if(is_array($rec)){
        	foreach($rec as $key=>$val){
            	if(!isset($_REQUEST[$key])){$_REQUEST[$key]=$val;}
			}
		}
	}
	return $rtn;
	}
//---------- begin function buildFormField-------------------
/**
* @describe shortcut to getDBFieldTag
* @param tablename string
* @param fieldname string
* @param opts array
* @return string
* @usage echo buildFormField('_users','username');
*/
function buildFormField($tablename,$fieldname,$opts=array()){
	$opts['-table']=$tablename;
	if(preg_match('/^(.+?)\[\]$/',$fieldname,$m)){
		$opts['-field']=$m[1];
		$opts['name']=$fieldname;
	}
	else{$opts['-field']=$fieldname;}
	return getDBFieldTag($opts);
	}
//---------- begin function buildFormFile--------------------
/**
* @describe creates an HTML file upload field
* @param name string
* @param params array
*	[-formname] string - specify the formname this field is identified with. Defaults to addedit
*	[-icon] string - icon class to use. Defaults to 'icon-upload w_big w_danger'
*	[text] string - text to display. Defaults to 'file to upload'
*	[multiple] bool - allows multiple files
*	[requiredif] string - make required if another value is true
*	[name] string - override name
*	[id] string - specify id. Default will be given otherwise
*	[value] string - current value when in edit mode
*	[-noview] 0|1 - do not display the viewer. Defaults to true
*	[viewonly] 0|1 - viewonly mode. Defaults to false
*	[path] string - relative path to where to store the files. Defaults to '/files/{name}'
*	[autonumber] 0|1 - assign an autonumber to the file on upload to prevent overriding files with same name. 
*	[displayif] - only display if another value is true or selected. format is {field}:{value}
*	[onchange] string - javascript to run on change
*	[resize] string - size ({width}x{height}) to resize to on upload. Imagemagick must be installed on server. 
* @return string
* @usage echo buildFormFile('file',$params);
*/
function buildFormFile($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(!isset($params['-icon'])){$params['-icon']='icon-upload w_big w_danger';}
	if(!isset($params['text'])){
		if(isset($params['multiple'])){$params['text']='Upload Files';}
		else{$params['text']='Upload';}
	}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=preg_replace('/[^a-z0-9\-\_]+/','_',$params['-formname'].'_'.$name);}
	$params['value']=buildFormValueParam($name,$params);
	$params['name']=$name;
	if(isset($params['multiple']) && !stringEndsWith($params['name'],'[]')){
		$params['name'].='[]';
	}
	if(isset($params['multiple'])){$params['multiple']='multiple';}
	else{$params['multiple']='';}
	if(!isset($params['accept'])){$params['accept']='';}
	if(!isset($params['capture'])){$params['capture']='';}
	if(!isset($params['class'])){$params['class']='';}
	$capture='';
	$icon='';
	switch(strtolower($params['accept'])){
		case 'audio/*':
		case 'audio':
			switch(strtolower($params['capture'])){
				case '1':
				case 'true':
				case 'microphone':
					$capture='capture="microphone"';
				break;
			}
			$params['accept']='audio/*';
			if(strlen($params['value'])){
				$icon='<span style="align-self:center;" class="icon-music w_pointer" title="Click to preview" data-src="'.$params['value'].'" onclick="wacss.showAudio(this);"></span>';
			}
			else{
				$icon='<span style="align-self:center;" class="icon-music"></span>';
			}
		break;
		case 'video/*':
		case 'video':
			switch(strtolower($params['capture'])){
				case '1':
				case 'true':
				case 'camcorder':
					$capture='capture="camcorder"';
				break;
			}
			$params['accept']='video/*';
			if(strlen($params['value'])){
				$icon='<span style="align-self:center;" class="icon-video w_pointer" title="Click to preview" data-src="'.$params['value'].'" onclick="wacss.showVideo(this);"></span>';
			}
			else{
				$icon='<span style="align-self:center;" class="icon-video"></span>';
			}
		break;
		case 'image/*':
		case 'image':
			switch(strtolower($params['capture'])){
				case '1':
				case 'true':
				case 'user':
					$capture='capture="user"';
				break;
				case 'environment':
					$capture='capture="environment"';
				break;
				case 'camera':
					$capture='capture="camera"';
				break;
			}
			$params['accept']='image/*';
			if(strlen($params['value'])){
				$icon='<span style="align-self:center;" class="icon-image w_pointer" title="Click to preview" data-src="'.$params['value'].'" onclick="wacss.showImage(this);"></span>';
			}
			else{
				$icon='<span style="align-self:center;" class="icon-image"></span>';
			}
		break;
		case '.csv':
		case 'csv':
			$params['accept']='.csv, text/csv, application/csv, text/comma-separated-values, application/csv';
			$icon='<span style="align-self:center;" class="icon-file-txt"></span>';
		break;
		case '.pdf':
		case 'pdf':
			$params['accept']='.pdf';
			$icon='<span style="align-self:center;" class="icon-file-pdf2"></span>';
		break;
		case 'excel':
			$params['accept']='.xls, .xlsx, application/excel, application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			$icon='<span style="align-self:center;" class="icon-application-excel"></span>';
		break;
		default:
			$icon='<span style="align-self:center;" class="icon-attach"></span>';
		break;
	}
	$params['ori_value']=$params['value'];
	$viewer='';
	if(strlen($params['value'])){
		$val=encodeHtml($params['value']);
		$ext=getFileExtension($params['value']);
		$afile=$_SERVER['DOCUMENT_ROOT'].$params['value'];
		switch(strtolower($ext)){
			case 'mp3':
			case 'wav':
				$mime=getFileMimeType($afile);
				$viewer .= '<div style="margin:5px 1px"><audio controls="controls">'.PHP_EOL;
				$viewer .= '	<source src="'.$params['value'].'" type="'.$mime.'"  >'.PHP_EOL;
				$viewer .= '</audio></div>'.PHP_EOL;
			break;
			case 'mp4':
				$mime=getFileMimeType($afile);
				if(!isset($params['view_width'])){
					$params['view_width']=300;
				}
				if(!isset($params['view_height'])){
					$params['view_height']=300;
				}
				$viewer .= '<div style="margin:5px 1px;"><video height="36" onmouseover="this.setAttribute(\'height\','.$params['view_height'].');" onmouseout="this.setAttribute(\'height\',36);" controls="controls">'.PHP_EOL;
				$viewer .= '	<source src="'.$params['value'].'" type="'.$mime.'"  >'.PHP_EOL;
				$viewer .= '</video></div>'.PHP_EOL;
			break;
			case 'gif':
			case 'png':
			case 'jpg':
			case 'jpeg':
			case 'svg':
			case 'avif':
				$mime=getFileMimeType($afile);
				if(!isset($params['view_width'])){
					$params['view_width']=300;
				}
				if(!isset($params['view_height'])){
					$params['view_height']=300;
				}
				$viewer .= '<div style="margin:5px 1px;max-width:'.$params['view_width'].'px;max-height:'.$params['view_height'].'px;"><a class="w_link w_lblue" href="'.$val.'" target="_blank"><img style="border-radius:3px;max-width:'.$params['view_width'].'px;max-height:'.$params['view_height'].'px;" src="'.$params['value'].'" ></a>'.PHP_EOL;
				$viewer .= '</div>'.PHP_EOL;
			break;
			default:
				$viewer .= '	<a class="w_link" href="'.$val.'" target="_blank"><span class="icon-upload"></span> '.$val.'</a>'.PHP_EOL;
			break;
		}
	}
	if(strlen($params['value'])){
		$afile=$_SERVER['DOCUMENT_ROOT'].$params['value'];
		//return $afile.printValue($params);
		if(file_exists($afile)){
			$arr=array(
				array(
					'name'=>$params['value'],
					'size'=>filesize($afile)
				)
			);
		}
		else{
			$arr=array(
				array(
					'name'=>$params['value'],
					'size'=>0
				)
			);
		}
		$params['value']=encodeJSON($arr);
	}
	//
	if(isset($_REQUEST['viewonly']) && $_REQUEST['viewonly']==1){$params['viewonly']=1;}
	if(isset($params['viewonly']) && $params['viewonly']==1){	
		if(!strlen($params['value'])){return '';}
		return $viewer;
	}
	elseif(isset($params['readonly']) && $params['readonly']==1){	
		if(!strlen($params['value'])){return '';}
		return $viewer;
	}
	$tag='<div style="display:inline-flex;" data-display="inline-flex"';
	//displayif
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
	}
	//hideif
	if(isset($params['hideif'])){
		$tag .= ' data-hideif="'.$params['hideif'].'"';
	}
	$tag.='>'.PHP_EOL;
	//prev value
	if(strlen($params['value'])){
		$tag .= '	<input type="hidden" name="'.$name.'_prev" value="'.$params['ori_value'].'">'.PHP_EOL;
	}
	//set path of where to store this file in
	if(!isset($params['path'])){
    	if(isset($params['defaultval']) && strlen($params['defaultval'])){$params['path']=$params['defaultval'];}
    	elseif(isset($params['data-path']) && strlen($params['data-path'])){$params['path']=$params['data-path'];}
    	elseif(isset($_REQUEST["{$name}_path"]) && strlen($_REQUEST["{$name}_path"])){$params['path']=$_REQUEST["{$name}_path"];}
    	else{$params['path']="/files/{$params['name']}";}
	}
	//create path if it does not exist
	$apath=$_SERVER['DOCUMENT_ROOT'].$params['path'];
	if(!is_dir($apath)){
		buildDir($apath);
	}
	$tag.=buildFormHidden("{$name}_path",array('value'=>$params['path']));
	//autonumber?
	if(isset($params['autonumber']) || isset($params['data-autonumber']) || $params['tvals'] == 'autonumber' || $params['behavior'] == 'autonumber'){
		$tag.=buildFormHidden("{$name}_autonumber",array('value'=>1));
    }
    //resize after upload?
    if(isset($params['resize']) || isset($params['data-resize'])){
    	$resize=isset($params['resize'])?$params['resize']:$params['data-resize'];
		$tag.=buildFormHidden("{$name}_resize",array('value'=>$resize));
		unset($params['data-resize']);
    }
    if(isset($params['data-onfile'])){
    	$onfile="data-onfile=\"{$params['data-onfile']}\"";
    }
    else{$onfile='';}
	$tag.=<<<ENDOFTAG
<input type="file" {$onfile} accept="{$params['accept']}" class="fileupload" name="{$name}" id="{$params['id']}" style="display:none;" onchange="wacss.formFileUpload(this);" {$capture} {$params['multiple']}>
<div class="fileupload {$params['class']}">
	{$icon}
	<label for="{$params['id']}" data-hover="id:{$params['id']}_hover" data-position="bottom" data-text="{$params['text']}">{$params['text']}</label>
	<div class="icon-erase"></div>
	<input type="checkbox" value="1" name="{$params['name']}_remove" style="display:none;">
	<code style="display:none;">{$params['value']}</code>
</div>
<div class="fileupload_hover" style="display:none;" id="{$params['id']}_hover" data-onload="wacss.formFileUploadInit();wacss.initHovers();">
</div>
ENDOFTAG;
	$tag.='</div>';
	return $tag;
}
//Note: old buildFormFile function replace on 9/4/2024 
function buildFormFile_OLD($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(!isset($params['-icon'])){$params['-icon']='icon-upload w_big w_danger';}
	if(!isset($params['text'])){
		if(isset($params['multiple'])){$params['text']='files to upload';}
		else{$params['text']='file to upload';}
	}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=preg_replace('/[^a-z0-9\-\_]+/','_',$params['-formname'].'_'.$name);}
	$params['value']=buildFormValueParam($name,$params);
	$params['name']=$name;
	if(isset($params['multiple']) && !stringEndsWith($params['name'],'[]')){
		$params['name'].='[]';
	}
	//ksort($params);return printValue($params);
	$tag='';
	$viewer='';
	if(strlen($params['value'])){
		$val=encodeHtml($params['value']);
		$ext=getFileExtension($params['value']);
		$afile=$_SERVER['DOCUMENT_ROOT'].$params['value'];
		switch(strtolower($ext)){
			case 'mp3':
			case 'wav':
				$mime=getFileMimeType($afile);
				$viewer .= '<div style="margin:5px 1px"><audio controls="controls">'.PHP_EOL;
				$viewer .= '	<source src="'.$params['value'].'" type="'.$mime.'"  >'.PHP_EOL;
				$viewer .= '</audio></div>'.PHP_EOL;
			break;
			case 'mp4':
				$mime=getFileMimeType($afile);
				if(!isset($params['view_width'])){
					$params['view_width']=300;
				}
				if(!isset($params['view_height'])){
					$params['view_height']=300;
				}
				$viewer .= '<div style="margin:5px 1px;"><video height="36" onmouseover="this.setAttribute(\'height\','.$params['view_height'].');" onmouseout="this.setAttribute(\'height\',36);" controls="controls">'.PHP_EOL;
				$viewer .= '	<source src="'.$params['value'].'" type="'.$mime.'"  >'.PHP_EOL;
				$viewer .= '</video></div>'.PHP_EOL;
			break;
			case 'gif':
			case 'png':
			case 'jpg':
			case 'jpeg':
			case 'svg':
			case 'avif':
				$mime=getFileMimeType($afile);
				if(!isset($params['view_width'])){
					$params['view_width']=300;
				}
				if(!isset($params['view_height'])){
					$params['view_height']=300;
				}
				$viewer .= '<div style="margin:5px 1px;max-width:'.$params['view_width'].'px;max-height:'.$params['view_height'].'px;"><a class="w_link w_lblue" href="'.$val.'" target="_blank"><img style="border-radius:3px;max-width:'.$params['view_width'].'px;max-height:'.$params['view_height'].'px;" src="'.$params['value'].'" ></a>'.PHP_EOL;
				$viewer .= '</div>'.PHP_EOL;
			break;
			default:
				$viewer .= '	<a class="w_link" href="'.$val.'" target="_blank"><span class="icon-upload"></span> '.$val.'</a>'.PHP_EOL;
			break;
		}
	}
	//$params['viewonly']=1;
	if(isset($params['viewonly']) && $params['viewonly']==1){	
		if(!strlen($params['value'])){return $tag;}
		$tag .= $viewer;
		return $tag;
	}
	elseif(isset($params['readonly']) && $params['readonly']==1){	
		if(!strlen($params['value'])){return $tag;}
		$tag .= $viewer;
		return $tag;
	}
	$tag='';
	$viewer_id=$params['id'].'_viewer';
	if(isset($params['-noview'])){$viewer='';}
	if(strlen($viewer)){
		$tag .='	<div id="'.$viewer_id.'" style="display:none;">'.$viewer.'</div>';
	}
	$tag .= '	<input type="hidden" name="'.$name.'_prev" value="'.$val.'">'.PHP_EOL;
	//set path of where to store this file in
	if(!isset($params['path'])){
    	if(isset($params['defaultval']) && strlen($params['defaultval'])){$params['path']=$params['defaultval'];}
    	elseif(isset($params['data-path']) && strlen($params['data-path'])){$params['path']=$params['data-path'];}
    	elseif(isset($_REQUEST["{$name}_path"]) && strlen($_REQUEST["{$name}_path"])){$params['path']=$_REQUEST["{$name}_path"];}
    	else{$params['path']="/files/{$params['name']}";}
	}
	//create path if it does not exist
	$apath=$_SERVER['DOCUMENT_ROOT'].$params['path'];
	if(!is_dir($apath)){
		buildDir($apath);
	}
	$tag .='<span';
	if(isset($params['displayif'])){
		$tag .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	$tag .= '>';
	$tag.=buildFormHidden("{$name}_path",array('value'=>$params['path']));
	//autonumber?
	if(isset($params['autonumber']) || isset($params['data-autonumber']) || $params['tvals'] == 'autonumber' || $params['behavior'] == 'autonumber'){
		$tag.=buildFormHidden("{$name}_autonumber",array('value'=>1));
    }
    //resize after upload?
    if(isset($params['resize']) || isset($params['data-resize'])){
    	$resize=isset($params['resize'])?$params['resize']:$params['data-resize'];
		$tag.=buildFormHidden("{$name}_resize",array('value'=>$resize));
		unset($params['data-resize']);
    }
    //remove checkbox
    $tag .= '		<input type="hidden" style="display:none;" value="0" name="'.$name.'_remove"  id="'.$params['id'].'_remove" >'.PHP_EOL;

    $params['data-type']='file';
    $params['data-formname']=$params['-formname'];
    $params['-thumbnail']=1;
    if(isset($params['-thumbnail'])){
    	$params['data-thumbnail']=$params['-thumbnail'];
    }
    $params['onchange']="setInputFileName(this);";

    
	$label_params=array(
		'class'=>'btn btn-default w_white'
	);
	if(isset($params['style'])){
		$label_params['style']=$params['style'];
		unset($params['style']);
	}
	$params['style']='width:1px;max-width:1px;';
	if(isset($params['class']) && strlen($params['class'])){
		$label_params['class']=$params['class'];
		unset($params['class']);
	}
	unset($label_params['width']);
	$label_params['style']=preg_replace('/width\:[0-9\%pxrem\;]+/is','',$label_params['style']);
	$label_params['style'].=';width:300px;padding:0 15px;';
	//return printValue($params);
	$tag .= '	<input type="file" data-text="'.$params['text'].'"';
	if(isset($params['required'])){
		$tag.=' data-required="1" ';
		unset($params['required']);
	}
	elseif(isset($params['_required'])){
		$tag.=' data-required="1" ';
		unset($params['_required']);
	}
	$tag .= setTagAttributes($params);
	if(isset($params['multiple']) && $params['multiple']){
    	$tag .= ' multiple ';
	}
	$tag .= ' >'.PHP_EOL;
	$tag .= '	<label';
	if(strlen($viewer)){
		$tag .=' data-tooltip="id:'.$viewer_id.'"';
	}
	$tag .=' for="'.$params['id'].'"';
	$tag .= setTagAttributes($label_params);
	$tag .= ' ><span class="'.$params['-icon'].'" style="font-size:24px;margin-right:25px;"></span><span id="'.$params['id'].'_text" class="input_file_text" style="margin-left:5px;display:inline-flex;justify-content:center;align-items:center;font-size:0.8rem;">';
	//check for value
	if(strlen($params['value'])){
		$val=encodeHtml($params['value']);	
		$ext=getFileExtension($params['value']);
		$afile=$_SERVER['DOCUMENT_ROOT'].$params['value'];
		if(isImageFile($afile)){
			$tag .= '<img style="display:inline;max-height:24px;max-width:190px;" src="'.$params['value'].'" >'.PHP_EOL;
		}
		else{
			$class=commonGetFileIconClass($afile);
			$tag .= '<span class="'.$class.'" style="font-size:26px" title="'.$params['value'].'"></span>'.PHP_EOL;
		}
		//clear button
		$tag .= <<<ENDOFTAG
<span class="w_danger icon-erase" data-id="{$params['id']}" data-remove="{$params['id']}_remove" data-text="{$params['id']}_text" style="font-size:24px;margin-left:25px;" title="Remove" onclick="document.getElementById(this.dataset.id).value='';document.getElementById(this.dataset.remove).value=1;document.getElementById(this.dataset.text).innerText='';return false;">
</span>
ENDOFTAG;
		//download
		$tag .= '<a href="'.$params['value'].'" target="_blank" title="Download" style="text-decoration:none;font-size:24px;margin-left:25px;"><span class="w_info icon-download"></span></a>'.PHP_EOL;
	}
	else{
		$tag.=$params['text'];
	}
	$tag .='</span></label>'.PHP_EOL;
	$tag .= '</span>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildFormFrequency --------------------
/**
* @describe creates an HTML frequency input
* @param name string
* @param params array
* @return string
* @usage echo buildFormFrequency('{$params['id']}',$params);
*/
function buildFormFrequency($name,$params=array()){
	//return printValue($params);
	if(!isset($params['-formname']) || !strlen(trim($params['-formname']))){
		$params['-formname']='addedit';
	}
	if(isset($params['name']) && strlen(trim($params['name']))){
		$name=$params['name'];
	}
	if(!isset($params['id']) || !strlen(trim($params['id']))){
		$params['id']=$params['-formname'].'_'.$name;
	}
	if(isset($params['requiredif']) && strlen(trim($params['requiredif']))){
		$params['data-requiredif']=$params['requiredif'];
	}
	$class=isset($params['class'])?' '.$params['class']:'';
	$style=isset($params['style'])?' style="'.$params['style'].'"':' style="width:100%;height:31px;overflow:hidden;"';
	$placeholder=isset($params['placeholder'])?' placeholder="'.$params['placeholder'].'"':'';
	$required=isset($params['required']) && $params['required']==1?' required="required"':'';
	//determine what sections to show
	$sections=array('minute','hour','day','dayname','month');
	if(isset($params['tvals']) && strlen($params['tvals'])){
		$sections=preg_split('/[\r\n\,]+/',trim($params['tvals']));
	}
	if(isset($params['-hide'])){
		foreach($sections as $i=>$section){
			if(stringContains($params['-hide'],$section)){
				unset($sections[$i]);
			}
		}
	}
	elseif(isset($params['-show'])){
		$sections=preg_split('/\,/',strtolower(trim($params['-show'])));
	}
	if(!count($sections)){return '';}
	$displayif=' style="display:block;"';
	if(isset($params['displayif'])){
		$displayif = ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	//return printValue($params);
	$sectionstr=implode("','",$sections);
	$rtn=<<<ENDOFRTN
	<div id="{$params['id']}_container" data-display="block" {$displayif}>
		<div><textarea name="{$name}" id="{$params['id']}" class="w_frequency{$class}" {$style}{$placeholder}{$required} onfocus="formSetFrequencyDisplay(this.id,1);" onblur="formSetFrequency(this.id,this.value);" wrap="off">{$params['value']}</textarea></div>
		<div id="{$params['id']}_wizard" data-sections="{$sectionstr}" class="w_frequency_wizard" style="display:none;min-height:100px;">
			<div style="display:flex;justify-content:flex-start;align-items:center;flex-wrap:wrap;" data-type="section" style="border-top:0px;">
ENDOFRTN;
	
	$rtn .= '				<span class="icon-frequency w_pointer" title="clear all" onclick="return formSetFrequency('."'{$params['id']}',{reset:['{$sectionstr}']});\"></span>".PHP_EOL;
	if(stringContains($sectionstr,'minute')){
		$rtn .= '				<a href="#" style="margin-left:10px;" class="w_link w_gray" onclick="return formSetFrequency('."'{$params['id']}',{minute:[-1],hour:[-1],month:[-1],day:[-1],dayname:[-1]});\">Every Minute</a>".PHP_EOL;
	}
	if(stringContains($sectionstr,'hour')){
		$rtn .= '				<a href="#" style="margin-left:10px;" class="w_link w_gray" onclick="return formSetFrequency('."'{$params['id']}',{minute:[0],hour:[-1],month:[-1],day:[-1],dayname:[-1]});\">Hourly</a>".PHP_EOL;
	}
	if(stringContains($sectionstr,'day') && stringContains($sectionstr,'month')){
		$rtn .= '				<a href="#" style="margin-left:10px;" class="w_link w_gray" onclick="return formSetFrequency('."'{$params['id']}',{minute:[0],hour:[0],month:[-1],day:[-1],dayname:[-1]});\">Daily</a>".PHP_EOL;
		$rtn .= '				<a href="#" style="margin-left:10px;" class="w_link w_gray" onclick="return formSetFrequency('."'{$params['id']}',{minute:[0],hour:[0],month:[-1],day:[1,8,15,22]},dayname:[-1]);\">Weekly</a>".PHP_EOL;
		$rtn .= '				<a href="#" style="margin-left:10px;" class="w_link w_gray" onclick="return formSetFrequency('."'{$params['id']}',{minute:[0],hour:[0],month:[-1],day:[1,15],dayname:[-1]});\">Bi-Monthly</a>".PHP_EOL;
		$rtn .= '				<a href="#" style="margin-left:10px;" class="w_link w_gray" onclick="return formSetFrequency('."'{$params['id']}',{minute:[0],hour:[0],month:[-1],day:[1],dayname:[-1]});\">Monthly</a>".PHP_EOL;
		$rtn .= '				<a href="#" style="margin-left:10px;" class="w_link w_gray" onclick="return formSetFrequency('."'{$params['id']}',{minute:[0],hour:[0],month:[1,4,7,10],day:[1],dayname:[-1]});\">Quarterly</a>".PHP_EOL;
		$rtn .= '				<a href="#" style="margin-left:10px;" class="w_link w_gray" onclick="return formSetFrequency('."'{$params['id']}',{minute:[0],hour:[0],month:[1],day:[1],dayname:[-1]});\">Yearly</a>".PHP_EOL;
	}
	$rtn .= '			</div>'.PHP_EOL;
	if(stringContains($sectionstr,'minute')){
	//minutes
		$rtn .= '			<div class="w_frequency_row" data-type="section"><span>Minutes</span><span class="icon-erase w_pointer" title="clear minutes" onclick="return formSetFrequency(\''.$params['id'].'\',{reset:[\'minute\']});"></span></div>'.PHP_EOL;
		$rtn.='			<div class="w_frequency_row" data-type="minutes">'.PHP_EOL;
		for($x=0;$x<60;$x++){
			$v=$x;
			if(strlen($v)==1){$v="0{$x}";}
			$rtn .= '		      	<label><input type="checkbox" onclick="formSetFrequency(\''.$params['id'].'\');" class="frequency_minute" value="'.$v.'" > '.$v.'</label>'.PHP_EOL;
			if(($x+1)%10==0){
				$rtn .= '		    </div>'.PHP_EOL;
				$rtn.='			<div class="w_frequency_row" data-type="minutes">'.PHP_EOL;
			}
		}
		$rtn .= '		    </div>'.PHP_EOL;
	}
	if(stringContains($sectionstr,'hour')){
		//hours
		$rtn .= '			<div class="w_frequency_row" data-type="section"><span>Hours</span><span class="icon-erase w_pointer" title="clear hours" onclick="return formSetFrequency(\''.$params['id'].'\',{reset:[\'hour\']});"></span></div>'.PHP_EOL;
		$rtn.='			<div class="w_frequency_row" data-type="hours">'.PHP_EOL;
		for($x=0;$x<24;$x++){
			$v=$x;
			if(strlen($v)==1){$v="0{$x}";}
			$rtn .= '		      	<label><input type="checkbox" onclick="formSetFrequency(\''.$params['id'].'\');" class="frequency_hour" value="'.$v.'" > '.$v.'</label>'.PHP_EOL;
			if(($x+1)%12==0){
				$rtn .= '		    </div>'.PHP_EOL;
				$rtn.='			<div class="w_frequency_row" data-type="hours">'.PHP_EOL;
			}
		}
		$rtn .= '		    </div>'.PHP_EOL;
	}
	if(stringContains($sectionstr,'day')){
		//days
		$rtn .= '			<div class="w_frequency_row" data-type="section"><span>Days</span><span class="icon-erase w_pointer" title="clear days" onclick="return formSetFrequency(\''.$params['id'].'\',{reset:[\'day\']});"></span></div>'.PHP_EOL;
		$rtn.='			<div class="w_frequency_row" data-type="days">'.PHP_EOL;
		for($x=1;$x<29;$x++){
			$v=$x;
			if(strlen($v)==1){$v="0{$x}";}
			$rtn .= '		      	<label><input type="checkbox" onclick="formSetFrequency(\''.$params['id'].'\');" class="frequency_day" value="'.$v.'" > '.$v.'</label>'.PHP_EOL;
			if($x%7==0){
				$rtn .= '		    </div>'.PHP_EOL;
				$rtn.='			<div class="w_frequency_row" data-type="days">'.PHP_EOL;
			}
		}
		$rtn .= '		    </div>'.PHP_EOL;
	}
	if(stringContains($sectionstr,'dayname')){
		//daynames
		$rtn .= '			<div class="w_frequency_row" data-type="section"><span>Daynames</span><span class="icon-erase w_pointer" title="clear daynames" onclick="return formSetFrequency(\''.$params['id'].'\',{reset:[\'dayname\']});"></span></div>'.PHP_EOL;
		$rtn.='			<div class="w_frequency_row" data-type="days">'.PHP_EOL;
		$daynames=array(0=>'Mon',1=>'Tue',2=>'Wed',3=>'Thu',4=>'Fri',5=>'Sat',6=>'Sun');
		foreach($daynames as $tval=>$dval){
			$rtn .= '		      	<label><input type="checkbox" data-inputtype="checkbox" onclick="formSetFrequency(\''.$params['id'].'\');" class="frequency_dayname" value="'.$tval.'" > '.$dval.'</label>'.PHP_EOL;
		}
		$rtn .= '		    </div>'.PHP_EOL;
	}
	if(stringContains($sectionstr,'month')){
		$rtn.=<<<ENDOFRTN
			<div class="w_frequency_row" data-type="section"><span>Months</span><span class="icon-erase w_pointer" title="clear months" onclick="return formSetFrequency('{$params['id']}',{reset:['month']});"></span></div>
		    <div class="w_frequency_row" data-type="months">
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="1" > Jan</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="2" > Feb</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="3" > Mar</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="4" > Apr</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="5" > May</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="6" > Jun</label>
		    </div>
		    <div class="w_frequency_row" style="border-bottom:1px solid #ccc;" data-type="months">      	
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="7" > Jul</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="8" > Aug</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="9" > Sep</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="10" > Oct</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="11" > Nov</label>
		      	<label><input type="checkbox" onclick="formSetFrequency('{$params['id']}');" class="frequency_month" value="12" > Dec</label>
		    </div>
ENDOFRTN;
	}
	$rtn.=<<<ENDOFRTN
	    	<div style="text-align: right;margin-top:5px"><a href="#" class="w_link w_gray" onclick="return formSetFrequencyDisplay('{$params['id']}',0);"><span class="icon-close"></span> done</a></div>
	    </div>
	</div>
ENDOFRTN;
	$rtn.=buildOnLoad("formSetFrequency('{$params['id']}',getText('{$params['id']}'));");
	return $rtn;

}
//---------- begin function buildFormEnd-------------------
/**
* @describe creates an HTML form ending tag
* @return string
* @usage echo buildFormEnd();
*/
function buildFormEnd(){
	return '</form>'.PHP_EOL;
	}

//---------- begin function buildFormImage-------------------
/**
* @describe creates an image submit tag
* @param src string
* @param name string
* @param onclick string
* @return string
* @usage echo buildFormImage('/wfiles/iconsets/checkmark.png');
*/
function buildFormImage($src,$name='',$onclick=''){
	$rtn = '	<input type="image" src="'.$src.'"';
	if(strlen($name)){$rtn .= ' name="'.$name.'"';}
	if(strlen($onclick)){$rtn .= ' onclick="'.$onclick.'"';}
	$rtn .= '>'.PHP_EOL;
	return $rtn;
}

//---------- begin function buildFormSelect-------------------
/**
* @describe creates an HTML form selection tag
* @param name string - name of select tag
* @param pairs array - tval/dval pairs array to populate select tag with
* @param params array - attribute/value pairs to add to select tag
* @return string - HTML Form select tag
* @usage echo buildFormSelect('age',array(5=>"Below Five",10=>"5 to 10"));
*/
function buildFormSelect($name,$pairs=array(),$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['displayif'])){$params['data-displayif']=$params['displayif'];}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	//return printValue($pairs);
	if(!isset($pairs) || !is_array($pairs)){
		$pairs=[];
	}
	$params['value']=buildFormValueParam($name,$params);
	if(isset($params['viewonly'])){
		return '<div class="w_viewonly" id="'.$params['id'].'">'.nl2br($params['value']).'</div>'.PHP_EOL;
	}
	$pcnt=count($pairs);
	if($pcnt==0 || ($pcnt==1 && isset($pairs[0]) && $pairs[0]=='')){
    	return buildFormText($name,$params);
	}
	$params['name']=$name;
	$skip=array();
	if(isset($params['-noname'])){$skip[]='name';}
	//select does not honor readonly so lets fix that
	if(isset($params['readonly'])){
		$params['style']="pointer-events: none;cursor: not-allowed;color:#a8a8a8;".$params['style'];
	}
	$rtn = '<select data-value="'.$params['value'].'"';
	$rtn .= setTagAttributes($params,$skip);
	$rtn .= '>';
	if(isset($params['message'])){
		$rtn .= '	<option value="">'.$params['message'].'</option>'.PHP_EOL;
    }
	if(isset($params['-groups'])){
		foreach($pairs as $group=>$opts){
			$rtn .= '	<optgroup label="'.$group.'">'.PHP_EOL;
			foreach($opts as $tval=>$dval){
				if(!isset($dval) || !strlen($dval)){$dval=$tval;}
				$rtn .= '		<option value="'.$tval.'"';
				if(isset($params["{$tval}_style"])){
					$rtn .= ' style="'.$params["{$tval}_style"].'"';
				}
				if(isset($params["{$tval}_class"])){
					$rtn .= ' class="'.$params["{$tval}_class"].'"';
				}
				foreach($params as $k=>$v){
					if(!stringBeginsWith($k,"{$tval}_data-")){continue;}
					$k=str_replace("{$tval}_data-",'',$k);
					$rtn .= " data-{$k}=\"{$v}\"";
				}
				if(strlen($params['value'])){
					if($params['value']==$tval){$rtn .= ' selected';}
					elseif($params['value']==$dval){$rtn .= ' selected';}
				}
				$rtn .= '>'.$dval.'</option>'.PHP_EOL;
		    }
		    $rtn .= '	</optgroup>'.PHP_EOL;
		}
	}
	else{
		foreach($pairs as $tval=>$dval){
			if(!isset($dval) || !strlen($dval)){$dval=$tval;}
			$rtn .= '	<option value="'.$tval.'"';
			if(isset($params["{$tval}_style"])){
				$rtn .= ' style="'.$params["{$tval}_style"].'"';
			}
			if(isset($params["{$tval}_class"])){
				$rtn .= ' class="'.$params["{$tval}_class"].'"';
			}
			foreach($params as $k=>$v){
				if(!stringBeginsWith($k,"{$tval}_data-")){continue;}
				$k=str_replace("{$tval}_data-",'',$k);
				$rtn .= " data-{$k}=\"{$v}\"";
			}
			if(strlen($params['value'])){
				if($params['value']==$tval){$rtn .= ' selected';}
				elseif($params['value']==$dval){$rtn .= ' selected';}
			}
			$rtn .= '>'.$dval.'</option>'.PHP_EOL;
	    }
	}
    $rtn .= '</select>'.PHP_EOL;
    if(strlen($sval) && isset($params['onchange']) && isset($params['-trigger']) && $params['-trigger']==1){
    	$rtn .= buildOnLoad("commonEmulateEvent('{$params['id']}','change')");	
    }
    return $rtn;
}
//---------- begin function buildFormSelectCountry--------------------
/**
* @describe creates an Selection list for Country
* @param name string
* @param params array
* @return string
* @usage echo buildFormSelectCountry('country',$params);
*/
function buildFormSelectCountry($name='country',$params=array('message'=>'-- country --')){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	$params['value']=buildFormValueParam($name,$params);
	//get a list of country codes that exist in the states table - place these first
	$query="select distinct(country) as code from states";
	$codes=getDBRecords(array('-query'=>$query,'-index'=>'code'));
	if(!is_array($codes)){
		debugValue("buildFormSelectCountry error - states table is blank");
		return '';
	}
	$codes=array_keys($codes);
	//get countries
	$recopts=array(
		'-table'=>'countries',
		'-order'=>'name,_id',
		'-fields'=>'_id,name,code'
	);
	$recs=getDBRecords($recopts);
	if(!is_array($recs)){return $recs;}

	//build the list - placing countries found in the states table first.
	foreach($recs as $i=>$rec){
    	if(in_array($rec['code'],$codes)){
        	$recs[$i]['sort']=0;
		}
		else{
        	$recs[$i]['sort']=1;
		}
	}
	//sort by sort field
	$recs=sortArrayByKeys($recs,array('sort'=>SORT_ASC,'name'=>SORT_ASC));
	$opts=array();
	$line=0;
	foreach($recs as $i=>$rec){
		if($line==0 && $rec['sort']==1){
        	$line=1;
        	$opts['']='----------------';
		}
		$opts[$rec['code']]=$rec['name'];
	}
	return buildFormSelect($name,$opts,$params);
}
//---------- begin function buildFormSelectColor--------------------
/**
* @describe creates an Selection list for Color
* @param name string
* @param params array
* @return string
* @usage echo buildFormSelectColor('color',$params);
*/
function buildFormSelectColor($name='color',$params=array('message'=>'-- color --')){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	$params['value']=buildFormValueParam($name,$params);
	//get colors
	$recopts=array(
		'-table'=>'colors',
		'-order'=>'hue,saturation,lightness,red,green,blue,name,code,_id',
	);
	$recs=getDBRecords($recopts);
	if(!is_array($recs) || !count($recs)){
		clearDBCache('databaseTables');
		$ok=createWasqlTable('colors');
		$recs=getDBRecords($recopts);
	}
	if(!is_array($recs)){return $recs;}
	$opts=array();
	//echo printValue($ok).printValue($recs);exit;
	foreach($recs as $i=>$rec){
		$key=$rec['hex'];
		if(isset($params['-rgb'])){$key="{$rec['red']},{$rec['green']},{$rec['blue']}";}
		elseif(isset($params['-code'])){$key=$rec['code'];}
		if(!strlen($rec['contrast_ratio'])){
			$rec['contrast_ratio']=$contrast_ratio=commonGetContrastRatio($rec['hex']);
			$ok=editDBRecordById('colors',$rec['_id'],array('contrast_ratio'=>$contrast_ratio));
		}
		if(!strlen($rec['hue'])){
			$hsl=commonRGBToHSL($rec['red'],$rec['green'],$rec['blue']);
			$ok=editDBRecordById('colors',$rec['_id'],array(
				'hue'=>$hsl['h'],
				'saturation'=>$hsl['s'],
				'lightness'=>$hsl['l']
			));
		}
		$opts[$key]="{$rec['name']} ({$rec['contrast_ratio']})";
		// If contrast is more than 5, return black color
	    if ($rec['contrast_ratio'] > 3) {
	        $color='#000000';
	    } else { 
	        // if not, return light gray color.
	        $color='#d3d3d3';
	    }
		$params["{$key}_style"]="background-color:{$rec['hex']};color:{$color};";
	}
	return buildFormSelect($name,$opts,$params);
}
function commonRGBToHSL($r,$g,$b) {
	$oldR = $r;
	$oldG = $g;
	$oldB = $b;
	$r /= 255;
	$g /= 255;
	$b /= 255;
	$max = max( $r, $g, $b );
	$min = min( $r, $g, $b );
	$h;
	$s;
	$l = ( $max + $min ) / 2;
	$d = $max - $min;
	if( $d == 0 ){
		$h = $s = 0; // achromatic
	} 
	else{
		$s = $d / ( 1 - abs( 2 * $l - 1 ) );
		switch( $max ){
			case $r:
				$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 ); 
				if ($b > $g) {
					$h += 360;
				}
			break;
			case $g: 
				$h = 60 * ( ( $b - $r ) / $d + 2 ); 
			break;
			case $b: 
				$h = 60 * ( ( $r - $g ) / $d + 4 ); 
			break;
		}			        	        
	}
	return array( 'h'=>round( $h, 2 ), 's'=>round( $s, 2 ), 'l'=>round( $l, 2 ) );
}
function commonGetContrastRatio($hexColor){
    // hexColor RGB
    $R1 = hexdec(substr($hexColor, 1, 2));
    $G1 = hexdec(substr($hexColor, 3, 2));
    $B1 = hexdec(substr($hexColor, 5, 2));

    // Black RGB
    $blackColor = "#000000";
    $R2BlackColor = hexdec(substr($blackColor, 1, 2));
    $G2BlackColor = hexdec(substr($blackColor, 3, 2));
    $B2BlackColor = hexdec(substr($blackColor, 5, 2));

     // Calc contrast ratio
     $L1 = 0.2126 * pow($R1 / 255, 2.2) +
           0.7152 * pow($G1 / 255, 2.2) +
           0.0722 * pow($B1 / 255, 2.2);

    $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
          0.7152 * pow($G2BlackColor / 255, 2.2) +
          0.0722 * pow($B2BlackColor / 255, 2.2);

    $contrastRatio = 0;
    if ($L1 > $L2) {
        $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
    } else {
        $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
    }
    return $contrastRatio;
}
//---------- begin function buildFormSelectCustom-------------------
/**
* @describe creates an HTML form selection tag
* @param name string - name of select tag
* @param pairs array - tval/dval pairs array to populate select tag with
* @param params array - attribute/value pairs to add to select tag
* @return string - HTML Form select tag
* @usage echo buildFormSelectCustom('age',array(5=>"Below Five",10=>"5 to 10"));
*/
function buildFormSelectCustom($name,$pairs=array(),$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	//return printValue($pairs);
	$pcnt=count($pairs);
	if($pcnt==0 || ($pcnt==1 && isset($pairs[0]) && $pairs[0]=='')){
    	return buildFormText($name,$params);
	}
	$params['value']=buildFormValueParam($name,$params);
	$params['name']=$name;
	$skip=array();
	$rtn='';
	$rtn .= '<div style="position:relative;display:inline-block;height:22px;"';
	if(isset($params['displayif'])){
		$rtn .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	$rtn .='>'.PHP_EOL;
	$rtn .= '	<fieldset class="select" onclick="this.classList.toggle(\'hover\');">'.PHP_EOL;
	$rtn .= '		<ul >'.PHP_EOL;
	$cnt=0;
	foreach($pairs as $tval=>$dval){
		$txtval=removeHtml($dval);
		$id=$params['id'].'_'.$cnt;
		$checked='';
		if($params['value']==$tval){$checked=' checked';}
		$rtn .= '			<li><input type="radio"  name="'.$params['name'].'" id="'.$id.'" value="'.$tval.'" onclick="event.stopPropagation();this.parentNode.parentNode.parentNode.className=\'select\';"'.$checked.' ><span data-value="'.$txtval.'"></span><label for="'.$id.'">'.$dval.'</label></li>'.PHP_EOL;
		$cnt++;
	}	
	$rtn .= '		</ul>'.PHP_EOL;
	$rtn .= '	</fieldset>'.PHP_EOL;
	$rtn .= '</div>'.PHP_EOL;
    return $rtn;
}
//---------- begin function buildFormSelectDatabase--------------------
/**
* @describe creates a select list from the DATABASE tags in config.xml
* @param name string
* @param opts array
* @param params array
*	[-group] string - only show this database group
*	[-dbs] mixed - only show this database list - comma separated or an array
* @return string
* @usage echo buildFormSelectDatabase('mydate',$opts,$params);
*/
function buildFormSelectDatabase($name,$params=array()){
	if(!isset($params['class'])){$params['class']='select browser-default';}
	if(!isset($params['name'])){$params['name']=$field;}
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(!isset($params['name'])){$params['name']=$name;}
	if(!isset($params['message'])){$params['message']=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['displayif'])){$params['data-displayif']=$params['displayif'];}
	$params['value']=buildFormValueParam($name,$params);
	if(isset($params['viewonly'])){
		return '<div class="w_viewonly" id="'.$params['id'].'">'.nl2br($params['value']).'</div>'.PHP_EOL;
	}
	global $CONFIG;
	global $DATABASE;
	$showtabs=array();
	if(isset($params['-group'])){
		foreach($DATABASE as $dbkey=>$db){
			if(isset($db['group']) && $db['group']==$params['-group']){
				$showtabs[]=$db['name'];
			}
		}
	}
	elseif(isset($params['-dbs'])){
		if(is_array($params['-dbs'])){
			$showtabs=$params['-dbs'];
		}
		else{
			$showtabs=preg_split('/[\,\:]+/',$params['-dbs']);
		}
	}
	elseif(isset($CONFIG['databases'])){
		$showtabs=preg_split('/[\,\:]+/',$CONFIG['databases']);
	}
	elseif(isset($CONFIG['sql_prompt_dbs'])){
		$showtabs=preg_split('/[\,\:]+/',$CONFIG['sql_prompt_dbs']);
	}
	$dbtypes=array();
	foreach($DATABASE as $dbkey=>$db){
		if(count($showtabs) && !in_array($dbkey,$showtabs)){continue;}
		if(isset($db['group'])){$group=$db['group'];}
		else{$group=ucfirst($db['dbtype']);}
		$dbtypes[$group][]=$db;
	}
	ksort($dbtypes);
	$tag='<select ';
	if(count($showtabs)){$tag.= ' data-showtabs="1"';}
	if(isset($db['group'])){$tag.= ' data-group="'.$db['group'].'"';}
	$tag.=setTagAttributes($params);
	$tag.='>'.PHP_EOL;
	$tag.='	<option value="">-- '.ucfirst($params['message']).' --</option>'.PHP_EOL;
	foreach($dbtypes as $group=>$dbs){
		$tag.='	<optgroup label="'.$group.'">'.PHP_EOL;
		foreach($dbs as $db){
			$dval=$db['displayname'];
			$addons=array();
			if(strtolower($group) != $db['dbtype']){
				$addons[]= $db['dbtype'];
			}
			if(strlen($db['dbuser'])){
				$addons[]= $db['dbuser'];
			}
			if(count($addons)){
				$dval.=' ('.implode(' - ',$addons).')';
			}
			$tval=$db['name'];
			$tag.='		<option value="'.$tval.'"';
			if(strlen($params['value'])){
				if(strtolower($params['value'])==strtolower($tval)){
					$tag.=' selected';
				}
			}
			$tag.='>'.$dval.'</option>'.PHP_EOL;
		}
		$tag.='	</optgroup>';
	}
	$tag.='</select>';
	return $tag;
}
//---------- begin function buildFormSelectFont--------------------
/**
* @describe creates an select list of available fonts found in /wfiles/fonts/extras
* @param name string
* @param params array
* @return string
* @usage echo buildFormSelectFont('myfont',array('style'=>'font-size:1.2rem'));
*/
function buildFormSelectFont($name,$params=array()){
	$fontdir=getWasqlPath('wfiles/fonts/extras');
	$files=listFilesEx($fontdir,array('ext'=>'css'));
	$files=sortArrayByKeys($files,array('name'=>SORT_ASC));
	$opts=array();
	$fonts=array();
	$tag='<select name="'.$name.'">'.PHP_EOL;
	foreach($files as $file){
		$tval=str_replace('.css','',$file['name']);
		$fonts[]=$tval;
		$dval=ucwords(str_replace('-',' ',$tval));
		$opts[$tval]=$dval;
		$params["{$tval}_style"]="font-family:{$tval}";
	}
	loadExtrasFont($fonts);
	if(isset($params['-checkbox'])){
		return buildFormCheckbox($name,$opts,$params);
	}
	return buildFormSelect($name,$opts,$params);
}
//---------- begin function buildFormSelectHost--------------------
/**
* @describe creates an select list of available hosts
* @return string
* @usage echo buildFormSelectHost('mydate',$params);
*/
function buildFormSelectHost($name,$params=array()){
	global $ALLCONFIG;
	global $CONFIG;
	if(!isset($params['message'])){$params['message']=' -- select --';}
	$params['value']=buildFormValueParam($name,$params);
	if(!isset($params['value'])){
		$params['value']=$CONFIG['host'];
	}
	$opts=array();
	foreach($ALLCONFIG as $host=>$conf){
		$opts[$host]="{$host} ({$conf['dbname']})";
	}
	ksort($opts);
	return buildFormSelect($name,$opts,$params);
}
//---------- begin function buildFormSelectMonth--------------------
/**
* @describe creates an Month selection field
* @param name string
* @param params array
* @return string
* @usage echo buildFormSelectMonth('cc_expire_month',$params);
*/
function buildFormSelectMonth($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	$params['value']=buildFormValueParam($name,$params);
	$opts=array(
		1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
		7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'
	);
	return buildFormSelect($name,$opts,$params);
}
//---------- begin function buildFormSelectOnOff--------------------
/**
* @describe creates an Button Select field for On/OFF
* @param name string
* @param params array
* @return string
* @usage echo buildFormSelectOnOff($name);
*/
function buildFormSelectOnOff($name,$params=array()){
	$opts=array(
		'0'=>'Off',
		'1'=>'On'
	);
	if(!isset($params['0_class'])){$params['0_class']='w_red';}
	if(!isset($params['1_class'])){$params['1_class']='w_green';}
	$params['value']=buildFormValueParam($name,$params);
	return buildFormButtonSelect($name,$opts,$params);
}
//---------- begin function buildFormSelectState--------------------
/**
* @describe creates an Selection list for State selection
* @param name string - name of field
* @param country string - country code of country
* @param params array
* @return string
* @usage echo buildFormSelectState('state','US',$params);
*/
function buildFormSelectState($name='state',$country='US',$params=array('message'=>'-- state --')){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	$params['value']=buildFormValueParam($name,$params);
	if(!isDBTable('states')){
		return 'missing States table';
	}
	//get a list of country codes that exist in the states table - place these first
	$recopts=array(
		'-table'=>"states",
		'country'=>$country,
		'-order'=>"name,_id",
		'-fields'=>"_id,name,code",
		'-index'=>'code'
	);
	$recs=getDBRecords($recopts);
	if(!is_array($recs)){return $recs;}
	$opts=array();
	$line=0;
	foreach($recs as $i=>$rec){
		$opts[$rec['code']]=$rec['name'];
	}
	return buildFormSelect($name,$opts,$params);
}
//---------- begin function buildFormSelectTimezone--------------------
/**
* @describe creates an timezone selection field
* @param name string - field name. defaults to 'timezone'
* @param params array
* @return string
* @usage echo buildFormSelectTimezone('timezone',$params);
*/
function buildFormSelectTimezone($name='timezone',$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	$params['value']=buildFormValueParam($name,$params);
	$zones=timezoneList($params);
	//groups
	foreach($zones as $tval=>$dval){
		list($country,$area)=preg_split('/\//',$tval,2);
		$groups[$country][$tval]=str_replace("{$country}/",'',$dval);
	}
	$params['-groups']=1;
	return buildFormSelect($name,$groups,$params);
}
//---------- begin function buildFormSelectYear--------------------
/**
* @describe creates an Year selection field
* @param name string
* @param params array
* @return string
* @usage echo buildFormSelectYear('cc_expire_year',$params);
*/
function buildFormSelectYear($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(!isset($params['-years'])){$params['-years']=10;}
	if(!isset($params['-backwards'])){$params['-backwards']=0;}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['class'])){$params['class']='w_form-control';}
	$params['value']=buildFormValueParam($name,$params);
	$tvals=selectYears($params['-years'],2,$params['-backwards']);
	$dvals=selectYears($params['-years'],4,$params['-backwards']);
	$opts=array();
	foreach($tvals as $i=>$tval){$opts[$tval]=$dvals[$i];}
	return buildFormSelect($name,$opts,$params);
}
//---------- begin function buildFormSignature--------------------------------------
/**
* @describe creates an HTML Form signature field the works on mobile and PC - user can use the mouse or finger to sign
* @param name string
*	The name of the field
* @param params array
*	options are as follows
*	- [displayname]. Defaults to "Please Sign Below:"
*	- [width]. Defaults to 300
*	- [height]. Defaults to 75
* 	- [style] - set width or height in style to override defaults
* 	- [data-autoresize] - width, height, both 
* @return
*	HTML Form signature field the works on mobile and PC - user can use the mouse or finger to sign
* @usage buildFormSignature('sign',$params);
*/
function buildFormSignature($name,$params=array()){
	loadExtrasJs(array('wacss','signature_pad'));
	global $USER;
	$rtn='';
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['displayname'])){$params['displayname']='Please Sign Below:';}
	if(!isset($params['clear'])){$params['clear']='<span class="icon-erase"></span>';}
	if(!isset($params['sign'])){$params['sign']='<span class="icon-signature"></span>';}
	if(!isset($params['width'])){$params['width']=600;}
	if(!isset($params['height'])){$params['height']=150;}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	$params['value']=buildFormValueParam($name,$params);
	$params['id']=str_replace('>','_',$params['id']);
	if(isset($params['readonly']) && ($params['readonly']==1 || $params['readonly']=='readonly')){
		if(isset($params['-value']) && strlen($params['-value'])){
			$src=$params['-value'];
		}
		else{
			$src='/wfiles/clear.gif';
		}
		$rtn='';
		$rtn .= '<div class="w_signature"><img src="'.$src.'" alt="current signature"';
		if(isset($params['style']) && strlen($params['style'])){
			$rtn .= ' style="'.$params['style'].'"';
		}
		else{
			$rtn .= ' style="width:'.$params['width'].'px;height:'.$params['height'].'px;"';
		}
		$rtn .= ' ></div>';
		return $rtn;
	}
	$params['data-behavior']='signature_pad';
	return buildFormTextarea($name,$params);
}
//---------- begin function buildFormSlider--------------------------------------
/**
* @describe creates an HTML Form slider field
* @param name string
*	The name of the field
* @param params array
*	options are as follows
*	- formname. Defaults to "addedit"
*	- min. Defaults to 1
*	- max. Defaults to 10
*	- step. Defaults to 1
*	- value. Defaults to 5
*	- label. Defaults to {formname}_{name}_value
*	- min_displayname. Defaults to 1
*	- max_displayname. Defaults to 10
* @return
*	HTML Form slider field
* @usage buildFormSlider('age',$params);
*/
function buildFormSlider($name, $params=array()){
	if(!strlen(trim($name))){return 'buildFormSlider Error: no name';}
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	if(!isset($params['min'])){$params['min']=1;}
	if(!isset($params['max'])){$params['max']=10;}
	if(!isset($params['step'])){$params['step']=1;}
	$params['value']=buildFormValueParam($name,$params);
	if(!isset($params['value'])){$params['value']=5;}
	if(!isset($params['label'])){$params['label']=$params['formname'].'_'.$name.'_value';}
	if(!isset($params['min_displayname'])){$params['min_displayname']=$params['min'];}
	if(!isset($params['max_displayname'])){$params['max_displayname']=$params['max'];}
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	//IE does not support slider until version 10 - build a select list instead -
	if($_SERVER['REMOTE_BROWSER']=='msie' && $_SERVER['REMOTE_BROWSER_VERSION'] < 10){
    	$opts=array();
    	for($x=$params['min'];$x<=$params['max'];$x++){$opts[$x]=$x;}
    	$params['message']="-- select --";
    	$params['required']=1;
    	unset($params['min']);
		unset($params['max']);
		unset($params['step']);
		unset($params['name']);
		unset($params['mask']);
    	return buildFormSelect($name,$opts,$params);
	}
	//build the slider control
	//$params['onchange'].=";setSliderText(this);";
	$params['oninput'].=";setSliderText(this);";
	$displayif='';
	if(isset($params['displayif'])){
		$displayif = ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	$title='';
	if(isset($params['title'])){
		$title = "<div>{$params['title']}</div>";
		unset($params['title']);
	}
	$min_displayname='';
	if(isset($params['min_displayname'])){
		$min_displayname = "<div style=\"align-self:center;\">{$params['min_displayname']}</div>";
		unset($params['min_displayname']);
	}
	$max_displayname='';
	if(isset($params['max_displayname'])){
		$max_displayname = "<div style=\"align-self:center;\">{$params['max_displayname']}</div>";
		unset($params['max_displayname']);
	}
	if(isset($params['style'])){$params['style'].=';align-self:center;';}
	else{$params['style']='align-self:center;';}
	$slider='';
	$slider .= ' <input type="range" data-label="'.$params['label'].'" name="'.$params['name'].'"';
	unset($params['name']);
	unset($params['required']);
	unset($params['mask']);
	unset($params['data-label']);
	$val=$params['value'];
	if(isset($params['data-labelmap'])){
		if(is_array($params['data-labelmap'])){
			if(isset($params['data-labelmap'][$val])){$val=$params['data-labelmap'][$val];}
	    	$params['data-labelmap']=json_encode($params['data-labelmap'],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
	    	$params['data-labelmap']=str_replace('"',"'",$params['data-labelmap']);
		}
		else{
        	$jsondata=str_replace("'",'"',$params['data-labelmap']);
        	$json=json_decode($jsondata);
        	if(isset($json[$val])){$val=$json[$val];}
		}
	}
	$skip=array();
	if(isset($params['-noname'])){$skip[]='name';}
	$slider .= setTagAttributes($params,$skip);
	$slider .= ' value="'.$params['value'].'"> '.PHP_EOL;
	$rtn=<<<ENDOFDIV
<div style="display:flex;flex-direction:column;width:100%;" data-display="flex" {$displayif}>
	{$title}
	<div style="display:flex;width:100%;">
		{$min_displayname}
		{$slider}
		{$max_displayname}
	</div>
	<div class="input_range_text" id="{$params['label']}" align="center">{$val}</div>
</div>
ENDOFDIV;
	return $rtn;
}
//---------- begin function buildFormStarRating-------------------
/**
* @param name string  - The name of the field
* @param params array - options are as follows
*	[-formname]. Defaults to addedit
*	[id]. Defaults to formname_fieldname
*	[name]. overrides name
*	[value]. Defaults to 0
* @return string - return HTML star rating field
* @usage buildFormStarRating('rating',$params);
*/
function buildFormStarRating($name, $params=array()){
	if(isset($params) && !is_array($params) && isNum($params)){
		$params=array('value'=>(float)$params,'-readonly'=>1);
	}
	if(!strlen(trim($name))){return 'buildFormSlider Error: no name';}
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
    $params['value']=buildFormValueParam($name,$params);
    if(!isset($params['max'])){$params['max']=5;}
    if(!isset($params['class'])){$params['class']='w_biggest';}
    if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
    if(isset($params['readonly'])){
    	$params['-readonly']=1;
    }
	$rtn = '<div id="'.$params['id'].'" style="display:flex;justify-content:flex-start;align-items:center;"';
	if(isset($params['displayif'])){
		$rtn .= ' data-displayif="'.$params['displayif'].'"';
		unset($params['displayif']);
	}
	if(isset($params['data-readonlyif'])){
		$rtn .= ' data-readonlyif="'.$params['data-readonlyif'].'"';
		unset($params['data-readonlyif']);
	}
	$rtn .= '>'.PHP_EOL;
	if(is_string($params['value'])){
		$params['value']=(float)$params['value'];
	}
	//return $x.printValue($params);
	for($x=1;$x<=$params['max'];$x++){
		$p=$x-1;
		$vr=round($params['value'],0);
		if($x <= $params['value']){$class='icon-star';}
		elseif($vr != $params['value'] && $vr > $p){
			$class='icon-star-half-empty';
		}
		else{$class='icon-star-empty';}
		if(!isset($params['-readonly'])){
			$class.=' w_pointer';
		}
		$class .= ' '.$params['class'];
		$rtn .= '	<span data-value="'.$x.'" class="'.$class.'"';
		if(!isset($params['-readonly'])){
			$rtn .= ' onclick="return wacss.setStarRating(this);"';
		}
		if(isset($params['style'])){
			$rtn .= ' style="'.$params['style'].'"';
		}
		$rtn .= '></span>'.PHP_EOL;
	}
	if(!isset($params['-readonly'])){
	$rtn .= '<input type="hidden" name="'.$name.'" value="'.$params['value'].'"';
		if(isset($params['required']) && $params['required']){$rtn .= ' data-required="1" data-blink="'.$params['id'].'"';}
		elseif(isset($params['_required']) && $params['_required']){$rtn .= ' data-required="1" data-blink="'.$params['id'].'"';}
		$rtn .=' >'.PHP_EOL;
	}
	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
}
//---------- begin function buildFormSubmit-------------------
/**
* @describe creates an HTML submit tag
* @param val string - defaults to Submit
* @param name string - defaults to blank
* @param onclick string - defaults to blank
* @return string
* @usage if(!buildDir('/var/www/test/folder/sub/test')){return 'failed to build dir';}
*/
function buildFormSubmit($val='Submit',$name='',$onclick='',$class=''){
	$rtn = '<button class="btn '.$class.'" type="submit" value="'.$val.'"';
	if(strlen($name)){$rtn .= ' name="'.$name.'"';}
	if(strlen($onclick)){$rtn .= ' onclick="'.$onclick.'"';}
	$rtn .= '> '.$val."</button>";
	return $rtn;
}
//---------- begin function buildFormWYSIWYG--------------------
/**
* @describe creates an HTML WYSIWYG Editor
* @param name string
* @param params array
* @return string
* @usage echo buildFormTextarea('name',$params);
*/
function buildFormWYSIWYG($name,$params=array()){
	if(!isset($params['-formname'])){$params['-formname']='addedit';}
	if(isset($params['name'])){$name=$params['name'];}
	if(!isset($params['id'])){$params['id']=$params['-formname'].'_'.$name;}
	unset($params['width']);
	$params['style']='width:100%';
	if(!isset($params['height'])){$params['height']=300;}
	if(!isset($params['class'])){$params['class']='wacssedit';}
	else{
		$params['class'] .= ' wacssedit';
	}
	$params['value']=buildFormValueParam($name,$params);
	if(isset($params['requiredif'])){$params['data-requiredif']=$params['requiredif'];}
	if(isset($params['height'])){
		if(isNum($params['height'])){$params['height'].='px';}
		if(!stringContains($params['style'],'height')){
			$params['style'].=";height:{$params['height']};";
		}
	}
	$params['wrap']="soft";
	$params['name']=$name;
	$tag='';
	$tag .= '	<textarea';
	$tag .= setTagAttributes($params);
	$tag .= ' >';
	$tag .= encodeHtml($params['value']);
	$tag .= '</textarea>'.PHP_EOL;
	return $tag;
}
//---------- begin function buildHtmlBegin-------------------
/**
* @describe creates beginning html head and body
* @param title string - defaults to blank
* @return string
* @usage echo buildHtmlBegin();
*/
function buildHtmlBegin($params=array()){
	$rtn='';
	$rtn .= '<!DOCTYPE HTML>'.PHP_EOL;
	if(isset($_SERVER['LANG'])){
		$rtn .= '<html lang="'.$_SERVER['LANG'].'">'.PHP_EOL;
		}
	else{$rtn .= '<html lang="en" style="max-width:100%; overflow-x:hidden;">'.PHP_EOL;}
	$rtn .= '<head>'.PHP_EOL;
	$rtn .= ' 	<link rel="icon" href="/wfiles/favicon.ico" type="image/x-icon" >'.PHP_EOL;
	$rtn .= ' 	<link rel="shortcut icon" href="/wfiles/favicon.ico" type="image/x-icon" >'.PHP_EOL;
	$title=setValue(array($params['title'],"WaSQL - {$_SERVER['HTTP_HOST']}"));
	$rtn .= ' 	<title>'.$title.'</title>'.PHP_EOL;
	$rtn .= ' 	<meta http-equiv="content-type" content="text/html; charset=UTF-8" >'.PHP_EOL;
	$rtn .= ' 	<meta name="robots" content="noindex, nofollow, noarchive" >'.PHP_EOL;
	$rtn .= ' 	<meta name="viewport" content="width=device-width, initial-scale=1" >'.PHP_EOL;
	//set the order of compatibility view for IE - dumb I know, but it works.
	$rtn .= ' 	<link type="text/css" rel="stylesheet" href="'.minifyCssFile('wacss').'" >'.PHP_EOL;
	if(isset($params['css']) && strlen($params['css'])){
		$rtn .= $params['css'] .PHP_EOL;
	}
	$rtn .= ' 	<script type="text/javascript" src="'.minifyJsFile('wacss').'"> </script>'.PHP_EOL;
	if(isset($params['js'])){
		$rtn .= $params['js'];
	}
	$rtn .= '</head>'.PHP_EOL;
	$rtn .= '<body style="background:#FFFFFF;margin:0px;padding:0px;max-width:100%;">'.PHP_EOL;
	return $rtn;
	}

//---------- begin function buildHtmlEnd-------------------
/**
* @describe creates ending html head and body
* @return string
* @usage echo buildHtmlEnd();
*/
function buildHtmlEnd(){
	$rtn='';
	$rtn .= '</body>'.PHP_EOL;
	$rtn .= '</html>'.PHP_EOL;
	return $rtn;
	}
//---------- begin function buildImage
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function buildImage($name,$size=16){
	$src=getImageSrc($name,$size);
	return '<img src="'.$src.'" border="0" alt="'.$name.'" >';
}
//---------- begin function buildInfoBox
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function buildInfoBox($top,$bot,$min=50,$max=200,$style=''){
	$box='';
	$box .= '<div id="w_infobox" '.$style.'>'.PHP_EOL;
	$box .= '	<div id="w_infobox_topleft"></div>'.PHP_EOL;
	$box .= '	<div id="w_infobox_topright"></div>'.PHP_EOL;
	$box .= '	<div id="w_infobox_content"  '.$style.' data-behavior="animate" min="'.$min.'" max="'.$max.'">'.PHP_EOL;
	$box .= '		<div id="w_infobox_content_top">'.PHP_EOL;
	$box .= "			{$top}\n";
	$box .= '		</div>'.PHP_EOL;
	$box .= '		<div id="w_infobox_content_bottom">'.PHP_EOL;
	$box .= "			{$bot}\n";
	$box .= '		</div>'.PHP_EOL;
	$box .= '	</div>'.PHP_EOL;
	$box .= '</div>'.PHP_EOL;
	return $box;
}
//---------- begin function buildMsnCtt
/**
* @describe - returns an xml file formatted as an msn ctt contact list file
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function buildMsnCtt($emails=array(),$push=0,$name='msn_contacts.ctt'){
	$xml=xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
	$xml .= '<messenger>'.PHP_EOL;
	$xml .= '  <service name=".NET Messenger Service">'.PHP_EOL;
	$xml .= '    <contactlist>'.PHP_EOL;
	//msn has a 1000 max limit
	$cnt=0;
	foreach($emails as $email){
		$cnt++;
		if($cnt > 1000){break;}
		if(isEmail($email)){
			$xml .= '    	<contact>'.$email.'</contact>'.PHP_EOL;
        	}
    	}
	$xml .= '    </contactlist>'.PHP_EOL;
	$xml .= '  </service>'.PHP_EOL;
	$xml .= '</messenger>'.PHP_EOL;
	if($push){
		pushData($xml,'xml',$name);
		}
	return $xml;
	}
//---------- begin function buildOnLoad
/**
* @describe executes javascript in an ajax call by builing an image and invoking onload
* @param str string - javascript to invoke on load
* @return string - image tag with the specified javascript string invoiked onload
* @usage echo buildOnLoad("document.myform.myfield.focus();");
*/
function buildOnLoad($str='',$img='/wfiles/clear.gif',$width=1,$height=1){
	return '<div style="display:none;" data-wacss_onload="'.$str.'"></div>';
	return '<img class="w_buildonload" src="'.$img.'" alt="onload functions" width="'.$width.'" height="'.$height.'" style="border:0px;" onload="if(typeof eventBuildOnLoad === \'function\'){eventBuildOnLoad();}" data-onload="'.$str.'">'.PHP_EOL;
	}
//---------- begin function buildShareButtons
/**
* @describe build share buttons for facebook, twitter, email, google +, etc.
* @param opts array
*	options array - possible values are -url,-size,-show,-hide,-title
*	-url - url to share. defaults to current page.
*	-size - size of buttons. defaults to 32. Possible values are 16, 32, 64
*	-show - buttons to show. defaults to all. comma separate values. Possible values are facebook,twitter,email,linkedin,google+,reddit
*	-hide - buttons to hide. defaults to none. comma separate values. Possible values are facebook,twitter,email,linkedin,google+,reddit
*	-title - title to share. default to none
* @return
*	links with buttons
* @usage return buildShareButtons();
*/
function buildShareButtons($params=array()){
	loadExtrasCss('socialbuttons');
	//set defaults
	if(!isset($params['-url'])){
		$prefix=isSecure()?'https://':'http://';
		$params['-url']=$prefix.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}
	if(!isset($params['-size'])){$params['-size']=32;}
	if(!isset($params['-show'])){$params['-show']='facebook,twitter,linkedin,google+,reddit,email';}
	if(!isset($params['-hide'])){$params['-hide']='';}
	if(!isset($params['-title'])){$params['-title']='';}

	//show
	$show=array();
	if(!is_array($params['-show'])){
		$params['-show']=preg_split('/\,/',trim(strtolower($params['-show'])));
	}
	foreach($params['-show'] as $button){
		$button=trim(strtolower($button));
		if(!strlen($button)){continue;}
		$show[]=$button;
	}
	//hide
	$hide=array();
	if(!is_array($params['-hide'])){
		$params['-hide']=preg_split('/\,/',trim(strtolower($params['-hide'])));
	}
	foreach($params['-hide'] as $button){
		$button=trim(strtolower($button));
		if(!strlen($button)){continue;}
		$hide[]=$button;
	}
	$rtn='';
	$rtn .= '<div class="w_share">'.PHP_EOL;
	foreach($show as $button){
		//skip buttons set to hide
		if(in_array($button,$hide)){continue;}
		switch($button){
        	case 'facebook':
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://www.facebook.com/sharer.php?u='.$params['-url'].'"><img src="/wfiles/iconsets/'.$params['-size'].'/facebook.png" width="'.$params['-size'].'" height="'.$params['-size'].'" class="w_middle" data-tooltip="Share on Facebook" data-tooltip_position="bottom" alt="Share on Facebook"></a>'.PHP_EOL;
        	break;
        	case 'twitter':
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://twitter.com/share?text='.$params['-title'].'&url='.$params['-url'].'"><img src="/wfiles/iconsets/'.$params['-size'].'/twitter.png" width="'.$params['-size'].'" height="'.$params['-size'].'" class="w_middle" data-tooltip="att:alt" data-tooltip_position="bottom" alt="Share on twitter"></a>'.PHP_EOL;
        	break;
        	case 'google+':
        	case 'google plus':
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="https://plus.google.com/share?url='.$params['-url'].'"><img src="/wfiles/iconsets/'.$params['-size'].'/googleplus.png" width="'.$params['-size'].'" height="'.$params['-size'].'" class="w_middle" data-tooltip="att:alt" data-tooltip_position="bottom" alt="Share on Google+"></a>'.PHP_EOL;
        	break;
        	case 'linkedin':
        	case 'linked in':
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://www.linkedin.com/shareArticle?title='.$params['-title'].'&mini=true&url='.$params['-url'].'"><img src="/wfiles/iconsets/'.$params['-size'].'/linkedin.png" width="'.$params['-size'].'" height="'.$params['-size'].'" class="w_middle" data-tooltip="att:alt" data-tooltip_position="bottom" alt="Share on LinkedIn"></a>'.PHP_EOL;
        	break;
        	case 'reddit':
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://www.reddit.com/submit?title='.$blogt.'&url='.$params['-url'].'"><img src="/wfiles/iconsets/'.$params['-size'].'/reddit.png" width="'.$params['-size'].'" height="'.$params['-size'].'" class="w_middle" data-tooltip="att:alt" data-tooltip_position="bottom" alt="Share on reddit"></a>'.PHP_EOL;
        	break;
        	case 'email':
        		$rtn .= '		<a onclick="return w_shareButton(this.href,\'email\');" href="'.$params['-url'].'" class="blog_color w_link"><img src="/wfiles/iconsets/'.$params['-size'].'/email.png" width="'.$params['-size'].'" height="'.$params['-size'].'" class="w_middle" data-tooltip="att:alt" data-tooltip_position="bottom" alt="Share via email"></a>'.PHP_EOL;
        	break;
        	case 'comment':
        		$rtn .= '		<a onclick="return w_shareButton(this.href,\'comment\');" href="'.$params['-url'].'" style="margin-left:25px;" class="blog_color w_link"><img src="/wfiles/iconsets/16/note.png" width="16" height="16" class="w_middle" data-tooltip="att:alt" data-tooltip_position="bottom" alt="Click to view comments and add a comment"> '.$comments[$id]['cnt'].' comments</a>'.PHP_EOL;
        	break;
		}
	}
	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
}
//---------- begin function buildShareLinks
/**
* @describe build share links for facebook, twitter, linkedin, reddit, tumblr, google+, pinterest, email
* @param opts array
*	options array - possible values are -url,-size,-show,-hide,-title
*	-url - url to share. defaults to current page.
*	-class - class to pass to all buttons
*	-show - buttons to show. defaults to all. comma separate values. Possible values are facebook,twitter,email,linkedin,google+,reddit
*	-hide - buttons to hide. defaults to none. comma separate values. Possible values are facebook,twitter,email,linkedin,google+,reddit
*	-title - title to share. default to none
* @return
*	links with buttons
* @usage return buildShareButtons();
*/
function buildShareLinks($params=array()){
	//set defaults
	if(!isset($params['-url'])){
		$prefix=isSecure()?'https://':'http://';
		$params['-url']=$prefix.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}
	if(!isset($params['-class'])){$params['-class']='';}
	if(!isset($params['-show'])){$params['-show']='facebook,twitter,linkedin,reddit,tumblr,google+,pinterest,email';}
	if(!isset($params['-hide'])){$params['-hide']='';}
	if(!isset($params['-title'])){$params['-title']='';}
	$params['-title']=encodeURL($params['-title']);
	$params['-url']=encodeURL($params['-url']);

	//show
	$show=array();
	if(!is_array($params['-show'])){
		$params['-show']=preg_split('/\,/',trim(strtolower($params['-show'])));
	}
	foreach($params['-show'] as $button){
		$button=trim(strtolower($button));
		if(!strlen($button)){continue;}
		$show[]=$button;
	}
	//hide
	$hide=array();
	if(!is_array($params['-hide'])){
		$params['-hide']=preg_split('/\,/',trim(strtolower($params['-hide'])));
	}
	foreach($params['-hide'] as $button){
		$button=trim(strtolower($button));
		if(!strlen($button)){continue;}
		$hide[]=$button;
	}
	$rtn='';
	foreach($show as $button){
		//skip buttons set to hide
		if(in_array($button,$hide)){continue;}
		switch($button){
        	case 'facebook':
        		$icon='icon-site-facebook';
        		if(isset($params['facebook_icon'])){$icon=$params['facebook_icon'];}
        		if(isset($params['-class'])){$icon.=" {$params['-class']}";}
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://www.facebook.com/sharer.php?m2w&s=100&p[url]='.$params['-url'].'&p[images][0]=&p[title]='.$params['-title'].'" title="facebook"><span class="'.$icon.'"><span></a>'.PHP_EOL;
        	break;
        	case 'twitter':
        		$icon='icon-site-twitter-bird';
        		if(isset($params['twitter_icon'])){$icon=$params['twitter_icon'];}
        		if(isset($params['-class'])){$icon.=" {$params['-class']}";}
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://twitter.com/share?text='.$params['-title'].'&url='.$params['-url'].'"><span class="'.$icon.'"><span></a>'.PHP_EOL;
        	break;
        	case 'linkedin':
        	case 'linked in':
        		$icon='icon-site-linkedin';
        		if(isset($params['linkedin_icon'])){$icon=$params['linkedin_icon'];}
        		if(isset($params['-class'])){$icon.=" {$params['-class']}";}
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://www.linkedin.com/shareArticle?title='.$params['-title'].'&mini=true&url='.$params['-url'].'"><span class="'.$icon.'"><span></a>'.PHP_EOL;
        	break;
        	case 'reddit':
        		$icon='icon-site-reddit';
        		if(isset($params['facebook_icon'])){$icon=$params['facebook_icon'];}
        		if(isset($params['-class'])){$icon.=" {$params['-class']}";}
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://www.reddit.com/submit?title='.$blogt.'&url='.$params['-url'].'"><span class="'.$icon.'"><span></a>'.PHP_EOL;
        	break;
        	case 'tumblr':
        		$icon='icon-site-tumblr';
        		if(isset($params['tumblr_icon'])){$icon=$params['tumblr_icon'];}
        		if(isset($params['-class'])){$icon.=" {$params['-class']}";}
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="http://www.tumblr.com/share/link?url='.$params['-url'].'&name='.$params['-title'].'&description='.$params['-title'].'"><span class="'.$icon.'"><span></a>'.PHP_EOL;
        	break;
        	case 'google+':
        	case 'google plus':
        		$icon='icon-site-gplus';
        		if(isset($params['google_icon'])){$icon=$params['google_icon'];}
        		if(isset($params['-class'])){$icon.=" {$params['-class']}";}
        		$rtn .= '		<a onclick="return w_shareButton(this.href);" href="https://plus.google.com/share?url='.$params['-url'].'"><span class="'.$icon.'"><span></a>'.PHP_EOL;
        	break;
			case 'pinterest':
				/*
					NOTE: Pinterest requires the following script to be in your template:
					Reference Link: https://developers.pinterest.com/docs/widgets/save/?
					Script:
					<script type="text/javascript" async defer src="//assets.pinterest.com/js/pinit.js"></script>
				*/
        		$icon='icon-site-pinterest';
        		if(isset($params['pinterest_icon'])){$icon=$params['pinterest_icon'];}
        		if(isset($params['-class'])){$icon.=" {$params['-class']}";}
        		$rtn .= '		<a href="http://pinterest.com/pin/create/button/?url='.$params['-url'].'&description='.$params['-title'].'&media=" data-pin-custom="true"><span class="'.$icon.'"><span></a>'.PHP_EOL;
        	break;
        	case 'email':
        		//mailto allows subject, cc, bcc, and body as parameters
        		$icon='icon-mail';
        		if(isset($params['mail_icon'])){$icon=$params['mail_icon'];}
        		if(isset($params['-class'])){$icon.=" {$params['-class']}";}
        		$rtn .= '		<a href="mailto:?subject='.encodeURL($params['-title']).'&body='.encodeURL($params['-url']).'" class="blog_color w_link"><span class="'.$icon.'"><span></a>'.PHP_EOL;
        	break;
		}
	}
	return $rtn;
}
//---------- begin function buildSocialButtons
/**
* @describe build social buttons for facebook, twitter, email, google +, etc.  Uses a sprite
* @param opts array
*	options array - key/value pair with the key as the social site and the value as the URL
*	Possible social sites (keys) are:
*		Facebook, Twitter, Twitter_bird, YouTube, LinkedIn, Tumblr,
*		Googleplus, Instagram, Pinterest, Reddit, ShareThis, Slashdot
*		Delicious, Flickr, Digg, RSS, Tagged
*	-test - boolean. show all icons with generic links as a test with annimation and tooltips on.
*	-target - target name. defaults to no target.
*	-size - size of buttons. defaults to normal. Possible values are normal, small
*	-color - color buttons. defaults to true. Possible values are true, false
*	-annimate - annimate buttons. defaults to true. Possible values are true, false
*	-tooltip - show tooltip. defaults to false. Possible values are true, false
* @return string
*	links with buttons
* @usage return buildShareButtons(array('facebook'=>"http://www.facebook.com/mypage",'-tooltip'=>true));
*/
function buildSocialButtons($params=array()){
	//set the CSS to load
	loadExtrasCss('socialbuttons');
	//set defaults
	if(!isset($params['-size'])){$params['-size']='normal';}
	if(!isset($params['-color'])){$params['-color']=true;}
	if(!isset($params['-annimate'])){$params['-annimate']=true;}
	if(!isset($params['-tooltip'])){$params['-tooltip']=false;}
	if($params['-test']){
		$params['Facebook']="#";
		$params['Twitter']="#";
		$params['Twitter_bird']="#";
		$params['YouTube']="#";
		$params['LinkedIn']="#";
		$params['Tumblr,']="#";
		$params['Googleplus']="#";
		$params['Instagram']="#";
		$params['Pinterest']="#";
		$params['Reddit']="#";
		$params['ShareThis']="#";
		$params['Slashdot']="#";
		$params['Delicious']="#";
		$params['Flickr']="#";
		$params['Digg']="#";
		$params['RSS']="#";
		$params['Tagged']="#";
	}
	$rtn='';
	$rtn .= '<div class="w_social">'.PHP_EOL;
	foreach($params as $name=>$url){
		//skip params beginnig with a dash
		if(stringBeginsWith($name,'-')){continue;}
		$annimate=$params['-annimate']?' w_transition':'';
		$color=$params['-color']?'':'_bw';
		$size=$params['-size']=='normal'?'':'_small';
		$rtn .= '<a href="'.$url.'" class="w_'.strtolower($name).$size.$color.$annimate.'"';
		if($params['-tooltip']){
        	$rtn .= ' data-tooltip="'.$name.'" data-tooltip_position="bottom"';
		}
		if($params['-target']){
        	$rtn .= ' target="'.$params['-target'].'"';
		}
		$rtn .= '></a>'.PHP_EOL;
	}
	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
}

//---------- begin function buildTableBegin
/**
* @describe build the begining table tag
* @param int padding
*	cellpadding value - default to 2
* @param int border
*	border width - defaults to 0
* @param boolean
*	sort table? - defaults to 0
* @param string
*	width - defaults to null
* @return
*	beginining table tag with specified padding and border
* @usage return buildTableBegin(2,1);
*/
function buildTableBegin($padding=2,$border=0,$sortable=0,$width=''){
	$class='w_table';
	if(strlen($width)){$width=' width="'.$width.'"';}
	if($border){$class.=' w_border';}
	if($sortable){$class.=' sortable';}
	if($padding){$class.=' w_pad';}
	else{$class .= ' w_nopad';}
	return '<table class="'.$class.'"'.$width.'>'.PHP_EOL;
	}
//---------- begin function buildTableEnd
/**
* @describe build the ending table tag
* @return
*	ending table tag
* @usage echo buildTableEnd();
*/
function buildTableEnd(){
	return '</table>'.PHP_EOL;
	}
//---------- begin function buildTableRow---------------
/**
* @describe build a single row table
* @param vals array of values
*	array of values to place in TH tags
* @param params array
*	parameters - currently only supports align
* @return
*	table header row with specified values and alignment
* @usage echo buildTableRow(array('Name','Age','Color'),array('align'=>"right"));
*/
function buildTableRow($arr=array(),$opts=array()){
	$rtn = buildTableBegin(0,0);
	$rtn .= buildTableTD($arr,$opts);
	$rtn .= buildTableEnd();
	return  $rtn;
}
//---------- begin function buildTableTH
/**
* @describe build table header row
* @param vals array of values
*	array of values to place in TH tags
* @param params array
*	parameters - currently only supports align
* @return
*	table header row with specified values and alignment
* @usage echo buildTableTH(array('Name','Age','Color'),array('align'=>"right"));
*/
function buildTableTH($vals=array(),$params=array()){
	if(!isset($params['align'])){$params['align']="center";}
	$rtn='';
	if(isset($params['thead'])){$rtn .= '	<thead>'.PHP_EOL;}
	$rtn .= '	<tr align="'.$params['align'].'">'.PHP_EOL;
	foreach($vals as $val){
		$rtn .= '		<th>'.$val.'</th>'.PHP_EOL;
		}
	$rtn .= '	</tr>'.PHP_EOL;
	if(isset($params['thead'])){$rtn .= '	</thead>'.PHP_EOL;}
	return $rtn;
	}
//---------- begin function buildTableTD
/**
* @describe build table  row
* @param vals array of values
*	array of values to place in TD tags
* @param params array
*	parameters - currenlty only supports align, valign,class,style
* @return
*	table row with specified values and alignment
* @usage echo buildTableTD(array('Bill Smith',45,'blue'),array('align'=>"right"));
*/
function buildTableTD($vals=array(),$params=array()){
	$rtn='';
	$rtn .= '	<tr>'.PHP_EOL;

	foreach($vals as $val){
		$rtn .= '		<td';
		if(isset($params['valign'])){$rtn .= ' valign="'.$params['valign'].'"';}
		if(isset($params['align'])){$rtn .= ' align="'.$params['align'].'"';}
		if(isset($params['class'])){$rtn .= ' class="'.$params['class'].'"';}
		if(isset($params['style'])){$rtn .= ' style="'.$params['style'].'"';}
		$rtn .= '>'.$val.'</td>'.PHP_EOL;
		}
	$rtn .= '	</tr>'.PHP_EOL;
	return $rtn;
	}
///---------- begin function buildUrl ----------
/**
* @describe
*	converts an array into a URL-encoded query string
* @param arr array
*	a key/value pair array
* @return URL encoded string from the key/value pairs in array
* @usage $url=buildUrl($arr);
*/
function buildUrl($parts=array()){
	$uparts=array();
	foreach($parts as $key=>$val){
		if(preg_match('/^(PHPSESSID|GUID|debug|error|username|password|add_result|domain_href|add_id|add_table|edit_result|edit_id|edit_table|)$/i',$key)){continue;}
		if(preg_match('/^\_(login|pwe|try|formfields|action|view|formname|enctype|fields|csuid|csoot)$/i',$key)){continue;}
		if(!is_string($val) && !isNum($val)){continue;}
		if(!strlen(trim($val))){continue;}
		if($val=='Array'){continue;}
		array_push($uparts,"{$key}=" . encodeURL($val));
    	}
    $url=implode('&',$uparts);
    return $url;
	}
//---------- begin function calculateDistance--------------------------------------
/**
* @describe distance between two longitude & latitude points
* @param lat1 float - First Latitude
* @param lon1 float - First Longitude
* @param lat2 float - Second Latitude
* @param lon2 float - Second Longitude
* @param unit char - unit of measure - K=kilometere, N=nautical miles, M=Miles
* @return distance float
* @usage
*  $dist=calculateDistance($lat1, $lon1, $lat2, $lon2);
*/
function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit='M'){
	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;
	switch(strtoupper($unit)){
		case 'F':
		case 'FEET':
			//feet - 5280 feet in a mile
			return round(($miles*5280),2);
			break;
		case 'METERS':
			//feet - 1609.34 meters in a mile
			return round(($miles*1609.34),2);
			break;
		case 'K':
		case 'KILOMETERS':
			//kilometers
			return ($miles*1.609344);
			break;
		case 'N':
		case 'NAUTICAL MILES':
			//nautical miles
			return ($miles * 0.8684);
			break;
		default:
			//miles
			return $miles;
	}
}
function calculateBearing($lat1, $lon1, $lat2, $lon2){
	// Convert degrees to radians
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);    
        
    $dlon = $lon2 - $lon1;
    $x = cos($lat2) * sin($dlon);
    $y = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dlon);
    
    $heading = atan2($x,$y);
    
    // We want heading in degrees, not radians.
    $heading = rad2deg($heading);
  	
    // We want a uniform heading of >=0 and <360
    if ($heading < 0){
        $heading = 360.0 + $heading;
    }
    
    return array($x,$y,$heading);
}
//---------- begin function cleanDir--------------------------------------
/**
* @describe removes all files and sub-directories in a directory recursively
* @param dir string
*	the absolute path tho the directory to clean
* @return bolean
*	returns true on success
* @usage
* $ok=cleanDir($path);
*/
function cleanDir($dir='') {
	if(!is_dir($dir)){return false;}
	if ($handle = opendir($dir)) {
    	while (false !== ($file = readdir($handle))) {
			if($file == '.' || $file == '..'){continue;}
			$afile="{$dir}/{$file}";
			if(is_dir($afile)){
				cleanDir($afile);
				rmdir($afile);
            	}
            else{
				unlink($afile);
            	}
    		}
    	closedir($handle);
		}
	return true;
	}

//---------- begin function cmdResults---------------
/**
* @describe executes command and returns results
* @param cmd string - the command to execute
* @param [args] string - a string of arguments to pass to the command 
* @param [dir] string - directory
* @param [timeout] integer - seconds to let process run for. Defaults to 0 - unlimited
* @return string - returns the results of executing the command
* @usage $out=cmdResults('ls -al');
*/
function cmdResults($cmd,$args='',$dir='',$timeout=0){
	$starttime=microtime(true);
	if(!is_dir($dir)){$dir=realpath('.');}
	if(strlen($args)){$cmd .= ' '.trim($args);}
	//windows OS requires the stderr pipe to be write
	if(isWindows()){
		$proc=@proc_open($cmd,
			array(
				0=>array('pipe', 'r'), //stdin
				1=>array('pipe', 'w'), //stdout
				2=>array('pipe', 'w')  //stderr
				),
			$pipes,
			$dir,
			null,
			array('bypass_shell'=>true)
		);
		if(!$proc){
			return array(
				'cmd'=>$cmd,
				'dir'=>$dir,
				'rtncode'=>123,
				'runtime'=>microtime(true)-$starttime,
				'stderr'=>'Create Process Failed - Verify cmd exists'
			);
		}
		stream_set_blocking($pipes[1], 0);
		stream_set_blocking($pipes[2], 0);
	}
	else{
		if($timeout != 0 && isNum($timeout)){
			//this will kill the process if it goes longer than timeout
	    	$cmd="($cmd) & WPID=\$!; sleep {$timeout} && kill \$WPID > /dev/null 2>&1 & wait \$WPID";
		}
		$proc=proc_open($cmd,
			array(
				0=>array('pipe', 'r'), //stdin
				1=>array('pipe', 'w'), //stdout
				2=>array('pipe', 'a')  //stderr
				),
			$pipes,
			$dir
		);
		stream_set_blocking($pipes[2], 0);
	}
    //fwrite($pipes[0], $args);
	fclose($pipes[0]);
    $stdout=@stream_get_contents($pipes[1]);fclose($pipes[1]);
    $stderr=@stream_get_contents($pipes[2]);fclose($pipes[2]);
    $rtncode=proc_close($proc);
    $rtn=array(
    	'cmd'	=> $cmd,
    	'args'	=> $args,
    	'dir'	=> $dir,
		'stdout'=>$stdout,
        'stderr'=>$stderr,
        'runtime'=>microtime(true)-$starttime,
        'rtncode'=>$rtncode
    );
    //remove blank vals
    foreach($rtn as $k=>$v){
    	if(!is_array($v)){
			if(!strlen($v)){unset($rtn[$k]);}
    		else{$rtn[$k]=trim($v);}
		}
	}
	return $rtn;
}
//---------- begin function copyFile--------------------
/**
* @describe copies file from source to destination using stream_copy_to_stream for speed and efficiency
* @param source string - source file
* @param dest string - destination file
* @return integer - returns number of bytes copied or error string
* @usage $b=copyFile($srcFile,$destFile);
*/
function copyFile($src, $dest){
	//stream_copy_to_stream is more efficient and faster than a copy command
	//Returns the total count of bytes copied  or error.
    $fsrc = fopen($src,'r');
    if(!is_resource($fsrc)){return 'src fopen failed: '.$src;}
    $fdest = fopen($dest,'c+');
    if(!is_resource($fdest)){return 'dest fopen failed: '.$dest;}
    $bytes = stream_copy_to_stream($fsrc,$fdest);
    fclose($fsrc);
    fclose($fdest);
    return $bytes;
}

//---------- begin function streamCmdResults--------------------------------------
/**
* @describe executes command and streams results to said function, then returns exit code
* @param cmd string
*	the command to execute including args
* @param functionName string
*	function to stream stdout to - defaults to echo. If set, the the buffer and the pstatus array from proc_get_status will be passed to it
* @param functionName string
*	function to stream stderr to - defaults to echo. If set, the the buffer and the pstatus array from proc_get_status will be passed to it
* @return integer
*	returns the exit code
* @usage
* $ok=streamCmdResults('dir','processDirLines');
*/
function streamCmdResults($cmd,$func='',$errfunc=''){
	//setup constants
	$buf_siz=1024;
	$fd_write=0;
	$fd_read=1;
	$fd_err=2;
	$descriptorspec = array(
    	$fd_write	=> array("pipe", "r"),
    	$fd_read 	=> array("pipe", "w"),
    	$fd_err 	=> array("pipe", "w")
	);
	$ptr = proc_open($cmd, $descriptorspec, $pipes, NULL, $_ENV);
    if (!is_resource($ptr)){
		//no resource available - return a bogus exit code. returning false would return 0, which means success
        return 1234;
	}
	//get the output
    while (($buffer = fgets($pipes[$fd_read], $buf_siz)) != NULL || ($errbuf = fgets($pipes[$fd_err], $buf_siz)) != NULL) {
		$pstatus = proc_get_status($ptr);
        if (!isset($flag)) {
            $first_exitcode = $pstatus["exitcode"];
            $flag = true;
        }
        //stdout
        if (strlen($buffer)){
			if(strlen($func)){
            	//pass stdout to this function
            	$func($buffer,$pstatus);
			}
			else{echo $buffer;}
		}
		//stderr
        if (strlen($errbuf)){
			if(strlen($errfunc)){
            	//pass stderr to this function
            	$errfunc($errbuf,$pstatus);
			}
			else{echo $errbuf;}
		}
	}
	//close the pipes
	foreach ($pipes as $pipe){fclose($pipe);}

    /* Get the expected *exit* code to return the value */
    $pstatus = proc_get_status($ptr);
    if (!strlen($pstatus["exitcode"]) || $pstatus["running"]) {
        /* we can trust the retval of proc_close() */
        if ($pstatus["running"]){proc_terminate($ptr);}
        $ret = proc_close($ptr);
    }
	else {
        if ((($first_exitcode + 256) % 256) == 255 && (($pstatus["exitcode"] + 256) % 256) != 255){
            $ret = $pstatus["exitcode"];
		}
        elseif (!strlen($first_exitcode)){$ret = $pstatus["exitcode"];}
        elseif ((($first_exitcode + 256) % 256) != 255){$ret = $first_exitcode;}
        else{
            $ret = 0; /* we "deduce" an EXIT_SUCCESS ;) */
		}
        proc_close($ptr);
    }
	return ($ret + 256) % 256;
}
//---------- begin function tailFile--------------------------------------
/**
* @describe returns last x lines of file
* @param afile string - full path to file to tail
* @param [lines] int - number of lines. defaults to 10
* @param adaptive boolean - set to false to give a performance boost
* @return string
* @usage $last=tailFile($afile);
*/
function tailFile($afile, $lines = 10, $adaptive = true) {
	// Open file
	$f = @fopen($afile, "rb");
	if ($f === false) return false;
	// Sets buffer size, according to the number of lines to retrieve.
	// This gives a performance boost when reading a few lines from the file.
	if (!$adaptive){$buffer = 4096;}
	else{$buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));}
	// Jump to last character
	fseek($f, -1, SEEK_END);
	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if (fread($f, 1) != "\n"){$lines -= 1;}
	// Start reading
	$output = '';
	$chunk = '';
	// While we would like more
	while (ftell($f) > 0 && $lines >= 0) {
		// Figure out how far back we should jump
		$seek = min(ftell($f), $buffer);
		// Do the jump (backwards, relative to where we are)
		fseek($f, -$seek, SEEK_CUR);
		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f, $seek)) . $output;
		// Jump back to where we started reading
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		// Decrease our line counter
		$lines -= substr_count($chunk, "\n");
	}
	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ($lines++ < 0) {
		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}
	// Close file and return
	fclose($f);
	return trim($output);
}
//---------- begin function headFile--------------------------------------
/**
* @describe returns first x lines of file
* @param afile string - full path to file to tail
* @param [lines] int - number of lines. defaults to 10
* @param adaptive boolean - set to false to give a performance boost
* @return array
* @usage $first=headFile($afile);
*/
function headFile($afile, $linecnt = 10, $adaptive = true) {
	// Open the file for reading
	$fh = fopen($afile, 'rb');
	// Handle failure
	if ($fh === false) {
	    return array('Could not open file: '.$file);
	}
	$lines=array();
	// Loop 3 times
	for ($i = 0; $i < $linecnt; $i++) {
	    // Read a line
	    $line = fgets($fh);

	    // If a line was read then include it, otherwise
	    // show an error
	    if ($line !== false) {
	        $lines[]=$line;
	    } else {
	        break;
	    }
	}
	// Close the file handle; when you are done using a
	// resource you should always close it immediately
	if (fclose($fh) === false) {
	    return array('Could not close file: '.$file);
	}
	return $lines;
}

//---------- begin function filterFile--------------------------------------
/**
* @describe returns only lines that contail search
* @param afile string - text file to read
* @param search string - string to search for
* @return lines array - array of lines that contain search string
* @usage $lines=filterFile($afile,'help');
*/
function filterFile($afile, $search) {
	$lines=array();
	if ($fh = fopen($afile,'r')) {
		while (!feof($fh)) {
			//stream_get_line is significantly faster than fgets
			$line = stream_get_line($fh, 1000000, "\n");
			if(stringContains($line,$search)){$lines[]=rtrim($line);}
		}
		fclose($fh);
	}
	return $lines;
}
//---------- begin function containsPHP--------------------------------------
/**
* @describe returns true if string contains PHP
* @param source string
*	the string to check
* @return boolean
*	returns true if string contains PHP, otherwise false
* @usage if(containsPHP($str)){....}
*/
function containsPHP($str){
	if(preg_match('/\<\?(.+?)\?\>/s',$str)){return true;}
	if(preg_match('/<script type\="php">(.+?)<\/script>/is',$str)){return true;}
	return false;
}
//---------- begin function convertString--------------------------------------
/**
* @describe converts string to specified encoding and auto detects what its encodind is.
* @param source string
*	the string to encode
* @param target_encoding string
*	the encoding, like UTF-8
* @return string
*	returns the encoded string
* @usage $string=convertString($string,'UTF-8');
*/
function convertString ( $source, $target_encoding ){
    // detect the character encoding of the incoming file
    $encoding = mb_detect_encoding( $source, "auto" );
    // escape all of the question marks so we can remove artifacts from
    // the unicode conversion process
    $target = str_replace( "?", "[question_mark]", $source );
    // convert the string to the target encoding
    $target = mb_convert_encoding( $target, $target_encoding, $encoding);
    // remove any question marks that have been introduced because of illegal characters
    $target = str_replace( "?", "", $target );
    // replace the token string "[question_mark]" with the symbol "?"
    $target = str_replace( "[question_mark]", "?", $target );
    return $target;
}
//---------- begin function convertTimezone--------------------------------------
/**
* @describe converts a datetime string from one timezone to another timezone
* @param datetime string - the date/datetime/time to convert
* @param from_timezone string - the current timezone 
* @param to_timezone string - the desired timezone
* @param [format] string - format to return the new date/datetime/time string in. Defaults to Y-m-d H:i:s
* @return string
* @usage $cdate=convertTimezone('2021-05-10 13:15:00','UTC','America/Denver','Y-m-d H:i:s');
* @reference https://dragosholban.com/2018/04/11/how-to-convert-date-and-time-from-one-time-zone-to-another-in-php/
*/
function convertTimezone($ds,$from_timezone,$to_timezone,$format='Y-m-d H:i:s'){
	$date = new DateTime($ds, new DateTimeZone($from_timezone));
	$date->setTimezone(new DateTimeZone($to_timezone));
	return $date->format($format);
}
//---------- begin function csvImplode--------------------------------------
/**
* @describe creates a csv string from an array
* @param arr array
*	the array to convert to a csv string
* @param delim char[optional]
*	The delimiter character - defaults to a comma
* @param enclose char
*	the enclose character - defaults to a double-quote
* @return
*	returns a csv string
* @usage $line=csvImplode($parts_array);
*/
function csvImplode($parts=array(),$delim=',', $enclose='"',$force=0){
	ob_start(); // buffer the output ...
	$csvImplodeFH = fopen('php://output', 'w'); // this file actual writes to php output
    fputcsv($csvImplodeFH, $parts, $delim, $enclose);
    fclose($csvImplodeFH);
    $line=ob_get_clean();
    $line=rtrim($line);
    $line=preg_replace('/[\r\n]+$/','',$line);
    return rtrim($line); // ... then return it as a string!
}

//---------- begin function csvParseLine--------------------------------------
/**
* @describe parses a csv line into a parts array.
* @param str string
*	the csv line string to parse
* @param delim[optionial] = , string
*	the delimeter - defaults to comman
* @param enclose[optionial] = " string
*	the enclose char - defaults to double quote
* @param preserve[optionial] = , boolean
*	preserve the quotes
* @return string
*	returns an array
* @usage
* $parts=csvParseLine($line);
*/
function csvParseLine($str,$delim=',', $enclose='"', $preserve=false){
	$resArr = array();
	$n = 0;
	if(!strlen($delim)){$delim=',';}
	if(!strlen($enclose)){$enclose='"';}
	$expEncArr = explode($enclose, $str);
  	foreach($expEncArr as $EncItem){
    	if($n++%2){
      		array_push($resArr, array_pop($resArr) . ($preserve?$enclose:'') . $EncItem.($preserve?$enclose:''));
    		}
		else{
      		$expDelArr = explode($delim, $EncItem);
      		array_push($resArr, array_pop($resArr) . array_shift($expDelArr));
      		$resArr = array_merge($resArr, $expDelArr);
    		}
  		}
	return $resArr;
	}

//---------- begin function addEditForm---------------------------------------
/**
* @deprecated use addEditDBForm instead
* @exclude  - this function is deprecated and thus excluded from the manual
*/
function curlExecute($curl_handle=''){
	$buffer = curl_exec($curl_handle);
	return $buffer;
	}


//---------- begin function addEditForm---------------------------------------
/**
* @deprecated use addEditDBForm instead
* @exclude  - this function is deprecated and thus excluded from the manual
*/
function array2String($arr){
	if(!is_array($arr)){return $arr;}
	$vals=array();
	foreach($arr as $a){
		if(is_array($a)){
			$val=implode(' ',$a);
			array_push($vals,$val);
			}
		else{
			array_push($vals,$a);
            }
		}
	return implode("\r\n",$vals);
	}
//---------- begin function array2XML---------------------------------------
/**
* @exclude  - this function is deprecated and thus excluded from the manual
*/
function array2XML($buffer=array(),$main='main',$item='item',$skip=0){
	$xml=xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
    $xml .= "<$main version=\"1.0\">\n";
    foreach($buffer as $val) {
        $xml .= "    <$item>\n";
        foreach ($val as $key => $value) {
			//skip keys that start with an underscore
			if($skip && preg_match('/^\_/',$key)){continue;}
			//skip keys that start with a number - invalid xml key
			if(preg_match('/^[0-9]/',$key)){continue;}
			//skip blank values
			if(strlen($value)==0){continue;}
			$val=utf8_encode($value);
			$val=xmlEncodeCDATA($val);
            $xml .= "        <{$key}>".$val."</{$key}>\n";
        	}

        $xml .= "    </$item>\n";
    	}
    $xml .= "</$main>\n";
    return $xml;
	}
//---------- begin function arrays2RSS ----------
/**
* @describe
*	converts getDBRecords results into an rss feed
*	RSS feeds must have 'title','link','description','pubDate' for the main and each record
* @param recs array
*	a getDBRecords result array
* @param params array
*	Params:
*		title - required - main title
*		link - required - main link
*		description - required - main description
*		pubdate - required - main pubdate
* @return string
*	RSS XML string
* @usage $rss=arrays2RSS($recs);
*/
function arrays2RSS($recs=array(),$params=array()){
	$fields=array('title','link','description','pubdate');
	foreach($fields as $field){
		if(!isset($params[$field])){return "missing main {$field}";}
	}
	//check to make sure each record also has a title, link, description, and pubdate
	foreach($recs as $rec){
		foreach($fields as $field){
			if(!isset($rec[$field])){return "missing record {$field} field in dataset";}
		}
		break;
	}
	//generate the xml for the RSS feed
	$xml=xmlHeader(array('version'=>'1.0','coding'=>'utf-8'));
	$xml.= '<rss version="2.0"'.PHP_EOL;
	$xml.= '	xmlns:content="http://purl.org/rss/1.0/modules/content/"'.PHP_EOL;
	//$xml.= '	xmlns:wfw="http://wellformedweb.org/CommentAPI/"'.PHP_EOL;
	//$xml.= '	xmlns:dc="http://purl.org/dc/elements/1.1/"'.PHP_EOL;
	$xml.= '	xmlns:atom="http://www.w3.org/2005/Atom"'.PHP_EOL;
	//$xml.= '	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"'.PHP_EOL;
	//$xml.= '	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"'.PHP_EOL;
	$xml.= '	>'.PHP_EOL;
	$xml.= '	<channel>'.PHP_EOL;
	$xml.= '		<title>'.$params['title'].'</title>'.PHP_EOL;
	if(isset($params['rss'])){
		$xml.= '		<atom:link href="'.xmlEncode($params['rss']).'" rel="self" type="application/rss+xml" >'.PHP_EOL;
	}
	$xml.= '		<link>'.xmlEncode($params['link']).'</link>'.PHP_EOL;
	$xml.= '		<description>'.xmlEncodeCDATA($params['description']).'</description>'.PHP_EOL;
	//$xml.= '		<lastBuildDate>Wed, 05 Dec 2012 12:00:49 +0000</lastBuildDate>'.PHP_EOL;
	if(!isset($params['language'])){$params['language']='en';}
	$xml.= '		<language>'.$params['language'].'</language>'.PHP_EOL;
	//$xml.= '		<sy:updatePeriod>hourly</sy:updatePeriod>'.PHP_EOL;
	//$xml.= '		<sy:updateFrequency>1</sy:updateFrequency>'.PHP_EOL;
	$xml.= '		<generator>http://wasql.com</generator>'.PHP_EOL;
	$xml.= '		<xhtml:meta xmlns:xhtml="http://www.w3.org/1999/xhtml" name="robots" content="noindex" >'.PHP_EOL;
	foreach($recs as $rec) {
		$xml .= '		<item>'.PHP_EOL;
		foreach($fields as $field){
			$val=$rec[$field];
			if(!isset($params['-utf8']) || $params['-utf8']==1){
				$val=utf8_encode($val);
			}
			if(!isset($params['-encode']) || $params['-encode']==1){
				$val=xmlEncodeCDATA($val);
			}
			else{
            	if(isXML($val)){$val= "<![CDATA[\n".$val."\n]]>";}
			}
			$key=$field=='pubdate'?'pubDate':$field;
    		$xml .= "   		<{$key}>{$val}</{$key}>\n";
		}
		$xml .= '   	</item>'.PHP_EOL;
	}
	$xml .= '	</channel>'.PHP_EOL;
	$xml .= '</rss>'.PHP_EOL;
    return $xml;
}
//---------- begin function arrays2CSV--------------------
/**
* @describe converts getDBRecords results into CSV file
* @param arr array - the array you want to convert
* @param params array - optional
*	[-fields] - comma separated list of fields to include in the csv
*	[-fieldmap] - field=>mapname array of fieldmaps to change the name on the first line
*	[-noheader] - do not include a header row
*	[-delim] - delimiter defaults to comma
*	[-enclose] - enclosed by defaults to quote
* @usage
* 	$csv=arrays2CSV($recs,array(
* 		'-fields'=>'name,age,color'
* 	));
* @return string - csv formatted output based on the recs array passed in
*/
function arrays2CSV($recs=array(),$params=array()){
	//defaults
	if(!isset($params['-delim'])){$params['-delim']=',';}
	if(!isset($params['-enclose'])){$params['-enclose']='"';}
	if(!is_array($recs) || !count($recs)){
		return "No records found";
	}
	//get fields for header row
	$fields=array();
	if(isset($params['-fields'])){
		if(is_array($params['-fields'])){$fields=$params['-fields'];}
		else{$fields=preg_split('/[\,\:\;]+/',trim($params['-fields']));}
	}
	else{
		$fields=array();
		foreach($recs as $rec){
        	foreach($rec as  $k=>$v){
            	if(!in_array($k,$fields)){$fields[]=$k;}
			}
		}
	}
	$fieldmap=array();
	foreach($fields as $field){
		$key=$field;
		if(isset($params['-fieldmap'][$field])){
			$field=$params['-fieldmap'][$field];
		}
		elseif(isset($params["{$field}_dname"])){
			$field=$params["{$field}_dname"];
		}
		elseif(isset($params["{$field}_displayname"])){
			$field=$params["{$field}_displayname"];
		}
		else{
			$field=strtolower(str_replace(' ','_',$field));
		}
		$fieldmap[$key]=$field;
	}
	$csvlines=array();
	if(!isset($params['-noheader']) || $params['-noheader']==0){
		if(isset($params['-force']) && $params['-force']){
			$csvlines[]=csvImplode(array_values($fieldmap),$params['-delim'],$params['-enclose'],1);
		}
		else{
        	$csvlines[]=csvImplode(array_values($fieldmap),$params['-delim'],$params['-enclose']);
		}

	}
	foreach($recs as $rec) {
		$vals=array();
		foreach($fieldmap as $field=>$dval){
			if(is_array($rec[$field])){
				$rec[$field]=json_encode($rec[$field],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
			}
        	$vals[]=$rec[$field];
		}
		if(isset($params['-force']) && $params['-force']){
			$csvlines[]=csvImplode($vals,$params['-delim'],$params['-enclose'],1);
		}
		else{
        	$csvlines[]=csvImplode($vals,$params['-delim'],$params['-enclose']);
		}
	}
    return implode("\r\n",$csvlines);
}
//---------- begin function arrays2TAB--------------------
/**
* @describe converts getDBRecords results into tab delimited file
* @param arr array
*	the array you want to convert
* @param params array
*	[-fields] - comma semarated list of fields to include in the csv
*	[-fieldmap] - field=>mapname array of fieldmaps to change the name on the first line
*	[-noheader] - do not include a header row
* @return string
*	csv formatted output based on the recs array passed in
* @usage
* $csv=arrays2TAB($recs);
*/
function arrays2TAB($recs=array(),$params=array()){
	//$delim=',', $enclose='"',$force=0
	if(!is_array($recs) || !count($recs)){
		return "No records found";
	}
	if(!isset($params['-delim'])){$params['-delim']="\t";}
	if(!isset($params['-enclose'])){$params['-enclose']='"';}
	//get fields for header row
	$fields=array();
	if(isset($params['-fields'])){
		if(is_array($params['-fields'])){$fields=$params['-fields'];}
		else{$fields=preg_split('/[\,\:\;]+/',trim($params['-fields']));}
	}
	else{$fields=array_keys($recs[0]);}
	$fieldmap=array();
	foreach($fields as $field){
		$key=$field;
		if(isset($params['-fieldmap'][$field])){
			$field=$params['-fieldmap'][$field];
		}
		elseif(isset($params["{$field}_dname"])){
			$field=$params["{$field}_dname"];
		}
		elseif(isset($params["{$field}_displayname"])){
			$field=$params["{$field}_displayname"];
		}
		$fieldmap[$key]=$field;
	}
	$lines=array();
	if(!isset($params['-noheader']) || $params['-noheader']==0){
		$lines[]=csvImplode(array_values($fieldmap),$params['-delim'],$params['-enclose']);
	}
	foreach($recs as $rec) {
		$vals=array();
		foreach($fieldmap as $field=>$dval){
        	$vals[]=$rec[$field];
		}
		$lines[]=csvImplode($vals,$params['-delim'],$params['-enclose']);
	}
    return implode("\r\n",$lines);
}
//---------- begin function arrays2XML--------------------
/**
* @describe converts getDBRecords results into XML
* @param arr array
*	the array you want to convert
* @param param array
*	optional parameters as follows:
*		[main] - name of main xml tag - defaults to main
*		[item] - name if item tag  - defaults to item
*		[skip] - skip keys that start with an underscore - defaults to 0 (false)
*		[header] - include xml header - defaults to 1
* @return string
*	xml based on the recs array passed in
* @usage
* $xml=arrays2XML($recs);
*/
function arrays2XML($recs=array(),$params=array()){
	if(!isset($params['main'])){$params['main']='main';}
	if(!isset($params['item'])){$params['item']='item';}
	if(!isset($params['skip'])){$params['skip']=0;}
	if(!isset($params['header'])){$params['header']=1;}
	if($params['header']==0 && isset($params['item2'])){$params['item']='item';}

	if($params['header']==1){
		$xml=xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
    	$xml .= "<{$params['main']} version=\"1.0\">\n";
	    $count=count($recs);
	    $total_count=isset($params['total'])?$params['total']:$count;
	    $xml .= '    <return_count>'.$count.'</return_count>'.PHP_EOL;
	    $xml .= '    <total_count>'.$total_count.'</total_count>'.PHP_EOL;
	    foreach($params as $key=>$val){
        	if(preg_match('/^(main|item|items|item2|skip|header|return_count|total_count|total)$/i',$key)){continue;}
			$xml .= '    <'.$key.'>'.xmlEncodeCDATA($params[$key]).'</'.$key.'>'.PHP_EOL;
		}
	}
	if(is_array($recs) && count($recs)){
		if($params['header']==1){$xml .= "<{$params['item']}s>\n";}
	    foreach($recs as $rec) {
	        $xml .= "    <{$params['item']}>\n";
	        foreach ($rec as $key => $value) {
				//skip keys that start with an underscore
				if($params['skip'] && preg_match('/^\_/',$key) && $key != '_id'){continue;}
				//skip keys that start with a number - invalid xml key
				if(preg_match('/^[0-9]/',$key)){continue;}
				//skip blank values
				if(is_array($value)){
					$cparams=$params;
					$cparams['header']=0;
					$cparams['item']=preg_replace('/s$/i','',$key);
					$val=arrays2XML($value,$cparams);
					$xml .= "        <{$key}>\n".$val."\n</{$key}>\n";
					}
				else{
					if(strlen($value)==0){continue;}
					$val=xmlEncodeCDATA($value);
	            	$xml .= "        <{$key}>".$val."</{$key}>\n";
	            	}
	        	}
	        $xml .= "    </{$params['item']}>\n";
	    	}
	    if($params['header']==1){$xml .= "</{$params['item']}s>\n";}
	    }
    if($params['header']==1){
    	$xml .= "</{$params['main']}>\n";
		}
    return $xml;
	}
//---------- begin function setSessionTemplate-----------------
/**
* @describe sets the current session template
* @param str string
* @author Brady Barten, bvfbarten@gmail.com, August 2013
* @return string
* @usage setSessionTemplate('test'); or setSessionTemplate(7); or setSessionTemplate('');
*/
function setSessionTemplate($str=''){
	if(!strlen($str)){unset($_SESSION['_template']);}
	else{
		$_SESSION['_template']=$str;
	}
}
//---------- begin function setStyleAttribues-----------------
/**
* @describe builds and style string out of a list of styles
* @param styles array
* @return string
* @usage $tag .= setStyleAttribues($styles);
*/
function setStyleAttribues($styles=array()){
	$parts=array();
    foreach($styles as $skey=>$val){
    	array_push($parts,$skey.':'.$val);
    	}
	return implode(';',$parts);
	}
//---------- begin function setTagAttributes-----------------
/**
* @describe builds and attribute string out of a list of attributes
* @param atts array
* @return string
* @usage $inputtag .= setTagAttributes($atts);
*/
function setTagAttributes($atts=array(),$skipatts=array()){
	$attstring='';
	//pass through common html attributes and ones used by submitForm and ajaxSubmitForm Validation js
	$htmlatts=array(
		'id','name','class','style','title','alt','accesskey','tabindex',
		'onclick','onchange','onmouseover','onmouseout','onmousedown','onmouseup','onkeypress','onkeyup','onkeydown','onblur','onfocus','oninvalid','oninput',
		'_behavior','display','capture',
		'required','requiredmsg','mask','maskmsg','displayname','size','minlength','maxlength','wrap','readonly','disabled',
		'placeholder','pattern','data-pattern-msg','spellcheck','max','min','readonly','step',
		'lang','autocorrect','list','data-requiredif','autofocus','accept','acceptmsg','autocomplete',
		'action','onsubmit'
		);
	if(isset($atts['pattern']) && !isset($atts['oninvalid']) && isset($atts['data-pattern_message'])){
		$atts['oninvalid']="setCustomValidity(this.getAttribute('data-pattern_message'));";
	}
	//autofocus
	if(isset($atts['autofocus'])){
		$atts['autofocus']="autofocus";
    }
	//required
	if(isset($atts['required']) && $atts['required']==1){
		$atts['required']="required";
    }
    if(isset($atts['_required']) && $atts['_required']==1){
		$atts['required']="required";
		unset($atts['_required']);
    }
	
    if(isset($atts['required']) && ($atts['required'] != 'required' && $atts['required'] == 0)){
		unset($atts['required']);
    }
    //inputmax
    if(isset($atts['inputmax']) && isNum($atts['inputmax']) && $atts['inputmax'] > 0){
		$atts['maxlength']=$atts['inputmax'];
		unset($atts['inputmax']);
    }
    //mask
    if(isset($atts['mask'])){
    	if(!isset($atts['data-mask'])){
			$atts['data-mask']=$atts['mask'];
    	}
		unset($atts['mask']);
    }
    //displayname
    if(isset($atts['displayname'])){
    	if(!isset($atts['data-displayname'])){
			$atts['data-displayname']=$atts['displayname'];
		}
		unset($atts['displayname']);
    }
	//behavior
	if(isset($atts['_behavior'])){
		if(!isset($atts['data-behavior'])){
			$atts['data-behavior']=$atts['_behavior'];
		}
		unset($atts['_behavior']);
    }
    if(isset($atts['behavior'])){
    	if(!isset($atts['data-behavior'])){
			$atts['data-behavior']=$atts['behavior'];
		}
		unset($atts['behavior']);
    }
    //readonly
    if(isset($atts['readonly'])){
		if(isNum($atts['readonly']) && $atts['readonly']==0){
			unset($atts['readonly']);
		}
		else{
			$atts['readonly']='readonly';
		}
    }
    //disabled
    if(isset($atts['disabled'])){
		if(isNum($atts['disabled']) && $atts['disabled']==0){
			unset($atts['disabled']);
		}
		else{
			$atts['disabled']='disabled';
		}
    }
    //build the string
	foreach($htmlatts as $att){
		if(in_array($att,$skipatts)){continue;}
		if(isset($atts[$att]) && strlen($atts[$att])){
			$val=removeHtml($atts[$att]);
			$val=str_replace('"',"'",$val);
			$attstring .= ' ' . $att . '="'.$val.'"';
		}
    }
    //allow any attribute that starts with a data-
    foreach($atts as $key=>$val){
		if(in_array($key,$skipatts)){continue;}
		if(stringBeginsWith($key,'data-')){
			$val=removeHtml($val);
			$val=str_replace('"',"'",$val);
			$attstring .= ' ' . $key . '="'.$val.'"';
		}
    }
	return $attstring;
	}
//---------- begin function getView--------------------
/**
* @describe gets the value of the specified view
* @param str string
* @return string
* @usage $txt=getView('email');
*/
function getView($name){
	global $PAGE;
	global $VIEWS;
	if(preg_match('/\<view\:'.$name.'\>(.+?)\<\/view\:'.$name.'\>/ism',$PAGE['body'],$m)){
		return $m[1];
	}
	if(isset($VIEWS[$name])){return $VIEWS[$name];}
	return '';
}
//---------- begin function setValue--------------------
/**
* @describe sets the value to the first valid value passed in
* @param vals array
* @return string or array based on what was passed in
* @author Jeremy Despain
* @usage $ok=setValue(array($_REQUEST['ok'],$_SESSION['ok'],32);
*/
function setValue($r,$default=''){
	if(is_array($r)){
		if(strlen($default)){$r[]=$default;}
		foreach($r as $val){
			if(is_array($val)){
				if(count($val)){return $val;}
				}
			elseif(isset($val)){
				if(strlen($val)){return $val;}
				}
        	}
    	}
    $val=$r;
    if(is_array($val)){
		if(count($val)){return $val;}
		}
	elseif(isset($val)){
		if(strlen($val)){return $val;}
		}
	$val=$default;
	if(is_array($val)){
		if(count($val)){return $val;}
		}
	elseif(isset($val)){
		if(strlen($val)){return $val;}
		}
	return null;
	}
//---------- begin function setValue--------------------
/**
* @describe turns on the view for the specified tag. To be used in the controller.
* @param name string
* @return 1
* @usage setView('ajax'); or to clear all views setView();
*/
function setView($name='',$clear=0){
	//sets or unsets the view in MVC model
	global $PAGE;
	if(is_array($name)){
    	if(!count($name) || $clear==1){
    		unset($PAGE['setView']);
		}
		if(!isset($PAGE['setView']) || !is_array($PAGE['setView'])){$PAGE['setView']=array();}
		foreach($name as $sname){
			if(!isset($PAGE['setView'][$sname])){$PAGE['setView'][$sname]=0;}
			$PAGE['setView'][$sname]+=1;
		}
		return count($name);
	}
	$name=strtolower(trim($name));
	if(!strlen($name) || $clear==1){
    	unset($PAGE['setView']);
	}
	if(!isset($PAGE['setView']) || !is_array($PAGE['setView'])){$PAGE['setView']=array();}
	if(!isset($PAGE['setView'][$name])){$PAGE['setView'][$name]=0;}
	$PAGE['setView'][$name]+=1;
	return 1;
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function setViewNames(){
	global $PAGE;
	if(!isset($PAGE['setView']) || !is_array($PAGE['setView'])){return array('');}
	return array_keys($PAGE['setView']);
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
* @author -  added by Jeremy Despain
*/
function removeViews($htm){
	global $PAGE;
	global $CONFIG;
	global $VIEWS;
	$depth=0;
	$sha='none';
	while($depth < 50 && stringContains($htm,'<view:')){
		if(sha1($htm) == $sha){break;}
		$sha=sha1($htm);
		$depth++;
		unset($removeViewMatches);
		preg_match_all('/\<view\:([a-z0-9\-\_]+?)\>(.+?)\<\/view\:\1\>/ism',$htm,$removeViewMatches,PREG_PATTERN_ORDER);
		/* this returns an array of three arrays
			0 = the whole tag
			1 = the view name
			2 = the view contents
		*/
		if($VIEWS == null ) { $VIEWS = array(); }
		$cnt=count($removeViewMatches[1]);
		//save views so they can be used by renderEach and renderView;
        for($i = 0; $i<$cnt; ++$i){
			$VIEWS[$removeViewMatches[1][$i]] = $removeViewMatches[2][$i];
        }
		for($ex=0;$ex<$cnt;$ex++){
			$replace_str='';
			$name=strtolower(trim($removeViewMatches[1][$ex]));
			if(isset($PAGE['setView'][$name]) || ($name=='default' && !isset($PAGE['setView']))){
				$replace_str=$removeViewMatches[2][$ex];
			}
			$htm=str_replace($removeViewMatches[0][$ex],$replace_str,$htm);
		}
	}
	if(stringContains($htm,'<view:')){
    	debugValue("View Tag Error detected - perhaps a malformed 'view' tag");
	}
	return $htm;
}
//---------- begin function processTranslateTags
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function processTranslateTags($htm){
	if(!stringContains($htm,'<translate>') && !stringContains($htm,'<wtranslate>')){return $htm;}
	loadExtras('translate');
	preg_match_all('/\<translate\>(.+?)\<\/translate\>/ism',$htm,$m,PREG_PATTERN_ORDER);
	/* this returns an array of three arrays
		0 = the whole tag
		1 = the tag text
	*/
	foreach($m[1] as $i=>$text){
		$replace_str=translateText($text);
		$htm=str_replace($m[0][$i],$replace_str,$htm);
	}
	if(stringContains($htm,'<translate>')){
    	debugValue("Translate Tag Error detected - perhaps a malformed 'translate' tag");
	}
	//wtranslate
	preg_match_all('/\<wtranslate\>(.+?)\<\/wtranslate\>/ism',$htm,$wm,PREG_PATTERN_ORDER);
	/* this returns an array of three arrays
		0 = the whole tag
		1 = the tag text
	*/
	foreach($wm[1] as $i=>$text){
		$replace_str=translateText($text,'',1);
		$htm=str_replace($wm[0][$i],$replace_str,$htm);
	}
	if(stringContains($htm,'<wtranslate>')){
    	debugValue("wTranslate Tag Error detected - perhaps a malformed 'wtranslate' tag");
	}
	return $htm;
}
//---------- begin function commonProcessChartjsTags
/**
* @describe function to process chartjs tags. called automatically. 
* @return htm - html for the chartjs module
* @usage
* <chartjs data-type="bar" data-bordercolor="#000">
* 	<dataset data-label="State counts per country">
* 		select count(*) as value,country as label from states group by country
* 	</dataset>
* </chartjs>
* 
*/
function commonProcessChartjsTags($htm){
	global $CONFIG;
	if(!stringContains($htm,'<chartjs')){return $htm;}
	//loadExtrasJs(array('chart','chartjs-plugin-labels','chartjs-plugin-doughnutlabel'));
	preg_match_all('/\<chartjs(.*?)\>(.*?)\<\/chartjs\>/ism',$htm,$chartjs,PREG_PATTERN_ORDER);
	/* this returns an array of three arrays
		0 = the whole chartjs tag
		1 = the chartjs attributes
		2 = the contents inside the chartjs tag - <dataset
	*/
	$colors=array(
		'rgba(255,159,64,0.4)',
        'rgba(75,192,192,0.4)',
        'rgba(255,99,132,0.4)',
        'rgba(54,162,235,0.4)',
        'rgba(153,102,255,0.4)',
        'rgba(218,165,32,0.4)',
        'rgba(233,150,122,0.4)',
        'rgba(189,183,107,0.4)',
        'rgba(154,205,50,0.4)',
        'rgba(255,228,196,0.4)',
        'rgba(244,164,96,0.4)',
        'rgba(176,196,222,0.4)',
        'rgba(188,143,143,0.4)',
        'rgba(255,228,225,0.4)',
        'rgba(201,203,207,0.4)'
	);
	$bcolors=array(
		'rgb(255,159,64)',
        'rgb(75,192,192)',
        'rgb(255,99,132)',
        'rgb(54,162,235)',
        'rgb(153,102,255)',
        'rgb(218,165,32)',
        'rgb(233,150,122)',
        'rgb(189,183,107)',
        'rgb(154,205,50)',
        'rgb(255,228,196)',
        'rgb(244,164,96)',
        'rgb(176,196,222)',
        'rgb(188,143,143)',
        'rgb(255,228,225)',
        'rgb(201,203,204)'
	);
	foreach($chartjs[0] as $i=>$chartjs_tag){
		$chartjs_attributes=parseHtmlTagAttributes($chartjs[1][$i]);
		if(isset($chartjs_attributes['data-id'])){
			$chartjs_attributes['id']=$chartjs_attributes['data-id'];
			unset($chartjs_attributes['data-id']);
		}
		if(!isset($chartjs_attributes['id'])){
			$chartjs_attributes['id']='chartjs_'.$i.strtolower(generateGUID(false,false));
		}
		$chartjs_contents=$chartjs[2][$i];
		//check for options
		$optionstr='';
		if(preg_match('/\<options\>(.*?)\<\/options\>/ism',$chartjs_contents,$m)){
			$optionstr=$m[1];
			$chartjs_contents=str_replace($m[0],'',$chartjs_contents);
		}
		//echo $optionstr;exit;
		//check for custom colors
		$colorstr='';
		if(isset($chartjs_attributes['data-colors'])){
			$arr=preg_split('/\,+/',$chartjs_attributes['data-colors']);
			$colorstr=encodeJSON($arr);
		}
		elseif(preg_match('/\<colors\>(.*?)\<\/colors\>/ism',$chartjs_contents,$m)){
			$colorstr=$m[1];
			$chartjs_contents=str_replace($m[0],'',$chartjs_contents);
		}
		$bcolorstr='';
		if(isset($chartjs_attributes['data-bcolors'])){
			$arr=preg_split('/\,+/',$chartjs_attributes['data-bcolors']);
			$bcolorstr=encodeJSON($arr);
		}
		elseif(preg_match('/\<bcolors\>(.*?)\<\/bcolors\>/ism',$chartjs_contents,$m)){
			$bcolorstr=$m[1];
			$chartjs_contents=str_replace($m[0],'',$chartjs_contents);
		}
		//look for just a query
		if(preg_match('/^(select|with)/is',trim($chartjs_contents))){
			$process='';
			if(isset($chartjs_attributes['data-process'])){
				$process=$chartjs_attributes['data-process'];
			}
			$db=$CONFIG['database'];
			if(isset($chartjs_attributes['db'])){
				$db=$chartjs_attributes['db'];
			}
			elseif(isset($chartjs_attributes['data-db'])){
				$db=$chartjs_attributes['data-db'];
			}
			//replace any [asdfsa] tags with inputs
			if(is_string($chartjs_contents)){
				foreach($_REQUEST as $k=>$v){
					if(is_array($v)){continue;}
					$chartjs_contents=str_replace("[{$k}]",$v,$chartjs_contents);
				}
			}
			
			//select date(_cdate) as label, code as dataset,count(*) as value
			$recs=dbQueryResults($db,$chartjs_contents);
			if(!is_array($recs)){$recs=[];}
			if(strlen($process)){
				$recs=call_user_func($process,$recs,$chartjs_attributes);
			}
			if(!isset($recs[0])){
				$replace_str='';
				$htm=str_replace($chartjs_tag,$replace_str,$htm);
				continue;
			}
			if(!isset($recs[0]['label']) || !isset($recs[0]['value'])){
				$replace_str='<error>Query Must return label, and value</error>';
				$replace_str.="<query>{$chartjs_contents}</query>";
				$htm=str_replace($chartjs_tag,$replace_str,$htm);
				continue;
			}
			if(isset($chartjs_attributes['data-name'])){
				$CHARTJS[$chartjs_attributes['data-name']]=$recs;
			}
			$datasets=array();
			$labels=array();
			$values=array();
			$rcolors=array();
			$rbcolors=array();
			$replace_str='';
			$replace_str.='<div data-behavior="chartjs" ';
			$replace_str .= setTagAttributes($chartjs_attributes);
			$replace_str .= '></div>'.PHP_EOL;
			$replace_str .= '<div id="'.$chartjs_attributes['id'].'_data" style="display:none">'.PHP_EOL;
			$dataset_name=isset($chartjs_attributes['data-dataset'])?$chartjs_attributes['data-dataset']:'data';
			//get a list labels and datasets
			$xrecs=array();
			foreach($recs as $rec){
				$dataset=isset($rec['dataset'])?$rec['dataset']:$dataset_name;
				if(isset($rec['label']) && strlen($rec['label']) && !in_array($rec['label'],$labels)){
					$labels[]=$rec['label'];
				}
				if(isset($rec['color'])){
					$rcolors[]=$rec['color'];
				}
				if(isset($rec['bcolor'])){
					$rbcolors[]=$rec['bcolor'];
				}
				if(!in_array($dataset,$datasets)){
					$datasets[]=$dataset;
				}
				$xrecs[$rec['label']][$dataset]=isset($rec['value'])?$rec['value']:0;
			}
			if(count($rcolors)){
				$colorstr=encodeJSON($rcolors);
			}
			if(count($rbcolors)){
				$bcolorstr=encodeJSON($rbcolors);
			}
			foreach($datasets as $dataset){
				foreach($labels as $label){
					if(isset($xrecs[$label][$dataset])){$v=$xrecs[$label][$dataset];}
					else{$v=0;}
					$values[$dataset][]=$v;
				}
			}
			$i=0;
			$values_count=count($values);
			foreach($values as $dataset=>$vals){
				$atts=$chartjs_attributes;
				$atts['data-label']=$dataset;
				if($values_count > 1){
					$atts['data-backgroundcolor']=$colors[$i];
					$atts['data-bordercolor']=$bcolors[$i];
				}
				$replace_str.='<dataset ';
				$replace_str .= setTagAttributes($atts);
				$replace_str.='>'.json_encode($vals,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).'</dataset>'.PHP_EOL;
				$i+=1;
			}
			$replace_str.='<labels>'.json_encode($labels,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).'</labels>'.PHP_EOL;
			if(isset($chartjs_attributes['data-recs'])){
				$id=$chartjs_attributes['data-recs'];
				$replace_str.='<recs id="'.$id.'">'.json_encode($recs,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).'</recs>'.PHP_EOL;
			}
			if(isset($chartjs_attributes['data-sql'])){
				$id=$chartjs_attributes['data-sql'];
				$replace_str.='<sql id="'.$id.'">'.$chartjs_contents.'</sql>'.PHP_EOL;
			}
			if(strlen($colorstr)){
				$replace_str.='<colors>'.$colorstr.'</colors>'.PHP_EOL;
			}
			if(strlen($bcolorstr)){
				$replace_str.='<bcolors>'.$bcolorstr.'</bcolors>'.PHP_EOL;
			}
			if(strlen($optionstr)){
				$replace_str.='<options>'.$optionstr.'</options>'.PHP_EOL;
			}
			$replace_str.='</div>'.PHP_EOL;
			if(isset($chartjs_attributes['data-debug']) && $chartjs_attributes['data-debug']==1){
				$replace_str="<xmp>{$replace_str}</xmp>";
			}
			//echo "<xmp>{$replace_str}</xmp>".printValue($values).printValue($recs);exit;
			$htm=str_replace($chartjs_tag,$replace_str,$htm);
			continue;
		}

		//get dataset tags inside
		preg_match_all('/\<(div|dataset|colors|bcolors|options|labels)(.*?)\>(.+?)\<\/\1\>/ism',$chartjs_contents,$chartjs_inner,PREG_PATTERN_ORDER);
		//echo printValue($chartjs_tags);exit;
		/* this returns an array of three arrays
			0 = the whole dataset tag
			1 = the dataset attributes
			2 = the contents inside the dataset tag - SQL, JSON are supported
		*/
		$replace_str='';
		//replace the chartjs tag with HTML that will render the chart specified
		foreach($chartjs_inner[0] as $t=>$chartjs_innertag){
			$innertag_name=$chartjs_inner[1][$t];
			if($innertag_name != 'div'){continue;}
			$replace_str.=$chartjs_innertag;
		}
		$replace_str.='<div data-behavior="chartjs"';
		$replace_str .= setTagAttributes($chartjs_attributes);
		$replace_str .= '></div>'.PHP_EOL;
		$replace_str .= '<div id="'.$chartjs_attributes['id'].'_data" style="display:none">'.PHP_EOL;
		$labels=array();
		$colors=array();
		$bcolors=array();
		foreach($chartjs_inner[0] as $t=>$chartjs_innertag){
			$innertag_name=$chartjs_inner[1][$t];
			$innertag_attributes=parseHtmlTagAttributes($chartjs_inner[2][$t]);
			if(!isset($innertag_attributes['id'])){
				$innertag_attributes['id']="{$chartjs_attributes['id']}_{$innertag_name}_{$t}";
			}
			$innertag_contents=$chartjs_inner[3][$t];
			//process?
			$process='';
			if(isset($innertag_attributes['data-process'])){
				$process=$innertag_attributes['data-process'];
			}
			elseif(isset($innertag_attributes['-results_eval'])){
				$process=$innertag_attributes['-results_eval'];
				unset($innertag_attributes['-results_eval']);
				$innertag_attributes['data-process']=$process;
			}
			elseif(isset($chartjs_attributes['data-process'])){
				$process=$chartjs_attributes['data-process'];
			}
			else{
				$process='';
			}
			$replace_str.='<'.$innertag_name;
			$replace_str .= setTagAttributes($innertag_attributes);
			$replace_str.='>';
			if(preg_match('/^(select|with)/is',trim($innertag_contents))){
				$db=$CONFIG['database'];
				if(isset($innertag_attributes['db'])){
					$db=$innertag_attributes['db'];
				}
				elseif(isset($innertag_attributes['data-db'])){
					$db=$innertag_attributes['data-db'];
				}
				elseif(isset($chartjs_attributes['db'])){
					$db=$chartjs_attributes['db'];
				}
				elseif(isset($chartjs_attributes['data-db'])){
					$db=$chartjs_attributes['data-db'];
				}
				//replace any [asdfsa] tags with inputs
				if(is_string($innertag_contents)){
					foreach($_REQUEST as $k=>$v){
						if(is_array($v)){continue;}
						$innertag_contents=str_replace("[{$k}]",$v,$innertag_contents);
					}
				}
				
				$recs=dbQueryResults($db,$innertag_contents);
				if(strlen($process)){
					$recs=call_user_func($process,$recs,$innertag_attributes,$chartjs_attributes);
				}
				//select date(_cdate) as label, code as dataset,count(*) as value
				if(isset($recs[0])){
					$vals=array();
					foreach($recs as $rec){
						if(isset($rec['value']) && isset($rec['label'])){
							if(!in_array($rec['label'],$labels)){
								$labels[]=$rec['label'];
							}
							$vals[]=isset($rec['value'])?$rec['value']:0;
						}
						if(isset($rec['color'])){
							$colors[]=$rec['color'];
						}
						if(isset($rec['bcolor'])){
							$bcolors[]=$rec['bcolor'];
						}
					}
					$replace_str.=json_encode($vals,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
				}
				else{
					$replace_str.='[]';
				}
			}
			else{
				if($innertag_name=='options'){
					$json=json_decode($innertag_contents,true);
					if(is_array($json)){
						if(strlen($process)){
							$json=call_user_func($process,$json,$innertag_attributes,$chartjs_attributes);
						}
						$replace_str.=json_encode($json,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT);
					}
					else{
						$replace_str.=$innertag_contents;
					}
				}
				else{
					$replace_str.=$innertag_contents;
				}
			}
			$replace_str.="</{$innertag_name}>".PHP_EOL;
		}
		if(count($labels)){
			$replace_str .= '<labels>'.json_encode(array_values($labels,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)).'</labels>'.PHP_EOL;
		}
		if(count($colors)){
			$replace_str .= '<colors>'.json_encode(array_values($colors,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)).'</colors>'.PHP_EOL;
		}
		elseif(strlen($colorstr)){
			$replace_str.='<colors>'.$colorstr.'</colors>'.PHP_EOL;
		}
		if(count($bcolors)){
			$replace_str .= '<bcolors>'.json_encode(array_values($bcolors,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)).'</bcolors>'.PHP_EOL;
		}
		elseif(strlen($bcolorstr)){
			$replace_str.='<bcolors>'.$bcolorstr.'</bcolors>'.PHP_EOL;
		}
		if(strlen($optionstr)){
			$replace_str.='<options>'.$optionstr.'</options>'.PHP_EOL;
		}
		$replace_str.='</div>'.PHP_EOL;
		$htm=str_replace($chartjs_tag,$replace_str,$htm);
	}
	if(stringContains($htm,'<chartjs')){
    	debugValue("chartjs Tag Error detected - perhaps a malformed 'chartjs' tag");
	}
	return $htm;
}
//---------- begin function commonProcessDBListRecordsTags
/**
* @describe function to process dblistrecords tags. called automatically. 
* @return htm - table
* @usage
* <dblistrecords -table="states" -tableclass="table striped">
* </dblistrecords>
*  OR
* <dblistrecords -tableclass="table striped">
* select name,code from states
* </dblistrecords>
*/
function commonProcessDBListRecordsTags($htm){
	global $CONFIG;
	global $PAGE;
	if(!stringContains($htm,'<dblistrecords')){return $htm;}
	$htm_ori=$htm;
	preg_match_all('/\<dblistrecords(.*?)\>(.*?)\<\/dblistrecords\>/ism',$htm,$dblistrecords,PREG_PATTERN_ORDER);
	/* this returns an array of three arrays
		0 = the whole datalist tag
		1 = the datalist attributes
		2 = the contents inside the datalist tag
	*/
	foreach($dblistrecords[0] as $i=>$dblistrecords_tag){
		$dblistrecords_attributes=array();
		if(preg_match_all('/([a-z\-\_0-9]+?)\=\"(.+?)\"/',$dblistrecords[1][$i],$matches,PREG_PATTERN_ORDER)){
			foreach($matches[1] as $m=>$akey){
				$dblistrecords_attributes[$akey]=$matches[2][$m];
			}
		}
		$divid=$dblistrecords_attributes['-divid'] ?? 'dblistrecords_'.$i;
		$dblistrecords_contents=$dblistrecords[2][$i];
		if(isset($dblistrecords_attributes['data-recs'])){
			$id=$dblistrecords_attributes['data-recs'];
			if(preg_match('/\<recs id\=\"'.$id.'\"\>(.+?)\<\/recs\>/ism',$htm,$m)){
				$dblistrecords_contents=$m[1];
			}
		}
		$replace_str='';
		$replace_str.='<div id="'.$divid.'">'.PHP_EOL;
		$opts=array(
			'-action'=>$dblistrecords_attributes['-action'] ?? '/php/index.php',
			'-onsubmit'=>"return pagingSubmit(this,'{$divid}');",
			'-formname'=>"dblistrecordsform_{$i}",
			'setprocessing'=>0,
		);
		if(isset($dblistrecords_attributes['data-process'])){
			$dblistrecords_attributes['-results_eval']=$dblistrecords_attributes['data-process'];
			unset($dblistrecords_attributes['data-process']);
		}
		foreach($dblistrecords_attributes as $k=>$v){
			if(!isset($opts[$k])){
				$opts[$k]=$v;
			}
		}
		if(isset($opts['-listview'])){
			$lvname=$opts['-listview'];
			if(preg_match('/\<view:'.$lvname.'\>(.*?)\<\/view:'.$lvname.'\>/ism',$htm,$vm)){
				$opts['-listview']=$vm[1];
				$htm=str_replace($vm[0],'',$htm);
			}
		}
		$opts['-pretable']='<div style="display:flex;justify-content:flex-end;align-content:flex-start;">'.PHP_EOL;
		$opts['-posttable']='</div>'.PHP_EOL;
		if(!isset($opts['-tableclass'])){
			$opts['-tableclass']='table striped bordered condensed';
		}
		if(isset($opts['db'])){
			$db=$opts['db'];
			unset($opts['db']);
		}
		elseif(isset($opts['data-db'])){
			$db=$opts['data-db'];
			unset($opts['data-db']);
		}
		else{
			$db=$CONFIG['database'];
		}
		if(strlen(trim($dblistrecords_contents))){
			if(preg_match('/^(show|select|with)/is',trim($dblistrecords_contents))){
				//replace any [asdfsa] tags with inputs
				foreach($_REQUEST as $k=>$v){
					if(!stringContains($dblistrecords_contents,"[{$k}]")){continue;}
					if(is_array($v)){
						$v=implode(',',$v);
					}
					$dblistrecords_contents=str_replace("[{$k}]",$v,$dblistrecords_contents);
				}
				$opts['-query']=trim($dblistrecords_contents);
			}
			else{
				$opts['-list']=json_decode($dblistrecords_contents,true,JSON_INVALID_UTF8_IGNORE);
			}
		}
		if(!isset($dblistrecords_attributes['id'])){
			$dblistrecords_attributes['id']='id_'.time();
		}
		$json=array(
			'db'=>$db,
			'opts'=>$opts,
			'id'=>$dblistrecords_attributes['id']
		);
		$opts['_dblistrecords']=1;
		if(isset($opts['-debug']) && $opts['-debug']==1){
			$replace_str.=printValue($opts);
		}
		else{
			$opts['_dblistrecords_params']=base64_encode(json_encode($json,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
			$replace_str.=dbListRecords($db,$opts);
		}
		$replace_str.='</div>'.PHP_EOL;
		$htm=str_replace($dblistrecords_tag,$replace_str,$htm);
	}
	if(stringContains($htm,'<dblistrecords')){
    	debugValue("dblistrecords Tag Error detected - perhaps a malformed 'dblistrecords' tag");
	}
	return $htm;
}

//---------- begin function renderView---------------------------------------
/**
* @describe Calls views starting with an underscore [_] (<view:_name>...</view:_name>) and passes in the $params value
* @param name string
*	The name of the views (with or without the preceding underscore [_])
* @param params object
*	any object (array, string, etc) you want to pass in to the view. Must be referenced in views as $params
* @param opts array
*	optional parameters as follows:
*		[-alias] - String - name of the variable you want to use inside the view instead of $params. Do Not include the $
*		[-key] - String/PHP Object - will create a variable called $key available to the view with this value
*		[-format] - pdf|pdfx|email|addeditdbform
*			- create a pdf from the rendered view
*			- sends email with the view as the message.  You must pass in to, from, subject as options also or have those fields in the $params array.
*			- renders an addEditDBForm with the view as the formfields. you must pass in -table as options or have it and -format in the params array.
* @return
*	Returns View with PHP already evaluated
* @see renderEach();
* @author Jeremy Despain, jeremy.despain@gmail.com
* @usage
* <view:items>
* 	<div class="items">
* 	<view:item>
* 		<div class="item">&lt;?=$rec['item'];?&gt;</div>
* 	</view:item>
* 	&lt;?=renderEach('item',$items,'rec');?&gt;
* 	</div>
* </view:items>
* &lt;?=renderView('items',$items,'items');?&gt;
*/
function renderView($view, $params=array(), $opts=array()){
	global $VIEWS;
	global $VIEW_PARAMS;
	global $VIEW_KEY;
	//allow you to shortcut opts and just pass in the alias
	if(isset($opts) && !is_array($opts) && strlen($opts)){
    	$opts=array('-alias'=>$opts);
	}
	if(!count($opts) && isset($params['-format'])){
        $opts=$params;
	}
	$view_code  = isset($VIEWS[$view]) ? $VIEWS[$view] : $VIEWS['_'.$view];
	if($view_code == null) {
		debugValue("renderView Error: There is no view named {$view}");
		return '';
	}
	foreach($opts as $k=>$v){
        if(!stringBeginsWith($k,'-') && !strlen($params[$k])){
			if(strtolower($opts['-format'])=='email' && in_array($k,array('to','from','subject','inline','debug','attach','maildebug'))){continue;}
			$params[$k]=$v;
		}
	}
	$view_code = removeViews($view_code);
	$VIEW_PARAMS = $params;
	$VIEW_KEY = $opts['-key'];
	$alias = isset($opts['-alias']) ? " = $".$opts['-alias'] : "";
	$view_data = '<?'."php\n".'global $VIEW_PARAMS;'.PHP_EOL;
	$view_data .= 'global $USER;'.PHP_EOL;
	$view_data .= 'global $PAGE;'.PHP_EOL;
	$view_data .= 'global $TEMPLATE;'.PHP_EOL;
	$view_data .= 'global $VIEW_KEY;'.PHP_EOL;
	$view_data .= '$key = $VIEW_KEY;'.PHP_EOL;
	$view_data .= '$params '.$alias.' = $VIEW_PARAMS;'."\n?>\n\n";

	$view_data .= $view_code;
	if(isset($params['debug'])){echo $view_data;exit;}
	$rtn= evalPHP($view_data);
	//remove leading and trailing carriage returns
	$rtn=preg_replace('/^[\r\n]+/','',$rtn);
	$rtn=preg_replace('/[\r\n]+$/','',$rtn);
	//render view as a pdf?
	$opts['-format']=isset($opts['-format'])?$opts['-format']:'';
	switch(strtolower($opts['-format'])){
		case 'pdf':
		case 'htmlpdf':
			html2pdf($rtn);
			exit;
		break;
		case 'xmlpdf':
		case 'pdfx':
			xml2PDF($rtn);
			exit;
		break;
		case 'md':
		case 'markdown';
			//convert markdown
			loadExtras('markdown');
			$rtn=markdown2html($rtn);
		break;
		case 'email':
			$fields=array('to','from','subject');
			foreach($fields as $field){
				if(!isset($opts[$field]) && isset($params[$field])){$opts[$field]=$params[$field];}
			}
			$opts['message']=trim($rtn);
			unset($opts['-alias']);
			unset($opts['-format']);
			unset($opts['-key']);
			$ok=sendMail($opts);
			if(!isNum($ok) && strlen($ok)){return $ok;}
			return;
		break;
		case 'addeditdbform':
		case 'form':
			if(!isset($opts['-table']) && isset($params['-table'])){
            	$opts=$params;
			}
			$opts['-formfields']=$rtn;
			$rtn=addEditDBForm($opts);
		break;
	}
	return $rtn;
}

//---------- begin function renderViewIf---------------------------------------
/**
* @describe renderViewIf is a Conditional renderView
* @param condition mixed - condition can be a boolean or an array of boolean=>view sets
* @param view string - view name if true - not needed if condition is an array
* @param params array
* @param options array
* @see renderView();
* @author
*	Brady Barten, brady.barten@zipsmart.com
* @usage
* <view:items>
* 	<div class="items">
* 	<view:item>
* 		<div class="item">&lt;?=$rec['item'];?&gt;</div>
* 	</view:item>
* 	&lt;?=renderEach('item',$items,'rec');?&gt;
* 	</div>
* </view:items>
* &lt;?=renderViewIf(isUser(),'items',$items,'items');?&gt;
*/
function renderViewIf($conditional,$view, $params=array(), $opts=array()){
	if(is_array($conditional) && count($conditional)){
		$opts=$params;
		$params=$view;
		foreach($conditional as $condition=>$view){
			if($condition){return renderView($view,$params,$opts);}
		}
		return '';
	}
	if($conditional){return renderView($view,$params,$opts);}
	return '';
}

//---------- begin function renderViewIfElse---------------------------------------
/**
* @describe renderViewIfElse is a Conditional renderView
* @param condition mixed - condition can be a boolean or an array of boolean=>view sets
* @param view string - view name if true
* @param viewelse string - view name if false. Not needed if condition is an array
* @param params array
* @param options array
* @see renderView();
* @author Brady Barten, brady.barten@zipsmart.com
* @usage
* <view:items>
* 	<div class="items">
* 	<view:item>
* 		<div class="item">&lt;?=$rec['item'];?&gt;</div>
* 	</view:item>
* 	&lt;?=renderEach('item',$items,'rec');?&gt;
* 	</div>
* </view:items>
* <view:no_items>
* 	<h1>No User Found</h1>
* </view:no_items>
* &lt;?=renderViewIfElse(isUser(),'items','no_items',$items,'items');?&gt;
*/
function renderViewIfElse($conditional,$view, $viewelse, $params=array(), $opts=array()){
	if(is_array($conditional) && count($conditional)){
		$opts=$params;
		$params=$viewelse;
		foreach($conditional as $condition=>$view){
			if($condition){return renderView($view,$params,$opts);}
		}
		return renderView($view,$params,$opts);;
	}
	if($conditional){return renderView($view,$params,$opts);}
	else{return renderView($viewelse,$params,$opts);}
}
//---------- begin function renderViewIfs---------------------------------------
/**
* @describe renderViewIfs is a renderViewIf with multiple bool,view pairs 
* @param array  - array of boolean,view pairs
* @param params array
* @param options array
* @usage
* 	renderViewIfs(array(array($a==$b,'vequal'),array($a<5,'vless'),array($a>5,'vfive'),array($b==5,'vbfive')),$recs,'recs');
*/
function renderViewIfs($ifs=array(), $params=array(), $opts=array()){
	foreach($ifs as $if){
		if(!is_array($if)){continue;}
		if(!count($if)==2){continue;}
		if($if[0]){
			return renderView($if[1],$params,$opts);
		}
	}
	return '';
}
//---------- begin function renderViewSwitch---------------------------------------
/**
* @describe renderViewSwitch is a renderView based on a switch statement
* @param  switch_value mixed - switch_value to compare the values against
* @param values array - array of values
* @param views array - array of view names that correspond to the array of values
* @param params array
* @param options array
* @see renderView();
* @usage
* 	renderViewSwitch('red',array('blue','red','pink'),array('_blueimg','_redimg','_pinkimg'),$recs,array('-alias'=>'recs'));
*   renderViewSwitch('red',array('blue','red','*'),array('blueimg','redimg','anythingelse'),$recs,'recs');
*/
function renderViewSwitch($str,$values,$views, $params=array(), $opts=array()){
	for($x=0;$x<count($values);$x++){
    	if($str==$values[$x] || $values[$x]=='*'){
			return renderView($views[$x],$params,$opts);
		}
	}
	return '';
}

//---------- begin function renderEach---------------------------------------
/**
* @describe Calls views starting with an underscore [_] (<view:_name>&lt;?= $params['name']' ?&gt;</view:_name>) and renders
* it for each item in the array $rows. Row data will be availbe in the $params variable, and the array index/key will appear as $key.
* @param name string
*	The name of the views (with or without the preceding underscore [_])
* @param rows array
*	Each item of the array will be passed into the view as $params. Each row must be referenced in views as $params
* @param opts mixed
*	optional parameters as follows:
*		[-alias] - String - name of the variable you want to use inside the view instead of $params. Do Not include the $
*		opts that do not start with a dash will be passed through as a key in params
*		if this param is not an array, then the value will be set as -alias
* @return
*	Returns Views for each array row with PHP already evaluated
* @see renderView();
* @author Jeremy Despain, jeremy.despain@gmail.com
* @usage
* <view:item>
* <div>&lt;?=$rec['name'];?&gt;</div>
* </view:item>
* &lt;?=renderEach('item',$items,'rec');?&gt;
*/
function renderEach($view, $rows, $opts=array()){
	//allow you to shortcut opts and just pass in the alias
	if(isset($opts) && !is_array($opts) && strlen($opts)){
    	$opts=array('-alias'=>$opts);
	}
	$rtn = '';
	if(!is_array($rows) || !count($rows)){return $rtn;}
	foreach($rows as $key=>$params){
		$opts['-key']=$key;
		foreach($opts as $k=>$v){
        	if(!stringBeginsWith($k,'-') && !strlen($params[$k])){$params[$k]=$v;}
		}
		$rtn .= renderView($view, $params, $opts);
	}
	return $rtn;
}
//---------- begin function parseUrl
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function parseUrl($url,$key=''){
	$info=parse_url($url);
	if(strlen($key)){return $info[$key];}
	return $info;
}
//---------- begin function processForeach----------------
/*
* @exclude  - this function is for internal use only and thus excluded from the manual
*	Inside a view you can have the following
*	<foreach:x $states as $state>
*		<div>{$state['code']} = {$state['name']}</div>
*	</foreach:x>
*	or
*	<foreach:x $states as $code=>$state>
*		<div>{$code} = {$state['name']}</div>
*	</foreach:x>
*	NOTE - YOU CANNOT NEST THESE or place additional php eval tags inside FOR NOW!
*/
function processForeach($htm){
	global $PAGE;
	if(!stringContains($htm,'<foreach:')){return $htm;}
	$depth=0;
	$sha='none';
	while($depth < 20 && stringContains($htm,'<foreach:')){
		if(sha1($htm) == $sha){break;}
		$sha=sha1($htm);
		$depth++;
		unset($processForeachMatches);
		preg_match_all('/\<foreach\:(.+?)\s(.+?)(?<!=)\>(.+?)\<\/foreach\:\1\>/ism',$htm,$processForeachMatches,PREG_PATTERN_ORDER);
		/* this returns an array of three arrays
			0 = the whole tag
			1 = the foreach name
			2 = the foreach variables
			3 = the inner code
		*/
		if(!isset($processForeachMatches[2][0])){return $htm;}
		$cnt=count($processForeachMatches[1]);
		for($ex=0;$ex<$cnt;$ex++){
			if(stringContains($processForeachMatches[3][$ex],'<foreach:')){
				$replace_str='';
				debugValue("Nested foreach Tag detected - you cannot nest 'foreach' tags");
			}
			elseif(stringContains($processForeachMatches[3][$ex],'<?')){
				$replace_str='';
				debugValue("PHP tag in Foreach Tag detected - you cannot put php tags in 'foreach' tags");
			}
			else{
				$lines=preg_split('/[\r\n]+/i',$processForeachMatches[3][$ex]);
				$recs=eval("return {$vars[0]};");
				$replace_str='<?php'.PHP_EOL;
				$replace_str .= '	foreach('.$processForeachMatches[2][$ex].'){'.PHP_EOL;
				foreach($lines as $line){
					if(!strlen(trim($line))){continue;}
					$line=rtrim($line);
					$line=str_replace('"','\\"',$line);
					$replace_str .= '		echo "'.$line.'\n";';
				}
				$replace_str .= '	}'.PHP_EOL;
				$replace_str .= '?>'.PHP_EOL;
			}
			$htm=str_replace($processForeachMatches[0][$ex],$replace_str,$htm);
		}
	}
	if(stringContains($htm,'<foreach:')){
    	debugValue("foreach Tag Error detected - perhaps a malformed 'foreach' tag");
	}
	return $htm;
}
//---------- begin function stringBeginsWith--------------------
/**
* @describe returns true if $str begins with $search
* @param str string
* @param search string
* @return boolean
* @usage if(stringBeginsWith('beginning','beg')){...}
*/
function stringBeginsWith($str='', $search=''){
	return (strncmp(strtolower($str), strtolower($search), strlen($search)) == 0);
	}

//---------- begin function stringEndsWith--------------------
/**
* @describe returns true if $str ends with $search
* @param str string
* @param search string
* @return boolean
* @usage if(stringEndsWith('test string','ing')){...}
*/
function stringEndsWith($string, $search){
    $pos = strrpos(strtolower($string), strtolower($search)) === strlen($string)-strlen($search);
    if($pos === false){return false;}
	return true;
	}

//---------- begin function stringContains--------------------
/**
* @describe returns true if $str contains $search (ignores case)
* @param str string
* @param search string
* @return boolean
* @usage if(stringContains('beginning','gin')){...}
*/
function stringContains($string, $search){
	if(is_array($string) || is_null($string)){return false;}
	if(!strlen($string) || !strlen($search)){return false;}
	return strpos(strtolower($string),strtolower($search)) !== false;
	}
//---------- begin function stringEquals--------------------
/**
* @describe returns true if $str equals $search (ignores case)
* @param str string
* @param search string
* @return boolean
* @usage if(stringEquals('beginning','gin')){...}
*/
function stringEquals($string, $search){
	$check=strcmp(strtolower($string),strtolower($search));
	if($check==0){return true;}
	return false;
}
//---------- begin function getCalendar--------------------
/**
* @describe returns a calendar array for specified date, monthyear, or timestamp
* @param str string - date, month year, or timestamp of the month and year to show the calendar for
* @param params array - optional
*	[-view] string month|week|day - defaults to month
*	[-holidays] boolean defaults to true. set to false to not load holidays as events.
*	[-events] array - array of event arrays.  An event array needs
*	[-event]_table string - table name of the events table to pull events from. Required Fields: startdate,name Optional: icon,user_id,private
*  	[-ical] mixed - array of iCal feeds or a single feed to add to the calendar. If the array key is not a number it will be used as the group name.
*	-ical_hours - integer - number of hours to cache the iCal feed before checking again. Defaults to 12 hours
*	-ical_icon string - default icon if none are specified for and event
*	-ical_icons array - array of icons for groups with the groupname as the key
*	)
* @return calendar array
* @usage $calendar=getCalendar('February 2015');
*/
function getCalendar($monthyear='',$params=array()){
	global $USER;
	if(!strlen($monthyear)){$monthyear=time();}
	if(!isNum($monthyear)){$monthyear=strtotime($monthyear);}
	if(!isset($params['-view'])){$params['-view']='month';}
	$params['-view']=strtolower(trim($params['-view']));
	//build current array
	$calendar=array('-view'=>$params['-view']);

	//setup current
	$calendar['current']=getdate($monthyear);
	$calendar['current']['month_short']=substr(date('M',$monthyear),0,2);
	$calendar['current']['month_med']=date('M',$monthyear);
	$calendar['current']['month_long']=date('F',$monthyear);
	$calendar['current']['month_num']=date('n',$monthyear);
	$calendar['current']['month_num2']=date('m',$monthyear);
	$calendar['current']['wnum']=getWeekNumber($monthyear);
	unset($calendar['current']['seconds']);
	unset($calendar['current']['minutes']);
	unset($calendar['current']['hours']);

	//first_week_day
	$calendar['current']['first_week_day']=getdate($calendar['current']['0']-($calendar['current']['wday']*86400));
	unset($calendar['next_day']['seconds']);
	unset($calendar['next_day']['minutes']);
	unset($calendar['next_day']['hours']);

	//last_week_day
	$calendar['current']['last_week_day']=getdate($calendar['current']['first_week_day'][0]+(6*86400));
	unset($calendar['next_day']['last_week_day']['seconds']);
	unset($calendar['next_day']['last_week_day']['minutes']);
	unset($calendar['next_day']['last_week_day']['hours']);

	$calendar['current']['days_in_this_month'] = getDaysInMonth($calendar['current'][0]);

	$calendar['next_day'] = getdate(strtotime('+1 day', $calendar['current'][0]));
	unset($calendar['next_day']['seconds']);
	unset($calendar['next_day']['minutes']);
	unset($calendar['next_day']['hours']);

	$calendar['prev_day'] = getdate(strtotime('-1 day', $calendar['current'][0]));
	unset($calendar['prev_day']['seconds']);
	unset($calendar['prev_day']['minutes']);
	unset($calendar['prev_day']['hours']);

	$calendar['next_week'] = getdate(strtotime('+1 week', $calendar['current'][0]));
	unset($calendar['next_week']['seconds']);
	unset($calendar['next_week']['minutes']);
	unset($calendar['next_week']['hours']);

	$calendar['prev_week'] = getdate(strtotime('-1 week', $calendar['current'][0]));
	unset($calendar['prev_week']['seconds']);
	unset($calendar['prev_week']['minutes']);
	unset($calendar['prev_week']['hours']);

	$calendar['this_month'] = getdate(mktime(0, 0, 0, $calendar['current']['mon'], 1, $calendar['current']['year']));
	$calendar['this_month']['days_in_this_month'] = getDaysInMonth($calendar['this_month'][0]);
	unset($calendar['this_month']['seconds']);
	unset($calendar['this_month']['minutes']);
	unset($calendar['this_month']['hours']);

	$calendar['nextnext_month'] = getdate(mktime(0, 0, 0, $calendar['current']['mon'] + 2, 1, $calendar['current']['year']));
	$calendar['nextnext_month']['days_in_this_month'] = getDaysInMonth($calendar['nextnext_month'][0]);
	unset($calendar['nextnext_month']['seconds']);
	unset($calendar['nextnext_month']['minutes']);
	unset($calendar['nextnext_month']['hours']);

	$calendar['next_month'] = getdate(mktime(0, 0, 0, $calendar['current']['mon'] + 1, 1, $calendar['current']['year']));
	$calendar['next_month']['days_in_this_month'] = getDaysInMonth($calendar['next_month'][0]);
	unset($calendar['next_month']['seconds']);
	unset($calendar['next_month']['minutes']);
	unset($calendar['next_month']['hours']);


	$calendar['prev_month'] = getdate(mktime(0, 0, 0, $calendar['current']['mon'] - 1, 1, $calendar['current']['year']));
	$calendar['prev_month']['days_in_this_month'] = getDaysInMonth($calendar['prev_month'][0]);
	unset($calendar['prev_month']['seconds']);
	unset($calendar['prev_month']['minutes']);
	unset($calendar['prev_month']['hours']);

	//Find out when this month starts and ends.
	$calendar['current']['first_month_day'] = $calendar['this_month']['wday'];

	$calendar['daynames']=array(
    	'short'	=> array('S','M','T','W','T','F','S'),
    	'med'	=> array('Sun','Mon','Tue','Wed','Thu','Fri','Sat'),
    	'long'	=> array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
    	'all'	=> array(
    		array('short'=>'Su','med'=>'Sun','long'=>'Sunday'),
    		array('short'=>'Mo','med'=>'Mon','long'=>'Monday'),
    		array('short'=>'Tu','med'=>'Tue','long'=>'Tuesday'),
    		array('short'=>'We','med'=>'Wed','long'=>'Wednesday'),
    		array('short'=>'Th','med'=>'Thu','long'=>'Thursday'),
    		array('short'=>'Fr','med'=>'Fri','long'=>'Friday'),
    		array('short'=>'Sa','med'=>'Sat','long'=>'Saturday'),
    	)
	);
	$calendar['groupnames']=array();
	//Fill the first week of the month with the appropriate days from previous month
	$minus=-1;
	for($week_day = 0; $week_day < $calendar['current']['first_month_day']; $week_day++){$minus++;}
	$d=$calendar['prev_month']['days_in_this_month']-$minus;
	$m=$calendar['prev_month']['month'];
	$y=$calendar['prev_month']['year'];

	for($week_day = 0; $week_day < $calendar['current']['first_month_day']; $week_day++){
		$cdate=getdate(strtotime("{$m} {$d} {$y}"));
		$cdate['wnum']=getWeekNumber($cdate[0]);
		$cdate['day']			= $cdate['mday'];
		$cdate['name_char']		= $calendar['daynames']['short'][$cdate['wday']];
		$cdate['name_abbr']		= $calendar['daynames']['med'][$cdate['wday']];
		$cdate['name']			= $calendar['daynames']['long'][$cdate['wday']];
		$cdate['date']			= "{$cdate['year']}-{$cdate['mon']}-{$cdate['mday']}";
		$cdate['month']			= $calendar['current']['month'];
		$cdate['previous_month']=1;
		$cdate['events']=array();
		$calendar['weeks'][1][]=$cdate;
		$d++;
	}
	//holiday map
	if(!isset($params['-holidays']) || $params['-holidays']){
		$holidaymap=getHolidays(array(
			'year'	=>$calendar['current']['year'],
			'month'	=>$calendar['current']['month'],
			'-index'=>'day'
		));
	}
	//events
	if(!isset($params['-events']) || !is_array($params['-events'])){
        $params['-events']=array();
	}
	else{
		$recs=$params['-events'];
		$params['-events']=array();
    	foreach($recs as $rec){
			if(isset($rec['startdate'])){
        		$edate=getdate(strtotime($rec['startdate']));
        		$edate['wnum']=getWeekNumber($edate[0]);
        		//skip events that do not apply to this view
        		if($calendar['current']['year'] != $edate['year']){continue;}
        		if($calendar['current']['mon'] != $edate['mon']){continue;}
        		if($params['-view']=='week' && $calendar['current']['wnum'] != $edate['wnum']){continue;}
        		elseif($params['-view']=='day' && $calendar['current']['mday'] != $edate['mday']){continue;}
        		$rec['_id']=isset($rec['_id'])?$rec['_id']:getGuid();
        		$rec['month']=$edate['month'];
				$rec['year']=$edate['year'];
        		$params['-events']['mday'][$edate['mday']][]=$rec;
			}
			else if(isset($rec['mday'])){
				$mdays=preg_split('/[\,\:\;]+/',$rec['mday']);
				foreach($mdays as $mday){
					$rec['mday']=$mday;
					if($params['-view']=='day' && $calendar['current']['mday'] != $rec['mday']){continue;}
					$rec['_id']=getGuid();
					$params['-events']['mday'][$mday][]=$rec;
				}
			}
			else if(isset($rec['wday'])){
				$wdays=preg_split('/[\,\:\;]+/',$rec['wday']);
				foreach($wdays as $wday){
					$rec['wday']=$wday;
					$rec['_id']=getGuid();
					$params['-events']['wday'][$wday][]=$rec;
				}
			}
			else if(isset($rec['day'])){
				$days=preg_split('/[\,\:\;]+/',$rec['day']);
				foreach($days as $day){
					$day=ucfirst(strtolower($day));
					$rec['day']=$day;
					$rec['_id']=getGuid();
					$params['-events']['day'][$day][]=$rec;
				}
			}
        	if(isset($rec['group']) && !isset($calendar['groupnames'][$rec['group']])){
				$calendar['groupnames'][$rec['group']]=array('icon'=>$rec['icon'],'name'=>$rec['group']);
				}
		}
	}
	//-event_table
	if(isset($params['-event_table']) && isDBTable($params['-event_table'])){
		$recopts=array(
			'-table'	=> $params['-event_table'],
			'-where'	=> "MONTH(startdate)='{$calendar['current']['mon']}' and YEAR(startdate)='{$calendar['current']['year']}'"
		);
		if($params['-view']=='week'){
        	$recopts['-where'].=" AND WEEK(startdate)={$calendar['current']['wnum']}";
		}
		elseif($params['-view']=='day'){
        	$recopts['-where'].=" AND DAYOFMONTH(startdate)={$calendar['current']['mday']}";
		}
		if(isset($params['-event_table_filters']) && is_array($params['-event_table_filters'])){
			foreach($params['-event_table_filters'] as $k=>$v){
				$recopts[$k]=$v;
			}
		}
    	$recs=getDBRecords($recopts);

		if(is_array($recs)){
			foreach($recs as $rec){
				$edate=getdate(strtotime($rec['startdate']));
				if(!isset($rec['group'])){$rec['group']=$params['-event_table'];}
				if(isset($rec['group']) && !isset($calendar['groupnames'][$rec['group']])){
					$calendar['groupnames'][$rec['group']]=array('icon'=>$rec['icon'],'name'=>$rec['group']);
				}
				$rec['month']=$edate['month'];
				$rec['year']=$edate['year'];
				$params['-events']['mday'][$edate['mday']][]=$rec;
            }
		}
	}
	//ical
	if(isset($params['-ical'])){
		$cache=isset($params['-ical_hours'])?$params['-ical_hours']:12;
		$icon=isset($params['-ical_icon'])?$params['-ical_icon']:'icon-slideshow w_dblue w_big';
		if(!is_array($params['-ical'])){$params['-ical']=array($params['-ical']);}
		foreach($params['-ical'] as $icalindex => $ical){
			if(!isNum($icalindex)){$ical_group=$icalindex;}
			else{
				$nameparts=preg_split('/\/+/',$ical);
				foreach($nameparts as $i=>$part){
					if(!strlen(trim($part))){unset($nameparts[$i]);}
					if(preg_match('/^(http|www)/i',$part)){unset($nameparts[$i]);}
					if(preg_match('/\.ics$/i',$part)){unset($nameparts[$i]);}
					if(preg_match('/^(events|public|ical|calendar)$/i',$part)){unset($nameparts[$i]);}
				}
				$ical_group=implode(' ',$nameparts);
			}
			//icon override for this group
			if(isset($params['-ical_icons'][$icon_group])){$icon=$params['-ical_icons'][$icon_group];}
			//load group and icon into groupnames
			if(isset($ical_group) && !isset($calendar['groupnames'][$ical_group])){
				$calendar['groupnames'][$ical_group]=array('icon'=>$icon,'name'=>$ical_group);
			}

			//$ical_events=icalEvents($ical);
			//use getStoredValue so we are not retrieving the same data every time - cache it for 3 hours
			$ical_events=getStoredValue("return icalEvents('".$ical."');",0,$cache);
        	foreach($ical_events as $rec){
				//skip events not in this month
				$dstart=getdate(strtotime($rec['date_start']));
				$dstart['wnum']=getWeekNumber($dstart[0]);
				$dstop=getdate(strtotime($rec['date_stop']));
				$dstop['wnum']=getWeekNumber($dstop[0]);
				//skip events not revelent to this view
				if($calendar['current']['year'] != $dstart['year'] && $calendar['current']['year'] != $dstop['year']){
					continue;
				}
				if($calendar['current']['mon'] != $dstart['mon'] && $calendar['current']['mon'] != $dstop['mon']){
					continue;
				}
				if($params['-view']=='week'){
                	if($calendar['current']['wnum'] != $dstart['wnum'] && $calendar['current']['wnum'] != $dstop['wnum']){
						continue;
					}
				}
				elseif($params['-view']=='day'){
					if($calendar['current']['mday'] != $dstart['mday'] && $calendar['current']['mday'] != $dstop['mday']){
						continue;
					}
				}
            	//span multiple days if dates are different
            	//startdate,name Optional: icon,user_id,private
            	$event=array(
					'name'	=> $rec['title'],
					'icon'	=> isset($rec['icon'])?$rec['icon']:$icon,
					'group'	=> $ical_group,
					'_id'	=> $rec['uid']
				);
				if(isset($rec['geo'])){$event['geo']=$rec['geo'];}
				if(isset($rec['location'])){$event['location']=$rec['location'];}
				if(isset($rec['description'])){$event['details']=$rec['description'];}
				$event['eventtimestamp']=strtotime("{$rec['date_start']} {$rec['time_start']}:00");
				$event['timestring']=date('g:i a',strtotime($rec['time_start']));
				$event['time_start']=date('g:i a',strtotime($rec['time_start']));
				if($rec['time_start'] != $rec['time_stop']){
					$event['timestring']='From '.$event['timestring'].' to '.date('g:i a',strtotime($rec['time_stop']));
					$event['time_stop']=date('g:i a',strtotime($rec['time_stop']));
				}
				$event['name']=$event['eventtime'].' '.$rec['title'];
            	if($rec['date_stop'] != $rec['date_start']){
                	$startTime = strtotime("{$rec['date_start']} 12:00");
					$endTime = strtotime("{$rec['date_stop']} 12:00");
					// Loop between timestamps, 24 hours at a time
					for ( $i = $startTime; $i <= $endTime; $i = $i + 86400 ) {
				  		$event['startdate']=date( 'Y-m-d', $i );
				  		$event['eventtimestamp']=strtotime("{$event['startdate']} {$rec['time_start']}:00");
				  		$edate=getdate($i);
						$params['-events']['mday'][$edate['mday']][]=$event;
					}
				}
            	else{
                	$event['startdate']=$rec['date_start'];
                	$edate=getdate(strtotime($rec['date_start']));
                	$mdate=$edate['mday'];
					$params['-events']['mday'][$mdate][]=$event;
				}
			}
		}
	}
	$week_day = $calendar['current']['first_month_day'];
	$cnt=0;
	$row=$week_day==0?0:1;
	$shas=array();
	for($day_counter = 1; $day_counter <= $calendar['current']['days_in_this_month']; $day_counter++){
		$week_day %= 7;
		if($week_day == 0){
			$row+=1;
			$cnt=0;
			}
		$cnt++;
		$current=array(
			'day'			=> $day_counter,
			'name_char'		=> $calendar['daynames']['short'][$week_day],
			'name_abbr'		=> $calendar['daynames']['med'][$week_day],
			'name'			=> $calendar['daynames']['long'][$week_day],
			'date'			=> "{$calendar['current']['year']}-{$calendar['current']['mon']}-{$day_counter}",
			'month'			=> $calendar['current']['month'],
			'events'		=> array()
		);
		$m=strlen($calendar['current']['mon'])==2?$calendar['current']['mon']:'0'.$calendar['current']['mon'];
		$d=strlen($day_counter)==2?$day_counter:'0'.$day_counter;
		$current['date']="{$calendar['current']['year']}-{$m}-{$d}";
		$edate=getdate(strtotime($current['date']));
		unset($edate['seconds']);
		unset($edate['minutes']);
		unset($edate['hours']);
		foreach($edate as $k=>$v){$current[$k]=$v;}
		$current['day_short']=$calendar['daynames']['short'][$current['wday']];
		$current['day_med']=$calendar['daynames']['med'][$current['wday']];
		$current['day_long']=$calendar['daynames']['long'][$current['wday']];
		$current['wnum']=getWeekNumber($current[0]);
		//skip if not relevant to this view
		//if($params['-view']=='week' && $calendar['current']['wnum'] != $current['wnum']){continue;}
		//elseif($params['-view']=='day' && $calendar['current']['mday'] != $current['mday']){continue;}

		//add holidays if not specified and not set to false
		if((!isset($params['-holidays']) || $params['-holidays']) && isset($holidaymap[$day_counter])){
			$holidaymap[$day_counter]['_id']=$holidaymap[$day_counter]['code'];
			$event=$holidaymap[$day_counter];
			$hdate=getdate($event['timestamp']);
			foreach($hdate as $k=>$v){$event[$k]=$v;}
			$event['wnum']=getWeekNumber($event[0]);
			$valid=1;
			//skip events not revelent to this view
			if($calendar['current']['year'] != $event['year']){$valid=0;}
			if($calendar['current']['mon'] != $event['mon']){$valid=0;}
			if($params['-view']=='week' && $calendar['current']['wnum'] != $event['wnum']){$valid=0;}
			elseif($params['-view']=='day' && $calendar['current']['mday'] != $event['mday']){$valid=0;}
			//skip this event if we have already listed it - user has overlapping events from two feeds
			$sha=sha1(json_encode($event,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
			if(isset($shas[$sha])){$valid=0;}
			else{$shas[$sha]=1;}
			if($valid){
				$event['group']='Holidays';
				if(isset($event['group']) && !isset($calendar['groupnames'][$event['group']])){
					$calendar['groupnames'][$event['group']]=array('icon'=>$event['icon'],'name'=>$event['group']);
				}
				$current['events'][]=$event;
				$calendar['events'][]=$event;
			}
		}
		//add other events
		$checks=array(
			'mday'	=> array($day_counter),
			'wday'	=> array($current['wday']),
			'day'	=> array($current['day_short'],$current['day_med'],$current['day_long'])
		);
		foreach($checks as $key=>$vals){
			foreach($vals as $val){
				if(isset($params['-events'][$key][$val]) && is_array($params['-events'][$key][$val])){
					//sort events by
					$params['-events'][$key][$val]=sortArrayByKey($params['-events'][$key][$val],'eventtimestamp',SORT_ASC);
		        	foreach($params['-events'][$key][$val] as $event){
						//skip events set for a specific user if the user is not the current user
						if(isset($event['private']) && isNum($event['private']) && $event['private'] ==1 && (!isset($USER['_id']) || $event['_cuser'] != $USER['_id'])){
		                	continue;
						}
						elseif(isset($event['user_id']) && isNum($event['user_id']) && $event['user_id'] !=0 && (!isset($USER['_id']) || $event['user_id'] != $USER['_id'])){
		                	continue;
						}
						//skip this event if we have already listed it - user has overlapping events from two feeds
						$sha=sha1(json_encode($event,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
						if(isset($shas[$sha])){continue;}
						$shas[$sha]=1;
						$current['events'][]=$event;
						$calendar['events'][]=$event;

					}
				}
			}
		}
		$today=getdate();
		//is it today
		if($today['mday']==$day_counter && $today['year']==$current['year'] && $today['mon']==$current['mon']){
        	$current['today']=1;
		}
		$calendar['weeks'][$row][]=$current;
        $week_day++;
    }
    //add any missing table cells on the end
    $d=1;
	$m=$calendar['next_month']['month'];
	$y=$calendar['next_month']['year'];
	for($x=$cnt;$x<7;$x++){
		$cdate=getdate(strtotime("{$m} {$d} {$y}"));
		$cdate['wnum']=getWeekNumber($cdate[0]);
		$cdate['day']			= $cdate['mday'];
		$cdate['name_char']		= $calendar['daynames']['short'][$cdate['wday']];
		$cdate['name_abbr']		= $calendar['daynames']['med'][$cdate['wday']];
		$cdate['name']			= $calendar['daynames']['long'][$cdate['wday']];
		$cdate['date']			= "{$cdate['year']}-{$cdate['mon']}-{$cdate['mday']}";
		$cdate['month']			= $calendar['current']['month'];
		$cdate['next_month']=1;
		$cdate['events']=array();
		$calendar['weeks'][$row][]=$cdate;
		$d++;
	}

    ksort($calendar['groupnames']);
    unset($calendar['this_month']);
    unset($calendar[0]);
    return $calendar;
	}
//---------- begin function calendar
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function calendar($params=array()){
	if(isset($params['date'])){
		if(is_array($params['date'])){$cdate=$params['date'];}
		else{$cdate=getdate(strtotime($params['date']));}
    	}
	else{$cdate=getdate();}
	$current=getdate();
	$day = $cdate["mday"];
	$month = $cdate["mon"];
	$month_name = $cdate["month"];
	$year = $cdate["year"];
	$this_month = getdate(mktime(0, 0, 0, $month, 1, $year));
	$next_month = getdate(mktime(0, 0, 0, $month + 1, 1, $year));
	//Find out when this month starts and ends.
	$first_month_day = $this_month["wday"];
	$days_in_this_month = round(($next_month[0] - $this_month[0]) / (60 * 60 * 24));
	//draw the calendar
	$rtn=''.PHP_EOL;
	$rtn .= '<table class="w_calendar">'.PHP_EOL;
    //Show Month Name and Year
	$rtn .= '	<tr class="w_calendar_month"><td colspan="7" align="center">'. "{$month_name} {$year}" . '</td></tr>'.PHP_EOL;
    //Show Day names
    $names=array(
    	'short'	=> array('S','M','T','W','T','F','S'),
    	'med'	=> array('Sun','Mon','Tue','Wed','Thu','Fri','Sat'),
    	'long'	=> array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
		);
	$daysformat=isset($params['daysformat'])?$params['daysformat']:'short';
	$rtn .= '	<tr class="w_calendar_names" align="center">'.PHP_EOL;
	foreach ($names[$daysformat] as $name){
		$rtn .= '		<td>'.$name.'</td>'.PHP_EOL;
    	}
	$rtn .= '	</tr>'.PHP_EOL;
	$rtn .= '	<tr class="w_calendar_days" valign="top">'.PHP_EOL;
	//Fill the first week of the month with the appropriate number of blanks.
	for($week_day = 0; $week_day < $first_month_day; $week_day++){
		$rtn .= '		<td></td>'.PHP_EOL;
		}
	$week_day = $first_month_day;
	$cnt=0;
	for($day_counter = 1; $day_counter <= $days_in_this_month; $day_counter++){
		$week_day %= 7;
		if($week_day == 0){
			$rtn .= '	</tr>'.PHP_EOL;
			$rtn .= '	<tr class="w_calendar_days" valign="top">'.PHP_EOL;
			$cnt=0;
			}
		$cnt++;
		//set a today class if today
		$cd=$day_counter;
		$cm=$month;
		if(strlen($cm)==1){$cm="0{$cm}";}
		if(strlen($cd)==1){$cd="0{$cd}";}
		$cid=$year.'-'.$cm.'-'.$cd;
		$rtn .= '		<td id="'.$cid.'"';
		$mm=$month;
		if(strlen($mm)==1){$mm="0{$mm}";}
		$dd=$day_counter;
		if(strlen($dd)==1){$dd="0{$dd}";}

		if(isset($params['events']) && is_array($params['events']) && count($params['events'])){
			$day_events=array();
			foreach($params['events'] as $key=>$val){
				foreach($cdate as $ckey=>$cval){
					$val=str_replace("%{$ckey}%",$cdate[$ckey],$val);
					}
				$val=str_replace('%y%',$year,$val);
				$val=str_replace('%m%',$month,$val);
				$val=str_replace('%d%',$day_counter,$val);
				$val=str_replace('%mm%',$mm,$val);
				$val=str_replace('%dd%',$dd,$val);
				$val=str_replace('%id%',$cid,$val);
				$day_events[$key]=$val;
            	}
            //process days
            if(isset($params['days'][$day_counter]) && is_array($params['days'][$day_counter])){
				foreach($params['days'][$day_counter] as $key=>$val){
					foreach($cdate as $ckey=>$cval){
						$val=str_replace("%{$ckey}%",$cdate[$ckey],$val);
						}
					$val=str_replace('%y%',$year,$val);
					$val=str_replace('%m%',$month,$val);
					$val=str_replace('%d%',$day_counter,$val);
					$val=str_replace('%mm%',$mm,$val);
					$val=str_replace('%dd%',$dd,$val);
					$val=str_replace('%id%',$cid,$val);
					$day_events[$key]=$val;
            		}
				}
			//process class
			if(isset($params['class']) && is_array($params['class'])){
				$pclass=$params['class'];
				$class=array_shift($pclass);
				foreach($pclass as $pday){
					if($pday == $day_counter){$day_events['class']=$class;}
                	}
				}
			//process class
			if(isset($params['style']) && is_array($params['style'])){
				$pclass=$params['style'];
				$class=array_shift($pclass);
				foreach($pclass as $pday){
					if($pday == $day_counter){$day_events['style']=$class;}
                	}
				}
			$rtn .= setHtmlTagAttributes('td',$day_events);
			}
		if($month==$current['mon'] && $day == $day_counter){$rtn .= ' class="w_calendar_today"';}
		$rtn .= '>'.PHP_EOL;
		$rtn .= '			<div align="right">'.$day_counter.'</div>'.PHP_EOL;
		$rtn .= '		</td>'.PHP_EOL;
        $week_day++;
        }
    //add any missing table cells on the end
    for($x=$cnt;$x<7;$x++){
		$rtn .= '		<td></td>'.PHP_EOL;
    	}
	$rtn .= '	</tr>'.PHP_EOL;
	$rtn .= '</table>'.PHP_EOL;
	return $rtn;
	}
//---------- begin function setHtmlTagAttributes
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function setHtmlTagAttributes($tag='',$opts=array()){
	$rtn='';
	//set common attributes
	$atts=array(
		'id','class','style','onclick','onmouseover','onmouseout','_behavior',
		'data-behavior','pattern','data-pattern-msg','data-displayname'
	);
	//add tag specific attributes
	switch(strtolower($tag)){
		case 'table':array_push($atts,'align','bgcolor','frame','rules','summary','width');break;
		case 'a':array_push($atts,'href');break;
    	}
	foreach($atts as $att){
		if(isset($opts[$att]) && strlen($opts[$att])){$rtn .= ' ' . $att . '="'.$opts[$att].'"';}
    	}
	return $rtn;
	}
//---------- begin function checkPHPSyntax--------------------
/**
* @describe checks the syntax of a PHP code segment
* @param code string
* @return string
* @usage $errs=checkPHPSyntax($code);
*/
function checkPHPSyntax($code=''){
    return @eval('return true;' . $code);
}
//---------- begin function createExpandDiv--------------------
/**
* @describe creates an html expandable tree node
* @param title string - the title of the clickable link
* @param content string - the content to show when the node is open
* @param color string - the color of the link - defaults to #002E5B
* @param open boolean - if true is displayed in open mode
* @param ajaxurl string - url to call via ajax for content
* @param ajaxopts string - ajax options to pass if ajaxurl is set
* @return string - html to display for thies node
* @usage return createExpandDiv($title,$expand,'#0d0d7d',1);
*/
function createExpandDiv($title='',$content='',$color='',$open=false,$ajaxurl='',$ajaxopts=''){
	$id=encodeCRC($content.$title);
	if(!strlen($color)){$color = '#002E5B';}
	$iconId='expand_icon_' . $id;
	$linkId='expand_link_' . $id;
	$sectionId='expand_section_' . $id;
	$icon='<span class="icon-plus" title="expand" style="border:1px solid #999;margin-right:5px;"></span>';
	$display='none';
	if($open){
		$icon='<span class="icon-minus" style="border:1px solid #999;margin-right:5px;"></span>';
		$display='block';
    }
	//begin div
	$html='<div style="margin-bottom:3px;" class="w_align_left">' . "\n";
	if(strlen($ajaxurl)){
    	//build the +/- link
		$html .= "\t<div id=\"{$iconId}\" onclick=\"ajaxExpand('{$id}','{$ajaxurl}','{$ajaxopts}')\" style=\"float:left;font-size:13pt;color:'.$color.';cursor:pointer;\">{$icon}</div>\n";
		//add title
		$html .= '<a href="#" id="'.$linkId.'" class="w_link" onclick="return ajaxExpand(\''.$id.'\',\''.$ajaxurl.'\',\''.$ajaxopts.'\');" style="color:'.$color.';">'.$title.'</a>' . "\n";
	}
	else{
		//build the +/- link
		$html .= "\t<div id=\"{$iconId}\" onclick=\"expand('{$id}')\" style=\"float:left;font-size:13pt;color:{$color};cursor:pointer;\">{$icon}</div>\n";
		//add title
		$html .= '<a href="#" id="'.$linkId.'" class="w_link" onclick="return expand(\''.$id.'\');" style="color:'.$color.';">'.$title.'</a>' . "\n";
	}
	//add the section message
    $html .= "\t<div id=\"{$sectionId}\" style=\"display:{$display};color:{$color};margin-left:15px;font-size:11pt;\">\n{$content}\n\t</div>\n";
	//ending div
	$html .= '<div style="height:0;clear:both;"> </div>'.PHP_EOL;
    $html .= "</div>\n";
    return $html;
}
//---------- begin function date2Mysql
/**
* @describe convert a date string into mysql formatted date
* @param str string
*	date string to format
* @return
*	date string formatted for mysql.  Date string can also be today, now, thisweek
* @usage date2Mysql('today');
*/
function date2Mysql($str=''){
	if(preg_match('/^([0-9]{2,2})-([0-9]{2,2})-([0-9]{4,4})$/s',$str,$dmatch)){
		$str=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
		}
	elseif(preg_match('/^(NOW|TODAY)$/is',$str)){
		$str=date("Y-m-d");
    	}
    elseif(preg_match('/^(ThisWeek)$/is',$str)){
		$dtime=time()-round((86400*7),2);
		$str=date("Y-m-d",$dtime);
    	}
    else{$str=date("Y-m-d",strtotime($str));}
	return $str;
	}
//---------- begin function decodeBase64
/**
* @describe decodes a base64 encodes string - same as base64_decode
* @param str string - base64 string to decode
* @return str string - decodes a base64 encodes string - same as base64_decode
* @usage $dec=decodeBase64($encoded_string);
*/
function decodeBase64($str=''){
	return base64_decode($str);
}
//---------- begin function decodeJson
/**
* @describe wrapper for json_decode, if it failes it converts the string to utf-8 and tries again
* @param str string - JSON string to decode
* @param arr boolean - return an array by default
* @return arr array
* @usage $js=decodeJson($str);
*/
function decodeJson($str,$arr=true){
	if(!is_string($str)){$str=encodeJson($str);}
	//remove control characters that may interfere
	$json = preg_replace('/[[:cntrl:]]/', '', trim($str));
	$decoded=json_decode($json,$arr,512,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
	if(!is_array($decoded)){
		//JSON_INVALID_UTF8_SUBSTITUTE flag was added in php 7.2
		$json = mb_convert_encoding($json, 'UTF-8', 'UTF-8');
		$decoded=json_decode($json,$arr,512,JSON_UNESCAPED_UNICODE);
	}
	if(!is_array($decoded)){
		$json=stripslashes($json);
		$json=preg_replace('/^\"/','',$json);
		$json=preg_replace('/\"$/','',$json);
		$decoded=json_decode($json,$arr,512,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
	}
	if(!is_array($decoded)){
		$decoded=json_last_error_msg();
		if(!is_array($decoded) && $decoded=='No error'){
			$decoded=null;
		}
	}
	return $decoded;
}
//---------- begin function decodeURL
/**
* @describe - wrapper for urldecode function
* @usage
* $url=decodeURL($str);
*/
function decodeURL($str=''){
	return urldecode($str);
	}
//---------- begin function deleteDirectory
/**
* @describe recursively deletes directory and all directories beneath it
* @param dir string - directory to delete
* @return boolean
* @usage $ok=deleteDirectory($dir);
*/
function deleteDirectory($dir=''){
	if(!file_exists($dir)){return true;}
	if(!is_dir($dir) || is_link($dir)){return unlink($dir);}
	foreach (scandir($dir) as $item) {
		if($item == '.' || $item == '..'){continue;}
		if(!deleteDirectory($dir . "/" . $item)) {
        	chmod($dir . "/" . $item, 0777);
            if(!deleteDirectory($dir . "/" . $item)){return false;}
            };
        }
    return rmdir($dir);
    }
//---------- begin function cleanupDirectory
/**
* @describe removes files from directory older than x days old. x defaults to 5 days
* @param dir string
*	absolute path of directory to cleanup
* @param num integer
*	units old - any file older than this is removed
* @param unit string - mon,day,hour,min
*
* @return boolean
*	returns true upon success
* @usage $ok=cleanupDirectory($dir[,3]);
*/
function cleanupDirectory($dir='',$num=5,$unit='days',$ext=''){
	$cnt=0;
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file[0] == '.' || is_dir($dir.'/'.$file)) {continue;}
			if(strlen($ext) && !stringEndsWith($file,".{$ext}")){continue;}
			$mtime=filemtime($dir.'/'.$file);
			$ttime=time();
			switch(strtolower($unit)){
				case 'yr':
				case 'yrs':
				case 'year':
				case 'years':
					$ctime=(integer)($num *31536000);
					break;
				case 'mon':
				case 'month':
				case 'months':
					$ctime=(integer)($num *2629743);
					break;
				case 'day':
				case 'days':
					$ctime=(integer)($num *86400);
					break;
				case 'hrs':
				case 'hour':
				case 'hours':
					$ctime=(integer)($num *3600);
					break;
				case 'min':
				case 'minute':
				case 'minutes':
					$ctime=(integer)($num *60);
					break;
				default:
					$ctime=$num;
					break;
			}
			$dtime=$ttime - $mtime;
		    if ($dtime > $ctime) {
		    	unlink($dir.'/'.$file);
		    	$cnt++;
		    }
		}
	    closedir($handle);
	}
	return $cnt;
}
//---------- begin function diffText
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function diffText($s,$m,$title='',$more='',$height=600){
	if(!is_array($m) || !is_array($s)){return 'No Array';}
	if(count($m)==0 && count($s)==0){return 'No Array count';}
	$linecnt=count($m);
	if(count($s) > $linecnt){$linecnt=count($s);}
	$sdiff=simpleDiff($m,$s);
	//return printValue($sdiff);
	$sha=sha1(json_encode(array($m,$s),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
	$header= '';
	$result='';
	//$result .= "<div>Diffs:" . implode(',',$diffs)."</div>\n";
	if($linecnt > 10){
		$result .= '<div id="diff" style="width:850px;position:relative;height:'.$height.'px;overflow-y:scroll;font-size:9pt;">'.PHP_EOL;
		}
	else{
		$result .= '<div id="diff" style="width:850px;position:relative;overflow-y:scroll;font-size:9pt;">'.PHP_EOL;
    	}

	$result .= '<table width="100%">'.PHP_EOL;
	//$result .= buildTableTH(array('','Stage','Live','M','S'));
	$anchor_started=0;
	$anchors=array();
	$diffcount=array();
	$r=0;
	foreach($sdiff as $diff){
		if(is_array($diff) && !count($diff['d']) && !count($diff['i'])){
			//$result .= "No Changes Found";
			continue;
			}
		if(!is_array($diff)){
			$result .= '	<tr>'.PHP_EOL;
        	$result .= '		<td valign="top"></td>'.PHP_EOL;
        	$content=preg_replace('/^\t/','[tab]',$diff);
        	//$content=encodeHtml($content);
        	$content='<xmp style="margin:1px 0px 1px 0px;">'.$content.'</xmp>';
        	$content=str_replace('[tab]','          ',$content);
        	$result .= '		<td><div class="w_diff">'.$content.'</div></td>'.PHP_EOL;
        	$result .= '	</tr>'.PHP_EOL;
		}
		else{
			$current_anchor='';
			if(count($diff['d'])){
				$r++;
				$diffcount['d']+=count($diff['d']);
				$aname=$sha.'_'.$r;
                $current_anchor='		<td valign="top"><a style="padding:0 4px 0 4px;" class="w_link w_dblue w_small w_block w_bold" href="#'.$aname.'">'.$r.'</a></td>';
				$result .= '		<td valign="top"><a name="'.$aname.'">-</a></td>'.PHP_EOL;
				$contentlines=array();
				foreach($diff['d'] as $line){
					$line=preg_replace('/^\t/','[[tab]]',$line);
					//$line=encodeHtml($line);
					$line='<div><xmp style="margin:1px 0px 1px 0px;">'.$line.'</xmp></div>';
					$line=str_replace('[[tab]]','          ',$line);
					$contentlines[]=$line;
				}
				$content=implode(PHP_EOL,$contentlines);
	        	$result .= '		<td><div class="w_del" title="Deleted">'.$content.'</div></td>'.PHP_EOL;
	        	$result .= '	</tr>'.PHP_EOL;
			}
			if(count($diff['i'])){
				$diffcount['i']+=count($diff['i']);
				if(!strlen($current_anchor)){
					$r++;
					$aname=$sha.'_'.$r;
                	$current_anchor='		<td valign="top"><a style="padding:0 4px 0 4px;" class="w_link w_dblue w_small w_block w_bold" href="#'.$aname.'">'.$r.'</a></td>';
				}
				$result .= '		<td valign="top"><a name="'.$aname.'">+</a></td>'.PHP_EOL;
				$contentlines=array();
				foreach($diff['i'] as $line){
					$line=preg_replace('/^\t/','[[tab]]',$line);
					//$line=encodeHtml($line);
					$line='<div><xmp style="margin:1px 0px 1px 0px;">'.$line.'</xmp></div>';
					$line=str_replace('[[tab]]','          ',$line);
					$contentlines[]=$line;
				}
				$content=implode(PHP_EOL,$contentlines);
	        	$result .= '		<td><div class="w_ins"  title="Inserted">'.$content.'</div></td>'.PHP_EOL;
	        	$result .= '	</tr>'.PHP_EOL;
			}
			if(strlen($current_anchor)){
            	$anchors[]=$current_anchor;
			}
		}
	}
	if(!count($anchors)){return '';}
	$result .= buildTableEnd();
	//$result .= printValue($diff);
	$result .= '</div>'.PHP_EOL;
	$header .= $more;
	if(count($anchors)){
    	$header .= '<div style="margin-bottom:3px;"><table class="w_table">'.PHP_EOL;
		$header .= '	<tr><td class="w_small">Quicklinks:</td>'.PHP_EOL;
		$header .= '		'.implode('',$anchors);
		if(isNum($diffcount['i']) && $diffcount['i'] > 0){
			$header .= ' 		<td class="w_small"><div style="width:10px;"></div></td>'.PHP_EOL;
			$header .= ' 		<td class="w_ins w_small">'.$diffcount['i'].' Lines Added</td>'.PHP_EOL;
		}
		if(isNum($diffcount['d']) && $diffcount['d'] > 0){
			$header .= ' 		<td class="w_small"><div style="width:10px;"></div></td>'.PHP_EOL;
			$header .= ' 		<td class="w_del w_small">'.$diffcount['d'].' Lines Deleted</td>'.PHP_EOL;
		}
		$header .= '	</tr></table></div>'.PHP_EOL;
	}
	$rtn = $header;
	$rtn .= $result;
	return $rtn;
	}
//---------- begin function simpleDiff
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function simpleDiff($old, $new){
	$maxlen=0;
	foreach($old as $oindex => $ovalue){
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex){
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
			$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen){
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		simpleDiff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		simpleDiff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
	);
}
//---------- begin function encodeAscii---------------------------------------
/**
* @describe encodes as string into ASCII characters
* @param str string
*	the string you want encoded
* @return
*	an ASCII encoded string
* @usage
* $ascii=encodeAscii($str);
*/
function encodeAscii($str=''){
	//encodes every letter into its ascii equivilent
	$outstr='';
	for($i=0;$i<strlen($str);$i++){
		$char=$str[$i];
		$code=ord($char);
		if(($code > 64 && $code < 91) || ($code > 96 && $code < 123)){
			$outstr .= '&#'.$code.';';
			}
		else{$outstr .= $str[$i];}
    	}
    return $outstr;
	}
//---------- begin function encodeJson
/**
* @describe wrapper for json_encode, if it failes it encodes the structure in utf-8 and tries again
* @param arr array or object - array or object to encode
* @return str json string
* @usage $json_string=encodeJson($arr);
*/
function encodeJson($arr,$flags=0){
	if($flags==0){$flags=JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE;}
	$str=json_encode($arr,$flags);
	if(!strlen($str)){
		$arr = mb_convert_encoding($arr, 'UTF-8', 'UTF-8');
		$str=json_encode($arr,$flags);
	}
	return $str;
}
//---------- begin function encodeBase64
/**
* @describe wrapper for base64_encode
* @param str string - string to encode
* @return str - Base64 encoded string
* @usage $enc=encodeBase64($str);
*/
function encodeBase64($str=''){
	return base64_encode($str);
	}
//---------- begin function encodeBase64
/**
* @describe returns a numeric Cyclical Redundancy Check value
* @param str string
*	string to encode
* @return
*	a numeric Cyclical Redundancy Check value
* @usage $enc=encodeCRC($str);
*/
function encodeCRC($data=''){
	return abs(crc32($data));
	}
//---------- begin function encodeURL
/**
* @describe wrapper for urlencode
* @param str string
*	string to encode
* @return
*	URL encoded string
* @usage $enc=encodeURL($str);
*/
function encodeURL($str=''){
	$str=trim((string)$str);
	if(!strlen($str)){return '';}
	return urlencode($str);
	}
//---------- begin function encodeData
/**
* @describe email encoding methods
* @param data string
*	data to encode
* @param encoding_method string
*	method to use: 7bit, qp, or base64
* @return
*	encoded string
* @usage $enc=encodeData($data,'7bit');
*/
function encodeData($data='', $encoding=''){
	switch ($encoding) {
		case '7bit':
			return $data;
			break;
		case 'qp':
			//quoted-printable
			return encodeQP($data);
			break;
		case 'base64':
			return rtrim(chunk_split(base64_encode($data), 76, MAIL_MIMEPART_CRLF));
			break;
		default:
			return $data;
		}
	}
//---------- begin function encodeHtml
/**
* @describe encodes html chars that would mess in  a browser
* @param str string
*	string to encode
* @param convert_tabs boolean
* @return
*	encodes html chars that would mess in  a browser
* @usage $html=encodeHtml($html);
*/
function encodeHtml($string='',$convert_tabs=0){
	if(strlen($string)==0){return $string;}
	//doing this a different way since htmlspecialchars also decodes UTF-8 characters which is not needed.
	$string=str_replace('?','[[!Q!]]',$string);
	$string=str_replace(
		array('<','>','"'),
		array('&lt;','&gt;','&quot;'),
		$string
	);
	$string=str_replace('?',' ',$string);
	$string=str_replace('[[!Q!]]','?',$string);
	return $string;

	// //Mar 30 2015 - additional UTF-8 fix
	
	// if(function_exists('mb_encode_numericentity')){
	// 	$string=utf2Html($string);
	// }
	// //echo '<textarea style="width:1000px;height:200px;">'.$string.'</div>';exit;
	// $string=str_replace('?',' ',$string);
	// $string=str_replace('[[!Q!]]','?',$string);
	// //Apr 2018 - no longer seem to need the UTF-8 fix
	// //return $string;
	// //Aug 7 2012: fix for UTF-8 characters to show properly in textarea
	// //echo '<textarea style="width:1000px;height:200px;">'.$string.'</div>';
	// $string = str_replace(array('{','}'),array('{','}'),htmlspecialchars($string,ENT_QUOTES,'UTF-8',true));
	// //echo '<textarea style="width:1000px;height:200px;">'.htmlspecialchars_decode($string).'</textarea>';exit;
	// if($convert_tabs==0){return htmlspecialchars_decode($string);}
	// //Mar 5 2013: replace tabs with emsp if requested
	// $tabspace='&'.'emsp'.';';
	// $string=str_ireplace("\t",$tabspace,$string);
	// return htmlspecialchars_decode($string);
	}
//---------- begin function utf2Html
/**
* @describe converts utf8 chars to ascii codes
* @param str string - utf8 string to convert
* @return string
* @usage $html=utf2Html($str);
*/
function utf2Html($utf2html_string){
    $f = 0xffff;
    $convmap = array(
		/* <!ENTITY % HTMLlat1 PUBLIC "-//W3C//ENTITIES Latin 1//EN//HTML">
	    %HTMLlat1; */
	     160,  255, 0, $f,
		/* <!ENTITY % HTMLsymbol PUBLIC "-//W3C//ENTITIES Symbols//EN//HTML">
	    %HTMLsymbol; */
	     402,  402, 0, $f,  913,  929, 0, $f,  931,  937, 0, $f,
	     945,  969, 0, $f,  977,  978, 0, $f,  982,  982, 0, $f,
	    8226, 8226, 0, $f, 8230, 8230, 0, $f, 8242, 8243, 0, $f,
	    8254, 8254, 0, $f, 8260, 8260, 0, $f, 8465, 8465, 0, $f,
	    8472, 8472, 0, $f, 8476, 8476, 0, $f, 8482, 8482, 0, $f,
	    8501, 8501, 0, $f, 8592, 8596, 0, $f, 8629, 8629, 0, $f,
	    8656, 8660, 0, $f, 8704, 8704, 0, $f, 8706, 8707, 0, $f,
	    8709, 8709, 0, $f, 8711, 8713, 0, $f, 8715, 8715, 0, $f,
	    8719, 8719, 0, $f, 8721, 8722, 0, $f, 8727, 8727, 0, $f,
	    8730, 8730, 0, $f, 8733, 8734, 0, $f, 8736, 8736, 0, $f,
	    8743, 8747, 0, $f, 8756, 8756, 0, $f, 8764, 8764, 0, $f,
	    8773, 8773, 0, $f, 8776, 8776, 0, $f, 8800, 8801, 0, $f,
	    8804, 8805, 0, $f, 8834, 8836, 0, $f, 8838, 8839, 0, $f,
	    8853, 8853, 0, $f, 8855, 8855, 0, $f, 8869, 8869, 0, $f,
	    8901, 8901, 0, $f, 8968, 8971, 0, $f, 9001, 9002, 0, $f,
	    9674, 9674, 0, $f, 9824, 9824, 0, $f, 9827, 9827, 0, $f,
	    9829, 9830, 0, $f,
		/* <!ENTITY % HTMLspecial PUBLIC "-//W3C//ENTITIES Special//EN//HTML">
	   %HTMLspecial; */
		/* These ones are excluded to enable HTML: 34, 38, 60, 62 */
	     338,  339, 0, $f,  352,  353, 0, $f,  376,  376, 0, $f,
	     710,  710, 0, $f,  732,  732, 0, $f, 8194, 8195, 0, $f,
	    8201, 8201, 0, $f, 8204, 8207, 0, $f, 8211, 8212, 0, $f,
	    8216, 8218, 0, $f, 8218, 8218, 0, $f, 8220, 8222, 0, $f,
	    8224, 8225, 0, $f, 8240, 8240, 0, $f, 8249, 8250, 0, $f,
	    8364, 8364, 0, $f
	);
    return mb_encode_numericentity($utf2html_string, $convmap, "UTF-8");
}
//---------- begin function encodeLatin
/**
* @describe convert latin characters to html friendly entities
* @param str string
*	string to encode
* @return
*	convert latin characters to html friendly entities
* @usage $enc=encodeLatin($str);
*/
function encodeLatin($str='') {
	$str = html_entity_decode(stripslashes($str),ENT_QUOTES,'UTF-8');
    $ar = preg_split('/(?<!^)(?!$)/u', $str );  // return array of every multi-byte character
    $str2='';
    foreach ($ar as $c){
        $o = ord($c);
        if ( (strlen($c) > 1) || /* multi-byte [unicode] */
            ($o <32 || $o > 126) || /* <- control / latin weirdos -> */
            ($o >33 && $o < 40) ||/* quotes + ambersand */
            ($o >59 && $o < 63) /* html */
        ) {
            // convert to numeric entity
            $c = mb_encode_numericentity($c,array (0x0, 0xffff, 0, 0xffff), 'UTF-8');
        }
        $str2 .= $c;
    }
    return $str2;
}
//---------- begin function encodeQP
/**
* @describe encode Quoted Printable - used in email sometimes
* @param str string
*	string to encode
* @param line_max int
*	defaults to 76
* @return
*	Quoted Printable encoded string for use in email messages
* @usage $enc=encodeQP($str);
*/
function encodeQP($data , $line_max = 76){
	$lines  = preg_split("/\r?\n/", $data);
	if (!defined('MAIL_MIMEPART_CRLF')) {
		define('MAIL_MIMEPART_CRLF', defined('MAIL_MIME_CRLF') ? MAIL_MIME_CRLF : "\r\n", TRUE);
		}
	$eol    = MAIL_MIMEPART_CRLF;
	$escape = '=';
	$output = '';
	while(list(, $line) = each($lines)){
		$line    = preg_split('||', $line, -1, PREG_SPLIT_NO_EMPTY);
		$linlen     = count($line);
		$newline = '';
		for ($i = 0; $i < $linlen; $i++) {
			$char = $line[$i];
			$dec  = ord($char);
			if (($dec == 32) AND ($i == ($linlen - 1))){    // convert space at eol only
				$char = '=20';
				}
			elseif(($dec == 9) AND ($i == ($linlen - 1))) {  // convert tab at eol only
				$char = '=09';
				}
			elseif($dec == 9) {
				// Do nothing if a tab.
				}
			elseif(($dec == 61) OR ($dec < 32 ) OR ($dec > 126)) {
				$char = $escape . strtoupper(sprintf('%02s', dechex($dec)));
				}
			elseif (($dec == 46) AND ($newline == '')) {
                //Bug #9722: convert full-stop at bol
                //Some Windows servers need this, won't break anything (cipri)
				$char = '=2E';
				}
			if ((strlen($newline) + strlen($char)) >= $line_max) {
				// MAIL_MIMEPART_CRLF is not counted
				$output  .= $newline . $escape . $eol;                    // soft line break; " =\r\n" is okay
				$newline  = '';
				}
			$newline .= $char;
			} // end of for
		$output .= $newline . $eol;
		} //end while
	$output = substr($output, 0, -1 * strlen($eol)); // Don't want last crlf
	return $output;
	}
//---------- begin function encryptSalt
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function encryptSalt($val){
	$crc=crc32(strtolower($val));
	$str=strrev($crc);
	return sha1($str);
	}
//---------- begin function encrypt
/**
* @describe encrypts a string with a salt value
* @param str string
*	string to encrypt
* @param salt string
*	salt value to use for the encryption.
*	You must use the same salt value in the decrypt function to decrypt the encrypted string
* @return
*	encrypted string
* @usage $enc=encrypt($str,$salt);
*/
function encrypt($string='', $salt='') {
	if(!strlen($salt)){
		$seed=setValue(array($_SERVER['HTTP_HOST'],$_SERVER['UNIQUE_HOST'],$_SERVER['SERVER_NAME']));
		$salt=encryptSalt($seed);
		}
	$result='';
	for($i=0; $i<strlen($string); $i++) {
		$char = substr($string, $i, 1);
		$keychar = substr($salt, ($i % strlen($salt))-1, 1);
		$char = chr(ord($char)+ord($keychar));
		$result.=$char;
		}
	return base64_encode($result);
	}
//---------- begin function decrypt
/**
* @describe decrypts a encrypted string that was encrypted using the encrypt function
* @param str string
*	string to decrypt
* @param salt string
*	salt value to use for the decryption.
*	You must use the same salt value as used in the encrypt function
* @return
*	decrypted string
* @usage $enc=decrypt($str,$salt);
*/
function decrypt($string='', $salt='') {
	if(!strlen($salt)){
		$seed=setValue(array($_SERVER['HTTP_HOST'],$_SERVER['UNIQUE_HOST'],$_SERVER['SERVER_NAME']));
		$salt=encryptSalt($seed);
		}
	$result = '';
	$string = base64_decode($string);
	for($i=0; $i<strlen($string); $i++) {
		$char = substr($string, $i, 1);
		$keychar = substr($salt, ($i % strlen($salt))-1, 1);
		$char = chr(ord($char)-ord($keychar));
		$result.=$char;
		}
	return $result;
	}
//---------- begin function evalPHP_ob
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalPHP_ob($string, $flags) {
	/*
		ob_start PHP Warning:
		Some web servers (e.g. Apache) change the working directory of a script when calling the callback function.
		You can change it back by e.g. chdir(dirname($_SERVER['SCRIPT_FILENAME'])) in the callback function.
	*/
	if(isset($_SERVER['SCRIPT_FILENAME'])){
		chdir(dirname($_SERVER['SCRIPT_FILENAME']));
		}
    if ( $flags & PHP_OUTPUT_HANDLER_END ){return $string;}
	return $string;
	return '';
	}
//---------- begin function evalPHP
/**
* @describe
*	evaluates PHP, Perl, Python, Ruby, bash, or sh embeded scripts and returns the result
*	supports short tags for PHP: 
*	Perl, Python, Ruby bash, and sh scripts are passed USER, SERVER, REQUEST, PASSTHRU, and CONFIG variables
*	&lt;?PHP ...?&gt;  or &lt;?=... ?&gt; or &lt;?perl ....?&gt; or &lt;?python ...?&gt; or &lt;?ruby ... ?&gt; or &lt;?bash ...?&gt; or &lt;?sh ...?&gt;
* @param str string or array of strings
*	php code or php embeded html to eval
* @return
*	eval result or errors
* @usage $rtn=evalPHP($str);
*/
function evalPHP($strings){
	global $CONFIG;
	global $PAGE;
	$getWasqlTempPath=getWasqlTempPath();
	//allow for both echo and return values but not both
	if(!is_array($strings)){$strings=array($strings);}
	//ob_start('evalPHP_ob');
	ob_start();
	
	$cntA=count($strings);
	for($sIndex=0;$sIndex<$cntA;$sIndex++){
		unset($evalmatches);
		unset($ex);
		if($sIndex == 1 || $cntA==1){
			$strings[$sIndex]=removeViews($strings[$sIndex]);
			$strings[$sIndex]=processForeach($strings[$sIndex]);
			}
		if(trim($strings[$sIndex])=='<??>'){
			$strings[$sIndex]='';
			continue;
		}
		if(is_null($strings[$sIndex])){
			$strings[$sIndex]='';
			continue;
		}
		preg_match_all('/\<\?(.+?)\?\>/sm',$strings[$sIndex],$evalmatches,PREG_PATTERN_ORDER);
		preg_match_all('/<script type\="php">(.+?)<\/script>/ism',$strings[$sIndex],$evalmatches2,PREG_PATTERN_ORDER);
		if(count($evalmatches2[1])){
			$evalmatches[0]=array_merge($evalmatches[0],$evalmatches2[0]);
			$evalmatches[1]=array_merge($evalmatches[1],$evalmatches2[1]);
		}
		$cntB=count($evalmatches[1]);
		for($ex=0;$ex<$cntB;$ex++){
			$evalcode=$evalmatches[1][$ex];
			//check for other supported languages: python, perl, ruby, bash, sh (bourne shell) 
			if(preg_match('/^(python|py|perl|pl|ruby|rb|vbscript|vbs|bash|sh|node|nodejs|lua|R|rscript|tcl)[\ \r\n]+(.+)/ism',$evalcode,$g)){
				$evalcode=preg_replace('/^'.$g[1].'/i','',$evalcode);
				$lang=commonGetLangInfo($g[1]);
				$lang['evalcode_md5']=md5($evalcode);
				$c=0;
				switch(strtolower($lang['name'])){
					case 'tcl':
						$val=evalTclCode($lang,$evalcode);
						$strings[$sIndex]=str_replace($evalmatches[0][$ex],$val,$strings[$sIndex]);
						$c=1;
					break;
					case 'r':
						$val=evalRCode($lang,$evalcode);
						$strings[$sIndex]=str_replace($evalmatches[0][$ex],$val,$strings[$sIndex]);
						$c=1;
					break;
					case 'python':
						$val=evalPythonCode($lang,$evalcode);
						$strings[$sIndex]=str_replace($evalmatches[0][$ex],$val,$strings[$sIndex]);
						$c=1;
					break;
					case 'perl':
						$val=evalPerlCode($lang,$evalcode);
						$strings[$sIndex]=str_replace($evalmatches[0][$ex],$val,$strings[$sIndex]);
						$c=1;;
					break;
					case 'lua':
						//https://www.educba.com/lua-json/
						$val=evalLuaCode($lang,$evalcode);
						$strings[$sIndex]=str_replace($evalmatches[0][$ex],$val,$strings[$sIndex]);
						$c=1;
					break;
					case 'nodejs':
						//https://www.educba.com/lua-json/
						$val=evalNodejsCode($lang,$evalcode);
						$strings[$sIndex]=str_replace($evalmatches[0][$ex],$val,$strings[$sIndex]);
						$c=1;
					break;
				}
				if($c==1){continue;}
				//handle other languages
				$page=isset($PAGE['_id'])?$PAGE['_id']:0;
				$pageid="p".$page;
				$tmpfile="{$CONFIG['name']}_{$pageid}_".md5($evalcode).".{$lang['ext']}";
				$evalcode=commonAddPrecode($lang,$evalcode);
				//run the script:
				if(is_file($evalcode)){
					$pfile=$evalcode;
					$command = "{$lang['exe']} \"{$pfile}\"";
					$out = cmdResults($command);
					if($out['rtncode']==0){$val=$out['stdout'];}	
					else{$val="ERROR: {$lang['exe']} embeded script failed";}
				}
				else{
					$tmppath=getWasqlTempPath();
					
					if($lang['ext'] != 'vbs' && !stringBeginsWith($evalcode,'#!')){
						$evalcode="{$lang['shebang']}".PHP_EOL.PHP_EOL.$evalcode;
					}
					if(isWindows()){
						setFileContents("{$tmppath}/{$tmpfile}",$evalcode);
						$command = "{$lang['exe']} \"{$tmppath}\\{$tmpfile}\"";
					}
					else{
						setFileContents("{$tmppath}/{$tmpfile}",$evalcode);
						$command = "{$lang['exe']} \"{$tmppath}/{$tmpfile}\"";	
					}
					$out = cmdResults($command);
					if($out['rtncode']==0){
						unlink("{$tmppath}/{$tmpfile}");
						$val=$out['stdout'];
					}	
					else{
						$val="<pre style=\"font-size:12px;text-align:left;\">ERROR: {$lang['exe']} embeded script failed".PHP_EOL.json_encode($out,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT).'</pre>';
					}
				}
				$strings[$sIndex]=str_replace($evalmatches[0][$ex],$val,$strings[$sIndex]);
				continue;
			}
			//assume PHP code
			$evalcode=preg_replace('/^php/i','',$evalcode);
			if(preg_match('/^xml version/i',$evalcode)){continue;}
			//  remove =/*...*/
			$evalcode=preg_replace('/^\=\/\*(.+?)\*\//','',$evalcode);
			$evalcode=preg_replace('/^\=/','return ',$evalcode);
			$evalcheck="error_reporting(E_ERROR | E_PARSE);\nreturn true;\n". trim($evalcode);
			@trigger_error('');
			@eval($evalcheck);
			$e=error_get_last();
			if(isset($e['message']) && $e['message']!==''){
				$e['code']="<pre>\n".trim($evalmatches[1][$ex])."\n</pre>\n";
				setWasqlError(array($e),"EvalPHP Error");
	    		$error=showWasqlErrors();
	    		$strings[$sIndex]=str_replace($evalmatches[0][$ex],$error,$strings[$sIndex]);
			}
			else{
				@trigger_error('');
				$val=@eval($evalcode);
				if(is_null($val)){$val='';}
				$ob=ob_get_contents();
				ob_clean();
				ob_flush();
				if(strlen(trim($ob)) && strlen(trim($val))){
					if(stringContains($ob,'wasqlDebug')){
						$strings[$sIndex]=str_replace($evalmatches[0][$ex],$ob,$strings[$sIndex]);
					}
					else{
						$strings[$sIndex]=str_replace($evalmatches[0][$ex],'evalPHP Error: return value and echo value both found',$strings[$sIndex]);
					}
				}
				elseif(strlen(trim($ob))){
					if(!is_string($ob)){
						$ob=json_encode($ob,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
					}
					$strings[$sIndex]=str_replace($evalmatches[0][$ex],$ob,$strings[$sIndex]);
				}
				else{
					if(is_null($val)){$val='';}
					if(!is_string($val)){
						$val=json_encode($val,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
					}
					$strings[$sIndex]=str_replace($evalmatches[0][$ex],$val,$strings[$sIndex]);
		        }
			}
		}
	}
	ob_clean();
	ob_flush();
	showAllErrors();
	$rtn=implode('',$strings);
	return $rtn;
}
//---------- begin function evalNodejsCode
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalNodejsCode($lang,$evalcode){
	global $USER;
	global $CONFIG;
	global $PAGE;
	global $TEMPLATE;
	global $PASSTHRU;
	global $DATABASE;
	global $CRONTHRU;
	$CRONTHRU['pid']=getmypid();
	$wasqlTempPath=getWasqlTempPath();
	$wasqlTempPath=str_replace("\\","/",$wasqlTempPath);
	$wasqlPythonPath=getWasqlPath('python');
	$wasqlPythonPath=str_replace("\\","/",$wasqlPythonPath);
	$files=array(
		'main'=>"{$wasqlTempPath}/main_{$lang['evalcode_md5']}.js",
		'wasql'=>"{$wasqlTempPath}/wasql_{$lang['evalcode_md5']}.js",
	);
	$pagecode='';
	if(isset($CONFIG['includes'][$lang['ext']][0]) && is_file($CONFIG['includes'][$lang['ext']][0])){
		$files['include']=$CONFIG['includes'][$lang['ext']][0];
		$code=getFileContents($files['include']);
		$code=preg_replace('/^\<\?(lua)/is','',rtrim($code));
		$code=preg_replace('/\?\>$/is','',ltrim($code));
		$code=trim($code);
		$files['page']="{$wasqlTempPath}/page_{$lang['evalcode_md5']}.js";
		$pagecode="var page = require('./page_{$lang['evalcode_md5']}.js');";
		$content=<<<ENDOFCONTENT
#! nodejs

{$code}

ENDOFCONTENT;
	setFileContents($files['page'],$content);
		//{$lang['evalcode_md5']}
	}
	//create a wasql.py file
	if(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
	}
	else{
		$db=array();
	}
	$removes=array('body','functions','controller','js','js_min','css','css_min');
	$p=$PAGE;
	foreach($removes as $fld){
		if(isset($p[$fld])){unset($p[$fld]);}
	}
	$t=$TEMPLATE;
	foreach($removes as $fld){
		if(isset($t[$fld])){unset($t[$fld]);}
	}
	/*
		$json=json_encode($arr,JSON_UNESCAPED_SLASHES);
			$json=str_replace('\\"','',$json);
			$precode[]="local {$varname} = json.decode('".$json."');";
	*/
	$wasql=array(
		'USER'=>"let USER = ".json_encode(evalCleanupGlobal($USER),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";",
		'CONFIG'=>"let CONFIG = ".json_encode(evalCleanupGlobal($CONFIG),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";",
		'PAGE'=>"let PAGE = ".json_encode(evalCleanupGlobal($p),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";",
		'TEMPLATE'=>"let TEMPLATE = ".json_encode(evalCleanupGlobal($t),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";",
		'PASSTHRU'=>"let PASSTHRU = ".json_encode(evalCleanupGlobal($PASSTHRU),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";",
		'DATABASE'=>"let DATABASE = ".json_encode(evalCleanupGlobal($db),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";",
		'REQUEST'=>"let REQUEST = ".json_encode(evalCleanupGlobal($_REQUEST),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";",
		'SESSION'=>"let SESSION = ".json_encode(evalCleanupGlobal($_SESSION),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";",
		'CRONTHRU'=>"let CRONTHRU = ".json_encode(evalCleanupGlobal($CRONTHRU),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";"
	);
	//add any additional globals
	if(isset($CONFIG['eval_globals'])){
		if(is_string($CONFIG['eval_globals'])){
			$CONFIG['eval_globals']=preg_split('/\,/',$CONFIG['eval_globals']);
		}
		foreach($CONFIG['eval_globals'] as $var){
			global $$var;
			$wasql[$var]="let CRONTHRU = ".json_encode(evalCleanupGlobal($$var),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";";
		}
	}
	$content=<<<ENDOFCONTENT
#! nodejs

exports.user = function(k){
	{$wasql['USER']}
	return USER[k] || "";
}
exports.config = function(k){
	{$wasql['CONFIG']}
	return CONFIG[k] || "";
}
exports.page = function(k){
	{$wasql['PAGE']}
	return PAGE[k] || "";
}
exports.template = function(k){
	{$wasql['TEMPLATE']}
	return TEMPLATE[k] || "";
}
exports.passthru = function(k){
	{$wasql['PASSTHRU']}
	return PASSTHRU[k] || "";
}
exports.database = function(k){
	{$wasql['DATABASE']}
	return DATABASE[k] || "";
}
exports.request = function(k){
	{$wasql['REQUEST']}
	return REQUEST[k] || "";
}
exports.session = function(k){
	{$wasql['SESSION']}
	return SESSION[k] || "";
}
exports.cronthru = function(k){
	{$wasql['CRONTHRU']}
	return CRONTHRU[k] || "";
}

ENDOFCONTENT;
	setFileContents($files['wasql'],$content);
	$content=<<<ENDOFCONTENT
#! nodejs

var wasql = require("./wasql_{$lang['evalcode_md5']}.js");
{$pagecode}

{$evalcode}
ENDOFCONTENT;
	setFileContents($files['main'],$content);
	$filename=getFileName($files['main']);
	$command = "{$lang['exe']} \"{$filename}\"";
	//cmdResults($cmd,$args='',$dir='',$timeout=0)
	$out = cmdResults($lang['exe'],$filename,$wasqlTempPath);
	//remove the temp files
	if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] != 'nodejs'){
		foreach($files as $name=>$afile){
			unlink($afile);
		}
	}
	if($out['rtncode']==0){
		return $out['stdout'];
	}	
	elseif(isset($out['stderr']) && strlen($out['stderr'])){
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded Nodejs Script Error. Return Code: {$out['rtncode']} !!</div>
<pre style="color:#5f5f5f;margin-left:20px;">
{$out['stderr']}
</pre>
ENDOFERR;
		return $err;
	}
	else{
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded Nodejs Script Error. Return Code: {$out['rtncode']} !!</div>
<pre style="color:#5f5f5f;margin-left:20px;">
{$out['stdout']}
</pre>
ENDOFERR;
		return $err;
	}
}
//---------- begin function evalLuaCode
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalLuaCode($lang,$evalcode){
	global $USER;
	global $CONFIG;
	global $PAGE;
	global $TEMPLATE;
	global $PASSTHRU;
	global $DATABASE;
	global $CRONTHRU;
	$CRONTHRU['pid']=getmypid();
	$wasqlTempPath=getWasqlTempPath();
	$wasqlTempPath=str_replace("\\","/",$wasqlTempPath);
	$wasqlPythonPath=getWasqlPath('python');
	$wasqlPythonPath=str_replace("\\","/",$wasqlPythonPath);
	$json_lua_dest=getWasqlPath('php/temp/json.lua');
	//copy json.lua to the tmppath
	if(!file_exists($json_lua)){
		$json_lua_source=getWasqlPath('php/extras/json.lua');
		copyFile($json_lua_source,$json_lua_dest);
	}
	$files=array(
		'main'=>"{$wasqlTempPath}/main_{$lang['evalcode_md5']}.lua",
		'wasql'=>"{$wasqlTempPath}/wasql_{$lang['evalcode_md5']}.lua",
		'json'=>"{$wasqlTempPath}/json.lua",
	);
	$pagecode='';
	if(isset($CONFIG['includes'][$lang['ext']][0]) && is_file($CONFIG['includes'][$lang['ext']][0])){
		$files['include']=$CONFIG['includes'][$lang['ext']][0];
		$code=getFileContents($files['include']);
		$code=preg_replace('/^\<\?(lua)/is','',rtrim($code));
		$code=preg_replace('/\?\>$/is','',ltrim($code));
		$code=trim($code);
		$files['page']="{$wasqlTempPath}/page_{$lang['evalcode_md5']}.lua";
		$pagecode="require \"page_{$lang['evalcode_md5']}\";";
		$content=<<<ENDOFCONTENT
#! lua

json = require "json";

{$code}

ENDOFCONTENT;
	setFileContents($files['page'],$content);
		//{$lang['evalcode_md5']}
	}
	//create a wasql.py file
	if(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
	}
	else{
		$db=array();
	}
	$removes=array('body','functions','controller','js','js_min','css','css_min');
	$p=$PAGE;
	foreach($removes as $fld){
		if(isset($p[$fld])){unset($p[$fld]);}
	}
	$t=$TEMPLATE;
	foreach($removes as $fld){
		if(isset($t[$fld])){unset($t[$fld]);}
	}
	/*
		$json=json_encode($arr,JSON_UNESCAPED_SLASHES);
			$json=str_replace('\\"','',$json);
			$precode[]="local {$varname} = json.decode('".$json."');";
	*/
	$wasql=array(
		'USER'=>"local USER = json.decode('".encodeJSON(evalCleanupGlobal($USER),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');",
		'CONFIG'=>"local CONFIG = json.decode('".encodeJSON(evalCleanupGlobal($CONFIG),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');",
		'PAGE'=>"local PAGE = json.decode('".json_encode(evalCleanupGlobal($p),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');",
		'TEMPLATE'=>"local TEMPLATE = json.decode('".json_encode(evalCleanupGlobal($t),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');",
		'PASSTHRU'=>"local PASSTHRU = json.decode('".json_encode(evalCleanupGlobal($PASSTHRU),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');",
		'DATABASE'=>"local DATABASE = json.decode('".json_encode(evalCleanupGlobal($db),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');",
		'REQUEST'=>"local REQUEST = json.decode('".json_encode(evalCleanupGlobal($_REQUEST),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');",
		'SESSION'=>"local SESSION = json.decode('".json_encode(evalCleanupGlobal($_SESSION),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');",
		'CRONTHRU'=>"local CRONTHRU = json.decode('".json_encode(evalCleanupGlobal($CRONTHRU),JSON_UNESCAPED_UNICODE| JSON_UNESCAPED_SLASHES)."');"
	);
	//add any additional globals
	if(isset($CONFIG['eval_globals'])){
		if(is_string($CONFIG['eval_globals'])){
			$CONFIG['eval_globals']=preg_split('/\,/',$CONFIG['eval_globals']);
		}
		foreach($CONFIG['eval_globals'] as $var){
			global $$var;
			$wasql[$var]="local CRONTHRU = json.decode('".json_encode(evalCleanupGlobal($$var),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."');";
		}
	}
	$content=<<<ENDOFCONTENT
#! lua

json = require "json";

function wasqlUser(k)
	{$wasql['USER']}
	return USER[k];
end
function wasqlConfig(k)
	{$wasql['CONFIG']}
	return CONFIG[k];
end
function wasqlPage(k)
	{$wasql['PAGE']}
	return PAGE[k];
end
function wasqlTemplate(k)
	{$wasql['TEMPLATE']}
	return TEMPLATE[k];
end
function wasqlPassthru(k)
	{$wasql['PASSTHRU']}
	return PASSTHRU[k];
end
function wasqlDatabase(k)
	{$wasql['DATABASE']}
	return DATABASE[k];
end
function wasqlRequest(k)
	{$wasql['REQUEST']}
	return REQUEST[k];
end
function wasqlSession(k)
	{$wasql['SESSION']}
	return SESSION[k];
end
function wasqlCronthru(k)
	{$wasql['CRONTHRU']}
	return CRONTHRU[k];
end

ENDOFCONTENT;
	setFileContents($files['wasql'],$content);
	$content=<<<ENDOFCONTENT
#! lua

json = require "json";

require "wasql_{$lang['evalcode_md5']}";
{$pagecode}

{$evalcode}
ENDOFCONTENT;
	setFileContents($files['main'],$content);
	$filename=getFileName($files['main']);
	$command = "{$lang['exe']} \"{$filename}\"";
	//cmdResults($cmd,$args='',$dir='',$timeout=0)
	$out = cmdResults($lang['exe'],$filename,$wasqlTempPath);
	if($out['rtncode']==0){
		//remove the temp files
		if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] != 'lua'){
			foreach($files as $name=>$afile){
				unlink($afile);
			}
		}
		return $out['stdout'];
	}	
	elseif(isset($out['stderr']) && strlen($out['stderr'])){
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded Lua Script Error. Return Code: {$out['rtncode']} !!</div>
<pre style="color:#5f5f5f;margin-left:20px;">
{$out['stderr']}
</pre>
ENDOFERR;
		return $err;
	}
	else{
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded Lua Script Error. Return Code: {$out['rtncode']} !!</div>
<pre style="color:#5f5f5f;margin-left:20px;">
{$out['stdout']}
</pre>
ENDOFERR;
		return $err;
	}
}
//---------- begin function evalPerlCode
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalPerlCode($lang,$evalcode){
	global $USER;
	global $CONFIG;
	global $PAGE;
	global $TEMPLATE;
	global $PASSTHRU;
	global $DATABASE;
	global $CRONTHRU;
	$CRONTHRU['pid']=getmypid();
	$wasqlTempPath=getWasqlTempPath();
	$wasqlTempPath=str_replace("\\","/",$wasqlTempPath);
	$wasqlPythonPath=getWasqlPath('python');
	$wasqlPythonPath=str_replace("\\","/",$wasqlPythonPath);
	$files=array(
		'main'=>"{$wasqlTempPath}/main_{$lang['evalcode_md5']}.pl",
		'wasql'=>"{$wasqlTempPath}/wasql_{$lang['evalcode_md5']}.pl",
	);
	$pagecode='';
	if(isset($CONFIG['includes'][$lang['ext']][0]) && is_file($CONFIG['includes'][$lang['ext']][0])){
		$files['include']=$CONFIG['includes'][$lang['ext']][0];
		$code=getFileContents($files['include']);
		$code=preg_replace('/^\<\?(pl|perl)/is','',rtrim($code));
		$code=preg_replace('/\?\>$/is','',ltrim($code));
		$code=trim($code);
		$files['page']="{$wasqlTempPath}/page_{$lang['evalcode_md5']}.pl";
		$pagecode="require \"page_{$lang['evalcode_md5']}.pl\";";
		$content=<<<ENDOFCONTENT
#! perl

{$code}

return 1;
ENDOFCONTENT;
	setFileContents($files['page'],$content);
		//{$lang['evalcode_md5']}
	}
	//create a wasql.py file
	if(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
	}
	else{
		$db=array();
	}
	$removes=array('body','functions','controller','js','js_min','css','css_min');
	$p=$PAGE;
	foreach($removes as $fld){
		if(isset($p[$fld])){unset($p[$fld]);}
	}
	$t=$TEMPLATE;
	foreach($removes as $fld){
		if(isset($t[$fld])){unset($t[$fld]);}
	}
	/*
		$json=json_encode($arr,JSON_UNESCAPED_SLASHES);
			$json=str_replace('\\"','',$json);
			$precode[]="local {$varname} = json.decode('".$json."');";
	*/
	$wasql=array(
		'USER'=>"my %USER = ".evalGlobal2Perl($USER),
		'CONFIG'=>"my %CONFIG = ".evalGlobal2Perl($CONFIG),
		'PAGE'=>"my %PAGE = ".evalGlobal2Perl($p),
		'TEMPLATE'=>"my %TEMPLATE = ".evalGlobal2Perl($t),
		'PASSTHRU'=>"my %PASSTHRU = ".evalGlobal2Perl($PASSTHRU),
		'DATABASE'=>"my %DATABASE = ".evalGlobal2Perl($db),
		'REQUEST'=>"my %REQUEST = ".evalGlobal2Perl($_REQUEST),
		'SESSION'=>"my %SESSION = ".evalGlobal2Perl($_SESSION),
		'CRONTHRU'=>"my %CRONTHRU = ".evalGlobal2Perl($CRONTHRU)
	);
	//add any additional globals
	if(isset($CONFIG['eval_globals'])){
		if(is_string($CONFIG['eval_globals'])){
			$CONFIG['eval_globals']=preg_split('/\,/',$CONFIG['eval_globals']);
		}
		foreach($CONFIG['eval_globals'] as $var){
			global $$var;
			$wasql[$var]="my %CRONTHRU = ".evalGlobal2Perl($$var);
		}
	}
	$content=<<<ENDOFCONTENT
#! perl

sub wasqlUser{
	my (\$k) = @_;
	{$wasql['USER']}
	return \$USER{\$k} || "";
}
sub wasqlConfig{
	my (\$k) = @_;
	{$wasql['CONFIG']}
	return \$CONFIG{\$k} || "";
}
sub wasqlPage{
	my (\$k) = @_;
	{$wasql['PAGE']}
	return \$PAGE{\$k} || "";
}
sub wasqlTemplate{
	my (\$k) = @_;
	{$wasql['TEMPLATE']}
	return \$TEMPLATE{\$k} || "";
}
sub wasqlPassthru{
	my (\$k) = @_;
	{$wasql['PASSTHRU']}
	return \$PASSTHRU{\$k} || "";
}
sub wasqlDatabase{
	my (\$k) = @_;
	{$wasql['DATABASE']}
	return \$DATABASE{\$k} || "";
}
sub wasqlRequest{
	my (\$k) = @_;
	{$wasql['REQUEST']}
	return \$REQUEST{\$k} || "";
}
sub wasqlSession{
	my (\$k) = @_;
	{$wasql['SESSION']}
	return \$SESSION{\$k} || "";
}
sub wasqlCronthru{
	my (\$k) = @_;
	{$wasql['CRONTHRU']}
	return \$CRONTHRU{\$k} || "";
}

return 1;
ENDOFCONTENT;
	setFileContents($files['wasql'],$content);
	$content=<<<ENDOFCONTENT
#! perl

require "wasql_{$lang['evalcode_md5']}.pl";
{$pagecode}

{$evalcode}
ENDOFCONTENT;
	setFileContents($files['main'],$content);
	$filename=getFileName($files['main']);
	$command = "{$lang['exe']} \"{$filename}\"";
	//cmdResults($cmd,$args='',$dir='',$timeout=0)
	$out = cmdResults($lang['exe'],$filename,$wasqlTempPath);
	//remove the temp files
	if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] != 'perl'){
		foreach($files as $name=>$afile){
			unlink($afile);
		}
	}
	if($out['rtncode']==0){
		return $out['stdout'];
	}	
	elseif(isset($out['stderr']) && strlen($out['stderr'])){
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded Perl Script Error. Return Code: {$out['rtncode']} !!</div>
<pre style="color:#5f5f5f;margin-left:20px;">
{$out['stderr']}
</pre>
ENDOFERR;
		return $err;
	}
	else{
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded Perl Script Error. Return Code: {$out['rtncode']} !!</div>
<pre style="color:#5f5f5f;margin-left:20px;">
{$out['stdout']}
</pre>
ENDOFERR;
		return $err;
	}
}
//---------- begin function evalGlobal2Perl
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalGlobal2Perl($arr){
	$sets=array();
	foreach($arr as $k=>$v){
		if(stringContains($k,"'")){continue;}
		$v=str_replace("'","\\'",$v);
		$sets[]="'{$k}'=>'{$v}'";
	}
	return '('.implode(', ',$sets).');';
}
//---------- begin function evalTclCode
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalTclCode($lang,$evalcode){
	$lines=preg_split('/[\r\n]+/',$evalcode);
	//$_REQUEST['view']='howdy <b class="w_bigg">du</b>';
	$prespace='';
	foreach($lines as $line){
		if(strlen(trim($line)) && preg_match('/^([\s\t]+)/',$line,$m)){
			$prespace=$m[1];
			break;
		}
	}
	foreach($lines as &$line){
	//	$line=preg_replace('/^'.$prespace.'/','',rtrim($line));
	}
	$evalcode=implode(PHP_EOL,$lines);
	global $USER;
	global $CONFIG;
	global $PAGE;
	global $TEMPLATE;
	global $PASSTHRU;
	global $DATABASE;
	global $CRONTHRU;
	$CRONTHRU['pid']=getmypid();
	$wasqlTempPath=getWasqlTempPath();
	$wasqlTempPath=str_replace("\\","/",$wasqlTempPath);
	$wasqlPath=getWasqlPath();
	$wasqlPath=str_replace("\\","/",$wasqlPath);
	$wasqlTclPath=getWasqlPath('Tcl');
	$wasqlTclPath=str_replace("\\","/",$wasqlTclPath);
	$files=array(
		'main'=>"{$wasqlTempPath}/main_{$lang['evalcode_md5']}.tcl",
		'wasql'=>"{$wasqlTempPath}/wasql_{$lang['evalcode_md5']}.tcl",
	);
	$pagecode='';
	if(isset($CONFIG['includes'][$lang['ext']][0]) && is_file($CONFIG['includes'][$lang['ext']][0])){
		$files['include']=$CONFIG['includes'][$lang['ext']][0];
		$code=getFileContents($files['include']);
		$code=preg_replace('/^\<\?(jl|tcl)/is','',rtrim($code));
		$code=preg_replace('/\?\>$/is','',ltrim($code));
		$code=trim($code);
		$files['page']="{$wasqlTempPath}/page_{$lang['evalcode_md5']}.tcl";
		if(!isset($CONFIG['includes_import_name'])){
			$CONFIG['includes_import_name']='page';
		}
		$pagecode="source(page_{$lang['evalcode_md5']})";
		$content=<<<ENDOFCONTENT
#! Rscript

{$code}
ENDOFCONTENT;
	setFileContents($files['page'],$content);
		//{$lang['evalcode_md5']}
	}
	//create a wasql.R file
	if(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
	}
	else{
		$db=array();
	}
	$removes=array('body','functions','controller','js','js_min','css','css_min');
	$p=$PAGE;
	foreach($removes as $fld){
		if(isset($p[$fld])){unset($p[$fld]);}
	}
	$t=$TEMPLATE;
	foreach($removes as $fld){
		if(isset($t[$fld])){unset($t[$fld]);}
	}
	if(!isset($_SESSION)){$_SESSION=[];}
	$wasql=array(
		'USER'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($USER),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'CONFIG'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($CONFIG),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'PAGE'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($p),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'TEMPLATE'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($t),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'PASSTHRU'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($PASSTHRU),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'DATABASE'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($db),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'REQUEST'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($_REQUEST),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'SESSION'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($_SESSION),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'SERVER'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($_SERVER),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\"",
		'CRONTHRU'=>"set jsondict \"\"\"".PHP_EOL.json_encode(evalCleanupGlobal($CRONTHRU),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL."\"\"\""
	);
	//add any additional globals
	if(isset($CONFIG['eval_globals'])){
		if(is_string($CONFIG['eval_globals'])){
			$CONFIG['eval_globals']=preg_split('/\,/',$CONFIG['eval_globals']);
		}
		foreach($CONFIG['eval_globals'] as $var){
			global $$var;
			$wasql[$var]="{$var} = ".json_encode(evalCleanupGlobal($$var),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
		}
	}
	$content=<<<ENDOFCONTENT
#! tcl

proc wasqlTclPath {} {
	return "{$wasqlTclPath}"
}

proc wasqlConfigFile {} {
	return "{$wasqlPath}/config.xml"
}

proc wasqlTempPath {} {
	return "{$wasqlTempPath}"
}

proc wasqlUSER {k} {
	{$wasql['USER']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

proc wasqlCONFIG {k} {
	{$wasql['CONFIG']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

proc wasqlPAGE {k} {
	{$wasql['PAGE']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

proc wasqlTEMPLATE {k} {
	{$wasql['TEMPLATE']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

proc wasqlPASSTHRU {k} {
	{$wasql['PASSTHRU']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

proc wasqlDATABASE {k} {
	{$wasql['DATABASE']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

proc wasqlSESSION {k} {
	{$wasql['SESSION']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

proc wasqlSERVER {k} {
	{$wasql['SERVER']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

proc wasqlCRONTHRU {k} {
	{$wasql['CRONTHRU']}
	# Extract the value at the specified key using dict get
	set value [dict get $jsondict $k]

	# Check if the value is not empty
	if { [string length $value] > 0 } {
		return $value
	} else {
		return ""
	}
}

ENDOFCONTENT;
	setFileContents($files['wasql'],$content);
	$content=<<<ENDOFCONTENT
#! Tcl

source "{$files['wasql']}"
source "{$wasqlTclPath}/config.tcl"
source "{$wasqlTclPath}/common.tcl"
source "{$wasqlTclPath}/db.tcl"

{$evalcode}
ENDOFCONTENT;
	setFileContents($files['main'],$content);
	$filename=getFileName($files['main']);
	$command = "{$lang['exe']} \"{$filename}\" 2>&1";
	//cmdResults($cmd,$args='',$dir='',$timeout=0)
	$out = cmdResults($command,'',$wasqlTempPath);
	//echo printValue($out);exit;
	//remove any R files in temp older than 1 day - wasqlTempPath
	$ok=cleanupDirectory($wasqlTempPath,1,'days','R');
	//check return code
	if(isNum($out['rtncode']) && $out['rtncode']==0){
		//remove the temp files on success
		foreach($files as $name=>$afile){
	 		unlink($afile);
	 	}
		if(isset($out['stdout']) && commonStrlen($out['stdout'])){
			return $out['stdout'];
		}
		if(isset($out['stderr']) && commonStrlen($out['stderr'])){
			return $out['stderr'];
		}
		return '';
	}	
	else{
		$code=commonShowCode($content);
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded Tcl Script Error. Return Code: {$out['rtncode']} !!</div>
<div style="display:inline-flex;background:#333;color:#ccc;padding:5px 7px;border-radius:3px;font-size:0.9rem;">
	<div style="align-self:center;justify-self:center;margin-right:3px;">{$wasqlTempPath}&gt;</div>
	<div style="align-self:center;justify-self:center;color:#FFF;">{$command}</div>
</div>
<pre style="color:#5f5f5f;white-space:break-spaces;font-size:0.8rem;color:#d70000;">
{$out['stdout']}
{$out['stderr']}
</pre>
{$code}
ENDOFERR;
		return $err;
	}
}
//---------- begin function evalRCode
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalRCode($lang,$evalcode){
	$lines=preg_split('/[\r\n]+/',$evalcode);
	//$_REQUEST['view']='howdy <b class="w_bigg">du</b>';
	$prespace='';
	foreach($lines as $line){
		if(strlen(trim($line)) && preg_match('/^([\s\t]+)/',$line,$m)){
			$prespace=$m[1];
			break;
		}
	}
	foreach($lines as &$line){
	//	$line=preg_replace('/^'.$prespace.'/','',rtrim($line));
	}
	$evalcode=implode(PHP_EOL,$lines);
	global $USER;
	global $CONFIG;
	global $PAGE;
	global $TEMPLATE;
	global $PASSTHRU;
	global $DATABASE;
	global $CRONTHRU;
	$CRONTHRU['pid']=getmypid();
	$wasqlTempPath=getWasqlTempPath();
	$wasqlTempPath=str_replace("\\","/",$wasqlTempPath);
	$wasqlPath=getWasqlPath();
	$wasqlPath=str_replace("\\","/",$wasqlPath);
	$wasqlRPath=getWasqlPath('R');
	$wasqlRPath=str_replace("\\","/",$wasqlRPath);
	$files=array(
		'main'=>"{$wasqlTempPath}/main_{$lang['evalcode_md5']}.R",
		'wasql'=>"{$wasqlTempPath}/wasql_{$lang['evalcode_md5']}.R",
	);
	$pagecode='';
	if(isset($CONFIG['includes'][$lang['ext']][0]) && is_file($CONFIG['includes'][$lang['ext']][0])){
		$files['include']=$CONFIG['includes'][$lang['ext']][0];
		$code=getFileContents($files['include']);
		$code=preg_replace('/^\<\?(r|rscript)/is','',rtrim($code));
		$code=preg_replace('/\?\>$/is','',ltrim($code));
		$code=trim($code);
		$files['page']="{$wasqlTempPath}/page_{$lang['evalcode_md5']}.R";
		if(!isset($CONFIG['includes_import_name'])){
			$CONFIG['includes_import_name']='page';
		}
		$pagecode="source(page_{$lang['evalcode_md5']})";
		$content=<<<ENDOFCONTENT
#! Rscript

{$code}
ENDOFCONTENT;
	setFileContents($files['page'],$content);
		//{$lang['evalcode_md5']}
	}
	//create a wasql.R file
	if(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
	}
	else{
		$db=array();
	}
	$removes=array('body','functions','controller','js','js_min','css','css_min');
	$p=$PAGE;
	foreach($removes as $fld){
		if(isset($p[$fld])){unset($p[$fld]);}
	}
	$t=$TEMPLATE;
	foreach($removes as $fld){
		if(isset($t[$fld])){unset($t[$fld]);}
	}
	if(!isset($_SESSION)){$_SESSION=[];}
	$wasql=array(
		'USER'=>"USER <- fromJSON('".json_encode(evalCleanupGlobal($USER),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'CONFIG'=>"CONFIG <- fromJSON('".json_encode(evalCleanupGlobal($CONFIG),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'PAGE'=>"PAGE <- fromJSON('".json_encode(evalCleanupGlobal($p),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'TEMPLATE'=>"TEMPLATE <- fromJSON('".json_encode(evalCleanupGlobal($t),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'PASSTHRU'=>"PASSTHRU <- fromJSON('".json_encode(evalCleanupGlobal($PASSTHRU),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'DATABASE'=>"DATABASE <- fromJSON('".json_encode(evalCleanupGlobal($db),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'REQUEST'=>"REQUEST <- fromJSON('".json_encode(evalCleanupGlobal($_REQUEST),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'SESSION'=>"SESSION <- fromJSON('".json_encode(evalCleanupGlobal($_SESSION),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'SERVER'=>"SERVER <- fromJSON('".json_encode(evalCleanupGlobal($_SERVER),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')",
		'CRONTHRU'=>"CRONTHRU <- fromJSON('".json_encode(evalCleanupGlobal($CRONTHRU),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."')"
	);
	//add any additional globals
	if(isset($CONFIG['eval_globals'])){
		if(is_string($CONFIG['eval_globals'])){
			$CONFIG['eval_globals']=preg_split('/\,/',$CONFIG['eval_globals']);
		}
		foreach($CONFIG['eval_globals'] as $var){
			global $$var;
			$wasql[$var]="{$var} = ".json_encode(evalCleanupGlobal($$var),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
		}
	}
	$content=<<<ENDOFCONTENT
#! Rscript
wasqlRPath <- function(){
	return("{$wasqlRPath}")
}

wasqlConfigFile <- function(){
	return("{$wasqlPath}/config.xml")
}

wasqlTempPath <- function(){
	return("{$wasqlTempPath}")
}

wasqlUSER <- function(k) {
	{$wasql['USER']}
	if (!is.null(USER[[k]])) { 
	  return(USER[[k]])
	} else {
	  return("")
	}
}

wasqlCONFIG <- function(k) {
	{$wasql['CONFIG']}
	if (!is.null(CONFIG[[k]])) { 
	  return(CONFIG[[k]])
	} else {
	  return(CONFIG)
	}
}

wasqlPAGE <- function(k) {
	{$wasql['PAGE']}
	if (!is.null(PAGE[[k]])) { 
	  return(PAGE[[k]])
	} else {
	  return("")
	}
}

wasqlTEMPLATE <- function(k) {
	{$wasql['TEMPLATE']}
	if (!is.null(TEMPLATE[[k]])) { 
	  return(TEMPLATE[[k]])
	} else {
	  return("")
	}
}

wasqlPASSTHRU <- function(k) {
	{$wasql['PASSTHRU']}
	if (!is.null(PASSTHRU[[k]])) { 
	  return(PASSTHRU[[k]])
	} else {
	  return("")
	}
}

wasqlDATABASE <- function(k) {
	{$wasql['DATABASE']}
	if (!is.null(DATABASE[[k]])) { 
	  return(DATABASE[[k]])
	} else {
	  return("")
	}
}

wasqlSESSION <- function(k) {
	{$wasql['SESSION']}
	if (!is.null(SESSION[[k]])) { 
	  return(SESSION[[k]])
	} else {
	  return("")
	}
}

wasqlSERVER <- function(k) {
	{$wasql['SERVER']}
	if (!is.null(SERVER[[k]])) { 
	  return(SERVER[[k]])
	} else {
	  return("")
	}
}

wasqlCRONTHRU <- function(k) {
	{$wasql['CRONTHRU']}
	if (!is.null(CRONTHRU[[k]])) { 
	  return(CRONTHRU[[k]])
	} else {
	  return("")
	}
}
ENDOFCONTENT;
	setFileContents($files['wasql'],$content);
	$content=<<<ENDOFCONTENT
#! Rscript

	suppressPackageStartupMessages(library(tidyverse))
	suppressPackageStartupMessages(library(jsonlite, quietly = TRUE))
	suppressPackageStartupMessages(library(htmlTable, quietly = TRUE))
	
	source("{$files['wasql']}")
	source("{$wasqlRPath}/common.R")
	source("{$wasqlRPath}/config.R")
	source("{$wasqlRPath}/db.R")
	

{$evalcode}
ENDOFCONTENT;
	setFileContents($files['main'],$content);
	$filename=getFileName($files['main']);
	$command = "{$lang['exe']} \"{$filename}\" 2>&1";
	//cmdResults($cmd,$args='',$dir='',$timeout=0)
	$out = cmdResults($command,'',$wasqlTempPath);
	//echo printValue($out);exit;
	//remove any R files in temp older than 1 day - wasqlTempPath
	$ok=cleanupDirectory($wasqlTempPath,1,'days','R');
	//check return code
	if(isNum($out['rtncode']) && $out['rtncode']==0){
		//remove the temp files on success
		foreach($files as $name=>$afile){
	 		unlink($afile);
	 	}
		if(isset($out['stdout']) && commonStrlen($out['stdout'])){
			return $out['stdout'];
		}
		if(isset($out['stderr']) && commonStrlen($out['stderr'])){
			return $out['stderr'];
		}
		return '';
	}	
	else{
		$code=commonShowCode($content);
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded R Script Error. Return Code: {$out['rtncode']} !!</div>
<div style="display:inline-flex;background:#333;color:#ccc;padding:5px 7px;border-radius:3px;font-size:0.9rem;">
	<div style="align-self:center;justify-self:center;margin-right:3px;">{$wasqlTempPath}&gt;</div>
	<div style="align-self:center;justify-self:center;color:#FFF;">{$command}</div>
</div>
<pre style="color:#5f5f5f;white-space:break-spaces;font-size:0.8rem;color:#d70000;">
{$out['stdout']}
{$out['stderr']}
</pre>
{$code}
ENDOFERR;
		return $err;
	}
}
//---------- begin function evalPythonCode
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalPythonCode($lang,$evalcode){
	$lines=preg_split('/[\r\n]+/',$evalcode);
	//$_REQUEST['view']='howdy <b class="w_bigg">du</b>';
	$prespace='';
	foreach($lines as $line){
		if(strlen(trim($line)) && preg_match('/^([\s\t]+)/',$line,$m)){
			$prespace=$m[1];
			break;
		}
	}
	foreach($lines as &$line){
	//	$line=preg_replace('/^'.$prespace.'/','',rtrim($line));
	}
	$evalcode=implode(PHP_EOL,$lines);
	global $USER;
	global $CONFIG;
	global $PAGE;
	global $TEMPLATE;
	global $PASSTHRU;
	global $DATABASE;
	global $CRONTHRU;
	$CRONTHRU['pid']=getmypid();
	$wasqlTempPath=getWasqlTempPath();
	$wasqlTempPath=str_replace("\\","/",$wasqlTempPath);
	$wasqlPythonPath=getWasqlPath('python');
	$wasqlPythonPath=str_replace("\\","/",$wasqlPythonPath);
	$files=array(
		'main'=>"{$wasqlTempPath}/main_{$lang['evalcode_md5']}.py",
		'wasql'=>"{$wasqlTempPath}/wasql_{$lang['evalcode_md5']}.py",
	);
	$pagecode='';
	if(isset($CONFIG['includes'][$lang['ext']][0]) && is_file($CONFIG['includes'][$lang['ext']][0])){
		$files['include']=$CONFIG['includes'][$lang['ext']][0];
		$code=getFileContents($files['include']);
		$code=preg_replace('/^\<\?(py|python)/is','',rtrim($code));
		$code=preg_replace('/\?\>$/is','',ltrim($code));
		$code=trim($code);
		$files['page']="{$wasqlTempPath}/page_{$lang['evalcode_md5']}.py";
		if(!isset($CONFIG['includes_import_name'])){
			$CONFIG['includes_import_name']='page';
		}
		$pagecode="import page_{$lang['evalcode_md5']} as {$CONFIG['includes_import_name']}";
		$content=<<<ENDOFCONTENT
#! python

try:
	import wasql_{$lang['evalcode_md5']} as wasql
	import json
	import pprint
	import io
	import base64
	import common
	import requests
	from urllib.parse import urlparse, parse_qs, parse_qsl
	import config
	import db
	import re
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print(f"Import Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
    sys.exit(31)

{$code}
ENDOFCONTENT;
	setFileContents($files['page'],$content);
		//{$lang['evalcode_md5']}
	}
	//create a wasql.py file
	if(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
	}
	else{
		$db=array();
	}
	$removes=array('body','functions','controller','js','js_min','css','css_min');
	$p=$PAGE;
	foreach($removes as $fld){
		if(isset($p[$fld])){unset($p[$fld]);}
	}
	$t=$TEMPLATE;
	foreach($removes as $fld){
		if(isset($t[$fld])){unset($t[$fld]);}
	}
	if(!isset($_SESSION)){$_SESSION=[];}
	$wasql=array(
		'USER'=>"USER = ".json_encode(evalCleanupGlobal($USER),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'CONFIG'=>"CONFIG = ".json_encode(evalCleanupGlobal($CONFIG),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'PAGE'=>"PAGE = ".json_encode(evalCleanupGlobal($p),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'TEMPLATE'=>"TEMPLATE = ".json_encode(evalCleanupGlobal($t),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'PASSTHRU'=>"PASSTHRU = ".json_encode(evalCleanupGlobal($PASSTHRU),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'DATABASE'=>"DATABASE = ".json_encode(evalCleanupGlobal($db),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'REQUEST'=>"REQUEST = ".json_encode(evalCleanupGlobal($_REQUEST),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'SESSION'=>"SESSION = ".json_encode(evalCleanupGlobal($_SESSION),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'SERVER'=>"SERVER = ".json_encode(evalCleanupGlobal($_SERVER),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),
		'CRONTHRU'=>"CRONTHRU = ".json_encode(evalCleanupGlobal($CRONTHRU),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)
	);
	//add any additional globals
	if(isset($CONFIG['eval_globals'])){
		if(is_string($CONFIG['eval_globals'])){
			$CONFIG['eval_globals']=preg_split('/\,/',$CONFIG['eval_globals']);
		}
		foreach($CONFIG['eval_globals'] as $var){
			global $$var;
			$wasql[$var]="{$var} = ".json_encode(evalCleanupGlobal($$var),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
		}
	}
	$content=<<<ENDOFCONTENT
#! python

def user(k):
	{$wasql['USER']}
	if k in USER:
		return USER[k]
	else:
		return ""

def config(k):
	{$wasql['CONFIG']}
	if k in CONFIG:
		return CONFIG[k]
	else:
		return ""

def page(k):
	{$wasql['PAGE']}
	if k in PAGE:
		return PAGE[k]
	else:
		return ""

def template(k):
	{$wasql['TEMPLATE']}
	if k in TEMPLATE:
		return TEMPLATE[k]
	else:
		return ""

def passthru(k):
	{$wasql['PASSTHRU']}
	if k in PASSTHRU:
		return PASSTHRU[k]
	else:
		return ""

def database(k):
	{$wasql['DATABASE']}
	if k in DATABASE:
		return DATABASE[k]
	else:
		return ""

def request(k):
	{$wasql['REQUEST']}
	if k in REQUEST:
		return REQUEST[k]
	else:
		return ""

def session(k):
	{$wasql['SESSION']}
	if k in SESSION:
		return SESSION[k]
	else:
		return ""

def server(k):
	{$wasql['SERVER']}
	if k in SERVER:
		return SERVER[k]
	else:
		return ""

def cronthru(k):
	{$wasql['CRONTHRU']}
	if k in CRONTHRU:
		return CRONTHRU[k]
	else:
		return ""
ENDOFCONTENT;
	setFileContents($files['wasql'],$content);
	$content=<<<ENDOFCONTENT
#! python
import os
import sys
sys.path.append("{$wasqlPythonPath}")
sys.path.append("{$wasqlTempPath}")
try:
	import wasql_{$lang['evalcode_md5']} as wasql
	import json
	import pprint
	import io
	import base64
	import common
	import requests
	from urllib.parse import urlparse, parse_qs, parse_qsl
	import config
	import db
	import re
	{$pagecode}
except Exception as err:
	exc_type, exc_obj, exc_tb = sys.exc_info()
	fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
	print(f"Import Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
	sys.exit(32)

{$evalcode}
ENDOFCONTENT;
	setFileContents($files['main'],$content);
	$filename=getFileName($files['main']);
	$command = "{$lang['exe']} \"{$filename}\" 2>&1";
	//cmdResults($cmd,$args='',$dir='',$timeout=0)
	$out = cmdResults($command,'',$wasqlTempPath);
	//remove any py files in temp older than 1 day - wasqlTempPath
	$ok=cleanupDirectory($wasqlTempPath,1,'days','py');
	//check return code
	if(isNum($out['rtncode']) && $out['rtncode']==0){
		//remove the temp files on success
		foreach($files as $name=>$afile){
	 		unlink($afile);
	 	}
		if(isset($out['stdout']) && commonStrlen($out['stdout'])){
			return $out['stdout'];
		}
		if(isset($out['stderr']) && commonStrlen($out['stderr'])){
			return $out['stderr'];
		}
		return '';
	}	
	else{
		$code=commonShowCode($content);
		$err=<<<ENDOFERR
<div style="color:#d70000;">!! Embedded Python Script Error. Return Code: {$out['rtncode']} !!</div>
<div style="display:inline-flex;background:#333;color:#ccc;padding:5px 7px;border-radius:3px;font-size:0.9rem;">
	<div style="align-self:center;justify-self:center;margin-right:3px;">{$wasqlTempPath}&gt;</div>
	<div style="align-self:center;justify-self:center;color:#FFF;">{$command}</div>
</div>
<pre style="color:#5f5f5f;white-space:break-spaces;font-size:0.8rem;color:#d70000;">
{$out['stdout']}
{$out['stderr']}
</pre>
{$code}
ENDOFERR;
		return $err;
	}
}
//---------- begin function commonCertInfo
/**
* @describe queries a domains SSL or TLS cert and returns info like:
* issuer_country, issuer_state, issuer_city, issuer_name
* issue_date, expire_date, expire_in_days
* @param params host string - host/domain name to check
* @return array
* @usage
*	$info=commonCertInfo('google.com');
*	echo printValue($info);
*	-----------------------
*		"issuer_name": "Google Trust Services LLC",
*   	"issuer_country": "US",
*   	"issue_date": "2023-09-18",
*   	"expire_date": "2023-12-11",
*   	"expire_in_days": "70"
*/
function commonCertInfo($host,$field=''){
	$get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
	stream_context_set_option($get, 'ssl', 'verify_host', false);
	stream_context_set_option($get, 'ssl', 'verify_peer', false);
	stream_context_set_option($get, 'ssl', 'verify_peer_name', false);
	$wpath=getWasqlPath('php');
	$ca_info=array("{$wpath}/curl-ca-bundle.crt");
	$errno=0;
	$errstr='                                                                                                   '; 
	$read = stream_socket_client("ssl://".$host.":443", $errno, $errstr,60,STREAM_CLIENT_CONNECT,$get);
	if(!$read){
		//try reading TLS
		$read = stream_socket_client("tls://".$host.":443", $errno, $errstr,60,STREAM_CLIENT_CONNECT,$get);
	} 
	$cert = stream_context_get_params($read);
	$certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
	if(!is_array($certinfo)){return array();}
	$certinfo=array_change_key_case($certinfo);
	//check certificate chaining - https://www.php.net/manual/en/function.openssl-x509-checkpurpose.php
	$purpose=0;
	$x509_res = openssl_x509_read($cert['options']['ssl']['peer_certificate']);
	// checkpurpose returns false if either the purpose is invalid OR
    // the certificate is untrusted, so we should validate the
    // trust before we send back any errors.
	$certinfo['chain'] = openssl_x509_checkpurpose($x509_res,X509_PURPOSE_SSL_SERVER,$ca_info);
	//issuer_name
	if(isset($certinfo['issuer']['O'])){
		$certinfo['issuer_name']=$certinfo['issuer']['O'];
	}
	//issuer_country
	if(isset($certinfo['issuer']['C'])){
		$certinfo['issuer_country']=$certinfo['issuer']['C'];
	}
	//issuer_state
	if(isset($certinfo['issuer']['ST'])){
		$certinfo['issuer_state']=$certinfo['issuer']['ST'];
	}
	//issuer_city
	if(isset($certinfo['issuer']['L'])){
		$certinfo['issuer_city']=$certinfo['issuer']['L'];
	}
	//issuer_url
	if(isset($certinfo['issuer']['OU'])){
		$certinfo['issuer_url']=$certinfo['issuer']['OU'];
	}
	//issue_date
	if(isset($certinfo['validfrom_time_t'])){
		$certinfo['issue_date']=date('Y-m-d',$certinfo['validfrom_time_t']);
	}
	else{
		$certinfo['issue_date']='UNKNOWN';
	}
	//expire_date
	if(isset($certinfo['validto_time_t'])){
		$certinfo['expire_date']=date('Y-m-d',$certinfo['validto_time_t']);
		$earlier = new DateTime($certinfo['expire_date']);
		$later = new DateTime(date('Y-m-d'));
		$certinfo['expire_in_days'] = $later->diff($earlier)->format("%a"); //3
	}
	else{
		$certinfo['expire_date']='UNKNOWN';
	}
	if(strlen($field)){
		if(isset($certinfo[$field])){return $certinfo[$field];}
		return '';
	}
	return $certinfo;
}
function commonShowCode($code){
	$codelines=preg_split('/[\r\n]+/',$code);
	foreach($codelines as &$codeline){
		$codeline='<code>'.$codeline.'</code>';
	}
	$codelines=implode(PHP_EOL,$codelines);
	return <<<ENDOFCODE
<style>
	pre.viewfile{
		counter-reset: viewfilelinenumber;
		padding: 15px 0;
		font-size:0.7rem;
		color:#717b8a;
	}
	pre.viewfile code{
		counter-increment: viewfilelinenumber;
		display:inline-block;
		width: 100%;
		padding:2px 0;
	}
	pre.viewfile code:nth-child(even){
		background-color: #f0f0f0;
	}
	pre.viewfile code:before{
	    width:35px;
	    display: inline-block;
	    margin-right: 5px;
	    border-right: 1px solid #ccc;
	    content: counter(viewfilelinenumber);
	    color: #999;
	}
</style>
<pre class="viewfile" id="view_file">
{$codelines}
</pre>
ENDOFCODE;
}
//---------- begin function evalCleanupGlobal
/**
* @exclude  - this function is internal thus excluded from the manual
*/
function evalCleanupGlobal($arr){
	if(!is_array($arr) || count($arr)==0){return array('_isempty'=>1);}
	foreach($arr as $k=>$v){
		if(is_array($v)){
			if(!count($v)){
				unset($arr[$k]);
			}
		}
		elseif(!strlen($v) || $v=='null'){
			unset($arr[$k]);
		}
	}
	if(!is_array($arr) || count($arr)==0){return array('_isempty'=>1);}
	return $arr;
}
//---------- begin function commonGetLangInfo
/**
* @exclude  - this function is depreciated thus excluded from the manual
*/
function commonGetLangInfo($lang){
	switch(strtolower($lang)){
		case 'tcl':
			//Tcl
			$lang=array(
				'name'=>'Tcl',
				'comment'=>'#',
				'ext'=>'tcl',
				'exe'=>'tclsh',
				'shebang'=>'#!/usr/bin/env tclsh'
			);
		break;
		case 'rscript':
		case 'r':
			//R
			$lang=array(
				'name'=>'R',
				'comment'=>'#',
				'ext'=>'R',
				'exe'=>'rscript',
				'shebang'=>'#!/usr/bin/env Rscript'
			);
		break;
		case 'python':
		case 'py':
			//python
			$lang=array(
				'name'=>'python',
				'comment'=>'#',
				'ext'=>'py',
				'exe'=>'python3 -B',
				'shebang'=>'#!/usr/bin/env python'
			);
		break;
		case 'perl':
		case 'pl':
			//perl
			$lang=array(
				'name'=>'perl',
				'comment'=>'#',
				'ext'=>'pl',
				'exe'=>'perl',
				'shebang'=>'#!/usr/bin/env perl'
			);
		break;
		case 'ruby':
		case 'rb':
			//ruby
			$lang=array(
				'name'=>'ruby',
				'comment'=>'#',
				'ext'=>'rb',
				'exe'=>'ruby',
				'shebang'=>'#!/usr/bin/env ruby'
			);
			
		break;
		case 'node':
		case 'nodejs':
			//node
			$lang=array(
				'name'=>'nodejs',
				'comment'=>'//',
				'ext'=>'js',
				'exe'=>'node',
				'shebang'=>'#!/usr/bin/env node'
			);
		break;
		case 'lua':
			//lua
			$lang=array(
				'name'=>'lua',
				'comment'=>'--',
				'ext'=>'lua',
				'exe'=>'lua',
				'shebang'=>'#!/usr/bin/env lua'
			);
		break;
		case 'bash':
			//bash shell
			$lang=array(
				'name'=>'bash',
				'comment'=>'#',
				'ext'=>'sh',
				'exe'=>'bash',
				'shebang'=>'#!/usr/bin/env bash'
			);
		break;
		case 'sh':
			//bourne shell
			$lang=array(
				'name'=>'shell',
				'comment'=>'#',
				'ext'=>'sh',
				'exe'=>'sh',
				'shebang'=>'#!/usr/bin/env sh'
			);
		break;
		case 'vbscript':
		case 'vbs':
			//vbscript shell
			$lang=array(
				'name'=>'vbscript',
				'comment'=>"'",
				'ext'=>'vbs',
				'exe'=>'cscript //Nologo',
				'shebang'=>''
			);
		break;
		default:
			$lang=array();
		break;
	}
	return $lang;
}

//---------- begin function commonGetPrecode
/**
* @exclude  - this function is depreciated thus excluded from the manual
*/
function commonAddPrecode($lang,$evalcode){
	global $USER;
	global $CONFIG;
	global $PAGE;
	global $TEMPLATE;
	global $PASSTHRU;
	global $DATABASE;
	$precode=array();
	//$USER
	$tmp=commonGetPrecodeForVar($lang,$USER,'USER');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//$CONFIG
	$tmp=commonGetPrecodeForVar($lang,$CONFIG,'CONFIG');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//$PAGE
	$tmp=commonGetPrecodeForVar($lang,$PAGE,'PAGE');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//$TEMPLATE
	$tmp=commonGetPrecodeForVar($lang,$TEMPLATE,'TEMPLATE');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//$PASSTHRU
	$tmp=commonGetPrecodeForVar($lang,$PASSTHRU,'PASSTHRU');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//$DATABASE
	if(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
		$tmp=commonGetPrecodeForVar($lang,$db,'DATABASE');
		if(count($tmp)){$precode=array_merge($precode,$tmp);}	
	}
	//$_REQUEST
	$tmp=commonGetPrecodeForVar($lang,$_REQUEST,'REQUEST');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//$_SERVER
	$tmp=commonGetPrecodeForVar($lang,$_SERVER,'SERVER');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//$_SESSION
	$tmp=commonGetPrecodeForVar($lang,$_SESSION,'SESSION');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//$CRONTHRU
	$tmp=commonGetPrecodeForVar($lang,$CRONTHRU,'CRONTHRU');
	if(count($tmp)){$precode=array_merge($precode,$tmp);}
	//add precode to evalcode
	if(count($precode)){
		//add requires
		switch($lang['name']){
			case 'ruby':
				array_unshift($precode,"require 'json'");
				//comment header
				array_unshift($precode,"{$lang['comment']} WaSQL Variables");
			
			break;
			case 'lua':
				//https://github.com/rxi/json.lua
				array_unshift($precode,"json = require \"json\";");
				//comment header
				array_unshift($precode,"{$lang['comment']} WaSQL Variables");
			break;
			case 'py':
			case 'python':
				//comment header
				array_unshift($precode,"{$lang['comment']} WaSQL Variables");
				//append the python dir to sys.path and inport db
				$pypath=getWasqlPath('python');
				$pypath=str_replace("\\",'/',$pypath);
				array_unshift($precode,"");
				array_unshift($precode,"	sys.exit(err)");
				array_unshift($precode,"	print(\"Content-type: text/plain; charset=UTF-8;\\n\\n\")");
				array_unshift($precode,"except ImportError as err:");
				array_unshift($precode,"	import re");
				array_unshift($precode,"	import db");
				array_unshift($precode,"	import config");
				array_unshift($precode,"	from urllib.parse import urlparse, parse_qs, parse_qsl");
				array_unshift($precode,"	import requests");
				array_unshift($precode,"	import common");
				array_unshift($precode,"	import base64");
				array_unshift($precode,"	import io");
				array_unshift($precode,"	import pprint");
				array_unshift($precode,"	import json");
				array_unshift($precode,"try:");
				array_unshift($precode,"sys.path.append(\"{$pypath}\")");
				array_unshift($precode,"import sys");
				array_unshift($precode,"import os");
				array_unshift($precode,"{$lang['comment']} WaSQL Imports");
				
			break;
			default:
				//comment header
				array_unshift($precode,"{$lang['comment']} WaSQL Variables");
			break;
		}
		//comment footer
		$precode[]="{$lang['comment']} Code".PHP_EOL;
		
		//look for CONFIG['includes']
		if(isset($CONFIG['includes'][$lang['ext']][0])){
			$precode[]="{$lang['comment']} BEGIN CUSTOM INCLUDES";
			switch($lang['ext']){
				case 'py':
					foreach($CONFIG['includes'][$lang['ext']] as $afile){
						$name=getFileName($afile,1);
						$precode[]="import {$name} as page";
					}
				break;
				case 'pl':
					foreach($CONFIG['includes'][$lang['ext']] as $afile){
						$afile=str_replace("\\","/",$afile);
						$precode[]="require \"{$afile}\";";
					}
				break;
				case 'rb':
					foreach($CONFIG['includes'][$lang['ext']] as $afile){
						$name=getFileName($afile,1);
						$precode[]="require_relative '{$name}';";
					}
				break;
				case 'lua':
					foreach($CONFIG['includes'][$lang['ext']] as $afile){
						$name=getFileName($afile);
						$precode[]="dofile(\"./temp/{$name}\");";
					}
				break;
				case 'js':
					foreach($CONFIG['includes'][$lang['ext']] as $afile){
						$name=getFileName($afile);
						$precode[]="var {$PAGE['name']} = require(\"./temp/{$name}\");";
					}
				break;
			}
			$precode[]="{$lang['comment']} END CUSTOM INCLUDES";
		}
		//add precode to evalcode
		$evalcode=implode(PHP_EOL,$precode).PHP_EOL.PHP_EOL.$evalcode;
	}
	return $evalcode;
}
//---------- begin function commonGetPrecodeForVar
/**
* @exclude  - this function is depreciated thus excluded from the manual
*/
function commonGetPrecodeForVar($lang,$arr,$varname){
	$precode=array();
	$skips=array('HTTP_COOKIE','REMOTE_PORT','REQUEST_TIME','REQUEST_TIME_FLOAT','TIME_START','WASQLGUID','_adate','_adate_utime','_edate','_edate_utime','_tauth','_sessionid');
	if(!is_array($arr) || !count($arr)){return array();}
	foreach($arr as $k=>$v){
		//remove arrays and values with a slash
		if(is_array($v) || in_array($k,$skips) || stringContains($v,"\\") || !strlen($v) || isXML($v)){
			unset($arr[$k]);
			continue;
		}
		//skip xml or multiline values
		$lines=preg_split('/[\r\n]/',$v);
		if(count($lines) > 1){
			unset($arr[$k]);
			continue;
		}
	}
	//echo "commonGetPrecodeForVar for {$lang}".printValue($arr);exit;
	if(!count($arr)){return array();}
	switch(strtolower($lang['name'])){
		case 'python':
			$precode[]="{$varname} = ".json_encode($arr,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
		break;
		case 'perl':
			$precode[]="our %{$varname} = (";
			foreach($arr as $k=>$v){
				$precode[]="	'{$k}' => '{$v}',";
			}
			$precode[]=");";
		break;
		case 'ruby':
			$precode[]="{$varname} = {";
			foreach($arr as $k=>$v){
				$precode[]="	'{$k}' => '{$v}',";
			}
			$precode[]="}";
		break;
		case 'nodejs':
			$precode[]="const {$varname} = ".json_encode($arr,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE).";";
		break;
		case 'lua':
			$json=json_encode($arr,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_SLASHES);
			$json=str_replace('\\"','',$json);
			$precode[]="local {$varname} = json.decode('".$json."');";
		break;
		case 'vbscript':
			$precode[]="Dim {$varname} : Set {$varname} = CreateObject(\"Scripting.Dictionary\")";
			foreach($arr as $k=>$v){
				$precode[]="{$varname}.Add \"{$k}\", \"{$v}\"";
			}
		break;
		case 'bash':
		case 'shell':
			$precode[]="declare -A {$varname}";
			foreach($arr as $k=>$v){
				$precode[]="	{$varname}[{$k}]=\"{$v}\"";
			}
		break;
	}
	return $precode;
}
//---------- begin function insertPage
/**
* @exclude  - this function is depreciated thus excluded from the manual
*/
function insertPage(){
	return 'This SHOULD NOT BE HERE';
	}
//---------- begin function evalInsertPage
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalInsertPage($str=''){
	unset($evalmatches);
	unset($ex);
	preg_match_all('/(echo|return|\<\?|\<\?\=|\<\?\echo|\<\?\return)\s*insertPage(.+?)\;*\?*\>*/sm',$str,$evalmatches,PREG_PATTERN_ORDER);
	$cnt=count($evalmatches[2]);
	for($ex=0;$ex<$cnt;$ex++){
		$name=trim($evalmatches[2][$ex]);
		$name=preg_match('/^[\"\']+/','',$name);
		$name=preg_match('/[\"\']+$/','',$name);
		$recopts=array('-table'=>'_pages');
		if(isNum($name)){$recopts['_id']=$name;}
		else{$recopts['name']=$name;}
		$rec=getDBRecord($recopts);
		$val=trim($rec['body']);
		$str=str_replace($evalmatches[0][$ex],$val,$str);
		}
	return $str;
	}
//---------- begin function selectYears--------------------
/**
* @describe returns an array of years
* @param cnt integer - number of years to return - defaults to 10
* @param digits integer - 2 or 4 - defaults to 2
* @param backwards boolean - backwards or forwards - defaults to false
* @return array - array of years
* @usage $yrs=selectYears(10,4,0); - will return an array with current year and previous 9 years
*/
function selectYears($cnt=10,$digits=2,$backwards=0){
	$format=$digits==2?'y':'Y';
	$year=date($format);
	for($x=0;$x<$cnt;$x++){
    	$years[]=$backwards==0?(integer)$year+$x:(integer)$year-$x;
	}
	return $years;
}
//---------- begin function evalErrorWrapper
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function evalErrorWrapper($e=array(),$title='evalPHP Error!'){
	$wrap='<b style="border:1px solid #D50000;color:#000;background:#F4F400;padding:2px;">'.$title.'</b><br ><br >' . "\n";
	$wrap .="<div><b>Error:</b> {$e['message']}</div>\n";
	$wrap .="<div><b>File:</b> {$e['file']}</div>\n";
	$wrap .="<div><b>Line:</b> {$e['line']}</div>\n";
	$wrap .="<div><b>Type:</b> {$e['type']}</div>\n";
	return $wrap;
	}
//---------- begin function exportFile2Array
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function exportFile2Array($file=''){
	if(!is_file($file)){return null;}
	$data=getFileContents($file);
	$data=removeComments($data);
	$xml = new SimpleXmlElement($data);
	$rtn=array();
	foreach($xml->xmlschema as $schema){
		$info=parseSimpleAttributes($schema);
		$tablename=$info['name'];
		foreach($schema->field as $field){
			$info=parseSimpleAttributes($field);
			$fieldname=$info['name'];
			$rtn['xmlschema'][$tablename][$fieldname]=$info['type'];
        	}
    	}
    //xmlmeta
    foreach($xml->xmlmeta as $metas){
		$info=parseSimpleAttributes($metas);
		$tablename=$info['name'];
		$meta=array();
		foreach($metas as $field=>$val){
			if(isNum((string)$val)){$meta[$field]=(float)$val;}
			else{$meta[$field]=removeCdata((string)$val);}
        	}
        if(!is_array($rtn['xmlmeta'][$tablename])){$rtn['xmlmeta'][$tablename]=array();}
        array_push($rtn['xmlmeta'][$tablename],$meta);
    	}
    //xmldata
    foreach($xml->xmldata as $datas){
		$info=parseSimpleAttributes($datas);
		$tablename=$info['name'];
		$data=array();
		foreach($datas as $field=>$val){
			if(isNum((string)$val)){$data[$field]=(float)$val;}
			else{$data[$field]=removeCdata((string)$val);}
        	}
        if(!is_array($rtn['xmldata'][$tablename])){$rtn['xmldata'][$tablename]=array();}
        array_push($rtn['xmldata'][$tablename],$data);
    	}
    return $rtn;
	}
//---------- begin function fileExplorer
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function fileExplorer($startdir='',$param=array()){
	if(!strlen($startdir)){$startdir=$_SERVER['DOCUMENT_ROOT'];}
	if(!is_dir($startdir)){return "{$startdir} does not exist";}
	$rtn='';
	return $rtn;

}
//---------- begin function fileManager
/**
* @describe Web-based File Manager/Explorer application used in admin to browse, upload, and edit files on the server
* @param startdir string
*	starting directory - defaults to the document root
* @param params array
*	-rights=readonly,fileonly,all. Default is all. allow folder creation, file uploads, renames, and deletes
*	-perms=1 - shows permission column if set
*	-folder_name=all
*	-height=600
*	-icons=1  shows icons for known file extensions
*	-actions=download,edit,delete
*	-fields=name,size,modified,perms
*	-reorder=1 allows user to reorder files and folders by drag-n-drop
* @return
*	Web-based File Manager/Explorer application
* @usage fileManager('/var/www/shared/test');
*/
function fileManager($startdir='',$params=array()){
	global $CONFIG;
	if(!strlen($startdir)){
		$startdir=$CONFIG['filemanager_startdir'] ?? $CONFIG['filemanager_path'] ?? $_SERVER['DOCUMENT_ROOT'];
	}
	//$params['-reorder']=1;
	if(!is_dir($startdir)){return "{$startdir} does not exist";}
	loadExtrasJs('html5');
	foreach($CONFIG as $k=>$v){
		if(preg_match('/^filemanager_(.+)$/',$k,$m)){
			if(!isset($params[$m[1]])){$params[$m[1]]=$v;}
		}
	}
	global $PAGE;
	if(isset($params['rights'])){$params['-rights']=$params['rights'];}
	if(!isset($params['-rights'])){$params['-rights']='all';}
	if(isset($params['height'])){$params['-height']=$params['height'];}
	if(!isset($params['-height'])){$params['-height']=600;}
	if(isset($params['view'])){$params['-view']=$params['view'];}
	if(!isset($params['-view'])){$params['-view']='table';}
	if(!isset($params['-icons'])){$params['-icons']=1;}
	if(!isset($params['-actions'])){$params['-actions']='download,edit,delete';}
	if(!isset($params['-fields'])){$params['-fields']='name,size,modified,owner,perms';}
	$action=isset($params['-action'])?$params['-action']:"/{$PAGE['name']}";
	$params['-rights']=strtolower($params['-rights']);
	if(!isset($params['-onfinish'])){$params['-onfinish']='window.location=window.location';}
	//change to sub dir if requested
	$cdir=$startdir;
	if(isset($_REQUEST['_dir']) && stringContains(decodeBase64($_REQUEST['_dir']),$startdir)){
		$cdir =decodeBase64($_REQUEST['_dir']);
	}
	//pretable: check to see if cdir is browsable
	$pretable = '<div class="w_bigger" style="padding:20px 0;">';
	$relpath=str_replace($startdir,'',$cdir);
	$relpath=preg_replace('/^\/+/','',$relpath);
	if(strlen($relpath)){
		$pathparts=preg_split('/\/+/',$relpath);
		$rpath=$startdir;
		$rpathlinks=array();
		array_push($rpathlinks,'<a class="w_link w_bold w_lblue" href="'.$action.'?_menu=files&_dir='.encodeBase64($rpath).'">Root:</a>'."\n");
		foreach($pathparts as $pathpart){
			$rpath .= "/{$pathpart}";
			array_push($rpathlinks,'<a class="w_link w_bold w_lblue" href="'.$action.'?_menu=files&_dir='.encodeBase64($rpath).'">'.$pathpart.'</a>'."\n");
        }
		$pretable .= implode(' <img src="/wfiles/crumb.gif" alt="crumb" > ',$rpathlinks);
	}
	//Handle file uploads
	 if($params['-rights'] == 'all' && is_array($_FILES) && count($_FILES) > 0){
	 	processFileUploads($cdir);
	}
	//perform actions
	if(isset($_REQUEST['_newdir']) && $params['-rights'] == 'all'){
		$rs = @mkdir("{$cdir}/{$_REQUEST['_newdir']}", 0777);
		$rtn .= '<div style="display:none" id="newdir" result="'.$rs.'">'.$_REQUEST['_newdir'].'</div>'.PHP_EOL;
    }
    elseif(isset($_REQUEST['_rmdir']) && $params['-rights'] == 'all'){
		$ddir=decodeBase64($_REQUEST['_rmdir']);
		//for security purposes, only push file that are in document_root or the wasql path
		$wasqlpath=getWasqlPath();
		if(!isAdmin() && !stringContains($ddir,$_SERVER['DOCUMENT_ROOT']) && !stringContains($ddir,$wasqlpath)){
			echo "Error: denied delete request";
			exit;
		}
		$rs = @deleteDirectory($ddir);
		$rtn .= '<div style="display:none" id="rmdir" result="'.$rs.'">'.$ddir.'</div>'.PHP_EOL;
    }
    elseif(isset($_REQUEST['_rmfile']) && $params['-rights'] != 'readonly'){
		$ddir=decodeBase64($_REQUEST['_rmfile']);
		//for security purposes, only push file that are in document_root or the wasql path
		$wasqlpath=getWasqlPath();
		if(!isAdmin() && !stringContains($ddir,$_SERVER['DOCUMENT_ROOT']) && !stringContains($ddir,$wasqlpath)){
			echo "Error: denied delete request";
			exit;
		}
		$rs = @deleteDirectory($ddir);
    }
	//upload form
	$pretable .= '  <div style="display:flex;flex-wrap:wrap;justify-content:center;">'.PHP_EOL;
	//$rtn .= $action;
	$pretable .= '	<form name="_fmfile" method="POST" action="'.$action.'"  enctype="multipart/form-data">'.PHP_EOL;
	$pretable .= '		<input type="hidden" name="_menu" value="files">'.PHP_EOL;
	$pretable .= '		<input type="hidden" name="_dir" value="'.encodeBase64($cdir).'">'.PHP_EOL;
	$pretable .= '		<input type="hidden" name="file_path" value="/'.$relpath.'">'.PHP_EOL;
	if($params['-rights'] == 'all'){
		$pretable .= '	<div style="display:flex;flex-direction:column;margin-top:10px;">'.PHP_EOL;
		$pretable .= '			<label for="_newdir">New Directory Name</label>'.PHP_EOL;
		$pretable .= '			<input type="text" id="_newdir" class="w_form-control" style="max-width:500px;" name="_newdir" value="" >'.PHP_EOL;
		$pretable .= '	</div>'.PHP_EOL;
		}
	if($params['-rights'] != 'readonly'){
		$pretable .= '	<div style="display:flex;margin:10px 0;">'.PHP_EOL;
		$pretable .= '		<div style="margin-right:5px;">'.PHP_EOL;
		$fileparams=array('id'=>'file','value'=>'');
		if(isset($params['-accept'])){
			$fileparams['accept']=$params['-accept'];
		}
		if(isset($params['-capture'])){
			$fileparams['capture']=$params['-capture'];
		}
		if(isset($params['-multiple'])){
			$fileparams['multiple']=1;
		}
		$pretable .= 			buildFormFile('file',$fileparams);
		$pretable .= '		</div>'.PHP_EOL;
		$pretable .= '		<button type="submit" class="btn btn-primary">Upload</button>'.PHP_EOL;
		$pretable .= '	</div>'.PHP_EOL;
	}
	$pretable .= '	</form>'.PHP_EOL;
	if(!isMobileDevice() && $params['-rights'] != 'readonly'){
    	//HTML5 file upload
    	$path=encodeBase64($cdir);
		$pretable .= '<div  title="drag files to upload"';
		if(isset($params['-resize'])){
        	$pretable .= ' data-resize="'.$params['-resize'].'"'.PHP_EOL;
		}
		$pretable .= ' _onfinish="'.$params['-onfinish'].'" _action="/php/admin.php" style="padding: 10px 100px;margin-left:15px;border-radius:6px;align-self:center;display:inline-table;width:72px;" data-behavior="fileupload" path="'.$path.'" _menu="files" _dir="'.$path.'">'.PHP_EOL;
		$pretable .= '	<div align="center"><span class="icon-upload" style="font-size:50px;color:#CCC;"></span></div>'.PHP_EOL;
		$pretable .= '	<div class="align-center w_small w_nowrap" style="color:#ccc;">Drag-n-Drop</div>'.PHP_EOL;
		$pretable .= '</div>'.PHP_EOL;
	}
	if(isset($_REQUEST['file_error'])){
    	$pretable .= '<div class="w_danger icon-warning"> '.$_REQUEST['file_error'].'</div>'.PHP_EOL;
	}
	$pretable .= '</div>'.PHP_EOL;
	$pretable.='</div>'.PHP_EOL;
	if(isset($params['-reorder'])){
		$pretable.='<div class="reorder_message w_small w_gray" style="margin:0 0 10px 10px;">Note: Drag and Drop to Reorder Items</div>';
	}
	$pretable.='<div style="display:flex;flex-wrap:wrap;justify-content:center;padding-bottom:50px;">'.PHP_EOL;
	$posttable='</div>';
	$reorder_file="{$cdir}/.reorder.json";
	//get the files
	$params['type']='file,dir';
	$recs=getDirRecords($cdir,$params);
	$recs=sortArrayByKeys($recs,array('type'=>SORT_ASC,'name'=>SORT_ASC));
	if(!isset($params['-listview'])){
		if(isset($params['-reorder'])){
			if(!file_exists($reorder_file)){
				$ordermap=array();
				$i=0;
				foreach($recs as $rec){
					$i+=1;
					$ordermap[$rec['name']]=$i;
				}
				setFileContents($reorder_file,encodeJSON($ordermap));
			}
		}
		if(isset($params['-reorder']) && file_exists($reorder_file)){
			$cdirB64=encodeBase64($cdir);
			$params['-listview']=<<<ENDOFHTML
<div data-filename="[name]" data-dir="{$cdirB64}" data-action="{$action}" data-ondrop="wacss.filemanagerReorder" draggable="true" style="display:flex;flex-direction:column;padding:10px;box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;margin:0 25px 25px 0;border-radius:4px;width:150px;min-height:170px;background:#fffff9;">	
	<div style="display:flex;justify-content:space-between">
		<div class="w_small">[action_nav]</div>
		<div class="w_small">[action_del]</div>
	</div>
	<div style="flex:1;padding:10px;flex-direction:column;align-content:center;justify-content:center;">
		<div title="[name]" style="display:flex;justify-content:center;">[preview]</div>
		<div class="w_gray w_smallest align-center" style="margin-top:4px;overflow:hidden;white-space:nowrap;text-overflow: ellipsis;">[name]</div>
	</div>
	
	<div class="align-right w_smaller w_gray">[size_verbose]</div>
</div>
ENDOFHTML;
		}
		else{
			$params['-listview']=<<<ENDOFHTML
<div style="display:flex;flex-direction:column;padding:10px;box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;margin:0 25px 25px 0;border-radius:4px;width:150px;height:170px;background:#fffff9;">	
	<div style="display:flex;justify-content:space-between">
		<div class="w_small">[action_nav]</div>
		<div class="w_small">[action_del]</div>
	</div>
	<div style="flex:1;padding:10px;flex-direction:column;align-content:center;justify-content:center;">
		<div title="[name]" style="display:flex;justify-content:center;">[preview]</div>
		<div class="w_gray w_smallest align-center" style="margin-top:4px;overflow:hidden;white-space:nowrap;text-overflow: ellipsis;">[name]</div>
	</div>
	
	<div class="align-right w_smaller w_gray">[size_verbose]</div>
</div>
ENDOFHTML;
		}
	}
	//order the results
	if(isset($params['-reorder']) && file_exists($reorder_file)){
		$ordermap=decodeJSON(getFileContents($reorder_file));
		if(isset($_REQUEST['_reorder']) && isset($_REQUEST['_dragname']) && isset($_REQUEST['_dropname'])){
			//insert dragname just before dropname
			$newordermap=array();
			$i=0;
			foreach($ordermap as $name=>$id){
				if($name==$_REQUEST['_dragname']){continue;}
				$i+=1;
				if($name==$_REQUEST['_dropname']){
					$newordermap[$_REQUEST['_dragname']]=$i;
					$i+=1;
					$newordermap[$_REQUEST['_dropname']]=$i;
				}
				else{
					$newordermap[$name]=$i;
				}
			}
			$ordermap=$newordermap;
			setFileContents($reorder_file,encodeJSON($ordermap));
		}
		//{name}=>orderid
		foreach($recs as $i=>$rec){
			if(isset($ordermap[$rec['name']])){
				$recs[$i]['order']=$ordermap[$rec['name']];
			}
			else{
				$recs[$i]['order']=99999999;
			}
		}
		$recs=sortArrayByKeys($recs,array('type'=>SORT_ASC,'order'=>SORT_ASC,'name'=>SORT_ASC));
	}
	foreach($recs as $i=>$rec){
		$actions=array();
		$recs[$i]['ctype']=getFileContentType($recs[$i]['afile']);
		switch(strtolower($rec['type'])){
			case 'dir':
				$recs[$i]['icon_class']='icon-folder w_yellow w_bigger';
				$actions[]=$recs[$i]['action_nav']='<a title="Browse" style="margin-left:10px;text-decoration:none;" href="'.$action.'?_menu=files&_dir='.encodeBase64($rec['afile']).'"><span class="icon-arrow-right w_yellow"></span></a>';
				if($params['-rights'] == 'all'){
					$actions[]=$recs[$i]['action_del']='<a title="Remove" style="margin-left:10px;text-decoration:none;" href="'.$action.'?_menu=files&_dir='.encodeBase64($cdir).'&_rmdir='.encodeBase64($rec['afile']).'" onclick="return confirm(\'Remove this directory? ARE YOU SURE?\');"><span class="icon-erase w_red"></span></a>';
				}
				else{$recs[$i]['action_del']='';}
				$recs[$i]['preview']='<a title="Browse" style="margin-left:10px;text-decoration:none;" href="'.$action.'?_menu=files&_dir='.encodeBase64($rec['afile']).'"><span class="'.$recs[$i]['icon_class'].'" style="font-size:48px;"></span>';
			break;
			case 'file':
				if(stringContains($rec['afile'],$_SERVER['DOCUMENT_ROOT'])){
					$recs[$i]['wfile']=str_replace($_SERVER['DOCUMENT_ROOT'],'',$rec['afile']);
				}
				else{$recs[$i]['wfile']='';}
				$recs[$i]['icon_class']=commonGetFileIconClass($rec['afile']);
				$actions[]=$recs[$i]['action_nav']='<a title="Download" style="margin-left:10px;text-decoration:none;" href="'.$action.'?_pushfile='.encodeBase64($rec['afile']).'"><span class="icon-download"></span></a>';
				if(in_array($params['-rights'],array('all','fileonly'))){
					$actions[]=$recs[$i]['action_del']='<a title="Delete" style="margin-left:10px;text-decoration:none;" href="'.$action.'?_menu=files&_dir='.encodeBase64($cdir).'&_rmfile='.encodeBase64($rec['afile']).'" onclick="return confirm(\'Remove this file? ARE YOU SURE?\');"><span class="icon-erase w_red"></span></a>';
				}
				else{$recs[$i]['action_del']='';}
				if(isImageFile($recs[$i]['afile'])){
					if(isset($params['-listview'])){
						if(strlen($recs[$i]['wfile'])){
							$recs[$i]['preview']='<img onclick="wacss.showImage(this);" src="'.$recs[$i]['wfile'].'" style="cursor:pointer;max-width:100px;max-height:100px;">';
						}
						elseif($recs[$i]['size'] < 1000000){
							$recs[$i]['b64']=encodeBase64(getFileContents($recs[$i]['afile']));
							$recs[$i]['preview']='<img onclick="wacss.showImage(this);" src="data:'.$recs[$i]['ctype'].';base64,'.$recs[$i]['b64'].'" style="cursor:pointer;max-width:100px;max-height:100px;">';
						}
						else{
							$recs[$i]['preview']='<span class="'.$recs[$i]['icon_class'].'" style="font-size:48px;"></span>';
						}
					}
					elseif(strlen($recs[$i]['wfile'])){
						$recs[$i]['preview']='<span onclick="wacss.showImage(this);" data-src="'.$recs[$i]['wfile'].'" class="'.$recs[$i]['icon_class'].'" style="cursor:pointer;font-size:48px;"></span>';
					}
					else{
						$recs[$i]['preview']='<span class="'.$recs[$i]['icon_class'].'" style="font-size:48px;"></span>';
					}
				}
				elseif(isAudioFile($recs[$i]['afile'])){
					if(isset($params['-listview'])){
						if(strlen($recs[$i]['wfile'])){
							$recs[$i]['preview']='<audio width="140" controls><source src="'.$recs[$i]['wfile'].'" type="'.$recs[$i]['ctype'].'"></source></audio>';
						}
						elseif($recs[$i]['size'] < 1000000){
							$recs[$i]['b64']=encodeBase64(getFileContents($recs[$i]['afile']));
							$recs[$i]['preview']='<audio width="140" controls><source src="data:'.$recs[$i]['ctype'].';base64,'.$recs[$i]['b64'].'" type="'.$recs[$i]['ctype'].'"></source></audio>';
						}
						else{
							$recs[$i]['preview']='<span class="'.$recs[$i]['icon_class'].'" style="font-size:48px;"></span>';
						}
					}
					elseif(strlen($recs[$i]['wfile'])){
						$recs[$i]['preview']='<span onclick="wacss.showAudio(this);" data-src="'.$recs[$i]['wfile'].'" class="'.$recs[$i]['icon_class'].'" style="cursor:pointer;font-size:48px;"></span>';
					}
					else{
						$recs[$i]['preview']='<span class="'.$recs[$i]['icon_class'].'" style="font-size:48px;"></span>';
					}
				}
				elseif(isVideoFile($recs[$i]['afile'])){
					if(isset($params['-listview'])){
						if(strlen($recs[$i]['wfile'])){
							$recs[$i]['preview']='<video width="140" height="100" controls><source src="'.$recs[$i]['wfile'].'" type="'.$recs[$i]['ctype'].'"></source></video>';
						}
						elseif($recs[$i]['size'] < 1000000){
							$recs[$i]['b64']=encodeBase64(getFileContents($recs[$i]['afile']));
							$recs[$i]['preview']='<video width="140" height="100" controls><source src="data:'.$recs[$i]['ctype'].';base64,'.$recs[$i]['b64'].'" type="'.$recs[$i]['ctype'].'"></source></video>';
						}
						else{
							$recs[$i]['preview']='<span class="'.$recs[$i]['icon_class'].'" style="font-size:48px;"></span>';
						}
					}
					elseif(strlen($recs[$i]['wfile'])){
						$recs[$i]['preview']='<span onclick="wacss.showVideo(this);" data-src="'.$recs[$i]['wfile'].'" class="'.$recs[$i]['icon_class'].'" style="cursor:pointer;font-size:48px;"></span>';
					}
					else{
						$recs[$i]['preview']='<span class="'.$recs[$i]['icon_class'].'" style="font-size:48px;"></span>';
					}
				}
				else{
					$recs[$i]['preview']='<span class="'.$recs[$i]['icon_class'].'" style="font-size:48px;"></span>';
				}
			break;
		}
		//actions
		$recs[$i]['actions']=implode(PHP_EOL,$actions);
		$recs[$i]['name_actions']=<<<ENDOFHTML
<div style="display:flex;justify-content:space-between;">
	<div style="align-self:center;"><span class="{$recs[$i]['icon_class']}" style="margin-right:5px;"></span> {$rec['name']}</div>
	<div  style="align-self:center;display:flex;">{$recs[$i]['actions']}</div>
</div>
ENDOFHTML;
	}
	$name_action_displayname=<<<ENDOFHTML
<div style="display:flex;justify-content:space-between;">
	<div style="align-self:center;">Name</div>
	<div style="align-self:center;">Action(s)</div>
</div>
ENDOFHTML;
	//return printValue(array_keys($recs[0]));
	$listopts=array(
		'-list'=>$recs,
		'-pretable'=>$pretable,
		'-posttable'=>$posttable,
		'-tableclass'=>'table striped bordered',
		'-listfields'=>'name_actions,type,size_verbose,_cdate,_edate,_adate',
		'name_actions_options'=>array(
			'displayname'=>$name_action_displayname,
		),
		'type_options'=>array(
			'displayname'=>'Type',
			'class'=>'align-center'
		),
		'size_verbose_options'=>array(
			'displayname'=>'Size',
			'class'=>'align-right'
		),
		'_cdate_options'=>array(
			'displayname'=>'Created',
			'class'=>'w_small'
		),
		'_edate_options'=>array(
			'displayname'=>'Edited',
			'class'=>'w_small'
		),
		'_adate_options'=>array(
			'displayname'=>'Accessed',
			'class'=>'w_small'
		)
	);
	if(isset($params['-listview'])){
		$listopts['-listview']=$params['-listview'];
	}
	return databaseListRecords($listopts);
}
function commonGetFileIconClass($afile){
	$class=' icon-file-doc';
	$ext=getFileExtension($afile);
	if(isImageFile($afile)){$class=' icon-file-image w_blue';}
	elseif(isAudioFile($afile)){$class=' icon-file-audio w_orange';}
	elseif(isVideoFile($afile)){$class=' icon-file-video w_yellow';}
	else{
    	switch(strtolower($ext)){
        	case 'xls':
        	case 'xlsx':
        		$class=' icon-application-excel';
        	break;
        	case 'doc':
        	case 'docx':
        		$class=' icon-application-word';
        	break;
        	case 'ppt':
        	case 'pptx':
        		$class=' icon-application-powerpoint';
        	break;
        	case 'zip':
        	case 'gz':
        		$class=' icon-file-zip w_gray';
        	break;
        	case 'htm':
        	case 'html':
        		$class=' icon-html5';
        	break;
        	case 'sql':$class=' icon-sql w_teal';break;
        	case 'css':$class=' icon-css3';break;
        	case 'json':$class=' icon-json';break;
        	case 'pdf':$class=' icon-file-pdf2 w_red';break;
        	case 'js':$class=' icon-program-javascript';break;
        	case 'php':$class=' icon-program-php';break;
        	case 'py':$class=' icon-program-python';break;
        	case 'pl':$class=' icon-program-perl';break;
        	case 'lua':$class=' icon-program-lua';break;
        	case 'rb':$class=' icon-program-ruby';break;
		}
	}
	return $class;
}
function fileManagerOLD($startdir='',$params=array()){
	global $CONFIG;
	if(!strlen($startdir)){$startdir=$CONFIG['filemanager_startdir'] ?? $CONFIG['filemanager_path'] ?? $_SERVER['DOCUMENT_ROOT'];}
	if(!is_dir($startdir)){return "{$startdir} does not exist";}
	loadExtrasJs('html5');
	foreach($CONFIG as $k=>$v){
		if(preg_match('/^filemanager_(.+)$/',$k,$m)){
			$params[$m[1]]=$v;
		}
	}
	global $PAGE;
	if(isset($params['rights'])){$params['-rights']=$params['rights'];}
	if(!isset($params['-rights'])){$params['-rights']='all';}
	if(isset($params['height'])){$params['-height']=$params['height'];}
	if(!isset($params['-height'])){$params['-height']=600;}
	if(isset($params['view'])){$params['-view']=$params['view'];}
	if(!isset($params['-view'])){$params['-view']='table';}
	if(!isset($params['-icons'])){$params['-icons']=1;}
	if(!isset($params['-actions'])){$params['-actions']='download,edit,delete';}
	if(!isset($params['-fields'])){$params['-fields']='name,size,modified,owner,perms';}
	$action=isset($params['-action'])?$params['-action']:"/{$PAGE['name']}";
	$params['-rights']=strtolower($params['-rights']);

	$rtn='';
	//$rtn .= printValue($params);
	$progpath=dirname(__FILE__);
	//get wfiles path
	$iconpath=preg_replace('/php$/i','',$progpath) . "wfiles/icons/files";
	$iconpath=str_replace("\\","/",$iconpath);
	//change to sub dir if requested
	$cdir=$startdir;
	if(isset($_REQUEST['_dir']) && stringContains(decodeBase64($_REQUEST['_dir']),$startdir)){
		$cdir =decodeBase64($_REQUEST['_dir']);
	}
	//check to see if this current dir has override optrions
	$opts=array('rights','height','view','icons','actions','fields');
	foreach($opts as $opt){
		if(isset($params["{$cdir}_{$opt}"])){$param["-{$opt}"]=$params["{$cdir}_{$opt}"];}
	}

	$filemanager=array();
	$description=array();
	if(is_file("{$cdir}/filemanager.xml")){
		//load existing xml into and array to get file descriptions
		$xml=readXML("{$cdir}/filemanager.xml");
		if(isset($xml->items)){
			foreach($xml->items->item as $item){
				$crec=array();
				$crec['name']=(string)$item->name;
				$crec['description']=removeCdata((string)$item->description);
				$crec['name']=preg_replace('/^\/+/','',$crec['name']);
				$description[$crec['name']]=$crec['description'];
				array_push($filemanager,$crec);
	        	}
	        }
	    elseif(isset($xml->item)){
			foreach($xml->item as $item){
				$crec=array();
				$crec['name']=(string)$item->name;
				$crec['description']=removeCdata((string)$item->description);
				$crec['name']=preg_replace('/^\/+/','',$crec['name']);
				$description[$crec['name']]=$crec['description'];
				array_push($filemanager,$crec);
				}
	        }
		}
	//Handle file uploads
	 if($params['-rights'] == 'all' && is_array($_FILES) && count($_FILES) > 0){
	 	processFileUploads($cdir);
	 	//update the description XML in the current path
	 	if(isset($_REQUEST['file_path']) && isset($_REQUEST['file_size']) && isset($_REQUEST['file_type']) && isset($_REQUEST['description']) && strlen($_REQUEST['description'])){
			//check to see if it already exists
			$found=0;
			$_REQUEST['file']=preg_replace('/^\/+/','',$_REQUEST['file']);
			$cnt=count($filemanager);
			for($f=0;$f<$cnt;$f++){
				if($filemanager[$f]['name']==$_REQUEST['file']){
					$found++;
					$filemanager[$f]['name']=$_REQUEST['description'];
					$description['name']=$_REQUEST['description'];
                }
			}
			if($found==0){
				array_push($filemanager,array('name'=>$_REQUEST['file'],'description'=>$_REQUEST['description']));
				$description[$_REQUEST['file']]=$_REQUEST['description'];
			}
			$xml=array2XML($filemanager);
			$ok=setFileContents("{$cdir}/filemanager.xml",$xml);
        }
	}
	//check to see if cdir is browsable
	$relpath=str_replace($startdir,'',$cdir);
	$relpath=preg_replace('/^\/+/','',$relpath);
	if(strlen($relpath)){
		$pathparts=preg_split('/\/+/',$relpath);
		$rpath=$startdir;
		$rpathlinks=array();
		array_push($rpathlinks,'<a class="w_link w_bold w_lblue" href="'.$action.'?_menu=files&_dir='.encodeBase64($rpath).'">Root:</a>'."\n");
		foreach($pathparts as $pathpart){
			$rpath .= "/{$pathpart}";
			array_push($rpathlinks,'<a class="w_link w_bold w_lblue" href="'.$action.'?_menu=files&_dir='.encodeBase64($rpath).'">'.$pathpart.'</a>'."\n");
        }
		$rtn .= '<div class="w_bigger">'.implode(' <img src="/wfiles/crumb.gif" alt="crumb" > ',$rpathlinks).'</div>'.PHP_EOL;
	}
	//perform actions
	if(isset($_REQUEST['_newdir']) && $params['-rights'] == 'all'){
		$rs = @mkdir("{$cdir}/{$_REQUEST['_newdir']}", 0777);
		$rtn .= '<div style="display:none" id="newdir" result="'.$rs.'">'.$_REQUEST['_newdir'].'</div>'.PHP_EOL;
		if(isset($_REQUEST['description'])){
			array_push($filemanager,array('name'=>$_REQUEST['_newdir'],'description'=>$_REQUEST['description']));
			$description[$_REQUEST['_newdir']]=$_REQUEST['description'];
			$xml=array2XML($filemanager);
			$ok=setFileContents("{$cdir}/filemanager.xml",$xml);
		}
    }
    elseif(isset($_REQUEST['_rmdir']) && $params['-rights'] == 'all'){
		$ddir=decodeBase64($_REQUEST['_rmdir']);
		//for security purposes, only push file that are in document_root or the wasql path
		$wasqlpath=getWasqlPath();
		if(!stringContains($ddir,$_SERVER['DOCUMENT_ROOT']) && !stringContains($ddir,$wasqlpath)){
			echo "Error: denied delete request";
			exit;
		}
		$rs = @deleteDirectory($ddir);
		$rtn .= '<div style="display:none" id="rmdir" result="'.$rs.'">'.$ddir.'</div>'.PHP_EOL;
		//Remove from xml
		$found=0;
		$dname=getFileName($ddir);
		$cnt=count($filemanager);
		for($f=0;$f<$cnt;$f++){
			if($filemanager[$f]['name']==$dname){
				$found++;
				unset($filemanager[$f]);
            }
		}
		if($found>0){
			$xml=array2XML($filemanager);
			$ok=setFileContents("{$cdir}/filemanager.xml",$xml);
		}
    }
    elseif(isset($_REQUEST['_rmfile']) && $params['-rights'] != 'readonly'){
		$ddir=decodeBase64($_REQUEST['_rmfile']);
		//for security purposes, only push file that are in document_root or the wasql path
		$wasqlpath=getWasqlPath();
		if(!stringContains($ddir,$_SERVER['DOCUMENT_ROOT']) && !stringContains($ddir,$wasqlpath)){
			echo "Error: denied delete request";
			exit;
		}
		$rs = @deleteDirectory($ddir);
		//Remove from xml
		$found=0;
		$dname=getFileName($ddir);
		$cnt=count($filemanager);
		for($f=0;$f<$cnt;$f++){
			if($filemanager[$f]['name']==$dname){
				$found++;
				unset($filemanager[$f]);
                }
			}
		if($found>0){
			$xml=array2XML($filemanager);
			$ok=setFileContents("{$cdir}/filemanager.xml",$xml);
			}
    	}
    elseif(isset($_REQUEST['_edit']) && $params['-rights'] != 'readonly'){
		$oldname=decodeBase64($_REQUEST['_edit']);
		$newname=$_REQUEST['file_name'];
		$ok=@rename("{$cdir}/{$oldname}","{$cdir}/{$newname}");
		//Remove from xml
		$found=0;
		$dname=getFileName($ddir);
		$cnt=count($filemanager);
		for($f=0;$f<$cnt;$f++){
			if($filemanager[$f]['name']==$oldname){
				$found++;
				unset($filemanager[$f]);
                }
			}
		array_push($filemanager,array('name'=>$_REQUEST['file_name'],'description'=>$_REQUEST['file_desc']));
		$description[$_REQUEST['file_name']]=$_REQUEST['file_desc'];
		$xml=array2XML($filemanager);
		$ok=setFileContents("{$cdir}/filemanager.xml",$xml);

    	}
	$files=listFiles($cdir);
	$rtn .= '  <div style="display:flex;flex-wrap:wrap;">'.PHP_EOL;
	//$rtn .= $action;
	$rtn .= '	<form name="_fmfile" method="POST" action="'.$action.'"  enctype="multipart/form-data">'.PHP_EOL;
	$rtn .= '		<input type="hidden" name="_menu" value="files">'.PHP_EOL;
	$rtn .= '		<input type="hidden" name="_dir" value="'.encodeBase64($cdir).'">'.PHP_EOL;
	$rtn .= '		<input type="hidden" name="file_path" value="/'.$relpath.'">'.PHP_EOL;
	if($params['-rights'] == 'all'){
		$rtn .= '	<div style="display:flex;flex-direction:column;margin-top:10px;">'.PHP_EOL;
		$rtn .= '			<label for="_newdir">New Directory Name</label>'.PHP_EOL;
		$rtn .= '			<input type="text" id="_newdir" class="w_form-control" style="max-width:500px;" name="_newdir" value="" >'.PHP_EOL;
		$rtn .= '	</div>'.PHP_EOL;
		}
	if($params['-rights'] != 'readonly'){
		$rtn .= '	<div style="display:flex;margin:10px 0;">'.PHP_EOL;
		$rtn .= '		<div style="margin-right:5px;">'.PHP_EOL;
		$fileparams=array('id'=>'file','value'=>'');
		if(isset($params['-accept'])){
			$fileparams['accept']=$params['-accept'];
		}
		if(isset($params['-capture'])){
			$fileparams['capture']=$params['-capture'];
		}
		if(isset($params['-multiple'])){
			$fileparams['multiple']=1;
		}
		$rtn .= 			buildFormFile('file',$fileparams);
		$rtn .= '		</div>'.PHP_EOL;
		$rtn .= '		<button type="submit" class="btn btn-primary">Upload</button>'.PHP_EOL;
		$rtn .= '	</div>'.PHP_EOL;
	}
	$rtn .= '	</form>'.PHP_EOL;
	if(!isMobileDevice() && $params['-rights'] != 'readonly'){
    	//HTML5 file upload
    	$path=encodeBase64($cdir);
		$rtn .= '<div  title="drag files to upload"';
		if(isset($params['-resize'])){
        	$rtn .= ' data-resize="'.$params['-resize'].'"'.PHP_EOL;
		}
		$rtn .= ' _onfinish="'.$params['-onfinish'].'" _action="/php/admin.php" style="padding: 10px 75px;margin-left:15px;border-radius:6px;align-self:center;display:inline-table;width:50px;" data-behavior="fileupload" path="'.$path.'" _menu="files" _dir=="'.$path.'">'.PHP_EOL;
		$rtn .= '	<div align="center"><span class="icon-upload" style="font-size:50px;color:#CCC;"></span></div>'.PHP_EOL;
		$rtn .= '	<div class="align-center w_small w_nowrap" style="color:#ccc;">Drag-n-Drop</div>'.PHP_EOL;
		$rtn .= '</div>'.PHP_EOL;
	}
	if(isset($_REQUEST['file_error'])){
    	$rtn .= '<div class="w_danger icon-warning"> '.$_REQUEST['file_error'].'</div>'.PHP_EOL;
	}
	$rtn .= '</div>'.PHP_EOL;
	if(!isset($params['-onfinish'])){$params['-onfinish']='window.location=window.location;';}
	if(count($files)==0){
		return $rtn;
	}
	sort($files);
	if(isNum($params['-height'])){
		$rtn .= '<div style="height:'.$params['-height'].'px;overflow:auto;padding-right:18px;">'.PHP_EOL;
		}
	else{
		$rtn .= '<div>'.PHP_EOL;
    	}
	$fields=preg_split('/\,/',$params['-fields']);
	$rtn .= '<table class="table table-striped table-bordered">'.PHP_EOL;
	if($params['-view']=='table'){
		$rtn .= '	<tr>'.PHP_EOL;
		foreach($fields as $field){
        	$title=ucfirst($field);
        	$rtn .= '		<th>'.$title.'</th>'.PHP_EOL;
		}
		$rtn .= '		<th>Actions</th>'.PHP_EOL;
		$rtn .= '	</tr>'.PHP_EOL;
	}
	$row=0;
	foreach($files as $file){
		if(preg_match('/^\./',$file)){continue;}
		if(preg_match('/^(filemanager\.xml|thumbs\.db)$/i',$file)){continue;}
		$afile=$cdir . "/{$file}";
		$fileId='f' . encodeCRC($afile);
		$stat = @stat($afile);
		$perms = sprintf("%o", ($stat['mode'] & 000777));
		$uid=$stat['uid'];
		$gid=$stat['gid'];
		if(function_exists('posix_getpwuid')){
        	$p=@posix_getpwuid($stat['uid']);
        	$uid=$p['name'];
		}
		if(function_exists('posix_getgrgid')){
        	$p=@posix_getgrgid($stat['gid']);
        	$gid=$p['name'];
		}
		$owner="{$uid}:{$gid}";
		//if(is_link($afile)){continue;}
		if(is_dir($afile)){
			if(preg_match('/^(Maildir|Logs|wfiles|php|min|cgi\-bin)$/i',$file)){continue;}
			$row++;
			$rtn .= '	<tr align="right" valign="top">'.PHP_EOL;
			$cspan=count($fields)-2;
			$rtn .= '		<td class="w_align_left w_nowrap" colspan="'.$cspan.'"><a class="w_link w_block icon-folder w_bigger" href="'.$action.'?_menu=files&_dir='.encodeBase64($afile).'"> '.$file.'</a></td>'.PHP_EOL;
			//owner
			$rtn .= '		<td align="right">'.$owner.'</td>'.PHP_EOL;
			//PERMS
			$rtn .= '		<td align="right">'.$perms.'</td>'.PHP_EOL;
			//actions
			$rtn .= '		<td class="nowrap w_flexgroup">'.PHP_EOL;
			if($params['-rights'] == 'all'){
				// $rtn .= '			<a title="Edit" style="margin:0 4px 4px 0;" alt="Edit Filename and description" class="w_link w_bigger icon-edit w_grey" href="#" onclick="return filemanagerEdit(\''.$fileId.'\',\''.$action.'\',{_menu:\'files\',_edit:\''.encodeBase64($file).'\',_dir:\''.encodeBase64($cdir).'\'});"></a>'.PHP_EOL;
				$rtn .= '			<a title="Delete" style="margin:0 10px 4px 0;" alt="Delete Folder" class="w_link w_bigger icon-cancel w_danger" href="'.$action.'?_menu=files&_rmdir='.encodeBase64($afile).'&_dir='.encodeBase64($cdir).'" onclick="return confirm(\'Delete Directory: '.$file.'? Click OK to confirm.\');"></a>'.PHP_EOL;
			}
			$rtn .= '			<a title="Browse" style="margin:0 4px 10px 0;" alt="Browse Folder" class="w_link w_bigger icon-folder" href="/'.$PAGE['name'].'?_menu=files&_dir='.encodeBase64($afile).'"></a>'.PHP_EOL;
			$rtn .= '		</td>'.PHP_EOL;
			$rtn .= '	</tr>'.PHP_EOL;
	    }
	    else{
			$row++;
			$rtn .= '	<tr align="right" valign="top" data-fields="['.implode('],[',$fields).']">'.PHP_EOL;
			foreach($fields as $field){
            	switch(strtolower($field)){
                	case 'name':
                	case 'filename':
                		$class=commonGetFileIconClass($afile);
						$previewlink='/'.$PAGE['name'].'?_pushfile='.encodeBase64($afile);
						$display=preg_replace('/\_/',' ',$file);
						if(isWebImage($file)){
							//show preview on mouse over for web images
							$rtn .= '		<td class="w_align_left w_nowrap"><a class="w_link w_bigger'.$class.'" onclick="wacss.showImage(this,10020,this.dataset.name);return false;" href="'.$previewlink.'" title="click to preview" data-name="'.$file.'"> '.$file.'</a></td>'.PHP_EOL;
			            }
			            elseif(isAudioFile($file)){
							//show preview on mouse over for web images
							$rtn .= '		<td class="w_align_left w_nowrap"><a class="w_link w_bigger'.$class.'" onclick="wacss.showAudio(this,10020,this.dataset.name);return false;" href="'.$previewlink.'" title="click to listen" data-name="'.$file.'"> '.$file.'</a></td>'.PHP_EOL;
			            }
			            elseif(isVideoFile($file)){
							//show preview on mouse over for web images
							$rtn .= '		<td class="w_align_left w_nowrap"><a class="w_link w_bigger'.$class.'" onclick="wacss.showVideo(this,10020,this.dataset.name);return false;" href="'.$previewlink.'" title="click to watch" data-name="'.$file.'"> '.$file.'</a></td>'.PHP_EOL;
			            }
			            else{
							$rtn .= '		<td class="w_align_left w_nowrap"><a class="w_link w_bigger'.$class.'" href="'.$previewlink.'&-attach=0"> '.$display.'</a></td>'.PHP_EOL;
			            }
                	break;
                	case 'mtime':
                	case 'modified':
                		$rtn .= '		<td align="right"  data-field="mtime" class="w_nowrap">'.date('m/d/y',$stat['mtime']).'</td>'.PHP_EOL;
                	break;
                	case 'desc':
                	case 'description':
                		$rtn .= '		<td align="right">'.$description[$file].'</td>'.PHP_EOL;
                	break;
                	case 'owner':
                	case 'group':
                		$rtn .= '		<td align="right">'.$owner.'</td>'.PHP_EOL;
                	break;
                	case 'perm':
                	case 'perms':
                	case 'rights':
                		$rtn .= '		<td align="right">'.$perms.'</td>'.PHP_EOL;
                		break;
                	case 'size':
                		//size
						$size=filesize($afile);
						$vsize=verboseSize($size);
						$rtn .= '		<td align="right" style="padding-left:5px;" class="w_nowrap">'.$vsize.'</td>'.PHP_EOL;
                		break;
				}
			}
			//actions
			$rtn .= '		<td align="right" valign="middle" class="w_nowrap w_flexgroup">'.PHP_EOL;
			//download
			$rtn .= '		<a title="Download" style="margin:0 10px 4px 0;" alt="Download" class="w_link w_bigger icon-download w_success" href="'.$previewlink.'"></a>'.PHP_EOL;
			if($params['-rights'] != 'readonly'){
				// $rtn .= '			<a title="Edit" style="margin:0 4px 4px 0;" alt="Edit Filename and description" class="w_link w_bigger icon-edit w_grey" href="#" onclick="return filemanagerEdit(\''.$fileId.'\',\''.$action.'\',{_menu:\'files\',_edit:\''.encodeBase64($file).'\',_dir:\''.encodeBase64($cdir).'\'});"></a>'.PHP_EOL;
				$rtn .= '			<a title="Delete" style="margin:0 10px 4px 0;" alt="Delete File" class="w_link w_bigger icon-cancel w_danger" href="'.$action.'?_menu=files&_rmfile='.encodeBase64($afile).'&_dir='.encodeBase64($cdir).'" onclick="return confirm(\'Delete File: '.$file.'? Click OK to confirm.\');"></a>'.PHP_EOL;
				}
			$rtn .= '		</td>'.PHP_EOL;
			}
		$rtn .= '	</tr>'.PHP_EOL;
		}
	$rtn .= '</table>'.PHP_EOL;
	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
}
//---------- begin function fileManagerV2--------------------
/**
* @describe interactive file manager - will replace filemanager in the future. requires html5 js to be loaded
* @param path string - root path
* @return params - array
* -root str - root path 
* [data-] str - adds data atributes
* @usage return fileManagerV2($path,$params)
*/
function fileManagerV2($path='',$params=array()){
	global $CONFIG;
	if(!strlen($path)){$path=$CONFIG['filemanager_startdir'] ?? $CONFIG['filemanager_path'] ?? $_SERVER['DOCUMENT_ROOT'];}
	if(!is_dir($path)){return "{$path} does not exist";}
	loadExtrasJs('html5');
	global $PAGE;
	if(isset($_REQUEST['path'])){
		$path=base64_decode($_REQUEST['path']);
	}
	elseif(isset($_REQUEST['rmdir'])){
		$path=base64_decode($_REQUEST['rmdir']);
		$ok=deleteDirectory($path);
		$path=getFilePath($path);
	}
	elseif(isset($_REQUEST['rm'])){
		$afile=base64_decode($_REQUEST['rm']);
		$ok=unlink($afile);
		$path=getFilePath($afile);
	}
	elseif(isset($_REQUEST['add'])){
		$path=base64_decode($_REQUEST['add']);
		$name=$_REQUEST['prompt'];
		$newdir="{$path}/{$name}";
		mkdir($newdir,0777,1);
	}
	$files=listFilesEx($path,array(
		'type'=>'all'
	));
	if(!isset($params['-action'])){
		$params['-action']='/t/1/'.$PAGE['name'];
	}
	//set icon and actions
	$docroot=$_SERVER['DOCUMENT_ROOT'];
	foreach($files as &$file){
		$actions=[];
		$afile_b64=base64_encode($file['afile']);
		switch(strtolower($file['type'])){
			case 'dir':
				$file['type']='<span title="browse" class="icon-folder w_bigger w_yellow w_pointer" onclick="return wacss.nav(this);" data-path="'.$afile_b64.'"></span> dir';
				$actions[]='<a href="#" style="display:inline-block;" onclick="return wacss.nav(this);" data-path="'.$afile_b64.'"><span class="icon-forward w_bigger w_yellow" title="browse"></span></a>';
				$actions[]='<a href="#" style="display:inline-block;" onclick="return wacss.nav(this);" data-rmdir="'.$afile_b64.'" data-confirm="Delete this folder? ARE YOU SURE?"><span class="icon-erase w_bigger w_danger" title="rmdir"></span></a>';
			break;
			case 'file':
				$actions[]='<a href="/php/index.php?-attach=1&_pushfile='.$afile_b64.'" style="display:inline-block;"><span class="icon-download w_bigger w_blue" title="download"></span></a>';
				$actions[]='<a href="#" style="display:inline-block;" onclick="return wacss.nav(this);" data-rm="'.$afile_b64.'" data-confirm="Delete this file? ARE YOU SURE?"><span class="icon-erase w_bigger w_danger" title="rm"></span></a>';
				$icon='';
				switch(strtolower($file['ext'])){
					default:
						$icon='<span title="text" class="icon-file-txt"></span> ';
					break;
					case 'pdf':
						$icon='<span title="Adobe Acrobat" class="brand-adobeacrobatreader"></span> ';
					break;
					case 'ai':
						$icon='<span title="Adobe Illustrator" class="brand-adobeillustrator"></span> ';
					break;
					case 'ps':
						$icon='<span title="Adobe Photoshop" class="brand-adobephotoshop"></span> ';
					break;
					case 'pl':
						$icon='<span title="Perl" class="icon-program-perl"></span> ';
					break;
					case 'php':
						$icon='<span title="PHP" class="icon-program-php"></span> ';
					break;
					case 'eml':
						$icon='<span title="email" class="icon-mail w_red"></span> ';
					break;
					case 'js':
						$icon='<span title="javascript" class="icon-program-javascript"></span> ';
					break;
					case 'py':
						$icon='<span title="Python" class="icon-program-python"></span> ';
					break;
					case 'htm':
					case 'html':
						$icon='<span title="HTML" class="icon-html5"></span> ';
					break;
					case 'css':
						$icon='<span title="CSS" class="icon-css3"></span> ';
					break;
					case 'zip':
					case '7z':
						$icon='<span title="compressed" class="icon-file-zip"></span> ';
					break;
					case 'gif':
					case 'jpg':
					case 'jpeg':
					case 'webp':
					case 'jfif':
					case 'png':
					case 'ico':
					case 'svg':
						$icon='<span title="image" class="icon-file-image w_info"></span> ';
					break;
					case 'mp3':
					case 'wav':
					case 'mid':
						$icon='<span title="audio" class="icon-file-audio w_orange"></span> ';
					break;
					case 'mp4':
					case 'mpeg':
					case 'mov':
						$icon='<span title="video" class="icon-file-video w_blue"></span> ';
					break;
					case 'xls':
					case 'xlsx':
						$icon='<span title="excel" class="icon-application-excel"></span> ';
					break;
					case 'ppt':
					case 'pptx':
						$icon='<span title="powerpoint" class="icon-application-powerpoint"></span> ';
					break;
					case 'doc':
					case 'docx':
						$icon='<span title="word" class="icon-application-word"></span> ';
					break;


				}
				$file['type']=$icon.' file';
			break;
		}
		$file['actions']='<div style="display:flex;justify-content:space-between">'.implode(' ',$actions).'</div>';
	}

	//Name	Size	Modified	Owner	Perms	Actions
	$pretables=[];
	$pretables[]='<div style="display:flex;margin:5px 0 2px 0;">';
	//add folder
	$pnav='<div style="margin-right:20px;padding:2px 7px;background:#ffc107;border-radius:3px;" class="w_pointer w_link" onclick="return wacss.nav(this);"';
	foreach($params as $k=>$v){
		if(stringBeginsWith($k,'data-')){
			$pnav.=" {$k}=\"{$v}\"";
		}
	}
	$pnav.=' data-prompt="New Folder Name:" data-add="'.base64_encode($path).'"><span class="icon-folder-add" style="margin-right:3px;"></span> add</div>';
	$pretables[]=$pnav;
	if($params['-root'] == $path){
		$pretables[]='<div style="align-self:center;" class="w_small w_gray">'.$path.'</div>';
	}
	else{
		$root=$params['-root'];
		$navstr=str_replace($root,'',$path);
		$navstr=preg_replace('/^[\/]/','',$navstr);
		$parts=preg_split('/\//',$navstr);
		$nav=array();
		$pnav='<span class="w_pointer w_link" onclick="return wacss.nav(this);"';
		foreach($params as $k=>$v){
			if(stringBeginsWith($k,'data-')){
				$pnav.=" {$k}=\"{$v}\"";
			}
		}
		$pnav.=' data-path="'.base64_encode($root).'">'.$root.'</span>';
		$nav[]=$pnav;
		
		foreach($parts as $part){
			$root.="/{$part}";
			$pnav='<span class="w_pointer w_link" onclick="return wacss.nav(this);"';
			foreach($params as $k=>$v){
				if(stringBeginsWith($k,'data-')){
					$pnav.=" {$k}=\"{$v}\"";
				}
			}
			$pnav.=' data-path="'.base64_encode($root).'">/'.$part.'</span>';
			$nav[]=$pnav;
		}
		$path=implode('',$nav);
		$pretables[]='<div style="align-self:center;" class="w_gray">'.$path.'</div>';
	}
	$pretables[]='</div>';
	$pretable=implode(PHP_EOL,$pretables);
	//drag and drop for file upload
	$dragpath=base64_encode($path);
	$drag .= '<div id="filemanager_drag" title="Drag n Drop files to upload"';
	foreach($params as $k=>$v){
		if(stringBeginsWith($k,'data-')){
			$drag.=" {$k}=\"{$v}\"";
		}
	}
	$drag .= ' _onfinish="wacss.nav(document.querySelector(\'#filemanager_drag\'));" _action="/php/admin.php" style="margin:5px 0 20px 0;border:1px dashed #ccc;border-radius:30%;padding:10px;display:inline-table;width:350px;" data-behavior="fileupload" data-path="'.$dragpath.'" path="'.$dragpath.'" _menu="files" _dir="'.$dragpath.'">'.PHP_EOL;
	$drag .= '	<div align="center"><span class="icon-download" style="font-size:50px;color:#CCC;"></span><div style="color:#ccc;font-size:0.8rem;">drag n drop</div></div>'.PHP_EOL;
	$drag .= '</div>'.PHP_EOL;
	
	$opts=array(
		'-tableclass'=>'table is-striped is-bordered is-narrow collapsed',
		'-listfields'=>'name,type,size_verbose,actions',
		'-tableheight'=>'70vh',
		'-hidesearch'=>1,
		'-pretable'=>$pretable,
		'-posttable'=>$drag,
		'size_verbose_displayname'=>'Size',
		'size_verbose_class'=>'align-right',
		'actions_class'=>'align-right'
	);
	foreach($params as $k=>$v){
		if(stringBeginsWith($k,'data-')){
			$k="-tr_{$k}";
		}
		$opts[$k]=$v;
	}
	$opts['-list']=$files;
	return databaseListRecords($opts);	
}
//---------- begin function fileStat--------------------
/**
* @describe returns an array of file stats for file specified
* @param file string - file to check
* @return array - array of file stats for file specified
* @usage $stat=fileStat($afile);
*/
function fileStat($file) {
	clearstatcache();
	$ss=@stat($file);
 	if(!$ss) return false; //Couldnt stat file
	$ts=array(
  		0140000=>'ssocket',
  		0120000=>'llink',
  		0100000=>'-file',
  		0060000=>'bblock',
  		0040000=>'ddir',
  		0020000=>'cchar',
  		0010000=>'pfifo'
 		);
 	$p=$ss['mode'];
 	$t=decoct($ss['mode'] & 0170000); // File Encoding Bit
 	$str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)][0]:'u';
 	$str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
 	$str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
 	$str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
 	$str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
 	$str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
 	$str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
 	$s=array(
 		'perms'=>array(
	  		'umask'=>sprintf("%04o",@umask()),
	  		'human'=>$str,
	  		'octal1'=>sprintf("%o", ($ss['mode'] & 000777)),
	  		'octal2'=>sprintf("0%o", 0777 & $p),
	  		'decimal'=>sprintf("%04o", $p),
	  		'fileperms'=>@fileperms($file),
	  		'mode1'=>$p,
	  		'mode2'=>$ss['mode']),
 		'owner'=>array(
  			'fileowner'=>$ss['uid'],
  			'filegroup'=>$ss['gid'],
  			'owner'=>(function_exists('posix_getpwuid'))?@posix_getpwuid($ss['uid']):'',
  			'group'=>(function_exists('posix_getgrgid'))?@posix_getgrgid($ss['gid']):''
  			),
 		'file'=>array(
  			'filename'=>$file,
  			'realpath'=>(@realpath($file) != $file) ? @realpath($file) : '',
  			'dirname'=>@dirname($file),
  			'basename'=>@basename($file)
  			),
		'filetype'=>array(
  			'type'=>substr($ts[octdec($t)],1),
  			'type_octal'=>sprintf("%07o", octdec($t)),
  			'is_file'=>@is_file($file),
  			'is_dir'=>@is_dir($file),
  			'is_link'=>@is_link($file),
  			'is_readable'=> @is_readable($file),
  			'is_writable'=> @is_writable($file)
  			),
 		'device'=>array(
  			'device'=>$ss['dev'], //Device
  			'device_number'=>$ss['rdev'], //Device number, if device.
  			'inode'=>$ss['ino'], //File serial number
  			'link_count'=>$ss['nlink'], //link count
  			'link_to'=>($s['type']=='link') ? @readlink($file) : ''
  			),
 		'size'=>array(
  			'size'=>$ss['size'], //Size of file, in bytes.
  			'blocks'=>$ss['blocks'], //Number 512-byte blocks allocated
  			'block_size'=> $ss['blksize'] //Optimal block size for I/O.
  			),
 		'time'=>array(
  			'mtime'=>$ss['mtime'], //Time of last modification
  			'atime'=>$ss['atime'], //Time of last access.
  			'ctime'=>$ss['ctime'], //Time of last status change
  			'accessed'=>@date('Y M D H:i:s',$ss['atime']),
  			'modified'=>@date('Y M D H:i:s',$ss['mtime']),
  			'created'=>@date('Y M D H:i:s',$ss['ctime'])
  			),
 		);
 	clearstatcache();
 	return $s;
	}
//---------- begin function fixEncoding
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function fixEncoding($in_str=''){
	$cur_encoding = mb_detect_encoding($in_str) ;
	if($cur_encoding == "UTF-8" && mb_check_encoding($in_str,"UTF-8")){return $in_str;}
	else{return utf8_encode($in_str);}
	}
//---------- begin function fixMicrosoft ----------
/**
* @describe converts the Microsoft special characters to their HTML entity
* @param str string
*	string that contains special Microsoft characters
* @return
*	decoded string
* @usage $str=fixMicrosoft($str);
*/
function fixMicrosoft($text=''){
	// First, replace UTF-8 characters.
	$text = str_replace(
 		array("\xE2\x80\x98","\xE2\x80\x99","\xE2\x80\x9C","\xE2\x80\x9D","\xE2\x80\x93","\xE2\x80\x94","\xE2\x84\xA2"),
 		array("'","'",'"','"',"-","-","&trade;"),
 		$text);
 	$text = str_replace(
 		array("\x91","\x92","\x93","\x94","\x96","\x97","\x99"),
 		array("'","'",'"','"',"-","-","&trade;"),
 		$text);
 	// Next, replace their ASCII equivalents.
 	$text = str_replace(
 		array('&#145;', '&#146;', '&#39;', '&#147;', '&#148;', '&#150;', '&#151;', '&#133;'),
 		array("'", "'", "'", '"', '"', '-', '--', '...'),
 		$text);
 	return $text;
 	/*
 	$cp1252_map=array(
	    "\x80"=>"\xE2\x82\xAC",    // EURO SIGN
	    "\x82" => "\xE2\x80\x9A",  // SINGLE LOW-9 QUOTATION MARK
	    "\x83" => "\xC6\x92",      // LATIN SMALL LETTER F WITH HOOK
	    "\x84" => "\xE2\x80\x9E",  // DOUBLE LOW-9 QUOTATION MARK
	    "\x85" => "\xE2\x80\xA6",  // HORIZONTAL ELLIPSIS
	    "\x86" => "\xE2\x80\xA0",  // DAGGER
	    "\x87" => "\xE2\x80\xA1",  // DOUBLE DAGGER
	    "\x88" => "\xCB\x86",      // MODIFIER LETTER CIRCUMFLEX ACCENT
	    "\x89" => "\xE2\x80\xB0",  // PER MILLE SIGN
	    "\x8A" => "\xC5\xA0",      // LATIN CAPITAL LETTER S WITH CARON
	    "\x8B" => "\xE2\x80\xB9",  // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
	    "\x8C" => "\xC5\x92",      // LATIN CAPITAL LIGATURE OE
	    "\x8E" => "\xC5\xBD",      // LATIN CAPITAL LETTER Z WITH CARON
	    "\x91" => "\xE2\x80\x98",  // LEFT SINGLE QUOTATION MARK
	    "\x92" => "\xE2\x80\x99",  // RIGHT SINGLE QUOTATION MARK
	    "\x93" => "\xE2\x80\x9C",  // LEFT DOUBLE QUOTATION MARK
	    "\x94" => "\xE2\x80\x9D",  // RIGHT DOUBLE QUOTATION MARK
	    "\x95" => "\xE2\x80\xA2",  // BULLET
	    "\x96" => "\xE2\x80\x93",  // EN DASH
	    "\x97" => "\xE2\x80\x94",  // EM DASH
	    "\x98" => "\xCB\x9C",      // SMALL TILDE
	    "\x99" => "\xE2\x84\xA2",  // TRADE MARK SIGN
	    "\x9A" => "\xC5\xA1",      // LATIN SMALL LETTER S WITH CARON
	    "\x9B" => "\xE2\x80\xBA",  // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
	    "\x9C" => "\xC5\x93",      // LATIN SMALL LIGATURE OE
	    "\x9E" => "\xC5\xBE",      // LATIN SMALL LETTER Z WITH CARON
	    "\x9F" => "\xC5\xB8"       // LATIN CAPITAL LETTER Y WITH DIAERESIS
	  );
	*/
    }
//---------- begin function flvPlayer
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function flvPlayer($params=array()){
	if(!isset($params['movie'])){return "flvPlayer Error: No movie";}
	//defaults unless passed in
	if(!isset($params['id'])){$params['id']='flvPlayer1';}
	if(!isset($params['width'])){$params['width']=400;}
	if(!isset($params['height'])){$params['height']=325;}
	if(!isset($params['align'])){$params['align']='';}
	if(!isset($params['bgcolor'])){$params['bgcolor']='#FFFFFF';}
	if(!isset($params['quality'])){$params['quality']='high';}
	if(!isset($params['btncolor'])){$params['btncolor']='0x333333';}
	if(!isset($params['accentcolor'])){$params['accentcolor']='0x31b8e9';}
	if(!isset($params['txtcolor'])){$params['txtcolor']='0xdddddd';}
	if(!isset($params['volume'])){$params['volume']=100;}
	if(!isset($params['autoload'])){$params['autoload']='on';}
	if(!isset($params['autoplay'])){$params['autoplay']='off';}
	if(!isset($params['vTitle'])){$params['vTitle']='';}
	if(!isset($params['showTitle'])){$params['showTitle']='no';}
	$width=' width="'.$params['width'].'"';
	$height=' height="'.$params['height'].'"';
	$align=' align="'.$params['align'].'"';
	$parts=array();
	foreach($params as $key=>$val){
    	if(preg_match('/^(id|width|height)$/i',$key)){continue;}
    	$parts[]=$key.'='.encodeURL($val);
	}
	$swfopts=implode('&',$parts);
	$rtn='';
	$divid=$params['id']."_div";
	$rtn .= '<div id="'.$divid.'">'.PHP_EOL;
	$rtn .= '	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" id="'.$params['id'].'" '.$width.$height.$align.'>'.PHP_EOL;
  	$rtn .= '		<param name="movie" value="/wfiles/OSplayer.swf?'.$swfopts.'">'.PHP_EOL;
  	$rtn .= '		<param name="allowFullScreen" value="true">'.PHP_EOL;
   	$rtn .= '		<param name="allowScriptAccess" value="always">'.PHP_EOL;
  	$rtn .= '		<param name="quality" value="'.$params['quality'].'">'.PHP_EOL;
  	$rtn .= '		<param name="bgcolor" value="'.$params['bgcolor'].'">'.PHP_EOL;
  	$rtn .= '		<embed src="/wfiles/OSplayer.swf?'.$swfopts.'" '.$width.$height.$align.' quality="'.$params['quality'].'" bgcolor="'.$params['bgcolor'].'" name="'.$params['id'].'" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">'.PHP_EOL;
  	$rtn .= '		</embed>'.PHP_EOL;
	$rtn .= '	</object>'.PHP_EOL;
	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
	}
//---------- begin function formatMoney ----------
/**
* @describe returns number formatted as currency
* @param number numeric
*	number to format as currency
* @param cents boolean
*	include cents - defaults to true
*	0=never, 1=if needed, 2=always
* @return
*	number formatted as currency
* @usage $num=formatMoney($num,0);
*/
function formatMoney($number=0,$cents = 1){
	if(isNum($number)){
    	if($number==0){
      		$money = ($cents == 1 ? '0.00' : '0');
    		}
		else{
      		if (floor($number) == $number) {
        		$money = number_format($number, ($cents == 1 ? 2 : 0));
      			}
			else{
        		$money = number_format(round($number, 2), ($cents == 1 ? 2 : 0));
      			}
    		}
    	return $money;
  		}
  	return $number;
	}
//---------- begin function timezoneList ----------
/**
* @describe returns an array of all timezones
* @param params array - options
*	[-groupby] string - region or timezone, defaults to region. becomes the array index
*	[-regions] mixed - DateTimeZone regions
* @return array - an array of all timezones
* @usage $zones=timezoneList(array('-regions'=>DateTimeZone::AMERICA));
*/function timezoneList($params=array()){
	global $timezoneListCache;
	ksort($params);
	$key=sha1(json_encode($params,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
	if(isset($timezoneListCache[$key])){return $timezoneListCache[$key];}
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    //echo printValue($timezones);exit;
    $timezone_offsets = array();
    foreach( $timezones as $timezone ){
        $tz = new DateTimeZone($timezone);
        $timezone_offsets[$timezone] = $tz->getOffset(new DateTime);
    }
    // sort timezone by timezone name
    ksort($timezone_offsets);
    $timezoneListCache[$key] = array();
    foreach( $timezone_offsets as $timezone => $offset ){
        $offset_prefix = $offset < 0 ? '-' : '+';
        $offset_formatted = gmdate( 'H:i', abs($offset) );
        $utc = "UTC{$offset_prefix}{$offset_formatted}";
        $t = new DateTimeZone($timezone);
        $c = new DateTime(null, $t);
        $current_time = $c->format('D g:i A');
        $timezoneListCache[$key][$timezone]="{$timezone} - {$current_time}";
    }
    return $timezoneListCache[$key];
}
//---------- begin function toFixed ----------
/**
* @describe returns number with specified decimal places - just like javascript toFixed
* @param number numeric - number to format as currency, invalid numbers will be set to 0.00
* @param decimals - number of decimals - defaults to 2
* @return number numeric -  value with specified decimals
* @usage $num=toFixed($num,2);
*/
function toFixed($number=0,$decimals = 2){
	$number=preg_replace('/[^0-9\.]+/','',$number);
	if(!isNum($number)){$number=0;}
	$num= number_format($number, $decimals, ".", "");
	return $num;
	}
//---------- begin function formatPre ----------
/**
* @describe wrapper for nl2br - converts end of line characters to <br> tags
* @param str string
*	multi-line string
* @return
*	formatted multi-line string
* @usage formatPre($txt);
*/
function formatPre($str=''){
	if(!is_string($str)){return printValue($str);}
	return nl2br(trim($str));
	}
//---------- begin function friendlyName--------------------
/**
* @describe converts camelCase or underscore_separated names to a human-readable/human-friendly version
* @param str string
* @return string
* @author Jeremy Despain
* @date January 27, 2011
* @usage
*	friendlyName("camelCase") returns "Camel Case";
*	friendlyName("underscore_separated") returns "Underscore Separated";
*/
function friendlyName($string){
	$string = preg_replace('/\_/', ' ', $string);
	$string = preg_replace('/([A-Z]{2,})([A-Z][a-z])/', '$1 $2', preg_replace('/([a-z])([A-Z])/', '$1 $2', $string));
	return ucwords($string);
	}
//---------- begin function generatePassword--------------------
/**
* @describe creates a password with specified strength
* @param length integer - requested password length - defaults to 9
* @param strength integer
*	1 = Consonants only
*	2 = consonants and vowels
*	4 = consonants, vowels, and numbers - excludes number 1 so it is not mixed up with letter L
*	8 = consonants, vowels, numbers, and a few special characters @#$%
* @return string
* @usage $pw=generatePassword(10,8);
*/
function generatePassword($length=9, $strength=2) {
	$vowels = 'aeuy';
	$consonants = 'bdghjmnpqrstvz';
	if ($strength & 1) {
		$consonants .= 'BDGHJLMNPQRSTVWXZ';
	}
	if ($strength & 2) {
		$vowels .= "AEUY";
	}
	if ($strength & 4) {
		$consonants .= '23456789';
	}
	if ($strength & 8) {
		$consonants .= '@#$%';
	}
	$password = '';
	$alt = time() % 2;
	for ($i = 0; $i < $length; $i++) {
		if ($alt == 1) {
			$password .= $consonants[(rand() % strlen($consonants))];
			$alt = 0;
		}
		else {
			$password .= $vowels[(rand() % strlen($vowels))];
			$alt = 1;
		}
	}
	return $password;
}
//---------- begin function generateGUID--------------------
/**
* @describe creates a password with specified strength
* @param [curly] boolean - show curly brackets - defaults to false
* @param [hyphen] - include hypyens - defaults to true
* @return string
* @usage $guid=generateGUID(false,true);
*/
function generateGUID($curly=false,$hyphen=true){
	if( function_exists('com_create_guid') ){
        if( $curly ){ return com_create_guid(); }
        else { return trim( com_create_guid(), '{}' ); }
    }
    else {
        mt_srand( (integer)microtime() * 10000 );    // optional for php 4.2.0 and up.
        $charid = strtoupper( md5(uniqid(rand(), true)) );
        $dash = $hyphen ? chr( 45 ) : "";    // "-"
        $left_curly = $curly ? chr(123) : "";     //  "{"
        $right_curly = $curly ? chr(125) : "";    //  "}"
        $uuid = $left_curly
            . substr( $charid, 0, 8 ) . $dash
            . substr( $charid, 8, 4 ) . $dash
            . substr( $charid, 12, 4 ) . $dash
            . substr( $charid, 16, 4 ) . $dash
            . substr( $charid, 20, 12 )
            . $right_curly;
        return $uuid;
    }
}
//---------- begin function functionList
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function functionList($internal=1,$wasql=1){
	$functions=array();
	//internal functions
	if($internal){
		$funcs=get_defined_functions();
		foreach($funcs['internal'] as $fname){
			$functions[$fname]['name']=$fname;
			$functions[$fname]['type']='internal';
			$functions[$fname]['link']='http://us2.php.net/manual/en/function.'.preg_replace('/\_/','-',$fname).'.php';
	        }
		}
	if($wasql){
	    //user functions
	    $files=get_included_files();
	    $wpath=getWasqlPath();
	    $files[]="{$wpath}/wfiles/js/common.js";
	    $files[]="{$wpath}/wfiles/js/form.js";
	    $files[]="{$wpath}/wfiles/js/event.js";
		foreach($files as $file){
			$lines=file($file);
			$cnt=count($lines);
			for($i=0;$i<$cnt;$i++){
				$line=trim($lines[$i]);
				if(preg_match('/^function (.+?)\((.*?)\)(\s*?)\{/',$line,$lmatch)){
					$fname=$lmatch[1];
					$functions[$fname]['name']=$fname;
					$functions[$fname]['type']='user';
					$functions[$fname]['line']=$i;
					$functions[$fname]['params']=$lmatch[2];
					$functions[$fname]['file']=getFileName($file);
					$functions[$fname]['ext']=strtolower(getFileExtension($file));
					$functions[$fname]['path']=getFilePath($file);
					$n=$i+1;
					while(preg_match('/^\/\/(.+?)\:(.+)/i',trim($lines[$n]),$imatch)){
						$type=$imatch[1];
						if(preg_match('/^(name|params|file|path|line)$/i',$type)){continue;}
						$functions[$fname][$type] .= trim($imatch[2]) . "<br>\n";
						$n++;
	                    }
	                if(!isset($functions[$fname]['info']) && !isset($functions[$fname]['usage'])){unset($functions[$fname]);}
	                }
	            }
	        unset($lines);
	        }
	    }
    return $functions;
	}
//---------- begin function functionSearch
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function functionSearch($str,$internal=1,$wasql=1){
	$functions=functionList($internal,$wasql);
	$search=array();
	$searchfields=array('name','info','usage','reference');
	foreach($functions as $function){
		$found=0;
		foreach($searchfields as $searchfield){
			if(stringContains($function[$searchfield],$str)){$found=1;}
			}
		if($found > 0){
			$fname=$function['name'];
			foreach($function as $key=>$val){
				$search[$fname][$key]=$val;
            }
        }
	}
	return $search;
}
//---------- begin function getAllVersions
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getAllVersions(){
	$versions=array(
		//'WaSQL'	=> wasqlVersion(),
		databaseType()	=> getDBVersion()
		);
	$funcs=get_defined_functions();
	foreach($funcs['internal'] as $fname){
		//if(preg_match('/^(mysqli_get_server_version|ming_useswfversion)/i',$fname)){continue;}
		if(preg_match('/(.+?)version/i',$fname,$fmatch)){
			switch(strtolower($fname)){
				case 'pg_version':
					$out=cmdResults('psql --version');
					$val=$out['stdout'];
				break;
				case 'mysqli_get_server_version':
					$out=cmdResults('mysql --version');
					$val=$out['stdout'];
				break;
				case 'oci_server_version':
				case 'ociserverversion':
					$val='';
				break;
				default:
					try{$val=$fname();}
					catch(Exception $e){
						$val=$e;
					}
				break;
			}
			//echo "{$fname}<br>".printValue($val).'<hr>';
			if(is_array($val)){
				$versions[$fname]=json_encode($val,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
			}
			else{$versions[$fname]=$val;}
		}
	}
	//GD Version
	if(function_exists('gd_info')){
		$info=gd_info();
		//$info=@eval('return gd_info();');
		if(is_array($info) && isset($info['GD Version'])){
			$versions['GD (Graphics)']=$info['GD Version'];
        	}
    	}
	ksort($versions,SORT_STRING );
	//echo printValue($versions);exit;
	return $versions;
	}
//---------- begin function getMemoryUsage-----------------
/**
* @describe Returns the amount of memory currently being allocated to your PHP script
* @param verbose boolean
*	verbose returns the verbose size instead of bytes, defaults to true
* @param diff boolean
*	also returns the delta since last call to this function. defaults to true
* @return
*	returns current memory usage
* @usage $mem=getMemoryUsage();
*/
function getMemoryUsage($verbose=1,$diff=1){
	$bytes = memory_get_usage(true);
	$rtn='';
	if($diff==1 && isset($_SERVER['getMemoryUsage_Last']) && isNum($_SERVER['getMemoryUsage_Last'])){
    	$delta=$bytes-$_SERVER['getMemoryUsage_Last'];
    	if($verbose==1){
			$rtn = verboseSize($bytes);
			$rtn .= ' ('.verboseSize($delta).' delta)';
			}
    	else{
			$rtn = $bytes;
			$rtn .= ' ('.$delta.' delta)';
			}
	}
	else{

    	if($verbose==1){$rtn = verboseSize($bytes);}
    	else{$rtn = $bytes;}
	}
	$_SERVER['getMemoryUsage_Last']=$bytes;
	return $rtn;
}
//---------- begin function getPageFunctions
/**
* @describe parses the current pages's functions and returns an array with metadeta about the functions.
* @return array
*	['name'] = function name
*   ['params'] = function paramaters
* @usage echo printValue(getPageFunctions());
* @author slloyd
* @history bbarten 2014-01-07 added documentation
*/
function getPageFunctions(){
	global $PAGE;
	global $TEMPLATE;
	$functions=array();
	$contents=$PAGE['functions'];
	if(!strlen($PAGE['functions'])){return 'NONE';}
	$lines=preg_split('/[\r\n]+/',$PAGE['functions']);
	$cnt=count($lines);
	for($i=0;$i<$cnt;$i++){
		$line=trim($lines[$i]);
		unset($lmatch);
		if(preg_match('/^function (.+?)\((.*?)\)(\s*?)\{/is',$line,$lmatch)){
			$fname=$lmatch[1];
			$functions[$fname]['name']=$fname;
			$functions[$fname]['params']=$lmatch[2];
        }
    }
    return $functions;
}
//---------- begin function getFileExif
/**
* @describe Returns EXIF Data stored in a file
* @param string file
*	the absolute path to the file you want to extract from
* @return array
*	returns and array of key/value pairs separated into groups
* @usage $exif=getFileExif($afile);
*/
function getFileExif($afile=''){
	if(!is_file($afile)){return "No such File: {$afile}";}
	$slash=isWindows()?"\\":'/';
	$path=getWasqlPath();
	$path=preg_replace('|[\\\/+]+$|','',$path);
	$path=preg_replace('/\/+/',$slash,$path);
    $path=preg_replace('/\\+/',$slash,$path);
	$cmd="perl -X {$path}{$slash}exif.pl file=\"{$afile}\"";
	$post=cmdResults($cmd);
	if(isset($post['stderr']) && strlen($post['stderr'])){debugValue($post);}
	if(!isset($post['stdout']) || !strlen($post['stdout'])){
    	return '';
	}
	$exif=xml2Array($post['stdout']);
	if(!isset($exif['root'])){
		debugValue($post);
		return '';
		}
	if(isset($exif['root']['exiftool'])){
		unset($exif['root']['exiftool']);
	}
	if(!isset($exif['root']['latitude'])){
		if(isset($exif['root']['composite']['gpslatitude'])){
			$exif['root']['latitude']=convertLatLon($exif['root']['composite']['gpslatitude']);
		}
		elseif(isset($exif['root']['exif']['gpslatitude'])){
			$str=$exif['root']['exif']['gpslatitude'];
			if(isset($exif['root']['exif']['gpslatituderef'])){$str .= " {$exif['root']['exif']['gpslatituderef']}";}
			$exif['root']['latitude']=convertLatLon($str);
		}
	}
	if(!isset($exif['root']['longitude'])){
		if(isset($exif['root']['composite']['gpslongitude'])){
			$exif['root']['longitude']=convertLatLon($exif['root']['composite']['gpslongitude']);
		}
		elseif(isset($exif['root']['exif']['gpslongitude'])){
			$str=$exif['root']['exif']['gpslongitude'];
			if(isset($exif['root']['exif']['gpslongituderef'])){$str .= " {$exif['root']['exif']['gpslongituderef']}";}
			$exif['root']['longitude']=convertLatLon($str);
		}
	}
	return $exif['root'];
}
//---------- begin function convertLatLon
/**
* @describe Returns EXIF Data stored in a file
* @param string - longitude or latitude string (111 deg 52' 11.93" W)
* @return number - numberical value for longitude or latitude
* @usage $longitude=convertLatLon('111 deg 52\' 11.93" W');
*/
function convertLatLon($str) {
	$m=preg_split('/[\s\'\"]+/',trim($str));
	if(!isNum($m[0])){
    	debugValue("convertLatLon Error: '{$str}' is malformed.".printValue($m));
    	return $str;
	}
	$deg=$m[0];
	$minutes=$m[2];
	$seconds=$m[3];
	$direction=$m[4];
    $dd = $deg + $minutes/60 + $seconds/(60*60);
	// South and west directions are negative
	switch(strtolower($direction)){
    	case 's':
    	case 'south':
    	case 'w':
    	case 'west':
    		$dd = $dd * -1;
    	break;
	}
    return $dd;
}
//---------- begin function xls2CSV---------------------------------------
/**
* @describe converts a simple Excel (xls) file to a csv data stream
* @param file string
*	full name and path to the file to convert to CSV
* @return csv data stream
*	returns the CSV data of the xls file
* @usage return xls2CSV('name');
*/
function xls2CSV($afile=''){
	$path=getWasqlPath();
	$cmd="perl -X {$path}/xls2csv.pl file=\"{$afile}\"";
	$post=cmdResults($cmd);
	$post['stdout']=preg_replace('/^Content\-type\:\ text\/plain/ism','',$post['stdout']);
	$post['stdout']=trim($post['stdout']);
	return $post['stdout'];
}
//---------- begin function getFileIcon---------------------------------------
/**
* @describe returns the img tag for the icon associated with a file extension
* @param file string
*	name of the file
* @return image path to the icon associated with file extension
* @usage 
*	<img src="&lt;?=getFileIcon('info.doc');?&gt;" >
*/
function getFileIcon($file=''){
	$progpath=dirname(__FILE__);
	$iconpath=preg_replace('/php$/i','',$progpath) . "wfiles/icons/files";
	$iconpath=str_replace("\\","/",$iconpath);
	$ext=strtolower(getFileExtension($file));
	if(is_file("{$iconpath}/{$ext}.gif")){$icon="{$ext}.gif";}
	elseif(is_file("{$iconpath}/{$ext}.png")){$icon="{$ext}.png";}
	elseif(isAudioFile($file)){$icon="audio.gif";}
	elseif(isVideoFile($file)){$icon="video.gif";}
	else{return null;}
	return '<img src="/wfiles/icons/files/'.$icon.'" class="w_middle" alt="" >';
	}
//---------- begin function getHolidays
/**
* @describe Returns all holidays for a given year
* @param params array
*	-year default to current year
* @return array
*	returns and array holidays
* @usage $holidays=getHolidays(2014);
*/
function getHolidays($params=array()){
	/*
		by default return all holidays for the current year
		get holidays for several years
		get holidays for one year from today
		need some unique holiday code so I can filter what holidays get returned
		federal holiday or not
	*/
	if(!isset($params['year']) || !isNum($params['year'])){$params['year']=date("Y");}
	$holidays=getHolidayList($params);
	$hcount=count($holidays);
	//return $hcount;
	$ctime=time();
	$list=array();
	foreach($holidays as $name=>$holiday){
		if(isset($params['codes']) && is_array($params['codes']) && !in_array($holiday['code'],$params['codes'])){continue;}
		if(isset($params['code']) && $holiday['code'] != $params['code']){continue;}
		if(isset($params['fed']) && $holiday['code'] != $params['fed']){continue;}
		if(isset($params['country']) && $holiday['country'] != $params['country']){continue;}
		$holiday['name']=$name;
		$holiday['timestring']="{$holiday['timestr']} {$params['year']}";
		$holiday['timestamp']=strtotime($holiday['timestring']);
		if(isset($holiday['offset']) && isNum($holiday['offset'])){
			$holiday['timestamp']+=$holiday['offset'];
        	}
		if(isset($params['upcoming']) && $params['upcoming']==true && $holiday['timestamp'] < $ctime){
			$nextyear=$params['year']+1;
			$holiday['timestring']="{$holiday['timestr']} {$nextyear}";
			$holiday['timestamp']=strtotime($holiday['timestring']);
			if(isset($holiday['offset']) && isNum($holiday['offset'])){
				$holiday['timestamp']+=$holiday['offset'];
	        	}
        	}

		$holiday['date']=date("Y-m-d",$holiday['timestamp']);
		$gdate=getdate($holiday['timestamp']);
		$holiday['day']=$gdate['mday'];
		$holiday['month']=$gdate['month'];
		if(isset($params['month']) && $holiday['month'] != $params['month']){continue;}
		if(isset($params['-index'])){
			$index=$holiday[$params['-index']];
			$list[$index]=$holiday;
			}
		else{$list[]=$holiday;}
    	}
    $list=sortArrayByKeys($list, array('timestamp'=>SORT_ASC, 'name'=>SORT_ASC));
	return $list;
	}
//---------- begin function getHolidayList
/**
* @describe Returns all holidays for a given year
* @param params array
*	-year default to current year
* @return array
*	returns and array holidays
* @usage $holidays=getHolidayList(2014);
*/
function getHolidayList($params=array()){
	if(!isset($params['year']) || !strlen($params['year']) || $params['year']=date('Y') < 1970){
		$params['year']=date('Y');
	}
	$params['year']=(integer)$params['year'];
	if($params['year'] < 1970 || $params['year'] > 2030){
		$params['year']=date('Y');
	}
	$holidays=array(
		"New Year's Day" =>
			array('code'=>'NYD','icon'=>'/wfiles/icons/holidays/nyd.png','fed'=>true,'country'=>'US','timestr'=>"January 1st"),
		"Martin Luther King, Jr. Day" =>
			array('code'=>'MLK','fed'=>true,'country'=>'US','note'=>'3rd Monday of January','timestr'=>"third monday of january"),
		//"Groundhog Day" =>array('code'=>'GRO','fed'=>false,'country'=>'US','timestr'=>"February 2nd"),
		//"Super Bowl Sunday"=>array('code'=>'SBS','icon'=>'/wfiles/icons/holidays/sbs.png','fed'=>false,'country'=>'US','note'=>"first Sunday of February",'timestr'=>"+0 week sun feb"),
		"Valentine's Day"=>
			array('code'=>'VAL','icon'=>'/wfiles/icons/holidays/val.png','fed'=>false,'country'=>'US','timestr'=>"February 14th"),
		"Presidents Day"=>
			array('code'=>'PRE','icon'=>'/wfiles/icons/holidays/pre.gif','fed'=>true,'country'=>'US','note'=>"3rd Monday of February",'timestr'=>"third monday of february"),
		//"April Fools' Day"=>array('code'=>'AFD','fed'=>false,'country'=>'US','timestr'=>"April 1st"),
		"Easter"=>array('code'=>'EAS','fed'=>false,'country'=>'US','timestr'=>date("F jS",easter_date($params['year'])),'note'=>"First Sunday after full moon after March 21st"),
		"Palm Sunday"=>array('code'=>'PSU','fed'=>false,'country'=>'US','timestr'=>date("F jS",easter_date($params['year'])-7*86400),'note'=>"7 days before Easter"),
		"Ash Wednesday"=>array('code'=>'PSU','fed'=>false,'country'=>'US','timestr'=>date("F jS",easter_date($params['year'])-46*86400),'note'=>"46 days before Easter"),
		"Good Friday"=>array('code'=>'PSU','fed'=>false,'country'=>'US','timestr'=>date("F jS",easter_date($params['year'])-2*86400),'note'=>"2 days before Easter"),
		"Corpus Christi"=>array('code'=>'PSU','fed'=>false,'country'=>'US','timestr'=>date("F jS",easter_date($params['year'])+60*86400),'note'=>"60 days after Easter"),
		"St. patrick's Day"=>array('code'=>'SPD','fed'=>false,'country'=>'US','timestr'=>"March 17th"),
		"Patriot's Day"=>array('code'=>'PAT','fed'=>false,'country'=>'US','note'=>'3rd Monday of April','timestr'=>"third monday of april"),
		//"Arbor Day"=>array('code'=>'ARB','fed'=>false,'country'=>'US','note'=>'last Friday of April','timestr'=>"-1 week fri may"),
		"Cinco De Mayo"=>array('code'=>'CIN','fed'=>false,'country'=>'US','timestr'=>"May 5th"),
		"Mother's Day"=>array('code'=>'MOT','icon'=>'/wfiles/icons/holidays/mot.gif','fed'=>false,'country'=>'US','timestr'=>"+1 week sun may"),
		"Memorial Day"=>array('code'=>'MEM','icon'=>'/wfiles/icons/holidays/mem.png','fed'=>true,'country'=>'US','note'=>'last Monday of May','timestr'=>"-1 week mon jun"),
		"Flag Day"=>array('code'=>'FLA','fed'=>false,'country'=>'US','timestr'=>"June 14th"),
		"Father's Day"=>array('code'=>'FAT','fed'=>false,'country'=>'US','note'=>'3rd Sunday of June','timestr'=>"+2 week sun jun"),
		"Independence Day"=>array('code'=>'IND','icon'=>'/wfiles/icons/holidays/ind.png','fed'=>true,'country'=>'US','timestr'=>"July 4th"),
		"Pioneer Day"=>array('code'=>'PIO','icon'=>'/wfiles/icons/holidays/pio.png','fed'=>false,'country'=>'US','timestr'=>"July 24th"),
		"Labor Day"=>array('code'=>'LAB','icon'=>'/wfiles/icons/holidays/lab.png','fed'=>true,'country'=>'US','note'=>'first Monday of September','timestr'=>"+0 week mon sep"),
		//"Leif Erikson Day"=>array('code'=>'LEI','fed'=>false,'country'=>'US','timestr'=>"October 9th"),
		"Columbus Day"=>array('code'=>'COL','fed'=>true,'country'=>'US','note'=>'2nd Monday of October','timestr'=>"+1 week mon oct"),
		"Halloween"=>array('code'=>'HAL','icon'=>'/wfiles/icons/holidays/hal.png','fed'=>false,'country'=>'US','timestr'=>"October 31st"),
		"All Saints Day"=>array('code'=>'ASD','fed'=>false,'country'=>'US','timestr'=>"November 1st"),
		"Veterans Day"=>array('code'=>'VET','fed'=>true,'country'=>'US','timestr'=>"November 11th"),
		"Thanksgiving"=>array('code'=>'THA','icon'=>'/wfiles/icons/holidays/tha.gif','fed'=>false,'country'=>'US','note'=>'4th Thursday of November','timestr'=>"fourth thursday of november"),
		//"Black Friday"=>array('code'=>'BLA','icon'=>'/wfiles/icons/holidays/bla.png','fed'=>false,'country'=>'US','note'=>'Friday after Thanksgiving Day','timestr'=>"fourth friday of november",'offset'=>86400),
		"Pearl Harbor Remembrance Day"=>array('code'=>'PHR','fed'=>false,'country'=>'US','timestr'=>"December 7th"),
		"Christmas Eve"=>array('code'=>'CHE','icon'=>'/wfiles/icons/holidays/che.png','fed'=>false,'country'=>'US','timestr'=>"December 24th"),
		"Christmas Day"=>array('code'=>'CHD','icon'=>'/wfiles/icons/holidays/chd.png','fed'=>true,'country'=>'US','timestr'=>"December 25th"),
		"New Year's Eve"=>array('code'=>'NYE','fed'=>false,'country'=>'US','timestr'=>"December 31st"),
		);
	return $holidays;
	}
//---------- begin function getImageWidthHeight
/**
* @describe gets the image width and height from EXIF data
* @param afile string
*	absolute path to image file
* @return array
*	return key/value array with width and height as keys or returns false if file is not an image
* @usage $img=getImageWidthHeight($afile);
*/
function getImageWidthHeight($afile){
	if(isImageFile($afile)){
		$exif=getFileExif($afile);
		//height
		if(isNum($exif['file']['imageheight'])){$exif['height']=$exif['file']['imageheight'];}
		elseif(isNum($exif['png']['imageheight'])){$exif['height']=$exif['png']['imageheight'];}
		elseif(isNum($exif['gif']['imageheight'])){$exif['height']=$exif['gif']['imageheight'];}
		elseif(isNum($exif['jpg']['imageheight'])){$exif['height']=$exif['jpg']['imageheight'];}
		//width
		if(isNum($exif['file']['imagewidth'])){$exif['width']=$exif['file']['imagewidth'];}
		elseif(isNum($exif['png']['imagewidth'])){$exif['width']=$exif['png']['imagewidth'];}
		elseif(isNum($exif['gif']['imagewidth'])){$exif['width']=$exif['gif']['imagewidth'];}
		elseif(isNum($exif['jpg']['imagewidth'])){$exif['width']=$exif['jpg']['imagewidth'];}
		//try composite if still no width or height
		if(!isNum($exif['width']) && isset($exif['composite']) && preg_match('/^([0-9]+?)x([0-9]+)$/i',$file['composite'],$m)){
			$exif['width']=$m[1];
			$exif['height']=$m[2];
		}
		return array('width'=>$exif['width'],'height'=>$exif['height']);
	}
	return false;
}
//---------- begin function getImageSrc
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getImageSrc($name='',$size=16){
	$wfiles=getWfilesPath();
	if(!strlen($name)){return '';}
	$name=strtolower($name);
	$names=array(
		$name,
		preg_replace('/^\_/','',$name),
		preg_replace('/s$/','',$name),
		preg_replace('/ies$/','y',$name),
		preg_replace('/es$/','',$name),
		);
	foreach($names as $name){
		if(is_file("{$wfiles}/iconsets/{$size}/{$name}.png")){return "/wfiles/iconsets/{$size}/{$name}.png";}
		if(is_file("{$wfiles}/icons/{$name}.png")){return "/wfiles/icons/{$name}.png";}
		if(is_file("{$wfiles}/icons/{$name}.gif")){return "/wfiles/icons/{$name}.gif";}
		if(is_file("{$wfiles}/icons/files/{$name}.gif")){return "/wfiles/icons/files/{$name}.gif";}
		if(is_file("{$wfiles}/{$name}.png")){return "/wfiles/{$name}.png";}
		if(is_file("{$wfiles}/{$name}.gif")){return "/wfiles/{$name}.gif";}
		}
	return '';
	}

//---------- begin function getRemoteEnv
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getRemoteEnv(){
	$xml=xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
	$xml .= '<env>'.PHP_EOL;
	foreach($_SERVER as $key=>$val){
		if(is_array($val) || !strlen($val)){continue;}
        	if(preg_match('/^(REMOTE\_|GUID|HTTP\_USER\_AGENT)/i',$key)){
            $val=utf8_encode($val);
			$val=xmlEncodeCDATA($val);
            $xml .= "        <{$key}>".$val."</{$key}>\n";
		}
	}
	$xml .= '<env>'.PHP_EOL;
	return $xml;
}
//---------- begin function getRemoteFileInfo--------------------
/**
* @describe returns an array of information about a remote (url based) file based on the header information returned
* @param url string
* @return array
* @usage $info=getRemoteFileInfo('http://www.someserver.com/somefile.txt');
*/
function getRemoteFileInfo($url){
	$ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    curl_close($ch);
    $parts=preg_split('/[\r\n]+/',trim($data));
	$info=array();
	foreach($parts as $part){
		if(!preg_match('/\:/',$part)){continue;}
		list($key,$val)=preg_split('/\:/',trim($part));
		$key=strtolower(trim($key));
		$info[$key]=trim($val);
        }
    $info['source']=$url;
    unset($info['date']);
    unset($info['accept-ranges']);
    ksort($info);
	return $info;
}
//---------- begin function getRunTime--------------------
/**
* @describe returns the number of seconds as a float value the PHP script has ran for
* @return float
* @usage $elapsed_time=getRunTime();  returns 3.2399411201477
*/
function getRunTime(){
	global $TIME_START;
	return microtime(true)-$TIME_START;
}
//---------- begin function getStoredValueAge ----------
/**
* @describe returns the age in seconds of the stored value
* @param eval_code string - php code to evaluate
* @return integer seconds old
* @usage
*	$age=getStoredValueAge('return pageData();'); echo verboseTime($age);
*/
function getStoredValueAge($evalstr){
	$progpath=dirname(__FILE__);
	global $CONFIG;
	$local="{$progpath}/temp/" . md5($CONFIG['name'].$evalstr) . '.gsv';
    if(is_file($local) && filesize($local) > 50){
		$filetime=filemtime($local);
		$ctime=time();
		$diff_seconds=$ctime-$filetime;
		return $diff_seconds;
	}
	return 0;
}
//---------- begin function getStoredValue ----------
/**
* @describe sets or returns a previously set stored value. Stored values persist like sessions but work across multiple users
* @param eval_code string - php code to evaluate
* @param force boolean - force a refresh
* @param hrs numeric - hours before requiring a refresh of data
* @param debug boolean
* @param serialize boolean - set to true to serialize data
* @return
*	sets or returns a previously set stored value. Stored values persist like sessions but work across multiple users
* @usage
*	$data=getStoredValue('return pageData();',0,3);
*/
function getStoredValue($evalstr,$force=0,$hrs=1,$debug=0,$serialize=1){
	$progpath=dirname(__FILE__);
	buildDir("{$progpath}/temp");
	global $CONFIG;
	$local="{$progpath}/temp/" . md5($CONFIG['name'].$evalstr) . '.gsv';
	if($force && is_file($local)){unlink($local);}
    if(is_file($local) && filesize($local) > 50){
		$filetime=filemtime($local);
		$ctime=time();
		$diff_seconds=$ctime-$filetime;
		/* Use the local file if it is less than $hrs hours old */
		$file_hrs = round(($diff_seconds/60/60),2);
		if($debug){echo "getStoredValue: {$local} exists: {$file_hrs} < {$hrs}<br>\n";}
        if ($file_hrs < $hrs){
			$content = file_get_contents($local);
			if($serialize){return unserialize($content);}
			return $content;
		}
    }
    //eval and save local file
    if($debug){echo "getStoredValue: Evaluating...<br>\n{$evalstr}\n";}
    $data=@eval($evalstr);
    if($debug){echo "getStoredValue: Saving to {$local}\n";}
    if($serialize){
		setFileContents($local,serialize($data));
	}
	else{
		setFileContents($local,$data);
	}
	return $data;
}
//---------- begin function setStoredValue ----------
/**
* @describe sets or returns a previously set stored value. Stored values persist like sessions but work across multiple users
* @param eval_code string - php code to evaluate - THE SAME you used in getStoredValue
* @param data mixed - data to set
* @param [serialize] boolean - set to true to serialize data. defaults true
* @return boolean
* @usage
*	$recs=getStoredValue('return pageData();',0,3);
*	$recs[0]['color']='red';
*	$recs=setStoredValue('return pageData();',$recs);
*/
function setStoredValue($evalstr,$data,$serialize=1){
	$progpath=dirname(__FILE__);
	buildDir("{$progpath}/temp");
	global $CONFIG;
	$local="{$progpath}/temp/" . md5($CONFIG['name'].$evalstr) . '.gsv';
	if(is_file($local)){unlink($local);}
    if($serialize){
		setFileContents($local,serialize($data));
	}
	else{
		setFileContents($local,$data);
	}
	return true;
}
//---------- begin function buildImage
/**
* @depreciated  - use getStoredValue instead
* @exclude - this function will be depreciated and thus excluded from the manual
*/
function getStoredData($evalstr,$force=0,$hrs=1,$debug=0){
	return getStoredValue($evalstr,$force,$hrs,$debug,0);
	}
//---------- begin function importXmlData
/**
* @describe - imports the items array returned from exportFile2Array
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function importXmlData($items=array(),$params=array()){
	//set defaults to read all types
	if(!is_array($params['xmltypes'])){return "No xmltypes: xmlschema,xmlmeta,xmldata";}
	$importmsg=array();
	$newtables=array();
	foreach($params['xmltypes'] as $imtype){
		if($imtype=='xmlschema'){
			$importmsg[]= "Processing " . ucwords($imtype);
			foreach($items[$imtype] as $table=>$fields){
				if(preg_match('/^\_/',$table)){continue;}
				if(isDBTable($table) && is_array($params['xmloptions']) && !in_array('drop',$params['xmloptions'])){continue;}
				//drop table
				if(isDBTable($table)){$ok=dropDBTable($table);}
				//create table
				$ok=createDBTable($table,$fields);
				if(!isNum($ok)){
					$importmsg[]="createDBTable Error - {$table}";
					$importmsg[]=$fields;
					$importmsg[]=$ok;
				}
				else{
					$importmsg[]= " Created {$table}";
				}
				$newtables[$table]=1;
            }
        }
		else{
			if(!isset($items[$imtype])){continue;}
			$importmsg[]= "Processing " . ucwords($imtype);
			foreach($items[$imtype] as $table=>$recs){
				$truncate=0;
				if(!isset($newtables[$table]) && is_array($params['xmloptions']) && in_array('truncate',$params['xmloptions'])){
					if(preg_match('/^\_/',$table)){continue;}
					$ok=truncateDBTable($table);
					if(!isNum($ok)){
						$importmsg[]= "Truncate Error: {$table}";
						$importmsg[]= $ok;
					}
					else{
						$truncate=1;
						$importmsg[]=  " - truncating {$table}";
					}
                }
                foreach($recs as $rec){
					$rec['-table']=$table;
					if($table=='_users'){unset($rec['guid']);}
					$merged=0;
					//set _id or not.
					if(is_array($params['xmloptions'])){
						if(!in_array('ids',$params['xmloptions'])){unset($rec['_id']);}
					}
					else{unset($rec['_id']);}
					if(preg_match('/^\_(tabledata|fielddata)$/i',$table)){unset($rec['_id']);}
					if(isset($params['xmlmerge']) && strlen($params['xmlmerge'])){
						$fields=preg_split('/[\r\n\,\s\t]+/',$params['xmlmerge']);
						$info=getDBFieldInfo($table,1);
						foreach($fields as $field){
							if(!isset($info[$field])){continue;}
							if(!isset($rec[$field]) || !strlen($rec[$field])){continue;}
							if($merged==1){break;}
							$crec=getDBRecord(array('-table'=>$table,'-nocache'=>1,$field=>$rec[$field]));
							if(is_array($crec)){
								//edit existing record
								$rec['-where']="_id={$crec['_id']}";
								unset($rec['_id']);
								unset($rec['_cdate']);
								unset($rec['_cuser']);
								unset($rec['_edate']);
								unset($rec['_euser']);
								$ok=editDBRecord($rec);
								if(!isNum($ok)){
									$importmsg[]= $ok;
								}
								else{
									$importmsg[]=  "Merged  where {$field}={$rec[$field]}";
									$merged=1;
								}
                            }
                            else{
								$importmsg[]= "No Records found where {$field}={$rec[$field]}";
                            }
                        }
                    }
					if($merged==0){
						$ok=addDBRecord($rec);
						if(!isNum($ok)){
							$importmsg[]= $ok;
						}
						else{
							$importmsg[]= "Added New Record {$ok}";
						}
					}
                }
            }
        }
    }
    return $importmsg;
}
//---------- begin function int8ToTime--------------------
/**
* @describe converts Integer8 to unix timestamps to be used in the date function
*	LDAP stores times as Integer8 values -  a 64-bit number representing
*	the date as the number of 100 nanosecond intervals since 12:00 am January 1, 1601.
*	This value is converted to a date. The last logon date is in UTC (Coordinated Univeral Time).
*	It must be adjusted by the Time Zone bias in the machine registry to convert to local time.
*	This function converts Integer8 to unix timestamps to be used in the date function.
* @param int8string string
* @return integer - unix timestamp
* @usage $unix_timestamp=int8ToTime($ldaptime);
*/
function int8ToTime($int8){
	// divide value by 10.000.000
	$t = substr($int8,0, strlen($int8)-7);
	$t -= 11644473600;
	return $t;
	}
//---------- begin function getCharset--------------------
/**
* @describe returns the correct charset for special symbols
* @param str string
* @return string
* @usage getCharset('trademark');
*/
function getCharset($str=''){
	$charset='';
	if(!strlen($str)){return '';}
	$str=strtolower($str);
	switch ($str){
		case 'trademark':$charset='&'.'trade'.';';break;
		case 'register':$charset='&'.'reg'.';';break;
		case 'copyright':$charset='&'.'copy'.';';break;
		case 'quote':$charset='&'.'quot'.';';break;
		default:
			$charset='&'.$str.';';
			break;
    	}
	return $charset;
	}
//---------- begin function getGravatar--------------------
/**
 * @describe returns the Gravatar URL for a specified email address.
 * @param string $email The email address
 * @param array $params
 *		size - numeric Size in pixels, defaults to 80px [ 1 - 2048 ]
 *		set - imageset to use, defaults to mm [ 404 | mm | identicon | monsterid | wavatar ]
 *		rating - max rating, defaults to g [ g | pg | r | x ]
 * @return String containing either just a URL or a complete image tag
 * @source https://gravatar.com/site/implement/images/php/
 * @usage <img src="&lt;?=getGravatar('some.email@gmail.com');?&gt;" alt="gravatar" >
 */
function getGravatar($email,$params=array()){
	if(!isset($params['size'])){$params['size']=80;}
	//check for valid size
	if(!isNum($params['size']) || $params['size'] < 1 || $params['size'] > 2048){
		$params['size']=80;
	}
	if(!isset($params['set'])){$params['set']='mm';}
	//check for a valid set value
	$valid_sets=array('404','mm','identicon','monsterid','wavatar');
	if(!in_array($params['set'],$valid_sets)){$params['set']='mm';}
	if(!isset($params['rating'])){$params['rating']='g';}
	//check for valid rating
	$valid_ratings=array('g','pg','r','x');
	if(!in_array($params['rating'],$valid_ratings)){$params['rating']='g';}
	//build md5 string
	$md5str=md5(strtolower(trim($email)));
	//return url
	return "https://secure.gravatar.com/avatar/{$md5str}/?s={$params['size']}&d={$params['set']}&r={$params['rating']}";
}
//---------- begin function getGUID--------------------
/**
* @describe gets a unique GUID for this user, setting it if it does not exist
* @param force boolean - force a new guid - defaults to false
* @return string guid value
* @usage $guid=getGUID();
*/
function getGUID($force=0){
	global $CONFIG;
	if($force != 1 && isset($_COOKIE['GUID'])){
		$guid=$_COOKIE['GUID'];
		$_SERVER['GUID']=$guid;
		return $guid;
		}
	if(isset($_SERVER['PHPSESSID'])){
		$t1=$_SERVER['PHPSESSID'];
		$t2=(integer)(rand(0,round((microtime(true)/1000),0)));
		$t3=microtime(true);
		$guid=sha1($t1+$t2+$t3);
		}
	elseif(function_exists('session_id')){
    	$t1 = (integer)session_id();
    	$t2=(integer)(rand(0,round((microtime(true)/1000),0)));
		$t3=microtime(true);
		$guid=sha1($t1+$t2+$t3);
	}
	else{
		$envs=array('REMOTE_ADDR','REMOTE_PORT','HTTP_HOST','UNIQUE_ID','HTTP_USER_AGENT');
		$gstr='';
		foreach($envs as $env){
			$_SERVER['GUID_PARTS'][$env]=$_SERVER[$env];
			if(strlen($_SERVER[$env])){$gstr .= $_SERVER[$env];}
		}
		$t1=$gstr;
		$t2=(integer)(rand(0,round((microtime(true)/1000),0)));
		$t3= microtime(true);
		$guid=sha1($t1+$t2+$t3);
	}
	//expire in a year
	$expire=time()+(3600*24*365);
	$ok=commonSetCookie("GUID", $guid, $expire);
	$_SERVER['GUID']=$guid;
	return $guid;
}
//---------- begin function commonSetCookie---------------------------------------
/**
* @describe sets a cookie
* @param name string
* @param value string
* @param [expire] timestamp - defaults to 1 year in the future
* @return ok boolean
* @usage $guid=commonSetCookie("MYCOOKIE",$value);
*/
function commonSetCookie($name,$value,$expire=''){
	global $CONFIG;
	if(!strlen($expire)){
		$expire=time()+(3600*24*365);
	}
	if(!isset($value) || !strlen($value)){$value='';}
	if(isset($CONFIG['session_domain'])){
		//setcookie(    $name, $value, $expire, $path, $domain, $secure, $httponly )
		/*Note: SameSite fix -  https://stackoverflow.com/questions/39750906/php-setcookie-samesite-strict */
		$samesite=isset($CONFIG['samesite'])?$CONFIG['samesite']:'Strict';
		if(PHP_VERSION_ID < 70300) {
			//name,value,expire,path,domain,secure,httponly
			setcookie($name, $value, $expire, "/; samesite={$samesite}", ".{$CONFIG['session_domain']}",isSSL(),true);
		}
		else{
			//name,value,expire,path,domain,secure,httponly,samesite
			setcookie($name, $value, array(
				'expires'	=> $expire,
				'path'		=> '/',
				'domain'	=> ".{$CONFIG['session_domain']}",
				'secure'	=> isSSL(),
				'httponly'	=> true,
				'samesite'	=>$samesite
			));	
		}
	}
	else{
		$samesite=isset($CONFIG['samesite'])?$CONFIG['samesite']:'Lax';
		if(PHP_VERSION_ID < 70300) {
    		setcookie($name, $value, $expire, "/; samesite={$samesite}",'',isSSL(),true);
    	}
    	else{
    		setcookie($name, $value, array(
				'expires'	=> $expire,
				'path'		=> '/',
				'domain'	=> '',
				'secure'	=> isSSL(),
				'httponly'	=> true,
				'samesite'	=>$samesite
			));
    	}
	}
	return true;
}
//---------- begin function getFileContentId---------------------------------------
/**
* @describe returns a unique content ID based on the shaw hash - 40 char hexadecimal number
* @param file string
*	full path and name of the file to inspect
* @return string a unique content ID based on the shaw hash - 40 char hexadecimal number
* @usage $guid=getFileContentId($afile);
*/
function getFileContentId($file){
	$name=getFileName($file);
	//get shaw hash - 40-character hexadecimal number
	$shaw=sha1_file($file);
	return $name . '@'. $shaw;
	}
//---------- begin function getFileLineCount---------------------------------------
/**
* @describe returns a number of lines in a file - efficient even for very large files
* @param file string
*	full path and name of the file to inspect
* @param regex string
*	regular expression to filter line count by so that it only counts lines if they match
* @return string number of lines in a file - efficient even for very large files
* @usage
*	$line_cnt=getFileLineCount($afile);
*/
function getFileLineCount($file,$regex='',$i=1){
	if(strlen($regex)){$linecnt=array();}
	else{$linecnt=0;}
	if ($fh = fopen($file,'r')) {
		while (!feof($fh)) {
			//stream_get_line is significantly faster than fgets
			$line = stream_get_line($fh, 1000000, "\n");
			if(strlen($regex)){
				if(preg_match($regex,$line,$m)){
                	$linecnt[$m[$i]]++;
				}
				$linecnt['_all']++;
			}
			else{
				$linecnt++;
			}
		}
		fclose($fh);
	}
	return $linecnt;
}
//---------- begin function getFileContentsPartial---------------------------------------
/**
* @describe returns partial contents of file
* @param file string
*	full path and name of the file to inspect
* @param begin integer - line to start with - default is 0
* @param end integer - line to end with - default is 200
* @return string partial contents of file
* @usage
*	$sample=getFileContentsPartial($afile,0,300);
*/
function getFileContentsPartial($file,$begin=0,$end=200){
	$content='';
	$linecnt=0;
	if ($fh = fopen($file,'r')) {
		while (!feof($fh)) {
			//stream_get_line is significantly faster than fgets
			$content .= stream_get_line($fh, 1000000, "\n");
			$linecnt++;
			if($linecnt >= $end){
				break;
			}
		}
		fclose($fh);
	}
	return $content;
}

//---------- begin function processFileLines---------------------------------------
/**
* @describe sends each line of a file through specified function for processing
* @param file string
*	full path and name of the file to inspect
* @param function_name string
*	name of the function to pass content of lines to.
*	It will send an array with the following keys:
*		file - name of file
*		line_number - line number in file
*		line - line contents
* @param params array
*	[-start] - line to start on
*	[-stop]  - line to stop on
*	[-mod] string - mod,val  only process lines that have a mod of value
*	any additional key/values passed in will be passed through to the function
* @return number of lines processed
* @usage
*	$num=processFileLines($afile,'processLine');
*	$num=processFileLines($afile,'processLine',array('-mod'=>'4,1'));
*/
function processFileLines($file,$func_name,$params=array()){
	//validate function exists
	if(!function_exists($func_name)){
		return 'invalid function:'.$func_name;
	}
	if(isset($params['-mod'])){
		if(!is_array($params['-mod'])){
			list($m,$v)=preg_split('/\,/',$params['-mod'],2);
			$params['-mod']=array('mod'=>$m,'val'=>$v);
		}
	}
	$linecnt = 0;
	if ($fh = fopen($file,'r')) {
		while (!feof($fh)) {
			//stream_get_line is significantly faster than fgets
			$line = stream_get_line($fh, 1000000, "\n");
			//startline and stopline
			if(isset($params['-start']) && $linecnt < $params['-start']-1){
				$linecnt++;
				continue;
			}
			elseif(isset($params['-stop']) && $linecnt >= $params['-stop']-1){
				$linecnt++;
				break;
			}
			//-mod
			if(isset($params['-mod']['mod']) && $linecnt % $params['-mod']['mod'] != $params['-mod']['val']){
				$linecnt++;
				continue;
			}
			//build an array with this line and some general info about where we are
			$set=array(
				'file'			=> $file,
				'line_number'	=> $linecnt,
				'line'			=> $line
			);
			foreach($params as $key=>$val){
				if(preg_match('/^(\-start|\-stop|\-mod)$/i',$key)){continue;}
            	$set[$key]=$val;
			}
			//pass array to function
			$ok=call_user_func($func_name,$set);
			$linecnt++;
		}
		fclose($fh);
	}
	return $linecnt;
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonJSV2CSV($jsvfile){
	global $jsvcsvfile;
	$jsvcsvfile=str_replace('.jsv','.csv',$jsvfile);
	$num=processFileLines($jsvfile,'commonJSV2CSVLine');
	return $jsvcsvfile;
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonJSV2CSVLine($line){
	global $jsvcsvfile;
	//echo $jsvcsvfile.printValue($line);exit;
	if($line['line_number']==0){
		$fields=decodeJson($line['line'],true);
		//echo "fields:".printValue($fields);exit;
		$ok=file_put_contents($jsvcsvfile,implode(',',$fields).PHP_EOL);
	}
	elseif(strlen($line['line'])){
		//echo "yoyo";
		$vals=decodeJson($line['line'],true);
		//echo "VALS:".printValue($vals);
		//fix utf-8
		foreach($vals as $i=>$val){
			if(stringContains($val,"\\u")){
				$vals[$i]=iconv('ASCII', 'UTF-8//IGNORE', $val);
			}
		}
		$row=csvImplode($vals);
		$ok=file_put_contents($jsvcsvfile,$row.PHP_EOL,FILE_APPEND);
	}
}
//---------- begin function processCSVFileLines---------------------------------------
/**
* @describe alias to processCSVLines for backward compatibility
* @exclude - alias
*/
function processCSVFileLines($file,$func_name,$params=array()){
	if(isset($params['fields']) && !isset($params['-fields'])){$params['-fields']=$params['fields'];}
	if(isset($params['map']) && !isset($params['-map'])){$params['-map']=$params['map'];}
	return processCSVLines($file,$func_name,$params);
}
//---------- begin function processCSVLines---------------------------------------
/**
* @describe sends each line of a CSV file through specified function for processing
* @param file string
*	full path and name of the file to inspect
* @param function_name string
*	name of the function to pass content of lines to.
*	It will send an array with the following keys:
*		file - name of file
*		line_number - line number in file
*		processtime - microtime of when the row was processed
*		line - CSV array based on first fields
* @param params array
*	[-maxlen] int - max row length. defaults to 1000000
*	[-separator] char - defaults to ,
*	[-enclose] char - defaults to "
*	[-fields]  mixed - an array of fields (or comma separated list) for the CSV.  If not specified it will use the first line of the file for field names
*	[-fieldsmap]  mixed - an array of fields based on the first value in the CSV ROW.this allows for a single csv to have multiple different fields based on teh row.
*	[-start|skiprows] int - line to start on
*	[-maxrows|stop]  int - line to stop on
*	[-map] array - fieldname map  i.e. ('first name'=>'firstname','fname'=>'firstname'.....)
*	[-mod] string - mod,val  only process lines that have a mod of value
*	[-addfields] array key/value pairs - add these to each row in the line array as if it were in the csv
*	any additional key/values passed in will be passed through to the function
* @return number of lines processed
* @usage
*	$num=processCSVLines($afile,'processLine');
*	$num=processCSVLines($afile,'processLine',array('-mod'=>'4,1'));
*/
function processCSVLines($file,$func_name,$params=array()){
	//validate function exists
	if(!function_exists($func_name)){
		return 'invalid function:'.$func_name;
		}
	if(!isset($params['-maxlen'])){$params['-maxlen']=0;}
	if(!isset($params['-separator'])){$params['-separator']=',';}
	if(!isset($params['-enclose'])){$params['-enclose']='"';}
	if(isset($params['-skiprows']) && !isset($params['-start'])){$params['-start']=$params['-skiprows'];}
	if(isset($params['-stop']) && !isset($params['-maxrows'])){$params['-maxrows']=$params['-stop'];}
	if(isset($params['-mod'])){
		if(!is_array($params['-mod'])){
			list($m,$v)=preg_split('/\,/',$params['-mod'],2);
			$params['-mod']=array('mod'=>$m,'val'=>$v);
		}
	}
	//ini_set('auto_detect_line_endings',TRUE);
	$linecnt = 0;
	$bomchecked=0;
	setlocale(LC_ALL, 'en_US.UTF-8');
	$chunk=array();
	$passthru=array();
	foreach($params as $key=>$val){
    	$passthru[$key]=$val;
	}
	if($fh = fopen_utf8($file,'r')){
		//get the fields
		if(isset($params['-fields'])){
			if(!is_array($params['-fields'])){
				$fields=preg_split('/\,/',$params['-fields']);
			}
			foreach($params['-fields'] as $k=>$fld){
				$fields[$k]=trim($fld);
			}
		}
		else{
			$fields=array();
		}
		//fieldsmap?
		if(isset($params['-fieldsmap'])){
			foreach($params['-fieldsmap'] as $k=>$v){
				$k=strtolower($k);
				if(!is_array($v)){
					$params['-fieldsmap'][$k]=preg_split('/\,/',$v);
				}
			}
		}
		while ( ($lineparts = fgetcsv($fh, $params['-maxlen'], $params['-separator'],$params['-enclose']) ) !== FALSE ) {
			if(is_null($lineparts[0])){continue;}
			if($bomchecked==0){
				$lineparts[0]=str_replace("\xEF\xBB\xBF",'',$lineparts[0]);
				$bomchecked=1;
			}
			if(count($fields)==0){
				$fields=$lineparts;
				//remove spaces and weird chars
				foreach($fields as $x=>$field){
					if(isset($params['-map']) && isset($params['-map'][$fields[$x]])){
						$fields[$x]=$params['-map'][$fields[$x]];
					}
					else{
						if($fields[$x]=='#'){$fields[$x]='row';}
						$fields[$x]=preg_replace('/\ \%$/','_pcnt',trim($fields[$x]));
						$fields[$x]=preg_replace('/\ \#$/','_num',trim($fields[$x]));
						$fields[$x]=preg_replace('/[\.\-\s]+/','_',trim($fields[$x]));
						$fields[$x]=preg_replace('/[^a-z0-9\_]+/i','',$fields[$x]);
						$fields[$x]=strtolower($fields[$x]);
					}
				}
				continue;
			}
			if(isset($params['-mod']['mod']) && $linecnt % $params['-mod']['mod'] != $params['-mod']['val']){
				$linecnt++;
				continue;
			}
	        if(isset($params['-start']) && $linecnt < $params['-start']-1){
				$linecnt++;
				continue;
			}
	        $set=array(
				'file'			=> $file,
				'line_number'	=> $linecnt,
				'processtime'	=> microtime(true),
				'line'			=> array()
			);
			foreach($params as $key=>$val){
				if(stringBeginsWith($key,'-')){continue;}
            	$set[$key]=$val;
			}
			if(isset($params['-addfields']) && is_array($params['-addfields'])){
				foreach($params['-addfields'] as $key=>$val){
					if(isset($set['line'][$key]) && strlen($set['line'][$key])){continue;}
					$set['line'][$key]=$val;		
				}
			}
			//process function?
			$first_line_val=strtolower($lineparts[0]);
			if(isset($params['-fieldsmap']) && !isset($params['-fieldsmap'][$first_line_val])){
				unset($set);
				continue;
			}
			if(isset($params['-fieldsmap']) && isset($params['-fieldsmap'][$first_line_val])){
				$cfields=$params['-fieldsmap'][$first_line_val];
			}
			else{
				$cfields=$fields;
			}
			foreach($cfields as $x=>$field){
				if(!isset($lineparts[$x])){continue;}
				$set['line'][$field]=$lineparts[$x];
			}
			//pass array to function
			if(isset($params['-chunk'])){
				$chunk[]=$set['line'];
				if(count($chunk)==(integer)$params['-chunk']){
					$ok=call_user_func($func_name,$chunk,$passthru);
					$chunk=array();
				}
			}
			else{
				$ok=call_user_func($func_name,$set,$passthru);	
			}
			
			unset($set);
			$linecnt++;
			if(isset($params['-maxrows']) && isNum($params['-maxrows']) && $linecnt >= $params['-maxrows']){
				break;
			}
	    }
	    fclose($fh);
	    if(count($chunk)){
			$ok=call_user_func($func_name,$chunk,$passthru);
		}
	}
	return $linecnt;
}
//---------- begin function getCSVRecords---------------------------------------
/**
* @describe returns csv file contents as recordsets
* @param file string
*	full path and name of the file to inspect
* @param params array
*	[-function] str - function to send each rec to as it processes the csv file
*	[-maxlen] int - max row length. defaults to 0
*	[-separator] char - defaults to ,
*	[-enclose] char - defaults to "
*	[-escape] char - defaults to blank
*	[-fields]  array - an array of fields for the CSV.  If not specified it will use the first line of the file for field names
* 	[-listfields] mixed - comman separated list of fields to return
*	[-start|skiprows] int - line to start on
*	[-maxrows|stop] int - max number of rows to return
*	[-map] array - fieldname map  i.e. ('first name'=>'firstname','fname'=>'firstname'.....)
*	any additional key/values passed in will filter your results
* @return array - recordsets
* @usage
*	$recs=getCSVRecords($afile);
*	$recs=getCSVRecords($afile,array('age'=>37,'active'=>1));
*/
function getCSVRecords($file,$params=array()){
	if(!isset($params['-maxlen'])){$params['-maxlen']=1000000;}
	if(!isset($params['-separator'])){$params['-separator']=',';}
	if(!isset($params['-enclose'])){$params['-enclose']='"';}
	if(!isset($params['-escape'])){$params['-escape']='';}
	$parts=explode('.',phpversion());
	$phpver=(float)"{$parts[0]}.{$parts[1]}";
	if($phpver <=  7.3){
		$params['-escape']='\\';
	}
	if(isset($params['-skiprows']) && !isset($params['-start'])){$params['-start']=$params['-skiprows'];}
	if(isset($params['-stop']) && !isset($params['-maxrows'])){$params['-maxrows']=$params['-stop'];}
	//backward compatibility
	if(isset($params['fields'])){$params['-fields']=$params['fields'];}
	if(isset($params['-fields'])){
		if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,/',$params['-fields']);}
	}
	if(isset($params['-listfields'])){
		if(!is_array($params['-listfields'])){$params['-listfields']=preg_split('/\,/',$params['-listfields']);}
	}
	else{$params['-listfields']=array();}
	//ini_set('auto_detect_line_endings',TRUE);
	$recs=array();
	$linecnt = 0;
	$bomchecked=0;
	setlocale(LC_ALL, 'en_US.UTF-8');
	if($fh = fopen_utf8($file,'r')){
		//get the fields
		if(isset($params['-fields']) && is_array($params['-fields'])){
			$fields=$params['-fields'];
		}
		else{
			$fields=array();
		}
		while ( ($lineparts = fgetcsv($fh, $params['-maxlen'], $params['-separator'],$params['-enclose'],$params['-escape']) ) !== FALSE ) {
			if($bomchecked==0){
				$lineparts[0]=str_replace("\xEF\xBB\xBF",'',$lineparts[0]);
				$bomchecked=1;
			}
			if(count($fields)==0){
				$fields=$lineparts;
				//remove spaces and weird chars
				foreach($fields as $x=>$field){
					if(isset($params['-map']) && isset($params['-map'][$fields[$x]])){
						$fields[$x]=$params['-map'][$fields[$x]];
					}
					else{
						if($fields[$x]=='#'){$fields[$x]='row';}
						$fields[$x]=preg_replace('/\ \%$/','_pcnt',trim($fields[$x]));
						$fields[$x]=preg_replace('/\ \#$/','_num',trim($fields[$x]));
						$fields[$x]=preg_replace('/[\.\-\s]+/','_',trim($fields[$x]));
						$fields[$x]=preg_replace('/[^a-z0-9\_]+/i','',$fields[$x]);
						$fields[$x]=strtolower($fields[$x]);
					}
				}
				continue;
			}
	        if(isset($params['-start']) && $linecnt < $params['-start']-1){
				$linecnt++;
				continue;
			}
	        $rec=array();
			if(isset($params['-id'])){
				$rec['_id']=$linecnt;
			}
			if(isset($params['-file'])){
				$rec['_file']=$file;
			}
			foreach($fields as $x=>$field){
				if(count($params['-listfields']) && !in_array($field,$params['-listfields'])){continue;}
				$val=$lineparts[$x];
				$rec[$field]=$lineparts[$x];
			}
			if(isset($params['-function']) && strlen($params['-function']) && function_exists($params['-function'])){
				$ok=call_user_func($params['-function'],$rec);	
				if(is_array($ok)){$rec=$ok;}
			}
			//filters
			$skip=0;
			foreach($params as $key=>$val){
				if(stringBeginsWith($key,'-')){continue;}
            	if(!isset($rec[$key])){$skip=1;break;}
            	if(isset($rec[$key]) && strtolower($rec[$key]) != strtolower($val)){
            		$skip=1;break;
            	}
			}
			if($skip==1){continue;}
			$linecnt++;
			if(isset($params['-index'])){
				$idx=$rec[$params['-index']];
				$recs[$idx]=$rec;
			}
			else{
				$recs[]=$rec;
			}
			
			if(isset($params['-maxrows']) && isNum($params['-maxrows']) && count($recs) >= $params['-maxrows']){
				//echo "linecnt:{$linecnt}, maxrows:{$params['-maxrows']}<br>";
				break;
			}
	    }
	    fclose($fh);
	}
	else{
		return "getCSVRecords error: failed to open {$file}";
	}
	return $recs;
}
//---------- begin function getCSVSchema---------------------------------------
/**
* @describe returns suggested schema for data in csv file
* @param file string
*	full path and name of the file to inspect
* @param params array
*	[-maxlen] int - max row length. defaults to 1000000
*	[-separator] char - defaults to ,
*	[-enclose] char - defaults to "
*	[-fields]  array - an array of fields for the CSV.  If not specified it will use the first line of the file for field names
*	[-start|skiprows] int - line to start on
*	[-stop|maxrows] int - max number of rows to check
* @return array schema fields, including wasql fields.  e.g. array('_cdate'=> "datetime NOT NULL",.....)
* @usage
*	$fields=getCSVSchema($afile);
*/
function getCSVSchema($file,$params=array()){
	if(!isset($params['-maxlen'])){$params['-maxlen']=1000000;}
	if(!isset($params['-separator'])){$params['-separator']=',';}
	if(!isset($params['-enclose'])){$params['-enclose']='"';}
	if(isset($params['-skiprows']) && !isset($params['-start'])){$params['-start']=$params['-skiprows'];}
	ini_set('auto_detect_line_endings',TRUE);
	$linecnt = 0;
	$bomchecked=0;
	setlocale(LC_ALL, 'en_US.UTF-8');
	$properties=array();
	if($fh = fopen_utf8($file,'r')){
		//get the fields
		if(isset($params['fields']) && is_array($params['fields'])){
			$fields=$params['fields'];
		}
		else{
			$fields=array();
		}
		while ( ($lineparts = fgetcsv($fh, $params['-maxlen'], $params['-separator'],$params['-enclose']) ) !== FALSE ) {
			if($bomchecked==0){
				$lineparts[0]=str_replace("\xEF\xBB\xBF",'',$lineparts[0]);
				$bomchecked=1;
			}
			if(count($fields)==0){
				// Using iconv to convert CSV header row to ASCII with TRANSLIT//IGNORE will remove/transliterate diacritics. There is no need to detect $from_encoding because the internal method fopen_utf8 (used above) yields UTF-8 data. NOTE: Locale must be set using setlocale (already done above) for consistent results across platforms/installs/versions, otherwise transliteration will replace characters with "?".
				$lineparts=implode($params['-separator'], $lineparts);
				$lineparts=iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lineparts); // NOTE: Warning: This will break the delimited string if the separator is not an ASCII character!
				$lineparts=explode($params['-separator'], $lineparts);
				$fields=$lineparts;
				//remove spaces and weird chars
				foreach($fields as $x=>$field){
					if(isset($params['-map']) && isset($params['-map'][$fields[$x]])){
						$fields[$x]=$params['-map'][$fields[$x]];
					}
					else{
						if($fields[$x]=='#'){$fields[$x]='row';}
						$fields[$x]=preg_replace('/\ \%$/','_pcnt',trim($fields[$x]));
						$fields[$x]=preg_replace('/\ \#$/','_num',trim($fields[$x]));
						$fields[$x]=preg_replace('/[\.\-\s]+/','_',trim($fields[$x]));
						$fields[$x]=preg_replace('/[^a-z0-9\_]+/i','',$fields[$x]);
						$fields[$x]=strtolower($fields[$x]);
					}
				}
				continue;
			}
	        if(isset($params['-start']) && $linecnt < $params['-start']-1){
				$linecnt++;
				continue;
			}
			foreach($fields as $x=>$field){
				$val=trim($lineparts[$x]);
				if($val=='null'){$val='';}
				if(!strlen($val)){continue;}
				if(!isset($properties[$field])){
					$properties[$field]['maxlen']=strlen($val);
					$properties[$field]['maxval']=$val;
					if(strlen($val)){
						$type='varchar';$precision=0;
						if(strlen($val)==1 && preg_match('/^[0-9]$/',$val)){$type='int';}
						elseif(ctype_digit($val)){$type='int';}
						elseif(ctype_alpha($val)){$type='varchar';}
						elseif(preg_match('/^[0-9\.]+$/',$val)){
							$type='float';
							$precision=strlen(substr(strrchr($val, "."), 1));
						}
						elseif(isDateTime($val)){
							$type='datetime';
						}
						elseif(isDate($val)){
							$type='date';
						}
						$properties[$field]['type']=$type;
						$properties[$field]['precision']=$precision;
						$properties[$field]['first_type']=$type;
						$properties[$field]['first_val']=$val;
					}
					else{
						$properties[$field]['type']='varchar';
					}
				}
				elseif(strlen($val)){
					if(strlen($val) > $properties[$field]['maxlen']){
						$properties[$field]['maxlen']=strlen($val);
						$properties[$field]['maxval']=$val;		
					}
					$type='varchar';$precision=0;
					if(strlen($val)==1 && preg_match('/^[0-9]$/',$val)){$type='int';}
					elseif(ctype_digit($val)){$type='int';}
					elseif(ctype_alpha($val)){$type='varchar';}
					elseif(preg_match('/^[0-9\.]+$/',$val)){
						$type='float';
						$precision=strlen(substr(strrchr($val, "."), 1));
					}
					elseif(isDateTime($val)){
						$type='datetime';
					}
					elseif(isDate($val)){
						$type='date';
					}
					if($type != $properties[$field]['type']){
						$oldtype=$properties[$field]['type'];
						switch($oldtype){
							case 'int':
								if($type=='float'){
									$properties[$field]['type']=$type;
									$properties[$field]['precision']=$precision;
									$properties[$field]['switches'][]="val:{$val}, oldtype:{$oldtype}, newtype:{$type}";
								}
								else{
									$type='varchar';
									$properties[$field]['type']=$type;
									$properties[$field]['switches'][]="val:{$val}, oldtype:{$oldtype}, newtype:{$type}";
								}
							break;
							case 'float':
								if($type !='int'){
									$type='varchar';
									$properties[$field]['type']=$type;
									$properties[$field]['switches'][]="val:{$val}, oldtype:{$oldtype}, newtype:{$type}";
								}
							break;
							case 'date':
								if($type=='datetime'){
									$properties[$field]['type']=$type;
									$properties[$field]['switches'][]="val:{$val}, oldtype:{$oldtype}, newtype:{$type}";
								}
								else{
									$type='varchar';
									$properties[$field]['type']=$type;
									$properties[$field]['switches'][]="val:{$val}, oldtype:{$oldtype}, newtype:{$type}";
								}
							break;
							case 'datetime':
								if($type !='date'){
									$type='varchar';
									$properties[$field]['type']=$type;
									$properties[$field]['switches'][]="val:{$val}, oldtype:{$oldtype}, newtype:{$type}";
								}
							break;
						}
						if(count($properties[$field]['switches']) > 10){
							echo $field.printValue($properties[$field]['switches']);exit;
						}
					}
				}
				
			}
			$linecnt++;
			if(isset($params['-maxrows']) && isNum($params['-maxrows']) && $linecnt >= $params['-maxrows']){
				break;
			}
	    }
	    fclose($fh);
	}
	$fields=array(
		'_id'	=> function_exists('databasePrimaryKeyFieldString')?databasePrimaryKeyFieldString():'autoincrement primary key',
		'_cdate'=> "datetime NOT NULL",
		'_cuser'=> "int NOT NULL",
		'_edate'=> "datetime NULL",
		'_euser'=> "int NULL",
		);
	foreach($properties as $fld=>$info){
		switch($info['type']){
			case 'int':
			case 'integer':
				$fields[$fld]="integer NULL";
			break;
			case 'float':
				$fields[$fld]="float({$info['maxlen']},{$info['precision']}) NULL";
			break;
			case 'datetime':
				$fields[$fld]="datetime NULL";
				break;
			case 'date':
				$fields[$fld]="date NULL";
				break;
			default:
				//maxlen rounded up to nearest 5
				$max=round(($info['maxlen']+5/2)/5)*5;
				if($max > 2000){$fields[$fld]="text NULL";}
				elseif($max < 11){$fields[$fld]="char({$max}) NULL";}
				else{$fields[$fld]="varchar({$max}) NULL";}
				break;
        }
    }
	return $fields;
}
//---------- begin function getDirRecords---------------------------------------
/**
* @describe returns files in dir  as recordsets
* @param file string
*	full path and name of the directory
* @param params array
*	[-function] str - function to send each rec to as it processes
* 	[-fields] mixed - comman separated list of fields to return
*	[-limit] int - max number of rows to return
*	[-offset] int - number to skip 
*	[-index] str - field to use as the resulting array index
*	[-map] array - fieldname map  i.e. ('first name'=>'firstname','fname'=>'firstname'.....)
*	[-dateformat] - return date format
*	[-perms] - limit results to files this these permissions
*	[-lines] - return the line count of each file returned (slow)
*	[-sha] - return the sha1 value of each file returned
* 	[-md5] - return the md5 value of each file returned
* 	[-exif] - return the exif value of each file returned  (slow)
*	any additional key/values passed in will filter your results. 
*	Note: type will default to file if not specified
* @return array - recordsets with the following fields:
*     _adate,_adate_age,_adate_age_verbose,_adate_utime,
*     _cdate,_cdate_age,_cdate_age_verbose,_cdate_utime,
*     _edate,_edate_age,_edate_age_verbose,_edate_utime,
*     afile,ext,name,path,size,size_verbose,type
*   Optional fields returned when requested
*     lines,perm_execute,perm_read,perm_write,perms,sha,md5,exif,exif_count
* @usage
*	$recs=getDirRecords($dir);
*	$recs=getDirRecords($dir,array('ext'=>'jpg|png','size'=>'>150000'));
*	$recs=getDirRecords($dir,array('-limit'=>3,'-lines'=>1,'ext'=>'php|html|md','-recurse'=>1,'lines'=>'>5000'));
*/
function getDirRecords($dir,$params=array()){
	if(isset($params['-stop']) && !isset($params['-maxrows'])){$params['-maxrows']=$params['-stop'];}
	$filters=array();
	$includes=array('-dateformat','-perms','-lines','-sha','-md5','-recurse','-exif');
	foreach($params as $key=>$val){
		if(stringBeginsWith($key,'-') && !in_array($key,$includes)){continue;}
		$filters[$key]=$val;
	}
	$allrecs=listFilesEx($dir,$filters);
	$recs=array();
	$linecnt = 0;
	if(is_array($allrecs)){
		foreach($allrecs as $rec){
	        if(isset($params['-offset']) && $linecnt < $params['-offset']-1){
				$linecnt++;
				continue;
			}
			if(isset($params['-function']) && strlen($params['-function']) && function_exists($params['-function'])){
				$ok=call_user_func($params['-function'],$rec);	
				if(is_array($ok)){$rec=$ok;}
			}
			$linecnt++;
			if(isset($params['-index'])){
				$idx=$rec[$params['-index']];
				$recs[$idx]=$rec;
			}
			else{
				$recs[]=$rec;
			}
			
			if(isset($params['-limit']) && isNum($params['-limit']) && count($recs) >= $params['-limit']){
				break;
			}
	    }
	}
	return $recs;
}
//---------- begin function fopen_utf8 ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function fopen_utf8($filename){
	$encoding='';
	$handle = fopen($filename, 'rb');
	if(!$handle){
		return null;
	}
	
	$bom = fread($handle, 2);
	rewind($handle);

	if($bom === chr(0xff).chr(0xfe)  || $bom === chr(0xfe).chr(0xff)){
		// UTF16 Byte Order Mark present
		$encoding = 'UTF-16';
	} else {
		try{
			$parts=explode('.',phpversion());
			$phpver=(float)"{$parts[0]}.{$parts[1]}";
			if($phpver <  7.3){
				$file_sample = @fread($handle, 1000) + 'e'; //read first 1000 bytes
				// + e is a workaround for mb_string bug
			}
			else{
				$file_sample = @fread($handle, 1000);
			}
			
			rewind($handle);
			$encoding = mb_detect_encoding($file_sample , mb_detect_order(),true);
		}
		catch(Exception $e){
			$encoding = 'UTF-8';
		}	
	}
	//if(!strlen($encoding)){return ($handle);}
	switch(strtoupper($encoding)){
		case 'UTF-8':
		case 'ASCII':
		case '':
			return ($handle);
		break;
	}
	stream_filter_append($handle, 'convert.iconv.'.$encoding.'/UTF-8');
	return ($handle);

}
	//---------- begin function csv2Arrays--------------------
/**
* @describe returns the contents of a CSV string as an array
* @param data mixed - lines of csv data as either a string or an array
* @param params array
*	[separator] - defaults to ,
*	[fields] - if not supplied uses the first rows as field names
*	[skiprows] - starting row number
* @return array
* @usage $csv=csv2Arrays($data);
*/
function csv2Arrays($lines,$params=array()){
	if(!is_array($lines)){$lines=preg_split('/[\r\n]+/',trim($lines));}
	if(!isset($params['separator'])){
		if(!stringContains($lines[0],',') && stringContains($lines[0],"\t")){
			$params['separator']="\t";
		}
	}
	if(!isset($params['separator'])){$params['separator']=',';}
	if(isset($params['fields']) && is_array($params['fields'])){$xfields=$params['fields'];}
	else{
		$str=array_shift($lines);
		$xfields = csvParseLine($str, $params['separator']);
	}
	if(!is_array($xfields) || count($xfields)==0){
		$results['error']="No Fields found: " . printValue($xfields);
		return $results;
	}
	//skip rows if requested
	if(isset($params['skiprows']) && isNum($params['skiprows'])){
		for($x=0;$x<$params['skiprows'];$x++){
			$junk = array_shift($lines);
		}
	}
	$fields=array();
	foreach($xfields as $field){
		if(isset($params['-lowercase']) && $params['-lowercase']){$field=strtolower($field);}
		if(isset($params['-nospaces']) && $params['-nospaces']){$field=preg_replace('/\ +/','_',trim($field));}
		if(isset($params['-fieldmap'][$field])){
			$field=$params['-fieldmap'][$field];
		}
		$field=trim($field);
		$fields[]=$field;
	}
	$recs=array();
	foreach($lines as $line){
		$vals=csvParseLine($line,$params['separator']);
		$rec=array();
		foreach($fields as $i=>$field){
			$rec[$field]=$vals[$i];
		}
		$recs[]=$rec;
	}
	return $recs;
}
//---------- begin function getCSVFileContents--------------------
/**
* @describe returns the contents of a CSV file as an array
* @param file string - file name and path
* @param params array
*	[maxrows] - defaults to 2000000
*	[maxlen] - defaults to 4096
*	[separator] - defaults to ,
*	[fields] - if not supplied uses the first rows as field names
*	[skiprows] - starting row number
*	[map] - translation map
* @return array
* @usage $csv=getCSVFileContents($file);
*/
function getCSVFileContents($file,$params=array()){
	if(!isset($params['maxrows'])){$params['maxrows']=2000000;}
	if(!isset($params['maxlen'])){$params['maxlen']=0;}
	if(!isset($params['-enclose'])){$params['-enclose']='"';}
	$results=array('file'=>$file,'params'=>$params);
	if(!is_file($file)){
		$results['error']="No such file [$file]";
		return $results;
		}
	$row = 1;
	setlocale(LC_ALL, 'en_US.UTF-8');
	//$handle =  fopen($file, "r");
	$handle = fopen_utf8($file, "rb");
	$results['stat']=fstat($handle);
	//determine separator
	if(!isset($params['separator'])){
		$sample = fgets($handle); //read first 1000 bytes, + e is a workaround for mb_string bug
		rewind($handle);
		if(!stringContains($sample,',') && stringContains($sample,"\t")){
			$params['separator']="\t";
		}
	}
	if(!isset($params['separator'])){$params['separator']=',';}
	if(isset($params['fields']) && is_array($params['fields'])){$xfields=$params['fields'];}
	else{$xfields = fgetcsv($handle, $params['maxlen'], $params['separator'],$params['-enclose']);}
	//echo printValue($xfields);exit;
	if(!is_array($xfields) || count($xfields)==0){
		$results['error']="No Fields found: " . printValue($xfields);
		fclose($handle);
		return $results;
		}
	//skip rows if requested
	if(isset($params['skiprows']) && isNum($params['skiprows'])){
		for($x=0;$x<$params['skiprows'];$x++){
			$junk = fgetcsv($handle, $params['maxlen'], $params['separator'],$params['-enclose']);
			}
		}
	$fields=array();
	foreach($xfields as $field){$fields[]=trim($field);}
	//fix up the field names
	$rows=array();
	$mapfield=array();
	$cnt=count($fields);
	for($x=0;$x<$cnt;$x++){
		if(isset($params['map']) && is_array($params['map']) && strlen($params['map'][$fields[$x]])){
			$fields[$x]=$params['map'][$fields[$x]];
			$mapfield[$fields[$x]]=1;
		}
		else{
			if($fields[$x]=='#'){$fields[$x]='row';}
			$fields[$x]=preg_replace('/\#$/','number',$fields[$x]);
			$fields[$x]=preg_replace('/\-+/','_',$fields[$x]);
			$fields[$x]=preg_replace('/[\s\.]+/','_',$fields[$x]);
			$fields[$x]=preg_replace('/[^a-z0-9\_]+/i','',$fields[$x]);
			$fields[$x]=strtolower($fields[$x]);
		}
    }
    $results['count']=0;
    $results['field_properties']=array();
    $row_ptr=0;
    if(isset($params['unique'])){$unique=array();}

	while (($data = fgetcsv($handle, $params['maxlen'],$params['separator'],$params['-enclose'])) !== FALSE) {
		//$data=csvParseLine($data,$params['separator'],$params['enclose']);
		//echo "data".printValue($data);exit;
		if($results['count'] > $params['maxrows']){break;}
		$row=array();
	    $num = count($data);
	    $collect=1;
	    for ($c=0; $c < count($data); $c++) {
			$val=trim($data[$c]);
			if(isset($params['-utf8_encode']) && $params['-utf8_encode']){
				$val=utf8_encode($val);
			}
			$field=$fields[$c];
			//echo "c:{$c}, Field:{$field}, val:{$val}";exit;
			if(isset($params['map']) && isset($params['maponly']) && $params['maponly'] && !isset($mapfield[$field])){continue;
			}
			if(strlen($val)){
				if(isset($params["{$field}_eval"])){
					$evalstr=$params[$field."_eval"];
					$replace='%val%';
	                $evalstr=str_replace($replace,$val,$evalstr);
	                $val=evalPHP('<?' . $evalstr .'?>');
                }
                $row[$field]=$val;
			}
	    }
	    foreach($params as $key=>$val){
			if(preg_match('/^(.+?)\_filter$/i',$key,$kmatch)){
				if(!isset($row[$kmatch[1]])){$collect=0;}
				if(is_array($val)){
					if(!in_array($row[$kmatch[1]],$val)){$collect=0;}
                }
				elseif($row[$kmatch[1]] != $val){$collect=0;}
            }
            if(preg_match('/^(.+?)\_min$/i',$key,$kmatch)){
				if(!isset($row[$kmatch[1]])){$collect=0;}
				if($row[$kmatch[1]] < $val){$collect=0;}
            }
            if(preg_match('/^(.+?)\_required$/i',$key,$kmatch)){
				if(!isset($row[$kmatch[1]])){$collect=0;}
            }
            if(preg_match('/^(.+?)\_max$/i',$key,$kmatch)){
				if(!isset($row[$kmatch[1]])){$collect=0;}
				if($row[$kmatch[1]] > $val){$collect=0;}
            }
        }
        $row_ptr++;
        //determine the maxlength of each field
        if(isset($params['startrow']) && isNum($params['startrow']) && $params['startrow'] > $row_ptr){continue;}
	    if($collect==1 && count($row)){
			if(isset($params['unique']) && isset($row[$params['unique']])){
				if(!isset($unique[$row[$params['unique']]])){
					$unique[$row[$params['unique']]]=1;
					$results['count']++;
					if(isset($params['addtable']) && strlen($params['addtable'])){
						$row['-table']=$params['addtable'];
						set_time_limit(180);
						$ok=addDBRecord($row);
						if(isset($params['echo']) && $params['echo']){echo "record {$ok}<br >\n";}
						$results['addtable_results'][]=$ok;
	                }
	                else{
						foreach($row as $field=>$val){
							if(isset($params["{$field}_counts"])){$results['counts'][$field][$val]+=1;}
			            }
			            //field_properties
						foreach($row as $field=>$val){
							//maxlength
							if(!isNum($results['field_properties'][$field]['maxlength']) || strlen($val) > $results['field_properties'][$field]['maxlength']){
								$results['field_properties'][$field]['maxlength']=strlen($val);
								$results['field_properties'][$field]['maxlength_value']=$val;
								$results['field_properties'][$field]['maxlength_rownum']=count($rows);
							}
							//minlength
							if(!isNum($results['field_properties'][$field]['minlength']) || strlen($val) < $results['field_properties'][$field]['minlength']){
								$results['field_properties'][$field]['minlength']=strlen($val);
								$results['field_properties'][$field]['minlength_value']=$val;
								$results['field_properties'][$field]['minlength_rownum']=count($rows);
							}
			            	//numeric vs text
			            	if(!isset($results['field_properties'][$field]['type'])){
			            		$results['field_properties'][$field]['type']=isNum($results['field_properties'][$field]['type'])?'numeric':'text';
			            	}
							else{
								if($results['field_properties'][$field]['type']=='numeric' && !isNum($results['field_properties'][$field]['type'])){
									$results['field_properties'][$field]['type']='text';
								}
                            }
						}
						if(isset($params['ksort']) && $params['ksort']){ksort($row);}
						array_push($rows,$row);
					}
				}
			}
			else{
				$results['count']++;
				if(isset($params['addtable']) && strlen($params['addtable'])){
					$row['-table']=$params['addtable'];
					set_time_limit(180);
					$ok=addDBRecord($row);
					if(isset($params['echo']) && $params['echo']){echo "record {$ok}<br >\n";}
					$results['addtable_results'][]=$ok;
                }
                else{
					foreach($row as $field=>$val){
						if(isset($params["{$field}_counts"])){$results['counts'][$field][$val]+=1;}
		            }
		            //field_properties
					foreach($row as $field=>$val){
						//maxlength
						if(isset($results['field_properties'][$field]['maxlength'])){
							if(!isNum($results['field_properties'][$field]['maxlength']) || strlen($val) > $results['field_properties'][$field]['maxlength']){
								$results['field_properties'][$field]['maxlength']=strlen($val);
								$results['field_properties'][$field]['maxlength_rownum']=count($rows);
								$results['field_properties'][$field]['maxlength_value']=$val;
							}
						}
						//minlength
						if(isset($results['field_properties'][$field]['minlength'])){
							if(!isNum($results['field_properties'][$field]['minlength']) || strlen($val) < $results['field_properties'][$field]['minlength']){
								$results['field_properties'][$field]['minlength']=strlen($val);
								$results['field_properties'][$field]['minlength_rownum']=count($rows);
								$results['field_properties'][$field]['minlength_value']=$val;
							}
						}
		            	//numeric vs text
		            	if(!isset($results['field_properties'][$field]['type'])){
		            		$results['field_properties'][$field]['type']='text';
		            	}
						else{
							if($results['field_properties'][$field]['type']=='numeric' && !isNum($results['field_properties'][$field]['type'])){
								$results['field_properties'][$field]['type']='text';
							}
                        }
					}
					if(isset($params['ksort']) && $params['ksort']){ksort($row);}
					array_push($rows,$row);
				}
        	}
		}
	}
	fclose($handle);
	if(isset($results['counts'])){
		if(is_array($results['counts'])){
			ksort($results['counts']);
			foreach($results['counts'] as $field=>$val){
				asort($results['counts'][$field]);
		    }
		}
	}
    if(isset($params['map']) && isset($params['maponly']) && $params['maponly']){
		$results['fields']=array_keys($mapfield);
	}
	else{
		$results['fields']=$fields;
	}
	sort($results['fields']);
	//determine suggested schema
	$fields=array(
		'_id'	=> function_exists('databasePrimaryKeyFieldString')?databasePrimaryKeyFieldString():'autoincrement primary key',
		'_cdate'=> "datetime NOT NULL",
		'_cuser'=> "int NOT NULL",
		'_edate'=> "datetime NULL",
		'_euser'=> "int NULL",
		);
	foreach($results['field_properties'] as $fld=>$info){
		$fld=preg_replace('/[^a-z0-9]+/i','_',$fld);
		switch($info['type']){
			case 'int':
			case 'integer':
				$fields[$fld]="int NULL";
				break;
			case 'datetime':
				$fields[$fld]="datetime NULL";
				break;
			case 'date':
				$fields[$fld]="date NULL";
				break;
			default:
				$max=isset($info['maxlength'])?$info['maxlength']:255;
				if($max > 2000){$fields[$fld]="text NULL";}
				elseif($max < 11){$fields[$fld]="char({$max}) NULL";}
				else{$fields[$fld]="varchar({$max}) NULL";}
				break;
        }
    }
    $results['schema']=$fields;
	if(!isset($params['count_only'])){
		$results['items'] = $rows;
	}
	unset($fields);
	unset($rows);
	return $results;
}
//---------- begin function arrays2SchemaFields-------------------
/**
* @describe returns the schema needed for the records in recs
* @param recs array - array of recs with key/value pairs
* @return array fields needed for schema
* @usage $fields=arrays2SchemaFields($recs);
*/
function arrays2SchemaFields($recs=array()){
	//properties to collect: valcount, minlen, maxlen, maxord, maxdec, numeric, date
	$properties=array();

	foreach($recs as $rec){
    	foreach($rec as $k=>$v){
			//valcount
			if(strlen($v)){$properties[$k]['valcount']+=1;}
			//minlen
			if(!isset($properties[$k]['minlen']) || strlen($v) < $properties[$k]['minlen']){
            	$properties[$k]['minlen']=strlen($v);
			}
			//maxlen
			if(!isset($properties[$k]['maxlen']) || strlen($v) > $properties[$k]['maxlen']){
            	$properties[$k]['maxlen']=strlen($v);
			}
			//type - date, numeric
			if(is_int($v)){$properties[$k]['types']['int']+=1;}
			elseif(is_numeric($v)){
				$properties[$k]['types']['real']+=1;
				list($ord,$dec)=preg_split('/\./',$v);
				//maxord
				if(!isset($properties[$k]['maxord']) || strlen($o) > $properties[$k]['maxord']){
	            	$properties[$k]['maxord']=strlen($o);
				}
				//maxdec
				if(!isset($properties[$k]['maxdec']) || strlen($d) > $properties[$k]['maxdec']){
	            	$properties[$k]['maxdec']=strlen($d);
				}
			}
			elseif(isDateTime($v)){$properties[$k]['types']['datetime']+=1;}
			elseif(isDate($v)){$properties[$k]['types']['date']+=1;}
			elseif(!is_string($v)){$properties[$k]['types']['blob']+=1;}
			else{$properties[$k]['types']['varchar']+=1;}
		}
	}
	$fields=array(
		'_id'	=> function_exists('databasePrimaryKeyFieldString')?databasePrimaryKeyFieldString():'autoincrement primary key',
		'_cdate'=> "datetime NOT NULL",
		'_cuser'=> "int NOT NULL",
		'_edate'=> "datetime NULL",
		'_euser'=> "int NULL",
	);
	foreach($properties as $fld=>$property){
		$fld=preg_replace('/[^a-z0-9]+/i','_',$fld);
		if(count($property['types'])==1){$type=$property['types'][0];}
		switch($type){
			case 'int':
				$fields[$fld]="integer NULL";
			break;
			case 'real':
				$m=$properties[$k]['maxord']+$properties[$k]['maxdec']+1;
				$fields[$fld]="real({$m},{$properties[$k]['maxdec']}) NULL";
			break;
			case 'datetime':
				$fields[$fld]="datetime NULL";
			break;
			case 'date':
				$fields[$fld]="date NULL";
			break;
			default:
				//round the max to the nearest 10th going up
				$max = ceil($property['maxlen'] / 10) * 10;
				if($max > 9000000){$fields[$fld]="longtext NULL";}
				elseif($max > 65000){$fields[$fld]="mediumtext NULL";}
				elseif($max > 2000){$fields[$fld]="text NULL";}
				elseif($max < 11){$fields[$fld]="char({$max}) NULL";}
				else{$fields[$fld]="varchar({$max}) NULL";}
			break;
        }
    }
    return $fields;
}
//---------- begin function getEncodedFileContents-------------------
/**
* @describe reads the contents of any encoded file (UTF-8, Unicode, etc)
* @param filename string
* @return string
* @usage $data=getEncodedFileContents($file);
*/
function getEncodedFileContents($filename,$return_encoding=0){
	if(!is_file($filename)){return "getEncodedFileContents Error: No such file [$filename]";}
    $encoding='';
    $handle = fopen($filename, 'r');
    $bom = fread($handle, 2);
    rewind($handle);
	if($bom === chr(0xff).chr(0xfe)  || $bom === chr(0xfe).chr(0xff)){
        // UTF16 Byte Order Mark present
        $encoding = 'UTF-16';
    }
	else{
        $file_sample = fread($handle, 1000) + 'e'; //read first 1000 bytes + e as a workaround for mb_string bug
        rewind($handle);
        $encoding = mb_detect_encoding($file_sample , 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP');
    }
    if($return_encoding){
		fclose($handle);
		return $encoding;
	}
    if ($encoding){
		if($encoding != 'UTF-8'){
			$filter='convert.iconv.'.$encoding.'/UTF-8';
        	stream_filter_append($handle, $filter);
		}
    }
    $sData = '';
    while(!feof($handle)){
    	$sData .= fread($handle, filesize($filename));
	}
    fclose($handle);
    return $sData;
}
//---------- begin function getFileContents--------------------
/**
* @describe  wrapper for file_get_contents
* @param file string - name and path of file
* @return string
* @usage $data=getFileContents($afile);
*/
function getFileContents($file){
	if(!is_file($file)){
		return "getFileContents Error: No such file [$file]";
	}
	return file_get_contents($file);
}
//---------- begin function getFileMimeType--------------------
/**
* @describe returns the mime type of file
* @param file string - name and path of file
* @return string
* @usage $mimetype=getFileMimeType($afile);
*/
function getFileMimeType($file=''){
	// return mime type ala mimetype extension
	if(isFunction('finfo_open')){
		$finfo = finfo_open(FILEINFO_MIME);
		return finfo_file($finfo, $file);
	}
	return getFileContentType($file);
}
//---------- begin function getFileContentType--------------------
/**
* @describe returns the content-type of file based on extension
* @param file string - name and path of file
* @return string
* @usage $type=getFileContentType($afile);
*/
function getFileContentType($file=''){
	$ext=getFileExtension($file);
	$custom=array(
		'mpg'	=> 'video/mpeg',
		'mpeg'	=> 'video/mpeg',
		'qt'	=> 'video/quicktime',
		'mov'	=> 'video/quicktime',
		'avi'	=> 'video/x-msvideo',
		'au'	=> 'audio/basic',
		'wav'	=> 'audio/x-wav',
		'png'	=> 'image/x-png',
		'bmp'	=> 'image/x-ms-bmp',
		'htm'	=> 'text/html',
		'js'	=> 'text/javascript',
		'sql'	=> 'text/plain',
		'txt'	=> 'text/plain',
		'doc'	=> 'application/msword',
		'ppt'	=> 'application/vnd.ms-powerpoint',
		'docx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'pptx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'xlsx'	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'pdf'	=> 'application/pdf',
		'svg'	=> 'image/svg+xml'
		);
	if(isset($custom[$ext])){return $custom[$ext];}
	$common=array(
		'image'	=> array('bmp','gif','jpeg','jpg','png','tif','tiff'),
		'audio'	=> array('aac','aif','iff','m3u','mid','midi','mp3','mpa','ra','ram','wav','wma'),
		'video'	=> array('3gp','asf','asx','avi','mov','mp4','mpg','qt','rm','swf','wmv','vdo'),
		'text'	=> array('css','html','txt','js','php','phtm','pl','asp')
		);
	foreach($common as $type=>$exts){
		if(in_array($ext,$exts)){return $type . "/" . $ext;}
		}
	if(isTextFile($file)){return "text/plain";}
	return "application/octet-stream";
	}
//---------- begin function getFileExtension--------------------
/**
* @describe returns the extension of file
* @param file string - name and path of file
* @return string
* @usage $ext=getFileExtension($afile);
*/
function getFileExtension($file=''){
	$tmp=preg_split('/\./',$file);
	$ext=array_pop($tmp);
	return strtolower($ext);
}
//---------- begin function getFileName--------------------
/**
* @describe returns the filename of file, removing the path
* @param file string - name and path of file
* @param stripext boolean - strips the extension also - defaults to false
* @return string
* @usage $ext=getFileName($afile);
*/
function getFileName($file='',$stripext=0){
	$file=str_replace("\\",'/',$file);
	$tmp=preg_split('/[\/]/',$file);
	$name=array_pop($tmp);
	if($stripext){
		$stmp=preg_split('/\./',$name);
		array_pop($stmp);
		$name=implode('.',$stmp);
    	}
	return $name;
	}
//---------- begin function getFilePath--------------------
/**
* @describe returns the path of file, removing the filename
* @param file string - name and path of file
* @return string
* @usage $path=getFilePath($afile);
*/
function getFilePath($file=''){
	if(!strlen(trim($file))){return '';}
	if(preg_match('/\//',$file)){$tmp=explode("/",$file);}
	else{$tmp=explode("\\",$file);}
	$name=array_pop($tmp);
	$path=implode('/',$tmp);
	return $path;
	}
//---------- begin function getRandomColor--------------------
/**
* @describe returns a random hex color
* @param [showpound] boolean - include the pound sign - defaults to true
* @param [alpha] integer - integer percent of alpha value you want
* @return string - hex color
* @usage 
*	$hexcolor=getRandomColor();
* 	$hexcolor=getRandomColor(true,40);
*/
function getRandomColor($showpound=1,$alpha='') {
	$showpound=(integer)$showpound;
	$color=sprintf("%02X%02X%02X", mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
	/*
		Another way - not sure what one is best/faster though
			$color=substr('00000' . dechex(mt_rand(0, 0xffffff)), -6);
	*/
	if($showpound==1){$color='#'.$color;}
	if(strlen($alpha)){$color .= getHexAlpha($alpha);}
	return $color;
}
//---------- begin function getHexAlpha--------------------
/**
* @describe returns the hex alpha value
* @param pcnt number - percent
* @return string
* @usage $alpha=getHexAlpha(38);
*/
function getHexAlpha($pcnt=0) {
	$pcnt=(integer)$pcnt;
	switch($pcnt){
		case 100:return 'FF';break;
		case 99:return 'FC';break;
		case 98:return 'FA';break;
		case 97:return 'F7';break;
		case 96:return 'F5';break;
		case 95:return 'F2';break;
		case 94:return 'F0';break;
		case 93:return 'ED';break;
		case 92:return 'EB';break;
		case 91:return 'E8';break;
		case 90:return 'E6';break;
		case 89:return 'E3';break;
		case 88:return 'E0';break;
		case 87:return 'DE';break;
		case 86:return 'DB';break;
		case 85:return 'D9';break;
		case 84:return 'D6';break;
		case 83:return 'D4';break;
		case 82:return 'D1';break;
		case 81:return 'CF';break;
		case 80:return 'CC';break;
		case 79:return 'C9';break;
		case 78:return 'C7';break;
		case 77:return 'C4';break;
		case 76:return 'C2';break;
		case 75:return 'BF';break;
		case 74:return 'BD';break;
		case 73:return 'BA';break;
		case 72:return 'B8';break;
		case 71:return 'B5';break;
		case 70:return 'B3';break;
		case 69:return 'B0';break;
		case 68:return 'AD';break;
		case 67:return 'AB';break;
		case 66:return 'A8';break;
		case 65:return 'A6';break;
		case 64:return 'A3';break;
		case 63:return 'A1';break;
		case 62:return '9E';break;
		case 61:return '9C';break;
		case 60:return '99';break;
		case 59:return '96';break;
		case 58:return '94';break;
		case 57:return '91';break;
		case 56:return '8F';break;
		case 55:return '8C';break;
		case 54:return '8A';break;
		case 53:return '87';break;
		case 52:return '85';break;
		case 51:return '82';break;
		case 50:return '80';break;
		case 49:return '7D';break;
		case 48:return '7A';break;
		case 47:return '78';break;
		case 46:return '75';break;
		case 45:return '73';break;
		case 44:return '70';break;
		case 43:return '6E';break;
		case 42:return '6B';break;
		case 41:return '69';break;
		case 40:return '66';break;
		case 39:return '63';break;
		case 38:return '61';break;
		case 37:return '5E';break;
		case 36:return '5C';break;
		case 35:return '59';break;
		case 34:return '57';break;
		case 33:return '54';break;
		case 32:return '52';break;
		case 31:return '4F';break;
		case 30:return '4D';break;
		case 29:return '4A';break;
		case 28:return '47';break;
		case 27:return '45';break;
		case 26:return '42';break;
		case 25:return '40';break;
		case 24:return '3D';break;
		case 23:return '3B';break;
		case 22:return '38';break;
		case 21:return '36';break;
		case 20:return '33';break;
		case 19:return '30';break;
		case 18:return '2E';break;
		case 17:return '2B';break;
		case 16:return '29';break;
		case 15:return '26';break;
		case 14:return '24';break;
		case 13:return '21';break;
		case 12:return '1F';break;
		case 11:return '1C';break;
		case 10:return '1A';break;
		case 9:return '17';break;
		case 8:return '14';break;
		case 7:return '12';break;
		case 6:return '0F';break;
		case 5:return '0D';break;
		case 4:return '0A';break;
		case 3:return '08';break;
		case 2:return '05';break;
		case 1:return '03';break;
		case 0:return '00';break;
	}
	return '';
}

//---------- begin function getRandomString---------------------------------------
/**
* @describe returns a random string of specified length
* @param num int
*	length of the string you want returned
* @param charset string
*	charset type: alpha|alphanum|num - defaults to alphanum
* @return string
*	returns a random string of specified length
* @usage $str=getRandomString(20);
*/
function getRandomString($length = 40, $charset = 'alphanum') {
    $alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $num = '0123456789';
    switch ($charset) {
        case 'alpha':
            $chars = $alpha;
            break;
        case 'alphanum':
            $chars = $alpha . $num;
            break;
        case 'num':
            $chars = $num;
            break;
    	}
    $randstring='';
    $maxvalue=strlen($chars)-1;
    for ($i = 0; $i < $length; $i++){
    	$randstring .= substr($chars, rand(0, $maxvalue), 1);
		}
    return $randstring;
	}
//---------- begin function getPHPVersion--------------------
/**
* @describe returns the current PHP version running
* @return string
* @usage $ver=getPHPVersion();
*/
function getPHPVersion(){
	return substr(phpversion(),0,strpos(phpversion(), '-'));
	}
//---------- begin function getUniqueHost--------------------
/**
* @describe returns the unique host name
* @param [httphost] - host name to parse - if not given defaults to $_SERVER['HTTP_HOST']
* @return string
* @usage $uhost=getUniqueHost("login.mydomain.com"); - returns mydomain.com
*/
function getUniqueHost($inhost=''){
	if(strlen($inhost)==0){$inhost=$_SERVER['HTTP_HOST'];}
	if(strlen($inhost)==0){$inhost=$_SERVER['SERVER_NAME'];}
	//check for ip address
	if(preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',$inhost)){return $inhost;}
	$parts=preg_split('/\./',$inhost);
	if(count($parts) < 3){
		//localhost, domain.com
    	return $inhost;
	}
	$x=array_pop($parts);
	$y=array_pop($parts);
	return strtolower("{$y}.{$x}");
	}
//---------- begin function getSubdomain--------------------
/**
* @describe returns the subdomain of  host name
* @param [httphost] - host name to parse - if not given defaults to $_SERVER['HTTP_HOST']
* @return string
* @usage $subdomain=getSubdomain("login.mydomain.com"); - returns login
*/
function getSubdomain($inhost=''){
	if(strlen($inhost)==0){$inhost=$_SERVER['HTTP_HOST'];}
	if(strlen($inhost)==0){$inhost=$_SERVER['SERVER_NAME'];}
	//return inhost if it is an ip address
	if(preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',$inhost)){return '';}
	$parts=preg_split('/\./',$inhost);
	if(count($parts) < 3){
		//localhost, domain.com - no subdomain
    	return '';
	}
	$x=array_pop($parts);
	$x=array_pop($parts);
	return strtolower(implode('.',$parts));
	}
//---------- begin function getURL--------------------
/**
* @describe wrapper for postURL with -method set to GET
* @param url string - url to retrieve
* @param params array - same as postURL
* @return string
* @usage $page=getURL($url);
*/
function getURL($url,$params=array()){
	$params['-method']='GET';
	return postURL($url,$params);
	}
//---------- begin function buildImage
/**
* @exclude  - this function will be depreciated (it has not value) and thus excluded from the manual
*/
function hasValue($v){
	if(isset($v)){
		if(is_array($v) && count($v)){return true;}
		elseif(is_object($v) && count($v)){return true;}
		elseif(strlen($v)){return true;}
    	}
	return false;
	}
//---------- begin function hex2RGB--------------------
/**
* @describe returns RGB array for specified hex value
* @param hex string - hex string
* @return array
* @usage $page=hex2RGB('#COCOCO');
*/
function hex2RGB($color){
    if ($color[0] == '#'){$color = substr($color, 1);}
    if (strlen($color) == 6){
        list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
	}
    elseif(strlen($color) == 3){
        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		}
    else{return false;}
    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
    return array($r, $g, $b);
}
//---------- begin function rgb2HEX--------------------
/**
* @describe returns hex value for RGB values
* @param r string or rgb array
* @param [g] g value in rgb color
* @param [b] b value in rgb color
* @return string
* @usage $hex=rgb2HEX(252,151,200); - returns #fc97c8
*/
function rgb2HEX($r,$g=-1,$b=-1){
    if (is_array($r) && sizeof($r) == 3){
        list($r, $g, $b) = $r;
	}
    $r = intval($r); $g = intval($g);
    $b = intval($b);
    $r = dechex($r<0?0:($r>255?255:$r));
    $g = dechex($g<0?0:($g>255?255:$g));
    $b = dechex($b<0?0:($b>255?255:$b));
    $color = (strlen($r) < 2?'0':'').$r;
    $color .= (strlen($g) < 2?'0':'').$g;
    $color .= (strlen($b) < 2?'0':'').$b;
    return '#'.$color;
}
//---------- begin function html2PDF--------------------
/**
* @describe wrapper for tcpdfHTML - converts html content into pdf
* @param html string
* @param params array
* @return pdf document
* @usage html2PDF($html);
*/
function html2PDF($html, $opts = array()){
	loadExtras(array('tcpdf'));
	tcpdfHTML($html,$opts);
	exit;
    }
//---------- begin function html2PDF--------------------
/**
* @describe wrapper for tcpdfXML - converts xml content into pdf
* @param xml string
* @param params array
* @return pdf document
* @usage xml2PDF($html);
*/
function xml2PDF($xml){
	loadExtras(array('tcpdf'));
	tcpdfXML($xml);
	exit;
    }
//---------- begin function htmlTidy
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function htmlTidy($tidy_in=''){
	$tidy=array();
	$tidy['descriptor'] = array(
  		0 => array("pipe", "r"), // stdin is a pipe that the child will read from
  		1 => array("pipe", "w"), // stdout is a pipe that the child will write to
  		2 => array("pipe", "r") // stderr
		);

	$tidy['process'] = proc_open('tidy -m --show-body-only yes', $tidy['descriptor'], $tidy['pipes']);
	if (is_resource($tidy['process'])) {
  		// $pipes now looks like this:
  		// 0 => writeable handle connected to child stdin
  		// 1 => readable handle connected to child stdout
  		// 2 => stderr pipe
		// writes the bad html to the tidy process that is reading from stdin.
  		fwrite($tidy['pipes'][0], $tidy_in);
  		fclose($tidy['pipes'][0]);
		// reads the good html from the tidy process that is writing to stdout.
  		$tidy_out = stream_get_contents($tidy['pipes'][1]);
  		fclose($tidy['pipes'][1]);
		// don't care about the stderr, but you might.
		// It is important that you close any pipes before calling proc_close in order to avoid a deadlock
  		$tidy['return'] = proc_close($tidy['process']);
  		unset($tidy);
  		return $tidy_out;
		}
	return "Unable to open tidy process";
	}
//---------- begin function includeFile---------------------------------------
/**
* @describe returns the contents of the specified file and processes any php in the file
* @param file string
*	absolute path and filename of the file to include
* @param params array
*	additional $_REQUEST key/value pairs you want sent to the page
* @return string
*	returns the contents of the specified file and processes any php in the file. File cannot include php functions
* @usage echo includeFile('/var/www/testfile.php',array('foo'=>25));
*/
function includeFile($file,$params=array()){
	//start with any contents currently in the buffer,if any
	$rtn=trim(ob_get_contents());
	ob_clean();
	ob_flush();
	ob_start();
	if(!is_file($file)){
		return '<img src="/wfiles/alert.gif" alt="Alert" title="includeFile error: No file."> includeFile error: No file '.$file.PHP_EOL;;
    	}
    $contents=getFileContents($file);
    //Load and params into the Request array, saving existing values in prev array
    $prev=array();
    foreach($params as $key=>$val){
		if(preg_match('/^\-\-/',$key)){continue;}
		if(isset($_REQUEST[$key])){$prev[$key]=$_REQUEST[$key];}
		$_REQUEST[$key]=$val;
		}
    $rtn .=  evalPHP($contents);
    //unset and restore any request values
    foreach($params as $key=>$val){
		if(isset($prev[$key])){$_REQUEST[$key]=$prev[$key];}
		else{unset($_REQUEST[$key]);}
		}
    return $rtn;
	}
//---------- begin function includeApp---------------------------------------
/**
* @describe returns the specified app found in the apps folder
* @param $appname string
*    -name of the application in the apps folder you want to include
* @param $params array
*    additional $_REQUEST key/value pairs you want sent to the app
* @return string
*    returns the specified app
* @usage echo includeApp('chat',array('app_title'=>'mychat'));
* @author slloyd
*/
function includeApp($app,$params=array()){
	global $CONFIG;
	global $APP;
	$app_path=getWasqlPath("apps/{$app}");
	if(!is_dir($app_path)){
    	return "includeApp Error:{$app_path} does not exist";
	}
	//Load any params into the Request array, saving existing values in prev array
    $APP=$params;
    $APP['-app_path']=$app_path;
	//include functions
	if(is_file("{$app_path}/model.php")){
    	include_once("{$app_path}/model.php");
	}
	if(is_file("{$app_path}/functions.php")){
    	include_once("{$app_path}/functions.php");
	}

	$controller='';
	if(is_file("{$app_path}/controller.php")){
    	$controller=getFileContents("{$app_path}/controller.php");
    	$controller=trim($controller);
	}
	$view='';
	if(is_file("{$app_path}/view.php")){
    	$view=getFileContents("{$app_path}/view.php");
	}
	elseif(is_file("{$app_path}/{$app}.php")){
    	$view=getFileContents("{$app_path}/{$app}.php");
	}
	elseif(is_file("{$app_path}/view.html")){
    	$view=getFileContents("{$app_path}/view.html");
	}
	elseif(is_file("{$app_path}/{$app}.html")){
    	$view=getFileContents("{$app_path}/{$app}.html");
	}
	//load any css
	$files=listFilesEx($app_path,array('ext'=>'css'));
	foreach($files as $file){
		$_SESSION['w_MINIFY']['cssfiles'][]=$file['afile'];
	}
	//load any js files
	$files=listFilesEx($app_path,array('ext'=>'js'));
	foreach($files as $file){
		$_SESSION['w_MINIFY']['jsfiles'][]=$file['afile'];
	}
	
	//start with any contents currently in the buffer
	$rtn=trim(ob_get_contents());
	ob_clean();
	ob_flush();
	ob_start();
	if(strlen($controller)){
		$rtn .=  evalPHP(array($controller,$view));
	}
	else{
	    $rtn .=  evalPHP($view);
	}
    return $rtn;
}
//---------- begin function includeModule---------------------------------------
/**
* @describe a module is a prebuilt page without a specific template. You can include them in your site.
* modules are made of three files: model.php, view.htm, and controller.php
* @param $module_name string - name of the module in the modules directory
* @param [$params] array - passed to the module in the global $MODULE array
*    additional $_REQUEST key/value pairs you want sent to the page
* @return html module
* @usage echo includeModule('translate',array('foo'=>25));
* @author slloyd
*/
function includeModule($name,$params=array()){
	global $MODULE;
	$MODULE=$params;
	$MODULE['name']=$name;
	if(!strlen($name)){return 'includeModule ERROR - no name';}
	$modulePath=getWaSQLPath("modules/{$name}");
	if(!is_dir($modulePath)){return "includeModule ERROR - {$name} does not exist";}
	//load module functions
	if(is_file("{$modulePath}/model.php")){
    	include_once("{$modulePath}/model.php");
	}
	if(is_file("{$modulePath}/controller.php")){
		$body=getFileContents("{$modulePath}/view.htm");
		$controller='<'.'?php'.PHP_EOL.'global $'.'MODULE;'.PHP_EOL.'?>'.PHP_EOL;
		$controller.=getFileContents("{$modulePath}/controller.php");
		return processTranslateTags(evalPHP(array($controller,$body)));
	}
	else{
		$body=getFileContents("{$modulePath}/view.htm");
		$controller='<'.'?php'.PHP_EOL.'global $'.'MODULE;'.PHP_EOL.'?>'.PHP_EOL;
		return processTranslateTags(evalPHP(array($controller,$body)));
	}
}
/**
 * loads an admin page
 * @param  string $name   name of the admin page to load
 * @param  array  $params params to pass in
 * @return string       HTML page content
 */
function includeAdminPage($name,$params=array()){
	global $ADMINPAGE;
	$ADMINPAGE=$params;
	if(!strlen($name)){return 'includeAdminPage ERROR - no name';}
	$ADMINPAGE['name']=$name;
	$progpath=getWasqlPath('php');
	$name=strtolower($name);
	if(!is_file("{$progpath}/admin/{$name}_body.htm")){
		return 'No such page: '.$name;
	}
	if(is_file("{$progpath}/admin/{$name}_functions.php")){
    	include_once("{$progpath}/admin/{$name}_functions.php");
	}
	$body=getFileContents("{$progpath}/admin/{$name}_body.htm");
	$controller=getFileContents("{$progpath}/admin/{$name}_controller.php");
	$rtn = evalPHP(array($controller,$body));
	$rtn=processTranslateTags($rtn);
    return $rtn;
}
//---------- begin function includePage---------------------------------------
/**
* @describe returns the specified page and loads any functions, etc of that page
* @param $val string
*    -name or id of the page you want to include
* @param $params array
*    [-dbname] string Specifies which database to draw from. Default = current
*    [-fieldname] string Specifies which page fields to extract. Default = "controller,body,functions".
*	 [-pageonly] boolean set to true to only load the page and not the css and js
*    additional $_REQUEST key/value pairs you want sent to the page
* @return string
*    returns the specified page and loads any functions, etc of that page
* @usage includePage('test',array('foo'=>25));
* @author slloyd
* @history
*	slloyd 2014-01-07 added -dbname, and -fieldname controling parameters.
*	bbarten 2014-01-07 added documentation
*/
function includePage($val='',$params=array()){
	global $CONFIG;
	global $PASSTHRU;
	global $PAGE;
	$prevpass=$PASSTHRU;
	$prevview=isset($_REQUEST['_view'])?$_REQUEST['_view']:'';
	//check to make sure this is not an infinite loop - includePage of the page you on with same passthrus
	$parts=preg_split('/\/+/',$val);
	$val=$_REQUEST['_view']=array_shift($parts);
	if(strtolower($PAGE['name'])==strtolower($val)){
		//name is the same. check for recursive issue
		if(isset($PASSTHRU[0]) && count($parts)==count($PASSTHRU)){
			$found=0;
			foreach($parts as $part){
				if(in_array($part,$PASSTHRU)){$found+=1;}
			}
			if($found==count($parts)){
				return "includePage '{$PAGE['name']}' Recursive Error";
			}
		}
		elseif(!isset($PASSTHRU[0]) && count($parts)==0){
			return "includePage '{$PAGE['name']}' Recursive Error";
		}
	}
	elseif(strtolower($PAGE['permalink'])==strtolower($val)){
		//name is the same. check for recursive issue
		if(isset($PASSTHRU[0]) && count($parts)==count($PASSTHRU)){
			$found=0;
			foreach($parts as $part){
				if(in_array($part,$PASSTHRU)){$found+=1;}
			}
			if($found==count($parts)){
				return "includePage '{$PAGE['permalink']}' Recursive Error";
			}
		}
		elseif(!isset($PASSTHRU[0]) && count($parts)==0){
			return "includePage '{$PAGE['permalink']}' Recursive Error";
		}
	}
	if(isset($params['passthru'][0])){
		$PASSTHRU=$params['passthru'];
	}
	elseif(count($parts)){
		$params['passthru']=$PASSTHRU=$parts;
	}
	//start with any contents currently in the buffer
	$rtn=trim(ob_get_contents());
	ob_clean();
	ob_flush();
	ob_start();
	//Disallow recursive calls - pages that call themselves
	$fieldname="body";
	$opts=array(
		'-table'=>'_pages'
	);
	unset($parts);
	if(isNum($val)){$opts['-where']="_id={$val}";}
	else{$opts['-where']="name='{$val}' or permalink='{$val}'";}
	$rec=getDBRecord($opts);
	if(isset($rec['-error'])){
		return '<img src="/wfiles/alert.gif" alt="Alert" title="' . $rec['-error'] . '"> Error: ' . $rec['-error'];
    }
    global $INCLUDEPAGE;
    $INCLUDEPAGE=$rec;
    //Load any params into the Request array, saving existing values in prev array
    $prev=array();
    foreach($params as $key=>$pval){
		if(preg_match('/^\-\-/',$key)){continue;}
		if(isset($_REQUEST[$key])){$prev[$key]=$_REQUEST[$key];}
		$_REQUEST[$key]=$pval;
	}
	
	//load any functions in the page template
	if(isset($params['-template']) && isNum($params['-template'])){
		$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"_id={$params['-template']}"));
	}
	elseif(isset($params['--template']) && isNum($params['--template'])){
		$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"_id={$params['--template']}"));
	}
	elseif(isset($rec['_template']) && isNum($rec['_template'])){
		$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"_id={$rec['_template']}"));
	}
	//load functions in the page record
    if(isset($rec['functions']) && strlen(trim($rec['functions']))){
    	$fname="p{$rec['_id']}";
		$ok=includePHPOnce(trim($rec['functions']),$fname);
		if(!isNum($ok)){return "includePage '{$rec['name']}' Error Loading functions:". $ok;}
    }
    //load controller
    if(isset($rec['body'])){
	    if(isset($rec['controller']) && strlen(trim($rec['controller']))){
			$rtn .=  evalPHP(array(trim($rec['controller']),$rec['body']));
		}
		else{
	    	$rtn .=  evalPHP($rec['body']);
		}
	}
	//prep to load js and css from minify
	if(!isset($params['-dbname']) && !isset($params['-pageonly']) && isset($rec['_id'])){
		if(!isset($_SESSION['w_MINIFY']['includepages'])){$_SESSION['w_MINIFY']['includepages']=array();}
		if(!in_array($rec['_id'],$_SESSION['w_MINIFY']['includepages'])){
    		$_SESSION['w_MINIFY']['includepages'][]=$rec['_id'];
		}
	}
    //unset and restore any request values
    foreach($params as $key=>$val){
		if(isset($prev[$key])){$_REQUEST[$key]=$prev[$key];}
		else{unset($_REQUEST[$key]);}
	}
	$PASSTHRU=$prevpass;
	$_REQUEST['_view']=$prevview;
	if(isset($params['-notranslate'])){
		return $rtn;
	}
	$rtn=processTranslateTags($rtn);
    return $rtn;
}

//---------- begin function includePageIf---------------------------------------
/**
* @describe includePageIf is a Conditional includePage
* @param condition - condition can be a boolean or an array of boolean=>view sets
* @param page_true string - page name if true
* @param params array
* @usage includePageIf(isUser(),'topmenu');
* @see includePage();
*/
function includePageIf($conditional,$page_true, $params=array()){
	if($conditional){return includePage($page_true,$params);}
	return '';
}

//---------- begin function includePageIfElse---------------------------------------
/**
* @describe includePageIfElse is a Conditional includePage
* @param condition mixed - condition can be a boolean
* @param page_true string - page name if true
* @param page_false string - page name if false.
* @param params array
* @usage includePageIfElse(isUser(),'topmenu_user','topmenu_nouser');
* @see includePage();
*/
function includePageIfElse($conditional,$page_true, $page_false, $params=array()){
	if($conditional){return includePage($page_true,$params);}
	else{return includePage($page_false,$params);}
}
//---------- begin function includePHPOnce--------------------
/**
* @describe provides a method to dynamically load functions
* @param code string - php code to include
* @param [name] string - name to use. If not provided creates a name based on the code content
* @return boolean
* @usage includePHPOnce($phpcode);
*/
function includePHPOnce($php_content,$name=''){
	return commonIncludeFunctionCode($php_content,$name);
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonIncludeFunctionCode($content,$name=''){
	global $CONFIG;
	$tmppath=getWasqlTempPath();
	//remove PHP comments
	$content=removePHPComments($content);
	preg_match_all('/\<\?(.+?)\?\>/sm',$content,$evalmatches,PREG_PATTERN_ORDER);
	if(!count($evalmatches[1])){
		//assume just PHP without an end marker
		$tmpfile="{$CONFIG['name']}_{$name}_".'php_'.sha1($content).".php";
		$afile="{$tmppath}/{$tmpfile}";
		$afile=str_replace("\\","/",$afile);
		//echo $afile.PHP_EOL;
		$content_md5=md5($content);
		if(!is_file($afile) || md5_file($afile) != $content_md5){
			$fp = fopen($afile, "w");
			fwrite($fp, $content);
			fclose($fp);
		}
		if(is_file($afile)){
			@trigger_error("");
			$evalstring='showAllErrors();'.PHP_EOL;
			$evalstring .= 'try{'.PHP_EOL;
			$evalstring .= '	include_once(\''.$afile.'\');'.PHP_EOL;
			$evalstring .= '	}'.PHP_EOL;
			$evalstring .= 'catch(Exception $e){'.PHP_EOL;
			$evalstring .= '	debugValue($e);'.PHP_EOL;
			$evalstring .= '	}'.PHP_EOL;
			@eval($evalstring);
			$e=error_get_last();
			//echo $evalstring.printValue($e).'<hr>';
			if($e['message']!=='' && !preg_match('/Undefined/i',$e['message'])){
	    		// An error occurred
	    		$e['file']=$afile;
	    		debugValue($e);
			}
		}
		else{
			debugValue(array("commonIncludeFunctionCode ERROR: {$afile} does not exist"));
		}
		return '';
	}
	
	foreach($evalmatches[1] as $evalcode){
		if(preg_match('/^(python|py|perl|pl|ruby|rb|vbscript|vbs|bash|sh|node|nodejs|lua)[\ \r\n]+(.+)/ism',$evalcode,$g)){
			$evalcode=trim(preg_replace('/^'.$g[1].'/i','',$evalcode));
			switch(strtolower($g[1])){
				case 'python':$lang='py';break;
				case 'perl':$lang='pl';break;
				case 'ruby':$lang='rb';break;
				case 'vbscript':$lang='vbs';break;
				case 'nodejs':$lang='js';break;
				default:
					$lang=strtolower($g[1]);
				break;
			}
			$content_md5=md5($evalcode);
			$tname="{$CONFIG['name']}_{$lang}_{$content_md5}";
			$tname=preg_replace('/[^a-z0-9\_]+/','_',$tname);
			$tmpfile="{$tname}.{$lang}";
			$afile="{$tmppath}/{$tmpfile}";
			$afile=str_replace("\\","/",$afile);
			//echo $afile.PHP_EOL;
			
			if(!is_file($afile) || md5_file($afile) != $content_md5){
				$fp = fopen($afile, "w");
				fwrite($fp, $evalcode);
				fclose($fp);
			}
			if(!isset($CONFIG['includes'][$lang][0])){
				$CONFIG['includes'][$lang]=array();
			}
			//set CONFIG['includes'] so they can be loaded when code is ran.
			$CONFIG['includes'][$lang][]=$afile;
		}
		elseif(preg_match('/^php[\ \r\n]+(.+)/ism',$evalcode,$g)){
			$evalcode=trim(preg_replace('/^php/i','',$evalcode));
			if(!strlen($evalcode)){continue;}
			$evalcode='<?'.'php'.PHP_EOL.$evalcode;	
			$content_md5=md5($evalcode);
			$tname=isset($CONFIG['name'])?$CONFIG['name']:'NO_CONFIG_NAME';
			$tmpfile="{$tname}_php_{$content_md5}.php";
			$afile="{$tmppath}/{$tmpfile}";
			$afile=str_replace("\\","/",$afile);
			//echo $afile.PHP_EOL;
				
			$content_md5=md5($evalcode);
			if(!is_file($afile) || md5_file($afile) != $content_md5){
				$fp = fopen($afile, "w");
				fwrite($fp, $evalcode);
				fclose($fp);
			}
			if(is_file($afile)){
				@trigger_error("");
				$evalstring='showAllErrors();'.PHP_EOL;
				$evalstring .= 'try{'.PHP_EOL;
				$evalstring .= '	include_once(\''.$afile.'\');'.PHP_EOL;
				$evalstring .= '	}'.PHP_EOL;
				$evalstring .= 'catch(Exception $e){'.PHP_EOL;
				$evalstring .= '	debugValue($e);'.PHP_EOL;
				$evalstring .= '	}'.PHP_EOL;
				//echo $evalstring;exit;
				@eval($evalstring);
				$e=error_get_last();
				if($e['message']!=='' && !preg_match('/Undefined/i',$e['message'])){
		    		// An error occurred
		    		//return evalErrorWrapper($e,"includeDBOnce Error".printValue($params));
		    		debugValue($e);
				}
				//remove file in temp dir?
				//$CONFIG['includes']['php'][]=$afile;
			}
			else{
				debugValue(array("commonIncludeFunctionCode ERROR: {$afile} does not exist"));
			}
		}
		elseif(!preg_match('/^(php|python|py|perl|pl|ruby|rb|vbscript|vbs|bash|sh|node|nodejs|lua)$/i',trim($evalcode))){
			debugValue("Unknown language in commonIncludeFunctionCode: ".$evalcode);
		}
	}
	return 1;
}
//---------- begin function isAdmin ----------
/**
* @describe returns true is current user is an administrator
* @return boolean
*	returns true is current user is an administrator
* @usage if(isAdmin()){...}
*/
function isAdmin(){
	global $USER;
	if(isset($USER) && is_array($USER) && isset($USER['_id']) && isNum($USER['_id']) && $USER['utype']==0){return true;}
	return false;
	}
//---------- begin function isAjax ----------
/**
* @describe returns true if page was called using AJAX
* @return boolean
*	returns true if page was called using AJAX
* @usage if(isAjax()){...}
*/
function isAjax(){
	if(isset($_REQUEST['AjaxRequestUniqueId'])){return true;}
	return false;
	}
//---------- begin function isAudioFile ----------
/**
* @describe returns true if specified filename is an audio file - based on file extension only
* @param filename string
*	name of the file
* @return boolean
*	returns true if specified filename is an audio file
* @usage if(isAudioFile($filename)){...}
*/
function isAudioFile($file=''){
	$mimetype=getFileMimeType($file);
	if(stringContains($mimetype,'audio')){return true;}
	$ext=getFileExtension($file);
	$exts=array('aac','aif','iff','m3u','mid','midi','mp3','mpa','ra','ram','wav','wma');
	if(in_array($ext,$exts)){return true;}
    return false;
}
//---------- begin function isCLI ----------
/**
* @describe returns true if script is running from a CLI - command line interface
* @return boolean
*	returns true if script is running from a command line/shell prompt
* @usage if(isCLI()){...}
*/
function isCLI(){
	$checks=array('HTTP_ACCEPT','HTTP_CONNECTION','REMOTE_ADDR','REQUEST_URI','SERVER_ADDR');
	foreach($checks as $check){
		if(isset($_SERVER[$check])){return false;}
	}
	return true;
}

//---------- begin function isDate ----------
/**
* @describe returns true if string is a date - uses checkdate function to validate date
* @param str string
*	string to check
* @return boolean
*	returns true if string is a date
* @usage if(isDate($str)){...}
*/
function isDate($str=''){
	//use checkdate to validate date
	if(!strlen(trim($str))){return false;}
	//must have one of these: ./-
	$ok=0;
	if(stringContains($str,'.')){$ok+=1;}
	if(stringContains($str,'-')){$ok+=1;}
	if(stringContains($str,'/')){$ok+=1;}
	if($ok==0){return false;}
	//YYYY-mm-dd or mm-dd-YYYY - make sure none of the tuplets are zero  (2015-10-00)
	if(preg_match('/([0-9]{2,4})[\-\.\/]([0-9]{2,2})[\-\.\/]([0-9]{2,4})/s',$str,$m)){
		if((integer)$m[1] == 0){return false;}
		if((integer)$m[2] == 0){return false;}
		if((integer)$m[3] == 0){return false;}
	}
	$time=strtotime($str);
	if(!strlen($time)|| $time==0){return false;}
	$m=date('m',$time);
	$d=date('d',$time);
	$y=date('Y',$time);
	return checkdate($m,$d,$y);
	if(checkdate($m,$d,$y)){return true;}
}
//---------- begin function isDateTime ----------
/**
* @describe returns true if string is in a valid datetime format
* @param str string
*	string to check
* @return boolean
*	returns true if string is in a valid datetime format
* @usage if(isDateTime($str)){...}
*/
function isDateTime($str=''){
	if(!strlen(trim($str))){return false;}
	$time=strtotime($str);
	if(!strlen($time)|| $time==0){return false;}
	$m=date('m',$time);
	$d=date('d',$time);
	$y=date('Y',$time);
	if(!checkdate($m,$d,$y)){return false;}
	if(preg_match('/([0-9]{1,2})\:([0-9]{2,2})\:([0-9]{2,2})/s',$str,$m)){
		if((integer)$m[1] > 23){return false;}
		if((integer)$m[2] > 59){return false;}
		if((integer)$m[3] > 59){return false;}
	}
	if(preg_match('/([0-9]{1,2})\:([0-9]{2,2})\ (am|pm)/is',$str,$m)){
		if((integer)$m[1] > 23){return false;}
		if((integer)$m[2] > 59){return false;}
	}
	if(!preg_match('/[0-9]{1,2}\:[0-9]{2,2}/is',$str)){return false;}
	return true;
	}
//---------- begin function isExtra ----------
/**
* @describe returns true if specified php extra is enabled for use
* @param str string
*	string to check
* @return boolean
*	returns true if specified php extra is enabled for use
* @usage if(isExtra($str)){...}
*/
function isExtra($string){
	if(isset($_SESSION['w_MINIFY']['extras'])){
	    foreach($_SESSION['w_MINIFY']['extras'] as $extra){
			$parts=preg_split('/\/+/',$extra);
			$extra=array_pop($parts);
	        if(stringEquals(getFileName($extra),$string)){
	            return true;
			}
		}
	}
	return false;
}
//---------- begin function isExtraCss ----------
/**
* @describe returns true if specified css extra is enabled for use
* @param str string
*	string to check
* @return boolean
*	returns true if specified css extra is enabled for use
* @usage if(isExtraCss($str)){...}
*/
function isExtraCss($string){
	if(isset($_SESSION['w_MINIFY']['extras_css'])){
	    foreach($_SESSION['w_MINIFY']['extras_css'] as $extra){
			$parts=preg_split('/\/+/',$extra);
			$extra=array_pop($parts);
	        if(stringEquals($extra,$string)){
	            return true;
			}
		}
	}
	return false;
}
//---------- begin function isExtraJs ----------
/**
* @describe returns true if specified javascript extra is enabled for use
* @param str string
*	string to check
* @return boolean
*	returns true if specified javascript extra is enabled for use
* @usage if(isExtraJs($str)){...}
*/
function isExtraJs($string){
	if(isset($_SESSION['w_MINIFY']['extras_js'])){
	    foreach($_SESSION['w_MINIFY']['extras_js'] as $extra){
			$parts=preg_split('/\/+/',$extra);
			$extra=array_pop($parts);
	        if(stringEquals($extra,$string)){
	            return true;
			}
		}
	}
	return false;
}
//---------- begin function isFunction ----------
/**
* @describe returns true if said function is available to use
* @return boolean
*	returns true if said function is available to use
* @usage if(isFunction($str)){...}
*/
function isFunction($func){
	return is_callable($func);
}
//---------- begin function isGDEnabled ----------
/**
* @describe returns true if  GD is enabled. GD must be enabled to create images, etc.
* @return boolean
*	returns true if GD is enabled. GD must be enabled to create images, etc.
* @usage if(isGDEnabled($str)){...}
*/
function isGDEnabled(){
	if(function_exists('gd_info')){
		$info=gd_info();
		//$info=@eval('return gd_info();');
		if(is_array($info) && isset($info['GD Version'])){
			return true;
        	}
    	}
	return false;
}
//---------- begin function isJSON ----------
/**
* @describe returns true if string is a valid JSON. returns error if not.
* @return mixed returns 1 if valid json, else error
* @usage $ck=isJSON($str);if($ck==1){...}
*/
function isJSON($string){
	if(!is_string($string)){
		$error=array(
			'error'=>"NOT a string",
			'code'=>-1,
			'type'=>gettype($string)
		);
	  return json_encode($error,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT);
	}
	if(!strlen(trim($string))){
		$error=array(
			'error'=>"Empty JSON string",
			'code'=>-1
		);
	  return json_encode($error,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT);
	}
	try {  
	  json_decode($string, false, 512, JSON_THROW_ON_ERROR);  
	}  
	catch (\JsonException $exception) {  
		$error=array(
			'error'=>$exception->getMessage(),
			'code'=>$exception->getCode()
		);
	  return json_encode($error,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT);
	}
	$enc=json_encode($string,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
	if(is_null(json_decode($string))){
		$error=array(
			'error'=>"Empty JSON string",
			'code'=>-1
		);
	  return json_encode($error,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT);
	}
	return 1;
}
//---------- begin function jsonCheck ----------
/**
* @exclude  - this function is depreciated and thus excluded from the manual. user isJSON instead
*/
function jsonCheck($string){
	return isJSON($string);

	if(!strlen(trim($string))){
		return 'empty string';
	}
    // decode the JSON data
    $result = json_decode($string);
    // switch and check possible JSON errors
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = ''; // JSON is valid // No error has occurred
            break;
        case JSON_ERROR_DEPTH:
            $error = 'The maximum stack depth has been exceeded.';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Invalid or malformed JSON.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Control character error, possibly incorrectly encoded.';
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Syntax error, malformed JSON.';
            break;
        // PHP >= 5.3.3
        case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_INF_OR_NAN:
            $error = 'One or more NAN or INF values in the value to be encoded.';
            break;
        case JSON_ERROR_UNSUPPORTED_TYPE:
            $error = 'A value of a type that cannot be encoded was given.';
            break;
        default:
            $error = 'Unknown JSON error occured.';
            break;
    }
    if ($error !== '') {
		return $error;
    }
	return 1;
}
//---------- begin function isLiveUrl ----------
/**
* @describe returns true if $url is valid (not broken) - assumes internet connection is present
* @param url string
*	url to check
* @return boolean
*	returns true if $url is valid (not broken) - assumes internet connection is present
* @usage if(isLiveUrl($url)){...}
*/
function isLiveUrl($url){
	$info=parseUrl($url);
	$port=isset($info['port'])?$info['port']:80;
	$fp = fsockopen($info['host'], $port, $errno, $errstr, 30);
	if (!$fp) {
    	return false;
	}
	$fh = fopen($url, "rb");
	if(is_resource($fh)){
		fclose($fh);
		return true;
		}
	return false;
	}
//---------- begin function isParams ----------
/**
* @describe returns true if $key is found in array $params ($params[$key])
* @param key string
*	key to check
* @param params array
*	array to check
* @return boolean
*	returns true if $key is found in array $params ($params[$key])
* @usage if(isParams($key,$params)){...}
*/
function isParams($p,$params=array()){
	if(is_array($p)){
		//if($debug){echo "isRequest Array:<br>\n";}
		foreach($p as $key){
			if(!strlen($key)){continue;}
			if(!isset($params[$key]) || !strlen($params[$key])){
				return false;
				}
        	}
    	}
    else{
		if(!isset($params[$p]) || !strlen($params[$p])){
			return false;
			}
    	}
    return true;
	}
//---------- begin function isRequest ----------
/**
* @describe returns true if $key is found in the $_REQUEST array
* @param key string
*	key to check
* @return boolean
*	returns true if $key is found in the $_REQUEST array
* @usage if(isRequest($key)){...}
*/
function isRequest($r){
	return isParams($r,$_REQUEST);
	}
//---------- begin function isSessionActive ----------
/**
* @describe returns true if sessions are active
* @return boolean
*	returns true if sessions are active
* @usage if(!isSessionActive()){session_start();}
*/
function isSessionActive(){
    $setting = 'session.use_trans_sid';
    $current = ini_get($setting);
    if (FALSE === $current){
        //Setting %s does not exists.', $setting));
        return false;
    }
    $testate = "mix{$current}{$current}";
    try {
	    $old = @ini_set($setting, $testate);
	    $peek = @ini_set($setting, $current);
    	$result = $peek === $current || $peek === FALSE;
    	return $result;
	}
	catch (Exception $e){
    	return true;
	}
}
//---------- begin function isServer ----------
/**
* @describe returns true if $key is found in the $_SERVER array
* @param key string
*	key to check
* @return boolean
*	returns true if $key is found in the $_SERVER array
* @usage if(isServer($key)){...}
*/
function isServer($r){
	return isParams($r,$_SERVER);
	}
//---------- begin function isSession ----------
/**
* @describe returns true if $key is found in the $_SESSION array
* @param key string
*	key to check
* @return boolean
*	returns true if $key is found in the $_SESSION array
* @usage if(isSession($key)){...}
*/
function isSession($r){
	return isParams($r,$_SESSION);
	}
//---------- begin function isSecure ----------
/**
* @describe wrapper for isSSL()
* @exclude  - this function is depreciated and thus excluded from the manual
*/
function isSecure(){return isSSL();}
//---------- begin function isSSL ----------
/**
* @describe returns true if sessions are active
* @return boolean
*	returns true if sessions are active
* @usage if(!isSSL()){header("location: https://{$_SERVER['REQUEST_URI']}");exit;}
*/
function isSSL(){
	/* Apache */
	if(isset($_SERVER['https']) && in_array($_SERVER['https'],array(1,'on'))){
		return true;
	}
	if(isset($_SERVER['HTTPS']) && in_array($_SERVER['HTTPS'],array(1,'on'))){
		return true;
	}
	if(isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME'])=='https'){
		return true;
	}
	//HTTP_X_FORWARDED
	if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'])=='https'){
		return true;
	}
	if (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443){return true;}
	/* others */
	if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443){return true;}
	return false;
}
//---------- begin function isStrongPassword ----------
/**
* @describe returns true if $pw is a strong password
* @param pw string
*	password to check
* @return boolean
*	returns true if $pw is a strong password
*	strong passwords must be at least 8 chars long and contain at least one number, one uppercase letter, one lowercase letter, and one non-alphanumberic character
* @usage if(isStrongPassword($pw)){...}
*/
function isStrongPassword($pw){
	if(passwordScore($pw) < 10){return false;}				//too short
	return true;
}
//---------- begin function passwordScore ----------
/**
* @describe returns a password score on a scale of 0-10
* @param pw string
*	password to check
* @return integer
*	returns a password score on a scale of 0-10
*	strong passwords(10) must be at least 8 chars long and contain at least one number, one uppercase letter, one lowercase letter, and one non-alphanumberic character
* @usage if(passwordScore($pw)<10){return false;}
*/
function passwordScore($pw){
	$score=0;
	if(strlen($pw) >= 8){$score++;}				//too short
	if(preg_match('/[0-9]/',$pw)){$score++;}	//at least one number
	if(preg_match('/[A-Z]/',$pw)){$score++;}	//at least one uppercase letter
	if(preg_match('/[a-z]/',$pw)){$score++;}	//at least one lowercase letter
	if(preg_match('/[^A-Z0-9]/i',$pw)){$score++;}	//at least one non-alphanumeric
	return round(($score/5)*10);
}
//---------- begin function isTextFile ----------
/**
* @describe returns true if specified filename is an text file - based on file extension only
* @param filename string
*	name of the file
* @return boolean
*	returns true if specified filename is an text file
* @usage if(isTextFile($filename)){...}
*/
function isTextFile($file=''){
	$ext=getFileExtension($file);
	$exts=array('txt','xml','htm','html','conf','cfg','log','css','js','php','rss','jsp','asp','xhtml','ini','config');
	if(in_array($ext,$exts)){return true;}
    return false;
	}
//---------- begin function isVideoFile ----------
/**
* @describe returns true if specified filename is an video file - based on file extension only
* @param filename string
*	name of the file
* @return boolean
*	returns true if specified filename is an video file
* @usage if(isVideoFile($filename)){...}
*/
function isVideoFile($file=''){
	$mimetype=getFileMimeType($file);
	if(stringContains($mimetype,'video')){return true;}
	$ext=getFileExtension($file);
	$exts=array('3gp','asf','asx','avi','flv','mov','mp4','mpg','qt','rm','swf','wmv','vdo');
	if(in_array($ext,$exts)){return true;}
    return false;
	}
//---------- begin function isWebImage ----------
/**
* @describe returns true if specified filename is a standard web image - based on file extension only
* @param filename string
*	name of the file
* @return boolean
*	returns true if specified filename is a standard web image
* @usage if(isWebImage($filename)){...}
*/
function isWebImage($file=''){
	$ext=getFileExtension($file);
	$exts=array('gif','jpg','png','bmp','jpeg');
	if(in_array($ext,$exts)){return true;}
    return false;
	}
//---------- begin function isEmail ----------
/**
* @describe returns true if specified string is a valid email address
* @param str string
*	string to check
* @return boolean
*	returns true if specified string is a valid email address
* @usage if(isEmail($filename)){...}
*/
function isEmail($str=''){
	if(strlen($str)==0){return false;}
	if(preg_match('/^.+@.+\..{2,6}$/',$str)){return true;}
	return false;
	}
//---------- begin function isEven ----------
/**
* @describe returns true if specified number is an even number
* @param num number - number to check
* @return boolean - returns true if specified number is an even number
* @usage if(isEven($num)){...}
*/
function isEven($num=0){
	return isFactor($num,2);
	}
//---------- begin function isFactor ----------
/**
* @describe returns true if num is divisable by divisor without a remainder
* @param num number - number to check
* @param divisor number
* @return boolean - returns true if num is divisable by divisor without a remainder
* @usage if(isFactor($num,2)){echo 'number is even';}
*/
function isFactor($num=0,$divisor=1){
	if($divisor==0 || $num==0){return false;}
	if ( $num % $divisor == 0 ){return true;}
	return false;
	}
//---------- begin function isMobileDevice ----------
/**
* @describe returns true if the remote client (browser) is a mobile device
* @return boolean
*	returns true if the remote client (browser) is a mobile device
* @usage if(isMobileDevice()){...}
*/
function isMobileDevice(){
	// check if the user agent value claims to be windows but not windows mobile
	if(isset($_SERVER['HTTP_USER_AGENT']) && stringContains($_SERVER['HTTP_USER_AGENT'],'windows') && !stringContains($_SERVER['HTTP_USER_AGENT'],'windows ce')){
		return false;
		}
	// check if the user agent gives away any tell tale signs it's a mobile browser
	if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(up\.browser|up\.link|windows ce|iemobile|mini|mmp|blackberry|symbian|midp|wap|phone|ipad|iphone|pocket|mobile|pda|psp|silk\-accelerated)/i',$_SERVER['HTTP_USER_AGENT'])){
		return true;
		}
	// check the http accept header to see if wap.wml or wap.xhtml support is claimed
	if(isset($_SERVER['HTTP_ACCEPT']) && (stringContains($_SERVER['HTTP_ACCEPT'],'text/vnd.wap.wml')||stringContains($_SERVER['HTTP_ACCEPT'],'application/vnd.wap.xhtml+xml'))){
		return true;
		}
	// check if there are any tell tales signs it's a mobile device from the _server headers
	if(isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE'])||isset($_SERVER['X-OperaMini-Features'])||isset($_SERVER['UA-pixels'])){
		return true;
		}
	// build an array with the first four characters from the most common mobile user agents
	$a = array(
		'acs-','alav','alca','amoi','audi','aste','avan','benq','bird','blac','blaz','brew','cell','cldc','cmd-',
		'dang','doco','eric','hipt','inno','ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
		'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-','newt','noki','opwv',
		'palm','pana','pant','pdxg','phil','play','pluc','port','prox','qtek','qwap',
		'sage','sams','sany','sch-','sec-','send','seri','sgh-','shar','sie-','siem','smal','smar','sony','sph-','symb',
		't-mo','teli','tim-','tosh','tsm-','upg1','upsi','vk-v','voda',
		'w3c ','wap-','wapa','wapi','wapp','wapr','webc','winw','winw','xda','xda-');
	// check if the first four characters of the current user agent are set as a key in the array
	if(isset($a[substr($_SERVER['HTTP_USER_AGENT'],0,4)])){
		return true;
		}
	return false;
	}
//---------- begin function isSpamBot---------------------------------------
/**
* @deprecated use isSpider instead
* @exclude  - this function is depreciated and thus excluded from the manual
*/
function isSpamBot($agent='',$return_name=0){
	return isSpider($agent,$return_name);
	}
//---------- begin function isSpider ----------
/**
* @describe returns true if the remote client (browser) is a spider, bot, web crawler - not a real person
* @param agent string
*	optional user_agent string. If not provided it will use $_SERVER['HTTP_USER_AGENT']
* @param return_name boolean
*	if true will return the name of the bot instead of boolean
* @return boolean/string
*	returns true if the remote client (browser) is a spider, bot, web crawler - not a real person
*	if optional return_name is true returns name of the bot instead
* @usage if(isSpider()){...}
*/
function isSpider($agent='',$return_name=0){
	$agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
	if(!$agent){return false;}
	$bots_contains=array(
		'abachobot','abcdatos_botlink','acoon robot','adsbot-google','aesop_com_spiderman','ah-ha.com crawler','antibot','architextspider','ask jeeves/teoma',
    	'baiduspider','bingbot','bdfetch',
    	'cc-rget','/robot/','/bingbot',
    	'discobot','duckduckgo','dataprovider.com',
    	'fast-webcrawler','findexa crawler','feedfetcher',
        'galaxybot','googlebot','gsa-crawler',
        'HTTrack',
        'ia_archiver','iaskspider','Indy Library','inktomi','infoseek sidewinder',
        'kw-lp-suggest',
        'LinksManager.com_bot','linkwalker','lookbot',
        'mediapartners-google','mercator','msnbot','mnogosearch',
        'naverrobot','NetcraftSurveyAgent','nuhk',
        'openbot','outfoxbot',
        'rabot','researchscan.com',
        'scooter','slurp','serpstatbot','sogou spider','sosospider','spider','superpagesurlverifybot','sogou web spider',
        'thunderstone.com',
		'yahoo!slurp','yammybot','yodaobot','yandexbot',
		'webalta','WebCollage','wget',
		'Yandex',
		'zermelo','ZyBorg'
		);
	$bots_beginswith=array(
		'BlackWidow','Bot mailto:craftbot@yahoo.com',
		'ChinaClaw','Custo',
		'DISCo','Download Demon',
		'eCatch','EirGrabber','EmailSiphon','EmailWolf','Express WebPictures','ExtractorPro','EyeNetIE',
		'FlashGet',
		'GetRight','GetWeb!','Go!Zilla','Go-Ahead-Got-It','GrabNet','Grafula',
		'HMView',
		'Image Stripper','Image Sucker','InterGET','Internet Ninja',
		'JetCar','JOC Web Spider',
		'larbin','LeechFTP',
		'Mass Downloader','MIDown tool','Mister PiX',
		'Navroad','NearSite','NetAnts','NetSpider','Net Vampire','NetZIP',
		'Octopus','Offline Explorer','Offline Navigator',
		'PageGrabber','Papa Foto','pavuk','pcBrowser',
		'RealDownload','ReGet',
		'SiteSnagger','SmartDownload','SuperBot','SuperHTTP','Surfbot',
		'tAkeOut','Teleport Pro',
		'VoidEYE',
		'Web Image Collector','Web Sucker','WebAuto','WebCopier','WebFetch','WebGo IS','WebLeacher','WebReaper','WebSauger','Website eXtractor',
		'Website Quester','WebStripper','WebWhacker','WebZIP','Wget','Widow','WWWOFFLE',
		'Xaldon WebSpider',
		'Zeus'
		);
	$bots=array(
		'Bingbot'=>'bingbot',
		'ahrefs.com'=>'ahrefs.com/robot',
		'Twitter'=>'Twitterbot',
		'Google'=>'Googlebot',
		'MSN' => 'msnbot',
		'Rambler' => 'Rambler',
		'Yahoo' => 'Yahoo',
		'AbachoBOT' => 'AbachoBOT',
		'accoona' => 'Accoona',
		'AcoiRobot' => 'AcoiRobot',
		'ASPSeek' => 'ASPSeek',
		'CrocCrawler' => 'CrocCrawler',
		'Dumbot' => 'Dumbot',
		'FAST-WebCrawler' => 'FAST-WebCrawler',
		'GeonaBot' => 'GeonaBot',
		'Gigabot' => 'Gigabot',
		'Lycos spider' => 'Lycos',
		'MSRBOT' => 'MSRBOT',
		'Altavista robot' => 'Scooter',
		'AltaVista robot' => 'Altavista',
		'ID-Search Bot' => 'IDBot',
		'eStyle Bot' => 'eStyle',
		'Scrubby robot' => 'Scrubby',
		'Facebook' => 'facebookexternalhit',
		'Serpstat'=>'serpstatbot',
		'Blexbot'=>'blexbot'
	);
	foreach($bots as $name=>$bot){
		if(stringContains($agent,$bot)){
			if($return_name==1){return $name;}
			return true;
		}
	}
	foreach($bots_contains as $bot){
		if(stringContains($agent,$bot)){
			if($return_name==1){return $bot;}
			return true;
		}
	}
	foreach($bots_beginswith as $bot){
		if(stringBeginsWith($agent,$bot)){
        	if($return_name==1){return $bot;}
			return true;
		}
	}
	return false;
}
//---------- begin function isSearchBot---------------------------------------
/**
* @deprecated use isSpider() instead
* @exclude - depreciated
*/
function isSearchBot($agent='',$return_name=0){
	return isSpider($agent,$return_name);
	}
//---------- begin function isWindows ----------
/**
* @describe returns true if the server is windows
* @return boolean
*	returns true if the server is windows
* @usage if(isWindows()){...}
*/
function isWindows(){
	if(PHP_OS == 'WINNT' || PHP_OS == 'WIN32' || PHP_OS == 'Windows'){return true;}
	return false;
}
//---------- begin function getBrowserInfo--------------------
/**
* @describe returns an array of browser info
* @return array
*	os, browser, device, width, height
* @usage $browserInfo=getBrowserInfo();
*/
function getBrowserInfo(){
	//return: array containing os, browser, device and width, height if specified in HTTP_UA_PIXELS
	$info=array(
		'os'	=> $_SERVER['REMOTE_OS'],
		'browser'=>$_SERVER['REMOTE_BROWSER'],
		'device'=> $_SERVER['REMOTE_DEVICE']
		);
	//width and height
	if(isset($_SERVER['HTTP_UA_PIXELS']) && preg_match('/^([0-9]+?)x([0-9]+?)$/',$_SERVER['HTTP_UA_PIXELS'],$whmatch)){
		$info['width']=$whmatch[1];
		$info['height']=$whmatch[2];
    	}
	return $info;
	}
//---------- begin function isImage ----------
/**
* @exclude depreciated - kept for backwards compatibility. Replaced with isImageFile
*/
function isImage($file=''){
	return isImageFile($file);
}
//---------- begin function isImage ----------
/**
* @describe returns true if specified filename is an image - based on file extension and getimagesize
* @param filename string
*	name of the file
* @return boolean
*	returns true if specified filename is an image
* @usage if(isImageFile($filename)){...}
*/
function isImageFile($file=''){
	$mimetype=getFileMimeType($file);
	if(stringContains($mimetype,'image')){return true;}
	$exts=array('jpg','jpeg','gif','png','bmp','tif','tiff','avif');
    $ext=getFileExtension($file);
    if(in_array($ext,$exts)){return true;}
    //if getimagesize succeeds then it is an image
    if(function_exists('getimagesize') && is_file($file) && getimagesize($file)){return true;}
    return false;
	}
//---------- begin function isNum ----------
/**
* @describe returns true if specified string is a number or zero
* @param str string
*	number to check
* @return boolean
*	returns true if specified string is a number or zero
* @usage if(isNum($num)){...}
*/
function isNum($str){
	if(is_numeric($str)){return true;}
	return false;
	}
//---------- begin function isOne ----------
/**
* @describe returns true if specified string is the number 1 regardless if it is a string, integer, or boolean true
* @param str string
*	string/number to check
* @return boolean
*	returns true if specified string is the number 1 regardless if it is a string, integer, or boolean true
* @usage if(isOne($str)){...}
*/
function isOne($str){
	if($str===true){return true;}
	if(is_numeric($str) && $str==1){return true;}
	if($str=='1'){return true;}
	return false;
	}
//---------- begin function isWasqlField ----------
/**
* @describe returns true if specified string begins with underscore. All internal WaSQL fields begin with underscores
* @param str string
*	string to check
* @return boolean
*	returns true if specified string begins with underscore. All internal WaSQL fields begin with underscores
* @usage if(isWasqlField($str)){...}
*/
function isWasqlField($field){
	if(stringBeginsWith($field,'_')){return true;}
	return false;
	}
//---------- begin function isWasqlTable ----------
/**
* @describe returns true if specified string begins with underscore. All internal WaSQL tables begin with underscores
* @param str string
*	string to check
* @return boolean
*	returns true if specified string begins with underscore. All internal WaSQL tables begin with underscores
* @usage if(isWasqlTable($str)){...}
*/
function isWasqlTable($table){
	if(stringBeginsWith($table,'_')){return true;}
	return false;
	}
//---------- begin function isXML ----------
/**
* @describe returns true if specified string is XML
* @param str string
*	str to check
* @return boolean
*	returns true if specified string is XML
* @usage if(isXML($str)){...}
*/
function isXML($str=''){
	if(is_array($str) || is_null($str)){return false;}
	if(strlen($str)==0){return false;}
	if(preg_match('/\<.+?\>/i',$str)){return true;}
	if(preg_match('/[\<\>]/s',$str)){return true;}

	return false;
}
//---------- begin function listFiles--------------------
/**
* @describe returns an array of files in dir
* @param dir string - directory path
* @return array
* @usage $files=listFiles($dir);
*/
function listFiles($dir='.'){
	if(!is_dir($dir)){return null;}
	if ($handle = opendir($dir)) {
    	$files=array();
    	while (false !== ($file = readdir($handle))) {
			if($file == '.' || $file == '..'){continue;}
        	$files[$file] = preg_replace('/[^0-9]/', '', $file);
    		}
    	closedir($handle);
    	$files=array_keys($files);
    	return $files;
		}
	return null;
}
//---------- begin function listFilesEx--------------------
/**
* @describe returns an array of files in dir
* @param dir string - directory path - required
* @param params array
*	[type] - limit results to this type - defaults to file
*	[-recurse] - recurse subdirectories
*	[-dateformat] - return date format
*	[-perms] - limit results to files this these permissions
*	[-lines] - return the line count of each file returned
*	[-sha] - return the sha1 value of each file returned
* 	[-md5] - return the md5 value of each file returned
* 	[-exif] - return the exif data of a file
* @return array
* @usage $files=listFilesEx($dir);
*/
function listFilesEx($dir='.',$params=array()){
	if(!isset($params['type'])){$params['type']='file';}
	if(!isset($params['-dateformat'])){$params['-dateformat']='m/d/Y g:i a';}
	if(is_null($dir) || !is_dir($dir)){return array();}
	//handle multiple types by separating them with [,;|]
	$params['type']=strtolower($params['type']);
	$types=preg_split('/[,;:\|]+/',$params['type']);
	if(isset($params['-perms']) && $params['-perms']){
		unset($myuid);
		unset($mygid);
		if(function_exists('posix_getuid')){
			$myuid=posix_getuid();
        }
    	if(function_exists('posix_getgid')){
			$mygid=posix_getgid();
        }
	}
	if ($handle = opendir($dir)) {
    	$files=array();
    	while (false !== ($file = readdir($handle))) {
			if($file == '.' || $file == '..'){continue;}
			$afile="{$dir}/{$file}";
        	$info=lstat($afile);
        	$fileinfo=array(
        		'name'	=> $file,
        		'path'	=> $dir,
        		'type'	=> filetype($afile),
        		'afile'	=> $afile
				);
        	$ftype=strtolower($fileinfo['type']);
			//skip all but dir,link, and file types.  Possible values are fifo, char, dir, block, link, file, socket and unknown.
			//if(is_dir($afile)){continue;}
			if(!in_array($ftype,array('dir','link','file'))){continue;}
			if(is_file($afile)){
	        	//sha
	        	if(isset($params['-sha']) && $params['-sha'] && function_exists('sha1_file')){
	        		$fileinfo['sha']=sha1_file($afile);
	        	}
	        	//md5
	        	if(isset($params['-md5']) && $params['-md5'] && function_exists('md5_file')){
	        		$fileinfo['md5']=md5_file($afile);
	        	}
	        }
			$ftype=strtolower($fileinfo['type']);
			//skip all but dir,link, and file types.  Possible values are fifo, char, dir, block, link, file, socket and unknown.
			if(!in_array($ftype,array('dir','link','file'))){continue;}
			if(in_array($ftype,array('dir','link')) && isset($params['-recurse']) && $params['-recurse']==1){
				$rfiles=listFilesEx($afile,$params);
				foreach($rfiles as $rfile){$files[]=$rfile;}
            	}
            //filter out types not requested
			if($params['type'] != 'all' && !in_array($ftype,$types)){continue;}
			$ctime=time();
			if(isset($params['-perms']) && $params['-perms']){
				$perms=getFilePerms($afile);
				$fileinfo['user_id']=$info['uid'];
				$fileinfo['group_id']=$info['gid'];
				$fileinfo['perm_read']=0;
				$fileinfo['perm_write']=0;
				$fileinfo['perm_execute']=0;
				if(preg_match('/r/i',$perms['world'])){$fileinfo['perm_read']=1;}
				if(preg_match('/w/i',$perms['world'])){$fileinfo['perm_write']=1;}
				if(preg_match('/x/i',$perms['world'])){$fileinfo['perm_execute']=1;}
				if(isset($myuid) && $myuid==$info['uid']){
					$fileinfo['user_owner']=true;
					if(preg_match('/r/i',$perms['owner'])){$fileinfo['perm_read']=1;}
					if(preg_match('/w/i',$perms['owner'])){$fileinfo['perm_write']=1;}
					if(preg_match('/x/i',$perms['owner'])){$fileinfo['perm_execute']=1;}
	            	}
	            else{$fileinfo['user_owner']=false;}
	            if(isset($mygid) && $mygid==$info['gid']){
					$fileinfo['group_owner']=true;
					if(preg_match('/r/i',$perms['group'])){$fileinfo['perm_read']=1;}
					if(preg_match('/w/i',$perms['group'])){$fileinfo['perm_write']=1;}
					if(preg_match('/x/i',$perms['group'])){$fileinfo['perm_execute']=1;}
	            }
	            else{$fileinfo['group_owner']=false;}
	  			if(function_exists('posix_getpwuid')){
	  				$tmp=@posix_getpwuid($info['uid']);
	  				$fileinfo['user_name']=$tmp['name'];
				}
				if(function_exists('posix_getgrgid')){
	  				$tmp=@posix_getgrgid($info['gid']);
	  				$fileinfo['group_name']=$tmp['name'];
				}
				$fileinfo['perms']=[];
				if($fileinfo['perm_read']==1){$fileinfo['perms'][]='r';}
				if($fileinfo['perm_write']==1){$fileinfo['perms'][]='w';}
				if($fileinfo['perm_execute']==1){$fileinfo['perms'][]='x';}
				$fileinfo['perms']=implode('',$fileinfo['perms']);
			}
			$fileinfo['size']=$info['size'];
			$fileinfo['size_verbose']=verboseSize($info['size']);
			$fileinfo['_cdate_utime']=$info['ctime'];
			$fileinfo['_cdate_age']=$ctime-$fileinfo['_cdate_utime'];
			if($fileinfo['_cdate_age'] < 0){$fileinfo['_cdate_age']=0;}
			$fileinfo['_cdate_age_verbose']=verboseTime($fileinfo['_cdate_age']);
			$fileinfo['_cdate']=date($params['-dateformat'],$fileinfo['_cdate_utime']);
			$fileinfo['_edate_utime']=$info['mtime'];
			$fileinfo['_edate_age']=$ctime-$fileinfo['_edate_utime'];
			if($fileinfo['_edate_age'] < 0){$fileinfo['_edate_age']=0;}
			$fileinfo['_edate_age_verbose']=verboseTime($fileinfo['_edate_age']);
			$fileinfo['_edate']=date($params['-dateformat'],$fileinfo['_edate_utime']);
			$fileinfo['_adate_utime']=$info['atime'];
			$fileinfo['_adate_age']=$ctime-$fileinfo['_adate_utime'];
			if($fileinfo['_adate_age'] < 0){$fileinfo['_adate_age']=0;}
			$fileinfo['_adate_age_verbose']=verboseTime($fileinfo['_adate_age']);
			$fileinfo['_adate']=date($params['-dateformat'],$fileinfo['_adate_utime']);
			if($fileinfo['type']=='file'){
				$fileinfo['ext']=getFileExtension($fileinfo['name']);
            }
            //exif
            if(isset($params['-exif']) && $params['-exif']){
				$exif=getFileExif($fileinfo['afile']);
				if(is_array($exif)){
					$fileinfo['exif']=encodeJSON($exif);
					$fileinfo['exif_count']=count($exif);
				}
				else{
					$fileinfo['exif']='';
					$fileinfo['exif_count']=0;
				}
			}
            //line count
            if(isset($params['-lines']) && $params['-lines']){
            	$fileinfo['lines']=getFileLineCount($afile);
			}
            //filters?
            $skip=0;
            foreach($params as $key=>$val){
				if(preg_match('/^\-/',$key)){continue;}
				if(preg_match('/^(type)$/i',$key)){continue;}
				$key=strtolower($key);
				if(!isset($fileinfo[$key])){continue;}
				//allow for multiple extentions: ext=>'jpg,png,jpeg'
				if($key=='ext'){
					$vals=preg_split('/[\|\,]+/',$val);
					$keep=0;
					foreach($vals as $val){
						if(stringEquals($fileinfo[$key],$val)){
							$keep=1;
							break;
						}
					}
					if($keep==0){$skip+=1;}
				}
				elseif(preg_match('/^(Between|\<\=|\>\=|\<|\>|\=)(.+)$/i',$val,$m)){
					//$d1=date("m/d/Y",$fileinfo[$key]);
					//$d2=date("m/d/Y",$m[2]);
					switch(strtolower($m[1])){
						case 'between':
							$nums=preg_split('/\ and\ /i',trim($m[2]),2);
							if((integer)$fileinfo[$key] < (integer)$nums[0] || (integer)$fileinfo[$key] > (integer)$nums[1]){$skip++;}
							break;
						case '>':
							if((integer)$fileinfo[$key] <= (integer)$m[2]){$skip++;}
							break;
						case '<':
							if((integer)$fileinfo[$key] >= (integer)$m[2]){$skip++;}
							break;
						case '>=':
							if((integer)$fileinfo[$key] < (integer)$m[2]){$skip++;}
							break;
						case '<=':
							if((integer)$fileinfo[$key] > (integer)$m[2]){$skip++;}
							break;
						case '=':
							if((integer)$fileinfo[$key] != (integer)$m[2]){$skip++;}
							break;
                    	}
                	}
				elseif(!stringContains($fileinfo[$key],$val)){$skip++;}
            }
            if($skip > 0){continue;}
			ksort($fileinfo);
        	$files[]=$fileinfo;
    	}
    	closedir($handle);
    	return $files;
	}
	return null;
}
//---------- begin function loadExtras--------------------
/**
* @describe loads additional functions found in the extras folder
* @param extras mixed
*	to load a single extra just pass in the extra name
*	to load multiple extras pass in an array of names
* @return null
* @usage
*	loadExtras('system');
* ---
*	loadExtras(array('fedex','usps','ups));
* ---
*	loadExtras('merchants/nmi');
*/
function loadExtras($extras){
	global $databaseCache;
	if(!is_array($extras)){$extras=array($extras);}
	if(!isset($_SESSION['w_MINIFY']['extras']) || !is_array($_SESSION['w_MINIFY']['extras'])){
    	$_SESSION['w_MINIFY']['extras']=array();
	}
	foreach($extras as $extra){
		if(isset($databaseCache['loadExtras'][$extra])){continue;}
		$databaseCache['loadExtras'][$extra]=1;
		if(is_file($extra)){
        	$phpfile=$extra;
		}
		else{
			$progpath=dirname(__FILE__);
			//for backward compatibility look for nmi, authnet, paypal - they were moved to merchants folder
			if(preg_match('/^(nmi|authnet|ebanx|securenet|stripe)$/i',$extra)){
            	$extra="merchants/{$extra}";
			}
			elseif(preg_match('/^(canada_post|fedex|ups|usps|npf|integracore)$/i',$extra)){
            	$extra="shipping_methods/{$extra}";
			}
			elseif(preg_match('/^(elastic|ccv2|gigya|splunk|firebird|msaccess|msexcel|mscsv|ctree|hana|mssql|mysql|odbc|oracle|pdo|postgresql|snowflake|sqlite|duckdb)$/i',$extra)){
            	$extra="databases/{$extra}";
			}
			//build full path to extra file
			$phpfile="{$progpath}/extras/{$extra}.php";
			if(!is_file($phpfile)){
            	debugValue("loadExtras Error: No such file. {$phpfile}");
            	continue;
			}
		}
		//debugValue("loadExtras[{$extra}]:".$phpfile);
    	@trigger_error("");
		//$evalstring='error_reporting(E_ERROR | E_PARSE);'.PHP_EOL;
		$evalstring='showAllErrors();'.PHP_EOL;
		$evalstring .= 'try{'.PHP_EOL;
		$evalstring .= '	include_once(\''.$phpfile.'\');'.PHP_EOL;
		$evalstring .= '	}'.PHP_EOL;
		$evalstring .= 'catch(Exception $e){'.PHP_EOL;
		$evalstring .= '	debugValue($e);'.PHP_EOL;
		$evalstring .= '	}'.PHP_EOL;
		@eval($evalstring);
		$e=error_get_last();
		if($e['message']!=='' && !preg_match('/Undefined/i',$e['message'])){
    		// An error occurred
    		echo "loadExtras Error loading {$phpfile}".printValue($e);
    		exit;
    		debugValue("loadExtras Error loading {$phpfile}");
    		debugValue($e);
		}
		else{
        	if(!in_array($extra,$_SESSION['w_MINIFY']['extras'])){
	        	$_SESSION['w_MINIFY']['extras'][]=$extra;
			}
		}
	}
}
//---------- begin function loadExtrasCss--------------------
/**
* @describe loads additional css files found in /wfiles/css/extras folder
* @param extras mixed
*	to load a single extra just pass in the extra name
*	to load multiple extras pass in an array of names
* @return null
* @usage 
* 	loadExtrasCss('dropdown');
*	loadExtrasCss(array('tcal','dropdown','custom'));
*/
function loadExtrasCss($extras){
	if(!is_array($extras)){$extras=array($extras);}
	if(!isset($_SESSION['w_MINIFY']['extras_css'][0])){
    	$_SESSION['w_MINIFY']['extras_css']=array();
	}
	if(!isset($_SESSION['w_MINIFY']['extras_js'][0])){
    	$_SESSION['w_MINIFY']['extras_js']=array();
	}
	foreach($extras as $extra){
		switch(strtolower($extra)){
        	case 'bootstrap':
        		$extra='bootstrap/css/bootstrap';
        	break;
        	case 'codemirror':
        	case 'tcal':
        	case 'alertify':
        	case 'slidy':
        		//load the js required for these just in case they don't
        		if(!in_array($extra,$_SESSION['w_MINIFY']['extras_js'])){
        			$_SESSION['w_MINIFY']['extras_js'][]=$extra;
				}
        	break;
		}
		if(!in_array($extra,$_SESSION['w_MINIFY']['extras_css'])){
        	$_SESSION['w_MINIFY']['extras_css'][]=$extra;
		}
	}
}
//---------- begin function loadExtrasFont--------------------
/**
* @describe loads additional fonts found in /wfiles/fonts/extras folder
* @param extras mixed
*	to load a single extra just pass in the extra name
*	to load multiple extras pass in an array of names
* @return null
* @usage
*	loadExtrasFont('arizona');
*	loadExtrasFont(array('arizona','alex-brush','great-vibes'));
*/
function loadExtrasFont($extras){
	if(!is_array($extras)){$extras=array($extras);}
	if(!isset($_SESSION['w_MINIFY']['extras_css'][0])){
    	$_SESSION['w_MINIFY']['extras_css']=array();
	}
	if(!isset($_SESSION['w_MINIFY']['extras_js'][0])){
    	$_SESSION['w_MINIFY']['extras_js']=array();
	}
	foreach($extras as $extra){
		$extra="/wfiles/fonts/extras/{$extra}";
		if(!in_array($extra,$_SESSION['w_MINIFY']['extras_css'])){
        	$_SESSION['w_MINIFY']['extras_css'][]=$extra;
		}
	}
}
//---------- begin function loadExtrasJs--------------------
/**
* @describe loads additional javascript files found in /wfiles/js/extras folder
* @param extras mixed
*	to load a single extra just pass in the extra name
*	to load multiple extras pass in an array of names
* @return null
* @usage
*	loadExtrasJs('dropdown');
*	loadExtrasJs(array('iefix','html5','custom'));
*/
function loadExtrasJs($extras){
	global $CONFIG;
	if(!is_array($extras)){$extras=array($extras);}
	if(!isset($_SESSION['w_MINIFY']['extras_js'][0])){
    	$_SESSION['w_MINIFY']['extras_js']=array();
	}
	if(!isset($_SESSION['w_MINIFY']['extras_css'][0])){
    	$_SESSION['w_MINIFY']['extras_css']=array();
	}
	foreach($extras as $extra){
		switch(strtolower($extra)){
        	case 'angularjs':
        		if($CONFIG['minify_js']){
        			$extra='https://ajax.googleapis.com/ajax/libs/angularjs/1.3.9/angular.min.js';
				}
				else{
        			$extra='https://ajax.googleapis.com/ajax/libs/angularjs/1.3.9/angular.js';
				}
        	break;
        	case 'websockets':
        		$_SESSION['w_MINIFY']['extras_js'][]='/php/extras/websockets/websockets.js';
        	break;
        	case 'signature':
			case 'file':
				$extra='html5';
			break;
			case 'wd3':
				if(!in_array('d3',$_SESSION['w_MINIFY']['extras_js'])){
        			$_SESSION['w_MINIFY']['extras_js'][]='d3';
				}
			break;
			case 'chart':
				//chartjs requires moment for time series charts
				if(!in_array('moment',$_SESSION['w_MINIFY']['extras_js'])){
        			//$_SESSION['w_MINIFY']['extras_js'][]='moment';
				}
			break;
        	case 'codemirror':
        		//load the css required for these just in case they don't
        		if(!in_array($extra,$_SESSION['w_MINIFY']['extras_css'])){
        			$_SESSION['w_MINIFY']['extras_css'][]=$extra;
				}
        	break;
        	case 'tcal':
        	case 'alertify':
        	case 'slidy':
        	case 'quill':
        		//load the css required for these just in case they don't
        		if(!in_array($extra,$_SESSION['w_MINIFY']['extras_css'])){
        			$_SESSION['w_MINIFY']['extras_css'][]=$extra;
				}
        	break;
        	case 'c3':
        		//load the d3 and css
        		if(!in_array('d3',$_SESSION['w_MINIFY']['extras_js'])){
        			$_SESSION['w_MINIFY']['extras_js'][]='d3';
				}
        		if(!in_array($extra,$_SESSION['w_MINIFY']['extras_css'])){
        			$_SESSION['w_MINIFY']['extras_css'][]=$extra;
				}
        	break;
		}
		if(!in_array($extra,$_SESSION['w_MINIFY']['extras_js'])){
        	$_SESSION['w_MINIFY']['extras_js'][]=$extra;
		}
		//load extra codemirror files if needed
		if(strtolower($extra)=='codemirror'){
			//load extra js for codemirror
    		$cpath=getWasqlPath('wfiles/js/extras/codemirror');
    		//echo "codemirror: {$cpath}";exit;
    		$cfiles=listFilesEx($cpath,array('ext'=>'js'));
    		$cnames=array();
    		foreach($cfiles as $cfile){
    			$cname=getFileName($cfile['name'],1);
    			if(stringEndsWith($cname,'.min')){continue;}
    			if(isset($cnames[$cname])){continue;}
    			$cnames[$cname]=1;
    			$cextra="codemirror/{$cname}";
    			if(!in_array($cextra,$_SESSION['w_MINIFY']['extras_js'])){
        			$_SESSION['w_MINIFY']['extras_js'][]=$cextra;
				}
    		}
		}
	}
}
//---------- begin function loadJsFile--------------------
/**
* @describe loads additional javascript functions into minifyJS.php results
* @param files mixed
*	to load a single file just pass in the file name
*	to load multiple files pass in an array of files
* @return null
* @usage
*	loadJsFile('nicedit');
*	loadJsFile(array('jquery','jqueryui'));
*/
function loadJsFile($files){
	if(!is_array($files)){$files=array($files);}
	if(!isset($_SESSION['w_MINIFY']['jsfiles'])){$_SESSION['w_MINIFY']['jsfiles']=array();}
	foreach($files as $file){
		switch(strtolower($file)){
        	case 'jquery':$file='http://code.jquery.com/jquery.min.js';break;
        	case 'jqueryui':
        	case 'jquery-ui':
				$file='http://code.jquery.com/ui/1.10.3/jquery-ui.min.js';
			break;
        	case 'codemirror':loadExtrasJs('codemirror');break;
			case 'nicedit':loadExtrasJs('quill');break;
		}
		if(!in_array($file,$_SESSION['w_MINIFY']['jsfiles'])){
    		$_SESSION['w_MINIFY']['jsfiles'][]=$file;
		}
	}
	return '';
}
//---------- begin function loadPage
/**
* @describe - Load a page with its template or pass in a template to override
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function loadPage($name='',$template='',$debug=''){
	global $PAGE;
	global $TEMPLATE;
	if(!strlen($name)){return "loadPage Error: no name";}
	//get the page record
	$getopts=array('-table'=>'_pages');
	if(isNum($name)){$getopts['_id']=$name;}
	else{$getopts['name']=$name;}
	$lpage=getDBRecord($getopts);
	if(!is_array($lpage)){return "loadPage Error: no such page" . printValue($getopts);}
	if($debug=='page'){return printValue($lpage);}
	if($debug=='pageopts'){return printValue($getopts);}
	//get the template record
	$getopts=array(
		'-table'=>strlen($_REQUEST['_dbname'])?"{$_REQUEST['_dbname']}._templates":"_templates"
		);
	if(strlen($template)){
		if(isNum($template)){$getopts['_id']=$template;}
		else{$getopts['name']=$template;}
		}
	elseif(isNum($_REQUEST['_template'])){$getopts['_id']=$_REQUEST['_template'];}
	elseif(strlen($_REQUEST['_template'])){$getopts['name']=$_REQUEST['_template'];}
	else{$getopts['_id']=$lpage['_template'];}
	$tpage=getDBRecord($getopts);
	if(!is_array($tpage)){return "loadPage Error: no such template" . printValue($getopts);}
	if($debug=='template'){return printValue($tpage);}
	if($debug=='templateopts'){return printValue($getopts);}
	$PAGE=$lpage;
	$TEMPLATE=$tpage;
	//Load template Functions
	if(strlen(trim($TEMPLATE['functions']))){
		$ok=includeDBOnce(array('-table'=>'_templates','-field'=>'functions','-where'=>"_id={$TEMPLATE['_id']}"));
		if(!isNum($ok)){echo $ok;}
    }
    //load page functions
    if(strlen(trim($PAGE['functions']))){
		$ok=includeDBOnce(array('-table'=>'_pages','-field'=>'functions','-where'=>"_id={$PAGE['_id']}"));
		if(!isNum($ok)){echo $ok;}
    }
    $htm=$tpage['body'];
	$htm=evalPHP($htm);
    $htm=str_replace('@self(body)',$lpage['body'],$htm);
    $htm = evalPHP($htm);
    return trim($htm);
	}
//---------- begin function loremIpsum--------------------
/**
* @describe returns fake text for use in fill during design.
* @param len integer - the max char length to return
* @return string
* @usage loremIpsum(500); creates a fake text string 500 chars long
*/
function loremIpsum($length=300,$end='.'){
	$wordstr="lorem ipsum dolor sit amet consectetur adipiscing elit vestibulum volutpat mollis tempus quisque in elit at lacus feugiat condimentum";
	$words=preg_split('/\ /',strtolower($wordstr));
	$lorem='';
	while(strlen($lorem) < $length){
    	$sentance_word_count=rand(4,10);
    	$sentence_words=array();
    	$charcnt=0;
    	for($i=0;$i<$sentance_word_count;$i++){
			if($charcnt > $length){break;}
			$word_index=rand(0,count($words));
			if(!isset($words[$word_index])){continue;}
			$word=$words[$word_index];
			if($i==0){$word=ucfirst($word);}
			$sentence_words[]=$word;
			$charcnt += strlen($word);
        }
        $lorem .= implode(' ',$sentence_words).$end.' ';
	}
	return trim($lorem);
}
//---------- begin function getFilePerms--------------------
/**
* @describe returns true if $str begins with $search
* @param file string - path and file
* @return array
* @usage $perms=getFilePerms($file);
*/
function getFilePerms($file=''){
	$info=array();
	$perms = fileperms($file);
	if (($perms & 0xC000) == 0xC000) {
    	// Socket
    	$info['all'] = 's';
		}
	elseif (($perms & 0xA000) == 0xA000) {
    	// Symbolic Link
    	$info['all'] = 'l';
		}
	elseif (($perms & 0x8000) == 0x8000) {
    	// Regular
    	$info['all'] = '-';
		}
	elseif (($perms & 0x6000) == 0x6000) {
    	// Block special
    	$info['all'] = 'b';
		}
	elseif (($perms & 0x4000) == 0x4000) {
    	// Directory
    	$info['all'] = 'd';
		}
	elseif (($perms & 0x2000) == 0x2000) {
    	// Character special
    	$info['all'] = 'c';
		}
	elseif (($perms & 0x1000) == 0x1000) {
    	// FIFO pipe
    	$info['all'] = 'p';
		}
	else {
    	// Unknown
    	$info['all'] = 'u';
		}
	// Owner
	$val = (($perms & 0x0100) ? 'r' : '-');
	$val .= (($perms & 0x0080) ? 'w' : '-');
	$val .= (($perms & 0x0040) ?(($perms & 0x0800) ? 's' : 'x' ):(($perms & 0x0800) ? 'S' : '-'));
	$info['all'] .= $val;
	$info['owner']=$val;
	// Group
	$val = (($perms & 0x0020) ? 'r' : '-');
	$val .= (($perms & 0x0010) ? 'w' : '-');
	$val .= (($perms & 0x0008) ?(($perms & 0x0400) ? 's' : 'x' ):(($perms & 0x0400) ? 'S' : '-'));
	$info['all'] .= $val;
	$info['group']=$val;
	// World
	$val = (($perms & 0x0004) ? 'r' : '-');
	$val .= (($perms & 0x0002) ? 'w' : '-');
	$val .= (($perms & 0x0001) ?(($perms & 0x0200) ? 't' : 'x' ):(($perms & 0x0200) ? 'T' : '-'));
	$info['all'] .= $val;
	$info['world']=$val;
	return $info;
	}
//---------- begin function niftyPlayer
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function niftyPlayer($params=array()){
	if(!isset($params['file'])){return "niftyPlayer Error: No File";}
	if(!isset($params['autostart'])){$params['autostart']=0;}
    if(!isset($params['id'])){$params['id']='niftyPlayer1';}
	$npTxt='';
	$divid=$params['id']."_div";
	$npTxt .= '<div id="'.$divid.'">'.PHP_EOL;
	if(isset($params['onload']) && $params['onload']==1){
		$npTxt .= '<img src="/wfiles/clear.gif" alt="Nifty Player" onLoad="embedFlash(\'/wfiles/niftyplayer.swf?file='.$params['file'].'\',{width:165,height:38,id:\''.$divid.'\',name:\''.$params['id'].'\'});">'.PHP_EOL;
    	}
	else{
		$npTxt .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="165" height="38" id="'.$params['id'].'" align="">'.PHP_EOL;
	  	$npTxt .= '	<param name=movie value="/wfiles/niftyplayer.swf?file='.$params['file'].'">'.PHP_EOL;
	  	$npTxt .= '	<param name=quality value="high">'.PHP_EOL;
	  	$npTxt .= '	<param name=bgcolor value="#FFFFFF">'.PHP_EOL;
	  	$npTxt .= '	<embed src="/wfiles/niftyplayer.swf?file='.$params['file'].'" quality="high" bgcolor="#FFFFFF" width="165" height="38" name="'.$params['id'].'" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">'.PHP_EOL;
	  	$npTxt .= '	</embed>'.PHP_EOL;
		$npTxt .= '</object>'.PHP_EOL;
		}
	$npTxt .= '</div>'.PHP_EOL;
	//return $npTxt;
	//events:
	$npevents=array('onPlay', 'onStop', 'onPause', 'onError', 'onSongOver', 'onBufferingComplete', 'onBufferingStarted');
	$npJs=0;
	$npTxt .= '<script type="text/javascript">'.PHP_EOL;
	$npTxt .= '	function niftyStart(){'.PHP_EOL;
	foreach($npevents as $npevent){
		if(isset($params[$npevent]) && strlen($params[$npevent])){
			$npTxt .= '		niftyplayer(\''.$params['id'].'\').registerEvent(\''.$npevent.'\', \''.$params[$npevent].'\');'.PHP_EOL;
			$npJs++;
        	}
 		}
 	if(isset($params['autostart']) && $params['autostart']==1){
		$npTxt .= '		niftyplayer(\''.$params['id'].'\').play();'.PHP_EOL;
		$npJs++;
    	}
    $npTxt .= '		return true;'.PHP_EOL;
    $npTxt .= '		}'.PHP_EOL;
 	if($npJs > 0){
		//$npTxt .= buildOnLoad("niftyStart();");
		$npTxt .= '	if (window.addEventListener){window.addEventListener("load",niftyStart,false);}'.PHP_EOL;
		$npTxt .= '	else if (window.attachEvent){window.attachEvent("onload",niftyStart);}'.PHP_EOL;
    	}
    $npTxt .= '</script>'.PHP_EOL;
	return $npTxt;
	}
//---------- begin function minifyCode--------------------
/**
* @describe minifies javascript and CSS code.
*	Js is minified using http://javascript-minifier.com/raw service
*	Css is minified using http://cssminifier.com/raw service
* @param code string - code to minify
* @param type string - js or css
* @return string
* @usage $minified=minifyCode($css_text,'css');
*/
function minifyCode($code,$type) {
	if(!strlen($code)){return 'no code';}
	if(!strlen($type)){return 'no type';}
	switch(strtolower($type)){
		case 'js':
		case 'css':
			//strip out multi-line comments first
			$code=preg_replace('/\/\*(.+?)\*\//s','',$code);
			//check each line and remove single line comments
			$lines=preg_split('/[\r\n]+/',$code);
			foreach($lines as $i=>$line){
				$lines[$i]=trim($lines[$i]);
				if(preg_match('/^\/\//',$lines[$i])){unset($lines[$i]);}
			}
			//recombine without carriage returns
			$code=implode('',$lines);
			return $code;
			break;
		case 'css':
			require_once("min-css.php");
			//remove @import lines at the beginning first then add them back in
			$lines=preg_split('/[\r\n]+/',$code);
			$importlines=array();
			foreach($lines as $i=>$line){
				if(preg_match('/\@import\ /i',ltrim($line))){
					$importlines[]=rtrim($line);
					unset($lines[$i]);
				}
			}
			if(count($importlines)){
				$code=implode(PHP_EOL,$lines);
				$code = CssMin::minify($code);
				$pre=implode(PHP_EOL,$importlines);
				$code=$pre.PHP_EOL.$code;
			}
			else{
				$code = CssMin::minify($code);
			}
			break;
	}
	return $code;
}
//---------- begin function monthName--------------------
/**
* @describe converts a month number to a month names
* @param month integer - 1--12
* @return string - month name
* @usage $monthName=monthName(3); - returns March
*/
function monthName($month,$format='F'){
	switch(strtoupper($format)){
    	case 'M':
    	case 'SHORT':
    	case '3':
    	case 'ABBR':
    		$format='M';
    	break;
    	default:
    		$format='F';
    	break;
	}
	//convert a month number to a month name
	if(!isNum($month)){return $month;}
	return date($format, mktime(0, 0, 0, $month, 10));
}
//---------- begin function mysqlDate---------------------------------------
/**
* @describe returns mysql formated date
* @param [t] int -timestamp return date based on a timestamp
* @return
*	mysql formated date
* @usage $cdate = mysqlDate();
*/
function mysqlDate($t=0){
	if($t==0){return date("Y-m-d");}
	return date("Y-m-d",$t);
}
//---------- begin function mysqlDateTime---------------------------------------
/**
* @describe returns mysql formated datetime string
* @param [t] int -timestamp return date based on a timestamp
* @return
*	mysql formated datetime
* @usage $cdate = mysqlDateTime();
*/
function mysqlDateTime($t=0){
	if($t==0){return date("Y-m-d H:i:s");}
	return date("Y-m-d H:i:s",$t);
}
//---------- begin function stringBeginsWith--------------------
/**
* @describe wrapper for number_format
* @param num integer
* @param dec integer - number of decimals
* @return number
* @usage $amount=numberFormat($amt,2);
*/
function numberFormat($num=0,$dec=0){
	if(!isNum($num)){return '';}
	if($num==0){return '';}
	return number_format($num,$dec);
	}
//---------- begin function ordinalSuffix--------------------
/**
* @describe adds the English ordinal suffix to any number. i.e. 179th or 1st
* @param num integer
* @return string
* @usage ordinalSuffix(15); 
*/
function ordinalSuffix($num) {
	if (!in_array(($num % 100),array(11,12,13))){
	    switch ($num % 10) {
	    	// Handle 1st, 2nd, 3rd
	      	case 1:  return $num.'st';
	      	case 2:  return $num.'nd';
	      	case 3:  return $num.'rd';
	    }
	}
  	return $num.'th';
}
//---------- begin function parseKeyValueString--------------------
/**
* @describe converts a key/value string into an array
* @param str string
* @return array
* @usage $args=parseKeyValueString($url_string);
*/
function parseKeyValueString($kvs=''){
    $rtn = array();
	parse_str(trim($kvs),$rtn);
    return $rtn;
	}

//---------- begin function ping--------------------
/**
* @describe pings $host and returns false on failure or number of milliseconds on success
* @param host string - hostname
* @param port integer - port to ping - defaults to 80
* @param timeout integer - timeout - defaults to 10
* @return mixed false on failure or number of milliseconds on success
* @usage $p=ping('google.com');
*/
function ping($host, $port=80, $timeout=10){
	try {
		$tB = microtime(true);
		$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
	  	if (!$fp) {
			//ping failed
			return false;
			}
		fclose($fp);
		//ping was a success - return milliseconds it took
	  	$tA = microtime(true);
		return round((($tA - $tB) * 1000), 0);
		}
	catch (Exception $e){
        return false;
        }
	}
//---------- begin function parseSimpleAttributes
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function parseSimpleAttributes($obj=object){
	$info=array();
	foreach($obj->attributes() as $key=>$val){
		$key=(string)$key;
		$info[$key]=(string)$val;
    	}
    return $info;
	}
//---------- begin function getAgentBrowser--------------------
/**
* @describe parses the user_agent and returns the browser name
* @param [agent] string - defaults to $_SERVER['HTTP_USER_AGENT']
* @reference - https://us.php.net/manual/en/function.get-browser.php
* @return string - browser name
* @usage $browser=getAgentBrowser();
*/
function getAgentBrowser($agent=''){
	// Declare known browsers to look for
	$knowns = array(
		"firefox", "trident","msie", "opera", "opr", "chrome", "safari",
        "mozilla", "seamonkey", "konqueror", "netscape",
        "gecko", "navigator", "mosaic", "lynx", "amaya",
        "omniweb", "avant", "camino", "flock", "aol","imified"
		);
	$agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
	$browser=array('agent'=>$agent,'browsers'=>$knowns);
	$pattern = '#(?<browser>' . join('|', $knowns) . ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';
	// Find all phrases (or return empty array if none found)
	if (!preg_match_all($pattern, $agent, $matches)){return array();}
	$mcount=count($matches['browser']);

	foreach($knowns as $known){
		for($i=0;$i<$mcount;$i++){
			if($known == $matches['browser'][$i]){
				$browser['match']=$known;
				$browser['index']=$i;
				$browser['browser']=$matches['browser'][$i];
				$browser['version']=$matches['version'][$i];
				//fix for opera
				if($browser['browser']=='opr'){
					$browser['browser']='opera';
				}
				elseif($browser['browser']=='trident'){
					$browser['browser']='msie';
					if(preg_match('/trident\/([0-9\.]+)/i',$browser['agent'],$m)){
						$browser['version']=round($m[1]+4,1);
					}
				}
				//fix for MSIE 8 -  it reports 7
				if($browser['browser']=='msie'){
					$version=(integer)$browser['version'];
					if($version==7 && preg_match('/compatible/i',$browser['agent']) && preg_match('/trident\/4.0/i',$browser['agent'])){
						$browser['version']=8;
					}
					if($version==7 && preg_match('/compatible/i',$browser['agent']) && preg_match('/trident\/5.0/i',$browser['agent'])){
						$browser['version']=9;
					}
				}
				return $browser;
            	}
        	}
    	}
    return array();
	}
//---------- begin function getAgentOS--------------------
/**
* @describe parses the user_agent and returns the operating system name
* @param [agent] string - defaults to $_SERVER['HTTP_USER_AGENT']
* @return string - operating system name name
* @usage $browser=getAgentOS();
*/
function getAgentOS($agent=''){
	$agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
	$bot=isSearchBot($agent,1);
	if(strlen($bot)){return "BOT:{$bot}";}
	//list of OS's and the match that goes with them
	$OSList = array(
		'Windows 3.11' => 'Win16',
		'Windows 95' => '(Windows 95)|(Win95)|(Windows_95)',
		'Windows 98' => '(Windows 98)|(Win98)',
		'Windows 2000' => '(Windows NT 5.0)|(Windows 2000)',
		'Windows XP' => '(Windows NT 5.1)|(Windows XP)',
		'Windows Server 2003' => '(Windows NT 5.2)',
		'Windows Vista' => '(Windows NT 6.0)',
		'Windows 7' => '(Windows NT 6.1)',
		'Windows NT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
		'Windows ME' => 'Windows ME',
		'Open BSD' => 'OpenBSD',
		'Sun OS' => 'SunOS',
		'iPhone' => 'iPhone',
		'iPod'	=> 'iPod',
		'iPad'	=> 'iPad',
		'Linux' => '(Linux)|(X11)',
		'Mac OS' => '(Mac_PowerPC)|(Macintosh)|(Mac OS)|(Silk-Accelerated)',
		'AppleWebKit' => 'AppleWebKit',
		'QNX' => 'QNX',
		'BeOS' => 'BeOS',
		'OS\/2' => 'OS\/2',
		);
	// Loop through the array of user agents and matching operating systems
	foreach($OSList as $CurrOS=>$Match){
		// Check for a match
		$reg='/'.$Match.'/i';
		if (preg_match($reg, $agent)){return $CurrOS;}
		}
	return 'Unknown';
	}
//---------- begin function parseEnv
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function parseEnv() {
	//CLI scripts will not have a user agent - just return
	if(!isset($_SERVER['HTTP_USER_AGENT'])){return;}
	$agent=$_SERVER['HTTP_USER_AGENT'];
	//REMOTE_BROWSER and REMOTE_BROWSER_VERSION
	$browser=getAgentBrowser($agent);
	//Check for applewebkit - safari
	if(preg_match('/MSIE/i',$agent) && !preg_match('/Opera/i',$agent)) { 
        $_SERVER['REMOTE_BROWSER']="msie";
		$_SERVER['REMOTE_BROWSER_VERSION']=$browser['version'];
    }
	elseif(preg_match('/Edge/i',$agent)){ 
       $_SERVER['REMOTE_BROWSER']="edge";
		$_SERVER['REMOTE_BROWSER_VERSION']=$browser['version']; 
    } 
    elseif(preg_match('/SkypeUriPreview/i',$agent)){ 
       $_SERVER['REMOTE_BROWSER']="skype";
		$_SERVER['REMOTE_BROWSER_VERSION']=$browser['version']; 
    } 
	if(preg_match('/\ opr\//i',$agent)){
		$_SERVER['REMOTE_BROWSER']="opera";
		$_SERVER['REMOTE_BROWSER_VERSION']=$browser['version'];
    	}
    elseif(preg_match('/\ chrome\//i',$agent)){
		$_SERVER['REMOTE_BROWSER']="chrome";
		$_SERVER['REMOTE_BROWSER_VERSION']=$browser['version'];
    	}
	elseif(preg_match('/applewebkit/i',$agent)){
		$_SERVER['REMOTE_BROWSER']="safari";
		$_SERVER['REMOTE_BROWSER_VERSION']=$browser['version'];
    	}
	elseif(isset($browser['browser'])){
		$_SERVER['REMOTE_BROWSER']=$browser['browser'];
		$_SERVER['REMOTE_BROWSER_VERSION']=$browser['version'];
		}
	else{
		$_SERVER['REMOTE_BROWSER']='Unknown';
		$_SERVER['REMOTE_BROWSER_VERSION']='Unknown';
		}
	//REMOTE_OS
	$_SERVER['REMOTE_OS']=getAgentOS();
	//Unique Host
	if(!isset($_SERVER['UNIQUE_HOST'])){
		$_SERVER['UNIQUE_HOST']=getUniqueHost($_SERVER['HTTP_HOST']);
	}
	//Unique Host
	if(isset($_SERVER['HTTP_REFERER']) && !isset($_SERVER['UNIQUE_REFERER'])){
		$_SERVER['UNIQUE_REFERER']=getUniqueHost(parse_url($_SERVER['HTTP_REFERER'],PHP_URL_HOST));
	}
	//Subdomain
	if(!isset($_SERVER['SUBDOMAIN'])){
		$_SERVER['SUBDOMAIN']=getSubdomain($_SERVER['HTTP_HOST']);
	}
	//Request Path
	if(isset($_SERVER['REQUEST_URI'])){
		$uri=$_SERVER['REQUEST_URI'];
		$uri=preg_replace('/\?.*/', '', $uri);
		$expr='/^(http|https):\/\/'.$_SERVER['UNIQUE_HOST'].'/';
		$uri=preg_replace($expr, '', $uri);
		if(isset($_SERVER['SCRIPT_NAME'])){
			$uri=str_replace($_SERVER['SCRIPT_NAME'],'',$uri);
			}
		$uri=preg_replace('/^[\/]+/', '', $uri);
		if(strlen($uri) && !preg_match('/\/admin$/i',$uri)){
			$parts=preg_split('/[\\/]/',$uri);
			$last=array_pop($parts);
			if(preg_match('/\./',$last)){array_push($parts,$last);}
			$_SERVER['REQUEST_PATH']=implode('/',$parts);
			}
    	}
	$_SERVER['REMOTE_LANG']=getAgentLang();
	if(preg_match('/^[a-z]{2,2}\-([a-z]{2,2})$/i',$_SERVER['REMOTE_LANG'],$m)){
    	$_SERVER['REMOTE_COUNTRY']=$m[1];
	}
	//Set REMOTE_DEVICE
	//$_SERVER['REMOTE_DEVICE_TYPE']=getRemoteDeviceType();
	if(preg_match('/mac os/i',$_SERVER['REMOTE_OS'])){
		if(preg_match('/iphone\;/i',$_SERVER['HTTP_USER_AGENT'])){
			$_SERVER['REMOTE_DEVICE']='iPhone';
			}
		elseif(preg_match('/ipad\;/i',$_SERVER['HTTP_USER_AGENT'])){
			$_SERVER['REMOTE_DEVICE']='iPad';
			}
		elseif(preg_match('/silk\-accelerated/i',$_SERVER['HTTP_USER_AGENT'])){
			$_SERVER['REMOTE_DEVICE']='Kindle Fire';
			}
		else{
			$_SERVER['REMOTE_DEVICE']='Mac';
        	}
		}
	elseif(isMobileDevice()){
		$_SERVER['REMOTE_DEVICE']='Mobile';
    	}
    else{$_SERVER['REMOTE_DEVICE']='PC';}
	$_SERVER['REMOTE_MOBILE']=isMobileDevice()?true:false;
	return $_SERVER['REMOTE_OS'];
	}
//---------- begin function getAgentLang--------------------
/**
* @describe parses the user_agent and returns the language name
* @param [agent] string - defaults to $_SERVER['HTTP_USER_AGENT']
* @return string - language name
* @usage $lang=getAgentLang();
*/
function getAgentLang($agent=''){
	$agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
	$found=0;
	if(preg_match('/^(.+?)\((.+?)\)/',$agent,$matches)){
		$parts=preg_split('/[\;\,]+/',$matches[2]);
		//check for en-US
		foreach($parts as $part){
			$part=trim($part);
			if(preg_match('/^[a-z]{2,2}\-[a-z]{2,2}$/i',$part)){return $part;}
        }
		//check for en
		foreach($parts as $part){
			$part=trim($part);
			if(preg_match('/^[a-z]{2,2}$/i',$part)){return $part;}
        }
    }
    if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
		$parts=preg_split('/[\;\,]+/',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
		foreach($parts as $part){
			$part=trim($part);
			if(preg_match('/^[a-z]{2,2}\-[a-z]{2,2}$/i',$part)){return $part;}
        }
		//check for en
		foreach($parts as $part){
			$part=trim($part);
			if(preg_match('/^[a-z]{2,2}$/i',$part)){return $part;}
        }
    	return $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }
    return 'en-us';
}
//---------- begin function getWasqlPath--------------------
/**
* @describe return the absolute path to where WaSQL is running from
* @param [subdir] string - additional directory to append
* @return string - WaSQL Path
* @usage $path=getWasqlPath('php');
*/
function getWasqlPath($subdir=''){
	global $getWasqlPathCache;
	$key=$subdir.'x';
	if(isset($getWasqlPathCache[$key])){
		return $getWasqlPathCache[$key];
	}
	$path=dirname( dirname(__FILE__) );
	if(isWindows()){
		$rtnpath="{$path}\\{$subdir}";
		$rtnpath=str_replace("/","\\",$rtnpath);
	}
	else{
    	$rtnpath="{$path}/{$subdir}";
	}
	$getWasqlPathCache[$key]=$rtnpath;
	return $rtnpath;
}
//---------- begin function getWasqlTempPath--------------------
/**
* @describe return the absolute path the wasql temp directory
* @return string - WaSQL Temp Path
* @usage $path=getWasqlTempPath();
*/
function getWasqlTempPath(){
	return getWasqlPath('php/temp');
}
//---------- begin function getD3Color--------------------
/**
* @describe returns a color used in D3js category color ranges
* @param int integer - index number of color wanted
* @return string - hex value for color
* @usage $color=getD3Color(3);
* @link http://bl.ocks.org/aaizemberg/78bd3dade9593896a59d
*/
function getD3Color($i=0,$cat='20'){
	switch(strtolower($cat)){
		case '10':
			$color=array('#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b','#e377c2','#7f7f7f','#bcbd22','#17becf');
		break;
		case '10c':
			$color=array('#3366cc','#dc3912','#ff9900','#109618','#990099','#0099c6','#dd4477','#66aa00','#b82e2e','#316395');
		break;
		case '20':
			$color=array(
				'#1f77b4','#aec7e8','#ff7f0e','#ffbb78','#2ca02c','#98df8a','#d62728','#ff9896','#9467bd','#c5b0d5',
				'#8c564b','#c49c94','#e377c2','#f7b6d2','#7f7f7f','#c7c7c7','#bcbd22','#dbdb8d','#17becf','#9edae5');
		break;
		case '20b':
			$color=array(
				'#393b79','#5254a3','#6b6ecf','#9c9ede','#637939','#8ca252','#b5cf6b','#cedb9c','#8c6d31','#bd9e39',
				'#e7ba52','#e7cb94','#843c39','#ad494a','#d6616b','#e7969c','#7b4173','#a55194','#ce6dbd','#de9ed6');
		break;
		case '20c':
			$color=array(
				'#3182bd','#6baed6','#9ecae1','#c6dbef','#e6550d','#fd8d3c','#fdae6b','#fdd0a2','#31a354','#74c476',
				'#a1d99b','#c7e9c0','#756bb1','#9e9ac8','#bcbddc','#dadaeb','#636363','#969696','#bdbdbd','#d9d9d9');
		break;
	}
	return $color[$i];
}
//---------- begin function getDaysInMonth--------------------
/**
* @describe return the number of days in any given month
* @param datestring string - a timestamp or string representing a date
* @return int integer - the number of days in the month
* @usage $n=getDaysInMonth(time());
*/
function getDaysInMonth($date){
	if(!isNum($date)){$date=strtotime($date);}
 	return date('t', $date);
}
//---------- begin function getFirstMonthDay--------------------
/**
* @describe return the day the month starts 0-6 with 0 as Sunday
* @param datestring string - a timestamp or string representing a date
* @return int integer - the day the month starts 0-6 with 0 as Sunday
* @usage $n=getFirstMonthDay(time());
*/
function getFirstMonthDay($date){
	if(!isNum($date)){$date=strtotime($date);}
	$str=date('F 1 Y',$date);
 	return date('w', strtotime($str));
}
//---------- begin function getWeekNumber--------------------
/**
* @describe return the week num
* @param datestring string - a timestamp or string representing a date
* @return int integer - the number of the week that the said date is in (1 through 5)
* @usage $wnum=getWeekNumber(time());
*/
function getWeekNumber($date){
	global $getWeekNumber;
	$start=microtime(true);
	if(!isNum($date)){$date=strtotime($date);}
	$key=date('W',$date);
	if(isset($getWeekNumber[$key])){return $getWeekNumber[$key];}
	$first_month_day=getFirstMonthDay($date);
	$days_in_month=getDaysInMonth($date);
	$day_of_month=date('j',$date);
	$group=array();
	$g=array();
	$d=1;
	//week (w) should is never bigger than 7
	for($w=1;$w<7;$w++){
		for($x=0;$x<7;$x++){
			if($w==1 && $x < $first_month_day){continue;}
			if($d == $day_of_month){
				$getWeekNumber[$key]=$w;
				return $w;
				}
			$d++;
		}
	}
	$stop=microtime(true);
	$diff=$stop-$start;
	return 0;
}
//---------- begin function getWfilesPath
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getWfilesPath(){
	$progpath=dirname(__FILE__);
	$wfiles=preg_replace('/php$/i','',$progpath) . "wfiles";
	$wfiles=str_replace("\\","/",$wfiles);
	return $wfiles;
	}
//---------- begin function grepFiles--------------------
/**
* @describe greps files in $path for $q and returns and array of files - searches filenames and contents
* @param query string
* @param path string
* @param recurse boolean - defaults to true
* @return array - array of files
* @usage $files=grepFiles($query,$path,0);
*/
function grepFiles($q, $path,$recurse=1){
	$slash=stringContains($_SERVER['SERVER_SOFTWARE'], "win")?"\\":"/";
	$rtn=array();
	$fp = opendir($path);
	while($f = readdir($fp)){
		// ignore symbolic links
		if( preg_match('/^\.+$/', $f)){continue;}
		$afile = $path.$slash.$f;
		if(is_dir($afile) && $recurse==1){
			$tmps = grepFiles($q,$afile);
			foreach($tmps as $f=>$tmp){
				foreach($tmp as $t=>$c){$rtn[$f][$t]=$c;}
				}
			}
		elseif(is_file($afile)){
			if(stringContains(file_get_contents($afile),$q)){
				$row=0;
				//$rtn[$afile][$row]="FOUND";
				$lines=file($afile);
				foreach($lines as $line){
					$row++;
					if(!strlen(trim($line))){continue;}
					if(stringContains($line,$q)){
						$rtn[$afile][$row]=$line;
	                	}
	            	}
				}
			}
		}
	return $rtn;
	}
//---------- begin function embedPDF--------------------
/**
* @describe returns and html embed tag for use in embedding PDF documents
* @param file string - web path to pdf file
* @param params array - additional options to include in embed tag
*	toolbar, navpanes,scrollbar, width, height
* @return string - html embed tag
* @usage embedPDF($file);
*/
function embedPDF($file='',$params=array()){
	$html='<embed bgcolor="#f2f2f2" src="'.$file.'#';
	$opts=array();
	foreach($params as $key=>$val){
		if(preg_match('/^\-/',$key)){continue;}
		array_push($opts,$key.'='.$val);
		}
	if(count($opts)){$html .= implode('&',$opts);}
	$html .= '">';
	return $html;
	}
//---------- begin function confValue
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function confValue($fld=''){
	return configValue($fld);
}
//---------- begin function configValue---------------------------------------
/**
* @describe returns the CONFIG value of the field specified
* @param field string
*	name of the field you wish to return
* @param eval boolean
*	run evalPHP on string before returning
* @return str value
*	returns the CONFIG value of the field specified
* @usage 
* 	configValue('name');
* 	configValue('title',true);
*/
function configValue($field,$e=0){
	global $CONFIG;
	if(isset($CONFIG[$field])){
		if($e==1){return evalPHP($CONFIG[$field]);}
		return $CONFIG[$field];
		}
	return '';
	}
//---------- begin function pageValue--------------------------
/**
* @describe returns the value of the page
* @param field string - name of the field you wish to return
* @param page string - name (or id) of the page you wish to return - optional. Defaults to the current page
* @return str value - returns the value of the field of the page specified or the current page is a page is not specified
* @usage
*	pageValue('name'); - returns the name of the current page being viewed
*	pageValue('title','about'); - returns the title of the page named 'about'
*	pageValue('title',4); - returns the title of the page with an id of 4
*/
function pageValue($field,$pagename=''){
	global $PAGE;
	if((!strlen($pagename) || $PAGE['name']==$pagename)){
		if(isset($PAGE[$field])){return $PAGE[$field];}
		else{return "pageValue Error! Unknown page field: '{$field}'";}
	}
	//if pagename is not blank then they are requesting a different page value
	$recopts=array('-table'=>'_pages','-fields'=>$field);
	if(isNum($pagename)){$recopts['_id']=$pagename;}
	else{$recopts['name']=$pagename;}
	$rec=getDBRecord($recopts);
	if(is_array($rec)){
    	if(isset($rec[$field])){return $rec[$field];}
    	else{return "pageValue Error! Unknown page field: '{$field}' for page '{$pagename}'";}
	}
	return "pageValue Error! Unknown page: '{$pagename}'";
}
//---------- begin function settingsValue
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function settingsValue($id=0,$field=''){
	$getopts=array('-table'=>'_settings','-order'=>"user_id ASC",'-nocache'=>1);
	if(isNum($id) && $id != 0){$getopts['-where'] = "user_id in (0,{$id})";}
	else{$getopts['-where'] = "user_id=0";}
	if(strlen($field)){
		$getopts['-where'] .= " and key_name='{$field}'";
	}
	$rec=getDBRecord($getopts);
	if(isset($rec['key_value'])){
		$val=$rec['key_value'];
		if(!is_array($val) && isXML($val)){$val=xml2Array($val);}
		return $val;
	}
	return '';
}
//---------- begin function settingsValues
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function settingsValues($id=0,$fields=array()){
	//query the _settings table for records that are global (0) or are tied to this user
	if(!isDBTable('_settings')){return array();}
	$getopts=array('-table'=>'_settings','-order'=>"user_id ASC",'-nocache'=>1);
	if(!is_array($id) && isNum($id)){$getopts['-where'] = "user_id in (0,{$id})";}
	else{$getopts['-where'] = "user_id=0";}
	if(count($fields)){
		$vals=implode("','",$fields);
		$getopts['-where'] .= " and key_name in ('{$vals}')";
		}
	$recs=getDBRecords($getopts);
	//return a blank array if no settings
	$settings=array();
	if(!is_array($recs)){return $settings;}
	//set the settings key/value pairs
	foreach($recs as $rec){
		$key=$rec['key_name'];
		$val=$rec['key_value'];
		if(isXML($val)){$val=xml2Array($val);}
		$settings[$key]=$val;
    	}
	return $settings;
	}
//---------- begin function postEditSha---------------------------------------
/**
* @describe postEditSha is used to communicate with postedit.exe
* @param $tables array - key is tablename and value is an array of fields to check
* @return $recs array - list of shas foreach table and field
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postEditSha($pextables=array()){
	global $USER;
	$shas=array(
		'tables'=>array(),
		'records'=>array(),
		'pexetables'=>$pextables
	);
	foreach($pextables as $tablename=>$tablefields){
		$fields=array();
		if(!is_array($tablefields)){
			$tablefields=preg_split('/\,/',$tablefields);
		}
		if(!isset($shas['tables'][$tablename])){
			$shas['tables'][$tablename]=$tablefields;
		}
		foreach($tablefields as $field){
			$fields[]=$field;
		}
		if(!count($fields)){continue;}
		$fieldstr=implode(',',$fields);
		$query="select _id,name,{$fieldstr} from {$tablename}";
		$query_result=@databaseQuery($query);
		if(!$query_result){
			$e=getDBError();
			echo "query_result is blank ".printValue($e);
			exit;
		}
		while ($row = databaseFetchAssoc($query_result)) {
			$rec=array();
			foreach($row as $key=>$val){
				$key=strtolower($key);
				$val=trim($val);
				switch($key){
					case '_id':
					case 'name':
						$rec[$key]=$val;
					break;
					default:
						if(strlen($val)){
							$rec[$key]=sha1($val);
						}
					break;
				}
			}
			$shas['records'][$tablename][]=$rec;
		}
	}
	return json_encode($shas,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
}

$GLOBALS['crc32Table']=array();        // Lookup table array
crc32InitTable();
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function crc32InitTable() {            // Builds lookup table array
    // This is the official polynomial used by CRC-32 in PKZip, WinZip and Ethernet.
    $polynomial = 0x04c11db7;
    // 256 values representing ASCII character codes.
	for($i=0;$i <= 0xFF;++$i) {
    	$GLOBALS['crc32Table'][$i]=(crc32Reflect($i,8) << 24);
        for($j=0;$j < 8;++$j) {
            $GLOBALS['crc32Table'][$i]=(($GLOBALS['crc32Table'][$i] << 1) ^ (($GLOBALS['crc32Table'][$i] & (1 << 31))?$polynomial:0));
        }
        $GLOBALS['crc32Table'][$i] = crc32Reflect($GLOBALS['crc32Table'][$i], 32);
    }
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function crc32Reflect($ref, $ch) {        // Reflects CRC bits in the lookup table
	$value=0;
	// Swap bit 0 for bit 7, bit 1 for bit 6, etc.
	for($i=1;$i<($ch+1);++$i) {
		if($ref & 1) $value |= (1 << ($ch-$i));
		$ref = (($ref >> 1) & 0x7fffffff);
	}
	return $value;
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function crc32String($text) {        // Creates a CRC from a text string
	// Once the lookup table has been filled in by the two functions above,
	// this function creates all CRCs using only the lookup table.

	// You need unsigned variables because negative values
	// introduce high bits where zero bits are required.
	// PHP doesn't have unsigned integers:
	// I've solved this problem by doing a '&' after a '>>'.

	// Start out with all bits set high.
	$crc=0xffffffff;
	$len=strlen($text);

	// Perform the algorithm on each character in the string,
	// using the lookup table values.
	for($i=0;$i < $len;++$i) {
		$crc=(($crc >> 8) & 0x00ffffff) ^ $GLOBALS['crc32Table'][($crc & 0xFF) ^ ord($text[$i])];
	}

	// Exclusive OR the result with the beginning value.
	return $crc ^ 0xffffffff;
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function crc32File($afile) {            // Creates a CRC from a file
	// Info: look at __crc32_string

	// Start out with all bits set high.
	$crc=0xffffffff;
	if(($fp=fopen($afile,'rb'))===false){ return false;}
	// Perform the algorithm on each character in file
	for(;;) {
		$i=@fread($fp,1);
		if(strlen($i)==0){break;}
		$crc=(($crc >> 8) & 0x00ffffff) ^ $GLOBALS['crc32Table'][($crc & 0xFF) ^ ord($i)];
	}
	@fclose($fp);
	// Exclusive OR the result with the beginning value.
	return $crc ^ 0xffffffff;
}


//---------- begin function postEditXmlFromJson---------------------------------------
/**
* @describe postEditXml is used to communicate with postedit.exe
* @param $json array
* @param $dbname string
*	database name - defaults to current database
* @return string xml
*	returns xml for postedit.exe to parse
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postEditXmlFromJson($json=array()){
	if(!is_array($json)){return null;}
	global $USER;
	//build XML
	$xml=xmlHeader(array('version'=>"1.0",'encoding'=>"utf-8"));
	$xml .= "<xmlroot>".PHP_EOL;
	$xml .= "	<wasql_host>{$_SERVER['HTTP_HOST']}</wasql_host>".PHP_EOL;
	$xml .= "	<wasql_dbname>{$_SERVER['WaSQL_DBNAME']}</wasql_dbname>".PHP_EOL;
	$xml .= "	<postedit_tables>".implode(',',array_keys($json))."</postedit_tables>".PHP_EOL;
    // JDESPAIN/IntegraCore expanded information for editing user and datetime stamps
    $finfo=getDBFieldInfo('_users');
    $fields=array();
    foreach($finfo as $field=>$info){
    	if(in_array($field,array('password','guid','_env','address','email','zip','zipcode','picture','image','bio','address2','utype','hint','note','_aip','_sid'))){continue;}
    	switch(strtolower($info['_dbtype'])){
    		case 'varchar':
    		case 'char':
    		case 'text':
    			$fields[]=$field;
    		break;
    	}
    }
    if(!in_array('_id',$fields)){$fields[]='_id';}
	$edit_users = getDBRecords(array(
		'-table'	=> '_users',
		'-index'	=> '_id',
		'-fields'	=> implode(',',$fields)
	));
	//json[table][id][field1], json[table][id][field2]...
	foreach($json as $table=>$tableids){
		//determine what fields I need for this table
		$finfo=getDBFieldInfo($table,1);
		//continue;
		//skip tables that do not have a name as a field
		$name='';
		if(isset($finfo['name'])){
			$name="name";
		}
		elseif(isset($finfo['item_name'])){
			$name="item_name";
			$finfo['name']=$finfo[$name];
		}
		elseif(isset($finfo['title'])){
			$name="title";
			$finfo['name']=$finfo[$name];
		}
		elseif(isset($finfo['sku'])){
			$name="sku";
			$finfo['name']=$finfo[$name];
		}
		elseif(isset($finfo['code']) && stringContains($finfo['code']['_dbtype'],'char')){
			$name="code";
			$finfo['name']=$finfo[$name];
		}
		if(!strlen($name)){
			continue;
		}
		$fields=array();
		foreach($finfo as $field=>$info){
			if($field==$name){continue;}
			if(isWasqlField($field)){continue;}
			if(preg_match('/^(template|css_min|js_min)$/i',$field)){continue;}
			if(
				(isset($info['inputtype']) && strlen($info['inputtype']) && $info['inputtype'] == 'textarea') 
				|| in_array($info['_dbtype'],array('blob','json','text'))
				|| preg_match('/^meta\_/i',$field)
			){
				$fields[]=$field;
			}
		}
		if(!count($fields)){
			continue;
		}
		$fieldstr=implode(',',$fields);
		$q="select _id,_cdate,_cuser,_edate,_euser,{$name} as name,{$fieldstr} from {$table}";
		if(strtolower($table)=='_prompts' || !isAdmin()){
			$q.=" where _cuser={$USER['_id']}";
		}
		
		$recs=getDBRecords($q);
		//echo $q;exit;
		if(!is_array($recs)){continue;}
		$recs_count=count($recs);
		//build the xml for these records
		//echo printValue($finfo);exit;
		$json_fields=array();
		foreach($recs as $rec){
			$id=$rec['_id'];
			$recxml='';
			foreach($rec as $field=>$val){
				$val=trim($val);
				if(isWasqlField($field) || $field=='name' || !strlen($val)){continue;}
				if($finfo[$field]['_dbtype']=='json'){
					$arr=json_decode($val,true);
					if(is_array($arr)){
						$val=json_encode($arr,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_PRETTY_PRINT);
						$json_fields[]=$field;
					}
				}
				$val=encodeBase64($val);
				$recxml .= "		<{$table}_{$field}>{$val}</{$table}_{$field}>".PHP_EOL;
			}
			//exit;
			//skip records that have not changed
			if(!strlen($recxml)){continue;}
			$atts=array(
				'table'	=> $table,
				'rec_count'=>$recs_count,
				'_id'	=> $rec['_id'],
				'name'	=> $rec['name'],
			);
			if(count($json_fields)){
				$atts['json_fields']=implode(',',$json_fields);
			}
			/* JDESPAIN/IntegraCore expanded information for editing user and datatime stamps */
            if(strlen($rec['_edate']) && isNum($rec['_euser']) && isset($edit_users[$rec['_euser']])){
            	foreach($edit_users[$rec['_euser']] as $k=>$v){
            		if(strlen($v) > 255){continue;}
            		$atts["user_{$k}"]=$v;	
				}
            }
            elseif(strlen($rec['_cdate']) && isNum($rec['_cuser']) && isset($edit_users[$rec['_cuser']])){
            	foreach($edit_users[$rec['_cuser']] as $k=>$v){
            		if(strlen($v) > 255){continue;}
            		$atts["user_{$k}"]=$v;	
				}
            }
            /* END JDESPAIN/IntegraCore expanded information for editing user and datatime stamps */
			$xml .= '	<WASQL_RECORD';
			foreach($atts as $key=>$val){
				if(strlen($val) > 255 || preg_match('/[\<\>\"]/ism',$val)){continue;}
				$val=str_replace('&','&amp;',$val);
				$val=str_replace('"','&quot;',$val);
				$xml .= " {$key}=\"{$val}\"";
			}
			$xml .= '>'.PHP_EOL;
			$xml .= $recxml;
	        $xml .= '	</WASQL_RECORD>'.PHP_EOL;
        }
    }
    $xml .= "</xmlroot>".PHP_EOL;
    return $xml;
}
/*
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function posteditSha1($str){
	if(is_file($str)){
		$str=file_get_contents($str);
	}
	$str=preg_replace('/[\r\n]+/','',$str);
	return md5($str);
}
//---------- begin function postEditXml---------------------------------------
/**
* @describe postEditXml is used to communicate with postedit.exe
* @param $tables array
*	list of tables
* @param $dbname string
*	database name - defaults to current database
* @return string xml
*	returns xml for postedit.exe to parse
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postEditXml($pextables=array(),$dbname='',$encoding=''){
	global $USER;
	//build xml
	if(strlen($dbname)){
        $x=$pextables;
		$pextables=array();
        foreach($x as $table){
			$pextables[]="{$dbname}.{$table}";
		}
		$_SERVER['WaSQL_DBNAME']=$dbname;
	}
	//add record to posteditlog
	if(!isDBTable('_posteditlog')){
		$progpath=dirname(__FILE__);
		include_once("{$progpath}/schema.php");
		$ok=createWasqlTable('_posteditlog');
	}
	$ok=addDBRecord(array(
		'-table'	=> '_posteditlog',
		'postedittables'	=> implode(', ',$pextables)
	));
	$ok=cleanupDBRecords('_posteditlog',30);
	//build XML
	$xml=xmlHeader(array('version'=>"1.0",'encoding'=>"utf-8"));
	$xml .= "<xmlroot>\n";
	$xml .= "	<wasql_host>{$_SERVER['WaSQL_HOST']}</wasql_host>\n";
	$xml .= "	<wasql_dbname>{$_SERVER['WaSQL_DBNAME']}</wasql_dbname>\n";
	$xml .= "	<postedit_tables>".implode(',',$pextables)."</postedit_tables>\n";
	//list directories
	if(isset($_SERVER['DOCUMENT_ROOT'])){
		$xml .= "	<docroot>{$_SERVER['DOCUMENT_ROOT']}</docroot>\n";
		$files=listFilesEx($_SERVER['DOCUMENT_ROOT'],array('-dateformat'=>'m/d/Y g:i a','type'=>'dir,link'));
		$listdirs=array();
		foreach($files as $file){
			//skip dirs that begin with a dot
			if(preg_match('/^\./i',$file['name'])){continue;}
			if(preg_match('/^(Maildir|Logs|wfiles|php|min|cgi\-bin)$/i',$file['name'])){continue;}
			//skip dirs that we do not have permissiong to write to
			if(!isset($file['perm_read']) || !$file['perm_read']){continue;}
			if(!isset($file['perm_write']) || !$file['perm_write']){continue;}
			$listdirs[]=$file['name'];
        }
        $xml .= '	<wasql_dirs>'.implode(',',$listdirs).'</wasql_dirs>'.PHP_EOL;
    }
    // JDESPAIN/IntegraCore expanded information for editing user and datetime stamps
	$edit_users = getDBRecords(array(
		'-table'	=> '_users',
		'-fields'	=> "_id,username,email",
		'-index'	=> '_id'
		));
	foreach($pextables as $table){
		if(!isDBTable($table)){
			$xml .= "	<{$table}_error>{$table} does not exist</{$table}_error>\n";
			continue;
        }
        $finfo=getDBFieldInfo($table,1);
        //$xml .= printValue($finfo);
        $recopts=array('-table'=>$table,'-order'=>"_id");
        if(isset($finfo['postedit'])){
        	$recopts['postedit']=1;
		}
		if(!isAdmin()){$recopts['_cuser']=$USER['_id'];}
		$recs=getDBRecords($recopts);
		if(!is_array($recs)){continue;}
		$xml .= "<!-- {$table} has ".count($recs)." records -->\n";
		foreach($recs as $rec){
			$recxml='';
			$fields=array();
			foreach($rec as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				if(preg_match('/^(template|name|css_min|js_min)$/i',$key)){continue;}
				//if(preg_match('/\_mdml$/i',$key)){continue;}
				if(!strlen($val)){continue;}
				if(
					(isset($finfo[$key]['inputtype']) && strlen($finfo[$key]['inputtype']) && $finfo[$key]['inputtype'] == 'textarea') 
					|| in_array($finfo[$key]['_dbtype'],array('blob','json','text'))
					|| preg_match('/^meta\_/i',$key)
				){
					//skip this one if there is a filter and it does not match
					if(isset($_REQUEST['filter']) && strlen(trim($_REQUEST['filter']))){
                		$filename="{$rec['name']}.{$table}.{$key}.{$rec['_id']}";
                		if(!stringContains($filename,$_REQUEST['filter'])){
                        	continue;
						}
					}
					switch(strtolower($encoding)){
                    	case 'base64':$val=encodeBase64($val);break;
                    	default:
                    		if(isXML($val)){$val="<![CDATA[\n" . $val . "\n]]>";}
						break;
					}
					$recxml .= "		<{$table}_{$key}>{$val}</{$table}_{$key}>\n";
					array_push($fields,$key);
					}
	        	}
	        if(count($fields) || strlen($recxml)){
				$atts=array(
					'table'	=> $table,
					'_id'	=> $rec['_id'],
					'name'	=> $rec['name'],
					'_xmlfields'=>implode(',',$fields)
				);

				/* JDESPAIN/IntegraCore expanded information for editing user and datatime stamps */
                if(isNum($rec['_edate_utime'])){
                    $atts['mtime']=$rec['_edate_utime'];
                    $atts['musername'] = $edit_users[$rec['_euser']]['username'];
                    $atts['museremail'] = $edit_users[$rec['_euser']]['email'];
                }
                if(isNum($rec['_adate_utime'])){$atts['atime']=$rec['_adate_utime'];}
                if(isNum($rec['_cdate_utime'])){
                    $atts['ctime']=$rec['_cdate_utime'];
                    if(isset($edit_users[$rec['_cuser']]['username'])){
						$atts['cusername'] = $edit_users[$rec['_cuser']]['username'];
					}
					if(isset($edit_users[$rec['_cuser']]['email'])){
						$atts['cuseremail'] = $edit_users[$rec['_cuser']]['email'];
					}
                }
                /* END JDESPAIN/IntegraCore expanded information for editing user and datatime stamps */
				$xml .= '	<WASQL_RECORD';
				foreach($atts as $key=>$val){
					if(strlen($val) > 255 || preg_match('/[\<\>\"]/ism',$val)){continue;}
					$val=str_replace('&','&amp;',$val);
					$val=str_replace('"','&quot;',$val);
					$xml .= " {$key}=\"{$val}\"";
				}
				$xml .= '>'.PHP_EOL;
				$xml .= $recxml;
		        $xml .= '	</WASQL_RECORD>'.PHP_EOL;
			}
        }
    }
    $xml .= "</xmlroot>\n";
    return $xml;
}
//---------- begin function postEditChanges
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postEditChanges($tables=array()){
	$xml=xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
	$xml .= "<xmlroot>\n";
	$xml .= "	<wasql_host>{$_SERVER['WaSQL_HOST']}</wasql_host>\n";
	$xml .= "	<wasql_dbname>{$_SERVER['WaSQL_DBNAME']}</wasql_dbname>\n";
	foreach($tables as $table){
		if(!isDBTable($table)){
			$xml .= "	<{$table}_error>{$table} does not exist</{$table}_error>\n";
			continue;
        	}
        #what changes for this table
        $ids=array();
        foreach($_REQUEST as $key=>$val){
			if(preg_match('/^\Q'.$table.'\E\_([0-9]+)$/is',$key,$km)){
				array_push($ids,$km[1]);
            	}
        	}
        if(!count($ids)){continue;}
        $finfo=getDBFieldInfo($table,1);
        $idstr=implode(',',$ids);
		$recs=getDBRecords(array('-table'=>$table,'-where'=>"_id in ({$idstr})",'-order'=>'_id'));
		foreach($recs as $rec){
			$timestamp=strlen($rec['_edate_utime'])?$rec['_edate_utime']:$rec['_cdate_utime'];
			$recxml='';
			$fields=array();
			foreach($rec as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				if(preg_match('/^(template|name)$/',$key)){continue;}
				if(!strlen($val)){continue;}
				if((strlen($finfo[$key]['inputtype']) && $finfo[$key]['inputtype'] == 'textarea') || $finfo[$key]['_dbtype']=='blob'){
					if(isXML($val)){$val="<![CDATA[\n" . $val . "\n]]>";}
					$recxml .= "		<{$table}_{$key}>{$val}</{$table}_{$key}>\n";
					array_push($fields,$key);
					}
	        	}
	        if(count($fields) || strlen($recxml)){
				$xml .= '	<WASQL_RECORD table="'.$table.'" _id="'.$rec['_id'].'" name="'.$rec['name'].'" timestamp="'.$timestamp.'" _xmlfields="'.implode(',',$fields).'">'.PHP_EOL;
				$xml .= $recxml;
		        $xml .= '	</WASQL_RECORD>'.PHP_EOL;
				}
        	}
    	}
    $xml .= "</xmlroot>\n";
    return $xml;
	}
//---------- begin function postEditCheck
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postEditCheck($tables=array()){
	$xml=xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
	$xml .= "<xmlroot>\n";
	$xml .= "	<wasql_host>{$_SERVER['WaSQL_HOST']}</wasql_host>\n";
	$xml .= "	<wasql_dbname>{$_SERVER['WaSQL_DBNAME']}</wasql_dbname>\n";
	foreach($tables as $table){
		if(!isDBTable($table)){
			$xml .= "	<{$table}_error>{$table} does not exist</{$table}_error>\n";
			continue;
        	}
        $finfo=getDBFieldInfo($table,1);
        //$xml .= printValue($finfo);
		$recs=getDBRecords(array('-table'=>$table,'-order'=>'_id'));
		//$xml .= "<!-- {$table} has ".count($recs)." records -->\n";
		foreach($recs as $rec){
			$timestamp=strlen($rec['_edate_utime'])?$rec['_edate_utime']:$rec['_cdate_utime'];
			$xml .= '	<WASQL_CHECK table="'.$table.'" _id="'.$rec['_id'].'" name="'.$rec['name'].'" timestamp="'.$timestamp.'" >'.PHP_EOL;
        	}
    	}
    $xml .= "</xmlroot>\n";
    return $xml;
	}
//---------- begin function postURL--------------------
/**
* @describe HTML URL POST using curl and return the result in an array
* @param url string - URL to post to
* @param params array
*	[-method] - POST,GET,DELETE, or PUT - defaults to POST
*	[-cookiefile] - cookiefile to use
*	[-fresh] - fresh connect
*	[-user_agent] - USER AGENT to pose as
*	[-headers] - headers to set
*	[-ssl] - if true set both SSL options to false (ignore)
*	[-authuser] - auth username
*	[-authpass] - auth password
*	[-keyfile] - path to ssl key file (pem)
*	[-keypass] - if your keyfile has a password...
*	[-follow] - follow location if redirected
*	[-timeout]  - The maximum number of seconds to allow cURL functions to execute. Defaults to 3600 (1 hour)
*	[-timeout_connect]  - The maximum number of seconds to allow cURL to connect. Defaults to 600 (5 minutes)
*	[-xml] - return xml_array as part of the resulting array
*	other params are passed through as key/value pairs to the URL specified
* @return array
* @usage $post=postURL($url,array('age'=>33,'name'=>'bob'));
*/
function postURL($url,$params=array()) {
	//reference - http://drewish.com/content/2007/07/using_php_and_curl_to_do_an_html_file_post
	$rtn=array('_params'=>$params);
	//check for auth params
	if(isset($params['username']) && isset($params['apikey'])){
		if(!isset($params['-headers']) || !is_array($params['-headers'])){$params['-headers']=array();}
		$params['-headers'][]="Wasql-Apikey: {$params['apikey']}";
		$params['-headers'][]="WaSQL-Username: {$params['username']}";
		$params['-headers'][]="WaSQL-Auth: 1";
		unset($params['username']);
		unset($params['apikey']);
	}
	elseif(isset($params['_auth'])){
		if(!is_array($params['-headers'])){$params['-headers']=array();}
		$params['-headers'][]="WaSQL-Auth: {$params['_auth']}";
		unset($params['_auth']);
	}
	if(isset($params['-noguid'])){
		if(!is_array($params['-headers'])){$params['-headers']=array();}
		$params['-headers'][]="WaSQL-NoGUID: 1";
		unset($params['-noguid']);
	}
	if(!isset($params['-timeout'])){$params['-timeout']=3600;}
	if(!isset($params['-timeout_connect'])){$params['-timeout_connect']=600;}
	//Build data stream from params
	$query=array();
	foreach($params as $key=>$val){
		if(preg_match('/^\-/',$key)){continue;}
		$query[$key]=$val;
    	}
    if(count($query)){
		$postfields=http_build_query($query);
		$rtn['_postfields']=$postfields;
		}
	if(isset($params['-method']) && preg_match('/^GET$/i',$params['-method'])){
		if(isset($postfields) && strlen($postfields)){$url .= '?'.$postfields;}
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_POST, 0);
    	}
	elseif(isset($params['-method']) && preg_match('/^DELETE$/i',$params['-method'])){
		//if($postfields){$url .= '?'.$postfields;}
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_POST, 0);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST,'DELETE');
    	}
	elseif(isset($params['-method']) && preg_match('/^PUT$/i',$params['-method'])){
		//if($postfields){$url .= '?'.$postfields;}
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_POST, 0);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST,'PUT');
    	}
	else{
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_POST, 1);
		if($postfields){
			curl_setopt($process, CURLOPT_POSTFIELDS, $postfields);
			}
		}
	//cookiefile?
	if(isset($params['-cookiefile'])){
		curl_setopt($process, CURLOPT_COOKIEFILE, $params['-cookiefile']);
		curl_setopt($process, CURLOPT_COOKIEJAR, $params['-cookiefile']);
	}
	elseif(isset($params['-cookie'])){
		curl_setopt($process, CURLOPT_COOKIE, $params['-cookie']);
	}
	if(isset($params['-fresh'])){
		curl_setopt($process, CURLOPT_FRESH_CONNECT, 1);
	}
	if(isset($params['-ipv4'])){
		curl_setopt( $process, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	}
	if(!isset($params['-user_agent'])){
		$params['-user_agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';
	}
	if(isset($params['-contenttype'])){
		if(!isset($params['-headers'][0])){$params['-headers']=array();}
		$params['-headers'][]=$params['-contenttype'];
	}
	if(isset($params['-headers']) && is_array($params['-headers'])){
		curl_setopt($process, CURLOPT_HTTPHEADER, $params['-headers']);
	}
	curl_setopt($process, CURLOPT_HEADER, 1);
	//alernate port?
	if(isset($params['-port']) && isNum($params['-port'])){
		curl_setopt($process, CURLOPT_PORT, $params['-port']);
	}
	if(isset($params['-user_agent'])){
		curl_setopt($process, CURLOPT_USERAGENT, $params['-user_agent']);
	}
	//-http_version
	if(isset($params['-http_version'])){
		curl_setopt($process, CURLOPT_HTTP_VERSION, $params['-http_version']);
	}
	//-keyfile
	if(isset($params['-keyfile'])){
		curl_setopt($process, CURLOPT_SSLKEY, $params['-keyfile']);
	}
	//-keypass
	if(isset($params['-keypass'])){
		curl_setopt($process, CURLOPT_SSLKEYPASSWD, $params['-keypass']);
	}
	//disable SSL verification?
	if(isset($params['-nossl']) && $params['-nossl'] != 0){
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
	}
	elseif(stringBeginsWith($url,'https') || (isset($params['-ssl']) && $params['-ssl'])){
		$cacert=dirname(__FILE__) . '/curl-ca-bundle.crt';
		curl_setopt($process, CURLOPT_CAINFO, $cacert);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 2);
	}
	if(isset($params['-ssl_version'])){
		curl_setopt($process, CURLOPT_SSLVERSION,$params['-ssl_version']);
	}
	//handle auth request - basic Authentication
	if(isset($params['-auth']) && strlen($params['-auth'])){
		//try all possible authentication methods
		//curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($process, CURLOPT_USERPWD, $params['-auth']);
		}
	elseif(isset($params['-authuser']) && strlen($params['-authuser']) && isset($params['-authpass']) && strlen($params['-authpass'])){
		//try all possible authentication methods
		//curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($process, CURLOPT_USERPWD, "{$params['-authuser']}:{$params['-authpass']}");
		}
	curl_setopt($process, CURLINFO_HEADER_OUT, true);
	//if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
	//if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
	curl_setopt($process, CURLOPT_CONNECTTIMEOUT, $params['-timeout_connect'] );
	curl_setopt($process, CURLOPT_TIMEOUT, $params['-timeout']);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
	if(isset($params['-follow'])){
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($process, CURLOPT_AUTOREFERER, true);
		curl_setopt($process, CURLOPT_MAXREDIRS, 20 );
		curl_setopt($process, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL );
	}
	//turn retrieving the header off
	//curl_setopt($process, CURLOPT_HEADER, 0);
	//convert Unix newlines to CRLF newlines
	if(isset($params['-crlf']) && $params['-crlf']==1){
		curl_setopt($process, CURLOPT_CRLF, 1);
	}
	else{
		curl_setopt($process, CURLOPT_CRLF, 0);
	}
	if(isset($params['-encoding'])){
		curl_setopt($process, CURLOPT_ENCODING , $params['-encoding']);
	}
	$return = curl_exec($process);
	$rtn['headers_out']=preg_split('/[\r\n]+/',curl_getinfo($process,CURLINFO_HEADER_OUT));
	$rtn['curl_info']=curl_getinfo($process);
	$rtn['headers']=array();
	//echo "<xmp>{$return}</xmp>".printValue($params).printValue($rtn);exit;
	//check for errors
	if ( curl_errno($process) ) {
		$rtn['error_number'] = curl_errno($process);
		$rtn['error'] = curl_error($process);
		}
	else{
		//break it up into header and body
		$parts=preg_split('/\r\n\r\n/',trim($return),2);
		$rtn['header']=trim($parts[0]);
		$rtn['body']=trim($parts[1]);
		//check for redirect cases with two headers
		if(preg_match('/^HTTP\//is',$rtn['body'])){
			$parts=preg_split('/\r\n\r\n/',trim($rtn['body']),2);
			$rtn['header']=trim($parts[0]);
			$rtn['body']=trim($parts[1]);
		}
		//parse the header into an array
		$parts=preg_split('/[\r\n]+/',trim($rtn['header']));
		$headers=array();
		foreach($parts as $part){
			if(!preg_match('/\:/',$part)){continue;}
			list($key,$val)=preg_split('/\:/',trim($part),2);
			$key=strtolower(trim($key));
			$headers[$key][]=trim($val);
        }
        foreach($headers as $k=>$v){
        	if(count($v)==1){$rtn['headers'][$k]=$v[0];}
        	else{$rtn['headers'][$k]=$v;}
		}
    }
    //close the handle
	curl_close($process);
	//echo printValue($rtn);exit;
    //decode it if gzipped
	if(isset($rtn['headers']['content-encoding']) && $rtn['headers']['content-encoding']=='gzip'){
		//try gzdecode first
		$body='';
		if(function_exists('gzdecode')){
			$body=gzdecode($rtn['body']);
		}
		if(strlen($body)){
			$rtn['headers']['content-length-decoded']=strlen($body);
			$rtn['headers']['content-encoding-decoded']='text/html';
			$rtn['body']=$body;
		}
		else{
			$body=file_get_contents('compress.zlib://data:who/cares;base64,'. base64_encode($rtn['body']));
			if(strlen($body)){
				$rtn['headers']['content-length-decoded2']=strlen($body);
				$rtn['headers']['content-encoding-decoded2']='text/html';
				$rtn['body']=$body;
			}
		}	
	}
	$rtn['url']=$url;
	if(isset($params['-xml']) && $params['-xml']==1 && isset($rtn['body']) && strlen($rtn['body'])){
		if(isset($params['-soap']) && $params['-soap']==1){
			$rtn['body'] = preg_replace('|<([/\w]+)(:)|m','<$1',$rtn['body']);
			$rtn['body'] = preg_replace('|(\w+)(:)(\w+=\")|m','$1$3',$rtn['body']);
		}
		$rtn['xml_array']=xml2Array($rtn['body']);
    	}
    elseif(isset($params['-json']) && $params['-json']==1 && isset($rtn['body']) && strlen($rtn['body'])){
		$rtn['json_array']=json_decode($rtn['body'],true);
    	}
    elseif(isset($params['-csv']) && $params['-csv']==1 && isset($rtn['body']) && strlen($rtn['body'])){
		$rtn['csv_array']=csv2Arrays($rtn['body']);
    	}
	if(isset($params['skip_error']) && !$params['skip_error'] && !isset($rtn['body']) && isset($rtn['error'])){
		echo "<h2>postURL Connection Error</h2><br>\n";
		echo "<b>Error #:</b> {$rtn['error_number']}<br>\n";
		echo "<b>Url:</b> {$rtn['url']}<br>\n";
		echo "<b>Error Message:</b> {$rtn['error']}<br>\n";
		exit;
    }
    
	return $rtn;
}
//---------- begin function postJSON--------------------
/**
* @describe post an JSON string to a URL and return the results.
* @param url string - URL to post to
* @param json string - JSON to post
* @param params array
*	[-user_agent] - USER AGENT to pose as
*	[-encoding] - charset to set encoding to
*	[-headers] - headers to set
*	[-crlf] - post using carriage returns for windows servers
*	[-ssl] - ignore SSL is set to true
*	[-timeout]  - The maximum number of seconds to allow cURL functions to execute.
*	[-timeout_connect]  - The maximum number of seconds to allow cURL to connect.
* @return array
* @usage $post=postJSON($url,$json);
*/
function postJSON($url='',$json='',$params=array()) {
	if(!isset($params['-encoding'])){$params['-encoding']='UTF-8';}
	if(!isset($params['-contenttype'])){$params['-contenttype']='application/json; charset=UTF-8';}
	if(!isset($params['-json'])){$params['-json']=1;}
	if(isset($params['username']) && isset($params['apikey'])){
		if(!is_array($params['-headers'])){$params['-headers']=array();}
		$params['-headers'][]="Wasql-Apikey: {$params['apikey']}";
		$params['-headers'][]="WaSQL-Username: {$params['username']}";
		$params['-headers'][]="WaSQL-Auth: 1";
		unset($params['username']);
		unset($params['apikey']);
	}
	elseif(isset($params['_auth'])){
		if(!isset($params['-headers'][0])){$params['-headers']=array();}
		$params['-headers'][]="WaSQL-Auth: {$params['_auth']}";
		unset($params['_auth']);
	}
	if(isset($params['-noguid'])){
		if(!is_array($params['-headers'])){$params['-headers']=array();}
		$params['-headers'][]="WaSQL-NoGUID: 1";
		unset($params['-noguid']);
	}
	//return printValue($params);
	return postBody($url,$json,$params);
}

//---------- begin function postXML--------------------
/**
* @describe post an XML string to a URL and return the results.
* @param url string - URL to post to
* @param xml string - XML to post
* @param params array
*	[-user_agent] - USER AGENT to pose as
*	[-encoding] - charset to set encoding to
*	[-headers] - headers to set
*	[-crlf] - post using carriage returns for windows servers
*	[-ssl] - ignore SSL is set to true
*	[-timeout]  - The maximum number of seconds to allow cURL functions to execute.
*	[-timeout_connect]  - The maximum number of seconds to allow cURL to connect.
* @return array
* @usage $post=postXML($url,$xml);
*/
function postXML($url='',$xml='',$params=array()) {
	if(!isset($params['-encoding'])){$params['-encoding']='UTF-8';}
	if(!isset($params['-contenttype'])){$params['-contenttype']='Content-type: text/xml; charset=UTF-8';}
	if(!isset($params['-xml'])){$params['-xml']=1;}
	if(isset($params['username']) && isset($params['apikey'])){
		if(!is_array($params['-headers'])){$params['-headers']=array();}
		$params['-headers'][]="Wasql-Apikey: {$params['apikey']}";
		$params['-headers'][]="WaSQL-Username: {$params['username']}";
		$params['-headers'][]="WaSQL-Auth: 1";
		unset($params['username']);
		unset($params['apikey']);
	}
	elseif(isset($params['_auth'])){
		if(!is_array($params['-headers'])){$params['-headers']=array();}
		$params['-headers'][]="WaSQL-Auth: {$params['_auth']}";
		unset($params['_auth']);
	}
	if(isset($params['-noguid'])){
		if(!is_array($params['-headers'])){$params['-headers']=array();}
		$params['-headers'][]="WaSQL-NoGUID: 1";
		unset($params['-noguid']);
	}
	return postBody($url,$xml,$params);
}
//---------- begin function postBody
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postBody($url='',$body='',$params=array()) {
	//defaults
	if(!isset($params['-encoding'])){$params['-encoding']='UTF-8';}
	if(!isset($params['-contenttype'])){$params['-contenttype']='Content-Type: text/xml; charset=UTF-8';}
	if(!isset($params['-user_agent'])){
		$params['-user_agent'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)';
	}
	//init
	$rtn=array('_debug'=>array(),'body'=>'','headers'=>array());
	$process = curl_init($url);
	//-http_version
	if(isset($params['-http_version'])){
		curl_setopt($process, CURLOPT_HTTP_VERSION, $params['-http_version']);
	}
	//user_agent?
	if(isset($params['-user_agent'])){
		//$rtn['_debug'][]='set user agent to' . $params['-user_agent'];
		curl_setopt($process, CURLOPT_USERAGENT, $params['-user_agent']);
	}
	//timeout_connect?
	if(isset($params['-timeout_connect'])){
		curl_setopt($process, CURLOPT_CONNECTTIMEOUT, $params['-timeout_connect'] );
	}
	//timeout?
	if(isset($params['-timeout'])){
		curl_setopt($process, CURLOPT_TIMEOUT, $params['-timeout']);
	}
	//-follow
	if(isset($params['-follow'])){
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($process, CURLOPT_AUTOREFERER, true);
		curl_setopt($process, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($process, CURLOPT_POSTREDIR, 3 );
	}
	//encoding
	curl_setopt($process, CURLOPT_ENCODING , $params['-encoding']);

	//headers
	if(isset($params['-headers']) && !is_array($params['-headers'])){
		$params['-headers']=array($params['-headers']);
	}
	if(isset($params['-contenttype'])){
		if(!isset($params['-headers'][0])){$params['-headers']=array();}
		$params['-headers'][]="Content-type: {$params['-contenttype']}";
	}
	if(isset($params['-headers']) && is_array($params['-headers'])){
		curl_setopt($process, CURLOPT_HTTPHEADER, $params['-headers']);
		$rtn['_debug'][]='set headers' . printValue($params['-headers']);
	}
	else{
		curl_setopt($process, CURLOPT_HTTPHEADER, array($params['-contenttype']));
	}
	//filename?
	if(isset($params['-filename'])){
		if(is_file($params['-filename'])){
			unlink($params['-filename']);
		}
		$fh = fopen($params['-filename'], "wb");
		if($fh){
			curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		    curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($process, CURLOPT_BINARYTRANSFER, 1);
		    curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		    curl_setopt($process, CURLOPT_BUFFERSIZE, 8096);
			curl_setopt($process, CURLOPT_FILE, $fh);
		}
	}
	//cookiefile?
	if(isset($params['-cookiefile'])){
		curl_setopt($process, CURLOPT_COOKIEFILE, $params['-cookiefile']);
		curl_setopt($process, CURLOPT_COOKIEJAR, $params['-cookiefile']);
	}
	elseif(isset($params['-cookie'])){
		if(is_array($params['-cookie'])){
			curl_setopt($process, CURLOPT_COOKIE, implode('; ',$params['-cookie']));
		}
		else{
			curl_setopt($process, CURLOPT_COOKIE, $params['-cookie']);
		}
	}
	if(isset($params['-authuser']) && strlen($params['-authuser']) && isset($params['-authpass']) && strlen($params['-authpass'])){
		//try all possible authentication methods
		//curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($process, CURLOPT_USERPWD, "{$params['-authuser']}:{$params['-authpass']}");
	}
	if(isset($params['-crlf']) && $params['-crlf']==1){
		curl_setopt($process, CURLOPT_CRLF, true);
		$rtn['_debug'][]='set crlf';
	}
	//alernate port?
	if(isset($params['-port']) && isNum($params['-port'])){
		curl_setopt($process, CURLOPT_PORT, $params['-port']);
	}
    curl_setopt($process, CURLOPT_HEADER, true);
    curl_setopt($process,CURLOPT_POST, true);
    curl_setopt($process,CURLOPT_TIMEOUT, 600);
    curl_setopt($process,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($process, CURLINFO_HEADER_OUT, true);
    curl_setopt($process, CURLOPT_AUTOREFERER, true);
	curl_setopt($process, CURLOPT_MAXREDIRS, 10 );
	curl_setopt($process, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL );
	if(isset($params['-nossl']) && $params['-nossl'] != 0){
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
	}
	elseif(stringBeginsWith($url,'https') || (isset($params['-ssl']) && $params['-ssl'])){
		if(isset($params['-ssl_cert'])){
			if(is_file($params['-ssl_cert'])){
				curl_setopt($process, CURLOPT_CAINFO, $params['-ssl_cert']);
				curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 2);
				$rtn['ssl_cert']=$params['-ssl_cert'];
			}
			else{
				$rtn['ssl_error']="missing cert: {$params['-ssl_cert']}";
				curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
			}
		}
		else{
			$rtn['ssl_cert']=dirname(__FILE__) . '/curl-ca-bundle.crt';
			if(is_file($rtn['ssl_cert'])){
				curl_setopt($process, CURLOPT_CAINFO, $rtn['ssl_cert']);
				curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 2);
			}
			else{
				$rtn['ssl_error']="missing cert: {$rtn['ssl_cert']}";
				curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
			}
		}

	}
	curl_setopt($process, CURLOPT_FRESH_CONNECT, 1);
	if(isset($params['-method'])){
		switch(strtolower($params['-method'])){
			case 'put':
				curl_setopt($process, CURLOPT_POST, 0);
				curl_setopt($process, CURLOPT_CUSTOMREQUEST,'PUT');
			break;
			case 'delete':
				curl_setopt($process, CURLOPT_POST, 0);
				curl_setopt($process, CURLOPT_CUSTOMREQUEST,'DELETE');
			break;
			default:
				curl_setopt($process, CURLOPT_POST, 1);
			break;
		}
    }
    curl_setopt($process,CURLOPT_POSTFIELDS,$body);
    $return=curl_exec($process);
    $rtn['headers_out']=preg_split('/[\r\n]+/',curl_getinfo($process,CURLINFO_HEADER_OUT));
    $rtn['curl_info']=curl_getinfo($process);
    $blank_count=0;
	//Process the result
	$ofx=0;
	//check for errors
	if ( curl_errno($process) ) {
		$rtn['err'] = curl_errno($process);
		$rtn['errno'] = curl_error($process);
	}
	else{
		//parse the result
		$lines=preg_split('/[\r\n]/',trim($return));
		$rtn['raw_lines']=$lines;
		foreach($lines as $line){
			$tline=trim($line);
			if(strlen($tline)==0){
				$blank_count+=1;
				continue;
            }
            //elseif(!strlen($rtn['body'])){$blank_count=0;}
            if(preg_match('/^HTTP\//i',$tline)){
				list($junk,$code,$ok)=preg_split('/[\s\t]+/',$tline);
				$rtn['code']=$code;
				$blank_count=0;
				continue;
			}
			if(preg_match('/^([a-z\-\_]+?)\ *?\:(.+)/i',$tline,$headerMatch)){
				//header line
				$key=strtolower(trim($headerMatch[1]));
				$rtn['headers'][$key]=trim($headerMatch[2]);
				$blank_count=0;
				continue;
			}
			if(!count($rtn['headers'])){
				$blank_count=0;
				continue;
			}
			unset($pm);
			if(preg_match('/\<\?xml version\=\"(.+?)\" encoding\=\"(.+?)\"\?\>(.*)/i',$tline,$pm)){
				$rtn['body'] .= xmlHeader(array('version'=>$pm[1],'encoding'=>$pm[2])) .PHP_EOL;
				$rtn['body'] .= "{$pm[3]}\n";
				$blank_count=2;
				continue;
			}
			if(preg_match('/^\<ofx\>\</i',$tline)){
				$rtn['body'] = '';
				$ofx=1;
				$blank_count=2;
			}
			if($blank_count > 1){
				$rtn['body'] .= "{$line}\n";
            }
        }
    }
    //close the handle
	curl_close($process);
	if(isset($params['-filename']) && $fh){fclose($fh);}
	$rtn['body']=trim($rtn['body']);
	if($ofx==1){
		//OFX is returned in SGML - fix it up to be valid XML
		$rtn['xml_format']='sgml';
		$rtn['body'] = sgml2XML($rtn['body']);
	}
	if(preg_match('/^\<\?xml /i',$rtn['body'])){
		if(preg_match('/\<soap:/i',$rtn['body'])){
			//returned as a SOAP request - fix it up so that SimpleXmlElement can parse it
			$rtn['xml_format']='soap';
			$rtn['body'] = preg_replace('|<([/\w]+)(:)|m','<$1',$rtn['body']);
			$rtn['body'] = preg_replace('|(\w+)(:)(\w+=\")|m','$1$3',$rtn['body']);
	    }
	    if(!isset($rtn['xml_format'])){$rtn['xml_format']='xml';}
		try {
			$rtn['xml_out']=new SimpleXmlElement($rtn['body']);
			$rtn['xml_array']=xml2Array($rtn['body']);
		}
		catch (Exception $e){
        	$rtn['error'] = "Invalid XML: " . printValue($e);
        }
	}
    if(isset($params['-json']) && $params['-json']==1){
		$rtn['json_in']=$body;
		if(strlen($rtn['body'])){
        	$rtn['json_array']=json_decode($rtn['body'],true);
		}
    }
    if(isset($params['-xml']) && $params['-xml']==1){
		$rtn['xml_in']=$body;
    }

	$rtn['params_in']=$params;
	$rtn['raw']=trim($return);
	ksort($rtn);
	return $rtn;
}
//---------- begin function showZipfileImage --------------------
/**
* @describe extracts an image out of a zipfile and pushes to browser
* @param zipfile_b64 string - base64 encoded absolute path to file
* @param img string - name of image in zipfile you want to push
* @return img binary
* @usage use page controller and passthrus to make a custom call like the following:
* @usage 	<img src="/t/1/{pagename}/showzip/thumbnail.png">
* @usage then in your controller do the following	
* @usage 	switch(strtolower($PASSTHRU[0])){
* @usage 		case 'showzip':
* @usage 			$img=$PASSTHRU[1];
* @usage 			$zipfile=$path_to_your_file;
* @usage 			$zipfile_b64=base64_encode($zipfile);
* @usage 			$ok=showZipfileImage($zipfile_b64,$img);
* @usage 			return;
* @usage 		break;
* @usage 		}
*/
function showZipfileImage($zipfile_b64,$img){
	loadExtras('zipfile');
	$zipfile=base64_decode($zipfile_b64);
	$zipfile=realpath($zipfile);
	if(!file_exists($zipfile)){
		debugValue(array('error'=>'no such file','function'=>'showZipfileImage','file'=>$zipfile));
		return '/wfiles/clear.gif" data-error="ERROR-no such zipfile';
	}
	$name=getFileName($zipfile,1);
	zipPushFile($zipfile,$img);
	exit;
}
//---------- begin function sgml2XML--------------------
/**
* @describe converts an SGML feed to XML for further processing as XML
* @param sgml string - SGML feed string
* @return string  - XML
* @usage $xml=sgml2XML($sgml);
*/
function sgml2XML($sgml){
	$xml=preg_replace('`=(([^" >])+)[ ]`','="$1" ',$sgml);
	$xml=preg_replace('`=(([^">])+)[>]`','="$1">' ,$xml);
	return $xml;
	}
//---------- begin function printValue
/**
* @describe returns an html block showing the contents of the object,array,or variable specified
* @param $v mixed The Variable to be examined.
* @param [$exit] boolean - if set to true, then it will echo the result and exit. defaults to false
* @return string
*	returns an html block showing the contents of the object,array,or variable specified.
* @usage
*	echo printValue($sampleArray);
 * printValue($str,1);
* @author slloyd
* @history bbarten 2014-01-07 added documentation
*/
function printValue($v='',$exit=0){
	$type=strtolower(gettype($v));
	$plaintypes=array('string','integer');
	if(in_array($type,$plaintypes)){return $v;}
	$type=ucfirst($type);
	$rtn='';
	if(!isCLI()){$rtn .= '<pre class="printvalue" type="'.$type.'">'.PHP_EOL;}
	//JSON_UNESCAPED_LINE_TERMINATORS was introduced in php 5.4. Value=256
	$j=json_encode($v,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | 256);
	if(is_string($j) && strlen($j)){
		$rtn .= "{$type} object:".PHP_EOL;
		//$j = preg_replace('/\\\//',"/",$j);
		$j=str_replace('\r\n',PHP_EOL,$j);
		$j=str_replace('\n',PHP_EOL,$j);
		$j=str_replace('\t',"\t",$j);
		$rtn .= stripslashes($j);
	}
	else{
		ob_start();
		print_r($v);
		$rtn .= ob_get_contents();
		ob_clean();
	}
	if(!isCLI()){$rtn .= "\n</pre>\n";}
    if($exit){echo $rtn;exit;}
	return $rtn;
	}
//---------- begin function printValueIf---------------------------------------
/**
* @describe printValueIf is a Conditional printValue
* @param condition mixed - condition can be a boolean or an array of boolean=>view sets
* @param $v mixed The Variable to be examined.
* @param [$exit] boolean - if set to true, then it will echo the result and exit. defaults to false
* @return string
*	returns an html block showing the contents of the object,array,or variable specified.
* @usage
*	echo printValueIf($USER['_id']==2,$sampleArray);
* @author slloyd
* @history bbarten 2014-01-07 added documentation
*/
function printValueIf($conditional,$v='',$exit=0){
	if(is_array($conditional) && count($conditional)){
		$opts=$params;
		$params=$view;
		foreach($conditional as $condition=>$view){
			if($condition){return printValue($v,$exit);}
		}
		return '';
	}
	if($conditional){return printValue($v,$exit);}
	return '';
}
//---------- begin function printValueIfUser---------------------------------------
/**
* @describe printValueIfUser is a Conditional printValue based on current user
* @param username string - username
* @param $v mixed The Variable to be examined.
* @param [$exit] boolean - if set to true, then it will echo the result and exit. defaults to false
* @return string
*	returns an html block showing the contents of the object,array,or variable specified.
* @usage
*	echo printValueIf('slloyd',$sampleArray);
*/
function printValueIfUser($username,$v='',$exit=0){
	if(!isUser()){return '';}
	global $USER;
	if($username != $USER['username']){return '';}
	return printValue($v,$exit);
	return '';
}
//---------- begin function printValueHidden
/**
* @describe returns a hidden html block showing the contents of the object,array,or variable specified
* @param $v mixed The Variable to be examined.
* @return string
*	returns a hidden html block showing the contents of the object,array,or variable specified.
* @usage
*	echo printValueHidden($sampleArray);
* @author slloyd
* @history bbarten 2014-01-07 added documentation
*/
function printValueHidden($v='',$title=''){
	$rtn = '<div style="display:none" id="printValue" title="'.$title.'">'.PHP_EOL;
	$rtn .= printValue($v);
	$rtn .= '</div>'.PHP_EOL;
	return $rtn;
	}
//---------- begin function processWysiwygPost---------------------------------------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function processWysiwygPost($table,$id,$fields=array()){
	return; //was not working
	//converts inline images into images on the server in a dir called /mce/{table}
	$rec=getDBRecord(array('-table'=>$table,'_id'=>$id,'-fields'=>implode(',',$fields)));
	if(!is_array($rec)){return 0;}
	//do not process if there is not document_root defined as a server global
	if(!isset($_SERVER['DOCUMENT_ROOT'])){
		debugValue("processWysiwygPost error: document_root is not defined.");
		return 0;
		}
	//make sure the path exists or create it.
	$path="{$_SERVER['DOCUMENT_ROOT']}/mce/{$table}";
	if(!is_dir($path)){buildDir($path);}
	if(!is_dir($path)){
		debugValue("processWysiwygPost error: unable to find or create mce path: {$path}");
		return 0;
		}
	$update=array();
	/* DEVELOPER NOTE:
		preg_match_all has a limit of 65535 so I cannot use it to look for img tags since the
		base64 code may be longer than that.  Instead we have to resort to splitting the code up outself.
	*/
	foreach($fields as $field){
		//break the field into lines since preg_match_all has a limit of
		$lines=preg_split('/[\r\n]+/',$rec[$field]);
		foreach($lines as $line){
			//$line=strip_tags($line,'<img>');
			if(!preg_match('/<img/i',$line)){continue;}
			//split up by <img
			$parts=preg_split('/(img src="|" alt=")/',$line);
			foreach($parts as $part){
            	if(!preg_match('/^data\:/i',$part)){continue;}
	            list($data,$type,$enc,$encodedString)=preg_split('/[\:;,]/',$part,4);
	            //make sure it is an extension we support
	            unset($ext);
            	switch(strtolower($type)){
					case 'image/jpeg':
					case 'image/jpg':
						$ext='jpg';
						break;
					case 'image/gif':
						$ext='gif';
						break;
					case 'image/png':
						$ext='png';
						break;
					case 'image/bmp':
						$ext='bmp';
						break;
				}
				if(!isset($ext)){continue;}
            	$file=$id.'_'.encodeCRC(sha1($encodedString)).".{$ext}";
				$decoded=base64_decode($encodedString);
				$afile="{$path}/{$file}";
				//remove the file if it exists already
				if(is_file($afile)){unlink($afile);}
				//save the file
				file_put_contents($afile,$decoded);
				if(is_file($afile)){
					//replace all instances of this image with the src path to the saved file
                    $src="/mce/{$table}/{$file}";
                    $rec[$field]=str_replace($part,$src,$rec[$field]);
                    $update[$field]+=1;
				}
			}
		}
		//fix the relative path problem
		$rcnt=0;
		$rec[$field]=str_replace('<img src="mce','<img src="/mce',$rec[$field],$rcnt);
		if($rcnt > 0){
        	$update[$field]+=1;
		}
		$rcnt=0;
		$rec[$field]=str_replace('<img src="../mce','<img src="/mce',$rec[$field],$rcnt);
		if($rcnt > 0){
        	$update[$field]+=1;
		}
		//if nothing was updated return without editing the record
		if(!count($update)){return 0;}
		//we found updates so save the edited fields with their new values that do not have embedded images
    	$editopts=array('-table'=>$table,'-where'=>"_id={$id}");
    	foreach($update as $key=>$cnt){
        	$editopts[$key]=$rec[$key];
		}
    	$ok=editDBRecord($editopts);
    	return $ok;
	}
}
//---------- begin function processInlineImage---------------------------------------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function processInlineImage($img,$fld='inline'){
	if(!strlen($img)){return;}
	list($data,$type,$enc,$encodedString)=preg_split('/[\:;,]/',$img,4);
    //make sure it is an extension we support
    $ext='';
    switch(strtolower($type)){
		case 'image/jpeg':
		case 'image/jpg':
			$ext='jpg';
			break;
		case 'image/gif':
			$ext='gif';
			break;
		case 'image/png':
			$ext='png';
			break;
		case 'image/bmp':
			$ext='bmp';
			break;
	}
	if(!strlen($ext)){return;}
	//make sure the path exists or create it.
	$path="{$_SERVER['DOCUMENT_ROOT']}/{$fld}_files";
	$path=str_replace('>','_',$path);
	$file=$fld.'_'.encodeCRC(sha1($encodedString)).".{$ext}";
	$file=str_replace('>','_',$file);
	$decoded=base64_decode($encodedString);
	$afile="{$path}/{$file}";
	if(!file_exists($path)){buildDir($path);}
	if(!file_exists($path)){
		debugValue("processInlineImage error: unable to find or create path: {$path}, Field:{$fld}, afile:{$afile}");
		return 0;
	}
	//remove the file if it exists already
	if(is_file($afile)){unlink($afile);}
	//save the file
	file_put_contents($afile,$decoded);
	if(is_file($afile)){
        $_REQUEST[$fld]="/{$fld}_files/{$file}";
        $_REQUEST[$fld]=str_replace('>','_',$_REQUEST[$fld]);
        return $_REQUEST[$fld];
	}
	debugValue("processInlineImage error: unable to save file: {$afile}");
	return 0;
}
//---------- begin function processInlineFiles---------------------------------------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function processInlineFiles(){
	foreach($_REQUEST as $key=>$val){
    	if(isset($_REQUEST["{$key}_base64"])){
    		//$_REQUEST["{$key}_base64_debug"]['processInlineFiles']=1;
        	$path=isset($_REQUEST["{$key}_path"])?$_REQUEST["{$key}_path"]:"/files/{$key}";
        	$apath="{$_SERVER['DOCUMENT_ROOT']}/{$path}";
			if(!is_dir($apath)){buildDir($apath);}
			if(!is_dir($apath)){
				$_REQUEST["{$key}_error"]="processInlineFiles error: unable to find or create path for {$key} files: {$apath}";
				debugValue($_REQUEST["{$key}_error"]);
				continue;
			}
			$base64_files=$_REQUEST["{$key}_base64"];
			if(!is_array($base64_files)){$base64_files=array($base64_files);}
			$efiles=array();
			//$_REQUEST["{$key}_base64_filecount"]=count($base64_files);
			foreach($base64_files as $b=>$base64_file){
				if(!strlen(trim($base64_file))){
					//$_REQUEST["{$key}_base64_error"]="{$b} - processInlineFiles error: empty value";
					continue;
				}
				/*
					Possible
					data:image/png;base64,iVB
					data:video/webm;codecs=vp9,opus;base64,GkX
				*/
				if(stringBeginsWith($base64_file,'data:image/png;base64,')){
					list($jnk,$encodedString)=preg_split('/base64\,/',$base64_file,2);
					$filename=$name=sha1($encodedString).'.png';
				}
				elseif(stringBeginsWith($base64_file,'data:image/jpg;base64,')){
					list($jnk,$encodedString)=preg_split('/base64\,/',$base64_file,2);
					$filename=$name=sha1($encodedString).'.jpg';
				}
				elseif(stringBeginsWith($base64_file,'data:image/jpeg;base64,')){
					list($jnk,$encodedString)=preg_split('/base64\,/',$base64_file,2);
					$filename=$name=sha1($encodedString).'.jpg';
				}
				elseif(stringBeginsWith($base64_file,'data:image/gif;base64,')){
					list($jnk,$encodedString)=preg_split('/base64\,/',$base64_file,2);
					$filename=$name=sha1($encodedString).'.gif';
				}
				elseif(stringBeginsWith($base64_file,'data:video/webm;') && stringContains($base64_file,';base64,')){
					list($jnk,$encodedString)=preg_split('/base64\,/',$base64_file,2);
					$filename=$name=sha1($encodedString).'.webm';
				}
				elseif(stringBeginsWith($base64_file,'data:video/x-matroska;') && stringContains($base64_file,';base64,')){
					list($jnk,$encodedString)=preg_split('/base64\,/',$base64_file,2);
					$filename=$name=sha1($encodedString).'.mp4';
				}
				elseif(stringBeginsWith($base64_file,'data:video/mp4;') && stringContains($base64_file,';base64,')){
					list($jnk,$encodedString)=preg_split('/base64\,/',$base64_file,2);
					$filename=$name=sha1($encodedString).'.mp4';
				}
				else{
					list($filename,$name,$data,$type,$enc,$encodedString)=preg_split('/[\:;,]/',$base64_file,6);
				}
				//$_REQUEST["{$key}_base64_filename"]=$filename;
				//$_REQUEST["{$key}_base64_name"]=$name;
				//$_REQUEST["{$key}_base64_type"]=$type;
				//$_REQUEST["{$key}_base64_debug"]['filename']=$filename;
				//$_REQUEST["{$key}_base64_debug"]['name']=$name;
				$decoded=base64_decode($encodedString);
				$ext=getFileExtension($name);
				if(isset($_REQUEST["{$key}_autonumber"])){
					$crc=encodeCRC(sha1($encodedString));
					$name=getFileName($name,1) . '_' . $crc . '.' . $ext;
				}
				//remove spaces from the name
				$name=str_replace(' ','_',$name);
				$apath=str_replace('//','/',$apath);
				$path=str_replace('//','/',$path);
				$afile="{$apath}/{$name}";
				$_REQUEST["{$key}_abspath"]=$afile;
				//$_REQUEST["{$key}_base64_debug"]['afile']=$afile;
				//remove the file if it exists already
				if(is_file($afile)){unlink($afile);}
				//save the file
				file_put_contents($afile,$decoded);
				if(is_file($afile) && filesize($afile) > 0){
			        $efiles[]="/{$path}/{$name}";
			        $ok=commonProcessFileActions($key,$afile);
				}
				else{
					$_REQUEST["{$key}_error"]="processInlineFiles error: unable to find or create file: {$afile}";
					debugValue($_REQUEST["{$key}_error"]);
				}
			}
			if(count($efiles)==1){$efiles=$efiles[0];}
			$_REQUEST[$key]=$efiles;
			unset($_REQUEST["{$key}_base64"]);
		}
	}
}
//---------- begin function processActions---------------------------------------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function processActions(){
	if(!strlen($_REQUEST['_action'])){return 0;}
	$action=strtoupper($_REQUEST['_action']);
	global $USER;
	global $CONFIG;
	switch($action){
		case 'MULTI_UPDATE':
			if(isset($_REQUEST['_auth_required']) && $_REQUEST['_auth_required']==1 && !isNum($USER['_id'])){
		    	//auth required
		    	return 0;
			}
			//used in listDBRecords to update multiple records at a time.
			$ids=array();
			$finfo=getDBFieldInfo($_REQUEST['_table'],1);
			foreach($_REQUEST as $key=>$val){
				unset($idmatch);
				if(preg_match('/^(.+)\_([0-9]+?)_prev$/s',$key,$idmatch)){
					$cfield=$idmatch[1];
					$cid=$idmatch[2];
					$nkey="{$cfield}_{$cid}";
					if(isset($_REQUEST[$nkey]) && is_array($_REQUEST[$nkey])){
                    	$_REQUEST[$nkey]=implode(':',$_REQUEST[$nkey]);
					}
					if(isset($finfo[$cfield]['inputtype']) && $finfo[$cfield]['inputtype']=='checkbox' && !isset($_REQUEST[$nkey]) && $finfo[$cfield]['_dbtype']=='tinyint'){
                		$_REQUEST[$nkey]=0;
					}
					$ids[$cid]['_id']=$idmatch[2];
					$ids[$cid][$cfield]=$_REQUEST[$nkey];
					//unset($_REQUEST[$key]);
					//unset($_REQUEST[$nkey]);
					//$_REQUEST['_formfields']=str_replace(','.$key,'',$_REQUEST['_formfields']);
					//$_REQUEST['_formfields']=str_replace(','.$nkey,'',$_REQUEST['_formfields']);
					//$_REQUEST['_formfields']=str_replace('[]','',$_REQUEST['_formfields']);
            	}
			}
			$xids=array_keys($ids);
			$idstr=implode(',',$xids);
			$recopts=array('-table'=>$_REQUEST['_table'],'-where'=>"_id in ({$idstr})",'-index'=>'_id');
			$recs=getDBRecords($recopts);
			foreach($ids as $rec){
				$change=array();
				foreach($rec as $key=>$val){
					if(preg_match('/^_id$/is',$key)){continue;}
					if($recs[$rec['_id']][$key]!=$val){
						$change[$key]=$val;
					}
            	}
            	if(count($change)){
					$changesets=$change;
					$change['-table']=$_REQUEST['_table'];
					$change['-where']="_id={$rec['_id']}";
					$ok=editDBRecord($change);
					//show what fields changed to what values
					if(!isNum($ok)){$_REQUEST["edit_{$rec['_id']}"]=printValue($ok) . printValue($change);}
					else{$_REQUEST["edit_{$rec['_id']}"]=$changesets;}
            	}
			}
			break;
		case 'CRONPANEL':
			echo showDBCronPanel(1);
			$sort=encodeURL($_REQUEST['_sort']);
			echo buildOnLoad("scheduleAjaxGet('cronlist','php/index.php','cronlist','_action=cronlist&_sort={$sort}',1,1);");
			exit;
		break;
		case 'CRONLIST':
			$recopts=array('-table'=>"_cron",'-action'=>'/php/admin.php','_id_href'=>"/php/admin.php?".$idurl);
			//format the run_date
			$recopts['run_date_dateage']=1;
			$recopts['run_date_displayname']="Last Run";
			//format the frequency
			$recopts['frequency_eval']="\$t=%frequency%*60;return 'Every ' . verboseTime(\$t);";
			//format active
			$recopts['active_checkmark']=1;
			$recopts['active_align']="center";
			echo listDBRecords($recopts);
			exit;
		break;
		case 'AUTH':
			header("Content-type: text/xml");
			echo xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
			echo "<main>\n";
			echo "	<authcode>{$_REQUEST['_authcode']}</authcode>\n";
			if(strlen($_REQUEST['_authcode']) && strlen($_REQUEST['_authkey'])){
				$authstring=decrypt($_REQUEST['_authcode'],$_REQUEST['_authkey']);
				list($user,$pass)=preg_split('/\:/',$authstring,2);
				$rec=getDBRecord(array('-table'=>'_users','username'=>$user,'-nocache'=>1));
				if(is_array($rec)){
					if(isNum($_REQUEST['_pwe']) && $_REQUEST['_pwe']==1 && userIsEncryptedPW($rec['password'])){
						$pass=userEncryptPW($pass);
					}
					if($rec['password']==$pass){
						$out=array();
						foreach($rec as $key=>$val){
							if(preg_match('/^\_/',$key)){continue;}
							$out[$key]=$val;
		                	}
		                $out['id']=$rec['_id'];
		                unset($out['guid']);
						$xml=arrays2XML(array($out));
						$code=encrypt($xml,$_REQUEST['_authkey']);
						echo "	<success>{$code}</success>\n";
						}
					else{
						echo "	<failed>User Authentication failed</failed>\n";
                    	}
					}
				else{
					echo "	<failed>No user</failed>\n";
	            	}
				}
			else{
				echo "	<failed>Invalid Auth Request</failed>\n";
            	}
	        echo "</main>\n";
			exit;
		break;
		case 'POSTEDIT':
		case 'EDIT':
			if(isset($_REQUEST['_auth_required']) && $_REQUEST['_auth_required']==1 && !isNum($USER['_id'])){
		    	//auth required
		    	return 0;
			}
			$ok=processInlineFiles();
			$ok=processFileUploads();
			//echo "EDIT" . printValue($_FILES).printValue($_REQUEST);exit;
			$_REQUEST['process_action']='edit';
			if(!isset($_REQUEST['_fields']) && isset($_REQUEST['_formfields'])){
            	$_REQUEST['_fields']=preg_replace('/\,+$/','',$_REQUEST['_formfields']);
			}
			if(strlen($_REQUEST['_table']) && (integer)$_REQUEST['_id'] > 0 && strlen($_REQUEST['_fields'])){
				$rec=getDBRecord(array('-table'=>$_REQUEST['_table'],'_id'=>$_REQUEST['_id'],'-nocache'=>1,'-relate'=>array('_euser'=>'_users')));
				$timestamp=time();
				$_REQUEST['edit_rec']=$rec;
				if(is_array($rec)){
					$tinymce=array();
					$ruser=(isNum($rec['_euser']) && $rec['_euser'] > 0)?$rec['_euser']:$rec['_cuser'];
                    $_REQUEST['_fields']=strtolower(str_replace(' ','',$_REQUEST['_fields']));
					$fields=preg_split('/\,+/',$_REQUEST['_fields']);
					$info=getDBFieldInfo($_REQUEST['_table'],1);
					$opts=array(
						'-table'=>$_REQUEST['_table'],
						'-where'=>'_id='.$_REQUEST['_id']
					);
					if($action=='POSTEDIT' && count($fields)==1){
						if(!isset($_REQUEST['_md5sha']) || !strlen($_REQUEST['_md5sha'])){
							echo "<timestamp>{$timestamp}</timestamp>";
							echo "<fatal_error>Fatal Error: your postedit is out of date. update your local git repo (git pull).</fatal_error>";
							echo "<wasql_dbname>{$_SERVER['WaSQL_DBNAME']}</wasql_dbname>";
							echo "<wasql_host>{$_SERVER['WaSQL_HOST']}</wasql_host>";
							exit;
						}
						$fld=$fields[0];
						$tpath=getWasqlTempPath();
						$t=time();
						$tfile="{$tpath}/postedit_{$fld}_{$t}.tmp";
						//remove any ending carriage returns so the hash will match
						$rec[$fld]=preg_replace('/[\r\n]+$/','',$rec[$fld]);
						setFileContents($tfile,$rec[$fld]);
						$md5sha=md5_file($tfile).sha1_file($tfile);
						unlink($tfile);
						if(strlen($rec['_euser_ex']['username']) && strtolower($_REQUEST['_table']) != '_prompts' && $md5sha != $_REQUEST['_md5sha']){
							$username=$rec['_euser_ex']['username'];
							if($USER['username'] != $username){
								echo "<timestamp>{$timestamp}</timestamp>";
								echo "<fatal_error>Fatal Error:Your are {$USER['username']}. The {$fld} field was changed by {$username} since you started ({$rec['_edate']})</fatal_error>";
								echo "<wasql_dbname>{$_SERVER['WaSQL_DBNAME']}</wasql_dbname>";
								echo "<wasql_host>{$_SERVER['WaSQL_HOST']}</wasql_host>";
								exit;
							}
						}
					}
					if(isset($_REQUEST['_collection_field'])){
						$cfield=strtolower($_REQUEST['_collection_field']);
						if(isset($info[$cfield])){
							$jreq=array();
							foreach($_REQUEST as $k=>$v){
								if(isWasqlField($k)){continue;}
								$jreq[$k]=$v;
							}
							$opts[$cfield]=json_encode($jreq,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
						}
					}
					//check for xmldata field unless noxmldata is passed also
					if(!isset($_REQUEST['noxmldata'])){
						if(isset($info['xmldata'])){
							if(strlen($rec['xmldata'])){
								$xmldata=xmldata2Array($rec['xmldata']);
								$opts['xmldata']= request2XML($_REQUEST,$xmldata['server']);
							}
							else{
								$opts['xmldata']= request2XML($_REQUEST);
	                        }
	                    }
	                    elseif(isset($info['_xmldata'])){
							if(strlen($rec['_xmldata'])){
								$xmldata=xmldata2Array($rec['_xmldata']);
								$opts['_xmldata']= request2XML($_REQUEST,$xmldata['server']);
							}
							else{
								$opts['_xmldata']= request2XML($_REQUEST);
	                        }
	                    }
					}
					//check for json fields
					//echo printValue($info).printValue($fields);
					$xfields=$fields;
					$jsonfieldmap=array();
					foreach($info as $ffield=>$finfo){
						if($finfo['_dbtype']=='json'){
							foreach($xfields as $field){
								if(stringBeginsWith($field,"{$ffield}>")){
									if(!in_array($ffield,$fields)){
										$fields[]=$ffield;
									}
									if($finfo['_dbtype']=='json'){
										$jsonfieldmap[$field]='json';
									}
								}
							}
						}
					}
					//if($_REQUEST['_table']=='_files'){echo printValue($info).printValue($_REQUEST);exit;}
					//echo "EDIT".printValue($jsonfieldmap).printValue($_FILES).printValue($_REQUEST['data>company>favicon']);exit;
					$jsonmaps=array();
					foreach($fields as $field){
						if(preg_match('/^\_(c|e)(user|date)$/i',$field)){continue;}
						//look for json fields  meta>file1  meta>1>file
						$inputtype='';
		    			$dbtype='';
		    			$jfield='';
		    			if(preg_match('/^([a-z0-9\_\-]+?)\>/i',$field,$jm)){
		    				$jfield=$jm[1];
		    			}
		    			if(isset($_REQUEST["{$field}_prev"]) || isset($_REQUEST["{$field}_remove"])){
		    				$inputtype='file';
		    				$dbtype='json';
		    			}
						elseif(isset($info[$field])){
							$inputtype=$info[$field]['inputtype'];
							$dbtype=isset($jsonfieldmap[$field])?$jsonfieldmap[$field]:$info[$field]['_dbtype'];
						}
						elseif(isset($info[$jfield])){
							$inputtype=$info[$jfield]['inputtype'];
							$dbtype=isset($jsonfieldmap[$field])?$jsonfieldmap[$field]:$info[$jfield]['_dbtype'];
						}
						if(!strlen($inputtype)){$inputtype='text';}
						//echo "Field:{$field}, inputtype:{$inputtype}<br>".PHP_EOL;
						//echo "Field:{$field}, jfield:{$jfield}, fieldval:{$_REQUEST[$field]}<br>".PHP_EOL;
						//json
						if($dbtype=='json'){
							//look for field:attr:attr2... and eval this $field['attr']['attr2']=$v
							global $_jsonval_;
							$jval=array();
							//echo printValue($_REQUEST['data>company>favicon']);
							foreach($_REQUEST as $k=>$v){
								//echo "-{$k}-<br>";
								if(strlen($jfield) && !stringBeginsWith($k,"{$jfield}>")){continue;}
								elseif(!strlen($jfield) && !stringBeginsWith($k,"{$field}>")){continue;}
								//echo "----{$k}-<br>";
								$keys=preg_split('/\>/',$k);
								array_shift($keys);
								$keystr=implode("']['",$keys);
								$_jsonval_=$v;
								$str="global \$_jsonval_;\$jval['{$keystr}']=\$_jsonval_;";
								//echo "{$str}<br>";
								eval($str);
							}
							unset($_jsonval_);
							
							if(is_array($jval) && count($jval)){
								if(strlen($jfield)){
									if(isset($_REQUEST[$jfield]) && strlen($_REQUEST[$jfield])){
										$_REQUEST[$jfield]=json_decode($_REQUEST[$jfield],true);
										foreach($jval as $jk=>$jv){
											if(is_array($_REQUEST[$jfield][$jk])){
												foreach($jv as $jvk=>$jvv){
													$_REQUEST[$jfield][$jk][$jvk]=$jvv;
												}
											}
											else{
												$_REQUEST[$jfield][$jk]=$jv;
											}
										}
										$_REQUEST[$jfield]=json_encode($_REQUEST[$jfield],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
									}
									else{
										$_REQUEST[$jfield]=json_encode($jval,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
									}
									//echo "{$field}::SET {$jfield} to {$_REQUEST[$jfield]}<br>".PHP_EOL;
								}
								else{
									$_REQUEST[$field]=json_encode($jval,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
								}
							}
							//echo "EDIT".printValue($jsonfieldmap).printValue($_FILES).printValue($_REQUEST['data>company>favicon']);exit;
						}
						//decode it if needs be
						if(isset($_REQUEST[$field])){
							if(isset($_REQUEST['_base64']) && $_REQUEST['_base64']){
								$_REQUEST[$field]=decodeBase64($_REQUEST[$field]);
							}
							elseif(isset($_REQUEST["{$field}_base64"]) && $_REQUEST["{$field}_base64"]==1){
								$_REQUEST[$field]=decodeBase64($_REQUEST[$field]);
							}
						}
						//css_min minifycode
						if($field=='css' && !isset($_REQUEST['css_min']) && in_array($_REQUEST['_table'],array('_pages','_templates')) && isset($info['css_min'])){
                        	//only call minifyCode if the css has changed
							if(sha1($rec['css']) != sha1($_REQUEST[$field]) || (!strlen($rec['css_min']) && strlen($_REQUEST[$field]))){
								$opts['css_min']=minifyCode($_REQUEST[$field],'css');
							}
						}
						elseif($field=='js' && !isset($_REQUEST['js_min']) && in_array($_REQUEST['_table'],array('_pages','_templates')) && isset($info['js_min'])){
                        	//only call minifyCode if the css has changed
							if(sha1($rec['js']) != sha1($_REQUEST[$field]) || (!strlen($rec['js_min']) && strlen($_REQUEST[$field]))){
								$opts['js_min']=minifyCode($_REQUEST[$field],'js');
							}
						}
						//markdown?
						if($inputtype=='textarea' && isset($CONFIG['markdown']) && isset($info["{$field}_mdml"]) && !isset($_REQUEST["{$field}_mdml"])){
							if(!strlen(trim($_REQUEST[$field]))){
                            	$opts["{$field}_mdml"]='NULL';
							}
							else{
								loadExtras('markdown');
								$mdmlopts=array();
								if($CONFIG['markdown']==1){$mdmlopts['-strip_tags']=true;}
								$opts["{$field}_mdml"]=markdownFromHtml($_REQUEST[$field],$mdmlopts);
							}
						}
						if(isset($info[$field]['behavior'])){
							if($info[$field]['behavior']=='tinymce'){$tinymce[]=$field;}
							elseif($info[$field]['behavior']=='nicedit'){$tinymce[]=$field;}
							elseif($info[$field]['behavior']=='wysiwyg'){$tinymce[]=$field;}
							elseif($info[$field]['behavior']=='richtext'){$tinymce[]=$field;}
							elseif($info[$field]['behavior']=='quill'){$tinymce[]=$field;}
						}
						//file type
						if($inputtype=='file'){
							//echo printValue($_REQUEST);exit;
							$suffixes=array('remove','autonumber','path','sha1','size','width','height','type','prev');
							if(isset($_REQUEST[$field.'_remove']) && $_REQUEST[$field.'_remove'] == 1){
								//echo $field.'_remove';exit;
								if(strlen($jfield) && isset($_REQUEST[$jfield]) && strlen($_REQUEST[$jfield])){
									$_REQUEST[$jfield]=json_decode($_REQUEST[$jfield],true);
									//echo printValue($_REQUEST[$jfield]);
									$jval=array();
									$keys=preg_split('/\>/',$field);
									array_shift($keys);
									$keystr=implode("']['",$keys);
									global $_jsonval_;
									$_jsonval_=$_REQUEST[$field.'_prev'];
									//echo "jsonval: {$_jsonval_}<br>";
									$str="global \$_jsonval_;\$jval['{$keystr}']=\$_jsonval_;";
									//echo "{$str}<br>".PHP_EOL;
									eval($str);
									if(count($jval)){
										//echo printValue($jval);
										foreach($jval as $jk=>$jv){
											unset($_REQUEST[$jfield][$jk]);
											foreach($suffixes as $suffix){
												unset($_REQUEST[$jfield]["{$jk}_{$suffix}"]);
											}
										}
									}
									//echo "HERE".printValue($_REQUEST[$jfield]);exit;
									$opts[$jfield]=$_REQUEST[$jfield]=json_encode($_REQUEST[$jfield],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
								}
								else{
									if(stringContains($info[$field]['flags'],'not_null')){
										$opts[$field]='';
									}
									else{$opts[$field]=null;}
								}
								
								//unset($_REQUEST[$field]);
								//echo printValue($info);exit;
								foreach($suffixes as $suffix){
									if(isset($info["{$field}_{$suffix}"])){
										if(stringContains($info["{$field}_{$suffix}"]['flags'],'not_null')){
											$opts["{$field}_{$suffix}"]='';	
										}
										else{
											$opts["{$field}_{$suffix}"]=null;
										}
										
									}
									unset($_REQUEST["{$field}_{$suffix}"]);
								}	
							}
							elseif(isset($_REQUEST[$field.'_prev']) && !isset($_REQUEST[$field.'_abspath'])){
								$_REQUEST[$field]=$_REQUEST[$field.'_prev'];
							}
							else{					
								if(isset($_REQUEST[$field])){
									$afile=$_SERVER['DOCUMENT_ROOT'].$_REQUEST[$field];
									//echo "afile:{$afile}<br>".PHP_EOL;
									if(is_dir($afile) || !file_exists($afile)){
										//echo "unsetting {$field} - afile does not exist::{$afile}".PHP_EOL;
										unset($_REQUEST[$field]);
									}
								}
								//echo "EDIT".printValue($opts).printValue($_REQUEST);exit;
								if((!isset($_REQUEST[$field]) || !strlen($_REQUEST[$field])) && isset($_REQUEST[$field.'_prev'])){
									if(strlen($jfield) && isset($_REQUEST[$jfield]) && strlen($_REQUEST[$jfield])){
										$_REQUEST[$jfield]=json_decode($_REQUEST[$jfield],true);
										//echo $field.printValue($_REQUEST[$jfield]);
										$jval=array();
										$keys=preg_split('/\>/',$field);
										array_shift($keys);
										$keystr=implode("']['",$keys);
										global $_jsonval_;
										$_jsonval_=$_REQUEST[$field.'_prev'];
										//echo "jsonval: {$_jsonval_}<br>";
										$str="global \$_jsonval_;\$jval['{$keystr}']=\$_jsonval_;";
										//echo "{$str}<br>".PHP_EOL;
										eval($str);
										if(count($jval)){
											//echo printValue($jval);
											foreach($jval as $jk=>$jv){
												$_REQUEST[$jfield][$jk]=$jv;
											}
										}
										//echo "{$field}={$_REQUEST[$field.'_prev']}".printValue($_REQUEST[$jfield]);exit;
										$opts[$jfield]=$_REQUEST[$jfield]=json_encode($_REQUEST[$jfield],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
									}
									else{
										$opts[$field]=$_REQUEST[$field.'_prev'];
									}
									//echo "A ".printValue($opts).printValue($_REQUEST);exit;
								}
								elseif(strlen($jfield)){
									if(!isset($opts[$jfield]) && isset($_REQUEST[$jfield])){
										$opts[$jfield]=$_REQUEST[$jfield];
									}
								}
								else{
									if(!isset($opts[$field]) && isset($_REQUEST[$field])){
										$opts[$field]=$_REQUEST[$field];
										//add sha1, width, height, and type if fields exist
										if(isset($info[$field.'_sha1']) && isset($_REQUEST[$field.'_sha1'])){
											$opts[$field.'_sha1']=$_REQUEST[$field.'_sha1'];
										}
										if(isset($info[$field.'_type']) && isset($_REQUEST[$field.'_type'])){
											$opts[$field.'_type']=$_REQUEST[$field.'_type'];
										}
										if(isset($info[$field.'_size']) && isset($_REQUEST[$field.'_size'])){
											$opts[$field.'_size']=$_REQUEST[$field.'_size'];
										}
										if(isset($info[$field.'_width']) && isset($_REQUEST[$field.'_width'])){
											$opts[$field.'_width']=$_REQUEST[$field.'_width'];
										}
										if(isset($info[$field.'_height']) && isset($_REQUEST[$field.'_height'])){$opts[$field.'_height']=$_REQUEST[$field.'_height'];
										}
										//echo "B ".printValue($opts).printValue($_REQUEST);exit;
									}

								}
							}
						}
						else{
							if(isset($info[$field.'_sha1'])){
								$opts[$field.'_sha1']=setValue(array($_REQUEST[$field.'_sha1'],sha1($_REQUEST[$field])));
							}
							if(isset($info[$field.'_size'])){
								$opts[$field.'_size']=setValue(array($_REQUEST[$field.'_size'],strlen($_REQUEST[$field])));
							}
						}
						if($opts['-table']=="_users" && $field=='password' && !userIsEncryptedPW($_REQUEST[$field])){
							$opts[$field]=userEncryptPW($_REQUEST[$field]);
						}
						//echo "Field:{$field}, opts:{$opts[$field]}, req: {$_REQUEST[$field]}, type:{$info[$field]['inputtype']}<br>";
						if(strlen($jfield) && isset($_REQUEST[$jfield])){
							$opts[$jfield]=$_REQUEST[$jfield];
						}
						if(!isset($opts[$field])){
							if(isset($_REQUEST[$field])){
								$opts[$field]=$_REQUEST[$field];
							}
							else{
								switch(strtolower($info[$field]['inputtype'])){
									case 'checkbox':
									case 'buttonselect_m':
										if($info[$field]['_dbtype']=='json'){
											$opts[$field]='[]';	
										}
										elseif(isset($info[$field]['_dbdef']) && strlen($info[$field]['_dbdef'])){
											$opts[$field]=$info[$field]['_dbdef'];
										}
										elseif(isset($info[$field]['defaultval']) && strlen($info[$field]['defaultval'])){
											$opts[$field]=$info[$field]['defaultval'];
										}
										else{
											$opts[$field]='NULL';
										}	
									break;
									default:
										if(isset($info[$field]['_dbdef'])){
											$opts[$field]=$info[$field]['_dbdef'];
										}
										elseif(isset($info[$field]['defaultval'])){
											$opts[$field]=$info[$field]['defaultval'];
										}
									break;
								}
							}
						}
						#unset($_REQUEST[$field]);
					}
					$_REQUEST['edit_opts']=$opts;
					unset($_REQUEST['_fields']);
					unset($_REQUEST['_action']);
					unset($_REQUEST['_id']);
					//update the _edate and _euser fields on edit
                    if(isUser()){$opts['_euser']=$USER['_id'];}
                    $opts['_edate']=date("Y-m-d H:i:s",$timestamp);
                    //$opts['data']=json_decode($opts['data'],true);
                    //ksort($opts['data']['website']);
                    
                    //edit the record
					$_REQUEST['edit_result']=editDBRecord($opts);
					//ksort($_REQUEST);echo printValue($opts).printValue($_FILES).printValue($_REQUEST);exit;
					global $databaseCache;
					$databaseCache=array();
					if(!isNum($_REQUEST['edit_result'])){
						setWasqlError(debug_backtrace(),$_REQUEST['edit_result']);
						}
					else{
						if($opts['-table']=='_users' && $opts['-where']=="_id={$USER['_id']}"){
							foreach($opts as $k=>$v){
								if(isWasqlField($k)){continue;}
								if(preg_match('/^\-/',$k)){continue;}
								if(isset($USER[$k]) && $USER[$k] != $v){
									$USER[$k]=$v;
								}
							}
						}
                    	if(count($tinymce)){processWysiwygPost($_REQUEST['_table'],$rec['_id'],$tinymce);}
                    	//remove affected css and js file if table is _pages, or _templates
                    	if(preg_match('/^\_(pages|templates)$/i',$opts['-table'])){
							switch(strtolower($opts['-table'])){
                            	case '_pages':$fstr='P'.$rec['_id'];break;
                            	case '_templates':$fstr='T'.$rec['_id'];break;
							}
                        	$minifydir=dirname(__FILE__)."/minify";
                        	$mfiles=listFilesEx($minifydir,array('name'=>$fstr));
                        	if(is_array($mfiles)){
                            	foreach($mfiles as $mfile){
									minifySetVersion($mfile['name']);
									unlink($mfile['afile']);
									}
							}
						}
						//remove affected static files if table is _pages
                    	if($opts['-table']=='_pages'){
							$staticfiles=array();
							if(preg_match('/\.(html|htm)$/i',$rec['name'],$pm)){
								if(!in_array($rec['name'],$staticfiles)){$staticfiles[]=$rec['name'];}
							}
							if(preg_match('/\.(html|htm)$/i',$rec['permalink'],$pm)){
								if(!in_array($rec['permalink'],$staticfiles)){$staticfiles[]=$rec['permalink'];}
							}
							if(preg_match('/\.(html|htm)$/i',$opts['name'],$pm)){
								if(!in_array($opts['name'],$staticfiles)){$staticfiles[]=$opts['name'];}
							}
							if(preg_match('/\.(html|htm)$/i',$opts['permalink'],$pm)){
								if(!in_array($opts['permalink'],$staticfiles)){$staticfiles[]=$opts['permalink'];}
							}
							foreach($staticfiles as $staticfile){
                            	$afile="{$_SERVER['DOCUMENT_ROOT']}/{$staticfile}";
                            	unlink($afile);
							}
							//remove cached page if necessary
							if($rec['_cache']==1){
								$progpath=dirname(__FILE__);
								$cachefile="{$progpath}/temp/cachedpage_{$CONFIG['dbname']}_{$PAGE['_id']}_{$TEMPLATE['_id']}.htm";
								if(is_file($cachefile)){unlink($cachefile);}
							}
						}
						//remove affected static files if table is _templates
                    	if($opts['-table']=='_templates'){
							//get all pages
							$pages=getDBRecords(array('-table'=>'_pages','-fields'=>'name,permalink','_template'=>$rec['_id']));
							$staticfiles=array();
							if(is_array($pages)){
								foreach($pages as $prec){
									if(preg_match('/\.(html|htm)$/i',$prec['name'],$pm)){
										if(!in_array($prec['name'],$staticfiles)){$staticfiles[]=$prec['name'];}
									}
									if(preg_match('/\.(html|htm)$/i',$prec['permalink'],$pm)){
										if(!in_array($prec['permalink'],$staticfiles)){$staticfiles[]=$prec['permalink'];}
									}
								}
							}
							foreach($staticfiles as $staticfile){
                            	$afile="{$_SERVER['DOCUMENT_ROOT']}/{$staticfile}";
                            	unlink($afile);
							}
							$pages=getDBRecords(array('-table'=>'_pages','-fields'=>'_id','_cache'=>1,'_template'=>$rec['_id']));
							$staticfiles=array();
							if(is_array($pages)){
								$progpath=dirname(__FILE__);
                            	foreach($pages as $p){
									$cachefile="{$progpath}/temp/cachedpage_{$CONFIG['dbname']}_{$p['_id']}_{$rec['_id']}.htm";
									if(is_file($cachefile)){unlink($cachefile);}
								}
							}
						}
					}
					if(isset($_REQUEST['_editfield'])){
						$fld=$_REQUEST['_editfield'];
						if(is_array($_REQUEST['edit_opts'][$fld])){
							$_REQUEST['edit_opts'][$fld]=encodeJSON($_REQUEST['edit_opts'][$fld]);
						}
						echo $_REQUEST['edit_opts'][$fld];
						$divid="editfield_{$fld}_{$_REQUEST['edit_rec']['_id']}";
						if(!isset($_REQUEST['_noicon'])){
							echo $rtn .= ' <sup class="icon-edit w_smallest w_gray w_pointer" onclick="ajaxEditField(\''.$_REQUEST['_table'].'\',\''.$_REQUEST['edit_rec']['_id'].'\',\''.$fld.'\',{div:\''.$divid.'\'});"></sup>';
						}
						exit;
					}
					$_REQUEST['edit_id']=$rec['_id'];
					$_REQUEST['edit_table']=$_REQUEST['_table'];
					//reload the User array if they edited their profile
					if($_REQUEST['_table']=='_users' && $USER['_id']==$rec['_id'] && isNum($_REQUEST['edit_result'])){
						$USER=getDBRecord(array('-table'=>'_users','_id'=>$rec['_id'],'-nocache'=>1));
                    	}
					}
				else{
					setWasqlError(debug_backtrace(),"No record found");
					}
				if(isset($_REQUEST['_return']) && $_REQUEST['_return']=='XML'){
					$_REQUEST['edit_result']=json_encode($_REQUEST['edit_result'],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
					echo "<timestamp>{$timestamp}</timestamp>";
					echo "<edit_result>{$_REQUEST['edit_result']}</edit_result>";
					echo "<wasql_dbname>{$_SERVER['WaSQL_DBNAME']}</wasql_dbname>";
					echo "<wasql_host>{$_SERVER['WaSQL_HOST']}</wasql_host>";
					exit;
	            	}
		    	}
			break;
		case 'DELETE':
			if(isset($_REQUEST['_auth_required']) && $_REQUEST['_auth_required']==1 && !isNum($USER['_id'])){
		    	//auth required
		    	return 0;
			}
			//Delete a database record
		    if(strlen($_REQUEST['_table']) && isNum($_REQUEST['_id']) && $_REQUEST['_id'] > 0){
				$rec=getDBRecord(array('-table'=>$_REQUEST['_table'],'_id'=>$_REQUEST['_id'],'-nocache'=>1));
				if(is_array($rec)){
					$tinymce=array();
					$info=getDBFieldInfo($_REQUEST['_table'],1);
					foreach($info as $field=>$finfo){
						if($finfo['behavior']=='tinymce'){$tinymce[]=$field;}
					}
					if(count($tinymce)){
                    	//remove images associated with this record
                    	$dpath="{$_SERVER['DOCUMENT_ROOT']}/mce/{$_REQUEST['_table']}";
                    	$dfiles=listFilesEx($dpath,array('name'=>"{$_REQUEST['_id']}_"));
                    	if(is_array($dfiles)){
                        	foreach($dfiles as $dfile){
								if(!stringBeginsWith($dfile['name'],"{$_REQUEST['_id']}_")){continue;}
                            	unlink($dfile['afile']);
							}
						}
					}
					$opts=array('-table'=>$_REQUEST['_table'],'-where'=>"_id={$_REQUEST['_id']}");
					$_REQUEST['delete_result']=delDBRecord($opts);
					$_REQUEST['delete_id']=$_REQUEST['_id'];
					//remove affected css and js file if table is _users, _pages, or _templates
                    if(preg_match('/^\_(pages|templates)$/i',$_REQUEST['_table'])){
						switch(strtolower($_REQUEST['_table'])){
                            case '_pages':$fstr='P'.$_REQUEST['_id'];break;
                            case '_templates':$fstr='T'.$_REQUEST['_id'];break;
						}
                        $minifydir=dirname(__FILE__)."/minify";
                        $mfiles=listFilesEx($minifydir,array('name'=>$fstr));
                        if(is_array($mfiles)){
                            foreach($mfiles as $mfile){
								minifySetVersion($mfile['name']);
								unlink($mfile['afile']);
								}
						}
					}
					$_REQUEST['delete_table']=$_REQUEST['_table'];
					}
				else{
					setWasqlError(debug_backtrace(),"No record found");
					}
		    	}
		    break;
		case 'ADD':
			if(isset($_REQUEST['_auth_required']) && $_REQUEST['_auth_required']==1 && !isNum($USER['_id'])){
		    	//auth required
		    	return 0;
			}
		  	$_REQUEST['process_action']='add';
		  	$ok=processInlineFiles();
			//check for a hidden css spam _honeypot field - its value should be blank
			$spam=0;
			if(isset($_REQUEST['_honeypot']) && strlen($_REQUEST[$_REQUEST['_honeypot']])){$spam=1;}
			//add a database record
		    if(!$spam && strlen($_REQUEST['_table'])){
				//$fields=getDBFields($_REQUEST['_table']);
				$opts=array('-table'=>$_REQUEST['_table']);
				$info=getDBFieldInfo($_REQUEST['_table'],1);
				if(isset($_REQUEST['_collection_field'])){
					$cfield=strtolower($_REQUEST['_collection_field']);
					if(isset($info[$cfield])){
						$jreq=array();
						foreach($_REQUEST as $k=>$v){
							if(isWasqlField($k)){continue;}
							$jreq[$k]=$v;
						}
						$opts[$cfield]=json_encode($jreq,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
					}
				}
				else{
					$fields=array_keys($info);
					$tinymce=array();
					foreach($fields as $field){
						if(preg_match('/^\_(c|e)(user|date)$/i',$field)){continue;}
						if($info[$field]['behavior']=='tinymce'){$tinymce[]=$field;}
						elseif($info[$field]['behavior']=='nicedit'){$tinymce[]=$field;}
						elseif($info[$field]['behavior']=='wysiwyg'){$tinymce[]=$field;}
						elseif($info[$field]['behavior']=='richtext'){$tinymce[]=$field;}
						elseif($info[$field]['behavior']=='quill'){$tinymce[]=$field;}
						//decode it if needs be
						if(isset($_REQUEST['_base64']) && $_REQUEST['_base64']){$_REQUEST[$field]=decodeBase64($_REQUEST[$field]);}
						elseif(isset($_REQUEST["{$field}_base64"]) && $_REQUEST["{$field}_base64"]==1){$_REQUEST[$field]=decodeBase64($_REQUEST[$field]);}
						//css_min minifycode
						if($field=='css' && !isset($_REQUEST['css_min']) && in_array($_REQUEST['_table'],array('_pages','_templates')) && isset($info['css_min'])){
							$opts['css_min']=minifyCode($_REQUEST[$field],'css');
						}
						elseif($field=='js' && !isset($_REQUEST['js_min']) && in_array($_REQUEST['_table'],array('_pages','_templates')) && isset($info['js_min'])){
	                        $opts['js_min']=minifyCode($_REQUEST[$field],'js');
						}
						//json
						if($info[$field]['_dbtype']=='json'){
							//look for field:attr:attr2... and eval this $field['attr']['attr2']=$v
							global $_jsonval_;
							$jval=array();
							foreach($_REQUEST as $k=>$v){
								if(!stringBeginsWith($k,"{$field}>")){continue;}
								$keys=preg_split('/\>/',$k);
								array_shift($keys);
								$keystr=implode("']['",$keys);
								$_jsonval_=$v;
								$str="global \$_jsonval_;\$jval['{$keystr}']=\$_jsonval_;";
								eval($str);
							}
							unset($_jsonval_);
							if(is_array($jval) && count($jval)){
								$_REQUEST[$field]=json_encode($jval,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
							}
						}
						//markdown?
						if($info[$field]['inputtype']=='textarea' && isset($CONFIG['markdown']) && isset($info["{$field}_mdml"]) && !isset($_REQUEST["{$field}_mdml"])){
							if(!strlen(trim($_REQUEST[$field]))){
	                            	$opts["{$field}_mdml"]='NULL';
							}
							else{
								loadExtras('markdown');
								$mdmlopts=array();
								if($CONFIG['markdown']==1){$mdmlopts['-strip_tags']=true;}
								$opts["{$field}_mdml"]=markdownFromHtml($_REQUEST[$field],$mdmlopts);
							}
						}
						if($info[$field]['inputtype']=='file'){
							//add width, height, size, and type if fields exist
							if(isset($info[$field.'_sha1']) && isset($_REQUEST[$field.'_sha1'])){$opts[$field.'_sha1']=$_REQUEST[$field.'_sha1'];}
							if(isset($info[$field.'_type']) && isset($_REQUEST[$field.'_type'])){$opts[$field.'_type']=$_REQUEST[$field.'_type'];}
							if(isset($info[$field.'_size']) && isset($_REQUEST[$field.'_size'])){$opts[$field.'_size']=$_REQUEST[$field.'_size'];}
							if(isset($info[$field.'_width']) && isset($_REQUEST[$field.'_width'])){$opts[$field.'_width']=$_REQUEST[$field.'_width'];}
							if(isset($info[$field.'_height']) && isset($_REQUEST[$field.'_height'])){$opts[$field.'_height']=$_REQUEST[$field.'_height'];}
							}
						else{
							if(isset($info[$field.'_sha1'])){$opts[$field.'_sha1']=setValue(array($_REQUEST[$field.'_sha1'],sha1($_REQUEST[$field])));}
							if(isset($info[$field.'_size'])){$opts[$field.'_size']=setValue(array($_REQUEST[$field.'_size'],strlen($_REQUEST[$field])));}
							}
						$ucfield=strtoupper($field);
						if(isset($_REQUEST[$field])){
							if($opts['-table']=="_users" && $field=='password'){$opts[$field]=userEncryptPW($_REQUEST[$field]);}
							elseif(is_array($_REQUEST[$field])){$opts[$field]=implode(':',$_REQUEST[$field]);}
							elseif(strlen($_REQUEST[$field])){$opts[$field]=$_REQUEST[$field];}
						}
						elseif($field=='xmldata'){$opts['xmldata']= request2XML($_REQUEST);}
						elseif($field=='_xmldata'){$opts['_xmldata']= request2XML($_REQUEST);}
						elseif($field=='jsondata'){$opts['jsondata']= json_encode($_REQUEST,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}
						elseif($field=='_jsondata'){$opts['_jsondata']= json_encode($_REQUEST,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}
						elseif(isset($_SERVER[$ucfield])){$opts[$field]=$_SERVER[$ucfield];}
					}
				}
				//upsert?
				if(isset($_REQUEST['_upsert']) && strlen($_REQUEST['_upsert'])){
					$opts['-upsert']=$_REQUEST['_upsert'];
				}
				$id=addDBRecord($opts);
				$_REQUEST['add_result']=$id;
				if(isNum($id)){
					$_REQUEST['add_id']=$id;
					$_REQUEST['add_table']=$_REQUEST['_table'];
					if(count($tinymce)){processWysiwygPost($_REQUEST['_table'],$id,$tinymce);}
					//remove affected css and js file if table is _users, _pages, or _templates
                    if(preg_match('/^\_(pages|templates)$/i',$_REQUEST['_table'])){
						switch(strtolower($_REQUEST['_table'])){
                            case '_pages':$fstr='P'.$_REQUEST['add_id'];break;
                            case '_templates':$fstr='T'.$_REQUEST['add_id'];break;
						}
                        $minifydir=dirname(__FILE__)."/minify";
                        $mfiles=listFilesEx($minifydir,array('name'=>$fstr));
                        if(is_array($mfiles)){
                            foreach($mfiles as $mfile){
								minifySetVersion($mfile['name']);
								unlink($mfile['afile']);
								}
						}
					}
	            }
	            else{
					setWasqlError(debug_backtrace(),$id);
                }
		    }
		    break;
		case 'SESSION':
			foreach($_REQUEST as $key=>$val){
				if($key == '_action'){continue;}
				$_SESSION[$key]=$val;
	        	}
	        break;
	    case 'SETTINGS':
	    	//sets
	    	if(isNum($USER['_id'])|| (isset($_REQUEST['_setfield']) && isNum($_REQUEST['_userid']))){
				global $SETTINGS;
				$settings=settingsValues($USER['_id']);
				//check to see if we should store the whole request form as xml
				if(isset($_REQUEST['_setfield']) && strlen($_REQUEST['_setfield'])){
					$setkey=$_REQUEST['_setfield'];
					$userid=$USER['_id'];
					if(isNum($_REQUEST['_userid'])){$userid=$_REQUEST['_userid'];}
					$settings=settingsValues($userid,array($setkey));
					$rec=getDBRecord(array('-table'=>'_settings','-where'=>"key_name='{$setkey}' and user_id={$userid}"));
					ksort($_REQUEST);
					//exit;
					$val=request2XMLSimple($_REQUEST);
					$val_array=xml2Array($val);
					if(is_array($rec)){
						if(sha1(json_encode($val_array,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)) != sha1(json_encode($settings[$setkey],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE))){
							$editopts=array('-table'=>'_settings','-where'=>"_id={$rec['_id']}",'key_value'=>$val);
							$ok=editDBRecord($editopts);
							$_REQUEST['edit_result']=$ok;
							$_REQUEST['edit_id']=$rec['_id'];
							$_REQUEST['edit_table']='_settings';
							$SETTINGS[$setkey]=$val;
						}
					}
					else{
						$addopts=array('-table'=>'_settings','key_name'=>$setkey,'key_value'=>$val,'user_id'=>$userid);
						$id=addDBRecord($addopts);
						$_REQUEST['add_id']=$id;
						$_REQUEST['add_table']='_settings';
						$SETTINGS[$setkey]=$val;
			    	}
			    break;
				}
				foreach($_REQUEST as $key=>$val){
					if(preg_match('/^set\_(.+)$/',$key,$m)){
						$userid=$USER['_id'];
						$setkey=$m[1];
						if(preg_match('/^global\_/',$setkey)){
							$userid=0;
							$setkey=str_replace('global_','',$setkey);
							}
						if(is_array($val)){$val=implode(':',$val);}
						if(isset($settings[$setkey])){
							if(sha1($val) != sha1($settings[$setkey])){
								$editopts=array('-table'=>'_settings','-where'=>"key_name='{$setkey}' and user_id={$userid}",'key_value'=>$val);
								$ok=editDBRecord($editopts);
								$SETTINGS[$setkey]=$val;
								}
							}
						else{
							$addopts=array('-table'=>'_settings','key_name'=>$setkey,'key_value'=>$val,'user_id'=>$userid);
							$ok=addDBRecord($addopts);
							$SETTINGS[$setkey]=$val;
				            }
				        }
				    }
				}
	    	break;
	    case 'FORMS':
        	//Process _forms
	        if(isset($_REQUEST['_formname'])){
				global $CONFIG;
				//check for bots and spammers
				$spam=0;
				//Check user agent to see if it is a spambot
				$botcheck=0;
				if(isset($CONFIG['_botcheck'])){$botcheck=$CONFIG['_botcheck'];}
				elseif(isset($_REQUEST['_botcheck'])){$botcheck=$_REQUEST['_botcheck'];}
				if($botcheck==1 && isSpamBot()){
					$spam++;
					$_REQUEST['_forms_result']='Botcheck failed: User Agent "'.$_SERVER['HTTP_USER_AGENT'].'" was detected as a bot';
					unset($_REQUEST['_botcheck']);
					}
				//check for a hidden css spam _honeypot field - its value should be blank
				$honeypot='';
				if(isset($CONFIG['_honeypot'])){$honeypot=$CONFIG['_honeypot'];}
				elseif(isset($_REQUEST['_honeypot'])){$honeypot=$_REQUEST['_honeypot'];}
				if(strlen($honeypot)){
					$honeyfield=$honeypot;
					if(strlen($_REQUEST[$honeyfield])){
						$spam++;
						$_REQUEST['_forms_result']='Honeypot failed. Field "'.$honeyfield.'" was not empty';
						}
					unset($_REQUEST[$honeyfield]);
					unset($_REQUEST['_honeypot']);
					}
				$emailcheck='';
				if(isset($CONFIG['_emailcheck'])){$emailcheck=$CONFIG['_emailcheck'];}
				elseif(isset($_REQUEST['_emailcheck'])){$emailcheck=$_REQUEST['_emailcheck'];}
				if(strlen($emailcheck)){
					$emailfield=$emailcheck;
					if(!isEmail($_REQUEST[$emailfield])){
						$spam++;
						$_REQUEST['_forms_result']='Email Check failed. "'.$_REQUEST[$emailfield].'" is an invalid email.';
						}
					unset($_REQUEST['_emailcheck']);
	            	}
				if($spam==0){
					//save form data as xml in the _forms table
					//unset
					$dupcheck=0;
					if(isset($CONFIG['_dupcheck'])){$dupcheck=$CONFIG['_dupcheck'];}
					elseif(isset($_REQUEST['_dupcheck'])){$dupcheck=$_REQUEST['_dupcheck'];}
					unset($_REQUEST['_botcheck']);
					unset($_REQUEST['_dupcheck']);
					//get rid of x and y since they change  based on where the user clicked
					unset($_REQUEST['x']);
					unset($_REQUEST['y']);
					$_REQUEST['_md5']=md5(implode('',$_REQUEST) . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . $_SERVER['GUID']);
					//add to forms table
					$opts=array();
					$fields=getDBFields("_forms",1);
					foreach($fields as $field){
						$ucfld=strtoupper($field);
						$ucffld=ucfirst($field);
						if(isset($_SERVER[$ucfld])){$opts[$field]=$_SERVER[$ucfld];}
						elseif(isset($_REQUEST[$field])){$opts[$field]=$_REQUEST[$field];}
						elseif(isset($_REQUEST[$ucffld])){$opts[$field]=$_REQUEST[$ucffld];}
						elseif(isset($_REQUEST[$ucfld])){$opts[$field]=$_REQUEST[$ucfld];}
				    	}
				    $opts['-table']="_forms";
					$opts['_formname']=$_REQUEST['_formname'];
				    unset($_REQUEST['_md5']);
					//Dup check? get the md5 of the xml data. If an exact entry is already recorded discard
					unset($form);
					if(isset($_REQUEST['_forms_id']) && isNum($_REQUEST['_forms_id'])){
						$form=getDBRecord(array('-table'=>"_forms",'_formname'=>$_REQUEST['_formname'],'_id'=>$_REQUEST['_forms_id'],'-nocache'=>1));
	                	}
	                if(isset($form) && is_array($form)){
						//Edit existing _forms data but leave the server info the same
						$xmldata=xmldata2Array($form['_xmldata']);
						$opts['_xmldata']= request2XML($_REQUEST,$xmldata['server']);
						$opts['-where']="_id={$form['_id']}";
						$_REQUEST['_forms_result']=editDBRecord($opts);
						$_REQUEST['_forms_id']=$form['_id'];
	                	}
	                else{
						//get the xml data
						$opts['_xmldata']= request2XML($_REQUEST);
						$dup=0;
						if(isNum($dupcheck) && $dupcheck==1 && isset($opts['_md5'])){
							$count=getDBCount(array('-table'=>"_forms",'_formname'=>$formname,'_md5'=>$opts['_md5']));
							$_REQUEST['_forms_dupcheck']=$count;
							if(isNum($count) && $count > 0){
								$_REQUEST['_forms_result']='Dupcheck failed. '.$formname.' record with md5 of "'.$opts['_md5'].'" already exists';
								$dup++;
								}
							}
						if($dup==0){
						    $id=addDBRecord($opts);
						    $_REQUEST['_forms_result']=$id;
						    if(isNum($id)){
								$_REQUEST['_forms_id']=$id;
	                        	}
						    }
						}
				    }
				}
			break;
		case 'CHAT':
			//Chat requires a valid USER
			if(isUser()){
				//verify _chat table exists
				if(!isDBTable('_chat')){createWasqlTable('_chat');}
				//remove messages older than 10
                $chatdays=10;
                if(isset($CONFIG['chatdays'])){$chatdays=$CONFIG['chatdays'];}
                //Remove user messages older than 10 days
                $ok=delDBRecord(array('-table'=>'_chat','-nolog'=>1,'-where'=>"_cdate < DATE_SUB(NOW(), INTERVAL {$chatdays} DAY)"));
                //Remove ping messages older than 3 minutes
                $ok=delDBRecord(array('-table'=>'_chat','-nolog'=>1,'-where'=>"message = 'Ping' and _cdate < DATE_SUB(NOW(), INTERVAL 3 MINUTE)"));
				//get last record for this user
				//$lastrec=getDBRecord(array('-table'=>'_chat','-order'=>'_cdate desc','user_id'=>$USER['_id'],'-limit'=>1));
				//add rec
				$fields=getDBFields('_chat');
				$addopts=array('-table'=>'_chat','-nolog'=>1);
				foreach($fields as $field){
					if(isset($_REQUEST[$field]) && strlen($_REQUEST[$field])){$addopts[$field]=$_REQUEST[$field];}
                	}
                $addopts['user_id']=$USER['_id'];
                $addopts['remote_addr']=$_SERVER['REMOTE_ADDR'];
				//record chat message - user_id, ip_address, message
				$id=addDBRecord($addopts);
				header('Content-type: text/xml');
				if(!isNum($id)){
					echo '<main>'.PHP_EOL;
					echo '	<error>'.xmlEncodeCDATA($id).'</error>'.PHP_EOL;
					echo '</main>'.PHP_EOL;

					exit;
                	}
				/*
					Items to send back:
						<stats>4 users online: slloyd, jdespain</stats>
						<list>user messages since last ping go here</list>
				*/
				echo xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
				echo '<chat>'."\r\n";
				$timestamp=time()-90;
				$recs=getDBRecords(array('-nolog'=>1,'-query'=>"select distinct(user_id) from _chat where UNIX_TIMESTAMP(_cdate) > {$timestamp}",'-relate'=>1));
				$chatusers=array();
				foreach($recs as $rec){$chatusers[]=$rec['user_id_ex']['username'];}
                echo '	<stats>'.count($chatusers).' users: '.implode(', ',$chatusers).'</stats>'."\r\n";
                 //Get list of messages to append
				$recopts=array('-table'=>'_chat','-nolog'=>1,'-relate'=>1,'-order'=>'_cdate desc','-limit'=>150);
				if($addopts['message']=='Started Domain Chat'){
					$recopts['-where']="DATE(_cdate) = CURDATE() and not(message in ('Ping','Started Domain Chat','Exited Domain Chat'))";
                	}
				elseif(isNum($_REQUEST['lastchatmsgid']) && $_REQUEST['lastchatmsgid'] > 0){
					$recopts['-where']="DATE(_cdate) = CURDATE() and _id > {$_REQUEST['lastchatmsgid']} and not(message in ('Ping','Started Domain Chat','Exited Domain Chat'))";
					}
				//List format:
				//user&datestamp&message
				$lines=array();
				echo '	<where>'.$recopts['-where'].'</where>'."\r\n";
				$recs=getDBRecords($recopts);
				if(is_array($recs)){
					foreach($recs as $rec){
						$rdate=date("D M d \\a\\t g:i a",$rec['_cdate_utime']);
						$lines[]=array($rec['_id'],$rec['user_id_ex']['username'],$rec['_cdate_utime'],$rec['message']);
						//"On {$rdate} from {$rec['user_id_ex']['firstname']} {$rec['user_id_ex']['lastname']}:\r\n  - {$rec['message']}";
						}
                	}
                echo '	<list>'."\r\n";
                //array_reverse($lines);
                foreach($lines as $line){
                	echo implode('&',$line) . "\r\n";
					}
				echo '	</list>'."\r\n";
				echo '</chat>'."\r\n";
				exit;
				}
			break;
		case 'API':
			/*
				API request require the following:
					- Must be a POST
					- Must be a valid user authentication
					- User must be an administrator
					- Must specify a valid table as "_table_"
					- Must specify a valid where as "_where_"
				Optional parameters:
					- _order_ sets the order by
					- _format_ = (json|xml) - format of output, defaults to xml

			*/
			header ("Content-type: text/xml");
			//require POST
			if(strtoupper($_SERVER['REQUEST_METHOD']) != 'POST'){
				echo "<catalog version=\"1.0\" date=\"".date("Y-m-d H:i:s")."\">\n";
				echo "    <status>Failed</status>\n";
				echo "    <method>{$_SERVER['REQUEST_METHOD']}</method>\n";
				echo "    <error>Unsupported API Request Method - You must use POST</error>\n";
				echo "</catalog>\n";
				exit;
				}
			//require a valid user
			if(!isUser()){
				echo "<catalog version=\"1.0\" date=\"".date("Y-m-d H:i:s")."\">\n";
				echo "    <status>Failed</status>\n";
				echo "    <error>Unauthorized API Request Method - You must authenticate</error>\n";
				echo "</catalog>\n";
				exit;
				}
			//require admin privileges
			if($USER['utype']!=0){
				echo "<catalog version=\"1.0\" date=\"".date("Y-m-d H:i:s")."\">\n";
				echo "    <status>Failed</status>\n";
				echo "    <username>{$USER['username']}</username>\n";
				echo "    <error>Unauthorized API Request Method - User must have admin privileges</error>\n";
				echo "</catalog>\n";
				exit;
				}
			//require a _table_ value
			if(!isset($_REQUEST['_table_'])){
				echo "<catalog version=\"1.0\" date=\"".date("Y-m-d H:i:s")."\">\n";
				echo "    <status>Failed</status>\n";
				echo "    <error>Invalid API Request Method - missing _table_ value</error>\n";
				echo "</catalog>\n";
				exit;
				}
			//require a _where_ value
			if(!isset($_REQUEST['_where_'])){
				echo "<catalog version=\"1.0\" date=\"".date("Y-m-d H:i:s")."\">\n";
				echo "    <status>Failed</status>\n";
				echo "    <table>{$_REQUEST['_table_']}</table>\n";
				echo "    <error>Invalid API Request Method - missing _where_ value</error>\n";
				echo "</catalog>\n";
				exit;
				}
			$table=$_REQUEST['_table_'];
			$where=$_REQUEST['_where_'];
			$recopts=array('-table'=>$table,'-where'=>$where);
			if(isset($_REQUEST['_order_'])){$recopts['-order']=$_REQUEST['_order_'];}
			$recs=getDBRecords($recopts);
			$tables=getDBTables();
			$isTable=array();
			foreach($tables as $table){$isTable[$table]=true;}
			$fieldinfo=getDBFieldInfo($_REQUEST['_table_']);
			$fields=array_keys($fieldinfo);
			//build any reference tables
			$reference=array();
			$refmap=array();
			foreach($fields as $field){
				if(preg_match('/^(.+)_id$/',$field,$fmatch) && $fieldinfo[$field]['_dbtype']=='int'){
					//is there a matching table?
					unset($rtable);
					if($isTable[$fmatch[1]]){$rtable=$fmatch[1];}
					elseif($isTable["_{$fmatch[1]}"]){$rtable="_{$fmatch[1]}";}
					elseif($isTable["_{$fmatch[1]}s"]){$rtable="_{$fmatch[1]}s";}
					if(isset($rtable)){
						//get ids
						$ids=array();
						foreach($recs as $rec){$ids[$rec[$field]]+=1;}
						$ids=array_keys($ids);
						sort($ids);
						$idstr=implode(',',$ids);
						$reference[$field]=getDBRecords(array('-table'=>$rtable,'-where'=>"_id in ({$idstr})",'-index'=>'_id'));
						$refmap[$field]=$fmatch[1];
						}
                    }
                }
			$out=array();
			foreach($recs as $rec){
				foreach($fields as $field){
					$val=$rec[$field];
					if(is_array($reference[$field]) && isNum($val) && is_array($reference[$field][$val])){
						$rec[$refmap[$field]][]=$reference[$field][$val];
                    	}
                	}
                $out[]=$rec;
            	}
            $params=array();
            foreach($_REQUEST as $key=>$val){
				if(preg_match('/^\_/',$key)){continue;}
				if(preg_match('/^(GUID|PHPSESSID)$/',$key)){continue;}
				if(is_string($val)){$params[$key]=$val;}
            	}
            switch(strtolower($_REQUEST['_format_'])){
				case 'json':
					echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
				break;
				default:
            		echo arrays2XML($out,$params);
            	break;
			}
            exit;
			//----------------------------------------------------------------------------------------------------
			break;
		case 'EDITFIELD':
			//require table,id,field
			if(!isset($_REQUEST['table'])){
				echo "EditField Error: Missing table";exit;
			}
			if(!isset($_REQUEST['id'])){
				echo "EditField Error: Missing id";exit;
			}
			if(!isset($_REQUEST['field'])){
				echo "EditField Error: Missing field";exit;
			}
			$rec=getDBRecord(array(
				'-table'=>$_REQUEST['table'],
				'_id'=>$_REQUEST['id'],
				'-fields',$_REQUEST['field']
			));
			$formname="editformfield_".strtolower(getRandomColor(0));
			$opts=array('-formname'=>$formname,'style'=>'width:90%;','value'=>$rec[$_REQUEST['field']]);
			$finfo=getDBFieldInfo($_REQUEST['table'],1);
			//centerpop?
			if(isset($_REQUEST['div']) && $_REQUEST['div']=='centerpop'){
				echo '<div class="w_centerpop_title">Edit '.$_REQUEST['field'].'</div>'.PHP_EOL;
				echo '<div class="w_centerpop_content">'.PHP_EOL;
				$opts['style']='width:100%';
				echo '<form style="display:flex;justify-content:flex-end;" method="post" name="'.$formname.'" enctype="multipart/form-data" action="/php/index.php" onsubmit="return ajaxSubmitForm(this,\'null\');">'.PHP_EOL;
			}
			else{
				echo '<form style="display:flex;justify-content:flex-end;" method="post" name="'.$formname.'" enctype="multipart/form-data" action="/php/index.php" onsubmit="return ajaxSubmitForm(this,\''.$_REQUEST['div'].'\');">'.PHP_EOL;
				echo '	<input type="hidden" name="setprocessing" value="0" >'.PHP_EOL;
			}
			if(isset($_REQUEST['noicon'])){
				echo '	<input type="hidden" name="_noicon" value="'.$_REQUEST['noicon'].'" >'.PHP_EOL;	
			}
			echo '	<input type="hidden" name="_table" value="'.$_REQUEST['table'].'" >'.PHP_EOL;
			echo '	<input type="hidden" name="_fields" value="'.$_REQUEST['field'].'" >'.PHP_EOL;
			echo '	<input type="hidden" name="_id" value="'.$_REQUEST['id'].'" >'.PHP_EOL;
			echo '	<input type="hidden" name="_action" value="EDIT" >'.PHP_EOL;
			echo '	<input type="hidden" name="_editfield" value="'.$_REQUEST['field'].'" >'.PHP_EOL;
			switch(strtolower($_REQUEST['table'])){
				case '_config':
					switch(strtolower($_REQUEST['field'])){
						case 'current_value':
							if(strlen($rec['possible_values'])){
								if(stringBeginsWith($rec['possible_values'],'&')){
									$efield=$_REQUEST['field'];
									$_REQUEST[$efield]=$rec[$efield];
									$evalstr='<?='.preg_replace('/^\&/','',$rec['possible_values']).'?>';
									echo evalPHP($evalstr);
								}
								else{
									$cvals=preg_split('/\,/',$rec['possible_values']);
									$copts=array();
									foreach($cvals as $cval){
										$parts=preg_split('/\=/',$cval,2);
										if(count($parts)==2){
											$t=trim($parts[0]);
											$d=trim($parts[1]);
											$copts[$t]=$d;
										}
										else{
											$t=trim($parts[0]);
											$copts[$t]=$t;
										}
									}
									$cparams=array(
										'message'=>' --- ',
										'value'=>$rec[$_REQUEST['field']]
									);
									echo buildFormSelect($_REQUEST['field'],$copts,$cparams);
								}
							}
							else{
								echo buildFormField($_REQUEST['table'],$_REQUEST['field'],$opts);
							}
						break;
						default:
							echo buildFormField($_REQUEST['table'],$_REQUEST['field'],$opts);
						break;
					}
				break;
				default:
					echo buildFormField($_REQUEST['table'],$_REQUEST['field'],$opts);
				break;
			}
			
			echo '	<button type="submit" title="save" style="padding:3px;"><span class="icon-save w_bigger"></span></button>'.PHP_EOL;
			echo '</form>'.PHP_EOL;
			echo buildOnLoad("document.{$formname}.{$_REQUEST['field']}.select();");
			//centerpop?
			if(isset($_REQUEST['div']) && $_REQUEST['div']=='centerpop'){
				echo '</div>'.PHP_EOL;
			}
			exit;
		break;
  		case 'EDITFORM':
			//show the edit form
			unset($_REQUEST['template']);
			unset($_REQUEST['_template']);
			unset($_REQUEST['_action']);
			$ok=includePage('blank',array('--table'=>'_templates'));
			if(!isset($_REQUEST['-action']) && isset($_SERVER['HTTP_REFERER'])){
				$_REQUEST['-action']=preg_replace('/.+?'.$_SERVER['HTTP_HOST'].'/i','',$_SERVER['HTTP_REFERER']);
				$_REQUEST['-action']=preg_replace('/\?.*$/','',$_REQUEST['-action']);
            	}
            //set defaults for fielddata table on new fields
            if(!isset($_REQUEST['-table']) && isset($_REQUEST['_table'])){
            	$_REQUEST['-table']=$_REQUEST['_table'];
            }
            if($_REQUEST['-table']=='_fielddata' && (!isset($_REQUEST['_id']) || $_REQUEST['_id']==0)){
				if(isset($_REQUEST['fieldname'])){
					$info=getDBFieldInfo($_REQUEST['tablename']);
					$fieldname=$_REQUEST['fieldname'];
					$_REQUEST['inputmax']=$info[$fieldname]['_dblength'];
					if(preg_match('/^(NO|False|0)$/i',$info[$fieldname]['_dbnull'])){
						if(!isset($info[$fieldname]['_dbdefault']) || !isNum($info[$fieldname]['_dbdefault']) || $info[$fieldname]['_dbdefault'] != 0){
							$_REQUEST['required']=1;
						}
	                }
	                switch($info[$fieldname]['_dbtype']){
						case 'string':
						case 'blob':
							if($info[$fieldname]['_dblength']>255){
								$_REQUEST['inputtype']="textarea";
								$_REQUEST['width']=600;
								$_REQUEST['height']=75;
								}
							else{
								$_REQUEST['inputtype']="text";
								if($info[$fieldname]['_dblength']>100){
									$_REQUEST['width']=200;
									}
								}
							break;
						case 'json':
							$_REQUEST['displayname']=ucfirst($fieldname).' (JSON)';
							$_REQUEST['inputmax']="";
							$_REQUEST['inputtype']="textarea";
							$_REQUEST['width']=600;
							$_REQUEST['height']=125;
							break;
						case 'int':
							$_REQUEST['mask']="integer";
							if($info[$fieldname]['_dblength']<5){
								$_REQUEST['inputtype']="checkbox";
								$_REQUEST['tvals']=1;
								}
							else{
	                        	$_REQUEST['inputtype']="text";
								$_REQUEST['width']=50;
								}
							break;
						case 'real':
							$_REQUEST['mask']="number";
							$_REQUEST['inputtype']="text";
							$_REQUEST['width']=50;
							break;
						case 'datetime':
						case 'date':
							$_REQUEST['inputtype']="date";
							$_REQUEST['width']='';
						break;
						case 'time':
							$_REQUEST['inputtype']="time";
							$_REQUEST['width']='';
						break;
						default:
							$_REQUEST['inputtype']="text";
							$_REQUEST['width']=200;
						break;
	                }
					if(preg_match('/email/i',$fieldname)){
						$_REQUEST['mask']="email";
	                }
	                elseif(preg_match('/^(zipcode|postal_code|zip)$/i',$fieldname)){
						$_REQUEST['mask']="zipcode";
	                }
	                elseif(preg_match('/(phone|fax)/i',$fieldname)){
						$_REQUEST['mask']="phone";
	                }
	                elseif(preg_match('/^(user_id|_cuser|_euser)$/i',$fieldname)){
						$_REQUEST['inputtype']="select";
						unset($_REQUEST['inputmax']);
						unset($_REQUEST['mask']);
						$_REQUEST['tvals']="select _id from _users order by firstname,lastname,_id";
						$_REQUEST['dvals']="select firstname,lastname from _users order by firstname,lastname,_id";
	                }
	                elseif(preg_match('/^state$/i',$fieldname)){
						$_REQUEST['inputtype']="select";
						unset($_REQUEST['mask']);
						unset($_REQUEST['inputmax']);
						$_REQUEST['tvals']="select code from states order by name,code,_id";
						$_REQUEST['dvals']="select name from states order by name,code,_id";
	                }
	            }
			}
			$title=isset($_REQUEST['_id']) && isNum($_REQUEST['_id'])?'Edit Record':'Add Record';
			echo addEditDBForm($_REQUEST);
			exit;
			break;
		}
	}
//---------- begin function processFileUploads---------------------------------------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function processFileUploads($docroot=''){
	if(isset($_REQUEST['ProcessFileUploads_CallCount'])){return false;}
	$_REQUEST['ProcessFileUploads_CallCount']+=1;
	global $USER;
	global $CONFIG;
	if(strlen($docroot)==0){$docroot=$_SERVER['DOCUMENT_ROOT'];}
	//echo "processFileUploads".printValue($_FILES).printValue($_REQUEST);exit;
	//if(preg_match('/multipart/i',$_SERVER['CONTENT_TYPE']) && is_array($_FILES) && count($_FILES) > 0){
	if(is_array($_FILES) && count($_FILES) > 0){
		//build a files list to handle multiple files in a single file input field
		$pfiles=array();
		foreach($_FILES as $name=>$file){
			if(is_array($file['name'])){
				$flds=array_keys($file);
				foreach($file['name'] as $f=>$val){
					$pfile=array('iname'=>$name,'pname'=>$name.'_'.$f);
					foreach($flds as $fld){
						$pfile[$fld]=$file[$fld][$f];
					}
					$pfiles[]=$pfile;
				}
			}
			else{
				$file['iname']=$file['pname']=$name;
				$pfiles[]=$file;
			}
		}
		//echo printValue($pfiles);exit;
		foreach($pfiles as $pi=>$file){
			if((is_array($file['name']) && !count($file['name'])) || !strlen($file['name'])){
	 			unset($pfiles[$pi]);
	 			unset($_FILES[$file['iname']]);
	 		}
	 		elseif($file['error'] != 0 && !strlen($file['tmp_name'])){
	 			unset($pfiles[$pi]);
	 			unset($_FILES[$file['iname']]);
	 			switch((integer)$file['error']){
	 				case 1:
	 					$message="The uploaded file exceeds the upload_max_filesize directive in php.ini";
	 				break;
	 				case 2:
	 					$message="The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
	 				break;
	 				case 3:
	 					$message="The uploaded file was only partially uploaded.";
	 				break;
	 				case 4:
	 					$message="No file was uploaded.";
	 				break;
	 				case 6:
	 					$message="Missing a temporary folder.";
	 				break;
	 				case 7:
	 					$message="Failed to write file to disk.";
	 				break;
	 				case 8:
	 					$message="A PHP extension stopped the file upload.";
	 				break;
	 				default:
	 					$message="unknown error";
	 				break;

	 			}
	 			$error=array(
	 				'error'=>'processFileUploads',
	 				'filename'=>$name,
	 				'message'=>$message
	 			);
	 			debugValue($error);
				$_REQUEST[$name.'_error']=$error;
				continue;
			}
			elseif(strlen($file['tmp_name'])==0 && strlen($file['type'])==0 ){
	 			unset($pfiles[$pi]);
	 			unset($_FILES[$file['iname']]);
	 			continue;
	 		}
	 	}
		//echo printValue($_REQUEST);
	 	foreach($pfiles as $pi=>$file){
			if($file['name']=='blob' && isset($_SERVER['HTTP_X_BLOB_NAME'])){
            	$file['name']=$_SERVER['HTTP_X_BLOB_NAME'];
            	if(isset($_SERVER['HTTP_X_CHUNK_NUMBER'])){
					$file['name'].='.chunk'.$_SERVER['HTTP_X_CHUNK_NUMBER'];
				}
			}
            $_REQUEST[$file['pname'].'_type']=$file['type'];
            $_REQUEST[$file['pname'].'_size']=$file['size'];
            //get the weburl and the abs path of the file
			$webpath='/' . $file['name'];
			$abspath=$docroot . $webpath;
			//clean up filename
			$_REQUEST[$file['pname'].'_ori']=$file['name'];
            $file['name']=preg_replace('/\%20+/','_',$file['name']);
			$file['name']=preg_replace('/\,/','',$file['name']);
			$file['name']=preg_replace('/\.\./','.',$file['name']);
			//check for ipath directive
			$ipathname='ipath_'.$file['iname'];
			//autonumber
			if(isset($_REQUEST[$file['iname'].'_autonumber']) && $_REQUEST[$file['iname'].'_autonumber']==1){
				//change the filename to be unique
				$crc=encodeCRC(sha1_file($file['tmp_name']));
				$file['name']=getFileName($file['name'],1) . '_' . $crc . '.' . getFileExtension($file['name']);
				$file['name']=str_replace(' ','_',$file['name']);
			}
			elseif(isset($_REQUEST[$file['iname'].'_rename'])){
				/*Rename specs:
					%key% will be replace with the value of $_REQUEST[key}
				*/
				$rename=$_REQUEST[$file['iname'].'_rename'];
				$ext=getFileExtension($file['name']);
				$rename=str_replace('%time()%',time(),$rename);
				$rename=str_replace('%sha()%',sha1($file['tmp_name']),$rename);
                foreach($_REQUEST as $rfld=>$rval){
					$rfldstr='%'.$rfld.'%';
                    $rename=str_replace($rfldstr,$rval,$rename);
                }
				//change the filename to be unique
				$file['name']=$rename . ".{$ext}";
				$file['name']=str_replace(' ','_',$file['name']);
			}
			if(strlen($_REQUEST['_dir'])){
				$cpath =decodeBase64($_REQUEST['_dir']);
				$cpath=str_replace('//','/',$cpath);
				$abspath=$cpath .'/'. $file['name'];
				if(!is_dir($cpath) && strlen($_REQUEST[$file['iname'].'_path'])){
					$path=$_REQUEST[$file['iname'].'_path'];
					$cpath=$docroot . $path;
					$cpath=str_replace('//','/',$cpath);
					if(!is_dir($cpath)){
						@trigger_error("");
						mkdir($cpath,0777,1);
					}
					$webpath = $path .'/'. $file['name'];
					$abspath = $docroot . $webpath;
				}
			}
			elseif(strlen($_REQUEST[$file['iname'].'_path'])){
				$wpath=getWasqlPath();
				$path=$_REQUEST[$file['iname'].'_path'];
				if($path=='wasql_temp_path'){
					$cpath=getWasqlPath('php/temp');
					$webpath="/php/temp/{$file['name']}";
					$abspath="{$cpath}/{$file['name']}";
				}
				elseif(isAdmin() && is_dir($path) && stringContains($path,$wpath)){
                	$cpath=$path;
                	$webpath='/'.str_replace($wpath,'',$path).'/'. $file['name'];
                	$abspath=$path.'/'. $file['name'];
				}
				else{
					$cpath=$docroot . $path;
					$cpath=str_replace('//','/',$cpath);
					if(!is_dir($cpath)){
						@trigger_error("");
						mkdir($cpath,0777,1);
					}
					$webpath = $path .'/'. $file['name'];
					$abspath = $docroot . $webpath;
				}
			}
			elseif(strlen($_REQUEST['_path'])){
				$path=$_REQUEST['_path'];
				$cpath=$docroot . $path;
				$cpath=str_replace('//','/',$cpath);
				if(!is_dir($cpath)){
					@trigger_error("");
					mkdir($cpath,0777,1);
				}
				$webpath = $path .'/'. $file['name'];
				$abspath = $docroot . $webpath;
			}
			else{
				$path='/uploads';
				$cpath=$docroot . $path;
				$cpath=str_replace('//','/',$cpath);
				if(!is_dir($cpath)){
					@trigger_error("");
					mkdir($cpath,0777,1);
				}
				$webpath = $path .'/'. $file['name'];
				$abspath = $docroot . $webpath;
			}
			$webpath=str_replace('//','/',$webpath);
            $abspath=str_replace('//','/',$abspath);
            $absdir=getFilePath($abspath);
            if(!is_dir($absdir)){
				@trigger_error("");
				mkdir($absdir,0777,1);
			}
    		if(!file_exists($file['tmp_name'])){
            	$_REQUEST[$file['pname'].'_upload_error']=$file['tmp_name'] . " does not exist";
            	continue;
            }
            @trigger_error("");
            $_REQUEST[$file['pname'].'_abspath']=$abspath;
            @move_uploaded_file($file['tmp_name'],$abspath);
            if(is_file($abspath)){
            	$_REQUEST[$file['pname']]=$webpath;
            	//if this is a chunk - see if all chunks are here and combine them.
				if(isset($_SERVER['HTTP_X_CHUNK_NUMBER']) && isset($_SERVER['HTTP_X_CHUNK_TOTAL'])){
					$realname=preg_replace('/\.chunk([0-9]+)$/','',$file['name']);
					$xfiles=array();
					for($x=0;$x<$_SERVER['HTTP_X_CHUNK_TOTAL'];$x++){
						$i=$x+1;
						$xfile="{$absdir}/{$realname}.chunk{$i}";
						if(!is_file($xfile)){break;}
						$xfiles[]=$xfile;
					}
					if(count($xfiles)==$_SERVER['HTTP_X_CHUNK_TOTAL']){
                    	if(mergeChunkedFiles($xfiles,"{$absdir}/{$realname}")){
							$abspath="{$absdir}/{$realname}";
							$webpath = "{$path}/{$realname}";
							$_REQUEST[$file['pname'].'_abspath']=$abspath;
						}

					}
				}
				$ext=strtolower(getFileExtension($file['name']));
				$ok=commonProcessFileActions($file['iname'],$abspath);
				//
            	$_REQUEST[$file['pname']]=$webpath;
            	$_REQUEST[$file['pname'].'_abspath']=$abspath;
            	$_REQUEST[$file['pname'].'_webpath']=$webpath;
				$_REQUEST[$file['pname'].'_sha1']=sha1_file($abspath);
				$_REQUEST[$file['pname'].'_size']=filesize($abspath);
				//echo $name.printValue($_REQUEST);exit;
				//Perhaps we should extract the exif info from the file.
				// /cgi-bin/exif.pl?file=$afile
            	//if the uploaded file is an image - get its width and height
            	if(isImageFile($abspath)){
					$info=@getimagesize($abspath);
					if(is_array($info)){
                        $_REQUEST[$file['pname'].'_width']=$info[0];
                        $_REQUEST[$file['pname'].'_height']=$info[1];
                    }
                }
            }
            else{
				$e=error_get_last();
				if($e['message']!==''){
		    		// An error occurred uploading the file
		    		$_REQUEST[$file['pname'].'_error']=array(
						"File Upload Error (2)",
						$e,
						$file,
						$abspath
					);
				}
				else{
					$_REQUEST[$file['pname'].'_error']=array(
						"File Upload Error (3)",
						$e,
						$file,
						$abspath
					);
                }
            }
        }
        //ksort($_REQUEST);echo printValue($_FILES).printValue($pfiles).printValue($_REQUEST);exit;
		return 1;
	}
	//echo printValue($_REQUEST);exit;
    return 0;
}
//---------- begin function commonParseIni
/**
* @describe converts an ini formatted string into an array
* @param str str - string or filename of the ini
* @param [multi] int - if 1 then allow multiple unique keys under an ini heading
* @usage
*	$settings=commonParseIni($str);
*	$settings=commonParseIni($afile);
* 	$multi_settings=commonParseIni($str,1);
*/
function commonParseIni($str,$multi=0){
	if(is_file($str)){
		$str=file_get_contents($str);
	}
	$lines=preg_split('/[\r\n]+/',$str);
	$settings=array();
	$key='';
	foreach($lines as $line){
		if(preg_match('/^\#/',trim($line))){continue;}
		if(preg_match('/^\[(.+)\]$/',trim($line),$m)){
			$key=strtolower($m[1]);
			continue;
		}
		if(!strlen($key)){continue;}
		if(!strlen(trim($line))){continue;}
		$parts=preg_split('/\=/',trim($line),2);
		
		if(count($parts)==2){
			$subkey=strtolower(trim($parts[0]));
			if(stringBeginsWith($subkey,'#')){continue;}
			if($multi==1){
				$settings[$key][$subkey][]=$parts[1];
			}
			else{
				$settings[$key][$subkey]=$parts[1];
			}
		}
		else{
			$settings[$key][]=$parts[0];
		}
	}
	if($multi==1){
		foreach($settings as $key=>$subkeys){
			foreach($subkeys as $subkey=>$subval){
				if(is_array($subval) && count($subval)==1){
					$settings[$key][$subkey]=$subval[0];
				}
			}
		}
	}
	return $settings;
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function commonProcessFileActions($name,$afile){
	global $CONFIG;
	$adir=getFilePath($afile);
	$ext=getFileExtension($afile);
	$mimetype=getFileMimeType($afile);
	//echo "Mime:{$mimetype},ext:{$ext} - commonProcessFileActions({$name},{$afile})<br>";
	//process $recs=call_user_func($params['-results_eval'],$recs)
	$process='';
	if(isset($_REQUEST[$name.'_process']) && strlen($_REQUEST[$name.'_process'])){
		$process=$_REQUEST[$name.'_process'];
	}
	elseif(isset($_REQUEST['data-process']) && strlen($_REQUEST['data-process'])){
		$process=$_REQUEST['data-process'];
	}
	elseif(isset($CONFIG['file_process']) && strlen($CONFIG['file_process'])){
		$process=$CONFIG['file_process'];
	}
	if(strlen($process)){
		if(function_exists($process)){
			$_REQUEST[$name.'_process_results']=call_user_func($process,$name,$afile);
		}
		else{
			$_REQUEST[$name.'_process_results']="function {$process} does not exist (yet)";
		}
	}
	$resize='';
	if(isset($_REQUEST[$name.'_resize']) && strlen($_REQUEST[$name.'_resize'])){
		$resize=$_REQUEST[$name.'_resize'];
	}
	elseif(isset($_REQUEST['data-resize']) && strlen($_REQUEST['data-resize'])){
		$resize=$_REQUEST['data-resize'];
	}
	elseif(isset($CONFIG['resize']) && strlen($CONFIG['resize'])){
		$resize=$CONFIG['resize'];
	}
	//echo "resize:{$resize}<br>";
	if(strlen($resize) && stringContains($mimetype,'image')){
		if(!isset($CONFIG['resize_command'])){
			$CONFIG['resize_command']="convert -thumbnail";
		}
		//resize
		$cmd=$CONFIG['resize_command'];
		$fname=getFileName($afile,1);
		$refile=str_replace($fname,$fname.'_resized',$afile);
		$cmd="{$cmd} {$resize} \"{$afile}\" -auto-orient \"{$refile}\"";
		$ok=cmdResults($cmd);
		if(is_file($refile) && filesize($refile) > 0){
			unlink($afile);
			rename($refile,$afile);
			$_REQUEST[$name.'_size_original']=$_REQUEST[$name.'_size'];
    		$_REQUEST[$name.'_size']=filesize($afile);
		}
    	$_REQUEST[$name.'_resized']=$ok;       	
	}
	//convert the file
	$convert='';
	if(isset($_REQUEST[$name.'_convert']) && strlen($_REQUEST[$name.'_convert'])){
		$convert=$_REQUEST[$name.'_convert'];
	}
	elseif(isset($_REQUEST['data-convert']) && strlen($_REQUEST['data-convert'])){
		$convert=$_REQUEST['data-convert'];
	}
	elseif(isset($CONFIG['convert']) && strlen($CONFIG['convert'])){
		$convert=$CONFIG['convert'];
	}
	//echo "convert:{$convert}<br>";
	if(strlen($convert)){
		if(!isset($CONFIG['convert_command'])){
			$CONFIG['convert_command']="convert ";
		}
		$cmd=$CONFIG['convert_command'];
		//bmp-jpg,heic-jpg,tiff-jpg,jpeg-jpg
		$sets=preg_split('/\,/',strtolower($convert));
		foreach($sets as $set){
			list($from,$to)=preg_split('/\-/',$set,2);
			//echo " -- convert: {$from}=={$ext}<br>";
			if($from==$ext){
				$fname=getFileName($afile,1);
				$tfile="{$adir}/{$fname}_reencoded.{$to}";
				$cmd="{$cmd} \"{$afile}\" \"{$tfile}\"";
        		$ok=cmdResults($cmd);
        		if(is_file($tfile) && filesize($tfile) > 0){
					unlink($afile);
					rename($tfile,$afile);
				}
            	$_REQUEST[$name.'_converted']=$ok;
			}
		}            	
	}
	//reencode the file
	$reencode='';
	if(isset($_REQUEST[$name.'_reencode']) && strlen($_REQUEST[$name.'_reencode'])){
		$reencode=$_REQUEST[$name.'_reencode'];
	}
	elseif(isset($_REQUEST['data-reencode']) && strlen($_REQUEST['data-reencode'])){
		$reencode=$_REQUEST['data-reencode'];
	}
	elseif(isset($CONFIG['reencode']) && strlen($CONFIG['reencode'])){
		$reencode=$CONFIG['reencode'];
	}
	//echo "reencode:{$reencode}<br>";
	if(strlen($reencode)){
		if(!isset($CONFIG['reencode_command'])){
			$CONFIG['reencode_command']="ffmpeg -i";
		}
		$cmd=$CONFIG['reencode_command'];
		//mp3-mp3,wav-mp3
		$sets=preg_split('/\,/',strtolower($reencode));
		foreach($sets as $set){
			list($from,$to)=preg_split('/\-/',$set,2);
			if($from==$ext){
				$fname=getFileName($afile,1);
				$tfile="{$adir}/{$fname}_reencoded.{$to}";
				if(is_file($tfile)){
					unlink($tfile);
				}
				$cmd="{$cmd} \"{$afile}\"";
				if($to=='mp4'){
					$cmd .= ' -vcodec h264 -acodec mp3 ';
				}
				$cmd .=" \"{$tfile}\"";
				$_REQUEST[$name.'_reencode_cmd']=$cmd;
        		$ok=cmdResults($cmd);
        		$_REQUEST[$name.'_reencode_cmd_results']=$ok;
        		if(is_file($tfile) && filesize($tfile) > 0){
					unlink($afile);
					rename($tfile,$afile);
				}
            	$_REQUEST[$name.'_reencoded']=$ok;
			}
		}            	
	}
	//echo printValue($_REQUEST);
}


//---------- begin function mergeChunkedFiles ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function commonDenyScriptsInDir($dir){
	if(!is_dir($dir)){return false;}
	$code=<<<ENDOFCODE
<FilesMatch "\.(py|php|php3|php4|phtml|pl|cgi|rb|rhtml|jsp|htm|html)$">
deny from all
</FilesMatch>
ENDOFCODE;
setFileContents("{$dir}/.htaccess",$code);
return true;
}
//---------- begin function mergeChunkedFiles ----
/**
 * @author slloyd
 * @exclude  - this function is for internal use only and thus excluded from the manual
 */
function mergeChunkedFiles($chunks,$name){
	//return false;
	if(!is_array($chunks) || !count($chunks)){return false;}
	$cnt=count($chunks);
	for($x=0;$x<$cnt;$x++){
		if(!is_file($chunks[$x])){return false;}
		$chunkdata=getFileContents($chunks[$x]);
    	// If it is the first chunk we have to create the file, othewise we append...
        $out_fp = @fopen($name, $x == 0 ? "wb" : "ab");
		fwrite($out_fp, $chunkdata);
		@fclose($out_fp);
	}
	if(file_exists($name)){
		//remove chunks
		for($x=0;$x<$cnt;$x++){
        	unlink($chunks[$x]);
		}
		return true;
	}
}
//---------- begin function pushData---------------------------------------
/**
* @describe pushes raw data to the browser as a file/attachment (forces a save as dialog)
* @param $data string
*    data you want to push (i.e. csv contents)
* @param $ext string
*  	extension of the filename. Defaults to txt
* @param $name string
*  	the filename.  If no filename is given, it creates a unique filename based on the data
* @return
* 	pushes the data to the browser and exits
* @usage
*	pushData($csvlines,'csv','mylist.csv');
* @author slloyd
* @history slloyd 2014-01-07 added documentation
*/
function pushData($data='',$ext='txt',$name=''){
	if(!strlen($data)){
		header('Content-type: text/plain');
		echo "pushData error: No Data to push!";
		exit;
		}
	if(!strlen($name)){$name=md5($data) . ".{$ext}";}
	header('Content-Description: File Transfer');
	header("Content-Type: text/{$ext}");
    header('Content-Disposition: attachment; filename='.basename($name));
    header("Accept-Ranges: bytes");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	header('ETag: ' . md5(time()));
	//Note: caching on https will make it so it will fail in IE
    if (isset($_SERVER['HTTPS'])) {
		header('Pragma: ');
		header('Cache-Control: ');
		}
    else{
    	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    	header('Pragma: public');
		}
    header('Content-Length: ' . strlen($data));
    echo $data;
    exit;
	}
//---------- begin function pushFile---------------------------------------
/**
* @describe pushes file contents to the browser as a file/attachment (forces a save as dialog)
* @param $file string
*    full path and name of file you want to push
* @param $params array
*  	-attach boolean - set to true to push as an attachment. Defaults to true.
*	-ctype string - content-type of filename.  If not set, it gets it based on file extension
* @return
* 	pushes the file to the browser and exits
* @usage
*	pushFile($csvfile);
* @author slloyd
* @history slloyd 2014-01-07 added documentation
*/
function pushFile($file='', $params=array()){
    if(!is_file($file)){
        header('Content-type: text/plain');
        echo "{$file} does NOT exist!";
        exit;
    }
    
    // Clear any output buffers to prevent "headers already sent" errors
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if(!isset($params['-attach'])){$params['-attach']=1;}
    if(isset($params['-ctype'])){$ctype=$params['-ctype'];}
    else{$ctype=getFileContentType($file);}
    if(!isset($params['-filename'])){$params['-filename']=getFileName($file);}
    
    // Remove @ suppressors and ensure headers aren't already sent
    if (!headers_sent()) {
        header("Content-Type: {$ctype}");
        header('Content-Description: File Transfer');
        
        if($params['-attach']){
            header('Content-Disposition: attachment; filename="'.$params['-filename'].'"');
        } else {
            header('Content-Disposition: inline; filename="'.$params['-filename'].'"');
        }
        
        if(!isTextFile($file)){
            header('Content-Transfer-Encoding: binary');
        }
        
        header('Content-Length: ' . filesize($file));
        header("X-Pushed-By: WaSQL");
        header("Accept-Ranges: bytes");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header('ETag: ' . sha1_file($file));
        
        if (isset($_SERVER['HTTPS'])) {
            header('Pragma: ');
            header('Cache-Control: ');
        } else {
            header("Cache-Control: no-cache, must-revalidate");
            header('Pragma: public');
        }
    }
    
    if(isset($params['-destroy'])){
        register_shutdown_function('unlink', $file);
    }
    
    readfile($file);
    exit;
}
//---------- begin---------------------------------------
/**
* @describe reads an RSS feed into an array and returns the array
* @param $url string
*    url to the RSS feed
* @param $hrs number number of hours before local cache is dirty.
* @param $force boolean force a refresh even if local cache is not too old
* @return array with each feed element an array with the following keys:
*	title - required - main title
*	link - required - main link
*	description - required - main description
*	pubdate - required - main pubdate
* @usage
*	$rss=readRSS($url);
* @author slloyd
* @history slloyd 2014-01-07 added documentation
*/
function readRSS($url,$hrs=3,$force=0){
	//set error string to blank
	$rss_log='';
	$results=array('feed'=>$url,'hrs'=>$hrs,'force'=>$force);
	$post=getStoredValue("return postURL('{$url}',array('-method'=>'GET'));",$force,$hrs);
	$lines=preg_split('/[\r\n]+/',$post['body']);
	//fix malformed & in links, etc
	foreach($lines as $i=>$line){
		$lines[$i]=str_replace('&amp;','[[[amp]]]',$lines[$i]);
		$lines[$i]=str_replace('&','&amp;',$lines[$i]);
		$lines[$i]=str_replace('[[[amp]]]','&amp;',$lines[$i]);
		
	}
	$content = implode('',$lines);
	//fix malformed links
	try{
		$xml = new SimpleXmlElement($content);
		}
	catch(Exception $e){
		$results['error']=printValue($e);
		$results['raw']=$content;
		return $results;
    	}
    //feedDate
    $results['feedDate']=date('D F j,Y g:i a',$results['feedDate_utime']);
	// define the namespaces that we are interested in
	$ns = $xml->getNamespaces(true);
	// step 2: extract the channel metadata
	$channel = array();
	$channel['title']       = (string)$xml->channel->title;
	$channel['link']        = (string)$xml->channel->link;
	$channel['description'] = (string)$xml->channel->description;
	$channel['pubDate']     = (string)$xml->pubDate;
	if(!strlen($channel['pubDate'])){$channel['pubDate']     = (string)$xml->channel->pubDate;}
	if(strlen($channel['pubDate'])){$channel['pubDate_utime']=strtotime($channel['pubDate']);}
	$channel['generator']   = (string)$xml->generator;
	if(!strlen($channel['generator'])){$channel['generator']     = (string)$xml->channel->generator;}
	$channel['language']    = (string)$xml->language;
	if(!strlen($channel['language'])){$channel['language']     = (string)$xml->channel->language;}
	// step 3: extract the articles
	$articles=array();
	foreach ($xml->channel->item as $item){
        $article = array();
        foreach($item as $citem=>$cval){
			$key=(string)$citem;
			if(isset($article[$key])){continue;}
            if(isNum((string)$cval)){$v=(float)$cval;}
			else{$v=removeCdata((string)$cval);}
			if(strlen($v)){$article[$key]=$v;}
		}
		//check for itunes elements -- https://stackoverflow.com/questions/11612712/reading-itunes-xml-file-with-php-dom-method
		//get values from all the namespaces
		foreach($ns as $nsk=>$nsurl){
			$nsitems = $item->children($nsurl);
			foreach($nsitems as $nsitemkey=>$nsitemval){
				$nsitemkey=(string)$nsitemkey;
                if(isset($article[$nsitemkey])){continue;}
				if(isNum((string)$nsitemval)){$v=(float)$nsitemval;}
				else{$v=removeCdata((string)$nsitemval);}
				if(strlen($v)){$article[$nsitemkey]=$v;}
				else{
					//get attributes
					$attribs=(array)$nsitemval->attributes();
					$attribs=(array)$attribs['@attributes'];
					foreach($attribs as $ak=>$av){
						$article["{$nsk}_{$nsitemkey}_{$ak}"]=$av;
					}
				}
			}
		}
        if(strlen($article['pubDate'])){$article['pubDate_utime']=strtotime($article['pubDate']);}
        // add this article to the list
        array_push($articles,$article);
		}
	$results['channel']=$channel;
	$results['articles']=$articles;
	$results['xml']=$xml;
	$results['raw']=$content;
	return $results;
	}
//---------- begin function readXML ----------
/**
* @describe reads an xml file or xml data string and returns the xml object
* @param file string
*	path and filename of xml file or xml data string to read
* @return object
*	xml object
* @usage $xml=readXML($xmlfile);
*/
function readXML($file){
	if(isXML($file)){
		$xml = simplexml_load_string($file);
		}
	else{
		$xml = simplexml_load_file($file);
    	}
	return $xml;
	}
//---------- begin function arrayToXML ----------
/**
* @describe
*	converts getDBRecords results into an rss feed
*	RSS feeds must have 'title','link','description','pubDate' for the main and each record
* @param recs array
*	a getDBRecords result array
* @param params array
*	Params:
*		title - required - main title
*		link - required - main link
*		description - required - main description
*		pubdate - required - main pubdate
* @return string
*	RSS XML string
* @usage $xml=arrayToXML($recs);
*/
function arrayToXML($data, $rootNodeName = 'data', $xml=null){
	// turn off compatibility mode as simple xml throws a wobbly if you don't.
	if (ini_get('zend.ze1_compatibility_mode') == 1){
		@ini_set ('zend.ze1_compatibility_mode', 0);
	}
	if ($xml == null){
		$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName >");
	}
	// loop through the data passed in.
	foreach($data as $key => $value){
		// make numeric keys strings
		if (is_numeric($key)){$key = "unknownNode_". (string) $key;}
		// replace anything not alpha numeric
		$key = preg_replace('/[^a-z]/i', '', $key);
		// if there is another array found recrusively call this function
		if (is_array($value)){
			$node = $xml->addChild($key);
			arrayToXML($value, $rootNodeName, $node);
			}
		else{
			// add single node.
			$value = htmlentities($value);
			$xml->addChild($key,$value);
			}
		}
	return $xml->asXML();
	}
//---------- begin function removeCdata ----------
/**
* @describe removes CDATA tags from an xml/xhtml string
* @param xml string
*	xml/xhmtl string
* @return string
*	xml/xhtml
* @usage $str=removeCdata($str);
*/
function removeCdata($xhtml=''){
	if(is_array($xhtml)){return $xhtml;}
	//$xhtml = preg_replace('(<\!\[CDATA\[(.|\n)*\]\]>)', '', $xhtml);
	$xhtml = str_replace(array('<![CDATA[',']]>') , '', $xhtml);
	return htmlspecialchars_decode($xhtml);
	}
//---------- begin function removeComments ----------
/**
* @describe removes html comment tags, and the values inside them, from a string
* @param str string
* @return string
* @usage $str=removeComments($str);
*/
function removeComments($str=''){
	$str = preg_replace('/\<\!--.+?--\>/','',$str);
	return $str;
}
//---------- begin function removePHPComments ----------
/**
* @describe removes PHP comments
* @param str string
* @return string
* @usage $str=removePHPComments($str);
*/
function removePHPComments($str=''){
	$lines=preg_split('/[\r\n]+/',$str);
	$comments=array();
	foreach($lines as $i=>$line){
		$tline=trim($line);
		if(stringBeginsWith($tline,"//")){
			unset($lines[$i]);
		}
		elseif(stringBeginsWith($tline,"/*") && stringEndsWith($tline,"*/")){
			unset($lines[$i]);
		}
		elseif(stringBeginsWith($tline,"/*")){
			$comments[]=$i;
		}
		elseif(count($comments)){
			$comments[]=$i;
		}
		if(count($comments) && stringEndsWith($tline,"*/")){
			$comments[]=$i;
			foreach($comments as $x){
				unset($lines[$x]);
			}
			$comments=array();
		}
	}
	return implode(PHP_EOL,$lines);
}	
//---------- begin function removeHtmlTags ----------
/**
* @describe removes html tags from a string
* @param str string
* @return string
* @usage $str=removeHtmlTags($str);
*/
function removeHtmlTags($str='',$tags=array()){
	foreach($tags as $tag){
		$regex='/<'.$tag.'.*?>.*?<\/'.$tag.'>/';
		$str = preg_replace($regex,'',$str);
		}
	return $str;
	}
//---------- begin function removeHtml ----------
/**
* @describe wrapper for strip_tags
* @param str string
* @return string
* @usage $str=removeHtml($str);
*/
function removeHtml($str=''){
	if(is_array($str)){$str=implode(' ',$str);}
	return strip_tags($str);
	}
//---------- begin function request2XML ----------
/**
* @describe creates an xml string from the $_REQUEST and $_SERVER arrays to store with a record so that you have a good idea about the environment when the record was created
* @param req array - optional - the $_REQUEST array to parse
* @param srv array - optional - the $_SERVER array to parse
* @return string xml string
* @usage $xml=request2XML();
*/
function request2XML($request=array(),$server=array()){
	global $USER;
	$xml=xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
    $xml .= "<request>\n";
    //Server
    $xml .= "	<server>\n";
    if(!isset($server) || !count($server)){
		$keys=array('HTTP_HOST','REMOTE_ADDR','HTTP_REFERER','SCRIPT_URL','HTTP_USER_AGENT','SERVER_ADDR');
		foreach($keys as $key){
			if(!isset($_SERVER[$key])){continue;}
			$lkey=strtolower($key);
			$val=xmlEncodeCDATA($_SERVER[$key]);
			$xml .= "		<{$lkey}>{$val}</{$lkey}>\n";
			}
		$xml .= "		<timestamp>".time()."</timestamp>\n";
		}
	else{
		$server['edit_timestamp']=time();
		foreach($server as $field=>$val){
			$key=strtolower($field);
			$val=xmlEncodeCDATA($val);
			$xml .= "		<{$key}>{$val}</{$key}>\n";
        	}
    	}
	$xml .= "	</server>\n";
	//Data
	$xml .= "	<data>\n";
	//add request vals as data
    foreach($request as $key=>$val) {
		if(preg_match('/^\_/',$key)){$key="u_{$key}";}
		if(preg_match('/^\-/',$key)){$key="d_{$key}";}
		if(preg_match('/^(PHPSESSID|SESSIONID|x|y)$/i',$key)){continue;}
		if(is_array($val)){
			$xml .= "		<{$key}>\n";
			foreach($val as $item){
				if(is_array($item)){continue;}
				$item=xmlEncodeCDATA(trim($item));
				$xml .= "			<values>{$item}</values>\n";
				}
			$xml .= "		</{$key}>\n";
        	}
		else{
			$val=xmlEncodeCDATA($val);
			$xml .= "		<{$key}>{$val}</{$key}>\n";
			}
    	}
    $xml .= "	</data>\n";
    //User
    if(isUser()){
		$xml .= "	<user>\n";
		//add request vals as data
	    foreach($USER as $key=>$val) {
			if(is_array($val)){continue;}
			if(!strlen(trim($val))){continue;}
			if(preg_match('/^(apikey|password|_auth)$/i',$key)){continue;}
			if(preg_match('/^\_/',$key)){$key="u_{$key}";}
			if(preg_match('/^\-/',$key)){$key="d_{$key}";}

			if(is_array($val)){
				$xml .= "		<{$key}>\n";
				foreach($val as $item){
					$item=xmlEncodeCDATA(trim($item));
					$xml .= "			<values>{$item}</values>\n";
					}
				$xml .= "		</{$key}>\n";
	        	}
			else{
				$val=xmlEncodeCDATA($val);
				$xml .= "		<{$key}>{$val}</{$key}>\n";
				}
	    	}
	    $xml .= "	</user>\n";
	    }
    $xml .= "</request>\n";
    return $xml;
	}
//---------- begin function request2XMLSimple ----------
/**
* @describe creates an xml string from the $_REQUEST array to store with a record so that you have a good idea about the environment when the record was created
* @param req array - optional - the $_REQUEST array to parse
* @return string xml string
* @usage $xml=request2XMLSimple();
*/
function request2XMLSimple($request=array()){
	global $USER;
	$xml=xmlHeader(array('version'=>'1.0','encoding'=>'utf-8'));
    $xml .= "<request>\n";
	//add request vals as data
    foreach($request as $key=>$val) {
		if(!is_array($val) && !strlen(trim($val))){continue;}
		if(preg_match('/^[\_\-]/',$key)){continue;}
		if(preg_match('/^(GUID|PHPSESSID|SESSIONID|x|y|AjaxRequestUniqueId)$/i',$key)){continue;}
		if(is_array($val)){
			if(count($val)==1){
				foreach($val as $item){
					if(is_array($item)){continue;}
					$item=xmlEncodeCDATA(trim($item));
					$xml .= "		<{$key}>{$item}</{$key}>\n";
					break;
					}
            	}
            else{
				$xml .= "		<{$key}>\n";
				foreach($val as $item){
					if(is_array($item)){continue;}
					$item=xmlEncodeCDATA(trim($item));
					$xml .= "			<values>{$item}</values>\n";
					}
				$xml .= "		</{$key}>\n";
				}
        	}
		else{
			$val=xmlEncodeCDATA($val);
			$xml .= "		<{$key}>{$val}</{$key}>\n";
			}
    	}
    $xml .= "</request>\n";
    return $xml;
	}
//---------- begin function requestValue---------------------------------------
/**
* @describe returns the $_REQUEST value of the field specified.  same as $_REQUEST[$key]
* @param field string
*	name of the field you wish to return
* @return str value
*	returns the $_REQUEST value of the field specified or null
* @usage
*	requestValue('name');
*/
function requestValue($k){
	if(isset($_REQUEST[$k])){return $_REQUEST[$k];}
	return null;
	}
//---------- begin function trimRequestArray
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function trimRequestArray($request=array()){
	if(!isset($request) || !count($request)){$request=$_REQUEST;}
	foreach($request as $key=>$val) {
		//skip fields that begin with a underscore
		if(preg_match('/^\_/',$key)){unset($request[$key]);}
		//skip fields that begin with a dash - xml tags cannot start with a dash anyway
		if(preg_match('/^\-/',$key)){unset($request[$key]);}
		//skip session id variables
		if(preg_match('/(PHPSESSID|SESSIONID)/',$key)){unset($request[$key]);}
		//skip image x/y values and GUID
		if(preg_match('/^(x|y|GUID)$/',$key)){unset($request[$key]);}
    	}
    return $request;
	}
//---------- begin function truncateWords
/**
* @describe truncates a string of words (sentence) by $maxlen but does not leave partial words
* @param wordstr string
*	the string or sentence you want to turncate
* @param maxlen int
*	the maximum length
* @return
*	a string
* @usage
* $str=truncateWords($longstr,300);
*/
function truncateWords($wordstr,$maxlen,$dots=0){
	$words=preg_split('/\ /',$wordstr);
	if(!is_array($words)){return $wordstr;}
	$rtn='';
	foreach($words as $word){
		if(strlen("{$rtn}  {$word}") > $maxlen){break;}
		$rtn .= " {$word}";
		$rtn=trim($rtn);
		if(strlen($rtn) > $maxlen){break;}
	}
	$rtn=trim($rtn);
	if($dots && !preg_match('/(\.|\?|\!)$/',$rtn)){$rtn .= "...";}
	return $rtn;
}
//---------- begin function splitWords
/**
* @describe splits a string of words (sentence) by $maxlen but does not leave partial words
* @param wordstr string
*	the string or sentence you want to turncate
* @param maxlen int
*	the maximum length
* @return array
* @usage
* $word_groups=splitWords($str,500);
*/
function splitWords($sentence,$maxlen){
	//return strlen($sentence);
	$words=preg_split('/\ +/',$sentence);
	if(!is_array($words)){return array($sentence);}
	$rtn='';
	$parts=array();
	$wordcount=count($words);
	for($x=0;$x<$wordcount;$x++){
		$word=$words[$x];
		$rtn .= rtrim(" {$word}");
		$len=strlen($rtn);
		$n=$x+1;
		if(isset($words[$n])){
			$len+=strlen(" {$words[$n]}");
		}
		if($len >= $maxlen){
        	$parts[]=trim($rtn);
        	$rtn='';
		}
	}
	if(strlen(trim($rtn))){
    	$parts[]=trim($rtn);
	}
	return $parts;
}
//---------- begin function sendMail---------------------------------------
/**
* @describe sends an email and returns null on success or the error string
* @param params array
*	to - the email address to send the email to.  For multiple recepients, separate emails by commas or semi-colons
*	from - the email address to send the email from
*	[cc] - optional email address to carbon copy the email to. For multiple recepients, separate emails by commas or semi-colons
*	[bcc] - optional email address to blind carbon copy the email to. For multiple recepients, separate emails by commas or semi-colons
*	[reply-to] - the email address to set as the reply-to address
*	subject - the subject of the email
*	message - the body of the email
*	[attach] - an array of files (full path required) to attach to the message
* @return str value
*	returns the error message or null on success
* @usage
*	$errmsg=sendMail(array(
*		'to'		=> 'john@doe.com',
*		'from'		=> 'jane@doe.com',
*		'subject'	=> 'When will you be home?',
*		'message'	=> 'Here is the document you requested',
*		'attach'	=> array('/var/www/doument.doc')
*	));
*/
function sendMail($params=array()){
	/*
		$errmsg=sendMail(array(
			'to'=>'a@b.com','from'=>'you@yourdomain.com','subject'=>'test subject','message'=>$html
			'attach'=>array($file,$file2...)
			));
	*/
	global $CONFIG;
	if(isset($CONFIG['phpmailer'])){
        loadExtras('phpmailer');
		return phpmailerSendMail($params);
	}
	//check for CONFIG settings?
    $flds=array('smtp','smtpport','smtpuser','smtppass');
    foreach($flds as $fld){
    	if(!isset($params[$fld]) && isset($CONFIG[$fld])){$params[$fld]=$CONFIG[$fld];}
    }
    if(!isset($params['from']) && isset($CONFIG['email_from'])){$params['from']=$CONFIG['email_from'];}
    if(!isset($params['encrypt']) && isset($CONFIG['email_encrypt'])){$params['encrypt']=$CONFIG['email_encrypt'];}
    if(!isset($params['-timeout']) && isset($CONFIG['email_timeout'])){$params['-timeout']=$CONFIG['email_timeout'];}

	$attachincluded=array();
	/* Required options */
	$reqopts=array('to','from','subject','message');
	foreach($reqopts as $key){
		if(!isset($params[$key]) || strlen($params[$key])==0){return "sendMail Error - missing required parameter: ". $key;}
    	}
    //parse the to and from:  Steve Lloyd <slloyd@timequest.org>
    $headers=array();
    $flds=array('to','from','cc','bcc','reply-to');
    foreach($flds as $fld){
		if(isset($params[$fld])){
		    if(preg_match('/(.+?)\<(.+?)\>/',$params[$fld],$ematch)){
				array_push($headers,ucfirst($fld).': ' . $params[$fld]);
				$params[$fld]=$ematch['2'];
		    }
		    elseif($fld != 'to'){array_push($headers,ucfirst($fld).': ' . $params[$fld]);}
		}
	}
    // verify that to and from are valid email addresses
    if(!isEmail($params['to'])){return "sendMail Error - invalid emailaddress: " . $params['to'];}
    if(!isEmail($params['from'])){return "sendMail Error - invalid emailaddress: " . $params['from'];}
    //add reply-to to the headers
    if(isset($params['reply-to']) && isEmail($params['reply-to'])){
		array_push($headers,"Reply-To: {$params['reply-to']}");
	}
	else{
    	array_push($headers,"Reply-To: {$params['from']}");
	}
    //add X-Mailer with current php verson
    array_push($headers,'X-Mailer: WaSQL On ' . $_SERVER['UNIQUE_HOST']);
	//add language
    array_push($headers,'Content-Language: en-us');
    //check for custoom headers
    if(isset($params['-headers']) && is_array($params['-headers'])){
		foreach($params['-headers'] as $header){
			array_push($headers,trim($header));
		}
	}
	/* If there are no attachments and the message is text, just send it */
	$multi=0;
	if(isset($params['attach']) && is_array($params['attach']) && count($params['attach']) > 0){$multi++;}
	if(isXML($params['message'])){$multi++;}
	if($multi==0){
		//simple text message
		$header=implode("\n",$headers);
		$tos=preg_split('/[\,\;\ ]+/',$params['to']);
		$errors=array();
		$errors=array();
		foreach($tos as $to){
			$to=trim($to);
			if(!strlen($to)){continue;}
			@trigger_error("");
			if(mail($to, $params['subject'], $params['message'],$header)){
				continue;
		    }
			else{
				$e=error_get_last();
				if($e['message'] !== ''){$errors[]= " - failed to send to {$to}. Subject: {$params['subject']}. Error: [" . $e['message'] ."]\n";}
				else{
					$errors[]= " - failed to send to {$to}. Subject: {$params['subject']}";
				}
		    }
		}
		if(count($errors)){
			return "sendMail Errors:<br>\n" . implode("<br>\n",$errors);
		}
		return null;
    }
	// Generate a boundary string
  	$semi_rand = md5(time());
  	$related_boundary = "==Multipart_Related_{$semi_rand}x";
  	$alternative_boundary = "==Multipart_Alternative_{$semi_rand}y";
  	// Add the headers for a multipart
  	array_push($headers,
	  	"MIME-Version: 1.0",
    	"Content-Type: multipart/related;",
    	" boundary=\"{$related_boundary}\""
	);
	// text and html message
	if(!isset($params['message_text'])){
		$text=removeHtmlTags($params['message'],array('style','head'));
		if(preg_match('/<body.*?>(.+)<\/body>/i',$text,$mmatch)){
			$params['message_text']=$mmatch[1];
		}
		else{$params['message_text']=removeHtml($text);}
	}
	$message = "This is a multi-part message in MIME format.\n\n" .
		"--{$related_boundary}\n" .
		"Content-Type: multipart/alternative;\n" .
		" boundary=\"{$alternative_boundary}\"\n\n\n".
		"--{$alternative_boundary}\n" .
        "Content-Type: text/plain; charset=\"UTF-8\"\n" .
        "Content-Transfer-Encoding: 7bit\n\n" .
        encodeData($params['message_text'],'7bit') . "\n\n" .
        "--{$alternative_boundary}\n" .
        "Content-Type: text/html; charset=\"UTF-8\"\n" .
        "Content-Transfer-Encoding: quoted-printable\n\n" .
        encodeData($params['message'],'qp') . "\n\n";
    //end text and html message boundary
    $message .= "--{$alternative_boundary}--\n";
	//add attachments if any
	if(is_array($params['attach']) && count($params['attach']) > 0){
		foreach($params['attach'] as $file){
			if(is_file($file) && is_readable($file)){
  				$data = file_get_contents($file,FILE_BINARY);
  				$cid=getFileContentId($file);
  				if(strlen($cid) && !isset($attachincluded[$cid])){
					$attachincluded[$cid]+=1;
	  				// Add file attachment to the message
	  				$filemessage = "--{$related_boundary}\n" .
	              		"Content-Type: ".getFileContentType($file).";\n" .
	              		" name=\"".getFileName($file)."\"\n" .
	              		"Content-Transfer-Encoding: base64\n" .
	              		"Content-ID: <" . $cid . ">\n\n" .
	              		encodeData($data,'base64') . "\n\n";
	              	$message .= $filemessage;
					}
				}
			else{return "sendMail Error - attachment not found: " . $file;}
        	}
    	}
	//end the message
	$message .= "--{$related_boundary}--\n";
	//return $message if debug
	if(isset($params['-debug']) && $params['-debug']==1){
		return $message;
    	}
	//send the message
	$header=implode("\n",$headers);
	$tos=preg_split('/[\,\;\ ]+/',$params['to']);
	$errors=array();
	foreach($tos as $to){
		$to=trim($to);
		if(!strlen($to)){continue;}
		@trigger_error("");
		if(mail($to, $params['subject'], $message,$header)){
			continue;
	        }
		else{
			$e=error_get_last();
			if($e['message']!==''){$errors[]= " - failed to send to {$to}. Subject: {$params['subject']}. Error: [" . $e['message'] ."]\n";}
			else{
				$errors[]= " - failed to send to {$to}. Subject: {$params['subject']}";
				}
	        }
	    }
	if(count($errors)){return "sendMail Errors:<br>\n" . implode("<br>\n",$errors);}
	return null;
	}
//---------- begin function sendSMTPMail
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function sendSMTPMail($params=array()){
	//verify required fields
	$rtn=array();
	//check for default smtp setting in conf
	global $CONFIG;
	$fields=array('smtp','smtpuser','smtppass');
	foreach($fields as $field){
		if(!isset($params[$field]) && isset($CONFIG[$field])){$params[$field]=$CONFIG[$field];}
    	}
    //set defaults
    if(!isset($params['auth'])){
		if(isset($params['smtpuser'])){$params['auth']=1;}
		else{$params['auth']=0;}
		}
    if(!isset($params['timeout'])){$params['timeout']=10;}
    if(!isset($params['debug'])){$params['debug']=false;}
	$attachincluded=array();
    $rtn['params']=$params;
    //validate required params are set
    $fields=array('smtp','to','from','subject','message');
    foreach($fields as $field){
		if(!isset($params[$field])){$rtn['error']="No {$field}";return $rtn;}
    	}
	/*	header info	*/
	$headers = array(
		'Subject'	=> $params['subject'],
		'From'		=> $params['from'],
		'To'		=> $params['to']
		);
	if(isset($params['reply'])){$headers['reply']=$params['reply'];}
	if(!isset($params['message_text'])){
		$text=removeHtmlTags($params['message'],array('style','head'));
		if(preg_match('/<body.*?>(.+)<\/body>/i',$text,$mmatch)){
			$params['message_text']=$mmatch[1];
			}
		else{$params['message_text']=removeHtml($text);}
		}
	include_once('Mail.php');
	include_once('Mail/mime.php');
	$mime = new Mail_mime();
	$mime->setTXTBody($params['message_text']);
	$mime->setHTMLBody($params['message']);
	if(is_array($params['attach']) && count($params['attach']) > 0){
		foreach($params['attach'] as $file){
			if(is_file($file)){
				$ctype=getFileContentType($file);
				$cid=getFileContentId($file);
  				if(strlen($cid) && !isset($attachincluded[$cid])){
					$attachincluded[$cid]+=1;
					//$name=getFileName($file);
					if(isImageFile($file)){
						$mime->addHTMLImage($file, $ctype);
						}
					else{
						$mime->addAttachment($file, $ctype);
                    	}
					}
				}
			else{return "sendSMTPMail Error - attachment not found: " . $file;}
        	}
    	}
	$body = $mime->get();
	$headers = $mime->headers($headers);
	$rtn['headers']=$headers;
	/*	SMTP params	*/
	$smtp = array(
		'host'		=> $params['smtp'],
		'auth'		=> $params['auth'],
		'localhost'	=> $_SERVER['SERVER_NAME'],
		'username'	=> $params['smtpuser'],
		'password'	=> $params['smtppass'],
		'timeout'	=> $params['timeout'],
		'debug'		=> $params['debug'],
		);
 	$mail =& Mail::factory('smtp', $smtp);
 	$res = $mail->send($params['to'],$headers,$body);
 	//returns TRUE or a PEAR_Error object on failure.
 	if($res === true){$rtn['sent']=1;}
	else{
		// error
		$rtn['sent']=0;
		$rtn['error']=$res->message;
		$rtn['error_code']=$res->code;
		$rtn['error_level']=$res->level;
		}
	return $rtn;
	}
//---------- begin function setFileContents--------------------
/**
* @describe wrapper for file_put_contents
* @param file string - file to write
* @param data string - data to write
* @param [append] - set to true to append
* @return boolean
* @usage $ok=setFileContents($file,$data);
*/
function setFileContents($file,$data,$append=0){
	if($append && is_file($file)){
		try{
			return file_put_contents($file,$data,FILE_APPEND);
		}
		catch (Exception $e){
        	return $e->faultstring;
        }
	}
	try{
		return file_put_contents($file,$data);
	}
	catch (Exception $e){
    	return $e->faultstring;
    }
}
//---------- begin function appendFileContents--------------------
/**
* @describe wrapper for file_put_contents with append
* @param file string - file to write
* @param data string - data to write
* @return boolean
* @usage $ok=setFileContents($file,$data);
*/
function appendFileContents($file,$data){
	return file_put_contents($file,$data,FILE_APPEND);
}
//---------- begin function showErrors
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function showErrors(){
	error_reporting(E_ERROR | E_PARSE);
	return;
	}
//---------- begin function showAllErrors
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function showAllErrors(){
	error_reporting(E_ALL & ~E_NOTICE);
	return;
	}
//---------- begin function soap2Array--------------------
/**
* @describe returns SOAP request data as an XML array
* @param soapstr string - SOAP string
* @return array - XML array
* @usage $xml_arrray=soap2Array($soapstr);
*/
function soap2Array($soapstr){
	/* SimpleXML seems to have problems with the colon ":" in the <xxx:yyy> response tags, so take them out */
	$soapstr=soap2Soap($soapstr);
	return xml2Array($soapstr);
	}
//---------- begin function soap2Soap
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function soap2Soap($soapstr){
	$soapstr=preg_replace('/<soap12\:/ism','<soap:',$soapstr);
	$soapstr=preg_replace('/<\/soap12\:/ism','</soap:',$soapstr);
	$soapstr=preg_replace('/<soap\-env\:/ism','<soap:',$soapstr);
	$soapstr=preg_replace('/<\/soap\-env\:/ism','</soap:',$soapstr);
	$soapstr = preg_replace('|<([/\w]+)(:)|m','<$1',$soapstr);
	$soapstr = preg_replace('|(\w+)(:)(\w+=\")|m','$1$3',$soapstr);
	return $soapstr;
}
//---------- begin function soap2XML--------------------
/**
* @describe
*	returns SOAP request data as an XML object
*	SimpleXML seems to have problems with the colon ":" in the <xxx:yyy> response tags, so this takes them out
* @param soapstr string SOAP request
* @return xml string
* @usage $xmsoap2XML($soapstr);
*/
function soap2XML($soapstr){
	/* SimpleXML seems to have problems with the colon ":" in the <xxx:yyy> response tags, so take them out */
	$soapstr=soap2Soap($soapstr);
	return simplexml_load_string($soapstr);
	}

//---------- begin function buildImage
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function swfChart($recs=array(),$params=array(),$crc=''){
  	if(!is_array($recs) || !count($recs) || !isset($recs[0]['xval']) || !isset($recs[0]['xval'])){return 'Missing or invalid Dataset';}
  	//build inline data and add any attributes passed in
  	$total=0;
  	foreach($recs as $rec){$total+=$rec['yval'];}
  	if(!strlen($crc)){$crc=encodeCRC(json_encode(array($recs,$params),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));}
  	if(!isset($params['showNames'])){$params['showNames']=1;}
  	if(!isset($params['decimalPrecision'])){$params['decimalPrecision']=0;}
  	if(!isset($params['subcaption'])){$params['subcaption']="Total: {$total}";}
  	$data = "<graph";
  	$atts=array(
    //Background Properties
	    'bgColor','bgAlpha','bgSWF',
	    //canvas properties
	    'canvasBgColor','canvasBgAlpha','canvasBorderColor','canvasBorderThickness',
	    //Chart and Axis Titles
	    'caption','subCaption','xAxisName','yAxisName',
	    //Chart Numerical limits - otherwise they are calculated based on the data
	    'yAxisMinValue','yAxisMaxValue',
	    //Generic Properties
	    'showNames','showValues','showLimits','rotateNames','annimation','showLegend',
	    //Area Properties
	    'showAreaBorder','areaBorderThickness','areaBorderColor','areaAlpha',
	    //font Properties
	    'baseFont','baseFontSize','baseFontColor','outCnvBaseFont','outCnvBaseFontSize','outCnvBaseFontColor',
	    //number formatting
	    'numberPrefix','numberSuffix','formatNumber','formatNumberScale','decimalPrecision',
	    //hover
	    'showHoverCap',
	    //chart margins
	    'chartLeftMargin','chartRightMargin','chartTopMargin','chartBottomMargin'
    );
  	foreach($atts as $att){
    	if(isset($params[$att])){$data .= " {$att}='{$params[$att]}'";}
    }
  	$data .= ">";
  	foreach($recs as $rec){
		if(isset($rec['color'])){
      		$data .= "<set name='{$rec['xval']}' value='{$rec['yval']}' color='".$rec['color']."' >";
      	}
    	elseif(isset($params['color'])){
      		$data .= "<set name='{$rec['xval']}' value='{$rec['yval']}' color='".$params['color']."' >";
      	}
    	else{
      		$data .= "<set name='{$rec['xval']}' value='{$rec['yval']}' >";
        }
	}
  	$data .= "</graph>";
  	//Build the javascript that calls the FusionChart
  	$width=count($recs)*42;
  	if(!isset($params['width'])){$params['width']=$width;}
  	if(!isset($params['height'])){$params['height']=225;}
  	$swf="/wfiles/charts/FCF_Column2D.swf";
  	if(isset($params['type'])){
    	switch(strtolower($params['type'])){
      		case '3d':$swf="/wfiles/charts/FCF_Column3D.swf";break;
      		case 'line':$swf="/wfiles/charts/FCF_Line.swf";break;
      		case 'pie':$swf="/wfiles/charts/FCF_Pie2D.swf";break;
      		case 'area':$swf="/wfiles/charts/FCF_Area2D.swf";break;
      		default:$swf="/wfiles/charts/FCF_Column2D.swf";break;
          	}
      	}
  	$rtn='';
  	$rtn .= '<div id="chart'.$crc.'"></div>'.PHP_EOL;
  	$rtn .= '<script type="text/javascript">'.PHP_EOL;
  	$rtn .= '   var chart1 = new FusionCharts("'.$swf.'", "ChID'.$crc.'", "'.$params['width'].'", "'.$params['height'].'", "0", "1");'.PHP_EOL;
  	$rtn .= '   chart1.setDataXML("'.$data.'");'.PHP_EOL;
  	$rtn .= '   chart1.render("chart'.$crc.'");'.PHP_EOL;
  	$rtn .= '</script>'.PHP_EOL;
  	return $rtn;
  	}
//---------- begin function wasqlMail---------------------------------------
/**
* @describe sends an email and returns null on success or the error string - uses wasqlmail.pl to send instead of PHP mail
* @param params array
*	to - the email address to send the email to.  For multiple recepients, separate emails by commas or semi-colons
*	from - the email address to send the email from
*	[cc] - optional email address to carbon copy the email to. For multiple recepients, separate emails by commas or semi-colons
*	[bcc] - optional email address to blind carbon copy the email to. For multiple recepients, separate emails by commas or semi-colons
*	[reply-to] - the email address to set as the reply-to address
*	subject - the subject of the email
*	message - the body of the email
*	[attach] - an array of files (full path required) to attach to the message
* @return str value
*	returns the error message or null on success
* @usage
*	$errmsg=wasqlMail(array(
*		'to'		=> 'john@doe.com',
*		'from'		=> 'jane@doe.com',
*		'subject'	=> 'When will you be home?',
*		'message'	=> 'Here is the document you requested',
*		'attach'	=> array('/var/www/doument.doc')
*	));
*/
function wasqlMail($opts=array(),$debug=0){
	global $CONFIG;
	if(!isset($opts['smtp']) && isset($CONFIG['smtp'])){
		$opts['smtp']=$CONFIG['smtp'];
		if(isset($CONFIG['smtpuser'])){$opts['smtpuser']=$CONFIG['smtpuser'];}
		if(isset($CONFIG['smtppass'])){$opts['smtppass']=$CONFIG['smtppass'];}
		}

	if(!isset($opts['from']) && isset($CONFIG['email_from'])){
		$opts['from']=$CONFIG['email_from'];
		}
	if(!isset($opts['url']) && isset($CONFIG['wasqlmail'])){
		$opts['url']=$CONFIG['wasqlmail'];
		}
	if(isset($opts['attach'])){
		if(is_array($opts['attach'])){$opts['attach']=implode(';',$opts['attach']);}
	}
	//defaults
	if(!isset($opts['url'])){$opts['url']='http://'.$_SERVER['HTTP_HOST'].'/cgi-bin/wasqlmail.pl';}
	if(!isset($opts['from'])){$opts['from']='no-reply@'.$_SERVER['UNIQUE_HOST'];}
	$post=postURL($opts['url'],$opts);
	if($debug==1){return $post;}
	if(is_array($post)){
		if(preg_match('/<success>/',$post['body'])){return true;}
		return 1;
		}
	return printValue($post);
	}
//---------- begin function wget--------------------
/**
* @describe returns url contents and writes the file locally - like linux wget
* @param url string - url of file
* @param localfile string - path and file to write locally
* @param array optional parameters
* *	[-authuser] - auth username
*	[-authpass] - auth password
* @return string - return url contents
* @usage 
* 	list($localfile,$path)=wget($url,$localfile);
* 	or
* 	$content=wget($url);
*/
function wget($url,$localfile='',$params=array()){
	set_time_limit(0);
	if(!strlen($localfile)){
		$content=1;
		$path=getWasqlTempPath();
		$ext=getFileExtension($url);
		$localfile=$path.'/'.sha1($url).'.'.$ext;
	}
	else{
		$content=0;
		$path=getFilePath($localfile);
		if(!is_dir($path)){buildDir($path);}
	}
	//This is the file where we save the    information
	$fp = fopen ($localfile, 'w+');
	if(!is_resource($fp)){
		return array("ERROR","Unable to open {$localfile}");
	}
	//Here is the file we are downloading, replace spaces with %20
	$ch = curl_init(str_replace(" ","%20",$url));
	// make sure to set timeout to a high enough value
	// if this is too low the download will be interrupted
	curl_setopt($ch, CURLOPT_TIMEOUT, 1200);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	//in a protected directory
	if(isset($params['-authuser']) && strlen($params['-authuser']) && isset($params['-authpass']) && strlen($params['-authpass'])){
		//try all possible authentication methods
		//curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "{$params['-authuser']}:{$params['-authpass']}");
	}
	// write curl response to file
	curl_setopt($ch, CURLOPT_FILE, $fp); 
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	// get curl response
	curl_exec($ch); 
	// Get the HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	fclose($fp);
	if($content==1){
		return file_get_contents($localfile);
	}
	return array($localfile,$path,$http_code);
}
//---------- begin function getRemoteImage--------------------
/**
* @describe get an image on a remote website and save it locally to target file
* @param url string - url of image to get
* @param target string - path and filename to write locally
* @return null
* @usage getRemoteImage($url,$targetFile);
*/
function getRemoteImage($remote_url, $target){
	$path=getFilePath($target);
	if(!is_dir($path)){buildDir($path);}
	elseif(is_file($target)){unlink($target);}
	$ch = curl_init($remote_url);
	$fp = fopen($target, "wb");
	// set URL and other appropriate options
	$options = array(
		CURLOPT_FILE => $fp,
		CURLOPT_HEADER => 0,
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_TIMEOUT => 600); // 5 minute timeout
	curl_setopt_array($ch, $options);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
}
//---------- begin function writeJavascript
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function writeJavascript($js=''){
	$rtn='';
	$rtn .= '<script type="text/javascript">'.PHP_EOL;
	$rtn .= '	'. $js .PHP_EOL;
	$rtn .= '</script>'.PHP_EOL;
	return $rtn;
}
//---------- begin function sortArrayByKey--------------------
/**
* @describe sorts and array by keys
* @param arr array - array to sort
* @param col string - column name to sort by
* @param sort constant - SORT_ASC or SORT_DESC
* @return array
* @usage $newarr=sortArrayByKey($arr1,'name',SORT_DESC);
*/
function sortArrayByKey($recs=array(),$key='',$sort=SORT_ASC){
	if(!strlen($key)){return $recs;}
	return sortArrayByKeys($recs, array($key=>$sort));
	}
//---------- begin function sortArrayByKeys--------------------
/**
* @describe sorts and array by keys
* @param arr array - array to sort
* @param cols array - columns to sort by
* @return array
* @usage $newarr=sortArrayByKeys($arr1, array('name'=>SORT_DESC, 'cat'=>SORT_ASC));
*/
function sortArrayByKeys($array=array(), $cols=array()){
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { 
        	if(is_array($row[$col]) || is_object($row[$col])){
        		$row[$col]=encodeJSON($row[$col]);
        	}
        	$colarr[$col]['_'.$k] = strtolower($row[$col]); 
        }
    }
    $eval = 'array_multisort(';
    foreach ($cols as $col => $order) {
        $eval .= '$colarr[\''.$col.'\'],'.$order.',';
    }
    $eval = substr($eval,0,-1).');';
    eval($eval);
    $ret = array();
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            $k = substr($k,1);
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
    }
    return $ret;
}
//---------- begin function sortArrayByLength--------------------
/**
* @describe sorts and array by length
* @param arr array - array to sort
* @return array
* @usage $newarr=sortArrayByLength($arr1);
*/
function sortArrayByLength($arr=array()){
	usort($arr,'sortArrayByLengthCmp');
	return $arr;
	}
//---------- begin function sortArrayByLengthCmp
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function sortArrayByLengthCmp($a,$b){
	//used by sortArrayByLength function
	if($a == $b) return 0;
	return (strlen($a) > strlen($b) ? -1 : 1);
	}
//---------- begin function buildImage
/**
* @depreciated - same as trim
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function strip($str=''){
	$str = preg_replace('/^[\r\n\s\t]+/','',$str);
	$str = preg_replace('/[\r\n\s\t]+$/','',$str);
	return $str;
	}
//---------- begin function strip_tags_content
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function strip_tags_content($text, $tags = '', $invert = FALSE) {
	preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
	$tags = array_unique($tags[1]);
	if(is_array($tags) AND count($tags) > 0) {
    	if($invert == FALSE) {
      	return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
    	}
    else{
    	return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
    	}
  	}
	elseif($invert == FALSE) {
		return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
  		}
	return $text;
	}
//---------- begin function underMaintenance--------------------
/**
* @describe returns a under maintenance banner and exits
* @param note string - note to display in banner
* @return exits
* @usage underMaintenance('Hope to be back soon!');
*/
function underMaintenance($note=''){
	//@usage returns a under maintenance banner
	$rtn='';
	$rtn .= '<html>'.PHP_EOL;
	$rtn .= '<head>'.PHP_EOL;
	$rtn .= '	<link type="text/css" rel="stylesheet" href="/wfiles/min/index.php?g=w_Css3" >'.PHP_EOL;
	$rtn .= ' 	<link type="text/css" rel="stylesheet" href="/wfiles/css/print.css" media="print" >'.PHP_EOL;
	$rtn .= '	<script type="text/javascript" src="/wfiles/min/index.php?g=w_Js3"></script>'.PHP_EOL;
	$rtn .= '</head>'.PHP_EOL;
	$rtn .= '<body>'.PHP_EOL;
	$rtn .= '<div align="center">'.PHP_EOL;
	$rtn .= '	<div style="width:600px;border:1px solid #000">'.PHP_EOL;
	$rtn .= '		<div style="background:url(/wfiles/back_blue.jpg);height:100px;">'.PHP_EOL;
	$rtn .= '			<div style="padding-top:15px;font-size:30pt;color:#FFF;">Under Maintenance</div>'.PHP_EOL;
	$rtn .= '			<div style="padding-top:2px;font-size:15pt;color:#FFF;">' . $_SERVER['HTTP_HOST'] . '</div>'.PHP_EOL;
	$rtn .= '		</div>'.PHP_EOL;
	if(strlen($note)){
		$rtn .= '		<div style="padding:10px;font-size:16pt;" class="w_align_left w_bold w_dblue">'.$note.'</div>'.PHP_EOL;
    }
    else{
    	$rtn .= '		<div class="w_align_left" style="padding:10px;font-size:15pt;">Our site is currently undergoing maintenance to upgrade our systems in order to better serve you.</div>'.PHP_EOL;
		$rtn .= '		<div class="w_align_left" style="padding:10px;font-size:12pt;">We apologize for any inconvenience during this short outage and thank you in advance for your patience and understanding.</div>'.PHP_EOL;
    }
	$rtn .= '	</div>'.PHP_EOL;
	$rtn .= '</div>'.PHP_EOL;
	$rtn .= '</body></html>'.PHP_EOL;
	return $rtn;
	}
//---------- begin function underConstruction--------------------
/**
* @describe returns a under construction banner and exits
* @param note string - note to display in banner
* @return exits
* @usage underConstruction('Hope to be up soon!');
*/
function underConstruction($note='',$login=0){
	//@usage returns a under maintenance banner
	$rtn='';
	$rtn .= '<html>'.PHP_EOL;
	$rtn .= '<head>'.PHP_EOL;
	$rtn .= '	<link type="text/css" rel="stylesheet" href="/wfiles/min/index.php?g=w_Css3" >'.PHP_EOL;
	$rtn .= ' 	<link type="text/css" rel="stylesheet" href="/wfiles/css/print.css" media="print" >'.PHP_EOL;
	$rtn .= '	<script type="text/javascript" src="/wfiles/min/index.php?g=w_Js3"></script>'.PHP_EOL;
	$rtn .= '</head>'.PHP_EOL;
	$rtn .= '<body>'.PHP_EOL;
	$rtn .= '<div align="center">'.PHP_EOL;
	$rtn .= '	<div style="width:600px;border:1px solid #000">'.PHP_EOL;
	$rtn .= '		<div style="background:url(/wfiles/back_blue.jpg);height:100px;">'.PHP_EOL;
	$rtn .= '			<div style="padding-top:15px;font-size:30pt;color:#FFF;">Under Construction</div>'.PHP_EOL;
	$rtn .= '			<div style="padding-top:2px;font-size:15pt;color:#FFF;">' . $_SERVER['HTTP_HOST'] . '</div>'.PHP_EOL;
	$rtn .= '		</div>'.PHP_EOL;
	$rtn .= '		<div class="w_align_left" style="padding:10px;font-size:15pt;">Our site is currently under Construction.</div>'.PHP_EOL;
	$rtn .= '		<div class="w_align_left" style="padding:10px;font-size:12pt;">We hope to have it up soon so check back often! Thank you in advance for your patience and understanding.</div>'.PHP_EOL;
	if(strlen($note)){
		$rtn .= '		<div style="padding:10px;font-size:16pt;" class="w_bold w_dblue w_align_left">'.$note.'</div>'.PHP_EOL;
    	}
    if($login==1){
		$rtn .= userLoginForm();
    	}
	$rtn .= '		<div class="w_align_left" style="padding:10px;font-size:11pt;">Sincerely,<br><br>Customer Service Team</div>'.PHP_EOL;
	$rtn .= '	</div>'.PHP_EOL;
	$rtn .= '</div>'.PHP_EOL;
	$rtn .= '</body></html>'.PHP_EOL;
	return $rtn;
	}
//---------- begin function verboseNumber--------------------
/**
* @describe convert a number to words
* @param number integer
* @return string
* @usage verboseNumber(12);
*/
function verboseNumber($num = false){
    $num = str_replace(array(',', ' '), '' , trim($num));
    if(! $num) {return false;}
    $num = (int) $num;
    $words = array();
    $list1 = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven',
        'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'
    );
    $list2 = array('', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred');
    $list3 = array('', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion', 'sextillion', 'septillion',
        'octillion', 'nonillion', 'decillion', 'undecillion', 'duodecillion', 'tredecillion', 'quattuordecillion',
        'quindecillion', 'sexdecillion', 'septendecillion', 'octodecillion', 'novemdecillion', 'vigintillion'
    );
    $num_length = strlen($num);
    $levels = (int) (($num_length + 2) / 3);
    $max_length = $levels * 3;
    $num = substr('00' . $num, -$max_length);
    $num_levels = str_split($num, 3);
    for ($i = 0; $i < count($num_levels); $i++) {
        $levels--;
        $hundreds = (int) ($num_levels[$i] / 100);
        $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ' ' : '');
        $tens = (int) ($num_levels[$i] % 100);
        $singles = '';
        if ( $tens < 20 ) {
            $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '' );
        } else {
            $tens = (int)($tens / 10);
            $tens = ' ' . $list2[$tens] . ' ';
            $singles = (int) ($num_levels[$i] % 10);
            $singles = ' ' . $list1[$singles] . ' ';
        }
        $words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_levels[$i] ) ) ? ' ' . $list3[$levels] . ' ' : '' );
    } //end for loop
    $commas = count($words);
    if ($commas > 1) {
        $commas = $commas - 1;
    }
    return implode(' ', $words);
}	
//---------- begin function verboseSize--------------------
/**
* @describe given a number of bytes, returns a more readable size
* @param bytes integer - the number of bytes
* @param [format] string - string format to return. Defaults to '%.2f %s'
* @return string
* @usage verboseSize($bytes);
*/
function verboseSize($bytes=0,$format=''){
    $sizes=array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $sizecnt = count($sizes);
    $i=0;
    for ($i=0; $bytes > 1024 && $i < $sizecnt && isset($sizes[$i+1]); $i++){$bytes /= 1024;}
    if(!strlen($format)){$format=$i<3?'%.1f %s':'%.2f %s';}
    $str=sprintf($format, $bytes,$sizes[$i]);
	return $str;
	}
//---------- begin function verboseTime--------------------
/**
* @describe given a number of seconds, returns a more readable time
* @param seconds integer - the number of seconds
* @param [notate] boolean - notation - defaults to false
* @param [nosecs] boolean - include seconds - defaults to false
* @return string
* @usage verboseTime($seconds);
*/
function verboseTime($num=0,$notate=0,$nosecs=0) {
	$years=0;$days=0;$hrs=0;$min=0;$sec=0;
	if($num>31536000){
		$years=intval($num/31536000);
		$num=($num-($years*31536000));
		}
	//1 month = 2629743 seconds
	if($num>2629743){
		$months=intval($num/2629743);
		$num=($num-($months*2629743));
		}
	//1 day = 86400 seconds
	if($num>86400){
		$days=intval($num/86400);
		$num=($num-($days*86400));
		}
	if($num>3600){
		$hrs=intval($num/3600);
		$num=($num-($hrs*3600));
		}
	if($num>60){
		$min=intval($num/60);
		$num=($num-($min*60));
		}
	$sec=intval($num);
	$string='';
	if($notate){
        if(!$hrs){$hrs='00';}
		if(!$min){$min='00';}
		if(!$sec){$sec='00';}
		if(strlen($hrs)==1){$hrs="0{$hrs}";}
        if(strlen($min)==1){$min="0{$min}";}
        if(strlen($sec)==1){$sec="0{$sec}";}
		$string .= "{$hrs}:{$min}:{$sec}";
		if($days){$string = "{$days}d {$string}";}
		if($months){$string = "{$months}m {$string}";}
		if($years){$string = "{$years}y {$string}";}
		return $string;
    }
	if($years){$string .= $years . ' yrs ';}
	if(isset($months)){$string .= $months . ' months ';}
	if($days){$string .= $days . ' days ';}
	if(!$days){
		if($hrs){$string .= $hrs . ' hrs ';}
		if($min){$string .= $min . ' mins ';}
		if($sec && $nosecs==0){$string .= $sec . ' secs ';}
		}
	return $string;
	}
//---------- begin function xml2Arrays
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function xml2Arrays($xmlfile=''){
	if(is_file($xmlfile)){$xml = simplexml_load_file($xmlfile);}
    else{$xml = simplexml_load_string($xmlfile);}
	$array_out=array();
	foreach($xml->items->item as $item){
		$crec=array();
		foreach($item as $citem=>$val){
			$key=(string)$citem;
			if(isNum((string)$val)){$crec[$key]=(float)$val;}
			else{$crec[$key]=removeCdata((string)$val);}
			}
		array_push($array_out,$crec);
        }
	return $array_out;
	}
//---------- begin function xml2Object
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function xml2Object($xmlstring){
	try {
		$xml = new SimpleXmlElement($xmlstring);
		return $xml;
		}
	catch (Exception $e){
        return $e->faultstring;
        }
    return '';
	}
//---------- begin function stringBeginsWith--------------------
/**
* @describe converts and XML string into an xml array
* @param xml string  - XML string
* @param get_attributes boolean - get the attributes as well as the tag values
* @param priority string - tag or attribute
* @return boolean
* @usage
*	$array =  xml2array(file_get_contents('feed.xml'));
*	$array =  xml2array(file_get_contents('feed.xml', 1, 'attribute'));
*	$array =  xml2array(file_get_contents('feed.xml', 'user')); //only read user tags attributes
*/
function xml2Array($contents, $get_attributes=1, $priority = 'tag') {
	if (strlen($contents) < PHP_MAXPATHLEN && is_file($contents)){$contents = file_get_contents($contents);}
    if(!strlen($contents)){return array('No contents');}
    if(!function_exists('xml_parser_create')) {
        //print "'xml_parser_create()' function not found!";
        return array('xml_parser_create function failure');
    	}
    if(preg_match('/\<soap/is',$contents)){$contents=soap2Soap($contents);}
    //Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	try{
    	xml_parse_into_struct($parser, trim($contents), $xml_values);
    	xml_parser_free($parser);
    }
	catch(Exception $e){
    	debugValue($e);
		return $e;
	}

    if(!$xml_values){return $contents;}
    //echo "xml_values".printValue($xml_values);exit;
    $recs=array();
    //Initializations
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();
    $current = &$xml_array; //Refference
    //Go through the tags.
    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
    foreach($xml_values as $data) {
    	if(isset($data['tag']) && strlen($get_attributes) && $data['tag']==$get_attributes && is_array($data['attributes'])){
    		$recs[]=$data['attributes'];
    		continue;
    	}
        unset($attributes,$value);//Remove existing values, or there will be trouble
        //This command will extract these variables into the foreach scope
        // tag(string), type(string), level(int), attributes(array).
        extract($data);//We could use the array by itself, but this is cooler.
        $result = array();
        $attributes_data = array();
        if(isset($value)) {
            if($priority == 'tag'){$result = $value;}
            else{$result['value'] = $value;} //Put the value in a assoc array if we are in the 'Attribute' mode
        	}
        //Set the attributes too.
        if(isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
                if($priority == 'tag'){$attributes_data[$attr] = $val;}
                else{$result['attr'][$attr] = $val;} //Set all the attributes in a array called 'attr'
            	}
        	}
        //See tag status and do the needed.
        if($type == "open") {//The starting of the tag '<tag>'
            $parent[$level-1] = &$current;
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                $repeated_tag_index[$tag.'_'.$level] = 1;
                $current = &$current[$tag];
            	}
			else { //There was another element with the same tag name
                if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    $repeated_tag_index[$tag.'_'.$level]++;
                	}
				else {//This section will make the value an array if multiple tags with the same name appear together
                    $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                    $repeated_tag_index[$tag.'_'.$level] = 2;
                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                        $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                        unset($current[$tag.'_attr']);
                    	}
                	}
                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                $current = &$current[$tag][$last_item_index];
            	}
        	}
		elseif($type == "complete") { //Tags that ends in 1 line '<tag >'
            //See if the key is already taken.
            if(!isset($current[$tag])) { //New Key
                $current[$tag] = $result;
                $repeated_tag_index[$tag.'_'.$level] = 1;
                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
            	}
			else { //If taken, put all things inside a list(array)
                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
                    // ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    if($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                    	}
                    $repeated_tag_index[$tag.'_'.$level]++;
                	}
				else { //If it is not an array...
                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $get_attributes) {
                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        	}
                        if($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        	}
                    	}
                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                	}
            	}
        	}
		elseif($type == 'close') { //End of tag '</tag>'
            $current = &$parent[$level-1];
        	}
    	}
    //echo "recs".printValue($recs);exit;
    if(count($recs)){return $recs;}
    //echo "xml_array".printValue($xml_array);exit;
    //cleanup
	if(isset($xml_array['soapEnvelope'])){$xml_array=$xml_array['soapEnvelope'];}
    return($xml_array);
	}
function xml2Array2($xml) {
    if (is_string($xml)) {
        $xml = simplexml_load_string($xml);
    }
    
    $result = array();
    
    // Handle attributes by adding them directly to parent
    foreach ($xml->attributes() as $key => $value) {
        $result[$key] = (string)$value;
    }
    
    // Handle child nodes
    foreach ($xml->children() as $child) {
        $nodeName = $child->getName();
        
        // Convert SimpleXMLElement to array recursively
        $temp = xml2Array($child);
        
        // Handle multiple nodes with same name
        if (isset($result[$nodeName])) {
            if (!isset($result[$nodeName][0])) {
                $result[$nodeName] = array($result[$nodeName]);
            }
            $result[$nodeName][] = $temp;
        } else {
            $result[$nodeName] = $temp;
        }
    }
    
    // Handle text content
    $text = trim((string)$xml);
    if (!is_array($xml) && $text !== '') {
        $result['value'] = $text;
    }
    
    // If the array only contains text, return just the text
    if (count($result) == 1 && isset($result['value'])) {
        return $result['value'];
    }
    
    return $result;
}
//---------- begin function xmldata2Array
/**
* @describe - parses the xmldata structure stored in the _forms table into a two part array: server and data
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function xmldata2Array($xmldata=''){
	$xmlarray=array();
	//load xmldata inta an xml structure so we can parse it
	@trigger_error("");
	$xml = @simplexml_load_string($xmldata);
	$e=error_get_last();
	if($e['message']!==''){
		$xmlarray['err']=$e;
		return $xmlarray;
		}
	foreach($xml->server as $objects){
		foreach($objects as $value){
			$key=$value->getName();
			$val=trim((string)$value);
			if(strlen($val)==0){continue;}
			$xmlarray['server'][$key]=$val;
			}
		}
	foreach($xml->data as $objects){
		foreach($objects as $value){
			$key=$value->getName();
			if(preg_match('/^\_(action|honeypot|botcheck|xmldata)$/',$key)){continue;}
			if(preg_match('/^add\_(result|id|table)$/',$key)){continue;}
			//check for values
			$vals=array();
			foreach($value->values as $xval){array_push($vals,$xval);}
			if(count($vals)){$val=implode(':',$vals);}
			else{$val=trim(removeHtml((string)$value));}
			if(strlen($val)==0){continue;}
			if(preg_match('/^d\_\-(.+)$/',$key,$kmatch)){
				$key='-'.$kmatch[1];
				$xmlarray['counts']['n']+=1;
            	}
            elseif(preg_match('/^u\_\_(.+)$/',$key,$kmatch)){
				$key='_'.$kmatch[1];
				$xmlarray['counts']['u']+=1;
            	}
			elseif(!preg_match('/^(guid|phpsessid)$/i',$key)){
				$xmlarray['counts']['n']+=1;
            	}
			$xmlarray['data'][$key]=$val;
			}
		}
	foreach($xml->user as $objects){
		foreach($objects as $value){
			$key=$value->getName();
			//check for values
			$vals=array();
			foreach($value->values as $xval){array_push($vals,$xval);}
			if(count($vals)){$val=implode(':',$vals);}
			else{$val=trim(removeHtml((string)$value));}
			if(strlen($val)==0){continue;}
			$key=preg_replace('/^d\_\-/','-',$key);
			$key=preg_replace('/^u\_\_/','_',$key);
			$xmlarray['user'][$key]=$val;
			}
		}
	return $xmlarray;
	}
//---------- begin function xmlEncode--------------------
/**
* @describe returns xml encoded string
* @param str string
* @return string
* @usage $str=xmlEncode($str);
*/
function xmlEncode( $string ) {
	$string=fixMicrosoft($string);
	$string = str_replace( "\r\n", "\n", $string );
	$string = convertSpecialChars($string);
	$string = htmlspecialchars($string,ENT_COMPAT,'UTF-8');
	return $string;
	//convert &amp;#039; back to '
	$string=str_replace('&amp;#039;',"'",$string);
	return $string;
	}
//---------- begin function convertSpecialChars--------------------
/**
* @describe replaces special characters with their closest english equivilent
* @param str string
* @return string
* @usage $data=convertSpecialChars($data);
*/
function convertSpecialChars($str,$charset=null){
	$str = htmlentities($str, ENT_NOQUOTES, $charset);
    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
    return $str;
}
//---------- begin function xmlEncodeCDATA--------------------
/**
* @describe returns xml encoded string and handles CDATA
* @param str string
* @return string
* @usage $data=xmlEncodeCDATA($data);
*/
function xmlEncodeCDATA($val='' ) {
	if(isXML($val)){return "<![CDATA[\n" . xmlEncode($val) . "\n]]>";}
    return xmlEncode($val);
}

//---------- begin function xmlEncodeCDATA--------------------
/**
* @describe returns a string containing the xml header line
* @param params array
*	version - defaults to 1.0
*	encodeing - defaults to ISO-8859-1
* @return string
* @usage $xml=xmlHeader($data);
*/
function xmlHeader($params=array()){
	$version=isset($params['version'])?$params['version']:"1.0";
	$encoding=isset($params['encoding'])?$params['encoding']:"ISO-8859-1";
	return '<?xml version="'.$version.'" encoding="'.$encoding.'"?>'."\r\n";
}
