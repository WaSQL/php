<?php
/********************************
 * Okta for WaSQL
 * A Singleton to negotiate Okta SSO authentication for WaSQL
 ********************************/

class Okta {
	const DEFAULT_DEBUG = false;
	const SIMPLESAMLPHP_CONFIG_WASQL_PATH = 'php/extras/simplesamlphp/config';
	// const SIMPLESAMLPHP_AUTOLOADER_PATH = '../../simplesamlphp/lib/_autoload.php'; // Absolute path: /var/www/simplesamlphp/lib/_autoload.php
	const SIMPLESAMLPHP_AUTOLOADER_WASQL_PATH = 'php/extras/simplesamlphp/lib/_autoload.php';

	private static $instance = null;
	private $error = null;
	private $debug_output = self::DEFAULT_DEBUG;
	private $debug_log = self::DEFAULT_DEBUG;
	private $okta_username = null;
	private $okta_login_url = null;

	public $auth_method;
	public $service_provider_id;
	public $client_id;
	public $client_secret;
	public $metadata_url;
	public $redirect_uri;

	public $restrict_auto_login_duration = 60; // Seconds

	protected function __construct($params, $authenticate) { // Declared as protected to follow Singleton pattern/prevent new instances
		$this->debug_output = $params['debug_output'] ?? self::DEFAULT_DEBUG;
		$this->debug_log = $params['debug_log'] ?? self::DEFAULT_DEBUG;
		$this->debugMessage("Okta::__construct called");
		// Check the settings required for the specified authorization method; if we don't have the right settings, return an exception
		if (!isset($params['auth_method'])) {
			throw new Exception("Error: A value is required for auth_method to instantiate the Okta singleton.");
		}
		// Required settings for SAML
		if (strtolower($params['auth_method']) == 'saml') {
			$required_params = array(
				'service_provider_id',
				'metadata_url',
				'redirect_uri',
			);
		}
		// Required settings for OAuth 2.0
		elseif (strtolower($params['auth_method']) == 'oauth2') {
			$required_params = array(
				'client_id',
				'client_secret',
				'metadata_url',
				'redirect_uri',
			);
		}
		else {
			throw new Exception("Error: An unexpected value for auth_method was encountered. Could not instantiate the Okta singleton.");
		}
		foreach ($required_params as $required_param) {
			if (!isset($params[$required_param])) {
				throw new Exception("Error: A value is required for ${required_param} to instantiate the Okta singleton.");
			}
		}
		// Set the class properties for the specified authorization method
		if (strtolower($params['auth_method']) == 'saml') {
			$this->setSAMLAuthetication($params['service_provider_id'], $params['metadata_url'], $params['redirect_uri']);
			$this->debugMessage("Okta SAML authentication set");
		}
		elseif (strtolower($params['auth_method']) == 'oauth2') {
			$this->setOAuth2Authetication($params['client_id'], $params['client_secret'], $params['metadata_url'], $params['redirect_uri']);
			$this->debugMessage("Okta OAuth 2.0 authentication set");
		}
		else {
			throw new Exception("Error: An unexpected error occurred. Could not instantiate the Okta singleton.");
		}
		// Set optional class properties passed in $params
		// Get a copy of $params
		$opt_params = $params;
		// Unset the required parameters
		unset($opt_params['auth_method']);
		unset($opt_params['service_provider_id']);
		unset($opt_params['client_id']);
		unset($opt_params['client_secret']);
		unset($opt_params['metadata_url']);
		unset($opt_params['redirect_uri']);
		// Iterate the remaining parameters and if a matching class property exists, set it
		foreach ($opt_params as $opt_param_key => $opt_param_value) {
			if (property_exists('Okta', $opt_param_key)) {
				$this->{$opt_param_key} = $opt_param_value;
			}
		}
		$this->debugMessage("Okta singleton instantiated");
		// Automatically authenticate
		if ($authenticate) {
			$this->debugMessage("Okta automatic authentication initiated");
			// $this->handleLoginState(); // OLD; moved logic into authenticateUser()
			$this->authenticateUser();
		}
	}

	protected function __clone() {} // Declared as protected to prevent cloning

	protected function __wakeup() {} //Declared as protected to prevent restore

