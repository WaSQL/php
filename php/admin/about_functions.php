<?php
//========================================================================
// About WaSQL - interactive dashboard (model / HTML builders)
//   Loaded by adminViewPage('about'). All lengthy HTML-string building
//   lives here so the controller stays thin and the body only echoes.
//========================================================================

//---------- begin function aboutEnvironment ----
/**
* @exclude - internal admin helper
* @describe Returns a small map describing the running environment used by the
*	overview cards (php, database, os, host, user, environment stage).
* @return array
* @usage $env=aboutEnvironment();
*/
function aboutEnvironment(){
	global $USER; global $CONFIG; global $DATABASE;
	$bits=(PHP_INT_SIZE*8).'-bit';
	$dbtype=function_exists('databaseType')?databaseType():'';
	$dbver='';
	if(function_exists('getDBVersion')){
		$v=getDBVersion();
		if(is_string($v)){$dbver=trim($v);}
	}
	$isstage=function_exists('isDBStage')?isDBStage():0;
	$dbname='';
	if(is_array($DATABASE) && isset($DATABASE['DBNAME'])){$dbname=$DATABASE['DBNAME'];}
	elseif(isset($CONFIG['dbname'])){$dbname=$CONFIG['dbname'];}
	return array(
		'php_version'	=> phpversion(),
		'php_sapi'		=> php_sapi_name(),
		'php_bits'		=> $bits,
		'db_type'		=> strlen($dbtype)?$dbtype:'unknown',
		'db_version'	=> $dbver,
		'db_name'		=> $dbname,
		'db_conn'		=> isset($CONFIG['database'])?$CONFIG['database']:'',
		'os'			=> php_uname('s').' '.php_uname('r'),
		'os_machine'	=> php_uname('m'),
		'host'			=> isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:php_uname('n'),
		'server'		=> isset($_SERVER['SERVER_SOFTWARE'])?$_SERVER['SERVER_SOFTWARE']:'',
		'user'			=> isset($USER['username'])?$USER['username']:'',
		'user_admin'	=> isUser() && isAdmin() ? 1 : 0,
		'stage'			=> $isstage ? 1 : 0,
		'ext_count'		=> count(get_loaded_extensions()),
	);
}

//---------- begin function aboutFactCards ----
/**
* @exclude - internal admin helper
* @describe Builds the Overview tab: a grid of at-a-glance cards plus quick links
*	into the deeper system pages.
* @return string html
* @usage <?=aboutFactCards();?>
*/
function aboutFactCards(){
	$e=aboutEnvironment();
	$cards=array();
	$cards[]=aboutCard('icon-php','PHP',encodeHtml($e['php_version']),encodeHtml($e['php_sapi']).' &middot; '.encodeHtml($e['php_bits']));
	//the db card leads with the database NAME - the thing you come here to check -
	//and drops engine + version underneath
	$dbengine=aboutDbEngineLabel($e['db_type']);
	$dbval=strlen($e['db_name'])?encodeHtml($e['db_name']):encodeHtml($dbengine);
	$dbsub=encodeHtml($dbengine);
	if(strlen($e['db_version'])){$dbsub.=' &middot; '.encodeHtml($e['db_version']);}
	if(strlen($e['db_conn']) && strtolower($e['db_conn'])!==strtolower($e['db_name'])){
		$dbsub.='<br>connection: '.encodeHtml($e['db_conn']);
	}
	$cards[]=aboutCard('icon-database','Database',$dbval,$dbsub);
	$cards[]=aboutCard('icon-server','Server OS',encodeHtml($e['os']),encodeHtml($e['os_machine']));
	$cards[]=aboutCard('icon-window','Web Server',encodeHtml(strlen($e['server'])?preg_replace('/ .*/','',$e['server']):'&mdash;'),encodeHtml($e['host']));
	if($e['stage']){
		$envval='<span class="about-env-stage">Staging / Dev</span>';
		$envsub='isDBStage() = true';
	}
	else{
		$envval='<span class="about-env-prod">Production</span>';
		$envsub='isDBStage() = false';
	}
	$cards[]=aboutCard('icon-sliders','Environment',$envval,$envsub);
	$usub=$e['user_admin']?'administrator':'standard user';
	$cards[]=aboutCard('icon-user-admin','Signed In',encodeHtml(strlen($e['user'])?$e['user']:'&mdash;'),$usub);
	$cards[]=aboutCard('icon-plug','PHP Extensions',number_format($e['ext_count'],0),'loaded modules');
	$cards[]=aboutCard('icon-clock','Server Time',date('g:i A'),date('D, M j Y').' &middot; '.date('T'));

	$out ='<div class="about-grid">'.implode('',$cards).'</div>'.PHP_EOL;
	$out.='<div class="about-quick">'.PHP_EOL;
	$out.='	<a class="wacss_button is-small" href="/php/admin.php?_menu=system"><span class="icon-server wacss_right5"></span> System Information</a>'.PHP_EOL;
	$out.='	<a class="wacss_button is-small" href="/php/admin.php?_menu=env"><span class="icon-list wacss_right5"></span> Server Variables</a>'.PHP_EOL;
	$out.='	<a class="wacss_button is-small" href="/php/admin.php?_menu=phpinfo" target="_blank"><span class="icon-php wacss_right5"></span> Full phpinfo()</a>'.PHP_EOL;
	$out.='	<a class="wacss_button is-small" href="https://wasql.com" target="_blank"><span class="icon-wasql wacss_right5"></span> wasql.com</a>'.PHP_EOL;
	$out.='</div>'.PHP_EOL;
	return $out;
}

