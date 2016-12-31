<?php
	$country='US';
	$states=pageGetStates($country);
	foreach($states as $state){
		$recs=pageGetCityByState($state);
		foreach($recs as $rec){
        	$xrec=getDBRecord(array(
				'-table'=>'cities',
				'country'=>$country,
				'state'=>$state,
				'city'=>$rec['city'],
				'county'=>$rec['county'],
				'zipcode'=>$rec['zipcode']
			));
        	if(!isset($xrec['_id'])){
            	$rec['-table']='cities';
            	$rec['country']=$country;
            	$rec=array_change_key_case($rec);
            	$id=addDBRecord($rec);
            	//echo printValue($id).printValue($rec);exit;
			}
		}
		//break;
	}
	echo "DONE";
	exit;
?>
