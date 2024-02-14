<?php
/*
	Instructions:
		run postedit_notepad.php from a command-line 
	Note: postedit_notepad.php cannot be run from a URL, it is a command line app only.
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
//kill notepad if it is running
$cmd="taskkill /IM \"notepad++.exe\" /F";
$out=cmdResults($cmd);
$args=$argv;
array_shift($args);
//make a arg list with each vdir wrapped in quotes
$argstr='""'.implode('"" ""',$args).'""';
$cmd="notepad++.exe -openFoldersAsWorkspace {$argstr}";
$vbs=<<<ENDOFVBS
Set objShell = WScript.CreateObject("WScript.Shell")
objShell.Run "{$cmd}",0,false
Set objShell = Nothing
ENDOFVBS;
setFileContents("{$progpath}/pn.vbs",$vbs);
$cmd="wscript {$progpath}/pn.vbs";
exec($cmd);
//unlink("{$progpath}/pn.vbs");
exit(0);

