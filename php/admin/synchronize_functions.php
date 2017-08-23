<?php
function synchronizeSetActive($key){
	global $setactive;
	if(isset($setactive) && strlen($setactive)){return '';}
	$setactive=$key;
	return ' active';
}
function syncronizeGetTarget(){
	$rec=getDBRecord(array(
		'-table'=>'_settings',
		'user_id'=>0,
		'key_name'=>'wasql_synchronize_slave',
		'-fields'=>'_id,key_name,key_value'
	));
	return $rec['key_value'];
}
function synchronizeGetAuth($user,$pass){
	//build the load
	$load=array(
		'func'		=> 'auth',
		'username'	=> $user,
		'password'	=> $pass,
		'_login'	=> 1,
		'_pwe'		=> 1
	);
	return synchronizePost($load,1);
}
function synchronizeGetTargetRecord($table,$id,$fields){
	//build the load
	$load=array(
		'func'		=> 'get_record',
		'table'		=> $table,
		'id'		=> $id,
		'fields'	=> $fields
	);
	$rec=synchronizePost($load,0);
	foreach($rec as $k=>$v){
		if(strlen(trim($v))){
			$rec[$k]=base64_decode($v);
		}
	}
	return $rec;
}
function synchronizeGetTargetSchema($table){
	//build the load
	$load=array(
		'func'		=> 'get_schema',
		'table'		=> $table
	);
	return synchronizePost($load,0);
}
function synchronizeUpdateTargetRecords($table,$recs){
	//convert the record values into Base64 so they will for sure convert to json
	foreach($recs as $i=>$rec){
		foreach($rec as $k=>$v){
			if(strlen(trim($v))){
				$recs[$i][$k]=base64_encode($v);
			}
		}
	}
	//build the load
	$load=array(
		'func'		=> 'update_records',
		'table'		=> $table,
		'records'	=> $recs
	);
	//echo printValue($load);exit;
	return synchronizePost($load,0);
}
function synchronizeUpdateTargetSchemas($schemas){
	//build the load
	$load=array(
		'func'		=> 'update_schemas',
		'table'		=> 'schemas',
		'records'	=> $schemas
	);
	return synchronizePost($load,0);
}
function synchronizePost($load,$plain=0){
	global $USER;
	//unset($_SESSION['sync_target_url']);
	if(!isset($_SESSION['sync_target_url']) || !strlen($_SESSION['sync_target_url'])){
		global $ALLCONFIG;
		$target=$_SESSION['sync_target'];
		if(!isset($ALLCONFIG[$target])){
			return json_encode(array('error'=>'invalid target'));
		}
		$uhost=getUniqueHost($ALLCONFIG[$target]['name']);
		$base=$ALLCONFIG[$target]['name'];
		if($uhost==$ALLCONFIG[$target]['name'] && stringContains($uhost,'.')){$base="www.{$base}";}
		if(isset($ALLCONFIG[$target]['admin_url']) && strlen($ALLCONFIG[$target]['admin_url'])){
			$_SESSION['sync_target_url']=$ALLCONFIG[$target]['admin_url'];
		}
		elseif(isset($ALLCONFIG[$target]['admin_secure']) && $ALLCONFIG[$target]['admin_secure']==1){
			$_SESSION['sync_target_url']="https://{$base}/php/admin.php";
		}
		else{
			$_SESSION['sync_target_url']="http://{$base}/php/admin.php";
		}
	}
	if($plain==1){
		$postopts=$load;
		$postopts['_menu']='synchronize';
		$postopts['load']=base64_encode(json_encode($load));
		$postopts['-follow']=1;
		$postopts['-nossl']=1;
		$postopts['_noguid']=1;
	}
	else{
		$postopts=array(
			'_menu'		=> 'synchronize',
			'load'		=> base64_encode(json_encode($load)),
			'_auth'		=> $_SESSION['sync_target_auth'],
			'_noguid'	=> 1,
			'-follow'	=> 1,
			'-nossl'	=> 1
		);
	}
	//echo $plain.$_SESSION['sync_target_url'].printValue($postopts);exit;
	$post=postURL($_SESSION['sync_target_url'],$postopts);
	//echo $post['body'];exit;
	if(isset($post['error'])){
		return array('error'=>$_SESSION['sync_target_url'].$post['error']);
	}
	elseif(!strlen($post['body'])){
		return array('error'=>$_SESSION['sync_target_url'].printValue($post));
	}
	else{
		$json=json_decode(base64_decode($post['body']),true);
		if(!is_array($json)){
			$json=json_decode($post['body'],true);
			if(!is_array($json)){
				return array('error'=>$_SESSION['sync_target_url'].$post['body']);
			}
		}
		return $json;
	}
	return array('error'=>$_SESSION['sync_target_url'].json_encode($post));
}
function synchronizeGetChanges($tables){
	global $USER;
	$fields=array();
	$xfields=array('_id','_cuser','_cdate','_euser','_edate');
	$markers=array('name','fieldname','tablename','tabledesc','displayname','username','description','desc','title','email');
	foreach($tables as $table){
		$fields[$table]=adminGetSynchronizeFields($table);
		$fields[$table]=array_merge($fields[$table],$xfields);
		//determine marker fields
		foreach($markers as $marker){
			if(in_array($marker,$fields[$table])){
				$fields[$table][]="{$marker} as _marker_";
				break;
			}
		}
	}
	//echprintValue($fields);exit;
	$xfields[]='_marker_';
	//get source
	$source_recs=array();
	foreach($fields as $table=>$fieldset){
		if(!in_array('_id',$fieldset)){$fieldset[]='_id';}
		$fieldstr=implode(',',$fieldset);
		$source_recs[$table]=getDBRecords(array('-table'=>$table,'-fields'=>$fieldstr,'-eval'=>'md5','-noeval'=>$xfields,'-index'=>'_id'));
	}
	//get target
	$load=array(
		'func'		=> 'get_changes',
		'username'	=> $USER['username'],
		'fields'	=> $fields
	);
	$json=synchronizePost($load);
	if(isset($json['error'])){return $json;}
	$target_recs=$json;
	//echo printValue($target_recs);exit;
	//compare source to target
	$changes=array();
	foreach($source_recs as $table=>$srecs){
		if($table=='_schema_'){continue;}
		if(!isset($target_recs[$table])){
			//new table - we catch this in the _schema_ check
			continue;
		}
		else{
			foreach($srecs as $id=>$srec){
				if(!isset($target_recs[$table][$id])){
					//new record
					if($table == '_fielddata'){
						if(!isWasqlField($srec['_marker_'])){
							$changes[$table][$id]['NEW record']=1;
						}
					}
					else{
						$changes[$table][$id]['NEW record']=1;
					}
				}
				else{
					foreach($srec as $skey=>$sval){
						if(isWasqlField($skey)){continue;}
						if(!isset($target_recs[$table][$id][$skey])){
							//new field - we catch this in the _schema_ check
							continue;
						}
						else{
							if($target_recs[$table][$id][$skey] != $sval){
								$changes[$table][$id][$skey]=1;
							}
						}
					}
				}
			}
		}
	}
	$recs=array();
	foreach($changes as $table=>$crecs){
		foreach($crecs as $id=>$crec){
			$rec=array(
				'id'=>$id,
				'tablename'=>$table,
				'tabname'=>$table,
				'marker'=>$source_recs[$table][$id]['_marker_'],
				'changes'=>implode(', ',array_keys($crec)),
				'changed_by'=>isset($source_recs[$table][$id]['_euser']) && isNum($source_recs[$table][$id]['_euser'])?$source_recs[$table][$id]['_euser']:$source_recs[$table][$id]['_cuser'],
				'changed_date'=>isset($source_recs[$table][$id]['_edate']) && strlen($source_recs[$table][$id]['_edate'])?$source_recs[$table][$id]['_edate']:$source_recs[$table][$id]['_cdate'],
			);
			if(strlen($rec['changed_date'])){
				$rec['changed_age']=verboseTime(time()-strtotime($rec['changed_date']),false,true);
			}
			$urec=getDBRecordById('_users',$rec['changed_by'],0,'username');
			if(isset($urec['username'])){
				$rec['changed_by']=$urec['username'];
			}
			$recs[$table][]=$rec;
		}
	}
	//compare schema
	$tables=getDBTables();
	foreach($tables as $table){
		$changes=array();
		if(!isset($target_recs['_schema_'][$table])){
			//table is new
			$changes[]="NEW Table: {$table}";
		}
		else{
			$info=getDBFieldInfo($table);
			foreach($info as $field=>$f){
				if(!isset($target_recs['_schema_'][$table][$field])){
					//field in this table is new
					$changes[]="New Field: {$field} {$f['_dbtype_ex']}";
				}
				elseif($target_recs['_schema_'][$table][$field] != $f['_dbtype_ex']){
					//field in this table has changed
					$changes[]="Changed Field: {$field} {$f['_dbtype_ex']}";
				}
			}
		}
		if(count($changes)){
			if(!is_array($recs['schema'])){$recs['schema']=array();}
			$id=count($recs['schema'])+1;
			$recs['schema'][]=array(
				'id'=>$table,
				'tablename'=>$table,
				'tabname'=>'schema',
				'marker'=>'schema',
				'changes'=>implode('<br />'.PHP_EOL,$changes),
			);
		}
	}
	return $recs;
}
?>
