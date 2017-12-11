<?php
/*
	ffmpeg functions
	References:
		https://github.com/amiaopensource/ffmpeg-amia-wiki/wiki/4)-Command-line-FFmpeg-commands-and-explanations
		https://www.labnol.org/internet/useful-ffmpeg-commands/28490/
		* https://www.catswhocode.com/blog/19-ffmpeg-commands-for-all-needs


*/
$progpath=dirname(__FILE__);
//---------- begin function imagemagickImage2ico--------------------
/**
* @describe converts and image to an ico file
* 	@params $infile string - $file to convert
*	@params [$outfile} string - $filename of image to convert.  Defaults to infile path and name with .ico extension
*/
function ffmpeg2Mp4($infile,$outfile=''){
	//ffmpeg -i in.mp4 -profile:v baseline -crf 21 -c:a copy out.mp4
	if(!strlen($outfile)){
    	$outfile=preg_replace('/\.[a-z0-9]+$/i','.mp4',$infile);
	}
	$cmd="ffmpeg -i '{$infile}' -profile:v baseline -crf 21 -c:a aac '{$outfile}'";
	return cmdResults($cmd);
}


?>
