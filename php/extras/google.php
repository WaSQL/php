<?php
$progpath=dirname(__FILE__);
/*
	Google Add to Calendar link example:
	https://www.google.com/calendar/event?action=TEMPLATE&pprop=eidmsgid%3A_9pb5kdai6tc48_16e199f8ba421fb2&dates=20191101T071500%2F20191101T080000&text=I%27d%20like%20a%20demo%20of%20the%20thinkorswim%20platform&location=TD%20Ameritrade&details=Dear%20Steven%20Jones%2C%20%0A%0AHere%20is%20your%20appointment%20information%20so%20that%20you%20may%20update%20your%20calendar.%0A%0AConfirmation%20Number%3A%20NVXD%20%0AActivity%20Name%3A%20%20%20I%27d%20like%20a%20demo%20of%20the%20thinkorswim%20platform%20%0ADate%3A%20Friday%2C%20November%201%2C%202019%20-%207%3A15%20AM%20%0AYou%20can%20use%20the%20following%20link%20to%20cancel%20or%20reschedule%20your%20appointment%3A%0A%0Ahttps%3A%2F%2Fwww.timetrade.com%2Fapp%2Ftdameritrade%2Fworkflows%2Ftd001%2Ffind%3Fattendee_person_lastName%3DJones%26appointmentId%3DNVZD&ctok=c3RldmUubGxveWRAZ21haWwuY29t
*/

//Google API to access analytics, calendar, etc.
//http://code.google.com/support/bin/answer.py?answer=62712&topic=10433
//http://code.google.com/apis/accounts/docs/AuthForInstalledApps.html#Request
//http://code.google.com/apis/analytics/docs/gdata/1.0/gdataProtocol.html#retrievingData
//http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
include_once("$progpath/google/GoogleCalendarWrapper.php");

/*
	go to https://console.developers.google.com for an apikey
*/
function googleTranslate($apikey,$text,$target,$source='en'){
	if(!isset($params['-apikey'])){return "No apikey";}
	if(!isset($params['-text'])){return "No text";}
	if(!isset($params['-target'])){return "No target";}
	//default source to en
	if(!isset($params['-source'])){$params['-source']='en';}
    $url = "https://www.googleapis.com/language/translate/v2?key={$params['-apikey']}&q=".rawurlencode($params['-text'])."&source={$params['-source']}&target={$params['-target']}";
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($handle);
    $responseDecoded = json_decode($response, true);
    curl_close($handle);
	return $responseDecoded['data']['translations'][0]['translatedText'];
}
//------------------
function googleGetLatLon($address){
	//info: return the Latitude and Longitude of the address specified.
	//info: requires a google maps API key obtained at http://code.google.com/apis/maps/signup.html
	$url='http://maps.googleapis.com/maps/api/geocode/xml';
	$post=postURL($url,array('-method'=>"GET",
		'address'=>encodeURL($address),
		'sensor'	=> 'false'
		));
	$xml=xml2Object($post['body']);
	$latlon=array();
	if(isset($xml->result->geometry->location)){
		$latlon['lat']	= (string)$xml->result->geometry->location->lat;
		$latlon['lon']	= (string)$xml->result->geometry->location->lng;
		}
	if(isset($xml->result->geometry->viewport->southwest)){
		$latlon['southwest']['lat']	= (string)$xml->result->geometry->viewport->southwest->lat;
		$latlon['southwest']['lon']	= (string)$xml->result->geometry->viewport->southwest->lng;
		}
	if(isset($xml->result->geometry->viewport->northeast)){
		$latlon['northeast']['lat']	= (string)$xml->result->geometry->viewport->northeast->lat;
		$latlon['northeast']['lon']	= (string)$xml->result->geometry->viewport->northeast->lng;
		}
	if(isset($latlon['lat'])){return $latlon;}
	return $post;
	}
