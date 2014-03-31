<?
global $USER;
if(!strlen($_REQUEST['tn'])){return "No Tracking Number";}
$rtn='';
/*Celio Credentials*/
$credentials=array(
	'Key'			=> 'YourFedExKeyGoesHere',
	'Password'		=> 'YourFedExPasswordGoesHere'
	);
$account=array(
	'AccountNumber'	=> 'YourFedExAccountNumberGoesHere',
	'MeterNumber' 	=> 'YourFedExMeterNumberGoesHere'
	);
/*Load libraries and wsdls*/
require_once('./fedex/common.php5');
date_default_timezone_set('America/Denver');
$path_to_wsdl = "./fedex/TrackService_v2.wsdl";
ini_set("soap.wsdl_cache_enabled", "1");
/*Soap Request*/
$client = new SoapClient($path_to_wsdl, array('trace' => 0)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
$request=array();
$request['WebAuthenticationDetail'] = array('UserCredential' =>$credentials);
$request['ClientDetail'] = $account;
$request['TransactionDetail'] = array('CustomerTransactionId' => '*** Track Request v2 using PHP ***');
$request['Version'] = array('ServiceId' => 'trck', 'Major' => '2', 'Intermediate' => '0', 'Minor' => '0');
$request['PackageIdentifier'] = array('Value' => $_REQUEST['tn'], 'Type' => 'TRACKING_NUMBER_OR_DOORTAG');
$request['IncludeDetailedScans'] = 1;
try{
    $response = $client ->track($request);
	if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
		$trackingNumber=$response->TrackDetails->TrackingNumber;
		$shipMethod=$response->TrackDetails->ServiceInfo;
		$status=$response->TrackDetails->StatusDescription;
		//debug?
		if(isset($_REQUEST['debug'])){return printValue($response);}
		//format=rss?
		if(isset($_REQUEST['format']) && strtolower($_REQUEST['format'])=='rss'){
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
		    $rtn .= '			<method>'.$shipMethod.'</method>'."\n";
		    if(isset($response->TrackDetails->ActualDeliveryTimestamp)){
				//2009-02-03T16:13:00-07:00
				$ddate=(string)$response->TrackDetails->ActualDeliveryTimestamp;
				$dts=strtotime($ddate);
				$cdate=date("D M jS h:i a",$dts);
				$sqldate=date("Y-m-d H:i:s",$dts);
				list($ddate,$hr,$min,$sec)=split('[T:]',$ddate);
				list($sec,$offset)=split('[-]',$sec);
				$rtn .= '			<date>'.$sqldate.'</date>'."\n";
	        	}
		  	$rtn .= '		</item>'."\n";
			$rtn .= '	</channel>'."\n";
			$rtn .= '</rss>'."\n";
			return $rtn;
			}
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
        $rtn .= '<div class="w_red">Error: Perhaps the tracking number is invalid.</div>'."\n";
    	}
	}
catch (SoapFault $exception) {
	$rtn .= printValue($exception);
	}
return $rtn;
?>
