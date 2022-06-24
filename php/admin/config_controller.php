<?php
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(!isset($CONFIG['admin_color']) || !strlen($CONFIG['admin_color'])){
		$CONFIG['admin_color']='w_gray';
	}
	switch(strtolower($_REQUEST['func'])){
		case 'config_users':
		case 'config_sync':
		case 'config_mail':
		case 'config_uploads':
		case 'config_misc':
			setView($_REQUEST['func'],1);
			return;
		break;
		case 'config_users_wasql':
		case 'config_users_ldap':
		case 'config_users_okta':
		case 'config_users_okta_ldap':
		case 'config_sync_form':
		case 'config_mail_form':
		case 'config_uploads_form':
		case 'config_misc_form':
			switch(strtolower($_REQUEST['process'])){
				case 'save':
					$ok=configSave();
					$_SESSION=array();
					setView('config_users_save',1);
					return;
				break;
			}
			setView($_REQUEST['func'],1);
			return;
		break;
		default:
			$ok=configCheckSchema();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
