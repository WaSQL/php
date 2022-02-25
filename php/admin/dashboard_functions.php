<?php
function dashboardGetStats(){
	global $CONFIG;
	if(isset($CONFIG['dashboard_tables'])){
		$tables=preg_split('/\,/',$CONFIG['dashboard_tables']);
	}
	else{
		$tables=array('_pages','_templates','_users','_cron','_queries');
	}
	$stats=array();
	$now=date('Y-m-d H:i:s');
	foreach($tables as $table){
		$table=strtolower(trim($table));
		//some tables have an _adate field
		switch($table){
			case '_pages':
			case '_users':
			case '_templates':
				$adate_query=<<<ENDOFQUERY
,
						SUM(CASE WHEN _adate >= DATE_SUB('{$now}', INTERVAL 1 MINUTE) THEN 1 ELSE 0 END) AS adate_minute,
						SUM(CASE WHEN _adate >= DATE_SUB('{$now}', INTERVAL 1 HOUR) THEN 1 ELSE 0 END) AS adate_hour,
						SUM(CASE WHEN _adate >= DATE_SUB('{$now}', INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS adate_day,
						SUM(CASE WHEN _adate >= DATE_SUB('{$now}', INTERVAL 1 WEEK) THEN 1 ELSE 0 END) AS adate_week,
						SUM(CASE WHEN _adate >= DATE_SUB('{$now}', INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS adate_month
ENDOFQUERY;
			break;
			case '_cron':
				$adate_query=<<<ENDOFQUERY
						,SUM(CASE WHEN run_date >= DATE_SUB('{$now}', INTERVAL 1 MINUTE) THEN 1 ELSE 0 END) AS adate_minute,
						SUM(CASE WHEN run_date >= DATE_SUB('{$now}', INTERVAL 1 HOUR) THEN 1 ELSE 0 END) AS adate_hour,
						SUM(CASE WHEN run_date >= DATE_SUB('{$now}', INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS adate_day,
						SUM(CASE WHEN run_date >= DATE_SUB('{$now}', INTERVAL 1 WEEK) THEN 1 ELSE 0 END) AS adate_week,
						SUM(CASE WHEN run_date >= DATE_SUB('{$now}', INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS adate_month
ENDOFQUERY;
			break;
			default:
				$adate_query='';
			break;
		}
		//crec
		$query=<<<ENDOFQUERY
			SELECT
				COUNT(*) AS cnt,
				SUM(CASE WHEN _cdate >= DATE_SUB('{$now}', INTERVAL 1 MINUTE) THEN 1 ELSE 0 END) AS cdate_minute,
				SUM(CASE WHEN _cdate >= DATE_SUB('{$now}', INTERVAL 1 HOUR) THEN 1 ELSE 0 END) AS cdate_hour,
				SUM(CASE WHEN _cdate >= DATE_SUB('{$now}', INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS cdate_day,
				SUM(CASE WHEN _cdate >= DATE_SUB('{$now}', INTERVAL 1 WEEK) THEN 1 ELSE 0 END) AS cdate_week,
				SUM(CASE WHEN _cdate >= DATE_SUB('{$now}', INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS cdate_month,

				SUM(CASE WHEN _edate >= DATE_SUB('{$now}', INTERVAL 1 MINUTE) THEN 1 ELSE 0 END) AS edate_minute,
				SUM(CASE WHEN _edate >= DATE_SUB('{$now}', INTERVAL 1 HOUR) THEN 1 ELSE 0 END) AS edate_hour,
				SUM(CASE WHEN _edate >= DATE_SUB('{$now}', INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS edate_day,
				SUM(CASE WHEN _edate >= DATE_SUB('{$now}', INTERVAL 1 WEEK) THEN 1 ELSE 0 END) AS edate_week,
				SUM(CASE WHEN _edate >= DATE_SUB('{$now}', INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS edate_month

				{$adate_query}
			FROM {$table}
ENDOFQUERY;
		$crec=getDBRecord($query);
		//echo $query.printValue($crec);exit;
		$stat=array(
			'name'=>$table,
			'counts'=>$crec,
		);
		//icon?
		switch($table){
			case '_pages':
				$stat['icon']='icon-file-doc';
			break;
			case '_templates':
				$stat['icon']='icon-file-docs';
			break;
			case '_users':
				$stat['icon']='icon-users';
			break;
			case '_cron':
				$stat['icon']='icon-cron';
			break;
			case '_queries':
				$stat['icon']='icon-sql';
			break;
		}
		
		$stats[]=$stat;
	}
	return $stats;
}
?>
