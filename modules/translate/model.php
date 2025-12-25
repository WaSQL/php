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
	// Post-processing hook for language selections
	// Can be extended to add additional formatting or data
	return $recs;
}
function translateGetWasqlValue(){
	global $MODULE;
	return 0;
	//Note: wasql flag is currently hardcoded to 0
	//If needed in future, uncomment below to use MODULE setting:
	//$wasql=isset($MODULE['wasql'])?(integer)$MODULE['wasql']:1;
	//return $wasql;
}
function translateList(){
	global $MODULE;
	global $locale;
	$opts=array(
		'-divid' => "list_translations",
		'-table' => "_translations" ,
		'-tableclass' => "wacss_table is-condensed is-bordered is-striped",
		'-listfields' => "_id,source,translation,confirmed",
		'-action' => "/{$MODULE['ajaxpage']}/listnext/{$locale}",
		'-onsubmit' => "return pagingSubmit(this,'list_translations')",
		'-navonly' => "1",
		'-searchopers' => "ct",
		'source_options'=>array(
			'displayname' => "Source (<?=translateGetSourceLocale();?>)",
			'style' => "white-space: normal"
		),
		'translation_options'=>array(
			'displayname' => "Translation ({$locale})",
			'style' => "white-space: normal;"
		),
		'confirmed_options'=>array(
			'style' => "text-align:center",
			'checkmark' => "1",
			'checkmark_icon' => "icon-mark w_success",
			'displayname'=><<<ENDOFHTM
<div style="display:flex;justify-content:center;">
	<label for="checkall_ids" style="margin-right:5px;cursor:pointer;"><span class="icon-mark w_success"></span></label>
	<input  title="check confirmed" id="check_confirmed" type="checkbox" onclick="checkAllElements('data-confirmed','yes',this.checked);">
</div>
ENDOFHTM
		),
		'_id_displayname' => <<<ENDOFNAME
<div style="display:flex;justify-content:space-between;">
	<label for="checkall_ids" style="margin-right:3px;cursor:pointer;">ID</label>
	<input title="Check Not Confirmed" id="check_not_confirmed" type="checkbox" onclick="checkAllElements('data-confirmed','no',this.checked);">
</div>
ENDOFNAME,
		'locale' => $locale,
		'wasql' => translateGetWasqlValue(),
		'-order' => "confirmed,_id",
		'setprocessing'=>0,
		'-results_eval' => "translateListExtra",
	);
	return databaseListRecords($opts);
}
function translateListExtra($recs){
	global $MODULE;
	global $locale;
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
	if(!is_array($sourcemap) || !count($sourcemap)){return $recs;}
	foreach($recs as $i=>$rec){
		$key=$rec['identifier'];
		//source
		if(isset($sourcemap[$key])){
			$recs[$i]['source']=$sourcemap[$key]['translation'];
		}
		$class=isXML($recs[$i]['source'])?'w_red':'';
		$recs[$i]['source']=<<<ENDOFSOURCE
<xmp class="{$class}" style="display:inline-block;font-family:inherit;white-space:inherit;margin:0 0;">
{$recs[$i]['source']}
</xmp>
ENDOFSOURCE;
		//translation
		$class=isXML($recs[$i]['translation'])?'w_red':'';
		$recs[$i]['translation']=<<<ENDOFSOURCE
<div style="display:flex;justify-content:space-between;">
	<xmp class="{$class}" style="display:inline-block;font-family:inherit;white-space:inherit;margin:0 0;">
	{$recs[$i]['translation']}
	</xmp>
	<div style="display:flex;justify-content:flex-end;">
		<a href="#" style="margin-right:10px;" data-confirm="Delete Translation #{$rec['_id']}?" title="Edit" data-title="Edit Translation" data-nav="/{$MODULE['ajaxpage']}/delete/{$locale}/{$rec['_id']}" data-div="tranalate_results" data-setprocessing="0" onclick="return wacss.nav(this);"><span class="icon-erase w_big w_red"></span></a>
		<a href="#" title="Edit" data-title="Edit Translation" data-nav="/{$MODULE['ajaxpage']}/edit/{$rec['_id']}" data-div="modal" data-setprocessing="0" onclick="return wacss.nav(this);"><span class="icon-edit w_big"></span></a>
	</div>
</div>
ENDOFSOURCE;
		$confirmed='';
		if($recs[$i]['confirmed']==1){
			$confirmed='yes';
			$recs[$i]['confirmed']='<span class="icon-mark w_success"></span>';
		}
		else{
			$confirmed='no';
			$recs[$i]['confirmed']='<span class="icon-block w_danger"></span>';
		}
		$recs[$i]['_id']=<<<ENDOFCHECKBOX
<div style="display:flex;justify-content:space-between;">
	<label for="trid_{$rec['_id']}" style="margin-right:3px;cursor:pointer;">{$rec['_id']}</label>
	<input type="checkbox" data-confirmed="{$confirmed}" data-group="_id_checkbox" id="trid_{$rec['_id']}" name="_id[]" value="{$rec['_id']}">
</div>
ENDOFCHECKBOX;
	}
	return $recs;
}
/* translateListLocales returns recs for json in dblistrecords */
function translateListLocales(){
	global $MODULE;
	$wasql=translateGetWasqlValue();
	$recs=translateGetLocalesUsed(0,$wasql);
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
