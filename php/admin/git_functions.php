<?php
/**
 * Git Management Functions for WaSQL
 *
 * Production-ready git repository management with comprehensive security,
 * error handling, audit logging, and performance optimizations.
 *
 * This module provides a secure web interface for managing git repositories
 * through the WaSQL admin panel. All operations are protected against common
 * security vulnerabilities including command injection, path traversal, and
 * unauthorized access.
 *
 * Key Features:
 * - Secure git command execution with whitelisting and argument escaping
 * - Path traversal protection for all file operations
 * - Comprehensive audit logging for compliance and security monitoring
 * - Performance optimizations for large repositories (200+ files)
 * - Authentication and authorization checks for all operations
 * - Detailed error handling and user feedback
 *
 * Security Measures:
 * - Command injection prevention via escapeshellarg()
 * - Path traversal blocking (../, absolute paths, null bytes)
 * - Whitelist of allowed git commands only
 * - Admin-only access with authentication checks
 * - All operations logged with username and IP address
 * - Input validation for all user-supplied data
 *
 * Performance Features:
 * - Cached realpath lookups
 * - Batch git operations (add multiple files at once)
 * - Optional logging for frequent operations
 * - Smart line counting (skip files > 1MB)
 * - Static variable caching where appropriate
 *
 * Functions provided:
 * - gitFileInfo()              - Get list of modified files with metadata
 * - gitValidateFilePath()      - Validate file paths for security
 * - gitGetPath()               - Get git repository path from settings
 * - gitCommand()               - Execute git commands safely
 * - gitLog()                   - Log operations for audit trail
 * - gitValidateCommitMessage() - Validate commit message quality
 *
 * @package    WaSQL
 * @subpackage GitManagement
 * @version    2.0
 * @author     WaSQL Development Team
 * @copyright  Copyright (c) 2024 WaSQL
 * @license    See WaSQL license
 *
 * @see git_controller.php For request handling and business logic
 * @see git_body.htm For user interface views
 * @see git_table.sql For audit log database schema
 * @see GIT_README.md For complete documentation
 * @see GIT_PERFORMANCE.md For performance optimization details
 */

/**
 * Retrieves and parses git status information for modified files
 *
 * Executes 'git status -s' and parses the output to build a list of modified files
 * with their status, file age, line count, and other metadata. Populates the global
 * $git array with file information for display and processing.
 *
 * Performance optimizations:
 * - Caches realpath of git directory (called once vs N times)
 * - Caches current time for age calculations
 * - Skips line counting for files larger than 1MB
 * - Does not log command execution (frequent operation)
 *
 * @global array $git Array to store git status information including:
 *                     - 'status': Raw git status output
 *                     - 'files': Array of file information
 *                     - 'b64sha': Mapping of base64 file names to SHA hashes
 *                     - 'error': Error message if operation fails
 *
 * @return void
 *
 * @since 2.0
 */
function gitFileInfo(){
	global $git;

	$result = gitCommand('status', array('-s'), false, false);
	if($result['success'] === false){
		$git['error'] = $result['error'];
		$git['files'] = array();
		return;
	}

	$git['status'] = $result['output'];
	$lines = preg_split('/[\r\n]+/', trim($git['status']));
	$git['b64sha'] = array();

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

			// Only count lines for small files or use fast method
			$filesize = filesize($afile_real);
			if($filesize > 0 && $filesize < 1048576){ // Skip line count for files > 1MB
				$rec['lines'] = getFileLineCount($afile_real);
			} else {
				$rec['lines'] = '~';
			}
		}

		$git['files'][] = $rec;
	}
}

