<?php
	loadExtrasCss('accordian');
	global $USER;
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
			$users=chatGetUserList();
			setView('_userlist',1);
			return;
		break;
		case 'chatlist':
			$chats=chatGetChatList();
			setView('_chatlist',1);
			return;
		break;
		case 'sendmessage':
			$msg_to=addslashes($_REQUEST['msg_to']);
			$msg=addslashes($_REQUEST['msg']);
			if($msg=='*'){
				$_REQUEST['setfocus']=$msg_to;
			}
			$ok=addDBRecord(array(
				'-table'	=> 'chatlog',
				'msg_from'	=> $USER['_id'],
				'msg_to'	=> $msg_to,
				'msg'		=> $msg
			));
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
