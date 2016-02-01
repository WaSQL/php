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
* @param [filename] - return a filename, defaults to false
* @return binary - binary qrcode
* @usage <img src="data:image/png;base64,<?=encodeBase64(qrcodeCreate('http://www.yoursite.com'));?>" />
*/
function qrcodeCreate($txt,$filename=''){
	$wpath=getWasqlPath();
	if(!strlen($filename)){
		$filename="{$wpath}/php/temp/".sha1($txt).'.png';
		QRcode::png($txt,$filename);
		$data=file_get_contents($filename);
		unlink($filename);
		return $data;
	}
	else{
		QRcode::png($txt,$filename);
		return $filename;
	}

}
?>