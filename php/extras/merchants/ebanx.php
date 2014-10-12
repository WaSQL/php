<?php
/* 
	eBanx API functions
	see http://developers.ebanx.com/integrations/ebanx-direct/direct-api-reference/
		http://developers.ebanx.com/integrations/ebanx-direct/sample-code/

The array returned from ebanxAuthorizeCapture on failure
Array
(
    [status] => ERROR
    [status_code] => BP-DR-0
    [status_message] => Payment already exists with merchant_payment_code: RD123456 (created on 2014-08-20 14:09:20, status is CO)
)

The array returned from ebanxAuthorizeCapture on success.  
********* NOTE:You MUSt store the hash value if you want to later refund via ebanxRefund function
Array
(
    [payment] => Array
        (
            [hash] => 53f4f37b4115290b11483eb80bf872ca52f187256e75aa40
            [merchant_payment_code] => RD1234571
            [order_number] => RD1234571
            [status] => CO
            [status_date] => 2014-08-20 16:14:04
            [open_date] => 2014-08-20 16:14:02
            [confirm_date] => 2014-08-20 16:14:04
            [transfer_date] => 
            [amount_br] => 52.84
            [amount_ext] => 22.40
            [amount_iof] => 0.20
            [currency_rate] => 2.3500
            [currency_ext] => USD
            [due_date] => 2014-08-23
            [instalments] => 1
            [payment_type_code] => visa
            [transaction_status] => Array
                (
                    [acquirer] => CIELO
                    [code] => OK
                    [description] => Cartão de teste autorizado - aguardando captura
                    [authcode] => 12345
                )

            [pre_approved] => 1
            [capture_available] => 
        )

    [status] => SUCCESS
)

Array returned on from ebanxRefund function:
Array
(
    [payment] => Array
        (
            [hash] => 53f4f37b4115290b11483eb80bf872ca52f187256e75aa40
            [merchant_payment_code] => RD1234571
            [order_number] => RD1234571
            [status] => CO
            [status_date] => 2014-08-20 16:14:04
            [open_date] => 2014-08-20 16:14:02
            [confirm_date] => 2014-08-20 16:14:04
            [transfer_date] => 
            [amount_br] => 52.84
            [amount_ext] => 22.40
            [amount_iof] => 0.20
            [currency_rate] => 2.3500
            [currency_ext] => USD
            [due_date] => 2014-08-23
            [instalments] => 1
            [payment_type_code] => visa
            [transaction_status] => Array
                (
                    [acquirer] => CIELO
                    [code] => OK
                    [description] => Cartão de teste autorizado - aguardando captura
                    [authcode] => 12345
                )

            [pre_approved] => 1
            [capture_available] => 
            [refunds] => Array
                (
                    [0] => Array
                        (
                            [id] => 7586
                            [merchant_refund_code] => 
                            [status] => RE
                            [request_date] => 2014-08-21 11:43:10
                            [pending_date] => 
                            [confirm_date] => 
                            [cancel_date] => 
                            [amount_ext] => 2.40
                            [description] => this is your partial refund
                        )

                )

        )

    [refund] => Array
        (
            [id] => 7586
            [merchant_refund_code] => 
            [status] => RE
            [request_date] => 2014-08-21 11:43:10
            [pending_date] => 
            [confirm_date] => 
            [cancel_date] => 
            [amount_ext] => 2.40
            [description] => this is your partial refund
        )

    [operation] => refund
    [status] => SUCCESS
)


*/
$progpath=dirname(__FILE__);

