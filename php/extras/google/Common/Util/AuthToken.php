<?php
/**
 * Client used to retrieve authentication tokens from the Client Login API.
 *
 * {@link http://code.google.com/apis/accounts/docs/AuthForInstalledApps.html}
 *
 * PHP version 5
 *
 * Copyright 2009, Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package    GoogleApiAdsCommon
 * @subpackage Util
 * @category   WebServices
 * @copyright  2009, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Adam Rogal <api.arogal@gmail.com>
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

/**
 * Client used to retrieve an authentication token for the supplied credentials
 * with the client login API.
 */
class AuthToken {
  private $email;
  private $passwd;
  private $accountType;
  private $service;
  private $source;
  private $server;
  private $res;

  /**
   * Constructor for the authentication token.
   * @param string $email the email of the user
   * @param string $passwd the password of the user
   * @param string $service the service name
   * @param string $source the source name
   * @param string $accountType the account type. Defaults to GOOGLE
   * @param string $server the server to make the request to. Defaults to
   *     https://www.google.com
   */
  function __construct($email, $passwd, $service,
      $source, $accountType = 'GOOGLE', $server = NULL) {
    $this->email = $email;
    $this->passwd = $passwd;
    $this->accountType = $accountType;
    $this->service = $service;
    $this->source = $source;
    $this->server = isset($server) ? $server : 'https://www.google.com';
  }

  /**
   * Gets the token from the client login API result.
   * @return string the auth token
   * @access private
   */
  private function GetToken() {
    $lines = explode("\n", $this->res);
    foreach ($lines as $line) {
      $parts = explode('=', $line);
      if ($parts[0] == 'Auth') {
        return $parts[1];
      }
    }

    throw new InvalidArgumentException(
        'The login request used a username or password that is not'
        . ' recognized.');
  }

  /**
   * Peforms a POST to get the auth token and then parses the result.
   * @return string the auth token
   */
  public function GetAuthToken() {
    $this->Login();
    return $this->GetToken();
  }

  /**
   * Makes the client login requrest and stores the result.
   * @access private
   */
  private function Login() {
    $postUrl = $this->server . '/accounts/ClientLogin';
    $postVars = 'accountType=' . $this->accountType . '&Email='
        . urlencode($this->email) . '&Passwd=' . urlencode($this->passwd)
        . '&service=' . $this->service . '&source=' . $this->source;

    $ch = curl_init($postUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $this->res = curl_exec($ch);
    curl_close($ch);
  }
}
