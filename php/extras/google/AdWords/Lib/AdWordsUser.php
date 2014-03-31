<?php
/**
 * User class for the AdWords API to create SOAP clients to the available API
 * services.
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
 * @package    GoogleApiAdsAdWords
 * @subpackage Lib
 * @category   WebServices
 * @copyright  2009, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Adam Rogal <api.arogal@gmail.com>
 * @see        AdsUser
 */

require_once dirname(__FILE__) . '/../../Common/Util/Logger.php';
require_once dirname(__FILE__) . '/../../Common/Util/AuthToken.php';
require_once dirname(__FILE__) . '/../../Common/Lib/AdsUser.php';
require_once 'AdWordsSoapClientFactory.php';

/**
 * User class for the AdWords API to create SOAP clients to the available API
 * services.
 */
class AdWordsUser extends AdsUser {
  private static $LIB_VERSION = 'v2.0.0';
  private static $LIB_NAME = 'AdWords API PHP Client Library';

  /**
   * The default version that is loaded if the settings INI cannot be loaded.
   * @var string default version of the API
   */
  private static $DEFAULT_VERSION = 'v200909';

  /**
   * The default server that is loaded if the settings INI cannot be loaded.
   * @var string default server of the API
   */
  private static $DEFAULT_SERVER = 'https://adwords.google.com';

  private $email;
  private $password;

  /**
   * The AdWordsUser constructor.
   * <p>The AdWordsUser class can be configured in one of two ways:
   * <ol><li>Using an authenitcation INI file</li>
   * <li>Using supplied credentials</li></ol></p>
   * <p>If an authentication INI file is provided and successfully loaded, those
   * values will be used unless a corresponding parameter overwrites it.
   * If the authentication INI file is not provided (e.g. it is <var>NULL</var>)
   * the class will attempt to load the default authentication file at the path
   * of "../auth.ini" relative to this file's directory. Any corresponding
   * parameter, which is not <var>NULL</var>, will, however, overwrite any
   * parameter loaded from the default INI.</p>
   * <p>Likewise, if a custom settings INI file is not provided, the default
   * settings INI file will be loaded from the path of "../settings.ini"
   * relative to this file's directory.</p>
   * @param string $authenticationIniPath the absolute path to the
   *     authentication INI or relative to the current directory (cwd). If
   *     <var>NULL</var>, the default authentication INI file will attempt to be
   *     loaded
   * @param string $email the email of the user (required header). Will
   *     overwrite the email entry loaded from any INI file
   * @param string $password the password of the user (required header). Will
   *     overwrite the password entry loaded from any INI file
   * @param string $developerToken the developer token (required header). Will
   *     overwrite the developer token entry loaded from any INI file
   * @param string $applicationToken the application token (required header).
   *     Will overwrite the application token entry loaded from any INI file
   * @param string $userAgent the user agent name (required header). Will
   *     be prepended with the library name and version. Will also overwrite the
   *     userAgent entry in any INI file
   * @param string $clientId the client email or ID to make the request against
   *     (optional header). Will overwrite the clientId, clientEmail, or
   *     clientCustomerId entries loaded from any INI file
   * @param string $settingsIniPath the path to the settings INI file. If
   *     <var>NULL</var>, the default settings INI file will be loaded
   */
  public function __construct($authenticationIniPath = NULL, $email = NULL,
      $password = NULL, $developerToken = NULL, $applicationToken = NULL,
      $userAgent = NULL, $clientId = NULL, $settingsIniPath = NULL) {
    parent::__construct();

    if (isset($authenticationIniPath)) {
      $authenticationIni = parse_ini_file(realpath($authenticationIniPath));
    } else {
      $authenticationIni =
          parse_ini_file(dirname(__FILE__) . '/../auth.ini');
    }

    $email = $this->GetAuthVarValue($email, 'email', $authenticationIni);
    $password = $this->GetAuthVarValue($password, 'password',
        $authenticationIni);
    $developerToken = $this->GetAuthVarValue($developerToken, 'developerToken',
        $authenticationIni);
    $applicationToken = $this->GetAuthVarValue($applicationToken,
        'applicationToken', $authenticationIni);
    $userAgent = $this->GetAuthVarValue($userAgent, 'userAgent',
        $authenticationIni);
    $clientId = $this->GetAuthVarValue($clientId, 'clientId',
        $authenticationIni);
    $clientId = $this->GetAuthVarValue($clientId, 'clientEmail',
        $authenticationIni);
    $clientId = $this->GetAuthVarValue($clientId, 'clientCustomerId',
        $authenticationIni);

    $this->SetEmail($email);
    $this->SetPassword($password);
    $this->SetClientLibraryUserAgent($userAgent);
    $this->SetClientId($clientId);
    $this->SetDeveloperToken($developerToken);
    $this->SetApplicationToken($applicationToken);
    $this->ValidateUser();
    $this->RegenerateAuthToken();

    if (!isset($settingsIniPath)) {
      $settingsIniPath = dirname(__FILE__) . '/../settings.ini';
    }

    $this->LoadSettings($settingsIniPath,
        AdWordsUser::$DEFAULT_VERSION,
        AdWordsUser::$DEFAULT_SERVER,
        dirname(__FILE__), dirname(__FILE__));
  }

