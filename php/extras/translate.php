<?php
/*
	translate - functions to enable you to build  your website in multiple languages easily
	To use these functions simply add the translate tag to  your site as follows:
	<translate>This will be translated into whatever language the end user in on.</translate>

	To manage the translations use the translate manager in the backend of WaSQL:
		http://localhost/php/admin.php?_menu=translate
		
	you can automatically translate your text using yandex.  https://tech.yandex.com/translate/ - go here to get your free API key
		to use yandex as your translater add 

			translate_source="yandex" 
			translate_key="{YOUR API KEY}" 

		to config.xml

	References: 
		https://stackoverflow.com/questions/3191664/list-of-all-locales-and-their-short-codes
*/
$progpath=dirname(__FILE__);
//make sure the translations table exists
translateCheckSchema();

/**
 * Returns translated month names for January through December
 * 
 * Uses PHP's IntlDateFormatter to provide localized month names in the specified
 * locale. The locale should be in the format 'en-US', 'es-ES', 'fr-FR', etc.
 * 
 * @param string $format The format type: 'short' (Jan, Feb) or 'long' (January, February)
 * @param string $locale The locale code (e.g., 'en-US', 'es-ES', 'fr-FR')
 * @return array An array of 12 translated month names starting with January
 * 
 * @example
 * $months = translateMonthNames('es-ES', 'long');
 * // Returns: ['enero', 'febrero', 'marzo', ...]
 */
function translateMonthNames($format = 'long',$locale='') {
		$locale = commonCoalesce($locale, $_SESSION['REMOTE_LANG'], $_REQUEST['REMOTE_LANG'], 'en-US');
    // Convert en-US to en_US format for IntlDateFormatter
    $locale = str_replace('-', '_', $locale);
    
    // Determine the pattern based on format
    switch(strtolower($format)){
    	case 'one':
    	case 'one-letter':
    		$pattern = 'MMMMM'; // one letter
    	break;
    	case 'three':
    	case 'short':
    	case 'medium':
    		$pattern = 'MMM'; // Three letter abbreviation
    	break;
    	default:
    		$pattern = 'MMMM'; // default to long
    	break;
    }
    // Create formatter
    $formatter = new IntlDateFormatter(
        $locale,
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        null,
        null,
        $pattern
    );
    //fail if formatter is invalid
    if (!$formatter) {return array();}
    $months = array();
    
    // Loop through 12 months
    for ($i = 1; $i <= 12; $i++) {
        // Create a date for each month (using the 1st day)
        $date = mktime(0, 0, 0, $i, 1, 2024);
        $months[] = $formatter->format($date);
    }
    
    return $months;
}

/**
 * Returns translated day names for Sunday through Saturday
 * 
 * Uses PHP's IntlDateFormatter to provide localized day names in the specified
 * locale. The locale should be in the format 'en-US', 'es-ES', 'fr-FR', etc.
 * 
 * @param string $format The format type: 'short' (S, M, T), 'med' (Sun, Mon, Tue), or 'long' (Sunday, Monday, Tuesday)
 * @param string $locale The locale code (e.g., 'en-US', 'es-ES', 'fr-FR')
 * @return array An array of 7 translated day names starting with Sunday
 * 
 * @example
 * $days = translateDayNames('fr-FR', 'long');
 * // Returns: ['dimanche', 'lundi', 'mardi', ...]
 */
function translateDayNames($format = 'long',$locale='') {
		$locale = commonCoalesce($locale, $_SESSION['REMOTE_LANG'], $_REQUEST['REMOTE_LANG'], 'en-US');
    // Convert en-US to en_US format for IntlDateFormatter
    $locale = str_replace('-', '_', $locale);
    
    // Determine the pattern based on format
    switch(strtolower($format)){
    	case 'short':
    	case 'one':
    	case 'one-letter':
    		$pattern = 'EEEEE'; // one letter
    	break;
    	case 'two':
    	case 'two-letter':
    		$pattern = 'EE'; // two letter
    	break;
    	case 'three':
    	case 'med':
    	case 'medium':
    		$pattern = 'EEE'; // Three letter abbreviation
    	break;
    	default:
    		$pattern = 'EEEE'; // default to long
    	break;
    }
    // Create formatter
    $formatter = new IntlDateFormatter(
        $locale,
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        null,
        null,
        $pattern
    );
    //fail if formatter is invalid
    if (!$formatter) {return array();}
    $days = array();
    // Loop through 7 days starting from Sunday
    $baseDate = mktime(0, 0, 0, 1, 7, 2024); // January 7, 2024 is a Sunday
		for ($i = 0; $i < 7; $i++) {
		    $date = strtotime("+$i days", $baseDate);
		    $days[] = $formatter->format($date);
		}
    return $days;
}

