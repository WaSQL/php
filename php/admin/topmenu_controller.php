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
global $rendered_appearance;
global $theme_attrs;
global $prebuilt;

// Handle AJAX Appearance Theme Save
if(isset($_REQUEST['_menu']) && $_REQUEST['_menu'] == 'topmenu' && isset($_REQUEST['func']) && $_REQUEST['func'] == 'theme_save'){
	$what = isset($_REQUEST['what']) ? strtolower(trim($_REQUEST['what'])) : '';
	$value = isset($_REQUEST['value']) ? strtolower(trim($_REQUEST['value'])) : '';
	$res = wasqlSetAppearance($what, $value);
	if(isset($res['error'])){
		echo json_encode(array('status'=>'error', 'message'=>$res['error']));
	} else {
		$msg = ($res['pref'] == 'mode') ? $res['label'] . ' appearance saved' : $res['label'] . ' theme saved';
		echo json_encode(array('status'=>'success', 'message'=>$msg, 'pref'=>$res['pref'], 'value'=>$res['value']));
	}
	exit;
}

if(isset($_REQUEST['_menu']) && $_REQUEST['_menu'] == 'logs' && isset($_REQUEST['func']) && ($_REQUEST['func'] == 'tail' || $_REQUEST['func'] == 'tail_refresh')){
	setView('blank', 1);
	return;
}

$admin_color_class = topmenuGetColorClass();
$is_stage = isDBStage();
$theme_attrs = wasqlUserThemeAttrs();
$rendered_appearance = topmenuRenderAppearancePicker();

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