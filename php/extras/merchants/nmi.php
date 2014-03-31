<?php
/*
	Network Merchants Integration Module
	https://www.nmi.com/
	To use this module in WaSQL: loadExtras('nmi'); or loadExtras(array('nmi'));
	Look at nmiTest function for a sample of how to use it.
	The nmi folder also has sample results.

*/
//-----------------------
function nmiTest(){
	$rtn=nmiAuth(array(
		'username'	=> "demo",
		'password'	=> "password",
		'ccnumber'	=> nmiTestModeCreditCard('amex'),
		'ccexp'		=> "10/10",
		'amount'	=> 7.50,
		'-ssl'		=> false,
		'address1'	=> 888,
		'zip'		=> 77777,
		'cvv'		=> 999,
		'orderid'	=> 'NH44345_web',
		'orderdescription'	=> "This is a test description",
		'product_sku'=>"45434"
	));
	echo "Auth" . printValue($rtn);
	if($rtn['response']==1){
		$rtn=nmiCapture(array(
			'username'	=> "demo",
			'password'	=> "password",
			'amount'	=> $rtn['amount'],
			'transactionid'=>$rtn['transactionid'],
			'-ssl'		=> false,
			'orderid'	=> 'NH44345_web',
			'orderdescription'	=> "This is a test description",
			'product_sku'=>"45434"
		));
	echo "Capture" . printValue($rtn);
	}
}
//-----------------------
function nmiUrl(){
	return 'https://secure.networkmerchants.com/api/transact.php';
	}
//------------------------
function nmiSale($params=array()){
	$params['type']='sale';
	return nmiCreditCardSAC($params);
	}
//------------------------
function nmiAuth($params=array()){
	$params['type']='auth';
	return nmiCreditCardSAC($params);
	}
//------------------------
function nmiCredit($params=array()){
	$params['type']='credit';
	return nmiCreditCardSAC($params);
	}
//------------------------
function nmiCapture($params=array()){
	//Credit Card Capture Request
	$params['type']='capture';
	$required=array('username','password','transactionid','amount');
	foreach($required as $req){
		if(!isset($params[$req]) || !strlen($params[$req])){return "{$req} is a required field" . printValue($params);}
    	}
	//validate amount
	if(!isNum($params['amount'])){return "Invalid amount (numeric)". printValue($params);}
	return nmiPost($params);
	}
//------------------------
function nmiVoid($params=array()){
	//Credit Card Void Request
	$params['type']='void';
	$required=array('username','password','transactionid');
	foreach($required as $req){
		if(!isset($params[$req]) || !strlen($params[$req])){return "{$req} is a required field" . printValue($params);}
    	}
	return nmiPost($params);
	}
//------------------------
function nmiRefund($params=array()){
	//Credit Card Refund Request
	$params['type']='refund';
	$required=array('username','password','transactionid');
	foreach($required as $req){
		if(!isset($params[$req]) || !strlen($params[$req])){return "{$req} is a required field" . printValue($params);}
    	}
	//validate optional amount
	if(isset($params['amount']) && !isNum($params['amount'])){return "Invalid amount (numeric)". printValue($params);}
	return nmiPost($params);
	}
//------------------------
function nmiUpdate($params=array()){
	//Credit Card Update Request
	$params['type']='update';
	$required=array('username','password','transactionid');
	foreach($required as $req){
		if(!isset($params[$req]) || !strlen($params[$req])){return "{$req} is a required field" . printValue($params);}
    	}
	//validate optional amount
	if(isset($params['amount']) && !isNum($params['amount'])){return "Invalid amount (numeric)". printValue($params);}
	return nmiPost($params);
	}
//------------------------
function nmiCreditCardSAC($params=array()){
	//Credit Card Sale/Authorization/Credit Request
	$params['payment']='creditcard';
	$required=array('type','username','password','ccnumber','ccexp','amount');
	foreach($required as $req){
		if(!isset($params[$req]) || !strlen($params[$req])){return "{$req} is a required field" . printValue($params);}
    	}
	//validate amount
	if(!isNum($params['amount'])){return "Invalid amount (numeric)". printValue($params);}
	//validate ccexp
	if(isset($params['ccexp']) && !preg_match('/^[01][1-9][0-9]{2,2}/',$params['ccexp'])){"Invalid format for ccexp (MMYY)". printValue($params);}
	return nmiPost($params);
	}
