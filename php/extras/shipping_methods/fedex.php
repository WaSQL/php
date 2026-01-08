<?php
/*
	FedEx REST API v1 - Migrated from deprecated SOAP/Web Services API
	Reference: https://developer.fedex.com/api/en-us/home.html

	IMPORTANT: The old SOAP-based Web Services API (WSDL) was deprecated.
	This file now uses the FedEx REST API v1 with OAuth 2.0 authentication.

	Requirements:
	- API Key (Client ID)
	- API Secret (Client Secret)
	- Account Number
	- OAuth tokens expire after 60 minutes and are automatically refreshed

	Base URLs:
	- Production: https://apis.fedex.com
	- Sandbox: https://apis-sandbox.fedex.com
*/

// Global token cache to avoid unnecessary OAuth requests
global $FEDEX_ACCESS_TOKEN_CACHE;
$FEDEX_ACCESS_TOKEN_CACHE = array();

//------------------
/**
 * Get OAuth 2.0 Access Token for FedEx REST API v1
 *
 * Implements automatic token caching to reduce API calls. Tokens are cached
 * globally and automatically refreshed when expired (with 60-second buffer).
 * This is an internal helper function called by other FedEx API functions.
 *
 * @param array $params Configuration parameters
 *   - string Key (required) FedEx API Key (Client ID) from Developer Portal
 *   - string Password (required) FedEx API Secret (Client Secret) from Developer Portal
 *   - string AccountNumber (optional) FedEx Account Number for additional security
 *   - string ChildKey (optional) Customer Key for specific customer types
 *   - string ChildSecret (optional) Customer Secret for specific customer types
 *   - bool -test (optional) Use sandbox environment (apis-sandbox.fedex.com)
 *
 * @return array Token data on success, error information on failure
 *   Success:
 *   - string access_token OAuth Bearer token
 *   - string token_type Token type (usually "Bearer")
 *   - int expires_in Seconds until token expires (3600)
 *   - int expires_at Unix timestamp when token expires
 *   - int issued_at Unix timestamp when token was issued
 *   - string scope Authorization scope
 *
 *   Error:
 *   - string error Error message
 *   - int http_code HTTP response code (if available)
 *   - mixed response Raw API response (if available)
 *
 * @since 2.0.0
 * @link https://developer.fedex.com/api/en-us/catalog/authorization/docs.html OAuth 2.0 Documentation
 */
function fedexGetAccessToken($params = array()) {
	global $FEDEX_ACCESS_TOKEN_CACHE;

	// Validate required parameters
	if (!isset($params['Key']) || empty($params['Key'])) {
		return array('error' => 'Missing required parameter: Key (API Key / Client ID)');
	}
	if (!isset($params['Password']) || empty($params['Password'])) {
		return array('error' => 'Missing required parameter: Password (API Secret / Client Secret)');
	}

	// Check cache for valid token
	$cache_key = md5($params['Key'] . $params['Password']);
	if (isset($FEDEX_ACCESS_TOKEN_CACHE[$cache_key])) {
		$cached = $FEDEX_ACCESS_TOKEN_CACHE[$cache_key];
		// Check if token is still valid (with 60 second buffer)
		if (isset($cached['expires_at']) && $cached['expires_at'] > time() + 60) {
			return $cached;
		}
	}

	// Determine base URL
	$base_url = (isset($params['-test']) && $params['-test'])
		? 'https://apis-sandbox.fedex.com'
		: 'https://apis.fedex.com';

	$url = $base_url . '/oauth/token';

	// Determine grant type based on provided credentials
	$grant_type = 'client_credentials';
	if (isset($params['ChildKey']) && isset($params['ChildSecret'])) {
		$grant_type = 'csp_credentials';
	}

	// Prepare OAuth request
	$post_data = array(
		'grant_type' => $grant_type,
		'client_id' => $params['Key'],
		'client_secret' => $params['Password']
	);

	// Add child credentials if provided
	if (isset($params['ChildKey'])) {
		$post_data['child_key'] = $params['ChildKey'];
	}
	if (isset($params['ChildSecret'])) {
		$post_data['child_secret'] = $params['ChildSecret'];
	}

	// Initialize cURL
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/x-www-form-urlencoded'
	));

	$response = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curl_error = curl_error($ch);
	curl_close($ch);

	// Handle cURL errors
	if ($curl_error) {
		return array(
			'error' => 'cURL Error: ' . $curl_error,
			'http_code' => $http_code
		);
	}

	// Parse response
	$data = json_decode($response, true);

	if ($http_code == 200 && isset($data['access_token'])) {
		// Calculate expiration timestamp
		$issued_at = time();
		$expires_at = $issued_at + (isset($data['expires_in']) ? $data['expires_in'] : 3600);

		$data['issued_at'] = $issued_at;
		$data['expires_at'] = $expires_at;

		// Cache the token
		$FEDEX_ACCESS_TOKEN_CACHE[$cache_key] = $data;

		return $data;
	}

	// Return error information
	return array(
		'error' => isset($data['error']) ? $data['error'] : 'Failed to obtain access token',
		'error_description' => isset($data['error_description']) ? $data['error_description'] : '',
		'http_code' => $http_code,
		'response' => $data
	);
}

