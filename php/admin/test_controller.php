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
		}
		return;
	}
	setView('default');
?>