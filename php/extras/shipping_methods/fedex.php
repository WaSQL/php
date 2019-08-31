<?php
$progpath=dirname(__FILE__);
/* 
	load the fedex common library
	Technical Support hotline phone: 1.877.339.2774 (When prompted, please say "Web Services")
*/
require_once("{$progpath}/fedex/common.php5");
date_default_timezone_set('America/Denver');
//------------------
function fedexAddressVerification($auth=array(),$addresses=array()){
	//auth - key, pass, account, meter, [-test, -accuracy]
	//accuracy can be EXACT, TIGHT, MEDIUM, LOOSE
	if(!isset($auth['-accuracy'])){$auth['-accuracy']='LOOSE';}
	//adresses - AddressId, Address - Streetlines, PostalCode, CompanyName
	$progpath=dirname(__FILE__);
	$path_to_wsdl = "{$progpath}/fedex/AddressValidationService_v2.wsdl";
	if(isset($auth['-test']) && $auth['-test']==1){
		$path_to_wsdl = "{$progpath}/fedex/AddressValidationService_v2_test.wsdl";
    	}
	ini_set("soap.wsdl_cache_enabled", "0");
	$client = new SoapClient($path_to_wsdl, array('trace' => 0)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$request['WebAuthenticationDetail'] = array('UserCredential' =>array('Key' => $auth['key'], 'Password' => $auth['pass']));
	$request['ClientDetail'] = array('AccountNumber' => $auth['account'], 'MeterNumber' => $auth['meter']);
	$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Address Validation Request v2 using PHP ***');
	$request['Version'] = array('ServiceId' => 'aval', 'Major' => '2', 'Intermediate' => '0', 'Minor' => '0');
	$request['RequestTimestamp'] = date('c');
	$request['Options'] = array(
		'CheckResidentialStatus' => 1,
	    'MaximumNumberOfMatches' => 5,
	    'StreetAccuracy' => $auth['-accuracy'], 		//EXACT, TIGHT, MEDIUM, LOOSE
	    'DirectionalAccuracy' => $auth['-accuracy'], 	//EXACT, TIGHT, MEDIUM, LOOSE
	    'CompanyNameAccuracy' => $auth['-accuracy'],	//EXACT, TIGHT, MEDIUM, LOOSE
	    'ConvertToUpperCase' => 1,
	    'RecognizeAlternateCityNames' => 1,
	    'ReturnParsedElements' => 1
		);
	$request['AddressesToValidate'] = array($addresses);
/* 		array(
		'AddressId' => 'BEST BUY PURCHASING LLC 5',
        'Address' => array(
			'StreetLines' 			=> array('1770 APPLE GLEN BLVD'),
			'City'					=> 'FORT WAYNE',
			'StateOrProvinceCode'	=> 'IN',
        	'PostalCode' 			=> '46804',
			)
		), */
	try{
		$response = $client ->addressValidation($request);
	    if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
	        $rtn = array(
				'result'=>'SUCCESS',
				'client'=>$client, 
				'response'=>$response
				);
	        return $rtn;
	    	}
	    else{
			$rtn = array(
				'result'=>'FAILED',
				'client'=>$client, 
				'response'=>$response
				);
	        return $rtn;
	    	}
	 //   writeToLog($client);    // Write to log file
	
		} 
	catch (SoapFault $exception) {
		$rtn = array(
			'result'=>'EXCEPTION',
			'client'=>$client, 
			'response'=>$exception
			);
	    return $rtn;
		}
	}
