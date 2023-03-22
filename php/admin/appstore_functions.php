<?php
function appstoreApps(){
	$opts=array(
		'-table'=>'_pages',
		'-tableclass'=>'table striped bordered sticky collapsed',
		'-where'=>"ifnull(_appkey,'') != ''",
		'-fields'=>'_id,_appkey,name,_cdate,_edate,title,description',
		'-listfields'=>'_id,name,title,description,actions',
		'-simplesearch'=>1,
		'-results_eval'=>'appstoreAppsExtra',
		'-tr_data-nav'=>'/php/admin.php',
		'-tr_data-_menu'=>'appstore',
		'-tr_data-appkey'=>"%_appkey%"
	);
	return databaseListRecords($opts);
}
function appstoreAppsExtra($recs){
	foreach($recs as &$rec){
		//name
		$rec['name']='<a href="/'.$rec['name'].'/postinstall" target="_blank" class="w_link">'.$rec['name'].'</a>';
		//actions
		$actions=array('<div style="display:flex;justify-content:flex-end;">');
		//action - update
		$actions[]='<span class="icon-refresh w_pointer" data-confirm="Update this app?" data-_func="update" data-div="actions_'.$rec['_id'].'" onclick="wacss.nav(this);"></span>';
		//action - uninstall
		$actions[]='<span class="icon-erase w_pointer w_danger" style="margin-left:10px;" data-id="'.$rec['_id'].'" data-confirm="UNINSTALL this app? ARE YOU SURE?" data-_func="uninstall" data-div="appstore_content" onclick="wacss.nav(this);"></span>';
		$actions[]='</div>';
		$actions[]='<div id="actions_'.$rec['_id'].'"></div>';
		$rec['actions']=implode(PHP_EOL,$actions);
	}
	return $recs;
}
function appstoreInit(){
	$finfo=getDBFieldInfo('_pages');
	if(!isset($finfo['_appkey'])){
		$query="alter table _pages add _appkey varchar(100) NULL";
		$ok=executeSQL($query);
	}
	if(!isset($finfo['meta'])){
		$query="alter table _pages add meta json NULL";
		$ok=executeSQL($query);
	}
	if(!isset($finfo['settings'])){
		$query="alter table _pages add settings json NULL";
		$ok=executeSQL($query);
	}
	return true;
}
function appstoreInstall($appkey){
	$url='https://appstore.wasql.com/api/install/';
	$post=postURL($url,array(
		'-method'=>'POST',
		'-headers'=>array("WaSQL-appkey: ".base64_encode($appkey)),
		'-json'=>1
	));
	//echo printValue($post);exit;
	if(!isset($post['json_array']['status'])){
		echo printValue($post);exit;
	}
	if($post['json_array']['status'] != 'success'){
		echo printValue($post['json_array']);exit;
	}
	$rec=array();
	$fields=['name','body','controller','title','description','functions','js','css','meta','settings'];
	foreach($fields as $field){
		$rec[$field]=base64_decode(base64_decode($post['json_array']['page'][$field]));
	}
	$rec['-table']='_pages';
	$rec['-upsert']='body,controller,title,description,functions,js,css,_appkey,_template,meta,settings';
	$rec['_appkey']=$appkey;
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
	$url='https://appstore.wasql.com/api/install/';
	$post=postURL($url,array(
		'-method'=>'POST',
		'-headers'=>array("WaSQL-appkey: ".base64_encode($appkey)),
		'-json'=>1
	));
	if(!isset($post['json_array']['status'])){
		echo printValue($post);exit;
	}
	if($post['json_array']['status'] != 'success'){
		echo printValue($post['json_array']);exit;
	}
	$rec=array();
	$fields=['name','body','controller','title','description','functions','js','css','meta','settings'];
	foreach($fields as $field){
		$rec[$field]=base64_decode(base64_decode($post['json_array']['page'][$field]));
	}
	$rec['-table']='_pages';
	$rec['-upsert']='body,controller,title,description,functions,js,css';
	$rec['_appkey']=$appkey;
	$rec['active']=1;
	$rec['_template']=2;
	$id=addDBRecord($rec);
	if(isNum($id)){
		echo "updated";exit;
	}
	else{
		echo "hmm".printValue($id);exit;
	}
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