<?php
/*
	USPS API v3 - Migrated from deprecated Web Tools API
	Reference: https://developers.usps.com/apis
	Migration Guide: https://www.usps.com/business/web-tools-apis/onboarding-guide.pdf

	IMPORTANT: The old Web Tools API was retired on January 25, 2026.
	This file now uses the new USPS API v3 with OAuth 2.0 authentication.

	Requirements:
	- Consumer Key (Client ID)
	- Consumer Secret (Client Secret)
	- OAuth tokens expire and are automatically refreshed

	Base URLs:
	- Production: https://apis.usps.com
	- Testing: https://apis-tem.usps.com
*/

// Global token cache to avoid unnecessary OAuth requests
global $USPS_ACCESS_TOKEN_CACHE;
$USPS_ACCESS_TOKEN_CACHE = array();

//------------------
/**
 * Get OAuth 2.0 Access Token for USPS API v3
 *
 * Implements automatic token caching to reduce API calls. Tokens are cached
 * globally and automatically refreshed when expired (with 60-second buffer).
 * This is an internal helper function called by uspsApiRequest().
 *
 * @param array $params Configuration parameters
 *   - string -client_id (required) USPS Consumer Key from Developer Portal
 *   - string -client_secret (required) USPS Consumer Secret from Developer Portal
 *   - bool -test (optional) Use testing environment (apis-tem.usps.com)
 *
 * @return array Token data on success, error information on failure
 *   Success:
 *   - string access_token OAuth Bearer token
 *   - string token_type Token type (usually "Bearer")
 *   - int expires_in Seconds until token expires
 *   - int expires_at Unix timestamp when token expires
 *   - int issued_at Unix timestamp when token was issued
 *
 *   Error:
 *   - string error Error message
 *   - int http_code HTTP response code (if available)
 *   - mixed response Raw API response (if available)
 *
 * @since 1.0.0
 * @link https://developers.usps.com/oauth OAuth 2.0 Documentation
 */
function uspsGetAccessToken($params = array()) {
	global $USPS_ACCESS_TOKEN_CACHE;

	// Validate required parameters
	if (!isset($params['-client_id']) || empty($params['-client_id'])) {
		return array('error' => 'Missing required parameter: -client_id (Consumer Key)');
	}
	if (!isset($params['-client_secret']) || empty($params['-client_secret'])) {
		return array('error' => 'Missing required parameter: -client_secret (Consumer Secret)');
	}

	// Check cache for valid token
	$cache_key = md5($params['-client_id'] . $params['-client_secret']);
	if (isset($USPS_ACCESS_TOKEN_CACHE[$cache_key])) {
		$cached = $USPS_ACCESS_TOKEN_CACHE[$cache_key];
		// Check if token is still valid (with 60 second buffer)
		if (isset($cached['expires_at']) && $cached['expires_at'] > time() + 60) {
			return $cached;
		}
	}

	// Determine base URL
	$base_url = (isset($params['-test']) && $params['-test'])
		? 'https://apis-tem.usps.com'
		: 'https://apis.usps.com';

	$url = $base_url . '/oauth2/v3/token';

	// Prepare OAuth request
	$post_data = array(
		'grant_type' => 'client_credentials',
		'client_id' => $params['-client_id'],
		'client_secret' => $params['-client_secret']
	);

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
		return array('error' => 'cURL error: ' . $curl_error);
	}

	// Parse response
	$result = json_decode($response, true);

	if ($http_code !== 200 || !isset($result['access_token'])) {
		return array(
			'error' => 'OAuth authentication failed',
			'http_code' => $http_code,
			'response' => $result
		);
	}

	// Cache the token
	$token_data = array(
		'access_token' => $result['access_token'],
		'token_type' => $result['token_type'],
		'expires_in' => $result['expires_in'],
		'expires_at' => time() + $result['expires_in'],
		'issued_at' => $result['issued_at']
	);

	$USPS_ACCESS_TOKEN_CACHE[$cache_key] = $token_data;

	return $token_data;
}

//------------------
/**
 * Make authenticated API request to USPS API v3
 *
 * This is an internal helper function that handles OAuth authentication,
 * HTTP requests, and response parsing for all USPS API v3 endpoints.
 * Automatically retrieves and caches OAuth tokens.
 *
 * @param string $endpoint API endpoint path (e.g., '/prices/v3/base-rates/search')
 * @param array $params Configuration parameters (must include -client_id and -client_secret)
 * @param string $method HTTP method (GET or POST, default: POST)
 * @param array|null $data Request body data for POST requests (will be JSON encoded)
 *
 * @return array Response data or error information
 *   Success:
 *   - bool success true if request succeeded
 *   - int http_code HTTP response code
 *   - array data Decoded JSON response data
 *
 *   Error:
 *   - string error Error message
 *   - int http_code HTTP response code (if available)
 *   - mixed response Raw API response (if available)
 *
 * @since 1.0.0
 */
