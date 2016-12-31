<?php
function pageProcessCreditCard($params=array()){
	global $USER;
	$cart=commonGetCart();
	$amount=$cart['totals']['total'];
	if($amount == 0){
		//using a giftcard so amount is zero
		$rtn='';
		//get an ordernumber
		$orderid=addDBRecord(array('-table'=>"orders"));
		$ordernumber='RD013'.$orderid;
		$response=array(
			'authorization_code'	=>'GIFTCARD',
			'response_reason_text'	=>'Used giftcards to purchase',
			'card_type'				=> 'giftcard'
		);
		$ordernumber=commonConvertCart2Order($orderid,$params,$response);
		//get order number and add to the message below.
		$window = 'https://www.skillsai.com/confirmation/invoice/'.$ordernumber;
		if(isDBStage()){
			$window = 'https://stage.skillsai.com/confirmation/invoice/'.$ordernumber;
		}
		//echo $_SESSION['country_code'].$window;exit;
		$rtn .= '<div class="w_bigger w_bold w_dblue w_required" style="padding-bottom:5px;border-bottom:1px solid #000;">'."\n";
		$rtn .= 'Thank You for your order!'."\n";
		$rtn .= '</div>'."\n";
		$rtn .= '<div style="padding:20px 20px 0 20px;">'."\n";
		$rtn .= '<div class="w_pad">Your Order Number is: <b>'.$ordernumber.'</b>.</div>'."\n";
		$rtn .= '<div class="w_pad">We have emailed you a receipt</div>'."\n";
		$rtn .= '</div>'."\n";
		$rtn .= buildOnLoad("document.cartform.reset();setText('cart_contents','Cart is empty');");
		return $rtn;
	}
	//get an ordernumber
	$orderid=addDBRecord(array('-table'=>"orders"));
	$ordernumber='RD0'.date('y').$orderid;
	//use stripe to process
	loadExtras('stripe');
	$auth=array(
		'card_num' 		=> $params['cc_num'],
		'card_exp_month'=> $params['cc_month'],
		'card_exp_year'	=> $params['cc_year'],
		'card_cvv2'		=> $params['cc_cvv2'],
		'amount'		=> $amount,
		'ordernumber'	=> $ordernumber,
		'company'		=> 'Skillsai.com',
		'description'	=> "Skillsai Distillers",
		'email'			=> $params['billtoemail'],
		'billtozipcode'	=> $params['billtozipcode'],
		'receipt_email'	=> $params['billtoemail']
	);
	foreach($params as $key=>$val){
    	if(preg_match('/^(x\_|\-test|shipto|billto)/i',$key) && !isset($auth[$key])){
        	$auth[$key]=$val;
		}
	}
	switch(strtoupper($_SESSION['country_code'])){
    	default:
    		//US stripe account
    		$auth['currency']='usd';
    		if(isDBStage()){
				$auth['apikey']='sk_test_w65jgkn31FVSVSbA6xU1OO4k';
			}
			else{
				$auth['apikey']='sk_live_jOkxYUdvrgHkOYd0LtrbTFCP';
			}
    	break;
	}

	ksort($auth);
	//echo $_SESSION['country_code'].printValue($auth).printValue($params);exit;
	$response=stripeCharge($auth);

	$rtn='';
	if($response['approved']){
		//credit card was APPROVED. Update order table and decriment inventory
		//return printValue($response);
		$ordernumber=commonConvertCart2Order($orderid,$params,$response);
		return array(
			'status'=>'success',
			'ordernumber'	=> $ordernumber
			);
	}
	else{
		//credit card was DENIED
		$rtn .= '<div class="w_bigger w_bold w_dblue w_required" style="padding-bottom:5px;border-bottom:1px solid #000;">'."\n";
		$rtn .= '	<img src="/wfiles/iconsets/32/alert.png" width="32" height="32" border="0" style="vertical-align:bottom;" /> Error processing payment'."\n";
		$rtn .= '</div>'."\n";
		$rtn .= '<div style="padding:20px 20px 0 20px;">'."\n";
		$rtn .= '<div class="w_pad">'.$response['response_reason_text'].'</div>'."\n";
		$rtn .= '<div class="w_pad">Please Verify your credit card information is correct.</div>'."\n";
		$rtn .= '</div>'."\n";
		//$rtn .= printValue($response);
		if(isNum($orderid)){
			$params['-table']="orders";
			$params['-where']="_id={$orderid}";
			$params['cc_num']=str_replace(' ','',$params['cc_num']);
			//remove all but the last four of the credit card number
			$params['cc_num']='****'.substr($params['cc_num'],-4);
			$params['status']='denied';
			$orderfields=getDBFields('orders');
			foreach($orderfields as $field){
		    	if(isset($cart['totals'][$field]) && !isset($params[$field])){
		        	$params[$field]=$cart['totals'][$field];
				}
			}
			//order total, ship_cost, items_total, items_quantity
		    $params['order_total']=$cart['totals']['total'];
		    $params['items_total']=$cart['totals']['subtotal'];
		    $params['items_quantity']=$cart['totals']['quantity'];
		    //authnet fields:
		    $params['cc_result']=$response['response_reason_text'];
		    $params['payment_type']=$response['card_type'];
		    //Status
		    $params['status']='failed';
		    $year=date('y');
    		$prefix="FAIL{$year}";
			$params['ordernumber']=$prefix.$orderid;
        	$ok=editDBRecord($params);
        	return array(
				'status'=>'denied',
				'ordernumber'	=> $params['ordernumber']
				);
		}
	}
	return null;
}
function pageGetOrder($id){
	$order=getDBRecord(array(
		'-table'	=> 'orders',
		'-where'	=> "_id='{$id}' or ordernumber='{$id}'"
	));
	if(!is_array($order)){
		return null;
	}
	$recs=getDBRecords(array(
		'-table'	=> "orders_items",
		'ordernumber'	=> $order['ordernumber']
	));
	if(!is_array($recs)){return null;}
	$order['items']=array();
	foreach($recs as $rec){
    	$rec['subtotal']=$rec['quantity']*$rec['price'];
    	$order['items'][]=$rec;
	}
	return $order;
}
?>
