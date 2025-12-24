<?php
	global $PAGE;
	global $MODULE;
	if(!isset($MODULE['title'])){
		$MODULE['title']='<span class="icon-server w_gray"></span> <translate>System Monitor</translate>';
	}
	loadExtras(array('translate','system'));
	loadExtrasCss('wacss');
	loadExtrasJs(array('wacss','moment','chart'));
	if(!isset($_SESSION['REMOTE_LANG']) || !strlen($_SESSION['REMOTE_LANG'])){
		$_SESSION['REMOTE_LANG']=$_SERVER['REMOTE_LANG'];
	}
	global $PASSTHRU;
	switch(strtolower($PASSTHRU[0])){
		default:
			$recs=sysmonGetProcessList();
			//charts
			if(!isset($_SESSION['sysmon_charts'])){
				$_SESSION['sysmon_charts']=array();
			}
			//loadavg
			$load=systemGetLoadAverage();
			$t=round(microtime(true)*1000,0);
			if(!isset($_SESSION['sysmon_charts']['loadavg'])){
				$_SESSION['sysmon_charts']['loadavg']['label']='LoadAvg';
				$_SESSION['sysmon_charts']['loadavg']['data'][]=array('t'=>$t,'y'=>100);
			}
			$_SESSION['sysmon_charts']['loadavg']['data'][]=array('t'=>$t,'y'=>$load);
			if(count($_SESSION['sysmon_charts']['loadavg']['data']) > 30){
				array_shift($_SESSION['sysmon_charts']['loadavg']['data']);
			}

			//drive
			$drives=systemGetDriveSpace();
			foreach($drives as $drive){
				if(stringContains($drive['description'],'cd-rom')){continue;}
				$label='Drive '.$drive['caption'];
				if(!isset($_SESSION['sysmon_charts'][$label]['label']) || !strlen($_SESSION['sysmon_charts'][$label]['label'])){
					$_SESSION['sysmon_charts'][$label]['label']=$label;
				}
				$used=$drive['size']-$drive['freespace'];
				$pcnt=number_format(($used/$drive['size'])*100,2);
				$_SESSION['sysmon_charts'][$label]['data'][]=array('t'=>$t,'y'=>$pcnt);
				if(count($_SESSION['sysmon_charts'][$label]['data']) > 30){
					array_shift($_SESSION['sysmon_charts'][$label]['data']);
				}
			}
			
		break;
	}
?>