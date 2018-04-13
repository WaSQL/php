<?php
//minify the css files in wfiles/css
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
if(!is_array($_SESSION['w_MINIFY']['extras_css'])){
	$_SESSION['w_MINIFY']['extras_css']=array();
}
//check for framework:  bootstrap, materialize, foundation are supported
if(isset($_REQUEST['_minify_'])){
	$parts=preg_split('/\_/',$_REQUEST['_minify_'],2);
	if($parts[0]=='bootstrap'){
		$parts[0]='bootstrap/css/bootstrap';
	}
	if(!in_array($parts[0],$_SESSION['w_MINIFY']['extras_css'])){
        $_SESSION['w_MINIFY']['extras_css'][]=$parts[0];
	}
}
elseif(isset($_REQUEST['bootstrap'])){
	$extra='bootstrap/css/bootstrap';
	if(!in_array($extra,$_SESSION['w_MINIFY']['extras_css'])){
        $_SESSION['w_MINIFY']['extras_css'][]=$extra;
	}
}
//echo $minify_string.printValue($_SERVER);exit;
global $filename;
$filename='minify';
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
	header('Content-type: text/css; charset=UTF-8');
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
//get the css path
$csspath=realpath("{$progpath}/../wfiles/css");
//initialize the global files array
global $files;
$files=array();
//add wasql CSS file
minifyFiles($csspath,'wasql');
minifyFiles($csspath,'wasql_icons');
//echo printValue($parts).printValue($_REQUEST).printValue($_SESSION['w_MINIFY']);exit;
//Get any extras
if(isset($_SESSION['w_MINIFY']['extras_css']) && is_array($_SESSION['w_MINIFY']['extras_css'])){
	foreach($_SESSION['w_MINIFY']['extras_css'] as $extra){
		minifyFiles(realpath("{$csspath}/extras"),$extra);
	}
	$filename.='X'.count($_SESSION['w_MINIFY']['extras_css']);
}
if(isset($_SESSION['w_MINIFY']['cssfiles']) && is_array($_SESSION['w_MINIFY']['cssfiles'])){
	foreach($_SESSION['w_MINIFY']['cssfiles'] as $file){
    	if(!in_array($file,$files)){$files[]=$file;}
	}
	$filename.='F'.count($_SESSION['w_MINIFY']['cssfiles']);
}
//echo printValue($files);exit;
//include files and set the lastmodifiedtime of any file
global $csslines;
global $pre_csslines;
$csslines=array();
$loaded=array();
$pre_csslines=array();
foreach($files as $file){
	if($_REQUEST['debug']==1){
		$csslines[]= "{$file}<br>\r\n";
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
		echo "/* Minify_css Error: NO SUCH FILE:{$file} */".PHP_EOL.PHP_EOL;
		continue;
	}
	$lines=file($file);
	//$cnt=count($lines);
	//echo "/* Minify_css File:{$file}  LineCnt:{$cnt} */".PHP_EOL.PHP_EOL;
	if(is_array($lines)){
		$fname=getFileName($file);
		$conditionals=1;
		if(stringContains($file,'extras') && !stringContains($fname,'extras')){
			$fname="extras/{$fname}";
		}
		if(stringEndsWith($file,'.min.css')){
			$conditionals=0;
		}
		$csslines[]= "\r\n/* BEGIN {$fname} */\r\n";
		$loaded[]=$fname;
    	minifyLines($lines,$conditionals);
	}
	else{
		echo "/* Minify_css Error: NO FILE LINES:{$file} */".PHP_EOL.PHP_EOL;
	}
}
//load the template, includepages, and page
$field=$CONFIG['minify_css']?'css_min':'css';
$field2=$CONFIG['minify_css']?'css':'css_min';
//_templates
if(isNum($_SESSION['w_MINIFY']['template_id']) && $_SESSION['w_MINIFY']['template_id'] > 0){
	$rec=getDBRecord(array('-table'=>'_templates','_id'=>$_SESSION['w_MINIFY']['template_id'],'-fields'=>$field));
	$content=evalPHP($rec[$field]);
	if(strlen(trim($content)) > 10){
		$filename.='T'.$_SESSION['w_MINIFY']['template_id'];
		$csslines[] = "\r\n/* BEGIN _templates {$field} */\r\n";
		$loaded[]="_templates {$field}";
		minifyLines($content);
	}
	else{
		$rec=getDBRecord(array('-table'=>'_templates','_id'=>$_SESSION['w_MINIFY']['template_id'],'-fields'=>$field2));
		$content=evalPHP($rec[$field2]);
		if(strlen(trim($content)) > 10){
			$filename.='T'.$_SESSION['w_MINIFY']['template_id'];
			$csslines[] = "\r\n/* BEGIN _templates {$field2} */\r\n";
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
		$filename.='P'.$_SESSION['w_MINIFY']['page_id'];
		$csslines[] = "\r\n/* BEGIN _pages {$field} */\r\n";
		$loaded[]="_pages {$field}";
		minifyLines($content);
	}
	else{
		$rec=getDBRecord(array('-table'=>'_pages','_id'=>$_SESSION['w_MINIFY']['page_id'],'-fields'=>$field2));
		$content=evalPHP($rec[$field2]);
		if(strlen(trim($content)) > 10){
			$filename.='T'.$_SESSION['w_MINIFY']['page_id'];
			$csslines[] = "\r\n/* BEGIN _pages {$field2} */\r\n";
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
			$filename.='P'.$id;
			$csslines[] = "\r\n/* BEGIN includepages {$field} for {$rec['name']} page */\r\n";
			$loaded[]="includepages {$field} for {$rec['name']} page";
			minifyLines($content);
		}
		else{
			$rec=getDBRecord(array('-table'=>'_pages','_id'=>$id,'-fields'=>"name,{$field2}"));
			$content=evalPHP($rec[$field2]);
			if(strlen(trim($content)) > 10){
				$filename.='T'.$_SESSION['w_MINIFY']['template_id'];
				$csslines[] = "\r\n/* BEGIN includepages {$field2} for {$rec['name']} page */\r\n";
				$loaded[]="includepages {$field} for {$rec['name']} page";
				minifyLines($content);
			}
		}
	}
}
//show loaded list
$sessionid=session_id();
//
if(count($pre_csslines)){
	echo "/* Begin Imports - must be at the top */\r\n";
	echo implode("\r\n",$pre_csslines);
}
echo implode("\r\n",$csslines);
ob_end_flush();
/* ------------ Functions needed ---------------- */
function minifyFiles($path,$names){
	global $files;
	global $CONFIG;
	if(!is_array($names)){$names=array($names);}
	//echo $path.implode(',',$names).PHP_EOL;return;
	foreach($names as $name){
		//automatically create minified versions if they do not exist - localhost only
		if($_SERVER['UNIQUE_HOST']=='localhost' && !stringEndsWith($name,'.min') && !is_file("{$path}/{$name}.min.css") && is_file("{$path}/{$name}.css")){
			$code=getFileContents("{$path}/{$name}.css");
			$mcode=minifyCode($code,'css');
			setFileContents("{$path}/{$name}.min.css",$mcode);
		}
		if(preg_match('/^http/i',$name)){
	     	//remote file - expire every week
	     	$evalstr="return minifyGetExternal('{$name}');";
			echo getStoredValue($evalstr,0,168);
			continue;
		}
		if($CONFIG['minify_css'] && is_file("{$path}/{$name}.min.css")){
			$file=realpath("{$path}/{$name}.min.css");
			if(!in_array($file,$files)){$files[]=$file;}
		}
		elseif(is_file("{$path}/{$name}.css")){
	    	$file=realpath("{$path}/{$name}.css");
			if(!in_array($file,$files)){$files[]=$file;}
		}
		else{echo "/* Minify_css Error: NO SUCH NAME:{$name} */".PHP_EOL.PHP_EOL;}
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
function minifyLines($lines,$conditionals=1) {
	global $csslines;
	global $pre_csslines;
	if(!is_array($lines)){
		$lines=preg_split('/[\r\n]+/',$lines);
	}
	$vars=array();
	foreach($lines as $line){
		$tline=trim($line);
     	if(!strlen($tline)){continue;}
     	//ignore comments
     	if(strpos($tline,"//") === 0){continue;}
     	if(strpos($tline,"/*") === 0 && strpos(strrev($tline),"/*") === 0){continue;}
     	/*look for variable definations */
     	preg_match_all('/\@([a-zA-Z0-9\-\_]+?)\:([^\{\}]*?)\;/ism',$tline,$vm);
     	for($v=0;$v<count($vm[1]);$v++){
			$vars['@'.$vm[1][$v]]=$vm[2][$v];
			$line=str_replace($vm[0][$v],'',$line);
		}
		//replace any vars
		foreach($vars as $vkey=>$vval){
			$line=str_replace($vkey,$vval,$line);
		}
     	//skip conditionals for minified files
     	if(!$conditionals){
			//remove conditionals
			preg_match_all('/\;\[([a-zA-Z0-9\s\|]+?)\]/ism',$line,$vm);
			foreach($vm[1] as $conditional){
				$conditional="[{$conditional}]";
				$line=str_replace($conditional,'',$line);
			}
			$csslines[]=rtrim($line);
			continue;
		}

     	/* look for conditonals - must appear at the beginning of the CSS line
		  supported browser names: firefox|msie|chrome|safari|opera
		  supported operators: lt, gt, lte, gte, eq, not
		  not msie: [not msie]
		  to only show for msie: [msie]
		  to only show for msie 6 [msie eq 6]
		  to only show for msie greater than 6 [msie gt 6]
		  to ony show for msie less than or equal to 6 [msie lte 6]
		  BUT ALLOW FOR [data-toggle="buttons"]
		*/
		if(preg_match('/^\[(.+?)\]/',$tline,$cssmatch)){
			$browser=strtolower($_SERVER['REMOTE_BROWSER']);
			$parts=preg_split('/\ +/',strtolower($cssmatch[1]),3);
			if(count($parts)==1){
				//allow for css selectors
				$valid_browsers=array('msie','chrome','firefox','safari','opera');
				$part_browsers=preg_split('/\|/',$parts[0]);
				if(!in_array($part_browsers[0],$valid_browsers)){
                	$csslines[]=rtrim($line);
					continue;
				}
				//ALLOW FOR [data-toggle="buttons"]
				if(stringContains($cssmatch[1],'"')){
					$csslines[]=rtrim($line);
					continue;
				}
				if(!in_array($browser,preg_split('/\|/',$parts[0]))){continue;}
			}
			//[not msie]
			if(count($parts)==2 && $parts[0] == 'not' && in_array($browser,preg_split('/\|/',$parts[1]))){continue;}
			if(count($parts)==3 ){
				$version=$_SERVER['REMOTE_BROWSER_VERSION'];
				//$rtn .=  "/*CONDITIONAL: Browser:{$browser}, version: {$version}, parts0:{$parts[0]}, parts1:{$parts[1]}, parts2:{$parts[2]}*/\r\n";
				if(!in_array($browser,preg_split('/\|/',$parts[0]))){continue;}
				$pass=0;
				switch($parts[1]){
                	case 'eq':
                		//[msie eq 6]
						if(round($version) == round($parts[2],2)){$pass=1;}
						break;
					case 'lt':
						//[msie lt 6]
						if(round($version) < round($parts[2],2)){$pass=1;}
						break;
					case 'gt':
						//[msie gt 6]
						if(round($version) > round($parts[2],2)){$pass=1;}
						break;
					case 'lte':
						//[msie lte 8]
						if(round($version) <= round($parts[2],2)){$pass=1;}
						break;
					case 'gte':
						//[msie gte 6]
						if(round($version) >= round($parts[2],2)){$pass=1;}
						break;
				}
				if($pass==0){continue;}
			}
			//remove the conditional statement
			$line=str_replace($cssmatch[0],'',$line);
		}
		//add the line but trim the right side
		if(strpos($tline,'@import') === 0){$pre_csslines[]=rtrim($line);}
		else{$csslines[]=rtrim($line);}
	}
	return 1;
}
