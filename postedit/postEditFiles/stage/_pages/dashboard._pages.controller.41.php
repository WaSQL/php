<?php
	if(!isUser()){
    	header('Location: /login');
    	exit;
	}
	loadExtrasJs('chart');
	list($func,$name)=$_REQUEST['passthru'];
	if(isset($_REQUESt['report_name'])){$name=$_REQUESt['report_name'];}
	switch(strtolower($func)){
		case 'report':
			if(!isset($_REQUEST['date_begin'])){
				$_REQUEST['date_begin']=$_REQUEST['date_end']=date('Y-m-d');
				$_REQUEST['type']='time';
			}
			$json_data=pageReportData('wam',$_REQUEST);
			if(isNum($_REQUEST['drilldown'])){
				$index=$_REQUEST['drilldown'];
				$data=json_decode($json_data,true);
			}

			setView("report_{$name}",1);
			return;
		break;
    	default:
    		setView('default');
    		return;
    	break;
	}
?>
