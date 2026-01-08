<?php
$progpath=dirname(__FILE__);
/**
 * UPS API Integration Library
 *
 * This library provides integration with UPS APIs using OAuth 2.0 authentication
 * as required since June 2024. All functions use the latest REST/JSON APIs.
 *
 * @version 2.0.0
 * @link https://developer.ups.com/api/reference UPS Developer Portal
 *
 * Service Codes (service_code):
 *   01 - UPS Next Day Air
 *   02 - UPS Second Day Air
 *   03 - UPS Ground (default)
 *   07 - UPS Worldwide Express
 *   08 - UPS Worldwide Expedited
 *   11 - UPS Standard
 *   12 - UPS Three-Day Select
 *   13 - UPS Next Day Air Saver
 *   14 - UPS Next Day Air Early AM
 *   54 - UPS Worldwide Express Plus
 *   59 - UPS Second Day Air AM
 *   65 - UPS Saver
 *
 * Pickup Types (pickup_type):
 *   01 - Daily Pickup (Default)
 *   03 - Customer Counter
 *   06 - One Time Pickup
 *   07 - On Call Air
 *   11 - Authorized Shipping Outlet
 *   19 - Letter Center
 *   20 - Air Service Center
 *
 * Package Type Codes (package_type):
 *   00 - Unknown
 *   01 - UPS Letter
 *   02 - Your Packaging (Default)
 *   03 - UPS Tube
 *   04 - UPS Pak
 *   21 - UPS Express Box
 *
 * Request Options (request_option):
 *   Rate - Get rate for specific service
 *   Shop - Returns rates for all valid UPS products (default)
 *
 * UPS Tracking Number Format:
 *   - First 2 characters: "1Z"
 *   - Next 6 characters: UPS account number
 *   - Next 2 characters: Service code
 *   - Next 5 characters: Invoice number
 *   - Next 2 digits: Package number (zero-filled, e.g., "01", "02")
 *   - Last character: Check digit
 *
 * Test Tracking Numbers:
 *   1Z12345E0205271688 - 2nd Day Air, Delivered (Signature Availability)
 *   1Z12345E6605272234 - World Wide Express, Delivered
 *   1Z12345E0305271640 - Ground, Delivered (Second Package: 1Z12345E0393657226)
 *   1Z12345E1305277940 - Next Day Air Saver, ORIGIN SCAN
 *   1Z12345E6205277936 - Day Air Saver, 2nd Delivery attempt
 *   1Z12345E020527079 - Invalid Tracking Number
 *   1Z12345E1505270452 - No Tracking Information Available
 *   990728071 - UPS Freight LTL, In Transit
 *   3251026119 - Delivered Origin CFS
 *   9102084383041101186729 - MI Tracking Number
 *   1Z648616E192760718 - UPS Worldwide Express Freight, Order Process by UPS
 *   5548789114 - UPS Express Freight, Response for UPS Air Freight
 *   ER751105042015062 - UPS Ocean, Response for UPS Ocean Freight
 *   1ZWX0692YP40636269 - UPS SUREPOST, Response for UPS SUREPOST
 */

/**
 * Obtain OAuth 2.0 access token from UPS API
 *
 * UPS requires OAuth 2.0 authentication for all API calls as of June 2024.
 * Access tokens are valid for approximately 4 hours and should be cached/reused.
 * It's recommended to implement token caching to avoid unnecessary auth requests.
 *
 * @param array $params Configuration parameters
 *   Required parameters:
 *     -client_id (string): OAuth client ID from UPS Developer Portal
 *     -client_secret (string): OAuth client secret from UPS Developer Portal
 *   Optional parameters:
 *     -test (bool): Use test/sandbox environment (default: production)
 *     -grant_type (string): OAuth grant type (default: 'client_credentials')
 *     -merchant_id (string): Merchant ID for tracking purposes
 *
 * @return array Returns array with:
 *   - access_token (string): The OAuth bearer token for API requests
 *   - token_type (string): Token type (usually 'Bearer')
 *   - expires_in (int): Token expiration time in seconds (typically 14400 = 4 hours)
 *   - status (string): 'success' or 'error'
 *   - error (string): Error message if authentication failed
 *   - error_code (string): Error code if authentication failed
 *   - result (array): Raw response data for debugging
 *
 * @example
 *   $token = upsGetOAuthToken([
 *       '-client_id' => 'your_client_id',
 *       '-client_secret' => 'your_client_secret'
 *   ]);
 *   if (isset($token['access_token'])) {
 *       // Use $token['access_token'] for API calls
 *   }
 *
 * @link https://developer.ups.com/oauth-developer-guide OAuth Documentation
 */