//---------- begin function aboutCard ----
/**
* @exclude - internal admin helper
* @describe Renders one overview stat card. $value / $sub may contain safe html.
* @return string html
* @usage $html=aboutCard('icon-php','PHP','8.3','cli');
*/
function aboutCard($icon,$label,$value,$sub=''){
	$out ='<div class="about-card">'.PHP_EOL;
	$out.='	<div class="about-card-ico"><span class="'.encodeHtml($icon).'"></span></div>'.PHP_EOL;
	$out.='	<div class="about-card-k">'.encodeHtml($label).'</div>'.PHP_EOL;
	$out.='	<div class="about-card-v">'.$value.'</div>'.PHP_EOL;
	if(strlen($sub)){
		$out.='	<div class="about-card-sub">'.$sub.'</div>'.PHP_EOL;
	}
	$out.='</div>'.PHP_EOL;
	return $out;
}

//---------- begin function aboutDbEngineLabel ----
/**
* @exclude - internal admin helper
* @describe Maps a raw driver name (mysqli, pgsql, sqlsrv...) to a friendly engine label.
* @return string
* @usage $label=aboutDbEngineLabel(databaseType());
*/
function aboutDbEngineLabel($type){
	$t=strtolower(trim((string)$type));
	$map=array(
		'mysqli'=>'MySQL','mysql'=>'MySQL','mariadb'=>'MariaDB',
		'pgsql'=>'PostgreSQL','postgresql'=>'PostgreSQL','postgres'=>'PostgreSQL',
		'sqlite'=>'SQLite','sqlite3'=>'SQLite',
		'sqlsrv'=>'SQL Server','mssql'=>'SQL Server','dblib'=>'SQL Server',
		'oci8'=>'Oracle','oci'=>'Oracle','oracle'=>'Oracle',
		'hana'=>'SAP HANA','odbc'=>'ODBC',
	);
	if(isset($map[$t])){return $map[$t];}
	return strlen($t)?ucfirst($t):'Database';
}

