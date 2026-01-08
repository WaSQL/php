<?php
/**
 * Canada Post API Integration Library
 *
 * Integration methods for integrating with Canada Post web services.
 * This library implements the latest Canada Post production APIs for rating, shipping, tracking, and manifest services.
 *
 * API Versions:
 * - Rating API: v4 (current as of 2019)
 * - Contract Shipping API: v8 (current as of 2016)
 * - Non-Contract Shipping API: v4
 * - Tracking API: v2
 * - Manifest API: v8
 *
 * Authentication:
 * All API calls use HTTP Basic Authentication with Base64-encoded credentials.
 * You must obtain API credentials from the Canada Post Developer Program:
 * https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/gettingstarted.jsf
 *
 * Environment URLs:
 * - Development: https://ct.soa-gw.canadapost.ca
 * - Production: https://soa-gw.canadapost.ca
 *
 * References:
 * - Developer Program: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/default.jsf
 * - API Versioning: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/versioning.jsf
 * - Rating Service: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/rating/getrates/default.jsf
 * - Shipping Service: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/createshipment.jsf
 * - Manifest Service: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/manifest.jsf
 *
 * @package    CanadaPost
 * @version    2.0.0
 * @author     WaSQL
 * @license    MIT
 * @link       https://github.com/WaSQL/php
 */

/* ============================================================================
 * NON-CONTRACT SHIPPING API (v4)
 * ============================================================================
 * Non-contract shipments use credit card payment instead of a contract account.
 * API Reference: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/onestepshipping/default.jsf
 * ============================================================================ */

/**
 * Create a non-contract shipment
 *
 * Creates a new non-contract shipment using credit card payment method.
 * This endpoint is used when you don't have a contract account with Canada Post.
 *
 * @param array $params Parameters for creating the shipment:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'sender_*' fields (name, address, city, state, country, zipcode, phone)
 *                      - 'recipient_*' fields (name, address, city, state, country, zipcode, email)
 *                      - 'parcel_weight' (float) Weight in kg
 *                      - 'parcel_length' (float) Length in cm
 *                      - 'parcel_width' (float) Width in cm
 *                      - 'parcel_height' (float) Height in cm
 *                      - '-service_code' (string) Service code (e.g., DOM.EP, DOM.XP, DOM.RP, DOM.PC)
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns shipment information including tracking PIN and label URL, or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/onestepshipping/createshipment.jsf
 *
 * @todo Implement Non-Contract Shipping API v4
 */
function cpCreateNCShipment($params=array()){
	// TODO: Implement Non-Contract Shipping API v4
	// Endpoint: POST https://soa-gw.canadapost.ca/rs/ncshipment
	// Media Type: application/vnd.cpc.ncshipment-v4+xml
	return null;
}

/**
 * Get non-contract shipment information
 *
 * Retrieves information for a previously created non-contract shipment.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'shipment_id' (string) The shipment ID returned from creation
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns shipment information or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/onestepshipping/shipment.jsf
 *
 * @todo Implement Non-Contract Shipping API v4
 */
function cpGetNCShipment($params=array()){
	// TODO: Implement Non-Contract Shipping API v4
	// Endpoint: GET https://soa-gw.canadapost.ca/rs/ncshipment/{shipment-id}
	// Media Type: application/vnd.cpc.ncshipment-v4+xml
	return null;
}

/**
 * Get non-contract shipment details
 *
 * Retrieves detailed information about a non-contract shipment.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'shipment_id' (string) The shipment ID
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns detailed shipment information or error details
 *
 * @todo Implement Non-Contract Shipping API v4
 */
function cpGetNCShipmentDetails($params=array()){
	// TODO: Implement Non-Contract Shipping API v4
	return null;
}

/**
 * Get non-contract shipment receipt
 *
 * Retrieves the receipt for a non-contract shipment.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'receipt_url' (string) Receipt URL returned from shipment creation
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|string Returns receipt information or PDF content
 *
 * @todo Implement Non-Contract Shipping API v4
 */
function cpGetNCShipmentReceipt($params=array()){
	// TODO: Implement Non-Contract Shipping API v4
	return null;
}

/**
 * Get list of non-contract shipments
 *
 * Retrieves a list of non-contract shipments for the authenticated user.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *                      - 'from_date' (string) Filter shipments from this date
 *                      - 'to_date' (string) Filter shipments to this date
 *
 * @return array|null Returns array of shipment information or error details
 *
 * @todo Implement Non-Contract Shipping API v4
 */
function cpGetNCShipments($params=array()){
	// TODO: Implement Non-Contract Shipping API v4
	return null;
}
/* ============================================================================
 * LABEL AND ARTIFACT RETRIEVAL
 * ============================================================================ */

/**
 * Get shipment label (artifact)
 *
 * Downloads and saves the shipping label PDF from Canada Post.
 * The label URL is returned from the cpCreateShipment() function.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'label_url' (string) The artifact/label URL from shipment creation response
 *                      Optional:
 *                      - 'label_path' (string) Custom path to save labels (default: ./labels/)
 *
 * @return string|array|false Returns:
 *                            - String: Absolute file path to saved PDF label on success
 *                            - Array: Error details if parameters missing or XML error response
 *                            - False: If file could not be saved
 *
 * @example
 * $label_file = cpGetShipmentLabel([
 *     '-username' => 'your_username',
 *     '-password' => 'your_password',
 *     'label_url' => $shipment['artifact_url']
 * ]);
 * if (is_string($label_file)) {
 *     echo "Label saved to: " . $label_file;
 * }
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/artifact.jsf
 */
function cpGetShipmentLabel($params=array()){
	// Check for required params
	$required = array('-username', '-password', 'label_url');
	$missing = array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){
			$missing[] = $key;
		}
	}
	if(count($missing)){
		return array(
			'-error' => 'Missing required parameters',
			'-missing' => $missing,
			'-function' => __FUNCTION__
		);
	}

	$progpath = dirname(__FILE__);
	$label_path = isset($params['label_path']) ? $params['label_path'] : "{$progpath}/labels";
	if(!is_dir($label_path)){
		if(function_exists('buildDir')){
			buildDir($label_path);
		} else {
			mkdir($label_path, 0755, true);
		}
	}

	$label_file = 'shipmentArtifact_' . sha1($params['label_url']) . '.pdf';
	$label_afile = "{$label_path}/{$label_file}";

	// Remove old file if exists
	if(is_file($label_afile)){
		unlink($label_afile);
	}

	$curl = curl_init($params['label_url']);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/pdf', 'Accept-Language: en-CA'));
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);

	$curl_response = curl_exec($curl);
	$curl_error = curl_error($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
	curl_close($curl);

	// Handle cURL errors
	if($curl_error){
		return array(
			'-error' => 'cURL error: ' . $curl_error,
			'-http_code' => $http_code,
			'-function' => __FUNCTION__
		);
	}

	// Handle non-200 responses
	if($http_code != 200){
		return array(
			'-error' => 'HTTP error ' . $http_code,
			'-http_code' => $http_code,
			'-response' => $curl_response,
			'-function' => __FUNCTION__
		);
	}

	// Handle PDF response
	if(strpos($contentType, 'application/pdf') !== false){
		file_put_contents($label_afile, $curl_response);
		if(is_file($label_afile)){
			return $label_afile;
		}
		return false;
	}

	// Handle XML error response
	if(strpos($contentType, 'xml') !== false){
		if(function_exists('xml2Array')){
			return xml2Array($curl_response);
		}
		return array(
			'-error' => 'XML error response received',
			'-xml' => $curl_response,
			'-function' => __FUNCTION__
		);
	}

	// Unexpected response type
	return array(
		'-error' => 'Unexpected content type: ' . $contentType,
		'-response' => $curl_response,
		'-function' => __FUNCTION__
	);
}
/* ============================================================================
 * RATING API (v4)
 * ============================================================================
 * Get shipping rates, delivery dates, and service options.
 * API Reference: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/rating/getrates/default.jsf
 * ============================================================================ */

