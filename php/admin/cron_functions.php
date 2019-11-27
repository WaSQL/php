<?php
function cronAddEdit($id=0){
	$opts=array(
		'-table'=>'_cron',
		'-action'=>'/php/admin.php',
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
	$opts=array(
		'-table'=>'_cron',
		'-fields'=>'_id,name,active,running,frequency,run_date,run_length,run_cmd,run_as',
		'-tableclass'=>'table striped bordered',
		'-action'=>'/php/admin.php',
		'_menu'=>'cron',
		'func'=>'list',
		'-onsubmit'=>"return ajaxSubmitForm(this,'cron_results');",
		'run_date_dateage'=>1,
		'frequency_class'=>'align-right',
		'run_length_class'=>'align-right',
		'run_length_verbosetime'=>1,
		'active_checkmark'=>1,
		'running_checkmark'=>1,
		'active_class'=>'align-center',
		'running_class'=>'align-center',
		'-results_eval'=>'cronListExtra'
	);
	return databaseListRecords($opts);
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
	foreach($recs as $i=>$rec){
		$id=$recs[$i]['_id'];
		$recs[$i]['_id'].='<a href="#" class="w_right w_link w_block" onclick="return ajaxGet(\'/php/admin.php\',\'modal\',{_menu:\'cron\',func:\'edit\',id:'.$id.',title:this.title});" title="Edit Cron"><span class="icon-edit"></span></a>';
		$name=$rec['name'];
		$recs[$i]['name']='<a href="#" class="w_right w_link w_block" style="margin-right:10px;" onclick="return ajaxGet(\'/php/admin.php\',\'modal\',{_menu:\'cron\',func:\'details\',id:'.$id.',title:this.title});" title="Cron Details - '.$name.'"><span class="icon-chart-bar"></span></a>';
		$recs[$i]['name'].='<a href="#" onclick="return ajaxGet(\'/php/admin.php\',\'modal\',{_menu:\'cron\',func:\'details\',id:'.$id.',title:this.title});" title="Cron Details - '.$name.'">'.$name.'</a>';
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
?>