function upsGetOAuthToken($params=array()){
	// Validate required parameters
	if(!isset($params['-client_id'])){return array('error'=>'No client_id', 'status'=>'error');}
	if(!isset($params['-client_secret'])){return array('error'=>'No client_secret', 'status'=>'error');}

	// Set defaults
	if(!isset($params['-grant_type'])){$params['-grant_type']='client_credentials';}

	// Determine endpoint URL
	if(isset($params['-test'])){
		$url='https://wwwcie.ups.com/security/v1/oauth/token';
	}
	else{
		$url='https://onlinetools.ups.com/security/v1/oauth/token';
	}

	// Prepare Basic Auth header
	$auth_header = 'Basic ' . base64_encode($params['-client_id'] . ':' . $params['-client_secret']);

	// Prepare POST data
	$post_data = 'grant_type=' . urlencode($params['-grant_type']);

	// Make API request
	$opts = array(
		'-ssl' => false,
		'-headers' => array(
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: ' . $auth_header,
			'x-merchant-id: ' . (isset($params['-merchant_id']) ? $params['-merchant_id'] : 'string')
		)
	);

	$result = postURL($url, $post_data, $opts);

	// Parse response
	if(isset($result['body'])){
		$response = json_decode($result['body'], true);
		if(isset($response['access_token'])){
			return array(
				'access_token' => $response['access_token'],
				'token_type' => $response['token_type'],
				'expires_in' => isset($response['expires_in']) ? $response['expires_in'] : 0,
				'status' => 'success',
				'result' => $result
			);
		}
		elseif(isset($response['error'])){
			return array(
				'error' => isset($response['error_description']) ? $response['error_description'] : $response['error'],
				'error_code' => $response['error'],
				'status' => 'error',
				'result' => $result
			);
		}
	}

	return array(
		'error' => 'Failed to obtain OAuth token',
		'status' => 'error',
		'result' => $result
	);
}

/**
 * Validate and standardize a street address using UPS Address Validation API
 *
 * Validates street addresses in the United States, Puerto Rico, and US territories.
 * Returns standardized address information and quality indicators.
 * Uses OAuth 2.0 authentication and REST/JSON API.
 *
 * @param array $params Configuration parameters
 *   Required parameters:
 *     -client_id (string): OAuth client ID from UPS Developer Portal
 *     -client_secret (string): OAuth client secret from UPS Developer Portal
 *     address (string): Street address line
 *     city (string): City name
 *     state (string): State/province code (e.g., 'CA', 'TX')
 *     zip (string): ZIP or postal code
 *   Optional parameters:
 *     -test (bool): Use test/sandbox environment
 *     -access_token (string): Pre-obtained OAuth token (avoids extra auth call)
 *     country (string): Country code (default: 'US')
 *     address2 (string): Second address line
 *     address3 (string): Third address line
 *
 * @return array Returns array with:
 *   - valid (bool): Whether address is valid
 *   - quality (string): Address quality indicator
 *   - candidate_addresses (array): Array of validated/standardized address suggestions
 *   - error (string): Error message if validation failed
 *   - result (array): Raw API response for debugging
 *
 * @example
 *   $validation = upsAddressValidate([
 *       '-client_id' => 'your_client_id',
 *       '-client_secret' => 'your_client_secret',
 *       'address' => '26601 W Agoura Rd',
 *       'city' => 'Calabasas',
 *       'state' => 'CA',
 *       'zip' => '91302'
 *   ]);
 *
 * @link https://developer.ups.com/api/reference Address Validation API Reference
 */