//------------------
function fedexCreatePendingShipment($params=array()){
	/* Credentials*/
	$credentials=array(
		'Key'			=> $params['Key'],
		'Password'		=> $params['Password']
		);
	$account=array(
		'AccountNumber'	=> $params['AccountNumber'],
		'MeterNumber' 	=> $params['MeterNumber']
		);
	/*Load libraries and wsdls*/
	$progpath=dirname(__FILE__);
	date_default_timezone_set('America/Denver');
	$path_to_wsdl = "{$progpath}/fedex/ShipService_v7.wsdl";
	if(isset($params['-test']) && $params['-test']==1){
		$path_to_wsdl = "{$progpath}/fedex/ShipService_v7_test.wsdl";
    	}
	$cache=isset($params['-cache'])?$params['-cache']:0;
	ini_set("soap.wsdl_cache_enabled", "{$cache}");
	/*Soap Request*/
	$client = new SoapClient($path_to_wsdl, array('trace' => 0)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$request=array();
	$request['WebAuthenticationDetail'] = array('UserCredential' =>$credentials);
	$request['ClientDetail'] = $account;
	$request['TransactionDetail'] = array('CustomerTransactionId' => '*** EmailLabel Request v7 using PHP ***');
	$request['Version'] = array('ServiceId' => 'ship', 'Major' => '7', 'Intermediate' => '0', 'Minor' => '0');
	$request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
	$request['RequestedShipment']['ShipTimestamp'] = date('c');
	$request['RequestedShipment']['ServiceType'] = 'FEDEX_GROUND'; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
	$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
	//Shipper Contact
	$shipper_contact=array();
	$fields=array('PersonName','CompanyName','PhoneNumber');
	foreach($fields as $field){
		$sfield='Shipper_' . $field;
		if(isset($params[$sfield])){$shipper_contact[$field]=$params[$sfield];}
    	}
    //Shipper Address
	$shipper_address=array();
	if(!isset($params['Shipper_CountryCode'])){$params['Shipper_CountryCode']='US';}
	$fields=array('StreetLines','City','StateOrProvinceCode','PostalCode','CountryCode','Residential');
	foreach($fields as $field){
		$sfield='Shipper_' . $field;
		if(isset($params[$sfield])){$shipper_address[$field]=$params[$sfield];}
    	}
    if(count($shipper_contact)){
		$request['RequestedShipment']['Shipper']['Contact']=$shipper_contact;
		}
	if(count($shipper_address)){
		$request['RequestedShipment']['Shipper']['Address'] = $shipper_address;
		}
	
	//Recipeint Contact
	$recipient_contact=array();
	$fields=array('PersonName','CompanyName','PhoneNumber');
	foreach($fields as $field){
		$sfield='Recipient_' . $field;
		if(isset($params[$sfield])){$recipient_contact[$field]=$params[$sfield];}
    	}
	//Recipeint address
	$recipient_address=array();
	$fields=array('StreetLines','City','StateOrProvinceCode','PostalCode','CountryCode','Residential');
	if(!isset($params['Recipient_CountryCode'])){$params['Recipient_CountryCode']='US';}
	if($params['Residential']){$params['Recipient_Residential']=true;}
	foreach($fields as $field){
		$sfield='Recipient_' . $field;
		if(isset($params[$sfield])){$recipient_address[$field]=$params[$sfield];}
    	}
    if(count($recipient_contact)){
		$request['RequestedShipment']['Recipient']['Contact']=$recipient_contact;
		}
	if(count($recipient_address)){
		$request['RequestedShipment']['Recipient']['Address'] = $recipient_address;
		}
	//Charge to what account?
	if(isset($params['ChargeAccount'])){
		$ShippingChargesPayment=array(
			'PaymentType'	=> isset($params['ChargeAccountType'])?$params['ChargeAccountType']:'THIRD_PARTY',
			'Payor' => array(
				'AccountNumber'=>$params['ChargeAccount'],
				'CountryCode'=>isset($params['ChargeAccountCountry'])?$params['ChargeAccountCountry']:$shipper_address['CountryCode']
				)
			);
    	}
    else{
		$ShippingChargesPayment=array(
			'PaymentType'	=>'SENDER',
			'Payor' => array('AccountNumber'=>$account['AccountNumber'],'CountryCode'=>$shipper_address['CountryCode'])
			);
    	}
	$request['RequestedShipment']['ShippingChargesPayment'] = $ShippingChargesPayment;
	//Email
	$request['RequestedShipment']['SpecialServicesRequested'] = array(
		'SpecialServiceTypes' => array ('RETURN_SHIPMENT', 'PENDING_SHIPMENT'),
		'EMailNotificationDetail' => array(
			'PersonalMessage' => $params['PersonalMessage'],
			'Recipients' => array(
				'EMailNotificationRecipientType' => 'RECIPIENT',
				'EMailAddress' => $params['EmailTo'],
				'Format' => 'HTML',
				'Localization' => array('LanguageCode' => 'EN', 'LocaleCode' => 'US')
				)
			),
		'ReturnShipmentDetail' => array(
			'ReturnType' => 'PENDING',
			'ReturnEMailDetail' => array(
				'MerchantPhoneNumber' => $shipper_contact['PhoneNumber']
				)
			),
		'PendingShipmentDetail' => array(
			'Type' => 'EMAIL', 
			'ExpirationDate' => date('Y-m-d'),
			'EmailLabelDetail' => array(
				'NotificationEMailAddress' => $params['EmailFrom'],
				'NotificationMessage' => ''
				)
			)
		);
	$request['RequestedShipment']['LabelSpecification'] = array('LabelFormatType' => 'COMMON2D','ImageType' => 'PNG');
	$request['RequestedShipment']['RateRequestTypes'] = 'LIST';
	$request['RequestedShipment']['PackageCount'] = '1';
	$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
	//Packing Info
	$PackageInfo=array(
		'SequenceNumber' => '1',
		'InsuredValue' => array(
			'Amount' => isset($params['ItemValue'])?$params['ItemValue']:25,
			'Currency' => 'USD'
			),
		'ItemDescription' => $params['ItemDescription'],
		'Weight' => array('Value' => $params['ItemWeight'],'Units' => 'LB')
		);
	if(isset($params['ItemDimensions']) && is_array($params['ItemDimensions'])){$PackageInfo['Dimensions']=$params['ItemDimensions'];}
	//References: BILL_OF_LADING, CUSTOMER_REFERENCE, DEPARTMENT_NUMBER, INVOICE_NUMBER, P_O_NUMBER, SHIPMENT_INTEGRITY, STORE_NUMBER
	$references=array();
	//BILL_OF_LADING
	if(isset($params['BillOfLading'])){
		array_push($references,array('CustomerReferenceType'=>'BILL_OF_LADING','Value'=>$params['BillOfLading']));
		}
	//CUSTOMER_REFERENCE
	if(isset($params['CustomerReference'])){
		array_push($references,array('CustomerReferenceType'=>'CUSTOMER_REFERENCE','Value'=>$params['CustomerReference']));
		}
	else if(isset($params['Reference'])){
		array_push($references,array('CustomerReferenceType'=>'CUSTOMER_REFERENCE','Value'=>$params['Reference']));
		}
	else if(isset($params['RMANumber'])){
		array_push($references,array('CustomerReferenceType'=>'CUSTOMER_REFERENCE','Value'=>"RMA #: " . $params['RMANumber']));
		}
	//DEPARTMENT_NUMBER
	if(isset($params['DepartmentNumber'])){
		array_push($references,array('CustomerReferenceType'=>'DEPARTMENT_NUMBER','Value'=>$params['DepartmentNumber']));
		}
	else if(isset($params['Department'])){
		array_push($references,array('CustomerReferenceType'=>'DEPARTMENT_NUMBER','Value'=>$params['Department']));
		}
	//INVOICE_NUMBER
	if(isset($params['InvoiceNumber'])){
		array_push($references,array('CustomerReferenceType'=>'INVOICE_NUMBER','Value'=>$params['InvoiceNumber']));
		}
	else if(isset($params['Invoice'])){
		array_push($references,array('CustomerReferenceType'=>'INVOICE_NUMBER','Value'=>$params['Invoice']));
		}
	//P_O_NUMBER
	if(isset($params['PONumber'])){
		array_push($references,array('CustomerReferenceType'=>'P_O_NUMBER','Value'=>$params['PONumber']));
		}
	//SHIPMENT_INTEGRITY
	if(isset($params['ShipmentIntegrity'])){
		array_push($references,array('CustomerReferenceType'=>'SHIPMENT_INTEGRITY','Value'=>$params['ShipmentIntegrity']));
		}
	//STORE_NUMBER
	if(isset($params['StoreNumber'])){
		array_push($references,array('CustomerReferenceType'=>'STORE_NUMBER','Value'=>$params['StoreNumber']));
		}
	else if(isset($params['Store'])){
		array_push($references,array('CustomerReferenceType'=>'STORE_NUMBER','Value'=>$params['Store']));
		}
	if(count($references)){$PackageInfo['CustomerReferences']=$references;}
	$request['RequestedShipment']['RequestedPackageLineItems'] = array('0' =>$PackageInfo);
	//
	$rtn=array();
	$rtn['wsdl']=$path_to_wsdl;
	try{
	    $response = $client->processShipment($request);
	    if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
			$rtn['tracking_number']=(string)$response -> CompletedShipmentDetail -> CompletedPackageDetails -> TrackingIds -> TrackingNumber;
	        $rtn['response']=$response;
	        return $rtn;
	    	}
	    else{
			$rtn['errors']=$response -> Notifications;
			$rtn['request']=$request;
			return $rtn;
	    	}
		}
	catch (SoapFault $exception) {
		$rtn['errors']=$exception;
		$rtn['request']=$request;
		}
	return $rtn;
	}