//---------- begin function ebanxQuery ----------
/**
* @describe returns information about a specific transaction
* @param array
*	integration_key  Your eBanx integration_key
*	hash - hash value returned from ebanxAuthorize
* @return array with a status and other transaction info
*/
function ebanxQuery($params=array()){
	//auth tokens are required
	$required=array(
		'integration_key','hash'
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "ebanxQuery Error: Missing required param '{$key}'";
		}
	}
	$order=array(
		'integration_key'	=> $params['integration_key'],
		'hash'				=> $params['hash'],
		'operation'			=> 'query',
	);
	if($params['-test']){
		$url='https://sandbox.ebanx.com/ws/query/';
	}
	else{
		$url='https://www.ebanx.com/pay/ws/query/';
	}

	//add options to URL
	$url.='?'. http_build_query($order);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$response = curl_exec($ch);

	if (curl_errno($ch) > 0){
		$error= curl_error($ch);
		curl_close($ch);
		return "ebanxQuery: {$error}";
	}
	curl_close($ch);

	// The response is a JSON expression, so we can decode it
	$response = json_decode($response, true);
	//$response['url']=$url;
	return $response;
}

//---------- begin function ebanxCheckCPF ----------
/**
* @describe validates CPF is valid number
* @param cpf string
* @return boolean
*/
function ebanxCheckCPF($cpf){
	// Verify if the number entered contains all the digits - must be 11 digits
	$cpf = str_pad(ereg_replace('[^0-9]', '', $cpf), 11, '0', STR_PAD_LEFT);
	if(strlen($cpf) != 11 || $cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999'){
		return false;
	}
	else{
		// Calculates the numbers to see if the CPF is true
		for ($t = 9; $t < 11; $t++) {
			for ($d = 0, $c = 0; $c < $t; $c++) {
				$d += $cpf{$c} * (($t + 1) - $c);
			}
			$d = ((10 * $d) % 11) % 10;
			if ($cpf{$c} != $d) {
				return false;
			}
		}
		return true;
	}
}
//---------- begin function ebanxRefund ----------
/**
* @describe refunds credit card payments
* @param array
*	integration_key  Your eBanx integration_key
*	hash - hash value returned from ebanxAuthorize
*	amount -  amount to refund
*	description
* @return array with a status of SUCCESS or FAILURE
*/
function ebanxRefund($params=array()){
	//auth tokens are required
	$required=array(
		'integration_key','hash','amount','description'
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "ebanxRefund Error: Missing required param '{$key}'";
		}
	}
	$order=array(
		'integration_key'	=> $params['integration_key'],
		'hash'				=> $params['hash'],
		'operation'			=> 'request',
		'amount'			=> $params['amount'],
		'description'		=> $params['description'],
	);
	if($params['-test']){
		$url='https://sandbox.ebanx.com/ws/refund/';
	}
	else{
		$url='https://www.ebanx.com/pay/ws/refund/';
	}

	//add options to URL
	$url.='?'. http_build_query($order);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$response = curl_exec($ch);

	if (curl_errno($ch) > 0){
		$error= curl_error($ch);
		curl_close($ch);
		return "ebanxRefund: {$error}";
	}
	curl_close($ch);

	// The response is a JSON expression, so we can decode it
	$response = json_decode($response, true);
	//$response['url']=$url;
	return $response;
}


//---------- begin function ebanxAuthorizeCapture ----------
/**
* @describe authorize and capture credit card payments
* @param array
* @return array with a status of SUCCESS or FAILURE
*/
function ebanxAuthorizeCapture($params=array()){
	$auth=ebanxAuthorize($params);
	if(!isset($auth['payment']['hash'])){return $auth;}
	$capture_params=array(
		'integration_key'	=> $params['integration_key'],
		'hash'				=> $auth['payment']['hash']
	);
	if($params['-test']){
    	$capture_params['-test']=$params['-test'];
	}
	$capt=ebanxCapture($capture_params);
	return $capt;
}

//---------- begin function ebanxCapture ----------
/**
* @describe capture credit card payments
* @param array
*	integration_key  Your eBanx integration_key
*	[hash] - hash value returned from ebanxAuthorize
*	[ordernumber] - unique order number - can only be charged against once
* @return array with a status of SUCCESS or FAILURE
*/
function ebanxCapture($params=array()){
	//auth tokens are required
	$required=array(
		'integration_key','hash'
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "ebanxCapture Error: Missing required param '{$key}'";
		}
	}
	$order=array(
		'integration_key'	=> $params['integration_key'],
		'hash'				=> $params['hash'],
	);
	if($params['-test']){
		$url='https://sandbox.ebanx.com/ws/capture/';
	}
	else{
		$url='https://www.ebanx.com/pay/ws/capture/';
	}
	//add options to URL
	$url.='?'. http_build_query($order);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$response = curl_exec($ch);

	if (curl_errno($ch) > 0){
		$error= curl_error($ch);
		curl_close($ch);
		return "ebanxCapture: {$error}";
	}
	curl_close($ch);

	// The response is a JSON expression, so we can decode it
	$response = json_decode($response, true);
	return $response;
}


