<?php
/*
	replacement for posteditd.pl to handle secure sites
*/
global $progpath;
global $hosts;
global $settings;
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
getSettings();
$groups=getGroups();
if(isset($argv[1])){
	if(isset($hosts[$argv[1]])){$chost=$argv[1];}
}
if(!isset($chost)){
	if(!isset($cgroup)){$cgroup=selectGroup();}
	$chost=selectHost();
}
// acquire an exclusive lock
$lock=preg_replace('/[^a-z0-9]+/i','',$chost);
global $lockfile;
$lockfile="{$progpath}/{$lock}_lock.txt";
global $pid;
$pid=getmypid();
echo "obtaining lock: {$lockfile}".PHP_EOL;
file_put_contents($lockfile,$pid);
echo "{$lockfile} is now mine".PHP_EOL;
//get the files
$afolder=writeFiles();
echo PHP_EOL."Listening to file in {$afolder} for changes...".PHP_EOL;
$ok=soundAlarm('ready');
while(1){
	sleep(1);
	shutdown_check();
	foreach($mtimes as $afile=>$mtime){
		$cmtime=filemtime($afile);
    	if($cmtime != $mtime){
	        //file Changed
        	$mtimes[$afile]=filemtime($afile);
        	fileChanged($afile);
		}
	}
}
exit;
function shutdown_check(){
	global $lockfile;
	global $pid;
	$tpid=getFileContents($lockfile);
	if(trim($tpid) != $pid){
		echo "Another postedit process took control. Exiting.".PHP_EOL;
		exit;
	}
}

