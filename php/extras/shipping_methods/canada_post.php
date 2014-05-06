<?php
/*
	Integration methods for integrating with Canada Postal Services
	References:
		https://www.canadapost.ca/cpo/mc/business/productsservices/developers/services/fundamentals.jsf
		http://www.canadapost.ca/cpo/mc/business/productsservices/developers/services/shippingmanifest/manifest.jsf

*/

/*Non-commercial Shipment Calls */

function cpCreateNCShipment($params=array()){

}
function cpGetNCShipment($params=array()){

}
function cpGetNCShipmentDetails($params=array()){

}
function cpGetNCShipmentReceipt($params=array()){

}
function cpGetNCShipments($params=array()){

}
function cpGetShipmentLabel($params=array()){
	//check for required params
	$required=array(
		'-username','-password','label_url'
		);
	$missing=array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){$missing[]=$key;}
	}
	if(count($missing)){
		$params['-missing']=$missing;
		$params['-error']='missing params';
		return $params;
	}
	$progpath=dirname(__FILE__);
	$label_path="{$progpath}/labels";
	if(!is_dir($label_path)){buildDir($label_path);}
	$label_file='shipmentArtifact_'.sha1($params['label_url']).'.pdf';
	$label_afile="{$label_path}/{$label_file}";
	if(is_file($label_afile)){unlink($label_afile);}

	$curl = curl_init($params['label_url']); // Create REST Request
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept:application/pdf', 'Accept-Language:en-CA'));
	$curl_response = curl_exec($curl); // Execute REST Request
	$contentType =  curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
	curl_close($curl);
	//echo "HERE".printValue($params). $contentType .printValue($curl_response);exit;
	if ( strpos($contentType, 'application/pdf' ) !== FALSE ) {
		// Writing binary response to file
		file_put_contents($label_afile, $curl_response);
		if(is_file($label_afile)){return $label_afile;}
	}
	if (strpos($contentType, 'xml' ) > -1 ) {return xml2Array($curl_response);}
	return $curl_response;
}
//---------- begin function cpGetRates--------------------
/**
* @describe Canada Post rates: returns a list of shipping services, prices and transit times for a given item to be shipped.
* @param params array - params
*	-username
*	-password
*	-account_number
*	sender_zipcode
*	recipient_zipcode
*	parcel_weight - in kg
* @return array - an array of rates with the following key/value pairs
*	name - service name
*	code - service code
*	base - base cost
*	total - total cost
*	expected_delivery_date
*	expected_transit_time - in days
*	gst and gst_percent
*	pst and pst_percent
*	hst and hst_percent
*	taxes_total
*	taxes_total_percent
* @usage $newarr=sortArrayByKey($arr1,'name',SORT_DESC);
*/
function cpGetRates($params=array()){
	//check for required params
	$required=array(
		'-username','-password','-account_number',
		'sender_zipcode','recipient_zipcode','parcel_weight'
		);
	$missing=array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){$missing[]=$key;}
	}
	if(count($missing)){
		$params['-missing']=$missing;
		$params['-error']='missing params';
		return $params;
	}
	$params['-service_url'] = 'https://ct.soa-gw.canadapost.ca/rs/ship/price';
	//default shipping point to the sender_zipcode
	if(!strlen($params['-shipping_point'])){$params['-shipping_point']=$params['sender_zipcode'];}
	//notify email
	$params['-xml'] = <<<CANADAPOSTXML
<?xml version="1.0" encoding="UTF-8"?>
<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v2">
  <customer-number>{$params['-account_number']}</customer-number>
  <parcel-characteristics>
    <weight>{$params['parcel_weight']}</weight>
  </parcel-characteristics>
  <origin-postal-code>{$params['-shipping_point']}</origin-postal-code>
  <destination>
    <domestic>
      <postal-code>{$params['recipient_zipcode']}</postal-code>
    </domestic>
  </destination>
