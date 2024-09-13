<?php
/*
	replacement for posteditd.pl to handle secure sites
	for sublime: subl --add
	for notepad++: notepad++ -openFoldersAsWorkspace
	Process:
		get host based on alias passed in
		get records from server
*/
//set timer to 0 to turn off auto sync.  Otherwise set it to the seconds
if(!isset($argv[1]) || in_array($argv[1],array('?','--help'))){
	echo "postEdit Usage: ".PHP_EOL;
	echo " - from a CMD prompt in the WaSQL direcory".PHP_EOL;
	echo " - p {alias name} [{filter1} {filter2}...]".PHP_EOL;
	echo PHP_EOL;
	exit;
}
global $postedit;
global $hosts;
error_reporting(E_ALL ^ E_WARNING);
ini_set("allow_url_fopen",1);
$progpath=dirname(__FILE__);
include_once("{$progpath}/../php/common.php");
if(!is_file("{$progpath}/postedit.xml")){
	abortMessage("Missing postedit.xml.");
}
//get hosts
$xmldata=getFileContents("{$progpath}/postedit.xml");
//remove any comments
$xmldata=preg_replace('/\<\!\-\-.+?\-\-\>/is','',$xmldata);
$xmldata=trim($xmldata);
$xml = (array)readXML("<postedit>{$xmldata}</postedit>");
if(!isset($xml['hosts'])){
	abortMessage("Invalid postedit.xml - no hosts entry");
}
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
if(strtolower($argv[1]) == '--list'){
	ksort($hosts);
	$i=1;
	echo "HOSTS Found in postedit.xml".PHP_EOL;
	$hosts=sortArrayByKeys($hosts,array('alias'=>SORT_ASC));
	foreach($hosts as $alias=>$info){
		echo "	{$i}. {$alias}".PHP_EOL;
		$i+=1;
	}
	exit;
}
if(!isset($hosts[$argv[1]])){
	abortMessage($argv[1]." was not found in postedit.xml. Pass in --list to see list.");
}
$postedit=$hosts[$argv[1]];
$postedit['host']=$argv[1];
$postedit['filters']=array();
if(isset($argv[2])){
	$postedit['filters']=$argv;
	array_shift($postedit['filters']);
	array_shift($postedit['filters']);
	foreach($postedit['filters'] as $i=>$filter){
		$postedit['filters'][$i]=strtolower(trim($filter));
	}
	echo " - Filters set: ".implode(', ',$postedit['filters']).PHP_EOL;
}
$postedit['progpath']=$progpath;
//settings?
if(isset($xml['settings'])){
	foreach($xml['settings']->sound as $set){
	    $set=(array)$set;
	    foreach($set['@attributes'] as $k=>$v){
			$postedit['sound'][$k]=$v;
		}
	}
	foreach($xml['settings']->editor as $set){
	    $set=(array)$set;
	    foreach($set['@attributes'] as $k=>$v){
			$postedit['editor'][$k][]=$v;
		}
	}
}
// acquire an exclusive lock
$postedit['lock']=preg_replace('/[^a-z0-9]+/i','',$postedit['host']);
$postedit['alock']="{$progpath}/{$postedit['lock']}_lock.txt";
$postedit['pid']=getmypid();
$postedit['firsttime']=1;
//echo "obtaining lock: {$postedit['alock']} ...";
file_put_contents($postedit['alock'],$postedit['pid']);
//echo "success".PHP_EOL;
//create the base dir
$postedit['folder']=!empty($postedit['alias'])?$postedit['alias']:$postedit['name'];
$basefolder=$postedit['folder'];
//allow timer to be set in postedit.xml
$postedit['timer']=!empty($postedit['timer'])?(integer)$postedit['timer']:20;
//allow timezone to be set
if(!empty($postedit['timezone'])){
	date_default_timezone_set($postedit['timezone']);
}
else{
	date_default_timezone_set('America/Denver');
}
$postedit['afolder']="{$progpath}/postEditFiles/{$postedit['folder']}";
$postedit['bfolder']=$postedit['afolder'].'_bak';

