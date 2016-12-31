<?php
function pageParseDictLine($params){

	//06471242 10 n 01 capitulation 1 001 @ 06470073 n 0000 | a document containing the terms of surrender
	if(!stringContains($params['line'],'|')){return;}
	global $counter;
	$counter++;
	$opts=array();
	$opts['type']=getFileExtension($params['file']);
	//remove first 18 characters
	$opts['line']=substr($params['line'],17);
	list($first,$last)=preg_split('/\|/',$opts['line'],2);
	list($opts['word'],$firstb)=preg_split('/\ /',$first,2);
	//usage and definition
	$opts['usage']=array();
	$opts['definition']=array();
	$parts=preg_split('/\;/',$last);
	foreach($parts as $part){
    	if(stringContains($part,'"')){
        	$opts['usage'][]=$part;
		}
		else{
        	$opts['definition'][]=$part;
		}
	}
	$opts['definition']=implode('; ',$opts['definition']);
	$opts['usage']=implode('; ',$opts['usage']);
	//synonyms
	unset($m);
	preg_match_all('/0\ ([a-z\-]+)/i',$firstb,$m);
	if(isset($m[1][0])){
    	$opts['sentences']=array();
    	foreach($m[1] as $s){
        	if(strlen($s) > 2){$opts['sentences'][]=$s;}
		}
		$opts['sentences']=implode(', ',$opts['sentences']);
	}
	//append to record if it exists
	$rec=getDBRecord(array(
		'-table'=>'wordnet',
		'type'	=> $opts['type'],
		'word'	=> $opts['word']
	));
	if(isset($rec['_id'])){
    	$ok=editDBRecord(array(
			'-table'=>'wordnet',
			'-where'=>"_id={$rec['_id']}",
		));
	}
	else{
		$opts['-table']='wordnet';
		$id=addDBRecord($opts);
		if(!isNum($id)){
        	echo $id.printValue($opts);exit;
		}
	}
}
?>