</mailing-scenario>
CANADAPOSTXML;
	$curl = curl_init($params['-service_url']); // Create REST Request
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	$progpath=dirname(__FILE__);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem"); // Signer Certificate in PEM format
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params['-xml']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.cpc.ship.rate-v2+xml', 'Accept: application/vnd.cpc.ship.rate-v2+xml'));
	$curl_response = curl_exec($curl); // Execute REST Request
	curl_close($curl);
	$xml_array=xml2Array($curl_response);
	if(!isset($xml_array['price-quotes']['price-quote'][0])){
		//failed to get quotes
		return null;
	}
	$rtn=array();
	foreach($xml_array['price-quotes']['price-quote'] as $quote){
    	$service_code=$quote['service-code'];
    	//base,hst,gst,pst,taxes_total,hst_percent,total
    	$rtn['rates'][$service_code]=array(
			'code'						=> $service_code,
			'base'						=> $quote['price-details']['base'],
			'total'						=> $quote['price-details']['due'],
			'name'						=> $quote['service-name'],
			'expected_delivery_date'	=> $quote['service-standard']['expected-delivery-date'],
			'expected_delivery_date_utime'	=> strtotime($quote['service-standard']['expected-delivery-date']),
			'expected_transit_time'		=> $quote['service-standard']['expected-transit-time'],
		);
		$taxes_total=0;
		$taxes_total_percent=0;
		$tax_types=array('gst','pst','hst');
		foreach($tax_types as $type){
			if(isNum($quote['price-details']['taxes'][$type])){
	        	$rtn['rates'][$service_code][$type]=$quote['price-details']['taxes'][$type];
	        	$taxes_total+=$rtn['rates'][$service_code][$type];
	        	if(isNum($quote['price-details']['taxes']["{$type}_attr"]['percent'])){
	            	$rtn['rates'][$service_code]["{$type}_percent"]=$quote['price-details']['taxes']["{$type}_attr"]['percent'];
					$taxes_total_percent+=$rtn['rates'][$service_code]["{$type}_percent"];
				}
			}
		}
		$rtn['rates'][$service_code]['taxes_total']=$taxes_total;
		$rtn['rates'][$service_code]['taxes_total_percent']=$taxes_total_percent;
	}
	//sort by delivery date
	$rtn['rates']=sortArrayByKeys($rtn['rates'], array('total'=>SORT_ASC,'expected_delivery_date_utime'=>SORT_ASC));
	return $rtn;
}
/* Commercial Shipment Calls */
function cpCreateShipment($params=array()){
	//check for required params
	$required=array(
		'-username','-password','-account_number',
		'sender_company','sender_phone','sender_address','sender_city','sender_state','sender_country','sender_zipcode',
		'recipient_name','recipient_address','recipient_city','recipient_state','recipient_country','recipient_zipcode','recipient_email',
		'parcel_weight','parcel_width','parcel_length','parcel_height',
		'ordernumber'
		);
	$missing=array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){$missing[]=$key;}
	}
	if(count($missing)){
		$params['-missing']=$missing;
		$params['-error']='missing params';
		return $params;
	}
	if(!strlen($params['-group_id'])){$params['-group_id']=date('Ymd');}
	if(!strlen($params['-shipdate'])){$params['-shipdate']=date('Y-m-d');}
	if(!strlen($params['-contract_id'])){$params['-contract_id']='1234567890';}
	//output_format options: 8.5x11, 4x6
	if(!strlen($params['-output_format'])){$params['-output_format']='8.5x11';}
	/*service_code
		DOM.RP	Regular Parcel
		DOM.EP	Expedited Parcel
		DOM.XP	Xpresspost
		DOM.PC	Priority
	*/
	if(!strlen($params['-service_code'])){$params['-service_code']='DOM.RP';}
	/*
		CreditCard = the payment will be by credit card.
		Account = the payment will be by an existing contract with the paid-by-customer.
	*/
	if(!strlen($params['-payment_method'])){$params['-payment_method']='CreditCard';}
	//default shipping point to the sender_zipcode
	if(!strlen($params['-shipping_point'])){$params['-shipping_point']=$params['sender_zipcode'];}
	//notify email
	if(!strlen($params['notify_email'])){$params['notify_email']=$params['recipient_email'];}
	if(!strlen($params['message'])){$params['message']='Thank you for your order';}
	$params['-service_url'] = 'https://ct.soa-gw.canadapost.ca/rs/' . $params['-account_number'] . '/' . $params['-account_number'] . '/shipment';
	$params['-xml'] = <<<CANADAPOSTXML
