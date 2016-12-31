<?php
global $USER;
if(!isUser()){
	setView('login');
	return;
}
loadExtrasJs(array('sortable','wd3'));
echo printValue($_REQUEST['passthru']);exit;
switch(strtolower($_REQUEST['passthru'][0])){
	case 'data':
		$rec=reportGetReportTabRec($_REQUEST['passthru'][1]);
		if(!isset($rec['_id'])){echo "Error:No Report";exit;}
		if(strlen($rec['defaults'])){
        	$defaults=json_decode($rec['defaults'],true);
        	foreach($defaults as $k=>$v){
				$rec['filters'][$k]=$v;
			}
		}
		//pass the rest of the passthru vars country/usa
		$temp=$_REQUEST['passthru'];
		array_shift($temp);array_shift($temp);
		$cnt=count($temp);
		for ($i = 0; $i < $cnt; $i += 2){
    		$rec['filters'][ $temp[$i] ] = $temp[$i+1];
		}
		//update defaults
		$defaults=json_encode($rec['filters']);
		if(sha1($defaults) != sha1($rec['defaults'])){
			$ok=editDBRecord(array(
				'-table'	=> 'report_tabs',
				'-where'	=> "_id={$_REQUEST['passthru'][1]}",
				'defaults'	=> $defaults
			));
		}
		echo pageLoadReportData($rec);
		exit;
	break;
	case 'nav':
		switch(strtolower($_REQUEST['passthru'][1])){
        	case 'tabform':
        		switch(strtolower($_REQUEST['func'])){
                	case 'add':
                	case 'save':
						$usertabs=reportAddUserTab(addslashes($_REQUEST['tabname']));
						$_REQUEST['tab_id']=$usertabs[0]['_id'];
                	break;
                	case 'edit':
                		$usertabs=reportEditUserTab($_REQUEST['tabid'],addslashes($_REQUEST['tabname']));
						$_REQUEST['tab_id']=$_REQUEST['tabid'];
                	break;
                	case 'delete':
                		$usertabs=reportDelUserTab(addslashes($_REQUEST['tabid']));
						$_REQUEST['tab_id']=$usertabs[0]['_id'];
                	break;
				}
        		setView('usertabs');
        		return;
        	break;
        	case 'del':
        		$usertabs=reportDelUserTab(addslashes($_REQUEST['passthru'][2]));
        		setView('usertabs');
        		return;
        	break;
			case 'sort':
        		$usertabs=reportUpdateTabSort(addslashes($_REQUEST['tabsort']));
        		echo "nav sort";exit;
        		setView('usertabs');
        		return;
        	break;
		}
		$_REQUEST['tab_id']=strtolower($_REQUEST['passthru'][1]);
		$recs=reportGetUserTabReports($_REQUEST['tab_id']);
		setView('tiles');
	break;
	case 'reportmanage':
		$recs=pageGetReports();
		setView('reportmanage');
		return;
	break;
	case 'addreport':
		$recs=reportAddUserTabReports($_REQUEST['tab_id'],$_REQUEST['report_id']);
		setView('tiles');
		return;
	break;
	case 'sortreport':
		//echo printValue($_REQUEST);exit;
		$recs=reportSortUserTabReports(addslashes($_REQUEST['tilesort']),$_REQUEST['tab_id']);
		echo "sortreport";exit;
		setView('tiles');
		return;
	break;
	case 'delreport':
		$recs=reportDelUserTabReports($_REQUEST['tab_id'],$_REQUEST['report_id']);
		setView('tiles');
		return;
	break;
	case 'tab':
		$tab_id=$_REQUEST['passthru'][1];
		$display_order=$_REQUEST['passthru'][2];
		$report_id=$_REQUEST['passthru'][3];
		$recs=homeSetReportTab($tab_id,$display_order,$report_id);
		$recs=$recs[0];
		setView(array('tab_table','init'));
		return;
	break;
	default:
		$usertabs=reportGetUserTabs();
		//echo printValue($usertabs);exit;
		$_REQUEST['tab_id']=$usertabs[0]['_id'];
		$recs=reportGetUserTabReports($_REQUEST['tab_id']);
		setView('default');
	break;
}
?>
