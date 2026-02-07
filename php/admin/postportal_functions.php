<?php
/**
 * PostPortal Utility Functions for WaSQL
 *
 * Helper functions for the postportal controller
 *
 * @package    WaSQL
 * @subpackage Utilities
 * @version    1.0
 * @author     WaSQL Development Team
 */

function postportalFormField($name){
	switch(strtolower($name)){
		case 'url':
			$params=array(
				'class'=>'wacss_input is-mobile-responsive',
				'placeholder'=>'Enter request URL (e.g., https://api.example.com/users)',
				'required'=>1
			);
			return buildFormText($name,$params);
		break;
		case 'method':
			$opts=array(
				'GET'=>'GET',
				'POST'=>'POST',
				'PUT'=>'PUT',
				'DELETE'=>'DELETE',
				'PATCH'=>'PATCH',
				'OPTIONS'=>'OPTIONS'
			);
			$params=array(
				'class'=>'wacss_select is-mobile-responsive'
			);
			return buildFormSelect($name,$opts,$params);
		break;
		case 'auth_type':
			$opts=array(
				'none'=>'No Auth',
				'basic'=>'Basic Auth',
				'bearer'=>'Bearer Token',
			);
			$params=array(
				'class'=>'wacss_select is-mobile-responsive'
			);
			return buildFormSelect($name,$opts,$params);
		break;
		case 'auth_username':
			$params=array(
				'class'=>'wacss_input is-mobile-responsive',
				'requiredif'=>'auth_type:basic'
			);
			return buildFormText($name,$params);
		break;
		case 'auth_password':
			$params=array(
				'class'=>'wacss_input is-mobile-responsive',
				'requiredif'=>'auth_type:basic'
			);
			return buildFormPassword($name,$params);
		break;
		case 'auth_token':
			$params=array(
				'class'=>'wacss_input is-mobile-responsive',
				'requiredif'=>'auth_type:bearer',
				'placeholder'=>'Enter bearer token'
			);
			return buildFormText($name,$params);
		break;
		case 'headers':
			$params=array(
				'class'=>'wacss_textarea is-mobile-responsive',
				'rows'=>10,
				'placeholder'=>'Content-Type: application/json&#10;Accept: application/json'
			);
			return buildFormTextarea($name,$params);
		break;
		case 'body':
			$params=array(
				'class'=>'wacss_textarea is-mobile-responsive',
				'rows'=>15,
				'placeholder'=>'{"name": "value"}'
			);
			return buildFormTextarea($name,$params);
		break;
		default:
			return "ERROR: {$name} field not defined";
		break;
	}
}

/**
 * Sends an HTTP request using cURL
 * @param string $url URL to send request to
 * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
 * @param array $headers Array of headers (name => value)
 * @param string $body Request body content
 * @return array Response array with status, headers, body, and error info
 * @since 1.0
 */