/**
 * Get Canada Post shipping rates
 *
 * Returns a list of available shipping services with prices, taxes, and estimated delivery times
 * for a given parcel. Uses the Rating API v4.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - '-account_number' (string) Canada Post customer number
 *                      - 'sender_zipcode' (string) Origin postal code (e.g., K1A0B1)
 *                      - 'recipient_zipcode' (string) Destination postal code
 *                      - 'parcel_weight' (float) Parcel weight in kilograms
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true (default: false - production)
 *                      - '-shipping_point' (string) Postal code for shipping point (defaults to sender_zipcode)
 *                      - 'parcel_length' (float) Length in cm
 *                      - 'parcel_width' (float) Width in cm
 *                      - 'parcel_height' (float) Height in cm
 *                      - 'destination_country' (string) Country code (CA, US, or other for international)
 *
 * @return array|null Returns array of rates with service information, or null on failure.
 *                    Each rate contains:
 *                    - 'code' (string) Service code (e.g., DOM.RP, DOM.EP, DOM.XP, DOM.PC)
 *                    - 'name' (string) Service name (e.g., "Regular Parcel", "Xpresspost")
 *                    - 'base' (float) Base cost before taxes
 *                    - 'total' (float) Total cost including taxes
 *                    - 'gst' (float) GST amount (if applicable)
 *                    - 'gst_percent' (float) GST percentage
 *                    - 'pst' (float) PST amount (if applicable)
 *                    - 'pst_percent' (float) PST percentage
 *                    - 'hst' (float) HST amount (if applicable)
 *                    - 'hst_percent' (float) HST percentage
 *                    - 'taxes_total' (float) Total of all taxes
 *                    - 'taxes_total_percent' (float) Total tax percentage
 *                    - 'expected_delivery_date' (string) Expected delivery date (YYYY-MM-DD)
 *                    - 'expected_delivery_date_utime' (int) Unix timestamp of delivery date
 *                    - 'expected_transit_time' (int) Transit time in days
 *
 * @example
 * $rates = cpGetRates([
 *     '-username' => 'your_username',
 *     '-password' => 'your_password',
 *     '-account_number' => '1234567890',
 *     'sender_zipcode' => 'K1A0B1',
 *     'recipient_zipcode' => 'M5W1E6',
 *     'parcel_weight' => 2.5
 * ]);
 * foreach ($rates as $rate) {
 *     echo "{$rate['name']}: \${$rate['total']} - Delivery: {$rate['expected_delivery_date']}\n";
 * }
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/rating/getrates/default.jsf
 */
function cpGetRates($params=array()){
	// Check for required params
	$required = array(
		'-username', '-password', '-account_number',
		'sender_zipcode', 'recipient_zipcode', 'parcel_weight'
	);
	$missing = array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){
			$missing[] = $key;
		}
	}
	if(count($missing)){
		return array(
			'-error' => 'Missing required parameters',
			'-missing' => $missing,
			'-function' => __FUNCTION__
		);
	}

	// Set environment URL
	if(isset($params['-test']) && $params['-test']){
		$params['-service_url'] = 'https://ct.soa-gw.canadapost.ca/rs/ship/price';
	} else {
		$params['-service_url'] = 'https://soa-gw.canadapost.ca/rs/ship/price';
	}

	// Default shipping point to sender_zipcode
	if(!isset($params['-shipping_point']) || !strlen($params['-shipping_point'])){
		$params['-shipping_point'] = $params['sender_zipcode'];
	}

	// Determine destination type (domestic, US, or international)
	$destination_country = isset($params['destination_country']) ? strtoupper($params['destination_country']) : 'CA';

	// Build destination XML based on country
	if($destination_country == 'CA'){
		$destination_xml = "<domestic><postal-code>{$params['recipient_zipcode']}</postal-code></domestic>";
	} elseif($destination_country == 'US'){
		$destination_xml = "<united-states><zip-code>{$params['recipient_zipcode']}</zip-code></united-states>";
	} else {
		$destination_xml = "<international><country-code>{$destination_country}</country-code></international>";
	}

	// Build parcel characteristics XML
	$parcel_xml = "<weight>{$params['parcel_weight']}</weight>";
	if(isset($params['parcel_length']) && isset($params['parcel_width']) && isset($params['parcel_height'])){
		$parcel_xml .= "
    <dimensions>
      <length>{$params['parcel_length']}</length>
      <width>{$params['parcel_width']}</width>
      <height>{$params['parcel_height']}</height>
    </dimensions>";
	}

	// Build XML request using Rating API v4
	$params['-xml'] = <<<CANADAPOSTXML
<?xml version="1.0" encoding="UTF-8"?>
<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v4">
  <customer-number>{$params['-account_number']}</customer-number>
  <parcel-characteristics>
    {$parcel_xml}
  </parcel-characteristics>
  <origin-postal-code>{$params['-shipping_point']}</origin-postal-code>
  <destination>
    {$destination_xml}
  </destination>
