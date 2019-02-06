<?php
//minify the js files in wfiles/js
/* first, lets determine the current page, template, domain, and user */

error_reporting(E_ALL & ~E_NOTICE);
$progpath=dirname(__FILE__);
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
//parse SERVER vars to get additional SERVER params
parseEnv();
if(!is_array($_SESSION['w_MINIFY']['extras_js'])){
	$_SESSION['w_MINIFY']['extras_js']=array();
}
//check for framework:  bootstrap, materialize, foundation are supported
if(isset($_REQUEST['_minify_'])){
	$parts=preg_split('/\_/',$_REQUEST['_minify_'],2);
	switch(strtolower($parts[0])){
		case 'uikit':
		case 'kube':
		case 'foundation':
			//uikit and kube require jquery
			$_SESSION['w_MINIFY']['extras_js'][]='jquery2';
		break;
	}
	if(!in_array($parts[0],$_SESSION['w_MINIFY']['extras_js'])){
        $_SESSION['w_MINIFY']['extras_js'][]=$parts[0];
	}
}
//echo printValue($_SESSION['w_MINIFY']['extras_js']);exit;
global $filename;
$filename='minify';
global $lastmodifiedtime;
//get the HTTP_IF_MODIFIED_SINCE header if set
global $ifModifiedSince;
$ifModifiedSince=isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:'';
//get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
global $etagHeader;
$etagHeader=isset($_SERVER['HTTP_IF_NONE_MATCH'])?trim($_SERVER['HTTP_IF_NONE_MATCH']):'NOTAG';
$afilename="{$progpath}/minify/{$filename}";
if($_REQUEST['debug']==1){
	header('Content-type: text/plain; charset=UTF-8');
	echo "Session ID: ".session_id().PHP_EOL;
	foreach($_SESSION['w_MINIFY'] as $key=>$val){
		if(!is_array($val) && strlen($val) > 300){continue;}
		$debug[$key]=$val;
	}
	echo 'Minify Values:'.printValue($debug,-1);
	exit;
}
else{
	header("Content-type: text/javascript; charset=UTF-8");
}
//enable caching of responses for IE8 and others even on https
header("Pragma: public");
//make sure caching is turned on
header('Cache-Control: public, must-revalidate');
//set expires to expire in 24 hours in the past - otherwise the browser will not even ask for it again until it expires
$expires = 60*60*24;
header('Expires: ' . gmdate('D, d M Y H:i:s', time()-$expires) . ' GMT');
//start
ob_start("compress");
//get the js path
$jspath=realpath("{$progpath}/../wfiles/js");
//app path
$appspath=realpath('../apps');
//initialize the global files array
global $files;
$files=array();
//add wasql js file
minifyFiles($jspath,array('common','event','form','calendar','colorpicker'));
//Get any extras
if(!isset($_SESSION['w_MINIFY']['extras_js']) || !is_array($_SESSION['w_MINIFY']['extras_js'])){
	$_SESSION['w_MINIFY']['extras_js']=array();
}
//check for framework:  bootstrap, materialize, foundation are supported
if(isset($_REQUEST['_minify_'])){
	$parts=preg_split('/\_/',$_REQUEST['_minify_'],2);
	if(!in_array($parts[0],$_SESSION['w_MINIFY']['extras_js'])){
        $_SESSION['w_MINIFY']['extras_js'][]=$parts[0];
	}
}
foreach($_SESSION['w_MINIFY']['extras_js'] as $extra){
	if($extra=='codemirror'){
		minifyCodeMirrorFiles();
	}
	elseif($extra=='google'){
		minifyGoogleFiles();
	}
	elseif($extra=='bootstrap4'){
		minifyFiles(realpath("{$jspath}/extras"),'jquery2');
		minifyFiles(realpath("{$jspath}/extras"),$extra);
	}
	elseif(preg_match('/^app\:(.+)$/i',$extra,$m)){
		$app=strtolower($m[1]);
		/* --- /apps/chat/chat.css ---*/
		minifyFiles(realpath("{$appspath}/{$app}"),"{$app}.css");
	}
	else{
		minifyFiles(realpath("{$jspath}/extras"),$extra);
	}
}
$filename.='X'.count($_SESSION['w_MINIFY']['extras_js']);


//Add any files in the $_SESSION['w_MINIFY']['jsfiles'] array
if(isset($_SESSION['w_MINIFY']['jsfiles']) && is_array($_SESSION['w_MINIFY']['jsfiles'])){
	foreach($_SESSION['w_MINIFY']['jsfiles'] as $file){
    	$files[]=$file;
	}
	$filename.='F'.count($_SESSION['w_MINIFY']['jsfiles']);
}

