<?php
function pageRunTests($params){
	$errors=array();
	$schema=json_decode(trim($params['schema']),true);
	$utterances=preg_split('/[\r\n]+/',trim($params['utterances']));
	/*
		schema_test1
		All intents must have sample utterances
	*/
	if(!isset($schema['intents'][0])){
		$errors['schema_test1'][]='invalid schema - not intents specified';
	}
	else{
		foreach($schema['intents'] as $i=>$intent){
			$found=0;
			foreach($utterances as $i=>$line){
				if(stringBeginsWith($line,$intent['intent'])){
                	$found++;
                	break;
				}
			}
			if($found==0){
				$errors['schema_test1'][]="Intent '{$intent['intent']}' is missing in utterances";
			}
		}
	}

	/*
		utterance_test1

		Each slot should be used only once within a sample utterance
		Examples of a bad line:
			ChooseReport {Prefix} {Prefix} {Report} {Group} for {Postcode}
	*/
	foreach($utterances as $i=>$line){
		preg_match_all('/\{(.+?)\}/ism',$line,$m,PREG_PATTERN_ORDER);
		if(isset($m[1])){
        	$slots=array();
        	$dups=0;
        	foreach($m[1] as $slot){
            	$slots[$slot]+=1;
            	if($slots[$slot] > 1){$dups+=1;}
			}
			if($dups > 0){
				$errors['utterance_test1'][]="line {$i} contains duplicate slots: <br /> -- {$line}";
			}
		}
	}
	return $errors;
}
?>
