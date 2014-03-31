<?php
/*filetype:php*/
/*
	Web Service Functions (ws)
		Generate WSDL and build a true web services into wasql
			http://wso2.org/downloads/wsf/php
*/
function wsAcronyms($word=''){
	//info:searches Acronyms web service for words that contain $word
	return wsAonawarePostURL($word,array('vera'));
	}
function wsAonawarePostURL($word,$dict_ids=array()){
	//info:searches aonaware web service for words that contain $word in dictionaries specified by $dict_ids
	$url="http://services.aonaware.com/DictService/DictService.asmx/Define";
	if(count($dict_ids)==1){
		$url="http://services.aonaware.com/DictService/DictService.asmx/DefineInDict";
    	}
	$cnt=0;
	try{
		$params=array('word'=>$word);
		if(count($dict_ids)==1){
			$params['dictId']=$dict_ids[0];
        	}
		$rss=postURL($url,$params);
		$xml=readXML($rss['body']);
		$results=array();
		if(isset($xml->Definitions->Definition)){
			foreach($xml->Definitions->Definition as $define){
				if(!isset($define->WordDefinition)){continue;}
				//each item has a source, word, and definition
				$dict_id=(string)$define->Dictionary->Id;
				//skip dictonaries not specified
				if(!in_array($dict_id,$dict_ids)){continue;}
				$result=array();
				//$result['example']="DictID:{$dict_id}";
				$result['title']=(string)$define->Dictionary->Name;
				$result['answer'] = removeHtml(strip($define->WordDefinition));
				$result['answer']=fixMicrosoft($result['answer']);
		        $result['answer']=str_replace('&#8220;','',$result['answer']);
				$result['answer']=str_replace('&#8221;','',$result['answer']);
				$result['answer']=preg_replace('/[\\r\\n\\t]+/','',$result['answer']);
				$result['answer']=preg_replace('/\s+/',' ',$result['answer']);
				$result['answer']=str_replace('[1913 Webster]','',trim($result['answer']));
		        array_push($results,$result);
				}
			}
		return $results;
		}
	catch (Exception $e){
		return array('error'=>$e);
    	}
	}
function wsBibleDictionary($word=''){
	//info:searches Bible dictionaries web service for items that contain $word
	$results = wsAonawarePostURL($word,array('devils','hitchcock','easton'));
	//Search LDS.org Bible Dictionary.
	$firstletter=strtolower(substr($word,0,1));
	$url="http://scriptures.lds.org/en/bd/$firstletter/contents";
	$result=getURL($url);
	$expr='/<a href=\\\'bd\/'.$firstletter.'\/([0-9]+?)\\\'><name>'.$word.'/i';
	if(is_array($result) && preg_match($expr,$result['body'],$match)){
		$num=$match[1];
		$url="http://scriptures.lds.org/en/bd/$firstletter/$num";
		$result=getURL($url);
		$expr='/<div class=\"paragraph\">(.+?)<\\/div>/is';
	    if(is_array($result) && preg_match($expr,$result['body'],$match)){
			$define=removeHtml(strip($match[1]));
			$define=fixMicrosoft($define);
			$define=preg_replace('/[\\r\\n]+/','',$define);
			$define=str_replace('“','',$define);
			$define=str_replace('”','',$define);
			$define=str_replace(': ',':',$define);
			array_push($results,array('title'=>"The LDS Bible Dictionary",'description'=>$define));
			}
		}
	return $results;
	}
function wsComputing($word=''){
	//info:searches Computing dictionaries web service for items that contain $word
	return wsAonawarePostURL($word,array('foldoc'));
	}
function wsDictionary($word=''){
	//info:searches Dictionaries web service for items that contain $word
	return wsAonawarePostURL($word,array('gcide','wn'));
	}
