<?php
/*
	Instructions:
		run dbclean.php from a command-line 
	Note: dbclean.php cannot be run from a URL, it is a command line app only.
*/
//set time limit to a large number so the dbclean does not time out
ini_set('max_execution_time', 72000);
set_time_limit(72000);
error_reporting(E_ALL & ~E_NOTICE);

$progpath=dirname(__FILE__);
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("$progpath/common.php");
//only allow this to be run from CLI
if(!isCLI()){
	dbcleanMessage("dbclean.php is a command line app only.");
	exit;
}
$_SERVER['TIME_START']=microtime(true);
global $ConfigXml;
global $allhost;
global $dbh;
global $sel;
global $CONFIG;
$_SERVER['HTTP_HOST']='localhost';
include_once("$progpath/config.php");
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("$progpath/wasql.php");
include_once("$progpath/database.php");
include_once("$progpath/user.php");
global $databaseCache;
//dbcleanMessage(count($ConfigXml).' hosts in config file');
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
	if(isset($CONFIG['dbclean']) && $CONFIG['dbclean']==0){
		//dbcleanMessage("dbclean set to 0");
    	continue;
	}
	ksort($CONFIG);
	//echo printValue($CONFIG);
	//connect to this database.
	$dbh='';
	//dbcleanMessage("connecting");
	$ok=dbcleanDBConnect();
	if($ok != 1){
    	dbcleanMessage("failed to connect: {$ok}");
    	continue;
	}
	//1 day
	$tables=array('_sessions','_minify');
	foreach($tables as $table){
		if(!isDBTable($table)){continue;}
		$ok=cleanupDBRecords($table,1);
	}
	//10 days
	$tables=array('_cronlog');
	foreach($tables as $table){
		if(!isDBTable($table)){continue;}
		$ok=cleanupDBRecords($table,10);
	}
}
exit;
//---------- begin function dbcleanDBConnect
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function dbcleanMessage($msg){
	global $CONFIG;
	global $mypid;
	if(!strlen($mypid)){$mypid=getmypid();}
	$ctime=time();
	echo "{$ctime},{$mypid},{$CONFIG['name']},{$msg}".PHP_EOL;
	return;
}
//---------- begin function dbcleanDBConnect
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function dbcleanDBConnect(){
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
