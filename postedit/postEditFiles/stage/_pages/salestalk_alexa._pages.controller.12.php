<?php
/*
	Name: SalesTalk
	AppId: amzn1.echo-sdk-ams.app.c37ff67c-05a2-4834-90a8-e49741379d6f
	Created: by slloyd on 2016-01-29

SSML



*/
/* $json=<<<ENDOFJSON
{"session": {"new": false, "sessionId": "amzn1.echo-api.session.1b12ba25-9392-4dce-93b8-57987ac2afd2", "attributes": {"lastResponse": "What volume would you like to start with?"}, "user": {"userId": "amzn1.echo-sdk-account.AERH3O3WTB6JL3I54HAZD54ZOK2OZS43DAZ3T2YZSAMNFSPJKFL4G"}, "application": {"applicationId": "amzn1.echo-sdk-ams.app.f9409108-1b9e-48cf-bbd7-57df380d8103"}}, "version": "1.0", "request": {"timestamp": "2016-01-26T14:32:02Z", "reason": "EXCEEDED_MAX_REPROMPTS", "type": "SessionEndedRequest", "requestId": "amzn1.echo-api.request.cb5ce855-25cb-49b5-9913-a3e34b1bde96"}}
ENDOFJSON;
$json_array=json_decode($json,1);
echo printValue($json_array);exit; */
loadDBFunctions(array('functions_alexa','functions_common'));
/* SalesTalk Report PROCESS */
//initialize
global $alexa;
global $PAGE;
if(!alexaInit()){
	setView('invalid');
	return;
}
if($alexa['request']['type']=='SessionEndedRequest'){
	//session ended
	$response=commonGreeting('goodbye','general');
	alexaSetUserVar('anything_else',0);
	$ok=alexaResponse($response);
	exit;
}
if(!isset($alexa['swim']['action']) && !isset($alexa['swim']['report'])){
	$anything_else=alexaGetUserVar('anything_else');
	if(strlen($anything_else) && $anything_else==1){
		$alexa['swim']['action']='stop';
		alexaSetUserVar('anything_else',0);
	}
}
$help=<<<ENDOFHELP
Greetings from Skillsay, where you can talk to the cloud and get answers to many of your business questions just by asking!
Sales Talk currently supports business owners using WooCommerce as their store platform or Stripe as their payment gateway as data sources.
There are currently several Named Reports you can ask for, including: Sales, Total Sales, Unit Sales, Best Selling and Worst Selling. New report options will be available every few weeks.
When you ask for a Named Report you can add filter criteria in order to specify a city, state, zip code, day, date range, or a named time period such as yesterday, last month, or last quarter.
If you do not specify a date, Alexa assumes you are asking in relation to the current year beginning on January first.
For instance, you can say "Tell me my sales in Phoenix last month" or, "What were my Unit Sales in Denver on December 24, 2015."
For more information on Sales Talk, please visit us on the web at www.skillsay.com.
ENDOFHELP;
switch(strtolower($alexa['swim']['action'])){
	case 'stop':
	case 'quit':
	case 'thank you':
	case 'never mind':
	case 'goodbye':
	case 'cancel':
	case 'goodbye':
	case 'thanks':
	case 'no':
	case 'done':
	case 'no thanks':
	case 'no thank you':
		$response=commonGreeting('goodbye','general');
		alexaSetUserVar('anything_else',0);
		alexaSetUserVar('report','');
        $ok=alexaResponse($response);
        exit;
	break;
	case 'help':
	case 'help me':
	case 'instructions':
		$response=$help;
		alexaSetUserVar('anything_else',1);
	    $params=array('listen'=>1,'reprompt'=>'what report would you like?');
	    $ok=alexaResponse($response,$params);
	break;
	
}
switch(strtolower($alexa['swim']['report'])){
	case 'stop':
	case 'quit':
	case 'thank you':
	case 'never mind':
	case 'goodbye':
	case 'cancel':
	case 'thanks':
	case 'no':
	case 'done':
	case 'no thanks':
	case 'nope':
	case 'no thank you':
		$response=commonGreeting('goodbye','general');
		alexaSetUserVar('anything_else',0);
		alexaSetUserVar('report','');
        $ok=alexaResponse($response);
        exit;
	break;
	case 'help':
	case 'help me':
	case 'instructions':
		$response=$help;
		alexaSetUserVar('anything_else',1);
	    $params=array('listen'=>1,'reprompt'=>'what report would you like?');
	    $ok=alexaResponse($response,$params);
	break;
}
// $response='Please use the companion app to authenticate on Amazon to start using this skill';
// $ok=alexaResponse($response,array(
// 	'type'=>'LinkAccount'
// ));exit;

