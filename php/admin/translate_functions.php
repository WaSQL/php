<?php
function pageListRecords($locale){
	return databaseListRecords(array(
		'-table'=>'_translations',
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered',
		'-trclass'=>'w_pointer',
		'-listfields'=>'page,template,source,translation,confirmed',
		'-searchfields'=>'source,translation,p_id,t_id',
		'-onclick'=>"return ajaxGet('{$_SERVER['PHP_SELF']}','modal',{_menu:'translate',func:'edit',id:'%_id%',cp_title:'Add/Edit Translation'})",
		'locale'=>$locale,
		'-sort'=>1,
		'-results_eval'=>'pageAddExtraInfo',
	));
}
function pageAddExtraInfo($recs){
	$locale=$recs[0]['locale'];
	$p_ids=array();
	$t_ids=array();
	foreach($recs as $rec){
		if(!in_array($rec['p_id'],$p_ids)){$p_ids[]=$rec['p_id'];}
		if(!in_array($rec['t_id'],$t_ids)){$t_ids[]=$rec['t_id'];}
	}
	if(!count($p_ids)){return $recs;}
	$p_idstr=implode(',',$p_ids);
	$t_idstr=implode(',',$t_ids);
	//sourcemap
	$source_locale=translateGetSourceLocale();	
	$opts=array(
		'-table'=>'_translations',
		'-where'=>"locale='{$source_locale}' and p_id in ($p_idstr)",
		'-index'=>'identifier',
		'-fields'=>'identifier,translation'
	);
	$sourcemap=getDBRecords($opts);
	//pagemap
	$opts=array(
		'-table'=>'_pages',
		'-where'=>"_id in ($p_idstr)",
		'-index'=>'_id',
		'-fields'=>'_id,name'
	);
	$pagemap=getDBRecords($opts);
	//echo printValue($opts).printValue($pagemap);exit;
	//templatemap
	$opts=array(
		'-table'=>'_templates',
		'-where'=>"_id in ($t_idstr)",
		'-index'=>'_id',
		'-fields'=>'_id,name'
	);
	$templatemap=getDBRecords($opts);
	//echo printValue($opts).printValue($templatemap);exit;
	if(!count($sourcemap)){return $recs;}
	foreach($recs as $i=>$rec){
		$key=$rec['identifier'];
		if(isset($sourcemap[$key])){
			$recs[$i]['source']=$sourcemap[$key]['translation'];
		}
		$id=$rec['p_id'];
		if(isset($pagemap[$id])){
			$recs[$i]['page']=$id.' - '.$pagemap[$id]['name'];
		}
		$id=$rec['t_id'];
		if(isset($templatemap[$id])){
			$recs[$i]['template']=$id.' - '.$templatemap[$id]['name'];
		}
		if($recs[$i]['confirmed']==1){
			$recs[$i]['confirmed']='<span class="icon-mark w_success"></span>';
		}
		else{
			$recs[$i]['confirmed']='<span class="icon-block w_danger"></span>';
		}
	}
	return $recs;
}
function pageEditRec($rec){
	$opts=array(
		'-action'=>$_SERVER['PHP_SELF'],
		'-onsubmit'=>"return ajaxSubmitForm(this,'translate_results');",
		'_menu'=>'translate',
		'func'=>'list',
		'locale'=>$rec['locale'],
		'-table'=>'_translations',
		'confirmed'=>1,
		'-fields'=>pageEditFields(),
		'-editfields'=>'translation,confirmed',
		'translation_inputtype'=>'textarea',
		'translation_class'=>'form-control',
		'translation_style'=>'width:100%',
		'confirmed_inputtype'=>'checkbox',
		'confirmed_tvals'=>1,
		'_id'=>$rec['_id'],
		'-hide'=>'clone,delete'
	);
	//return $opts['-fields'];
	return addEditDBForm($opts);
}
function pageEditFields(){
	return <<<ENDOFEDITFIELDS
	<div>[translation]</div>
ENDOFEDITFIELDS;
}
?>