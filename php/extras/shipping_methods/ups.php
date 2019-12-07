<?php
$progpath=dirname(__FILE__);
/*
	References:
	http://www.marksanborn.net/php/calculating-ups-shipping-rate-with-php/
	service_code: Valid UPS Service Selection Codes
		01 – UPS Next Day Air
	    02 – UPS Second Day Air
	    03 – UPS Ground (default)
	    07 – UPS Worldwide Express
	    08 – UPS Worldwide Expedited
	    11 – UPS Standard
	    12 – UPS Three-Day Select
	    13 - Next Day Air Saver
	    14 – UPS Next Day Air Early AM
	    54 – UPS Worldwide Express Plus
	    59 – UPS Second Day Air AM
	    65 – UPS Saver
    pickup_type: Valid Pickup Types:
		01 - Daily Pickup (Default)
		03 - Customer Counter
		06 - One Time Pickup
		07 - On Call Air
		11 - Authorized Shipping Outlet
		19 - Letter Center
		20 - Air Service Center
	package_type: Valid Package Type Codes:
		00 - Unknown
		01 - Ups Letter
		02 - Your Packaging (Default)
		03 - UPS Tube
		04 - UPS Pak
		21 - UPS Express Box
	request_option: Valid RequestOptions
		Rate
		Shop - returns rates for all valid UPS products (default)
	Breakdownd of a UPS Shipping Number
		The first two characters must be "1Z".
		The next 6 characters we fill with our UPS account number "XXXXXX"
		The next 2 characters denote the service_code:
		The next 5 characters is our invoice number
		The next 2 digits is the package number, zero filled e.g. Package 1 is "01", 2 is "02"
		The last and final character is the check digit.

Test Tracking Numbers:
	TRACKING NUMBER, SERVICE, RESPONSE
	1Z12345E0205271688 (Signature Availability), 2nd Day Air, Delivered
	1Z12345E6605272234, World Wide Express, Delivered
	1Z12345E0305271640, (Second Package: 1Z12345E0393657226), Ground, Delivered
	1Z12345E1305277940, Next Day Air Saver, ORIGIN SCAN
	1Z12345E6205277936, Day Air Saver, 2nd Delivery attempt
	1Z12345E020527079, Invalid Tracking Number
	1Z12345E1505270452, No Tracking Information Available
	990728071, UPS Freight LTL, In Transit
	3251026119, Delivered Origin CFS
	MI Tracking Number: 9102084383041101186729
	MI Reference Number: cgish000116630
	1Z648616E192760718, UPS Worldwide Express Freight, Order Process by UPS
	5548789114, UPS Express Freight, Response for UPS Air Freight
	ER751105042015062, UPS Ocean, Response for UPS Ocean Freight
	1ZWX0692YP40636269, UPS SUREPOST, Response for UPS SUREPOST
*/
//-----------------------
function upsAddressValidate($params=array()){
	if(!isset($params['-userid'])){return "No userid";}
	if(!isset($params['-accesskey'])){return "No accesskey";}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-account'])){return "No account";}
	//defaults
	if(!isset($params['country'])){$params['country']='US';}
	$request=<<<ENDOFREQUEST
<?xml version="1.0" ?>
	<AccessRequest xml:lang='en-US'>
		<AccessLicenseNumber>{$params['-accesskey']}</AccessLicenseNumber>
		<UserId>{$params['-userid']}</UserId>
		<Password>{$params['-password']}</Password>
	</AccessRequest>
<?xml version="1.0" ?>
	<AddressValidationRequest xml:lang='en-US'>
		<Request>
			<TransactionReference>
				<CustomerContext>Your Customer Context</CustomerContext>
				<XpciVersion>1.0</XpciVersion>
			</TransactionReference>
			<RequestAction>XAV</RequestAction>
			<RequestOption>1</RequestOption>
		</Request>
		<AddressKeyFormat>
			<AddressLine>{$params['address']}</AddressLine>
			<Region>{$params['city']} {$params['state']} {$params['zip']}</Region>
			<PoliticalDivision2>{$params['city']}</PoliticalDivision2>
			<PoliticalDivision1>{$params['state']}</PoliticalDivision1>
			<PostcodePrimaryLow>{$params['zip']}</PostcodePrimaryLow>
			<CountryCode>{$params['country']}</CountryCode
		</AddressKeyFormat>
	</AddressValidationRequest>
ENDOFREQUEST;
	$url="https://www.ups.com/ups.app/xml/XAV";
    $result=postXML($url,$xml_out,array('-ssl'=>false));
    echo printValue($result);exit;
}
function upsServices($params=array()){
	if(!isset($params['-userid'])){return "No userid";}
	if(!isset($params['-accesskey'])){return "No accesskey";}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-account'])){return "No account";}
	if(!isset($params['-shipfrom_zip'])){return "No shipfrom_zip";}
	if(!isset($params['-shipto_zip'])){return "No shipto_zip";}
	if(!isset($params['-weight'])){return "No weight";}
	//Set Defaults
	if(!isset($params['shipfrom_country'])){$params['shipfrom_country']='US';}
	if(!isset($params['shipto_country'])){$params['shipto_country']='US';}
	if(!isset($params['pickup_type'])){$params['pickup_type']='01';}
	if(!isset($params['package_type'])){$params['package_type']='02';}
	if(!isset($params['service_code'])){$params['service_code']='03';}
	if(!isset($params['request_option'])){$params['request_option']='Shop';}
	$rtn=array('-params'=>$params);
	$lookup=array(
		'01' => 'UPS Next Day Air',
	    '02' => 'UPS Second Day Air',
	    '03' => 'UPS Ground',
	    '07' => 'UPS Worldwide Express',
	    '08' => 'UPS Worldwide Expedited',
	    '11' => 'UPS Standard',
	    '12' => 'UPS Three-Day Select',
	    '13' => 'UPS Next Day Air Saver',
	    '14' => 'UPS Next Day Air Early AM',
	    '54' => 'UPS Worldwide Express Plus',
	    '59' => 'UPS Second Day Air AM',
	    '65' => 'UPS Saver'
	    );
	$xml_out="
<?xml version=\"1.0\"?>
<AccessRequest xml:lang=\"en-US\">
 	<AccessLicenseNumber>{$params['-accesskey']}</AccessLicenseNumber>
	<UserId>{$params['-userid']}</UserId>
	<Password>{$params['-password']}</Password>
</AccessRequest>
<?xml version=\"1.0\"?>
<RatingServiceSelectionRequest xml:lang=\"en-US\">
  <Request>
    <TransactionReference>
      <CustomerContext>Rating and Service</CustomerContext>
      <XpciVersion>1.0</XpciVersion>
    </TransactionReference>
	<RequestAction>Rate</RequestAction>
	<RequestOption>{$params['request_option']}</RequestOption>
  </Request>
    <PickupType>
  	<Code>{$params['pickup_type']}</Code>
  	<Description>Rate</Description>
    </PickupType>
  <Shipment>
    	<Description>Rate Description</Description>
    <Shipper>
      <ShipperNumber>{$params['-account']}</ShipperNumber>
      <Address>
        <PostalCode>{$params['-shipfrom_zip']}</PostalCode>
        <CountryCode>{$params['-shipfrom_country']}</CountryCode>
      </Address>
    </Shipper>
    <ShipTo>
      <Address>
        <PostalCode>{$params['-shipto_zip']}</PostalCode>
        <CountryCode>{$params['-shipto_country']}</CountryCode>
      </Address>
    </ShipTo>
  	<Service>
  		<Code>{$params['service_code']}</Code>
  	</Service>
  	<PaymentInformation>
	      	<Prepaid>
        		<BillShipper>
          			<AccountNumber>{$params['-account']}</AccountNumber>
        		</BillShipper>
      		</Prepaid>
  	</PaymentInformation>
  	<Package>
      		<PackagingType>
	        	<Code>{$params['package_type']}</Code>
        		<Description>Customer Supplied</Description>
      		</PackagingType>
      		<Description>Rate</Description>
      		<PackageWeight>
      			<UnitOfMeasurement>
      			  <Code>LBS</Code>
      			</UnitOfMeasurement>
	        	<Weight>{$params['-weight']}</Weight>
      		</PackageWeight>   
   	</Package>
  </Shipment>
</RatingServiceSelectionRequest>
";
	$url="https://www.ups.com/ups.app/xml/Rate";
    $result=postXML($url,$xml_out,array('-ssl'=>false));
	if(isset($result['xml_out'])){
		if(isset($result['xml_out']->RatedShipment)){
			$rates=array();
			foreach($result['xml_out']->RatedShipment as $rate){
				//return $rate;
				$upscode=(string)$rate->Service->Code;
				$servicetype='UPS ' . $upscode;
				$cost=(real)$rate->TotalCharges->MonetaryValue;
				$rates[$servicetype]=$cost;
				$rtn['descriptions'][$servicetype]=$lookup[$upscode];
	        	}
	        if(count($rates)>0){
				asort($rates);
				$rtn['rates']=$rates;
	        	}
			}
		}
	$rtn['result']=$result;
    return $rtn;
    }
