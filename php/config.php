<?php
/*
	Config.php - reads config.xml to determine host to connect to.

	*** NOTE *** 
	Do NOT modify this file.  Make changes to config.xml instead.
*/
/*******************************************************************/
$progpath=dirname(__FILE__);
//require common to be loaded first
//Read in the WaSQL configuration xml file to determine what database to connect to
if(file_exists("$progpath/config.xml")){
	//found confix.xml in the php directory
	$xml = readXML("$progpath/config.xml");
	}
elseif(file_exists("$progpath/../config.xml")){
	$xml = readXML("$progpath/../config.xml");
	}
else{
	abort("Configuration Error: missing config.xml<hr>".PHP_EOL);
}
//convert object to array
$json=json_encode($xml);
$xml=json_decode($json,true);
//check for single host
if(isset($xml['host']['@attributes'])){
	$xml['host']=array($xml['host']);
}
//check for single database
if(isset($xml['database']['@attributes'])){
	$xml['database']=array($xml['database']);
}
//remove comments
if(isset($xml['comment'])){
	unset($xml['comment']);
}
global $DATABASE;
global $CONFIG;
global $ALLCONFIG;
$CONFIG=array();
if(!isset($_SERVER['UNIQUE_HOST'])){parseEnv();}
/* Load Global configurations from allhost if it exists */
$DATABASE=array();
if(isset($xml['database'][0])){
	foreach($xml['database'] as $database){
		if(!isset($database['@attributes']['name'])){continue;}
		$name=$database['@attributes']['name'];
		$name=strtolower($name);
		foreach($database['@attributes'] as $k=>$v){
			$DATABASE[$name][$k]=$v;
		}
	}
}
$ConfigXml=array();
$allhost=array();
if(isset($xml['allhost']['@attributes'])){
	foreach($xml['allhost']['@attributes'] as $k=>$v){
		$k=strtolower(trim($k));
		$lv=strtolower(trim($v));
		if($k=='database' && isset($DATABASE[$lv])){
			foreach($DATABASE[$lv] as $dk=>$dv){
				if(strtolower($dk)=='name'){continue;}
				$allhost[$dk]=trim($dv);
			}
		}
		$allhost[$k]=trim($v);
	}
}

if(isset($xml['host'][0]['@attributes'])){
	foreach($xml['host'] as $host){
		$chost=array();
		foreach($host['@attributes'] as $k=>$v){
			$k=strtolower(trim($k));
			$lv=strtolower(trim($v));
			//echo "{$host['@attributes']['name']},{$k},{$lv}<br>".PHP_EOL;
			if($k=='database' && isset($DATABASE[$lv])){
				foreach($DATABASE[$lv] as $dk=>$dv){
					if(strtolower($dk)=='name'){continue;}
					$chost[$dk]=trim($dv);
				}
			}
			$chost[$k]=trim($v);
		}
        $name=$chost['name'];
        foreach($chost as $k=>$v){$ConfigXml[$name][$k]=$v;}
	}
}
//Check for HTTP_HOST
$checkhosts=array('HTTP_HOST','UNIQUE_HOST','SERVER_NAME');
$chost='';
foreach($checkhosts as $env){
	$checkhost=strtolower($_SERVER[$env]);
	if(isset($ConfigXml[$checkhost]) && is_array($ConfigXml[$checkhost])){
		$CONFIG['_source']=$env;
		$_SERVER['WaSQL_HOST']=$env;
		$chost=$checkhost;
		break;
	}
}
//allhost, then sameas, then chost
foreach($allhost as $key=>$val){
	$CONFIG[$key]=$val;
}

