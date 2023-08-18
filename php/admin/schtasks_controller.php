<?php
global $CONFIG;
if(!isset($CONFIG['admin_form_url'])){
	$CONFIG['admin_form_url']='/php/admin.php';
}
switch(strtolower($_REQUEST['func'])){
	case 'enable':
		$id=(integer)$_REQUEST['id'];
		$ok=schtasksEnable($id);
		setView('list',1);
		return;	
	break;
	case 'disable':
		$id=(integer)$_REQUEST['id'];
		$ok=schtasksDisable($id);
		setView('list',1);
		return;	
	break;
	case 'list':
		//echo "list";exit;
		setView('list',1);
		return;
	break;
	default:
		//echo "default";exit;
		setView('default');
	break;
}
?>