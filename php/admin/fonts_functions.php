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
		$files[$i]['example']='<span style="font-family:'.$files[$i]['name'].'">'.loremIpsum(300).'</span>';
		if(stringEndsWith($files[$i]['path'],'extras')){
			$files[$i]['wpath']='/wfiles/fonts/extras';
		}
		else{
			$files[$i]['wpath']='/wfiles/fonts';
		}
		$files[$i]['code']=<<<ENDOFCODE
<div style="display:flex;justify-content:space-between;">
	<div id="fontcode_{$i}">loadExtrasFont('{$files[$i]['name']}');</div>
	<div class="w_pointer w_small w_gray" onclick="wacss.copy2Clipboard(document.querySelector('#fontcode_{$i}').innerText);"><span class="icon-copy"></span></div>
</div>
ENDOFCODE;
	}
	return $files;
}
function fontsList(){
	return databaseListRecords(array(
		'-list'=>fontsGetFonts(),
		'-listfields'=>'name,code,example',
		'-tableclass'=>'table striped bordered',
		'name_options'=>array(
			'displayname'=>'Font Name',
			'class'=>'w_nowrap'
		),
		'code_options'=>array(
			'displayname'=>'Controller Code',
			'class'=>'w_nowrap'
		)
	));
}
?>
