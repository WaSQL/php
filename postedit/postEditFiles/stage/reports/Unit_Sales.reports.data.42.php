<?php
	global $rec;
	//default databack to csv
	if(!strlen($_REQUEST['databack'])){
		$_REQUEST['databack']='csv';
	}
	$rec['return_type']=$_REQUEST['databack'];
	//table and export just need csv also
	if($rec['return_type']=='table'){
    	$rec['return_type']='csv';
	}
	elseif($rec['return_type']=='export'){
    	$rec['return_type']='csv';
	}
	//get data to return - this calls the decoupled code if specified
	$data=commonLoadReportData($rec);
	//return based on databack value
	switch(strtolower($_REQUEST['databack'])){
    	case 'sql':
    	case 'speech':
    		return $data;
    	break;
    	case 'export':
    		pushData($data,'csv',$rec['name'].'.csv');
    	break;
    	case 'table':
    		//convert csv data to recs array
    		$trecs=accountCSV2Recs($data);
			$rtn=listDBRecords(array('-list'=>$trecs,'-listfields'=>$keys,'-tableclass'=>'table table-bordered table-striped table-condensed'));
			return $rtn;
    	break;
    	case 'csv':
    	default:
    		//convert csv data to recs array
    		$trecs=accountCSV2Recs($data);
			//combine days
			$data=array();
			foreach($trecs as $rec){
				$qtys=json_decode($rec['qty'],true);
				foreach($qtys as $qty){
					$data['day'][$rec['day']]+=$qty;
					$data['month'][$rec['month']]+=$qty;
					$data['year'][$rec['year']]+=$qty;
					$data['total']+=$qty;
				}
			}
			//sort by day
			ksort($data['day']);
			ksort($data['month']);
			ksort($data['year']);
			//create csv for chart data
			$lines=array();
			$line=array('day','qty');
			$lines[]=implode(',',$line);
			foreach($data['day'] as $day=>$qty){
				$line=array($day,$qty);
				$lines[]=implode(',',$line);
			}
			$csv=implode("\n",$lines);
			pushData($csv,'csv','data.csv');
		break;
	}
?>
