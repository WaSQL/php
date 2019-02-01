<?php
function translateListRecords($locale){
	global $PAGE;
	return databaseListRecords(array(
		'-table'=>'_translations',
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered',
		'-listfields'=>'p_id,t_id,locale,confirmed,failed,translation',
		'p_id_displayname'=>'PageID',
		't_id_displayname'=>'TemplateID',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/edit/%_id%','centerpop')",
		'locale'=>$locale,
		'-sort'=>1
	));
}
function translateListLocales(){
	global $PAGE;
	return databaseListRecords(array(
		'-list'=>translateGetLocalesUsed(),
		'-hidesearch'=>1,
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered',
		'-listfields'=>'flag4x3,locale,entry_cnt,confirmed_cnt,failed_cnt',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/list/%locale%','translate_results');",
		'flag4x3_displayname'=>'Flag',
		'flag4x3_image'=>1,
		'entry_cnt_displayname'=>'Entries',
		'entry_cnt_style'=>'text-align:right;',
		'confirmed_cnt_displayname'=>'<span class="icon-mark w_success"></span>',
		'failed_cnt_displayname'=>'<span class="icon-block w_danger"></span>',
		'failed_cnt_style'=>'text-align:center;'
	));
}
function translateEditRec($rec){
	$opts=array(
		'-action'=>$_SERVER['PHP_SELF'],
		'-onsubmit'=>"return ajaxSubmitForm(this,'translate_results');",
		'_menu'=>'translate',
		'func'=>'list',
		'locale'=>$rec['locale'],
		'-table'=>'_translations',
		'-fields'=>'<div><div style="float:right;" title="Mark Confirmed">[confirmed]</div>Translation </div><div>[translation]</div>',
		'translation_inputtype'=>'textarea',
		'translation_class'=>'form-control',
		'translation_style'=>'width:100%',
		'confirmed_inputtype'=>'checkbox',
		'confirmed_tvals'=>1,
		'_id'=>$rec['_id'],
		'-hide'=>'clone'
	);
	//return $opts['-fields'];
	return addEditDBForm($opts);
}
?>