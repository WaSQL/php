<?php
/*
	translate - functions to enable you to build  your website in multiple languages easily
	To use these functions simply add the translate tag to  your site as follows:
	<translate:unique_name>
	This will be translated into whatever language the end user in on.
	</translate:unique_name>

	you can automatically translate your text using yandex.  https://tech.yandex.com/translate/ - go here to get your free API key
		to use yandex as your translater add translate_source="yandex" translate_key="{YOUR API KEY}" to config.xml

	References: 
		https://stackoverflow.com/questions/3191664/list-of-all-locales-and-their-short-codes
*/
$progpath=dirname(__FILE__);
//make sure the translations table exists
translateCheckSchema();


//functions
//---------- begin function translateCheckSchema
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGetLocaleInfo($locale){
	global $localesJson;
	$path=getWasqlPath('php/schema');
	$flagspath=getWasqlPath('wfiles/flags');
	if(!is_array($localesJson)){
		$jsontxt=getFileContents("{$path}/locales.json");
		$localesJson=json_decode($jsontxt,true);
		$localesJson=array_change_key_case($localesJson,CASE_LOWER);
	}
	$locale=strtolower(str_replace('_','-',$locale));
	if(!isset($localesJson[$locale])){return array();}
	list($lang,$country)=translateParseLocale($locale);
	$country=strtolower($country);
	$rec=array(
		'locale'=>str_replace('_','-',$locale),
		'name'=>$localesJson[$locale],
		'lang'=>$lang,
		'country'=>$country
	);
	if(file_exists("{$flagspath}/4x3/{$country}.svg")){
		$rec['flag4x3']="/wfiles/flags/4x3/{$country}.svg";
	}
	if(file_exists("{$flagspath}/1x1/{$country}.svg")){
		$rec['flag1x1']="/wfiles/flags/1x1/{$country}.svg";
	}
	//echo $locale.printValue($rec);exit;
	return $rec;
}
//---------- begin function translateCheckSchema
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGetLocales($filters=array()){
	if(!is_array($filters)){
		$filters=preg_split('/\,+/',$filters);
	}
	$path=getWasqlPath('php/schema');
	$flagspath=getWasqlPath('wfiles/flags');
	$jsontxt=getFileContents("{$path}/locales.json");
	$sets=json_decode($jsontxt,true);
	$recs=array();
	foreach($sets as $locale=>$name){
		if(strlen($locale) != 5){continue;}
		$locale=strtolower(str_replace('_','-',$locale));
		list($lang,$country)=translateParseLocale($locale);
		$country=strtolower($country);
		$rec=array(
			'locale'=>$locale,
			'name'=>$name,
			'lang'=>$lang,
			'country'=>$country
		);

		if(file_exists("{$flagspath}/4x3/{$country}.svg")){
			$rec['flag4x3']="/wfiles/flags/4x3/{$country}.svg";
		}
		if(file_exists("{$flagspath}/1x1/{$country}.svg")){
			$rec['flag1x1']="/wfiles/flags/1x1/{$country}.svg";
		}
		$recs[]=$rec;
	}
	return $recs;
}
//---------- begin function translateCheckSchema
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGetLocalesUsed($ibl=0){
	global $CONFIG;
	$locales=translateGetLocales();
	$source_local=translateGetSourceLocale();
	$wherestr='';
	if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
		$wherestr="where source_id={$CONFIG['translate_source_id']}";
	}
	$q=<<<ENDOFQ
		SELECT
			lower(locale) as locale,
			count(*) entry_cnt,
			sum(confirmed) confirmed_cnt
		FROM
			_translations
		{$wherestr}	
		GROUP BY locale
