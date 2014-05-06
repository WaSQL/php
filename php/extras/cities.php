<?php
/* 
	Getting a list of world city populations turns out to be quite difficult
		http://opengeocode.org/download/cow.php
			North and South America, UK, and AU:  http://opengeocode.org/download/cow-na1.txt
		http://esa.un.org/unpd/wup/unup/index_panel2.html
			lets you choose up to 5 countries.  Only gives data on cities with 750,000 or more.

	Creates a table called cities if it does not alread exist
	Includes citiesImportCountry function to Download and import country cities from http://download.geonames.org/export/dump/
	Data format is as follows:
	geonameid         : integer id of record in geonames database
	name              : name of geographical point (utf8) varchar(200)
	asciiname         : name of geographical point in plain ascii characters, varchar(200)
	alternatenames    : alternatenames, comma separated, ascii names automatically transliterated, convenience attribute from alternatename table, varchar(8000)
	latitude          : latitude in decimal degrees (wgs84)
	longitude         : longitude in decimal degrees (wgs84)
	feature class     : see http://www.geonames.org/export/codes.html, char(1)
	feature code      : see http://www.geonames.org/export/codes.html, varchar(10)
	country code      : ISO-3166 2-letter country code, 2 characters
	cc2               : alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters
	admin1 code       : state - fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20)
	admin2 code       : county  - code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80)
	admin3 code       : code for third level administrative division, varchar(20)
	admin4 code       : code for fourth level administrative division, varchar(20)
	population        : bigint (8 byte int) 
	elevation         : in meters, integer
	dem               : digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat.
	timezone          : the timezone id (see file timeZone.txt) varchar(40)
	modification date : date of last modification in yyyy-MM-dd format

*/
//Load the zipfile extra so we can extract the .zip files
loadExtras('zipfile');
//create the table if it does not exist
if(!isDBTable('cities')){citiesCreateTable();}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function citiesCreateTable(){
	$table='cities';
	$fields=array(
		'city'				=> 'varchar(200) NULL',
		'country'			=> 'varchar(200)',
		'country_code'		=> 'char(2) NOT NULL',
		'population'		=> 'bigint NOT NULL Default 0',
		'population_past'	=> 'bigint NOT NULL Default 0',
		'growth_rate'		=> 'float(6,3) NOT NULL Default 0',
		);
	$ok = createDBTable($table,$fields);
	//indexes
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"city,country_code",'-unique'=>true));
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"population"));
}

