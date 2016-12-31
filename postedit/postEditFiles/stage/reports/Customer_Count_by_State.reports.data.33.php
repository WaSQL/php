<?php
	global $rec;
	$rec=commonBuildFilters($rec);
	//Get the data
	$query=<<<ENDOFSQL
	SELECT
		count(*) as orders
		,shipto_state as name
	FROM clientdata_woo_orders
	WHERE 
		{$rec['filterstr']}
		and shipto_country in ('US','USA')
	GROUP BY shipto_state
	UNION
	SELECT
		count(*) as orders
		,shipto_state as name
	FROM clientdata_stripe_orders
	WHERE 
		{$rec['filterstr']}
		and shipto_country in ('US','USA')
	GROUP BY shipto_state
	ORDER BY orders desc
ENDOFSQL;
//echo $query;exit;
	//cache the results for 1 minute so we have enough time to get the table view
	$hrs=1/60;
	$sv="return getDBRecords(\"{$query}\",0,$hrs);";
	$tmp=getStoredValue($sv);
	$states=getDBRecords(array(
		'-table'	=> 'states',
		'country'	=> 'US',
		'-index'	=> 'code'
	));
	$total=0;
	$recs=array();
	foreach($tmp as $rec){
    	$state=$rec['name'];
    	if(isset($states[$state])){
			$total+=$rec['orders'];
			$rec['statename']=$states[$state]['name'];
			$recs[]=$rec;
		}
	}
	$levels=array();
	foreach($recs as $i=>$rec){
    	$rec['pcnt']=round(($rec['orders']/$total)*100,0);
    	if(!in_array($rec['pcnt'],$levels)){
        	$levels[]=$rec['pcnt'];
		}
		$rec['level']=count($levels);
		if($rec['level'] <= 6){
    		$recs[$i]['fillkey']=ordinalSuffix($rec['level']);
		}
		else{
			$recs[$i]['fillkey']="defaultFill";
		}
	}
	//echo printValue($recs);exit;
	//format the data into name,orders
	$lines=array();
	$line=array('id','value','fillKey');
    $lines[]=implode(',',$line);
    $total=0;
    foreach($recs as $rec){
		$total+=$rec['orders'];
	}
	foreach($recs as $rec){
    	$line=array($rec['name'],$rec['orders'],$rec['fillkey']);
    	$lines[]=implode(',',$line);
	}
	$rtn='';
	switch(strtolower($_REQUEST['databack'])){
		case 'sql':
			$rtn=nl2br($query);
		break;
		case 'speech':
			if(!isset($recs[0])){
            	$rtn="You had no orders";
            	break;
			}
			$sentences=array();
			foreach($recs as $rec){
				if(strtolower($rec['fillkey'])=='defaultfill'){break;}
				$monthName = monthName($rec['month']);
				$sentences[]= "{$rec['statename']} is in {$rec['fillkey']} place with {$rec['orders']} orders. \n";
			}
			$rtn=implode(' ', $sentences);
			if(isset($_REQUEST['divid'])){
            	$rtn=nl2br($rtn);
			}
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
			$rtn=$rtn;
            $rtn .= buildOnLoad("hideId('{$_REQUEST['divid']}');");
		}
		return $rtn;
	}
	$csv=implode("\n",$lines);
	pushData($csv,'csv','data.csv');
?>
