<?php
function metaShowList(){
	$opts=array(
		'-table'=>'_pages',
		'-action'=>'/php/admin.php',
		'_menu'=>'meta',
		'-listfields'=>'_id,name,_template,meta_image,meta_title,meta_description,meta_keywords',
		'-where'=>'_template != 1',
		'-order'=>'_template,name',
		'-tableclass'=>'table bordered is-bordered striped is-striped',
		'-editfields'=>'meta_image,meta_title,meta_description,meta_keywords',
		'-sorting'=>1
	);
	return databaseListRecords($opts);
}
?>