	/**
	 * Get a single instance of the Okta class
	 * @param array $params An array of values to set for any class properties, using the class property names as the keys.
	 * @param boolean $autheticate A boolean indicating whether authentication should be attempted automatically after instantiating or updating the properties of the singleton.
	 * @return Okta The Okta singleton, or the constructor will throw an exception if the singleton could not be instantiated
	 */
	public static function getInstance($params, $authenticate = false) {
		self::debugMessage("Okta::getInstance called", null, false, true, ($params['debug_output'] ?? self::DEFAULT_DEBUG), ($params['debug_log'] ?? self::DEFAULT_DEBUG));
		// Instantiate the Okta singleton
		if(!isset(self::$instance)) {
			self::$instance = new Okta($params, $authenticate);
		}
		// NOTE: Program flow will arrive here/an Okta class instance will be returned ONLY if not automatically authenticating, if authentication does not redirect to Okta, or if there are no exceptions during instantiation
		return self::$instance;
	}

	/**
	 * Perform one of several interactions with Okta based on request and session variables to/on this site and the presence of an active Okta SSO session elsewhere
	 */
	public function authenticateUser() {
		$this->debugMessage("Okta::authenticateUser called");
		session_start();

		// For debugging
		// $this->debugMessage("\$_REQUEST = ", $_REQUEST);
		// $this->debugMessage("\$_SESSION = ", $_SESSION);

		// Non-standard authentication process state checks
		// For testing allow this site's session to be manually destroyed
		// NOTE: This will automatically login users with an active Okta session when the page reloads
		if (isset($_REQUEST['reset'])) {
			session_destroy();
			unset($_SESSION['okta']);
			$this->debugMessage("After session_destroy, \$_SESSION = ", $_SESSION);
			header('Location: '.$this->redirect_uri);
			die();
		}
		// Unset automatic login restriction if expired
		if (isset($_SESSION['okta']['restrict_auto_login']) && time() > $_SESSION['okta']['restrict_auto_login']) {
			unset($_SESSION['okta']['restrict_auto_login']);
		}
		// Handle Okta states
		// Logging-out
		// TODO: Does execution with _logout ever get here because of the way user.php may process logout before the Okta class is called?
		if (isset($_REQUEST['_logout'])) {
			$this->clearError();
			// Logout by unsetting Okta user session variables and setting "logged-out" session variable
			unset($_SESSION['okta']['username']);
			unset($_SESSION['okta']['sub']);
			$_SESSION['okta']['restrict_auto_login'] = time()+$this->restrict_auto_login_duration;
			// Reload page to complete logout
			header('Location: '.$this->redirect_uri);
			die();
		}

		// Standard Okta authentication process state checks
		// Logged-in user
		if (isset($_SESSION['okta']['sub']) && isset($_SESSION['okta']['username'])) {
			$this->debugMessage("Active Okta session exists for {$_SESSION['okta']['username']}");
			// Set username
			$this->okta_username = $_SESSION['okta']['username'];
			return;
		}
		// Not logged-in; authenticate or get login URL

		// $auth = $this->authenticateUser(); // OLD; replaced with authorization-method-specific functions below
		// Clear any session errors
		if(isset($_SESSION['okta']['error'])){
			$this->clearError();
		}
		if (strtolower($this->auth_method) == 'saml') {
			$auth = $this->authenticateUserSAML();
		}
		elseif (strtolower($this->auth_method) == 'oauth2') {
			$auth = $this->authenticateUserOAuth2();
		}
		else {
			throw new Exception("Error: An unexpected value for auth_method was encountered. Could not initiate Okta authentication.");
		}
		// We do get here if the user is already authenticated and no redirects are necessary
		// QUESTION: Do we get back here after authentication?
		// ANSWER: Yes, we do return here immediately after login, but not subsequently
		$this->debugMessage("Returned to authenticateUser from authentication. Is user authenticated? ".($auth === true ? "Yes" : "No"));
		$this->debugMessage("\$_SESSION['okta'] = ", $_SESSION['okta']);
		$this->debugMessage("Okta username: {$this->okta_username}"); // NOTE: The username class property should NOT be set at this point is using a "reload to complete login" strategy; is SHOULD be set at this point if using a no-reload strategy

		// User authenticated successfully
		if ($auth === true) {
			$this->clearError();
			unset($_SESSION['okta']['restrict_auto_login']);
			// OLD Reload page to complete login

			// If the current URL is different, redirect to the requested URL or redirect URI
			$current_url = isSSL()?'https':'http';
			$current_url .= '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$requested_url = $_SESSION['okta']['redirect_uri'] ?? $this->redirect_uri ?? $current_url;
			unset($_SESSION['okta']['redirect_uri']);
			if ($requested_url != $current_url) {
				$this->debugMessage("Redirecting to {$requested_url}");
				$this->debugMessage("<script type=\"text/javascript\">window.location.href = '".$requested_url."';</script>", null, false, false, false);
				header("Location: {$requested_url}");
				//var_dump($_SESSION['okta']);
				die();
			}
			$this->debugMessage("Already on the requested/redirect URL {$requested_url}");
			return;
		}
		// Login URL returned
		elseif ($auth !== false && is_string($auth)) {
			$this->clearError();
			// Automatically login users with an active Okta session using the login URL UNLESS the user specifically logged-out of this site
			if (!isset($_SESSION['okta']['restrict_auto_login'])) {
				$this->debugMessage("Redirecting to login URL {$this->okta_login_url}");
				$this->debugMessage("<script type=\"text/javascript\">window.location.href = '".$this->okta_login_url."';</script>", null, false, false, false);
				header('Location: '.$this->okta_login_url);
				die();
			}
			// Okta object is now constructed with a login URL for use by renderLoginWidget
			$this->debugMessage("OAuth 2.0 login URL set to {$this->okta_login_url}");
			return;
		}
		// Authentication error occurred
		else {
			$this->debugMessage("An OAuth 2.0 authentication error occurred.");
			// Intentionally blank
		}
		// Okta object is now constructed but in an error state; errors can be accessed using getError, if required
		// renderLoginWidget should present a fatal error
		$this->debugMessage("Unexpected code path in authenticateUser!");
	}

