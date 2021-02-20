<?php
//minify the js files in wfiles/js. updated July 2019
/* first, lets determine the current page, template, domain, and user */
error_reporting(E_ALL & ~E_NOTICE);
$progpath=dirname(__FILE__);
$slash=DIRECTORY_SEPARATOR;
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("{$progpath}/common.php");
global $CONFIG;
if(isset($CONFIG['allow_origin']) && strlen($CONFIG['allow_origin'])){
	switch(strtolower($CONFIG['allow_origin'])){
		case '*':
		case 'all':
			$CONFIG['allow_origin']='*';
		break;
	}
	@header("Access-Control-Allow-Origin: {$CONFIG['allow_origin']}");
}
if(isset($CONFIG['allow_methods']) && strlen($CONFIG['allow_methods'])){
	@header("Access-Control-Allow-Methods: {$CONFIG['allow_methods']}");
}
if(isset($CONFIG['allow_headers']) && strlen($CONFIG['allow_headers'])){
	@header("Access-Control-Allow-Headers: {$CONFIG['allow_headers']}");
}
if(isset($CONFIG['allow_credentials'])){
	@header('Access-Control-Allow-Credentials:true');
}
if(!isset($CONFIG['allow_frames']) || !$CONFIG['allow_frames']){
	@header('X-Frame-Options: SAMEORIGIN');
}
else{
	//Allowing all domains is the default. Don't set the X-Frame-Options header at all if you want that.
	//@header('X-Frame-Options: ALLOWALL');
}
if(!isset($CONFIG['xss_protection']) || !$CONFIG['xss_protection']){
	@header('X-XSS-Protection: 1; mode=block');
}
include_once("{$progpath}/config.php");
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("{$progpath}/wasql.php");
include_once("{$progpath}/database.php");
//parse SERVER vars to get additional SERVER params
parseEnv();
$guid=getGUID();
if(!isset($_REQUEST['_minify_'])){
	header('Content-type: application/javascript; charset=UTF-8');
	echo '/*missing _minify_ request*/';
	exit;
}
global $filename;
$docroot=$_SERVER['DOCUMENT_ROOT'];
list($prefix,$hash)=preg_split('/\_/',$_REQUEST['_minify_'],2);
$afile="{$docroot}/w_min/{$hash}_js.json";
$filename="minify_{$_REQUEST['_minify_']}.js";
if(!file_exists($afile)){
	header('Content-type: application/javascript; charset=UTF-8');
	echo '/*missing _minify_ json file*/';
	exit;
}
$jstr=getFileContents($afile);
$minify=json_decode($jstr,true);
if(!is_array($minify)){
	header('Content-type: application/javascript; charset=UTF-8');
	echo '/*invalid _minify_ request*/';
	exit;
}
if($_REQUEST['debug']==1){
	header('Content-type: text/plain; charset=UTF-8');
	echo printValue($minify);
	exit;
}
if(!isset($minify['extras'])){$minify['extras']=array();}
if(!isset($minify['jsfiles'])){$minify['jsfiles']=array();}
if(!isset($minify['includepages'])){$minify['includepages']=array();}
//set proper javascript content-type header
header('Content-type: application/javascript; charset=UTF-8');
//enable caching of responses for IE8 and others even on https
header("Pragma: public");
//make sure caching is turned on
header('Cache-Control: public, must-revalidate');
//set expires to expire in 24 hours in the past - otherwise the browser will not even ask for it again until it expires
$expires = 60*60*24;
header('Expires: ' . gmdate('D, d M Y H:i:s', time()-$expires) . ' GMT');
global $lastmodifiedtime;
//get the HTTP_IF_MODIFIED_SINCE header if set
global $ifModifiedSince;
$ifModifiedSince=isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:'';
//get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
global $etagHeader;
$etagHeader=isset($_SERVER['HTTP_IF_NONE_MATCH'])?trim($_SERVER['HTTP_IF_NONE_MATCH']):'NOTAG';
//start
ob_start("compress");
//get the js path
$jspath=realpath("{$progpath}/../wfiles/js");
//initialize the global files array
global $files;
$files=array();
//load basic js files
minifyFiles($jspath,array('common','event','form','colorpicker'));
minifyFiles(realpath("{$jspath}/extras"),array('pikaday'));
//Get any extras
foreach($minify['extras'] as $extra){
	minifyFiles(realpath("{$jspath}/extras"),$extra);
}
//get any jsfiles
foreach($minify['jsfiles'] as $file){
   	if(!in_array($file,$files)){$files[]=$file;}
}
//include files and set the lastmodifiedtime of any file
global $jslines;
global $pre_jslines;
$jslines=array();
$loaded=array();
$pre_jslines=array();
foreach($files as $file){
	if(preg_match('/^http/i',$file)){
     	//remote file
     	$evalstr="return minifyGetExternal('{$file}');";
		$content = getStoredValue($evalstr,0,24);
		minifyLines($content);
		continue;
	}
	if(!is_file($file)){
		echo "/* Minify_js Error: NO SUCH FILE:{$file} */".PHP_EOL.PHP_EOL;
		continue;
	}
	$lines=file($file);
	//$cnt=count($lines);
	//echo "/* Minify_js File:{$file}  LineCnt:{$cnt} */".PHP_EOL.PHP_EOL;
	if(is_array($lines)){
		$fname=getFileName($file);
		$conditionals=1;
		if(stringContains($file,'extras') && !stringContains($fname,'extras')){
			$fname="extras/{$fname}";
		}
		if(stringEndsWith($file,'.min.js')){
			$conditionals=0;
		}
		$jslines[]= PHP_EOL."/* BEGIN {$fname} */".PHP_EOL;
		$loaded[]=$fname;
    	minifyLines($lines,$conditionals);
	}
	else{
		echo "/* Minify_js Error: NO FILE LINES:{$file} */".PHP_EOL.PHP_EOL;
	}
}
//load the template, includepages, and page
$field=$minify['min']==1?'js_min':'js';
$field2=$minify['min']==1?'js':'js_min';
//_templates
if(isNum($minify['tid']) && $minify['tid'] > 0){
	$rec=getDBRecord(array('-table'=>'_templates','_id'=>$minify['tid'],'-fields'=>'_id,name,js,js_min'));
	if(isset($rec['_id'])){
		$content=evalPHP($rec[$field]);
		if(strlen(trim($content)) > 10){
			$jslines[] = PHP_EOL."/* BEGIN _templates for {$rec['_id']}->{$rec['name']}->{$field} */".PHP_EOL;
			$loaded[]="_templates {$rec['_id']}->{$rec['name']}->{$field}";
			minifyLines($content);
		}
		else{
			$content=evalPHP($rec[$field2]);
			if(strlen(trim($content)) > 10){
				$jslines[] = PHP_EOL."/* BEGIN _templates for {$rec['_id']}->{$rec['name']}->{$field2} */".PHP_EOL;
				$loaded[]="_templates {$rec['_id']}->{$rec['name']}->{$field2}";
				minifyLines($content);
			}
		}
	}
	else{
		$jslines[] = PHP_EOL."/* ERROR retrieving _templates record for id {$minify['tid']} */".PHP_EOL;
	}
	
}
//_pages
if(isNum($minify['pid']) && $minify['pid'] > 0){
	$rec=getDBRecord(array('-table'=>'_pages','_id'=>$minify['pid'],'-fields'=>'_id,name,js,js_min'));
	if(isset($rec['_id'])){
		$content=evalPHP($rec[$field]);
		if(strlen(trim($content)) > 10){
			$jslines[] = PHP_EOL."/* BEGIN _pages for {$rec['_id']}->{$rec['name']}->{$field} */".PHP_EOL;
			$loaded[]="_pages {$rec['_id']}->{$rec['name']}->{$field}";
			minifyLines($content);
		}
		else{
			$content=evalPHP($rec[$field2]);
			if(strlen(trim($content)) > 10){
				$jslines[] = PHP_EOL."/* BEGIN _pages for {$rec['_id']}->{$rec['name']}->{$field2} */".PHP_EOL;
				$loaded[]="_pages {$rec['_id']}->{$rec['name']}->{$field2}";
				minifyLines($content);
			}
		}
	}
	else{
		$jslines[] = PHP_EOL."/* ERROR retrieving _pages record for id {$minify['pid']} */".PHP_EOL;
	}
}
//includepages
if(is_array($minify['includepages'])){
	foreach($minify['includepages'] as $id){
		$rec=getDBRecord(array('-table'=>'_pages','_id'=>$id,'-fields'=>"_id,name,js,js_min"));
		if(isset($rec['_id'])){
			$content=evalPHP($rec[$field]);
			if(strlen(trim($content)) > 10){
				$jslines[] = PHP_EOL."/* BEGIN includepages for {$rec['_id']}->{$rec['name']}->{$field} */".PHP_EOL;
				$loaded[]="includepages for {$rec['_id']}->{$rec['name']}->{$field}";
				minifyLines($content);
			}
			else{
				$content=evalPHP($rec[$field2]);
				if(strlen(trim($content)) > 10){
					$jslines[] = PHP_EOL."/* BEGIN includepages for {$rec['_id']}->{$rec['name']}->{$field} */".PHP_EOL;
					$loaded[]="includepages {$rec['_id']}->{$rec['name']}->{$field2}";
					minifyLines($content);
				}
			}
		}
		else{
			$jslines[] = PHP_EOL."/* ERROR retrieving _pages record for id {$id} */".PHP_EOL;
		}
	}
}
if(strlen($filename)){
	$docroot=$_SERVER['DOCUMENT_ROOT'];
	if(!is_dir("{$docroot}/w_min")){
		buildDir("{$docroot}/w_min");
	}
	$afile="{$docroot}/w_min/{$filename}";
	$data='';
	if(count($pre_jslines)){
		$d="/* Begin Imports - must be at the top */".PHP_EOL;
		echo $d;
		$data.=$d;
		$d=implode(PHP_EOL,$pre_jslines);
		echo $d;
		$data.=$d;
	}
	$d=implode(PHP_EOL,$jslines);
	echo $d;
	$data.=$d;
	ob_end_flush();
	setFileContents($afile,$data);
}
else{
	if(count($pre_jslines)){
		$d="/* Begin Imports - must be at the top */".PHP_EOL;
		echo $d;
		$d=implode(PHP_EOL,$pre_jslines);
		echo $d;
	}
	$d=implode(PHP_EOL,$jslines);
	echo $d;
	ob_end_flush();
}

