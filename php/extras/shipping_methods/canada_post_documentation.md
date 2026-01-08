# Canada Post API Integration Documentation

## Overview

This library provides comprehensive integration with Canada Post web services for rating, shipping, tracking, and manifest management. All implemented functions use the latest production API versions as of January 2026.

## Table of Contents

1. [Getting Started](#getting-started)
2. [API Versions](#api-versions)
3. [Authentication](#authentication)
4. [Common Workflows](#common-workflows)
5. [Function Reference](#function-reference)
6. [Error Handling](#error-handling)
7. [Service Codes](#service-codes)
8. [Testing](#testing)

## Getting Started

### Prerequisites

- PHP 5.4 or higher
- cURL extension enabled
- SSL/TLS support
- Canada Post Developer Program credentials
- `xml2Array()` function (part of WaSQL framework)

### Obtaining API Credentials

1. Sign up for the Canada Post Developer Program:
   https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/gettingstarted.jsf

2. You will receive two sets of credentials:
   - Development (test) credentials - for testing with `ct.soa-gw.canadapost.ca`
   - Production credentials - for live transactions with `soa-gw.canadapost.ca`

3. Your credentials include:
   - Username (API key)
   - Password
   - Customer number (account number)
   - Contract ID (for contract customers)

## API Versions

This library implements the following API versions:

| Service | Version | Release Date | Status |
|---------|---------|--------------|--------|
| **Rating API** | v4 | April 2019 | ✅ Implemented |
| **Contract Shipping API** | v8 | June 2016 | ✅ Implemented |
| **Manifest API** | v8 | June 2016 | ✅ Implemented |
| **Non-Contract Shipping API** | v4 | June 2016 | ⏳ Planned |
| **Tracking API** | v2 | April 2016 | ⏳ Planned |
| **Customer Information API** | v1 | Nov 2011 | ⏳ Planned |

✅ = Fully implemented | ⏳ = Planned for future implementation

## Authentication

All API calls use HTTP Basic Authentication. Pass your credentials in the parameters:

```php
$params = [
    '-username' => 'your_api_username',
    '-password' => 'your_api_password',
    '-account_number' => 'your_customer_number'
];
```

## Common Workflows

### Workflow 1: Get Shipping Rates

```php
// Get rates for a domestic shipment
$rates = cpGetRates([
    '-username' => 'your_username',
    '-password' => 'your_password',
    '-account_number' => '1234567890',
    'sender_zipcode' => 'K1A0B1',        // Ottawa, ON
    'recipient_zipcode' => 'M5W1E6',     // Toronto, ON
    'parcel_weight' => 2.5,               // 2.5 kg
    'parcel_length' => 30,                // 30 cm (optional)
    'parcel_width' => 20,                 // 20 cm (optional)
    'parcel_height' => 10                 // 10 cm (optional)
]);

// Display available services
if (is_array($rates) && !isset($rates['-error'])) {
    foreach ($rates as $rate) {
        echo "{$rate['name']}: \${$rate['total']} ";
        echo "(Delivery: {$rate['expected_delivery_date']})\n";
    }
}
```

### Workflow 2: Create Shipment and Get Label

```php
// Step 1: Create a shipment
$shipment = cpCreateShipment([
    '-username' => 'your_username',
    '-password' => 'your_password',
    '-account_number' => '1234567890',
    '-service_code' => 'DOM.EP',              // Expedited Parcel

    // Sender information
    'sender_company' => 'My Company Inc',
    'sender_name' => 'John Smith',
    'sender_phone' => '613-555-1234',
    'sender_address' => '123 Main Street',
    'sender_city' => 'Ottawa',
    'sender_state' => 'ON',
    'sender_country' => 'CA',
    'sender_zipcode' => 'K1A0B1',

    // Recipient information
    'recipient_name' => 'Jane Doe',
    'recipient_company' => 'ABC Corp',
    'recipient_phone' => '416-555-9876',
    'recipient_address' => '456 Queen Street West',
    'recipient_city' => 'Toronto',
    'recipient_state' => 'ON',
    'recipient_country' => 'CA',
    'recipient_zipcode' => 'M5W1E6',
    'recipient_email' => 'jane@example.com',

    // Parcel information
    'parcel_weight' => 2.5,
    'parcel_length' => 30,
    'parcel_width' => 20,
    'parcel_height' => 10,

    // Order reference
    'ordernumber' => 'ORD-2026-001',
    'message' => 'Thank you for your order!',

    // Optional: output format
    '-output_format' => '4x6'  // or '8.5x11' (default)
]);

// Step 2: Check for errors
if (isset($shipment['-error'])) {
    echo "Error: " . $shipment['-error'] . "\n";
    exit;
}

// Step 3: Get the shipping label
$label_file = cpGetShipmentLabel([
    '-username' => 'your_username',
    '-password' => 'your_password',
    'label_url' => $shipment['artifact_url']
]);

// Step 4: Use the label
if (is_string($label_file)) {
    echo "Label saved to: {$label_file}\n";
    echo "Tracking PIN: {$shipment['tracking-pin']}\n";
} else {
    echo "Error retrieving label\n";
}
```

### Workflow 3: Manifest Multiple Shipments

```php
// Step 1: Create multiple shipments with the same group-id
$group_id = date('Ymd');  // e.g., 20260107

$shipments = [];
for ($i = 1; $i <= 5; $i++) {
    $shipment = cpCreateShipment([
        '-username' => 'your_username',
        '-password' => 'your_password',
        '-account_number' => '1234567890',
        '-group_id' => $group_id,  // Important: Same group ID for all
        '-service_code' => 'DOM.EP',
        // ... other shipment details ...
        'ordernumber' => "ORD-2026-00{$i}"
    ]);

    if (!isset($shipment['-error'])) {
        $shipments[] = $shipment;
    }
}

// Step 2: Transmit all shipments in the group
$transmit = cpTransmitShipments([
    '-username' => 'your_username',
    '-password' => 'your_password',
    '-account_number' => '1234567890',
    '-group_id' => $group_id,
    'sender_company' => 'My Company Inc',
    'sender_phone' => '613-555-1234',
    'sender_address' => '123 Main Street',
    'sender_city' => 'Ottawa',
    'sender_state' => 'ON',
    'sender_country' => 'CA',
    'sender_zipcode' => 'K1A0B1'
]);

// Step 3: Get the manifest (MANDATORY)
if (isset($transmit['manifest_url'])) {
    $manifest = cpGetManifest([
        '-username' => 'your_username',
        '-password' => 'your_password',
        'manifest_url' => $transmit['manifest_url']
    ]);

    // Step 4: Get the manifest artifact (PDF)
    if (isset($manifest['artifact_url'])) {
        $manifest_file = cpGetManifestArtifact([
            '-username' => 'your_username',
            '-password' => 'your_password',
            'artifact_url' => $manifest['artifact_url']
        ]);

        if (is_string($manifest_file)) {
            echo "Manifest created!\n";
            echo "PO Number: {$manifest['po-number']}\n";
            echo "Manifest PDF: {$manifest_file}\n";
        }
    }
}
```

## Function Reference

### Rating Functions

#### cpGetRates()

Get shipping rates for a parcel.

**Parameters:**
- `-username` (string, required) - API username
- `-password` (string, required) - API password
- `-account_number` (string, required) - Customer number
- `sender_zipcode` (string, required) - Origin postal code
- `recipient_zipcode` (string, required) - Destination postal code
- `parcel_weight` (float, required) - Weight in kg
- `-test` (bool, optional) - Use test environment
- `parcel_length` (float, optional) - Length in cm
- `parcel_width` (float, optional) - Width in cm
- `parcel_height` (float, optional) - Height in cm
- `destination_country` (string, optional) - Country code (CA, US, etc.)

**Returns:**
Array of rates, each containing:
- `code` - Service code
- `name` - Service name
- `base` - Base cost
- `total` - Total cost with taxes
- `expected_delivery_date` - Expected delivery date
- `expected_transit_time` - Transit time in days
- Tax information (gst, pst, hst)

### Shipping Functions

#### cpCreateShipment()

Create a contract shipment.

**Parameters:** (See Workflow 2 above for complete example)

**Returns:**
Array containing:
- `shipment-id` - Unique shipment identifier
- `shipment-status` - Status (created, transmitted)
- `tracking-pin` - Tracking number
- `artifact_url` - URL to get shipping label
- `self_url` - URL to get shipment details
- `price_url` - URL to get pricing (if requested)
- `receipt_url` - URL to get receipt (if requested)

#### cpGetShipmentLabel()

Download shipping label PDF.

**Parameters:**
- `-username` (string, required) - API username
- `-password` (string, required) - API password
- `label_url` (string, required) - Label URL from cpCreateShipment()
- `label_path` (string, optional) - Custom path to save labels

**Returns:**
- String: File path to saved PDF on success
- Array: Error details on failure

### Manifest Functions

#### cpTransmitShipments()

Transmit shipments and initiate manifest creation.

**Parameters:**
- `-username` (string, required) - API username
- `-password` (string, required) - API password
- `-account_number` (string, required) - Customer number
- `-group_id` (string, optional) - Group ID (default: YYYYMMDD)
- `sender_*` fields (required) - Sender address information
- `-test` (bool, optional) - Use test environment

**Returns:**
Array containing `manifest_url` for use with cpGetManifest()

#### cpGetManifest()

Get manifest details (MANDATORY after cpTransmitShipments).

**Parameters:**
- `-username` (string, required) - API username
- `-password` (string, required) - API password
- `manifest_url` (string, required) - URL from cpTransmitShipments()

**Returns:**
Array containing:
- `po-number` - Canada Post PO number
- `artifact_url` - URL to get manifest PDF
- `details_url` - URL to get detailed information

#### cpGetManifestArtifact()

Download manifest PDF.

**Parameters:**
- `-username` (string, required) - API username
- `-password` (string, required) - API password
- `artifact_url` (string, required) - Artifact URL from cpGetManifest()

**Returns:**
- String: File path to saved PDF on success
- Array: Error details on failure

## Error Handling

All functions return arrays with error information when failures occur:

```php
$result = cpCreateShipment($params);

if (isset($result['-error'])) {
    // Error occurred
    echo "Error: " . $result['-error'] . "\n";

    // Check for missing parameters
    if (isset($result['-missing'])) {
        echo "Missing: " . implode(', ', $result['-missing']) . "\n";
    }

    // Check HTTP status code
    if (isset($result['-http_code'])) {
        echo "HTTP Code: " . $result['-http_code'] . "\n";
    }

    // Check API messages
    if (isset($result['-messages'])) {
        print_r($result['-messages']);
    }
} else {
    // Success
    echo "Tracking PIN: " . $result['tracking-pin'] . "\n";
}
```

## Service Codes

### Domestic Services (Canada)

| Code | Service Name | Description |
|------|-------------|-------------|
| DOM.RP | Regular Parcel | Standard ground shipping |
| DOM.EP | Expedited Parcel | Faster ground shipping |
| DOM.XP | Xpresspost | Next-day to major centers |
| DOM.XP.CERT | Xpresspost Certified | Xpresspost with signature |
| DOM.PC | Priority | Fastest service, guaranteed |
| DOM.LIB | Library Books | Discounted rate for libraries |

### USA Services

| Code | Service Name | Description |
|------|-------------|-------------|
| USA.EP | Expedited Parcel USA | 4-7 business days |
| USA.PW.ENV | Priority Worldwide Envelope USA | 3-4 business days |
| USA.PW.PAK | Priority Worldwide Pak USA | 3-4 business days |
| USA.PW.PARCEL | Priority Worldwide Parcel USA | 3-4 business days |
| USA.SP.AIR | Small Packet USA Air | 6-12 business days |
| USA.XP | Xpresspost USA | 2-3 business days |

### International Services

| Code | Service Name | Description |
|------|-------------|-------------|
| INT.XP | Xpresspost International | Express delivery worldwide |
| INT.IP.AIR | International Parcel Air | Standard air service |
| INT.IP.SURF | International Parcel Surface | Economy surface service |
| INT.PW.ENV | Priority Worldwide Envelope International | 3-6 business days |
| INT.PW.PAK | Priority Worldwide Pak International | 3-6 business days |
| INT.PW.PARCEL | Priority Worldwide Parcel International | 3-6 business days |

## Testing

### Using the Test Environment

To use the Canada Post test environment, set the `-test` parameter to `true`:

```php
$rates = cpGetRates([
    '-username' => 'test_username',
    '-password' => 'test_password',
    '-account_number' => 'test_account',
    '-test' => true,  // Use test environment
    'sender_zipcode' => 'K1A0B1',
    'recipient_zipcode' => 'M5W1E6',
    'parcel_weight' => 2.5
]);
```

### Test Credentials

Use your development credentials provided by Canada Post for testing. The test environment uses:
- URL: `https://ct.soa-gw.canadapost.ca`
- No real shipments are created
- No charges are incurred
- Test tracking numbers are provided

### Production Deployment

When ready for production:

1. Remove or set `-test` to `false`
2. Use production credentials
3. Verify all postal codes are real
4. Ensure proper error handling is in place
5. Test with small volumes first
6. Monitor API responses for errors

## Best Practices

1. **Always validate input** - Check addresses and weights before calling APIs
2. **Cache rates** - Don't call cpGetRates() on every page load
3. **Store tracking numbers** - Save tracking PINs to your database
4. **Handle errors gracefully** - Always check for errors and provide user feedback
5. **Use manifests** - Group shipments by date for easier pickup management
6. **Test thoroughly** - Use test environment before going live
7. **Keep labels organized** - Labels are saved to `./labels/` by default
8. **Secure credentials** - Never commit API credentials to source control

## Support and Resources

- **Canada Post Developer Program**: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/
- **API Versioning**: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/versioning.jsf
- **Service Directory**: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/default.jsf
- **WaSQL PHP Framework**: https://github.com/WaSQL/php

## License

MIT License - See repository for details

## Version History

- **v2.0.0** (January 2026) - Updated to latest Canada Post production APIs
  - Rating API v4
  - Contract Shipping API v8
  - Manifest API v8
  - Comprehensive PHPDocs
  - Improved error handling
  - Production-ready code

- **v1.0.0** - Initial release
  - Rating API v2
  - Contract Shipping API v5
  - Basic manifest support