function wsDomainSearch($name=''){
	//info: searches to see if $name is available for .com, .net, or .org
	$name=trim($name);
	if(!strlen($name)){return "No name";}
	$results=array();
	$url="http://instantdomainsearch.com/services/quick/";
	//#http://instantdomainsearch.com/services/rest/?name=safe
	//return: {'name':'safe','com':'u','net':'u','org':'u'}
	$post=postURL($url,array('name'=>strtolower($name),'-method'=>"GET"));
	if(is_array($post)){
		//$results['raw']=$post['body'];
		$json=json_decode((string)$post['body']);
		$map=array(
			'u'=>'taken',
			'a'=>'available'
			);
		foreach($json as $key=>$val){
			$val=(string)$val;
			if(isset($map[$val])){$val=$map[$val];}
			$results[(string)$key]=$val;
        	}
        return $results;
        }
    else{
		$results['name']="DomainSearch service is currently unavailable";
    	}
	return $results;
	}
function wsElements($word=''){
	//info:searches Elements web service for items that contain $word
	return wsAonawarePostURL($word,array('elements'));
	}
function wsGazette($word=''){
	//info:searches Gazette web service for items that contain $word
	return wsAonawarePostURL($word,array('gazetteer','gaz-county','gaz-place','gaz-zip'));
	}
function wsJargon($word=''){
	//info:searches Jargon web service for items that contain $word
	return wsAonawarePostURL($word,array('jargon'));
	}
function wsLanguageDictionary($word='',$lang='eng-spa'){
	return wsAonawarePostURL($word,array($lang));
	}
function wsLawDictionary($word=''){
	//info:searches Law Dictionary web service for items that contain $word
	return wsAonawarePostURL($word,array('bouvier'));
	}
function wsMusic($word='',$si=1){
	//info:searches mldb web site for music that contain $word
	$url='http://www.mldb.org/search?mq='.encodeURL($word).'&si='.$si.'&mm=2&ob=1';
	$result=getURL($url);
	if(is_array($result) && preg_match('/<table id="thelist" cellspacing="0">(.+?)<\\/table>/i',$result['body'],$match)){
		$thelist=$match[1];
		preg_match_all('/artist-(.+?)>(.+?)<(.+?)song-(.+?)>(.+?)</i',$thelist,$matches);
		if(is_array($matches)){
			//echo printValue($matches);
			$results=array();
			for($m=0;$m<count($matches[2]);$m++){
				$result = array(
					'title'			=> fixMicrosoft($matches[5][$m]),
					'answer'	=> ' by '.fixMicrosoft($matches[2][$m])
					);
				//get the lyrics - <p class="songtext" lang="EN">
				$result['info'] ="artist at http://www.mldb.org/song-" . preg_replace('/[\"\']+$/','',$matches[1][$m]) . "<br />";
				$result['info'].=" -- lyrics at http://www.mldb.org/song-" . preg_replace('/[\"\']+$/','',$matches[4][$m]) . "<br />";
				//$data=getURL($url);
				//echo printValue($data);
				//if(is_array($data) && preg_match('/<p class="songtext" lang="EN">(.+?)<\\/p>/i',$data['body'],$match)){
				//	$result['info']=$match[1];
                //	}
                array_push($results,$result);
            	}
        	}
		return $results;
		}
	}
