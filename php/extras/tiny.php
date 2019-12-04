<?php
$path=realpath('../');
if(is_file("{$path}/schema.php")){
	include_once("{$path}/schema.php");
}
else{
	$path=realpath('../php');
	if(is_file("{$path}/schema.php")){
		include_once("{$path}/schema.php");
	}
}
//make sure _tiny table exists
if(!isDBTable('_tiny')){
	createWasqlTables(array('_tiny'));
}
//---------- begin function tinyUrl --------------------------------------
/**
* @describe creates a tinyurl
* @param url string - URL string to make tiny
* @return string  - tiny url
*/
function tinyUrl($url,$forcenew=0){
	//add record to tiny
	if($forcenew==1){
		$id=addDBRecord(array(
			'-table'=>'_tiny',
			'url'=>$url
		));
	}
	else{
		$rec=getDBRecord(array('-table'=>'_tiny','url'=>$url,'-fields'=>'_id'));
		if(isset($rec['_id'])){$id=$rec['_id'];}
		else{
			$id=addDBRecord(array(
				'-table'=>'_tiny',
				'url'=>$url
			));
		}
	}
	if(isNum($id)){
		$code=tinyBase2Base($id, 10, 36);
			if(strlen($code) < 3){
			$seed=str_split('$!');
			shuffle($seed);
			$code .= array_shift($seed);
					$seed=str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
					shuffle($seed);
					while(strlen($code) < 4){
				$code .= array_shift($seed);
			}
		}
		$http=isset($_SERVER['HTTPS'])?'https':'http';
		return "{$http}://{$_SERVER['HTTP_HOST']}/y/{$code}";
	}
	return 'ERROR:'.$id;
}
//---------- begin function tinyCode
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function tinyCode($code){
	$code=$oricode=strtoupper($code);
	if(preg_match('/^(.+?)[\$\!]/',$code,$m)){$code=$m[1];}

	$id=tinyBase2Base($code, 36, 10);
	//echo "code:{$code}, id:{$id}";exit;
	if(!isNum($id)){return 'ERROR';}
	//add record to tiny
	$rec=getDBRecord(array('-table'=>'_tiny','_id'=>$id,'-fields'=>'_id,url'));
	if(isset($rec['url'])){
		if(stringContains($rec['url'],'?')){
			$rec['url'].="&_tiny={$rec['_id']}";
		}
		else{
			$rec['url'].="?_tiny={$rec['_id']}";
		}
		return $rec['url'];
	}
	return 'ERROR: No such tiny url';
}
//---------- begin function tinyTest
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function tinyTest(){
	date_default_timezone_set('America/Denver');
	for($i=0;$i<20;$i++){
		$base=rand(36,36);
		$rnum=rand(1,6000000000);
		$b62=tinyBase2Base($rnum, 10, $base);
		$b10=tinyBase2Base($b62, $base, 10);
		$tiny="{$base}{$b62}";
		echo "Base:{$base}, Num:{$rnum}, Tiny: {$b62} \n";
	}
}
//---------- begin function tinyDec2Base
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function tinyDec2Base($iNum, $iBase, $iScale=0) { // cope with base 2..62
	$sChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$sResult = ''; // Store the result
		// special case for Base64 encoding
		if ($iBase == 64){
			$sChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	}
	$sNum = is_integer($iNum) ? "$iNum" : (string)$iNum;
	$iBase = intval($iBase); // incase it is a string or some weird decimal
	// Check to see if we are an integer or real number
	if (strpos($sNum, '.') !== FALSE) {
		list ($sNum, $sReal) = explode('.', $sNum, 2);
		$sReal = '0.' . $sReal;
	} 
	else{
		$sReal = '0';
	}
	while (bccomp($sNum, 0, $iScale) != 0) { // still data to process
			$sRem = bcmod($sNum, $iBase); // calc the remainder
			$sNum = bcdiv( bcsub($sNum, $sRem, $iScale), $iBase, $iScale );
			$sResult = $sChars[$sRem] . $sResult;
		}
		if ($sReal != '0') {
			$sResult .= '.';
			$fraciScale = $iScale;
			while($fraciScale-- && bccomp($sReal, 0, $iScale) != 0) { // still data to process
					$sReal = bcmul($sReal, $iBase, $iScale); // multiple the float part with the base
					$sFrac = 0;
					if (bccomp($sReal ,1, $iScale) > -1){
						list($sFrac, $dummy) = explode('.', $sReal, 2); // get the intval
			}
					$sResult .= $sChars[$sFrac];
					$sReal = bcsub($sReal, $sFrac, $iScale);
			}
		}
		return $sResult;
}
//---------- begin function tinyBase2Dec
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function tinyBase2Dec($sNum, $iBase=0, $iScale=0) {
	$sChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$sResult = '';
	$iBase = intval($iBase); // incase it is a string or some weird decimal
	// special case for Base64 encoding
	if ($iBase == 64){
			$sChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	}
		//clean up the input string if it uses particular input formats
		switch ($iBase) {
			case 16: // remove 0x from start of string
					if (strtolower(substr($sNum, 0, 2)) == '0x'){ $sNum = substr($sNum, 2);}
				break;
			case 8: // remove the 0 from the start if it exists - not really required
					if (strpos($sNum, '0')===0){ $sNum = substr($sNum, 1);}
				break;
			case 2: // remove an 0b from the start if it exists
					if (strtolower(substr($sNum, 0, 2)) == '0b'){ $sNum = substr($sNum, 2);}
				break;
			case 64: // remove padding chars: =
					$sNum = str_replace('=', '', $sNum);
				break;
			default: // Look for numbers in the format base#number,
						 // if so split it up and use the base from it
					if (strpos($sNum, '#') !== false) {
						list ($sBase, $sNum) = explode('#', $sNum, 2);
						$iBase = intval($sBase);  // take the new base
					}
					if ($iBase == 0) {
						return "tinyBase2Dec called without a base value and not in base#number format";
					}
			break;
		}
	// Convert string to upper case since base36 or less is case insensitive
		if ($iBase < 37){ $sNum = strtoupper($sNum);}
	// Check to see if we are an integer or real number
	if (strpos($sNum, '.') !== FALSE) {
			list ($sNum, $sReal) = explode('.', $sNum, 2);
			$sReal = '0.' . $sReal;
		} 
	else{
			$sReal = '0';
	}
		// By now we know we have a correct base and number
		$iLen = strlen($sNum);
		// Now loop through each digit in the number
		for ($i=$iLen-1; $i>=0; $i--) {
			$sChar = $sNum[$i]; // extract the last char from the number
			$iValue = strpos($sChars, $sChar); // get the decimal value
			if ($iValue > $iBase) {
					return "tinyBase2Dec: {$sNum} is not a valid base {$iBase} number";
			}
			// Now convert the value+position to decimal
			$sResult = bcadd($sResult, bcmul( $iValue, bcpow($iBase, ($iLen-$i-1))) );
		}
	// Now append the real part
	if (strcmp($sReal, '0') != 0) {
			$sReal = substr($sReal, 2); // Chop off the '0.' characters
			$iLen = strlen($sReal);
			for ($i=0; $i<$iLen; $i++) {
					$sChar = $sReal[$i]; // extract the first, second, third, etc char
					$iValue = strpos($sChars, $sChar); // get the decimal value
					if ($iValue > $iBase) {
						return "tinyBase2Dec: {$sNum} is not a valid base {$iBase} number";
					}
					$sResult = bcadd($sResult, bcdiv($iValue, bcpow($iBase, ($i+1)), $iScale), $iScale);
			}
	}
		return $sResult;
}
//$tiny = tinyBase2Base(554512, 10, 62); and that evaluates to $tiny = '2KFk'.
//---------- begin function tinyBase2Base
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function tinyBase2Base($iNum, $iBase, $oBase, $iScale=0) {
	if ($iBase != 10){
		$oNum = tinyBase2Dec($iNum, $iBase, $iScale);
	}
		else{
		$oNum = $iNum;
	}
		return tinyDec2Base($oNum, $oBase, $iScale);
}

?>