</mailing-scenario>
CANADAPOSTXML;

	$progpath = dirname(__FILE__);
	$curl = curl_init($params['-service_url']);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params['-xml']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/vnd.cpc.ship.rate-v4+xml',
		'Accept: application/vnd.cpc.ship.rate-v4+xml',
		'Accept-language: en-CA'
	));
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);

	$curl_response = curl_exec($curl);
	$curl_error = curl_error($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	// Handle cURL errors
	if($curl_error){
		return array(
			'-error' => 'cURL error: ' . $curl_error,
			'-http_code' => $http_code,
			'-function' => __FUNCTION__
		);
	}

	// Parse XML response
	if(function_exists('xml2Array')){
		$xml_array = xml2Array($curl_response);
	} else {
		return array(
			'-error' => 'xml2Array function not available',
			'-function' => __FUNCTION__
		);
	}

	// Check for API errors
	if(isset($xml_array['messages'])){
		return array(
			'-error' => 'API returned error messages',
			'-messages' => $xml_array['messages'],
			'-http_code' => $http_code,
			'-function' => __FUNCTION__
		);
	}

	// Check for valid price quotes
	if(!isset($xml_array['price-quotes']['price-quote'])){
		return array(
			'-error' => 'No price quotes returned',
			'-response' => $xml_array,
			'-http_code' => $http_code,
			'-function' => __FUNCTION__
		);
	}

	// Handle single quote vs multiple quotes
	$quotes = $xml_array['price-quotes']['price-quote'];
	if(!isset($quotes[0])){
		$quotes = array($quotes);
	}

	// Parse rates
	$rates = array();
	foreach($quotes as $quote){
		$service_code = $quote['service-code'];

		$rates[$service_code] = array(
			'code' => $service_code,
			'name' => $quote['service-name'],
			'base' => isset($quote['price-details']['base']) ? floatval($quote['price-details']['base']) : 0,
			'total' => isset($quote['price-details']['due']) ? floatval($quote['price-details']['due']) : 0,
		);

		// Add delivery date if available
		if(isset($quote['service-standard']['expected-delivery-date'])){
			$rates[$service_code]['expected_delivery_date'] = $quote['service-standard']['expected-delivery-date'];
			$rates[$service_code]['expected_delivery_date_utime'] = strtotime($quote['service-standard']['expected-delivery-date']);
		}

		// Add transit time if available
		if(isset($quote['service-standard']['expected-transit-time'])){
			$rates[$service_code]['expected_transit_time'] = intval($quote['service-standard']['expected-transit-time']);
		}

		// Parse taxes
		$taxes_total = 0;
		$taxes_total_percent = 0;
		$tax_types = array('gst', 'pst', 'hst');

		foreach($tax_types as $type){
			if(isset($quote['price-details']['taxes'][$type])){
				$tax_value = floatval($quote['price-details']['taxes'][$type]);
				if($tax_value > 0){
					$rates[$service_code][$type] = $tax_value;
					$taxes_total += $tax_value;

					if(isset($quote['price-details']['taxes']["{$type}_attr"]['percent'])){
						$tax_percent = floatval($quote['price-details']['taxes']["{$type}_attr"]['percent']);
						$rates[$service_code]["{$type}_percent"] = $tax_percent;
						$taxes_total_percent += $tax_percent;
					}
				}
			}
		}

		$rates[$service_code]['taxes_total'] = $taxes_total;
		$rates[$service_code]['taxes_total_percent'] = $taxes_total_percent;
	}

	// Sort by total cost (ascending) and delivery date (soonest first)
	if(function_exists('sortArrayByKeys')){
		$rates = sortArrayByKeys($rates, array('total' => SORT_ASC, 'expected_delivery_date_utime' => SORT_ASC));
	} else {
		uasort($rates, function($a, $b){
			if($a['total'] != $b['total']){
				return $a['total'] < $b['total'] ? -1 : 1;
			}
			if(isset($a['expected_delivery_date_utime']) && isset($b['expected_delivery_date_utime'])){
				return $a['expected_delivery_date_utime'] < $b['expected_delivery_date_utime'] ? -1 : 1;
			}
			return 0;
		});
	}

	return $rates;
}
/* ============================================================================
 * CONTRACT SHIPPING API (v8)
 * ============================================================================
 * Create shipments, get labels, and manage manifests for contract customers.
 * API Reference: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/createshipment.jsf
 * ============================================================================ */

/**
 * Create a contract shipment
 *
 * Creates a new shipment using your Canada Post contract account.
 * Returns shipment details including tracking PIN and links to labels and receipts.
 * Uses the Contract Shipping API v8.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - '-account_number' (string) Canada Post customer number (mailed-by customer)
 *                      - 'sender_company' (string) Sender company name
 *                      - 'sender_phone' (string) Sender phone number
 *                      - 'sender_address' (string) Sender street address
 *                      - 'sender_city' (string) Sender city
 *                      - 'sender_state' (string) Sender province/state code (e.g., ON, QC, BC)
 *                      - 'sender_country' (string) Sender country code (CA, US, etc.)
 *                      - 'sender_zipcode' (string) Sender postal code
 *                      - 'recipient_name' (string) Recipient name
 *                      - 'recipient_address' (string) Recipient street address
 *                      - 'recipient_city' (string) Recipient city
 *                      - 'recipient_state' (string) Recipient province/state code
 *                      - 'recipient_country' (string) Recipient country code
 *                      - 'recipient_zipcode' (string) Recipient postal code
 *                      - 'recipient_email' (string) Recipient email for notifications
 *                      - 'parcel_weight' (float) Weight in kg
 *                      - 'parcel_length' (float) Length in cm
 *                      - 'parcel_width' (float) Width in cm
 *                      - 'parcel_height' (float) Height in cm
 *                      - 'ordernumber' (string) Your order/reference number
 *                      Optional:
 *                      - '-test' (bool) Use test environment (default: false - production)
 *                      - '-mobo' (string) Mailed-on-behalf-of customer number (defaults to -account_number)
 *                      - '-service_code' (string) Service code (default: DOM.RP)
 *                            Common codes: DOM.RP (Regular Parcel), DOM.EP (Expedited Parcel),
 *                            DOM.XP (Xpresspost), DOM.PC (Priority), USA.EP, USA.XP, INT.XP, INT.IP.AIR
 *                      - '-group_id' (string) Group ID for manifest (default: YYYYMMDD)
 *                      - '-shipdate' (string) Expected mailing date (default: today, format: YYYY-MM-DD)
 *                      - '-contract_id' (string) Contract ID for payment
 *                      - '-payment_method' (string) Account or CreditCard (default: Account)
 *                      - '-output_format' (string) Label format: 8.5x11 or 4x6 (default: 8.5x11)
 *                      - '-shipping_point' (string) Shipping point postal code (default: sender_zipcode)
 *                      - 'sender_name' (string) Sender name
 *                      - 'notify_email' (string) Notification email (default: recipient_email)
 *                      - 'message' (string) Custom message on label
 *                      - 'cost_centre' (string) Cost centre reference
 *                      - 'recipient_company' (string) Recipient company name
 *                      - 'recipient_phone' (string) Recipient phone number
 *                      - '-transmit_shipment' (bool) If true, transmit immediately without manifest
 *                      - '-provide_pricing' (bool) Include pricing in response
 *                      - '-provide_receipt' (bool) Include receipt in response
 *
 * @return array Returns array with shipment information and URLs, or error details:
 *               Success response includes:
 *               - 'shipment-id' (string) Unique shipment identifier
 *               - 'shipment-status' (string) Status (created, transmitted, etc.)
 *               - 'tracking-pin' (string) Tracking number
 *               - 'artifact_url' (string) URL to retrieve shipping label PDF
 *               - 'self_url' (string) URL to retrieve shipment details
 *               - 'price_url' (string) URL to retrieve pricing (if requested)
 *               - 'receipt_url' (string) URL to retrieve receipt (if requested)
 *               - All input parameters for reference
 *               Error response includes:
 *               - '-error' (string) Error description
 *               - '-missing' (array) Missing required parameters (if applicable)
 *               - '-http_code' (int) HTTP status code
 *               - '-messages' (array) API error messages (if applicable)
 *
 * @example
 * $shipment = cpCreateShipment([
 *     '-username' => 'your_username',
 *     '-password' => 'your_password',
 *     '-account_number' => '1234567890',
 *     '-service_code' => 'DOM.EP',
 *     'sender_company' => 'My Company',
 *     'sender_phone' => '555-1234',
 *     'sender_address' => '123 Main St',
 *     'sender_city' => 'Ottawa',
 *     'sender_state' => 'ON',
 *     'sender_country' => 'CA',
 *     'sender_zipcode' => 'K1A0B1',
 *     'recipient_name' => 'John Doe',
 *     'recipient_address' => '456 Queen St',
 *     'recipient_city' => 'Toronto',
 *     'recipient_state' => 'ON',
 *     'recipient_country' => 'CA',
 *     'recipient_zipcode' => 'M5W1E6',
 *     'recipient_email' => 'john@example.com',
 *     'parcel_weight' => 2.5,
 *     'parcel_length' => 30,
 *     'parcel_width' => 20,
 *     'parcel_height' => 10,
 *     'ordernumber' => 'ORD-12345'
 * ]);
 * if (isset($shipment['tracking-pin'])) {
 *     echo "Tracking: " . $shipment['tracking-pin'];
 *     $label = cpGetShipmentLabel([
 *         '-username' => 'your_username',
 *         '-password' => 'your_password',
 *         'label_url' => $shipment['artifact_url']
 *     ]);
 * }
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/createshipment.jsf
 */