ENDOFQ;
	$recs=getDBRecords(array(
		'-query'=>$q,
		'-index'=>'locale'
	));
	//echo printValue($recs);
	foreach($locales as $i=>$rec){
		$locale=strtolower(str_replace('_','-',$rec['locale']));
		if(!isset($recs[$locale])){
			//echo "skipping locale: {$locale}<br>".PHP_EOL;
			unset($locales[$i]);
			continue;
		}
		foreach($recs[$locale] as $k=>$v){
			if(isset($locales[$i][$k])){continue;}
			$locales[$i][$k]=$v;
		}
	}
	$recs=array();
	foreach($locales as $locale){
		$recs[]=$locale;
	}
	if($ibl==1){
		$lrecs=array();
		foreach($recs as $rec){
			$lrecs[$rec['locale']]=$rec;
		}
		return $lrecs;
	}
	//echo printValue($recs);exit;
	return $recs;
}
//---------- begin function translateCheckSchema
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateCheckSchema(){
	global $CONFIG;
	$table='_translations';
	if(isDBTable($table)){
		return false;
	}
	$fields=array(
		'_id'			=> databasePrimaryKeyFieldString(),
		'_cdate'		=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'		=> databaseDataType('int')." NOT NULL",
		'_edate'		=> databaseDataType('datetime')." NULL",
		'_euser'		=> databaseDataType('int')." NULL",
		'source_id'		=> databaseDataType('int')." NOT NULL Default 0", //source id set by users in CONFIG
		'locale'		=> databaseDataType('varchar(50)')." NOT NULL",
		'translation'	=> databaseDataType('text')." NULL",
		'identifier'	=> databaseDataType('char(40)')." NULL",
		'confirmed'		=> databaseDataType('int')." NOT NULL Default 0",
		'failed'		=> databaseDataType('int')." NOT NULL Default 0",
		);
	$ok = createDBTable($table,$fields,'InnoDB');
	if($ok != 1){
		echo "translateCheckSchema Error: ".printValue($ok);exit;
	}
	//indexes
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"identifier,locale",'-unique'=>1));
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"locale"));
	return true;
}
//---------- begin function translateGetSourceLocale
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGetSourceLocale(){
	global $CONFIG;
	//determine source locale
	if(isset($CONFIG['translate_locale']) && strlen($CONFIG['translate_locale'])){
		$source_locale=$CONFIG['translate_locale'];
	}
	else{$source_locale='en-us';}
	return $source_locale;
}
function translateMapText($text){
	preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($text), $m);
	$map=array(
		'text'=>$text,
		'identifier'=>'',
		'map'=>array(),
		'maptext'=>$text,
	);
	$map['map'][]="\r\n";
	$map['map'][]="\n";
	$map['map'][]="\t";
	$map['maptext']=str_replace("\r\n",'{0}',$map['maptext']);
	$map['maptext']=str_replace("\n",'{1}',$map['maptext']);
	$map['maptext']=str_replace("\t",'{2}',$map['maptext']);
	foreach($m[0] as $i=>$tag){
		if(!in_array($tag,$map['map'])){
			$map['map'][]=$tag;
		}
		$mapindex=array_search($tag,$map['map']);
		$map['maptext']=str_replace($tag,'{'.$mapindex.'}',$map['maptext']);
	}
	$map['identifier']=sha1(trim($map['maptext']));
	return $map;
}
function translateUnmapText($source,$target){
	$map=translateMapText($source);
	foreach($map['map'] as $i=>$tag){
		$mtag='{'.$i.'}';
		$target=str_replace($mtag,$tag,$target);
		$mtag='{ '.$i.'}';
		$target=str_replace($mtag,$tag,$target);
		$mtag='{'.$i.' }';
		$target=str_replace($mtag,$tag,$target);
	}
	return $target;
}
//---------- begin function translateText
/**
// http://www.lingoes.net/en/translator/langcode.htm
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateText($text,$locale=''){
	global $CONFIG;
	$map=translateMapText($text);
	if(!isset($map['identifier'])){return $text;}
	//set the identifier as the sha of the text
	$identifier=$map['identifier'];
	//determine source locale
	$source_locale=translateGetSourceLocale();
	list($source_lang,$source_country)=translateParseLocale($source_locale);
	//default locale if not passed in.  
	if(!strlen($locale)){
		if(isset($_SESSION['REMOTE_LANG']) && strlen($_SESSION['REMOTE_LANG'])){$locale=$_SESSION['REMOTE_LANG'];}
		else{$locale=$_SERVER['REMOTE_LANG'];}
	}
	if(strlen($locale)!=5){return $text;}
	if(!strlen($locale)){
		$locale=$source_locale;
	}
	global $translateTextCache;
	$locale=strtolower($locale);
	if(isset($translateTextCache[$locale][$identifier])){return $translateTextCache[$locale][$identifier];}
	$topts=array(
		'-table'	=> '_translations',
		'-where'	=> "locale ='{$locale}'",
		'-fields'	=> 'locale,identifier,translation'
	);
	if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
		$topts['-where'].=" and source_id={$CONFIG['translate_source_id']}";
	}
	$trecs=getDBRecords($topts);
	foreach($trecs as $rec){
		$rec['locale']=strtolower($rec['locale']);
		$rec['identifier']=strtolower($rec['identifier']);
		$translateTextCache[$rec['locale']][$rec['identifier']]=$rec['translation'];
	}
	if(isset($translateTextCache[$locale][$identifier])){return $translateTextCache[$locale][$identifier];}
	global $CONFIG;
	list($target_lang,$target_country)=translateParseLocale($locale);
	$translation=$text;
	//replace HTML TAGS with {1}

	if($target_lang != $source_lang && isset($CONFIG['translate_source'])){
		if(!isset($CONFIG['translate_key'])){
			debugValue('Missing Translate Key in config');
			return $text;
		}
		switch(strtolower($CONFIG['translate_source'])){
			case 'yandex':
				$translation=translateYandex($translation,$source_lang,$target_lang);
			break;
			case 'google':
				$translation=translateGoogle($translation,$source_lang,$target_lang);
			break;
		}
	}
	$map=translateMapText($translation);

	$taddopts=array(
		'-table'		=> '_translations',
		'-ignore'		=> 1,
		'locale'		=> $locale,
		'identifier'	=> $map['identifier'],
		'translation'	=> $translation
	);
	if($source_locale == $locale){
		$taddopts['confirmed']=1;
	}
	else{
		$taddopts['confirmed']=0;
	}
	if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
		$taddopts['source_id']=$CONFIG['translate_source_id'];
	}
	//echo $source_locale.printValue($taddopts);exit;
	$tid=addDBRecord($taddopts);

	if($target_lang != $source_lang){
		$taddopts['locale']=$source_locale;
		$taddopts['translation']=$text;
		$tid=addDBRecord($taddopts);
	}
	//echo "{$target_lang} != {$source_lang}<br>{$translation}<br>{$text}".printValue($addopts);exit;
	//$id=addDBRecord($addopts);
	return $translation;
}
//---------- begin function translateYandex
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGoogle($text,$source_lang,$target_lang){
	global $CONFIG;
	if(!isset($CONFIG['translate_key'])){
		debugValue('Missing Google Translate Key in config');
		return $text;
	}
	$json=<<<ENDOFJSON
{
  'q': '{$text}',
  'target': '{$target_lang}',
  'source': '{$source_lang}'
}
ENDOFJSON;
	$post=postJSON($url,$json);
	echo printValue($post);exit;

    $url = "https://www.googleapis.com/language/translate/v2?key={$CONFIG['translate_key']}&q=".rawurlencode($text)."&source={$source_lang}&target={$target_lang}";
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($handle);
    //echo printValue($response);
    $responseDecoded = json_decode($response, true);
    curl_close($handle);
	return $responseDecoded['data']['translations'][0]['translatedText'];
}
//---------- begin function translateYandex
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateYandex($text,$source_lang,$target_lang){
	global $CONFIG;
	if(!isset($CONFIG['translate_key'])){
		debugValue('Missing Yandex Translate Key in config');
		return $text;
	}
	$url='https://translate.yandex.net/api/v1.5/tr.json/translate';
	$xurl=$url;
	$opts=array(
		'key'		=> $CONFIG['translate_key'],
		'lang'		=> "{$source_lang}-{$target_lang}",
		'format'	=> 'plain',
		'text'		=> encodeURL($text),
		'-method'	=> 'GET',
		'-json'		=> 1,
		'-nossl'	=> 1,
		'-follow'	=> 1
	);
	$post=postURL($url,$opts);
	//echo $post['body'];exit;
	if(isset($post['json_array']['text'][0])){
		return decodeURL($post['json_array']['text'][0]);
	}
	elseif(isset($post['json_array']['text'])){
		return decodeURL($post['json_array']['text']);
	}
	return $text;
}
//---------- begin function translateParseLocale
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateParseLocale($locale){
	list($lang,$country)=preg_split('/[\_\-]/',strtolower($locale));
	switch($country){
		case 'chs':
		case 'cht':
			$country='cn';
		break;
		default:
			$country=translateGetCountryCode2($country);
		break;
	}
	return array($lang,$country);
}
//---------- begin function translateGetCountryCode2
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGetCountryCode2($code3){
	$rec=getDBRecord(array(
		'-table'=>'countries',
		'-where'=>"code3='{$code3}' or code = '{$code3}'",
		'-fields'=>'_id,code'
	));
	if(isset($rec['code'])){return $rec['code'];}
	return substr($code3,0,2);
}
?>