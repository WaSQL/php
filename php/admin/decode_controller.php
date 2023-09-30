<?php
	switch(strtolower($_REQUEST['func'])){
		case 'json_forms':
			$type='json';
			setView('decode_forms',1);
			return;
		break;
		case 'base64_forms':
			$type='base64';
			setView('decode_forms',1);
			return;
		break;
		case 'json':
			$json = preg_replace('/[[:cntrl:]]/', '', trim($_REQUEST['json']));
			$decoded=decodeJSON($json);
			$decoded=printValue($decoded);
			setView('decoded',1);
			return;
		break;
		case 'base64':
			$decoded=base64_decode($_REQUEST['base64']);
			setView('decoded',1);
			return;
		break;
		case 'qrcode_form':
			$type='qrcode';
			setView('encode_form',1);
			return;
		break;
		case 'qrcode':
			loadExtras('qrcode');
			$lines=preg_split('/[\r\n]+/',trim($_REQUEST['content']));
			$codes=array();
			foreach($lines as $url){
				$qrcode=qrcodeCreate($url,'','H',5);
				$b64=encodeBase64($qrcode);
				$codes[]=<<<ENDOFCODE
<div style="margin-bottom:20px;display:flex;flex-direction:column;align-items:center;">
	<div><img src="data:image/png;base64,{$b64}"></div>
	<div class="w_bold">{$url}</div>
</div>
ENDOFCODE;
			}
			$results='<div style="display:flex;flex-direction:column;align-items:flex-start;">'.implode(PHP_EOL,$codes).'</div>';
			setView('results',1);
			return;
		break;
		case 'barcode_form':
			$type='barcode';
			setView('encode_form',1);
			return;
		break;
		case 'barcode':
			$lines=preg_split('/[\r\n]+/',trim($_REQUEST['content']));
			$codes=array();
			foreach($lines as $code){
				$codes[]="<div style=\"margin-bottom:30px;\"><img src=\"/php/barcode.php?{$code}\"></div>";
			}
			$results='<div style="display:flex;flex-direction:column;align-items:flex-start;">'.implode(PHP_EOL,$codes).'</div>';
			setView('results',1);
			return;
		break;
		default:
			setView('default',1);
			$type='json';
		break;
	}
	setView('default',1);
?>
