<?php
/**
 * An extension of the {@link SoapClient} class intended to prepare
 * the XML before making a request as well as perform any book-keeping on
 * the response.
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
require_once dirname(__FILE__) . '/../Util/Logger.php';
require_once dirname(__FILE__) . '/../Util/SoapRequestXmlFixer.php';
require_once dirname(__FILE__) . '/../Util/XmlUtils.php';

/**
 * An extension of the {@link SoapClient} class intended to prepare
 * the XML before making a request as well as perform any book-keeping on
 * the response.
 */
abstract class AdsSoapClient extends SoapClient {
  /**
   * The {@link AdsUser} which generated this client.
   * @var AdsUser the user that generated this client
   * @access protected
   */
  protected $user;

  /**
   * The header values.
   * @var array the header values
   * @access protected
   */
  protected $headers;

  /**
   * The name of the service this client is accessing.
   * @var string the name of the service this client is accessing
   * @access protected
   */
  protected $serviceName;

  /**
   * The namespace of the service this client is accessing.
   * @var string the namespace of the service this client is accessing
   * @access protected
   */
  protected $serviceNamespace;

  /**
   * The last SOAP XML request made to the server after PrepareRequest() and
   * RemoveSensitiveInfo() have been called on it.
   * @var string the last SOAP XML request made to the server
   * @see PrepareRequest()
   * @see RemoveSensitiveInfo()
   * @access protected
   */
  protected $lastRequest;

  /**
   * The last SOAP XML DOMDocument request made to the server after
   * PrepareRequest() and RemoveSensitiveInfo() have been called on it.
   * @var DOMDocument the last SOAP XML request made to the server. Can be
   *     <var>NULL</var> if the last request was not proper XML
   * @see PrepareRequest()
   * @see RemoveSensitiveInfo()
   * @access private
   */
  private $lastRequestDom;

  /**
   * The last SOAP XML response from the server.
   * @var string the last SOAP XML response from the server
   * @access protected
   */
  protected $lastResponse;

  /**
   * The last SOAP XML DOMDocument response from the server.
   * @var DOMDocument the last SOAP XML response from the server. Can be
   *     <var>NULL</var> if the last request was not proper XML
   * @access private
   */
  private $lastResponseDom;

  /**
   * The last SOAP fault generated from the server. <var>NULL</var> if none.
   * @var SOAPFault the last SOAP fault generated from the server
   * @access protected
   */
  protected $lastSoapFault;

  /**
   * The name of the last method called from this client.
   * @var string the name of the last method called from this client
   * @access protected
   */
  protected $lastMethodName;

  /**
   * The last arguments passed to the called SOAP method
   * @var array the last arguments passed to the called SOAP method
   */
  protected $lastArguments;

  /**
   * The constructor intended to be called by all sub-classes.
   * @param string $wsdl URI of the WSDL file or NULL if working in non-WSDL
   *     mode
   * @param array $options the SOAP client options
   * @param AdsUser $user the user which is responsible for this client
   * @param string $serviceName the name of the service which is making this
   *     call
   * @param string $serviceNamespace the namespace of the service
   * @access protected
   */
  protected function __construct($wsdl, array $options, AdsUser $user,
      $serviceName, $serviceNamespace) {
    $this->user = $user;
    $this->serviceName = $serviceName;
    $this->serviceNamespace = $serviceNamespace;
    parent::__construct($wsdl, $options);
  }

  /**
   * Overrides the method SoapClient.__doRequest() to
   * perform a clean up of the request XML before marshalling.
   * @param string $request the request XML
   * @param string $location the URL to request
   * @param string $action the SOAP action
   * @param string $version the SOAP version
   * @return string the XML SOAP response
   */
  function __doRequest($request, $location, $action, $version) {
    $this->lastRequest = $this->PrepareRequest($request, $this->lastArguments);
    return parent::__doRequest($this->lastRequest,
        $location, $action, $version);
  }

  /**
   * Overrides the method SoapClient.__soapCall() to process the
   * response from the SOAP call.
   * @param string $function_name the name of the function being called
   * @param array $arguments the arguments to that function
   * @param array $options the options for the SOAP call
   * @param array $input_headers the optional input headers
   * @param array $output_headers the options output headers
   * @return mixed the return from the parent __soapCall
   * @throws SOAPFault if there was an exception making the request
   */
  function __soapCall($function_name, $arguments, $options = NULL,
      $input_headers = NULL, &$output_headers = NULL) {
    try {
      $input_headers[] = $this->GenerateSoapHeader();
      $this->lastArguments = $arguments;
      $response = parent::__soapCall($function_name, $arguments, $options,
          $input_headers, $output_headers);
      $this->ProcessResponse($this->lastRequest,
          $this->__getLastResponse(), $function_name);
      return $response;
    } catch (SoapFault $e) {
      $this->ProcessResponse($this->lastRequest,
          $this->__getLastResponse(), $function_name, $e);
      throw $e;
    }
  }