//---------- begin function ebanxAuthorize ----------
/**
* @describe capture credit card payments
* @param array
*	integration_key  Your eBanx integration_key
*	ordernumber - unique order number - can only be charged against onc
*	amount - amount to authorize
*	currency_code - USD, 
*	document - brazilian version of SSN
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
* @return array with a status of SUCCESS or FAILURE
*/
function ebanxAuthorize($params=array()){
	//auth tokens are required
	$required=array(
		'integration_key','ordernumber','amount','currency_code','document',
		'card_type','card_num','card_exp_date','card_cvv',
		'name','email','phone','city','state','zipcode'
	);
	//For Beloto payments, add birth_date and remove cc fields
	if($params['card_type']=='boleto'){
    	$required=array(
			'integration_key','ordernumber','amount','currency_code','document',
			'card_type','name','email','birth_date','phone','city','state','zipcode'
		);
	}
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "ebanxAuthorize Error: Missing required param '{$key}'";
		}
	}
	//type - personal or business
	if(!isset($params['type'])){$params['type']='personal';}
	//validate document number.  personal is 11 digits, business is 14 digits
	if(!isNum($params['document'])){
		return "ebanxAuthorize Error: invalid document number";
	}
	switch(strtolower($params['type'])){
		case 'personal':
			if(!strlen($params['document'])==11){return "ebanxAuthorize Error: invalid document number";}
		break;
		case 'business':
			if(!strlen($params['document'])==14){return "ebanxAuthorize Error: invalid document number";}
		break;
	}
	$order=array(
		'integration_key'	=> $params['integration_key'],
		'operation'	=> 'request',
		'mode'		=> 'full',
		'payment'	=> array(
			'currency_code'			=> $params['currency_code'],
			'merchant_payment_code'	=> $params['ordernumber'],
			'order_number'			=> $params['ordernumber'],
			'amount_total'			=> $params['amount'],
			'name'					=> $params['name'],
			'person_type'			=> $params['type'],
			'document'				=> $params['document'],
			'email'					=> $params['email'],
			'phone_number'			=> $params['phone'],
			'address'				=> $params['address'],
			'street_number'			=> $params['street_number'],
			'city'					=> $params['city'],
			'state'					=> $params['state'],
			'zipcode'				=> $params['zipcode'],
			'country'				=> 'br',
			'payment_type_code'		=> $params['card_type'],
		)
	);
	if($params['birth_date']){
		$order['payment']['birth_date']=$params['birth_date'];
	}
	if($params['street_complement']){
		$order['payment']['street_complement']=$params['street_complement'];
	}
	if($params['card_num']){
		$order['payment']['creditcard']=array(
			'card_number'			=> $params['card_num'],
			'card_name'				=> $params['name'],
			'card_due_date'			=> $params['card_exp_date'],
			'card_cvv'				=> $params['card_cvv'],
			'auto_capture'			=> false
			);
	}
	if($params['-test']){
		$url='https://sandbox.ebanx.com/ws/direct';
	}
	else{
		$url='https://www.ebanx.com/pay/ws/direct';
	}

	// We format the data as JSON, and we prepare a CURL call to the API
	$json = json_encode($order);
	$ch = curl_init();
	$data['request_body']=$json;
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	
	$result = curl_exec($ch);
	
	// Checks if there was a problem with cURL
	if (curl_errno($ch) > 0){
		$error= curl_error($ch);
		curl_close($ch);
		return "ebanxAuthorize error: {$error}";
	}
	curl_close($ch);
	$result = json_decode($result, true);
	return $result;
}


?>