<?php
/**
 * Git Management Functions for WaSQL - API Version
 *
 * Production-ready git repository management using Git hosting APIs (GitHub, GitLab, Bitbucket).
 * This version uses direct API calls instead of local git commands for a cloud-first workflow.
 *
 * This module provides a secure web interface for managing git repositories through
 * remote APIs, allowing file operations without requiring a local git installation.
 *
 * Key Features:
 * - Direct API integration with GitHub, GitLab, and Bitbucket
 * - File synchronization between local and remote repositories
 * - Secure API authentication via tokens
 * - Comprehensive error handling and user feedback
 * - Audit logging for compliance and security monitoring
 *
 * Security Measures:
 * - Path traversal blocking (../, absolute paths, null bytes)
 * - Admin-only access with authentication checks
 * - API tokens stored securely in CONFIG
 * - All operations logged with username and IP address
 * - Input validation for all user-supplied data
 *
 * Functions provided:
 * - gitFileInfo()              - Get list of modified files comparing local with remote
 * - gitValidateFilePath()      - Validate file paths for security
 * - gitGetPath()               - Get git repository path from settings
 * - gitLog()                   - Log operations for audit trail
 * - gitValidateCommitMessage() - Validate commit message quality
 * - gitApiCall()               - Wrapper for API calls with error handling
 *
 * @package    WaSQL
 * @subpackage GitManagement
 * @version    3.0 - API Edition
 * @author     WaSQL Development Team
 * @copyright  Copyright (c) 2024 WaSQL
 * @license    See WaSQL license
 *
 * @see git_controller.php For request handling and business logic
 * @see git_body.htm For user interface views
 * @see gitapi.php For API functions
 */

// Include the Git API library
$progpath = dirname(__FILE__);
require_once("{$progpath}/../extras/gitapi.php");

/**
 * Execute local git status command
 *
 * Executes 'git status -s' locally to see what files have changed.
 * This is the one exception where we use local git instead of API.
 *
 * @return array Result with 'success', 'output', 'error' keys
 *
 * @since 3.0
 */
function gitLocalStatus(){
	$original_dir = getcwd();

	if(!isset($_SESSION['git_path']) || !chdir($_SESSION['git_path'])){
		return array(
			'success' => false,
			'output' => '',
			'error' => 'Invalid git path'
		);
	}

	$output = array();
	$exit_code = 0;
	exec('git status -s 2>&1', $output, $exit_code);

	chdir($original_dir);

	return array(
		'success' => $exit_code === 0,
		'output' => implode("\n", $output),
		'error' => $exit_code !== 0 ? implode("\n", $output) : ''
	);
}

/**
 * Retrieves file information using local git status
 *
 * Uses local 'git status' to see what files have changed, then prepares
 * them for upload via API.
 *
 * @global array $git Array to store git status information including:
 *                     - 'files': Array of file information
 *                     - 'b64sha': Mapping of base64 file names to SHA hashes
 *                     - 'error': Error message if operation fails
 *
 * @return void
 *
 * @since 3.0
 */
function gitFileInfo(){
	global $git, $CONFIG;

	if(!isset($_SESSION['git_path']) || !is_dir($_SESSION['git_path'])){
		$git['error'] = 'Invalid git path';
		$git['files'] = array();
		return;
	}

	$git['files'] = array();
	$git['b64sha'] = array();

	// Use local git status to see what changed
	$result = gitLocalStatus();

	if(!$result['success']){
		$git['error'] = $result['error'];
		$git['files'] = array();
		return;
	}

	$git['status'] = $result['output'];
	$lines = preg_split('/[\r\n]+/', trim($git['status']));

	// Cache realpath of git directory for performance
	$git_realpath = realpath($_SESSION['git_path']);
	if($git_realpath === false){
		$git['error'] = 'Invalid git path';
		$git['files'] = array();
		return;
	}

	$current_time = time();

	foreach($lines as $line){
		$line = trim($line);
		if(empty($line) || preg_match('/^git status/i', $line)){
			continue;
		}

		$x = substr($line, 0, 1);
		$line = preg_replace('/^.{2,2}/', '', $line);
		$parts = preg_split('/\s+/', ltrim($line));

		switch(strtoupper($x)){
			case '#':
				$git['branch'] = $parts[0];
				$status = 'skip';
			break;
			case ' ': $status = 'unmodified'; break;
			case 'M': $status = 'modified'; break;
			case 'A': $status = 'added'; break;
			case 'D': $status = 'deleted'; break;
			case 'R': $status = 'renamed'; break;
			case 'C': $status = 'copied'; break;
			case 'U': $status = 'updated but unmerged'; break;
			case '?': $status = 'new'; break;
			default: $status = 'skip'; break;
		}

		if($status == 'skip'){
			continue;
		}

		$file = $parts[0];

		// Validate file path to prevent directory traversal
		if(!gitValidateFilePath($file)){
			continue;
		}

		$afile = $git_realpath . DIRECTORY_SEPARATOR . $file;
		$afile_real = realpath($afile);

		// Additional security check - ensure file is within git path
		if($afile_real === false || strpos($afile_real, $git_realpath) !== 0){
			continue;
		}

		$rec = array(
			'name' => $file,
			'afile' => $afile_real,
			'status' => $status,
			'sha' => sha1($afile_real),
			'b64' => encodeBase64($file)
		);

		$git['b64sha'][$rec['b64']] = $rec['sha'];

		if(is_file($afile_real)){
			$age = $current_time - filemtime($afile_real);
			$rec['age'] = $age;
			$rec['age_verbose'] = verboseTime($age);

			// Only count lines for small files
			$filesize = filesize($afile_real);
			if($filesize > 0 && $filesize < 1048576){ // Skip line count for files > 1MB
				$rec['lines'] = getFileLineCount($afile_real);
			} else {
				$rec['lines'] = '~';
			}
		}

		$git['files'][] = $rec;
	}

	// Track files in session for later operations
	$_SESSION['git_tracked_files'] = $git['files'];
}

