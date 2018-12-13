<?php
/*
requests int NOT NULL
concurrency int NOT NULL Default 1
url varchar(255) NOT NULL
basic_auth varchar(200) NULL
proxy_auth varchar(200) NULL
proxy varchar(255) NULL
result text
*/
function pageABForm(){
	return addEditDBForm(array(
		'-table'=>'ab',
		'-action'=>'/php/admin.php',
		'_menu'=>'ab',
		'-onsubmit'=>"return ajaxSubmitForm(this,'ab_results');",
		'-style_all'=>'width:100%',
		'-class_all'=>'browser-default',
		'-save'=>'Run Test'
	));
}
?>