//---------- begin function citiesImportCountry ----------
/**
* @describe creates and pushes a zip file to the browser
* @param files array - array of files to include in the zip file
* @param zipname string - name of the zipfile - defaults to zipfile.zip
* @param truncate boolean - truncate the table first - defaults to false
* @return file - pushes zipfile to browser and exits
* @usage
*	<?php
*	loadExtras('cities');
*	$rtn=citiesImportCountry('US,CA,AU');
*	?>
*/
function citiesImportCountry($country_codes,$truncate=false){
	global $dbh;
	global $USER;
	ini_set('max_execution_time', 5000);
	set_time_limit(5500);
	//truncate?
	if($truncate){$ok=truncateDBTable('cities');}
	//if the user passes in a comma separated list, split it up into an array
	if(!is_array($country_codes)){
		$country_codes=preg_split('/\,/',trim($country_codes));
	}
	$progpath=dirname(__FILE__);
	$rtn=array();
	$rtn['country_codes']=$country_codes;
	//make sure a temp folder exists to extract the zip files into
	if(!is_dir("{$progpath}/temp")){buildDir("{$progpath}/temp");}
	if(!is_dir("{$progpath}/temp")){
		$rtn['errors'][]="Unable to create {$progpath}/temp";
		return $rtn;
	}

	$sql = "INSERT INTO cities (
			_cuser,_cdate,city,country,country_code,population,population_past,growth_rate
			) VALUES ( ?,?,?,?,?,?,?,? )";
	$cdate=date('Y-m-d');
	global $zipcodeLine;
	global $zipcodePreparedStmt;
	$zipcodePreparedStmt = $dbh->prepare($sql);
	$cuser=isset($USER['_id'])?$USER['_id']:0;
	$zipcodeLine=array(
		'_cuser'		=> $cuser,
		'_cdate'		=> $cdate,
		'city'			=> 'Salt Lake City',
		'country'		=> 'United States of America',
		'country_code'	=> 'US',
		'population'	=> 12127250,
		'population_past'=>12127250,
		'growth_rate'	=> 1.25
	);
	$zipcodePreparedStmt->bind_param('issssiid',
		$zipcodeLine['_cuser'],
		$zipcodeLine['_cdate'],
		$zipcodeLine['city'],
		$zipcodeLine['country'],
		$zipcodeLine['country_code'],
		$zipcodeLine['population'],
		$zipcodeLine['population_past'],
		$zipcodeLine['growth_rate']
	);
	//lock the cities table for efficiency
	//$ok=citiesUpdateCountries();
	//printValue($ok);exit;
	//loop through each country
	foreach($country_codes as $country_code){
		$country=citiesGetCountry($country_code);
		$ok=executeSQL("UNLOCK TABLES");
		$ok=executeSQL("LOCK TABLES cities WRITE");
		if(!$truncate){$ok=executeSQL("delete from cities where country_code='{$country_code}'");}
		$csvfile="{$progpath}/temp/cities_{$country['name']}.csv";
		$cyear=(integer)date('Y');
		$endyear=5 * ceil($cyear / 5);
		$startyear=$endyear-5;
		unlink($csvfile);
		if(!is_file($csvfile)){
			$url='http://esa.un.org/unpd/wup/unup/p2k0data.asp';
			$postopts=array(
				'Panel'		=> 2,
				'Variable'	=> '92;',
				'Location'	=> $country['ccn3'],
				'StartYear'	=> $startyear,
				'EndYear'	=> $endyear,
				'DoWhat'	=> 'Download as .CSV File'
			);
			$post=postURL($url,$postopts);
			$ok=setFileContents($csvfile,$post['body']);
		}

		$csv=getCSVFileContents($csvfile);
		unlink($csvfile);
		if(!isset($csv['items'])){
			echo 'NO items: '.printValue($csv);
			continue;
		}
		foreach($csv['items'] as $rec){
			$rec['_cuser']=isNum($USER['_id'])?$USER['_id']:0;
			$rec['_cdate']=date('Y-m-d H:i:s');
			if(!strlen($rec['city'])){continue;}
			$rec['country_code']=$country_code;
			$rec['population']=$rec[$endyear]*1000;
			$rec['population_past']=$rec[$startyear]*1000;
			//determine growth rate.
			$growth=$rec[$endyear]-$rec[$startyear];
			if($growth > 0){
				$rec['growth_rate']=1+round(($growth/$rec[$startyear]/5),3);
			}
			elseif($growth < 0){
				$growth=$growth*-1;
				$rec['growth_rate']=-1*(1+round(($growth/$rec[$startyear]/5),3));
			}
			else{
				$rec['growth_rate']=0;
			}
			foreach($zipcodeLine as $key=>$val){
				if(isset($rec[$key])){
		        	$zipcodeLine[$key]=$rec[$key];
				}
				else{$zipcodeLine[$key]='';}
			}
			//echo printValue($zipcodeLine);continue;
			$zipcodePreparedStmt->execute();
		}
		$ok=executeSQL("UNLOCK TABLES");
	}
	return $rtn;
}
function citiesGetCountry($code){
	return getDBRecord(array(
		'-table'=>'countries',
		'code'	=> $code,
		'-fields'=>'_id,code,ccn3,name'
	));
}

