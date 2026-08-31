<?php
/**
 * Backups Controller for WaSQL Admin.
 *
 * Routes actions for backup creation, database restoration, file renaming,
 * downloading, and batch deletion.
 */

if(!isAdmin()){
	setView('no_access', 1);
	return;
}

global $CONFIG, $DATABASE, $USER;

$func = isset($_REQUEST['func']) ? strtolower($_REQUEST['func']) : '';

switch($func){
	case 'backup':
	case 'backup now':
		$table = isset($_REQUEST['_table_']) ? trim($_REQUEST['_table_']) : '';
		$dump = backupsCreateBackup($table);

		if(isset($_REQUEST['push']) && $_REQUEST['push'] == 'filename'){
			if(is_file($dump['afile']) && filesize($dump['afile'])){
				echo '<backup>' . base64_encode($dump['afile']) . '</backup>';
			}
			else{
				$err = isset($dump['error']) ? $dump['error'] : 'Unknown error during backup';
				echo '<backup>ERROR:' . $err . '</backup>';
			}
			exit;
		}

		if(isset($dump['success'])){
			$targetDesc = strlen($table) ? "table '{$table}'" : "database '{$CONFIG['dbname']}'";
			$_SESSION['backups_status'] = array(
				'type' => 'success',
				'title' => 'Backup Successful',
				'message' => "Successfully created backup for {$targetDesc}: <b>" . encodeHtml($dump['file']) . "</b> (" . verboseSize(filesize($dump['afile'])) . ")",
				'command' => isset($dump['command']) ? $dump['command'] : ''
			);
		}
		else{
			$_SESSION['backups_status'] = array(
				'type' => 'danger',
				'title' => 'Backup Failed',
				'error' => isset($dump['error']) ? $dump['error'] : 'Command execution failed',
				'command' => isset($dump['command']) ? $dump['command'] : '',
				'result' => isset($dump['result']) ? printValue($dump['result']) : ''
			);
		}
	break;

	case 'restore':
		$file = isset($_REQUEST['file']) ? decodeBase64($_REQUEST['file']) : (isset($_REQUEST['filename']) ? $_REQUEST['filename'] : '');
		$res = backupsRestoreBackup($file);
		if(isset($res['success']) && $res['success']){
			if(isset($res['table']) && strlen($res['table'])){
				$restoreMsg = "Table <b>" . encodeHtml($res['table']) . "</b> restored successfully from backup <b>" . encodeHtml($res['file']) . "</b>. Other tables were not modified.";
			}
			else{
				$restoreMsg = "Database <b>" . encodeHtml($CONFIG['dbname']) . "</b> restored successfully from backup <b>" . encodeHtml($res['file']) . "</b>.";
			}
			$_SESSION['backups_status'] = array(
				'type' => 'success',
				'title' => 'Restore Successful',
				'message' => $restoreMsg
			);
		}
		else{
			$_SESSION['backups_status'] = array(
				'type' => 'danger',
				'title' => 'Restore Failed',
				'error' => isset($res['error']) ? $res['error'] : 'Restore command failed.',
				'command' => isset($res['command']) ? $res['command'] : '',
				'result' => isset($res['result']) ? printValue($res['result']) : ''
			);
		}
	break;

	case 'rename':
		$file = isset($_REQUEST['file']) ? decodeBase64($_REQUEST['file']) : '';
		$newname = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
		$res = backupsRenameBackup($file, $newname);
		if(isset($res['success']) && $res['success']){
			$msg = isset($res['message']) ? $res['message'] : "Backup successfully renamed to <b>" . encodeHtml($res['new_name']) . "</b>.";
			$_SESSION['backups_status'] = array(
				'type' => 'success',
				'title' => 'File Renamed',
				'message' => $msg
			);
		}
		else{
			$_SESSION['backups_status'] = array(
				'type' => 'danger',
				'title' => 'Rename Failed',
				'error' => isset($res['error']) ? $res['error'] : 'Could not rename backup file.'
			);
		}
	break;

	case 'download':
		$file = isset($_REQUEST['file']) ? decodeBase64($_REQUEST['file']) : (isset($_REQUEST['filename']) ? base64_decode($_REQUEST['filename']) : '');
		$real = backupsValidateFilePath($file);
		if($real && is_file($real)){
			pushFile($real);
			exit;
		}
		else{
			$_SESSION['backups_status'] = array(
				'type' => 'danger',
				'title' => 'Download Failed',
				'error' => 'Backup file was not found or access is restricted.'
			);
		}
	break;

	case 'delete':
		$names = isset($_REQUEST['name']) && is_array($_REQUEST['name']) ? $_REQUEST['name'] : array();
		$cnt = backupsDeleteBackups($names);
		if($cnt > 0){
			$_SESSION['backups_status'] = array(
				'type' => 'success',
				'title' => 'Backups Deleted',
				'message' => "Successfully deleted {$cnt} backup file(s)."
			);
		}
		else{
			$_SESSION['backups_status'] = array(
				'type' => 'warning',
				'title' => 'No Files Deleted',
				'message' => 'No valid backup files were selected to delete.'
			);
		}
	break;

	case 'list':
		$files = backupsGetFiles();
		setView('list', 1);
		return;
	break;

	default:
	break;
}

$backupdir = backupsGetDir();
$files = backupsGetFiles();
$stats = backupsGetStats($files);
$selected_table = isset($_REQUEST['_table_']) ? $_REQUEST['_table_'] : '';
$table_options = backupsGetTableOptions($selected_table);
$backup_command = backupsGetBackupCommand($selected_table);
$status = isset($_SESSION['backups_status']) ? $_SESSION['backups_status'] : null;
unset($_SESSION['backups_status']);

setView('default', 1);
?>
