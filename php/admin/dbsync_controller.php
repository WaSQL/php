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
			$title="Fields for {$table}";
			//echo $table.printValue($_SESSION['dbsync'][$table]);exit;
			$diff=dbsyncDiff($_SESSION['dbsync'][$table]['source']['fields'],$_SESSION['dbsync'][$table]['target']['fields']);
			setView('view_diff',1);
			return;
		break;
		case 'view_indexes':
			$table=$_REQUEST['table'];
			$title="Indexes for {$table}";
			$diff=dbsyncDiff($_SESSION['dbsync'][$table]['source']['indexes'],$_SESSION['dbsync'][$table]['target']['indexes']);
			setView('view_diff',1);
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
