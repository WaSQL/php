<?php
$progpath=dirname(__FILE__);
include_once("$progpath/weather/phpweatherlib.php");
/* References:
	http://www.weather.gov/forecasts/xml/
	http://www.weather.gov/forecasts/xml/docs/SOAP_Requests/LatLonListZipCode.xml
	http://www.ebrueggeman.com/phpweatherlib/documentation.php
*/
//------------------
function weatherByZip($zip,$params=array()){
	$station=weatherGetStationZip($zip);
	if(!isset($station['station_id'])){return '';}
	$displayWeather=true;
	$storedstr='return weatherByStationId(\''.$station['station_id'].'\');';
	//weather is updated every hour so store the value and only get it once per hour
	$weatherLib = getStoredValue($storedstr,0,1);
	if(!isset($params['location'])){$params['location']=$weatherLib->get_location();}
	if(!isset($params['dateformat'])){$params['dateformat']='l M jS g:i a';}
	if(!isset($params['hide'])){
		$params['hide']=array(
			'image'		=> false,
			'location'	=> false,
			'conditions'=> false,
			'temperature'=>false,
			'wind'		=> false,
			'humidity'	=> false,
			'obtime'		=>false
			);
		}

	$rtn.='<table class="w_weather">'."\n";
	if(!$params['hide']['location']){
		$rtn.='  <tr><th colspan="2" align="left"><div>'.$weatherLib->get_weather_string().'</div></th></tr>'."\n";
		}
	$rtn.='  <tr valign="top">'."\n";
	if(!$params['hide']['image']){
		$rtn.='    <td valign="top"><img src="'. $weatherLib->get_icon().'" alt="Weather Icon" name="currentConditions" id="currentConditions" /></td>'."\n";
		}
	$rtn.='    <td>'."\n";
	if(!$params['hide']['temperature']){
		$rtn.='		<div title="temperature"><img src="/wfiles/icons/misc/temperature.gif" border="0" style="vertical-align:middle"> <b>'. $weatherLib->get_temp_f().'&deg; F / '.$weatherLib->get_temp_c()."&deg; C</b></div>\n";
		}
	if(!$params['hide']['wind']){
		$rtn.='		<div title="wind"><img src="/wfiles/icons/misc/wind.gif" border="0" style="vertical-align:middle"> <b>'.$weatherLib->get_wind_string()."</b></div>\n";
		}
	if(!$params['hide']['humidity']){
		$rtn.='		<div title="humidity"><img src="/wfiles/icons/misc/humidity.gif" border="0" style="vertical-align:middle"> <b> '. $weatherLib->get_humidity()."&#37;</b></div>\n";
		}
	$rtn.='		</td>'."\n";
	$rtn.='  </tr>'."\n";
/* 	if(!$params['hide']['obtime']){
		$obtime=$weatherLib->get_observation_time();
		if(preg_match('/^Last Updated on (.+)/is',trim($obtime),$tmatch)){
			$obtime='' . date($params['dateformat'],strtotime($tmatch[1]));
			}
		$rtn.='  <tr><th colspan="2">'. $obtime.'</th></tr>'."\n";
		} */
	$rtn.='</table>'."\n";
	return $rtn;
	}
//------------------
function weatherByStationId($station){
	$weatherLib=new WeatherLib($station);
	if($weatherLib->has_error()){
		return $weatherLib->get_error();
		}
	return $weatherLib;
	}
//------------------
function weatherGetForecast($zip=''){
	//return weatherNDFDgenByDay($zip);
	$evalstr="return weatherNDFDgenByDay('{$zip}');";
	//the government weather is only updated hourly so we can use getStoredValue to cache the result.
	return getStoredValue($evalstr,0,1);
	}
