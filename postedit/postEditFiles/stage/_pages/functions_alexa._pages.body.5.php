<?php
/*
https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/speech-synthesis-markup-language-ssml-reference
<speak>
	<say-as interpret-as="cardinal">12345</say-as>,
	<say-as interpret-as="digits">12345</say-as>,
	<say-as interpret-as="ordinal">5</say-as>,
	<say-as interpret-as="characters">read</say-as>,
	<say-as interpret-as="date">20160308</say-as>,
	<say-as interpret-as="time">23'6</say-as>,
	<say-as interpret-as="telephone">3854141728</say-as>,
	<say-as interpret-as="address">2325 N 600 W, Pleasant Grove, UT 84062</say-as>,
	<say-as interpret-as="fraction">3+1/2</say-as>,
    the present simple form <w role="ivona:VB">read</w>,
    or the past participle form <w role="ivona:VBD">read</w>,
    or the noun form <w role="ivona:NN">read</w>,
    or the non-default sense of the word <w role="ivona:SENSE_1">read</w>,
     <audio src="https://carfu.com/audio/carfu-welcome.mp3" />
     <break time="3s" />
</speak>



Sample JSON with images in card
	{
  "version": "1.0",
  "response": {
    "outputSpeech": {"type":"PlainText","text":"Your Car-Fu car is on the way!"},
    "card": {
      "type": "Standard",
      "title": "Ordering a Car",
      "text": "Your ride is on the way to 123 Main Street!\nEstimated cost for this ride: $25",
      "image": {
        "smallImageUrl": "https://carfu.com/resources/card-images/race-car-small.png",
        "largeImageUrl": "https://carfu.com/resources/card-images/race-car-large.png"
      }
    }
  }
}

*/
function alexaInit($logid=0){
	global $alexa;
	if(isset($_REQUEST['debug_init']) && isNum($_REQUEST['debug_init'])){
		$logid=$_REQUEST['debug_init'];
	}
	if($logid>0){
		$rec=getDBRecord(array('-table'=>'alexa_log','_id'=>$logid));
		$postdata=$rec['request'];
  		$alexa=json_decode($postdata,1);
		$headers=json_decode($rec['request_header'],1);
	}
	else{
		$postdata = file_get_contents("php://input");
		$alexa=json_decode($postdata,1);
		$headers=getallheaders();
	}
	if(!isset($alexa['session']['application']['applicationId'])){
		if(!strlen($postdata)){$postdata='[]';}
		$alexa['logid']=addDBRecord(array('-table'=>'alexa_log','request'=>$postdata,'appid'=>'MISSING','status'=>'fail'));
		$ok=alexaResponse("Invalid application ID");
    	return false;
	}
	//echo "Debug".printValue($alexa);exit;
	$alexa['userid']=$alexa['session']['user']['userId'];
	//set username based on userid
	//$alexa['username']=alexaGetUsername();
	//set appid
	$alexa['appid']=$alexa['session']['application']['applicationId'];
	//set skill based on appid
	$alexa['skill']=alexaGetSkill();
	$alexa['timestamp']=strtotime($alexa['request']['timestamp']);
	$alexa['intent']=$alexa['request']['intent']['name'];
	$alexa['swim']=array();
	//fetch Header

	$addopts=array(
		'-table'	=> 'alexa_log',
		'request'	=> $postdata,
		'appid'		=> $alexa['appid'],
		'userid'	=> $alexa['userid'],
		'timestamp'	=> $alexa['timestamp'],
		'intent'	=> $alexa['intent'],
		'skill'		=> $alexa['skill'],
		'status'	=> 'fail',
		'request_header'	=> json_encode($headers),
		'request_url'		=> $_SERVER['HTTP_REFERER'],
		'secure_check'		=> 'passed'
	);
	$alexa['logid']=addDBRecord($addopts);
	$addopts=array('secure_check'=>'passed');
	if(isset($alexa['session']['user']['accessToken'])){
		$ajson=alexaGetAmazonProfile($alexa['session']['user']['accessToken']);
		$ama=json_decode($ajson,1);
		if(!isset($ama['user_id'])){
			$ok=alexaResponse("I was unable to retrieve your Amazon Profile. Please enable this skill in our alexa app.");
		}
    	$profile=getDBRecord(array(
			'-table'=>'clientdata_profiles',
			'account'=>$ama['user_id']
		));
		$ajson='';
		if(!isset($profile['_id'])){
			$copts=array(
				'-table'=>'clientdata_profiles',
				'jdoc'=>$ajson
			);
			if(isset($ama['postal_code'])){
            	$zone=alexaGetTimeZone($ama['postal_code']);
            	$zarr=json_decode($zone,1);
            	foreach($zarr as $zk=>$zv){
                	$ama[$zk]=$zv;
                	$alexa['profile'][$zk]=$zv;
				}
				$copts['jdoc']=json_encode($ama);
			}
			$ajson=$copts['jdoc'];
			$addopts['profile_id']=addDBRecord($copts);
			$profile=getDBRecord(array(
				'-table'=>'clientdata_profiles',
				'_id'=>$addopts['profile_id']
			));
		}
  		else{
			$addopts['profile_id']=$profile['_id'];
			$ajson=$profile['jdoc'];
			if($ama['postal_code'] != $profile['postal_code']){
				$copts=array(
					'-table'=>'clientdata_profiles',
					'-where'=>"_id={$profile['_id']}",
					'jdoc'=>$ajson
				);
				if(isset($ama['postal_code'])){
	            	$zone=alexaGetTimeZone($ama['postal_code']);
	            	$zarr=json_decode($zone,1);
	            	foreach($zarr as $zk=>$zv){
	                	$ama[$zk]=$zv;
					}
					$copts['jdoc']=json_encode($ama);
				}
				$ok=editDBRecord($copts);
				$ajson=$copts['jdoc'];
			}
		}
		$alexa['user']=getDBRecord(array('-table'=>'_users','profile_id'=>$addopts['profile_id']));
		//confirm the user exists
		if(!isset($alexa['user']['_id'])){
			$cid=addDBRecord(array(
				'-table'	=>'clients',
				'name'		=>$profile['name'],
				'active'	=>1,
				'apiseed'	=> generatePassword(10,4)
			));
			list($first,$last)=preg_split('/\ +/',$profile['name'],2);
        	$uid=addDBRecord(array(
				'-table'=>'_users',
				'utype'=>2,
				'active'=>1,
				'username'=>$profile['email'],
				'email'=>$profile['email'],
				'password'=>generatePassword(10,4),
				'firstname'=>$first,
				'lastname'=>$last,
				'profile_id'=>$profile['_id'],
				'client_id'=>$cid
			));
			$alexa['user']=getDBRecord(array('-table'=>'_users','profile_id'=>$addopts['profile_id']));
		}
		$arr=json_decode($ajson,1);
		foreach($arr as $k=>$v){
        	$alexa['profile'][$k]=$v;
		}
	}
	/* verify the actual signature */
	// fetch public key from certificate and ready it
/* 	$fp = fopen($headers['Signaturecertchainurl'], "r");
	$pem = fread($fp, 8192);
	fclose($fp); */
	if(isset($headers['SignatureCertChainUrl']) || !isset($headers['Signaturecertchainurl'])){
		$headers['Signaturecertchainurl']=$headers['SignatureCertChainUrl'];
	}
	if(!count($headers)){
		$addopts['secure_check']='headers';
	}
	elseif(!isset($headers['Signaturecertchainurl']) || !strlen($headers['Signaturecertchainurl'])){
    	$addopts['secure_check']='chainurl';
	}
	elseif(!isset($headers['Signature']) || !strlen($headers['Signature'])){
    	$addopts['secure_check']='signature';
	}
	else{
		$shapem=sha1($headers['Signaturecertchainurl']);
		$shapem_file="/var/www/vhosts/devmavin.com/certs/alexa_{$shapem}.pem";
		if(!is_file($shapem_file)){
			$pem=file_get_contents($headers['Signaturecertchainurl']);
			file_put_contents($shapem_file,$pem);
		}
		else{
	    	$pem=file_get_contents($shapem_file);
		}
		// Validate certificate chain and signature
		$parsedCertificate = openssl_x509_parse($pem);
		//$addopts['debug']=printValue($parsedCertificate);
	    $ssl_check = openssl_verify( $postdata,base64_decode($_SERVER['HTTP_SIGNATURE']),$pem );
	    if ($ssl_check != 1) {
			$addopts['secure_check']='openssl';
	    }
	    else{
	    	// Parse certificate for validations below
	    	$parsedCertificate = openssl_x509_parse($pem);
	    	if (!$parsedCertificate) {
				$addopts['secure_check']='x509';
			}
			else{
	        	$validFrom = $parsedCertificate['validFrom_time_t'];
				$validTo = $parsedCertificate['validTo_time_t'];
				$time = time();
				if($time > $validTo || $time < $validFrom){
					$addopts['secure_check']='x509_time';
					$addopts['debug']='x509_time Failed'.printValue(array($time,$validFrom,$validTo));;
				}
			}
	    }
	}
	if($addopts['secure_check']=='passed'){
		//Verifying the Signature Certificate URL
		//https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/developing-an-alexa-skill-as-a-web-service#verifying-the-signature-certificate-url
		$sigurl=$headers['Signaturecertchainurl'];
		//normalize url and do a secure_check
		$sigurl=preg_replace('/\.\.\//','',$sigurl);
		$sigurl=strtolower($sigurl);
		/*
			Next, determine whether the URL meets each of the following criteria:
				The protocol is equal to https (case insensitive).
	    		The hostname is equal to s3.amazonaws.com (case insensitive).
	    		The path starts with /echo.api/ (case sensitive).
	    		If a port is defined in the URL, the port is equal to 443.
		*/
		$p=parse_url($sigurl);
		$timestamp=strtotime($alexa['request']['timestamp']);
		$allowx=time()-150;
		$allowy=time()+150;
		if(!isset($p['scheme']) || $p['scheme'] != 'https'){$addopts['secure_check']='https';}
		elseif(!isset($p['host']) || $p['host'] != 's3.amazonaws.com'){$addopts['secure_check']='host';}
		elseif(!isset($p['path']) || !stringBeginsWith($p['path'],'/echo.api/')){$addopts['secure_check']='path';}
		elseif(isset($p['port']) && $p['port'] != 443){$addopts['secure_check']='port';}
		//confirm timestamp is fresh - no less than 150 seconds old
		elseif($timestamp < $allowx || $timestamp > $allowy){$addopts['secure_check']='time';}
	}
	if($addopts['secure_check']!='passed'){
		//echo "here";exit;
		header('HTTP/1.1 400: BAD REQUEST', true, 400);
		alexaUpdateLog($addopts);
    	exit;
	}
	alexaUpdateLog($addopts);
	$editopts=array(
		'-table'=>'alexa_log',
		'-where'=>"_id={$alexa['logid']}"
	);
	$sticky=alexaGetUserVar('sticky');
	$len=strlen($sticky);
	if($len>0){
    	alexaSetUserVar('sticky','');
    	alexaSetUserVar('sticky_old',$sticky);
    	$json=json_decode($sticky,true);
    	//alexaSetUserVar('sticky_json',printValue($json));
    	foreach($json as $key=>$slot){
			if(!isset($slot['value'])){continue;}
        	if(!isset($alexa['request']['intent']['slots'][$key]['value'])){
            	$alexa['request']['intent']['slots'][$key]=$slot;
            	//alexaResponse("set {$key} to {$slot['value']}");
			}
		}
	}
	//parse intent slots
	if(is_array($alexa['request']['intent']['slots'])){
		$slots=array();
		foreach($alexa['request']['intent']['slots'] as $slot){
			$slotname=strtolower($slot['name']);
			if(!isset($slot['value'])){continue;}
			if(!strlen($slot['value'])){continue;}
			$slots[$slot['name']]=$slot['value'];
			$alexa['swim'][$slotname]=$slot['value'];
		}
		//use the last slots if no report was specified
		if(count($alexa['request']['intent']['slots']) && !isset($alexa['request']['intent']['slots']['Report']['value']) && !isset($alexa['request']['intent']['slots']['Action']['value'])){
			$report=alexaGetUserVar('report');
			if(strlen($report)){
				$alexa['request']['intent']['slots']['Report']['value']=$report;
				$alexa['swim']['report']=$report;
			}
		}
		//check for postcode of 4 digits and make it a date year value if we do not have a date value
		if(isset($alexa['request']['intent']['slots']['Postcode']['value']) && strlen($alexa['request']['intent']['slots']['Postcode']['value'])==4){
	        $alexa['request']['intent']['slots']['Date']['value']=$alexa['request']['intent']['slots']['Postcode']['value'];
	        unset($alexa['request']['intent']['slots']['Postcode']);
	        unset($alexa['swim']['postcode']);
		}
		//if todate without fromdate then make todate date
		if(isset($alexa['request']['intent']['slots']['ToDate']['value']) && !isset($alexa['request']['intent']['slots']['FromDate']['value'])){
			$alexa['request']['intent']['slots']['Date']=$alexa['request']['intent']['slots']['ToDate'];
			unset($alexa['request']['intent']['slots']['ToDate']);
		}
		//if historic is in the prefix, remove the Dates
		if(isset($alexa['request']['intent']['slots']['Prefix']['value']) && stringContains($alexa['request']['intent']['slots']['Prefix']['value'],'historic')){
			unset($alexa['request']['intent']['slots']['Date']);
			unset($slots['date']);
			$alexa['request']['intent']['slots']['ToDate']['value']=$alexa['swim']['to_date']=date('Y-m-d');
			$alexa['request']['intent']['slots']['FromDate']['value']=$alexa['swim']['from_date']=date('Y-m-d',0);
			//alexaSetUserVar('historic',printValue($alexa['request']['intent']['slots']));
		}
		//lets fix a few common slots mistakes
		if(!strlen($alexa['request']['intent']['slots']['Date']['value'])){
			//check for city or state as a month
			$months=array('january'=>'01','february'=>'02','march'=>'03','april'=>'04','may'=>'05','june'=>'06','july'=>'07','august'=>'08','september'=>'09','october'=>'10','november'=>'11','december'=>'12');
			$ch=strtolower($alexa['request']['intent']['slots']['City']);
			if(isset($months[$ch])){
				$year=date('Y');
				$alexa['request']['intent']['slots']['Date']['value']="{$year}-{$months[$ch]}";
				unset($alexa['request']['intent']['slots']['City']);
			}
			$ch=strtolower($alexa['request']['intent']['slots']['State']);
			if(isset($months[$ch])){
				$year=date('Y');
				$alexa['request']['intent']['slots']['Date']['value']="{$year}-{$months[$ch]}";
				unset($alexa['request']['intent']['slots']['State']);
			}
		}
		//check postcode
		if(strlen($alexa['request']['intent']['slots']['Postcode']['value']) && $alexa['request']['intent']['slots']['Postcode']['value']=='?'){
			$response="Can you repeat that zipcode?";
			$editopts['speech']=$response;
			$editopts['query']='n/a';
			$editopts['slots']=json_encode($slots);
			$ok=editDBRecord($editopts);
			alexaSetUserVar('sticky',json_encode($alexa['request']['intent']['slots']));
            $params=array('listen'=>1,'reprompt'=>'what report would you like?');
    		$ok=alexaResponse($response,$params);
		}
		//check city
		if(strlen($alexa['request']['intent']['slots']['City']['value']) && !strlen($alexa['request']['intent']['slots']['State']['value'])){
			$city=$alexa['request']['intent']['slots']['City']['value'];
			//if the city is may without any date slots then change it to a date
			if(strtolower($city)=='may' 
				&& !isset($alexa['request']['intent']['slots']['Date']['value'])
				&& !isset($alexa['request']['intent']['slots']['ToDate']['value'])
				&& !isset($alexa['request']['intent']['slots']['FromDate']['value'])
			){
				$alexa['request']['intent']['slots']['Date']['value']=date('Y-05');
				unset($alexa['request']['intent']['slots']['City']);
				unset($alexa['swim']['city']);
			}
			else{
				$query="select count(*) rcnt,city,state from cities where city='{$city}' group by city,state";
	        	$recs=getDBRecords($query);
	        	if(!is_array($recs) || count($recs)==0){
					$response="I don't know of a city called '{$city}'. Please restate the city.";
					$editopts['speech']=$response;
					$editopts['query']='n/a';
					$editopts['slots']=json_encode($slots);
					$ok=editDBRecord($editopts);
	            	$params=array('listen'=>1,'reprompt'=>'what report would you like?');
	            	alexaSetUserVar('sticky',json_encode($alexa['request']['intent']['slots']));
	    			$ok=alexaResponse($response,$params);
				}
				elseif(count($recs)>1){
					//have they already told me what state for this city?
					$cnt=count($recs);
					$response="There are {$cnt} states with '{$city}' as a city. Specify the city and state.";
					$editopts['speech']=$response;
					$editopts['query']='n/a';
					$editopts['slots']=json_encode($slots);
					$ok=editDBRecord($editopts);
					alexaSetUserVar('sticky',json_encode($alexa['request']['intent']['slots']));
	            	$params=array('listen'=>1,'reprompt'=>'what report would you like?');
	    			$ok=alexaResponse($response,$params);
				}
				else{
					$alexa['request']['intent']['slots']['State']['value']=$rec['state'];
	            	$alexa['request']['intent']['slots']['City']['value']=$rec['city'];
				}
			}
			//else{$alexa['request']['intent']['slots']['invalidCity']['value']=1;}
		}
		//check state
		if(strlen($alexa['request']['intent']['slots']['State']['value'])){
			$state=$alexa['request']['intent']['slots']['State']['value'];
        	$rec=getDBRecord(array(
				'-table'=>'states',
				'-where'=>"country='US' and (code='{$state}' or name='{$state}')",
				'-fields'=>'_id,code,country,name'
			));
			if(isset($rec['_id'])){
				//$alexa['request']['intent']['slots']['oriState']['value']=$state;
            	$alexa['request']['intent']['slots']['State']['value']=$rec['name'];
			}
			//else{$alexa['request']['intent']['slots']['invalidState']['value']=1;}
		}
		//set this state
		if(strlen($alexa['request']['intent']['slots']['City']['value']) && strlen($alexa['request']['intent']['slots']['State']['value'])){
			$city=$alexa['request']['intent']['slots']['City']['value'];
			$state=$alexa['request']['intent']['slots']['State']['value'];
        	$srec=getDBRecord(array(
				'-table'=>'states',
				'-where'=>"country='US' and (code='{$state}' or name='{$state}')",
				'-fields'=>'_id,code,country,name'
			));
			if(isset($srec['_id'])){
				$crec=getDBRecord(array(
					'-table'=>'cities',
					'-where'=>"country='US' and state='{$srec['code']}' and city='{$city}'",
					'-fields'=>'_id,state,country,city'
				));
				if(!isset($crec['_id'])){
					$response="I can't find a city called '{$city}' in {$srec['name']}. ";
					$editopts['speech']=$response;
					$editopts['query']='n/a';
					$editopts['slots']=json_encode($slots);
					$ok=editDBRecord($editopts);
					alexaSetUserVar('report','');
            		$params=array('listen'=>1,'reprompt'=>'what report would you like?');
    				$ok=alexaResponse($response,$params);
				}
			}
		}
		//Group Quarters
		if(isset($alexa['request']['intent']['slots']['Group']['value'])){
			if(isset($alexa['request']['intent']['slots']['Date']['value'])
				&& strlen($alexa['request']['intent']['slots']['Date']['value'])==4){
				$year=$alexa['request']['intent']['slots']['Date']['value'];
			}
			else{$year=date('Y');}
			$newval='';
			switch(strtolower($alexa['request']['intent']['slots']['Group']['value'])){
				case 'today':
					$newval="today";
					break;
				case 'yesterday':
					$newval="yesterday";
					break;
				case 'last week':
					$w = date('W',strtotime('last week'));
					$newval="{$year}-W{$w}";
				break;
				case 'last month':
					$n = date('n');
					if($n==12){$year=$year-1;}
					$newval="{$year}-{$n}";
				break;
				case 'last january':
					$cm=date('n');
					if($cm > 1){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-01";
				break;
				case 'last february':
					$cm=date('n');
					if($cm > 2){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-02";
				break;
				case 'last march':
					$cm=date('n');
					if($cm > 3){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-03";
				break;
				case 'last april':
					$cm=date('n');
					if($cm > 4){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-04";
				break;
				case 'last may':
					$cm=date('n');
					if($cm > 5){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-05";
				break;
				case 'last june':
					$cm=date('n');
					if($cm > 6){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-06";
				break;
				case 'last july':
					$cm=date('n');
					if($cm > 7){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-07";
				break;
				case 'last august':
					$cm=date('n');
					if($cm > 8){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-08";
				break;
				case 'last september':
					$cm=date('n');
					if($cm > 9){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-09";
				break;
				case 'last october':
					$cm=date('n');
					if($cm > 10){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-10";
				break;
				case 'last november':
					$cm=date('n');
					if($cm > 11){$y=date('Y');}
					else{$y=date('Y')-1;}
					$newval="{$year}-11";
				break;
				case 'last december':
					$cm=date('n');
					$y=date('Y')-1;
					$newval="{$year}-12";
				break;
				case 'this quarter':
					$n = date('n');
					switch($n){
          	case 1:
          	case 2:
          	case 3:
							$newval="{$year}-Q1";
							break;
						case 4:
          	case 5:
          	case 6:
							$newval="{$year}-Q2";
							break;
						case 7:
          	case 8:
          	case 9:
							$newval="{$year}-Q3";
							break;
						case 10:
            	case 11:
            	case 12:
							$newval="{$year}-Q4";
							break;
					}
					break;
				case 'last quarter':
					$n = date('n');
					switch($n){
          	case 1:
          	case 2:
          	case 3:
							$year=$year-1;
							$newval="{$year}-Q4";
							break;
						case 4:
          	case 5:
          	case 6:
							$newval="{$year}-Q1";
							break;
						case 7:
          	case 8:
          	case 9:
							$newval="{$year}-Q2";
							break;
						case 10:
          	case 11:
          	case 12:
							$newval="{$year}-Q3";
							break;
					}
					break;
				case 'last year':
					$newval=$year-1;
					break;
        case 'q1':
				case 'first quarter':
				case '1st quarter':
					$newval="{$year}-Q1";
					break;
        case 'q2':
				case 'second quarter':
				case '2nd quarter':
					$newval="{$year}-Q2";
					break;
        case 'q3':
				case 'third quarter':
				case '3rd quarter':
					$newval="{$year}-Q3";
					break;
        case 'q4':
				case 'fourth quarter':
				case '4th quarter':
					$newval="{$year}-Q4";
					break;
			}
			if(strlen($newval)){
				$alexa['request']['intent']['slots']['Date']['value']=$newval;
				unset($alexa['request']['intent']['slots']['Group']);
			}
		}
		foreach($alexa['request']['intent']['slots'] as $slot){
			$slotname=strtolower($slot['name']);
			if(!isset($slot['value'])){continue;}
			if(!strlen($slot['value'])){continue;}
			//check for between
			if(
				isset($alexa['request']['intent']['slots']['FromDate']['value'])
				&&
				isset($alexa['request']['intent']['slots']['ToDate']['value'])
				){
				$dates=alexaDateBetween($alexa['request']['intent']['slots']['FromDate']['value'],$alexa['request']['intent']['slots']['ToDate']['value']);
				foreach($dates as $k=>$v){
          if(!isset($alexa['swim'][$k])){$alexa['swim'][$k]=$v;}
				}
				unset($alexa['request']['intent']['slots']['FromDate']);
				unset($alexa['request']['intent']['slots']['ToDate']);
			}
      switch($slotname){
			case 'date':
				$dates=alexaDateValue($slot['value']);
				foreach($dates as $k=>$v){
          			if(!isset($alexa['swim'][$k])){$alexa['swim'][$k]=$v;}
				}
			break;
			case 'fromdate':
				//handle between
				$dates=alexaDateValue($slot['value']);
				foreach($dates as $k=>$v){
          			if(!isset($alexa['swim']['from_date'])){$alexa['swim']['from_date']=$v;}
				}
			break;
				case 'todate':
					$dates=alexaDateValue($slot['value']);
					foreach($dates as $k=>$v){
          				if(!isset($alexa['swim']['to_date'])){$alexa['swim']['to_date']=$v;}
					}
				break;
				case 'year':
        	//if(!isset($slot['value'])){$slot['value']=date('Y');}
        	$dates=alexaDateValue($slot['value']);
					foreach($dates as $k=>$v){
          				if(!isset($alexa['swim'][$k])){$alexa['swim'][$k]=$v;}
					}
				break;
				case 'report':
					if(preg_match('/^last (year|month)(.+)$/i',$slot['value'],$m)){
						$alexa['swim']['report_raw']=$slot['value'];
						$slot['value']=preg_replace('/^\'s\ /i','',$m[2]);
						switch(strtolower($m[1])){
            	case 'month':
            		//last month
            		$v=date('Y-m',strtotime('last month'));
            		$dates=alexaDateValue($v);
								foreach($dates as $k=>$v){
	              	if(!isset($alexa['swim'][$k])){$alexa['swim'][$k]=$v;}
								}
            		break;
            	case 'year':
            		//last year
            		$v=date('Y',strtotime('last year'));
            		$dates=alexaDateValue($v);
								foreach($dates as $k=>$v){
                	if(!isset($alexa['swim'][$k])){$alexa['swim'][$k]=$v;}
								}
              	break;
						}
					}
					$alexa['swim']['report']=$slot['value'];
				break;
				default:
					if(strlen($slot['value']) && $slot['value'] != 'null'){
						$key=strtolower($slot['name']);
						$alexa['swim'][$key]=$slot['value'];
					}
				break;
			}
        	$slots[$slot['name']]=$slot['value'];
		}
		$alexa['slots']=$slots;
		$editopts['slots']=json_encode($slots);
		//remove null swim values
		foreach($alexa['swim'] as $k=>$v){
        	if(!strlen($v) || strtolower($v)=='null' || preg_match('/^(prep|prefix)/i',$k)){unset($alexa['swim'][$k]);}
		}
		$editopts['swim']=json_encode($alexa['swim']);
		//determine possible fromDate and toDate
	}
	//get skill that this appid belongs to
	$recopts=array('-table'=>'_pages','appid'=>$alexa['appid'],'-fields'=>'name,_id');
	$rec=getDBRecord($recopts);
	if(isset($rec['_id'])){
    	$editopts['skill']=$rec['name'];
	}
	//get any user_vars
	$recs=getDBRecords(array(
		'-table'	=>'alexa_user_vars',
		'userid'	=> $alexa['userid'],
		'appid'		=> $alexa['appid']
	));
	if(is_array($recs)){
    	foreach($recs as $rec){
        	$alexa['user'][$rec['name']]=$rec['val'];
		}
	}
	//if more_info is set
	if(isset($alexa['user']['more_info_field'])){
		$alexa['swim']=json_decode($alexa['user']['more_info_last'],1);
		$key=$alexa['user']['more_info_field'];
		$val='';
    	foreach($alexa['request']['intent']['slots'] as $slot){
			if(!strlen($slot['value'])){continue;}
			$alexa['swim'][$key]=$slot['value'];
			$editopts['swim']=json_encode($alexa['swim']);
			break;
		}
		$ok=alexaSetUserVar('more_info_field','');
		$ok=alexaSetUserVar('more_info_last','');
	}
	//get user_profile record and user that this belongs to
	$rec=getDBRecord(array('-table'=>'clientdata_profiles','account'=>$alexa['userid']));
	if(isset($rec['_id'])){
    	$editopts['user_profile_id']=$rec['_id'];
    	$alexa['user']=getDBRecord(array('-table'=>'_users','profile_id'=>$rec['_id'],'-fields'=>'_id,firstname,lastname,email,client_id'));
    	if(isset($alexa['user']['_id'])){
			$editopts['user_id']=$alexa['user']['_id'];
		}
	}
	$ok=alexaUpdateLog($editopts);
	//If the secure_check failed, reject the request and do not proceed
	if($addopts['secure_check']!='passed'){
		header('HTTP/1.1 400: BAD REQUEST', true, 400);
    	exit;
	}
	//pass initialization
	$ok=alexaUpdateLog(array('status'=>'pass'));
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	return true;
}
function alexaGetSkill(){
	global $alexa;
	$rec=getDBRecord(array(
		'-table'	=>'_pages',
		'appid'		=> $alexa['appid'],
		'-fields'	=> '_id,name'
	));
	if(isset($rec['name'])){return $rec['name'];}
	return null;
}
function alexaGetUsername(){
	global $alexa;
	$rec=getDBRecord(array(
		'-table'	=>'_users',
		'-where'	=> "_id in (select user_id from user_profiles where profile_id='{$alexa['userid']}')",
		'-fields'	=> '_id,username'
	));
	if(isset($rec['username'])){return $rec['username'];}
	return null;
}
function alexaSetUserVar($name,$val='',$sticky=0){
	global $alexa;
	$rec=getDBRecord(array(
		'-table'	=>'alexa_user_vars',
		'userid'	=> $alexa['userid'],
		'appid'		=> $alexa['appid'],
		'name'		=> $name
	));
	if(isset($rec['_id'])){
    	if(!strlen($val)){
			//clear
			unset($alexa['user'][$name]);
        	$ok=delDBRecord(array(
				'-table'	=> 'alexa_user_vars',
				'-where'	=> "_id={$rec['_id']}"
			));
		}
		else{
			$alexa['user'][$name]=$val;
			$ok=editDBRecord(array(
				'-table'	=> 'alexa_user_vars',
				'-where'	=> "_id={$rec['_id']}",
				'val'		=> $val,
				'sticky'	=> $sticky
			));
		}
	}
	else{
		if(!strlen($val)){
			//clear
			unset($alexa['user'][$name]);
		}
		else{
			$alexa['user'][$name]=$val;
			$opts=array(
				'-table'	=> 'alexa_user_vars',
				'userid'	=> $alexa['userid'],
				'appid'		=> $alexa['appid'],
				'name'		=> $name,
				'val'		=> $val,
				'sticky'	=> $sticky
			);
			if(strlen($alexa['skill'])){$opts['skill']=$alexa['skill'];}
			if(strlen($alexa['username'])){$opts['username']=$alexa['username'];}
			$ok=addDBRecord($opts);
		}
	}
}
function alexaAppendUserVar($name,$val){
	global $alexa;
	$val=alexaGetUserVar($name)."\r\n".$val;
	return alexaSetUserVar($name,$val);
}
function alexaGetUserVar($name){
	global $alexa;
	if(isset($alexa['user'][$name])){return $alexa['user'][$name];}
	$rec=getDBRecord(array(
		'-table'	=>'alexa_user_vars',
		'userid'	=> $alexa['userid'],
		'appid'		=> $alexa['appid'],
		'name'		=> $name
	));
	if(isset($rec['_id'])){
		$alexa['user'][$name]=$rec['val'];
		return $alexa['user'][$name];
	}
	return null;
}
function alexaClearUserVars($sticky=0){
	global $alexa;
	$opts=array(
		'-table'	=> 'alexa_user_vars',
		'-where'	=> "userid='{$alexa['userid']}' and appid='{$alexa['appid']}' and sticky={$sticky}"
	);
	$ok=delDBRecord($opts);
}
function alexaEncode($str){
	//prefix with 5 random characters
	$chars = array_merge(range('A', 'Z'), range('a', 'z'),range(0,9));
	$indexes=array_rand($chars,5);
	$prefix='';
	foreach($indexes as $index){
    	$prefix.=$chars[$index];
	}
	return "{$prefix}".base64_encode($str);
}
function alexaDecode($str){
	//remove the first two chars
	$str=substr($str,5);
	//decode base64
	$str=base64_decode($str);
	return $str;
}
function alexaPingSwim($swim){
	$opts=array('report'=>'swim_ping');
	$json=json_encode($opts);
	$body=alexaEncode($json);
	$post=postXML($swim,$body);
	$code=alexaDecode($post['body']);
	if($code=='pong'){return true;}
	return false;
}
function alexaPairSwim($swim,$paircode){
	$opts=array('report'=>'swim_pair');
	$json=json_encode($opts);
	$body=alexaEncode($json);
	$post=postXML($swim,$body);
	$code=alexaDecode($post['body']);
	if($code==$paircode){return true;}
	return false;
}
function alexaPostSwim(){
	global $alexa;
	$json=json_encode($alexa['swim']);
	$body=alexaEncode($json);
	$begin=microtime(true);
	$post=postXML($alexa['swim_url'],$body);
	$end=microtime(true);
	$response_time=$end-$begin;
	$result=alexaDecode($post['body']);
	$ok=alexaUpdateLog(array('swim_response'=>$response,'swim_response_time'=>$response_time));
	return trim($result);
}
function alexaDateValue($str){
	$dates=array();
	$thisyear=date('Y');
	//$dates['str']=$str;
	if(!strlen($str)){return $dates;}
	/*
		Utterances that map to just a specific week (such as "this week" or "next week"),
		convert a date indicating the week number: 2015-W49
	*/
	if(preg_match('/^([0-9]{4,4})\-W([0-9]+)$/i',$str,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}W{$m[2]} -1 day"));
    	$dates['to_date']=date('Y-m-d',strtotime("{$m[1]}W{$m[2]} +5 day"));
    	$dates['date_map']='week';
	}
	/*
		Utterances for Today
	*/
	if(preg_match('/(today)/i',$str,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
			$dates['from_date']=date('Y-m-d',strtotime("today"));
			$dates['to_date']=date('Y-m-d',strtotime("today"));
			$dates['date_map']='today';
	}
	/*
		Utterances for Yesterday
	*/
	if(preg_match('/(yesterday)/i',$str,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
			$dates['from_date']=date('Y-m-d',strtotime("-1 days"));
			$dates['to_date']=date('Y-m-d',strtotime("-1 days"));
			$dates['date_map']='yesterday';
	}
	/*
		Utterances that map to just a specific week (such as "this quarter" or "next quarter"),
		convert a date indicating the quarter number: 2015-Q1
	*/
	elseif(preg_match('/^([0-9]{4,4})\-Q([0-9]{1,1})$/i',$str,$m)){
		$year=$m[1];
		$quarter = $m[2];
        switch($quarter){
            case 1:
                $dates['from_date'] = date('Y-m-d',strtotime("first day of January {$year}"));
				$dates['to_date'] = date('Y-m-d',strtotime("last day of March {$year}"));
			break;
			case 2:
                $dates['from_date'] = date('Y-m-d',strtotime("first day of April {$year}"));
				$dates['to_date'] = date('Y-m-d',strtotime("last day of June {$year}"));
			break;
			case 3:
                $dates['from_date'] = date('Y-m-d',strtotime("first day of July {$year}"));
				$dates['to_date'] = date('Y-m-d',strtotime("last day of September {$year}"));
			break;
			case 4:
                $dates['from_date'] = date('Y-m-d',strtotime("first day of October {$year}"));
				$dates['to_date'] = date('Y-m-d',strtotime("last day of December {$year}"));
			break;
		}
    	$dates['date_map']='quarter';
	}
	/*
		Utterances that map to the weekend for a specific week (such as "this weekend")
		convert to a date indicating the week number and weekend: 2015-W49-WE, 2016-W12-WE
	*/
	elseif(preg_match('/^([0-9]{4,4})\-W([0-9]+)\-WE$/i',$str,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}W{$m[2]} +5 day"));
    	$dates['to_date']=date('Y-m-d',strtotime("{$m[1]}W{$m[2]} +6 day"));
    	$dates['date_map']='weekend';
	}
	/*
		Utterances that map to a month, but not a specific day (such as "next month", or "december")
		convert to a date with just the year and month: 2015-12
	*/
	elseif(preg_match('/^([0-9]{4,4})\-([0-9]+)$/i',$str,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}-{$m[2]}-01"));
    	//t returns the number of days in the month of a given date
    	$dates['to_date']=date('Y-m-t',strtotime("{$m[1]}-{$m[2]}-01"));
    	$dates['date_map']='month';
	}
	elseif(preg_match('/^([0-9]{4,4})\-([0-9]{2,2})\-([0-9]{2,2})$/i',$str,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}-{$m[2]}-{$m[3]}"));
    	//t returns the number of days in the month of a given date
    	$dates['to_date']=date('Y-m-d',strtotime("{$m[1]}-{$m[2]}-{$m[3]}"));
    	$dates['date_map']='day';
	}
	/*
		Utterances that map to a year (such as "next year")
		convert to a date containing just the year: 2016.
	*/
	elseif(preg_match('/^([0-9]{4,4})$/i',$str,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}-01-01"));
    	//t returns the number of days in the month of a given date
    	$dates['to_date']=date('Y-m-t',strtotime("{$m[1]}-12-01"));
    	$dates['date_map']='year';
	}
	/*
		Utterances that map to a decade
		convert to a date indicating the decade: 201X
	*/
	elseif(preg_match('/^([0-9]{3,3})X$/i',$str,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}0-01-01"));
    	//t returns the number of days in the month of a given date
    	$dates['to_date']=date('Y-m-t',strtotime("{$m[1]}9-12-01"));
    	$dates['date_map']='decade';
	}
	else{
		$dates['date']=date('Y-m-d',strtotime($str));
		$dates['date_map']='date';
	}
	//Change future dates to be in the past
	$future=0;
	foreach($dates as $k=>$v){
        if(strtotime($v) > strtotime(date('Y-m-d'))){
            $future++;
            break;
		}
	}
	if(count($dates)==$future){
		foreach($dates as $k=>$v){
	        $y=date('Y',strtotime($v));
	           $y-=1;
			$dates[$k]=date("{$y}-m-d",strtotime($v));
		}
	}
	return $dates;
}
function alexaDateBetween($fromstr,$tostr){
	$dates=array();
	$thisyear=date('Y');
	/*
		Utterances for Today
	*/
	if(!isset($dates['from_date']) && preg_match('/(today)/i',$fromstr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("today"));
    	$dates['date_map']='today';
	}
	if(!isset($dates['to_date']) && preg_match('/(today)/i',$tostr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['to_date']=date('Y-m-d',strtotime("today"));
    	$dates['date_map']='today';
	}
	/*
		Utterances for Yesterday
	*/
	if(!isset($dates['from_date']) && preg_match('/(yesterday)/i',$fromstr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("-1 days"));
    	$dates['date_map']='yesterday';
	}
	if(!isset($dates['to_date']) && preg_match('/(yesterday)/i',$tostr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['to_date']=date('Y-m-d',strtotime("-1 days"));
    	$dates['date_map']='yesterday';
	}
	/*
		Utterances that map to just a specific week (such as "this week" or "next week"),
		convert a date indicating the week number: 2015-W49
	*/
	if(!isset($dates['from_date']) && preg_match('/^([0-9]{4,4})\-W([0-9]+)$/i',$fromstr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}W{$m[2]} -1 day"));
    	$dates['date_map']='bweek';
	}
	if(!isset($dates['to_date']) && preg_match('/^([0-9]{4,4})\-W([0-9]+)$/i',$tostr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['to_date']=date('Y-m-d',strtotime("{$m[1]}W{$m[2]} +6 day"));
    	$dates['date_map']='bweek';
	}
	/*
		Utterances that map to the weekend for a specific week (such as "this weekend")
		convert to a date indicating the week number and weekend: 2015-W49-WE, 2016-W12-WE
	*/
	if(!isset($dates['from_date']) && preg_match('/^([0-9]{4,4})\-W([0-9]+)\-WE$/i',$fromstr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}W{$m[2]} +5 day"));
    	$dates['date_map']='weekend';
	}
	if(!isset($dates['to_date']) && preg_match('/^([0-9]{4,4})\-W([0-9]+)\-WE$/i',$tostr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['to_date']=date('Y-m-d',strtotime("{$m[1]}W{$m[2]} +6 day"));
    	$dates['date_map']='weekend';
	}
	/*
		Utterances that map to a month, but not a specific day (such as "next month", or "december")
		convert to a date with just the year and month: 2015-12
	*/
	if(!isset($dates['from_date']) && preg_match('/^([0-9]{4,4})\-([0-9]+)$/i',$fromstr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}-{$m[2]}-01"));
    	//t returns the number of days in the month of a given date
    	$dates['date_map']='month';
	}
	if(!isset($dates['to_date']) && preg_match('/^([0-9]{4,4})\-([0-9]+)$/i',$tostr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	//t returns the number of days in the month of a given date
    	$dates['to_date']=date('Y-m-t',strtotime("{$m[1]}-{$m[2]}-01"));
    	$dates['date_map']='month';
	}
	if(!isset($dates['from_date']) && preg_match('/^([0-9]{4,4})\-([0-9]{2,2})\-([0-9]{2,2})$/i',$fromstr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}-{$m[2]}-{$m[3]}"));
    	$dates['date_map']='day';
	}
	if(!isset($dates['to_date']) && preg_match('/^([0-9]{4,4})\-([0-9]{2,2})\-([0-9]{2,2})$/i',$tostr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	//t returns the number of days in the month of a given date
    	$dates['to_date']=date('Y-m-d',strtotime("{$m[1]}-{$m[2]}-{$m[3]}"));
    	$dates['date_map']='day';
	}
	/*
		Utterances that map to a year (such as "next year")
		convert to a date containing just the year: 2016.
	*/
	if(!isset($dates['from_date']) && preg_match('/^([0-9]{4,4})$/i',$fromstr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}-01-01"));
    	$dates['date_map']='year';
	}
	if(!isset($dates['to_date']) && preg_match('/^([0-9]{4,4})$/i',$tostr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	//t returns the number of days in the month of a given date
    	$dates['to_date']=date('Y-m-t',strtotime("{$m[1]}-12-01"));
    	$dates['date_map']='year';
	}
	/*
		Utterances that map to a decade
		convert to a date indicating the decade: 201X
	*/
	if(!isset($dates['from_date']) && preg_match('/^([0-9]{3,3})X$/i',$fromstr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	$dates['from_date']=date('Y-m-d',strtotime("{$m[1]}0-01-01"));
    	$dates['date_map']='decade';
	}
	if(!isset($dates['to_date']) && preg_match('/^([0-9]{3,3})X$/i',$tostr,$m)){
		if($m[1]>$thisyear){$m[1]=$thisyear;}
    	//t returns the number of days in the month of a given date
    	$dates['to_date']=date('Y-m-t',strtotime("{$m[1]}9-12-01"));
    	$dates['date_map']='decade';
	}
	if(!isset($dates['from_date'])){
		$dates['from_date']=date('Y-m-d',strtotime($fromstr));
		$dates['date_map']='date';
	}
	if(!isset($dates['to_date'])){
		$dates['to_date']=date('Y-m-d',strtotime($tostr));
		$dates['date_map']='date';
	}
	return $dates;
}
function alexaGetLastSlot(){
	global $alexa;
	$opts=array(
		'-table'=>'alexa_log',
		'-where'=>"userid='{$alexa['userid']}' and query is not null",
		'-order'=>'_cdate desc',
		'-fields'=>'_id,slots',
		'-limit'=>10
	);
	if(isset($alexa['logid']) && strlen($alexa['logid'])){
    	$opts['-where'].= " and  _id != {$alexa['logid']}";
	}
	//alexaSetUserVar('alexaGetLastSlot',printValue($opts));
	$recs=getDBRecords($opts);
	//alexaSetUserVar('alexaGetLastSlot',printValue($opts).printValue($recs));
	foreach($recs as $rec){
		$slots=json_decode($rec['slots'],true);

		if(isset($slots['Report'])){
			if(isset($slots['Date'])){
            	unset($slots['ToDate']);
            	unset($slots['FromDate']);
			}
			return $slots;
		}
	}
	return array();
}
function alexaGetLastResponse(){
	global $alexa;
	$opts=array(
		'-table'=>'alexa_log',
		'-where'=>"userid='{$alexa['userid']}' and  _id != {$alexa['logid']}",
		'-order'=>'_cdate desc',
		'-limit'=>10
	);
	//setFileContents('/var/www/vhosts/devmavin.com/wasql_stage/alexa.txt',printValue($alexa).printValue($opts));

	$recs=getDBRecords($opts);
	foreach($recs as $rec){
		$slots=json_decode($rec['slots'],true);
		if(isset($slots['Action'])){continue;}
    	if(!strlen($rec['response'])){continue;}
    	break;
	}
	//setFileContents('/var/www/vhosts/devmavin.com/wasql_stage/alexa.txt',printValue($alexa).printValue($opts).printValue($rec));

	$json=json_decode($rec['response'],1);
	if(isset($json['response']['outputSpeech']['text'])){
    	return $json['response']['outputSpeech']['text'];
	}
	if(isset($json['response']['outputSpeech']['ssml'])){
    	return $json['response']['outputSpeech']['ssml'];
	}

	return 'I do not remember';
}
function alexaGetAmazonProfile($accessToken){
	//{"user_id":"amzn1.account.AGGJ6C3PLHHAKLAU4YVR7LB6TPJQ","name":"Steve Lloyd","postal_code":"84062-9253","email":"steve.lloyd@gmail.com"}
	$url='https://api.amazon.com/user/profile';
	$post=postURL($url,array(
		'-method'=>'GET',
		'access_token'=>$accessToken,
		'-json'=>1,
		'-ssl'=>1
	));
	return $post['body'];
}
function alexaGetTimeZone($zip){
	/*
		https://maps.googleapis.com/maps/api/timezone/json?location=38.908133,-77.047119&timestamp=1458000000&key=YOUR_API_KEY
		{
		   "dstOffset" : 3600,
		   "rawOffset" : -18000,
		   "status" : "OK",
		   "timeZoneId" : "America/New_York",
		   "timeZoneName" : "Eastern Daylight Time"
		}
	*/
	$city=alexaGetCityFromZip($zip);
	$location="{$city['latitude']},{$city['longitude']}";
	$apikey='AIzaSyDhZkPoYc17DcnDEpjJCGR54CHBzXGviDE';
	$url='https://maps.googleapis.com/maps/api/timezone/json';
	$post=postURL($url,array(
		'-method'=>'GET',
		'location'=>$location,
		'timestamp'=>time(),
		'key'=>$apikey,
		'-json'=>1,
		'-ssl'=>1
	));
	$jarr=json_decode($post['body'],1);
	$jarr['location']=$location;
	//timezonecode
	$jarr['timeZoneCode']=alexaGetTimezoneCode($jarr['timeZoneName']);
	foreach($city as $k=>$v){
		$jarr[$k]=$v;
	}
	return json_encode($jarr);
}
function alexaGetCityFromZip($zip){
	$zip=substr($zip,0,5);
	$rec=getDBRecord(array(
		'-table'=>'cities',
		'zipcode'=>$zip,
		'-fields'=>'city,state,county,country,longitude,latitude'
	));
	return $rec;
}
function alexaGetTimezoneCode($timezone){
	switch(strtoupper($timezone)){
		case 'ATLANTIC STANDARD TIME':return 'AST';break;
		case 'EASTERN STANDARD TIME':return 'EST';break;
		case 'EASTERN DAYLIGHT TIME':return 'EDT';break;
		case 'CENTRAL STANDARD TIME':return 'CST';break;
		case 'CENTRAL DAYLIGHT TIME':return 'CDT';break;
		case 'MOUNTAIN STANDARD TIME':return 'MST';break;
		case 'MOUNTAIN DAYLIGHT TIME':return 'MDT';break;
		case 'PACIFIC STANDARD TIME':return 'PST';break;
		case 'PACIFIC DAYLIGHT TIME':return 'PDT';break;
		case 'ALASKA TIME':return 'AKST';break;
		case 'ALASKA DAYLIGHT TIME':return 'AKDT';break;
		case 'HAWAII STANDARD TIME':return 'HST';break;
		case 'HAWAII-ALEUTIAN STANDARD TIME':return 'HAST';break;
		case 'HAWAII-ALEUTIAN DAYLIGHT TIME':return 'HADT';break;
		case 'SAMOA STANDARD TIME':return 'SST';break;
		case 'SAMOA DAYLIGHT TIME':return 'SDT';break;
		case 'CHAMORRO STANDARD TIME':return 'CHST';break;
	}
	return $timezone;
}
function alexaResponse($response,$params=array()){
	global $alexa;
	if(!isset($params['type'])){$params['type']='PlainText';}
	if(!strlen($response)){
    	$response='I got no response';
	}
	//response
	$json_array=array(
		'version'=>$alexa['version'],
		'response'=>array(
			'outputSpeech'=>array(
				'type'	=> 'PlainText',
				'text'	=> $response
			),
		)
	);
	if(isXml($response)){
		$json_array['response']['outputSpeech']['type']='SSML';
		$json_array['response']['outputSpeech']['ssml']=stripslashes($response);
		unset($json_array['response']['outputSpeech']['text']);
	}
	//card
	if(isset($params['card'])){
		if($params['type']!='LinkAccount'){$params['type']='Standard';}
		if(isset($params['card']['image_small']) && isset($params['card']['image_large'])){
			$json_array['response']['card']=array(
				'type'	=> $params['type'],
				'title'	=> $params['card']['title'],
				'text'=>removeHtml($params['card']['content']),
				'image'=>array(
					'smallImageUrl'	=> $params['card']['image_small'],
					'largeImageUrl' => $params['card']['image_large']
				)
			);
		}
		else{
        	$json_array['response']['card']=array(
				'type'	=> $params['type'],
				'title'	=> $params['card']['title'],
				'content'=>removeHtml($params['card']['content'])
			);
		}

	}
	//reprompt
	if(isset($params['reprompt'])){
    	$json_array['response']['reprompt']=array(
			'outputSpeech'=>array(
				'type'	=> 'PlainText',
				'text'	=> $params['reprompt']
			)
		);
		if(isXml($params['reprompt'])){
			$json_array['response']['reprompt']['outputSpeech']['type']='SSML';
			$json_array['response']['reprompt']['outputSpeech']['ssml']=$params['reprompt'];
			unset($json_array['response']['reprompt']['outputSpeech']['text']);
		}
	}
	//shouldSessionEnd
	if($params['listen']){
    	$json_array['response']['shouldEndSession']=false;
	}
	else{
    	$json_array['response']['shouldEndSession']=true;
	}
/* 	$json_array['sessionAttributes']=array(
		'lastResponse'=>$response
	); */
	$json=json_encode($json_array);
	//alexaAppendUserVar('debug',"Response:{$json}".count($recs));
	$ok=alexaUpdateLog(array('response'=>$json));
	echo $json;
	exit;
	return $response;
}
function alexaUpdateLog($params){
	global $alexa;
	if(!isset($alexa['logid'])){return false;}
	$params['-table']='alexa_log';
	$params['-where']="_id={$alexa['logid']}";
	$ok=editDBRecord($params);
}
?>
