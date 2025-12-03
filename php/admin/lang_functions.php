<?php
function langLinuxOSName(){
	if(isWindows()){return 'windows';}
	$out=cmdResults('cat /etc/os-release');
	$ini='[os]'.PHP_EOL.$out['stdout'];
	$info=commonParseIni($ini);
	if(isset($info['os']['name'])){return $info['os']['name'];}
	return 'unknown';
}
function langFindJulia(){
	// Try to find julia command
	$check=isWindows()?'where julia 2>nul':'which julia 2>/dev/null';
	$test=cmdResults($check);
	// On Windows, also check common installation locations
	if(isWindows() && empty(trim($test['stdout']))){
		$common_paths=array(
			getenv('LOCALAPPDATA').'\\Microsoft\\WindowsApps\\julia.exe',
			getenv('APPDATA').'\\Microsoft\\WindowsApps\\julia.exe',
			'C:\\Program Files\\Julia\\bin\\julia.exe',
			'C:\\Julia\\bin\\julia.exe'
		);
		foreach($common_paths as $path){
			if(file_exists($path)){
				return '"'.$path.'"';
			}
		}
	}
	// Return full path if found
	if(trim($test['stdout'])){
		$paths=preg_split('/[\r\n]+/',trim($test['stdout']));
		if(isset($paths[0]) && strlen(trim($paths[0]))){
			return '"'.trim($paths[0]).'"';
		}
	}
	// Fallback to just 'julia' command
	return 'julia';
}
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
	// Check if python3 exists
	$check=isWindows()?'where python3 2>nul':'which python3 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#306998;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-python"></span> Python</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Python3 is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Download Python from <a href="https://www.python.org/downloads/" target="_blank" class="w_link">python.org/downloads</a></li><li>Run the installer</li><li>Check "Add Python to PATH" during installation</li><li>Restart your terminal/command prompt</li></ol>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
					$instructions.='<p><b>AlmaLinux:</b></p><pre>dnf install python3</pre>';
				break;
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>yum install python3</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>apt-get update<br>apt-get install python3</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	//get pythoninfo contents
	$pypath=getWasqlPath('python');
	$pyfile="{$pypath}/pythoninfo.py";
	// Check if pythoninfo.py exists
	if(!file_exists($pyfile)){
		$header='<header class="align-left"><div style="background:#306998;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-python"></span> Python</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Script Missing</div></div></header>';
		return array('<div class="align-center w_error" style="width:934px;">'.$header.'<div class="w_padding">pythoninfo.py script not found at: '.encodeHtml($pyfile).'</div></div>',array());
	}
	$out=cmdResults("python3 \"{$pyfile}\" 2>&1");
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
function langRubyInfo(){
	// Check if ruby exists
	$check=isWindows()?'where ruby 2>nul':'which ruby 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#CC342D;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-ruby is-white"></span> Ruby</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Ruby is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Download Ruby+Devkit from <a href="https://rubyinstaller.org/" target="_blank" class="w_link">rubyinstaller.org</a></li><li>Run the installer (includes RubyGems)</li><li>Follow the installation wizard</li><li>Restart your terminal/command prompt</li></ol>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
					$instructions.='<p><b>AlmaLinux:</b></p><pre>dnf install ruby ruby-devel</pre>';
				break;
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>yum install ruby ruby-devel</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>apt-get update<br>apt-get install ruby-full</pre><p>Or use rbenv/rvm for version management:</p><pre>curl -fsSL https://github.com/rbenv/rbenv-installer/raw/HEAD/bin/rbenv-installer | bash</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	// Check if gem command exists
	$check=isWindows()?'where gem 2>nul':'which gem 2>/dev/null';
	$test=cmdResults($check);
	$out=cmdResults("ruby -v 2>&1");
	$version=$out['stdout'];
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#CC342D;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-ruby is-white"></span> Ruby</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version '.$version.'</div></div></header>';
		return array('<div class="align-center" style="width:934px;">'.$header.'<div class="w_padding w_error">RubyGems (gem command) is not available. It should be included with Ruby installation.</div></div>',array());
	}
	$modules=array();
	$menu=array();
	// Use Ruby one-liner to get all gem info in one command (much faster than individual gem info calls)
	// This outputs JSON array with name, versions, author, homepage, and description for all installed gems
	$ruby_cmd='ruby -W0 -rjson -e "specs=Gem::Specification.all; gems={}; specs.each{|s| k=s.name.downcase; if !gems[k] || Gem::Version.new(s.version) > Gem::Version.new(gems[k][\'version\']) then gems[k]={\'name\'=>s.name,\'version\'=>s.version.to_s,\'versions\'=>[],\'author\'=>(s.authors.is_a?(Array) ? s.authors.join(\', \') : s.authors.to_s),\'homepage\'=>s.homepage.to_s,\'description\'=>s.description.to_s.gsub(/[\\r\\n]+/,\' \')}; end; }; specs.each{|s| k=s.name.downcase; gems[k][\'versions\'] << s.version.to_s if gems[k]; }; gems.each{|k,g| g[\'versions\']=g[\'versions\'].uniq.sort{|a,b| Gem::Version.new(b) <=> Gem::Version.new(a)}.join(\', \'); }; puts JSON.generate(gems.values)"';
	$output=shell_exec($ruby_cmd);
	$gem_data=array();
	if($output && !stringContains($output,'command not found')){
		// Remove any warning lines that might appear before the JSON
		$lines=preg_split('/[\r\n]+/',trim($output));
		foreach($lines as $i=>$line){
			if(substr(trim($line),0,1)==='['){
				// Found the JSON start, take everything from here
				$output=implode("\n",array_slice($lines,$i));
				break;
			}
		}
		$gem_data=json_decode($output,true);
		if(!is_array($gem_data)){
			$gem_data=array();
		}
	}
	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background:#CC342D;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-ruby is-white"></span> Ruby</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;
	foreach($gem_data as $gem){
		$gemname=$gem['name'];
		$version=$gem['version'];
		$versions=$gem['versions'];
		$author=encodeHtml($gem['author']);
		$homepage=encodeHtml($gem['homepage']);
		$description=encodeHtml($gem['description']);

		if(!strlen($gemname)){continue;}
		$k=strtolower($gemname);

		$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$gemname}">{$gemname}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#CC342D4D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$gemname}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#CC342D4D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$version}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#CC342D4D;width:300px;">All Versions</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$versions}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#CC342D4D;width:300px;">Author</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$author}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#CC342D4D;width:300px;">Description</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$description}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#CC342D4D;width:300px;">Homepage</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;word-break:break-all;">{$homepage}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$gemname;
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
	// Check if perl exists
	$check=isWindows()?'where perl 2>nul':'which perl 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#003e62;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-perl"></span> Perl</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Perl is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Download Strawberry Perl from <a href="https://strawberryperl.com/" target="_blank" class="w_link">strawberryperl.com</a></li><li>Run the installer (MSI package)</li><li>Restart your terminal/command prompt</li></ol><p>Alternative: <a href="https://www.activestate.com/products/perl/" target="_blank" class="w_link">ActivePerl</a></p>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
					$instructions.='<p><b>AlmaLinux:</b></p><pre>dnf install perl</pre>';
				break;
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>yum install perl</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>apt-get update<br>apt-get install perl</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	$modules=array();
	$menu=array();
	$output=shell_exec('perl -V 2>&1');
	if(!$output){
		$header='<header class="align-left"><div style="background:#003e62;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-perl"></span> Perl</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Error</div></div></header>';
		return array('<div class="align-center" style="width:934px;">'.$header.'<div class="w_padding w_error">Failed to execute perl -V command</div></div>',array());
	}
	$lines=preg_split('/[\r\n]+/',$output);
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
		$parts=preg_split('/\=/',$line,2);
		if(count($parts) < 1){continue;}
		$k=isset($parts[0])?trim($parts[0]):'';
		$v=isset($parts[1])?trim($parts[1]):'';
		if($section=='@INC' && $k!='.'){$incpaths[]=$k;}
		if(!strlen($v) || stringContains($k,':')){continue;}
		//echo $section.'<br>'.$line;exit;
		$perlinfo[$section][$k]=$v;
	}
	if(count($incpaths)){
		$perlinfo['Platform']['incpaths']=implode('<br>',$incpaths);
		// Use Perl to find all .pm files in @INC paths
		// Filter out empty paths
		$incpaths = array_filter($incpaths, function($p){ return strlen(trim($p)) > 0; });
		$inc_list = implode(' ', array_map(function($p){ return escapeshellarg($p); }, $incpaths));
		// Use escapeshellarg to properly escape the Perl code and chr(10) for newlines
		$perl_code = 'use File::Find; find(sub { print $File::Find::name, chr(10) if /\.pm$/}, @ARGV)';
		$find_cmd = 'perl -e ' . escapeshellarg($perl_code) . ' ' . $inc_list . ' 2>&1';
		$module_files = shell_exec($find_cmd);
		if($module_files){
			$file_list = preg_split('/[\r\n]+/', trim($module_files));
			foreach($file_list as $filepath){
				if(!strlen($filepath)){continue;} // Skip empty lines
				$filename = basename($filepath);
				$section = getFileName($filename, 1);
				$k = strtolower($section);
				if(isset($menu[$k])){continue;} // Skip duplicates
				$menu[$k] = $section;
				$perlinfo[$section] = array(
					'Name' => $filename,
					'Location' => $filepath
				);
				//parse the file a bit to figure out version, author etc
				// Note: Skip file parsing on Windows/MSYS as Unix paths aren't accessible to PHP
				// Open the file for reading
				$maxloops = 1000;
				$loops = 0;
				$pod = array();
				if(@$fh = fopen($filepath, 'rb')){
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
							$items[]=$line;
							continue;
						}
						$pod[$head][]=$line;
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
	$out=shell_exec("perl -v 2>&1");
	$version='';
	if(preg_match('/\(v([0-9\.]+?)\)/',$out,$m)){
		$version=$m[1];
	}
	elseif(preg_match('/\ v([0-9\.]+?)\ /',$out,$m)){
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
	// Check if node exists
	$nodecmd=isWindows()?'node':'node';
	$check=isWindows()?'where node 2>nul':'which node 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background-color:#000000;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="brand-node-dot-js"></span> Node</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Node.js is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Download Node.js LTS from <a href="https://nodejs.org/" target="_blank" class="w_link">nodejs.org</a></li><li>Run the installer (MSI package)</li><li>Follow the installation wizard</li><li>Restart your terminal/command prompt</li></ol>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
					$instructions.='<p><b>AlmaLinux:</b></p><pre>dnf install nodejs npm</pre>';
				break;
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>yum install nodejs npm</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>apt-get update<br>apt-get install nodejs npm</pre><p>Or use NodeSource repository for latest version:</p><pre>curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -<br>apt-get install -y nodejs</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	$modules=array();
	$menu=array();
	// Get node modules
	$npmcmd=isWindows()?'npm.cmd':'npm';
	$out=shell_exec("{$npmcmd} ls --g --json 2>&1");
	$json=decodeJSON($out);
	if(!is_array($json)){
		$json=array();
	}
	$version_out=shell_exec("{$nodecmd} -v 2>&1");
	$version=trim($version_out);
	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background-color:#000000;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="brand-node-dot-js"></span> Node</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;

	// Get built-in Node.js modules
	$builtin_out=shell_exec("{$nodecmd} -p \"require('module').builtinModules.join('\\n')\" 2>&1");
	if($builtin_out){
		$builtin_modules=preg_split('/[\r\n]+/',trim($builtin_out));
		foreach($builtin_modules as $module){
			if(!strlen(trim($module))){continue;}
			// Skip internal modules that start with underscore
			if(strpos($module,'_')===0){continue;}
			$k=strtolower($module);
			$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$module}">{$module}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Name</td><td style="text-align:left;min-width:300px;background-color:#CCCCCC80;">{$module}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Type</td><td style="text-align:left;min-width:300px;background-color:#CCCCCC80;">Built-in Core Module</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Version</td><td style="text-align:left;min-width:300px;background-color:#CCCCCC80;">{$version}</td></tr>
</table>
ENDOFSECTION;
			$menu[$k]=$module;
		}
	}

	// Add npm global modules if available
	if(is_array($json) && isset($json['dependencies']) && is_array($json['dependencies'])){
		foreach($json['dependencies'] as $module=>$info){
		$k=strtolower($module);
		$version=isset($info['version'])?$info['version']:'';
		$dependencies=array();
		if(isset($info['dependencies']) && is_array($info['dependencies'])){
			foreach($info['dependencies'] as $dname=>$dinfo){
				$dversion=isset($dinfo['version'])?$dinfo['version']:'unknown';
				$dependencies[]="{$dname} - v{$dversion}";
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
	// Check if lua exists
	$check=isWindows()?'where lua 2>nul':'which lua 2>/dev/null';
	$test=shell_exec($check);
	if(empty(trim($test))){
		$header='<header class="align-left"><div style="background:#ccc;padding:10px 20px;margin-bottom:20px;border:1px solid #999;"><div style="font-size:clamp(24px,3vw,48px);color:#2c2d72"><span class="brand-lua"></span> Lua</div><div style="font-size:clamp(11px,2vw,18px);color:#2c2d72">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Lua is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Download Lua binaries from <a href="http://luabinaries.sourceforge.net/" target="_blank" class="w_link">luabinaries.sourceforge.net</a></li><li>Extract the ZIP file to a folder (e.g., C:\\Lua)</li><li>Add the Lua folder to your PATH environment variable</li><li>Restart your terminal/command prompt</li></ol><p>Alternative: Use <a href="https://github.com/rjpcomputing/luaforwindows" target="_blank" class="w_link">Lua for Windows</a> (includes LuaRocks)</p>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
					$instructions.='<p><b>AlmaLinux:</b></p><pre>dnf install lua</pre>';
				break;
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>yum install lua</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>apt-get update<br>apt-get install lua5.4</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	// Get Lua version
	$version_out=shell_exec("lua -v 2>&1");
	$version=trim($version_out);
	$modules=array();
	$menu=array();

	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background:#ccc;padding:10px 20px;margin-bottom:20px;border:1px solid #999;">
		<div style="font-size:clamp(24px,3vw,48px);color:#2c2d72"><span class="brand-lua"></span> Lua</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#2c2d72">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;

	// Add built-in Lua standard libraries
	$builtin_libraries = array('string', 'table', 'math', 'io', 'os', 'debug', 'coroutine', 'package', 'utf8');
	foreach($builtin_libraries as $library){
		$k=strtolower($library);
		$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$library}">{$library}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$library}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Type</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">Built-in Standard Library</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$version}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$library;
	}

	// Get LuaRocks modules
	$out=shell_exec('luarocks list --porcelain 2>&1');
	$lines=array();
	if($out){
		$lines=preg_split('/[\r\n]+/',$out);
	}

	// Add LuaRocks modules if available
	if(is_array($lines)){
		foreach($lines as $line){
		$line=trim($line);
		if(!strlen($line)){continue;}
		// Parse porcelain format: module\tversion\tstatus\tpath
		// Split by tab first, fall back to multiple spaces
		$parts=preg_split('/\t+/',$line);
		if(count($parts) < 2){
			// Fallback to splitting by multiple spaces if tab splitting didn't work
			$parts=preg_split('/\s{2,}/',$line);
		}
		if(count($parts) < 2){
			// Single space split as last resort
			$parts=preg_split('/\s+/',$line,4);
		}
		$module=isset($parts[0])?trim($parts[0]):'';
		$version=isset($parts[1])?trim($parts[1]):'';
		$status=isset($parts[2])?trim($parts[2]):'';
		$path=isset($parts[3])?trim($parts[3]):'';

		if(!strlen($module)){continue;}
		$k=strtolower($module);
		$info='';
		$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$module}">{$module}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$module}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$version}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Status</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$status}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#0000004D;width:300px;">Location</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;word-break:break-all;">{$path}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$module;
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
function langRInfo(){
	// Check if Rscript exists
	$check=isWindows()?'where Rscript 2>nul':'which Rscript 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#165CAA;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-r is-white"></span> R</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>R is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Download R from <a href="https://cran.r-project.org/bin/windows/base/" target="_blank" class="w_link">CRAN</a></li><li>Run the installer (EXE package)</li><li>Follow the installation wizard</li><li>Restart your terminal/command prompt</li></ol><p>Optional: Install <a href="https://posit.co/download/rstudio-desktop/" target="_blank" class="w_link">RStudio</a> for an IDE</p>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
					$instructions.='<p><b>AlmaLinux:</b></p><pre>dnf install epel-release<br>dnf install R</pre>';
				break;
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>yum install epel-release<br>yum install R</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>apt-get update<br>apt-get install r-base r-base-dev</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	//get rinfo contents
	$rpath=getWasqlPath('R');
	$rfile="{$rpath}/rinfo.R";
	// Check if rinfo.R exists
	if(!file_exists($rfile)){
		$header='<header class="align-left"><div style="background:#165CAA;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-r is-white"></span> R</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Script Missing</div></div></header>';
		return array('<div class="align-center w_error" style="width:934px;">'.$header.'<div class="w_padding">rinfo.R script not found at: '.encodeHtml($rfile).'</div></div>',array());
	}
	$out=cmdResults("rscript \"{$rfile}\" 2>&1");
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
function langJuliaInfo(){
	// Check if julia exists
	$juliacmd=langFindJulia();
	$check_result=cmdResults("{$juliacmd} --version 2>&1");
	if(empty(trim($check_result['stdout'])) || stringContains($check_result['stdout'],'not found') || stringContains($check_result['stdout'],'not recognized')){
		$header='<header class="align-left"><div style="background:#9558B2;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-julia is-white"></span> Julia</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Julia is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Download Julia from <a href="https://julialang.org/downloads/" target="_blank" class="w_link">julialang.org/downloads</a></li><li>Run the installer (EXE package)</li><li>Follow the installation wizard</li><li>Add Julia to PATH during installation</li><li>Restart your terminal/command prompt</li></ol>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
					$instructions.='<p><b>AlmaLinux:</b></p><pre>wget https://julialang-s3.julialang.org/bin/linux/x64/1.10/julia-1.10.0-linux-x86_64.tar.gz<br>tar zxvf julia-1.10.0-linux-x86_64.tar.gz<br>sudo cp -r julia-1.10.0 /opt/<br>sudo ln -s /opt/julia-1.10.0/bin/julia /usr/local/bin/julia</pre>';
				break;
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>wget https://julialang-s3.julialang.org/bin/linux/x64/1.10/julia-1.10.0-linux-x86_64.tar.gz<br>tar zxvf julia-1.10.0-linux-x86_64.tar.gz<br>sudo cp -r julia-1.10.0 /opt/<br>sudo ln -s /opt/julia-1.10.0/bin/julia /usr/local/bin/julia</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>wget https://julialang-s3.julialang.org/bin/linux/x64/1.10/julia-1.10.0-linux-x86_64.tar.gz<br>tar zxvf julia-1.10.0-linux-x86_64.tar.gz<br>sudo cp -r julia-1.10.0 /opt/<br>sudo ln -s /opt/julia-1.10.0/bin/julia /usr/local/bin/julia</pre><p>Or use official PPA:</p><pre>sudo add-apt-repository ppa:staticfloat/juliareleases<br>sudo apt-get update<br>sudo apt-get install julia</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	$modules=array();
	$menu=array();
	// Use the version we already got from the check
	$version=$check_result['stdout'];

	// Write Julia code to temp file to avoid quoting issues
	$tpath=getWasqlTempPath();
	$julia_script="{$tpath}/julia_info_".uniqid().".jl";
	$julia_code=<<<'JULIASCRIPT'
using Pkg
println("===STDLIB===")
stdlib_pkgs = Pkg.Types.stdlibs()
pkg_list = sort(collect(Set(values(stdlib_pkgs))))
for pkg in pkg_list
    println(pkg)
end
println("===USER===")
Pkg.status()
JULIASCRIPT;
	setFileContents($julia_script,$julia_code);

	// Execute Julia script
	$cmd="{$juliacmd} \"{$julia_script}\" 2>&1";
	$out=cmdResults($cmd);
	$combined_output=$out['stdout'];

	// Clean up temp file
	@unlink($julia_script);

	// Split output into stdlib and user sections
	$parts=preg_split('/===USER===/',preg_replace('/===STDLIB===/','',$combined_output));
	$stdlib_data=isset($parts[0])?trim($parts[0]):'';
	$pkgdata=isset($parts[1])?trim($parts[1]):'';


	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background:#9558B2;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-julia is-white"></span> Julia</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;

	// Parse standard library packages
	$stdlib_lines=preg_split('/[\r\n]+/',$stdlib_data);
	$stdlib_count=0;
	foreach($stdlib_lines as $line){
		$line=trim($line);
		if(!strlen($line)){continue;}
		// Parse format: ("PackageName", v"1.2.3")
		if(preg_match('/^\(\"(.+?)\",\s*v\"(.+?)\"\)$/i',$line,$m)){
			$pkgname=trim($m[1]);
			$pkgver=trim($m[2]);

			if(!strlen($pkgname)){continue;}
			$k='stdlib_'.strtolower($pkgname);
			$stdlib_count++;

			$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$pkgname}">{$pkgname} <span style="color:#9558B2;font-size:0.6em;">[Standard Library]</span></a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$pkgname}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$pkgver}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">Type</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">Built-in Standard Library</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">Documentation</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;"><a href="https://docs.julialang.org/en/v1/stdlib/{$pkgname}/" target="_blank" class="w_link">{$pkgname} Docs</a></td></tr>
</table>
ENDOFSECTION;
			$menu[$k]=$pkgname;
		}
	}

	// Parse user-installed packages
	$user_count=0;
	$user_lines=preg_split('/[\r\n]+/',$pkgdata);
	foreach($user_lines as $line){
		$line=trim($line);
		if(!strlen($line)){continue;}
		// Parse Pkg.status() format: [hash] PackageName v1.2.3
		if(preg_match('/^\[([a-f0-9]+)\]\s+(\S+)\s+v?(.+?)$/i',$line,$m)){
			$hash=trim($m[1]);
			$pkgname=trim($m[2]);
			$pkgver=trim($m[3]);

			if(!strlen($pkgname)){continue;}
			$k='user_'.strtolower($pkgname);
			$user_count++;

			$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$pkgname}">{$pkgname} <span style="color:#4ade80;font-size:0.6em;">[User Installed]</span></a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$pkgname}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$pkgver}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">Type</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">User Installed Package</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">UUID</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$hash}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#9558B24D;width:300px;">JuliaHub</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;"><a href="https://juliahub.com/ui/Packages/{$pkgname}" target="_blank" class="w_link">{$pkgname}</a></td></tr>
</table>
ENDOFSECTION;
			$menu[$k]=$pkgname;
		}
	}

	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;

	// Add summary section
	$data.='<div class="w_padding" style="background:#9558B21A;margin-bottom:20px;padding:15px;border-left:4px solid #9558B2;">';
	$data.='<h3 style="margin-top:0;">Package Summary</h3>';
	$data.='<p><b>Standard Library Packages:</b> '.$stdlib_count.' (built-in with Julia)</p>';
	$data.='<p><b>User-Installed Packages:</b> '.$user_count.' (installed via Pkg.add)</p>';
	if($user_count==0){
		$data.='<p style="color:#666;font-style:italic;">To install packages: <code>julia -e "using Pkg; Pkg.add(\"PackageName\")"</code></p>';
	}
	$data.='</div>';

	ksort($modules);
	ksort($menu);
	foreach($modules as $k=>$section){
		$data.=$section;
	}
	$data.='</div>';
	return array($data,$menu);
}
function langBashInfo(){
	// Check if bash exists
	$check=isWindows()?'where bash 2>nul':'which bash 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#4EAA25;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-bash is-white"></span> Bash</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Bash is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Install Git for Windows from <a href="https://git-scm.com/download/win" target="_blank" class="w_link">git-scm.com</a> (includes Git Bash)</li><li>Or install Windows Subsystem for Linux (WSL): <pre>wsl --install</pre></li><li>Restart your terminal</li></ol>';
		}
		else{
			$instructions.='<p><b>Linux/Unix:</b> Bash is usually pre-installed. Check your PATH or install bash package.</p>';
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	$modules=array();
	$menu=array();
	$out=cmdResults("bash --version 2>&1");
	$lines=preg_split('/[\r\n]+/',$out['stdout']);
	$version=isset($lines[0])?$lines[0]:'Unknown';
	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background:#4EAA25;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-bash is-white"></span> Bash</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">{$version}</div>
	</div>
</header>
ENDOFHEADER;
	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;
	$data.='<div class="w_padding"><p>Bash is a Unix shell and command language. Modules are typically system packages or custom scripts.</p></div>';
	$data.='</div>';
	return array($data,$menu);
}
function langPowershellInfo(){
	// Check if powershell exists
	$check=isWindows()?'where powershell 2>nul':'which pwsh 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#5391FE;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-powershell is-white"></span> PowerShell</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>PowerShell is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b> PowerShell 5.1 comes pre-installed on Windows 10/11. For PowerShell Core 7+:</p><ol><li>Download from <a href="https://github.com/PowerShell/PowerShell/releases" target="_blank" class="w_link">PowerShell Releases</a></li><li>Run the MSI installer</li><li>Follow the installation wizard</li></ol>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>sudo dnf install powershell</pre><p>Or download RPM from <a href="https://github.com/PowerShell/PowerShell/releases" target="_blank" class="w_link">PowerShell Releases</a></p>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>sudo apt-get update<br>sudo apt-get install -y wget apt-transport-https software-properties-common<br>wget -q https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/packages-microsoft-prod.deb<br>sudo dpkg -i packages-microsoft-prod.deb<br>sudo apt-get update<br>sudo apt-get install -y powershell</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	$modules=array();
	$menu=array();
	$cmd=isWindows()?"powershell -Command \"\$PSVersionTable.PSVersion.ToString()\"":"pwsh -Command \"\$PSVersionTable.PSVersion.ToString()\"";
	$out=cmdResults($cmd);
	$version=trim($out['stdout']);
	if(empty($version) || stringContains($version,'Major')){
		// Fallback to formatted string if ToString() doesn't work
		$cmd=isWindows()?"powershell -Command \"Write-Output \\\"$(\$PSVersionTable.PSVersion.Major).$(\$PSVersionTable.PSVersion.Minor).$(\$PSVersionTable.PSVersion.Build).$(\$PSVersionTable.PSVersion.Revision)\\\"\"":"pwsh -Command \"Write-Output \\\"$(\$PSVersionTable.PSVersion.Major).$(\$PSVersionTable.PSVersion.Minor).$(\$PSVersionTable.PSVersion.Build).$(\$PSVersionTable.PSVersion.Revision)\\\"\"";
		$out=cmdResults($cmd);
		$version=trim($out['stdout']);
	}
	// Get installed modules
	$listcmd=isWindows()?"powershell -Command \"Get-Module -ListAvailable | Select-Object Name,Version | Sort-Object Name | Format-Table -AutoSize\"":"pwsh -Command \"Get-Module -ListAvailable | Select-Object Name,Version | Sort-Object Name | Format-Table -AutoSize\"";
	$out=cmdResults($listcmd);
	$pkgdata=$out['stdout'];
	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background:#5391FE;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-powershell is-white"></span> PowerShell</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">{$version}</div>
	</div>
</header>
ENDOFHEADER;
	$lines=preg_split('/[\r\n]+/',$pkgdata);
	foreach($lines as $line){
		$line=trim($line);
		if(!strlen($line) || stringBeginsWith($line,'Name') || stringBeginsWith($line,'---')){continue;}
		$parts=preg_split('/\s+/',$line,2);
		if(count($parts)<2){continue;}
		$modname=trim($parts[0]);
		$modver=trim($parts[1]);
		if(!strlen($modname)){continue;}
		$k=strtolower($modname);
		$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$modname}">{$modname}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#5391FE4D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$modname}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#5391FE4D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$modver}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#5391FE4D;width:300px;">PowerShell Gallery</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;"><a href="https://www.powershellgallery.com/packages/{$modname}" target="_blank" class="w_link">{$modname}</a></td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$modname;
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
function langGroovyInfo(){
	// Check if groovy exists
	$check=isWindows()?'where groovy 2>nul':'which groovy 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#4298B8;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-groovy is-white"></span> Groovy</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Groovy is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Install using SDKMAN: <pre>sdk install groovy</pre></li><li>Or download binary from <a href="https://groovy.apache.org/download.html" target="_blank" class="w_link">groovy.apache.org</a></li><li>Extract and add bin directory to PATH</li><li>Requires Java JDK 8+</li></ol>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>curl -s "https://get.sdkman.io" | bash<br>source "$HOME/.sdkman/bin/sdkman-init.sh"<br>sdk install groovy</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>sudo apt-get install groovy</pre><p>Or using SDKMAN:</p><pre>curl -s "https://get.sdkman.io" | bash<br>source "$HOME/.sdkman/bin/sdkman-init.sh"<br>sdk install groovy</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}

	// Groovy is installed, now check if it can run (requires Java JDK)
	$modules=array();
	$menu=array();
	$groovy_cmd=isWindows()?'groovy.bat':'groovy';
	$out=cmdResults("{$groovy_cmd} --version 2>&1");
	$version=$out['stdout'];

	// Check for JAVA_HOME or javac errors
	if(stringContains($version,'JAVA_HOME not set') || stringContains($version,'cannot find javac')){
		$header='<header class="align-left"><div style="background:#4298B8;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-groovy is-white"></span> Groovy</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Java JDK Required</div></div></header>';
		$os=langLinuxOSName();

		// Check if java runtime exists
		$java_check=cmdResults(isWindows()?'where java 2>nul':'which java 2>/dev/null');
		$javac_check=cmdResults(isWindows()?'where javac 2>nul':'which javac 2>/dev/null');
		$has_java=!empty(trim($java_check['stdout']));
		$has_javac=!empty(trim($javac_check['stdout']));

		$instructions='<div class="w_padding">';
		$instructions.='<div class="w_error" style="margin-bottom:20px;padding:15px;"><b>Error:</b> Groovy is installed but cannot run.<br><pre style="margin-top:10px;">'.encodeHtml($version).'</pre></div>';
		$instructions.='<h3>Groovy requires Java JDK (not just JRE)</h3>';
		$instructions.='<p><b>Current Status:</b></p><ul>';
		$instructions.='<li>✓ Groovy is installed at: '.encodeHtml(trim($test['stdout'])).'</li>';
		if($has_java){
			$java_version=cmdResults('java -version 2>&1');
			$instructions.='<li>✓ Java Runtime (JRE) is installed</li>';
			$instructions.='<li style="color:#999;margin-left:20px;">'.encodeHtml(trim(preg_split('/[\r\n]+/',$java_version['stdout'])[0])).'</li>';
		}
		else{
			$instructions.='<li>✗ Java Runtime (JRE) is NOT installed</li>';
		}
		if($has_javac){
			$instructions.='<li>✓ Java Compiler (javac) is available</li>';
		}
		else{
			$instructions.='<li><b style="color:#c00;">✗ Java Compiler (javac) is NOT available - THIS IS THE PROBLEM</b></li>';
		}
		$instructions.='</ul>';

		$instructions.='<h4>Solution: Install Java JDK</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows - Option 1: Oracle JDK</b></p>';
			$instructions.='<ol><li>Download JDK from <a href="https://www.oracle.com/java/technologies/downloads/" target="_blank" class="w_link">Oracle JDK Downloads</a></li>';
			$instructions.='<li>Run the installer (choose the full JDK, not just JRE)</li>';
			$instructions.='<li>Set JAVA_HOME environment variable:<pre>setx JAVA_HOME "C:\\Program Files\\Java\\jdk-21" /M</pre></li>';
			$instructions.='<li>Add to PATH:<pre>setx PATH "%PATH%;%JAVA_HOME%\\bin" /M</pre></li>';
			$instructions.='<li>Restart your terminal/command prompt</li></ol>';
			$instructions.='<p><b>Windows - Option 2: Eclipse Temurin (OpenJDK)</b></p>';
			$instructions.='<ol><li>Download from <a href="https://adoptium.net/" target="_blank" class="w_link">Adoptium Temurin</a></li>';
			$instructions.='<li>Run the MSI installer (includes automatic JAVA_HOME setup)</li>';
			$instructions.='<li>Restart your terminal</li></ol>';
			$instructions.='<p><b>Windows - Option 3: Using Chocolatey</b></p>';
			$instructions.='<pre>choco install temurin21</pre>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>sudo dnf install java-17-openjdk-devel</pre>';
					$instructions.='<p>Then set JAVA_HOME in ~/.bashrc:</p><pre>export JAVA_HOME=/usr/lib/jvm/java-17-openjdk<br>export PATH=$JAVA_HOME/bin:$PATH</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>sudo apt-get update<br>sudo apt-get install openjdk-17-jdk</pre>';
					$instructions.='<p>Then set JAVA_HOME in ~/.bashrc:</p><pre>export JAVA_HOME=/usr/lib/jvm/java-17-openjdk-amd64<br>export PATH=$JAVA_HOME/bin:$PATH</pre>';
				break;
			}
			$instructions.='<p><b>Or using SDKMAN (recommended):</b></p>';
			$instructions.='<pre>curl -s "https://get.sdkman.io" | bash<br>source "$HOME/.sdkman/bin/sdkman-init.sh"<br>sdk install java</pre>';
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}

	// Check for other errors
	$rtncode=isset($out['rtncode'])?$out['rtncode']:0;
	if(stringContains($version,'error') || stringContains($version,'Error') || $rtncode != 0){
		$header='<header class="align-left"><div style="background:#4298B8;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-groovy is-white"></span> Groovy</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Error</div></div></header>';
		$instructions='<div class="w_padding"><div class="w_error"><b>Error running Groovy:</b><pre style="margin-top:10px;">'.encodeHtml($version).'</pre></div></div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}

	// Success - Groovy is working
	// Parse version info
	$version_lines=preg_split('/[\r\n]+/',trim($version));
	$groovy_version='';
	$jvm_version='';
	$vendor='';
	$os_info='';
	foreach($version_lines as $line){
		if(preg_match('/Groovy Version:\s*(.+?)\s+JVM:\s*(.+?)\s+Vendor:\s*(.+?)\s+OS:\s*(.+?)$/i',$line,$m)){
			$groovy_version=$m[1];
			$jvm_version=$m[2];
			$vendor=$m[3];
			$os_info=$m[4];
		}
	}
	if(empty($groovy_version)){$groovy_version=$version;}

	// Get Java version
	$java_ver_out=cmdResults('java -version 2>&1');
	$java_version='Unknown';
	$java_stdout=isset($java_ver_out['stdout'])?$java_ver_out['stdout']:'';
	if(preg_match('/version "(.+?)"/i',$java_stdout,$m)){
		$java_version=$m[1];
	}

	// Find GROOVY_HOME
	$groovy_home='';
	$groovy_cmd_path=trim($test['stdout']);
	$groovy_paths=preg_split('/[\r\n]+/',$groovy_cmd_path);
	$groovy_cmd=$groovy_paths[0];
	// Try to determine GROOVY_HOME from groovy command path
	if(isWindows()){
		// On Windows: C:\path\to\groovy\bin\groovy.bat -> C:\path\to\groovy
		$groovy_home=dirname(dirname($groovy_cmd));
	}
	else{
		// On Unix: /path/to/groovy/bin/groovy -> /path/to/groovy
		$groovy_home=dirname(dirname($groovy_cmd));
	}

	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background:#4298B8;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-groovy is-white"></span> Groovy</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {$groovy_version}</div>
	</div>
</header>
ENDOFHEADER;

	// Get Groovy lib directory JARs (system packages)
	$lib_dir="{$groovy_home}/lib";
	$jar_files=array();
	if(is_dir($lib_dir)){
		$files=listFilesEx($lib_dir,array('ext'=>'jar'));
		foreach($files as $file){
			$jarname=getFileName($file['name'],1);
			$jarsize=verboseSize($file['size']);
			$k=strtolower($jarname);
			$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$jarname}">{$jarname} <span style="color:#4ade80;font-size:0.6em;">[System Library]</span></a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Name</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$jarname}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Type</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">System JAR Library</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Location</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;word-break:break-all;">{$file['afile']}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Size</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$jarsize}</td></tr>
</table>
ENDOFSECTION;
			$menu[$k]=$jarname;
		}
	}

	// Get Grape dependencies (user-installed packages)
	$grapes_dir=getenv('HOME').'/.groovy/grapes';
	if(isWindows()){
		$grapes_dir=getenv('USERPROFILE').'/.groovy/grapes';
	}
	$grape_count=0;
	if(is_dir($grapes_dir)){
		// List all grape packages
		$grape_dirs=glob($grapes_dir.'/*',GLOB_ONLYDIR);
		foreach($grape_dirs as $group_dir){
			$artifact_dirs=glob($group_dir.'/*',GLOB_ONLYDIR);
			foreach($artifact_dirs as $artifact_dir){
				$version_dirs=glob($artifact_dir.'/*',GLOB_ONLYDIR);
				foreach($version_dirs as $version_dir){
					$group=basename($group_dir);
					$artifact=basename($artifact_dir);
					$ver=basename($version_dir);
					$grape_count++;
					$k='grape_'.strtolower($group.'_'.$artifact);
					$grape_jars=listFilesEx($version_dir,array('ext'=>'jar','maxdepth'=>1));
					$jar_list='';
					foreach($grape_jars as $jar){
						$jar_list.=basename($jar['name']).' ('.verboseSize($jar['size']).')<br>';
					}
					if(empty($jar_list)){$jar_list='No JARs found';}
					$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$k}">{$group}:{$artifact} <span style="color:#9558B2;font-size:0.6em;">[Grape Dependency]</span></a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Group</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$group}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Artifact</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$artifact}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$ver}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Type</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">Grape Cached Dependency</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">Location</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;word-break:break-all;">{$version_dir}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#4298B84D;width:300px;">JAR Files</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$jar_list}</td></tr>
</table>
ENDOFSECTION;
					$menu[$k]="{$group}:{$artifact}";
				}
			}
		}
	}

	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;

	// Add summary section
	$system_lib_count=count($jar_files);
	if(isset($modules)){
		$total_modules=count($modules);
		$system_lib_count=$total_modules-$grape_count;
	}
	$data.='<div class="w_padding" style="background:#4298B81A;margin-bottom:20px;padding:15px;border-left:4px solid #4298B8;">';
	$data.='<h3 style="margin-top:0;">Groovy Environment</h3>';
	$data.='<p><b>Groovy Version:</b> '.encodeHtml($groovy_version).'</p>';
	$data.='<p><b>Java Version:</b> '.encodeHtml($java_version).' ('.$vendor.')</p>';
	$data.='<p><b>JVM:</b> '.encodeHtml($jvm_version).'</p>';
	$data.='<p><b>Groovy Home:</b> '.encodeHtml($groovy_home).'</p>';
	$data.='<p><b>System Libraries:</b> '.$system_lib_count.' JAR files in lib directory</p>';
	$data.='<p><b>Grape Dependencies:</b> '.$grape_count.' cached packages</p>';
	if($grape_count==0){
		$data.='<p style="color:#666;font-style:italic;">To install Grape packages, use <code>@Grab</code> annotation in your Groovy scripts</p>';
	}
	$data.='</div>';

	ksort($modules);
	ksort($menu);
	foreach($modules as $k=>$section){
		$data.=$section;
	}
	$data.='</div>';
	return array($data,$menu);
}
function langTclInfo(){
	// Check if tclsh exists
	$check=isWindows()?'where tclsh 2>nul':'which tclsh 2>/dev/null';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#1C71D8;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-tcl is-white"></span> Tcl</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Installed</div></div></header>';
		$os=langLinuxOSName();
		$instructions='<div class="w_padding"><h3>Tcl is not installed or not in PATH</h3><h4>Installation Instructions:</h4>';
		if(isWindows()){
			$instructions.='<p><b>Windows:</b></p><ol><li>Download ActiveTcl from <a href="https://www.activestate.com/products/tcl/" target="_blank" class="w_link">ActiveState</a></li><li>Or download from <a href="https://www.tcl.tk/software/tcltk/" target="_blank" class="w_link">tcl.tk</a></li><li>Run the installer</li><li>Add to PATH if needed</li></ol>';
		}
		else{
			switch(strtolower($os)){
				case 'almalinux':
				case 'redhat':
				case 'centos':
				case 'fedora':
					$instructions.='<p><b>'.$os.':</b></p><pre>sudo dnf install tcl</pre>';
				break;
				default:
					$instructions.='<p><b>Ubuntu/Debian:</b></p><pre>sudo apt-get install tcl</pre>';
				break;
			}
		}
		$instructions.='</div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	$modules=array();
	$menu=array();
	// Get Tcl version using temp file (more reliable than echo pipe)
	$tmpfile=getWasqlTempPath().'/tcl_version.tcl';
	$tclcode='puts $tcl_version';
	setFileContents($tmpfile,$tclcode);
	$out=cmdResults("tclsh \"{$tmpfile}\" 2>&1");
	if(file_exists($tmpfile)){unlink($tmpfile);}
	$version=trim($out['stdout']);

	// Get list of available packages
	$tmpfile=getWasqlTempPath().'/tcl_packages.tcl';
	$tclcode='foreach pkg [lsort [package names]] { puts "$pkg [package provide $pkg]" }';
	setFileContents($tmpfile,$tclcode);
	$out=cmdResults("tclsh \"{$tmpfile}\" 2>&1");
	if(file_exists($tmpfile)){unlink($tmpfile);}
	$pkgdata=$out['stdout'];

	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background:#1C71D8;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-tcl is-white"></span> Tcl</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {$version}</div>
	</div>
</header>
ENDOFHEADER;

	// Parse package list
	$lines=preg_split('/[\r\n]+/',$pkgdata);
	foreach($lines as $line){
		$line=trim($line);
		if(!strlen($line)){continue;}
		$parts=preg_split('/\s+/',$line,2);
		if(count($parts)<1){continue;}
		$pkgname=trim($parts[0]);
		$pkgver=isset($parts[1])?trim($parts[1]):'(loaded)';
		if(!strlen($pkgname)){continue;}
		$k=strtolower($pkgname);
		$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$k}">{$pkgname}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#1C71D84D;width:300px;">Package</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$pkgname}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#1C71D84D;width:300px;">Version</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$pkgver}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$pkgname;
	}

	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;
	if(count($modules) > 0){
		$data.='<div class="w_padding"><p>Tcl (Tool Command Language) packages. These are built-in and loaded packages available in your Tcl installation.</p></div>';
		ksort($modules);
		ksort($menu);
		foreach($modules as $k=>$section){
			$data.=$section;
		}
	}
	else{
		$data.='<div class="w_padding"><p>Tcl (Tool Command Language) is a scripting language. Packages are typically installed using teacup or from source. Use <code>package require PackageName</code> to load packages.</p></div>';
	}
	$data.='</div>';
	return array($data,$menu);
}
function langVBScriptInfo(){
	// VBScript only works on Windows
	if(!isWindows()){
		$header='<header class="align-left"><div style="background:#854CC7;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-vbscript is-white"></span> VBScript</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Windows Only</div></div></header>';
		$instructions='<div class="w_padding"><h3>VBScript is a Windows-only scripting language</h3><p>VBScript (Visual Basic Scripting Edition) is only available on Windows operating systems. It comes pre-installed with Windows and is executed using cscript.exe or wscript.exe.</p></div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	// Check if cscript exists
	$check='where cscript 2>nul';
	$test=cmdResults($check);
	if(empty(trim($test['stdout']))){
		$header='<header class="align-left"><div style="background:#854CC7;padding:10px 20px;margin-bottom:20px;border:1px solid #000;"><div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-vbscript is-white"></span> VBScript</div><div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Not Found</div></div></header>';
		$instructions='<div class="w_padding"><h3>VBScript (cscript.exe) not found</h3><p>VBScript should be pre-installed on Windows. Check your system PATH or Windows installation.</p></div>';
		return array('<div class="align-center" style="width:934px;">'.$header.$instructions.'</div>',array());
	}
	$modules=array();
	$menu=array();
	// Get VBScript version
	$tmpfile=getWasqlTempPath().'/vbs_version.vbs';
	$vbscode='WScript.Echo "VBScript " & ScriptEngineMajorVersion & "." & ScriptEngineMinorVersion & "." & ScriptEngineBuildVersion';
	setFileContents($tmpfile,$vbscode);
	$out=cmdResults("cscript //Nologo \"{$tmpfile}\" 2>&1");
	if(file_exists($tmpfile)){unlink($tmpfile);}
	$version=trim($out['stdout']);
	$header=<<<ENDOFHEADER
<header class="align-left">
	<div style="background:#854CC7;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="icon-program-vbscript is-white"></span> VBScript</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">{$version}</div>
	</div>
</header>
ENDOFHEADER;
	// List common COM objects that VBScript can use
	$common_objects = array(
		'Scripting.FileSystemObject' => 'File system access (files, folders, drives)',
		'Scripting.Dictionary' => 'Associative arrays / hash tables',
		'WScript.Shell' => 'Execute commands, access registry, environment variables',
		'WScript.Network' => 'Network operations (map drives, printer connections)',
		'MSXML2.DOMDocument' => 'XML parsing and manipulation',
		'MSXML2.ServerXMLHTTP' => 'HTTP requests / AJAX',
		'ADODB.Connection' => 'Database connectivity (SQL Server, Access, etc)',
		'ADODB.Recordset' => 'Database result sets',
		'CDO.Message' => 'Send email via SMTP',
		'Shell.Application' => 'Windows Shell operations',
		'InternetExplorer.Application' => 'Automate Internet Explorer',
		'Excel.Application' => 'Automate Microsoft Excel (if installed)',
		'Word.Application' => 'Automate Microsoft Word (if installed)',
		'Outlook.Application' => 'Automate Microsoft Outlook (if installed)',
		'SAPI.SpVoice' => 'Text-to-speech',
	);

	foreach($common_objects as $progid => $description){
		$tmpfile=getWasqlTempPath().'/vbs_test_'.md5($progid).'.vbs';
		$testcode="On Error Resume Next\nSet obj = CreateObject(\"{$progid}\")\nIf Err.Number = 0 Then\n    WScript.Echo \"OK\"\nElse\n    WScript.Echo \"ERROR\"\nEnd If";
		setFileContents($tmpfile,$testcode);
		$out=cmdResults("cscript //Nologo \"{$tmpfile}\" 2>&1");
		if(file_exists($tmpfile)){unlink($tmpfile);}
		$available = trim($out['stdout']) == 'OK';
		$status = $available ? '<span style="color:#4ade80;">Available</span>' : '<span style="color:#999;">Not Available</span>';
		$k=strtolower(str_replace('.','_',$progid));
		$modules[$k]=<<<ENDOFSECTION
<h2><a name="module_{$k}">{$progid}</a></h2>
<table>
<tr><td class="align-left w_small w_nowrap" style="background:#854CC74D;width:300px;">COM Object</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$progid}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#854CC74D;width:300px;">Status</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$status}</td></tr>
<tr><td class="align-left w_small w_nowrap" style="background:#854CC74D;width:300px;">Description</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{$description}</td></tr>
</table>
ENDOFSECTION;
		$menu[$k]=$progid;
	}

	$data='<div class="align-center" style="width:934px;">';
	$data.=$header;
	$data.='<div class="w_padding"><p>VBScript (Visual Basic Scripting Edition) is a Windows-only scripting language. It uses <b>COM objects</b> instead of modules/packages. Below are commonly available COM objects:</p></div>';
	ksort($modules);
	ksort($menu);
	foreach($modules as $k=>$section){
		$data.=$section;
	}
	$data.='</div>';
	return array($data,$menu);
}
?>
