<?php
	global $PAGE;
	global $MODULE;
	if(!isset($MODULE['title'])){
		$MODULE['title']='<span class="icon-translate w_success"></span> <translate>Translation Manager</translate>';
	}
	loadExtras('translate');
	loadExtrasCss('wacss');
	loadExtrasJs('wacss');
	if(!isset($_SESSION['REMOTE_LANG']) || !strlen($_SESSION['REMOTE_LANG'])){
		$_SESSION['REMOTE_LANG']=$_SERVER['REMOTE_LANG'];
	}
	switch(strtolower($_REQUEST['passthru'][0])){
		case 'locale':
			$locale=addslashes($_REQUEST['passthru'][1]);
			//echo $locale.printValue($_REQUEST);exit;
			setView('default');
		break;
		case 'selectlocale':
			setView('selectlocale',1);
			return;
		break;
		case 'setlocale':
			$_SESSION['REMOTE_LANG']=$_REQUEST['passthru'][1];
			setView('setlocale',1);
			return;
		break;
		case 'selectlang':
			setView('selectlang',1);
			return;
		break;
		case 'addlang':
			$locale=$_REQUEST['passthru'][1];
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
			$locale=addslashes($_REQUEST['passthru'][1]);
			$info=translateGetLocaleInfo($locale);
			setView('list',1);
			return;
		break;
		case 'edit':
			global $CONFIG;
			$source_locale=translateGetSourceLocale();
			$id=(integer)$_REQUEST['passthru'][1];
			$rec=getDBRecord(array('-table'=>'_translations','_id'=>$id,'-fields'=>'_id,locale,identifier'));
			$sopts=array('-table'=>'_translations','locale'=>$source_locale,'identifier'=>$rec['identifier'],'-fields'=>'translation');
			//echo $id.printValue($rec).printValue($sopts);exit;
			//echo printValue($sopts);exit;
			$source_rec=getDBRecord($sopts);
			$rec['source']=$source_rec['translation'];
			//get info
			$source=translateGetLocaleInfo($source_locale);
			$dest=translateGetLocaleInfo($rec['locale']);
			//build google translate link
			$rec['google']="https://translate.google.com/#view=home&op=translate&sl={$source['lang']}&tl={$dest['lang']}&text=".urlencode($rec['source']);
			$rec['yandex']="https://translate.yandex.com/?lang={$source['lang']}-{$dest['lang']}&text=".urlencode($rec['source']);
			$rec['bing']="https://www.bing.com/translator/?from={$source['lang']}&to={$dest['lang']}&text=".urlencode($rec['source']);
			setView('edit',1);
			return;
		break;
		default:
			$locales=translateGetLocalesUsed();
			setView('default');
		break;
	}
?>