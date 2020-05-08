<?php
loadExtras('translate');
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
