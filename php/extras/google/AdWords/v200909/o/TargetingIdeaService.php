<?php
/**
 * Contains all client objects for the TargetingIdeaService service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("Paging", FALSE)) {
/**
 * Specifies what kind of paging wanted for the result of a get.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("PolicyViolationErrorPart", FALSE)) {
/**
 * Points to a substring within an ad field or criterion.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class PolicyViolationErrorPart {
  /**
   * @access public
   * @var integer
   */
  public $index;

  /**
   * @access public
   * @var integer
   */
  public $length;

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
    return "PolicyViolationError.Part";
  }

  public function __construct($index = NULL, $length = NULL) {
    if(get_parent_class('PolicyViolationErrorPart')) parent::__construct();
    $this->index = $index;
    $this->length = $length;
  }
}}

if (!class_exists("PolicyViolationKey", FALSE)) {
/**
 * Key of the violation. The key is used for referring to a violation when
 * filing an exemption request.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class PolicyViolationKey {
  /**
   * @access public
   * @var string
   */
  public $policyName;

  /**
   * @access public
   * @var string
   */
  public $violatingText;

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
    return "PolicyViolationKey";
  }

  public function __construct($policyName = NULL, $violatingText = NULL) {
    if(get_parent_class('PolicyViolationKey')) parent::__construct();
    $this->policyName = $policyName;
    $this->violatingText = $violatingText;
  }
}}

if (!class_exists("SoapRequestHeader", FALSE)) {
/**
 * Defines the required and optional elements within the header of a SOAP request.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("ComparableValue", FALSE)) {
/**
 * Comparable types for constructing ranges with.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("Criterion", FALSE)) {
/**
 * Represents any criterion (e.g. keyword, placement).
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class Criterion {
  /**
   * @access public
   * @var integer
   */
  public $id;

  /**
   * @access public
   * @var string
   */
  public $CriterionType;

  private $_parameterMap = array (
    "Criterion.Type" => "CriterionType",
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
    return "Criterion";
  }

  public function __construct($id = NULL, $CriterionType = NULL) {
    if(get_parent_class('Criterion')) parent::__construct();
    $this->id = $id;
    $this->CriterionType = $CriterionType;
  }
}}

if (!class_exists("AdWordsTarget", FALSE)) {
/**
 * Target abstract class.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class AdWordsTarget {
  /**
   * @access public
   * @var string
   */
  public $TargetType;

  private $_parameterMap = array (
    "Target.Type" => "TargetType",
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
    return "Target";
  }

  public function __construct($TargetType = NULL) {
    if(get_parent_class('AdWordsTarget')) parent::__construct();
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("ApiError", FALSE)) {
/**
 * A service api error base class that provides error details.
 * 1) the OGNL field path is provided for parsers.
 * 2) the OGNL field path with debug comments easily helps track causes.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("AdGroupCriterionErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class AdGroupCriterionErrorReason {
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
    return "AdGroupCriterionError.Reason";
  }

  public function __construct() {
    if(get_parent_class('AdGroupCriterionErrorReason')) parent::__construct();
  }
}}

if (!class_exists("AdGroupCriterionLimitExceededCriteriaLimitType", FALSE)) {
/**
 * The entity type that exceeded the limit.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class AdGroupCriterionLimitExceededCriteriaLimitType {
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
    return "AdGroupCriterionLimitExceeded.CriteriaLimitType";
  }

  public function __construct() {
    if(get_parent_class('AdGroupCriterionLimitExceededCriteriaLimitType')) parent::__construct();
  }
}}

if (!class_exists("AgeTargetAge", FALSE)) {
/**
 * Age segments.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class AgeTargetAge {
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
    return "AgeTarget.Age";
  }

  public function __construct() {
    if(get_parent_class('AgeTargetAge')) parent::__construct();
  }
}}

if (!class_exists("AuthenticationErrorReason", FALSE)) {
/**
 * The single reason for the authentication failure.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("BiddingErrorReason", FALSE)) {
/**
 * Reason for bidding error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class BiddingErrorReason {
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
    return "BiddingError.Reason";
  }

  public function __construct() {
    if(get_parent_class('BiddingErrorReason')) parent::__construct();
  }
}}

if (!class_exists("BudgetErrorReason", FALSE)) {
/**
 * The reasons for the budget error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class BudgetErrorReason {
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
    return "BudgetError.Reason";
  }

  public function __construct() {
    if(get_parent_class('BudgetErrorReason')) parent::__construct();
  }
}}

if (!class_exists("ClientTermsErrorReason", FALSE)) {
/**
 * Enums for the various reasons an error can be thrown as a result of
 * ClientTerms violation.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("DatabaseErrorReason", FALSE)) {
/**
 * The reasons for the database error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class DatabaseErrorReason {
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
    return "DatabaseError.Reason";
  }

  public function __construct() {
    if(get_parent_class('DatabaseErrorReason')) parent::__construct();
  }
}}

if (!class_exists("DateErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class DateErrorReason {
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
    return "DateError.Reason";
  }

  public function __construct() {
    if(get_parent_class('DateErrorReason')) parent::__construct();
  }
}}

if (!class_exists("DayOfWeek", FALSE)) {
/**
 * Days of the week.
 * 
 * 
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class DayOfWeek {
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
    return "DayOfWeek";
  }

  public function __construct() {
    if(get_parent_class('DayOfWeek')) parent::__construct();
  }
}}

if (!class_exists("DistinctErrorReason", FALSE)) {
/**
 * The reasons for the validation error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("EntityCountLimitExceededReason", FALSE)) {
/**
 * Limits at various levels of the account.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class EntityCountLimitExceededReason {
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
    return "EntityCountLimitExceeded.Reason";
  }

  public function __construct() {
    if(get_parent_class('EntityCountLimitExceededReason')) parent::__construct();
  }
}}

if (!class_exists("EntityNotFoundReason", FALSE)) {
/**
 * The specified id refered to an entity which either doesn't exist or is not accessible to the
 * customer. e.g. campaign belongs to another customer.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("GenderTargetGender", FALSE)) {
/**
 * Gender segments.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class GenderTargetGender {
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
    return "GenderTarget.Gender";
  }

  public function __construct() {
    if(get_parent_class('GenderTargetGender')) parent::__construct();
  }
}}

if (!class_exists("InternalApiErrorReason", FALSE)) {
/**
 * The single reason for the internal API error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("KeywordMatchType", FALSE)) {
/**
 * Match type of a keyword. i.e. the way we match a keyword string with
 * search queries.
 * 
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class KeywordMatchType {
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
    return "KeywordMatchType";
  }

  public function __construct() {
    if(get_parent_class('KeywordMatchType')) parent::__construct();
  }
}}

if (!class_exists("MinuteOfHour", FALSE)) {
/**
 * Minutes in an hour.  Currently only 0, 15, 30, and 45 are supported
 * 
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class MinuteOfHour {
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
    return "MinuteOfHour";
  }

  public function __construct() {
    if(get_parent_class('MinuteOfHour')) parent::__construct();
  }
}}

if (!class_exists("NetworkCoverageType", FALSE)) {
/**
 * Network coverage types.
 * 
 * 
 * 
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class NetworkCoverageType {
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
    return "NetworkCoverageType";
  }

  public function __construct() {
    if(get_parent_class('NetworkCoverageType')) parent::__construct();
  }
}}

if (!class_exists("NotEmptyErrorReason", FALSE)) {
/**
 * The reasons for the validation error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("PlatformType", FALSE)) {
/**
 * Platform types.
 * 
 * 
 * 
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class PlatformType {
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
    return "PlatformType";
  }

  public function __construct() {
    if(get_parent_class('PlatformType')) parent::__construct();
  }
}}

if (!class_exists("ProximityTargetDistanceUnits", FALSE)) {
/**
 * The radius distance is expressed in either kilometers or miles.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class ProximityTargetDistanceUnits {
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
    return "ProximityTarget.DistanceUnits";
  }

  public function __construct() {
    if(get_parent_class('ProximityTargetDistanceUnits')) parent::__construct();
  }
}}

if (!class_exists("QuotaCheckErrorReason", FALSE)) {
/**
 * Enums for all the reasons an error can be thrown to the user during
 * billing quota checks.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("StatsQueryErrorReason", FALSE)) {
/**
 * The reasons for errors when querying for stats.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class StatsQueryErrorReason {
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
    return "StatsQueryError.Reason";
  }

  public function __construct() {
    if(get_parent_class('StatsQueryErrorReason')) parent::__construct();
  }
}}

if (!class_exists("StringLengthErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("AdFormatSpec", FALSE)) {
/**
 * A placement response object which provides information about one of the ad formats
 * supported by a placement.
 */
class AdFormatSpec {
  /**
   * @access public
   * @var tnsSiteConstantsAdFormat
   */
  public $format;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdFormatSpec";
  }

  public function __construct($format = NULL) {
    if(get_parent_class('AdFormatSpec')) parent::__construct();
    $this->format = $format;
  }
}}

