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
 * Generates QR code matrix with specified error correction level
 * @private
 */
function qrcodeGenerateMatrix($data, $errorLevel) {
    $version = qrcodeDetermineVersion($data, $errorLevel);
    $size = 21 + ($version - 1) * 4;
    
    $matrix = array_fill(0, $size, array_fill(0, $size, false));
    $reserved = array_fill(0, $size, array_fill(0, $size, false));
    
    // Add function patterns
    qrcodeAddFinderPatterns($matrix, $reserved, $size);
    qrcodeAddSeparators($matrix, $reserved, $size);
    qrcodeAddAlignmentPatterns($matrix, $reserved, $version);
    qrcodeAddTimingPatterns($matrix, $reserved, $size);
    qrcodeAddDarkModule($matrix, $reserved, $version);
    qrcodeReserveFormatArea($reserved, $size);
    
    // Encode data with error correction
    $encoded = qrcodeEncodeData($data, $version, $errorLevel);
    
    // Place data in matrix
    qrcodePlaceData($matrix, $reserved, $encoded, $size);
    
    // Apply best mask pattern
    $bestMask = qrcodeSelectBestMask($matrix, $reserved, $size);
    qrcodeApplyMask($matrix, $reserved, $bestMask, $size);
    
    // Add format information
    qrcodeAddFormatInfo($matrix, $errorLevel, $bestMask, $size);
    
    return $matrix;
}

/**
 * Determines appropriate QR version
 * @private
 */
function qrcodeDetermineVersion($data, $errorLevel) {
    $capacities = [
        'H' => [9, 16, 26, 36, 46, 60, 74, 86, 108, 130, 151, 177, 203, 241, 258, 292, 
                322, 364, 394, 442, 482, 509, 565, 611, 661, 715, 751, 805, 868, 908, 
                982, 1030, 1112, 1168, 1228, 1283, 1351, 1423, 1499, 1579]
    ];
    
    $byteLength = qrcodeCalculateByteLength($data);
    
    foreach ($capacities[$errorLevel] as $v => $capacity) {
        if ($byteLength <= $capacity) {
            return $v + 1;
        }
    }
    
    throw new Exception('Data too long for QR code');
}

/**
 * Calculates byte length including mode and count
 * @private
 */
function qrcodeCalculateByteLength($data) {
    $charCountBits = 8;
    $totalBits = 4 + $charCountBits + (strlen($data) * 8);
    return ceil($totalBits / 8);
}

/**
 * Adds finder patterns
 * @private
 */
function qrcodeAddFinderPatterns(&$matrix, &$reserved, $size) {
    $positions = [[0, 0], [$size - 7, 0], [0, $size - 7]];
    
    foreach ($positions as list($row, $col)) {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $y = $row + $r;
                $x = $col + $c;
                
                if ($y >= 0 && $y < $size && $x >= 0 && $x < $size) {
                    $reserved[$y][$x] = true;
                    $isOuter = $r === 0 || $r === 6 || $c === 0 || $c === 6;
                    $isInner = $r >= 2 && $r <= 4 && $c >= 2 && $c <= 4;
                    $matrix[$y][$x] = $isOuter || $isInner;
                }
            }
        }
    }
}

/**
 * Adds separators
 * @private
 */
function qrcodeAddSeparators(&$matrix, &$reserved, $size) {
    $separators = [
        [7, 0, 8, 1], [0, 7, 1, 8],
        [$size - 8, 0, 1, 8], [$size - 7, 7, 7, 1],
        [0, $size - 8, 8, 1], [7, $size - 7, 1, 7]
    ];
    
    foreach ($separators as list($y, $x, $h, $w)) {
        for ($r = 0; $r < $h; $r++) {
            for ($c = 0; $c < $w; $c++) {
                if ($y + $r < $size && $x + $c < $size) {
                    $reserved[$y + $r][$x + $c] = true;
                    $matrix[$y + $r][$x + $c] = false;
                }
            }
        }
    }
}

/**
 * Adds alignment patterns
 * @private
 */
function qrcodeAddAlignmentPatterns(&$matrix, &$reserved, $version) {
    if ($version === 1) return;
    
    $positions = qrcodeGetAlignmentPositions($version);
    $size = count($matrix);
    
    foreach ($positions as list($cy, $cx)) {
        if (($cy < 10 && $cx < 10) || 
            ($cy < 10 && $cx > $size - 10) || 
            ($cy > $size - 10 && $cx < 10)) {
            continue;
        }
        
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $y = $cy + $r;
                $x = $cx + $c;
                if ($y >= 0 && $y < $size && $x >= 0 && $x < $size) {
                    $reserved[$y][$x] = true;
                    $isOuter = abs($r) === 2 || abs($c) === 2;
                    $isCenter = $r === 0 && $c === 0;
                    $matrix[$y][$x] = $isOuter || $isCenter;
                }
            }
        }
    }
}

