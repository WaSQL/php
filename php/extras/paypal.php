<?php
/* 
	Paypal API functions
	References:
		https://developer.paypal.com/docs/api/payments.payouts-batch/v1/
		https://developer.paypal.com/docs/payouts/integrate/api-integration/
		https://developer.paypal.com/developer/applications/
	Instructions
		set paypal_secret, paypal_clientid, and optionally paypal_url in config.xml

Paypal Javascript SDK for payment buttons
https://stackoverflow.com/questions/56414640/paypal-checkout-javascript-with-smart-payment-buttons-create-order-problem
<script src="https://www.paypal.com/sdk/js?client-id=sb"></script>
<script>
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [
                            {
                                reference_id: "PUHF",
                                description: "Sporting Goods",

                                custom_id: "CUST-HighFashions",
                                soft_descriptor: "HighFashions",
                                amount: {
                                    currency_code: "USD",
                                    value: "230.00",
                                    breakdown: {
                                        item_total: {
                                            currency_code: "USD",
                                            value: "180.00"
                                        },
                                        shipping: {
                                            currency_code: "USD",
                                            value: "30.00"
                                        },
                                        handling: {
                                            currency_code: "USD",
                                            value: "10.00"
                                        },
                                        tax_total: {
                                            currency_code: "USD",
                                            value: "20.00"
                                        },
                                        shipping_discount: {
                                            currency_code: "USD",
                                            value: "10"
                                        }
                                    }
                                },
                                items: [
                                    {
                                        name: "T-Shirt",
                                        description: "Green XL",
                                        sku: "sku01",
                                        unit_amount: {
                                            currency_code: "USD",
                                            value: "90.00"
                                        },
                                        tax: {
                                            currency_code: "USD",
                                            value: "10.00"
                                        },
                                        quantity: "1",
                                        category: "PHYSICAL_GOODS"
                                    },
                                    {
                                        name: "Shoes",
                                        description: "Running, Size 10.5",
                                        sku: "sku02",
                                        unit_amount: {
                                            currency_code: "USD",
                                            value: "45.00"
                                        },
                                        tax: {
                                            currency_code: "USD",
                                            value: "5.00"
                                        },
                                        quantity: "2",
                                        category: "PHYSICAL_GOODS"
                                    }
                                ],
                                shipping: {
                                    method: "United States Postal Service",
                                    address: {
                                        name: {
                                            full_name:"John",
                                            surname:"Doe"
                                        },
                                        address_line_1: "123 Townsend St",
                                        address_line_2: "Floor 6",
                                        admin_area_2: "San Francisco",
                                        admin_area_1: "CA",
                                        postal_code: "94107",
                                        country_code: "US"
                                    }
                                }
                            }
                        ]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        alert('Transaction completed by ' + details.payer.name.given_name);
                        // Call your server to save the transaction
                        return fetch('/api/paypal-transaction-complete', {
                            method: 'post',
                            headers: {
                                'content-type': 'application/json'
                            },
                            body: JSON.stringify({
                                orderID: data.orderID
                            })
                        });
                    });
                }
            }).render('#paypal-button-container');
        </script>



*/

