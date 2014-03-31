<?php
/**
 * Contains all client objects for the AdExtensionOverrideService service.
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
 * @subpackage v200909
 * @category   WebServices
 * @copyright  2009, Google Inc. All Rights Reserved.
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author     Eric Koleda <api.ekoleda@gmail.com>
 */

require_once dirname(__FILE__) . "/../../Lib/AdWordsSoapClient.php";

if (!class_exists("Address", FALSE)) {
/**
 * Structure to specify an address location.
 */
class Address {
  /**
   * @access public
   * @var string
   */
  public $streetAddress;

  /**
   * @access public
   * @var string
   */
  public $streetAddress2;

  /**
   * @access public
   * @var string
   */
  public $cityName;

  /**
   * @access public
   * @var string
   */
  public $provinceCode;

  /**
   * @access public
   * @var string
   */
  public $provinceName;

  /**
   * @access public
   * @var string
   */
  public $postalCode;

  /**
   * @access public
   * @var string
   */
  public $countryCode;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Address";
  }

  public function __construct($streetAddress = NULL, $streetAddress2 = NULL, $cityName = NULL, $provinceCode = NULL, $provinceName = NULL, $postalCode = NULL, $countryCode = NULL) {
    if(get_parent_class('Address')) parent::__construct();
    $this->streetAddress = $streetAddress;
    $this->streetAddress2 = $streetAddress2;
    $this->cityName = $cityName;
    $this->provinceCode = $provinceCode;
    $this->provinceName = $provinceName;
    $this->postalCode = $postalCode;
    $this->countryCode = $countryCode;
  }
}}

if (!class_exists("GeoPoint", FALSE)) {
/**
 * Specifies a geo location with the supplied lat/long.
 */
class GeoPoint {
  /**
   * @access public
   * @var integer
   */
  public $latitudeInMicroDegrees;

  /**
   * @access public
   * @var integer
   */
  public $longitudeInMicroDegrees;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "GeoPoint";
  }

  public function __construct($latitudeInMicroDegrees = NULL, $longitudeInMicroDegrees = NULL) {
    if(get_parent_class('GeoPoint')) parent::__construct();
    $this->latitudeInMicroDegrees = $latitudeInMicroDegrees;
    $this->longitudeInMicroDegrees = $longitudeInMicroDegrees;
  }
}}

if (!class_exists("LocationOverrideInfo", FALSE)) {
/**
 * Represents override info for ad level LocationExtension overrides.
 */
class LocationOverrideInfo {
  /**
   * @access public
   * @var integer
   */
  public $radius;

  /**
   * @access public
   * @var tnsLocationOverrideInfoRadiusUnits
   */
  public $radiusUnits;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LocationOverrideInfo";
  }

  public function __construct($radius = NULL, $radiusUnits = NULL) {
    if(get_parent_class('LocationOverrideInfo')) parent::__construct();
    $this->radius = $radius;
    $this->radiusUnits = $radiusUnits;
  }
}}

if (!class_exists("OverrideInfo", FALSE)) {
/**
 * Represents additional override info with which to augment the override
 * extension.
 */
class OverrideInfo {
  /**
   * @access public
   * @var LocationOverrideInfo
   */
  public $LocationOverrideInfo;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "OverrideInfo";
  }

  public function __construct($LocationOverrideInfo = NULL) {
    if(get_parent_class('OverrideInfo')) parent::__construct();
    $this->LocationOverrideInfo = $LocationOverrideInfo;
  }
}}

if (!class_exists("Paging", FALSE)) {
/**
 * Specifies what kind of paging wanted for the result of a get.
 */
class Paging {
  /**
   * @access public
   * @var integer
   */
  public $startIndex;

  /**
   * @access public
   * @var integer
   */
  public $numberResults;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Paging";
  }

  public function __construct($startIndex = NULL, $numberResults = NULL) {
    if(get_parent_class('Paging')) parent::__construct();
    $this->startIndex = $startIndex;
    $this->numberResults = $numberResults;
  }
}}

if (!class_exists("SoapRequestHeader", FALSE)) {
/**
 * Defines the required and optional elements within the header of a SOAP request.
 */
class SoapRequestHeader {
  /**
   * @access public
   * @var string
   */
  public $applicationToken;

  /**
   * @access public
   * @var string
   */
  public $authToken;

  /**
   * @access public
   * @var string
   */
  public $clientCustomerId;

  /**
   * @access public
   * @var string
   */
  public $clientEmail;

  /**
   * @access public
   * @var string
   */
  public $developerToken;

  /**
   * @access public
   * @var string
   */
  public $userAgent;

  /**
   * @access public
   * @var boolean
   */
  public $validateOnly;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SoapHeader";
  }

  public function __construct($applicationToken = NULL, $authToken = NULL, $clientCustomerId = NULL, $clientEmail = NULL, $developerToken = NULL, $userAgent = NULL, $validateOnly = NULL) {
    if(get_parent_class('SoapRequestHeader')) parent::__construct();
    $this->applicationToken = $applicationToken;
    $this->authToken = $authToken;
    $this->clientCustomerId = $clientCustomerId;
    $this->clientEmail = $clientEmail;
    $this->developerToken = $developerToken;
    $this->userAgent = $userAgent;
    $this->validateOnly = $validateOnly;
  }
}}

if (!class_exists("SoapResponseHeader", FALSE)) {
/**
 * Defines the elements within the header of a SOAP response.
 */
class SoapResponseHeader {
  /**
   * @access public
   * @var string
   */
  public $requestId;

  /**
   * @access public
   * @var integer
   */
  public $operations;

  /**
   * @access public
   * @var integer
   */
  public $responseTime;

  /**
   * @access public
   * @var integer
   */
  public $units;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SoapResponseHeader";
  }

  public function __construct($requestId = NULL, $operations = NULL, $responseTime = NULL, $units = NULL) {
    if(get_parent_class('SoapResponseHeader')) parent::__construct();
    $this->requestId = $requestId;
    $this->operations = $operations;
    $this->responseTime = $responseTime;
    $this->units = $units;
  }
}}

