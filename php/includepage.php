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
	header("Content-type: text/plain");
	echo "includpage.php is a command line app only.";
	exit(1);
}
global $CONFIG;
global $PAGE;
$PAGE=array(
	'_id'=>0,
	'name'=>'includepage_cli_name_'.time(),
	'permalink'=>'includepage_cli_permalink'.time()
);
if(!isset($argv[2])){
	echo "usage:".PHP_EOL;
	echo "php includepage.php {hostname} {pagename}/{passthru} [x=1,y=2]".PHP_EOL;
	echo " - example:  php includepage.php localhost test/one/two/three".PHP_EOL;
	exit(1);
}
//set hostname based on first argv
$hostname=$argv[1];
//set pagename based on second argv
$pagename=$argv[2];
//set _view
$_REQUEST['_view']=$PAGE['name'];
$_SERVER['HTTP_HOST']=$hostname;
//load config now that http_host is set so we connect to the right db
include_once("$progpath/config.php");
if($CONFIG['name'] != $hostname){
	echo "error: invalid hostname".PHP_EOL;
	exit(2);
}
//check for timezone override
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("$progpath/wasql.php");
include_once("$progpath/database.php");
include_once("$progpath/user.php");
//check for additional params to send to includePage as $_REQUEST key/value pairs
$params=array();
if(isset($argv[3]) && strlen($argv[3])){
	$sets=preg_split('/[\,\&\;]+/s',$argv[3]);
	foreach($sets as $set){
		list($k,$v)=preg_split('/\=/',$set);
		$k=strtolower(trim($k));
		$params[$k]=trim($v);
	}
}
//return the results of includePage function
echo includePage($pagename,$params);
//exit cleanly
exit(0);
