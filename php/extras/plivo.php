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
//---------- begin function plivoSendMsg ----
/**
* sends an sms message using the Plivo API.
* @param to text - 10 digit phone number to send to
* @param txt text - message to send
* @param [rtn] boolean - if set to true then it returns without true, otherwise it return a json array of the response
* @usage  $ok=plivoUpdatePlivoLog($filters);
* @author slloyd
* @reference https://www.plivo.com/docs/sms/api/message#list-all-messages
*/
function plivoUpdatePlivoLog($filters=array()){
	if(!isDBTable('plivo_log')){
		$ok=plivoCreatePlivoLogTable();
	}
	date_default_timezone_set('UTC');
	//get max message_time
	$rec=getDBRecord("select max(message_time) as message_time from plivo_log");
	if(isset($rec['message_time'])){
		$filters['message_time__gte']=gmdate('Y-m-d H:i:s',strtotime($rec['message_time']));
	}
	$filters['-auth']=plivoAuth();
	$filters['-json']=1;
	$filters['-method']='GET';
	$url='https://api.plivo.com/v1/Account/'.plivoAuthID().'/Message/';
	$post=postURL($url,$filters);
	if(!isset($post['json_array']) || !count($post['json_array'])){
		return 0;
	}
	//echo printValue($filters).printValue($post);exit;
	$cnt=0;
	while(1){
		foreach($post['json_array']['objects'] as $rec){
			$rec['-table']='plivo_log';
			$rec['message_time_ori']=$rec['message_time'];
			$rec['message_time']=date('Y-m-d H:i:s',strtotime($rec['message_time']));
			$rec['-upsert']='error_code,from_number,message_direction,message_state,message_time,message_type,to_number,total_amount,total_rate,units,_cuser,_cdate';
			//echo printValue($rec);exit;
			$id=addDBRecord($rec);
			if(isNum($id)){
				$cnt+=1;
			}
			
		}
		if(isset($post['json_array']['meta']['next']) && strlen($post['json_array']['meta']['next'])){
			$url='https://api.plivo.com'.$post['json_array']['meta']['next'];
			$post=postURL($url,array('-method'=>'GET','-auth'=>$filters['-auth'],'-json'=>1));
			//echo $url.printValue($post);exit;
			if(!isset($post['json_array']['objects'])){
				break;
			}
		}
		else{
			break;
		}
		//echo printValue($post['json_array']);break;
	}
	return $cnt;
}
function plivoCreatePlivoLogTable(){
	if(isDBTable('plivo_log')){return false;}
	$fields=array(
		'_id'	=> databasePrimaryKeyFieldString(),
		'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'=> databaseDataType('int')." NOT NULL",
		'_edate'=> databaseDataType('datetime')." NULL",
		'_euser'=> databaseDataType('int')." NULL",
		'error_code'=>databaseDataType('varchar(5)')." NULL",
		'from_number'=>databaseDataType('varchar(12)')." NOT NULL",
		'message_direction'=>databaseDataType('varchar(25)')." NULL",
		'message_state'=>databaseDataType('varchar(25)')." NULL",
		'message_time'=>databaseDataType('datetime')." NULL",
		'message_type'=>databaseDataType('varchar(5)')." NULL",
		'message_uuid'=>databaseDataType('varchar(60)')."NOT NULL UNIQUE",
		'to_number'=>databaseDataType('varchar(12)')." NOT NULL",
		'total_amount'=>databaseDataType('float(12,5)')." NOT NULL Default 0",
		'total_rate'=>databaseDataType('float(12,5)')." NOT NULL Default 0",
		'units'=>databaseDataType('int')." NULL",
	);
	$ok = createDBTable('plivo_log',$fields,'InnoDB');
	$ok=addDBIndex(array('-table'=>'plivo_log','-fields'=>"message_time"));
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