<?php
	switch(strtolower($_REQUEST['func'])){

		case 'new':
			setView('new',1);
			return;
		break;
		case 'edit':
			$id=addslashes($_REQUEST['id']);
			setView('edit',1);
			return;
		break;
		case 'reports':
			$menu=addslashes($_REQUEST['menu']);
			$reports=reportsGetReports($menu);
			setView('reports',1);
			return;
		break;
		case 'report':
			$id=addslashes($_REQUEST['id']);
			$report=getDBRecord(array('-table'=>'_reports','active'=>1,'_id'=>$id));
			$report=reportsRunReport($report);
			setView('report',1);
			return;
		break;
		default:
			$groups=reportsGetGroups();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
