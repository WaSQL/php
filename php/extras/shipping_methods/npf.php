<?php
/**
 * NPFulfilment (National Products Fulfilment) API Integration Library
 *
 * This library provides PHP wrapper functions for interacting with NPFulfilment's
 * SOAP/WSDL-based API services. NPFulfilment is an order fulfillment and logistics
 * service provider operating in Australia and New Zealand.
 *
 * API Endpoints:
 * - Order Submission: https://npfulfilmentapi.com/serverorder.php?wsdl
 * - Order Status: https://npfulfilmentapi.com/npforderstatus.php?wsdl
 * - Stock on Hand: https://npfulfilmentapi.com/npfsoh.php?wsdl
 *
 * @package    WaSQL
 * @subpackage ShippingMethods
 * @author     WaSQL Development Team
 * @copyright  2022-2026 WaSQL
 * @license    MIT
 * @version    2.0.0
 * @link       https://github.com/WaSQL/php
 *
 * Requirements:
 * - PHP 5.6 or higher
 * - NuSOAP library (included in npf/lib/nusoap.php)
 * - WaSQL framework functions (xml2Array, xmlEncode, etc.)
 *
 * Authentication:
 * All API functions require an authentication array with the following keys:
 * - username: NPFulfilment API username
 * - password: NPFulfilment API password
 * - clientcode: NPFulfilment client code
 *
 * @example Basic usage:
 * <code>
 * $auth = array(
 *     'username' => 'YOUR_USERNAME',
 *     'password' => 'YOUR_PASSWORD',
 *     'clientcode' => 'YOUR_CLIENTCODE'
 * );
 *
 * // Submit an order
 * $result = npfServeOrder($auth, $orders);
 *
 * // Check order status
 * $status = npfOrderStatus($auth, array('ORDER123', 'ORDER124'));
 *
 * // Get stock levels
 * $stock = npfStockOnHand($auth, array('SKU001', 'SKU002'));
 * </code>
 */

$progpath = dirname(__FILE__);
// Load the NuSOAP library for SOAP/WSDL communication
require_once("{$progpath}/npf/lib/nusoap.php");
/**
 * Test function for NPF Order Status API
 *
 * This function demonstrates how to check the status of multiple orders using the
 * NPFulfilment Order Status API. It uses test credentials and order numbers.
 *
 * WARNING: This function contains test credentials and should only be used for
 * development and testing purposes. Replace credentials with production values
 * before deploying to production environments.
 *
 * @return array Returns the order status response from NPFulfilment API containing:
 *               - 'raw_request': The XML request sent to the API
 *               - 'raw_response': The raw XML response from the API
 *               - 'orders': Parsed array of order status information
 *
 * @see npfOrderStatus() for detailed response structure
 *
 * @example
 * <code>
 * $result = npfTestOrderStatus();
 * print_r($result);
 * </code>
 */
function npfTestOrderStatus() {
	// Test credentials - DO NOT use in production
	$auth = array(
		'username'   => 'APINPFON',
		'password'   => 'APINPFON212',
		'clientcode' => 'APINPFON'
	);

	// Sample order numbers for testing
	$salesOrderNumbers = array(
		'INT1235',
		'INT1234',
		'TST1236'
	);

	$result = npfOrderStatus($auth, $salesOrderNumbers);
	echo printValue($result);
	return $result;
}
/**
 * Test function for NPF Order Submission API
 *
 * This function demonstrates how to submit orders to NPFulfilment using their
 * serverorder API. It creates a complete test order with billing, shipping,
 * and line item details.
 *
 * WARNING: This function contains test credentials and sample data. It should
 * only be used for development and testing purposes. Replace with production
 * credentials and real order data before deploying to production.
 *
 * The test order includes:
 * - Billing and shipping addresses in Australia
 * - Shipping method and carrier information
 * - Multiple line items with products
 * - Order totals and tax calculations
 *
 * @return array Returns the order submission response from NPFulfilment API
 *
 * @see npfServeOrder() for detailed parameters and response structure
 *
 * @example
 * <code>
 * $result = npfTestServeOrder();
 * print_r($result);
 * </code>
 */
