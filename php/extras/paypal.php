<?php
/* 
	Paypal API functions
	References:
		https://developer.paypal.com/docs/api/payments.payouts-batch/v1/


*/
function paypalSecret(){
	global $paypal_Secret;
	global $CONFIG;
	if(!strlen($paypal_Secret)){
		if(isset($CONFIG['paypal_secret'])){
			$paypal_Secret=$CONFIG['paypal_secret'];
			return $paypal_Secret;
		}
		echo "Error: set \$paypal_Secret as a global variable in your code or set paypal_secret in config.xml";
		exit;
	}
	return $paypal_Secret;
}
function paypapClientId(){
	global $paypal_ClientId;
	global $CONFIG;
	if(!strlen($paypal_ClientId)){
		if(isset($CONFIG['paypal_clientid'])){
			$paypal_ClientId=$CONFIG['paypal_clientid'];
			return $paypal_ClientId;
		}
		echo "Error: set \$paypal_ClientId as a global variable in your code or set paypal_clientid in config.xml";
		exit;
	}
	return $paypal_ClientId;
}
function paypalUrl(){
	if(isDBStage()){
		return 'https://api.sandbox.paypal.com';
	}
	return 'https://api.paypal.com';
}
function paypalSendInvoice($params=array()){
	if(!isset($params['from'],$params['to'],$params['items'][0])){
		return "Error: Missing required params.";
	}
	if(!isset($params['invoice_number'])){$params['invoice_number']=paypalNextInvoiceNumber();}
	if(!isset($params['description'])){$params['description']='Services Rendered';}
	if(!isset($params['invoice_date'])){$params['invoice_date']=date('Y-m-d');}
	if(!isset($params['currency_code'])){$params['currency_code']='USD';}
	if(!isset($params['note'])){$params['note']='Billed services to date';}
	if(!isset($params['payment_term'])){$params['payment_term']='DUE_ON_RECEIPT';}
	if(!isset($params['payment_due'])){$params['payment_due']=date('Y-m-d');}
	$invoice=array(
		'detail'=>array(
			'invoice_number'	=> $params['invoice_number'],
			'description'		=> $params['description'],
			'invoice_date'		=> $params['invoice_date'],
			'currency_code'		=> $params['currency_code'],
			'note'				=> $params['note'],
			'payment_term'=>array(
				'term_type'		=> $params['payment_term'],
				'due_date'		=> $params['payment_due']
			),
		),
		'invoicer'=>array(
			'name'=>array(
				'given_name'	=> $params['from']['firstname'],
				'surname'		=> $params['from']['lastname']
			),
			'address'=>array(
				'address_line_1'=> $params['from']['address_1'],
				'admin_area_2'	=> $params['from']['city'],
				'admin_area_1'	=> $params['from']['state'],
				'postal_code'	=> $params['from']['postal_code'],
				'country_code'	=> $params['from']['country_code']
			),
			'email_address'		=> $params['from']['email'],
			'website'			=> $params['from']['website']
		),
		'primary_recipients'=>array(),
		'items'=>array(),
		'configuration'=>array(
			'partial_payment'=>array(
				'allow_partial_payment'=>isset($params['allow_partial_payment']?$params['allow_partial_payment']:false
			),
			'allow_tip'=>isset($params['allow_tip']?$params['allow_tip']:true,
			'tax_calculated_after_discount'=>isset($params['tax_calculated_after_discount']?$params['tax_calculated_after_discount']:true,
			'tax_inclusive'=>isset($params['tax_inclusive']?$params['tax_inclusive']:false,
		)
	);
	//recipeints
	if(!is_array($params['to']) && isEmail($params['to'])){
		$invoice['primary_recipients'][]=array('email_address'=>$params['to']);
	}
	elseif(is_array($params['to'])){
		$invoice['primary_recipients']=$params['to'];
	}
	//template_id?
	if(isset($params['template_id'])){
		$invoice['configuration']['template_id']=$params['template_id'];
	}
	foreach($params['items'] as $item){
		if(!isset($item['unit_of_measure'])){$item['unit_of_measure']='QUANTITY';}
		if(!isset($item['amount_currency'])){$item['amount_currency']='USD';}
		$invoice['items'][]=array(
			'name'			=> $item['name'],
			'description'	=> $item['description'],
			'quantity'		=> $item['quantity'],
			'unit_amount'=>array(
				'value'		=> $item['amount_value'],
				'currency'	=> $item['amount_currency']
			),
			'item_date'		=> $item['item_date'],
			'unit_of_measure'=>$item['unit_of_measure']
		);
	}
	$url=paypalUrl().'/v2/invoicing/invoices';
	$json=json_encode($invoice);
	//echo $json;exit;
	$token=paypalGetAccessToken();
	if(strlen($token)){
		$post=postJSON($url,$json,array(
			'-method'=>'POST',
			'-json'=>1,
			'-headers'=>array("Authorization: Bearer {$token}")
		));
		if(isset($post['json_array'])){
			return $post['json_array'];
		}
		return "ERROR: ". printValue($post);
	}
	return "Error: no token";
}
//---------- begin function paypalSendPayout
/**
* @describe sends a paypal/venmo payment to recipient
* @param params array
*	sender_batch_id
*	email_subject
*	email_message
*	items - array with the following attributes
*		recipient_type
*		amount_value
*		sender_item_id
*		receiver
*		[amount_currency]
*	[-form{class|style|id|...}] string - sets specified attribute on the form
*	[-filters] array or string - filter sets of field-oper-value in an array or comma separated. i.e. name-ct-bob
*	[-limit] integer - number of records to show
*	[-offset] integer - number to start with - defaults to 0
*	[-total] integer - number of total records - required to show pagination buttons
*	['-formname'] - formname. defaults to searchfiltersform
* @return array - array of results
* @usage
*	$result=paypalSendPayout($params);
*/
function paypalSendPayout($params=array()){
	//check for required fields
	if(!isset($params['sender_batch_id'],$params['email_subject'],$params['email_message'],$params['items'][0])){
		return "Error: Missing required params.";
	}
	$payout=array(
		'sender_batch_header'=>array(
			'sender_batch_id'=>$params['sender_batch_id'],
			'email_subject'=>$params['email_subject'],
			'email_message'=>$params['email_message']
		),
		'items'=>array()
	);
	foreach($params['items'] as $item){
		//check for required item fields
		if(!isset($item['recipient_type'],$item['amount_value'],$item['sender_item_id'],$item['receiver'])){
			continue;
		}
		//default currency to USD
		if(!isset($item['amount_currency'])){$item['amount_currency']='USD';}
		$payout['items'][]=array(
			'recipient_type'=>$item['recipient_type'],
			'amount'=>array(
				'value'		=> $item['amount_value'],
				'currency'	=> $item['amount_currency']
			),
			'note'			=> $item['note'],
			'sender_item_id'=> $item['sender_item_id'],
			'receiver'		=> $item['receiver'],
		);
	}
	$url=paypalUrl().'/v1/payments/payouts';
	$json=json_encode($invoice);
	//echo $json;exit;
	$token=paypalGetAccessToken();
	if(strlen($token)){
		$post=postJSON($url,$json,array(
			'-method'=>'POST',
			'-json'=>1,
			'-headers'=>array("Authorization: Bearer {$token}")
		));
		if(isset($post['json_array'])){
			return $post['json_array'];
		}
		return "ERROR: ". printValue($post);
	}
	return "Error: no token";
}
function paypalNextInvoiceNumber(){
	$url=paypalUrl().'/v2/invoicing/generate-next-invoice-number';
	//echo $json;exit;
	$token=paypalGetAccessToken();
	if(strlen($token)){
		$post=postJSON($url,$json,array(
			'-method'=>'GET',
			'-json'=>1,
			'-headers'=>array("Accept: application/json","Accept-Language: en_US","Authorization: Bearer {$token}")
		));
		if(isset($post['json_array']['invoice_number'])){
			return $post['json_array']['invoice_number'];
		}
	}
	return date('Ymd').rand(1,100);
}

function paypalGetAccessToken(){
	/*
		curl -v https://api.sandbox.paypal.com/v1/oauth2/token \
		   -H "Accept: application/json" \
		   -H "Accept-Language: en_US" \
		   -u "client_id:secret" \
		   -d "grant_type=client_credentials"
	*/
	$url=paypalUrl().'/v1/oauth2/token';
	$ch = curl_init();
	$clientId = paypapClientId();
	$secret = paypalSecret();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_USERPWD, $clientId.":".$secret);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

	$result = curl_exec($ch);
	curl_close($ch);
	if(empty($result)){return '';}
	else{
	    $json = json_decode($result,true);
	    return $json['access_token'];
	}
	return '';
}
?>