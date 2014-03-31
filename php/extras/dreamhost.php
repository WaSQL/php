<?php
/* DreamHost.com API functions:
	- Reference: http://wiki.dreamhost.com/API
	- the only param that all functions require is 'key'
	 - you may also pass in unique_id to insure you do not submit the same request twice
*/
//---------- begin function dreamhostListAccessibleCmds --------------------
/**
* @describe Lists all the promo codes you have created.
* @link http://wiki.dreamhost.com/API/Rewards_commands#rewards-list_promo_codes
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
*	[showpasswords]  boolean - show passwords for each user account
* @return array - Returns the following fields for each result
*	args
*	cmd
*	optargs - optional args
*	order array - array of fields returned by this command
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostListAccessibleCmds(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostListAccessibleCmds($params=array()){
	//Dump a list of all commands this API Key has access to.
	$params['cmd']='api-list_accessible_cmds';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostMailFilters --------------------
/**
* @describe list of all e-mail filter rules for all users on all accounts you have access to.
* @link http://wiki.dreamhost.com/API/Mail_commands#mail-list_filters
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
*	[showpasswords]  boolean - show passwords for each user account
* @return array - Returns the following fields for each result
*	account_id
*	action - or, move, delete, demime
*	address - the full email address to which you want to add the filter.
*	filter_on - subject, from, to, cc, body, reply-to, headers.
*	filter - what to filter for (case sensitive).
*	action - move,forward,delete,add_subject,forward_shell, and, or.
*	[action_value] - the parameter for the action (optional if action is delete, and, or).
*	[contains] - yes or no. Default is yes.
*	[stop] - yes or no (optional, default is yes. note: must be yes if action is delete).
*	[rank] - the rank of the filter, indexes from 0, lower means executed first (optional, default is the number of filters for the address).
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostMailFilters(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostMailFilters($params=array()){
	$params['cmd']='mail-list_filters';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostAddFilter --------------------
/**
* @describe Adds a new mail filter to an email address you have with dreamhost.
* @link http://wiki.dreamhost.com/API/Mail_commands#mail-add_filter
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
*	[showpasswords]  boolean - show passwords for each user account
*	address - the full email address to which you want to add the filter.
*	filter_on - subject, from, to, cc, body, reply-to, headers.
*	filter - what to filter for (case sensitive).
*	action - move,forward,delete,add_subject,forward_shell, and, or.
*	[action_value] - the parameter for the action (optional if action is delete, and, or).
*	[contains] - yes or no. Default is yes.
*	[stop] - yes or no (optional, default is yes. note: must be yes if action is delete).
*	[rank] - the rank of the filter, indexes from 0, lower means executed first (optional, default is the number of filters for the address).
* @return string
*	success or error msg
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostAddFilter(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostAddFilter($params=array()){
	$params['cmd']='mail-add_filter';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostRemoveFilter --------------------
/**
* @describe Remove a mail filter from an email address you have with dreamhost.
* @link http://wiki.dreamhost.com/API/Mail_commands#mail-add_filter
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
*	[showpasswords]  boolean - show passwords for each user account
*	address - the full email address to which you want to remove the filter.
*	filter_on - subject, from, to, cc, body, reply-to, headers.
*	filter - what to filter for (case sensitive).
*	action - move,forward,delete,add_subject,forward_shell.
*	[action_value] - the parameter for the action (optional if action is delete, and, or).
*	[contains] - yes or no. Default is yes.
*	[stop] - yes or no (optional, default is yes. note: must be yes if action is delete).
*	[rank] - the rank of the filter, indexes from 0, lower means executed first (optional, default is the number of filters for the address).
* @return string
*	success or error msg
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostRemoveFilter(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostRemoveFilter($params=array()){
	$params['cmd']='mail-remove_filter';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostGetPromoCodes --------------------
/**
* @describe Lists all the promo codes you have created.
* @link http://wiki.dreamhost.com/API/Rewards_commands#rewards-list_promo_codes
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
*	[showpasswords]  boolean - show passwords for each user account
* @return array - Returns the following fields for each result
*	code - code name
*	created - YYYY-MM-DD MH:MM:SS
*	description
*	status - active, inactive
*	used - number of times used
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostGetPromoCodes(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostGetPromoCodes($params=array()){
	$params['cmd']='rewards-list_promo_codes';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostGetUsers --------------------
/**
* @describe list of all users (including ftp, shell, anonymous ftp, backup, and mailboxes) on all accounts you have access to.
* @link http://wiki.dreamhost.com/API/Account_commands#account-domain_usage
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
*	[showpasswords]  boolean - show passwords for each user account
* @return array - Returns the following fields for each result
*	account_id
*	username - user name
*	type - ftp, sftp, shell, mail, or backup
*	shell
*	home
*	password
*	disk_used_mb
*	quata_mb
*	gecos
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostGetUsers(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostGetUsers($params=array()){
	$params['cmd']='user-list_users_no_pw';
	if($params['showpasswords']){$params['cmd']='user-list_users';}
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostAccountUsage --------------------
/**
* @describe calls the dreamhost API and returns account usage information
* @link http://wiki.dreamhost.com/API/Account_commands#account-domain_usage
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
* @return array - Returns the following fields for each result
*	bw - Bandwidth usage is in bytes since the beginning of the current billing cycle
*	domain - domain name
*	type - http
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostAccountUsage(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostAccountUsage($params=array()){
	$params['cmd']='account-domain_usage';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostAccountStatus --------------------
/**
* @describe calls the dreamhost API and returns account information
* @link http://wiki.dreamhost.com/API/Account_commands#account-status
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
* @return array - Returns the following fields for each result
*	balance - current account balance
*	delinquent - 0 or 1
*	delinquent_date - YYYY-MM-DD
*	due - amount currently due
*	lastrebill_date - YYYY-MM-DD
*	nextrebill_date - YYYY-MM-DD
*	past_due - amount past due
*	pastdue_date - YYYY-MM-DD
*	today - YYYY-MM-DD
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostAccountStatus(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostAccountStatus($params=array()){
	$params['cmd']='account-status';
	$recs = dreamhostAPI($params);
	if(isset($recs[0]['key'])){
		$status=array();
		foreach($recs as $rec){
        	$key=$rec['key'];
        	$status[$key]=$rec['value'];
		}
		return $status;
	}
	return $recs;
}
//---------- begin function dreamhostGetDomains --------------------
/**
* @describe calls the dreamhost API and returns results as an array
* @link http://wiki.dreamhost.com/API
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
* @return array - Returns the following fields for each result
*	account_id
*   domain - name of the domain or subdomain
*    fastcgi - 1 or 0
*    home - server this domain resides on
*    hosting_type - cgi, full,redirect,parked,mirror,cloaked,google
*    passenger - 1 or 0
*    path - path of the domain or subdomain
*    php - pcgi4, pcgi5, mod_php
*    php_fcgid - 1 or 0
*    security - 1 or 0
*    type - http, https
*    unique_ip
*    user - user the domain runs under
*    www_or_not - add_www, remove_www, both_work
*    xcache - 1 or 0
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostGetDomains(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostGetDomains($params=array()){
	$params['cmd']='domain-list_domains';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostGetRegistrations --------------------
/**
* @describe calls the dreamhost API and returns registrations information
* @link http://wiki.dreamhost.com/API
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
* @return array - Returns the following fields for each result
*	account_id
*	domain
*	expires
*	created
*	modified
*	autorenew - yes, no, ask
*	locked - yes, no
*	expired	- yes, no
*	ns1
*	ns2
*	ns3
*	ns4
*	registrant
*	registrant_org
*	registrant_street1
*	registrant_street2
*	registrant_city
*	registrant_state
*	registrant_zip
*	registrant_country
*	registrant_phone
*	registrant_fax
*	registrant_email
*	tech
*	tech_org
*	tech_street1
*	tech_street2
*	tech_city
*	tech_state
*	tech_zip
*	tech_country
*	tech_phone
*	tech_fax
*	tech_email
*	billing
*	billing_org
*	billing_street1
*	billing_street2
*	billing_city
*	billing_state
*	billing_zip
*	billing_country
*	billing_phone
*	billing_fax
*	billing_email
*	admin
*	admin_org
*	admin_street1
*	admin_street2
*	admin_city
*	admin_state
*	admin_zip
*	admin_country
*	admin_phone
*	admin_fax
*	admin_email
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostGetRegistrations(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostGetRegistrations($params=array()){
	$params['cmd']='domain-list_registrations';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostGetPS --------------------
/**
* @describe calls the dreamhost API and returns PS information
* @link http://wiki.dreamhost.com/API/Dreamhost_ps_commands#dreamhost_ps-list_ps
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
* @return array - Returns the following fields for each result
*	account_id
*	ps
*	description
*	status
*	type
*	memory_mb
*	start_date
*	ip
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostGetPS(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostGetPS($params=array()){
	$params['cmd']='dreamhost_ps-list_ps';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostGetDatabases --------------------
/**
* @describe calls the dreamhost API and returns databases information
* @link http://wiki.dreamhost.com/API
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
* @return array - Returns the following fields for each result
*	account_id
*	db
*	description
*	disk_usage_mb
*	home
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostGetDatabases(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostGetDatabases($params=array()){
	$params['cmd']='mysql-list_dbs';
	return dreamhostAPI($params);
}
//---------- begin function dreamhostGetDatabaseHosts --------------------
/**
* @describe calls the dreamhost API and returns database hosts information
* @link http://wiki.dreamhost.com/API
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
* @return array - Returns the following fields for each result
*	account_id
*	domain
*	home
* @usage
*	<?php
*		loadExtras('dreamhost');
*		$key='YOURDREAMHOSTAPIKEY';
*		$rtn=dreamhostGetDatabaseHosts(array('key'=>$key));
*		echo "Return".printValue($rtn);
*		exit;
*	?>
*/
function dreamhostGetDatabaseHosts($params=array()){
	$params['cmd']='mysql-list_hostnames';
	return dreamhostAPI($params);
	}
