<?php
function tempfilesGetTabs(){
	global $path;
	$files=listFiles($path);
	$exts=array();
	foreach($files as $file){
		$ext=getFileExtension($file);
		$exts[$ext]+=1;
	}
	ksort($exts);
	$tabs=array();
	foreach($exts as $ext=>$cnt){
		$rec=array(
			'name'=>$ext,
			'count'=>$cnt
		);
		if(count($tabs)==0){
			$rec['class']='active';
		}
		$tabs[]=$rec;
	}
	return $tabs;
}
function tempfilesShowList($ext,$sort){
	global $path;
	$files=listFilesEx($path,array('ext'=>$ext));
	$files=sortArrayByKey($files,$sort,SORT_ASC);
	return databaseListRecords(array(
		'-list'=>$files,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped bordered condensed',
		'-listfields'=>'action,name,ext,_cdate_age_verbose,_adate_age_verbose,size_verbose',
		'_cdate_age_verbose_displayname'=>'Created',
		'_adate_age_verbose_displayname'=>'Accessed',
		'-results_eval'=>'tempfilesShowListExtra',
		'action_displayname'=>buildFormCheckAll('class','selectfile',array('-label'=>'Actions'))
	));
}
function tempfilesShowListExtra($recs){
	//echo printValue($recs);exit;
	foreach($recs as $i=>$rec){
		//action - select, view, delete
		$recs[$i]['action']='<input type="checkbox" name="file[]" value="'.$rec['name'].'" class="input selectfile" />';
		$recs[$i]['action'].='<a href="#" style="display:inline;margin-left:8px;" data-name="'.$rec['name'].'" onclick="return tempfilesViewFile(this);" data-name="'.$rec['name'].'"><span class="icon-eye w_info"></span></a>';
		$recs[$i]['action'].='<a href="#" style="display:inline;margin-left:12px;" data-name="'.$rec['name'].'" onclick="return tempfilesClearFile(this)" data-name="'.$rec['name'].'"><span class="icon-erase w_danger"></span></a>';
	}
	return $recs;
}
?>