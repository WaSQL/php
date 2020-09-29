<?php
	global $CONFIG;
	$refresh=isset($CONFIG['logs_rowcount'])?(integer)$CONFIG['logs_rowcount']:60;
	$logs=logsGetLogs();
	setView('default');
?>