function fedexProcessShipment($params=array()){
	/* Credentials*/
	$credentials=array(
		'Key'			=> $params['Key'],
		'Password'		=> $params['Password']
		);
	$account=array(
		'AccountNumber'	=> $params['AccountNumber'],
		'MeterNumber' 	=> $params['MeterNumber']
		);
	/*Load libraries and wsdls*/
	$progpath=dirname(__FILE__);
	date_default_timezone_set('America/Denver');
	$path_to_wsdl = "{$progpath}/fedex/ShipService_v7.wsdl";
	if(isset($params['-test']) && $params['-test']==1){
		$path_to_wsdl = "{$progpath}/fedex/ShipService_v7_test.wsdl";
    	}
	$cache=isset($params['-cache'])?$params['-cache']:0;
	ini_set("soap.wsdl_cache_enabled", "{$cache}");
	/*Soap Request*/
	$client = new SoapClient($path_to_wsdl, array('trace' => 1)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$request=array();
	$request['WebAuthenticationDetail'] = array('UserCredential' =>$credentials);
	$request['ClientDetail'] = $account;
	$request['TransactionDetail'] = array('CustomerTransactionId' => '*** Ground Domestic Shipping Request v7 using PHP ***');
	$request['Version'] = array('ServiceId' => 'ship', 'Major' => '7', 'Intermediate' => '0', 'Minor' => '0');
	$request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
	$request['RequestedShipment']['ShipTimestamp'] = date('c');
	$request['RequestedShipment']['ServiceType'] = 'FEDEX_GROUND'; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
	$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
	//Shipper Contact
	$shipper_contact=array();
	$fields=array('PersonName','CompanyName','PhoneNumber');
	foreach($fields as $field){
		$sfield='Shipper_' . $field;
		if(isset($params[$sfield])){$shipper_contact[$field]=$params[$sfield];}
    	}
    //Shipper Address
	$shipper_address=array();
	if(!isset($params['Shipper_CountryCode'])){$params['Shipper_CountryCode']='US';}
	$fields=array('StreetLines','City','StateOrProvinceCode','PostalCode','CountryCode','Residential');
	foreach($fields as $field){
		$sfield='Shipper_' . $field;
		if(isset($params[$sfield])){$shipper_address[$field]=$params[$sfield];}
    	}
    if(count($shipper_contact)){
		$request['RequestedShipment']['Shipper']['Contact']=$shipper_contact;
		}
	if(count($shipper_address)){
		$request['RequestedShipment']['Shipper']['Address'] = $shipper_address;
		}
	
	//Recipeint Contact
	$recipient_contact=array();
	$fields=array('PersonName','CompanyName','PhoneNumber');
	foreach($fields as $field){
		$sfield='Recipient_' . $field;
		if(isset($params[$sfield])){$recipient_contact[$field]=$params[$sfield];}
    	}
	//Recipeint address
	$recipient_address=array();
	$fields=array('StreetLines','City','StateOrProvinceCode','PostalCode','CountryCode','Residential');
	if(!isset($params['Recipient_CountryCode'])){$params['Recipient_CountryCode']='US';}
	if($params['Residential']){$params['Recipient_Residential']=true;}
	foreach($fields as $field){
		$sfield='Recipient_' . $field;
		if(isset($params[$sfield])){$recipient_address[$field]=$params[$sfield];}
    	}
    if(count($recipient_contact)){
		$request['RequestedShipment']['Recipient']['Contact']=$recipient_contact;
		}
	if(count($recipient_address)){
		$request['RequestedShipment']['Recipient']['Address'] = $recipient_address;
		}
	//Charge to what account?
	if(isset($params['ChargeAccount'])){
		$ShippingChargesPayment=array(
			'PaymentType'	=> isset($params['ChargeAccountType'])?$params['ChargeAccountType']:'THIRD_PARTY',
			'Payor' => array(
				'AccountNumber'=>$params['ChargeAccount'],
				'CountryCode'=>isset($params['ChargeAccountCountry'])?$params['ChargeAccountCountry']:$shipper_address['CountryCode']
				)
			);
    	}
    else{
		$ShippingChargesPayment=array(
			'PaymentType'	=>'SENDER',
			'Payor' => array('AccountNumber'=>$account['AccountNumber'],'CountryCode'=>$shipper_address['CountryCode'])
			);
    	}
    $imagetype=isset($params['ImageType'])?$params['ImageType']:'PNG';
	$request['RequestedShipment']['ShippingChargesPayment'] = $ShippingChargesPayment;
	$request['RequestedShipment']['LabelSpecification'] = array('LabelFormatType' => 'COMMON2D','ImageType' => $imagetype);
	$request['RequestedShipment']['RateRequestTypes'] = 'LIST';
	$request['RequestedShipment']['PackageCount'] = '1';
	$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
	//Packing Info
	$PackageInfo=array(
		'SequenceNumber' => '1',
		'InsuredValue' => array(
			'Amount' => isset($params['ItemValue'])?$params['ItemValue']:25,
			'Currency' => 'USD'
			),
		'ItemDescription' => $params['ItemDescription'],
		'Weight' => array('Value' => $params['ItemWeight'],'Units' => 'LB')
		);
	if(isset($params['ItemDimensions']) && is_array($params['ItemDimensions'])){$PackageInfo['Dimensions']=$params['ItemDimensions'];}
	//References: BILL_OF_LADING, CUSTOMER_REFERENCE, DEPARTMENT_NUMBER, INVOICE_NUMBER, P_O_NUMBER, SHIPMENT_INTEGRITY, STORE_NUMBER
	$references=array();
	//BILL_OF_LADING
	if(isset($params['BillOfLading'])){
		array_push($references,array('CustomerReferenceType'=>'BILL_OF_LADING','Value'=>$params['BillOfLading']));
		}
	//CUSTOMER_REFERENCE
	if(isset($params['CustomerReference'])){
		array_push($references,array('CustomerReferenceType'=>'CUSTOMER_REFERENCE','Value'=>$params['CustomerReference']));
		}
	else if(isset($params['Reference'])){
		array_push($references,array('CustomerReferenceType'=>'CUSTOMER_REFERENCE','Value'=>$params['Reference']));
		}
	else if(isset($params['RMANumber'])){
		array_push($references,array('CustomerReferenceType'=>'CUSTOMER_REFERENCE','Value'=>"RMA #: " . $params['RMANumber']));
		}
	//DEPARTMENT_NUMBER
	if(isset($params['DepartmentNumber'])){
		array_push($references,array('CustomerReferenceType'=>'DEPARTMENT_NUMBER','Value'=>$params['DepartmentNumber']));
		}
	else if(isset($params['Department'])){
		array_push($references,array('CustomerReferenceType'=>'DEPARTMENT_NUMBER','Value'=>$params['Department']));
		}
	//INVOICE_NUMBER
	if(isset($params['InvoiceNumber'])){
		array_push($references,array('CustomerReferenceType'=>'INVOICE_NUMBER','Value'=>$params['InvoiceNumber']));
		}
	else if(isset($params['Invoice'])){
		array_push($references,array('CustomerReferenceType'=>'INVOICE_NUMBER','Value'=>$params['Invoice']));
		}
	//P_O_NUMBER
	if(isset($params['PONumber'])){
		array_push($references,array('CustomerReferenceType'=>'P_O_NUMBER','Value'=>$params['PONumber']));
		}
	//SHIPMENT_INTEGRITY
	if(isset($params['ShipmentIntegrity'])){
		array_push($references,array('CustomerReferenceType'=>'SHIPMENT_INTEGRITY','Value'=>$params['ShipmentIntegrity']));
		}
	//STORE_NUMBER
	if(isset($params['StoreNumber'])){
		array_push($references,array('CustomerReferenceType'=>'STORE_NUMBER','Value'=>$params['StoreNumber']));
		}
	else if(isset($params['Store'])){
		array_push($references,array('CustomerReferenceType'=>'STORE_NUMBER','Value'=>$params['Store']));
		}
	if(count($references)){$PackageInfo['CustomerReferences']=$references;}
	$request['RequestedShipment']['RequestedPackageLineItems'] = array('0' =>$PackageInfo);
	//
	$rtn=array();
	$rtn['wsdl']=$path_to_wsdl;
	try{
	    $response = $client->processShipment($request);
	    if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
			$rtn['tracking_number']=(string)$response -> CompletedShipmentDetail -> CompletedPackageDetails -> TrackingIds -> TrackingNumber;
	        $rtn['response']=$response;
	        return $rtn;
	    	}
	    else{
			$rtn['errors']=$response -> Notifications;
			return $rtn;
	    	}
		}
	catch (SoapFault $exception) {
		$rtn['errors']=$exception;
		}
	return $rtn;
	}
