<?php
/*
	Run each time a person logs in
		php wdns_user.php

	Compile using http://sourceforge.net/projects/bamcompile/
	bamcompile.exe -c -i:wdns.ico wdns_user.php wdns_user.exe
	
	You can set host, path, and port in a wdns_users.ini file
	
	use this local dns proxy to proxy to port 80 so we can use dnsone.com as our proxy.
	http://mayakron.altervista.org/wikibase/show.php?id=AcrylicHome

*/
//set the default time zone
//date_default_timezone_set('America/Denver');
$ini=array(
	'secure'=>0,
	'host'	=>'www.dnsome.com',
	'path'	=> '/php/extras/wdns.php',
	'port'	=> 80
);
if(file_exists('wdns_user.ini')){
	$lines=file('wdns_user.ini');
	foreach($lines as $line){
		$line=trim($line);
		//ignore comment lines or lines wihout an equals sign
		if(stringBeginsWith($line,';') || stringBeginsWith($line,'#') || !stringContains($line,'=')){continue;}
    	list($key,$val)=preg_split('/\=/',$line,2);
    	if(!strlen($val)){continue;}
    	$key=strtolower(trim($key));
		$ini[$key]=$val;
	}
}
//get os
$os=urlencode(php_uname());
$ini['path'] .="?username={$_SERVER['USERNAME']}&os={$os}&computername={$_SERVER['COMPUTERNAME']}";
//fsockopen(host,port,errno,errstr,timeout)
$fp = fsockopen($ini['host'], $ini['port'], $errno, $errstr, 5);
if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} 
else {
    $post_data = "GET {$ini['path']} HTTP/1.1\r\n";
    $post_data .= "Host: {$ini['host']}\r\n";
    $post_data .= "Connection: Close\r\n\r\n";
    fwrite($fp, $post_data);
    while (!feof($fp)) {
        echo fgets($fp, 128);
    }
    fclose($fp);
}
exit;
//---------- begin function stringBeginsWith--------------------
/**
* @describe returns true if $str begins with $search
* @param str string
* @param search string
* @return boolean
* @usage if(stringBeginsWith('beginning','beg')){...}
*/
function stringBeginsWith($str='', $search=''){
	return (strncmp(strtolower($str), strtolower($search), strlen($search)) == 0);
	}
//---------- begin function stringContains--------------------
/**
* @describe returns true if $str contains $search (ignores case)
* @param str string
* @param search string
* @return boolean
* @usage if(stringContains('beginning','gin')){...}
*/
function stringContains($string, $search){
	if(!strlen($string) || !strlen($search)){return false;}
	return strpos(strtolower($string),strtolower($search)) !== false;
	}
//---------- begin function printValue
/**
* @describe returns an html block showing the contents of the object,array,or variable specified
* @param $v mixed The Variable to be examined.
* @return string
*	returns an html block showing the contents of the object,array,or variable specified.
* @usage 
*	echo printValue($sampleArray);
* @author slloyd
* @history bbarten 2014-01-07 added documentation
*/
function printValue($v=''){
	$type=strtolower(gettype($v));
	$plaintypes=array('string','integer');
	if(in_array($type,$plaintypes)){return $v;}
	$rtn = '<pre class="w_times" type="'.$type.'">'."\n";
	ob_start();
	print_r($v);
	$rtn .= ob_get_contents();
	ob_clean();
	flush();
	$rtn .= "\n</pre>\n";
	return $rtn;
	}
?>