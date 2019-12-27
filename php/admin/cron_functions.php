<?php
function cronAddEdit($id=0){
	$opts=array(
		'-table'=>'_cron',
		'-action'=>configValue('admin_form_url'),
		'run_cmd_displayname'=>'Run Cmd: (command, page name, or url)',
		'_menu'=>'cron',
		'func'=>'list',
		'-onsubmit'=>"return ajaxSubmitForm(this,'cron_results');",
		'-style_all'=>'width:100%',
		'-class_all'=>'browser-default',
		'run_length_readonly'=>1,
		'last_run_readonly'=>1,
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
	return addEditDBForm($opts);
}
function cronDetails($id){
	/*
		last_run
	*/
	$cron=getDBRecordById('_cron',$id);
	$cron['logs']=getDBRecords(array(
		'-table'=>'_cronlog',
		'cron_id'=>$id,
		'-order'=>'_id desc',
		'-limit'=>100,
		'-fields'=>'_id,run_date,run_length'
	));
	return $cron;
}
function cronList(){
	$url=configValue('admin_form_url');
	if(!stringContains($url,'admin.php')){
		$url='/t/1'.$url;
	}
	$opts=array(
		'-table'=>'_cron',
		'-fields'=>'_id,groupname,name,active,paused,running,run_date,run_length,run_cmd,records_to_keep',
		'-listfields'=>'_id,groupname,name,active,paused,running,last_run,run_length,run_cmd,records_to_keep',
		'-tableclass'=>'table striped bordered',
		'-action'=>$url,
		'_menu'=>'cron',
		'func'=>'list',
		'-onsubmit'=>"return ajaxSubmitForm(this,'cron_results');",
		'run_date_dateage'=>1,
		'frequency_class'=>'align-right',
		'run_length_class'=>'align-right',
		'run_length_verbosetime'=>1,
		'groupname_displayname'=>'Group',
		'name_class'=>'w_nowrap w_link',
		'active_class'=>'align-center',
		'paused_class'=>'align-center',
		'running_class'=>'align-center',
		'records_to_keep_class'=>'align-right',
		'last_run_class'=>'align-right',
		'-results_eval'=>'cronListExtra'
	);
	return databaseListRecords($opts);
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
function cronListExtra($recs){
	$ids=array();
	foreach($recs as $i=>$rec){
		if($rec['run_as'] > 0 && !in_array($rec['run_as'],$ids)){
			$ids[]=$rec['run_as'];
		}
	}
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
	foreach($recs as $i=>$rec){
		$id=$recs[$i]['_id'];
		$recs[$i]['active']=cronIsActive($rec);
		$recs[$i]['paused']=cronIsPaused($rec);
		$recs[$i]['running']=cronIsRunning($rec);
		$recs[$i]['groupname']='<span class="w_pointer" onclick="checkAllElements(\'data-groupname\',\''.$rec['groupname'].'\',true);">'.$rec['groupname'].'</span>';
		$recs[$i]['last_run']=verboseTime(time()-strtotime($rec['run_date']),0,1).' ago';
		$recs[$i]['_id']='<input type="checkbox" data-groupname="'.$rec['groupname'].'" name="cronid[]" value="'.$id.'" /> '.$id;
		$recs[$i]['_id'].='<a href="#" class="w_right w_link w_block" onclick="return ajaxGet(\''.$url.'\',\'modal\',{_menu:\'cron\',func:\'edit\',id:'.$id.',title:this.title});" title="Edit Cron"><span class="icon-edit"></span></a>';
		$name=$rec['name'];
		$recs[$i]['name']='<a href="#" onclick="return ajaxGet(\''.$url.'\',\'modal\',{_menu:\'cron\',func:\'details\',id:'.$id.',title:this.title});" title="Cron Details - '.$name.'">'.$name.'</a>';
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
	return true;
}
?>