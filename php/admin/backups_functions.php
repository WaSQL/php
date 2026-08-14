<?php
/**
 * Backups functions for WaSQL Admin.
 *
 * Provides database backup creation, restoration, list management,
 * file renaming, downloads, and batch deletions.
 */

/**
 * Get the backup storage directory path and ensure it exists.
 *
 * @return string Absolute directory path to sh/backups.
 */
function backupsGetDir(){
	$path = getWasqlPath('sh/backups');
	if(!is_dir($path)){
		buildDir($path);
	}
	return $path;
}

/**
 * Validate that a filename contains only safe characters and no directory traversal.
 *
 * @param string $filename Filename to validate.
 * @return bool True if valid, false otherwise.
 */
function backupsValidateFileName($filename){
	if(!strlen($filename)){return false;}
	if(stringContains($filename,'/') || stringContains($filename,'\\') || stringContains($filename,'..')){
		return false;
	}
	return preg_match('/^[a-z0-9_\-\.]+$/i', $filename) ? true : false;
}

/**
 * Validate that a file path exists and is located within the backup directory.
 *
 * @param string $file Absolute or relative file path, or decoded filename.
 * @return string|false Canonical real path on success, false on failure.
 */
function backupsValidateFilePath($file){
	if(!strlen($file)){return false;}
	$backupdir = backupsGetDir();
	$realDir = realpath($backupdir);
	if(!$realDir){return false;}

	// Normalize separators and strip directory traversal components
	$file = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $file);
	if(!stringContains($file, DIRECTORY_SEPARATOR)){
		$file = $realDir . DIRECTORY_SEPARATOR . $file;
	}

	$realPath = realpath($file);
	if($realPath && strpos($realPath, $realDir) === 0 && is_file($realPath)){
		return $realPath;
	}
	return false;
}

/**
 * Get the list of existing database backup files.
 *
 * @param array $params Optional filters and parameters.
 * @return array List of file record arrays sorted newest first.
 */
function backupsGetFiles($params = array()){
	global $CONFIG;
	$backupdir = backupsGetDir();
	$dbname = isset($params['dbname']) ? $params['dbname'] : $CONFIG['dbname'];
	$prefix = isset($params['prefix']) ? $params['prefix'] : "{$dbname}__";

	$files = listFilesEx($backupdir, array('name' => $prefix));
	if(!is_array($files) || !count($files)){
		return array();
	}

	$list = array();
	$filecnt = count($files);
	for($x = 0; $x < $filecnt; $x++){
		$rec = $files[$x];
		if(strlen($prefix) && !stringBeginsWith($rec['name'], $prefix)){
			continue;
		}

		$encodedFile = encodeBase64($rec['afile']);
		$downloadUrl = "/php/admin.php?_pushfile={$encodedFile}";
		$restoreUrl = "/php/admin.php?_menu=backups&func=restore&file={$encodedFile}";
		$renameUrl = "/php/admin.php?_menu=backups&func=rename&file={$encodedFile}";

		$actions = array();
		$actions[] = '<a class="w_link w_block" style="padding:0 3px 0 3px" href="' . $downloadUrl . '" data-tooltip="Click to Download" data-tooltip_position="bottom"><span class="icon-download w_big"></span></a>';
		$actions[] = '<a class="w_link w_block" style="padding:0 3px 0 3px" href="' . $restoreUrl . '" onclick="return confirm(\'This will restore the entire database back to this point.\\r\\n\\r\\nARE YOU ABSOLUTELY SURE? If so, click OK.\');" data-tooltip="Restore Database" data-tooltip_position="bottom"><span class="icon-undo w_danger w_big"></span></a>';
		$actions[] = '<a class="w_link w_block" style="padding:0 3px 0 3px" href="' . $renameUrl . '" onclick="return backupsRename(this);" data-tooltip="Rename Backup File" data-tooltip_position="bottom"><span class="icon-rename w_grey w_big"></span></a>';

		$rec['action'] = implode(' ', $actions);
		$list[] = $rec;
	}

	// Sort newest first
	$list = sortArrayByKey($list, '_cdate_age', SORT_ASC);
	return $list;
}

/**
 * Calculate summary statistics for existing backups.
 *
 * @param array|null $files Optional pre-fetched file list.
 * @return array Associative array with count, total_size, total_size_verbose, newest_date.
 */
function backupsGetStats($files = null){
	if($files === null){
		$files = backupsGetFiles();
	}
	$totalBytes = 0;
	$newestDate = '-';
	$count = count($files);

	if($count > 0){
		foreach($files as $file){
			if(isset($file['size'])){
				$totalBytes += (int)$file['size'];
			}
		}
		$newestDate = isset($files[0]['_cdate']) ? $files[0]['_cdate'] : '-';
	}

	return array(
		'count' => $count,
		'total_size' => $totalBytes,
		'total_size_verbose' => verboseSize($totalBytes),
		'newest_date' => $newestDate
	);
}

