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
function synchronizeGetTargetIndexes($table){
	//build the load
	$load=array(
		'func'		=> 'get_indexes',
		'table'		=> $table
	);
	return synchronizePost($load,0);
}
function synchronizeUpdateTargetIndexes($indexes){
	//build the load
	$load=array(
		'func'		=> 'update_indexes',
		'table'		=> 'indexes',
		'records'	=> $indexes
	);
	return synchronizePost($load,0);
}
/**
* @describe returns the non-primary indexes on $table, grouped by index name
* @param table string
* @return array keyed by index name: array('unique'=>0/1,'fulltext'=>0/1,'columns'=>array(...))
* @usage $info=adminGetTableIndexInfo('_pages');
*/
function adminGetTableIndexInfo($table){
	$rows=getDBTableIndexes($table);
	$info=array();
	if(!is_array($rows)){return $info;}
	foreach($rows as $row){
		$keyname=$row['key_name'];
		if(strtoupper($keyname)=='PRIMARY'){continue;}
		if(!isset($info[$keyname])){
			$info[$keyname]=array(
				'unique'	=> (isset($row['non_unique']) && $row['non_unique']==0)?1:0,
				'fulltext'	=> (isset($row['index_type']) && strtoupper($row['index_type'])=='FULLTEXT')?1:0,
				'columns'	=> array()
			);
		}
		$seq=isset($row['seq_in_index']) && isNum($row['seq_in_index'])?(int)$row['seq_in_index']:(count($info[$keyname]['columns'])+1);
		$info[$keyname]['columns'][$seq]=$row['column_name'];
	}
	foreach($info as $keyname=>$data){
		ksort($info[$keyname]['columns']);
		$info[$keyname]['columns']=array_values($info[$keyname]['columns']);
	}
	ksort($info);
	return $info;
}
/**
* @describe formats one index's info into a comparable display/definition string
* @param keyname string - index name
* @param data array - array('unique'=>0/1,'fulltext'=>0/1,'columns'=>array(...))
* @return string
* @usage $def=adminFormatIndexDef('uidx_pages_name',$data);
*/
function adminFormatIndexDef($keyname,$data){
	$type=!empty($data['fulltext'])?'FULLTEXT':(!empty($data['unique'])?'UNIQUE':'INDEX');
	$columns=isset($data['columns'])?$data['columns']:array();
	return "{$keyname} {$type} (".implode(',',$columns).")";
}
/**
* @describe returns adminGetTableIndexInfo() formatted as an array of definition strings, suitable for diffText()
* @param table string
* @param [info] array - pass a previously fetched adminGetTableIndexInfo()/synchronizeGetTargetIndexes() result to avoid refetching
* @return array
* @usage $defs=adminGetTableIndexDefs('_pages');
*/
function adminGetTableIndexDefs($table,$info=null){
	if(!is_array($info)){$info=adminGetTableIndexInfo($table);}
	$defs=array();
	foreach($info as $keyname=>$data){
		$defs[]=adminFormatIndexDef($keyname,$data);
	}
	return $defs;
}
/**
* @describe reconciles a table's actual indexes to match $desired (adds missing/changed indexes, drops indexes not in $desired) - used on both sides of a sync: the target applies the source's desired indexes, and a revert applies the target's indexes back onto the source
* @param table string
* @param desired array - adminGetTableIndexInfo()-shaped array of the indexes that should exist
* @return array of result strings, one per add/drop performed
* @usage $results=synchronizeApplyTableIndexes('_pages',$desired);
*/
function synchronizeApplyTableIndexes($table,$desired){
	if(!is_array($desired)){$desired=array();}
	$current=adminGetTableIndexInfo($table);
	$results=array();
	//drop indexes that no longer exist (or whose definition changed - readded below)
	foreach($current as $keyname=>$cdata){
		if(!isset($desired[$keyname]) || adminFormatIndexDef($keyname,$desired[$keyname]) != adminFormatIndexDef($keyname,$cdata)){
			$ok=dropDBIndex($keyname,$table);
			$results[]="Dropped index {$keyname} on {$table}: ".printValue($ok);
		}
	}
	//add indexes that are new or changed
	foreach($desired as $keyname=>$ddata){
		if(isset($current[$keyname]) && adminFormatIndexDef($keyname,$current[$keyname])==adminFormatIndexDef($keyname,$ddata)){continue;}
		$params=array(
			'-table'	=> $table,
			'-name'		=> $keyname,
			'-fields'	=> $ddata['columns']
		);
		if(!empty($ddata['unique'])){$params['-unique']=true;}
		if(!empty($ddata['fulltext'])){$params['-fulltext']=true;}
		$ok=addDBIndex($params);
		$results[]="Added index {$keyname} on {$table}: ".printValue($ok);
	}
	return $results;
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
		elseif(isset($ALLCONFIG[$target]['admin_insecure']) && $ALLCONFIG[$target]['admin_insecure']==1){
			$_SESSION['sync_target_url']="http://{$base}/php/admin.php";
		}
		else{
			$_SESSION['sync_target_url']="https://{$base}/php/admin.php";
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
			'_auth'		=> encodeURL($_SESSION['sync_target_auth']),
			'_noguid'	=> 1,
			'-follow'	=> 1,
			'-nossl'	=> 1
		);
	} 
	//echo $plain.$_SESSION['sync_target_url'].printValue($postopts);exit;
	$post=postURL($_SESSION['sync_target_url'],$postopts);
	//echo printValue($load).printValue($postopts).$post['body'];exit;
	if(isset($post['error'])){
		foreach($_SESSION as $k=>$v){
			if(stringBeginsWith($k,'sync')){
				unset($_SESSION[$k]);
			}
		}
		return array('error'=>$_SESSION['sync_target_url'].$post['error']);
	}
	elseif(!strlen($post['body'])){
		foreach($_SESSION as $k=>$v){
			if(stringBeginsWith($k,'sync')){
				unset($_SESSION[$k]);
			}
		}
		return array('error'=>$_SESSION['sync_target_url'].printValue($post));
	}
	else{
		//remove debug errors if they exist
		if(stringBeginsWith($post['body'],'HTTP/') && stringContains($post['body'],'Content-Type:')){
			$parts=preg_split('/\r\n\r\n/',trim($post['body']),2);
			$post['body']=trim($parts[1]);
			if(stringBeginsWith($post['body'],'HTTP/') && stringContains($post['body'],'Content-Type:')){
				$parts=preg_split('/\r\n\r\n/',trim($post['body']),2);
				$post['body']=trim($parts[1]);
			}
		}
		$postBody=$post['body'];
		$post['body_decoded']=base64_decode($post['body']);
		$json=decodeJson($post['body_decoded']);
		if(!is_array($json)){
			$json=decodeJson(base64_decode(trim($post['body'])));
		}
		if(!is_array($json)){
			$post['body']=preg_replace('/\<div(.+?)\<\/div\>/is','',$post['body']);
			$post['body']=preg_replace('/\<img(.+?)\>/is','',$post['body']);
			$json=decodeJson(base64_decode(trim($post['body'])),true);
		}
		//echo $_SESSION['sync_target_url'].printValue($postopts).printValue($json);exit;
		if(!is_array($json)){
			$json=decodeJson(trim($post['body']));
			if(!is_array($json)){
				foreach($_SESSION as $k=>$v){
					if(stringBeginsWith($k,'sync')){
						unset($_SESSION[$k]);
					}
				}
				return array(
					'error'=>"Failed to decode response",
					'post_body'=>"<xmp>{$post['body']}</xmp>",
				);
			}
		}
		return $json;
	}
	foreach($_SESSION as $k=>$v){
		if(stringBeginsWith($k,'sync')){
			unset($_SESSION[$k]);
		}
	}
	return array('error'=>$_SESSION['sync_target_url'].'<br>'.json_encode($post));
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
				//skip virtual fields
				if(stringContains($f['_dbtype_ex'],'generated')){continue;}
				if(stringBeginsWith($field,'_')){continue;}
				//ignore the precision
				$scheck=preg_replace('/\(.+?\)/','',$f['_dbtype_ex']);
				$tcheck=preg_replace('/\(.+?\)/','',$target_recs['_schema_'][$table][$field]);
				if(!isset($target_recs['_schema_'][$table][$field])){
					//field in this table is new
					$changes[]="New Field: {$field} {$f['_dbtype_ex']}";
				}
				elseif($tcheck != $scheck){
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
	//compare indexes
	foreach($tables as $table){
		$changes=array();
		$sindexes=adminGetTableIndexInfo($table);
		$tindexes=isset($target_recs['_indexes_'][$table]) && is_array($target_recs['_indexes_'][$table])?$target_recs['_indexes_'][$table]:array();
		foreach($sindexes as $keyname=>$sdata){
			$sdef=adminFormatIndexDef($keyname,$sdata);
			if(!isset($tindexes[$keyname])){
				//index is new
				$changes[]="New Index: {$sdef}";
			}
			else{
				$tdef=adminFormatIndexDef($keyname,$tindexes[$keyname]);
				if($tdef != $sdef){
					//index has changed
					$changes[]="Changed Index: {$sdef}";
				}
			}
		}
		if(count($changes)){
			if(!is_array($recs['indexes'])){$recs['indexes']=array();}
			$recs['indexes'][]=array(
				'id'=>$table,
				'tablename'=>$table,
				'tabname'=>'indexes',
				'marker'=>'indexes',
				'changes'=>implode('<br />'.PHP_EOL,$changes),
			);
		}
	}
	return $recs;
}
?>
