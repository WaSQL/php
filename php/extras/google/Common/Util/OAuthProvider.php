<?php
/**
 * Base class for all OAuth providers.
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
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

/**
 * Base class for all OAuth providers.
 * @abstract
 */
abstract class OAuthProvider {
  public static $REQUEST_TOKEN_ENDPOINT =
      'https://www.google.com/accounts/OAuthGetRequestToken';
  public static $AUTHORIZE_TOKEN_ENDPOINT =
      'https://www.google.com/accounts/OAuthAuthorizeToken';
  public static $ACCESS_TOKEN_ENDPOINT =
      'https://www.google.com/accounts/OAuthGetAccessToken';

  /**
   * Returns the value of the OAuth Authorization HTTP header for a given
   * endpoint URL.
   * @param string $url the endpoint URL to authorize against
   * @return string the OAuth Authorization HTTP header value
   */
  abstract public function GetOAuthHeader($url);
}