//functions
//---------- begin function translateCheckSchema
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGetLocaleInfo($locale){
	$locale=preg_replace('/\?.+$/','',$locale);
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
	if(is_file("{$flagspath}/4x3/{$country}.svg")){
		$rec['flag4x3']="/wfiles/flags/4x3/{$country}.svg";
	}
	if(is_file("{$flagspath}/1x1/{$country}.svg")){
		$rec['flag1x1']="/wfiles/flags/1x1/{$country}.svg";
	}
	//echo $locale.printValue($rec);exit;
	return $rec;
}
//---------- begin function translateCheckSchema
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGetLocales($params=array()){
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

		if(is_file("{$flagspath}/4x3/{$country}.svg")){
			$rec['flag4x3']="/wfiles/flags/4x3/{$country}.svg";
		}
		if(is_file("{$flagspath}/1x1/{$country}.svg")){
			$rec['flag1x1']="/wfiles/flags/1x1/{$country}.svg";
		}
		if(isset($params['-index']) && isset($rec[$params['-index']])){
			$recs[$rec[$params['-index']]]=$rec;
		}
		else{$recs[]=$rec;}
	}
	return $recs;
}
//---------- begin function translateCheckSchema
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGetLocalesUsed($ibl=0,$wasql=0){
	global $CONFIG;
	$locales=translateGetLocales();
	$source_local=translateGetSourceLocale();
	$wherestr="where wasql={$wasql}";
	if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
		$wherestr.=" and source_id={$CONFIG['translate_source_id']}";
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
	//echo $q.printValue($recs);exit;
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
	//no need to check every hit.. only once per session
	if(isset($_SESSION['translateCheckSchema'])){return;}
	$_SESSION['translateCheckSchema']=time();
	global $CONFIG;
	$table='_translations';
	if(isDBTable($table)){
		$fields=getDBFields($table);
		if(!in_array('wasql',$fields)){
			$ok=executeSQL("alter table {$table} add wasql integer NOT NULL Default 0");
		}
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
		'wasql'		=> databaseDataType('int')." NOT NULL Default 0",
		);
	$ok = createDBTable($table,$fields,'InnoDB');
	if($ok != 1){
		$ok=commonLogMessage('translate',"ERROR - createDBTable".printValue($ok));
		return;
	}
	$ok=commonLogMessage('translate',"created {$table}");
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
//---------- begin function translateMapText
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateMapText($text){
	$map=array(
		'text'=>$text,
		'identifier'=>sha1(trim($text)),
		'map'=>array(),
		'maptext'=>$text,
	);
	return $map;
	preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($text), $m);
	

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
	if(stringContains($text,'<img')){
		$map['identifier']=sha1(trim($text));	
	}
	else{
		$map['identifier']=sha1(trim($map['maptext']));
	}
	return $map;
}
//---------- begin function translateUnmapText
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
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
/**
 * Replace <translate>...</translate> tags in $html using the translations table.
 *
 * - translations.identifier = sha1(text inside tags)
 * - Prefers confirmed=1 (ties broken by lowest failed), in ONE batched query
 *   using MySQL 8 CTE + JSON_TABLE + ROW_NUMBER().
 * - Fast within the request via a static memory cache (no APCu required).
 * - Auto-creates missing translations with source locale and target locale
 *
 * @param string $html
 * @param string $locale e.g. 'es-mx'
 * @return string translated HTML
 */