if (!class_exists("CollectionSizeError", FALSE)) {
/**
 * A list of all errors associated with the @ContentsSize constraint.
 */
class CollectionSizeError extends ApiError {
  /**
   * @access public
   * @var tnsCollectionSizeErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "CollectionSizeError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('CollectionSizeError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("InStreamAdInfo", FALSE)) {
/**
 * Information specific to the instream ad format. Instream ads are video ads
 * which play as part of the delivery of video content, either before, during,
 * or after the content itself.
 */
class InStreamAdInfo {
  /**
   * @access public
   * @var integer
   */
  public $maxAdDuration;

  /**
   * @access public
   * @var integer
   */
  public $minAdDuration;

  /**
   * @access public
   * @var integer
   */
  public $medianAdDuration;

  /**
   * @access public
   * @var double
   */
  public $preRollPercent;

  /**
   * @access public
   * @var double
   */
  public $midRollPercent;

  /**
   * @access public
   * @var double
   */
  public $postRollPercent;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "InStreamAdInfo";
  }

  public function __construct($maxAdDuration = NULL, $minAdDuration = NULL, $medianAdDuration = NULL, $preRollPercent = NULL, $midRollPercent = NULL, $postRollPercent = NULL) {
    if(get_parent_class('InStreamAdInfo')) parent::__construct();
    $this->maxAdDuration = $maxAdDuration;
    $this->minAdDuration = $minAdDuration;
    $this->medianAdDuration = $medianAdDuration;
    $this->preRollPercent = $preRollPercent;
    $this->midRollPercent = $midRollPercent;
    $this->postRollPercent = $postRollPercent;
  }
}}

if (!class_exists("LongComparisonOperation", FALSE)) {
/**
 * Pojo representing integer comparison operations. This is usually used within
 * a particular
 * {@link com.google.ads.api.services.targetingideas.search.SearchParameter} to
 * specify the valid values requested for the specific
 * {@link com.google.ads.api.services.targetingideas.attributes.Attribute}.
 */
class LongComparisonOperation {
  /**
   * @access public
   * @var integer
   */
  public $minimum;

  /**
   * @access public
   * @var integer
   */
  public $maximum;

  /**
   * @access public
   * @var integer[]
   */
  public $excludes;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LongComparisonOperation";
  }

  public function __construct($minimum = NULL, $maximum = NULL, $excludes = NULL) {
    if(get_parent_class('LongComparisonOperation')) parent::__construct();
    $this->minimum = $minimum;
    $this->maximum = $maximum;
    $this->excludes = $excludes;
  }
}}

if (!class_exists("MonthlySearchVolume", FALSE)) {
/**
 * A keyword response value representing search volume for a single month. An
 * instance with a {@code null} count is valid, indicating that the information
 * is unavailable.
 */
class MonthlySearchVolume {
  /**
   * @access public
   * @var integer
   */
  public $year;

  /**
   * @access public
   * @var integer
   */
  public $month;

  /**
   * @access public
   * @var integer
   */
  public $count;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "MonthlySearchVolume";
  }

  public function __construct($year = NULL, $month = NULL, $count = NULL) {
    if(get_parent_class('MonthlySearchVolume')) parent::__construct();
    $this->year = $year;
    $this->month = $month;
    $this->count = $count;
  }
}}

if (!class_exists("TargetingIdeaError", FALSE)) {
/**
 * Base error class for the
 * {@link com.google.ads.api.services.targetingideas.TargetingIdeaService}.
 */
class TargetingIdeaError extends ApiError {
  /**
   * @access public
   * @var tnsTargetingIdeaErrorReason
   */
  public $reason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TargetingIdeaError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('TargetingIdeaError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("WebpageDescriptor", FALSE)) {
/**
 * Basic information about a webpage.
 */
class WebpageDescriptor {
  /**
   * @access public
   * @var string
   */
  public $url;

  /**
   * @access public
   * @var string
   */
  public $title;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "WebpageDescriptor";
  }

  public function __construct($url = NULL, $title = NULL) {
    if(get_parent_class('WebpageDescriptor')) parent::__construct();
    $this->url = $url;
    $this->title = $title;
  }
}}

if (!class_exists("Range", FALSE)) {
/**
 * Represents a range of values that has either an upper or a lower bound.
 */
class Range {
  /**
   * @access public
   * @var ComparableValue
   */
  public $min;

  /**
   * @access public
   * @var ComparableValue
   */
  public $max;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Range";
  }

  public function __construct($min = NULL, $max = NULL) {
    if(get_parent_class('Range')) parent::__construct();
    $this->min = $min;
    $this->max = $max;
  }
}}

if (!class_exists("SearchParameter", FALSE)) {
/**
 * A set of {@link SearchParameter}s are supplied to the
 * {@link com.google.ads.api.services.targetingideas.TargetingIdeaSelector}
 * to specify how the user wants to filter the set of all possible
 * {@link com.google.ads.api.services.targetingideas.TargetingIdea}s.
 * 
 * There is a {@link SearchParameter} for all types of inputs.
 * {@link SearchParameter}s can conceptually be broken down into two types.
 * <ul>
 * <li>Input {@link SearchParameter}s provide the seed information from which
 * ideas should be generated or statistic information is desired
 * (eg. {@link RelatedToKeywordSearchParameter},
 * {@link RelatedToUrlSearchParameter}, etc).
 * Such {@link SearchParameters} are required for valid requests.</li>
 * <li>Filter {@link SearchParameter}s are used to trim down the results based
 * on {@link com.google.ads.api.services.targetingideas.attributes.Attribute}
 * related information (eg. {@link CompetitionSearchParameter}, etc.).</li>
 * </ul><p>
 * 
 * A request should only contain one instance of each {@link SearchParameter}
 * <p>One or more of the following {@link SearchParameter}s are required:<br/>
 * <ul><li>{@link KeywordCategoryIdSearchParameter}</li>
 * <li>{@link RelatedToKeywordSearchParameter}</li>
 * <li>{@link RelatedToUrlSearchParameter}</li>
 * <li>{@link SeedAdGroupIdSearchParameter}</li>
 * </ul><p>
 * <p><b>{@link IdeaType} KEYWORD supports following {@link SearchParameter}s:</b><br/>
 * <ul>
 * <li>{@link AverageTargetedMonthlySearchesSearchParameter}</li>
 * <li>{@link CompetitionSearchParameter}</li>
 * <li>{@link CountryTargetSearchParameter}</li>
 * <li>{@link ExcludedKeywordSearchParameter}</li>
 * <li>{@link GlobalMonthlySearchesSearchParameter}</li>
 * <li>{@link IncludeAdultContentSearchParameter}</li>
 * <li>{@link KeywordCategoryIdSearchParameter}</li>
 * <li>{@link KeywordMatchTypeSearchParameter}</li>
 * <li>{@link LanguageTargetSearchParameter}</li>
 * <li>{@link MobileSearchParameter}</li>
 * <li>{@link NgramGroupsSearchParameter}</li>
 * <li>{@link RelatedToKeywordSearchParameter}</li>
 * <li>{@link RelatedToUrlSearchParameter}</li>
 * <li>{@link SeedAdGroupIdSearchParameter}</li>
 * </ul><p>
 * <p><b>{@link IdeaType} PLACEMENT supports following {@link SearchParameter}s:</b><br/>
 * <ul>
 * <li>{@link AdTypeSearchParameter}</li>
 * <li>{@link CountryTargetSearchParameter}</li>
 * <li>{@link LanguageTargetSearchParameter}</li>
 * <li>{@link PlacementTypeSearchParameter}</li>
 * <li>{@link RelatedToKeywordSearchParameter}</li>
 * <li>{@link RelatedToUrlSearchParameter}</li>
 * </ul><p>
 */
class SearchParameter {
  /**
   * @access public
   * @var string
   */
  public $SearchParameterType;

  private $_parameterMap = array (
    "SearchParameter.Type" => "SearchParameterType",
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
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SearchParameter";
  }

  public function __construct($SearchParameterType = NULL) {
    if(get_parent_class('SearchParameter')) parent::__construct();
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("TargetingIdeaSelector", FALSE)) {
/**
 * A descriptor for finding {@link TargetingIdea}s that match the specified
 * criteria.
 */
class TargetingIdeaSelector {
  /**
   * @access public
   * @var SearchParameter[]
   */
  public $searchParameters;

  /**
   * @access public
   * @var tnsIdeaType
   */
  public $ideaType;

  /**
   * @access public
   * @var tnsRequestType
   */
  public $requestType;

  /**
   * @access public
   * @var tnsAttributeType[]
   */
  public $requestedAttributeTypes;

  /**
   * @access public
   * @var Paging
   */
  public $paging;

  /**
   * @access public
   * @var string
   */
  public $localeCode;

  /**
   * @access public
   * @var string
   */
  public $currencyCode;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TargetingIdeaSelector";
  }

  public function __construct($searchParameters = NULL, $ideaType = NULL, $requestType = NULL, $requestedAttributeTypes = NULL, $paging = NULL, $localeCode = NULL, $currencyCode = NULL) {
    if(get_parent_class('TargetingIdeaSelector')) parent::__construct();
    $this->searchParameters = $searchParameters;
    $this->ideaType = $ideaType;
    $this->requestType = $requestType;
    $this->requestedAttributeTypes = $requestedAttributeTypes;
    $this->paging = $paging;
    $this->localeCode = $localeCode;
    $this->currencyCode = $currencyCode;
  }
}}

if (!class_exists("Attribute", FALSE)) {
/**
 * {@link Attribute}s encompass the core information about a particular
 * {@link com.google.ads.api.services.targetingideas.TargetingIdea}. Some
 * attributes are for {@code KEYWORD} {@link IdeaType}s, some are for
 * {@code PLACEMENT} {@link IdeaType}s, and some are for both. Ultimately, an
 * {@link Attribute} instance simply wraps an actual value of interest. For
 * example, {@link KeywordAttribute} wraps the keyword itself, while a
 * {@link BooleanAttribute} simply wraps a boolean describing some information
 * about the keyword idea.
 */
class Attribute {
  /**
   * @access public
   * @var string
   */
  public $AttributeType;

  private $_parameterMap = array (
    "Attribute.Type" => "AttributeType",
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
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Attribute";
  }

  public function __construct($AttributeType = NULL) {
    if(get_parent_class('Attribute')) parent::__construct();
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("Type_AttributeMapEntry", FALSE)) {
/**
 * This represents an entry in a map with a key of type Type
 * and value of type Attribute.
 */
class Type_AttributeMapEntry {
  /**
   * @access public
   * @var tnsAttributeType
   */
  public $key;

  /**
   * @access public
   * @var Attribute
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Type_AttributeMapEntry";
  }

  public function __construct($key = NULL, $value = NULL) {
    if(get_parent_class('Type_AttributeMapEntry')) parent::__construct();
    $this->key = $key;
    $this->value = $value;
  }
}}

if (!class_exists("TargetingIdea", FALSE)) {
/**
 * Represents a {@link TargetingIdea} returned by search criteria specified in
 * the {@link TargetingIdeaSelector}.
 */
class TargetingIdea {
  /**
   * @access public
   * @var Type_AttributeMapEntry[]
   */
  public $data;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TargetingIdea";
  }

  public function __construct($data = NULL) {
    if(get_parent_class('TargetingIdea')) parent::__construct();
    $this->data = $data;
  }
}}

if (!class_exists("TargetingIdeaPage", FALSE)) {
/**
 * Contains a subset of {@link TargetingIdea}s from the search criteria
 * specified by a {@link TargetingIdeaSelector}.
 */
class TargetingIdeaPage {
  /**
   * @access public
   * @var integer
   */
  public $totalNumEntries;

  /**
   * @access public
   * @var TargetingIdea[]
   */
  public $entries;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TargetingIdeaPage";
  }

  public function __construct($totalNumEntries = NULL, $entries = NULL) {
    if(get_parent_class('TargetingIdeaPage')) parent::__construct();
    $this->totalNumEntries = $totalNumEntries;
    $this->entries = $entries;
  }
}}

if (!class_exists("AttributeType", FALSE)) {
/**
 * Represents the type of
 * {@link com.google.ads.api.services.targetingideas.attributes.Attribute}.
 * 
 * 
 * <p><b>{@link IdeaType} KEYWORD supports the following {@link AttributeType}s:</b><br/>
 * <ul><li>{@link #AD_SHARE}</li>
 * <li>{@link #AVERAGE_TARGETED_MONTHLY_SEARCHES}</li>
 * <li>{@link #COMPETITION}</li>
 * <li>{@link #EXTRACTED_FROM_WEBPAGE}</li>
 * <li>{@link #GLOBAL_MONTHLY_SEARCHES}</li>
 * <li>{@link #IDEA_TYPE}</li>
 * <li>{@link #KEYWORD}</li>
 * <li>{@link #KEYWORD_CATEGORY}</li>
 * <li>{@link #NGRAM_GROUP}</li>
 * <li>{@link #SEARCH_SHARE}</li>
 * <li>{@link #TARGETED_MONTHLY_SEARCHES}</li>
 * </ul>
 * <p><b>{@link IdeaType} PLACEMENT supports the following {@link AttributeType}s:</b><br/>
 * <ul><li>{@link #APPROX_CONTENT_IMPRESSIONS_PER_DAY}</li>
 * <li>{@link #FORMATS}</li>
 * <li>{@link #IDEA_TYPE}</li>
 * <li>{@link #IN_STREAM_AD_INFO}</li>
 * <li>{@link #PLACEMENT}</li>
 * <li>{@link #PLACEMENT_CATEGORY}</li>
 * <li>{@link #PLACEMENT_NAME}</li>
 * <li>{@link #PLACEMENT_TYPE}</li>
 * <li>{@link #PUBLISHER_DESCRIPTION}</li>
 * <li>{@link #SAMPLE_URL}</li>
 * </ul>
 */
class AttributeType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AttributeType";
  }

  public function __construct() {
    if(get_parent_class('AttributeType')) parent::__construct();
  }
}}

