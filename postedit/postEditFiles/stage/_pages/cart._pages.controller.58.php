<?php
	setView('default');
	//$stripe=cartGetStripePlan('solo','month',3);
	//echo printValue($stripe);exit;
	$package=addslashes($_REQUEST['passthru'][0]);
	switch(strtolower($package)){
		case 'subscribe':
			loadExtras('stripe');
			//lookup stripe plan based on pkg, count(package), and term
			$plan=addslashes($_REQUEST['plan']);
			$type=addslashes($_REQUEST['term']);
			$pkg_cnt=count($_REQUEST['package']);
			$plan=cartGetStripePlan($plan,$type,$pkg_cnt);
			$fields=getDBFields('clients_stripedata');
			//echo printValue($fields).printValue($plan).printValue($_REQUEST);exit;
			$stripe=cartSubscribePlan($plan,$_REQUEST);
			setView('subscribe',1);
			return;

		break;
    	case 'redraw':
    		$country=addslashes($_REQUEST['country']);
    		//echo $country;exit;
    		setView('country',1);
    		return;
    	break;
		default:
			if(isUser()){
				global $USER;
            	$client=commonGetClient();
            	$_REQUEST['company']=$client['name'];
            	$_REQUEST['url']=$client['url'];
            	$_REQUEST['timezone']=$client['timezone'];
            	$_REQUEST['city']=$client['city'];
            	$_REQUEST['state']=$client['state'];
            	$_REQUEST['postcode']=$client['postcode'];
            	$_REQUEST['country']=$client['country'];
            	$_REQUEST['name']="{$USER['firstname']} {$USER['lastname']}";
            	$_REQUEST['email']=$USER['email'];
			}
			if(isDBStage()){
            	$_REQUEST['cc_name']='Test C. Name';
            	$_REQUEST['cc_num']='4242424242424242';
            	$_REQUEST['cc_month']='10';
            	$_REQUEST['cc_year']='2019';
            	$_REQUEST['cc_cvv']='321';
			}
			if(!isset($_REQUEST['country'])){
            	$_REQUEST['country']='US';
			}
			setView($package);
		break;
	}

?>
