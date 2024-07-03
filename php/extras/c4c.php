<?php
/* References:
	
//---------- begin function c4cEditCustomer
/**
* @describe Edits customer with objectid in SAP C4C application
*/
//---------- begin function c4cEditCustomer
function c4cGetCustomerById($customerid){
	global $CONFIG;
	if(!isset($CONFIG['c4c_user']) || !isset($CONFIG['c4c_pass']) || !isset($CONFIG['c4c_hostname'])){
		return "c4c_user or c4c_pass or c4c_hostname not found in config";
	}
	//https://my325381.crm.ondemand.com/sap/c4c/odata/v1/c4codataapi/IndividualCustomerCollection?filter=ID eq '9000000150' or ID eq '9000000141'
	if(is_array($customerid)){
		$url="https://{$CONFIG['c4c_hostname']}.crm.ondemand.com/sap/c4c/odata/v1/c4codataapi/IndividualCustomerCollection?\$filter=";
		$ors=array();
		foreach($customerid as $id){
			$ors[]="CustomerID eq '{$id}'";
		}
		$orstr=implode(" or ",$ors);
		$url.=encodeURL($orstr);
		$url.="&\$format=json";
	}
	else{
		$url="https://{$CONFIG['c4c_hostname']}.crm.ondemand.com/sap/c4c/odata/v1/c4codataapi/IndividualCustomerQueryByElements?CustomerID=%27{$customerid}%27&\$format=json";
	}
	//echo $url;exit;
	$params=array(
		'-method'=>'GET',
		'-authuser'=>$CONFIG['c4c_user'],
		'-authpass'=>$CONFIG['c4c_pass'] 
	);
	$params['-contenttype']='application/json; charset=UTF-8';
	$params['-headers']=array('x-csrf-token: fetch');
	$post=postURL($url,$params);
	if(!isset($post['headers']['x-csrf-token']) || strtolower($post['headers']['x-csrf-token'])=='required'){return "Failed to get token".printValue($post);}
	//set cookies and token
	if(isset($post['headers']['set-cookie'])){
		if(!is_array($post['headers']['set-cookie'])){
			$post['headers']['set-cookie']=array($post['headers']['set-cookie']);
		}
		$params['-cookie']=array();
		foreach($post['headers']['set-cookie'] as $cookie){
			$parts=preg_split('/[\=\;]/',$cookie);
			$params['-cookie'][]=$parts[0].'='.$parts[1];
		}
	}
	$params['-headers']=array('x-csrf-token: '.$post['headers']['x-csrf-token']);
	//it returns xml
	$params['-json']=1;
	$post=postURL($url,$params);
	//echo printValue($post);exit;
	if(isset($post['json_array']['d']['results'][0])){
		$recs=$post['json_array']['d']['results'];
		$xrecs=array();
		foreach($recs as $i=>$rec){
			foreach($rec as $k=>$v){
				if(is_array($v) || !strlen($v)){
					unset($recs[$i][$k]);
				}
			}
			$xrecs[$rec['CustomerID']]=$recs[$i];
		}
		return $xrecs;
	}
	return $post['json_array'];
}

