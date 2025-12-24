<?php
function schtasksEnable($id){
	$rec=schtasksGetTask($id);
	if(!isset($rec['id'])){return false;}
	$cmd="schtasks.exe /change /tn \"{$rec['taskname']}\" /enable";
	$out=cmdResults($cmd);
	$_REQUEST['refresh']=1;
	return true;
}
function schtasksDisable($id){
	$rec=schtasksGetTask($id);
	if(!isset($rec['id'])){return false;}
	$cmd="schtasks.exe /change /tn \"{$rec['taskname']}\" /disable";
	$out=cmdResults($cmd);
	$_REQUEST['refresh']=1;
	return true;
}
function schtasksDelete($id){
	$rec=schtasksGetTask($id);
	if(!isset($rec['id'])){return false;}
	$cmd="schtasks.exe /delete /f /tn \"{$rec['taskname']}\"";
	$out=cmdResults($cmd);
	$_REQUEST['refresh']=1;
	return true;
}
function schtasksGetTask($id){
	$recs=schtasksGetRecs();
	foreach($recs as $rec){
		if($rec['id']==$id){
			return $rec;
		}
	}
	return false;
}
function schtasksGetRecs(){
	$tpath=getWasqlPath();
	$rfile="{$tpath}php/temp/admin_schtasks.raw";
	$afile="{$tpath}php/temp/admin_schtasks.json";
	$maxage=60*15;
	if(file_exists($afile)){
		$ctime=filemtime($afile);
		$ttime=time();
		$age=$ttime-$ctime;
		//if file is older than 15 min then delete it
		if($age > $maxage){
		    unlink($afile);
		    unlink($rfile);
		    //debugValue(array('afile'=>$afile,'age'=>$age,'ttime'=>$ttime,'ctime'=>$ctime,'maxage'=>$maxage));
		}
	}
	//return $recs;
	if(!file_exists($afile) || !filesize($afile) || isset($_REQUEST['refresh'])){
		$cmd = "schtasks /query /v /fo list";
		$out=cmdResults($cmd);
		setFileContents($rfile,$out['stdout']);
		$lines=preg_split('/[\r\n]+/',$out['stdout']);
		$recs=array();
		$rec=array();
		$cnt=0;
		$lastfield='';
		foreach($lines as $line){
			$line=rtrim($line);
			if(preg_match('/^\s\s\s/i',$line) && strlen($lastfield)){
				$rec[$lastfield].=ltrim($line);
				continue;
			}
			list($k,$v)=preg_split('/\:/',$line,2);
			$k=str_replace(' ','_',strtolower(trim($k)));
			$v=trim($v);
			if($k=='folder'){continue;}
			$lastfield=$k;
			if($k=='hostname' && count($rec)){
				$hash=encodeBase64($rec['taskname'].$rec['location'].$rec['task_to_run']);
				if(!isset($recs[$hash])){
					$cnt+=1;
					$rec['id']=$cnt;
					$recs[$hash]=$rec;
				}
				$lastfield='';
				$rec=array();
			}
			$rec[$k]=$v;
		}
		if(count($rec)){
			$hash=encodeBase64($rec['taskname'].$rec['location'].$rec['task_to_run']);
			if(!isset($recs[$hash])){
				$cnt+=1;
				$rec['id']=$cnt;
				$recs[$hash]=$rec;
			}
		}
		//extract location from taskname
		foreach($recs as $i=>$rec){
			$rec['taskname']=str_replace("\\",'::',$rec['taskname']);
			$parts=preg_split('/\:\:/',$rec['taskname']);
			if(!strlen($parts[0])){
				array_shift($parts);
			}
			$recs[$i]['taskname']=array_pop($parts);
			if(count($parts)){
				$recs[$i]['location']=implode('/',$parts);
			}
			else{
				$recs[$i]['location']='';
			}
		}
		//exit;
		$json=encodeJson($recs,JSON_PRETTY_PRINT);
		setFileContents($afile,$json);
	}
	else{
		$json=getFileContents($afile);
		$recs=decodeJson($json);
	}
	$recs=sortArrayByKeys($recs,array('scheduled_task_state'=>SORT_DESC,'location'=>SORT_ASC,'taskname'=>SORT_ASC));
	return $recs;
}
function schtasksList(){
	$recs=schtasksGetRecs();
	$stats=array();
	foreach($recs as $i=>$rec){
		$stats['scheduled_task_state'][$rec['scheduled_task_state']]+=1;
		$stats['status'][$rec['status']]+=1;
		$stats['run_as_user'][$rec['run_as_user']]+=1;
	}
	$stats['scheduled_task_state']=encodeJson($stats['scheduled_task_state']);
	$stats['status']=encodeJson($stats['status']);
	$stats['run_as_user']=encodeJson($stats['run_as_user']);
	$pretable=<<<ENDOFPRETABLE
<table class="wacss_table condensed striped">
	<tr>
		<th class="w_smaller">Status</th>
		<th class="w_smaller">State</th>
		<th class="w_smaller">Run As User</th>
	</tr>
	<tr>
		<td class="w_small">{$stats['status']}</td>
		<td class="w_small">{$stats['scheduled_task_state']}</td>
		<td class="w_small">{$stats['run_as_user']}</td>
	</tr>
</table>
ENDOFPRETABLE;
	$filter_order=isset($_REQUEST['filter_order'])?$_REQUEST['filter_order']:'';
	$pretable=<<<ENDOFPRE
<form method="post" name="searchfiltersform" action="/php/admin.php" onsubmit="return wacss.pagingSubmit(this);">
	<input type="hidden" name="_menu" value="schtasks">
	<input type="hidden" name="_filters" value="">
	<input type="hidden" name="filter_order" value="{$filter_order}">
</form>
ENDOFPRE;
	$listfields=array('taskname','location','status','scheduled_task_state','task_to_run','schedule_type','run_as_user','comment');
	//sort recs
	if(isset($_REQUEST['filter_order']) && strlen($_REQUEST['filter_order'])){
		foreach($listfields as $listfield){
			if(stringBeginsWith($_REQUEST['filter_order'],$listfield)){
				if(stringEndsWith($_REQUEST['filter_order'],' desc')){
					$recs=sortArrayByKeys($recs,array($listfield=>SORT_DESC));
				}
				else{
					$recs=sortArrayByKeys($recs,array($listfield=>SORT_ASC));
				}
				break;
			}
		}
	}
	$opts=array(
		'-list'=>$recs,
		'-truecount'=>1,
		'-pretable'=>$pretable,
		'-listfields'=>$listfields,
		'-pretable'=>$pretable,
		'-sorting'=>1,
		'-tableclass'=>'wacss_table striped bordered sticky',
		'_menu'=>'schtasks',
		'-tableheight'=>'75vh',
		'-results_eval'=>'schtasksListExtra',
		'status_class'=>'w_nowrap',
		'scheduled_task_state_options'=>array(
			'class'=>'w_nowrap',
			'displayname'=>'State'
		),
	);
	return databaseListRecords($opts);
}
function schtasksListExtra($recs){
	global $CONFIG;
	foreach($recs as $i=>$rec){
		//scheduled_task_state
		switch(strtolower($rec['scheduled_task_state'])){
			case 'enabled':
				$recs[$i]['scheduled_task_state']='<span data-id="'.$rec['id'].'" data-div="schtasks_results" data-_menu="schtasks" data-func="disable" data-nav="'.$CONFIG['admin_form_url'].'" onclick="wacss.nav(this);" data-confirm="Disable this task?" class="icon-mark w_bold w_success w_pointer" title="click to disable"></span> '.$rec['scheduled_task_state'];
			break;
			case 'disabled':
				$recs[$i]['scheduled_task_state']='<span data-id="'.$rec['id'].'" data-div="schtasks_results" data-_menu="schtasks" data-func="enable" data-nav="'.$CONFIG['admin_form_url'].'" onclick="wacss.nav(this);" data-confirm="Enable this task?" class="icon-block w_bold w_danger w_pointer" title="click to enable"></span> '.$rec['scheduled_task_state'];
			break;
		}
		//clicking on taskname gives full details
		$recs[$i]['taskname']=<<<ENDOFTASKNAME
	<a class="w_link" data-id="{$rec['id']}" href="#" data-div="centerpop" data-_menu="schtasks" data-func="details" data-nav="{$CONFIG['admin_form_url']}" onclick="return wacss.nav(this);" data-title="Details - {$rec['taskname']}" title="click to view details">{$rec['taskname']}</a>
ENDOFTASKNAME;
		//status
		switch(strtolower($rec['status'])){
			case 'ready':
				$recs[$i]['status']='<span class="icon-mark w_bold w_success"></span> '.$rec['status'];
			break;
			case 'disabled':
				$recs[$i]['status']='<span class="icon-block w_bold w_danger"></span> '.$rec['status'];
			break;
			case 'running':
				$recs[$i]['status']='<span class="icon-spin4 w_spin"></span> '.$rec['status'];
			break;
		}
		if(isXML($rec['comment'])){
			$recs[$i]['comment']="<pre style=\"text-wrap:inherit;margin:0;\">".encodeHtml($rec['comment'])."</pre>";
		}
		$brands=array('microsoft','lenovo','dell','samsung','hp','apple','google','mozilla');
		foreach($brands as $brand){
			if(stringBeginsWith($rec['location'],"{$brand}")){
				$recs[$i]['location']='<span class="brand-'.$brand.'"></span> '.$rec['location'];
				break;
			}
		}
	}
	return $recs;
}
?>