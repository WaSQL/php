<?php

function visitorsGetSession(){
	global $alexa;
	$rec=getDBRecord(array(
		'-table'=>'visitors_sessions',
		'active'=>1,
		'userid'=>$alexa['userid']
	));
	if(isset($rec)){return $rec;}
	$id=addDBRecord(array(
		'-table'=>'visitors_sessions',
		'active'=>1,
		'userid'=>$alexa['userid']
	));
	$rec=getDBRecord(array(
		'-table'=>'visitors_sessions',
		'_id'=>$id
	));
	if(isset($rec)){return $rec;}
	return null;
}
function visitorsUpdateSession($rec,$updates=array()){
	if(!count($updates)){return;}
	foreach($updates as $k=>$v){
    	$rec[$k]=$v;
	}
	$updates['-table']='visitors_sessions';
	$updates['-where']="_id={$rec['_id']}";
	$ok=editDBRecord($updates);
	return $rec;
}
?>
