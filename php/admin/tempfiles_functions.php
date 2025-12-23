<?php
//Input validation helper functions
function tempfilesValidateFileName($filename){
	//Prevent path traversal attacks
	if(preg_match('/\.\./',$filename) || preg_match('/[\/\\\\]/',$filename)){
		return false;
	}
	//Only allow alphanumeric, dash, underscore, and dot
	if(!preg_match('/^[a-zA-Z0-9\-\_\.]+$/',$filename)){
		return false;
	}
	return true;
}

function tempfilesValidateExtension($ext){
	//Allow extensions for WaSQL supported languages and common temp file types
	$allowed=array(
		//Common temp/data files
		'log','txt','csv','tsv','gsv','json','xml','sql','md','yaml','yml','ini','conf','config','lock',
		//PHP
		'php',
		//Python
		'py','pyc','pyw',
		//Perl
		'pl','pm',
		//Node.js/JavaScript
		'js','mjs','cjs',
		//Lua
		'lua',
		//Tcl
		'tcl',
		//Ruby
		'rb','rake','gemspec',
		//R
		'r','rdata','rds',
		//Julia
		'jl',
		//Bash
		'sh','bash',
		//PowerShell
		'ps1','psm1','psd1',
		//Groovy
		'groovy','gradle',
		//VBScript
		'vbs','vba',
		//HTML/CSS
		'html','htm','css',
		//Batch
		'bat','cmd'
	);
	return in_array(strtolower($ext),$allowed);
}

function tempfilesShowFileLines($content,$scrollto=0){
	$content=encodeHtml($content);
	$lines=preg_split('/[\r\n]+/',$content);
	$outlines=array();
	foreach($lines as $i=>$line){
		$n=$i+1;
		$outlines[]="<code id=\"line_{$n}\">{$line}</code>";
	}
	$rtn=implode(PHP_EOL,$outlines);
	if((integer)$scrollto > 0){
		$rtn.=<<<ENDOFSCRIPT
<script>
		let el=document.getElementById('line_{$scrollto}');
		if(undefined != el){
			el.scrollIntoView({ behavior: "smooth", block: "end", inline: "nearest" });
			el.style.backgroundColor='#f9f999';
		}
</script>
ENDOFSCRIPT;
	}
	return $rtn;
}
function tempfilesGetTabs(){
	global $path;
	$files=listFiles($path);
	$exts=array();
	foreach($files as $file){
		if(is_dir("{$path}/{$file}")){continue;}
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
			//Validate as integer to prevent SQL injection
			$uid=(integer)$m[1];
			if($uid > 0 && !in_array($uid,$uids)){
				$uids[]=$uid;
			}
		}
	}
	$usermap=[];
	if(count($uids)){
		//UIDs are now guaranteed to be integers
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
	if(preg_match('/^(.+?)\ desc$/i',$sort,$m)){
		$files=sortArrayByKey($files,$m[1],SORT_DESC);
	}
	elseif(preg_match('/^(.+?)\ asc$/i',$sort,$m)){
		$files=sortArrayByKey($files,$m[1],SORT_ASC);
	}
	else{
		$files=sortArrayByKey($files,$sort,SORT_ASC);
	}
	return databaseListRecords(array(
		'-list'=>$files,
		'-hidesearch'=>1,
		'-tableclass'=>'wacss_table striped bordered condensed',
		'-listfields'=>implode(',',$fields),
		'_cdate_age_verbose_displayname'=>'Created',
		'_adate_age_verbose_displayname'=>'Accessed',
		'-th_name_onclick'=>"return tempfilesSortBy('%field% desc');",
		'-th_username_onclick'=>"return tempfilesSortBy('%field% desc');",
		'-th_db_onclick'=>"return tempfilesSortBy('%field% desc');",
		'-th__cdate_age_verbose_onclick'=>"return tempfilesSortBy('%field% desc');",
		'-th__adate_age_verbose_onclick'=>"return tempfilesSortBy('%field% desc');",
		'-th_size_onclick'=>"return tempfilesSortBy('%field% desc');",
		'-th_name_class'=>'w_pointer',
		'-th_username_class'=>'w_pointer',
		'-th_db_class'=>'w_pointer',
		'-th__cdate_age_verbose_class'=>'w_pointer',
		'-th__adate_age_verbose_class'=>'w_pointer',
		'-th_size_class'=>'w_pointer',
		'-results_eval'=>'tempfilesShowListExtra',
		'action_displayname'=>buildFormCheckAll('class','selectfile',array('-label'=>'Actions'))
	));
}
function tempfilesShowListExtra($recs){
	foreach($recs as $i=>$rec){
		$ext=getFileExtension($rec['name']);
		//size to verboseSize
		$recs[$i]['size']=verboseSize($rec['size']);
		//action - select, view, delete
		$recs[$i]['action']='<input type="checkbox" name="file[]" value="'.$rec['name'].'" class="selectfile" />';
		//viewfile
		$recs[$i]['action'].=<<<ENDOFACTION
<a href="#" style="display:inline;margin-left:8px;" data-div="centerpop" data-title="{$rec['name']}" data-nav="/php/admin.php" data-_menu="tempfiles" data-func="view_file" data-file="{$rec['name']}" onclick="return wacss.nav(this);"><span class="icon-file-txt w_info"></span></a>
ENDOFACTION;
		//clear_file
		$recs[$i]['action'].=<<<ENDOFACTION
<a href="#" style="display:inline;margin-left:12px;" data-confirm="Remove {$rec['name']}? Click OK to confirm." data-div="tempfiles_content" data-ext="{$ext}" data-title="{$rec['name']}" data-nav="/php/admin.php" data-_menu="tempfiles" data-func="clear_file" data-file="{$rec['name']}" onclick="return wacss.nav(this);"><span class="icon-erase w_danger"></span></a>
ENDOFACTION;
		if($rec['ext'] != 'log'){
			//matching logfile?
			$logfile=preg_replace('/\.'.$rec['ext'].'$/','.log',$rec['afile']);
			if(file_exists($logfile)){
				$lname=getFileName($logfile);
				$recs[$i]['action'].=<<<ENDOFACTION
<a href="#" title="view log" style="display:inline;margin-left:12px;" data-div="centerpop" data-ext="{$ext}" data-title="Logfile: {$lname}" data-nav="/php/admin.php" data-_menu="tempfiles" data-func="view_file" data-file="{$lname}" onclick="return wacss.nav(this);"><span class="icon-file-txt w_gray"></span></a>
ENDOFACTION;
			}
		}
		//echo printValue($rec);exit;
	}
	return $recs;
}
?>