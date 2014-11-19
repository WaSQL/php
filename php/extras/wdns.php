<?php
/* 
	STARTUP INSTRUCTIONS IN LINUX:
		In order for this script to run in the background and not stop when you exit the cli, run as follows:
		php -q wdns.php < /dev/null &
	
	Problems:
		secure sites fail:  https://www.facebook.com
		pidgin failes- not handling srv record request properly
		DONE: Images
		DONE: reverse_lookup on IP address
		DONE: Filter by client_ip or machine name
		DONE: Add scheduling to control internet access based on time
		DONE: Add alerts so you can get an email/text based on filters...
		
	External DNS Servers:
		OpenDNS
			Security Only
				208.67.222.222 	208.67.220.220
					returns 67.215.65.133 for phishing sites
			Security+Pornography
				208.67.222.123 and 208.67.220.123
					returns 67.215.65.130 for pornography sites
					returns 67.215.65.133 for phishing sites
		Comodo Secure DNS
			8.26.56.26 and 8.20.247.20
		Google
			8.8.8.8 and 8.8.4.4
		GreenTeamDNS
			81.218.119.11 and 209.88.198.133
				return 81.218.119.11 for blocked sites
		Norton Connect Safe
			Security Only:
				199.85.126.10 and 199.85.127.10
			Security+Pornography:
				199.85.126.20 and 199.85.127.20
					returns 156.154.175.20 for blocked sites
			Security+Pornography+mature content, abortion, alcohol, crime, cults, drugs, gambling, hate, sexual orientation, suicide, tobacco or violence
				199.85.126.30 and 199.85.127.30
					returns 156.154.175.30 for blocked sites
					returns 54.200.75.96 if domain does not exist
			OpenNIC - does not censor anything
				173.230.156.28 and 23.226.230.72

*/
date_default_timezone_set('America/Denver');
$progpath=dirname(__FILE__);
$php_path=realpath("{$progpath}/../");
$_SERVER['HTTP_HOST']='wdns';
include_once "{$php_path}/database.php";
include_once "{$php_path}/wasql.php";
global $CONFIG;

if(isset($_REQUEST['username']) && isset($_REQUEST['computername'])){
	$opts=array(
		'-table'		=> 'wdns_users',
		'username'		=> $_REQUEST['username'],
		'computername'	=> $_REQUEST['computername'],
		'ip_public'		=> $_SERVER['REMOTE_ADDR'],
		'ip_local'		=> $_REQUEST['ip_local'],
		'os'			=> $_REQUEST['os']
	);
	$id=addDBRecord($opts);
	//delete records older than 30 days or wdns_users_age if specified in the config.xml
	if(!isset($CONFIG['wdns_users_age'])){
		$CONFIG['wdns_users_age']=30;
	}
	$ok=executeSQL("DELETE FROM wdns_users WHERE _cdate < DATE_SUB(NOW(), INTERVAL {$CONFIG['wdns_users_age']} DAY);");
	echo $id.printValue($opts);exit(0);
}
elseif(isset($_SERVER['HTTP_USER_AGENT'])){
	echo "Invalid Request";
	exit;
}
$progpath=dirname(__FILE__);
include_once "{$progpath}/wdns/DNServer.php";
if(PHP_OS != 'WINNT' && PHP_OS != 'WIN32' && PHP_OS != 'Windows'){
	include_once "{$progpath}/wdns/Fork.php";
	include_once "{$progpath}/wdns/DNServer_thread.php";
}
require("{$progpath}/wdns/dns.inc.php");

