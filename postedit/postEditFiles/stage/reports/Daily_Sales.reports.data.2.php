<?php
	global $rec;
	$report=commonBuildFilters($rec);
	unset($report['data']);
	unset($report['body']);
	//Get the data
	$query=<<<ENDOFSQL
	SELECT
       AVG(amount) as sales
       ,dayofmonth(cdate) as day
       ,month(cdate) as month
	FROM clientdata_woo_orders
	WHERE
       {$report['filterstr']}
	GROUP BY
       dayofmonth(cdate)
       ,month(cdate)
    ORDER BY
    	month(cdate),dayofmonth(cdate)
ENDOFSQL;
	//cache the results for 2 minute so we have enough time to get the table view
	$filterspeech=$report['filterspeech'];
	$hrs=2/60;
	$sv="return getDBRecords(\"{$query}\",0,$hrs);";
	$recs=getStoredValue($sv);
	//echo $query.printValue($recs);exit;
	//format the data into day,Apr,May,Jun,Aug 2015
	$data=array();
	$months=array();
	$stats=array(
		'bestmonth'=>array('sales'=>0,'day'=>0,'month'=>''),
		'worstmonth'=>array('sales'=>9999999999,'day'=>0,'month'=>''),
		'bestday'=>array('sales'=>0,'day'=>0),
		'worstday'=>array('sales'=>9999999999,'day'=>0),
		'months'=>array()
	);
	foreach($recs as $rec){
		$sales=round($rec['sales'],0);
		$data[$rec['day']]+=$sales;
		$stats['months'][$rec['month']]+=1;
		if($sales > $stats['bestmonth']['sales']){
			$stats['bestmonth']=array('sales'=>$sales,'day'=>ordinalSuffix($rec['day']),'month'=>monthName($rec['month']));
		}
		if($sales < $stats['worstmonth']['sales']){
			$stats['worstmonth']=array('sales'=>$sales,'day'=>ordinalSuffix($rec['day']),'month'=>monthName($rec['month']));
		}
	}
	ksort($data);
	$lines=array();
	$line=array('day','sales');
	$lines[]=implode(',',$line);
	foreach($data as $day=>$sales){
		$line=array($day,$sales);
		$lines[]=implode(',',$line);
		$stats['total']+=$sales;
		$stats['days']+=1;
		if($sales > $stats['bestday']['sales']){
			$stats['bestday']=array('sales'=>$sales,'day'=>ordinalSuffix($day));
		}
		if($sales < $stats['worstday']['sales']){
			$stats['worstday']=array('sales'=>$sales,'day'=>ordinalSuffix($day));
		}
	}
	$stats['average']=round($stats['total']/$stats['days'],0);
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
			//$sentences[]=$filterspeech;
			//months
			$monthcnt=count($stats['months']);
			$sentences[]= "There are {$monthcnt} months of data in this report.";
			$ave=round($stats['average']/$monthcnt,0);
			$sentences[]= "On average you sell \${$ave} per day.";
			//bestmonth
			//$sentences[]= "Your best single day was on {$stats['bestmonth']['month']} {$stats['bestmonth']['day']} with sales of \${$stats['bestmonth']['sales']}.";
			//bestday
			$ave=round($stats['bestday']['sales']/$monthcnt,0);
			$sentences[]= "Your best day is usually on the {$stats['bestday']['day']} of each month with average sales of \${$ave}.";
			//worstmonth
			//$sentences[]= "Your worst single day was on {$stats['worstmonth']['month']} {$stats['worstmonth']['day']} with sales of \${$stats['worstmonth']['sales']}.";
			//worstday
			$ave=round($stats['worstday']['sales']/$monthcnt,0);
			$sentences[]= "Your worst day is usually on the {$stats['worstday']['day']} of each month with average sales of \${$ave}.";

			$rtn=implode("\n", $sentences);
			if(isset($_REQUEST['divid'])){
				$rtn=nl2br($rtn);
			}
			else{return $rtn;}
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