//------------------
function fedexTracking($tn='',$params=array()){
	if(strlen($tn)==0){return 'No tracking number specified';}
	$rtn=array('-params'=>$params,'tracking_number'=>$tn,'carrier'=>"FedEx");
	/* Credentials*/
	$credentials=array(
		'Key'			=> $params['Key'],
		'Password'		=> $params['Password']
		);
	$account=array(
		'AccountNumber'	=> $params['AccountNumber'],
		'MeterNumber' 	=> $params['MeterNumber']
		);
	/*Load libraries and wsdls*/
	$progpath=dirname(__FILE__);
	date_default_timezone_set('America/Denver');
	$path_to_wsdl = "{$progpath}/fedex/TrackService_v2.wsdl";
	if(isset($params['-test']) && $params['-test']==1){
		$path_to_wsdl = "{$progpath}/fedex/TrackService_v2_test.wsdl";
    	}
	$cache=isset($params['-cache'])?$params['-cache']:0;
	ini_set("soap.wsdl_cache_enabled", "{$cache}");
	/*Soap Request*/
	$client = new SoapClient($path_to_wsdl, array('trace' => 0)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$request=array();
	$request['WebAuthenticationDetail'] = array('UserCredential' =>$credentials);
	$request['ClientDetail'] = $account;
	$request['TransactionDetail'] = array('CustomerTransactionId' => '*** Track Request v2 using PHP ***');
	$request['Version'] = array('ServiceId' => 'trck', 'Major' => '2', 'Intermediate' => '0', 'Minor' => '0');
	$request['PackageIdentifier'] = array('Value' => $tn, 'Type' => 'TRACKING_NUMBER_OR_DOORTAG');
	$request['IncludeDetailedScans'] = 1;
	try{
	    $response = $client ->track($request);
	    $rtn['raw']=$response;
		if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
			$rtn['trackingNumber']=(string)$response->TrackDetails->TrackingNumber;
			$rtn['method']=(string)$response->TrackDetails->ServiceInfo;
			$rtn['status']=(string)$response->TrackDetails->StatusDescription;
			$rtn['ship_weight']=(string)$response->TrackDetails->ShipmentWeight->Value.' '.(string)$response->TrackDetails->ShipmentWeight->Units;
			$rtn['destination']=array(
				'city'	=> (string)$response->TrackDetails->DestinationAddress->City,
				'state'	=> (string)$response->TrackDetails->DestinationAddress->StateOrProvinceCode,
				'Country'=>(string)$response->TrackDetails->DestinationAddress->CountryCode
				);
			if(isset($response->TrackDetails->ShipTimestamp)){
				//2009-02-03T16:13:00-07:00
				$ddate=(string)$response->TrackDetails->ShipTimestamp;
				$sts=strtotime($ddate);
				$rtn['ship_date']=date("Y-m-d H:i:s",$sts);
				$rtn['ship_date_utime']=$sts;
	        	}
		    if(isset($response->TrackDetails->ActualDeliveryTimestamp)){
				//2009-02-03T16:13:00-07:00
				$ddate=(string)$response->TrackDetails->ActualDeliveryTimestamp;
				$dts=strtotime($ddate);
				$rtn['delivery_date']=date("Y-m-d H:i:s",$dts);
				$rtn['delivery_date_utime']=$dts;
	        	}
	        if(isset($response->TrackDetails->EstimatedDeliveryTimestamp)){
				//2009-02-03T16:13:00-07:00
				$ddate=(string)$response->TrackDetails->EstimatedDeliveryTimestamp;
				$dts=strtotime($ddate);
				$rtn['scheduled_delivery_date']=date("Y-m-d H:i:s",$dts);
				$rtn['scheduled_delivery_date_utime']=$dts;
	        	}
			if(is_array($response->TrackDetails->Events)){
				$rtn['activity']=array();
				$events=$response->TrackDetails->Events;
				$rtn['city']=$events[0]->Address->City;
	            $rtn['state']=$events[0]->Address->StateOrProvinceCode;
	            $events=array_reverse($events);
				foreach($events as $event){
					$history=array();
					$edate=(string)$event->Timestamp;
					$history['date_utime']=strtotime($edate);
					$history['date']=date("D M jS g:i a",$history['date_utime']);
					$history['city']=(string)$event->Address->City;
					$history['state']=(string)$event->Address->StateOrProvinceCode;
					if(isset($event->Address->CountryCode)){
						$history['country']=(string)$event->Address->CountryCode;
						}
					$history['description']=(string)$event->EventDescription;
					$history['status']=$history['description'];
					if(isset($event->StatusExceptionDescription)){
						$history['exception'] =  (string)$event->StatusExceptionDescription ;
						$history['status']=$history['exception'];
						}
					$rtn['activity'][]=$history;
	            	}
	            $rtn['history']=$rtn['activity'];
	        	}
			return $rtn;
	    	}
	    else{
			$rtn['error']=(string)$response->Notifications->Severity;
			$rtn['error'] .=' '.(string)$response->Notifications->Code;
			$rtn['error'] .=' '.(string)$response->Notifications->Message;
			return $rtn;
	    	}
		}
	catch (SoapFault $exception) {
		$rtn['exception']=$exception;
		}
	return $rtn;
	}
