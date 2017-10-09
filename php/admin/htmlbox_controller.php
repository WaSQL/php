<?php
	$cpath=dirname(__FILE__);
	switch(strtolower($_REQUEST['func'])){
		case 'save':
			$_SESSION['htmlbox_css']=$_REQUEST['htmlbox_css'];
			$_SESSION['htmlbox_html']=$_REQUEST['htmlbox_html'];
			$htmlref=getCSVFileContents("{$cpath}/admin/htmlref.csv");
        	$cssref=getCSVFileContents("{$cpath}/admin/cssref.csv");
			setView('default',1);
		break;
        default:
        	$htmlref=getCSVFileContents("{$cpath}/admin/htmlref.csv");
        	$cssref=getCSVFileContents("{$cpath}/admin/cssref.csv");
			setView('default',1);
		break;
	}
?>