function nmiPost($params){
	$params['ipaddress']=$_SERVER['REMOTE_ADDR'];
	$url=nmiUrl();
	$post=postUrl($url,$params);
	if(isset($post['body']) && preg_match('/^response\=/is',$post['body'])){
		$response=parseKeyValueString(trim($post['body']));
		$response['response_ex']=nmiResponseCodeLookup($response['response_code']);
		if(strlen($response['avsresponse'])){
			$response['avsresponse_ex']=nmiResponseCodeLookup_AVS($response['avsresponse']);
			}
		if(strlen($response['cvvresponse'])){
			$response['cvvresponse_ex']=nmiResponseCodeLookup_CVV($response['cvvresponse']);
			}
		if($params['username']=='demo'){$response['mode']="TEST MODE";}
		$response['amount']=$params['amount'];
		switch($response['response']){
			case 1: $response['status']="OK";break;
			case 2: $response['status']="DECLINED";break;
			case 3: $response['status']="REJECTED";break;
			case 4: $response['status']="RETURNED";break;
			default: $response['status']="PROCESS ERROR";break;
        	}
    	}
    else{
		$response=array(
			'status'	=> "PROCESS ERROR",
			'response'	=> 5,
			'responsetext'	=> $post['error'],
			'response_code'	=> $post['curl_info']['http_code']
			);
    	}
    ksort($response);
    if(isset($params['-debug']) && $params['-debug']==1){
		$response['-debug']=array(
			'url'		=> $url,
			'sent'		=> $params,
			'received'	=>	array(
				'body'	=> $post['body'],
				'code'	=> $post['curl_info']['http_code'],
				'date'	=> $post['headers']['date'],
				'server'=> $post['headers']['server'],
				)
			);
    	}
	return $response;
	}
//-----------------------
function nmiTestModeCreditCard($type=''){
	switch(strtolower($type)){
		case 'visa':return '4111111111111111';break;
		case 'mastercard':
		case 'mc':return '5431111111111111';break;
		case 'discover':
		case 'discover card':return '6011601160116611';break;
		case 'amex':
		case 'american express':return '341111111111111';break;
    	}
	}
//-----------------------
function nmiResponseCodeLookup($code=0){
	switch($code){
		case 100:return "Transaction was Approved";break;
		case 200:return "Transaction was declined by Processor";break;
		case 201:return "Do Not Honor";break;
		case 202:return "Insufficient Funds";break;
		case 203:return "Over Limit";break;
		case 204:return "Transaction not allowed";break;
		case 220:return "Incorrect Payment Data";break;
		case 221:return "No Such Card Issuer";break;
		case 222:return "No Card Number on file with Issuer";break;
		case 223:return "Expired Card";break;
		case 224:return "Invalid Expiration Date";break;
		case 225:return "Invalid Card Security Code";break;
		case 240:return "Call Issuer for Further Information";break;
		case 250:return "Pick Up Card";break;
		case 251:return "Lost Card";break;
		case 252:return "Stolen Card";break;
		case 253:return "Fraudulant Card";break;
		case 260:return "Declined with further Instuctns Available";break;
		case 261:return "Declined - Stop All Recurring Payments";break;
		case 262:return "Declined - Stop this Recurrring Program";break;
		case 263:return "Declined - Update Cardholder Data Available";break;
		case 264:return "Declined - Retry in a few days";break;
		case 300:return "Transaction was rejected by Gateway";break;
		case 400:return "Transaction Error Returned by Processor";break;
		case 410:return "Invalid Merchant Configuration";break;
		case 411:return "Merchant Account is inactive";break;
		case 420:return "Communication Error";break;
		case 421:return "Communication Error with Issuer";break;
		case 430:return "Duplicate Transaction at Processor";break;
		case 440:return "Processor Format Error";break;
		case 441:return "Invalid Transacton Information";break;
		case 460:return "Processor Feature not Available";break;
		case 461:return "Unsupported Card Type";break;
		}
    return "Unknown";
	}
//-----------------------
function nmiResponseCodeLookup_AVS($code=''){
	switch(strtoupper($code)){
		case 'X':return "Exact match, 9-character numeric ZIP";break;
		case 'Y':
		case 'D':
		case 'M':return "Exact match, 5-character numeric ZIP";break;
		case 'A':
		case 'B': return "Address match only";break;
		case 'W': return "9-character numeric ZIP match only";break;
		case 'Z':
		case 'P':
		case 'L': return "5-character Zip match only";break;
		case 'N':
		case 'C': return "No address or ZIP match";break;
		case 'U': return "Address unavailable";break;
		case 'G': 
		case 'I': return "Non-U.S. Issuer does not participate";break;
		case 'R': return "Issuer system unavailable";break;
		case 'E': return "Not a mail/phone order";break;
		case 'S': return "Service not supported";break;
		case '0':
		case 'O':
		case 'B': return "AVS Not Available";break;
    	}
    return "Unknown";
	}
//-----------------------
function nmiResponseCodeLookup_CVV($code=''){
	switch(strtoupper($code)){
		case 'M':return "CVV2/CVC2 Match";break;
		case 'N':return "CVV2/CVC2 No Match";break;
		case 'P':return "Not Processed";break;
		case 'S':return "Merchant has indicated that CVV2/CVC2 is not present on card";break;
		case 'U':return "Issuer is not certified and/or has not provided Visa encryption keys";break;
		}
    return "Unknown";
	}

?>