<?php
/*
	Fishbowl wrapper
	loadExtras('fishbowl');
	$fbapi = new FishbowlAPI("localhost"[,28192]);
	$fbapi->setAppInfo('key','name','description');
	$fbapi->login($user,$pass);
	// Get customer name list
	$fbapi->getCustomer();
	echo printValue($fbapi->result);

*/
$progpath=dirname(__FILE__);
require_once("{$progpath}/fishbowl/fishbowlAPI.class.php");
?>