//------------------
function adwordsTargetingIdea($params=array()){
	if(!isset($params['-email'])){return "No email" . printValue($params);}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-devtoken'])){return "No devtoken";}
	if(!isset($params['-apptoken'])){return "No apptoken";}
	if(!isset($params['-apptoken'])){return "No apptoken";}
	if(!isset($params['keyword'])){return "No keyword";}
	$progpath=dirname(__FILE__);
	require_once "{$progpath}/google/AdWords/Lib/AdWordsUser.php";
	try {
		// Credentials
		$user = new AdWordsUser(NULL, $params['-email'], $params['-password'], $params['-devtoken'],
			$params['-apptoken'],"WaSQL API for Google Adwords");
		if(!is_object($user)){return "Authentication failure";}
		$user->LogDefaults();
		// Get the TargetingIdeaService.
		$targetingIdeaService = $user->GetTargetingIdeaService('v200909');
		// Set keyword(s) to look for
		$keywords=array();
		if(is_array($params['keyword'])){
			foreach($params['keyword'] as $word){
				$keywords[]=new Keyword($word, 'EXACT');
            	}
        	}
        else{
			$keywords[] = new Keyword($params['keyword'], 'EXACT');
			}
		// Set language.
		$language = new LanguageTarget('en');
		// Set country.
		$country = new CountryTarget('US');
		// Create selector to get related keywords.
		$selector = new TargetingIdeaSelector();
		$selector->requestType = 'STATS';
		$selector->ideaType = 'KEYWORD';
		$selector->searchParameters = array(
			new RelatedToKeywordSearchParameter($keywords),
			new KeywordMatchTypeSearchParameter('EXACT'),
			new LanguageTargetSearchParameter(array($language)),
			new CountryTargetSearchParameter(array($country))
			);
		$selector->requestedAttributeTypes = array(
			'KEYWORD',
			'AVERAGE_TARGETED_MONTHLY_SEARCHES',
			'GLOBAL_MONTHLY_SEARCHES'
			);
		// Increase paging for more results.
		$selector->paging = new Paging(0, count($keywords));
		// Get targeting ideas.
		$page = $targetingIdeaService->get($selector);
		//parse and return only the data we want.
		$entries=array();
		if(isset($page->entries)){
			foreach($page->entries as $entryobj){
				$entry=array();
				foreach($entryobj->data as $dataobj){

					$key=strtolower((string)$dataobj->key);
					if(isset($dataobj->value->value->text)){
						$val=strtolower((string)$dataobj->value->value->text);
						}
					else{
						$val=strtolower((integer)$dataobj->value->value);
                    	}
                    $entry[$key]=$val;
                	}
                $entries[$entry['keyword']]=$entry;
            	}
        	}
		return $entries;
		}
	catch(Exception $e){
		return $e;
		}
	}
