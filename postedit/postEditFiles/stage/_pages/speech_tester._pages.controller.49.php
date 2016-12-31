<?php
loadDBFunctions(array('functions_alexa','functions_common'));
/*
	Alexa open {skill}
		there was a problem with the requested skills response
		I am unable to reach the requested skill
*/
//initialize
global $alexa;
global $PAGE;


$yearweek=date('YW');
if(!alexaInit()){
	//show the webpage for this skill
	if(isAjax()){
		$stats=pageStats();
		setView('current_canvas',1);
		return;
	}
	$colors=getColors();
	setView('default');
	return;
}


if($alexa['request']['type']=='SessionEndedRequest'){
	//session ended
	alexaClearUserVars();
	$response=commonGreeting('goodbye','general');
	$ok=alexaResponse($response);
	exit;
}
//check for quit words
switch(strtolower($alexa['swim']['speech'])){
	case 'quit':
	case 'i quit':
	case 'never mind':
	case 'goodbye':
	case 'stop':
 	case 'cancel':
 		alexaClearUserVars();
		$response=commonGreeting('goodbye','general');
		$ok=alexaResponse($response);
	break;
	default:
		if(!isset($alexa['user']['speech'])){
        	$response="say something";
        	alexaSetUserVar('speech',1);
		}
		elseif(!isset($alexa['swim']['speech'])){
        	$response="You said nothing";
		}
		else{
        	$response="I heard you say {$alexa['swim']['speech']}";
		}
		$params=array('listen'=>1,'reprompt'=>"say something");
		$ok=alexaResponse($response,$params);
	break;
}
?>