function translateHTML($html, $locale=''){
	if(!stringContains((string)$html,'<translate>')){return (string)$html;}
	// set locale if not specified
	if(!strlen($locale)){
		$locale = $_SESSION['REMOTE_LANG'] = commonCoalesce($_SESSION['REMOTE_LANG'], $_REQUEST['REMOTE_LANG'], 'en-us');
	}
	global $CONFIG;
	if(!isset($CONFIG['database'])){return (string)$html;}
	// Find all <translate>…</translate> occurrences (works in text or attributes)
	if (!preg_match_all('#<translate>(.*?)</translate>#is', $html, $matches, PREG_SET_ORDER)) {
		return $html;
	}
	//echo $locale.printValue($matches);exit;
	// Unique identifiers for batch lookup
	$phrasesById = [];   // id => original text
	foreach ($matches as $m) {
		$raw = trim($m[1]);
		if ($raw === '') { continue; }
		$phrasesById[sha1($raw)] = $raw;
	}
	if (!$phrasesById) {
		return $html;
	}
	// Per-request cache: [locale][id] => translation|null
	static $memoryCache = [];
	// Which IDs we still need to fetch from DB (not seen in cache)
	$need = [];
	foreach ($phrasesById as $id => $_txt) {
		if (!isset($memoryCache[$locale][$id])) {
			$need[$id] = $_txt;
		}
	}
	// Nothing to look up
	if (!$need) {
		return preg_replace_callback(
			'#<translate>(.*?)</translate>#is',
			function ($m) use ($locale, $phrasesById, $memoryCache) {
				$original = trim($m[1]);
				if ($original === '') { return ''; }
				$id = sha1($original);
				return array_key_exists($id, (array)$memoryCache[$locale] ?? [])
					? $memoryCache[$locale][$id]
					: $original;
			},
			$html
		);
	}
	// Determine server version (8+ supports CTE/JSON_TABLE; 5.7 does not)
	$verRes = dbQueryResults($CONFIG['database'], "SELECT VERSION() AS value", []);
	$verStr = isset($verRes[0]['value']) ? (string)$verRes[0]['value'] : '5.7.0';
	list($major) = array_map('intval', explode('.', $verStr, 2));
	$supports_json_table = ($major >= 8);

	// Initialize cache container for locale
	if (!isset($memoryCache[$locale])) {
		$memoryCache[$locale] = [];
	}
	if ($supports_json_table) {
		// Fast path for MySQL 8+: single round-trip using CTE + JSON_TABLE
		$jsonIds = json_encode(array_keys($need), JSON_UNESCAPED_UNICODE);
		$query = <<<SQL
WITH ids AS (
  SELECT jt.id
  FROM JSON_TABLE(?, '$[*]' COLUMNS (id VARCHAR(40) PATH '$')) AS jt
)
SELECT t.identifier, t.translation
FROM _translations t
INNER JOIN ids i ON i.id = t.identifier
WHERE t.locale = ? AND t.confirmed = 1 AND t.wasql = 0
SQL;
		$params = array('-values' => array($jsonIds, $locale));
		try{
			$recs = dbQueryResults($CONFIG['database'], $query, $params);
			//echo $query.printValue($recs).$jsonIds;exit;
			foreach (($recs ?: []) as $rec) {
				$memoryCache[$locale][$rec['identifier']] = $rec['translation'];
			}
		}
		catch(Exception $e){
			// If anything about JSON_TABLE/CTE fails unexpectedly, fall back to 5.7 logic
			$supports_json_table = false;
		}
	}
	if (!$supports_json_table) {
		// Portable path for MySQL 5.7: chunked IN (…) lookups (no CTE/JSON_TABLE)
		$ids = array_keys($need);
		$chunkSize = 500; // safe default
		for ($i = 0; $i < count($ids); $i += $chunkSize) {
			$chunk = array_slice($ids, $i, $chunkSize);
			$ph = implode(',', array_fill(0, count($chunk), '?'));
			$query = "
				SELECT t.identifier, t.translation
				FROM _translations t
				WHERE t.locale = ? AND t.confirmed = 1 AND t.wasql = 0
				  AND t.identifier IN ($ph)
			";
			$params = array('-values' => array_merge([$locale], $chunk));
			$recs = dbQueryResults($CONFIG['database'], $query, $params);
			foreach (($recs ?: []) as $rec) {
				$memoryCache[$locale][$rec['identifier']] = $rec['translation'];
			}
		}
	}
	// Identify missing translations and auto-create them
	$missing = [];
	foreach ($need as $id => $txt) {
		if (!array_key_exists($id, $memoryCache[$locale])) {
			$missing[$id] = $txt;
		}
	}
	if (!empty($missing)) {
		$sourceLang = commonCoalesce($_SERVER['REMOTE_LANG'],'en-us');
		//check to see if the source is there
		$ids=array_keys($missing);
		$idstr=implode("','",$ids);
		$sourcerecs=getDBRecords(array(
			'-table'=>'_translations',
			'-fields'=>'_id,identifier,locale',
			'-index'=>'identifier',
			'-where'=>"locale='{$sourceLang}' AND identifier IN ('{$idstr}')"
		));
		//echo printValue($missing).printValue($sourcerecs);exit;
		foreach ($missing as $id => $txt) {
			try {
				// Step 1: Insert source locale record (if not already there)
				if (!isset($sourcerecs[$id])) {
					// Insert source record
					$sourceId=addDBRecord(array(
							'-table'=>'_translations',
							'locale'=>$sourceLang,
							'identifier'=>$id,
							'translation'=>$txt,
							'source_id'=>0,
							'confirmed'=>1
					));
				} 
				else {
					$sourceId = $sourcerecs[$id]['_id'];
				}
				// Step 2: Insert target locale record with reference to source
				$newid=addDBRecord(array(
							'-table'=>'_translations',
							'locale'=>$locale,
							'identifier'=>$id,
							'translation'=>$txt,
							'source_id'=>$sourceId,
							'-upsert'=>'ignore'
					));
				// Cache the translation (using original text as fallback)
				$memoryCache[$locale][$id] = $txt;
			} catch (Exception $e) {
				// Silently fail and use original text
				$memoryCache[$locale][$id] = $txt;
			}
		}
	}
	// Replace tags using cached resolutions
	$out = preg_replace_callback(
		'#<translate>(.*?)</translate>#is',
		function ($m) use ($locale, $phrasesById, $memoryCache) {
			$original = trim($m[1]);
			if ($original === '') { return ''; }
			$id = sha1($original);
			// Use translation if present (allow empty-string translations)
			return array_key_exists($id, (array)$memoryCache[$locale] ?? [])
				? $memoryCache[$locale][$id]
				: $original;
		},
		$html
	);
	return $out;
}
//---------- begin function translateTextOLD
/**
// http://www.lingoes.net/en/translator/langcode.htm
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateText($text,$locale='',$wasql=0){
	if(!strlen(trim($text))){return $text;}
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
	if(isset($translateTextCache[$wasql][$locale][$identifier])){return $translateTextCache[$wasql][$locale][$identifier];}
	if(isset($CONFIG['translate_cache_hours']) && isNum($CONFIG['translate_cache_hours'])){
		$evalstr="return translateGetCachedRecs('{$locale}',{$wasql});";
		$trecs=getStoredValue($evalstr,0,$CONFIG['translate_cache_hours']);
	}
	else{
		$topts=array(
			'-table'	=> '_translations',
			'-where'	=> "wasql={$wasql} and locale='{$locale}'",
			'-fields'	=> 'locale,identifier,translation'
		);
		if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
			$topts['-where'].=" and source_id={$CONFIG['translate_source_id']}";
		}
		$trecs=getDBRecords($topts);
	}
	//echo printValue($map).printValue($topts).printValue($trecs);exit;
	foreach($trecs as $rec){
		$rec['locale']=strtolower($rec['locale']);
		$rec['identifier']=strtolower($rec['identifier']);
		$translateTextCache[$wasql][$rec['locale']][$rec['identifier']]=$rec['translation'];
	}
	if(isset($translateTextCache[$wasql][$locale][$identifier])){return $translateTextCache[$wasql][$locale][$identifier];}
	global $CONFIG;
	list($target_lang,$target_country)=translateParseLocale($locale);
	$translation=$text;
	//replace HTML TAGS with {1}

	// if($target_lang != $source_lang && isset($CONFIG['translate_source'])){
	// 	if(!isset($CONFIG['translate_key'])){
	// 		debugValue('Missing Translate Key in config');
	// 		return $text;
	// 	}
	// 	switch(strtolower($CONFIG['translate_source'])){
	// 		case 'yandex':
	// 			$translation=translateYandex($translation,$source_lang,$target_lang);
	// 		break;
	// 		case 'google':
	// 			$translation=translateGoogle($translation,$source_lang,$target_lang);
	// 		break;
	// 	}
	// }
	$map=translateMapText($translation);

	$taddopts=array(
		'-table'		=> '_translations',
		'-ignore'		=> 1,
		'locale'		=> $locale,
		'identifier'	=> $map['identifier'],
		'translation'	=> $translation,
		'wasql'=>$wasql
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
	$translateTextCache[$wasql][$locale][$map['identifier']]=$translation;
	//echo $source_locale.printValue($taddopts);exit;
	$tid=addDBRecord($taddopts);
	$ok=commonLogMessage('translate',"addDBRecord - {$tid} - {$locale} - {$translation}");

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
function translateGetCachedRecs($locale,$wasql){
	global $CONFIG;
	$topts=array(
		'-table'	=> '_translations',
		'-where'	=> "wasql={$wasql} and locale='{$locale}'",
		'-fields'	=> 'locale,identifier,translation'
	);
	if(isset($CONFIG['translate_source_id']) && isNum($CONFIG['translate_source_id'])){
		$topts['-where'].=" and source_id={$CONFIG['translate_source_id']}";
	}
	return getDBRecords($topts);
}
//---------- begin function translateYandex
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateGoogle($text,$source_lang,$target_lang){
	global $CONFIG;
	if(!isset($CONFIG['translate_key'])){
		commonLogMessage('translate','ERROR -  translateGoogle. Missing Google Translate Key in config');
		return $text;
	}
	$json=<<<ENDOFJSON
{
  'q': '{$text}',
  'target': '{$target_lang}',
  'source': '{$source_lang}'
}
ENDOFJSON;
	//$ok=commonLogMessage('translate',"translateGoogle - {$url} - {$locale} {$translation}");
	//return;
	//$post=postJSON($url,$json);
	//echo printValue($post);exit;
	commonLogMessage('translate',"translateGoogle - calling. {$source_lang} - {$target_lang} - {$text}");
	$url = "https://www.googleapis.com/language/translate/v2?key={$CONFIG['translate_key']}&q=".rawurlencode($text)."&source={$source_lang}&target={$target_lang}";
	$handle = curl_init($url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($handle);
	//echo printValue($response);
	$responseDecoded = json_decode($response, true);
	curl_close($handle);
	if(isset($responseDecoded['data']['translations'][0]['translatedText'])){
		commonLogMessage('translate',"translateGoogle - returned {$responseDecoded['data']['translations'][0]['translatedText']}");
		return $responseDecoded['data']['translations'][0]['translatedText'];
	}
	elseif(isset($responseDecoded['data']['translations']['translatedText'])){
		commonLogMessage('translate',"translateGoogle - returned {$responseDecoded['data']['translations']['translatedText']}");
		return $responseDecoded['data']['translations']['translatedText'];
	}
	commonLogMessage('translate',"translateGoogle - failed");
	return $text;
}
//---------- begin function translateYandex
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function translateYandex($text,$source_lang,$target_lang){
	global $CONFIG;
	if(!isset($CONFIG['translate_key'])){
		commonLogMessage('translate','ERROR - translateYandex. Missing translate_key in config');
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
	commonLogMessage('translate',"translateYandex - calling. {$source_lang} - {$target_lang} - {$text}");
	$post=postURL($url,$opts);
	//echo $post['body'];exit;
	if(isset($post['json_array']['text'][0])){
		commonLogMessage('translate',"translateYandex - returned {$post['json_array']['text'][0]}");
		return decodeURL($post['json_array']['text'][0]);
	}
	elseif(isset($post['json_array']['text'])){
		commonLogMessage('translate',"translateYandex - returned {$post['json_array']['text']}");
		return decodeURL($post['json_array']['text']);
	}
	commonLogMessage('translate',"translateYandex - failed");
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