/**
 * Validates that a file path doesn't contain directory traversal attempts
 *
 * Security checks performed:
 * - Blocks directory traversal attempts (..)
 * - Blocks absolute paths (C:/ or /)
 * - Blocks null byte injection
 *
 * @param string $path File path to validate (relative path expected)
 *
 * @return bool True if path is safe, false if dangerous
 *
 * @since 3.0
 */
function gitValidateFilePath($path){
	// Block directory traversal attempts
	if(strpos($path, '..') !== false){
		return false;
	}

	// Block absolute paths
	if(preg_match('/^[a-z]:/i', $path) || substr($path, 0, 1) === '/'){
		return false;
	}

	// Block null bytes
	if(strpos($path, "\0") !== false){
		return false;
	}

	return true;
}

/**
 * Retrieves git configuration from database settings
 *
 * Checks the _settings table for git configuration and validates settings.
 *
 * @return string Repository path or error code ('not_enabled', 'invalid_path')
 *
 * @since 3.0
 */
function gitGetPath(){
	$recs = getDBRecords(array(
		'-table' => '_settings',
		'-where' => "user_id=0 and key_name like 'wasql_git%'",
		'-index' => 'key_name',
		'-fields' => '_id,key_name,key_value'
	));

	if(!isset($recs['wasql_git']['key_value']) || $recs['wasql_git']['key_value'] != 1){
		return 'not_enabled';
	}

	if(!isset($recs['wasql_git_path']['key_value']) || !is_dir($recs['wasql_git_path']['key_value'])){
		return 'invalid_path';
	}

	$path = realpath($recs['wasql_git_path']['key_value']);
	if($path === false){
		return 'invalid_path';
	}

	return $path;
}

/**
 * Wrapper function for Git API calls with error handling and logging
 *
 * Provides a consistent interface for making API calls with automatic
 * error handling, logging, and response formatting.
 *
 * @param string $function API function name (e.g., 'gitapiPush', 'gitapiCommit')
 * @param array $params Parameters to pass to the function
 * @param bool $log_call Whether to log this call (default: true)
 *
 * @return array Result array with 'success', 'data', 'error' keys
 *
 * @since 3.0
 */
function gitApiCall($function, $params = array(), $log_call = true){
	if(!function_exists($function)){
		$error = "API function not found: {$function}";
		if($log_call){
			gitLog($error, 'error');
		}
		return array(
			'success' => false,
			'error' => $error,
			'data' => null
		);
	}

	if($log_call){
		gitLog("Calling API function: {$function}", 'info');
	}

	$result = call_user_func($function, $params);

	if(isset($result['success']) && !$result['success']){
		if($log_call){
			$error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
			gitLog("API call failed: {$function} - {$error_msg}", 'error');
		}
	}

	return $result;
}

/**
 * Logs git operations for audit trail and security monitoring
 *
 * Creates audit log entries and logs critical errors to PHP error log.
 *
 * @global array $USER Current user information array
 *
 * @param string $message Log message describing the operation or event
 * @param string $level   Log level: 'info', 'warning', or 'error' (default: 'info')
 *
 * @return void
 *
 * @since 3.0
 */
function gitLog($message, $level = 'info'){
	global $USER;

	$username = isset($USER['username']) ? $USER['username'] : 'unknown';

	// Always log to file
	$tpath = getWaSQLPath('php/admin');
	$logfile = "{$tpath}/git.log";
	$log_entry = array(
		'timestamp' => date('Y-m-d H:i:s'),
		'unixtime' => time(),
		'username' => $username,
		'message' => $message,
		'level' => $level,
		'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI'
	);
	$log_entry_json = encodeJSON($log_entry);
	appendFileContents($logfile, $log_entry_json . PHP_EOL);

	// Also log to PHP error log for critical errors
	if($level === 'error'){
		error_log("WaSQL Git API [{$username}]: {$message}");
	}
}

/**
 * Validates commit message meets minimum requirements
 *
 * Enforces commit message standards to ensure meaningful commit history.
 *
 * @param string $message Commit message to validate
 *
 * @return bool True if message meets requirements, false otherwise
 *
 * @since 3.0
 */