$alerts_sent=array();
global $alerts_sent;
$bind_ip='127.0.0.1';
if(isset($CONFIG['ip_address'])){
	$bind_ip=$CONFIG['ip_address'];
}
global $bind_ip;
if(isset($argv[1]) && $argv[1]=='clean'){
	$names=array('wdns','wdns','dnsserver');
	foreach($names as $name){
		if(isDBTable($name.'_lookup')){executeSQL("drop table {$name}_lookup");}
		if(isDBTable($name.'_lookup')){executeSQL("drop table {$name}_users");}
		if(isDBTable($name.'_lookup')){executeSQL("drop table {$name}_cache");}
		if(isDBTable($name.'_logs')){executeSQL("drop table {$name}_logs");}
		if(isDBTable($name.'_filters')){executeSQL("drop table {$name}_filters");}
		if(isDBTable($name.'_exceptions')){executeSQL("drop table {$name}_exceptions");}
		if(isDBTable($name.'_schedules')){executeSQL("drop table {$name}_schedules");}
		if(isDBTable($name.'_alerts')){executeSQL("drop table {$name}_alerts");}
		executeSQL("delete from _tabledata where tablename like '{$name}_%'");
		executeSQL("delete from _fielddata where tablename like '{$name}_%'");
	}
	echo "Database Cleaned";
	exit;
}
date_default_timezone_set('America/Denver');
//check database schema for wdns
wdnsSchema();
if($CONFIG['debug']){
	echo "wdns listening on {$bind_ip}\n";
}
$dns = new DNServer("wdnsHandleRequest", $bind_ip /* needs the IP to listen in UnixLike OS in windows just need NULL*/);
exit;
/*-----------------------------------------------------------------------------------------*/
function wdnsGetDomainRecord($name){
	global $CONFIG;
	//remove records older than cache_days, default to 30 days if not set
	$days=isNum($CONFIG['cache_days'])?$CONFIG['log_days']:30;
	$query="DELETE FROM wdns_lookup WHERE _cdate < DATE_SUB(NOW(), INTERVAL {$days} DAY)";
	$ok=executeSQL($query);
	//if($name=='_xmpp-client._tcp.gmail.com'){$name='talk.google.com';}
	//elseif($name=='_xmppconnect.gmail.com'){$name='talk.google.com';}

	//wdns_filters
	$rec=getDBRecord(array(
		'-table'	=> 'wdns_lookup',
		'name'		=> $name,
		'-fields'	=> '_id,name,ip_address,block'
	));
	if(isset($rec['name'])){return $rec;}
	/*
		check Norton Connect Safe
		199.85.126.30 and 199.85.127.30
			returns 156.154.175.30 for blocked sites
	*/
	$opts=array('-table'=>'wdns_lookup','block'=>0,'name'=>$name,'source'=>'norton');
	$ip=wdnsNSLookup($name,'199.85.126.30');
	echo "Norton IP for {$name}:{$ip}\n";
	if(strlen($ip)){
		if($ip=='156.154.175.30'){
			$opts['block']=1;
			$opts['note']='Pornography';
			$opts['source']='norton';
		}
		elseif($ip=='156.154.176.30'){
			$opts['block']=1;
			$opts['note']='Pornography';
			$opts['source']='norton';
		}
		elseif($ip=='54.200.75.96'){
			$opts['block']=1;
			$opts['source']='norton';
	    	$opts['note']='Domain does not exist';
		}
	}
	//double check by checking opendns also
	$ip=wdnsNSLookup($name,'208.67.222.123');
	echo "OpenDNS IP for {$name}:{$ip}\n";
	if(strlen($ip)){
		if($ip=='67.215.65.130'){
			$opts['block']=1;
			$opts['note']='Pornography';
			$opts['source']='opendns';
		}
		elseif($ip=='67.215.65.133'){
			$opts['block']=1;
			$opts['note']='Phishing';
			$opts['source']='opendns';
		}
	}
	if($opts['block'] || !strlen($ip)){
    	//ip was blocked - look up true IP with google
    	$opts['ip_address']=wdnsNSLookup($name,'8.8.8.8');
    	if(!strlen($ip)){$opts['source']='google';}
    	echo "Google IP for {$name}:{$ip}\n";
	}
	else{$opts['ip_address']=$ip;}
	//echo printValue($opts);
	$id=addDBRecord($opts);
	$opts['_id']=$id;
	return $opts;
}
function wdnsGetDomainException($name,$client_ip){
	//mark records older than 1 day as not active
	$query="update wdns_exceptions set active=0 WHERE _cdate < DATE_SUB(NOW(), INTERVAL 1 DAY)";
	$ok=executeSQL($query);
	$name=getUniqueHost($name);
	//wdns_filters
	$rec=getDBRecord(array(
		'-table'	=> 'wdns_exceptions',
		'name'		=> $name,
		'client_ip'	=> $client_ip,
		'active'	=> 1,
		'-fields'	=> '_id,name,client_ip,reason'
	));
	return $rec;
}
function wdnsGetUserRecord($client_ip,$client_name){
	//return the first wdns_users record that applies.  
	//	This table is populated by placing wdns_user.exe in their startup folder so it runs when they login
	//	wdns_user.exe captures their username,computername, and ip_address when they login
	$rec=getDBRecord(array(
		'-table'		=> 'wdns_users',
		'ip_address'	=> $client_ip,
		'-order'		=> '_cdate desc'
	));
	if(!is_array($rec)){return 'unknown';}
	//how old is the record in days?
	$seconds = strtotime(date("M d Y ")) - (strtotime($rec['_cdate']));
	$rec['age']=floor($str/3600/24);
	return $rec;
}
function wdnsGetFilterRecord($name,$client_ip,$client_name){
	//return the first wdns_filters record that applies
	//wdns_filters
	//client specific filters
	$recs=getDBRecords(array(
		'-table'	=> 'wdns_filters',
		'-where'	=> "client in ('{$client_ip}','{$client_name}')",
	));
	if(is_array($recs)){
		foreach($recs as $rec){
			if(stringContains($name,$rec['filter'])){
				return $rec;
			}
		}
	}
	//check for records that apply to ALL clients
	$recs=getDBRecords(array(
		'-table'	=> 'wdns_filters',
		'client'	=> 'ALL',
	));
	$ip='unknown';
	if(!is_array($recs)){return null;}
	foreach($recs as $rec){
		if(stringContains($name,$rec['filter'])){
			return $rec;
		}
	}
	return null;
}
function wdnsGetScheduleRecord($name,$client_ip,$client_name){
	//return the first wdns_schedules record that applies
	//wdns_schedules

	/*
		op: 0=before, 1=after
		action: 0=block, 1=allow

	*/
	//client specific records
	$wheres=array();
	$wheres[]="client in ('{$client_ip}','{$client_name}')";
	$wheres[]="( op=0 and time(now()) < time(mtime) ) or ( op=1 and time(now()) > time(mtime) )";
	$opts=array(
		'-table'	=> 'wdns_schedules',
		'-where'	=> implode(' and ',$wheres),
	);
	//echo printValue($opts);
	$recs=getDBRecords($opts);
	if(is_array($recs)){
		foreach($recs as $rec){
			if(strlen($rec['filter']) && !stringContains($name,$rec['filter'])){continue;}
			return $rec;
		}
	}
	//check for records that apply to ALL clients
	$wheres=array();
	$wheres[]="client='ALL'";
	$wheres[]="( op=0 and time(now()) < time(mtime) ) or ( op=1 and time(now()) > time(mtime) )";
	$recs=getDBRecords(array(
		'-table'	=> 'wdns_schedules',
		'-where'	=> implode(' and ',$wheres),
	));
	if(is_array($recs)){
		foreach($recs as $rec){
			if(strlen($rec['filter']) && !stringContains($name,$rec['filter'])){continue;}
			return $rec;
		}
	}
	return null;
}

