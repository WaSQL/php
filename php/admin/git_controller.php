<?php
/**
 * Git Controller for WaSQL - API Version
 *
 * Main request handler and business logic controller for git repository operations
 * using Git hosting APIs (GitHub, GitLab, Bitbucket).
 *
 * This version replaces local git commands with direct API calls for a cloud-first
 * workflow that doesn't require a local git installation.
 *
 * Request Flow:
 * 1. Authentication check (requires logged-in  user)
 * 2. Git path initialization and validation
 * 3. Route request based on 'func' parameter
 * 4. Execute API operation with validation and error handling
 * 5. Set appropriate view for response
 *
 * Supported Operations (via ?func= parameter):
 * - pull        - Fetch latest changes from remote repository
 * - add         - Mark files for upload (batch operation)
 * - commit_push - Upload files directly to remote repository
 * - status      - Display repository status via API
 * - diff        - Compare local file with remote version
 * - log         - Show commit history via API
 * - default     - Display main git management interface
 *
 * Security Features:
 * - Admin-only access (isAdmin() check)
 * - Path validation for all file operations
 * - All operations logged for audit trail
 * - API token authentication
 * - CSRF protection via WaSQL framework
 *
 * Global Variables Used:
 * @global array $USER Current user information
 * @global array $git  Git operation data and results
 * @global array $CONFIG Configuration including API settings
 * @global array $_SESSION Session data including git_path
 * @global array $_REQUEST Request parameters (func, files, msg_*, etc.)
 *
 * Response Variables Set:
 * @var array $git['files']   - Array of modified files with metadata
 * @var array $git['error']   - Error message if operation failed
 * @var array $git['success'] - Success message if operation succeeded
 * @var array $git['warning'] - Warning message for partial failures
 * @var array $git['status']  - Repository status from API
 * @var array $recs           - Operation results for display
 *
 * @package    WaSQL
 * @subpackage GitManagement
 * @version    3.0 - API Edition
 * @author     WaSQL Development Team
 * @copyright  Copyright (c) 2024 WaSQL
 * @license    See WaSQL license
 *
 * @see git_functions.php For core git operation functions
 * @see git_body.htm For view templates
 * @see gitapi.php For API functions
 *
 * @example URL: /php/admin.php?_menu=git&func=pull
 * @example URL: /php/admin.php?_menu=git&func=commit_push&files[]=base64file1
 */
// Authentication check - require admin user
global $USER;
if(!isUser()){
	setView('not_authenticated', 1);
	return;
}

// Check if user has admin rights
if(!isAdmin()){
	setView('not_authorized', 1);
	gitLog("Unauthorized access attempt by user: " . ($USER['username'] ?? 'unknown'), 'warning');
	return;
}

// Initialize git path
if(!isset($_SESSION['git_path']) || !strlen($_SESSION['git_path'])){
	$_SESSION['git_path'] = getWasqlPath();
}

if(!isset($_SESSION['git_path']) || !strlen($_SESSION['git_path'])){
	$p = gitGetPath();
	switch(strtolower($p)){
		case 'not_enabled':
		case 'invalid_path':
			setView($p, 1);
			return;
		break;
		default:
			$_SESSION['git_path'] = $p;
		break;
	}
}

global $git, $CONFIG;
$git = array(
	'details' => array(),
	'files' => array(),
	'error' => null,
	'success' => null
);

if(!isset($_REQUEST['func'])){
	$_REQUEST['func'] = '';
}

$func = strtolower(trim($_REQUEST['func']));

// Log the action
gitLog("Git API action initiated: {$func}", 'info');

