<?php
function systemGetDiskStatsExtra($recs){
	global $ADMINPAGE;
	foreach($recs as $i=>$rec){
		// Add visual indicators for performance issues
		// Note: Values are already formatted by eval, we just add color coding

		// Activity indicator with color coding
		if(isset($rec['activity_pcnt'])){
			$value=$rec['activity_pcnt'];
			// Extract numeric value from already-formatted string if needed
			$activity=is_numeric($value)?$value:floatval($value);
			$color='';
			if($activity>90){
				$color='w_red w_bold'; // Very high activity - potential bottleneck
			}
			elseif($activity>70){
				$color='w_orange'; // High activity
			}
			elseif($activity>30){
				$color='w_bold'; // Moderate activity
			}
			else{
				$color='w_muted'; // Low activity
			}
			if($color){
				$recs[$i]['activity_pcnt']='<span class="'.$color.'">'.$value.'</span>';
			}
		}

		// Latency indicators with color coding
		if(isset($rec['read_latency_ms'])){
			$value=$rec['read_latency_ms'];
			$latency=is_numeric($value)?$value:floatval($value);
			$color='';
			if($latency>100){
				$color='w_red w_bold'; // Very slow
			}
			elseif($latency>50){
				$color='w_orange'; // Slow
			}
			elseif($latency>20){
				$color='w_bold'; // Moderate
			}
			if($color){
				$recs[$i]['read_latency_ms']='<span class="'.$color.'">'.$value.'</span>';
			}
		}

		if(isset($rec['write_latency_ms'])){
			$value=$rec['write_latency_ms'];
			$latency=is_numeric($value)?$value:floatval($value);
			$color='';
			if($latency>100){
				$color='w_red w_bold';
			}
			elseif($latency>50){
				$color='w_orange';
			}
			elseif($latency>20){
				$color='w_bold';
			}
			if($color){
				$recs[$i]['write_latency_ms']='<span class="'.$color.'">'.$value.'</span>';
			}
		}

		// Queue depth indicator
		if(isset($rec['current_disk_queue_length'])){
			$value=$rec['current_disk_queue_length'];
			$queue=is_numeric($value)?$value:floatval($value);
			$color='';
			if($queue>10){
				$color='w_red w_bold'; // Very high queue - serious bottleneck
			}
			elseif($queue>5){
				$color='w_orange'; // High queue
			}
			elseif($queue>2){
				$color='w_bold'; // Moderate queue
			}
			if($color){
				$recs[$i]['current_disk_queue_length']='<span class="'.$color.'">'.$value.'</span>';
			}
		}

		// Status indicator with icon
		if(isset($rec['status'])){
			$status=strtolower(trim($rec['status']));
			if($status=='ok'){
				$recs[$i]['status']='<span class="icon-check w_green" title="OK"></span>';
			}
			elseif($status=='error' || $status=='degraded' || $status=='failed'){
				$recs[$i]['status']='<span class="icon-warning w_red w_bold" title="'.encodeHtml(ucfirst($status)).'"></span>';
			}
			else{
				$recs[$i]['status']='<span class="icon-help w_orange" title="'.encodeHtml(ucfirst($status)).'"></span>';
			}
		}
	}
	return $recs;
}
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