//------------------
function fedexTrack($tn='',$params=array()){
	if(strlen($tn)==0){return 'No tracking number specified';}

	$rtn='';
	/* Credentials*/
	$credentials=array(
		'Key'			=> $params['Key'],
		'Password'		=> $params['Password']
		);
	$account=array(
		'AccountNumber'	=> $params['AccountNumber'],
		'MeterNumber' 	=> $params['MeterNumber']
		);
	/*Load libraries and wsdls*/
	$progpath=dirname(__FILE__);
	date_default_timezone_set('America/Denver');
	$path_to_wsdl = "{$progpath}/fedex/TrackService_v2.wsdl";
	if(isset($params['-test']) && $params['-test']==1){
		$path_to_wsdl = "{$progpath}/fedex/TrackService_v2_test.wsdl";
    	}
	$cache=isset($params['-cache'])?$params['-cache']:0;
	ini_set("soap.wsdl_cache_enabled", "{$cache}");
	/*Soap Request*/
	$client = new SoapClient($path_to_wsdl, array('trace' => 0)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$request=array();
	$request['WebAuthenticationDetail'] = array('UserCredential' =>$credentials);
	$request['ClientDetail'] = $account;
	$request['TransactionDetail'] = array('CustomerTransactionId' => '*** Track Request v2 using PHP ***');
	$request['Version'] = array('ServiceId' => 'trck', 'Major' => '2', 'Intermediate' => '0', 'Minor' => '0');
	$request['PackageIdentifier'] = array('Value' => $tn, 'Type' => 'TRACKING_NUMBER_OR_DOORTAG');
	$request['IncludeDetailedScans'] = 1;
	try{
	    $response = $client ->track($request);
		if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
			$trackingNumber=$response->TrackDetails->TrackingNumber;
			$shipMethod=$response->TrackDetails->ServiceInfo;
			$status=$response->TrackDetails->StatusDescription;
			$ship_weight=(string)$response->TrackDetails->ShipmentWeight->Value.' '.(string)$response->TrackDetails->ShipmentWeight->Units;
			$destination=array(
				'city'	=> (string)$response->TrackDetails->DestinationAddress->City,
				'state'	=> (string)$response->TrackDetails->DestinationAddress->StateOrProvinceCode,
				'Country'=>(string)$response->TrackDetails->DestinationAddress->CountryCode
				);
			//debug?
			if(isset($params['debug'])){return printValue($response);}
			//Raw?
			if(isset($params['raw'])){return $response;}
			//RSS?
			if(isset($params['rss'])){
				header('Content-type: text/xml');
				$rtn .= '<rss version="2.0">'."\n";
				$rtn .= '	<channel>'."\n";
			  	$rtn .= '		<title>Fedex Tracking Status</title>'."\n";
			  	$rtn .= '		<link>http://orderfly/fedex_track.phtm</link>'."\n";
			  	$rtn .= '		<description>Fedex Tracking Status</description>'."\n";
			  	$rtn .= '		<item>'."\n";
			    $rtn .= '			<title>Tracking Number Details</title>'."\n";
			    $rtn .= '			<description>Tracking Number Details</description>'."\n";
			    $rtn .= '			<number>'.$trackingNumber.'</number>'."\n";
			    $rtn .= '			<status>'.$status.'</status>'."\n";
			    $rtn .= '			<ship_weight>'.$ship_weight.'</ship_weight>'."\n";
			    $rtn .= '			<method>'.$shipMethod.'</method>'."\n";
			    foreach($destination as $key=>$val){
			    	$rtn .= '			<dest_'.$key.'>'.$destination[$key].'</dest_'.$key.'>'."\n";
					}
			    if(isset($response->TrackDetails->ShipTimestamp)){
					//2009-02-03T16:13:00-07:00
					$ddate=(string)$response->TrackDetails->ShipTimestamp;
					$sts=strtotime($ddate);
					$sqldate=date("Y-m-d H:i:s",$sts);
					$rtn .= '			<ship_date>'.$sqldate.'</ship_date>'."\n";
		        	}
			    if(isset($response->TrackDetails->ActualDeliveryTimestamp)){
					//2009-02-03T16:13:00-07:00
					$ddate=(string)$response->TrackDetails->ActualDeliveryTimestamp;
					$dts=strtotime($ddate);
					$cdate=date("D M jS h:i a",$dts);
					$sqldate=date("Y-m-d H:i:s",$dts);
					list($ddate,$hr,$min,$sec)=split('[T:]',$ddate);
					list($sec,$offset)=split('[-]',$sec);
					$rtn .= '			<delivery_date>'.$sqldate.'</delivery_date>'."\n";
		        	}
			  	$rtn .= '		</item>'."\n";
				$rtn .= '	</channel>'."\n";
				$rtn .= '</rss>'."\n";
				return $rtn;
				}
			//Default to HTML results
			$rtn .= '<table cellspacing="0" class="w_table" cellpadding="2" border="1" style="border:1px solid #6699cc;width:450px;">'."\n";
			$rtn .= '	<tr><td class="w_bold">Tracking Number</td><td colspan="2">'.$trackingNumber.'</td></tr>'."\n";
			$rtn .= '	<tr><td class="w_bold">Ship Method</td><td colspan="2">'.$shipMethod.'</td></tr>'."\n";
			$rtn .= '	<tr><td class="w_bold">Status</td><td colspan="2">'.$status.'</td></tr>'."\n";
			if(isset($response->TrackDetails->PackageCount)){
				$count=(integer)$response->TrackDetails->PackageCount;
				$rtn .= '	<tr><td class="w_bold">Package Count</td><td colspan="2">'.$count.'</td></tr>'."\n";
	        	}
			if(isset($response->TrackDetails->ActualDeliveryTimestamp)){
				//2009-02-03T16:13:00-07:00
				$ddate=(string)$response->TrackDetails->ActualDeliveryTimestamp;
				$dts=strtotime($ddate);
				$cdate=date("D M jS g:i a",$dts);
				$rtn .= '	<tr><td class="w_bold">Delivery Date</td><td colspan="2">'.$cdate.'</td></tr>'."\n";
				if(isset($response->TrackDetails->DeliveryLocationDescription)){
					$val=(string)$response->TrackDetails->DeliveryLocationDescription;
					$rtn .= '	<tr><td class="w_bold">Delivery Location</td><td colspan="2">'.$val.'</td></tr>'."\n";
					}
				if(isset($response->TrackDetails->DeliverySignatureName)){
					$val=(string)$response->TrackDetails->DeliverySignatureName;
					$rtn .= '	<tr><td class="w_bold">Delivery Signature</td><td colspan="2">'.$val.'</td></tr>'."\n";
					}
	        	}
	        else if(isset($response->TrackDetails->EstimatedDeliveryTimestamp)){
				//2009-02-03T16:13:00-07:00
				$ddate=(string)$response->TrackDetails->EstimatedDeliveryTimestamp;
				$dts=strtotime($ddate);
				$cdate=date("D M jS g:i a",$dts);
				$rtn .= '	<tr><td class="w_bold">Estimated Delivery Date</td><td colspan="2">'.$cdate.'</td></tr>'."\n";
	        	}
	        if(isset($response->TrackDetails->DestinationAddress)){
				$city=(string)$response->TrackDetails->DestinationAddress->City;
				$state=(string)$response->TrackDetails->DestinationAddress->StateOrProvinceCode;
				$country=(string)$response->TrackDetails->DestinationAddress->CountryCode;
				$rtn .= '	<tr><td class="w_bold">Destination</td><td colspan="2">'.$city;
				if(strlen($state)){$rtn .=', '.$state;}
				if($country != 'US'){$rtn .= ' <sub>'.$country.'</sub>';}
				$rtn .= '</td></tr>'."\n";
	        	}
	        if(isset($response->TrackDetails->Events)){
				$rtn .= '	<tr><th align="left" colspan="3" class="w_bigger">Fedex Tracking History</th></tr>'."\n";
				$events=array_reverse($response->TrackDetails->Events);
				foreach($events as $event){
					$edate=(string)$event->Timestamp;
					$dts=strtotime($edate);
					$cdate=date("D M jS g:i a",$dts);
					$city=(string)$event->Address->City;
					$state=(string)$event->Address->StateOrProvinceCode;
					$country=(string)$event->Address->CountryCode;
					$desc=$event->EventDescription;
					if(isset($event->StatusExceptionDescription)){$desc .= '<div style="margin-left:15px;>' . $event->StatusExceptionDescription . '</div>';}
					$rtn .= '	<tr valign="top"><td nowrap>'.$cdate.'</td><td>'.$desc.'</td><td nowrap>'.$city;
					if(strlen($state)){$rtn .=', '.$state;}
					if($country != 'US'){$rtn .= ' <sub>'.$country.'</sub>';}
					$rtn .= '</td></tr>'."\n";
	            	}
	        	}
			$rtn .= '</table>'."\n";
			//$rtn .= printValue($response);
	    	}
	    else{
			$msg=(string)$response->Notifications->Severity;
			$msg .=' '.(string)$response->Notifications->Code;
			$msg .=' '.(string)$response->Notifications->Message;
			if(isset($params['rss'])){
				header('Content-type: text/xml');
				$rtn .= '<rss version="2.0">'."\n";
				$rtn .= '	<channel>'."\n";
			  	$rtn .= '		<title>Fedex Tracking Status</title>'."\n";
			  	$rtn .= '		<link>http://orderfly/fedex_track.phtm</link>'."\n";
			  	$rtn .= '		<description>Fedex Tracking Status</description>'."\n";
			  	$rtn .= '		<error>'.$msg.'</error>'."\n";
				$rtn .= '	</channel>'."\n";
				$rtn .= '</rss>'."\n";
				return $rtn;
				}
	        $rtn .= $msg;
	    	}
		}
	catch (SoapFault $exception) {
		$rtn .= printValue($exception);
		}
	return $rtn;
	}
