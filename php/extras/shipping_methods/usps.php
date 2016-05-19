<?php
/*
	References:
		http://www.usps.com/webtools/htm/Address-Information.htm#_Toc131231416
		http://www.marksanborn.net/php/calculating-usps-shipping-rates-with-php/
		United States Postal Service Web Tools: 
			Main site: 	http://www.usps.com/webtools/
			Tracking: 	http://www.usps.com/webtools/htm/Track-Confirm.htm
			Address:	http://www.usps.com/webtools/htm/Address-Information.htm



*/
function uspsServices($params=array()){
	if(!isset($params['-userid'])){return "No userid";}
	if(!isset($params['-weight'])){return "No weight";}
	$api='RateV3';
	$request='RateV3Request';
	if(isset($params['-intl']) && $params['-intl']){
		//International rate request
		if(!isset($params['-country'])){return "No country. Required for intl requests";}
		$api='IntlRate';
		$request='IntlRateRequest';
		}
	else{
		if(!isset($params['-service'])){$params['-service']='ALL';}
		if(!isset($params['-zip_orig'])){return "No zip_orig";}
		if(!isset($params['-zip_dest'])){return "No zip_dest";}
    }
	$weight_lbs=floor($params['-weight']/16);
	$weight_oz=$params['-weight']-($weight_lbs*16);
	$xml = '<'.$request.' USERID="'.$params['-userid'].'">';
	$xml .= 	'<Package ID="1ST">';
	$xml .= 		'<Pounds>'.$weight_lbs.'</Pounds>';
	$xml .= 		'<Ounces>'.$weight_oz.'</Ounces>';
	$xml .= 		'<MailType>Package</MailType>';
	if($request=='RateV3Request'){
		$xml .= 		'<Service>'.$params['-service'].'</Service>';
		$xml .= 		'<ZipOrigination>'.$params['-zip_orig'].'</ZipOrigination>';
		$xml .= 		'<ZipDestination>'.$params['-zip_dest'].'</ZipDestination>';
		$xml .= 		'<Size>REGULAR</Size>';
		$xml .= 		'<Machinable>false</Machinable>';
		}
	if(isset($params['-country'])){
		$xml .= 		'<Country>'.$params['-country'].'</Country>';
		}
	$xml .= 	'</Package>';
	$xml .= '</'.$request.'>';
	$opts = array(
		'API'=>$api,
		'XML'=>$xml,
		'-ssl'=>false
		);
	//Note:they provide you a secure url but this is only used with Label printing
	$urls=array(
		'test'	=> 'http://testing.shippingapis.com/ShippingAPITest.dll',
		'live'	=> 'http://Production.ShippingAPIs.com/ShippingAPI.dll',
		);
	$rtn=array(
		'params'=>$params,
		'xml_out'	=> $xml
		);
	if(isset($params['-test']) && $params['-test']){
		//pass to the test server - Note: their test services are usually broken
		$result=postURL($urls['test'],$opts);
		}
	else{$result=postURL($urls['live'],$opts);}

	if(preg_match('/^\<\?xml version=\"1\.0\"\?\>/i',$result['body'])){
		$xml=new SimpleXmlElement($result['body']);
		//Check for errors
		if($xml->Package->Error->Description){
			$rtn['error']=(string)$xml->Package->Error->Description;
        	}
		$rates=array();
		if($request=='IntlRateRequest'){
			//International
			foreach($xml->Package->Service as $rate){
				//return $rate;
				$servicetype=(string)$rate->SvcDescription;
				$cost=(real)$rate->Postage;
				$rates[$servicetype]=$cost;
	        	}
	        $rtn['Prohibitions']=(string)$xml->Package->Prohibitions;
        	}
        else{
			foreach($xml->Package->Postage as $rate){
				//return $rate;
				$servicetype=(string)$rate->MailService;
				$cost=(real)$rate->Rate;
				$rates[$servicetype]=$cost;
	        	}
			}
        if(count($rates)>0){
			asort($rates);
			$rtn['rates']=$rates;
        	}
		}
	if(isset($xml)){$rtn['xml']=$xml;}
	$rtn['result']=$result;
	return $rtn;
	}