/**
 * Render the HTML grid of backup files using databaseListRecords.
 *
 * @param array|null $files Optional list of file records.
 * @return string HTML table grid.
 */
function backupsListBackups($files = null){
	if($files === null){
		$files = backupsGetFiles();
	}

	if(!is_array($files) || !count($files)){
		return '<div class="w_gray" style="padding:20px 10px;text-align:center;"><span class="icon-info w_big"></span> No backup files found for this database.</div>';
	}

	return databaseListRecords(array(
		'-list' => $files,
		'-listfields' => 'name,action,size_verbose,_cdate,_cdate_age_verbose',
		'-tableclass' => 'wacss_table is-bordered is-striped is-mobile-responsive',
		'name_displayname' => 'Filename',
		'action_displayname' => 'Actions',
		'action_align' => 'center',
		'size_verbose_displayname' => 'Size',
		'size_verbose_align' => 'right',
		'_cdate_displayname' => 'Date Created',
		'_cdate_align' => 'center',
		'_cdate_age_verbose_displayname' => 'Age',
		'_cdate_age_verbose_align' => 'right',
		'name_checkbox' => 1
	));
}

/**
 * Create a new database or table backup dump.
 *
 * @param string $table Optional table name to backup; defaults to all tables.
 * @return array Dump result array.
 */
function backupsCreateBackup($table = ''){
	$dump = dumpDB($table);
	return $dump;
}

/**
 * Restore database from a backup SQL or GZ file.
 *
 * @param string $file File path or base64 string pointing to backup file.
 * @return array Result array with success, command, error, and result.
 */
function backupsRestoreBackup($file){
	global $CONFIG;
	$realFile = backupsValidateFilePath($file);
	if(!$realFile){
		return array(
			'success' => false,
			'error' => 'Invalid backup file path or file does not exist.'
		);
	}

	// If gzipped, decompress
	if(preg_match('/\.gz$/i', $realFile)){
		// Inspect magic bytes (\x1f\x8b) to check if the file is genuinely gzip-compressed
		$fp = @fopen($realFile, 'rb');
		$magic = $fp ? fread($fp, 2) : '';
		if($fp){ fclose($fp); }
		$isRealGzip = ($magic === "\x1f\x8b");

		$decompressed = preg_replace('/\.gz$/i', '', $realFile);
		if($isRealGzip){
			if(function_exists('gzopen') && (!is_file($decompressed) || filemtime($realFile) > filemtime($decompressed))){
				$gz = @gzopen($realFile, 'rb');
				$dest = @fopen($decompressed, 'wb');
				if($gz && $dest){
					while(!gzeof($gz)){
						fwrite($dest, gzread($gz, 524288));
					}
					gzclose($gz);
					fclose($dest);
				}
			}
			if(is_file($decompressed) && filesize($decompressed) > 0){
				$realFile = $decompressed;
			}
			else{
				$cmd = isWindows() ? "gzip -d -k \"{$realFile}\"" : "gunzip -k \"{$realFile}\"";
				$ok = cmdResults($cmd);
				if(is_file($decompressed)){
					$realFile = $decompressed;
				}
			}
		}
		else{
			// File has .gz extension but actually contains uncompressed plain SQL text
			if(!is_file($decompressed) || filemtime($realFile) > filemtime($decompressed)){
				copy($realFile, $decompressed);
			}
			if(is_file($decompressed)){
				$realFile = $decompressed;
			}
		}
	}

	if(!is_file($realFile) || !preg_match('/\.sql$/i', $realFile)){
		return array(
			'success' => false,
			'error' => 'Decompressed file is not a valid .sql backup.'
		);
	}

	$mysqlCmd = isWindows() ? 'mysql.exe' : 'mysql';
	$host = isset($CONFIG['dbhost']) ? $CONFIG['dbhost'] : 'localhost';
	$user = isset($CONFIG['dbuser']) ? $CONFIG['dbuser'] : 'root';
	$pass = isset($CONFIG['dbpass']) ? $CONFIG['dbpass'] : '';
	$dbname = isset($CONFIG['dbname']) ? $CONFIG['dbname'] : '';
	$port = isset($CONFIG['dbport']) && isNum($CONFIG['dbport']) ? " -P {$CONFIG['dbport']}" : '';

	$passArg = '';
	if(strlen($pass)){
		$passArg = stringContains($pass, '$') ? " -p'{$pass}'" : " -p\"{$pass}\"";
	}

	$cmds = array(
		"{$mysqlCmd} -h {$host}{$port} -u {$user}{$passArg} --execute=\"DROP DATABASE {$dbname}; CREATE DATABASE {$dbname} CHARACTER SET utf8 COLLATE utf8_general_ci;\"",
		"{$mysqlCmd} -h {$host}{$port} -u {$user}{$passArg} --max_allowed_packet=128M --default-character-set=utf8 {$dbname} < \"{$realFile}\""
	);

	$results = array();
	foreach($cmds as $cmd){
		$ok = cmdResults($cmd);
		$results[] = $ok;
		if(isset($ok['rtncode']) && $ok['rtncode'] != 0){
			$errMsg = isset($ok['stderr']) && strlen($ok['stderr']) ? $ok['stderr'] : (isset($ok['stdout']) ? $ok['stdout'] : 'Execution failed');
			return array(
				'success' => false,
				'command' => $cmd,
				'error' => $errMsg,
				'result' => $ok
			);
		}
	}

	return array(
		'success' => true,
		'file' => getFileName($realFile),
		'results' => $results
	);
}

