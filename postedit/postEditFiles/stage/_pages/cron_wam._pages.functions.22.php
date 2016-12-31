<?php
function wamGetClientRecords(){
	return getDBRecords(array(
		'-table'=>'clients',
		'active'=>1
	));
}
?>