if (!class_exists("CollectionSizeErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class CollectionSizeErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "CollectionSizeError.Reason";
  }

  public function __construct() {
    if(get_parent_class('CollectionSizeErrorReason')) parent::__construct();
  }
}}

if (!class_exists("CompetitionSearchParameterLevel", FALSE)) {
/**
 * An enumeration of possible values to be used in conjunction with the
 * {@link CompetitionSearchParameter} to specify the granularity of
 * competition to be filtered.
 */
class CompetitionSearchParameterLevel {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "CompetitionSearchParameter.Level";
  }

  public function __construct() {
    if(get_parent_class('CompetitionSearchParameterLevel')) parent::__construct();
  }
}}

if (!class_exists("IdeaType", FALSE)) {
/**
 * Represents the type of idea.
 */
class IdeaType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "IdeaType";
  }

  public function __construct() {
    if(get_parent_class('IdeaType')) parent::__construct();
  }
}}

if (!class_exists("RequestType", FALSE)) {
/**
 * Represents the type of the request.
 */
class RequestType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "RequestType";
  }

  public function __construct() {
    if(get_parent_class('RequestType')) parent::__construct();
  }
}}

if (!class_exists("SiteConstantsAdFormat", FALSE)) {
/**
 * Enumerates the ad formats which can be reported in search results.
 */
class SiteConstantsAdFormat {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SiteConstants.AdFormat";
  }

  public function __construct() {
    if(get_parent_class('SiteConstantsAdFormat')) parent::__construct();
  }
}}

if (!class_exists("SiteConstantsAdType", FALSE)) {
/**
 * Enumerates the ad types available for constraining searches.  Each corresponds
 * to one or more values from {@link AdFormat}.
 */
class SiteConstantsAdType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SiteConstants.AdType";
  }

  public function __construct() {
    if(get_parent_class('SiteConstantsAdType')) parent::__construct();
  }
}}

if (!class_exists("SiteConstantsPlacementType", FALSE)) {
/**
 * A placement request and response value indicating the type of site or other medium
 * (for example, a web page, in a feed, in a video) where ads will appear.
 */
class SiteConstantsPlacementType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SiteConstants.PlacementType";
  }

  public function __construct() {
    if(get_parent_class('SiteConstantsPlacementType')) parent::__construct();
  }
}}

if (!class_exists("TargetingIdeaErrorReason", FALSE)) {
/**
 * An enumeration of
 * {@link com.google.ads.api.services.targetingideas.TargetingIdeaService}
 * specific errors.
 */
class TargetingIdeaErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TargetingIdeaError.Reason";
  }

  public function __construct() {
    if(get_parent_class('TargetingIdeaErrorReason')) parent::__construct();
  }
}}

