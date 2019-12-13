<?php
loadExtras('translate');
function sqlpromptListResults($recs){
	if(!is_array($recs) || !count($recs)){
		if(strlen($recs)){return $recs;}
		return translateText('No Results');
	}
	return databaseListRecords(array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped bordered condensed'
	));
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
			switch(strtolower($name)){
				case 'running_queries':
					return 'show processlist';
				break;
				case 'sessions':
					return "select id, user, host, db, command, time, state, info from information_schema.processlist";
				break;
				case 'table_locks':
					return 'SHOW OPEN TABLES WHERE In_use > 0';
				break;
				case 'functions':
					//routine_definition, routine_comment
					return "SELECT routine_catalog,routine_schema,routine_name,created,last_altered,definer FROM information_schema.ROUTINES WHERE routine_type = 'FUNCTION' and routine_schema != 'sys'";
				break;
				case 'procedures':
					return "SELECT routine_catalog,routine_schema,routine_name,created,last_altered,definer FROM information_schema.ROUTINES WHERE routine_type = 'PROCEDURE' and routine_schema != 'sys'";
				break;
			}
		break;
	}

}
?>
