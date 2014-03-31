<?php
/**
 * Contains all client objects for the BulkMutateJobService service.
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

if (!class_exists("Ad", FALSE)) {
/**
 * The base class of all Ad types.  Not abstract since it is conceivable for
 * the base attributes of an Ad to be updated without reference to its concrete
 * type.
 */
class Ad {
  /**
   * @access public
   * @var integer
   */
  public $id;

  /**
   * @access public
   * @var string
   */
  public $url;

  /**
   * @access public
   * @var string
   */
  public $displayUrl;

  /**
   * @access public
   * @var tnsAdApprovalStatus
   */
  public $approvalStatus;

  /**
   * @access public
   * @var string[]
   */
  public $disapprovalReasons;

  /**
   * @access public
   * @var string
   */
  public $AdType;

  private $_parameterMap = array (
    "Ad.Type" => "AdType",
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
    return "Ad";
  }

  public function __construct($id = NULL, $url = NULL, $displayUrl = NULL, $approvalStatus = NULL, $disapprovalReasons = NULL, $AdType = NULL) {
    if(get_parent_class('Ad')) parent::__construct();
    $this->id = $id;
    $this->url = $url;
    $this->displayUrl = $displayUrl;
    $this->approvalStatus = $approvalStatus;
    $this->disapprovalReasons = $disapprovalReasons;
    $this->AdType = $AdType;
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

if (!class_exists("AdGroup", FALSE)) {
/**
 * Represents the AdGroup object that is sent over the wire.
 */
class AdGroup {
  /**
   * @access public
   * @var integer
   */
  public $id;

  /**
   * @access public
   * @var integer
   */
  public $campaignId;

  /**
   * @access public
   * @var string
   */
  public $campaignName;

  /**
   * @access public
   * @var string
   */
  public $name;

  /**
   * @access public
   * @var tnsAdGroupStatus
   */
  public $status;

  /**
   * @access public
   * @var AdGroupBids
   */
  public $bids;

  /**
   * @access public
   * @var Stats
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
    return "AdGroup";
  }

  public function __construct($id = NULL, $campaignId = NULL, $campaignName = NULL, $name = NULL, $status = NULL, $bids = NULL, $stats = NULL) {
    if(get_parent_class('AdGroup')) parent::__construct();
    $this->id = $id;
    $this->campaignId = $campaignId;
    $this->campaignName = $campaignName;
    $this->name = $name;
    $this->status = $status;
    $this->bids = $bids;
    $this->stats = $stats;
  }
}}

if (!class_exists("AdGroupAd", FALSE)) {
/**
 * Represents an ad in an ad group.
 */
class AdGroupAd {
  /**
   * @access public
   * @var integer
   */
  public $adGroupId;

  /**
   * @access public
   * @var Ad
   */
  public $ad;

  /**
   * @access public
   * @var tnsAdGroupAdStatus
   */
  public $status;

  /**
   * @access public
   * @var AdStats
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
    return "AdGroupAd";
  }

  public function __construct($adGroupId = NULL, $ad = NULL, $status = NULL, $stats = NULL) {
    if(get_parent_class('AdGroupAd')) parent::__construct();
    $this->adGroupId = $adGroupId;
    $this->ad = $ad;
    $this->status = $status;
    $this->stats = $stats;
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

if (!class_exists("AdGroupCriterion", FALSE)) {
/**
 * Represents a crterion in an ad group.
 * 
 * <p>May be instantiated to refer to ad group criteria to be deleted.
 */
class AdGroupCriterion {
  /**
   * @access public
   * @var integer
   */
  public $adGroupId;

  /**
   * @access public
   * @var Criterion
   */
  public $criterion;

  /**
   * @access public
   * @var string
   */
  public $AdGroupCriterionType;

  private $_parameterMap = array (
    "AdGroupCriterion.Type" => "AdGroupCriterionType",
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
    return "AdGroupCriterion";
  }

  public function __construct($adGroupId = NULL, $criterion = NULL, $AdGroupCriterionType = NULL) {
    if(get_parent_class('AdGroupCriterion')) parent::__construct();
    $this->adGroupId = $adGroupId;
    $this->criterion = $criterion;
    $this->AdGroupCriterionType = $AdGroupCriterionType;
  }
}}

if (!class_exists("AdGroupCriterionBids", FALSE)) {
/**
 * Represents criterion level bids.
 */
class AdGroupCriterionBids {
  /**
   * @access public
   * @var string
   */
  public $AdGroupCriterionBidsType;

  private $_parameterMap = array (
    "AdGroupCriterionBids.Type" => "AdGroupCriterionBidsType",
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
    return "AdGroupCriterionBids";
  }

  public function __construct($AdGroupCriterionBidsType = NULL) {
    if(get_parent_class('AdGroupCriterionBids')) parent::__construct();
    $this->AdGroupCriterionBidsType = $AdGroupCriterionBidsType;
  }
}}

if (!class_exists("AdUnionId", FALSE)) {
/**
 * Represents an id indicating a grouping of Ads under some heuristic.
 */
class AdUnionId {
  /**
   * @access public
   * @var integer
   */
  public $id;

  /**
   * @access public
   * @var string
   */
  public $AdUnionIdType;

  private $_parameterMap = array (
    "AdUnionId.Type" => "AdUnionIdType",
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
    return "AdUnionId";
  }

  public function __construct($id = NULL, $AdUnionIdType = NULL) {
    if(get_parent_class('AdUnionId')) parent::__construct();
    $this->id = $id;
    $this->AdUnionIdType = $AdUnionIdType;
  }
}}

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

if (!class_exists("ApiErrorReason", FALSE)) {
/**
 * Interface that has a reason return an associated service error.
 */
class ApiErrorReason {
  /**
   * @access public
   * @var tnsAdErrorReason
   */
  public $AdErrorReason;

  /**
   * @access public
   * @var tnsAdGroupAdErrorReason
   */
  public $AdGroupAdErrorReason;

  /**
   * @access public
   * @var tnsAdGroupCriterionErrorReason
   */
  public $AdGroupCriterionErrorReason;

  /**
   * @access public
   * @var tnsAdGroupServiceErrorReason
   */
  public $AdGroupServiceErrorReason;

  /**
   * @access public
   * @var tnsAuthenticationErrorReason
   */
  public $AuthenticationErrorReason;

  /**
   * @access public
   * @var tnsAuthorizationErrorReason
   */
  public $AuthorizationErrorReason;

  /**
   * @access public
   * @var tnsBiddingErrorReason
   */
  public $BiddingErrorReason;

  /**
   * @access public
   * @var tnsBiddingTransitionErrorReason
   */
  public $BiddingTransitionErrorReason;

  /**
   * @access public
   * @var tnsBudgetErrorReason
   */
  public $BudgetErrorReason;

  /**
   * @access public
   * @var tnsBulkMutateJobErrorReason
   */
  public $BulkMutateJobErrorReason;

  /**
   * @access public
   * @var tnsCampaignCriterionErrorReason
   */
  public $CampaignCriterionErrorReason;

  /**
   * @access public
   * @var tnsCampaignErrorReason
   */
  public $CampaignErrorReason;

  /**
   * @access public
   * @var tnsClientTermsErrorReason
   */
  public $ClientTermsErrorReason;

  /**
   * @access public
   * @var tnsDatabaseErrorReason
   */
  public $DatabaseErrorReason;

  /**
   * @access public
   * @var tnsDateErrorReason
   */
  public $DateErrorReason;

  /**
   * @access public
   * @var tnsDistinctErrorReason
   */
  public $DistinctErrorReason;

  /**
   * @access public
   * @var tnsEntityAccessDeniedReason
   */
  public $EntityAccessDeniedReason;

  /**
   * @access public
   * @var tnsEntityCountLimitExceededReason
   */
  public $EntityCountLimitExceededReason;

  /**
   * @access public
   * @var tnsEntityNotFoundReason
   */
  public $EntityNotFoundReason;

  /**
   * @access public
   * @var tnsIdErrorReason
   */
  public $IdErrorReason;

  /**
   * @access public
   * @var tnsImageErrorReason
   */
  public $ImageErrorReason;

  /**
   * @access public
   * @var tnsInternalApiErrorReason
   */
  public $InternalApiErrorReason;

  /**
   * @access public
   * @var tnsJobErrorReason
   */
  public $JobErrorReason;

  /**
   * @access public
   * @var tnsLoasAuthenticationErrorReason
   */
  public $LoasAuthenticationErrorReason;

  /**
   * @access public
   * @var tnsMediaErrorReason
   */
  public $MediaErrorReason;

  /**
   * @access public
   * @var tnsNewEntityCreationErrorReason
   */
  public $NewEntityCreationErrorReason;

  /**
   * @access public
   * @var tnsNotEmptyErrorReason
   */
  public $NotEmptyErrorReason;

  /**
   * @access public
   * @var tnsNotWhitelistedErrorReason
   */
  public $NotWhitelistedErrorReason;

  /**
   * @access public
   * @var tnsNullErrorReason
   */
  public $NullErrorReason;

  /**
   * @access public
   * @var tnsOperationAccessDeniedReason
   */
  public $OperationAccessDeniedReason;

  /**
   * @access public
   * @var tnsOperatorErrorReason
   */
  public $OperatorErrorReason;

  /**
   * @access public
   * @var tnsPagingErrorReason
   */
  public $PagingErrorReason;

  /**
   * @access public
   * @var tnsPolicyViolationErrorReason
   */
  public $PolicyViolationErrorReason;

  /**
   * @access public
   * @var tnsQuotaCheckErrorReason
   */
  public $QuotaCheckErrorReason;

  /**
   * @access public
   * @var tnsQuotaErrorReason
   */
  public $QuotaErrorReason;

  /**
   * @access public
   * @var tnsQuotaExceededErrorReason
   */
  public $QuotaExceededErrorReason;

  /**
   * @access public
   * @var tnsRangeErrorReason
   */
  public $RangeErrorReason;

  /**
   * @access public
   * @var tnsReadOnlyErrorReason
   */
  public $ReadOnlyErrorReason;

  /**
   * @access public
   * @var tnsRegionCodeErrorReason
   */
  public $RegionCodeErrorReason;

  /**
   * @access public
   * @var tnsRequiredErrorReason
   */
  public $RequiredErrorReason;

  /**
   * @access public
   * @var tnsSizeLimitErrorReason
   */
  public $SizeLimitErrorReason;

  /**
   * @access public
   * @var tnsStatsQueryErrorReason
   */
  public $StatsQueryErrorReason;

  /**
   * @access public
   * @var tnsStringLengthErrorReason
   */
  public $StringLengthErrorReason;

  /**
   * @access public
   * @var tnsTargetErrorReason
   */
  public $TargetErrorReason;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ApiErrorReason";
  }

  public function __construct($AdErrorReason = NULL, $AdGroupAdErrorReason = NULL, $AdGroupCriterionErrorReason = NULL, $AdGroupServiceErrorReason = NULL, $AuthenticationErrorReason = NULL, $AuthorizationErrorReason = NULL, $BiddingErrorReason = NULL, $BiddingTransitionErrorReason = NULL, $BudgetErrorReason = NULL, $BulkMutateJobErrorReason = NULL, $CampaignCriterionErrorReason = NULL, $CampaignErrorReason = NULL, $ClientTermsErrorReason = NULL, $DatabaseErrorReason = NULL, $DateErrorReason = NULL, $DistinctErrorReason = NULL, $EntityAccessDeniedReason = NULL, $EntityCountLimitExceededReason = NULL, $EntityNotFoundReason = NULL, $IdErrorReason = NULL, $ImageErrorReason = NULL, $InternalApiErrorReason = NULL, $JobErrorReason = NULL, $LoasAuthenticationErrorReason = NULL, $MediaErrorReason = NULL, $NewEntityCreationErrorReason = NULL, $NotEmptyErrorReason = NULL, $NotWhitelistedErrorReason = NULL, $NullErrorReason = NULL, $OperationAccessDeniedReason = NULL, $OperatorErrorReason = NULL, $PagingErrorReason = NULL, $PolicyViolationErrorReason = NULL, $QuotaCheckErrorReason = NULL, $QuotaErrorReason = NULL, $QuotaExceededErrorReason = NULL, $RangeErrorReason = NULL, $ReadOnlyErrorReason = NULL, $RegionCodeErrorReason = NULL, $RequiredErrorReason = NULL, $SizeLimitErrorReason = NULL, $StatsQueryErrorReason = NULL, $StringLengthErrorReason = NULL, $TargetErrorReason = NULL) {
    if(get_parent_class('ApiErrorReason')) parent::__construct();
    $this->AdErrorReason = $AdErrorReason;
    $this->AdGroupAdErrorReason = $AdGroupAdErrorReason;
    $this->AdGroupCriterionErrorReason = $AdGroupCriterionErrorReason;
    $this->AdGroupServiceErrorReason = $AdGroupServiceErrorReason;
    $this->AuthenticationErrorReason = $AuthenticationErrorReason;
    $this->AuthorizationErrorReason = $AuthorizationErrorReason;
    $this->BiddingErrorReason = $BiddingErrorReason;
    $this->BiddingTransitionErrorReason = $BiddingTransitionErrorReason;
    $this->BudgetErrorReason = $BudgetErrorReason;
    $this->BulkMutateJobErrorReason = $BulkMutateJobErrorReason;
    $this->CampaignCriterionErrorReason = $CampaignCriterionErrorReason;
    $this->CampaignErrorReason = $CampaignErrorReason;
    $this->ClientTermsErrorReason = $ClientTermsErrorReason;
    $this->DatabaseErrorReason = $DatabaseErrorReason;
    $this->DateErrorReason = $DateErrorReason;
    $this->DistinctErrorReason = $DistinctErrorReason;
    $this->EntityAccessDeniedReason = $EntityAccessDeniedReason;
    $this->EntityCountLimitExceededReason = $EntityCountLimitExceededReason;
    $this->EntityNotFoundReason = $EntityNotFoundReason;
    $this->IdErrorReason = $IdErrorReason;
    $this->ImageErrorReason = $ImageErrorReason;
    $this->InternalApiErrorReason = $InternalApiErrorReason;
    $this->JobErrorReason = $JobErrorReason;
    $this->LoasAuthenticationErrorReason = $LoasAuthenticationErrorReason;
    $this->MediaErrorReason = $MediaErrorReason;
    $this->NewEntityCreationErrorReason = $NewEntityCreationErrorReason;
    $this->NotEmptyErrorReason = $NotEmptyErrorReason;
    $this->NotWhitelistedErrorReason = $NotWhitelistedErrorReason;
    $this->NullErrorReason = $NullErrorReason;
    $this->OperationAccessDeniedReason = $OperationAccessDeniedReason;
    $this->OperatorErrorReason = $OperatorErrorReason;
    $this->PagingErrorReason = $PagingErrorReason;
    $this->PolicyViolationErrorReason = $PolicyViolationErrorReason;
    $this->QuotaCheckErrorReason = $QuotaCheckErrorReason;
    $this->QuotaErrorReason = $QuotaErrorReason;
    $this->QuotaExceededErrorReason = $QuotaExceededErrorReason;
    $this->RangeErrorReason = $RangeErrorReason;
    $this->ReadOnlyErrorReason = $ReadOnlyErrorReason;
    $this->RegionCodeErrorReason = $RegionCodeErrorReason;
    $this->RequiredErrorReason = $RequiredErrorReason;
    $this->SizeLimitErrorReason = $SizeLimitErrorReason;
    $this->StatsQueryErrorReason = $StatsQueryErrorReason;
    $this->StringLengthErrorReason = $StringLengthErrorReason;
    $this->TargetErrorReason = $TargetErrorReason;
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

if (!class_exists("BiddableAdGroupCriterion", FALSE)) {
/**
 * A biddable (positive) criterion in an adgroup.
 */
class BiddableAdGroupCriterion extends AdGroupCriterion {
  /**
   * @access public
   * @var tnsUserStatus
   */
  public $userStatus;

  /**
   * @access public
   * @var tnsSystemServingStatus
   */
  public $systemServingStatus;

  /**
   * @access public
   * @var tnsApprovalStatus
   */
  public $approvalStatus;

  /**
   * @access public
   * @var string
   */
  public $destinationUrl;

  /**
   * @access public
   * @var AdGroupCriterionBids
   */
  public $bids;

  /**
   * @access public
   * @var Bid
   */
  public $firstPageCpc;

  /**
   * @access public
   * @var QualityInfo
   */
  public $qualityInfo;

  /**
   * @access public
   * @var Stats
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
    return "BiddableAdGroupCriterion";
  }

  public function __construct($userStatus = NULL, $systemServingStatus = NULL, $approvalStatus = NULL, $destinationUrl = NULL, $bids = NULL, $firstPageCpc = NULL, $qualityInfo = NULL, $stats = NULL, $adGroupId = NULL, $criterion = NULL, $AdGroupCriterionType = NULL) {
    if(get_parent_class('BiddableAdGroupCriterion')) parent::__construct();
    $this->userStatus = $userStatus;
    $this->systemServingStatus = $systemServingStatus;
    $this->approvalStatus = $approvalStatus;
    $this->destinationUrl = $destinationUrl;
    $this->bids = $bids;
    $this->firstPageCpc = $firstPageCpc;
    $this->qualityInfo = $qualityInfo;
    $this->stats = $stats;
    $this->adGroupId = $adGroupId;
    $this->criterion = $criterion;
    $this->AdGroupCriterionType = $AdGroupCriterionType;
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

if (!class_exists("BillingSummary", FALSE)) {
/**
 * Represents the billing summary of the job that provides the overall cost of
 * the job.
 */
class BillingSummary {
  /**
   * @access public
   * @var integer
   */
  public $numOperations;

  /**
   * @access public
   * @var integer
   */
  public $numUnits;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BillingSummary";
  }

  public function __construct($numOperations = NULL, $numUnits = NULL) {
    if(get_parent_class('BillingSummary')) parent::__construct();
    $this->numOperations = $numOperations;
    $this->numUnits = $numUnits;
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

if (!class_exists("BudgetOptimizerAdGroupCriterionBids", FALSE)) {
/**
 * AdGroupCriterion level bids used in budget optimizer bidding strategy.
 */
class BudgetOptimizerAdGroupCriterionBids extends AdGroupCriterionBids {
  /**
   * @access public
   * @var Bid
   */
  public $proxyBid;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BudgetOptimizerAdGroupCriterionBids";
  }

  public function __construct($proxyBid = NULL, $AdGroupCriterionBidsType = NULL) {
    if(get_parent_class('BudgetOptimizerAdGroupCriterionBids')) parent::__construct();
    $this->proxyBid = $proxyBid;
    $this->AdGroupCriterionBidsType = $AdGroupCriterionBidsType;
  }
}}

if (!class_exists("BulkMutateJobError", FALSE)) {
/**
 * Bulk mutate job handling errors.
 */
class BulkMutateJobError extends ApiError {
  /**
   * @access public
   * @var tnsBulkMutateJobErrorReason
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
    return "BulkMutateJobError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('BulkMutateJobError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("BulkMutateJobPolicy", FALSE)) {
/**
 * A basic job policy.
 */
class BulkMutateJobPolicy {
  /**
   * @access public
   * @var integer[]
   */
  public $prerequisiteJobIds;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BulkMutateJobPolicy";
  }

  public function __construct($prerequisiteJobIds = NULL) {
    if(get_parent_class('BulkMutateJobPolicy')) parent::__construct();
    $this->prerequisiteJobIds = $prerequisiteJobIds;
  }
}}

if (!class_exists("BulkMutateRequest", FALSE)) {
/**
 * A request part of a {@link BulkMutateJob}.
 */
class BulkMutateRequest {
  /**
   * @access public
   * @var integer
   */
  public $partIndex;

  /**
   * @access public
   * @var OperationStream[]
   */
  public $operationStreams;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BulkMutateRequest";
  }

  public function __construct($partIndex = NULL, $operationStreams = NULL) {
    if(get_parent_class('BulkMutateRequest')) parent::__construct();
    $this->partIndex = $partIndex;
    $this->operationStreams = $operationStreams;
  }
}}

if (!class_exists("BulkMutateResult", FALSE)) {
/**
 * Represents a result part of a {@link BulkMutateJob}.
 * 
 * <p>Once one or more mutate operations have been processed, the bulk mutate
 * job will eventually be completed. This means that the job will have a result
 * corresponding to each operation. The types of results for an operation are:
 * <ul>
 * <li>{@link ReturnValueResult}: the corresponding operation was successfully
 * processed and this result contains the return value of that operation;
 * </li>
 * <li>{@link FailureResult} or {@link BatchFailureResult}: the corresponding
 * operation was in a batch that failed with errors. Please inspect these
 * errors to determine if the operation itself was faulty and if so what
 * errors need to be fixed before you submit this operation as part of a
 * future job.</li>
 * <li>{@link LostResult}: the corresponding operation was processed - it was
 * either successful and had a return value, or it was a part of a failed
 * batch. Unfortunately, despite our best efforts, we were unable to
 * durably save the result. You may have to retrieve the related state of
 * your account to determine if this operation actually succeeded.</li>
 * <li>{@link UnprocessedResult}: the corresponding operation was not processed
 * because the job was aborted <i>Note: The job's status will be
 * {@code COMPLETED}, signifying that although it may have some operations
 * that were processed, it cannot be resumed.</i></li></p>
 * 
 * <p>The results of a job are structured so that the each operation can be
 * easily matched to its corresponding result. So for every
 * {@link BulkMutateRequest} in a job, there is a corresponding
 * {@code BulkMutateResult}; For every {@link OperationStream} in a request
 * part, there is a corresponding {@link OperationStreamResult}; And, for
 * every {@link Operation} in an operation stream, there is an
 * {@link OperationResult}.</p>
 * 
 * <p>Within an operation stream, the operations are processed in atomic
 * batches. So even one faulty operation may cause its neighboring non-faulty
 * operations to fail just because they happened to be processed in the same
 * batch. It is important to keep this batch failure reporting scheme in mind
 * when handling operation failures.</p>
 * 
 * <p>There will be a single {@link ApiException} returned for each failing
 * batch of operations, and it will have a similar structure and
 * interpretation to the exceptions returned by the synchronous AdWords API
 * services. This exception is returned in a {@link FailureResult} that
 * corresponds to the first operation of the failing batch.</p>
 * 
 * <p>The exception may contain multiple errors, and each error may have
 * {@code OGNL path}s that identify the faulty operation(s) by index (or
 * position within the batch). The first operation in the batch has an
 * implicit index of 0. The subsequent operations in the batch have a
 * corresponding {@link BatchFailureResult} which explicitly specifies the
 * index of the operation within the batch.</p>
 */
class BulkMutateResult {
  /**
   * @access public
   * @var integer
   */
  public $partIndex;

  /**
   * @access public
   * @var OperationStreamResult[]
   */
  public $operationStreamResults;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BulkMutateResult";
  }

  public function __construct($partIndex = NULL, $operationStreamResults = NULL) {
    if(get_parent_class('BulkMutateResult')) parent::__construct();
    $this->partIndex = $partIndex;
    $this->operationStreamResults = $operationStreamResults;
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

if (!class_exists("CampaignCriterion", FALSE)) {
/**
 * Represents a campaign level criterion (either keyword or placement).
 * 
 * <p>Can be directly used to refer to campaign criteria in remove operations.
 */
class CampaignCriterion {
  /**
   * @access public
   * @var integer
   */
  public $campaignId;

  /**
   * @access public
   * @var Criterion
   */
  public $criterion;

  /**
   * @access public
   * @var string
   */
  public $CampaignCriterionType;

  private $_parameterMap = array (
    "CampaignCriterion.Type" => "CampaignCriterionType",
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
    return "CampaignCriterion";
  }

  public function __construct($campaignId = NULL, $criterion = NULL, $CampaignCriterionType = NULL) {
    if(get_parent_class('CampaignCriterion')) parent::__construct();
    $this->campaignId = $campaignId;
    $this->criterion = $criterion;
    $this->CampaignCriterionType = $CampaignCriterionType;
  }
}}

if (!class_exists("CampaignCriterionError", FALSE)) {
/**
 * Base error class for Campaign Criterion Service.
 */
class CampaignCriterionError extends ApiError {
  /**
   * @access public
   * @var tnsCampaignCriterionErrorReason
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
    return "CampaignCriterionError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('CampaignCriterionError')) parent::__construct();
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

if (!class_exists("ConversionOptimizerAdGroupCriterionBids", FALSE)) {
/**
 * AdGroupCriterion level bids used in conversion optimizer bidding strategy.
 * This bidding strategy does not contain any bid information at the
 * AGC level.
 */
class ConversionOptimizerAdGroupCriterionBids extends AdGroupCriterionBids {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ConversionOptimizerAdGroupCriterionBids";
  }

  public function __construct($AdGroupCriterionBidsType = NULL) {
    if(get_parent_class('ConversionOptimizerAdGroupCriterionBids')) parent::__construct();
    $this->AdGroupCriterionBidsType = $AdGroupCriterionBidsType;
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

if (!class_exists("Criterion", FALSE)) {
/**
 * Represents any criterion (e.g. keyword, placement).
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

if (!class_exists("DeprecatedAd", FALSE)) {
/**
 * Represents a deprecated ad.
 * 
 * Deprecated ads can be deleted, but cannot be created.
 */
class DeprecatedAd extends Ad {
  /**
   * @access public
   * @var string
   */
  public $name;

  /**
   * @access public
   * @var tnsDeprecatedAdType
   */
  public $type;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "DeprecatedAd";
  }

  public function __construct($name = NULL, $type = NULL, $id = NULL, $url = NULL, $displayUrl = NULL, $approvalStatus = NULL, $disapprovalReasons = NULL, $AdType = NULL) {
    if(get_parent_class('DeprecatedAd')) parent::__construct();
    $this->name = $name;
    $this->type = $type;
    $this->id = $id;
    $this->url = $url;
    $this->displayUrl = $displayUrl;
    $this->approvalStatus = $approvalStatus;
    $this->disapprovalReasons = $disapprovalReasons;
    $this->AdType = $AdType;
  }
}}

if (!class_exists("Dimensions", FALSE)) {
/**
 * Represents a simple height-width dimension.
 */
class Dimensions {
  /**
   * @access public
   * @var integer
   */
  public $width;

  /**
   * @access public
   * @var integer
   */
  public $height;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Dimensions";
  }

  public function __construct($width = NULL, $height = NULL) {
    if(get_parent_class('Dimensions')) parent::__construct();
    $this->width = $width;
    $this->height = $height;
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

if (!class_exists("EntityAccessDenied", FALSE)) {
/**
 * Reports permission problems trying to access an entity.
 */
class EntityAccessDenied extends ApiError {
  /**
   * @access public
   * @var tnsEntityAccessDeniedReason
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
    return "EntityAccessDenied";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('EntityAccessDenied')) parent::__construct();
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

if (!class_exists("EntityId", FALSE)) {
/**
 * A qualified long-valued identity that can identify different types of
 * AdWords entities such as campaigns and ad-groups.
 */
class EntityId {
  /**
   * @access public
   * @var tnsEntityIdType
   */
  public $type;

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
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "EntityId";
  }

  public function __construct($type = NULL, $value = NULL) {
    if(get_parent_class('EntityId')) parent::__construct();
    $this->type = $type;
    $this->value = $value;
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

if (!class_exists("ExemptionRequest", FALSE)) {
/**
 * A request to be exempted from a {@link PolicyViolationError}.
 */
class ExemptionRequest {
  /**
   * @access public
   * @var PolicyViolationKey
   */
  public $key;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ExemptionRequest";
  }

  public function __construct($key = NULL) {
    if(get_parent_class('ExemptionRequest')) parent::__construct();
    $this->key = $key;
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

if (!class_exists("IdError", FALSE)) {
/**
 * Errors associated with the ids.
 */
class IdError extends ApiError {
  /**
   * @access public
   * @var tnsIdErrorReason
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
    return "IdError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('IdError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("ImageAd", FALSE)) {
/**
 * Represents an ImageAd.
 */
class ImageAd extends Ad {
  /**
   * @access public
   * @var Image
   */
  public $image;

  /**
   * @access public
   * @var string
   */
  public $name;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ImageAd";
  }

  public function __construct($image = NULL, $name = NULL, $id = NULL, $url = NULL, $displayUrl = NULL, $approvalStatus = NULL, $disapprovalReasons = NULL, $AdType = NULL) {
    if(get_parent_class('ImageAd')) parent::__construct();
    $this->image = $image;
    $this->name = $name;
    $this->id = $id;
    $this->url = $url;
    $this->displayUrl = $displayUrl;
    $this->approvalStatus = $approvalStatus;
    $this->disapprovalReasons = $disapprovalReasons;
    $this->AdType = $AdType;
  }
}}

if (!class_exists("ImageError", FALSE)) {
/**
 * Error class for errors associated with parsing image data.
 */
class ImageError extends ApiError {
  /**
   * @access public
   * @var tnsImageErrorReason
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
    return "ImageError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('ImageError')) parent::__construct();
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

if (!class_exists("Job", FALSE)) {
/**
 * Represents an asynchronous macro unit of work.
 */
class Job {
  /**
   * @access public
   * @var string
   */
  public $customerJobKey;

  /**
   * @access public
   * @var JobContext
   */
  public $context;

  /**
   * @access public
   * @var ApiErrorReason
   */
  public $failureReason;

  /**
   * @access public
   * @var JobStats
   */
  public $stats;

  /**
   * @access public
   * @var BillingSummary
   */
  public $billingSummary;

  /**
   * @access public
   * @var string
   */
  public $JobType;

  private $_parameterMap = array (
    "Job.Type" => "JobType",
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
    return "Job";
  }

  public function __construct($customerJobKey = NULL, $context = NULL, $failureReason = NULL, $stats = NULL, $billingSummary = NULL, $JobType = NULL) {
    if(get_parent_class('Job')) parent::__construct();
    $this->customerJobKey = $customerJobKey;
    $this->context = $context;
    $this->failureReason = $failureReason;
    $this->stats = $stats;
    $this->billingSummary = $billingSummary;
    $this->JobType = $JobType;
  }
}}

if (!class_exists("JobContext", FALSE)) {
/**
 * Represents the request and processing context information of a job.
 */
class JobContext {
  /**
   * @access public
   * @var string
   */
  public $authenticatedUserEmail;

  /**
   * @access public
   * @var integer
   */
  public $effectiveCustomerId;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "JobContext";
  }

  public function __construct($authenticatedUserEmail = NULL, $effectiveCustomerId = NULL) {
    if(get_parent_class('JobContext')) parent::__construct();
    $this->authenticatedUserEmail = $authenticatedUserEmail;
    $this->effectiveCustomerId = $effectiveCustomerId;
  }
}}

if (!class_exists("JobError", FALSE)) {
/**
 * Job handling errors.
 */
class JobError extends ApiError {
  /**
   * @access public
   * @var tnsJobErrorReason
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
    return "JobError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('JobError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("JobEvent", FALSE)) {
/**
 * An event defined by a status change of a job.
 */
class JobEvent {
  /**
   * @access public
   * @var string
   */
  public $dateTime;

  /**
   * @access public
   * @var string
   */
  public $JobEventType;

  private $_parameterMap = array (
    "JobEvent.Type" => "JobEventType",
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
    return "JobEvent";
  }

  public function __construct($dateTime = NULL, $JobEventType = NULL) {
    if(get_parent_class('JobEvent')) parent::__construct();
    $this->dateTime = $dateTime;
    $this->JobEventType = $JobEventType;
  }
}}

if (!class_exists("JobSelector", FALSE)) {
/**
 * For selecting jobs whose information will be returned from a get method call
 * of a job service.
 * 
 * <p>The selector is immutable so use the inner Builder class to create one.
 */
class JobSelector {
  /**
   * @access public
   * @var string[]
   */
  public $customerJobKeys;

  /**
   * @access public
   * @var boolean
   */
  public $includeHistory;

  /**
   * @access public
   * @var boolean
   */
  public $includeStats;

  /**
   * @access public
   * @var string
   */
  public $JobSelectorType;

  private $_parameterMap = array (
    "JobSelector.Type" => "JobSelectorType",
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
    return "JobSelector";
  }

  public function __construct($customerJobKeys = NULL, $includeHistory = NULL, $includeStats = NULL, $JobSelectorType = NULL) {
    if(get_parent_class('JobSelector')) parent::__construct();
    $this->customerJobKeys = $customerJobKeys;
    $this->includeHistory = $includeHistory;
    $this->includeStats = $includeStats;
    $this->JobSelectorType = $JobSelectorType;
  }
}}

if (!class_exists("JobStats", FALSE)) {
/**
 * Job-handling statistics.
 */
class JobStats {
  /**
   * @access public
   * @var integer
   */
  public $progressPercent;

  /**
   * @access public
   * @var integer
   */
  public $pendingTimeMillis;

  /**
   * @access public
   * @var integer
   */
  public $processingTimeMillis;

  /**
   * @access public
   * @var string
   */
  public $JobStatsType;

  private $_parameterMap = array (
    "JobStats.Type" => "JobStatsType",
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
    return "JobStats";
  }

  public function __construct($progressPercent = NULL, $pendingTimeMillis = NULL, $processingTimeMillis = NULL, $JobStatsType = NULL) {
    if(get_parent_class('JobStats')) parent::__construct();
    $this->progressPercent = $progressPercent;
    $this->pendingTimeMillis = $pendingTimeMillis;
    $this->processingTimeMillis = $processingTimeMillis;
    $this->JobStatsType = $JobStatsType;
  }
}}

if (!class_exists("Keyword", FALSE)) {
/**
 * A keyword.
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

if (!class_exists("LocalBusinessAd", FALSE)) {
/**
 * Represents Legacy Local Business Ad
 * 
 * Legacy LBAs can be deleted or paused/unpaused, but are not editable.
 */
class LocalBusinessAd extends Ad {
  /**
   * @access public
   * @var string
   */
  public $fullBusinessName;

  /**
   * @access public
   * @var string
   */
  public $phoneNumber;

  /**
   * @access public
   * @var string
   */
  public $streetAddress;

  /**
   * @access public
   * @var string
   */
  public $city;

  /**
   * @access public
   * @var string
   */
  public $region;

  /**
   * @access public
   * @var string
   */
  public $regionCode;

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
   * @access public
   * @var string
   */
  public $businessName;

  /**
   * @access public
   * @var string
   */
  public $description1;

  /**
   * @access public
   * @var string
   */
  public $description2;

  /**
   * @access public
   * @var ProximityTarget
   */
  public $target;

  /**
   * @access public
   * @var Image
   */
  public $businessImage;

  /**
   * @access public
   * @var Image
   */
  public $icon;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LocalBusinessAd";
  }

  public function __construct($fullBusinessName = NULL, $phoneNumber = NULL, $streetAddress = NULL, $city = NULL, $region = NULL, $regionCode = NULL, $postalCode = NULL, $countryCode = NULL, $businessName = NULL, $description1 = NULL, $description2 = NULL, $target = NULL, $businessImage = NULL, $icon = NULL, $id = NULL, $url = NULL, $displayUrl = NULL, $approvalStatus = NULL, $disapprovalReasons = NULL, $AdType = NULL) {
    if(get_parent_class('LocalBusinessAd')) parent::__construct();
    $this->fullBusinessName = $fullBusinessName;
    $this->phoneNumber = $phoneNumber;
    $this->streetAddress = $streetAddress;
    $this->city = $city;
    $this->region = $region;
    $this->regionCode = $regionCode;
    $this->postalCode = $postalCode;
    $this->countryCode = $countryCode;
    $this->businessName = $businessName;
    $this->description1 = $description1;
    $this->description2 = $description2;
    $this->target = $target;
    $this->businessImage = $businessImage;
    $this->icon = $icon;
    $this->id = $id;
    $this->url = $url;
    $this->displayUrl = $displayUrl;
    $this->approvalStatus = $approvalStatus;
    $this->disapprovalReasons = $disapprovalReasons;
    $this->AdType = $AdType;
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

if (!class_exists("ManualCPCAdGroupCriterionBids", FALSE)) {
/**
 * AdGroupCriterion level bids used in manual cpc bidding strategy.
 */
class ManualCPCAdGroupCriterionBids extends AdGroupCriterionBids {
  /**
   * @access public
   * @var Bid
   */
  public $maxCpc;

  /**
   * @access public
   * @var tnsBidSource
   */
  public $bidSource;

  /**
   * @access public
   * @var PositionPreferenceAdGroupCriterionBids
   */
  public $positionPreferenceBids;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ManualCPCAdGroupCriterionBids";
  }

  public function __construct($maxCpc = NULL, $bidSource = NULL, $positionPreferenceBids = NULL, $AdGroupCriterionBidsType = NULL) {
    if(get_parent_class('ManualCPCAdGroupCriterionBids')) parent::__construct();
    $this->maxCpc = $maxCpc;
    $this->bidSource = $bidSource;
    $this->positionPreferenceBids = $positionPreferenceBids;
    $this->AdGroupCriterionBidsType = $AdGroupCriterionBidsType;
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

if (!class_exists("ManualCPMAdGroupCriterionBids", FALSE)) {
/**
 * AdGroupCriterion level bids used in manual CPM bidding strategy.
 */
class ManualCPMAdGroupCriterionBids extends AdGroupCriterionBids {
  /**
   * @access public
   * @var Bid
   */
  public $maxCpm;

  /**
   * @access public
   * @var tnsBidSource
   */
  public $bidSource;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ManualCPMAdGroupCriterionBids";
  }

  public function __construct($maxCpm = NULL, $bidSource = NULL, $AdGroupCriterionBidsType = NULL) {
    if(get_parent_class('ManualCPMAdGroupCriterionBids')) parent::__construct();
    $this->maxCpm = $maxCpm;
    $this->bidSource = $bidSource;
    $this->AdGroupCriterionBidsType = $AdGroupCriterionBidsType;
  }
}}

if (!class_exists("Media", FALSE)) {
/**
 * Represents any media (e.g., image).
 */
class Media {
  /**
   * @access public
   * @var integer
   */
  public $mediaId;

  /**
   * @access public
   * @var tnsMediaMediaType
   */
  public $mediaTypeDb;

  /**
   * @access public
   * @var integer
   */
  public $referenceId;

  /**
   * @access public
   * @var Media_Size_DimensionsMapEntry[]
   */
  public $dimensions;

  /**
   * @access public
   * @var Media_Size_StringMapEntry[]
   */
  public $urls;

  /**
   * @access public
   * @var tnsMediaMimeType
   */
  public $mimeType;

  /**
   * @access public
   * @var string
   */
  public $sourceUrl;

  /**
   * @access public
   * @var tnsMediaMediaSubType
   */
  public $mediaSubType;

  /**
   * @access public
   * @var string
   */
  public $name;

  /**
   * @access public
   * @var integer
   */
  public $fileSize;

  /**
   * @access public
   * @var Media_MediaExtendedCapabilityType_Media_MediaExtendedCapabilityStateMapEntry[]
   */
  public $extendedCapabilities;

  /**
   * @access public
   * @var string
   */
  public $creationTime;

  /**
   * @access public
   * @var string
   */
  public $MediaType;

  private $_parameterMap = array (
    "Media.Type" => "MediaType",
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
    return "Media";
  }

  public function __construct($mediaId = NULL, $mediaTypeDb = NULL, $referenceId = NULL, $dimensions = NULL, $urls = NULL, $mimeType = NULL, $sourceUrl = NULL, $mediaSubType = NULL, $name = NULL, $fileSize = NULL, $extendedCapabilities = NULL, $creationTime = NULL, $MediaType = NULL) {
    if(get_parent_class('Media')) parent::__construct();
    $this->mediaId = $mediaId;
    $this->mediaTypeDb = $mediaTypeDb;
    $this->referenceId = $referenceId;
    $this->dimensions = $dimensions;
    $this->urls = $urls;
    $this->mimeType = $mimeType;
    $this->sourceUrl = $sourceUrl;
    $this->mediaSubType = $mediaSubType;
    $this->name = $name;
    $this->fileSize = $fileSize;
    $this->extendedCapabilities = $extendedCapabilities;
    $this->creationTime = $creationTime;
    $this->MediaType = $MediaType;
  }
}}

if (!class_exists("MediaError", FALSE)) {
/**
 * Error class for media related errors.
 */
class MediaError extends ApiError {
  /**
   * @access public
   * @var tnsMediaErrorReason
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
    return "MediaError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('MediaError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("Media_MediaExtendedCapabilityType_Media_MediaExtendedCapabilityStateMapEntry", FALSE)) {
/**
 * This represents an entry in a map with a key of type MediaExtendedCapabilityType
 * and value of type MediaExtendedCapabilityState.
 */
class Media_MediaExtendedCapabilityType_Media_MediaExtendedCapabilityStateMapEntry {
  /**
   * @access public
   * @var tnsMediaMediaExtendedCapabilityType
   */
  public $key;

  /**
   * @access public
   * @var tnsMediaMediaExtendedCapabilityState
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
    return "Media_MediaExtendedCapabilityType_Media_MediaExtendedCapabilityStateMapEntry";
  }

  public function __construct($key = NULL, $value = NULL) {
    if(get_parent_class('Media_MediaExtendedCapabilityType_Media_MediaExtendedCapabilityStateMapEntry')) parent::__construct();
    $this->key = $key;
    $this->value = $value;
  }
}}

if (!class_exists("Media_Size_DimensionsMapEntry", FALSE)) {
/**
 * This represents an entry in a map with a key of type Size
 * and value of type Dimensions.
 */
class Media_Size_DimensionsMapEntry {
  /**
   * @access public
   * @var tnsMediaSize
   */
  public $key;

  /**
   * @access public
   * @var Dimensions
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
    return "Media_Size_DimensionsMapEntry";
  }

  public function __construct($key = NULL, $value = NULL) {
    if(get_parent_class('Media_Size_DimensionsMapEntry')) parent::__construct();
    $this->key = $key;
    $this->value = $value;
  }
}}

if (!class_exists("Media_Size_StringMapEntry", FALSE)) {
/**
 * This represents an entry in a map with a key of type Size
 * and value of type String.
 */
class Media_Size_StringMapEntry {
  /**
   * @access public
   * @var tnsMediaSize
   */
  public $key;

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
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Media_Size_StringMapEntry";
  }

  public function __construct($key = NULL, $value = NULL) {
    if(get_parent_class('Media_Size_StringMapEntry')) parent::__construct();
    $this->key = $key;
    $this->value = $value;
  }
}}

if (!class_exists("MobileAd", FALSE)) {
/**
 * Represents a MobileAd.
 * 
 * A mobile ad can contain a click-to-call phone number, a link to a website,
 * or both.  You specify which features you want by setting certain fields, as
 * shown in the following table.  For example, to create a click-to-call mobile
 * ad, set the fields in the <b>Click-to-call</b> column.  A hyphen means don't
 * set the corresponding field.
 * </p>
 * <table summary="">
 * <tr> <th scope="col"> Click-to-call </th>
 * <th scope="col"> Website       </th>
 * <th scope="col"> Both          </th></tr>
 * <tr> <td> headline        <br />
 * description     <br />
 * mobileCarriers  <br />
 * phoneNumber     <br />
 * countryCode     <br />
 * businessName    <br />
 * -               <br />
 * -               <br />
 * -               <br />
 * </td>
 * <td> headline        <br />
 * description     <br />
 * mobileCarriers  <br />
 * -               <br />
 * -               <br />
 * -               <br />
 * displayUrl      <br />
 * destinationUrl  <br />
 * markupLanguages <br />
 * </td>
 * <td> headline        <br />
 * description     <br />
 * mobileCarriers  <br />
 * phoneNumber     <br />
 * countryCode     <br />
 * businessName    <br />
 * displayUrl      <br />
 * destinationUrl  <br />
 * markupLanguages <br />
 * </td></tr>
 * </table>
 * 
 * <h2 class="normalsize">More Information</h2>
 * <ul class="nolist">
 * <li>
 * <a href="https://adwords.google.com/select/mobileguidelines.html"
 * target="_blank">Mobile Ad Editorial Guidelines</a></li>
 * <li>
 * <a href="http://adwords.google.com/support/bin/topic.py?topic=8500"
 * target="_blank">Mobile Ad FAQ</a></li>
 * </ul>
 */
class MobileAd extends Ad {
  /**
   * @access public
   * @var string
   */
  public $headline;

  /**
   * @access public
   * @var string
   */
  public $description;

  /**
   * @access public
   * @var tnsMarkupLanguageType[]
   */
  public $markupLanguages;

  /**
   * @access public
   * @var string[]
   */
  public $mobileCarriers;

  /**
   * @access public
   * @var string
   */
  public $businessName;

  /**
   * @access public
   * @var string
   */
  public $countryCode;

  /**
   * @access public
   * @var string
   */
  public $phoneNumber;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "MobileAd";
  }

  public function __construct($headline = NULL, $description = NULL, $markupLanguages = NULL, $mobileCarriers = NULL, $businessName = NULL, $countryCode = NULL, $phoneNumber = NULL, $id = NULL, $url = NULL, $displayUrl = NULL, $approvalStatus = NULL, $disapprovalReasons = NULL, $AdType = NULL) {
    if(get_parent_class('MobileAd')) parent::__construct();
    $this->headline = $headline;
    $this->description = $description;
    $this->markupLanguages = $markupLanguages;
    $this->mobileCarriers = $mobileCarriers;
    $this->businessName = $businessName;
    $this->countryCode = $countryCode;
    $this->phoneNumber = $phoneNumber;
    $this->id = $id;
    $this->url = $url;
    $this->displayUrl = $displayUrl;
    $this->approvalStatus = $approvalStatus;
    $this->disapprovalReasons = $disapprovalReasons;
    $this->AdType = $AdType;
  }
}}

if (!class_exists("MobileImageAd", FALSE)) {
/**
 * Data associated with a mobile image ad.
 * 
 * <h2 class="normalsize">More Information</h2>
 * <ul class="nolist">
 * <li>
 * <a href="https://adwords.google.com/select/mobileguidelines.html"
 * target="_blank">Mobile Ad Editorial Guidelines</a></li>
 * <li>
 * <a href="http://adwords.google.com/support/bin/topic.py?topic=8500"
 * target="_blank">Mobile Ad FAQ</a></li>
 * </ul>
 */
class MobileImageAd extends Ad {
  /**
   * @access public
   * @var tnsMarkupLanguageType[]
   */
  public $markupLanguages;

  /**
   * @access public
   * @var string[]
   */
  public $mobileCarriers;

  /**
   * @access public
   * @var Image
   */
  public $image;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "MobileImageAd";
  }

  public function __construct($markupLanguages = NULL, $mobileCarriers = NULL, $image = NULL, $id = NULL, $url = NULL, $displayUrl = NULL, $approvalStatus = NULL, $disapprovalReasons = NULL, $AdType = NULL) {
    if(get_parent_class('MobileImageAd')) parent::__construct();
    $this->markupLanguages = $markupLanguages;
    $this->mobileCarriers = $mobileCarriers;
    $this->image = $image;
    $this->id = $id;
    $this->url = $url;
    $this->displayUrl = $displayUrl;
    $this->approvalStatus = $approvalStatus;
    $this->disapprovalReasons = $disapprovalReasons;
    $this->AdType = $AdType;
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

if (!class_exists("NegativeAdGroupCriterion", FALSE)) {
/**
 * A negative criterion in an adgroup.
 */
class NegativeAdGroupCriterion extends AdGroupCriterion {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NegativeAdGroupCriterion";
  }

  public function __construct($adGroupId = NULL, $criterion = NULL, $AdGroupCriterionType = NULL) {
    if(get_parent_class('NegativeAdGroupCriterion')) parent::__construct();
    $this->adGroupId = $adGroupId;
    $this->criterion = $criterion;
    $this->AdGroupCriterionType = $AdGroupCriterionType;
  }
}}

if (!class_exists("NegativeCampaignCriterion", FALSE)) {
/**
 * A negative campaign criterion.
 */
class NegativeCampaignCriterion extends CampaignCriterion {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NegativeCampaignCriterion";
  }

  public function __construct($campaignId = NULL, $criterion = NULL, $CampaignCriterionType = NULL) {
    if(get_parent_class('NegativeCampaignCriterion')) parent::__construct();
    $this->campaignId = $campaignId;
    $this->criterion = $criterion;
    $this->CampaignCriterionType = $CampaignCriterionType;
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

if (!class_exists("Operand", FALSE)) {
/**
 * A marker interface for entities that can be operated upon in mutate
 * operations.
 */
class Operand {
  /**
   * @access public
   * @var AdExtensionOverride
   */
  public $AdExtensionOverride;

  /**
   * @access public
   * @var AdGroupAd
   */
  public $AdGroupAd;

  /**
   * @access public
   * @var AdGroupCriterion
   */
  public $AdGroupCriterion;

  /**
   * @access public
   * @var AdGroup
   */
  public $AdGroup;

  /**
   * @access public
   * @var Ad
   */
  public $Ad;

  /**
   * @access public
   * @var CampaignCriterion
   */
  public $CampaignCriterion;

  /**
   * @access public
   * @var Campaign
   */
  public $Campaign;

  /**
   * @access public
   * @var Job
   */
  public $Job;

  /**
   * @access public
   * @var Media
   */
  public $Media;

  /**
   * @access public
   * @var TargetList
   */
  public $TargetList;

  /**
   * @access public
   * @var Target
   */
  public $Target;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Operand";
  }

  public function __construct($AdExtensionOverride = NULL, $AdGroupAd = NULL, $AdGroupCriterion = NULL, $AdGroup = NULL, $Ad = NULL, $CampaignCriterion = NULL, $Campaign = NULL, $Job = NULL, $Media = NULL, $TargetList = NULL, $Target = NULL) {
    if(get_parent_class('Operand')) parent::__construct();
    $this->AdExtensionOverride = $AdExtensionOverride;
    $this->AdGroupAd = $AdGroupAd;
    $this->AdGroupCriterion = $AdGroupCriterion;
    $this->AdGroup = $AdGroup;
    $this->Ad = $Ad;
    $this->CampaignCriterion = $CampaignCriterion;
    $this->Campaign = $Campaign;
    $this->Job = $Job;
    $this->Media = $Media;
    $this->TargetList = $TargetList;
    $this->Target = $Target;
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

if (!class_exists("OperationResult", FALSE)) {
/**
 * Represents the result of an individual mutate operation.
 */
class OperationResult {
  /**
   * @access public
   * @var string
   */
  public $OperationResultType;

  private $_parameterMap = array (
    "OperationResult.Type" => "OperationResultType",
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
    return "OperationResult";
  }

  public function __construct($OperationResultType = NULL) {
    if(get_parent_class('OperationResult')) parent::__construct();
    $this->OperationResultType = $OperationResultType;
  }
}}

if (!class_exists("OperationStream", FALSE)) {
/**
 * A stream of AdWords API mutate operations that must be performed serially.
 * Further, this operation stream must be processed serially with all other
 * operation streams in a bulk mutate job that specify the same scoping
 * entity.
 */
class OperationStream {
  /**
   * @access public
   * @var EntityId
   */
  public $scopingEntityId;

  /**
   * @access public
   * @var Operation[]
   */
  public $operations;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "OperationStream";
  }

  public function __construct($scopingEntityId = NULL, $operations = NULL) {
    if(get_parent_class('OperationStream')) parent::__construct();
    $this->scopingEntityId = $scopingEntityId;
    $this->operations = $operations;
  }
}}

if (!class_exists("OperationStreamResult", FALSE)) {
/**
 * The result of processing an {@link OperationStream}.
 */
class OperationStreamResult {
  /**
   * @access public
   * @var OperationResult[]
   */
  public $operationResults;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "OperationStreamResult";
  }

  public function __construct($operationResults = NULL) {
    if(get_parent_class('OperationStreamResult')) parent::__construct();
    $this->operationResults = $operationResults;
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

if (!class_exists("PagingError", FALSE)) {
/**
 * Error codes for pagination.
 * See {@link com.google.ads.api.services.common.pagination.Paging}.
 */
class PagingError extends ApiError {
  /**
   * @access public
   * @var tnsPagingErrorReason
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
    return "PagingError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('PagingError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("Placement", FALSE)) {
/**
 * A placement used for modifying bids for sites when targeting the content
 * network.
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

if (!class_exists("PolicyViolationErrorPart", FALSE)) {
/**
 * Points to a substring within an ad field or criterion.
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

if (!class_exists("PositionPreferenceAdGroupCriterionBids", FALSE)) {
/**
 * AdGroupCriterion level bids used in manual cpc bidding strategy
 * when position preference is turned on.
 */
class PositionPreferenceAdGroupCriterionBids {
  /**
   * @access public
   * @var Bid
   */
  public $proxyMaxCpc;

  /**
   * @access public
   * @var integer
   */
  public $preferredPosition;

  /**
   * @access public
   * @var integer
   */
  public $bottomPosition;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "PositionPreferenceAdGroupCriterionBids";
  }

  public function __construct($proxyMaxCpc = NULL, $preferredPosition = NULL, $bottomPosition = NULL) {
    if(get_parent_class('PositionPreferenceAdGroupCriterionBids')) parent::__construct();
    $this->proxyMaxCpc = $proxyMaxCpc;
    $this->preferredPosition = $preferredPosition;
    $this->bottomPosition = $bottomPosition;
  }
}}

if (!class_exists("QualityInfo", FALSE)) {
/**
 * Container for criterion quality information.
 */
class QualityInfo {
  /**
   * @access public
   * @var boolean
   */
  public $isKeywordAdRelevanceAcceptable;

  /**
   * @access public
   * @var boolean
   */
  public $isLandingPageQualityAcceptable;

  /**
   * @access public
   * @var boolean
   */
  public $isLandingPageLatencyAcceptable;

  /**
   * @access public
   * @var integer
   */
  public $qualityScore;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "QualityInfo";
  }

  public function __construct($isKeywordAdRelevanceAcceptable = NULL, $isLandingPageQualityAcceptable = NULL, $isLandingPageLatencyAcceptable = NULL, $qualityScore = NULL) {
    if(get_parent_class('QualityInfo')) parent::__construct();
    $this->isKeywordAdRelevanceAcceptable = $isKeywordAdRelevanceAcceptable;
    $this->isLandingPageQualityAcceptable = $isLandingPageQualityAcceptable;
    $this->isLandingPageLatencyAcceptable = $isLandingPageLatencyAcceptable;
    $this->qualityScore = $qualityScore;
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

if (!class_exists("RegionCodeError", FALSE)) {
/**
 * A list of all errors associated with the @RegionCode constraints.
 */
class RegionCodeError extends ApiError {
  /**
   * @access public
   * @var tnsRegionCodeErrorReason
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
    return "RegionCodeError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('RegionCodeError')) parent::__construct();
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

if (!class_exists("ReturnValueResult", FALSE)) {
/**
 * Represents a success result of processing a mutate operation that returned
 * a value.
 */
class ReturnValueResult extends OperationResult {
  /**
   * @access public
   * @var Operand
   */
  public $returnValue;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ReturnValueResult";
  }

  public function __construct($returnValue = NULL, $OperationResultType = NULL) {
    if(get_parent_class('ReturnValueResult')) parent::__construct();
    $this->returnValue = $returnValue;
    $this->OperationResultType = $OperationResultType;
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

if (!class_exists("AdWordsTarget", FALSE)) {
/**
 * Target abstract class.
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

if (!class_exists("TargetError", FALSE)) {
/**
 * A list of all the error codes being used by the common targeting package.
 */
class TargetError extends ApiError {
  /**
   * @access public
   * @var tnsTargetErrorReason
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
    return "TargetError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('TargetError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("TargetList", FALSE)) {
/**
 * Targets list abstract class (a list of a given type of targets along with their campaign ID).
 */
class TargetList {
  /**
   * @access public
   * @var integer
   */
  public $campaignId;

  /**
   * @access public
   * @var string
   */
  public $TargetListType;

  private $_parameterMap = array (
    "TargetList.Type" => "TargetListType",
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
    return "TargetList";
  }

  public function __construct($campaignId = NULL, $TargetListType = NULL) {
    if(get_parent_class('TargetList')) parent::__construct();
    $this->campaignId = $campaignId;
    $this->TargetListType = $TargetListType;
  }
}}

if (!class_exists("TempAdUnionId", FALSE)) {
/**
 * Represents the temporary id for an ad union id, which the user can specify.
 * The temporary id can be used to group ads together during ad creation.
 */
class TempAdUnionId extends AdUnionId {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TempAdUnionId";
  }

  public function __construct($id = NULL, $AdUnionIdType = NULL) {
    if(get_parent_class('TempAdUnionId')) parent::__construct();
    $this->id = $id;
    $this->AdUnionIdType = $AdUnionIdType;
  }
}}

if (!class_exists("TemplateAd", FALSE)) {
/**
 * Represents a TemplateAd. A template ad is composed of a template (specified
 * by it's template id) along with all the elements to populate the
 * template's fields.
 */
class TemplateAd extends Ad {
  /**
   * @access public
   * @var integer
   */
  public $templateId;

  /**
   * @access public
   * @var AdUnionId
   */
  public $adUnionId;

  /**
   * @access public
   * @var TemplateElement[]
   */
  public $templateElements;

  /**
   * @access public
   * @var Dimensions
   */
  public $dimensions;

  /**
   * @access public
   * @var string
   */
  public $name;

  /**
   * @access public
   * @var integer
   */
  public $duration;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TemplateAd";
  }

  public function __construct($templateId = NULL, $adUnionId = NULL, $templateElements = NULL, $dimensions = NULL, $name = NULL, $duration = NULL, $id = NULL, $url = NULL, $displayUrl = NULL, $approvalStatus = NULL, $disapprovalReasons = NULL, $AdType = NULL) {
    if(get_parent_class('TemplateAd')) parent::__construct();
    $this->templateId = $templateId;
    $this->adUnionId = $adUnionId;
    $this->templateElements = $templateElements;
    $this->dimensions = $dimensions;
    $this->name = $name;
    $this->duration = $duration;
    $this->id = $id;
    $this->url = $url;
    $this->displayUrl = $displayUrl;
    $this->approvalStatus = $approvalStatus;
    $this->disapprovalReasons = $disapprovalReasons;
    $this->AdType = $AdType;
  }
}}

if (!class_exists("TemplateElement", FALSE)) {
/**
 * Represents an element in a template. Each template element is composed
 * of a list of fields (actual value data).
 */
class TemplateElement {
  /**
   * @access public
   * @var string
   */
  public $uniqueName;

  /**
   * @access public
   * @var TemplateElementField[]
   */
  public $fields;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TemplateElement";
  }

  public function __construct($uniqueName = NULL, $fields = NULL) {
    if(get_parent_class('TemplateElement')) parent::__construct();
    $this->uniqueName = $uniqueName;
    $this->fields = $fields;
  }
}}

if (!class_exists("TemplateElementField", FALSE)) {
/**
 * Represents a field in a template element.
 */
class TemplateElementField {
  /**
   * @access public
   * @var string
   */
  public $name;

  /**
   * @access public
   * @var tnsTemplateElementFieldType
   */
  public $type;

  /**
   * @access public
   * @var string
   */
  public $fieldText;

  /**
   * @access public
   * @var Media
   */
  public $fieldMedia;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TemplateElementField";
  }

  public function __construct($name = NULL, $type = NULL, $fieldText = NULL, $fieldMedia = NULL) {
    if(get_parent_class('TemplateElementField')) parent::__construct();
    $this->name = $name;
    $this->type = $type;
    $this->fieldText = $fieldText;
    $this->fieldMedia = $fieldMedia;
  }
}}

if (!class_exists("TextAd", FALSE)) {
/**
 * Represents a TextAd.
 */
class TextAd extends Ad {
  /**
   * @access public
   * @var string
   */
  public $headline;

  /**
   * @access public
   * @var string
   */
  public $description1;

  /**
   * @access public
   * @var string
   */
  public $description2;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TextAd";
  }

  public function __construct($headline = NULL, $description1 = NULL, $description2 = NULL, $id = NULL, $url = NULL, $displayUrl = NULL, $approvalStatus = NULL, $disapprovalReasons = NULL, $AdType = NULL) {
    if(get_parent_class('TextAd')) parent::__construct();
    $this->headline = $headline;
    $this->description1 = $description1;
    $this->description2 = $description2;
    $this->id = $id;
    $this->url = $url;
    $this->displayUrl = $displayUrl;
    $this->approvalStatus = $approvalStatus;
    $this->disapprovalReasons = $disapprovalReasons;
    $this->AdType = $AdType;
  }
}}

if (!class_exists("UnprocessedResult", FALSE)) {
/**
 * Indicates that the mutate operation was not processed. This could result
 * either because the operation has not yet been scheduled for processing,
 * or because the workflow implementation prevented the operation from
 * being processed. See the job status to see which of these is relevant.
 */
class UnprocessedResult extends OperationResult {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "UnprocessedResult";
  }

  public function __construct($OperationResultType = NULL) {
    if(get_parent_class('UnprocessedResult')) parent::__construct();
    $this->OperationResultType = $OperationResultType;
  }
}}

if (!class_exists("Video", FALSE)) {
/**
 * Encapsulates a Video media identified by a MediaId
 */
class Video extends Media {
  /**
   * @access public
   * @var integer
   */
  public $durationMillis;

  /**
   * @access public
   * @var string
   */
  public $streamingUrl;

  /**
   * @access public
   * @var boolean
   */
  public $readyToPlayOnTheWeb;

  /**
   * @access public
   * @var string
   */
  public $industryStandardCommercialIdentifier;

  /**
   * @access public
   * @var string
   */
  public $advertisingId;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Video";
  }

  public function __construct($durationMillis = NULL, $streamingUrl = NULL, $readyToPlayOnTheWeb = NULL, $industryStandardCommercialIdentifier = NULL, $advertisingId = NULL, $mediaId = NULL, $mediaTypeDb = NULL, $referenceId = NULL, $dimensions = NULL, $urls = NULL, $mimeType = NULL, $sourceUrl = NULL, $mediaSubType = NULL, $name = NULL, $fileSize = NULL, $extendedCapabilities = NULL, $creationTime = NULL, $MediaType = NULL) {
    if(get_parent_class('Video')) parent::__construct();
    $this->durationMillis = $durationMillis;
    $this->streamingUrl = $streamingUrl;
    $this->readyToPlayOnTheWeb = $readyToPlayOnTheWeb;
    $this->industryStandardCommercialIdentifier = $industryStandardCommercialIdentifier;
    $this->advertisingId = $advertisingId;
    $this->mediaId = $mediaId;
    $this->mediaTypeDb = $mediaTypeDb;
    $this->referenceId = $referenceId;
    $this->dimensions = $dimensions;
    $this->urls = $urls;
    $this->mimeType = $mimeType;
    $this->sourceUrl = $sourceUrl;
    $this->mediaSubType = $mediaSubType;
    $this->name = $name;
    $this->fileSize = $fileSize;
    $this->extendedCapabilities = $extendedCapabilities;
    $this->creationTime = $creationTime;
    $this->MediaType = $MediaType;
  }
}}

if (!class_exists("AdApprovalStatus", FALSE)) {
/**
 * Approval status for Creatives.
 */
class AdApprovalStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Ad.ApprovalStatus";
  }

  public function __construct() {
    if(get_parent_class('AdApprovalStatus')) parent::__construct();
  }
}}

if (!class_exists("AdErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class AdErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdError.Reason";
  }

  public function __construct() {
    if(get_parent_class('AdErrorReason')) parent::__construct();
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

if (!class_exists("AdGroupStatus", FALSE)) {
/**
 * The status of the AdGroup.
 */
class AdGroupStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdGroup.Status";
  }

  public function __construct() {
    if(get_parent_class('AdGroupStatus')) parent::__construct();
  }
}}

if (!class_exists("AdGroupAdStatus", FALSE)) {
/**
 * The current status of an Ad.
 */
class AdGroupAdStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdGroupAd.Status";
  }

  public function __construct() {
    if(get_parent_class('AdGroupAdStatus')) parent::__construct();
  }
}}

if (!class_exists("AdGroupAdErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class AdGroupAdErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdGroupAdError.Reason";
  }

  public function __construct() {
    if(get_parent_class('AdGroupAdErrorReason')) parent::__construct();
  }
}}

if (!class_exists("AdGroupCriterionErrorReason", FALSE)) {
/**
 * The reasons for the target error.
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

if (!class_exists("AdGroupServiceErrorReason", FALSE)) {
/**
 * The reasons for the adgroup service error.
 */
class AdGroupServiceErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdGroupServiceError.Reason";
  }

  public function __construct() {
    if(get_parent_class('AdGroupServiceErrorReason')) parent::__construct();
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

if (!class_exists("AgeTargetAge", FALSE)) {
/**
 * Age segments.
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

if (!class_exists("ApprovalStatus", FALSE)) {
/**
 * Approval status for the criterion.
 * Note: there are more states involved but we only expose two to users.
 */
class ApprovalStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ApprovalStatus";
  }

  public function __construct() {
    if(get_parent_class('ApprovalStatus')) parent::__construct();
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

if (!class_exists("BasicJobStatus", FALSE)) {
/**
 * A basic set of job statuses.
 */
class BasicJobStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BasicJobStatus";
  }

  public function __construct() {
    if(get_parent_class('BasicJobStatus')) parent::__construct();
  }
}}

if (!class_exists("BidSource", FALSE)) {
/**
 * Indicate where a criterion's bid came from: criterion or the adgroup it
 * belongs to.
 */
class BidSource {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BidSource";
  }

  public function __construct() {
    if(get_parent_class('BidSource')) parent::__construct();
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

if (!class_exists("BulkMutateJobErrorReason", FALSE)) {
/**
 * The reasons for the bulk mutate job handling error.
 */
class BulkMutateJobErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BulkMutateJobError.Reason";
  }

  public function __construct() {
    if(get_parent_class('BulkMutateJobErrorReason')) parent::__construct();
  }
}}

if (!class_exists("CampaignCriterionErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class CampaignCriterionErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "CampaignCriterionError.Reason";
  }

  public function __construct() {
    if(get_parent_class('CampaignCriterionErrorReason')) parent::__construct();
  }
}}

if (!class_exists("CampaignCriterionLimitExceededCriteriaLimitType", FALSE)) {
/**
 * 
 */
class CampaignCriterionLimitExceededCriteriaLimitType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "CampaignCriterionLimitExceeded.CriteriaLimitType";
  }

  public function __construct() {
    if(get_parent_class('CampaignCriterionLimitExceededCriteriaLimitType')) parent::__construct();
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

if (!class_exists("ContentLabelType", FALSE)) {
/**
 * Content label type.
 */
class ContentLabelType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ContentLabelType";
  }

  public function __construct() {
    if(get_parent_class('ContentLabelType')) parent::__construct();
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

if (!class_exists("DayOfWeek", FALSE)) {
/**
 * Days of the week.
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

if (!class_exists("DeprecatedAdType", FALSE)) {
/**
 * 
 */
class DeprecatedAdType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "DeprecatedAd.Type";
  }

  public function __construct() {
    if(get_parent_class('DeprecatedAdType')) parent::__construct();
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

if (!class_exists("EntityAccessDeniedReason", FALSE)) {
/**
 * User did not have read access.
 */
class EntityAccessDeniedReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "EntityAccessDenied.Reason";
  }

  public function __construct() {
    if(get_parent_class('EntityAccessDeniedReason')) parent::__construct();
  }
}}

if (!class_exists("EntityCountLimitExceededReason", FALSE)) {
/**
 * Limits at various levels of the account.
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

if (!class_exists("EntityIdType", FALSE)) {
/**
 * Type of entity identity.
 */
class EntityIdType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "EntityId.Type";
  }

  public function __construct() {
    if(get_parent_class('EntityIdType')) parent::__construct();
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

if (!class_exists("GenderTargetGender", FALSE)) {
/**
 * Gender segments.
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

if (!class_exists("IdErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class IdErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "IdError.Reason";
  }

  public function __construct() {
    if(get_parent_class('IdErrorReason')) parent::__construct();
  }
}}

if (!class_exists("ImageErrorReason", FALSE)) {
/**
 * 
 */
class ImageErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ImageError.Reason";
  }

  public function __construct() {
    if(get_parent_class('ImageErrorReason')) parent::__construct();
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

if (!class_exists("JobErrorReason", FALSE)) {
/**
 * The reasons for the job handling error.
 */
class JobErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "JobError.Reason";
  }

  public function __construct() {
    if(get_parent_class('JobErrorReason')) parent::__construct();
  }
}}

if (!class_exists("KeywordMatchType", FALSE)) {
/**
 * Match type of a keyword. i.e. the way we match a keyword string with
 * search queries.
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

if (!class_exists("LoasAuthenticationErrorReason", FALSE)) {
/**
 * The reasons for the loas authentication error.
 */
class LoasAuthenticationErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LoasAuthenticationError.Reason";
  }

  public function __construct() {
    if(get_parent_class('LoasAuthenticationErrorReason')) parent::__construct();
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

if (!class_exists("MarkupLanguageType", FALSE)) {
/**
 * Markup languages to use for mobile ads.
 */
class MarkupLanguageType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "MarkupLanguageType";
  }

  public function __construct() {
    if(get_parent_class('MarkupLanguageType')) parent::__construct();
  }
}}

if (!class_exists("MediaMediaExtendedCapabilityState", FALSE)) {
/**
 * 
 */
class MediaMediaExtendedCapabilityState {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Media.MediaExtendedCapabilityState";
  }

  public function __construct() {
    if(get_parent_class('MediaMediaExtendedCapabilityState')) parent::__construct();
  }
}}

if (!class_exists("MediaMediaExtendedCapabilityType", FALSE)) {
/**
 * 
 */
class MediaMediaExtendedCapabilityType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Media.MediaExtendedCapabilityType";
  }

  public function __construct() {
    if(get_parent_class('MediaMediaExtendedCapabilityType')) parent::__construct();
  }
}}

if (!class_exists("MediaMediaSubType", FALSE)) {
/**
 * 
 */
class MediaMediaSubType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Media.MediaSubType";
  }

  public function __construct() {
    if(get_parent_class('MediaMediaSubType')) parent::__construct();
  }
}}

if (!class_exists("MediaMediaType", FALSE)) {
/**
 * Media types
 */
class MediaMediaType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Media.MediaType";
  }

  public function __construct() {
    if(get_parent_class('MediaMediaType')) parent::__construct();
  }
}}

if (!class_exists("MediaMimeType", FALSE)) {
/**
 * Mime types
 */
class MediaMimeType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Media.MimeType";
  }

  public function __construct() {
    if(get_parent_class('MediaMimeType')) parent::__construct();
  }
}}

if (!class_exists("MediaSize", FALSE)) {
/**
 * Sizes for retrieving the original media
 */
class MediaSize {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Media.Size";
  }

  public function __construct() {
    if(get_parent_class('MediaSize')) parent::__construct();
  }
}}

if (!class_exists("MediaErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class MediaErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "MediaError.Reason";
  }

  public function __construct() {
    if(get_parent_class('MediaErrorReason')) parent::__construct();
  }
}}

if (!class_exists("MinuteOfHour", FALSE)) {
/**
 * Minutes in an hour.  Currently only 0, 15, 30, and 45 are supported
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

if (!class_exists("PagingErrorReason", FALSE)) {
/**
 * The reasons for errors when using pagination.
 */
class PagingErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "PagingError.Reason";
  }

  public function __construct() {
    if(get_parent_class('PagingErrorReason')) parent::__construct();
  }
}}

if (!class_exists("PlatformType", FALSE)) {
/**
 * Platform types.
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

if (!class_exists("PolicyViolationErrorReason", FALSE)) {
/**
 * 
 */
class PolicyViolationErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "PolicyViolationError.Reason";
  }

  public function __construct() {
    if(get_parent_class('PolicyViolationErrorReason')) parent::__construct();
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

if (!class_exists("ProximityTargetDistanceUnits", FALSE)) {
/**
 * The radius distance is expressed in either kilometers or miles.
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

if (!class_exists("RegionCodeErrorReason", FALSE)) {
/**
 * The reasons for the validation error.
 */
class RegionCodeErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "RegionCodeError.Reason";
  }

  public function __construct() {
    if(get_parent_class('RegionCodeErrorReason')) parent::__construct();
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

if (!class_exists("SystemServingStatus", FALSE)) {
/**
 * Reported by system to reflect the criterion's serving status.
 */
class SystemServingStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "SystemServingStatus";
  }

  public function __construct() {
    if(get_parent_class('SystemServingStatus')) parent::__construct();
  }
}}

if (!class_exists("TargetErrorReason", FALSE)) {
/**
 * The reasons for the target error.
 */
class TargetErrorReason {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TargetError.Reason";
  }

  public function __construct() {
    if(get_parent_class('TargetErrorReason')) parent::__construct();
  }
}}

if (!class_exists("TemplateElementFieldType", FALSE)) {
/**
 * Field types
 */
class TemplateElementFieldType {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "TemplateElementField.Type";
  }

  public function __construct() {
    if(get_parent_class('TemplateElementFieldType')) parent::__construct();
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

if (!class_exists("UserStatus", FALSE)) {
/**
 * Specified by user to pause or unpause a criterion.
 */
class UserStatus {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "UserStatus";
  }

  public function __construct() {
    if(get_parent_class('UserStatus')) parent::__construct();
  }
}}

if (!class_exists("BulkMutateJobServiceGet", FALSE)) {
/**
 * <span class="constraint Required">This field is required and should not be {@code null}.</span>
 * 
 * 
 * 
 * Returns a list of bulk mutate jobs.
 * 
 * @param selector Specifies which jobs to return.
 * If the selector is empty, all jobs are returned.
 * @return List of bulk mutate jobs meeting the {@code selector} criteria.
 * @throws ApiException if problems occurred while fetching the jobs
 */
class BulkMutateJobServiceGet {
  /**
   * @access public
   * @var BulkMutateJobSelector
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
    if(get_parent_class('BulkMutateJobServiceGet')) parent::__construct();
    $this->selector = $selector;
  }
}}

if (!class_exists("BulkMutateJobServiceGetResponse", FALSE)) {
/**
 * 
 */
class BulkMutateJobServiceGetResponse {
  /**
   * @access public
   * @var BulkMutateJob[]
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
    if(get_parent_class('BulkMutateJobServiceGetResponse')) parent::__construct();
    $this->rval = $rval;
  }
}}

if (!class_exists("BulkMutateJobServiceMutate", FALSE)) {
/**
 * <span class="constraint Required">This field is required and should not be {@code null}.</span>
 * 
 * 
 * 
 * Adds or updates a bulk mutate job.
 * 
 * <p>Use the &laquo;ADD&raquo; operator to submit a new job, and the
 * &laquo;SET&raquo; operator to add additional
 * {@linkplain BulkMutateRequest request parts} to an existing job.
 * The &laquo;DELETE&raquo; operator is not supported.</p>
 * 
 * <p class="note"><b>Note:</b> In the current implementation, the check for
 * duplicate job keys is only "best effort", and may not prevent jobs with
 * the same keys from being accepted if they are submitted around the same
 * instant.</p>
 * 
 * @param operation The operation to perform.
 * @throws ApiException if problems occurred while creating or updating jobs
 * @return The added or updated bulk mutate job.
 */
class BulkMutateJobServiceMutate {
  /**
   * @access public
   * @var JobOperation
   */
  public $operation;

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

  public function __construct($operation = NULL) {
    if(get_parent_class('BulkMutateJobServiceMutate')) parent::__construct();
    $this->operation = $operation;
  }
}}

if (!class_exists("BulkMutateJobServiceMutateResponse", FALSE)) {
/**
 * 
 */
class BulkMutateJobServiceMutateResponse {
  /**
   * @access public
   * @var BulkMutateJob
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
    if(get_parent_class('BulkMutateJobServiceMutateResponse')) parent::__construct();
    $this->rval = $rval;
  }
}}

if (!class_exists("AdError", FALSE)) {
/**
 * Base error class for Ad Service.
 */
class AdError extends ApiError {
  /**
   * @access public
   * @var tnsAdErrorReason
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
    return "AdError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AdError')) parent::__construct();
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

if (!class_exists("AdGroupAdCountLimitExceeded", FALSE)) {
/**
 * Indicates too many ads were added/enabled under the specified adgroup.
 */
class AdGroupAdCountLimitExceeded extends EntityCountLimitExceeded {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdGroupAdCountLimitExceeded";
  }

  public function __construct($reason = NULL, $enclosingId = NULL, $limit = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AdGroupAdCountLimitExceeded')) parent::__construct();
    $this->reason = $reason;
    $this->enclosingId = $enclosingId;
    $this->limit = $limit;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("AdGroupAdError", FALSE)) {
/**
 * Error information for AdGroupAdService.
 */
class AdGroupAdError extends ApiError {
  /**
   * @access public
   * @var tnsAdGroupAdErrorReason
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
    return "AdGroupAdError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AdGroupAdError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("AdGroupAdOperation", FALSE)) {
/**
 * AdGroupAd service operations.
 */
class AdGroupAdOperation extends Operation {
  /**
   * @access public
   * @var AdGroupAd
   */
  public $operand;

  /**
   * @access public
   * @var ExemptionRequest[]
   */
  public $exemptionRequests;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdGroupAdOperation";
  }

  public function __construct($operand = NULL, $exemptionRequests = NULL, $operator = NULL, $OperationType = NULL) {
    if(get_parent_class('AdGroupAdOperation')) parent::__construct();
    $this->operand = $operand;
    $this->exemptionRequests = $exemptionRequests;
    $this->operator = $operator;
    $this->OperationType = $OperationType;
  }
}}

if (!class_exists("AdGroupCriterionError", FALSE)) {
/**
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

if (!class_exists("AdGroupCriterionLimitExceeded", FALSE)) {
/**
 * Signals that too many criteria were added to some ad group.
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

if (!class_exists("AdGroupCriterionOperation", FALSE)) {
/**
 * Operation (add, remove and set) on adgroup criteria.
 */
class AdGroupCriterionOperation extends Operation {
  /**
   * @access public
   * @var AdGroupCriterion
   */
  public $operand;

  /**
   * @access public
   * @var ExemptionRequest[]
   */
  public $exemptionRequests;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdGroupCriterionOperation";
  }

  public function __construct($operand = NULL, $exemptionRequests = NULL, $operator = NULL, $OperationType = NULL) {
    if(get_parent_class('AdGroupCriterionOperation')) parent::__construct();
    $this->operand = $operand;
    $this->exemptionRequests = $exemptionRequests;
    $this->operator = $operator;
    $this->OperationType = $OperationType;
  }
}}

if (!class_exists("AdGroupOperation", FALSE)) {
/**
 * AdGroup operations for adding/updating/removing adgroups.
 */
class AdGroupOperation extends Operation {
  /**
   * @access public
   * @var AdGroup
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
    return "AdGroupOperation";
  }

  public function __construct($operand = NULL, $operator = NULL, $OperationType = NULL) {
    if(get_parent_class('AdGroupOperation')) parent::__construct();
    $this->operand = $operand;
    $this->operator = $operator;
    $this->OperationType = $OperationType;
  }
}}

if (!class_exists("AdGroupServiceError", FALSE)) {
/**
 * Represents possible error codes in AdGroupService.
 */
class AdGroupServiceError extends ApiError {
  /**
   * @access public
   * @var tnsAdGroupServiceErrorReason
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
    return "AdGroupServiceError";
  }

  public function __construct($reason = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('AdGroupServiceError')) parent::__construct();
    $this->reason = $reason;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("AdScheduleTarget", FALSE)) {
/**
 * Immutable structure to hold an ad schedule target.
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

if (!class_exists("AdScheduleTargetList", FALSE)) {
/**
 * List of ad schedule targets.
 */
class AdScheduleTargetList extends TargetList {
  /**
   * @access public
   * @var AdScheduleTarget[]
   */
  public $targets;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdScheduleTargetList";
  }

  public function __construct($targets = NULL, $campaignId = NULL, $TargetListType = NULL) {
    if(get_parent_class('AdScheduleTargetList')) parent::__construct();
    $this->targets = $targets;
    $this->campaignId = $campaignId;
    $this->TargetListType = $TargetListType;
  }
}}

if (!class_exists("AdStats", FALSE)) {
/**
 * Represents stats specific to Ads
 */
class AdStats extends Stats {
  /**
   * @access public
   * @var double
   */
  public $percentServed;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "AdStats";
  }

  public function __construct($percentServed = NULL, $startDate = NULL, $endDate = NULL, $network = NULL, $clicks = NULL, $impressions = NULL, $cost = NULL, $averagePosition = NULL, $averageCpc = NULL, $averageCpm = NULL, $ctr = NULL, $conversions = NULL, $conversionRate = NULL, $costPerConversion = NULL, $conversionsManyPerClick = NULL, $conversionRateManyPerClick = NULL, $costPerConversionManyPerClick = NULL, $StatsType = NULL) {
    if(get_parent_class('AdStats')) parent::__construct();
    $this->percentServed = $percentServed;
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

if (!class_exists("Audio", FALSE)) {
/**
 * Encapsulates an Audio media identified by a MediaId.
 */
class Audio extends Media {
  /**
   * @access public
   * @var integer
   */
  public $durationMillis;

  /**
   * @access public
   * @var string
   */
  public $streamingUrl;

  /**
   * @access public
   * @var boolean
   */
  public $readyToPlayOnTheWeb;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Audio";
  }

  public function __construct($durationMillis = NULL, $streamingUrl = NULL, $readyToPlayOnTheWeb = NULL, $mediaId = NULL, $mediaTypeDb = NULL, $referenceId = NULL, $dimensions = NULL, $urls = NULL, $mimeType = NULL, $sourceUrl = NULL, $mediaSubType = NULL, $name = NULL, $fileSize = NULL, $extendedCapabilities = NULL, $creationTime = NULL, $MediaType = NULL) {
    if(get_parent_class('Audio')) parent::__construct();
    $this->durationMillis = $durationMillis;
    $this->streamingUrl = $streamingUrl;
    $this->readyToPlayOnTheWeb = $readyToPlayOnTheWeb;
    $this->mediaId = $mediaId;
    $this->mediaTypeDb = $mediaTypeDb;
    $this->referenceId = $referenceId;
    $this->dimensions = $dimensions;
    $this->urls = $urls;
    $this->mimeType = $mimeType;
    $this->sourceUrl = $sourceUrl;
    $this->mediaSubType = $mediaSubType;
    $this->name = $name;
    $this->fileSize = $fileSize;
    $this->extendedCapabilities = $extendedCapabilities;
    $this->creationTime = $creationTime;
    $this->MediaType = $MediaType;
  }
}}

if (!class_exists("BatchFailureResult", FALSE)) {
/**
 * Represents a failure result for a mutate operation that was applied in a
 * transactional batch. It does not imply that the corresponding operation
 * for this result itself caused the failure. To determine that, please see
 * the {@link FailureResult} provided for the first operation of the batch.
 */
class BatchFailureResult extends OperationResult {
  /**
   * @access public
   * @var integer
   */
  public $operationIndexInBatch;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BatchFailureResult";
  }

  public function __construct($operationIndexInBatch = NULL, $OperationResultType = NULL) {
    if(get_parent_class('BatchFailureResult')) parent::__construct();
    $this->operationIndexInBatch = $operationIndexInBatch;
    $this->OperationResultType = $OperationResultType;
  }
}}

if (!class_exists("BulkMutateJob", FALSE)) {
/**
 * A {@code BulkMutateJob} is essentially a mixed collection of mutate
 * operations from the following AdWords API campaign management services:
 * <ul>
 * <li>{@link CampaignService}</li>
 * <li>{@link CampaignTargetService}</li>
 * <li>{@link CampaignCriterionService}</li>
 * <li>{@link AdGroupService}</li>
 * <li>{@link AdGroupAdService}</li>
 * <li>{@link AdGroupCriterionService}</li>
 * </ul>
 * 
 * <p>The mutate operations in a job's request are constructed in exactly the
 * same way as they are for synchronous calls to these services.</p>
 * 
 * <p>The mutate operations are grouped by their scoping entity in the
 * AdWords customer tree. Currently, mutate operations can be grouped either
 * by the customer or by their parent campaign. However, they cannot be
 * grouped both ways - some by customer and others by campaigns - in the same
 * job.
 * 
 * <p><i>Note: A job may have no more than 500,000 mutate operations in total,
 * and no more than 10 different scoping campaigns.</i></p>
 * 
 * <p>The mutate operations must be packaged into containers called
 * {@code Operation Streams}, each tagged with the id of the scoping entity of
 * its operations.
 * 
 * <p>To facilitate the building of very large bulk mutate jobs, the operation
 * streams of a job can be submitted using multiple request parts. A job is
 * queued for processing as soon as it can be determined that all of its
 * request parts have been received.</p>
 * 
 * <p><i>Note: A job may have no more than 100 request parts. Each part may
 * have no more than 25 operation streams and no more than 10,000 operations
 * in total.</i></p>
 */
class BulkMutateJob extends Job {
  /**
   * @access public
   * @var integer
   */
  public $id;

  /**
   * @access public
   * @var BulkMutateJobPolicy
   */
  public $policy;

  /**
   * @access public
   * @var BulkMutateRequest
   */
  public $request;

  /**
   * @access public
   * @var tnsBasicJobStatus
   */
  public $status;

  /**
   * @access public
   * @var BulkMutateJobEvent[]
   */
  public $history;

  /**
   * @access public
   * @var BulkMutateResult
   */
  public $result;

  /**
   * @access public
   * @var integer
   */
  public $numRequestParts;

  /**
   * @access public
   * @var integer
   */
  public $numRequestPartsReceived;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BulkMutateJob";
  }

  public function __construct($id = NULL, $policy = NULL, $request = NULL, $status = NULL, $history = NULL, $result = NULL, $numRequestParts = NULL, $numRequestPartsReceived = NULL, $customerJobKey = NULL, $context = NULL, $failureReason = NULL, $stats = NULL, $billingSummary = NULL, $JobType = NULL) {
    if(get_parent_class('BulkMutateJob')) parent::__construct();
    $this->id = $id;
    $this->policy = $policy;
    $this->request = $request;
    $this->status = $status;
    $this->history = $history;
    $this->result = $result;
    $this->numRequestParts = $numRequestParts;
    $this->numRequestPartsReceived = $numRequestPartsReceived;
    $this->customerJobKey = $customerJobKey;
    $this->context = $context;
    $this->failureReason = $failureReason;
    $this->stats = $stats;
    $this->billingSummary = $billingSummary;
    $this->JobType = $JobType;
  }
}}

if (!class_exists("BulkMutateJobEvent", FALSE)) {
/**
 * An event defined by a status change of a bulk mutate job.
 */
class BulkMutateJobEvent extends JobEvent {
  /**
   * @access public
   * @var tnsBasicJobStatus
   */
  public $status;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BulkMutateJobEvent";
  }

  public function __construct($status = NULL, $dateTime = NULL, $JobEventType = NULL) {
    if(get_parent_class('BulkMutateJobEvent')) parent::__construct();
    $this->status = $status;
    $this->dateTime = $dateTime;
    $this->JobEventType = $JobEventType;
  }
}}

if (!class_exists("BulkMutateJobSelector", FALSE)) {
/**
 * Specifies additional criteria for selecting bulk mutate jobs.
 */
class BulkMutateJobSelector extends JobSelector {
  /**
   * @access public
   * @var integer[]
   */
  public $jobIds;

  /**
   * @access public
   * @var tnsBasicJobStatus[]
   */
  public $jobStatuses;

  /**
   * @access public
   * @var integer
   */
  public $resultPartIndex;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BulkMutateJobSelector";
  }

  public function __construct($jobIds = NULL, $jobStatuses = NULL, $resultPartIndex = NULL, $customerJobKeys = NULL, $includeHistory = NULL, $includeStats = NULL, $JobSelectorType = NULL) {
    if(get_parent_class('BulkMutateJobSelector')) parent::__construct();
    $this->jobIds = $jobIds;
    $this->jobStatuses = $jobStatuses;
    $this->resultPartIndex = $resultPartIndex;
    $this->customerJobKeys = $customerJobKeys;
    $this->includeHistory = $includeHistory;
    $this->includeStats = $includeStats;
    $this->JobSelectorType = $JobSelectorType;
  }
}}

if (!class_exists("BulkMutateJobStats", FALSE)) {
/**
 * Bulk mutate job-handling statistics.
 */
class BulkMutateJobStats extends JobStats {
  /**
   * @access public
   * @var integer
   */
  public $numOperations;

  /**
   * @access public
   * @var integer
   */
  public $numFailedOperations;

  /**
   * @access public
   * @var integer
   */
  public $numUnprocessedOperations;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "BulkMutateJobStats";
  }

  public function __construct($numOperations = NULL, $numFailedOperations = NULL, $numUnprocessedOperations = NULL, $progressPercent = NULL, $pendingTimeMillis = NULL, $processingTimeMillis = NULL, $JobStatsType = NULL) {
    if(get_parent_class('BulkMutateJobStats')) parent::__construct();
    $this->numOperations = $numOperations;
    $this->numFailedOperations = $numFailedOperations;
    $this->numUnprocessedOperations = $numUnprocessedOperations;
    $this->progressPercent = $progressPercent;
    $this->pendingTimeMillis = $pendingTimeMillis;
    $this->processingTimeMillis = $processingTimeMillis;
    $this->JobStatsType = $JobStatsType;
  }
}}

if (!class_exists("CampaignCriterionLimitExceeded", FALSE)) {
/**
 * Error to indicate that too many negative criteria added to some campaign.
 */
class CampaignCriterionLimitExceeded extends EntityCountLimitExceeded {
  /**
   * @access public
   * @var tnsCampaignCriterionLimitExceededCriteriaLimitType
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
    return "CampaignCriterionLimitExceeded";
  }

  public function __construct($limitType = NULL, $reason = NULL, $enclosingId = NULL, $limit = NULL, $fieldPath = NULL, $trigger = NULL, $ApiErrorType = NULL) {
    if(get_parent_class('CampaignCriterionLimitExceeded')) parent::__construct();
    $this->limitType = $limitType;
    $this->reason = $reason;
    $this->enclosingId = $enclosingId;
    $this->limit = $limit;
    $this->fieldPath = $fieldPath;
    $this->trigger = $trigger;
    $this->ApiErrorType = $ApiErrorType;
  }
}}

if (!class_exists("CampaignCriterionOperation", FALSE)) {
/**
 * Generic operation (add, remove and set) for campaign criteria.
 */
class CampaignCriterionOperation extends Operation {
  /**
   * @access public
   * @var CampaignCriterion
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
    return "CampaignCriterionOperation";
  }

  public function __construct($operand = NULL, $operator = NULL, $OperationType = NULL) {
    if(get_parent_class('CampaignCriterionOperation')) parent::__construct();
    $this->operand = $operand;
    $this->operator = $operator;
    $this->OperationType = $OperationType;
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

if (!class_exists("CampaignTargetOperation", FALSE)) {
/**
 * <span class="constraint Required">This field is required and should not be {@code null}.</span>
 */
class CampaignTargetOperation extends Operation {
  /**
   * @access public
   * @var TargetList
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
    return "CampaignTargetOperation";
  }

  public function __construct($operand = NULL, $operator = NULL, $OperationType = NULL) {
    if(get_parent_class('CampaignTargetOperation')) parent::__construct();
    $this->operand = $operand;
    $this->operator = $operator;
    $this->OperationType = $OperationType;
  }
}}

if (!class_exists("ContentLabel", FALSE)) {
/**
 * Content Label for category exclusion.
 */
class ContentLabel extends Criterion {
  /**
   * @access public
   * @var tnsContentLabelType
   */
  public $contentLabelType;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "ContentLabel";
  }

  public function __construct($contentLabelType = NULL, $id = NULL, $CriterionType = NULL) {
    if(get_parent_class('ContentLabel')) parent::__construct();
    $this->contentLabelType = $contentLabelType;
    $this->id = $id;
    $this->CriterionType = $CriterionType;
  }
}}

if (!class_exists("CriterionPolicyError", FALSE)) {
/**
 * Contains the policy violations for a single BiddableAdGroupCriterion.
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

if (!class_exists("DemographicTarget", FALSE)) {
/**
 * Abstract class to identify a demographic target.
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

if (!class_exists("DemographicTargetList", FALSE)) {
/**
 * List of demographic targets.
 */
class DemographicTargetList extends TargetList {
  /**
   * @access public
   * @var DemographicTarget[]
   */
  public $targets;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "DemographicTargetList";
  }

  public function __construct($targets = NULL, $campaignId = NULL, $TargetListType = NULL) {
    if(get_parent_class('DemographicTargetList')) parent::__construct();
    $this->targets = $targets;
    $this->campaignId = $campaignId;
    $this->TargetListType = $TargetListType;
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

if (!class_exists("FailureResult", FALSE)) {
/**
 * Represents a failure result for an individual mutate operation.
 * 
 * <p>Currently, operations are applied in transactional batches, so the
 * corresponding operation for this result may not itself be faulty.</p>
 * 
 * <p>The errors in the exception may contain {@code OGNL path}s that identify
 * the faulty operations in the batch. Please see the subsequent
 * {@link BatchFailureResult}s to determine the remaining operations that were
 * also applied in the batch and their positions within the batch.</p>
 */
class FailureResult extends OperationResult {
  /**
   * @access public
   * @var ApiException
   */
  public $cause;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "FailureResult";
  }

  public function __construct($cause = NULL, $OperationResultType = NULL) {
    if(get_parent_class('FailureResult')) parent::__construct();
    $this->cause = $cause;
    $this->OperationResultType = $OperationResultType;
  }
}}

if (!class_exists("GenderTarget", FALSE)) {
/**
 * Immutable structure to hold a gender target.
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

if (!class_exists("GeoTarget", FALSE)) {
/**
 * Abstract class to identify a geographic target.
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

if (!class_exists("GeoTargetList", FALSE)) {
/**
 * List of geographic targets.
 */
class GeoTargetList extends TargetList {
  /**
   * @access public
   * @var GeoTarget[]
   */
  public $targets;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "GeoTargetList";
  }

  public function __construct($targets = NULL, $campaignId = NULL, $TargetListType = NULL) {
    if(get_parent_class('GeoTargetList')) parent::__construct();
    $this->targets = $targets;
    $this->campaignId = $campaignId;
    $this->TargetListType = $TargetListType;
  }
}}

if (!class_exists("Image", FALSE)) {
/**
 * 
 */
class Image extends Media {
  /**
   * @access public
   * @var base64Binary
   */
  public $data;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "Image";
  }

  public function __construct($data = NULL, $mediaId = NULL, $mediaTypeDb = NULL, $referenceId = NULL, $dimensions = NULL, $urls = NULL, $mimeType = NULL, $sourceUrl = NULL, $mediaSubType = NULL, $name = NULL, $fileSize = NULL, $extendedCapabilities = NULL, $creationTime = NULL, $MediaType = NULL) {
    if(get_parent_class('Image')) parent::__construct();
    $this->data = $data;
    $this->mediaId = $mediaId;
    $this->mediaTypeDb = $mediaTypeDb;
    $this->referenceId = $referenceId;
    $this->dimensions = $dimensions;
    $this->urls = $urls;
    $this->mimeType = $mimeType;
    $this->sourceUrl = $sourceUrl;
    $this->mediaSubType = $mediaSubType;
    $this->name = $name;
    $this->fileSize = $fileSize;
    $this->extendedCapabilities = $extendedCapabilities;
    $this->creationTime = $creationTime;
    $this->MediaType = $MediaType;
  }
}}

if (!class_exists("JobOperation", FALSE)) {
/**
 * Mutate operations of a job service:
 * <ul>
 * <li>ADD: submit a new job;
 * <li>SET: update a previously submitted job.
 * </ul>
 */
class JobOperation extends Operation {
  /**
   * @access public
   * @var Job
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
    return "JobOperation";
  }

  public function __construct($operand = NULL, $operator = NULL, $OperationType = NULL) {
    if(get_parent_class('JobOperation')) parent::__construct();
    $this->operand = $operand;
    $this->operator = $operator;
    $this->OperationType = $OperationType;
  }
}}

if (!class_exists("LanguageTarget", FALSE)) {
/**
 * Immutable structure to hold a language target.
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

if (!class_exists("LanguageTargetList", FALSE)) {
/**
 * List of language targets.
 */
class LanguageTargetList extends TargetList {
  /**
   * @access public
   * @var LanguageTarget[]
   */
  public $targets;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LanguageTargetList";
  }

  public function __construct($targets = NULL, $campaignId = NULL, $TargetListType = NULL) {
    if(get_parent_class('LanguageTargetList')) parent::__construct();
    $this->targets = $targets;
    $this->campaignId = $campaignId;
    $this->TargetListType = $TargetListType;
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

if (!class_exists("LostResult", FALSE)) {
/**
 * Represents the result of processing an individual mutate operation that
 * however failed to get durably saved despite the service's best efforts.
 * 
 * <p>The operation is considered to have completed processing because it
 * either was successfully performed or failed with validation errors, and
 * cannot be retried without the risk of doing duplicate work.
 */
class LostResult extends OperationResult {
  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "LostResult";
  }

  public function __construct($OperationResultType = NULL) {
    if(get_parent_class('LostResult')) parent::__construct();
    $this->OperationResultType = $OperationResultType;
  }
}}

if (!class_exists("MetroTarget", FALSE)) {
/**
 * Immutable structure to specify a geographic target for a metro.
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

if (!class_exists("NetworkTarget", FALSE)) {
/**
 * Immutable structure to hold a network coverage target.
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

if (!class_exists("NetworkTargetList", FALSE)) {
/**
 * List of network targets.
 */
class NetworkTargetList extends TargetList {
  /**
   * @access public
   * @var NetworkTarget[]
   */
  public $targets;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "NetworkTargetList";
  }

  public function __construct($targets = NULL, $campaignId = NULL, $TargetListType = NULL) {
    if(get_parent_class('NetworkTargetList')) parent::__construct();
    $this->targets = $targets;
    $this->campaignId = $campaignId;
    $this->TargetListType = $TargetListType;
  }
}}

if (!class_exists("PlatformTarget", FALSE)) {
/**
 * Immutable structure to hold a platform target.
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

if (!class_exists("PlatformTargetList", FALSE)) {
/**
 * List of platform targets.
 */
class PlatformTargetList extends TargetList {
  /**
   * @access public
   * @var PlatformTarget[]
   */
  public $targets;

  /**
   * Gets the namesapce of this class
   * @return the namespace of this class
   */
  public function getNamespace() {
    return "https://adwords.google.com/api/adwords/cm/v200909";
  }

  /**
   * Gets the xsi:type name of this class
   * @return the xsi:type name of this class
   */
  public function getXsiTypeName() {
    return "PlatformTargetList";
  }

  public function __construct($targets = NULL, $campaignId = NULL, $TargetListType = NULL) {
    if(get_parent_class('PlatformTargetList')) parent::__construct();
    $this->targets = $targets;
    $this->campaignId = $campaignId;
    $this->TargetListType = $TargetListType;
  }
}}

if (!class_exists("PolygonTarget", FALSE)) {
/**
 * Structure to specify a geographic target for a polygon location.
 * 
 * This polygon target consists of a list of vertices;
 * each vertex is a geo point defined by a latitude and longitude.
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

if (!class_exists("AgeTarget", FALSE)) {
/**
 * Immutable structure to hold an age target.
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

if (!class_exists("BulkMutateJobService")) {
/**
 * BulkMutateJobService
 * @author WSDLInterpreter
 */
class BulkMutateJobService extends AdWordsSoapClient {
  /**
   * Default class map for wsdl=>php
   * @access private
   * @var array
   */
  public static $classmap = array(
    "getResponse" => "BulkMutateJobServiceGetResponse",
    "get" => "BulkMutateJobServiceGet",
    "mutate" => "BulkMutateJobServiceMutate",
    "mutateResponse" => "BulkMutateJobServiceMutateResponse",
    "DateTime" => "AdWordsDateTime",
    "Target" => "AdWordsTarget",
    "SoapHeader" => "SoapRequestHeader",
    "Ad" => "Ad",
    "AdError" => "AdError",
    "ApiError" => "ApiError",
    "AdExtension" => "AdExtension",
    "AdExtensionOverride" => "AdExtensionOverride",
    "AdExtensionOverrideStats" => "AdExtensionOverrideStats",
    "Stats" => "Stats",
    "AdGroup" => "AdGroup",
    "AdGroupAd" => "AdGroupAd",
    "AdGroupAdCountLimitExceeded" => "AdGroupAdCountLimitExceeded",
    "EntityCountLimitExceeded" => "EntityCountLimitExceeded",
    "AdGroupAdError" => "AdGroupAdError",
    "AdGroupAdOperation" => "AdGroupAdOperation",
    "Operation" => "Operation",
    "AdGroupBids" => "AdGroupBids",
    "AdGroupCriterion" => "AdGroupCriterion",
    "AdGroupCriterionBids" => "AdGroupCriterionBids",
    "AdGroupCriterionError" => "AdGroupCriterionError",
    "AdGroupCriterionLimitExceeded" => "AdGroupCriterionLimitExceeded",
    "AdGroupCriterionOperation" => "AdGroupCriterionOperation",
    "AdGroupOperation" => "AdGroupOperation",
    "AdGroupServiceError" => "AdGroupServiceError",
    "AdScheduleTarget" => "AdScheduleTarget",
    "AdScheduleTargetList" => "AdScheduleTargetList",
    "TargetList" => "TargetList",
    "AdStats" => "AdStats",
    "AdUnionId" => "AdUnionId",
    "Address" => "Address",
    "AgeTarget" => "AgeTarget",
    "DemographicTarget" => "DemographicTarget",
    "ApiErrorReason" => "ApiErrorReason",
    "ApiException" => "ApiException",
    "ApplicationException" => "ApplicationException",
    "Audio" => "Audio",
    "Media" => "Media",
    "AuthenticationError" => "AuthenticationError",
    "AuthorizationError" => "AuthorizationError",
    "BatchFailureResult" => "BatchFailureResult",
    "OperationResult" => "OperationResult",
    "Bid" => "Bid",
    "BiddableAdGroupCriterion" => "BiddableAdGroupCriterion",
    "BiddingError" => "BiddingError",
    "BiddingStrategy" => "BiddingStrategy",
    "BiddingTransition" => "BiddingTransition",
    "BiddingTransitionError" => "BiddingTransitionError",
    "BillingSummary" => "BillingSummary",
    "Budget" => "Budget",
    "BudgetError" => "BudgetError",
    "BudgetOptimizer" => "BudgetOptimizer",
    "BudgetOptimizerAdGroupBids" => "BudgetOptimizerAdGroupBids",
    "BudgetOptimizerAdGroupCriterionBids" => "BudgetOptimizerAdGroupCriterionBids",
    "BulkMutateJob" => "BulkMutateJob",
    "Job" => "Job",
    "BulkMutateJobError" => "BulkMutateJobError",
    "BulkMutateJobEvent" => "BulkMutateJobEvent",
    "JobEvent" => "JobEvent",
    "BulkMutateJobPolicy" => "BulkMutateJobPolicy",
    "BulkMutateJobSelector" => "BulkMutateJobSelector",
    "JobSelector" => "JobSelector",
    "BulkMutateJobStats" => "BulkMutateJobStats",
    "JobStats" => "JobStats",
    "BulkMutateRequest" => "BulkMutateRequest",
    "BulkMutateResult" => "BulkMutateResult",
    "Campaign" => "Campaign",
    "CampaignCriterion" => "CampaignCriterion",
    "CampaignCriterionError" => "CampaignCriterionError",
    "CampaignCriterionLimitExceeded" => "CampaignCriterionLimitExceeded",
    "CampaignCriterionOperation" => "CampaignCriterionOperation",
    "CampaignError" => "CampaignError",
    "CampaignOperation" => "CampaignOperation",
    "CampaignTargetOperation" => "CampaignTargetOperation",
    "CityTarget" => "CityTarget",
    "GeoTarget" => "GeoTarget",
    "ClientTermsError" => "ClientTermsError",
    "ComparableValue" => "ComparableValue",
    "ContentLabel" => "ContentLabel",
    "Criterion" => "Criterion",
    "ConversionOptimizer" => "ConversionOptimizer",
    "ConversionOptimizerAdGroupBids" => "ConversionOptimizerAdGroupBids",
    "ConversionOptimizerAdGroupCriterionBids" => "ConversionOptimizerAdGroupCriterionBids",
    "ConversionOptimizerBiddingTransition" => "ConversionOptimizerBiddingTransition",
    "CountryTarget" => "CountryTarget",
    "CriterionPolicyError" => "CriterionPolicyError",
    "PolicyViolationError" => "PolicyViolationError",
    "DatabaseError" => "DatabaseError",
    "DateError" => "DateError",
    "DemographicTargetList" => "DemographicTargetList",
    "DeprecatedAd" => "DeprecatedAd",
    "Dimensions" => "Dimensions",
    "DistinctError" => "DistinctError",
    "DoubleValue" => "DoubleValue",
    "NumberValue" => "NumberValue",
    "EntityAccessDenied" => "EntityAccessDenied",
    "EntityId" => "EntityId",
    "EntityNotFound" => "EntityNotFound",
    "ExemptionRequest" => "ExemptionRequest",
    "FailureResult" => "FailureResult",
    "FrequencyCap" => "FrequencyCap",
    "GenderTarget" => "GenderTarget",
    "GeoPoint" => "GeoPoint",
    "GeoTargetList" => "GeoTargetList",
    "IdError" => "IdError",
    "Image" => "Image",
    "ImageAd" => "ImageAd",
    "ImageError" => "ImageError",
    "InternalApiError" => "InternalApiError",
    "JobContext" => "JobContext",
    "JobError" => "JobError",
    "JobOperation" => "JobOperation",
    "Keyword" => "Keyword",
    "LanguageTarget" => "LanguageTarget",
    "LanguageTargetList" => "LanguageTargetList",
    "LocalBusinessAd" => "LocalBusinessAd",
    "LocationExtension" => "LocationExtension",
    "LocationOverrideInfo" => "LocationOverrideInfo",
    "LongValue" => "LongValue",
    "LostResult" => "LostResult",
    "ManualCPC" => "ManualCPC",
    "ManualCPCAdGroupBids" => "ManualCPCAdGroupBids",
    "ManualCPCAdGroupCriterionBids" => "ManualCPCAdGroupCriterionBids",
    "ManualCPM" => "ManualCPM",
    "ManualCPMAdGroupBids" => "ManualCPMAdGroupBids",
    "ManualCPMAdGroupCriterionBids" => "ManualCPMAdGroupCriterionBids",
    "MediaError" => "MediaError",
    "Media_MediaExtendedCapabilityType_Media_MediaExtendedCapabilityStateMapEntry" => "Media_MediaExtendedCapabilityType_Media_MediaExtendedCapabilityStateMapEntry",
    "Media_Size_DimensionsMapEntry" => "Media_Size_DimensionsMapEntry",
    "Media_Size_StringMapEntry" => "Media_Size_StringMapEntry",
    "MetroTarget" => "MetroTarget",
    "MobileAd" => "MobileAd",
    "MobileImageAd" => "MobileImageAd",
    "Money" => "Money",
    "NegativeAdGroupCriterion" => "NegativeAdGroupCriterion",
    "NegativeCampaignCriterion" => "NegativeCampaignCriterion",
    "NetworkTarget" => "NetworkTarget",
    "NetworkTargetList" => "NetworkTargetList",
    "NewEntityCreationError" => "NewEntityCreationError",
    "NotEmptyError" => "NotEmptyError",
    "NotWhitelistedError" => "NotWhitelistedError",
    "NullError" => "NullError",
    "Operand" => "Operand",
    "OperationAccessDenied" => "OperationAccessDenied",
    "OperationStream" => "OperationStream",
    "OperationStreamResult" => "OperationStreamResult",
    "OperatorError" => "OperatorError",
    "OverrideInfo" => "OverrideInfo",
    "PagingError" => "PagingError",
    "Placement" => "Placement",
    "PlatformTarget" => "PlatformTarget",
    "PlatformTargetList" => "PlatformTargetList",
    "PolicyViolationError.Part" => "PolicyViolationErrorPart",
    "PolicyViolationKey" => "PolicyViolationKey",
    "PolygonTarget" => "PolygonTarget",
    "PositionPreferenceAdGroupCriterionBids" => "PositionPreferenceAdGroupCriterionBids",
    "ProvinceTarget" => "ProvinceTarget",
    "ProximityTarget" => "ProximityTarget",
    "QualityInfo" => "QualityInfo",
    "QuotaCheckError" => "QuotaCheckError",
    "QuotaError" => "QuotaError",
    "QuotaExceededError" => "QuotaExceededError",
    "RangeError" => "RangeError",
    "ReadOnlyError" => "ReadOnlyError",
    "RegionCodeError" => "RegionCodeError",
    "RequiredError" => "RequiredError",
    "ReturnValueResult" => "ReturnValueResult",
    "SizeLimitError" => "SizeLimitError",
    "SoapResponseHeader" => "SoapResponseHeader",
    "StatsQueryError" => "StatsQueryError",
    "StringLengthError" => "StringLengthError",
    "TargetError" => "TargetError",
    "TempAdUnionId" => "TempAdUnionId",
    "TemplateAd" => "TemplateAd",
    "TemplateElement" => "TemplateElement",
    "TemplateElementField" => "TemplateElementField",
    "TextAd" => "TextAd",
    "UnprocessedResult" => "UnprocessedResult",
    "Video" => "Video",
    "Ad.ApprovalStatus" => "AdApprovalStatus",
    "AdError.Reason" => "AdErrorReason",
    "AdExtensionOverride.ApprovalStatus" => "AdExtensionOverrideApprovalStatus",
    "AdExtensionOverride.Status" => "AdExtensionOverrideStatus",
    "AdGroup.Status" => "AdGroupStatus",
    "AdGroupAd.Status" => "AdGroupAdStatus",
    "AdGroupAdError.Reason" => "AdGroupAdErrorReason",
    "AdGroupCriterionError.Reason" => "AdGroupCriterionErrorReason",
    "AdGroupCriterionLimitExceeded.CriteriaLimitType" => "AdGroupCriterionLimitExceededCriteriaLimitType",
    "AdGroupServiceError.Reason" => "AdGroupServiceErrorReason",
    "AdServingOptimizationStatus" => "AdServingOptimizationStatus",
    "AgeTarget.Age" => "AgeTargetAge",
    "ApprovalStatus" => "ApprovalStatus",
    "AuthenticationError.Reason" => "AuthenticationErrorReason",
    "AuthorizationError.Reason" => "AuthorizationErrorReason",
    "AutoKeywordMatchingStatus" => "AutoKeywordMatchingStatus",
    "BasicJobStatus" => "BasicJobStatus",
    "BidSource" => "BidSource",
    "BiddingError.Reason" => "BiddingErrorReason",
    "BiddingTransitionError.Reason" => "BiddingTransitionErrorReason",
    "Budget.BudgetDeliveryMethod" => "BudgetBudgetDeliveryMethod",
    "Budget.BudgetPeriod" => "BudgetBudgetPeriod",
    "BudgetError.Reason" => "BudgetErrorReason",
    "BulkMutateJobError.Reason" => "BulkMutateJobErrorReason",
    "CampaignCriterionError.Reason" => "CampaignCriterionErrorReason",
    "CampaignCriterionLimitExceeded.CriteriaLimitType" => "CampaignCriterionLimitExceededCriteriaLimitType",
    "CampaignError.Reason" => "CampaignErrorReason",
    "CampaignStatus" => "CampaignStatus",
    "ClientTermsError.Reason" => "ClientTermsErrorReason",
    "ContentLabelType" => "ContentLabelType",
    "DatabaseError.Reason" => "DatabaseErrorReason",
    "DateError.Reason" => "DateErrorReason",
    "DayOfWeek" => "DayOfWeek",
    "DeprecatedAd.Type" => "DeprecatedAdType",
    "DistinctError.Reason" => "DistinctErrorReason",
    "EntityAccessDenied.Reason" => "EntityAccessDeniedReason",
    "EntityCountLimitExceeded.Reason" => "EntityCountLimitExceededReason",
    "EntityId.Type" => "EntityIdType",
    "EntityNotFound.Reason" => "EntityNotFoundReason",
    "GenderTarget.Gender" => "GenderTargetGender",
    "IdError.Reason" => "IdErrorReason",
    "ImageError.Reason" => "ImageErrorReason",
    "InternalApiError.Reason" => "InternalApiErrorReason",
    "JobError.Reason" => "JobErrorReason",
    "KeywordMatchType" => "KeywordMatchType",
    "Level" => "Level",
    "LoasAuthenticationError.Reason" => "LoasAuthenticationErrorReason",
    "LocationExtension.Source" => "LocationExtensionSource",
    "LocationOverrideInfo.RadiusUnits" => "LocationOverrideInfoRadiusUnits",
    "MarkupLanguageType" => "MarkupLanguageType",
    "Media.MediaExtendedCapabilityState" => "MediaMediaExtendedCapabilityState",
    "Media.MediaExtendedCapabilityType" => "MediaMediaExtendedCapabilityType",
    "Media.MediaSubType" => "MediaMediaSubType",
    "Media.MediaType" => "MediaMediaType",
    "Media.MimeType" => "MediaMimeType",
    "Media.Size" => "MediaSize",
    "MediaError.Reason" => "MediaErrorReason",
    "MinuteOfHour" => "MinuteOfHour",
    "NetworkCoverageType" => "NetworkCoverageType",
    "NewEntityCreationError.Reason" => "NewEntityCreationErrorReason",
    "NotEmptyError.Reason" => "NotEmptyErrorReason",
    "NotWhitelistedError.Reason" => "NotWhitelistedErrorReason",
    "NullError.Reason" => "NullErrorReason",
    "OperationAccessDenied.Reason" => "OperationAccessDeniedReason",
    "Operator" => "Operator",
    "OperatorError.Reason" => "OperatorErrorReason",
    "PagingError.Reason" => "PagingErrorReason",
    "PlatformType" => "PlatformType",
    "PolicyViolationError.Reason" => "PolicyViolationErrorReason",
    "PricingModel" => "PricingModel",
    "ProximityTarget.DistanceUnits" => "ProximityTargetDistanceUnits",
    "QuotaCheckError.Reason" => "QuotaCheckErrorReason",
    "QuotaError.Reason" => "QuotaErrorReason",
    "QuotaExceededError.Reason" => "QuotaExceededErrorReason",
    "RangeError.Reason" => "RangeErrorReason",
    "ReadOnlyError.Reason" => "ReadOnlyErrorReason",
    "RegionCodeError.Reason" => "RegionCodeErrorReason",
    "RequiredError.Reason" => "RequiredErrorReason",
    "ServingStatus" => "ServingStatus",
    "SizeLimitError.Reason" => "SizeLimitErrorReason",
    "Stats.Network" => "StatsNetwork",
    "StatsQueryError.Reason" => "StatsQueryErrorReason",
    "StringLengthError.Reason" => "StringLengthErrorReason",
    "SystemServingStatus" => "SystemServingStatus",
    "TargetError.Reason" => "TargetErrorReason",
    "TemplateElementField.Type" => "TemplateElementFieldType",
    "TimeUnit" => "TimeUnit",
    "UserStatus" => "UserStatus",
  );

  /**
   * Constructor using wsdl location and options array
   * @param string $wsdl WSDL location for this service
   * @param array $options Options for the SoapClient
   */
  public function __construct($wsdl=null, $options, $user) {
    $options["classmap"] = BulkMutateJobService::$classmap;
    parent::__construct($wsdl, $options, $user, 'BulkMutateJobService', 'https://adwords.google.com/api/adwords/cm/v200909');
  }

  /**
   * <span class="constraint Required">This field is required and should not be {@code null}.</span>
   * 
   * 
   * 
   * Returns a list of bulk mutate jobs.
   * 
   * @param selector Specifies which jobs to return.
   * If the selector is empty, all jobs are returned.
   * @return List of bulk mutate jobs meeting the {@code selector} criteria.
   * @throws ApiException if problems occurred while fetching the jobs
   */
  public function get($selector) {
    $arg = new BulkMutateJobServiceGet($selector);
    $result = $this->__soapCall("get", array($arg));
    return $result->rval;
  }


  /**
   * <span class="constraint Required">This field is required and should not be {@code null}.</span>
   * 
   * 
   * 
   * Adds or updates a bulk mutate job.
   * 
   * <p>Use the &laquo;ADD&raquo; operator to submit a new job, and the
   * &laquo;SET&raquo; operator to add additional
   * {@linkplain BulkMutateRequest request parts} to an existing job.
   * The &laquo;DELETE&raquo; operator is not supported.</p>
   * 
   * <p class="note"><b>Note:</b> In the current implementation, the check for
   * duplicate job keys is only "best effort", and may not prevent jobs with
   * the same keys from being accepted if they are submitted around the same
   * instant.</p>
   * 
   * @param operation The operation to perform.
   * @throws ApiException if problems occurred while creating or updating jobs
   * @return The added or updated bulk mutate job.
   */
  public function mutate($operation) {
    $arg = new BulkMutateJobServiceMutate($operation);
    $result = $this->__soapCall("mutate", array($arg));
    return $result->rval;
  }


}}

?>