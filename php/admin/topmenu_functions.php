<?php
/**
 * WaSQL Admin Top Menu Helper Functions & User Themes System
 */

/* ==================== THEME PALETTES & DECLARATIONS ==================== */

/**
 * Declares all Light Theme palettes
 * @return array slug => array('label','dot','dot2')
 */
function wasqlThemes(){
	return array(
		'indigo' => array('label'=>'Indigo', 'dot'=>'#3b5bfd', 'dot2'=>'#6d5cf7'),
		'ocean'  => array('label'=>'Ocean',  'dot'=>'#0e7490', 'dot2'=>'#0891b2'),
		'forest' => array('label'=>'Forest', 'dot'=>'#15803d', 'dot2'=>'#059669'),
		'sunset' => array('label'=>'Sunset', 'dot'=>'#c2410c', 'dot2'=>'#ea580c'),
		'plum'   => array('label'=>'Plum',   'dot'=>'#9333ea', 'dot2'=>'#c026d3'),
	);
}

/**
 * Declares all Dark Theme palettes
 * @return array slug => array('label','dot','dot2')
 */
function wasqlDarkThemes(){
	return array(
		'midnight'  => array('label'=>'Midnight',  'dot'=>'#0c0f16', 'dot2'=>'#7f96ff'),
		'nord'      => array('label'=>'Nord',      'dot'=>'#2e3440', 'dot2'=>'#88c0d0'),
		'dracula'   => array('label'=>'Dracula',   'dot'=>'#282a36', 'dot2'=>'#bd93f9'),
		'solarized' => array('label'=>'Solarized', 'dot'=>'#002b36', 'dot2'=>'#4fa6e0'),
		'carbon'    => array('label'=>'Carbon',    'dot'=>'#000000', 'dot2'=>'#d4d4d8'),
	);
}

/**
 * Appearance mode choices
 * @return array
 */
function wasqlModes(){
	return array('system'=>'System', 'light'=>'Light', 'dark'=>'Dark');
}

function wasqlDefaultTheme(){ return 'indigo'; }
function wasqlDefaultDarkTheme(){ return 'midnight'; }
function wasqlDefaultMode(){ return 'system'; }

/* ==================== PREFERENCES STORAGE & VALIDATED READERS ==================== */

/**
 * Decodes and returns the user's preferences from _users.meta
 * @param int $uid
 * @param int $force
 * @return array
 */
function wasqlUserPrefs($uid=0, $force=0){
	global $USER;
	$uid = (int)$uid > 0 ? (int)$uid : (isset($USER['_id']) ? (int)$USER['_id'] : 0);
	if(!$uid){ return array(); }

	if(!$force && isset($USER['_id']) && (int)$USER['_id'] === $uid && isset($USER['meta_ex']) && is_array($USER['meta_ex'])){
		return $USER['meta_ex'];
	}

	$rec = getDBRecord(array('-table'=>'_users', '_id'=>$uid, '-fields'=>'_id,meta'));
	if(!isset($rec['_id'])){ return array(); }
	$meta = array();
	if(isset($rec['meta']) && strlen($rec['meta'])){
		$meta = json_decode($rec['meta'], true);
		if(!is_array($meta)){ $meta = array(); }
	}
	if(isset($USER['_id']) && (int)$USER['_id'] === $uid){
		$USER['meta_ex'] = $meta;
	}
	return $meta;
}

/**
 * Reads a single user preference with fallback default
 * @param string $key
 * @param mixed $default
 * @param int $uid
 * @return mixed
 */
function wasqlUserPref($key, $default='', $uid=0){
	$prefs = wasqlUserPrefs($uid);
	return isset($prefs[$key]) ? $prefs[$key] : $default;
}

/**
 * Sets and merges a preference key into _users.meta
 * @param string $key
 * @param mixed $value
 * @param int $uid
 * @return bool
 */
function wasqlUserPrefSet($key, $value, $uid=0){
	global $USER;
	$uid = (int)$uid > 0 ? (int)$uid : (isset($USER['_id']) ? (int)$USER['_id'] : 0);
	if(!$uid){ return false; }

	// Re-read current meta from DB first so other tabs/preferences are not clobbered
	$rec = getDBRecord(array('-table'=>'_users', '_id'=>$uid, '-fields'=>'_id,meta'));
	$meta = array();
	if(isset($rec['meta']) && strlen($rec['meta'])){
		$meta = json_decode($rec['meta'], true);
		if(!is_array($meta)){ $meta = array(); }
	}
	$meta[$key] = $value;
	$json = json_encode($meta);
	$ok = editDBRecordById('_users', $uid, array('meta' => $json));
	if(isNum($ok) || $ok){
		if(isset($USER['_id']) && (int)$USER['_id'] === $uid){
			$USER['meta'] = $json;
			$USER['meta_ex'] = $meta;
		}
		return true;
	}
	return false;
}

