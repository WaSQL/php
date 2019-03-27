<?php
function translateShowLocaleSelections(){
	global $PAGE;
	$recs=translateGetLocales();
	return databaseListRecords(array(
		'-list'=>$recs,
		'-tableclass'=>'table table-condensed table-bordered table-striped table-hover',
		'flag4x3_image'=>1,
		'-listfields'=>'locale,name,country,flag4x3',
		'-trclass'=>'w_pointer',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/setlocale/%locale%','modal',{setprocessing:0,cp_title:'Locale Set'})",
		'-hidesearch'=>1
	));
}
function translateShowLangSelections(){
	global $PAGE;
	$recs=translateGetLocales();
	return databaseListRecords(array(
		'-list'=>$recs,
		'-tableclass'=>'table table-condensed table-bordered table-striped table-hover',
		'flag4x3_image'=>1,
		'-listfields'=>'locale,name,country,flag4x3',
		'-trclass'=>'w_pointer',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/addlang/%locale%','modal',{setprocessing:0,cp_title:'Locale Set'})",
		'-hidesearch'=>1
	));
}
function translateListRecords($locale){
	global $PAGE;
	$source_locale=translateGetSourceLocale();
	return databaseListRecords(array(
		'-table'=>'_translations',
		'-formaction'=>"/{$PAGE['name']}/locale/{$locale}",
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered',
		'-trclass'=>'w_pointer',
		'-listfields'=>'page,template,source,translation,confirmed',
		'-searchfields'=>'source,translation,p_id,t_id',
		'source_displayname'=>"Source ({$source_locale})",
		'source_style'=>'white-space: normal;',
		'translation_displayname'=>"Translation ({$locale})",
		'translation_style'=>'white-space: normal;',
		'confirmed_style'=>'text-align:center',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/edit/%_id%','modal',{setprocessing:0})",
		'locale'=>$locale,
		'-order'=>'confirmed,p_id',
		'-results_eval'=>'translateAddExtraInfo',
	));
}
function translateAddExtraInfo($recs){
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
		'-where'=>"locale='{$source_locale}' and p_id in ({$p_idstr}) or t_id in ({$t_idstr})",
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
function translateListLocales(){
	global $PAGE;
	global $MODULE;
	$opts=array(
		'-list'=>translateGetLocalesUsed(),
		'-hidesearch'=>1,
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered w_pointer condensed striped bordered hover',
		'-listfields'=>'flag4x3,locale,entry_cnt,confirmed_cnt',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/list/%locale%','translate_results',{setprocessing:'processing'});",
		'flag4x3_displayname'=>'Flag',
		'entry_cnt_displayname'=>'Entries',
		'entry_cnt_style'=>'text-align:right;',
		'confirmed_cnt_displayname'=>'<span class="icon-mark w_success"></span>',
		'-results_eval'=>'translateListLocalesExtra'
	);
	if(isset($MODULE['showflags']) && $MODULE['showflags']==0){
		$opts['-listfields']='locale,entry_cnt,confirmed_cnt';
	}
	return databaseListRecords($opts);
}
function translateListLocalesExtra($recs){
	foreach($recs as $i=>$rec){
		$recs[$i]['flag4x3']="<div><img src=\"{$rec['flag4x3']}\" style=\"max-height:28px;max-width:28px;border-radius:18px;box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.2), 0 3px 10px 0 rgba(0, 0, 0, 0.19);\" /></div><div>{$rec['name']}</div>";
	}
	return $recs;
}
function translateEditRec($rec){
	global $PAGE;
	$opts=array(
		'-action'		=> "/t/1/{$PAGE['name']}/list/{$rec['locale']}",
		'-onsubmit'		=> "return ajaxSubmitForm(this,'translate_results');",
		'-name'			=> 'translateEditForm',
		'setprocessing'	=> 0,
		'_menu'			=> 'translate',
		'func'			=> 'list',
		'locale'		=> $rec['locale'],
		'-table'		=> '_translations',
		'-fields'		=> translateEditFields(),
		'-editfields'	=> 'translation,confirmed',
		'-order'		=> 'confirmed',
		'translation_inputtype'	=> 'textarea',
		'translation_class'		=> 'form-control browser-default',
		'translation_style'		=> 'height:150px;',
		'translation_wrap'		=> 'soft',
		'confirmed'		=> 1,
		'_id'			=> $rec['_id'],
		'-hide'			=> 'clone,delete',
		'-save'			=> translateText('Save'),
		'-reset'		=> translateText('Reset')
	);
	//return $opts['-fields'];
	return addEditDBForm($opts);
}
function translateEditFields(){
	return <<<ENDOFFIELDS
	<div>[translation]</div>
ENDOFFIELDS;
}
?>