/**
 * Rename an existing backup file.
 *
 * @param string $file File path to rename.
 * @param string $newname New name for the backup.
 * @return array Result array with success status, old and new filenames.
 */
function backupsRenameBackup($file, $newname){
	global $CONFIG;
	$realFile = backupsValidateFilePath($file);
	if(!$realFile){
		return array('success' => false, 'error' => 'Source file not found or invalid.');
	}

	$newname = trim($newname);
	$newname = str_replace(' ', '_', $newname);
	$newname = preg_replace('/[^a-z0-9_\-\.]/i', '', $newname);
	$newname = preg_replace('/_+/', '_', $newname);
	$newname = preg_replace('/\.sql(\.gz)?$/i', '', $newname);
	$newname = preg_replace('/\.gz$/i', '', $newname);
	$newname = trim($newname, '._-');

	if(!strlen($newname)){
		return array('success' => false, 'error' => 'New filename cannot be empty.');
	}

	$prefix = "{$CONFIG['dbname']}__";
	if(!stringBeginsWith($newname, $prefix)){
		$newname = $prefix . $newname;
	}

	$filename = getFileName($realFile);
	$ext = '.sql';
	if(preg_match('/\.sql\.gz$/i', $filename)){
		$ext = '.sql.gz';
	}
	elseif(preg_match('/\.gz$/i', $filename)){
		$ext = '.gz';
	}

	$targetFile = dirname($realFile) . DIRECTORY_SEPARATOR . "{$newname}{$ext}";
	if($targetFile == $realFile){
		return array('success' => true, 'message' => 'Filename unchanged.');
	}

	if(file_exists($targetFile)){
		return array('success' => false, 'error' => 'A backup file with that name already exists.');
	}

	if(!rename($realFile, $targetFile)){
		return array('success' => false, 'error' => 'Failed to rename backup file.');
	}

	return array(
		'success' => true,
		'old_name' => $filename,
		'new_name' => "{$newname}{$ext}"
	);
}

/**
 * Delete one or more backup files.
 *
 * @param array $names List of filenames to delete.
 * @return int Number of successfully deleted files.
 */
function backupsDeleteBackups($names){
	global $CONFIG;
	if(!is_array($names) || !count($names)){
		return 0;
	}

	$backupdir = backupsGetDir();
	$deleted = 0;
	$prefix = "{$CONFIG['dbname']}__";

	foreach($names as $name){
		if(!backupsValidateFileName($name)){
			continue;
		}
		if(!stringBeginsWith($name, $prefix)){
			continue;
		}
		$target = $backupdir . DIRECTORY_SEPARATOR . $name;
		$real = backupsValidateFilePath($target);
		if($real && is_file($real)){
			if(unlink($real)){
				$deleted++;
			}
		}
	}

	return $deleted;
}

/**
 * Build HTML option tags for database table selector.
 *
 * @param string $selected Currently selected table name.
 * @return string HTML option elements.
 */
function backupsGetTableOptions($selected = ''){
	$tables = getDBTables();
	$rtn = '<option value="">All Tables (Full Database)</option>' . PHP_EOL;
	if(is_array($tables)){
		foreach($tables as $table){
			$sel = ($selected == $table) ? ' selected' : '';
			$rtn .= '<option value="' . encodeHtml($table) . '"' . $sel . '>' . encodeHtml($table) . '</option>' . PHP_EOL;
		}
	}
	return $rtn;
}

/**
 * Build the CLI command string used to run database/table backups manually.
 *
 * @param string $table Optional table name to backup.
 * @param string $targetFile Optional explicit output destination filepath.
 * @return string Full shell command string.
 */
