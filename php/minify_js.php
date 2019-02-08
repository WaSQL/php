<?php
//minify the js files in wfiles/js
/* first, lets determine the current page, template, domain, and user */
error_reporting(E_ALL & ~E_NOTICE);
$progpath=dirname(__FILE__);
$slash=DIRECTORY_SEPARATOR;
//set the default time zone
date_default_timezone_set('America/Denver');
//includes
include_once("{$progpath}/common.php");
global $CONFIG;
include_once("{$progpath}/config.php");
if(isset($CONFIG['timezone'])){
	@date_default_timezone_set($CONFIG['timezone']);
}
include_once("{$progpath}/wasql.php");
include_once("{$progpath}/database.php");
include_once("{$progpath}/sessions.php");
$session_id=session_id();
//parse SERVER vars to get additional SERVER params
parseEnv();
$guid=getGUID();
foreach($_REQUEST as $key=>$val){
	$minify_string=$key;
	break;
}
if(!is_array($_SESSION['w_MINIFY']['extras_js'])){
	$_SESSION['w_MINIFY']['extras_js']=array();
}
//check for framework:  bootstrap, materialize, foundation are supported
//echo $minify_string.printValue($_SERVER);exit;
global $filename;
$filename=$_SESSION['w_MINIFY']['js_filename'];
if($_REQUEST['debug']==1){
	header('Content-type: text/plain; charset=UTF-8');
	foreach($_SESSION['w_MINIFY'] as $key=>$val){
		if(!is_array($val) && strlen($val) > 300){continue;}
		$debug[$key]=$val;
	}
	echo '$_SESSION[w_MINIFY] Values:'.printValue($debug);
	exit;
}
else{
	header('Content-type: text/javascript; charset=UTF-8');
}
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
minifyFiles($jspath,array('common','event','form','calendar','colorpicker'));
//echo printValue($parts).printValue($_REQUEST).printValue($_SESSION['w_MINIFY']);exit;
//Get any extras
if(isset($_SESSION['w_MINIFY']['extras_js']) && is_array($_SESSION['w_MINIFY']['extras_js'])){
	foreach($_SESSION['w_MINIFY']['extras_js'] as $extra){
		minifyFiles(realpath("{$jspath}/extras"),$extra);
	}
}
if(isset($_SESSION['w_MINIFY']['jsfiles']) && is_array($_SESSION['w_MINIFY']['jsfiles'])){
	foreach($_SESSION['w_MINIFY']['jsfiles'] as $file){
    	if(!in_array($file,$files)){$files[]=$file;}
	}
}
//echo printValue($files);exit;
//include files and set the lastmodifiedtime of any file
global $jslines;
global $pre_jslines;
$jslines=array();
$loaded=array();
$pre_jslines=array();
foreach($files as $file){
	if($_REQUEST['debug']==1){
		$jslines[]= "{$file}<br>\r\n";
		continue;
	}
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
		$jslines[]= "\r\n/* BEGIN {$fname} */\r\n";
		$loaded[]=$fname;
    	minifyLines($lines,$conditionals);
	}
	else{
		echo "/* Minify_js Error: NO FILE LINES:{$file} */".PHP_EOL.PHP_EOL;
	}
}
//load the template, includepages, and page
$field=$CONFIG['minify_js']?'js_min':'js';
$field2=$CONFIG['minify_js']?'js':'js_min';
//_templates
if(isNum($_SESSION['w_MINIFY']['template_id']) && $_SESSION['w_MINIFY']['template_id'] > 0){
	$rec=getDBRecord(array('-table'=>'_templates','_id'=>$_SESSION['w_MINIFY']['template_id'],'-fields'=>$field));
	$content=evalPHP($rec[$field]);
	if(strlen(trim($content)) > 10){
		$jslines[] = "\r\n/* BEGIN _templates {$field} */\r\n";
		$loaded[]="_templates {$field}";
		minifyLines($content);
	}
	else{
		$rec=getDBRecord(array('-table'=>'_templates','_id'=>$_SESSION['w_MINIFY']['template_id'],'-fields'=>$field2));
		$content=evalPHP($rec[$field2]);
		if(strlen(trim($content)) > 10){
			$jslines[] = "\r\n/* BEGIN _templates {$field2} */\r\n";
			$loaded[]="_templates {$field2}";
			minifyLines($content);
		}
	}
}
//_pages
if(isNum($_SESSION['w_MINIFY']['page_id']) && $_SESSION['w_MINIFY']['page_id'] > 0){
	$rec=getDBRecord(array('-table'=>'_pages','_id'=>$_SESSION['w_MINIFY']['page_id'],'-fields'=>$field));
	$content=evalPHP($rec[$field]);
	if(strlen(trim($content)) > 10){
		$jslines[] = "\r\n/* BEGIN _pages {$field} */\r\n";
		$loaded[]="_pages {$field}";
		minifyLines($content);
	}
	else{
		$rec=getDBRecord(array('-table'=>'_pages','_id'=>$_SESSION['w_MINIFY']['page_id'],'-fields'=>$field2));
		$content=evalPHP($rec[$field2]);
		if(strlen(trim($content)) > 10){
			$jslines[] = "\r\n/* BEGIN _pages {$field2} */\r\n";
			$loaded[]="_pages {$field2}";
			minifyLines($content);
		}
	}
}
//includepages
if(is_array($_SESSION['w_MINIFY']['includepages'])){
	foreach($_SESSION['w_MINIFY']['includepages'] as $id){
		$rec=getDBRecord(array('-table'=>'_pages','_id'=>$id,'-fields'=>"name,{$field}"));
		$content=evalPHP($rec[$field]);
		if(strlen(trim($content)) > 10){
			$jslines[] = "\r\n/* BEGIN includepages {$field} for {$rec['name']} page */\r\n";
			$loaded[]="includepages {$field} for {$rec['name']} page";
			minifyLines($content);
		}
		else{
			$rec=getDBRecord(array('-table'=>'_pages','_id'=>$id,'-fields'=>"name,{$field2}"));
			$content=evalPHP($rec[$field2]);
			if(strlen(trim($content)) > 10){
				$jslines[] = "\r\n/* BEGIN includepages {$field2} for {$rec['name']} page */\r\n";
				$loaded[]="includepages {$field} for {$rec['name']} page";
				minifyLines($content);
			}
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
		$d="/* Begin Imports - must be at the top */\r\n";
		echo $d;
		$data.=$d;
		$d=implode("\r\n",$pre_jslines);
		echo $d;
		$data.=$d;
	}
	$d=implode("\r\n",$jslines);
	echo $d;
	$data.=$d;
	ob_end_flush();
	setFileContents($afile,$data);
}
else{
	if(count($pre_jslines)){
		$d="/* Begin Imports - must be at the top */\r\n";
		echo $d;
		$d=implode("\r\n",$pre_jslines);
		echo $d;
	}
	$d=implode("\r\n",$jslines);
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
		if($_SERVER['UNIQUE_HOST']=='localhost' && !stringEndsWith($name,'.min') && !is_file("{$path}/{$name}.min.js") && is_file("{$path}/{$name}.js")){
			$code=getFileContents("{$path}/{$name}.js");
			$mcode=minifyCode($code,'js');
			setFileContents("{$path}/{$name}.min.js",$mcode);
		}
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
		elseif(is_file("{$path}/{$name}.js")){
	    	$file=realpath("{$path}/{$name}.js");
			if(!in_array($file,$files)){$files[]=$file;}
		}
		else{echo "/* Minify_js Error: NO SUCH NAME:{$name} */".PHP_EOL.PHP_EOL;}
	}
}

function minifyGetExternal($url){
	$lines=file($url);
	$rtn='';
	if(is_array($lines)){
		$size=filesize(realpath($file));
		//$rtn .= "/* BEGIN {$url} ({$size} bytes) */\r\n";
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
