<?php
//---------- crontab2csv.php
// 
// 
//  
/**
 * Sample crontab entry
 * 		* * * * * php /var/www/wasql_stage/php/crontab2csv.php 2>&1
 * Sample crontab2csv.conf file (optional)
 * 		[webhook]
 * 		url=https://some.site.com/v1/add_records
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
			//$csvlines[]=csvImplode($parts);
			$rec=array();
			foreach($fields as $f=>$field){
				if(isset($parts[$f])){$rec[$field]=$parts[$f];}
				else{$rec[$field]='';}
			}
			$rec['uid']=sha1($server_name.$cuser.$rec['command']);
			$recs[]=$rec;
		}
	}
	//check from /var/log/cron log
	if(file_exists('/var/log/cron')){
		$cnt=count($recs)*2;
		//Dec 27 07:30:01 dca32007 CROND[23673]: (root) CMD (ssh 'slloyd@den22005.co.doterra.net' uptime >/var/www/uptime_postgres.txt 2>&1)
		$cmd="tail -n 1000 /var/log/cron|grep CROND";
		$out=cmdResults($cmd);
		$lines=preg_split('/[\r\n]+/',$out['stdout']);
		if(count($lines)){
			$runtimes=array();
			foreach($lines as $line){
				if(preg_match('/^(.+?)CROND\[([0-9]+?)\]\:\ \(([a-z]+?)\)\ CMD\ \((.+)\)$/',$line,$m)){
					$parts=preg_split('/\s/',trim($m[1]));
					$server_name=array_pop($parts);
					$runtime=implode(' ',$parts);
					$cuser=$m[3];
					$cmd=$m[4];
					if(isset($runtimes[$cuser][$cmd])){continue;}
					$runtimes[$cuser][$cmd]=array(
						'datetime'=>date('Y-m-d H:i:s',strtotime($runtime)),
						'datestr'=>$runtime,
						'server'=>$server_name,
						'cmd'=>$cmd,
						'pid'=>$m[2]
					);
					#echo printValue($m).$line;exit;
				}
			}
			foreach($recs as $i=>$rec){
				$cmd=$rec['command'];
				$cuser=$rec['user_name'];
				if(isset($runtimes[$cuser][$cmd])){
					$recs[$i]['last_run']=$runtimes[$cuser][$cmd]['datetime'];
				}
				else{
					$recs[$i]['last_run']='';
				}
			}
			//echo printValue($runtimes);exit;
		}
	}
}
if(!isset($settings['csv']['stdout']) || $settings['csv']['stdout'] != 'off'){
	$csv=arrays2CSV($recs);
	echo $csv.PHP_EOL;
}
#check for webhook
if(isset($settings['webhook']['url'])){
	$hashes=array();
	#use a hash file to only push changes from last time
	$hash_file="{$progpath}/crontab2csv.json";
	if(file_exists($hash_file)){
		$data=getFileContents($hash_file);
		$hashes=decodeJSON($data);
	}
	$wrecs=array();
	foreach($recs as $i=>$rec){
		ksort($rec);
		$hash=sha1(encodeJSON($rec));
		$uid=$rec['uid'];
		if(!isset($hashes[$uid]) || $hashes[$uid]!=$hash){
			$wrecs[]=$rec;
		}
		$hashes[$uid]=$hash;
	}
	if(count($wrecs)){
		setFileContents($hash_file,encodeJSON($hashes));
		$params=$settings['webhook'];
		unset($params['url']);
		$json=encodeJSON($wrecs);
		$post=postJSON($settings['webhook']['url'],$json,$params);
		if(isset($post['curl_info']['http_code']) && $post['curl_info']['http_code'] != 200){
			unlink($hash_file);
			echo "Webhook failed to return a success http code.".PHP_EOL.printValue($post);exit;
		}
	}
	elseif(!isset($settings['csv']['stdout']) || $settings['csv']['stdout'] != 'off'){
		echo "Nothing has changed".PHP_EOL;
	}
}


?>