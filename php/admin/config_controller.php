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
			if(file_exists($afile)){
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
				$title=$afile;
				$content='NO SUCH FILE:';
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
		case 'config_sync_form':
		case 'config_mail_form':
		case 'config_uploads_form':
		case 'config_misc_form':
		case 'config_database_form':
		case 'config_logs_form':
			switch(strtolower($_REQUEST['process'])){
				case 'save':
					$ok=configSave();
					$_SESSION=array();
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
