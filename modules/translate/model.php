<?php
function translateShowLocaleSelections(){
	global $PAGE;
	$recs=translateGetLocales();
	return databaseListRecords(array(
		'-list'=>$recs,
		'-anchormap'=>'name',
		'-tableclass'=>'table table-condensed table-bordered table-striped table-hover',
		'-listfields'=>'locale,name,country',
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
		'-listfields'=>'locale,name,country',
		'-anchormap'=>'name',
		'-trclass'=>'w_pointer',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/addlang/%locale%','modal',{setprocessing:0,cp_title:'Locale Set'})",
		'-hidesearch'=>1
	));
}
function translateListRecords($locale){
	global $PAGE;
	global $CONFIG;
	$source_locale=translateGetSourceLocale();
	$opts=array(
		'-table'=>'_translations',
		'-formaction'=>"/{$PAGE['name']}/locale/{$locale}",
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered',
		'-trclass'=>'w_pointer',
		'-listfields'=>'page,template,source,translation,confirmed',
		'-searchfields'=>'source,translation',
		'-searchopers'=>'ct',
		'source_displayname'=>"Source ({$source_locale})",
		'source_style'=>'white-space: normal;',
		'translation_displayname'=>"Translation ({$locale})",
		'translation_style'=>'white-space: normal;',
		'confirmed_style'=>'text-align:center',
		'-onclick'=>"return ajaxGet('/{$PAGE['name']}/edit/%_id%','modal',{setprocessing:0})",
		'locale'=>$locale,
		'-order'=>'confirmed,p_id',
		'-results_eval'=>'translateAddExtraInfo',
	);
	if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
		$opts['source_id']=$CONFIG['translate_source_id'];
	}
	if(isset($_REQUEST['filter_field'])){
		switch(strtolower($_REQUEST['filter_field'])){
			case 'source':
				unset($_REQUEST['filter_field']);
				$v=str_replace('source-ct-','',$_REQUEST['_filters']);
				unset($_REQUEST['_filters']);
				$opts['-where']="identifier in (select identifier from _translations where locale='{$source_locale}' and translation like '%{$v}%')";
		}
	}
	//return printValue($_REQUEST);
	return databaseListRecords($opts);
}
function translateAddExtraInfo($recs){
	$locale=$recs[0]['locale'];
	$p_ids=array();
	$t_ids=array();
	$ids=array();
	foreach($recs as $rec){
		if(!in_array($rec['p_id'],$p_ids)){$p_ids[]=$rec['p_id'];}
		if(!in_array($rec['t_id'],$t_ids)){$t_ids[]=$rec['t_id'];}
		$ids[]=$rec['_id'];
	}
	if(!count($p_ids)){return $recs;}
	$p_idstr=implode(',',$p_ids);
	$t_idstr=implode(',',$t_ids);
	$idstr=implode(',',$ids);
	//sourcemap
	$source_locale=translateGetSourceLocale();	
	$opts=array(
		'-table'=>'_translations',
		'-where'=>"locale='{$source_locale}' and identifier in (select identifier from _translations where locale='{$locale}' and _id in ({$idstr}))",
		'-index'=>'identifier',
		'-fields'=>'identifier,translation'
	);
	$sourcemap=getDBRecords($opts);
	//echo printValue($opts).printValue($sourcemap);exit;
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
		'-anchormap'=>'locale',
		'-tableclass'=>'table table-condensed table-bordered table-hover table-bordered condensed striped bordered hover',
		'-listfields'=>'flag4x3,locale,entry_cnt,confirmed_cnt',
		'locale_onclick'=>"return ajaxGet('/{$PAGE['name']}/list/%locale%','translate_results',{setprocessing:'processing'});",
		'entry_cnt_onclick'=>"return ajaxGet('/{$PAGE['name']}/list/%locale%','translate_results',{setprocessing:'processing'});",
		'entry_cnt_displayname'=>translateText('Entries'),
		'entry_cnt_style'=>'text-align:right;',
		'flag4x3_displayname'=>translateText('Location'),
		'confirmed_cnt_displayname'=>'<span class="icon-mark w_success"></span>',
		'-results_eval'=>'translateListLocalesExtra'
	);
	return databaseListRecords($opts);
}
function translateListLocalesExtra($recs){
	global $PAGE;
	foreach($recs as $i=>$rec){
		$flag='<div>';
		$flag .= "	<div style=\"float:right\"><a href=\"#remove\" onclick=\"return ajaxGet('/{$PAGE['name']}/deletelocale/{$rec['locale']}','modal');\"><span class=\"icon-close w_red\"></span></a></div>";
		$flag .="	<div><a href=\"#\" onclick=\"return ajaxGet('/{$PAGE['name']}/list/{$rec['locale']}','translate_results',{setprocessing:'processing'});\">{$rec['name']}</a></div>";
		$flag .="	</div>";
		$recs[$i]['flag4x3']=$flag;
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
		'-hide'			=> 'clone',
		'-save'			=> translateText('Save'),
		'-reset'		=> translateText('Reset'),
		'-delete'		=> translateText('Delete')
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