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
		'active_checkmark'=>1,
		'active_class'=>'align-center',
		'running_class'=>'align-center',
		'-results_eval'=>'cronListExtra'
	);
	return databaseListRecords($opts);
}
function cronListExtra($recs){
	foreach($recs as $i=>$rec){
		$id=$recs[$i]['_id'];
		$recs[$i]['_id'].='<a href="#" class="w_right" onclick="return ajaxGet(\'/php/admin.php\',\'modal\',{_menu:\'cron\',func:\'edit\',id:'.$id.',title:this.title});" title="Edit Cron"><span class="icon-edit w_gray"></span></a>';
		$name=$rec['name'];
		$recs[$i]['name']='<a href="#" onclick="return ajaxGet(\'/php/admin.php\',\'modal\',{_menu:\'cron\',func:\'edit\',id:'.$id.',title:this.title});" title="Edit Cron">'.$name.'</a>';
	}
	return $recs;
}
?>