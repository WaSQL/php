<?php
global $path;
$path=getWasqlPath('/php/temp');

// Validate tab parameter - whitelist only
$valid_tabs = array('html_tags', 'html_attributes', 'html_events', 'css_styles', 'css_selectors', 'css_functions', 'php_functions', 'sql_reference');
$tab = 'html_tags'; // default

if(isset($_REQUEST['tab']) && in_array($_REQUEST['tab'], $valid_tabs)){
	$tab = $_REQUEST['tab'];
}

switch(strtolower($_REQUEST['func'])){
	case 'list':
		setView('list',1);
		return;
	break;
	default:
		setView('default');
	break;
}
?>
