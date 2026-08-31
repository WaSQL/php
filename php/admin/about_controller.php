<?php
//========================================================================
// About WaSQL - interactive dashboard (thin controller)
//   Routes on $_REQUEST['func'] (set by the tab links via wacss.nav).
//   No func / unknown func = full page load -> the 'default' view.
//========================================================================
global $CONFIG; global $USER;
loadExtrasCss('wacss');
loadExtrasJs('wacss');

$valid=array('about_overview','about_versions','about_server','about_php','about_extensions','about_config');
$func=isset($_REQUEST['func']) ? strtolower($_REQUEST['func']) : '';

//tab clicks arrive as ajax partials; a plain GET always renders the full dashboard
if(isAjax() && in_array($func,$valid)){
	setView($func,1);
	return;
}
setView('default',1);
?>
