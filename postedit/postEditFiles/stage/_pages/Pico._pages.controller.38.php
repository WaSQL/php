<?php
loadDBFunctions(array('functions_alexa','functions_common'));
//initialize
global $alexa;
global $PAGE;

if(!alexaInit()){
	loadExtrasJs('chart');
	setView('default');
	return;
}
if($alexa['request']['type']=='SessionEndedRequest'){
	//session ended
	$response=commonGreeting('goodbye','general');
	$ok=alexaResponse($response);
	exit;
}
//check for custom words
switch(strtolower($alexa['swim']['playmethod'])){
	case 'help':
	case 'help me':
		if(isset($alexa['swim']['length'])){
        	$response="I didn't catch that. What number?";
			$params=array('listen'=>1,'reprompt'=>$response);
			$ok=alexaResponse($response,$params);
		}
		if(!isset($alexa['user']['playmethod'])){$reprompt="Which version, easy or standard?";}
		elseif(!isset($alexa['user']['length'])){
		    $reprompt='What length of number would you like to try? 3, 4, or 5?';
		}
		else{
			$reprompt="Pick a number.";
		}
		$response=<<<ENDOFHELP
Welcome to Pico - a number guessing game provided by skill say dot com.
Pico Fermi Bagel requires logic to discover the secret number.
To see an example of how to play this game, go to http://www.skill say.com/pico. ,
There are three terms I will use during the game to help you make your next guess.
Pico. Pico means a digit is in the secret number, but in the wrong location.
Fermi. Fermi means a digit is in the secret number and in the correct location.
Bagel. Bagel means the digit is not in the secret number at all.,
I will choose a secret number and you try to guess it. 
I will give you 6 tries for a 2 digit number, 8 tries for a 3 digit number, 9 tries for a 4 digit number and 10 tries for a 5 digit number.
{$reprompt}
ENDOFHELP;
		$params=array('listen'=>1,'reprompt'=>$reprompt);
		$ok=alexaResponse($response,$params);
	break;
	case 'repeat':
	case 'repeat that':
	case 'what':
	case 'what was that':
		if(!isset($alexa['user']['playmethod'])){$reprompt="Which version, easy or standard?";}
		elseif(!isset($alexa['user']['length'])){
		    $reprompt='What length of number would you like to try? 2, 3, 4, or 5?';
		}
		else{
			$reprompt="Pick a number.";
		}
		$response=alexaGetLastResponse();
		$params=array('listen'=>1,'reprompt'=>$reprompt);
		$ok=alexaResponse($response,$params);
	break;
	case 'debug mode':
		if(isDBStage()){
			$max_tries=3;
			alexaSetUserVar('debug_mode',1);
			$response =" debug mode is now on. ";
		}
		else{
        	$response = " no such mode. ";
		}
		if(!isset($alexa['user']['playmethod'])){$reprompt="Which version, easy or standard?";}
		elseif(!isset($alexa['user']['length'])){
		    $reprompt='What length of number would you like to try? 2, 3, 4, or 5?';
		}
		else{
			$reprompt="Pick a number.";
		}
		$response .=" {$reprompt}";
		$params=array('listen'=>1,'reprompt'=>$reprompt);
		$ok=alexaResponse($response,$params);
	break;
	case 'make me immortal':
		$response="You've unlocked secret powers. You're now immortal.";
	case 'the math fairy sent me':
		if(!strlen($response)){
			$response="You've unlocked magical powers. You now have unlimited guesses.";
		}
	case 'pico de gallo':
		if(!strlen($response)){
			$response="Alebrijes are with you. You now have unlimited guesses.";
		}
		alexaSetUserVar('immortal',1);
		if(!isset($alexa['user']['playmethod'])){$reprompt="Which version, easy or standard?";}
		elseif(!isset($alexa['user']['length'])){
		    $reprompt='What length of number would you like to try?';
			if(!isset($alexa['user']['first_time'])){$reprompt.=' 2, 3, 4, or 5?';}
		}
		else{
			$reprompt="Pick a number.";
		}
		$response .=" {$reprompt}";
		$params=array('listen'=>1,'reprompt'=>$reprompt);
		$ok=alexaResponse($response,$params);
	break;
	case 'quit':
	case 'i quit':
	case 'i give up':
		alexaClearUserVars();
		$response=commonGreeting('goodbye','general');
		if(isset($alexa['user']['number'])){
			$response="<speak>{$response}. The number was <say-as interpret-as=\"digits\">{$alexa['user']['number']}</say-as>.</speak>";
			alexaClearUserVars();
		}
		$ok=alexaResponse($response);
	break;
	case 'never mind':
	case 'enough':
	case 'goodbye':
	case 'stop':
 	case 'cancel':
 		alexaClearUserVars();
		$response=commonGreeting('goodbye','general');
		$ok=alexaResponse($response);
		break;
	case 'no':
	case 'no thanks':
	case 'no thank you':
		if(isset($alexa['user']['again'])){
			alexaClearUserVars();
			$response=commonGreeting('goodbye','general');
			$ok=alexaResponse($response);
			exit;
		}
	break;
	case 'yes':
	case 'please':
	case 'yes please':
		if(isset($alexa['user']['again'])){
			alexaSetUserVar('again','');
			alexaSetUserVar('playmethod','');
			$response='How would you like to play? Easy or Standard version?';
			$params=array('listen'=>1,'reprompt'=>'Easy or Standard version?');
			$ok=alexaResponse($response,$params);
		}
	break;
}