//------------------
function uspsExpressMailLabel($params=array()){
	//Tracking information for USPS shipments
	//Sample Request: <TrackRequest USERID="xxxxxxxx"><TrackID ID="EJ958088694US"></TrackID></TrackRequest>
	if(!isset($params['-userid'])){return "No userid";}
	if(!isset($params['-tn'])){return "No tn";}
	$xml=xmlHeader(array('version'=>"1.0",'encoding'=>"UTF-8"));
	$xml .= '<ExpressMailLabelCertifyRequest  USERID="'.$params['-userid'].'" PASSWORD="'.$params['-password'].'">'."\n";
	$xml .= '	<Option />'."\n";
	$xml .= '	<Revision>2</Revision>'."\n";
	$xml .= '	<EMCAAccount />'."\n";
	$xml .= '	<EMCAPassword />'."\n";
	$xml .= '	<ImageParameters />'."\n";
	//FromFirstName - max length=26
	$xml .= '	<FromFirstName>'.$params['shipfromfirstname'].'</FromFirstName>'."\n";
	//FromLastName - max length=26
	$xml .= '	<FromLastName>'.$params['shipfromlastname'].'</FromLastName>'."\n";
	//FromFirm - max length=26
	$xml .= '	<FromFirm>'.$params['shipfromcompany'].'</FromFirm>'."\n";
	//FromAddress1 - max length=26
	$xml .= '	<FromAddress1>'.$params['shipfromaddress1'].'</FromAddress1>'."\n";
	//FromAddress2 - max length=26
	$xml .= '	<FromAddress2>'.$params['shipfromaddress2'].'</FromAddress2>'."\n";
	//FromCity - max length=13
	$xml .= '	<FromCity>'.$params['shipfromcity'].'</FromCity>'."\n";
	//FromState - max length=2
	$xml .= '	<FromState>'.$params['shipfromstate'].'</FromState>'."\n";
	//FromZip5 - 5 digits
	$xml .= '	<FromZip5>'.$params['shipfromzipcode'].'</FromZip5>'."\n";
	$xml .= '	<FromZip4 />'."\n";
	//FromPhone - max length=26
	$xml .= '	<FromPhone>'.$params['shipfromtelephone'].'</FromPhone>'."\n";
	//ToFirstName - max length=26
	$xml .= '	<ToFirstName>'.$params['shiptofirstname'].'</ToFirstName>'."\n";
	//ToLastName - max length=26
	$xml .= '	<ToLastName>'.$params['shiptolastname'].'</ToLastName>'."\n";
	//ToFirm - max length=26
	$xml .= '	<ToFirm>'.$params['shiptocompany'].'</ToFirm>'."\n";
	//ToAddress1 - max length=26
	$xml .= '	<ToAddress1>'.$params['shiptoaddress1'].'</ToAddress1>'."\n";
	//ToAddress2 - max length=26
	$xml .= '	<ToAddress2>'.$params['shiptoaddress2'].'</ToAddress2>'."\n";
	//ToCity - max length=13
	$xml .= '	<ToCity>'.$params['shiptocity'].'</ToCity>'."\n";
	//ToState - max length=2
	$xml .= '	<ToState>'.$params['shiptostate'].'</ToState>'."\n";
	//ToZip5 - 5 digit zip
	$xml .= '	<ToZip5>'.$params['shiptozipcode'].'</ToZip5>'."\n";
	$xml .= '	<ToZip4 />'."\n";
	//ToPhone - 10 digits with no spaces or hyphens
	$xml .= '	<ToPhone>'.$params['shiptotelephone'].'</ToPhone>'."\n";
	//WeightInOunces - Items must weigh 70 pounds or less
	$xml .= '	<WeightInOunces>'.$params['weight'].'</WeightInOunces>'."\n";
	$xml .= '	<FlatRate />'."\n";
	$xml .= '	<SundayHolidayDelivery />'."\n";
	$xml .= '	<StandardizeAddress />'."\n";
	$xml .= '	<WaiverOfSignature />'."\n";
	$xml .= '	<NoHoliday />'."\n";
	$xml .= '	<NoWeekend />'."\n";
	$xml .= '	<SeparateReceiptPage/>'."\n";
	$xml .= '	<POZipCode>'.$params['shipfromzipcode'].'</POZipCode>'."\n";
	//FacilityType - DDU,SCF,BMC,ADC,ASF
	$xml .= '	<FacilityType>DDU</FacilityType>'."\n";
	//ImageType - PDF,GIF,NONE
	$xml .= '	<ImageType>PDF</ImageType>'."\n";
	//LabelDate - dd-mmm-yyyy or mm/dd/yyyy
	$xml .= '	<LabelDate>'.$params['shipdate'].'</LabelDate>'."\n";
	$xml .= '	<CustomerRefNo>'.$params['ordernumber'].'</CustomerRefNo>'."\n";
	$xml .= '	<SenderName>'.$params['sendername'].'</SenderName>'."\n";
	$xml .= '	<SenderEMail>'.$params['senderemail'].'</SenderEMail>'."\n";
	$xml .= '	<RecipientName>'."{$params['shiptofirstname']} {$params['shiptolastname']}".'</RecipientName>'."\n";
	$xml .= '	<RecipientEMail>'.$params['shiptoemail'].'</RecipientEMail>'."\n";
	$xml .= '	<HoldForManifest />'."\n";
	$xml .= '	<CommercialPrice>false</CommercialPrice>'."\n";
	$xml .= '	<InsuredAmount>'.$params['insured_amount'].'</InsuredAmount>'."\n";
	//Container - VARIABLE,RECTANGULAR,NONRECTANGULAR,FLAT RATE ENVELOPE,LEGAL FLAT RATE ENVELOPE,PADDED FLAT RATE ENVELOPE,FLAT RATE BOX
	$xml .= '	<Container>'.$params['container'].'</Container>'."\n";
	//Size - LARGE,REGULAR - REGULAR if all package dimensions are under 12 inches
	$xml .= '	<Size>'.$params['size'].'</Size>'."\n";
	//Width - in inches
	$xml .= '	<Width>'.$params['width'].'</Width>'."\n";
	//Length - in inches
	$xml .= '	<Length>'.$params['length'].'</Length>'."\n";
	//Height - in inches
	$xml .= '	<Height>'.$params['height'].'</Height>'."\n";
	//Girth is only required when Container = ‘NONRECTANGULAR’ and Size=’LARGE’.
	$xml .= '	<Girth>'.$params['girth'].'</Girth>'."\n";
	$xml .= '</ExpressMailLabelCertifyRequest>'."\n";
	//Note:they provide you a secure url but this is only used with Label printing
	$urls=array(
		'test'	=> 'http://production.shippingapis.com/ShippingAPITest.dll',
		'live'	=> 'http://Production.ShippingAPIs.com/ShippingAPI.dll',
		);
	$rtn=array(
		'params'		=>$params,
		'carrier'		=> "USPS",
		'method'		=> "N/A",
		'xml_out'		=> $xml,
		'tracking_number'=>$params['-tn']
		);
	$opts = array(
		'API'=>'ExpressMailLabel',
		'XML'=>$xml,
		'-ssl'=>false
		);
	if(isset($params['-test']) && $params['-test']){
		//pass to the test server - Note: their test services are usually broken
		$result=postURL($urls['test'],$opts);
	}
	else{$result=postURL($urls['live'],$opts);}
	$result['body']=trim($result['body']);
	$result['array']=xml2Array($result['body']);
	return $result;
}
//------------------
function uspsTrack($params=array()){
	//Tracking information for USPS shipments
	//Sample Request: <TrackRequest USERID="xxxxxxxx"><TrackID ID="EJ958088694US"></TrackID></TrackRequest>
	if(!isset($params['-userid'])){return "No userid";}
	if(!isset($params['-tn'])){return "No tn";}
	$api='TrackV2';
	$xml = '<TrackRequest USERID="'.$params['-userid'].'">';
	$xml .= 	'<TrackID ID="'.$params['-tn'].'">';
	$xml .= 	'</TrackID>';
	$xml .= '</TrackRequest>';
	$opts = array(
		'API'=>$api,
		'XML'=>$xml,
		'-ssl'=>false
		);
	//Note:they provide you a secure url but this is only used with Label printing
	$urls=array(
		'test'	=> 'http://production.shippingapis.com/ShippingAPITest.dll',
		'live'	=> 'http://Production.ShippingAPIs.com/ShippingAPI.dll',
		);
	$rtn=array(
		'params'		=>$params,
		'carrier'		=> "USPS",
		'method'		=> "N/A",
		'xml_out'		=> $xml,
		'tracking_number'=>$params['-tn']
		);
	if(isset($params['-test']) && $params['-test']){
		//pass to the test server - Note: their test services are usually broken
		$result=postURL($urls['test'],$opts);
		}
	else{$result=postURL($urls['live'],$opts);}
	$result['body']=trim($result['body']);
	if(preg_match('/\<TrackResponse\>/i',$result['body'])){
		$rtn['status']="In Transit";
		$xml=new SimpleXmlElement($result['body']);
		//Check for errors
		if($xml->Package->Error->Description){
			$rtn['error']=(string)$xml->Package->Error->Description;
			$rtn['status']="Error";
        	}
        if(isset($xml->TrackInfo->TrackSummary)){
			$rtn['summary']=(string)$xml->TrackInfo->TrackSummary;
			if(preg_match('/was delivered/i',$rtn['summary'])){$rtn['status']="Delivered";}
			else if(preg_match('/is out for delivery/i',$rtn['summary'])){$rtn['status']="Out for Delivery";}
			else if(preg_match('/Your item arrived/i',$rtn['summary'])){$rtn['status']="Arrived";}
			if(preg_match('/is no record of/i',$rtn['summary'])){
				$rtn['error']=$rtn['summary'];
				$rtn['status']="Error";
				}
        	}
        if(is_array($xml->TrackInfo->TrackDetail)){
			$detail=array();
			foreach($xml->TrackInfo->TrackDetail as $track){
				$detail[]=(string)$track;
				}
			$rtn['detail']=$detail;
			}
		else{$rtn['detail'][]=(string)$xml->TrackInfo->TrackDetail;}
		if(isset($rtn['summary'])){
			//add the first line of the summary as a detail item
			list($detail,$summary)=preg_split('/\./',$rtn['summary'],2);
			$rtn['detail'][]=$detail;
			$rtn['summary']=$summary;
			}
		}
	if(isset($xml)){$rtn['xml']=$xml;}
	$rtn['result']=$result;
	return $rtn;
	}