function upsAddressValidate($params=array()){
	// Validate required parameters
	if(!isset($params['-client_id'])){return array('error'=>'No client_id');}
	if(!isset($params['-client_secret'])){return array('error'=>'No client_secret');}
	if(!isset($params['address'])){return array('error'=>'No address');}
	if(!isset($params['city'])){return array('error'=>'No city');}
	if(!isset($params['state'])){return array('error'=>'No state');}
	if(!isset($params['zip'])){return array('error'=>'No zip');}

	// Get OAuth token if not provided
	if(!isset($params['-access_token'])){
		$token_result = upsGetOAuthToken($params);
		if(!isset($token_result['access_token'])){
			return $token_result; // Return error from auth
		}
		$access_token = $token_result['access_token'];
	}
	else{
		$access_token = $params['-access_token'];
	}

	// Set defaults
	if(!isset($params['country'])){$params['country']='US';}

	// Determine endpoint URL
	if(isset($params['-test'])){
		$url='https://wwwcie.ups.com/api/addressvalidation/v1/1';
	}
	else{
		$url='https://onlinetools.ups.com/api/addressvalidation/v1/1';
	}

	// Build address lines array
	$addressLines = array($params['address']);
	if(isset($params['address2']) && strlen($params['address2'])>0){
		$addressLines[] = $params['address2'];
	}
	if(isset($params['address3']) && strlen($params['address3'])>0){
		$addressLines[] = $params['address3'];
	}

	// Prepare JSON request
	$request_data = array(
		'XAVRequest' => array(
			'AddressKeyFormat' => array(
				'AddressLine' => $addressLines,
				'PoliticalDivision2' => $params['city'],
				'PoliticalDivision1' => $params['state'],
				'PostcodePrimaryLow' => $params['zip'],
				'CountryCode' => $params['country']
			)
		)
	);

	$json_out = json_encode($request_data);

	// Make API request
	$opts = array(
		'-ssl' => false,
		'-headers' => array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $access_token
		)
	);

	$result = postJSON($url, $json_out, $opts);

	// Parse response
	$rtn = array('-params'=>$params);

	if(isset($result['json_array']['XAVResponse'])){
		$response = $result['json_array']['XAVResponse'];

		// Check for errors
		if(isset($response['Response']['ResponseStatus']['Description'])
		   && strtolower($response['Response']['ResponseStatus']['Description']) == 'failure'){
			$rtn['error'] = isset($response['Response']['Error']['ErrorDescription'])
				? $response['Response']['Error']['ErrorDescription']
				: 'Address validation failed';
			$rtn['valid'] = false;
		}
		// Parse valid candidates
		elseif(isset($response['Candidate'])){
			$candidates = $response['Candidate'];
			// Ensure candidates is an array
			if(!isset($candidates[0])){
				$candidates = array($candidates);
			}

			$rtn['valid'] = true;
			$rtn['candidate_addresses'] = array();

			foreach($candidates as $candidate){
				$addr = array();
				if(isset($candidate['AddressKeyFormat'])){
					$akf = $candidate['AddressKeyFormat'];
					$addr['address_lines'] = isset($akf['AddressLine']) ? $akf['AddressLine'] : array();
					$addr['city'] = isset($akf['PoliticalDivision2']) ? $akf['PoliticalDivision2'] : '';
					$addr['state'] = isset($akf['PoliticalDivision1']) ? $akf['PoliticalDivision1'] : '';
					$addr['zip'] = isset($akf['PostcodePrimaryLow']) ? $akf['PostcodePrimaryLow'] : '';
					$addr['zip_extended'] = isset($akf['PostcodeExtendedLow']) ? $akf['PostcodeExtendedLow'] : '';
					$addr['country'] = isset($akf['CountryCode']) ? $akf['CountryCode'] : '';
				}
				$addr['quality'] = isset($candidate['AddressClassification']['Description'])
					? $candidate['AddressClassification']['Description']
					: '';
				$addr['quality_code'] = isset($candidate['AddressClassification']['Code'])
					? $candidate['AddressClassification']['Code']
					: '';

				$rtn['candidate_addresses'][] = $addr;
			}

			// Set quality from first candidate
			if(count($rtn['candidate_addresses']) > 0){
				$rtn['quality'] = $rtn['candidate_addresses'][0]['quality'];
			}
		}
		else{
			$rtn['valid'] = false;
			$rtn['error'] = 'No address candidates returned';
		}
	}
	else{
		$rtn['valid'] = false;
		$rtn['error'] = isset($result['error']) ? $result['error'] : 'Invalid response from UPS';
	}

	$rtn['result'] = $result;
	return $rtn;
}

