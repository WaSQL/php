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
include_once("{$progpath}/common.php");
//only allow this to be run from CLI
if(!isCLI()){
	echo "postedit_notepad.php is a command line app only.".PHP_EOL;
	exit;
}
//determine the postedit path
$ppath=getWasqlPath('postedit/postEditFiles');
//get a list of directories
$dirs=listFilesEx($ppath,array('type'=>'dir'));
//build me a list of valid dirs
$vdirs=array();
foreach($dirs as $dir){
	if(stringEndsWith($dir['name'],'_bak')){continue;}
	$vdirs[]=$dir['afile'];
}
//kill notepad if it is running
$cmd="taskkill /IM \"notepad++.exe\" /F";
$out=cmdResults($cmd);
//make a arg list with each vdir wrapped in quotes
$vdirstr='"'.implode('" "',$vdirs).'"';
$cmd="notepad++ -openFoldersAsWorkspace {$vdirstr}";
//echo $cmd;exit;
$out=cmdResults($cmd);
if($out['rtncode'] !=0){
	echo printValue($out).PHP_EOL;
}
exit;

