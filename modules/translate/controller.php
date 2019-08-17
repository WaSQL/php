<?php
	/*
		Module params:
			page - defaults to page name
			passthru_index - defaults to 0.  If the page name is t/1/bob/translate then passthru_index should be set to 1

	*/
	global $PAGE;
	global $MODULE;
	global $CONFIG;
	if(!isset($MODULE['title'])){
		$MODULE['title']='<span class="icon-translate w_success"></span> <translate>Translation Manager</translate>';
	}
	if(!isset($MODULE['page'])){
		$MODULE['page']=$PAGE['name'];
	}
	loadExtras('translate');
	loadExtrasCss('wacss');
	loadExtrasJs('wacss');
	if(!isset($_SESSION['REMOTE_LANG']) || !strlen($_SESSION['REMOTE_LANG'])){
		$_SESSION['REMOTE_LANG']=$_SERVER['REMOTE_LANG'];
	}
	$p=isset($MODULE['passthru_index'])?$MODULE['passthru_index']:0;
	$p1=$p+1;
	$p2=$p+2;
	switch(strtolower($_REQUEST['passthru'][$p])){
		case 'bulktranslate':
			$locale=addslashes($_REQUEST['passthru'][$p1]);
			$target=translateGetLocaleInfo($locale);
			$locale=translateGetSourceLocale();
			$source=translateGetLocaleInfo($locale);
			//get the source texts
			$topts=array(
				'-table'	=> '_translations',
				'-where'	=> "locale ='{$source['locale']}' and identifier in (select identifier from _translations where locale='{$target['locale']}')",
				'-fields'	=> 'locale,translation',
				'-order'	=> '_id'
			);
			if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
				$topts['-where']="locale ='{$source['locale']}' and  source_id={$CONFIG['translate_source_id']} and identifier in (select identifier from _translations where locale='{$target['locale']}' and  source_id={$CONFIG['translate_source_id']})";
			}
			$trecs=getDBRecords($topts);
			$source['lines']=array();
			foreach($trecs as $trec){$source['lines'][]=trim(strip_tags($trec['translation']));}
			//echo printValue($topts).printValue($source);exit;
			//echo $locale.printValue($info);exit;
			setView('bulktranslate',1);
			return;
		break;
		case 'bulktranslate_process':
			//echo printValue($_REQUEST);exit;
			$slines=preg_split('/[\r\n]/',trim($_REQUEST['source']));
			$tlines=preg_split('/[\r\n]/',trim($_REQUEST['target']));
			if(count($slines) != count($tlines)){
				echo '<span class="w_red w_bold">Line Counts do not match between source('.count($slines).') and target('.count($tlines).')</span>';
				exit;
			}
			$locale=addslashes($_REQUEST['locale']);
			foreach($slines as $i=>$sline){
				$identifier=sha1(trim($sline));
				$eopts=array(
					'-table'=>'_translations',
					'-where'=>"identifier='{$identifier}' and locale='{$locale}'",
					'translation'=>$tlines[$i],
					'confirmed'=>1,
					'p_id'=> 0,
					't_id'=> 0
				);
				if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
					$eopts['source_id']=$CONFIG['translate_source_id'];
				}
				$ok=editDBRecord($eopts);
			}
			echo '<span class="icon-mark w_green w_bold"></span> Updated '.count($slines).' translations';
			exit;
		break;
		case 'locale':
			$locale=addslashes($_REQUEST['passthru'][$p1]);
			//echo $locale.printValue($_REQUEST);exit;
			setView('default');
		break;
		case 'deletelocale':
			$info=translateGetLocaleInfo($_REQUEST['passthru'][$p1]);
			setView('deletelocale',1);
			return;
		break;
		case 'deletelocale_confirmed':
			$info=translateGetLocaleInfo($_REQUEST['passthru'][$p1]);
			$ok=delDBRecord(array(
				'-table'=>'_translations',
				'-where'=>"locale='{$info['locale']}'"
			));
			$locales=translateGetLocalesUsed();
			setView('default');
		break;
		case 'selectlocale':
			setView('selectlocale',1);
			return;
		break;
		case 'setlocale':
			$_SESSION['REMOTE_LANG']=$_REQUEST['passthru'][$p1];
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
			$recs=getDBRecords(array(
				'-table'=>'_translations',
				'locale'=>$source_locale
			));
			foreach($recs as $rec){
				translateText($rec['translation'],$locale);
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
		case 'edit':
			global $CONFIG;
			$source_locale=translateGetSourceLocale();
			$id=(integer)$_REQUEST['passthru'][$p1];
			$rec=getDBRecord(array('-table'=>'_translations','_id'=>$id,'-fields'=>'_id,locale,identifier'));
			$sopts=array('-table'=>'_translations','locale'=>$source_locale,'identifier'=>$rec['identifier'],'-fields'=>'translation');
			//echo $id.printValue($rec).printValue($sopts);exit;
			//echo printValue($sopts);exit;
			$source_rec=getDBRecord($sopts);
			$rec['source']=$source_rec['translation'];
			//get info

			$source=translateGetLocaleInfo($source_locale);
			//echo $source_locale.printValue($source);exit;
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