//------------------
function uspsZipCodeInfo($zip='',$params=array()){
	if(strlen($zip)==0){return 'No zip code';}
	/*
	http://SERVERNAME/ShippingAPITest.dll?API=CityStateLookup&XML= <CityStateLookupRequest%20USERID="xxxxxxx"><ZipCode ID= "0">
	<Zip5>90210</Zip5></ZipCode></CityStateLookupRequest>
	*/
	$rtn=array();
	$urls=array(
		'test_standard'	=> "http://testing.shippingapis.com/ShippingAPITest.dll",
		'test_secure'	=> "https://secure.shippingapis.com/ShippingAPITest.dll"
		);
	$api="CityStateLookup";
	$xml= '<CityStateLookupRequest%20USERID="'.$params['userid'].'"%20PASSWORD="'.$params['password'].'">';
	$xml .= '<ZipCode%20ID="0"><Zip5>'.$zip.'</Zip5></ZipCode>';
	$xml .= '</CityStateLookupRequest>';
	$rtn['data_out']="API=" . $api . "&XML=" . $xml;
	$process = curl_init($urls['test_standard']);
	curl_setopt($process, CURLOPT_POST, 1);
	curl_setopt($process, CURLOPT_HEADER, 1);
	curl_setopt($process, CURLOPT_POSTFIELDS, $rtn['data_out']);
	curl_setopt($process, CURLOPT_TIMEOUT, 60);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($process, CURLINFO_HEADER_OUT, true);
	curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
	$return = curl_exec($process);
	$rtn['header_out']=curl_getinfo($process,CURLINFO_HEADER_OUT);
	//check for errors
	if ( curl_errno($process) ) {
		$rtn['err'] = curl_errno($process);
		$rtn['errno'] = curl_error($process);
		}
	else{
		//break it up into header and body
		$parts=preg_split('/\r\n\r\n/',trim($return),2);
		$rtn['header']=$parts[0];
		$rtn['body']=$parts[1];
		$rtn['xml']=readXML($rtn['body']);
    	}
    //close the handle
	curl_close($process);

	return $rtn;
	}
