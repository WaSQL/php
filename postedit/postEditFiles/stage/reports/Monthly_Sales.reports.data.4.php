<?php
	global $rec;
	$report=commonBuildFilters($rec);
	//Get the data
	$query=<<<ENDOFSQL
	SELECT
       sum(amount) as sales
       ,month(cdate) as month
	FROM clientdata_woo_orders
	WHERE
       {$report['filterstr']}
	GROUP BY
       month(cdate)
    ORDER BY
    	month(cdate)
ENDOFSQL;
	//cache the results for 1 minute so we have enough time to get the table view
	$filterspeech=$report['filterspeech'];
	$hrs=1/60;
	$sv="return getDBRecords(\"{$query}\",0,$hrs);";
	$recs=getStoredValue($sv);
	//echo $query.printValue($recs);exit;
	//format the data into day,Apr,May,Jun,Aug 2015
	$data=array();
	$months=array();
	$lines=array();
	$line=array('label','value');
	$lines[]=implode(',',$line);
	$stats=array(
		'bestmonth'=>array('sales'=>0,'month'=>''),
		'worstmonth'=>array('sales'=>9999999999,'month'=>''),
		'months'=>array(),
		'total'=>0
	);
	foreach($recs as $rec){
		$sales=round($rec['sales'],0);
		$data[$rec['month']]+=$sales;
		$stats['total']+=$sales;
	}
	ksort($data);
	foreach($data as $m=>$sales){
		$month=monthName($m,'M');
		$line=array($month,$sales);
		$lines[]=implode(',',$line);
		$stats['months'][$m]+=1;
		$month=monthName($m,'F');
		if($sales > $stats['bestmonth']['sales']){
			$stats['bestmonth']=array('sales'=>$sales,'month'=>$month);
		}
		if($sales < $stats['worstmonth']['sales']){
			$stats['worstmonth']=array('sales'=>$sales,'month'=>$month);
		}
	}
	$stats['monthcount']=count($stats['months']);
	$stats['average']=round($stats['total']/$stats['monthcount'],0);
	//echo printValue($stats);exit;
	$rtn='';
	switch(strtolower($_REQUEST['databack'])){
		case 'sql':
			$rtn=$query;
			//$rtn.=printValue($recs);
		break;
		case 'speech':
			if(!isset($recs[0])){
            	$rtn="You had no orders";
            	break;
			}
			$sentences=array();
			//filterspeech
			$sentences[]=$filterspeech;
			//months
			$sentences[]= "There are {$stats['monthcount']} months of data in this report.";
			$sentences[]= "On average you sell \${$stats['average']} per month.";
			//bestmonth
			$sentences[]= "Your best sales month is {$stats['bestmonth']['month']} with sales of \${$stats['bestmonth']['sales']}.";
			//worstmonth
			$sentences[]= "Your worst sales month is {$stats['worstmonth']['month']} with sales of \${$stats['worstmonth']['sales']}.";

			$rtn=implode("\n", $sentences);
			if(isset($_REQUEST['divid'])){
				$rtn=nl2br($rtn);
			}
		break;
		case 'table':
		case 'export':
			$keystr=array_shift($lines);
			$keys=csvParseLine($keystr);
			$cnt=count($lines);
			$trecs=array();
			//echo $cnt.printValue($lines);exit;
			foreach($lines as $line){
		    	$parts=csvParseLine($line);
		    	$datarec=array();
		    	for($x=0;$x<count($parts);$x++){
					$datarec[$keys[$x]]=$parts[$x];
				}
				$trecs[]=$datarec;
			}
			if(strtolower($_REQUEST['databack'])=='export'){
				$filename=str_replace(' ','_',$report['name']).'.csv';
				$csv=arrays2Csv($trecs);
				pushData($csv,'csv',$filename);
			}
			$rtn=listDBRecords(array('-list'=>$trecs,'-listfields'=>$keys,'-tableclass'=>'table table-bordered table-striped table-condensed'));
		break;
	}
	if(strlen($rtn)){
    	if(isset($_REQUEST['divid'])){
            $rtn .= buildOnLoad("hideId('{$_REQUEST['divid']}');");
		}
		return $rtn;
	}
	$csv=implode("\n",$lines);
	pushData($csv,'csv','data.csv');
?>