/**
 * Get shipping rates for UPS services
 *
 * Retrieves shipping rates from UPS for specified origin/destination and package details.
 * Can return rates for a single service or shop for all available services.
 * Uses OAuth 2.0 authentication and REST/JSON API.
 *
 * @param array $params Configuration parameters
 *   Required parameters:
 *     -client_id (string): OAuth client ID from UPS Developer Portal
 *     -client_secret (string): OAuth client secret from UPS Developer Portal
 *     -account (string): UPS account number
 *     -shipfrom_zip (string): Origin ZIP/postal code
 *     -shipto_zip (string): Destination ZIP/postal code
 *     -weight (float): Package weight in pounds
 *   Optional parameters:
 *     -test (bool): Use test/sandbox environment
 *     -access_token (string): Pre-obtained OAuth token
 *     shipfrom_country (string): Origin country code (default: 'US')
 *     shipto_country (string): Destination country code (default: 'US')
 *     pickup_type (string): Pickup type code (default: '01' = Daily Pickup)
 *     package_type (string): Package type code (default: '02' = Customer Packaging)
 *     service_code (string): Specific service code for Rate request (default: '03' = Ground)
 *     request_option (string): 'Rate' for single service or 'Shop' for all (default: 'Shop')
 *     length (float): Package length in inches
 *     width (float): Package width in inches
 *     height (float): Package height in inches
 *
 * @return array Returns array with:
 *   - rates (array): Associative array of service names to costs
 *   - descriptions (array): Service code descriptions
 *   - error (string): Error message if request failed
 *   - result (array): Raw API response for debugging
 *   - -params (array): Input parameters for reference
 *
 * @example
 *   $rates = upsServices([
 *       '-client_id' => 'your_client_id',
 *       '-client_secret' => 'your_client_secret',
 *       '-account' => '123456',
 *       '-shipfrom_zip' => '90210',
 *       '-shipto_zip' => '10001',
 *       '-weight' => 5.5
 *   ]);
 *   if(isset($rates['rates'])){
 *       foreach($rates['rates'] as $service => $cost){
 *           echo "$service: $$cost\n";
 *       }
 *   }
 *
 * @link https://developer.ups.com/api/reference Rating API Reference
 */
