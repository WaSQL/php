<?php
/*
	References:
	https://phpqrcode.sourceforge.net/
	https://stackoverflow.com/questions/45520988/creating-a-qr-code-with-a-centered-logo-in-php-with-php-qr-code-generator
	//example of inline image
	qrcodeCreate("{$url}",'','H',5)
	$b64=encodeBase64(qrcodeCreate('http://www.yoursite.com'));
	echo <<<ENDOFSTR<img src="data:image/png;base64,{$b64}">ENDOFSTR;
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
* @param [gdimage] 0|1 - if 1 then return the GDImage object
* @return binary - binary qrcode
* @usage 
* 	$b64=encodeBase64(qrcodeCreate('http://www.yoursite.com'));
* 	echo <<<ENDOFSTR<img src="data:image/png;base64,{$b64}">ENDOFSTR;
*/
function qrcodeCreate($txt,$filename='',$eclevel='M',$size=3,$margin=4,$gdimage=0){
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
		//return GDImage?
    	if($gdimage==1){return imagecreatefrompng($filename);}
		$data=file_get_contents($filename);
		unlink($filename);
		return $data;
	}
	else{
		QRcode::png($txt,$filename,$eclevel,$size,$margin);
		if($gdimage==1){return imagecreatefrompng($filename);}
		return $filename;
	}

}
/*
//---------- begin function qrcodeCreateWithLogo--------------------
/**
* @describe creates QR codes from text and places logo file in center
* @param txt text- text to encode
* @param [logo] str - absolute path to logo to embed
* @param [transparent] integer- set white space in logo transparent. only works on png files
* @param [eclevel] integer- error correction level. L=up to 7% damage, M=up to 15% damage,Q=up to 25% damange, H=up to 30% damage. Defaults to M
* @param [size] int - size of QRCode. Defaults to 5
* @param [margin] int - margin of QRCode. Defaults to 4
* @param [gdimage] 0|1 - if 1 then return the GDImage object
* @return binary - binary qrcode
* @usage 
* 	$b64=encodeBase64(qrcodeCreateWithLogo('http://www.yoursite.com'),$logofile);
*	echo <<<ENDOFSTR<img src="data:image/png;base64,{$b64}">ENDOFSTR;
*/
function qrcodeCreateWithLogo($txt,$logo='',$transparent=0, $eclevel='M', $size = 4, $margin = 4,$gdimage=0){
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
	QRcode::png($txt,$filename,$eclevel,$size,$margin);
	//if the logo does not exist just return the qrcode
	if(!is_file($logo)){
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
		echo $logo." -- Unsupported logo source type: ".$source_type;exit;
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
    //return GDImage?
    if($gdimage==1){return $QR;}
    // Save QR code again, but with logo on it
    imagepng($QR,$filename);
	$data=file_get_contents($filename);
	unlink($filename);
	return $data;
}
function qrcodeRoundCorners($source, $radius) {
    $ws = imagesx($source);
    $hs = imagesy($source);

    $corner = $radius + 2;
    $s = $corner*2;

    $src = imagecreatetruecolor($s, $s);
    imagecopy($src, $source, 0, 0, 0, 0, $corner, $corner);
    imagecopy($src, $source, $corner, 0, $ws - $corner, 0, $corner, $corner);
    imagecopy($src, $source, $corner, $corner, $ws - $corner, $hs - $corner, $corner, $corner);
    imagecopy($src, $source, 0, $corner, 0, $hs - $corner, $corner, $corner);

    $q = 8; # change this if you want
    $radius *= $q;

    # find unique color
    do {
        $r = rand(0, 255);
        $g = rand(0, 255);
        $b = rand(0, 255);
    } while (imagecolorexact($src, $r, $g, $b) < 0);

    $ns = $s * $q;

    $img = imagecreatetruecolor($ns, $ns);
    $alphacolor = imagecolorallocatealpha($img, $r, $g, $b, 127);
    imagealphablending($img, false);
    imagefilledrectangle($img, 0, 0, $ns, $ns, $alphacolor);

    imagefill($img, 0, 0, $alphacolor);
    imagecopyresampled($img, $src, 0, 0, 0, 0, $ns, $ns, $s, $s);
    imagedestroy($src);

    imagearc($img, $radius - 1, $radius - 1, $radius * 2, $radius * 2, 180, 270, $alphacolor);
    imagefilltoborder($img, 0, 0, $alphacolor, $alphacolor);
    imagearc($img, $ns - $radius, $radius - 1, $radius * 2, $radius * 2, 270, 0, $alphacolor);
    imagefilltoborder($img, $ns - 1, 0, $alphacolor, $alphacolor);
    imagearc($img, $radius - 1, $ns - $radius, $radius * 2, $radius * 2, 90, 180, $alphacolor);
    imagefilltoborder($img, 0, $ns - 1, $alphacolor, $alphacolor);
    imagearc($img, $ns - $radius, $ns - $radius, $radius * 2, $radius * 2, 0, 90, $alphacolor);
    imagefilltoborder($img, $ns - 1, $ns - 1, $alphacolor, $alphacolor);
    imagealphablending($img, true);
    imagecolortransparent($img, $alphacolor);

    # resize image down
    $dest = imagecreatetruecolor($s, $s);
    imagealphablending($dest, false);
    imagefilledrectangle($dest, 0, 0, $s, $s, $alphacolor);
    imagecopyresampled($dest, $img, 0, 0, 0, 0, $s, $s, $ns, $ns);
    imagedestroy($img);

    # output image
    imagealphablending($source, false);
    imagecopy($source, $dest, 0, 0, 0, 0, $corner, $corner);
    imagecopy($source, $dest, $ws - $corner, 0, $corner, 0, $corner, $corner);
    imagecopy($source, $dest, $ws - $corner, $hs - $corner, $corner, $corner, $corner, $corner);
    imagecopy($source, $dest, 0, $hs - $corner, 0, $corner, $corner, $corner);
    imagealphablending($source, true);
    imagedestroy($dest);

    return $source;
}
/**
 * Generates an SVG QR code with H-level error correction, optional center image, and code text
 * 
 * @param string $data The URL or text to encode (max 2953 characters for version 40)
 * @param array $options Configuration options:
 *   - int    size            Size of the QR code in pixels (default: 300)
 *   - string color           Color of the QR code modules (default: '#000000')
 *   - string bgColor         Background color (default: '#FFFFFF')
 *   - string centerImage     URL or data URI of center image (default: null)
 *   - float  centerImageSize Size of center image as percentage 0.1-0.4 (default: 0.3)
 *   - int    quietZone       Quiet zone modules around QR code (default: 4)
 *   - string code            Text code to display below center image (default: null)
 *   - string codeColor       Color of the code text (default: '#000000')
 * @return string SVG string
 * @throws Exception If data is invalid or too long
 */
function qrcodeGenerateSVG($data, $options = []) {
    // Validate input
    if (!is_string($data) || strlen($data) === 0) {
        throw new Exception('Data must be a non-empty string');
    }
    
    if (strlen($data) > 2953) {
        throw new Exception('Data exceeds maximum length of 2953 characters');
    }
    
    // Parse and validate options
    $config = qrcodeParseOptions($options);
    
    try {
        // Generate QR matrix with H-level error correction
        $qr = qrcodeGenerateMatrix($data, 'H');
        $modules = count($qr);
        $totalSize = $modules + ($config['quietZone'] * 2);
        
        // Calculate center clear area if image is provided
        $centerStart = 0;
        $centerEnd = 0;
        $centerClearSize = 0;
        
        if ($config['centerImage']) {
            $centerClearSize = floor($modules * $config['centerImageSize']);
            // Ensure it's odd for symmetry
            if ($centerClearSize % 2 === 0) $centerClearSize++;
            $centerStart = floor(($modules - $centerClearSize) / 2);
            $centerEnd = $centerStart + $centerClearSize;
        }
        
        // Build SVG
        $svg = qrcodeBuildSVGHeader($config['size'], $totalSize);
        $svg .= qrcodeBuildBackground($totalSize, $config['bgColor']);
        $svg .= qrcodeBuildModules($qr, $modules, $config['quietZone'], $config['color'], 
                                    $centerStart, $centerEnd, $config['centerImage']);
        
        if ($config['centerImage']) {
            $svg .= qrcodeBuildCenterImage(
                $centerStart + $config['quietZone'],
                $centerClearSize,
                $config['centerImage'],
                $config['bgColor'],
                $config['code'],
                $config['codeColor'],
                $totalSize
            );
        }
        
        $svg .= '</svg>';
        
        return $svg;
    } catch (Exception $e) {
        throw new Exception('QR code generation failed: ' . $e->getMessage());
    }
}

/**
 * Parses and validates options
 * @private
 */
function qrcodeParseOptions($options) {
    $config = [
        'size' => 300,
        'color' => '#000000',
        'bgColor' => '#FFFFFF',
        'centerImage' => null,
        'centerImageSize' => 0.3,
        'quietZone' => 4,
        'code' => null,
        'codeColor' => '#000000'
    ];
    
    if (isset($options['size'])) {
        $size = (int)$options['size'];
        if ($size < 50 || $size > 10000) {
            throw new Exception('Size must be between 50 and 10000 pixels');
        }
        $config['size'] = $size;
    }
    
    if (isset($options['color'])) {
        if (!is_string($options['color']) || !qrcodeIsValidColor($options['color'])) {
            throw new Exception('Invalid color format');
        }
        $config['color'] = $options['color'];
    }
    
    if (isset($options['bgColor'])) {
        if (!is_string($options['bgColor']) || !qrcodeIsValidColor($options['bgColor'])) {
            throw new Exception('Invalid background color format');
        }
        $config['bgColor'] = $options['bgColor'];
    }
    
    if (isset($options['centerImage']) && $options['centerImage'] !== null) {
        if (!is_string($options['centerImage'])) {
            throw new Exception('Center image must be a string URL or data URI');
        }
        $config['centerImage'] = $options['centerImage'];
    }
    
    if (isset($options['centerImageSize'])) {
        $size = (float)$options['centerImageSize'];
        if ($size < 0.1 || $size > 0.4) {
            throw new Exception('Center image size must be between 0.1 and 0.4');
        }
        $config['centerImageSize'] = $size;
    }
    
    if (isset($options['quietZone'])) {
        $qz = (int)$options['quietZone'];
        if ($qz < 0 || $qz > 10) {
            throw new Exception('Quiet zone must be between 0 and 10');
        }
        $config['quietZone'] = $qz;
    }
    
    if (isset($options['code']) && $options['code'] !== null) {
        if (!is_string($options['code'])) {
            throw new Exception('Code must be a string');
        }
        $config['code'] = $options['code'];
    }
    
    if (isset($options['codeColor'])) {
        if (!is_string($options['codeColor']) || !qrcodeIsValidColor($options['codeColor'])) {
            throw new Exception('Invalid code color format');
        }
        $config['codeColor'] = $options['codeColor'];
    }
    
    return $config;
}

/**
 * Validates color format
 * @private
 */
function qrcodeIsValidColor($color) {
    $hexPattern = '/^#([0-9A-Fa-f]{3}){1,2}$/';
    $rgbPattern = '/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/';
    $rgbaPattern = '/^rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[\d.]+\s*\)$/';
    $namedColors = ['black', 'white', 'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'gray', 'grey'];
    
    return preg_match($hexPattern, $color) || 
           preg_match($rgbPattern, $color) || 
           preg_match($rgbaPattern, $color) || 
           in_array(strtolower($color), $namedColors);
}

/**
 * Builds SVG header
 * @private
 */
function qrcodeBuildSVGHeader($size, $totalSize) {
    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" shape-rendering="crispEdges">',
        $size, $size, $totalSize, $totalSize
    );
}

/**
 * Builds background rectangle
 * @private
 */
function qrcodeBuildBackground($totalSize, $bgColor) {
    return sprintf(
        '<rect width="%d" height="%d" fill="%s"/>',
        $totalSize, $totalSize, qrcodeEscapeXML($bgColor)
    );
}

/**
 * Builds QR code modules
 * @private
 */
function qrcodeBuildModules($qr, $modules, $quietZone, $color, $centerStart, $centerEnd, $hasCenterImage) {
    $svg = '<g>';
    
    for ($y = 0; $y < $modules; $y++) {
        for ($x = 0; $x < $modules; $x++) {
            // Skip center area if image is provided
            if ($hasCenterImage && $x >= $centerStart && $x < $centerEnd && 
                $y >= $centerStart && $y < $centerEnd) {
                continue;
            }
            
            if ($qr[$y][$x]) {
                $px = $x + $quietZone;
                $py = $y + $quietZone;
                $svg .= sprintf(
                    '<rect x="%d" y="%d" width="1" height="1" fill="%s"/>',
                    $px, $py, qrcodeEscapeXML($color)
                );
            }
        }
    }
    
    $svg .= '</g>';
    return $svg;
}

/**
 * Builds center image with background and optional code text
 * @private
 */
function qrcodeBuildCenterImage($centerStart, $centerClearSize, $centerImage, $bgColor, $code, $codeColor, $totalSize) {
    $imgPadding = max(1, $centerClearSize * 0.1);
    $imgActualSize = $centerClearSize - ($imgPadding * 2);
    $borderRadius = $centerClearSize * 0.15;
    
    $svg = '<g>';
    
    // White background for image
    $svg .= sprintf(
        '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" rx="%d"/>',
        $centerStart, $centerStart, $centerClearSize, $centerClearSize,
        qrcodeEscapeXML($bgColor), $borderRadius
    );
    
    // Image
    $svg .= sprintf(
        '<image x="%d" y="%d" width="%d" height="%d" href="%s" preserveAspectRatio="xMidYMid meet"/>',
        $centerStart + $imgPadding, $centerStart + $imgPadding,
        $imgActualSize, $imgActualSize, qrcodeEscapeXML($centerImage)
    );
    
    // Add code text below image if provided
    if ($code !== null) {
        $textY = $centerStart + $centerClearSize + 2;
        $textX = $totalSize / 2;
        $fontSize = max(1.5, $centerClearSize * 0.15);
        
        $svg .= sprintf(
            '<text x="%s" y="%s" font-family="Arial, sans-serif" font-size="%s" font-weight="bold" text-anchor="middle" fill="%s">%s</text>',
            $textX, $textY, $fontSize, qrcodeEscapeXML($codeColor), qrcodeEscapeXML($code)
        );
    }
    
    $svg .= '</g>';
    
    return $svg;
}

/**
 * Escapes XML special characters
 * @private
 */
function qrcodeEscapeXML($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * Generates the QR module matrix using the bundled phpqrcode library
 * (replaces the hand-rolled encoder, which produced unscannable codes - bug #52)
 * @private
 * @param string $data text to encode
 * @param string $errorLevel L|M|Q|H
 * @return array rows of booleans, true = dark module
 */
function qrcodeGenerateMatrix($data, $errorLevel) {
    $levels = ['L' => QR_ECLEVEL_L, 'M' => QR_ECLEVEL_M, 'Q' => QR_ECLEVEL_Q, 'H' => QR_ECLEVEL_H];
    $level = isset($levels[$errorLevel]) ? $levels[$errorLevel] : QR_ECLEVEL_H;
    // phpqrcode emits float-to-int deprecations on PHP 8.1+
    $oldLevel = error_reporting(error_reporting() & ~E_DEPRECATED);
    try {
        $rows = QRcode::text($data, false, $level, 1, 0);
    } catch (Exception $e) {
        error_reporting($oldLevel);
        throw $e;
    }
    error_reporting($oldLevel);
    if (!is_array($rows) || !count($rows)) {
        throw new Exception('phpqrcode returned no matrix');
    }
    $matrix = [];
    foreach ($rows as $row) {
        $line = [];
        $len = strlen($row);
        for ($x = 0; $x < $len; $x++) {
            $line[] = ($row[$x] === '1');
        }
        $matrix[] = $line;
    }
    return $matrix;
}