//If this is Internet explorer, load this js file: http://html5shiv.googlecode.com/svn/trunk/html5.js
if(isset($_SESSION['w_MINIFY']['device_browser']) && $_SESSION['w_MINIFY']['device_browser']=='msie'){
	$files[]='http://html5shiv.googlecode.com/svn/trunk/html5.js';
	$filename.='IE';
}

if(isset($CONFIG['facebook_appid'])){
	echo <<<ENDOFFACEBOOKAPPJS
/* Facebook AppID js */
var facebook_appid='{$CONFIG['facebook_appid']}';
var facebook_id='{$_SESSION['facebook_id']}';
var facebook_email='{$_SESSION['facebook_email']}';
ENDOFFACEBOOKAPPJS;
}
if(isset($CONFIG['google_appid'])){
	$files[]='https://apis.google.com/js/platform.js?onload=renderGoogleLogin';
	echo <<<ENDOFGOOGLEAPPJS

/* Google Login*/
var meta = document.createElement('meta');
meta.name = "google-signin-scope";
meta.content = "profile email";
document.getElementsByTagName('head')[0].appendChild(meta);
meta = document.createElement('meta');
meta.name = "google-signin-client_id";
meta.content = '{$CONFIG['google_appid']}';
document.getElementsByTagName('head')[0].appendChild(meta);
function onGoogleSuccess(googleUser) {
	var s = document.querySelectorAll('span[id^="not_signed_in"]');
	if(undefined == s || undefined == s[0]){return false;}
	var ck=s[0].getAttribute('data-loaded');
	if(undefined == ck){
		s[0].setAttribute('data-loaded',1);
		return false;
	}
	var txt=getText(s[0]);
	console.log(txt.indexOf('Autofill'));
	if(txt.indexOf('Autofill') == -1){return false;}
    var profile = googleUser.getBasicProfile();
    //Login Form?
    if(undefined != document.loginform){
		var id_token = googleUser.getAuthResponse().id_token;
		document.loginform.username.value=profile.getEmail();
		document.loginform.username.name='google_email';
		document.loginform.password.value=id_token;
		//pass in google_id
		i=document.createElement('input');
		i.name='google_id';
		i.value=id_token;
		i.style.display='none';
		document.loginform.appendChild(i);

		//also pass in google_image
		var i=document.createElement('input');
		i.name='google_image';
		i.value=profile.getImageUrl();
		i.style.display='none';
		document.loginform.appendChild(i);

		//also pass in google_name
		i=document.createElement('input');
		i.name='google_name';
		i.value=profile.getName();
		i.style.display='none';
		document.loginform.appendChild(i);
		hideId('google_login');
		//submit the form - note: this causes issues and keeps on resubmitting the form on error
		//document.loginform.submit();
		return;
	}
	//Register Form
	else if(undefined != document.registerform){
		var id_token = googleUser.getAuthResponse().id_token;
		if(undefined != document.registerform.username){
			document.registerform.username.value=profile.getEmail();
		}
		if(undefined != document.registerform.email){
			document.registerform.email.value=profile.getEmail();
		}
		//check for name in form (not the form name itself)
		if(undefined != document.registerform.name && undefined != document.registerform.name.type){
			document.registerform.name.value=profile.getName();
		}
		else if(undefined != document.registerform.firstname && undefined != document.registerform.lastname){
			var pname=profile.getName();
			var p=pname.split(' ',2);
			document.registerform.firstname.value=p[0];
			document.registerform.lastname.value=p[1];
		}
		if(undefined != document.registerform.icon){
			document.registerform.name.value=profile.getImageUrl();
		}
		//also pass in google_name
		var i=document.createElement('input');
		i.name='google_name';
		i.value=profile.getName();
		i.style.display='none';
		document.registerform.appendChild(i);

		//also pass in google_image
		i=document.createElement('input');
		i.nme='google_image';
		i.value=profile.getImageUrl();
		i.style.display='none';
		document.registerform.appendChild(i);

		//pass in google_id
		i=document.createElement('input');
		i.name='google_id';
		i.value=id_token;
		i.style.display='none';
		document.registerform.appendChild(i);

		//hide the google button
		hideId('google_login');
		//submit the form
		//document.registerform.submit();
		return;
	}
    return false;
};
function onGoogleFailure(){
	console.log('google login failed');
	return false;
}
function renderGoogleLogin() {
    gapi.signin2.render('google_login', {
        'scope': 'https://www.googleapis.com/auth/plus.login',
        'width': 150,
        'height': 20,
        'longtitle': true,
        'theme': 'dark',
        'onsuccess': onGoogleSuccess,
        'onfailure': onGoogleFailure,
    });
    addEventHandler(window,'load',changeGoogleLoginText);
}
function changeGoogleLoginText(){
    var s = document.querySelectorAll('span[id^="not_signed_in"]');
    if(s.length==1){setText(s[0],'Autofill using Google');}
    s = document.querySelectorAll('span[id^="connected"]');
    if(s.length==1){setText(s[0],'Autofill using Google');}
}
ENDOFGOOGLEAPPJS;
}
//include files and set the lastmodifiedtime of any file
foreach($files as $file){
	if($_REQUEST['debug']==1){
		echo "{$file}<br>".PHP_EOL;
		continue;
	}
	if(strtolower($file)=='stripe'){
		echo "/* BEGIN {$file} */".PHP_EOL.PHP_EOL;
    	echo "loadJsCss('https://js.stripe.com/v1/','js')";
    	echo PHP_EOL.PHP_EOL;
    	continue;
	}
	if(preg_match('/^http/i',$file)){
     	//remote file - expire every week
     	$evalstr="return minifyGetExternal('{$file}');";
		echo getStoredValue($evalstr,0,168);
		continue;
	}
	$afile=realpath($file);
	$mtime=filemtime($afile);
	if(!strlen($lastmodifiedtime) || $mtime > $lastmodifiedtime){$lastmodifiedtime=$mtime;}
	if(!strlen($afile)){continue;}
	$lines=file($afile);
	if(is_array($lines)){
		echo "/* BEGIN {$file} */".PHP_EOL.PHP_EOL;
    	echo minifyLines($lines);
    	echo PHP_EOL.PHP_EOL;
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
		$filename.='T'.$_SESSION['w_MINIFY']['template_id'];
		echo "/* BEGIN _templates {$field} */".PHP_EOL.PHP_EOL;
		echo minifyLines($content);
		echo PHP_EOL.PHP_EOL;
	}
	else{
    	$rec=getDBRecord(array('-table'=>'_templates','_id'=>$_SESSION['w_MINIFY']['template_id'],'-fields'=>$field2));
		$content=evalPHP($rec[$field2]);
		if(strlen(trim($content)) > 10){
			$filename.='T'.$_SESSION['w_MINIFY']['template_id'];
			echo "/* BEGIN _templates {$field2} */".PHP_EOL.PHP_EOL;
			echo minifyLines($content);
			echo PHP_EOL.PHP_EOL;
		}
	}
}
//_pages
if(isNum($_SESSION['w_MINIFY']['page_id']) && $_SESSION['w_MINIFY']['page_id'] > 0){
	$rec=getDBRecord(array('-table'=>'_pages','_id'=>$_SESSION['w_MINIFY']['page_id'],'-fields'=>$field));
	$content=evalPHP($rec[$field]);
	$error='';
	if($field=='js_min' && preg_match('/Error\: Unexpected token/',$content)){
		$error=' -- ERROR Minifying Js on Page';
	}
	if(!strlen($error) && strlen(trim($content)) > 10){
		$filename.='P'.$_SESSION['w_MINIFY']['page_id'];
		echo "/* BEGIN _pages {$field} */".PHP_EOL.PHP_EOL;
		echo minifyLines($content);
		echo PHP_EOL.PHP_EOL;
	}
	else{
    	$rec=getDBRecord(array('-table'=>'_pages','_id'=>$_SESSION['w_MINIFY']['page_id'],'-fields'=>$field2));
		$content=evalPHP($rec[$field2]);
		if(strlen(trim($content)) > 10){
			$filename.='P'.$_SESSION['w_MINIFY']['page_id'];
			echo "/* BEGIN _pages {$field2} {$error} */".PHP_EOL.PHP_EOL;
			echo minifyLines($content);
			echo PHP_EOL.PHP_EOL;
		}
	}
}
//includepages
if(is_array($_SESSION['w_MINIFY']['includepages'])){
	foreach($_SESSION['w_MINIFY']['includepages'] as $id){
		$rec=getDBRecord(array('-table'=>'_pages','_id'=>$id,'-fields'=>"name,{$field}"));
		$content=$field=='js'?evalPHP($rec[$field]):$rec[$field];
		if(strlen(trim($content)) > 10){
			$filename.='P'.$id;
			echo "/* BEGIN includepages {$field} for {$rec['name']} page */".PHP_EOL.PHP_EOL;
			echo minifyLines($content);
			echo PHP_EOL.PHP_EOL;
		}
		else{
        	$rec=getDBRecord(array('-table'=>'_pages','_id'=>$id,'-fields'=>"name,{$field2}"));
			$content=$field=='js'?evalPHP($rec[$field]):$rec[$field2];
			if(strlen(trim($content)) > 10){
				$filename.='P'.$id;
				echo "/* BEGIN includepages {$field2} for {$rec['name']} page */".PHP_EOL.PHP_EOL;
				echo minifyLines($content);
				echo PHP_EOL.PHP_EOL;
			}
		}
	}
}
echo "/* END Minify {$filename}.js */".PHP_EOL.PHP_EOL;
//debug?
if($_REQUEST['debug']==1 && is_array($_SESSION['w_MINIFY'])){
	$debug=array();
	foreach($_SESSION['w_MINIFY'] as $key=>$val){
		if(!is_array($val) && strlen($val) > 300){continue;}
		$debug[$key]=$val;
	}
	//echo '<hr size=1>$_SESSION[w_MINIFY]:'.printValue($debug);
}
ob_end_flush();
/* ------------ Functions needed ---------------- */
function minifyFiles($path,$names){
	global $files;
	global $CONFIG;
	if(!is_array($names)){$names=array($names);}
	foreach($names as $name){
		//automatically create minified versions if they do not exist - localhost only
		if($_SERVER['UNIQUE_HOST']=='localhost' && !stringEndsWith($name,'.min') && !is_file("{$path}/{$name}.min.js") && is_file("{$path}/{$name}.js") && file_exists('d:/minifier/minifyme.php')){	
			$out=cmdResults("php d:/minifier/minifyme.php \"{$path}/{$name}.js\"");
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
		elseif(strtolower($name)=='stripe'){
			$files[]=$name;
		}
		else{echo "/* Minify_js Error: NO SUCH NAME:{$name} */".PHP_EOL.PHP_EOL;}
	}
	return false;
}
function minifyCodeMirrorFiles(){
	$codemirror_path=realpath('../wfiles/js/codemirror');
	minifyFiles($codemirror_path,'codemirror');
	$cfiles=minifyListFiles($codemirror_path);
	foreach($cfiles as $file){
		//skip files that do not end in .js
		if(!preg_match('/\.js$/i',$file)){continue;}
		if(preg_match('/min\.js$/i',$file)){continue;}
		if(preg_match('/^(codemirror|matchbrackets)\.js$/i',$file)){continue;}
		$file=preg_replace('/\.js$/i','',$file);
		minifyFiles($codemirror_path,$file);
	}
}
function minifyGoogleFiles(){
	global $files;
	$files[]='https://www.google.com/jsapi';
	$google_path=realpath('../wfiles/js/google');
	minifyFiles($google_path,'json2');
	minifyFiles($google_path,'google');
}
function minifyGetExternal($url){
	if(!strlen($url)){return '';}
	//validate we can actually connect to the internet and that the file exists
	if(!isLiveUrl($url)){return "/* UNABLE TO CONNECT: {$url} */";}
	$lines=file($url);
	$rtn='';
	if(is_array($lines)){
		$rtn .= "/* BEGIN {$url} */".PHP_EOL.PHP_EOL;
    	$rtn .=  minifyLines($lines);
    	$rtn .= PHP_EOL.PHP_EOL;
	}
	return $rtn;
}
function compress($buffer) {
	/* remove comments */
	/*set an etag based on the combined file data */
	global $lastmodifiedtime;
	global $filename;
	global $etagHeader;
	global $ifModifiedSince;
	$etag=sha1($buffer);
	$filename.='.js';
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
	else{
    	header("X-ETH: {$etagHeader}");
    	header("X-MTN: {$etagHeader}");
    	header('X-LMT: '.gmdate("D, d M Y H:i:s", $modtime).' GMT');
	}
	header("Accept-Ranges: bytes");
	header("Content-Length: ".strlen($buffer));
	header('Content-Disposition: inline; filename="'.$filename.'"');
	return $buffer;
}
//--------------------
function minifyListFiles($dir='.'){
	//info: returns an array of files in dir
	if(!is_dir($dir)){return array();}
	if ($handle = opendir($dir)) {
    	$files=array();
    	while (false !== ($file = readdir($handle))) {
			if($file == '.' || $file == '..'){continue;}
        	$files[$file] = 1;
    		}
    	closedir($handle);
    	ksort($files);
    	return array_keys($files);
	}
	return array();
}
//--------------------
function minifyLines($lines) {
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
		$rtn .= rtrim($line).PHP_EOL;
	}
	return $rtn;
}
