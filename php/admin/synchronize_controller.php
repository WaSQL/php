<?php
	/*
	 * the current database is the master. Get the slave - the database to sync with
	 * */
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
		 $json=synchronizeGetAuth($user,$pass);
		 //echo printValue($json);exit;
		 if(isset($json['auth'])){
			$_SESSION['sync_target_auth']=$json['auth'];
		}
		else{
			setView('sync_auth',1);
			return;
		}
	}
	if(!isset($_SESSION['sync_target_auth'])){
		//check the log table once
		checkDBTableSchema('_synchronize');
		setView('sync_auth',1);
		return;
	}
	global $setactive;
	switch(strtolower($_REQUEST['func'])){
		case 'unauth':
			unset($_SESSION['sync_target_auth']);
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
			$id=addslashes($_REQUEST['id']);
			$table=addslashes($_REQUEST['table']);
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
			$diffs=array();
			foreach($source_rec as $field=>$val){
				if(isWasqlField($field) || preg_match('/\_utime$/i',$field)){continue;}
				$arr_source=preg_split('/[\r\n]+/', trim($val));
				$arr_target=preg_split('/[\r\n]+/', trim($target_rec[$field]));
				$diff = diffText($arr_source,$arr_target, $field,'',300);
				if(!strlen($diff)){continue;}
				if(preg_match('/No differences found/i',$diff)){continue;}
				$diffs[$field]=$diff;
			}
			if(!count($diffs)){
				$error="No differences found";
				setView('error',1);
				return;
			}
			//echo $diffs['controller'];exit;
			setView('sync_diffs',1);
			return;
			echo printValue($diffs);exit;
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
			if(isset($changes['error'])){
				$error=$changes['error'];
				setView('error',1);
				return;
			}
			setView('default',1);
		break;
	}
?>