exit;
/* ------------ Functions needed ---------------- */
function minifyFiles($path,$names){
	global $files;
	global $CONFIG;
	if(!is_array($names)){$names=array($names);}
	//echo $path.implode(',',$names).PHP_EOL;return;
	foreach($names as $name){
		//automatically create minified versions if they do not exist - localhost only
		if(preg_match('/^http/i',$name)){
	     	//remote file - expire every week
	     	$evalstr="return minifyGetExternal('{$name}');";
			echo getStoredValue($evalstr,0,168);
			continue;
		}
		if($CONFIG['minify_js'] && is_file("{$path}/{$name}.min.js")){
			$file=realpath("{$path}/{$name}.min.js");
			if(!in_array($file,$files)){$files[]=$file;}
		}
		elseif($CONFIG['minify_js'] && is_file("{$path}/{$name}/{$name}.min.js")){
			$file=realpath("{$path}/{$name}/{$name}.min.js");
			if(!in_array($file,$files)){$files[]=$file;}
		}
		elseif($CONFIG['minify_js'] && is_file("{$path}/{$name}/{$name}.min.js")){
			$file=realpath("{$path}/{$name}/{$name}.min.js");
			if(!in_array($file,$files)){$files[]=$file;}
		}
		elseif(is_file("{$path}/{$name}.js")){
	    	$file=realpath("{$path}/{$name}.js");
			if(!in_array($file,$files)){$files[]=$file;}
		}
		elseif(is_file("{$path}/{$name}/{$name}.js")){
	    	$file=realpath("{$path}/{$name}/{$name}.js");
			if(!in_array($file,$files)){$files[]=$file;}
		}
		else{echo "/* Minify_js Error: NO SUCH NAME:{$name}, Path:{$path} */".PHP_EOL.PHP_EOL;}
	}
}