//------------------
function adwordsTargetingIdeaNS($params=array()){
	//use if you server does not support soap
	if(!isset($params['-email'])){return "No email" . printValue($params);}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-devtoken'])){return "No devtoken";}
	if(!isset($params['-apptoken'])){return "No apptoken";}
	if(!isset($params['-apptoken'])){return "No apptoken";}
	if(!isset($params['keyword'])){return "No keyword";}
	
	$authtoken=getGoogleAuth(array(
		'-user'		=> $params['-email'],
		'-pass'		=> $params['-password'],
		'service'	=> 'adwords',
		));
	$url='https://adwords.google.com/api/adwords/o/v200909/TargetingIdeaService';
	$xmlrequest='
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://adwords.google.com/api/adwords/cm/v200909" xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <SOAP-ENV:Header>
    <ns2:RequestHeader xsi:type="ns1:RequestHeader">
      <ns1:applicationToken>'.$params['-apptoken'].'</ns1:applicationToken>
      <ns1:authToken>'.$authtoken.'</ns1:authToken>
      <ns1:developerToken>'.$params['-devtoken'].'</ns1:developerToken>
      <ns1:userAgent>PHP v5.2.13 - AdWords API PHP Client Library - v2.0.0 - WaSQL API for Google Adwords</ns1:userAgent>
    </ns2:RequestHeader>
  </SOAP-ENV:Header>
  <SOAP-ENV:Body>
    <ns2:get>
      <ns2:selector>
        <ns2:searchParameters xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xsi:type="ns2:RelatedToKeywordSearchParameter">
          <ns2:keywords>
            <ns1:text>termites</ns1:text>
            <ns1:matchType>EXACT</ns1:matchType>
          </ns2:keywords>
          <ns2:keywords>
            <ns1:text>pest control</ns1:text>
            <ns1:matchType>EXACT</ns1:matchType>
          </ns2:keywords>
          <ns2:keywords>
            <ns1:text>rat control</ns1:text>
            <ns1:matchType>EXACT</ns1:matchType>
          </ns2:keywords>
        </ns2:searchParameters>
        <ns2:searchParameters xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xsi:type="ns2:KeywordMatchTypeSearchParameter">
          <ns2:keywordMatchTypes>EXACT</ns2:keywordMatchTypes>
        </ns2:searchParameters>
        <ns2:searchParameters xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xsi:type="ns2:LanguageTargetSearchParameter">
          <ns2:languageTargets>
            <ns1:languageCode>en</ns1:languageCode>
          </ns2:languageTargets>
        </ns2:searchParameters>
        <ns2:searchParameters xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xsi:type="ns2:CountryTargetSearchParameter">
          <ns2:countryTargets>
            <ns1:countryCode>US</ns1:countryCode>
          </ns2:countryTargets>
        </ns2:searchParameters>
        <ns2:ideaType>KEYWORD</ns2:ideaType>
        <ns2:requestType>STATS</ns2:requestType>
        <ns2:requestedAttributeTypes>KEYWORD</ns2:requestedAttributeTypes>
        <ns2:requestedAttributeTypes>AVERAGE_TARGETED_MONTHLY_SEARCHES</ns2:requestedAttributeTypes>
        <ns2:requestedAttributeTypes>GLOBAL_MONTHLY_SEARCHES</ns2:requestedAttributeTypes>
        <ns2:paging>
          <ns1:startIndex>0</ns1:startIndex>
          <ns1:numberResults>3</ns1:numberResults>
        </ns2:paging>
      </ns2:selector>
    </ns2:get>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>


';
	$result=postXML($url,$xmlrequest,array(
		'-soap'=>1,
		'-headers'=>array(
			'Content-Type: text/xml; charset=utf-8',
			'Accept: application/xml; charset=UTF-8'
			),
		'-encoding'=>"gzip, deflate",
		'-user_agent'=>"PHP-SOAP/5.2.13, gzip",
		'-crlf'=>true
		));
	return $result;
	}