/**
 * Validated reader for light theme
 * @param int $uid
 * @return string
 */
function wasqlUserTheme($uid=0){
	$slug = strtolower(trim((string)wasqlUserPref('theme', wasqlDefaultTheme(), $uid)));
	$themes = wasqlThemes();
	return isset($themes[$slug]) ? $slug : wasqlDefaultTheme();
}

/**
 * Validated reader for dark theme
 * @param int $uid
 * @return string
 */
function wasqlUserDarkTheme($uid=0){
	$slug = strtolower(trim((string)wasqlUserPref('dark', wasqlDefaultDarkTheme(), $uid)));
	$darkThemes = wasqlDarkThemes();
	return isset($darkThemes[$slug]) ? $slug : wasqlDefaultDarkTheme();
}

/**
 * Validated reader for appearance mode
 * @param int $uid
 * @return string
 */
function wasqlUserMode($uid=0){
	$slug = strtolower(trim((string)wasqlUserPref('mode', wasqlDefaultMode(), $uid)));
	$modes = wasqlModes();
	return isset($modes[$slug]) ? $slug : wasqlDefaultMode();
}

/**
 * Validated writer for theme, dark, or mode
 * @param string $what - theme|dark|mode
 * @param string $value
 * @param int $uid
 * @return array
 */
function wasqlSetAppearance($what, $value, $uid=0){
	global $USER;
	$uid = (int)$uid > 0 ? (int)$uid : (isset($USER['_id']) ? (int)$USER['_id'] : 0);
	if(!$uid){ return array('error'=>'Sign in to change appearance settings.'); }
	$what = strtolower(trim((string)$what));
	$value = strtolower(trim((string)$value));
	$allowed = array(
		'theme' => array('list'=>wasqlThemes(),     'noun'=>'light themes'),
		'dark'  => array('list'=>wasqlDarkThemes(), 'noun'=>'dark themes'),
		'mode'  => array('list'=>wasqlModes(),      'noun'=>'appearance modes')
	);
	if(!isset($allowed[$what])){ return array('error'=>'Invalid appearance setting.'); }
	$list = $allowed[$what]['list'];
	if(!isset($list[$value])){ return array('error'=>'Invalid choice for ' . $allowed[$what]['noun'] . '.'); }

	if(!wasqlUserPrefSet($what, $value, $uid)){
		return array('error'=>'Unable to save appearance setting.');
	}
	$label = is_array($list[$value]) ? $list[$value]['label'] : $list[$value];
	return array('pref'=>$what, 'value'=>$value, 'label'=>$label);
}

/**
 * Generates HTML data-wasql-* attributes for <html>
 * @param int $uid
 * @return string
 */
function wasqlUserThemeAttrs($uid=0){
	$theme = wasqlUserTheme($uid);
	$dark  = wasqlUserDarkTheme($uid);
	$mode  = wasqlUserMode($uid);
	$attrs = ' data-wasql-theme="' . encodeHtml($theme) . '"'
	       . ' data-wasql-dark="' . encodeHtml($dark) . '"'
	       . ' data-wasql-mode="' . encodeHtml($mode) . '"';
	if($mode !== 'system'){
		$attrs .= ' data-wasql-resolved="' . encodeHtml($mode) . '"';
	}
	return $attrs;
}

/**
 * Renders the HTML Appearance / Theme Picker markup for the topmenu
 * @return string
 */