	/**
	 * Perform Okta SSO authentication using SAML
	 * @return true|false True if the user is authenticated; false if an authentication error occurs. TODO: Are there SimpleSAMLphp errors we should expect and catch here? There is currently no case in which this function returns false. The user is either already authenticated, or returns to the site via successful Okta login process which is the same as being already authenticated, OR they do not return to the site if the Okta login process is unsuccessful and stalls.
	 */
	private function authenticateUserSAML() {
		$this->debugMessage("Okta::authenticateUserSAML called");
		// apache_setenv('SIMPLESAMLPHP_CONFIG_DIR', getWasqlPath(self::SIMPLESAMLPHP_CONFIG_WASQL_PATH));
		// $_SERVER['SIMPLESAMLPHP_CONFIG_DIR'] = getWasqlPath(self::SIMPLESAMLPHP_CONFIG_WASQL_PATH);
		// require_once(self::SIMPLESAMLPHP_AUTOLOADER_PATH);
		require_once(getWasqlPath(self::SIMPLESAMLPHP_AUTOLOADER_WASQL_PATH));
		$as = new \SimpleSAML\Auth\Simple($this->service_provider_id);
		$as->requireAuth();
		// Get session identifier and username from SAML session authorization data
		// // Get Okta session ID to use as the equivalent of OAuth 2.0 "sub"
		// $session = \SimpleSAML\Session::getSessionFromRequest();
		// $session_id = $session->getSessionId();
		// NOTE: It seemed like a good idea to get session ID and username from the request session but couldn't get getNameId() to work as mentioned here https://groups.google.com/g/simplesamlphp/c/NRpMNDdudEA?pli=1
		// Get SAML session index to use as the equivalent of OAuth 2.0 "sub"
		$session_index = $as->getAuthData('saml:sp:SessionIndex');
		// Get identity provider NameID
		$auth_data = $as->getAuthData('saml:sp:NameID');
		$username = $auth_data->getValue();
		// NOTE: Attributes are not automatically configured in Okta and will be empty for a new application
		$attributes = $as->getAttributes();
		$profile = $this->flattenAttributesArray($attributes);
		// TODO: Possibly set error and return false if the required values for sub and username are not as expected.
		// OLD Login by setting session state and reloading the page (done by caller when returning true)
		// Login by setting username and session variables; when returning to caller, only reload/redirect the page if the requested URL is difference from theh current URL
		$this->okta_username = $username;
		$_SESSION['okta']['sub'] = $session_index;
		$_SESSION['okta']['username'] = $username;
		$_SESSION['okta']['profile'] = $profile;
		return true;
	}

