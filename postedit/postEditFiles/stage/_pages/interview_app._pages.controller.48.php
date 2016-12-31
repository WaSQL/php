<?php
loadDBFunctions(array('functions_alexa','functions_common'));
/*
	Ask Interview Questions

*/
//initialize
global $alexa;
global $PAGE;
$yearweek=date('YW');
if(!alexaInit()){
	//show the webpage for this skill
	setView('default');
	return;
}


if($alexa['request']['type']=='SessionEndedRequest'){
	//session ended
	$response=commonGreeting('goodbye','general');
	$ok=alexaResponse($response);
	exit;
}
//check for quit words
switch(strtolower($alexa['swim']['color'])){
	case 'quit':
	case 'i quit':
	case 'never mind':
	case 'goodbye':
	case 'stop':
 	case 'cancel':
 		alexaClearUserVars();
		alexaSetUserVar('first_time',1);
		$response=commonGreeting('goodbye','general');
		$ok=alexaResponse($response);
	break;
}
$params=array(
	'listen'=>1,
	'reprompt'=>'What question Number?'
	);
if(isset($alexa['swim']['number'])){
	$question=pageGetQuestion($alexa['swim']['number']);
	if(!strlen($question)){
		$question="Question number {$alexa['swim']['number']} does not exist. What question number?";
		$ok=alexaResponse($question,$params);
	}
	$ok=alexaResponse($question);
	//$ok=alexaResponse($response,$params);
}
$question="Hey, what question number?";
$ok=alexaResponse($question,$params);
?>