function wdnsHandleRequest($domain_name,$type,$client_ip){
	global $CONFIG;
	global $bind_ip;

	$ip=$bind_ip;
	$blocked=0;
	$filter_id=0;
	global $cached;
	$true_domain=str_replace('.Home','',$domain_name);
	//echo "wdnsHandleRequest({$domain_name},{$type},{$client_ip})\n";
	$domainRec=wdnsGetDomainRecord($true_domain);
	$client_name=gethostbyaddr($client_ip);
	//if($CONFIG['debug']){echo "wdnsHandleRequest({$client_name},{$domain_name},[{$type}],{$domainRec['ip_address']},{$domainRec['block']})\n";}
	$type=strtoupper(trim($type));
	switch($type){
		case 'A':
		case 'AAAA':
		case 'ANY':
			//must have a valid domain record
			if(!strlen($domainRec['ip_address'])){
				//$client_ip,$client_name,$name,$return_ip,$action=1,$filter_id=0,$exception_id=0
				wdnsLog(array(
					'client_ip'		=> $client_ip,
					'client_name'	=> $client_name,
					'name'			=> $true_domain,
					'return_ip'		=> $bind_ip,
					'action'		=> 0,
					'debug'			=> 1
				));
				return $bind_ip;
			}
			//check for schedules - is this person allowed on right now?
			$domainSchedule=wdnsGetScheduleRecord($true_domain,$client_ip,$client_name);
			if(isset($domainSchedule['action'])){
				if($domainSchedule['action']==1){
					//$client_ip,$client_name,$name,$return_ip,$action=1,$filter_id=0,$exception_id=0
					wdnsLog(array(
						'client_ip'		=> $client_ip,
						'client_name'	=> $client_name,
						'name'			=> $true_domain,
						'return_ip'		=> $domainRec['ip_address'],
						'action'		=> 1,
						'schedule_id'		=> $domainSchedule['_id'],
						'debug'			=> 2
					));
					return $domainRec['ip_address'];
				}
				else{
					//$client_ip,$client_name,$name,$return_ip,$action=1,$filter_id=0,$exception_id=0
					wdnsLog(array(
						'client_ip'		=> $client_ip,
						'client_name'	=> $client_name,
						'name'			=> $true_domain,
						'return_ip'		=> $bind_ip,
						'action'		=> 0,
						'schedule_id'	=> $domainSchedule['_id'],
						'debug'			=> 3
					));
					return $bind_ip;
				}
			}
			//check for execptions
			$domainException=wdnsGetDomainException($true_domain,$client_ip);
			if(isset($domainException['_id'])){
				//$client_ip,$client_name,$name,$return_ip,$action=1,$filter_id=0,$exception_id=0
				wdnsLog(array(
					'client_ip'		=> $client_ip,
					'client_name'	=> $client_name,
					'name'			=> $true_domain,
					'return_ip'		=> $domainRec['ip_address'],
					'action'		=> 1,
					'exception_id'	=> $domainException['_id'],
					'debug'			=> 4
				));
				return $domainRec['ip_address'];
			}
			//check for custom filters
			$filterRec=wdnsGetFilterRecord($true_domain,$client_ip,$client_name);
			if(isset($filterRec['action'])){
				if($filterRec['action']==1){
					//$client_ip,$client_name,$name,$return_ip,$action=1,$filter_id=0,$exception_id=0
					wdnsLog(array(
						'client_ip'		=> $client_ip,
						'client_name'	=> $client_name,
						'name'			=> $true_domain,
						'return_ip'		=> $domainRec['ip_address'],
						'action'		=> 1,
						'filter_id'		=> $filterRec['_id'],
						'debug'			=> 5
					));

					return $domainRec['ip_address'];
				}
				else{
					//$client_ip,$client_name,$name,$return_ip,$action=1,$filter_id=0,$exception_id=0
					wdnsLog(array(
						'client_ip'		=> $client_ip,
						'client_name'	=> $client_name,
						'name'			=> $true_domain,
						'return_ip'		=> $bind_ip,
						'action'		=> 0,
						'filter_id'		=> $filterRec['_id'],
						'debug'			=> 6
					));
					return $bind_ip;
				}
			}
			//check for block in domainRec
			if($domainRec['block']){
				if($type=='A'){
					$filter=isset($filterRec['_id'])?$filterRec['_id']:0;
					//$client_ip,$client_name,$name,$return_ip,$action=1,$filter_id=0,$exception_id=0
					wdnsLog(array(
						'client_ip'		=> $client_ip,
						'client_name'	=> $client_name,
						'name'			=> $true_domain,
						'return_ip'		=> $bind_ip,
						'action'		=> 0,
						'filter_id'		=> $filter,
						'debug'			=> 7
					));
				}
				return $bind_ip;
			}
		break;
	}
	//$client_ip,$client_name,$name,$return_ip,$action=1,$filter_id=0,$exception_id=0
	if(!strlen($domainRec['ip_address'])){
    	$domainRec['ip_address']=$bind_ip;
	}
	wdnsLog(array(
		'client_ip'		=> $client_ip,
		'client_name'	=> $client_name,
		'name'			=> $true_domain,
		'return_ip'		=> $domainRec['ip_address'],
		'action'		=> 1,
		'debug'			=> 8
	));
    return $domainRec['ip_address'];
}
function wdnsLog($opts){
	global $CONFIG;
	//remove records older than wdns_log_days, default to 30 days if not set
	$days=isNum($CONFIG['log_days'])?$CONFIG['log_days']:30;
	$query="DELETE FROM wdns_logs WHERE _cdate < DATE_SUB(NOW(), INTERVAL {$days} DAY)";
	$ok=executeSQL($query);
	$opts['-table']='wdns_logs';
	echo printValue($opts);
	$id=addDBRecord($opts);
	if(!isNum($id) && $CONFIG['debug']){
		echo printValue($id);exit;
	}
	//send wdns_alerts
	if(!isset($opts['alert_id'])){
		wdnsProcessAlerts($opts);
	}
}
function wdnsProcessAlerts($params){
	//TODO: limit messages to one per 5 minutes per alert per client. 
	global $CONFIG;
	global $alerts_sent;
	$recs=getDBRecords(array(
		'-table'	=> 'wdns_alerts',
		'-where'	=> "client in ('{$params['client_ip']}','{$params['client_name']}','ALL')",
	));
	if(!is_array($recs)){return;}
	//clear old alerts_sent records
	$fiveminutesago=time()-60*5;
	foreach($alerts_sent as $alertkey=>$rec){
		if($alerts_sent[$alertkey] < $fiveminutesago){
			unset($alerts_sent[$alertkey]);
		}
	}
	//echo "alerts_sent".printValue($alerts_sent);exit;
	$alerts=array();
	foreach($recs as $rec){
		$alertkey=$rec['email'].$rec['_id'];
		if(isset($alerts_sent[$alertkey])){continue;}
		if(stringContains($params['name'],$rec['filter']) && isEmail($rec['email'])){
			$alerts[]=$rec;
		}
	}
	if(!count($alerts)){return;}
	foreach($alerts as $rec){
		$alertkey=$rec['email'].$rec['_id'];
		$sendopts=array(
			'to'		=> $rec['email'],
			'from'		=> 'wdns@wasql.com',
			'subject' 	=> 'wdns Alert',
			'message'	=> printValue($params)
		);
		$ok=@sendMail($sendopts);
		//echo "sendMail".printValue($ok).printValue($sendopts);
		if($CONFIG['debug']){
			echo "sendMail".printValue($ok).printValue($sendopts);exit;
		}
		$alerts_sent[$alertkey]=time();
	}
}
function wdnsNSLookup($domain,$server,$loopcnt=0){
	$type='A';
	$query=new DNSQuery($server,53,2,true,false,false);
	$result=$query->Query($domain,$type);
	unset($query);
	if(!isset($result->results[0]->data)){return null;}
	$ip=$result->results[0]->data;
	$parts=preg_split('/\./',$ip);
	if(!isNum($parts[0]) && $loopcnt < 50){
		$loopcnt++;
		return wdnsNSLookup($ip,$server,$loopcnt);
	}
	return $ip;
}