//------------------
function uspsVerifyAddress($params=array()){
	/*
	Reference: https://www.usps.com/webtools/_pdf/Address-Information-v3-1a.pdf
	Example Request:
	http://SERVERNAME/ShippingAPITest.dll?API=Verify&XML=<AddressValidateRequest%20USERID="xxxxxxx"><Address ID="0"><Address1></Address1>
	<Address2>6406 Ivy Lane</Address2><City>Greenbelt</City><State>MD</State>
	<Zip5></Zip5><Zip4></Zip4></Address></AddressValidateRequest>
	*/
	$rtn=array();
	$urls=array(
		'test_standard'	=> "http://testing.shippingapis.com/ShippingAPITest.dll",
		'test_secure'	=> "https://secure.shippingapis.com/ShippingAPITest.dll",
		'live'	=> 'http://Production.ShippingAPIs.com/ShippingAPI.dll'
		);
	$api="Verify";
	$xml= '<AddressValidateRequest%20USERID="'.$params['-userid'].'"%20PASSWORD="'.$params['-password'].'">';
	$rtn['address']=array();
	unset($address);
	//$rtn['address_in']=$params['address'];
	foreach($params['address'] as $id=>$address){
		$xml .= '<Address ID="'.$id.'">';
		$rtn['address'][$id]['in']=$address;
		foreach($address as $key=>$val){
	    	if(!strlen($val)){
	        	$xml .= "<{$key} />";
	        	continue;
			}
	    	$val=utf8_encode($val);
			$val=xmlEncodeCDATA($val);
	    	$xml .= "<{$key}>{$val}</{$key}>";
		}
		$xml .= '</Address>';
	}
	$xml .= '</AddressValidateRequest>';
	//$rtn['xml']=$xml;
	$datastream="API=" . $api . "&XML=" . $xml;
	if(isset($params['-test']) && $params['-test']){$url=$urls['test_standard'];}
	else{$url=$urls['live'];}
	$process = curl_init($url);
	curl_setopt($process, CURLOPT_POST, 1);
	curl_setopt($process, CURLOPT_HEADER, 1);
	curl_setopt($process, CURLOPT_POSTFIELDS, $datastream);
	curl_setopt($process, CURLOPT_TIMEOUT, 60);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($process, CURLINFO_HEADER_OUT, true);
	curl_setopt($process, CURLOPT_SSL_VERIFYPEER, FALSE);
	$return = curl_exec($process);
	//$rtn['header_out']=curl_getinfo($process,CURLINFO_HEADER_OUT);
	$rtn['raw']=$return;
	//check for errors
	if ( curl_errno($process) ) {
		$rtn['err'] = curl_errno($process);
		$rtn['errno'] = curl_error($process);
		}
	else{
		//break it up into header and body
		$parts=preg_split('/\r\n\r\n/',trim($return),2);
		//$rtn['header']=$parts[0];
		$rtn['body']=$parts[1];
		$rtn['xml_array']=xml2Array($parts[1]);
    	}
    //close the handle
	curl_close($process);
    $addresses=array();
    if(isset($rtn['xml_array']['AddressValidateResponse']['Address']['Error']['Description'])){
    	$rtn['address'][0]['out']['err']=$rtn['xml_array']['AddressValidateResponse']['Address']['Error']['Description'];
    	$rtn['address'][0]['out']['errno']=$rtn['xml_array']['AddressValidateResponse']['Address']['Error']['Number'];
    	$rtn['attn']=1;
	}
	elseif(isset($rtn['xml_array']['AddressValidateResponse']['Address']['City'])){
    	$rtn['address'][0]['out']=$rtn['xml_array']['AddressValidateResponse']['Address'];
	}
	elseif(isset($rtn['xml_array']['AddressValidateResponse']['Address'][0])){
		unset($address);
		foreach($rtn['xml_array']['AddressValidateResponse']['Address'] as $id=>$address){
        	if(isset($address['City'])){
            	$rtn['address'][$id]['out']=$address;
			}
			elseif(isset($address['Error']['Description'])){
            	$rtn['address'][$id]['out']['err']=$address['Error']['Description'];
            	$rtn['address'][$id]['out']['errno']=$address['Error']['Number'];
			}
		}
	}
	//Compare
	foreach($rtn['address'] as $id=>$rec){
    	foreach($rec['in'] as $key=>$val){
			if(strtolower($key)=='zip4'){continue;}
        	if(isset($rec['out'][$key]) && encodeCRC(strtoupper($val)) != encodeCRC(strtoupper($rec['out'][$key]))){
            	$rtn['address'][$id]['diff'][]=$key;
            	$rtn['attn']=1;
			}
		}
	}
	unset($rtn['xml_array']);
	return $rtn;
	}
?>