if(!isset($alexa['user']['_id'])){
	//User is not registered
	$greet=commonGreeting('hello');
	$response=<<<ENDOFRESPONSE
{$greet}, welcome to Sales Talk. To use this app first open your Alexa app and click on "Link Account".
ENDOFRESPONSE;
	$params=array(
		'type'=>'LinkAccount',
		'card'=>array(
			'title'	=> "SalesTalk Profile Registration",
			'content' => 'click on Link Account to enable sales talk'
		)
	);
	alexaSetUserVar('anything_else',0);
	$ok=alexaResponse($response,$params);
	exit;
}
//set currency code to US for now
setlocale(LC_MONETARY, 'en_US.UTF-8');
if(!isset($alexa['swim']['report'])){
	$greeting=commonGreeting('hello','general');
	$response="{$greeting} {$alexa['user']['firstname']}, how can I help?";
	alexaSetUserVar('anything_else',1);
    $params=array('listen'=>1,'reprompt'=>'what report would you like?');
    $ok=alexaResponse($response,$params);
}
switch(strtolower($alexa['swim']['report'])){
	case 'time':
	case 'what time is it':
        $t=date('g:i a',$alexa['timestamp']+$alexa['profile']['rawOffset']);
        //$response='<say-as interpret-as="time">'.$t.'</say-as>';
        $response="The time is {$t}";
		alexaSetUserVar('anything_else',1);
        $params=array('listen'=>1,'reprompt'=>'Anything else?');
        $ok=alexaResponse($response,$params);
    	break;
	case 'date':
	case 'what is the date':
	case 'day':
	case 'what day is it':
        $d=date('l F j, Y',$alexa['timestamp']+$alexa['profile']['rawOffset']);
        //$response='<say-as interpret-as="time">'.$t.'</say-as>';
        $response="It is {$d}";
        alexaSetUserVar('anything_else',1);
        $params=array('listen'=>1,'reprompt'=>'Anything else?');
        $ok=alexaResponse($response,$params);
    	break;
	case 'repeat that':
	case 'repeat':
	case 'what was that':
	case 'say that again':
		//repeat last response
		$response=alexaGetLastResponse();
		alexaSetUserVar('anything_else',1);
		$params=array('listen'=>1,'reprompt'=>'Anything else?');
        $ok=alexaResponse($response,$params);
		break;
    case 'yes':
        $response="OK, what report?";
        alexaSetUserVar('anything_else',1);
		$ok=alexaResponse($response,array(
			'listen'		=> true,
			'reprompt'		=> "What report was that?"
	
		));
    break;
    default:
    	//Generate the Report Response
    	/*
			Check tab names to see if report name matches
				get alexa_data for each report based on filters
			Check active report names
				get alexa_data for report
			combine data sets to create a response
		*/
		$recs=pageGetUserReportByName();
		//$ok=alexaResponse("I found ".count($recs)." reports that match the {$alexa['swim']['report']} report");
		//alexaSetUserVar('debug',"Tab Count:".count($recs));
		if(!count($recs)){
			$recs=pageGetUserReportsByTab();
			//alexaAppendUserVar('debug'," Report Count:".count($recs));

		}
		if(!count($recs)){
        	//There was no report in the reports table for this. Just send to our nodejs report module
        	$rec=array();
        	foreach($alexa['swim'] as $k=>$v){
				$k=strtolower($k);
		    	$rec['filters'][$k] = $v;
			}
			$_REQUEST['databack']='speech';
			$rec['return_type']='speech';
			$response=commonLoadReportData($rec);
			alexaSetUserVar('report',$alexa['swim']['report']);
		}
		else{
			//alexaSetUserVar('debug',printValue($recs));
			//$ok=alexaResponse("I found ".count($recs)." that match the {$alexa['swim']['report']} report");
			alexaSetUserVar('report',$alexa['swim']['report']);
			$response=array();
			global $rec;
			foreach($recs as $xrec){
				if(strlen($xrec['defaults'])){
		        	$defaults=json_decode($xrec['defaults'],true);
		        	foreach($defaults as $k=>$v){
						$xrec['filters'][$k]=$v;
					}
				}
				foreach($alexa['swim'] as $k=>$v){
					$k=strtolower($k);
		    		$xrec['filters'][$k] = $v;
				}
				$_REQUEST['databack']='speech';
				$xrec['return_type']='speech';
				$response[]=commonLoadReportData($xrec);
			}
			$response=implode(" ", $response);
		}
		// $response=alexaPostSwim();
		$response=trim($response);
		if(!strlen($response)){
        	$response="Nothing to say for {$alexa['swim']['report']}.";
        	$params=array('listen'=>1,'reprompt'=>'How else can I help?');
        	alexaSetUserVar('anything_else',1);
        	$ok=alexaResponse($response,$params);
		}
		else{
        	$ok=alexaUpdateLog(array('swim_response'=>$response));
		}
		$params=array('listen'=>1,'reprompt'=>'How else can I help?');
		if(strlen($recs[0]['card_image'])){
			$url=isDBStage()?'https://stage.skillsai.com':'https://www.skillsai.com';
        	$params['card']['image_small']="{$url}/cors/images/reports/{$recs[0]['card_image']}-small-blank.png";
        	$params['card']['image_large']="{$url}/cors/images/reports/{$recs[0]['card_image']}-large-blank.png";
		}
		//default
		if(!isset($params['card']['content'])){
			$report=ucwords($alexa['swim']['report']);
			$params['card']['content']="Request: {$report}\r\nResponse: {$response}";
		}

        alexaSetUserVar('anything_else',1);
        $ok=alexaResponse($response,$params);
		if(preg_match('/\[(.+?)\](.+)$/',$response,$m)){
        	//SWIM module is requesting more information
        	//$ok=alexaSetUserVar('more_info_field',$m[1]);
			$response=$m[2];
        	$params=array('listen'=>1,'reprompt'=>$m[2]);
        	//$ok=alexaSetUserVar('more_info_last',json_encode($alexa['swim']));
        	alexaSetUserVar('anything_else',1);
        	$ok=alexaResponse($response,$params);
		}
		if(preg_match('/email/i',$alexa['value'])){
        	if(isEmail($biuser['email'])){
				loadExtras('phpmailer');
				$today=date('m/d/Y');
            	$ok=phpmailerSendMail(array(
					'to'=>$biuser['email'],
					'from'=>'sales@skillsai.com',
					'subject'=>"{$filter} {$alexa['swim']['report']} Report as of {$today}",
					'message'=>removeHtml($response)
				));
				$response="I have emailed you the {$filter} {$alexa['swim']['report']} Report";
			}
			else{
				$response="I cannot email reports until you have added your email to your profile.";
			}
		}
		//$post['body'] may be text, csv, xml, or json
		//check for json
		$json=json_decode($response);
 		if(json_last_error() == JSON_ERROR_NONE){
			if(isset($json['response'])){$response=$json['response'];}
        	if(isset($json['card'])){$params['card']=$json['card'];}
        	if(isset($json['title'])){$params['card']['title']=$json['title'];}
        	if(isset($json['content'])){$params['card']['content']=$json['content'];}
        	if(isset($json['listen'])){$params['listen']=$json['listen'];}
        	if(isset($json['reprompt'])){$params['reprompt']=$json['reprompt'];}
		}
		else{
        	$params['listen']=1;
        	$params['reprompt']='Anything else?';
		}
		if(strlen($recs[0]['card_image'])){
			$url=isDBStage()?'https://stage.skillsai.com':'https://www.skillsai.com';
        	$params['card']['image_small']="{$url}/cors/images/reports/{$recs[0]['card_image']}-small-blank.png";
        	$params['card']['image_large']="{$url}/cors/images/reports/{$recs[0]['card_image']}-large-blank.png";
		}
		//default
		if(!isset($params['card']['content'])){
			$report=ucwords($alexa['swim']['report']);
			$params['card']['content']="Request: {$report}\r\nResponse: {$response}";
		}
		alexaSetUserVar('debug',"PARAMS".printValue($params));
		alexaSetUserVar('anything_else',1);
		$ok=alexaResponse($response,$params);
	break;
}

?>