  /**
   * Processes the response from the __doRequest call.
   * @param string $request the request to the server
   * @param string $response the response from the server
   * @param string $method the method called
   * @param SoapFault $e the SOAP fault thrown if any
   * @access private
   */
  private function ProcessResponse($request, $response, $method,
      SoapFault $e = NULL) {
    $this->lastSoapFault = $e;
    $this->lastRequestDom = NULL;
    $this->lastResponseDom = NULL;
    $this->lastRequest = $this->RemoveSensitiveInfo($request);
    $this->lastResponse = $response;
    $this->lastMethodName = $method;

    try {
      $this->GetLastResponseDom();
    } catch (DOMException $domException) {
      //TODO(api.arogal): Log warning that DOM could not be created.
    }

    try {
      $this->GetLastRequestDom();
    } catch (DOMException $domException) {
      //TODO(api.arogal): Log warning that DOM could not be created.
    }

    $this->LogSoapXml();
    $this->LogRequestInfo();
  }

  /**
   * Logs the SOAP XML to the Logger::$SOAP_XML_LOG log after the request has
   * transformed by PrepareRequest() and both the request and response have
   * been sanitized by RemoveSensitiveInfo().
   * @see PrepareRequest()
   * @see RemoveSensitiveInfo()
   * @access private
   */
  private function LogSoapXml() {
    Logger::log(Logger::$SOAP_XML_LOG, $this->__getLastRequestHeaders()
        . XmlUtils::PrettyPrint($this->lastRequest) . "\n\n"
        . $this->__getLastResponseHeaders() . "\n"
        . XmlUtils::PrettyPrint($this->lastResponse));
  }

  /**
   * Logs the request info to the Logger::$REQUEST_INFO_LOG log the request has
   * transformed by PrepareRequest() and both the request has been sanitized by
   * RemoveSensitiveInfo().
   * @see PrepareRequest()
   * @see RemoveSensitiveInfo()
   * @access private
   */
  private function LogRequestInfo() {
    Logger::log(Logger::$REQUEST_INFO_LOG,
        $this->GenerateRequestInfoMessage($this->lastRequest,
            $this->lastResponse, $this->lastSoapFault));
  }

  /**
   * Gets the user for this client.
   * @return AdsUser the user for this client.
   */
  public function GetAdsUser() {
    return $this->user;
  }

  /**
   * Gets the server that the request was made to.
   * @return string the server that the request was made to
   */
  public function GetServer() {
    $hostMatches = array();
    preg_match('/^.*Host:\\s(.*)Connection:.*$/s',
       $this->__getLastRequestHeaders(), $hostMatches);
    return trim($hostMatches[1]);
  }

  /**
   * Gets the email of the user making the request.
   * @return string the email of the user making the request
   */
  public function GetEmail() {
    return $this->user->getEmail();
  }

  /**
   * Gets the service name for this client.
   * @return string the service name for this client
   */
  public function GetServiceName() {
    return $this->serviceName;
  }

  /**
   * Gets the method name for the last method called.
   * @return string the name of last method called
   */
  public function GetLastMethodName() {
    return $this->lastMethodName;
  }

  /**
   * Gets the response time for the last call
   * @return double the response time of the last call
   */
  public function GetLastResponseTime() {
    try {
      $responseTimeElements =
          $this->GetLastResponseDom()->getElementsByTagName('responseTime');
      foreach ($responseTimeElements as $responseTimeElement) {
        return $responseTimeElement->nodeValue;
      }
    } catch (DOMException $e) {
      // TODO(api.arogal): Log failures to retrieve headers.
      return "null";
    }
  }

  /**
   * Gets the request ID for the last call
   * @return string the request ID of the last call
   */
  public function GetLastRequestId() {
    try {
      $requestIdElements =
          $this->GetLastResponseDom()->getElementsByTagName('requestId');
      foreach ($requestIdElements as $requestIdElement) {
        return $requestIdElement->nodeValue;
      }
    } catch (DOMException $e) {
      // TODO(api.arogal): Log failures to retrieve headers.
      return 'null';
    }
  }

  /**
   * Returns <var>TRUE</var> if there was a SOAP fault during the last call.
   * @returns boolean <var>TRUE</var> if there was a SOAP fault during the last
   *     call
   */
  public function IsFault() {
    return isset($this->lastSoapFault);
  }

  /**
   * Returns the SOAP fault message if there was any
   * @return string the fault message if there was any
   */
  public function GetLastFaultMessage() {
    return $this->IsFault()
        ? $this->lastSoapFault->getMessage() : 'null';
  }