function upsServices($params=array()){
	// Validate required parameters
	if(!isset($params['-client_id'])){return array('error'=>'No client_id');}
	if(!isset($params['-client_secret'])){return array('error'=>'No client_secret');}
	if(!isset($params['-account'])){return array('error'=>'No account');}
	if(!isset($params['-shipfrom_zip'])){return array('error'=>'No shipfrom_zip');}
	if(!isset($params['-shipto_zip'])){return array('error'=>'No shipto_zip');}
	if(!isset($params['-weight'])){return array('error'=>'No weight');}

	// Get OAuth token if not provided
	if(!isset($params['-access_token'])){
		$token_result = upsGetOAuthToken($params);
		if(!isset($token_result['access_token'])){
			return $token_result; // Return error from auth
		}
		$access_token = $token_result['access_token'];
	}
	else{
		$access_token = $params['-access_token'];
	}

	// Set defaults
	if(!isset($params['shipfrom_country'])){$params['shipfrom_country']='US';}
	if(!isset($params['shipto_country'])){$params['shipto_country']='US';}
	if(!isset($params['pickup_type'])){$params['pickup_type']='01';}
	if(!isset($params['package_type'])){$params['package_type']='02';}
	if(!isset($params['service_code'])){$params['service_code']='03';}
	if(!isset($params['request_option'])){$params['request_option']='Shop';}

	$rtn = array('-params'=>$params);

	// Service code lookup table
	$lookup = array(
		'01' => 'UPS Next Day Air',
		'02' => 'UPS Second Day Air',
		'03' => 'UPS Ground',
		'07' => 'UPS Worldwide Express',
		'08' => 'UPS Worldwide Expedited',
		'11' => 'UPS Standard',
		'12' => 'UPS Three-Day Select',
		'13' => 'UPS Next Day Air Saver',
		'14' => 'UPS Next Day Air Early AM',
		'54' => 'UPS Worldwide Express Plus',
		'59' => 'UPS Second Day Air AM',
		'65' => 'UPS Saver'
	);

	// Build request
	$request_data = array(
		'RateRequest' => array(
			'Request' => array(
				'TransactionReference' => array(
					'CustomerContext' => 'Rating and Service'
				)
			),
			'Shipment' => array(
				'Shipper' => array(
					'ShipperNumber' => $params['-account'],
					'Address' => array(
						'PostalCode' => $params['-shipfrom_zip'],
						'CountryCode' => $params['shipfrom_country']
					)
				),
				'ShipTo' => array(
					'Address' => array(
						'PostalCode' => $params['-shipto_zip'],
						'CountryCode' => $params['shipto_country']
					)
				),
				'ShipFrom' => array(
					'Address' => array(
						'PostalCode' => $params['-shipfrom_zip'],
						'CountryCode' => $params['shipfrom_country']
					)
				),
				'PaymentDetails' => array(
					'ShipmentCharge' => array(
						'Type' => '01',
						'BillShipper' => array(
							'AccountNumber' => $params['-account']
						)
					)
				),
				'Package' => array(
					'PackagingType' => array(
						'Code' => $params['package_type'],
						'Description' => 'Package'
					),
					'PackageWeight' => array(
						'UnitOfMeasurement' => array(
							'Code' => 'LBS'
						),
						'Weight' => (string)$params['-weight']
					)
				)
			)
		)
	);

	// Add dimensions if provided
	if(isset($params['length']) && isset($params['width']) && isset($params['height'])){
		$request_data['RateRequest']['Shipment']['Package']['Dimensions'] = array(
			'UnitOfMeasurement' => array(
				'Code' => 'IN'
			),
			'Length' => (string)$params['length'],
			'Width' => (string)$params['width'],
			'Height' => (string)$params['height']
		);
	}

	// Add service code if Rate request
	if(strtolower($params['request_option']) == 'rate'){
		$request_data['RateRequest']['Shipment']['Service'] = array(
			'Code' => $params['service_code']
		);
	}

	$json_out = json_encode($request_data);

	// Determine endpoint URL
	if(isset($params['-test'])){
		$url='https://wwwcie.ups.com/api/rating/v1/' . $params['request_option'];
	}
	else{
		$url='https://onlinetools.ups.com/api/rating/v1/' . $params['request_option'];
	}

	// Make API request
	$opts = array(
		'-ssl' => false,
		'-headers' => array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $access_token
		)
	);

	$result = postJSON($url, $json_out, $opts);

	// Parse response
	if(isset($result['json_array']['RateResponse'])){
		$response = $result['json_array']['RateResponse'];

		// Check for errors
		if(isset($response['Response']['ResponseStatus']['Description'])
		   && strtolower($response['Response']['ResponseStatus']['Description']) == 'failure'){
			$rtn['error'] = isset($response['Response']['Errors'][0]['ErrorDescription'])
				? $response['Response']['Errors'][0]['ErrorDescription']
				: 'Rating request failed';
		}
		// Parse rated shipments
		elseif(isset($response['RatedShipment'])){
			$shipments = $response['RatedShipment'];

			// Ensure shipments is an array
			if(!isset($shipments[0])){
				$shipments = array($shipments);
			}

			$rates = array();
			$descriptions = array();

			foreach($shipments as $shipment){
				$service_code = $shipment['Service']['Code'];
				$service_name = isset($lookup[$service_code]) ? $lookup[$service_code] : 'UPS ' . $service_code;
				$cost = floatval($shipment['TotalCharges']['MonetaryValue']);

				$rates[$service_name] = $cost;
				$descriptions[$service_name] = $service_name;
			}

			if(count($rates) > 0){
				asort($rates);
				$rtn['rates'] = $rates;
				$rtn['descriptions'] = $descriptions;
			}
		}
		else{
			$rtn['error'] = 'No rates returned';
		}
	}
	else{
		$rtn['error'] = isset($result['error']) ? $result['error'] : 'Invalid response from UPS';
	}

	$rtn['result'] = $result;
	return $rtn;
}

