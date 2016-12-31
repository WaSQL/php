<?php
if(isset($_REQUEST['passthru'][0])){
	$order=pageGetOrder($_REQUEST['passthru'][0]);
	if(isset($order['items'])){
		setView('_receipt');
		return;
	}
}

if(1==2 && isDBStage()){
	global $USER;
    $_REQUEST['shiptoname']="{$USER['firstname']} {$USER['lastname']}";
    $_REQUEST['shiptoaddress1']='2325 N 600 W';
    $_REQUEST['shiptocity']='Pleasant Grove';
	$_REQUEST['shiptostate']='UT';
    $_REQUEST['price']=45;
    $_REQUEST['shiptotelephone']='(801) 123-4567';
    $_REQUEST['shiptozipcode']='84062';
    $_REQUEST['cc_num']='4242424242424242';
	$_REQUEST['cc_month']='11';
	$_REQUEST['cc_year']=date('Y');
	$_REQUEST['cc_cvv2']='112';
    $_REQUEST['shiptoemail']=$USER['email'];
}
switch(strtolower($_REQUEST['func'])){
	case 'delete':
		$ok=executeSQL("update orders_items set status='deleted' where status='cart' and ordernumber='{$_SERVER['GUID']}' and sku='{$_REQUEST['sku']}'");
		$cart=commonGetCart();
		if($cart['totals']['quantity']>0){
			setView(array('_cart','update'));
		}
		else{
        	setView(array('_empty','update'));
		}

	break;
	case 'shiptostate':
		$cart=commonGetCart();
		setView(array('_cart_table'));
		return;
	break;
	case 'applycoupon':
    	$cart=commonApplyCoupon($_REQUEST['coupon']);
    	//echo printValue($cart);exit;
    	if($cart['totals']['quantity']>0){
			setView('_cart_table',1);
		}
		else{
        	setView(array('_empty'),1);
		}
		return;
    break;
    case 'applygiftcard':
    	$cart=commonApplyGiftcard($_REQUEST['coupon']);
    	if($cart['totals']['quantity']>0){
			setView('_cart_table',1);
		}
		else{
        	setView(array('_empty'));
		}
		return;
    break;
	case 'update':
        if(preg_match('/^qty\_(.+)$/',$_REQUEST['fld'],$m) && isNum($_REQUEST['val']) && $_REQUEST['val'] >= 0){
			if($_REQUEST['val']==0){
                $ok=executeSQL("update orders_items set status='deleted' where status='cart' and ordernumber='{$_SERVER['GUID']}' and sku='{$m[1]}'");
			}
			else{
				$sql="update orders_items set quantity='{$_REQUEST['val']}' where status='cart' and ordernumber='{$_SERVER['GUID']}' and sku='{$m[1]}'";
                $ok=executeSQL($sql);
                	//echo $sql;exit;
			}
		}
		$cart=commonGetCart();
		if($cart['totals']['quantity']>0){
			setView(array('_cart','update'));
		}
		else{
        	setView(array('_empty','update'));
		}

	break;
	case 'process_order':
		setView($_REQUEST['func']);
		switch(strtoupper($_SESSION['country_code'])){
            	case 'CA':
            		$_REQUEST['usps_verified']=1;
            		$_REQUEST['shiptocountry']='CA';
            	break;
            	case 'GB':
            	case 'UK':
            		$_REQUEST['usps_verified']=1;
            		$_REQUEST['shiptocountry']='GB';
            	break;
		}
		if($_REQUEST['usps_verified']==0){
			//validate address before processing for US orders
			loadExtras('usps');
			$verify=uspsVerifyAddress(array(
				'-userid'	=> '590PURES4250',
				'-password' => '028NN70LW360',
				'address'	=> array(
					array(
						'Address1'	=> $_REQUEST['shiptoaddress2'],
						'Address2'	=> $_REQUEST['shiptoaddress1'],
						'City'		=> $_REQUEST['shiptocity'],
						'State'		=> $_REQUEST['shiptostate'],
						'Zip5'		=> $_REQUEST['shiptozipcode'],
						'Zip4'		=> ''
					)
				)
			));
			if(isset($verify['address'][0]['out']['err'])){
				setView('address_not_found',1);
			}
			elseif(count($verify['address'][0]['diff'])){
				setView('address_diff',1);
			}
			else{
				$process = pageProcessCreditCard($_REQUEST);
				if(isset($process['ordernumber'])){
					$order=pageGetOrder($process['ordernumber']);
					switch($process['status']){
						case 'success':
							setView('process_order_success');
							$orderopts=array(
								'-alias'	=> 'order',
								'-format'	=> 'email',
								'to'		=> 'steve.lloyd@gmail.com',
								'from'		=> 'info@skillsai.com',
								'subject'	=> 'RE:Skillsai Order Receipt'
							);
						break;
                		default:
							setView('process_order_failed');
						break;
					}
				}
			}
		}
		else{
			$process = pageProcessCreditCard($_REQUEST);
			if(isset($process['ordernumber'])){
				$order=pageGetOrder($process['ordernumber']);
				switch($process['status']){
					case 'success':
						setView('process_order_success');
						$orderopts=array(
							'-alias'	=> 'order',
							'-format'	=> 'email',
							'to'		=> $order['billtoemail'],
							'from'		=> 'info@skillsai.com',
							'subject'	=> 'RE:Skillsai Order Receipt'
						);
						$notifyopts=array(
							'-alias'	=> 'order',
							'-format'	=> 'email',
							'to'		=> 'info@skillsai.com',
							'from'		=> $order['billtoemail'],
							'subject'	=> 'NEW Skillsai Order  - PLEASE SHIP'
						);
					break;
                	default:
						setView('process_order_failed');
					break;
				}
			}
		}
    		return;
    	break;
	default:
		$cart=commonGetCart();
		//echo printValue($cart);exit;
		setView('default');
	break;
}


?>
