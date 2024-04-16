<?php
function fontsGetFonts(){
	$path=getWaSQLPath('wfiles/fonts');
	$files=listFilesEx($path,array('ext'=>'woff','-recurse'=>1));
	foreach($files as $i=>$file){
		$files[$i]['name']=str_replace('.woff','',$files[$i]['name']);
		if($files[$i]['name']=='wasql_icons' || $files[$i]['name']=='brands' || $files[$i]['name']=='material'){
			unset($files[$i]);
			continue;
		}
		$files[$i]['example']='<span style="font-family:'.$files[$i]['name'].'">Example Text</span>';
		if(stringEndsWith($files[$i]['path'],'extras')){
			$files[$i]['wpath']='/wfiles/fonts/extras';
		}
		else{
			$files[$i]['wpath']='/wfiles/fonts';
		}
		$files[$i]['code']="loadExtrasFont('{$files[$i]['name']}');";
	}
	return $files;
}
function fontsList(){
	return databaseListRecords(array(
		'-list'=>fontsGetFonts(),
		'-listfields'=>'name,code,example',
		'-tableclass'=>'table striped bordered'
	));
}
?>
