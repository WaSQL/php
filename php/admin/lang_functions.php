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
	return array($body,$menu);
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
	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;
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
<header class="align-left">
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
		$modules[$k]=array('key'=>$k,'name'=>$module,'parts'=>$parts,'submodules'=>array());
		$menu[$k]=$module;
	}
	ksort($modules);
	foreach($modules as $k=>$info){
		if(count($info['parts'])==1){continue;}
		$parts=$info['parts'];
		while(count($parts)){
			$last=array_pop($parts);
			$pkey=strtolower(implode('::',$parts));
			if(isset($modules[$pkey])){
				$modules[$pkey]['submodules'][]=$modules[$k]['name'];
				unset($modules[$k]);
				unset($menu[$k]);
				break;
			}
		}
	}
	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;
	ksort($menu);
	foreach($modules as $module=>$info){
		$submodules=implode('<br>',$info['submodules']);
		$data.=<<<ENDOFSECTION
<h2><a name="module_{$info['name']}">{$info['name']}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background-color:#003e624D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$info['name']}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background-color:#003e624D;width:300px;">Submodules</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$submodules}</td></tr>
</table>
ENDOFSECTION;
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
<header class="align-left">
	<div style="background-color:#000000;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="brand-node-dot-js"></span> Node</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;
	foreach($json['dependencies'] as $module=>$info){
		$k=strtolower($module);
		$version=isset($info['version'])?$info['version']:'';
		$dependencies=array();
		if(isset($info['dependencies']) && is_array($info['dependencies'])){
			foreach($info['dependencies'] as $dname=>$dinfo){
				$dependencies[]="{$dname} - v{$dinfo['version']}";
			}
		}
		$dependencies=implode('<br>',$dependencies);
		$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$module}">{$module}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Name</td><td style="text-align:left;min-width:300px;background-color:#CCCCCC80;">{$module}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Version</td><td style="text-align:left;min-width:300px;background-color:#CCCCCC80;">{$version}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Dependencies</td><td style="text-align:left;min-width:300px;background-color:#CCCCCC80;">{$dependencies}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$module;
	}
	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;
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
<header class="align-left">
	<div style="background:#ccc;padding:10px 20px;margin-bottom:20px;border:1px solid #999;">
		<div style="font-size:clamp(24px,3vw,48px);color:#2c2d72"><span class="brand-lua"></span> Lua</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#2c2d72">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;
	foreach($lines as $line){
		list($module,$version,$status,$path)=preg_split('/\s+/is',trim($line));
		$k=strtolower($module);
		$info='';
		$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$module}">{$module}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$module}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$version}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$module;
	}
	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;
	ksort($modules);
	ksort($menu);
	foreach($modules as $k=>$section){
		$data.=$section;
	}
	$data.='</div>';
	return array($data,$menu);
}
?>