function wsPHPDocumentation($word='',$html=0,$dir=''){
	if(!strlen($dir)){
		$dir=wsGetPHPDocPath();
		}
	if(!file_exists($dir)){return "No PHP html dir found: {$dir}";}
	$section='function';
	//info:searches PHP Functions for $word and returns syntax
	$matches=array();
	$list=array();
	if ($handle = opendir($dir)) {
    	$files=array();
    	while (false !== ($file = readdir($handle))) {
			if($file == '.' || $file == '..'){continue;}
			if(!stringBeginsWith($file,$section)){continue;}
			$mword=preg_replace('/[\-\_]+/','-',$word);
			if(preg_match('/^'.$section.'\.(.+)$/is',$file,$fmatch) && preg_match('/'.$mword.'/i',$fmatch[1])){
				//function match = get the page content
				$function=preg_replace('/\.html$/i','',$fmatch[1]);
				$function=preg_replace('/\-/i','_',$function);
				if(strtolower($word)==strtolower($function)){
					//exact match found - return syntax
					$content=getFileContents("{$dir}/$file");
					if($html==1 && preg_match('/<body>(.+?)<\/body>/is',$content,$smatch)){
						//return the html page instead of trying to parse it
						$html=$smatch[1];
						$html=preg_replace('/<div class="(prev|next|up|down|home)"(.+?)<\/div>/is','',$html);
						$html=preg_replace('/<a (.+?)>(.+?)<\/a>(\.*)/is','',$html);
						return $html;
						}
					$matches['name']=$function;
					//parse the page too build the syntax: title, description
					if(preg_match('/<title>(.+?)<\/title>/is',$content,$smatch)){$matches['syntax'][]=$smatch[1];unset($smatch);}
					if(preg_match('/<h3 class="title">Description<\/h3>(.+?)<h3 class="title">/is',$content,$smatch)){
						$val=$smatch[1];
						$val=preg_replace('/<\/(tr|p|dd)>/i','[newline]',$val);
						//$val=preg_replace('/<div>(.+?)</div>/i',removeHtml(${1}),$val);
						$val=trim(removeHtml($val));
						$val=preg_replace('/[\r\n]\s+/i',' ',$val);
						$val=preg_replace('/\s+\)\s+/i',")\n",$val);
						$val=preg_replace('/\s+\(\s+/i','(',$val);
						$val=preg_replace('/\s+\[\s+/i','[',$val);
						$val=preg_replace('/\s+\]\s+/i',']',$val);
						$val=preg_replace('/\[newline\]/i',"\n",$val);
						$parts=preg_split('/[\r\n]+/s',$val);
						foreach($parts as $part){
							if(strlen(trim($part)) && trim($part) != '.'){$matches['syntax'][]=trim($part);}
                        	}
						unset($smatch);
						}
					//parameters
					if(preg_match('/<h3 class="title">Parameters<\/h3>(.+?)<h3 class="title">/is',$content,$smatch)){
						$val=$smatch[1];
						$val=preg_replace('/<\/(tr|p|dd)>/i','[newline]',$val);
						$val=preg_replace('/<td align="left"><i>(.+?)<\/i><\/td>/i','*${1}*',$val);
						$val=trim(removeHtml($val));
						$val=preg_replace('/[\r\n]\s+/i',' ',$val);
						$val=preg_replace('/\[newline\]/i',"\n",$val);
						$val=preg_replace('/\*(.+?)\*/i','<b>${1}</b>',$val);
						$parts=preg_split('/[\r\n]+/s',$val);
						foreach($parts as $part){
							if(strlen(trim($part)) && trim($part) != '.'){$matches['syntax'][]=trim($part);}
							}
						unset($smatch);
						}
					}
				else{$list[]=$function;}
            	}
    		}
    	closedir($handle);
    	if(count($list)){
    		sort($list);
    		$matches['matches']=$list;
			}
    	return $matches;
		}
	return wsAonawarePostURL($word,array('moby-thes'));
	}
//--------------------
function wsGetPHPDocPath(){
	//info: returns the path to the PHP documentation files, or tries to..
	$progpath=dirname(__FILE__);
	$parts=preg_split('/[\\\\\\/]+/s',trim($progpath));
	if(is_array($parts)){
		//echo printValue($parts);
		$paths=array();
		foreach($parts as $part){
			array_push($paths,$part);
			$path=implode('/',$paths);
			//echo "path: {$path}<br>\n";
			if(file_exists("{$path}/html/function.date.html")){
				return "{$path}/html";
				}
			if(file_exists("{$path}/shared/php/function.date.html")){
				return "{$path}/shared/php";
				}
			if(file_exists("{$path}/php/function.date.html")){
				return "{$path}/php";
				}
			if(file_exists("{$path}/phpdocs/function.date.html")){
				return "{$path}/phpdocs";
				}
	    	}
		}
	$ini_path=(string)php_ini_loaded_file();
	//echo "ini_path: {$ini_path}<br>\n";
	if(strlen($ini_path)){
		$parts=preg_split('/[\\\\\\/]+/s',trim((string)$ini_path));
		if(is_array($parts)){
			//echo printValue($parts);
			$paths=array();
			foreach($parts as $part){
				array_push($paths,$part);
				$path=implode('/',$paths);
				//echo "path: {$path}<br>\n";
				if(file_exists("{$path}/html/function.date.html")){
					return "{$path}/html";
					}
		    	}
			}
		}
    return null;
	}
function wsThesaurus($word=''){
	//info:searches Thesaurus web service for items that contain $word
	return wsAonawarePostURL($word,array('moby-thes'));
	}
?>