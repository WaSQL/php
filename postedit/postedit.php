<?php
/*
	replacement for posteditd.pl to handle secure sites
*/
//set timer to 0 to turn off auto sync.  Otherwise set it to the seconds
$timer=20;

global $progpath;
global $hosts;
global $settings;
global $cgroup;
global $chost;
global $argv;
global $local_shas;
global $firsttime;
global $afolder;
$firsttime=1;
$local_shas=array();
error_reporting(E_ALL ^ E_WARNING);
date_default_timezone_set('America/Denver');
ini_set("allow_url_fopen",1);
$progpath=dirname(__FILE__);
include_once("$progpath/../php/common.php");
// $post['body']=getFileContents("{$progpath}/posteditxmlfromjson.txt");
// $xml = simplexml_load_string($post['body'],'SimpleXMLElement',LIBXML_NOCDATA | LIBXML_PARSEHUGE );
// $xml=(array)$xml;
// echo printValue($xml);
// exit;
getHosts();
getSettings();
if(strtolower($argv[1]) == '--list'){
	ksort($hosts);
	$i=1;
	echo "HOSTS Found in postedit.xml".PHP_EOL;
	$hosts=sortArrayByKeys($hosts,array('alias'=>SORT_ASC));
	foreach($hosts as $key=>$info){
		if(isset($found[$info['name']])){continue;}
		$found[$info['name']]=1;
		echo "	{$i}. {$info['alias']}".PHP_EOL;
		$i+=1;
	}
	exit;
}
if(isset($argv[1])){
	if(isset($hosts[$argv[1]])){$chost=$argv[1];}
}
if(!isset($chost)){
	abortMessage($argv[1]." was not found in postedit.xml. Pass in --list to see list.");
}
// acquire an exclusive lock
$lock=preg_replace('/[^a-z0-9]+/i','',$chost);
global $lockfile;
$lockfile="{$progpath}/{$lock}_lock.txt";
global $noloop;
$noloop="{$progpath}/postedit.noloop";
global $pid;
$pid=getmypid();
echo "obtaining lock: {$lockfile} ...";
file_put_contents($lockfile,$pid);
echo "success".PHP_EOL;
//create the base dir
$folder=isset($hosts[$chost]['alias'])?$hosts[$chost]['alias']:$hosts[$chost]['name'];
$basefolder=$folder;
//check for datestamp
$cday=date('Ymd');
if(isset($hosts[$chost]['datestamp'])){
	$folder .= '_'.$cday;
}

//allow timer to be set in postedit.xml
if(isset($hosts[$chost]['timer'])){
	$timer=(integer)$hosts[$chost]['timer'];
}
//allow timezone to be set
if(isset($hosts[$chost]['timezone'])){
	date_default_timezone_set($hosts[$chost]['timezone']);
}
$afolder="{$progpath}/postEditFiles/{$folder}";
$bfolder="{$progpath}/postEditFiles/{$folder}_bak";
$userfields=array('username');
if(isset($hosts[$chost]['user_fields'])){
	$userfields=preg_split('/\,/',$hosts[$chost]['user_fields']);
}
if(!is_dir($afolder)){
	mkdir($afolder,0777,true);
}
else{
	cli_set_process_title("{$afolder} - cleaning");
	if(file_exists($bfolder)){
		postEditCleanDir($bfolder);
		@rmdir($bfolder);
	}
	posteditCopyDir($afolder,$bfolder);
	postEditCleanDir($afolder);
}
//get the files
writeFiles();
if(file_exists($noloop)){
	echo "files are now in {$afolder}".PHP_EOL;
	echo "noloop file detected. exiting".PHP_EOL;
	exit;
}
file_put_contents("{$progpath}/postedit_shas.txt", printValue($local_shas));
//check for git="1"
if(isset($hosts[$chost]['git']) && $hosts[$chost]['git']==1){
	$cdir=$afolder;
	echo "  -- initializing git in {$cdir}";
	$args=array(
		'pull',
		'add *'
	);
	$fails=0;
	foreach($args as $arg){
		$out=cmdResults('git',$arg,$cdir);
		if($out['rtncode']!=0){
			$ok=errorMessage("git {$arg} FAILED");
			echo printValue($out);
			exit;
			$fails+=1;
		}
	}
	if($fails==0){
		$ok=successMessage('initialized');
	}
}
echo PHP_EOL."Listening to files in {$afolder} for changes...".PHP_EOL;
//$ok=soundAlarm('ready');
$ok=posteditBeep(2);
$countdown=$timer;

