<?php
function getCategories($arr=0){
	$query="select distinct category as cat from dictionary order by category";
	$recs=getDBRecords(array('-query'=>$query,'-index'=>'cat'));
	if($arr==1){return $recs;}
	return implode(', ',array_keys($recs));
}
function getTriesLeft(){
	global $alexa;
	return 6-strlen($alexa['user']['miss']);
}
function getPuzzle(){
	global $alexa;
	$word_letters=str_split(strtoupper($alexa['user']['word']));
	$used_letters=str_split(strtoupper($alexa['user']['used']));
	$puzzle=array();
	$left=0;
	foreach($word_letters as $letter){
    	if(in_array($letter,$used_letters)){$puzzle[]=$letter;}
    	else{
			$puzzle[]='_ ';
			$left++;
		}
	}
	if($left==0){return strtoupper($alexa['user']['word']);}
	$puzzle=implode('',$puzzle);
	return strtoupper($puzzle);
}
function getSpelling($word){
	$parts=str_split($word);
	return implode(', ',$parts);
}
function getVerbosePuzzle(){
	global $alexa;
	$parts=str_split(getPuzzle());
	//determine position of blanks
	$blanks=array();
	foreach($parts as $i=>$letter){
    	if($letter=='_'){$blanks[$i]=1;}
	}
	$puzzle=array();
	$lcnt=0;
	$blanks=0;
	foreach($parts as $i=>$letter){
    	if($letter=='_'){
        	$blanks++;
		}
    	elseif($letter!=' '){
			$lcnt++;
			if($blanks > 0){
				if($blanks==1){$puzzle[]=", blank";}
				else{$puzzle[]=", {$blanks} blanks";}
            	$blanks=0;
			}
			$puzzle[]=", {$letter} ";
		}
	}
	if($blanks > 0){
        if($blanks==1){$puzzle[]=", blank";}
		else{$puzzle[]=", {$blanks} blanks";}
        $blanks=0;
	}
	if($lcnt==0){return 'no letters in the word';}
	$puzzle=implode('',$puzzle);
	return $puzzle;
}

function getImageName(){
	global $alexa;
	switch(strlen($alexa['user']['miss'])){
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
        	$image_name='miss'.strlen($alexa['user']['miss']);
        break;
        default:
        	$image_name='newgame';
        break;
	}
	return $image_name;
}
function getLettersUsed(){
	global $alexa;
	$used_letters=str_split(strtoupper($alexa['user']['used']));
	return implode(' ',$used_letters);
}
function getLettersLeft(){
	global $alexa;
	$used_letters=str_split(strtoupper($alexa['user']['used']));
	$all_letters=range('A','Z');
	$letters_left=array();
	foreach($all_letters as $letter){
    	if(in_array($letter,$used_letters)){$letters_left[]='_';}
    	else{$letters_left[]=$letter;}
	}
	$letters_left=implode('',$letters_left);
	return $letters_left;
}
function getLetter($str){
	$val=strtolower($str);
	//alexaSetUserVar('getletter_1',$val);
	$val=preg_replace('/[^a-z]+/i','',$val);
	//alexaSetUserVar('getletter_2',$val);
	if(strlen($val)==1){return strtoupper($val);}
	$phonetics=getPhoneticAlphabet();
	//is it a phonetic?
	if(isset($phonetics[$val])){$val=$phonetics[$val];}
	else{
		switch(strtolower($val)){
			case '8':
			case 'egg':
			case 'hey':
				$val='a';
			break;
			case 'bee':
			case 'be':
			case '3':
				$val='b';
			break;
			case 'sea':
			case 'siri':
				$val='c';
			break;
			case 'elmer':
				$val='e';
			break;
			case 'frank':
				$val='f';
			break;
			case 'gee':
				$val='g';
			break;
			case 'lie':
			case 'eye':
				$val='i';
			break;
			case 'jay':
				$val='j';
			break;
			case 'oh':
				$val='o';
			break;
			case 'pee':
				$val='p';
			break;
			case 'are':
				$val='r';
			break;
			case 'tee':
				$val='t';
			break;
			case 'you':
				$val='u';
			break;
			case 'why':
				$val='y';
			break;
			default:
				//get first letter of word
				$val=substr($val,0,1);
			break;
		}
	}
	return strtoupper($val);
}
function getPhoneticAlphabet(){
	return array(
		'alpha'		=> 'a',
		'bravo'		=> 'b',
		'charlie'	=> 'c',
		'delta'		=> 'd',
		'echo'		=> 'e',
		'foxtrot'	=> 'f',
		'golf'		=> 'g',
		'hotel'		=> 'h',
		'india'		=> 'i',
		'juliet'	=> 'j',
		'kilo'		=> 'k',
		'lima'		=> 'l',
		'mike'		=> 'm',
		'november'	=> 'n',
		'oscar'		=> 'o',
		'papa'		=> 'p',
		'quebec'	=> 'q',
		'romeo'		=> 'r',
		'sierra'	=> 's',
		'tango'		=> 't',
		'uniform'	=> 'u',
		'victor'	=> 'v',
		'whiskey'	=> 'w',
		'xray'		=> 'x',
		'yankee'	=> 'y',
		'zulu'		=> 'z'
	);
}
?>