if (!class_exists("TargetingIdeaServiceGet", FALSE)) {
/**
 * <span class="constraint Required">This field is required and should not be {@code null}.</span>
 * 
 * 
 * 
 * Returns a page of ideas that match the query described by the specified
 * {@link TargetingIdeaSelector}.
 * 
 * The selector must specify a Paging value, with {@code numberResults} set to 800 or less.
 * 
 * @param selector Query describing the types of results to return when
 * finding matches (similar keyword ideas/placement ideas).
 * @return A {@link TargetingIdeaPage} of results, that is a subset of the
 * list of possible ideas meeting the criteria of the
 * {@link TargetingIdeaSelector}.
 * @throws ApiException If problems occurred while querying for ideas.
 */
class TargetingIdeaServiceGet {
  /**
   * @access public
   * @var TargetingIdeaSelector
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
    if(get_parent_class('TargetingIdeaServiceGet')) parent::__construct();
    $this->selector = $selector;
  }
}}

if (!class_exists("TargetingIdeaServiceGetResponse", FALSE)) {
/**
 * 
 */
class TargetingIdeaServiceGetResponse {
  /**
   * @access public
   * @var TargetingIdeaPage
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
    if(get_parent_class('TargetingIdeaServiceGetResponse')) parent::__construct();
    $this->rval = $rval;
  }
}}

if (!class_exists("getBulkKeywordIdeas", FALSE)) {
/**
 * <span class="constraint Required">This field is required and should not be {@code null}.</span>
 * 
 * 
 * 
 * Returns a page of ideas that match the query described by the specified
 * {@link TargetingIdeaSelector}.  This method is specialized for returning
 * bulk keyword ideas, and thus the selector must be set for
 * {@link com.google.ads.api.services.targetingideas.attributes.RequestType#IDEAS} and
 * {@link com.google.ads.api.services.targetingideas.attributes.IdeaType#KEYWORD}.
 * A limited, fixed set of attributes will be returned.<p>
 * 
 * A single-valued
 * {@link com.google.ads.api.services.targetingideas.search.RelatedToUrlSearchParameter}
 * must be supplied.  Single-valued
 * {@link com.google.ads.api.services.targetingideas.search.LanguageTargetSearchParameter} and
 * {@link com.google.ads.api.services.targetingideas.search.CountryTargetSearchParameter} are
 * both optional.  Other search parameters compatible with a keyword query may also be
 * supplied.<p>
 * 
 * The selector must specify a Paging value, with {@code numberResults} set to 500 or less.
 * Large result sets must be composed through multiple calls to this method, advancing the
 * Paging {@code startIndex} value by {@code numberResults} with each call.
 * 
 * @param selector Query describing the bulk keyword ideas to return.
 * @return A {@link TargetingIdeaPage} of results, that is a subset of the
 * list of possible ideas meeting the criteria of the
 * {@link TargetingIdeaSelector}.
 * @throws ApiException If problems occurred while querying for ideas.
 */
class getBulkKeywordIdeas {
  /**
   * @access public
   * @var TargetingIdeaSelector
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
    return "getBulkKeywordIdeas";
  }

  public function __construct($selector = NULL) {
    if(get_parent_class('getBulkKeywordIdeas')) parent::__construct();
    $this->selector = $selector;
  }
}}

if (!class_exists("getBulkKeywordIdeasResponse", FALSE)) {
/**
 * 
 */
class getBulkKeywordIdeasResponse {
  /**
   * @access public
   * @var TargetingIdeaPage
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
    return "getBulkKeywordIdeasResponse";
  }

  public function __construct($rval = NULL) {
    if(get_parent_class('getBulkKeywordIdeasResponse')) parent::__construct();
    $this->rval = $rval;
  }
}}

if (!class_exists("AdGroupCriterionError", FALSE)) {
/**
 * Base error class for Ad Group Criterion Service.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class AdGroupCriterionError extends ApiError {
  /**
   * @access public
   * @var tnsAdGroupCriterionErrorReason
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
    return "AdGroupCriterionError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AdGroupCriterionError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("AdScheduleTarget", FALSE)) {
/**
 * Immutable structure to hold an ad schedule target.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class AdScheduleTarget extends AdWordsTarget {
  /**
   * @access public
   * @var tnsDayOfWeek
   */
  public $dayOfWeek;

  /**
   * @access public
   * @var integer
   */
  public $startHour;

  /**
   * @access public
   * @var tnsMinuteOfHour
   */
  public $startMinute;

  /**
   * @access public
   * @var integer
   */
  public $endHour;

  /**
   * @access public
   * @var tnsMinuteOfHour
   */
  public $endMinute;

  /**
   * @access public
   * @var double
   */
  public $bidMultiplier;

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
    return "AdScheduleTarget";
  }

  public function __construct($dayOfWeek = NULL, $startHour = NULL, $startMinute = NULL, $endHour = NULL, $endMinute = NULL, $bidMultiplier = NULL, $TargetType = NULL) {
    if(get_parent_class('AdScheduleTarget')) parent::__construct();
    $this->dayOfWeek = $dayOfWeek;
    $this->startHour = $startHour;
    $this->startMinute = $startMinute;
    $this->endHour = $endHour;
    $this->endMinute = $endMinute;
    $this->bidMultiplier = $bidMultiplier;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("AuthenticationError", FALSE)) {
/**
 * Errors returned when Authentication failed.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("BiddingError", FALSE)) {
/**
 * Represents bidding errors.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class BiddingError extends ApiError {
  /**
   * @access public
   * @var tnsBiddingErrorReason
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
    return "BiddingError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('BiddingError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("BudgetError", FALSE)) {
/**
 * A list of all the error codes being used by the common budget domain package.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class BudgetError extends ApiError {
  /**
   * @access public
   * @var tnsBudgetErrorReason
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
    return "BudgetError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('BudgetError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("ClientTermsError", FALSE)) {
/**
 * Encapsulates the errors thrown during client terms checks for adwords.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("DatabaseError", FALSE)) {
/**
 * Errors that are thrown due to a database access problem.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class DatabaseError extends ApiError {
  /**
   * @access public
   * @var tnsDatabaseErrorReason
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
    return "DatabaseError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('DatabaseError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("DateError", FALSE)) {
/**
 * Errors associated with invalid dates and date ranges.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class DateError extends ApiError {
  /**
   * @access public
   * @var tnsDateErrorReason
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
    return "DateError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('DateError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("DistinctError", FALSE)) {
/**
 * Errors related to distinct ids or content.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("EntityCountLimitExceeded", FALSE)) {
/**
 * Signals that an entity count limit was exceeded for some level.
 * For example, too many criteria for a campaign.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class EntityCountLimitExceeded extends ApiError {
  /**
   * @access public
   * @var tnsEntityCountLimitExceededReason
   */
  public $reason;

  /**
   * @access public
   * @var string
   */
  public $enclosingId;

  /**
   * @access public
   * @var integer
   */
  public $limit;

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
    return "EntityCountLimitExceeded";
  }

  public function __construct($reason = NULL, $enclosingId = NULL, $limit = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('EntityCountLimitExceeded')) parent::__construct();
    $this->reason = $reason;
    $this->enclosingId = $enclosingId;
    $this->limit = $limit;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("EntityNotFound", FALSE)) {
/**
 * An id did not correspond to an entity, or it referred to an entity which does not belong to the
 * customer.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("Keyword", FALSE)) {
/**
 * A keyword.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class Keyword extends Criterion {
  /**
   * @access public
   * @var string
   */
  public $text;

  /**
   * @access public
   * @var tnsKeywordMatchType
   */
  public $matchType;

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
    return "Keyword";
  }

  public function __construct($text = NULL, $matchType = NULL, $id = NULL, $CriterionType = NULL) {
    if(get_parent_class('Keyword')) parent::__construct();
    $this->text = $text;
    $this->matchType = $matchType;
    $this->id = $id;
    $this->CriterionType = $CriterionType;
  }
}}

if (!class_exists("LanguageTarget", FALSE)) {
/**
 * Immutable structure to hold a language target.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class LanguageTarget extends AdWordsTarget {
  /**
   * @access public
   * @var string
   */
  public $languageCode;

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
    return "LanguageTarget";
  }

  public function __construct($languageCode = NULL, $TargetType = NULL) {
    if(get_parent_class('LanguageTarget')) parent::__construct();
    $this->languageCode = $languageCode;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("Money", FALSE)) {
/**
 * Represents a money amount.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("NetworkTarget", FALSE)) {
/**
 * Immutable structure to hold a network coverage target.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class NetworkTarget extends AdWordsTarget {
  /**
   * @access public
   * @var tnsNetworkCoverageType
   */
  public $networkCoverageType;

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
    return "NetworkTarget";
  }

  public function __construct($networkCoverageType = NULL, $TargetType = NULL) {
    if(get_parent_class('NetworkTarget')) parent::__construct();
    $this->networkCoverageType = $networkCoverageType;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("NotEmptyError", FALSE)) {
/**
 * A list of all errors associated with the @NotEmpty constraints.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("Placement", FALSE)) {
/**
 * A placement used for modifying bids for sites when targeting the content
 * network.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class Placement extends Criterion {
  /**
   * @access public
   * @var string
   */
  public $url;

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
    return "Placement";
  }

  public function __construct($url = NULL, $id = NULL, $CriterionType = NULL) {
    if(get_parent_class('Placement')) parent::__construct();
    $this->url = $url;
    $this->id = $id;
    $this->CriterionType = $CriterionType;
  }
}}