//------------------
function adwordsTargetingIdeaNS2($params=array()){
	//use if you server does not support soap
	if(!isset($params['-email'])){return "No email" . printValue($params);}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-devtoken'])){return "No devtoken";}
	if(!isset($params['-apptoken'])){return "No apptoken";}
	if(!isset($params['-apptoken'])){return "No apptoken";}
	if(!isset($params['keyword'])){return "No keyword";}
	
	$authtoken=getGoogleAuth(array(
		'-user'		=> $params['-email'],
		'-pass'		=> $params['-password'],
		'service'	=> 'adwords',
		));
	$url='https://adwords.google.com/api/adwords/o/v200909/TargetingIdeaService';
	$xmlrequest='
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://adwords.google.com/api/adwords/cm/v200909" xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <SOAP-ENV:Header>
    <ns2:RequestHeader xsi:type="ns1:RequestHeader">
      <ns1:applicationToken>qmhH6jtBM67YvFskWFuIhQ</ns1:applicationToken>
      <ns1:authToken>'.$authtoken.'</ns1:authToken>
      <ns1:developerToken>L8MP1RQFJfIc_KrxJ6pdHg</ns1:developerToken>
      <ns1:userAgent>PHP v5.2.13 - AdWords API PHP Client Library - v2.0.0 - WaSQL API for Google Adwords</ns1:userAgent>
    </ns2:RequestHeader>
  </SOAP-ENV:Header>
  <SOAP-ENV:Body>
    <ns2:get>
      <ns2:selector>
        <ns2:searchParameters xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xsi:type="ns2:RelatedToKeywordSearchParameter">
          <ns2:keywords>
            <ns1:text>termites</ns1:text>
            <ns1:matchType>EXACT</ns1:matchType>
          </ns2:keywords>
          <ns2:keywords>
            <ns1:text>pest control</ns1:text>
            <ns1:matchType>EXACT</ns1:matchType>
          </ns2:keywords>
          <ns2:keywords>
            <ns1:text>rat control</ns1:text>
            <ns1:matchType>EXACT</ns1:matchType>
          </ns2:keywords>
        </ns2:searchParameters>
        <ns2:searchParameters xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xsi:type="ns2:KeywordMatchTypeSearchParameter">
          <ns2:keywordMatchTypes>EXACT</ns2:keywordMatchTypes>
        </ns2:searchParameters>
        <ns2:searchParameters xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xsi:type="ns2:LanguageTargetSearchParameter">
          <ns2:languageTargets>
            <ns1:languageCode>en</ns1:languageCode>
          </ns2:languageTargets>
        </ns2:searchParameters>
        <ns2:searchParameters xmlns:ns2="https://adwords.google.com/api/adwords/o/v200909" xsi:type="ns2:CountryTargetSearchParameter">
          <ns2:countryTargets>
            <ns1:countryCode>US</ns1:countryCode>
          </ns2:countryTargets>
        </ns2:searchParameters>
        <ns2:ideaType>KEYWORD</ns2:ideaType>
        <ns2:requestType>STATS</ns2:requestType>
        <ns2:requestedAttributeTypes>KEYWORD</ns2:requestedAttributeTypes>
        <ns2:requestedAttributeTypes>AVERAGE_TARGETED_MONTHLY_SEARCHES</ns2:requestedAttributeTypes>
        <ns2:requestedAttributeTypes>GLOBAL_MONTHLY_SEARCHES</ns2:requestedAttributeTypes>
        <ns2:paging>
          <ns1:startIndex>0</ns1:startIndex>
          <ns1:numberResults>3</ns1:numberResults>
        </ns2:paging>
      </ns2:selector>
    </ns2:get>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
';
	$result=postXML($url,$xmlrequest,array(
		'-soap'=>1,
		'-headers'=>array(
			'Content-Type: text/xml; charset=utf-8',
			'Accept: application/xml; charset=UTF-8'
			),
		'-encoding'=>"gzip, deflate",
		'-user_agent'=>"PHP-SOAP/5.2.13, gzip",
		'-crlf'=>true
		));
	return $result;
	}
//------------------
function googleClientLogin($params=array()){
	//gets the authorization string needed
	$url='https://www.google.com/accounts/ClientLogin';
	if(!isset($params['-user'])){return "No User";}
	if(!isset($params['-pass'])){return "No Pass";}
	if(!isset($params['service'])){$params['service']="analytics";}
	$opts=array(
		'accountType'	=> "GOOGLE",
		'Email'			=> $params['-user'],
		'Passwd'		=> $params['-pass'],
		'service'		=> $params['service'],
		'source'		=> 'Wasql Google API Library v2.0',
		'-ssl'			=> false
		);
	$rtn=postURL($url,$opts);
	if(isset($rtn['body'])){
		list($sid,$lsid,$auth)=preg_split('/[\r\n]+/',$rtn['body']);
		$auth=preg_replace('/^Auth\=/','',$auth);
		return $auth;
		}
	return printValue($rtn);
	}
//------------------
function getGoogleAuth($params=array()){
	$url='https://www.google.com/accounts/ClientLogin';
	if(!isset($params['-user'])){return "No User";}
	if(!isset($params['-pass'])){return "No Pass";}
	if(!isset($params['service'])){$params['service']="analytics";}
	$opts=array(
		'accountType'	=> "GOOGLE",
		'Email'			=> $params['-user'],
		'Passwd'		=> $params['-pass'],
		'service'		=> $params['service'],
		'source'		=> 'Wasql-API-1.0',
		'-ssl'			=> false
		);
	$rtn=postURL($url,$opts);
	if(isset($rtn['body'])){
		list($sid,$lsid,$auth)=preg_split('/[\r\n]+/',$rtn['body']);
		$auth=preg_replace('/^Auth\=/','',$auth);
		return $auth;
		}
	return null;
	}