function paypalCheckout(){
	global $CONFIG;
	if(!isset($CONFIG['paypal_clientid'])){
		debugValue('paypal_secret not set in config.xml');
		return 'ERROR';
	}
	$purchase=array(
		'purchase_units' => array(
			array(
				'reference_id'=>"PUHF",
                'description'=>"Sporting Goods",
				'custom_id'=>"CUST-HighFashions",
                'soft_descriptor'=>"HighFashions",
                'amount'=>array(
                	'currency_code'=>'USD',
                	'value'=>'0.25',
                	'breakdown'=>array(
                		'item_total'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.10"
                        ),
                        'shipping'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.05"
                        ),
                        'handling'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.05"
                        ),
                        'tax_total'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.05"
                        ),
                        'shipping_discount'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.00"
                        )	
                	)
                ),
                'items'=>array(
                	array(
                		'name'=>"T-Shirt",
                        'description'=>"Green XL",
                        'sku'=>"sku01",
                        'quantity'=>"1",
                        'category'=>"PHYSICAL_GOODS",
                        'unit_amount'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.04"
                        ),
                        'tax'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.01"
                        ), 
                	),
                	array(
                		'name'=>"Shoes",
                        'description'=>"Running, Size 10.5",
                        'sku'=>"sku02",
                        'quantity'=>"2",
                        'category'=>"PHYSICAL_GOODS",
                        'unit_amount'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.03"
                        ),
                        'tax'=>array(
                            'currency_code'=>"USD",
                            'value'=>"0.02"
                        ), 
                	)
                ),
                'shipping'=>array(
                    'method'=>"United States Postal Service",
                    'address'=>array(
                        'name'=>array(
                            'full_name'=>"Steven",
                            'surname'=>"Lloyd"
                        ),
                        'address_line_1'=>"2325 N 600 W",
                        'address_line_2'=>"Attn: Sales",
                        'admin_area_2'=>"Pleasant Grove",
                        'admin_area_1'=>"UT",
                        'postal_code'=>"84062",
                        'country_code'=>"US"
                    )
                )
			)
		)
	);
	$purchase_json=json_encode($purchase,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
	$rtn=<<<ENDOFRTN
	<div id="paypalcheckout_purchase" style="display:none">{$purchase_json}</div>
	<script src="https://www.paypal.com/sdk/js?client-id={$CONFIG['paypal_clientid']}"></script>
<script>
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create(JSON.parse(getText('paypalcheckout_purchase')));
                },
                onApprove: function(data, actions) {
                	console.log('onApprove');
                	console.log(data);
                    return actions.order.capture().then(function(details) {
                    	console.log('capture');
                		console.log(details);
                		let rtn={
                			order_id:details.id,
                			order_cdate:details.create_time,
                			order_edate:details.update_time,
                			order_status:details.status,
                			order_amount:details.purchase_units[0].amount.value,
                			order_ordernumber:details.purchase_units[0].custom_id,
                			payer_id:details.payer.payer_id,
                			payer_email:details.payer.email_address,
                			payer_country_code:details.payer.address.country_code,
                			payer_firstname:details.payer.name.given_name,
                			payer_lastname:details.payer.name.surname
                		};
                        return ajaxGet('/t/1/index/process','nulldiv',rtn);
                    });
                }
            }).render('#paypal-button-container');
        </script>
        <div id="paypal-button-container"></div>
ENDOFRTN;
	return $rtn;
}

