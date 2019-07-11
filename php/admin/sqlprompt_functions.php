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
function sqlpromptBuildQuery($db,$type){
	switch(strtolower($db)){
		case 'postgresql':
			loadExtras('postgresql');
			return trim(postgresqlMonitorSql($type));
		break;
		case 'oracle':

		break;

		break;
		case 'hana':

		break;
		case 'sqlite':

		break;
		default:
			switch(strtolower($type)){
				case 'running_queries':
					return 'show processlist';
				break;
				case 'table_locks':
					return 'SHOW OPEN TABLES WHERE In_use > 0';
				break;
			}
		break;
	}

}
?>
