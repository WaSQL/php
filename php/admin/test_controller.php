<?php
	global $CONFIG;
	loadExtrasJs(array('chart'));
	if(isset($_REQUEST['test'])){
		setView($_REQUEST['test'],1);
		return;
	}
	setView('default');
?>