//------------------
function getGoogleData($params=array()){
	//GET http://www.google.com/calendar/feeds/default/private/full?start-min=2006-03-16T00:00:00&start-max=2006-03-24T23:59:59
	$url='https://www.google.com/analytics/feeds/data';
	//Required
	if(!isset($params['-auth'])){return "No Auth";}
	if(!isset($params['-profile'])){return "No Profile";}
	if(!isset($params['start-date'])){return "No Start Date";}
	if(!isset($params['end-date'])){return "No End Date";}
	$opts=array(
		'-headers'		=> array("Authorization: GoogleLogin Auth={$params['-auth']}"),
		'ids'			=> 'ga:'.$params['-profile'],
		'start-date'	=> $params['start-date'],
		'end-date'		=> $params['end-date'],
		'-method'		=> 'GET',
		'-ssl'			=> false
		);
	//Optional:
	$optionals=array('dimensions','metrics','sort','filters');
	foreach($optionals as $optional){
		if(isset($params[$optional])){$opts[$optional]=$params[$optional];}
    	}
    //post Request and return the resulting xml
	$rtn=postURL($url,$opts);
	if(isXML($rtn['body'])){
		return soap2XML($rtn['body']);
		}
	return $rtn;
	}
//-----------------------------
function getGoogleStats($params=array()){
	if(!isset($params['-user'])){return "No User";}
	if(!isset($params['-pass'])){return "No Pass";}
	//profile: https://www.google.com/analytics/reporting/?reset=1&id= >> 12149828 << &pdr=20090409-20090509
	if(!isset($params['-profile'])){return "No Profile";}
	if(!isset($params['start-date'])){return "No Start Date";}
	if(!isset($params['end-date'])){return "No End Date";}
	//default metrics and dimensions
	if(!isset($params['metrics'])){$params['metrics']='ga:visitors,ga:pageviews,ga:visits,ga:newVisits,ga:timeOnSite';}
	if(!isset($params['dimensions'])){$params['dimensions']='ga:date';}
	if(!isset($params['service'])){$params['service']='analytics';}
	$auth=getGoogleAuth(array(
		'-user'		=> $params['-user'],
		'-pass'		=> $params['-pass'],
		'service'	=> $params['service'],
		));
	$data=getGoogleData(array(
		'-auth'		=> $auth,
		'-profile'	=> $params['-profile'],
		'metrics'	=> $params['metrics'],
		'dimensions'=> $params['dimensions'],
		'start-date'=> $params['start-date'],
		'end-date'	=> $params['end-date']
		));
	$stats=array();
	foreach($data->entry as $entry){
		//get date
		foreach($entry->dxpdimension->attributes() as $key=>$val){
			$key=(string)$key;
			if($key=='value'){$date=(string)$val;}
	    	}
	    if(!isset($date)){continue;}
	    //date is coming in as 20090401 - format it as 2009-04-01
	    $year=substr($date,0,4);
	    $mon=substr($date,4,2);
	    $day=substr($date,6,2);
	    $date="{$year}-{$mon}-{$day}";

	    foreach($entry->dxpmetric as $metric){
			$info=parseSimpleAttributes($metric);
			$name=preg_replace('/^ga:/','',$info['name']);
			if($info['type']=='integer'){$val=(integer)$info['value'];}
			else if($info['type']=='time'){$val=(integer)$info['value'];}
			else{$val=(string)$info['value'];}
			if($name=='newVisits'){$name='uniqueVisitors';}
			if($name=='visitors'){$name='webVisitors';}
			$stats[$date][$name]=$val;
			}
		//average time on site: timeOnSite/visitors
		if(isset($stats[$date]['timeOnSite']) && isset($stats[$date]['visits'])){
			$seconds=round(($stats[$date]['timeOnSite']/$stats[$date]['visits']),2);
			$stats[$date]['aveTimeOnSite']=round(($seconds/60),2);
			}
		//pages per visit
		if(isset($stats[$date]['pageviews']) && isset($stats[$date]['visits'])){
			$stats[$date]['pagesPerVisit']=round(($stats[$date]['pageviews']/$stats[$date]['visits']),2);
			}
		}
	return $stats;
	}
