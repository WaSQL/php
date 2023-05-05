<?php
	switch(strtolower($_REQUEST['func'])){
		case 'json_forms':
			$type='json';
			setView('forms',1);
			return;
		break;
		case 'base64_forms':
			$type='base64';
			setView('forms',1);
			return;
		break;
		case 'json':
			$json = preg_replace('/[[:cntrl:]]/', '', trim($_REQUEST['json']));
			$decoded=json_decode($json,1,512,JSON_INVALID_UTF8_SUBSTITUTE);
			if(!is_array($decoded)){
				$decoded=json_last_error_msg();
			}
			$decoded=printValue($decoded);
			setView('decoded',1);
			return;
		break;
		case 'base64':
			$decoded=base64_decode($_REQUEST['base64']);
			setView('decoded',1);
			return;
		break;
		default:
			setView('default',1);
			$type='json';
		break;
	}
	setView('default',1);
?>
