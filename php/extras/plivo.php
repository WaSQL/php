<?php
/*
	Plivo extra used to send sms messages:
		https://www.plivo.com/
		https://console.plivo.com/dashboard/

		NOTE: you must set plivo_auth_id, plivo_auth_token, and plivo_from in config.xml for this to work

*/
global $CONFIG;
if(!isset($CONFIG['plivo_auth_id'])){
	echo "plivo extra error - missing plivo_auth_id in config";
	exit;
}
if(!isset($CONFIG['plivo_auth_token'])){
	echo "plivo extra error - missing plivo_auth_token in config";
	exit;
}
if(!isset($CONFIG['plivo_from'])){
	echo "plivo extra error - missing plivo_from in config";
	exit;
}
$CONFIG['plivo_loaded']=1;
//---------- begin function plivoSendMsg ----
/**
* sends an sms message using the Plivo API.
* @param to text - 10 digit phone number to send to
* @param txt text - message to send
* @param [rtn] boolean - if set to true then it returns without true, otherwise it return a json array of the response
* @usage  $ok=plivoSendMsg(16541236547,'hello there',true);
* @author slloyd
*/
function plivoSendMsg($to,$txt,$rtn=true){
	$from=plivoFrom();
	$to=preg_replace('/[^0-9]+/','',$to);
	if(strlen($to)==10){
		$to="1{$to}";
	}
	if(strlen($to) != 11){
		return false;
	}
	if(!strlen($txt)){
		return false;
	}
	$url='https://api.plivo.com/v1/Account/'.plivoAuthID().'/Message/';
	$postopts=array(
		'-auth'=>plivoAuth(),
		'src'=>$from,
		'dst'=>$to,
		'text'=>$txt,
		'-json'=>1
	);
	$post=postURL($url,$postopts);
	//echo printValue($postopts).printValue($post);exit;
	if($rtn==1){
		return true;
	}
	//echo printValue($post);exit;
	if(isset($post['json_array'])){
		return $post['json_array'];
	}
	echo "plivo send error: ".$post['body'];
	exit;
}
//---------- begin function plivoAuth---------------------------------------
/**
* @exclude  - this function is a helper function and thus excluded from the manual
*/
function plivoAuth(){
	return plivoAuthID().':'.plivoAuthToken();
}
//---------- begin function plivoAuthID---------------------------------------
/**
* @exclude  - this function is a helper function and thus excluded from the manual
*/
function plivoAuthID(){
	global $CONFIG;
	return $CONFIG['plivo_auth_id'];
}
//---------- begin function plivoAuthToken---------------------------------------
/**
* @exclude  - this function is a helper function and thus excluded from the manual
*/
function plivoAuthToken(){
	global $CONFIG;
	return $CONFIG['plivo_auth_token'];
}
//---------- begin function plivoFrom---------------------------------------
/**
* @exclude  - this function is a helper function and thus excluded from the manual
*/
function plivoFrom(){
	global $CONFIG;
	return $CONFIG['plivo_from'];
}
?>