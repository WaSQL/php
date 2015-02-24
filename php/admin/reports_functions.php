<?php
	function reportsGetReports($menu){
    	$opts=array(
			'-table'	=> '_reports',
			'active'	=> 1,
			'-order'	=> 'menu,name',
			'-fields'	=> '_id,name,description',
			'menu'		=> $menu
		);
		return getDBRecords($opts);
	}
	function reportsNewReport(){
    	return addEditDBForm(array(
			'-table'=>'_reports',
			'-action'=>'/php/admin.php',
			'_menu'=>'reports',
			'-focus'=>'name',
			'options_style'=>'white-space: nowrap;',
			'query_style'=>'white-space: nowrap;',
		));
	}
	function reportsEditReport($id){
    	return addEditDBForm(array(
			'-table'=>'_reports',
			'-action'=>'/php/admin.php',
			'_menu'=>'reports',
			'-focus'=>'name',
			'options_style'=>'white-space: nowrap;',
			'query_style'=>'white-space: nowrap;',
			'_id'=>$id
		));
	}
	function reportsGetGroups(){
		$query="select distinct(menu) as name from _reports where active=1 order by menu";
		return getDBRecords(array('-query'=>$query));
	}
	function reportsRunReport($report){
		//load options
		$options=array();
		if(strlen($report['options'])){
			$lines=preg_split('/[\r\n]+/',$report['options']);
        	foreach($lines as $line){
            	$line=trim($line);
            	if(!strlen($line) || preg_match('/^(\-\-|\#)/',$line)){continue;}
            	list($str,$values)=preg_split('/\=/',$line,2);
            	list($key,$type)=preg_split('/\:/',$str);
				if(strlen($type)){
                	$options[$key][$type]=reportsGetValues($values);
				}
            	else{$options[$key]=reportsGetValues($values);}
			}
		}
		$report['options']=$options;
		unset($options);
		//if all the options with a values key also have defaults then run it. otherwise ask for input.
		// status: ready,input
		$status='ready';
		foreach($report['options'] as $key=>$val){
			if(isset($val['values'])){
				if(isset($_REQUEST[$key])){
                	$report['options'][$key]['value']=$_REQUEST[$key];
				}
				elseif(isset($val['default'])){
					$report['options'][$key]['value']=$val['default'];
				}
				else{
					$report['status']='input';
					return $report;
				}
			}
		}
		//parse the query
		$query=trim($report['query']);
		foreach($report['options'] as $key=>$val){
			$value=$report['status'][$key]['value'];
			$query=str_replace(":{$key}","'{$value}'",$query);
		}
		$report['recs']=getDBRecords(array('-query'=>$query));
		return $report;
	}
	function reportsGetValues($str){
    	$values=array();
    	if(stringBeginsWith($str,'select')){
            $recs=getDBRecords(array('-query'=>$str));
            if(is_array($recs)){
	            $fields=array_keys($recs[0]);
	            $tval_key=array_shift($fields);
	            if(!count($fields)){$fields[]=$tval_key;}
				foreach($recs as $rec){
					$tval=$rec[$tval_key];
					$dvals=array();
					foreach($fields as $field){$dvals[]=$rec[$field];}
					$dval=implode(' ',$dvals);
	                $values[$tval]=$dval;
				}
			}
		}
		else{
			$parts=csvParseLine($str);
			if(count($parts)==1){return $str;}
			foreach($parts as $part){
            	list($tval,$dval)=preg_split('/\:/',$part,2);
            	if(!strlen($dval)){$dval=$tval;}
            	$values[$tval]=$dval;
			}
		}
		return $values;
	}
?>
