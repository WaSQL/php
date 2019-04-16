<?php
	/*
	 * the current database is the master. Get the slave - the database to sync with
	 * */
	global $CONFIG;
	if(!isset($_SESSION['sync_target'])){
		$_SESSION['sync_target']=syncronizeGetTarget();
	}
	if(!strlen($_SESSION['sync_target'])){
		//not setup for syncronize
		$error="Not setup for syncronize. No target specified.";
		setView('error',1);
		return;
	}
	if(isset($_REQUEST['sync_target_user']) && isset($_REQUEST['sync_target_pass'])){
		 $user=addslashes($_REQUEST['sync_target_user']);
		 $pass=addslashes($_REQUEST['sync_target_pass']);
		 //echo printValue(array($user,$pass));exit;
		 $json=synchronizeGetAuth($user,$pass);
		 //echo printValue($json['error']);exit;
		 if(isset($json['auth']) && strlen($json['auth'])){
			$_SESSION['sync_target_auth']=$json['auth'];
		}
		else{
			$synctables=adminGetSynchronizeTables();
			$_SESSION['sync_target']=syncronizeGetTarget();
			unset($_SESSION['sync_target_auth']);
			unset($_SESSION['sync_target_url']);
			unset($json['body']);
			setView('sync_auth',1);
			return;
		}
	}
	if(!isset($_SESSION['sync_target_auth'])){
		//check the log table once
		checkDBTableSchema('_synchronize');
		$synctables=adminGetSynchronizeTables();
		$_SESSION['sync_target']=syncronizeGetTarget();
		unset($_SESSION['sync_target_auth']);
		unset($_SESSION['sync_target_url']);
		setView('sync_auth',1);
		return;
	}
	global $setactive;
	switch(strtolower($_REQUEST['func'])){
		case 'unauth':
			$_SESSION['sync_target']=syncronizeGetTarget();
			unset($_SESSION['sync_target_auth']);
			unset($_SESSION['sync_target_url']);
			$synctables=adminGetSynchronizeTables();
			setView('sync_auth',1);
			return;
		break;
		case 'table':
			$table=addslashes($_REQUEST['table']);
			setView('switch',1);
			return;
		break;
		case 'diff_details':
			$field=addslashes($_REQUEST['field']);
			setView('switch_field',1);
			return;
		break;
		case 'diff':
			$diffs=array();
			$id=addslashes($_REQUEST['id']);
			$table=addslashes($_REQUEST['table']);
			$marker=addslashes($_REQUEST['marker']);
			//push to target
			if($table=='schema'){
				$title="{$marker} - {$id}";
				$target_fields=synchronizeGetTargetSchema($id);
				$source_fields=array();
				$fields=getDBFieldInfo($id);
				foreach($fields as $field=>$info){
					if(isWasqlField($field)){continue;}
					$source_fields[]="{$field} {$info['_dbtype_ex']}";
				}
				$diff = diffText($source_fields,$target_fields, $id,'',300);
				if(!strlen($diff) || preg_match('/No differences found/i',$diff)){
					$diff=array(
						'source'=>$source_fields,
						'target'=>$target_fields
					);
					setView('sync_diffs_none',1);
					return;
				}
				$diffs[$id]=$diff;
				setView('sync_diffs',1);
				return;
			}
			$fields=adminGetSynchronizeFields($table);
			$target_rec=synchronizeGetTargetRecord($table,$id,$fields);
			if(isset($target_rec['error'])){
				$error=$target_rec['error'];
				setView('error',1);
				return;
			}
			//get local record
			$source_rec=getDBRecord(array('-table'=>$table,'_id'=>$id,'-fields'=>$fields));
			//compare them

			foreach($source_rec as $field=>$val){
				if(isWasqlField($field) || preg_match('/\_utime$/i',$field)){continue;}
				$arr_source=preg_split('/[\r\n]+/', trim($val));
				$arr_target=preg_split('/[\r\n]+/', trim($target_rec[$field]));
				$diff = diffText($arr_source,$arr_target, $field,'',300);
				if(!strlen($diff)){continue;}
				if(preg_match('/No differences found/i',$diff)){continue;}
				$diffs[$field]=$diff;
			}
			$title="{$table} - {$marker}";
			if(!count($diffs)){
				setView('sync_diffs_none',1);
				return;
			}
			//echo printValue($diffs);exit;
			//echo $diffs['controller'];exit;
			setView('sync_diffs',1);
			return;
		break;
		case 'revert':
			if(!isset($_REQUEST['id'])){
				$error="None selected";
				setView('error',1);
				return;
			}
			if(!is_array($_REQUEST['id'])){
				$_REQUEST['id']=array($_REQUEST['id']);
			}
			$ids=$_REQUEST['id'];
			$table=addslashes($_REQUEST['table']);
			setView('revert_verify',1);
			return;
		break;
		case 'revert_verified':
			if(!isset($_REQUEST['id']) || !isset($_REQUEST['table'])){
				$error="Missing params".printValue($_REQUEST);
				setView('error',1);
				return;
			}
			if(!is_array($_REQUEST['id'])){
				$_REQUEST['id']=array($_REQUEST['id']);
			}
			$ids=$_REQUEST['id'];
			$table=addslashes($_REQUEST['table']);
			//push to target
			if($table=='schema'){
				$schemas=array();
				$results=array("Reverting {$table} records");
				foreach($ids as $id){
					$fields=synchronizeGetTargetSchema($id);
					$new=isDBTable($id)?0:1;
					$ok=updateDBSchema($id,$fields,$new);
					$results[]="Table {$id} schema reverted";
				}
			}
			else{
				$results=array("Reverting {$table} records");
				$fields=adminGetSynchronizeFields($table);
				foreach($ids as $id){
					$target_rec=synchronizeGetTargetRecord($table,$id,$fields);
					$target_rec['-table']=$table;
					$target_rec['-where']="_id={$id}";
					$results[]=editDBRecord($target_rec);
				}
			}
			$notes="reverted local changes";
			//add log record
			$x=addDBRecord(array(
				'-table'	=> '_synchronize',
				'tablename'	=> $table,
				'ids'		=> json_encode($ids),
				'target'	=> 'none',
				'notes'		=> $notes,
				'results'	=> json_encode($results)
			));
			setView('sync_verified',1);
			return;
		break;
		case 'sync':
			if(!isset($_REQUEST['id'])){
				$error="None selected";
				setView('error',1);
				return;
			}
			if(!is_array($_REQUEST['id'])){
				$_REQUEST['id']=array($_REQUEST['id']);
			}
			$ids=$_REQUEST['id'];
			$table=addslashes($_REQUEST['table']);
			setView('sync_verify',1);
			return;
		break;
		case 'sync_verified':
			if(!isset($_REQUEST['id']) || !isset($_REQUEST['table']) || !isset($_REQUEST['notes'])){
				$error="Missing params".printValue($_REQUEST);
				setView('error',1);
				return;
			}
			if(!is_array($_REQUEST['id'])){
				$_REQUEST['id']=array($_REQUEST['id']);
			}
			$ids=$_REQUEST['id'];
			$table=addslashes($_REQUEST['table']);
			$_SESSION['sync_notes']=$_REQUEST['notes'];
			$notes=addslashes($_REQUEST['notes']);
			//push to target
			if($table=='schema'){
				$schemas=array();
				foreach($ids as $id){
					$fields=getDBFieldInfo($id);
					foreach($fields as $field=>$info){
						if(isWasqlField($field)){continue;}
						$schemas[$id][]="{$field} {$info['_dbtype_ex']}";
					}
				}
				$results=synchronizeUpdateTargetSchemas($schemas);
			}
			else{
				$idstr=implode(',',$ids);
				$recs=getDBRecords(array('-table'=>$table,'-where'=>"_id in ({$idstr})",'-index'=>'_id'));
				$results=synchronizeUpdateTargetRecords($table,$recs);
			}
			//echo $results;exit;
			//add log record
			$x=addDBRecord(array(
				'-table'	=> '_synchronize',
				'tablename'	=> $table,
				'ids'		=> json_encode($ids),
				'target'	=> $_SESSION['sync_target'],
				'notes'		=> $notes,
				'results'	=> json_encode($results)
			));
			setView('sync_verified',1);
			return;
		break;
		case 'edit':
			$table=addslashes($_REQUEST['table']);
			$id=addslashes($_REQUEST['id']);
			setView('edit_record');
			return;
		break;
		default:
			global $target;
			global $setactive;
			$synctables=adminGetSynchronizeTables();

			$changes=synchronizeGetChanges($synctables);
			//echo printValue($changes);exit;
			if(isset($changes['error'])){
				$error=$changes['error'];
				setView('error',1);
				return;
			}
			setView('default',1);
		break;
	}
?>
