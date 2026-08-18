<?php
/**
 * WaSQL Admin Top Menu Helper Functions
 */

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