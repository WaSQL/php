<?php

function pageReportImageSmall($report,$request,$response){
	header("Access-Control-Allow-Origin: *");
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
	header("Connection: close");
	header("Content-Transfer-Encoding: binary");
	header('Content-type: image/png');

	// Create Image From Existing File
	$image = imagecreatefrompng("/var/www/skillsai.com/images/reports/{$report}-small-blank.png");
	imagealphablending($image, FALSE);
	imagesavealpha($image, TRUE);
	// Allocate A Color For The Text
	$orange = imagecolorallocate($image, 255,153,0);
  	$blue = imagecolorallocate($image, 1,171,180);
  	$black = imagecolorallocate($image, 0,0,0);
  	// Set Path to Font File
  	$font_path = '/var/www/skillsai.com/fonts/montserrat-regular-webfont.ttf';
  	// REQUEST: imagettftext(image,size,angle,x,y,font,text);
  	imagettftext($image, 20, 0, 300, 200, $blue, $font_path, 'Request:');
  	//first line can only be 450px wide, second and third lines can be 643pc wide
  	$parts=splitWords($request,20);
  	$first=array_shift($parts);
  	$request=implode(' ',$parts);
  	//echo $request.printValue($parts);exit;
  	imagettftext($image, 20, 0, 430, 200, $black, $font_path, $first);
  	$parts=splitWords($request,30);
  	//echo $first.printValue($parts);exit;
  	$y=240;
  	foreach($parts as $part){
  		imagettftext($image, 20, 0, 300, $y, $black, $font_path, $part);
  		$y+=40;
	}
	//RESPONSE
  	imagettftext($image, 20, 0, 300, 320, $orange, $font_path, 'Response:');
  	$parts=splitWords($response,19);
  	$first=array_shift($parts);
  	$response=implode(' ',$parts);
  	imagettftext($image, 20, 0, 450, 320, $black, $font_path, $first);
  	$parts=splitWords($response,30);
  	$y=360;
  	foreach($parts as $part){
  		imagettftext($image, 20, 0, 300, $y, $black, $font_path, $part);
  		$y+=40;
	}
  	// Send Image to Browser
  	imagepng($image);
  	// Clear Memory
  	imagedestroy($image);
  	exit;
}
function pageReportImageLarge($report,$request,$response){
	header("Access-Control-Allow-Origin: *");
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
	header("Connection: close");
	header("Content-Transfer-Encoding: binary");
	header('Content-type: image/png');
	// Create Image From Existing File
	$image = imagecreatefrompng("/var/www/skillsai.com/images/reports/{$report}-large-blank.png");
	imagealphablending($image, FALSE);
	imagesavealpha($image, TRUE);
	// Allocate A Color For The Text
	$orange = imagecolorallocate($image, 255,153,0);
  	$blue = imagecolorallocate($image, 1,171,180);
  	$black = imagecolorallocate($image, 0,0,0);
  	// Set Path to Font File
  	$font_path = '/var/www/skillsai.com/fonts/montserrat-regular-webfont.ttf';
  	// REQUEST: imagettftext(image,size,angle,x,y,font,text);
  	imagettftext($image, 30, 0, 500, 320, $blue, $font_path, 'Request:');
  	//first line can only be 450px wide, second and third lines can be 643pc wide
  	$parts=splitWords($request,22);
  	$first=array_shift($parts);
  	$request=implode(' ',$parts);
  	imagettftext($image, 30, 0, 700, 320, $black, $font_path, $first);
  	$parts=splitWords($request,31);
  	$y=370;
  	foreach($parts as $part){
  		imagettftext($image, 30, 0, 500, $y, $black, $font_path, $part);
  		$y+=50;
	}
	//RESPONSE
  	imagettftext($image, 30, 0, 500, 500, $orange, $font_path, 'Response:');
  	$parts=splitWords($response,21);
  	$first=array_shift($parts);
  	$response=implode(' ',$parts);
  	imagettftext($image, 30, 0, 720, 500, $black, $font_path, $first);
  	$parts=splitWords($response,31);
  	$y=550;
  	foreach($parts as $part){
  		imagettftext($image, 30, 0, 500, $y, $black, $font_path, $part);
  		$y+=50;
	}
  	// Send Image to Browser
  	imagepng($image);
  	// Clear Memory
  	imagedestroy($image);
  	exit;
}
function pageGetUserReportsByTab() {
  global $alexa;
  $query=<<<ENDOFSQL
  SELECT
      r.name
      ,r.card_image
      ,r.data
      ,r.url
      ,rt.data_table
      ,rt.defaults
    FROM
      reports r, report_tabs rt, user_tabs ut
    WHERE
      r._id=rt.report_id
      and rt.user_tab_id=ut._id
      and soundex(ut.name)=soundex('{$alexa['swim']['report']}')
      and ut.user_id={$alexa['user']['_id']}
ENDOFSQL;
  $recs = getDBRecords($query);
  if(isset($recs[0])) {
    return $recs;
  }
  return array();
}

function pageGetUserReportByName() {
  global $alexa;
/*   $query=<<<ENDOFSQL
  SELECT
      r.name
      ,r.card_image
      ,r.data
    FROM
      reports r, user_reports ur
    WHERE
      r._id=ur.report_id
      and soundex(r.name)=soundex('{$alexa['swim']['report']}')
      and ur.user_id={$alexa['user']['_id']}
ENDOFSQL; */
	$query=<<<ENDOFSQL
  SELECT
      r.name
      ,r.card_image
      ,r.data
      ,r.url
    FROM
      reports r
    WHERE
      soundex(r.name)=soundex('{$alexa['swim']['report']}')
ENDOFSQL;
	//alexaSetUserVar('debug',$query);
  $recs = getDBRecords($query);
  if(isset($recs[0])) {
    return array($recs[0]);
  }
  return array();
}
function pageLoadReportData($xrec){
	return commonLoadReportData($xrec);
}
?>
