<?php
	global $CONFIG;
	global $SETTINGS;
	loadExtras('zipcodes');
	if(!isset($CONFIG['admin_menu_color'])){
		$CONFIG['admin_menu_color']='w_gray';
	}
	setView('default');
	if(isset($_REQUEST['country_codes']) && is_array($_REQUEST['country_codes'])){
		// Validate country codes - must be 2 letter codes
		$validated_codes=array();
		foreach($_REQUEST['country_codes'] as $code){
			$code=preg_replace('/[^a-z]/is','',$code);
			if(strlen($code)==2){
				$validated_codes[]=$code;
			}
		}
		if(count($validated_codes) > 0){
			if(!isDBTable('zipcodes')){
				zipcodesCreateTable();
			}
			$results=zipcodesImportCountry($validated_codes);
		}
	}
?>