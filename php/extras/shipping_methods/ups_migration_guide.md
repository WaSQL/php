# UPS API Migration Guide

## Migrating from XML API (v1.x) to OAuth 2.0 REST API (v2.0)

This guide helps you migrate from the deprecated UPS XML API to the new OAuth 2.0 REST/JSON API.

---

## Table of Contents

- [Why Migrate?](#why-migrate)
- [What Changed](#what-changed)
- [Migration Checklist](#migration-checklist)
- [Credential Migration](#credential-migration)
- [Code Migration](#code-migration)
  - [Authentication](#authentication)
  - [upsServices()](#upsservices)
  - [upsTrack()](#upstrack)
  - [upsAddressValidate()](#upsaddressvalidate)
- [Testing Your Migration](#testing-your-migration)
- [Common Migration Issues](#common-migration-issues)
- [Rollback Plan](#rollback-plan)

---

## Why Migrate?

**CRITICAL:** The old XML-based UPS API was officially deprecated on **January 1, 2020**, and the Access Key authentication method was fully retired on **June 3, 2024**. All UPS API requests now require OAuth 2.0 authentication.

**Benefits of migrating:**
- ✅ Continued API access (old API no longer works)
- ✅ More secure OAuth 2.0 authentication
- ✅ Faster JSON responses vs XML
- ✅ Better error messages and debugging
- ✅ Modern REST architecture
- ✅ Support for new UPS features

---

## What Changed

### High-Level Changes

| Aspect | Old (v1.x) | New (v2.0) |
|--------|------------|------------|
| **Authentication** | Access Key + User ID + Password | OAuth 2.0 (Client ID + Client Secret) |
| **Protocol** | XML over HTTP | JSON over HTTPS (REST) |
| **Endpoints** | `/ups.app/xml/*` | `/api/*/v1/*` |
| **Token Management** | Static credentials | Bearer tokens (4-hour expiry) |
| **Response Format** | XML | JSON |

### Function Changes

| Function | Status | Changes Required |
|----------|--------|------------------|
| `upsServices()` | ✅ Updated | Change credentials only |
| `upsTrack()` | ✅ Updated | Change credentials only |
| `upsAddressValidate()` | ✅ Updated | Change credentials only |
| `upsTrack_OLD()` | ❌ Deprecated | Returns error message |

**Good News:** Function names and core parameters remain the same! Only authentication credentials need to change.

---

## Migration Checklist

Use this checklist to track your migration progress:

- [ ] **Step 1:** Backup your current code
- [ ] **Step 2:** Get OAuth 2.0 credentials from UPS Developer Portal
- [ ] **Step 3:** Update `ups.php` to version 2.0
- [ ] **Step 4:** Update credential parameters in your code
- [ ] **Step 5:** Test in sandbox environment
- [ ] **Step 6:** Update to production credentials
- [ ] **Step 7:** Deploy to production
- [ ] **Step 8:** Monitor for errors
- [ ] **Step 9:** Remove old credential variables
- [ ] **Step 10:** Update documentation

---

## Credential Migration

### Old Credentials (XML API)

You previously needed these credentials from UPS:

```php
$old_credentials = [
    '-userid' => 'your_username',
    '-accesskey' => 'ABC123DEF456GHI789JKL012',
    '-password' => 'your_password',
    '-account' => 'your_ups_account_number'
];
```

### New Credentials (OAuth 2.0)

You now need these credentials from the UPS Developer Portal:

```php
$new_credentials = [
    '-client_id' => 'your_client_id_from_developer_portal',
    '-client_secret' => 'your_client_secret_from_developer_portal',
    '-account' => 'your_ups_account_number'  // Same as before
];
```

### How to Get New Credentials

1. **Visit UPS Developer Portal**
   - Go to https://developer.ups.com
   - Sign in or create an account

2. **Create Application**
   - Click "Apps" or "My Apps"
   - Click "Add Apps" or "Create App"
   - Fill in application details:
     - App Name: "My Shipping Application"
     - Description: Your app description

3. **Add Products/APIs**
   - Select the APIs you need:
     - ✅ Rating API (for `upsServices`)
     - ✅ Tracking API (for `upsTrack`)
     - ✅ Address Validation API (for `upsAddressValidate`)

4. **Get Credentials**
   - After creating the app, you'll see:
     - **Client ID**: Copy this
     - **Client Secret**: Copy this (store securely!)

5. **Request Production Access**
   - By default, you get sandbox access
   - Request production access through the portal
   - Approval typically takes 1-2 business days

### Credential Storage Best Practices

**Don't do this:**
```php
// ❌ Hardcoded credentials in code
$credentials = [
    '-client_id' => 'abc123xyz',
    '-client_secret' => 'secret123'
];
```

**Do this instead:**
```php
// ✅ Environment variables
$credentials = [
    '-client_id' => getenv('UPS_CLIENT_ID'),
    '-client_secret' => getenv('UPS_CLIENT_SECRET')
];

// ✅ Config file (outside web root)
$config = include('/path/outside/webroot/config.php');
$credentials = [
    '-client_id' => $config['ups_client_id'],
    '-client_secret' => $config['ups_client_secret']
];

// ✅ Database (encrypted)
$credentials = getEncryptedConfig('ups_credentials');
```

---

## Code Migration

### Authentication

#### Before (XML API)
```php
// Authentication was implicit - passed with every request
$result = upsServices([
    '-userid' => 'myuser',
    '-accesskey' => 'ABC123',
    '-password' => 'mypass',
    '-account' => '123456',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5
]);
```

#### After (OAuth 2.0)
```php
// Option 1: Automatic authentication (simplest)
$result = upsServices([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret',
    '-account' => '123456',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5
]);

// Option 2: Manual token management (better performance)
$token = upsGetOAuthToken([
    '-client_id' => 'your_client_id',
    '-client_secret' => 'your_client_secret'
]);

$result = upsServices([
    '-access_token' => $token['access_token'],
    '-account' => '123456',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5
]);
```

**Migration Steps:**
1. Replace `-userid`, `-accesskey`, `-password` with `-client_id`, `-client_secret`
2. Keep `-account` parameter (unchanged)
3. Optionally implement token caching for better performance

---

### upsServices()

#### Before (XML API)
```php
$rates = upsServices([
    '-userid' => 'myuser',
    '-accesskey' => 'ABC123',
    '-password' => 'mypass',
    '-account' => '123456',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5,
    'shipfrom_country' => 'US',
    'shipto_country' => 'US',
    'pickup_type' => '01',
    'package_type' => '02',
    'service_code' => '03',
    'request_option' => 'Shop'
]);

if(isset($rates['rates'])){
    foreach($rates['rates'] as $service => $cost){
        echo "$service: $$cost\n";
    }
}
```

#### After (OAuth 2.0)
```php
$rates = upsServices([
    '-client_id' => 'your_client_id',          // Changed
    '-client_secret' => 'your_client_secret',  // Changed
    '-account' => '123456',                     // Same
    '-shipfrom_zip' => '90210',                 // Same
    '-shipto_zip' => '10001',                   // Same
    '-weight' => 5.5,                           // Same
    'shipfrom_country' => 'US',                 // Same
    'shipto_country' => 'US',                   // Same
    'pickup_type' => '01',                      // Same
    'package_type' => '02',                     // Same
    'service_code' => '03',                     // Same
    'request_option' => 'Shop'                  // Same
]);

// Response format is identical
if(isset($rates['rates'])){
    foreach($rates['rates'] as $service => $cost){
        echo "$service: $$cost\n";
    }
}
```

**Migration Steps:**
1. Replace `-userid` → `-client_id`
2. Replace `-accesskey` → Remove (not needed)
3. Replace `-password` → `-client_secret`
4. All other parameters remain the same
5. Response format is identical - no code changes needed!

---

### upsTrack()

#### Before (XML API)
```php
$tracking = upsTrack([
    '-userid' => 'myuser',
    '-accesskey' => 'ABC123',
    '-password' => 'mypass',
    '-tn' => '1Z12345E0205271688'
]);

if(isset($tracking['status']) && !isset($tracking['error'])){
    echo "Status: {$tracking['status']}\n";
    if(isset($tracking['delivery_date'])){
        echo "Delivered: {$tracking['delivery_date']}\n";
    }
}
```

#### After (OAuth 2.0)
```php
$tracking = upsTrack([
    '-client_id' => 'your_client_id',          // Changed
    '-client_secret' => 'your_client_secret',  // Changed
    '-tn' => '1Z12345E0205271688'              // Same
]);

// Response format is identical
if(isset($tracking['status']) && !isset($tracking['error'])){
    echo "Status: {$tracking['status']}\n";
    if(isset($tracking['delivery_date'])){
        echo "Delivered: {$tracking['delivery_date']}\n";
    }
}
```

**Migration Steps:**
1. Replace `-userid` → `-client_id`
2. Replace `-accesskey` → Remove (not needed)
3. Replace `-password` → `-client_secret`
4. Keep `-tn` parameter (unchanged)
5. Response format is identical - no code changes needed!

---

### upsAddressValidate()

#### Before (XML API)
```php
$validation = upsAddressValidate([
    '-userid' => 'myuser',
    '-accesskey' => 'ABC123',
    '-password' => 'mypass',
    'address' => '26601 W Agoura Rd',
    'city' => 'Calabasas',
    'state' => 'CA',
    'zip' => '91302'
]);

if($validation['valid']){
    echo "Address is valid!\n";
}
```

#### After (OAuth 2.0)
```php
$validation = upsAddressValidate([
    '-client_id' => 'your_client_id',          // Changed
    '-client_secret' => 'your_client_secret',  // Changed
    'address' => '26601 W Agoura Rd',          // Same
    'city' => 'Calabasas',                     // Same
    'state' => 'CA',                           // Same
    'zip' => '91302'                           // Same
]);

// Response format is identical
if($validation['valid']){
    echo "Address is valid!\n";
}
```

**Migration Steps:**
1. Replace `-userid` → `-client_id`
2. Replace `-accesskey` → Remove (not needed)
3. Replace `-password` → `-client_secret`
4. All other parameters remain the same
5. Response format is identical - no code changes needed!

---

## Complete Migration Example

Here's a complete before/after example of a typical integration:

### Before (XML API)

```php
<?php
require_once('ups_old.php');

// Old credentials
$ups_config = [
    '-userid' => 'myuser',
    '-accesskey' => 'ABC123',
    '-password' => 'mypass',
    '-account' => '123456'
];

// Get shipping rates
function calculateShipping($from_zip, $to_zip, $weight) {
    global $ups_config;

    $rates = upsServices(array_merge($ups_config, [
        '-shipfrom_zip' => $from_zip,
        '-shipto_zip' => $to_zip,
        '-weight' => $weight
    ]));

    if(isset($rates['rates'])){
        return $rates['rates'];
    }

    return false;
}

// Track package
function trackPackage($tracking_number) {
    global $ups_config;

    $tracking = upsTrack(array_merge($ups_config, [
        '-tn' => $tracking_number
    ]));

    if(isset($tracking['status']) && !isset($tracking['error'])){
        return [
            'status' => $tracking['status'],
            'delivered' => isset($tracking['delivery_date']),
            'delivery_date' => $tracking['delivery_date'] ?? null
        ];
    }

    return false;
}

// Usage
$rates = calculateShipping('90210', '10001', 5.5);
$tracking = trackPackage('1Z12345E0205271688');
?>
```

### After (OAuth 2.0)

```php
<?php
require_once('ups.php');  // Updated file

// New credentials (from environment variables)
$ups_config = [
    '-client_id' => getenv('UPS_CLIENT_ID'),
    '-client_secret' => getenv('UPS_CLIENT_SECRET'),
    '-account' => getenv('UPS_ACCOUNT')
];

// Get shipping rates - EXACT SAME FUNCTION
function calculateShipping($from_zip, $to_zip, $weight) {
    global $ups_config;

    $rates = upsServices(array_merge($ups_config, [
        '-shipfrom_zip' => $from_zip,
        '-shipto_zip' => $to_zip,
        '-weight' => $weight
    ]));

    if(isset($rates['rates'])){
        return $rates['rates'];
    }

    return false;
}

// Track package - EXACT SAME FUNCTION
function trackPackage($tracking_number) {
    global $ups_config;

    $tracking = upsTrack(array_merge($ups_config, [
        '-tn' => $tracking_number
    ]));

    if(isset($tracking['status']) && !isset($tracking['error'])){
        return [
            'status' => $tracking['status'],
            'delivered' => isset($tracking['delivery_date']),
            'delivery_date' => $tracking['delivery_date'] ?? null
        ];
    }

    return false;
}

// Usage - EXACT SAME
$rates = calculateShipping('90210', '10001', 5.5);
$tracking = trackPackage('1Z12345E0205271688');
?>
```

**What changed:**
- ✅ Credentials array (3 lines)
- ✅ Environment variable usage (best practice)
- ❌ NO changes to function implementations
- ❌ NO changes to usage code
- ❌ NO changes to response handling

---

## Testing Your Migration

### Step 1: Test in Sandbox

Use sandbox credentials first to verify your migration:

```php
$test_config = [
    '-client_id' => 'your_sandbox_client_id',
    '-client_secret' => 'your_sandbox_client_secret',
    '-test' => true  // Use sandbox environment
];

// Test authentication
$token = upsGetOAuthToken($test_config);
if(!isset($token['access_token'])){
    die("Sandbox auth failed: {$token['error']}");
}
echo "✓ Sandbox authentication works\n";

// Test rating
$rates = upsServices(array_merge($test_config, [
    '-account' => 'test_account',
    '-shipfrom_zip' => '90210',
    '-shipto_zip' => '10001',
    '-weight' => 5.5
]));

if(isset($rates['rates'])){
    echo "✓ Sandbox rating works\n";
} else {
    echo "✗ Sandbox rating failed: {$rates['error']}\n";
}

// Test tracking with test tracking number
$tracking = upsTrack(array_merge($test_config, [
    '-tn' => '1Z12345E0205271688'  // Test tracking number
]));

if(isset($tracking['status']) && !isset($tracking['error'])){
    echo "✓ Sandbox tracking works\n";
} else {
    echo "✗ Sandbox tracking failed: {$tracking['error']}\n";
}
```

### Step 2: Test with Production Credentials

Once sandbox testing passes, test with production credentials:

```php
$prod_config = [
    '-client_id' => getenv('UPS_PROD_CLIENT_ID'),
    '-client_secret' => getenv('UPS_PROD_CLIENT_SECRET'),
    '-account' => getenv('UPS_ACCOUNT')
    // No -test parameter = production
];

// Test with real data
$rates = upsServices(array_merge($prod_config, [
    '-shipfrom_zip' => 'your_real_origin_zip',
    '-shipto_zip' => 'your_real_dest_zip',
    '-weight' => 5.5
]));
```

### Step 3: Parallel Testing

If possible, run old and new code in parallel temporarily:

```php
// Get rates with both APIs
$old_rates = old_upsServices([...old credentials...]);
$new_rates = upsServices([...new credentials...]);

// Compare results
if(json_encode($old_rates['rates']) != json_encode($new_rates['rates'])){
    error_log("WARNING: Rate mismatch detected");
    // Alert your team
}
```

---

## Common Migration Issues

### Issue 1: "invalid_client" Error

**Symptom:**
```php
[
    'error' => 'The client identifier provided is invalid',
    'error_code' => 'invalid_client'
]
```

**Solutions:**
- ✅ Verify Client ID is correct (no typos)
- ✅ Verify Client Secret is correct (no extra spaces)
- ✅ Ensure you're using production credentials for production environment
- ✅ Regenerate credentials in Developer Portal if needed

### Issue 2: "No rates returned"

**Symptom:**
```php
[
    'error' => 'No rates returned'
]
```

**Solutions:**
- ✅ Verify UPS account number is correct
- ✅ Check that account is active and in good standing
- ✅ Verify origin/destination ZIP codes are valid
- ✅ Check package weight is reasonable (> 0, < 150 lbs for most services)
- ✅ Enable error logging to see full API response

### Issue 3: Token Expires Too Quickly

**Symptom:**
- API calls fail intermittently
- Works then stops working after ~4 hours

**Solution:**
Implement token caching:

```php
class UPSAuth {
    private static $token = null;
    private static $expires = 0;

    public static function getToken() {
        if(self::$token && time() < self::$expires) {
            return self::$token;
        }

        $result = upsGetOAuthToken([
            '-client_id' => getenv('UPS_CLIENT_ID'),
            '-client_secret' => getenv('UPS_CLIENT_SECRET')
        ]);

        if(isset($result['access_token'])){
            self::$token = $result['access_token'];
            self::$expires = time() + (3.5 * 3600); // 3.5 hours
            return self::$token;
        }

        return null;
    }
}
```

### Issue 4: "No tracking information available"

**Symptom:**
```php
[
    'error' => 'No tracking information available',
    'error_code' => '151044'
]
```

**Solutions:**
- ✅ Verify tracking number is correct
- ✅ Wait 24 hours after shipment creation (tracking may not be immediate)
- ✅ Check if package was actually shipped
- ✅ Verify you're using production environment for production tracking numbers

### Issue 5: Performance Degradation

**Symptom:**
- API calls are slower than before
- Timeout errors

**Solutions:**
- ✅ Implement token caching (see Issue 3)
- ✅ Cache rate responses (rates don't change frequently)
- ✅ Use `-access_token` parameter to reuse tokens
- ✅ Increase timeout settings if needed

---

## Rollback Plan

If you need to rollback temporarily:

### Option 1: Keep Old File

```php
// Keep old file as backup
// Before: ups.php (old version)
// After migration:
//   - ups_old.php (old version - backup)
//   - ups.php (new version)

// To rollback temporarily:
require_once('ups_old.php');  // Use old version
```

### Option 2: Environment-Based Switching

```php
$use_new_api = getenv('UPS_USE_NEW_API') === 'true';

if($use_new_api){
    require_once('ups.php');  // New OAuth version
} else {
    require_once('ups_old.php');  // Old XML version (won't work after June 2024)
}
```

**Note:** The old XML API is deprecated and will not work with UPS servers. Rollback is only possible if you kept a backup and UPS restores the old API (unlikely).

---

## Migration Timeline Recommendation

### Week 1: Preparation
- [ ] Read this migration guide
- [ ] Create UPS Developer Portal account
- [ ] Request sandbox credentials
- [ ] Review current code usage

### Week 2: Development
- [ ] Update to new `ups.php` file
- [ ] Update credential variables
- [ ] Implement token caching
- [ ] Test in sandbox environment

### Week 3: Testing
- [ ] Request production credentials
- [ ] Test with production credentials
- [ ] Parallel test (if possible)
- [ ] Load test

### Week 4: Deployment
- [ ] Deploy to staging environment
- [ ] Deploy to production
- [ ] Monitor error logs
- [ ] Update documentation

---

## Post-Migration Checklist

After successful migration:

- [ ] Remove old credential variables from code
- [ ] Delete old backup files (after confirming stability)
- [ ] Update team documentation
- [ ] Update deployment scripts
- [ ] Configure monitoring/alerts for API failures
- [ ] Document new credential rotation process
- [ ] Train team on new authentication flow

---

## Need Help?

### Resources

- **UPS Developer Portal**: https://developer.ups.com
- **API Documentation**: https://developer.ups.com/api/reference
- **OAuth Guide**: https://developer.ups.com/oauth-developer-guide
- **UPS Technical Support**: Available through Developer Portal

### Common Questions

**Q: Do I need to change my UPS account number?**
A: No, your UPS account number remains the same.

**Q: Can I use old and new API simultaneously?**
A: No, the old API is deprecated. You must migrate to OAuth 2.0.

**Q: How long are access tokens valid?**
A: Approximately 4 hours (14,400 seconds).

**Q: Do I need separate credentials for sandbox and production?**
A: Yes, create separate apps in the Developer Portal for sandbox and production.

**Q: Will response formats change?**
A: No, we've maintained the same response structure for easy migration.

**Q: What if I have custom modifications to ups.php?**
A: Document your changes, then apply them to the new version. Contact us if you need help.

---

## Summary

The migration to OAuth 2.0 is straightforward:

1. **Get new credentials** from UPS Developer Portal
2. **Update credential parameters** in your code:
   - `-userid`, `-accesskey`, `-password` → `-client_id`, `-client_secret`
3. **Test thoroughly** in sandbox environment
4. **Deploy to production** with confidence

The function signatures and response formats remain the same, making this primarily a credential update rather than a code rewrite.

**Total migration effort:** 1-4 hours depending on complexity and testing requirements.

Good luck with your migration! 🚀