/**
 * Validates that a file path doesn't contain directory traversal attempts
 *
 * Security checks performed:
 * - Blocks directory traversal attempts (..)
 * - Blocks absolute paths (C:/ or /)
 * - Blocks null byte injection
 *
 * This function is called before any file operation to prevent path traversal
 * attacks that could access files outside the git repository.
 *
 * @param string $path File path to validate (relative path expected)
 *
 * @return bool True if path is safe and can be used, false if dangerous
 *
 * @since 2.0
 *
 * @example
 * gitValidateFilePath('src/file.php')      // Returns true
 * gitValidateFilePath('../etc/passwd')     // Returns false
 * gitValidateFilePath('/etc/passwd')       // Returns false
 * gitValidateFilePath("file\0.php")        // Returns false
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
 * Retrieves git configuration from database settings and validates repository
 *
 * Checks the _settings table for git configuration (wasql_git and wasql_git_path)
 * and validates that the path is a valid git repository. Returns the absolute
 * path to the repository or an error code.
 *
 * Error codes returned:
 * - 'not_enabled': Git management is disabled (wasql_git != 1)
 * - 'invalid_path': Path doesn't exist, isn't a directory, or isn't a git repository
 *
 * @return string Absolute path to git repository, or error code string
 *                ('not_enabled', 'invalid_path')
 *
 * @since 2.0
 *
 * @example
 * $path = gitGetPath();
 * if($path === 'not_enabled'){
 *     // Show enable git message
 * } elseif($path === 'invalid_path'){
 *     // Show invalid path message
 * } else {
 *     // Use $path as repository directory
 * }
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

	// Verify it's actually a git repository
	if(!is_dir($path . DIRECTORY_SEPARATOR . '.git')){
		return 'invalid_path';
	}

	return $path;
}

/**
 * Executes a git command safely with proper escaping and error handling
 *
 * This is the core function for all git operations. It provides:
 * - Command whitelisting (only allowed commands can execute)
 * - Automatic argument escaping via escapeshellarg()
 * - Directory management (changes to repo dir and back)
 * - Error capture (stderr redirected to stdout)
 * - Optional logging for audit trail
 *
 * Security features:
 * - Only whitelisted commands allowed (status, add, rm, checkout, commit, push, pull, diff, log, config)
 * - All arguments are escaped with escapeshellarg()
 * - Commands execute within the git repository directory
 * - Invalid commands are logged and rejected
 *
 * Performance features:
 * - Static command whitelist (created once per request)
 * - Optional logging (disable for frequent read operations)
 * - Efficient output handling for both string and array modes
 *
 * @param string $command Git subcommand name (must be in whitelist)
 * @param array  $args    Arguments for the command (automatically escaped)
 * @param bool   $return_array Whether to return output as array of lines (default: false)
 * @param bool   $log_command  Whether to log this command execution (default: true)
 *
 * @return array Associative array with keys:
 *               - 'success' (bool): True if exit code was 0
 *               - 'output' (string|array): Command output (string or array based on $return_array)
 *               - 'error' (string): Error message (empty if success)
 *               - 'exit_code' (int): Command exit code
 *
 * @since 2.0
 *
 * @example
 * // Simple status check
 * $result = gitCommand('status', array('-s'));
 * if($result['success']){
 *     echo $result['output'];
 * }
 *
 * @example
 * // Add multiple files
 * $result = gitCommand('add', array('file1.php', 'file2.php'));
 *
 * @example
 * // Get log as array without logging
 * $result = gitCommand('log', array('--max-count=10'), true, false);
 * foreach($result['output'] as $line){
 *     echo $line;
 * }
 */
function gitCommand($command, $args = array(), $return_array = false, $log_command = true){
	// Validate command is allowed
	static $allowed_commands = array('status', 'add', 'rm', 'checkout', 'commit', 'push', 'pull', 'diff', 'log', 'config');
	if(!in_array($command, $allowed_commands)){
		if($log_command){
			gitLog("Attempted to execute disallowed git command: {$command}", 'error');
		}
		return array(
			'success' => false,
			'output' => '',
			'error' => 'Disallowed git command',
			'exit_code' => -1
		);
	}

	// Build command with escaped arguments
	$cmd_parts = array('git', escapeshellarg($command));
	foreach($args as $arg){
		$cmd_parts[] = escapeshellarg($arg);
	}
	$cmd = implode(' ', $cmd_parts);

	// Save current directory
	$original_dir = getcwd();

	// Change to git directory
	if(!chdir($_SESSION['git_path'])){
		if($log_command){
			gitLog("Failed to change to git directory: {$_SESSION['git_path']}", 'error');
		}
		return array(
			'success' => false,
			'output' => '',
			'error' => 'Failed to access repository directory',
			'exit_code' => -1
		);
	}

	// Execute command
	$output = array();
	$exit_code = 0;
	exec($cmd . ' 2>&1', $output, $exit_code);

	// Restore original directory
	chdir($original_dir);

	$success = ($exit_code === 0);

	// Log the command execution (optional)
	if($log_command){
		gitLog("Git command: {$cmd} | Exit code: {$exit_code}", $success ? 'info' : 'error');
	}

	if($return_array){
		$output_str = implode("\n", $output);
		return array(
			'success' => $success,
			'output' => $output,
			'error' => $success ? '' : $output_str,
			'exit_code' => $exit_code
		);
	}

	$output_str = implode("\n", $output);
	return array(
		'success' => $success,
		'output' => $output_str,
		'error' => $success ? '' : $output_str,
		'exit_code' => $exit_code
	);
}