function cpCreateShipment($params=array()){
	// Check for required params
	$required = array(
		'-username', '-password', '-account_number',
		'sender_company', 'sender_phone', 'sender_address', 'sender_city', 'sender_state', 'sender_country', 'sender_zipcode',
		'recipient_name', 'recipient_address', 'recipient_city', 'recipient_state', 'recipient_country', 'recipient_zipcode', 'recipient_email',
		'parcel_weight', 'parcel_width', 'parcel_length', 'parcel_height',
		'ordernumber'
	);
	$missing = array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){
			$missing[] = $key;
		}
	}
	if(count($missing)){
		return array(
			'-error' => 'Missing required parameters',
			'-missing' => $missing,
			'-function' => __FUNCTION__
		);
	}

	// Set defaults
	if(!isset($params['-group_id']) || !strlen($params['-group_id'])){
		$params['-group_id'] = date('Ymd');
	}
	if(!isset($params['-shipdate']) || !strlen($params['-shipdate'])){
		$params['-shipdate'] = date('Y-m-d');
	}
	if(!isset($params['-contract_id']) || !strlen($params['-contract_id'])){
		$params['-contract_id'] = $params['-account_number'];
	}
	// Output format options: 8.5x11, 4x6
	if(!isset($params['-output_format']) || !strlen($params['-output_format'])){
		$params['-output_format'] = '8.5x11';
	}
	// Service codes: DOM.RP, DOM.EP, DOM.XP, DOM.PC, USA.EP, USA.XP, INT.XP, INT.IP.AIR
	if(!isset($params['-service_code']) || !strlen($params['-service_code'])){
		$params['-service_code'] = 'DOM.RP';
	}
	// Payment method: Account or CreditCard
	if(!isset($params['-payment_method']) || !strlen($params['-payment_method'])){
		$params['-payment_method'] = 'Account';
	}
	// Default shipping point to sender_zipcode
	if(!isset($params['-shipping_point']) || !strlen($params['-shipping_point'])){
		$params['-shipping_point'] = $params['sender_zipcode'];
	}
	// Notification email defaults to recipient_email
	if(!isset($params['notify_email']) || !strlen($params['notify_email'])){
		$params['notify_email'] = $params['recipient_email'];
	}
	// Default message
	if(!isset($params['message']) || !strlen($params['message'])){
		$params['message'] = 'Thank you for your order';
	}
	// Mailed-on-behalf-of defaults to account number
	if(!isset($params['-mobo']) || !strlen($params['-mobo'])){
		$params['-mobo'] = $params['-account_number'];
	}

	// Set environment URL
	if(isset($params['-test']) && $params['-test']){
		$params['-service_url'] = 'https://ct.soa-gw.canadapost.ca/rs/' . $params['-account_number'] . '/' . $params['-mobo'] . '/shipment';
	} else {
		$params['-service_url'] = 'https://soa-gw.canadapost.ca/rs/' . $params['-account_number'] . '/' . $params['-mobo'] . '/shipment';
	}

	// Build sender name XML if provided
	$sender_name_xml = '';
	if(isset($params['sender_name']) && strlen($params['sender_name'])){
		$sender_name_xml = "<name>" . htmlspecialchars($params['sender_name'], ENT_XML1, 'UTF-8') . "</name>";
	}

	// Build recipient company XML if provided
	$recipient_company_xml = '';
	if(isset($params['recipient_company']) && strlen($params['recipient_company'])){
		$recipient_company_xml = "<company>" . htmlspecialchars($params['recipient_company'], ENT_XML1, 'UTF-8') . "</company>";
	}

	// Build recipient phone XML if provided
	$recipient_phone_xml = '';
	if(isset($params['recipient_phone']) && strlen($params['recipient_phone'])){
		$recipient_phone_xml = "<contact-phone>" . htmlspecialchars($params['recipient_phone'], ENT_XML1, 'UTF-8') . "</contact-phone>";
	}

	// Build group-id or transmit-shipment element
	$shipment_mode_xml = '';
	if(isset($params['-transmit_shipment']) && $params['-transmit_shipment']){
		$shipment_mode_xml = "<transmit-shipment>true</transmit-shipment>";
	} else {
		$shipment_mode_xml = "<group-id>{$params['-group_id']}</group-id>";
	}

	// Build optional elements
	$optional_elements = '';
	if(isset($params['-provide_pricing']) && $params['-provide_pricing']){
		$optional_elements .= "\n  <provide-pricing-info>true</provide-pricing-info>";
	}
	if(isset($params['-provide_receipt']) && $params['-provide_receipt']){
		$optional_elements .= "\n  <provide-receipt-info>true</provide-receipt-info>";
	}

	// Escape XML special characters
	$sender_company = htmlspecialchars($params['sender_company'], ENT_XML1, 'UTF-8');
	$sender_phone = htmlspecialchars($params['sender_phone'], ENT_XML1, 'UTF-8');
	$sender_address = htmlspecialchars($params['sender_address'], ENT_XML1, 'UTF-8');
	$sender_city = htmlspecialchars($params['sender_city'], ENT_XML1, 'UTF-8');
	$recipient_name = htmlspecialchars($params['recipient_name'], ENT_XML1, 'UTF-8');
	$recipient_address = htmlspecialchars($params['recipient_address'], ENT_XML1, 'UTF-8');
	$recipient_city = htmlspecialchars($params['recipient_city'], ENT_XML1, 'UTF-8');
	$notify_email = htmlspecialchars($params['notify_email'], ENT_XML1, 'UTF-8');
	$message = htmlspecialchars($params['message'], ENT_XML1, 'UTF-8');
	$ordernumber = htmlspecialchars($params['ordernumber'], ENT_XML1, 'UTF-8');
	$cost_centre = isset($params['cost_centre']) ? htmlspecialchars($params['cost_centre'], ENT_XML1, 'UTF-8') : '';

	// Build XML request using Contract Shipping API v8
	$params['-xml'] = <<<CANADAPOSTXML
