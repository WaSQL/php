<?php
	global $PAGE;
	global $MODULE;
	if(!isset($MODULE['title'])){
		$MODULE['title']='<span class="icon-server w_gray"></span> <translate>System Monitor</translate>';
	}
	loadExtras(array('translate','system'));
	loadExtrasCss('wacss');
	loadExtrasJs('wacss');
	if(!isset($_SESSION['REMOTE_LANG']) || !strlen($_SESSION['REMOTE_LANG'])){
		$_SESSION['REMOTE_LANG']=$_SERVER['REMOTE_LANG'];
	}
	switch(strtolower($_REQUEST['passthru'][0])){;
		default:
			$recs=sysmonGetProcessList();
			//calculate totals
			$totals=array();
			foreach($recs as $rec){
				//skip system idle process
				if($rec['pid']==0){continue;}
				$totals['pcpu']+=$rec['pcpu'];
				$totals['pmem']+=$rec['pmem'];
				$totals['pcount']+=1;
			}
			$totals['pcpu']=number_format($totals['pcpu'],2);
			$totals['pmem']=number_format($totals['pmem'],2);
			setView('default');
		break;
	}
?>