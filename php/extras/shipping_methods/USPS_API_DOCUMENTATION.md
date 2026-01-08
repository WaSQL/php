# USPS API v3 - Complete Documentation

**Version:** 1.0.0
**Last Updated:** January 2026
**Author:** WaSQL Framework
**License:** MIT

---

## Table of Contents

1. [Overview](#overview)
2. [Requirements](#requirements)
3. [Getting Started](#getting-started)
4. [Authentication](#authentication)
5. [Rate Limiting](#rate-limiting)
6. [Function Reference](#function-reference)
   - [uspsGetAccessToken()](#uspsgetaccesstoken)
   - [uspsApiRequest()](#uspsapirequest)
   - [uspsServices()](#uspsservices)
   - [uspsTrack()](#uspstrack)
   - [uspsVerifyAddress()](#uspsverifyaddress)
   - [uspsZipCodeInfo()](#uspszipcodein)
   - [uspsExpressMailLabel()](#uspsexpressmaillabel)
7. [Error Handling](#error-handling)
8. [Code Examples](#code-examples)
9. [Testing](#testing)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)
12. [Migration from Old API](#migration-from-old-api)
13. [Resources](#resources)

---

## Overview

This library provides PHP functions for integrating with the USPS API v3. The old USPS Web Tools API was retired on **January 25, 2026**, and this library provides a complete migration to the new REST-based API with OAuth 2.0 authentication.

### Key Features

- ✅ **OAuth 2.0 Authentication** - Automatic token management and caching
- ✅ **Real-time Shipping Rates** - Domestic and international pricing
- ✅ **Package Tracking** - Track shipments with detailed status updates
- ✅ **Address Validation** - Verify and standardize US addresses
- ✅ **Label Creation** - Generate shipping labels with tracking numbers
- ✅ **ZIP Code Lookup** - Get city/state from ZIP codes
- ✅ **Error Handling** - Comprehensive error reporting
- ✅ **Backward Compatible** - Maintains similar function signatures

---

## Requirements

### System Requirements
- PHP 5.6 or higher (7.4+ recommended)
- cURL extension enabled
- JSON extension enabled
- SSL/TLS support (for HTTPS)

### USPS Account Requirements
- USPS Developer Portal account
- Consumer Key (Client ID)
- Consumer Secret (Client Secret)
- Payment Account Number (for label creation only)

---

## Getting Started

### Step 1: Register for USPS API

1. Visit the [USPS Developer Portal](https://developers.usps.com/)
2. Create an account or sign in
3. Create a new App in the Developer Portal
4. Copy your **Consumer Key** and **Consumer Secret**

### Step 2: Include the Library

```php
require_once('path/to/usps.php');
```

### Step 3: Make Your First API Call

```php
// Get shipping rates
$rates = uspsServices(array(
    '-client_id' => 'YOUR_CONSUMER_KEY',
    '-client_secret' => 'YOUR_CONSUMER_SECRET',
    '-weight' => 16,  // 1 pound (16 ounces)
    '-zip_orig' => '90210',
    '-zip_dest' => '10001'
));

if (isset($rates['rates'])) {
    foreach ($rates['rates'] as $service => $price) {
        echo "$service: $$price\n";
    }
}
```

---

## Authentication

### OAuth 2.0 Flow

The library uses OAuth 2.0 Client Credentials grant type. Authentication is handled automatically:

1. **Automatic Token Retrieval** - Tokens are fetched on first API call
2. **Global Token Caching** - Tokens are cached to reduce API calls
3. **Automatic Refresh** - Expired tokens are automatically refreshed (60-second buffer)

### Token Cache

Tokens are stored in the global variable `$USPS_ACCESS_TOKEN_CACHE` and persist for the duration of the script execution.

```php
// Token cache structure
$USPS_ACCESS_TOKEN_CACHE[md5($client_id . $client_secret)] = array(
    'access_token' => 'Bearer token string',
    'token_type' => 'Bearer',
    'expires_in' => 3600,
    'expires_at' => 1234567890,  // Unix timestamp
    'issued_at' => 1234564290
);
```

### Testing vs Production

All functions accept a `-test` parameter to use the testing environment:

- **Production:** `https://apis.usps.com`
- **Testing:** `https://apis-tem.usps.com`

```php
// Use testing environment
$rates = uspsServices(array(
    '-client_id' => 'test_consumer_key',
    '-client_secret' => 'test_consumer_secret',
    '-test' => true,  // Use testing environment
    '-weight' => 16,
    '-zip_orig' => '90210',
    '-zip_dest' => '10001'
));
```

---

## Rate Limiting

The USPS API v3 has rate limits to ensure fair usage:

### Default Limits
- **60 calls per hour** per API category
- Rate limits are per Consumer Key
- Separate limits for each API:
  - Addresses API: 60 calls/hour
  - Prices API: 60 calls/hour
  - Tracking API: 60 calls/hour
  - Labels API: 60 calls/hour

### Best Practices
- Implement request caching where possible
- Use token caching (already implemented)
- Batch address verifications when possible
- Monitor API usage through the Developer Portal

### HTTP 429 Response

If you exceed rate limits, you'll receive:
```php
array(
    'error' => 'API request failed',
    'http_code' => 429,
    'response' => array('message' => 'Too Many Requests')
)
```

---

## Function Reference

### uspsGetAccessToken()

**Purpose:** Get OAuth 2.0 access token for USPS API v3 (Internal helper function)

**Visibility:** Public (but typically called internally by uspsApiRequest)

**Signature:**
```php
function uspsGetAccessToken(array $params = array()): array
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `-client_id` | string | Yes | USPS Consumer Key from Developer Portal |
| `-client_secret` | string | Yes | USPS Consumer Secret from Developer Portal |
| `-test` | bool | No | Use testing environment (default: false) |

**Returns:**

Success:
```php
array(
    'access_token' => 'eyJhbGciOi...',
    'token_type' => 'Bearer',
    'expires_in' => 3600,
    'expires_at' => 1234567890,
    'issued_at' => 1234564290
)
```

Error:
```php
array(
    'error' => 'OAuth authentication failed',
    'http_code' => 401,
    'response' => array(...)
)
```

**Example:**
```php
$token = uspsGetAccessToken(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret'
));

if (isset($token['access_token'])) {
    echo "Token: " . $token['access_token'];
}
```

**Notes:**
- Tokens are automatically cached globally
- Cache key is MD5 hash of client_id + client_secret
- Tokens are auto-refreshed when expired (60-second buffer)
- You typically don't need to call this directly

---

### uspsApiRequest()

**Purpose:** Make authenticated API request to USPS API v3 (Internal helper function)

**Visibility:** Public (but typically called internally by other functions)

**Signature:**
```php
function uspsApiRequest(string $endpoint, array $params, string $method = 'POST', array $data = null): array
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$endpoint` | string | Yes | API endpoint path (e.g., '/prices/v3/base-rates/search') |
| `$params` | array | Yes | Configuration including -client_id and -client_secret |
| `$method` | string | No | HTTP method: GET or POST (default: POST) |
| `$data` | array | No | Request body for POST requests (auto JSON-encoded) |

**Returns:**

Success:
```php
array(
    'success' => true,
    'http_code' => 200,
    'data' => array(...)  // Decoded JSON response
)
```

Error:
```php
array(
    'error' => 'API request failed',
    'http_code' => 400,
    'response' => array(...)
)
```

**Example:**
```php
$result = uspsApiRequest(
    '/prices/v3/base-rates/search',
    array(
        '-client_id' => 'your_consumer_key',
        '-client_secret' => 'your_consumer_secret'
    ),
    'POST',
    array(
        'originZIPCode' => '90210',
        'destinationZIPCode' => '10001',
        'weight' => 16
    )
);
```

**Notes:**
- Automatically handles OAuth authentication
- JSON encodes POST data
- Parses JSON responses
- Returns consistent error structure

---

### uspsServices()

**Purpose:** Get USPS shipping rates for domestic and international shipments

**Signature:**
```php
function uspsServices(array $params = array()): array
```

**Parameters:**

**Required (All Shipments):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `-client_id` | string | USPS Consumer Key |
| `-client_secret` | string | USPS Consumer Secret |
| `-weight` | float | Package weight in ounces (16oz = 1lb) |

**Required (Domestic):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `-zip_orig` | string | Origin ZIP code (5 digits) |
| `-zip_dest` | string | Destination ZIP code (5 digits) |

**Required (International):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `-intl` | bool | Set to `true` for international |
| `-country` | string | Country code (ISO 2-letter, e.g., 'CA') |
| `-zip_orig` | string | Origin ZIP code (5 digits) |

**Optional:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `-test` | bool | Use testing environment |
| `-length` | float | Package length in inches |
| `-width` | float | Package width in inches |
| `-height` | float | Package height in inches |
| `-mail_class` | string | Specific mail class or 'ALL' (default) |
| `-processing_category` | string | 'MACHINABLE', 'NON_MACHINABLE' |
| `-destination_entry_facility` | string | Facility type |
| `-foreign_postal_code` | string | Foreign postal code (international) |

**Mail Class Options:**
- `PRIORITY_MAIL_EXPRESS`
- `PRIORITY_MAIL`
- `USPS_GROUND_ADVANTAGE`
- `MEDIA_MAIL`
- `LIBRARY_MAIL`
- `BOUND_PRINTED_MATTER`
- `ALL` (default - returns all available services)

**Returns:**

Success:
```php
array(
    'rates' => array(
        'Priority Mail Express' => 26.35,
        'Priority Mail' => 8.45,
        'USPS Ground Advantage' => 5.50
    ),
    'params' => array(...),
    'request_data' => array(...),
    'result' => array(...)
)
```

Error:
```php
array(
    'error' => 'Missing required parameter: -zip_dest'
)
```

**Examples:**

**Domestic Rates:**
```php
$rates = uspsServices(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-weight' => 16,          // 1 pound
    '-zip_orig' => '90210',
    '-zip_dest' => '10001',
    '-length' => 10,
    '-width' => 8,
    '-height' => 6
));

if (isset($rates['rates'])) {
    foreach ($rates['rates'] as $service => $price) {
        echo sprintf("%s: $%.2f\n", $service, $price);
    }
}
```

**International Rates:**
```php
$rates = uspsServices(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-weight' => 32,          // 2 pounds
    '-zip_orig' => '90210',
    '-country' => 'CA',       // Canada
    '-intl' => true,
    '-foreign_postal_code' => 'M5H 2N2'
));

if (isset($rates['rates'])) {
    echo "International Rates to Canada:\n";
    foreach ($rates['rates'] as $service => $price) {
        echo "  $service: $$price\n";
    }
}
```

**Specific Mail Class:**
```php
$rates = uspsServices(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-weight' => 16,
    '-zip_orig' => '90210',
    '-zip_dest' => '10001',
    '-mail_class' => 'PRIORITY_MAIL'  // Only Priority Mail rates
));
```

**API Endpoints:**
- Domestic: `POST /prices/v3/base-rates/search`
- International: `POST /international-prices/v3/international-prices`

**Documentation:** [Domestic Prices](https://developers.usps.com/domesticpricesv3) | [International Prices](https://developers.usps.com/internationalpricesv3)

---

### uspsTrack()

**Purpose:** Track USPS package using tracking number

**Signature:**
```php
function uspsTrack(array $params = array()): array
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `-client_id` | string | Yes | USPS Consumer Key |
| `-client_secret` | string | Yes | USPS Consumer Secret |
| `-tn` | string | Yes | Tracking number (auto-cleaned) |
| `-test` | bool | No | Use testing environment |

**Returns:**

Success:
```php
array(
    'status' => 'Delivered',
    'tracking_number' => '9400111899562853289749',
    'carrier' => 'USPS',
    'method' => 'N/A',
    'summary' => 'Delivered - 2024-01-15 10:30 AM - New York, NY',
    'detail' => array(
        'Delivered - 2024-01-15 10:30 AM - New York, NY',
        'Out for Delivery - 2024-01-15 08:15 AM - New York, NY',
        'Arrived at Post Office - 2024-01-14 22:45 PM - New York, NY'
    ),
    'params' => array(...),
    'result' => array(...)
)
```

Error:
```php
array(
    'error' => 'API request failed',
    'status' => 'Error',
    'tracking_number' => '...',
    'carrier' => 'USPS',
    'method' => 'N/A'
)
```

**Status Values:**
- `Delivered` - Package delivered
- `Out for Delivery` - Out for delivery today
- `In Transit` - Package in transit
- `Arrived` - Arrived at facility
- `Error` - Tracking error or not found
- `Unknown` - Unknown status

**Example:**
```php
$tracking = uspsTrack(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-tn' => '9400 1118 9956 2853 2897 49'  // Spaces/dashes OK
));

if ($tracking['status'] == 'Delivered') {
    echo "Package delivered!\n";
    echo "Details: " . $tracking['summary'] . "\n";
} elseif ($tracking['status'] == 'In Transit') {
    echo "Package in transit\n";
    echo "Recent activity:\n";
    foreach ($tracking['detail'] as $event) {
        echo "  - $event\n";
    }
} else {
    echo "Status: " . $tracking['status'] . "\n";
    if (isset($tracking['error'])) {
        echo "Error: " . $tracking['error'] . "\n";
    }
}
```

**Notes:**
- Tracking numbers are automatically cleaned (spaces/dashes removed)
- Detail array contains all tracking events (newest first)
- Summary contains the most recent tracking event
- Supports all USPS tracking number formats

**API Endpoint:** `GET /tracking/v3/tracking/{trackingNumber}`

**Documentation:** [Tracking v3 API](https://developers.usps.com/trackingv3)

---

### uspsVerifyAddress()

**Purpose:** Verify and standardize USPS addresses

**Signature:**
```php
function uspsVerifyAddress(array $params = array()): array
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `-client_id` | string | Yes | USPS Consumer Key |
| `-client_secret` | string | Yes | USPS Consumer Secret |
| `address` | array | Yes | Indexed array of addresses to verify |
| `-test` | bool | No | Use testing environment |

**Address Array Format:**

Each address should contain (supports both old and new field names):

| Field (Old) | Field (New) | Required | Description |
|-------------|-------------|----------|-------------|
| `Address2` | `streetAddress` | Yes | Primary street address |
| `City` | `city` | Yes | City name |
| `State` | `state` | Yes | State code (2 letters) |
| `Address1` | `secondaryAddress` | No | Apt, suite, unit, etc. |
| `Zip5` | `ZIPCode` | No | ZIP code (5 digits) |
| `Zip4` | `ZIPPlus4` | No | ZIP+4 extension |

**Returns:**

```php
array(
    'address' => array(
        0 => array(
            'in' => array(
                'Address1' => '',
                'Address2' => '6406 ivy ln',
                'City' => 'greenbelt',
                'State' => 'md',
                'Zip5' => ''
            ),
            'out' => array(
                'Address1' => '',
                'Address2' => '6406 IVY LN',
                'City' => 'GREENBELT',
                'State' => 'MD',
                'Zip5' => '20770',
                'Zip4' => '1441'
            ),
            'diff' => array('Address2', 'City', 'State')
        )
    ),
    'attn' => 1  // 1 = corrections needed, 0 = all valid
)
```

**Examples:**

**Single Address Verification:**
```php
$verification = uspsVerifyAddress(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    'address' => array(
        0 => array(
            'Address1' => '',
            'Address2' => '6406 Ivy Lane',
            'City' => 'Greenbelt',
            'State' => 'MD',
            'Zip5' => ''
        )
    )
));

if ($verification['attn'] == 0) {
    echo "Address is valid as entered\n";
} else {
    $addr = $verification['address'][0];
    echo "Address corrections needed:\n";
    echo "Original: " . $addr['in']['Address2'] . "\n";
    echo "Standard: " . $addr['out']['Address2'] . "\n";
    echo "ZIP+4: " . $addr['out']['Zip5'] . '-' . $addr['out']['Zip4'] . "\n";
}
```

**Multiple Address Verification:**
```php
$verification = uspsVerifyAddress(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    'address' => array(
        0 => array(
            'Address2' => '123 Main St',
            'City' => 'Los Angeles',
            'State' => 'CA',
            'Zip5' => '90210'
        ),
        1 => array(
            'Address2' => '456 Broadway',
            'City' => 'New York',
            'State' => 'NY',
            'Zip5' => '10001'
        )
    )
));

foreach ($verification['address'] as $id => $addr) {
    echo "Address $id:\n";
    if (isset($addr['out']['err'])) {
        echo "  Error: " . $addr['out']['err'] . "\n";
    } else {
        echo "  Valid: " . $addr['out']['Address2'] . "\n";
        echo "  City: " . $addr['out']['City'] . "\n";
        echo "  ZIP: " . $addr['out']['Zip5'] . "\n";
    }
}
```

**Notes:**
- Addresses are standardized to USPS format (uppercase)
- ZIP+4 is automatically added when available
- `diff` array shows which fields were changed
- Supports batch verification (multiple addresses)
- Backward compatible with old field names

**API Endpoint:** `POST /addresses/v3/address`

**Documentation:** [Addresses v3 API](https://developers.usps.com/addressesv3)

---

### uspsZipCodeInfo()

**Purpose:** Get city and state information from ZIP code

**Signature:**
```php
function uspsZipCodeInfo(string $zip = '', array $params = array()): array
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$zip` | string | Yes | ZIP code (5 digits) |
| `-client_id` | string | Yes | USPS Consumer Key |
| `-client_secret` | string | Yes | USPS Consumer Secret |
| `-test` | bool | No | Use testing environment |

**Returns:**

Success:
```php
array(
    'zip' => '90210',
    'city' => 'BEVERLY HILLS',
    'state' => 'CA',
    'zip5' => '90210',
    'zip4' => '',
    'result' => array(...)
)
```

Error:
```php
array(
    'zip' => '00000',
    'error' => 'Invalid ZIP code',
    'result' => array(...)
)
```

**Example:**
```php
$zipInfo = uspsZipCodeInfo('90210', array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret'
));

if (!isset($zipInfo['error'])) {
    echo "ZIP Code: " . $zipInfo['zip'] . "\n";
    echo "City: " . $zipInfo['city'] . "\n";
    echo "State: " . $zipInfo['state'] . "\n";
} else {
    echo "Invalid ZIP code: " . $zipInfo['error'] . "\n";
}
```

**Use Cases:**
- Auto-complete city/state in address forms
- Validate ZIP codes
- Calculate shipping zones
- Display location information

**API Endpoint:** `POST /addresses/v3/address` (with ZIP-only lookup)

**Documentation:** [Addresses v3 API](https://developers.usps.com/addressesv3)

---

### uspsExpressMailLabel()

**Purpose:** Create USPS shipping label with tracking

**Signature:**
```php
function uspsExpressMailLabel(array $params = array()): array
```

**Parameters:**

**Required (Authentication):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `-client_id` | string | USPS Consumer Key |
| `-client_secret` | string | USPS Consumer Secret |
| `-payment_account_number` | string | USPS Payment Account Number |

**Required (From Address):**

| Parameter | Type | Max Length | Description |
|-----------|------|------------|-------------|
| `shipfromfirstname` | string | 26 | Sender first name |
| `shipfromlastname` | string | 26 | Sender last name |
| `shipfromaddress1` | string | 50 | Sender street address |
| `shipfromcity` | string | 28 | Sender city |
| `shipfromstate` | string | 2 | Sender state code |
| `shipfromzipcode` | string | 5 | Sender ZIP code |

**Required (To Address):**

| Parameter | Type | Max Length | Description |
|-----------|------|------------|-------------|
| `shiptofirstname` | string | 26 | Recipient first name |
| `shiptolastname` | string | 26 | Recipient last name |
| `shiptoaddress1` | string | 50 | Recipient street address |
| `shiptocity` | string | 28 | Recipient city |
| `shiptostate` | string | 2 | Recipient state code |
| `shiptozipcode` | string | 5 | Recipient ZIP code |

**Required (Package):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `weight` | float | Weight in ounces (max 1120oz = 70lbs) |
| `length` | float | Length in inches |
| `width` | float | Width in inches |
| `height` | float | Height in inches |

**Optional:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `-test` | bool | false | Use testing environment |
| `-mail_class` | string | PRIORITY_MAIL_EXPRESS | Mail class |
| `-processing_category` | string | MACHINABLE | Processing category |
| `shipfromcompany` | string | - | Sender company (max 26) |
| `shipfromaddress2` | string | - | Sender apt/suite (max 50) |
| `shipfromtelephone` | string | - | Sender phone (10 digits) |
| `shiptocompany` | string | - | Recipient company (max 26) |
| `shiptoaddress2` | string | - | Recipient apt/suite (max 50) |
| `shiptotelephone` | string | - | Recipient phone (10 digits) |
| `shiptoemail` | string | - | Recipient email |
| `insured_amount` | float | 0 | Insurance amount in dollars |
| `label_type` | string | PDF | Label format |

**Mail Class Options:**
- `PRIORITY_MAIL_EXPRESS` (default)
- `PRIORITY_MAIL`
- `USPS_GROUND_ADVANTAGE`
- `MEDIA_MAIL`
- `LIBRARY_MAIL`
- `BOUND_PRINTED_MATTER`

**Label Type Options:**
- `PDF` (default) - Standard PDF format
- `PNG` - PNG image format
- `ZPLII` - ZPL II for thermal printers
- `4X6PDF` - 4x6 inch PDF
- `4X6ZPLII` - 4x6 inch ZPL II

**Processing Category Options:**
- `MACHINABLE` (default)
- `NON_MACHINABLE`
- `IRREGULAR`

**Returns:**

Success:
```php
array(
    'tracking_number' => '9400111899562853289749',
    'label_image' => 'JVBERi0xLjQKJeLjz9MKMSAwIG9iaiA8PC...',  // Base64-encoded
    'carrier' => 'USPS',
    'method' => 'PRIORITY_MAIL',
    'postage' => 8.45,
    'sku' => 'DVXP0XXUXXXXX8',
    'label_metadata' => array(...),
    'params' => array(...),
    'request_data' => array(...),
    'result' => array(...)
)
```

Error:
```php
array(
    'error' => 'Missing required parameter: shipfromzipcode',
    'carrier' => 'USPS',
    'method' => '...',
    'result' => array(...)
)
```

**Examples:**

**Basic Priority Mail Label:**
```php
$label = uspsExpressMailLabel(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-payment_account_number' => 'your_account_number',
    '-mail_class' => 'PRIORITY_MAIL',

    // From address
    'shipfromfirstname' => 'John',
    'shipfromlastname' => 'Doe',
    'shipfromaddress1' => '123 Main St',
    'shipfromcity' => 'Los Angeles',
    'shipfromstate' => 'CA',
    'shipfromzipcode' => '90210',

    // To address
    'shiptofirstname' => 'Jane',
    'shiptolastname' => 'Smith',
    'shiptoaddress1' => '456 Broadway',
    'shiptocity' => 'New York',
    'shiptostate' => 'NY',
    'shiptozipcode' => '10001',

    // Package
    'weight' => 16,    // 1 pound
    'length' => 10,
    'width' => 8,
    'height' => 6
));

if (isset($label['tracking_number'])) {
    // Save label to file
    $label_data = base64_decode($label['label_image']);
    $filename = 'label_' . $label['tracking_number'] . '.pdf';
    file_put_contents($filename, $label_data);

    echo "Label created successfully!\n";
    echo "Tracking: " . $label['tracking_number'] . "\n";
    echo "Postage: $" . $label['postage'] . "\n";
    echo "Label saved to: $filename\n";
} else {
    echo "Error: " . $label['error'] . "\n";
}
```

**Priority Mail Express with Insurance:**
```php
$label = uspsExpressMailLabel(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-payment_account_number' => 'your_account_number',
    '-mail_class' => 'PRIORITY_MAIL_EXPRESS',

    // From address
    'shipfromfirstname' => 'John',
    'shipfromlastname' => 'Doe',
    'shipfromcompany' => 'Acme Corp',
    'shipfromaddress1' => '123 Main St',
    'shipfromaddress2' => 'Suite 100',
    'shipfromcity' => 'Los Angeles',
    'shipfromstate' => 'CA',
    'shipfromzipcode' => '90210',
    'shipfromtelephone' => '(310) 555-1234',

    // To address
    'shiptofirstname' => 'Jane',
    'shiptolastname' => 'Smith',
    'shiptocompany' => 'Smith Industries',
    'shiptoaddress1' => '456 Broadway',
    'shiptoaddress2' => 'Floor 5',
    'shiptocity' => 'New York',
    'shiptostate' => 'NY',
    'shiptozipcode' => '10001',
    'shiptotelephone' => '(212) 555-5678',
    'shiptoemail' => 'jane@example.com',

    // Package
    'weight' => 32,    // 2 pounds
    'length' => 12,
    'width' => 10,
    'height' => 8,

    // Optional services
    'insured_amount' => 500,      // $500 insurance
    'label_type' => 'PDF'
));
```

**Notes:**
- All string fields are automatically truncated to USPS limits
- Phone numbers are auto-formatted (non-digits removed)
- Label image is base64-encoded (decode before saving)
- Insurance automatically adds INSURANCE extra service
- Requires valid USPS payment account

**API Endpoint:** `POST /labels/v3/label`

**Documentation:** [Domestic Labels v3 API](https://developers.usps.com/domesticlabelsv3)

---

## Error Handling

### Error Response Format

All functions return errors in a consistent format:

```php
array(
    'error' => 'Error message description',
    'http_code' => 400,         // HTTP status code (optional)
    'response' => array(...)    // Raw API response (optional)
)
```

### Common Error Codes

| HTTP Code | Meaning | Solution |
|-----------|---------|----------|
| 400 | Bad Request | Check input parameters |
| 401 | Unauthorized | Verify client_id and client_secret |
| 403 | Forbidden | Check API access permissions |
| 404 | Not Found | Verify tracking number or endpoint |
| 429 | Too Many Requests | Rate limit exceeded, wait and retry |
| 500 | Server Error | USPS API issue, retry later |

### Error Handling Best Practices

```php
$rates = uspsServices($params);

// Check for error
if (isset($rates['error'])) {
    // Log error
    error_log('USPS API Error: ' . $rates['error']);

    // Check for specific error types
    if (isset($rates['http_code'])) {
        switch ($rates['http_code']) {
            case 401:
                // Authentication failed
                echo "Authentication error. Check credentials.";
                break;
            case 429:
                // Rate limit
                echo "Rate limit exceeded. Please wait.";
                break;
            case 500:
                // Server error
                echo "USPS API temporarily unavailable.";
                break;
            default:
                echo "API error: " . $rates['error'];
        }
    } else {
        // Parameter validation error
        echo "Input error: " . $rates['error'];
    }

    return false;
}

// Success - process rates
if (isset($rates['rates'])) {
    foreach ($rates['rates'] as $service => $price) {
        echo "$service: $$price\n";
    }
}
```

---

## Code Examples

### Complete Shipping Workflow

```php
<?php
require_once('usps.php');

// Configuration
$config = array(
    '-client_id' => 'YOUR_CONSUMER_KEY',
    '-client_secret' => 'YOUR_CONSUMER_SECRET',
    '-payment_account_number' => 'YOUR_ACCOUNT_NUMBER'
);

// Step 1: Verify sender address
$from_address = array(
    0 => array(
        'Address2' => '123 Main St',
        'City' => 'Los Angeles',
        'State' => 'CA',
        'Zip5' => '90210'
    )
);

$verify_from = uspsVerifyAddress(array_merge($config, array(
    'address' => $from_address
)));

if ($verify_from['attn'] == 1) {
    echo "From address needs correction:\n";
    print_r($verify_from['address'][0]['out']);
}

// Step 2: Verify recipient address
$to_address = array(
    0 => array(
        'Address2' => '456 Broadway',
        'City' => 'New York',
        'State' => 'NY',
        'Zip5' => '10001'
    )
);

$verify_to = uspsVerifyAddress(array_merge($config, array(
    'address' => $to_address
)));

if ($verify_to['attn'] == 1) {
    echo "To address needs correction:\n";
    print_r($verify_to['address'][0]['out']);
}

// Step 3: Get shipping rates
$rates = uspsServices(array_merge($config, array(
    '-weight' => 16,
    '-zip_orig' => $verify_from['address'][0]['out']['Zip5'],
    '-zip_dest' => $verify_to['address'][0]['out']['Zip5'],
    '-length' => 10,
    '-width' => 8,
    '-height' => 6
)));

echo "\nAvailable Rates:\n";
foreach ($rates['rates'] as $service => $price) {
    echo sprintf("  %-30s $%.2f\n", $service, $price);
}

// Step 4: Create label (Priority Mail)
$label = uspsExpressMailLabel(array_merge($config, array(
    '-mail_class' => 'PRIORITY_MAIL',

    'shipfromfirstname' => 'John',
    'shipfromlastname' => 'Doe',
    'shipfromaddress1' => $verify_from['address'][0]['out']['Address2'],
    'shipfromcity' => $verify_from['address'][0]['out']['City'],
    'shipfromstate' => $verify_from['address'][0]['out']['State'],
    'shipfromzipcode' => $verify_from['address'][0]['out']['Zip5'],

    'shiptofirstname' => 'Jane',
    'shiptolastname' => 'Smith',
    'shiptoaddress1' => $verify_to['address'][0]['out']['Address2'],
    'shiptocity' => $verify_to['address'][0]['out']['City'],
    'shiptostate' => $verify_to['address'][0]['out']['State'],
    'shiptozipcode' => $verify_to['address'][0]['out']['Zip5'],

    'weight' => 16,
    'length' => 10,
    'width' => 8,
    'height' => 6,

    'insured_amount' => 100
)));

if (isset($label['tracking_number'])) {
    // Save label
    $label_data = base64_decode($label['label_image']);
    $filename = 'label_' . $label['tracking_number'] . '.pdf';
    file_put_contents($filename, $label_data);

    echo "\nLabel created successfully!\n";
    echo "Tracking Number: " . $label['tracking_number'] . "\n";
    echo "Postage: $" . $label['postage'] . "\n";
    echo "Label saved to: $filename\n";

    // Step 5: Track package
    sleep(2);  // Wait for tracking to activate
    $tracking = uspsTrack(array_merge($config, array(
        '-tn' => $label['tracking_number']
    )));

    echo "\nTracking Status: " . $tracking['status'] . "\n";
    if (isset($tracking['summary'])) {
        echo "Latest Update: " . $tracking['summary'] . "\n";
    }
}
?>
```

### Bulk Address Verification

```php
<?php
require_once('usps.php');

$config = array(
    '-client_id' => 'YOUR_CONSUMER_KEY',
    '-client_secret' => 'YOUR_CONSUMER_SECRET'
);

// Bulk addresses from CSV or database
$addresses = array(
    array(
        'Address2' => '123 Main St',
        'City' => 'Los Angeles',
        'State' => 'CA',
        'Zip5' => '90210'
    ),
    array(
        'Address2' => '456 Broadway',
        'City' => 'New York',
        'State' => 'NY',
        'Zip5' => '10001'
    ),
    array(
        'Address2' => '789 Market St',
        'City' => 'San Francisco',
        'State' => 'CA',
        'Zip5' => '94102'
    )
);

$verification = uspsVerifyAddress(array_merge($config, array(
    'address' => $addresses
)));

// Process results
foreach ($verification['address'] as $id => $result) {
    echo "Address " . ($id + 1) . ":\n";
    echo "  Input: " . $result['in']['Address2'] . ", " . $result['in']['City'] . "\n";

    if (isset($result['out']['err'])) {
        echo "  ERROR: " . $result['out']['err'] . "\n";
    } else {
        echo "  Valid: " . $result['out']['Address2'] . "\n";
        echo "  City: " . $result['out']['City'] . ", " . $result['out']['State'] . "\n";
        echo "  ZIP: " . $result['out']['Zip5'] . "-" . $result['out']['Zip4'] . "\n";

        if (isset($result['diff']) && count($result['diff']) > 0) {
            echo "  CORRECTED: " . implode(', ', $result['diff']) . "\n";
        }
    }
    echo "\n";
}
?>
```

---

## Testing

### Testing Environment

Use the `-test` parameter to access the USPS testing environment:

```php
$rates = uspsServices(array(
    '-client_id' => 'test_consumer_key',
    '-client_secret' => 'test_consumer_secret',
    '-test' => true,  // Use testing environment
    '-weight' => 16,
    '-zip_orig' => '90210',
    '-zip_dest' => '10001'
));
```

### Test Credentials

Get test credentials from the USPS Developer Portal:
1. Create a test app
2. Use separate credentials for testing
3. Testing environment: `https://apis-tem.usps.com`

### Test ZIP Codes

USPS provides test ZIP codes for the testing environment:
- `90210` - Beverly Hills, CA
- `10001` - New York, NY
- `60601` - Chicago, IL

---

## Best Practices

### 1. Credential Management

```php
// Store credentials securely
define('USPS_CLIENT_ID', getenv('USPS_CLIENT_ID'));
define('USPS_CLIENT_SECRET', getenv('USPS_CLIENT_SECRET'));

// Use in API calls
$rates = uspsServices(array(
    '-client_id' => USPS_CLIENT_ID,
    '-client_secret' => USPS_CLIENT_SECRET,
    // ...
));
```

### 2. Error Logging

```php
function callUSPSAPI($function, $params) {
    $result = $function($params);

    if (isset($result['error'])) {
        error_log(sprintf(
            'USPS API Error: %s | Params: %s',
            $result['error'],
            json_encode($params)
        ));
    }

    return $result;
}
```

### 3. Response Caching

```php
function getCachedRates($params) {
    $cache_key = 'usps_rates_' . md5(json_encode($params));
    $cache_ttl = 3600;  // 1 hour

    // Check cache
    $cached = apc_fetch($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    // Call API
    $rates = uspsServices($params);

    // Cache result
    if (isset($rates['rates'])) {
        apc_store($cache_key, $rates, $cache_ttl);
    }

    return $rates;
}
```

### 4. Input Validation

```php
function validateZipCode($zip) {
    return preg_match('/^\d{5}(-\d{4})?$/', $zip);
}

function validateWeight($weight) {
    return is_numeric($weight) && $weight > 0 && $weight <= 1120;
}

// Use before API calls
if (!validateZipCode($params['-zip_orig'])) {
    return array('error' => 'Invalid origin ZIP code');
}
```

### 5. Rate Limiting

```php
class USPSRateLimiter {
    private $calls = array();
    private $limit = 60;  // 60 calls per hour
    private $window = 3600;  // 1 hour in seconds

    public function canMakeRequest() {
        $now = time();

        // Remove old calls outside window
        $this->calls = array_filter($this->calls, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });

        return count($this->calls) < $this->limit;
    }

    public function recordRequest() {
        $this->calls[] = time();
    }
}
```

---

## Troubleshooting

### Common Issues

**1. Authentication Failures**

```
Error: OAuth authentication failed
HTTP Code: 401
```

**Solution:**
- Verify Consumer Key and Consumer Secret
- Check that credentials are for correct environment (test vs production)
- Ensure no extra spaces in credentials

**2. Rate Limit Exceeded**

```
Error: API request failed
HTTP Code: 429
Response: Too Many Requests
```

**Solution:**
- Implement request caching
- Add delay between requests
- Monitor usage in Developer Portal
- Consider upgrading API plan

**3. Invalid Address**

```
Error: Address not found
```

**Solution:**
- Verify address format
- Use address verification first
- Check for typos in city/state
- Ensure ZIP code is valid

**4. Label Creation Fails**

```
Error: Missing required parameter: -payment_account_number
```

**Solution:**
- Verify payment account is active
- Check account number is correct
- Ensure account has sufficient balance
- Confirm account permissions

**5. Token Caching Issues**

If tokens aren't caching properly:

```php
// Clear token cache
global $USPS_ACCESS_TOKEN_CACHE;
$USPS_ACCESS_TOKEN_CACHE = array();

// Force new token
$token = uspsGetAccessToken($params);
```

---

## Migration from Old API

### Parameter Changes

| Old Parameter | New Parameter | Notes |
|---------------|---------------|-------|
| `-userid` | `-client_id` | Consumer Key from Developer Portal |
| `-password` | `-client_secret` | Consumer Secret from Developer Portal |
| `-tn` | `-tn` | No change |
| `-weight` | `-weight` | No change |
| `-zip_orig` | `-zip_orig` | No change |
| `-zip_dest` | `-zip_dest` | No change |

### Function Changes

| Old Function | New Function | Status |
|--------------|--------------|--------|
| `uspsServices()` | `uspsServices()` | Updated, same name |
| `uspsTrack()` | `uspsTrack()` | Updated, same name |
| `uspsVerifyAddress()` | `uspsVerifyAddress()` | Updated, same name |
| `uspsZipCodeInfo()` | `uspsZipCodeInfo()` | Updated, same name |
| `uspsExpressMailLabel()` | `uspsExpressMailLabel()` | Updated, same name |

### Migration Checklist

- [ ] Register for USPS API v3 account
- [ ] Get Consumer Key and Consumer Secret
- [ ] Update authentication parameters
- [ ] Test in testing environment
- [ ] Update error handling for new format
- [ ] Implement rate limiting
- [ ] Update to production environment
- [ ] Monitor for errors

---

## Resources

### Official Documentation
- [USPS Developer Portal](https://developers.usps.com/)
- [API Catalog](https://developers.usps.com/apis)
- [OAuth 2.0 Guide](https://developers.usps.com/oauth)
- [Migration Guide (PDF)](https://www.usps.com/business/web-tools-apis/onboarding-guide.pdf)

### API Documentation
- [Domestic Prices API](https://developers.usps.com/domesticpricesv3)
- [International Prices API](https://developers.usps.com/internationalpricesv3)
- [Tracking API](https://developers.usps.com/trackingv3)
- [Addresses API](https://developers.usps.com/addressesv3)
- [Domestic Labels API](https://developers.usps.com/domesticlabelsv3)

### Support
- Developer Portal Support
- Phone: 1-800-344-7779 (Internet Customer Care Center)
- [GitHub Examples](https://github.com/USPS/api-examples)
- [FAQ](https://developers.usps.com/faq)

### Additional Files
- [Migration Guide](USPS_MIGRATION_GUIDE.md) - Quick migration reference
- [usps.php](usps.php) - PHP library source code

---

## Version History

### Version 1.0.0 (January 2026)
- Initial release with USPS API v3 support
- Complete migration from Web Tools API
- OAuth 2.0 authentication
- Token caching implementation
- All core functions implemented
- Comprehensive documentation
- Production ready

---

## License

MIT License - See project license file for details

---

## Support

For issues with this library, please open an issue on the project repository.

For USPS API issues, contact USPS Developer Support:
- Web: https://developers.usps.com/
- Phone: 1-800-344-7779

---

**End of Documentation**
