<?php
/*
	Stripe Integration - stripe.com
	https://stripe.com/docs/api/php#create_charge
	Test Credit Card numbers
		4242424242424242	Visa
		4012888888881881	Visa
		4000056655665556	Visa (debit)
		5555555555554444	MasterCard
		5200828282828210	MasterCard (debit)
		5105105105105100	MasterCard (prepaid)
		378282246310005	American Express
		371449635398431	American Express
		6011111111111117	Discover
		6011000990139424	Discover
		30569309025904	Diners Club
		38520000023237	Diners Club
		3530111333300000	JCB
		3566002020360505	JCB
	NOTE: amount must be in integer. A positive integer in the smallest currency unit (e.g 100 cents to charge $1.00, or 1 to charge ¥1, a 0-decimal currency) representing how much to charge the card
	
	Example Code:
		loadExtras('stripe');
		$charge=stripeCharge(array(
			'apikey'			=> 'sk_test_fgfYOURAPIKEYHEREH4r',
			'card_num'			=> '4242424242424242',
			'card_exp_month'	=> 10,
			'card_exp_year'		=> '15',
			'amount'			=> 67.12,
			'card_cvc'			=> '123',
			'description'		=> 'cool stuff ',
			'ordernumber'		=> 'RD4584758',
			'email'				=> 'your@customer.com',
			'name'				=> 'Jane Doe',
			'billtozipcode'		=> '89578'
		));
		echo printValue($charge);
		exit;
*/
$progpath=dirname(__FILE__);
require_once("{$progpath}/stripe/Stripe.php");


