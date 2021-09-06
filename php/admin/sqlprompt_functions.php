<?php
loadExtras('translate');
function sqlpromptGetTables($dbname=''){
	global $CONFIG;
	if(strlen($dbname)){
		$tables=dbGetTables($dbname);
	}
	else{
		$tables=getDBTables();
	}	
	if(isset($CONFIG['sqlprompt_tables'])){
		if(!is_array($CONFIG['sqlprompt_tables'])){
			$CONFIG['sqlprompt_tables']=preg_split('/\,/',$CONFIG['sqlprompt_tables']);
		}
		foreach($tables as $i=>$table){
			if(!in_array($table,$CONFIG['sqlprompt_tables'])){
				unset($tables[$i]);
			}
		}
	}
	if(isset($CONFIG['sqlprompt_tables_filter'])){
		if(!is_array($CONFIG['sqlprompt_tables_filter'])){
			$CONFIG['sqlprompt_tables_filter']=preg_split('/\,/',$CONFIG['sqlprompt_tables_filter']);
		}
		//echo printValue($CONFIG['sqlprompt_tables_filter']);
		foreach($tables as $i=>$table){
			$found=0;
			foreach($CONFIG['sqlprompt_tables_filter'] as $filter){
				if(stringContains($table,$filter)){$found+=1;}
			}
			if($found==0){
				unset($tables[$i]);
			}
		}
	}
	$CONFIG['sqlprompt_tables_hide_wasql']=1;
	if(isset($CONFIG['sqlprompt_tables_hide_wasql'])){
		foreach($tables as $i=>$table){
			if(stringBeginsWith($table,'_')){
				unset($tables[$i]);
			}
		}
	}
	return $tables;
}
function sqlpromptShowlist($recs,$listopts=array()){
	$opts=array(
		'-list'=>$recs,
		'-tableclass'=>'table bordered striped responsive',
		'-hidesearch'=>1,
		'-sorting'=>1
	);
	if(is_array($listopts) && count($listopts)){
		foreach($listopts as $k=>$v){
			$opts[$k]=$v;
		}
	}
	//unset($opts['-list']);echo printValue($opts);exit;
	return databaseListRecords($opts);
}
function sqlpromptCaptureFirstRows($rec,$max=30){
	global $recs;
	global $sqlpromptCaptureFirstRows_count;
	$sqlpromptCaptureFirstRows_count+=1;
	if(count($recs) < $max){
		$recs[]=$rec;
	}
	return;
}
function sqlpromptListResults($recs){
	if(!is_array($recs)){
		if(strlen($recs)){return $recs;}
		return translateText('No Results');
	}
	if(!count($recs)){
		return translateText('No Results');
	}
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped bordered condensed responsive'
	);
	$sumfields=array();
	foreach($recs[0] as $k=>$v){
		if(preg_match('/\_(count|size)$/i',$k)){
			$opts["{$k}_class"]='align-right';
			$sumfields[]=$k;
		}
	}
	if(count($sumfields)){
		$opts['-sumfields']=implode(',',$sumfields);
	}
	return databaseListRecords($opts);
}
function sqlpromptListFields($recs){
	if(!is_array($recs) || !count($recs)){
		if(strlen($recs)){return $recs;}
		return translateText('No fields defined');
	}
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped condensed responsive',
		'-listfields'=>'_dbfield,_dbtype_ex',
		'-thclass'=>'w_smaller',
		'-tdclass'=>'w_smaller',
		'_dbfield_options'=>array(
			'displayname'=>'Field',
			'style'=>'max-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;',
			'title'=>'%_dbfield%'
		),
		'_dbtype_ex_displayname'=>'Type'
	);
	return databaseListRecords($opts);
}
function sqlpromptListIndexes($recs){
	if(!is_array($recs)){
		if(strlen($recs)){return $recs;}
		return translateText('No indexes defined');
	}
	if(!count($recs)){
		return translateText('No indexes defined');
	}
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped condensed responsive',
		'-listfields'=>'key_name,column_name,is_primary,is_unique,seq_in_index',
		'-thclass'=>'w_smaller',
		'-tdclass'=>'w_smaller',
		'is_primary_displayname'=>'Pk',
		'is_primary_checkmark'=>1,
		'key_name_options'=>array(
			'displayname'=>'Index',
			'style'=>'max-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;',
			'title'=>'%key_name%'
		),
		'is_unique_displayname'=>'Un',
		'is_unique_checkmark'=>1,
		'column_name_displayname'=>'Column',
		'seq_in_index_displayname'=>'Seq',
		'index_type_displayname'=>'Type'
	);
	return databaseListRecords($opts);
}
function sqlpromptBuildQuery($db,$name){
	global $DATABASE;
	//echo printValue($DATABASE[$db]);exit;
	switch(strtolower($DATABASE[$db]['dbtype'])){
		case 'mssql':
			loadExtras('mssql');
			global $dbh_mssql;
			$dbh_mssql='';
			return trim(mssqlNamedQuery($name));
		break;
		case 'postgresql':
		case 'postgres':
		case 'postgre';
			loadExtras('postgresql');
			global $dbh_postgresql;
			$dbh_postgresql='';
			return trim(postgresqlNamedQuery($name));
		break;
		case 'oracle':
			loadExtras('oracle');
			global $dbh_oracle;
			$dbh_oracle='';
			return trim(oracleNamedQuery($name));
		break;
		case 'hana':
			loadExtras('hana');
			global $dbh_hana;
			$dbh_hana='';
			return trim(hanaNamedQuery($name));
		break;
		case 'sqlite':
			loadExtras('sqlite');
			global $dbh_sqlite;
			$dbh_sqlite='';
			return trim(sqliteNamedQuery($name));
		break;
		default:
			loadExtras('mysql');
			global $dbh_mysql;
			$dbh_mysql='';
			return trim(mysqlNamedQuery($name));
		break;
	}

}
?>
