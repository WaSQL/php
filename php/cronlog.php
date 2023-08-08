<?php
/*
	Instructions:
		>cronlog.php localhost 234 "this is a message" 
	Note: cronlog.php cannot be run from a URL, it is a command line app only.
*/
//set time limit to a large number so the cronlog does not time out
ini_set('max_execution_time', 72000);
set_time_limit(72000);
error_reporting(E_ALL & ~E_NOTICE);

$progpath=dirname(__FILE__);
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("$progpath/common.php");
$_SERVER['TIME_START']=microtime(true);
global $ConfigXml;
global $allhost;
global $dbh;
global $sel;
global $CONFIG;
global $CRONTHRU;
array_shift($argv);
$_SERVER['HTTP_HOST']=array_shift($argv);
$CRONTHRU['pid']=array_shift($argv);
$message=implode(' ',$argv);
include_once("$progpath/config.php");
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("{$progpath}/wasql.php");
include_once("{$progpath}/database.php");
include_once("{$progpath}/user.php");
$ok=commonCronLog($message);
// setFileContents("{$progpath}/cronlog.log","HOST: {$_SERVER['HTTP_HOST']}".PHP_EOL);
// appendFileContents("{$progpath}/cronlog.log","PID: {$CRONTHRU['pid']}".PHP_EOL);
// appendFileContents("{$progpath}/cronlog.log","MESSAGE: {$message}".PHP_EOL);
// appendFileContents("{$progpath}/cronlog.log","OK: {$ok}".PHP_EOL.PHP_EOL);
exit(0);


