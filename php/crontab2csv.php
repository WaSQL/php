<?php
//---------- crontab2csv.php
//Features to add
//  - read a config file 
//  - store a hash of the last list
//  - when the list changes then push to a webhook (dexpdq)
//  - get crontabs for all users
//  for user in $(cut -f1 -d: /etc/passwd); do crontab -u $user -l 2>/dev/null | grep -v '^#'; done
/**
* @describe converts the crontab -l output into csv format
* returns csv data with header min,hour,dom,mon,dow,command
* @usage php crontab2csv.php
* @author Steven Lloyd 
*/
$progpath=dirname(__FILE__);
include_once("{$progpath}/common.php");
$settings=array();
$settings_file="{$progpath}/crontab2csv.conf";
if(file_exists($settings_file)){
  $settings=commonParseIni($settings_file,1);
}
if(isWindows()){
	echo "This does not run on Windows";
	echo printValue($settings);
	exit;
}
else{
	//linux
	$out=cmdResults('cut -f1 -d: /etc/passwd');
	$cusers=preg_split('/[\r\n]+/',$out['stdout']);
	//echo printValue($cusers).printValue($out);exit;
	$server_name=php_uname("n");
	$host= gethostname();
	$ip = gethostbyname($host);
	$fields=array('server_name','server_ip','user_name','status','min','hour','dom','mon','dow','command');
	$csvlines=array();
	$csvlines[]=implode(',',$fields);
	$recs=array();
	foreach($cusers as $cuser){
		if(isset($settings['users_exclude'][$cuser])){continue;}
		if(isset($settings['users']) && !isset($settings['users'][$cuser])){continue;}
		$out=cmdResults("crontab -u {$cuser} -l 2>&1");
		if(!isset($out['stdout']) || !strlen($out['stdout']) || stringContains($out['stdout'],'no crontab')){continue;}
		$lines=preg_split('/[\r\n]+/',$out['stdout']);
		if(!count($lines)){continue;}
		foreach($lines as $line){
			#skip comments
			if(preg_match('/^\#/',trim($line))){
				$status='paused';
				$line=preg_replace('/^\#/', '', trim($line));
			}
			else{
				$status='live';
			}
			#split the current line 6 ways
			$parts=preg_split('/\s/',$line,6);
			array_unshift($parts, $status);
			array_unshift($parts, $cuser);
			array_unshift($parts, $ip);
			array_unshift($parts, $server_name);
			#convert parts to a csv delimited string
			$csvlines[]=csvImplode($parts);
			$rec=array();
			foreach($fields as $f=>$field){
				if(isset($parts[$f])){$rec[$field]=$parts[$f];}
				else{$rec[$field]='';}
			}
			$recs[]=$rec;
		}
	}
}
#check for webhook
if(isset($settings['webhook']['url'])){
	#determine the payload format
	$json=encodeJSON($recs);
	#use a hash file to only push changes from last time
	$hash_file="{$progpath}/crontab2csv.hash";
	$hash=sha1($json);
	$skip=0;
	if(file_exists($hash_file)){
		$prev_hash=getFileContents($hash_file);
		if($prev_hash==$hash){$skip=1;}
	}
	if($skip==0){
		setFileContents($hash_file,$hash);
		$params=$settings['webhook'];
		unset($params['url']);
		$post=postJSON($settings['webhook']['url'],$json,$params);
		if(isset($post['curl_info']['http_code']) && $post['curl_info']['http_code'] != 200){
			unlink($hash_file);
			echo "Webhook failed to return a success http code.".PHP_EOL.printValue($post);exit;
		}
	}
}
if(!isset($settings['csv']['stdout']) || $settings['csv']['stdout'] != 'off'){
	echo implode(PHP_EOL,$csvlines);
}

?>