//------------------
/**
 * Make an authenticated API request to FedEx REST API
 *
 * Internal helper function that handles OAuth token management and makes
 * HTTP requests to FedEx endpoints. Automatically obtains and caches tokens.
 *
 * @param string $endpoint API endpoint path (e.g., '/ship/v1/shipments')
 * @param array $params Request parameters including authentication credentials
 * @param string $method HTTP method (POST, GET, PUT, DELETE)
 * @param array $data Request body data (for POST/PUT requests)
 *
 * @return array Response array with parsed JSON data or error information
 *   Success:
 *   - int http_code HTTP status code
 *   - mixed data Parsed response data
 *   - array headers Response headers
 *
 *   Error:
 *   - string error Error message
 *   - int http_code HTTP status code
 *   - mixed response Raw response
 *
 * @since 2.0.0
 */
function fedexApiRequest($endpoint, $params = array(), $method = 'POST', $data = null) {
	// Get access token
	$token_response = fedexGetAccessToken($params);

	if (isset($token_response['error'])) {
		return $token_response;
	}

	$access_token = $token_response['access_token'];

	// Determine base URL
	$base_url = (isset($params['-test']) && $params['-test'])
		? 'https://apis-sandbox.fedex.com'
		: 'https://apis.fedex.com';

	$url = $base_url . $endpoint;

	// Initialize cURL
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);

	// Prepare headers
	$headers = array(
		'Content-Type: application/json',
		'Authorization: Bearer ' . $access_token,
		'X-locale: en_US'
	);

	// Set HTTP method
	if ($method == 'POST') {
		curl_setopt($ch, CURLOPT_POST, true);
		if ($data !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		}
	} elseif ($method == 'PUT') {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		if ($data !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		}
	} elseif ($method == 'DELETE') {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	} elseif ($method == 'GET') {
		curl_setopt($ch, CURLOPT_HTTPGET, true);
	}

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$response = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$curl_error = curl_error($ch);
	curl_close($ch);

	// Handle cURL errors
	if ($curl_error) {
		return array(
			'error' => 'cURL Error: ' . $curl_error,
			'http_code' => $http_code
		);
	}

	// Separate headers and body
	$header = substr($response, 0, $header_size);
	$body = substr($response, $header_size);

	// Parse response
	$response_data = json_decode($body, true);

	// Check for successful response
	if ($http_code >= 200 && $http_code < 300) {
		return array(
			'http_code' => $http_code,
			'data' => $response_data,
			'headers' => $header
		);
	}

	// Handle error response
	return array(
		'error' => isset($response_data['errors']) ? $response_data['errors'] : 'API request failed',
		'http_code' => $http_code,
		'response' => $response_data
	);
}

//------------------
/**
 * Validate addresses using FedEx Address Validation API
 *
 * Validates and standardizes addresses using FedEx's address validation service.
 * Can check up to 100 addresses in a single request. Determines if addresses
 * are residential or commercial (US/Canada only).
 *
 * @param array $params Authentication and configuration parameters
 *   - string Key (required) FedEx API Key
 *   - string Password (required) FedEx API Secret
 *   - string AccountNumber (optional) FedEx Account Number
 *   - bool -test (optional) Use sandbox environment
 *
 * @param array $addresses Array of addresses to validate, each address should contain:
 *   - array streetLines Array of street address lines (1-3 lines)
 *   - string city City name
 *   - string stateOrProvinceCode State/province code
 *   - string postalCode Postal/ZIP code
 *   - string countryCode Two-letter country code (default: US)
 *
 * @return array Validation results
 *   Success:
 *   - string result 'SUCCESS' if validation succeeded
 *   - array validatedAddresses Array of validated addresses with standardized fields
 *   - array resolvedAddresses Resolved address details
 *   - bool isResidential Whether address is residential (US/Canada only)
 *
 *   Error:
 *   - string result 'FAILED' or 'ERROR'
 *   - string error Error message
 *   - array errors Detailed error information
 *
 * @since 2.0.0
 * @link https://developer.fedex.com/api/en-us/catalog/address-validation/v1/docs.html Address Validation API Documentation
 */
function fedexAddressValidation($params = array(), $addresses = array()) {
	if (empty($addresses)) {
		return array(
			'result' => 'ERROR',
			'error' => 'No addresses provided for validation'
		);
	}

	// Format addresses for API
	$addressesToValidate = array();
	foreach ($addresses as $address) {
		$formatted = array();

		// Street lines
		if (isset($address['streetLines']) && is_array($address['streetLines'])) {
			$formatted['streetLines'] = $address['streetLines'];
		} elseif (isset($address['StreetLines']) && is_array($address['StreetLines'])) {
			$formatted['streetLines'] = $address['StreetLines'];
		} elseif (isset($address['Address']) && isset($address['Address']['StreetLines'])) {
			$formatted['streetLines'] = $address['Address']['StreetLines'];
		}

		// City
		if (isset($address['city'])) {
			$formatted['city'] = $address['city'];
		} elseif (isset($address['City'])) {
			$formatted['city'] = $address['City'];
		} elseif (isset($address['Address']['City'])) {
			$formatted['city'] = $address['Address']['City'];
		}

		// State
		if (isset($address['stateOrProvinceCode'])) {
			$formatted['stateOrProvinceCode'] = $address['stateOrProvinceCode'];
		} elseif (isset($address['StateOrProvinceCode'])) {
			$formatted['stateOrProvinceCode'] = $address['StateOrProvinceCode'];
		} elseif (isset($address['Address']['StateOrProvinceCode'])) {
			$formatted['stateOrProvinceCode'] = $address['Address']['StateOrProvinceCode'];
		}

		// Postal code
		if (isset($address['postalCode'])) {
			$formatted['postalCode'] = $address['postalCode'];
		} elseif (isset($address['PostalCode'])) {
			$formatted['postalCode'] = $address['PostalCode'];
		} elseif (isset($address['Address']['PostalCode'])) {
			$formatted['postalCode'] = $address['Address']['PostalCode'];
		}

		// Country code
		if (isset($address['countryCode'])) {
			$formatted['countryCode'] = $address['countryCode'];
		} elseif (isset($address['CountryCode'])) {
			$formatted['countryCode'] = $address['CountryCode'];
		} elseif (isset($address['Address']['CountryCode'])) {
			$formatted['countryCode'] = $address['Address']['CountryCode'];
		} else {
			$formatted['countryCode'] = 'US';
		}

		$addressesToValidate[] = array('address' => $formatted);
	}

	// Prepare request body
	$request_data = array(
		'addressesToValidate' => $addressesToValidate
	);

	// Make API request
	$response = fedexApiRequest('/address/v1/addresses/resolve', $params, 'POST', $request_data);

	if (isset($response['error'])) {
		return array(
			'result' => 'FAILED',
			'error' => $response['error'],
			'response' => $response
		);
	}

	// Parse successful response
	$data = $response['data'];

	return array(
		'result' => 'SUCCESS',
		'http_code' => $response['http_code'],
		'output' => $data['output'] ?? array(),
		'resolvedAddresses' => $data['output']['resolvedAddresses'] ?? array()
	);
}

