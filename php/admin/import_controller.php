<?php
global $importrecs;
global $importrecs_total;
global $results;
global $fieldinfo;
$importrecs=$results=array();
switch(strtolower($_REQUEST['func'])){
	case 'process':
		switch(strtolower($_REQUEST['filetype'])){
			case 'xml':
				$results=importProcessXML($_REQUEST);
			break;
			case 'csv':
				$results=importProcessCSV($_REQUEST);
			break;
			case 'apps':
				$results=importProcessAPPS($_REQUEST);
			break;
		}
		$_REQUEST=array();
		setView(array('default','processed'),1);
		return;
	break;
	default:
		setView('default',1);
	break;
}
?>