function npfTestServeOrder() {
	// Test credentials - DO NOT use in production
	$auth = array(
		'username'   => 'APINPFON',
		'password'   => 'APINPFON212',
		'clientcode' => 'APINPFON'
	);

	// Sample order structure for testing
	$orders = array(
		'custid'	=> "TESTID",
		'orders'	=> array(
			array(
				'site_id'		=> 1,
				'ordernumber'	=> "TST1236",
				'custid'		=> "TESTID",
	            'createdate' 	=> "20120907",
	            'requiredshipdate' => "20120907",
	            'orderfilled' 	=> "O",
	            'carrier_service_code' => "USS 01",
	            'shipmethod'	=> "AP",
	            'carrier' 		=> "FDX",
	            'billtoname' 	=> "John Doe Company",
	            'billtocontact' => "John Doe",
	            'billtoaddress1'=> "35 Warrawee Street",
	            'billtocity' 	=> "Sapphire Beach",
	            'billtostate' 	=> "NSW",
	            'billtozipcode' => "2450",
	            'billtocountry' => "Australia",
	            'billtotelephone' => "2343234343",
	            'shiptoname' 	=> "John Doe Company",
	            'shiptocontact' => "John Doe",
	            'shiptoaddress1'=> "35 Warrawee Street",
	            'shiptocity' 	=> "Sapphire Beach",
	            'shiptostate' 	=> "NSW",
	            'shiptozipcode' => "2450",
	            'shiptocountry' => "Australia",
	            'shiptotelephone' => "2343234343",
	            'freightcode' 	=> 3,
	            'allow_short_ship' => "N",
	            'packlist_required' => "T",
	            'drop_ship' 	=> "R",
	            'customer_email_to' => "johndoe@hotmail.com",
	            'residential_flag' => "T",
	            'value'				=> 10.00,
	            'description'		=> "personal accessories",
	            'items' => array(
					array(
						'site_id'		=> 1,
						'ordernumber'	=> "INT1235",
		                'linenumber' 	=> 1,
		                'itemid' 		=> "001215",
		                'description'	=> "Nude By Night Foundation Medium 50ml",
		                'qtyordered' 	=> 3,
		                'retail_price'	=> 5.54
					),
					array(
						'site_id'		=> 1,
						'ordernumber'	=> "INT1235",
		                'linenumber' 	=> 2,
		                'itemid' 		=> "01-250",
		                'description'	=> "Goggles 7550",
		                'qtyordered' 	=> 1,
		                'retail_price'	=> 8.44
					)
				)
			)
		)
	);
	$test = npfServeOrder($auth, $orders);
	echo printValue($auth) . printValue($test);
}

/**
 * Generate XML authentication block for NPFulfilment API requests
 *
 * This function creates the XML authentication section required by all NPFulfilment
 * API calls. The authentication block contains the username, password, and client
 * code needed to authenticate with NPFulfilment's SOAP services.
 *
 * @param array $auth Authentication credentials array containing:
 *                    - 'username' (string) NPFulfilment API username
 *                    - 'password' (string) NPFulfilment API password
 *                    - 'clientcode' (string) NPFulfilment client code
 *
 * @return string XML authentication block formatted for NPFulfilment API requests
 *
 * @example
 * <code>
 * $auth = array(
 *     'username' => 'myusername',
 *     'password' => 'mypassword',
 *     'clientcode' => 'myclientcode'
 * );
 * $authXML = npfAuthXML($auth);
 * // Returns:
 * // <Login>
 * //     <Username>myusername</Username>
 * //     <Password>mypassword</Password>
 * //     <ClientCode>myclientcode</ClientCode>
 * // </Login>
 * </code>
 */
function npfAuthXML($auth) {
	return <<<ENDOFAUTH
	<Login>
		<Username>{$auth['username']}</Username>
    	<Password>{$auth['password']}</Password>
		<ClientCode>{$auth['clientcode']}</ClientCode>
	</Login>
ENDOFAUTH;
}

