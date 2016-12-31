<?php
	global $rec;
	$rec=commonBuildFilters($rec);
	//Get the data
	$query=<<<ENDOFSQL
	SELECT
       sum(amount) as sales
       ,dayofmonth(cdate) as day
       ,month(cdate) as month
       ,year(cdate) as year
	FROM clientdata_woo_orders
	WHERE
       {$rec['filterstr']}
	GROUP BY
       dayofmonth(cdate)
       ,month(cdate)
       ,year(cdate)
    UNION
    SELECT
       sum(amount) as sales
       ,dayofmonth(cdate) as day
       ,month(cdate) as month
       ,year(cdate) as year
	FROM clientdata_stripe_orders
	WHERE
       {$rec['filterstr']}
	GROUP BY
       dayofmonth(cdate)
       ,month(cdate)
       ,year(cdate)
	ORDER BY
       day
ENDOFSQL;
	//cache the results for 1 minute so we have enough time to get the table view
	$hrs=1/60;
	$sv="return getDBRecords(\"{$query}\",0,$hrs);";
	$recs=getStoredValue($sv);
	//echo $query.printValue($recs);exit;
	//format the data into day,Apr,May,Jun,Aug 2015
	$data=array();
	foreach($recs as $rec){
		$t=strtotime("{$rec['year']}-{$rec['month']}-{$rec['day']}");
		if(date('Ym',$t)==$maxym){
        	$subkey=date('MY',$t);
        	$data[$rec['day']][$subkey]=round($rec['sales'],0);
        	if(count($rec)==4){
				$subkey=date('n-M',$t);
				$data[$rec['day']][$subkey]=round($rec['sales'],0);
			}
		}
		else{
			$subkey=date('n-M',$t);
			$data[$rec['day']][$subkey]=round($rec['sales'],0);
			}

	}
	//sort the data
	$keys=array();
	foreach($data as $day=>$rec){
		foreach($data[$day] as $k=>$v){
        	if(!in_array($k,$keys)){$keys[]=$k;}
		}
	}
	foreach($data as $day=>$rec){
		//echo printValue($data[$day]);
		foreach($keys as $k){
        	if(!isset($data[$day][$k])){
            	$data[$day][$k]=0;
			}
		}
		ksort($data[$day]);
		//echo printValue($data[$day]);exit;
		while(count($data[$day]) > 4){array_shift($data[$day]);}
		$tmp=array();
		foreach($data[$day] as $k=>$v){
        	$k=preg_replace('/^([0-9]+?)\-/','',$k);
        	$tmp[$k]=$v;
		}
		$data[$day]=$tmp;
	}
	$lines=array();
	$line=array('day');
	foreach($data as $day=>$rec){
		foreach($rec as $k=>$v){$line[]=$k;}
		break;
	}
	$aggragate=array();
    $lines[]=implode(',',$line);
	foreach($data as $day=>$rec){
    	$line=array($day);
    	foreach($rec as $k=>$v){
			if($key != 'day'){
				$aggragate[$k]+=$v;
				$line[]=$aggragate[$k];
			}
			else{
				$line[]=$v;
			}
		}
    	$lines[]=implode(',',$line);
	}
	$rtn='';
	switch(strtolower($_REQUEST['databack'])){
		case 'sql':
			$rtn=$query;
		break;
		case 'speech':
			$rtn='still working on this one';break;
			if(!isset($recs[0])){
            	$rtn="You had no orders";
            	break;
			}
			return printValue($recs);
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
