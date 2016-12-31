<?php
/*
	https://stage.skillsai.com?_auth=1&_noguid=1&username=amazon&apikey=YW1LbTZTUENUZVV1LjpBbTdzRDV2cS82NGFrOmFtTUhrdVZQVldzM1k=
*/
loadDBFunctions(array('functions_alexa','functions_common'));
loadExtrasJs('chart');
//initialize
global $alexa;
global $PAGE;

if(!alexaInit()){
	$categorylist=getCategories(1);
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
switch(strtolower($alexa['swim']['letter'])){
	case 'help':
	case 'help me':
		if(!isset($alexa['user']['playmethod'])){$reprompt="How would you like to play? By word length? Or by category?";}
		elseif(!isset($alexa['user']['methodfilter'])){
			switch(strtolower($alexa['user']['playmethod'])){
		    	case 'word length':
		    	case 'length':
		    		$reprompt='How many letters would you like to try?';
		    	break;
		    	default:
		    		$reprompt="Choose a category. To hear the categories, just say, list categories.";
		    	break;
			}
		}
		else{
			$reprompt="Pick a letter.";
		}
		$response=<<<ENDOFHELP
Welcome to Hangman, provided by skill say dot com.
Hangman is a game where you try to guess the letters of a word.
You first start by either picking a word category or playing by word length.
I then think of a word for you to guess.  
You will be prompted to pick a letter.  
You can say a letter, or say a word that begins with the letter you want.
Once you have picked six letters that are not in the word then you lose.,
You win, when you guess each letter in the word.
At any time during the game you can also try to guess the whole word for a win.,
If you need a hint, just say "what does it mean?", and I will give you the meaning of the word.
Use your alexa app to see your progress, visualize the word, etc., ,
{$reprompt}
ENDOFHELP;
		$params=array('listen'=>1,'reprompt'=>$reprompt);
		$ok=alexaResponse($response,$params);
	break;
	case 'say the phonetic alphabet':
	case 'phonetic alphabet':
	case 'say the alphabet':
	case 'repeat the phonetic alphabet':
	case 'repeat the alphabet':
	case 'what is the phonetic alphabet':
	case 'whats the phonetic alphabet':
		$phonetics=getPhoneticAlphabet();
		$phonetics_list=implode(', ',array_keys($phonetics));
		$parts=array();
		foreach($phonetics as $phonetic=>$letter){
        	$parts[]=strtoupper($letter).' = '.ucwords($phonetic);
		}
		$card=implode(", ",$parts);
		$response="The phonetic alphabet is: {$phonetics_list}. I have sent this list to your amazon app for your reference. Pick a letter.";
		$params=array(
			'card' => array(
				'title' 		=> 'The Phonetic Alphabet',
				'content'		=> $card,
			),
			'listen'=>1,
			'reprompt'=>'Pick a letter.'
		);
		$ok=alexaResponse($response,$params);
		exit;
	break;
	case 'repeat that':
	case 'what was that':
		$reprompt="Pick a letter.";
		$response=alexaGetLastResponse();
		$params=array('listen'=>1,'reprompt'=>$reprompt);
		$ok=alexaResponse($response,$params);
	break;
	case  'give me a hint':
	case  'what does it mean':
	case  'define the word':
	case  'tell me the definition':
		$rec=commonGetDictionaryWord(array('word'=>$alexa['user']['word']));
		$response="It means, {$rec['definition']}., Pick a letter.";
		$params=array('listen'=>1,'reprompt'=>'Pick a letter.');
		$ok=alexaResponse($response,$params);
	break;
	case 'quit':
	case 'i quit':
	case 'i give up':
		alexaClearUserVars();
		alexaSetUserVar('first_time',1);
		$word=strtoupper($alexa['user']['word']);
        $spelling=getSpelling($word);
		$response=commonGreeting('goodbye','general').", the word was {$word}, {$spelling}.";
		$ok=alexaResponse($response);
	break;
	case 'never mind':
	case 'enough':
	case 'goodbye':
	case 'stop':
 	case 'cancel':
 		alexaClearUserVars();
		alexaSetUserVar('first_time',1);
		$response=commonGreeting('goodbye','general');
		$ok=alexaResponse($response);
		break;
	case 'no':
	case 'no thanks':
	case 'no thank you':
		if(isset($alexa['user']['again'])){
			alexaClearUserVars();
			alexaSetUserVar('first_time',1);
			$response=commonGreeting('goodbye','general');
			$ok=alexaResponse($response);
			exit;
		}
		else{
        	$alexa['swim']['letter']='N';
		}
	break;
	case 'yes':
		if(!isset($alexa['user']['again'])){
			$alexa['swim']['letter']='Y';
		}
	break;
}


//set playmethod?
if(isset($alexa['swim']['playmethod']) && !isset($alexa['user']['playmethod'])){
	alexaSetUserVar('playmethod',$alexa['swim']['playmethod']);
}
//set methodfilter?
if(isset($alexa['user']['methodfilter']) && $alexa['user']['methodfilter']=='?'){
	alexaSetUserVar('methodfilter','');
}
if(isNum($alexa['swim']['length']) && !isset($alexa['user']['methodfilter'])){
	alexaSetUserVar('methodfilter',$alexa['swim']['length']);
}
elseif(isset($alexa['swim']['category']) && !isset($alexa['user']['methodfilter'])){
	switch(strtolower($alexa['swim']['category'])){
		case 'parts of speech':
			$categorylist="Adjectives, Adverbs, Nouns or Verbs";
    		$response="{$alexa['swim']['category']} consists of the following categories:, {$categorylist}., Which one would you like?";
    		$params=array('listen'=>1,'reprompt'=>"Which category?");
    		$ok=alexaResponse($response,$params);
		case 'the natural world':
			$categorylist="Anatomy, Fruits and Vegetables, Periodic Elements, Trees or Wildlife";
    		$response="{$alexa['swim']['category']} consists of the following categories:, {$categorylist}., Which one would you like?";
    		$params=array('listen'=>1,'reprompt'=>"Which category?");
    		$ok=alexaResponse($response,$params);
		case 'the kitchen sink':
			$categorylist="Colors, Cooking Terms, Musical Instruments, Occupations, or Transportation ";
    		$response="{$alexa['swim']['category']} consists of the following categories:, {$categorylist}., Which one would you like?";
    		$params=array('listen'=>1,'reprompt'=>"Which category?");
    		$ok=alexaResponse($response,$params);
		break;
    	case 'list categories':
    		$categorylist=getCategories();
    		$response="The categories are, {$categorylist}., Pick a category.";
    		$params=array('listen'=>1,'reprompt'=>"Which category?");
    		$ok=alexaResponse($response,$params);
    	break;
    	default:
    		alexaSetUserVar('methodfilter',$alexa['swim']['category']);
    	break;
	}
}
if(!isset($alexa['user']['playmethod'])){
	if(isset($alexa['swim']['category'])){
		alexaSetUserVar('playmethod','category');
	}
	elseif(isset($alexa['swim']['length'])){
		alexaSetUserVar('playmethod','word length');
	}
}
if(!isset($alexa['user']['playmethod'])){
	$response='<speak>';
	if(!isset($alexa['user']['first_time'])){
		$response.= "Welcome to Hangman, provided by skill say dot com. ";
	}
	$response.="How would you like to play? By word length? Or by category?</speak>";
	$content=<<<ENDOFRESPONSE
		Welcome to Hangman, provided by Skillsai.com.  
		For more information about this game visit http://www.skillsai.com/hangman.
		How would you like to play? By word length or category?
ENDOFRESPONSE;
	$letters_left=getLettersLeft();
	$params=array(
		'card' => array(
			'title' 		=> 'Hangman',
			'content'		=> $content,
			'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/newgame-large.png",
			'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/newgame-small.png",

		),
		'listen'=>1,
		'reprompt'=>'How would you like to play? Word length? or category?'
	);

	$ok=alexaResponse($response,$params);
}

if(!isset($alexa['user']['methodfilter'])){
	switch(strtolower($alexa['user']['playmethod'])){
    	case 'word length':
    	case 'length':
    		$response='How many letters would you like to try?';
    		$params=array('listen'=>1,'reprompt'=>$response);
    	break;
    	case 'list categories':
    		$categorylist=getCategories();
    		$response="The categories are, {$categorylist}., Pick a category.";
    		$params=array('listen'=>1,'reprompt'=>"Which category?");
    		$ok=alexaResponse($response,$params);
    	break;
    	default:
    		$categorylist=getCategories();
    		if(isset($alexa['swim']['letter'])){
				$response="I do not currently have a category for '{$alexa['swim']['letter']}'. Go to your alexa app to see the category list.";
			}
    		else{
    			$response="Categories are in three groups. Parts of Speech, The Natural World, or,  The Kitchen Sink. Which group would you like? ";
			}
    		$letters_left=getLettersLeft();
    		$params=array(
				'card' => array(
					'title' 		=> 'Hangman',
					'content'		=> "What category? Categories are in three groups as follows:\r\n -- Parts of Speech: Adjectives, Adverbs, Nouns or Verbs\r\n -- The Natural World: Anatomy, Fruits and Vegetables, Periodic Elements, Trees or Wildlife\r\n -- The Kitchen Sink: Colors, Cooking Terms, Musical Instruments, Occupations, or Transportation",
					'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/newgame-small.png",
					'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/newgame-large.png",
				),
				'listen'=>1,
				'reprompt'=>"Choose a category. To hear the categories, just say, list categories."
			);
    	break;
	}
	$ok=alexaResponse($response,$params);
}

if(!isset($alexa['user']['word'])){
	//so I now have a playmethod and a methodfilter -- pick a word.
	switch(strtolower($alexa['user']['playmethod'])){
    	case 'word length':
    	case 'length':

    		if(!isNum($alexa['user']['methodfilter'])){
            	$response="{$alexa['user']['methodfilter']} is not a number. Pick a number between 3 and 31.";
            	$params=array('listen'=>1,'reprompt'=>$response);
            	$ok=alexaResponse($response,$params);
            	exit;
			}
			if($alexa['user']['methodfilter'] > 31){
            	$response="{$alexa['user']['methodfilter']} is too large of a number. Pick a number between 3 and 31.";
            	$params=array('listen'=>1,'reprompt'=>$response);
            	$ok=alexaResponse($response,$params);
            	alexaSetUserVar('methodfilter','');
            	exit;
			}
    		$where="length(word)={$alexa['user']['methodfilter']}";
    		$response="Okay. I have chosen a word";
    	break;
    	case 'difficulty level':
    	case 'difficulty':
    		switch(strtolower($alexa['user']['methodfilter'])){
		    	case 'medium':
		    	case 2:
		    		$difficulty=2;
		    	break;
		    	case 'hard':
		    	case 3:
		    		$difficulty=3;
		    	break;
		    	case 'genius':
		    	case 4:
		    		$difficulty=4;
		    	break;
		    	case 'impossible':
		    	case 5:
		    		$difficulty=5;
		    	break;
		    	default:
		    		//easy
		    		$difficulty=1;
		    	break;
			}
    		$where="difficulty={$difficulty}";
    		$response="Okay. I have chosen a word at the {$alexa['user']['methodfilter']} level";
    	break;
    	default:
    		//category
    		$where="category='{$alexa['user']['methodfilter']}'";
    		$response="Okay. I have chosen a word in the {$alexa['user']['methodfilter']} category";
    	break;
	}
	$wordopts=array(
		'-random'=>1,
		'-where'=>$where
	);
	$wordrec=commonGetDictionaryWord($wordopts);
	alexaSetUserVar('debug','');
	alexaSetUserVar('word',$wordrec['word']);
	$wordlen=strlen($alexa['user']['word']);

	$response .= " that is {$wordlen} letters long.\r\n";
	alexaSetUserVar('again','');
	$puzzle=getPuzzle();
	$letters_left=getLettersLeft();
	$response .= ' Pick a letter.';
	if(!isset($alexa['user']['first_time'])){
		alexaSetUserVar('first_time',1);
		$response .= ' You can say the letter, or any word that starts with the letter. For example, say, "elephant", instead of the letter E.';
	}
 	$params=array(
		'card' => array(
			'title' 		=> $puzzle,
			'content'		=> $response,
			'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/newgame-small.png",
			'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/newgame-large.png",
		),
		'listen'=>1,
		'reprompt'=>'Pick a letter.'
	);
	$ok=alexaResponse($response,$params);
}
if(!isset($alexa['swim']['letter'])){
	if(isset($alexa['swim']['category'])){
		$alexa['swim']['letter']=$alexa['swim']['category'];
	}
	elseif(isset($alexa['swim']['length'])){
		$alexa['swim']['letter']=$alexa['swim']['length'];
	}
	elseif(isset($alexa['swim']['playmethod'])){
		$alexa['swim']['letter']=$alexa['swim']['playmethod'];
	}
}
if(!isset($alexa['swim']['letter'])){
	$response='Pick a letter';
	$params=array('listen'=>1,'reprompt'=>'Pick a letter.');
	$ok=alexaResponse($response,$params);
}
//should be getting letters at this point
//fix some values that may sound like letters

switch(strtolower($alexa['swim']['letter'])){
	case 'say the phonetic alphabet':
	case 'phonetic alphabet':
	case 'say the alphabet':
	case 'repeat the phonetic alphabet':
	case 'repeat the alphabet':
	case 'what is the phonetic alphabet':
	case 'whats the phonetic alphabet':
		$phonetics=getPhoneticAlphabet();
		$phonetics_list=implode(', ',array_keys($phonetics));
		$parts=array();
		foreach($phonetics as $phonetic=>$letter){
        	$parts[]=strtoupper($letter).' = '.ucwords($phonetic);
		}
		$card=implode(", ",$parts);
		$response="The phonetic alphabet is: {$phonetics_list}. I have sent this list to your amazon app for your reference. Pick a letter.";
		$params=array(
			'card' => array(
				'title' 		=> 'The Phonetic Alphabet',
				'content'		=> $card,
			),
			'listen'=>1,
			'reprompt'=>'Pick a letter.'
		);
		$ok=alexaResponse($response,$params);
		exit;
	break;
	case 'quit':
	case 'i quit':
	case 'i give up':
	case 'never mind':
	case 'enough':
	case 'goodbye':
	case 'stop':
 	case 'cancel':
	case 'no':
	case 'no thanks':
	case 'no thank you':
		if(isset($alexa['user']['again'])){
			alexaClearUserVars();
			alexaSetUserVar('first_time',1);
			$response=commonGreeting('goodbye','general');
			$ok=alexaResponse($response);
		}
		else{
        	$alexa['swim']['letter']='N';
		}
		exit;
	break;
	case 'yes':
	case 'please':
	case 'yes please':
		if(isset($alexa['user']['again'])){
			alexaSetUserVar('again','');
			alexaSetUserVar('playmethod','');
			$response='How would you like to play? By word length or category?';
			$params=array('listen'=>1,'reprompt'=>'By word length or category?');
			$ok=alexaResponse($response,$params);
		}
		else{
        	$alexa['swim']['letter']='Y';
		}
		break;
	default:
		//alexaSetUserVar('debug',$alexa['swim']['letter']);
		$letter=getLetter($alexa['swim']['letter']);
		if(!strlen($letter) || strlen($letter) != 1){
			//did not understand the letter
			$response='What was that?';
			$params=array('listen'=>1,'reprompt'=>'Pick a letter.');
			$ok=alexaResponse($response,$params);
			break;
		}
		if(metaphone($alexa['swim']['letter'])==metaphone($alexa['user']['word'])){
        	//they won
        	$tries_left=getTriesLeft();
			$letters_used=getLettersUsed();
			$tries_name=$tries_left > 1?'tries':'try';
        	$image_name=getImageName();
        	$word=strtoupper($alexa['user']['word']);
        	$spelling=getSpelling($word);
        	$soundfile=commonGetSoundfile('win');
			$response="<speak>Your guessed it right. You win! <audio src=\"{$soundfile}\" /> The word was {$word}, {$spelling}. ,Would you like to play again?</speak>";
            $rec=commonGetDictionaryWord(array('word'=>$alexa['user']['word']));
			$params=array(
				'card' => array(
					'title' 		=> $puzzle,
					'content'		=> "You won! The word was {$word}.\r\n\r\n Definition: {$rec['definition']}.",
					'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-small.png",
					'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-large.png",
				),
				'listen'=>1,
				'reprompt'=>'Would you like to play again?'
			);
			alexaClearUserVars();
			alexaSetUserVar('first_time',1);
			alexaSetUserVar('again',1);
			$ok=alexaResponse($response,$params);
			break;
		}
		//have they used this letter already?
		if(isset($alexa['user']['used']) && stringContains($alexa['user']['used'],$letter)){
			//already used that letter
			$response="You have already used the letter, {$letter}. ,Pick a different letter.";
			$params=array('listen'=>1,'reprompt'=>'Pick a letter.');
			$ok=alexaResponse($response,$params);
			break;
		}
		//add this letter to used
		alexaSetUserVar('used',$alexa['user']['used'].$letter);
		$puzzle=getPuzzle();
		$verbal_puzzle=getVerbosePuzzle();
		$letters_left=getLettersLeft();
		if(strtolower($alexa['swim']['letter'])==strtolower($alexa['user']['word']) || metaphone($alexa['swim']['letter'])==metaphone($alexa['user']['word'])){
        	//they won
        	$tries_left=getTriesLeft();
			$letters_used=getLettersUsed();
			$tries_name=$tries_left > 1?'tries':'try';
        	$image_name=getImageName();
        	$word=strtoupper($alexa['user']['word']);
        	$spelling=getSpelling($word);
        	$soundfile=commonGetSoundfile('win');
			$response="<speak>You guessed correctly! You win! <audio src=\"{$soundfile}\" /> The word was {$word}, {$spelling}. ,Would you like to play again?</speak>";
            $rec=commonGetDictionaryWord(array('word'=>$alexa['user']['word']));
			$params=array(
				'card' => array(
					'title' 		=> $word,
					'content'		=> "You won! The word was {$word}.\r\n\r\n Definition: {$rec['definition']}.",
					'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-small.png",
					'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-large.png",
				),
				'listen'=>1,
				'reprompt'=>'Would you like to play again?'
			);
			alexaClearUserVars();
			alexaSetUserVar('first_time',1);
			alexaSetUserVar('again',1);
			$ok=alexaResponse($response,$params);
			break;
		}
		elseif(strtolower($puzzle)==strtolower($alexa['user']['word'])){
        	//they won
        	$tries_left=getTriesLeft();
			$letters_used=getLettersUsed();
			$tries_name=$tries_left > 1?'tries':'try';
        	$image_name=getImageName();
        	$word=strtoupper($alexa['user']['word']);
        	$spelling=getSpelling($word);
        	$soundfile=commonGetSoundfile('win');
			$response="<speak>The letter, {$letter}, completes the word. You win! <audio src=\"{$soundfile}\" /> The word was {$word}, {$spelling}. ,Would you like to play again?</speak>";
            $rec=commonGetDictionaryWord(array('word'=>$alexa['user']['word']));
			$params=array(
				'card' => array(
					'title' 		=> $puzzle,
					'content'		=> "You won! The word was {$word}.\r\n\r\n Definition: {$rec['definition']}.",
					'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-small.png",
					'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-large.png",
				),
				'listen'=>1,
				'reprompt'=>'Would you like to play again?'
			);
			alexaClearUserVars();
			alexaSetUserVar('first_time',1);
			alexaSetUserVar('again',1);
			$ok=alexaResponse($response,$params);
			break;
		}
		elseif(!stringContains($alexa['user']['word'],$letter)){
			alexaSetUserVar('miss',$alexa['user']['miss'].$letter);
			$tries_left=getTriesLeft();
			$letters_used=getLettersUsed();
			$image_name=getImageName();
			$tries_name=$tries_left > 1?'tries':'try';
			if($tries_left > 0){
				$response="The letter, {$letter}, is not in the word. You have {$tries_left} {$tries_name} left. So far you have {$verbal_puzzle}. Pick a letter.";
				$params=array(
					'card' => array(
						'title' 		=> $puzzle,
						'content'		=> "The letter, {$letter}, is not in the word. You have {$tries_left} {$tries_name} left. Pick a letter.\r\n Letters guessed so far: {$letters_used}.\r\n Letters Left: {$letters_left}.",
						'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-small.png",
						'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-large.png",
					),
					'listen'=>1,
					'reprompt'=>'Pick a letter.'
				);
			}
			else{
            	//they lose
            	$word=strtoupper($alexa['user']['word']);
            	$spelling=getSpelling($word);
            	$soundfile=commonGetSoundfile('lose');
            	$response="<speak>The letter, {$letter}, is not in the word. You lost. <audio src=\"{$soundfile}\" /> The word was {$word}, {$spelling}., Would you like to play again?</speak>";
            	$rec=commonGetDictionaryWord(array('word'=>$alexa['user']['word']));
				$params=array(
					'card' => array(
						'title' 		=> $puzzle,
						'content'		=> "You lost. The word was {$word}. \r\nLetters used: {$letters_used}. \r\n\r\n Definition: {$rec['definition']}.",
						'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/lose-small.png",
						'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/lose-large.png",
					),
					'listen'=>1,
					'reprompt'=>'Would you like to play again?'
				);
				alexaClearUserVars();
				alexaSetUserVar('first_time',1);
				alexaSetUserVar('again',1);
			}
			$ok=alexaResponse($response,$params);
			break;
		}
		else{
        	//the letter is in the word
        	$tries_left=getTriesLeft();
        	$letters_used=getLettersUsed();
        	$image_name=getImageName();
        	$tries_name=$tries_left > 1?'tries':'try';
        	$times_name=$tries_left > 1?'times':'time';
        	$response="The letter, {$letter}, is in the word. So far you have {$verbal_puzzle}. You can still miss {$tries_left} more {$times_name}. Pick another letter.";
			$params=array(
				'card' => array(
					'title' 		=> $puzzle,
					'content'		=> "Correct guess!\r\n Letters guessed so far: {$letters_used}.\r\n Letters left: {$letters_left}.",
					'image_small'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-small.png",
					'image_large'	=> "https://www.skillsai.com/cors/skills/hangman/{$image_name}-large.png",
				),
				'listen'=>1,
				'reprompt'=>'Pick a letter.'
			);
			$ok=alexaResponse($response,$params);
		}
	break;
}

$response='I did not understand? What was that?';
$params=array('listen'=>1,'reprompt'=>$response);
$ok=alexaResponse($response,$params);
exit;
?>
