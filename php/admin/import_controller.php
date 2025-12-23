<?php
//Access control check
if(!isAdmin()){
	echo '<div class="w_bold w_danger">Access Denied: Admin privileges required</div>';
	exit;
}

global $importrecs;
global $importrecs_total;
global $results;
global $fieldinfo;
$importrecs=$results=array();

//Validate file type parameter
$validFileTypes=array('xml','csv','apps');
$fileType=isset($_REQUEST['filetype'])?strtolower($_REQUEST['filetype']):'';
if(!in_array($fileType,$validFileTypes)){
	$fileType='xml'; //default to xml
}

switch(strtolower($_REQUEST['func'])){
	case 'process':
		$ok=processFileUploads();
		switch($fileType){
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
		//$_REQUEST=array();
		setView(array('default','processed'),1);
		return;
	break;
	default:
		setView('default',1);
	break;
}
?>
