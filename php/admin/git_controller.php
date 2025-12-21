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

// Initialize git repository settings from $GITREPO
// Read gitrepo tags from config.xml and populate $GITREPO global
global $GITREPO;
if(!isset($GITREPO) || !is_array($GITREPO)){
	$GITREPO = array();
	// Read config.xml to get gitrepo configurations
	$progpath = dirname(__FILE__);
	$config_file = '';
	if(is_file("{$progpath}/../config.xml")){
		$config_file = "{$progpath}/../config.xml";
	}
	elseif(is_file("{$progpath}/../../config.xml")){
		$config_file = "{$progpath}/../../config.xml";
	}

	if(strlen($config_file) && is_file($config_file)){
		$xml = readXML($config_file);
		$json = json_encode($xml);
		$xml = json_decode($json, true);

		// Check for gitrepo tags
		if(isset($xml['gitrepo'])){
			// Check if single gitrepo tag
			if(isset($xml['gitrepo']['@attributes'])){
				$xml['gitrepo'] = array($xml['gitrepo']);
			}

			// Process each gitrepo tag
			foreach($xml['gitrepo'] as $gitrepo){
				if(!isset($gitrepo['@attributes']['name'])){
					// If no name, generate one from path
					if(isset($gitrepo['@attributes']['path'])){
						$name = basename($gitrepo['@attributes']['path']);
					}
					elseif(isset($gitrepo['@attributes']['gitapi_path'])){
						$name = basename($gitrepo['@attributes']['gitapi_path']);
					}
					else{
						continue;
					}
				}
				else{
					$name = $gitrepo['@attributes']['name'];
				}
				$name = strtolower($name);
				$GITREPO[$name] = array();
				foreach($gitrepo['@attributes'] as $k => $v){
					$GITREPO[$name][$k] = $v;
				}
			}
		}
	}
}

global $current_repo;
// Check if GITREPO is configured
if(!isset($GITREPO) || !is_array($GITREPO) || count($GITREPO) == 0){
	setView('no_repos_configured', 1);
	return;
}
//set current repo
if(isset($_REQUEST['current_repo']) && isset($GITREPO[$_REQUEST['current_repo']])){
	$GITREPO[$_REQUEST['current_repo']]['class']='active';
	$current_repo=$GITREPO[$_REQUEST['current_repo']];
}
else{
	foreach($GITREPO as $i=>$current_repo){
		$GITREPO[$i]['class']=$current_repo['class']='active';
		break;
	}
}
global $CONFIG;
// Set git path from current repo (support both 'path' and 'gitapi_path' attributes)
$repo_path = null;
if(isset($current_repo['path']) && is_dir($current_repo['path'])){
	$repo_path = $current_repo['path'];
}
elseif(isset($current_repo['gitapi_path']) && is_dir($current_repo['gitapi_path'])){
	$repo_path = $current_repo['gitapi_path'];
}

if($repo_path && is_dir($repo_path)){
	$CONFIG['gitapi_path'] = realpath($repo_path);
} else {
	setView('invalid_repo_path', 1);
	return;
}
//ksort($CONFIG);
//ksort($current_repo);
//echo printValue($current_repo).printValue($CONFIG);exit;
// Set API configuration from current repo - support both with and without gitapi_ prefix
$CONFIG['gitapi_name'] = isset($current_repo['name']) ? $current_repo['name'] : 'WaSQL';

// Provider: check gitapi_provider first, then provider
$CONFIG['gitapi_provider'] = isset($current_repo['gitapi_provider']) ? $current_repo['gitapi_provider'] :
                              (isset($current_repo['provider']) ? $current_repo['provider'] : 'github');

// Token: check gitapi_token first, then token
$CONFIG['gitapi_token'] = isset($current_repo['gitapi_token']) ? $current_repo['gitapi_token'] :
                          (isset($current_repo['token']) ? $current_repo['token'] : '');

// Owner: check gitapi_owner first, then owner
$CONFIG['gitapi_owner'] = isset($current_repo['gitapi_owner']) ? $current_repo['gitapi_owner'] :
                          (isset($current_repo['owner']) ? $current_repo['owner'] : '');

