<?php
/* barcode generator
		Note: Requires GD. If you want to run this on windows, do the following:
			install gd: http://gnuwin32.sourceforge.net/packages/gd.htm
			uncomment "extension=php_gd2.dll" in php.ini
USAGE EXAMPLES:
	<img src="/php/barcode.php?XRD563432356" />
	<img src="/php/barcode.php?XRD563432356&height=100" />
Options:
    width   (default=0) Width of image in pixels. set to 0 for autowidth
    height  (default=70) Height of image in pixels
    format  (default=jpeg) Can be "jpeg", "png", or "gif"
    quality (default=100) For JPEG only: ranges from 0-100
    text    (default=1) 0 to disable text below barcode, 1 to enable
    fontnum	(default=3) 1 to 5, 1 is the smallest, 5 is the largest
*/
//-----------------------------------------------------------------------------------------------------------------
//set defaults
if(!function_exists('ImageCreate')){
	echo "barcode.php will only work if GD is installed";
	exit;
}
//set $barcode to the first key
foreach($_REQUEST as $barcode=>$val){break;}
//set defaults
$options=array('width'=>300,'height'=>70,'quality'=>100,'format'=>'jpg','text'=>1,'fontnum'=>3);
//override defaults
foreach($options as $key=>$val){
	if(isset($_REQUEST[$key]) && strlen($_REQUEST[$key])){$options[$key]=$_REQUEST[$key];}
}
Barcode39($barcode,$options['width'],$options['height'],$options['quality'],$options['format'],$options['text'],$options['fontnum']);
/***************************************************************************************/
//---------- begin function Barcode39
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function Barcode39 ($barcode, $width=0, $height=70, $quality=100, $format='jpg', $text=1,$FontNum=3){
	//set image header Content-type
	if($width==0){
		//autowidth
		//list($left,,$right) = imageftbbox( 12, 0, arial.ttf, $barcode);
		//$width = $right - $left;
		//$width=strlen($barcode)*($FontNum+1)*8;
		$width=getStringPixelWidth($barcode);
		}
	$NarrowRatio = 20;
    $WideRatio = 55;
    $QuietRatio = 35;
    $nChars = (strlen($barcode)+2) * ((6 * $NarrowRatio) + (3 * $WideRatio) + ($QuietRatio));
    $Pixels = $width / $nChars;
    $NarrowBar = (int)(20 * $Pixels);
    $WideBar = (int)(55 * $Pixels);
    $QuietBar = (int)(35 * $Pixels);
    $ActualWidth = (($NarrowBar * 6) + ($WideBar*3) + $QuietBar) * (strlen ($barcode)+2);
	$im = imagecreate($width, $height)	or die ("Cannot Initialize new GD image stream ({$widthin}, {$width}, {$height})");
	switch (strtoupper($format)){
        case "JPEG":
        case "JPG":
			header ("Content-type: image/jpeg");
			break;
        case "PNG":header("Content-type: image/png");break;
        case "GIF":header("Content-type: image/gif");break;
    }
    $White = ImageColorAllocate($im, 255, 255, 255);
    $Black = ImageColorAllocate($im, 0, 0, 0);
    //ImageColorTransparent($im, $White);
    ImageInterLace($im, 1);
    if (($NarrowBar == 0) || ($NarrowBar == $WideBar) || ($NarrowBar == $QuietBar) || ($WideBar == 0) || ($WideBar == $QuietBar) || ($QuietBar == 0)){
        ImageString($im,1,0,0,"{$width},{$height}",$Black);
        OutputImage($im,$format,$quality);
        exit;
    }
    $CurrentBarX = (int)(($width - $ActualWidth) / 2);
    $Color = $White;
    $BarcodeFull = "*".strtoupper($barcode)."*";
    //set barcodefull to a string
    settype($BarcodeFull,"string");
    $FontHeight = ImageFontHeight($FontNum);
    $FontWidth = ImageFontWidth($FontNum);
    if ($text != 0){
		//Remove the asterics
		$textFull=strtoupper($barcode);
		//center teh text
        $CenterLoc = (int)(($width-1) / 2) - (int)(($FontWidth * strlen($textFull)) / 2);
        ImageString($im, $FontNum, $CenterLoc, $height-$FontHeight, $textFull, $Black);
    }
	else{
		$FontHeight=-2;
	}
	for ($i=0; $i<strlen($BarcodeFull); $i++){
    	$StripeCode = getCode39($BarcodeFull[$i]);
		for ($n=0; $n < 9; $n++){
            if ($Color == $White){$Color = $Black;}
            else{$Color = $White;}
			switch($StripeCode[$n]){
            	case '0':
                	ImageFilledRectangle($im, $CurrentBarX, 0, $CurrentBarX+$NarrowBar, $height-1-$FontHeight-2, $Color);
                    $CurrentBarX += $NarrowBar;
                    break;
				case '1':
                    ImageFilledRectangle($im, $CurrentBarX, 0, $CurrentBarX+$WideBar, $height-1-$FontHeight-2, $Color);
                    $CurrentBarX += $WideBar;
                    break;
            }
        }
		$Color = $White;
        ImageFilledRectangle($im, $CurrentBarX, 0, $CurrentBarX+$QuietBar, $height-1-$FontHeight-2, $Color);
        $CurrentBarX += $QuietBar;
    }
	OutputImage($im, $format, $quality);
	// Free up memory
	imagedestroy($im);
}
// Output an image to the browser
//---------- begin function OutputImage
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function OutputImage($im,$format='PNG',$quality=100){
	switch(strtoupper($format)){
    	case "JPEG":
		case "JPG":
			imagejpeg($im,NULL,$quality);
			break;
        case "PNG":
        	//png quality is from 0-9
        	$quality=round(($quality/10),0)-1;
			imagepng($im,NULL,$quality);
			break;
        case "GIF":imagegif($im);break;
    }
}
//---------- begin function getCode39
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getCode39($ascii){
	switch($ascii){
        case ' ':return "011000100";
    	case '$':return "010101000";
        case '%':return "000101010";
        case '*':return "010010100";
        case '+':return "010001010";
        case '|':return "010000101";
        case '.':return "110000100";
        case '/':return "010100010";
		case '-':return "010000101";
        case '0':return "000110100";
        case '1':return "100100001";
        case '2':return "001100001";
    	case '3':return "101100000";
        case '4':return "000110001";
        case '5':return "100110000";
        case '6':return "001110000";
        case '7':return "000100101";
        case '8':return "100100100";
        case '9':return "001100100";
        case 'A':return "100001001";
        case 'B':return "001001001";
        case 'C':return "101001000";
        case 'D':return "000011001";
        case 'E':return "100011000";
        case 'F':return "001011000";
        case 'G':return "000001101";
        case 'H':return "100001100";
        case 'I':return "001001100";
        case 'J':return "000011100";
        case 'K':return "100000011";
        case 'L':return "001000011";
        case 'M':return "101000010";
        case 'N':return "000010011";
        case 'O':return "100010010";
        case 'P':return "001010010";
        case 'Q':return "000000111";
        case 'R':return "100000110";
        case 'S':return "001000110";
        case 'T':return "000010110";
        case 'U':return "110000001";
        case 'V':return "011000001";
        case 'W':return "111000000";
        case 'X':return "010010001";
        case 'Y':return "110010000";
        case 'Z':return "011010000";
        default:return "011000100";
    }
}
//---------- begin function getStringPixelWidth
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getStringPixelWidth($string){
	$width = 0;
  	if (!empty($string)) {
		for ($i = 0; $i < strlen($string); $i++) {
      		$w = getCharPixelWidth(substr($string, $i, 1));
      		if ($w){$width += $w;}
		}
  	}
  	$width=round(($width*3),0);
  	if($width < 200){return 200;}
  	return $width;
}
//---------- begin function getStringPixelWidth
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getCharPixelWidth($ch){
	switch($ch){
        case ' ':return 3;
    	case '$':return 7;
        case '%':return 11;
        case '*':return 5;
        case '+':return 7;
        case '|':return 3;
        case '.':return 3;
        case '/':return 3;
		case '-':return 4;
        case '0':return 7;
        case '1':return 7;
        case '2':return 7;
    	case '3':return 7;
        case '4':return 7;
        case '5':return 7;
        case '6':return 7;
        case '7':return 7;
        case '8':return 7;
        case '9':return 7;
        case 'A':return 7;
        case 'B':return 8;
        case 'C':return 9;
        case 'D':return 9;
        case 'E':return 8;
        case 'F':return 7;
        case 'G':return 9;
        case 'H':return 9;
        case 'I':return 3;
        case 'J':return 6;
        case 'K':return 8;
        case 'L':return 7;
        case 'M':return 9;
        case 'N':return 9;
        case 'O':return 9;
        case 'P':return 8;
        case 'Q':return 9;
        case 'R':return 9;
        case 'S':return 8;
        case 'T':return 7;
        case 'U':return 9;
        case 'V':return 7;
        case 'W':return 11;
        case 'X':return 7;
        case 'Y':return 7;
        case 'Z':return 7;
        default:return 7;
    }
}
?>