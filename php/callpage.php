<?php
/*
	Instructions:
		run callpage.php from a command-line to make it easier to run a cron page
		Note: callpage.php cannot be run from a URL, it is a command line app only.
	Example
		php callpage.php -host=localhost -page=testme/a/b/c name=bob age=8
	Crontab Example running every minute
		* * * * * php /var/www/wasql/php/callpage.php -host=localhost -page=testme/a/b/c name=bob age=8 >/var/www/wasql/php/callpage.log 2>&1
*/
//set time limit to a large number so the callpage does not time out
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
	echo "callpage.php is a command line app only.";
	exit;
}
//convert argv into $_REQUEST
foreach($argv as $str){
	$parts=preg_split('/\=/',$str,2);
	if(count($parts)==2){
		$key=strtolower(trim($parts[0]));
		$val=trim($parts[1]);
		if($key=='-page'){
			$parts=preg_split('/\/+/',$val);
			$_REQUEST[$key]=array_shift($parts);
			$_REQUEST['passthru']=$parts;
		}
		else{
			$_REQUEST[$key]=$val;
		}
	}
}
if(!isset($_REQUEST['-page'])){
	echo "no -page specified";exit;
}
global $dbh;
global $sel;
global $CONFIG;
//host
if(isset($_REQUEST['-host'])){
	$_SERVER['HTTP_HOST']=$_REQUEST['-host'];
}
else{
	$_SERVER['HTTP_HOST']='callpage';
}
include_once("$progpath/config.php");
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("$progpath/wasql.php");
include_once("$progpath/database.php");
include_once("$progpath/user.php");
echo includePage($_REQUEST['-page'],$_REQUEST);
exit;