// Repo: check gitapi_repo first, then repo
$CONFIG['gitapi_repo'] = isset($current_repo['gitapi_repo']) ? $current_repo['gitapi_repo'] :
                         (isset($current_repo['repo']) ? $current_repo['repo'] : '');

// Branch: check gitapi_branch first, then branch
$CONFIG['gitapi_branch'] = isset($current_repo['gitapi_branch']) ? $current_repo['gitapi_branch'] :
                           (isset($current_repo['branch']) ? $current_repo['branch'] : 'master');

// SSL Verify: check gitapi_ssl_verify first, then ssl_verify
$CONFIG['gitapi_ssl_verify'] = isset($current_repo['gitapi_ssl_verify']) ? $current_repo['gitapi_ssl_verify'] :
                                (isset($current_repo['ssl_verify']) ? $current_repo['ssl_verify'] : 'false');

// Base URL: check gitapi_baseurl first, then baseurl
if(isset($current_repo['gitapi_baseurl'])){
	$CONFIG['gitapi_baseurl'] = $current_repo['gitapi_baseurl'];
}
elseif(isset($current_repo['baseurl'])){
	$CONFIG['gitapi_baseurl'] = $current_repo['baseurl'];
}

global $git;
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
		// Pull latest changes from remote repository using git command
		$result = gitapiPull(array());
		if($result['success']){
			$recs = array('Pull completed successfully');
			if(isset($result['output']) && strlen($result['output'])){
				// Show git pull output
				$output_lines = explode("\n", $result['output']);
				foreach($output_lines as $line){
					if(strlen(trim($line)) > 0){
						$recs[] = $line;
					}
				}
			}
			$git['success'] = 'Pull completed successfully';
			gitLog("Git pull completed successfully", 'info');
		} else {
			$error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
			$recs = array("Pull failed: {$error_msg}");
			$git['error'] = 'Pull failed';
			$git['warning'] = $error_msg;
			gitLog("Git pull failed: " . $error_msg, 'error');
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
				if(!isset($CONFIG['git_staged_files'])){
					$CONFIG['git_staged_files'] = array();
				}
				foreach($files_to_add as $file){
					$CONFIG['git_staged_files'][$file] = true;
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

		// PRE-FLIGHT CHECK: Ensure local repo is up to date before pushing via API
		$recs[] = "--- Checking if local repository is up to date ---";
		$pull_check = gitapiPull(array());
		if($pull_check['success']){
			if(isset($pull_check['output']) && strlen($pull_check['output'])){
				$recs[] = "Local repository status: " . $pull_check['output'];
				// Check if pull brought in changes
				if(strpos($pull_check['output'], 'Already up to date') === false &&
				   strpos($pull_check['output'], 'Already up-to-date') === false){
					$recs[] = "Local repository was updated from remote";
					gitLog("Local repository updated before API push", 'info');
				}
			}
		} else {
			$recs[] = "Warning: Could not verify local repository is up to date";
			$recs[] = "Error: " . $pull_check['error'];
			$recs[] = "You may need to run 'git pull' manually before pushing";
			gitLog("Pre-flight pull check failed: " . $pull_check['error'], 'warning');
		}
		$recs[] = "";

		$push_count = 0;
		$commit_errors = array();
		$git_realpath = realpath($CONFIG['gitapi_path']);

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
			$afile_real = realpath($afile);
			if($afile_real === false){
				$recs[] = "File not found locally: {$file}";
				$commit_errors[] = $file;
				continue;
			}
			$sha = sha1($afile_real);
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

			// Convert Windows paths to forward slashes for API
			$api_file_path = str_replace('\\', '/', $file);

			// Check if file exists on remote (to determine create vs update)
			$remote_sha = gitGetRemoteFileSha($api_file_path);

			if($remote_sha !== null){
				// File exists - update it
				$result = gitapiUpdateFile($api_file_path, $content, $msg, array('sha' => $remote_sha));
				if($result['success']){
					$recs[] = "Updated on remote: {$file} - {$msg}";
					$push_count++;
					gitLog("File updated via API: {$file} | Message: {$msg}", 'info');
				} else {
					$error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
					$recs[] = "Failed to update {$file}: {$error_msg}";
					$commit_errors[] = $file;
					gitLog("API update failed for {$file}: {$error_msg}", 'error');
				}
			} else {
				// File doesn't exist - create it
				$result = gitapiCreateFile($api_file_path, $content, $msg);
				if($result['success']){
					$recs[] = "Created on remote: {$file} - {$msg}";
					$push_count++;
					gitLog("File created via API: {$file} | Message: {$msg}", 'info');
				} else {
					$error_msg = isset($result['error']) ? $result['error'] : 'Unknown error';
					$recs[] = "Failed to create {$file}: {$error_msg}";

					// Try to give more helpful error message
					if(strpos($error_msg, 'Not Found') !== false || strpos($error_msg, '404') !== false){
						$recs[] = "Tip: Try using git CLI to commit this file first, or ensure parent directory exists on remote";
						$recs[] = "Run: git add \"{$file}\" && git commit -m \"{$msg}\" && git push";
					}

					$commit_errors[] = $file;
					gitLog("API create failed for {$file}: {$error_msg}", 'error');
				}
			}
		}

		// Report results
		if($push_count > 0){
			$git['success'] = "Uploaded {$push_count} file(s) to remote repository";
			gitLog("Uploaded {$push_count} files via API", 'info');

			// Sync local repository with remote after successful push
			$recs[] = "--- Syncing local repository with remote ---";
			$pull_result = gitapiPull(array());
			if($pull_result['success']){
				$recs[] = "Local repository synced successfully";
				gitLog("Local repository synced after API push", 'info');
			} else {
				$error_msg = isset($pull_result['error']) ? $pull_result['error'] : 'Unknown error';
				$recs[] = "Warning: Could not sync local repository: {$error_msg}";
				$recs[] = "You may need to run 'git pull' manually from command line";
				gitLog("Failed to sync local repository after API push: " . $error_msg, 'warning');
			}
		} else {
			$git['error'] = 'No files were uploaded';
		}

		if(count($commit_errors) > 0){
			$git['warning'] = count($commit_errors) . ' file(s) had errors';
		}

		// Clear staged files
		unset($CONFIG['git_staged_files']);

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
		// Display API configuration for current repository
		$config_lines = array();
		$config_lines[] = "Current Repository: " . $current_repo['name'];
		$config_lines[] = "Local Path: " . $current_repo['path'];
		$config_lines[] = "";
		$config_lines[] = "API Provider: " . $CONFIG['gitapi_provider'];
		$config_lines[] = "API Base URL: " . (isset($CONFIG['gitapi_baseurl']) ? $CONFIG['gitapi_baseurl'] : 'Using default');
		$config_lines[] = "Repository Owner: " . $CONFIG['gitapi_owner'];
		$config_lines[] = "Repository Name: " . $CONFIG['gitapi_repo'];
		$config_lines[] = "Branch: " . $CONFIG['gitapi_branch'];
		$config_lines[] = "SSL Verify: " . $CONFIG['gitapi_ssl_verify'];
		$config_lines[] = "API Token: " . (strlen($CONFIG['gitapi_token']) > 0 ? 'Set (****)' : 'Not set');

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

	case 'add_to_gitignore':
		// Add file/folder to .gitignore
		if(!isset($_REQUEST['file'])){
			$git['error'] = 'No file specified';
			$recs = array('No file specified');
			setView('git_details', 1);
			return;
		}

		$file = base64_decode($_REQUEST['file']);

		// Validate file path
		if(!gitValidateFilePath($file)){
			$git['error'] = 'Invalid file path';
			$recs = array("Invalid file path: {$file}");
			gitLog("Invalid file path in gitignore operation: {$file}", 'warning');
			setView('git_details', 1);
			return;
		}

		$git_realpath = realpath($CONFIG['gitapi_path']);
		if($git_realpath === false){
			$git['error'] = "Invalid gitapi_path: {$CONFIG['gitapi_path']}";
			$recs = array('Invalid gitapi_path');
			setView('git_details', 1);
			return;
		}

		$afile = $git_realpath . DIRECTORY_SEPARATOR . $file;
		$afile_real = realpath($afile);

		// Security check - ensure file is within git path (or doesn't exist yet)
		if($afile_real === false){
			// File doesn't exist yet, use constructed path for SHA
			$file_sha = sha1($afile);
		} else {
			// File exists, use realpath for SHA (matches gitFileInfo)
			$file_sha = sha1($afile_real);

			if(strpos($afile_real, $git_realpath) !== 0){
				$git['error'] = 'File path security violation';
				$recs = array("Security violation: File must be within git repository");
				gitLog("Security violation in gitignore operation: {$file}", 'warning');
				setView('git_details', 1);
				return;
			}
		}

		// Get .gitignore path
		$gitignore_path = $git_realpath . DIRECTORY_SEPARATOR . '.gitignore';

		// Read current .gitignore content
		$gitignore_content = '';
		if(file_exists($gitignore_path)){
			$gitignore_content = file_get_contents($gitignore_path);
		}

		// Convert to Unix line endings for consistent processing
		$gitignore_content = str_replace("\r\n", "\n", $gitignore_content);
		$lines = explode("\n", $gitignore_content);

		// Check if entry already exists
		$entry_exists = false;
		foreach($lines as $line){
			$line = trim($line);
			if($line === $file || $line === '/' . $file){
				$entry_exists = true;
				break;
			}
		}

		if($entry_exists){
			$recs = array("Already in .gitignore: {$file}");
			$git['warning'] = 'Entry already exists in .gitignore';
			$git['deleted_sha'] = $file_sha;
		} else {
			// Add entry to .gitignore
			$new_entry = $file;

			// Ensure file ends with newline before appending
			if(!empty($gitignore_content) && substr($gitignore_content, -1) !== "\n"){
				$new_entry = "\n" . $new_entry;
			}
			$new_entry .= "\n";

			// Append to .gitignore
			if(file_put_contents($gitignore_path, $gitignore_content . $new_entry)){
				$recs = array("Added to .gitignore: {$file}");
				$git['success'] = 'Added to .gitignore successfully';
				$git['deleted_sha'] = $file_sha;
				gitLog("Added to .gitignore: {$file}", 'info');
			} else {
				$recs = array("Failed to update .gitignore: {$file}");
				$git['error'] = 'Failed to update .gitignore';
				gitLog("Failed to update .gitignore: {$file}", 'error');
			}
		}

		setView('git_details', 1);
		return;
	break;

	case 'delete_file':
		// Delete local file (for new/untracked files)
		if(!isset($_REQUEST['file'])){
			$git['error'] = 'No file specified';
			$recs = array('No file specified');
			setView('git_details', 1);
			return;
		}

		$file = base64_decode($_REQUEST['file']);

		// Validate file path
		if(!gitValidateFilePath($file)){
			$git['error'] = 'Invalid file path';
			$recs = array("Invalid file path: {$file}");
			gitLog("Invalid file path in delete operation: {$file}", 'warning');
			setView('git_details', 1);
			return;
		}

		$git_realpath = realpath($CONFIG['gitapi_path']);
		if($git_realpath === false){
			$git['error'] = 'Invalid gitapi_path: '.$CONFIG['gitapi_path'];
			$recs = array('Invalid gitapi_path');
			setView('git_details', 1);
			return;
		}

		$afile = $git_realpath . DIRECTORY_SEPARATOR . $file;
		$afile_real = realpath($afile);

		// Security check - ensure file is within git path
		if($afile_real === false || strpos($afile_real, $git_realpath) !== 0){
			$git['error'] = 'File path security violation';
			$recs = array("Security violation: File must be within git repository");
			gitLog("Security violation in delete operation: {$file}", 'warning');
			setView('git_details', 1);
			return;
		}

		// Check if file exists
		if(!file_exists($afile_real)){
			$git['error'] = 'File not found';
			$recs = array("File not found: {$file}");
			setView('git_details', 1);
			return;
		}

		// Calculate sha for identifying the row
		$file_sha = sha1($afile_real);

		// Delete the file
		if(unlink($afile_real)){
			$recs = array("Successfully deleted: {$file}");
			$git['success'] = 'File deleted successfully';
			$git['deleted_sha'] = $file_sha;
			gitLog("Deleted local file: {$file}", 'info');
		} else {
			$recs = array("Failed to delete: {$file}");
			$git['error'] = 'Failed to delete file';
			gitLog("Failed to delete local file: {$file}", 'error');
		}

		setView('git_details', 1);
		return;
	break;

	default:
		gitFileInfo();
		setView('default', 1);
	break;
}
?>