/**
 * Submit orders to NPFulfilment for fulfillment
 *
 * This function submits one or more orders to NPFulfilment's order processing system.
 * Each order is submitted individually as NPF can only process one order per API request.
 * The function builds XML requests containing order details, customer information,
 * line items, and calculates totals with GST.
 *
 * Order Structure Requirements:
 * - Each order must contain complete billing and shipping address information
 * - Line items must include product codes, descriptions, quantities, and pricing
 * - Country codes 'AU' and 'NZ' are automatically expanded to full country names
 * - If billing address is not provided, shipping address will be used
 *
 * @param array $auth Authentication credentials array containing:
 *                    - 'username' (string) NPFulfilment API username
 *                    - 'password' (string) NPFulfilment API password
 *                    - 'clientcode' (string) NPFulfilment client code
 *
 * @param array $orders Orders data structure containing:
 *                      - 'custid' (string) Customer identifier
 *                      - 'orders' (array) Array of order objects, each containing:
 *                        * 'ordernumber' (string) Unique order reference number
 *                        * 'shiptoname' (string) Recipient company name
 *                        * 'shiptocontact' (string) Recipient contact person (full name)
 *                        * 'shiptoaddress1' (string) Shipping address line 1
 *                        * 'shiptoaddress2' (string) Shipping address line 2 (optional)
 *                        * 'shiptocity' (string) Shipping city
 *                        * 'shiptostate' (string) Shipping state/province
 *                        * 'shiptozipcode' (string) Shipping postal code
 *                        * 'shiptocountry' (string) Shipping country (use 'AU' or 'Australia')
 *                        * 'billtoname' (string) Billing company name (optional, defaults to shipto)
 *                        * 'billtocontact' (string) Billing contact person (optional, defaults to shipto)
 *                        * 'billtoaddress1' (string) Billing address line 1 (optional)
 *                        * 'billtocity' (string) Billing city (optional)
 *                        * 'billtostate' (string) Billing state (optional)
 *                        * 'billtozipcode' (string) Billing postal code (optional)
 *                        * 'billtocountry' (string) Billing country (optional)
 *                        * 'billtotelephone' (string) Billing phone (optional)
 *                        * 'billtoemail' (string) Billing email (optional)
 *                        * 'shipmethod' (string) Dispatch method code (e.g., 'AP')
 *                        * 'giftwrap' (string) Gift wrapping required (Y/N)
 *                        * 'customer_message' (string) Customer message (max 200 chars)
 *                        * 'value' (float) Order value (auto-calculated if not provided)
 *                        * 'description' (string) Article description for customs
 *                        * 'url' (string) Website code
 *                        * 'items' (array) Array of line items, each containing:
 *                          - 'itemid' (string) Product/SKU code
 *                          - 'description' (string) Product description
 *                          - 'quantity' or 'qtyordered' (int) Quantity to ship
 *                          - 'price' or 'retail_price' (float) Unit price
 *                          - 'upc' (string) Barcode (optional, defaults to itemid)
 *                          - 'gst' (float) GST amount (auto-calculated if not provided)
 *                          - 'gstprice' (float) Price including GST (auto-calculated)
 *                          - 'amount' (float) Line total (auto-calculated)
 *
 * @param int $debug Optional debug mode. Set to 1 to return XML without sending to API,
 *                   useful for testing and debugging. Default: 0
 *
 * @return array Response array with results for each order, containing:
 *               - 'raw_request' (array) XML requests sent, keyed by order number
 *               - 'raw' (array) Raw XML responses, keyed by order number
 *               - [ordernumber] (array|string) Parsed response for each order, or error string
 *
 * @example Basic order submission:
 * <code>
 * $auth = array(
 *     'username' => 'myusername',
 *     'password' => 'mypassword',
 *     'clientcode' => 'myclientcode'
 * );
 *
 * $orders = array(
 *     'custid' => 'CUST123',
 *     'orders' => array(
 *         array(
 *             'ordernumber' => 'ORD001',
 *             'shiptoname' => 'Acme Corp',
 *             'shiptocontact' => 'John Smith',
 *             'shiptoaddress1' => '123 Main St',
 *             'shiptocity' => 'Sydney',
 *             'shiptostate' => 'NSW',
 *             'shiptozipcode' => '2000',
 *             'shiptocountry' => 'AU',
 *             'shipmethod' => 'AP',
 *             'description' => 'office supplies',
 *             'items' => array(
 *                 array(
 *                     'itemid' => 'SKU001',
 *                     'description' => 'Widget',
 *                     'quantity' => 5,
 *                     'price' => 19.99
 *                 )
 *             )
 *         )
 *     )
 * );
 *
 * $result = npfServeOrder($auth, $orders);
 * if (is_array($result['ORD001'])) {
 *     echo "Order submitted successfully";
 * } else {
 *     echo "Error: " . $result['ORD001'];
 * }
 * </code>
 *
 * @see npfTestServeOrder() for a complete working example
 * @see npfOrderStatus() to check status of submitted orders
 */
