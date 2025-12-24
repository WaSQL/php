<?php
function sysmonGetProcessList(){
	$recs=systemGetProcessList();
	//filters
	if(!empty($_REQUEST['_filters'])){
		$filters=array();
        //field-oper-value
        if(is_array($_REQUEST['_filters'])){$sets=$_REQUEST['_filters'];}
    	else{$sets=preg_split('/[\r\n\,]+/',$_REQUEST['_filters']);}
    	foreach($sets as $set){
        	list($field,$oper,$val)=preg_split('/\-/',trim($set),3);
        	//validate field and operator to prevent injection
        	$valid_opers=array('ct','nct','eq','neq','gt','lt','egt','elt');
        	if(!in_array($oper,$valid_opers)){continue;}
        	$filters[]=array(
        		'field'=>trim($field),
        		'oper'=>trim($oper),
        		'val'=>trim($val)
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
		$parts=preg_split('/\ /',trim($_REQUEST['filter_order']));
		//validate sort order to prevent injection
		$valid_fields=array('command','pid','pcpu','pmem','session_name','status','user');
		if(count($parts) >= 1 && in_array($parts[0],$valid_fields)){
			if(count($parts)==2 && strtolower($parts[1])=='desc'){
				$recs=sortArrayByKey($recs,$parts[0],SORT_DESC);
			}
			else{
				$recs=sortArrayByKey($recs,$parts[0],SORT_ASC);
			}
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
/**
 * sysmonProcessListExtra - callback function for additional processing of process list records
 * This function is called by databaseListRecords via -results_eval parameter
 * Add custom record processing logic here if needed
 */
function sysmonProcessListExtra($recs){
	global $PAGE;
	//add custom processing here if needed
	return $recs;
}

function sysmonMountsList(){
	//note: df is piped to a file then read to get around splitting issue on some platforms
	$path=getWasqlPath('php/temp');
	if(!is_dir($path)){
		return '<div class="w_red w_bold">Error: temp directory not found</div>';
	}
	//sanitize path to prevent command injection
	$outfile=realpath($path).DIRECTORY_SEPARATOR.'dfb1.txt';
	$cmd=sprintf("df -B1 | awk '{print \$1\",\"\$2\",\"\$3\",\"\$4\",\"\$5\",\"\$6\",\"\$7\",\"\$8}' >%s",escapeshellarg($outfile));
	$out=`$cmd`;
	if(!file_exists($outfile)){
		return '<div class="w_red w_bold">Error: unable to retrieve disk information</div>';
	}
	$lines=file($outfile);
	if($lines===false){
		unlink($outfile);
		return '<div class="w_red w_bold">Error: unable to read disk information</div>';
	}
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
	$out=implode(PHP_EOL,$lines);
	$recs=CSV2Arrays($out);
	if(empty($recs)){
		return '<div class="w_red w_bold">Error: unable to parse disk information</div>';
	}
	$recs=array_change_key_case($recs,CASE_LOWER);
	foreach($recs as $i=>$rec){
		$recs[$i]=array_change_key_case($recs[$i],CASE_LOWER);
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
		$pcntstr='<div class="w_right">'.encodeHtml($recs[$i]['use%']).'</div>';
		//add bar - sanitize pcnt to prevent XSS
		$pcnt_safe=min(100,max(0,(integer)$pcnt));
		$recs[$i]['use%']='<div style="border:1px solid #ccd2d9;height:15px;width:150px;display:inline-block;">';
		$recs[$i]['use%'].='<div style="display:inline-block;height:15px;width:'.$pcnt_safe.'%;background-color:'.$bgcolor.';"></div>';
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