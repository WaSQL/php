<?php
/* References:
	https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_api_reference
	https://www.x.com/devzone/articles/how-use-paypal-invoicing-apis-php
Examples are offered in the following functions:
	loadExtras('paypal');
	paypalTestSearch();
	paypalTestCreateAndSendInvoice();


*/
//load the common functions library
$progpath=dirname(__FILE__);
//require_once("{$progpath}/common.php");
require_once("{$progpath}/../../XML2Array.php");
require_once("{$progpath}/paypal/class.invoice.php");
//set timezone
date_default_timezone_set('America/Denver');
function paypalTestSearch(){
	//setup a PPInvoice Object
	$ppInv = new PaypalInvoiceAPI(array(
			'mode'				=> 'live',
			'application_id'	=> '',
			'api_username'		=> '',
			'api_password'		=> '',
			'api_signature'		=> '',
			'return_format'		=> 'JSON'
		));
	$search=array(
		'language' 			=> "en_US",
    	'merchant_email'	=> '',
		'email'	=> '',
		'page'	=> 1,
		'page_size'	=> 100
	);
	$response = $ppInv->doSearchInvoice($search);
    if($response['responseEnvelope']['ack']== "Success"){
        echo printValue($response);
    }
    else{
         echo printValue($response);
    }
    return $response;
}
function paypalTestCreateAndSendInvoice(){
	//doCreateAndSendInvoice
	//build the invoice
	$ppInv = new PaypalInvoiceAPI(array(
			'mode'				=> 'live',
			'application_id'	=> '',
			'api_username'		=> '',
			'api_password'		=> '',
			'api_signature'		=> '',
			'return_format'		=> 'JSON'
		));
	$invoice=array(
    	'language' 			=> "en_US",
    	'merchant_email'	=> '',
    	'payer_email'		=> '',
    	'currency_code'	 	=> "USD",
		'order_id'			=> "PPTEST-03",
    	'payment_terms' 	=> "DueOnReceipt",   //[DueOnReceipt, DueOnDateSpecified, Net10, Net15, Net30, Net45]
    	'logo_url' 			=> "",
		'lineitems'				=> array()
	);
	//first line item
	$lineitem =	array(
		'name'		=> "Development Services",
		'description'=>"Developed a new PayPal API",
		'date'		=> "2012-09-15 05:38:48",
		'quantity'	=> 	2,
		'unitprice'	=>	125.00,
		'tax_name'	=> "Sales Tax",
		'tax_rate'	=> 6.75				//as a percentage. 6.5 equals 6.5%
	);
	$invoice['lineitems'][]=$lineitem;
	//second line item
	$lineitem =	array(
		'name'		=> "Development Hours",
		'description'=>"Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API Developed a new PayPal API ",
		'date'		=> "2012-09-15 05:38:48",
		'quantity'	=> 	2,
		'unitprice'	=>	125.00,
		'tax_name'	=> "Sales Tax",
		'tax_rate'	=> 6.75				//as a percentage. 6.5 equals 6.5%
	);
	$invoice['lineitems'][]=$lineitem;

    $response = $ppInv->doCreateAndSendInvoice($invoice);
    if($response['responseEnvelope']['ack']== "Success"){
        echo printValue($response);
    }
    else{
         echo printValue($response);
    }
    return $response;
}
?>