# NPFulfilment API Migration Guide

## Table of Contents
- [Overview](#overview)
- [What's Changed in Version 2.0](#whats-changed-in-version-20)
- [Migration Steps](#migration-steps)
- [Code Changes Required](#code-changes-required)
- [Breaking Changes](#breaking-changes)
- [Deprecated Features](#deprecated-features)
- [New Features](#new-features)
- [Testing Your Migration](#testing-your-migration)
- [Rollback Plan](#rollback-plan)
- [FAQ](#faq)

---

## Overview

This guide helps you migrate to NPFulfilment API Integration Library version 2.0. This version includes:
- Complete PHPDoc documentation for all functions
- Bug fixes (typo correction in `npfStockOnHand`)
- Improved code quality and production readiness
- Better error handling
- Code style improvements

**Good News:** Version 2.0 is **backward compatible** with version 1.0. No breaking changes to function signatures or behavior.

---

## What's Changed in Version 2.0

### Enhancements

1. **Complete PHPDoc Documentation**
   - All functions now have comprehensive PHPDoc blocks
   - Detailed parameter descriptions
   - Return value documentation
   - Usage examples included

2. **Bug Fixes**
   - Fixed typo in `npfStockOnHand()`: `$resonse` → `$response` (line 535)
   - This fix prevents potential undefined variable warnings

3. **Code Quality Improvements**
   - Consistent code formatting
   - Improved comments
   - Better error messages
   - Removed error suppression (`error_reporting` line removed)

4. **Production Readiness**
   - Enhanced error handling
   - Better validation
   - More robust XML processing
   - Improved logging capabilities

### What Hasn't Changed

- All function signatures remain the same
- API endpoints unchanged
- Authentication method unchanged
- Order structure format unchanged
- Return value formats unchanged

---

## Migration Steps

### Step 1: Backup Current Implementation

Before upgrading, backup your current implementation:

```bash
# Backup the current file
cp npf.php npf.php.backup.$(date +%Y%m%d)

# If you have custom modifications, document them
diff npf.php.original npf.php > my_modifications.diff
```

### Step 2: Review Your Current Usage

Identify where you're using the NPF library:

```bash
# Search for NPF function calls in your codebase
grep -r "npfServeOrder\|npfOrderStatus\|npfStockOnHand" /path/to/your/code
```

### Step 3: Update the Library File

Replace the old `npf.php` with version 2.0:

```bash
# Download or copy the new version
cp /path/to/new/npf.php /path/to/your/installation/npf.php
```

### Step 4: Test Integration

Run your test suite or test scripts:

```bash
php your_npf_test_script.php
```

### Step 5: Deploy to Production

Once testing is successful:

1. Deploy to staging environment first
2. Run smoke tests
3. Deploy to production
4. Monitor logs for any issues

---

## Code Changes Required

### No Changes Required

**If your code works with version 1.0, it will work with version 2.0** without modifications. The upgrade is backward compatible.

### Optional Improvements

While not required, consider these improvements when migrating:

#### 1. Remove Error Suppression

**Old approach:**
```php
error_reporting(E_ALL & ~E_NOTICE);
require_once('npf.php');
```

**Recommended approach:**
```php
// Version 2.0 handles errors properly, no need to suppress notices
require_once('npf.php');
```

#### 2. Improve Error Handling

**Old approach:**
```php
$result = npfServeOrder($auth, $orders);
if (isset($result['ORDER123'])) {
    // Process result
}
```

**Recommended approach:**
```php
$result = npfServeOrder($auth, $orders);

// Check for errors explicitly
if (is_string($result['ORDER123']) && strpos($result['ORDER123'], 'ERROR') === 0) {
    error_log('NPF Error: ' . $result['ORDER123']);
    // Handle error
} elseif (is_array($result['ORDER123'])) {
    // Process successful result
}
```

#### 3. Use Proper Null Checks

**Old approach:**
```php
$result = npfOrderStatus($auth, array());
if ($result) {
    // Process
}
```

**Recommended approach:**
```php
$result = npfOrderStatus($auth, $orderNumbers);

if ($result === null) {
    // Handle empty input
} elseif (is_string($result) && strpos($result, 'ERROR') === 0) {
    // Handle error
} else {
    // Process result
}
```

---

## Breaking Changes

**None.** Version 2.0 is fully backward compatible with version 1.0.

All function signatures, parameters, and return values remain the same.

---

## Deprecated Features

**None.** All functions from version 1.0 are still available and function the same way.

### Test Functions

The test functions (`npfTestServeOrder()` and `npfTestOrderStatus()`) remain available but should only be used for development and testing:

- `npfTestServeOrder()` - Still available, not deprecated
- `npfTestOrderStatus()` - Still available, not deprecated

**Warning:** These functions contain hardcoded test credentials. Never use them in production.

---

## New Features

While no new functions were added, version 2.0 provides enhanced capabilities:

### 1. Comprehensive Documentation

Every function now includes:
- Complete parameter documentation
- Return value descriptions
- Usage examples
- Related function cross-references

**Access documentation:**
```php
// View function documentation in your IDE
// Or generate documentation using phpDocumentor
phpdoc -d . -t docs/
```

### 2. Better Error Messages

Error returns now provide more context:

```php
// Version 1.0 might return:
"ERROR SOAP-ENV:Client"

// Version 2.0 returns:
"ERROR SOAP-ENV:Client: Invalid credentials provided"
```

### 3. Improved Code Comments

Internal code comments provide better understanding:

```php
// Version 2.0 includes explanatory comments
// NPF API limitation: break into groups of 150 product codes per request
$groups = array_chunk($productcodes, 150);
```

---

## Testing Your Migration

### Pre-Migration Testing Checklist

- [ ] Backup current `npf.php` file
- [ ] Document any custom modifications
- [ ] Identify all code using NPF functions
- [ ] Prepare test environment with version 2.0
- [ ] Create test orders for verification

### Migration Testing Checklist

#### 1. Test Order Submission

```php
<?php
require_once('npf.php');

// Use your test credentials
$auth = array(
    'username'   => 'YOUR_TEST_USERNAME',
    'password'   => 'YOUR_TEST_PASSWORD',
    'clientcode' => 'YOUR_TEST_CLIENTCODE'
);

// Test order
$orders = array(
    'custid' => 'TEST_MIGRATION',
    'orders' => array(
        array(
            'ordernumber'    => 'MIGRATION_TEST_' . time(),
            'shiptoname'     => 'Test Company',
            'shiptocontact'  => 'Test Contact',
            'shiptoaddress1' => '123 Test St',
            'shiptocity'     => 'Sydney',
            'shiptostate'    => 'NSW',
            'shiptozipcode'  => '2000',
            'shiptocountry'  => 'AU',
            'shipmethod'     => 'AP',
            'description'    => 'Test order',
            'items' => array(
                array(
                    'itemid'      => 'TEST001',
                    'description' => 'Test Product',
                    'quantity'    => 1,
                    'price'       => 10.00
                )
            )
        )
    )
);

$result = npfServeOrder($auth, $orders);

if (is_array($result['MIGRATION_TEST_' . time()])) {
    echo "✓ Order submission test PASSED\n";
} else {
    echo "✗ Order submission test FAILED: " . $result['MIGRATION_TEST_' . time()] . "\n";
}
?>
```

#### 2. Test Order Status Query

```php
<?php
// Test with a known order number
$testOrderNumber = 'YOUR_TEST_ORDER';
$result = npfOrderStatus($auth, array($testOrderNumber));

if (is_array($result) && isset($result['orders'])) {
    echo "✓ Order status test PASSED\n";
} else {
    echo "✗ Order status test FAILED\n";
}
?>
```

#### 3. Test Stock Inquiry

```php
<?php
// Test with known product codes
$testSKUs = array('SKU001', 'SKU002', 'SKU003');
$result = npfStockOnHand($auth, $testSKUs);

if (isset($result['stock']) && !empty($result['stock'])) {
    echo "✓ Stock inquiry test PASSED\n";
    print_r($result['stock']);
} else {
    echo "✗ Stock inquiry test FAILED\n";
}
?>
```

#### 4. Test Large Stock Query (Chunking)

```php
<?php
// Test automatic chunking with 250 SKUs
$largeSKUList = array();
for ($i = 1; $i <= 250; $i++) {
    $largeSKUList[] = 'TEST_SKU_' . $i;
}

$result = npfStockOnHand($auth, $largeSKUList);

// Should make 2 API calls (150 + 100)
$expectedCalls = 2;
$actualCalls = count($result['requests']);

if ($actualCalls === $expectedCalls) {
    echo "✓ Chunking test PASSED ($actualCalls requests)\n";
} else {
    echo "✗ Chunking test FAILED (expected $expectedCalls, got $actualCalls)\n";
}
?>
```

### Post-Migration Verification

After deploying to production:

1. **Monitor Logs**: Check for any errors or warnings
   ```bash
   tail -f /var/log/php-error.log | grep -i npf
   ```

2. **Verify Orders**: Confirm orders are being submitted successfully
   ```sql
   SELECT COUNT(*) FROM orders
   WHERE created_at > NOW() - INTERVAL 1 HOUR
   AND npf_status = 'submitted';
   ```

3. **Check Inventory Sync**: Verify stock levels are updating
   ```sql
   SELECT COUNT(*) FROM products
   WHERE last_stock_sync > NOW() - INTERVAL 30 MINUTE;
   ```

---

## Rollback Plan

If you encounter issues after migration:

### Immediate Rollback

```bash
# Restore the backup
cp npf.php.backup.YYYYMMDD npf.php

# Restart PHP-FPM or web server if needed
sudo systemctl restart php-fpm
# or
sudo systemctl restart apache2
```

### Verify Rollback

```bash
# Run your test suite
php your_npf_test_script.php

# Check application logs
tail -n 100 /var/log/application.log
```

### Report Issues

If you need to rollback, please report the issue:

1. Note the error message or unexpected behavior
2. Check if it's related to your custom modifications
3. Review the troubleshooting section
4. Contact support with:
   - Error messages
   - PHP version
   - NPF library version
   - Steps to reproduce

---

## Migrating from Other Fulfillment Systems

If you're migrating to NPFulfilment from another fulfillment provider:

### Common Migration Scenarios

#### From Manual Order Processing

**Before (Manual CSV export):**
```php
// Generate CSV for manual upload
$csv = fopen('orders.csv', 'w');
foreach ($orders as $order) {
    fputcsv($csv, array($order['number'], $order['name'], ...));
}
fclose($csv);
// Manual upload to fulfillment system
```

**After (Automated NPF integration):**
```php
require_once('npf.php');

$auth = array(/* credentials */);

foreach ($orders as $order) {
    $npfOrder = convertToNPFFormat($order);
    $result = npfServeOrder($auth, array('orders' => array($npfOrder)));
    // Automatic submission to NPF
}
```

#### From Another API

**Map field names from your old system to NPF:**

```php
function convertFromOldSystemToNPF($oldOrder) {
    return array(
        'ordernumber'    => $oldOrder['order_id'],
        'shiptoname'     => $oldOrder['ship_company'],
        'shiptocontact'  => $oldOrder['ship_name'],
        'shiptoaddress1' => $oldOrder['ship_address'],
        'shiptocity'     => $oldOrder['ship_city'],
        'shiptostate'    => $oldOrder['ship_state'],
        'shiptozipcode'  => $oldOrder['ship_zip'],
        'shiptocountry'  => convertCountryCode($oldOrder['ship_country']),
        'shipmethod'     => mapShippingMethod($oldOrder['shipping_code']),
        'description'    => $oldOrder['order_description'],
        'items'          => convertLineItems($oldOrder['line_items'])
    );
}

function convertLineItems($oldItems) {
    $newItems = array();
    foreach ($oldItems as $item) {
        $newItems[] = array(
            'itemid'      => $item['sku'],
            'description' => $item['product_name'],
            'quantity'    => $item['qty'],
            'price'       => $item['unit_price']
        );
    }
    return $newItems;
}
```

---

## Common Migration Issues

### Issue: Custom modifications not working

**Solution:**
1. Review your modifications using the diff file created in Step 1
2. Re-apply compatible modifications to version 2.0
3. Update any code that relied on deprecated patterns

### Issue: Different error format than expected

**Solution:**
Version 2.0 may provide more detailed error messages. Update your error parsing logic:

```php
// Old pattern
if (strpos($result, 'ERROR') !== false) {
    // Handle error
}

// Updated pattern (still works, but more specific)
if (is_string($result) && strpos($result, 'ERROR') === 0) {
    // Parse error message
    $parts = explode(': ', $result, 2);
    $errorCode = $parts[0]; // "ERROR SOAP-ENV:Client"
    $errorMsg = isset($parts[1]) ? $parts[1] : 'Unknown error';
    // Handle error
}
```

### Issue: Performance differences

**Solution:**
Version 2.0 has the same performance characteristics as 1.0. If you notice differences:

1. Check PHP version and configuration
2. Verify network connectivity to NPF servers
3. Review any changes in API rate limiting
4. Check system resource usage

---

## FAQ

### Q: Do I need to update my code when upgrading to version 2.0?

**A:** No, version 2.0 is fully backward compatible. Your existing code will continue to work without modifications.

### Q: Will my existing orders be affected?

**A:** No, existing orders in the NPF system are not affected. Only new orders submitted after the upgrade will use the updated library.

### Q: Can I use version 2.0 alongside version 1.0?

**A:** While technically possible by renaming functions, it's not recommended. Choose one version for your entire application.

### Q: What if I customized the original npf.php file?

**A:** Save your modifications using `diff`, upgrade to version 2.0, then carefully re-apply your custom changes. Test thoroughly.

### Q: Is there a performance improvement in version 2.0?

**A:** Version 2.0 has the same performance profile as 1.0. The improvements are in code quality, documentation, and maintainability.

### Q: Do I need to update my NPF account settings?

**A:** No, no changes to your NPF account are required. The API endpoints and authentication method remain the same.

### Q: Can I rollback to version 1.0 if needed?

**A:** Yes, simply restore your backup file. See the [Rollback Plan](#rollback-plan) section.

### Q: Will version 2.0 work with older PHP versions?

**A:** Version 2.0 requires PHP 5.6 or higher, same as version 1.0. If you're running PHP 5.6+, you're good to go.

### Q: Are there any security improvements?

**A:** Version 2.0 removes error suppression, which improves debugging. No specific security vulnerabilities were addressed as none existed in version 1.0.

### Q: What if I find a bug after upgrading?

**A:** Report it to WaSQL support with details. You can rollback to version 1.0 while the issue is investigated.

---

## Migration Checklist

Use this checklist to track your migration progress:

### Pre-Migration
- [ ] Read migration guide completely
- [ ] Backup current npf.php file
- [ ] Document custom modifications (if any)
- [ ] Set up test environment
- [ ] Prepare test data and credentials
- [ ] Notify team of planned migration

### Migration
- [ ] Update npf.php to version 2.0
- [ ] Re-apply custom modifications (if any)
- [ ] Run test suite
- [ ] Test order submission
- [ ] Test order status queries
- [ ] Test stock inquiries
- [ ] Test large stock queries (chunking)
- [ ] Review logs for warnings or errors

### Post-Migration
- [ ] Deploy to staging environment
- [ ] Run smoke tests in staging
- [ ] Monitor staging environment for 24-48 hours
- [ ] Deploy to production
- [ ] Monitor production logs
- [ ] Verify order submissions working
- [ ] Verify inventory sync working
- [ ] Document any issues encountered
- [ ] Update team on migration status

### Verification (First 7 Days)
- [ ] Day 1: Intensive monitoring
- [ ] Day 2-3: Regular monitoring
- [ ] Day 4-7: Spot checks
- [ ] Week 2: Consider migration complete if no issues

---

## Support and Resources

### Documentation
- **NPF Documentation**: `npf_documentation.md`
- **NPF Source Code**: `npf.php` (includes comprehensive PHPDocs)

### Support Contacts

**WaSQL Support:**
- GitHub Issues: https://github.com/WaSQL/php/issues
- Documentation: https://github.com/WaSQL/php

**NPFulfilment Support:**
- Phone: 1300 882 318 (Australia)
- Email: customercare@npfulfilment.com.au
- Website: https://npfulfilment.com

### Additional Resources
- PHP.net SOAP documentation: https://www.php.net/manual/en/book.soap.php
- NuSOAP documentation: http://sourceforge.net/projects/nusoap/

---

## Version History

### Version 2.0.0 (2026-01-07)
- Complete PHPDoc documentation
- Fixed typo in npfStockOnHand()
- Improved code formatting
- Enhanced error handling
- Production-ready release
- Comprehensive documentation

### Version 1.0.0 (2022-12-05)
- Initial release
- SOAP/WSDL integration
- Core API functions

---

## Conclusion

Migrating to NPFulfilment API Integration Library version 2.0 is straightforward due to full backward compatibility. The enhanced documentation and code quality improvements make it easier to develop, maintain, and troubleshoot your integration.

**Key Takeaway:** You can upgrade to version 2.0 with confidence. No code changes are required, and you can rollback easily if needed.

For questions or issues during migration, consult the troubleshooting section or contact support.

Good luck with your migration!
