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
?>
