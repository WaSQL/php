<?php
$progpath=dirname(__FILE__);
require_once "{$progpath}/authnet/AuthorizeNet.php";
/* Functions for Authorize.net Advanced Integration Method (AIM)

Valid functions:
	authnetAuthorizeAndCapture - authorizes and charges the card
	authnetAuthorize - authorizes but does not charge the card
	authnetCapture - captures previously authorized - requires tranaction_id as a param
	authnetVoid - voids previous transactions - requires tranaction_id as a param
	authnetCredit - credits previous transactions - requires tranaction_id,amount,last 4 of credit card

To email receipt to customer add this to the auth or authAndCapture params
	'-receipt_email' => 'customer@email.com'
	You can optionally add an email header and footer
		'-receipt_header'	=> $header
		'-receipt_footer'	=> $footer

Example Usage:
loadExtras('authnet');
$response=authnetAuthorizeAndCapture(array(
	'-auth_id'		=> "123413242314",
	'-auth_key'		=> "14231423143",
	'-blank'		=> 0,
	'card_num' 		=> '4007000000027',
	'exp_date'		=> '04/15',
	'amount'		=> 256.67,
	'invoice_num'	=> 'INV345-33',
	'company'		=> "John Doe Company",
	'category'		=> "Custom Field"
));
//echo "response".printValue($response);
if($response['approved']){
	//success
	echo 'Thanks for your order';
}
else{
	//failed
	echo $response['response_reason_text'];
}

Additonal Info:
	http://developer.authorize.net/guides/AIM/
	Evalon InternetSecure is also compatible with Authorize.net as follows:
	https://www.internetsecure.com/merchants/ShowPage.asp?page=SCAN&q=7
	
	12/8/2012
		Download SDK: http://developer.authorize.net/downloads/
		PHP SDK Help: http://developer.authorize.net/guides/DPM/wwhelp/wwhimpl/js/html/wwhelp.htm#href=integratingPHP.html
		read the README.html file - it has all you should need.

*/