function npfServeOrder($auth, $orders, $debug = 0) {
	$url = 'https://npfulfilmentapi.com/serverorder.php?wsdl';
	// NPF can only accept one order per request
	$responses = array();
	foreach($orders['orders'] as $order){
		$ordernumber=$order['ordernumber'];
  		$xml=xmlHeader();
		$xml .= '<OrderList>'."\n";
		$xml .= '	'.trim(npfAuthXML($auth))."\n";
		//default billto to shipto if not set
		if(strtoupper($order['shiptocountry'])=='AU'){
        	$order['shiptocountry']="Australia";
		}
		elseif(strtoupper($order['shiptocountry'])=='NZ'){
        	$order['shiptocountry']="New Zealand";
		}
		$billtofields=array('contact','name','address1','address2','city','state','zipcode','country','telephone','email');
		foreach($billtofields as $field){
			if(!isset($order["billto{$field}"])){$order["billto{$field}"]=$order["shipto{$field}"];}
		}
		if(!strlen($order['billtotelephone'])){$order['billtotelephone']='na';}
		list($billtofirstname,$billtolastname)=preg_split('/\ /',$order['billtocontact'],2);
		list($shiptofirstname,$shiptolastname)=preg_split('/\ /',$order['shiptocontact'],2);
		$xml .= '	<Order>'."\n";
		$xml .= '		<SalesOrderNo>'.$ordernumber.'</SalesOrderNo>'."\n";
		$xml .= '		<OrderDate>01-Jun-2012</OrderDate>'."\n";
	    $xml .= '		<CustomerNo>C001</CustomerNo>'."\n";
	    $xml .= '		<BillingAddress>'."\n";
	    $xml .= '			<FirstName>'.xmlEncode($billtofirstname).'</FirstName>'."\n";
	    $xml .= '			<LastName>'.xmlEncode($billtolastname).'</LastName>'."\n";
	    $xml .= '			<CompanyName>'.xmlEncode($order['billtoname']).'</CompanyName>'."\n";
	    $xml .= '			<Address1>'.xmlEncode($order['billtoaddress1']).'</Address1>'."\n";
	    $xml .= '			<Address2>'.xmlEncode($order['billtoaddress1']).'</Address2>'."\n";
	    $xml .= '			<City>'.xmlEncode($order['billtocity']).'</City>'."\n";
	    $xml .= '			<State>'.xmlEncode($order['billtostate']).'</State>'."\n";
	    $xml .= '			<PostCode>'.$order['billtozipcode'].'</PostCode>'."\n";
	    $xml .= '			<Country>'.$order['billtocountry'].'</Country>'."\n";
	    $xml .= '			<Phone>'.$order['billtotelephone'].'</Phone>'."\n";
	    $xml .= '			<Email>'.$order['billtoemail'].'</Email>'."\n";
	    $xml .= '		</BillingAddress>'."\n";
	    $xml .= '		<ShippingAddress>'."\n";
	    $xml .= '			<FirstName>'.xmlEncode($shiptofirstname).'</FirstName>'."\n";
	    $xml .= '			<LastName>'.xmlEncode($shiptolastname).'</LastName>'."\n";
	    $xml .= '			<CompanyName>'.xmlEncode($order['shiptoname']).'</CompanyName>'."\n";
	    $xml .= '			<Address1>'.xmlEncode($order['shiptoaddress1']).'</Address1>'."\n";
	    $xml .= '			<Address2>'.xmlEncode($order['shiptoaddress2']).'</Address2>'."\n";
	    $xml .= '			<City>'.xmlEncode($order['shiptocity']).'</City>'."\n";
	    $xml .= '			<State>'.xmlEncode($order['shiptostate']).'</State>'."\n";
	    $xml .= '			<PostCode>'.$order['shiptozipcode'].'</PostCode>'."\n";
	    $xml .= '			<Country>'.$order['shiptocountry'].'</Country>'."\n";
	    $xml .= '		</ShippingAddress>'."\n";
	    $xml .= '		<DespatchDetails>'."\n";
	    $xml .= '			<DispatchMethod>'.$order['shipmethod'].'</DispatchMethod>'."\n";
	    $xml .= '			<GiftWrappingRequired>'.$order['giftwrap'].'</GiftWrappingRequired>'."\n";
	    $message=xmlEncodeCDATA($order['customer_message']);
	    $message=str_replace('&amp;#039;',"'",$message);
	    //customer message is limited to 200 chars
	    $message=substr($message,0,200);
	    $xml .= '			<CustomerMessage>'.$message.'</CustomerMessage>'."\n";
	    //order value
	    if(!isset($order['value'])){
			$order['value']=0;
        	foreach($order['items'] as $item){
				if(!isset($item['quantity'])){$item['quantity']=$item['qtyordered'];}
				if(!isset($item['price'])){$item['price']=$item['retail_price'];}
				$subtotal=$item['quantity']*$item['price'];
				$order['value']+=$subtotal;
			}
			$order['value']=round($order['value'],2);
		}
	    $xml .= '			<ArticleValue>'.$order['value'].'</ArticleValue>'."\n";
	    $xml .= '			<ArticleDescription>'.xmlEncodeCDATA($order['description']).'</ArticleDescription>'."\n";
	    $xml .= '			<WebSiteCode>'.$order['url'].'</WebSiteCode>'."\n";
	    $xml .= '		</DespatchDetails>'."\n";
	    $xml .= '		<Products>'."\n";
	    $subtotal1=0;
	    foreach($order['items'] as $item){
			if(!isset($item['quantity'])){$item['quantity']=$item['qtyordered'];}
			if(!isset($item['price'])){$item['price']=$item['retail_price'];}
			if(!isset($item['gst'])){$item['gst']=round(($item['price']*.1),2);}
			if(!isset($item['gstprice'])){$item['gstprice']=$item['price']+$item['gst'];}
			if(!isset($item['amount'])){$item['amount']=$item['gstprice']*$item['quantity'];}
			if(!isset($item['upc'])){$item['upc']=$item['itemid'];}
			if(!strlen($item['price'])){$item['price']=0.00;}
			$subtotal1+=$item['amount'];
		    $xml .= '			<Product>'."\n";
		    $xml .= '				<Code>'.$item['itemid'].'</Code>'."\n";
		    $xml .= '				<Description>'.xmlEncodeCDATA($item['description']).'</Description>'."\n";
		    $xml .= '				<BarCode>'.$item['upc'].'</BarCode>'."\n";
		    $xml .= '				<Quantity>'.$item['quantity'].'</Quantity>'."\n";
		    $xml .= '				<SalesPrice>'.$item['price'].'</SalesPrice>'."\n";
		    $xml .= '				<GST>'.$item['gst'].'</GST>'."\n";
		    $xml .= '				<SalesPricesIncludesGST>'.$item['gstprice'].'</SalesPricesIncludesGST>'."\n";
		    $amount=$item['quantity']*$item['price'];
		    $xml .= '				<Amount>'.$item['amount'].'</Amount>'."\n";
		    $xml .= '			</Product>'."\n";
		}
		if(!isset($order['subtotal1'])){$order['subtotal1']=$subtotal1;}
		if(!isset($order['ph_cost'])){$order['ph_cost']=0.00;}
		if(!isset($order['ph_gst'])){$order['ph_gst']=round(($order['ph_cost']*.1),2);}
		if(!isset($order['ph_gstcost'])){$order['ph_gstcost']=$order['ph_cost']+$order['ph_gst'];}
		if(!isset($order['subtotal2'])){$order['subtotal2']=$order['subtotal1']+$order['ph_gstcost'];}
		if(!isset($order['discount_pcnt'])){$order['discount_pcnt']=0;}
		if($order['discount_pcnt'] > 1){$order['discount_pcnt']=round($order['discount_pcnt']/100,2);}
		if(!isset($order['discount_amt'])){round(($order['subtotal2']*$order['discount_pcnt']),2);}
		if(!isset($order['total'])){$order['total']=$order['subtotal2']-$order['discount_amt'];}
	    $xml .= '		</Products>'."\n";
	    $xml .= '		<Totals>'."\n";
	    $xml .= '			<SubTotal1>'.$order['subtotal1'].'</SubTotal1>'."\n";
	    $xml .= '			<P_H_Cost>'.$order['ph_cost'].'</P_H_Cost>'."\n";
	    $xml .= '			<P_H_GST>'.$order['ph_gst'].'</P_H_GST>'."\n";
	    $xml .= '			<P_H_IncludesGST>'.$order['ph_gstcost'].'</P_H_IncludesGST>'."\n";
	    $xml .= '			<SubTotal2>'.$order['subtotal2'].'</SubTotal2>'."\n";
	    $xml .= '			<DiscountPercentage>'.$order['discount_pcnt'].'</DiscountPercentage>'."\n";
	    if(!isset($order['discount_amt'])){$order['discount_amt']=0.00;}
	    $xml .= '			<DiscountAmount>'.$order['discount_amt'].'</DiscountAmount>'."\n";
	    $xml .= '			<Total>'.$order['total'].'</Total>'."\n";
	    $xml .= '		</Totals>'."\n";
	  	$xml .= '	</Order>'."\n";
    	$xml .= '</OrderList>'."\n";
    	$responses['raw_request'][$ordernumber]=$xml;
	  	if($debug==1){$responses[$ordernumber]=$xml;}
	  	else{
			$response=npfPostXML($url,'serverorder',$xml);
			if(stringBeginsWith($response,"ERROR")){
				$responses[$ordernumber] = $response;
			}
			else{
				$responses[$ordernumber]=xmL2Array($response);
			}
			$responses['raw'][$ordernumber]=$response;
		}
	}
	return $responses;
}