//---------- begin function c4cAddCustomer
/**
* @describe Adds a customer to SAP C4C application
*/
//---------- begin function c4cAddCustomer
function c4cAddCustomer($customer=array()){
	global $CONFIG;
	if(!isset($CONFIG['c4c_user']) || !isset($CONFIG['c4c_pass']) || !isset($CONFIG['c4c_hostname'])){
		return "c4c_user or c4c_pass or c4c_hostname not found in config";
	}
	$url="https://{$CONFIG['c4c_hostname']}.crm.ondemand.com/sap/c4c/odata/v1/c4codataapi/IndividualCustomerCollection";
	$params=array(
		'-method'=>'GET',
		'-authuser'=>$CONFIG['c4c_user'],
		'-authpass'=>$CONFIG['c4c_pass'] 
	);
	$params['-contenttype']='application/json; charset=UTF-8';
	$params['-headers']=array('x-csrf-token: fetch');
	$post=postURL($url,$params);
	if(!isset($post['headers']['x-csrf-token'])){return "Failed to get token";}
	//set cookies and token
	if(isset($post['headers']['set-cookie'])){
		if(!is_array($post['headers']['set-cookie'])){
			$post['headers']['set-cookie']=array($post['headers']['set-cookie']);
		}
		$params['-cookie']=array();
		foreach($post['headers']['set-cookie'] as $cookie){
			$parts=preg_split('/[\=\;]/',$cookie);
			$params['-cookie'][]=$parts[0].'='.$parts[1];
		}
	}
	$params['-headers']=array('x-csrf-token: '.$post['headers']['x-csrf-token']);
	//it returns xml
	$params['-xml']=1;
	$json=json_encode($customer);
	$post=postJSON($url,$json,$params);
	return $post['xml_array'];
}
//---------- begin function c4cEditCustomer
/**
* @describe Edits customer with objectid in SAP C4C application
*/
//---------- begin function c4cEditCustomer
function c4cEditCustomer($objectid,$customer=array()){
	global $CONFIG;
	if(!isset($CONFIG['c4c_user']) || !isset($CONFIG['c4c_pass']) || !isset($CONFIG['c4c_hostname'])){
		return "c4c_user or c4c_pass or c4c_hostname not found in config";
	}
	$url="https://{$CONFIG['c4c_hostname']}.crm.ondemand.com/sap/c4c/odata/v1/c4codataapi/IndividualCustomerCollection(ObjectID='{$objectid}')";
	$params=array(
		'-method'=>'GET',
		'-authuser'=>$CONFIG['c4c_user'],
		'-authpass'=>$CONFIG['c4c_pass'] 
	);
	$params['-contenttype']='application/json; charset=UTF-8';
	$params['-headers']=array('x-csrf-token: fetch');
	$post=postURL($url,$params);
	if(!isset($post['headers']['x-csrf-token'])){return "Failed to get token";}
	//set cookies and token
	if(isset($post['headers']['set-cookie'])){
		if(!is_array($post['headers']['set-cookie'])){
			$post['headers']['set-cookie']=array($post['headers']['set-cookie']);
		}
		$params['-cookie']=array();
		foreach($post['headers']['set-cookie'] as $cookie){
			$parts=preg_split('/[\=\;]/',$cookie);
			$params['-cookie'][]=$parts[0].'='.$parts[1];
		}
	}
	$params['-headers']=array('x-csrf-token: '.$post['headers']['x-csrf-token']);
	//it returns xml
	$params['-xml']=1;
	$json=json_encode($customer);
	$post=postJSON($url,$json,$params);
	return $post['xml_array'];
}
//---------- begin function c4cAddCustomer
/**
* @describe Adds a customer to SAP C4C application
*/
//---------- begin function c4cAddCustomer
function c4cAddCustomerInteraction($interaction_data=array()){
	global $CONFIG;
	if(!isset($CONFIG['c4c_user']) || !isset($CONFIG['c4c_pass']) || !isset($CONFIG['c4c_hostname'])){
		return "c4c_user or c4c_pass or c4c_hostname not found in config";
	}
	$url="https://{$CONFIG['c4c_hostname']}.crm.ondemand.com/sap/c4c/odata/cust/v1/z_interaction_c4c/ActivityCollection";
	$params=array(
		'-method'=>'GET',
		'-authuser'=>$CONFIG['c4c_user'],
		'-authpass'=>$CONFIG['c4c_pass'] 
	);
	$params['-contenttype']='application/json; charset=UTF-8';
	$params['-headers']=array('x-csrf-token: fetch');
	$post=postURL($url,$params);
	if(!isset($post['headers']['x-csrf-token'])){return "Failed to get token";}
	//set cookies and token
	if(isset($post['headers']['set-cookie'])){
		if(!is_array($post['headers']['set-cookie'])){
			$post['headers']['set-cookie']=array($post['headers']['set-cookie']);
		}
		$params['-cookie']=array();
		foreach($post['headers']['set-cookie'] as $cookie){
			$parts=preg_split('/[\=\;]/',$cookie);
			$params['-cookie'][]=$parts[0].'='.$parts[1];
		}
	}
	$params['-headers']=array('x-csrf-token: '.$post['headers']['x-csrf-token']);
	//it returns xml
	$params['-xml']=1;
	$json=json_encode($interaction_data);
	$post=postJSON($url,$json,$params);
	return $post['xml_array'];
}
?>