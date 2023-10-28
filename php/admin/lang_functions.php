<?php
function langPHPInfo(){
	//get phpinfo contents
	ob_start();
    phpinfo();
    $data = ob_get_contents();
    ob_clean();
    //get modules for left side menu by parsing phpinfo contents
    $modules=array();
    $menu=array();
	if(preg_match('/\<body\>(.+)\<\/body\>/ism',$data,$m)){
		$body=preg_replace('/\<hr \/\>/ism','',$m[1]);
		//parse out modules to build a list
		preg_match_all('/\<a name\=\"module\_(.+?)\".*?\>(.+?)\<\/a\>/is',$data,$matches);
		$links=array();
		foreach($matches[1] as $module){
			$k=strtolower($module);
			$menu[$k]=$module;
		}
	}
	ksort($menu);
	$data=<<<ENDOFDATA
	<style type="text/css">
	table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}
	.center {text-align: center;}
	.center table {margin: 1em auto; text-align: left;}
	.center th {text-align: center !important;}
	td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
	h1 {font-size: 150%;}
	h2 {font-size: 125%;}
	.p {text-align: left;}
	.e {background-color: #ccf; width: 300px; font-weight: bold;}
	.h {background-color: #99c; font-weight: bold;}
	.v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
	.v i {color: #999;}
	</style>
	{$body}
ENDOFDATA;
	return array($data,$menu);
}
function langPythonInfo(){
	//get pythoninfo contents
	$pypath=getWasqlPath('python');
	$out=cmdResults("python3 \"{$pypath}/pythoninfo.py\"");
	$data=$out['stdout'];
	//get modules for left side menu by parsing pythoninfo contents
	preg_match_all('/\<section\>(.+?)\<\/section\>/ism',$data,$sections);
	$header='';
	if(preg_match('/\<header\>(.+?)\<\/header\>/ism',$data,$m)){
		$header=$m[0];
	}
	$modules=array();
	$menu=array();
	foreach($sections[0] as $section){
		$module='';
		if(preg_match('/\<a name\=\"module\_(.+?)\">(.+?)\<\/a\>/is',$section,$m)){
			$k=strtolower($m[1]);
			$modules[$k]=$section;
			$menu[$k]=$m[1];
		}
	}
	$data=$header;
	$data.='<div class="align-center">';
	ksort($modules);
	ksort($menu);
	foreach($modules as $k=>$section){
		$data.=$section;
	}
	$data.='</div>';
	return array($data,$menu);
}
function langPerlInfo(){
	$modules=array();
	$menu=array();
	//get pythoninfo contents
	$out=cmdResults('perl -MFile::Find=find -MFile::Spec::Functions -Tlwe "find { wanted => sub { print canonpath $_ if /\.pm\z/ }, no_chdir => 1 }, @INC"');
	$lines=preg_split('/[\r\n]+/',$out['stdout']);
	$out=cmdResults("perl -v");
	$version='';
	if(preg_match('/\(v([0-9\.]+?)\)/',$out['stdout'],$m)){
		$version=$m[1];
	}
	elseif(preg_match('/\ v([0-9\.]+?)\ /',$out['stdout'],$m)){
		$version=$m[1];
	}
	$header=<<<ENDOFHEADER
<header>
	<div style="background:#003e62;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-perl"></span> Perl</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;
	foreach($lines as $line){
		$line=str_replace("\\","/",trim($line));
		if(!stringEndsWith($line,'.pm')){continue;}
		$line=preg_replace('/\.pm$/i','',$line);
		$parts=preg_split('/\/+/is',$line);
		while(count($parts)){
			$p=array_shift($parts);
			if(strtolower($p)=='lib'){
				break;
			}
		}
		if(!count($parts)){continue;}
		$module=implode('::',$parts);
		$k=strtolower($module);
		$info='';
		$version='';
		$modules[$k]=<<<ENDOFSECTION
<div class="align-center w_bold w_big">{$module}</div>
<table class="table condensed bordered" style="margin-bottom:15px;">
<tr><td class="align-left w_small w_nowrap" style="background:#003e62;color:#FFF;">Name</td><td class="align-left w_small" style="width:90%;">{$module}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#003e62;color:#FFF;">Version</td><td class="align-left w_small" style="width:90%;">{$version}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#003e62;color:#FFF;">Info</td><td class="align-left w_small" style="width:90%;">{$info}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$module;
	}
	$data=$header;
	$data.='<div class="align-center">';
	ksort($modules);
	ksort($menu);
	foreach($modules as $k=>$section){
		$data.=$section;
	}
	$data.='</div>';
	return array($data,$menu);
}
function langNodeInfo(){
	$modules=array();
	$menu=array();
	//get node modules
	$tpath=getWasqlTempPath();
	if(file_exists("{$tpath}/npm_modules.json")){
		$json=decodeJSON(getFileContents("{$tpath}/npm_modules.json"));
	}
	else{
		$out=cmdResults('npm -g -json ll');
		$json=decodeJSON($out['stdout']);
	}
	if(!is_array($json)){return array('','');}
	$out=cmdResults("node -v");
	$version=$out['stdout'];
	$header=<<<ENDOFHEADER
<header>
	<div style="background:#003e62;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="brand-node-dot-js"></span> Node</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;
	foreach($json['dependencies'] as $module=>$info){
		//echo $module.printValue($info);exit;
		$k=strtolower($module);
		$version=isset($info['version'])?$info['version']:'';
		$modules[$k]=<<<ENDOFSECTION
<div class="align-center w_bold w_big">{$module}</div>
<table class="table condensed bordered" style="margin-bottom:15px;">
<tr><td class="align-left w_small w_nowrap" style="background:#003e62;color:#FFF;">Name</td><td class="align-left w_small" style="width:90%;">{$module}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#003e62;color:#FFF;">Version</td><td class="align-left w_small" style="width:90%;">{$version}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$module;
	}
	$data=$header;
	$data.='<div class="align-center">';
	ksort($modules);
	ksort($menu);
	foreach($modules as $k=>$section){
		$data.=$section;
	}
	$data.='</div>';
	return array($data,$menu);
}
function langLuaInfo(){
	$modules=array();
	$menu=array();
	//get lua modules
	$tpath=getWasqlTempPath();
	if(file_exists("{$tpath}/lua_modules.txt")){
		$lines=file("{$tpath}/lua_modules.txt");
	}
	else{
		$out=cmdResults('luarocks list --porcelain');
		$lines=preg_split('/[\r\n]+/',$out['stdout']);
	}
	$out=cmdResults("lua -v");
	$version=$out['stdout'];
	$header=<<<ENDOFHEADER
<header>
	<div style="background:#FFF;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#00007c;"><span class="brand-lua"></span> Lua</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#00007c;">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;
	foreach($lines as $line){
		list($module,$version,$status,$path)=preg_split('/\s+/is',trim($line));
		$k=strtolower($module);
		$info='';
		$modules[$k]=<<<ENDOFSECTION
<div class="align-center w_bold w_big">{$module}</div>
<table class="table condensed bordered" style="margin-bottom:15px;">
<tr><td class="align-left w_small w_nowrap" style="background:#00007c;color:#FFF;">Name</td><td class="align-left w_small" style="width:90%;">{$module}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#00007c;color:#FFF;">Version</td><td class="align-left w_small" style="width:90%;">{$version}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#00007c;color:#FFF;">Info</td><td class="align-left w_small" style="width:90%;">{$info}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$module;
	}
	$data=$header;
	$data.='<div class="align-center">';
	ksort($modules);
	ksort($menu);
	foreach($modules as $k=>$section){
		$data.=$section;
	}
	$data.='</div>';
	return array($data,$menu);
}
?>
