<?php
function checktablesList(){
	$recs=checkDBTables();
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped bordered',
		//'table_checkbox'=>1
	);
	return databaseListRecords($opts);
}
?>