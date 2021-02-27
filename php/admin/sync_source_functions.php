<?php
loadExtras('translate');
function sync_sourceSetActive($key){
	global $setactive;
	if(isset($setactive) && strlen($setactive)){return '';}
	$setactive=$key;
	return ' active';
}
function sync_sourceAddEdit($id=0){
	$opts=array(
		'-table'=>'sync_source',
		'-fields'=>getView('addedit_fields'),
		'-style_all'=>'width:100%',
		'-action'=>'/php/admin.php',
		'-onsubmit'=>"return ajaxSubmitForm(this,'sync_source_content');",
		'_menu'=>'sync_source',
		'func'=>'list',
		'table_name_options'=>array(
			'inputtype'=>'select',
			'tvals'=>"select distinct(tablename) from _fielddata where fieldname='name' and tablename != '_models'",
			'onchange'=>"sync_sourceFunc(this);",
			'data-func'=>'redraw_table_id',
			'data-div'=>'table_id'
		)
	);
	if($id > 0){
		$opts['_id']=$id;
		$opts['-custombutton']='<button type="button" onclick="sync_sourceAddEdit(0);" class="button btn btn-danger"><span class="icon-block"></span> Cancel Edit</button>';
		$opts['-hide']='clone,reset';
	}
	$rtn=addEditDBForm($opts);
	if($id==0 && isset($_REQUEST['table_name'])){
		$rtn.=buildOnLoad("ajaxGet('/php/admin.php','table_id',{setprocessing:0,'_menu':'sync_source',func:'redraw_table_id',value:'{$_REQUEST['table_name']}'});");
	}
	return $rtn;
}
function sync_sourceList(){
	$opts=array(
		'-table'=>'sync_source',
		'-action'=>'/php/admin.php',
		'_menu'=>'sync_source',
		'-tableclass'=>'table striped bordered narrow sticky',
		'-tableheight'=>'70vh',
		'-listfields'=>'action,table_name,table_id,source_domain,source_id,last_sync',
		'action_class'=>'align-right',
		'-results_eval'=>'sync_sourceListExtra',
		'-navonly'=>1
	);
	return databaseListRecords($opts);
}
function sync_sourceListExtra($recs){
	foreach($recs as $i=>$rec){
		$recs[$i]['action']='<button type="button" onclick="sync_sourceCheck('.$rec['_id'].');" class="button btn btn-warning"><span class="icon-sync"></span> Check</button>';
		$recs[$i]['action'].='<button type="button" onclick="sync_sourceAddEdit('.$rec['_id'].');" class="button btn"><span class="icon-edit"></span> Edit</button>';
	}
	return $recs;
}
function sync_sourcePost($url,$postopts=array()){
	global $USER;
	global $DEBUG;
	//echo $plain.$_SESSION['sync_target_url'].printValue($postopts);exit;
	$post=postURL($url,$postopts);
	$DEBUG[]=$postopts;
	$DEBUG[]=$post['body'];
	
	//echo printValue($postopts).$post['body'];exit;
	if(isset($post['error'])){
		$DEBUG[]='ERROR_1<hr />';
		return array('error'=>$post['error'],'opts'=>$postopts);
	}
	elseif(!strlen($post['body'])){
		$DEBUG[]='ERROR_2<hr />';
		return array('error'=>printValue($post));
	}
	else{
		//remove debug errors if they exist
		$post['body']=preg_replace('/\<div(.+?)\<\/div\>/is','',$post['body']);
		$post['body']=preg_replace('/\<img(.+?)\>/is','',$post['body']);
		$body=base64_decode(trim($post['body']));
		$json=json_decode($body,true);
		$DEBUG[]=$json;
		$DEBUG[]='<hr />';
		//echo $_SESSION['sync_target_url'].printValue($postopts).printValue($json);exit;
		if(!is_array($json)){
			$json=json_decode(trim($post['body']),true);
			if(!is_array($json)){
				return array('error'=>"Failed to decode response.<br />".$post['body']);
			}
		}
		return $json;
	}
	$DEBUG[]='ERROR_3<hr />';
	return array('error'=>json_encode($post));
}
?>
