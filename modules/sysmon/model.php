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
		'-tableclass'=>'table table-responsive responsive table-condensed table-bordered table-hover table-bordered condensed striped bordered hover',
		'-results_eval'=>'sysmonProcessListExtra',
		'pcpu_displayname'=>'%CPU',
		'pmem_displayname'=>'%MEM',
		'pid_displayname'=>'PID',
		'pcpu_class'=>'align-right',
		'pmem_class'=>'align-right',
		'pid_class'=>'align-right',
		'command_class'=>'truncate',
		'user_class'=>'truncate',
		'command_style'=>'max-width:200px;'
	);
	return databaseListRecords($opts);
}
function sysmonProcessListExtra($recs){
	global $PAGE;
	foreach($recs as $i=>$rec){
		
	}
	return $recs;
}

function sysmonMountsList(){
	//note: df is piped to a file then read to get around splitting issue on some platforms
	$path=getWasqlPath('php/temp');
	$outfile="{$path}/dfb1.txt";
	$cmd=<<<ENDOFCMD
df -B1 | awk '{print \$1","\$2","\$3","\$4","\$5","\$6","\$7","\$8}' >{$outfile}
ENDOFCMD;
	$cmd=trim($cmd);
	$out=`$cmd`;
	if(file_exists($outfile)){
		$lines=file($outfile);
		unlink($outfile);
		foreach($lines as $i=>$line){
			if($i==0){continue;}
			$line=trim($line);
			$line=preg_replace('/\,+$/','',$line);
			$parts=preg_split('/\,/',$line);
			if(count($parts) > 6){
				$c=count($parts) - 5;
				$xparts=array();
				for($x=0;$x<$c;$x++){
					$xparts[]=$parts[$x];
				}
				$nparts=array(implode(' ',$xparts));
				for($x=$c;$x<count($parts);$x++){
					$nparts[]=$parts[$x];
				}
				$lines[$i]=implode(',',$nparts);
			}
		}
	}
	$out=implode(PHP_EOL,$lines);
	$recs=CSV2Arrays($out);
	$recs=array_change_key_case($recs,CASE_LOWER);
	foreach($recs as $i=>$rec){
		$recs[$i]=array_change_key_case($recs[$i],CASE_LOWER);
		//echo printValue($rec);exit;
		$recs[$i]['1b-blocks']=verboseSize($recs[$i]['1b-blocks']);
		$recs[$i]['used']=verboseSize($recs[$i]['used']);
		$recs[$i]['available']=verboseSize($recs[$i]['available']);
		$pcnt=(integer)$recs[$i]['use%'];
		$bgcolor='#17a2b8';
		if($pcnt > 75){
			$bgcolor='#dc3545';
		}
		elseif($pcnt > 60){
			$bgcolor='#ffc107';
		}
		$pcntstr='<div class="w_right">'.$recs[$i]['use%'].'</div>';
		//add bar
		$recs[$i]['use%']='<div style="border:1px solid #ccd2d9;height:15px;width:150px;display:inline-block;">';
		$recs[$i]['use%'].='<div style="disply:inline-block;height:15px;width:'.$pcnt.'%;background-color:'.$bgcolor.';"></div>';
		$recs[$i]['use%'].='</div>'.$pcntstr;	
	}
	$opts=array(
		'-list'=>$recs,
		'-tableclass'=>'table table-responsive responsive bordered striped is-bordered is-striped is-fullwidth',
		'-hidesearch'=>1,
		'-listfields'=>'filesystem,1b-blocks,used,available,use%,mounted',
		'1b-blocks_displayname'=>'Size',
		'size_class'=>'align-right w_nowrap',
		'used_class'=>'align-right w_nowrap',
		'available_class'=>'align-right w_nowrap',
		'use%_class'=>'align-left w_nowrap'
	);
	return databaseListRecords($opts);
}

?>