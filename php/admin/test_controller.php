<?php
	global $CONFIG;
	loadExtrasJs(array('moment','chart','chartjs-plugin-datalabels'));
	if(isset($_REQUEST['multipart'])){
		processFileUploads();
		echo "PROCESSED ".printValue($_REQUEST);exit;
	}
	if(isset($_REQUEST['test'])){
		setView($_REQUEST['test'],1);
		switch(strtolower($_REQUEST['test'])){
			case 'chartjs':

			break;
			case 'languages':
				//sub-tab click: render just the one language panel into #languages_content
				if(isset($_REQUEST['lang'])){
					$langkey=strtolower($_REQUEST['lang']);
					setView('language_panel',1);
				}
				return;
			break;
			case 'script':
				//"Show full generated script" popup - wrap the panel's bridge snippet the way evalPHP would
				$lang=commonGetLangInfo($_REQUEST['lang']);
				$testdefs=testLanguageDefs();
				$testkey=strtolower($_REQUEST['lang']);
				$lang['code']=isset($testdefs[$testkey])?trim($testdefs[$testkey]['bridge']).PHP_EOL:'';
				setView('script',1);
				return;
			break;
			case 'language_includes':
				$lang=commonGetLangInfo($_REQUEST['lang']);
				setView('language_includes',1);
				return;
			break;
		}
		return;
	}
	setView('default');
?>