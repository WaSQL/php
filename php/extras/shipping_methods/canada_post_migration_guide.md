# Canada Post API Migration Guide

## Overview

This guide helps you migrate from the old Canada Post API implementation (v1.x) to the updated version (v2.0.0) which uses the latest production APIs.

## What Changed

### API Version Updates

| Function | Old Version | New Version | Status |
|----------|------------|-------------|--------|
| cpGetRates() | v2 | v4 | ✅ Updated |
| cpCreateShipment() | v5 | v8 | ✅ Updated |
| cpTransmitShipments() | v5 | v8 | ✅ Updated |
| cpGetManifest() | v5 | v8 | ✅ Updated |
| cpGetManifestArtifact() | v5 | v8 | ✅ Updated |
| cpGetShipmentLabel() | v5 | v8 | ✅ Updated |

### Key Changes

1. **Updated XML Namespaces**
   - Rating: `rate-v2` → `rate-v4`
   - Shipping: `shipment-v5` → `shipment-v8`
   - Manifest: `manifest-v5` → `manifest-v8`

2. **Updated Media Types**
   - Rating: `application/vnd.cpc.ship.rate-v2+xml` → `application/vnd.cpc.ship.rate-v4+xml`
   - Shipping: `application/vnd.cpc.shipment-v5+xml` → `application/vnd.cpc.shipment-v8+xml`
   - Manifest: `application/vnd.cpc.manifest-v5+xml` → `application/vnd.cpc.manifest-v8+xml`

3. **Improved Error Handling**
   - All functions now return consistent error arrays
   - Better HTTP status code checking
   - Improved cURL error handling
   - XML escaping for special characters

4. **Enhanced PHPDocs**
   - Complete parameter documentation
   - Return value documentation
   - Usage examples
   - Links to official API documentation

5. **Production Readiness**
   - Removed debug code
   - Added timeouts to all cURL requests
   - Better handling of edge cases
   - Consistent parameter validation

## Migration Steps

### Step 1: Backup Your Code

Before making any changes, backup your existing implementation:

```bash
cp canada_post.php canada_post.php.backup
```

### Step 2: Update Function Calls

The function signatures remain the same, so most of your code will work without changes. However, you should review error handling.

#### Old Error Handling (v1.x)

```php
$rates = cpGetRates($params);
if(!isset($rates['rates'])){
    echo "Error getting rates";
    exit;
}
```

#### New Error Handling (v2.0.0)

```php
$rates = cpGetRates($params);
if(isset($rates['-error'])){
    echo "Error: " . $rates['-error'];
    if(isset($rates['-missing'])){
        echo "\nMissing params: " . implode(', ', $rates['-missing']);
    }
    exit;
}
```

### Step 3: Update cpGetRates() Calls

The function works the same, but now supports international destinations.

#### Old Code (v1.x)

```php
$rates = cpGetRates([
    '-username' => $username,
    '-password' => $password,
    '-account_number' => $account,
    'sender_zipcode' => 'K1A0B1',
    'recipient_zipcode' => 'M5W1E6',
    'parcel_weight' => 2.5
]);
```

#### New Code (v2.0.0) - Same, but with optional enhancements

```php
$rates = cpGetRates([
    '-username' => $username,
    '-password' => $password,
    '-account_number' => $account,
    'sender_zipcode' => 'K1A0B1',
    'recipient_zipcode' => 'M5W1E6',
    'parcel_weight' => 2.5,
    // New optional parameters:
    'parcel_length' => 30,           // Now properly included in request
    'parcel_width' => 20,
    'parcel_height' => 10,
    'destination_country' => 'CA'    // Explicit country code
]);
```

### Step 4: Update cpCreateShipment() Calls

The function now includes better defaults and XML escaping.

#### Old Code (v1.x)

```php
$shipment = cpCreateShipment([
    '-username' => $username,
    '-password' => $password,
    '-account_number' => $account,
    'sender_company' => 'My Company',
    // ... other params ...
    'ordernumber' => 'ORD-123'
]);

// Old way - had to check specific field
if(strlen($shipment['tracking-pin'])){
    echo "Success: " . $shipment['tracking-pin'];
}
```

#### New Code (v2.0.0)

```php
$shipment = cpCreateShipment([
    '-username' => $username,
    '-password' => $password,
    '-account_number' => $account,
    'sender_company' => 'My Company & Sons',  // Now properly escaped
    // ... other params ...
    'ordernumber' => 'ORD-123',
    // New optional parameters:
    '-transmit_shipment' => true,    // Skip manifest, transmit immediately
    '-provide_pricing' => true,      // Include pricing in response
    '-provide_receipt' => true       // Include receipt in response
]);

// New way - consistent error checking
if(isset($shipment['-error'])){
    echo "Error: " . $shipment['-error'];
} else {
    echo "Success: " . $shipment['tracking-pin'];
}
```

### Step 5: Update cpTransmitShipments() Calls

