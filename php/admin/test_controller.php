<?php
	global $CONFIG;
	//loadExtrasJs(array('c3'));
	//loadExtrasCss('c3');
	if(isset($_REQUEST['test'])){
		setView($_REQUEST['test'],1);
		return;
	}
	setView('default');
?>