function stripeCharge($params=array()){
	//auth tokens are required
	$required=array(
		'apikey','amount','card_num',
		'description'
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
			$response=array(
				'status'	=> 'failed',
				'response_reason_text' => "Error: Missing required param '{$key}'",
				'approved'	=> false
				);
			$response['message']=$response['response_reason_text'];
			ksort($response);
			return $response;
		}
	}
	if(!isset($params['currency'])){$params['currency']='usd';}
	else{$params['currency']=strtolower($params['currency']);}

	$auth=Stripe::setApiKey($params['apikey']);
	//echo printValue($params);exit;
	$card=array(
		'number'	=> $params['card_num'],
		'exp_month'	=> $params['card_exp_month'],
		'exp_year'	=> $params['card_exp_year']
	);
	if(isset($params['card_cvc'])){$card['cvc']=$params['card_cvc'];}
	elseif(isset($params['card_cvv2'])){$card['cvc']=$params['card_cvv2'];}
	elseif(isset($params['cc_cvv2'])){$card['cvc']=$params['cc_cvv2'];}
	if(isset($params['billtoname'])){$card['name']=$params['billtoname'];}
	elseif(isset($params['name'])){$card['name']=$params['name'];}
	if(isset($params['billtozipcode'])){$card['address_zip']=$params['billtozipcode'];}
	elseif(isset($params['address_zip'])){$card['address_zip']=$params['address_zip'];}
	$convert_currency=0;
	switch(strtolower($params['currency'])){
		case 'usd':
		case 'cad':
		case 'gbp':
			$convert_currency=1;
			$params['amount']=round(($params['amount']*100),0);
		break;
	}
	$charge=array(
		"amount" => $params['amount'],
		"currency" => $params['currency'],
		"card" => $card,
		"description" => $params['description']
	);
	$meta=array();
	if(isset($params['email'])){$meta['email']=$params['email'];}
	if(isset($params['ordernumber'])){$meta['ordernumber']=$params['ordernumber'];}
	if(isset($params['invoice'])){$meta['invoice']=$params['invoice'];}
	foreach($params as $key=>$val){
    	if(isset($card[$key]) || isset($charge[$key]) || isset($meta[$key])){continue;}
    	if(preg_match('/^[\-\_]/',$key)){continue;}
    	if(preg_match('/^(card)_/',$key)){continue;}
    	$meta[$key]=$val;
    	//meta can only support up to 10 keys
    	if(count($meta)==10){break;}
	}
	if(count($meta)){
    	$charge['metadata']=$meta;
	}
	try{
		$response=Stripe_Charge::create($charge);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	$response['status']='failed';
		$response['approved']=false;
		if(isset($response['message'])){$response['response_reason_text']=$response['message'];}
		$response['message']=$response['response_reason_text'];
    	ksort($response);
    	return $response;
	}
	$response=stripeObject2Array($response);

	if(isset($response['values'])){
		$response=$response['values'];
		foreach($response as $key=>$val){
        	if(is_array($val) && count($val)==1 && isset($val['values'])){
            	$response[$key]=$val['values'];
			}
		}

		if(isNum($response['created'])){
        	$response['created_date']=date('Y-m-d H:i:s',$response['created']);
		}
		if($convert_currency==1){
    		$response['amount']=round(($response['amount']/100),2);
		}
		if($response['captured'] && $response['paid']){
			$response['status']='success';
			$response['approved']=true;
			$response['authorization_code']=$response['id'];
			$response['response_reason_text']='';
			$response['card_type']=$response['card']['brand'];
		}
		else{
			$response['status']='failed';
        	$response['approved']=false;
		}
		ksort($response);
		foreach($response as $key=>$val){
        	if(is_array($val) && count($val)==1 && isset($val['values'])){
            	$response[$key]=$val['values'];
			}
		}
		if(!isset($response['message'])){$response['message']=$response['response_reason_text'];}
		return $response;
	}
	if(!isset($response['message'])){$response['message']=$response['response_reason_text'];}
	return $response;
}
function stripeRefund($params=array()){
	//auth tokens are required
	$required=array(
		'apikey','amount','id',
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	if(!isset($params['currency'])){$params['currency']='usd';}
	else{$params['currency']=strtolower($params['currency']);}

	$auth=Stripe::setApiKey($params['apikey']);
	if($params['currency']=='usd' && strpos($params['amount'], ".")){
    	$params['amount']=round(($params['amount']*100),0);
	}
	$charge=array(
		"amount" => $params['amount'],
		"currency" => $params['currency'],
		"id" => $params['id']
	);
	$meta=array();
	if(isset($params['description'])){$meta['description']=$params['description'];}
	if(isset($params['ordernumber'])){$meta['ordernumber']=$params['ordernumber'];}
	if(isset($params['invoice'])){$meta['invoice']=$params['invoice'];}
	if(count($meta)){
    	$charge['metadata']=$meta;
	}
	try{
		$response=Stripe_Charge::create($charge);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	$response['status']='failed';

    	ksort($response);
    	return $response;
	}
	$response=stripeObject2Array($response);
	if(isset($response['values'])){
		$response=$response['values'];
		if(isNum($response['created'])){
        	$response['created_date']=date('Y-m-d H:i:s',$response['created']);
		}
		if($params['currency']=='usd'){
    		$response['amount']=round(($response['amount']/100),2);
		}
		if($response['captured'] && $response['paid']){$response['status']='success';}
		ksort($response);
		foreach($response as $key=>$val){
        	if(is_array($val) && count($val)==1 && isset($val['values'])){
            	$response[$key]=$val['values'];
			}
		}
		return $response;
	}
	return $response;
}
function stripeObject2Array($obj) {
	if(is_object($obj)){ $obj = (array) $obj;}
	if(is_array($obj)) {
		$new = array();
		foreach($obj as $key => $val){
			if(is_array($val)){
				if(!count($val)){continue;}
			}
			elseif(!strlen($val)){continue;}
			//remove non alpha-numeric characters in keys and lowercase them
			$newkey=strtolower(preg_replace('/[^a-z0-9]/i','',$key));
			if(
				$newkey=='unsavedvalues' || 
				$newkey=='transientvalues' || 
				$newkey=='apikey' ||
				$newkey=='exceptiontrace' ||
				$newkey=='httpbody' ||
				$newkey=='jsonbody'
				){continue;}
			$new[$newkey] = stripeObject2Array($val);
		}
	}
	else{
		$new = $obj;
	}
	return $new;
}
?>