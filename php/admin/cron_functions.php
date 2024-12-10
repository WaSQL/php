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
		'-onsubmit'=>"return ajaxSubmitForm(this,'cron_results');",
		'-style_all'=>'width:100%',
		'-class_all'=>'browser-default',
		'run_length_readonly'=>1,
		'run_result_readonly'=>1,
		'run_result_height'=>120,
		'run_format_inputtype'=>'frequency',
		'run_format_style'=>'width:100%;height:32px;overflow:hidden;',
		'frequency_onchange'=>"return formSetFrequency('addedit_run_format',parseInt(this.value));"
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
		'-tableclass'=>'table striped bordered condensed',
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
			$recs[$i]['run']=(integer)$elapsed;
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

	return databaseListRecords(array(
		'-list'=>$recs,
		'-listfields'=>'time,elapsed,message',
		'-table_class'=>'table striped bordered condensed sticky',
		'-table_data-onload'=>"document.querySelector('#cron_details_content').scrollTop=document.querySelector('#cron_details_content').scrollHeight;",
		'-hidesearch'=>1,
		'time_class'=>'align-right w_nowrap',
		'-results_eval'=>'cronDetailsLogExtra',
		'elapsed_options'=>array(
			'class'=>'w_nowrap align-right',
			'displayname'=>'<div class="align-center"><span class="icon-clock"></span></div>'
		),
	));
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
		'-listfields'=>'_id,groupname,name,active,cron_pid,paused,running,stop_now,run_now,logcount,last_run,run_length,run_memory,run_format,frequency_max',
		'-fields'=>'_id,groupname,name,active,cron_pid,paused,running,stop_now,run_now,run_date,unix_timestamp(now())-unix_timestamp(run_date) as last_run,run_length,run_format,frequency_max',
		'-tableclass'=>'table striped bordered',
		'-action'=>$url,
		'_menu'=>'cron',
		'-editfields'=>'frequency_max,cron_pid,groupname',
		'-export'=>1,
		'-sorting'=>1,
		'setprocessing'=>0,
		'func'=>'list',
		'-onsubmit'=>"return pagingSubmit(this,'cron_results');",
		'run_date_dateage'=>1,
		'run_length_class'=>'align-right',
		'run_length_options'=>array(
			'verbosetime'=>1,
			'displayname'=>'Runtime'
		),
		'last_run_options'=>array(
			'class'=>'align-right',
			'verbosetime'=>1
		),
		'run_memory_options'=>array(
			'displayname'=>'Mem',
			'title'=>'Memory Used',
			'class'=>'w_nowrap align-right',
			'eval'=>"return verboseSize('%run_memory%');"
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
				'onclick'=>"return ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'tail','name':'cron_scheduler',title:'Cron Scheduler Log'});",
			),
			array(
				'name'=>'',
				'icon'=>'icon-file-txt w_green',
				'title'=>'view Cron Worker log',
				'class'=>'btn w_white',
				'onclick'=>"return ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'tail','name':'cron_worker',title:'Cron Worker Log'});",
			),
			array(
				'name'=>'',
				'icon'=>'icon-file-txt w_gray',
				'title'=>'view Old Cron log',
				'class'=>'btn w_white',
				'onclick'=>"return ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'tail','name':'cron',title:'Cron Log'});",
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
		$pid=$recs[$i]['cron_pid_ori']=(integer)$recs[$i]['cron_pid'];
		$name=$rec['name'];
		//if cron_pid and not running something is wrong.
		if($pid != 0 && (integer)$rec['running']==0){
			$recs[$i]['cron_pid']='<span class="w_danger">'.$rec['cron_pid'].'</span>';
		}
		//pid lookup
		if($pid != 0){
			$recs[$i]['cron_pid']='<a id="process_'.$pid.'" style="margin-left:10px;align-self:center;margin-right:10px;display:inline" href="#" class="w_right w_link" onclick="return ajaxGet(\'/php/admin.php\',\'centerpop\',{_menu:\'cron\',func:\'pid\',id:this.dataset.cron_pid,title:this.title,setprocessing:\'cron_processing\'});" data-cron_pid="'.$rec['cron_pid'].'" title="check process">'.$recs[$i]['cron_pid'].'</a>';
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

			<a style="margin-left:10px;align-self:center;" href="#" class="w_right w_link w_block" onclick="return ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'filter','name':'cron_scheduler',filter:'cron_id:{$id}',title:'Scheduler Log Entries'});" title="View Scheduler Log Entries"><span class="icon-file-txt w_orange"></span></a>

			<a style="margin-left:10px;align-self:center;" href="#" class="w_right w_link w_block" onclick="return ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'filter','name':'cron_worker',filter:'cron_id:{$id}',title:'Worker Log Entries'});" title="View Worker Log Entries"><span class="icon-file-txt w_green"></span></a>

			<a style="margin-left:10px;align-self:center;" href="#" onclick="return cronModal('details','{$id}',this.title);" class="w_bigger" title="Cron Details - {$name}"><span class="icon-info-circled  w_gray"></span></a>
		</div>
ENDOFID;
	}
	return $recs;
}
?>