function getGoogleCalendar($params=array()){
	if(!isset($params['-auth'])){
		if(!isset($params['-user'])){return "No User";}
		if(!isset($params['-pass'])){return "No Pass";}
		$params['-auth']=getGoogleAuth(array(
			'-user'		=> $params['-user'],
			'-pass'		=> $params['-pass'],
			'service'	=> $params['service'],
			));
		}
	//profile: https://www.google.com/analytics/reporting/?reset=1&id= >> 12149828 << &pdr=20090409-20090509
	if(!isset($params['-profile'])){return "No Profile";}
	if(!isset($params['start-date'])){return "No Start Date";}
	if(!isset($params['end-date'])){return "No End Date";}
	//default metrics and dimensions
	if(!isset($params['metrics'])){$params['metrics']='ga:visitors,ga:pageviews,ga:visits,ga:newVisits,ga:timeOnSite';}
	if(!isset($params['dimensions'])){$params['dimensions']='ga:date';}
	if(!isset($params['service'])){$params['service']='analytics';}

	$data=getGoogleCalendarData(array(
		'-auth'		=> $params['-auth'],
		'-profile'	=> $params['-profile'],
		'metrics'	=> $params['metrics'],
		'dimensions'=> $params['dimensions'],
		'start-date'=> $params['start-date'],
		'end-date'	=> $params['end-date']
		));
	return printValue($data);
	$stats=array();
	foreach($data->entry as $entry){
		//get date
		foreach($entry->dxpdimension->attributes() as $key=>$val){
			$key=(string)$key;
			if($key=='value'){$date=(string)$val;}
	    	}
	    if(!isset($date)){continue;}
	    //date is coming in as 20090401 - format it as 2009-04-01
	    $year=substr($date,0,4);
	    $mon=substr($date,4,2);
	    $day=substr($date,6,2);
	    $date="{$year}-{$mon}-{$day}";

	    foreach($entry->dxpmetric as $metric){
			$info=parseSimpleAttributes($metric);
			$name=preg_replace('/^ga:/','',$info['name']);
			if($info['type']=='integer'){$val=(integer)$info['value'];}
			else if($info['type']=='time'){$val=(integer)$info['value'];}
			else{$val=(string)$info['value'];}
			if($name=='newVisits'){$name='uniqueVisitors';}
			if($name=='visitors'){$name='webVisitors';}
			$stats[$date][$name]=$val;
			}
		//average time on site: timeOnSite/visitors
		if(isset($stats[$date]['timeOnSite']) && isset($stats[$date]['visits'])){
			$seconds=round(($stats[$date]['timeOnSite']/$stats[$date]['visits']),2);
			$stats[$date]['aveTimeOnSite']=round(($seconds/60),2);
			}
		//pages per visit
		if(isset($stats[$date]['pageviews']) && isset($stats[$date]['visits'])){
			$stats[$date]['pagesPerVisit']=round(($stats[$date]['pageviews']/$stats[$date]['visits']),2);
			}
		}
	return $stats;
	}
//------------------
function getGoogleCalendarList($params=array()){
	if(!isset($params['-auth'])){
		if(!isset($params['-user'])){return "No User";}
		if(!isset($params['-pass'])){return "No Pass";}
		$params['-auth']=getGoogleAuth(array(
			'-user'		=> $params['-user'],
			'-pass'		=> $params['-pass'],
			'service'	=> 'cl',
			));
		}
	$url='http://www.google.com/calendar/feeds/default';
	//Required
	if(!isset($params['-auth'])){return "No Auth";}
	$opts=array(
		'-headers'		=> array("Authorization: GoogleLogin Auth={$params['-auth']}"),
		'-method'		=> 'GET'
		);
    //post Request and return the resulting xml
	$rtn=postURL($url,$opts);
	if(isXML($rtn['body'])){
		return soap2XML($rtn['body']);
		}
	return $rtn;
	}
