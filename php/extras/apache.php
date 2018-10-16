<?php
/*
	CONFIG SETTINGS:
		apache_access_log="{full path to access log}" -if set then it will trigger the cron to read and parse
		apache_access_table="apache_access_log" - set to change table name - defaults to apache_access_log
		apache_access_skip_local="1" - set to skip local requests
		apache_access_skip_bots="1" - set to skip requests from bots
*/
function apacheStatusCodes(){
	return array(
	    100 => 'Continue',
	    101 => 'Switching Protocols',
	    102 => 'Processing', // WebDAV; RFC 2518
	    200 => 'OK',
	    201 => 'Created',
	    202 => 'Accepted',
	    203 => 'Non-Authoritative Information', // since HTTP/1.1
	    204 => 'No Content',
	    205 => 'Reset Content',
	    206 => 'Partial Content',
	    207 => 'Multi-Status', // WebDAV; RFC 4918
	    208 => 'Already Reported', // WebDAV; RFC 5842
	    226 => 'IM Used', // RFC 3229
	    300 => 'Multiple Choices',
	    301 => 'Moved Permanently',
	    302 => 'Found',
	    303 => 'See Other', // since HTTP/1.1
	    304 => 'Not Modified',
	    305 => 'Use Proxy', // since HTTP/1.1
	    306 => 'Switch Proxy',
	    307 => 'Temporary Redirect', // since HTTP/1.1
	    308 => 'Permanent Redirect', // approved as experimental RFC
	    400 => 'Bad Request',
	    401 => 'Unauthorized',
	    402 => 'Payment Required',
	    403 => 'Forbidden',
	    404 => 'Not Found',
	    405 => 'Method Not Allowed',
	    406 => 'Not Acceptable',
	    407 => 'Proxy Authentication Required',
	    408 => 'Request Timeout',
	    409 => 'Conflict',
	    410 => 'Gone',
	    411 => 'Length Required',
	    412 => 'Precondition Failed',
	    413 => 'Request Entity Too Large',
	    414 => 'Request-URI Too Long',
	    415 => 'Unsupported Media Type',
	    416 => 'Requested Range Not Satisfiable',
	    417 => 'Expectation Failed',
	    418 => 'I\'m a teapot', // RFC 2324
	    419 => 'Authentication Timeout', // not in RFC 2616
	    420 => 'Enhance Your Calm', // Twitter
	    420 => 'Method Failure', // Spring Framework
	    422 => 'Unprocessable Entity', // WebDAV; RFC 4918
	    423 => 'Locked', // WebDAV; RFC 4918
	    424 => 'Failed Dependency', // WebDAV; RFC 4918
	    424 => 'Method Failure', // WebDAV)
	    425 => 'Unordered Collection', // Internet draft
	    426 => 'Upgrade Required', // RFC 2817
	    428 => 'Precondition Required', // RFC 6585
	    429 => 'Too Many Requests', // RFC 6585
	    431 => 'Request Header Fields Too Large', // RFC 6585
	    444 => 'No Response', // Nginx
	    449 => 'Retry With', // Microsoft
	    450 => 'Blocked by Windows Parental Controls', // Microsoft
	    451 => 'Redirect', // Microsoft
	    451 => 'Unavailable For Legal Reasons', // Internet draft
	    494 => 'Request Header Too Large', // Nginx
	    495 => 'Cert Error', // Nginx
	    496 => 'No Cert', // Nginx
	    497 => 'HTTP to HTTPS', // Nginx
	    499 => 'Client Closed Request', // Nginx
	    500 => 'Internal Server Error',
	    501 => 'Not Implemented',
	    502 => 'Bad Gateway',
	    503 => 'Service Unavailable',
	    504 => 'Gateway Timeout',
	    505 => 'HTTP Version Not Supported',
	    506 => 'Variant Also Negotiates', // RFC 2295
	    507 => 'Insufficient Storage', // WebDAV; RFC 4918
	    508 => 'Loop Detected', // WebDAV; RFC 5842
	    509 => 'Bandwidth Limit Exceeded', // Apache bw/limited extension
	    510 => 'Not Extended', // RFC 2774
	    511 => 'Network Authentication Required', // RFC 6585
	    598 => 'Network read timeout error', // Unknown
	    599 => 'Network connect timeout error', // Unknown
	);
}
function apacheTableSetup(){
	global $CONFIG;
	$table=$CONFIG['apache_access_table'];
	if(!isDBTable($table)){
		$fields=array(
			'_id'				=> databasePrimaryKeyFieldString(),
			'_cdate'			=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'			=> databaseDataType('int')." NOT NULL",
			'_edate'			=> databaseDataType('datetime')." NULL",
			'_euser'			=> databaseDataType('int')." NULL",
			'browser'			=> 'varchar(100) NULL',
			'browser_version'	=> 'varchar(20) NULL',
			'country'			=> 'varchar(25) NULL',
			'domain'			=> 'varchar(200) NULL',
			'ip_address'		=> 'varchar(25) NULL',
			'lang'				=> 'varchar(10) NULL',
			'log_date'			=> 'datetime NULL',
			'method'			=> 'varchar(15) NULL',
			'mobile'			=> 'int(11) NULL',
			'os'				=> 'varchar(25) NULL',
			'path'				=> 'varchar(255) NULL',
			'referer'			=> 'varchar(255) NULL',
			'sha'				=> 'char(42) NOT NULL UNIQUE',
			'status'			=> 'int(11) NULL',			
			'user_agent'		=> 'varchar(255) NULL',
			'bot'				=> 'varchar(100) NULL'
		);
		$ok = createDBTable($table,$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"log_date"));
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"os"));
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"bot"));
		$addopts=array('-table'=>"_tabledata",
			'tablename'		=> $table,
			'listfields'	=> "log_date\r\nip_address\r\nbrowser\r\nbot\r\npath\r\nstatus\r\nos\r\nreferer",
			'sortfields'	=> "log_date desc",
			'synchronize'	=> 0
		);
		$id=addDBRecord($addopts);
	}
	return true;
}
function apacheReportCounts($field){
	global $CONFIG;
	if(!isset($CONFIG['apache_access_table'])){
		$CONFIG['apache_access_table']='apache_access_log';
	}
	if(!isDBTable($CONFIG['apache_access_table'])){
		apacheTableSetup();
	}
	$table=$CONFIG['apache_access_table'];
	$q=<<<ENDOFQ
		SELECT 
			count(*) cnt,
			{$field} as name,
			min(log_date) as mindate,
			max(log_date) as maxdate  
		FROM 
			{$table} 
		GROUP BY 
			{$field}
		ORDER BY
			cnt desc
ENDOFQ;
	$recs=getDBRecords($q);
	//map status name
	if($field=='status'){
		$map=apacheStatusCodes();
		foreach($recs as $i=>$rec){
			$status=$rec[$field];
			$recs[$i]['status_name']=$map[$status];
		}
	}
	//add percent
	$total=0;
	foreach($recs as $i=>$rec){$total+=$rec['cnt'];}
	foreach($recs as $i=>$rec){
		if(!strlen($rec['name'])){$recs[$i]['name']='UNKNOWN';}
		$recs[$i]['pcnt']=round(($rec['cnt']/$total)*100,0);
	}
	return $recs;
}
function apacheParseLogFile(){
	global $CONFIG;
	if(!isset($CONFIG['apache_access_table'])){
		$CONFIG['apache_access_table']='apache_access_log';
	}
	if(!isDBTable($CONFIG['apache_access_table'])){
		apacheTableSetup();
	}
	$logfile=$CONFIG['apache_access_log'];
	if(!file_exists($logfile)){return $logfile.' does not exist';}
	copyFile($logfile, "{$logfile}.reading");
	if(file_exists("{$logfile}.reading")){
		setFileContents($logfile,'');
		$logfile.='.reading';
		if ($fh = fopen($logfile,'r')) {
			while (!feof($fh)) {
				//stream_get_line is significantly faster than fgets
				$line = stream_get_line($fh, 1000000, "\n");
				$line=trim($line);
		        $rec=apacheParseLogFileLine($line);
			}
			fclose($fh);
		}
		unlink($logfile);
	}
	else{
		return "unable to copy {$logfile} to {$logfile}.reading";
	}
	return '';
}
function apacheParseLogFileLine($line){
	global $CONFIG;
	preg_match("/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/", $line, $matches);
	if(!empty($matches[1])){
		//skip 301 redirects
		//if((integer)$matches[10]==301){return null;}
		//skip local access
		if(isset($CONFIG['apache_access_skip_local']) && $CONFIG['apache_access_skip_local']==1){
			if(stringBeginsWith($matches[1],'10.')){return null;}
			if(stringBeginsWith($matches[1],'172.16.')){return null;}
			if(stringBeginsWith($matches[1],'172.17.')){return null;}
			if(stringBeginsWith($matches[1],'172.18.')){return null;}
			if(stringBeginsWith($matches[1],'172.19.')){return null;}
			if(stringBeginsWith($matches[1],'172.20.')){return null;}
			if(stringBeginsWith($matches[1],'172.21.')){return null;}
			if(stringBeginsWith($matches[1],'172.22.')){return null;}
			if(stringBeginsWith($matches[1],'172.23.')){return null;}
			if(stringBeginsWith($matches[1],'172.24.')){return null;}
			if(stringBeginsWith($matches[1],'172.25.')){return null;}
			if(stringBeginsWith($matches[1],'172.26.')){return null;}
			if(stringBeginsWith($matches[1],'172.27.')){return null;}
			if(stringBeginsWith($matches[1],'172.28.')){return null;}
			if(stringBeginsWith($matches[1],'172.29.')){return null;}
			if(stringBeginsWith($matches[1],'172.30.')){return null;}
			if(stringBeginsWith($matches[1],'172.31.')){return null;}
			if(stringBeginsWith($matches[1],'192.168.')){return null;}
			if(stringBeginsWith($matches[1],'127.0.0.1')){return null;}
		}
		
		//build a rec array
		$rec = array(
			'sha'=>sha1($line),
			'domain'=>$CONFIG['name']
		); 
      	$rec['ip_address'] = $matches[1];
      	$rec['identity'] = $matches[2];
      	$rec['user'] = $matches[2];
      	$rec['date'] = str_replace('/','-',$matches[4]);
      	$rec['time'] = $matches[5];
      	$rec['timezone'] = $matches[6];
      	$rec['log_date'] = date('Y-m-d H:i:s',strtotime("{$rec['date']} {$rec['time']}{$rec['timezone']}"));
      	$rec['method'] = $matches[7];
      	$rec['path'] = $matches[8];
      	$rec['protocal'] = $matches[9];
      	$rec['status'] = $matches[10];
      	$rec['bytes'] = $matches[11];
      	$rec['referer'] = str_replace('"','',$matches[12]);
      	$rec['user_agent'] = $matches[13];
      	$browser=getAgentBrowser($rec['user_agent']);
      	//Check for applewebkit - safari
		if(preg_match('/\ opr\//i',$rec['user_agent'])){
			$rec['browser']="opera";
			$rec['browser_version']=$browser['version'];
	    }
	    elseif(preg_match('/\ chrome\//i',$rec['user_agent'])){
			$rec['browser']="chrome";
			$rec['browser_version']=$browser['version'];
	    }
		elseif(preg_match('/applewebkit/i',$rec['user_agent'])){
			$rec['browser']="safari";
			$rec['browser_version']=$browser['version'];
	    }
		elseif(isset($browser['browser'])){
			$rec['browser']=$browser['browser'];
			$rec['browser_version']=$browser['version'];
		}
		else{
			$rec['browser']='Unknown';
			$rec['browser_version']='Unknown';
		}
      	$rec['lang']=getAgentLang($rec['user_agent']);
      	if(preg_match('/^[a-z]{2,2}\-([a-z]{2,2})$/i',$rec['lang'],$m)){
	    	$rec['country']=$m[1];
		}
      	$rec['os']=getAgentOS($rec['user_agent']);
      	//skip bots?
      	if(isset($CONFIG['apache_access_skip_bots']) && $CONFIG['apache_access_skip_bots']==1 && stringContains($rec['os'],'BOT:')){return null;}
      	if(stringContains($rec['os'],'BOT:')){
      		$rec['bot']=str_replace('bot:','',strtolower($rec['os']));
      		$rec['os']='BOT';
      	}
      	$rec['mobile']=isMobileDevice($rec['user_agent']);
      	//remove junk values
      	foreach($rec as $k=>$v){
      		if($v=='-'){unset($rec[$k]);}
      	}
      	$rec['-table']=$CONFIG['apache_access_table'];
		$rec['-ignore']=1;
		$id=addDBRecord($rec);
      	return $id;
	}
	return null;
}