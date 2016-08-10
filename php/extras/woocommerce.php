<?php
/*
	https://github.com/kloon/WooCommerce-REST-API-Client-Library

*/
$progpath=dirname(__FILE__);
require_once("{$progpath}/woocommerce/woocommerce-api.php");

function woocommerceNewClient($params){
	if(!isset($params['url'])){return "woocommerceNewClient Error - missing url param";}
	if(!isset($params['consumer_key'])){return "woocommerceNewClient Error - missing consumer_key param";}
	if(!isset($params['consumer_secret'])){return "woocommerceNewClient Error - missing consumer_secret param";}
	if(!isset($params['ssl'])){$params['ssl']=false;}
	$options = array('ssl_verify'=> $params['ssl']);
	try {
		$client = new WC_API_Client( $params['url'], $params['consumer_key'], $params['consumer_secret'], $options );
		return $client;
	} catch ( WC_API_Client_Exception $e ) {
		return "woocommerceNewClient Error -".printValue($e);
	}
	return "woocommerceNewClient Error - unknown";
}
function woocommerceGetWebhooks($params){
	$client=woocommerceNewClient($params);
	if(!is_object($client)){
		return $client;
	}
	$rtn=$client->webhooks->get();
	$rtn=json_decode(json_encode($rtn), true);
	if(!isset($rtn['webhooks'])){return array();}
	return $rtn['webhooks'];
}
function woocommerceAddWebhook($params){
	if(!isset($params['topic'])){return "woocommerceAddWebhook Error - missing topic param";}
	if(!isset($params['delivery_url'])){return "woocommerceAddWebhook Error - missing delivery_url param";}
	//init client
	$client=woocommerceNewClient($params);
	if(!is_object($client)){
		return $client;
	}

	$opts=array(
		'topic' 		=> $params['topic'],
		'delivery_url' 	=> $params['delivery_url']
	);
	if(isset($params['status'])){$opts['status']=$params['status'];}
	if(isset($params['secret'])){$opts['secret']=$params['secret'];}
	try{
		$rtn=$client->webhooks->create($opts);
		$rtn=json_decode(json_encode($rtn), true);
		if(!isset($rtn['webhook'])){return array();}
		return $rtn['webhook'];
	} catch ( WC_API_Client_Exception $e ) {
		$code=$e->get_response()->code;
		$msg=json_decode($e->get_response()->body,true);
		return "woocommerceAddWebhook {$code} Error - {$msg['errors'][0]['message']}";
	}
}
function woocommerceEditWebhook($params){
	if(!isset($params['id'])){return "woocommerceEditWebhook Error - missing id param";}
	//init client
	$client=woocommerceNewClient($params);
	if(!is_object($client)){
		return $client;
	}
	$opts=array();
	if(isset($params['topic'])){$opts['topic']=$params['topic'];}
	if(isset($params['secret'])){$opts['secret']=$params['secret'];}
	if(isset($params['status'])){$opts['status']=$params['status'];}
	if(isset($params['delivery_url'])){$opts['delivery_url']=$params['delivery_url'];}
	if(!count($opts)){return "woocommerceEditWebhook Error - nothing to update";}
	try{
		$rtn=$client->webhooks->update($params['id'],$opts);
		$rtn=json_decode(json_encode($rtn), true);
		if(!isset($rtn['webhook'])){return array();}
		return $rtn['webhook'];
	} catch ( WC_API_Client_Exception $e ) {
		$code=$e->get_response()->code;
		$msg=json_decode($e->get_response()->body,true);
		return "woocommerceEditWebhook {$code} Error - {$msg['errors'][0]['message']}";
	}
}
function woocommerceDelWebhook($params){
	if(!isset($params['id'])){return "woocommerceDelWebhook Error - missing id param";}
	//init client
	$client=woocommerceNewClient($params);
	if(!is_object($client)){
		return $client;
	}
	try{
		$rtn=$client->webhooks->delete($params['id']);
		$rtn=json_decode(json_encode($rtn), true);
		if(!isset($rtn['message'])){return false;}
		return $rtn['message'];
	} catch ( WC_API_Client_Exception $e ) {
		$code=$e->get_response()->code;
		$msg=json_decode($e->get_response()->body,true);
		return "woocommerceDelWebhook {$code} Error - {$msg['errors'][0]['message']}";
	}
}
function woocommerceGetWebhooksCount($params){
	//init client
	$client=woocommerceNewClient($params);
	if(!is_object($client)){
		return $client;
	}
	try{
		$rtn=$client->webhooks->get_count();
		$rtn=json_decode(json_encode($rtn), true);
		if(!isset($rtn['count'])){return 0;}
		return $rtn['count'];
	} catch ( WC_API_Client_Exception $e ) {
		$code=$e->get_response()->code;
		$msg=json_decode($e->get_response()->body,true);
		return "woocommerceGetWebhooksCount {$code} Error - {$msg['errors'][0]['message']}";
	}
}

	//print_r( $client->webhooks->update( $webhook_id, array( 'secret' => 'some_secret' ) ) );
	//print_r( $client->webhooks->delete( $webhook_id ) );
	//print_r( $client->webhooks->get_count() );
	//print_r( $client->webhooks->get_deliveries( $webhook_id ) );
	//print_r( $client->webhooks->get_delivery( $webhook_id, $delivery_id );
?>