//set playmethod?
if(isset($alexa['swim']['playmethod']) && !isset($alexa['user']['playmethod'])){
	alexaSetUserVar('playmethod',$alexa['swim']['playmethod']);
}
//set methodfilter?
if(isNum($alexa['swim']['length']) && !isNum($alexa['user']['length'])){
	alexaSetUserVar('length',$alexa['swim']['length']);
}
if(isNum($alexa['swim']['number']) && !isNum($alexa['user']['length'])){
	alexaSetUserVar('length',$alexa['swim']['number']);
}

if(!isset($alexa['user']['playmethod'])){
	$response='<speak>';
	if(!isset($alexa['user']['first_time'])){
		$response.= "Welcome to Pico - a number guessing game provided by skill say dot com. ";
		$response.="Would you like to play the easy version where I tell you the result for every digit, or the standard version where you only get the result summary?";
	}
	else{
    	$response.="Which version, easy or standard?";
	}
	$response.="</speak>";
	$content=<<<ENDOFRESPONSE
		Welcome to Pico - a number guessing game provided by skillsai.com.
		For more information about this game visit http://www.skillsai.com/pico.
		Would you like to play the easy version where I tell you the result for every digit, or the standard version where you only get the result summary?
ENDOFRESPONSE;
	$params=array(
		'card' => array(
			'title' 		=> 'Pico',
			'content'		=> $content,
			'image_small'	=> "https://www.skillsai.com/cors/skills/pico/PicoSmall720x480.png",
			'image_large'	=> "https://www.skillsai.com/cors/skills/pico/PicoLarge1200x800.png",

		),
		'listen'=>1,
		'reprompt'=>'Would you like to play the easy version or the standard version?'
	);

	$ok=alexaResponse($response,$params);
}

