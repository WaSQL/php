<?php
/*
<div style="border-left:1px solid #ccc;flex-grow:1;"><?=chatFileField();?></div>
*/

function chatEditMessage($id){
	global $APP;
	$opts=array(
		'-table'=>'app_chat',
		'_id'=>$id,
		'-fields'=>getView('message_edit_fields'),
		'-formname'=>"edit_message_form_{$id}",
		'_action'=>'EDIT',
		'setprocessing'=>0,
		'-hide'=>'clone,delete,reset,save',
		'-action'=>$APP['-ajaxurl']."/app_chat_edit_processed/{$id}",
		'-onsubmit'=>"return ajaxSubmitForm(this,'chat_message_{$id}');"
	);
	//return printValue($opts);
	return addEditDBForm($opts).buildOnLoad("document.edit_message_form_{$id}.msg.focus();");

}
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
function chatAddMessage($params){
	global $USER;
	global $APP;
	$msg=stripslashes($params['msg']);
	$msg_to=(integer)$msg_to;
	$addopts=array(
		'-table'=>'app_chat',
		'msg_from'=>$USER['_id'],
		'msg_to'=>$msg_to,
		'msg_group'=>$APP['-group'],
		'msg'=>$msg
	);
	//process attachments
	$path=$_SERVER['DOCUMENT_ROOT'].'/app_chat_files';
	if(!is_dir($path)){
		buildDir($path);
	}
	$attachments=array();
	foreach($params as $k=>$v){
		if(stringBeginsWith($k,'msg_attachment_')){
	    	list($data,$type,$enc,$encodedString)=preg_split('/[\:;,]/',$v,4);
            //make sure it is an extension we support
            $ftype='';
            unset($ext);
            if(preg_match('/^(image|audio|video|text)\/(.+)$/i',$type,$m)){
            	$ext=$m[2];
            	$ftype=$m[1];
            }
            elseif(stringContains($type,'gzip')){
            	$ftype=='file';
            	$ext='gz';
            }
            elseif(stringContains($type,'zip')){
            	$ftype=='file';
            	$ext='zip';
            }
            elseif(stringContains($type,'pdf')){
            	$ftype=='file';
            	$ext='pdf';
            }
			if(!isset($ext)){
				echo "invalid type: {$type}<br>";
				continue;
			}
			$crc=encodeCRC($encodedString);
        	$file="{$k}_{$crc}.{$ext}";
			$decoded=base64_decode($encodedString);
			$afile="{$path}/{$file}";
			//remove the file if it exists already
			if(file_exists($afile)){unlink($afile);}
			//save the file
			file_put_contents($afile,$decoded);
			if(file_exists($afile)){
				//replace all instances of this image with the src path to the saved file
                $attachments[$ftype][]="/app_chat_files/{$file}";
			}
		}
	}
	if(count($attachments)){
		$addopts['attachments']=json_encode($attachments);
	}
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
	if(count($uids)){
		$uidstr=implode(',',$uids);
		$usermap=getDBRecords(array(
			'-table'=>'_users',
			'active'=>1,
			'-where'=>"_id in ({$uidstr})",
			'-index'=>'_id'
		));
	}
	else{
		$usermap=array();
	}
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
		//attachments
		if(strlen($rec['attachments'])){
			$recs[$i]['attachments']=json_decode($rec['attachments'],true);
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
		if(isset($APP['-message_eval']) && function_exists($APP['-message_eval'])){
			$recs[$i]=call_user_func($APP['-message_eval'],$recs[$i]);
		}
	}
	//echo printValue($recs);exit;
	return $recs;
}
function chatToField(){
	global $USER;
	global $APP;
	$getopts=array(
		'-table'=>'_users',
		'active'=>1,
		'-fields'=>'firstname,lastname,username,_id',
		'-order'=>'firstname,lastname,username,_id'
	);
	if(isset($APP['-user_filter'])){
		$getopts['-filter']=$APP['-user_filter'];
	}
	$recs=getDBRecords($getopts);
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
		'data-navigate'=>1,
		'data-navigate-group'=>'app_chat',
		'data-navigate-focus'=>'Alt+u',
		'data-navigate-up'=>'false',
		'data-navigate-down'=>'false',
		'data-navigate-left'=>'false',
		'data-navigate-right'=>'false',
	);
	//echo printValue($opts);exit;
	return buildFormSelect('msg_to',$opts,$params);
}
function chatInputField(){
	global $USER;
	return buildFormText('msg',array(
		'autofocus'=>'autofocus',
		'class'=>'input chat_input',
		'style'=>'flex-grow:9;',
		'data-navigate'=>1,
		'data-navigate-group'=>'app_chat',
		'data-navigate-focus'=>'Alt+c',
		'data-navigate-up'=>'chatEditLast();',
		'data-navigate-down'=>'false',
		'data-navigate-left'=>'false',
		'data-navigate-right'=>'false',
		'data-accept'=>"attachments",
		'data-accept-target'=>"chat_msg_attachments"
	));
}
function chatFileField(){
	global $USER;
	return buildFormFile('msg_file',array(
		'-icon'=>'icon-attach',
		'text'=>'',
		'class'=>'input chat_input',
		'style'=>'flex-grow:1;',
		'data-navigate'=>1,
		'data-navigate-group'=>'app_chat',
		'data-navigate-focus'=>'Alt+f',
		'data-navigate-up'=>'false',
		'data-navigate-down'=>'false',
		'data-navigate-left'=>'false',
		'data-navigate-right'=>'false',

	));
}
function chatSetup(){
	$table='app_chat';
	if(!isDBTable($table)){
		$fields=array(
			'msg_from'	=> 'integer NOT NULL Default 0',
			'msg_to'	=> 'integer NOT NULL Default 0',
			'msg'		=> "varchar(1500) NOT NULL Default ''",
			'msg_group'	=> 'varchar(50) NOT NULL',
			'active'	=> 'tinyint(1) NOT NULL Default 1',
			'attachments'=>'json NULL'

		);
		$ok = createDBTable($table,$fields);
		if($ok != 1){return false;}
	}
}
?>