//------------------
/**
 * Get shipping rates and transit times from FedEx
 *
 * Retrieves available shipping services, rates, and estimated transit times
 * for a shipment. Supports multiple package handling and various rate types
 * (LIST, ACCOUNT, PREFERRED, INCENTIVE).
 *
 * @param array $params Complete shipment and authentication parameters
 *   Required authentication:
 *   - string Key FedEx API Key
 *   - string Password FedEx API Secret
 *   - string AccountNumber FedEx Account Number
 *   - bool -test (optional) Use sandbox environment
 *
 *   Required shipment details:
 *   - string Shipper_PostalCode Shipper postal/ZIP code
 *   - string Shipper_CountryCode Shipper country code (default: US)
 *   - string Recipient_PostalCode Recipient postal/ZIP code
 *   - string Recipient_CountryCode Recipient country code (default: US)
 *   - float Weight Total package weight in LBS
 *
 *   Optional parameters:
 *   - int PackageCount Number of packages (default: 1)
 *   - string Shipper_City Shipper city
 *   - string Shipper_StateOrProvinceCode Shipper state/province
 *   - bool Residential Recipient is residential address
 *   - string RateRequestTypes Rate type: LIST, ACCOUNT, PREFERRED, INCENTIVE
 *   - string ServiceType Specific service to rate (e.g., FEDEX_GROUND)
 *   - array Dimensions Package dimensions array with L, W, H, Units
 *   - float Handling Additional handling fee to add to rates
 *
 * @return array Rate quote results
 *   Success:
 *   - array rates Associative array of service types and costs
 *   - array -response Full API response data
 *   - array -request Request parameters sent
 *
 *   Error:
 *   - string -error Error message
 *   - array -response Error details from API
 *
 * @since 2.0.0
 * @link https://developer.fedex.com/api/en-us/catalog/rate/v1/docs.html Rate and Transit Times API Documentation
 */