The function now has better error handling and supports MOBO customers.

#### Old Code (v1.x)

```php
$transmit = cpTransmitShipments([
    '-username' => $username,
    '-password' => $password,
    '-account_number' => $account,
    '-group_id' => date('Ymd'),
    'sender_company' => 'My Company',
    'sender_address' => '123 Main St',
    // ... other params ...
]);

// Had to check for manifest_url existence
if(isset($transmit['manifest_url'])){
    $manifest = cpGetManifest([
        '-username' => $username,
        '-password' => $password,
        'manifest_url' => $transmit['manifest_url']
    ]);
}
```

#### New Code (v2.0.0)

```php
$transmit = cpTransmitShipments([
    '-username' => $username,
    '-password' => $password,
    '-account_number' => $account,
    '-group_id' => date('Ymd'),
    'sender_company' => 'My Company & Sons',  // Now properly escaped
    'sender_address' => '123 Main St',
    // ... other params ...
    '-mobo' => $mobo_account  // Optional: mail-on-behalf-of
]);

// Better error checking
if(isset($transmit['-error'])){
    echo "Error: " . $transmit['-error'];
    if(isset($transmit['-messages'])){
        print_r($transmit['-messages']);
    }
} elseif(isset($transmit['manifest_url'])){
    $manifest = cpGetManifest([
        '-username' => $username,
        '-password' => $password,
        'manifest_url' => $transmit['manifest_url']
    ]);
}
```

## Breaking Changes

### 1. Error Response Format

**Old behavior:** Functions returned `null` or incomplete arrays on error

**New behavior:** Functions return arrays with `-error` key and detailed error information

**Migration:** Update all error checking to look for `$result['-error']` instead of checking for null or specific fields.

### 2. Missing Parameter Handling

**Old behavior:** Sometimes used empty strings for missing params, could result in unclear errors

**New behavior:** Always validates required parameters and returns detailed `-missing` array

**Migration:** Ensure all required parameters are provided. Check for `-missing` key in error responses.

### 3. XML Special Characters

**Old behavior:** Did not escape XML special characters, could cause API errors

**New behavior:** All text fields are properly escaped using `htmlspecialchars()`

**Migration:** No changes needed. Your data is now safer and less likely to cause errors.

### 4. cURL Timeout

**Old behavior:** No timeout specified, could hang indefinitely

**New behavior:** 30-second timeout on all requests

**Migration:** If you have slow connections, you may need to increase timeout by modifying the source code.

### 5. Label File Handling

**Old behavior:** Used `buildDir()` function which might not exist

**New behavior:** Falls back to `mkdir()` if `buildDir()` is not available

**Migration:** No changes needed, but you can now use the library without WaSQL framework.

## Compatibility

### Backward Compatibility

The v2.0.0 update maintains backward compatibility for:

- ✅ Function names (no changes)
- ✅ Required parameters (no changes)
- ✅ Basic return structure (fields added, none removed)
- ✅ File paths and locations

### Non-Compatible Changes

- ❌ Error handling pattern (must update error checks)
- ❌ XML namespace URLs (automatically handled)
- ❌ Media type headers (automatically handled)

## Testing Your Migration

### 1. Test with Development Credentials

Always test with `-test => true` first:

```php
$params['-test'] = true;  // Use test environment
$result = cpGetRates($params);
```

### 2. Verify Error Handling

Test error conditions:

```php
// Test with missing parameters
$result = cpGetRates([
    '-username' => $username,
    '-password' => $password
    // Missing required params
]);

// Should return:
// [
//   '-error' => 'Missing required parameters',
//   '-missing' => ['-account_number', 'sender_zipcode', ...]
// ]
```

### 3. Test Complete Workflow

Test a complete shipment workflow:

```php
// 1. Get rates
$rates = cpGetRates($rate_params);
assert(!isset($rates['-error']), 'Rates failed');

// 2. Create shipment
$shipment = cpCreateShipment($shipment_params);
assert(!isset($shipment['-error']), 'Shipment creation failed');
assert(isset($shipment['tracking-pin']), 'No tracking PIN');

// 3. Get label
$label = cpGetShipmentLabel([
    '-username' => $username,
    '-password' => $password,
    'label_url' => $shipment['artifact_url']
]);
assert(is_string($label), 'Label download failed');
assert(file_exists($label), 'Label file not found');

// 4. Transmit
$transmit = cpTransmitShipments($transmit_params);
assert(!isset($transmit['-error']), 'Transmit failed');

// 5. Get manifest
$manifest = cpGetManifest([
    '-username' => $username,
    '-password' => $password,
    'manifest_url' => $transmit['manifest_url']
]);
assert(!isset($manifest['-error']), 'Manifest failed');
assert(isset($manifest['po-number']), 'No PO number');

echo "All tests passed!\n";
```

## Common Issues and Solutions

### Issue 1: "Missing required parameters" Error

**Problem:** Getting `-missing` array in response