if (!class_exists("AdExtension", FALSE)) {
/**
 * Base class for AdExtension objects. An AdExtension is an extension to
 * an existing ad or metadata that will process into an extension.
 * The class is concrete, so ad extensions can be added/removed to campaigns
 * by referring to the id.
 */
class AdExtension {
  /**
   * @access public
   * @var integer
   */
  public $id;

  /**
   * @access public
   * @var string
   */
  public $AdExtensionType;

  private $_parameterMap = array (
    "AdExtension.Type" => "AdExtensionType",
  );

  /**
   * Provided for setting non-php-standard named variables
   * @param $var Variable name to set
   * @param $value Value to set
   */
  public function __set($var, $value) { $this->{$this->_parameterMap[$var]} = $value; }

  /**
   * Provided for getting non-php-standard named variables
   * @param $var Variable name to get.
   * @return mixed Variable value
   */
  public function __get($var) {
    if (!array_key_exists($var, $this->_parameterMap)) {
      return NULL;
    } else {
      return $this->{$this->_parameterMap[$var]};
    }
  }

  /**
   * Provided for getting non-php-standard named variables
   * @return array parameter map
   */
  protected function getParameterMap() {
    return $this->_parameterMap;
    }

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtension";
  }

  public function __construct($id = NULL, $AdExtensionType = NULL) {
    if(get_parent_class('AdExtension')) parent::__construct();
    $this->id = $id;
    $this->AdExtensionType = $AdExtensionType;
  }
}}

if (!class_exists("AdExtensionOverrideSelector", FALSE)) {
/**
 * Specifies criteria for selecting a set of AdExtensionOverrides.
 */
class AdExtensionOverrideSelector {
  /**
   * @access public
   * @var integer[]
   */
  public $campaignIds;

  /**
   * @access public
   * @var integer[]
   */
  public $adIds;

  /**
   * @access public
   * @var integer[]
   */
  public $adExtensionIds;

  /**
   * @access public
   * @var tnsAdExtensionOverrideStatus[]
   */
  public $statuses;

  /**
   * @access public
   * @var Paging
   */
  public $paging;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverrideSelector";
  }

  public function __construct($campaignIds = NULL, $adIds = NULL, $adExtensionIds = NULL, $statuses = NULL, $paging = NULL) {
    if(get_parent_class('AdExtensionOverrideSelector')) parent::__construct();
    $this->campaignIds = $campaignIds;
    $this->adIds = $adIds;
    $this->adExtensionIds = $adExtensionIds;
    $this->statuses = $statuses;
    $this->paging = $paging;
  }
}}

if (!class_exists("ComparableValue", FALSE)) {
/**
 * Comparable types for constructing ranges with.
 */
class ComparableValue {
  /**
   * @access public
   * @var string
   */
  public $ComparableValueType;

  private $_parameterMap = array (
    "ComparableValue.Type" => "ComparableValueType",
  );

  /**
   * Provided for setting non-php-standard named variables
   * @param $var Variable name to set
   * @param $value Value to set
   */
  public function __set($var, $value) { $this->{$this->_parameterMap[$var]} = $value; }

  /**
   * Provided for getting non-php-standard named variables
   * @param $var Variable name to get.
   * @return mixed Variable value
   */
  public function __get($var) {
    if (!array_key_exists($var, $this->_parameterMap)) {
      return NULL;
    } else {
      return $this->{$this->_parameterMap[$var]};
    }
  }

  /**
   * Provided for getting non-php-standard named variables
   * @return array parameter map
   */
  protected function getParameterMap() {
    return $this->_parameterMap;
    }

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ComparableValue";
  }

  public function __construct($ComparableValueType = NULL) {
    if(get_parent_class('ComparableValue')) parent::__construct();
    $this->ComparableValueType = $ComparableValueType;
  }
}}

if (!class_exists("Stats", FALSE)) {
/**
 * Statistics about an ad or criterion within an adgroup or campaign.
 */
class Stats {
  /**
   * @access public
   * @var string
   */
  public $startDate;

  /**
   * @access public
   * @var string
   */
  public $endDate;

  /**
   * @access public
   * @var tnsStatsNetwork
   */
  public $network;

  /**
   * @access public
   * @var integer
   */
  public $clicks;

  /**
   * @access public
   * @var integer
   */
  public $impressions;

  /**
   * @access public
   * @var Money
   */
  public $cost;

  /**
   * @access public
   * @var double
   */
  public $averagePosition;

  /**
   * @access public
   * @var Money
   */
  public $averageCpc;

  /**
   * @access public
   * @var Money
   */
  public $averageCpm;

  /**
   * @access public
   * @var double
   */
  public $ctr;

  /**
   * @access public
   * @var integer
   */
  public $conversions;

  /**
   * @access public
   * @var double
   */
  public $conversionRate;

  /**
   * @access public
   * @var Money
   */
  public $costPerConversion;

  /**
   * @access public
   * @var integer
   */
  public $conversionsManyPerClick;

  /**
   * @access public
   * @var double
   */
  public $conversionRateManyPerClick;

  /**
   * @access public
   * @var Money
   */
  public $costPerConversionManyPerClick;

  /**
   * @access public
   * @var string
   */
  public $StatsType;

  private $_parameterMap = array (
    "Stats.Type" => "StatsType",
  );

  /**
   * Provided for setting non-php-standard named variables
   * @param $var Variable name to set
   * @param $value Value to set
   */
  public function __set($var, $value) { $this->{$this->_parameterMap[$var]} = $value; }

  /**
   * Provided for getting non-php-standard named variables
   * @param $var Variable name to get.
   * @return mixed Variable value
   */
  public function __get($var) {
    if (!array_key_exists($var, $this->_parameterMap)) {
      return NULL;
    } else {
      return $this->{$this->_parameterMap[$var]};
    }
  }

  /**
   * Provided for getting non-php-standard named variables
   * @return array parameter map
   */
  protected function getParameterMap() {
    return $this->_parameterMap;
    }

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Stats";
  }

  public function __construct($startDate = NULL, $endDate = NULL, $network = NULL, $clicks = NULL, $impressions = NULL, $cost = NULL, $averagePosition = NULL, $averageCpc = NULL, $averageCpm = NULL, $ctr = NULL, $conversions = NULL, $conversionRate = NULL, $costPerConversion = NULL, $conversionsManyPerClick = NULL, $conversionRateManyPerClick = NULL, $costPerConversionManyPerClick = NULL, $StatsType = NULL) {
    if(get_parent_class('Stats')) parent::__construct();
    $this->startDate = $startDate;
    $this->endDate = $endDate;
    $this->network = $network;
    $this->clicks = $clicks;
    $this->impressions = $impressions;
    $this->cost = $cost;
    $this->averagePosition = $averagePosition;
    $this->averageCpc = $averageCpc;
    $this->averageCpm = $averageCpm;
    $this->ctr = $ctr;
    $this->conversions = $conversions;
    $this->conversionRate = $conversionRate;
    $this->costPerConversion = $costPerConversion;
    $this->conversionsManyPerClick = $conversionsManyPerClick;
    $this->conversionRateManyPerClick = $conversionRateManyPerClick;
    $this->costPerConversionManyPerClick = $costPerConversionManyPerClick;
    $this->StatsType = $StatsType;
  }
}}

