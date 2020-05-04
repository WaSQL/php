<?php
function chatConfigForm(){
	global $USER;
	global $APP;
	$fields=getDBFields('_users');
	$cfields=array();
	$opts=array(
		'-table'=>'_users',
		'_id'=>$USER['_id'],
		'firstname_autofocus'=>'autofocus',
		'-style_all'=>'width:100%',
		'-action'=>$APP['-url'],
		'-hide'=>'clone,delete',
	);
	if(in_array('chatname',$fields)){
		$cfields[]='chatname';
		$opts['chatname_autofocus']='autofocus';
	}
	else{
		$cfields[]='firstname';
		$opts['firstname_autofocus']='autofocus';
	}
	if(in_array('color',$fields)){$cfields[]='color';}
	if(in_array('picture',$fields)){$cfields[]='picture';}
	$opts['-fields']=$cfields;
	//return printValue($fields).printValue($opts);
	return addEditDBForm($opts);
}
function chatCheckForNewMessages($last){
	global $USER;
	global $APP;
	$msg_to=(integer)$msg_to;
	$opts=array(
		'-table'=>'app_chat',
		'-where'=>"active=1 and _id > {$last} and (msg_to in (0,{$USER['_id']}) or msg_from={$USER['_id']}) and msg_group='{$APP['-group']}'",
	);
	return getDBCount($opts);
}
function chatAddMessage($msg,$msg_to=0){
	global $USER;
	global $APP;
	$msg_to=(integer)$msg_to;
	$addopts=array(
		'-table'=>'app_chat',
		'msg_from'=>$USER['_id'],
		'msg_to'=>$msg_to,
		'msg_group'=>$APP['-group'],
		'msg'=>$msg
	);
	$id=addDBRecord($addopts);
	if(!isNum($id)){
		echo printValue($id);exit;
	}
	return chatGetMessages();
}
function chatGetMessages($offset=0){
	global $USER;
	global $APP;
	$opts=array(
		'-table'=>'app_chat',
		'-where'=>"active=1 and (msg_to in (0,{$USER['_id']}) or msg_from={$USER['_id']}) and msg_group='{$APP['-group']}'",
		'-order'=>'_id desc',
		'-limit'=>500
	);
	$recs=getDBRecords($opts);
	$APP['last_message_id']=$recs[0]['_id'];
	$recs=array_reverse($recs);
	//get list of user ids
	$uids=array();
	foreach($recs as $i=>$rec){
		if(!in_array($rec['msg_from'],$uids)){$uids[]=$rec['msg_from'];}
		if(!in_array($rec['msg_to'],$uids)){$uids[]=$rec['msg_to'];}
	}
	$uidstr=implode(',',$uids);
	$usermap=getDBRecords(array(
		'-table'=>'_users',
		'active'=>1,
		'-where'=>"_id in ({$uidstr})",
		'-index'=>'_id'
	));
	//echo printValue($usermap);exit;
	//set the last message id in APP
	foreach($recs as $i=>$rec){
		$tid=$rec['msg_to'];
		$fid=$rec['msg_from'];
		if($tid != 0){
			//private message
			$recs[$i]['msg_icon']='icon-user';
		}
		else{
			//To Everyone
			$recs[$i]['msg_icon']='icon-users w_red';
		}
		//map user name and photo/picture
		if(isset($usermap[$rec['msg_to']])){
			$cuser=$usermap[$rec['msg_to']];
			//name
			if(isset($cuser['chatname']) && strlen($cuser['chatname'])){
				$recs[$i]['msg_to_name']=$cuser['chatname'];
			}
			elseif(isset($cuser['firstname']) && strlen($cuser['firstname'])){
				$recs[$i]['msg_to_name']=$cuser['firstname'];
			}
			else{
				$recs[$i]['msg_to_name']=$cuser['username'];
			}
			//photo or picture
			if(isset($cuser['picture']) && strlen($cuser['picture'])){
				$recs[$i]['msg_to_photo']=$cuser['picture'];
			}
			elseif(isset($cuser['photo']) && strlen($cuser['photo'])){
				$recs[$i]['msg_to_photo']=$cuser['picture'];
			}
			
		}
		if(isset($usermap[$rec['msg_from']])){
			$cuser=$usermap[$rec['msg_from']];
			//name
			if(isset($cuser['chatname']) && strlen($cuser['chatname'])){
				$recs[$i]['msg_from_name']=$cuser['chatname'];
			}
			elseif(isset($cuser['firstname']) && strlen($cuser['firstname'])){
				$recs[$i]['msg_from_name']=$cuser['firstname'];
			}
			else{
				$recs[$i]['msg_from_name']=$cuser['username'];
			}
			//photo or picture
			if(isset($cuser['picture']) && strlen($cuser['picture'])){
				$recs[$i]['msg_from_photo']=$cuser['picture'];
			}
			elseif(isset($cuser['photo']) && strlen($cuser['photo'])){
				$recs[$i]['msg_from_photo']=$cuser['photo'];
			}
			//color
			if(isset($cuser['color']) && strlen($cuser['color'])){
				$recs[$i]['msg_from_color']=$cuser['color'];
			}
			
		}
		if(isset($APP['-message_eval'])){
			$recs[$i]=call_user_func($APP['-message_eval'],$recs[$i]);
		}
	}
	//echo printValue($recs);exit;
	return $recs;
}
function chatToField(){
	global $USER;
	global $APP;
	$recs=getDBRecords(array(
		'-table'=>'_users',
		'active'=>1,
		'-fields'=>'firstname,lastname,username,_id',
		'-order'=>'firstname,lastname,username,_id'
	));
	$opts=array();
	foreach($recs as $rec){
		$name="{$rec['firstname']} {$rec['lastname']}";
		if(!strlen(trim($name))){$name="{$rec['username']}";}
		if(!strlen(trim($name))){$name="User {$rec['_id']}";}
		$opts[$rec['_id']]=$name;
	}
	$params=array(
		'message'=>'To Everyone',
		'style'=>'font-size:0.8rem;flex-grow:2;width:15%;',
	);
	//echo printValue($opts);exit;
	return buildFormSelect('msg_to',$opts,$params);
}
function chatInputField(){
	return buildFormText('msg',array('autofocus'=>'autofocus','class'=>'input chat_input','style'=>'flex-grow:9;'));
}
function chatSetup(){
	$table='app_chat';
	if(!isDBTable($table)){
		$fields=array(
			'msg_from'	=> 'integer NOT NULL Default 0',
			'msg_to'	=> 'integer NOT NULL Default 0',
			'msg'		=> "varchar(1500) NOT NULL Default ''",
			'msg_group'	=> 'varchar(50) NOT NULL',
			'active'	=> 'tinyint(1) NOT NULL Default 1'
		);
		$ok = createDBTable($table,$fields);
		if($ok != 1){return false;}
	}
}
?>