//---------- begin function aboutKvTable ----
/**
* @exclude - internal admin helper
* @describe Builds a searchable key/value table with per-row + copy-all buttons.
* @param string $id - unique dom id for the table
* @param array $rows - list of array('name'=>, 'value'=>) (value shown + copied as-is)
* @param string $note - optional caption under the search box
* @return string html
* @usage <?=aboutKvTable('about_versions_tbl',aboutVersionRows());?>
*/
function aboutKvTable($id,$rows,$note=''){
	$count=count($rows);
	$out ='<div class="about-toolbar">'.PHP_EOL;
	$out.='	<input type="text" class="wacss_input is-small about-search" placeholder="Filter&hellip;" onkeyup="aboutFilter(this,\''.$id.'\');" />'.PHP_EOL;
	$out.='	<span class="about-toolbar-count"><span id="'.$id.'_count">'.$count.'</span> of '.$count.'</span>'.PHP_EOL;
	$out.='	<button type="button" class="wacss_button is-small" onclick="return aboutCopyRows(\''.$id.'\');"><span class="icon-copy wacss_right5"></span> Copy visible</button>'.PHP_EOL;
	$out.='</div>'.PHP_EOL;
	if(strlen($note)){
		$out.='<div class="about-note">'.$note.'</div>'.PHP_EOL;
	}
	$out.='<table id="'.$id.'" class="wacss_table is-striped is-fullwidth about-kv">'.PHP_EOL;
	$out.='<tbody>'.PHP_EOL;
	foreach($rows as $row){
		$name=isset($row['name'])?(string)$row['name']:'';
		$value=isset($row['value'])?(string)$row['value']:'';
		if(!strlen(trim($value))){$value='';}
		$out.='	<tr data-name="'.encodeHtml($name).'" data-value="'.encodeHtml($value).'">'.PHP_EOL;
		$out.='		<th class="about-kv-k">'.encodeHtml($name).'</th>'.PHP_EOL;
		$out.='		<td class="about-kv-v"><span class="about-val">'.(strlen($value)?encodeHtml($value):'<span class="about-empty">&mdash;</span>').'</span>';
		if(strlen($value)){
			$out.=' <button type="button" class="about-copy" title="Copy value" data-copy="'.encodeHtml($value).'" onclick="return aboutCopy(this);"><span class="icon-copy"></span></button>';
		}
		$out.='</td>'.PHP_EOL;
		$out.='	</tr>'.PHP_EOL;
	}
	$out.='</tbody>'.PHP_EOL;
	$out.='</table>'.PHP_EOL;
	return $out;
}

//---------- begin function aboutVersionRows ----
/**
* @exclude - internal admin helper
* @describe Normalizes getAllVersions() into name/value rows, dropping blanks.
* @return array
* @usage $rows=aboutVersionRows();
*/
function aboutVersionRows(){
	$rows=array();
	$versions=getAllVersions();
	if(is_array($versions)){
		foreach($versions as $k=>$v){
			if(!is_string($v)){continue;}
			$v=trim($v);
			if(!strlen($v)){continue;}
			$rows[]=array('name'=>$k,'value'=>$v);
		}
	}
	return $rows;
}

//---------- begin function aboutServerRows ----
/**
* @exclude - internal admin helper
* @describe Host / OS detail rows.
* @return array
* @usage $rows=aboutServerRows();
*/
function aboutServerRows(){
	$rows=array(
		array('name'=>'Operating System',	'value'=>php_uname('s')),
		array('name'=>'Host Name',			'value'=>php_uname('n')),
		array('name'=>'Release',			'value'=>php_uname('r')),
		array('name'=>'Version',			'value'=>php_uname('v')),
		array('name'=>'Machine Type',		'value'=>php_uname('m')),
	);
	if(isset($_SERVER['SERVER_SOFTWARE'])){
		$rows[]=array('name'=>'Web Server','value'=>$_SERVER['SERVER_SOFTWARE']);
	}
	if(isset($_SERVER['SERVER_ADDR'])){
		$rows[]=array('name'=>'Server Address','value'=>$_SERVER['SERVER_ADDR'].(isset($_SERVER['SERVER_PORT'])?':'.$_SERVER['SERVER_PORT']:''));
	}
	if(isset($_SERVER['HTTP_HOST'])){
		$rows[]=array('name'=>'HTTP Host','value'=>$_SERVER['HTTP_HOST']);
	}
	if(isset($_SERVER['DOCUMENT_ROOT'])){
		$rows[]=array('name'=>'Document Root','value'=>$_SERVER['DOCUMENT_ROOT']);
	}
	return $rows;
}