function topmenuRenderAppearancePicker(){
	$curTheme = wasqlUserTheme();
	$curDark  = wasqlUserDarkTheme();
	$curMode  = wasqlUserMode();

	$modes = wasqlModes();
	$themes = wasqlThemes();
	$darkThemes = wasqlDarkThemes();

	$rtn = '<div class="wasql-theme-picker" onclick="event.stopPropagation();">';
	$rtn .= '<div class="wasql-theme-header"><span class="icon-brush"></span> Theme &amp; Appearance</div>';

	// Appearance Mode segmented buttons
	$rtn .= '<div class="wasql-theme-modes" role="radiogroup" aria-label="Appearance Mode">';
	foreach($modes as $mKey => $mLabel){
		$active = ($mKey === $curMode);
		$icon = $mKey === 'system' ? 'icon-slideshow' : ($mKey === 'light' ? 'icon-sun' : 'icon-moon');
		$rtn .= '<button type="button" class="wasql-mode-btn' . ($active ? ' is-active' : '') . '" '
		      . 'role="radio" aria-checked="' . ($active ? 'true' : 'false') . '" '
		      . 'data-pref="mode" data-value="' . encodeHtml($mKey) . '" '
		      . 'onclick="wasqlAppearanceSet(this);">'
		      . '<span class="' . $icon . '"></span> ' . encodeHtml($mLabel)
		      . '</button>';
	}
	$rtn .= '</div>';

	// Light Themes Palette
	$rtn .= '<div class="wasql-theme-section-title">Light Palette</div>';
	$rtn .= '<div class="wasql-swatch-row" role="radiogroup" aria-label="Light Themes" data-swatches="theme">';
	foreach($themes as $slug => $th){
		$active = ($slug === $curTheme);
		$rtn .= '<button type="button" class="wasql-swatch' . ($active ? ' is-active' : '') . '" '
		      . 'role="radio" aria-checked="' . ($active ? 'true' : 'false') . '" '
		      . 'data-pref="theme" data-value="' . encodeHtml($slug) . '" '
		      . 'style="--sw-1:' . encodeHtml($th['dot']) . ';--sw-2:' . encodeHtml($th['dot2']) . ';" '
		      . 'title="' . encodeHtml($th['label']) . '" aria-label="' . encodeHtml($th['label']) . '" '
		      . 'onclick="wasqlAppearanceSet(this);"></button>';
	}
	$rtn .= '</div>';

	// Dark Themes Palette
	$rtn .= '<div class="wasql-theme-section-title">Dark Palette</div>';
	$rtn .= '<div class="wasql-swatch-row" role="radiogroup" aria-label="Dark Themes" data-swatches="dark">';
	foreach($darkThemes as $slug => $th){
		$active = ($slug === $curDark);
		$rtn .= '<button type="button" class="wasql-swatch wasql-swatch-dark' . ($active ? ' is-active' : '') . '" '
		      . 'role="radio" aria-checked="' . ($active ? 'true' : 'false') . '" '
		      . 'data-pref="dark" data-value="' . encodeHtml($slug) . '" '
		      . 'style="--sw-1:' . encodeHtml($th['dot']) . ';--sw-2:' . encodeHtml($th['dot2']) . ';" '
		      . 'title="' . encodeHtml($th['label']) . '" aria-label="' . encodeHtml($th['label']) . '" '
		      . 'onclick="wasqlAppearanceSet(this);"></button>';
	}
	$rtn .= '</div>';

	$rtn .= '</div>';
	return $rtn;
}

/* ==================== TABLES, PAGES, TEMPLATES, USERS HELPERS ==================== */

/**
 * Returns pre-built table definitions
 * @return array
 */
function topmenuGetPreBuiltTables(){
	$recs = array(
		array('name'=>'cities', 'file'=>'all_cities'),
		array('name'=>'states', 'file'=>'all_states'),
		array('name'=>'countries', 'file'=>'all_countries'),
		array('name'=>'colors', 'file'=>'all_colors'),
	);
	foreach($recs as $i => $rec){
		if(isDBTable($rec['name'])){
			$recs[$i]['class'] = 'icon-checkbox';
			$recs[$i]['onclick'] = "if(!confirm('A {$rec['name']} table already exists. Rebuild this table?')){return false;}else{return true;}";
		}
		else{
			$recs[$i]['class'] = 'icon-checkbox-empty';
			$recs[$i]['onclick'] = "if(!confirm('Create this table - {$rec['name']}?')){return false;}else{return true;}";
		}
	}
	return $recs;
}

/**
 * Retrieves all database tables grouped by tablegroup metadata
 * @return array ['tables'=>array, 'counts'=>array, 'total'=>int]
 */