  /**
   * Gets the AdExtensionOverrideService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return AdExtensionOverrideService the instantiated ad extension override
   *     service
   */
  public function GetAdExtensionOverrideService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('AdExtensionOverrideService', 'cm', $version, $server,
        $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the AdGroupAdService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return AdGroupAdService the instantiated ad group ad service
   */
  public function GetAdGroupAdService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('AdGroupAdService', 'cm', $version, $server,
        $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the AdGroupCriterionService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return AdGroupCriterionService the instantiated ad group criterion service
   */
  public function GetAdGroupCriterionService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('AdGroupCriterionService', 'cm', $version, $server,
        $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the AdGroupService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return AdGroupService the instantiated ad group service
   */
  public function GetAdGroupService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('AdGroupService', 'cm', $version, $server,
        $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the AdParamService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return AdParamService the instantiated ad group service
   */
  public function GetAdParamService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('AdParamService', 'cm', $version, $server,
        $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the BulkMutateJobService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return BulkMutateJobService the instantiated bulk mutate job service
   */
  public function GetBulkMutateJobService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('BulkMutateJobService', 'cm', $version, $server,
        $serviceFactory, 'job', NULL, $validateOnly);
  }

  /**
   * Gets the CampaignAdExtensionService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return CampaignAdExtensionService the instantiated campaign ad extension
   *     service
   */
  public function GetCampaignAdExtensionService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('CampaignAdExtensionService', 'cm', $version,
        $server, $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the CampaignCriterionService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return CampaignCriterionService the instantiated campaign criterion
   *     service
   */
  public function GetCampaignCriterionService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('CampaignCriterionService', 'cm', $version,
        $server, $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the CampaignService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return CampaignService the instantiated campaign service
   */
  public function GetCampaignService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('CampaignService', 'cm', $version, $server,
        $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the CampaignTargetService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return CampaignTargetService the instantiated campaign target service
   */
  public function GetCampaignTargetService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('CampaignTargetService', 'cm', $version, $server,
        $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the GeoLocationService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return GeoLocationService the instantiated geo location service
   */
  public function GetGeoLocationService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('GeoLocationService', 'cm', $version, $server,
        $serviceFactory, NULL, NULL, $validateOnly);
  }

  /**
   * Gets the InfoService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return InfoService the instantiated info service
   */
  public function GetInfoService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('InfoService', 'info', $version, $server,
        $serviceFactory, NULL, 'info', $validateOnly);
  }

  /**
   * Gets the TargetingIdeaService SOAP client.
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return TargetingIdeaService the instantiated targeting idea service
   */
  public function GetTargetingIdeaService($version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $validateOnly = NULL) {
    return $this->GetService('TargetingIdeaService', 'o', $version, $server,
        $serviceFactory, NULL, 'o', $validateOnly);
  }

  /**
   * Gets the service by its service name and group.
   * @param $serviceName the service name
   * @param $serviceGroup the service group
   * @param string $version the version of the service to get. If
   *     <var>NULL</var>, then the default version will be used
   * @param string $server the server to make the request to. If
   *     <var>NULL</var>, then the default server will be used
   * @param SoapClientFactory $serviceFactory the factory to create the client.
   *     If <var>NULL</var>, then the built-in SOAP client factory will be used
   * @param string $serviceGroupUrlOverride the name of the service group to be
   *     used in the location url
   * @param string $serviceGroupHeaderNamespaceOverride the name of the service
   *     group to use in the header namespace
   * @param bool $validateOnly if the service should be created in validateOnly
   *     mode
   * @return SoapClient the instantiated service
   * @access private
   */
  private function GetService($serviceName, $serviceGroup, $version = NULL,
      $server = NULL, SoapClientFactory $serviceFactory = NULL,
      $serviceGroupUrlOverride = NULL,
      $serviceGroupHeaderNamespaceOverride = NULL, $validateOnly = NULL) {
    if (!isset($serviceFactory)) {
      if (!isset($version)) {
        $version = $this->GetDefaultVersion();
      }

      if (!isset($server)) {
        $server = $this->GetDefaultServer();
      }

      $serviceFactory =
          new AdWordsSoapClientFactory($this, $version, $server, $validateOnly);
    }

    return parent::GetServiceSoapClient($serviceName, $serviceFactory,
        $serviceGroup, $serviceGroupUrlOverride,
        $serviceGroupHeaderNamespaceOverride);
  }

  /**
   * Regenerates the authentication token and sets it for this user.
   * @param string $server the sever to retrieve the token from
   * @return string the newly generated auth token
   */
  public function RegenerateAuthToken($server = NULL) {
    $authTokenClient = new AuthToken($this->email, $this->password, 'adwords',
        $this->GetUserAgent(), 'GOOGLE', $server);
    $authToken = $authTokenClient->GetAuthToken();
    $this->SetAuthToken($authToken);
    return $authToken;
  }

  /**
   * Sets the authentication token.
   * @param string $authToken the auth token to set
   */
  public function SetAuthToken($authToken) {
    $this->SetHeaderValue('authToken', $authToken);
  }

  /**
   * Gets the developer token for this user.
   * @return string the developer token
   */
  public function GetDeveloperToken() {
    return $this->GetHeaderValue('developerToken');
  }

  /**
   * Sets the developer token for this user.
   * @param string $developerToken the developer token
   */
  public function SetDeveloperToken($developerToken) {
    $this->SetHeaderValue('developerToken', $developerToken);
  }

  /**
   * Gets the application token that this user.
   * @return string the application token
   */
  public function GetApplicationToken() {
    return $this->GetHeaderValue('applicationToken');
  }

  /**
   * Sets the application token for this user.
   * @param string $applicationToken the application token
   */
  public function SetApplicationToken($applicationToken) {
    $this->SetHeaderValue('applicationToken', $applicationToken);
  }

  /**
   * Gets the client ID for this user. Can be the client email or client
   * customer ID.
   * @return string the client ID for this user
   */
  public function GetClientId() {
    if ($this->GetHeaderValue('clientEmail')) {
      return $this->GetHeaderValue('clientEmail');
    } else if ($this->GetHeaderValue('clientCustomerId')) {
      return $this->GetHeaderValue('clientCustomerId');
    }
  }

  /**
   * Sets the client ID for this user. Can be the client email or client
   * customer ID. Setting <var>$clientId</var> to <var>NULL</var> will result
   * in removing both the clientEmail and clientCustomerId fields.
   * @param string $clientId the client ID for this user
   */
  public function SetClientId($clientId) {
    if (!isset($clientId)) {
      $this->SetHeaderValue('clientCustomerId', NULL);
      $this->SetHeaderValue('clientEmail', NULL);
    } else if (strpos($clientId, '@') === FALSE) {
      $this->SetHeaderValue('clientCustomerId', $clientId);
    } else {
      $this->SetHeaderValue('clientEmail', $clientId);
    }
  }

  /**
   * Gets the user agent for this library.
   * @return string the user agent
   */
  public function GetUserAgent() {
    return $this->GetHeaderValue('userAgent');
  }

  /**
   * Sets the user agent for this library.
   * @param string $userAgent the user agent. Will be prepended
   *     with the library name and version.
   */
  public function SetClientLibraryUserAgent($userAgent) {
    $this->SetHeaderValue('userAgent',
        'PHP v' . phpversion() . ' - '
            . AdWordsUser::$LIB_NAME . ' - '
            . AdWordsUser::$LIB_VERSION
            . ' - ' . $userAgent);
  }

  /**
   * Gets the email address of the user login.
   * @return string the user login email
   */
  public function GetEmail() {
    return $this->email;
  }

  /**
   * Sets the email address of the user login.
   * @param string $email the user login email
   */
  public function SetEmail($email) {
    $this->email = $email;
  }

  /**
   * Gets the password for this user.
   * @return string the password for this user
   */
  public function GetPassword() {
    return $this->password;
  }

  /**
   * Sets the password for this user.
   * @param string $password the password for this user
   */
  public function SetPassword($password) {
    $this->password = $password;
  }

  /**
   * Gets the client library identifier used for user-agent fields.
   * @return string a unique client library identifier
   */
  public function GetClientLibraryIdentifier() {
    return $this->GetUserAgent();
  }

  /**
   * Validates the user and throws a validation error if there are any errors.
   * @throws ValidationException if there are any validation errors
   * @access private
   */
  private function ValidateUser() {
    if (!isset($this->email)) {
      throw new ValidationException('email', NULL,
          'email is required and cannot be NULL.');
    }

    if (!isset($this->password)) {
      throw new ValidationException('password', NULL,
          'password is required and cannot be NULL.');
    }

    if ($this->GetUserAgent() == NULL) {
      throw new ValidationException('userAgent', NULL,
          'userAgent is required and cannot be NULL.');
    }

    if ($this->GetDeveloperToken() == NULL) {
      throw new ValidationException('developerToken', NULL,
          'developerToken is required and cannot be NULL.');
    }
  }
}
