<?php
/*
	Name: Report Master
	AppId: amzn1.echo-sdk-ams.app.c37ff67c-05a2-4834-90a8-e49741379d6f
	Created: by slloyd on 2016-01-29
	
*/
/* $json=<<<ENDOFJSON
{"session": {"new": false, "sessionId": "amzn1.echo-api.session.1b12ba25-9392-4dce-93b8-57987ac2afd2", "attributes": {"lastResponse": "What volume would you like to start with?"}, "user": {"userId": "amzn1.echo-sdk-account.AERH3O3WTB6JL3I54HAZD54ZOK2OZS43DAZ3T2YZSAMNFSPJKFL4G"}, "application": {"applicationId": "amzn1.echo-sdk-ams.app.f9409108-1b9e-48cf-bbd7-57df380d8103"}}, "version": "1.0", "request": {"timestamp": "2016-01-26T14:32:02Z", "reason": "EXCEEDED_MAX_REPROMPTS", "type": "SessionEndedRequest", "requestId": "amzn1.echo-api.request.cb5ce855-25cb-49b5-9913-a3e34b1bde96"}}
ENDOFJSON;
$json_array=json_decode($json,1);
echo printValue($json_array);exit; */
loadDBFunctions('functions_alexa');
//initialize
global $alexa;
if(!alexaInit()){
	echo "Invalid Request";
	exit;
}
$session=essentialOilCompanionGetSession();
if($alexa['request']['type']=='SessionEndedRequest'){
	//session ended
	$ok=alexaResponse("Goodbye");
	exit;
}
switch(strtolower($alexa['intent'])){
	case 'action':
		switch(strtolower(trim($alexa['value']))){
		    case 'never mind':
		    case 'stop':
		    case 'done':
		    	$response='later.';
		    	$session=essentialOilCompanionUpdateSession($session,array(
					'intent'=>$alexa['intent'],
					'value'=>$alexa['value'],
					'response'=>$response
				));
		        $ok=alexaResponse($response);
		        exit;
		    break;
		    case 'thanks':
		    case 'thank you':
		        $response='You are welcome.';
		    	$session=essentialOilCompanionUpdateSession($session,array(
					'intent'=>$alexa['intent'],
					'value'=>$alexa['value'],
					'response'=>$response
				));
		        $ok=alexaResponse($response);
		        exit;
		    break;
		    default:
		    	$response= "Not sure what you mean. How may I help?";
		    	$session=essentialOilCompanionUpdateSession($session,array(
					'intent'=>$alexa['intent'],
					'value'=>$alexa['value'],
					'response'=>$response
				));
				$ok=alexaResponse($response,array(
					'listen'		=> true,
					'reprompt'		=> "How may I help?"
				));
				exit;
		    break;
		}
	break;
	case 'symptom':
		//get oils that help with this symptom
		$recs=essentialOilCompanionGetOils($alexa['value']);
		$params=array(
			'listen'		=> true,
			'reprompt'		=> "How may I help?"
		);
		if(is_array($recs)){
			$response="For help with {$alexa['value']} try,".implode(', ',array_keys($recs));
			$params['card']=array(
				'title'	=> $alexa['value'],
				'content'=> $response
			);
		}
		else{
        	$response="I do not know how to help with {$alexa['value']}";
		}
		$response.= ". , How may I help?";
		$session=essentialOilCompanionUpdateSession($session,array(
			'intent'=>$alexa['intent'],
			'value'=>$alexa['value'],
			'response'=>$response
		));
		$ok=alexaResponse($response,$params);
		exit;
	break;
	case 'oil':
		//get symptoms that this oil helps with
		$recs=essentialOilCompanionGetSymptoms($alexa['value']);
		$params=array(
			'listen'		=> true,
			'reprompt'		=> "How may I help?"
		);
		if(is_array($recs)){
			$response="{$alexa['value']} will help with ,".implode(', ',array_keys($recs));
			$params['card']=array(
				'title'	=> $alexa['value'],
				'content'=> $response
			);
		}
		else{
        	$response="I do not know what {$alexa['value']} is good for";
		}
		$response.= ". , How may I help?";
		$session=essentialOilCompanionUpdateSession($session,array(
			'intent'=>$alexa['intent'],
			'value'=>$alexa['value'],
			'response'=>$response
		));
		$ok=alexaResponse($response,$params);
		exit;
	break;
	default:
		$response="yes?";
		$session=essentialOilCompanionUpdateSession($session,array(
			'intent'=>$alexa['intent'],
			'value'=>$alexa['value'],
			'response'=>$response
		));
		$ok=alexaResponse($response,array(
			'listen'		=> true,
			'reprompt'		=> "How may I help?"
		));
	break;
}


?>
