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
		case 'view_procedure':
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$title="{$_REQUEST['type']} - {$_REQUEST['name']}";
			$s=dbGetProcedureText($source,$_REQUEST['name'],$_REQUEST['type']);
			$t=dbGetProcedureText($target,$_REQUEST['name'],$_REQUEST['type']);
			$diff=diffText($s,$t);
			setView('view_diff',1);
			if(in_array($_SESSION['dbsync'][$table]['indexes'],array('different','new','missing'))){
				setView('sync_indexes_button');
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
