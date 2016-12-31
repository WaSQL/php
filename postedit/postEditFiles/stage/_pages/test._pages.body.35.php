<?php
switch(strtolower($_REQUEST['return_type'])){
	case 'speech':
		echo "this is test speech";
	break;
	case 'table':
		echo 'test table';
	break;
	case 'export':
		echo 'test export';
	break;
	case 'data':
		echo 'test,data';
	break;
	default:
		echo 'return type equals '.$_REQUEST['return_type'];
	break;
}
echo " - done";

?>
