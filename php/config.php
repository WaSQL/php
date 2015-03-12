<?php
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
else{abort("Configuration Error: No config.xml configuration file found.");}
global $CONFIG;
$CONFIG=array();
if(!isset($_SERVER['UNIQUE_HOST'])){parseEnv();}
/* Load Global configurations from allhost if it exists */
$ConfigXml=array();
$allhost=array();
if(is_object($xml->allhost)){
	foreach($xml->allhost->attributes() as $okey=>$oval){
		$key=(string) trim($okey);
		$val=(string) trim($oval);
		$allhost[$key]=$val;
        }
    }
if(is_object($xml->host)){
	foreach($xml->host as $host){
		$chost=array();
		foreach($host->attributes() as $okey=>$oval){
			$key=strtolower((string) trim($okey));
			$val=(string) trim($oval);
			$chost[$key]=$val;
        	}
        $name=(string)$chost['name'];
        //foreach($allhost as $key=>$val){$ConfigXml[$name][$key]=$val;}
        foreach($chost as $key=>$val){$ConfigXml[$name][$key]=$val;}
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
if(strlen($chost)){
	//ksort($CONFIG);echo printValue($CONFIG);exit;
	$sameas='';
	if(isset($ConfigXml[$chost]['sameas']) && isset($ConfigXml[$ConfigXml[$chost]['sameas']])){
		$sameas=$ConfigXml[$chost]['sameas'];
		foreach($ConfigXml[$sameas] as $key=>$val){
			$CONFIG[$key]=$val;
		}
	}
	//ksort($CONFIG);echo printValue($CONFIG);exit;
	foreach($ConfigXml[$chost] as $key=>$val){
		$CONFIG[$key]=$val;
	}
}

//ksort($CONFIG);echo "chost:{$chost}<br>sameas:{$sameas}<br>".printValue($CONFIG).printValue($ConfigXml);exit;
if(!isset($CONFIG['dbname'])){abort("Configuration Error: No Host found for {$_SERVER['HTTP_HOST']}<hr>\n" . printValue($_SERVER));}
//allow users to override dbhost with dbhost and dbauth
if(isset($_REQUEST['dbhost'])){
	if(isset($ConfigXml[$_REQUEST['dbhost']]) && isset($_REQUEST['dbauth']) && isset($ConfigXml[$_REQUEST['dbhost']]['dbauth']) && $ConfigXml[$_REQUEST['dbhost']]['dbauth']==$_REQUEST['dbauth']){
		$_SESSION['dbhost']=$_REQUEST['dbhost'];
	}
	else{
		unset($_SESSION['dbhost']);
		unset($_SESSION['dbhost_original']);
	}
}
if(isset($_SESSION['dbhost_original']) && !isset($_SESSION['dbhost'])){
	unset($_SESSION['dbhost_original']);
}
if(isset($_SESSION['dbhost']) && isset($ConfigXml[$_SESSION['dbhost']])){
	$CONFIG['_source']=$_SESSION['dbhost'];
	$_SERVER['WaSQL_HOST']=$_SESSION['dbhost'];
	if($_SESSION['dbhost'] != $CONFIG['dbhost']){
    	$_SESSION['dbhost_original']=$CONFIG['dbhost'];
	}
	foreach($ConfigXml[$_SESSION['dbhost']] as $key=>$val){$CONFIG[$key]=$val;}
};
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