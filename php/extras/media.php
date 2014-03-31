<?php
/*
	references:
		http://www.catswhocode.com/blog/19-ffmpeg-commands-for-all-needs

*/
//---mediaPoster---------------------------------------
/**
* uses imagemagic to convert an image into a poster
* @param src string
*	<p>absolute path to image to convert</p>
* @param dest string
*	<p>absolute path to dest image to create</p>
* @param params array
*	<p>params to override.  Options are: title, subtitle, title_color, subtitle_color, bordercolor, bordercolor2, resize</p>
* @return array
*	<p>returns an array of the commands taken to convert image to dest image</p>
* 	<p>@usage $out=mediaPoster($src,$dest,$params);</p>
*/
function mediaPoster($src,$dest,$params=array()){
	if(!isset($params['title'])){$params['title']='';}
	if(!isset($params['subtitle'])){$params['subtitle']='';}
	if(!isset($params['title_color'])){$params['title_color']='white';}
	if(!isset($params['subtitle_color'])){$params['subtitle_color']='white';}
	if(!isset($params['bordercolor'])){$params['bordercolor']='black';}
	if(!isset($params['bordercolor2'])){$params['bordercolor2']='white';}
	$progpath=dirname(__FILE__);
	$tmpname=sha1($src);
	$cmds=array(
		"convert \"{$src}\"  -bordercolor {$params['bordercolor']} -border 3x3 \"{$progpath}/temp/{$tmpname}1.ppm\"", 							// add small bgcolor1 border
		"convert \"{$progpath}/temp/{$tmpname}1.ppm\"  -bordercolor {$params['bordercolor2']} -border 2x2 \"{$progpath}/temp/{$tmpname}2.ppm\"",		// add bgcolor2 line around the image
		"convert \"{$progpath}/temp/{$tmpname}2.ppm\"  -bordercolor {$params['bordercolor']} -border 5%x5% \"{$progpath}/temp/{$tmpname}3.ppm\"",	//add a wide bgcolor1 border around
		"convert \"{$progpath}/temp/{$tmpname}3.ppm\" -size 40x40 xc:{$params['bordercolor']} -background {$params['bordercolor']} -append -pointsize 40 -fill {$params['title_color']} -draw \"gravity South text 0,10 '{$params['title']}'\" \"{$progpath}/temp/{$tmpname}4.ppm\"",
		"convert \"{$progpath}/temp/{$tmpname}4.ppm\" -size 30x30 xc:{$params['bordercolor']} -background {$params['bordercolor']} -append -pointsize 25 -fill {$params['subtitle_color']} -draw \"gravity South text 0,10 '{$params['subtitle']}'\" \"{$dest}\""
	);
	if(isset($params['resize'])){
    	$cmds[]="convert -thumbnail {$params['resize']} \"{$dest}\" \"{$dest}\"";
	}
	$out=array();
	foreach($cmds as $cmd){
		if(isWindows()){$cmd=str_replace("/","\\",$cmd);}
    	$out[]=cmdResults($cmd);
	}
	//remove temp files
	for($x=1;$x<5;$x++){
    	unlink("{$progpath}/temp/{$tmpname}{$x}.ppm");
	}
	return $out;
}
//---mediaSound---------------------------------------
/**
* uses ffmpeg to convert an src sound into dest sound
* @param src string
*	<p>absolute path to sound file to convert</p>
* @param dest string
*	<p>absolute path to dest file to create</p>
* @param params array
*	<p>optional parameters</p>
* @return array
*	<p>returns an array of the commands taken to convert src file to dest file</p>
* 	<p>@usage $out=mediaSound($src,$dest,$params);</p>
*/
function mediaSound($src,$dest,$params=array()){
	if(!isset($params['title'])){$params['title']='';}
	if(!isset($params['subtitle'])){$params['subtitle']='';}
	if(!isset($params['title_color'])){$params['title_color']='white';}
	if(!isset($params['subtitle_color'])){$params['subtitle_color']='white';}
	if(!isset($params['bordercolor'])){$params['bordercolor']='black';}
	if(!isset($params['bordercolor2'])){$params['bordercolor2']='white';}
	$progpath=dirname(__FILE__);
	$src_ext=getFileExtension($src);
	$dest_ext=getFileExtension($dest);
	$cmds=array();
	switch(strtolower($dest_ext)){
		case 'mp3':
			$cmd[]="ffmpeg -i \"{$src}\" -vn -ar 44100 -ac 2 -ab 192 -f mp3 \"$dest\"";
			break;
	}
	$out=array();
	foreach($cmds as $cmd){
		if(isWindows()){$cmd=str_replace("/","\\",$cmd);}
    	$out[]=cmdResults($cmd);
	}
	return $out;
}

?>