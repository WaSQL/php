<?php
/* 
	ImageMagick functions
	References:
		http://www.imagemagick.org/discourse-server/viewtopic.php?t=26252
		Convert image to multi-sized ico
			convert logo.png -define icon:auto-resize=64,48,32,16 favicon.ico


*/
$progpath=dirname(__FILE__);
//---------- begin function imagemagickImage2ico--------------------
/**
* @describe converts and image to an ico file
* 	@params $infile string - $file to convert
*	@params [$outfile} string - $filename of image to convert.  Defaults to infile path and name with .ico extension
*/
function imagemagickImage2ico($infile,$outfile=''){
	if(!strlen($outfile)){
    	$outfile=preg_replace('/\.[a-z0-9]+$/i','.ico',$infile);
	}
	$cmd="convert '{$infile}' -define icon:auto-resize=64,48,32,16 '{$outfile}'";
	return cmdResults($cmd);
}
//---------- begin function imagemagickImageResize--------------------
/**
* @describe resizes and image
* 	@params $infile string - $file to convert
*	@params $size string - {Width}x{Height}
*	@params [$outfile} string -  Defaults to infile path and name with _{size}  logo_32x32.png
*/
function imagemagickImageResize($infile,$size,$outfile=''){
	if(!strlen($outfile)){
    	$outfile=preg_replace('/(\.[a-z0-9]+)$/i','_'.$size.'\1',$infile);
	}
	$cmd="convert '{$infile}' -quality 100 -resize {$size} '{$outfile}'";
	return cmdResults($cmd);
}

?>
