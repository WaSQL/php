<?php
loadExtrasCss('bootstrap');
global $PAGE;

function templateActiveMenu($name){
	global $PAGE;
	if($PAGE['name']==$name){return ' active';}
	return '';
}

function templateMetaEtag(){
	global $PAGE;
	global $TEMPLATE;
	//get latest edit date and latest create date and sha1 encode them
	return sha1($PAGE['_cdate'].$PAGE['_edate'].$TEMPLATE['_cdate'].$TEMPLATE['_edate']);
}
function templateMetaTitle(){
	global $PAGE;
	if(isset($PAGE['title']) && strlen($PAGE['title'])){return $PAGE['title'];}
	return '';
}
function templateMetaDescription(){
	global $PAGE;
	if(isset($PAGE['meta_description']) && strlen($PAGE['meta_description'])){return $PAGE['meta_description'];}
	return '';
}
function templateMetaKeywords(){
	global $PAGE;
	if(isset($PAGE['meta_keywords']) && strlen($PAGE['meta_keywords'])){return $PAGE['meta_keywords'];}
	return '';
}


function templateSocialButtons(){
	return buildSocialButtons(array(
		'facebook'	=> "http://www.facebook.com/yourfacebookpage",
		'-size'		=> 'small',
		'-tooltip'=>true
	));
}
?>