function weatherNDFDgenByDay($zip='',$params=array()){
	//info: returns an array containing the weather forecast
	//build a soap request to the government weather service
	if(!strlen($zip)){return "No Zip";}
	//set defaults
	if(!isset($params['start'])){$params['start']=date('Y-m-d');}
	if(!isset($params['days'])){$params['days']=7;}
	$latlon=weatherGetLatLon($zip);
	$forecast=array('zip'=>$zip,'latlon'=>$latlon);
	//$latlon['lat'],$latlon['lon']
	$soap ='<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'."\n";
	$soap.='<SOAP-ENV:Body>'."\n";
	$soap.='<ns8077:NDFDgenByDay>'."\n";
	$soap.='<latitude xsi:type="xsd:decimal">'.$latlon['lat'].'</latitude>'."\n";
	$soap.='<longitude xsi:type="xsd:decimal">'.$latlon['lon'].'</longitude>'."\n";
	$soap.='<startDate xsi:type="xsd:string">'.$params['start'].'</startDate>'."\n";
	$soap.='<numDays xsi:type="xsd:integer">'.$params['days'].'</numDays>'."\n";
	$soap.='<format xsi:type="xsd:string">24 hourly</format>'."\n";
	$soap.='</ns8077:NDFDgenByDay>'."\n";
	$soap.='</SOAP-ENV:Body>'."\n";
	$soap.='</SOAP-ENV:Envelope>'."\n";
	$url='http://www.weather.gov/forecasts/xml/SOAP_server/ndfdXMLserver.php';
	$post=postXML($url,$soap,array('-soap'=>true));
	//$post=array();
	//$post['body']=getFileContents('weathersoap.xml');
	if(preg_match('/<dwmlByDayOut xsi:type="xsd:string">(.+)<\/dwmlByDayOut>/s',$post['body'],$smatch)){
		$soap = html_entity_decode($smatch[1]);
		$soap = str_replace('&apos;','"',$soap);
		$soap = str_replace('probability-of-precipitation','precipitation',$soap);
		$soap = str_replace('conditions-icon','images',$soap);
		$soap = str_replace('icon-link','icons',$soap);
		$soap = str_replace('weather-conditions','weather_conditions',$soap);
		$soap = str_replace('time-layout','time_layout',$soap);
		$soap = str_replace('-valid-time','_valid_time',$soap);
		//return $soap;
		$xml=soap2XML($soap);
		$index=0;
		//Days
		$index=0;
		foreach($xml->data->time_layout->start_valid_time as $ctime){
			$forecast['forecast'][$index]['start_datestamp']=strtotime($ctime);
			$forecast['forecast'][$index]['start_date']=date("D M jS",$forecast['forecast'][$index]['start_datestamp']);
			$index++;
			}
		$index=0;
		foreach($xml->data->time_layout->end_valid_time as $ctime){
			$forecast['forecast'][$index]['end_datestamp']=strtotime($ctime);
			$forecast['forecast'][$index]['end_date']=date("D M jS",$forecast['forecast'][$index]['end_datestamp']);
			$index++;
			}
		//Daily Maximum Temperature
		$index=0;
		foreach($xml->data->parameters->temperature[0] as $temp){
			if(isset($forecast['forecast'][$index]) && isNum((string)$temp)){
				$forecast['forecast'][$index]['max']=(integer)$temp;
				$index++;
				}
        	}
        //Daily Minimum Temperature
        $index=0;
		foreach($xml->data->parameters->temperature[1] as $temp){
			if(isset($forecast['forecast'][$index]) && isNum((string)$temp)){
				$forecast['forecast'][$index]['min']=(integer)$temp;
				$index++;
				}
        	}
        //Probability of Precipitation
        $index=0;
		foreach($xml->data->parameters->precipitation->value as $val){
			if(isset($forecast['forecast'][$index]) && isNum((string)$val)){
				$forecast['forecast'][$index]['precipitation']=(integer)$val;
				$index++;
				}
        	}
        //weather conditions for each day
        $index=0;
		foreach($xml->data->parameters->weather->weather_conditions as $weather){
			if(!isset($forecast['forecast'][$index])){continue;}
			$name=parseSimpleAttributes($weather);
			$info=parseSimpleAttributes($weather->value);
			$info['summary']=$name['weather-summary'];
			foreach($info as $key=>$val){
				$forecast['forecast'][$index][$key]=$val;
				}
			$index++;
        	}
        //image icons
        $index=0;
		foreach($xml->data->parameters->images->icons as $val){
			$val=(string)$val;
			if(preg_match('/^http/i',$val)){
				$forecast['forecast'][$index]['image']=$val;
				$index++;
				}
        	}
    	}
    else if(preg_match('/<detail xsi:type="xsd:string">(.+)<\/detail>/s',$post['body'],$smatch)){
    	$soap = html_entity_decode($smatch[1]);
		$soap = str_replace('&apos;','"',$soap);
		unset($smatch);
		if(preg_match('/<problem>(.+)<\/problem>/s',$soap,$smatch)){
			$forecast['error']=$smatch[1];
			unset($smatch);
			if(preg_match('/<latitudeLongitudes>(.+)<\/latitudeLongitudes>/is',$soap,$smatch)){
				$forecast['error'] .= "[{$smatch[1]}]";
				}
        	}
		}
    else{$forecast['error']="SOAP Request Failed";}
	return $forecast;
	}
