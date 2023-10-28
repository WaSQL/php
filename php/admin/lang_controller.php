<?php
	global $CONFIG;
	global $DATABASE;
	global $_SESSION;
	global $USER;
	$lang=$_REQUEST['lang'];
	switch(strtolower($lang)){
		case 'php':
			$install='';
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="python3 -m pip install {$module}";
				$install=cmdResults($cmd);
			}
			list($body,$modules)=langPHPInfo();
		break;
		case 'python':
			$install='';
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="python3 -m pip install {$module}";
				$install=cmdResults($cmd);
			}
			list($body,$modules)=langPythonInfo();
		break;
		case 'perl':
			$install='';
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="perl -MCPAN -e \"install {$module}\"";
				$install=cmdResults($cmd);
			}
			list($body,$modules)=langPerlInfo();
		break;
		case 'node':
			$install='';
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="npm -g install {$module}";
				$install=cmdResults($cmd);
			}
			list($body,$modules)=langNodeInfo();
		break;
		case 'lua':
			$install='';
			if(isset($_REQUEST['module']) && strlen($_REQUEST['module'])){
				$module=trim($_REQUEST['module']);
				//install this module
				$cmd="luarocks install {$module}";
				$install=cmdResults($cmd);
			}
			list($body,$modules)=langLuaInfo();
		break;
	}
	setView('default',1);
?>