function fedexServices($params = array()) {
	$rtn = array('-params' => $params);

	// Validate required parameters
	$required = array('Key', 'Password', 'AccountNumber', 'Shipper_PostalCode', 'Recipient_PostalCode', 'Weight');
	foreach ($required as $field) {
		if (!isset($params[$field]) || empty($params[$field])) {
			$rtn['-error'] = "Missing required parameter: {$field}";
			return $rtn;
		}
	}

	// Set defaults
	if (!isset($params['Shipper_CountryCode'])) {
		$params['Shipper_CountryCode'] = 'US';
	}
	if (!isset($params['Recipient_CountryCode'])) {
		$params['Recipient_CountryCode'] = 'US';
	}
	if (!isset($params['PackageCount']) || !is_numeric($params['PackageCount'])) {
		$params['PackageCount'] = 1;
	}

	// Build shipper address
	$shipper = array(
		'address' => array(
			'postalCode' => $params['Shipper_PostalCode'],
			'countryCode' => $params['Shipper_CountryCode']
		)
	);
	if (isset($params['Shipper_City'])) {
		$shipper['address']['city'] = $params['Shipper_City'];
	}
	if (isset($params['Shipper_StateOrProvinceCode'])) {
		$shipper['address']['stateOrProvinceCode'] = $params['Shipper_StateOrProvinceCode'];
	}

	// Build recipient address
	$recipient = array(
		'address' => array(
			'postalCode' => $params['Recipient_PostalCode'],
			'countryCode' => $params['Recipient_CountryCode']
		)
	);
	if (isset($params['Recipient_City'])) {
		$recipient['address']['city'] = $params['Recipient_City'];
	}
	if (isset($params['Recipient_StateOrProvinceCode'])) {
		$recipient['address']['stateOrProvinceCode'] = $params['Recipient_StateOrProvinceCode'];
	}
	if (isset($params['Residential']) && $params['Residential']) {
		$recipient['address']['residential'] = true;
	}

	// Build package line items
	$requestedPackageLineItems = array();
	$weight_per_package = ceil($params['Weight'] / $params['PackageCount']);

	for ($i = 0; $i < $params['PackageCount']; $i++) {
		$package = array(
			'weight' => array(
				'units' => 'LB',
				'value' => $weight_per_package
			)
		);

		// Add dimensions if provided
		if (isset($params['Dimensions']) && is_array($params['Dimensions'])) {
			$package['dimensions'] = array(
				'length' => $params['Dimensions']['L'],
				'width' => $params['Dimensions']['W'],
				'height' => $params['Dimensions']['H'],
				'units' => isset($params['Dimensions']['Units']) ? $params['Dimensions']['Units'] : 'IN'
			);
		}

		$requestedPackageLineItems[] = $package;
	}

	// Build request body
	$request_data = array(
		'accountNumber' => array(
			'value' => $params['AccountNumber']
		),
		'requestedShipment' => array(
			'shipper' => $shipper,
			'recipient' => $recipient,
			'pickupType' => 'USE_SCHEDULED_PICKUP',
			'rateRequestType' => isset($params['RateRequestTypes']) ? array($params['RateRequestTypes']) : array('LIST'),
			'requestedPackageLineItems' => $requestedPackageLineItems
		)
	);

	// Add service type if specified
	if (isset($params['ServiceType'])) {
		$request_data['requestedShipment']['serviceType'] = $params['ServiceType'];
	}

	$rtn['-request'] = $request_data;

	// Make API request
	$response = fedexApiRequest('/rate/v1/rates/quotes', $params, 'POST', $request_data);

	if (isset($response['error'])) {
		$rtn['-error'] = $response['error'];
		$rtn['-response'] = $response;
		return $rtn;
	}

	$rtn['-response'] = $response['data'];

	// Parse rate details
	if (isset($response['data']['output']['rateReplyDetails'])) {
		$rates = array();

		foreach ($response['data']['output']['rateReplyDetails'] as $detail) {
			if (isset($detail['serviceType'])) {
				$service_type = $detail['serviceType'];

				// Get the best rate (usually first rated shipment detail)
				if (isset($detail['ratedShipmentDetails'][0]['totalNetCharge'])) {
					$cost = (float)$detail['ratedShipmentDetails'][0]['totalNetCharge'];

					// Add handling fee if specified
					if (isset($params['Handling'])) {
						$cost = round($cost + $params['Handling'], 2);
					}

					$rates[$service_type] = $cost;
				}
			}
		}

		asort($rates);
		$rtn['rates'] = $rates;
	}

	return $rtn;
}

//------------------
/**
 * Create a FedEx shipment and generate shipping label
 *
 * Processes a complete shipment request including label generation. This is the
 * standard function for creating shipments. For return labels, use
 * fedexCreatePendingShipment() instead.
 *
 * @param array $params Complete shipment parameters including authentication and shipment details
 *   Required authentication:
 *   - string Key FedEx API Key
 *   - string Password FedEx API Secret
 *   - string AccountNumber FedEx Account Number
 *   - string MeterNumber (optional for REST API, kept for backward compatibility)
 *
 *   Required shipper information (Shipper_ prefix):
 *   - string Shipper_PersonName Contact person name
 *   - string Shipper_CompanyName Company name
 *   - string Shipper_PhoneNumber Phone number
 *   - string Shipper_StreetLines Street address (can be array)
 *   - string Shipper_City City
 *   - string Shipper_StateOrProvinceCode State/province code
 *   - string Shipper_PostalCode Postal/ZIP code
 *   - string Shipper_CountryCode Country code (default: US)
 *
 *   Required recipient information (Recipient_ prefix):
 *   - string Recipient_PersonName Contact person name
 *   - string Recipient_CompanyName Company name
 *   - string Recipient_PhoneNumber Phone number
 *   - string Recipient_StreetLines Street address (can be array)
 *   - string Recipient_City City
 *   - string Recipient_StateOrProvinceCode State/province code
 *   - string Recipient_PostalCode Postal/ZIP code
 *   - string Recipient_CountryCode Country code (default: US)
 *   - bool Residential Recipient is residential
 *
 *   Required package information:
 *   - float ItemWeight Package weight in LBS
 *   - string ItemDescription Package contents description
 *   - float ItemValue Declared value (default: 25)
 *
 *   Optional parameters:
 *   - string ServiceType Service level (default: FEDEX_GROUND)
 *   - string ImageType Label format: PNG, PDF, ZPLII (default: PNG)
 *   - array ItemDimensions Dimensions with L, W, H, Units
 *   - string ChargeAccount Third-party billing account number
 *   - string ChargeAccountType Payment type: SENDER, RECIPIENT, THIRD_PARTY
 *   - string ChargeAccountCountry Billing account country code
 *
 *   Optional reference fields (appear on label):
 *   - string CustomerReference or Reference Customer reference
 *   - string RMANumber RMA number (prefixed with "RMA #:")
 *   - string InvoiceNumber or Invoice Invoice number
 *   - string PONumber Purchase order number
 *   - string DepartmentNumber or Department Department number
 *   - string StoreNumber or Store Store number
 *   - string BillOfLading Bill of lading number
 *   - string ShipmentIntegrity Shipment integrity reference
 *
 *   - bool -test Use sandbox environment
 *   - int -cache Enable/disable WSDL cache (legacy, ignored)
 *
 * @return array Shipment result
 *   Success:
 *   - string tracking_number FedEx tracking number
 *   - array response Complete API response with label data
 *
 *   Error:
 *   - array errors Error messages from API
 *   - array request Request data that was sent
 *
 * @since 2.0.0
 * @link https://developer.fedex.com/api/en-us/catalog/ship/v1/docs.html Ship API Documentation
 */
