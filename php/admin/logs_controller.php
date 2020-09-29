<?php
	global $CONFIG;
	$refresh=isset($CONFIG['logs_refresh'])?(integer)$CONFIG['logs_refresh']:60;
	if($refresh < 15){$refresh=15;}
	$logs=logsGetLogs();
	setView('default');
?>