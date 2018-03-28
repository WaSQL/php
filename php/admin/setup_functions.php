<?php
function setupTypeChecked($v){
	if(isset($_REQUEST['starttype']) && $_REQUEST['starttype']==$v){return ' checked';}
	return '';
}

?>