if (!class_exists("PlatformTarget", FALSE)) {
/**
 * Immutable structure to hold a platform target.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class PlatformTarget extends AdWordsTarget {
  /**
   * @access public
   * @var tnsPlatformType
   */
  public $platformType;

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
    return "PlatformTarget";
  }

  public function __construct($platformType = NULL, $TargetType = NULL) {
    if(get_parent_class('PlatformTarget')) parent::__construct();
    $this->platformType = $platformType;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("QuotaCheckError", FALSE)) {
/**
 * Encapsulates the errors thrown during developer quota checks.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("StatsQueryError", FALSE)) {
/**
 * Represents possible error codes when querying for stats.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class StatsQueryError extends ApiError {
  /**
   * @access public
   * @var tnsStatsQueryErrorReason
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
    return "StatsQueryError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('StatsQueryError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("StringLengthError", FALSE)) {
/**
 * A list of all errors associated with the @ContentsString constraint.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("DemographicTarget", FALSE)) {
/**
 * Abstract class to identify a demographic target.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class DemographicTarget extends AdWordsTarget {
  /**
   * @access public
   * @var integer
   */
  public $bidModifier;

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
    return "DemographicTarget";
  }

  public function __construct($bidModifier = NULL, $TargetType = NULL) {
    if(get_parent_class('DemographicTarget')) parent::__construct();
    $this->bidModifier = $bidModifier;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("GeoTarget", FALSE)) {
/**
 * Abstract class to identify a geographic target.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class GeoTarget extends AdWordsTarget {
  /**
   * @access public
   * @var boolean
   */
  public $excluded;

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
    return "GeoTarget";
  }

  public function __construct($excluded = NULL, $TargetType = NULL) {
    if(get_parent_class('GeoTarget')) parent::__construct();
    $this->excluded = $excluded;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("PolicyViolationError", FALSE)) {
/**
 * Represents violations of a single policy by some text in a field.
 * 
 * Violations of a single policy by the same string in multiple places
 * within a field is reported in one instace of this class and only one
 * exemption needs to be filed.
 * Violations of a single policy by two different strings is reported
 * as two separate instances of this class.
 * 
 * e.g. If 'ACME' violates 'capitalization' and occurs twice in a text ad it
 * would be represented by one instance. If the ad also contains 'INC' which
 * also violates 'capitalization' it would be represented in a separate
 * instance.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class PolicyViolationError extends ApiError {
  /**
   * @access public
   * @var PolicyViolationKey
   */
  public $key;

  /**
   * @access public
   * @var string
   */
  public $externalPolicyName;

  /**
   * @access public
   * @var string
   */
  public $externalPolicyUrl;

  /**
   * @access public
   * @var string
   */
  public $externalPolicyDescription;

  /**
   * @access public
   * @var boolean
   */
  public $isExemptable;

  /**
   * @access public
   * @var PolicyViolationErrorPart[]
   */
  public $violatingParts;

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
    return "PolicyViolationError";
  }

  public function __construct($key = NULL, $externalPolicyName = NULL, $externalPolicyUrl = NULL, $externalPolicyDescription = NULL, $isExemptable = NULL, $violatingParts = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('PolicyViolationError')) parent::__construct();
    $this->key = $key;
    $this->externalPolicyName = $externalPolicyName;
    $this->externalPolicyUrl = $externalPolicyUrl;
    $this->externalPolicyDescription = $externalPolicyDescription;
    $this->isExemptable = $isExemptable;
    $this->violatingParts = $violatingParts;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("ApiException", FALSE)) {
/**
 * Exception class for holding a list of service errors.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("AdFormatSpecListAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a list of {@link AdFormatSpec} values.
 */
class AdFormatSpecListAttribute extends Attribute {
  /**
   * @access public
   * @var AdFormatSpec[]
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdFormatSpecListAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('AdFormatSpecListAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("AdTypeSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code PLACEMENT} {@link IdeaType}s used to
 * filter the results by the {@link AdType}.
 * <p>This element is supported by following {@link IdeaType}s: PLACEMENT.
 * <p>This element is supported by following {@link RequestType}s: IDEAS.
 */
class AdTypeSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var tnsSiteConstantsAdType[]
   */
  public $adTypes;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdTypeSearchParameter";
  }

  public function __construct($adTypes = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('AdTypeSearchParameter')) parent::__construct();
    $this->adTypes = $adTypes;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("BooleanAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a boolean value.
 */
class BooleanAttribute extends Attribute {
  /**
   * @access public
   * @var boolean
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BooleanAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('BooleanAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("CompetitionSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code KEYWORD} {@link IdeaType}s used to
 * filter the results by the amount of competition (eg: LOW, MEDIUM, HIGH).
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class CompetitionSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var tnsCompetitionSearchParameterLevel[]
   */
  public $levels;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "CompetitionSearchParameter";
  }

  public function __construct($levels = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('CompetitionSearchParameter')) parent::__construct();
    $this->levels = $levels;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("DoubleAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a double value.
 */
class DoubleAttribute extends Attribute {
  /**
   * @access public
   * @var double
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "DoubleAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('DoubleAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("IdeaTypeAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains an {@link IdeaType} value. For example,
 * if a {@link com.google.ads.api.services.targetingideas.TargetingIdea}
 * represents a keyword idea, its {@link IdeaTypeAttribute} would contain a
 * {@code KEYWORD} {@link IdeaType}.
 */
class IdeaTypeAttribute extends Attribute {
  /**
   * @access public
   * @var tnsIdeaType
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "IdeaTypeAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('IdeaTypeAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("InStreamAdInfoAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains an {@link InStreamAdInfo} value.
 */
class InStreamAdInfoAttribute extends Attribute {
  /**
   * @access public
   * @var InStreamAdInfo
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "InStreamAdInfoAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('InStreamAdInfoAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("IncludeAdultContentSearchParameter", FALSE)) {
/**
 * {@link SearchParameter} that specifies whether adult content should be
 * returned.<p>
 * 
 * Presence of this {@link SearchParameter} will allow adult keywords
 * to be included in the results.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class IncludeAdultContentSearchParameter extends SearchParameter {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "IncludeAdultContentSearchParameter";
  }

  public function __construct($SearchParameterType = NULL) {
    if(get_parent_class('IncludeAdultContentSearchParameter')) parent::__construct();
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("IntegerAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains an integer value.
 */
class IntegerAttribute extends Attribute {
  /**
   * @access public
   * @var integer
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "IntegerAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('IntegerAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("IntegerSetAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a Set of integer values.
 */
class IntegerSetAttribute extends Attribute {
  /**
   * @access public
   * @var integer[]
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "IntegerSetAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('IntegerSetAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("KeywordCategoryIdSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code KEYWORD} {@link IdeaType}s that
 * sets a keyword category that all search results should belong to.
 * <p>This search parameter can be used in bulk keyword requests through the {@link com.google.ads.api.services.targetingideas.TargetingIdeaService#getBulkKeywordIdeas(TargetingIdeaSelector)} method.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS.
 */
class KeywordCategoryIdSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var integer
   */
  public $categoryId;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "KeywordCategoryIdSearchParameter";
  }

  public function __construct($categoryId = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('KeywordCategoryIdSearchParameter')) parent::__construct();
    $this->categoryId = $categoryId;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("KeywordMatchTypeSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code KEYWORD} {@link IdeaType}s that
 * specifies the {@link KeywordMatchType}s that all results must match.
 * For example, results may be limited to only Broad or Exact matches.
 * Setting no {@link KeywordMatchTypeSearchParameter} will match all
 * targeting ideas, regardless of {@link KeywordMatchType}. If multiple
 * {@link KeywordMatchType}s are set, a result need only match one
 * match type to be returned.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class KeywordMatchTypeSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var cmKeywordMatchType[]
   */
  public $keywordMatchTypes;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "KeywordMatchTypeSearchParameter";
  }

  public function __construct($keywordMatchTypes = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('KeywordMatchTypeSearchParameter')) parent::__construct();
    $this->keywordMatchTypes = $keywordMatchTypes;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("LongAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a long value.
 */
class LongAttribute extends Attribute {
  /**
   * @access public
   * @var integer
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LongAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('LongAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("MobileSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} that limits results to keywords and statistics for
 * mobile WAP devices. Estimated
 * {@link com.google.ads.api.services.targetingideas.external.AttributeType}s
 * are not affected.<p>
 * 
 * Note that the presence of this parameter is all that is needed to limit
 * search results. It is not necessary to set any properties.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class MobileSearchParameter extends SearchParameter {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "MobileSearchParameter";
  }

  public function __construct($SearchParameterType = NULL) {
    if(get_parent_class('MobileSearchParameter')) parent::__construct();
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("MonthlySearchVolumeAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a list of {@link MonthlySearchVolume}
 * values. The list contains the past 12 {@link MonthlySearchVolume}s
 * (excluding the current month). The first item is the data for the most
 * recent month and the last item is the data for the oldest month.
 */
class MonthlySearchVolumeAttribute extends Attribute {
  /**
   * @access public
   * @var MonthlySearchVolume[]
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "MonthlySearchVolumeAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('MonthlySearchVolumeAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("NgramGroupsSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} that applies the constraint that the ideas returned
 * must match one of the specified ngram groups. Any absence of an
 * {@link NgramGroupsSearchParameter} in a
 * {@link com.google.ads.api.services.targetingideas.TargetingIdeaSelector} is
 * equivalent to having no constraint on ngram groups.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class NgramGroupsSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var string[]
   */
  public $ngramGroups;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NgramGroupsSearchParameter";
  }

  public function __construct($ngramGroups = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('NgramGroupsSearchParameter')) parent::__construct();
    $this->ngramGroups = $ngramGroups;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("PlacementTypeAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a {@link PlacementType} value.
 */
class PlacementTypeAttribute extends Attribute {
  /**
   * @access public
   * @var tnsSiteConstantsPlacementType
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "PlacementTypeAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('PlacementTypeAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("PlacementTypeSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code PLACEMENT} {@link IdeaType}s
 * used to filter results based on the type of website, known as
 * {@link com.google.ads.api.services.targetingideas.attributes.Type#PLACEMENT_TYPE},
 * that they appear in. For example, searches may be limited to find
 * results that only appear within mobile websites or feeds.
 * <p>This element is supported by following {@link IdeaType}s: PLACEMENT.
 * <p>This element is supported by following {@link RequestType}s: IDEAS.
 */
class PlacementTypeSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var tnsSiteConstantsPlacementType[]
   */
  public $placementTypes;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "PlacementTypeSearchParameter";
  }

  public function __construct($placementTypes = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('PlacementTypeSearchParameter')) parent::__construct();
    $this->placementTypes = $placementTypes;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("RelatedToUrlSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code KEYWORD} and {@code PLACEMENT}
 * {@link IdeaType}s that specifies a set of URLs that results should
 * in some way be related too. For example, keyword results would be
 * similar to content keywords found on the related URLs.
 * <p>This search parameter can be used in bulk keyword requests through the {@link com.google.ads.api.services.targetingideas.TargetingIdeaService#getBulkKeywordIdeas(TargetingIdeaSelector)} method. It must be single-valued when used in a call to that method.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD, PLACEMENT.
 * <p>This element is supported by following {@link RequestType}s: IDEAS.
 */
class RelatedToUrlSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var string[]
   */
  public $urls;

  /**
   * @access public
   * @var boolean
   */
  public $includeSubUrls;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "RelatedToUrlSearchParameter";
  }

  public function __construct($urls = NULL, $includeSubUrls = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('RelatedToUrlSearchParameter')) parent::__construct();
    $this->urls = $urls;
    $this->includeSubUrls = $includeSubUrls;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("SeedAdGroupIdSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code KEYWORD} {@link IdeaType}s
 * that specifies that the supplied AdGroup should be used as a seed
 * for generating new ideas. For example, an AdGroup's keywords
 * would be used to generate new and related keywords.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class SeedAdGroupIdSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var integer
   */
  public $adGroupId;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SeedAdGroupIdSearchParameter";
  }

  public function __construct($adGroupId = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('SeedAdGroupIdSearchParameter')) parent::__construct();
    $this->adGroupId = $adGroupId;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("StringAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a string value.
 */
class StringAttribute extends Attribute {
  /**
   * @access public
   * @var string
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "StringAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('StringAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("WebpageDescriptorAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a {@link WebpageDescriptor} value.
 */
class WebpageDescriptorAttribute extends Attribute {
  /**
   * @access public
   * @var WebpageDescriptor
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "WebpageDescriptorAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('WebpageDescriptorAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("AverageTargetedMonthlySearchesSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} that constrains the approximate average number of
 * targeted monthly searches on ideas to be returned. Absence of a
 * {@link AverageTargetedMonthlySearchesSearchParameter} in a
 * {@link com.google.ads.api.services.targetingideas.TargetingIdeaSelector} is
 * equivalent to having no constraint on average targeted monthly searches
 * pecified.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class AverageTargetedMonthlySearchesSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var LongComparisonOperation
   */
  public $operation;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AverageTargetedMonthlySearchesSearchParameter";
  }

  public function __construct($operation = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('AverageTargetedMonthlySearchesSearchParameter')) parent::__construct();
    $this->operation = $operation;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("ExcludedKeywordSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code KEYWORD} {@link IdeaType}s that
 * specifies {@link Keyword}s that should be excluded from the results.<p>
 * 
 * The {@link KeywordMatchType} associated with these keywords is used to
 * provide various filtering strategies. For example, the excluded keyword
 * <b>"sony player"</b> will exclude ideas from the resulting ideas as
 * described by the table below.
 * 
 * <table border="1">
 * <tr style="font-weight: bold;">
 * <th>Idea</th>
 * <th>{@code BROAD}</th>
 * <th>{@code PHRASE}</th>
 * <th>{@code EXACT}</th>
 * </tr>
 * <tr>
 * <td>sony player</td>
 * <td>Exclude</td>
 * <td>Exclude</td>
 * <td>Exclude</td>
 * </tr>
 * <tr>
 * <td>blu-ray sony player</td>
 * <td>Exclude</td>
 * <td>Exclude</td>
 * <td>Include</td>
 * </tr>
 * <tr>
 * <td>sony dvd player</td>
 * <td>Exclude</td>
 * <td>Include</td>
 * <td>Include</td>
 * </tr>
 * <tr>
 * <td>sony dvd</td>
 * <td>Include</td>
 * <td>Include</td>
 * <td>Include</td>
 * </tr>
 * </table>
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class ExcludedKeywordSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var Keyword[]
   */
  public $keywords;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ExcludedKeywordSearchParameter";
  }

  public function __construct($keywords = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('ExcludedKeywordSearchParameter')) parent::__construct();
    $this->keywords = $keywords;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("GlobalMonthlySearchesSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} that specifies the level of search volume expected
 * in results. Absence of a {@link GlobalMonthlySearchesSearchParameter} in a
 * {@link com.google.ads.api.services.targetingideas.TargetingIdeaSelector} is
 * equivalent to having no constraint on search volume specified. This search
 * parameter has a direct relationship to
 * {@link com.google.ads.api.services.targetingideas.external.AttributeType#GLOBAL_MONTHLY_SEARCHES}.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class GlobalMonthlySearchesSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var LongComparisonOperation
   */
  public $operation;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "GlobalMonthlySearchesSearchParameter";
  }

  public function __construct($operation = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('GlobalMonthlySearchesSearchParameter')) parent::__construct();
    $this->operation = $operation;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("KeywordAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a {@link Keyword} value.
 */
class KeywordAttribute extends Attribute {
  /**
   * @access public
   * @var Keyword
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "KeywordAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('KeywordAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("MoneyAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a {@link Money} value.
 */
class MoneyAttribute extends Attribute {
  /**
   * @access public
   * @var Money
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "MoneyAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('MoneyAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("PlacementAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a {@link Placement} value.
 */
class PlacementAttribute extends Attribute {
  /**
   * @access public
   * @var Placement
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "PlacementAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('PlacementAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("RelatedToKeywordSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for {@code KEYWORD} and {@code PLACEMENT}
 * {@link IdeaType}s that specifies a generic keyword that results should
 * be related to.
 * <p>This search parameter can be used in bulk keyword requests through the {@link com.google.ads.api.services.targetingideas.TargetingIdeaService#getBulkKeywordIdeas(TargetingIdeaSelector)} method.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD, PLACEMENT.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class RelatedToKeywordSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var Keyword[]
   */
  public $keywords;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "RelatedToKeywordSearchParameter";
  }

  public function __construct($keywords = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('RelatedToKeywordSearchParameter')) parent::__construct();
    $this->keywords = $keywords;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("CountryTargetSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for both {@code PLACEMENT} and {@code KEYWORD}
 * {@link IdeaType}s used to indicate the countries being targeted. This can be
 * used, for example,  to search for {@code KEYWORD} {@link IdeaType}s that are
 * best for Japan and Korea.<p>
 * 
 * Note: The {@link CountryTarget}'s {@code excluded} attribute is ignored.
 * <p>This search parameter can be used in bulk keyword requests through the {@link com.google.ads.api.services.targetingideas.TargetingIdeaService#getBulkKeywordIdeas(TargetingIdeaSelector)} method. It must be single-valued when used in a call to that method.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD, PLACEMENT.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class CountryTargetSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var CountryTarget[]
   */
  public $countryTargets;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "CountryTargetSearchParameter";
  }

  public function __construct($countryTargets = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('CountryTargetSearchParameter')) parent::__construct();
    $this->countryTargets = $countryTargets;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("LanguageTargetSearchParameter", FALSE)) {
/**
 * A {@link SearchParameter} for both {@code PLACEMENT} and {@code KEYWORD}
 * {@link IdeaType}s used to indicate the languages being targeted. This can
 * be used, for example, to search for {@code KEYWORD} {@link IdeaType}s that
 * are best for Japanese and Korean languages.
 * <p>
 * Note: The {@link LanguageTarget}'s {@code excluded} attribute is ignored.
 * <p>This search parameter can be used in bulk keyword requests through the {@link com.google.ads.api.services.targetingideas.TargetingIdeaService#getBulkKeywordIdeas(TargetingIdeaSelector)} method. It must be single-valued when used in a call to that method.
 * <p>This element is supported by following {@link IdeaType}s: KEYWORD, PLACEMENT.
 * <p>This element is supported by following {@link RequestType}s: IDEAS, STATS.
 */
class LanguageTargetSearchParameter extends SearchParameter {
  /**
   * @access public
   * @var LanguageTarget[]
   */
  public $languageTargets;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LanguageTargetSearchParameter";
  }

  public function __construct($languageTargets = NULL, $SearchParameterType = NULL) {
    if(get_parent_class('LanguageTargetSearchParameter')) parent::__construct();
    $this->languageTargets = $languageTargets;
    $this->SearchParameterType = $SearchParameterType;
  }
}}

if (!class_exists("LongRangeAttribute", FALSE)) {
/**
 * {@link Attribute} type that contains a {@link Range} of {@link LongValue}
 * values.
 */
class LongRangeAttribute extends Attribute {
  /**
   * @access public
   * @var Range
   */
  public $value;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/o/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LongRangeAttribute";
  }

  public function __construct($value = NULL, $AttributeType = NULL) {
    if(get_parent_class('LongRangeAttribute')) parent::__construct();
    $this->value = $value;
    $this->AttributeType = $AttributeType;
  }
}}

if (!class_exists("AdGroupCriterionLimitExceeded", FALSE)) {
/**
 * Signals that too many criteria were added to some ad group.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class AdGroupCriterionLimitExceeded extends EntityCountLimitExceeded {
  /**
   * @access public
   * @var tnsAdGroupCriterionLimitExceededCriteriaLimitType
   */
  public $limitType;

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
    return "AdGroupCriterionLimitExceeded";
  }

  public function __construct($limitType = NULL, $reason = NULL, $enclosingId = NULL, $limit = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AdGroupCriterionLimitExceeded')) parent::__construct();
    $this->limitType = $limitType;
    $this->reason = $reason;
    $this->enclosingId = $enclosingId;
    $this->limit = $limit;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("AgeTarget", FALSE)) {
/**
 * Immutable structure to hold an age target.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class AgeTarget extends DemographicTarget {
  /**
   * @access public
   * @var tnsAgeTargetAge
   */
  public $age;

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
    return "AgeTarget";
  }

  public function __construct($age = NULL, $bidModifier = NULL, $TargetType = NULL) {
    if(get_parent_class('AgeTarget')) parent::__construct();
    $this->age = $age;
    $this->bidModifier = $bidModifier;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("CityTarget", FALSE)) {
/**
 * Immutable structure to specify a geographic target for a city.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class CityTarget extends GeoTarget {
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
    return "CityTarget";
  }

  public function __construct($cityName = NULL, $provinceCode = NULL, $countryCode = NULL, $excluded = NULL, $TargetType = NULL) {
    if(get_parent_class('CityTarget')) parent::__construct();
    $this->cityName = $cityName;
    $this->provinceCode = $provinceCode;
    $this->countryCode = $countryCode;
    $this->excluded = $excluded;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("CountryTarget", FALSE)) {
/**
 * Immutable structure to specify a geographic target for a country.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class CountryTarget extends GeoTarget {
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
    return "CountryTarget";
  }

  public function __construct($countryCode = NULL, $excluded = NULL, $TargetType = NULL) {
    if(get_parent_class('CountryTarget')) parent::__construct();
    $this->countryCode = $countryCode;
    $this->excluded = $excluded;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("CriterionPolicyError", FALSE)) {
/**
 * Contains the policy violations for a single BiddableAdGroupCriterion.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class CriterionPolicyError extends PolicyViolationError {
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
    return "CriterionPolicyError";
  }

  public function __construct($key = NULL, $externalPolicyName = NULL, $externalPolicyUrl = NULL, $externalPolicyDescription = NULL, $isExemptable = NULL, $violatingParts = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('CriterionPolicyError')) parent::__construct();
    $this->key = $key;
    $this->externalPolicyName = $externalPolicyName;
    $this->externalPolicyUrl = $externalPolicyUrl;
    $this->externalPolicyDescription = $externalPolicyDescription;
    $this->isExemptable = $isExemptable;
    $this->violatingParts = $violatingParts;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("DoubleValue", FALSE)) {
/**
 * Number value type for constructing double valued ranges.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("GenderTarget", FALSE)) {
/**
 * Immutable structure to hold a gender target.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class GenderTarget extends DemographicTarget {
  /**
   * @access public
   * @var tnsGenderTargetGender
   */
  public $gender;

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
    return "GenderTarget";
  }

  public function __construct($gender = NULL, $bidModifier = NULL, $TargetType = NULL) {
    if(get_parent_class('GenderTarget')) parent::__construct();
    $this->gender = $gender;
    $this->bidModifier = $bidModifier;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("LongValue", FALSE)) {
/**
 * Number value type for constructing long valued ranges.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
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

if (!class_exists("MetroTarget", FALSE)) {
/**
 * Immutable structure to specify a geographic target for a metro.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class MetroTarget extends GeoTarget {
  /**
   * @access public
   * @var string
   */
  public $metroCode;

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
    return "MetroTarget";
  }

  public function __construct($metroCode = NULL, $excluded = NULL, $TargetType = NULL) {
    if(get_parent_class('MetroTarget')) parent::__construct();
    $this->metroCode = $metroCode;
    $this->excluded = $excluded;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("PolygonTarget", FALSE)) {
/**
 * Structure to specify a geographic target for a polygon location.
 * 
 * This polygon target consists of a list of vertices;
 * each vertex is a geo point defined by a latitude and longitude.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class PolygonTarget extends GeoTarget {
  /**
   * @access public
   * @var GeoPoint[]
   */
  public $vertices;

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
    return "PolygonTarget";
  }

  public function __construct($vertices = NULL, $excluded = NULL, $TargetType = NULL) {
    if(get_parent_class('PolygonTarget')) parent::__construct();
    $this->vertices = $vertices;
    $this->excluded = $excluded;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("ProvinceTarget", FALSE)) {
/**
 * Immutable structure to specify a geographic target for a province or state.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class ProvinceTarget extends GeoTarget {
  /**
   * @access public
   * @var string
   */
  public $provinceCode;

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
    return "ProvinceTarget";
  }

  public function __construct($provinceCode = NULL, $excluded = NULL, $TargetType = NULL) {
    if(get_parent_class('ProvinceTarget')) parent::__construct();
    $this->provinceCode = $provinceCode;
    $this->excluded = $excluded;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("ProximityTarget", FALSE)) {
/**
 * Structure to specify a geographic target for a proximity location.
 * 
 * This proximity target doesn't support taking in an address location in place of a lat/long,
 * geocoding it, and creating a proximity target for the campaign like AWFE does.
 * The caller must ensure the address fields are valid and consistent with the supplied lat/long.
 * 
 * 
 * 
 * Base error class for Ad Group Criterion Service.
 */
class ProximityTarget extends GeoTarget {
  /**
   * @access public
   * @var GeoPoint
   */
  public $geoPoint;

  /**
   * @access public
   * @var tnsProximityTargetDistanceUnits
   */
  public $radiusDistanceUnits;

  /**
   * @access public
   * @var double
   */
  public $radiusInUnits;

  /**
   * @access public
   * @var Address
   */
  public $address;

  /**
   * @access public
   * @var boolean
   */
  public $allowServiceOfAddress;

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
    return "ProximityTarget";
  }

  public function __construct($geoPoint = NULL, $radiusDistanceUnits = NULL, $radiusInUnits = NULL, $address = NULL, $allowServiceOfAddress = NULL, $excluded = NULL, $TargetType = NULL) {
    if(get_parent_class('ProximityTarget')) parent::__construct();
    $this->geoPoint = $geoPoint;
    $this->radiusDistanceUnits = $radiusDistanceUnits;
    $this->radiusInUnits = $radiusInUnits;
    $this->address = $address;
    $this->allowServiceOfAddress = $allowServiceOfAddress;
    $this->excluded = $excluded;
    $this->TargetType = $TargetType;
  }
}}

if (!class_exists("TargetingIdeaService")) {
/**
 * TargetingIdeaService
 * @author WSDLInterpreter
 */
class TargetingIdeaService extends AdWordsSoapClient {
  /**
   * Default class map for wsdl=>php
   * @access private
   * @var array
   */
  public static $classmap = array(
    "getResponse" => "TargetingIdeaServiceGetResponse",
    "get" => "TargetingIdeaServiceGet",
    "mutate" => "TargetingIdeaServiceMutate",
    "mutateResponse" => "TargetingIdeaServiceMutateResponse",
    "DateTime" => "AdWordsDateTime",
    "Target" => "AdWordsTarget",
    "SoapHeader" => "SoapRequestHeader",
    "AdGroupCriterionError" => "AdGroupCriterionError",
    "ApiError" => "ApiError",
    "AdGroupCriterionLimitExceeded" => "AdGroupCriterionLimitExceeded",
    "EntityCountLimitExceeded" => "EntityCountLimitExceeded",
    "AdScheduleTarget" => "AdScheduleTarget",
    "Address" => "Address",
    "AgeTarget" => "AgeTarget",
    "DemographicTarget" => "DemographicTarget",
    "AuthenticationError" => "AuthenticationError",
    "AuthorizationError" => "AuthorizationError",
    "BiddingError" => "BiddingError",
    "BudgetError" => "BudgetError",
    "CityTarget" => "CityTarget",
    "GeoTarget" => "GeoTarget",
    "ClientTermsError" => "ClientTermsError",
    "CountryTarget" => "CountryTarget",
    "CriterionPolicyError" => "CriterionPolicyError",
    "PolicyViolationError" => "PolicyViolationError",
    "DatabaseError" => "DatabaseError",
    "DateError" => "DateError",
    "DistinctError" => "DistinctError",
    "DoubleValue" => "DoubleValue",
    "NumberValue" => "NumberValue",
    "EntityNotFound" => "EntityNotFound",
    "GenderTarget" => "GenderTarget",
    "GeoPoint" => "GeoPoint",
    "InternalApiError" => "InternalApiError",
    "Keyword" => "Keyword",
    "Criterion" => "Criterion",
    "LanguageTarget" => "LanguageTarget",
    "LongValue" => "LongValue",
    "MetroTarget" => "MetroTarget",
    "Money" => "Money",
    "ComparableValue" => "ComparableValue",
    "NetworkTarget" => "NetworkTarget",
    "NotEmptyError" => "NotEmptyError",
    "NotWhitelistedError" => "NotWhitelistedError",
    "NullError" => "NullError",
    "Paging" => "Paging",
    "Placement" => "Placement",
    "PlatformTarget" => "PlatformTarget",
    "PolicyViolationError.Part" => "PolicyViolationErrorPart",
    "PolicyViolationKey" => "PolicyViolationKey",
    "PolygonTarget" => "PolygonTarget",
    "ProvinceTarget" => "ProvinceTarget",
    "ProximityTarget" => "ProximityTarget",
    "QuotaCheckError" => "QuotaCheckError",
    "QuotaExceededError" => "QuotaExceededError",
    "RangeError" => "RangeError",
    "ReadOnlyError" => "ReadOnlyError",
    "RequiredError" => "RequiredError",
    "SizeLimitError" => "SizeLimitError",
    "SoapResponseHeader" => "SoapResponseHeader",
    "StatsQueryError" => "StatsQueryError",
    "StringLengthError" => "StringLengthError",
    "ApiException" => "ApiException",
    "ApplicationException" => "ApplicationException",
    "AdGroupCriterionError.Reason" => "AdGroupCriterionErrorReason",
    "AdGroupCriterionLimitExceeded.CriteriaLimitType" => "AdGroupCriterionLimitExceededCriteriaLimitType",
    "AgeTarget.Age" => "AgeTargetAge",
    "AuthenticationError.Reason" => "AuthenticationErrorReason",
    "AuthorizationError.Reason" => "AuthorizationErrorReason",
    "BiddingError.Reason" => "BiddingErrorReason",
    "BudgetError.Reason" => "BudgetErrorReason",
    "ClientTermsError.Reason" => "ClientTermsErrorReason",
    "DatabaseError.Reason" => "DatabaseErrorReason",
    "DateError.Reason" => "DateErrorReason",
    "DayOfWeek" => "DayOfWeek",
    "DistinctError.Reason" => "DistinctErrorReason",
    "EntityCountLimitExceeded.Reason" => "EntityCountLimitExceededReason",
    "EntityNotFound.Reason" => "EntityNotFoundReason",
    "GenderTarget.Gender" => "GenderTargetGender",
    "InternalApiError.Reason" => "InternalApiErrorReason",
    "KeywordMatchType" => "KeywordMatchType",
    "MinuteOfHour" => "MinuteOfHour",
    "NetworkCoverageType" => "NetworkCoverageType",
    "NotEmptyError.Reason" => "NotEmptyErrorReason",
    "NotWhitelistedError.Reason" => "NotWhitelistedErrorReason",
    "NullError.Reason" => "NullErrorReason",
    "PlatformType" => "PlatformType",
    "ProximityTarget.DistanceUnits" => "ProximityTargetDistanceUnits",
    "QuotaCheckError.Reason" => "QuotaCheckErrorReason",
    "QuotaExceededError.Reason" => "QuotaExceededErrorReason",
    "RangeError.Reason" => "RangeErrorReason",
    "ReadOnlyError.Reason" => "ReadOnlyErrorReason",
    "RequiredError.Reason" => "RequiredErrorReason",
    "SizeLimitError.Reason" => "SizeLimitErrorReason",
    "StatsQueryError.Reason" => "StatsQueryErrorReason",
    "StringLengthError.Reason" => "StringLengthErrorReason",
    "AdFormatSpec" => "AdFormatSpec",
    "AdFormatSpecListAttribute" => "AdFormatSpecListAttribute",
    "Attribute" => "Attribute",
    "AdTypeSearchParameter" => "AdTypeSearchParameter",
    "SearchParameter" => "SearchParameter",
    "BooleanAttribute" => "BooleanAttribute",
    "CollectionSizeError" => "CollectionSizeError",
    "CompetitionSearchParameter" => "CompetitionSearchParameter",
    "DoubleAttribute" => "DoubleAttribute",
    "IdeaTypeAttribute" => "IdeaTypeAttribute",
    "InStreamAdInfo" => "InStreamAdInfo",
    "InStreamAdInfoAttribute" => "InStreamAdInfoAttribute",
    "IncludeAdultContentSearchParameter" => "IncludeAdultContentSearchParameter",
    "IntegerAttribute" => "IntegerAttribute",
    "IntegerSetAttribute" => "IntegerSetAttribute",
    "KeywordCategoryIdSearchParameter" => "KeywordCategoryIdSearchParameter",
    "KeywordMatchTypeSearchParameter" => "KeywordMatchTypeSearchParameter",
    "LongAttribute" => "LongAttribute",
    "LongComparisonOperation" => "LongComparisonOperation",
    "MobileSearchParameter" => "MobileSearchParameter",
    "MonthlySearchVolume" => "MonthlySearchVolume",
    "MonthlySearchVolumeAttribute" => "MonthlySearchVolumeAttribute",
    "NgramGroupsSearchParameter" => "NgramGroupsSearchParameter",
    "PlacementTypeAttribute" => "PlacementTypeAttribute",
    "PlacementTypeSearchParameter" => "PlacementTypeSearchParameter",
    "RelatedToUrlSearchParameter" => "RelatedToUrlSearchParameter",
    "SeedAdGroupIdSearchParameter" => "SeedAdGroupIdSearchParameter",
    "StringAttribute" => "StringAttribute",
    "TargetingIdeaError" => "TargetingIdeaError",
    "WebpageDescriptor" => "WebpageDescriptor",
    "WebpageDescriptorAttribute" => "WebpageDescriptorAttribute",
    "AverageTargetedMonthlySearchesSearchParameter" => "AverageTargetedMonthlySearchesSearchParameter",
    "ExcludedKeywordSearchParameter" => "ExcludedKeywordSearchParameter",
    "GlobalMonthlySearchesSearchParameter" => "GlobalMonthlySearchesSearchParameter",
    "KeywordAttribute" => "KeywordAttribute",
    "MoneyAttribute" => "MoneyAttribute",
    "PlacementAttribute" => "PlacementAttribute",
    "Range" => "Range",
    "RelatedToKeywordSearchParameter" => "RelatedToKeywordSearchParameter",
    "CountryTargetSearchParameter" => "CountryTargetSearchParameter",
    "LanguageTargetSearchParameter" => "LanguageTargetSearchParameter",
    "LongRangeAttribute" => "LongRangeAttribute",
    "TargetingIdeaSelector" => "TargetingIdeaSelector",
    "Type_AttributeMapEntry" => "Type_AttributeMapEntry",
    "TargetingIdea" => "TargetingIdea",
    "TargetingIdeaPage" => "TargetingIdeaPage",
    "AttributeType" => "AttributeType",
    "CollectionSizeError.Reason" => "CollectionSizeErrorReason",
    "CompetitionSearchParameter.Level" => "CompetitionSearchParameterLevel",
    "IdeaType" => "IdeaType",
    "RequestType" => "RequestType",
    "SiteConstants.AdFormat" => "SiteConstantsAdFormat",
    "SiteConstants.AdType" => "SiteConstantsAdType",
    "SiteConstants.PlacementType" => "SiteConstantsPlacementType",
    "TargetingIdeaError.Reason" => "TargetingIdeaErrorReason",
    "getBulkKeywordIdeas" => "getBulkKeywordIdeas",
    "getBulkKeywordIdeasResponse" => "getBulkKeywordIdeasResponse",
  );

  /**
   * Constructor using wsdl location and options array
   * @param string $wsdl WSDL location for this service
   * @param array $options Options for the SoapClient
   */
  public function __construct($wsdl=null, $options, $user) {
    $options["classmap"] = TargetingIdeaService::$classmap;
    parent::__construct($wsdl, $options, $user, 'TargetingIdeaService', 'https://adwords.google.com/api/adwords/o/v200909');
  }

  /**
   * <span class="constraint Required">This field is required and should not be {@code null}.</span>
   * 
   * 
   * 
   * Returns a page of ideas that match the query described by the specified
   * {@link TargetingIdeaSelector}.
   * 
   * The selector must specify a Paging value, with {@code numberResults} set to 800 or less.
   * 
   * @param selector Query describing the types of results to return when
   * finding matches (similar keyword ideas/placement ideas).
   * @return A {@link TargetingIdeaPage} of results, that is a subset of the
   * list of possible ideas meeting the criteria of the
   * {@link TargetingIdeaSelector}.
   * @throws ApiException If problems occurred while querying for ideas.
   */
  public function get($selector) {
    $arg = new TargetingIdeaServiceGet($selector);
    $result = $this->__soapCall("get", array($arg));
    return $result->rval;
  }


  /**
   * <span class="constraint Required">This field is required and should not be {@code null}.</span>
   * 
   * 
   * 
   * Returns a page of ideas that match the query described by the specified
   * {@link TargetingIdeaSelector}.  This method is specialized for returning
   * bulk keyword ideas, and thus the selector must be set for
   * {@link com.google.ads.api.services.targetingideas.attributes.RequestType#IDEAS} and
   * {@link com.google.ads.api.services.targetingideas.attributes.IdeaType#KEYWORD}.
   * A limited, fixed set of attributes will be returned.<p>
   * 
   * A single-valued
   * {@link com.google.ads.api.services.targetingideas.search.RelatedToUrlSearchParameter}
   * must be supplied.  Single-valued
   * {@link com.google.ads.api.services.targetingideas.search.LanguageTargetSearchParameter} and
   * {@link com.google.ads.api.services.targetingideas.search.CountryTargetSearchParameter} are
   * both optional.  Other search parameters compatible with a keyword query may also be
   * supplied.<p>
   * 
   * The selector must specify a Paging value, with {@code numberResults} set to 500 or less.
   * Large result sets must be composed through multiple calls to this method, advancing the
   * Paging {@code startIndex} value by {@code numberResults} with each call.
   * 
   * @param selector Query describing the bulk keyword ideas to return.
   * @return A {@link TargetingIdeaPage} of results, that is a subset of the
   * list of possible ideas meeting the criteria of the
   * {@link TargetingIdeaSelector}.
   * @throws ApiException If problems occurred while querying for ideas.
   */
  public function getBulkKeywordIdeas($selector) {
    $arg = new getBulkKeywordIdeas($selector);
    $result = $this->__soapCall("getBulkKeywordIdeas", array($arg));
    return $result->rval;
  }


}}

?>