# UPS API Integration Library Documentation

Version 2.0.0 - Updated for OAuth 2.0 and REST/JSON API

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Getting Started](#getting-started)
- [Authentication](#authentication)
- [Functions](#functions)
  - [upsGetOAuthToken](#upsgetoauthtoken)
  - [upsServices](#upsservices)
  - [upsTrack](#upstrack)
  - [upsAddressValidate](#upsaddressvalidate)
- [Code Examples](#code-examples)
- [Service Codes Reference](#service-codes-reference)
- [Error Handling](#error-handling)
- [Performance Optimization](#performance-optimization)
- [Testing](#testing)

## Overview

This library provides a complete PHP integration with UPS shipping APIs, including:

- **Rate Shopping**: Get shipping rates for all UPS services
- **Package Tracking**: Track UPS shipments in real-time
- **Address Validation**: Validate and standardize US addresses

All functions use the latest UPS REST/JSON APIs with OAuth 2.0 authentication as required since June 2024.

## Requirements

- PHP 5.4 or higher
- Active UPS Developer Portal account
- OAuth 2.0 credentials (Client ID and Client Secret)
- Helper functions: `postURL()`, `postJSON()`, `getURL()` (typically from WaSQL framework)

## Getting Started

### Step 1: Get UPS API Credentials

1. Visit the [UPS Developer Portal](https://developer.ups.com)
2. Create an account or sign in
3. Create a new application
4. Note your **Client ID** and **Client Secret**
5. Request production access (development/sandbox access is automatic)

### Step 2: Include the Library

```php
require_once('path/to/ups.php');
```

### Step 3: Make Your First API Call

```php
// Get shipping rates
$rates = upsServices([
    '-client_id' => 'your_client_id_here',
    '-client_secret' => 'your_client_secret_here',
    '-account' => 'your_ups_account_number',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5
]);

if(isset($rates['rates'])){
    foreach($rates['rates'] as $service => $cost){
        echo "$service: \$$cost\n";
    }
}
```

## Authentication

### upsGetOAuthToken

Obtains an OAuth 2.0 access token for API authentication.

**Function Signature:**
```php
upsGetOAuthToken(array $params)
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| -client_id | string | Yes | OAuth client ID from UPS Developer Portal |
| -client_secret | string | Yes | OAuth client secret from UPS Developer Portal |
| -test | bool | No | Set to true to use sandbox environment |
| -grant_type | string | No | OAuth grant type (default: 'client_credentials') |
| -merchant_id | string | No | Merchant ID for tracking purposes |

**Returns:**

```php
[
    'access_token' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...',
    'token_type' => 'Bearer',
    'expires_in' => 14400,  // 4 hours in seconds
    'status' => 'success',
    'result' => [...]  // Raw response data
]
```

**Error Response:**

```php
[
    'error' => 'Invalid credentials',
    'error_code' => 'invalid_client',
    'status' => 'error',
    'result' => [...]
]
```

**Example:**

```php
$token = upsGetOAuthToken([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret'
]);

if($token['status'] == 'success'){
    $access_token = $token['access_token'];
    echo "Token expires in: {$token['expires_in']} seconds\n";
}
```

**Best Practices:**

- Cache the access token for up to 4 hours to reduce API calls
- Implement token refresh logic before expiration
- Store credentials securely (environment variables, encrypted config)

## Functions

### upsServices

Get shipping rates for UPS services between two locations.

**Function Signature:**
```php
upsServices(array $params)
```

**Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| -client_id | string | Yes | - | OAuth client ID |
| -client_secret | string | Yes | - | OAuth client secret |
| -account | string | Yes | - | UPS account number |
| -shipfrom_zip | string | Yes | - | Origin ZIP/postal code |
| -shipto_zip | string | Yes | - | Destination ZIP/postal code |
| -weight | float | Yes | - | Package weight in pounds |
| -access_token | string | No | - | Pre-obtained OAuth token |
| -test | bool | No | false | Use sandbox environment |
| shipfrom_country | string | No | 'US' | Origin country code (ISO 2-letter) |
| shipto_country | string | No | 'US' | Destination country code |
| pickup_type | string | No | '01' | Pickup type code (see reference) |
| package_type | string | No | '02' | Package type code (see reference) |
| service_code | string | No | '03' | Service code for 'Rate' requests |
| request_option | string | No | 'Shop' | 'Shop' for all rates or 'Rate' for single |
| length | float | No | - | Package length in inches |
| width | float | No | - | Package width in inches |
| height | float | No | - | Package height in inches |

**Returns:**

```php
[
    'rates' => [
        'UPS Ground' => 12.50,
        'UPS Three-Day Select' => 18.75,
        'UPS Second Day Air' => 25.00,
        'UPS Next Day Air' => 45.00
    ],
    'descriptions' => [
        'UPS Ground' => 'UPS Ground',
        'UPS Three-Day Select' => 'UPS Three-Day Select',
        // ...
    ],
    '-params' => [...],  // Input parameters
    'result' => [...]    // Raw API response
]
```

**Error Response:**

```php
[
    'error' => 'Invalid shipper number',
    '-params' => [...],
    'result' => [...]
]
```

**Example 1: Shop for All Rates**

```php
$rates = upsServices([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret',
    '-account' => '123456',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5,
    'length' => 12,
    'width' => 8,
    'height' => 6
]);

if(isset($rates['rates'])){
    echo "Available shipping options:\n";
    foreach($rates['rates'] as $service => $cost){
        echo sprintf("%-30s \$%.2f\n", $service, $cost);
    }
}
```

**Example 2: Get Specific Service Rate**

```php
$rate = upsServices([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret',
    '-account' => '123456',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5,
    'request_option' => 'Rate',
    'service_code' => '03'  // UPS Ground
]);
```

**Example 3: Reuse OAuth Token**

```php
// Get token once
$token = upsGetOAuthToken([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret'
]);

// Reuse for multiple rate requests
$rate1 = upsServices([
    '-access_token' => $token['access_token'],
    '-account' => '123456',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5
]);

$rate2 = upsServices([
    '-access_token' => $token['access_token'],
    '-account' => '123456',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '94102',
    '-weight' => 3.2
]);
```

### upsTrack

Track a UPS shipment by tracking number.

**Function Signature:**
```php
upsTrack(array $params)
```

**Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| -client_id | string | Yes | - | OAuth client ID |
| -client_secret | string | Yes | - | OAuth client secret |
| -tn | string | Yes | - | UPS tracking number |
| -access_token | string | No | - | Pre-obtained OAuth token |
| -test | bool | No | false | Use sandbox environment |
| -locale | string | No | 'en_US' | Response language locale |
| -return_signature | bool | No | false | Include signature image data |
| -return_milestone | bool | No | false | Include milestone data |

**Returns (Single Package):**

```php
[
    'carrier' => 'UPS',
    'tracking_number' => '1Z12345E0205271688',
    'status' => 'Delivered',
    'status_code' => 'D',
    'service' => [
        'code' => '02',
        'description' => 'UPS Second Day Air'
    ],
    'ship_date' => '2025-01-05',
    'ship_date_utime' => 1736035200,
    'delivery_date' => '2025-01-07 14:23:00',
    'delivery_date_utime' => 1736266980,
    'shipto' => [
        'city' => 'NEW YORK',
        'state' => 'NY',
        'country' => 'US',
        'zip' => '10001'
    ],
    '-params' => [...],
    'result' => [...]
]
```

**Returns (Multiple Packages):**

```php
[
    'carrier' => 'UPS',
    'service' => [...],
    'ship_date' => '2025-01-05',
    'packages' => [
        [
            'tracking_number' => '1Z12345E0305271640',
            'status' => 'In Transit',
            'status_code' => 'IT',
            'shipto' => [...]
        ],
        [
            'tracking_number' => '1Z12345E0393657226',
            'status' => 'In Transit',
            'status_code' => 'IT',
            'shipto' => [...]
        ]
    ],
    '-params' => [...],
    'result' => [...]
]
```

**Error Response:**

```php
[
    'carrier' => 'UPS',
    'tracking_number' => '1Z12345E020527079',
    'error' => 'No tracking information available',
    'error_code' => '151044',
    'status' => 'ERROR: No tracking information available',
    '-params' => [...],
    'result' => [...]
]
```

**Example 1: Basic Tracking**

```php
$tracking = upsTrack([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret',
    '-tn' => '1Z12345E0205271688'
]);

if(isset($tracking['status']) && !isset($tracking['error'])){
    echo "Status: {$tracking['status']}\n";
    echo "Service: {$tracking['service']['description']}\n";

    if(isset($tracking['delivery_date'])){
        echo "Delivered: {$tracking['delivery_date']}\n";
    }

    if(isset($tracking['shipto'])){
        echo "Location: {$tracking['shipto']['city']}, {$tracking['shipto']['state']}\n";
    }
}
else{
    echo "Error: {$tracking['error']}\n";
}
```

**Example 2: Multiple Package Shipment**

```php
$tracking = upsTrack([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret',
    '-tn' => '1Z12345E0305271640'
]);

if(isset($tracking['packages'])){
    echo "Multi-package shipment:\n";
    foreach($tracking['packages'] as $pkg){
        echo "  Package {$pkg['tracking_number']}: {$pkg['status']}\n";
    }
}
```

**Example 3: Test Environment**

```php
// Use test tracking numbers in sandbox
$tracking = upsTrack([
    '-client_id' => 'your_test_client_id',
    '-client_secret' => 'your_test_client_secret',
    '-tn' => '1Z12345E0205271688',  // Test tracking number
    '-test' => true
]);
```

### upsAddressValidate

Validate and standardize a street address.

**Function Signature:**
```php
upsAddressValidate(array $params)
```

**Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| -client_id | string | Yes | - | OAuth client ID |
| -client_secret | string | Yes | - | OAuth client secret |
| address | string | Yes | - | Street address line |
| city | string | Yes | - | City name |
| state | string | Yes | - | State/province code |
| zip | string | Yes | - | ZIP/postal code |
| -access_token | string | No | - | Pre-obtained OAuth token |
| -test | bool | No | false | Use sandbox environment |
| country | string | No | 'US' | Country code (US, PR, etc.) |
| address2 | string | No | - | Second address line |
| address3 | string | No | - | Third address line |

**Returns:**

```php
[
    'valid' => true,
    'quality' => 'Perfect Match',
    'candidate_addresses' => [
        [
            'address_lines' => ['26601 W AGOURA RD'],
            'city' => 'CALABASAS',
            'state' => 'CA',
            'zip' => '91302',
            'zip_extended' => '1844',
            'country' => 'US',
            'quality' => 'Perfect Match',
            'quality_code' => '1.0'
        ]
    ],
    '-params' => [...],
    'result' => [...]
]
```

**Error Response:**

```php
[
    'valid' => false,
    'error' => 'Address not found',
    '-params' => [...],
    'result' => [...]
]
```

**Example 1: Basic Address Validation**

```php
$validation = upsAddressValidate([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret',
    'address' => '26601 W Agoura Rd',
    'city' => 'Calabasas',
    'state' => 'CA',
    'zip' => '91302'
]);

if($validation['valid']){
    echo "Address is valid! Quality: {$validation['quality']}\n";

    $best = $validation['candidate_addresses'][0];
    echo "Standardized address:\n";
    echo implode("\n", $best['address_lines']) . "\n";
    echo "{$best['city']}, {$best['state']} {$best['zip']}-{$best['zip_extended']}\n";
}
else{
    echo "Invalid address: {$validation['error']}\n";
}
```

**Example 2: Multiple Address Lines**

```php
$validation = upsAddressValidate([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret',
    'address' => '123 Main St',
    'address2' => 'Suite 100',
    'city' => 'New York',
    'state' => 'NY',
    'zip' => '10001'
]);
```

**Example 3: Check All Candidates**

```php
$validation = upsAddressValidate([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret',
    'address' => '123 Main',  // Incomplete address
    'city' => 'Springfield',
    'state' => 'IL',
    'zip' => '62701'
]);

if($validation['valid'] && count($validation['candidate_addresses']) > 1){
    echo "Multiple address matches found:\n";
    foreach($validation['candidate_addresses'] as $idx => $addr){
        echo ($idx+1) . ". " . implode(", ", $addr['address_lines']) . "\n";
        echo "   {$addr['city']}, {$addr['state']} {$addr['zip']}\n";
        echo "   Quality: {$addr['quality']}\n\n";
    }
}
```

## Code Examples

### Complete Rate Shopping with Error Handling

```php
function getShippingRates($from_zip, $to_zip, $weight) {
    $config = [
        '-client_id' => getenv('UPS_CLIENT_ID'),
        '-client_secret' => getenv('UPS_CLIENT_SECRET'),
        '-account' => getenv('UPS_ACCOUNT')
    ];

    $result = upsServices(array_merge($config, [
        '-shipfrom_zip' => $from_zip,
        '-shipto_zip' => $to_zip,
        '-weight' => $weight
    ]));

    if(isset($result['error'])){
        error_log("UPS Rating Error: " . $result['error']);
        return false;
    }

    if(!isset($result['rates']) || count($result['rates']) == 0){
        error_log("UPS Rating: No rates returned");
        return false;
    }

    return $result['rates'];
}

// Usage
$rates = getShippingRates('90210', '10001', 5.5);
if($rates !== false){
    echo "Cheapest: " . key($rates) . " - $" . current($rates) . "\n";
}
```

### Token Caching for Performance

```php
class UPSHelper {
    private static $token = null;
    private static $token_expiry = 0;

    public static function getToken() {
        // Return cached token if still valid
        if(self::$token && time() < self::$token_expiry){
            return self::$token;
        }

        // Get new token
        $result = upsGetOAuthToken([
            '-client_id' => getenv('UPS_CLIENT_ID'),
            '-client_secret' => getenv('UPS_CLIENT_SECRET')
        ]);

        if(isset($result['access_token'])){
            self::$token = $result['access_token'];
            // Cache for 3.5 hours (token valid for 4 hours)
            self::$token_expiry = time() + (3.5 * 3600);
            return self::$token;
        }

        return null;
    }

    public static function getRates($from_zip, $to_zip, $weight) {
        $token = self::getToken();
        if(!$token) return false;

        return upsServices([
            '-access_token' => $token,
            '-account' => getenv('UPS_ACCOUNT'),
            '-shipfrom_zip' => $from_zip,
            '-shipto_zip' => $to_zip,
            '-weight' => $weight
        ]);
    }
}

// Usage
$rates = UPSHelper::getRates('90210', '10001', 5.5);
```

### Batch Tracking Multiple Packages

```php
function trackMultiplePackages($tracking_numbers) {
    // Get token once
    $token_result = upsGetOAuthToken([
        '-client_id' => getenv('UPS_CLIENT_ID'),
        '-client_secret' => getenv('UPS_CLIENT_SECRET')
    ]);

    if(!isset($token_result['access_token'])){
        return ['error' => 'Authentication failed'];
    }

    $results = [];
    foreach($tracking_numbers as $tn){
        $tracking = upsTrack([
            '-access_token' => $token_result['access_token'],
            '-tn' => $tn
        ]);

        $results[$tn] = [
            'status' => $tracking['status'] ?? 'Unknown',
            'delivered' => isset($tracking['delivery_date']),
            'delivery_date' => $tracking['delivery_date'] ?? null
        ];
    }

    return $results;
}

// Usage
$tracking_numbers = ['1Z12345E0205271688', '1Z12345E6605272234'];
$statuses = trackMultiplePackages($tracking_numbers);

foreach($statuses as $tn => $info){
    echo "$tn: {$info['status']}\n";
}
```

## Service Codes Reference

### Service Codes (service_code)

| Code | Service Name |
|------|-------------|
| 01 | UPS Next Day Air |
| 02 | UPS Second Day Air |
| 03 | UPS Ground |
| 07 | UPS Worldwide Express |
| 08 | UPS Worldwide Expedited |
| 11 | UPS Standard |
| 12 | UPS Three-Day Select |
| 13 | UPS Next Day Air Saver |
| 14 | UPS Next Day Air Early AM |
| 54 | UPS Worldwide Express Plus |
| 59 | UPS Second Day Air AM |
| 65 | UPS Saver |

### Pickup Types (pickup_type)

| Code | Description |
|------|-------------|
| 01 | Daily Pickup (Default) |
| 03 | Customer Counter |
| 06 | One Time Pickup |
| 07 | On Call Air |
| 11 | Authorized Shipping Outlet |
| 19 | Letter Center |
| 20 | Air Service Center |

### Package Types (package_type)

| Code | Description |
|------|-------------|
| 00 | Unknown |
| 01 | UPS Letter |
| 02 | Your Packaging (Default) |
| 03 | UPS Tube |
| 04 | UPS Pak |
| 21 | UPS Express Box |

## Error Handling

All functions return error information in a consistent format:

```php
[
    'error' => 'Human-readable error message',
    'error_code' => 'API_ERROR_CODE',  // When available
    'status' => 'error',  // or 'ERROR: message' for tracking
    'result' => [...]  // Raw API response for debugging
]
```

### Common Errors

**Authentication Errors:**
- `invalid_client` - Invalid client ID or secret
- `No client_id` - Missing client ID parameter
- `No client_secret` - Missing client secret parameter

**Rating Errors:**
- `No account` - Missing UPS account number
- `Invalid shipper number` - UPS account number not found
- `No rates returned` - No services available for route

**Tracking Errors:**
- `No tracking information available` - Invalid tracking number or not yet in system
- `Invalid Tracking Number` - Tracking number format is invalid

**Address Validation Errors:**
- `Address not found` - Address cannot be validated
- `No address` - Missing required address parameter

### Error Handling Best Practices

```php
$result = upsServices([...]);

// Check for error key
if(isset($result['error'])){
    // Log for debugging
    error_log("UPS API Error: " . $result['error']);

    // Check error code for specific handling
    if(isset($result['error_code'])){
        switch($result['error_code']){
            case 'invalid_client':
                // Handle authentication failure
                break;
            case '151044':
                // Handle tracking not found
                break;
        }
    }

    // Return user-friendly message
    return "Unable to retrieve shipping rates. Please try again later.";
}

// Check for expected data
if(!isset($result['rates'])){
    error_log("UPS API: Unexpected response format");
    return "Service temporarily unavailable.";
}

// Process successful result
return $result['rates'];
```

## Performance Optimization

### 1. Cache OAuth Tokens

Access tokens are valid for 4 hours. Cache them to reduce authentication requests:

```php
// Example using PHP sessions
session_start();

function getCachedToken() {
    if(isset($_SESSION['ups_token']) &&
       isset($_SESSION['ups_token_expiry']) &&
       time() < $_SESSION['ups_token_expiry']){
        return $_SESSION['ups_token'];
    }

    $result = upsGetOAuthToken([
        '-client_id' => getenv('UPS_CLIENT_ID'),
        '-client_secret' => getenv('UPS_CLIENT_SECRET')
    ]);

    if(isset($result['access_token'])){
        $_SESSION['ups_token'] = $result['access_token'];
        $_SESSION['ups_token_expiry'] = time() + (3.5 * 3600);
        return $result['access_token'];
    }

    return null;
}
```

### 2. Reuse Tokens Across Requests

When making multiple API calls, pass the token explicitly:

```php
$token = getCachedToken();

// Multiple rate requests with same token
$rates1 = upsServices(['-access_token' => $token, ...]);
$rates2 = upsServices(['-access_token' => $token, ...]);
$rates3 = upsServices(['-access_token' => $token, ...]);
```

### 3. Cache Rate Responses

Shipping rates don't change frequently. Cache them:

```php
$cache_key = "ups_rates_{$from_zip}_{$to_zip}_{$weight}";
$cached = apcu_fetch($cache_key);

if($cached !== false){
    return $cached;
}

$rates = upsServices([...]);
if(isset($rates['rates'])){
    // Cache for 1 hour
    apcu_store($cache_key, $rates, 3600);
}

return $rates;
```

## Testing

### Test Credentials

UPS provides test credentials for sandbox testing. Create a separate application in the Developer Portal for testing.

### Test Tracking Numbers

Use these tracking numbers in the sandbox environment:

| Tracking Number | Service | Expected Status |
|----------------|---------|-----------------|
| 1Z12345E0205271688 | 2nd Day Air | Delivered |
| 1Z12345E6605272234 | World Wide Express | Delivered |
| 1Z12345E0305271640 | Ground | Delivered |
| 1Z12345E1305277940 | Next Day Air Saver | Origin Scan |
| 1Z12345E6205277936 | Day Air Saver | 2nd Delivery Attempt |
| 1Z12345E020527079 | N/A | Invalid Tracking Number |
| 1Z12345E1505270452 | N/A | No Tracking Info Available |

### Example Test Script

```php
// Test with sandbox credentials
$test_config = [
    '-client_id' => 'test_client_id',
    '-client_secret' => 'test_client_secret',
    '-test' => true
];

// Test authentication
echo "Testing authentication...\n";
$token = upsGetOAuthToken($test_config);
if(isset($token['access_token'])){
    echo "✓ Authentication successful\n";
} else {
    echo "✗ Authentication failed: {$token['error']}\n";
    exit;
}

// Test rating
echo "\nTesting rating...\n";
$rates = upsServices(array_merge($test_config, [
    '-account' => 'test_account',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5
]));
if(isset($rates['rates'])){
    echo "✓ Rating successful: " . count($rates['rates']) . " rates returned\n";
} else {
    echo "✗ Rating failed: {$rates['error']}\n";
}

// Test tracking
echo "\nTesting tracking...\n";
$tracking = upsTrack(array_merge($test_config, [
    '-tn' => '1Z12345E0205271688'
]));
if(isset($tracking['status']) && !isset($tracking['error'])){
    echo "✓ Tracking successful: {$tracking['status']}\n";
} else {
    echo "✗ Tracking failed: {$tracking['error']}\n";
}

echo "\nAll tests complete!\n";
```

## Support and Resources

- **UPS Developer Portal**: https://developer.ups.com
- **API Reference**: https://developer.ups.com/api/reference
- **OAuth Guide**: https://developer.ups.com/oauth-developer-guide
- **Support**: Contact UPS Technical Support through the Developer Portal

## Version History

- **2.0.0** (2025-01-07)
  - Migrated to OAuth 2.0 authentication
  - Updated all functions to use REST/JSON APIs
  - Added comprehensive PHPDocs
  - Maintained backward-compatible function signatures
  - Deprecated old XML-based functions

- **1.x** (Legacy)
  - XML-based API with Access Key authentication
  - Deprecated as of June 2024