if (!class_exists("AdExtensionOverride", FALSE)) {
/**
 * Represents an ad level ad extension override. An ad extension override
 * specifies the ad extension that must be used if the ad is served with
 * any ad extension data.
 */
class AdExtensionOverride {
  /**
   * @access public
   * @var integer
   */
  public $adId;

  /**
   * @access public
   * @var AdExtension
   */
  public $adExtension;

  /**
   * @access public
   * @var OverrideInfo
   */
  public $overrideInfo;

  /**
   * @access public
   * @var tnsAdExtensionOverrideStatus
   */
  public $status;

  /**
   * @access public
   * @var tnsAdExtensionOverrideApprovalStatus
   */
  public $approvalStatus;

  /**
   * @access public
   * @var AdExtensionOverrideStats
   */
  public $stats;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverride";
  }

  public function __construct($adId = NULL, $adExtension = NULL, $overrideInfo = NULL, $status = NULL, $approvalStatus = NULL, $stats = NULL) {
    if(get_parent_class('AdExtensionOverride')) parent::__construct();
    $this->adId = $adId;
    $this->adExtension = $adExtension;
    $this->overrideInfo = $overrideInfo;
    $this->status = $status;
    $this->approvalStatus = $approvalStatus;
    $this->stats = $stats;
  }
}}

if (!class_exists("ApiError", FALSE)) {
/**
 * A service api error base class that provides error details.
 * 1) the OGNL field path is provided for parsers.
 * 2) the OGNL field path with debug comments easily helps track causes.
 */
class ApiError {
  /**
   * @access public
   * @var string
   */
  public $fieldPath;

  /**
   * @access public
   * @var string
   */
  public $trigger;

  /**
   * @access public
   * @var string
   */
  public $ApiErrorType;

  private $_parameterMap = array (
    "ApiError.Type" => "ApiErrorType",
  );

  /**
   * Provided for setting non-php-standard named variables
   * @param $var Variable name to set
   * @param $value Value to set
   */
  public function __set($var, $value) { $this->{$this->_parameterMap[$var]} = $value; }

  /**
   * Provided for getting non-php-standard named variables
   * @param $var Variable name to get.
   * @return mixed Variable value
   */
  public function __get($var) {
    if (!array_key_exists($var, $this->_parameterMap)) {
      return NULL;
    } else {
      return $this->{$this->_parameterMap[$var]};
    }
  }

  /**
   * Provided for getting non-php-standard named variables
   * @return array parameter map
   */
  protected function getParameterMap() {
    return $this->_parameterMap;
    }

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ApiError";
  }

  public function __construct($fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('ApiError')) parent::__construct();
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("ApplicationException", FALSE)) {
/**
 * Base class for exceptions.
 */
class ApplicationException {
  /**
   * @access public
   * @var string
   */
  public $message;

  /**
   * @access public
   * @var string
   */
  public $ApplicationExceptionType;

  private $_parameterMap = array (
    "ApplicationException.Type" => "ApplicationExceptionType",
  );

  /**
   * Provided for setting non-php-standard named variables
   * @param $var Variable name to set
   * @param $value Value to set
   */
  public function __set($var, $value) { $this->{$this->_parameterMap[$var]} = $value; }

  /**
   * Provided for getting non-php-standard named variables
   * @param $var Variable name to get.
   * @return mixed Variable value
   */
  public function __get($var) {
    if (!array_key_exists($var, $this->_parameterMap)) {
      return NULL;
    } else {
      return $this->{$this->_parameterMap[$var]};
    }
  }

  /**
   * Provided for getting non-php-standard named variables
   * @return array parameter map
   */
  protected function getParameterMap() {
    return $this->_parameterMap;
    }

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ApplicationException";
  }

  public function __construct($message = NULL, $ApplicationExceptionType = NULL) {
    if(get_parent_class('ApplicationException')) parent::__construct();
    $this->message = $message;
    $this->ApplicationExceptionType = $ApplicationExceptionType;
  }
}}

if (!class_exists("ListReturnValue", FALSE)) {
/**
 * Base list return value type.
 */
class ListReturnValue {
  /**
   * @access public
   * @var string
   */
  public $ListReturnValueType;

  private $_parameterMap = array (
    "ListReturnValue.Type" => "ListReturnValueType",
  );

  /**
   * Provided for setting non-php-standard named variables
   * @param $var Variable name to set
   * @param $value Value to set
   */
  public function __set($var, $value) { $this->{$this->_parameterMap[$var]} = $value; }

  /**
   * Provided for getting non-php-standard named variables
   * @param $var Variable name to get.
   * @return mixed Variable value
   */
  public function __get($var) {
    if (!array_key_exists($var, $this->_parameterMap)) {
      return NULL;
    } else {
      return $this->{$this->_parameterMap[$var]};
    }
  }

  /**
   * Provided for getting non-php-standard named variables
   * @return array parameter map
   */
  protected function getParameterMap() {
    return $this->_parameterMap;
    }

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ListReturnValue";
  }

  public function __construct($ListReturnValueType = NULL) {
    if(get_parent_class('ListReturnValue')) parent::__construct();
    $this->ListReturnValueType = $ListReturnValueType;
  }
}}

