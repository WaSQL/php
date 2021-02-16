<?php
	global $CONFIG;
	global $SETTINGS;
	loadExtras('zipcodes');
	if(!isset($CONFIG['admin_menu_color'])){
		$CONFIG['admin_menu_color']='gray';
	}
	setView('default');
	if(isset($_REQUEST['country_codes'])){
		if(!isDBTable('zipcodes')){
			zipcodesCreateTable();
		}
		$results=zipcodesImportCountry($_REQUEST['country_codes']);
	}
?>