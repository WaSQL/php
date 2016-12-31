<?php
loadExtrasJs('wd3');
if(!isUser()){
	setView('login');
	return;
}
loadDBFunctions('functions_common');
loadExtrasJs(array('sortable','wd3'));
$defaultmenu='reports';
//get existing sources
global $sources;
global $USER;
$sources=accountGetDataSources();
switch(strtolower($_REQUEST['passthru'][0])){
	case 'account':
	case 'profile':
	case 'report_stats':
	case 'addtab':
		setView(strtolower($_REQUEST['passthru'][0]));
	break;
	case 'streams':
	case 'streamdata':
		$stream=accountGetStreamData();
		setView(strtolower($_REQUEST['passthru'][0]));
	break;
	case 'export':
		echo printValue($_REQUEST);
		exit;
	break;
	case 'users':
		if(strlen($_REQUEST['passthru'][1])){
			switch(strtolower($_REQUEST['passthru'][1])){
	        	case 'add':
	        		$id='';
	        	break;
	        	default:
	        		$id=(integer)$_REQUEST['passthru'][1];
				break;
			}
			setView('users_addedit');
			return;
		}
		$recs=accountGetUsers();
		setView('users');
	break;
	case 'reports':
		switch(strtolower($_REQUEST['passthru'][1])){
			case 'account_status':
				//if this person is not signed up redirect to /pricing page
				$sub=commonGetSubscription();
				if(!isset($sub['packages'])){
                	echo buildOnLoad("window.location='/pricing';");
                	exit;
				}
				switch(strtolower($_REQUEST['passthru'][2])){
                	case 1:
                	case 'true':
                		$account_status='live';
                	break;
                	default:
                		$account_status='test';
                	break;
				}
				$ok=pageSetClientAccountStatus($account_status);
			break;
		}
		$usertabs=reportGetUserTabs();
		$default_tab='Sales';
		foreach($usertabs as $name=>$utab){
        	if($utab['default_tab']==1){$default_tab=$name;}
		}
		//echo printValue($usertabs);exit;
		$_REQUEST['tab_id']=$usertabs[$default_tab]['_id'];
		$recs=reportGetUserTabReports($_REQUEST['tab_id']);
		setView('reports');
	break;
	case 'sources':
		$client=accountGetClient();
		$opts=array(
			'-table'	=> 'clients_datasources',
			'client_id'	=> $client['_id'],
			'-index' => 'name'
		);
		$datasources=getDBRecords($opts);
		if(!isAjax() && strtolower($_REQUEST['passthru'][1]) != 'woo'){
			setView('default',1);
			if(accountIsAdmin()){
	        	setView('account_admin');
			}
			$defaultmenu='sources';
			return;
		}
		//echo printValue($opts).printValue($datasources);exit;
		setView('sources');
		switch(strtolower($_REQUEST['passthru'][1])){
			case 'woo':
				$url=$_REQUEST['woo_url'];
				$code=encodeBase64($_REQUEST['woo_url']);
				if(!preg_match('/^http/i',$url)){$url="https://{$url}";}
				$url.="/wc-auth/v1/authorize?app_name=Skillsai&scope=read_write&user_id={$client['_id']}";
				$url.="&return_url=".encodeURL('https://stage.skillsai.com/account/sources');
				$url.="&callback_url=".encodeURL("https://stage.skillsai.com/register/{$client['_id']}/{$code}?type=woo");
				//echo $url;exit;
				header("Location: {$url}");
				exit;
			break;
			case 'stripe':
				loadExtras('stripe');
				setView('sources_stripe',1);
				$opts=array(
					'secret_key'	=> addslashes($_REQUEST['stripe_secret_key']),
				);
				$ok=accountSetDatasource('stripe',$opts);
				//initialize the collector to get woo data
				$post=postUrl("http://localhost/init/{$client['_id']}",array('-method'=>'GET','-port'=>5263));
			break;
		}
		return;
	break;
	case 'data':
		$rec=reportGetReportTabRec($_REQUEST['passthru'][1]);
		if(!isset($rec['_id'])){echo "Error:No Report";exit;}
		if(strlen($rec['defaults'])){
        	$defaults=json_decode($rec['defaults'],true);
        	foreach($defaults as $k=>$v){
				if(preg_match('/(from|to)_date/i',$k)){continue;}
				$rec['filters'][$k]=$v;
			}
		}
		//pass the rest of the passthru vars country/usa
		$temp=$_REQUEST['passthru'];
		array_shift($temp);array_shift($temp);
		$cnt=count($temp);
		for ($i = 0; $i < $cnt; $i += 2){
    		if(strlen($temp[$i+1])){
	    		$rec['filters'][ $temp[$i] ] = $temp[$i+1];
			}
		}
		if(isset($rec['filters']['update'])){
        	$rec['filters']=array();
        	for ($i = 0; $i < $cnt; $i += 2){
				if(strlen($temp[$i+1])){
	    			$rec['filters'][ $temp[$i] ] = $temp[$i+1];
				}
			}
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
                	case 'default':
                		$usertabs=reportDefaultUserTab(addslashes($_REQUEST['tabid']));
						$_REQUEST['tab_id']=$usertabs[0]['_id'];
                	break;
                	case 'clear':
                		$usertabs=reportClearUserTab(addslashes($_REQUEST['tabid']));
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
	case 'addreportform':
		$tabname=reportGetTabName($_REQUEST['tab_id']);
		$recs=pageGetReports($tabname);
		//echo printValue(array_keys($recs[0]));exit;
		setView('addreportform');
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
		setView('default');
		if(accountIsAdmin()){
        	setView('account_admin');
		}
	break;
}
?>
