<?php
//test
function cronAddEdit($id=0){
	$url=configValue('admin_form_url');
	if(!stringContains($url,'admin.php')){
		$url='/t/1'.$url;
	}
	$opts=array(
		'-table'=>'_cron',
		'-action'=>$url,
		'setprocessing'=>0,
		'run_cmd_displayname'=>'Run Cmd: (command, page name, or url)',
		'_menu'=>'cron',
		'func'=>'list',
		'-onsubmit'=>"return wacss.ajaxPost(this,'cron_results');",
		'-style_all'=>'width:100%',
		'-class_all'=>'browser-default',
		'run_length_readonly'=>1,
		'run_result_options'=>array(
			'readonly'=>1,
			'height'=>50
		),
		'run_format_options'=>array(
			'inputtype'=>'frequency',
			'style'=>'width:100%;height:32px;overflow:hidden;'
		),
		'frequency_onchange'=>"return wacss.formSetFrequency('addedit_run_format',parseInt(this.value));"
	);
	if($id > 0){
		$opts['_id']=$id;
		$opts['-fields']=getView('edit_fields');
	}
	else{
		$opts['name']='';
		$opts['-fields']=getView('add_fields');
	}
	if(isset($_REQUEST['filter_order']) && strlen($_REQUEST['filter_order'])){
		$opts['filter_order']=$_REQUEST['filter_order'];
	}
	if(isset($_REQUEST['_filters']) && strlen($_REQUEST['_filters'])){
		$opts['_filters']=$_REQUEST['_filters'];
	}
	return addEditDBForm($opts);
}
function cronDetailsList($id){
	$recs=getDBRecords(array(
		'-table'=>'_cron_log',
		'cron_id'=>$id,
		'-order'=>'_id desc',
		'-limit'=>1000
	));
	if(!isset($recs[0])){
		return '';
	}
	$rtn=databaseListRecords(array(
		'-list'=>$recs,
		'-tableclass'=>'wacss_table striped bordered condensed',
		'-listfields'=>'date,time,run,action',
		'-results_eval'=>'cronDetailsListExtra',
		'-table_data-onload'=>"wacss.nav(document.querySelector('#tr_{$recs[0]['_id']}'));",
		'time_class'=>'w_nowrap',
		'run_options'=>array(
			'class'=>'w_nowrap align-right',
			'displayname'=>'<div class="align-center"><span class="icon-clock"></span></div>'
		),
		'action_options'=>array(
			'class'=>'w_nowrap align-right',
			'displayname'=>'<div class="align-center"><span class="icon-forward"></span></div>'
		),
		'-hidesearch'=>1,
		'-tr_data-_menu'=>'cron',
		'-tr_data-func'=>'details_log',
		'-tr_data-nav'=>'/php/admin.php',
		'-tr_data-div'=>'cron_details_content',
		'-tr_data-setprocessing'=>'0',
		'-tr_data-id'=>'%_id%',
		'-tr_data-tr'=>'1',
		'-tr_id'=>'tr_%_id%'
	));
	return $rtn;
}
function cronDetailsListExtra($recs){
	foreach($recs as $i=>$rec){
		$header=decodeJson($rec['header']);
		$footer=decodeJson($rec['footer']);
		$recs[$i]['date']=date('m/d/Y',$header['timestamp']);
		$recs[$i]['time']=date('h:i a',$header['timestamp']);
		if(isset($footer['timestamp'])){
			$elapsed=$footer['timestamp']-$header['timestamp'];	
			$recs[$i]['run']=(int)$elapsed;
		}
		else{
			$recs[$i]['run']=0;
		}
		
		$actions=[];
		$actions[]='<a href="#" data-func="details_log" title="view log" style="display:inline-block;margin-right:10px;" onclick="return wacss.nav(this);"><span class="icon-info-circled w_blue"></span></a>';
		$icon='';
		if(!isset($header['crontype'])){$header['crontype']='';}
		switch(strtolower($header['crontype'])){
			case 'page':$icon='icon-file-doc';break;
			case 'php command':
			case 'eval':
				$icon="icon-code w_red";
			break;
			case 'url':$icon='icon-globe w_warning';break;
			default:$icon='icon-prompt w_green';break;
		}
		$actions[]='<a href="#" data-func="details_body" title="'.$header['crontype'].': view body" style="display:inline-block;margin-right:10px;" onclick="return wacss.nav(this);"><span class="'.$icon.'"></span></a>';
		$recs[$i]['action']=implode('',$actions);
	}
	return $recs;
}
function cronDetailsLog($id,$field='log'){
	$log=getDBRecord(array(
		'-table'=>'_cron_log',
		'_id'=>$id,
	));
	if(!strlen($log['log'])){return 'no logs';}
	$recs=decodeJson($log['log']);
	$rtn='<div id="cron_details_log">';
	$rtn.= databaseListRecords(array(
		'-list'=>$recs,
		'-listfields'=>'time,elapsed,message',
		'-table_class'=>'wacss_table striped bordered condensed',
		'-table_data-onload'=>"document.querySelector('#cron_details_log').scrollTop=document.querySelector('#cron_details_log').scrollHeight;",
		'-hidesearch'=>1,
		'time_class'=>'align-right w_nowrap',
		'-results_eval'=>'cronDetailsLogExtra',
		'elapsed_options'=>array(
			'class'=>'w_nowrap align-right',
			'displayname'=>'<div class="align-center"><span class="icon-clock"></span></div>',
		),
	));
	$rtn.='</div>';
	return $rtn;
}
function cronDetailsLogExtra($recs){
	$t=$recs[0]['timestamp'];
	foreach($recs as $i=>$rec){
		$elapsed=$rec['timestamp']-$t;
		if($elapsed > 0){
			$recs[$i]['elapsed']=$elapsed;
		}
		else{
			$recs[$i]['elapsed']='';
		}
		$recs[$i]['time']=date('H:i:s',$rec['timestamp']);
	}
	return $recs;
}
function cronIsRunning($rec){
	if($rec['running']==1){return '<span class="icon-spin4 w_spin w_primary"></span>';}
	return '';
}
function cronIsPaused($rec){
	if($rec['paused']==1){return '<span class="icon-spin8 w_danger"></span>';}
	return '';
}
function cronIsActive($rec){
	if($rec['active']==1){return '<span class="icon-mark w_success"></span>';}
	return '';
}
function cronRunNow($rec){
	if($rec['run_now']==1){return '<div class="align-center"><span class="icon-mark w_blue"></span></div>';}
	return <<<ENDOFLINK
<div class="align-center"><a href="#" style="align-self:center;margin-left:10px;" class="w_link" onclick="return cronModal('run',{$rec['_id']},this.title);" title="Run Now"><span class="icon-play w_success"></span></a></div>
ENDOFLINK;
}
function cronList(){
	global $CONFIG;
	if(!isset($CONFIG['paging'])){
		$CONFIG['paging']=20;
	}
	$url=configValue('admin_form_url');
	if(!stringContains($url,'admin.php')){
		$url='/t/1'.$url;
	}
	$opts=array(
		'-table'=>'_cron',
		'-truecount'=>1,
		'-formname'=>'cronlistform',
		'-searchfields'=>'name,groupname,_id,cron_pid,active,paused,running,run_now,stop_now',
		'-searchopers'=>'ct,eq,neq,ca,ea,ib,nb',
		'-listfields'=>'_id,groupname,name,active,cron_pid,paused,running,stop_now,run_now,logcount,last_run,next_run,run_format,frequency_max',
		'-fields'=>'_id,groupname,name,active,cron_pid,paused,running,stop_now,run_now,run_date,unix_timestamp(now())-unix_timestamp(run_date) as last_run,run_format,run_values,frequency,begin_date,end_date,frequency_max',
		'-tableclass'=>'wacss_table striped bordered',
		'-action'=>$url,
		'_menu'=>'cron',
		'-editfields'=>'frequency_max,cron_pid,groupname',
		'-export'=>1,
		'-sorting'=>1,
		'setprocessing'=>0,
		'func'=>'list',
		'-onsubmit'=>"return pagingSubmit(this,'cron_results');",
		'run_date_dateage'=>1,
		'last_run_options'=>array(
			'class'=>'align-right',
			'verbosetime'=>1
		),
		'next_run_options'=>array(
			'class'=>'w_nowrap',
			'displayname'=>'Next Run',
			'title'=>'When this cron is next scheduled to run'
		),
		'cron_pid_options'=>array(
			'class'=>'align-right',
			'displayname'=>'PID',
			'title'=>'Process ID'
		),
		'groupname_displayname'=>'Group',
		'run_format_displayname'=>'Frequency <div class="w_gray w_smaller">(min, hr, mon, day, dayname)</div>',
		'_id_displayname'=>"ID / Action",
		'active_options'=>array(
			'class'=>'w_success align-center ',
			'checkbox'=>1,
			'data-id'=>'%_id_ori%',
			'data-type'=>'checkbox',
			'title'=>'Paused',
			'checkbox_onclick'=>"cronSetFieldValue(this,'active',this.checked)"
		),
		'paused_options'=>array(
			'class'=>'w_orange align-center ',
			'checkbox'=>1,
			'data-id'=>'%_id_ori%',
			'data-type'=>'checkbox',
			'title'=>'Paused',
			'checkbox_onclick'=>"cronSetFieldValue(this,'paused',this.checked)"
		),
		'running_options'=>array(
			'class'=>'align-center',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-spin4 w_spin w_blue'
		),
		'stop_now_options'=>array(
			'class'=>'w_red align-center',
			'checkbox'=>1,
			'data-id'=>'%_id_ori%',
			'title'=>'Attempt to stop cron',
			'checkbox_onclick'=>"cronSetFieldValue(this,'stop_now',this.checked)"
		),
		'run_now_options'=>array(
			'class'=>'w_blue align-center',
			'checkbox'=>1,
			'data-id'=>'%_id_ori%',
			'data-type'=>'checkbox',
			'title'=>'Mark cron to Run Now',
			'checkbox_onclick'=>"cronSetFieldValue(this,'run_now',this.checked)"
		),
		'logcount_options'=>array(
			'class'=>'align-right',
			'displayname'=>'Logs'
		),
		'-results_eval'=>'cronListExtra',
		'-quickfilters'=>array(
			array(
				'name'=>'',
				'icon'=>'icon-file-txt w_orange',
				'title'=>'view Cron Scheduler log',
				'class'=>'btn w_white',
				'onclick'=>"return wacss.ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'tail','name':'cron_scheduler',title:'Cron Scheduler Log'});",
			),
			array(
				'name'=>'',
				'icon'=>'icon-file-txt w_green',
				'title'=>'view Cron Worker log',
				'class'=>'btn w_white',
				'onclick'=>"return wacss.ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'tail','name':'cron_worker',title:'Cron Worker Log'});",
			),
			array(
				'name'=>'',
				'icon'=>'icon-file-txt w_gray',
				'title'=>'view Old Cron log',
				'class'=>'btn w_white',
				'onclick'=>"return wacss.ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'tail','name':'cron',title:'Cron Log'});",
			),
			array(
				'name'=>'',
				'icon'=>'icon-mark w_green',
				'title'=>'active',
				'filter'=>'active eq 1',
				'class'=>'btn w_white'
			),
			array(
				'name'=>'',
				'icon'=>'icon-spin8 w_danger',
				'title'=>'paused',
				'filter'=>'paused eq 1',
				'class'=>'btn w_white'
			),
			array(
				'name'=>'',
				'icon'=>'icon-spin4 w_primary',
				'title'=>'running',
				'filter'=>'running eq 1',
				'class'=>'btn w_white'
			),			
			array(
				'name'=>'',
				'icon'=>'icon-plus w_green',
				'title'=>'add new',
				'onclick'=>"return cronModal('add',0,'Add New Cron');",
				'class'=>'btn w_white'
			),
			array(
				'name'=>'',
				'icon'=>'icon-refresh w_gray',
				'title'=>'refresh',
				'onclick'=>"return pagingSubmit(document.cronlistform,'cron_results');",
				'class'=>'btn w_white'
			)
		)
	);
	//predata
	$recs=getDBRecords("select count(*) cnt, groupname from _cron where groupname is not null group by groupname order by groupname");
	$opts['-predata']='<div class="w_gray w_smaller w_right">Server Time: '.date('Y-m-d H:i:s').'</div>';
	if(isset($recs[0])){
		$opts['-predata'].=renderView('groupnames',$recs,'recs');
	}
	return databaseListRecords($opts);
}
/**
* @describe returns when a cron is next scheduled to run, mirroring the schedule rules in php/cron.php
* @param $rec array cron record (active,paused,run_date,run_format,run_values,frequency,begin_date,end_date)
* @return string verbose time until the next run (e.g. '32 min', '12 hours'; exact date in the title tooltip), or a short status ('paused','inactive','-') when there is no upcoming run
*/
function cronNextRun($rec){
	if(isset($rec['active']) && (int)$rec['active']!=1){return '<span class="w_gray">inactive</span>';}
	if(isset($rec['paused']) && (int)$rec['paused']==1){return '<span class="w_gray">paused</span>';}
	$now=time();
	$last=(isset($rec['run_date']) && strlen($rec['run_date']))?strtotime($rec['run_date']):0;
	//cron.php never runs a cron twice within a minute
	$earliest=max($now,$last+60);
	if(isset($rec['begin_date']) && strlen($rec['begin_date'])){
		$earliest=max($earliest,strtotime(date('Y-m-d',strtotime($rec['begin_date']))));
	}
	$next=0;
	$format=isset($rec['run_format'])?trim($rec['run_format']):'';
	if(strlen($format) && strpos($format,'{')!==false){
		$json=json_decode($format,true);
		if(is_array($json)){
			foreach(array('month','day','hour','minute') as $k){
				if(!isset($json[$k][0])){return '-';}
			}
			$start=strtotime(date('Y-m-d H:i:00',$earliest));
			if($start < $earliest){$start+=60;}
			$hours=$json['hour'][0]==-1?range(0,23):array_map('intval',$json['hour']);
			$mins=$json['minute'][0]==-1?range(0,59):array_map('intval',$json['minute']);
			sort($hours);sort($mins);
			for($d=0;$d<=732 && !$next;$d++){
				$day=strtotime(date('Y-m-d',$start).' +'.$d.' day');
				if($json['month'][0]!=-1 && !in_array((int)date('n',$day),$json['month'])){continue;}
				if($json['day'][0]!=-1 && !in_array((int)date('j',$day),$json['day'])){continue;}
				if(isset($json['dayname'][0]) && $json['dayname'][0]!=-1 && !in_array((int)date('N',$day)-1,$json['dayname'])){continue;}
				foreach($hours as $h){
					foreach($mins as $m){
						$ts=strtotime(date('Y-m-d',$day).sprintf(' %02d:%02d:00',$h,$m));
						if($ts >= $start){$next=$ts;break 2;}
					}
				}
			}
		}
	}
	elseif(isset($rec['frequency']) && (int)$rec['frequency'] > 0){
		$next=max($earliest,$last+((int)$rec['frequency']*60)+1);
	}
	elseif(strlen($format) && isset($rec['run_values']) && strlen($rec['run_values'])){
		$values=preg_split('/\,/',$rec['run_values']);
		$start=strtotime(date('Y-m-d H:i:00',$earliest));
		if($start < $earliest){$start+=60;}
		//step minute by minute (bounded to one week) until date(run_format) matches one of the values
		for($ts=$start;$ts <= $start+604800;$ts+=60){
			if(in_array(date($format,$ts),$values)){$next=$ts;break;}
		}
	}
	if(!$next){return '-';}
	if(isset($rec['end_date']) && strlen($rec['end_date']) && date('Y-m-d',$next) > date('Y-m-d',strtotime($rec['end_date']))){return '-';}
	$secs=max(0,$next-$now);
	if($secs < 60){$verbose='< 1 min';}
	elseif($secs < 3600){$verbose=round($secs/60).' min';}
	elseif($secs < 86400){$h=round($secs/3600);$verbose=$h.($h==1?' hour':' hours');}
	elseif($secs < 2629743){$d=round($secs/86400);$verbose=$d.($d==1?' day':' days');}
	else{$mo=round($secs/2629743);$verbose=$mo.($mo==1?' month':' months');}
	$title=date('m/d/Y h:i a',$next);
	if(isset($rec['running']) && (int)$rec['running']==1){
		return '<span class="w_gray" title="'.$title.' (currently running)">'.$verbose.'</span>';
	}
	return '<span title="'.$title.'">'.$verbose.'</span>';
}
function cronListExtra($recs){
	$ids=array();
	$cron_ids=array();
	foreach($recs as $i=>$rec){
		if($rec['run_as'] > 0 && !in_array($rec['run_as'],$ids)){
			$ids[]=$rec['run_as'];
		}
		$cron_ids[]=$rec['_id'];
	}
	$logcounts=getDBRecords(array(
		'-query'=>"select count(*) cnt, cron_id from _cron_log group by cron_id",
		'-index'=>'cron_id'
	));
	$umap=array();
	if(count($ids)){
		$idstr=implode(',',$ids);
		$umap=getDBRecords(array(
			'-table'=>'_users',
			'-where'=>"_id in ({$idstr})",
			'-fields'=>'_id,username',
			'-index'=>'_id'
		));
	}
	$url=configValue('admin_form_url');
	if(!stringContains($url,'admin.php')){
		$url='/t/1'.$url;
	}
	//echo printValue($recs);exit;
	foreach($recs as $i=>$rec){
		$id=$recs[$i]['_id_ori']=$recs[$i]['_id'];
		$pid=$recs[$i]['cron_pid_ori']=(int)$recs[$i]['cron_pid'];
		$name=$rec['name'];
		$recs[$i]['next_run']=cronNextRun($rec);
		//if cron_pid and not running something is wrong.
		if($pid != 0 && (int)$rec['running']==0){
			$recs[$i]['cron_pid']='<span class="w_danger">'.$rec['cron_pid'].'</span>';
		}
		//pid lookup
		if($pid != 0){
			$recs[$i]['cron_pid']='<a id="process_'.$pid.'" style="margin-left:10px;align-self:center;margin-right:10px;display:inline" href="#" class="w_right w_link" onclick="return wacss.ajaxGet(\'/php/admin.php\',\'centerpop\',{_menu:\'cron\',func:\'pid\',id:this.dataset.cron_pid,title:this.title,setprocessing:\'cron_processing\'});" data-cron_pid="'.$rec['cron_pid'].'" title="check process">'.$recs[$i]['cron_pid'].'</a>';
		}
		else{$recs[$i]['cron_pid']='';}
		//logcount
		if(isset($logcounts[$id]['cnt'])){
			$recs[$i]['logcount']=$logcounts[$id]['cnt'];
		}
		else{
			$recs[$i]['logcount']=0;
		}
	
		//check for valid run_format
		if(!strlen($rec['run_format'])){
			$recs[$i]['run_format']='<span class="w_danger">MISSING</span>';
		}
		elseif(!stringBeginsWith($rec['run_format'],'{')){
			$recs[$i]['run_format']='<span title="Invalid JSON" class="w_danger">'.$rec['run_format'].'</span>';
		}
		elseif(!is_object(json_decode($rec['run_format']))){
			$recs[$i]['run_format']='<span title="Invalid JSON" class="w_danger">'.$rec['run_format'].'</span>';
		} 
		else{
			$freq=json_decode($rec['run_format'],true);
			$vals=array($freq['minute'][0],$freq['hour'][0],$freq['month'][0],$freq['day'][0],$freq['dayname'][0]);
			$recs[$i]['run_format']=implode(', ',$vals);
		}
		$recs[$i]['groupname']='<span class="w_pointer" onclick="checkAllElements(\'data-groupname\',\''.$rec['groupname'].'\',true);">'.$rec['groupname'].'</span>';
		$recs[$i]['_id']=<<<ENDOFID
		<div style="display:flex;justify-content:space-between;align-items:center;">
			<span style="align-self:center;">{$id}</span>
			<input type="checkbox" data-type="checkbox" class="w_gray align-center"  style="margin-left:10px;margin-right:0px;align-self:center;" data-groupname="{$rec['groupname']}" name="cronid[]" value="{$id}" />
			<a style="margin-left:10px;align-self:center;" href="#" class="w_right w_link w_block" onclick="return cronModal('edit','{$id}',this.title);" title="Edit Cron"><span class="icon-edit"></span></a>

			<a style="margin-left:10px;align-self:center;" href="#" class="w_right w_link w_block" onclick="return wacss.ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'filter','name':'cron_scheduler',filter:'cron_id:{$id}',title:'Scheduler Log Entries'});" title="View Scheduler Log Entries"><span class="icon-file-txt w_orange"></span></a>

			<a style="margin-left:10px;align-self:center;" href="#" class="w_right w_link w_block" onclick="return wacss.ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'filter','name':'cron_worker',filter:'cron_id:{$id}',title:'Worker Log Entries'});" title="View Worker Log Entries"><span class="icon-file-txt w_green"></span></a>

			<a style="margin-left:10px;align-self:center;" href="#" onclick="return cronModal('details','{$id}',this.title);" class="w_bigger" title="Cron Details - {$name}"><span class="icon-info-circled  w_gray"></span></a>
		</div>
ENDOFID;
	}
	return $recs;
}
?>