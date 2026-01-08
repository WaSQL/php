# USPS API Migration Guide

## Overview

The USPS Web Tools API was **retired on January 25, 2026**. This guide explains the migration to the new USPS API v3.

## What Changed

### Authentication
**Old (Web Tools):**
- Simple userid/password in XML requests
- No OAuth required

**New (API v3):**
- OAuth 2.0 with Consumer Key and Consumer Secret
- Bearer tokens that automatically expire and refresh
- Token caching implemented for efficiency

### Request Format
**Old:** XML-based requests
**New:** JSON-based REST API

### Base URLs
**Old:**
- `http://testing.shippingapis.com/ShippingAPITest.dll`
- `https://secure.shippingapis.com/ShippingAPI.dll`

**New:**
- Production: `https://apis.usps.com`
- Testing: `https://apis-tem.usps.com`

## Getting Started

### 1. Register for USPS API v3
1. Visit [USPS Developer Portal](https://developers.usps.com/)
2. Create an account
3. Create a new App to get your credentials:
   - **Consumer Key** (Client ID)
   - **Consumer Secret** (Client Secret)

### 2. Update Your Code

**Old Code:**
```php
$result = uspsServices(array(
    '-userid' => 'YOUR_USERID',
    '-weight' => 16,
    '-zip_orig' => '90210',
    '-zip_dest' => '10001'
));
```

**New Code:**
```php
$result = uspsServices(array(
    '-client_id' => 'YOUR_CONSUMER_KEY',
    '-client_secret' => 'YOUR_CONSUMER_SECRET',
    '-weight' => 16,
    '-zip_orig' => '90210',
    '-zip_dest' => '10001'
));
```

## Function Migration Details

### 1. uspsServices() - Get Shipping Rates

**Parameters Changed:**
- `-userid` → `-client_id`
- `-password` (removed) → `-client_secret`

**New Parameters:**
- `-mail_class`: Specific mail class (optional)
- `-processing_category`: Processing category (optional)

**Example:**
```php
$rates = uspsServices(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-weight' => 16,  // Weight in ounces
    '-zip_orig' => '90210',
    '-zip_dest' => '10001',
    '-length' => 10,  // Optional
    '-width' => 8,    // Optional
    '-height' => 6,   // Optional
    '-test' => false  // Use production
));

if (isset($rates['rates'])) {
    foreach ($rates['rates'] as $service => $price) {
        echo "$service: \$$price\n";
    }
}
```

**International:**
```php
$rates = uspsServices(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-weight' => 16,
    '-zip_orig' => '90210',
    '-country' => 'CA',  // Canada
    '-intl' => true
));
```

### 2. uspsTrack() - Track Packages

**Parameters Changed:**
- `-userid` → `-client_id`
- (Added) `-client_secret`

**Example:**
```php
$tracking = uspsTrack(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-tn' => '9400111899562853289749'
));

echo "Status: " . $tracking['status'] . "\n";
echo "Summary: " . $tracking['summary'] . "\n";
foreach ($tracking['detail'] as $event) {
    echo "  - $event\n";
}
```

### 3. uspsVerifyAddress() - Verify Addresses

**Parameters Changed:**
- `-userid` → `-client_id`
- `-password` → `-client_secret`

**Backward Compatible:** Still accepts old field names (Address1, Address2, City, State, Zip5, Zip4)

**Example:**
```php
$verification = uspsVerifyAddress(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    'address' => array(
        0 => array(
            'Address1' => '',  // Secondary address (apt, suite, etc.)
            'Address2' => '6406 Ivy Lane',  // Primary street address
            'City' => 'Greenbelt',
            'State' => 'MD',
            'Zip5' => ''
        )
    )
));

if ($verification['attn'] == 0) {
    echo "Address verified:\n";
    print_r($verification['address'][0]['out']);
} else {
    echo "Address has corrections:\n";
    print_r($verification['address'][0]['diff']);
}
```

### 4. uspsZipCodeInfo() - ZIP Code Lookup

**Parameters Changed:**
- `userid` → `-client_id`
- `password` → `-client_secret`

**Example:**
```php
$zipInfo = uspsZipCodeInfo('90210', array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret'
));

echo "City: " . $zipInfo['city'] . "\n";
echo "State: " . $zipInfo['state'] . "\n";
```

### 5. uspsExpressMailLabel() - Create Shipping Labels

**Major Changes:**
- Now uses Domestic Labels v3 API
- Requires payment account number
- Returns base64-encoded label image

**Parameters Changed:**
- `-userid` → `-client_id`
- `-password` → `-client_secret`
- (Added) `-payment_account_number` (Required)
- (Added) `-mail_class` (Optional, default: PRIORITY_MAIL_EXPRESS)

**Example:**
```php
$label = uspsExpressMailLabel(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-payment_account_number' => 'your_account_number',
    '-mail_class' => 'PRIORITY_MAIL_EXPRESS',  // Optional

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
    'weight' => 16,  // ounces
    'length' => 10,  // inches
    'width' => 8,
    'height' => 6,

    // Optional
    'insured_amount' => 100,
    'label_type' => 'PDF'
));

if (isset($label['tracking_number'])) {
    echo "Tracking: " . $label['tracking_number'] . "\n";
    echo "Postage: $" . $label['postage'] . "\n";

    // Save label image
    $label_data = base64_decode($label['label_image']);
    file_put_contents('shipping_label.pdf', $label_data);
}
```

## Rate Limiting

**IMPORTANT:** The new USPS API v3 has rate limits:
- Default: 60 calls per hour per API
- This applies to each API category (Addresses, Prices, Tracking, etc.)

## Testing

Use the `-test` parameter to use the testing environment:

```php
$result = uspsServices(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-test' => true,  // Uses apis-tem.usps.com
    '-weight' => 16,
    '-zip_orig' => '90210',
    '-zip_dest' => '10001'
));
```

## Error Handling

All functions now return consistent error information:

```php
$result = uspsTrack(array(
    '-client_id' => 'your_consumer_key',
    '-client_secret' => 'your_consumer_secret',
    '-tn' => 'invalid_tracking'
));

if (isset($result['error'])) {
    echo "Error: " . $result['error'] . "\n";
    if (isset($result['http_code'])) {
        echo "HTTP Code: " . $result['http_code'] . "\n";
    }
}
```

## Common Errors

### 401 Unauthorized
- Check your Consumer Key and Consumer Secret
- Verify credentials are for the correct environment (test vs production)

### 403 Forbidden
- Check that your app has access to the specific API
- Verify payment account is valid (for label APIs)

### 429 Too Many Requests
- You've hit the rate limit (60 calls/hour)
- Implement caching or request throttling

## OAuth Token Caching

The new implementation automatically caches OAuth tokens to minimize API calls:
- Tokens are cached globally in `$USPS_ACCESS_TOKEN_CACHE`
- Tokens automatically refresh when expired
- 60-second buffer before expiration to prevent race conditions

## Additional Resources

- [USPS Developer Portal](https://developers.usps.com/)
- [API Documentation](https://developers.usps.com/apis)
- [Migration Guide (PDF)](https://www.usps.com/business/web-tools-apis/onboarding-guide.pdf)
- [GitHub Examples](https://github.com/USPS/api-examples)

## Support

For USPS API support:
- Developer Portal: https://developers.usps.com/
- Phone: 1-800-344-7779 (Internet Customer Care Center)

## Migration Checklist

- [ ] Register for USPS API v3 account
- [ ] Create app and obtain Consumer Key/Secret
- [ ] Update all function calls to use `-client_id` and `-client_secret`
- [ ] Remove `-userid` and `-password` parameters
- [ ] Add `-payment_account_number` for label creation
- [ ] Test in testing environment first (`-test` => true)
- [ ] Update error handling to check for new error format
- [ ] Implement rate limiting if making high-volume requests
- [ ] Update production environment
- [ ] Monitor for any API errors after migration

## Breaking Changes Summary

1. **Authentication:** Must use OAuth 2.0 (Consumer Key/Secret)
2. **Label Creation:** Requires payment account number
3. **Rate Limits:** 60 calls/hour per API category
4. **Response Format:** JSON instead of XML (handled internally)
5. **Field Names:** Some internal fields renamed but backward compatible

## Backward Compatibility

The updated functions maintain backward compatibility where possible:
- Function names unchanged
- Input parameter names mostly unchanged (except auth)
- Output format similar to original
- Old address field names still supported (Address1, Address2, Zip5, etc.)