/**
 * Retrieve order status information from NPFulfilment
 *
 * This function queries the NPFulfilment API to get the current status of one or more
 * orders. It returns tracking information, shipping status, and fulfillment details
 * for the specified order numbers.
 *
 * The function can handle multiple order numbers in a single request and will parse
 * the XML response into a structured array format.
 *
 * @param array $auth Authentication credentials array containing:
 *                    - 'username' (string) NPFulfilment API username
 *                    - 'password' (string) NPFulfilment API password
 *                    - 'clientcode' (string) NPFulfilment client code
 *
 * @param array $salesOrderNumbers Array of order numbers (strings) to query status for.
 *                                  Must contain at least one order number.
 *                                  Example: array('ORD001', 'ORD002', 'ORD003')
 *
 * @return array|string|null Response array containing order status information:
 *                           - 'raw_request' (string) The XML request sent to API
 *                           - 'raw_response' (string) The raw XML response from API
 *                           - 'orders' (array) Array of order status objects, each containing:
 *                             * 'ClientCode' (string) Client identifier
 *                             * 'SalesOrderNo' (string) Order number
 *                             * 'OrderStatus' (string) Current order status
 *                             * 'TrackingNumber' (string) Shipping tracking number (if available)
 *                             * 'Carrier' (string) Shipping carrier name
 *                             * Additional fulfillment-specific fields
 *
 *                           Returns null if $salesOrderNumbers array is empty.
 *                           Returns error string if API call fails (starts with "ERROR").
 *
 * @example Single order status check:
 * <code>
 * $auth = array(
 *     'username' => 'myusername',
 *     'password' => 'mypassword',
 *     'clientcode' => 'myclientcode'
 * );
 *
 * $result = npfOrderStatus($auth, array('ORD123'));
 * if (is_array($result) && isset($result['orders'])) {
 *     foreach ($result['orders'] as $order) {
 *         echo "Order: " . $order['SalesOrderNo'] . "\n";
 *         echo "Status: " . $order['OrderStatus'] . "\n";
 *         if (isset($order['TrackingNumber'])) {
 *             echo "Tracking: " . $order['TrackingNumber'] . "\n";
 *         }
 *     }
 * }
 * </code>
 *
 * @example Multiple orders status check:
 * <code>
 * $orderNumbers = array('ORD001', 'ORD002', 'ORD003');
 * $result = npfOrderStatus($auth, $orderNumbers);
 * </code>
 *
 * @see npfTestOrderStatus() for a working test example
 * @see npfServeOrder() to submit orders for fulfillment
 */
