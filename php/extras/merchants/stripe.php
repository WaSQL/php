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
	NOTE: amount must be in integer. A positive integer in the smallest currency unit (e.g 100 cents to charge $1.00, or 1 to charge �1, a 0-decimal currency) representing how much to charge the card
	
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
//require_once("{$progpath}/stripe/Stripe.php");
require_once("{$progpath}/stripe/init.php");
//---------- begin function stripeCreateCustomer--------------------
/**
* @describe creates a customer
* @param params array.  apikey, description, email, metadata, plan, qty,
*	address_line1,address_line2,address_city,address_state,address_zip,address_country
*	shipto_name,shipto_address,shipto_phone
*	cc_name,cc_number,cc_month,cc_year,cc_cvc
*	tax_percent, trial_end (timestamp)
*	coupon
* @return array
* @usage $ok=stripeCreateCustomer(array('apikey'=>$apikey,'email'=>"bob@nob.com","description" => "Bob Nob from Norway"));
* @reference https://stripe.com/docs/api?lang=php#create_customer
*/
function stripeCreateCustomer($params=array()){
	//auth tokens are required
	$required=array('apikey');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	$valid=array('apikey','account_balance','business_vat_id','coupon','description','email','metadata','plan','qty','shipping','source','tax_percent','trial_end');
	//look for shipping fields and put in shipping
	foreach($params as $k=>$v){
    	if(preg_match('/^shipto\_(name|address|phone)$/i',$k)){
        	$params['shipping'][$k]=$v;
        	unset($params[$k]);
		}
	}
	//look for credit card fields and put in source
	foreach($params as $k=>$v){
    	if(preg_match('/^address\_(line1|line2|city|state|zip|country)$/i',$k)){
        	$params['source'][$k]=$v;
        	unset($params[$k]);
		}
		if(preg_match('/^cc\_(month|year)$/i',$k,$m)){
        	$params['source']["exp_{$m[1]}"]=$v;
        	unset($params[$k]);
		}
		if(preg_match('/^cc\_(name|number|cvc)$/i',$k,$m)){
        	$params['source'][$m[1]]=$v;
        	unset($params[$k]);
		}
	}
	if(isset($params['source']['number'])){
    	$params['source']['object']='card';
	}
	//add any other invalid keys as meta data
	foreach($params as $k=>$v){
		if(!in_array($k,$valid)){
        	$params['metadata'][$k]=$v;
        	unset($params[$k]);
		}
	}
	//return $params;
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$response=\Stripe\Customer::create($params);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}

	$response=stripeObject2Array($response);
	$rec=array();
	if(isset($response['values']['id'])){
		foreach($response['values'] as $k=>$v){
			if(!is_array($v)){$rec[$k]=$v;}
			else{
            	switch($k){
                	case 'metadata':$rec[$k]=$v['values'];break;
                	case 'sources':
                		$rec['card']=$v['values']['data'][0]['values'];
                		unset($rec['card']['metadata']);
                	break;
					case 'subscriptions':
                		$rec['subscription']=$v['values']['data'][0]['values'];
                		unset($rec['subscription']['metadata']);
                		$rec['plan']=$rec['subscription']['plan']['values'];
                		unset($rec['plan']['metadata']);
                		unset($rec['subscription']['plan']);
                	break;
				}
			}
		}
		return $rec;
	}
	return $response;
}
//---------- begin function stripeGetCustomer--------------------
/**
* @describe creates a customer
* @param params array.  apikey, customer_id
* @return array
* @usage $ok=stripeGetCustomer(array('apikey'=>$apikey,'customer_id'=>"cus_9UzTvTLy7OVRP1"));
* @reference https://stripe.com/docs/api?lang=php#retrieve_customer
*/
function stripeGetCustomer($params=array()){
	//auth tokens are required
	$required=array('apikey','customer_id');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		$response=\Stripe\Customer::retrieve($params['customer_id']);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	if(isset($response['values'])){
		return $response['values'];
	}
	return $response;
}
//---------- begin function stripeDeleteCustomer--------------------
/**
* @describe creates a customer
* @param params array.  apikey, customer_id
* @return boolean
* @usage $ok=stripeDeleteCustomer(array('apikey'=>$apikey,'customer_id'=>"cus_9UzTvTLy7OVRP1"));
* @reference https://stripe.com/docs/api?lang=php#delete_customer
*/
function stripeDeleteCustomer($params=array()){
	//auth tokens are required
	$required=array('apikey','customer_id');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		$cu=\Stripe\Customer::retrieve($params['customer_id']);
		$cu->delete();
		return true;
	}
	catch (Exception $e){
		return false;
	}
	return true;
}
//---------- begin function stripeEditCustomer--------------------
/**
* @describe edits a customer
* @param params array.  apikey, customer_id
*	description, email, metadata, plan, qty,
*	address_line1,address_line2,address_city,address_state,address_zip,address_country
*	shipto_name,shipto_address,shipto_phone
*	tax_percent, trial_end (timestamp)
*	coupon
* @return array
* @usage $ok=stripeEditCustomer(array('apikey'=>$apikey,'customer_id'=>"cus_9UzTvTLy7OVRP1","plan" => "TSTPLN50Y"));
* @reference https://stripe.com/docs/api?lang=php#create_subscription
*/
function stripeEditCustomer($params=array()){
	//auth tokens are required
	$required=array('apikey','customer_id');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	$valid=array('apikey','account_balance','business_vat_id','coupon','description','email','metadata','plan','qty','shipping','source','tax_percent','trial_end');
	//look for shipping fields and put in shipping
	foreach($params as $k=>$v){
    	if(preg_match('/^shipto\_(name|address|phone)$/i',$k)){
        	$params['shipping'][$k]=$v;
        	unset($params[$k]);
		}
	}
	//add any other invalid keys as meta data
	foreach($params as $k=>$v){
		if(!in_array($k,$valid) && !in_array($k,$required)){
        	$params['metadata'][$k]=$v;
        	unset($params[$k]);
		}
	}
	//return $params;
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$cu=\Stripe\Customer::retrieve($params['customer_id']);
		//echo "cu".printValue($cu);
		unset($params['customer_id']);
		//echo printValue($params);
		foreach($params as $k=>$v){
			if(in_array($k,$valid) && !in_array($k,$required)){
            	$cu->{$k}=$v;
            	//echo "setting {$k} to {$v}<br>\n";
			}
		}
		$cu->save();
		return true;
	}
	catch (Exception $e){
    	return false;
	}
	return false;
}
//---------- begin function stripeListCustomers--------------------
/**
* @describe returns stripe customers
* @param params array.  Options are apikey, ending_before, starting_after, limit (default is 10, max is 1000)
* @return array
* @usage $balances=stripeBalance(array('apikey'=>$apikey));
* @reference https://stripe.com/docs/api?lang=php#list_customers
*/
function stripeListCustomers($params=array()){
	//echo printValue(get_declared_classes());exit;
	if(!isset($params['limit'])){
    	$params['limit']=1000;
	}
	//auth tokens are required
	$required=array('apikey');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$response=\Stripe\Customer::all($params);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	$recs=array();
	if(isset($response['values']['data'][0]['values'])){
        foreach($response['values']['data'] as $rec){
			$crec=array();
        	foreach($rec['values'] as $key=>$val){
				if(is_array($val)){continue;}
				if(!strlen($val)){continue;}
				$crec[$key]=$val;
			}
			if(isset($rec['values']['sources']['values']['totalcount'])){
            	$crec['sources']=$rec['values']['sources']['values']['totalcount'];
			}
			if(isset($rec['values']['subscriptions']['values']['totalcount'])){
            	$crec['subscriptions']=$rec['values']['subscriptions']['values']['totalcount'];
			}
			$recs[]=$crec;
		}
	}
	return $recs;
}
//---------- begin function stripeListPlans--------------------
/**
* @describe returns stripe plans
* @param params array.  Options are apikey, active, ids, , shippable, url, ending_before, starting_after, limit (default is 10, max is 1000)
* @return array
* @usage $balances=stripeBalance(array('apikey'=>$apikey));
* @reference https://stripe.com/docs/api?lang=php#list_products
*/
function stripeListPlans($params=array()){
	if(!isset($params['limit'])){$params['limit']=1000;}
	//auth tokens are required
	$required=array('apikey');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$response=\Stripe\Plan::all($params);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	$recs=array();
	if(isset($response['values']['data'][0]['values'])){
        foreach($response['values']['data'] as $rec){
			$crec=array();
        	foreach($rec['values'] as $key=>$val){
				if(is_array($val)){continue;}
				if(!strlen($val)){continue;}
				$crec[$key]=$val;
			}
			$recs[]=$crec;
		}
	}
	return $recs;
}

