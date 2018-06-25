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
global $local_shas;
global $firsttime;
global $afolder;
$firsttime=1;
$local_shas=array();
ini_set("allow_url_fopen",1);
$progpath=dirname(__FILE__);
include_once("$progpath/../php/common.php");
getHosts();
getSettings();
$groups=getGroups();
if(isset($argv[1])){
	if(isset($hosts[$argv[1]])){$chost=$argv[1];}
}
if(!isset($chost)){
	abortMessage($argv[1]." was not found in postedit.xml");
}
// acquire an exclusive lock
$lock=preg_replace('/[^a-z0-9]+/i','',$chost);
global $lockfile;
$lockfile="{$progpath}/{$lock}_lock.txt";
global $noloop;
$noloop="{$progpath}/postedit.noloop";
global $pid;
$pid=getmypid();
echo "obtaining lock: {$lockfile}".PHP_EOL;
file_put_contents($lockfile,$pid);
echo "{$lockfile} is now mine".PHP_EOL;
//create the base dir
$folder=isset($hosts[$chost]['alias'])?$hosts[$chost]['alias']:$hosts[$chost]['name'];
$afolder="{$progpath}/postEditFiles/{$folder}";
$userfields=array('username');
if(isset($hosts[$chost]['user_fields'])){
	$userfields=preg_split('/\,/',$hosts[$chost]['user_fields']);
}
if(!is_dir($afolder)){
	mkdir($afolder,0777,true);
}
else{
	cli_set_process_title("{$afolder} - cleaning");
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
echo PHP_EOL."Listening to files in {$afolder} for changes...".PHP_EOL;
$ok=soundAlarm('ready');
$countdown=20;

while(1){
	cli_set_process_title("{$afolder} - {$countdown} seconds to next check");
	sleep(1);
	shutdown_check();
	//check for local changes
	foreach($local_shas as $afile=>$sha){
		if(!file_exists($afile)){continue;}
		$csha=sha1_file($afile);
		if($sha != $csha){
			$name=getFileName($afile);
			cli_set_process_title("{$afolder} - file changed {$name}");
			//echo "  {$afile} changed locally {$sha} != {$csha}".PHP_EOL;
			fileChanged($afile);
		}
	}
	$countdown-=1;
	if($countdown==0){
		writeFiles();
		$countdown=20;
		exit;
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
	global $settings;
	global $firsttime;
	global $local_shas;
	global $afolder;
	global $userfields;
	$url=buildHostUrl();
	cli_set_process_title("{$afolder} - checking {$url}");
	$tables=isset($hosts[$chost]['tables'])?$hosts[$chost]['tables']:'_pages,_templates,_models';
	$postopts=array(
		'apikey'	=>$hosts[$chost]['apikey'],
		'username'	=>$hosts[$chost]['username'],
		'_noguid'	=>1,
		'postedittables'=>$tables,
		'apimethod'	=>"posteditsha",
		'-nossl'=>1,
		'-follow'=>1,
		'-json'=>1
	);
	//echo "Calling posteditsha ...".PHP_EOL;
	$post=postURL($url,$postopts);
	file_put_contents("{$progpath}/postedit_sha.txt",$post['body']);
	if(isset($post['error']) && strlen($post['error'])){
		abortMessage($post['error']);
	}
	elseif(isset($post['xml_array']['result']['fatal_error'])){
		$msg=str_replace('&quot;','"',$post['xml_array']['result']['fatal_error']);
		$msg=str_replace('&gt;','>',$msg);
		$msg=str_replace('&lt;','<',$msg);
		abortMessage($msg);
	}
	$server=json_decode($post['body'],true);
	//file_put_contents("{$progpath}/postedit_sha.json",$post['body']);
	//exit;
	if(!isset($server['records'])){
		abortMessage("invalid json - make sure you have updated WaSQL on the server");
	}
	file_put_contents("{$progpath}/postedit_sha.txt",printValue($server));
	//figure out what pages we need to get
	
	
	$extensions=array('php','html','js','css');
	$json=array();
	$changes=0;
	$missing=0;
	foreach($server['records'] as $tablename=>$recs){
		//path
    	$path="{$afolder}/{$tablename}";
		foreach($recs as $rec){
			//loop through the fields 
			$id=$rec['_id'];
			foreach($server['tables'][$tablename] as $field){
				$cpath=$path;
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
				switch(strtolower($hosts[$chost]['groupby'])){				
					case 'type':
						$cpath .= "/{$type}";
					break;
					case 'field':
						$cpath .= "/{$field}";
					break;
					case 'ext':
					case 'extension':
						$cpath .= "/{$ext}";
					break;
					default:
						$cpath .= "/{$rec['name']}";
					break;
				}
				$afile="{$cpath}/{$rec['name']}.{$tablename}.{$field}.{$rec['_id']}.{$ext}";
				if(!file_exists($afile)){
					//check extensions
					foreach($extensions as $ext){
						$cfile="{$cpath}/{$rec['name']}.{$tablename}.{$field}.{$rec['_id']}.{$ext}";
						if(!file_exists($cfile)){
							$afile=$cfile;
							break;
						}
					}
				}
				if(file_exists($afile)){
					$shakey=posteditShaKey($afile);
					$sha=sha1_file($afile);
					if($firsttime==1){$local_shas[$shakey]=$sha;}
					if($sha!=$rec[$field]){
						//need it 
						echo "Need it: {$tablename},{$id},{$field} -- {$sha} != {$rec[$field]}".PHP_EOL;
						$json[$tablename][$id][]=$field;
						$changes++;
					}
				}
				else{
					$json[$tablename][$id][]=$field;
					$missing++;
				}
			}
		}
		
	}
	//if($changes==0){return;}
	$json=json_encode($json);
	//file_put_contents("{$progpath}/postedit_xml.json",$json);
	$postopts=array(
		'apikey'	=>$hosts[$chost]['apikey'],
		'username'	=>$hosts[$chost]['username'],
		'_noguid'	=>1,
		'postedittables'=>$tables,
		'apimethod'	=>"posteditxmlfromjson",
		'encoding'	=>"base64",
		'-nossl'=>1,
		'-follow'=>1,
		'-xml'=>1,
		'json'=>$json
	);
	if($changes > 0){
		echo "  Getting {$changes} changes from server ...".PHP_EOL;
	}
	$post=postURL($url,$postopts);
	file_put_contents("{$progpath}/postedit_xml.txt",$post['body']);
	if(isset($post['error']) && strlen($post['error'])){
		abortMessage($post['error']);
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
	//fix the one record issue
	$xml['WASQL_RECORD']=(array)$xml['WASQL_RECORD'];
	if(isset($xml['WASQL_RECORD']['@attributes'])){
		$xml['WASQL_RECORD']=array($xml['WASQL_RECORD']);
	}
	//echo printValue($xml['WASQL_RECORD']);
	foreach($xml['WASQL_RECORD'] as $rec){
		$rec=(array)$rec;
		//echo printValue($rec);
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
	    	$shakey=posteditShaKey($afile);
	    	if(file_exists($afile) && sha1($content)==sha1_file($afile)){
	    		continue;
	    	}
	    	file_put_contents($afile,$content);
	    	$local_shas[$shakey]=sha1_file($afile);
	    	if($firsttime != 1 && $hosts[$chost]['username'] != $info['musername']){
	    		$fname=getFileName($afile);
	    		$ftable=str_replace('_',' ',$info['table']);
	    		$changedby=array();
	    		foreach($userfields as $userfield){
	    			$changedby[]=$info["user_{$userfield}"];
	    		}
	    		$changedby=implode(' ',$changedby);
	    		echo "  {$afile} was changed by {$changedby} {$local_shas[$shakey]}".PHP_EOL;

	    		//posteditSpeak("The, {$name} {$field} in the {$ftable} table was changed by {$changedby}");
	    	}
		}
	}
	if($firsttime==1){
		$cmd='';
		if(isset($settings['editor']['command'])){$cmd=$settings['editor']['command'];}
		elseif(isWindows()){$cmd="EXPLORER /E";}
		if(strlen($cmd)){
			$afolder=preg_replace('/\//',"\\",$afolder);
			cmdResults("{$cmd} \"{$afolder}\"");
		}
	}
	$firsttime=0;
	return false;
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
				rmdir($afile);
            	}
            else{
				unlink($afile);
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
		'_noguid'	=>1,
		'_base64'	=>1,
		'_id'		=>$id,
		'_action'	=>'postEdit',
		'_table'	=>$table,
		'_fields'	=>$field,
		$field		=>$content,
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
	$shakey=posteditShaKey($afile);
	$local_shas[$shakey]=sha1_file($afile);
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
	if(file_exists($lockfile)){unlink($lockfile);}
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
		if(!isset($host['group'])){continue;}
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
