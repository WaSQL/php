<?php
$progpath=dirname(__FILE__);
require_once("{$progpath}/websockets_functions.php");
//get command line arguments, removing the first one since it is the filename
array_shift($argv);
//if the first arg is a file assume they want to tail the file
if(is_file($argv[0])){
	wsTailFile($argv[0]);
}
else{
	//send the args as a string to the websocket
	$ok=wsSendMessage(implode(' ',$argv));
}
?>