//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function paypalSecret(){
	global $CONFIG;
	if(!isset($CONFIG['paypal_secret'])){
		debugValue('paypal_secret not set in config.xml');
		return '';
	}
	return $CONFIG['paypal_secret'];
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function paypapClientId(){
	global $CONFIG;
	if(!isset($CONFIG['paypal_clientid'])){
		debugValue('paypal_secret not set in config.xml');
		return '';
	}
	return $CONFIG['paypal_clientid'];
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function paypalUrl(){
	if(isset($CONFIG['paypal_url'])){
		return $CONFIG['paypal_url'];
	}
	if(isDBStage()){
		return 'https://api.sandbox.paypal.com';
	}
	return 'https://api.paypal.com';
}
//---------- begin function paypalSendInvoice
/**
* @describe sends a paypal invoice
* @param params array
*	from - array of info of sender
*		firstname
*		lastname
*		address_1
*		city
*		state
*		postal_code
*		country_code
*		email
*		website
*	to - email, comma separated list of emails, or array of emails
*	[template_id] - uses this template if given
*	[invoice_number] - defaults to paypalNextInvoiceNumber
*	[description] - defaults to Services Rendered
*	[invoice_date] - defaults to YYYY-MM-DD
*	[currency_code] - defaults to USD
*	[note] - defaults to Billed Services to Date
*	[payment_term] - defaults to DUE_ON_RECEIPT
*	[payment_due] - defaults to YYYY-MM-DD
*	items - array with the following attributes
*		[unit_of_measure] - defaults to QUANTITY
*		name
*		description
*		quantity
*		amount_value
*		[amount_currency] - defaults to USD
*		item_date

* @return array - array of results
* @usage
*	$result=paypalSendPayout($params);
*/
function paypalSendInvoice($params=array()){
	if(!isset($params['from'],$params['to'],$params['items'][0])){
		return "Error: Missing required params.";
	}
	if(!isset($params['invoice_number'])){$params['invoice_number']=paypalNextInvoiceNumber();}
	if(!isset($params['description'])){$params['description']='Services Rendered';}
	if(!isset($params['invoice_date'])){$params['invoice_date']=date('Y-m-d');}
	if(!isset($params['currency_code'])){$params['currency_code']='USD';}
	if(!isset($params['note'])){$params['note']='Billed Services to Date';}
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
				'allow_partial_payment'=>isset($params['allow_partial_payment'])?$params['allow_partial_payment']:false
			),
			'allow_tip'=>isset($params['allow_tip'])?$params['allow_tip']:true,
			'tax_calculated_after_discount'=>isset($params['tax_calculated_after_discount'])?$params['tax_calculated_after_discount']:true,
			'tax_inclusive'=>isset($params['tax_inclusive'])?$params['tax_inclusive']:false
		)
	);
	//recipeints
	if(!is_array($params['to'])){
		$params['to']=preg_split('/[\,\;]+/',$params['to']);
		foreach($params['to'] as $to){
			$to=trim($to);
			if(isEmail($to)){
				$invoice['primary_recipients'][]=array('email_address'=>$to);
			}
		}	
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
				'value'		=> round($item['amount_value'],2),
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
*	sender_batch_id - max length 256 chars
*	email_subject
*	email_message
*	items - array with the following attributes
*		amount_value
*		note - note about item. Max length is 4000 chars
*		recipient_type  - email, phone, paypal_id (encrypted)
*		recipient_value - the actual email, phone, or paypal_id to send to
*		[sender_item_id]  - defaults to YYMMDD-x where x incriments. Tracks the payout in an accounting system
*		[amount_currency] - defaults to USD
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
	foreach($params['items'] as $i=>$item){
		//check for required item fields
		if(!isset($item['amount_value'])){
			continue;
		}
		//default sender_item_id
		if(!isset($item['sender_item_id'])){
			$n=$i+1;
			$item['sender_item_id']=date('YmdHis')."-{$n}";
		}
		//default currency to USD
		if(!isset($item['amount_currency'])){$item['amount_currency']='USD';}
		$citem=array(
			'recipient_type'=> $item['recipient_type'],
			'amount'=>array(
				'value'		=> round($item['amount_value'],2),
				'currency'	=> $item['amount_currency']
			),
			'note'			=> $item['note'],
			'sender_item_id'=> $item['sender_item_id'],
			'receiver'		=> $item['recipient_value'],
		);
		if(strtolower($citem['recipient_type']) == 'phone'){
			$citem['recipient_wallet']='Venmo';
		}
		$payout['items'][]=$citem;
	}
	$url=paypalUrl().'/v1/payments/payouts';
	$json=json_encode($payout);
	//echo $url.printValue($payout);exit;
	$token=paypalGetAccessToken();
	if(strlen($token)){
		$post=postJSON($url,$json,array(
			'-method'=>'POST',
			'-json'=>1,
			'-headers'=>array("Authorization: Bearer {$token}")
		));
		if(isset($post['json_array'])){
			$payout['response']=$post['json_array'];
			return $payout;
			//return $post['json_array'];
		}
		return "ERROR: ". printValue($post);
	}
	return "Error: no token";
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
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
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
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
	//echo "url:{$url}, clientId:{$clientId}, secret:{$secret}".PHP_EOL;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($ch, CURLOPT_USERPWD, $clientId.":".$secret);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

	$result = curl_exec($ch);
	curl_close($ch);
	//echo $result;exit;
	if(empty($result)){return '';}
	else{
	    $json = json_decode($result,true);
	    return $json['access_token'];
	}
	return '';
}