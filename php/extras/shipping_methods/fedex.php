<?php
$progpath=dirname(__FILE__);
/* 
	load the fedex common library
	Technical Support hotline phone: 1.877.339.2774 (When prompted, please say "Web Services")
*/
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
	if(strlen($tn)==0){return 'fedexTracking error - No tracking number specified';}
	$progpath=dirname(__FILE__);
	$path_to_wsdl = "{$progpath}/fedex/TrackService_v18.wsdl";
	ini_set("soap.wsdl_cache_enabled", "0");
	$opts = array(
		'ssl' => array('verify_peer' => false, 'verify_peer_name' => false)
	);
	$client = new SoapClient($path_to_wsdl, array('trace' => 1,'stream_context' => stream_context_create($opts)));  // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$params=array_change_key_case($params,CASE_LOWER);
	if(!isset($params['key'])){return 'fedexTracking error - No key specified';}
	if(!isset($params['password'])){return 'fedexTracking error - No password specified';}
	if(!isset($params['accountnumber'])){return 'fedexTracking error - No accountnumber specified';}
	if(!isset($params['meternumber'])){return 'fedexTracking error - No meternumber specified';}
	if(!isset($params['parentkey'])){$params['parentkey']=$params['key'];}
	if(!isset($params['parentpassword'])){$params['parentpassword']=$params['password'];}
	$rtn=array('-params'=>$params,'tracking_number'=>$tn,'carrier'=>"FedEx");
	$request=array();
	$request['WebAuthenticationDetail'] = array(
		'ParentCredential' => array(
			'Key' => $params['parentkey'], 
			'Password' => $params['parentpassword']
		),
		'UserCredential' => array(
			'Key' => $params['key'], 
			'Password' => $params['password']
		)
	);

	$request['ClientDetail'] = array(
		'AccountNumber' => $params['accountnumber'], 
		'MeterNumber' => $params['meternumber']
	);
	$request['TransactionDetail'] = array('CustomerTransactionId' => '*** Track Request using PHP ***');
	$request['Version'] = array(
		'ServiceId' => 'trck', 
		'Major' => '18', 
		'Intermediate' => '0', 
		'Minor' => '0'
	);
	$request['SelectionDetails'] = array(
		'PackageIdentifier' => array(
			'Type' => 'TRACKING_NUMBER_OR_DOORTAG',
			'Value' => $tn
		)
	);
	try {
		if(setEndpoint('changeEndpoint')){
			$newLocation = $client->__setLocation(setEndpoint('endpoint'));
		}
		$response = $client ->track($request);
		//print_r($response);exit;
	    if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
			if($response->HighestSeverity != 'SUCCESS'){
				$rtn['error']=(string)$response->Notifications->Severity;
				$rtn['error'] .=' '.(string)$response->Notifications->Code;
				$rtn['error'] .=' '.(string)$response->Notifications->Message;
				return $rtn;
			}else{
		    	if ($response->CompletedTrackDetails->HighestSeverity != 'SUCCESS'){
					$rtn['error']=(string)$response->Notifications->Severity;
					$rtn['error'] .=' '.(string)$response->Notifications->Code;
					$rtn['error'] .=' '.(string)$response->Notifications->Message;
					return $rtn;
				}else{
					$details=$response->CompletedTrackDetails->TrackDetails;
					//success
					$rtn['trackingNumber']=(string)$details->TrackingNumber;
					$rtn['method']=(string)$details->Service->Type;
					$rtn['status']=(string)$details->StatusDetail->Description;
					$rtn['ship_weight']=(string)$details->ShipmentWeight->Value.' '.(string)$details->ShipmentWeight->Units;
					$rtn['destination']=array(
						'city'	=> (string)$details->DestinationAddress->City,
						'state'	=> (string)$details->DestinationAddress->StateOrProvinceCode,
						'Country'=>(string)$details->DestinationAddress->CountryCode
						);
					if(isset($details->DatesOrTimes)){
						foreach($details->DatesOrTimes as $dt){
							//echo printValue($dt);
							$dts=strtotime($dt->DateOrTimestamp);
							switch($dt->Type){	
								case 'ACTUAL_PICKUP':
									$rtn['pickup_date']=date("Y-m-d H:i:s",$dts);
									$rtn['pickup_date_utime']=$dts;
								break;
								case 'SHIP':
									$rtn['ship_date']=date("Y-m-d H:i:s",$dts);
									$rtn['ship_date_utime']=$dts;
								break;
								case 'ESTIMATED_DELIVERY':
									$rtn['scheduled_delivery_date']=date("Y-m-d H:i:s",$dts);
									$rtn['scheduled_delivery_date_utime']=$dts;
								break;
								case 'ACTUAL_DELIVERY':
									$rtn['delivery_date']=date("Y-m-d H:i:s",$dts);
									$rtn['delivery_date_utime']=$dts;
								break;
								
							}
						}
					}
					if(isset($rtn['ship_date_utime']) && isset($rtn['delivery_date_utime'])){
						$rtn['delivery_elapsed_time']=$rtn['delivery_date_utime']-$rtn['ship_date_utime'];
						$rtn['delivery_elapsed_time_ex']=verboseTime($rtn['delivery_elapsed_time']);
					}
					if(is_array($details->Events)){
						$rtn['activity']=array();
						$events=$details->Events;
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
			        //echo printValue($rtn).printValue($details);exit;
				}
			}
	    }
	    else{
	        $rtn['error']=$response;
	    }   
	} catch (SoapFault $exception) {
	    $rtn['exception']=$exception;
	}
	return $rtn;
	}
//------------------
function setEndpoint($var){
	if($var == 'changeEndpoint') Return true;
	if($var == 'endpoint') Return 'https://ws.fedex.com:443/web-services';
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