**Solution:** Ensure all required parameters are provided. Check the documentation for each function.

```php
// Check what's missing
if(isset($result['-missing'])){
    echo "Missing parameters: " . implode(', ', $result['-missing']);
}
```

### Issue 2: HTTP 401 Unauthorized

**Problem:** Authentication failure

**Solution:**
- Verify your credentials are correct
- Ensure you're using development credentials with `-test => true`
- Ensure you're using production credentials without `-test` flag
- Check that your API account is active

### Issue 3: HTTP 400 Bad Request

**Problem:** API rejected your request

**Solution:**
- Check the `-messages` array in the response for details
- Verify postal codes are valid format (no spaces in Canadian postal codes)
- Ensure weight is in kg, dimensions are in cm
- Check that service code is valid for destination

```php
if(isset($result['-messages'])){
    print_r($result['-messages']);
}
```

### Issue 4: "xml2Array function not available"

**Problem:** Missing xml2Array() dependency

**Solution:**
- Include the WaSQL framework
- Or implement your own XML parser
- The function expects SimpleXML-style arrays

### Issue 5: SSL Certificate Verification Failed

**Problem:** cURL cannot verify Canada Post SSL certificate

**Solution:**
- Update your cacert.pem file in the `canada_post/` directory
- Download latest from: https://curl.se/docs/caextract.html
- Ensure the path in code is correct: `{$progpath}/canada_post/cacert.pem`

## Performance Considerations

### 1. Rate Limiting

Canada Post may rate-limit API requests. Implement caching for rate quotes:

```php
// Cache rates for 1 hour
$cache_key = "cp_rates_" . md5(serialize($params));
$rates = getCache($cache_key);

if($rates === null){
    $rates = cpGetRates($params);
    if(!isset($rates['-error'])){
        setCache($cache_key, $rates, 3600);
    }
}
```

### 2. Batch Operations

Group shipments by day for efficient manifest creation:

```php
$group_id = date('Ymd');  // Use same group_id for all shipments today
```

### 3. Error Logging

Log API errors for troubleshooting:

```php
if(isset($result['-error'])){
    error_log("Canada Post API Error: " . print_r($result, true));
}
```

## Rollback Plan

If you encounter issues after migration:

### 1. Quick Rollback

```bash
# Restore backup
cp canada_post.php.backup canada_post.php
```

### 2. Identify Issues

- Check error logs
- Review API responses
- Test with development credentials

### 3. Gradual Migration

You can run both versions side-by-side:

```php
// Load old version
require_once 'canada_post.php.backup';

// Rename old functions
function cpGetRates_v1($params){ /* old code */ }

// Load new version
require_once 'canada_post.php';

// Test both
$rates_old = cpGetRates_v1($params);
$rates_new = cpGetRates($params);

// Compare results
if($rates_old != $rates_new){
    error_log("Difference detected: " . print_r([
        'old' => $rates_old,
        'new' => $rates_new
    ], true));
}
```

## Getting Help

If you need assistance:

1. **Check the Documentation**
   - Read `canada_post_documentation.md`
   - Review function PHPDocs in source code
   - Check official Canada Post API docs

2. **Review Error Messages**
   - Always check the `-error` and `-messages` keys
   - Look for HTTP status codes in `-http_code`
   - Enable verbose cURL output for debugging

3. **Test Environment**
   - Use `-test => true` to test safely
   - Verify credentials are correct
   - Check that test data is valid

4. **Canada Post Support**
   - Developer Program: https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/
   - Technical Support: Available through Developer Program portal

## Checklist

Use this checklist to ensure a smooth migration:

- [ ] Backed up existing `canada_post.php`
- [ ] Updated error handling to check for `-error` key
- [ ] Tested with development credentials (`-test => true`)
- [ ] Verified all required parameters are provided
- [ ] Updated any custom error handling code
- [ ] Tested complete workflow (rates → shipment → label → manifest)
- [ ] Checked label and manifest PDFs are generated correctly
- [ ] Verified tracking PINs are received
- [ ] Tested error scenarios (missing params, invalid data)
- [ ] Reviewed and updated any cached rate data
- [ ] Updated documentation for your team
- [ ] Tested in staging environment
- [ ] Gradually rolled out to production
- [ ] Monitoring logs for errors

## Summary

The migration to v2.0.0 brings your Canada Post integration up to date with the latest production APIs, improves reliability, and provides better error handling. While the changes are mostly internal, you should update your error handling code to take advantage of the improved error reporting.

**Key takeaways:**
- Function signatures unchanged - easy migration
- Better error handling - update your error checks
- Production-ready - improved reliability
- Latest APIs - future-proofed integration
- Comprehensive docs - easier to maintain

For most users, the migration should take less than an hour. The improved error handling and production readiness make it well worth the effort.

## Version History

- **v2.0.0** (January 2026) - Updated to latest Canada Post production APIs
- **v1.0.0** - Initial release

---

**Need help?** Review the documentation or contact the WaSQL team.
