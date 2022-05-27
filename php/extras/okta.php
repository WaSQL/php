<?php
/********************************
* Okta class
* A Singleton to negotiate Okta SSO authentication
********************************/

class Okta {
     private static $instance = null;
     private $error = null;
     private $okta_username = null;
     private $okta_login_url = null;

     protected function __construct() { // Declared as protected to follow Singleton pattern/prevent new instances
     	$this->handleLoginState();
     }
     protected function __clone() {} // Declared as protected to prevent cloning
     protected function __wakeup() {} //Declared as protected to prevent restore

     /**
     * Get a single instance of the Okta class
     * @return Okta Okta instance
     */
     public static function getInstance() {
          if(!isset(self::$instance)) {
               self::$instance = new Okta();
          }
          return self::$instance;
     }

     /**
     * Perform one of several interactions with Okta based on request and session variables to/on this site and the presence of an active Okta SSO session elsewhere
     */
     private function handleLoginState() {
          session_start();

          // For debugging
          // echo "<p>\$_REQUEST = </p><pre>".print_r($_REQUEST, true)."</pre>";
          // echo "<p>\$_SESSION = </p><pre>".print_r($_SESSION, true)."</pre>";

          // For testing allow this site's session to be manually destroyed
          // NOTE: This will automatically login users with an active Okta session when the page reloads
          if (isset($_REQUEST['reset'])) {
               session_destroy();
               header('Location: '.OKTA_REDIRECT_URI);
               die();
          }

          // Unset automatic login restriction if expired
          if (isset($_SESSION['okta']['restrict_auto_login']) && time() > $_SESSION['okta']['restrict_auto_login']) {
               unset($_SESSION['okta']['restrict_auto_login']);
          }

          // Handle Okta states
          // Logging-out
          if (isset($_REQUEST['_logout'])) {
               $this->clearError();
               // Logout by unsetting Okta user session variables and setting "logged-out" session variable
               unset($_SESSION['okta']['username']);
               unset($_SESSION['okta']['sub']);
               $_SESSION['okta']['restrict_auto_login'] = time()+OKTA_RESTRICT_AUTO_LOGIN_DURATION;
               // Reload page to complete logout
               header('Location: '.OKTA_REDIRECT_URI);
               die();
          }
          // Logged-in user
          if (isset($_SESSION['okta']['sub'])) {
               // Set username
               $this->okta_username = $_SESSION['okta']['username'];
               return;
          }
          // Not logged-in; authenticate or get login URL
          $auth = $this->authenticateUser();
          // User authenticated successfully
          if ($auth === true) {
               $this->clearError();
               unset($_SESSION['okta']['restrict_auto_login']);
               // Reload page to complete login
               header('Location: '.OKTA_REDIRECT_URI);
               die();
          }
          // Login URL returned
          elseif ($auth !== false && is_string($auth)) {
               $this->clearError();
               // Automatically login users with an active Okta session using the login URL UNLESS the user specifically logged-out of this site
               if (!isset($_SESSION['okta']['restrict_auto_login'])) {
                    header('Location: '.$this->okta_login_url);
                    die();
               }
               // Okta object is now constructed with a login URL for use by renderLoginWidget
               return;
          }
          // Authentication error occurred
          else {
               // Intentionally blank
          }
          // Okta object is now constructed but in an error state; errors can be accessed using getError, if required
          // renderLoginWidget should present a fatal error
     }

     /**
     * Perform authentication using Okta SSO
     * @return true|false|string True if login is in process and the user is authenticated successfully; false if an authentication error occurs; the Okta login URL string for this site if login is not in process
     */
     private function authenticateUser() {
     	//clear out errors in session
     	if(isset($_SESSION['okta']['error'])){
			unset($_SESSION['okta']['error']);
		}
          // Get Okta metadata (used to get endpoint URLs)
          $metadata = Okta::httpRequest(OKTA_METADATA_URL);
          // Not in the process of logging-in; get login URL from Okta for use in the login widget
          if(!isset($_REQUEST['code'])) {
               $_SESSION['okta']['state'] = bin2hex(random_bytes(5));
               $_SESSION['okta']['code_verifier'] = bin2hex(random_bytes(50));
               $code_challenge = Okta::base64UrlEncode(hash('sha256', $_SESSION['okta']['code_verifier'], true));
               $authorize_url = $metadata->authorization_endpoint.'?'.http_build_query([
	               'response_type' => 'code',
	               'client_id' => OKTA_CLIENT_ID,
	               'redirect_uri' => OKTA_REDIRECT_URI,
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
                    $_SESSION['okta']['error']='Authorization server returned an invalid state parameter';
                    $this->setError($_SESSION['okta']['error']);
                    return false;
               }
               // Check for authentication errors
               if(isset($_REQUEST['error'])) {
                    $_SESSION['okta']['error']='Authorization server returned an error: '.htmlspecialchars($_REQUEST['error']);
                    $this->setError($_SESSION['okta']['error']);
                    return false;
               }
               // Attempt to authenticate
               // Get token
               $params=array(
               	'grant_type' => 'authorization_code',
	               'code' => $_REQUEST['code'],
	               'redirect_uri' => OKTA_REDIRECT_URI,
	               'client_id' => OKTA_CLIENT_ID,
	               'client_secret' => OKTA_CLIENT_SECRET,
	               'code_verifier' => $_SESSION['okta']['code_verifier']
	          );
               $response = Okta::httpRequest($metadata->token_endpoint, $params);
               if(!isset($response->access_token)) {
                     $_SESSION['okta']['error']='Error fetching access token'.printValue($response).printValue($params);
                     $this->setError($_SESSION['okta']['error']);
                    return false;
               }
               // Get authenticated user info
               $userinfo = Okta::httpRequest($metadata->userinfo_endpoint, [
               	'access_token' => $response->access_token,
               ]);
               if($userinfo->sub) {
                    // Login by setting session state and reloading the page (done by caller when returning true)
                    $_SESSION['okta']['sub'] = $userinfo->sub;
                    $_SESSION['okta']['username'] = $userinfo->preferred_username;
                    $_SESSION['okta']['profile'] = $userinfo;
                    return true;
               }
          }
          $_SESSION['okta']['error']='Unexpected code path';
          $this->setError($_SESSION['okta']['error']);
          return false;
     }

     /**
     * Set Okta error string
     * @param string Okta error string
     */
     private function setError($error) {
     	$this->error = $error;
     }

     /**
     * Clear Okta error string
     */
     private function clearError() {
     	$this->error = null;
     }

     /**
     * Get Okta error string
     * @return string Okta error string
     */
     public function getError() {
     	return $this->error;
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
     function base64UrlEncode($text) {
	     return str_replace(
		     ['+', '/', '='],
		     ['-', '_', ''],
		     base64_encode($text)
	     );
     }

     // TODO: Replace this with the WaSQL equivalent getUrl???
     function httpRequest($url, $params=false, $headers=false) {
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

}

?>