if (!class_exists("Operation", FALSE)) {
/**
 * This represents an operation that includes an operator and an operand
 * specified type.
 */
class Operation {
  /**
   * @access public
   * @var tnsOperator
   */
  public $operator;

  /**
   * @access public
   * @var string
   */
  public $OperationType;

  private $_parameterMap = array (
    "Operation.Type" => "OperationType",
  );

  /**
   * Provided for setting non-php-standard named variables
   * @param $var Variable name to set
   * @param $value Value to set
   */
  public function __set($var, $value) { $this->{$this->_parameterMap[$var]} = $value; }

  /**
   * Provided for getting non-php-standard named variables
   * @param $var Variable name to get.
   * @return mixed Variable value
   */
  public function __get($var) {
    if (!array_key_exists($var, $this->_parameterMap)) {
      return NULL;
    } else {
      return $this->{$this->_parameterMap[$var]};
    }
  }

  /**
   * Provided for getting non-php-standard named variables
   * @return array parameter map
   */
  protected function getParameterMap() {
    return $this->_parameterMap;
    }

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Operation";
  }

  public function __construct($operator = NULL, $OperationType = NULL) {
    if(get_parent_class('Operation')) parent::__construct();
    $this->operator = $operator;
    $this->OperationType = $OperationType;
  }
}}

if (!class_exists("Page", FALSE)) {
/**
 * Contains the results from a get call.
 */
class Page {
  /**
   * @access public
   * @var integer
   */
  public $totalNumEntries;

  /**
   * @access public
   * @var string
   */
  public $PageType;

  private $_parameterMap = array (
    "Page.Type" => "PageType",
  );

  /**
   * Provided for setting non-php-standard named variables
   * @param $var Variable name to set
   * @param $value Value to set
   */
  public function __set($var, $value) { $this->{$this->_parameterMap[$var]} = $value; }

  /**
   * Provided for getting non-php-standard named variables
   * @param $var Variable name to get.
   * @return mixed Variable value
   */
  public function __get($var) {
    if (!array_key_exists($var, $this->_parameterMap)) {
      return NULL;
    } else {
      return $this->{$this->_parameterMap[$var]};
    }
  }

  /**
   * Provided for getting non-php-standard named variables
   * @return array parameter map
   */
  protected function getParameterMap() {
    return $this->_parameterMap;
    }

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Page";
  }

  public function __construct($totalNumEntries = NULL, $PageType = NULL) {
    if(get_parent_class('Page')) parent::__construct();
    $this->totalNumEntries = $totalNumEntries;
    $this->PageType = $PageType;
  }
}}

if (!class_exists("AdExtensionOverrideApprovalStatus", FALSE)) {
/**
 * Approval status for the AdExtensionOverride
 */
class AdExtensionOverrideApprovalStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverride.ApprovalStatus";
  }

  public function __construct() {
    if(get_parent_class('AdExtensionOverrideApprovalStatus')) parent::__construct();
  }
}}

if (!class_exists("AdExtensionOverrideStatus", FALSE)) {
/**
 * Status of the AdExtensionOverride
 */
class AdExtensionOverrideStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverride.Status";
  }

  public function __construct() {
    if(get_parent_class('AdExtensionOverrideStatus')) parent::__construct();
  }
}}

if (!class_exists("AdExtensionOverrideErrorReason", FALSE)) {
/**
 * Cannot override an ad that is already overriden with an adextension
 */
class AdExtensionOverrideErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverrideError.Reason";
  }

  public function __construct() {
    if(get_parent_class('AdExtensionOverrideErrorReason')) parent::__construct();
  }
}}

if (!class_exists("AuthenticationErrorReason", FALSE)) {
/**
 * The single reason for the authentication failure.
 */
class AuthenticationErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AuthenticationError.Reason";
  }

  public function __construct() {
    if(get_parent_class('AuthenticationErrorReason')) parent::__construct();
  }
}}

if (!class_exists("AuthorizationErrorReason", FALSE)) {
/**
 * The reasons for the database error.
 */
class AuthorizationErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AuthorizationError.Reason";
  }

  public function __construct() {
    if(get_parent_class('AuthorizationErrorReason')) parent::__construct();
  }
}}

if (!class_exists("ClientTermsErrorReason", FALSE)) {
/**
 * Enums for the various reasons an error can be thrown as a result of
 * ClientTerms violation.
 */
class ClientTermsErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ClientTermsError.Reason";
  }

  public function __construct() {
    if(get_parent_class('ClientTermsErrorReason')) parent::__construct();
  }
}}

if (!class_exists("DistinctErrorReason", FALSE)) {
/**
 * The reasons for the validation error.
 */
class DistinctErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "DistinctError.Reason";
  }

  public function __construct() {
    if(get_parent_class('DistinctErrorReason')) parent::__construct();
  }
}}

if (!class_exists("EntityNotFoundReason", FALSE)) {
/**
 * The specified id refered to an entity which either doesn't exist or is not accessible to the
 * customer. e.g. campaign belongs to another customer.
 */
class EntityNotFoundReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "EntityNotFound.Reason";
  }

  public function __construct() {
    if(get_parent_class('EntityNotFoundReason')) parent::__construct();
  }
}}

if (!class_exists("InternalApiErrorReason", FALSE)) {
/**
 * The single reason for the internal API error.
 */
class InternalApiErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "InternalApiError.Reason";
  }

  public function __construct() {
    if(get_parent_class('InternalApiErrorReason')) parent::__construct();
  }
}}

if (!class_exists("LocationExtensionSource", FALSE)) {
/**
 * From manual entry in adwords frontend
 */
class LocationExtensionSource {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LocationExtension.Source";
  }

  public function __construct() {
    if(get_parent_class('LocationExtensionSource')) parent::__construct();
  }
}}

if (!class_exists("LocationOverrideInfoRadiusUnits", FALSE)) {
/**
 * Radius units
 */
class LocationOverrideInfoRadiusUnits {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LocationOverrideInfo.RadiusUnits";
  }

  public function __construct() {
    if(get_parent_class('LocationOverrideInfoRadiusUnits')) parent::__construct();
  }
}}

if (!class_exists("NotEmptyErrorReason", FALSE)) {
/**
 * The reasons for the validation error.
 */
class NotEmptyErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NotEmptyError.Reason";
  }

  public function __construct() {
    if(get_parent_class('NotEmptyErrorReason')) parent::__construct();
  }
}}