function citiesUpdateCountries(){
	$url='https://raw.githubusercontent.com/mledoze/countries/master/countries.json';
	$post=postURL($url,array('-method'=>'GET','-ssl'=>false,'-ssl_version'=>3,'-debug'=>1));
	$countries=json_decode($post['body'], true);
	$recs=array();
	foreach($countries as $country){
    	$rec=array('name'=>$country['name']);
    	$tlds=array();
    	foreach($country['tld'] as $tld){
        	$tld=preg_replace('/^\./','',$tld);
        	$tlds[]=$tld;
		}
		$rec['tlds']=implode(':',$tlds);
		$rec['currency']=implode(':',$country['currency']);
		$rec['calling_code']=implode(':',$country['callingCode']);
		$rec['language']=implode(':',$country['language']);
		$rec['calling_code']=implode(':',$country['callingCode']);
		$rec['border_countries']=implode(':',$country['borders']);
		$rec['ccn3']=$country['ccn3'];
		$rec['code']=$country['cca2'];
		$rec['code3']=$country['cca3'];
		$rec['capital']=$country['capital'];
		$rec['region']=$country['region'];
		$rec['subregion']=$country['subregion'];
		$rec['population']=$country['population'];
		$rec['latitude']=$country['latlng'][0];
		$rec['longitude']=$country['latlng'][1];
		$rec['relevance']=$country['relevance'];
		switch(strtoupper($rec['code'])){
        	case 'US':$rec['tlds']='com';break;
        	case 'UK':$rec['tlds']='co.uk:uk';break;
        	case 'AU':$rec['tlds']='co.au:au';break;
		}
		$crec=getDBRecord(array(
			'-table'=>'countries',
			'code'	=> $rec['code'],
			'name'	=> $rec['name']
		));
		if(isset($crec['_id'])){
        	$rec['-table']='countries';
        	$rec['-where']="_id={$crec['_id']}";
        	$ok=editDBRecord($rec);
		}
		else{
			$crecs=getDBRecords(array(
				'-table'=>'countries',
				'code'	=> $rec['code']
			));
			if(is_array($crecs) && count($crecs)==1){
            	$rec['-table']='countries';
        		$rec['-where']="_id={$crecs[0]['_id']}";
        		$ok=editDBRecord($rec);
        		//echo printValue($ok).printValue($rec);
			}
			else{
				$rec['-table']='countries';
				$rec['newid']=addDBRecord($rec);
			}
		}
		//$recs[]=$rec;
	}
	//determine population_rank
	$recs=getDBRecords(array(
		'-table'	=> 'countries',
		'-order'	=> 'population desc,relevance desc',
		'-fields'	=> '_id,name,code,population,relevance'
	));
	foreach($recs as $i=>$rec){
		$rank=$i+1;
    	$ok=executeSQL("update countries set population_rank={$rank} where _id={$rec['_id']}");
    	$recs[$i]['population_rank']=$rank;
	}
	//echo "recs".printValue($recs);

}

//---------- begin function citiesGetClosestRecords ----------
/**
* @describe gets the closest records to a latitude,longitude
* @param latitude float - latitude
* @param longitude float - longitude
* @param limit integer - number of records to return - defaults to 1
* @return array - record set
* @usage
*	<?php
*	loadExtras('cities');
*	$rec=citiesGetClosestRecords($latitude,$longitude);
*	?>
*/
function citiesGetClosestRecords($latitude,$longitude,$limit=1){
	$query="
	SELECT *,
	3956 * 2 * ASIN(SQRT( POWER(SIN(({$latitude} -
	abs(
	dest.latitude)) * pi()/180 / 2),2) + COS({$latitude} * pi()/180 ) * COS(
	abs
	(dest.latitude) *  pi()/180) * POWER(SIN(({$longitude} -dest.longitude) *  pi()/180 / 2), 2) ))
	as distance
	FROM cities dest
	having distance < 25
	ORDER BY distance limit {$limit}
	";
	$recs=getDBRecords(array('-query'=>$query));
	if(count($recs)==1){
		//$recs[0]['_query']=$query;
		return $recs[0];
		}
	return $recs;
}

?>