if(!isset($alexa['user']['length'])){
	$response="What length of number would you like to try?";
	if(!isset($alexa['user']['first_time'])){$response.=" 2, 3, 4, or 5?";}
	$params=array('listen'=>1,'reprompt'=>$response);
	$ok=alexaResponse($response,$params);
}
if(!in_array($alexa['user']['length'],array(2,3,4,5))){
	$response="{$alexa['user']['length']} is an invalid length. What length of number would you like to try?";
	if(!isset($alexa['user']['first_time'])){$response.=" 2, 3, 4, or 5?";}
	$params=array('listen'=>1,'reprompt'=>$response);
	$ok=alexaResponse($response,$params);
}
if(!isset($alexa['user']['number'])){
	$digits = $alexa['user']['length'];
	$number=picoRandomNumber($digits);
	//set max_tries based on length
	switch($digits){
		case 2:$max_tries=6;break;
    	case 3:$max_tries=8;break;
    	case 4:$max_tries=9;break;
    	case 5:$max_tries=10;break;
	}
	//$number = rand(pow(10, $digits-1), pow(10, $digits)-1);
	alexaSetUserVar('number',$number);
	alexaSetUserVar('max_tries',$max_tries);
	//set the guesses to 10 un
	alexaSetUserVar('tries',0);
	//so I now have a playmethod and a methodfilter -- pick a word.
	$debug=isset($alexa['user']['debug_mode'])?"The number is <say-as interpret-as=\"digits\">{$number}</say-as>. ":'';
	$response="<speak>Okay. I've chosen a number that's {$digits} digits long. {$debug} Now it's your turn to guess. Pick a {$digits} digit number.</speak>";
	alexaSetUserVar('again','');
	alexaSetUserVar('first_time',1,1);
 	$params=array(
		'card' => array(
			'title' 		=> 'Pico',
			'content'		=> $response,
			'image_small'	=> "https://www.skillsai.com/cors/skills/pico/PicoSmall720x480.png",
			'image_large'	=> "https://www.skillsai.com/cors/skills/pico/PicoLarge1200x800.png",
		),
		'listen'=>1,
		'reprompt'=>"Pick a {$digits} digit number."
	);
	$ok=alexaResponse($response,$params);
}
if(!isset($alexa['swim']['number']) || !isNum($alexa['swim']['number'])){
	$response="I don't understand? What number was that?";
	$params=array('listen'=>1,'reprompt'=>$response);
	$response = "<speak>I heard you say <say-as interpret-as=\"digits\">{$alexa['swim']['number']}</say-as>. {$response}</speak>";
	$ok=alexaResponse($response,$params);
}
//guess cannot contain zero
if(stringContains($alexa['swim']['number'],0)){
	$response="Zero is not a valid number.";
	if(!isset($alexa['user']['first_time'])){$response.=" Pick a different number.";}
	$params=array('listen'=>1,'reprompt'=>$response);
	$ok=alexaResponse($response,$params);
}
//confirm that their guess has no duplicate numbers
$parts=str_split($alexa['swim']['number']);
$vcnts=array_count_values($parts);
$dups=0;
foreach($vcnts as $vcnt){
	if($vcnt > 1){$dups++;}
}
if($dups > 0){
	$response="Your guess cannot have duplicate digits. Pick a number without duplicate digits.";
	$params=array('listen'=>1,'reprompt'=>$response);
	$response = "<speak>I heard you say <say-as interpret-as=\"digits\">{$alexa['swim']['number']}</say-as>. {$response}</speak>";
	$ok=alexaResponse($response,$params);
}
if(strlen($alexa['swim']['number']) != $alexa['user']['length']){
	$response="Pick a {$alexa['user']['length']} digit number";
	$params=array('listen'=>1,'reprompt'=>$response);
	$response = "<speak>I heard you say <say-as interpret-as=\"digits\">{$alexa['swim']['number']}</say-as>. {$response}</speak>";
	$ok=alexaResponse($response,$params);
}
//check for numbers they have already used.
$numbers=array();
if(strlen($alexa['user']['numbers'])){
	$numbers=json_decode($alexa['user']['numbers'],true);
	if(in_array($alexa['swim']['number'],$numbers)){
		//you have already tried that number
		$response="<speak>You've already used <say-as interpret-as=\"digits\">{$alexa['swim']['number']}</say-as>. Pick a different {$alexa['user']['length']} digit number</speak>";
		$params=array('listen'=>1,'reprompt'=>"Pick a {$alexa['user']['length']} digit number");
		$ok=alexaResponse($response,$params);
	}
}
$max_tries=$alexa['user']['max_tries'];
$numbers[]=$alexa['swim']['number'];
alexaSetUserVar('numbers',json_encode($numbers));
alexaSetUserVar('tries',$alexa['user']['tries']+1);