function topmenuGetTablesData(){
	$alltables = getDBTables();
	if(!is_array($alltables)){
		$alltables = array();
	}
	$meta = getDBRecords(array(
		'-table' => '_tabledata',
		'-index' => 'tablename',
		'-fields' => 'tablename,tablegroup,synchronize,tabledesc'
	));
	if(!is_array($meta)){
		$meta = array();
	}
	$tables = array();
	$counts = array();
	foreach($alltables as $table){
		if(preg_match('/^\_/', $table)){
			$key = 'WaSQL';
		}
		else{
			if(isset($meta[$table]['tablegroup']) && strlen(trim($meta[$table]['tablegroup']))){
				$key = trim($meta[$table]['tablegroup']);
			}
			else{
				$key = 'Ungrouped';
			}
		}
		$tables[$key][] = $table;
	}
	ksort($tables);
	foreach($tables as $key => $tbls){
		sort($tables[$key]);
		$counts[$key] = count($tbls);
	}
	return array(
		'tables' => $tables,
		'counts' => $counts,
		'total'  => count($alltables)
	);
}

/**
 * Retrieves the most recent pages
 * @param int $limit
 * @return array
 */
function topmenuGetRecentPages($limit = 15){
	$limit = (int)$limit > 0 ? (int)$limit : 15;
	$recs = getDBRecords(array(
		'-table' => '_pages',
		'-fields' => '_id,name,_edate',
		'-order' => '_edate desc,_adate desc',
		'-limit' => $limit
	));
	return is_array($recs) ? $recs : array();
}

/**
 * Retrieves templates
 * @param int $limit
 * @return array
 */
function topmenuGetTemplates($limit = 15){
	$limit = (int)$limit > 0 ? (int)$limit : 15;
	$recs = getDBRecords(array(
		'-table' => '_templates',
		'-fields' => '_id,name',
		'-order' => 'name',
		'-limit' => $limit
	));
	return is_array($recs) ? $recs : array();
}

/**
 * Retrieves the most recent / active users
 * @param int $limit
 * @return array
 */
function topmenuGetRecentUsers($limit = 15){
	$limit = (int)$limit > 0 ? (int)$limit : 15;
	$recs = getDBRecords(array(
		'-table' => '_users',
		'-fields' => '_id,username,utype',
		'-order' => '_edate desc,_adate desc',
		'-limit' => $limit
	));
	return is_array($recs) ? $recs : array();
}

/**
 * Normalizes admin color to consistent CSS class
 * @return string
 */
function topmenuGetColorClass(){
	global $CONFIG;
	$color = isset($CONFIG['admin_color']) ? strtolower(trim($CONFIG['admin_color'])) : 'dark';
	if(!strlen($color)){
		$color = 'dark';
	}
	if(stringBeginsWith($color, 'is-')){
		return $color;
	}
	switch($color){
		case 'gray':
		case 'grey':
			return 'is-gray';
		case 'black':
			return 'is-black';
		case 'light':
		case 'white':
			return 'is-light';
		case 'blue':
			return 'is-link';
		case 'teal':
		case 'cyan':
			return 'is-info';
		case 'green':
			return 'is-success';
		case 'orange':
			return 'is-orange';
		case 'yellow':
			return 'is-warning';
		case 'red':
			return 'is-danger';
		case 'turquoise':
			return 'is-primary';
		case 'dark':
		default:
			return 'is-dark';
	}
}

/**
 * Renders HTML for Table Groups -> Tables -> Actions nested dropdown
 * @param array $tables
 * @param array $counts
 * @return string
 */
