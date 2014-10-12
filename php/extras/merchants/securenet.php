<?php
/* 
	SecureNet API functions
	see http://www.securenet.com/developers
		https://apidocs.securenet.com/docs/getstarted.html




*/
$progpath=dirname(__FILE__);


//---------- begin function securenetAuthorize ----------
/**
* @describe This method authorizes a transaction but does not capture the transaction for settlement. In a card-not-present environment, this call is most often used when goods are to be shipped after purchase. In such cases, an authorization is performed on the transaction, and a subsequent Prior Auth Capture call is made to capture the transaction for settlement after the goods are shipped. The merchant does not receive funding until the initial authorization is captured and settled.
*	POST https://gwapi.demo.securenet.com/api/Payments/Authorize
*		Sample Code
*		{
*		  amount: 11.00,
*		  card: {
*		    number: '4444 3333 2222 1111',
*		    cvv: '999',
*		    expirationDate: '04/2016',
*		    address: {
*		      line1: '123 Main St.',
*		      city: 'Austin',
*		      state: 'TX',
*		      zip: '78759'
*		    },
*		    firstname: 'Jack',
*		    lastname: 'Test'
*		  },
*		  extendedInformation: {
*		    typeOfGoods: 'PHYSICAL'
*		  },
*		  developerApplication: {
*		    developerId: 12345678,
*		    version: '1.2'
*		  }
*		}
Authorization Request Parameters
name 	type 	description
required:
* @param  params array.
*	amount - amount to authorize
*	card_type - visa, mastercard, diners, hipercard, boleto, banrisul
*	card_num
*	card_exp_date - mm/YYYY
*	card_cvv
*	name	- name on card
*	email
*	phone
*	city
*	state
*	zipcode
* @param  [allowPartialCharges] 	boolean indicates whether it is permissible to authorize less than the total balance available on a prepaid card.
* @param  [transactionDuplicateCheckIndicator] 	int indicates how checks for duplicate transactions should behave. Duplicates are evaluated on the basis of amount, card number, and order ID; these evaluation criteria can be extended to also include customer ID, invoice number, or a user-defined field. Valid values for this parameter are:
*		0 - No duplicate check
*		1 - Exception code is returned in case of duplicate
*		2 - Previously existing transaction is returned in case of duplicate
*		3 - Check is performed as above but without using order ID, and exception code is returned in case of duplicate
*		The transactionDuplicateCheckIndicator parameter must be enabled in the Virtual Terminal under Tools->Duplicate Transactions. Duplicates are checked only for APPROVED transactions.
* @param  [orderId] 	string 	client-generated unique ID for each transaction, used as a way to prevent the processing of duplicate transactions. The orderId must be unique to the merchant's SecureNet ID; however, uniqueness is only evaluated for APPROVED transactions and only for the last 30 days. If a transaction is declined, the corresponding orderId may be used again. (NOTE: orderId is not used in Prior Auth Capture.)
*		The orderId is limited to 25 characters; e.g., “CUSTOMERID MMddyyyyHHmmss”.
* @return result 	enum 	result of the method call.
*		responseCode 	enum 	response code for the method call.
*		message 	string 	text description of the response.
*		transaction 	object 	detailed information about the transaction, including but not limited to: transaction id; authorization code; avs result code; and cvv result code.
Success Request:
Array
(
    [transaction] => Array
        (
            [secureNetId] => 8003085
            [transactionType] => AUTH_CAPTURE
            [customerId] => 
            [orderId] => RD1234574
            [transactionId] => 114140367
            [authorizationCode] => 3UJDGT
            [authorizedAmount] => 22.4
            [allowedPartialCharges] => 
            [paymentTypeCode] => VI
            [paymentTypeResult] => CREDIT_CARD
            [level2Valid] => 
            [level3Valid] => 
            [transactionData] => Array
                (
                    [date] => 2014-09-05T19:37:50Z
                    [amount] => 22.4
                )

            [settlementData] => 
            [vaultData] => 
            [creditCardType] => VISA
            [cardNumber] => XXXXXXXXXXXX 1111
            [avsCode] => Y
            [avsResult] => MATCH
            [cardHolder_FirstName] => Joan
            [cardHolder_LastName] => Silva
            [expirationDate] => 0820
            [billAddress] => Array
                (
                    [line1] => Rua Teste
                    [city] => Curitiba
                    [state] => PR
                    [zip] => 
                    [country] => BR
                    [company] => 
                    [phone] => 
                )

            [email] => 
            [emailReceipt] => 
            [cardCodeCode] => M
            [cardCodeResult] => MATCH
            [accountName] => 
            [accountType] => 
            [accountNumber] => 
            [checkNumber] => 
            [traceNumber] => 
            [surchargeAmount] => 0
            [cashbackAmount] => 0
            [fnsNumber] => 
            [voucherNumber] => 
            [fleetCardInfo] => 
            [gratuity] => 0
            [industrySpecificData] => P
            [marketSpecificData] => 
            [networkCode] => 
            [additionalAmount] => 0
            [additionalData1] => 
            [additionalData2] => 
            [additionalData3] => 
            [additionalData4] => 
            [additionalData5] => 
            [method] => CC
            [imageResult] => 
        )

    [success] => 1
    [result] => APPROVED
    [responseCode] => 1
    [message] => SUCCESS
    [responseDateTime] => 2014-09-05T19:37:53.45Z
    [rawRequest] => 
    [rawResponse] => 
    [jsonRequest] => 
)
Duplicate Request:
Array
(
    [transaction] => Array
        (
            [secureNetId] => 8003085
            [transactionType] => AUTH_CAPTURE
            [customerId] => 
            [orderId] => RD1234573
            [transactionId] => 0
            [authorizationCode] => 
            [authorizedAmount] => 0
            [allowedPartialCharges] => 
            [paymentTypeCode] => 
            [paymentTypeResult] => UNKNOWN
            [level2Valid] => 
            [level3Valid] => 
            [transactionData] => 
            [settlementData] => 
            [vaultData] => 
            [creditCardType] => 
            [cardNumber] => XXXXXXXXXXXX 
            [avsCode] => 
            [avsResult] => NOT_CHECKED
            [cardHolder_FirstName] => Joan
            [cardHolder_LastName] => Silva
            [expirationDate] => 
            [billAddress] => Array
                (
                    [line1] => Rua Teste
                    [city] => Curitiba
                    [state] => PR
                    [zip] => 
                    [country] => BR
                    [company] => 
                    [phone] => 
                )

            [email] => 
            [emailReceipt] => 
            [cardCodeCode] => 
            [cardCodeResult] => NOT_CHECKED
            [accountName] => 
            [accountType] => 
            [accountNumber] => 
            [checkNumber] => 
            [traceNumber] => 
            [surchargeAmount] => 0
            [cashbackAmount] => 0
            [fnsNumber] => 
            [voucherNumber] => 
            [fleetCardInfo] => 
            [gratuity] => 0
            [industrySpecificData] => 
            [marketSpecificData] => 
            [networkCode] => 
            [additionalAmount] => 0
            [additionalData1] => 
            [additionalData2] => 
            [additionalData3] => 
            [additionalData4] => 
            [additionalData5] => 
            [method] => CC
            [imageResult] => 
        )

    [success] => 
    [result] => BAD_REQUEST
    [responseCode] => 3
    [message] => PROVIDED ORDER ID MUST BE UNIQUE FOR SECURENET ID
    [responseDateTime] => 2014-09-05T19:37:04.96Z
    [rawRequest] => 
    [rawResponse] => 
    [jsonRequest] => 
)




*/
function securenetCharge($params=array()){
	//auth tokens are required
	$required=array(
		'securenet_id','securenet_key','amount','description',
		'card_num','card_exp_date','card_cvv'
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "securenetAuthorize Error: Missing required param '{$key}'";
		}
	}
	//type of goods:  PHYSICAL or DIGITAL
	if(!isset($params['type_of_goods'])){$params['type_of_goods']='PHYSICAL';}
	//developerID during test can just be an 8 digit number:  12345678
	$order=array(
		'developerApplication'	=> array('developerId'=>$params['developer_id'],'version'=>'1.2'),
		'extendedInformation'	=> array('typeOfGoods'=>$params['type_of_goods']),
		'card'					=> array(
			'number'			=> $params['card_num'],
			'cvv'				=> $params['card_cvv'],
			'expirationDate'	=> $params['card_exp_date'] /* MM/YYYY */
		),
		'amount'			=> $params['amount'],
		'description'		=> $params['description'],
	);
 //use orderid or ordernumber as order to guarantee no duplicate transactions
	if(isset($params['orderid'])){$order['orderId']=$params['orderid'];}
	elseif(isset($params['ordernumber'])){$order['orderId']=$params['ordernumber'];}
	//firstname and lastname
	if(isset($params['billtoname'])){
		list($firstname,$lastname)=preg_split('/\ /',$params['billtoname'],2);
		$order['card']['firstName']=$firstname;
		$order['card']['lastName']=$lastname;
	}
	elseif(isset($params['shiptoname'])){
		list($firstname,$lastname)=preg_split('/\ /',$params['shiptoname'],2);
		$order['card']['firstName']=$firstname;
		$order['card']['lastName']=$lastname;
	}
	elseif(isset($params['name'])){
		list($firstname,$lastname)=preg_split('/\ /',$params['name'],2);
		$order['card']['firstName']=$firstname;
		$order['card']['lastName']=$lastname;
	}
	else{
		if(isset($params['firstname'])){$order['card']['firstName']=$params['firstname'];}
		if(isset($params['lastname'])){$order['card']['lastName']=$params['lastname'];}
	}
 //address
	if(isset($params['address'])){$order['card']['address']['line1']=$params['address'];}
	if(isset($params['city'])){$order['card']['address']['city']=$params['city'];}
	if(isset($params['state'])){$order['card']['address']['state']=$params['state'];}
	if(isset($params['zip'])){$order['card']['address']['zip']=$params['zip'];}
	elseif(isset($params['zipcode'])){$order['card']['address']['zipcode']=$params['zipcode'];}
	elseif(isset($params['postcode'])){$order['card']['address']['zip']=$params['postcode'];}
	if(isset($params['country'])){$order['card']['address']['country']=$params['country'];}
	//ordernumber
	if(isset($params['ordernumber'])){$order['extendedInformation']['invoiceNumber']=$params['ordernumber'];}
	//description
	if(isset($params['description'])){$order['extendedInformation']['invoiceDescription']=$params['description'];}
	if($params['-test']){
		$url='https://gwapi.demo.securenet.com/api/Payments/Charge';
	}
	else{
		$url='https://gwapi.securenet.com/api/Payments/Charge';
	}
	//echo $url.printValue($order);exit;
	$data = json_encode($order);
	//initialize curl
	$ch = curl_init($url);
	//add custom auth header for SecureNet
	/* $securenet_id_base64=base64_encode("{$params['securenet_id']}:{$params['securenet_key']}");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Authorization'=>"Basic {$securenet_id_base64}"
	)); */
	$headers = array(
	    'Content-Type:application/json',
	    'Authorization: Basic '. base64_encode("{$params['securenet_id']}:{$params['securenet_key']}")
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	//curl_setopt($process, CURLOPT_USERPWD, $params['securenet_id'] . ":" . $params['securenet_key']);
	//add other options
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$response = curl_exec($ch);

	if (curl_errno($ch) > 0){
		$error= curl_error($ch);
		curl_close($ch);
		return "securenetAuthorize Error: {$error}";
	}
	curl_close($ch);

	// The response is a JSON expression, so we can decode it
	$response = json_decode($response, true);
	//$response['_options']=$order;
	//$response['url']=$url;
	return $response;
}

