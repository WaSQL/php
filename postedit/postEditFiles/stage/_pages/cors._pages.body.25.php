<?php
$file="{$_SERVER['DOCUMENT_ROOT']}/".implode('/',$_REQUEST['passthru']);
switch(strtolower(getFileExtension($file))){
	case 'png':$ctype="image/png";break;
	case "pdf": $ctype="application/pdf"; break;
	case "exe": $ctype="application/octet-stream"; break;
	case "zip": $ctype="application/zip"; break;
	case "doc": $ctype="application/msword"; break;
	case "xls": $ctype="application/vnd.ms-excel"; break;
	case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
	case "gif": $ctype="image/gif"; break;
	case "png": $ctype="image/png"; break;
	case "jpg": $ctype="image/jpg"; break;
	case "mp3": $ctype="audio/mpeg"; break;
	default: $ctype="application/force-download";break;
}
//set headers
if(isset($_REQUEST['caption'])){
	$ps=isNum($_REQUEST['ps'])?$_REQUEST['ps']:40;
	$_REQUEST['caption']=strtoupper($_REQUEST['caption']);
	$filename=getFileName($file);
	$path=getFilePath($file);
 	unlink("{$path}/tmp_{$filename}");
	$cmd="convert '{$file}' -gravity south -stroke '#000C' -strokewidth 1 -pointsize {$ps} -annotate 0 '{$_REQUEST['caption']}'  '{$path}/tmp_{$filename}'";
	$p=cmdResults($cmd);
	if(is_file("{$path}/tmp_{$filename}")){
    	$file="{$path}/tmp_{$filename}";
	}
}
//set CORS
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Connection: close");
header("Content-Type: {$ctype}");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".filesize($file));
//push the file
readfile($file);
?>
