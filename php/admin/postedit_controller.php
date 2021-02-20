<?php
	global $CONFIG;
	global $SETTINGS;
	if(!isset($CONFIG['admin_menu_color'])){
		$CONFIG['admin_menu_color']='gray';
	}
	setView('default');
?>