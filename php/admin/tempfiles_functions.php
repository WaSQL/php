<?php
function tempfilesShowFileLines($content){
	$content=encodeHtml($content);
	$lines=preg_split('/[\r\n]+/',$content);
	$outlines=array();
	foreach($lines as $i=>$line){
		$outlines[]="<code>{$line}</code>";
	}
	return implode(PHP_EOL,$outlines);
}
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
	//add in username based on filename
	$uids=[];
	foreach($files as $i=>$file){
		if(preg_match('/\_u([0-9]+?)\_/',$file['name'],$m)){
			if(!in_array($m[1],$uids)){$uids[]=$m[1];}
		}
	}
	$usermap=[];
	if(count($uids)){
		$uidstr=implode(',',$uids);
		$usermap=getDBRecords(array(
			'-table'=>'_users',
			'-fields'=>'_id,username',
			'-index'=>'_id',
			'-where'=>"_id in ({$uidstr})"
		));
	}
	$fields=array('action','name','ext');
	foreach($files as $i=>$file){
		if(preg_match('/\_u([0-9]+?)\_/',$file['name'],$m)){
			$files[$i]['username']=$usermap[$m[1]]['username'];
			if(!in_array('username',$fields)){$fields[]='username';}
		}
		if(preg_match('/sqlprompt\_(.+?)\_u/',$file['name'],$m)){
			$files[$i]['db']=$m[1];
			if(!in_array('db',$fields)){$fields[]='db';}
		}
	}
	$fields[]='_cdate_age_verbose';
	$fields[]='_adate_age_verbose';
	$fields[]='size';
	$files=sortArrayByKey($files,$sort,SORT_ASC);
	return databaseListRecords(array(
		'-list'=>$files,
		'-hidesearch'=>1,
		'-tableclass'=>'table striped bordered condensed',
		'-listfields'=>implode(',',$fields),
		'_cdate_age_verbose_displayname'=>'Created',
		'_adate_age_verbose_displayname'=>'Accessed',
		'-th_onclick'=>"return tempfilesSortBy('%field%');",
		'-th_class'=>'w_pointer',
		'-results_eval'=>'tempfilesShowListExtra',
		'action_displayname'=>buildFormCheckAll('class','selectfile',array('-label'=>'Actions'))
	));
}
function tempfilesShowListExtra($recs){
	foreach($recs as $i=>$rec){
		//size to verboseSize
		$recs[$i]['size']=verboseSize($rec['size']);
		//action - select, view, delete
		$recs[$i]['action']='<input type="checkbox" name="file[]" value="'.$rec['name'].'" class="input selectfile" />';
		$recs[$i]['action'].='<a href="#" style="display:inline;margin-left:8px;" data-name="'.$rec['name'].'" onclick="return tempfilesViewFile(this);" data-name="'.$rec['name'].'"><span class="icon-file-txt w_info"></span></a>';
		$recs[$i]['action'].='<a href="#" style="display:inline;margin-left:12px;" data-name="'.$rec['name'].'" onclick="return tempfilesClearFile(this)" data-name="'.$rec['name'].'"><span class="icon-erase w_danger"></span></a>';
		if($rec['ext'] != 'log'){
			//matching logfile?
			$logfile=preg_replace('/\.'.$rec['ext'].'$/','.log',$rec['afile']);
			if(file_exists($logfile)){
				$lname=getFileName($logfile);
				$recs[$i]['action'].='<a href="#" title="view log" style="display:inline;margin-left:12px;" data-name="'.$lname.'" onclick="return tempfilesViewFile(this)" data-name="'.$lname.'"><span class="icon-file-txt w_gray"></span></a>';
			}
		}
		//echo printValue($rec);exit;
	}
	return $recs;
}
?>