/**
 * Gets alignment positions
 * @private
 */
function qrcodeGetAlignmentPositions($version) {
    $positionTable = [
        2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 
        9 => [6, 26, 46], 10 => [6, 28, 50]
    ];
    
    $coords = $positionTable[$version] ?? [6, 18];
    $result = [];
    
    foreach ($coords as $y) {
        foreach ($coords as $x) {
            $result[] = [$y, $x];
        }
    }
    
    return $result;
}

/**
 * Adds timing patterns
 * @private
 */
function qrcodeAddTimingPatterns(&$matrix, &$reserved, $size) {
    for ($i = 8; $i < $size - 8; $i++) {
        if (!$reserved[6][$i]) {
            $matrix[6][$i] = $i % 2 === 0;
            $reserved[6][$i] = true;
        }
        if (!$reserved[$i][6]) {
            $matrix[$i][6] = $i % 2 === 0;
            $reserved[$i][6] = true;
        }
    }
}

/**
 * Adds dark module
 * @private
 */
function qrcodeAddDarkModule(&$matrix, &$reserved, $version) {
    $y = 4 * $version + 9;
    if ($y < count($matrix)) {
        $matrix[$y][8] = true;
        $reserved[$y][8] = true;
    }
}

/**
 * Reserves format area
 * @private
 */
function qrcodeReserveFormatArea(&$reserved, $size) {
    for ($i = 0; $i < 9; $i++) {
        if ($i !== 6) $reserved[8][$i] = true;
        if ($i !== 6) $reserved[$i][8] = true;
    }
    
    for ($i = 0; $i < 8; $i++) {
        $reserved[8][$size - 1 - $i] = true;
        $reserved[$size - 1 - $i][8] = true;
    }
}

/**
 * Encodes data
 * @private
 */
function qrcodeEncodeData($data, $version, $errorLevel) {
    $charCountBits = $version < 10 ? 8 : 16;
    
    $bits = '0100'; // Byte mode
    $bits .= str_pad(decbin(strlen($data)), $charCountBits, '0', STR_PAD_LEFT);
    
    for ($i = 0; $i < strlen($data); $i++) {
        $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
    }
    
    $bits .= '0000'; // Terminator
    
    while (strlen($bits) % 8 !== 0) {
        $bits .= '0';
    }
    
    $capacity = qrcodeGetDataCapacity($version, $errorLevel);
    $padBytes = ['11101100', '00010001'];
    $padIndex = 0;
    
    while (strlen($bits) < $capacity * 8) {
        $bits .= $padBytes[$padIndex % 2];
        $padIndex++;
    }
    
    $bits = substr($bits, 0, $capacity * 8);
    
    $ecBytes = qrcodeGetErrorCorrectionBytes($version, $errorLevel);
    for ($i = 0; $i < $ecBytes * 8; $i++) {
        $bits .= '0';
    }
    
    return $bits;
}

/**
 * Gets data capacity
 * @private
 */
function qrcodeGetDataCapacity($version, $errorLevel) {
    $capacities = [
        1 => ['H' => 9], 2 => ['H' => 16], 3 => ['H' => 26], 
        4 => ['H' => 36], 5 => ['H' => 46], 6 => ['H' => 60], 
        7 => ['H' => 74], 8 => ['H' => 86], 9 => ['H' => 108], 
        10 => ['H' => 130]
    ];
    
    return $capacities[$version][$errorLevel] ?? 9;
}

/**
 * Gets error correction bytes
 * @private
 */
function qrcodeGetErrorCorrectionBytes($version, $errorLevel) {
    $ecBytes = [
        1 => ['H' => 17], 2 => ['H' => 28], 3 => ['H' => 44], 
        4 => ['H' => 64], 5 => ['H' => 88], 6 => ['H' => 112], 
        7 => ['H' => 130], 8 => ['H' => 156], 9 => ['H' => 192], 
        10 => ['H' => 230]
    ];
    
    return $ecBytes[$version][$errorLevel] ?? 17;
}

/**
 * Places data in matrix
 * @private
 */
