<?php
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(!isset($CONFIG['admin_color']) || !strlen($CONFIG['admin_color'])){
		$CONFIG['admin_color']='w_gray';
	}
	switch(strtolower($_REQUEST['func'])){
		case 'showlist':
			$category=$_REQUEST['category'];
			setView('showlist',1);
			return;
		break;
		case 'showlist_ajax':
			$category=$_REQUEST['category'];
			setView('showlist_ajax',1);
			return;
		break;
		default:
			$ok=configCheckSchema();
			setView('default',1);
			$categories=getDBRecords("select count(*) cnt,ifnull(category,'misc') as name from _config group by category order by ifnull(category,'misc')");
		break;
	}
	setView('default',1);
?>
