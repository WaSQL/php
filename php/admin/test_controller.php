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
			case 'script':
				$lang=commonGetLangInfo($_REQUEST['lang']);
				switch(strtolower($lang['name'])){
					case 'python':
						$lang['code']="print ('Hello, world!')".PHP_EOL."print(wasql.user('username'))".PHP_EOL;
					break;
					case 'nodejs':
						$lang['code']="console.log(process.version);".PHP_EOL;
					break;
					case 'perl':
						$lang['code']="print ('Hello, world!<br>');".PHP_EOL."print wasqlUser('username');".PHP_EOL;
					break;
					case 'lua':
						$lang['code']="print('Hello, world!');".PHP_EOL."var2=wasqlUser('username');".PHP_EOL."print(var2);".PHP_EOL;
					break;
					case 'ruby':
						$lang['code']="puts 'Hello, world!'".PHP_EOL;
					break;
				}
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