//---------- begin function stripeCreatePlan--------------------
/**
* @describe creates a subscription
* @param params array.  Options are apikey, id, amount, currency, interval, name
* @return array
* @usage $ok=stripeCreatePlan(array('apikey'=>$apikey,'customer'=>"cus_9UzTvTLy7OVRP1","plan" => "TSTPLN50Y"));
* @reference https://stripe.com/docs/api?lang=php#create_subscription
*/
function stripeCreatePlan($params=array()){
	//auth tokens are required
	$required=array('apikey','id','amount','currency','interval','name');
	$optional=array('interval_count','metadata','statement_descriptor','trial_period_days');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	//add any other fields to metadata
	foreach($params as $k=>$v){
    	if(!in_array($k,$required) && !in_array($k,$optional)){
        	$params['metadata'][$k]=$v;
        	unset($params[$k]);
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$response=\Stripe\Plan::create($params);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	return $response;
	$recs=array();
	if(isset($response['values']['data'][0]['values'])){
        foreach($response['values']['data'] as $rec){
			$crec=array();
        	foreach($rec['values'] as $key=>$val){
				if(is_array($val)){continue;}
				if(!strlen($val)){continue;}
				$crec[$key]=$val;
			}
			$recs[]=$crec;
		}
	}
	return $recs;
}
//---------- begin function stripeCreateSubscription--------------------
/**
* @describe creates a subscription
* @param params array.  Options are apikey, customer, plan
* @return array
* @usage $ok=stripeCreateSubscription(array('apikey'=>$apikey,'customer'=>"cus_9UzTvTLy7OVRP1","plan" => "TSTPLN50Y"));
* @reference https://stripe.com/docs/api?lang=php#create_subscription
*/
function stripeCreateSubscription($params=array()){
	//auth tokens are required
	$required=array('apikey','customer','plan');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$response=\Stripe\Subscription::create($params);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	$recs=array();
	if(isset($response['values']['data'][0]['values'])){
        foreach($response['values']['data'] as $rec){
			$crec=array();
        	foreach($rec['values'] as $key=>$val){
				if(is_array($val)){continue;}
				if(!strlen($val)){continue;}
				$crec[$key]=$val;
			}
			$recs[]=$crec;
		}
	}
	return $recs;
}
//---------- begin function stripeEditSubscription--------------------
/**
* @describe edits a Subscription
* @param params array.  apikey, customer_id
*	description, email, metadata, plan, qty,
*	address_line1,address_line2,address_city,address_state,address_zip,address_country
*	shipto_name,shipto_address,shipto_phone
*	tax_percent, trial_end (timestamp)
*	coupon
* @return array
* @usage $ok=stripeEditSubscription(array('apikey'=>$apikey,'customer_id'=>"cus_9UzTvTLy7OVRP1","plan" => "TSTPLN50Y"));
* @reference https://stripe.com/docs/api?lang=php#create_subscription
*/
function stripeEditSubscription($params=array()){
	//auth tokens are required
	$required=array('apikey','subscription_id');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	$valid=array(
		'apikey','subscription_id','coupon','metadata','plan','quantity','prorate','prorate_date','source'
	);

	//add any other invalid keys as meta data
	foreach($params as $k=>$v){
		if(!in_array($k,$valid) && !in_array($k,$required)){
        	$params['metadata'][$k]=$v;
        	unset($params[$k]);
		}
	}
	//return $params;
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$cu=\Stripe\Subscription::retrieve($params['subscription_id']);
		//echo "cu".printValue($cu);
		unset($params['subscription_id']);
		//echo printValue($params);
		foreach($params as $k=>$v){
			if(in_array($k,$valid) && !in_array($k,$required)){
            	$cu->{$k}=$v;
            	//echo "setting {$k} to {$v}<br>\n";
			}
		}
		$cu->save();
		return true;
	}
	catch (Exception $e){
    	return false;
	}
	return false;
}
//---------- begin function stripeListProducts--------------------
/**
* @describe returns stripe products
* @param params array.  Options are apikey, active, ids, , shippable, url, ending_before, starting_after, limit (default is 10, max is 1000)
* @return array
* @usage $balances=stripeBalance(array('apikey'=>$apikey));
* @reference https://stripe.com/docs/api?lang=php#list_products
*/
function stripeListProducts($params=array()){
	if(!isset($params['limit'])){$params['limit']=1000;}
	if(!isset($params['active'])){$params['active']=true;}
	//auth tokens are required
	$required=array('apikey');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$response=\Stripe\Product::all($params);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	$recs=array();
	if(isset($response['values']['data'][0]['values'])){
        foreach($response['values']['data'] as $rec){
			$crec=array();
        	foreach($rec['values'] as $key=>$val){
				if(is_array($val)){continue;}
				if(!strlen($val)){continue;}
				$crec[$key]=$val;
			}
			$recs[]=$crec;
		}
	}
	return $recs;
}

//---------- begin function stripeListSKUs--------------------
/**
* @describe returns stripe skus
* @param params array.  Options are apikey, active, attributes, in_stock, product, ending_before, starting_after, limit (default is 10, max is 1000)
* @return array
* @usage $skus=stripeListSKUs(array('apikey'=>$apikey));
* @reference https://stripe.com/docs/api?lang=php#list_skus
*/
function stripeListSKUs($params=array()){
	if(!isset($params['limit'])){$params['limit']=1000;}
	if(!isset($params['active'])){$params['active']=true;}
	//auth tokens are required
	$required=array('apikey');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		unset($params['apikey']);
		$response=\Stripe\SKU::all($params);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	$recs=array();
	if(isset($response['values']['data'][0]['values'])){
        foreach($response['values']['data'] as $rec){
			$crec=array();
        	foreach($rec['values'] as $key=>$val){
				if(is_array($val)){continue;}
				if(!strlen($val)){continue;}
				$crec[$key]=$val;
			}
			$recs[]=$crec;
		}
	}
	return $recs;
}


//---------- begin function stripeBalance--------------------
/**
* @describe returns stripe balances
* @param params array
* @return array
* @usage $balances=stripeBalance(array('apikey'=>$apikey));
*/
function stripeBalance($params=array()){
	//auth tokens are required
	$required=array(
		'apikey'
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		$response=\Stripe\Balance::retrieve();
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	$balances=array();
	if(isset($response['values']['available'][0]['values'])){
        $balances['available']=$response['values']['available'][0]['values'];
	}
	if(isset($response['values']['pending'][0]['values'])){
        $balances['pending']=$response['values']['pending'][0]['values'];
	}
	return $balances;
}
//---------- begin function stripeRetrieve--------------------
/**
* @describe returns stripe charge info
* @param params array
* @return array
* @usage $charge=stripeRetrieve(array('apikey'=>$apikey,'id'=>$id));
*/
function stripeRetrieve($params=array()){
	//auth tokens are required
	$required=array(
		'apikey','id'
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	try{
		$auth=\Stripe\Stripe::setApiKey($params['apikey']);
		$response=\Stripe\Charge::retrieve($params['id']);
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	return $response;
	}
	$response=stripeObject2Array($response);
	$charge=array();
	if(isset($response['values']['amount'])){
        $charge['amount']=round($response['values']['amount']/100,2);
	}
	if(isset($response['values']['amountrefunded'])){
        $charge['refunded']=round($response['values']['amountrefunded']/100,2);
	}
	if(isset($response['values']['id'])){
        $charge['id']=$response['values']['id'];
	}
	if(isset($response['values']['description'])){
        $charge['description']=$response['values']['description'];
	}
	if(isset($response['values']['paid'])){
        $charge['paid']=$response['values']['paid'];
	}
	if(isset($response['values']['status'])){
        $charge['status']=$response['values']['status'];
	}
	//has this been disputed?
	if(isset($response['values']['dispute']['values'])){
		$charge['status'].=' ** DISPUTED **';
	}
	//are we in test mode?
	if(!isset($response['values']['livemode']) || $response['values']['livemode'] != 1){
        $charge['status'] .= ' ** TEST MODE **';
	}
	if(isset($response['values']['currency'])){
        $charge['currency']=$response['values']['currency'];
	}
	if(isset($response['values']['created'])){
        $charge['charge_date']=date('Y-m-d H:i:s',$response['values']['created']);
	}
	if(isset($response['values']['source']['values'])){
        $charge['cc_type']=$response['values']['source']['values']['brand'];
        $charge['cc_last4']=$response['values']['source']['values']['last4'];
        $charge['cc_month']=$response['values']['source']['values']['expmonth'];
        $charge['cc_year']=$response['values']['source']['values']['expyear'];
	}
	if(isset($response['values']['metadata']['values'])){
        foreach($response['values']['metadata']['values'] as $k=>$v){
        	$charge[$k]=$v;
		}
	}
	ksort($charge);
	return $charge;
}
//---------- begin function stripeCharge--------------------
/**
* @describe attempts a charge
* @param params array - required fields are apikey, amount, card_num, and description
* @return array
* @usage $auth=stripeCharge($auth);
*/
function stripeCharge($params=array()){
	//auth tokens are required
	$required=array('apikey','amount','description');
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
	//backward compatibility
	if(isset($params['cc_num']) && !isset($params['card_num'])){$params['card_num']=$params['cc_num'];}
	if(isset($params['cc_exp_month']) && !isset($params['card_exp_month'])){$params['card_exp_month']=$params['cc_exp_month'];}
	if(isset($params['cc_exp_year']) && !isset($params['card_exp_year'])){$params['card_exp_year']=$params['cc_exp_year'];}
	if(isset($params['cc_cvv2']) && !isset($params['card_cvc'])){$params['card_cvc']=$params['cc_cvv2'];}
	//either card_num or source is required
	$ok=0;
	if(isset($params['card_num']) && strlen($params['card_num'])){$ok=1;}
	elseif(isset($params['source']) && strlen($params['source'])){$ok=1;}
	if($ok==0){
		$response=array(
			'status'	=> 'failed',
			'response_reason_text' => "Error: Missing required param - card_num or source",
			'approved'	=> false
		);
		$response['message']=$response['response_reason_text'];
		ksort($response);
		return $response;
	}
	if(!isset($params['currency'])){$params['currency']='usd';}
	else{$params['currency']=strtolower($params['currency']);}

	$auth=\Stripe\Stripe::setApiKey($params['apikey']);
	//echo printValue($params);exit;
	$card=array();
	if(isset($params['card_num'])){
		$card['number']=$params['card_num'];
		$card['exp_month']=$params['card_exp_month'];
		$card['exp_year']=$params['card_exp_year'];
		if(isset($params['card_cvc'])){$card['cvc']=$params['card_cvc'];}
		elseif(isset($params['card_cvv2'])){$card['cvc']=$params['card_cvv2'];}
		elseif(isset($params['cc_cvv2'])){$card['cvc']=$params['cc_cvv2'];}
	}


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
		"description" => $params['description']
	);
	if(count($card)){
    	$charge['card'] = $card;
	}
	else{
    	$charge['source']=$params['source'];
	}
	//statement_descriptor - what to show on customers credit card statement - up to 22 characters
	if(isset($params['statement_descriptor'])){
    	$charge['statement_descriptor']=$params['statement_descriptor'];
	}
	if(isset($params['receipt_email'])){
    	$charge['receipt_email']=$params['receipt_email'];
	}

	$meta=array();
	if(isset($params['email'])){$meta['email']=$params['email'];}
	if(isset($params['ordernumber'])){$meta['ordernumber']=$params['ordernumber'];}
	if(isset($params['invoice'])){$meta['invoice']=$params['invoice'];}
	foreach($params as $key=>$val){
    	if(isset($card[$key]) || isset($charge[$key]) || isset($meta[$key])){continue;}
    	if(preg_match('/^[\-\_]/',$key)){continue;}
    	if(preg_match('/^(card)_/i',$key)){continue;}
    	if(preg_match('/^(apikey|source|statement_descriptor|receipt_email)$/i',$key)){continue;}
    	$meta[$key]=$val;
    	//meta can only support up to 10 keys
    	if(count($meta)==10){break;}
	}
	if(count($meta)){
    	$charge['metadata']=$meta;
	}
	try{
		$response=\Stripe\Charge::create($charge);
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
	if(isset($params['id']) && !isset($params['charge'])){
    	$params['charge']=$params['id'];
	}
	$required=array(
		'apikey','amount','charge',
	);
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "Error: Missing required param '{$key}'";
		}
	}
	if(!isset($params['currency'])){$params['currency']='usd';}
	else{$params['currency']=strtolower($params['currency']);}

	$auth=\Stripe\Stripe::setApiKey($params['apikey']);
	if($params['currency']=='usd' && strpos($params['amount'], ".")){
    	$params['amount']=round(($params['amount']*100),0);
	}
	$charge=array(
		"amount" => $params['amount'],
		"currency" => $params['currency'],
		"charge" => $params['charge'],
	);
	$meta=array();
	if(isset($params['description'])){$meta['description']=$params['description'];}
	if(isset($params['ordernumber'])){$meta['ordernumber']=$params['ordernumber'];}
	if(isset($params['invoice'])){$meta['invoice']=$params['invoice'];}
	if(count($meta)){
    	$charge['metadata']=$meta;
	}
	//echo printValue($charge);exit;

	try{
		$stripe_charge = \Stripe\Charge::retrieve($charge['charge']);
		$response = $stripe_charge->refund(array("amount" => $charge['amount']));
	}
	catch (Exception $e){
    	$response=stripeObject2Array($e);
    	$response['status']='failed';
		$response['params']=$charge;
    	ksort($response);
    	return $response;
	}
	//echo printValue($response);exit;
	$response=stripeObject2Array($response);
	if(isset($response['values'])){
		$response=$response['values'];
		if(isNum($response['created'])){
        	$response['created_date']=date('Y-m-d H:i:s',$response['created']);
        	$response['status']='success';
		}
		if($params['currency']=='usd'){
    		$response['amount']=round(($response['amount']/100),2);
		}
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
	//echo printValue($obj);exit;
	if(is_array($obj)) {
		$new = array();
		foreach($obj as $key => $val){
			if(is_object($val)){ $val = (array) $val;}
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