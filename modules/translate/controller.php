<?php
	/*
		Module params:
			page - defaults to page name
			passthru_index - defaults to 0.  If the page name is t/1/bob/translate then passthru_index should be set to 1

		includeModule('translate',array('page'=>'manage/translations','ajaxpage'=>'t/1/manage/translations','passthru_index'=>1,'-hide'=>'bing_translate,yandex_translate'));

	*/
	global $PAGE;
	global $MODULE;
	global $CONFIG;
	global $PASSTHRU;
	global $locale;
	if(!isset($MODULE['title'])){
		$MODULE['title']='<span class="icon-translate w_success"></span> <wtranslate>Translation Manager</wtranslate>';
	}
	if(!isset($MODULE['page'])){
		$MODULE['page']=$PAGE['name'];
	}
	if(!isset($MODULE['ajaxpage'])){
		$MODULE['ajaxpage']=$PAGE['name'];
	}
	loadExtras('translate');
	loadExtrasCss('wacss');
	loadExtrasJs('wacss');
	if(!isDBTable('countries')){
		$ok=createWasqlTables('countries');
	}
	if(!isset($_SESSION['REMOTE_LANG']) || !strlen($_SESSION['REMOTE_LANG'])){
		$_SESSION['REMOTE_LANG']=$_SERVER['REMOTE_LANG'];
	}
	$p=isset($MODULE['passthru_index'])?$MODULE['passthru_index']:0;
	$p1=$p+1;
	$p2=$p+2;
	switch(strtolower($_REQUEST['passthru'][$p])){
		case 'bulktranslate':
			$locale=addslashes($_REQUEST['passthru'][$p1]);
			$idstr=str_replace(':',',',$_REQUEST['passthru'][$p2]);
			// Security: Validate that idstr only contains numbers, commas, and colons to prevent SQL injection
			if(!preg_match('/^[0-9,:]+$/', $idstr)){
				echo '<span class="w_red w_bold">Invalid ID format</span>';
				exit;
			}
			$target=translateGetLocaleInfo($locale);
			$locale=translateGetSourceLocale();
			$source=translateGetLocaleInfo($locale);
			//get the source texts
			$topts=array(
				'-table'	=> '_translations',
				'-where'	=> "locale ='{$source['locale']}' and identifier in (select identifier from _translations where _id in ({$idstr}))",
				'-fields'	=> 'locale,translation',
				'-order'	=> '_id'
			);
			if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
				$topts['-where']="locale ='{$source['locale']}' and identifier in (select identifier from _translations where _id in ({$idstr})) and  source_id in (0,{$CONFIG['translate_source_id']})";
			}
			$trecs=getDBRecords($topts);
			$source['lines']=array();
			foreach($trecs as $trec){
				$map=translateMapText($trec['translation']);
				$source['lines'][]=$map['maptext'];
			}
			setView('bulktranslate',1);
			return;
		break;
		case 'bulktranslate_process':
			$slines=preg_split('/[\r\n]/',trim($_REQUEST['source']));
			$tlines=preg_split('/[\r\n]/',trim($_REQUEST['target']));
			if(count($slines) != count($tlines)){
				echo '<span class="w_red w_bold">Line count mismatch. Please verify your translations.</span>';
				exit;
			}
			$source_locale=addslashes($_REQUEST['source_locale']);
			$target_locale=addslashes($_REQUEST['target_locale']);
			$sopts=array(
				'-table'=>'_translations',
				'-fields'=>'identifier,translation',
				'locale'=>$source_locale,
				'-index'=>'identifier'
			);

			if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
				$sopts['-where']="source_id in (0,{$CONFIG['translate_source_id']})";
			}
			$source_map=getDBRecords($sopts);
			foreach($slines as $i=>$sline){
				$identifier=sha1(trim($sline));
				$translation=translateUnmapText($source_map[$identifier]['translation'],$tlines[$i]);
				$eopts=array(
					'-table'=>'_translations',
					'-where'=>"identifier='{$identifier}' and locale='{$target_locale}'",
					'translation'=>$translation,
					'confirmed'=>1,
				);
				if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
					$eopts['source_id']=$CONFIG['translate_source_id'];
				}
				$ok=editDBRecord($eopts);
			}
			echo '<span class="icon-mark w_green w_bold"></span> Updated '.count($slines).' translations. Refresh to see changes.';
			exit;
		break;
		case 'locale':
			$locale=addslashes($_REQUEST['passthru'][$p1]);
			setView('default');
		break;
		case 'deletelocale':
			$info=translateGetLocaleInfo($_REQUEST['passthru'][$p1]);
			setView('deletelocale',1);
			return;
		break;
		case 'deletelocale_confirmed':
			$info=translateGetLocaleInfo($_REQUEST['passthru'][$p1]);
			$dopts=array(
				'-table'=>'_translations',
				'-where'=>"locale='{$info['locale']}'"
			);
			if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
				$dopts['-where'].= " and source_id={$CONFIG['translate_source_id']}";
			}
			$ok=delDBRecord($dopts);
			setView('deletelang',1);
		break;
		case 'selectlocale':
			setView('selectlocale',1);
			return;
		break;
		case 'setlocale':
			// Security: Validate locale format before storing in session (e.g., en, en-US, en_US, fr-FR, zh-CN, etc.)
			$new_locale=$_REQUEST['passthru'][$p1];
			if(preg_match('/^[a-z]{2,3}([-_][a-zA-Z]{2,3})?$/i', $new_locale)){
				$_SESSION['REMOTE_LANG']=$new_locale;
			}
			setView('setlocale',1);
			return;
		break;
		case 'selectlang':
			setView('selectlang',1);
			return;
		break;
		case 'addlang':
			$locale=$_REQUEST['passthru'][$p1];
			$source_locale=translateGetSourceLocale();
			$opts=array(
				'-table'=>'_translations',
				'locale'=>$source_locale,
				'wasql'=>0
			);
			if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
				$opts['source_id'].= $CONFIG['translate_source_id'];
			}
			$recs=getDBRecords($opts);
			foreach($recs as $rec){
				translateText($rec['translation'],$locale,0);
			}
			setView('addlang',1);
			return;
		break;
		case 'list':
			$locale=addslashes($_REQUEST['passthru'][$p1]);
			$info=translateGetLocaleInfo($locale);
			setView('list',1);
			return;
		break;
		case 'listnext':
			$locale=addslashes($_REQUEST['passthru'][$p1]);
			$info=translateGetLocaleInfo($locale);
			setView('listnext',1);
			return;
		break;
		case 'delete':
			$locale=addslashes($_REQUEST['passthru'][$p1]);
			$info=translateGetLocaleInfo($locale);
			$id=(integer)$_REQUEST['passthru'][$p2];
			$ok=delDBRecordById('_translations',$id);
			setView('listnext',1);
			return;
		break;
		case 'edit':
			global $CONFIG;
			$source_locale=translateGetSourceLocale();
			$id=(integer)$_REQUEST['passthru'][$p1];
			$rec=getDBRecord(array('-table'=>'_translations','_id'=>$id,'-fields'=>'_id,locale,identifier'));
			$sopts=array('-table'=>'_translations','locale'=>$source_locale,'identifier'=>$rec['identifier'],'-fields'=>'translation');
			if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
				$sopts['source_id']=$CONFIG['translate_source_id'];
			}
			$source_rec=getDBRecord($sopts);
			$rec['source']=$source_rec['translation'];
			//get info

			$source=translateGetLocaleInfo($source_locale);
			$dest=translateGetLocaleInfo($rec['locale']);
			//build google translate link
			switch(strtolower($dest['lang'])){
				case 'zh':
					$tl=$dest['lang'].'-'.strtoupper($dest['country']);
				break;
				default:
					$tl=$dest['lang'];
				break;
			}
			$rec['google']="https://translate.google.com/#view=home&op=translate&sl={$source['lang']}&tl={$tl}&text=".urlencode($rec['source']);
			$rec['yandex']="https://translate.yandex.com/?lang={$source['lang']}-{$dest['lang']}&text=".urlencode($rec['source']);
			$rec['bing']="https://www.bing.com/translator/?from={$source['lang']}&to={$tl}&text=".urlencode($rec['source']);
			setView('edit',1);
			return;
		break;
		default:
			$locales=translateGetLocalesUsed();
			setView('default');
		break;
	}
?>
