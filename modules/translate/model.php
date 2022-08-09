<?php
function translateHasPermission($perm){
	global $MODULE;
	if(!isset($MODULE['-hide'])){return true;}
	$hides=preg_split('/\,+/',$MODULE['-hide']);
	foreach($hides as $hide){
		if(strtolower($perm)==strtolower($hide)){return false;}
	}
	return true;
}
function translateShowLocaleSelections(){
	global $MODULE;
	$recs=translateGetLocalesUsed(1);
	$current_locale=isset($_SESSION['REMOTE_LANG'])?strtolower($_SESSION['REMOTE_LANG']):strtolower($_SERVER['REMOTE_LANG']);
	foreach($recs as $i=>$rec){
		if($current_locale ==$rec['locale']){
			$recs[$i]['name'].=' <span class="icon-mark w_green"></span>';
		}
	}
	$recs=sortArrayByKeys($recs,array('name'=>SORT_ASC,'locale'=>SORT_ASC));
	return databaseListRecords(array(
		'-list'=>$recs,
		'-anchormap'=>'name',
		'-tableclass'=>'table table-condensed table-bordered table-striped table-hover',
		'-listfields'=>'locale,name,country',
		'-trclass'=>'w_pointer',
		'-onclick'=>"return ajaxGet('/{$MODULE['page']}/setlocale/%locale%','translate_nulldiv',{setprocessing:0,cp_title:'Locale Set'})",
		'-hidesearch'=>1
	));
}
/* translateGetLangSelections return recs for json string selectlang dblistrecords */
function translateGetLangSelections(){
	global $MODULE;
	$recs=translateGetLocales();
	$used=translateGetLocalesUsed(1);
	$current_locale=isset($_SESSION['REMOTE_LANG'])?strtolower($_SESSION['REMOTE_LANG']):strtolower($_SERVER['REMOTE_LANG']);
	foreach($recs as $i=>$rec){
		if($current_locale ==$rec['locale']){
			$recs[$i]['name'].=' <span class="icon-mark w_green"></span>';
		}
		elseif(isset($used[$rec['locale']])){
			$recs[$i]['name'].=' <span class="icon-mark w_orange"></span>';
		}
	}
	$recs=sortArrayByKeys($recs,array('name'=>SORT_ASC,'locale'=>SORT_ASC));
	return $recs;
}
function translateGetLangSelectionsExtra($recs){
	//echo printValue($used);exit;
	foreach($recs as $i=>$rec){

	}
	return $recs;
}
function translateAddExtraInfo($recs){
	$locale=$recs[0]['locale'];
	$ids=array();
	foreach($recs as $rec){
		$ids[]=$rec['_id'];
	}
	$idstr=implode(',',$ids);
	//sourcemap
	$source_locale=translateGetSourceLocale();	
	$opts=array(
		'-table'=>'_translations',
		'-where'=>"wasql=0 and locale='{$source_locale}' and identifier in (select identifier from _translations where wasql=0 and locale='{$locale}' and _id in ({$idstr}))",
		'-index'=>'identifier',
		'-fields'=>'identifier,translation'
	);
	$sourcemap=getDBRecords($opts);
	if(!count($sourcemap)){return $recs;}
	foreach($recs as $i=>$rec){
		$key=$rec['identifier'];
		if(isset($sourcemap[$key])){
			$recs[$i]['source']=$sourcemap[$key]['translation'];
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
function translateGetWasqlValue(){
	global $MODULE;
	//echo printValue($MODULE);exit;
	$wasql=isset($MODULE['wasql'])?(integer)$MODULE['wasql']:1;
	return $wasql;
}
/* translateListLocales returns recs for json in dblistrecords */
function translateListLocales(){
	global $MODULE;
	$wasql=translateGetWasqlValue();
	$recs=translateGetLocalesUsed(0,$wasql);
	//echo 'recs'.printValue($recs).printValue($MODULE);exit;
	$localesmap=array();
	foreach(translateGetLocales() as $locale){
		$localesmap[$locale['lang']][]=$locale;
		$localesmap[$locale['locale']][]=$locale;
	}
	if(isset($MODULE['-locales'])){
		if(!is_array($MODULE['-locales'])){
			$MODULE['-locales']=preg_split('/\,+/',$MODULE['-locales']);
		}
		$filtermap=array();
		foreach($MODULE['-locales'] as $i=>$val){
			$val=strtolower(trim($val));
			if(isset($localesmap[$val])){
				foreach($localesmap[$val] as $lval){
					$filtermap[$lval['locale']]=$lval['locale'];
				}
			}
		}
		if(count($filtermap)){
			foreach($recs as $i=>$rec){
				if(!isset($filtermap[$rec['locale']])){
					unset($recs[$i]);
				}
			}
		}
	}
	return $recs;
}

function translateListLocalesExtra($recs){
	global $MODULE;
	$current_locale=isset($_SESSION['REMOTE_LANG'])?strtolower($_SESSION['REMOTE_LANG']):strtolower($_SERVER['REMOTE_LANG']);
	$confirm_msg=translateText('Delete?','',1);
	foreach($recs as $i=>$rec){
		//echo printValue($rec);exit;

		$recs[$i]['flag4x3'] = <<<ENDOFFLAG
<div style="display:flex;justify-content:space-between;align-items:center;width:max-content;">
	<div style="margin-right:10px;flex:1;align-self:left;">
		<a href="#" onclick="return ajaxGet('/{$MODULE['page']}/list/{$rec['locale']}','translate_results',{setprocessing:'processing'});" class="w_gray w_smaller">
			<img src="{$rec['flag4x3']}" style="height:20px;width:auto;margin-right:5px;" />
			{$rec['name']}
		</a>
	</div>
	<div>
		<a href="#remove" data-confirm="{$confirm_msg} -- {$rec['name']}" onclick="if(!confirm(this.dataset.confirm)){return false;}return ajaxGet('/{$MODULE['ajaxpage']}/deletelocale_confirmed/{$rec['locale']}','translate_nulldiv');"><span class="icon-close w_smallest w_red"></span></a>
	</div>
</div>
ENDOFFLAG;
		if($current_locale ==strtolower($rec['locale'])){
			$recs[$i]['locale'].=' <span class="icon-mark w_green"></span>';
		}
	}
	return $recs;
}
function translateEditRec($rec){
	global $MODULE;
	$opts=array(
		'-action'		=> "/{$MODULE['ajaxpage']}/list/{$rec['locale']}",
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
		'translation_style'		=> 'height:150px;max-width:100%;',
		'translation_wrap'		=> 'soft',
		'confirmed'		=> 1,
		'_id'			=> $rec['_id'],
		'-hide'			=> 'clone',
		'-save'			=> translateText('Save','',1),
		'-reset'		=> translateText('Reset','',1),
		'-delete'		=> translateText('Delete','',1)
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