function writeFiles(){
	global $hosts;
	global $chost;
	global $progpath;
	global $mtimes;
	$tables=isset($hosts[$chost]['tables'])?$hosts[$chost]['tables']:'_pages,_templates,_models';
	$postopts=array(
		'apikey'	=>$hosts[$chost]['apikey'],
		'username'	=>$hosts[$chost]['username'],
		'_noguid'	=>1,
		'postedittables'=>$tables,
		'apimethod'	=>"posteditxml",
		'encoding'	=>"base64",
		'-nossl'=>1,
		'-follow'=>1,
		'-xml'=>1
	);
	$url=buildHostUrl();
	echo "Calling {$url}...".PHP_EOL;
	$post=postURL($url,$postopts);
	if(isset($post['error']) && strlen($post['error'])){
		abortMessage($post['error']);
	}
	elseif(isset($post['xml_array']['result']['fatal_error'])){
		$msg=str_replace('&quot;','"',$post['xml_array']['result']['fatal_error']);
		$msg=str_replace('&gt;','>',$msg);
		$msg=str_replace('&lt;','<',$msg);
		abortMessage($msg);
	}
	//check for login form
	if(preg_match('/\"\_login\"/is',$post['body'])){
    	abortMessage("INVALID LOGIN CREDENTIALS");
	}
	file_put_contents("{$progpath}/postedit_pages.result",$post['body']);
	$xml = simplexml_load_string($post['body'],'SimpleXMLElement',LIBXML_NOCDATA | LIBXML_PARSEHUGE );
	$xml=(array)$xml;
	if(isset($post['curl_info']['http_code']) && $post['curl_info']['http_code'] != 200){
    	abortMessage("{$post['curl_info']['http_code']} error retrieving files");
	}
	elseif(isset($xml['fatal_error'])){
		$msg=str_replace('&quote;','"',$xml['fatal_error']);
		$msg=str_replace('&gt;','>',$msg);
		$msg=str_replace('&lt;','<',$msg);
		abortMessage($msg);
	}


	$folder=isset($hosts[$chost]['alias'])?$hosts[$chost]['alias']:$hosts[$chost]['name'];
	$afolder="{$progpath}/postEditFiles/{$folder}";
	if(is_dir($afolder)){cleanDir($afolder);}
	else{
		mkdir($afolder,0777,true);
	}
	foreach($xml['WASQL_RECORD'] as $rec){
		$rec=(array)$rec;
		$info=$rec['@attributes'];
		unset($rec['@attributes']);
		foreach($rec as $name=>$content){
	    	if(!strlen(trim($content))){continue;}
	    	$path="{$afolder}/{$info['table']}";
	    	if(!is_dir($path)){
				mkdir($path,0777,true);
			}
	    	//determine extension
	    	$parts=preg_split('/\_/',ltrim($name,'_'),2);
	    	//echo $name.printValue($parts);
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
			$name=preg_replace('/[^a-z0-9\ \_\-]+/i','',$info['name']);
	    	$afile="{$path}/{$name}.{$info['table']}.{$field}.{$info['_id']}.{$ext}";
	    	//echo "{$afile}".PHP_EOL;
	    	$content=base64_decode(trim($content));
	    	file_put_contents($afile,$content);
	    	$mtimes[$afile]=1;
		}
	}
	sleep(1);
	echo "  setting baseline modify times.".PHP_EOL;
	foreach($mtimes as $afile=>$x){
		$mtimes[$afile]=filemtime($afile);
	}
	if(isWindows()){
		$afolder=preg_replace('/\//',"\\",$afolder);
		cmdResults("EXPLORER /E,\"{$afolder}\"");
	}
	return $afolder;
}
function buildHostUrl(){
	global $hosts;
	global $chost;
	if(isset($hosts[$chost]['insecure']) && $hosts[$chost]['insecure'] == 1){$http='http';}
	else{$http='https';}
	$url="{$http}://{$hosts[$chost]['name']}/php/index.php";
	return $url;
}
function fileChanged($afile){
	global $progpath;
	global $hosts;
	global $chost;
	global $mtimes;
	$filename=getFileName($afile);
	echo "  {$filename}";
	$content=@file_get_contents($afile);
	if(!strlen($content) && isWindows()){
		$content=getContents($afile);
		if(!strlen($content)){
    		$ok=errorMessage(" - failed to get content");
    		return;
		}
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
		'-nossl'=>1,
		'-follow'=>1,
		'-xml'		=>1
	);
	$url=buildHostUrl();
	$post=postURL($url,$postopts);
	file_put_contents("{$progpath}/postedit_change.post",printValue($post));
	file_put_contents("{$progpath}/postedit_change.result",$post['body']);
POSTFILE:
	$xml=array();
	$json=array();
	if(isset($post['curl_info']['http_code']) && $post['curl_info']['http_code'] == 200){
		$xml = (array)readXML("<postedit>{$post['body']}</postedit>");
		$json=json_encode($xml);
	}
	if(isset($post['curl_info']['http_code']) && $post['curl_info']['http_code'] != 200){
    	abortMessage("{$post['curl_info']['http_code']} error posting file to server");
	}
	elseif(isset($xml['fatal_error'])){
    	abortMessage(" - Fatal error posting");
	}
	elseif(isset($xml['refresh_error'])){
		$ok=errorMessage($xml['refresh_error']." attention required");
    	echo "   Refresh Now? Y or N: ";
		$s = stream_get_line(STDIN, 1024, PHP_EOL);
		$s=strtolower($s);
		if($s != 'n'){
        	writeFiles();
		}
	}
	elseif(isset($xml['error'])){
		$ok=errorMessage($xml['error']. "attention required");
    	echo "   Overwrite Anyway? Y or N: ";
		$s = stream_get_line(STDIN, 1024, PHP_EOL);
		$s=strtolower($s);
		if($s == 'y'){
			$postopts['_overwrite']=1;
        	$post=postURL($url,$postopts);
        	goto POSTFILE;
		}
	}
	$ok=successMessage(" - Successfully updated");
	return true;
}
function abortMessage($msg){
	global $settings;
	global $progpath;
	global $lockfile;
	$msg=trim($msg);
	echo "Fatal Error: {$msg}".PHP_EOL;
	echo $progpath;
	if(isWindows()){
		$ok=soundAlarm('abort');
	}
	unlink($lockfile);
	exit;
}
function errorMessage($msg){
	$msg=trim($msg);
	echo " - Error: {$msg}".PHP_EOL;
	if(isWindows()){
		$ok=soundAlarm('error');
	}
	return;
}
function successMessage($msg){
	global $settings;
	global $progpath;
	global $chost;
	$msg=trim($msg);
	echo " - Success: {$msg}".PHP_EOL;
	if(isWindows()){
		$ok=soundAlarm('success');
	}
	return;
}
function soundAlarm($type='success'){
	global $settings;
	global $progpath;
	global $chost;
	if(isset($settings['sound'][$type])){
		if(is_file("{$progpath}/{$settings['sound'][$type]}")){
			$cmd="{$progpath}\\sounder.exe {$progpath}\\{$settings['sound'][$type]}";
			$ok=exec($cmd);
			return;
		}
		elseif(isset($settings['sound']['gender'])){
			$soundmsg=$settings['sound'][$type];
			$soundmsg=str_replace('%name%',$chost,$soundmsg);
			switch(strtolower($settings['sound']['gender'])){
				case 'f':
				case 'female':
					$cmd="{$progpath}\\voice.exe -v 100 -r 1 -f -d \"{$soundmsg}\"";
				break;
				default:
					$cmd="{$progpath}\\voice.exe -v 100 -r 1 -m -d \"{$soundmsg}\"";
				break;
			}
			$ok=exec($cmd);
			return;;
		}
		else{
			echo "\x07";
			return;
		}
	}
}
function getContents($file){
	$file=preg_replace('/\//',"\\",$file);
	$cmd="file_get_contents.exe \"{$file}\"";
	//echo $cmd.PHP_EOL;
	$out=cmdResults($cmd);
	return $out['stdout'];
	$tries=0;
	while(!isset($out['stdout']) && $tries < 5){
    	sleep(1);
    	$out=cmdResults($cmd);
    	$tries++;
	}
	return $out['stdout'];

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
	global $xml;
	if(!isset($xml['hosts'])){getXml();}
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
function getSettings(){
	global $progpath;
	global $settings;
	global $xml;
	if(!isset($xml['hosts'])){getXml();}
	$settings=array();
	if(!isset($xml['settings'])){
		return;
	}
	foreach($xml['settings']->sound as $set){
	    	$set=(array)$set;
	    	foreach($set['@attributes'] as $k=>$v){
			$settings['sound'][$k]=$v;
		}
	}
	return;
}
function getXml(){
	global $progpath;
	global $xml;
	if(!isset($xml['hosts'])){
		if(!file_exists("$progpath/postedit.xml")){
			abortMessage("Unable to find postedit.xml file");
		}
		$xmldata=getFileContents("$progpath/postedit.xml");
		$xml = (array)readXML("<postedit>{$xmldata}</postedit>");
	}
	return;
}
?>
