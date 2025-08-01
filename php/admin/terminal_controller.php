<?php
	global $params;
	global $CONFIG;
	if(isset($_REQUEST['cmd'])){
		$cmd=$_REQUEST['cmd'];
		if(isset($_REQUEST['dir']) && strlen($_REQUEST['dir'])){
			$cmd_dir=$_REQUEST['dir'];
			$out=cmdResults($cmd,'',$cmd_dir);
			$ok=terminalAddHistory($cmd,$cmd_dir);
		}
		else{
			$out=cmdResults($cmd);
			$ok=terminalAddHistory($cmd,'');
		}
		$drecs=terminalGetHistory();
		setView('results',1);
		return;
	}
	$drecs=terminalGetHistory();
	if(!isset($_SESSION['terminal_tablecheck'])){
		$_SESSION['terminal_tablecheck']=1;
		$ok=terminalTableCheck();
	}
	setView('default',1);
?>
