<?php
	switch(strtolower($_REQUEST['func'])){
		case 'json_forms':
			$type='json';
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
		case 'base64_forms':
			$type='base64';
			setView('decode_forms',1);
			return;
		break;
		case 'base64':
			$decoded=base64_decode($_REQUEST['base64']);
			setView('decoded',1);
			return;
		break;
		case 'url_forms':
			$type='url';
			setView('encode_decode_forms',1);
			return;
		break;
		case 'url_encode':
			$results=encodeURL($_REQUEST['str']);
			setView('results',1);
			return;
		break;
		case 'url_decode':
			$results=decodeURL($_REQUEST['str']);
			setView('results',1);
			return;
		break;
		case 'html_forms':
			$type='html';
			setView('encode_decode_forms',1);
			return;
		break;
		case 'html_encode':
			$results='<xmp style="text-wrap:pretty">'.htmlspecialchars($_REQUEST['str'],ENT_NOQUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', /*double_encode*/false).'</xmp style="text-wrap:pretty">';
			setView('results',1);
			return;
		break;
		case 'html_decode':
			$results=htmlspecialchars_decode($_REQUEST['str'],ENT_NOQUOTES | ENT_HTML5 | ENT_SUBSTITUTE);
			setView('results',1);
			return;
		break;
		case 'qrcode_form':
			$type='qrcode';
			setView('encode_form',1);
			return;
		break;
		case 'qrcode':
			loadExtras('qrcode');
			$ok=processFileUploads();
			$ok=processInlineFiles();
			$lines=preg_split('/[\r\n]+/',trim($_REQUEST['content']));
			$codes=array();
			$eclevel=isNum($_REQUEST['eclevel'])?$_REQUEST['eclevel']:'H';
			$size=isNum($_REQUEST['size'])?$_REQUEST['size']:5;
			$qty=isNum($_REQUEST['qty'])?$_REQUEST['qty']:5;
			$margin=isNum($_REQUEST['margin'])?$_REQUEST['margin']:4;
			$transparent=isNum($_REQUEST['transparent'])?$_REQUEST['transparent']:0;
			//echo printValue($_REQUEST).printValue($_FILES);exit;
			foreach($lines as $url){
				if(isset($_REQUEST['logo_abspath']) && file_exists($_REQUEST['logo_abspath'])){
					$qrcode=qrcodeCreateWithLogo($url,$_REQUEST['logo_abspath'],$transparent,$eclevel,$size,$margin);
				}
				else{
					$qrcode=qrcodeCreate($url,'',$eclevel,$size,$margin);
				}
				$b64=encodeBase64($qrcode);
				$codes[]=<<<ENDOFCODE
<div style="margin-bottom:20px;display:flex;flex-direction:column;align-items:center;">
	<div><img src="data:image/png;base64,{$b64}"></div>
	<div>{$url}</div>
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
