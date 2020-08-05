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
		'-listfields'=>'key_name,column_name,seq_in_index,non_unique',
		'-thclass'=>'w_smaller',
		'-tdclass'=>'w_smaller',
		'key_name_displayname'=>'Index',
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
			return trim(mssqlNamedQuery($name));
		break;
		case 'postgresql':
		case 'postgres':
		case 'postgre';
			loadExtras('postgresql');
			return trim(postgresqlNamedQuery($name));
		break;
		case 'oracle':
			loadExtras('oracle');
			return trim(oracleNamedQuery($name));
		break;
		case 'hana':
			loadExtras('hana');
			return trim(hanaNamedQuery($name));
		break;
		case 'sqlite':
			return '';
		break;
		default:
			loadExtras('mysql');
			return trim(mysqlNamedQuery($name));
		break;
	}

}
?>
