<?php
switch(strtolower($_REQUEST['passthru'][0])){
	case 'test':
		$errors=pageRunTests($_REQUEST);
		setView('tests',1);
	break;
	default:
		setView('default',1);
	break;
}
?>