function npfOrderStatus($auth, $salesOrderNumbers = array()) {
	if (!count($salesOrderNumbers)) {
		return null;
	}
	$url='https://npfulfilmentapi.com/npforderstatus.php?wsdl';
	$xml=xmlHeader();
	$xml .= '<OrderList>'."\n";
	$xml .= '	'.trim(npfAuthXML($auth))."\n";
	$xml .= '	<Order>'."\n";
	$string=implode(",",$salesOrderNumbers);
    $xml .= '		<SalesOrderNo>'.$string.'</SalesOrderNo>'."\n";
  	$xml .= '	</Order>'."\n";
	$xml .= '</OrderList>'."\n";
/*	$method='importorderstatus';
 	$param = array('orders' => $xml);
	$client = new nusoap_client($url);
	$response = $client->call($method,$param);
	echo printValue($param).printValue($client).printValue($response) . $xml;exit; */
	$response=npfPostXML($url,'importorderstatus',$xml);
	if(stringBeginsWith($response,"ERROR")){
		return $response;
	}
	//hack to fix an ampersand to make it valid XML
	$rtn=array();
	$rtn['raw_request']=$xml;
	$rtn['raw_response']=$response;
	$response=str_replace('&type=','&amp;type=',$response);
	$rtn['orders']=xmL2Array($response);
	if(isset($rtn['orders']['OrderList']['Order'])){
		$orders=$rtn['orders']['OrderList']['Order'];
		if(isset($orders['ClientCode'])){$orders=array($orders);}
		$rtn['orders']=$orders;
	}
	return $rtn;
}

