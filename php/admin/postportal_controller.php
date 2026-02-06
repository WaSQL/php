<?php
/**
 * PostPortal - Postman-like API Testing Controller for WaSQL
 *
 * Provides API testing functionality similar to Postman:
 * - HTTP request builder (GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS)
 * - Request history and saved requests
 * - Headers and authentication management
 * - Response visualization (JSON, XML, HTML, text)
 * - Environment variables
 *
 * Security Features:
 * - Admin-only access (isAdmin() check)
 * - Input validation and sanitization
 * - Request logging for audit trail
 * - CSRF protection via WaSQL framework
 *
 * @package    WaSQL
 * @subpackage Utilities
 * @version    1.0
 * @author     WaSQL Development Team
 */

// Authentication check - require admin user
global $USER;
if(!isAdmin()){
	setView('not_authenticated', 1);
	return;
}
//check for postportal in USER table. It may by null for !isset will not work
if(!array_key_exists('_postportal', $USER)){
	$USER['_postportal']=array();
	$ok=executeSQL("ALTER TABLE _users ADD _postportal JSON");
}
else{
	$USER['_postportal_ex']=decodeJSON($USER['_postportal']);
}

// Initialize func parameter
if(!isset($_REQUEST['func'])){
	$_REQUEST['func'] = '';
}

$func = strtolower(trim($_REQUEST['func']));
switch($func){
	case 'request':
		$hid='';
		$request_data=array();
		//check to see if we need to load a history id (hid)
		if(isset($_REQUEST['hid']) && strlen($_REQUEST['hid'])){
			$hid=trim($_REQUEST['hid']);
			if(isset($USER['_postportal_ex']['history'][$hid])){
				//url
				$_REQUEST['url']=$USER['_postportal_ex']['history'][$hid]['url'];
				//method
				$_REQUEST['method']=$USER['_postportal_ex']['history'][$hid]['method'];
				//body
				$_REQUEST['body']=$USER['_postportal_ex']['history'][$hid]['request_body'];
				//headers
				if(isset($USER['_postportal_ex']['history'][$hid]['request_headers'][0])){
					$headers=array();
					foreach($USER['_postportal_ex']['history'][$hid]['request_headers'] as $header){
						$headers[]="{$header['name']}: {$header['value']}";
					}
					$_REQUEST['headers']=implode(PHP_EOL,$headers);
				}
				$request_data=$USER['_postportal_ex']['history'][$hid];
			}
		}
		setView($func,1);
		return;
	break;
	case 'request_send':
		// Validate required fields
		if(!isset($_REQUEST['url']) || strlen(trim($_REQUEST['url'])) == 0){
			$error = 'URL is required';
			setView('error', 1);
			return;
		}
		// Validate URL format
		$url = trim($_REQUEST['url']);
		if(!filter_var($url, FILTER_VALIDATE_URL)){
			$error = 'Invalid URL format';
			setView('error', 1);
			return;
		}
		// Get request method
		$method = isset($_REQUEST['method']) ? strtoupper(trim($_REQUEST['method'])) : 'GET';
		$allowed_methods = array('GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS');
		if(!in_array($method, $allowed_methods)){
			$error = 'Invalid HTTP method';
			setView('error', 1);
			return;
		}
		// Parse headers
		$request_headers = array();
		if(isset($_REQUEST['headers']) && strlen(trim($_REQUEST['headers'])) > 0){
			$header_lines = preg_split('/[\r\n]+/', trim($_REQUEST['headers']));
			foreach($header_lines as $line){
				if(strpos($line, ':') !== false){
					list($name, $value) = explode(':', $line, 2);
					$request_headers[] = array('name'=>trim($name),'value'=>trim($value));
				}
			}
		}
		// Get request body
		$request_body = isset($_REQUEST['body']) ? $_REQUEST['body'] : '';
		// Get auth settings
		$auth_type = isset($_REQUEST['auth_type']) ? $_REQUEST['auth_type'] : 'none';
		if($auth_type == 'basic' && isset($_REQUEST['auth_username']) && isset($_REQUEST['auth_password'])){
			$request_headers[]=array(
				'name'=>'Authorization',
				'value' => 'Basic ' . base64_encode($_REQUEST['auth_username'] . ':' . $_REQUEST['auth_password'])
			);
		} elseif($auth_type == 'bearer' && isset($_REQUEST['auth_token'])){
			$request_headers[]=array(
				'name'=>'Authorization',
				'value' => 'Bearer ' . trim($_REQUEST['auth_token'])
			);
		}
		// Make the request
		$start_time = microtime(true);
		$response = postportalSendRequest($url, $method, $request_headers, $request_body);
		$duration = round((microtime(true) - $start_time) * 1000, 2);

		// Format response for display
		$request_data = array(
			'url' => $url,
			'method' => $method,
			'request_headers' => $request_headers,
			'request_body'=>$request_body,
			'response'=>$response,
			'duration'=>$duration
		);
		// Save to history?
		if(isset($_REQUEST['save_history']) && $_REQUEST['save_history'] == '1'){
			postportalSaveHistory($request_data);
		}

		//$request_data['response']['body']='...';echo printValue($request_data);exit;
		setView('request_response', 1);
		return;
	break;

	case 'history':
		setView($func,1);
		return;
	break;
	case 'history_clear':
		$result = postportalClearHistory();
		setView('history_list', 1);
		return;
	break;
	case 'history_delete':
		if(!isset($_REQUEST['id'])){
			$error = 'Invalid history id';
			setView('error', 1);
			return;
		}
		$result = postportalDeleteHistory($_REQUEST['id']);
		setView('history_list', 1);
		return;
	break;
	case 'environment':
		setView($func,1);
		return;
	break;
	case 'environment_save':
		if(!isset($_REQUEST['env_key']) || strlen(trim($_REQUEST['env_key'])) == 0){
			$error = 'Environment key is required';
			setView('error', 1);
			return;
		}

		if(!isset($_REQUEST['env_value']) || strlen(trim($_REQUEST['env_value'])) == 0){
			$error = 'Environment value is required';
			setView('error', 1);
			return;
		}

		$result = postportalSaveEnvironment(trim($_REQUEST['env_key']), trim($_REQUEST['env_value']));
		setView('environment_list', 1);
		return;
	break;

	case 'environment_delete':
		if(!isset($_REQUEST['env_key'])){
			$error = 'Invalid environment Key';
			setView('error', 1);
			return;
		}
		$result = postportalDeleteEnvironment($_REQUEST['env_key']);
		setView('environment_list', 1);
		return;
	break;

	default:
		// Initial page load - show default request builder
		$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
		$method = isset($_REQUEST['method']) ? $_REQUEST['method'] : 'GET';
		$headers = isset($_REQUEST['headers']) ? $_REQUEST['headers'] : '';
		$body = isset($_REQUEST['body']) ? $_REQUEST['body'] : '';
		$auth_type = isset($_REQUEST['auth_type']) ? $_REQUEST['auth_type'] : 'none';
		$auth_username = isset($_REQUEST['auth_username']) ? $_REQUEST['auth_username'] : '';
		$auth_password = isset($_REQUEST['auth_password']) ? $_REQUEST['auth_password'] : '';
		$auth_token = isset($_REQUEST['auth_token']) ? $_REQUEST['auth_token'] : '';
		setView('default', 1);
		return;
	break;
}

?>
