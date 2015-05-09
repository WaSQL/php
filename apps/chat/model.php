<?php
/*
	Chat app


*/
/*-------------*/
function chatGetMyChatUsers($days=7){
	global $USER;
	//get a list of users that I have chatted with in the past x days
	$query="
SELECT
	u._id
	,u.firstname
	,u.lastname
	,max(c._cdate) as last_date
FROM _users u, chatlog c
WHERE
	c.msg_to=u._id
	and c.msg_from={$USER['_id']}
	and c._cdate > DATE_SUB(NOW(), INTERVAL {$days} DAY)
GROUP BY
	u._id
	,u.firstname
	,u.lastname
ORDER BY last_date desc
	";
	return getDBRecords(array('-query'=>$query));
}
function chatConfigForm(){
	global $USER;
	$rec=getDBRecord(array('-table'=>'chatconfig','user_id'=>$USER['_id']));
	$opts=array(
		'-table'	=> 'chatconfig',
		'-name'		=> 'chatconfigform',
		'-action'	=> '/apps/chat',
		'-fields'	=> 'backup_ids',
		'user_id'	=> $USER['_id'],
		'-hide'		=> 'clone',
		'func'		=> 'chatconfig2',
		'-onsubmit'	=> "return ajaxSubmitForm(this,'nulldiv');",
		'backup_ids_displayname'	=> '<span class="icon-users"></span> Select who you want to redirect messages to if you are NOT online:'
	);
	if(is_array($rec)){$opts['_id']=$rec['_id'];}
	return addEditDBForm($opts);
}
function chatShowConfigIcon(){
	global $USER;
	$rec=getDBRecord(array('-table'=>'chatconfig','user_id'=>$USER['_id']));
	if(is_array($rec) && strlen($rec['backup_ids'])){return 'icon-gear w_primary';}
	return 'icon-gear';
}
function chatGetBackupIds(){
	global $USER;
	$rec=getDBRecord(array('-table'=>'chatconfig','user_id'=>$USER['_id']));
	if(is_array($rec) && strlen($rec['backup_ids'])){return preg_split('/\:+/',trim($rec['backup_ids']));}
	return array();
}
function chatIsUserActive($id){
	$rec=getDBRecord(array('-table'=>'_users','_id'=>$id,'-fields'=>'_adate'));
	if(!is_array($rec)){return false;}
	$minutes=floor(time()-strtotime($rec['_adate']))/60;
	if($minutes < 10){return true;}
	return false;
}
/*-------------*/
function chatMessageDate($d){
	$dt=strtotime($d);
	$cdate=getdate();
	$ddate=getdate($dt);
	if($cdate['year'] != $ddate['year']){
		return date('m/d/Y h:i a',$dt);
	}
	elseif($cdate['mon'] != $ddate['mon']){
		return date('m/d h:i a',$dt);
	}
	elseif($cdate['mday'] != $ddate['mday']){
		return date('jS h:i a',$dt);
	}
	else{return date('g:i a',$dt);}

}
/*-------------*/
function chatCheckSetup(){
	global $USER;
	if(!isNum($USER['chatlog_id'])){$USER['chatlog_id']=0;}
	//check for chatlog_id in _users table
	$fields=getDBFields('_users');
	if(!in_array('department',$fields)){
    	$query="ALTER TABLE _users ADD department varchar(125) NULL";
		$ok=executeSQL($query);
	}
	if(!strlen($USER['department'])){$USER['department']='No Department';}
	//check for the chatlog table
	$table='chatlog';
	if(!isDBTable($table)){
		$fields=array(
			'msg_from'	=> 'integer NOT NULL Default 0',
			'msg_to'	=> 'integer NOT NULL Default 0',
			'msg'		=> "varchar(1500) NOT NULL Default ''"
		);
		$ok = createDBTable($table,$fields,'InnoDB');
		if($ok != 1){return false;}
		//$ok=addDBIndex(array('-table'=>$table,'-fields'=>"_cuser,category,title",'-unique'=>1));
		//Add tabledata
		$addopts=array('-table'=>"_tabledata",
			'tablename'		=> $table,
			'formfields'	=> "msg_from msg_to\r\nmsg",
			'listfields'	=> "_cdate\r\nmsg_from\r\nmsg_to",
			);
		$id=addDBRecord($addopts);
	}
	//chatconfig
	$table='chatconfig';
	if(!isDBTable($table)){
		$fields=array(
			'user_id'	=> 'integer NOT NULL UNIQUE',
			'backup_ids'=> 'varchar(254)'
		);
		$ok = createDBTable($table,$fields,'InnoDB');
		if($ok != 1){return false;}
		//Add tabledata
		$addopts=array('-table'=>"_tabledata",
			'tablename'		=> $table,
			'formfields'	=> "user_id\r\nbackup_ids",
			'listfields'	=> "_cdate\r\nuser_id\r\nbackup_ids",
		);
		$id=addDBRecord($addopts);
		$id=addDBRecord(array(
			'-table'		=> '_fielddata',
			'tablename'		=> $table,
			'fieldname'		=> 'backup_ids',
			'inputtype'		=> 'checkbox',
			'width'			=> 4,
			'displayname'	=> "Select Users for your messages to redirect to if you are not online.",
			'tvals'			=> 'select _id from _users where active=1 order by department,firstname,lastname',
			'dvals'			=> 'select firstname,lastname from _users where active=1 order by department,firstname,lastname',
		));
	}
	//clean up messages
	chatCleanupMessages();
	return true;
}
/*-------------*/
function chatGetUserList(){
	global $USER;
	$recs=getDBRecords(array(
		'-table'	=> '_users',
		'-where'	=> "active=1 and _id != {$USER['_id']}",
		'-fields'	=> "_id,concat(firstname,' ',lastname) as name,department,phone,_adate"
	));
	if(!is_array($recs)){return array();}
	foreach($recs as $i=>$rec){
    	//set color based on how active
    	$minutes=floor(time()-strtotime($rec['_adate']))/60;
    	$recs[$i]['minutes']=$minutes;
    	if($minutes < 3){
			$recs[$i]['active_level']='active';
			$recs[$i]['sort']=1;
		}
    	elseif($minutes < 10){
			$recs[$i]['active_level']='was_active';
			$recs[$i]['sort']=2;
		}
    	else{
			$recs[$i]['active_level']='inactive';
			$recs[$i]['sort']=3;
		}
	}
	//sort them by sort,name
	$recs=sortArrayByKeys($recs, array('sort'=>SORT_ASC, 'name'=>SORT_ASC));
	return $recs;
}
/*-------------*/
function chatCleanupMessages(){
	global $USER;
	//remove all records older than one year
	$ok=delDBRecord(array(
		'-table'	=> 'chatlog',
		'-where'	=> "_cdate < DATE_SUB(NOW(), INTERVAL 1 YEAR)"
	));
	return 1;
}
/*-------------*/
function chatGetChatList(){
	/*
		return all users that have:
			1. sent me a message(msg_to is 1) since I sent them a message (msg_from is 1) where their message is in the last 7 days
					msg_to={$USER['_id']}
					AND _cdate < DATE_SUB(NOW(), INTERVAL 7 Day)
					AND _id > (select max(_id) from chatlog where msg_from {$USER['_id']}
			2. that I have sent a real message to in the last 30 minutes
					_cdate < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND msg != ''
			3. That I have sent a blank message to in the last 5 minutes
					_cdate < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND msg = ''
	*/
	global $USER;
	$usermap=getDBRecords(array(
		'-table'	=> '_users',
		'active'	=> 1,
		'-fields'	=> "_id,_adate,username,concat(firstname,' ',lastname) as name,department",
		'-index'	=> '_id'
	));
	foreach($usermap as $i=>$rec){
    	//set color based on how active
    	$minutes=floor(time()-strtotime($rec['_adate']))/60;
    	if($minutes < 3){
			$usermap[$i]['active_level']='active';
			$usermap[$i]['sort']=1;
		}
    	elseif($minutes < 10){
			$usermap[$i]['active_level']='was_active';
			$usermap[$i]['sort']=2;
		}
    	else{
			$usermap[$i]['active_level']='inactive';
			$usermap[$i]['sort']=3;
		}
	}
	$query="
SELECT _cdate,_id,msg,msg_to,msg_from
FROM chatlog
WHERE
	msg_from = {$USER['_id']}
	OR msg_to = {$USER['_id']}
ORDER BY _id desc
LIMIT 5000
	";
	//get the records
	$recs= getDBRecords(array('-query'=>$query));
	//group by msg_to
	$chats=array();
	foreach($recs as $i=>$rec){
		if($rec['msg_to'] != $USER['_id']){$id=$rec['msg_to'];}
		elseif($rec['msg_from'] != $USER['_id']){$id=$rec['msg_from'];}
		else{continue;}
		if(!isset($id) || !strlen($id)){continue;}
		if(!isset($chats[$id]['id']) && $id != $USER['_id']){
				$chats[$id]=array(
					'name'			=> $usermap[$id]['name'],
					'id'			=> $id,
					'active_level'	=> $usermap[$id]['active_level'],
					'department'	=> $usermap[$id]['department'],
					'notify'		=> ''
				);
		}
		if($rec['msg_from']==$USER['_id']){$rec['class']='me';}
		else{$rec['class']='them';}
		$rec['msg']=str_replace("\\'","'",$rec['msg']);
		$chats[$id]['msgs'][]=$rec;
	}
	//echo printValue($chats);exit;
	//cleanup chat records
	foreach($chats as $id=>$chat){
		$msgs=$chat['msgs'];
		//if my last message is a - or x then skip
		$my_last_message=array();
		$their_last_message=array();
		foreach($msgs as $msg){
        	if($msg['msg_from']==$USER['_id'] && !isset($my_last_message['_id'])){
            	$my_last_message=$msg;
			}
			if($msg['msg_to']==$USER['_id'] && !in_array($msg['msg'],array('','*','-','x')) && !isset($their_last_message['_id'])){
            	$their_last_message=$msg;
			}
		}
		if(in_array($my_last_message['msg'],array('-','x')) && $their_last_message['_id'] < $my_last_message['_id']){
			unset($chats[$id]);
        	continue;
		}
		//
		//remove close messages
		foreach($msgs as $i=>$msg){
        	if(in_array($msg['msg'],array('-','x'))){
				unset($msgs[$i]);
			}
		}
		//set new class if the first message is to me and is not a *
		if($msgs[0]['msg_to']==$USER['_id'] && !in_array($msgs[0]['msg'],array('','*'))){
        	$chats[$id]['notify']='new';
		}
		if(!count($msgs)){
        	unset($chats[$id]);
        	continue;
		}
		//remove unwanted messages
		foreach($msgs as $i=>$msg){
        	if(in_array($msg['msg'],array('','*','-','x'))){
				unset($msgs[$i]);
			}
		}
		//reverse the messages
		$msgs=array_reverse($msgs);
		//assign to chats again
		$chats[$id]['msgs']=$msgs;
	}
	//echo printValue($chats);
	$chats=sortArrayByKeys($chats, array('id'=>SORT_ASC, 'name'=>SORT_ASC));
	return $chats;
}
?>