//------------------
function fedexServices($params=array()){
	/* Required Params:
		Key, Password, AccountNumber, MeterNumber
		Shipper_CountryCode, Shipper_PostalCode
		Recipient_CountryCode, Recipient_PostalCode
		Weight
	   Returned Values:
	    $rtn['rates'] - array of available rates and prices
	    $rtn['-request'] - request sent to fedex
	    $rtn['-response'] - result from fedex
	    $rtn['-params'] - params passed in as an array
	    $rtn['-error'] - errors
	*/
	$rtn=array('-params'=>$params);
	/* Credentials*/
	$credentials=array(
		'Key'			=> $params['Key'],
		'Password'		=> $params['Password']
		);
	$account=array(
		'AccountNumber'	=> $params['AccountNumber'],
		'MeterNumber' 	=> $params['MeterNumber']
		);
	if(!isNum($params['PackageCount'])){$params['PackageCount']=1;}
	global $progpath;
	/*Load libraries and wsdls*/
	$path_to_wsdl = "{$progpath}/fedex/RateService_v7.wsdl";
	if(isset($params['-test']) && $params['-test']==1){
		$path_to_wsdl = "{$progpath}/fedex/RateService_v7_test.wsdl";
    	}
	$cache=isset($params['-cache'])?$params['-cache']:0;
	ini_set("soap.wsdl_cache_enabled", "{$cache}");
	/*Soap Request*/
	$client = new SoapClient($path_to_wsdl, array('trace' => 0)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$request=array();
	$request['WebAuthenticationDetail'] = array('UserCredential' =>$credentials);
	$request['ClientDetail'] = $account;
	$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Available Services Request v7 using PHP ***');
	$request['Version'] = array('ServiceId' => 'crs', 'Major' => '7', 'Intermediate' => '0', Minor => '0');
	$request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
	$request['RequestedShipment']['ShipTimestamp'] = date('c');
	// Service Type and Packaging Type are not passed in the request
	//Shipper Address
	$shipper_address=array();
	if(!isset($params['Shipper_CountryCode'])){$params['Shipper_CountryCode']='US';}
	$fields=array('StreetLines','City','StateOrProvinceCode','PostalCode','CountryCode','Residential');
	foreach($fields as $field){
		$sfield='Shipper_' . $field;
		if(isset($params[$sfield])){$shipper_address[$field]=$params[$sfield];}
    	}
	$request['RequestedShipment']['Shipper'] =	array('Address' => $shipper_address);
	//Recipeint
	$recipient_address=array();
	if(!isset($params['Recipient_CountryCode'])){$params['Recipient_CountryCode']='US';}
	if($params['Residential']){$params['Recipient_Residential']=true;}
	foreach($fields as $field){
		$sfield='Recipient_' . $field;
		if(isset($params[$sfield])){$recipient_address[$field]=$params[$sfield];}
    	}
	$request['RequestedShipment']['Recipient']=	array('Address' => $recipient_address);
	//other
	$request['RequestedShipment']['ShippingChargesPayment']=array('PaymentType' => 'SENDER','Payor' => array('AccountNumber' => $account['AccountNumber'],'CountryCode' => 'US'));
	$request['RequestedShipment']['RateRequestTypes'] = isset($params['RateRequestTypes'])?$params['RateRequestTypes']:'LIST';
	$request['RequestedShipment']['PackageCount'] = $params['PackageCount'];
	$request['RequestedShipment']['PackageDetail'] = isset($params['PackageDetail'])?$params['PackageDetail']:'INDIVIDUAL_PACKAGES';
	//Package Information
	$requestedPackages=array();
	for($p=0;$p<$params['PackageCount'];$p++){
		$n=$p+1;
		$package=array('SequenceNumber' => $n);
		$weight=ceil($params['Weight']/$params['PackageCount']);
		$package['Weight']=array('Value'=>$weight,'Units'=>'LB');
		array_push($requestedPackages,$package);
		}
	$request['RequestedShipment']['RequestedPackageLineItems'] = $requestedPackages;
	$rtn['-request']=$request;
	try{
	    $response = $client ->getRates($request);
	    $rtn['-response']=$response;
		if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
			//remove Notifications so it does not mess up the object on a Warning.
			unset($response->Notifications);
			//return $response;
			$rates=array();
			foreach($response->RateReplyDetails as $reply){
				$servicetype=(string)$reply->ServiceType;
				$cost=(real)$reply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount;
				if(isset($params['Handling'])){$cost=round(($cost+$params['Handling']),2);}
				$rates[$servicetype]=$cost;
				if(!isset($rtn['zone'])){
					$rtn['zone']=(integer)$reply->RatedShipmentDetails[0]->ShipmentRateDetail->RateZone;
                	}
            	}
            asort($rates);
            $rtn['rates']=$rates;
			return $rtn;
	    	}
	    else{
	        $rtn['-error'] = $response->Notifications->Message;
	    	}
		}
	catch (SoapFault $exception) {
		$rtn[-error] = printValue($exception);
		}
	return $rtn;
	}
?>