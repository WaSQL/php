<?php
/*
	cron_run.php is simply meant to simulate a scheduled task - launches cron.php every 30 seconds
*/
$progpath=dirname(__FILE__);
include_once("{$progpath}/common.php");
while(1){
	cmdResults('php cron.php');
	sleep(30);
}
?>