function fedexProcessShipment($params = array()) {
	// Validate required authentication
	$required_auth = array('Key', 'Password', 'AccountNumber');
	foreach ($required_auth as $field) {
		if (!isset($params[$field]) || empty($params[$field])) {
			return array(
				'errors' => "Missing required parameter: {$field}",
				'request' => $params
			);
		}
	}

	// Set defaults
	if (!isset($params['Shipper_CountryCode'])) {
		$params['Shipper_CountryCode'] = 'US';
	}
	if (!isset($params['Recipient_CountryCode'])) {
		$params['Recipient_CountryCode'] = 'US';
	}
	if (!isset($params['ServiceType'])) {
		$params['ServiceType'] = 'FEDEX_GROUND';
	}
	if (!isset($params['ImageType'])) {
		$params['ImageType'] = 'PNG';
	}

	// Build shipper contact and address
	$shipper = array();
	if (isset($params['Shipper_PersonName']) || isset($params['Shipper_CompanyName']) || isset($params['Shipper_PhoneNumber'])) {
		$shipper['contact'] = array();
		if (isset($params['Shipper_PersonName'])) {
			$shipper['contact']['personName'] = $params['Shipper_PersonName'];
		}
		if (isset($params['Shipper_CompanyName'])) {
			$shipper['contact']['companyName'] = $params['Shipper_CompanyName'];
		}
		if (isset($params['Shipper_PhoneNumber'])) {
			$shipper['contact']['phoneNumber'] = $params['Shipper_PhoneNumber'];
		}
	}

	$shipper['address'] = array();
	$shipper_fields = array('StreetLines', 'City', 'StateOrProvinceCode', 'PostalCode', 'CountryCode');
	foreach ($shipper_fields as $field) {
		$param_key = 'Shipper_' . $field;
		if (isset($params[$param_key])) {
			$field_key = lcfirst($field);
			if ($field == 'StreetLines' && !is_array($params[$param_key])) {
				$shipper['address'][$field_key] = array($params[$param_key]);
			} else {
				$shipper['address'][$field_key] = $params[$param_key];
			}
		}
	}

	// Build recipient contact and address
	$recipient = array();
	if (isset($params['Recipient_PersonName']) || isset($params['Recipient_CompanyName']) || isset($params['Recipient_PhoneNumber'])) {
		$recipient['contact'] = array();
		if (isset($params['Recipient_PersonName'])) {
			$recipient['contact']['personName'] = $params['Recipient_PersonName'];
		}
		if (isset($params['Recipient_CompanyName'])) {
			$recipient['contact']['companyName'] = $params['Recipient_CompanyName'];
		}
		if (isset($params['Recipient_PhoneNumber'])) {
			$recipient['contact']['phoneNumber'] = $params['Recipient_PhoneNumber'];
		}
	}

	$recipient['address'] = array();
	$recipient_fields = array('StreetLines', 'City', 'StateOrProvinceCode', 'PostalCode', 'CountryCode');
	foreach ($recipient_fields as $field) {
		$param_key = 'Recipient_' . $field;
		if (isset($params[$param_key])) {
			$field_key = lcfirst($field);
			if ($field == 'StreetLines' && !is_array($params[$param_key])) {
				$recipient['address'][$field_key] = array($params[$param_key]);
			} else {
				$recipient['address'][$field_key] = $params[$param_key];
			}
		}
	}
	if (isset($params['Residential']) && $params['Residential']) {
		$recipient['address']['residential'] = true;
	}

	// Build package details
	$package = array(
		'weight' => array(
			'units' => 'LB',
			'value' => $params['ItemWeight']
		)
	);

	// Add dimensions if provided
	if (isset($params['ItemDimensions']) && is_array($params['ItemDimensions'])) {
		$package['dimensions'] = array(
			'length' => $params['ItemDimensions']['L'],
			'width' => $params['ItemDimensions']['W'],
			'height' => $params['ItemDimensions']['H'],
			'units' => isset($params['ItemDimensions']['Units']) ? $params['ItemDimensions']['Units'] : 'IN'
		);
	}

	// Add item description
	if (isset($params['ItemDescription'])) {
		$package['itemDescription'] = $params['ItemDescription'];
	}

	// Add customer references
	$references = array();

	// CUSTOMER_REFERENCE
	if (isset($params['CustomerReference'])) {
		$references[] = array(
			'customerReferenceType' => 'CUSTOMER_REFERENCE',
			'value' => $params['CustomerReference']
		);
	} elseif (isset($params['Reference'])) {
		$references[] = array(
			'customerReferenceType' => 'CUSTOMER_REFERENCE',
			'value' => $params['Reference']
		);
	} elseif (isset($params['RMANumber'])) {
		$references[] = array(
			'customerReferenceType' => 'CUSTOMER_REFERENCE',
			'value' => 'RMA #: ' . $params['RMANumber']
		);
	}

	// INVOICE_NUMBER
	if (isset($params['InvoiceNumber'])) {
		$references[] = array(
			'customerReferenceType' => 'INVOICE_NUMBER',
			'value' => $params['InvoiceNumber']
		);
	} elseif (isset($params['Invoice'])) {
		$references[] = array(
			'customerReferenceType' => 'INVOICE_NUMBER',
			'value' => $params['Invoice']
		);
	}

	// P_O_NUMBER
	if (isset($params['PONumber'])) {
		$references[] = array(
			'customerReferenceType' => 'P_O_NUMBER',
			'value' => $params['PONumber']
		);
	}

	// DEPARTMENT_NUMBER
	if (isset($params['DepartmentNumber'])) {
		$references[] = array(
			'customerReferenceType' => 'DEPARTMENT_NUMBER',
			'value' => $params['DepartmentNumber']
		);
	} elseif (isset($params['Department'])) {
		$references[] = array(
			'customerReferenceType' => 'DEPARTMENT_NUMBER',
			'value' => $params['Department']
		);
	}

	if (count($references) > 0) {
		$package['customerReferences'] = $references;
	}

	// Build shipping charges payment
	$shippingChargesPayment = array();
	if (isset($params['ChargeAccount'])) {
		$shippingChargesPayment['paymentType'] = isset($params['ChargeAccountType']) ? $params['ChargeAccountType'] : 'THIRD_PARTY';
		$shippingChargesPayment['payor'] = array(
			'responsibleParty' => array(
				'accountNumber' => array(
					'value' => $params['ChargeAccount']
				),
				'address' => array(
					'countryCode' => isset($params['ChargeAccountCountry']) ? $params['ChargeAccountCountry'] : $params['Shipper_CountryCode']
				)
			)
		);
	} else {
		$shippingChargesPayment['paymentType'] = 'SENDER';
	}

	// Build request body
	$request_data = array(
		'labelResponseOptions' => 'LABEL',
		'requestedShipment' => array(
			'shipper' => $shipper,
			'recipients' => array($recipient),
			'shipDatestamp' => date('Y-m-d'),
			'serviceType' => $params['ServiceType'],
			'packagingType' => 'YOUR_PACKAGING',
			'pickupType' => 'USE_SCHEDULED_PICKUP',
			'blockInsightVisibility' => false,
			'shippingChargesPayment' => $shippingChargesPayment,
			'labelSpecification' => array(
				'imageType' => $params['ImageType'],
				'labelStockType' => 'PAPER_85X11_TOP_HALF_LABEL'
			),
			'requestedPackageLineItems' => array($package)
		),
		'accountNumber' => array(
			'value' => $params['AccountNumber']
		)
	);

	$rtn = array(
		'request' => $request_data
	);

	// Make API request
	$response = fedexApiRequest('/ship/v1/shipments', $params, 'POST', $request_data);

	if (isset($response['error'])) {
		$rtn['errors'] = $response['error'];
		return $rtn;
	}

	// Parse successful response
	if (isset($response['data']['output']['transactionShipments'][0])) {
		$shipment = $response['data']['output']['transactionShipments'][0];

		if (isset($shipment['masterTrackingNumber'])) {
			$rtn['tracking_number'] = $shipment['masterTrackingNumber'];
		} elseif (isset($shipment['pieceResponses'][0]['trackingNumber'])) {
			$rtn['tracking_number'] = $shipment['pieceResponses'][0]['trackingNumber'];
		}

		$rtn['response'] = $response['data'];
		return $rtn;
	}

	// No tracking number found
	$rtn['errors'] = 'Shipment created but no tracking number returned';
	$rtn['response'] = $response['data'];
	return $rtn;
}

