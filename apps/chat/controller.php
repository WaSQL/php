<?php
//require user
global $USER;
global $PASSTHRU;
global $PAGE;
global $CONFIG;
global $chat;

if(isset($_REQUEST['chat_b64'])){
	$chat=json_decode(decodeBase64($_REQUEST['chat_b64']),true);
}
else{
	$chat=array();
	$chatkeys=array('chat_url','chat_group');
	foreach($chatkeys as $chatkey){
		if(isset($_REQUEST[$chatkey])){$chat[$chatkey]=$_REQUEST[$chatkey];}
		elseif(isset($CONFIG[$chatkey])){$chat[$chatkey]=$CONFIG[$chatkey];}
	}
	if(!isset($chat['chat_url'])){$chat['chat_url']='/'.$PAGE['name'];}	
	if(!isset($chat['chat_group'])){$chat['chat_group']=$_SERVER['HTTP_HOST'];}	
	$chat['colors']=chatGetColors();
}

if(!isUser()){
	setView('login',1);
	return;
}
switch(strtolower($PASSTHRU[0])){
	case 'msg':
		$messages=chatAddMessage(stripslashes($_REQUEST['msg']));
		setView('messages',1);
		return;
	break;
	default:
		$ok=chatSetup();
		$messages=chatGetMessages();
		setView('default');
	break;
}
?>