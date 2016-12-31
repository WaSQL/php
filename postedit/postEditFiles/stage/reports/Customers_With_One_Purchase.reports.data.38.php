<?php
	global $rec;
	$report=commonBuildFilters($rec);
	$filterspeech=$report['filterspeech'];
	//Get the data
	$query=<<<ENDOFSQL
	SELECT
		count(*) as count
		,max(cdate) as cdate
		,customer_id
		,email
		,firstname
		,lastname
	FROM clientdata_woo_orders
	WHERE
		{$report['filterstr']}
	GROUP BY
		customer_id
		,email
		,firstname
		,lastname
	HAVING count(*)=1
	ORDER BY
		cdate,firstname,lastname
ENDOFSQL;
//echo $query;exit;
	//cache the results for 1 minute so we have enough time to get the table view
	$hrs=1/60;
	$sv="return getDBRecords(\"{$query}\",1,$hrs);";
	$recs=getStoredValue($sv);
	$cyear=date('Y');
	foreach($recs as $i=>$rec){
		$recs[$i]['timestamp']=strtotime($rec['cdate']);
		$recs[$i]['year']=date('Y',$recs[$i]['timestamp']);
    	$recs[$i]['cdate']=date('m/d/Y',$recs[$i]['timestamp']);
    	//add a color based on how many year it has been
    	switch($cyear-$recs[$i]['year']){
    		case 0:$recs[$i]['class']='w_primary';break;
    		case 1:$recs[$i]['class']='w_warning';break;
    		case 2:$recs[$i]['class']='w_danger';break;
    		default:$recs[$i]['class']='w_grey';break;
		}
	}
	$lines=array();
	$line=array('firstname','lastname','cdate');
    $lines[]=implode(',',$line);
	$rtn='';
	switch(strtolower($_REQUEST['databack'])){
		case 'sql':
			$rtn=$query;
		break;
		case 'speech':
			if(!isset($recs[0])){
            	$rtn="You have no customers with one purchase only";
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
				$xrecs=array();
				$keys=array('cdate','customer_id','firstname','lastname','email');
				foreach($recs as $rec){
					$xrec=array();
					foreach($keys as $key){$xrec[$key]=$rec[$key];}
					$xrecs[]=$xrec;
				}
				$filename=str_replace(' ','_',$report['name']).'.csv';
				$csv=arrays2Csv($xrecs);
				pushData($csv,'csv',$filename);
			}
			$rtn=listDBRecords(array('-list'=>$trecs,'-listfields'=>$keys,'-tableclass'=>'table table-bordered table-striped'));
		break;
	}
	if(strlen($rtn)){
    	if(isset($_REQUEST['divid'])){
            $rtn .= buildOnLoad("hideId('{$_REQUEST['divid']}');");
		}
		echo $rtn;exit;
	}
	//echo printValue($recs);exit;
	if(!isset($recs[0])){
    	echo "You have no customers with one purchase only";
    	exit;
	}
	$keys=array('firstname','lastname','cdate');
	$opts=array('-list'=>$recs,'-listfields'=>$keys,'-tableclass'=>'table table-bordered table-striped');
	$opts['cdate_class']='class';
	//return printValue($opts);
	echo listDBRecords($opts);exit;
?>
