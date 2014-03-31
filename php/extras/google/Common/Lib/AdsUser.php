<?php
/**
 * User class for all API modules using the Ads API.
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

require_once dirname(__FILE__) . '/../Util/Logger.php';
require_once 'SoapClientFactory.php';
require_once 'ValidationException.php';

/**
 * User class for all API modules using the Ads API.
 */
abstract class AdsUser {
  private $requestHeaderElements;
  private $defaultServer;
  private $defaultVersion;
  private $logsDirectory;

  /**
   * Constructor for AdsUser.
   * @access protected
   */
  protected function __construct() {
    $this->requestHeaderElements = array();
    $this->logsDirectory = '.';
  }

  /**
   * Gets the authenticaiton value for the <var>$authVar</var> supplied. If
   * the <var>$authVar</var> is set, it is is used. Otherwirse, the supplied
   * <var>$authenticationIni</var> is queired for the variable. If none is found
   * <var>NULL</var> is returned.
   * @param string $authVar the default value for the authenticaiton variable
   * @param string $authVarName the name of the authencation variable
   * @param array $authIni the array of authentication variables from
   *     an INI file
   * @return string the authentication variable value
   * @access protected
   */
  protected function GetAuthVarValue($authVar, $authVarName,
      array $authIni) {
    if (isset($authVar)) {
      return $authVar;
    } else {
      if (array_key_exists($authVarName, $authIni)) {
        return $authIni[$authVarName];
      } else {
        return NULL;
      }
    }
  }

  /**
   * Gets the names of all registered request header elements.
   * @return array the names of the request header elements
   */
  public function GetHeaderNames() {
    return array_keys($this->requestHeaderElements);
  }

  /**
   * Gets the value for a registered request header element.
   * @param string $key the name of the request header element
   * @return string the value of the request header element or NULL if not found
   */
  public function GetHeaderValue($key) {
    if (array_key_exists($key, $this->requestHeaderElements)) {
      return $this->requestHeaderElements[$key];
    } else {
      return NULL;
    }
  }

  /**
   * Sets the value for a request header.
   * @param string $key the name of the request header element
   * @param string $value the value for the request header element
   */
  public function SetHeaderValue($key, $value) {
    $this->requestHeaderElements[$key] = $value;
  }

  /**
   * Gets the service by its service name.
   * @param string $serviceName the service name
   * @param SoapClientFactory $serviceFactory the service factory
   * @param string $serviceGroup the service group. Can be <var>NULL</var>
   *     if the product has not implemented service groups yet
   * @param string $serviceGroupUrlOverride the name of the service group to be
   *     used in the location url
   * @param string $serviceGroupHeaderNamespaceOverride the name of the service
   *     group to use in the header namespace
   * @return SoapClient the instantiated service
   */
  public function GetServiceSoapClient($serviceName,
      SoapClientFactory $serviceFactory, $serviceGroup = NULL,
      $serviceGroupUrlOverride = NULL,
      $serviceGroupHeaderNamespaceOverride = NULL) {
    return $serviceFactory->GenerateSoapClient($serviceName, $serviceGroup,
        $serviceGroupUrlOverride, $serviceGroupHeaderNamespaceOverride);
  }

  /**
   * Initiates the default logging behavior of logging all HTTP headers and
   * SOAP XML to the soap_xml.log file and all request information to the
   * request_info.log file under the logs directory.
   */
  public function LogDefaults() {
    Logger::logToFile(Logger::$SOAP_XML_LOG,
        $this->logsDirectory . "/soap_xml.log");
    Logger::logToFile(Logger::$REQUEST_INFO_LOG,
        $this->logsDirectory . "/request_info.log");
  }