function backupsGetBackupCommand($table = '', $targetFile = ''){
	global $CONFIG;
	$backupdir = backupsGetDir();
	$table = trim($table);
	$dateStr = date("Y-m-d_H-i-s");

	if(!strlen($targetFile)){
		$ext = '.sql.gz';
		if(isWindows()){
			$ext = (isset($CONFIG['gzip']) && $CONFIG['gzip'] == 1) ? '.sql.gz' : '.sql';
		}
		if(strlen($table)){
			$targetFile = "{$backupdir}" . DIRECTORY_SEPARATOR . "{$CONFIG['dbname']}.{$table}_{$dateStr}{$ext}";
		}
		else{
			$targetFile = "{$backupdir}" . DIRECTORY_SEPARATOR . "{$CONFIG['dbname']}__{$dateStr}{$ext}";
		}
	}

	$version = getDBRecord("SHOW VARIABLES LIKE 'version'");
	$v1 = $v2 = $v3 = 0;
	if(isset($version['value'])){
		$parts = preg_split('/\./', $version['value']);
		$v1 = isset($parts[0]) ? (int)$parts[0] : 0;
		$v2 = isset($parts[1]) ? (int)$parts[1] : 0;
		$v3 = isset($parts[2]) ? (int)$parts[2] : 0;
	}

	$cmd = '';
	if(isset($CONFIG['backup_command'])){
		$cmd = $CONFIG['backup_command'];
		if($v1 >= 8 && ($v2 > 0 || $v3 >= 32)){
			$cmd .= " --single-transaction=TRUE";
		}
		$cmd .= " -h {$CONFIG['dbhost']}";
		if(strlen($CONFIG['dbuser'])){
			$cmd .= " -u {$CONFIG['dbuser']}";
		}
		if(strlen($CONFIG['dbpass'])){
			if(stringContains($CONFIG['dbpass'], '$')){
				$cmd .= " -p'{$CONFIG['dbpass']}'";
			}
			else{
				$cmd .= " -p\"{$CONFIG['dbpass']}\"";
			}
		}
		if($v1 >= 8){
			$cmd .= " --set-gtid-purged=OFF --column-statistics=0";
		}
		$cmd .= " --max_allowed_packet=128M {$CONFIG['dbname']}";
		if(strlen($table)){
			$cmd .= " {$table}";
		}
	}
	elseif(isMysql() || isMysqli()){
		$cmd = isWindows() ? 'mysqldump.exe' : 'mysqldump';
		if($v1 >= 8 && ($v2 > 0 || $v3 >= 32)){
			$cmd .= " --single-transaction=TRUE";
		}
		$cmd .= " -h {$CONFIG['dbhost']}";
		if(strlen($CONFIG['dbuser'])){
			$cmd .= " -u {$CONFIG['dbuser']}";
		}
		if(strlen($CONFIG['dbpass'])){
			if(stringContains($CONFIG['dbpass'], '$')){
				$cmd .= " -p'{$CONFIG['dbpass']}'";
			}
			else{
				$cmd .= " -p\"{$CONFIG['dbpass']}\"";
			}
		}
		if($v1 >= 8){
			$cmd .= " --set-gtid-purged=OFF --column-statistics=0";
		}
		$cmd .= " --max_allowed_packet=128M {$CONFIG['dbname']}";
		if(strlen($table)){
			$cmd .= " {$table}";
		}
	}
	elseif(isPostgreSQL()){
		$cmd = isWindows() ? 'pg_dump.exe' : 'pg_dump';
		if(strlen($CONFIG['dbpass']) && strlen($CONFIG['dbuser'])){
			$cmd .= " \"--dbname=postgresql://{$CONFIG['dbuser']}:{$CONFIG['dbpass']}@{$CONFIG['dbhost']}:5432/{$CONFIG['dbname']}\"";
		}
		else{
			$cmd .= " -h {$CONFIG['dbhost']} -Fp -c";
		}
		if(strlen($table)){
			$cmd .= " -t {$table}";
		}
	}

	$portable = ((isMysql() || isMysqli()) && isset($CONFIG['backup_portable']) && $CONFIG['backup_portable'] == 1);
	if(isWindows()){
		$gzip = (isset($CONFIG['gzip']) && $CONFIG['gzip'] == 1);
		if($gzip){
			$cmd .= " | gzip -9 > \"{$targetFile}\"";
		}
		else{
			$cmd .= " > \"{$targetFile}\"";
		}
	}
	else{
		$gzip = (isset($CONFIG['gzip']) && $CONFIG['gzip'] == 0) ? false : true;
		if($portable && function_exists('dumpDBPortableFilter')){
			$cmd .= ' | ' . dumpDBPortableFilter();
		}
		if($gzip){
			$cmd .= ' | gzip -9';
		}
		$cmd .= " > \"{$targetFile}\"";
	}

	return $cmd;
}
?>
