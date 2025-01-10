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
	$out=cmdResults("python3 \"{$pypath}/pythoninfo.py\" 2>&1");
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
	$out=cmdResults('perl -V 2>&1');
	$lines=preg_split('/[\r\n]+/',$out['stdout']);
	$perlinfo=array();
	$section='';
	$incpaths=array();
	foreach($lines as $line){
		$line=trim($line);
		if(preg_match('/^(.+?)\:$/',$line,$m)){
			$section=$m[1];
			continue;
		}
		if(!strlen($section)){continue;}
		list($k,$v)=preg_split('/\=/',$line,2);
		if($section=='@INC' && $k!='.'){$incpaths[]=$k;}
		if(!strlen($v) || stringContains($k,':')){continue;}
		//echo $section.'<br>'.$line;exit;
		$perlinfo[$section][$k]=$v;
	}
	if(count($incpaths)){
		$perlinfo['Platform']['incpaths']=implode('<br>',$incpaths);
		foreach($incpaths as $incpath){
			$files=listFilesEx($incpath,array('ext'=>'pm'));
			foreach($files as $file){
				$section=getFileName($file['name'],1);
				$k=strtolower($section);
				$menu[$k]=$section;
				$perlinfo[$section]=array(
					'Name'=>$file['name'],
					'Location'=>$file['afile']
				);
				//parse the file a bit to figure out version, author etc
				// Open the file for reading
				$maxloops=1000;
				$loops=0;
				$pod=array();
				if($fh = fopen($file['afile'], 'rb')){
					$head='';
					$items=array();
					$list=0;
					while( $line = fgets($fh)){
						//version?
						if(!isset($perlinfo[$section]['Version']) && preg_match('/VERSION[\s\t]*?\=[\s\t]*?([0-9\'\"\.\_]+?)\;/is',$line,$m)){
							$perlinfo[$section]['Version']=preg_replace('/(\'|\")/','',$m[1]);
							continue;
						}
						if(preg_match('/^\=head[0-9](.+)$/',trim($line),$m)){
							if(strlen($head) && is_array($items) && count($items)){
								//echo printValue($items);exit;
								$pod[$head][]='<ul>'.PHP_EOL;
								foreach($items as $lv){
									$pod[$head][]='<li>'.$lv.'</li>';
								}
								$pod[$head][]='</ul>'.PHP_EOL;
								$items=array();
							}
							$head=strtolower(trim($m[1]));
							$pod[$head]=array();
							$list=0;
							continue;
						}
						if(!strlen($head)){continue;}
						if(!strlen(trim($line))){continue;}
						if(preg_match('/^\=cut/',trim($line))){
							$maxloops=25;
							$loops=0;
							$head='';
							$item='';
							$list=0;
							continue;
						}
						if(preg_match('/^\=over/',trim($line))){
							$list=1;
							continue;
						}
						if(preg_match('/^\=back/',trim($line))){
							if(strlen($head) && is_array($items) && count($items)){
								//echo printValue($items);exit;
								$pod[$head][]='<ul>'.PHP_EOL;
								foreach($items as $lv){
									$pod[$head][]='<li>'.$lv.'</li>';
								}
								$pod[$head][]='</ul>'.PHP_EOL;
								$items=array();
							}
							$list=0;
							$item='';
							continue;
						}
						if(preg_match('/^\=item(.+)$/',trim($line),$m)){
							$items=array();
							continue;
						}
						$line=trim($line);
						$line=preg_replace('/I\<(.+?)\>/','<i>\1</i>',$line);
						$line=preg_replace('/B\<(.+?)\>/','<b>\1</b>',$line);
						$line=preg_replace('/L\<(.+?)\>/','\1',$line);
						$line=preg_replace('/E\<(.+?)\>/','&\1;',$line);
						$line=preg_replace('/F\<(.+?)\>/','<u>\1</u>',$line);
						$line=preg_replace('/S\<(.+?)\>/','<span class="w_nowrap">\1</span>',$line);
						$line=preg_replace('/C\<(.+?)\>/','<code>\1</code>',$line);
						if($list==1){
							$items[]=utf8_encode($line);
							continue;
						}
						$pod[$head][]=utf8_encode($line);
						$loops+=1;
						if($loops >= $maxloops){break;}
					}
					fclose($fh);
				}
				if(isset($pod['name'][0])){
					$perlinfo[$section]['Name']=implode(' '.PHP_EOL,$pod['name']);
				}
				if(isset($pod['description'][0])){
					$perlinfo[$section]['Description']=implode(' '.PHP_EOL,$pod['description']);
				}
				if(isset($pod['caveats'][0])){
					$perlinfo[$section]['Caveats']=implode(' '.PHP_EOL,$pod['caveats']);
				}
				if(isset($pod['notes'][0])){
					$perlinfo[$section]['Notes']=implode(' '.PHP_EOL,$pod['notes']);
				}
				if(isset($pod['author'][0])){
					$perlinfo[$section]['Author(s)']=implode(' '.PHP_EOL,$pod['author']);
				}
				elseif(isset($pod['authors'][0])){
					$perlinfo[$section]['Author(s)']=implode(' '.PHP_EOL,$pod['authors']);
				}
			}
			//echo $incpath.printValue($files);exit;
		}
	}
	ksort($menu);
	$out=cmdResults("perl -v 2>&1");
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
	$sections=array();
	foreach($perlinfo as $name=>$info){
		$section="<h2><a name=\"module_{$name}\">{$name}</a></h2>";
		$section.='<table>'.PHP_EOL;
		foreach($info as $k=>$v){
			$section.='<tr><td class="align-left w_small w_nowrap" style="background-color:#003e624D;width:300px;">'.$k.'</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">'.$v.'</td></tr>'.PHP_EOL;
		}
		$section.='</table>'.PHP_EOL;
		$sections[]=$section;
	}
	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;
	$data.=implode('',$sections);
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
		if(!is_array($json)){
			$out=cmdResults('npm.cmd ls --g --json');
			$json=decodeJSON($out['stdout']);
		}
	}
	else{
		$out=cmdResults('npm ls --g --json');
		$json=decodeJSON($out['stdout']);
	}
	if(!is_array($json)){return array('',array());}
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
		$out=cmdResults('luarocks list --porcelain 2>&1');
		$lines=preg_split('/[\r\n]+/',$out['stdout']);
	}
	$out=cmdResults("lua -v 2>&1");
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
		if(!strlen($module)){continue;}
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
function langRInfo(){
	//get pythoninfo contents
	$rpath=getWasqlPath('R');
	$out=cmdResults("rscript \"{$rpath}/rinfo.R\" 2>&1");
	//echo printValue($out);exit;
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
?>
