<?php
function pageListRecords($locale){
	return databaseListRecords(array(
		'-table'=>'_translations',
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered',
		'-listfields'=>'p_id,t_id,locale,confirmed,failed,translation',
		'p_id_displayname'=>'PageID',
		't_id_displayname'=>'TemplateID',
		'-onclick'=>"return ajaxGet('{$_SERVER['PHP_SELF']}','centerpop',{_menu:'translate',func:'edit',id:'%_id%'})",
		'locale'=>$locale,
		'-sort'=>1
	));
}
function pageEditRec($rec){
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