  /**
   * Loads the settings for this client library. If the settings INI file
   * located at <var>$settingsIniPath</var> cannot be loaded, then the
   * parameters passed into this method are used.
   * @param string $settingsIniPath the path to the settings INI file
   * @param string $defaultVersion the default version if the settings INI file
   *     cannot be loaded
   * @param string $defaultServer the default server if the settings INI file
   *     cannot be loaded
   * @param string $defaultLogsDir the default logs directory if the settings
   *     INI file cannot be loaded
   * @param string $logsRelativePathBase the relative path base for the logs
   *     directory
   */
  public function LoadSettings($settingsIniPath, $defaultVersion,
      $defaultServer, $defaultLogsDir, $logsRelativePathBase) {
    // Set no time limit for PHP operations.
    set_time_limit(0);

    ini_set('default_socket_timeout', 480);

    // Settings for the SOAP extension.
    ini_set('soap.wsdl_cache', 0);
    ini_set('soap.wsdl_cache_enabled', 0);
    ini_set('soap.wsdl_cache_dir', "./tmp/");
    ini_set('soap.wsdl_cache_ttl', 86400);

    $settingsIni = parse_ini_file($settingsIniPath, true);

    if (isset($settingsIni)) {
      // Logging settings.
      if ($settingsIni['LOGGING']['PATH_RELATIVE'] == 1) {
        $path = realpath($logsRelativePathBase . '/'
            . $settingsIni['LOGGING']['LIB_LOG_DIR_PATH']);
        if ($path === false) {
          $this->logsDirectory = $defaultLogsDir;
        } else {
          $this->logsDirectory = $path;
        }
      } else {
        $this->logsDirectory = $settingsIni['LOGGING']['LIB_LOG_DIR_PATH'];
      }

      // Server settings.
      if (array_key_exists('SERVER', $settingsIni)
          && array_key_exists('DEFAULT_VERSION', $settingsIni['SERVER'])) {
        $this->defaultVersion = $settingsIni['SERVER']['DEFAULT_VERSION'];
      } else {
        $this->defaultVersion = $defaultVersion;
      }
      if (array_key_exists('SERVER', $settingsIni)
          && array_key_exists('DEFAULT_SERVER', $settingsIni['SERVER'])) {
        $this->defaultServer = $settingsIni['SERVER']['DEFAULT_SERVER'];
      } else {
        $this->defaultServer = $defaultServer;
      }

      // SOAP settings.
      if (array_key_exists('SOAP', $settingsIni)
          && array_key_exists('COMPRESSION', $settingsIni['SOAP'])) {
        $this->soapCompression = $settingsIni['SOAP']['COMPRESSION'];
      } else {
        // Default to using compression.
        $this->soapCompression = TRUE;
      }
      if (array_key_exists('SOAP', $settingsIni)
          && array_key_exists('COMPRESSION_LEVEL', $settingsIni['SOAP'])
          && $settingsIni['SOAP']['COMPRESSION_LEVEL'] >= 1
          && $settingsIni['SOAP']['COMPRESSION_LEVEL'] <= 9) {
        $this->soapCompressionLevel = $settingsIni['SOAP']['COMPRESSION_LEVEL'];
      } else {
        // Default to using compression level 1.
        $this->soapCompressionLevel = 1;
      }
    }
  }

  /**
   * Gets the default server.
   * @return string the default server
   */
  public function GetDefaultServer() {
    return $this->defaultServer;
  }

  /**
   * Gets the default version.
   * @return string the default version
   */
  public function GetDefaultVersion() {
    return $this->defaultVersion;
  }

  /**
   * Gets the logs directory.
   * @return string the logs directory
   */
  public function GetLogsDirectory() {
    return $this->logsDirectory;
  }

  /**
   * Is SOAP compression enabled.
   * @return bool is SOAP compression enabled
   */
  public function IsSoapCompressionEnabled() {
    return $this->soapCompression;
  }

  /**
   * Gets the SOAP compression level.
   * @return int the SOAP compression level
   */
  public function GetSoapCompressionLevel() {
    return $this->soapCompressionLevel;
  }

  /**
   * Gets the client library identifier used for user-agent fields.
   * @return string a unique client library identifier
   */
  abstract public function GetClientLibraryIdentifier();
}
