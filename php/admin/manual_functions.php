<?php
function manualParseFile($file){
	global $docs;
	$lines=file($file);
	$cnt=count($lines);
	for($x=0;$x<$cnt;$x++){
		$line=trim($lines[$x]);
		if(preg_match('/^function\ (.+?)\(\$(.+?)\)\ *\{/',$line,$m)){
			$doc=array(
				'file'=>$file,
				'folder'=>getFileName(getFilePath($file)),
				'line'=>$x+1,
				'name'=>$m[1],
				'call'=>$m[0].'}'
			);
			//backup to read phpdoc comments before the function name
			$p=$x-1;
			$comments=array();
			while(1){
				$pline=trim($lines[$p]);
				if(!strlen($pline)){break;}
				if(preg_match('/^\}/',$pline)){break;}
				$comments[]=encodeHtml($pline);
				if($p==0){break;}
				$p--;
			}
			$doc['comments']=array_reverse($comments);
			$key='';
			//if($doc['name']=='arrayAverage'){echo printValue($doc);}
			foreach($doc['comments'] as $cline){
				$cline=trim($cline);

				if(preg_match('/\@([a-z]+)(.*)$/i',$cline,$c)){
					//if($doc['name']=='arrayAverage'){echo $key.printValue($c);}
					$key=$c[1];
					$v=trim($c[2]);
					if(strlen($v)){
						$v=preg_replace('/\t/','&nbsp;&nbsp;&nbsp;&nbsp;',$v);
						$v=preg_replace('/^\*/','&nbsp;',$v);
						$doc[$key][]=$v;
					}
				}
				elseif(strlen($key) && preg_match('/^\*(.*)$/',$cline,$c)){
					//if($doc['name']=='arrayAverage'){echo $key.printValue($c);}
					$v=trim($c[1]);
					if($v != '/' && strlen($v)){
						$v=preg_replace('/\t/','&nbsp;&nbsp;&nbsp;&nbsp;',$v);
						$v=preg_replace('/^\*/','&nbsp;',$v);
						$doc[$key][]=$v;
					}
				}
			}
			//if($doc['name']=='arrayAverage'){echo printValue($doc);exit;}
			if(!isset($doc['exclude'])){
				$docs[$file][]=$doc;
			}

		}
	}
	//sort docs by file and by function name
	foreach($docs as $k=>$v){
		$docs[$k]=sortArrayByKey($docs[$k],'name');
	}
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
