<?php
	global $rec;
	$report=commonBuildFilters($rec);
	$filterspeech=$report['filterspeech'];
	//Get the data
	$query=<<<ENDOFSQL
	SELECT
		sum(amount) as sales
		,shipto_postcode as postcode
		,shipto_country as country
	FROM clientdata_woo_orders
	WHERE
		{$report['filterstr']}
	GROUP BY 
		shipto_postcode
		,shipto_country
	ORDER BY
		sum(amount) desc
ENDOFSQL;
//echo $query;exit;
	//cache the results for 1 minute so we have enough time to get the table view
	$hrs=1/60;
	$sv="return getDBRecords(\"{$query}\",1,$hrs);";
	$tmp=getStoredValue($sv);
	$total=0;
	$recs=array();
	global $ex;
	foreach($tmp as $rec){
		//$ex=reportGetPostcodeEx($rec['postcode']);
		//foreach($ex as $k=>$v){$rec[$k]=$v;}

		if(!isset($ex[$rec['country']][$rec['postcode']])){
			$opts=array(
				'-table'=>'cities',
				'country'=>$rec['country'],
				'zipcode'=>$rec['postcode'],
				'-fields'=>'city,state'
			);
			//echo printValue($opts);exit;
    		$ex[$rec['country']][$rec['postcode']]=getDBRecord($opts);
			if(!is_array($ex[$rec['country']][$rec['postcode']])){
            	$ex[$rec['country']][$rec['postcode']]=array();
			}
		}
		//echo printValue($ex[$rec['country']][$rec['postcode']]);exit;
		foreach($ex[$rec['country']][$rec['postcode']] as $k=>$v){
        	$rec[$k]=$v;
		}
    	$recs[]=$rec;
    	//echo printValue($rec);
	}

	$lines=array();
	$line=array('postcode','city','state','sales','pcnt');
    $lines[]=implode(',',$line);
    $stats=array(
		'best'=>1,
		'worst'=>1,
		'total'=>0,
		'count'=>0
	);
    foreach($recs as $i=>$rec){
		$stats['total']+=$rec['sales'];
		if($rec['sales'] > 0){$stats['count']+=1;}
		if(isset($rec['city']) && $rec['sales'] > $recs[$stats['best']]['sales']){
			$stats['best']=$i;
		}
		if(isset($rec['city']) && $rec['sales'] > 0 && $rec['sales'] < $recs[$stats['best']]['sales']){
			$stats['worst']=$i;
		}
	}
	foreach($recs as $i=>$rec){
		$recs[$i]['pcnt']=round(($rec['sales']/$stats['total'])*100,1);
    	$line=array($rec['postcode'],$rec['city'],$rec['state'],$rec['sales'],$recs[$i]['pcnt']);
    	$lines[]=implode(',',$line);
	}
	$rtn='';
	switch(strtolower($_REQUEST['databack'])){
		case 'sql':
			$rtn=$query;
		break;
		case 'speech':
			if(!isset($recs[0])){
            	$rtn="You had no sales";
            	break;
			}
			$sentences=array();
			//filterspeech
			$sentences[]=$filterspeech;
			//months
			$sentences[]= "There are {$stats['count']} zipcodes with sales.";
			$i=$stats['best'];
			$sentences[]= "You sold the most in {$recs[$i]['city']},{$recs[$i]['state']} with sales of \${$recs[$i]['sales']}.";
			$i=$stats['worst'];
			$sentences[]= "You sold the least in {$recs[$i]['city']},{$recs[$i]['state']} with sales of \${$recs[$i]['sales']}.";

			$rtn=implode(' ', $sentences);
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
		echo $rtn;exit;
	}
	//echo printValue($recs);exit;
	$keys=array('postcode','city','state','sales','pcnt');
	$opts=array('-list'=>$recs,'-listfields'=>$keys,'-tableclass'=>'table table-bordered table-striped table-condensed');
	//return printValue($opts);
	echo listDBRecords($opts);exit;
?>