  /**
   * Depending on the version of PHP, the xsi:types need to be added and empty
   * tags may need to be removed. The SoapRequestXmlFixer class can facilitate
   * these changes.
   * @param string $request the request to be modified
   * @param array $arguments the arguments passed to the SOAP method
   * @return string the XML request ready to be sent to the server
   * @access protected
   */
  protected function PrepareRequest($request, array $arguments) {
    $addXsiTypes = false;
    $removeEmptyElements = false;
    $replaceReferences = false;
    // Needed for AdWords API XML validation.
    $redeclareXsiTypeNamespaceDefinitions = true;

    if (version_compare(PHP_VERSION, '5.2.0', '<')) {
      trigger_error('The minimum required version of this client library'
          . ' is 5.2.0.', E_USER_ERROR);
    }
    $addXsiTypes = version_compare(PHP_VERSION, '5.2.7', '<');
    $removeEmptyElements = version_compare(PHP_VERSION, '5.2.3', '<');
    $replaceReferences = version_compare(PHP_VERSION, '5.2.2', '>=');

    if ($addXsiTypes || $removeEmptyElements || $replaceReferences
        || $redeclareXsiTypeNamespaceDefinitions) {
      $fixer = new SoapRequestXmlFixer($addXsiTypes, $removeEmptyElements,
          $replaceReferences, $redeclareXsiTypeNamespaceDefinitions);
      return $fixer->FixXml($request, $arguments);
    } else {
      // Empty string is appended to "save" the XML from being deleted.
      return $request . '';
    }
  }

  /**
   * Gets the names of all registered request header elements.
   * @return array the names of the request header elements
   */
  public function GetHeaderNames() {
    return array_keys($this->headers);
  }

  /**
   * Gets the value for a registered request header element.
   * @param string $key the name of the request header element
   * @return string the value of the request header element or <var>NULL</var>
   *     if not found
   */
  public function GetHeaderValue($key) {
    if (array_key_exists($key, $this->headers)) {
      return $this->headers[$key];
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
    $this->headers[$key] = $value;
  }

  /**
   * Generates the SOAP header for the client.
   * @return SoapHeader the instantiated SoapHeader ready to set
   * @access protected
   */
  protected abstract function GenerateSoapHeader();

  /**
   * Creates a SOAP header for the client given the user. It assumes that
   * each element within the header to be filled in is a publicly acessible
   * feild of the SOAP header element.
   * @param string $soapHeaderClassName the class of the SOAP header to
   *     instantiate
   * @param string $soapHeaderElementName the SOAP element name of the header
   * @param string $namespace the namespace of the header
   * @return SoapHeader the wrapped SOAP header ready to be set
   * @access protected
   */
  protected function CreateSoapHeader($soapHeaderClassName,
      $soapHeaderElementName, $headersOverrides) {
    $requestHeader = new $soapHeaderClassName();
    $namespace = $this->serviceNamespace;

    foreach (get_class_vars($soapHeaderClassName) as $classVar => $value) {
      if (isset($headersOverrides)
          && array_key_exists($classVar, $headersOverrides)) {
        $requestHeader->$classVar = $headersOverrides[$classVar];
      } elseif (isset($this->headers)
          && array_key_exists($classVar, $this->headers)) {
        $requestHeader->$classVar = $this->headers[$classVar];
      }
    }

    $soapRequestHeader =
        new SoapVar($requestHeader, SOAP_ENC_OBJECT, $soapHeaderElementName,
            $requestHeader->getNamespace());

    return new SoapHeader($namespace, $soapHeaderElementName,
        $soapRequestHeader, false);
  }

  /**
   * Removes any sensitive information from the request XML. This method is
   * called after the request has been made and before logging any XML.
   * @param string $request the request just made to the server
   * @return string the request with any sensitive information removed ready to
   *     be logged.
   * @access protected
   */
  protected abstract function RemoveSensitiveInfo($request);

  /**
   * Generates the request information using the request and response. This
   * method is called after the request has been made and RemoveSensitiveInfo()
   * has been called as well.
   * @return string the request information ready to be logged
   * @access protected
   * @see RemoveSensitiveInfo
   */
  protected abstract function GenerateRequestInfoMessage();

  /**
   * Gets the DOMDocument representing the last response from this client.
   * @return DOMDocument the DOMDocument representing the last response
   * @throws DOMException if the DOMDocument could not be loaded
   */
  public function GetLastResponseDom() {
    if (!isset($this->lastResponseDom)) {
      $this->lastResponseDom = XmlUtils::GetDomFromXml($this->lastResponse);
    }

    return $this->lastResponseDom;
  }

  /**
   * Get the DOMDocument representing the last request from this client.
   * @return DOMDocument the DOMDocument representing the last request
   * @throws DOMException if the DOMDocument could not be loaded
   */
  public function GetLastRequestDom() {
    if (!isset($this->lastRequestDom)) {
      $this->lastRequestDom = XmlUtils::GetDomFromXml($this->lastRequest);
    }

    return $this->lastRequestDom;
  }
}
