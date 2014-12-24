<?php
loadExtrasCss('bootstrap');
loadDBFunctions('global_functions');
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
	if(strlen($PAGE['title'])){return $PAGE['title'];}
	return 'Providing access to clean healthy water';
}
function templateMetaDescription(){
	global $PAGE;
	if(strlen($PAGE['meta_description'])){return $PAGE['meta_description'];}
	return 'PureStill has one mission - to ensure that everyone has access to clean healthy water.';
}
function templateMetaKeywords(){
	global $PAGE;
	if(strlen($PAGE['meta_keywords'])){return $PAGE['meta_keywords'];}
	return 'water distiller, h2o, clean water, pure water, distilled water, healthy water, safe water';
}


function templateSocialButtons(){
	return buildSocialButtons(array(
		'facebook'	=> "http://www.facebook.com/yourfacebookpage",
		'-size'		=> 'small',
		'-tooltip'=>true
	));
}
?>
