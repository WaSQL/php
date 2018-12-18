<?php
	loadExtras('translate');
	switch(strtolower($_REQUEST['func'])){
		case 'list':
			$locale=addslashes($_REQUEST['locale']);
			setView('list',1);
			return;
		break;
		case 'edit':
			global $CONFIG;
			if(isset($CONFIG['translate_locale']) && strlen($CONFIG['translate_locale'])){
				$source_locale=$CONFIG['translate_locale'];
			}
			else{$source_locale='en-us';}
			$id=(integer)$_REQUEST['id'];
			$rec=getDBRecord(array('-table'=>'_translations','_id'=>$id,'-fields'=>'_id,locale,identifier'));
			$sopts=array('-table'=>'_translations','locale'=>$source_locale,'identifier'=>$rec['identifier'],'-fields'=>'translation');
			//echo printValue($sopts);exit;
			$source_rec=getDBRecord($sopts);
			$rec['source']=$source_rec['translation'];
			setView('edit',1);
			return;
		break;
		default:
			$locales=translateGetLocalesUsed();
			setView('default');
		break;
	}
?>