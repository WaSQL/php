<?php
$start=microtime(true);
$progpath=dirname(__FILE__);
$begin=$start;
$times=array();
$loads=array('common.php','config.php','wasql.php','database.php','schema.php','sessions.php','user.php');
foreach($loads as $load){
	$begin=microtime(true);
	include_once("{$progpath}/{$load}");
	$end=microtime(true);
	$times[$load]=$end-$begin;
}
//query
$begin=microtime(true);
$recs=getDBRecords(array('-table'=>'countries'));
$cnt=count($recs);
$end=microtime(true);
$times["query countries table ({$cnt} results)"]=$end-$begin;
$stop=microtime(true);
$times['TOTAL']=$stop-$start;
echo "LOAD TIMES BY MODULE".PHP_EOL;
echo printValue($times);
exit;
?>