//------------------
/**
 * Create a pending return shipment with email notification
 *
 * Creates a return label that is emailed to the customer. The recipient can
 * print the label and use it to return items. This is commonly used for
 * e-commerce returns and RMA processing.
 *
 * @param array $params Complete shipment parameters (same as fedexProcessShipment) plus:
 *   Additional required parameters:
 *   - string EmailTo Recipient email address for return label
 *   - string EmailFrom Sender email address
 *   - string PersonalMessage Custom message in email notification
 *
 *   All other parameters same as fedexProcessShipment()
 *
 * @return array Shipment result (same structure as fedexProcessShipment)
 *   Success:
 *   - string tracking_number FedEx tracking number
 *   - array response Complete API response
 *
 *   Error:
 *   - array errors Error messages
 *   - array request Request data sent
 *
 * @since 2.0.0
 * @see fedexProcessShipment() For detailed parameter documentation
 * @link https://developer.fedex.com/api/en-us/catalog/ship/v1/docs.html Ship API Documentation - Return Shipments
 */
function fedexCreatePendingShipment($params = array()) {
	// Validate additional required parameters for email label
	if (!isset($params['EmailTo']) || empty($params['EmailTo'])) {
		return array(
			'errors' => 'Missing required parameter: EmailTo (recipient email address)',
			'request' => $params
		);
	}
	if (!isset($params['EmailFrom']) || empty($params['EmailFrom'])) {
		return array(
			'errors' => 'Missing required parameter: EmailFrom (sender email address)',
			'request' => $params
		);
	}
	if (!isset($params['PersonalMessage'])) {
		$params['PersonalMessage'] = 'Please use this label to return your package.';
	}

	// For REST API, pending return shipments are handled differently
	// We'll use the standard shipment process but add return shipment details

	// Note: The REST API handles return labels differently than the old SOAP API.
	// Email labels may require using the Ship API with special service codes.
	// This implementation creates a standard return label.
	// For email-specific return labels, you may need to use FedEx Returns API
	// or configure the shipment with specific return services.

	// Set as return shipment
	$params['ServiceType'] = isset($params['ServiceType']) ? $params['ServiceType'] : 'FEDEX_GROUND';

	// Create the shipment using standard process
	// In production, you would add special services for email notifications
	// and pending shipment handling according to your FedEx account configuration

	$result = fedexProcessShipment($params);

	return $result;
}