//---------- begin function authnetAuthorizeAndCapture--------------------
/**
* @describe authorizes and charges the card through authorize.net API
* @param params array
* 	-auth_id - Your Authorize.net Auth ID
* 	-auth_key- Your Authorize.net Auth Key
*	card_num - credit card number
*	exp_date - credit card expire date in MM/YY format
*	amount - amount to authorize
*	[-blank] boolean - if false do not return keys with blank values. Defaults to true
*	[-sandbox] boolean - if true uses the Authorize.net Sandbox. Defaults to false
*	[-debug] boolean - if true returns debug info. Defaults to false
*	[-receipt_email] string - send receipt of transaction to this email address
*	[-receipt_header] string - header html/text to add to email
*	[-receipt_footer]  string - footer html/text to add to email
*	Other authnet approved param keys are:
*		amount,card_num,exp_date,card_code,
*		invoice_num,po_num,description,
*		first_name,last_name,company,
*		address,city,state,zip,country,
*		email,phone,
*		freight,tax,
*		ship_to_first_name,ship_to_last_name,ship_to_company,
*		ship_to_address,ship_to_city,ship_to_state,ship_to_zip,ship_to_country
*	Any other param keys used will be passed in as a Custom Field
* @return array
* @usage
* loadExtras('authnet');
* $response=authnetAuthorizeAndCapture(array(
* 	'-auth_id'		=> "123413242314",
* 	'-auth_key'		=> "14231423143",
* 	'-blank'		=> 0,
* 	'card_num' 		=> '4007000000027',
* 	'exp_date'		=> '04/15',
* 	'amount'		=> 256.67,
* 	'invoice_num'	=> time(),
* 	'company'		=> "John Doe Company",
* 	'category'		=> "Custom Field"
* ));
* if($response['approved']){
* 	//success
* 	echo 'Thanks for your order';
* }
* else{
* 	//failed
* 	echo $response['response_reason_text'];
* }
*/
function authnetAuthorizeAndCapture($params=array()){
	$params['-method']='authcapture';
	return authnetAIM($params);
}
//---------- begin function authnetAuthorize--------------------
/**
* @describe authorizes, but does NOT charge, the card through authorize.net API
* @param params array
* 	-auth_id - Your Authorize.net Auth ID
* 	-auth_key- Your Authorize.net Auth Key
*	card_num - credit card number
*	exp_date - credit card expire date in MM/YY format
*	amount - amount to authorize
*	[-blank] boolean - if false do not return keys with blank values. defaults to true
*	[-sandbox] boolean - if true uses the Authorize.net Sandbox. Defaults to false
*	[-debug] boolean - if true returns debug info. Defaults to false
*	[-receipt_email] string - send receipt of transaction to this email address
*	[-receipt_header] string - header html/text to add to email
*	[-receipt_footer]  string - footer html/text to add to email
*	Other authnet approved param keys are:
*		amount,card_num,exp_date,card_code,
*		invoice_num,po_num,description,
*		first_name,last_name,company,
*		address,city,state,zip,country,
*		email,phone,
*		freight,tax,
*		ship_to_first_name,ship_to_last_name,ship_to_company,
*		ship_to_address,ship_to_city,ship_to_state,ship_to_zip,ship_to_country
*	Any other param keys used will be passed in as a Custom Field
* @return array
* @usage
* loadExtras('authnet');
* $response=authnetAuthorize(array(
* 	'-auth_id'		=> "123413242314",
* 	'-auth_key'		=> "14231423143",
* 	'-blank'		=> 0,
* 	'card_num' 		=> '4007000000027',
* 	'exp_date'		=> '04/15',
* 	'amount'		=> 256.67,
* 	'invoice_num'	=> time(),
* 	'company'		=> "John Doe Company",
* 	'category'		=> "Custom Field"
* ));
* if($response['approved']){
* 	//success
* 	echo 'your card was authorized';
* }
* else{
* 	//failed
* 	echo $response['response_reason_text'];
* }
*/
function authnetAuthorize($params=array()){
	$params['-method']='auth';
	return authnetAIM($params);
}
//---------- begin function authnetCapture--------------------
/**
* @describe charges the card using an existing authorization key through authorize.net API
* @param params array
* 	-auth_id - Your Authorize.net Auth ID
* 	-auth_key- Your Authorize.net Auth Key
*	transaction_id - prior authOnly transaction ID to charge
*	[-blank] boolean - if false do not return keys with blank values. defaults to true
*	[-sandbox] boolean - if true uses the Authorize.net Sandbox. Defaults to false
*	[-debug] boolean - if true returns debug info. Defaults to false
*	[-receipt_email] string - send receipt of transaction to this email address
*	[-receipt_header] string - header html/text to add to email
*	[-receipt_footer]  string - footer html/text to add to email
* @return array
* @usage
* loadExtras('authnet');
* $response=authnetCapture(array(
* 	'-auth_id'		=> "123413242314",
* 	'-auth_key'		=> "14231423143",
* 	'transaction_id'=> '098124374'
* ));
* if($response['approved']){
* 	//success
* 	echo 'your card was authorized';
* }
* else{
* 	//failed
* 	echo $response['response_reason_text'];
* }
*/
function authnetCapture($params=array()){
	$params['-method']='capture';
	return authnetAIM($params);
}
//---------- begin function authnetVoid--------------------
/**
* @describe voids an existing transaction through authorize.net API
* @param params array
* 	-auth_id - Your Authorize.net Auth ID
* 	-auth_key- Your Authorize.net Auth Key
*	transaction_id - transaction ID of charge to void
*	[-blank] boolean - if false do not return keys with blank values. defaults to true
*	[-sandbox] boolean - if true uses the Authorize.net Sandbox. Defaults to false
*	[-debug] boolean - if true returns debug info. Defaults to false
*	[-receipt_email] string - send receipt of transaction to this email address
*	[-receipt_header] string - header html/text to add to email
*	[-receipt_footer]  string - footer html/text to add to email
* @return array
* @usage
* loadExtras('authnet');
* $response=authnetVoid(array(
* 	'-auth_id'		=> "123413242314",
* 	'-auth_key'		=> "14231423143",
* 	'transaction_id'=> '098124374'
* ));
* if($response['approved']){
* 	//success
* 	echo 'your charge was voided';
* }
* else{
* 	//failed
* 	echo $response['response_reason_text'];
* }
*/
function authnetVoid($params=array()){
	$params['-method']='void';
	return authnetAIM($params);
}
//---------- begin function authnetCredit--------------------
/**
* @describe credits/refunds an existing transaction through authorize.net API
* @param params array
* 	-auth_id - Your Authorize.net Auth ID
* 	-auth_key- Your Authorize.net Auth Key
*	transaction_id - transaction ID of charge to credit/refund
*	amount - amount to credit/refund
*	card_num - last 4 digits of credit card number
*	[-blank] boolean - if false do not return keys with blank values. defaults to true
*	[-sandbox] boolean - if true uses the Authorize.net Sandbox. Defaults to false
*	[-debug] boolean - if true returns debug info. Defaults to false
*	[-receipt_email] string - send receipt of transaction to this email address
*	[-receipt_header] string - header html/text to add to email
*	[-receipt_footer]  string - footer html/text to add to email
* @return array
* @usage
* loadExtras('authnet');
* $response=authnetVoid(array(
* 	'-auth_id'		=> "123413242314",
* 	'-auth_key'		=> "14231423143",
* 	'transaction_id'=> '098124374',
*	'amount'		=> 12.22,
*	'card_num'		=> '3443'
* ));
* if($response['approved']){
* 	//success
* 	echo 'your charge was refunded';
* }
* else{
* 	//failed
* 	echo $response['response_reason_text'];
* }
*/
function authnetCredit($params=array()){
	$params['-method']='credit';
	return authnetAIM($params);
}