if (!class_exists("NotWhitelistedErrorReason", FALSE)) {
/**
 * The single reason for the whitelist error.
 */
class NotWhitelistedErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NotWhitelistedError.Reason";
  }

  public function __construct() {
    if(get_parent_class('NotWhitelistedErrorReason')) parent::__construct();
  }
}}

if (!class_exists("NullErrorReason", FALSE)) {
/**
 * The reasons for the validation error.
 */
class NullErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NullError.Reason";
  }

  public function __construct() {
    if(get_parent_class('NullErrorReason')) parent::__construct();
  }
}}

if (!class_exists("Operator", FALSE)) {
/**
 * This represents an operator that may be presented to an adsapi service.
 */
class Operator {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Operator";
  }

  public function __construct() {
    if(get_parent_class('Operator')) parent::__construct();
  }
}}

if (!class_exists("OperatorErrorReason", FALSE)) {
/**
 * The reasons for the validation error.
 */
class OperatorErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "OperatorError.Reason";
  }

  public function __construct() {
    if(get_parent_class('OperatorErrorReason')) parent::__construct();
  }
}}

if (!class_exists("QuotaCheckErrorReason", FALSE)) {
/**
 * Enums for all the reasons an error can be thrown to the user during
 * billing quota checks.
 */
class QuotaCheckErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "QuotaCheckError.Reason";
  }

  public function __construct() {
    if(get_parent_class('QuotaCheckErrorReason')) parent::__construct();
  }
}}

if (!class_exists("QuotaExceededErrorReason", FALSE)) {
/**
 * The single reason for the quota error.
 */
class QuotaExceededErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "QuotaExceededError.Reason";
  }

  public function __construct() {
    if(get_parent_class('QuotaExceededErrorReason')) parent::__construct();
  }
}}

if (!class_exists("RangeErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class RangeErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "RangeError.Reason";
  }

  public function __construct() {
    if(get_parent_class('RangeErrorReason')) parent::__construct();
  }
}}

if (!class_exists("ReadOnlyErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class ReadOnlyErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ReadOnlyError.Reason";
  }

  public function __construct() {
    if(get_parent_class('ReadOnlyErrorReason')) parent::__construct();
  }
}}

if (!class_exists("RequiredErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class RequiredErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "RequiredError.Reason";
  }

  public function __construct() {
    if(get_parent_class('RequiredErrorReason')) parent::__construct();
  }
}}

if (!class_exists("SizeLimitErrorReason", FALSE)) {
/**
 * The reasons for Ad Scheduling errors.
 */
class SizeLimitErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SizeLimitError.Reason";
  }

  public function __construct() {
    if(get_parent_class('SizeLimitErrorReason')) parent::__construct();
  }
}}

if (!class_exists("StatsNetwork", FALSE)) {
/**
 * Ad network.
 */
class StatsNetwork {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Stats.Network";
  }

  public function __construct() {
    if(get_parent_class('StatsNetwork')) parent::__construct();
  }
}}

if (!class_exists("StringLengthErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class StringLengthErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "StringLengthError.Reason";
  }

  public function __construct() {
    if(get_parent_class('StringLengthErrorReason')) parent::__construct();
  }
}}

if (!class_exists("AdExtensionOverrideServiceGet", FALSE)) {
/**
 * Returns a list of AdExtensionOverrides based on an
 * AdExtensionOverrideSelector.
 * @param selector the selector specifying the query
 * @return the page containing the AdExtensionOverride which meet the
 * criteria specified by the selector
 */
class AdExtensionOverrideServiceGet {
  /**
   * @access public
   * @var AdExtensionOverrideSelector
   */
  public $selector;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "get";
  }

  public function __construct($selector = NULL) {
    if(get_parent_class('AdExtensionOverrideServiceGet')) parent::__construct();
    $this->selector = $selector;
  }
}}

if (!class_exists("AdExtensionOverrideServiceGetResponse", FALSE)) {
/**
 * 
 */
class AdExtensionOverrideServiceGetResponse {
  /**
   * @access public
   * @var AdExtensionOverridePage
   */
  public $rval;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "getResponse";
  }

  public function __construct($rval = NULL) {
    if(get_parent_class('AdExtensionOverrideServiceGetResponse')) parent::__construct();
    $this->rval = $rval;
  }
}}

if (!class_exists("AdExtensionOverrideServiceMutate", FALSE)) {
/**
 * <span class="constraint ContentsNotNull">This field must not contain {@code null} elements.</span>
 * <span class="constraint DistinctIds">Elements in this field must have distinct IDs for following {@link Operator}s : SET, REMOVE.</span>
 * <span class="constraint NotEmpty">This field must contain at least one element.</span>
 * <span class="constraint Required">This field is required and should not be {@code null}.</span>
 * <span class="constraint SuppoprtedOperators">The following {@link Operator}s are supported: ADD, REMOVE.</span>
 * 
 * 
 * 
 * Applies the list of mutate operations.
 * Conditions for adding an ad-level AdExtension override using an AdExtension
 * under the Ad's campaign:
 * 1) If the text ad has never been overridden, an AdExtension override may
 * be set on the creative using an extension under the campaign, along with
 * any override info.
 * 2) If the text ad has been overriden before, the latitude/longitude of the
 * new extension override must be the same as the previous override and
 * no override info can be specified (ie. the override info will inherit from
 * the previous override info).
 * @param operations the operations to apply. The same ad extension override
 * cannot be specified in more than one operation.
 * @return the applied ad extension overrides
 */
class AdExtensionOverrideServiceMutate {
  /**
   * @access public
   * @var AdExtensionOverrideOperation[]
   */
  public $operations;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "mutate";
  }

  public function __construct($operations = NULL) {
    if(get_parent_class('AdExtensionOverrideServiceMutate')) parent::__construct();
    $this->operations = $operations;
  }
}}

if (!class_exists("AdExtensionOverrideServiceMutateResponse", FALSE)) {
/**
 * 
 */
class AdExtensionOverrideServiceMutateResponse {
  /**
   * @access public
   * @var AdExtensionOverrideReturnValue
   */
  public $rval;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "mutateResponse";
  }

  public function __construct($rval = NULL) {
    if(get_parent_class('AdExtensionOverrideServiceMutateResponse')) parent::__construct();
    $this->rval = $rval;
  }
}}

