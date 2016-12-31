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
if($_REQUEST['passthru'][0]=='register'){
	$rid=addslashes($_REQUEST['passthru'][1]);
	setView('register');
	return;
}

loadDBFunctions('functions_alexa');
//initialize
global $volume;
global $alexa;
if(!alexaInit()){
	echo "Invalid Request";
	exit;
}
$session=visitorsGetSession();
if($alexa['request']['type']=='SessionEndedRequest'){
	//session ended
	$ok=alexaResponse("Goodbye");
	exit;
}
switch(strtolower(trim($alexa['value']))){
    case 'never mind':
    case 'stop':
    case 'done':
        $ok=alexaResponse('later.');
    break;
    case 'thanks':
    case 'thank you':
        $ok=alexaResponse('You are welcome.');
    break;
}

switch(strtolower($alexa['intent'])){
	case 'visitor':
		//add visitor
		$visitors=preg_split('/\,+/',trim($session['visitors']));
		$visitors[]=$alexa['value'];
		$list=implode(',',$visitors);
		$list=preg_replace('/^\,+/','',$list);
		$list=preg_replace('/\,+$/','',$list);
		$session=visitorsUpdateSession($session,array('visitors'=>$list));
		$response="I have added {$alexa['value']} to the visitors list";
		$params=array(
			'listen'		=> true,
			'reprompt'		=> "How may I help?"
		);
		$ok=alexaResponse($response,$params);
	break;
	case 'action':
		switch(strtolower($alexa['value'])){
        	case 'say hi':
        	case 'say hi to our visitors':
        		$list=str_replace(',',' and ',$session['visitors']);
        		$response=<<<ENDOFRESPONSE
Hello {$list}.
My name is Alexa.
I joined the family recently.
We are so excited you are in our home!
If there is anything I can do, feel free to ask, ok? , ,
Just say my name, and ask me whatever you want.
I will do my best to answer.
ENDOFRESPONSE;
				$ok=alexaResponse($response);
        	break;
        	case 'remove visitors':
        		$session=visitorsUpdateSession($session,array('visitors'=>''));
				$response="I have removed all visitors from the list. It is now blank.";
				$params=array(
					'listen'		=> true,
					'reprompt'		=> "How may I help?"
				);
				$ok=alexaResponse($response,$params);
        	break;
        	case 'list visitors':
        		$list=str_replace(',',' and ',$session['visitors']);
        		if(!strlen($list)){$list=' is blank';}
        		$response="Visitors are, {$list}.";
				$params=array(
					'listen'		=> true,
					'reprompt'		=> "How may I help?"
				);
				$ok=alexaResponse($response,$params);
        	break;
        	case 'help':
        		$response=<<<ENDOFHELP
To add a visitor to the visitors list, say, "add visitor" followed by their first name.
To remove all visitors from the visitors list, say, "remove visitors".
To list visitors on the list, say, "list visitors".
You can also say, "thank you" or, "never mind", and I will finally shutup.
To say hi to the visitors, say, "say hi".
ENDOFHELP;
				$params=array(
					'listen'		=> true,
					'reprompt'		=> "How may I help?"
				);
				$ok=alexaResponse($response,$params);
        	break;
        	default:
        		$response="Not sure what you mean.";
				$params=array(
					'listen'		=> true,
					'reprompt'		=> "How may I help?"
				);
				$ok=alexaResponse($response,$params);
        	break;
		}

	break;
	default:
		$response="yes?";
		$ok=alexaResponse($response,array(
			'listen'		=> true,
			'reprompt'		=> "How may I help?"
		));
	break;
}


?>
