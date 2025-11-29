<?php
	global $CONFIG;
	global $DATABASE;
	global $_SESSION;
	global $USER;
	$lang=$_REQUEST['lang'];
	switch(strtolower($lang)){
		case 'php':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				if(isWindows() && strtolower($_REQUEST['title']) != 'search'){
					$installs[]=array('cmd'=>"",'stdout'=>'Unable to auto install PHP modules on windows.<br><br><a href="https://www.php.net/manual/en/install.pecl.windows.php" class="w_link w_white" target="_blank"><span class="icon-php"></span> Click for instructions.</a>');
					setView('installs',1);
					return;
				}
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$module=trim($module);
					// Validate module name to prevent command injection
					if(!preg_match('/^[a-zA-Z0-9\_\-\.]+$/', $module)){
						$installs[]=array('cmd'=>'','stdout'=>'<div class="w_error">Invalid module name: ' . encodeHtml($module) . '</div>');
						unset($modules[$m]);
						continue;
					}
					$modules[$m]=$module;
				}
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						$url='https://packagist.org/search.json?per_page=100&q='.implode('|',$modules);
						$post=postURL($url,array(
							'-method'=>'GET',
							'-json'=>1,
							'-nossl'=>1
						));
						//echo printValue($post);exit;
						$recs=$post['json_array']['results'];
						$recs=sortArrayByKeys($recs,array('downloads'=>SORT_DESC));
						$install_table=databaseListRecords(array(
							'-list'=>$recs,
							'-tableclass'=>'wacss_table striped sticky',
							'name_class'=>'w_nowrap',
							'date_class'=>'w_nowrap w_small',
							'-tableheight'=>'500px',
							'-pretable'=>"Search for: ".implode(' OR ',$modules),
							'-listfields'=>'name,repository,downloads',
							'repository_options'=>array('target'=>'_blank','href'=>'%repository%'),
							'downloads_options'=>array('class'=>'align-right','number_format'=>0)
						));
						setView('install_table',1);
						return;
					break;
					case 'install':
						foreach($modules as $module){
							switch(strtolower(langLinuxOSName())){
								case 'almalinux':
									$cmd="dnf install php-{$module}";
								break;
								case 'redhat':
								case 'centos':
								case 'fedora':
									$cmd="yum install php-{$module}";
								break;
								default:
									$cmd="apt-get install -y php-{$module}";
								break;
							}
							$installs[]=cmdResults($cmd);
						}	
					break;
					case 'uninstall':
						foreach($modules as $module){
							switch(strtolower(langLinuxOSName())){
								case 'almalinux':
									$cmd="dnf uninstall php-{$module}";
								break;
								case 'redhat':
								case 'centos':
								case 'fedora':
									$cmd="yum uninstall php-{$module}";
								break;
								default:
									$cmd="apt-get remove -y php-{$module}";
								break;
							}
							$installs[]=cmdResults($cmd);
						}
					break;
				}
				setView('installs',1);
				return;
			}
			$result=array('lang'=>'php');
			list($result['body'],$result['modules'])=langPHPInfo();
			setView('lang_results',1);
			return;
		break;
		case 'python':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$module=trim($module);
					// Validate module name to prevent command injection
					if(!preg_match('/^[a-zA-Z0-9\_\-\.]+$/', $module)){
						$installs[]=array('cmd'=>'','stdout'=>'<div class="w_error">Invalid module name: ' . encodeHtml($module) . '</div>');
						unset($modules[$m]);
						continue;
					}
					$modules[$m]=$module;
				}
				// Check if any valid modules remain after validation
				if(count($installs) > 0 && count($modules) == 0){
					setView('installs',1);
					return;
				}
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						$url='https://pypi.org/pypi?%3Aaction=search&term='.implode('+OR+',array_map('urlencode',$modules)).'&submit=search';
						$recs=array();
						foreach($modules as $module){
							// Use PyPI JSON API for better search results
							$apiurl="https://pypi.org/pypi/{$module}/json";
							$post=postURL($apiurl,array('-method'=>'GET','-json'=>1,'-nossl'=>1,'-timeout'=>5));
							if(isset($post['json_array']['info'])){
								$info=$post['json_array']['info'];
								$recs[]=array(
									'name'=>$info['name'],
									'version'=>$info['version'],
									'summary'=>$info['summary'],
									'author'=>$info['author'],
									'home_page'=>$info['home_page']
								);
							}
						}
						if(count($recs)){
							$install_table=databaseListRecords(array(
								'-list'=>$recs,
								'-tableclass'=>'wacss_table striped sticky',
								'name_class'=>'w_nowrap',
								'-tableheight'=>'500px',
								'-pretable'=>"Search for: ".implode(' OR ',$modules),
								'-listfields'=>'name,version,summary,author',
								'home_page_options'=>array('target'=>'_blank','href'=>'%home_page%')
							));
							setView('install_table',1);
							return;
						}
						else{
							$installs[]=array(
								'cmd'=>'',
								'stdout'=>'No exact matches found. <a href="https://pypi.org/search/?q='.implode('+OR+',array_map('urlencode',$modules)).'" target="_blank" class="w_link">Search PyPI</a>'
							);
						}
					break;
					case 'install':
						foreach($modules as $module){
							switch(strtolower(langLinuxOSName())){
								case 'almalinux':
									$cmd="dnf install python3-{$module}";
								break;
								case 'redhat':
								case 'centos':
								case 'fedora':
									$cmd="yum install python3-{$module}";
								break;
								case 'ubuntu':
									$cmd="apt-get install python3-{$module}";
								break;
								default:
									$cmd="python3 -m pip install {$module}";
								break;
							}
							$installs[]=cmdResults($cmd);
						}	
					break;
					case 'uninstall':
						foreach($modules as $module){
							switch(strtolower(langLinuxOSName())){
								case 'almalinux':
									$cmd="dnf uninstall python3-{$module}";
								break;
								case 'redhat':
								case 'centos':
								case 'fedora':
									$cmd="yum uninstall python3-{$module}";
								break;
								case 'ubuntu':
									$cmd="apt-get remove -y python3-{$module}";
								break;
								default:
									$cmd="python3 -m pip uninstall {$module}";
								break;
							}
							$installs[]=cmdResults($cmd);
						}
					break;
				}
				setView('installs',1);
				return;
			}
			$result=array('lang'=>'python');
			list($result['body'],$result['modules'])=langPythonInfo();
			setView('lang_results',1);
			return;
		break;
		case 'ruby':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$module=trim($module);
					// Validate module name to prevent command injection
					if(!preg_match('/^[a-zA-Z0-9\_\-\.]+$/', $module)){
						$installs[]=array('cmd'=>'','stdout'=>'<div class="w_error">Invalid gem name: ' . encodeHtml($module) . '</div>');
						unset($modules[$m]);
						continue;
					}
					$modules[$m]=$module;
				}
				// Check if any valid modules remain after validation
				if(count($installs) > 0 && count($modules) == 0){
					setView('installs',1);
					return;
				}
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						$recs=array();
						foreach($modules as $module){
							// Use RubyGems.org API
							$apiurl="https://rubygems.org/api/v1/search.json?query=".urlencode($module);
							$post=postURL($apiurl,array('-method'=>'GET','-json'=>1,'-nossl'=>1,'-timeout'=>5));
							if(isset($post['json_array']) && is_array($post['json_array'])){
								foreach($post['json_array'] as $gem){
									$recs[]=array(
										'name'=>$gem['name'],
										'version'=>$gem['version'],
										'info'=>$gem['info'],
										'downloads'=>$gem['downloads'],
										'project_uri'=>$gem['project_uri']
									);
								}
							}
						}
						if(count($recs)){
							$recs=sortArrayByKeys($recs,array('downloads'=>SORT_DESC));
							$install_table=databaseListRecords(array(
								'-list'=>$recs,
								'-tableclass'=>'wacss_table striped sticky',
								'name_class'=>'w_nowrap',
								'-tableheight'=>'500px',
								'-pretable'=>"Search for: ".implode(' OR ',$modules),
								'-listfields'=>'name,version,info,downloads',
								'project_uri_options'=>array('target'=>'_blank','href'=>'%project_uri%'),
								'downloads_options'=>array('class'=>'align-right','number_format'=>0)
							));
							setView('install_table',1);
							return;
						}
						else{
							$installs[]=array(
								'cmd'=>'',
								'stdout'=>'No matches found. <a href="https://rubygems.org/search?query='.urlencode(implode(' ',$modules)).'" target="_blank" class="w_link">Search RubyGems</a>'
							);
						}
					break;
					case 'install':
						foreach($modules as $module){
							$cmd="gem install {$module}";
							$installs[]=cmdResults($cmd);
						}
					break;
					case 'uninstall':
						foreach($modules as $module){
							$cmd="gem uninstall {$module} -x";
							$installs[]=cmdResults($cmd);
						}
					break;
				}
				setView('installs',1);
				return;
			}
			$result=array('lang'=>'ruby');
			list($result['body'],$result['modules'])=langRubyInfo();
			setView('lang_results',1);
			return;
		break;
		case 'perl':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$module=trim($module);
					// Validate module name to prevent command injection (Perl modules can have ::)
					if(!preg_match('/^[a-zA-Z0-9\_\-\.\:]+$/', $module)){
						$installs[]=array('cmd'=>'','stdout'=>'<div class="w_error">Invalid module name: ' . encodeHtml($module) . '</div>');
						unset($modules[$m]);
						continue;
					}
					$modules[$m]=$module;
				}
				// Check if any valid modules remain after validation
				if(count($installs) > 0 && count($modules) == 0){
					setView('installs',1);
					return;
				}
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						foreach($modules as $m=>$module){
							$modules[$m]="distribution:*{$module}*";
						}
						$url='https://fastapi.metacpan.org/v1/release/_search?size=100&q='.implode('+OR+',$modules);
						$post=postURL($url,array(
							'-method'=>'GET',
							'-json'=>1,
							'-nossl'=>1
						));
						//echo printValue($post);exit;
						$recs=array();
						foreach($post['json_array']['hits']['hits'] as $hit){
							$timestamp=strtotime($hit['_source']['date']);
							$name=commonCoalesce($hit['_source']['distribution'],$hit['_source']['name']);
							if(isset($recs[$name]) && $recs[$name]['timestamp'] > $timestamp){continue;}
							$recs[$name]=array(
								'name'=>$name,
								'author'=>$hit['_source']['author'],
								'version'=>$hit['_source']['version_numified'],
								'date'=>$hit['_source']['date'],
								'timestamp'=>$timestamp
							);
						}
						$recs=sortArrayByKeys($recs,array('timestamp'=>SORT_DESC));
						$install_table=databaseListRecords(array(
							'-list'=>$recs,
							'-tableclass'=>'wacss_table striped sticky',
							'name_class'=>'w_nowrap',
							'date_class'=>'w_nowrap w_small',
							'-tableheight'=>'500px',
							'-listfields'=>'name,version,date,author'
						));
						setView('install_table',1);
						return;
					break;
					case 'install':
						foreach($modules as $module){
							$cmd="perl -MCPAN -e \"install {$module}\"";
							$installs[]=cmdResults($cmd);
						}	
					break;
					case 'uninstall':
						//uninstall requires App::pmuninstall (perl -MCPAN -e "install 'App::pmuninstall'")
						foreach($modules as $module){
							$cmd="pmuninstall -v \"{$module}\"";
							$installs[]=cmdResults($cmd);
						}
						
					break;
				}
				setView('installs',1);
				return;
			}
			$result=array('lang'=>'perl');
			list($result['body'],$result['modules'])=langPerlInfo();
			setView('lang_results',1);
			return;
		break;
		case 'node':
		case 'nodejs':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$module=trim($module);
					// Validate module name to prevent command injection (NPM allows @scope/package)
					if(!preg_match('/^[\@a-zA-Z0-9\_\-\.\/]+$/', $module)){
						$installs[]=array('cmd'=>'','stdout'=>'<div class="w_error">Invalid module name: ' . encodeHtml($module) . '</div>');
						unset($modules[$m]);
						continue;
					}
					$modules[$m]=$module;
				}
				// Check if any valid modules remain after validation
				if(count($installs) > 0 && count($modules) == 0){
					setView('installs',1);
					return;
				}
				$npmcmd=isWindows()?'npm.cmd':'npm';
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						$recs=array();
						foreach($modules as $module){
							$cmd="{$npmcmd} search {$module}";
							$out=cmdResults($cmd);
							$crecs=csv2Arrays($out['stdout'],array('separator'=>'|','-lowercase'=>1,'-nospaces'=>1));
							//echo printValue($crecs);exit;
							foreach($crecs as $crec){$recs[]=$crec;}
						}
						$recs=sortArrayByKeys($recs,array('Package'=>SORT_ASC));
						$install_table=databaseListRecords(array(
							'-list'=>$recs,
							'-tableclass'=>'wacss_table striped sticky',
							'name_class'=>'w_nowrap',
							'date_class'=>'w_nowrap',
							'-tableheight'=>'500px',
							'-listfields'=>'name,version,description,author,date'
						));
						setView('install_table',1);
						return;
					break;
					case 'install':
						foreach($modules as $module){
							$cmd="{$npmcmd} -g install {$module}";
							$installs[]=cmdResults($cmd);
						}
					break;
					case 'uninstall':
						foreach($modules as $module){
							$cmd="{$npmcmd} -g uninstall {$module}";
							$installs[]=cmdResults($cmd);
						}
						
					break;
				}
				setView('installs',1);
				return;
			}
			$result=array('lang'=>'nodejs');
			list($result['body'],$result['modules'])=langNodeInfo();
			setView('lang_results',1);
			return;
		break;
		case 'lua':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$module=trim($module);
					// Validate module name to prevent command injection
					if(!preg_match('/^[a-zA-Z0-9\_\-\.]+$/', $module)){
						$installs[]=array('cmd'=>'','stdout'=>'<div class="w_error">Invalid module name: ' . encodeHtml($module) . '</div>');
						unset($modules[$m]);
						continue;
					}
					$modules[$m]=$module;
				}
				// Check if any valid modules remain after validation
				if(count($installs) > 0 && count($modules) == 0){
					setView('installs',1);
					return;
				}
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						foreach($modules as $m=>$module){
							$modules[$m]=strtolower($module);
						}
						//search the manifest
						$post=postURL('https://luarocks.org/manifest.json',array('-method'=>'GET','-json'=>1));
						$recs=array();
						foreach($post['json_array']['repository'] as $name=>$versions){
							$match=0;
							foreach($modules as $module){
								if(stringContains($name,$module)){$match=1;break;}
							}
							if($match==1){
								foreach($versions as $version=>$info){break;}
								$recs[]=array(
									'name'=>$name,
									'version'=>$version
								);
							}
						}
						$recs=sortArrayByKeys($recs,array('name'=>SORT_ASC));
						$install_table=databaseListRecords(array(
							'-list'=>$recs,
							'-tableclass'=>'wacss_table striped sticky',
							'name_class'=>'w_nowrap',
							'-tableheight'=>'500px',
							'-listfields'=>'name,version'
						));
						setView('install_table',1);
						return;
					break;
					case 'install':
						foreach($modules as $module){
							$cmd="luarocks install {$module}";
							$installs[]=cmdResults($cmd);
						}	
					break;
					case 'uninstall':
						foreach($modules as $module){
							$cmd="luarocks remove {$module}";
							$installs[]=cmdResults($cmd);
						}
						
					break;
				}
				setView('installs',1);
				return;
			}
			$result=array('lang'=>'lua');
			list($result['body'],$result['modules'])=langLuaInfo();
			setView('lang_results',1);
			return;
		break;
		case 'r':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$module=trim($module);
					// Validate module name to prevent command injection
					if(!preg_match('/^[a-zA-Z0-9\_\-\.]+$/', $module)){
						$installs[]=array('cmd'=>'','stdout'=>'<div class="w_error">Invalid module name: ' . encodeHtml($module) . '</div>');
						unset($modules[$m]);
						continue;
					}
					$modules[$m]=$module;
				}
				// Check if any valid modules remain after validation
				if(count($installs) > 0 && count($modules) == 0){
					setView('installs',1);
					return;
				}
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						$modulestr=implode('|',$modules);
						$cmd="Rscript -e \"options(repos = c(CRAN = 'https://cran.r-project.org')); result <- available.packages(); matching <- result[grepl('{$modulestr}', rownames(result), ignore.case=TRUE), c('Package', 'Version')]; write.csv(matching, file = stdout(), row.names = TRUE)\"";
						$out=cmdResults($cmd);
						$recs=csv2Arrays($out['stdout'],array('-lowercase'=>1,'-nospaces'=>1));
						$recs=sortArrayByKeys($recs,array('package'=>SORT_ASC));
						$install_table=databaseListRecords(array(
							'-list'=>$recs,
							'-tableclass'=>'wacss_table striped sticky',
							'name_class'=>'w_nowrap',
							'-tableheight'=>'500px',
							'-listfields'=>'package,version'
						));
						setView('install_table',1);
						return;
					break;
					case 'install':
						foreach($modules as $module){
							$cmd="Rscript -e \"install.packages('{$module}', repos='https://cran.rstudio.com')\"";
							$installs[]=cmdResults($cmd);
						}	
					break;
					case 'uninstall':
						foreach($modules as $module){
							$cmd="Rscript -e \"remove.packages('{$module}')\"";
							$installs[]=cmdResults($cmd);
						}
						
					break;
				}
				setView('installs',1);
				return;
			}
			$result=array('lang'=>'r');
			list($result['body'],$result['modules'])=langRInfo();
			setView('lang_results',1);
			return;
		break;
		case 'julia':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$module=trim($module);
					// Validate module name to prevent command injection
					if(!preg_match('/^[a-zA-Z0-9\_\-\.]+$/', $module)){
						$installs[]=array('cmd'=>'','stdout'=>'<div class="w_error">Invalid package name: ' . encodeHtml($module) . '</div>');
						unset($modules[$m]);
						continue;
					}
					$modules[$m]=$module;
				}
				// Check if any valid modules remain after validation
				if(count($installs) > 0 && count($modules) == 0){
					setView('installs',1);
					return;
				}
				// Get julia command path
				$juliacmd=langFindJulia();
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						$recs=array();
						foreach($modules as $module){
							// Use JuliaHub API
							$apiurl="https://juliahub.com/api/v1/package?name=".urlencode($module);
							$post=postURL($apiurl,array('-method'=>'GET','-json'=>1,'-nossl'=>1,'-timeout'=>5));
							if(isset($post['json_array']['results']) && is_array($post['json_array']['results'])){
								foreach($post['json_array']['results'] as $pkg){
									$recs[]=array(
										'name'=>isset($pkg['name'])?$pkg['name']:'',
										'version'=>isset($pkg['latest_version'])?$pkg['latest_version']:'',
										'description'=>isset($pkg['description'])?$pkg['description']:'',
										'stars'=>isset($pkg['stars'])?$pkg['stars']:0,
										'url'=>isset($pkg['name'])?'https://juliahub.com/ui/Packages/'.$pkg['name']:''
									);
								}
							}
						}
						if(count($recs)){
							$recs=sortArrayByKeys($recs,array('stars'=>SORT_DESC));
							$install_table=databaseListRecords(array(
								'-list'=>$recs,
								'-tableclass'=>'wacss_table striped sticky',
								'name_class'=>'w_nowrap',
								'-tableheight'=>'500px',
								'-pretable'=>"Search for: ".implode(' OR ',$modules),
								'-listfields'=>'name,version,description,stars',
								'url_options'=>array('target'=>'_blank','href'=>'%url%'),
								'stars_options'=>array('class'=>'align-right','number_format'=>0)
							));
							setView('install_table',1);
							return;
						}
						else{
							$installs[]=array(
								'cmd'=>'',
								'stdout'=>'No matches found. <a href="https://juliahub.com/ui/Packages" target="_blank" class="w_link">Browse JuliaHub</a>'
							);
						}
					break;
					case 'install':
						foreach($modules as $module){
							$cmd="{$juliacmd} -e \"using Pkg; Pkg.add(\\\"{$module}\\\")\"";
							$installs[]=cmdResults($cmd);
						}
					break;
					case 'uninstall':
						foreach($modules as $module){
							$cmd="{$juliacmd} -e \"using Pkg; Pkg.rm(\\\"{$module}\\\")\"";
							$installs[]=cmdResults($cmd);
						}
					break;
				}
				setView('installs',1);
				return;
			}
			$result=array('lang'=>'julia');
			list($result['body'],$result['modules'])=langJuliaInfo();
			setView('lang_results',1);
			return;
		break;
		case 'bash':
			$result=array('lang'=>'bash');
			list($result['body'],$result['modules'])=langBashInfo();
			setView('lang_results',1);
			return;
		break;
		case 'powershell':
		case 'pwsh':
		case 'ps1':
			$result=array('lang'=>'powershell');
			list($result['body'],$result['modules'])=langPowershellInfo();
			setView('lang_results',1);
			return;
		break;
		case 'groovy':
			$result=array('lang'=>'groovy');
			list($result['body'],$result['modules'])=langGroovyInfo();
			setView('lang_results',1);
			return;
		break;
		case 'tcl':
			$result=array('lang'=>'tcl');
			list($result['body'],$result['modules'])=langTclInfo();
			setView('lang_results',1);
			return;
		break;
		case 'vbscript':
		case 'vbs':
			$result=array('lang'=>'vbscript');
			list($result['body'],$result['modules'])=langVBScriptInfo();
			setView('lang_results',1);
			return;
		break;
	}
	$result=array('lang'=>'php');
	list($result['body'],$result['modules'])=langPHPInfo();
	setView('default',1);
?>