<?xml version="1.0" encoding="UTF-8"?>
<shipment xmlns="http://www.canadapost.ca/ws/shipment-v8">
  {$shipment_mode_xml}
  <requested-shipping-point>{$params['-shipping_point']}</requested-shipping-point>
  <cpc-pickup-indicator>true</cpc-pickup-indicator>
  <expected-mailing-date>{$params['-shipdate']}</expected-mailing-date>{$optional_elements}
  <delivery-spec>
    <service-code>{$params['-service_code']}</service-code>
    <sender>
      {$sender_name_xml}
      <company>{$sender_company}</company>
      <contact-phone>{$sender_phone}</contact-phone>
      <address-details>
        <address-line-1>{$sender_address}</address-line-1>
        <city>{$sender_city}</city>
        <prov-state>{$params['sender_state']}</prov-state>
        <country-code>{$params['sender_country']}</country-code>
        <postal-zip-code>{$params['sender_zipcode']}</postal-zip-code>
      </address-details>
    </sender>
    <destination>
      <name>{$recipient_name}</name>
      {$recipient_company_xml}
      {$recipient_phone_xml}
      <address-details>
        <address-line-1>{$recipient_address}</address-line-1>
        <city>{$recipient_city}</city>
        <prov-state>{$params['recipient_state']}</prov-state>
        <country-code>{$params['recipient_country']}</country-code>
        <postal-zip-code>{$params['recipient_zipcode']}</postal-zip-code>
      </address-details>
    </destination>
    <options>
      <option>
        <option-code>DC</option-code>
      </option>
    </options>
    <parcel-characteristics>
      <weight>{$params['parcel_weight']}</weight>
      <dimensions>
        <length>{$params['parcel_length']}</length>
        <width>{$params['parcel_width']}</width>
        <height>{$params['parcel_height']}</height>
      </dimensions>
      <unpackaged>false</unpackaged>
      <mailing-tube>false</mailing-tube>
    </parcel-characteristics>
    <notification>
      <email>{$notify_email}</email>
      <on-shipment>true</on-shipment>
      <on-exception>false</on-exception>
      <on-delivery>true</on-delivery>
    </notification>
    <print-preferences>
      <output-format>{$params['-output_format']}</output-format>
    </print-preferences>
    <preferences>
      <show-packing-instructions>false</show-packing-instructions>
      <show-postage-rate>false</show-postage-rate>
      <show-insured-value>true</show-insured-value>
    </preferences>
    <references>
      <cost-centre>{$cost_centre}</cost-centre>
      <customer-ref-1>{$message}</customer-ref-1>
      <customer-ref-2>OrderNumber: {$ordernumber}</customer-ref-2>
    </references>
    <settlement-info>
      <contract-id>{$params['-contract_id']}</contract-id>
      <intended-method-of-payment>{$params['-payment_method']}</intended-method-of-payment>
    </settlement-info>
  </delivery-spec>
