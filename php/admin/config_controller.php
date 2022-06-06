<?php
/*
	User authentication: 
		Method: ldap
			host
			domain
			checkmemberof
			admins
		Method: OKTA
			okta_client_id="0oa51shizpAKNJdjS5d7"
        	okta_client_secret="tS3s7hN66E8b-vzfkMKenqds5AUju4fcjqbgvL_9"
        	okta_metadata_url="https://dev-67363231.okta.com/oauth2/default/.well-known/openid-configuration"
        	okta_redirect_uri="https://localhost/"
        	okta_restrict_auto_login_duration="30"
        	ldap_username="serviceterra"
        	ldap_password="0nG@dm1ns!"
        	ldap_host="ldap.corp.doterra.net"
        	ldap_domain="corp.doterra.net"
        	ldap_secure="0"
        	ldap_checkmemberof="0"
        	ldap_basedn="DC=corp,DC=doterra,DC=net"
        	admins
        login_title
        userlog

	Sendmail authentication
		Method: aws
		Method: smtp
			user
			pass
			port
		from
		encrypt
		phpmailer
	file conversions
		convert
		convert_command
		reencode
		reencode_command	
*/
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
