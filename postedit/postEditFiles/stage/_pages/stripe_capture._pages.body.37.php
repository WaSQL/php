<?php
	$client_name=$_REQUEST['passthru'][0];
	$client=getDBRecord(array(
		'-table'	=> 'clients',
		'code'		=> $client_name
	));
	if(!isset($client['_id'])){exit;}
	$ok=addDBRecord(array(
		'-table'	=> 'stripe_log',
		'client_id'	=> $client['_id'],
		'content'	=> file_get_contents("php://input"),
		'header'	=> json_encode(getallheaders())
	));
	echo "thanks";
	exit;

?>
