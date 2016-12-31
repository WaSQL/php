<?php
/* $recs=getDBRecords(array(
	'-table'=>"scripture_mastery_scriptures"
));
foreach($recs as $rec){
	$km=metaphone(str_replace(' ','',$rec['keyphrase']));
	$opts=array(
		'-table'=>'scripture_mastery_scriptures',
		'-where'=>"_id={$rec['_id']}",
		'keyphrase_metaphone'=>$km
	);
	if(preg_match('/^([0-9\ ]*?)([a-z\&\ \-]+)([0-9\:\,\ \-]+)$/i',$rec['reference'],$m)){
		$rm=metaphone(str_replace(' ','',$rec['reference']));
		$opts['reference_metaphone']=preg_replace('/[^0-9]+/','',$m[1]).$rm.preg_replace('/[^0-9]+/','',$m[3]);

	}
	$ok=editDBRecord($opts);
} */
function scriptureMasteryPraise(){
	$praises=array(
		'good job!',
		'awesome!',
		'wonderful!',
		'amazing!',
		'yes!',
		'way to go!'
	);
	$i=array_rand($praises,1);
	return $praises[$i];
}
function scriptureMasteryPlay($scripture=array()){
	global $session;
	global $response;
	$fields=array('keyphrase','reference');
	if(isset($scripture['_id'])){
		$val=$scripture[$session['ask']];
    	$response .= "The {$session['ask']} was, {$val}.";
	}
	$xscripture=$scripture;
	$scripture=scriptureMasteryGetScripture($session['volume']);
	$i=rand(0,1);
	//$i=1;
	switch($i){
        case 0:
        	$ask=$fields[0];
        	$val=$scripture[$fields[1]];
        break;
        case 1:
        	$ask=$fields[1];
        	$val=$scripture[$fields[0]];
        break;
	}
	$session=scriptureMasteryUpdateSession($session,array(
		'ask'=>$ask,
		'val'=>$val,
		'scripture_id'=>$scripture['_id']
	));
	$params=array(
		'listen'		=> true,
		'reprompt'		=> "Give me the {$ask} for {$val}"
	);
	if(isset($xscripture['_id'])){
    	//add a card
    	$content='';
    	if(isset($xscripture['debug'])){
        	$content.="DEBUG:{$xscripture['debug']}\n";
		}
        $content.="Keyphrase: {$xscripture['keyphrase']}\nScripture: {$xscripture['scripture']}";
    	$params['card']=array(
			'title'	=> $xscripture['reference'],
			'content'=>$content
		);
	}
	$val=str_replace(':',' ',$val);
	$val=str_replace('-',' through',$val);
	$response .= " Give me the {$ask} for {$val}";
	$ok=alexaResponse($response,$params);
}
function scriptureMasteryGetScriptureById($id){
	return getDBRecord(array(
		'-table'	=> 'scripture_mastery_scriptures',
		'_id'		=> $id
	));
}
function scriptureMasteryGetScripture($book){
	global $session;
	$where='';
	if(strtolower($book)!='all volumes'){
		$where.="and book='{$book}'";
	}
	$correct_ids=scriptureMasteryGetCorrectIds();
	if(count($correct_ids)){
    	$idstr=implode(',',$correct_ids);
    	$where .= " and _id not in ({$idstr})";
	}
    $query=<<<ENDOFQUERY
		SELECT *
	FROM scripture_mastery_scriptures
	WHERE 1=1
	{$where}
	ORDER BY RAND()
	LIMIT 1
ENDOFQUERY;
	return getDBRecord(array('-query'=>$query));
}
function scriptureMasteryGetCorrectIds(){
	global $session;
	if(!strlen($session['correct_ids'])){return array();}
	$idstr=preg_replace('/^\:+/','',trim($session['correct_ids']));
	return preg_split('/\:+/',$idstr);
}
function scriptureMasteryGetSession(){
	global $alexa;
	$rec=getDBRecord(array(
		'-table'=>'scripture_mastery_sessions',
		'active'=>1,
		'userid'=>$alexa['userid']
	));
	if(isset($rec)){return $rec;}
	$id=addDBRecord(array(
		'-table'=>'scripture_mastery_sessions',
		'active'=>1,
		'userid'=>$alexa['userid'],
		'sessionid'=>$alexa['sessionid']
	));
	$rec=getDBRecord(array(
		'-table'=>'scripture_mastery_sessions',
		'_id'=>$id
	));
	if(isset($rec)){return $rec;}
	return null;
}
function scriptureMasteryGetScore($session){
	$recs=getDBRecords(array(
		'-table'=>'scripture_mastery_sessions',
		'sessionid'=>$session['sessionid'],
		'-order'=>'_id desc',
		'-fields'=>'correct'
	));
	$score=0;
	foreach($recs as $rec){
    	if($rec['correct']==0){return $score;}
    	else{$score+=1;}
	}
	return $score;

}
function scriptureMasteryUpdateSession($rec,$updates=array()){
	if(!count($updates)){return;}
	foreach($updates as $k=>$v){
    	$rec[$k]=$v;
	}
	$updates['-table']='scripture_mastery_sessions';
	$updates['-where']="_id={$rec['_id']}";
	$ok=editDBRecord($updates);
	return $rec;
}
?>
