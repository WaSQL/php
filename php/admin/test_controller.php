<?php
	global $CONFIG;
	loadExtrasJs(array('moment','chart','chartjs-plugin-datalabels'));
	if(isset($_REQUEST['multipart'])){
		processFileUploads();
		echo "PROCESSED ".printValue($_REQUEST);exit;
	}
	if(isset($_REQUEST['test'])){
		setView($_REQUEST['test'],1);
		switch(strtolower($_REQUEST['test'])){
			case 'chartjs':
				
			break;
			case 'language_includes':
				$lang=commonGetLangInfo($_REQUEST['lang']);
				//echo printValue($_REQUEST);exit;
				setView('language_includes',1);
				return;
			break;
		}
		return;
	}
	setView('default');
?>