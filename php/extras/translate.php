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
	$locale=strtolower(str_replace('-','_',$locale));
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
function translateGetLocalesUsed(){
	$locales=translateGetLocales();
	$source_local=translateGetSourceLocale();
	$q=<<<ENDOFQ
		SELECT
			lower(locale) as locale,
			count(*) entry_cnt,
			sum(confirmed) confirmed_cnt
		FROM
			_translations
		WHERE
			locale != '{$source_local}'	
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
		't_id'			=> databaseDataType('int')." NOT NULL Default 0", //template id
		'p_id'			=> databaseDataType('int')." NOT NULL Default 0", //page id
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
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"t_id,p_id,locale"));
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
//---------- begin function translateText
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateText($text,$locale=''){
	global $PAGE;
	global $TEMPLATE;
	global $CONFIG;
	//set the identifier as the sha of the text
	$identifier=sha1(trim($text));
	//determine source locale
	$source_locale=translateGetSourceLocale();
	list($source_lang,$source_country)=translateParseLocale($source_locale);
	//default locale if not passed in.  
	if(!strlen($locale)){
		if(isset($_SESSION['locale']) && strlen($_SESSION['locale'])){$locale=$_SESSION['locale'];}
		elseif(isset($_SESSION['REMOTE_LANG']) && strlen($_SESSION['REMOTE_LANG'])){$locale=$_SESSION['REMOTE_LANG'];}
		elseif(isset($_SERVER['locale']) && strlen($_SERVER['locale'])){$locale=$_SERVER['locale'];}
		else{$locale=$_SERVER['REMOTE_LANG'];}
	}
	if(strlen($locale)!=5){return $text;}
	if(!strlen($locale)){
		$locale=$source_locale;
	}
	global $translateTextCache;
	$locale=strtolower($locale);
	if(isset($translateTextCache[$locale][$identifier])){return $translateTextCache[$locale][$identifier];}
	$opts=array(
		'-table'	=> '_translations',
		'-where'	=> "locale ='{$locale}' and p_id in (0,{$PAGE['_id']}) and t_id in (0,{$TEMPLATE['_id']})"
	);
	$recs=getDBRecords($opts);
	foreach($recs as $rec){
		$rec['locale']=strtolower($rec['locale']);
		$rec['identifier']=strtolower($rec['identifier']);
		$translateTextCache[$rec['locale']][$rec['identifier']]=$rec['translation'];
	}
	if(isset($translateTextCache[$locale][$identifier])){return $translateTextCache[$locale][$identifier];}
	global $CONFIG;
	list($target_lang,$target_country)=translateParseLocale($locale);
	$translation=$text;
	if($target_lang != $source_lang && isset($CONFIG['translate_source'])){
		if(!isset($CONFIG['translate_key'])){
			debugValue('Missing Translate Key in config');
			return $text;
		}
		switch(strtolower($CONFIG['translate_source'])){
			case 'yandex':
				$translation=translateYandex($translation,$source_lang,$target_lang);
			break;
		}
	}
	$addopts=array(
		'-table'		=> '_translations',
		'-ignore'		=> 1,
		'locale'		=> $locale,
		'identifier'	=> $identifier,
		't_id'			=> $TEMPLATE['_id'],
		'p_id'			=> $PAGE['_id'],
		'translation'	=> $translation
	);
	$id=addDBRecord($addopts);
	if($target_lang != $source_lang){
		$addopts['locale']=$source_locale;
		$addopts['translation']=$text;
		$id=addDBRecord($addopts);
	}
	//echo "{$target_lang} != {$source_lang}<br>{$translation}<br>{$text}".printValue($addopts);exit;
	$id=addDBRecord($addopts);
	return $translation;
}
//---------- begin function translateYandex
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateYandex($text,$source_lang,$target_lang){
	global $CONFIG;
	if(!isset($CONFIG['translate_key'])){
		debugValue('Missing Translate Key in config');
		return;
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