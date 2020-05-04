<?php
//require user
global $USER;
global $PASSTHRU;
global $PAGE;
global $CONFIG;
global $APP;
//check for minimun APP settings required to run.  url, timer, users
if(!isset($APP['-url'])){
	if(isset($PAGE['name'])){
		$APP['-url']='/'.$PAGE['name'];
	}
}
if(!isset($APP['-group'])){
	$APP['-group']='GENERAL';
}
if(!isset($APP['-ajaxurl'])){
	if(isset($PAGE['name'])){
		$APP['-ajaxurl']='/t/1/'.$PAGE['name'];
	}
}
if(!isset($APP['-timer'])){
	$APP['-timer']=5;
}

if(!isUser()){
	setView('login',1);
	return;
}
switch(strtolower($PASSTHRU[0])){
	case 'config':
		setView('config',1);
		return;
	break;
	case 'playsound':
		$soundfile="{$APP['-app_path']}/notify.mp3";
		header('Content-Type: audio/mpeg');
		header('Content-Disposition: inline;filename="notify.mp3"');
		header('Content-length: '.filesize($soundfile));
    	header('Cache-Control: no-cache');
		header("Content-Transfer-Encoding: chunked");
		readfile($soundfile);
		exit;
	break;
	case 'check_for_new_messages':
		$last_message_id=(integer)$_REQUEST['last_message'];
		$cnt=chatCheckForNewMessages($last_message_id);
		if($cnt > 0){
			//new messages - go get them
			setView('get_new_messages',1);
		}
		else{
			setView('do_nothing',1);
		}
		return;
	break;
	case 'get_new_messages':
		$messages=chatGetMessages();
		setView(array('messages','notify'),1);
		return;
	break;
	case 'msg':
		$messages=chatAddMessage(stripslashes($_REQUEST['msg']),(integer)$_REQUEST['msg_to']);
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