if (!class_exists("AdExtensionOverrideError", FALSE)) {
/**
 * AdExtensionOverride errors.
 */
class AdExtensionOverrideError extends ApiError {
  /**
   * @access public
   * @var tnsAdExtensionOverrideErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverrideError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AdExtensionOverrideError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("AdExtensionOverrideStats", FALSE)) {
/**
 * Represents stats specific to AdExtensionOverrides.
 */
class AdExtensionOverrideStats extends Stats {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverrideStats";
  }

  public function __construct($startDate = NULL, $endDate = NULL, $network = NULL, $clicks = NULL, $impressions = NULL, $cost = NULL, $averagePosition = NULL, $averageCpc = NULL, $averageCpm = NULL, $ctr = NULL, $conversions = NULL, $conversionRate = NULL, $costPerConversion = NULL, $conversionsManyPerClick = NULL, $conversionRateManyPerClick = NULL, $costPerConversionManyPerClick = NULL, $StatsType = NULL) {
    if(get_parent_class('AdExtensionOverrideStats')) parent::__construct();
    $this->startDate = $startDate;
    $this->endDate = $endDate;
    $this->network = $network;
    $this->clicks = $clicks;
    $this->impressions = $impressions;
    $this->cost = $cost;
    $this->averagePosition = $averagePosition;
    $this->averageCpc = $averageCpc;
    $this->averageCpm = $averageCpm;
    $this->ctr = $ctr;
    $this->conversions = $conversions;
    $this->conversionRate = $conversionRate;
    $this->costPerConversion = $costPerConversion;
    $this->conversionsManyPerClick = $conversionsManyPerClick;
    $this->conversionRateManyPerClick = $conversionRateManyPerClick;
    $this->costPerConversionManyPerClick = $costPerConversionManyPerClick;
    $this->StatsType = $StatsType;
  }
}}