//-----------------------
function upsTrack($params=array()){
	if(!isset($params['-userid'])){return "No userid";}
	if(!isset($params['-accesskey'])){return "No accesskey";}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-tn'])){return "No tn";}
	$rtn=array('-params'=>$params,'carrier'=>"UPS");
	$json_out=<<<ENDOFJSONOUT
{
	"UPSSecurity": { 
		"UsernameToken": { 
			"Username": "{$params['-userid']}", 
			"Password": "{$params['-password']}" 
		}, 
		"ServiceAccessToken": { 
			"AccessLicenseNumber": "{$params['-accesskey']}" 
		} 
	}, 
	"TrackRequest": { 
		"Request": { 
			"RequestOption": "1", 
			"TransactionReference": { 
				"CustomerContext": "Tracking Request" 
			} 
		}, 
		"InquiryNumber": "{$params['-tn']}" 
	} 
} 
ENDOFJSONOUT;
	if(isset($params['-test'])){
		$url='https://wwwcie.ups.com/rest/Track';
	}
	else{
		$url='https://onlinetools.ups.com/rest/Track';
	}
    $result=postJSON($url,$json_out,array('-ssl'=>false));
    //check for errors/fault
    if(isset($result['json_array']['Fault'])){
    	$rtn['tracking_number']=$params['-tn'];
		$rtn['error']=$result['json_array']['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Description'];
		$rtn['error_code']=$result['json_array']['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Code'];
		$rtn['status']='ERROR: '. $rtn['error'];
    }
    elseif(isset($result['json_array']['TrackResponse']['Shipment'])){
    	//Set Service
        $rtn['service']['code']=$result['json_array']['TrackResponse']['Shipment']['Service']['Code'];
        $rtn['service']['description']=$result['json_array']['TrackResponse']['Shipment']['Service']['Description'];
        //Set Tracking Number
        if(!isset($result['json_array']['TrackResponse']['Shipment']['Package'][0])){
        	$result['json_array']['TrackResponse']['Shipment']['Package']=array($result['json_array']['TrackResponse']['Shipment']['Package']);
        }
        $packages=$result['json_array']['TrackResponse']['Shipment']['Package'];
        if(count($packages)==1){
        	$rtn['tracking_number']=$packages[0]['TrackingNumber'];

        	//status
        	if(isset($packages[0]['Activity']['Status']['Description'])){
				$rtn['status']=$packages[0]['Activity']['Status']['Description'];
				$rtn['status_code']=$packages[0]['Activity']['Status']['Code'];
			}
			elseif(isset($packages[0]['Activity'][0]['Status']['Description'])){
				$rtn['status']=$packages[0]['Activity'][0]['Status']['Description'];
				$rtn['status_code']=$packages[0]['Activity'][0]['Status']['Code'];
			}
        	//set the delivery date if the package is delivered
			if(preg_match('/^delivered$/i',$packages[0]['Activity']['Status']['Description'])){
				$sdate=$packages[0]['Activity']['Date'];
				$stime=$packages[0]['Activity']['Time'];
		        if(strlen($sdate)){
					//20090908
					$year=substr($sdate,0,4);
					$month=substr($sdate,4,2);
					$day=substr($sdate,6,2);
					$hh=substr($stime,0,2);
					$mm=substr($stime,2,2);
					$ss=substr($stime,4,2);
					$rtn['delivery_date']="{$year}-{$month}-{$day} {$hh}:{$mm}:{$ss}";
		            $rtn['delivery_date_utime']=strtotime($rtn['delivery_date']);
				}
			}
			elseif(preg_match('/^delivered$/i',$packages[0]['Activity'][0]['Status']['Description'])){
				$sdate=$packages[0]['Activity'][0]['Date'];
				$stime=$packages[0]['Activity'][0]['Time'];
		        if(strlen($sdate)){
					//20090908
					$year=substr($sdate,0,4);
					$month=substr($sdate,4,2);
					$day=substr($sdate,6,2);
					$hh=substr($stime,0,2);
					$mm=substr($stime,2,2);
					$ss=substr($stime,4,2);
					$rtn['delivery_date']="{$year}-{$month}-{$day} {$hh}:{$mm}:{$ss}";
		            $rtn['delivery_date_utime']=strtotime($rtn['delivery_date']);
				}
			}
			//ship to
			if(isset($packages[0]['Activity']['ActivityLocation']['Address'])){
				foreach($packages[0]['Activity']['ActivityLocation']['Address'] as $k=>$v){
					$k=strtolower($k);
					$rtn['shipto'][$k]=$v;
				}
			}	
        }
        else{
        	foreach($packages as $package){
        		$pkg=array();
        		$pkg['tracking_number']=$package['TrackingNumber'];
        		//status
	        	if(isset($package['Activity']['Status']['Description'])){
					$pkg['status']=$package['Activity']['Status']['Description'];
					$pkg['status_code']=$package['Activity']['Status']['Code'];
				}
				elseif(isset($package['Activity'][0]['Status']['Description'])){
					$pkg['status']=$package['Activity'][0]['Status']['Description'];
					$pkg['status_code']=$package['Activity'][0]['Status']['Code'];
				}
	        	//set the delivery date if the package is delivered
				if(preg_match('/^delivered$/i',$package['Activity']['Status']['Description'])){
					$sdate=$package['Activity']['Date'];
					$stime=$package['Activity']['Time'];
			        if(strlen($sdate)){
						//20090908
						$year=substr($sdate,0,4);
						$month=substr($sdate,4,2);
						$day=substr($sdate,6,2);
						$hh=substr($stime,0,2);
						$mm=substr($stime,2,2);
						$ss=substr($stime,4,2);
						$pkg['delivery_date']="{$year}-{$month}-{$day} {$hh}:{$mm}:{$ss}";
			            $pkg['delivery_date_utime']=strtotime($pkg['delivery_date']);
					}
					//status
					$pkg['status']=$package['Activity']['Status']['Description'];
					$pkg['status_code']=$package['Activity']['Status']['Code'];
				}
				elseif(preg_match('/^delivered$/i',$package['Activity'][0]['Status']['Description'])){
					$sdate=$package['Activity'][0]['Date'];
					$stime=$package['Activity'][0]['Time'];
			        if(strlen($sdate)){
						//20090908
						$year=substr($sdate,0,4);
						$month=substr($sdate,4,2);
						$day=substr($sdate,6,2);
						$hh=substr($stime,0,2);
						$mm=substr($stime,2,2);
						$ss=substr($stime,4,2);
						$pkg['delivery_date']="{$year}-{$month}-{$day} {$hh}:{$mm}:{$ss}";
			            $pkg['delivery_date_utime']=strtotime($pkg['delivery_date']);
					}
					//status
					$pkg['status']=$package['Activity'][0]['Status']['Description'];
					$pkg['status_code']=$package['Activity'][0]['Status']['Code'];
				}
				//ship to
				if(isset($package['Activity']['ActivityLocation']['Address'])){
					foreach($package['Activity']['ActivityLocation']['Address'] as $k=>$v){
						$k=strtolower($k);
						$pkg['shipto'][$k]=$v;
					}
				}
				$rtn['packages'][]=$pkg;
        	}
        }
		//Ship_date
        $sdate=$result['json_array']['TrackResponse']['Shipment']['PickupDate'];
        if(strlen($sdate)){
			//20090908
			$year=substr($sdate,0,4);
			$month=substr($sdate,4,2);
			$day=substr($sdate,6,2);
			$rtn['ship_date']="{$year}-{$month}-{$day}";
            $rtn['ship_date_utime']=strtotime($rtn['ship_date']);
		}
    }
    //echo printValue($rtn).printValue($result['json_array']);exit;
    return $rtn;
}
//upsTrack depreciated API as of Jan 1 2020
function upsTrack_OLD($params=array()){
	if(!isset($params['-userid'])){return "No userid";}
	if(!isset($params['-accesskey'])){return "No accesskey";}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-tn'])){return "No tn";}
	$rtn=array('-params'=>$params,'carrier'=>"UPS");
	$xml_out="
<?xml version=\"1.0\"?>
<AccessRequest xml:lang=\"en-US\">
 	<AccessLicenseNumber>{$params['-accesskey']}</AccessLicenseNumber>
	<UserId>{$params['-userid']}</UserId>
	<Password>{$params['-password']}</Password>
</AccessRequest>
<?xml version=\"1.0\"?>
<TrackRequest>
	<Request>
		<TransactionReference>
			<CustomerContext>guidlikesubstance</CustomerContext>
		</TransactionReference>
		<RequestAction>Track</RequestAction>
		<RequestOption>activity</RequestOption>
	</Request>
	<TrackingNumber>{$params['-tn']}</TrackingNumber>
</TrackRequest>
";
	$url="https://www.ups.com/ups.app/xml/Track";
    $result=postXML($url,$xml_out,array('-ssl'=>false));
	if(isset($result['xml_out'])){
		if(isset($result['xml_out']->Response->Error)){
			$rtn['tracking_number']=$params['-tn'];
			$rtn['error']=(string)$result['xml_out']->Response->Error->ErrorDescription;
			$rtn['error_code']=(string)$result['xml_out']->Response->Error->ErrorCode;
			$rtn['status']='ERROR: '. $rtn['error'];
        	}
		else if(isset($result['xml_out']->Shipment)){
			//Set shipfrom
			foreach($result['xml_out']->Shipment->Shipper->Address as $ship){
				foreach($ship as $fld=>$val){
					$key=strtolower((string)$fld);
					$rtn['shipfrom'][$key]=(string)($val);
                	}
            	}
			//Set shipto
			foreach($result['xml_out']->Shipment->ShipTo->Address as $ship){
				foreach($ship as $fld=>$val){
					$key=strtolower((string)$fld);
					$rtn['shipto'][$key]=(string)($val);
                	}
            	}
            //Set Service
            $rtn['service']['code']=(string)$result['xml_out']->Shipment->Service->Code;
            $rtn['service']['description']=(string)$result['xml_out']->Shipment->Service->Description;
            $rtn['method']=$rtn['service']['description'];
			//Set Tracking Number
			$rtn['tracking_number']=(string)$result['xml_out']->Shipment->ShipmentIdentificationNumber;
			//Ship_date
            $sdate=(string)$result['xml_out']->Shipment->PickupDate;
			if(strlen($sdate)){
				//20090908
				$year=substr($sdate,0,4);
				$month=substr($sdate,4,2);
				$day=substr($sdate,6,2);
				$rtn['ship_date']="{$year}-{$month}-{$day}";
                $rtn['ship_date_utime']=strtotime($rtn['ship_date']);
				}
			//Scheduled Delivery Date
			$sddate=(string)$result['xml_out']->Shipment->ScheduledDeliveryDate;
			if(strlen($sddate)){
				//20090908
				$year=substr($sddate,0,4);
				$month=substr($sddate,4,2);
				$day=substr($sddate,6,2);
				$rtn['scheduled_delivery_date']="{$year}-{$month}-{$day}";
				$rtn['scheduled_delivery_date_utime']=strtotime($rtn['scheduled_delivery_date']);
				}
			//Pickup Date
			$pudate=(string)$result['xml_out']->Shipment->PickupDate;
			if(strlen($pudate)){
				//20090908
				$year=substr($pudate,0,4);
				$month=substr($pudate,4,2);
				$day=substr($pudate,6,2);
				$rtn['pickup_date']="{$year}-{$month}-{$day}";
				$rtn['pickup_date_utime']=strtotime($rtn['pickup_date']);
				}
			//Set Activity
			$activityList=array();
			foreach($result['xml_out']->Shipment->Package->Activity as $act){
				$activity=array();
				//date
				$datestr=(string)$act->Date;
				$year=substr($datestr,0,4);$month=substr($datestr,4,2);$day=substr($datestr,6,2);
				$timestr=(string)$act->Time;
				$hour=substr($timestr,0,2);$min=substr($timestr,2,2);$sec=substr($timestr,4,2);
				$activity['date']="{$year}-{$month}-{$day} {$hour}:{$min}:{$sec}";
				$activity['date_utime']=strtotime($activity['date']);
				//status
				$activity['status']=ucwords(strtolower((string)$act->Status->StatusType->Description));
				//set the delivery date if the package is delivered
				if(preg_match('/^delivered$/i',$activity['status']) && !isset($rtn['delivery_date'])){
					$rtn['delivery_date']=$activity['date'];
					$rtn['delivery_date_utime']=$activity['date_utime'];
				}
				//Status Code
				$activity['status_code']=(string)$act->Status->StatusType->Code;
				//location
				$activity['city']=ucwords(strtolower((string)$act->ActivityLocation->Address->City));
				$activity['state']=(string)$act->ActivityLocation->Address->StateProvinceCode;
				$activity['country']=(string)$act->ActivityLocation->Address->CountryCode;
				array_push($activityList,$activity);
            	}
            if(is_array($activityList)){
				//set the current status
				foreach($activityList[0] as $key=>$val){
					$rtn[$key]=$val;
                	}
                //set activity array
                $rtn['activity']=$activityList;
            	}
			}
		}
	$rtn['result']=$result;
    return $rtn;
    }
?>