function wdnsSchema(){
	//wdns_lookup
	if(!isDBTable('wdns_lookup')){
		$table='wdns_lookup';
		$fields=array(
			'_id'		=> databasePrimaryKeyFieldString(),
			'_cdate'	=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'	=> "INT NOT NULL",
			'_edate'	=> databaseDataType('datetime')." NULL",
			'_euser'	=> "INT NULL",
		);
		$fields['name']="varchar(225) NOT NULL";
		$fields['ip_address']="varchar(40) NULL";
		$fields['source']="varchar(40) NOT NULL Default 'unknown'";
		$fields['block']=databaseDataType('tinyint')." NOT NULL Default 0";
		$fields['note']="varchar(225) NULL";
		$ok = createDBTable($table,$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>1));
		//_tabledata
		$id=addDBRecord(array('-table'=>'_tabledata',
			'tablegroup'	=> 'wdns',
			'tablename'		=> $table,
			'formfields'	=> "name\r\nip_address\r\nsource block\r\nnote",
			'listfields'	=> "name\r\nip_address\r\nsource\r\nblock\r\nnote",
		));
	}
	//wdns_exceptions
	if(!isDBTable('wdns_exceptions')){
		$table='wdns_exceptions';
		$fields=array(
			'_id'		=> databasePrimaryKeyFieldString(),
			'_cdate'	=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'	=> "INT NOT NULL",
			'_edate'	=> databaseDataType('datetime')." NULL",
			'_euser'	=> "INT NULL",
		);
		$fields['name']="varchar(225) NOT NULL";
		$fields['client_ip']="varchar(40) NOT NULL";
		$fields['active']=databaseDataType('tinyint')." NOT NULL Default 1";
		$fields['reason']="varchar(255)";
		$ok = createDBTable($table,$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name,client_ip"));
		//_tabledata
		$id=addDBRecord(array('-table'=>'_tabledata',
			'tablegroup'	=> 'wdns',
			'tablename'		=> $table,
			'formfields'	=> "reason",
			'listfields'	=> "_cdate\r\nactive\r\nname\r\nclient_ip\r\nreason",
		));
		//_fielddata
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'reason',
			'inputtype'		=> 'textarea',
			'width'			=> 600,
			'height'		=> 70,
			'inputmax'		=> 255,
			'required'		=> 0
		));
	}
	//wdns_filters?
	if(!isDBTable('wdns_filters')){
		$table='wdns_filters';
		$fields=array(
			'_id'		=> databasePrimaryKeyFieldString(),
			'_cdate'	=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'	=> "INT NOT NULL",
			'_edate'	=> databaseDataType('datetime')." NULL",
			'_euser'	=> "INT NULL",
		);
		$fields['filter']="varchar(225) NOT NULL";
		$fields['client']="varchar(120) NOT NULL Default 'ALL'";
		$fields['note']="varchar(225) NULL";
		//action:  0=block (default), 1=allow
		$fields['action']=databaseDataType('tinyint')." NOT NULL Default 0";
		$ok = createDBTable($table,$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"client",));
		//_tabledata
		$id=addDBRecord(array('-table'=>'_tabledata',
			'tablegroup'	=> 'wdns',
			'tablename'		=> $table,
			'formfields'	=> "filter action\r\nclient\r\nnote",
			'listfields'	=> "filter\r\naction\r\nclient\r\nnote",
		));
		//_fielddata
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'filter',
			'inputtype'		=> 'text',
			'width'			=> 300,
			'inputmax'		=> 255,
			'required'		=> 0
		));
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'client',
			'inputtype'		=> 'text',
			'width'			=> 220,
			'displayname'	=> 'Client IP Address, Machine Name (or ALL)',
			'defaultval'	=> 'ALL',
			'inputmax'		=> 40,
			'required'		=> 0
		));
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $table,
			'fieldname'		=> 'action',
			'inputtype'		=> 'select',
			'tvals'			=> "0\r\n1",
			'dvals'			=> "Block\r\nAllow",
			'required'		=> 1,
			'defaultval'	=> 0
		));
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'note',
			'inputtype'		=> 'textarea',
			'width'			=> 400,
			'height'		=> 50,
			'inputmax'		=> 255,
			'required'		=> 0
		));
		//add a few filters
		$blocks=array('topless','orgy','xxx');
		foreach($blocks as $block){
			$id=addDBRecord(array('-table'=>$table,
				'action'		=> 0,
				'client'		=> 'ALL',
				'filter'		=> $block
			));
		}
	}
	//wdns_users
	if(!isDBTable('wdns_users')){
		$table='wdns_users';
		$fields=array(
			'_id'		=> databasePrimaryKeyFieldString(),
			'_cdate'	=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'	=> "INT NOT NULL Default 0",
			'_edate'	=> databaseDataType('datetime')." NULL",
			'_euser'	=> "INT NULL",
		);
		$fields['username']="varchar(225) NOT NULL";
		$fields['ip_public']="varchar(40) NULL NOT NULL";
		$fields['ip_local']="varchar(40) NULL NOT NULL";
		$fields['computername']="varchar(225) NOT NULL";
		$fields['os']="varchar(225) NULL";
		$ok = createDBTable($table,$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"ip_address"));
		//_tabledata
		$id=addDBRecord(array('-table'=>'_tabledata',
			'tablegroup'	=> 'wdns',
			'tablename'		=> $table,
			'formfields'	=> "ip_public\r\nip_local\r\nusername\r\ncomputername\r\nos",
			'listfields'	=> "_cdate\r\nip_public:ip_local\r\nusername\r\ncomputername\r\nos",
		));
	}
	//wdns_schedules
	if(!isDBTable('wdns_schedules')){
		$table='wdns_schedules';
		$fields=array(
			'_id'		=> databasePrimaryKeyFieldString(),
			'_cdate'	=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'	=> "INT NOT NULL",
			'_edate'	=> databaseDataType('datetime')." NULL",
			'_euser'	=> "INT NULL",
		);
		//operator: 0=before, 1=after
		$fields['op']=databaseDataType('tinyint')." NOT NULL Default 1";
		$fields['client']="varchar(60) NOT NULL";
		$fields['action']=databaseDataType('tinyint')." NOT NULL Default 0";
		$fields['mtime']="TIME NOT NULL";
		$ok = createDBTable($table,$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"client"));
		//_tabledata
		$id=addDBRecord(array('-table'=>'_tabledata',
			'tablegroup'	=> 'wdns',
			'tablename'		=> $table,
			'formfields'	=> "client\r\naction op mtime",
			'listfields'	=> "client\r\naction\r\nop\r\nmtime",
		));
		//_fielddata
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'action',
			'inputtype'		=> 'select',
			'required'		=> 0,
			'tvals'			=> "0\r\n1",
			'dvals'			=> "Block\r\nAllow"
		));
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'op',
			'inputtype'		=> 'select',
			'required'		=> 0,
			'tvals'			=> "0\r\n1",
			'dvals'			=> "Before\r\nAfter"
		));
	}
	//wdns_alerts
	if(!isDBTable('wdns_alerts')){
		$table='wdns_alerts';
		$fields=array(
			'_id'		=> databasePrimaryKeyFieldString(),
			'_cdate'	=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'	=> "INT NOT NULL",
			'_edate'	=> databaseDataType('datetime')." NULL",
			'_euser'	=> "INT NULL",
		);
		//operator: 0=before, 1=after
		$fields['client']="varchar(60) NOT NULL";
		$fields['filter']="varchar(255) NOT NULL";
		$fields['email']="varchar(255) NOT NULL";
		$ok = createDBTable($table,$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"client"));
		//_tabledata
		$id=addDBRecord(array('-table'=>'_tabledata',
			'tablegroup'	=> 'wdns',
			'tablename'		=> $table,
			'formfields'	=> "client\r\nfilter\r\nemail",
			'listfields'	=> "client\r\nfilter\r\nemail",
		));
		//_fielddata
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'client',
			'inputtype'		=> 'text',
			'width'			=> 200,
			'required'		=> 1,
			'placeholder'	=> "Client IP, Machine Name, or ALL",
		));
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'filter',
			'inputtype'		=> 'text',
			'required'		=> 1,
			'width'			=> 200,
			'placeholder'	=> "filter"
		));
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> $table,
			'fieldname'		=> 'email',
			'inputtype'		=> 'text',
			'mask'			=> 'email',
			'required'		=> 1,
			'width'			=> 200,
			'placeholder'	=> "email or phone email"
		));
	}
	//wdns_logs?
	if(!isDBTable('wdns_logs')){
		$table='wdns_logs';
		$fields=array(
			'_id'		=> databasePrimaryKeyFieldString(),
			'_cdate'	=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'	=> "INT NOT NULL",
			'_edate'	=> databaseDataType('datetime')." NULL",
			'_euser'	=> "INT NULL",
		);
		$fields['name']="varchar(225) NOT NULL";
		$fields['return_ip']="varchar(40) NOT NULL";
		$fields['client_ip']="varchar(40) NOT NULL";
		$fields['client_name']="varchar(120) NULL";
		$fields['filter_id']="int NOT NULL Default 0";
		$fields['exception_id']="int NOT NULL Default 0";
		$fields['schedule_id']="int NOT NULL Default 0";
		$fields['alert_id']="int NOT NULL Default 0";
		$fields['action']=databaseDataType('tinyint')." NOT NULL Default 0";
		$ok = createDBTable($table,$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"client_name"));
		$ok=addDBIndex(array('-table'=>$table,'-fields'=>"action"));
		//_tabledata
		$id=addDBRecord(array('-table'=>'_tabledata',
			'tablegroup'	=> 'wdns',
			'tablename'		=> $table,
			'formfields'	=> "name action filter_id exception_id\r\nclient_ip return_ip",
			'listfields'	=> "_cdate\r\nname\r\naction\r\nfilter_id\r\nexception_id\r\nclient_ip\r\nclient_name\r\nreturn_ip",
		));
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $table,
			'fieldname'		=> 'action',
			'inputtype'		=> 'select',
			'tvals'			=> "0\r\n1",
			'dvals'			=> "Block\r\nAllow",
			'required'		=> 1,
			'defaultval'	=> 0
		));
	}
	return;
}
?>