<?php
	global $CONFIG;
	global $DATABASE;
	global $_SESSION;
	global $USER;
	$lang=$_REQUEST['lang'];
	switch(strtolower($lang)){
		case 'php':
			$install=array('cmd'=>'','stdout'=>'');
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				//install this module
				$module=trim($_REQUEST['module']);
				if(isWindows()){
					$install['cmd']="";
					$install['stdout']='Unable to auto install PHP modules on windows.<br><br><a href="https://www.php.net/manual/en/install.pecl.windows.php" class="w_link w_white" target="_blank"><span class="icon-php"></span> Click for instructions.</a>';
				}
				else{
					$out=cmdResults('cat /etc/os-release');
					$ini='[os]'.PHP_EOL.$out['stdout'];
					$info=commonParseIni($ini);
					if(isset($info['os']['name'])){
						switch(strtolower($info['os']['name'])){
							case 'almalinux':
								$cmd="dnf install php-{$module}";
							break;
							case 'redhat':
							case 'centos':
							case 'fedora':
								$cmd="yum install php-{$module}";
							break;
							default:
							case 'ubuntu':
								$cmd="apt-get install php-{$module}";
							break;
						}
					}
					$install=cmdResults($cmd);
				}
				setView('install',1);
				return;
			}
			list($body,$modules)=langPHPInfo();
		break;
		case 'python':
			$install=array('cmd'=>'','stdout'=>'');
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="python3 -m pip install {$module}";
				$install=cmdResults($cmd);
				setView('install',1);
				return;
			}
			list($body,$modules)=langPythonInfo();
		break;
		case 'perl':
			$install=array('cmd'=>'','stdout'=>'');
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="perl -MCPAN -e \"install {$module}\"";
				$install=cmdResults($cmd);
				setView('install',1);
				return;
			}
			list($body,$modules)=langPerlInfo();
		break;
		case 'node':
			$install=array('cmd'=>'','stdout'=>'');
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="npm -g install {$module}";
				$install=cmdResults($cmd);
				setView('install',1);
				return;
			}
			list($body,$modules)=langNodeInfo();
		break;
		case 'lua':
			$install=array('cmd'=>'','stdout'=>'');
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="luarocks install {$module}";
				$install=cmdResults($cmd);
				setView('install',1);
				return;
			}
			list($body,$modules)=langLuaInfo();
		break;
		case 'r':
			$install=array('cmd'=>'','stdout'=>'');
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="python3 -m pip install {$module}";
				$install=cmdResults($cmd);
				setView('install',1);
				return;
			}
			list($body,$modules)=langRInfo();
		break;
	}
	setView('default',1);
?>