if (!class_exists("AuthenticationError", FALSE)) {
/**
 * Errors returned when Authentication failed.
 */
class AuthenticationError extends ApiError {
  /**
   * @access public
   * @var tnsAuthenticationErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AuthenticationError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AuthenticationError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("AuthorizationError", FALSE)) {
/**
 * Errors that are thrown due to an authorization problem.
 */
class AuthorizationError extends ApiError {
  /**
   * @access public
   * @var tnsAuthorizationErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AuthorizationError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AuthorizationError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("ClientTermsError", FALSE)) {
/**
 * Encapsulates the errors thrown during client terms checks for adwords.
 */
class ClientTermsError extends ApiError {
  /**
   * @access public
   * @var tnsClientTermsErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ClientTermsError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('ClientTermsError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("DistinctError", FALSE)) {
/**
 * Errors related to distinct ids or content.
 */
class DistinctError extends ApiError {
  /**
   * @access public
   * @var tnsDistinctErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "DistinctError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('DistinctError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("EntityNotFound", FALSE)) {
/**
 * An id did not correspond to an entity, or it referred to an entity which does not belong to the
 * customer.
 */
class EntityNotFound extends ApiError {
  /**
   * @access public
   * @var tnsEntityNotFoundReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "EntityNotFound";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('EntityNotFound')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("InternalApiError", FALSE)) {
/**
 * Indicates that a server-side error has occured. {@code InternalApiError}s
 * are generally not the result of an invalid request or message sent by the
 * client.
 */
class InternalApiError extends ApiError {
  /**
   * @access public
   * @var tnsInternalApiErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "InternalApiError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('InternalApiError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("LocationExtension", FALSE)) {
/**
 * Location based ad extension.
 */
class LocationExtension extends AdExtension {
  /**
   * @access public
   * @var Address
   */
  public $address;

  /**
   * @access public
   * @var GeoPoint
   */
  public $geoPoint;

  /**
   * @access public
   * @var base64Binary
   */
  public $encodedLocation;

  /**
   * @access public
   * @var string
   */
  public $companyName;

  /**
   * @access public
   * @var string
   */
  public $phoneNumber;

  /**
   * @access public
   * @var tnsLocationExtensionSource
   */
  public $source;

  /**
   * @access public
   * @var integer
   */
  public $iconMediaId;

  /**
   * @access public
   * @var integer
   */
  public $imageMediaId;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LocationExtension";
  }

  public function __construct($address = NULL, $geoPoint = NULL, $encodedLocation = NULL, $companyName = NULL, $phoneNumber = NULL, $source = NULL, $iconMediaId = NULL, $imageMediaId = NULL, $id = NULL, $AdExtensionType = NULL) {
    if(get_parent_class('LocationExtension')) parent::__construct();
    $this->address = $address;
    $this->geoPoint = $geoPoint;
    $this->encodedLocation = $encodedLocation;
    $this->companyName = $companyName;
    $this->phoneNumber = $phoneNumber;
    $this->source = $source;
    $this->iconMediaId = $iconMediaId;
    $this->imageMediaId = $imageMediaId;
    $this->id = $id;
    $this->AdExtensionType = $AdExtensionType;
  }
}}

if (!class_exists("Money", FALSE)) {
/**
 * Represents a money amount.
 */
class Money extends ComparableValue {
  /**
   * @access public
   * @var integer
   */
  public $microAmount;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Money";
  }

  public function __construct($microAmount = NULL, $ComparableValueType = NULL) {
    if(get_parent_class('Money')) parent::__construct();
    $this->microAmount = $microAmount;
    $this->ComparableValueType = $ComparableValueType;
  }
}}

if (!class_exists("NotEmptyError", FALSE)) {
/**
 * A list of all errors associated with the @NotEmpty constraints.
 */
class NotEmptyError extends ApiError {
  /**
   * @access public
   * @var tnsNotEmptyErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NotEmptyError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('NotEmptyError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("NotWhitelistedError", FALSE)) {
/**
 * Indicates that the customer is not whitelisted for accessing the API.
 */
class NotWhitelistedError extends ApiError {
  /**
   * @access public
   * @var tnsNotWhitelistedErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NotWhitelistedError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('NotWhitelistedError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("NullError", FALSE)) {
/**
 * Errors associated with contents not null constraint.
 */
class NullError extends ApiError {
  /**
   * @access public
   * @var tnsNullErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NullError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('NullError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("NumberValue", FALSE)) {
/**
 * Number value types for constructing number valued ranges.
 */
class NumberValue extends ComparableValue {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NumberValue";
  }

  public function __construct($ComparableValueType = NULL) {
    if(get_parent_class('NumberValue')) parent::__construct();
    $this->ComparableValueType = $ComparableValueType;
  }
}}

if (!class_exists("OperatorError", FALSE)) {
/**
 * A list of all errors associated with the @SupportedOperators constraints.
 */
class OperatorError extends ApiError {
  /**
   * @access public
   * @var tnsOperatorErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "OperatorError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('OperatorError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("QuotaCheckError", FALSE)) {
/**
 * Encapsulates the errors thrown during developer quota checks.
 */
class QuotaCheckError extends ApiError {
  /**
   * @access public
   * @var tnsQuotaCheckErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "QuotaCheckError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('QuotaCheckError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("QuotaExceededError", FALSE)) {
/**
 * Represents Quota Exceeded Error.
 */
class QuotaExceededError extends ApiError {
  /**
   * @access public
   * @var tnsQuotaExceededErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "QuotaExceededError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('QuotaExceededError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("RangeError", FALSE)) {
/**
 * A list of all errors associated with the Range constraint.
 */
class RangeError extends ApiError {
  /**
   * @access public
   * @var tnsRangeErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "RangeError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('RangeError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("ReadOnlyError", FALSE)) {
/**
 * A list of all errors associated with the @ReadOnly constraint.
 */
class ReadOnlyError extends ApiError {
  /**
   * @access public
   * @var tnsReadOnlyErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ReadOnlyError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('ReadOnlyError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("RequiredError", FALSE)) {
/**
 * Errors due to missing required field.
 */
class RequiredError extends ApiError {
  /**
   * @access public
   * @var tnsRequiredErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "RequiredError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('RequiredError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("SizeLimitError", FALSE)) {
/**
 * Indicates that the number of entries in the request or response exceeds the system limit.
 */
class SizeLimitError extends ApiError {
  /**
   * @access public
   * @var tnsSizeLimitErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SizeLimitError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('SizeLimitError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("StringLengthError", FALSE)) {
/**
 * A list of all errors associated with the @ContentsString constraint.
 */
class StringLengthError extends ApiError {
  /**
   * @access public
   * @var tnsStringLengthErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "StringLengthError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('StringLengthError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("AdExtensionOverrideOperation", FALSE)) {
/**
 * AdExtensionOverride service operation.
 */
class AdExtensionOverrideOperation extends Operation {
  /**
   * @access public
   * @var AdExtensionOverride
   */
  public $operand;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverrideOperation";
  }

  public function __construct($operand = NULL, $operator = NULL, $OperationType = NULL) {
    if(get_parent_class('AdExtensionOverrideOperation')) parent::__construct();
    $this->operand = $operand;
    $this->operator = $operator;
    $this->OperationType = $OperationType;
  }
}}

if (!class_exists("AdExtensionOverridePage", FALSE)) {
/**
 * Represents a page of {@link AdExtensionOverride}s resulting from the query
 * done by {@link AdExtensionOverrideService}.
 */
class AdExtensionOverridePage extends Page {
  /**
   * @access public
   * @var AdExtensionOverride[]
   */
  public $entries;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverridePage";
  }

  public function __construct($entries = NULL, $totalNumEntries = NULL, $PageType = NULL) {
    if(get_parent_class('AdExtensionOverridePage')) parent::__construct();
    $this->entries = $entries;
    $this->totalNumEntries = $totalNumEntries;
    $this->PageType = $PageType;
  }
}}

if (!class_exists("AdExtensionOverrideReturnValue", FALSE)) {
/**
 * A container for return values from AdExtensionOverrideService.mutate().
 */
class AdExtensionOverrideReturnValue extends ListReturnValue {
  /**
   * @access public
   * @var AdExtensionOverride[]
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdExtensionOverrideReturnValue";
  }

  public function __construct($value = NULL, $ListReturnValueType = NULL) {
    if(get_parent_class('AdExtensionOverrideReturnValue')) parent::__construct();
    $this->value = $value;
    $this->ListReturnValueType = $ListReturnValueType;
  }
}}

if (!class_exists("ApiException", FALSE)) {
/**
 * Exception class for holding a list of service errors.
 */
class ApiException extends ApplicationException {
  /**
   * @access public
   * @var ApiError[]
   */
  public $errors;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ApiException";
  }

  public function __construct($errors = NULL, $message = NULL, $ApplicationExceptionType = NULL) {
    if(get_parent_class('ApiException')) parent::__construct();
    $this->errors = $errors;
    $this->message = $message;
    $this->ApplicationExceptionType = $ApplicationExceptionType;
  }
}}

if (!class_exists("DoubleValue", FALSE)) {
/**
 * Number value type for constructing double valued ranges.
 */
class DoubleValue extends NumberValue {
  /**
   * @access public
   * @var double
   */
  public $number;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "DoubleValue";
  }

  public function __construct($number = NULL, $ComparableValueType = NULL) {
    if(get_parent_class('DoubleValue')) parent::__construct();
    $this->number = $number;
    $this->ComparableValueType = $ComparableValueType;
  }
}}

if (!class_exists("LongValue", FALSE)) {
/**
 * Number value type for constructing long valued ranges.
 */
class LongValue extends NumberValue {
  /**
   * @access public
   * @var integer
   */
  public $number;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LongValue";
  }

  public function __construct($number = NULL, $ComparableValueType = NULL) {
    if(get_parent_class('LongValue')) parent::__construct();
    $this->number = $number;
    $this->ComparableValueType = $ComparableValueType;
  }
}}

if (!class_exists("AdExtensionOverrideService")) {
/**
 * AdExtensionOverrideService
 * @author WSDLInterpreter
 */
class AdExtensionOverrideService extends AdWordsSoapClient {
  /**
   * Default class map for wsdl=>php
   * @access private
   * @var array
   */
  public static $classmap = array(
    "getResponse" => "AdExtensionOverrideServiceGetResponse",
    "get" => "AdExtensionOverrideServiceGet",
    "mutate" => "AdExtensionOverrideServiceMutate",
    "mutateResponse" => "AdExtensionOverrideServiceMutateResponse",
    "DateTime" => "AdWordsDateTime",
    "Target" => "AdWordsTarget",
    "SoapHeader" => "SoapRequestHeader",
    "AdExtensionOverrideError" => "AdExtensionOverrideError",
    "ApiError" => "ApiError",
    "AdExtensionOverrideStats" => "AdExtensionOverrideStats",
    "Stats" => "Stats",
    "Address" => "Address",
    "AuthenticationError" => "AuthenticationError",
    "AuthorizationError" => "AuthorizationError",
    "ClientTermsError" => "ClientTermsError",
    "DistinctError" => "DistinctError",
    "DoubleValue" => "DoubleValue",
    "NumberValue" => "NumberValue",
    "EntityNotFound" => "EntityNotFound",
    "GeoPoint" => "GeoPoint",
    "InternalApiError" => "InternalApiError",
    "LocationExtension" => "LocationExtension",
    "AdExtension" => "AdExtension",
    "LocationOverrideInfo" => "LocationOverrideInfo",
    "LongValue" => "LongValue",
    "Money" => "Money",
    "ComparableValue" => "ComparableValue",
    "NotEmptyError" => "NotEmptyError",
    "NotWhitelistedError" => "NotWhitelistedError",
    "NullError" => "NullError",
    "OperatorError" => "OperatorError",
    "OverrideInfo" => "OverrideInfo",
    "Paging" => "Paging",
    "QuotaCheckError" => "QuotaCheckError",
    "QuotaExceededError" => "QuotaExceededError",
    "RangeError" => "RangeError",
    "ReadOnlyError" => "ReadOnlyError",
    "RequiredError" => "RequiredError",
    "SizeLimitError" => "SizeLimitError",
    "SoapResponseHeader" => "SoapResponseHeader",
    "StringLengthError" => "StringLengthError",
    "AdExtensionOverrideSelector" => "AdExtensionOverrideSelector",
    "AdExtensionOverride" => "AdExtensionOverride",
    "AdExtensionOverrideOperation" => "AdExtensionOverrideOperation",
    "Operation" => "Operation",
    "AdExtensionOverridePage" => "AdExtensionOverridePage",
    "Page" => "Page",
    "AdExtensionOverrideReturnValue" => "AdExtensionOverrideReturnValue",
    "ListReturnValue" => "ListReturnValue",
    "ApiException" => "ApiException",
    "ApplicationException" => "ApplicationException",
    "AdExtensionOverride.ApprovalStatus" => "AdExtensionOverrideApprovalStatus",
    "AdExtensionOverride.Status" => "AdExtensionOverrideStatus",
    "AdExtensionOverrideError.Reason" => "AdExtensionOverrideErrorReason",
    "AuthenticationError.Reason" => "AuthenticationErrorReason",
    "AuthorizationError.Reason" => "AuthorizationErrorReason",
    "ClientTermsError.Reason" => "ClientTermsErrorReason",
    "DistinctError.Reason" => "DistinctErrorReason",
    "EntityNotFound.Reason" => "EntityNotFoundReason",
    "InternalApiError.Reason" => "InternalApiErrorReason",
    "LocationExtension.Source" => "LocationExtensionSource",
    "LocationOverrideInfo.RadiusUnits" => "LocationOverrideInfoRadiusUnits",
    "NotEmptyError.Reason" => "NotEmptyErrorReason",
    "NotWhitelistedError.Reason" => "NotWhitelistedErrorReason",
    "NullError.Reason" => "NullErrorReason",
    "Operator" => "Operator",
    "OperatorError.Reason" => "OperatorErrorReason",
    "QuotaCheckError.Reason" => "QuotaCheckErrorReason",
    "QuotaExceededError.Reason" => "QuotaExceededErrorReason",
    "RangeError.Reason" => "RangeErrorReason",
    "ReadOnlyError.Reason" => "ReadOnlyErrorReason",
    "RequiredError.Reason" => "RequiredErrorReason",
    "SizeLimitError.Reason" => "SizeLimitErrorReason",
    "Stats.Network" => "StatsNetwork",
    "StringLengthError.Reason" => "StringLengthErrorReason",
  );

  /**
   * Constructor using wsdl location and options array
   * @param string $wsdl WSDL location for this service
   * @param array $options Options for the SoapClient
   */
  public function __construct($wsdl=null, $options, $user) {
    $options["classmap"] = AdExtensionOverrideService::$classmap;
    parent::__construct($wsdl, $options, $user, 'AdExtensionOverrideService', 'https://adwords.google.com/api/adwords/cm/v200909');
  }

  /**
   * Returns a list of AdExtensionOverrides based on an
   * AdExtensionOverrideSelector.
   * @param selector the selector specifying the query
   * @return the page containing the AdExtensionOverride which meet the
   * criteria specified by the selector
   */
  public function get($selector) {
    $arg = new AdExtensionOverrideServiceGet($selector);
    $result = $this->__soapCall("get", array($arg));
    return $result->rval;
  }


  /**
   * <span class="constraint ContentsNotNull">This field must not contain {@code null} elements.</span>
   * <span class="constraint DistinctIds">Elements in this field must have distinct IDs for following {@link Operator}s : SET, REMOVE.</span>
   * <span class="constraint NotEmpty">This field must contain at least one element.</span>
   * <span class="constraint Required">This field is required and should not be {@code null}.</span>
   * <span class="constraint SuppoprtedOperators">The following {@link Operator}s are supported: ADD, REMOVE.</span>
   * 
   * 
   * 
   * Applies the list of mutate operations.
   * Conditions for adding an ad-level AdExtension override using an AdExtension
   * under the Ad's campaign:
   * 1) If the text ad has never been overridden, an AdExtension override may
   * be set on the creative using an extension under the campaign, along with
   * any override info.
   * 2) If the text ad has been overriden before, the latitude/longitude of the
   * new extension override must be the same as the previous override and
   * no override info can be specified (ie. the override info will inherit from
   * the previous override info).
   * @param operations the operations to apply. The same ad extension override
   * cannot be specified in more than one operation.
   * @return the applied ad extension overrides
   */
  public function mutate($operations) {
    $arg = new AdExtensionOverrideServiceMutate($operations);
    $result = $this->__soapCall("mutate", array($arg));
    return $result->rval;
  }


}}

?>