//------------------
function getGoogleCalendarData($params=array()){
	$url='http://www.google.com/calendar/feeds/default/private/full';
	//Required
	if(!isset($params['-auth'])){return "No Auth";}
	if(!isset($params['-profile'])){return "No Profile";}
	if(!isset($params['start-date'])){return "No Start Date";}
	if(!isset($params['end-date'])){return "No End Date";}
	$opts=array(
		'-headers'		=> array("Authorization: GoogleLogin Auth={$params['-auth']}"),
		'ids'			=> 'ga:'.$params['-profile'],
		'start-max'		=> $params['start-date'],
		'start-min'		=> $params['end-date'],
		'-method'		=> 'GET',
		'-ssl'			=> false
		);
	//Optional:
	$optionals=array('dimensions','metrics','sort','filters');
	foreach($optionals as $optional){
		if(isset($params[$optional])){$opts[$optional]=$params[$optional];}
    	}
    //post Request and return the resulting xml
	$rtn=postURL($url,$opts);
	if(isXML($rtn['body'])){
		return soap2XML($rtn['body']);
		}
	return $rtn;
	}
	
//------------------
//Test function to Add Cal Events from within Flyzone
function postCalEvent($params=array()){

	$gc = new GoogleCalendarWrapper("gmail@getredfly.com", "R3dfly001!");
	$gc->feed_url = $params['url'];

	$s = array(
	'title' => $params['title'],
	'content' => $params['content'],
	'where' => $params['where'],
	'startDay' => $params['startDay'],
	'startTime' => $params['startTime'],
	'endDay' => $params['endDay'],
	'endTime' => $params['endTime'],
	);
	
	$gc->login();
	$gc->prepare_feed_url();
	if ($params['allDay'] == false){
	$rtn=$gc->add_event($s);}
	else{
	$rtn=$gc->add_event_allday($s);}
	
	
	$split=preg_split('/ /',$rtn['raw']);
	
	
	if ($split[1] == '201')
	return '1';
	else
	return '0';
	
	}
//------------------------------------------
//Retrieve Calendar events
function getCalEvent($params=array()){
	$gc = new GoogleCalendarWrapper("gmail@getredfly.com", "R3dfly001!");
	$gc->feed_url = $params['url'];
	$gc->login();
	$url=$gc->prepare_feed_url();
	$url=$url."?q=".$params['search'];
	$events = $gc->getEvent($url);
		
	return $events;
}

function modCalEvent($params=array()){
	$gc = new GoogleCalendarWrapper("gmail@getredfly.com", "R3dfly001!");
	$gc->login();
	if($params['mod']== 'delete'){$events = $gc->deleteEvent($params);}
	if($params['mod']== 'edit'){$events = $gc->editEvent($params);}
		
	return $events;
}

function prepDate($myDate) {
	$startD='';
	$date=str_replace('-', '/', $myDate);
	$startD=strtotime($date.'UTC');
	$gmstartD=gmdate("Y-m-d", $startD);
		
	return $gmstartD;
	}

function endDate($myDate,$params=array()) {
	if($params['allDay'])
	{
		$startD='';
		$date=str_replace('-', '/', $myDate);
		$startD=strtotime($date.'UTC');
		$startD=DateAdd('d',1,$startD);
		$gmstartD=gmdate("Y-m-d", $startD);
	}
	else
	{
		$startD='';
		$date=str_replace('-', '/', $myDate);
		$startD=strtotime($date.'UTC');
		$gmstartD=gmdate("Y-m-d", $startD);
	}
		
	return $gmstartD;
	}



function prepTime($myTime){
	
	$time='';
	$time2='';
	$time=strtotime($myTime);
	$time2=gmdate("H:i:s",$time);
	return $time2;
	
	}
	
function DateAdd($interval, $number, $date) {

    $date_time_array = getdate($date);
    $hours = $date_time_array['hours'];
    $minutes = $date_time_array['minutes'];
    $seconds = $date_time_array['seconds'];
    $month = $date_time_array['mon'];
    $day = $date_time_array['mday'];
    $year = $date_time_array['year'];

    switch ($interval) {
    
        case 'yyyy':
            $year+=$number;
            break;
        case 'q':
            $year+=($number*3);
            break;
        case 'm':
            $month+=$number;
            break;
        case 'y':
        case 'd':
        case 'w':
            $day+=$number;
            break;
        case 'ww':
            $day+=($number*7);
            break;
        case 'h':
            $hours+=$number;
            break;
        case 'n':
            $minutes+=$number;
            break;
        case 's':
            $seconds+=$number; 
            break;            
    }
       $timestamp= mktime($hours,$minutes,$seconds,$month,$day,$year);
    return $timestamp;
}
?>