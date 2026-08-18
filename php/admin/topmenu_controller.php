<?php
global $CONFIG;
global $SETTINGS;
global $GITREPO;
global $USER;
global $admin_color_class;
global $is_stage;
global $totalTableCount;
global $rendered_tables;
global $rendered_pages;
global $rendered_templates;
global $rendered_users;
global $prebuilt;

if(isset($_REQUEST['_menu']) && $_REQUEST['_menu'] == 'logs' && isset($_REQUEST['func']) && ($_REQUEST['func'] == 'tail' || $_REQUEST['func'] == 'tail_refresh')){
	setView('blank', 1);
	return;
}

$admin_color_class = topmenuGetColorClass();
$is_stage = isDBStage();

setView('default');
if(isAdmin()){
	$tableData = topmenuGetTablesData();
	$tables = $tableData['tables'];
	$tableCounts = $tableData['counts'];
	$totalTableCount = $tableData['total'];

	$pages = topmenuGetRecentPages(15);
	$templates = topmenuGetTemplates(15);
	$users = topmenuGetRecentUsers(15);
	$prebuilt = topmenuGetPreBuiltTables();

	$rendered_tables = topmenuRenderTableGroupMenu($tables, $tableCounts);
	$rendered_pages = topmenuRenderRecentPages($pages);
	$rendered_templates = topmenuRenderTemplates($templates);
	$rendered_users = topmenuRenderUsers($users);

	setView('admin');
}
?>