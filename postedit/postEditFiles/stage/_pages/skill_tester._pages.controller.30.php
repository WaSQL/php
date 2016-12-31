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
switch(strtolower($alexa['swim']['action'])){
	case 'quit':
	case 'i quit':
	case 'never mind':
	case 'goodbye':
	case 'stop':
 	case 'cancel':
 		$color=$alexa['user']['color'];
 		alexaClearUserVars();
		alexaSetUserVar('first_time',1);
		alexaSetUserVar('color',$color);
		$response=commonGreeting('goodbye','general');
		$ok=alexaResponse($response);
	break;
}
if(strlen($alexa['swim']['device']) ){
	alexaSetUserVar('device',$alexa['swim']['device']);
}
if(!strlen($alexa['user']['device'])){
	$response="Which one of us do you want to test? Amazon or Alexa?";
	$params=array('listen'=>1,'reprompt'=>"You really have to choose between us! Amazon or Alexa?");
	$ok=alexaResponse($response,$params);
}
$response='';
if(strlen($alexa['swim']['error'])){
	switch(strtolower($alexa['swim']['error'])){
		case 'there was a problem with the requested skills response':
		case 'i am unable to reach the requested skill':
			$response .= " Skill, {$alexa['user']['skill']}, is busted, broken, in the shop, under the weather, or thus incapacitated.";
		break;
		default:
			$response .= "{$alexa['user']['device']}, stop. Skill, {$alexa['user']['skill']}, is working fine. ";
		break;
	}
}
switch(strtolower($alexa['user']['skill'])){
	case 'pico fermi bagel':
		alexaClearUserVars();
		$response .= " All tests are complete";
		$ok=alexaResponse($response);
	break;
	case 'hangman':
		alexaSetUserVar('skill','Pico Fermi Bagel');
		$initiate="{$alexa['user']['device']}, stop. ,{$alexa['user']['device']},  open Pico Fermi Bagel.";
		$response .= $initiate;
		$params=array('listen'=>1,'reprompt'=>$initiate);
		$ok=alexaResponse($response,$params);
	break;
	default:
		alexaSetUserVar('skill','Hangman');
		$initiate="{$alexa['user']['device']},  open Hangman.";
		$response .= $initiate;
		$params=array('listen'=>1,'reprompt'=>$initiate);
		$ok=alexaResponse($response,$params);
	break;
}
?>