switch($func){
	case 'pull':
		// Fetch latest commits from remote repository
		$result = gitapiPull(array());
		if($result['success']){
			$recs = array('Pull completed successfully - Latest commits fetched');
			if(isset($result['data']) && is_array($result['data'])){
				$commit_count = count($result['data']);
				$recs[] = "Found {$commit_count} recent commit(s)";
				// Show first 5 commits
				$show_count = min(5, $commit_count);
				for($i = 0; $i < $show_count; $i++){
					$commit = $result['data'][$i];
					if(isset($commit['sha']) && isset($commit['commit']['message'])){
						$short_sha = substr($commit['sha'], 0, 7);
						$message = $commit['commit']['message'];
						$recs[] = "{$short_sha}: {$message}";
					}
				}
			}
			$git['success'] = 'Pull completed successfully';
			gitLog("Git API pull completed successfully", 'info');
		} else {
			$error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
			$recs = array("Pull failed: {$error_msg}");
			$git['error'] = 'Pull failed';
			$git['warning'] = $error_msg;
			gitLog("Git API pull failed: " . $error_msg, 'error');
		}
		setView('git_details', 1);
		return;
	break;

	case 'add':
		// Mark files for upload (stored in session)
		$recs = array();
		if(isset($_REQUEST['files']) && is_array($_REQUEST['files']) && count($_REQUEST['files']) > 0){
			$files_to_add = array();
			$invalid_files = array();

			// Validate all files first
			foreach($_REQUEST['files'] as $bfile){
				$file = base64_decode($bfile);
				if(!gitValidateFilePath($file)){
					$invalid_files[] = $file;
					gitLog("Invalid file path in add operation: {$file}", 'warning');
				} else {
					$files_to_add[] = $file;
				}
			}

			// Mark files for upload (store in session)
			if(count($files_to_add) > 0){
				if(!isset($_SESSION['git_staged_files'])){
					$_SESSION['git_staged_files'] = array();
				}
				foreach($files_to_add as $file){
					$_SESSION['git_staged_files'][$file] = true;
					$recs[] = "Staged for upload: {$file}";
				}
				$git['success'] = count($files_to_add) . ' file(s) staged for upload';
			}

			if(count($invalid_files) > 0){
				foreach($invalid_files as $file){
					$recs[] = "Invalid file path: {$file}";
				}
			}
		} else {
			$git['error'] = 'No files selected';
			$recs[] = 'No files selected';
		}
		setView('git_details', 1);
		return;
	break;

	case 'commit_push':
		// Upload files directly to remote repository via API
		$recs = array();

		if(!isset($_REQUEST['files']) || !is_array($_REQUEST['files']) || count($_REQUEST['files']) == 0){
			$git['error'] = 'No files selected for upload';
			$recs[] = 'No files selected';
			setView('git_details', 1);
			return;
		}

		$push_count = 0;
		$commit_errors = array();
		$git_realpath = realpath($_SESSION['git_path']);

		foreach($_REQUEST['files'] as $bfile){
			$file = base64_decode($bfile);

			// Validate file path
			if(!gitValidateFilePath($file)){
				$recs[] = "Invalid file path: {$file}";
				$commit_errors[] = $file;
				gitLog("Invalid file path in upload operation: {$file}", 'warning');
				continue;
			}

			// Calculate sha for message lookup
			$afile = $git_realpath . DIRECTORY_SEPARATOR . $file;
			$sha = sha1(realpath($afile));
			$msg = '';

			// Get commit message
			if(isset($_REQUEST["msg_{$sha}"]) && strlen(trim($_REQUEST["msg_{$sha}"]))){
				$msg = trim($_REQUEST["msg_{$sha}"]);
			} elseif(isset($_REQUEST['msg']) && strlen(trim($_REQUEST['msg']))){
				$msg = trim($_REQUEST['msg']);
			}

			// Validate commit message
			if(!gitValidateCommitMessage($msg)){
				$recs[] = "INVALID OR MISSING MESSAGE for \"{$file}\" - NOT UPLOADED (must be 3-500 characters)";
				$commit_errors[] = $file;
				continue;
			}

			// Get local file content
			$local_file_result = gitGetLocalFile($file);
			if(!$local_file_result['success']){
				$recs[] = "Failed to read local file {$file}: " . $local_file_result['error'];
				$commit_errors[] = $file;
				continue;
			}

			$content = $local_file_result['content'];

			// Check if file exists on remote (to determine create vs update)
			$remote_sha = gitGetRemoteFileSha($file);

			if($remote_sha !== null){
				// File exists - update it
				$result = gitapiUpdateFile($file, $content, $msg, array('sha' => $remote_sha));
				if($result['success']){
					$recs[] = "Updated on remote: {$file} - {$msg}";
					$push_count++;
					gitLog("File updated via API: {$file} | Message: {$msg}", 'info');
				} else {
					$error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
					$recs[] = "Failed to update {$file}: {$error_msg}";
					$commit_errors[] = $file;
				}
			} else {
				// File doesn't exist - create it
				$result = gitapiCreateFile($file, $content, $msg);
				if($result['success']){
					$recs[] = "Created on remote: {$file} - {$msg}";
					$push_count++;
					gitLog("File created via API: {$file} | Message: {$msg}", 'info');
				} else {
					$error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
					$recs[] = "Failed to create {$file}: {$error_msg}";
					$commit_errors[] = $file;
				}
			}
		}

		// Report results
		if($push_count > 0){
			$git['success'] = "Uploaded {$push_count} file(s) to remote repository";
			gitLog("Uploaded {$push_count} files via API", 'info');
		} else {
			$git['error'] = 'No files were uploaded';
		}

		if(count($commit_errors) > 0){
			$git['warning'] = count($commit_errors) . ' file(s) had errors';
		}

		// Clear staged files
		unset($_SESSION['git_staged_files']);

		setView('git_details', 1);
		return;
	break;

	case 'status':
		// Get local git status (exception - uses local git command)
		$result = gitLocalStatus();
		if($result['success']){
			$git['status'] = $result['output'];
		} else {
			$git['status'] = 'Error: ' . $result['error'];
			$git['error'] = 'Failed to get status';
		}
		setView('git_status', 1);
		return;
	break;

	case 'config':
		// Display API configuration
		global $CONFIG;
		$config_lines = array();
		$config_lines[] = "API Provider: " . (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'Not set');
		$config_lines[] = "API Base URL: " . (isset($CONFIG['gitapi_baseurl']) ? $CONFIG['gitapi_baseurl'] : 'Using default');
		$config_lines[] = "Repository Owner: " . (isset($CONFIG['gitapi_owner']) ? $CONFIG['gitapi_owner'] : 'Not set');
		$config_lines[] = "Repository Name: " . (isset($CONFIG['gitapi_repo']) ? $CONFIG['gitapi_repo'] : 'Not set');
		$config_lines[] = "Default Branch: " . (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'main');
		$config_lines[] = "API Token: " . (isset($CONFIG['gitapi_token']) && strlen($CONFIG['gitapi_token']) > 0 ? 'Set (****)' : 'Not set');

		$git['config'] = implode("\n", $config_lines);
		setView('git_config', 1);
		return;
	break;

	case 'diff':
		// Compare local file with remote version - smart diff with LCS algorithm

		if(!isset($_REQUEST['file'])){
			$git['error'] = 'No file specified';
			setView('git_error', 1);
			return;
		}

		$file = base64_decode($_REQUEST['file']);

		// Validate file path
		if(!gitValidateFilePath($file)){
			$git['error'] = 'Invalid file path';
			gitLog("Invalid file path in diff operation: {$file}", 'warning');
			setView('git_error', 1);
			return;
		}

		$recs = array();

		// Get local file content
		$local_result = gitGetLocalFile($file);
		if(!$local_result['success']){
			$git['error'] = 'Failed to read local file: ' . $local_result['error'];
			setView('git_error', 1);
			return;
		}

		// Get remote file content
		$remote_result = gitapiGetFile($file);
		if(!$remote_result['success']){
			$git['error'] = 'Failed to get remote file: ' . (isset($remote_result['error']) ? $remote_result['error'] : 'Unknown error');
			setView('git_error', 1);
			return;
		}

		// Decode remote content (GitHub returns base64)
		$remote_content = '';
		if(isset($remote_result['data']['content'])){
			$remote_content = base64_decode($remote_result['data']['content']);
		}

		// Check if files are identical
		if($local_result['content'] === $remote_content){
			$recs[] = array('class' => '', 'line' => 'Files are identical - no differences found');
			setView('git_diff', 1);
			return;
		}

		// Split into lines
		$remote_lines = preg_split('/\R/', $remote_content);
		$local_lines  = preg_split('/\R/', $local_result['content']);

		// Use LCS-based diff algorithm for smarter diff
		$diff = gitSmartDiff($remote_lines, $local_lines);

		// Add header
		$recs[] = array('class' => '', 'line' => '--- Remote version');
		$recs[] = array('class' => '', 'line' => '+++ Local version');

		// Process diff output with context
		$context_lines = 3;
		$output = array();
		$last_change_idx = -999;

		foreach($diff as $idx => $item){
			$type = $item['type'];

			// Determine if we should show this line
			$show = ($type != 'same');

			// Also show if it's a context line near a change
			if(!$show && $type == 'same'){
				// Check if near a change
				for($j = max(0, $idx - $context_lines); $j <= min(count($diff) - 1, $idx + $context_lines); $j++){
					if($diff[$j]['type'] != 'same'){
						$show = true;
						break;
					}
				}
			}

			if($show){
				// Add separator if there's a gap
				if($idx - $last_change_idx > 1 && $last_change_idx != -999){
					$needs_sep = false;
					for($k = $last_change_idx + 1; $k < $idx; $k++){
						if($diff[$k]['type'] == 'same'){
							$needs_sep = true;
							break;
						}
					}
					if($needs_sep){
						$output[] = array('class' => 'w_grey', 'line' => '...');
					}
				}

				// Output the line
				if($type == 'same'){
					$line_num = $item['old_num'];
					$output[] = array('class' => '', 'line' => ' ' . $line_num . ': ' . encodeHtml($item['line']));
				} elseif($type == 'delete'){
					$line_num = $item['old_num'];
					$output[] = array('class' => 'w_del', 'line' => '-' . $line_num . ': ' . encodeHtml($item['line']));
				} elseif($type == 'insert'){
					$line_num = $item['new_num'];
					$output[] = array('class' => 'w_ins', 'line' => '+' . $line_num . ': ' . encodeHtml($item['line']));
				}

				$last_change_idx = $idx;
			}
		}

		$recs = array_merge($recs, $output);
		setView('git_diff', 1);
		return;
	break;

	case 'log':
		// Get commit history via API
		if(!isset($_REQUEST['file'])){
			$git['error'] = 'No file specified';
			setView('git_error', 1);
			return;
		}

		$file = base64_decode($_REQUEST['file']);

		// Validate file path
		if(!gitValidateFilePath($file)){
			$git['error'] = 'Invalid file path';
			gitLog("Invalid file path in log operation: {$file}", 'warning');
			setView('git_error', 1);
			return;
		}

		// Get commits from API
		$result = gitapiCommits(array('per_page' => 10));
		if($result['success']){
			$recs = array();
			if(isset($result['data']) && is_array($result['data'])){
				foreach($result['data'] as $commit){
					if(isset($commit['sha']) && isset($commit['commit']['message'])){
						$short_sha = substr($commit['sha'], 0, 7);
						$message = trim(explode("\n", $commit['commit']['message'])[0]);
						$date = isset($commit['commit']['author']['date']) ? $commit['commit']['author']['date'] : '';
						$author = isset($commit['commit']['author']['name']) ? $commit['commit']['author']['name'] : '';
						$recs[] = "{$short_sha} - {$message} ({$author}) {$date}";
					}
				}
			}
			if(empty($recs)){
				$recs[] = 'No commits found';
			}
		} else {
			$recs = array('Error: ' . (isset($result['error']) ? $result['error'] : 'Unknown error'));
			$git['error'] = 'Failed to get log';
		}

		setView('git_log', 1);
		return;
	break;

	default:
		gitFileInfo();
		setView('default', 1);
	break;
}
?>