	/**
	 * Perform Okta SSO authentication using OAuth 2.0
	 * @return true|false|string True if login is in process and the user is authenticated successfully; false if an authentication error occurs; the Okta login URL string for this site if login is not in process
	 */
	private function authenticateUserOAuth2() {
		$this->debugMessage("Okta::authenticateUserOAuth2 called");
		$this->debugMessage("\$_REQUEST = ", $_REQUEST);
		// Get Okta metadata (used to get endpoint URLs)
		$metadata = Okta::httpRequest($this->metadata_url);
		// $this->debugMessage("<p>\$metadata = </p><pre>".print_r($metadata, true)."</pre>");
		// Not in the process of logging-in; get login URL from Okta for use in the login widget
		if(!isset($_REQUEST['code'])) {
			$this->debugMessage("Getting login URL from Okta...");
			$_SESSION['okta']['state'] = bin2hex(random_bytes(5));
			$_SESSION['okta']['code_verifier'] = bin2hex(random_bytes(50));
			$code_challenge = Okta::base64UrlEncode(hash('sha256', $_SESSION['okta']['code_verifier'], true));
			$authorize_url = $metadata->authorization_endpoint.'?'.http_build_query([
				'response_type' => 'code',
				'client_id' => $this->client_id,
				'redirect_uri' => $this->redirect_uri,
				'state' => $_SESSION['okta']['state'],
				'scope' => 'openid profile',
				'code_challenge' => $code_challenge,
				'code_challenge_method' => 'S256',
			]);
			$this->okta_login_url = $authorize_url;
			return $this->okta_login_url;
		}
		// In the process of logging-in
		else {
			// Make sure session state is valid
			if($_SESSION['okta']['state'] != $_REQUEST['state']) {
				$this->setError('Authorization server returned an invalid state parameter');
				return false;
			}
			// Check for authentication errors
			if(isset($_REQUEST['error'])) {
				$this->setError('Authorization server returned an error: '.htmlspecialchars($_REQUEST['error']));
				return false;
			}
			// Attempt to authenticate
			$this->debugMessage("Getting access token...");
			// Get token
			$params=array(
				'grant_type' => 'authorization_code',
				'code' => $_REQUEST['code'],
				'redirect_uri' => $this->redirect_uri,
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
				'code_verifier' => $_SESSION['okta']['code_verifier']
			);
			$response = Okta::httpRequest($metadata->token_endpoint, $params);
			$this->debugMessage("\$response = ", $response);
			if(!isset($response->access_token)) {
				$this->setError('Error fetching access token'.printValue($response).printValue($params));
				return false;
			}
			// Get authenticated user info
			// If the URL for the /userinfo endpoint is not included in the metadata, synthesize it using the URL for the /token endpoint
			if (!isset($metadata->userinfo_endpoint)) {
				$metadata->userinfo_endpoint = str_replace('token', 'userinfo', $metadata->token_endpoint);
			}
			$this->debugMessage("Got access token. Getting authenticated user info...");
			$this->debugMessage("\$metadata->userinfo_endpoint = {$metadata->userinfo_endpoint}");
			$this->debugMessage("\$response->access_token = {$response->access_token}");
			$userinfo = Okta::httpRequest($metadata->userinfo_endpoint, [
				'access_token' => $response->access_token,
			]);
			$this->debugMessage("\$userinfo = ", $userinfo);
			if (gettype($userinfo) !== 'array') {
				$profile = json_decode(json_encode($userinfo), true);
			}
			else {
				$profile = $userinfo;
			}
			$this->debugMessage("\$profile = ", $profile);
			if($userinfo->sub) {
				$this->debugMessage("Got user info.");
				// OLD Login by setting session state and reloading the page (done by caller when returning true)
				// Login by setting username and session variables; when returning to caller, only reload/redirect the page if the requested URL is difference from theh current URL
				$this->okta_username = $userinfo->preferred_username;
				$_SESSION['okta']['sub'] = $userinfo->sub;
				$_SESSION['okta']['username'] = $userinfo->preferred_username;
				$_SESSION['okta']['profile'] = $profile;
				return true;
			}
		}
		$this->debugMessage("Unexpected code path in authenticateUserOAuth2!");
		$this->setError('Unexpected code path');
		return false;
	}

