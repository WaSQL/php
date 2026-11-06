<?php
/*
	PostEDIT - WaSQL companion app to 
		- pull down db changes locally, 
		- monitor local files, 
		- update db records
	USAGE:
		In the backend of WaSQL select postEdit in the User menu can copy tag
		add to postedit.xml - the alias name is what you use to call that site
		in a command prompt type: p {alias} where alias is the name in your tag
*/
//set timer to 0 to turn off auto sync.  Otherwise set it to the seconds
//pull --ignore-editor out of argv (can appear anywhere) before positional args are parsed
$posteditIgnoreEditor=0;
foreach($argv as $i=>$arg){
	if($i > 0 && strtolower($arg) == '--ignore-editor'){
		$posteditIgnoreEditor=1;
		unset($argv[$i]);
	}
}
$argv=array_values($argv);
if(!isset($argv[1]) || in_array($argv[1],array('?','--help'))){
	echo "postEdit Usage: ".PHP_EOL;
	echo " - from a CMD prompt in the WaSQL direcory".PHP_EOL;
	echo " - p {alias name} [{filter1} {filter2}...] [--ignore-editor]".PHP_EOL;
	echo " - --ignore-editor skips running the <editor> command from postedit.xml after each sync".PHP_EOL;
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
$postedit['ignoreeditor']=$posteditIgnoreEditor;
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
//release our lock on ANY exit, including CTRL-C / CTRL-BREAK (previously these orphaned the lock file).
register_shutdown_function('posteditReleaseLock');
register_shutdown_function('posteditShutdownAnnounce');
if(function_exists('sapi_windows_set_ctrl_handler')){
	//Windows console CTRL-C / CTRL-BREAK: a handler suppresses the default kill, so we clean up and exit ourselves.
	sapi_windows_set_ctrl_handler(function($event){ posteditReleaseLock(); exit(0); });
}
elseif(function_exists('pcntl_signal')){
	//*nix SIGINT (CTRL-C) / SIGTERM. pcntl_async_signals is PHP 7.1+; on older PHP we fall back
	//to a manual pcntl_signal_dispatch() each iteration of the main loop below.
	if(function_exists('pcntl_async_signals')){pcntl_async_signals(true);}
	$posteditSignalHandler=function($signo=0){ posteditReleaseLock(); exit(0); };
	pcntl_signal(SIGINT, $posteditSignalHandler);
	pcntl_signal(SIGTERM, $posteditSignalHandler);
}
//create the base dir
$postedit['folder']=!empty($postedit['alias'])?$postedit['alias']:$postedit['name'];
$basefolder=$postedit['folder'];
//allow timer to be set in postedit.xml
$postedit['timer']=!empty($postedit['timer'])?(int)$postedit['timer']:20;
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
$ok=posteditSay('success.wav',posteditSpokenName().' ready');
$cliname="postedit - ".getFileName($postedit['afolder']);
cli_set_process_title($cliname);
while(1){
	sleep(1);
	//deliver pending *nix signals on PHP < 7.1 (no async signals); harmless no-op otherwise / on Windows
	if(function_exists('pcntl_signal_dispatch')){pcntl_signal_dispatch();}
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
	//collect every file's outcome for this pass so ONE sound/announcement covers them all (see posteditAnnounce)
	$postedit['pass']=array();
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
	$results=$postedit['pass'];
	unset($postedit['pass']);
	if(count($results)){
		posteditAnnounce($results);
	}
	return true;
}
function fileChanged($afile){
	global $postedit;
	$filename=getFileName($afile);
	//spoken label for this file in the pass announcement: "{name} {field}"
	$parts=preg_split('/\./',$filename);
	$postedit['pass_current']=count($parts) >= 5?"{$parts[0]} {$parts[2]}":$filename;
	echo "  {$filename}";
	$content=@file_get_contents($afile);
	if(!commonStrlen($content) && isWindows()){
		$content=getContents($afile);
		if(!commonStrlen($content)){
			//a zero-byte file is a legitimately blank field, not a read failure
			clearstatcache(true,$afile);
			if(file_exists($afile) && filesize($afile)===0){
				$content='';
			}
			else{
    			$ok=errorMessage(" - failed to get content");
    			return;
			}
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
		//alert=true: a Y/N prompt is waiting on the developer, so sound now rather than after the pass
		$ok=errorMessage($xml['refresh_error']." attention required",true);
    	echo "   Refresh Now? Y or N: ";
		$s = stream_get_line(STDIN, 1024, PHP_EOL);
		$s=strtolower($s);
		if($s != 'n'){
        	writeFiles();
		}
	}
	elseif(isset($xml['error'])){
		$ok=errorMessage($xml['error']. "attention required",true);
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
	//convert xml to array - strip UTF-8 BOM(s) that break simplexml_load_string
	$body = preg_replace('/^(\xEF\xBB\xBF)+/', '', $post['body']);
	$xml = simplexml_load_string($body,'SimpleXMLElement',LIBXML_NOCDATA | LIBXML_PARSEHUGE );
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

			// Determine file extension from content
			// Detection priority: HTML directive > JSON > language tags > filename > fallback
			// Check first 50 chars of content for performance while catching most detection patterns
			// Remove UTF-8 BOM if present (\xEF\xBB\xBF)
			$contentCheck = substr($content, 0, 3) === "\xEF\xBB\xBF" ? substr($content, 3, 50) : substr($content, 0, 50);
			$checkstr=trim($contentCheck);
			$checklen=strlen($checkstr);

			// Priority 1: Explicit extension directive via HTML comment (<!--ext:xxx-->)
			// Allows developers to override automatic detection when needed
			if($checklen > 0 && preg_match('/^\<\!\-\-ext\:([a-z0-9]+)\-\-\>/i', $checkstr, $extMatch)){
				$ext=strtolower($extMatch[1]);
			}
			// Priority 2: JSON content detection (starts with { or [)
			// Must come before language tag checks since JSON doesn't use opening tags
			elseif($checklen > 0 && (stringBeginsWith($checkstr,'{') || stringBeginsWith($checkstr,'['))){
				$ext='json';
			}
			// Priority 3: Language-specific opening tags (WaSQL multi-language support)
			// PHP: <?php or <?=
			elseif($checklen > 0 && stringBeginsWith($checkstr,'<?php')){
				$ext='php';
			}
			// PHP short echo tag (<?=) at start
			elseif($checklen > 0 && stringBeginsWith($checkstr,'<?=')){
				$ext='php';
			}
			// Python: <?py or <?python
			elseif($checklen > 0 && (stringBeginsWith($checkstr,'<?py') || stringBeginsWith($checkstr,'<?python'))){
				$ext='py';
			}
			// Node.js/JavaScript: <?node or <?js
			elseif($checklen > 0 && (stringBeginsWith($checkstr,'<?node') || stringBeginsWith($checkstr,'<?js'))){
				$ext='js';
			}
			// Ruby: <?ruby or <?rb
			elseif($checklen > 0 && (stringBeginsWith($checkstr,'<?ruby') || stringBeginsWith($checkstr,'<?rb'))){
				$ext='rb';
			}
			// Perl: <?perl or <?pl
			elseif($checklen > 0 && (stringBeginsWith($checkstr,'<?perl') || stringBeginsWith($checkstr,'<?pl'))){
				$ext='pl';
			}
			// VBScript: <?vbscript or <?vbs
			elseif($checklen > 0 && (stringBeginsWith($checkstr,'<?vbscript') || stringBeginsWith($checkstr,'<?vbs'))){
				$ext='vbs';
			}
			// Lua: <?lua
			elseif($checklen > 0 && stringBeginsWith($checkstr,'<?lua')){
				$ext='lua';
			}
			// Bash/Shell: <?bash or <?sh
			elseif($checklen > 0 && (stringBeginsWith($checkstr,'<?bash') || stringBeginsWith($checkstr,'<?sh'))){
				$ext='sh';
			}
			// Priority 4: Check filename for extension hint
			// Useful when content doesn't have language tags but filename indicates type
			elseif(isset($info['name']) && stringEndsWith($info['name'],'.py')){
				$ext='py';
			}
			elseif(isset($info['name']) && stringEndsWith($info['name'],'.rb')){
				$ext='rb';
			}
			elseif(isset($info['name']) && stringEndsWith($info['name'],'.pl')){
				$ext='pl';
			}
			elseif(isset($info['name']) && stringEndsWith($info['name'],'.vbs')){
				$ext='vbs';
			}
			elseif(isset($info['name']) && stringEndsWith($info['name'],'.lua')){
				$ext='lua';
			}
			elseif(isset($info['name']) && stringEndsWith($info['name'],'.sh')){
				$ext='sh';
			}
			elseif(isset($info['name']) && stringEndsWith($info['name'],'.md')){
				$ext='md';
			}
			// Priority 5: Fallback - check for PHP tags
			// PHP short tag (<?  ) - after checking all other language tags
			// Must come last to avoid conflicting with other <?lang tags
			elseif($checklen > 0 && stringBeginsWith($checkstr,'<?') && !stringBeginsWith($checkstr,'<?xml')){
				$ext='php';
			}
			// If no detection matched, $ext retains its value from the switch statement above (lines 386-414)
	    	$afile="{$path}/{$name}.{$info['table']}.{$field}.{$info['_id']}.{$ext}";
	    	file_put_contents($afile,$content);
			$md5sha=md5_file($afile).sha1_file($afile);
	    	$idx=preg_replace('/\//',"\\",$afile);
	    	$postedit['md5sha'][$idx]=$md5sha;
		}
	}
	//check for editor command to run after writing files
	if(!empty($postedit['ignoreeditor'])){
		//--ignore-editor was passed on the command line - skip launching the editor
	}
	elseif(isset($postedit['editor']['command']) && is_array($postedit['editor']['command'])){

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
function posteditReleaseLock(){
	global $postedit;
	if(isset($postedit['alock']) && is_file($postedit['alock'])){
		//only remove the lock if it's still ours (PID match), or empty/unreadable — never yank a lock another instance took over.
		$lockpid=trim((string)@file_get_contents($postedit['alock']));
		if($lockpid === '' || $lockpid === (string)getmypid()){
			@unlink($postedit['alock']);
		}
	}
}
function abortMessage($msg){
	global $postedit;
	if(!is_string($msg)){$msg=json_encode($msg);}
	$msg=trim($msg);
	echo "Fatal Error: {$msg}".PHP_EOL;
	//speak the first few words of the reason; wait for it - the watcher is exiting anyway and blocking guarantees it is heard in full
	$reason=preg_replace('/^(fatal\s+error\s*\:?\s*)+/i','',$msg);
	//first sentence only, capped at 10 words
	$reason=preg_replace('/^(.+?[\.\!\?])\s.*$/s','$1',$reason);
	$reason=implode(' ',array_slice(preg_split('/\s+/',$reason),0,10));
	$postedit['died_spoken']=1;
	$ok=posteditSay('failure.wav',posteditSpokenName()." stopped. {$reason}",true);
	//only removes the lock if it is still ours - on a takeover the lock now belongs to the NEW instance,
	//and deleting it made that instance abort a second later too
	posteditReleaseLock();
	exit;
}
/**
* @describe registered as a shutdown function: announces a PHP fatal error (an exit abortMessage did not report).
*	A CTRL-C or a normal exit says nothing.
* @return null
*/
function posteditShutdownAnnounce(){
	global $postedit;
	if(!empty($postedit['died_spoken'])){return;}
	$err=error_get_last();
	if(!is_array($err) || !in_array($err['type'],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR))){return;}
	$ok=posteditSay('failure.wav',posteditSpokenName().' crashed. PHP fatal error',true);
	return;
}
/**
* @describe the alias as it should be spoken ("serviceterra live" for serviceterra-live)
* @return string
*/
function posteditSpokenName($prefix='postedit '){
	global $postedit;
	//a host's spokenname="" attribute overrides the alias when the alias reads badly aloud (e.g. "dex p d q")
	if(!empty($postedit['spokenname'])){$name=$postedit['spokenname'];}
	else{$name=isset($postedit['host'])?$postedit['host']:'';}
	$name=trim(preg_replace('/[\_\-\.]+/',' ',$name));
	return strlen($name)?"{$prefix}{$name}":trim($prefix);
}
/**
* @describe plays a wav then (in speak mode, the default) speaks text after it; beep/wav modes just play the wav
* @param wav string - wav file name in the postedit folder
* @param text string - text to speak
* @param wait boolean - block until finished (default false: background)
* @return null
*/
function posteditSay($wav,$text,$wait=false){
	global $postedit;
	$mode=isset($postedit['sound']['mode'])?strtolower(trim($postedit['sound']['mode'])):'speak';
	$play=posteditSoundPlay($wav);
	if($mode != 'speak' || !strlen(trim($text))){
		return posteditPowershell($play,$wait);
	}
	//keep the spoken text free of anything that could break the quoted powershell command
	$text=preg_replace('/[^a-z0-9\ \,\.]+/i',' ',$text);
	$text=trim(preg_replace('/\s+/',' ',$text));
	return posteditPowershell("{$play}; Add-Type -AssemblyName System.Speech; (New-Object System.Speech.Synthesis.SpeechSynthesizer).Speak('{$text}')",$wait);
}
/**
* @describe prints an error. Inside a checkForChanges() pass the failure is recorded for the single end-of-pass
*	announcement instead of sounding per file; outside a pass it sounds immediately (non-blocking)
* @param msg string - error text
* @param alert boolean - sound immediately even inside a pass (use when a prompt is about to wait on the developer)
* @return null
*/
function errorMessage($msg,$alert=false){
	global $postedit;
	$msg=trim($msg);
	echo " - Error: {$msg}".PHP_EOL;
	if(isset($postedit['pass'])){
		$postedit['pass'][$postedit['pass_current']]=0;
		if($alert){$ok=sounder('failure.wav');}
	}
	else{
		$ok=sounder('failure.wav');
	}
	return;
}
/**
* @describe prints a success. Inside a checkForChanges() pass it is recorded for the single end-of-pass
*	announcement; outside a pass it sounds immediately (non-blocking)
* @param msg string - success text
* @return null
*/
function successMessage($msg){
	global $postedit;
	$msg=trim($msg);
	$msg .= ' - ' . date('g:i:s a');
	echo " - Success: {$msg}".PHP_EOL;
	if(isset($postedit['pass'])){
		//keyed by file label: a later outcome for the same file (e.g. after a Y/N retry) replaces the earlier one
		$postedit['pass'][$postedit['pass_current']]=1;
	}
	else{
		$ok=sounder('success.wav');
	}
	return;
}
/**
* @describe announces the outcome of one checkForChanges() pass with a single background sound.
*	One file sounds exactly as before (success.wav / failure.wav). Several files depend on the <sound mode=""> setting:
*		speak (default) - result wav, then a spoken summary: "10 saved", "9 saved, index body failed", "8 saved, 2 failed"
*		beep - one short beep per file (high = saved, low = failed), then the result wav
*		wav  - the result wav once per file, back to back
* @param results array - file label => 1 (saved) or 0 (failed)
* @return null
*/
function posteditAnnounce($results){
	global $postedit;
	$failed=array_keys($results,0,true);
	$saved=count($results)-count($failed);
	$wav=count($failed)?'failure.wav':'success.wav';
	$mode=isset($postedit['sound']['mode'])?strtolower(trim($postedit['sound']['mode'])):'speak';
	if(count($results)==1 && $mode != 'speak'){
		$ok=sounder($wav);
		return;
	}
	$play=posteditSoundPlay($wav);
	switch($mode){
		case 'wav':
			$cmds=array();
			foreach($results as $label=>$ok){
				$cmds[]=posteditSoundPlay($ok?'success.wav':'failure.wav');
			}
			$ok=posteditPowershell(implode('; ',$cmds));
		break;
		case 'beep':
			$cmds=array();
			foreach($results as $label=>$ok){
				$cmds[]=$ok?'[console]::Beep(1200,120)':'[console]::Beep(400,250)';
			}
			$cmds[]=$play;
			$ok=posteditPowershell(implode('; ',$cmds));
		break;
		default:
			//speak - always led by the project name, since several postedits may be running at once
			$parts=array();
			if(count($results)==1){
				//one file: "dexpdq saved" / "dexpdq index body failed"
				$parts[]=$saved?'saved':"{$failed[0]} failed";
			}
			else{
				//several: "dexpdq 10 saved" / "dexpdq 9 saved, index body failed" / "dexpdq 8 saved, 2 failed"
				if($saved > 0){$parts[]="{$saved} saved";}
				if(count($failed)==1){$parts[]="{$failed[0]} failed";}
				elseif(count($failed) > 1){$parts[]=count($failed)." failed";}
			}
			$ok=posteditSay($wav,posteditSpokenName('').' '.implode(', ',$parts));
		break;
	}
	return;
}
/**
* @describe builds the powershell snippet that plays a wav from the postedit folder to completion
* @param sound string - wav file name in the postedit folder
* @return string
*/
function posteditSoundPlay($sound){
	global $progpath;
	$path=str_replace("'","''","{$progpath}\\{$sound}");
	return "(New-Object Media.SoundPlayer '{$path}').PlaySync()";
}
/**
* @describe runs a powershell command (Windows only). Default is fire-and-forget: a detached process plays the
*	sound while postedit carries on (~0.04s vs ~1.7s blocking). -NoProfile skips the profile load to cut startup lag.
* @param ps string - powershell command (use single quotes inside; no double quotes)
* @param wait boolean - block until it finishes
* @return null
*/
function posteditPowershell($ps,$wait=false){
	if(!isWindows()){return;}
	$cmd="powershell -NoProfile -NonInteractive -c \"{$ps}\"";
	if($wait){
		$out=cmdResults($cmd);
	}
	else{
		pclose(popen("start \"\" /B {$cmd} >NUL 2>&1",'r'));
	}
	return;
}
/**
* @describe plays a wav from the postedit folder (Windows only)
* @param sound string - wav file name in the postedit folder
* @param wait boolean - block until it finishes (default false: plays in the background)
* @return null
*/
function sounder($sound,$wait=false){
	$ok=posteditPowershell(posteditSoundPlay($sound),$wait);
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
