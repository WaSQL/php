<?php
function sysmonGetProcessList(){
	$recs=systemGetProcessList();
	//filters
	if(!empty($_REQUEST['_filters'])){
		$filters=array();
        //field-oper-value
        if(is_array($params['_filters'])){$sets=$_REQUEST['_filters'];}
    	else{$sets=preg_split('/[\r\n\,]+/',$_REQUEST['_filters']);}
    	foreach($sets as $set){
        	list($field,$oper,$val)=preg_split('/\-/',trim($set),3);
        	$filters[]=array(
        		'field'=>$field,
        		'oper'=>$oper,
        		'val'=>$val
        	);
        }
        foreach($recs as $i=>$rec){
        	$keep=0;
        	foreach($filters as $filter){
        		$field=$filter['field'];
        		switch($filter['oper']){
		        	case 'ct':
		        		if(stringContains($rec[$field],$filter['val'])){$keep+=1;}
		        	break;
		        	case 'nct':
		        		if(!stringContains($rec[$field],$filter['val'])){$keep+=1;}
		        	break;
					case 'eq': 
						if($rec[$field]==$filter['val']){$keep+=1;}
					break;
					case 'neq':
						if($rec[$field]!=$filter['val']){$keep+=1;}
					break;
					case 'gt':
						if($rec[$field] > $filter['val']){$keep+=1;}
					break;
					case 'lt':
						if($rec[$field] < $filter['val']){$keep+=1;}
					break;
					case 'egt':
						if($rec[$field] >= $filter['val']){$keep+=1;}
					break;
					case 'elt':
						if($rec[$field] <= $filter['val']){$keep+=1;}
					break;
				}
        	}
        	if($keep < count($filters)){
        		unset($recs[$i]);
        	}
        }
	}
	//order by
	if(isset($_REQUEST['filter_order']) && strlen($_REQUEST['filter_order'])){
		$parts=preg_split('/\ /',$_REQUEST['filter_order']);
		if(count($parts)==2 && $parts[1]=='desc'){
			$recs=sortArrayByKey($recs,$parts[0],SORT_DESC);
		}
		else{
			$recs=sortArrayByKey($recs,$parts[0],SORT_ASC);
		}
		
	}
	return $recs;
}
function sysmonProcessList($recs){
	global $PAGE;
	global $MODULE;
	
	$opts=array(
		'-list'=>$recs,
		'-export'=>1,
		'-sorting'=>1,
		'-sortfields'=>'command,pid,pcpu,pmem,session_name,status,user',
		'-filters'=>$_REQUEST['_filters'],
		'-searchopers'=>'ct,nct,eq,ne,gt,lt,egt,elt',
		'-listfields'=>'command,pid,pcpu,pmem,session_name,status,user',
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered condensed striped bordered hover',
		'-results_eval'=>'sysmonProcessListExtra',
		'pcpu_displayname'=>'%CPU',
		'pmem_displayname'=>'%MEM',
		'pid_displayname'=>'PID',
		'pcpu_class'=>'align-right',
		'pmem_class'=>'align-right',
		'pid_class'=>'align-right',
	);
	return databaseListRecords($opts);
}
function sysmonProcessListExtra($recs){
	global $PAGE;
	foreach($recs as $i=>$rec){
		
	}
	return $recs;
}

?>