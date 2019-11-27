<?php
//echo printValue($_REQUEST);exit;
switch(strtolower($_REQUEST['func'])){
	case 'add':
		$id=0;
		setView('addedit',1);
		return;
	break;
	case 'edit':
		$id=(integer)$_REQUEST['id'];
		setView('addedit',1);
		return;
	break;
	case 'details':
		$id=(integer)$_REQUEST['id'];
		$cron=cronDetails($id);
		setView('details',1);
		return;
	break;
	case 'cron_result':
		$id=(integer)$_REQUEST['id'];
		$log=getDBRecordById('_cronlog',$id);
		setView('cron_result',1);
	break;
	case 'list':
		setView('list',1);
		return;
	break;
	case 'default':
		setView('default');
	break;
}
?>