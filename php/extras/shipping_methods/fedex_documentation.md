# FedEx REST API v1 - Complete Documentation

## Overview

This library provides a complete PHP implementation of the FedEx REST API v1, replacing the deprecated SOAP-based Web Services API. It includes automatic OAuth 2.0 authentication with token caching, comprehensive error handling, and maintains backward compatibility with existing function signatures.

**Current Version:** 2.0.0
**API Version:** FedEx REST API v1
**Last Updated:** January 2026

## Table of Contents

- [Requirements](#requirements)
- [Getting Started](#getting-started)
- [Authentication](#authentication)
- [Available Functions](#available-functions)
- [Usage Examples](#usage-examples)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)
- [API Limits and Considerations](#api-limits-and-considerations)

---

## Requirements

### PHP Requirements
- PHP 5.6 or higher (7.4+ recommended)
- cURL extension enabled
- JSON extension enabled
- OpenSSL extension for HTTPS

### FedEx Account Requirements
- Active FedEx account
- FedEx Developer Portal account
- API Key (Client ID)
- API Secret (Client Secret)
- FedEx Account Number

### Getting Your API Credentials

1. Register at [FedEx Developer Portal](https://developer.fedex.com/)
2. Create a new project
3. Note your **API Key** (Client ID) and **API Secret** (Client Secret)
4. Have your **FedEx Account Number** ready
5. For testing, use the sandbox credentials and set `-test => true`

---

## Getting Started

### Basic Setup

```php
<?php
require_once('fedex.php');

// Your FedEx credentials
$credentials = array(
    'Key' => 'YOUR_API_KEY',
    'Password' => 'YOUR_API_SECRET',
    'AccountNumber' => 'YOUR_ACCOUNT_NUMBER'
);

// For testing, add:
// '-test' => true
?>
```

---

## Authentication

### OAuth 2.0 Automatic Token Management

The library automatically handles OAuth 2.0 authentication:

- **Automatic token retrieval** on first API call
- **Token caching** to reduce API calls (60-minute expiration)
- **Automatic refresh** with 60-second buffer before expiration
- **Error handling** for authentication failures

**You don't need to manually call authentication functions.** Simply pass your credentials to any API function, and authentication is handled automatically.

### Manual Token Retrieval (Advanced)

```php
// Normally not needed, but available if required
$token_response = fedexGetAccessToken($credentials);

if (isset($token_response['access_token'])) {
    echo "Access Token: " . $token_response['access_token'];
    echo "Expires At: " . date('Y-m-d H:i:s', $token_response['expires_at']);
} else {
    echo "Error: " . $token_response['error'];
}
```

---

## Available Functions

### 1. fedexAddressValidation()

Validates and standardizes addresses. Determines if an address is residential or commercial.

**Signature:**
```php
fedexAddressValidation($params, $addresses)
```

**Parameters:**

- `$params` (array) - Authentication parameters:
  - `Key` (string, required) - FedEx API Key
  - `Password` (string, required) - FedEx API Secret
  - `AccountNumber` (string, optional) - FedEx Account Number
  - `-test` (bool, optional) - Use sandbox environment

- `$addresses` (array) - Array of addresses to validate. Each address:
  - `streetLines` (array) - Street address lines (1-3)
  - `city` (string) - City name
  - `stateOrProvinceCode` (string) - State/province code
  - `postalCode` (string) - Postal/ZIP code
  - `countryCode` (string) - Two-letter country code (default: US)

**Returns:** Array with validation results

**Example:**
```php
$addresses = array(
    array(
        'streetLines' => array('1600 Pennsylvania Avenue NW'),
        'city' => 'Washington',
        'stateOrProvinceCode' => 'DC',
        'postalCode' => '20500',
        'countryCode' => 'US'
    )
);

$result = fedexAddressValidation($credentials, $addresses);

if ($result['result'] == 'SUCCESS') {
    print_r($result['resolvedAddresses']);
} else {
    echo "Error: " . $result['error'];
}
```

---

### 2. fedexServices()

Get shipping rates and transit times for available FedEx services.

**Signature:**
```php
fedexServices($params)
```

**Required Parameters:**

- `Key` (string) - FedEx API Key
- `Password` (string) - FedEx API Secret
- `AccountNumber` (string) - FedEx Account Number
- `Shipper_PostalCode` (string) - Origin postal code
- `Recipient_PostalCode` (string) - Destination postal code
- `Weight` (float) - Total weight in pounds

**Optional Parameters:**

- `Shipper_CountryCode` (string) - Default: 'US'
- `Recipient_CountryCode` (string) - Default: 'US'
- `Shipper_City` (string) - Origin city
- `Shipper_StateOrProvinceCode` (string) - Origin state
- `Recipient_City` (string) - Destination city
- `Recipient_StateOrProvinceCode` (string) - Destination state
- `PackageCount` (int) - Number of packages (default: 1)
- `Residential` (bool) - Destination is residential
- `RateRequestTypes` (string) - 'LIST', 'ACCOUNT', 'PREFERRED', 'INCENTIVE'
- `ServiceType` (string) - Specific service (e.g., 'FEDEX_GROUND')
- `Dimensions` (array) - Package dimensions: L, W, H, Units
- `Handling` (float) - Additional handling fee
- `-test` (bool) - Use sandbox environment

**Returns:** Array with rates

**Example:**
```php
$params = array_merge($credentials, array(
    'Shipper_PostalCode' => '80202',
    'Shipper_StateOrProvinceCode' => 'CO',
    'Recipient_PostalCode' => '90210',
    'Recipient_StateOrProvinceCode' => 'CA',
    'Weight' => 5,
    'Residential' => true
));

$result = fedexServices($params);

if (isset($result['rates'])) {
    foreach ($result['rates'] as $service => $cost) {
        echo "$service: $$cost\n";
    }
} else {
    echo "Error: " . $result['-error'];
}
```

**Sample Output:**
```
FEDEX_GROUND: $12.50
FEDEX_2_DAY: $25.00
PRIORITY_OVERNIGHT: $45.00
```

---

### 3. fedexProcessShipment()

Create a shipment and generate a shipping label.

**Signature:**
```php
fedexProcessShipment($params)
```

**Required Authentication:**
- `Key` (string) - FedEx API Key
- `Password` (string) - FedEx API Secret
- `AccountNumber` (string) - FedEx Account Number

**Required Shipper Information (Shipper_ prefix):**
- `Shipper_PersonName` (string) - Contact name
- `Shipper_CompanyName` (string) - Company name
- `Shipper_PhoneNumber` (string) - Phone number
- `Shipper_StreetLines` (string/array) - Street address
- `Shipper_City` (string) - City
- `Shipper_StateOrProvinceCode` (string) - State code
- `Shipper_PostalCode` (string) - ZIP code
- `Shipper_CountryCode` (string) - Country (default: US)

**Required Recipient Information (Recipient_ prefix):**
- `Recipient_PersonName` (string) - Contact name
- `Recipient_CompanyName` (string) - Company name
- `Recipient_PhoneNumber` (string) - Phone number
- `Recipient_StreetLines` (string/array) - Street address
- `Recipient_City` (string) - City
- `Recipient_StateOrProvinceCode` (string) - State code
- `Recipient_PostalCode` (string) - ZIP code
- `Recipient_CountryCode` (string) - Country (default: US)
- `Residential` (bool) - Is residential address

**Required Package Information:**
- `ItemWeight` (float) - Weight in pounds
- `ItemDescription` (string) - Package contents
- `ItemValue` (float) - Declared value (default: 25)

**Optional Parameters:**
- `ServiceType` (string) - Service level (default: FEDEX_GROUND)
  - Values: FEDEX_GROUND, FEDEX_2_DAY, PRIORITY_OVERNIGHT, etc.
- `ImageType` (string) - Label format (default: PNG)
  - Values: PNG, PDF, ZPLII
- `ItemDimensions` (array) - Dimensions: L, W, H, Units
- `ChargeAccount` (string) - Third-party billing account
- `ChargeAccountType` (string) - SENDER, RECIPIENT, THIRD_PARTY
- `ChargeAccountCountry` (string) - Billing country

**Optional Reference Fields:**
- `CustomerReference` or `Reference` - Customer reference
- `RMANumber` - RMA number
- `InvoiceNumber` or `Invoice` - Invoice number
- `PONumber` - Purchase order number
- `DepartmentNumber` or `Department` - Department
- `StoreNumber` or `Store` - Store number
- `BillOfLading` - Bill of lading
- `ShipmentIntegrity` - Shipment integrity reference

**Returns:** Array with tracking number and label data

**Example:**
```php
$shipment = array_merge($credentials, array(
    // Shipper
    'Shipper_PersonName' => 'John Sender',
    'Shipper_CompanyName' => 'ABC Company',
    'Shipper_PhoneNumber' => '303-555-0100',
    'Shipper_StreetLines' => '123 Main St',
    'Shipper_City' => 'Denver',
    'Shipper_StateOrProvinceCode' => 'CO',
    'Shipper_PostalCode' => '80202',

    // Recipient
    'Recipient_PersonName' => 'Jane Receiver',
    'Recipient_CompanyName' => 'XYZ Corp',
    'Recipient_PhoneNumber' => '310-555-0200',
    'Recipient_StreetLines' => '456 Oak Ave',
    'Recipient_City' => 'Los Angeles',
    'Recipient_StateOrProvinceCode' => 'CA',
    'Recipient_PostalCode' => '90210',
    'Residential' => false,

    // Package
    'ItemWeight' => 5,
    'ItemDescription' => 'Books',
    'ItemValue' => 50,
    'ServiceType' => 'FEDEX_GROUND',
    'ImageType' => 'PNG',

    // References
    'Reference' => 'ORDER-12345',
    'InvoiceNumber' => 'INV-2024-001'
));

$result = fedexProcessShipment($shipment);

if (isset($result['tracking_number'])) {
    echo "Success! Tracking: " . $result['tracking_number'];

    // Label data is in $result['response']['output']['transactionShipments'][0]
    $label_data = $result['response']['output']['transactionShipments'][0]['pieceResponses'][0]['packageDocuments'][0];
    $label_base64 = $label_data['encodedLabel'];

    // Save label to file
    file_put_contents('label.png', base64_decode($label_base64));
} else {
    echo "Error: " . print_r($result['errors'], true);
}
```

---

### 4. fedexCreatePendingShipment()

Create a return shipment label with email notification (for customer returns/RMAs).

**Signature:**
```php
fedexCreatePendingShipment($params)
```

**Additional Required Parameters:**
- `EmailTo` (string) - Recipient email for return label
- `EmailFrom` (string) - Sender email address
- `PersonalMessage` (string) - Message in email (optional)

All other parameters same as `fedexProcessShipment()`.

**Example:**
```php
$return_shipment = array_merge($shipment, array(
    'EmailTo' => 'customer@example.com',
    'EmailFrom' => 'returns@yourcompany.com',
    'PersonalMessage' => 'Thank you for your return request. Please print and use this label.'
));

$result = fedexCreatePendingShipment($return_shipment);
```

---

### 5. fedexTracking()

Track a shipment by tracking number.

**Signature:**
```php
fedexTracking($tracking_number, $params)
```

**Parameters:**

- `$tracking_number` (string) - FedEx tracking number
- `$params` (array) - Credentials (keys are case-insensitive):
  - `key` - FedEx API Key
  - `password` - FedEx API Secret
  - `accountnumber` - FedEx Account Number
  - `-test` (bool) - Use sandbox

**Returns:** Array with tracking details

**Example:**
```php
$tracking_params = array(
    'key' => 'YOUR_API_KEY',
    'password' => 'YOUR_API_SECRET',
    'accountnumber' => 'YOUR_ACCOUNT'
);

$result = fedexTracking('794608082345', $tracking_params);

if (!isset($result['error'])) {
    echo "Status: " . $result['status'] . "\n";
    echo "Current Location: " . $result['city'] . ", " . $result['state'] . "\n";

    if (isset($result['delivery_date'])) {
        echo "Delivered: " . $result['delivery_date'] . "\n";
    } elseif (isset($result['scheduled_delivery_date'])) {
        echo "Estimated Delivery: " . $result['scheduled_delivery_date'] . "\n";
    }

    // Show tracking history
    echo "\nTracking History:\n";
    foreach ($result['activity'] as $event) {
        echo $event['date'] . " - " . $event['city'] . ", " . $event['state'];
        echo " - " . $event['status'] . "\n";
    }
} else {
    echo "Error: " . $result['error'];
}
```

**Returned Fields:**
- `tracking_number` - Tracking number queried
- `carrier` - "FedEx"
- `trackingNumber` - Confirmed tracking number
- `method` - Service type (FEDEX_GROUND, etc.)
- `status` - Current status description
- `ship_weight` - Package weight with units
- `destination` - Destination address array
- `ship_date` - Shipment date
- `delivery_date` - Actual delivery date
- `scheduled_delivery_date` - Estimated delivery
- `pickup_date` - Pickup date
- `city` - Current location city
- `state` - Current location state
- `activity` - Array of tracking events
- `history` - Alias of activity

---

### 6. fedexCancelShipment()

Cancel a shipment that hasn't been picked up yet.

**Signature:**
```php
fedexCancelShipment($tracking_number, $params)
```

**Parameters:**

- `$tracking_number` (string) - FedEx tracking number
- `$params` (array) - Credentials:
  - `Key` - FedEx API Key
  - `Password` - FedEx API Secret
  - `AccountNumber` - FedEx Account Number
  - `-test` (bool) - Use sandbox

**Returns:** Array with cancellation result

**Example:**
```php
$result = fedexCancelShipment('794608082345', $credentials);

if ($result['result'] == 'SUCCESS') {
    echo $result['message']; // "Shipment cancelled successfully"
} else {
    echo "Error: " . $result['error'];
}
```

---

## Error Handling

### Error Response Format

All functions return arrays with error information when failures occur:

```php
array(
    'error' => 'Error message description',
    'http_code' => 400,  // HTTP status code
    'response' => array(...)  // Full API response
)
```

### Common Error Types

**Authentication Errors:**
```php
if (isset($result['error']) && strpos($result['error'], 'Missing required parameter') !== false) {
    // Missing credentials
}
```

**API Errors:**
```php
if (isset($result['http_code']) && $result['http_code'] >= 400) {
    // HTTP error - check response for details
    print_r($result['response']);
}
```

**cURL Errors:**
```php
if (isset($result['error']) && strpos($result['error'], 'cURL Error') !== false) {
    // Network or connection issue
}
```

### Best Practice Error Handling

```php
function handleFedExError($result, $operation = 'API call') {
    if (isset($result['error'])) {
        error_log("FedEx {$operation} failed: " . $result['error']);

        if (isset($result['http_code'])) {
            error_log("HTTP Code: " . $result['http_code']);
        }

        if (isset($result['response'])) {
            error_log("Response: " . json_encode($result['response']));
        }

        return false;
    }

    return true;
}

// Usage
$result = fedexServices($params);
if (!handleFedExError($result, 'Rate Quote')) {
    // Handle error appropriately
    die('Unable to get rates at this time');
}
```

---

## Best Practices

### 1. Token Caching

The library automatically caches OAuth tokens. To maximize efficiency:

```php
// DON'T create new credential arrays for each call
// DO reuse the same credential array

// Good:
$creds = array('Key' => 'xxx', 'Password' => 'yyy', 'AccountNumber' => 'zzz');
$rates = fedexServices($creds);
$tracking = fedexTracking('12345', $creds);

// This uses the same cached token for both calls
```

### 2. Test Mode

Always test with sandbox before going to production:

```php
// Development
$credentials = array(
    'Key' => 'TEST_API_KEY',
    'Password' => 'TEST_API_SECRET',
    'AccountNumber' => 'TEST_ACCOUNT',
    '-test' => true  // Use sandbox
);

// Production - simply remove -test flag
unset($credentials['-test']);
```

### 3. Rate Limiting

Be mindful of API rate limits:

- Track API has a limit of **10,000 calls per day**
- Implement request throttling for high-volume applications
- Cache tracking results when possible

### 4. Label Storage

Store generated labels securely:

```php
$result = fedexProcessShipment($params);

if (isset($result['tracking_number'])) {
    $tracking = $result['tracking_number'];
    $label_data = $result['response']['output']['transactionShipments'][0]['pieceResponses'][0]['packageDocuments'][0];

    // Get label type
    $image_type = $params['ImageType']; // PNG, PDF, ZPLII
    $extension = strtolower($image_type);
    if ($extension == 'zplii') $extension = 'zpl';

    // Save with tracking number as filename
    $filename = "labels/{$tracking}.{$extension}";
    file_put_contents($filename, base64_decode($label_data['encodedLabel']));

    // Store in database
    // INSERT INTO shipments (tracking_number, label_path, ...) VALUES (...)
}
```

### 5. Address Validation

Always validate addresses before creating shipments:

```php
// Validate recipient address first
$addresses = array($recipient_address);
$validation = fedexAddressValidation($credentials, $addresses);

if ($validation['result'] == 'SUCCESS') {
    $resolved = $validation['resolvedAddresses'][0];

    // Use the validated address for shipping
    if (isset($resolved['resolvedAddress'])) {
        $validated_address = $resolved['resolvedAddress'];
        // Update your shipment params with validated address
    }
} else {
    // Address invalid - notify user to correct it
}
```

---

## API Limits and Considerations

### Production vs Sandbox

**Sandbox (Test) Environment:**
- Base URL: `https://apis-sandbox.fedex.com`
- Use for development and testing
- May have simulated responses
- No real shipments created
- No charges incurred

**Production Environment:**
- Base URL: `https://apis.fedex.com`
- Use for live operations
- Real shipments and charges
- Requires production credentials

### Rate Limits

- **OAuth Token:** Valid for 60 minutes (automatically managed)
- **Tracking API:** 10,000 calls per day
- **Other APIs:** Consult FedEx Developer Portal for specific limits

### Service Availability

Not all services are available in all regions. Common service types:

**Domestic (US):**
- `FEDEX_GROUND`
- `FEDEX_2_DAY`
- `FEDEX_2_DAY_AM`
- `PRIORITY_OVERNIGHT`
- `STANDARD_OVERNIGHT`
- `FIRST_OVERNIGHT`

**International:**
- `INTERNATIONAL_ECONOMY`
- `INTERNATIONAL_PRIORITY`
- `INTERNATIONAL_FIRST`

**Freight:**
- `FEDEX_FREIGHT_ECONOMY`
- `FEDEX_FREIGHT_PRIORITY`

### Country-Specific Requirements

**Ireland, Bulgaria, Cyprus, Ukraine:**
- As of January 5, 2026, valid postal codes are required

**Ireland:**
- Eircodes required (7-digit postcodes)

---

## Additional Resources

- [FedEx Developer Portal](https://developer.fedex.com/)
- [Ship API Documentation](https://developer.fedex.com/api/en-us/catalog/ship/v1/docs.html)
- [Rate API Documentation](https://developer.fedex.com/api/en-us/catalog/rate/v1/docs.html)
- [Track API Documentation](https://developer.fedex.com/api/en-us/catalog/track/v1/docs.html)
- [Address Validation API](https://developer.fedex.com/api/en-us/catalog/address-validation/v1/docs.html)

---

## Troubleshooting

### "Missing required parameter" Error

Ensure all required credentials are provided:
```php
$credentials = array(
    'Key' => 'YOUR_KEY',           // Required
    'Password' => 'YOUR_SECRET',   // Required
    'AccountNumber' => 'YOUR_ACCT' // Required for most operations
);
```

### "cURL Error" Messages

Check:
1. PHP cURL extension is enabled: `php -m | grep curl`
2. OpenSSL is enabled: `php -m | grep openssl`
3. Server can reach FedEx APIs (firewall rules)
4. SSL certificates are up to date

### Token Expiration Issues

The library automatically handles token refresh. If you encounter issues:
```php
// Clear the token cache
global $FEDEX_ACCESS_TOKEN_CACHE;
$FEDEX_ACCESS_TOKEN_CACHE = array();

// Next API call will get a fresh token
```

### Sandbox vs Production Confusion

Always double-check your environment:
```php
// Explicitly set for clarity
$is_production = true; // or false

$credentials = array(
    'Key' => $is_production ? PROD_KEY : TEST_KEY,
    'Password' => $is_production ? PROD_SECRET : TEST_SECRET,
    'AccountNumber' => $is_production ? PROD_ACCOUNT : TEST_ACCOUNT
);

if (!$is_production) {
    $credentials['-test'] = true;
}
```

---

## Version History

**2.0.0** (January 2026)
- Complete migration to FedEx REST API v1
- Removed deprecated SOAP/WSDL dependencies
- Added OAuth 2.0 authentication with automatic token management
- Comprehensive PHPDoc documentation
- Maintained backward-compatible function signatures
- Added fedexCancelShipment() function

**1.x** (Legacy)
- SOAP-based implementation (deprecated)

---

## Support

For issues with this library:
- Review the [Migration Guide](fedex_migration_guide.md)
- Check error messages and logs
- Verify credentials and environment settings

For FedEx API issues:
- Contact [FedEx Technical Support](https://developer.fedex.com/api/en-us/support.html)
- Technical Support Hotline: 1-877-339-2774 (say "Web Services")

---

## License

This library is provided as-is for use with FedEx shipping services. FedEx API usage is subject to FedEx's terms of service and developer agreement.
