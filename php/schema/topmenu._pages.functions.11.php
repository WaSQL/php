<?php
	function pageIsActive($name){
    	global $PAGE;
    	if($PAGE['name']==$name){return ' active';}
    	return '';
	}
?>
