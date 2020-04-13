<?php
function chatGetColors(){
	$recs=getDBRecords(array(
		'-table'=>'colors',
		'-fields'=>'_id,hex',
		'-where'=>"r between 50 and 180 and g between 50 and 180 and b between 50 and 180",
		'-index'=>'hex'
	));
	return array_keys($recs);
}
function chatColorsExtra($recs){
	foreach($recs as $i=>$rec){
		$recs[$i]['color']='<div style="width:50px;height;12px;background:'.$rec['hex'].'">&nbsp;</div>';
	}
	return $recs;
}
function chatAddMessage($msg){
	global $USER;
	global $chat;
	$addopts=array(
		'-table'=>'app_chat',
		'msg_from'=>$USER['_id'],
		'msg_to'=>(integer)$_REQUEST['msg_to'],
		'msg_group'=>$chat['chat_group'],
		'msg'=>$msg
	);
	$ok=addDBRecord($addopts);
	return chatGetMessages();
}
function chatGetMessages(){
	global $USER;
	global $chat;
	$usermap=getDBRecords(array(
		'-table'=>'_users',
		'active'=>1,
		'-order'=>'firstname,lastname,username,_id',
		'-index'=>'_id'
	));
	$opts=array(
		'-table'=>'app_chat',
		'-where'=>"active=1 and msg_to in (0,{$USER['_id']}) and msg_group='{$chat['chat_group']}'",
	);
	$colors=$chat['colors'];
	$recs=getDBRecords($opts);
	foreach($recs as $i=>$rec){
		$tid=$rec['msg_to'];
		$fid=$rec['msg_from'];
		if($tid==$USER['_id']){
			//private message
			$recs[$i]['msg_icon']='icon-user';
		}
		else{
			//To Everyone
			$recs[$i]['msg_icon']='icon-users';
		}
		if(isset($usermap[$tid])){
			//name
			if(isset($usermap[$tid]['chat_name'])){
				$name=$usermap[$tid]['chat_name'];
			}
			else{
				$name="{$usermap[$tid]['firstname']} {$usermap[$tid]['lastname']}";
				if(!strlen(trim($name))){$name="{$usermap[$tid]['username']}";}
				if(!strlen(trim($name))){$name="User {$usermap[$tid]['_id']}";}	
			}
			$recs[$i]['msg_to_name']=$name;
			//color
			if(isset($usermap[$tid]['color'])){
				$recs[$i]['msg_to_color']=$usermap[$tid]['color'];
			}
			elseif(count($colors)){
				$recs[$i]['msg_to_color']=$usermap[$tid]['color']=array_shift($colors);
			}
		}
		if(isset($usermap[$fid])){
			//name
			if(isset($usermap[$tid]['chat_name'])){
				$name=$usermap[$tid]['chat_name'];
			}
			else{
				$name="{$usermap[$fid]['firstname']} {$usermap[$fid]['lastname']}";
				if(!strlen(trim($name))){$name="{$usermap[$fid]['username']}";}
				if(!strlen(trim($name))){$name="User {$usermap[$fid]['_id']}";}
			}
			$recs[$i]['msg_from_name']=$name;
			//color
			if(isset($usermap[$fid]['color'])){
				$recs[$i]['msg_from_color']=$usermap[$fid]['color'];
			}
			elseif(count($colors)){
				$recs[$i]['msg_from_color']=$usermap[$fid]['color']=array_shift($colors);
			}
		}
	}
	//echo printValue($recs);exit;
	return $recs;
}
function chatToField(){
	global $USER;
	global $chat;
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