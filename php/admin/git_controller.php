<?php
/**
 * Git Controller for WaSQL
 *
 * Main request handler and business logic controller for git repository operations.
 * This file is called from the WaSQL admin panel when the git menu is accessed.
 *
 * Request Flow:
 * 1. Authentication check (requires logged-in admin user)
 * 2. Git path initialization and validation
 * 3. Route request based on 'func' parameter
 * 4. Execute operation with validation and error handling
 * 5. Set appropriate view for response
 *
 * Supported Operations (via ?func= parameter):
 * - pull        - Pull latest changes from remote repository
 * - add         - Stage files for commit (batch operation)
 * - remove      - Remove files from git tracking (requires confirmation)
 * - revert      - Revert files to last committed state (requires confirmation)
 * - commit_push - Commit staged files and push to remote
 * - status      - Display git status (read-only, not logged)
 * - config      - Display git config (read-only, not logged)
 * - diff        - Show changes in a specific file
 * - log         - Show commit history for a file
 * - default     - Display main git management interface
 *
 * Security Features:
 * - Admin-only access (isAdmin() check)
 * - Server-side confirmation for destructive operations
 * - Path validation for all file operations
 * - All operations logged for audit trail
 * - CSRF protection via WaSQL framework
 *
 * Performance Optimizations:
 * - Batch git add operations (single command for multiple files)
 * - Removed unnecessary gitFileInfo() call in commit_push
 * - Optional logging for frequent read-only operations
 * - Efficient error handling and early returns
 *
 * Global Variables Used:
 * @global array $USER Current user information
 * @global array $git  Git operation data and results
 * @global array $_SESSION Session data including git_path
 * @global array $_REQUEST Request parameters (func, files, msg_*, etc.)
 *
 * Response Variables Set:
 * @var array $git['files']   - Array of modified files with metadata
 * @var array $git['error']   - Error message if operation failed
 * @var array $git['success'] - Success message if operation succeeded
 * @var array $git['warning'] - Warning message for partial failures
 * @var array $git['status']  - Raw git status output
 * @var array $git['config']  - Git configuration output
 * @var array $recs           - Operation results for display
 *
 * @package    WaSQL
 * @subpackage GitManagement
 * @version    2.0
 * @author     WaSQL Development Team
 * @copyright  Copyright (c) 2024 WaSQL
 * @license    See WaSQL license
 *
 * @see git_functions.php For core git operation functions
 * @see git_body.htm For view templates
 * @see GIT_README.md For complete documentation
 *
 * @example URL: /php/admin.php?_menu=git&func=pull
 * @example URL: /php/admin.php?_menu=git&func=add&files[]=base64file1&files[]=base64file2
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
gitLog("Git action initiated: {$func}", 'info');

switch($func){
	case 'pull':
		$result = gitCommand('pull', array('-v'));
		if($result['success']){
			$recs = preg_split('/[\r\n]+/', $result['output']);
			$git['success'] = 'Pull completed successfully';
			gitLog("Git pull completed successfully", 'info');
		} else {
			$recs = array($result['error']);
			$git['error'] = 'Pull failed: ' . $result['error'];
			gitLog("Git pull failed: " . $result['error'], 'error');
		}
		setView('git_details', 1);
		return;
	break;

	case 'add':
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

			// Batch add all valid files in one command for better performance
			if(count($files_to_add) > 0){
				$result = gitCommand('add', $files_to_add);
				if($result['success']){
					foreach($files_to_add as $file){
						$recs[] = "Added: {$file}";
					}
					$git['success'] = count($files_to_add) . ' file(s) added to staging';
				} else {
					$recs[] = "Failed to add files: " . $result['error'];
					$git['error'] = 'Add operation failed';
				}
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

	case 'remove':
		$recs = array();
		if(isset($_REQUEST['files']) && is_array($_REQUEST['files']) && count($_REQUEST['files']) > 0){
			// Server-side confirmation required
			if(!isset($_REQUEST['confirm_remove']) || $_REQUEST['confirm_remove'] !== '1'){
				$git['error'] = 'Remove operation requires confirmation';
				$recs[] = 'Remove operation not confirmed';
				setView('git_details', 1);
				return;
			}

			foreach($_REQUEST['files'] as $bfile){
				$file = base64_decode($bfile);

				// Validate file path
				if(!gitValidateFilePath($file)){
					$recs[] = "Invalid file path: {$file}";
					gitLog("Invalid file path in remove operation: {$file}", 'warning');
					continue;
				}

				$result = gitCommand('rm', array($file));
				if($result['success']){
					$recs[] = "Removed: {$file}";
					gitLog("File removed from git: {$file}", 'info');
				} else {
					$recs[] = "Failed to remove {$file}: " . $result['error'];
				}
			}
			$git['success'] = 'Files removed from git';
		} else {
			$git['error'] = 'No files selected';
			$recs[] = 'No files selected';
		}
		gitFileInfo();
		setView('default', 1);
		return;
	break;

	case 'revert':
		$recs = array();
		if(isset($_REQUEST['files']) && is_array($_REQUEST['files']) && count($_REQUEST['files']) > 0){
			// Server-side confirmation required
			if(!isset($_REQUEST['confirm_revert']) || $_REQUEST['confirm_revert'] !== '1'){
				$git['error'] = 'Revert operation requires confirmation';
				$recs[] = 'Revert operation not confirmed';
				setView('git_details', 1);
				return;
			}

			foreach($_REQUEST['files'] as $bfile){
				$file = base64_decode($bfile);

				// Validate file path
				if(!gitValidateFilePath($file)){
					$recs[] = "Invalid file path: {$file}";
					gitLog("Invalid file path in revert operation: {$file}", 'warning');
					continue;
				}

				$result = gitCommand('checkout', array('--', $file));
				if($result['success']){
					$recs[] = "Reverted: {$file}";
					gitLog("File reverted: {$file}", 'info');
				} else {
					$recs[] = "Failed to revert {$file}: " . $result['error'];
				}
			}
			$git['success'] = 'Files reverted';
		} else {
			$git['error'] = 'No files selected';
			$recs[] = 'No files selected';
		}
		setView('git_details', 1);
		return;
	break;

	case 'commit_push':
		$recs = array();

		if(!isset($_REQUEST['files']) || !is_array($_REQUEST['files']) || count($_REQUEST['files']) == 0){
			$git['error'] = 'No files selected for commit';
			$recs[] = 'No files selected';
			setView('git_details', 1);
			return;
		}

		$push = 0;
		$commit_errors = array();
		$git_realpath = realpath($_SESSION['git_path']);

		foreach($_REQUEST['files'] as $bfile){
			$file = base64_decode($bfile);

			// Validate file path
			if(!gitValidateFilePath($file)){
				$recs[] = "Invalid file path: {$file}";
				$commit_errors[] = $file;
				gitLog("Invalid file path in commit operation: {$file}", 'warning');
				continue;
			}

			// Calculate sha directly without calling gitFileInfo() for better performance
			$afile = $git_realpath . DIRECTORY_SEPARATOR . $file;
			$sha = sha1(realpath($afile));
			$msg = '';

			// Get commit message (check per-file message first, then global)
			if(isset($_REQUEST["msg_{$sha}"]) && strlen(trim($_REQUEST["msg_{$sha}"]))){
				$msg = trim($_REQUEST["msg_{$sha}"]);
			} elseif(isset($_REQUEST['msg']) && strlen(trim($_REQUEST['msg']))){
				$msg = trim($_REQUEST['msg']);
			}

			// Validate commit message
			if(!gitValidateCommitMessage($msg)){
				$recs[] = "INVALID OR MISSING MESSAGE for \"{$file}\" - NOT COMMITTED (must be 3-500 characters)";
				$commit_errors[] = $file;
				continue;
			}

			// Commit the file
			$result = gitCommand('commit', array('-m', $msg, $file));
			if($result['success']){
				$recs[] = "Committed: {$file} - {$msg}";
				$push++;
				gitLog("File committed: {$file} | Message: {$msg}", 'info');
			} else {
				$recs[] = "Failed to commit {$file}: " . $result['error'];
				$commit_errors[] = $file;
			}
		}

		// Push if any commits were successful
		if($push > 0){
			$result = gitCommand('push', array());
			if($result['success']){
				$recs[] = "Successfully pushed {$push} commit(s) to remote repository";
				$git['success'] = "Committed and pushed {$push} file(s)";
				gitLog("Pushed {$push} commits to remote", 'info');
			} else {
				$recs[] = "Push failed: " . $result['error'];
				$git['error'] = "Committed {$push} file(s) but push failed: " . $result['error'];
				gitLog("Push failed: " . $result['error'], 'error');
			}
		} else {
			$git['error'] = 'No files were committed';
		}

		if(count($commit_errors) > 0){
			$git['warning'] = count($commit_errors) . ' file(s) had errors';
		}

		setView('git_details', 1);
		return;
	break;

	case 'status':
		$result = gitCommand('status', array('-s'), false, false);
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
		$result = gitCommand('config', array('-l'), false, false);
		if($result['success']){
			$git['config'] = $result['output'];
		} else {
			$git['config'] = 'Error: ' . $result['error'];
			$git['error'] = 'Failed to get config';
		}
		setView('git_config', 1);
		return;
	break;

	case 'diff':
		if(!isset($_REQUEST['file'])){
			$git['error'] = 'No file specified';
			setView('git_details', 1);
			return;
		}

		$file = base64_decode($_REQUEST['file']);

		// Validate file path
		if(!gitValidateFilePath($file)){
			$git['error'] = 'Invalid file path';
			gitLog("Invalid file path in diff operation: {$file}", 'warning');
			setView('git_details', 1);
			return;
		}

		// Use --unified=5 for reasonable context, faster than full diff
		$result = gitCommand('diff', array('--unified=5', $file), true, false);
		$recs = array();

		if($result['success']){
			foreach($result['output'] as $line){
				$row = array();
				if(preg_match('/^\+/', $line)){
					$row['class'] = 'w_ins';
				} elseif(preg_match('/^\-/', $line)){
					$row['class'] = 'w_del';
				} else {
					$row['class'] = '';
				}
				$row['line'] = encodeHtml($line);
				$recs[] = $row;
			}
		} else {
			$git['error'] = 'Failed to get diff: ' . $result['error'];
		}

		setView('git_diff', 1);
		return;
	break;

	case 'log':
		if(!isset($_REQUEST['file'])){
			$git['error'] = 'No file specified';
			setView('git_details', 1);
			return;
		}

		$file = base64_decode($_REQUEST['file']);

		// Validate file path
		if(!gitValidateFilePath($file)){
			$git['error'] = 'Invalid file path';
			gitLog("Invalid file path in log operation: {$file}", 'warning');
			setView('git_details', 1);
			return;
		}

		// Use --oneline for faster, more compact output
		$result = gitCommand('log', array('--max-count=10', '--oneline', '--date=relative', $file), true, false);
		if($result['success']){
			$recs = $result['output'];
		} else {
			$recs = array('Error: ' . $result['error']);
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
