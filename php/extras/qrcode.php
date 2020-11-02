<?php
/*
	References:
	http://phpqrcode.sourceforge.net/
	https://stackoverflow.com/questions/45520988/creating-a-qr-code-with-a-centered-logo-in-php-with-php-qr-code-generator
	//example of inline image
	qrcodeCreate("{$url}",'','H',5)
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
/*
<img src="data:image/png;base64,<?=encodeBase64(qrcodeCreateWithLogo($url,$logo));?>" alt="qrcode" />
*/
//---------- begin function qrcodeCreateWithLogo--------------------
/**
* @describe creates QR codes from text and places logo file in center
* @param txt text- text to encode
* @param [logo] str - absolute path to logo to embed
* @param [transparent] integer- set white space in logo transparent. only works on png files
* @param [size] int - size of QRCode. Defaults to 5
* @param [margin] int - margin of QRCode. Defaults to 4
* @return binary - binary qrcode
* @usage <img src="data:image/png;base64,<?=encodeBase64(qrcodeCreate('http://www.yoursite.com'));?>" />
*/
function qrcodeCreateWithLogo($txt,$logo='',$transparent=0, $size = 4, $margin = 4){
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
	ob_start("callback");
	$wpath=getWasqlPath();
	$filename="{$wpath}/php/temp/".sha1($txt).'.png';
	//$text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4
	QRcode::png($txt,$filename,3,$size,$margin);
	//if the logo does not exist just return the qrcode
	if(!file_exists($logo)){
		$data=file_get_contents($filename);
		unlink($filename);
		return $data;
	}
	list($source_width, $source_height, $source_type) = getimagesize($logo);
	switch ($source_type) {
	    case IMAGETYPE_GIF:
	        $logo = imagecreatefromgif($logo);
	    break;
	    case IMAGETYPE_JPEG:
	        $logo = imagecreatefromjpeg($logo);
	    break;
	    case IMAGETYPE_PNG:
	        $logo = imagecreatefrompng($logo);
	        //make logo transparent
			if($transparent==1){
				imagecolortransparent($logo , imagecolorallocatealpha($logo , 0, 0, 0, 127));
				imagealphablending($logo , false);
				imagesavealpha($logo , true);
			}
		break;
		default:
		echo $logo."HERE".$source_type;exit;
			$logo = imagecreatefromstring(file_get_contents($logo));
		break;
	}
	// Start DRAWING LOGO IN QRCODE
    $QR = imagecreatefrompng($filename);
    // START TO DRAW THE IMAGE ON THE QR CODE
    $QR_width = imagesx($QR);
    $QR_height = imagesy($QR);

    $logo_width = imagesx($logo);
    $logo_height = imagesy($logo);

    // Scale logo to fit in the QR Code
    $logo_qr_width = $QR_width/3;
    $scale = $logo_width/$logo_qr_width;
    $logo_qr_height = $logo_height/$scale;
	//imagecolortransparent($QR, imagecolorallocate($logo, 255, 255, 255));
    imagecopyresampled($QR, $logo, $QR_width/3, $QR_height/3, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);

    // Save QR code again, but with logo on it
    imagepng($QR,$filename);
	$data=file_get_contents($filename);
	unlink($filename);
	return $data;
}
?>