function gitValidateCommitMessage($message){
	$message = trim($message);

	// Minimum length requirement
	if(strlen($message) < 3){
		return false;
	}

	// Maximum length check
	if(strlen($message) > 500){
		return false;
	}

	return true;
}

/**
 * Get file content from local file system
 *
 * Reads a file from the local git directory with security checks.
 *
 * @param string $filePath Relative file path
 *
 * @return array Result with 'success', 'content', 'error' keys
 *
 * @since 3.0
 */
function gitGetLocalFile($filePath){
	global $git;

	if(!gitValidateFilePath($filePath)){
		return array(
			'success' => false,
			'error' => 'Invalid file path',
			'content' => null
		);
	}

	$local_path = realpath($_SESSION['git_path']);
	$afile = $local_path . DIRECTORY_SEPARATOR . $filePath;
	$afile_real = realpath($afile);

	// Security check
	if($afile_real === false || strpos($afile_real, $local_path) !== 0){
		return array(
			'success' => false,
			'error' => 'File path security violation',
			'content' => null
		);
	}

	if(!file_exists($afile_real)){
		return array(
			'success' => false,
			'error' => 'File not found',
			'content' => null
		);
	}

	$content = file_get_contents($afile_real);
	if($content === false){
		return array(
			'success' => false,
			'error' => 'Failed to read file',
			'content' => null
		);
	}

	return array(
		'success' => true,
		'content' => $content,
		'error' => null
	);
}

/**
 * Get file SHA from remote repository
 *
 * Required for GitHub API file updates. Retrieves the current file's SHA from remote.
 *
 * @param string $filePath Relative file path
 * @param array $params Optional API parameters
 *
 * @return string|null File SHA or null if not found
 *
 * @since 3.0
 */
function gitGetRemoteFileSha($filePath, $params = array()){
	$result = gitapiGetFile($filePath, $params);

	if($result['success'] && isset($result['data']['sha'])){
		return $result['data']['sha'];
	}

	return null;
}

/**
 * Smart diff algorithm using Longest Common Subsequence (LCS)
 *
 * This function implements a proper diff algorithm that intelligently detects
 * which lines were added, deleted, or remain unchanged. Unlike simple line-by-line
 * comparison, this algorithm understands when lines have been moved or reordered.
 *
 * The algorithm works by:
 * 1. Finding the longest common subsequence of lines between old and new files
 * 2. Using the LCS to identify which lines are unchanged (in common)
 * 3. Marking lines not in the LCS as added or deleted
 *
 * This produces a more readable diff that groups related changes together
 * and doesn't show spurious additions/deletions when lines are merely reordered.
 *
 * @param array $old_lines Array of lines from old version
 * @param array $new_lines Array of lines from new version
 *
 * @return array Array of diff items, each with:
 *   - type: 'same', 'delete', or 'insert'
 *   - line: The line content
 *   - old_num: Line number in old file (for same/delete)
 *   - new_num: Line number in new file (for same/insert)
 *
 * @example
 * $diff = gitSmartDiff($remote_lines, $local_lines);
 * foreach($diff as $item){
 *     if($item['type'] == 'delete'){
 *         echo "- " . $item['line'];
 *     } elseif($item['type'] == 'insert'){
 *         echo "+ " . $item['line'];
 *     } else {
 *         echo "  " . $item['line'];
 *     }
 * }
 *
 * @since 3.0
 */
function gitSmartDiff($old_lines, $new_lines){
	$old_count = count($old_lines);
	$new_count = count($new_lines);

	// Compute LCS lengths using dynamic programming
	$lcs = array();
	for($i = 0; $i <= $old_count; $i++){
		$lcs[$i] = array_fill(0, $new_count + 1, 0);
	}

	for($i = 1; $i <= $old_count; $i++){
		for($j = 1; $j <= $new_count; $j++){
			if($old_lines[$i - 1] === $new_lines[$j - 1]){
				$lcs[$i][$j] = $lcs[$i - 1][$j - 1] + 1;
			} else {
				$lcs[$i][$j] = max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
			}
		}
	}

	// Backtrack to build the diff
	$diff = array();
	$i = $old_count;
	$j = $new_count;

	while($i > 0 || $j > 0){
		if($i > 0 && $j > 0 && $old_lines[$i - 1] === $new_lines[$j - 1]){
			// Lines are the same
			array_unshift($diff, array(
				'type' => 'same',
				'line' => $old_lines[$i - 1],
				'old_num' => $i,
				'new_num' => $j
			));
			$i--;
			$j--;
		} elseif($j > 0 && ($i == 0 || $lcs[$i][$j - 1] >= $lcs[$i - 1][$j])){
			// Line was inserted in new version
			array_unshift($diff, array(
				'type' => 'insert',
				'line' => $new_lines[$j - 1],
				'old_num' => null,
				'new_num' => $j
			));
			$j--;
		} elseif($i > 0 && ($j == 0 || $lcs[$i][$j - 1] < $lcs[$i - 1][$j])){
			// Line was deleted from old version
			array_unshift($diff, array(
				'type' => 'delete',
				'line' => $old_lines[$i - 1],
				'old_num' => $i,
				'new_num' => null
			));
			$i--;
		}
	}

	return $diff;
}

?>
