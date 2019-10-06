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
	switch(strtolower($db)){
		case 'mssql':
			loadExtras('mssql');
			return trim(mssqlNamedQuery($name));
		break;
		case 'postgresql':
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

		break;
		default:
			switch(strtolower($name)){
				case 'running_queries':
					return 'show processlist';
				break;
				case 'table_locks':
					return 'SHOW OPEN TABLES WHERE In_use > 0';
				break;
				case 'functions':
					return "SHOW FUNCTION STATUS WHERE db != 'sys'";
				break;
				case 'procedures':
					return "SHOW PROCEDURE STATUS where db != 'sys'";
				break;
			}
		break;
	}

}
?>
