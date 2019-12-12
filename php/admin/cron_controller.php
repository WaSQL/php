<?php
switch(strtolower($_REQUEST['func'])){
	case 'add':
		//echo "add";exit;
		$id=0;
		setView('addedit',1);
		return;
	break;
	case 'edit':
		//echo "edit";exit;
		$id=(integer)$_REQUEST['id'];
		setView('addedit',1);
		return;
	break;
	case 'details':
		//echo "details";exit;
		$id=(integer)$_REQUEST['id'];
		$cron=cronDetails($id);
		setView('details',1);
		return;
	break;
	case 'cron_result':
		//echo "cron_result";exit;
		$id=(integer)$_REQUEST['id'];
		$log=getDBRecordById('_cronlog',$id);
		setView('cron_result',1);
	break;
	case 'list':
		//echo "list";exit;
		setView('list',1);
		return;
	break;
	default:
		//echo "default";exit;
		$ok=cronCheckSchema();
		setView('default');
	break;
}
?>