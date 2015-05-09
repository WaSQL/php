<?php
	loadExtrasCss('accordian');
	global $USER;
	processActions();
	chatCheckSetup();
	$chatTitle=isset($_REQUEST['chat_title'])?$_REQUEST['chat_title']:'chatjat';
	if(!isUser()){
    	setView('login',1);
    	return;
	}
	$chat=array();
	//get users in groups
	switch(strtolower($_REQUEST['func'])){
		case 'mychatusers':
			$users=chatGetMyChatUsers();
			setView('mychatusers',1);
			return;
		break;
		case 'userlist':
			$chat['userlist']=chatGetUserList();
			setView('_userlist',1);
			return;
		break;
		case 'chatlist':
			$chat['chatlist']=chatGetChatList();
			setView('chatlist',1);
			return;
		break;
		case 'chatconfig':
			setView('chatconfig',1);
			return;
		break;
		case 'chatconfig2':
			setView('chatconfig2',1);
			return;
		break;
		case 'sendmessage':
			$msg_to=addslashes($_REQUEST['msg_to']);
			$msg=addslashes($_REQUEST['msg']);
			if($msg=='*'){
				$_REQUEST['setfocus']=$msg_to;
			}
			//if the msg_to is not active then send the message to backup ids instead
			$tos=array();
			if(chatIsUserActive($msg_to)){$tos[]=$msg_to;}
			else{
				$tos=chatGetBackupIds();
			}
			foreach($tos as $to){
				$ok=addDBRecord(array(
					'-table'	=> 'chatlog',
					'msg_from'	=> $USER['_id'],
					'msg_to'	=> $to,
					'msg'		=> $msg
				));
			}
			$chats=chatGetChatList();
			setView('_chatlist',1);
			if(isset($_REQUEST['formid'])){
				$formid=addslashes($_REQUEST['formid']);
				setView('_focusform');
			}
		break;
    	default:
    		$chat['userlist']=chatGetUserList();
    		$chat['chatlist']=chatGetChatList();
    		setView('default');
    	break;
	}
?>
