<?php
	//CRITICAL: Access control check - DB Sync is a sensitive operation
	if(!isAdmin()){
		echo '<div class="w_bold w_danger">Access Denied: Admin privileges required</div>';
		exit;
	}

	global $CONFIG;
	global $DATABASE;
	global $USER;

	switch(strtolower($_REQUEST['func'])){
		case 'table_push_table':
			echo '<div class="w_bold w_danger">Function not yet implemented</div>';
			return;
		break;
		case 'table_push_diff':
			echo '<div class="w_bold w_danger">Function not yet implemented</div>';
			return;
		break;
		case 'table_pull_table':
			echo '<div class="w_bold w_danger">Function not yet implemented</div>';
			return;
		break;
		case 'table_pull_diff':
			echo '<div class="w_bold w_danger">Function not yet implemented</div>';
			return;
		break;
		case 'indexes_push_table':
			echo '<div class="w_bold w_danger">Function not yet implemented</div>';
			return;
		break;
		case 'indexes_push_diff':
			echo '<div class="w_bold w_danger">Function not yet implemented</div>';
			return;
		break;
		case 'indexes_pull_table':
			echo '<div class="w_bold w_danger">Function not yet implemented</div>';
			return;
		break;
		case 'indexes_pull_diff':
			echo '<div class="w_bold w_danger">Function not yet implemented</div>';
			return;
		break;
		case 'compare':
			$_SESSION['dbsync']=array();

			//Validate source database
			if(!isset($_REQUEST['source']) || !strlen(trim($_REQUEST['source'])) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Please select a source database</div>';
				return;
			}
			$source=$_REQUEST['source'];

			//Validate target database
			if(!isset($_REQUEST['target']) || !strlen(trim($_REQUEST['target'])) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Please select a target database</div>';
				return;
			}
			$target=$_REQUEST['target'];

			//Validate diffs parameter
			$diffs=isset($_REQUEST['diffs']) && isNum($_REQUEST['diffs'])?(integer)$_REQUEST['diffs']:0;

			//Validate tab/view name
			if(isset($_REQUEST['tab'])){
				$tab=str_replace('-','_',$_REQUEST['tab']); //Convert dashes to underscores
				if(!dbsyncValidateViewName($tab)){
					$tab='compare';
				}
			}
			else{
				$tab='compare';
			}
			setView($tab,1);
			return;
		break;
		case 'view_fields':
			//Validate table name
			if(!isset($_REQUEST['table']) || !dbsyncValidateTableName($_REQUEST['table'])){
				echo '<div class="w_bold w_danger">Invalid table name</div>';
				return;
			}
			$table=$_REQUEST['table'];

			//Validate source and target databases
			if(!isset($_REQUEST['source']) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Invalid source database</div>';
				return;
			}
			if(!isset($_REQUEST['target']) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Invalid target database</div>';
				return;
			}
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];

			//Validate session data exists
			if(!isset($_SESSION['dbsync'][$table])){
				echo '<div class="w_bold w_danger">Session data not found. Please run compare first.</div>';
				return;
			}

			$title=encodeHtml("Fields for {$table}");
			setView('view_diff',1);
			if(in_array($_SESSION['dbsync'][$table]['schema'],array('different','new'))){
				setView('sync_fields_button');
			}
			$diff=dbsyncDiff($_SESSION['dbsync'][$table]['source']['fields'],$_SESSION['dbsync'][$table]['target']['fields']);
			return;
		break;
		case 'view_indexes':
			//Validate inputs (same validation as view_fields)
			if(!isset($_REQUEST['table']) || !dbsyncValidateTableName($_REQUEST['table'])){
				echo '<div class="w_bold w_danger">Invalid table name</div>';
				return;
			}
			if(!isset($_REQUEST['source']) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Invalid source database</div>';
				return;
			}
			if(!isset($_REQUEST['target']) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Invalid target database</div>';
				return;
			}
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];

			if(!isset($_SESSION['dbsync'][$table])){
				echo '<div class="w_bold w_danger">Session data not found. Please run compare first.</div>';
				return;
			}

			$title=encodeHtml("Indexes for {$table}");
			setView('view_diff',1);
			if(in_array($_SESSION['dbsync'][$table]['indexes'],array('different','new','missing'))){
				setView('sync_indexes_button');
			}
			$diff=dbsyncDiff($_SESSION['dbsync'][$table]['source']['indexes'],$_SESSION['dbsync'][$table]['target']['indexes']);
			return;
		break;
		case 'view_constraints':
			//Validate inputs
			if(!isset($_REQUEST['table']) || !dbsyncValidateTableName($_REQUEST['table'])){
				echo '<div class="w_bold w_danger">Invalid table name</div>';
				return;
			}
			if(!isset($_REQUEST['source']) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Invalid source database</div>';
				return;
			}
			if(!isset($_REQUEST['target']) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Invalid target database</div>';
				return;
			}
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];

			if(!isset($_SESSION['dbsync'][$table])){
				echo '<div class="w_bold w_danger">Session data not found. Please run compare first.</div>';
				return;
			}

			$title=encodeHtml("Constraints for {$table}");
			setView('view_diff',1);
			$diff=dbsyncDiff($_SESSION['dbsync'][$table]['source']['constraints'],$_SESSION['dbsync'][$table]['target']['constraints']);
			return;
		break;
		case 'view_procedure':
			//Validate procedure name
			if(!isset($_REQUEST['name']) || !dbsyncValidateProcedureName($_REQUEST['name'])){
				echo '<div class="w_bold w_danger">Invalid procedure name</div>';
				return;
			}
			$name=$_REQUEST['name'];

			//Validate procedure type
			if(!isset($_REQUEST['type']) || !dbsyncValidateProcedureType($_REQUEST['type'])){
				echo '<div class="w_bold w_danger">Invalid procedure type</div>';
				return;
			}
			$type=$_REQUEST['type'];

			//Validate databases
			if(!isset($_REQUEST['source']) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Invalid source database</div>';
				return;
			}
			if(!isset($_REQUEST['target']) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Invalid target database</div>';
				return;
			}
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];

			//Validate status
			$allowedStatus=array('same','new','missing','args','content');
			$status=isset($_REQUEST['status']) && in_array($_REQUEST['status'],$allowedStatus)?$_REQUEST['status']:'different';

			$title=encodeHtml("{$type} - {$name}");
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
			//Validate inputs
			if(!isset($_REQUEST['table']) || !dbsyncValidateTableName($_REQUEST['table'])){
				echo '<div class="w_bold w_danger">Invalid table name</div>';
				return;
			}
			if(!isset($_REQUEST['source']) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Invalid source database</div>';
				return;
			}
			if(!isset($_REQUEST['target']) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Invalid target database</div>';
				return;
			}
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];

			if(!isset($_SESSION['dbsync'][$table])){
				echo '<div class="w_bold w_danger">Session data not found. Please run compare first.</div>';
				return;
			}

			$title=encodeHtml("Sync Fields for {$table}");
			$recs=array();
			$recs[]=dbsyncSyncFields($_SESSION['dbsync'][$table]);
			$sync=databaseListRecords(array(
				'-list'=>$recs,
				'-hidesearch'=>1,
				'-tableclass'=>'wacss_table bordered striped sticky',
				'-tableheight'=>'80vh',
			));
			setView('view_sync',1);
			return;
		break;
		case 'ddl':
			//Validate inputs
			if(!isset($_REQUEST['table']) || !dbsyncValidateTableName($_REQUEST['table'])){
				echo '<div class="w_bold w_danger">Invalid table name</div>';
				return;
			}
			if(!isset($_REQUEST['source']) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Invalid source database</div>';
				return;
			}
			if(!isset($_REQUEST['target']) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Invalid target database</div>';
				return;
			}
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];

			if(!isset($_SESSION['dbsync'][$table])){
				echo '<div class="w_bold w_danger">Session data not found. Please run compare first.</div>';
				return;
			}

			$title=encodeHtml("DDL for {$table}");
			$ddl=dbGetTableDDL($_SESSION['dbsync'][$table]['source']['name'],$table);
			$ddl=trim($ddl);
			setView('ddl',1);
			return;
		break;
		case 'sync_indexes':
			//Validate inputs
			if(!isset($_REQUEST['table']) || !dbsyncValidateTableName($_REQUEST['table'])){
				echo '<div class="w_bold w_danger">Invalid table name</div>';
				return;
			}
			if(!isset($_REQUEST['source']) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Invalid source database</div>';
				return;
			}
			if(!isset($_REQUEST['target']) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Invalid target database</div>';
				return;
			}
			$table=$_REQUEST['table'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];

			if(!isset($_SESSION['dbsync'][$table])){
				echo '<div class="w_bold w_danger">Session data not found. Please run compare first.</div>';
				return;
			}

			$title=encodeHtml("Sync Indexes for {$table}");
			$recs=dbsyncSyncIndexes($_SESSION['dbsync'][$table]);
			$sync=databaseListRecords(array(
				'-list'=>$recs,
				'-hidesearch'=>1,
				'-tableclass'=>'wacss_table bordered striped sticky',
				'-tableheight'=>'80vh',
			));
			setView('view_sync',1);
			return;
		break;
		case 'sync_procedure':
			//Validate inputs (same as view_procedure)
			if(!isset($_REQUEST['name']) || !dbsyncValidateProcedureName($_REQUEST['name'])){
				echo '<div class="w_bold w_danger">Invalid procedure name</div>';
				return;
			}
			if(!isset($_REQUEST['type']) || !dbsyncValidateProcedureType($_REQUEST['type'])){
				echo '<div class="w_bold w_danger">Invalid procedure type</div>';
				return;
			}
			if(!isset($_REQUEST['source']) || !dbsyncValidateDatabaseName($_REQUEST['source'])){
				echo '<div class="w_bold w_danger">Invalid source database</div>';
				return;
			}
			if(!isset($_REQUEST['target']) || !dbsyncValidateDatabaseName($_REQUEST['target'])){
				echo '<div class="w_bold w_danger">Invalid target database</div>';
				return;
			}
			$name=$_REQUEST['name'];
			$type=$_REQUEST['type'];
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];

			$title=encodeHtml("Sync {$type} - {$name}");
			$ddl=dbGetProcedureText($source,$name,$type);
			if(is_array($ddl)){
				$ddl=implode(PHP_EOL,$ddl);
			}
			$_SESSION['debugValue_lastm']='';
			$ok=dbExecuteSQL($target,$ddl);
			if(strlen($_SESSION['debugValue_lastm'])){
				$error=nl2br(encodeHtml($_SESSION['debugValue_lastm']));
				$sync="DONE with Errors <br> $error";
			}
			else{
				$sync="DONE";
			}
			setView('view_sync',1);
			return;
		break;

		case 'fields':
			//Validate table name
			if(!isset($_REQUEST['table']) || !dbsyncValidateTableName($_REQUEST['table'])){
				echo '<div class="w_bold w_danger">Invalid table name</div>';
				return;
			}
			$table=$_REQUEST['table'];

			//Note: $db variable needs to be defined somewhere for this to work
			if(!isset($db['name'])){
				echo '<div class="w_bold w_danger">Database not specified</div>';
				return;
			}
			$fields=dbGetTableFields($db['name'],$table);
			$indexes=dbGetTableIndexes($db['name'],$table);
			setView('tabledetails',1);
			return;
		break;
		default:
			
			setView('default',1);
		break;
	}
	setView('default',1);
?>
