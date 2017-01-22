<?php
/*
	ways to call this script
		actions: 
			getfiles {name}
			push
  		php postedit.php action
*/
global $progpath;
global $hosts;
global $cgroup;
global $chost;
global $argv;
global $mtimes;
ini_set("allow_url_fopen",1);
$mtimes=array();
global $mds;
$mds=array();
$progpath=dirname(__FILE__);
include_once("$progpath/../php/common.php");
getHosts();
$groups=getGroups();
if(isset($argv[1])){
	if(isset($hosts[$argv[1]])){$chost=$argv[1];}
}
if(!isset($chost)){
	if(!isset($cgroup)){$cgroup=selectGroup();}
	$chost=selectHost();
}
//get the files
$tables=isset($hosts[$chost]['apikey'])?$hosts[$chost]['apikey']:'_pages,_templates,_models';
$postopts=array(
	'apikey'	=>$hosts[$chost]['apikey'],
	'username'	=>$hosts[$chost]['username'],
	'_noguid'	=>1,
	'postedittables'=>$tables,
	'apimethod'	=>"posteditxml",
	'-ssl'=>1
);
$url=buildHostUrl();
//echo "{$url}".PHP_EOL;
$post=postURL($url,$postopts);
$xml = simplexml_load_string($post['body'],'SimpleXMLElement',LIBXML_NOCDATA | LIBXML_PARSEHUGE );
$xml=(array)$xml;
$folder=isset($hosts[$chost]['alias'])?$hosts[$chost]['alias']:$hosts[$chost]['name'];
cleanDir("{$progpath}/postEditFiles/{$folder}");
foreach($xml['WASQL_RECORD'] as $rec){
	$rec=(array)$rec;
	$info=$rec['@attributes'];
	//echo printValue($info);exit;
	unset($rec['@attributes']);
	foreach($rec as $name=>$content){
    	if(!strlen($content)){continue;}

    	$path="{$progpath}/postEditFiles/{$folder}/{$info['table']}";
    	if(!is_dir($path)){buildDir($path);}
    	//determine extension
    	$parts=preg_split('/\_/',$name);
    	$field=array_pop($parts);
    	switch(strtolower($field)){
        	case 'js':$ext='js';break;
        	case 'css':$ext='css';break;
        	case 'controller':
			case 'functions':
				$ext='php';
			break;
        	default:
        		$ext='html';
        	break;
		}
    	$afile="{$path}/{$info['name']}.{$info['table']}.{$field}.{$info['_id']}.{$ext}";
    	echo "{$afile}".PHP_EOL;
    	setFileContents($afile,$content);
    	chmod($afile,0777);
    	$mtimes[$afile]=1;
	}
}
echo "Listening to {$folder} for changes...".PHP_EOL;
sleep(2);
foreach($mtimes as $afile=>$mtime){
	$mtimes[$afile]=filemtime($afile);
}
while(1){
	usleep(250);
	foreach($mtimes as $afile=>$mtime){
		$cmtime=filemtime($afile);
    	if($cmtime != $mtime){
	        //file Changed
        	$mtimes[$afile]=filemtime($afile);
        	fileChanged($afile);
		}
	}
}

