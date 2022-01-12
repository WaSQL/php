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
function cronDetails($id){
	/*
		last_run
	*/
	global $CONFIG;
	$cron=getDBRecordById('_cron',$id);
	$cron['logs']=getDBRecords(array(
		'-table'=>'_cronlog',
		'cron_id'=>$id,
		'-order'=>'_id desc',
		'-limit'=>100
	));
	//echo $id.printValue($cron['logs']);exit;
	foreach($cron['logs'] as $i=>$log){
		$cron['logs'][$i]['cron_id']=$id;
	}
	$path=getWaSQLPath('php/temp');
	$commonCronLogFile="{$path}/{$CONFIG['name']}_cronlog_{$id}.txt";
	if(file_exists($commonCronLogFile)){
		$t=time()-filectime($commonCronLogFile);
		$run_length=verboseTime($t);
		$bottom="{$id},0,'{$run_length}'";
		$rec=array(
			'_id'=>0,
			'cron_id'=>$id,
			'run_date'=>'Running',
			'run_length'=>$t,
			'color'=>'#0086ff',
			'bottom'=>$bottom
		);
		array_unshift($cron['logs'],$rec);
		$cron['run_result']=getFileContents($commonCronLogFile);
		$cron['bottom']=$bottom;
	}
	return $cron;
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
function cronList(){
	global $CONFIG;
	$url=configValue('admin_form_url');
	if(!stringContains($url,'admin.php')){
		$url='/t/1'.$url;
	}
	$opts=array(
		'-table'=>'_cron',
		'-formname'=>'cronlistform',
		'-searchfields'=>'_id,groupname,name,active,paused,running,run_now,stop_now',
		'-listfields'=>'_id,groupname,name,active,cron_pid,paused,running,stop_now,run_now,last_run,run_length,run_format,frequency_max',
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
		'run_length_verbosetime'=>1,
		'last_run_options'=>array(
			'class'=>'align-right',
			'verbosetime'=>1
		),
		'cron_pid_options'=>array(
			'class'=>'align-right'
		),
		'groupname_displayname'=>'Group',
		'run_format_displayname'=>'Frequency',
		'name_class'=>'w_nowrap w_link',
		'active_options'=>array(
			'class'=>'align-center',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-mark w_success'
		),
		'active_options'=>array(
			'class'=>'w_green align-center ',
			'checkbox'=>1,
			'data-id'=>'%_id%',
			'data-type'=>'checkbox',
			'title'=>'Paused',
			'checkbox_onclick'=>"cronSetFieldValue(this.dataset.id,'active',this.checked)"
		),
		'paused_options'=>array(
			'class'=>'w_orange align-center ',
			'checkbox'=>1,
			'data-id'=>'%_id%',
			'data-type'=>'checkbox',
			'title'=>'Paused',
			'checkbox_onclick'=>"cronSetFieldValue(this.dataset.id,'paused',this.checked)"
		),
		'running_options'=>array(
			'class'=>'align-center',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-spin4 w_spin w_blue'
		),
		'stop_now_options'=>array(
			'class'=>'w_red align-center',
			'checkbox'=>1,
			'data-id'=>'%_id%',
			'title'=>'Attempt to stop cron',
			'checkbox_onclick'=>"cronSetFieldValue(this.dataset.id,'stop_now',this.checked)"
		),
		'run_now_options'=>array(
			'class'=>'w_blue align-center',
			'checkbox'=>1,
			'data-id'=>'%_id%',
			'data-type'=>'checkbox',
			'title'=>'Mark cron to Run Now',
			'checkbox_onclick'=>"cronSetFieldValue(this.dataset.id,'run_now',this.checked)"
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
	if(isset($recs[0])){
		$opts['-predata']=renderView('groupnames',$recs,'recs');
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
		'-query'=>"select count(*) cnt, cron_id from _cronlog group by cron_id",
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
		$recs[$i]['groupname']='<span class="w_pointer" onclick="checkAllElements(\'data-groupname\',\''.$rec['groupname'].'\',true);">'.$rec['groupname'].'</span>';
		$recs[$i]['_id']=<<<ENDOFID
		<div style="display:flex;justify-content:space-between;align-items:center;">
			<input type="checkbox" data-type="checkbox" class="w_gray align-center"  style="align-self:center;" data-groupname="{$rec['groupname']}" name="cronid[]" value="{$id}" />
			<span style="margin-left:5px;align-self:center;">{$id}</span>
			<a style="margin-left:10px;align-self:center;" href="#" class="w_right w_link w_block" onclick="return cronModal('edit','{$id}',this.title);" title="Edit Cron"><span class="icon-edit"></span></a>
			<a style="margin-left:10px;align-self:center;" href="#" class="w_right w_link w_block" onclick="return ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'filter','name':'cron_scheduler',filter:'cron_id:{$id}',title:'Scheduler Log Entries'});" title="View Scheduler Log Entries"><span class="icon-file-txt w_orange"></span></a>
			<a style="margin-left:10px;align-self:center;" href="#" class="w_right w_link w_block" onclick="return ajaxGet('/php/admin.php','modal',{setprocessing:0,_menu:'logs',func:'filter','name':'cron_worker',filter:'cron_id:{$id}',title:'Worker Log Entries'});" title="View Worker Log Entries"><span class="icon-file-txt w_green"></span></a>
		</div>
ENDOFID;
		$name=$rec['name'];
		$recs[$i]['name']='<a href="#" onclick="return cronModal(\'details\',\''.$id.'\',this.title);" class="w_bigger" title="Cron Details - '.$name.'"><span class="icon-info-circled '.configValue('admin_color').'"></span> '.$name.'</a>';
	}
	return $recs;
}
?>