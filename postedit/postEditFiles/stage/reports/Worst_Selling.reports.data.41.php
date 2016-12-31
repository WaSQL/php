<?php
	global $rec;
	global $alexa;
	$report=commonBuildFilters($rec);
	$filterspeech=$report['filterspeech'];
	//best selling day | date | product | state | zipcode | month
	$groups=array('day','date','product','state','zipcode','month');
	if(isset($alexa['swim']['group'])){
    	$groups=array(strtolower($alexa['swim']['group']));
	}
	$data=array();
	/*  DAY */
	if(in_array('day',$groups)){
		//Best Selling Day (of the week)
		$query=<<<ENDOFQUERY
		SELECT
			sum(amount) as sales
			,dayofweek(cdate) as day
		FROM clientdata_woo_orders
		WHERE
			{$report['filterstr']}
		GROUP BY
			dayofweek(cdate)
		ORDER BY sum(amount) asc
		LIMIT 1
ENDOFQUERY;
		$hrs=5/60;
		$sv="return getDBRecords(\"{$query}\",1,$hrs);";
		$recs=getStoredValue($sv);
		$days=array('','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
		$data[]=array('kpi'=>'day','label'=>$days[$recs[0]['day']],'value'=>$recs[0]['sales']);
	}
	/*  DATE */
	if(in_array('date',$groups)){
		//Best Selling Date (day of the month)
		$query=<<<ENDOFQUERY
		SELECT
			sum(amount) as sales
			,date(cdate) as date
		FROM clientdata_woo_orders
		WHERE
			{$report['filterstr']}
		GROUP BY
			date(cdate)
		ORDER BY sum(amount) asc
		LIMIT 1
ENDOFQUERY;
		$hrs=5/60;
		$sv="return getDBRecords(\"{$query}\",1,$hrs);";
		$recs=getStoredValue($sv);
		$data[]=array('kpi'=>'date','label'=>date('jS',strtotime($recs[0]['date'])),'value'=>$recs[0]['sales']);
	}
	/*  PRODUCT */
	if(in_array('product',$groups)){
		//Best Selling product
		$query=<<<ENDOFQUERY
		SELECT
			product_names,product_qtys
		FROM clientdata_woo_orders
		WHERE
			{$report['filterstr']}
ENDOFQUERY;
		$hrs=5/60;
		$sv="return getDBRecords(\"{$query}\",0,$hrs);";
		$recs=getStoredValue($sv);
		$best=array();
		foreach($recs as $rec){
			$str=preg_replace('/^\[/','return array(',$rec['product_names']);
			$str=preg_replace('/\]$/',');',$str);
			$names=eval($str);
			$str=preg_replace('/^\[/','return array(',$rec['product_qtys']);
			$str=preg_replace('/\]$/',');',$str);
			$qtys=eval($str);
			foreach($names as $i=>$name){
            	$best[$name]+=$qtys[$i];
			}
		}
		asort($best);
		$key=key($best);
		$data[]=array('kpi'=>'product','label'=>$key,'value'=>$best[$key]);
	}
	/*  STATE */
	if(in_array('state',$groups)){
		//Best Selling state (of the USA)
		$query=<<<ENDOFQUERY
		SELECT
			sum(amount) as sales
			,shipto_state as state
		FROM clientdata_woo_orders
		WHERE
			{$report['filterstr']}
		GROUP BY
			shipto_state
		ORDER BY sum(amount) asc
		LIMIT 1
ENDOFQUERY;
		$hrs=5/60;
		$sv="return getDBRecords(\"{$query}\",1,$hrs);";
		$recs=getStoredValue($sv);
		$data[]=array('kpi'=>'state','label'=>$recs[0]['state'],'value'=>$recs[0]['sales']);
	}
	/*  ZIPCODE */
	if(in_array('zipcode',$groups)){
		//Best Selling zipcode (of the USA)
		$query=<<<ENDOFQUERY
		SELECT
			sum(amount) as sales
			,shipto_postcode as postcode
		FROM clientdata_woo_orders
		WHERE
			{$report['filterstr']}
		GROUP BY
			shipto_postcode
		ORDER BY sum(amount) asc
		LIMIT 1
ENDOFQUERY;
		$hrs=5/60;
		$sv="return getDBRecords(\"{$query}\",1,$hrs);";
		$recs=getStoredValue($sv);
		$data[]=array('kpi'=>'zipcode','label'=>$recs[0]['postcode'],'value'=>$recs[0]['sales']);
	}
	/*  MONTH */
	if(in_array('month',$groups)){
		//Best Selling month (of the year)
		$query=<<<ENDOFQUERY
		SELECT
			sum(amount) as sales
			,monthname(cdate) as month
		FROM clientdata_woo_orders
		WHERE
			{$report['filterstr']}
		GROUP BY
			monthname(cdate)
		ORDER BY sum(amount) desc
		LIMIT 1
ENDOFQUERY;
		$hrs=5/60;
		$sv="return getDBRecords(\"{$query}\",1,$hrs);";
		$recs=getStoredValue($sv);
		$data[]=array('kpi'=>'month','label'=>$recs[0]['month'],'value'=>$recs[0]['sales']);
	}

	$rtn='';
	switch(strtolower($_REQUEST['databack'])){
		case 'sql':
			$rtn=$query;
		break;
		case 'speech':
			if(!isset($data[0])){
            	$rtn="You have no orders";
            	break;
			}
			$sentences=array();
			//filterspeech
			//$sentences[]=$filterspeech;
			foreach($data as $rec){
				//'day','date','product','state','zipcode','month'
            	switch($rec['kpi']){
                	case 'day':
                		$sentences[]= "Your worst selling day is {$rec['label']} with sales of \${$rec['value']}.";
                	break;
                	case 'date':
                		$sentences[]= "Your worst selling day of the month is the {$rec['label']} with sales of \${$rec['value']}.";
                	break;
                	case 'product':
                		$sentences[]= "Your worst selling product is {$rec['label']}. You sold {$rec['value']} units.";
                	break;
                	case 'state':
                		$sentences[]= "Your worst selling state is {$rec['label']} with sales of \${$rec['value']}.";
                	break;
                	case 'zipcode':
                		$sentences[]= "Your worst selling zipcode is {$rec['label']} with sales of \${$rec['value']}.";
                	break;
                	case 'month':
                		$sentences[]= "Your worst selling month is {$rec['label']} with sales of \${$rec['value']}.";
                	break;
				}
			}
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
				$csv=arrays2Csv($data);
				pushData($csv,'csv',$filename);
			}
			$keys=array('kpi','label','value');
			$rtn=listDBRecords(array('-list'=>$data,'-listfields'=>$keys,'-tableclass'=>'table table-bordered table-striped'));
		break;
	}
	if(strlen($rtn)){
    	if(isset($_REQUEST['divid'])){
            $rtn .= buildOnLoad("hideId('{$_REQUEST['divid']}');");
		}
		return $rtn;
	}
	$keys=array('kpi','label','value');
	$opts=array('-list'=>$data,'-listfields'=>$keys,'-tableclass'=>'table table-bordered table-striped');
	$opts['cdate_class']='class';
	//return printValue($opts);
	echo listDBRecords($opts);exit;
?>
