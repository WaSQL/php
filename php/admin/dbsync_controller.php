<?php
	global $CONFIG;
	global $DATABASE;
	global $USER;
	
	switch(strtolower($_REQUEST['func'])){
		case 'table_push_table':
			echo printValue($_REQUEST);exit;
		break;
		case 'table_push_diff':
			echo printValue($_REQUEST);exit;
		break;
		case 'table_pull_table':
			echo printValue($_REQUEST);exit;
		break;
		case 'table_pull_diff':
			echo printValue($_REQUEST);exit;
		break;
		case 'indexes_push_table':
			echo printValue($_REQUEST);exit;
		break;
		case 'indexes_push_diff':
			echo printValue($_REQUEST);exit;
		break;
		case 'indexes_pull_table':
			echo printValue($_REQUEST);exit;
		break;
		case 'indexes_pull_diff':
			echo printValue($_REQUEST);exit;
		break;
		case 'compare':
			$_SESSION['dbsync']=array();
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$diffs=isNum($_REQUEST['diffs'])?$_REQUEST['diffs']:0;
			setView('compare',1);
			return;
		break;
		case 'view_fields':
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$title="Fields for {$table}";
			setView('view_diff',1);
			if(in_array($_SESSION['dbsync'][$table]['schema'],array('different','new'))){
				setView('sync_fields_button');
			}
			//echo $table.printValue($_SESSION['dbsync'][$table]);exit;
			$diff=dbsyncDiff($_SESSION['dbsync'][$table]['source']['fields'],$_SESSION['dbsync'][$table]['target']['fields']);
			return;
		break;
		case 'view_indexes':
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$title="Indexes for {$table}";
			setView('view_diff',1);
			//echo printValue($_SESSION['dbsync'][$table]);exit;
			if(in_array($_SESSION['dbsync'][$table]['indexes'],array('different','new','missing'))){
				setView('sync_indexes_button');
			}
			$diff=dbsyncDiff($_SESSION['dbsync'][$table]['source']['indexes'],$_SESSION['dbsync'][$table]['target']['indexes']);
			return;
		break;
		case 'view_constraints':
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$title="Constraints for {$table}";
			setView('view_diff',1);
			$diff=dbsyncDiff($_SESSION['dbsync'][$table]['source']['constraints'],$_SESSION['dbsync'][$table]['target']['constraints']);
			return;
		break;
		case 'view_procedure':
			$name=$_REQUEST['name'];
			$type=$_REQUEST['type'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$title="{$_REQUEST['type']} - {$name}";
			$status=$_REQUEST['status'];
			$s=dbGetProcedureText($source,$name,$type);
			$t=dbGetProcedureText($target,$name,$type);
			$diff=diffText($s,$t);
			setView('view_diff',1);
			if($status != 'same'){
				setView('sync_procedure_button');
			}
			return;
		break;
		case 'sync_fields':
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$title="Sync Fields for {$table}";
			$recs=array();
			$recs[]=dbsyncSyncFields($_SESSION['dbsync'][$table]);
			$sync=databaseListRecords(array(
				'-list'=>$recs,
				'-hidesearch'=>1,
				'-tableclass'=>'table bordered striped is-sticky',
				'-tableheight'=>'80vh',
			));
			setView('view_sync',1);
			return;
		break;
		case 'sync_indexes':
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$title="Sync Indexes for {$table}";
			$recs=dbsyncSyncIndexes($_SESSION['dbsync'][$table]);
			$sync=databaseListRecords(array(
				'-list'=>$recs,
				'-hidesearch'=>1,
				'-tableclass'=>'table bordered striped is-sticky',
				'-tableheight'=>'80vh',
			));
			setView('view_sync',1);
			return;
		break;
		case 'sync_procedure':
			$name=$_REQUEST['name'];
			$type=$_REQUEST['type'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$title="Sync {$type} - {$name}";
			//get the DDL from source
			$ddl=dbGetDDL($source,$type,$name);
			$_SESSION['debugValue_lastm']='';
			//compile the DDL on target
			$ok=dbExecuteSQL($target,$ddl);
			if(strlen($_SESSION['debugValue_lastm'])){
				$error=nl2br($_SESSION['debugValue_lastm']);
				$sync="DONE with Errors <br> $error";

			}
			else{
				$sync="DONE";
			}
			setView('view_sync',1);
			return;
		break;

		case 'fields':
			$table=addslashes($_REQUEST['table']);
			$fields=dbGetTableFields($db['name'],$table);
			$indexes=dbGetTableIndexes($db['name'],$table);
			//echo printValue($indexes);exit;
			setView('tabledetails',1);
			return;
		break;
		default:
			
			setView('default',1);
		break;
	}
	setView('default',1);
?>
