<?php
	global $params;
	global $CONFIG;
	if(isset($_REQUEST['cmd'])){
		$cmd=$_REQUEST['cmd'];
		if(isset($_REQUEST['dir']) && strlen($_REQUEST['dir'])){
			$dir=$_REQUEST['dir'];
			$out=cmdResults($cmd,'',$dir);
			if(!in_array($dir,$_SESSION['terminal_dirs'])){
				$_SESSION['terminal_dirs'][]=$dir;
			}
		}
		else{
			$out=cmdResults($cmd);
		}
		if(!in_array($cmd,$_SESSION['terminal_commands'])){
			$_SESSION['terminal_commands'][]=$cmd;
		}
		setView('results',1);
		return;
	}
	$_SESSION['terminal_commands']=array();
	setView('default',1);
?>