</shipment>
CANADAPOSTXML;

	$progpath = dirname(__FILE__);
	$curl = curl_init($params['-service_url']);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params['-xml']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/vnd.cpc.shipment-v8+xml',
		'Accept: application/vnd.cpc.shipment-v8+xml',
		'Accept-language: en-CA'
	));
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);

	$curl_response = curl_exec($curl);
	$curl_error = curl_error($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	// Handle cURL errors
	if($curl_error){
		$params['-error'] = 'cURL error: ' . $curl_error;
		$params['-http_code'] = $http_code;
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	// Parse XML response
	if(function_exists('xml2Array')){
		$xml_array = xml2Array($curl_response);
	} else {
		$params['-error'] = 'xml2Array function not available';
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	// Check for API errors
	if(isset($xml_array['messages'])){
		$params['-error'] = 'API returned error messages';
		$params['-messages'] = $xml_array['messages'];
		$params['-http_code'] = $http_code;
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	// Check for shipment-info
	if(!isset($xml_array['shipment-info'])){
		$params['-error'] = 'No shipment-info in response';
		$params['-response'] = $xml_array;
		$params['-http_code'] = $http_code;
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	// Extract shipment information
	if(isset($xml_array['shipment-info']['shipment-id'])){
		$params['shipment-id'] = $xml_array['shipment-info']['shipment-id'];
	}
	if(isset($xml_array['shipment-info']['shipment-status'])){
		$params['shipment-status'] = $xml_array['shipment-info']['shipment-status'];
	}
	if(isset($xml_array['shipment-info']['tracking-pin'])){
		$params['tracking-pin'] = $xml_array['shipment-info']['tracking-pin'];
	}

	// Extract links (artifact, receipt, price, etc.)
	if(isset($xml_array['shipment-info']['links']['link'])){
		$links = $xml_array['shipment-info']['links']['link'];
		// Handle single link vs multiple links
		if(isset($links['rel'])){
			$links = array($links);
		}
		foreach($links as $link){
			if(isset($link['rel']) && isset($link['href'])){
				$key = $link['rel'];
				$params["{$key}_url"] = $link['href'];
			}
		}
	}

	return $params;
}

/* ============================================================================
 * CUSTOMER INFORMATION AND GROUPS
 * ============================================================================ */

/**
 * Get customer information
 *
 * Retrieves customer account information including available contracts and services.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - '-account_number' (string) Canada Post customer number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns customer information or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/customerstatus.jsf
 *
 * @todo Implement Customer Information API v1
 */
function cpGetCustomerInformation($params=array()){
	// TODO: Implement Customer Information API v1
	// Endpoint: GET https://soa-gw.canadapost.ca/rs/{customer}/{mobo}/customer
	// Media Type: application/vnd.cpc.customer-v1+xml
	return null;
}

/**
 * Get shipment groups
 *
 * Retrieves a list of shipment groups for the specified customer.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - '-account_number' (string) Canada Post customer number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns array of group IDs or error details
 *
 * @todo Implement Groups API
 */
function cpGetGroups($params=array()){
	// TODO: Implement Groups API
	// Endpoint: GET https://soa-gw.canadapost.ca/rs/{customer}/{mobo}/group
	return null;
}

/* ============================================================================
 * MANIFEST API (v8)
 * ============================================================================
 * Transmit shipments and create manifests for Canada Post pickup.
 * API Reference: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/manifest.jsf
 * ============================================================================ */

/**
 * Get manifest details
 *
 * Retrieves manifest information including PO number and links to artifact and details.
 * This is a mandatory call after cpTransmitShipments() to confirm manifest creation.
 * Uses Manifest API v8.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'manifest_url' (string) Manifest URL returned from cpTransmitShipments()
 *
 * @return array Returns array with manifest information and URLs, or error details:
 *               Success response includes:
 *               - 'po-number' (string) Canada Post PO number for the manifest
 *               - 'artifact_url' (string) URL to retrieve manifest PDF
 *               - 'details_url' (string) URL to retrieve detailed manifest information
 *               - All input parameters for reference
 *               Error response includes:
 *               - '-error' (string) Error description
 *               - '-missing' (array) Missing required parameters (if applicable)
 *               - '-http_code' (int) HTTP status code
 *
 * @example
 * $manifest = cpGetManifest([
 *     '-username' => 'your_username',
 *     '-password' => 'your_password',
 *     'manifest_url' => $transmit_result['manifest_url']
 * ]);
 * if (isset($manifest['po-number'])) {
 *     echo "Manifest PO: " . $manifest['po-number'];
 *     $artifact = cpGetManifestArtifact([
 *         '-username' => 'your_username',
 *         '-password' => 'your_password',
 *         'artifact_url' => $manifest['artifact_url']
 *     ]);
 * }
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/manifest.jsf
 */
function cpGetManifest($params=array()){
	// Check for required params
	$required = array('-username', '-password', 'manifest_url');
	$missing = array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){
			$missing[] = $key;
		}
	}
	if(count($missing)){
		return array(
			'-error' => 'Missing required parameters',
			'-missing' => $missing,
			'-function' => __FUNCTION__
		);
	}

	$progpath = dirname(__FILE__);
	$curl = curl_init($params['manifest_url']);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Accept: application/vnd.cpc.manifest-v8+xml',
		'Accept-language: en-CA'
	));
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);

	$curl_response = curl_exec($curl);
	$curl_error = curl_error($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	// Handle cURL errors
	if($curl_error){
		return array(
			'-error' => 'cURL error: ' . $curl_error,
			'-http_code' => $http_code,
			'-function' => __FUNCTION__
		);
	}

	// Handle non-200 responses
	if($http_code != 200){
		return array(
			'-error' => 'HTTP error ' . $http_code,
			'-http_code' => $http_code,
			'-response' => $curl_response,
			'-function' => __FUNCTION__
		);
	}

	// Parse XML response
	if(function_exists('xml2Array')){
		$xml_array = xml2Array($curl_response);
	} else {
		return array(
			'-error' => 'xml2Array function not available',
			'-function' => __FUNCTION__
		);
	}

	// Check for manifest data
	if(!isset($xml_array['manifest'])){
		return array(
			'-error' => 'No manifest data in response',
			'-response' => $xml_array,
			'-http_code' => $http_code,
			'-function' => __FUNCTION__
		);
	}

	// Extract PO number
	if(isset($xml_array['manifest']['po-number'])){
		$params['po-number'] = $xml_array['manifest']['po-number'];
	}

	// Extract links
	if(isset($xml_array['manifest']['links']['link'])){
		$links = $xml_array['manifest']['links']['link'];
		// Handle single link vs multiple links
		if(isset($links['rel'])){
			$links = array($links);
		}
		foreach($links as $link){
			if(isset($link['rel']) && isset($link['href'])){
				$key = $link['rel'];
				$params["{$key}_url"] = $link['href'];
			}
		}
	}

	return $params;
}
/**
 * Get manifest artifact (PDF)
 *
 * Downloads and saves the manifest PDF document from Canada Post.
 * The artifact URL is returned from the cpGetManifest() function.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'artifact_url' (string) The artifact URL from cpGetManifest() response
 *                      Optional:
 *                      - 'label_path' (string) Custom path to save manifests (default: ./labels/)
 *
 * @return string|array|false Returns:
 *                            - String: Absolute file path to saved PDF manifest on success
 *                            - Array: Error details if parameters missing or XML error response
 *                            - False: If file could not be saved
 *
 * @example
 * $manifest_file = cpGetManifestArtifact([
 *     '-username' => 'your_username',
 *     '-password' => 'your_password',
 *     'artifact_url' => $manifest['artifact_url']
 * ]);
 * if (is_string($manifest_file)) {
 *     echo "Manifest saved to: " . $manifest_file;
 * }
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/manifestartifact.jsf
 */
function cpGetManifestArtifact($params=array()){
	// Check for required params
	$required = array('-username', '-password', 'artifact_url');
	$missing = array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){
			$missing[] = $key;
		}
	}
	if(count($missing)){
		return array(
			'-error' => 'Missing required parameters',
			'-missing' => $missing,
			'-function' => __FUNCTION__
		);
	}

	$progpath = dirname(__FILE__);
	$label_path = isset($params['label_path']) ? $params['label_path'] : "{$progpath}/labels";
	if(!is_dir($label_path)){
		if(function_exists('buildDir')){
			buildDir($label_path);
		} else {
			mkdir($label_path, 0755, true);
		}
	}

	$label_file = 'manifestArtifact_' . sha1($params['artifact_url']) . '.pdf';
	$label_afile = "{$label_path}/{$label_file}";

	// Remove old file if exists
	if(is_file($label_afile)){
		unlink($label_afile);
	}

	$curl = curl_init($params['artifact_url']);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/pdf', 'Accept-Language: en-CA'));
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);

	$curl_response = curl_exec($curl);
	$curl_error = curl_error($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
	curl_close($curl);

	// Handle cURL errors
	if($curl_error){
		return array(
			'-error' => 'cURL error: ' . $curl_error,
			'-http_code' => $http_code,
			'-function' => __FUNCTION__
		);
	}

	// Handle non-200 responses
	if($http_code != 200){
		return array(
			'-error' => 'HTTP error ' . $http_code,
			'-http_code' => $http_code,
			'-response' => $curl_response,
			'-function' => __FUNCTION__
		);
	}

	// Handle PDF response
	if(strpos($contentType, 'application/pdf') !== false){
		file_put_contents($label_afile, $curl_response);
		if(is_file($label_afile)){
			return $label_afile;
		}
		return false;
	}

	// Handle XML error response
	if(strpos($contentType, 'xml') !== false){
		if(function_exists('xml2Array')){
			return xml2Array($curl_response);
		}
		return array(
			'-error' => 'XML error response received',
			'-xml' => $curl_response,
			'-function' => __FUNCTION__
		);
	}

	// Unexpected response type
	return array(
		'-error' => 'Unexpected content type: ' . $contentType,
		'-response' => $curl_response,
		'-function' => __FUNCTION__
	);
}

/**
 * Get list of manifests
 *
 * Retrieves a list of all manifests for the specified customer.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - '-account_number' (string) Canada Post customer number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *                      - '-mobo' (string) Mailed-on-behalf-of customer number
 *
 * @return array|null Returns array of manifest information or error details
 *
 * @todo Implement Manifests List API v8
 */
function cpGetManifests($params=array()){
	// TODO: Implement Manifests List API v8
	// Endpoint: GET https://soa-gw.canadapost.ca/rs/{customer}/{mobo}/manifest
	// Media Type: application/vnd.cpc.manifest-v8+xml
	return null;
}