//echo printValue($xml);
exit;
function buildHostUrl(){
	global $hosts;
	global $chost;
	if(isset($hosts[$chost]['secure']) && $hosts[$chost]['secure'] != 0){$http='https';}
	else{$http='http';}
	$url="{$http}://{$hosts[$chost]['name']}/php/index.php";
	return $url;
}
function fileChanged($afile){
	global $hosts;
	global $chost;
	global $mtimes;
	$afile=fixSlashes($afile);
	//echo $afile;exit;
	$filename=getFileName($afile);
	echo "File changed: {$afile}".PHP_EOL;
	//exit;
	$content=file_get_contents($afile);
	if(!strlen($content)){
    	echo "failed to get content".PHP_EOL;
    	return;
	}
	$content=encodeBase64($content);
	list($fname,$table,$field,$id,$ext)=preg_split('/\./',$filename);
	$postopts=array(
		'apikey'	=>$hosts[$chost]['apikey'],
		'username'	=>$hosts[$chost]['username'],
		'_noguid'	=>1,
		'_base64'	=>1,
		'_id'		=>$id,
		'timestamp'	=>$mtimes[$afile],
		'_action'	=>'postEdit',
		'_table'	=>$table,
		'_fields'	=>$field,
		$field		=>$content,
		'_return'	=>'XML',
		'-xml'		=>1
	);
	$url=buildHostUrl();
	$post=postURL($url,$postopts);
	setFileContents('postedit_filechanged.last',printValue($post));
	return true;
}
###############
function fixSlashes($str){
	if(isWindows()){$slash="\\";}
	else{$slash="/";}
	$tmp=preg_split('/[\\/]+/',$str);
	return implode($slash,$tmp);
}
function selectHost(){
	global $cgroup;
	global $hosts;
	global $argv;
	if(!is_array($hosts)){getHosts();}
	$groups=getGroups();
	$lines=array();
	$x=1;
	$map=array();
	foreach($hosts as $name=>$host){
		if(strtolower($host['group']) != $cgroup){continue;}
    	$lines[]=" {$x}-{$name}";
    	$map[$x]=$name;
    	$x+=1;
	}
	//check for command line input
	if(isset($argv[2])){
		if(isset($map[$argv[2]])){return $map[$argv[2]];}
		if(isset($hosts[$argv[2]])){return $argv[2];}
	}
	while(1){
		echo "Select a Host:".PHP_EOL;
		echo implode("\r\n",$lines);
		echo "\r\nSelection: ";
		$s = stream_get_line(STDIN, 1024, PHP_EOL);
		$s=strtolower($s);
		if(isset($map[$s])){
			return $map[$s];
		}
		elseif(isset($hosts[$s])){
			return $s;
		}
		else{
        	echo "\r\nInvalid host entry".PHP_EOL;
		}
	}
}
function selectGroup(){
	global $argv;
	$groups=getGroups();
	$lines=array();
	$x=1;
	$map=array();
	foreach($groups as $group=>$cnt){
    	$lines[]=" {$x}-{$group}";
    	$map[$x]=$group;
    	$x+=1;
	}
	//check for command line input
	if(isset($argv[1])){
		if(isset($map[$argv[1]])){return $map[$argv[1]];}
		if(isset($groups[$argv[1]])){return $argv[1];}
	}
	while(1){
		echo "Select a Group:".PHP_EOL;
		echo implode("\r\n",$lines);
		echo "\r\nSelection: ";
		$s = stream_get_line(STDIN, 1024, PHP_EOL);
		$s=strtolower($s);
		if(isset($map[$s])){
			return $map[$s];
		}
		elseif(isset($groups[$s])){
			return $s;
		}
		else{
        	echo "\r\nInvalid group entry".PHP_EOL;
		}
	}
}
function getGroups(){
	global $hosts;
	if(!is_array($hosts)){getHosts();}
	$groups=array();
	foreach($hosts as $name=>$host){
		$group=strtolower($host['group']);
		if(isset($groups[$group])){$groups[$group]+=1;}
		else{$groups[$group]=1;}
	}
	ksort($groups);
	return $groups;
}
function getHosts(){
	global $progpath;
	global $hosts;
	if(!file_exists("$progpath/postedit.xml")){
		echo "Unable to find postedit.xml file";
		exit;
	}
	$xmldata=getFileContents("$progpath/postedit.xml");
	$xml = (array)readXML("<postedit>{$xmldata}</postedit>");
	$hosts=array();
	foreach($xml['hosts']->host as $xhost){
	    	$xhost=(array)$xhost;
	    	$name=$xhost['@attributes']['name'];
	    	foreach($xhost['@attributes'] as $k=>$v){
			$hosts[$name][$k]=$v;
			if(isset($xhost['@attributes']['alias'])){
            	$alias=$xhost['@attributes']['alias'];
            	$hosts[$alias]=$hosts[$name];
			}
		}
	}
	return;
}
?>