<?php
/*
	Name: LDS Scripture Mastery
	AppId: amzn1.echo-sdk-ams.app.f9409108-1b9e-48cf-bbd7-57df380d8103
	Created: by slloyd on 2016-01-24
	
*/
/* $json=<<<ENDOFJSON
{"session": {"new": false, "sessionId": "amzn1.echo-api.session.1b12ba25-9392-4dce-93b8-57987ac2afd2", "attributes": {"lastResponse": "What volume would you like to start with?"}, "user": {"userId": "amzn1.echo-sdk-account.AERH3O3WTB6JL3I54HAZD54ZOK2OZS43DAZ3T2YZSAMNFSPJKFL4G"}, "application": {"applicationId": "amzn1.echo-sdk-ams.app.f9409108-1b9e-48cf-bbd7-57df380d8103"}}, "version": "1.0", "request": {"timestamp": "2016-01-26T14:32:02Z", "reason": "EXCEEDED_MAX_REPROMPTS", "type": "SessionEndedRequest", "requestId": "amzn1.echo-api.request.cb5ce855-25cb-49b5-9913-a3e34b1bde96"}}
ENDOFJSON;
$json_array=json_decode($json,1);
echo printValue($json_array);exit; */

loadDBFunctions('functions_alexa');
//initialize
global $volume;
global $alexa;
if(!alexaInit()){
	echo "Invalid Request";
	exit;
}
global $session;
global $response;
$session=scriptureMasteryGetSession();
if($alexa['request']['type']=='SessionEndedRequest'){
	//session ended
	$ok=scriptureMasteryUpdateSession($session,array('active'=>0));
	$ok=alexaResponse("Goodbye");
	exit;
}
switch(strtolower(trim($alexa['value']))){
    case 'never mind':
    case 'stop':
    case 'done':
    	$session=scriptureMasteryUpdateSession($session,array('active'=>0));
        $ok=alexaResponse('later.');
    break;
    case 'thanks':
    case 'thank you':
    	$session=scriptureMasteryUpdateSession($session,array('active'=>0));
        $ok=alexaResponse('You are welcome.');
    break;
}
switch(strtolower($alexa['intent'])){
	case 'volume':
		if(strtolower($alexa['value'])=='all volumes' || getDBCount(array('-table'=>'scripture_mastery_scriptures','book'=>$alexa['value']))){
			//set the volume
			$response="{$alexa['value']}. OK.";
			$session=scriptureMasteryUpdateSession($session,array('volume'=>$alexa['value']));
			scriptureMasteryPlay();
		}
		else{
        	$response="What book of scripture would you like to start with? old testament, new testament, book of mormon, doctrine & covenants, or all volumes?";
			$ok=alexaResponse($response,array(
				'reprompt'		=> 'old testament, new testament, book of mormon, doctrine & covenants, or all volumes?',
				'listen'		=> true
			));
		}
	break;
	case 'reference':
	case 'phrase':
		$scripture=scriptureMasteryGetScriptureById($session['scripture_id']);
		switch(strtolower($alexa['value'])){
        	case "i don't know":
        	case 'i dont know':
        	case 'not sure':
        	case 'tell me':
        		$response .= "Ok, ";
        		scriptureMasteryPlay($scripture);
        	break;
		}
		if($session['ask']=='keyphrase'){
			//$scripture['metaphone']=metaphone($alexa['value']);
			similar_text(strtolower(metaphone($alexa['value'])),strtolower($scripture['keyphrase_metaphone']),$pcnt);
        	if($pcnt > 70){
				$correct_ids=scriptureMasteryGetCorrectIds();
				if(!in_array($session['scripture_id'],$correct_ids)){
                	$correct_ids[]=$session['scripture_id'];
				}
				$correct_cnt=count($correct_ids);
				$correct_ids=implode(':',$correct_ids);
				$session=scriptureMasteryUpdateSession($session,array('correct_ids'=>$correct_ids,'heard'=>$alexa['value'],'pcnt'=>$pcnt,'correct'=>1,'score'=>$session['score']+1));
				if($session['score']==5){$response.="Five in a row equals way to go! ";}
				elseif($correct_cnt==10){$response.="Fifteen left before you ace this! ";}
				elseif($correct_cnt==15){$response.="Only ten left before you win! ";}
				elseif($correct_cnt==20){$response.="Only five left and you get them all! ";}
				elseif($correct_cnt==25){
					$response.="Way to score! That is awesome! ";
					$ok=scriptureMasteryUpdateSession($session,array('active'=>0));
					$ok=alexaResponse($response);
					break;
					}
				else{
            		$response.=scriptureMasteryPraise().' ';
				}
            	scriptureMasteryPlay($scripture);
            	break;
			}
			else{
				$session=scriptureMasteryUpdateSession($session,array('heard'=>$alexa['value'],'pcnt'=>$pcnt,'correct'=>0,'score'=>0));
				$response.="Not quite, ";
            	scriptureMasteryPlay($scripture);
            	break;
			}
		}
		else{
        	//make one string without numbers
        	$alexa['value']=str_replace('do you see','D&C',$alexa['value']);
        	$a1=preg_replace('/[^a-z]+/i','',$alexa['value']);
        	$n1=preg_replace('/[^0-9]+/','',$alexa['value']);
        	$m1=metaphone(strtolower($a1))."-{$n1}";
			$a2=preg_replace('/[^a-z]+/i','',$scripture['reference']);
			$n2=preg_replace('/[^0-9]+/i','',$scripture['reference']);
			$m2=metaphone(strtolower($a2))."-{$n2}";
			similar_text($m1,$m2,$pcnt);
			//$scripture['debug']="m1:{$m1} ==  m2:{$m2} == pcnt:{$pcnt}";
        	if($pcnt > 50){
				$correct_ids=scriptureMasteryGetCorrectIds();
				if(!in_array($session['scripture_id'],$correct_ids)){
                	$correct_ids[]=$session['scripture_id'];
				}
				$correct_cnt=count($correct_ids);
				$correct_ids=implode(':',$correct_ids);
				$session=scriptureMasteryUpdateSession($session,array('correct_ids'=>$correct_ids,'heard'=>$alexa['value'],'pcnt'=>$pcnt,'correct'=>1,'score'=>$session['score']+1));
            	if($session['score']==5){$response.="Five in a row equals way to go! ";}
				elseif($correct_cnt==10){$response.="Fifteen left before you ace this! ";}
				elseif($correct_cnt==15){$response.="Only ten left before you win! ";}
				elseif($correct_cnt==20){$response.="Only five left and you get them all! ";}
				elseif($correct_cnt==25){
					$response.="Way to score! That is awesome! ";
					$ok=scriptureMasteryUpdateSession($session,array('active'=>0));
					$ok=alexaResponse($response);
					break;
					}
				else{
            		$response.=scriptureMasteryPraise().' ';
				}
            	scriptureMasteryPlay($scripture);
            	break;
			}
			else{
				$session=scriptureMasteryUpdateSession($session,array('heard'=>$alexa['value'],'pcnt'=>$pcnt,'correct'=>0,'score'=>0));
				$response.="Nope, ";
            	scriptureMasteryPlay($scripture);
            	break;
			}
		}
	break;
	default:
		$response="What book of scripture would you like to start with?";
		$ok=alexaResponse($response,array(
			'reprompt'		=> 'old testament, new testament, book of mormon, doctrine & covenants, or all volumes?',
			'listen'		=> true
		));
	break;
}


?>
