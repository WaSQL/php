<?php
function pageResponse($response='I do not have a response for that',$params=array()){
	$json=array(
		'speech'=>$response,
		'displayText'=>$response,
		'source'=>'Skillsai SalesTalk'
	);
	$jsontxt=json_encode($json);
	header('Content-type: application/json');
	echo $jsontxt;
	exit;
}
?>