if($alexa['swim']['number'] == $alexa['user']['number']){
	$answers=picoParseAnswers();
	switch(strtolower($alexa['user']['playmethod'])){
		case 'easy':
			$title = implode('',$answers['easy_titles']);
		break;
		default:
			//standard
			$title='';
			krsort($answers['standard']);
			foreach($answers['standard'] as $type=>$cnt){
	        	$letter=substr($type,0,1);
	        	$title .= "{$cnt}{$letter} ";
			}
			$title=trim($title);
		break;
	}
	$response="You Win!";
	$soundfile=commonGetSoundfile('win');
	$trystr=$alexa['user']['tries'] > 1?'tries':'try';
	$guesstr=$alexa['user']['tries'] > 1?'guesses':'guess';
	$response="<speak>Your guessed it right in only {$alexa['user']['tries']} {$trystr}. You win! <audio src=\"{$soundfile}\" /> Would you like to play again?</speak>";
	$titles=array();
	if(strlen($alexa['user']['titles'])){
		$titles=json_decode($alexa['user']['titles'],true);
	}
	$titles[]="{$alexa['swim']['number']} ({$title})";
	$content= "\r\n\r\nYour tries so far:\r\n".implode("\r\n",$titles);
	$params=array(
		'card' => array(
			'title' 		=> 'Pico',
			'content'		=> "You won in {$alexa['user']['tries']} {$guesstr}. The number was {$alexa['user']['number']}. {$content}",
			'image_small'	=> "https://www.skillsai.com/cors/skills/pico/PicoSmall720x480.png",
			'image_large'	=> "https://www.skillsai.com/cors/skills/pico/PicoLarge1200x800.png",
		),
		'listen'=>1,
		'reprompt'=>'Would you like to play again?'
	);
	alexaClearUserVars();
	alexaSetUserVar('again',1);
	$ok=alexaResponse($response,$params);
}
if(!isset($alexa['user']['immortal']) && $alexa['user']['tries'] >= $max_tries){
	$answers=picoParseAnswers();
	switch(strtolower($alexa['user']['playmethod'])){
		case 'easy':
			$title = implode('',$answers['easy_titles']);
		break;
		default:
			//standard
			$title='';
			krsort($answers['standard']);
			foreach($answers['standard'] as $type=>$cnt){
	        	$letter=substr($type,0,1);
	        	$title .= "{$cnt}{$letter} ";
			}
			$title=trim($title);
		break;
	}
	$response="You Lose!";
	$soundfile=commonGetSoundfile('lose');
	$response="<speak>You lost! <audio src=\"{$soundfile}\" /> The number was <say-as interpret-as=\"digits\">{$alexa['user']['number']}</say-as>. Would you like to play again?</speak>";
	$titles=array();
	if(strlen($alexa['user']['titles'])){
		$titles=json_decode($alexa['user']['titles'],true);
	}
	$titles[]="{$alexa['swim']['number']} ({$title})";
	$content= "\r\n\r\nYour tries so far:\r\n".implode("\r\n",$titles);
	$params=array(
		'card' => array(
			'title' 		=> 'Pico',
			'content'		=> "You lost! The number was {$alexa['user']['number']}. {$content}",
			'image_small'	=> "https://www.skillsai.com/cors/skills/pico/PicoSmall720x480.png",
			'image_large'	=> "https://www.skillsai.com/cors/skills/pico/PicoLarge1200x800.png",
		),
		'listen'=>1,
		'reprompt'=>'Would you like to play again?'
	);
	alexaClearUserVars();
	alexaSetUserVar('again',1);
	$ok=alexaResponse($response,$params);
}

//determine how much of the number is correct
$answers=picoParseAnswers();
$response='';
$title='';
$debug='';

switch(strtolower($alexa['user']['playmethod'])){
	case 'easy':
		$response = implode(', ',$answers['easy']).',';
		$title = implode('',$answers['easy_titles']);
	break;
	default:
		//standard
		$title='';
		krsort($answers['standard']);
		foreach($answers['standard'] as $type=>$cnt){
        	$response.= "{$cnt} {$type}, ";
        	$letter=substr($type,0,1);
        	$title .= "{$cnt}{$letter} ";
		}
		$title=trim($title);
	break;
}
$response=preg_replace('/\,+$/','',rtrim($response));
$response .= '. ';
if(!isset($alexa['user']['immortal'])){
	$tries_left=$max_tries-$alexa['user']['tries'];
	$trystr=$tries_left > 1?'tries':'try';
	$response .= "  You have {$tries_left} {$trystr} left.";
}

$reprompt = " Pick another {$alexa['user']['length']} digit number.";
$response .= $reprompt;
$titles=array();
if(strlen($alexa['user']['titles'])){
	$titles=json_decode($alexa['user']['titles'],true);
}
$titles[]="{$alexa['swim']['number']} ({$title})";
alexaSetUserVar('titles',json_encode($titles));
$content= "\r\n\r\nYour tries so far:\r\n".implode("\r\n",$titles);
$params=array('listen'=>1,'reprompt'=>$reprompt);
$params=array(
	'card' => array(
		'title' 		=> "{$alexa['swim']['number']} ({$title})",
		'content'		=> $response." {$content}",
		'image_small'	=> "https://www.skillsai.com/cors/skills/pico/PicoSmall720x480.png",
		'image_large'	=> "https://www.skillsai.com/cors/skills/pico/PicoLarge1200x800.png",
	),
	'listen'=>1,
	'reprompt'=>$reprompt
);
$response = "<speak>I heard you say <say-as interpret-as=\"digits\">{$alexa['swim']['number']}</say-as>. {$response}</speak>";

$ok=alexaResponse($response,$params);
exit;
?>