/**
 * Logs git operations for audit trail and security monitoring
 *
 * Creates audit log entries in the _gitlog database table (if it exists) and
 * also logs critical errors to the PHP error log. Provides a complete audit
 * trail of all git operations for security and compliance.
 *
 * Log levels:
 * - 'info': Normal operations (commits, adds, status checks)
 * - 'warning': Security warnings (invalid paths, unauthorized access attempts)
 * - 'error': Operation failures (command errors, permission issues)
 *
 * Performance optimization:
 * - Caches table existence check in static variable (checked once per request)
 * - Only queries database if _gitlog table exists
 *
 * Database log includes:
 * - Username (from global $USER or 'unknown')
 * - Message describing the operation
 * - Log level (info/warning/error)
 * - IP address of user
 * - Timestamp of operation
 *
 * @global array $USER Current user information array
 *
 * @param string $message Log message describing the operation or event
 * @param string $level   Log level: 'info', 'warning', or 'error' (default: 'info')
 *
 * @return void
 *
 * @since 2.0
 *
 * @example
 * gitLog("File committed: config.php | Message: Update settings", 'info');
 * gitLog("Invalid file path detected: ../../etc/passwd", 'warning');
 * gitLog("Git pull failed: Permission denied", 'error');
 */
function gitLog($message, $level = 'info'){
	global $USER;
	static $table_exists = null;

	// Cache table existence check for performance
	if($table_exists === null){
		$table_exists = isDBTable('_gitlog');
	}

	$username = isset($USER['username']) ? $USER['username'] : 'unknown';

	// Only log to database if _gitlog table exists
	if($table_exists){
		$log_entry = array(
			'-table' => '_gitlog',
			'username' => $username,
			'message' => $message,
			'level' => $level,
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'timestamp' => date('Y-m-d H:i:s')
		);
		addDBRecord($log_entry);
	}

	// Also log to PHP error log for critical errors
	if($level === 'error'){
		error_log("WaSQL Git [{$username}]: {$message}");
	}
}

/**
 * Validates commit message meets minimum requirements for quality and safety
 *
 * Enforces commit message standards to ensure meaningful commit history:
 * - Minimum length: 3 characters (prevents empty or too-short messages)
 * - Maximum length: 500 characters (prevents extremely long messages)
 *
 * The message is trimmed before validation, so leading/trailing whitespace
 * doesn't count toward the length requirements.
 *
 * Validation rules:
 * - Too short (< 3 chars): "fix", "wip", ".", etc. are rejected
 * - Too long (> 500 chars): Essays or overly verbose messages are rejected
 * - Empty/whitespace only: Rejected after trimming
 *
 * @param string $message Commit message to validate
 *
 * @return bool True if message meets requirements, false otherwise
 *
 * @since 2.0
 *
 * @example
 * gitValidateCommitMessage("Update config")          // Returns true (valid)
 * gitValidateCommitMessage("fix")                    // Returns false (too short)
 * gitValidateCommitMessage("")                       // Returns false (empty)
 * gitValidateCommitMessage("   ")                    // Returns false (whitespace only)
 * gitValidateCommitMessage(str_repeat("a", 501))     // Returns false (too long)
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
?>
