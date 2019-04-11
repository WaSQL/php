<?php
	global $CONFIG;
	if(isset($_REQUEST['test'])){
		setView($_REQUEST['test'],1);
		return;
	}
	setView('default');
?>