<?php
function checktablesList(){
	$recs=checkDBTables();
	$opts=array(
		'-list'=>$recs,
		'-hidesearch'=>1,
		'-tableclass'=>'wacss_table striped bordered',
		//'table_checkbox'=>1
	);
	return databaseListRecords($opts);
}
?>