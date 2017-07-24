<?php
function settingsGetValues(){
	$recs=getDBRecords(array(
		'-table'=>'_settings',
		'user_id'=>0,
		'-index'=>'key_name',
		'-fields'=>'_id,key_name,key_value'
	));
	foreach($recs as $key=>$rec){
		if($rec['key_value']==1){
			$recs[$key]['checked']=' checked';
		}
		elseif($rec['key_value']==0){$recs[$key]['checked']='';}
	}
	return $recs;
}
function settingsSyncSites($n,$v){
	$opts=array();
	global $ALLCONFIG;
	foreach($ALLCONFIG as $name=>$conf){
		$opts[$name]="{$name} ({$conf['dbname']})";
	}
	return buildFormSelect($n,$opts,array('message'=>'---select---','value'=>$v));
}
?>
