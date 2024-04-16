<?php
	global $git;
	if(!isset($_REQUEST['func'])){$_REQUEST['func']='';}
	switch(strtolower($_REQUEST['func'])){
		case 'status':
			$git['status']=gitCommand('status -s');
			setView('git_status',1);
			return;
		break;
        default:
			$fonts=fontsGetFonts();
			//echo printValue($fonts);exit;
			setView('default',1);
        break;
	}
?>
