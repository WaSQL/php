<?php
loadExtras('translate');
function tablesList(){
	global $CONFIG;
	$opts=array(
		'-query'				=>	"show table status",
		'-pretable'				=> evalPHP(getView('change_charset')),
		'-posttable'			=> '</form>',
		'-listfields'			=> 'select,name,field_count,rows,avg_row_length,data_length,index_count,index_length,auto_increment,collation,engine',
		'-hidesearch'			=>1,
		'-tableclass'			=> "table table-responsive table-bordered table-striped",
		'name_href'				=> "/php/admin.php?_menu=list&_table_=%name%",
		'field_count_options'	=> array(
				'class'			=> 'align-right'
		),
		'rows_options'			=>array(
				'eval'				=>	"return number_format(%rows%,0);",
				'class'				=> 'align-right',
				'displayname'		=> 'Row Count'
		),
		'data_length_options'	=>array(
				'eval'				=>	"return verboseSize(%data_length%);",
				'class'				=> 'align-right'
		),
		'avg_row_length_options'=>array(
				'eval'				=>	"return verboseSize(%avg_row_length%);",
				'class'				=> 'align-right',
				'displayname'		=> 'Row Length'
		),
		'index_count_options'	=> array(
				'class'			=> 'align-right'
		),
		'index_length_options'	=>array(
				'eval'				=>	"return verboseSize(%index_length%);",
				'class'				=> 'align-right'
		),
		'auto_increment_class'	=> 'align-right',
		'-sumfields'			=> 'data_length,index_length',
		'-results_eval'			=>'tablesListExtra',
		'select_options'		=> array(
				'checkbox'			=> 1,
				'checkbox_value'	=> '%name%',
				'displayname'		=> '&nbsp;'
		),

	);
	return databaseListRecords($opts);
}
function tablesListExtra($recs){
	//get a fields list
	$query=<<<ENDOFQUERY
		SELECT 
			table_name,
			group_concat(distinct column_name order by ordinal_position SEPARATOR '<br> ') as fields,
			count(*) as field_count 
		FROM information_schema.columns
		WHERE table_schema=database()
		GROUP BY table_name
ENDOFQUERY;
	$frecs=getDBRecords(array(
		'-query'=>$query,
		'-index'=>'table_name'
	));
	//get a indexes list
	$query=<<<ENDOFQUERY
		SELECT 
			table_name,
			group_concat(distinct index_name SEPARATOR '<br> ') as indexes,
			count(*) as index_count 
		FROM information_schema.statistics
		WHERE table_schema=database()
		GROUP BY table_name
ENDOFQUERY;
	$irecs=getDBRecords(array(
		'-query'=>$query,
		'-index'=>'table_name'
	));
	foreach($recs as $i=>$rec){
		$table=$rec['name'];
		$recs[$i]['field_count']='';
		if(isset($frecs[$table])){
			$cnt=$frecs[$table]['field_count'];
			$recs[$i]['field_count']='<div class="w_pointer align-right" style="display:block;width:100%;" data-tip="'.$table.' fields:<hr>'.$frecs[$table]['fields'].'" data-tip_position="right">'.$cnt.'</div>';
		}
		if(isset($irecs[$table])){
			$cnt=$irecs[$table]['index_count'];
			$recs[$i]['index_count']='<div class="w_pointer align-right" style="display:block;width:100%;" data-tip="'.$table.' indexes:<hr>'.$irecs[$table]['indexes'].'" data-tip_position="right">'.$cnt.'</div>';
		}
		if(!stringContains($rec['collation'],'utf')){
			$recs[$i]['collation']='<span class="w_red">'.$rec['collation'].'</span>';
		}
	}
	return $recs;
}
function tablesBuildCharsets(){
	$charsets=getDBCharsets();
	$current_charset=getDBCharset();
	$params=array('message'=>'-- Available Charsets --');
	foreach($charsets as $k=>$v){
		if(stringContains($current_charset,$k)){
			//$charsets[$k]="{$v} (DEFAULT)";
		}
	}
	//return $current_charset;
	return buildFormSelect('charset',$charsets,$params);
}
function tablesGetPreBuilt(){
	$recs=array(
		array('name'=>'cities','file'=>'all_cities'),	
		array('name'=>'states','file'=>'all_states'),
		array('name'=>'countries','file'=>'all_countries'),
		array('name'=>'colors','file'=>'all_colors'),
	);
	foreach($recs as $i=>$rec){
		if(isDBTable($rec['name'])){
			$recs[$i]['class']='icon-checkbox';
			$recs[$i]['data-confirm']="A {$rec['name']} table already exists. Rebuild this table?";
		}
		else{
			$recs[$i]['class']='icon-checkbox-empty';
			$recs[$i]['data-confirm']="Create this table - {$rec['name']}?'";
		}
	}
	return $recs;
}
?>
