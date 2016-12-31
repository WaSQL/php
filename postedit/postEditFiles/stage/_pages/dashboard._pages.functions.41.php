<?php
function pageReportData($report,$params){
	$recs=array();
	switch(strtolower($report)){
		case 'wam':
			$params['-datefield']='logdate';
			$params=pageParseParams($params);
			//echo printValue($params);exit;
			$queries=array(
				"select {$params['xfield']} as xval ,avg(total_time) as yval ,'avg' as setval
				from clientdata_wam_log WHERE client_id=2 {$params['filter']}
				GROUP BY {$params['xfield']} ORDER BY {$params['xfield']}",
				"select {$params['xfield']} as xval ,min(total_time) as yval ,'min' as setval
				from clientdata_wam_log WHERE client_id=2 {$params['filter']}
				GROUP BY {$params['xfield']} ORDER BY {$params['xfield']}",
				"select {$params['xfield']} as xval ,max(total_time) as yval ,'max' as setval
				from clientdata_wam_log WHERE client_id=2 {$params['filter']}
				GROUP BY {$params['xfield']} ORDER BY {$params['xfield']}",
			);
			$_REQUEST['queries']=$queries;
			foreach($queries as $query){
				$xrecs=getDBRecords(array('-query'=>$query));
				$recs=array_merge($recs,$xrecs);
			}
			//echo printValue($queries).printValue($recs);exit;
			//convert xval to times if type==time
			if($params['type']=='time'){
				foreach($recs as $i=>$rec){
					$h=round($rec['xval'],0);
                	$recs[$i]['xval']=date('g a',strtotime("{$h}:00"));
				}
			}
			//echo printValue($recs);exit;
			$min=hex2RGB('#cca9d0');
			$min[]=.5;
			$max=hex2RGB('#f8991f');
			$max[]=.5;
			$avg=hex2RGB('#71aaa5');
			$avg[]=.5;
			$params=array(
				'min_backgroundColor'	=> "rgba({$min[0]},{$min[1]},{$min[2]},{$min[3]})",
				'max_backgroundColor'	=> "rgba({$max[0]},{$max[1]},{$max[2]},{$max[3]})",
				'avg_backgroundColor'	=> "rgba({$avg[0]},{$avg[1]},{$avg[2]},{$avg[3]})",
				'max_pointHoverRadius'	=> 5
			);
		break;
	}
	if(!count($recs)){return '';}
	return buildChartJsData($recs,$params);
}

function pageParseParams($params=array()){
	if(strlen($params["date_begin"])){
    	$params["date_begin"]=date('Y-m-d',strtotime($params["date_begin"]));
	}
	if(strlen($params["date_end"])){
    	$params["date_end"]=date('Y-m-d',strtotime($params["date_end"]));
	}
	if(!isset($params['type']) && isset($params['date_begin']) && isset($params['date_end']) && $params['date_begin']==$params['date_end']){
		$params['type']='time';
	}
	$datefield=isset($params['-datefield'])?$params['-datefield']:'_cdate';
	if($params['type']=='time'){
		$xfield="HOUR({$datefield})";
	}
	elseif($params['type']=='minute'){
		$xfield="minute({$datefield})";
	}
	else{
    	$xfield="date({$datefield})";
	}

	$filter='';
	if(strlen($params['date_begin']) && strlen($params['date_end'])){
		if($params['date_begin']==$params['date_end']){
        	$filter .= " and date({$datefield}) = '{$params['date_begin']}'";
		}
		else{
    		$filter .= " and date({$datefield}) between '{$params['date_begin']}' and '{$params['date_end']}'";
		}
	}
	elseif(strlen($params['date_begin'])){
    	$filter .= " and date({$datefield}) >= '{$params['date_begin']}'";
	}
	elseif(strlen($params['date_end'])){
    	$filter .= " and date({$datefield}) <= '{$params['date_end']}'";
	}
	//hour
	if(isNum($params['hour'])){
    	$filter .= " and hour({$datefield}) = {$params['hour']}";
	}
	return array('filter'=>$filter,'xfield'=>$xfield,'datefield'=>$datefield,'type'=>$opts['type']);
}
?>
