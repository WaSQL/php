<?php
global $path;
$path=getWasqlPath('/php/temp');
switch(strtolower($_REQUEST['func'])){
	case 'list':
		$tab=$_REQUEST['tab'];
		setView('list',1);
		return;
	break;
	default:
		setView('default');
	break;
}
?>
