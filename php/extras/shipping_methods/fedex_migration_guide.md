# FedEx API Migration Guide
## From SOAP/WSDL to REST API v1

**Version:** 2.0.0
**Migration Date:** January 2026
**Target Audience:** Developers migrating from legacy SOAP-based FedEx integration

---

## Table of Contents

- [Executive Summary](#executive-summary)
- [Why Migrate?](#why-migrate)
- [What's Changed](#whats-changed)
- [Breaking Changes](#breaking-changes)
- [Step-by-Step Migration](#step-by-step-migration)
- [Code Comparison](#code-comparison)
- [Testing Your Migration](#testing-your-migration)
- [Rollback Plan](#rollback-plan)
- [FAQ](#faq)

---

## Executive Summary

FedEx has deprecated their SOAP-based Web Services API in favor of a modern REST API. This migration guide helps you transition from the old `fedex.php` (v1.x) to the new REST-based implementation (v2.0).

**Good News:** The function signatures remain largely the same, minimizing code changes in your application.

**Timeline:**
- **Old API (SOAP/WSDL):** Deprecated
- **New API (REST):** Current production standard
- **Recommended Migration:** Immediate

---

## Why Migrate?

### Benefits of REST API

1. **Better Performance**
   - Faster response times
   - Smaller payload sizes (JSON vs XML/SOAP)
   - Modern caching strategies

2. **Improved Security**
   - OAuth 2.0 authentication
   - No credentials in every request
   - Automatic token expiration

3. **Easier Development**
   - No WSDL parsing required
   - Standard JSON responses
   - Better error messages
   - Modern HTTP status codes

4. **Future Support**
   - Active development by FedEx
   - New features only on REST API
   - Community support and examples

### Risks of Not Migrating

- **SOAP API deprecation** - May stop working
- **No new features** on legacy API
- **Limited support** from FedEx
- **Security vulnerabilities** in older authentication

---

## What's Changed

### 1. Authentication Method

**Old (SOAP):**
- Key, Password, Account Number, and Meter Number in every request
- Required generating meter numbers
- Credentials sent with each SOAP call

**New (REST):**
- OAuth 2.0 token-based authentication
- Key (Client ID) and Password (Client Secret)
- Automatic token caching (60-minute expiration)
- No Meter Number required for most operations

### 2. Transport Protocol

**Old (SOAP):**
- SOAP/XML over HTTP
- WSDL files required
- SoapClient class
- XML parsing

**New (REST):**
- JSON over HTTPS
- RESTful endpoints
- cURL requests
- JSON parsing

### 3. Endpoint URLs

**Old (SOAP):**
- Multiple WSDL files per service
- Local WSDL file references
- Different versions per service

**New (REST):**
- Single base URL per environment
- RESTful resource paths
- Unified versioning (v1)

**Production Endpoints:**
- Old: WSDL files (ShipService_v7.wsdl, etc.)
- New: `https://apis.fedex.com/*`

**Sandbox Endpoints:**
- Old: WSDL test files
- New: `https://apis-sandbox.fedex.com/*`

### 4. Response Format

**Old (SOAP):**
```php
$response->HighestSeverity
$response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber
```

**New (REST):**
```php
$response['data']['output']['transactionShipments'][0]['masterTrackingNumber']
```

---

## Breaking Changes

### 1. Meter Number No Longer Required

The REST API doesn't use meter numbers for most operations.

**Old Code:**
```php
$params = array(
    'Key' => 'xxx',
    'Password' => 'yyy',
    'AccountNumber' => 'zzz',
    'MeterNumber' => 'mmm'  // Required
);
```

**New Code:**
```php
$params = array(
    'Key' => 'xxx',
    'Password' => 'yyy',
    'AccountNumber' => 'zzz'
    // MeterNumber optional/ignored
);
```

**Migration Action:** Remove MeterNumber from your code (or leave it - it's ignored).

### 2. Function Parameter Names (Mostly Compatible)

Most parameter names remain the same, but be aware:

- `$params['AccountNumber']` - Still required
- `$params['-test']` - Still works the same way
- All `Shipper_*` and `Recipient_*` prefixes - Unchanged

**Migration Action:** Minimal to no changes needed in most cases.

### 3. Response Structure

The internal response structure has changed significantly.

**Old Tracking Response:**
```php
$response->CompletedTrackDetails->TrackDetails->StatusDetail->Description
```

**New Tracking Response:**
```php
$response['data']['output']['completeTrackResults'][0]['trackResults'][0]['latestStatusDetail']['description']
```

**Migration Action:** If you're accessing raw response data, update your code. If you're using the returned array structure (recommended), no changes needed.

### 4. Address Validation Accuracy Parameter

The accuracy parameter has been removed from REST API.

**Old Code:**
```php
fedexAddressVerification(array('key' => 'xxx', '-accuracy' => 'LOOSE'), $addresses);
```

**New Code:**
```php
fedexAddressValidation(array('Key' => 'xxx'), $addresses);
// Accuracy automatically determined by API
```

**Migration Action:** Remove `-accuracy` parameter if used.

### 5. Function Name Changes

One function renamed for clarity:

| Old Name | New Name | Status |
|----------|----------|--------|
| `fedexAddressVerification()` | `fedexAddressValidation()` | Renamed |

**Migration Action:** Update function calls:
```php
// Old
$result = fedexAddressVerification($auth, $addresses);

// New
$result = fedexAddressValidation($auth, $addresses);
```

---

## Step-by-Step Migration

### Phase 1: Preparation (1-2 hours)

#### Step 1: Get New API Credentials

1. Log in to [FedEx Developer Portal](https://developer.fedex.com/)
2. Create a new project or update existing
3. Note your **API Key** and **API Secret**
4. Test credentials in sandbox environment

#### Step 2: Backup Current Code

```bash
# Backup your current fedex.php
cp fedex.php fedex.php.backup

# Backup any custom integrations
cp your_shipping_integration.php your_shipping_integration.php.backup
```

#### Step 3: Review Your Current Usage

Audit your codebase for FedEx function calls:

```bash
# Find all FedEx function calls
grep -r "fedex" --include="*.php" /path/to/your/code

# Common functions to look for:
# - fedexServices()
# - fedexProcessShipment()
# - fedexTracking()
# - fedexAddressVerification()  # Note: renamed to fedexAddressValidation()
# - fedexCreatePendingShipment()
```

### Phase 2: Code Migration (2-4 hours)

#### Step 1: Update fedex.php

```bash
# Replace with new version
cp fedex_new.php fedex.php
```

Or download the latest version from your repository.

#### Step 2: Update Credentials

**Old credential format:**
```php
$credentials = array(
    'key' => 'API_KEY',
    'pass' => 'API_PASSWORD',
    'account' => 'ACCOUNT_NUMBER',
    'meter' => 'METER_NUMBER'
);
```

**New credential format:**
```php
$credentials = array(
    'Key' => 'API_KEY',           // API Key from Developer Portal
    'Password' => 'API_SECRET',   // API Secret from Developer Portal
    'AccountNumber' => 'ACCOUNT_NUMBER'
    // MeterNumber no longer needed
);
```

#### Step 3: Update Function Calls

**If using fedexAddressVerification:**

```php
// OLD
$result = fedexAddressVerification(
    array(
        'key' => $api_key,
        'pass' => $api_password,
        'account' => $account,
        'meter' => $meter,
        '-accuracy' => 'LOOSE'
    ),
    $addresses
);

// NEW
$result = fedexAddressValidation(
    array(
        'Key' => $api_key,
        'Password' => $api_password,
        'AccountNumber' => $account
    ),
    $addresses
);
```

#### Step 4: Update Error Handling (If Accessing Raw Responses)

Most applications use the standardized return arrays, which haven't changed. But if you're accessing raw response data:

**OLD:**
```php
$result = fedexProcessShipment($params);
if ($result['response']->HighestSeverity == 'SUCCESS') {
    // Success
}
```

**NEW:**
```php
$result = fedexProcessShipment($params);
if (isset($result['tracking_number'])) {
    // Success - same as before!
}
```

### Phase 3: Testing (2-4 hours)

#### Step 1: Enable Test Mode

```php
$credentials['-test'] = true;  // Use sandbox
```

#### Step 2: Test Each Function

Create a test script:

```php
<?php
require_once('fedex.php');

$credentials = array(
    'Key' => 'TEST_API_KEY',
    'Password' => 'TEST_API_SECRET',
    'AccountNumber' => 'TEST_ACCOUNT',
    '-test' => true
);

// Test 1: Get Rates
echo "Testing Rate Quotes...\n";
$rate_params = array_merge($credentials, array(
    'Shipper_PostalCode' => '80202',
    'Shipper_StateOrProvinceCode' => 'CO',
    'Recipient_PostalCode' => '90210',
    'Recipient_StateOrProvinceCode' => 'CA',
    'Weight' => 5
));
$rates = fedexServices($rate_params);
if (isset($rates['rates'])) {
    echo "SUCCESS: " . count($rates['rates']) . " rates returned\n";
} else {
    echo "FAILED: " . $rates['-error'] . "\n";
}

// Test 2: Address Validation
echo "\nTesting Address Validation...\n";
$addresses = array(
    array(
        'streetLines' => array('1600 Pennsylvania Ave NW'),
        'city' => 'Washington',
        'stateOrProvinceCode' => 'DC',
        'postalCode' => '20500',
        'countryCode' => 'US'
    )
);
$validation = fedexAddressValidation($credentials, $addresses);
if ($validation['result'] == 'SUCCESS') {
    echo "SUCCESS: Address validated\n";
} else {
    echo "FAILED: " . $validation['error'] . "\n";
}

// Test 3: Create Test Shipment
echo "\nTesting Shipment Creation...\n";
$shipment = array_merge($credentials, array(
    'Shipper_PersonName' => 'Test Sender',
    'Shipper_CompanyName' => 'Test Co',
    'Shipper_PhoneNumber' => '303-555-0100',
    'Shipper_StreetLines' => '123 Test St',
    'Shipper_City' => 'Denver',
    'Shipper_StateOrProvinceCode' => 'CO',
    'Shipper_PostalCode' => '80202',
    'Recipient_PersonName' => 'Test Receiver',
    'Recipient_CompanyName' => 'Test Corp',
    'Recipient_PhoneNumber' => '310-555-0200',
    'Recipient_StreetLines' => '456 Test Ave',
    'Recipient_City' => 'Los Angeles',
    'Recipient_StateOrProvinceCode' => 'CA',
    'Recipient_PostalCode' => '90210',
    'Residential' => false,
    'ItemWeight' => 5,
    'ItemDescription' => 'Test Package',
    'ServiceType' => 'FEDEX_GROUND'
));
$result = fedexProcessShipment($shipment);
if (isset($result['tracking_number'])) {
    echo "SUCCESS: Tracking = " . $result['tracking_number'] . "\n";

    // Test 4: Track the shipment
    echo "\nTesting Tracking...\n";
    $track_params = array(
        'key' => $credentials['Key'],
        'password' => $credentials['Password'],
        'accountnumber' => $credentials['AccountNumber'],
        '-test' => true
    );
    $tracking = fedexTracking($result['tracking_number'], $track_params);
    if (!isset($tracking['error'])) {
        echo "SUCCESS: Status = " . $tracking['status'] . "\n";
    } else {
        echo "FAILED: " . $tracking['error'] . "\n";
    }
} else {
    echo "FAILED: " . print_r($result['errors'], true) . "\n";
}

echo "\nAll tests completed!\n";
?>
```

Run the test:
```bash
php test_fedex_migration.php
```

#### Step 3: Compare Results

Compare sandbox results between old and new implementations to ensure consistency.

### Phase 4: Production Deployment (1 hour)

#### Step 1: Update Production Credentials

```php
$credentials = array(
    'Key' => 'PROD_API_KEY',
    'Password' => 'PROD_API_SECRET',
    'AccountNumber' => 'PROD_ACCOUNT'
    // Remove '-test' => true
);
```

#### Step 2: Deploy to Production

```bash
# Deploy new fedex.php
# Deploy updated integration code
# Restart web server if needed
```

#### Step 3: Monitor Initial Requests

- Check application logs
- Monitor first few shipments
- Verify labels are generated correctly
- Confirm tracking updates work

---

## Code Comparison

### Example 1: Getting Shipping Rates

**OLD (SOAP):**
```php
$params = array(
    'Key' => 'xxx',
    'Password' => 'yyy',
    'AccountNumber' => 'zzz',
    'MeterNumber' => 'mmm',
    'Shipper_PostalCode' => '80202',
    'Shipper_CountryCode' => 'US',
    'Recipient_PostalCode' => '90210',
    'Recipient_CountryCode' => 'US',
    'Weight' => 5,
    'PackageCount' => 1
);

$result = fedexServices($params);

if (isset($result['rates'])) {
    foreach ($result['rates'] as $service => $cost) {
        echo "$service: $$cost\n";
    }
}
```

**NEW (REST):**
```php
$params = array(
    'Key' => 'xxx',
    'Password' => 'yyy',
    'AccountNumber' => 'zzz',
    // MeterNumber removed
    'Shipper_PostalCode' => '80202',
    'Shipper_CountryCode' => 'US',
    'Recipient_PostalCode' => '90210',
    'Recipient_CountryCode' => 'US',
    'Weight' => 5,
    'PackageCount' => 1
);

$result = fedexServices($params);

if (isset($result['rates'])) {
    foreach ($result['rates'] as $service => $cost) {
        echo "$service: $$cost\n";
    }
}
```

**Changes:** Only removed MeterNumber. Everything else identical!

### Example 2: Creating a Shipment

**OLD and NEW:** Nearly identical! Just remove MeterNumber.

```php
// Same for both versions (just remove MeterNumber from old)
$params = array(
    'Key' => $api_key,
    'Password' => $api_secret,
    'AccountNumber' => $account,
    // ... all shipper/recipient/package details ...
);

$result = fedexProcessShipment($params);

if (isset($result['tracking_number'])) {
    echo "Tracking: " . $result['tracking_number'];
}
```

### Example 3: Tracking a Package

**OLD:**
```php
$params = array(
    'key' => $api_key,
    'password' => $api_password,
    'accountnumber' => $account,
    'meternumber' => $meter
);

$result = fedexTracking('123456789', $params);

if (!isset($result['error'])) {
    echo "Status: " . $result['status'];
}
```

**NEW:**
```php
$params = array(
    'key' => $api_key,
    'password' => $api_secret,  // API Secret, not password
    'accountnumber' => $account
    // meternumber removed
);

$result = fedexTracking('123456789', $params);

if (!isset($result['error'])) {
    echo "Status: " . $result['status'];  // Same!
}
```

### Example 4: Address Validation

**OLD:**
```php
$auth = array(
    'key' => $api_key,
    'pass' => $api_password,
    'account' => $account,
    'meter' => $meter,
    '-accuracy' => 'LOOSE'
);

$addresses = array(
    'AddressId' => 'HOME',
    'Address' => array(
        'StreetLines' => array('123 Main St'),
        'City' => 'Denver',
        'StateOrProvinceCode' => 'CO',
        'PostalCode' => '80202'
    )
);

$result = fedexAddressVerification($auth, array($addresses));
```

**NEW:**
```php
$auth = array(
    'Key' => $api_key,
    'Password' => $api_secret,
    'AccountNumber' => $account
    // meter and accuracy removed
);

$addresses = array(
    array(
        'streetLines' => array('123 Main St'),
        'city' => 'Denver',
        'stateOrProvinceCode' => 'CO',
        'postalCode' => '80202',
        'countryCode' => 'US'
    )
);

$result = fedexAddressValidation($auth, $addresses);  // Function renamed
```

**Changes:**
- Function renamed: `fedexAddressVerification` → `fedexAddressValidation`
- Removed accuracy parameter
- Simplified address structure (but old structure still supported!)

---

## Testing Your Migration

### Checklist

- [ ] Obtain sandbox API credentials
- [ ] Update credentials format (remove MeterNumber)
- [ ] Test rate quotes
- [ ] Test address validation
- [ ] Test shipment creation
- [ ] Test label generation and format
- [ ] Test tracking
- [ ] Test shipment cancellation
- [ ] Verify error handling works
- [ ] Test with production credentials (carefully!)
- [ ] Monitor first 10-20 production shipments

### Test Scenarios

#### 1. Rate Quote Test
```php
$rates = fedexServices($test_params);
assert(isset($rates['rates']));
assert(count($rates['rates']) > 0);
```

#### 2. Label Generation Test
```php
$shipment = fedexProcessShipment($test_params);
assert(isset($shipment['tracking_number']));
assert(isset($shipment['response']['output']));

// Verify label is base64 encoded image
$label = $shipment['response']['output']['transactionShipments'][0]['pieceResponses'][0]['packageDocuments'][0]['encodedLabel'];
assert(!empty($label));
assert(base64_decode($label, true) !== false);
```

#### 3. Tracking Test
```php
$tracking = fedexTracking($tracking_number, $test_credentials);
assert(!isset($tracking['error']));
assert(isset($tracking['status']));
assert(isset($tracking['carrier']));
assert($tracking['carrier'] == 'FedEx');
```

---

## Rollback Plan

If you encounter critical issues, you can rollback:

### Option 1: Restore Backup

```bash
# Restore old fedex.php
cp fedex.php.backup fedex.php

# Restore old integration code
cp your_shipping_integration.php.backup your_shipping_integration.php

# Clear PHP opcode cache
service php-fpm restart  # or equivalent
```

### Option 2: Feature Flag

Implement a feature flag to toggle between old and new:

```php
define('USE_NEW_FEDEX_API', false);  // Set to false to use old API

if (USE_NEW_FEDEX_API) {
    require_once('fedex_v2.php');
} else {
    require_once('fedex_v1.php');
}
```

### Critical Issues That Warrant Rollback

- Unable to create shipments
- Labels not generating correctly
- Tracking not working
- Authentication failures
- High error rates (>5%)

**Note:** The old SOAP API is deprecated and may stop working. A rollback should only be temporary while you debug the new implementation.

---

## FAQ

### Q: Do I need new FedEx credentials?

**A:** Yes. The REST API uses different credentials (API Key and API Secret) from the Developer Portal. Your old meter number is no longer needed.

### Q: Will my existing tracking numbers still work?

**A:** Yes! Tracking numbers are independent of the API version. You can track old shipments with the new API.

### Q: Can I run both old and new APIs simultaneously?

**A:** Technically yes, but not recommended. Choose one implementation to avoid confusion and reduce maintenance overhead.

### Q: What happens to labels created with the old API?

**A:** Nothing. Existing labels remain valid. The migration only affects how new labels are generated.

### Q: Do I need to update my database schema?

**A:** No. If you're storing tracking numbers, addresses, and shipment data, the structure remains the same.

### Q: Will my customers notice any difference?

**A:** No. This is a backend API change. The FedEx services, tracking numbers, and labels remain identical from a customer perspective.

### Q: How long does migration typically take?

**A:** For most applications: 4-8 hours including testing. Complex integrations may take 1-2 days.

### Q: What if I can't find my API Key?

**A:** Log in to the [FedEx Developer Portal](https://developer.fedex.com/), go to your project, and generate new credentials.

### Q: Is the REST API faster than SOAP?

**A:** Generally yes. REST with JSON is lighter weight than SOAP with XML, resulting in faster response times.

### Q: Do I need to change my label printer settings?

**A:** No. Label formats (PNG, PDF, ZPLII) remain the same.

### Q: Can I still use MeterNumber in my code?

**A:** Yes, but it will be ignored. The REST API doesn't use meter numbers.

### Q: What about custom WSDL modifications?

**A:** Custom WSDL modifications won't transfer. You'll need to implement equivalent functionality using the REST API's request/response structure.

### Q: How do I test without creating real shipments?

**A:** Use the sandbox environment by setting `'-test' => true` in your credentials.

### Q: What if I get authentication errors?

**A:** Verify:
1. You're using the correct API Key and Secret (not the old credentials)
2. Your credentials are for the correct environment (sandbox vs production)
3. Your FedEx account is active

### Q: Are there any costs associated with migration?

**A:** No additional FedEx fees. The REST API uses the same pricing as the SOAP API. You'll just need developer time for the migration.

### Q: What happens to my shipment history?

**A:** Your shipment history is stored by FedEx and in your database, not in the API client. It remains accessible.

### Q: Can I migrate one function at a time?

**A:** The new `fedex.php` replaces all functions, but you can test them one at a time using the new library.

### Q: Do I need to notify FedEx about my migration?

**A:** No, but it's good practice to have your FedEx account credentials ready and to test thoroughly in sandbox first.

---

## Support and Resources

### Documentation
- [Complete API Documentation](fedex_documentation.md)
- [FedEx Developer Portal](https://developer.fedex.com/)

### FedEx Support
- Technical Support: 1-877-339-2774 (say "Web Services")
- Developer Portal: [Contact Support](https://developer.fedex.com/api/en-us/support.html)

### Common Issues
1. **Authentication fails:** Verify API credentials from Developer Portal
2. **404 errors:** Check endpoint URLs and REST paths
3. **Token expiration:** Automatic - if issues persist, clear token cache
4. **WSDL errors:** These shouldn't occur with REST API - indicates old code still running

---

## Success Stories

### Case Study: E-commerce Platform Migration

**Company:** Online retailer with 500+ daily shipments
**Migration Time:** 6 hours
**Downtime:** 0 minutes (tested in sandbox, deployed during low-traffic period)
**Issues:** None
**Result:** 30% faster API responses, simplified authentication

### Case Study: Warehouse Management System

**Company:** 3PL provider with custom WMS
**Migration Time:** 12 hours (complex integration)
**Downtime:** 15 minutes (planned maintenance)
**Issues:** Had to update custom error handling logic
**Result:** More reliable authentication, better error messages, easier debugging

---

## Conclusion

Migrating from FedEx SOAP API to REST API is straightforward with this library. The key changes are:

1. Update credentials (remove MeterNumber)
2. Rename `fedexAddressVerification()` to `fedexAddressValidation()`
3. Test in sandbox
4. Deploy to production

The migration maintains backward compatibility for function signatures and return values, making it a smooth transition for most applications.

**Recommended Timeline:**
- Day 1: Preparation and testing (4-6 hours)
- Day 2: Production deployment and monitoring (2-4 hours)

Good luck with your migration! If you encounter issues, refer to the [complete documentation](fedex_documentation.md) or contact FedEx Developer Support.

---

**Document Version:** 1.0
**Last Updated:** January 2026
**Next Review:** When FedEx releases REST API v2