//---------- begin function securenetRefund ----------
/**
* @describe A refund may be applied against any charge that has settled.
*	POST https://gwapi.demo.securenet.com/api/Payments/Refund
*		Sample Code
*		{
*		  transactionId: 111995104,
*		  developerApplication: {
*		    developerId: 12345678,
*		    version: '1.2'
*		  }
*		}
* @param  params array.
*	[amount] - amount to refund, if less than the total
*	ordernumber - ordernumber
*	transaction_id  - transactionId returned from authorize transaction
*	developer_id
* @return result 	enum 	result of the method call.
*		responseCode 	enum 	response code for the method call.
*		message 	string 	text description of the response.
*		transaction 	object 	detailed information about the transaction, including but not limited to: transaction id; authorization code; avs result code; and cvv result code.
Success Request:
Array
(
    [transaction] => Array
        (
            [secureNetId] => 8003085
            [transactionType] => PARTIAL_VOID
            [customerId] => 
            [orderId] => RD1234574
            [transactionId] => 114140367
            [authorizationCode] => 3UJDGT
            [authorizedAmount] => 20
            [allowedPartialCharges] => 
            [paymentTypeCode] => VI
            [paymentTypeResult] => CREDIT_CARD
            [level2Valid] => 
            [level3Valid] => 
            [transactionData] => Array
                (
                    [date] => 2014-09-06T13:54:35Z
                    [amount] => 2.4
                )

            [settlementData] => 
            [vaultData] => 
            [creditCardType] => VISA
            [cardNumber] => XXXXXXXXXXXX 1111
            [avsCode] => Y
            [avsResult] => MATCH
            [cardHolder_FirstName] => 
            [cardHolder_LastName] => 
            [expirationDate] => 0820
            [billAddress] => Array
                (
                    [line1] => 
                    [city] => 
                    [state] => 
                    [zip] => 
                    [country] => 
                    [company] => 
                    [phone] => 
                )

            [email] => 
            [emailReceipt] => 
            [cardCodeCode] => M
            [cardCodeResult] => MATCH
            [accountName] => 
            [accountType] => 
            [accountNumber] => 
            [checkNumber] => 
            [traceNumber] => 
            [surchargeAmount] => 0
            [cashbackAmount] => 0
            [fnsNumber] => 
            [voucherNumber] => 
            [fleetCardInfo] => 
            [gratuity] => 0
            [industrySpecificData] => 0
            [marketSpecificData] => 
            [networkCode] => 
            [additionalAmount] => 0
            [additionalData1] => 
            [additionalData2] => 
            [additionalData3] => 
            [additionalData4] => 
            [additionalData5] => 
            [method] => CC
            [imageResult] => 
        )

    [success] => 1
    [result] => APPROVED
    [responseCode] => 1
    [message] => SUCCESS
    [responseDateTime] => 2014-09-06T13:54:41.88Z
    [rawRequest] => 
    [rawResponse] => 
    [jsonRequest] => 
)
*/
function securenetRefund($params=array()){
	//auth tokens are required
	$required=array(
		'securenet_id','securenet_key','amount','ordernumber',
		'transaction_id'
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "securenetRefund Error: Missing required param '{$key}'";
		}
	}
	$order=array(
		'developerApplication'	=> array('developerId'=>$params['developer_id'],'version'=>'1.2'),
		'amount'			=> $params['amount'],
		'transactionId'		=> $params['transaction_id']
	);
 	//use orderid or ordernumber as order to guarantee no duplicate transactions
	if(isset($params['orderid'])){$order['orderId']=$params['orderid'];}
	elseif(isset($params['ordernumber'])){$order['orderId']=$params['ordernumber'];}
	//echo $url.printValue($order);exit;
	$data = json_encode($order);
	if($params['-test']){
		$url='https://gwapi.demo.securenet.com/api/Payments/Refund';
	}
	else{
		$url='https://gwapi.securenet.com/api/Payments/Refund';
	}
	//initialize curl
	$ch = curl_init($url);
	//add custom auth header for SecureNet
	$headers = array(
	    'Content-Type:application/json',
	    'Authorization: Basic '. base64_encode("{$params['securenet_id']}:{$params['securenet_key']}")
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	//add other options
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$response = curl_exec($ch);

	if (curl_errno($ch) > 0){
		$error= curl_error($ch);
		curl_close($ch);
		return "securenetAuthorize Error: {$error}";
	}
	curl_close($ch);

	// The response is a JSON expression, so we can decode it
	$response = json_decode($response, true);
	//$response['_options']=$order;
	//$response['url']=$url;
	return $response;
}

?>