function postportalSendRequest($url, $method = 'GET', $headers = array(), $body = ''){
	// Initialize response array
	$response = array(
		'status' => 0,
		'status_text' => '',
		'headers' => array(),
		'body' => '',
		'error' => '',
		'info' => array()
	);
	try {
		// Initialize cURL
		$ch = curl_init();
		// Set URL
		curl_setopt($ch, CURLOPT_URL, $url);
		// Set method
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		// Set headers
		$header_array = array();
		foreach($headers as $header){
			$header_array[] = "{$header['name']}: {$header['value']}";
		}
		if(count($header_array) > 0){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
		}
		// Set body for POST, PUT, PATCH
		if(in_array($method, array('POST', 'PUT', 'PATCH')) && strlen($body) > 0){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}
		// Return response as string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Include headers in output
		curl_setopt($ch, CURLOPT_HEADER, true);
		// Follow redirects
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		// Set timeout (30 seconds)
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		// Set maximum response size (50MB) to prevent memory exhaustion
		curl_setopt($ch, CURLOPT_MAXFILESIZE, 52428800);
		// SSL Certificate Verification
		// SECURITY NOTE: For production, you should verify SSL certificates
		// Set CURLOPT_SSL_VERIFYPEER to true and provide a CA bundle path
		// Currently disabled to allow testing with self-signed certificates
		$verify_ssl = false; // Set to true in production environments
		if($verify_ssl){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			// Optionally set CA bundle: curl_setopt($ch, CURLOPT_CAINFO, '/path/to/cacert.pem');
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		// Execute request
		$result = curl_exec($ch);
		// Get request info
		$info = curl_getinfo($ch);
		$response['info'] = $info;
		// Check for errors
		if(curl_errno($ch)){
			$error_code = curl_errno($ch);
			$error_msg = curl_error($ch);
			$response['error'] = "cURL Error ({$error_code}): {$error_msg}";
		} else {
			// Parse headers and body
			$header_size = $info['header_size'];
			$header_text = substr($result, 0, $header_size);
			$response['body'] = substr($result, $header_size);
			// Parse status
			$response['status'] = $info['http_code'];
			$response['status_text'] = postportalGetStatusText($info['http_code']);
			// Parse headers
			$header_lines = explode("\r\n", $header_text);
			foreach($header_lines as $line){
				if(strpos($line, ':') !== false){
					list($name, $value) = explode(':', $line, 2);
					$response['headers'][]=array('name'=>trim($name),'value'=> trim($value));
				}
			}
		}
		// Close cURL handle
		curl_close($ch);
	} catch(Exception $e){
		$response['error'] = $e->getMessage();
	}
	return $response;
}

/**
 * Gets HTTP status text for a given status code
 * @param int $code HTTP status code
 * @return string Status text
 * @since 1.0
 */
function postportalGetStatusText($code){
	$status_codes = array(
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		204 => 'No Content',
		301 => 'Moved Permanently',
		302 => 'Found',
		304 => 'Not Modified',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		408 => 'Request Timeout',
		429 => 'Too Many Requests',
		500 => 'Internal Server Error',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout'
	);
	return isset($status_codes[$code]) ? $status_codes[$code] : 'Unknown';
}
/**
 * Formats response body based on content type
 * @param string $body Response body
 * @param array $headers Response headers
 * @return string Formatted body HTML
 * @since 1.0
 */
function postportalFormatResponse($body, $headers){
	// Detect content type
	$content_type = '';
	foreach($headers as $header){
		if(strtolower($header['name']) == 'content-type'){
			$content_type = strtolower($header['value']);
			break;
		}
	}
	// Format based on content type
	if(strpos($content_type, 'application/json') !== false){
		// Pretty print JSON with error handling
		$json = json_decode($body);
		if(json_last_error() === JSON_ERROR_NONE){
			return '<pre class="w_code">' . encodeHtml(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
		} else {
			// JSON parsing failed, show error and raw content
			$error_msg = json_last_error_msg();
			return '<div class="alert alert-warning"><strong>JSON Parse Error:</strong> ' . encodeHtml($error_msg) . '</div>' .
			       '<pre class="w_code">' . encodeHtml($body) . '</pre>';
		}
	} elseif(strpos($content_type, 'text/html') !== false){
		// Show HTML as rendered and as code
		return '<pre class="w_code">' . encodeHtml($body) . '</pre>';
	} elseif(strpos($content_type, 'text/xml') !== false || strpos($content_type, 'application/xml') !== false){
		// Pretty print XML with error handling
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($body);
		if($xml !== false){
			$dom = dom_import_simplexml($xml)->ownerDocument;
			$dom->formatOutput = true;
			libxml_clear_errors();
			return '<pre class="w_code">' . encodeHtml($dom->saveXML()) . '</pre>';
		} else {
			// XML parsing failed, show errors
			$errors = libxml_get_errors();
			libxml_clear_errors();
			// Fall through to show as plain text
		}
	}
	// Default: show as plain text
	return '<pre class="w_code">' . encodeHtml($body) . '</pre>';
}

function postportalHistoryList(){
	$recs=postportalGetHistory();
	foreach($recs as $i=>$rec){
		//actions
		$recs[$i]['action']=<<<ENDOFACTIONS
<div style="display:flex;justify-content:flex-end;gap:20px;">
	<span class="icon-forward w_bigger w_success w_pointer" title="Load into requests" data-div="main_content" data-_menu="postportal" data-func="request" data-hid="{$rec['id']}" data-nav="/php/admin.php" onclick="wacss.setActiveTab('postportal_nav_requests');return wacss.nav(this);"></span>
	<span class="icon-close w_danger w_pointer" title="Delete / Remove" data-div="history_content" data-_menu="postportal" data-func="history_delete" data-id="{$rec['id']}" data-nav="/php/admin.php" data-confirm="Delete?" onclick="return wacss.nav(this);"></span>
</div>
ENDOFACTIONS;
	}
	return databaseListRecords(array(
		'-list'=>$recs,
		'-tableclass'=>'wacss_table bordered striped',
		'-listfields'=>'id,timestamp,url,method,duration,action'
	));
}

/**
 * Saves request to history
 * @param string $url Request URL
 * @param string $method HTTP method
 * @param array $headers Request headers
 * @param string $body Request body
 * @param array $response Response data
 * @param float $duration Request duration in milliseconds
 * @return bool Success status
 * @since 1.0
 */
function postportalSaveHistory($request_data){
	global $USER;
	if(!array_key_exists('_postportal_ex', $USER)){
		$USER['_postportal_ex']=array('history'=>array());
	}
	if(!isset($USER['_postportal_ex']['history'])){
		$USER['_postportal_ex']['history']=array();
	}
	$id=uniqid();
	$request_data['id']=$id;
	$request_data['timestamp']=date('Y-m-d H:i:s');
	$USER['_postportal_ex']['history'][$id]=$request_data;
	// Only store the last 15 requests in history to prevent database bloat
	// Adjust this limit based on your needs
	if(count($USER['_postportal_ex']['history']) > 15){
		$USER['_postportal_ex']['history']=sortArrayByKeys($USER['_postportal_ex']['history'],array('timestamp'=>SORT_ASC));
		while(count($USER['_postportal_ex']['history']) > 15){
			$pop=array_shift($USER['_postportal_ex']['history']);
		}
	}
	$ok=editDBRecordById('_users',$USER['_id'],array(
		'_postportal'=>$USER['_postportal_ex']
	));
	if(!is_numeric($ok)){
		// Database update failed
		return false;
	}
	return true;
}

/**
 * Gets request history for current user
 * @param int $limit Number of records to return (default: 50)
 * @return array History records
 * @since 1.0
 */
function postportalGetHistory($limit = 200){
	global $USER;
	if(!array_key_exists('_postportal_ex', $USER)){
		$USER['_postportal_ex']=array('history'=>array());
	}
	if(!isset($USER['_postportal_ex']['history'])){
		$USER['_postportal_ex']['history']=array();
	}
	$recs=sortArrayByKeys($USER['_postportal_ex']['history'],array('timestamp'=>SORT_DESC));
	return $recs;
}


/**
 * Clears request history for current user
 * @return bool Success status
 * @since 1.0
 */
function postportalClearHistory(){
	global $USER;
	if(!array_key_exists('_postportal_ex', $USER)){
		$USER['_postportal_ex']=array('history'=>array());
	}
	$USER['_postportal_ex']['history']=array();
	$ok=editDBRecordById('_users',$USER['_id'],array(
		'_postportal'=>$USER['_postportal_ex']
	));
	if(!is_numeric($ok)){
		// Database update failed
		return false;
	}
	return true;
}
function postportalDeleteHistory($id){
	global $USER;
	if(!array_key_exists('_postportal_ex', $USER)){
		$USER['_postportal_ex']=array('history'=>array());
	}
	if(!isset($USER['_postportal_ex']['history'])){
		$USER['_postportal_ex']['history']=array();
	}
	unset($USER['_postportal_ex']['history'][$id]);
	$ok=editDBRecordById('_users',$USER['_id'],array(
		'_postportal'=>$USER['_postportal_ex']
	));
	if(!is_numeric($ok)){
		// Database update failed
		return false;
	}
	return true;
}
function postportalHeadersList($recs){
	return databaseListRecords(array(
		'-list'=>$recs,
		'-tableclass'=>'wacss_table bordered striped'
	));
}

function postportalEnvironmentList(){
	$recs=postportalGetEnvironments();
	foreach($recs as $i=>$rec){
		$recs[$i]['action']=<<<ENDOFDEL
<span class="icon-close w_danger w_pointer" title="Delete / Remove" data-div="environment_content" data-_menu="postportal" data-func="environment_delete" data-env_key="{$rec['env_key']}" data-nav="/php/admin.php" data-confirm="Delete?" onclick="return wacss.nav(this);"></span>
ENDOFDEL;
	}
	return databaseListRecords(array(
		'-list'=>$recs,
		'-tableclass'=>'wacss_table bordered striped'
	));
}
/**
 * Saves an environment variable
 * @param string $key Variable key
 * @param string $value Variable value
 * @return bool Success status
 * @since 1.0
 */
function postportalSaveEnvironment($key, $value){
	global $USER;
	if(!array_key_exists('_postportal_ex', $USER)){
		$USER['_postportal_ex']=array('environment'=>array());
	}
	if(!isset($USER['_postportal_ex']['environment'])){
		$USER['_postportal_ex']['environment']=array();
	}
	$USER['_postportal_ex']['environment'][$key]=$value;
	$ok=editDBRecordById('_users',$USER['_id'],array(
		'_postportal'=>$USER['_postportal_ex']
	));
	if(!is_numeric($ok)){
		// Database update failed
		return false;
	}
	return true;
}

/**
 * Gets environment variables for current user
 * @return array Environment variable records
 * @since 1.0
 */
function postportalGetEnvironments(){
	global $USER;
	if(!array_key_exists('_postportal_ex', $USER)){
		$USER['_postportal_ex']=array('environment'=>array());
	}
	if(!isset($USER['_postportal_ex']['environment'])){
		$USER['_postportal_ex']['environment']=array();
	}
	$recs=array();
	foreach($USER['_postportal_ex']['environment'] as $k=>$v){
		$recs[]=array(
			'env_key'=>$k,
			'env_value'=>$v
		);
	}
	return $recs;
}

/**
 * Deletes an environment variable
 * @param int $id Environment variable record ID
 * @return bool Success status
 * @since 1.0
 */
function postportalDeleteEnvironment($k){
	global $USER;
	if(!array_key_exists('_postportal_ex', $USER)){
		$USER['_postportal_ex']=array('environment'=>array());
	}
	if(!isset($USER['_postportal_ex']['environment'])){
		$USER['_postportal_ex']['environment']=array();
	}
	unset($USER['_postportal_ex']['environment'][$k]);
	$ok=editDBRecordById('_users',$USER['_id'],array(
		'_postportal'=>$USER['_postportal_ex']
	));
	if(!is_numeric($ok)){
		// Database update failed
		return false;
	}
	return true;
}

/**
 * Replaces environment variables in a string
 * Environment variables are defined as {{variable_name}}
 * @param string $string String to process
 * @return string Processed string with variables replaced
 * @since 1.0
 */
function postportalReplaceVariables($string){
	$environments = postportalGetEnvironments();
	foreach($environments as $env){
		$string = str_replace('{{' . $env['env_key'] . '}}', $env['env_value'], $string);
	}
	return $string;
}

/**
 * Format bytes to human readable size
 * @param int $bytes Bytes to format
 * @param int $precision Decimal precision (default: 2)
 * @return string Formatted size (e.g., "1.5 KB")
 * @since 1.0
 */
function postportalFormatBytes($bytes, $precision = 2){
	if(is_string($bytes)){$bytes=strlen($bytes);}
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes /= pow(1024, $pow);
	return round($bytes, $precision) . ' ' . $units[$pow];
}

?>
