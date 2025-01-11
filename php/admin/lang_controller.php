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
					$modules[$m]=trim($module);
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
							'-tableclass'=>'table striped sticky',
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
									$cmd="apt-get install php-{$module}";
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
									$cmd="apt-get uninstall php-{$module}";
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
					$modules[$m]=trim($module);
				}
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						foreach($modules as $module){
							$installs[]=array(
								'cmd'=>$module,
								'stdout'=>'<a href="https://pypi.org/search/?q='.$module.'" target="_blank" class="btn w_whiteback w_link">Search for '.$module.'</a>'
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
									$cmd="apt-get uninstall python3-{$module}";
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
		case 'perl':
			$installs=array();
			if($_REQUEST['title'] && strlen($_REQUEST['title']) && isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$modules=preg_split('/[\,\ ]+/',trim($_REQUEST['module']));
				foreach($modules as $m=>$module){
					$modules[$m]=trim($module);
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
							'-tableclass'=>'table striped sticky',
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
					$modules[$m]=trim($module);
				}
				switch(strtolower($_REQUEST['title'])){
					case 'search':
						$recs=array();
						foreach($modules as $module){
							$cmd="npm.cmd search {$module}";
							$out=cmdResults($cmd);
							$crecs=csv2Arrays($out['stdout'],array('separator'=>'|','-lowercase'=>1,'-nospaces'=>1));
							//echo printValue($crecs);exit;
							foreach($crecs as $crec){$recs[]=$crec;}
						}	
						$recs=sortArrayByKeys($recs,array('Package'=>SORT_ASC));
						$install_table=databaseListRecords(array(
							'-list'=>$recs,
							'-tableclass'=>'table striped sticky',
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
							$cmd="npm.cmd -g install {$module}";
							$installs[]=cmdResults($cmd);
						}	
					break;
					case 'uninstall':
						foreach($modules as $module){
							$cmd="npm.cmd -g uninstall {$module}";
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
					$modules[$m]=trim($module);
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
							'-tableclass'=>'table striped sticky',
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
					$modules[$m]=trim($module);
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
							'-tableclass'=>'table striped sticky',
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
	}
	$result=array('lang'=>'php');
	list($result['body'],$result['modules'])=langPHPInfo();
	setView('default',1);
?>