//echo $chost.printValue($CONFIG).printValue($_SERVER);exit;
foreach($ConfigXml as $name=>$host){
	foreach($allhost as $key=>$val){
		$ALLCONFIG[$name][$key]=$val;
	}
	if(isset($ConfigXml[$name]['sameas']) && isset($ConfigXml[$ConfigXml[$name]['sameas']])){
		$sameas=$ConfigXml[$name]['sameas'];
		if(isset($ConfigXml[$sameas]['sameas']) && isset($ConfigXml[$ConfigXml[$sameas]['sameas']])){
			$sameas2=$ConfigXml[$sameas]['sameas'];
			foreach($ConfigXml[$sameas2] as $key=>$val){
				$ALLCONFIG[$name][$key]=$val;
				if(strlen($chost) && $name==$chost){
					$CONFIG[$key]=$val;
				}
			}
		}
		foreach($ConfigXml[$sameas] as $key=>$val){
			$ALLCONFIG[$name][$key]=$val;
			if(strlen($chost) && $name==$chost){
				$CONFIG[$key]=$val;
			}
		}
		
	}
	foreach($ConfigXml[$name] as $key=>$val){
		$ALLCONFIG[$name][$key]=$val;
		if(strlen($chost) && $name==$chost){
			$CONFIG[$key]=$val;
		}
	}
}
//set the icon and displayname
foreach($DATABASE as $d=>$db){
	if(!isset($db['displayname'])){
		$DATABASE[$d]['displayname']=ucwords(str_replace('_',' ',$db['name']));
	}
	if(isset($db['dbicon'])){continue;}
	switch(strtolower($db['dbtype'])){
		case 'postgresql':
		case 'postgres':
			$DATABASE[$d]['dbicon']='icon-database-postgresql';
			//echo printValue($tables);
		break;
		case 'oracle':
			$DATABASE[$d]['dbicon']='icon-database-oracle';
		break;
		case 'mssql':
			$DATABASE[$d]['dbicon']='icon-database-mssql';
		break;
		case 'hana':
			$DATABASE[$d]['dbicon']='icon-database-hana';
		break;
		case 'sqlite':
			$DATABASE[$d]['dbicon']='icon-database-sqlite';
		break;
		case 'mysql':
		case 'mysqli':
			$DATABASE[$d]['dbicon']='icon-database-mysql';
		break;
	}
}
//echo printValue($DATABASE).printValue($CONFIG).printValue($xml);exit;
//ksort($CONFIG);echo "chost:{$chost}<br>sameas:{$sameas}<br>".printValue($CONFIG).printValue($ConfigXml);exit;
if(!isset($CONFIG['dbname'])){
	abort("Configuration Error: missing dbname attribute in config.xml for '{$_SERVER['HTTP_HOST']}'<hr>".PHP_EOL);
}
//echo $checkhost.printValue($CONFIG).printValue($ConfigXml[$checkhost]);exit;
$_SERVER['WaSQL_DBNAME']=$CONFIG['dbname'];
/* Load additional modules as specified in the conf settings */
if(isset($CONFIG['load_modules']) && strlen($CONFIG['load_modules'])){
	$modules=explode(',',$CONFIG['load_modules']);
	loadExtras($modules);
}
elseif(isset($CONFIG['load_extras']) && strlen($CONFIG['load_extras'])){
	$extras=explode(',',$CONFIG['load_extras']);
	loadExtras($extras);
}
//up the memory limit to resolve the "allowed memory" error
if(isset($CONFIG['memory_limit']) && strlen($CONFIG['memory_limit'])){ini_set("memory_limit",$CONFIG['memory_limit']);}
//changes based on config
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
if(isset($CONFIG['post_max_size'])){
	@ini_set('POST_MAX_SIZE', $CONFIG['post_max_size']);
}
if(isset($CONFIG['upload_max_filesize'])){
	@ini_set('UPLOAD_MAX_FILESIZE', $CONFIG['upload_max_filesize']);
}
if(isset($CONFIG['max_execution_time'])){
	@ini_set('max_execution_time', $CONFIG['max_execution_time']);
}
//set encoding to UTF-8 by default, unless overridden in the config
if(isset($CONFIG['encoding'])){
	@mb_internal_encoding($CONFIG['encoding']);
}
else{
	mb_internal_encoding("UTF-8");
}
if(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']]['dbicon'])){
	$CONFIG['dbicon']=$DATABASE[$CONFIG['database']]['dbicon'];
	$CONFIG['displayname']=$DATABASE[$CONFIG['database']]['displayname'];
}
//default dbicon
if(!isset($CONFIG['dbicon'])){
	switch(strtolower($CONFIG['dbtype'])){
		case 'postgresql':
		case 'postgres':
			$CONFIG['dbicon']='icon-database-postgresql';
			//echo printValue($tables);
		break;
		case 'oracle':
			$CONFIG['dbicon']='icon-database-oracle';
		break;
		case 'mssql':
			$CONFIG['dbicon']='icon-database-mssql';
		break;
		case 'hana':
			$DATABASE[$d]['dbicon']='';
		break;
		case 'sqlite':
			$CONFIG['dbicon']='icon-database-sqlite';
		break;
		case 'hana':
			$CONFIG['dbicon']='icon-database-hana';
		break;
		case 'mysql':
		case 'mysqli':
			$CONFIG['dbicon']='icon-database-mysql';
		break;
	}
}
if(!isset($CONFIG['displayname'])){
	$CONFIG['displayname']=$CONFIG['dbname'];
}
ksort($CONFIG);
//echo "elho".printValue($DATABASE);exit;
