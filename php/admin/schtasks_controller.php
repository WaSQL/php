<?php
global $CONFIG;
if(!isset($CONFIG['admin_form_url'])){
	$CONFIG['admin_form_url']='/php/admin.php';
}
switch(strtolower($_REQUEST['func'])){
	case 'details':
		$id=(int)$_REQUEST['id'];
		$rec=schtasksGetTask($id);
		$xrecs=schtasksListExtra(array($rec));
		$xrecs[0]['taskname']=$rec['taskname'];
		$rec=$xrecs[0];
		setView('details',1);
		return;	
	break;
	case 'enable':
		$id=(int)$_REQUEST['id'];
		$ok=schtasksEnable($id);
		setView('list',1);
		return;	
	break;
	case 'disable':
		$id=(int)$_REQUEST['id'];
		$ok=schtasksDisable($id);
		setView('list',1);
		return;	
	break;
	case 'list':
		setView('list',1);
		return;
	break;
	default:
		setView('default');
	break;
}
?>