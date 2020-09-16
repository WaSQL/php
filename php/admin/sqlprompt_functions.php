<?php
loadExtras('translate');
function sqlpromptCaptureFirstRows($rec,$max=30){
	global $recs;
	if(count($recs) < $max){
		$recs[]=$rec;
	}
	return;
}
function sqlpromptListResults($recs){
	if(!is_array($recs) || !count($recs)){
		if(strlen($recs)){return $recs;}
		return translateText('No Results');
	}
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped bordered condensed'
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
		'-tableclass'=>'table striped condensed',
		'-listfields'=>'_dbfield,_dbtype_ex',
		'-thclass'=>'w_smaller',
		'-tdclass'=>'w_smaller',
		'_dbfield_displayname'=>'Field',
		'_dbtype_ex_displayname'=>'Type'
	);
	return databaseListRecords($opts);
}
function sqlpromptListIndexes($recs){
	if(!is_array($recs) || !count($recs)){
		if(strlen($recs)){return $recs;}
		return translateText('No indexes defined');
	}
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped condensed',
		'-listfields'=>'key_name,column_name,is_primary,is_unique,seq_in_index',
		'-thclass'=>'w_smaller',
		'-tdclass'=>'w_smaller',
		'is_primary_displayname'=>'Pk',
		'is_primary_checkmark'=>1,
		'key_name_displayname'=>'Index',
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
			return '';
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