/**
 * Get mailed-on-behalf-of customer information
 *
 * Retrieves information about a mailed-on-behalf-of (MOBO) customer.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - '-account_number' (string) Mailing customer number
 *                      - '-mobo' (string) Mailed-on-behalf-of customer number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns MOBO customer information or error details
 *
 * @todo Implement MOBO Customer Information API
 */
function cpGetMoBoCustomerInformation($params=array()){
	// TODO: Implement MOBO Customer Information API
	// Endpoint: GET https://soa-gw.canadapost.ca/rs/{customer}/{mobo}/mobo
	return null;
}

/**
 * Get shipment artifact
 *
 * Alias for cpGetShipmentLabel(). Retrieves the shipping label artifact.
 *
 * @param array $params Parameters - see cpGetShipmentLabel() for details
 *
 * @return string|array|false See cpGetShipmentLabel() return value
 *
 * @see cpGetShipmentLabel()
 */
function cpGetShipmentArtifact($params=array()){
	// Alias for cpGetShipmentLabel
	return cpGetShipmentLabel($params);
}

/**
 * Get shipment details
 *
 * Retrieves complete information for a specific shipment including status and links.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'shipment_url' (string) Shipment URL (self_url from creation) or full endpoint
 *                      Alternative:
 *                      - '-account_number', '-mobo', 'shipment-id' can be used instead of shipment_url
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns shipment details or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/shipment.jsf
 *
 * @todo Implement Get Shipment API v8
 */
function cpGetShipmentDetails($params=array()){
	// TODO: Implement Get Shipment API v8
	// Endpoint: GET https://soa-gw.canadapost.ca/rs/{customer}/{mobo}/shipment/{shipment-id}
	// Media Type: application/vnd.cpc.shipment-v8+xml
	return null;
}

/**
 * Get shipment price
 *
 * Retrieves pricing information for a specific shipment.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'price_url' (string) Price URL returned from shipment creation
 *
 * @return array|null Returns pricing details or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/shipmentprice.jsf
 *
 * @todo Implement Get Shipment Price API v8
 */
function cpGetShipmentPrice($params=array()){
	// TODO: Implement Get Shipment Price API v8
	// Endpoint: Retrieved from price_url link
	// Media Type: application/vnd.cpc.shipment-v8+xml
	return null;
}

/**
 * Get shipment receipt
 *
 * Retrieves receipt information for a specific shipment.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'receipt_url' (string) Receipt URL returned from shipment creation
 *
 * @return array|null Returns receipt details or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/shipmentreceipt.jsf
 *
 * @todo Implement Get Shipment Receipt API v8
 */
function cpGetShipmentReceipt($params=array()){
	// TODO: Implement Get Shipment Receipt API v8
	// Endpoint: Retrieved from receipt_url link
	// Media Type: application/vnd.cpc.shipment-v8+xml
	return null;
}

/**
 * Get list of shipments
 *
 * Retrieves a list of shipments for a specific group ID.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - '-account_number' (string) Canada Post customer number
 *                      - '-group_id' (string) Group ID to retrieve shipments for
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *                      - '-mobo' (string) Mailed-on-behalf-of customer number
 *
 * @return array|null Returns array of shipment information or error details
 *
 * @todo Implement Get Shipments API v8
 */
function cpGetShipments($params=array()){
	// TODO: Implement Get Shipments API v8
	// Endpoint: GET https://soa-gw.canadapost.ca/rs/{customer}/{mobo}/group/{group-id}/shipments
	// Media Type: application/vnd.cpc.shipment-v8+xml
	return null;
}
/**
 * Transmit shipments and create manifest
 *
 * Transmits a group of shipments to Canada Post and initiates manifest creation.
 * After calling this function, you MUST call cpGetManifest() to complete the manifest.
 * Uses Manifest API v8.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - '-account_number' (string) Canada Post customer number
 *                      - 'sender_company' (string) Sender company name
 *                      - 'sender_phone' (string) Sender phone number
 *                      - 'sender_address' (string) Sender street address
 *                      - 'sender_city' (string) Sender city
 *                      - 'sender_state' (string) Sender province/state code
 *                      - 'sender_country' (string) Sender country code
 *                      - 'sender_zipcode' (string) Sender postal code
 *                      Optional:
 *                      - '-test' (bool) Use test environment (default: false - production)
 *                      - '-mobo' (string) Mailed-on-behalf-of customer number (defaults to -account_number)
 *                      - '-group_id' (string) Group ID for manifest (default: YYYYMMDD)
 *                      - '-shipping_point' (string) Shipping point postal code (default: sender_zipcode)
 *
 * @return array Returns array with manifest URL, or error details:
 *               Success response includes:
 *               - 'manifest_url' (string) URL to call cpGetManifest() with
 *               - All input parameters for reference
 *               Error response includes:
 *               - '-error' (string) Error description
 *               - '-missing' (array) Missing required parameters (if applicable)
 *               - '-http_code' (int) HTTP status code
 *
 * @example
 * $transmit = cpTransmitShipments([
 *     '-username' => 'your_username',
 *     '-password' => 'your_password',
 *     '-account_number' => '1234567890',
 *     '-group_id' => '20260107',
 *     'sender_company' => 'My Company',
 *     'sender_phone' => '555-1234',
 *     'sender_address' => '123 Main St',
 *     'sender_city' => 'Ottawa',
 *     'sender_state' => 'ON',
 *     'sender_country' => 'CA',
 *     'sender_zipcode' => 'K1A0B1'
 * ]);
 * if (isset($transmit['manifest_url'])) {
 *     $manifest = cpGetManifest([
 *         '-username' => 'your_username',
 *         '-password' => 'your_password',
 *         'manifest_url' => $transmit['manifest_url']
 *     ]);
 * }
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/manifest.jsf
 */
function cpTransmitShipments($params=array()){
	// Check for required params
	$required = array(
		'-username', '-password', '-account_number',
		'sender_zipcode', 'sender_address', 'sender_city', 'sender_state', 'sender_country', 'sender_company'
	);
	$missing = array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){
			$missing[] = $key;
		}
	}
	if(count($missing)){
		return array(
			'-error' => 'Missing required parameters',
			'-missing' => $missing,
			'-function' => __FUNCTION__
		);
	}

	// Set defaults
	if(!isset($params['-shipping_point']) || !strlen($params['-shipping_point'])){
		$params['-shipping_point'] = $params['sender_zipcode'];
	}
	if(!isset($params['-group_id']) || !strlen($params['-group_id'])){
		$params['-group_id'] = date('Ymd');
	}
	if(!isset($params['-mobo']) || !strlen($params['-mobo'])){
		$params['-mobo'] = $params['-account_number'];
	}
	// Default sender_phone if not provided
	if(!isset($params['sender_phone'])){
		$params['sender_phone'] = '';
	}

	// Set environment URL
	if(isset($params['-test']) && $params['-test']){
		$params['-service_url'] = 'https://ct.soa-gw.canadapost.ca/rs/' . $params['-account_number'] . '/' . $params['-mobo'] . '/manifest';
	} else {
		$params['-service_url'] = 'https://soa-gw.canadapost.ca/rs/' . $params['-account_number'] . '/' . $params['-mobo'] . '/manifest';
	}

	// Escape XML special characters
	$sender_company = htmlspecialchars($params['sender_company'], ENT_XML1, 'UTF-8');
	$sender_phone = htmlspecialchars($params['sender_phone'], ENT_XML1, 'UTF-8');
	$sender_address = htmlspecialchars($params['sender_address'], ENT_XML1, 'UTF-8');
	$sender_city = htmlspecialchars($params['sender_city'], ENT_XML1, 'UTF-8');

	// Build XML request using Manifest API v8
	$params['-xml'] = <<<CANADAPOSTXML
