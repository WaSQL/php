<?php
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
		'-limit'=>100,
		'-fields'=>'_id,run_date,run_length,run_error'
	));
	foreach($cron['logs'] as $i=>$log){
		$cron['logs'][$i]['cron_id']=$id;
		if(strlen($log['run_error'])){
			$cron['logs'][$i]['color']='#d9534f';
		}
		else{
			$cron['logs'][$i]['color']='#5cb85c';
		}
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
	if($rec['paused']==1){return '<span class="icon-spin8 w_danger" style="border-radius:15px;background:#fefcab;"></span>';}
	return '';
}
function cronIsActive($rec){
	if($rec['active']==1){return '<span class="icon-mark w_success"></span>';}
	return '';
}
function cronList(){
	$url=configValue('admin_form_url');
	if(!stringContains($url,'admin.php')){
		$url='/t/1'.$url;
	}
	$opts=array(
		'-table'=>'_cron',
		'-fields'=>'_id,groupname,name,active,paused,running,run_date,run_length,run_cmd,run_memory,records_to_keep,run_error',
		'-searchfields'=>'_id,groupname,name,active,paused,running,run_memory,records_to_keep',
		'-listfields'=>'_id,groupname,name,err,active,paused,running,last_run,run_length,run_cmd,run_memory,logs,records_to_keep',
		'-tableclass'=>'table striped bordered',
		'-action'=>$url,
		'_menu'=>'cron',
		'-export'=>1,
		'-sorting'=>1,
		'setprocessing'=>0,
		'func'=>'list',
		'-onsubmit'=>"return pagingSubmit(this,'cron_results');",
		'run_date_dateage'=>1,
		'frequency_class'=>'align-right',
		'run_length_class'=>'align-right',
		'run_length_verbosetime'=>1,
		'groupname_displayname'=>'Group',
		'run_memory_displayname'=>'Memory',
		'records_to_keep_displayname'=>'Logs Max',
		'err_displayname'=>'',
		'name_class'=>'w_nowrap w_link',
		'active_class'=>'align-center',
		'paused_class'=>'align-center',
		'running_class'=>'align-center',
		'records_to_keep_class'=>'align-right',
		'last_run_class'=>'align-right',
		'logs_class'=>'align-right',
		'-results_eval'=>'cronListExtra'
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
	foreach($recs as $i=>$rec){
		$id=$recs[$i]['_id'];
		$recs[$i]['active']=cronIsActive($rec);
		$recs[$i]['paused']=cronIsPaused($rec);
		$recs[$i]['running']=cronIsRunning($rec);
		if(isset($logcounts[$id])){
			$recs[$i]['logs']=$logcounts[$id]['cnt'];
		}
		else{
			$recs[$i]['logs']='';
		}
		if(isNum($rec['run_memory']) && $rec['run_memory'] > 0){
			$recs[$i]['run_memory']=verboseSize($rec['run_memory']);
		}
		$recs[$i]['groupname']='<span class="w_pointer" onclick="checkAllElements(\'data-groupname\',\''.$rec['groupname'].'\',true);">'.$rec['groupname'].'</span>';
		if(strlen($rec['run_date'])){
			$recs[$i]['last_run']=verboseTime(time()-strtotime($rec['run_date']),0,1).' ago';
		}
		else{
			$recs[$i]['last_run']='';
			$recs[$i]['run_length']='';
		}
		if(strlen($rec['run_error'])){
			$recs[$i]['err']='<div class="align-middle" style="margin-top:2px;background-color:#d9534f;height:12px;width:12px;border-radius:6px;"></div>';
		}
		elseif(strlen($recs[$i]['last_run'])){
			$recs[$i]['err']='<div class="align-middle" style="margin-top:2px;background-color:#5cb85c;height:12px;width:12px;border-radius:6px;"></div>';
		}
		else{
			$recs[$i]['err']='';
		}
		if(strlen($rec['run_cmd']) > 50){
			$truncated=substr($rec['run_cmd'],0,50).'...';
			$recs[$i]['run_cmd']='<span title="'.$rec['run_cmd'].'">'.$truncated.'</span>';
		}
		$recs[$i]['_id']='<input type="checkbox" data-groupname="'.$rec['groupname'].'" name="cronid[]" value="'.$id.'" /> '.$id;
		$recs[$i]['_id'].='<a href="#" class="w_right w_link w_block" onclick="return cronModal(\'edit\',\''.$id.'\',this.title);" title="Edit Cron"><span class="icon-edit"></span></a>';
		$name=$rec['name'];
		$recs[$i]['name']='<a href="#" onclick="return cronModal(\'details\',\''.$id.'\',this.title);" title="Cron Details - '.$name.'">'.$name.'</a>';
		//run_as
		if(isset($rec['run_as'])){
			if((integer)$rec['run_as'] > 0){
				$recs[$i]['run_as']=$umap[$rec['run_as']]['username'];
			}
			else{
				$recs[$i]['run_as']='';
			}
		}
		
	}
	return $recs;
}
function cronCheckSchema(){
	$cronfields=getDBFieldInfo('_cron');
	//add paused and groupname fields?
	if(!isset($cronfields['paused'])){
		//paused
		$query="ALTER TABLE _cron ADD paused ".databaseDataType('integer(1)')." NOT NULL Default 0;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> '_cron',
			'fieldname'		=> 'paused',
			'inputtype'		=> 'checkbox',
			'synchronize'	=> 0,
			'tvals'			=> '1',
			'editlist'		=> 1,
			'required'		=> 0
		));
		$ok=addDBIndex(array('-table'=>'_cron','-fields'=>"paused"));
	}
	if(!isset($cronfields['groupname'])){
		//groupname
		$query="ALTER TABLE _cron ADD groupname ".databaseDataType('varchar(150)')." NULL;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'groupname',
			'inputtype'		=> 'text',
			'width'			=> 150,
			'required'		=> 0
		));
		$ok=addDBIndex(array('-table'=>'_cron','-fields'=>"groupname"));
	}
	if(!isset($cronfields['records_to_keep'])){
		//records_to_keep
		$query="ALTER TABLE _cron ADD records_to_keep ".databaseDataType('integer')." NOT NULL Default 1000;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'records_to_keep',
			'inputtype'		=> 'text',
			'width'			=> 100,
			'mask'			=> 'integer',
			'required'		=> 1
		));
	}
	//records_to_keep
	if(!isset($cronfields['run_error'])){
		$query="ALTER TABLE _cron ADD run_error ".databaseDataType('varchar(2000)')." NULL;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'run_error',
			'inputtype'		=> 'textarea',
			'width'			=> 600
		));
	}
	//run_memory
	if(!isset($cronfields['run_memory'])){
		$query="ALTER TABLE _cron ADD run_memory ".databaseDataType('integer')." NULL";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cron',
			'fieldname'		=> 'run_memory',
			'inputtype'		=> 'text',
			'width'			=> 100,
			'mask'			=> 'integer',
			'required'		=> 1
		));
	}
	//check _cronlog table
	$cronfields=getDBFieldInfo('_cronlog');
	if(!isset($cronfields['run_error'])){
		$query="ALTER TABLE _cronlog ADD run_error ".databaseDataType('varchar(2000)')." NULL;";
		$ok=executeSQL($query);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_cronlog',
			'fieldname'		=> 'run_error',
			'inputtype'		=> 'textarea',
			'width'			=> 600
		));
	}
	if(!isset($cronlogfields['run_memory'])){
		//$query="ALTER TABLE _cronlog ADD run_memory ".databaseDataType('integer')." NULL";
		//$ok=executeSQL($query);
	}
	return true;
}
?>