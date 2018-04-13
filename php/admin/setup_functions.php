<?php
function setupTypeChecked($v){
	if(isset($_REQUEST['starttype']) && $_REQUEST['starttype']==$v){return ' checked';}
	return '';
}
function setupGetTemplates(){
	$progpath=dirname(__FILE__);
	//echo $progpath;exit;
	$tpath=realpath("{$progpath}/../schema/templates");
	$recs=listFilesEx($tpath,array('ext'=>'zip'));
	foreach($recs as $i=>$rec){
		$recs[$i]['nameonly']=getFileName($rec['name'],1);
	}
	return $recs;
}
function setupGetDescription($zipfile){
	loadExtras('zipfile');
	$zipfile=realpath($zipfile);
	$name=getFileName($zipfile,1);
	return zipGetFileContents($zipfile,"{$name}/description.txt");
}
?>