/**
 * Track a UPS shipment by tracking number
 *
 * Retrieves detailed tracking information for a UPS package including current status,
 * delivery date, activity history, and address information.
 * Uses OAuth 2.0 authentication and REST/JSON API.
 *
 * @param array $params Configuration parameters
 *   Required parameters:
 *     -client_id (string): OAuth client ID from UPS Developer Portal
 *     -client_secret (string): OAuth client secret from UPS Developer Portal
 *     -tn (string): UPS tracking number (e.g., '1Z12345E0205271688')
 *   Optional parameters:
 *     -test (bool): Use test/sandbox environment
 *     -access_token (string): Pre-obtained OAuth token
 *     -locale (string): Locale for response (default: 'en_US')
 *     -return_signature (bool): Include signature image data
 *     -return_milestone (bool): Include milestone data
 *
 * @return array Returns array with:
 *   - carrier (string): Always 'UPS'
 *   - tracking_number (string): The tracking number
 *   - status (string): Current shipment status description
 *   - status_code (string): Status code
 *   - service (array): Service information (code and description)
 *   - ship_date (string): Shipment date in YYYY-MM-DD format
 *   - ship_date_utime (int): Ship date as Unix timestamp
 *   - delivery_date (string): Actual delivery date/time (if delivered)
 *   - delivery_date_utime (int): Delivery date as Unix timestamp
 *   - shipto (array): Destination address information
 *   - packages (array): Array of package info for multi-package shipments
 *   - error (string): Error message if tracking failed
 *   - error_code (string): Error code if tracking failed
 *   - result (array): Raw API response for debugging
 *
 * @example
 *   $tracking = upsTrack([
 *       '-client_id' => 'your_client_id',
 *       '-client_secret' => 'your_client_secret',
 *       '-tn' => '1Z12345E0205271688'
 *   ]);
 *   if(isset($tracking['status'])){
 *       echo "Status: {$tracking['status']}\n";
 *       if(isset($tracking['delivery_date'])){
 *           echo "Delivered: {$tracking['delivery_date']}\n";
 *       }
 *   }
 *
 * @link https://developer.ups.com/api/reference Tracking API Reference
 */