//---------- begin function dreamhostAPI --------------------
/**
* @describe calls the dreamhost API and returns results as an array
* @link http://wiki.dreamhost.com/API
* @param params array
*	key string - and API key that you need to generate in the web panel: http://panel.dreamhost.com
*	cmd string - command to execute - the key must have rights to execute this command
*	[unique_id] string - unique id to insure you do not submit the same one twice
*	[format} string - tab, xml, json, perl, php, yaml, html - defaults to json
*	[account] string - account number to perform cmd on. Defaults to your account.
* @return array
* @usage $rtn=dreamhostAPI($params); - returns March
*/
function dreamhostAPI($params=array()){
	//cmd and key are required
	if(!isset($params['format'])){$params['format']='json';}
	$url='https://api.dreamhost.com';
	//turn off ssl check in case cert fails
	$params['-ssl']=false;
	$post=postURL($url,$params);
	//echo printValue($post);
	switch(strtolower($params['format'])){
		case 'json':
			$data=json_decode($post['body']);
			break;
		case 'xml':
			$data = dreamhostSimpleXML($post['body']);
			break;
    	}
    if(isset($data->data[0])){
    	return dreamhost2Array($data);
		}
	else{return $data;}
	}
//---------- begin function dreamhost2Array
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function dreamhost2Array($data){
	$recs=array();
	foreach($data->data as $obj){
		$rec=array();
		foreach($obj as $fld=>$val){
			if(!is_array($val)){
				$fld=strtolower((string)$fld);
				$val=(string)$val;
			}
			$rec[$fld]=$val;
        }
        ksort($rec);
        $recs[]=$rec;
    }
    return $recs;
}
//---------- begin function dreamhostSimpleXML
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function dreamhostSimpleXML($data){
	try {
		$xml= new SimpleXmlElement($data);
		return $xml;
		}
	catch (Exception $e){
        return $e->faultstring;
        }
    return 'simpleXML failed';
	}
?>