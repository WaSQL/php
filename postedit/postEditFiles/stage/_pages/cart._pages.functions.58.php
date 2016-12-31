<?php
function cartSubscribePlan($plan,$params){
	$apikey='sk_live_GyO47w5hw8gLRWqcGHnyj7PR';
	if(isDBStage()){
		$apikey='sk_test_kQxGYORSgl5fbMMnMpJSox6A';
	}
	$stripe=stripeCreateCustomer(array(
		'apikey'		=> $apikey,
		'name'			=> $params['name'],
		'email'			=> $params['email'],
		'url'			=> $params['url'],
		'company'		=> $params['company'],
		'timezone'		=> $params['timezone'],
		'address_city'	=> $params['city'],
		'address_state'	=> $params['state'],
		'address_zip'	=> $params['postcode'],
		'address_country'=>$params['country'],
		'cc_name'		=> $params['cc_name'],
		'cc_number'		=> $params['cc_num'],
		'cc_month'		=> $params['cc_month'],
		'cc_year'		=> $params['cc_year'],
		'cc_cvc'		=> $params['cc_cvv'],
		'plan'			=> $plan['stripe_id']
	));
	//add info to clients_stripedata
	$fields=getDBFields('clients_stripedata');
	if(!isset($stripe['id'])){return null;}
	$client=commonGetClient();
	$opts=array(
		'-table'=>'clients_stripedata',
		'packages'=>implode(':',$params['package'])
	);
	if(isset($client['_id'])){$opts['client_id']=$client['_id'];}
	else{
    	$opts['client_id']=addDBRecord(array(
			'-table'	=> 'clients',
			'name'		=> $params['company'],
			'url'		=> $params['url'],
			'city'		=> $params['city'],
			'province'	=> $params['state'],
			'country'	=> $params['country'],
			'timezone'	=> $params['timezone']
		));
	}
    //customer_id
	$opts['customer_id']=$stripe['id'];
	$opts['email']=$stripe['email'];
	//plan fields
	if(isset($stripe['plan']['id'])){
		foreach($stripe['plan'] as $k=>$v){
			if(in_array("plan_{$k}",$fields)){
        		$opts["plan_{$k}"]=$v;
			}
		}
	}
	//card fields
	if(isset($stripe['card']['id'])){
		foreach($stripe['card'] as $k=>$v){
			if(in_array("card_{$k}",$fields)){
        		$opts["card_{$k}"]=$v;
			}
		}
	}
	//subscription fields
	if(isset($stripe['subscription']['id'])){
		foreach($stripe['subscription'] as $k=>$v){
			if(in_array("subscription_{$k}",$fields)){
        		$opts["subscription_{$k}"]=$v;
			}
		}
	}
	$id=addDBRecord($opts);
	//echo printValue($id).printValue($opts);exit;
	return $stripe;
}
function cartGetStripePlan($plan,$type,$pkg_cnt){
	return getDBRecord(array(
		'-table'=>'stripe_plans',
		'plan'	=> $plan,
		'type'	=> $type,
		'pkg_cnt'=>$pkg_cnt,
		'-fields'=>'_id,name,stripe_id,plan,type,pkg_cnt,amount'
	));
}
function cartFormField($name){
	switch(strtolower($name)){
    	case 'firstname':
    	case 'lastname':
		case 'company':
		case 'address':
		case 'city':
		case 'postcode':
		case 'phone':
			return buildFormText($name,array('placeholder'=>ucfirst($name)));
    	break;
    	case 'email':
    		return buildFormText($name,array('type'=>'email'));
    	break;
    	case 'state':
    		$recopts=array(
				'-table'=>"states",
				'country'=>'US',
				'-order'=>"name,_id",
				'-fields'=>"_id,name,code",
				'-index'=>'code'
			);
			$recs=getDBRecords($recopts);
			$opts=array();
			foreach($recs as $code=>$rec){
            	$opts[$code]=$rec['name'];
			}
    		return buildFormSelect($name,$opts,array('message'=>'-- State --'));
    	break;
    	case 'country':
    	break;
    	case 'url':
    		return buildFormText($name,array('placeholder'=>'Website URL'));
    	break;;
    	case 'timezone':
    	break;
	}
}
?>
