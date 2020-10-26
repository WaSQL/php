<?php
/*
	References:
	http://phpqrcode.sourceforge.net/
	//example of inline image
	<img src="data:image/png;base64,<?=encodeBase64(qrcodeCreate('http://www.yoursite.com'));?>">
*/
$progpath=dirname(__FILE__);
require_once("{$progpath}/qrcode/phpqrcode.php");
//---------- begin function qrcodeCreate--------------------
/**
* @describe creates QR codes from text
* @param txt text- text to encode
* @param [filename] str - return a filename, defaults to false
* @param [eclevel] integer- error correction level. L=up to 7% damage, M=up to 15% damage,Q=up to 25% damange, H=up to 30% damage. Defaults to M
* @param [size] int - size of QRCode. Defaults to 3
* @param [margin] int - margin of QRCode. Defaults to 4
* @return binary - binary qrcode
* @usage <img src="data:image/png;base64,<?=encodeBase64(qrcodeCreate('http://www.yoursite.com'));?>" />
*/
function qrcodeCreate($txt,$filename='',$eclevel=1, $size = 3, $margin = 4){
	switch(strtolower($eclevel)){
		case 0:
		case 'l':
			$eclevel=0;
		break;
		case 1:
		case 'm':
		default:
			$eclevel=1;
		break;
		case 2:
		case 'q':
			$eclevel=2;
		break;
		case 3:
		case 'h':
			$eclevel=3;
		break;
	}
	$wpath=getWasqlPath();
	if(!strlen($filename)){
		$filename="{$wpath}/php/temp/".sha1($txt).'.png';
		//$text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4
		QRcode::png($txt,$filename,$eclevel,$size,$margin);
		$data=file_get_contents($filename);
		unlink($filename);
		return $data;
	}
	else{
		QRcode::png($txt,$filename,$eclevel,$size,$margin);
		return $filename;
	}

}
?>