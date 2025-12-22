<?php
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(!isset($CONFIG['admin_color']) || !strlen($CONFIG['admin_color'])){
		$CONFIG['admin_color']='w_gray';
	}
	switch(strtolower($_REQUEST['func'])){
		case 'config_logs_view_file':
			$afile=$_REQUEST['file'];
			// Security: Validate file path to prevent path traversal attacks
			$isAllowed=false;
			if(file_exists($afile)){
				$realPath=realpath($afile);
				// Define allowed log directories (common locations)
				$allowedDirs=array(
					'/var/log',
					'C:\\Windows\\Logs',
					'C:\\xampp\\apache\\logs',
					'C:\\xampp\\mysql\\data',
					'/usr/local/var/log',
					'/var/lib/mysql',
					'/opt/homebrew/var/log'
				);
				// Check if file is within allowed directories
				foreach($allowedDirs as $allowedDir){
					if(is_dir($allowedDir)){
						$allowedPath=realpath($allowedDir);
						if($allowedPath && strpos($realPath,$allowedPath)===0){
							$isAllowed=true;
							break;
						}
					}
				}
			}
			if($isAllowed && file_exists($afile)){
				$content=tailFile($afile,200);
				$lines=preg_split('/[\r\n]/',$content);
				$filesize=verboseSize(filesize($afile));
				$title=$afile." ({$filesize})";
				foreach($lines as &$line){
					if(stringContains($line,'error]')){
						$line='<div class="w_danger">'.$line.'</div>';
					}
					elseif(stringContains($line,'warning]')){
						$line='<div class="w_orange">'.$line.'</div>';
					}
					elseif(stringContains($line,'notice]')){
						$line='<div class="w_info">'.$line.'</div>';
					}
					else{
						$line='<div class="w_gray">'.$line.'</div>';
					}
				}
				$content=implode(PHP_EOL,$lines);
			}
			else{
				$title='Access Denied';
				$content='<div class="w_danger">Access Denied: File path not authorized or file does not exist.</div>';
			}
			setView('config_logs_view_file',1);
			return;
		break;
		case 'config_users':
		case 'config_sync':
		case 'config_crons':
		case 'config_mail':
		case 'config_uploads':
		case 'config_misc':
		case 'config_database':
		case 'config_logs':
			setView($_REQUEST['func'],1);
			return;
		break;
		case 'config_users_okta':
		case 'config_users_okta_ldap':
			switch(strtolower($_REQUEST['process'])){
				case 'save':
					$ok=configSave();
					$_SESSION=array();
					if(isset($_REQUEST['okta_auth_method']) && strtolower($_REQUEST['okta_auth_method']) == 'saml'){
						$ok=configOktaSAMLWriteConfig();
					}
					setView('config_users_save',1);
					return;
					break;
				default:
					// Continue evaluating parent switch cases
			}
		case 'config_users_wasql':
		case 'config_users_ldap':
			switch(strtolower($_REQUEST['process'])){
				case 'save':
					$ok=configSave();
					// Clear session when auth settings change to force re-authentication
					$_SESSION=array();
					setView('config_users_save',1);
					return;
					break;
				default:
			}
			setView($_REQUEST['func'],1);
			return;
		break;
		case 'config_sync_form':
		case 'config_mail_form':
		case 'config_uploads_form':
		case 'config_misc_form':
		case 'config_database_form':
		case 'config_logs_form':
			switch(strtolower($_REQUEST['process'])){
				case 'save':
					$ok=configSave();
					// No session clear needed for non-auth config changes
					setView('config_users_save',1);
					return;
					break;
				default:
			}
			setView($_REQUEST['func'],1);
			return;
		break;
		default:
			$ok=configCheckSchema();
			$_REQUEST['submenu']='none';
			setView('default',1);
		break;
	}
	setView('default',1);
?>
