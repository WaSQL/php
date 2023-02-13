<?php
function manualGetCategories(){
	$query=<<<ENDOFQUERY
	SELECT
		category,
		COUNT(*) as cnt
	FROM _docs
	GROUP BY category
	ORDER BY category
ENDOFQUERY;
	$recs=getDBRecords($query);
	foreach($recs as $i=>$rec){
		switch(strtolower($rec['category'])){
			case 'template php':
			case 'template python':
			case 'template perl':
				$recs[$i]['icon']='icon-file-docs';
			break;
			case 'page php':
			case 'page python':
			case 'page perl':
				$recs[$i]['icon']='icon-file-doc';
			break;
			default:
				$recs[$i]['icon']='brand-'.strtolower($rec['category']);
			break;
		}
	}
	return $recs;
}
function manualGetFileNames($category){
	$query=<<<ENDOFQUERY
	SELECT
		COUNT(*) as cnt,
		afile
	FROM _docs
	WHERE category='{$category}'
	GROUP BY afile
ENDOFQUERY;
	$recs=getDBRecords($query);
	foreach($recs as $i=>$rec){
		$recs[$i]['afile_decoded']=base64_decode($rec['afile']);
		$recs[$i]['file_path']=getFilePath($recs[$i]['afile_decoded']);
		$recs[$i]['file_name']=getFileName($recs[$i]['afile_decoded']);
	}
	$recs=sortArrayByKeys($recs,array('afile_path'=>SORT_ASC,'afile_name'=>SORT_ASC));
	return $recs;
}
function manualGetNames($afile){
	$query=<<<ENDOFQUERY
	SELECT
		_id,
		name,
		afile_line,
		info,
		length(info) as info_length
	FROM _docs
	where afile='{$afile}'
	order by name
ENDOFQUERY;
	$recs=getDBRecords($query);
	foreach($recs as $i=>$rec){
			
		}
	foreach($recs as $i=>$rec){
		if($rec['info_length'] < 10 ){
			$recs[$i]['class']='w_red';
		}
		else{
			$rec['info_ex']=json_decode($rec['info'],true);
			if(!isset($rec['info_ex']['usage'])){
				$recs[$i]['class']='w_red';
			}
			else{
				$recs[$i]['class']='';
			}
		}
	}
	//echo printValue($recs);exit;
	return $recs;
}
function manualParseFile($file){
	global $docs_files;
	if(!is_array($docs_files)){
		$docs_files=getDBRecords(array(
			'-table'=>'_docs_files',
			'-index'=>'afile'
		));
	}
	
	global $CONFIG;
	$recs=array();
	$lines=array();
	if(is_file($file)){
		$file=strtolower(realpath($file));
		$md5=md5_file($file);
		if(isset($docs_files[$file]['afile_md5']) && $docs_files[$file]['afile_md5']==$md5){
			return;
		}
		$lines=file($file);
		$ext=getFileExtension($file);
	}
	else{
		//table:record_id:fieldname:name
		list($table,$id,$field,$name)=preg_split('/\:/',$file,4);
		$rec=getDBRecord(array(
			'-table'=>$table,
			'_id'=>$id,
			'-fields'=>"_id,{$field}"
		));
		//echo $file.printValue($rec);exit;
		$lines=preg_split('/[\r\n]+/',$rec[$field]);
		$md5=md5($rec[$field]);
		if(stringContains($rec[$field],'<?php')){
			$ext="{$table}_php";
		}
		elseif(stringContains($rec[$field],'<?py')){
			$ext="{$table}_py";
		}
		elseif(stringContains($rec[$field],'<?pl')){
			$ext="{$table}_pl";
		}
	}
	$ok=addDBRecord(array(
		'-table'=>'_docs_files',
		'afile'=>$file,
		'afile_md5'=>$md5,
		'-upsert'=>'afile_md5'
	));
	if(is_array($lines)){
		foreach($lines as $i=>$line){
			$lines[$i]=preg_replace('/\t/','[tab]',$line);
		}
		$cnt=count($lines);
	}
	else{
		$cnt=0;
	}
	//echo $file.printValue($lines);exit;
	$lang=array();
	switch(strtolower($ext)){
		case 'py':
			$lang['function_begin']='/^def\ (.+?)\((.*?)\)\ *\:/';
			$lang['function_end']='/^[\t\s]+$/';
			$lang['comment']='/^[\#]/';
			$lang['comment_more']='/^[\#]+(.*)$/';
			$lang['category']='Python';
			$lang['caller_end']='...';
		break;
		case '_pages_py':
			$lang['function_begin']='/^def\ (.+?)\((.*?)\)\ *\:/';
			$lang['function_end']='/^[\t\s]+$/';
			$lang['comment']='/^[\#]/';
			$lang['comment_more']='/^[\#]+(.*)$/';
			$lang['category']='Page Python';
			$lang['caller_end']='...';
		break;
		case '_templates_py':
			$lang['function_begin']='/^def\ (.+?)\((.*?)\)\ *\:/';
			$lang['function_end']='/^[\t\s]+$/';
			$lang['comment']='/^[\#]/';
			$lang['comment_more']='/^[\#]+(.*)$/';
			$lang['category']='Template Python';
			$lang['caller_end']='...';
		break;
		case 'js':
			//function abc(){
			$lang['function_begin']='/^function\ (.+?)\((.*?)\)\ *\{/';
			$lang['function_end']='/^\}/';
			//abc: function(el,ev){
			$lang['function_begin_2']='/^(.+?)\: function\((.*?)\)\ *\{/';
			$lang['function_end_2']='/^\}/';
			$lang['comment']='/^[\/\*]/';
			$lang['comment_more']='/^[\/\*]+(.*)$/';
			$lang['category']='Javascript';
			$lang['caller_end']='...}';
		break;
		case 'php':
			$lang['function_begin']='/^function\ (.+?)\((.*?)\)\ *\{/';
			$lang['function_end']='/^\}/';
			$lang['comment']='/^[\/\*]/';
			$lang['comment_more']='/^[\/\*]+(.*)$/';
			$lang['category']='PHP';
			$lang['caller_end']='...}';
		break;
		case '_pages_php':
			$lang['function_begin']='/^function\ (.+?)\((.*?)\)\ *\{/';
			$lang['function_end']='/^\}/';
			$lang['comment']='/^[\/\*]/';
			$lang['comment_more']='/^[\/\*]+(.*)$/';
			$lang['category']='Page PHP';
			$lang['caller_end']='...}';
		break;
		case '_templates_php':
			$lang['function_begin']='/^function\ (.+?)\((.*?)\)\ *\{/';
			$lang['function_end']='/^\}/';
			$lang['comment']='/^[\/\*]/';
			$lang['comment_more']='/^[\/\*]+(.*)$/';
			$lang['category']='Template PHP';
			$lang['caller_end']='...}';
		break;
		case 'pl':
			$lang['function_begin']='/^sub\ (.+?)\ *\{/';
			$lang['function_end']='/^\}/';
			$lang['comment']='/^[\#]/';
			$lang['comment_more']='/^[\#]+(.*)$/';
			$lang['category']='Perl';
			$lang['caller_end']='...}';
		break;
		case '_pages_pl':
			$lang['function_begin']='/^sub\ (.+?)\ *\{/';
			$lang['function_end']='/^\}/';
			$lang['comment']='/^[\#]/';
			$lang['comment_more']='/^[\#]+(.*)$/';
			$lang['category']='Page Perl';
			$lang['caller_end']='...}';
		break;
		case '_templates_pl':
			$lang['function_begin']='/^sub\ (.+?)\ *\{/';
			$lang['function_end']='/^\}/';
			$lang['comment']='/^[\#]/';
			$lang['comment_more']='/^[\#]+(.*)$/';
			$lang['category']='Template Perl';
			$lang['caller_end']='...}';
		break;
		case 'lua':
			$lang['function_begin']='/^function\ (.+?)\((.*?)\)/';
			$lang['function_end']='/^end/';
			$lang['comment']='/^\-\-/';
			$lang['comment_more']='/^\-\-(.*)$/';
			$lang['category']='Lua';
			$lang['caller_end']='...';
		break;
		default:
			$lang['function_begin']='/^function\ (.+?)\((.*?)\)\ *\{/';
			$lang['function_end']='/^\}/';
			$lang['comment']='/^[\#\/\*]/';
			$lang['comment_more']='/^[\#\/\*]+(.*)$/';
			$lang['category']='Other';
			$lang['caller_end']='...}';
		break;
	}
	for($x=0;$x<$cnt;$x++){
		$line=trim($lines[$x]);
		//echo $ext.'<br>'.$re;exit;
		if(preg_match($lang['function_begin'],$line,$m)){
			if(stringBeginsWith($m[1],'_')){continue;}
			if(stringBeginsWith($m[1],'$')){continue;}
			if(strlen($m[1])==1){continue;}
			$rec=array(
				'afile'=>base64_encode($file),
				'afile_line'=>$x+1,
				'name'=>$m[1],
				'category'=>$lang['category'],
				'caller'=>$m[0].$lang['caller_end']
			);
			
			//backup to read phpdoc comments before the function name
			$p=$x-1;
			$comments=array();
			while(1){
				$pline=trim($lines[$p]);
				$xline=str_replace('[tab]','',$pline);
				$xline=trim($xline);
				if(!strlen($pline) || !strlen($xline)){break;}
				if(!preg_match($lang['comment'],$xline)){break;}
				if(preg_match($lang['function_end'],$pline)){break;}
				$pline=encodeHtml($pline);
				$pline=str_replace('[tab]','&nbsp;&nbsp;&nbsp;&nbsp;',$pline);
				$comments[]=$pline;
				if($p==0){break;}
				$p--;
			}
			$rec['comments']=array_reverse($comments);
			$key='';
			foreach($rec['comments'] as $cline){
				$cline=trim($cline);
				$cline=str_replace('[tab]','&nbsp;&nbsp;&nbsp;&nbsp;',$cline);
				if(preg_match('/\@([a-z]+)(.*)$/i',$cline,$c)){
					$key=$c[1];
					$v=trim($c[2]);
					if(strlen($v)){
						if($key=='name' || $key=='caller'){$rec[$key]=$v;}
						else{
							$rec['info'][$key][]=base64_encode($v);
						}
					}
				}
				elseif(strlen($key) && preg_match($lang['comment_more'],$cline,$c)){
					$v=trim($c[1]);
					if(strlen($v)){
						$rec['info'][$key][]=base64_encode($v);
					}
				}
			}
			if(!isset($rec['info']['exclude'])){
				$rec['comments']=implode(PHP_EOL,$rec['comments']);
				$rec['comments']=base64_encode($rec['comments']);
				$rec['info']=json_encode($rec['info'],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
				if(!strlen($rec['info'])){
					$rec['info']='{}';
				}
				$rec['hash']=md5($rec['name'].$rec['afile']);
				$rec['name']=str_replace('[tab]','&nbsp;&nbsp;&nbsp;&nbsp;',$rec['name']);
				$rec['caller']=str_replace('[tab]','&nbsp;&nbsp;&nbsp;&nbsp;',$rec['caller']);
				$recs[]=$rec;
			}

		}
		elseif(isset($lang['function_begin_2']) && preg_match($lang['function_begin_2'],$line,$m)){
			if(stringBeginsWith($m[1],'_')){continue;}
			if(stringBeginsWith($m[1],'$')){continue;}
			if(strlen($m[1])==1){continue;}
			$rec=array(
				'afile'=>base64_encode($file),
				'afile_line'=>$x+1,
				'name'=>$m[1],
				'category'=>$lang['category'],
				'caller'=>$m[0].$lang['caller_end']
			);
			
			//backup to read phpdoc comments before the function name
			$p=$x-1;
			$comments=array();
			while(1){
				$pline=trim($lines[$p]);
				$xline=str_replace('[tab]','',$pline);
				$xline=trim($xline);
				if(!strlen($pline) || !strlen($xline)){break;}
				if(!preg_match($lang['comment'],$xline)){break;}
				if(preg_match($lang['function_end_2'],$pline)){break;}
				$pline=encodeHtml($pline);
				$pline=str_replace('[tab]','&nbsp;&nbsp;&nbsp;&nbsp;',$pline);
				$comments[]=$pline;
				if($p==0){break;}
				$p--;
			}
			$rec['comments']=array_reverse($comments);
			$key='';
			foreach($rec['comments'] as $cline){
				$cline=trim($cline);
				$cline=str_replace('[tab]','&nbsp;&nbsp;&nbsp;&nbsp;',$cline);
				if(preg_match('/\@([a-z]+)(.*)$/i',$cline,$c)){
					$key=$c[1];
					$v=trim($c[2]);
					if(strlen($v)){
						if($key=='name' || $key=='caller'){$rec[$key]=$v;}
						else{
							$rec['info'][$key][]=base64_encode($v);
						}
					}
				}
				elseif(strlen($key) && preg_match($lang['comment_more'],$cline,$c)){
					$v=trim($c[1]);
					if(strlen($v)){
						$rec['info'][$key][]=base64_encode($v);
					}
				}
			}
			if(!isset($rec['info']['exclude'])){
				$rec['comments']=implode(PHP_EOL,$rec['comments']);
				$rec['comments']=base64_encode($rec['comments']);
				$rec['info']=json_encode($rec['info'],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
				if(!strlen($rec['info'])){
					$rec['info']='{}';
				}
				$rec['hash']=md5($rec['name'].$rec['afile']);
				$rec['name']=str_replace('[tab]','&nbsp;&nbsp;&nbsp;&nbsp;',$rec['name']);
				$rec['caller']=str_replace('[tab]','&nbsp;&nbsp;&nbsp;&nbsp;',$rec['caller']);
				$recs[]=$rec;
			}
		}
	}
	if(!count($recs)){return 0;}
	$ok=dbAddRecords($CONFIG['database'],'_docs',array('-recs'=>$recs,'-upsert'=>'afile_line,caller,category,comments,info'));
	//echo printValue($ok).printValue($recs);exit;
	return count($recs);
}


//---------- begin function wasqlParseHelp
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function manualParseHelp($file,$location=''){
	global $Manual;
	if(!strlen($location)){
		if(!is_file($file)){return;}
		$lines=file($file);
		$location="File: {$file}";
		$ext=strtolower(getFileExtension($file));
		$filename=ucfirst(getFileName($file,1));
		$path=getFilePath($file);
		$pathname=strtolower(getFileName($path));
		switch($pathname){
        	case 'extras':
				$treenode='Extras Functions';
			break;
        	case 'js':$treenode='Javascript Functions';break;
        	default:$treenode='Main Functions';break;
		}
	}
	else{
    	$lines=preg_split('/[\r\n]/',trim($file));
		$filename="_pages & _templates";
		$treenode='User Defined Functions';
		$path='';
		$ext='php';
	}
	if(!count($lines)){return;}
	$function_cnt=0;
	$cnt=count($lines);
	$reservedTypes=array('helplines'=>1,'loaded'=>1,'name'=>1,'location'=>1,'line'=>1,'file'=>1,'ext'=>1,'index'=>1,'body'=>1);
	for($x=0;$x<$cnt;$x++){
    	$line=trim($lines[$x]);
    	if(preg_match('/^function (.+?)\((.*?)\)/i',$line,$m)){
			$function_cnt++;
			$function_params=$m[2];
			$current=array(
				'name'		=> $m[1],
				'location'	=> "{$location}, <b>Line: {$x}</b>",
				'line'		=> $x,
				'file'		=> $filename,
				'ext'		=> $ext
			);
			switch($ext){
				case 'php':$current['lang']='PHP';break;
				case 'pl':$current['lang']='Perl';break;
				case 'js':$current['lang']='javascript';break;
			}
			//look for inline javascript in the php files
			if($current['lang']=='PHP' && strlen($function_params) && preg_match('/^[a-z]/i',$function_params)){
				$current['lang']='javascript';
			}
			//look for comments before the function name
			$n=$x-1;
			$helplines=array();
			$rawhelplines=array();
			while($n>0 && preg_match('/^(\*|\/\*)(.+)/i',trim($lines[$n]),$imatch)){
				$raw=$lines[$n];
				$cline=$imatch[2];
				//if(stringContains($cline,'userValue')){echo $cline;exit;}
				$cline=preg_replace('/\t/','[[tab]]',$cline);
				$cline=str_replace('&nbsp;','',$cline);
				$cline=trim($cline);
				$cline=encodeHtml($cline);
				$cline=str_replace('&amp;nbsp;','&nbsp;',$cline);
				$cline=str_replace("*/",'',trim($cline));
				$cline=preg_replace('/^\/+/','',trim($cline));
				$cline=preg_replace('/^\*+/','',trim($cline));
				if(strlen(trim($cline))){
					$cline=trim($cline);
					$cline=preg_replace('/^\s+/','',$cline);
					$cline=str_replace('[[tab]]',"\t",$cline);
					$helplines[] = $cline;
				}
				$rawhelplines[]=trim($raw);
				$n--;
            }
            $current['helplines']=$helplines;
            $current['documented']=0;
            if(count($helplines)){
            	$helplines=array_reverse($helplines);
            	$rawhelplines=array_reverse($rawhelplines);
            	$type='info';
            	$has_updated_help=0;
            	foreach($helplines as $helpline){
					if(preg_match('/^\@([a-z]+?)\s(.*)/',$helpline,$hmatch)){
                    	$type=strtolower($hmatch[1]);
                    	if(strlen($hmatch[2])){$current[$type][]= trim($hmatch[2]);}
                    	$current['documented']=1;
                    	continue;
					}
					elseif(preg_match('/^\@([a-z]+)$/i',$helpline,$hmatch)){
                    	$type=strtolower($hmatch[1]);
                    	continue;
					}
					if(!isset($reservedTypes[$type])){
						$current[$type][]=' &nbsp; &nbsp; '.$helpline;
					}
				}
			}
            //get the function body
            if(1==2 && !isset($current['usage']) && in_array($current['lang'],array('PHP','javascript'))){
	            $bodylines=array(rtrim($lines[$x]));
				$n=$x+1;
				while(!preg_match('/^(function|\?\>)/i',trim($lines[$n])) && $n < count($lines) && trim($lines[$n]) != '//----------------------'){
					$bodylines[] = $lines[$n];
					if(stringBeginsWith(trim($lines[$n]),'//---------- begin function ')){break;}
					$n++;
	            }
	            if(count($bodylines)){
                	//remove any comments at the end
                	$blines=array_reverse($bodylines);
                	$bodylines=array();
                	foreach($blines as $bline){
                    	if(preg_match('/^(\*|\/)/i',trim($bline))){continue;}
                    	if(!strlen(rtrim($bline))){continue;}
                    	$bodylines[]=rtrim($bline);
					}
					$bodylines=array_reverse($bodylines);
					$body=implode("\r\n",$rawhelplines)."\r\n";
					$body.=implode("\r\n",$bodylines);
					$current['body'].=encodeBase64($body);
				}
				//echo "HERE - B" . printValue($current);exit;
			}
            //exclude this function from the help by adding an "exclude" key
			if(isset($current['exclude'])){continue;}
			//paths
            if(!isset($Manual['paths']) || !in_array($current['path'],$Manual['paths'])){
            	$Manual['paths'][]=$current['path'];
			}
			//files
            if(!isset($Manual['files']) || !in_array($current['file'],$Manual['files'])){
            	$Manual['files'][]=$current['file'];
			}
            //langs
            if(!isset($Manual['langs']) || !in_array($current['lang'],$Manual['langs'])){
            	$Manual['langs'][]=$current['lang'];
			}
            //keys?
            foreach($current as $key=>$val){
            	if(!isset($Manual['keys'][$key])){
                	$Manual['keys'][$key]="Line {$x} in {$file}";
				}
			}
			if(!isset($current['param']) && strlen($function_params)){$current['param'][]=$function_params;}
			$current['index']=sha1($current['lang'].$current['file'].$current['name']);
			if(!isset($Manual['index'][$current['index']])){
	            ksort($current);
	            $Manual['functions'][]=$current;
				$Manual['tree'][$treenode][$current['file']][]=$current;
				$Manual['index'][$current['index']]=$current;
			}
		}
	}
	return $function_cnt;
}
function manualRebuild(){
	global $Manual;
	global $progpath;
	$Manual=array(
		'timestamp'=>time()
	);
	//get PHP functions
	$rtn='';
	//PHP dir
	$cdir=$progpath;
	if ($handle = opendir($cdir)) {
    	$files=array();
    	while (false !== ($file = readdir($handle))) {
			if($file == '.' || $file == '..' || !preg_match('/\.php$/i',$file) || preg_match('/\_(test|example|css|js|install)/i',$file)){continue;}
			$cnt = wasqlParseHelp("{$cdir}/{$file}");
			if(isNum($cnt)){
				$rtn .= "{$cnt} functions found in {$file}<br>\n";
			}
    	}
    	closedir($handle);
	}
	//js files
	$files=array(
		'../wfiles/js/common.js',
		'../wfiles/js/event.js',
		'../wfiles/js/form.js',
		'../wfiles/js/html5.js'
	);
	//add any custom js files found in ../wfiles/js/custom folder
	$cfiles=listFiles('../wfiles/js/custom');
	if(is_array($cfiles)){
		foreach($cfiles as $cfile){
			if(preg_match('/\.js$/i',$cfile)){$files[]="../wfiles/js/custom/{$cfile}";}
		}
	}
	//add extras $phpdir/extras
	$cfiles=listFiles("{$phpdir}/extras");
	if(is_array($cfiles)){
		foreach($cfiles as $cfile){
			if(preg_match('/\.php$/i',$cfile)){$files[]="{$phpdir}/extras/{$cfile}";}
		}
	}
	foreach($files as $file){
		if(stringContains($file,'/')){
			$cfile=realpath("{$file}");
		}
		else{
			$cfile=realpath("{$phpdir}/{$file}");
		}
		$cnt = wasqlParseHelp($cfile);
		if(isNum($cnt)){
			$rtn .= "{$cnt} functions found in {$cfile}<br>\n";
		}
	}
	//get the functions from the pages table
	$recs=getDBRecords(array('-table'=>'_pages','-where'=>"functions is not null",'-fields'=>"_id,functions,name"));
	if(is_array($recs)){
		foreach($recs as $rec){
			$cnt = wasqlParseHelp($rec['functions'],"Page: '{$rec['name']}', Record: {$rec['_id']}, Field: functions");
			if(isNum($cnt)){
				$rtn .= "{$cnt} functions found in Page: '{$rec['name']}', Record: {$rec['_id']}, Field: functions<br>\n";
			}
		}
	}
	//get the functions from the pages table body
	$recs=getDBRecords(array('-table'=>'_pages','-where'=>"name like '%functions'",'-fields'=>"_id,body,name"));
	if(is_array($recs)){
		foreach($recs as $rec){
			$cnt = wasqlParseHelp($rec['body'],"Page: '{$rec['name']}', Record: {$rec['_id']}, Field: body");
			if(isNum($cnt)){
				$rtn .= "{$cnt} functions found in Page: '{$rec['name']}', Record: {$rec['_id']}, Field: body<br>\n";
			}
		}
	}
	//get the functions from the templates table
	$recs=getDBRecords(array('-table'=>'_templates','-where'=>"functions is not null",'-fields'=>"_id,functions,name"));
	if(is_array($recs)){
		foreach($recs as $rec){
			$cnt = wasqlParseHelp($rec['functions'],"Template: '{$rec['name']}', Record: {$rec['_id']}, Field: functions");
			if(isNum($cnt)){
				$rtn .= "{$cnt} functions found in Template: '{$rec['name']}', Record: {$rec['_id']}, Field: functions<br>\n";
			}
		}
	}
	$ok=setFileContents("{$phpdir}/temp/manual.json",json_encode($Manual));
	return $rtn;
}


?>
