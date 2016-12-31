<?php
	global $rec;
	$rec=commonBuildFilters($rec);
	//get the highest month and add this month to the query above
	$query=<<<ENDOFSQL
	SELECT
       count(*) orders
       ,month(cdate) as month
	   ,year(cdate) as year
	FROM clientdata_woo_orders
	WHERE
       {$rec['filterstr']}
	GROUP BY
		month(cdate)
		,year(cdate)
	UNION
	SELECT
       count(*) orders
       ,month(cdate) as month
	   ,year(cdate) as year
	FROM clientdata_stripe_orders
	WHERE
       {$rec['filterstr']}
	GROUP BY
		month(cdate)
		,year(cdate)
	ORDER BY
		year
		,month
ENDOFSQL;
//echo $query;exit;
	//cache the results for 1 minute so we have enough time to get the table view
	$hrs=1/60;
	$sv="return getDBRecords(\"{$query}\",0,$hrs);";
	$recs=getStoredValue($sv);

	//format the data into orders,Apr,May,Jun,Aug...Apr
	$data=array();
	foreach($recs as $rec){
		$t=strtotime("{$rec['year']}-{$rec['month']}-01");
		$subkey=date('My',$t);
    	$data[$subkey]=round($rec['orders'],0);
	}
	$lines=array();
	$line=array('label','value');
	$lines[]=implode(',',$line);
	foreach($data as $month=>$orders){
		$line=array($month,$orders);
		$lines[]=implode(',',$line);
	}
	$rtn='';
	switch(strtolower($_REQUEST['databack'])){
		case 'sql':
			$rtn=$query;
		break;
		case 'speech':
			if(!isset($recs[0])){
            	$rtn="You had no orders";
            	break;
			}
			$sentences=array();
			foreach($recs as $rec){
				$monthName = monthName($rec['month']);
				$sentences[]= "In {$monthName} of {$rec['year']} you had {$rec['orders']} orders.";
			}
			$rtn=implode(' ', $sentences);
		break;
		case 'table':
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
