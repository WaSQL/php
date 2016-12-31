<?php
	$rec=getDBRecord(array('-table'=>'skillsai_live._pages','name'=>'odoo','-fields'=>'appid'));
	header("Location: http://{$rec['appid']}/rental_application");
    exit;
?>
