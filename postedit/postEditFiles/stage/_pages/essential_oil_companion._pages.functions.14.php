<?php
function essentialOilCompanionGetSymptoms($oil){
	$oil_rec=getDBRecord(array(
		'-table'	=> 'essential_oil_companion_oils',
		'oil'		=> $oil
	));
	$recs=getDBRecords(array(
		'-table'	=> 'essential_oil_companion_symptoms',
		'-where'	=> "_id in (select symptom_id from essential_oil_companion_matches where oil_id={$oil_rec['_id']})",
		'-index'	=> 'symptom',
		'-order'	=> 'symptom'
	));
	return $recs;
}
function essentialOilCompanionGetOils($symptom){
	$symptom_rec=getDBRecord(array(
		'-table'	=> 'essential_oil_companion_symptoms',
		'symptom'		=> $symptom
	));
	$recs=getDBRecords(array(
		'-table'	=> 'essential_oil_companion_oils',
		'-where'	=> "_id in (select oil_id from essential_oil_companion_matches where symptom_id={$symptom_rec['_id']})",
		'-index'	=> 'oil',
		'-order'	=> 'oil'
	));
	return $recs;
}
function essentialOilCompanionGetSession(){
	global $alexa;
	$rec=getDBRecord(array(
		'-table'=>'essential_oil_companion_sessions',
		'active'=>1,
		'userid'=>$alexa['userid']
	));
	if(isset($rec)){return $rec;}
	$id=addDBRecord(array(
		'-table'=>'essential_oil_companion_sessions',
		'active'=>1,
		'userid'=>$alexa['userid']
	));
	$rec=getDBRecord(array(
		'-table'=>'essential_oil_companion_sessions',
		'_id'=>$id
	));
	if(isset($rec)){return $rec;}
	return null;
}
function essentialOilCompanionUpdateSession($rec,$updates=array()){
	if(!count($updates)){return;}
	foreach($updates as $k=>$v){
    	$rec[$k]=$v;
	}
	$updates['-table']='essential_oil_companion_sessions';
	$updates['-where']="_id={$rec['_id']}";
	$ok=editDBRecord($updates);
	return $rec;
}
?>
