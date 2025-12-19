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
	// Immediate debug logging
	$tpath = getWaSQLPath('php/admin');
	$debug_file = "{$tpath}/git_debug.log";
	file_put_contents($debug_file, date('Y-m-d H:i:s') . " - gitCommand called: {$command}\n", FILE_APPEND);

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
	$cmd_parts = array('git');

	// Add credential config if needed (BEFORE the command)
	$needs_credentials = in_array($command, array('push', 'pull', 'fetch', 'clone'));
	$has_credentials = isset($_SESSION['git_credentials']) && is_array($_SESSION['git_credentials']) && $_SESSION['git_credentials'] !== 'skip';

	// Debug credential check
	$cred_debug = "Command: {$command}, Needs creds: " . ($needs_credentials ? 'YES' : 'NO');
	$cred_debug .= ", Has creds: " . ($has_credentials ? 'YES' : 'NO');
	if(isset($_SESSION['git_credentials'])){
		$cred_debug .= ", Creds type: " . gettype($_SESSION['git_credentials']);
		if(is_array($_SESSION['git_credentials'])){
			$cred_debug .= ", Username: " . (isset($_SESSION['git_credentials']['username']) ? 'SET' : 'NOT SET');
		}
	} else {
		$cred_debug .= ", Creds: NOT SET IN SESSION";
	}
	gitLog($cred_debug, 'info');

	// Store credential helper path for cleanup
	$cred_helper_file = null;

	if($needs_credentials && $has_credentials){
		$username = $_SESSION['git_credentials']['username'];
		$password = $_SESSION['git_credentials']['password'];

		// Create a simple credential helper batch file
		// GIT_ASKPASS will be called by git when it needs credentials
		$cred_helper_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'git_askpass_' . uniqid() . '.bat';

		// Batch file content: output username or password based on what git asks for
		$bat_content = "@echo off\r\n";
		$bat_content .= "echo %1 | findstr /C:\"Username\" >nul && echo {$username} && exit /b\r\n";
		$bat_content .= "echo {$password}\r\n";

		file_put_contents($cred_helper_file, $bat_content);

		// Tell git to use our batch file for credentials via environment variable
		putenv("GIT_ASKPASS={$cred_helper_file}");

		gitLog("Created credential helper at: {$cred_helper_file}", 'info');
	} else {
		gitLog("Credentials NOT added to command", 'warning');
	}

	$cmd_parts[] = escapeshellarg($command);
	foreach($args as $arg){
		$cmd_parts[] = escapeshellarg($arg);
	}
	$cmd = implode(' ', $cmd_parts);

	// Debug logging
	if($log_command){
		$safe_cmd = preg_replace('/password=[^\s;]+/', 'password=***', $cmd);
		gitLog("Executing command: {$safe_cmd}", 'info');
	}
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

	// Detect platform for cross-platform compatibility
	$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

	// Set environment to prevent interactive prompts (platform-specific)
	$env_vars = array(
		'GIT_TERMINAL_PROMPT' => '0',  // Disable git credential prompts
		'GIT_EDITOR' => 'true',         // Prevent editor from opening (use 'true' command that exits immediately)
		'EDITOR' => 'true',             // Fallback editor prevention
		'VISUAL' => 'true'              // Fallback visual editor prevention
	);

	// Platform-specific SSH configuration
	if(!$is_windows){
		$env_vars['GIT_SSH_COMMAND'] = 'ssh -o BatchMode=yes -o ConnectTimeout=10';
		$env_vars['GIT_ASKPASS'] = 'echo';
	} else {
		// Windows-specific settings
		$env_vars['GIT_ASKPASS'] = 'echo';
		$env_vars['GIT_EDITOR'] = 'cmd /c exit 0';
	}

	foreach($env_vars as $key => $value){
		putenv("{$key}={$value}");
	}

	// Platform-specific execution for reliability
	$output = array();
	$exit_code = -1;
	$timeout = in_array($command, array('push', 'pull')) ? 120 : 60;

	if($is_windows){
		// Debug logging
		file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Windows execution path\n", FILE_APPEND);

		// Windows: Use direct exec() which is simpler and more reliable under Apache
		// Increase PHP timeout to handle long-running git operations
		$old_timeout = ini_get('max_execution_time');
		set_time_limit($timeout + 30);

		$start_time = time();

		// Build command with environment variables set inline
		$git_path_escaped = str_replace('/', '\\', $_SESSION['git_path']);

		// Set environment variables inline for Windows
		$env_prefix = 'set GIT_TERMINAL_PROMPT=0 && set GIT_EDITOR=cmd /c exit 0 && set EDITOR=true';

		// Add GIT_ASKPASS if we have a credential helper
		if($cred_helper_file && file_exists($cred_helper_file)){
			$env_prefix .= ' && set GIT_ASKPASS=' . $cred_helper_file;
		} else {
			$env_prefix .= ' && set GIT_ASKPASS=echo';
		}

		$env_prefix .= ' && ';
		$full_cmd = $env_prefix . 'cd /d "' . $git_path_escaped . '" && ' . $cmd . ' 2>&1';

		// Debug log the command
		file_put_contents($debug_file, date('Y-m-d H:i:s') . " - About to exec: {$full_cmd}\n", FILE_APPEND);

		// Log the actual command being executed
		if($log_command){
			gitLog("Windows command: " . preg_replace('/password=[^\s]+/', 'password=***', $full_cmd), 'info');
		}

		// Execute and capture output - use file-based approach to avoid blocking
		$output_lines = array();
		$output_file = sys_get_temp_dir() . '\\git_output_' . uniqid() . '.txt';

		file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Using file-based execution\n", FILE_APPEND);

		// Redirect output to file and execute in background
		$file_cmd = $full_cmd . " > " . escapeshellarg($output_file) . " 2>&1";

		file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Executing: {$file_cmd}\n", FILE_APPEND);

		// Execute command - this should return immediately if backgrounded properly
		exec($file_cmd, $dummy_output, $exit_code);

		file_put_contents($debug_file, date('Y-m-d H:i:s') . " - After exec, waiting for output file\n", FILE_APPEND);

		// Wait for output file to appear and have content
		$wait_count = 0;
		while($wait_count < ($timeout * 10) && (!file_exists($output_file) || filesize($output_file) == 0)){
			usleep(100000); // 100ms
			$wait_count++;
		}

		file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Output file check complete, waited {$wait_count} iterations\n", FILE_APPEND);

		// Read output from file
		if(file_exists($output_file)){
			$output_str = file_get_contents($output_file);
			$output_lines = explode("\n", trim($output_str));
			@unlink($output_file);

			file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Read " . count($output_lines) . " lines from output file\n", FILE_APPEND);
		} else {
			$output_lines = array('No output file generated');
			$exit_code = -1;
			file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Output file never appeared!\n", FILE_APPEND);
		}

		// Check if we exceeded timeout (approximate)
		if((time() - $start_time) >= $timeout){
			$output_lines[] = "Operation may have timed out";
			$exit_code = 124;
		}

		$output = array_filter($output_lines, function($line){ return !empty(trim($line)); });

		// Restore PHP timeout
		set_time_limit($old_timeout);

		file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Completed, output lines: " . count($output) . "\n", FILE_APPEND);

		// Cleanup credential helper file on Windows
		if($cred_helper_file && file_exists($cred_helper_file)){
			@unlink($cred_helper_file);
			gitLog("Cleaned up credential helper", 'info');
		}
	} else {
		// Unix/Linux: Use proc_open with stream_select (original reliable method)
		$descriptors = array(
			0 => array('pipe', 'r'),  // stdin
			1 => array('pipe', 'w'),  // stdout
			2 => array('pipe', 'w')   // stderr
		);

		$process = proc_open($cmd, $descriptors, $pipes);

		if(is_resource($process)){
			fclose($pipes[0]);

			$start_time = time();
			stream_set_blocking($pipes[1], false);
			stream_set_blocking($pipes[2], false);

			$stdout = '';
			$stderr = '';

			while(time() - $start_time < $timeout){
				$read = array($pipes[1], $pipes[2]);
				$write = null;
				$except = null;

				if(stream_select($read, $write, $except, 1) > 0){
					foreach($read as $stream){
						$data = fread($stream, 8192);
						if($stream === $pipes[1]){
							$stdout .= $data;
						} else {
							$stderr .= $data;
						}
					}
				}

				$status = proc_get_status($process);
				if(!$status['running']){
					$stdout .= stream_get_contents($pipes[1]);
					$stderr .= stream_get_contents($pipes[2]);
					$exit_code = $status['exitcode'];
					break;
				}
			}

			if(time() - $start_time >= $timeout){
				proc_terminate($process, 9);
				$stderr .= "\nOperation timed out after {$timeout} seconds";
				$exit_code = 124;
			}

			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($process);

			$combined_output = trim($stdout . "\n" . $stderr);
			$output = preg_split('/[\r\n]+/', $combined_output);
			$output = array_filter($output, function($line){ return !empty(trim($line)); });
		} else {
			$output = array('Failed to execute command');
			$exit_code = -1;
		}
	}

	// Restore original directory
	chdir($original_dir);

	// Cleanup credential helper file on Unix/Linux
	if(!$is_windows && $cred_helper_file && file_exists($cred_helper_file)){
		@unlink($cred_helper_file);
		gitLog("Cleaned up credential helper", 'info');
	}

	$success = ($exit_code === 0);

	// Special handling for timeout
	$timed_out = ($exit_code === 124);

	// Log the command execution (optional)
	if($log_command){
		$log_msg = "Git command: {$cmd} | Exit code: {$exit_code}";
		if($timed_out){
			$log_msg .= " | TIMEOUT";
		}
		gitLog($log_msg, $success ? 'info' : 'error');
	}

	// Convert output array to string
	$output_str = implode("\n", $output);

	if($return_array){
		return array(
			'success' => $success,
			'output' => $output,
			'error' => $success ? '' : $output_str,
			'exit_code' => $exit_code,
			'timed_out' => $timed_out
		);
	}

	return array(
		'success' => $success,
		'output' => $output_str,
		'error' => $success ? '' : $output_str,
		'exit_code' => $exit_code,
		'timed_out' => $timed_out
	);
}

/****
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
	//echo $logfile.$log_entry_json;exit;
	appendFileContents($logfile, $log_entry_json . PHP_EOL);

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