if(!is_dir($postedit['afolder'])){
	mkdir($postedit['afolder'],0777,true);
}
else{
	cli_set_process_title("{$postedit['afolder']} - cleaning");
	echo " - cleaning".PHP_EOL;
	if(file_exists($postedit['bfolder'])){
		postEditCleanDir($postedit['bfolder']);
		@rmdir($postedit['bfolder']);
	}
	posteditCopyDir($postedit['afolder'],$postedit['bfolder']);
	postEditCleanDir($postedit['afolder']);
}
//get the files
echo " - writing local files".PHP_EOL;
writeFiles();
$countdown=$postedit['timer'];
echo "Listening to files in {$postedit['afolder']} for changes...".PHP_EOL;
$ok=sounder('success.wav');
$cliname="postedit - ".getFileName($postedit['afolder']);
cli_set_process_title($cliname);
while(1){
	sleep(1);
	shutdown_check();
	$mypid=getmypid();
	if($postedit['pid'] != $mypid){
		abortMessage("another postedit process took over...");
		exit;
	}
	//check for local changes
	checkForChanges();
	$countdown-=1;
}
exit;
function checkForChanges(){
	global $postedit;
	$files=listFilesEx($postedit['afolder'],array('-md5'=>1,'-sha'=>1,'-recurse'=>1));
	foreach($files as $file){
		$afile=$file['afile'];
		$idx=preg_replace('/\//',"\\",$afile);
		if(!isset($postedit['md5sha'][$idx])){
			continue;
		}
		$md5sha=$file['md5'].$file['sha'];
		if($postedit['md5sha'][$idx] != $md5sha){
			//file changed
			fileChanged($afile);
		}
	}
	return true;
}
function fileChanged($afile){
	global $postedit;
	$filename=getFileName($afile);
	echo "  {$filename}";
	$content=@file_get_contents($afile);
	if(!commonStrlen($content) && isWindows()){
		$content=getContents($afile);
		if(!commonStrlen($content)){
    		$ok=errorMessage(" - failed to get content");
    		return;
		}
	}
	$idx=preg_replace('/\//',"\\",$afile);
	if(!isset($postedit['md5sha'][$idx])){
		$ok=errorMessage("Unknown file: {$afile}");
		return;
	}
	$md5sha=md5_file($afile).sha1_file($afile);
	$content=encodeBase64($content);
	list($fname,$table,$field,$id,$ext)=preg_split('/\./',$filename);
	$postopts=array(
		'apikey'	=>$postedit['apikey'],
		'username'	=>$postedit['username'],
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
		'-xml'		=>1,
		'_md5sha'	=> $postedit['md5sha'][$idx]
	);
	$postedit['md5sha'][$idx]=$md5sha;
	$url=buildHostUrl();
	$post=postURL($url,$postopts);
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
		$error=" - fatal error posting. ".PHP_EOL.$xml['fatal_error'];
    	abortMessage($error);
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
function shutdown_check(){
	global $postedit;
	$tpid=getFileContents($postedit['alock']);
	$pid=getmypid();
	if(trim($tpid) != $pid){
		abortMessage("Another postedit process took control. Exiting.");
		exit;
	}
}
/*
	writeFiles:
		submit sha1 of local files to server
		Server responds with any files I need - new or changed
*/
function writeFiles(){
	global $postedit;
	$postedit['files']=array();
	$json=posteditGetLocalShas();
	$json=json_encode($json);
	$url=buildHostUrl();
	cli_set_process_title("{$postedit['afolder']} - checking {$url}");
	if($postedit['firsttime']==1){
		echo " - Calling {$url}".PHP_EOL;
	}
	$tables=isset($postedit['tables'])?$postedit['tables']:'_pages,_templates,_models,_prompts';
	$postopts=array(
		'apikey'	=>$postedit['apikey'],
		'username'	=>$postedit['username'],
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
	//$ok=file_put_contents("{$postedit['progpath']}\\writefiles.txt",$post['body']);
	//echo "writeFiles - got files from server".PHP_EOL;
	//check for failures
	if(isset($post['curl_info']['http_code']) && $post['curl_info']['http_code']==404){
		abortMessage("404 Error - /php/index.php not found");
	}
	if(isset($post['curl_info']['http_code']) && $post['curl_info']['http_code'] != 200){
    	abortMessage("{$post['curl_info']['http_code']} error retrieving files");
	}
	if(isset($post['error']) && commonStrlen($post['error'])){
		abortMessage("POST ERROR".$post['error']);
	}
	if(stringBeginsWith($post['body'],'error:')){
		abortMessage("BODY ERROR:".$post['body']);
	}
	if(stringBeginsWith($post['body'],'You have an error in your SQL syntax;')){
		abortMessage($post['body']);
	}
	if(stringContains($post['body'],'form id="loginform"')){
		abortMessage('Invalid Login Credentials');
	}
	if(isset($post['xml_array']['result']['fatal_error'])){
		$msg=str_replace('&quot;','"',$post['xml_array']['result']['fatal_error']);
		$msg=str_replace('&gt;','>',$msg);
		$msg=str_replace('&lt;','<',$msg);
		abortMessage($msg);
	}
	//echo $post['body'];exit;
	//convert xml to array
	$xml = simplexml_load_string($post['body'],'SimpleXMLElement',LIBXML_NOCDATA | LIBXML_PARSEHUGE );
	$xml=(array)$xml;
	//check for fatal xml errors
	if(isset($xml['fatal_error'])){
		$msg=str_replace('&quote;','"',$xml['fatal_error']);
		$msg=str_replace('&gt;','>',$msg);
		$msg=str_replace('&lt;','<',$msg);
		abortMessage($msg);
	}
	elseif(!isset($xml['WASQL_RECORD'])){
		$progpath=dirname(__FILE__);
		setFileContents("{$progpath}/postedit_results.xml",$post['body']);
		$msg="Error - no WaSQL RECORD".PHP_EOL.printValue($postopts).$post['body'];
		abortMessage($msg);
	}
	//fix the one record issue
	$xml['WASQL_RECORD']=(array)$xml['WASQL_RECORD'];
	if(isset($xml['WASQL_RECORD']['@attributes'])){
		$xml['WASQL_RECORD']=array($xml['WASQL_RECORD']);
	}
	$cnt=count($xml['WASQL_RECORD']);
	if($cnt==0){
		$msg="Error - no WaSQL RECORDs";
		abortMessage($msg);
	}
	foreach($xml['WASQL_RECORD'] as $rec){
		$rec=(array)$rec;
		$info=$rec['@attributes'];
		unset($rec['@attributes']);
		$json_fields=array();
		if(isset($info['json_fields'])){
			$json_fields=preg_split('/\,/',$info['json_fields']);
		}
		$skip=0;
		if(isset($postedit['filters'][0])){
			$skip=1;
			foreach($postedit['filters'] as $filter){
				if(isset($info['name']) && stringContains($info['name'],$filter)){$skip=0;}
			}
		}
		if($skip==1){continue;}
		foreach($rec as $name=>$content){
	    	if(!commonStrlen(trim($content))){continue;}
	    	$field=str_replace("{$info['table']}_",'',$name);
	    	//echo "Name: {$name}, field: {$field}";exit;
	    	//name
	    	if(!isset($info['name'])){
	    		$info['name']="{$info['table']}_{$info['_id']}";
	    	}
	    	$name=preg_replace('/[^a-z0-9\ \_\-]+/i','',$info['name']);
	    	$name=trim($name);
	    	//extension
	    	//echo "{$name} -- {$field}".PHP_EOL;
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
	        		switch(strtolower($name)){
	        			case 'code_prompt':$ext='php';break;
	        			case 'php_prompt':$ext='php';break;
	        			case 'python_prompt':$ext='py';break;
	        			case 'sql_prompt':$ext='sql';break;
	        			case 'json_prompt':$ext='json';break;
	        			default:$ext='html';break;
	        		}
	        		$type='views';
	        	break;
			}
			if(in_array($field,$json_fields)){
	    		$ext='json';
	    	}
	    	//path
	    	$path="{$postedit['afolder']}/{$info['table']}";
	    	switch(strtolower($postedit['groupby'])){
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
			$checkstr=trim(substr($content,0,10));
			if(stringEndsWith($info['name'],'.py')){$ext='py';}
			elseif(stringContains($checkstr,'php')){$ext='php';}
			elseif(stringContains($checkstr,'py')){$ext='py';}
			elseif(stringContains($checkstr,'?=')){$ext='php';}
	    	$afile="{$path}/{$name}.{$info['table']}.{$field}.{$info['_id']}.{$ext}";
	    	file_put_contents($afile,$content);
			$md5sha=md5_file($afile).sha1_file($afile);
	    	$idx=preg_replace('/\//',"\\",$afile);
	    	$postedit['md5sha'][$idx]=$md5sha;
		}
	}
	//check for editor command to run after writing files
	if(isset($postedit['editor']['command']) && is_array($postedit['editor']['command'])){

		foreach($postedit['editor']['command'] as $c=>$cmd){
			if(!strlen($cmd)){continue;}
			if(isWindows()){
				$postedit['afolder']=preg_replace('/\//',"\\",$postedit['afolder']);
				$cmd=preg_replace('/\//',"\\",$cmd);
				$cmd=str_replace("'",'"',$cmd);
			}
			$cmd="{$cmd} \"{$postedit['afolder']}\"";
			echo " - Running command: {$cmd}".PHP_EOL;
			$out=cmdResults($cmd);
			if($out['rtncode'] !=0){
				echo printValue($out).PHP_EOL;
			}
		}
	}
	//echo printValue($postedit['md5sha']).PHP_EOL;
	return false;
}
function posteditGetLocalShas(){
	global $postedit;
	$tables=isset($postedit['tables'])?$postedit['tables']:'_pages,_templates,_models,_prompts';
	$tables=preg_split('/\,/',$tables);
	//echo $chost.printValue($hosts[$chost]).printValue($tables);exit;
	$json=array();
	//make sure every table is represented in the jason
	foreach($tables as $table){
		if(!isset($json[$table])){$json[$table]=array();}
	}
	//echo printValue($json);exit;
	return $json;
}
function posteditBeep($n=1){
	for ($i = 0; $i < $n; $i++){ 
		fprintf ( STDOUT, "%s", "\x07" );
		sleep(1);
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
	global $postedit;
	if(isset($postedit['insecure']) && $postedit['insecure'] == 1){$http='http';}
	else{$http='https';}
	$url="{$http}://{$postedit['name']}/php/index.php";
	return $url;
}
function abortMessage($msg){
	global $postedit;
	if(!is_string($msg)){$msg=json_encode($msg);}
	$msg=trim($msg);
	echo "Fatal Error: {$msg}".PHP_EOL;
	if(isWindows()){
		//$ok=posteditBeep(3);
		$ok=sounder('failure.wav');
	}
	if(isset($postedit['alock']) && is_file($postedit['alock'])){
		unlink($postedit['alock']);
	}
	exit;
}
function errorMessage($msg){
	$msg=trim($msg);
	echo " - Error: {$msg}".PHP_EOL;
	if(isWindows()){
		//$ok=posteditBeep(2);
		$ok=sounder('failure.wav');
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
		//$ok=posteditBeep(1);
		$ok=sounder('success.wav');
	}
	return;
}
function sounder($sound){
	global $progpath;
	if(isWindows()){
		$cmd="powershell -c (New-Object Media.SoundPlayer '{$progpath}\\{$sound}').PlaySync();";
		$out=cmdResults($cmd);
	}
	
	return;
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
?>
