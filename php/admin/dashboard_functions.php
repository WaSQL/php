<?php
function dashboardGetStats(){
	$tables=array('pages','templates','users','cron','queries');
	$stats=array();
	foreach($tables as $table){
		$query="SELECT _id,_cuser,date(_cdate) as _cdate from _{$table} where _cuser > 0 order by _cdate limit 1";
		$crec=getDBRecord(array('-query'=>$query,'-relate'=>array('_cuser'=>'_users')));
		$query="SELECT _id,_euser,date(_edate) as _edate from _{$table} where _euser > 0 order by _edate limit 1";
		$erec=getDBRecord(array('-query'=>$query,'-relate'=>array('_cuser'=>'_users')));
		$stat=array(
			'name'=>$table,
			'cnt'=>getDBCount(array('-table'=>"_{$table}")),
			'crec'=>$crec,
			'erec'=>$erec
		);
		$stats[]=$stat;
	}
	return $stats;
}
?>