function topmenuRenderTableGroupMenu($tables, $counts){
	if(!is_array($tables) || empty($tables)){
		return '<li><a href="/php/admin.php?_menu=tables"><span class="icon-list"></span> No tables found</a></li>';
	}
	$rtn = '';
	foreach($tables as $groupName => $groupTables){
		$groupEsc = encodeHtml($groupName);
		$cnt = isset($counts[$groupName]) ? $counts[$groupName] : count($groupTables);
		$rtn .= '<li class="topmenu-has-sub"><a href="#group_' . $groupEsc . '" class="dropdown topmenu-sub-trigger" onclick="return false;">';
		$rtn .= '<span class="icon-group"></span> <span class="topmenu-label-text">' . $groupEsc . ' Tables</span>';
		$rtn .= '<span class="topmenu-badge">' . $cnt . '</span></a>';
		$rtn .= '<ul class="topmenu-scrollable">';
		foreach($groupTables as $table){
			$tableEsc = encodeHtml($table);
			$rtn .= '<li class="topmenu-has-sub"><a href="/php/admin.php?_menu=list&amp;_table_=' . urlencode($table) . '" class="dropdown topmenu-sub-trigger">';
			$rtn .= '<span class="icon-table"></span> <span class="topmenu-label-text">' . $tableEsc . '</span></a>';
			$rtn .= '<ul class="topmenu-actions-menu">';
			$rtn .= '<li class="topmenu-header"><span class="icon-table"></span> ' . $tableEsc . '</li>';
			$rtn .= '<li><hr /></li>';
			$rtn .= '<li><a href="/php/admin.php?_menu=list&amp;_table_=' . urlencode($table) . '"><span class="icon-list"></span> List Records</a></li>';
			$rtn .= '<li><a href="/php/admin.php?_menu=add&amp;_table_=' . urlencode($table) . '"><span class="icon-plus"></span> Add New Record</a></li>';
			$rtn .= '<li><a href="/php/admin.php?_menu=properties&amp;_table_=' . urlencode($table) . '"><span class="icon-table-add"></span> Table Properties</a></li>';
			$rtn .= '<li><a href="/php/admin.php?_menu=indexes&amp;_table_=' . urlencode($table) . '"><span class="icon-optimize"></span> Show Indexes</a></li>';
			$rtn .= '<li><a href="/php/admin.php?_menu=add&amp;_table_=_models&amp;name=' . urlencode($table) . '"><span class="icon-toggle-on"></span> Triggers</a></li>';
			$rtn .= '<li><a href="/php/admin.php?_menu=backup&amp;_table_=' . urlencode($table) . '"><span class="icon-save"></span> Backup Table</a></li>';
			$rtn .= '<li><a href="/php/admin.php?_menu=truncate&amp;_table_=' . urlencode($table) . '" onclick="return confirm(\'PLEASE READ THIS CAREFULLY!\\n\\nPURGE ALL RECORDS?\\nTable: ' . $tableEsc . '\\nAction: Truncate (Irreversible)\\n\\nClick OK to confirm.\');"><span class="icon-blank"></span> Truncate Table</a></li>';
			$rtn .= '<li><a href="/php/admin.php?_menu=drop&amp;_table_=' . urlencode($table) . '" onclick="return confirm(\'PLEASE READ THIS CAREFULLY!\\n\\nDELETE TABLE PERMANENTLY?\\nTable: ' . $tableEsc . '\\nAction: Drop (Irreversible)\\n\\nClick OK to confirm.\');"><span class="icon-erase"></span> Delete Table</a></li>';
			$rtn .= '</ul>';
			$rtn .= '</li>';
		}
		$rtn .= '</ul>';
		$rtn .= '</li>';
	}
	return $rtn;
}

/**
 * Renders HTML for recent pages
 * @param array $pages
 * @return string
 */
function topmenuRenderRecentPages($pages){
	if(!is_array($pages) || empty($pages)){
		return '';
	}
	$rtn = '';
	foreach($pages as $p){
		$id = (int)$p['_id'];
		$nameEsc = encodeHtml($p['name']);
		$rtn .= '<li><a href="/php/admin.php?_menu=edit&amp;_table_=_pages&amp;_id=' . $id . '"><span class="topmenu-num">' . $id . '.</span> <span class="topmenu-label-text">' . $nameEsc . '</span></a></li>';
	}
	return $rtn;
}

/**
 * Renders HTML for templates
 * @param array $templates
 * @return string
 */
function topmenuRenderTemplates($templates){
	if(!is_array($templates) || empty($templates)){
		return '';
	}
	$rtn = '';
	foreach($templates as $t){
		$id = (int)$t['_id'];
		$nameEsc = encodeHtml($t['name']);
		$rtn .= '<li><a href="/php/admin.php?_menu=edit&amp;_table_=_templates&amp;_id=' . $id . '"><span class="topmenu-num">' . $id . '.</span> <span class="topmenu-label-text">' . $nameEsc . '</span></a></li>';
	}
	return $rtn;
}

/**
 * Renders HTML for users
 * @param array $users
 * @return string
 */
function topmenuRenderUsers($users){
	if(!is_array($users) || empty($users)){
		return '';
	}
	$rtn = '';
	foreach($users as $u){
		$id = (int)$u['_id'];
		$nameEsc = encodeHtml($u['username']);
		$adminBadge = (isset($u['utype']) && (int)$u['utype'] === 0) ? '<span class="topmenu-tag">Admin</span>' : '';
		$rtn .= '<li><a href="/php/admin.php?_menu=edit&amp;_table_=_users&amp;_id=' . $id . '"><span class="topmenu-num">' . $id . '.</span> <span class="topmenu-label-text">' . $nameEsc . '</span>' . $adminBadge . '</a></li>';
	}
	return $rtn;
}