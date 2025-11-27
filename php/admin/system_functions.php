<?php
function systemGetProcessListExtra($recs){
	global $ADMINPAGE;
	foreach($recs as $i=>$rec){
		$recs[$i]['command']='<div style="max-width:200px;text-overflow: ellipsis;overflow:hidden;white-space:nowrap;" title="'.$rec['command'].'">'.$rec['command'].'</div>';
	}
	return $recs;
}
function systemGetDriveSpaceExtra($recs){
	global $ADMINPAGE;
	foreach($recs as $i=>$rec){
		if(isset($ADMINPAGE["mounted_{$rec['mounted']}_hide"])){
			unset($recs[$i]);
			continue;
		}
		elseif(isset($ADMINPAGE["filesystem_{$rec['filesystem']}_hide"])){
			unset($recs[$i]);
			continue;
		}
		if(isset($ADMINPAGE["mounted_{$rec['mounted']}_icon"])){
			$recs[$i]['filesystem']='<span class="'.$ADMINPAGE["mounted_{$rec['mounted']}_icon"].'"></span> '.$rec['filesystem'];
		}

	}
	return $recs;
}
function systemShowList($recs,$listopts=array()){
	$opts=array(
		'-list'=>$recs,
		'-navonly'=>1,
		'setprocessing'=>0,
		'-sorting'=>1,
		'_menu'=>'system',
		'-onsubmit'=>"return pagingSubmit(this,'system_content');",
		'-formname'=>'systemlist',
		'-tableclass'=>'wacss_table responsive bordered striped fullwidth sticky',
		'-tableheight'=>'80vh',
	);
	foreach($listopts as $k=>$v){
		if(is_array($v)){$opts[$k]=$v;}
		elseif(!strlen($v)){unset($opts[$k]);}
		else{$opts[$k]=$v;}
	}
	if(isset($_REQUEST['filter_order'])){
		$fld=$_REQUEST['filter_order'];
		$opts['-order']=$fld;
		if(stringEndsWith($fld,' desc')){
			$fld=str_replace(' desc','',$fld);
			$sort=SORT_DESC;
		}
		else{
			$sort=SORT_ASC;
		}
		if(isset($recs[0][$fld])){
			$opts['-list']=sortArrayByKey($recs,$fld,$sort);
		}
	}
	return databaseListRecords($opts);
}
?>