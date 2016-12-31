<?php
function picoParseAnswers(){
	global $alexa;
	$guess_parts=str_split($alexa['swim']['number']);
	$number_parts=str_split($alexa['user']['number']);
	$answers=array();
	foreach($guess_parts as $i=>$num){
		if($number_parts[$i]!=$num && in_array($num,$number_parts)){
	    	$answers['standard']['Pico']+=1;
	    	$answers['easy'][]='Pico';
	    	$answers['easy_titles'][]='P';
		}
		elseif($number_parts[$i]==$num){
			//Fermi means a digit is correct AND it's in the correct place value or location.
			$answers['standard']['Fermi']+=1;
			$answers['easy'][]='Fermi';
			$answers['easy_titles'][]='F';
		}
		else{
			$answers['standard']['Bagel']+=1;
			$answers['easy'][]='Bagel';
			$answers['easy_titles'][]='B';
		}
	}
	return $answers;
}
function picoRandomNumber($digits){
	//$numbers=getStoredValue("return picoGetNumbers({$digits});");
	//$numbers=range(1,str_repeat(9,$digits));
	$numbers=picoGetNumbers($digits);
	$i=array_rand($numbers);
	return $numbers[$i];
}
function picoGetNumbers($digits){
	$numbers=range(1,str_repeat(9,$digits));
	//remove any numbers with the duplicate numbers in it
	$cnt=count($numbers);
	for($x=0;$x<$cnt;$x++){
		if(stringContains($numbers[$x],0)){
			unset($numbers[$x]);
			continue;
		}
		if(strlen($numbers[$x]) != $digits){
			unset($numbers[$x]);
			continue;
		}
    	$parts=str_split($numbers[$x]);
    	$vcnts=array_count_values($parts);
    	$skip=0;
    	foreach($vcnts as $vcnt){
        	if($vcnt > 1){
            	$skip++;
			}
		}
		if($skip > 0){unset($numbers[$x]);}
	}
	return $numbers;
}
?>
