<?php
/**
 * Contains all client objects for the CampaignService service.
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

if (!class_exists("DateRange", FALSE)) {
/**
 * Represents a range of dates that has either an upper or a lower bound.
 */
class DateRange {
  /**
   * @access public
   * @var string
   */
  public $min;

  /**
   * @access public
   * @var string
   */
  public $max;

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
    return "DateRange";
  }

  public function __construct($min = NULL, $max = NULL) {
    if(get_parent_class('DateRange')) parent::__construct();
    $this->min = $min;
    $this->max = $max;
  }
}}

if (!class_exists("FrequencyCap", FALSE)) {
/**
 * A frequency cap is the maximum number of times an ad (or some set of ads) can
 * be shown to a user over a particular time period.
 */
class FrequencyCap {
  /**
   * @access public
   * @var integer
   */
  public $impressions;

  /**
   * @access public
   * @var tnsTimeUnit
   */
  public $timeUnit;

  /**
   * @access public
   * @var tnsLevel
   */
  public $level;

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
    return "FrequencyCap";
  }

  public function __construct($impressions = NULL, $timeUnit = NULL, $level = NULL) {
    if(get_parent_class('FrequencyCap')) parent::__construct();
    $this->impressions = $impressions;
    $this->timeUnit = $timeUnit;
    $this->level = $level;
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

if (!class_exists("StatsSelector", FALSE)) {
/**
 * Selects statistics for date range.
 */
class StatsSelector {
  /**
   * @access public
   * @var DateRange
   */
  public $dateRange;

  /**
   * @access public
   * @var string
   */
  public $StatsSelectorType;

  private $_parameterMap = array (
    "StatsSelector.Type" => "StatsSelectorType",
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
    return "StatsSelector";
  }

  public function __construct($dateRange = NULL, $StatsSelectorType = NULL) {
    if(get_parent_class('StatsSelector')) parent::__construct();
    $this->dateRange = $dateRange;
    $this->StatsSelectorType = $StatsSelectorType;
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

if (!class_exists("Bid", FALSE)) {
/**
 * Represents a bid on a criterion.
 */
class Bid {
  /**
   * @access public
   * @var Money
   */
  public $amount;

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
    return "Bid";
  }

  public function __construct($amount = NULL) {
    if(get_parent_class('Bid')) parent::__construct();
    $this->amount = $amount;
  }
}}

if (!class_exists("Budget", FALSE)) {
/**
 * Class representing budget for the campaign.
 */
class Budget {
  /**
   * @access public
   * @var tnsBudgetBudgetPeriod
   */
  public $period;

  /**
   * @access public
   * @var Money
   */
  public $amount;

  /**
   * @access public
   * @var tnsBudgetBudgetDeliveryMethod
   */
  public $deliveryMethod;

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
    return "Budget";
  }

  public function __construct($period = NULL, $amount = NULL, $deliveryMethod = NULL) {
    if(get_parent_class('Budget')) parent::__construct();
    $this->period = $period;
    $this->amount = $amount;
    $this->deliveryMethod = $deliveryMethod;
  }
}}

if (!class_exists("AdGroupBids", FALSE)) {
/**
 * Represents bids at the adgroup level, which could be of different subtypes based on
 * campaign's bidding strategy.
 */
class AdGroupBids {
  /**
   * @access public
   * @var string
   */
  public $AdGroupBidsType;

  private $_parameterMap = array (
    "AdGroupBids.Type" => "AdGroupBidsType",
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
    return "AdGroupBids";
  }

  public function __construct($AdGroupBidsType = NULL) {
    if(get_parent_class('AdGroupBids')) parent::__construct();
    $this->AdGroupBidsType = $AdGroupBidsType;
  }
}}

if (!class_exists("BiddingStrategy", FALSE)) {
/**
 * A campaign's bidding strategy, such as manual CPC, manual CPM, budget optimizer, etc.
 */
class BiddingStrategy {
  /**
   * @access public
   * @var string
   */
  public $BiddingStrategyType;

  private $_parameterMap = array (
    "BiddingStrategy.Type" => "BiddingStrategyType",
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
    return "BiddingStrategy";
  }

  public function __construct($BiddingStrategyType = NULL) {
    if(get_parent_class('BiddingStrategy')) parent::__construct();
    $this->BiddingStrategyType = $BiddingStrategyType;
  }
}}

if (!class_exists("BiddingTransition", FALSE)) {
/**
 * Used to switch a campaign's bidding strategy.
 */
class BiddingTransition {
  /**
   * @access public
   * @var BiddingStrategy
   */
  public $targetBiddingStrategy;

  /**
   * @access public
   * @var AdGroupBids
   */
  public $explicitAdGroupBids;

  /**
   * @access public
   * @var string
   */
  public $BiddingTransitionType;

  private $_parameterMap = array (
    "BiddingTransition.Type" => "BiddingTransitionType",
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
    return "BiddingTransition";
  }

  public function __construct($targetBiddingStrategy = NULL, $explicitAdGroupBids = NULL, $BiddingTransitionType = NULL) {
    if(get_parent_class('BiddingTransition')) parent::__construct();
    $this->targetBiddingStrategy = $targetBiddingStrategy;
    $this->explicitAdGroupBids = $explicitAdGroupBids;
    $this->BiddingTransitionType = $BiddingTransitionType;
  }
}}

if (!class_exists("Campaign", FALSE)) {
/**
 * Class representing a campaign for the CampaignService.
 */
class Campaign {
  /**
   * @access public
   * @var integer
   */
  public $id;

  /**
   * @access public
   * @var string
   */
  public $name;

  /**
   * @access public
   * @var tnsCampaignStatus
   */
  public $status;

  /**
   * @access public
   * @var tnsServingStatus
   */
  public $servingStatus;

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
   * @var Budget
   */
  public $budget;

  /**
   * @access public
   * @var BiddingStrategy
   */
  public $biddingStrategy;

  /**
   * @access public
   * @var tnsAutoKeywordMatchingStatus
   */
  public $autoKeywordMatchingStatus;

  /**
   * @access public
   * @var Stats
   */
  public $stats;

  /**
   * @access public
   * @var tnsAdServingOptimizationStatus
   */
  public $adServingOptimizationStatus;

  /**
   * @access public
   * @var FrequencyCap
   */
  public $frequencyCap;

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
    return "Campaign";
  }

  public function __construct($id = NULL, $name = NULL, $status = NULL, $servingStatus = NULL, $startDate = NULL, $endDate = NULL, $budget = NULL, $biddingStrategy = NULL, $autoKeywordMatchingStatus = NULL, $stats = NULL, $adServingOptimizationStatus = NULL, $frequencyCap = NULL) {
    if(get_parent_class('Campaign')) parent::__construct();
    $this->id = $id;
    $this->name = $name;
    $this->status = $status;
    $this->servingStatus = $servingStatus;
    $this->startDate = $startDate;
    $this->endDate = $endDate;
    $this->budget = $budget;
    $this->biddingStrategy = $biddingStrategy;
    $this->autoKeywordMatchingStatus = $autoKeywordMatchingStatus;
    $this->stats = $stats;
    $this->adServingOptimizationStatus = $adServingOptimizationStatus;
    $this->frequencyCap = $frequencyCap;
  }
}}

if (!class_exists("CampaignSelector", FALSE)) {
/**
 * A filter for selecting campaigns from the customer's AdWords account.
 * Only those campaigns that match all criteria (i.e., id and status) will be
 * included.
 * 
 * The selector is immutable so use the inner Builder class to create one.
 */
class CampaignSelector {
  /**
   * @access public
   * @var integer[]
   */
  public $ids;

  /**
   * @access public
   * @var tnsCampaignStatus[]
   */
  public $campaignStatuses;

  /**
   * @access public
   * @var StatsSelector
   */
  public $statsSelector;

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
    return "CampaignSelector";
  }

  public function __construct($ids = NULL, $campaignStatuses = NULL, $statsSelector = NULL, $paging = NULL) {
    if(get_parent_class('CampaignSelector')) parent::__construct();
    $this->ids = $ids;
    $this->campaignStatuses = $campaignStatuses;
    $this->statsSelector = $statsSelector;
    $this->paging = $paging;
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

if (!class_exists("AdServingOptimizationStatus", FALSE)) {
/**
 * Ad serving status of campaign.
 */
class AdServingOptimizationStatus {
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
    return "AdServingOptimizationStatus";
  }

  public function __construct() {
    if(get_parent_class('AdServingOptimizationStatus')) parent::__construct();
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

if (!class_exists("AutoKeywordMatchingStatus", FALSE)) {
/**
 * Automatic Keyword matching (AutoPilot) status for the campaign.
 */
class AutoKeywordMatchingStatus {
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
    return "AutoKeywordMatchingStatus";
  }

  public function __construct() {
    if(get_parent_class('AutoKeywordMatchingStatus')) parent::__construct();
  }
}}

if (!class_exists("BiddingErrorReason", FALSE)) {
/**
 * Reason for bidding error.
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

if (!class_exists("BiddingTransitionErrorReason", FALSE)) {
/**
 * Reason for bidding transition error.
 * It is used by capability service as denial reasons, for bidding transition
 * capability.
 */
class BiddingTransitionErrorReason {
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
    return "BiddingTransitionError.Reason";
  }

  public function __construct() {
    if(get_parent_class('BiddingTransitionErrorReason')) parent::__construct();
  }
}}

if (!class_exists("BudgetBudgetDeliveryMethod", FALSE)) {
/**
 * Budget delivery methods.
 */
class BudgetBudgetDeliveryMethod {
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
    return "Budget.BudgetDeliveryMethod";
  }

  public function __construct() {
    if(get_parent_class('BudgetBudgetDeliveryMethod')) parent::__construct();
  }
}}

if (!class_exists("BudgetBudgetPeriod", FALSE)) {
/**
 * Budget periods.
 */
class BudgetBudgetPeriod {
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
    return "Budget.BudgetPeriod";
  }

  public function __construct() {
    if(get_parent_class('BudgetBudgetPeriod')) parent::__construct();
  }
}}

if (!class_exists("BudgetErrorReason", FALSE)) {
/**
 * The reasons for the budget error.
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

if (!class_exists("CampaignErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class CampaignErrorReason {
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
    return "CampaignError.Reason";
  }

  public function __construct() {
    if(get_parent_class('CampaignErrorReason')) parent::__construct();
  }
}}

if (!class_exists("CampaignStatus", FALSE)) {
/**
 * Campaign status.
 */
class CampaignStatus {
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
    return "CampaignStatus";
  }

  public function __construct() {
    if(get_parent_class('CampaignStatus')) parent::__construct();
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

if (!class_exists("DatabaseErrorReason", FALSE)) {
/**
 * The reasons for the database error.
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

if (!class_exists("Level", FALSE)) {
/**
 * The level on which the cap is to be applied.
 */
class Level {
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
    return "Level";
  }

  public function __construct() {
    if(get_parent_class('Level')) parent::__construct();
  }
}}

if (!class_exists("NewEntityCreationErrorReason", FALSE)) {
/**
 * Do not set the id field while creating new entities.
 */
class NewEntityCreationErrorReason {
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
    return "NewEntityCreationError.Reason";
  }

  public function __construct() {
    if(get_parent_class('NewEntityCreationErrorReason')) parent::__construct();
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

if (!class_exists("OperationAccessDeniedReason", FALSE)) {
/**
 * The reasons for the operation access error.
 */
class OperationAccessDeniedReason {
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
    return "OperationAccessDenied.Reason";
  }

  public function __construct() {
    if(get_parent_class('OperationAccessDeniedReason')) parent::__construct();
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

if (!class_exists("PricingModel", FALSE)) {
/**
 * Campaign's pricing model indicates whether it is a pay per clicks,
 * pay per impressions, or play per conversions campaign.
 */
class PricingModel {
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
    return "PricingModel";
  }

  public function __construct() {
    if(get_parent_class('PricingModel')) parent::__construct();
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

if (!class_exists("QuotaErrorReason", FALSE)) {
/**
 * The reasons for the quota error.
 */
class QuotaErrorReason {
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
    return "QuotaError.Reason";
  }

  public function __construct() {
    if(get_parent_class('QuotaErrorReason')) parent::__construct();
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

if (!class_exists("ServingStatus", FALSE)) {
/**
 * Campaign serving status.
 */
class ServingStatus {
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
    return "ServingStatus";
  }

  public function __construct() {
    if(get_parent_class('ServingStatus')) parent::__construct();
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

if (!class_exists("StatsQueryErrorReason", FALSE)) {
/**
 * The reasons for errors when querying for stats.
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

if (!class_exists("TimeUnit", FALSE)) {
/**
 * Unit of time the cap is defined at.
 */
class TimeUnit {
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
    return "TimeUnit";
  }

  public function __construct() {
    if(get_parent_class('TimeUnit')) parent::__construct();
  }
}}

if (!class_exists("CampaignServiceGet", FALSE)) {
/**
 * <span class="constraint Required">This field is required and should not be {@code null}.</span>
 * 
 * 
 * 
 * Returns a list of campaigns specified by the list of selectors from
 * the customer's account. Each selector (filter) in the list results in
 * a set of campaigns. The final result is the union of each of these sets.
 * @param selector filter to run campaigns through.
 * If selector is empty, all campaigns are returned.
 * @return list of campaigns meeting all the criteria of each selector.
 * @throws ApiException if problems occurred while fetching campaign information.
 */
class CampaignServiceGet {
  /**
   * @access public
   * @var CampaignSelector
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
    if(get_parent_class('CampaignServiceGet')) parent::__construct();
    $this->selector = $selector;
  }
}}

if (!class_exists("CampaignServiceGetResponse", FALSE)) {
/**
 * 
 */
class CampaignServiceGetResponse {
  /**
   * @access public
   * @var CampaignPage
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
    if(get_parent_class('CampaignServiceGetResponse')) parent::__construct();
    $this->rval = $rval;
  }
}}

if (!class_exists("CampaignServiceMutate", FALSE)) {
/**
 * <span class="constraint ContentsNotNull">This field must not contain {@code null} elements.</span>
 * <span class="constraint DistinctIds">Elements in this field must have distinct IDs for following {@link Operator}s : SET, REMOVE.</span>
 * <span class="constraint NotEmpty">This field must contain at least one element.</span>
 * <span class="constraint Required">This field is required and should not be {@code null}.</span>
 * <span class="constraint SuppoprtedOperators">The following {@link Operator}s are supported: ADD, SET.</span>
 * 
 * 
 * 
 * Adds, updates, or removes campaigns.
 * Note: to REMOVE use SET and mark status to DELETE.
 * @param operations a list of unique operations.
 * The same campaign cannot be specified in more than one operation.
 * @return the added campaigns.
 * The list of campaigns is returned in the same order in which it came in as input.
 * @throws ApiException if problems occurred while updating campaign information.
 */
class CampaignServiceMutate {
  /**
   * @access public
   * @var CampaignOperation[]
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
    if(get_parent_class('CampaignServiceMutate')) parent::__construct();
    $this->operations = $operations;
  }
}}

if (!class_exists("CampaignServiceMutateResponse", FALSE)) {
/**
 * 
 */
class CampaignServiceMutateResponse {
  /**
   * @access public
   * @var CampaignReturnValue
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
    if(get_parent_class('CampaignServiceMutateResponse')) parent::__construct();
    $this->rval = $rval;
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

if (!class_exists("BiddingError", FALSE)) {
/**
 * Represents bidding errors.
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

if (!class_exists("BiddingTransitionError", FALSE)) {
/**
 * Bidding transition errors.
 */
class BiddingTransitionError extends ApiError {
  /**
   * @access public
   * @var tnsBiddingTransitionErrorReason
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
    return "BiddingTransitionError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('BiddingTransitionError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("BudgetError", FALSE)) {
/**
 * A list of all the error codes being used by the common budget domain package.
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

if (!class_exists("CampaignError", FALSE)) {
/**
 * Base error class for Campaign Service.
 */
class CampaignError extends ApiError {
  /**
   * @access public
   * @var tnsCampaignErrorReason
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
    return "CampaignError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('CampaignError')) parent::__construct();
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

if (!class_exists("ConversionOptimizer", FALSE)) {
/**
 * Conversion optimizer bidding strategy.
 */
class ConversionOptimizer extends BiddingStrategy {
  /**
   * @access public
   * @var tnsPricingModel
   */
  public $pricingModel;

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
    return "ConversionOptimizer";
  }

  public function __construct($pricingModel = NULL, $BiddingStrategyType = NULL) {
    if(get_parent_class('ConversionOptimizer')) parent::__construct();
    $this->pricingModel = $pricingModel;
    $this->BiddingStrategyType = $BiddingStrategyType;
  }
}}

if (!class_exists("ConversionOptimizerBiddingTransition", FALSE)) {
/**
 * Used to switch a campaign's bidding strategy to conversion optimizer.
 */
class ConversionOptimizerBiddingTransition extends BiddingTransition {
  /**
   * @access public
   * @var boolean
   */
  public $useSavedBids;

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
    return "ConversionOptimizerBiddingTransition";
  }

  public function __construct($useSavedBids = NULL, $targetBiddingStrategy = NULL, $explicitAdGroupBids = NULL, $BiddingTransitionType = NULL) {
    if(get_parent_class('ConversionOptimizerBiddingTransition')) parent::__construct();
    $this->useSavedBids = $useSavedBids;
    $this->targetBiddingStrategy = $targetBiddingStrategy;
    $this->explicitAdGroupBids = $explicitAdGroupBids;
    $this->BiddingTransitionType = $BiddingTransitionType;
  }
}}

if (!class_exists("DatabaseError", FALSE)) {
/**
 * Errors that are thrown due to a database access problem.
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

if (!class_exists("ManualCPC", FALSE)) {
/**
 * Manual click based bidding where user pays per click.
 */
class ManualCPC extends BiddingStrategy {
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
    return "ManualCPC";
  }

  public function __construct($BiddingStrategyType = NULL) {
    if(get_parent_class('ManualCPC')) parent::__construct();
    $this->BiddingStrategyType = $BiddingStrategyType;
  }
}}

if (!class_exists("ManualCPM", FALSE)) {
/**
 * Manual impression based bidding where user pays per thousand impressions.
 */
class ManualCPM extends BiddingStrategy {
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
    return "ManualCPM";
  }

  public function __construct($BiddingStrategyType = NULL) {
    if(get_parent_class('ManualCPM')) parent::__construct();
    $this->BiddingStrategyType = $BiddingStrategyType;
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

if (!class_exists("NewEntityCreationError", FALSE)) {
/**
 * Error associated with creation of new entities using
 * {@link com.google.ads.api.services.common.id.TempId}s.
 */
class NewEntityCreationError extends ApiError {
  /**
   * @access public
   * @var tnsNewEntityCreationErrorReason
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
    return "NewEntityCreationError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('NewEntityCreationError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
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

if (!class_exists("OperationAccessDenied", FALSE)) {
/**
 * Unauthorized access errors as determined by the invoked service's
 * access policy.
 */
class OperationAccessDenied extends ApiError {
  /**
   * @access public
   * @var tnsOperationAccessDeniedReason
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
    return "OperationAccessDenied";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('OperationAccessDenied')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
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

if (!class_exists("QuotaError", FALSE)) {
/**
 * Errors that are thrown if a quota limit is exceeded.
 */
class QuotaError extends ApiError {
  /**
   * @access public
   * @var tnsQuotaErrorReason
   */
  public $reason;

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
    return "QuotaError";
  }

  public function __construct($reason = NULL, $limit = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('QuotaError')) parent::__construct();
    $this->reason = $reason;
    $this->limit = $limit;
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

if (!class_exists("StatsQueryError", FALSE)) {
/**
 * Represents possible error codes when querying for stats.
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

if (!class_exists("BudgetOptimizer", FALSE)) {
/**
 * In budget optimizer, Google automatically places bids for the user
 * based on their daily/monthly budget.
 */
class BudgetOptimizer extends BiddingStrategy {
  /**
   * @access public
   * @var Money
   */
  public $bidCeiling;

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
    return "BudgetOptimizer";
  }

  public function __construct($bidCeiling = NULL, $BiddingStrategyType = NULL) {
    if(get_parent_class('BudgetOptimizer')) parent::__construct();
    $this->bidCeiling = $bidCeiling;
    $this->BiddingStrategyType = $BiddingStrategyType;
  }
}}

if (!class_exists("BudgetOptimizerAdGroupBids", FALSE)) {
/**
 * Adgroup level bids used in budget optimizer bidding strategy.
 */
class BudgetOptimizerAdGroupBids extends AdGroupBids {
  /**
   * @access public
   * @var Bid
   */
  public $proxyKeywordMaxCpc;

  /**
   * @access public
   * @var Bid
   */
  public $proxySiteMaxCpc;

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
    return "BudgetOptimizerAdGroupBids";
  }

  public function __construct($proxyKeywordMaxCpc = NULL, $proxySiteMaxCpc = NULL, $AdGroupBidsType = NULL) {
    if(get_parent_class('BudgetOptimizerAdGroupBids')) parent::__construct();
    $this->proxyKeywordMaxCpc = $proxyKeywordMaxCpc;
    $this->proxySiteMaxCpc = $proxySiteMaxCpc;
    $this->AdGroupBidsType = $AdGroupBidsType;
  }
}}

if (!class_exists("ConversionOptimizerAdGroupBids", FALSE)) {
/**
 * Adgroup level bids used in conversion optimizer bidding strategy.
 */
class ConversionOptimizerAdGroupBids extends AdGroupBids {
  /**
   * @access public
   * @var Bid
   */
  public $targetCpa;

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
    return "ConversionOptimizerAdGroupBids";
  }

  public function __construct($targetCpa = NULL, $AdGroupBidsType = NULL) {
    if(get_parent_class('ConversionOptimizerAdGroupBids')) parent::__construct();
    $this->targetCpa = $targetCpa;
    $this->AdGroupBidsType = $AdGroupBidsType;
  }
}}

if (!class_exists("ManualCPCAdGroupBids", FALSE)) {
/**
 * Adgroup level bids used in manual CPC bidding strategy.
 */
class ManualCPCAdGroupBids extends AdGroupBids {
  /**
   * @access public
   * @var Bid
   */
  public $keywordMaxCpc;

  /**
   * @access public
   * @var Bid
   */
  public $keywordContentMaxCpc;

  /**
   * @access public
   * @var Bid
   */
  public $siteMaxCpc;

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
    return "ManualCPCAdGroupBids";
  }

  public function __construct($keywordMaxCpc = NULL, $keywordContentMaxCpc = NULL, $siteMaxCpc = NULL, $AdGroupBidsType = NULL) {
    if(get_parent_class('ManualCPCAdGroupBids')) parent::__construct();
    $this->keywordMaxCpc = $keywordMaxCpc;
    $this->keywordContentMaxCpc = $keywordContentMaxCpc;
    $this->siteMaxCpc = $siteMaxCpc;
    $this->AdGroupBidsType = $AdGroupBidsType;
  }
}}

if (!class_exists("ManualCPMAdGroupBids", FALSE)) {
/**
 * Adgroup level bids used in manual CPM bidding strategy.
 */
class ManualCPMAdGroupBids extends AdGroupBids {
  /**
   * @access public
   * @var Bid
   */
  public $maxCpm;

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
    return "ManualCPMAdGroupBids";
  }

  public function __construct($maxCpm = NULL, $AdGroupBidsType = NULL) {
    if(get_parent_class('ManualCPMAdGroupBids')) parent::__construct();
    $this->maxCpm = $maxCpm;
    $this->AdGroupBidsType = $AdGroupBidsType;
  }
}}

if (!class_exists("CampaignOperation", FALSE)) {
/**
 * Operations (add, update, remove) class for campaigns.
 */
class CampaignOperation extends Operation {
  /**
   * @access public
   * @var BiddingTransition
   */
  public $biddingTransition;

  /**
   * @access public
   * @var Campaign
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
    return "CampaignOperation";
  }

  public function __construct($biddingTransition = NULL, $operand = NULL, $operator = NULL, $OperationType = NULL) {
    if(get_parent_class('CampaignOperation')) parent::__construct();
    $this->biddingTransition = $biddingTransition;
    $this->operand = $operand;
    $this->operator = $operator;
    $this->OperationType = $OperationType;
  }
}}

if (!class_exists("CampaignPage", FALSE)) {
/**
 * Contains a subset of campaign resulting from the filtering and paging of the
 * {@link com.google.ads.api.services.campaignmgmt.campaign.CampaignService#get} call.
 */
class CampaignPage extends Page {
  /**
   * @access public
   * @var Budget
   */
  public $totalBudget;

  /**
   * @access public
   * @var Campaign[]
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
    return "CampaignPage";
  }

  public function __construct($totalBudget = NULL, $entries = NULL, $totalNumEntries = NULL, $PageType = NULL) {
    if(get_parent_class('CampaignPage')) parent::__construct();
    $this->totalBudget = $totalBudget;
    $this->entries = $entries;
    $this->totalNumEntries = $totalNumEntries;
    $this->PageType = $PageType;
  }
}}

if (!class_exists("CampaignReturnValue", FALSE)) {
/**
 * A container for return values from the CampaignService.
 */
class CampaignReturnValue extends ListReturnValue {
  /**
   * @access public
   * @var Campaign[]
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
    return "CampaignReturnValue";
  }

  public function __construct($value = NULL, $ListReturnValueType = NULL) {
    if(get_parent_class('CampaignReturnValue')) parent::__construct();
    $this->value = $value;
    $this->ListReturnValueType = $ListReturnValueType;
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

if (!class_exists("CampaignService")) {
/**
 * CampaignService
 * @author WSDLInterpreter
 */
class CampaignService extends AdWordsSoapClient {
  /**
   * Default class map for wsdl=>php
   * @access private
   * @var array
   */
  public static $classmap = array(
    "getResponse" => "CampaignServiceGetResponse",
    "get" => "CampaignServiceGet",
    "mutate" => "CampaignServiceMutate",
    "mutateResponse" => "CampaignServiceMutateResponse",
    "DateTime" => "AdWordsDateTime",
    "Target" => "AdWordsTarget",
    "SoapHeader" => "SoapRequestHeader",
    "AuthenticationError" => "AuthenticationError",
    "ApiError" => "ApiError",
    "AuthorizationError" => "AuthorizationError",
    "BiddingError" => "BiddingError",
    "BiddingTransitionError" => "BiddingTransitionError",
    "BudgetError" => "BudgetError",
    "CampaignError" => "CampaignError",
    "ClientTermsError" => "ClientTermsError",
    "ConversionOptimizer" => "ConversionOptimizer",
    "BiddingStrategy" => "BiddingStrategy",
    "ConversionOptimizerBiddingTransition" => "ConversionOptimizerBiddingTransition",
    "BiddingTransition" => "BiddingTransition",
    "DatabaseError" => "DatabaseError",
    "DateError" => "DateError",
    "DateRange" => "DateRange",
    "DistinctError" => "DistinctError",
    "DoubleValue" => "DoubleValue",
    "NumberValue" => "NumberValue",
    "EntityNotFound" => "EntityNotFound",
    "FrequencyCap" => "FrequencyCap",
    "InternalApiError" => "InternalApiError",
    "LongValue" => "LongValue",
    "ManualCPC" => "ManualCPC",
    "ManualCPM" => "ManualCPM",
    "Money" => "Money",
    "ComparableValue" => "ComparableValue",
    "NewEntityCreationError" => "NewEntityCreationError",
    "NotEmptyError" => "NotEmptyError",
    "NotWhitelistedError" => "NotWhitelistedError",
    "NullError" => "NullError",
    "OperationAccessDenied" => "OperationAccessDenied",
    "OperatorError" => "OperatorError",
    "Paging" => "Paging",
    "QuotaCheckError" => "QuotaCheckError",
    "QuotaError" => "QuotaError",
    "QuotaExceededError" => "QuotaExceededError",
    "RangeError" => "RangeError",
    "ReadOnlyError" => "ReadOnlyError",
    "RequiredError" => "RequiredError",
    "SizeLimitError" => "SizeLimitError",
    "SoapResponseHeader" => "SoapResponseHeader",
    "StatsQueryError" => "StatsQueryError",
    "StatsSelector" => "StatsSelector",
    "StringLengthError" => "StringLengthError",
    "Stats" => "Stats",
    "ApiException" => "ApiException",
    "ApplicationException" => "ApplicationException",
    "Bid" => "Bid",
    "Budget" => "Budget",
    "BudgetOptimizer" => "BudgetOptimizer",
    "BudgetOptimizerAdGroupBids" => "BudgetOptimizerAdGroupBids",
    "AdGroupBids" => "AdGroupBids",
    "ConversionOptimizerAdGroupBids" => "ConversionOptimizerAdGroupBids",
    "ManualCPCAdGroupBids" => "ManualCPCAdGroupBids",
    "ManualCPMAdGroupBids" => "ManualCPMAdGroupBids",
    "Campaign" => "Campaign",
    "CampaignOperation" => "CampaignOperation",
    "Operation" => "Operation",
    "CampaignPage" => "CampaignPage",
    "Page" => "Page",
    "CampaignReturnValue" => "CampaignReturnValue",
    "ListReturnValue" => "ListReturnValue",
    "CampaignSelector" => "CampaignSelector",
    "AdServingOptimizationStatus" => "AdServingOptimizationStatus",
    "AuthenticationError.Reason" => "AuthenticationErrorReason",
    "AuthorizationError.Reason" => "AuthorizationErrorReason",
    "AutoKeywordMatchingStatus" => "AutoKeywordMatchingStatus",
    "BiddingError.Reason" => "BiddingErrorReason",
    "BiddingTransitionError.Reason" => "BiddingTransitionErrorReason",
    "Budget.BudgetDeliveryMethod" => "BudgetBudgetDeliveryMethod",
    "Budget.BudgetPeriod" => "BudgetBudgetPeriod",
    "BudgetError.Reason" => "BudgetErrorReason",
    "CampaignError.Reason" => "CampaignErrorReason",
    "CampaignStatus" => "CampaignStatus",
    "ClientTermsError.Reason" => "ClientTermsErrorReason",
    "DatabaseError.Reason" => "DatabaseErrorReason",
    "DateError.Reason" => "DateErrorReason",
    "DistinctError.Reason" => "DistinctErrorReason",
    "EntityNotFound.Reason" => "EntityNotFoundReason",
    "InternalApiError.Reason" => "InternalApiErrorReason",
    "Level" => "Level",
    "NewEntityCreationError.Reason" => "NewEntityCreationErrorReason",
    "NotEmptyError.Reason" => "NotEmptyErrorReason",
    "NotWhitelistedError.Reason" => "NotWhitelistedErrorReason",
    "NullError.Reason" => "NullErrorReason",
    "OperationAccessDenied.Reason" => "OperationAccessDeniedReason",
    "Operator" => "Operator",
    "OperatorError.Reason" => "OperatorErrorReason",
    "PricingModel" => "PricingModel",
    "QuotaCheckError.Reason" => "QuotaCheckErrorReason",
    "QuotaError.Reason" => "QuotaErrorReason",
    "QuotaExceededError.Reason" => "QuotaExceededErrorReason",
    "RangeError.Reason" => "RangeErrorReason",
    "ReadOnlyError.Reason" => "ReadOnlyErrorReason",
    "RequiredError.Reason" => "RequiredErrorReason",
    "ServingStatus" => "ServingStatus",
    "SizeLimitError.Reason" => "SizeLimitErrorReason",
    "Stats.Network" => "StatsNetwork",
    "StatsQueryError.Reason" => "StatsQueryErrorReason",
    "StringLengthError.Reason" => "StringLengthErrorReason",
    "TimeUnit" => "TimeUnit",
  );

  /**
   * Constructor using wsdl location and options array
   * @param string $wsdl WSDL location for this service
   * @param array $options Options for the SoapClient
   */
  public function __construct($wsdl=null, $options, $user) {
    $options["classmap"] = CampaignService::$classmap;
    parent::__construct($wsdl, $options, $user, 'CampaignService', 'https://adwords.google.com/api/adwords/cm/v200909');
  }

  /**
   * <span class="constraint Required">This field is required and should not be {@code null}.</span>
   * 
   * 
   * 
   * Returns a list of campaigns specified by the list of selectors from
   * the customer's account. Each selector (filter) in the list results in
   * a set of campaigns. The final result is the union of each of these sets.
   * @param selector filter to run campaigns through.
   * If selector is empty, all campaigns are returned.
   * @return list of campaigns meeting all the criteria of each selector.
   * @throws ApiException if problems occurred while fetching campaign information.
   */
  public function get($selector) {
    $arg = new CampaignServiceGet($selector);
    $result = $this->__soapCall("get", array($arg));
    return $result->rval;
  }


  /**
   * <span class="constraint ContentsNotNull">This field must not contain {@code null} elements.</span>
   * <span class="constraint DistinctIds">Elements in this field must have distinct IDs for following {@link Operator}s : SET, REMOVE.</span>
   * <span class="constraint NotEmpty">This field must contain at least one element.</span>
   * <span class="constraint Required">This field is required and should not be {@code null}.</span>
   * <span class="constraint SuppoprtedOperators">The following {@link Operator}s are supported: ADD, SET.</span>
   * 
   * 
   * 
   * Adds, updates, or removes campaigns.
   * Note: to REMOVE use SET and mark status to DELETE.
   * @param operations a list of unique operations.
   * The same campaign cannot be specified in more than one operation.
   * @return the added campaigns.
   * The list of campaigns is returned in the same order in which it came in as input.
   * @throws ApiException if problems occurred while updating campaign information.
   */
  public function mutate($operations) {
    $arg = new CampaignServiceMutate($operations);
    $result = $this->__soapCall("mutate", array($arg));
    return $result->rval;
  }


}}

?>