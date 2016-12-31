<?php
function skapiLog($params=array()){
	global $skapi_id;
	$params['-table']='skapi_log';
	if(!isNum($skapi_id)){
    	$skapi_id=addDBRecord($params);
	}
	else{
		$params['-where']="_id={$skapi_id}";
		$ok=editDBRecord($params);
	}
	return $skapi_id;
}
?>
