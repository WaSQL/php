<?php
function translateListRecords($locale){
	global $PAGE;
	return databaseListRecords(array(
		'-table'=>'_translations',
		'-formaction'=>"/{$PAGE['name']}/locale/{$locale}",
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered w_pointer condensed striped bordered hover',
		'-listfields'=>'p_id,t_id,locale,confirmed,failed,translation',
		'-searchfields'=>'translation,locale,p_id,t_id,confirmed,failed',
		'p_id_displayname'=>'PageID',
		't_id_displayname'=>'TemplateID',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/edit/%_id%','centerpop',{setprocessing:0})",
		'locale'=>$locale,
		'-sort'=>1
	));
}
function translateListLocales(){
	global $PAGE;
	global $MODULE;
	$opts=array(
		'-list'=>translateGetLocalesUsed(),
		'-hidesearch'=>1,
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered w_pointer condensed striped bordered hover',
		'-listfields'=>'flag4x3,locale,entry_cnt,confirmed_cnt,failed_cnt',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/list/%locale%','translate_results',{setprocessing:0});",
		'flag4x3_displayname'=>'Flag',
		'flag4x3_image'=>1,
		'entry_cnt_displayname'=>'Entries',
		'entry_cnt_style'=>'text-align:right;',
		'confirmed_cnt_displayname'=>'<span class="icon-mark w_success"></span>',
		'failed_cnt_displayname'=>'<span class="icon-block w_danger"></span>',
		'failed_cnt_style'=>'text-align:center;'
	);
	if(isset($MODULE['showflags']) && $MODULE['showflags']==0){
		$opts['-listfields']='locale,entry_cnt,confirmed_cnt,failed_cnt';
	}
	return databaseListRecords($opts);
}
function translateEditRec($rec){
	global $PAGE;
	$opts=array(
		'-action'=>"/t/1/{$PAGE['name']}/list/{$rec['locale']}",
		'-onsubmit'=>"return ajaxSubmitForm(this,'translate_results');",
		'setprocessing'=>0,
		'_menu'=>'translate',
		'func'=>'list',
		'locale'=>$rec['locale'],
		'-table'=>'_translations',
		'-fields'=>translateEditFields(),
		'translation_inputtype'=>'textarea',
		'translation_class'=>'form-control browser-default',
		'translation_style'=>'width:100%;height:150px;',
		'confirmed_inputtype'=>'checkbox',
		'confirmed_tvals'=>1,
		'_id'=>$rec['_id'],
		'-hide'=>'clone'
	);
	//return $opts['-fields'];
	return addEditDBForm($opts);
}
function translateEditFields(){
	return <<<ENDOFFIELDS
	<div style="display: flex;flex-direction: row;justify-content: space-between;margin-bottom:10px;">
		<div style="flex-grow:1;margin-right:10px;">Translation</div>
		<div style="flex-grow:1;margin-right:10px;">Confirmed</div>
		<div style="flex-grow:4;">[confirmed]</div>
	</div>
	<div>[translation]</div>
ENDOFFIELDS;
}
?>