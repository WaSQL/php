<?php
	global $rec;
	$rec=commonBuildFilters($rec);

	//get the highest month and add this month to the query above
	$query=<<<ENDOFSQL
	SELECT
		count(*) orders
		,round(sum(amount)/count(*),2) as aos
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
		,round(sum(amount)/count(*),2) as aos
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
	//cache the results for 1 minute so we have enough time to get the table view
	$hrs=1/60;
	$query=trim($query);
	$recs=getDBRecords($query);
	//format the data into orders,Apr,May,Jun,Aug...Apr
	$data=array();
	$lines=array();
	$line=array('label','aos','value');
	$lines[]=implode(',',$line);
	foreach($recs as $i=>$rec){
		$t=strtotime("{$rec['year']}-{$rec['month']}-01");
		$subkey=date('My',$t);
    	$data[$subkey]=$i;
    	$recs[$i]['monthname']=monthName($rec['month']);
	}
	foreach($data as $month=>$i){
		$line=array($month,$recs[$i]['aos'],$recs[$i]['orders']);
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
				$amount=money_format('%.2n', $rec['aos']);
				$sentences[]= "In {$rec['monthname']} of {$rec['year']} you had {$rec['orders']} orders with an average order size of {$amount}.";
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