<?xml version="1.0" encoding="UTF-8"?>
<shipment xmlns="http://www.canadapost.ca/ws/shipment-v5">
	<group-id>{$params['-group_id']}</group-id>
	<!-- <transmit-shipment>true</transmit-shipment> -->
	<requested-shipping-point>{$params['-shipping_point']}</requested-shipping-point>
	<cpc-pickup-indicator>true</cpc-pickup-indicator>
	<expected-mailing-date>{$params['-shipdate']}</expected-mailing-date>
	<delivery-spec>
		<service-code>{$params['-service_code']}</service-code>
			<sender>
				<name>{$params['sender_name']}</name>
				<company>{$params['sender_company']}</company>
				<contact-phone>{$params['sender_phone']}</contact-phone>
				<address-details>
					<address-line-1>{$params['sender_address']}</address-line-1>
					<city>{$params['sender_city']}</city>
					<prov-state>{$params['sender_state']}</prov-state>
					<country-code>{$params['sender_country']}</country-code>
					<postal-zip-code>{$params['sender_zipcode']}</postal-zip-code>
				</address-details>
			</sender>
			<destination>
				<name>{$params['recipient_name']}</name>
				<address-details>
					<address-line-1>{$params['recipient_address']}</address-line-1>
					<city>{$params['recipient_city']}</city>
					<prov-state>{$params['recipient_state']}</prov-state>
					<country-code>{$params['recipient_country']}</country-code>
					<postal-zip-code>{$params['recipient_zipcode']}</postal-zip-code>
				</address-details>
			</destination>
		<options>
			<option>
				<option-code>DC</option-code>
			</option>
		</options>
		<parcel-characteristics>
			<weight>{$params['parcel_weight']}</weight>
			<dimensions>
				<length>{$params['parcel_length']}</length>
				<width>{$params['parcel_width']}</width>
				<height>{$params['parcel_height']}</height>
			</dimensions>
			<unpackaged>false</unpackaged>
			<mailing-tube>false</mailing-tube>
		</parcel-characteristics>
		<notification>
			<email>{$params['notify_email']}</email>
			<on-shipment>true</on-shipment>
			<on-exception>false</on-exception>
			<on-delivery>true</on-delivery>
		</notification>
		<print-preferences>
			<output-format>{$params['-output_format']}</output-format>
		</print-preferences>
		<preferences>
			<show-packing-instructions>false</show-packing-instructions>
			<show-postage-rate>false</show-postage-rate>
			<show-insured-value>true</show-insured-value>
		</preferences>
		<references>
			<cost-centre>{$params['cost_centre']}</cost-centre>
			<customer-ref-1>{$params['message']}</customer-ref-1>
			<customer-ref-2>OrderNumber: {$params['ordernumber']}</customer-ref-2>
		</references>
		<settlement-info>
			<contract-id>{$params['-contract_id']}</contract-id>
			<intended-method-of-payment>{$params['-payment_method']}</intended-method-of-payment>
		</settlement-info>
	</delivery-spec>
</shipment>
CANADAPOSTXML;
	$curl = curl_init($params['-service_url']); // Create REST Request
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	$progpath=dirname(__FILE__);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem"); // Signer Certificate in PEM format
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params['-xml']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.cpc.shipment-v5+xml', 'Accept: application/vnd.cpc.shipment-v5+xml'));

	$curl_response = curl_exec($curl); // Execute REST Request
	curl_close($curl);
	$xml_array = xml2Array($curl_response);
	if(!isset($xml_array['shipment-info'])){return $xml_array;}
	//include the shipment info in $params and pass it back.
	if(strlen($xml_array['shipment-info']['shipment-id'])){
    	$params['shipment-id']=$xml_array['shipment-info']['shipment-id'];
	}
	if(strlen($xml_array['shipment-info']['shipment-status'])){
    	$params['shipment-status']=$xml_array['shipment-info']['shipment-status'];
	}
	if(strlen($xml_array['shipment-info']['tracking-pin'])){
    	$params['tracking-pin']=$xml_array['shipment-info']['tracking-pin'];
	}
	foreach($xml_array['shipment-info']['links']['link'] as $links){
    	if(isset($links['rel'])){
        	$key=$links['rel'];
        	$params["{$key}_url"]=$links['href'];
		}
	}
	return $params;
}

