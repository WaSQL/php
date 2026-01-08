# NPFulfilment API Integration Documentation

## Table of Contents
- [Overview](#overview)
- [Getting Started](#getting-started)
- [Authentication](#authentication)
- [API Functions](#api-functions)
- [Order Submission](#order-submission)
- [Order Status](#order-status)
- [Inventory Management](#inventory-management)
- [Error Handling](#error-handling)
- [Code Examples](#code-examples)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## Overview

The NPFulfilment API integration library provides PHP wrapper functions for interacting with NPFulfilment's SOAP/WSDL-based fulfillment services. NPFulfilment (National Products Fulfilment) is an order fulfillment and logistics provider operating in Australia and New Zealand.

### Key Features
- **Order Submission**: Submit orders for fulfillment with complete shipping and billing details
- **Order Tracking**: Query real-time order status and tracking information
- **Inventory Management**: Retrieve current stock levels for products
- **Automatic Chunking**: Handles large inventory queries by automatically splitting into appropriate batch sizes
- **Error Handling**: Built-in error detection and reporting

### API Endpoints
- **Order Submission**: `https://npfulfilmentapi.com/serverorder.php?wsdl`
- **Order Status**: `https://npfulfilmentapi.com/npforderstatus.php?wsdl`
- **Stock on Hand**: `https://npfulfilmentapi.com/npfsoh.php?wsdl`

### Requirements
- PHP 5.6 or higher
- NuSOAP library (included in `npf/lib/nusoap.php`)
- WaSQL framework functions (`xml2Array`, `xmlEncode`, `xmlHeader`, etc.)
- NPFulfilment account with API credentials

---

## Getting Started

### Installation

1. Include the NPF library in your PHP application:
```php
require_once('path/to/npf.php');
```

2. Ensure the NuSOAP library is accessible at `npf/lib/nusoap.php`

### Quick Start Example

```php
<?php
require_once('npf.php');

// Configure authentication
$auth = array(
    'username'   => 'YOUR_NPF_USERNAME',
    'password'   => 'YOUR_NPF_PASSWORD',
    'clientcode' => 'YOUR_NPF_CLIENTCODE'
);

// Submit an order
$orders = array(
    'custid' => 'CUST123',
    'orders' => array(
        array(
            'ordernumber'     => 'ORD' . time(),
            'shiptoname'      => 'Acme Corporation',
            'shiptocontact'   => 'John Smith',
            'shiptoaddress1'  => '123 Main Street',
            'shiptocity'      => 'Sydney',
            'shiptostate'     => 'NSW',
            'shiptozipcode'   => '2000',
            'shiptocountry'   => 'AU',
            'shipmethod'      => 'AP',
            'description'     => 'office supplies',
            'items' => array(
                array(
                    'itemid'      => 'SKU001',
                    'description' => 'Office Widget',
                    'quantity'    => 5,
                    'price'       => 19.99
                )
            )
        )
    )
);

$result = npfServeOrder($auth, $orders);

if (is_array($result['ORD' . time()])) {
    echo "Order submitted successfully!\n";
} else {
    echo "Error: " . $result['ORD' . time()] . "\n";
}
?>
```

---

## Authentication

All NPFulfilment API functions require an authentication array with three components:

```php
$auth = array(
    'username'   => 'YOUR_USERNAME',   // NPFulfilment API username
    'password'   => 'YOUR_PASSWORD',   // NPFulfilment API password
    'clientcode' => 'YOUR_CLIENTCODE'  // NPFulfilment client code
);
```

### Obtaining Credentials

Contact NPFulfilment support to obtain your API credentials:
- **Phone**: 1300 882 318 (Australia)
- **Email**: customercare@npfulfilment.com.au
- **Website**: https://npfulfilment.com

### Security Best Practices

1. **Never hardcode credentials** in production code
2. Store credentials in environment variables or secure configuration files
3. Use different credentials for development/staging/production environments
4. Restrict file permissions on configuration files containing credentials
5. Never commit credentials to version control systems

Example using environment variables:
```php
$auth = array(
    'username'   => getenv('NPF_USERNAME'),
    'password'   => getenv('NPF_PASSWORD'),
    'clientcode' => getenv('NPF_CLIENTCODE')
);
```

---

## API Functions

### Overview of Available Functions

| Function | Purpose | Returns |
|----------|---------|---------|
| `npfServeOrder()` | Submit orders for fulfillment | Array with order submission results |
| `npfOrderStatus()` | Query order status and tracking | Array with order status information |
| `npfStockOnHand()` | Get inventory levels | Array with stock quantities |
| `npfAuthXML()` | Generate authentication XML | String (XML authentication block) |
| `npfPostXML()` | Low-level SOAP communication | String (XML response or error) |
| `npfTestServeOrder()` | Test order submission | Array (test results) |
| `npfTestOrderStatus()` | Test order status query | Array (test results) |

---

## Order Submission

### npfServeOrder()

Submit orders to NPFulfilment for fulfillment.

#### Function Signature
```php
npfServeOrder(array $auth, array $orders, int $debug = 0)
```

#### Parameters

**$auth** (array) - Authentication credentials:
- `username` (string) - NPFulfilment API username
- `password` (string) - NPFulfilment API password
- `clientcode` (string) - NPFulfilment client code

**$orders** (array) - Order data structure:
- `custid` (string) - Customer identifier
- `orders` (array) - Array of order objects

**$debug** (int) - Debug mode (optional):
- `0` (default) - Send order to API
- `1` - Return XML without sending (for testing)

#### Order Object Structure

Each order in the `orders` array must contain:

**Required Fields:**
- `ordernumber` (string) - Unique order reference number
- `shiptoname` (string) - Recipient company name
- `shiptocontact` (string) - Recipient contact person (full name)
- `shiptoaddress1` (string) - Shipping address line 1
- `shiptocity` (string) - Shipping city
- `shiptostate` (string) - Shipping state/province
- `shiptozipcode` (string) - Shipping postal code
- `shiptocountry` (string) - Shipping country ('AU' or 'Australia', 'NZ' or 'New Zealand')
- `shipmethod` (string) - Dispatch method code
- `description` (string) - Article description for customs
- `items` (array) - Array of line items

**Optional Fields:**
- `shiptoaddress2` (string) - Shipping address line 2
- `billtoname` (string) - Billing company name (defaults to shipto if not provided)
- `billtocontact` (string) - Billing contact person
- `billtoaddress1` (string) - Billing address line 1
- `billtoaddress2` (string) - Billing address line 2
- `billtocity` (string) - Billing city
- `billtostate` (string) - Billing state
- `billtozipcode` (string) - Billing postal code
- `billtocountry` (string) - Billing country
- `billtotelephone` (string) - Billing phone
- `billtoemail` (string) - Billing email
- `giftwrap` (string) - Gift wrapping required (Y/N)
- `customer_message` (string) - Customer message (max 200 characters)
- `value` (float) - Order value (auto-calculated if not provided)
- `url` (string) - Website code

#### Line Item Structure

Each item in the `items` array must contain:

**Required:**
- `itemid` (string) - Product/SKU code
- `description` (string) - Product description
- `quantity` or `qtyordered` (int) - Quantity to ship
- `price` or `retail_price` (float) - Unit price

**Optional:**
- `upc` (string) - Barcode (defaults to itemid)
- `gst` (float) - GST amount (auto-calculated at 10%)
- `gstprice` (float) - Price including GST (auto-calculated)
- `amount` (float) - Line total (auto-calculated)

#### Return Value

Returns an array with:
- `raw_request` (array) - XML requests sent, keyed by order number
- `raw` (array) - Raw XML responses, keyed by order number
- `[ordernumber]` (array|string) - Parsed response for each order, or error string

#### Example: Complete Order Submission

```php
$auth = array(
    'username'   => 'myusername',
    'password'   => 'mypassword',
    'clientcode' => 'myclientcode'
);

$orders = array(
    'custid' => 'CUSTOMER123',
    'orders' => array(
        array(
            'ordernumber'       => 'WEB-' . date('YmdHis'),

            // Shipping Information
            'shiptoname'        => 'Acme Corporation Pty Ltd',
            'shiptocontact'     => 'Jane Doe',
            'shiptoaddress1'    => '456 Business Avenue',
            'shiptoaddress2'    => 'Suite 200',
            'shiptocity'        => 'Melbourne',
            'shiptostate'       => 'VIC',
            'shiptozipcode'     => '3000',
            'shiptocountry'     => 'AU',

            // Billing Information (optional - will default to shipping)
            'billtoname'        => 'Acme Corporation Pty Ltd',
            'billtocontact'     => 'Accounts Payable',
            'billtoaddress1'    => '456 Business Avenue',
            'billtocity'        => 'Melbourne',
            'billtostate'       => 'VIC',
            'billtozipcode'     => '3000',
            'billtocountry'     => 'Australia',
            'billtotelephone'   => '03 9876 5432',
            'billtoemail'       => 'accounts@acme.com.au',

            // Order Details
            'shipmethod'        => 'AP',  // Australia Post
            'giftwrap'          => 'N',
            'customer_message'  => 'Please handle with care',
            'description'       => 'Computer accessories',
            'url'               => 'www.example.com.au',

            // Line Items
            'items' => array(
                array(
                    'itemid'      => 'MOUSE-001',
                    'description' => 'Wireless Mouse',
                    'quantity'    => 2,
                    'price'       => 29.99,
                    'upc'         => '9876543210123'
                ),
                array(
                    'itemid'      => 'KEYBOARD-001',
                    'description' => 'Mechanical Keyboard',
                    'quantity'    => 1,
                    'price'       => 149.99,
                    'upc'         => '9876543210456'
                )
            )
        )
    )
);

$result = npfServeOrder($auth, $orders);

// Check result
$orderNumber = 'WEB-' . date('YmdHis');
if (isset($result[$orderNumber]) && is_array($result[$orderNumber])) {
    echo "Order {$orderNumber} submitted successfully\n";
    print_r($result[$orderNumber]);
} else {
    echo "Order submission failed: " . $result[$orderNumber] . "\n";
}

// Debug: View raw request and response
echo "\nRaw Request:\n" . $result['raw_request'][$orderNumber] . "\n";
echo "\nRaw Response:\n" . $result['raw'][$orderNumber] . "\n";
```

#### Country Code Handling

The library automatically converts country codes to full names:
- `'AU'` → `'Australia'`
- `'NZ'` → `'New Zealand'`

#### Automatic Calculations

The following values are calculated automatically if not provided:
- **GST**: 10% of item price
- **GST Price**: Price + GST
- **Line Amount**: GST Price × Quantity
- **Order Value**: Sum of all line amounts
- **Order Totals**: Subtotals, discounts, and final total

#### Important Notes

1. **One Order Per Request**: NPF can only process one order per API request. The function automatically handles this by submitting each order individually.

2. **Billing Address Default**: If billing address fields are not provided, they automatically default to the shipping address.

3. **Customer Message Limit**: Customer messages are automatically truncated to 200 characters.

4. **Telephone Requirement**: If no billing telephone is provided, it defaults to 'na'.

---

## Order Status

### npfOrderStatus()

Query the status of one or more orders.

#### Function Signature
```php
npfOrderStatus(array $auth, array $salesOrderNumbers)
```

#### Parameters

**$auth** (array) - Authentication credentials

**$salesOrderNumbers** (array) - Array of order numbers to query

#### Return Value

Returns an array with:
- `raw_request` (string) - XML request sent to API
- `raw_response` (string) - Raw XML response from API
- `orders` (array) - Array of order status objects

Returns `null` if `$salesOrderNumbers` is empty.
Returns error string if API call fails.

#### Example: Single Order Status

```php
$auth = array(
    'username'   => 'myusername',
    'password'   => 'mypassword',
    'clientcode' => 'myclientcode'
);

$result = npfOrderStatus($auth, array('WEB-20260107-001'));

if (is_array($result) && isset($result['orders'])) {
    foreach ($result['orders'] as $order) {
        echo "Order Number: " . $order['SalesOrderNo'] . "\n";
        echo "Status: " . $order['OrderStatus'] . "\n";

        if (isset($order['TrackingNumber'])) {
            echo "Tracking Number: " . $order['TrackingNumber'] . "\n";
        }

        if (isset($order['Carrier'])) {
            echo "Carrier: " . $order['Carrier'] . "\n";
        }
    }
} else {
    echo "Error retrieving order status\n";
}
```

#### Example: Multiple Orders Status

```php
$orderNumbers = array(
    'WEB-20260107-001',
    'WEB-20260107-002',
    'WEB-20260107-003'
);

$result = npfOrderStatus($auth, $orderNumbers);

if (is_array($result) && isset($result['orders'])) {
    echo "Retrieved status for " . count($result['orders']) . " orders\n";

    foreach ($result['orders'] as $order) {
        printf(
            "Order %s: %s\n",
            $order['SalesOrderNo'],
            $order['OrderStatus']
        );
    }
}
```

#### Order Status Response Fields

Typical fields in order status response:
- `ClientCode` - Client identifier
- `SalesOrderNo` - Order number
- `OrderStatus` - Current status (e.g., "Processing", "Shipped", "Delivered")
- `TrackingNumber` - Shipping tracking number (if shipped)
- `Carrier` - Shipping carrier name (if shipped)
- Additional fulfillment-specific fields

---

## Inventory Management

### npfStockOnHand()

Retrieve current inventory levels for products.

#### Function Signature
```php
npfStockOnHand(array $auth, array $productcodes)
```

#### Parameters

**$auth** (array) - Authentication credentials

**$productcodes** (array) - Array of product codes/SKUs to query

#### Return Value

Returns an array with:
- `stock` (array) - Associative array mapping product codes to quantities
- `requests` (array) - XML requests sent (for debugging)
- `responses` (array) - Raw XML responses (for debugging)
- `responses_array` (array) - Parsed responses (for debugging)

#### Automatic Chunking

The NPF API has limitations on the number of products per request. This function automatically handles large requests by chunking them into groups of 150 products per API call.

#### Example: Check Stock Levels

```php
$auth = array(
    'username'   => 'myusername',
    'password'   => 'mypassword',
    'clientcode' => 'myclientcode'
);

$productCodes = array('SKU001', 'SKU002', 'SKU003', 'MOUSE-001', 'KEYBOARD-001');

$result = npfStockOnHand($auth, $productCodes);

if (isset($result['stock'])) {
    echo "Current Stock Levels:\n";
    echo str_repeat('-', 40) . "\n";

    foreach ($result['stock'] as $sku => $quantity) {
        $status = $quantity > 0 ? 'In Stock' : 'Out of Stock';
        printf("%-20s %5d units  [%s]\n", $sku, $quantity, $status);
    }
} else {
    echo "Error retrieving stock information\n";
}
```

#### Example: Large Inventory Query

```php
// Query stock for 500 products (automatically chunked into 4 API calls)
$largeSKUList = array();
for ($i = 1; $i <= 500; $i++) {
    $largeSKUList[] = 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT);
}

$result = npfStockOnHand($auth, $largeSKUList);

echo "Queried " . count($result['stock']) . " products\n";
echo "API calls made: " . count($result['requests']) . "\n";

// Find low stock items
$lowStockItems = array();
foreach ($result['stock'] as $sku => $qty) {
    if ($qty < 10 && $qty > 0) {
        $lowStockItems[$sku] = $qty;
    }
}

if (!empty($lowStockItems)) {
    echo "\nLow Stock Alert (< 10 units):\n";
    foreach ($lowStockItems as $sku => $qty) {
        echo "  $sku: $qty units remaining\n";
    }
}
```

#### Example: Stock Availability Check

```php
function checkProductAvailability($auth, $items) {
    // Extract product codes
    $skus = array_column($items, 'sku');

    // Get stock levels
    $stockData = npfStockOnHand($auth, $skus);

    // Check availability
    $results = array();
    foreach ($items as $item) {
        $sku = $item['sku'];
        $requested = $item['quantity'];
        $available = isset($stockData['stock'][$sku]) ? $stockData['stock'][$sku] : 0;

        $results[$sku] = array(
            'requested'  => $requested,
            'available'  => $available,
            'can_fulfill' => $available >= $requested
        );
    }

    return $results;
}

// Usage
$orderItems = array(
    array('sku' => 'SKU001', 'quantity' => 5),
    array('sku' => 'SKU002', 'quantity' => 10),
    array('sku' => 'SKU003', 'quantity' => 2)
);

$availability = checkProductAvailability($auth, $orderItems);

foreach ($availability as $sku => $info) {
    if (!$info['can_fulfill']) {
        echo "Warning: $sku - Requested {$info['requested']}, only {$info['available']} available\n";
    }
}
```

---

## Error Handling

### Error Types

The NPF API integration returns errors in several formats:

1. **SOAP Faults**: Returned as string starting with "ERROR"
   ```php
   "ERROR SOAP-ENV:Client: Invalid credentials"
   ```

2. **Empty Results**: Functions may return `null` for invalid input
   ```php
   npfOrderStatus($auth, array()); // Returns null
   ```

3. **API Response Errors**: Errors within XML responses

### Best Practices for Error Handling

```php
function submitOrderSafely($auth, $orders) {
    try {
        // Validate inputs
        if (empty($orders['orders'])) {
            throw new Exception('No orders to submit');
        }

        // Submit order
        $result = npfServeOrder($auth, $orders);

        // Check each order result
        foreach ($orders['orders'] as $order) {
            $orderNum = $order['ordernumber'];

            if (!isset($result[$orderNum])) {
                throw new Exception("No response for order $orderNum");
            }

            // Check for error string
            if (is_string($result[$orderNum]) &&
                strpos($result[$orderNum], 'ERROR') === 0) {
                throw new Exception("Order $orderNum failed: " . $result[$orderNum]);
            }

            // Validate successful response
            if (!is_array($result[$orderNum])) {
                throw new Exception("Invalid response format for order $orderNum");
            }
        }

        return array('success' => true, 'result' => $result);

    } catch (Exception $e) {
        error_log('NPF Order Submission Error: ' . $e->getMessage());
        return array('success' => false, 'error' => $e->getMessage());
    }
}
```

### Checking Order Status Errors

```php
$result = npfOrderStatus($auth, $orderNumbers);

// Check for null (empty input)
if ($result === null) {
    echo "Error: No order numbers provided\n";
    exit;
}

// Check for error string
if (is_string($result) && strpos($result, 'ERROR') === 0) {
    echo "API Error: $result\n";
    exit;
}

// Check for valid response structure
if (!isset($result['orders']) || !is_array($result['orders'])) {
    echo "Error: Invalid response format\n";
    exit;
}

// Process orders
foreach ($result['orders'] as $order) {
    // ... process order status
}
```

### Logging and Debugging

```php
function npfOrderWithLogging($auth, $orders, $logFile = '/var/log/npf.log') {
    $timestamp = date('Y-m-d H:i:s');

    // Log request
    $logEntry = "[$timestamp] NPF Order Submission\n";
    $logEntry .= "Orders: " . json_encode(array_column($orders['orders'], 'ordernumber')) . "\n";

    // Submit order
    $result = npfServeOrder($auth, $orders);

    // Log response
    foreach ($orders['orders'] as $order) {
        $orderNum = $order['ordernumber'];
        $status = is_array($result[$orderNum]) ? 'SUCCESS' : 'FAILED';
        $logEntry .= "Order $orderNum: $status\n";

        if ($status === 'FAILED') {
            $logEntry .= "Error: " . $result[$orderNum] . "\n";
        }
    }

    $logEntry .= str_repeat('-', 80) . "\n";

    // Write to log
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    return $result;
}
```

---

## Code Examples

### Example 1: Complete E-commerce Integration

```php
<?php
require_once('npf.php');

class NPFOrderManager {
    private $auth;

    public function __construct($username, $password, $clientcode) {
        $this->auth = array(
            'username'   => $username,
            'password'   => $password,
            'clientcode' => $clientcode
        );
    }

    public function submitOrder($orderData) {
        // Build NPF order structure
        $npfOrder = array(
            'custid' => $orderData['customer_id'],
            'orders' => array(
                array(
                    'ordernumber'       => $orderData['order_number'],
                    'shiptoname'        => $orderData['shipping']['company'],
                    'shiptocontact'     => $orderData['shipping']['contact_name'],
                    'shiptoaddress1'    => $orderData['shipping']['address1'],
                    'shiptoaddress2'    => $orderData['shipping']['address2'],
                    'shiptocity'        => $orderData['shipping']['city'],
                    'shiptostate'       => $orderData['shipping']['state'],
                    'shiptozipcode'     => $orderData['shipping']['postcode'],
                    'shiptocountry'     => $orderData['shipping']['country'],
                    'shipmethod'        => $this->mapShippingMethod($orderData['shipping_method']),
                    'description'       => $orderData['description'],
                    'customer_message'  => $orderData['special_instructions'],
                    'items'             => $this->formatLineItems($orderData['items'])
                )
            )
        );

        // Submit to NPF
        $result = npfServeOrder($this->auth, $npfOrder);

        // Process result
        $orderNum = $orderData['order_number'];
        if (is_array($result[$orderNum])) {
            return array(
                'success' => true,
                'order_number' => $orderNum,
                'npf_response' => $result[$orderNum]
            );
        } else {
            return array(
                'success' => false,
                'order_number' => $orderNum,
                'error' => $result[$orderNum]
            );
        }
    }

    private function formatLineItems($items) {
        $formatted = array();
        foreach ($items as $item) {
            $formatted[] = array(
                'itemid'      => $item['sku'],
                'description' => $item['name'],
                'quantity'    => $item['quantity'],
                'price'       => $item['price'],
                'upc'         => isset($item['barcode']) ? $item['barcode'] : $item['sku']
            );
        }
        return $formatted;
    }

    private function mapShippingMethod($method) {
        $methodMap = array(
            'standard'  => 'AP',    // Australia Post
            'express'   => 'FDX',   // FedEx
            'overnight' => 'TNT'    // TNT
        );
        return isset($methodMap[$method]) ? $methodMap[$method] : 'AP';
    }

    public function getOrderStatus($orderNumbers) {
        if (!is_array($orderNumbers)) {
            $orderNumbers = array($orderNumbers);
        }

        $result = npfOrderStatus($this->auth, $orderNumbers);

        if ($result === null || is_string($result)) {
            return array('success' => false, 'error' => $result);
        }

        return array('success' => true, 'orders' => $result['orders']);
    }

    public function checkInventory($skus) {
        if (!is_array($skus)) {
            $skus = array($skus);
        }

        $result = npfStockOnHand($this->auth, $skus);

        if (!isset($result['stock'])) {
            return array('success' => false, 'error' => 'Failed to retrieve stock');
        }

        return array('success' => true, 'stock' => $result['stock']);
    }
}

// Usage
$npf = new NPFOrderManager(
    getenv('NPF_USERNAME'),
    getenv('NPF_PASSWORD'),
    getenv('NPF_CLIENTCODE')
);

// Submit order
$orderData = array(
    'customer_id' => 'CUST123',
    'order_number' => 'WEB-' . time(),
    'shipping' => array(
        'company' => 'Test Company',
        'contact_name' => 'John Doe',
        'address1' => '123 Test St',
        'address2' => '',
        'city' => 'Sydney',
        'state' => 'NSW',
        'postcode' => '2000',
        'country' => 'AU'
    ),
    'shipping_method' => 'standard',
    'description' => 'Test products',
    'special_instructions' => 'Handle with care',
    'items' => array(
        array('sku' => 'TEST001', 'name' => 'Test Product', 'quantity' => 1, 'price' => 29.99)
    )
);

$result = $npf->submitOrder($orderData);
if ($result['success']) {
    echo "Order submitted successfully!\n";
} else {
    echo "Order failed: " . $result['error'] . "\n";
}
?>
```

### Example 2: Inventory Sync Script

```php
<?php
require_once('npf.php');

// Configuration
$auth = array(
    'username'   => getenv('NPF_USERNAME'),
    'password'   => getenv('NPF_PASSWORD'),
    'clientcode' => getenv('NPF_CLIENTCODE')
);

// Get all product SKUs from your database
$db = new PDO('mysql:host=localhost;dbname=mystore', 'user', 'pass');
$stmt = $db->query('SELECT sku FROM products WHERE active = 1');
$skus = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Syncing inventory for " . count($skus) . " products...\n";

// Get stock levels from NPF
$stockData = npfStockOnHand($auth, $skus);

if (!isset($stockData['stock'])) {
    die("Error retrieving stock data\n");
}

// Update database with current stock levels
$updateStmt = $db->prepare('UPDATE products SET stock_qty = ?, last_sync = NOW() WHERE sku = ?');

$updated = 0;
$outOfStock = 0;

foreach ($stockData['stock'] as $sku => $quantity) {
    $updateStmt->execute(array($quantity, $sku));
    $updated++;

    if ($quantity == 0) {
        $outOfStock++;
    }
}

echo "Updated $updated products\n";
echo "Out of stock: $outOfStock products\n";
echo "Sync completed at " . date('Y-m-d H:i:s') . "\n";
?>
```

---

## Best Practices

### 1. Credential Management

- Store credentials securely using environment variables or encrypted configuration
- Never commit credentials to version control
- Use separate credentials for development and production
- Rotate credentials periodically

### 2. Order Submission

- Always validate order data before submission
- Use unique order numbers (consider including timestamp)
- Provide complete address information to avoid delivery issues
- Include customer contact information for delivery notifications
- Set appropriate shipping methods based on customer selection

### 3. Error Handling

- Always check return values for errors
- Log API requests and responses for troubleshooting
- Implement retry logic for transient failures
- Notify administrators of persistent errors

### 4. Performance Optimization

- Cache stock levels with appropriate TTL (e.g., 5-15 minutes)
- Batch order status checks when possible
- Use asynchronous processing for large inventory syncs
- Implement queue system for order submissions during high traffic

### 5. Testing

- Use debug mode (`$debug = 1`) to test order XML without submitting
- Test with NPF sandbox/test environment if available
- Validate address formats before submission
- Test error handling scenarios

### 6. Monitoring

- Log all API interactions
- Monitor API response times
- Set up alerts for failed orders
- Track order fulfillment success rates
- Monitor inventory sync accuracy

---

## Troubleshooting

### Common Issues and Solutions

#### Issue: "ERROR SOAP-ENV:Client: Invalid credentials"

**Solution:**
- Verify username, password, and client code are correct
- Check for extra spaces or special characters in credentials
- Confirm credentials are active with NPFulfilment support

#### Issue: Orders not appearing in NPF system

**Solution:**
- Check that order was submitted successfully (no error in response)
- Verify order number is unique
- Check raw XML request/response for formatting issues
- Contact NPF support to verify order was received

#### Issue: Stock levels not updating

**Solution:**
- Verify product codes match exactly (case-sensitive)
- Check that products exist in NPF system
- Confirm API user has permission to query inventory
- Check for API rate limiting

#### Issue: "No contents" error for stock query

**Solution:**
- Verify product codes are correct
- Check that products are set up in NPF system
- Try querying fewer products at once
- Review raw XML response for specific error messages

#### Issue: Address validation failures

**Solution:**
- Ensure country is set to full name ('Australia' not 'AU')
- Provide complete address including state and postcode
- Include contact telephone number
- Verify address format matches NPF requirements

### Debug Mode

Use debug mode to inspect XML without submitting:

```php
$result = npfServeOrder($auth, $orders, 1); // Debug mode ON

// Inspect XML
echo $result['raw_request'][$orderNumber];
```

### Logging

Enable comprehensive logging:

```php
function logNPFCall($function, $params, $result) {
    $logEntry = array(
        'timestamp' => date('c'),
        'function' => $function,
        'params' => $params,
        'result' => $result
    );

    file_put_contents(
        '/var/log/npf_api.log',
        json_encode($logEntry) . "\n",
        FILE_APPEND
    );
}

// Usage
$result = npfServeOrder($auth, $orders);
logNPFCall('npfServeOrder', $orders, $result);
```

### Support Resources

**NPFulfilment Support:**
- Phone: 1300 882 318 (Australia)
- Email: customercare@npfulfilment.com.au
- Website: https://npfulfilment.com

**API Documentation:**
- Contact NPF support for official API documentation
- Request access to API testing environment

---

## Version History

### Version 2.0.0 (2026-01-07)
- Complete PHPDoc documentation for all functions
- Fixed typo in `npfStockOnHand()` function
- Improved code formatting and consistency
- Enhanced error handling
- Production-ready release
- Comprehensive documentation created

### Version 1.0.0 (2022-12-05)
- Initial release
- SOAP/WSDL integration
- Order submission, status, and inventory functions
- Test functions included

---

## License

MIT License - See LICENSE file for details

## Support

For issues with this integration library:
- Review this documentation
- Check function PHPDocs in source code
- Contact WaSQL support

For NPFulfilment API issues:
- Contact NPFulfilment support directly
- Phone: 1300 882 318
- Email: customercare@npfulfilment.com.au
