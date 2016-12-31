<?php
	global $PAGE;
	if(isset($_REQUEST['passthru'][0])){$_REQUEST['func']=$_REQUEST['passthru'][0];}
	switch(strtolower($_REQUEST['func'])){
    	case 'ip':
    		$ip=addslashes($_REQUEST['passthru'][1]);
			$ok=editDBRecord(array(
				'-table'	=> '_pages',
				'-where'	=> "_id={$PAGE['_id']}",
				'appid'		=> $ip
			));
			exit;
    	break;
    	default:
    		header("Location: http://{$PAGE['appid']}:8069");
    		exit;
    	break;
	}
?>