	/**
	 * Set the Okta singleton's authentication method to OAuth2
	 * @param string $client_id The client ID from Okta OAuth 2.0 Application settings
	 * @param string $client_secret The client secret from Okta OAuth 2.0 Application settings
	 * @param string $metadata_url The OAuth 2.0 authorization server discovery endpoint (metadata URL). See the "Org Authorization Server discovery endpoints" section on this page: https://developer.okta.com/docs/concepts/auth-servers/#available-authorization-server-types
	 * @param string $redirect_uri A sign-in redirect URI that has been added to Okta OAuth 2.0 Application settings
	 * @return boolean Return true upon successfully setting the authentication method or set the class error property and return false on failure
	 */
	public function setOAuth2Authetication($client_id, $client_secret, $metadata_url, $redirect_uri) {
		if (!$client_id) {
			$this->setError("Error: A client ID is required for Okta OAuth 2.0 authentication. Failed to set the Okta service to OAuth 2.0 authentication.");
			return false;
		}
		if (!$client_secret) {
			$this->setError("Error: A client secret is required for Okta OAuth 2.0 authentication. Failed to set the Okta service to OAuth 2.0 authentication.");
			return false;
		}
		if (!$metadata_url) {
			$this->setError("Error: A metadata URL is required for Okta OAuth 2.0 authentication. Failed to set the Okta service to OAuth 2.0 authentication.");
			return false;
		}
		if (!$redirect_uri) {
			$this->setError("Error: A redirect URI is required for Okta OAuth 2.0 authentication. Failed to set the Okta service to OAuth 2.0 authentication.");
			return false;
		}
		$this->auth_method = 'oauth2';
		$this->service_provider_id = null;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->metadata_url = $metadata_url;
		$this->redirect_uri = $redirect_uri;
		return true;
	}

	/**
	 * Set the Okta singleton's authentication method to OAuth2
	 * @param string $service_provider_id The service provider ID used in the Okta SAML Application settings > General > SAML Settings > General > URLs and configured in SimpleSAMLphp
	 * @param string $metadata_url The identity provider metadata URL from Okta SAML Application settings > Sign On > SAML Signing Certificates > Actions > View IdP metadata
	 * @param string $redirect_uri A sign-in redirect URI
	 * @return boolean Return true upon successfully setting the authentication method or set the class error property and return false on failure
	 */
	public function setSAMLAuthetication($service_provider_id, $metadata_url, $redirect_uri) {
		if (!$service_provider_id) {
			$this->setError("Error: A service provider ID is required for Okta SAML authentication using SimpleSAMLphp. Failed to set the Okta service to SAML authentication.");
			return false;
		}
		if (!$metadata_url) {
			$this->setError("Error: A metadata URL is required for Okta SAML authentication using SimpleSAMLphp. Failed to set the Okta service to SAML authentication.");
			return false;
		}
		if (!$redirect_uri) {
			$this->setError("Error: A redirect URI is required for Okta SAML authentication using SimpleSAMLphp. Failed to set the Okta service to SAML authentication.");
			return false;
		}
		$this->auth_method = 'saml';
		$this->service_provider_id = $service_provider_id;
		$this->client_id = null;
		$this->client_secret = null;
		$this->metadata_url = $metadata_url;
		$this->redirect_uri = $redirect_uri;
		return true;
	}

	/**
	 * Get username of authenticated Okta user
	 * @return string Okta username
	 */
	public function getAuthenticatedUsername() {
		return $this->okta_username;
	}

	/**
	 * Set Okta error string
	 * @param string Okta error string
	 */
	private function setError($error) {
		$_SESSION['okta']['error'] = $error;
		$this->error = $error;
	}

	/**
	 * Clear Okta error string
	 */
	private function clearError() {
		unset($_SESSION['okta']['error']);
		$this->error = null;
	}

	/**
	 * Get Okta error string
	 * @return string Okta error string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Convert wierdly constructed SimpleSAMLphp attributes array to a one-dimensional key-value array
	 * @describe The attributes array is a key-value array whose values are numerically-indexed arrays typically consisting of a single value. This function produces a one-dimensional key-value array using either a simple string value if the value array has only one element or a JSON-encoded array if the value array has multiple elements.
	 * @param array A SimpleSAMLphp attributes array
	 * @return array A one-dimensional key-value array
	 */
	private function flattenAttributesArray($array) {
		$flat_array = array();
		foreach ($array as $key => $value) {
			$flat_array[$key] = count($value) > 1 ? json_encode($value) : $value[0];
		}
		return $flat_array;
	}

