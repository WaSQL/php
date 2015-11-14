<?php
/* 
	https://github.com/kbjr/Git.php

*/
$progpath=dirname(__FILE__);
require_once("{$progpath}/git/Git.php");
//---------- begin function gitDiff--------------------------------------
/**
* @describe executes git diff command on file
* @param dir string - the directory of your git repo
* @param file string -  fill to diff
* @return array
*	returns the results of executing the command in a key/value array
*		branch
*		diff
*		raw - raw lines returned before parsing
*/
function gitDiff($dir='',$file){
	$repo = Git::open($dir);
	return $repo->diff($file);
}
//---------- begin function gitLog--------------------------------------
/**
* @describe executes git log command on file
* @param dir string - the directory of your git repo
* @param file string -  fill to show log of
* @return array
*	returns the results of executing the command in a key/value array
*		branch
*		diff
*		raw - raw lines returned before parsing
*/
function gitLog($dir='',$file){
	$repo = Git::open($dir);
	return $repo->log($file);
}
//---------- begin function gitPull--------------------------------------
/**
* @describe executes git pull command on file
* @param dir string - the directory of your git repo
* @param [remote] string -  remote
* @param [branch] string - branch
* @return array
*/
function gitPull($dir='',$remote='',$branch=''){
	$repo = Git::open($dir);
	return $repo->pull($remote,$branch);
}

//---------- begin function gitAdd--------------------------------------
/**
* @describe executes git pull command on file
* @param dir string - the directory of your git repo
* @param files array -  files to add
* @return array
*/
function gitAdd($dir='',$files=array()){
	$repo = Git::open($dir);
	return $repo->add($files);
}

//---------- begin function gitCheckout--------------------------------------
/**
* @describe executes git checkout command on file - used to remove local changes
* @param dir string - the directory of your git repo
* @param files array -  files to checkout
* @return array
*/
function gitCheckout($dir='',$files=array()){
	$repo = Git::open($dir);
	$filestr=implode('" "',$files);
	$cmd="checkout \"{$filestr}\"";
	//echo printValue($cmd);exit;
	$repo->run($cmd);
}

//---------- begin function gitCommit--------------------------------------
/**
* @describe executes git commit command on the file
* @param dir string - the directory of your git repo
* @param msg string
* @param file string
* @param files array -  files to add
* @return array
*/
function gitCommit($dir='',$msg,$file){
	$repo = Git::open($dir);
	$config=gitConfigList($dir);
	if(!isset($config['user.name']) && isset($_REQUEST['name'])){
		$repo->run('config user.name "'.$_REQUEST['name'].'"');
	}
	if(!isset($config['user.email']) && isset($_REQUEST['email'])){
		$repo->run('config user.email "'.$_REQUEST['email'].'"');
	}
	$config=gitConfigList($dir);
	if(!isset($config['user.name'])){return 'gitCommit error: no user.name set';}
	if(!isset($config['user.email'])){return 'gitCommit error: no user.email set';}
	$repo = Git::open($dir);
	return $repo->commit($msg,$file);
	/*
		Commit[master a873007] git Manager
 		1 file changed, 87 insertions(+)
 		create mode 100644 php/admin/git_body.htm

	*/
}

//---------- begin function gitPush--------------------------------------
/**
* @describe executes git push command
* @param dir string - the directory of your git repo
* @return array
*/
function gitPush($dir=''){
	$repo = Git::open($dir);
	return $repo->run('push');
}

//---------- begin function gitPush--------------------------------------
/**
* @describe executes git push command
* @param dir string - the directory of your git repo
* @return array
*/
function gitConfigList($dir=''){
	global $gitConfigList;
	if(is_array($gitConfigList) && count($gitConfigList)){
    	return $gitConfigList;
	}
	$repo = Git::open($dir);
	$rtn=$repo->run('config -l');
	$lines=preg_split('/[\r\n]+/',trim($rtn));
	$config=array('repo_path'=>$repo->get_repo_path());
	foreach($lines as $line){
    	list($key,$val)=preg_split('/\=/',$line,2);
    	if(strlen($key) && strlen($val)){
        	$config[$key]=$val;
		}
	}
	$gitConfigList=$config;
	return $config;
}

//---------- begin function gitStatus--------------------------------------
/**
* @describe executes git status command and returns result in a key/value array
* @param dir string - the directory of your git repo
* @return array
*	returns the results of executing the command in a key/value array
*		branch
*		status
*		new - array of uncommitted files found
*		modified - array of modified files found
*		raw - raw lines returned before parsing
*/
function gitStatus($dir='',$format='array'){
	$repo = Git::open($dir);
	return $repo->status($format);
}

?>