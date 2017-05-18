<?php
global $progpath;
switch(strtolower($_REQUEST['func'])){
	case 'search':
		setView('search',1);
		return;
	break;
	default:
		$files=listFilesEx($progpath,array('ext'=>'php'));
		setView('default',1);
	break;
}
setView('default',1);
?>