function cpGetCustomerInformation($params=array()){

}
function cpGetGroups($params=array()){

}
function cpGetManifest($params=array()){
		//check for required params
	$required=array(
		'-username','-password','manifest_url'
		);
	$missing=array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){$missing[]=$key;}
	}
	if(count($missing)){
		$params['-missing']=$missing;
		$params['-error']='missing params';
		return $params;
	}
	$progpath=dirname(__FILE__);
	$curl = curl_init($params['manifest_url']); // Create REST Request
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept:application/vnd.cpc.manifest-v5+xml'));
	$curl_response = curl_exec($curl); // Execute REST Request
	$contentType =  curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
	curl_close($curl);
	$xml_array=xml2Array($curl_response);
	if(!isset($xml_array['manifest'])){return $xml_array;}
	//include the shipment info in $params and pass it back.
	if(strlen($xml_array['manifest']['po-number'])){
    	$params['po-number']=$xml_array['manifest']['po-number'];
	}
	foreach($xml_array['manifest']['links']['link'] as $links){
    	if(isset($links['rel'])){
        	$key=$links['rel'];
        	$params["{$key}_url"]=$links['href'];
		}
	}
	return $params;

}
function cpGetManifestArtifact($params=array()){
	//check for required params
	$required=array(
		'-username','-password','artifact_url'
		);
	$missing=array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){$missing[]=$key;}
	}
	if(count($missing)){
		$params['-missing']=$missing;
		$params['-error']='missing params';
		return $params;
	}
	$progpath=dirname(__FILE__);
	$label_path="{$progpath}/labels";
	if(!is_dir($label_path)){buildDir($label_path);}
	$label_file='manifestArtifact_'.sha1($params['artifact_url']).'.pdf';
	$label_afile="{$label_path}/{$label_file}";
	if(is_file($label_afile)){unlink($label_afile);}

	$curl = curl_init($params['artifact_url']); // Create REST Request
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept:application/pdf', 'Accept-Language:en-CA'));
	$curl_response = curl_exec($curl); // Execute REST Request
	$contentType =  curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
	curl_close($curl);
	//echo "HERE".printValue($params). $contentType .printValue($curl_response);exit;
	if ( strpos($contentType, 'application/pdf' ) !== FALSE ) {
		// Writing binary response to file
		file_put_contents($label_afile, $curl_response);
		if(is_file($label_afile)){return $label_afile;}
	}
	if (strpos($contentType, 'xml' ) > -1 ) {return xml2Array($curl_response);}
	return $curl_response;
}
function cpGetManifests($params=array()){

}
function cpGetMoBoCustomerInformation($params=array()){

}
function cpGetShipmentArtifact($params=array()){

}
function cpGetShipmentDetails($params=array()){

}
function cpGetShipmentPrice($params=array()){

}
function cpGetShipmentReceipt($params=array()){

}
function cpGetShipments($params=array()){

}
function cpTransmitShipments($params=array()){
	//check for required params
	$required=array(
		'-username','-password','-account_number',
		'sender_zipcode','sender_address','sender_city','sender_state','sender_country','sender_company'
		);
	$missing=array();
	foreach($required as $key){
		if(!isset($params[$key]) || !strlen($params[$key])){$missing[]=$key;}
	}
	if(count($missing)){
		$params['-missing']=$missing;
		$params['-error']='missing params';
		return $params;
	}
	//default shipping point to the sender_zipcode
	if(!strlen($params['-shipping_point'])){$params['-shipping_point']=$params['sender_zipcode'];}
	// REST URL
	$params['-service_url']= 'https://ct.soa-gw.canadapost.ca/rs/' . $params['-account_number'] . '/' . $params['-account_number'] . '/manifest';
	//default shipping point to the sender_zipcode
	if(!strlen($params['-shipping_point'])){$params['-shipping_point']=$params['sender_zipcode'];}
	//groupid
	if(!strlen($params['-group_id'])){$params['-group_id']=date('Ymd');}
	//notify email
	$params['-xml'] = <<<CANADAPOSTXML
<?xml version="1.0" encoding="UTF-8"?>
<transmit-set xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.canadapost.ca/ws/manifest-v5" >
  <group-ids>
    <group-id>{$params['-group_id']}</group-id>
  </group-ids>
  <requested-shipping-point>{$params['-shipping_point']}</requested-shipping-point>
  <cpc-pickup-indicator>true</cpc-pickup-indicator>
  <detailed-manifests>true</detailed-manifests>
  <method-of-payment>Account</method-of-payment>
  <manifest-address>
    <manifest-company>{$params['sender_company']}</manifest-company>
    <phone-number>{$params['sender_phone']}</phone-number>
    <address-details>
      <address-line-1>{$params['sender_address']}</address-line-1>
      <city>{$params['sender_city']}</city>
      <prov-state>{$params['sender_state']}</prov-state>
  	  <country-code>{$params['sender_country']}</country-code>
      <postal-zip-code>{$params['sender_zipcode']}</postal-zip-code>
    </address-details>
  </manifest-address>
</transmit-set>
CANADAPOSTXML;
	$curl = curl_init($params['-service_url']); // Create REST Request
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	$progpath=dirname(__FILE__);
	curl_setopt($curl, CURLOPT_CAINFO, "{$progpath}/canada_post/cacert.pem"); // Signer Certificate in PEM format
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params['-xml']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $params['-username'] . ':' . $params['-password']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.cpc.manifest-v5+xml', 'Accept: application/vnd.cpc.manifest-v5+xml'));
	$curl_response = curl_exec($curl); // Execute REST Request
	curl_close($curl);
	$xml_array=xml2Array($curl_response);
	if(!isset($xml_array['manifests'])){return $xml_array;}
	//include the manifest in $params and pass it back.
	if(isset($xml_array['manifests']['link_attr']['rel']) && $xml_array['manifests']['link_attr']['rel']=='manifest'){
    	$params['manifest_url']=$xml_array['manifests']['link_attr']['href'];
	}
	else{
    	echo "ERROR:".printValue($xml_array);exit;
	}
	return $params;
}
function cpVoidShipment($params=array()){

}
function cpGetDeliveryConfirmationCertificate($params=array()){

}
function cpGetSignatureImage($params=array()){

}
function cpGetTrackingDetails($params=array()){

}
function cpGetTrackingSummary($params=array()){

}

?>