while(1){
	cli_set_process_title("{$afolder} - {$countdown} seconds to next check");
	sleep(1);
	shutdown_check();
	//check for local changes
	checkForChanges();
	$countdown-=1;
	if($timer != 0 && $countdown < 1){
		writeFiles();
		$countdown=$timer;
	}
}
exit;
function posteditCopyDir( $source, $target ) {
    if ( is_dir( $source ) ) {
        @mkdir( $target,0777,true );
        $d = dir( $source );
        while ( FALSE !== ( $entry = $d->read() ) ) {
            if ( $entry == '.' || $entry == '..' ) {
                continue;
            }
            $Entry = $source . '/' . $entry; 
            if ( is_dir( $Entry ) ) {
                posteditCopyDir( $Entry, $target . '/' . $entry );
                continue;
            }
            copy( $Entry, $target . '/' . $entry );
        }

        $d->close();
    }else {
        copy( $source, $target );
    }
}
function checkForChanges(){
	global $local_shas;
	global $afolder;
	if(!is_array($local_shas)){return false;}
	foreach($local_shas as $afile=>$sha){
		if(!file_exists($afile)){continue;}
		$csha=posteditSha1($afile);
		if($sha != $csha){
			$name=getFileName($afile);
			cli_set_process_title("{$afolder} - file changed {$name}");
			//echo "  {$afile} changed locally {$sha} != {$csha}".PHP_EOL;
			fileChanged($afile);
		}
	}
	return true;
}
function shutdown_check(){
	global $lockfile;
	global $pid;
	$tpid=getFileContents($lockfile);
	if(trim($tpid) != $pid){
		echo "Another postedit process took control. Exiting.".PHP_EOL;
		exit;
	}
}
/*
	writeFiles:
		submit sha1 of local files to server
		Server responds with any files I need - new or changed
*/
function writeFiles(){
	global $hosts;
	global $chost;
	global $progpath;
	global $settings;
	global $firsttime;
	global $local_shas;
	global $afolder;
	global $userfields;
	$json=posteditGetLocalShas();
	$json=json_encode($json);
	//echo "Local JSON: {$json}".PHP_EOL;
	$url=buildHostUrl();
	cli_set_process_title("{$afolder} - checking {$url}");
	if($firsttime==1){
		echo "checking {$url}".PHP_EOL;
	}
	$tables=isset($hosts[$chost]['tables'])?$hosts[$chost]['tables']:'_pages,_templates,_models';
	//file_put_contents("{$progpath}/postedit_xml.json",$json);
	$postopts=array(
		'apikey'	=>$hosts[$chost]['apikey'],
		'username'	=>$hosts[$chost]['username'],
		'_auth'		=>1,
		'postedittables'=>$tables,
		'apimethod'	=>"posteditxmlfromjson",
		'encoding'	=>"base64",
		'-nossl'=>1,
		'-ipv4'=>1,
		'-follow'=>1,
		'-xml'=>1,
		'json'=>$json
	);
	$post=postURL($url,$postopts);
	if(isset($post['body'])){
		file_put_contents("{$progpath}/posteditxmlfromjson.txt",$post['body']);
	}
	if(isset($post['curl_info']['http_code']) && $post['curl_info']['http_code']==404){
		abortMessage("404 Error - /php/index.php not found");
	}
	if(isset($post['error']) && strlen($post['error'])){
		abortMessage($post['error']);
	}
	elseif(stringBeginsWith($post['body'],'error:')){
		abortMessage($post['body']);
	}
	elseif(stringBeginsWith($post['body'],'You have an error in your SQL syntax;')){
		abortMessage($post['body']);
	}
	elseif(stringContains($post['body'],'form id="loginform"')){
		abortMessage('Invalid Login Credentials');
	}
	elseif(isset($post['xml_array']['result']['fatal_error'])){
		$msg=str_replace('&quot;','"',$post['xml_array']['result']['fatal_error']);
		$msg=str_replace('&gt;','>',$msg);
		$msg=str_replace('&lt;','<',$msg);
		abortMessage($msg);
	}

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
	elseif(!isset($xml['WASQL_RECORD'])){
		return;
	}
	//fix the one record issue
	$xml['WASQL_RECORD']=(array)$xml['WASQL_RECORD'];
	if(isset($xml['WASQL_RECORD']['@attributes'])){
		$xml['WASQL_RECORD']=array($xml['WASQL_RECORD']);
	}
	//echo printValue($xml['WASQL_RECORD']);
	$cnt=count($xml['WASQL_RECORD']);
	if($cnt==0){return;}
	//echo "updating {$cnt} pages".PHP_EOL;
	foreach($xml['WASQL_RECORD'] as $rec){
		$rec=(array)$rec;
		//echo printValue($rec).PHP_EOL;
		$info=$rec['@attributes'];
		unset($rec['@attributes']);
		//echo printValue($info).printValue($rec);exit;
		foreach($rec as $name=>$content){
	    	if(!strlen(trim($content))){continue;}
	    	//determine extension
	    	$parts=preg_split('/\_/',ltrim($name,'_'),2);
	    	//echo $name.printValue($parts);
	    	$field=array_pop($parts);
	    	//name
	    	$name=preg_replace('/[^a-z0-9\ \_\-]+/i','',$info['name']);
	    	$name=trim($name);
	    	//extension
	    	switch(strtolower($field)){
	        	case 'js':
					$ext='js';
					$type='views';
				break;
	        	case 'css':
					$ext='css';
					$type='views';
				break;
	        	case 'controller':
					$ext='php';
					$type='controllers';
				break;
				case 'functions':
					$ext='php';
					$type='models';
				break;
	        	default:
	        		$ext='html';
	        		$type='views';
	        	break;
			}
	    	//path
	    	$path="{$afolder}/{$info['table']}";
	    	switch(strtolower($hosts[$chost]['groupby'])){
				case 'name':$path .= "/{$name}";break;
				case 'type':
					$path .= "/{$type}";
				break;
				case 'field':
					$path .= "/{$field}";
				break;
				case 'ext':
				case 'extension':
					$path .= "/{$ext}";
				break;
			}
	    	if(!is_dir($path)){
				mkdir($path,0777,true);
			}
			$content=base64_decode(trim($content));
			//check content to see if it starts with php
			if(preg_match('/^\<\?php/i',$content)){$ext='php';}
			elseif(preg_match('/^\<\?\=/i',$content)){$ext='php';}
			
	    	$afile="{$path}/{$name}.{$info['table']}.{$field}.{$info['_id']}.{$ext}";
	    	$changename="added";
	    	if(file_exists($afile)){
	    		$changename="changed";
	    	}
	    	file_put_contents($afile,$content);
	    	$shakey=posteditShaKey($afile);
	    	$local_shas[$shakey]=posteditSha1($afile);
	    	if(!isset($info['musername']) && isset($info['user_username'])){
	    		$info['musername']=$info['user_username'];
	    	}
	    	if(isset($info['musername']) && $firsttime != 1 && $hosts[$chost]['username'] != $info['musername']){
	    		$fname=getFileName($afile);
	    		$ftable=str_replace('_',' ',$info['table']);
	    		$changedby=array();
	    		foreach($userfields as $userfield){
	    			$changedby[]=$info["user_{$userfield}"];
	    		}
	    		$changedby=implode(' ',$changedby);
	    		$sfile=getFileName($afile);
	    		$stime=date('Y-m-d H:i:s');
	    		echo "  $stime: {$sfile} was {$changename} by {$changedby}".PHP_EOL;
	    	}
		}
	}
	if($firsttime==1){
		$cmd='';
		if(isset($settings['editor']['command'])){$cmd=$settings['editor']['command'];}
		elseif(isWindows()){$cmd='';}
		if(strlen($cmd)){
			$afolder=preg_replace('/\//',"\\",$afolder);
			$out=cmdResults("{$cmd} \"{$afolder}\"");
			if($out['rtncode'] !=0){
				echo printValue($out).PHP_EOL;
			}
		}
	}
	$firsttime=0;
	return false;
}
function posteditGetLocalShas(){
	global $local_shas;
	global $tables;
	global $hosts;
	global $chost;
	$tables=isset($hosts[$chost]['tables'])?$hosts[$chost]['tables']:'_pages,_templates,_models';
	$tables=preg_split('/\,/',$tables);
	//echo $chost.printValue($hosts[$chost]).printValue($tables);exit;
	$json=array();
	//echo "localshas:".printValue($local_shas).PHP_EOL;
	foreach($local_shas as $file=>$sha){
		list($name,$table,$field,$id,$ext)=preg_split('/\./',getFileName($file));
		//echo "file:{$file}".PHP_EOL;
		//echo "{$name},{$table},{$field},{$id},{$ext} = {$sha}".PHP_EOL;
		$json[$table][$id][$field]=$sha;
	}
	//make sure every table is represented in the jason
	foreach($tables as $table){
		if(!isset($json[$table])){$json[$table]=array();}
	}
	//echo printValue($json);exit;
	return $json;
}
function posteditBeep($n=1){
	for($x=0;$x<$n;$x++){
		fprintf ( STDOUT, "%s", "\x07" );
	}
}
function posteditShaKey($afile){
	$path=getFilePath($afile);
	$path=realpath($path);
	$name=getFileName($afile);
	return "{$path}/{$name}";
}
function postEditCleanDir($dir='') {
	if(!is_dir($dir)){return false;}
	if ($handle = opendir($dir)) {
    	while (false !== ($file = readdir($handle))) {
			if($file == '.' || $file == '..'){continue;}
			//skip files and dirs that start with a dot.
			if(stringBeginsWith($file,'.')){continue;}
			//skip the README.md file.
			if(strtolower($file) == 'readme.md'){continue;}
			$afile="{$dir}/{$file}";
			if(is_dir($afile)){
				postEditCleanDir($afile);
				@rmdir($afile);
            	}
            else{
				@unlink($afile);
            	}
    		}
    	closedir($handle);
		}
	return true;
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
	global $local_shas;
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
		'_auth'		=>1,
		'_base64'	=>1,
		'_id'		=>$id,
		'_action'	=>'postEdit',
		'_table'	=>$table,
		'_fields'	=>$field,
		$field		=>$content,
		'-ipv4'		=>1,
		'_return'	=>'XML',
		'-nossl'	=>1,
		'-follow'	=>1,
		'-xml'		=>1
	);

	$url=buildHostUrl();
	$post=postURL($url,$postopts);
	file_put_contents("{$progpath}/postedit_filechanged.txt", printValue($postopts).$post['body']);
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
	//check for git="1"
	if(isset($hosts[$chost]['git']) && $hosts[$chost]['git']==1){
		echo "  -- updating git";
		$cdir=getFilePath($afile);
		$args=array(
			'pull',
			"commit -a -m \"updated {$filename}\"",
			'push'
		);
		$fails=0;
		foreach($args as $arg){
			$out=cmdResults('git',$arg,$cdir);
			if($out['rtncode']!=0){
				$ok=errorMessage("git {$arg} FAILED");
				$fails+=1;
			}
		}
		if($fails==0){
			echo ' success'.PHP_EOL;
		}
	}
	$shakey=posteditShaKey($afile);
	$local_shas[$shakey]=posteditSha1($afile);
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
		//$ok=soundAlarm('abort');
		$ok=posteditBeep(3);
	}
	if(file_exists($lockfile)){unlink($lockfile);}
	exit;
}
function errorMessage($msg){
	$msg=trim($msg);
	echo " - Error: {$msg}".PHP_EOL;
	if(isWindows()){
		$ok=posteditBeep(3);
	}
	return;
}
function successMessage($msg){
	global $settings;
	global $progpath;
	global $chost;
	$msg=trim($msg);
	$msg .= ' - ' . date('g:i:s a');
	echo " - Success: {$msg}".PHP_EOL;
	if(isWindows()){
		$ok=posteditBeep(1);
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
					$cmd="{$progpath}\\voice.exe -v 75 -r 1 -f -d \"{$soundmsg}\"";
				break;
				default:
					$cmd="{$progpath}\\voice.exe -v 75 -r 1 -m -d \"{$soundmsg}\"";
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
function posteditSpeak($msg){
	global $settings;
	global $progpath;
	if(!isWindows()){return false;}
	switch(strtolower($settings['sound']['gender'])){
		case 'f':
		case 'female':
			$cmd="{$progpath}\\voice.exe -v 60 -r 1 -f -d \"{$msg}\"";
		break;
		default:
			$cmd="{$progpath}\\voice.exe -v 60 -r 1 -m -d \"{$msg}\"";
		break;
	}
	$ok=exec($cmd);
	return true;
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
	foreach($xml['settings']->editor as $set){
	    	$set=(array)$set;
	    	foreach($set['@attributes'] as $k=>$v){
			$settings['editor'][$k]=$v;
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
