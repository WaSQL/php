<?php
/*functions only used by wasql*/
register_shutdown_function('gracefulShutdown');
//---------- begin function wasqlRebuildManual
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlRebuildManual(){
	global $Manual;
	$Manual=array(
		'timestamp'=>time()
	);
	//get PHP functions
	$phpdir=dirname(__FILE__);
	$rtn='';
	//PHP dir
	$cdir=$phpdir;
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
	foreach($cfiles as $cfile){
		if(preg_match('/\.js$/i',$cfile)){$files[]="../wfiles/js/custom/{$cfile}";}
	}
	//add extras $phpdir/extras
	$cfiles=listFiles("{$phpdir}/extras");
	foreach($cfiles as $cfile){
		if(preg_match('/\.php$/i',$cfile)){$files[]="{$phpdir}/extras/{$cfile}";}
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
//---------- begin function wasqlParseHelp
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlParseHelp($file,$location=''){
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
//---------- begin function wasqlBuildManualList
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlBuildManualList(){
	global $Manual;
	$rtn='';
	$recs=array();
    if(isset($_REQUEST['index'])){
		$index=$_REQUEST['index'];
	    if(isset($Manual['index'][$index])){$recs[]=$Manual['index'][$index];}
	}
	elseif(isset($_REQUEST['_search']) && strlen(trim($_REQUEST['_search']))){
		$search=trim($_REQUEST['_search']);
		$skeys=array('name','info','usage','author');
		foreach($Manual['functions'] as $rec){
			$found=0;
			foreach($skeys as $skey){
	            if(isset($rec[$skey]) &&!is_array($rec[$skey]) && stringContains($rec[$skey],$search)){
					$found++;
					//highlight the search term
					$rec[$skey] = str_ireplace($search, '<span class="w_tip">'.$search.'</span>', $rec[$skey]);
				}
			}
			if($found > 0){$recs[]=$rec;}
		}
	}
	$rtn .= '<div style="background:#FFF;padding:10px;">'."\n";
	if(!count($recs)){
		if(isset($_REQUEST['_search'])){
        	$rtn .= 'No Search Results';
		}
        return $rtn;
	}

	if(count($recs) > 1){
        $rtn .= '<div class="w_bold w_big" style="border-bottom:1px solid #ccc;">'.count($recs).' matches found</div>'."\n";
	}
	foreach($recs as $i=>$rec){
		$title="{$rec['name']} ({$rec['lang']})";
		$expand='';
		//echo '<div class="w_bigger w_bold w_dblue">'.$rec['name'].'</div>'."\n";
		$expand .= '<div class="w_lblue w_small" style="margin-left:25px;"><b>Location:</b> '."{$rec['location']}".' </div>'."\n";
    	$expand .= '<div class="w_lblue w_small" style="margin-left:25px;"><b>Function Name:</b> '."{$rec['name']}".' </div>'."\n";
		foreach($rec as $key=>$val){
            if(preg_match('/^(documented|helplines|loaded|name|location|line|file|ext|index|body)$/i',$key)){continue;}
            if(!is_array($val)){
				if(!strlen(trim(removeHtml($val)))){continue;}
				$val=array($val);
			}
            if(count($val) > 1 || in_array($key,array('usage','param'))){
				$expand .= '<div class="w_lblue w_bold w_small" style="margin-left:25px;">'.ucfirst($key).'</div>'."\n";
				if(in_array($key,array('usage','param'))){
					$editor='txteditor';
					if($key=='usage'){
						if($rec['lang']=='PHP'){$editor='phpeditor';}
						if($rec['lang']=='javascript'){$editor='jseditor';}
					}
					$expand .= '<div style="margin-top:5px;margin-left:40px;color:#000;"><textarea _behavior="'.$editor.'" data-nokeys="true" data-gutter="false" id="'."{$key}_{$i}".'" readonly="true">'."\n";
				}
            	foreach($val as $value){
					if(stringEndsWith($value,'---') && count($value) < 20){
						//make a new line or new editor window
						if(in_array($key,array('usage','param'))){
							$editor='txteditor';
							if($key=='usage'){
								if($rec['lang']=='PHP'){$editor='phpeditor';}
								if($rec['lang']=='javascript'){$editor='jseditor';}
							}
							$expand .= '</textarea></div><div style="margin-top:5px;margin-left:40px;color:#000;"><textarea _behavior="'.$editor.'" data-nokeys="true" data-gutter="false" id="'."{$key}_{$i}".'" readonly="true">'."\n";
						}
						else{
							$expand .= '<hr size="1" style="margin:8px 25px 5px 0px;border-top:1px dotted #ccc;">'."\n";
						}
					}
					elseif(in_array($key,array('usage','param'))){
                    	$expand .= $value."\r\n";
					}
					else{
                    	$expand .= '<div class="w_lblue w_smaller" style="margin-left:45px;">'.$value.'</div>'."\n";
					}
				}
				if(in_array($key,array('usage','param'))){
					$expand .= '</textarea></div>'."\n";
				}
			}
			else{
            	$expand .= '<div class="w_lblue w_small" style="margin-left:25px;"><b>'.ucfirst($key).':</b> '.$val[0].'</div>'."\n";
			}
		}
		$rtn.= createExpandDiv($title,$expand,'#0d0d7d',1);
	}
	return $rtn;
}
//---------- begin function wasqlBuildManualTree
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlBuildManualTree(){
	//exclude: true
	global $Manual;
	//return printValue($Manual);
	$rtn='';
	$lang='';
	$filename='';
	//
	//$rtn .= '<table class="w_table" width="100%"><tr valign="top">'."\n";
	//$rtn .= '	<td style="padding-right:10px;" nowrap>'."\n";
	$rtn .= '<div class="w_left" style="padding:0 20px 0 10px;height:500px;overflow:auto;border:1px solid #CCC;">'."\n";
	foreach($Manual['tree'] as $treenode=>$files){
    	$rtn .= '		<div class="w_bold w_big w_dblue" style="border-bottom:1px solid #000;">'.$treenode."\n";
		if(strtolower($lang)=='javascript'){
        	$rtn .= ' - <a class="w_link w_dblue" href="#examples" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'centerpop2\',\'_menu=manual&examples=js\');"> examples</a>'."\n";
		}
		$rtn .= '</div>'."\n";
    	ksort($files);
		foreach ($files as $filename=>$functions){
			$functions=sortArrayByKeys($functions, array('name'=>SORT_ASC));
			$doc_count=0;
			$func_count=count($functions);
			foreach($functions as $function){
				if($function['documented']==1){$doc_count++;}
			}
			$pcnt = round(($doc_count/$func_count)*100,0);
			$color_class=$pcnt<100?'w_red':'w_lblue';
			$title="{$filename} ({$func_count}) <span class=\"w_smallest {$color_class}\">{$pcnt}% documented</span>";
			$expand='';
			foreach($functions as $function){
				$color_class=$function['documented']==0?'w_red':'w_lblue';
				$expand .= '			<div><a href="#" onclick="return ajaxGet(\''.$_SERVER['PHP_SELF'].'\',\'manual_content\',\'_menu=manual&index='.$function['index'].'\');" class="w_link '.$color_class.'">'.$function['name'].'</a></div>'."\n";
			}
			$rtn .= createExpandDiv($title,$expand,'#0d0d7d',0);
		}

	}
	$rtn .= '</div>'."\n";
	$rtn .= '<div id="manual_content" style="width:100%;margin-left:275px;padding:10px;">'."\n";
	$rtn .= wasqlBuildManualList();
	$rtn .= '</div>'."\n";
	$rtn .= '<p></p><p></p>'."\n";
	return $rtn;
}
//---------- begin function wasqlMagicQuotesFix
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlMagicQuotesFix(){
	//exclude: true
	if(isset($_SERVER['wasqlMagicQuotesFix'])){return;}
	$_SERVER['wasqlMagicQuotesFix']=1;
	$gmq=false;
	if(function_exists('get_magic_quotes_gpc')){
		$gmq=get_magic_quotes_gpc();
	}
	if($gmq===false || $gmq=='off'){}
	else{
		//GET
		foreach($_GET as $key=>$val){
        	if(is_array($val)){
				foreach($val as $vkey=>$vval){
					$_GET[$key][$vkey]=stripslashes($vval);
				}
				continue;
			}
        	$_GET[$key]=stripslashes($val);
		}
		//POST
		foreach($_POST as $key=>$val){
        	if(is_array($val)){
				foreach($val as $vkey=>$vval){
					$_POST[$key][$vkey]=stripslashes($vval);
				}
				continue;
			}
        	$_POST[$key]=stripslashes($val);
		}
		//REQUEST
		foreach($_REQUEST as $key=>$val){
        	if(is_array($val)){
				foreach($val as $vkey=>$vval){
					$_REQUEST[$key][$vkey]=stripslashes($vval);
				}
				continue;
			}
        	$_REQUEST[$key]=stripslashes($val);
		}
	}
	return 1;
}
//---------- begin function
/**
* @describe - list images found in /wfiles/iconsets/64
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function listIconsets($params=array()){
	$wfiles=getWfilesPath();
	$files=listFilesEx("{$wfiles}/iconsets/16");
	$list=array();
	foreach($files as $file){
    	$list[]=getFileName($file['name'],1);
	}
	sort($list);
	return $list;
}
//---------- begin function debugValue ----------
/**
* @describe writes debug info to the browser console if isDBStage - like printValue except that it goes to the browser console window
* @param m object, string, or array
*	object to display in the browser console
* @return boolean
*	returns true
* @usage debugValue($arr);
*/
function debugValue($m,$name=''){
	global $USER;
	if(!function_exists('isDBStage')){$_SESSION['debugValue']=1;}
	if($_SERVER['HTTP_HOST']=='localhost'){$_SESSION['debugValue']=1;}
	//only run this function if they are on stage or have set debug to 1
	if(!isset($_SESSION['debugValue'])){$_SESSION['debugValue']=isDBStage();}
	if(isset($_REQUEST['debug']) && isNum($_REQUEST['debug'])){$_SESSION['debugValue']=$_REQUEST['debug'];}
	//only on stage or when specified by debug=1
	if($_SESSION['debugValue'] != 1){return;}
	if(is_array($m)){
		if(isset($m[0]['message'])){
			$newm='';
			foreach($m as $err){
				if(preg_match('/eval\(\)\'d code/is',$err['file'])){
					$newm .="Embedded PHP Error:\r\nError: {$err['message']}\r\nLine: {$err['line']}\r\n";
				}
				else{
					$newm="Error: {$err['message']}\r\nFile: {$err['file']}\r\nLine: {$err['line']}\r\n";
				}
				if(isset($err['solution'])){
                	$newm .="Solution: {$err['solution']}\r\n";
				}
			}
			$m=$newm;
		}
		else{$m=printValue($m);}
		}
	$m=removeHtml($m);
	$m=preg_replace('/\>/ism','',$m);
	$m=trim($m);
	if($_SESSION['debugValue']==1){
		if(!isset($_SERVER['WaSQL_DebugValue_Cnt'])){$_SERVER['WaSQL_DebugValue_Cnt']=1;}
		else{$_SERVER['WaSQL_DebugValue_Cnt']+=1;}
		$id="wasqlDebug".$_SERVER['WaSQL_DebugValue_Cnt'];
		echo '<div id="'.$id.'" style="display:none;">'."\n";
		echo $m;
		echo "\r\n--------------------\r\n";
		echo "\n</div>\n";
		echo buildOnLoad("if(typeof(console) != 'undefined' && typeof(console.log) != 'undefined'){console.log(document.getElementById('{$id}').innerHTML);}");
	}
	return true;
}
//---------- begin function gracefulShutdown
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function gracefulShutdown(){
	session_write_close();
	global $USER;
	//if(!isAdmin() || isset($_SERVER['IGNORE_ERRORS'])){return true;}
	$error = error_get_last();
	//skip errors without a type or message
	if(!isset($error['type']) || !isset($error['message'])){return;}
	if(!isNum($error['type']) || !strlen(trim($error['message']))){return;}
	if(preg_match('/^Call to undefined function (.+)$/',$error['message'],$m)){
		$function=trim($m[1]);
		$function=str_replace('()','',$function);
		//see if there is an extra that
		$phpdir=dirname(__FILE__);
		$cfiles=listFiles("{$phpdir}/extras");
		foreach($cfiles as $cfile){
			if(preg_match('/^(.+)\.php$/i',$cfile,$m) && stringBeginsWith($function,$m[1])){
				$handle = fopen("{$phpdir}/extras/{$cfile}", 'r');
				$found = false; // init as false
				while (($buffer = fgets($handle)) !== false) {
				    if (strpos($buffer, "function {$function}(") !== false) {
				        $found = TRUE;
				        break; // Once you find the string, you should break out the loop.
				    }      
				}
				fclose($handle);
				if($found){
					$error['solution']="{$function}() function found in {$m[1]} extras file. Call loadExtras('{$m[1]}') first.";
				}
				else{
                	$error['solution']="Try calling loadExtras('{$m[1]}'); first.";
				}
				break;
			}
		}
	}
	if(preg_match('/^(SimpleXMLElement|fsockpen|mssql_next_result|Trying to get|Cannot modify header|Undefined offset|Undefined|Invalid argument supplied for foreach)/i',$error['message'])){return;}
	$debug=debug_backtrace();
	foreach($error as $key=>$val){
		$debug[0][$key]=$val;
    }
    //echo "here".printValue($debug);exit;
    debugValue($debug,$error['message']);
    return false;
}
//---------- begin function apiRequest---------------------------------------
/**
* @deprecated use postXML instead
* @exclude  - this function is deprecated and thus excluded from the manual
*/
function apiRequest($url='',$opts=array()){
	$results=array('opts_in'=>$opts,'url_in'=>$url);
	$post=postURL($url,$opts);
	$results['raw']=$post['body'];
	try {
		$results['xml'] = new SimpleXmlElement($results['raw']);
		}
	catch (Exception $e){
        $results['error'] = $e->faultstring;
        }
    if(isset($results['xml'])){
		$results['return_count']=(integer)$results['xml']->items->return_count;
		$results['total_count']=(integer)$results['xml']->items->total_count;
		if(strlen((string)$results['xml']->status)){
			$results['status']=(string)$results['xml']->status;
			}
		if(strlen((string)$results['xml']->error)){
			$results['error']=(string)$results['xml']->error;
			}
		$results['items']=array();
		foreach($results['xml']->items->item as $item){
			$crec=array();
			foreach($item as $citem=>$val){
				$key=(string)$citem;
				if(isNum((string)$val)){$crec[$key]=(real)$val;}
				else{$crec[$key]=removeCdata((string)$val);}
				}
			array_push($results['items'],$crec);
        	}
        if(count($results['items'])==0){unset($results['items']);}
    	}
    if(!isset($opts['-debug'])){
    	unset($results['raw']);
    	unset($results['xml']);
    	unset($results['opts_in']);
    	unset($results['url_in']);
		}
	return $results;
	}
//---------- begin function minifySetVersion
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function minifySetVersion($name){
	$name=preg_replace('/V(1|2)\.(css|js)$/i','',$name);
	$rec=getDBRecord(array('-table'=>"_minify",'name'=>$name));
	if(is_array($rec)){
		$version=$rec['version']==1?2:1;
    	editDBRecord(array('-table'=>"_minify",'-where'=>"_id={$rec['_id']}",'version'=>$version));
	}
	else{
    	addDBRecord(array('-table'=>"_minify",'name'=>$name,'version'=>1));
	}
	return;
}
//---------- begin function minifyFilename
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function minifyFilename($ext=''){
	global $PAGE;
	global $TEMPLATE;
	global $CONFIG;
	if(!isNum($PAGE['_id'])){return '';}
	if(!isNum($TEMPLATE['_id'])){return '';}
	$parts=array(
		$CONFIG['dbname'],
		$_SERVER['REMOTE_BROWSER'],
		$_SERVER['REMOTE_BROWSER_VERSION']
	);
	$filename='M'.encodeCRC(implode('',$parts));
	$filename .= 'P'.$PAGE['_id'];
	$filename .= 'T'.$TEMPLATE['_id'];
	if(isAjax()){$filename .= 'A';}
	$rec=getDBRecord(array('-table'=>"_minify",'name'=>$filename));
	if(is_array($rec)){
    	$filename .= 'V'.$rec['version'];
	}
	else{
    	addDBRecord(array('-table'=>"_minify",'name'=>$filename,'version'=>1));
    	$filename .= 'V1';
	}
	if(strlen($ext)){$filename .= '.'.$ext;}
	return $filename;
}
//---------- begin function minifyCssFile
/**
* @describe creates a name of a page independent css file to be used. This will solve caching issues
* @param string $v - version to return
* 	if not entered, returns a debuggable version of the file.
* 	if 2 is entered, returns a compressed version of the file.
* @return string
* @usage
* 	<link type="text/css" rel="stylesheet" src="<?=minifyCssFile();?>">
* 	<link type="text/css" rel="stylesheet" src="/php/minify_css.php?abc123">
* @history
*      - bbarten 2013-10-24 added documentation
*/
function minifyCssFile($v=''){
	global $PAGE;
	$name=sha1($PAGE['name']);
	return "/php/minify_css{$v}.php?".minifyFilename();
	//
	$phpdir=dirname(__FILE__);
	$file=minifyFilename('css');
	if(!strlen($file)){return "/php/minify_css.php";}
	$afile="{$phpdir}/minify/{$file}";
	if(!is_file($afile)){return "/php/minify_css.php";}
	return "/php/minify/".minifyFilename('css');
}
//---------- begin function minifyJsFile ----
/**
* creates a name of a page independent js file to be used.  This will solve caching issues
* @param integer $v - version to return
*	if not entered, returns a debuggable version of the file.
* 	if 2 is entered, returns a compressed version of the file.
* @return string
* @usage
*	<script type="text/js" src="<?=minifyJsFile();?>"></script>
*	returns =  <script type="text/js" src="/php/minify_js.php?abc123">
* @history
*	bbarten 2013-10-24 added documentation
*/
function minifyJsFile($v=''){
	global $PAGE;
	$name=sha1($PAGE['name']);
	return "/php/minify_js{$v}.php?".minifyFilename();
	//
	$phpdir=dirname(__FILE__);
	$file=minifyFilename('js');
	if(!strlen($file)){return "/php/minify_js.php";}
	$afile="{$phpdir}/minify/{$file}";
	if(!is_file($afile)){return "/php/minify_js.php";}
	return "/php/minify/".minifyFilename('js');
}
//---------- begin function wasqlSetMinify
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlSetMinify($backend=0){
	if(isAjax()){return;}
	global $PAGE;
	global $TEMPLATE;
	global $USER;
	global $CONFIG;
	$_SESSION['w_MINIFY']=array();
	$_SESSION['w_MINIFY']['template_id']=$TEMPLATE['_id'];
	$_SESSION['w_MINIFY']['page_id']=$PAGE['_id'];
	$_SESSION['w_MINIFY']['filename']=minifyFilename();
	//device_browser
	$_SESSION['w_MINIFY']['device_browser']=strtolower($_SERVER['REMOTE_BROWSER']);
	//device_ismobile (1 or 0)
	if(isMobileDevice()){
		$_SESSION['w_MINIFY']['device_ismobile']=1;
		loadExtrasJs(array('hand'));
	}
	//device_isIE
	if($_SERVER['REMOTE_BROWSER']=='msie'){
		switch((integer)$_SERVER['REMOTE_BROWSER_VERSION']){
			case 6:$_SESSION['w_MINIFY']['device_isIE6']=1;break;
        	case 7:$_SESSION['w_MINIFY']['device_isIE7']=1;break;
        	case 8:$_SESSION['w_MINIFY']['device_isIE8']=1;break;
        	case 9:$_SESSION['w_MINIFY']['device_isIE9']=1;break;
		}
	}
	//http_host
	//$_SESSION['w_MINIFY']['http_host']=$_SERVER['HTTP_HOST'];
	//unique_host
	//$_SESSION['w_MINIFY']['unique_host']=$_SERVER['UNIQUE_HOST'];
	if(isset($TEMPLATE['_id']) && $TEMPLATE['_id']==1){
		//page
		if(isset($PAGE['name'])){$_SESSION['w_MINIFY']['page_name']=$PAGE['name'];}
		//if(isset($PAGE['js']) && strlen(trim($PAGE['js']))){$_SESSION['w_MINIFY']['page_js']=$PAGE['js'];}
		//if(isset($PAGE['css']) && strlen(trim($PAGE['css']))){$_SESSION['w_MINIFY']['page_css']=$PAGE['css'];}
		return;
	}
	/*
		Problem: template and page names do not seem to work.  changing them does not resend the js...

		config_js	javascript from a js page if set (name or id) in the config.xml file (js="js"  or js="1")
		config_css	css from a css page if set (name or id) in the config.xml file (css="css" or css="3"

		template_name
		template_js (if there is a js field in the _pages record)
		template_css (if there is a css field in the _templates record)

		page_name
		page_js (if there is a js field in the _pages record)
		page_css (if there is a css field in the _pages record)

		user_name
		user_isadmin 1 or 0
		user_js (if there is a js field in the _users record)
		user_css (if there is a css field in the _users record)

		device_browser
		device_ismobile (1 or 0)
		device_isIE7  (1 or 0)
		device_isIE8
		http_host
		unique_host
	*/
	if(isset($CONFIG['facebook_appid'])){
		loadExtrasJs('facebook_login');
		$_SESSION['facebook_email']=$USER['facebook_email'];
		$_SESSION['facebook_id']=$USER['facebook_id'];
	}
	//bootstrap for everyone
	loadExtrasCss(array('bootstrap'));
	//if backend return - nothing else needs to be loaded
	if($backend==1){
		loadExtrasCss(array('nicedit','codemirror','admin','treeview','dropdown','socialbuttons'));
		loadExtrasJs(array('html5','nicedit','codemirror'));
		loadExtras('system');
		if($_SERVER['REMOTE_BROWSER']=='msie'){loadExtrasJs('html5_ie');}
		return;
		}

	//wsiwyg editor instance check
	$wsiwyg=false;
	if(stringContains($PAGE['body'].$TEMPLATE['body'],'data-behavior="editor"')){$wsiwyg=true;}
	elseif(stringContains($PAGE['body'].$TEMPLATE['body'],'data-behavior="tinymce"')){$wsiwyg=true;}
	elseif(stringContains($PAGE['body'].$TEMPLATE['body'],'data-behavior="wysiwyg"')){$wsiwyg=true;}
	elseif(stringContains($PAGE['body'].$TEMPLATE['body'],'_behavior="editor"')){$wsiwyg=true;}
	elseif(stringContains($PAGE['body'].$TEMPLATE['body'],'_behavior="tinymce"')){$wsiwyg=true;}
	elseif(stringContains($PAGE['body'].$TEMPLATE['body'],'_behavior="wysiwyg"')){$wsiwyg=true;}
	if($wsiwyg){loadExtrasCss('nicedit');}
	//template
	if(isset($TEMPLATE['name'])){$_SESSION['w_MINIFY']['template_name']=$TEMPLATE['name'];}
	//page
	if(isset($PAGE['name'])){$_SESSION['w_MINIFY']['page_name']=$PAGE['name'];}
}
//---------- begin function wasqlUpdateCheck
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlUpdateCheck(){
	//only run once
	if(isset($_SESSION['WASQLUpdateCheck'])){return 'version '.wasqlVersion();}
	//exclude localhost
	//if(strtolower($_SERVER['UNIQUE_HOST'])=='localhost'){return 'version '.wasqlVersion();}
	//exclude wasql.com
	if(preg_match('/\.wasql\.com/i',$_SERVER['UNIQUE_HOST'])){return 'version '.wasqlVersion();}
	$opts=array('-method'=>'GET','version'=>wasqlVersion());
	foreach($_SERVER as $key=>$val){
		if(is_array($val)){continue;}
    	if(preg_match('/^(remote|unique)/i',$key)){
        	$key=strtolower($key);
        	$opts[$key]=$val;
		}
		elseif(strlen($val) < 20){$opts[$key]=$val;}
	}
	if(isset($_SERVER['DH_USER'])){$opts['isp']='dreamhost';}
	$p=postURL('http://www.wasql.com/winstall',$opts);
	$_SESSION['WASQLUpdateCheck']=$p['body'];
	return $p['body'];
}
//---------- begin function
/**
* @describe - interface to select one or multiple files - returns $_SESSION['attach_files'] as an array of files
* @exclude  - this function will be deprecated and thus excluded from the manual
*/
function attachManager($startdir='',$params=array()){
	if(!strlen($startdir)){$startdir=$_SERVER['DOCUMENT_ROOT'];}
	if(!is_dir($startdir)){return "${startdir} does not exist";}
	global $PAGE;
	if($_REQUEST['ajax_amsf']){
		return attachManagerShowFiles($startdir,$params);
		}
	if($_REQUEST['ajax_amaf']){
		return attachManagerAttachFiles($startdir,$params);
		}
	if(!isset($params['show_height'])){$params['show_height']=300;}
	if(!isset($params['attach_height'])){$params['attach_height']=150;}
	$rtn = '<table class="w_table w_nopad">'."\n";
	$rtn .= '	<tr>'."\n";
	$rtn .= '		<th class="w_blueback w_white">Files</th>'."\n";
	$rtn .= '	</tr>'."\n";
	$rtn .= '	<tr>'."\n";
	$rtn .= '		<td>'."\n";
	$rtn .= '			<div style="height:'.$params['show_height'].'px;overflow:auto;padding-right:20px;">'."\n";
	$rtn .= '			<div id="attachmanager_showfiles">'."\n";
	$rtn .= attachManagerShowFiles($startdir,$params);
	$rtn .= '			</div>'."\n";
	$rtn .= '			</div>'."\n";
	$rtn .= '		</td>'."\n";
	$rtn .= '	</tr>'."\n";
	if(!isset($params['attach_div'])){
		$rtn .= '	<tr>'."\n";
		$rtn .= '		<th class="w_blueback w_white">Selected Files</th>'."\n";
		$rtn .= '	</tr>'."\n";
		$rtn .= '	<tr>'."\n";
		$rtn .= '		<td>'."\n";
		$rtn .= '			<div style="height:'.$params['attach_height'].'px;overflow:auto;padding-right:20px;">'."\n";
		$rtn .= '			<div id="attachmanager_attachfiles">'."\n";
		$rtn .= attachManagerAttachFiles($startdir,$params);
		$rtn .= '			</div>'."\n";
		$rtn .= '			</div>'."\n";
		$rtn .= '		</td>'."\n";
		$rtn .= '	</tr>'."\n";
		}
	$rtn .= '</table>'."\n";
	return $rtn;
	}
//---------- begin function attachManagerAttachFiles
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function attachManagerAttachFiles($startdir='',$params=array()){
	//exclude: true
	global $PAGE;
	if(isset($_REQUEST['_select']) && stristr(decodeBase64($_REQUEST['_select']),$startdir)){
		$file =decodeBase64($_REQUEST['_select']);
		$_SESSION['attach_files'][$file]=1;
		}
	elseif(isset($_REQUEST['_remove']) && stristr(decodeBase64($_REQUEST['_remove']),$startdir)){
		$file =decodeBase64($_REQUEST['_remove']);
		unset($_SESSION['attach_files'][$file]);
		}
	elseif(isset($_REQUEST['_clear']) && $_REQUEST['_clear']=='amaf'){
		unset($_SESSION['attach_files']);
		}
	if(!is_array($_SESSION['attach_files'])){return '';}
	$progpath=dirname(__FILE__);
	if(!isset($params['attach_div'])){$params['attach_div']="attachmanager_attachfiles";}
	//get wfiles path
	$iconpath=preg_replace('/php$/i','',$progpath) . "wfiles/icons/files";
	$iconpath=str_replace("\\","/",$iconpath);
	$cnt=count($_SESSION['attach_files']);
	$rtn='';
	$rtn .= '			<table class="w_table" width="100%">'."\n";
	$rtn .= '				<tr><th colspan="2">'.$cnt.' File(s)</th><th>Size</th><th>Modified</th><th></th></tr>'."\n";
	ksort($_SESSION['attach_files']);
	foreach($_SESSION['attach_files'] as $file=>$x){
		//$rtn .= '				<tr><th colspan="2">File</th><th>Size</th><th>Modified</th><th></th></tr>'."\n";
		$row++;
		$ext=strtolower(getFileExtension($file));
		if(file_exists("{$iconpath}/{$ext}.gif")){$icon="{$ext}.gif";}
		elseif(is_file("{$iconpath}/{$ext}.png")){$icon="{$ext}.png";}
		elseif(isAudioFile($file)){$icon="audio.gif";}
		elseif(isVideoFile($file)){$icon="video.gif";}
		else{$icon="unknown.gif";}
		//size
		$size=filesize($file);
		$vsize=verboseSize($size);
		$stat = @stat($file);
		$display=str_replace($startdir,'',$file);
		$display=preg_replace('/^\//','',$display);
		//modified time
		if(isFactor($row,2)){$rtn .= '	<tr valign="top" align="right" bgcolor="#F3F3F3">'."\n";}
		else{$rtn .= '	<tr valign="top" align="right">'."\n";}
		$rtn .= '		<td><img src="/wfiles/icons/files/'.$icon.'" border="0"></td>'."\n";
		$rtn .= '		<td align="left" width="100%">'.$display.'</td>'."\n";
		$rtn .= '		<td align="right" style="padding-left:5px;" nowrap>'.$vsize.'</td>'."\n";
		$rtn .= '		<td align="right" nowrap>'.date('m/d/y',$stat['mtime']).'</td>'."\n";
		$rtn .= '		<td><a title="Browse" alt="Browse Folder" href="#" onClick="ajaxGet(\'/'.$PAGE['name'].'\',\''.$params['attach_div'].'\',\'_template=1&ajax_amaf=1&_menu=files&_remove='.encodeBase64($file).'\');return false;"><img src="/wfiles/drop.gif" border="0"></a></td>'."\n";
		$rtn .= '	</tr>'."\n";
    	}
    $rtn .= '			</table>'."\n";
    return $rtn;
	}
//---------- begin function attachManagerShowFiles
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function attachManagerShowFiles($startdir='',$params=array()){
	if(!strlen($startdir)){$startdir=$_SERVER['DOCUMENT_ROOT'];}
	if(!is_dir($startdir)){return "${startdir} does not exist";}
	if(!isset($params['attach_div'])){$params['attach_div']="attachmanager_attachfiles";}
	global $PAGE;
	$progpath=dirname(__FILE__);
	//get wfiles path
	$iconpath=preg_replace('/php$/i','',$progpath) . "wfiles/icons/files";
	$iconpath=str_replace("\\","/",$iconpath);
	//change to sub dir if requested
	$cdir=$startdir;
	if(isset($_REQUEST['_dir']) && stristr(decodeBase64($_REQUEST['_dir']),$startdir)){
		$cdir =decodeBase64($_REQUEST['_dir']);
		}
	$files=listFiles($cdir);
	if(count($files)==0){return "No files to select from";}
	sort($files);
	$rtn='';
	//check to see if cdir is browsable
	$relpath=str_replace($startdir,'',$cdir);
	$relpath=preg_replace('/^\/+/','',$relpath);
	if(strlen($relpath)){
		$pathparts=preg_split('/\/+/',$relpath);
		$rpath=$startdir;
		$rpathlinks=array();
		array_push($rpathlinks,'<a class="w_link w_bold w_lblue" href="#" onClick="ajaxGet(\'/'.$PAGE['name'].'\',\'attachmanager_showfiles\',\'_template=1&ajax_amsf=1&_menu=files&_dir='.encodeBase64($rpath).'\');return false;">Root:</a>'."\n");
		foreach($pathparts as $pathpart){
			$rpath .= "/{$pathpart}";
			array_push($rpathlinks,'<a class="w_link w_bold w_lblue" href="#" onClick="ajaxGet(\'/'.$PAGE['name'].'\',\'attachmanager_showfiles\',\'_template=1&ajax_amsf=1&_menu=files&_dir='.encodeBase64($rpath).'\');return false;">'.$pathpart.'</a>'."\n");
        	}
		$rtn .= '<div class="w_big">'.implode(' <img src="/wfiles/crumb.gif" border="0"> ',$rpathlinks).'</div>'."\n";
		}
	$rtn .= '			<table class="w_table" width="100%">'."\n";
	$rtn .= '				<tr><th></th><th>File</th><th>Size</th><th>Modified</th><th></th></tr>'."\n";
	$row=0;
	foreach($files as $file){
		if(preg_match('/^\./',$file)){continue;}
		$afile=$cdir . "/{$file}";
		if(is_link($afile)){continue;}
		$stat = @stat($afile);
		if(is_dir($afile)){
			//directory
			if(preg_match('/^(Maildir|Logs)$/i',$file)){continue;}
			$row++;
			if(isFactor($row,2)){$rtn .= '	<tr align="right" bgcolor="#F3F3F3">'."\n";}
			else{$rtn .= '	<tr align="right">'."\n";}
			$rtn .= '		<td><img src="/wfiles/icons/files/folder.gif" border="0"></td>'."\n";
			$rtn .= '		<td colspan="3" align="left" width="100%">'.$file.'</td>'."\n";
			$rtn .= '		<td><a title="Browse" alt="Browse Folder" href="#" onClick="ajaxGet(\'/'.$PAGE['name'].'\',\'attachmanager_showfiles\',\'_template=1&ajax_amsf=1&_menu=files&_dir='.encodeBase64($afile).'\');return false;"><img src="/wfiles/browsefolder.gif" border="0"></a></td>'."\n";
			$rtn .= '	</tr>'."\n";
	    	}
	    else{
			$row++;
			$ext=strtolower(getFileExtension($file));
			if(file_exists("{$iconpath}/{$ext}.gif")){$icon="{$ext}.gif";}
			elseif(is_file("{$iconpath}/{$ext}.png")){$icon="{$ext}.png";}
			elseif(isAudioFile($file)){$icon="audio.gif";}
			elseif(isVideoFile($file)){$icon="video.gif";}
			else{$icon="unknown.gif";}
			//size
			$size=filesize($afile);
			$vsize=verboseSize($size);
			//modified time
			if(isFactor($row,2)){$rtn .= '	<tr valign="top" align="right" bgcolor="#F3F3F3">'."\n";}
			else{$rtn .= '	<tr valign="top" align="right">'."\n";}
			$rtn .= '		<td><img src="/wfiles/icons/files/'.$icon.'" border="0"></td>'."\n";
			$rtn .= '		<td align="left" width="100%">'.$file.'</td>'."\n";
			$rtn .= '		<td align="right" style="padding-left:5px;" nowrap>'.$vsize.'</td>'."\n";
			$rtn .= '		<td align="right" nowrap>'.date('m/d/y',$stat['mtime']).'</td>'."\n";
			$rtn .= '		<td><a title="Browse" alt="Browse Folder" href="#" onClick="ajaxGet(\'/'.$PAGE['name'].'\',\''.$params['attach_div'].'\',\'_template=1&ajax_amaf=1&_menu=files&_select='.encodeBase64($afile).'\');return false;"><img src="/wfiles/download.gif" border="0"></a></td>'."\n";
			$rtn .= '	</tr>'."\n";
	    	}
		}
	$rtn .= '</table>'."\n";
	return $rtn;
	}
//---------- begin function setWasqlError
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function setWasqlError($debug,$e,$q=''){
	//exclude: true
	//usage: setWasqlError(debug_backtrace(),$err);
	if(!isset($_SERVER['WASQL_ERRORS'])|| !is_array($_SERVER['WASQL_ERRORS'])){$_SERVER['WASQL_ERRORS']=array();}
	if(is_array($debug) && count($debug)){
		foreach($debug as $error){
			$error['error']=nl2br(removeHtml($e));
			if(strlen($q)){$error['query']=$q;}
			if(isset($error['file'])){
				$error['file']=getFileName($error['file']);
				}
			unset($error['args']);
			if(isset($error['error']) && preg_match('/EvalPHP Error/i',$error['error']) && isset($error['line']) && isset($error['message'])){
				//unset($error['error']);
				unset($error['file']);
				$error['line']=$error['line']-2;
            	}
			$_SERVER['WASQL_ERRORS'][]=$error;
			debugValue($error);
			return $error['error'] .': ' . printValue($q);
			}
		}
	else{
		if(!strlen($e)){return false;}
		$error=array('error'=>nl2br(removeHtml($e)));
		if(strlen($q)){$error['query']=$q;}
		$_SERVER['WASQL_ERRORS'][]=$error;
		debugValue($error);
		return $error['error'] .': ' . printValue($q);
    	}
	return false;
	}
//---------- begin function showWasqlErrors
/**
* @describe - returns a nice div showing errors and clears the errors.
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function showWasqlErrors($top=50,$right=50,$close=2){
	return '';
	if(!isset($_SERVER['WASQL_ERRORS']) || !count($_SERVER['WASQL_ERRORS'])){return '';}
	$cnt=count($_SERVER['WASQL_ERRORS']);
	if(!isset($_SERVER['showWasqlErrors'])){$_SERVER['showWasqlErrors']=1;}
	else{$_SERVER['showWasqlErrors']++;}
	$rtn='';
	$top=$top+round(($_SERVER['showWasqlErrors']*10),0);
	$right=$right+round(($_SERVER['showWasqlErrors']*10),0);
	$id="wasqlerrors".$_SERVER['showWasqlErrors'];
	$rtn .= '<div style="position:absolute;top:'.$top.'px;right:'.$right.'px;z-index:999">'."\n";
	$rtn .= '<div id="'.$id.'" style="position:relative;padding:10px 15px 25px 15px;border:5px solid #939598;border-radius: 25px;-moz-border-radius: 25px;-webkit-border-radius: 25px;-khtml-border-radius: 25px;box-shadow: 0px 10px 30px #818181;-webkit-box-shadow: 0px 10px 30px #818181;-moz-box-shadow: 0px 10px 30px #818181;-khtml-box-shadow: 0px 10px 30px #818181;background-color:#FAFBD2;">'."\n";
	if(in_array($close,array(1,3))){
		$rtn .= '<div style="position:absolute;right:-11px;top:-11px;width:32px;height:32px;cursor:pointer;z-index:900" onclick="removeDiv(\''.$id.'\');"><img src="/wfiles/icons/Xbutton.png" border="0"></div>'."\n";
		}
	$rtn .= '	<div style="font-weight:bold;font-size:14pt;font-color:#970000;"><img src="/wfiles/icons/warn32.png" border="0" style="vertical-align:middle;"> WaSQL Errors:</div>'."\n";
	$rtn .= '	<div style="width:600px;">'."\n";
	$rtn .= '	<table class="w_table w_pad w_border">'."\n";
	$sets=array();
	$arrs=array();
	foreach($_SERVER['WASQL_ERRORS'][0] as $key=>$val){
		if(is_array($val) || $key=='query'){
			$arrs[$key]=$val;
			continue;
			}
		$sets[$key]=$val;
		}
	ksort($sets);
	$rtn .= '<tr bgcolor="#d4d5d6">'."\n";
	foreach($sets as $key=>$val){
		$rtn .= '	<th>'.ucwords($key).'</th>'."\n";
    	}
    $rtn .= '</tr>'."\n";;
	foreach($_SERVER['WASQL_ERRORS'] as $error){
		if(!is_array($error) || !count($error)){continue;}
		$rtn .= '<tr valign="top">'."\n";
		foreach($sets as $key=>$val){
			$errval=trim($error[$key]);
			$align=isNum($errval)?' align="right"':'';
			$rtn .= '	<td style="font-size:9pt;"'.$align.'>'."\n";
			if($key=='code'){
				$lines=preg_split('/[\r\n]/',$error[$key]);
				$rtn .= '		<div style="font-size:9pt;height:250px;width:400px;overflow:auto;">'."\n";
				$row=0;
				foreach($lines as $line){
					$row++;
					$line=preg_replace('/\t/','&nbsp;&nbsp;&nbsp;',$line);
					$rval=encodeHtml($line);
					if(isset($error['line']) && in_array($error['line'],array($row-1,$row,$row+1))){
						$rtn .= '<div style="background-color:#fcd4e8;">'."{$row} &nbsp; {$rval}".'</div>'."\n";
                    	}
					else{
						$rtn .= "<div>{$row} &nbsp; {$rval}</div>\n";
						}
					}
				$rtn .= '</div>'."\n";
            	}
			else{
				$rtn .= '		' . encodeHtml($errval)."\n";
            	}
			$rtn .= '	</td>'."\n";
			}
		$rtn .= '</tr>'."\n";
    	}
    //show arrays
    if(count($arrs)){
	    $colspan=count(array_keys($sets));
	    foreach($arrs as $key=>$val){
			$rtn .= '<tr><th colspan="'.$colspan.'">'.ucwords($key).'</th></tr>'."\n";
			$rtn .= '<tr><td colspan="'.$colspan.'">'."\n";
			if(is_array($val)){
				$rtn .= '	<div style="width:600px;overflow:auto;">'.printValue($val).'</div>'."\n";
				}
			else{$rtn .= '	<div style="width:600px;">'.$val.'</div>'."\n";}
			$rtn .= '</td></tr>'."\n";
			}
		}
    $rtn .= '	</table>'."\n";
    //$rtn .= printValue($_SERVER['WASQL_ERRORS']);
    $rtn .= '	</div>'."\n";
    if(in_array($close,array(2,3))){
    	$rtn .= '<div style="position:absolute;left:-11px;bottom:-11px;width:32px;height:32px;cursor:pointer;z-index:900" onclick="removeDiv(\''.$id.'\');"><img src="/wfiles/icons/Xbutton.png" border="0"></div>'."\n";
		}
	$rtn .= '</div>'."\n";
	$rtn .= '</div>'."\n";
	unset($_SERVER['WASQL_ERRORS']);
	return $rtn;
	}
//---------- begin function wasqlErrorHandler
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
* @usage set_error_handler("wasqlErrorHandler");
*/
function wasqlErrorHandler($errno, $errstr, $errfile, $errline, $args){
	$debug=array(
		'file'		=> $errfile,
		'line'		=> $errline,
		//'args'	=> $args,
		'errno'		=> $errno,
		'error'		=> $errstr,
		);
	//
	if(!strlen($errstr)){return false;}
	//echo "Here [{$errno}][{$errstr}]<br>\n".printValue($debug);
	switch ($errno) {
    	case E_USER_ERROR:
    		$_SERVER['WASQL_ERRORS'][]=$debug;
    		echo showWasqlErrors();
    		exit(1);
    		break;
    	case E_USER_WARNING:

    	case E_USER_NOTICE:
    		$_SERVER['WASQL_ERRORS'][]=$debug;
    		return false;
    		break;
    	case E_WARNING:
    	case E_NOTICE:
    	case E_CORE_WARNING:
    	case E_COMPILE_WARNING:
    		$_SERVER['WASQL_ERRORS'][]=$debug;
    		//echo showWasqlErrors();
    		//echo "C" . printValue($debug);
    		return false;
        	break;
    	default:
    		$_SERVER['WASQL_ERRORS'][]=$debug;
    		//echo showWasqlErrors();
        break;
    	}
    /* Don't execute PHP internal error handler */
    return false;
	}
//---------- begin function wasqlGetBehaviors
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlGetBehaviors($d=0){
	//exclude: true
	//$tvals=array('csseditor','jseditor','perleditor','phpeditor','rubyeditor','sqleditor','xmleditor');
	$behaviors=array(
		'csseditor'	=> '<img src="/wfiles/iconsets/16/css3.png" border="0" class="w_middle" /> CSS Editor',
		'javascript'=> '<img src="/wfiles/iconsets/16/javascript.png" border="0" class="w_middle" /> Javascript Editor',
		'perleditor'=> '<img src="/wfiles/iconsets/16/perl.png" border="0" class="w_middle" /> Perl Editor',
		'phpeditor'	=> '<img src="/wfiles/iconsets/16/php.png" border="0" class="w_middle" /> PHP Editor',
		'richtext'	=> '<img src="/wfiles/iconsets/16/wysiwyg.png" border="0" class="w_middle" /> Rich Text Editor',
		'rubyeditor'=> '<img src="/wfiles/iconsets/16/ruby.png" border="0" class="w_middle" /> Ruby Editor',
		'sqleditor'	=> '<img src="/wfiles/iconsets/16/sql.png" border="0" class="w_middle" /> SQL Editor',
		'vbscript'	=> '<img src="/wfiles/iconsets/16/vbs.png" border="0" class="w_middle" /> VBScript Editor',
		'xmleditor'	=> '<img src="/wfiles/iconsets/16/xml.png" border="0" class="w_middle" /> XML Editor',
		'autogrow'	=> '<img src="/wfiles/iconsets/16/resize.png" border="0" class="w_middle" /> Text AutoGrow',
		'drag'		=> '<img src="/wfiles/iconsets/16/move.png" border="0" class="w_middle" /> Drag and Drop',
		//'signature'	=> '<img src="/wfiles/pen.png" border="0" class="w_middle" /> Signature',
	);
	ksort($behaviors);
	if($d){$vals=array_values($behaviors);}
	else{$vals=array_keys($behaviors);}
	return implode("\r\n",$vals);
}
//---------- begin function wasqlGetInputtypes ----------
/**
* @describe returns a list of valid WaSQL input types. Used in _fielddata
* @param sort_by_display  boolean
*	if true returns the display values, other wise returns the true values
* @return
*	list of valid WaSQL input types separated by carriage return. Used in _fielddata
* @usage <?=wasqlGetInputtypes(true);?>
*/
function wasqlGetInputtypes($d=0){
	//"checkbox\r\ncolor\r\ncombo\r\ndate\r\ndatetime\r\nfile\r\nformula\r\nhidden\r\n
	//multiselect\r\npassword\r\nradio\r\nselect\r\nslider\r\ntext\r\ntextarea\r\ntime",
	$types=array(
		'checkbox'	=> 'Checkbox',
		'color'		=> 'Color',
		'combo'		=> 'Combo',
		'date'		=> 'Date',
		'datetime'	=> 'DateTime',
		'file'		=> 'File Upload',
		'formula'	=> 'Formula',
		'hidden'	=> 'Hidden',
		'multiselect'=> 'Select Multiple',
		'select'	=> 'Select',
		'password'	=> 'Password',
		'radio'		=> 'Radio',
		'slider'	=> 'Slider',
		'text'		=> 'Text',
		'textarea'	=> 'Textarea',
		'time'		=> 'Time',
		'signature'	=> 'Signature'
	);
	ksort($types);
	if($d){$vals=array_values($types);}
	else{$vals=array_keys($types);}
	return implode("\r\n",$vals);
}
//---------- begin function wasqlGetMasks ----------
/**
* @describe returns a list of valid WaSQL input masks. Used in _fielddata
* @param sort_by_display  boolean
*	if true returns the display values, other wise returns the true values
* @return
*	list of valid WaSQL input masks separated by carriage return. Used in _fielddata
* @usage <?=wasqlGetMasks(true);?>
*/
function wasqlGetMasks($d=0){
	//'alpha,alphanumeric,email,hexcolor,integer,decimal,number,phone,time,ssn,zipcode',
	$masks=array(
		'alpha'			=> 'Alphabetic',
		'alphanumeric'	=> 'AlphaNumeric',
		'email'			=> 'Email Address',
		'hexcolor'		=> 'Hex Color',
		'integer'		=> 'Integer',
		'decimal'		=> 'Decimal',
		'number'		=> 'Number',
		'phone'			=> 'Phone',
		'time'			=> 'Time',
		'ssn'			=> 'SSN',
		'zipcode'		=> 'Zipcode'
	);
	ksort($masks);
	if($d){$vals=array_values($masks);}
	else{$vals=array_keys($masks);}
	return implode("\r\n",$vals);
}
//---------- begin function wasqlGetStates ----------
/**
* @describe returns a list of states . Used in _fielddata
* @param sort_by_display  boolean
*	if true returns the display values, other wise returns the true values (2 char country code)
* @param country string
*	country code. defaults to US
* @return
*	list of states separated by carriage return. Used in _fielddata
* @usage <?=wasqlGetStates(true,'US');?>
*/
function wasqlGetStates($d=0,$country='US'){
	if(isset($_REQUEST['country']) && strlen(trim($_REQUEST['country']))){
    	$country=strtoupper(trim($_REQUEST['country']));
	}
	elseif(isset($_REQUEST['shiptocountry']) && strlen(trim($_REQUEST['shiptocountry']))){
    	$country=strtoupper(trim($_REQUEST['shiptocountry']));
	}
	elseif(isset($_REQUEST['billtocountry']) && strlen(trim($_REQUEST['billtocountry']))){
    	$country=strtoupper(trim($_REQUEST['billtocountry']));
	}
	if($country=='USA'){$country='US';}
	$recopts=array(
		'-table'=>"states",
		'country'=>$country,
		'-order'=>"name,_id",
		'-fields'=>"_id,name,code",
		'-index'=>$d?'name':'code'
	);
	$recs=getDBRecords($recopts);
	if(is_array($recs)){return implode("\r\n",array_keys($recs));}
	return '';
}
//---------- begin function wasqlGetCountries ----------
/**
* @describe returns a list of countries . Used in _fielddata
* @param sort_by_display  boolean
*	if true returns the display values, other wise returns the true values (2 char country code)
* @param pre array
*	an array of country codes to place first in the list for easier access. Defaults to array('US','CA')
* @return
*	list of countries separated by carriage return. Used in _fielddata
* @usage <?=wasqlGetCountries(true);?>
*/
function wasqlGetCountries($d=0,$pre=array('US','CA')){
	//get countries
	$recopts=array(
		'-table'=>'countries',
		'-order'=>'name,_id',
		'-fields'=>'_id,name,code',
		'-index'=>'code'
	);
	$recs=getDBRecords($recopts);
	if(!is_array($recs)){return $recs;}
	//get a list of country codes that exist in the states table - place these first
	$query="select distinct(country) as code from states";
	$codes=getDBRecords(array('-query'=>$query,'-index'=>'code'));
	//build the list - placing countries found in the states table first.

	$vals=$pvals=$rvals=array();
	foreach($recs as $code=>$rec){
		$val=$d?$rec['name']:$rec['code'];
		if(isset($codes[$code])){
        	$pvals[]=$val;
		}
		else{
        	$vals[]=$val;
		}
	}
	if(count($pvals)){$pvals[]='--';}
	$rvals=array_merge($pvals,$vals);
	return implode("\r\n",$rvals);
}
//---------- begin function wasqlGetInstallFiles
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlGetInstallFiles(){
	//exclude: true
	$files=array(
		'/'		=> array(
			'env.pl',
			'exif.pl',
			'sample.htaccess',
			'sample.config.xml',
			'sample.win32_apache.conf',
			'wasqlmail.pl',
			'wget.pl',
			'wpath.pl',
		),
		'php'	=> array(
			'admin.php',
			'config.php',
			'database.php',
			'index.php',
			'minify_css.php',
			'minify_js.php',
			'schema.php',
			'user.php',
			),
		'wfiles/css'	=> array(
			'wasql.css',
			'dropdown.css',
			'tcal.css',
			'dialog.css',
			'codemirror.css'
		),
		'wfiles/js'		=> array(
			'common.js',
			'event.js',
			'form.js',
			'html5.js',
			'tcal.js',
			'sorttable.js'
			),
		'wfiles/js/codemirror'=>'ALL'
	);
	return $files;
}

//---------- begin function wasqlVersion
/**
* @describe - returns WaSQL version as Major.Minor.BuildNumber
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function wasqlVersion(){
	return getFileContents('../version.txt');
	}
	
function wasqlFontIcons($x=''){
	$wfiles_path=getWfilesPath();
	$file="{$wfiles_path}/css/wasql_icons{$x}.css";
	$lines=file($file);
	$icons=array();
	foreach($lines as $line){
    	if(preg_match('/^\.(icon\-.+?)\:/',$line,$m)){
        	$icons[]=$m[1];
		}
	}
	sort($icons);
	return $icons;
}