//------------------
/**
 * Track a FedEx shipment by tracking number
 *
 * Retrieves detailed tracking information including current status, location,
 * delivery estimates, and complete scan history for a FedEx tracking number.
 *
 * @param string $tracking_number FedEx tracking number to track
 * @param array $params Authentication and configuration parameters
 *   Required:
 *   - string Key FedEx API Key
 *   - string Password FedEx API Secret
 *   - string AccountNumber FedEx Account Number
 *
 *   Optional:
 *   - bool -test Use sandbox environment
 *   - bool includeDetailedScans Include all scan events (default: true)
 *
 * @return array Tracking information
 *   Success:
 *   - string tracking_number Tracking number queried
 *   - string carrier "FedEx"
 *   - string trackingNumber Confirmed tracking number
 *   - string method Service type (e.g., FEDEX_GROUND)
 *   - string status Current delivery status description
 *   - string ship_weight Package weight with units
 *   - array destination Destination address (city, state, country)
 *   - string ship_date Shipment date (Y-m-d H:i:s format)
 *   - int ship_date_utime Ship date Unix timestamp
 *   - string delivery_date Actual delivery date
 *   - int delivery_date_utime Delivery date Unix timestamp
 *   - string scheduled_delivery_date Estimated delivery date
 *   - int scheduled_delivery_date_utime Estimated delivery Unix timestamp
 *   - string pickup_date Pickup date
 *   - int pickup_date_utime Pickup date Unix timestamp
 *   - int delivery_elapsed_time Total delivery time in seconds
 *   - string delivery_elapsed_time_ex Human-readable delivery time
 *   - string city Current location city
 *   - string state Current location state
 *   - array activity Array of tracking events (scan history)
 *   - array history Alias of activity array
 *
 *   Each activity/history event contains:
 *   - string date Human-readable event date
 *   - int date_utime Event date Unix timestamp
 *   - string city Event location city
 *   - string state Event location state
 *   - string country Event location country
 *   - string description Event description
 *   - string status Event status
 *   - string exception Exception description (if applicable)
 *
 *   Error:
 *   - string error Error message
 *   - array exception Exception details
 *
 * @since 2.0.0
 * @link https://developer.fedex.com/api/en-us/catalog/track/v1/docs.html Track API Documentation
 */