//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function authnetAIM($xparams=array()){
	//-blank=0 will not return keys with blank values
	//translate mapped fields
	$params=authnetMapFields($xparams);
	//verify method
	if(!isset($params['-method'])){
		$rtn = array(
			'authnet_status'	=> "error",
			'authnet_error'		=> "missing method",
		);
		if($params['-debug']){
			$rtn['authnet_xparams']=$xparams;
			$rtn['authnet_params']=$params;
		}
		return $rtn;
	}
	//default sandbox to false
	if(!isset($params['-sandbox'])){$params['-sandbox']=false;}
	//verify auth params exist
	if(!isset($params['-auth_id']) || !isset($params['-auth_key'])){
		$rtn = array(
			'authnet_status'	=> "error",
			'authnet_error'		=> "missing auth params",
		);
		if($params['-debug']){
			$rtn['authnet_xparams']=$xparams;
			$rtn['authnet_params']=$params;
		}
		return $rtn;
	}
	define("AUTHORIZENET_API_LOGIN_ID", $params['-auth_id']);
	define("AUTHORIZENET_TRANSACTION_KEY", $params['-auth_key']);
	define("AUTHORIZENET_SANDBOX", $params['-sandbox']);
	$validkeys=authnetValidParams();
	try{
		$auth = new AuthorizeNetAIM;
		//email receipt to customer?
		if(isset($params['-receipt_email'])){
			$auth->email_customer=$params['-receipt_email'];
            if(isset($params['-receipt_header'])){
				$auth->header_email_receipt=$params['-receipt_header'];
			}
            if(isset($params['-receipt_footer'])){
                $auth->footer_email_receipt=$params['-receipt_footer'];
			}
		}
		switch(strtolower($params['-method'])){
        	case 'authorizeandcapture':
        	case 'authcapture':
        		foreach($params as $key=>$val){
					if(stringBeginsWith($key,'-')){continue;}
					if(is_array($val)){continue;}
					if(in_array($key,$validkeys)){$auth->$key = $val;}
					else{$auth->setCustomField($key,$val);}
				}
				//add line items?
				if(isset($params['-items']) && is_array($params['-items'])){
                	foreach($params['-items'] as $item){
                    	//usage: $auth->addLineItem(id,name,description,quantity,price,taxable);
                    	//id
                    	if(isset($item['itemid'])){$id=$item['itemid'];}
                    	elseif(isset($item['sku'])){$id=$item['sku'];}
                    	//name
                    	if(isset($item['name'])){$name=$item['name'];}
                    	elseif(isset($item['base_sku'])){$name=$item['base_sku'];}
                    	//description
                    	if(isset($item['description'])){$description=$item['description'];}
                    	elseif(isset($item['desc'])){$description=$item['desc'];}
                    	//quantity
                    	if(isset($item['quantity'])){$quantity=$item['quantity'];}
                    	elseif(isset($item['qty'])){$quantity=$item['qty'];}
                    	//price
                    	if(isset($item['price'])){$price=$item['price'];}
                    	elseif(isset($item['retail_price'])){$price=$item['retail_price'];}
                    	//taxable
                    	$taxable=isset($item['taxable'])?$item['taxable']:'N';
                    	//add line item
                    	$auth->addLineItem($id,$name,$description,$quantity,$price,$taxable);
					}
				}
        		$response = $auth->authorizeAndCapture();
        		break;
        	case 'auth':
        	case 'authonly':
        	case 'authorizeonly':
        		foreach($params as $key=>$val){
					if(stringBeginsWith($key,'-')){continue;}
					if(is_array($val)){continue;}
					if(in_array($key,$validkeys)){$auth->$key = $val;}
					else{$auth->setCustomField($key,$val);}
				}
        		$response = $auth->authorizeOnly();
        		break;
        	case 'capture':
        	case 'priorauthcapture':
        		$response = $auth->priorAuthCapture($params['transaction_id']);
        		break;
        	case 'void':
        		$response = $auth->void($params['transaction_id']);
        		break;
        	case 'credit':
        	case 'refund':
        		//transaction_id,amount,card_num(last 4 digits)
        		$response = $auth->credit($params['transaction_id'],$params['amount'],$params['card_num']);
        		break;
		}
	}
	catch(Exception $e){
		$rtn = array(
			'authnet_status'	=> "exception",
			'authnet_exception'	=> $e
		);
		if($params['-debug']){
			$rtn['authnet_xparams']=$xparams;
			$rtn['authnet_params']=$params;
			$rtn['authnet_auth']=$auth;
		}
		return $rtn;
	}
	$rtn=array(
		'authnet_status'	=> "success",
	);
	//convert response to an array
	foreach($response as $key => $val){
		$key=strtolower(trim((string)$key));
		//remove the response key since it is not needed - just a string of the other keys
		if($key=='response'){continue;}
		$val=trim((string)$val);
		//remove blanks if requested
		if(isset($params['-blank']) && $params['-blank']==0 && !strlen($val)){continue;}
		$rtn[$key]=$val;
	}
	ksort($rtn);
	//add debug info?
	if($params['-debug']){
		$rtn['authnet_auth']=$auth;
		$rtn['authnet_xparams']=$xparams;
		$rtn['authnet_params']=$params;
		$rtn['authnet_response']=$response;
	}
	return $rtn;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function authnetMapFields($params=array()){
	$fieldmap=array(
		'description'		=> 'description',
		'first_name'		=> 'first_name',
		'last_name'			=> 'last_name',
		'firstname'			=> 'first_name',
		'lastname'			=> 'last_name',
		'company'			=> 'company',
		'address'			=> 'address',
		'city'				=> 'city',
		'state'				=> 'state',
		'zip'				=> 'zip',
		'zipcode'			=> 'zip',
		'country'			=> 'country',
		'phone'				=> 'phone',
		'email'				=> 'email',
		'billtofirstname'	=> 'first_name',
		'billtolastname'	=> 'last_name',
		'billtocompany'		=> 'company',
		'billtoaddress'		=> 'address',
		'billtocity'		=> 'city',
		'billtostate'		=> 'state',
		'billtozipcode'		=> 'zip',
		'billtozip'			=> 'zip',
		'billtocountry'		=> 'country',
		'billtotelephone'	=> 'phone',
		'billtoemail'		=> 'email',
		'billto_firstname'	=> 'first_name',
		'billto_lastname'	=> 'last_name',
		'billto_first_name'	=> 'first_name',
		'billto_last_name'	=> 'last_name',
		'billto_company'	=> 'company',
		'billto_address'	=> 'address',
		'billto_city'		=> 'city',
		'billto_state'		=> 'state',
		'billto_zipcode'	=> 'zip',
		'billto_zip'		=> 'zip',
		'billto_country'	=> 'country',
		'billto_telephone'	=> 'phone',
		'billto_email'		=> 'email',
		'customer_id'		=> 'cust_id',
		'customer_ip'		=> 'customer_ip',
		'invoice_num'		=> 'x_invoice_num',
		'order_id'			=> 'x_invoice_num',
		'shipto_firstname'	=> 'ship_to_first_name',
		'shipto_lastname'	=> 'ship_to_last_name',
		'shipto_first_name'	=> 'ship_to_first_name',
		'shipto_last_name'	=> 'ship_to_last_name',
		'shipto_company'	=> 'ship_to_company',
		'shipto_address'	=> 'ship_to_address',
		'shipto_city'		=> 'ship_to_city',
		'shipto_state'		=> 'ship_to_state',
		'shipto_zipcode'	=> 'ship_to_zip',
		'shipto_zip'		=> 'ship_to_zip',
		'shipto_country'	=> 'ship_to_country',
		'shiptofirstname'	=> 'ship_to_first_name',
		'shiptolastname'	=> 'ship_to_last_name',
		'shiptocompany'		=> 'ship_to_company',
		'shiptoaddress'		=> 'ship_to_address',
		'shiptocity'		=> 'ship_to_city',
		'shiptostate'		=> 'ship_to_state',
		'shiptozipcode'		=> 'ship_to_zip',
		'shiptozip'			=> 'ship_to_zip',
		'shiptocountry'		=> 'ship_to_country',
		'ship_firstname'	=> 'ship_to_first_name',
		'ship_lastname'		=> 'ship_to_last_name',
		'ship_first_name'	=> 'ship_to_first_name',
		'ship_last_name'	=> 'ship_to_last_name',
		'ship_company'		=> 'ship_to_company',
		'ship_address'		=> 'ship_to_address',
		'ship_city'			=> 'ship_to_city',
		'ship_state'		=> 'ship_to_state',
		'ship_zip'			=> 'ship_to_zip',
		'ship_zipcode'		=> 'ship_to_zip',
		'ship_country'		=> 'ship_to_country'
	);
	//split name
	if(isset($params['shiptoname'])){
    	list($params['shipto_firstname'],$params['shipto_lastname'])=preg_split('/\ /',$params['shiptoname'],2);
    	unset($params['shiptoname']);
	}
	if(isset($params['billtoname'])){
    	list($params['billto_firstname'],$params['billto_lastname'])=preg_split('/\ /',$params['billtoname'],2);
    	unset($params['billtoname']);
	}
	//join address
	if(isset($params['billtoaddress1'])){
		$params['billtoaddress']=$params['billtoaddress1'];
		unset($params['billtoaddress1']);
		if(isset($params['billtoaddress2'])){
			$params['billtoaddress'].=', '.$params['billtoaddress2'];
			unset($params['billtoaddress2']);
		}
	}
	if(isset($params['shiptoaddress1'])){
		$params['shiptoaddress']=$params['shiptoaddress1'];
		unset($params['shiptoaddress1']);
		if(isset($params['shiptoaddress2'])){
			$params['shiptoaddress'].=', '.$params['shiptoaddress2'];
			unset($params['shiptoaddress2']);
		}
	}
	foreach($fieldmap as $mfield=>$afield){
    	if(isset($params[$mfield])){
			$params[$afield]=$params[$mfield];
			unset($params[$mfield]);
		}
	}
	return $params;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function authnetValidParams(){
	return array(
		'amount','card_num','exp_date','card_code',
		'invoice_num','po_num','description',
		'first_name','last_name','company',
		'address','city','state','zip','country',
		'email','phone',
		'freight','tax',
		'ship_to_first_name','ship_to_last_name','ship_to_company',
		'ship_to_address','ship_to_city','ship_to_state','ship_to_zip','ship_to_country'
		);
}
//-----------------------------------------------------------------------------------------
//--- NOTE: the functions below this line are for backwards compatibility only. no need to use them.
//-----------------------------------------------------------------------------------------



//---------- begin function
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function authnetAuthOnly($params=array()){
	$params['x_type']="AUTH_ONLY";
	return authnetCharge($params);
	}
//---------- begin function
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function authnetPriorAuthCapture($params=array()){
	$params['x_type']="PRIOR_AUTH_CAPTURE";
	return authnetCharge($params);
	}
//---------- begin function
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function authnetAuthCapture($params=array()){
	$params['x_type']="AUTH_CAPTURE";
	return authnetCharge($params);
	}
//---------- begin function
/**
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function authnetCharge($params=array()){
	/*	-- Required params:
		authId 		- authorize.net login id
		authKey 	- authorize.net transaction key
		ccNumber 	- credit card number
		ccExpire	- credit care expiration date in MMYY format
		description - description of charge
		amount		- amount to charge

		--Optional params
		firstname	- First Name  - must match billing first name
		lastname	- Last Name  - must match billing last name
		address		- Address - must match billing address
		city		- City - must match billing city
		state		- State - must match billing state
		zip			- Zip/Postal Code - must match billing zip
		birthDay	- Customer Birth Day in format DD
		birthMonth	- Customer Birth Month in format MM
		birthYear	- Customer Birth Year in format YYYY
		special		- Special promotion Note that customer will see on their charge
	*/
	$result=array();
	if($params['-debug']){
		$result['params_in']=$params;
		ksort($result['params_in']);
		}
	if(!isset($params['x_type'])){$params['x_type']="AUTH_CAPTURE";}
	//required values
	$authOpts= array(
		"x_login"				=> $params['authId'],
		"x_version"				=> "3.1",
		"x_delim_char"			=> "|",
		"x_delim_data"			=> "TRUE",
		"x_method"				=> "CC",
	 	"x_tran_key"			=> $params['authKey'],
	 	"x_relay_response"		=> "FALSE",
		"x_card_num"			=> $params['ccNumber'],
		"x_exp_date"			=> $params['ccExpire']
		);
	/* x_type - type of authorization
		AUTH_CAPTURE - authorize and capture the funds now
		AUTH_ONLY - authorize but do NOT capture funds right now (use PRIOR_AUTH_CAPTURE and x_trans_id to capture later)
		PRIOR_AUTH_CAPTURE - caputure funds of a previous AUTH_ONLY transaction. requires x_trans_id
	*/
	switch(strtoupper($params['x_type'])){
		case 'AUTH_ONLY':
			$authOpts['x_type']="AUTH_ONLY";
			$authOpts['x_amount']=$params['amount'];
			break;
		case 'PRIOR_AUTH_CAPTURE':
			//requires x_trans_id
			if(isset($params['x_trans_id'])){$authOpts['x_trans_id']=$params['x_trans_id'];}
			else if(isset($params['trans_id'])){$authOpts['x_trans_id']=$params['trans_id'];}
			else if(isset($params['transaction_id'])){$authOpts['x_trans_id']=$params['transaction_id'];}
			else if(isset($params['transid'])){$authOpts['x_trans_id']=$params['transid'];}
			else{
				$result['response_code']	= 3;
				$result['response_text']	= "Missing trans_id";
				return $result;
            	}
			$authOpts['x_type']="PRIOR_AUTH_CAPTURE";
			break;
		default:
			$authOpts['x_type']="AUTH_CAPTURE";
			$authOpts['x_amount']=$params['amount'];
			break;
    	}
    if(isset($params['x_test_request'])){$authOpts['x_test_request']=$params['x_test_request'];}
	//card CVV2
	if(isset($params['cardcode'])){$authOpts['x_card_code']=$params['cardcode'];}
	else if(isset($params['ccCode'])){$authOpts['x_card_code']=$params['ccCode'];}
	else if(isset($params['cc_cvv2'])){$authOpts['x_card_code']=$params['cc_cvv2'];}
	//split name
	if(isset($params['shiptoname'])){
    	list($params['shipto_firstname'],$params['shipto_lastname'])=preg_split('/\ /',$params['shiptoname'],2);
	}
	if(isset($params['billtoname'])){
    	list($params['billto_firstname'],$params['billto_firstname'])=preg_split('/\ /',$params['billtoname'],2);
	}
	//join address
	if(isset($params['billtoaddress1'])){
		$params['billtoaddress']=$params['billtoaddress1'];
		if(isset($params['billtoaddress2'])){
			$params['billtoaddress'].=', '.$params['billtoaddress2'];
		}
	}
	if(isset($params['shiptoaddress1'])){
		$params['shiptoaddress']=$params['shiptoaddress1'];
		if(isset($params['shiptoaddress2'])){
			$params['shiptoaddress'].=', '.$params['shiptoaddress2'];
		}
	}
	//field map
	$fieldmap=array(
		'description'		=> 'x_description',
		'first_name'		=> 'x_first_name',
		'last_name'			=> 'x_last_name',
		'firstname'			=> 'x_first_name',
		'lastname'			=> 'x_last_name',
		'company'			=> 'x_company',
		'address'			=> 'x_address',
		'city'				=> 'x_city',
		'state'				=> 'x_state',
		'zip'				=> 'x_zip',
		'country'			=> 'x_country',
		'phone'				=> 'x_phone',
		'email'				=> 'x_email',
		'billtofirstname'	=> 'x_first_name',
		'billtolastname'	=> 'x_last_name',
		'billtocompany'		=> 'x_company',
		'billtoaddress'		=> 'x_address',
		'billtocity'		=> 'x_city',
		'billtostate'		=> 'x_state',
		'billtozipcode'		=> 'x_zip',
		'billtocountry'		=> 'x_country',
		'billtotelephone'	=> 'x_phone',
		'billtoemail'		=> 'x_email',
		'billto_firstname'	=> 'x_first_name',
		'billto_lastname'	=> 'x_last_name',
		'billto_company'		=> 'x_company',
		'billto_address'		=> 'x_address',
		'billto_city'		=> 'x_city',
		'billto_state'		=> 'x_state',
		'billto_zipcode'		=> 'x_zip',
		'billto_country'		=> 'x_country',
		'billto_telephone'	=> 'x_phone',
		'billto_email'		=> 'x_email',
		'customer_id'		=> 'x_cust_id',
		'customer_ip'		=> 'x_customer_ip',
		'invoice_num'		=> 'x_invoice_num',
		'order_id'			=> 'x_invoice_num',
		'shipto_firstname'	=> 'x_ship_to_first_name',
		'shipto_lastname'	=> 'x_ship_to_last_name',
		'shipto_first_name'	=> 'x_ship_to_first_name',
		'shipto_last_name'	=> 'x_ship_to_last_name',
		'shipto_company'	=> 'x_ship_to_company',
		'shipto_address'	=> 'x_ship_to_address',
		'shipto_city'		=> 'x_ship_to_city',
		'shipto_state'		=> 'x_ship_to_state',
		'shipto_zip'		=> 'x_ship_to_zip',
		'shipto_country'	=> 'x_ship_to_country',
		'shiptocity'		=> 'x_ship_to_city',
		'shiptostate'		=> 'x_ship_to_state',
		'shiptozipcode'		=> 'x_ship_to_zip',
		'shiptocountry'		=> 'x_ship_to_country',
		'ship_firstname'	=> 'x_ship_to_first_name',
		'ship_lastname'		=> 'x_ship_to_last_name',
		'ship_first_name'	=> 'x_ship_to_first_name',
		'ship_last_name'	=> 'x_ship_to_last_name',
		'ship_company'		=> 'x_ship_to_company',
		'ship_address'		=> 'x_ship_to_address',
		'ship_city'			=> 'x_ship_to_city',
		'ship_state'		=> 'x_ship_to_state',
		'ship_zip'			=> 'x_ship_to_zip',
		'ship_country'		=> 'x_ship_to_country'
	);
	foreach($fieldmap as $mfield=>$afield){
    	if(isset($params[$mfield])){$authOpts[$afield]=$params[$mfield];}
	}
	//birthday
	if(isset($params['birthMonth'])){$authOpts['CustomerBirthMonth']="Customer Birth Month: " . $params['birthMonth'];}
	if(isset($params['birthDay'])){$authOpts['CustomerBirthDay']="Customer Birth Day: " . $params['birthDay'];}
	if(isset($params['birthYear'])){$authOpts['CustomerBirthYear']="Customer Birth Year: " . $params['birthYear'];}
	if(isset($params['special'])){$authOpts['SpecialCode']="Promotion: " . $params['special'];}
	if($params['-debug']){
		$result['params_out']=$authOpts;
		ksort($result['params_out']);
		}
	//create url encoded pairs
	$pairs=array();
	foreach($authOpts as $key=>$value){
		array_push($pairs,"$key=" . urlencode($value));
		}
	//create a url string
	$urlstring=implode('&',$pairs);
	//$result['x_urlstring']=$urlstring;
	// Post the transaction to authorize.net usine curl
	$authUrls=array(
		'test'	=> "https://test.authorize.net/gateway/transact.dll",
		'live'	=> "https://secure.authorize.net/gateway/transact.dll",
		'internetsecure' => "https://anet.internetsecure.com/process.cgi"
		);
	//allow url to be overridden - InternetSecure can also use authnet. -url=https://anet.internetsecure.com/process.cgi
	if($params['-url']){
		$result['_url']=$authUrls['-url'];
		}
	elseif($params['-internetsecure']){
		$result['_url']=$authUrls['internetsecure'];
		}
	elseif($params['-test']){
		$result['_url']=$authUrls['test'];
		}
    else{
		$result['_url']=$authUrls['live'];
		}
	$ch = curl_init($result['_url']);
	curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
	curl_setopt($ch, CURLOPT_POSTFIELDS, $urlstring); // use HTTP POST to send form data
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
	$response = curl_exec($ch); //execute post and get results
	curl_close ($ch);
	//parse the response
	//$result['x_raw']= $response;
	// load a temporary array with the values returned from authorize.net
    $response_values = explode('|', $response);
	// keys corresponding to the values returned from authorize.net
    $response_keys= array (
    	"response_code", "response_subcode", "response_reason_code", "response_reason_text","approval_code", 
		"avs_result_code", "transaction_id", "invoice_number", "description","amount", 
		"method", "transaction_type", "customer_id", "firstname","lastname",
		"company", "address", "city", "state","zip", 
		"country", "phone", "fax", "email", "shipto_firstname", 
		"shipto_lastname", "shipto_company", "shipto_address", "shipto_city", "shipto_state",
        "shipto_zip", "shipto_country", "tax_amount", "duty_amount", "freight_amount",
        "tax_exempt_flag", "po_number", "md5_hash", "cvv2_response_code", "cavv_response_code"
      );
      // add additional keys for reserved fields and merchant defined fields
    for ($i=0; $i<=27; $i++){
    	array_push($response_keys, 'x_reserved_'.$i);
    	}
    $i=0;
    while (sizeof($response_keys) < sizeof($response_values)) {
    	array_push($response_keys, 'x_merchant_defined_'.$i);
    	$i++;
		}
	for($i=0; $i<sizeof($response_values);$i++) {
		$val=trim($response_values[$i]);
		if(strlen($val)){
    		$result["$response_keys[$i]"] = $val;
			}
      	}
    //response_code: 1=approved, 2=declined, 3=error
    switch((integer)$result['response_code']){
		case 1: $result['response_text']='approved';break;
		case 2: $result['response_text']='declined';break;
		case 3: $result['response_text']='error';break;
    	}
    //cvv2_response_code: M=Match, N=No Match, P=Not Processed, S=Should have been present, U=Issuer unable to process request
    switch(strtoupper($result['cvv2_response_code'])){
		case 'M': $result['cvv2_response_text']='Match';break;
		case 'N': $result['cvv2_response_text']='No Match';break;
		case 'P': $result['cvv2_response_text']='Not Processed';break;
		case 'S': $result['cvv2_response_text']='Should have been present';break;
		case 'U': $result['cvv2_response_text']='Issuer unable to process request';break;
		default : $result['cvv2_response_text']='NO VALUE RETURNED';
    	}
    ksort($result);
	return $result;
	}
?>