function minifyGetExternal($url){
	$lines=file($url);
	$rtn='';
	if(is_array($lines)){
		$size=filesize(realpath($file));
		//$rtn .= "/* BEGIN {$url} ({$size} bytes) */".PHP_EOL;
    	$rtn .=  minifyLines($lines);
    	//$rtn .= "\r\n\r\n";
	}
	return $rtn;
}
function compress($buffer) {
	/*set an etag based on the combined file data */
	global $lastmodifiedtime;
	global $filename;
	global $etagHeader;
	global $ifModifiedSince;
	$etag=sha1($buffer);
	header('Last-Modified: '.gmdate("D, d M Y H:i:s", $lastmodifiedtime).' GMT');
	header("Etag: {$etag}");
	//check if page has changed. If not, send 304 and exit
	if(strlen($ifModifiedSince)){$modtime=strtotime($ifModifiedSince);}
	else{$modtime=0;}
	//return "{$modtime}=={$lastmodifiedtime} && {$etagHeader} == {$etag}";
	if($modtime==$lastmodifiedtime && $etagHeader == $etag){
    	header("HTTP/1.1 304 Not Modified");
    	return;
		}
	header("Accept-Ranges: bytes");
	header("Content-Length: ".strlen($buffer));
	header('Content-Disposition: inline; filename="'.$filename.'"');
	return $buffer;
}
//--------------------
function minifyLines($lines) {
	global $jslines;
	if(!is_array($lines)){
		$lines=preg_split('/[\r\n]+/',$lines);
	}
	$rtn='';
	foreach($lines as $line){
		$tline=trim($line);
     	if(!strlen($tline)){continue;}
     	//ignore comments
     	if(strpos($tline,"//") === 0){continue;}
     	//if(strpos($tline,"/*") === 0 && strpos(strrev($tline),"/*") === 0){continue;}
		$jslines[]=rtrim($line);
	}
}