//------------------
function weatherGetLatLon($zip=''){
	//info: latitude, longitude from zip code
	//info: returns an array containing the lat, lon, latlon, and zip
	//build a soap request
	$soap ='<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'."\n";
	$soap.='<SOAP-ENV:Body>'."\n";
	$soap.='<ns8077:LatLonListZipCode>'."\n";
	$soap.='<listZipCodeList xsi:type="xsd:string">'.$zip.'</listZipCodeList>'."\n";
	$soap.='</ns8077:LatLonListZipCode>'."\n";
	$soap.='</SOAP-ENV:Body>'."\n";
	$soap.='</SOAP-ENV:Envelope>'."\n";
	$url='http://www.weather.gov/forecasts/xml/SOAP_server/ndfdXMLserver.php';
	$post=postXML($url,$soap);
	unset($smatch);
	if(preg_match('/<listLatLonOut xsi:type="xsd:string">(.+)<\/listLatLonOut>/s',$post['body'],$smatch)){
		$soap = html_entity_decode($smatch[1]);
		$soap = str_replace('&apos;','"',$soap);
		//return $soap;
		$xml=soap2XML($soap);
		$string=trim((string)$xml->latLonList);
		list($lat,$lon)=preg_split('/\,/',$string);
		$rtn = array(
			'zip'	=> $zip,
			'lat'	=> trim($lat),
			'lon'	=> trim($lon),
			'latlon'=> trim($string)
			);
		return $rtn;
    	}
	return null;
	}
function weatherGetStationList($force=0,$try=0){
	//info: gets an array of weather stations - update every week (168 hours)
	$xmlstr = getStoredData('return weatherGetStationsXML();',$force,168,0);
	try {
		$xml = new SimpleXmlElement($xmlstr);
		$list=array();
		foreach($xml->station as $station){
			$crec=array();
			foreach($station as $citem=>$val){
				$key=(string)$citem;
				if(isNum((string)$val)){$crec[$key]=(real)$val;}
				else{$crec[$key]=removeCdata((string)$val);}
				}
			array_push($list,$crec);
        	}
		return $list;
		}
	catch (Exception $e){
		if($try==0){return weatherGetStationList(1,1);}
		return $e->faultstring;
        }
	return null;
	}
function weatherGetStationsXML(){
	$url = 'http://www.weather.gov/xml/current_obs/index.xml';
	$post=getURL($url);
	return $post['body'];
	$xmlstr=trim($post['body']);
	try {
		$xml = new SimpleXmlElement($xmlstr);
		return $xml;
		}
	catch (Exception $e){
		return $e->faultstring;
        }
	return "Failed";
	}
//------------------
function weatherGetStationZip($zip){
	$latlon=weatherGetLatLon($zip);
	$station=weatherGetStation($latlon['lat'],$latlon['lon']);
	return $station;
	}
//------------------
function weatherGetStation($lat='',$lon=''){
	//info: gets nearest station
	//info: returns an array containing the lat, lon, latlon, and zip
	$stations=weatherGetStationList();
	unset($best);
	for($x=0;$x<count($stations);$x++){
		$distance=weatherGetDistance($stations[$x]['latitude'],$stations[$x]['longitude'],$lat,$lon);
		if(!isset($best)){
			$best=$stations[$x];
			$best['distance']=$distance;
			continue;
			}
		if($distance < $best['distance']){
			$best=$stations[$x];
			$best['distance']=$distance;
        	}
    	}
	return $best;
	}
function weatherGetDistance($lat1, $lon1, $lat2, $lon2) {
	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;
	return $miles;
	}
?>