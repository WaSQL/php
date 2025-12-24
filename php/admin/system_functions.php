<?php
function systemGetProcessListExtra($recs){
	global $ADMINPAGE;
	foreach($recs as $i=>$rec){
		$recs[$i]['command']='<div style="max-width:200px;text-overflow: ellipsis;overflow:hidden;white-space:nowrap;" title="'.encodeHtml($rec['command']).'">'.encodeHtml($rec['command']).'</div>';
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
			$recs[$i]['filesystem']='<span class="'.encodeHtml($ADMINPAGE["mounted_{$rec['mounted']}_icon"]).'"></span> '.encodeHtml($rec['filesystem']);
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
		'-tableclass'=>'wacss_table bordered striped fullwidth sticky',
	);
	foreach($listopts as $k=>$v){
		if(is_array($v)){$opts[$k]=$v;}
		elseif(!strlen($v)){unset($opts[$k]);}
		else{$opts[$k]=$v;}
	}
	if(isset($_REQUEST['filter_order']) && strlen($_REQUEST['filter_order'])){
		// Sanitize field name - only allow alphanumeric, underscore, dash, and space
		$fld = preg_replace('/[^a-z0-9_\-\s]/i', '', $_REQUEST['filter_order']);
		$opts['-order']=$fld;
		if(stringEndsWith($fld,' desc')){
			$fld=str_replace(' desc','',$fld);
			$sort=SORT_DESC;
		}
		else{
			$sort=SORT_ASC;
		}
		// Only sort if the field actually exists in the data
		if(isset($recs[0][$fld])){
			$opts['-list']=sortArrayByKey($recs,$fld,$sort);
		}
	}
	return databaseListRecords($opts);
}
?>