<?php
	function reportsRenderOption($option){
		if(isset($option['value']) && !isset($_REQUEST[$option['key']]) && $_REQUEST['status'] != 'ready'){$_REQUEST[$option['key']]=$option['value'];}
		if(isset($option['values']) && is_array($option['values'])){
			if($option['multi']){
            	return buildFormMultiSelect($option['key'],$option['values'],array('message'=>" -- {$option['key']} --"));
			}
			else{
        		return buildFormSelect($option['key'],$option['values'],array('message'=>" -- {$option['key']} --"));
			}
		}
	}
	function reportsGetReports($menu){
    		$opts=array(
			'-table'	=> '_reports',
			'active'	=> 1,
			'-order'	=> 'menu,name',
			'-fields'	=> '_id,name,description,_euser,_edate,runtime,rowcount',
			'-relate'	=> array('_euser'=>'_users'),
			'menu'		=> $menu
		);
		return getDBRecords($opts);
	}
	function reportsNewReport(){
    		return addEditDBForm(array(
			'-table'=>'_reports',
			'-action'=>'/php/admin.php',
			'_menu'=>'reports',
			'-focus'=>'name'
		));
	}
	function reportsEditReport($id){
    		return addEditDBForm(array(
			'-table'=>'_reports',
			'-action'=>'/php/admin.php',
			'_menu'=>'reports',
			'-focus'=>'name',
			'_id'=>$id
		));
	}
	function reportsGetGroups(){
		global $USER;
		//only show groups that this person has access to
		$opts=array(
			'-table'	=> '_reports',
			'active'	=> 1,
			'-order'	=> 'menu,name',
			'-fields'	=> '_id,_cuser,departments,users,menu'
		);
		$recs=getDBRecords($opts);
		$groups=array();
		foreach($recs as $rec){
        	$rec_users=array();
			if(strlen(trim($rec['users']))){
				$rec_users=preg_split('/\:/',trim($rec['users']));
			}
        	$rec_departments=array();
        	$group=strlen(trim($rec['menu']))?trim($rec['menu']):'Unknown';
        	if(strlen(trim($rec['departments']))){
				$rec_departments=preg_split('/\:/',trim($rec['departments']));
			}

        	if(count($rec_users)){
				if(!in_array($USER['_id'],$rec_users) && $USER['_id'] != $rec['_cuser']){continue;}
			}
        	if(count($rec_departments)){
				if(!strlen($USER['department']) || !in_array($USER['department'],$rec_departments)){continue;}
			}

			if(!in_array($group,$groups)){$groups[]=$group;}
		}
		return $groups;
	}
	function reportsRunReport($report){
		global $USER;
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
		$report['status']=isset($_REQUEST['status'])?$_REQUEST['status']:'input';
		foreach($report['options'] as $key=>$val){
			if(isset($val['values'])){
				if(isset($_REQUEST[$key])){
                	$report['options'][$key]['value']=$_REQUEST[$key];
				}
				elseif(isset($val['default']) && $report['status']=='input'){
					$report['options'][$key]['value']=$val['default'];
				}
				else{
					$report['options'][$key]['value']='NULL';
				}
			}
		}
		$tmp=array();
		foreach($report['options'] as $key=>$val){
        		$val['key']=$key;
        		$tmp[]=$val;
		}
		$report['options']=$tmp;

		if($report['status']=='input'){
        	return $report;
		}
		//return $report;
		//parse the query
		$query=trim($report['query']);
		foreach($report['options'] as $i=>$option){
			$value=$option['value'];
			$key=$option['key'];
			if(is_array($value)){$value=implode("','",$value);}
			if($value=='NULL'){
				$field=isset($option['field'])?$option['field']:$key;
				$query=str_replace(":{$key}",$field,$query);
			}
			else{
            	$query=str_replace(":{$key}","'{$value}'",$query);
			}
		}
		$report['offset']=isset($_REQUEST['offset'])?$_REQUEST['offset']:0;
		$report['rows']=isset($_REQUEST['rows'])?$_REQUEST['rows']:100;
		if(isset($_REQUEST['limit'])){
			$parts=preg_split('/\,/',$_REQUEST['limit']);
			if(count($parts)==2){
				$report['offset']=$parts[0];
				$report['rows']=$parts[1];
			}
			else{
            	$report['offset']=0;
				$report['rows']=$parts[0];
			}
		}
		$report['limit']="{$report['offset']},{$report['rows']}";
		if(!stringContains($query,'limit') && $_REQUEST['func']!='export'){
			//paging set to show 50 at a time
			$query .= " LIMIT {$report['limit']}";
			}
		$report['query']=$query;
		$start=microtime(true);
		$report['recs']=getDBRecords(array('-query'=>$query));
		$stop=microtime(true);
		$runtime=$stop-$start;
		$rowcount=count($report['recs'])+$report['offset'];
		$ok=editDBRecord(array(
			'-table'	=> '_reports',
			'-where'	=> "_id={$report['_id']}",
			'runtime'	=> $runtime,
			'rowcount'	=> $rowcount
		));
		if(isset($report['recs'][0])){
        	$report['fields']=array_keys($report['recs'][0]);
		}
		$report['count']=count($report['recs']);
		//set limit_prev and limit_next
		if($report['offset'] > 0){
        	$offset=$report['offset']-$report['rows'];
        	if($offset<0){$offset=0;}
        	$report['limit_prev']="{$offset},{$report['rows']}";
		}
		if($report['count'] == $report['rows']){
			$offset=$report['offset']+$report['rows'];
        	$report['limit_next']="{$offset},{$report['rows']}";
		}
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
