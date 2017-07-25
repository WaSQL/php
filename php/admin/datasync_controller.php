<?php
	/*
	 * the current database is the master. Get the slave - the database to sync with
	 * */
	if(!isset($_SESSION['sync_target'])){
		$_SESSION['sync_target']=datasyncGetTarget();
	}
	if(!strlen($_SESSION['sync_target'])){
		//not setup for datasync
		$error="Not setup for datasync. No target specified.";
		setView('error',1);
		return;
	}
	if(isset($_REQUEST['sync_target_user']) && isset($_REQUEST['sync_target_pass'])){
		 $user=addslashes($_REQUEST['sync_target_user']);
		 $pass=addslashes($_REQUEST['sync_target_pass']);
		 $json=datasyncGetAuth($user,$pass);
		 if(isset($json['auth'])){
			$_SESSION['sync_target_auth']=$json['auth'];
		}
		else{
			$_SESSION['sync_target']=datasyncGetTarget();
			unset($_SESSION['sync_target_auth']);
			unset($_SESSION['sync_target_url']);
			setView('sync_auth',1);
			return;
		}
	}
	if(!isset($_SESSION['sync_target_auth'])){
		$_SESSION['sync_target']=datasyncGetTarget();
		unset($_SESSION['sync_target_auth']);
		unset($_SESSION['sync_target_url']);
		$json=array();
		setView('sync_auth',1);
		return;
	}
	switch(strtolower($_REQUEST['func'])){
		case 'unauth':
			$_SESSION['sync_target']=datasyncGetTarget();
			unset($_SESSION['sync_target_auth']);
			unset($_SESSION['sync_target_url']);
			setView('sync_auth',1);
			return;
		break;
		case 'sync_to_target':
		case 'sync_from_target':
			$func=strtolower($_REQUEST['func']);
			$title=ucwords(str_replace('_',' ',$func)).' Confirmation';
			$table=addslashes($_REQUEST['table']);
			setView('datasync_verify',1);
			return;
		break;
		case 'sync_to_target_verified':
			$table=addslashes($_REQUEST['table']);
			$results=datasyncToTarget($table);
			setView('results',1);
			//add log record
			$x=addDBRecord(array(
				'-table'	=> '_synchronize',
				'tablename'	=> $table,
				'ids'		=> '["datasync"]',
				'target'	=> $_SESSION['sync_target'],
				'notes'		=> 'datasyncToTarget',
				'results'	=> json_encode($results)
			));
			return;
		break;
		case 'sync_from_target_verified':
			$table=addslashes($_REQUEST['table']);
			$results=datasyncFromTarget($table);
			setView('results',1);
			//add log record
			$x=addDBRecord(array(
				'-table'	=> '_synchronize',
				'tablename'	=> $table,
				'ids'		=> '["datasync"]',
				'target'	=> $_SESSION['sync_target'],
				'notes'		=> 'datasyncFromTarget',
				'results'	=> json_encode($results)
			));
			return;
		break;
		default:
			$target_tables=datasyncGetTargetTables();
			$source_tables=datasyncGetSourceTables();
			$recs=array();
			foreach($source_tables as $table=>$rec){
				$recs[$table]=array(
					'name'=>$table,
					'source_records'=>$rec['records'],
					'source_fields'=>count($rec['fields']),
					'internal'=>isWasqlField($table)?1:0
				);
			}
			foreach($target_tables as $table=>$rec){
				if(!isset($recs[$table]['name'])){
					$recs[$table]['name']=$table;
					$recs[$table]['internal']=isWasqlField($table)?1:0;
				}
				$recs[$table]['target_records']=$rec['records'];
				$recs[$table]['target_fields']=count($rec['fields']);
			}
			foreach($recs as $table=>$rec){
				//are field counts different?
				if(!isset($recs[$table]['target_fields'])){
					$recs[$table]['error']="NOT on target";
				}
				elseif(!isset($recs[$table]['source_fields'])){
					$recs[$table]['error']="ONLY on target";
				}
				elseif($recs[$table]['target_fields'] != $recs[$table]['source_fields']){
					$recs[$table]['error']="Field count mismatch";
				}
				else{
					foreach($source_tables[$table]['fields'] as $field=>$type){
						if(sha1($type) != sha1($target_tables[$table]['fields'][$field])){
							$recs[$table]['error']="Field '{$field}' type mismatch";
						}
					}
				}
			}
			$recs=sortArrayByKeys($recs, array('internal'=>SORT_ASC,'name'=>SORT_ASC));
			setView('default',1);
		break;
	}
?>
