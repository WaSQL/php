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
function apachebenchCheckSchema(){
	$table='apachebench';
	if(isDBTable($table)){return false;}
	$fields=array(
		'_id'		=> databasePrimaryKeyFieldString(),
		'_cdate'	=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'	=> databaseDataType('int')." NOT NULL",
		'_edate'	=> databaseDataType('datetime')." NULL",
		'_euser'	=> databaseDataType('int')." NULL",
		'requests'	=> databaseDataType('int')." NOT NULL",
		'concurrency'=> databaseDataType('int')." NOT NULL",
		'url'		=> databaseDataType('varchar(255)')." NULL",
		'proxy_auth'=> databaseDataType('varchar(200)')." NULL",
		'basic_auth'=> databaseDataType('varchar(200)')." NULL",
		'proxy'		=> databaseDataType('varchar(255)')." NULL",
		'result'	=> databaseDataType('text')." NULL"
	);
	$ok = createDBTable($table,$fields,'InnoDB');
	if($ok != 1){return false;}
	//indexes
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"url"));
	return true;
}
function apachebenchForm(){
	return addEditDBForm(array(
		'-table'=>'apachebench',
		'-action'=>'/php/admin.php',
		'-fields'=>'requests:concurrency,url,basic_auth,proxy,proxy_auth',
		'requests_displayname'=>'How many total requests?',
		'concurrency_displayname'=>'How many concurrent requests?',
		'url_displayname'=>'URL to hit ([http://]hostname[:port]/path)',
		'basic_auth_displayname'=>'Basic Authentication - optional (username:password)',
		'proxy_displayname'=>'Proxy Server - optional (server:port)',
		'proxy_auth_displayname'=>'Proxy Authentication - optional (username:password)',
		'requests_required'=>1,
		'concurrency_required'=>1,
		'url_required'=>1,
		'_menu'=>'ab',
		'-onsubmit'=>"return ajaxSubmitForm(this,'ab_results');",
		'-style_all'=>'width:100%',
		'-class_all'=>'browser-default',
		'-save'=>'Run Test'
	));
}
?>