function uspsApiRequest($endpoint, $params, $method = 'POST', $data = null) {
	// Get access token
	$token_result = uspsGetAccessToken($params);

	if (isset($token_result['error'])) {
		return array('error' => $token_result['error'], 'token_result' => $token_result);
	}

	// Determine base URL
	$base_url = (isset($params['-test']) && $params['-test'])
		? 'https://apis-tem.usps.com'
		: 'https://apis.usps.com';

	$url = $base_url . $endpoint;

	// Initialize cURL
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

	$headers = array(
		'Authorization: Bearer ' . $token_result['access_token'],
		'Content-Type: application/json',
		'Accept: application/json'
	);

	if (strtoupper($method) === 'POST') {
		curl_setopt($ch, CURLOPT_POST, true);
		if ($data !== null) {
			$json_data = json_encode($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
			$headers[] = 'Content-Length: ' . strlen($json_data);
		}
	}

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$response = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curl_error = curl_error($ch);
	curl_close($ch);

	// Handle cURL errors
	if ($curl_error) {
		return array('error' => 'cURL error: ' . $curl_error);
	}

	// Parse response
	$result = json_decode($response, true);

	if ($http_code < 200 || $http_code >= 300) {
		return array(
			'error' => 'API request failed',
			'http_code' => $http_code,
			'response' => $result ? $result : $response
		);
	}

	return array(
		'success' => true,
		'http_code' => $http_code,
		'data' => $result
	);
}

//------------------
/**
 * Get USPS shipping rates for domestic and international shipments
 *
 * Retrieves real-time shipping rates from USPS API v3. Supports both domestic
 * (US to US) and international shipments. Returns sorted list of available
 * services with pricing. Replaces legacy RateV4 and IntlRateV2 APIs.
 *
 * @param array $params Configuration parameters
 *
 *   Required (all shipments):
 *   - string -client_id USPS Consumer Key from Developer Portal
 *   - string -client_secret USPS Consumer Secret from Developer Portal
 *   - float -weight Package weight in ounces (16oz = 1lb)
 *
 *   Required (domestic shipments):
 *   - string -zip_orig Origin ZIP code (5 digits)
 *   - string -zip_dest Destination ZIP code (5 digits)
 *
 *   Required (international shipments):
 *   - bool -intl Set to true for international shipments
 *   - string -country Destination country code (ISO 2-letter, e.g., 'CA' for Canada)
 *   - string -zip_orig Origin ZIP code (5 digits)
 *
 *   Optional:
 *   - bool -test Use testing environment (default: false)
 *   - float -length Package length in inches
 *   - float -width Package width in inches
 *   - float -height Package height in inches
 *   - string -mail_class Specific mail class or 'ALL' (default: 'ALL')
 *     Examples: 'PRIORITY', 'PRIORITY_MAIL_EXPRESS', 'USPS_GROUND_ADVANTAGE'
 *   - string -processing_category Processing category (e.g., 'MACHINABLE', 'NON_MACHINABLE')
 *   - string -destination_entry_facility Destination entry facility type
 *   - string -foreign_postal_code Foreign postal code (international only)
 *
 * @return array Rate information or error
 *   Success:
 *   - array rates Associative array of service name => price (sorted by price)
 *   - array params Input parameters
 *   - array request_data Request data sent to API
 *   - array result Full API response
 *
 *   Partial success:
 *   - string warning Warning message (e.g., 'No rates found in response')
 *
 *   Error:
 *   - string error Error message
 *
 * @since 1.0.0
 * @link https://developers.usps.com/domesticpricesv3 Domestic Prices API
 * @link https://developers.usps.com/internationalpricesv3 International Prices API
 *
 * @example
 * // Get domestic rates
 * $rates = uspsServices(array(
 *     '-client_id' => 'your_consumer_key',
 *     '-client_secret' => 'your_consumer_secret',
 *     '-weight' => 16,
 *     '-zip_orig' => '90210',
 *     '-zip_dest' => '10001',
 *     '-length' => 10,
 *     '-width' => 8,
 *     '-height' => 6
 * ));
 *
 * // Get international rates
 * $rates = uspsServices(array(
 *     '-client_id' => 'your_consumer_key',
 *     '-client_secret' => 'your_consumer_secret',
 *     '-weight' => 16,
 *     '-zip_orig' => '90210',
 *     '-country' => 'CA',
 *     '-intl' => true
 * ));
 */
function uspsServices($params = array()) {
	// Validate required parameters
	if (!isset($params['-client_id'])) {
		return array('error' => 'Missing required parameter: -client_id');
	}
	if (!isset($params['-client_secret'])) {
		return array('error' => 'Missing required parameter: -client_secret');
	}
	if (!isset($params['-weight'])) {
		return array('error' => 'Missing required parameter: -weight');
	}

	$is_international = isset($params['-intl']) && $params['-intl'];

	// Build the request
	if ($is_international) {
		// International pricing
		if (!isset($params['-country'])) {
			return array('error' => 'Missing required parameter for international: -country');
		}
		if (!isset($params['-zip_orig'])) {
			return array('error' => 'Missing required parameter: -zip_orig');
		}

		$endpoint = '/international-prices/v3/international-prices';

		// Convert weight to pounds and ounces
		$weight_lbs = floor($params['-weight'] / 16);
		$weight_oz = $params['-weight'] - ($weight_lbs * 16);

		$request_data = array(
			'originZIPCode' => $params['-zip_orig'],
			'foreignPostalCode' => isset($params['-foreign_postal_code']) ? $params['-foreign_postal_code'] : '',
			'destinationCountryCode' => $params['-country'],
			'weight' => (float)$params['-weight'],
			'mailClass' => isset($params['-mail_class']) ? $params['-mail_class'] : 'ALL'
		);

		// Add dimensions if provided
		if (isset($params['-length']) && isset($params['-width']) && isset($params['-height'])) {
			$request_data['length'] = (float)$params['-length'];
			$request_data['width'] = (float)$params['-width'];
			$request_data['height'] = (float)$params['-height'];
		}

	} else {
		// Domestic pricing
		if (!isset($params['-zip_orig'])) {
			return array('error' => 'Missing required parameter: -zip_orig');
		}
		if (!isset($params['-zip_dest'])) {
			return array('error' => 'Missing required parameter: -zip_dest');
		}

		$endpoint = '/prices/v3/base-rates/search';

		// Convert weight to pounds and ounces
		$weight_lbs = floor($params['-weight'] / 16);
		$weight_oz = $params['-weight'] - ($weight_lbs * 16);

		$request_data = array(
			'originZIPCode' => $params['-zip_orig'],
			'destinationZIPCode' => $params['-zip_dest'],
			'weight' => (float)$params['-weight'],
			'mailClass' => isset($params['-mail_class']) ? $params['-mail_class'] : 'ALL'
		);

		// Add dimensions if provided
		if (isset($params['-length']) && isset($params['-width']) && isset($params['-height'])) {
			$request_data['length'] = (float)$params['-length'];
			$request_data['width'] = (float)$params['-width'];
			$request_data['height'] = (float)$params['-height'];
		}

		// Add processing category if provided
		if (isset($params['-processing_category'])) {
			$request_data['processingCategory'] = $params['-processing_category'];
		}

		// Add destination entry facility if provided
		if (isset($params['-destination_entry_facility'])) {
			$request_data['destinationEntryFacilityType'] = $params['-destination_entry_facility'];
		}
	}

	// Make API request
	$result = uspsApiRequest($endpoint, $params, 'POST', $request_data);

	if (isset($result['error'])) {
		return $result;
	}

	// Parse rates from response
	$rates = array();
	$rtn = array(
		'params' => $params,
		'request_data' => $request_data
	);

	if (isset($result['data']) && is_array($result['data'])) {
		// Handle different response structures
		if (isset($result['data']['rateOptions'])) {
			// Domestic response format
			foreach ($result['data']['rateOptions'] as $rate_option) {
				if (isset($rate_option['rates']) && is_array($rate_option['rates'])) {
					foreach ($rate_option['rates'] as $rate) {
						$service_name = isset($rate['description']) ? $rate['description'] :
							(isset($rate['mailClass']) ? $rate['mailClass'] : 'Unknown Service');
						$price = isset($rate['price']) ? $rate['price'] :
							(isset($rate['totalPrice']) ? $rate['totalPrice'] : 0);
						$rates[$service_name] = $price;
					}
				}
			}
		} elseif (isset($result['data']['totalBasePrice'])) {
			// Single rate response
			$service_name = isset($result['data']['mailClass']) ? $result['data']['mailClass'] : 'USPS Service';
			$rates[$service_name] = $result['data']['totalBasePrice'];
		} elseif (is_array($result['data'])) {
			// Array of rates
			foreach ($result['data'] as $rate) {
				if (isset($rate['mailClass']) && isset($rate['price'])) {
					$rates[$rate['mailClass']] = $rate['price'];
				} elseif (isset($rate['rateOptions'])) {
					foreach ($rate['rateOptions'] as $option) {
						if (isset($option['rates'])) {
							foreach ($option['rates'] as $subroute) {
								$service_name = isset($subroute['description']) ? $subroute['description'] :
									(isset($subroute['mailClass']) ? $subroute['mailClass'] : 'Unknown Service');
								$price = isset($subroute['price']) ? $subroute['price'] :
									(isset($subroute['totalPrice']) ? $subroute['totalPrice'] : 0);
								$rates[$service_name] = $price;
							}
						}
					}
				}
			}
		}
	}

	if (count($rates) > 0) {
		asort($rates);
		$rtn['rates'] = $rates;
	} else {
		$rtn['warning'] = 'No rates found in response';
	}

	$rtn['result'] = $result;

	return $rtn;
}

//------------------
/**
 * Track USPS package using tracking number
 *
 * Retrieves real-time tracking information for USPS shipments including
 * status, location history, and delivery details. Automatically cleans
 * tracking numbers (removes spaces/special characters). Replaces legacy
 * TrackV2 API.
 *
 * @param array $params Configuration parameters
 *   Required:
 *   - string -client_id USPS Consumer Key from Developer Portal
 *   - string -client_secret USPS Consumer Secret from Developer Portal
 *   - string -tn Tracking number (letters and numbers, spaces/dashes OK)
 *
 *   Optional:
 *   - bool -test Use testing environment (default: false)
 *
 * @return array Tracking information or error
 *   Success:
 *   - string status Current status ('Delivered', 'In Transit', 'Out for Delivery', 'Arrived', 'Error', 'Unknown')
 *   - string tracking_number Cleaned tracking number
 *   - string carrier Always 'USPS'
 *   - string method Always 'N/A'
 *   - string summary Latest tracking event summary
 *   - array detail Array of all tracking events (newest first)
 *   - array params Input parameters
 *   - array result Full API response
 *
 *   Error:
 *   - string error Error message
 *   - string status 'Error'
 *
 * @since 1.0.0
 * @link https://developers.usps.com/trackingv3 Tracking v3 API Documentation
 *
 * @example
 * $tracking = uspsTrack(array(
 *     '-client_id' => 'your_consumer_key',
 *     '-client_secret' => 'your_consumer_secret',
 *     '-tn' => '9400111899562853289749'
 * ));
 *
 * if ($tracking['status'] == 'Delivered') {
 *     echo "Package delivered: " . $tracking['summary'];
 * }
 */
function uspsTrack($params = array()) {
	// Validate required parameters
	if (!isset($params['-client_id'])) {
		return array('error' => 'Missing required parameter: -client_id');
	}
	if (!isset($params['-client_secret'])) {
		return array('error' => 'Missing required parameter: -client_secret');
	}
	if (!isset($params['-tn'])) {
		return array('error' => 'Missing required parameter: -tn (tracking number)');
	}

	// Clean tracking number (remove spaces and special characters)
	$tracking_number = preg_replace('/[^A-Z0-9]/i', '', $params['-tn']);

	$endpoint = '/tracking/v3/tracking/' . urlencode($tracking_number);

	// Make API request (GET method for tracking)
	$result = uspsApiRequest($endpoint, $params, 'GET');

	$rtn = array(
		'params' => $params,
		'carrier' => 'USPS',
		'method' => 'N/A',
		'tracking_number' => $tracking_number,
		'status' => 'Unknown'
	);

	if (isset($result['error'])) {
		$rtn['error'] = $result['error'];
		$rtn['status'] = 'Error';
		$rtn['result'] = $result;
		return $rtn;
	}

	// Parse tracking data
	if (isset($result['data']['trackingEvents']) && is_array($result['data']['trackingEvents'])) {
		$events = $result['data']['trackingEvents'];

		// Get most recent event (usually first in array)
		if (count($events) > 0) {
			$latest_event = $events[0];

			// Set status based on event
			if (isset($latest_event['eventType'])) {
				$event_type = strtolower($latest_event['eventType']);

				if (strpos($event_type, 'delivered') !== false) {
					$rtn['status'] = 'Delivered';
				} elseif (strpos($event_type, 'out for delivery') !== false) {
					$rtn['status'] = 'Out for Delivery';
				} elseif (strpos($event_type, 'arrival') !== false || strpos($event_type, 'arrived') !== false) {
					$rtn['status'] = 'Arrived';
				} elseif (strpos($event_type, 'in transit') !== false || strpos($event_type, 'accepted') !== false) {
					$rtn['status'] = 'In Transit';
				}
			}

			// Build summary from latest event
			$summary_parts = array();
			if (isset($latest_event['eventType'])) {
				$summary_parts[] = $latest_event['eventType'];
			}
			if (isset($latest_event['eventTimestamp'])) {
				$summary_parts[] = $latest_event['eventTimestamp'];
			}
			if (isset($latest_event['eventCity']) && isset($latest_event['eventState'])) {
				$summary_parts[] = $latest_event['eventCity'] . ', ' . $latest_event['eventState'];
			}

			$rtn['summary'] = implode(' - ', $summary_parts);
		}

		// Build detail array from all events
		$detail = array();
		foreach ($events as $event) {
			$detail_parts = array();
			if (isset($event['eventType'])) {
				$detail_parts[] = $event['eventType'];
			}
			if (isset($event['eventTimestamp'])) {
				$detail_parts[] = $event['eventTimestamp'];
			}
			if (isset($event['eventCity']) && isset($event['eventState'])) {
				$detail_parts[] = $event['eventCity'] . ', ' . $event['eventState'];
			}
			$detail[] = implode(' - ', $detail_parts);
		}
		$rtn['detail'] = $detail;
	}

	// Check for error messages in response
	if (isset($result['data']['error'])) {
		$rtn['error'] = $result['data']['error'];
		$rtn['status'] = 'Error';
	} elseif (isset($result['data']['errorMessage'])) {
		$rtn['error'] = $result['data']['errorMessage'];
		$rtn['status'] = 'Error';
	}

	$rtn['result'] = $result;

	return $rtn;
}

//------------------
/**
 * Verify and standardize USPS addresses
 *
 * Validates and standardizes US addresses against USPS database. Returns
 * standardized addresses with corrections and compares input vs output.
 * Supports batch verification of multiple addresses. Backward compatible
 * with old field names (Address1, Address2, City, State, Zip5, Zip4).
 * Replaces legacy Address Validation API.
 *
 * @param array $params Configuration parameters
 *   Required:
 *   - string -client_id USPS Consumer Key from Developer Portal
 *   - string -client_secret USPS Consumer Secret from Developer Portal
 *   - array address Array of addresses to verify (indexed array)
 *
 *   Each address array should contain:
 *   - string Address2 or streetAddress Primary street address (required)
 *   - string City or city City name (required)
 *   - string State or state State code 2 letters (required)
 *   - string Address1 or secondaryAddress Secondary address like apt/suite (optional)
 *   - string Zip5 or ZIPCode ZIP code 5 digits (optional but recommended)
 *   - string Zip4 or ZIPPlus4 ZIP+4 extension (optional)
 *
 *   Optional:
 *   - bool -test Use testing environment (default: false)
 *
 * @return array Verification results
 *   - array address Indexed array of address verification results
 *     - array in Original input address
 *     - array out Standardized output address (if successful)
 *       - string Address1 Secondary address (apt, suite, etc.)
 *       - string Address2 Primary street address
 *       - string City City name
 *       - string State State code (2 letters)
 *       - string Zip5 ZIP code (5 digits)
 *       - string Zip4 ZIP+4 extension (4 digits)
 *     - array diff Fields that were changed (if any)
 *     - string err Error message (if error)
 *     - int errno Error number (if error)
 *   - int attn Attention flag (1 if any address has corrections/errors, 0 if all OK)
 *
 * @since 1.0.0
 * @link https://developers.usps.com/addressesv3 Addresses v3 API Documentation
 *
 * @example
 * $verification = uspsVerifyAddress(array(
 *     '-client_id' => 'your_consumer_key',
 *     '-client_secret' => 'your_consumer_secret',
 *     'address' => array(
 *         0 => array(
 *             'Address1' => '',
 *             'Address2' => '6406 Ivy Lane',
 *             'City' => 'Greenbelt',
 *             'State' => 'MD',
 *             'Zip5' => ''
 *         )
 *     )
 * ));
 *
 * if ($verification['attn'] == 0) {
 *     echo "Address verified without changes";
 * } else {
 *     echo "Address corrections: ";
 *     print_r($verification['address'][0]['diff']);
 * }
 */
function uspsVerifyAddress($params = array()) {
	// Validate required parameters
	if (!isset($params['-client_id'])) {
		return array('error' => 'Missing required parameter: -client_id');
	}
	if (!isset($params['-client_secret'])) {
		return array('error' => 'Missing required parameter: -client_secret');
	}
	if (!isset($params['address']) || !is_array($params['address'])) {
		return array('error' => 'Missing required parameter: address (must be array)');
	}

	$rtn = array(
		'address' => array(),
		'attn' => 0
	);

	$endpoint = '/addresses/v3/address';

	// Process each address
	foreach ($params['address'] as $id => $address) {
		$rtn['address'][$id]['in'] = $address;

		// Map old field names to new API format
		$request_data = array();

		// Handle different field name formats (old API used Address1, Address2, City, State, Zip5, Zip4)
		if (isset($address['Address2'])) {
			$request_data['streetAddress'] = $address['Address2'];
		} elseif (isset($address['streetAddress'])) {
			$request_data['streetAddress'] = $address['streetAddress'];
		}

		if (isset($address['Address1']) && !empty($address['Address1'])) {
			$request_data['secondaryAddress'] = $address['Address1'];
		} elseif (isset($address['secondaryAddress'])) {
			$request_data['secondaryAddress'] = $address['secondaryAddress'];
		}

		if (isset($address['City'])) {
			$request_data['city'] = $address['City'];
		} elseif (isset($address['city'])) {
			$request_data['city'] = $address['city'];
		}

		if (isset($address['State'])) {
			$request_data['state'] = $address['State'];
		} elseif (isset($address['state'])) {
			$request_data['state'] = $address['state'];
		}

		if (isset($address['Zip5'])) {
			$request_data['ZIPCode'] = $address['Zip5'];
		} elseif (isset($address['ZIPCode'])) {
			$request_data['ZIPCode'] = $address['ZIPCode'];
		}

		if (isset($address['Zip4'])) {
			$request_data['ZIPPlus4'] = $address['Zip4'];
		} elseif (isset($address['ZIPPlus4'])) {
			$request_data['ZIPPlus4'] = $address['ZIPPlus4'];
		}

		// Make API request
		$result = uspsApiRequest($endpoint, $params, 'POST', $request_data);

		if (isset($result['error'])) {
			$rtn['address'][$id]['out']['err'] = $result['error'];
			$rtn['address'][$id]['out']['errno'] = isset($result['http_code']) ? $result['http_code'] : 0;
			$rtn['attn'] = 1;
			continue;
		}

		// Parse response
		if (isset($result['data']['address'])) {
			$verified = $result['data']['address'];

			// Map response back to old format for compatibility
			$output = array();

			if (isset($verified['streetAddress'])) {
				$output['Address2'] = $verified['streetAddress'];
			}
			if (isset($verified['secondaryAddress'])) {
				$output['Address1'] = $verified['secondaryAddress'];
			} else {
				$output['Address1'] = '';
			}
			if (isset($verified['city'])) {
				$output['City'] = $verified['city'];
			}
			if (isset($verified['state'])) {
				$output['State'] = $verified['state'];
			}
			if (isset($verified['ZIPCode'])) {
				$output['Zip5'] = $verified['ZIPCode'];
			}
			if (isset($verified['ZIPPlus4'])) {
				$output['Zip4'] = $verified['ZIPPlus4'];
			}

			$rtn['address'][$id]['out'] = $output;

			// Compare input vs output to detect changes
			$diff = array();
			foreach ($address as $key => $val) {
				if ($key == 'Zip4' || $key == 'ZIPPlus4') {
					continue; // Skip ZIP+4 comparison
				}

				$old_key = $key;
				// Map to new output keys
				if ($key == 'Address2') {
					$new_key = 'Address2';
				} elseif ($key == 'Address1') {
					$new_key = 'Address1';
				} elseif ($key == 'City') {
					$new_key = 'City';
				} elseif ($key == 'State') {
					$new_key = 'State';
				} elseif ($key == 'Zip5') {
					$new_key = 'Zip5';
				} else {
					continue;
				}

				if (isset($output[$new_key]) && strtoupper(trim($val)) != strtoupper(trim($output[$new_key]))) {
					$diff[] = $key;
					$rtn['attn'] = 1;
				}
			}

			if (count($diff) > 0) {
				$rtn['address'][$id]['diff'] = $diff;
			}

		} elseif (isset($result['data']['error'])) {
			$rtn['address'][$id]['out']['err'] = $result['data']['error'];
			$rtn['address'][$id]['out']['errno'] = 0;
			$rtn['attn'] = 1;
		}
	}

	return $rtn;
}

//------------------
/**
 * Get city and state information from ZIP code
 *
 * Performs reverse ZIP code lookup to retrieve city and state information.
 * Useful for auto-completing address forms or validating ZIP codes.
 * Uses the Address Validation API with ZIP-only lookup. Replaces legacy
 * CityStateLookup API.
 *
 * @param string $zip ZIP code to lookup (5 digits, required)
 * @param array $params Configuration parameters
 *   Required:
 *   - string -client_id USPS Consumer Key from Developer Portal
 *   - string -client_secret USPS Consumer Secret from Developer Portal
 *
 *   Optional:
 *   - bool -test Use testing environment (default: false)
 *
 * @return array City and state information or error
 *   Success:
 *   - string zip Input ZIP code
 *   - string city City name
 *   - string state State code (2 letters)
 *   - string zip5 ZIP code (5 digits)
 *   - string zip4 ZIP+4 extension (if available)
 *   - array result Full API response
 *
 *   Error:
 *   - string zip Input ZIP code
 *   - string error Error message
 *   - array result Full API response
 *
 * @since 1.0.0
 * @link https://developers.usps.com/addressesv3 Addresses v3 API Documentation
 *
 * @example
 * $zipInfo = uspsZipCodeInfo('90210', array(
 *     '-client_id' => 'your_consumer_key',
 *     '-client_secret' => 'your_consumer_secret'
 * ));
 *
 * if (!isset($zipInfo['error'])) {
 *     echo "City: " . $zipInfo['city'] . ", State: " . $zipInfo['state'];
 * }
 */
function uspsZipCodeInfo($zip = '', $params = array()) {
	if (strlen($zip) == 0) {
		return array('error' => 'No ZIP code provided');
	}

	// Validate required parameters
	if (!isset($params['-client_id'])) {
		return array('error' => 'Missing required parameter: -client_id');
	}
	if (!isset($params['-client_secret'])) {
		return array('error' => 'Missing required parameter: -client_secret');
	}

	// Use address verification API with just ZIP code
	$endpoint = '/addresses/v3/address';

	$request_data = array(
		'ZIPCode' => $zip
	);

	// Make API request
	$result = uspsApiRequest($endpoint, $params, 'POST', $request_data);

	$rtn = array(
		'zip' => $zip
	);

	if (isset($result['error'])) {
		$rtn['error'] = $result['error'];
		$rtn['result'] = $result;
		return $rtn;
	}

	// Parse response
	if (isset($result['data']['address'])) {
		$address = $result['data']['address'];

		if (isset($address['city'])) {
			$rtn['city'] = $address['city'];
		}
		if (isset($address['state'])) {
			$rtn['state'] = $address['state'];
		}
		if (isset($address['ZIPCode'])) {
			$rtn['zip5'] = $address['ZIPCode'];
		}
		if (isset($address['ZIPPlus4'])) {
			$rtn['zip4'] = $address['ZIPPlus4'];
		}
	}

	$rtn['result'] = $result;

	return $rtn;
}

//------------------
/**
 * Create USPS shipping label with tracking
 *
 * Generates USPS shipping labels for domestic shipments including Priority Mail
 * Express, Priority Mail, USPS Ground Advantage, and other mail classes. Returns
 * tracking number and base64-encoded label image (PDF or PNG). Requires USPS
 * payment account. Field lengths are automatically truncated to USPS limits.
 * Replaces legacy ExpressMailLabel API.
 *
 * @param array $params Configuration parameters
 *
 *   Required (Authentication):
 *   - string -client_id USPS Consumer Key from Developer Portal
 *   - string -client_secret USPS Consumer Secret from Developer Portal
 *   - string -payment_account_number USPS Payment Account Number
 *
 *   Required (From Address):
 *   - string shipfromfirstname Sender first name (max 26 chars)
 *   - string shipfromlastname Sender last name (max 26 chars)
 *   - string shipfromaddress1 Sender street address (max 50 chars)
 *   - string shipfromcity Sender city (max 28 chars)
 *   - string shipfromstate Sender state code (2 letters)
 *   - string shipfromzipcode Sender ZIP code (5 digits)
 *
 *   Required (To Address):
 *   - string shiptofirstname Recipient first name (max 26 chars)
 *   - string shiptolastname Recipient last name (max 26 chars)
 *   - string shiptoaddress1 Recipient street address (max 50 chars)
 *   - string shiptocity Recipient city (max 28 chars)
 *   - string shiptostate Recipient state code (2 letters)
 *   - string shiptozipcode Recipient ZIP code (5 digits)
 *
 *   Required (Package):
 *   - float weight Package weight in ounces (16oz = 1lb, max 1120oz = 70lbs)
 *   - float length Package length in inches
 *   - float width Package width in inches
 *   - float height Package height in inches
 *
 *   Optional:
 *   - bool -test Use testing environment (default: false)
 *   - string -mail_class Mail class (default: 'PRIORITY_MAIL_EXPRESS')
 *     Options: 'PRIORITY_MAIL_EXPRESS', 'PRIORITY_MAIL', 'USPS_GROUND_ADVANTAGE',
 *              'MEDIA_MAIL', 'LIBRARY_MAIL', 'BOUND_PRINTED_MATTER'
 *   - string -processing_category Processing category (default: 'MACHINABLE')
 *     Options: 'MACHINABLE', 'NON_MACHINABLE', 'IRREGULAR'
 *   - string shipfromcompany Sender company name (max 26 chars)
 *   - string shipfromaddress2 Sender secondary address (max 50 chars)
 *   - string shipfromtelephone Sender phone (10 digits, formatting removed)
 *   - string shiptocompany Recipient company name (max 26 chars)
 *   - string shiptoaddress2 Recipient secondary address (max 50 chars)
 *   - string shiptotelephone Recipient phone (10 digits, formatting removed)
 *   - string shiptoemail Recipient email address
 *   - float insured_amount Insurance amount in dollars (adds INSURANCE service)
 *   - string label_type Label image format (default: 'PDF')
 *     Options: 'PDF', 'PNG', 'ZPLII', '4X6PDF', '4X6ZPLII'
 *
 * @return array Label data or error
 *   Success:
 *   - string tracking_number USPS tracking number
 *   - string label_image Base64-encoded label image (decode and save to file)
 *   - string carrier Always 'USPS'
 *   - string method Mail class used
 *   - array label_metadata Label metadata from USPS
 *   - string sku SKU identifier
 *   - float postage Postage amount charged
 *   - array params Input parameters
 *   - array request_data Request sent to API
 *   - array result Full API response
 *
 *   Error:
 *   - string error Error message
 *   - array result Full API response
 *
 * @since 1.0.0
 * @link https://developers.usps.com/domesticlabelsv3 Domestic Labels v3 API Documentation
 *
 * @example
 * $label = uspsExpressMailLabel(array(
 *     '-client_id' => 'your_consumer_key',
 *     '-client_secret' => 'your_consumer_secret',
 *     '-payment_account_number' => 'your_account_number',
 *     '-mail_class' => 'PRIORITY_MAIL',
 *
 *     'shipfromfirstname' => 'John',
 *     'shipfromlastname' => 'Doe',
 *     'shipfromaddress1' => '123 Main St',
 *     'shipfromcity' => 'Los Angeles',
 *     'shipfromstate' => 'CA',
 *     'shipfromzipcode' => '90210',
 *
 *     'shiptofirstname' => 'Jane',
 *     'shiptolastname' => 'Smith',
 *     'shiptoaddress1' => '456 Broadway',
 *     'shiptocity' => 'New York',
 *     'shiptostate' => 'NY',
 *     'shiptozipcode' => '10001',
 *
 *     'weight' => 16,
 *     'length' => 10,
 *     'width' => 8,
 *     'height' => 6,
 *
 *     'insured_amount' => 100,
 *     'label_type' => 'PDF'
 * ));
 *
 * if (isset($label['tracking_number'])) {
 *     // Save label to file
 *     $label_data = base64_decode($label['label_image']);
 *     file_put_contents('label_' . $label['tracking_number'] . '.pdf', $label_data);
 *     echo "Label created with tracking: " . $label['tracking_number'];
 * }
 */
function uspsExpressMailLabel($params = array()) {
	// Validate required parameters
	if (!isset($params['-client_id'])) {
		return array('error' => 'Missing required parameter: -client_id');
	}
	if (!isset($params['-client_secret'])) {
		return array('error' => 'Missing required parameter: -client_secret');
	}
	if (!isset($params['-payment_account_number'])) {
		return array('error' => 'Missing required parameter: -payment_account_number');
	}

	// Validate required address fields
	$required_fields = array(
		'shipfromfirstname', 'shipfromlastname', 'shipfromaddress1',
		'shipfromcity', 'shipfromstate', 'shipfromzipcode',
		'shiptofirstname', 'shiptolastname', 'shiptoaddress1',
		'shiptocity', 'shiptostate', 'shiptozipcode',
		'weight', 'length', 'width', 'height'
	);

	foreach ($required_fields as $field) {
		if (!isset($params[$field]) || empty($params[$field])) {
			return array('error' => "Missing required parameter: {$field}");
		}
	}

	$endpoint = '/labels/v3/label';

	// Build request data
	$request_data = array(
		'imageInfo' => array(
			'imageType' => isset($params['label_type']) ? $params['label_type'] : 'PDF'
		),
		'labelInformation' => array(
			'mailClass' => isset($params['-mail_class']) ? $params['-mail_class'] : 'PRIORITY_MAIL_EXPRESS',
			'processingCategory' => isset($params['-processing_category']) ? $params['-processing_category'] : 'MACHINABLE'
		),
		'fromAddress' => array(
			'firstName' => substr($params['shipfromfirstname'], 0, 26),
			'lastName' => substr($params['shipfromlastname'], 0, 26),
			'streetAddress' => substr($params['shipfromaddress1'], 0, 50),
			'city' => substr($params['shipfromcity'], 0, 28),
			'state' => substr($params['shipfromstate'], 0, 2),
			'ZIPCode' => substr($params['shipfromzipcode'], 0, 5)
		),
		'toAddress' => array(
			'firstName' => substr($params['shiptofirstname'], 0, 26),
			'lastName' => substr($params['shiptolastname'], 0, 26),
			'streetAddress' => substr($params['shiptoaddress1'], 0, 50),
			'city' => substr($params['shiptocity'], 0, 28),
			'state' => substr($params['shiptostate'], 0, 2),
			'ZIPCode' => substr($params['shiptozipcode'], 0, 5)
		),
		'packageDescription' => array(
			'weight' => (float)$params['weight'],
			'length' => (float)$params['length'],
			'height' => (float)$params['height'],
			'width' => (float)$params['width']
		),
		'paymentInfo' => array(
			'paymentMethod' => 'PERMIT_IMPRINT',
			'accountNumber' => $params['-payment_account_number']
		)
	);

	// Add optional fields
	if (isset($params['shipfromcompany']) && !empty($params['shipfromcompany'])) {
		$request_data['fromAddress']['firm'] = substr($params['shipfromcompany'], 0, 26);
	}
	if (isset($params['shipfromaddress2']) && !empty($params['shipfromaddress2'])) {
		$request_data['fromAddress']['secondaryAddress'] = substr($params['shipfromaddress2'], 0, 50);
	}
	if (isset($params['shipfromtelephone']) && !empty($params['shipfromtelephone'])) {
		$request_data['fromAddress']['phone'] = preg_replace('/[^0-9]/', '', $params['shipfromtelephone']);
	}

	if (isset($params['shiptocompany']) && !empty($params['shiptocompany'])) {
		$request_data['toAddress']['firm'] = substr($params['shiptocompany'], 0, 26);
	}
	if (isset($params['shiptoaddress2']) && !empty($params['shiptoaddress2'])) {
		$request_data['toAddress']['secondaryAddress'] = substr($params['shiptoaddress2'], 0, 50);
	}
	if (isset($params['shiptotelephone']) && !empty($params['shiptotelephone'])) {
		$request_data['toAddress']['phone'] = preg_replace('/[^0-9]/', '', $params['shiptotelephone']);
	}
	if (isset($params['shiptoemail']) && !empty($params['shiptoemail'])) {
		$request_data['toAddress']['email'] = $params['shiptoemail'];
	}

	// Add insurance if provided
	if (isset($params['insured_amount']) && $params['insured_amount'] > 0) {
		$request_data['extraServices'] = array('INSURANCE');
		$request_data['packageDescription']['insuredValue'] = (float)$params['insured_amount'];
	}

	// Make API request
	$result = uspsApiRequest($endpoint, $params, 'POST', $request_data);

	$rtn = array(
		'params' => $params,
		'carrier' => 'USPS',
		'method' => isset($params['-mail_class']) ? $params['-mail_class'] : 'PRIORITY_MAIL_EXPRESS',
		'request_data' => $request_data
	);

	if (isset($result['error'])) {
		$rtn['error'] = $result['error'];
		$rtn['result'] = $result;
		return $rtn;
	}

	// Parse response
	if (isset($result['data']['labelImage'])) {
		$rtn['label_image'] = $result['data']['labelImage'];
	}
	if (isset($result['data']['trackingNumber'])) {
		$rtn['tracking_number'] = $result['data']['trackingNumber'];
	}
	if (isset($result['data']['labelMetadata'])) {
		$rtn['label_metadata'] = $result['data']['labelMetadata'];
	}
	if (isset($result['data']['SKU'])) {
		$rtn['sku'] = $result['data']['SKU'];
	}
	if (isset($result['data']['postage'])) {
		$rtn['postage'] = $result['data']['postage'];
	}

	$rtn['result'] = $result;

	return $rtn;
}

?>