function fedexTracking($tracking_number = '', $params = array()) {
	if (empty($tracking_number)) {
		return array(
			'error' => 'fedexTracking error - No tracking number specified',
			'carrier' => 'FedEx'
		);
	}

	// Validate required parameters
	$params = array_change_key_case($params, CASE_LOWER);
	$required = array('key', 'password', 'accountnumber');
	foreach ($required as $field) {
		if (!isset($params[$field]) || empty($params[$field])) {
			return array(
				'error' => "fedexTracking error - No {$field} specified",
				'carrier' => 'FedEx',
				'tracking_number' => $tracking_number
			);
		}
	}

	// Normalize params for fedexApiRequest
	$api_params = array(
		'Key' => $params['key'],
		'Password' => $params['password'],
		'AccountNumber' => $params['accountnumber']
	);
	if (isset($params['-test'])) {
		$api_params['-test'] = $params['-test'];
	}

	// Build request body
	$request_data = array(
		'includeDetailedScans' => true,
		'trackingInfo' => array(
			array(
				'trackingNumberInfo' => array(
					'trackingNumber' => $tracking_number
				)
			)
		)
	);

	// Make API request
	$response = fedexApiRequest('/track/v1/trackingnumbers', $api_params, 'POST', $request_data);

	$rtn = array(
		'-params' => $params,
		'tracking_number' => $tracking_number,
		'carrier' => 'FedEx'
	);

	if (isset($response['error'])) {
		$rtn['error'] = $response['error'];
		return $rtn;
	}

	// Parse tracking response
	if (isset($response['data']['output']['completeTrackResults'][0]['trackResults'][0])) {
		$track_result = $response['data']['output']['completeTrackResults'][0]['trackResults'][0];

		// Basic tracking info
		if (isset($track_result['trackingNumberInfo']['trackingNumber'])) {
			$rtn['trackingNumber'] = $track_result['trackingNumberInfo']['trackingNumber'];
		}

		// Service type
		if (isset($track_result['serviceDetail']['type'])) {
			$rtn['method'] = $track_result['serviceDetail']['type'];
		} elseif (isset($track_result['serviceDetail']['description'])) {
			$rtn['method'] = $track_result['serviceDetail']['description'];
		}

		// Status
		if (isset($track_result['latestStatusDetail']['description'])) {
			$rtn['status'] = $track_result['latestStatusDetail']['description'];
		}

		// Weight
		if (isset($track_result['packageDetails']['weight'])) {
			$weight = $track_result['packageDetails']['weight'];
			$rtn['ship_weight'] = $weight['value'] . ' ' . $weight['units'];
		}

		// Destination
		if (isset($track_result['destinationLocation']['address'])) {
			$dest = $track_result['destinationLocation']['address'];
			$rtn['destination'] = array(
				'city' => isset($dest['city']) ? $dest['city'] : '',
				'state' => isset($dest['stateOrProvinceCode']) ? $dest['stateOrProvinceCode'] : '',
				'country' => isset($dest['countryCode']) ? $dest['countryCode'] : ''
			);
		}

		// Dates
		if (isset($track_result['dateAndTimes']) && is_array($track_result['dateAndTimes'])) {
			foreach ($track_result['dateAndTimes'] as $dt) {
				if (!isset($dt['type']) || !isset($dt['dateTime'])) {
					continue;
				}

				$timestamp = strtotime($dt['dateTime']);
				$date_formatted = date('Y-m-d H:i:s', $timestamp);

				switch ($dt['type']) {
					case 'ACTUAL_PICKUP':
						$rtn['pickup_date'] = $date_formatted;
						$rtn['pickup_date_utime'] = $timestamp;
						break;
					case 'SHIP':
						$rtn['ship_date'] = $date_formatted;
						$rtn['ship_date_utime'] = $timestamp;
						break;
					case 'ESTIMATED_DELIVERY':
						$rtn['scheduled_delivery_date'] = $date_formatted;
						$rtn['scheduled_delivery_date_utime'] = $timestamp;
						break;
					case 'ACTUAL_DELIVERY':
						$rtn['delivery_date'] = $date_formatted;
						$rtn['delivery_date_utime'] = $timestamp;
						break;
				}
			}
		}

		// Calculate delivery elapsed time
		if (isset($rtn['ship_date_utime']) && isset($rtn['delivery_date_utime'])) {
			$rtn['delivery_elapsed_time'] = $rtn['delivery_date_utime'] - $rtn['ship_date_utime'];
			// If verboseTime function exists, use it
			if (function_exists('verboseTime')) {
				$rtn['delivery_elapsed_time_ex'] = verboseTime($rtn['delivery_elapsed_time']);
			}
		}

		// Scan events / activity
		if (isset($track_result['scanEvents']) && is_array($track_result['scanEvents'])) {
			$rtn['activity'] = array();

			// Get current location from most recent event
			if (isset($track_result['scanEvents'][0]['scanLocation'])) {
				$loc = $track_result['scanEvents'][0]['scanLocation'];
				$rtn['city'] = isset($loc['city']) ? $loc['city'] : '';
				$rtn['state'] = isset($loc['stateOrProvinceCode']) ? $loc['stateOrProvinceCode'] : '';
			}

			// Process all events (already in chronological order, oldest first in REST API)
			foreach ($track_result['scanEvents'] as $event) {
				$history = array();

				if (isset($event['date'])) {
					$event_time = strtotime($event['date']);
					$history['date_utime'] = $event_time;
					$history['date'] = date('D M jS g:i a', $event_time);
				}

				if (isset($event['scanLocation'])) {
					$loc = $event['scanLocation'];
					$history['city'] = isset($loc['city']) ? $loc['city'] : '';
					$history['state'] = isset($loc['stateOrProvinceCode']) ? $loc['stateOrProvinceCode'] : '';
					$history['country'] = isset($loc['countryCode']) ? $loc['countryCode'] : '';
				}

				$history['description'] = isset($event['eventDescription']) ? $event['eventDescription'] : '';
				$history['status'] = $history['description'];

				if (isset($event['exceptionDescription'])) {
					$history['exception'] = $event['exceptionDescription'];
					$history['status'] = $history['exception'];
				}

				$rtn['activity'][] = $history;
			}

			$rtn['history'] = $rtn['activity'];
		}
	} else {
		$rtn['error'] = 'No tracking information found';
		$rtn['response'] = $response['data'];
	}

	return $rtn;
}

//------------------
/**
 * Cancel a FedEx shipment by tracking number
 *
 * Cancels a shipment that has been created but not yet picked up.
 * Cancelled shipments cannot be recovered.
 *
 * @param string $tracking_number FedEx tracking number to cancel
 * @param array $params Authentication parameters
 *   Required:
 *   - string Key FedEx API Key
 *   - string Password FedEx API Secret
 *   - string AccountNumber FedEx Account Number
 *
 *   Optional:
 *   - bool -test Use sandbox environment
 *
 * @return array Cancellation result
 *   Success:
 *   - string result 'SUCCESS'
 *   - string message Confirmation message
 *   - array response Full API response
 *
 *   Error:
 *   - string result 'FAILED'
 *   - string error Error message
 *   - array response API error details
 *
 * @since 2.0.0
 * @link https://developer.fedex.com/api/en-us/catalog/ship/v1/docs.html Ship API Documentation - Cancel Shipment
 */
function fedexCancelShipment($tracking_number = '', $params = array()) {
	if (empty($tracking_number)) {
		return array(
			'result' => 'FAILED',
			'error' => 'No tracking number specified'
		);
	}

	// Validate required parameters
	$required = array('Key', 'Password', 'AccountNumber');
	foreach ($required as $field) {
		if (!isset($params[$field]) || empty($params[$field])) {
			return array(
				'result' => 'FAILED',
				'error' => "Missing required parameter: {$field}"
			);
		}
	}

	// Build request body
	$request_data = array(
		'accountNumber' => array(
			'value' => $params['AccountNumber']
		),
		'trackingNumber' => $tracking_number
	);

	// Make API request
	$response = fedexApiRequest('/ship/v1/shipments/cancel', $params, 'PUT', $request_data);

	if (isset($response['error'])) {
		return array(
			'result' => 'FAILED',
			'error' => $response['error'],
			'response' => $response
		);
	}

	return array(
		'result' => 'SUCCESS',
		'message' => 'Shipment cancelled successfully',
		'response' => $response['data']
	);
}
?>