/**
 * Retrieve stock on hand (inventory levels) from NPFulfilment
 *
 * This function queries the NPFulfilment API to get current inventory levels for
 * one or more product codes. The function automatically handles large requests by
 * breaking them into groups of 150 products per API call to comply with NPF's
 * API limitations.
 *
 * The function aggregates results from multiple API calls and returns a consolidated
 * response with stock levels for all requested products.
 *
 * @param array $auth Authentication credentials array containing:
 *                    - 'username' (string) NPFulfilment API username
 *                    - 'password' (string) NPFulfilment API password
 *                    - 'clientcode' (string) NPFulfilment client code
 *
 * @param array $productcodes Array of product codes/SKUs (strings) to query inventory for.
 *                             Can contain any number of products; function automatically
 *                             chunks them into groups of 150 for API compliance.
 *                             Example: array('SKU001', 'SKU002', 'PROD-123')
 *
 * @return array Response array containing inventory information:
 *               - 'stock' (array) Associative array mapping product codes to stock quantities
 *                         Format: array('SKU001' => 50, 'SKU002' => 0, 'PROD-123' => 125)
 *               - 'requests' (array) Array of XML requests sent to API (for debugging)
 *               - 'responses' (array) Array of raw XML responses from API (for debugging)
 *               - 'responses_array' (array) Array of parsed response arrays (for debugging)
 *
 * @example Check stock for multiple products:
 * <code>
 * $auth = array(
 *     'username' => 'myusername',
 *     'password' => 'mypassword',
 *     'clientcode' => 'myclientcode'
 * );
 *
 * $productCodes = array('SKU001', 'SKU002', 'PROD-ABC', 'ITEM-XYZ');
 * $result = npfStockOnHand($auth, $productCodes);
 *
 * if (isset($result['stock'])) {
 *     foreach ($result['stock'] as $sku => $quantity) {
 *         echo "$sku: $quantity units in stock\n";
 *     }
 * }
 * </code>
 *
 * @example Check stock for large inventory (automatically chunked):
 * <code>
 * // This will automatically be split into multiple API calls
 * $largeSKUList = array(); // 500 SKUs
 * for ($i = 1; $i <= 500; $i++) {
 *     $largeSKUList[] = "SKU" . str_pad($i, 5, '0', STR_PAD_LEFT);
 * }
 * $result = npfStockOnHand($auth, $largeSKUList);
 * // Result will contain stock levels for all 500 products
 * </code>
 *
 * @note The NPFulfilment API has a limitation on the number of products that can be
 *       queried in a single request. This function automatically handles this by
 *       chunking requests into groups of 150 products.
 *
 * @see npfOrderStatus() to check order fulfillment status
 */