function qrcodePlaceData(&$matrix, $reserved, $bits, $size) {
    $bitIndex = 0;
    $direction = -1;
    
    for ($x = $size - 1; $x > 0; $x -= 2) {
        if ($x === 6) $x--;
        
        for ($i = 0; $i < $size; $i++) {
            $y = $direction < 0 ? $size - 1 - $i : $i;
            
            for ($c = 0; $c < 2; $c++) {
                $xx = $x - $c;
                
                if (!$reserved[$y][$xx] && $bitIndex < strlen($bits)) {
                    $matrix[$y][$xx] = $bits[$bitIndex] === '1';
                    $bitIndex++;
                }
            }
        }
        
        $direction = -$direction;
    }
}

/**
 * Selects best mask
 * @private
 */
function qrcodeSelectBestMask($matrix, $reserved, $size) {
    $bestMask = 0;
    $lowestPenalty = PHP_INT_MAX;
    
    for ($mask = 0; $mask < 8; $mask++) {
        $testMatrix = array_map(function($row) {
            return $row;
        }, $matrix);
        
        qrcodeApplyMask($testMatrix, $reserved, $mask, $size);
        $penalty = qrcodeCalculatePenalty($testMatrix, $size);
        
        if ($penalty < $lowestPenalty) {
            $lowestPenalty = $penalty;
            $bestMask = $mask;
        }
    }
    
    return $bestMask;
}

/**
 * Calculates penalty
 * @private
 */
function qrcodeCalculatePenalty($matrix, $size) {
    $penalty = 0;
    
    for ($i = 0; $i < $size; $i++) {
        $lastBit = -1;
        $count = 0;
        
        for ($j = 0; $j < $size; $j++) {
            if ($matrix[$i][$j] === $lastBit) {
                $count++;
            } else {
                if ($count >= 5) $penalty += ($count - 2);
                $lastBit = $matrix[$i][$j];
                $count = 1;
            }
        }
        if ($count >= 5) $penalty += ($count - 2);
    }
    
    return $penalty;
}

/**
 * Applies mask
 * @private
 */
function qrcodeApplyMask(&$matrix, $reserved, $maskPattern, $size) {
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if (!$reserved[$y][$x]) {
                if (qrcodeGetMaskValue($maskPattern, $y, $x)) {
                    $matrix[$y][$x] = !$matrix[$y][$x];
                }
            }
        }
    }
}

/**
 * Gets mask value
 * @private
 */
function qrcodeGetMaskValue($pattern, $y, $x) {
    switch ($pattern) {
        case 0: return ($y + $x) % 2 === 0;
        case 1: return $y % 2 === 0;
        case 2: return $x % 3 === 0;
        case 3: return ($y + $x) % 3 === 0;
        case 4: return (floor($y / 2) + floor($x / 3)) % 2 === 0;
        case 5: return (($y * $x) % 2) + (($y * $x) % 3) === 0;
        case 6: return ((($y * $x) % 2) + (($y * $x) % 3)) % 2 === 0;
        case 7: return (((($y + $x) % 2) + (($y * $x) % 3)) % 2) === 0;
        default: return false;
    }
}

/**
 * Adds format information
 * @private
 */
function qrcodeAddFormatInfo(&$matrix, $errorLevel, $maskPattern, $size) {
    $errorBits = ['L' => '01', 'M' => '00', 'Q' => '11', 'H' => '10'];
    $formatStr = $errorBits[$errorLevel] . str_pad(decbin($maskPattern), 3, '0', STR_PAD_LEFT);
    
    $formatWithEC = qrcodeGenerateFormatBits($formatStr);
    
    $mask = '101010000010010';
    $maskedFormat = '';
    for ($i = 0; $i < 15; $i++) {
        $maskedFormat .= $formatWithEC[$i] === $mask[$i] ? '0' : '1';
    }
    
    for ($i = 0; $i < 15; $i++) {
        $bit = $maskedFormat[$i] === '1';
        
        if ($i < 6) {
            $matrix[8][$i] = $bit;
        } elseif ($i < 8) {
            $matrix[8][$i + 1] = $bit;
        } elseif ($i === 8) {
            $matrix[7][8] = $bit;
        } else {
            $matrix[14 - $i][8] = $bit;
        }
        
        if ($i < 8) {
            $matrix[$size - 1 - $i][8] = $bit;
        } else {
            $matrix[8][$size - 15 + $i] = $bit;
        }
    }
}

/**
 * Generates format bits with BCH
 * @private
 */
function qrcodeGenerateFormatBits($data) {
    $poly = bindec('10100110111');
    $bits = bindec($data) << 10;
    
    for ($i = 4; $i >= 0; $i--) {
        if ($bits & (1 << ($i + 10))) {
            $bits ^= $poly << $i;
        }
    }
    
    $result = (bindec($data) << 10) | $bits;
    return str_pad(decbin($result), 15, '0', STR_PAD_LEFT);
}
?>