function upsTrack($params=array()){
	// Validate required parameters
	if(!isset($params['-client_id'])){return array('error'=>'No client_id', 'carrier'=>'UPS');}
	if(!isset($params['-client_secret'])){return array('error'=>'No client_secret', 'carrier'=>'UPS');}
	if(!isset($params['-tn'])){return array('error'=>'No tn', 'carrier'=>'UPS');}

	// Get OAuth token if not provided
	if(!isset($params['-access_token'])){
		$token_result = upsGetOAuthToken($params);
		if(!isset($token_result['access_token'])){
			$token_result['carrier'] = 'UPS';
			return $token_result; // Return error from auth
		}
		$access_token = $token_result['access_token'];
	}
	else{
		$access_token = $params['-access_token'];
	}

	$rtn = array('-params'=>$params, 'carrier'=>'UPS');

	// Set defaults
	if(!isset($params['-locale'])){$params['-locale']='en_US';}

	// Build query string
	$query_params = array('locale=' . urlencode($params['-locale']));
	if(isset($params['-return_signature'])){
		$query_params[] = 'returnSignature=true';
	}
	if(isset($params['-return_milestone'])){
		$query_params[] = 'returnMilestones=true';
	}
	$query_string = implode('&', $query_params);

	// Determine endpoint URL
	if(isset($params['-test'])){
		$url = 'https://wwwcie.ups.com/api/track/v1/details/' . urlencode($params['-tn']) . '?' . $query_string;
	}
	else{
		$url = 'https://onlinetools.ups.com/api/track/v1/details/' . urlencode($params['-tn']) . '?' . $query_string;
	}

	// Make API request
	$opts = array(
		'-ssl' => false,
		'-headers' => array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $access_token
		)
	);

	$result = getURL($url, $opts);

	// Parse response
	if(isset($result['body'])){
		$response = json_decode($result['body'], true);

		// Check for errors
		if(isset($response['response']['errors'])){
			$error = $response['response']['errors'][0];
			$rtn['tracking_number'] = $params['-tn'];
			$rtn['error'] = $error['message'];
			$rtn['error_code'] = $error['code'];
			$rtn['status'] = 'ERROR: ' . $rtn['error'];
		}
		elseif(isset($response['trackResponse']['shipment'])){
			$shipments = $response['trackResponse']['shipment'];

			// Ensure shipments is an array
			if(!isset($shipments[0])){
				$shipments = array($shipments);
			}

			// Process first shipment
			$shipment = $shipments[0];

			// Service information
			if(isset($shipment['service'])){
				$rtn['service']['code'] = $shipment['service']['code'];
				$rtn['service']['description'] = $shipment['service']['description'];
			}

			// Package information
			if(isset($shipment['package'])){
				$packages = $shipment['package'];

				// Ensure packages is an array
				if(!isset($packages[0])){
					$packages = array($packages);
				}

				// Single package
				if(count($packages) == 1){
					$package = $packages[0];
					$rtn['tracking_number'] = $package['trackingNumber'];

					// Get current status
					if(isset($package['currentStatus'])){
						$rtn['status'] = $package['currentStatus']['description'];
						$rtn['status_code'] = $package['currentStatus']['code'];
					}
					elseif(isset($package['activity'][0]['status'])){
						$rtn['status'] = $package['activity'][0]['status']['description'];
						$rtn['status_code'] = $package['activity'][0]['status']['code'];
					}

					// Check for delivery
					if(isset($package['deliveryDate'])){
						$delivery = $package['deliveryDate'][0];
						$delivery_date = $delivery['date'];
						$delivery_time = isset($delivery['time']) ? $delivery['time'] : '000000';

						// Parse date YYYYMMDD and time HHMMSS
						$year = substr($delivery_date, 0, 4);
						$month = substr($delivery_date, 4, 2);
						$day = substr($delivery_date, 6, 2);
						$hh = substr($delivery_time, 0, 2);
						$mm = substr($delivery_time, 2, 2);
						$ss = substr($delivery_time, 4, 2);

						$rtn['delivery_date'] = "{$year}-{$month}-{$day} {$hh}:{$mm}:{$ss}";
						$rtn['delivery_date_utime'] = strtotime($rtn['delivery_date']);
					}

					// Delivery location/address
					if(isset($package['deliveryInformation']['location']['address'])){
						$addr = $package['deliveryInformation']['location']['address'];
						$rtn['shipto'] = array(
							'city' => isset($addr['city']) ? $addr['city'] : '',
							'state' => isset($addr['stateProvince']) ? $addr['stateProvince'] : '',
							'country' => isset($addr['country']) ? $addr['country'] : '',
							'zip' => isset($addr['postalCode']) ? $addr['postalCode'] : ''
						);
					}
					elseif(isset($package['activity'][0]['location']['address'])){
						$addr = $package['activity'][0]['location']['address'];
						$rtn['shipto'] = array(
							'city' => isset($addr['city']) ? $addr['city'] : '',
							'state' => isset($addr['stateProvince']) ? $addr['stateProvince'] : '',
							'country' => isset($addr['country']) ? $addr['country'] : '',
							'zip' => isset($addr['postalCode']) ? $addr['postalCode'] : ''
						);
					}
				}
				// Multiple packages
				else{
					$rtn['packages'] = array();

					foreach($packages as $package){
						$pkg = array();
						$pkg['tracking_number'] = $package['trackingNumber'];

						// Get current status
						if(isset($package['currentStatus'])){
							$pkg['status'] = $package['currentStatus']['description'];
							$pkg['status_code'] = $package['currentStatus']['code'];
						}
						elseif(isset($package['activity'][0]['status'])){
							$pkg['status'] = $package['activity'][0]['status']['description'];
							$pkg['status_code'] = $package['activity'][0]['status']['code'];
						}

						// Check for delivery
						if(isset($package['deliveryDate'])){
							$delivery = $package['deliveryDate'][0];
							$delivery_date = $delivery['date'];
							$delivery_time = isset($delivery['time']) ? $delivery['time'] : '000000';

							// Parse date YYYYMMDD and time HHMMSS
							$year = substr($delivery_date, 0, 4);
							$month = substr($delivery_date, 4, 2);
							$day = substr($delivery_date, 6, 2);
							$hh = substr($delivery_time, 0, 2);
							$mm = substr($delivery_time, 2, 2);
							$ss = substr($delivery_time, 4, 2);

							$pkg['delivery_date'] = "{$year}-{$month}-{$day} {$hh}:{$mm}:{$ss}";
							$pkg['delivery_date_utime'] = strtotime($pkg['delivery_date']);
						}

						// Delivery location/address
						if(isset($package['deliveryInformation']['location']['address'])){
							$addr = $package['deliveryInformation']['location']['address'];
							$pkg['shipto'] = array(
								'city' => isset($addr['city']) ? $addr['city'] : '',
								'state' => isset($addr['stateProvince']) ? $addr['stateProvince'] : '',
								'country' => isset($addr['country']) ? $addr['country'] : '',
								'zip' => isset($addr['postalCode']) ? $addr['postalCode'] : ''
							);
						}
						elseif(isset($package['activity'][0]['location']['address'])){
							$addr = $package['activity'][0]['location']['address'];
							$pkg['shipto'] = array(
								'city' => isset($addr['city']) ? $addr['city'] : '',
								'state' => isset($addr['stateProvince']) ? $addr['stateProvince'] : '',
								'country' => isset($addr['country']) ? $addr['country'] : '',
								'zip' => isset($addr['postalCode']) ? $addr['postalCode'] : ''
							);
						}

						$rtn['packages'][] = $pkg;
					}
				}
			}

			// Ship date (pickup date)
			if(isset($shipment['pickupDate'])){
				$sdate = $shipment['pickupDate'];
				// Parse date YYYYMMDD
				$year = substr($sdate, 0, 4);
				$month = substr($sdate, 4, 2);
				$day = substr($sdate, 6, 2);
				$rtn['ship_date'] = "{$year}-{$month}-{$day}";
				$rtn['ship_date_utime'] = strtotime($rtn['ship_date']);
			}
		}
		else{
			$rtn['tracking_number'] = $params['-tn'];
			$rtn['error'] = 'Invalid response from UPS tracking API';
			$rtn['status'] = 'ERROR: ' . $rtn['error'];
		}
	}
	else{
		$rtn['tracking_number'] = $params['-tn'];
		$rtn['error'] = isset($result['error']) ? $result['error'] : 'Failed to connect to UPS';
		$rtn['status'] = 'ERROR: ' . $rtn['error'];
	}

	$rtn['result'] = $result;
	return $rtn;
}

/**
 * DEPRECATED: Legacy upsTrack function using old XML API
 *
 * This function uses the deprecated XML-based UPS API that was retired January 1, 2020.
 * Use upsTrack() instead, which uses the current OAuth 2.0 REST API.
 *
 * @deprecated since version 2.0.0
 * @param array $params Legacy parameters
 * @return array Error message directing to new function
 */
function upsTrack_OLD($params=array()){
	return array(
		'error' => 'This API was deprecated as of January 1, 2020. Use upsTrack() with OAuth credentials instead.',
		'carrier' => 'UPS',
		'status' => 'ERROR: API deprecated'
	);
}
?>
