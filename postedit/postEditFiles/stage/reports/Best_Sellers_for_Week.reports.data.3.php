<?php
	global $rec;
	$rec=commonBuildFilters($rec);
	//get the highest month and add this month to the query above
	$query=<<<ENDOFSQL
	SELECT
		count(*) as orders
		,product_ids
		,product_names
		,product_qtys
	FROM clientdata_woo_orders
	WHERE
		{$rec['filterstr']}
	GROUP BY
		product_ids
		,product_names
		,product_qtys
ENDOFSQL;
	//echo $query;exit;
	//cache the results for 1 minute so we have enough time to get the table view
	$hrs=1/60;
	$sv="return getDBRecords(\"{$query}\",0,$hrs);";
	$tmp=getStoredValue($sv);
	$recs=array();
	//parse the orders that have more than one value
	foreach($tmp as $i=>$rec){
    	$product_ids=json_decode($rec['product_ids'],1);
    	$product_names=json_decode($rec['product_names'],1);
    	$product_qtys=json_decode($rec['product_qtys'],1);
    	foreach($product_ids as $x=>$pid){
			if(!isset($recs[$pid])){
				$xrec=$rec;
	        	$xrec['pid']=$pid;
				$xrec['qty']=$product_qtys[$x]*$rec['orders'];
				$xrec['name']=$product_names[$x];
				unset($xrec['product_ids']);
				unset($xrec['product_names']);
				unset($xrec['product_qtys']);
				$recs[$pid]=$xrec;
			}
			else{
				$recs[$pid]['qty']+=$product_qtys[$x]*$rec['orders'];
				$recs[$pid]['orders']+=$rec['orders'];
			}
		}

	}
	//sort by qty desc
	$tmp=sortArrayByKeys($recs,array('qty'=>SORT_DESC,'name'=>SORT_ASC));
	$recs=array();
	$total=0;
	foreach($tmp as $rec){
		$total+=$rec['qty'];
		$recs[]=$rec;
		if(count($recs)==10){break;}
	}
	unset($tmp);
	//format the data into name,sales
	$lines=array();
	$line=array('label','value','pcnt');
    $lines[]=implode(',',$line);
	foreach($recs as $rec){
		$pcnt=round((($rec['orders']/$total)*100),0);
		if($pcnt < 1){continue;}
    	$line=array("{$rec['name']}",$rec['orders'],$pcnt);
    	$lines[]=implode(',',$line);
    	if(count($lines)==20){break;}
	}
	if($otherpcnt > 0){
    	$line=array("Other Products",$otherorders,$otherpcnt);
    	$lines[]=implode(',',$line);
	}
	$rtn='';
	switch(strtolower($_REQUEST['databack'])){
		case 'sql':
			$rtn=$query;
		break;
		case 'speech':
			//$rtn='still working on this one';break;
			if(!isset($recs[0])){
            	$rtn="You had no orders";
            	break;
			}
			$sentences=array();
			foreach($recs as $i=>$rec){
				$pcnt=round((($rec['orders']/$total)*100),0);
				if($pcnt < 1){continue;}
    			$product="{$rec['name']}-#{$rec['pid']}";
				$place=ordinalSuffix($i+1);
				$sentences[]= "{$place} place is {$product} with {$rec['orders']} orders comprising {$pcnt} percent., \r\n";
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
			$rtn=nl2br($rtn);
            $rtn .= buildOnLoad("hideId('{$_REQUEST['divid']}');");
		}
		return $rtn;
	}
	$csv=implode("\n",$lines);
	pushData($csv,'csv','data.csv');
?>
