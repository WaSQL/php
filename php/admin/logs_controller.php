<?php
	global $CONFIG;
	$refresh=isset($CONFIG['logs_refresh'])?(integer)$CONFIG['logs_refresh']:60;
	$logs=logsGetLogs();
	setView('default');
	if($refresh > 15){setView('refresh');}
?>