function npfStockOnHand($auth, $productcodes) {
	$url = 'https://npfulfilmentapi.com/npfsoh.php?wsdl';
	// NPF API limitation: break into groups of 150 product codes per request
	$groups = array_chunk($productcodes, 150);
	$result=array('stock'=>array());
	foreach($groups as $group){
		$xml=xmlHeader();
		$xml .= '<ProductList>'."\n";
		$xml .= '	'.trim(npfAuthXML($auth))."\n";
		$xml .= '	<Product>'."\n";
		$string=implode(",",$group);
	    $xml .= '		<ProductCode>'.$string.'</ProductCode>'."\n";
	  	$xml .= '	</Product>'."\n";
		$xml .= '</ProductList>'."\n";
		//echo printValue($params) . $xml;exit;
		$response = npfPostXML($url, 'importsoh', $xml);
		$result['requests'][] = $xml;
		$result['responses'][] = $response;
		// Skip if response is empty or contains error
		if (stringContains($response, 'no contents') || stringBeginsWith($response, "ERROR")) {
			continue;
		}
		//echo $xml.printValue($response);
		$arr=xmL2Array($response);
		$result['responses_array'][]=$arr;
		if(isset($arr['ProductList']['Product']['ProductCode'])){
			$code=$arr['ProductList']['Product']['ProductCode'];
        	$result['stock'][$code]=$arr['ProductList']['Product']['StockonHand'];
		}
		elseif(isset($arr['ProductList']['Product'][0])){
			foreach($arr['ProductList']['Product'] as $product){
				$code=$product['ProductCode'];
	        	$result['stock'][$code]=$product['StockonHand'];
			}
		}
	}
	return $result;
}

/**
 * Post XML data to NPFulfilment SOAP/WSDL endpoint
 *
 * This is a low-level function that handles SOAP communication with NPFulfilment's
 * API endpoints. It uses the NuSOAP library to create a SOAP client, send XML data
 * to the specified endpoint, and handle any SOAP faults that may occur.
 *
 * This function is called internally by all other NPF API functions and should not
 * typically need to be called directly unless implementing custom NPF API operations.
 *
 * @param string $url The WSDL endpoint URL for the NPFulfilment API service.
 *                    Example: 'https://npfulfilmentapi.com/serverorder.php?wsdl'
 *
 * @param string $method The SOAP method name to call.
 *                       Common methods include:
 *                       - 'serverorder' for order submission
 *                       - 'importorderstatus' for order status queries
 *                       - 'importsoh' for stock on hand queries
 *
 * @param string $xml The XML data to send as the request body. Must be properly
 *                    formatted XML matching the NPFulfilment API specification
 *                    for the given method.
 *
 * @return string Returns the response from the SOAP service.
 *                On success: XML string containing the API response
 *                On SOAP fault: Error string in format "ERROR [code]: [message]"
 *
 * @example Custom API call:
 * <code>
 * $url = 'https://npfulfilmentapi.com/serverorder.php?wsdl';
 * $method = 'serverorder';
 * $xml = '<?xml version="1.0" encoding="UTF-8"?>
 *         <OrderList>
 *           <Login>
 *             <Username>test</Username>
 *             <Password>test123</Password>
 *             <ClientCode>TEST</ClientCode>
 *           </Login>
 *           <Order>...</Order>
 *         </OrderList>';
 *
 * $response = npfPostXML($url, $method, $xml);
 * if (strpos($response, 'ERROR') === 0) {
 *     echo "SOAP fault occurred: " . $response;
 * } else {
 *     // Process successful XML response
 *     $data = xml2Array($response);
 * }
 * </code>
 *
 * @see npfServeOrder() uses this to submit orders
 * @see npfOrderStatus() uses this to query order status
 * @see npfStockOnHand() uses this to query inventory levels
 *
 * @internal This is primarily an internal function used by other NPF API wrappers
 */
function npfPostXML($url, $method, $xml) {
	$param = array('orders' => $xml);
	$client = new nusoap_client($url);
	$response = $client->call($method, $param);

	if ($client->fault) {
		return "ERROR " . $client->faultcode . ": " . $client->faultstring;
	} else {
		return $response;
	}
}
?>