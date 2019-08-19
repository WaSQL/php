<?php
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
function templateMetaImage(){
	global $PAGE;
	if(strlen($PAGE['meta_image'])){return "//{$_SERVER['HTTP_HOST']}/{$PAGE['meta_image']}";}
	return '/images/logo.svg';
}
function templateMetaImageType(){
	global $PAGE;
	if(strlen($PAGE['meta_image'])){return 'image/'.getFileExtension($PAGE['meta_image']);}
	return 'image/svg';
}
function templateMetaTitle(){
	global $PAGE;
	if($PAGE['_id']==1 && isset($_REQUEST['passthru'][1]) && $_REQUEST['passthru'][0]=='p'){
		$sku=addslashes($_REQUEST['passthru'][1]);
		$filters=array('sku'=>$sku);
		$products=globalGetProducts($filters);
		if(isset($products[0]['images'][0]['file'])){
			$PAGE['meta_image']=$products[0]['images'][0]['file'];
		}
		if(isset($products[0]['name'])){
			$PAGE['meta_title']=$products[0]['name'];
		}
		if(isset($products[0]['details'])){
			$PAGE['meta_description']=trim(removeHtml($products[0]['details']));
			$PAGE['meta_description']=preg_replace('/[\r\n]+/','. ',$PAGE['meta_description']);
			$PAGE['meta_description']=preg_replace('/[\s\t]+/',' ',$PAGE['meta_description']);
			//trim to 160 chars but do not cut off words
			$PAGE['meta_description']=truncateWords($PAGE['meta_description'],160);
		}
		if(isset($products[0]['category'])){
			$PAGE['meta_keywords']="{$products[0]['name']},{$products[0]['sku']},{$products[0]['category']}";
		}
	}
	if(strlen($PAGE['title'])){return $PAGE['title'];}
	//enter default title below
	return '';
}
function templateMetaSite(){
	global $PAGE;
	if(strlen($PAGE['meta_site'])){return $PAGE['meta_site'];}
	return $_SERVER['HTTP_HOST'];
}
function templateMetaDescription(){
	global $PAGE;
	if(strlen($PAGE['meta_description'])){return $PAGE['meta_description'];}
	//enter default description below
	return '';
}
function templateMetaKeywords(){
	global $PAGE;
	if(strlen($PAGE['meta_keywords'])){return $PAGE['meta_keywords'];}
	//enter default keywords below
	return '';
}
?>
?>
