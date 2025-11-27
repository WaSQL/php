<?php
function appstoreApps(){
	$opts=array(
		'-table'=>'_pages',
		'-tableclass'=>'wacss_table striped bordered sticky collapsed',
		'-where'=>"coalesce(_app->>'\$.key','') != ''",
		'-fields'=>'_id,_app,name,_cdate,_edate,title,description',
		'-listfields'=>'_id,name,actions',
		'name_displayname'=>'Name/Description',
		'-simplesearch'=>1,
		'-results_eval'=>'appstoreAppsExtra',
		'-tr_data-nav'=>'/php/admin.php',
		'-tr_data-_menu'=>'appstore',
		'-tr_data-appkey'=>"%appkey%"
	);
	return databaseListRecords($opts);
}
function appstoreAppsExtra($recs){
	foreach($recs as &$rec){
		$app=json_decode($rec['_app'],true);
		$rec['appkey']=$app['key'];
		//name
		$name=$rec['name'];
		$rec['name']=<<<ENDOFNAME
		<div class="w_big w_bold">{$name} - {$rec['title']}</div>
		<div class="w_small w_gray" style="margin-left:10px;">: {$rec['description']}</div>
ENDOFNAME;
		//actions
		$actions=array('<div style="display:flex;flex-wrap:wrap;justify-content:flex-end;">');
		//action - setup
		$actions[]='<a href="/'.$name.'/postinstall" title="Run once after install" target="_blank" class="wacss_button is-mobile-responsive" style="align-self:center;margin-left:10px;margin-bottom:5px;"><span class="icon-circle w_green"></span> Post Install Setup</a>';
		//action - settings
		$actions[]='<a href="/'.$name.'/settings" target="_blank" class="wacss_button is-mobile-responsive" style="align-self:center;margin-left:10px;margin-bottom:5px;"><span class="icon-gear w_blue"></span> App Settings</a>';
		//action - settings
		$actions[]='<a href="/'.$name.'/documentation" target="_blank" class="wacss_button is-mobile-responsive" style="align-self:center;margin-left:10px;margin-bottom:5px;"><span class="icon-help-circled w_teal"></span> App Documentation</a>';
		//action - update
		$actions[]='<button class="wacss_button is-mobile-responsive" style="margin-bottom:5px;" data-confirm="Update '.$name.'?" data-_func="update" data-div="actions_'.$rec['_id'].'" onclick="wacss.nav(this);"><span class="icon-refresh"></span> Update App</button>';
		//action - show appkey
		$actions[]='<button class="wacss_button is-mobile-responsive" style="margin-bottom:5px;" data-appkey="'.$rec['appkey'].'" data-_func="update" data-div="actions_'.$rec['_id'].'" onclick="wacss.copy2Clipboard(this.dataset.appkey);return false;"><span class="icon-lock w_gold"></span> Copy Appkey</button>';
		//action - uninstall
		$actions[]='<button class="wacss_button is-mobile-responsive w_danger" style="margin-left:10px;" data-id="'.$rec['_id'].'" data-confirm="UNINSTALL '.$name.'? [newline][newline]This will remove ALL '.$name.' related files.[newline][newline]ARE YOU SURE? Click OK to confirm." data-_func="uninstall" data-div="appstore_content" onclick="wacss.nav(this);"><span class="icon-erase w_danger"></span> Uninstall App</button>';
		$actions[]='</div>';
		$actions[]='<div id="actions_'.$rec['_id'].'"></div>';
		$rec['actions']=implode(PHP_EOL,$actions);
	}
	return $recs;
}
function appstoreInit(){
	$finfo=getDBFieldInfo('_pages');
	if(!isset($finfo['_app'])){
		$query="ALTER TABLE _pages ADD _app JSON NULL";
		$ok=executeSQL($query);
	}
	if(!isset($finfo['meta'])){
		$query="ALTER TABLE _pages ADD meta JSON NULL";
		$ok=executeSQL($query);
	}
	if(!isset($finfo['settings'])){
		$query="ALTER TABLE _pages ADD settings JSON NULL";
		$ok=executeSQL($query);
	}
	return true;
}
function appstoreInstall($appkey,$update=0){
	$appkey=str_replace(' ','+',$appkey);
	$url='https://appstore.wasql.com/api/install/';
	if($update==1){
		$opts=array('-table'=>'_pages','-fields'=>'_id,_app,name','-where'=>"_app->>'\$.key'='{$appkey}'");
		$app=getDBRecord($opts);
		if(isset($app['_app'])){
			$app=json_decode($app['_app'],true);
		}
		else{
			$app=array('key'=>$appkey,'code'=>generatePassword(8,4));
		}
	}	
	else{
		$app=array('key'=>$appkey,'code'=>generatePassword(8,4));
	}
	$post=postURL($url,array(
		'-method'=>'POST',
		'-headers'=>array(
			"WaSQL-appkey: ".base64_encode($app['key']),
			"WaSQL-appcode: ".base64_encode($app['code'])
		),
		'-json'=>1
	));
	//echo printValue($post);exit;
	if(!isset($post['json_array']['status'])){
		echo printValue($post);exit;
	}
	if($post['json_array']['status'] != 'success'){
		echo printValue($post['json_array']);exit;
	}
	//remove files
	$wpath=getWaSQLPath();
	$lpath="{$wpath}/wfiles/appdata/".strtolower($post['json_array']['name']);
	$files=listFilesEx($lpath);
	foreach($files as $file){
		unlink($file['afile']);
	}
	//add files
	if(isset($post['json_array']['files'][0])){
		foreach($post['json_array']['files'] as $url){
			$name=getFileName($url);
			$localfile="{$lpath}/{$name}";
			if(file_exists($localfile)){
				unlink($localfile);
			}
			list($localfile,$path)=wget($url,$localfile);
		}
	}
	//page
	$rec=array();
	$fields=['name','body','controller','title','description','functions','js','css','meta','settings'];
	foreach($fields as $field){
		$rec[$field]=base64_decode(base64_decode($post['json_array']['page'][$field]));
	}
	$rec['-table']='_pages';
	//does this app already exist?
	$arec=getDBRecord(array('-table'=>'_pages','name'=>$rec['name']));
	if($update != 1 && isset($arec['_id']) && isNum($arec['_id'])){
		$ok=editDBRecordById('_pages',$arec['_id'],array('active'=>1,'_app'=>json_encode($app)));
		return true;
	}
	elseif($update==1){
		$rec['-upsert']='body,controller,description,functions,js,css,meta';
	}
	else{
		$rec['-upsert']='body,controller,title,description,functions,js,css,_app,_template,meta,settings';
	}
	$rec['_app']=json_encode($app);
	$rec['active']=1;
	$rec['_template']=2;
	$id=addDBRecord($rec);
	if(isNum($id)){
		return true;
	}
	else{
		echo printValue($id);exit;
		return false;
	}
}
function appstoreUpdate($appkey){
	return appstoreInstall($appkey,1);
}
function appstoreUninstall($appkey){
	$appkey=str_replace(' ','+',$appkey);
	$opts=array(
		'-table'=>'_pages',
		'-where'=>"_app->>'\$.key'='{$appkey}'"
	);
	$rec=getDBRecord($opts);
	if(isset($rec['name'])){
		//remove files
		$wpath=getWaSQLPath();
		$lpath="{$wpath}/wfiles/appdata/".strtolower($rec['name']);
		$files=listFilesEx($lpath);
		foreach($files as $file){
			unlink($file['afile']);
		}
		rmdir($lpath);
		//remove tables
		$tables=getDBTables();
		foreach($tables as $table){
			if(strtolower($rec['name']) == strtolower($table) || stringBeginsWith($table,"{$rec['name']}_")){
				$ok=dropDBTable($table,1);
			}
		}
		$ok=delDBRecord($opts);
		//remove tabledata,fielddata, and triggers, and _crons
		$queries=array(
			"delete from _tabledata where tablename like '{$rec['name']}_%'",
			"delete from _fielddata where tablename like '{$rec['name']}_%'",
			"delete from _queries where tablename like '{$rec['name']}_%'",
			"delete from _queries where function_name='sql_prompt' and query like '%from {$rec['name']}_%'",
			"delete from _triggers where name like '{$rec['name']}_%'",
			"delete from _cron where groupname = '{$rec['name']}'",
		);
		foreach($queries as $query){
			$ok=executeSQL($query);
		}
		$ok=appstoreSetStatus($appkey,'uninstalled');
	}
	else{
		echo printValue($opts).printValue($rec);
		return false;
	}
	return true;
}
function appstoreSetStatus($appkey,$status){
	$url='https://appstore.wasql.com/api/setstatus/'.$status;
	$post=postURL($url,array(
		'-method'=>'POST',
		'-headers'=>array("WaSQL-appkey: ".base64_encode($appkey)),
		'-json'=>1
	));
	return true;
}
?>