//---------- begin function aboutPhpRows ----
/**
* @exclude - internal admin helper
* @describe PHP runtime / path / limit rows.
* @return array
* @usage $rows=aboutPhpRows();
*/
function aboutPhpRows(){
	global $USER;
	$rows=array(
		array('name'=>'PHP Version',		'value'=>phpversion()),
		array('name'=>'SAPI',				'value'=>php_sapi_name()),
		array('name'=>'Architecture',		'value'=>(PHP_INT_SIZE*8).'-bit'),
		array('name'=>'Zend Version',		'value'=>zend_version()),
		array('name'=>'Loaded php.ini',		'value'=>php_ini_loaded_file()),
		array('name'=>'Scanned .ini Files',	'value'=>trim((string)php_ini_scanned_files())),
		array('name'=>'Include Path',		'value'=>get_include_path()),
		array('name'=>'Script Owner',		'value'=>get_current_user()),
		array('name'=>'System Temp Dir',	'value'=>sys_get_temp_dir()),
		array('name'=>'WaSQL Path',			'value'=>dirname(dirname(__FILE__))),
		array('name'=>'Signed-in User',		'value'=>isset($USER['username'])?$USER['username']:''),
	);
	$limits=array('memory_limit','max_execution_time','max_input_time','upload_max_filesize','post_max_size','max_input_vars','default_socket_timeout','date.timezone','display_errors','error_reporting','opcache.enable');
	foreach($limits as $key){
		$val=ini_get($key);
		if($val===false){continue;}
		if($val===''){$val='(not set)';}
		$rows[]=array('name'=>$key,'value'=>(string)$val);
	}
	return $rows;
}

//---------- begin function aboutConfigRows ----
/**
* @exclude - internal admin helper
* @describe config.xml settings for this host, with password-ish values masked and
*	internal (leading underscore) / array values skipped - same rules as the old page.
* @return array
* @usage $rows=aboutConfigRows();
*/
function aboutConfigRows(){
	global $CONFIG;
	$rows=array();
	if(!is_array($CONFIG)){return $rows;}
	$keys=array_keys($CONFIG);
	sort($keys,SORT_NATURAL|SORT_FLAG_CASE);
	foreach($keys as $key){
		if(preg_match('/^_/',$key)){continue;}
		$val=$CONFIG[$key];
		if(is_array($val)){continue;}
		$val=(string)$val;
		if(preg_match('/(pass|password|secret|apikey|api_key|token|private_key)$/i',$key) && strlen($val)){
			$val=str_repeat('*',min(strlen($val),12));
		}
		$rows[]=array('name'=>$key,'value'=>$val);
	}
	return $rows;
}

//---------- begin function aboutExtensionsHtml ----
/**
* @exclude - internal admin helper
* @describe Searchable chip cloud of every loaded PHP extension.
* @return string html
* @usage <?=aboutExtensionsHtml();?>
*/
function aboutExtensionsHtml(){
	$exts=get_loaded_extensions();
	sort($exts,SORT_NATURAL|SORT_FLAG_CASE);
	$count=count($exts);
	$out ='<div class="about-toolbar">'.PHP_EOL;
	$out.='	<input type="text" class="wacss_input is-small about-search" placeholder="Filter extensions&hellip;" onkeyup="aboutFilterChips(this,\'about_ext_cloud\');" />'.PHP_EOL;
	$out.='	<span class="about-toolbar-count"><span id="about_ext_cloud_count">'.$count.'</span> of '.$count.'</span>'.PHP_EOL;
	$out.='</div>'.PHP_EOL;
	$php=phpversion();
	$out.='<div id="about_ext_cloud" class="about-chips">'.PHP_EOL;
	foreach($exts as $ext){
		$ver=phpversion($ext);
		//most bundled extensions just echo the php version back - only show a
		//version chip when the extension carries its own distinct one
		$showver=($ver && $ver!=='' && $ver!=='0' && $ver!==$php);
		$title=$showver?encodeHtml($ext.' '.$ver):encodeHtml($ext);
		$out.='	<span class="about-chip" title="'.$title.'">'.encodeHtml($ext);
		if($showver){
			$out.=' <span class="about-chip-v">'.encodeHtml($ver).'</span>';
		}
		$out.='</span>'.PHP_EOL;
	}
	$out.='</div>'.PHP_EOL;
	return $out;
}
