<?php
/**
 * Base class for all SOAP client factories of Ads client libraries.
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
 * @subpackage Lib
 * @category   WebServices
 * @copyright  2009, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Adam Rogal <api.arogal@gmail.com>
 */

require_once 'AdsUser.php';

/**
 * Base class for all SOAP client factories of Ads client libraries.
 * @abstract
 */
abstract class SoapClientFactory {
  private $user;
  private $version;
  private $server;
  private $productName;
  private $headerOverrides;

  /**
   * The constructor called by any sub-class.
   * @param AdsUser $user the user which the client will use for credentials
   * @param string $version the version to generate clients for
   * @param string $server the server to generate clients for
   * @param string $productName the product name (i.e. adwords)
   * @access protected
   */
  protected function __construct(AdsUser $user, $version, $server,
      $productName, $headerOverrides = NULL) {
    $this->user = $user;
    $this->version = $version;
    $this->server = $server;
    $this->productName = $productName;
    $this->headerOverrides = $headerOverrides;
  }

  /**
   * Initiates a require_once for the service.
   * @param string $serviceName the service to instantiate
   * @param string $serviceGroup the service group to use. Can be
   *     <var>NULL</var> if the product has not implemented service groups yet
   * @access protected
   */
  abstract protected function DoRequireOnce($serviceName, $serviceGroup = NULL);

  /**
   * Generates a SOAP client for the given service name. Generates a user level
   * error if this instalation of PHP does not have the extension for SOAP
   * installed.
   * @param string $serviceName the name of the service to generate a client for
   * @param string $serviceGroup the name of the service group. Can be
   *     <var>NULL</var> if the product has not implemented service groups yet
   * @param string $serviceGroupUrlOverride the name of the service group to be
   *     used in the location url
   * @param string $serviceGroupHeaderNamespaceOverride the name of the service
   *     group to use in the header namespace
   * @return AdsSoapClient an instantiated SOAP client
   */
  public function GenerateSoapClient($serviceName, $serviceGroup = NULL,
      $serviceGroupUrlOverride = NULL,
      $serviceGroupHeaderNamespaceOverride = NULL) {
    if (extension_loaded('soap')) {
      $this->DoRequireOnce($serviceName, $serviceGroup);
      $soapClient = $this->GenerateServiceClient($serviceName,
          isset($serviceGroupUrlOverride)
              ? $serviceGroupUrlOverride : $serviceGroup);
      return $soapClient;
    } else {
      trigger_error('This client library requires the SOAP extension to be'
          . ' activated. See http://php.net/manual/en/soap.installation.php for'
          . ' details.', E_USER_ERROR);
    }
  }

  /**
   * Generates the SOAP service client without the proper headers set yet.
   * @param string $serviceName the service to create a client for
   * @param string $serviceGroupo the group of the service
   * @return AdsSoapClient the SOAP service client
   * @access protected
   */
  protected function GenerateServiceClient($serviceName, $serviceGroup = NULL) {
    $location = $this->GetServiceLocation($serviceName, $serviceGroup);
    $wsdl = $location . '?wsdl';
    $options = array(
        'trace' => true,
        'encoding' => 'utf-8',
        'connection_timeout' => 0,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS);

    if ($this->GetAdsUser()->IsSoapCompressionEnabled()) {
      $options['compression'] = SOAP_COMPRESSION_ACCEPT |
          SOAP_COMPRESSION_GZIP |
          $this->GetAdsUser()->GetSoapCompressionLevel();
      // The User-Agent HTTP header must contain the string 'gzip'.
      $options['user_agent'] = 'PHP-SOAP/'. phpversion() . ', gzip';
    }

    $soapClient = new $serviceName($wsdl, $options, $this->GetAdsUser());
    $soapClient->__setLocation($location);

    // Copy headers from user.
    foreach($this->GetAdsUser()->GetHeaderNames() as $key) {
      $soapClient->SetHeaderValue(
          $key, $this->GetAdsUser()->GetHeaderValue($key));
    }

    // Copy headers from overrides.
    if (isset($this->headerOverrides)) {
      foreach($this->headerOverrides as $key => $value) {
        $soapClient->SetHeaderValue($key, $value);
      }
    }

    return $soapClient;
  }

  /**
   * Gets the end-point location of the service.
   * @param string $serviceName the service to instantiate
   * @param string $serviceGroup the service group to use. Can be
   *     <var>NULL</var> if the product has not implemented service groups yet
   * @return string the end-point location of the service.
   * @access protected
   */
  protected function GetServiceLocation($serviceName, $serviceGroup = NULL) {
    return implode('/', array($this->GetServer(), 'api',
        $this->GetProductName(), $serviceGroup, $this->GetVersion(),
        $serviceName));
  }

  /**
   * Gets the user associated with this factory.
   * @return AdsUser the user associated with this factory
   */
  public function GetAdsUser() {
    return $this->user;
  }

  /**
   * Gets the version associated with this factory.
   * @return string the version associated with this factory
   */
  public function GetVersion() {
    return $this->version;
  }

  /**
   * Gets the server associated with this factory.
   * @return string the server associated with this factory
   */
  public function GetServer() {
    return $this->server;
  }

  /**
   * Gets the product name associated with this factory.
   * @return string the product name associated with this factory
   */
  public function GetProductName() {
    return $this->productName;
  }
}