	/********************************
   * Utility functions
   ********************************/

	//---------- begin function base64UrlEncode--------------------
	/**
	 * @describe Return a JSON Web Token (JWT) for the provided header, payload, and secret key
	 * @param string
	 * @return string Base64 URL-encoded string
	 * See https://developer.okta.com/blog/2019/02/04/create-and-verify-jwts-in-php#:~:text=%E2%80%9CA%20JSON%20Web%20Token%20(JWT,payload%2C%20and%20a%20signature.%E2%80%9D&text=The%20header%20component%20of%20the,JWT%20signature%20should%20be%20computed.
	 */
	private function base64UrlEncode($text) {
		return str_replace(
			['+', '/', '='],
			['-', '_', ''],
			base64_encode($text)
		);
	}

	// TODO: Replace this with the WaSQL equivalent getUrl???
	private function httpRequest($url, $params=false, $headers=false) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if($params){
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		if($headers){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		return json_decode(curl_exec($ch));
	}

	private function debugMessage($message = '', $dump = null, $die = false, $htmlFormat = true, $output = null, $log = null) {
		if (!($output ?? $this->debug_output) && !($log ?? $this->debug_log)) return;
		$debugStr = '';
		$debugHTML = '';
		if (!empty($message)) {
			$debugStr .= $message;
			$debugHTML .= "<p>".$message."</p>";
		}
		if (!empty($dump)) {
			$debugStr .= print_r($dump, true);
			$debugHTML .= "<pre>".print_r($dump, true)."</pre>";
		}
		if (($output ?? $this->debug_output)) {
			if ($htmlFormat) {
				echo $debugHTML;
			}
			else {
				echo $debugStr."\n";
			}
		}
		if (($log ?? $this->debug_log)) {
			commonLogMessage('user', $debugStr);
		}
		if ($die) die;
	}

	/**
	 * Write a SimpleSAMLphp config.php file in the SimpleSAMLphp directory using a copy of the default config.php and the values in $config, typically provided by WaSQL Configuration > User Authentication inputs
	 * @param array $config_wasql_auth A key-value array where the key is a named SimpleSAMLphp configuration property.
	 * @return boolean|string Returns true if the configuration file was written successfully or an error message on failure
	 */
	public function writeSAMLConfig($config_wasql_auth) {
		$error_msg = null;
		$config_dir_path = getWasqlPath(self::SIMPLESAMLPHP_CONFIG_WASQL_PATH);
		$default_config_path = $config_dir_path.'/config_default.php';
		$config_path = $config_dir_path.'/config.php';
		// Get the default configuration file, config_default.php, and read it into the $config array
		if (!is_file($default_config_path)) {
			return "Error: SimpleSAMLphp default config.php file does not exist.";
		}
		if (!is_readable($default_config_path)) {
			return "Error: SimpleSAMLphp default config.php file is not readable.";
		}
		// Load config_default.php as a string and append our configuration values and code to merge them into $config and save it as config.php. SimpleSAMLphp will evalute the file as usual, but additionalyl use the configuration values from the WaSQL Configuration > User Authentication GUI.
		$config_php_str = file_get_contents($default_config_path);
		$config_wasql_auth_export = var_export($config_wasql_auth, true);
		$config_php_str .= <<<EOF
// Merge WaSQL SimpleSAMLphp configuration into default configuration
\$config_wasql = {$config_wasql_auth_export};
\$config = array_merge(\$config, \$config_wasql);
unset(\$config_wasql);
EOF;
		if (!is_dir($config_dir_path)) {
		  return "Error: SimpleSAMLphp config directory {$config_dir_path} does not exist.";
		}
		elseif (!is_writable($config_dir_path)) {
			return "Error: SimpleSAMLphp config directory {$config_dir_path} is not writable.";
		}
		elseif (is_file($config_path) && !is_writable($config_path)) {
		  return "Error: SimpleSAMLphp config.php file already exists and is not writable.";
		}
		// File/directory exists and is writable
		$result = file_put_contents($config_path, $config_php_str);
		if ($result === false) {
			return "Error: SimpleSAMLphp config.php file could not be written.";
		}
		return true;
	}

}

?>
