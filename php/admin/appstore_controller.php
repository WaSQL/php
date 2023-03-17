<?php
	global $CONFIG;
	global $SETTINGS;
	global $PAGE;
	if(!isset($CONFIG['admin_menu_color'])){
		$CONFIG['admin_menu_color']='w_gray';
	}
	switch($_REQUEST['_func']){
		case 'install':
			$appkey=trim($_REQUEST['appkey']);
			$ok=appstoreInstall($appkey);
			setView('appstore_apps',1);
			return;
		break;
		case 'update':
			$appkey=trim($_REQUEST['appkey']);
			$ok=appstoreUpdate($appkey);
			return;
		break;
		case 'uninstall':
			$appkey=trim($_REQUEST['appkey']);
			$id=(integer)$_REQUEST['id'];
			$opts=array(
				'-table'=>'_pages',
				'-where'=>"_id={$id} and _appkey='{$appkey}'"
			);
			$ok=delDBRecord($opts);
			$ok=appstoreSetStatus($appkey,'uninstalled');
			setView('appstore_apps',1);
			return;
		break;
		case 'search':
			$search=str_replace("'","''",$_REQUEST['search']);
			$apps=appstoreApps($search);
			setView('appstore_apps',1);
		break;
		default:
			$ok=appstoreInit();
			$apps=appstoreApps();
			setView('default');
		break;	
	}
	
	
?>