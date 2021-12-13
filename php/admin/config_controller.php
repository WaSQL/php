<?php
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(!isset($CONFIG['admin_color']) || !strlen($CONFIG['admin_color'])){
		$CONFIG['admin_color']='w_gray';
	}
	switch(strtolower($_REQUEST['func'])){
		case 'delete':
			$id=(integer)$_REQUEST['id'];
			$ok=delDBRecordById('_config',$id);
			setView(array('showlist_ajax','config_menu'),1);
			return;
		break;
		case 'addedit':
			$id=(integer)$_REQUEST['id'];
			setView('addedit',1);
			return;
		break;
		case 'showlist':
			$category=$_REQUEST['category'];
			setView('showlist',1);
			if(isset($_REQUEST['config_menu'])){
				setView('config_menu');
			}
			return;
		break;
		case 'showlist_ajax':
			$category=$_REQUEST['category'];
			setView('showlist_ajax',1);
			return;
		break;
		case 'config_menu':
			$categories=getDBRecords("select count(*) cnt,ifnull(category,'misc') as name from _config group by category order by ifnull(category,'misc')");
			setView('config_menu',1);
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
