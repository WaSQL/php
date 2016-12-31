<?php
	global $rec;
	$report=commonBuildFilters($rec);
	//Get the data
	$query=<<<ENDOFSQL
	SELECT
		sum(amount) as sales
		,shipto_state as state
	FROM clientdata_woo_orders
	WHERE
		{$report['filterstr']}
		and shipto_country in ('US','USA')
	GROUP BY shipto_state
ENDOFSQL;
//echo $query;exit;
	//cache the results for 1 minute so we have enough time to get the table view
	$filterspeech=$report['filterspeech'];
	$hrs=1/60;
	$sv="return getDBRecords(\"{$query}\",0,$hrs);";
	$recs=getStoredValue($sv);
	$rstates=array();
	foreach($recs as $rec){$rstates[]=$rec['state'];}
	$states=getDBRecords(array(
		'-table'	=> 'states',
		'country'	=> 'US',
		'-index'	=> 'code'
	));
	foreach($states as $state=>$rec){
    	if(!in_array($state,$rstates)){
        	$recs[]=array(
				'state'=>$state,
				'sales'=>0
			);
		}
	}
	$stats=array(
		'best'=>array('sales'=>0,'state'=>''),
		'worst'=>array('sales'=>9999999999,'state'=>''),
		'total'=>0,
		'count'=>0
	);
	$lines=array();
	$line=array('id','value','fillKey');
    $lines[]=implode(',',$line);
    $r=0;
	foreach($recs as $i=>$rec){
    	$state=$rec['state'];
    	if(isset($states[$state])){
			$sales=round($rec['sales'],0);
			if($sales > 0){
				$r++;
				$fillkey="fill{$r}";
				$stats['count']+=1;
				if($sales > $stats['best']['sales']){
					$stats['best']=array('sales'=>$sales,'state'=>$state);
				}
				if($sales < $stats['worst']['sales']){
					$stats['worst']=array('sales'=>$sales,'state'=>$state);
				}
			}
			else{
            	$fillkey='defaultFill';
			}
			$stats['total']+=$sales;
			$line=array($state,$sales,$fillkey);
			$lines[]=implode(',',$line);
		}
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
			//filterspeech
			$sentences[]=$filterspeech;
			//months
			$sentences[]= "There are {$stats['count']} states with sales.";
			$state=$states[$stats['best']['state']]['name'];
			$sentences[]= "You sold the most in {$state} with sales of \${$stats['best']['sales']}.";
			$state=$states[$stats['worst']['state']]['name'];
			$sentences[]= "You sold the least in {$state} with sales of \${$stats['worst']['sales']}.";

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
			$rtn=$rtn;
            $rtn .= buildOnLoad("hideId('{$_REQUEST['divid']}');");
		}
		return $rtn;
	}
	$csv=implode("\n",$lines);
	pushData($csv,'csv','data.csv');
?>