<?xml version="1.0" encoding="UTF-8"?>
<transmit-set xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.canadapost.ca/ws/manifest-v8">
  <group-ids>
    <group-id>{$params['-group_id']}</group-id>
  </group-ids>
  <requested-shipping-point>{$params['-shipping_point']}</requested-shipping-point>
  <cpc-pickup-indicator>true</cpc-pickup-indicator>
  <detailed-manifests>true</detailed-manifests>
  <method-of-payment>Account</method-of-payment>
  <manifest-address>
    <manifest-company>{$sender_company}</manifest-company>
    <phone-number>{$sender_phone}</phone-number>
    <address-details>
      <address-line-1>{$sender_address}</address-line-1>
      <city>{$sender_city}</city>
      <prov-state>{$params['sender_state']}</prov-state>
      <country-code>{$params['sender_country']}</country-code>
      <postal-zip-code>{$params['sender_zipcode']}</postal-zip-code>
    </address-details>
  </manifest-address>
</transmit-set>
CANADAPOSTXML;

	$progpath = dirname(__FILE__);
	$curl = curl_init($params['-service_url']);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params['-xml']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/vnd.cpc.manifest-v8+xml',
		'Accept: application/vnd.cpc.manifest-v8+xml',
		'Accept-language: en-CA'
	));
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);

	$curl_response = curl_exec($curl);
	$curl_error = curl_error($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	// Handle cURL errors
	if($curl_error){
		$params['-error'] = 'cURL error: ' . $curl_error;
		$params['-http_code'] = $http_code;
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	// Parse XML response
	if(function_exists('xml2Array')){
		$xml_array = xml2Array($curl_response);
	} else {
		$params['-error'] = 'xml2Array function not available';
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	// Check for API errors
	if(isset($xml_array['messages'])){
		$params['-error'] = 'API returned error messages';
		$params['-messages'] = $xml_array['messages'];
		$params['-http_code'] = $http_code;
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	// Check for manifests data
	if(!isset($xml_array['manifests'])){
		$params['-error'] = 'No manifests data in response';
		$params['-response'] = $xml_array;
		$params['-http_code'] = $http_code;
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	// Extract manifest URL
	if(isset($xml_array['manifests']['link_attr']['rel']) && $xml_array['manifests']['link_attr']['rel'] == 'manifest'){
		$params['manifest_url'] = $xml_array['manifests']['link_attr']['href'];
	} elseif(isset($xml_array['manifests']['link']['href'])){
		$params['manifest_url'] = $xml_array['manifests']['link']['href'];
	} else {
		$params['-error'] = 'Manifest URL not found in response';
		$params['-response'] = $xml_array;
		$params['-http_code'] = $http_code;
		$params['-function'] = __FUNCTION__;
		return $params;
	}

	return $params;
}

/* ============================================================================
 * SHIPMENT MANAGEMENT
 * ============================================================================ */

/**
 * Void a shipment
 *
 * Cancels a shipment that has not yet been transmitted.
 * Only shipments with status 'created' can be voided.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'shipment_id' (string) Shipment ID to void
 *                      - '-account_number' (string) Canada Post customer number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *                      - '-mobo' (string) Mailed-on-behalf-of customer number
 *
 * @return array|null Returns void confirmation or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/shippingmanifest/voidshipment.jsf
 *
 * @todo Implement Void Shipment API v8
 */
function cpVoidShipment($params=array()){
	// TODO: Implement Void Shipment API v8
	// Endpoint: DELETE https://soa-gw.canadapost.ca/rs/{customer}/{mobo}/shipment/{shipment-id}
	// Media Type: application/vnd.cpc.shipment-v8+xml
	return null;
}

/* ============================================================================
 * TRACKING API (v2)
 * ============================================================================
 * Track shipments and get delivery confirmation.
 * API Reference: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/tracking/default.jsf
 * ============================================================================ */

/**
 * Get delivery confirmation certificate
 *
 * Retrieves the delivery confirmation certificate (signature proof of delivery).
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'tracking_pin' (string) Tracking PIN/number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|string Returns certificate data or PDF, or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/tracking/delconfcertificate.jsf
 *
 * @todo Implement Delivery Confirmation API v2
 */
function cpGetDeliveryConfirmationCertificate($params=array()){
	// TODO: Implement Delivery Confirmation API v2
	// Endpoint: GET https://soa-gw.canadapost.ca/vis/track/pin/{tracking-pin}/certificate
	// Media Type: application/vnd.cpc.dcert-v2+xml or application/pdf
	return null;
}

/**
 * Get signature image
 *
 * Retrieves the signature image from proof of delivery.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'tracking_pin' (string) Tracking PIN/number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|string Returns image data or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/tracking/signatureimage.jsf
 *
 * @todo Implement Signature Image API v2
 */
function cpGetSignatureImage($params=array()){
	// TODO: Implement Signature Image API v2
	// Endpoint: GET https://soa-gw.canadapost.ca/vis/track/pin/{tracking-pin}/image
	// Media Type: image/*
	return null;
}

/**
 * Get tracking details
 *
 * Retrieves detailed tracking information including all scan events for a shipment.
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'tracking_pin' (string) Tracking PIN/number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns detailed tracking information with all events, or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/tracking/trackingdetails.jsf
 *
 * @todo Implement Tracking Details API v2
 */
function cpGetTrackingDetails($params=array()){
	// TODO: Implement Tracking Details API v2
	// Endpoint: GET https://soa-gw.canadapost.ca/vis/track/pin/{tracking-pin}/detail
	// Media Type: application/vnd.cpc.track+xml
	return null;
}

/**
 * Get tracking summary
 *
 * Retrieves summary tracking information for a shipment (current status only).
 *
 * @param array $params Parameters:
 *                      Required:
 *                      - '-username' (string) API username
 *                      - '-password' (string) API password
 *                      - 'tracking_pin' (string) Tracking PIN/number
 *                      Optional:
 *                      - '-test' (bool) Use test environment if true
 *
 * @return array|null Returns tracking summary with current status, or error details
 *
 * @link https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/tracking/trackingsummary.jsf
 *
 * @todo Implement Tracking Summary API v2
 */
function cpGetTrackingSummary($params=array()){
	// TODO: Implement Tracking Summary API v2
	// Endpoint: GET https://soa-gw.canadapost.ca/vis/track/pin/{tracking-pin}/summary
	// Media Type: application/vnd.cpc.track+xml
	return null;
}

?>