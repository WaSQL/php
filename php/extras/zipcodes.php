<?php
/* 
   Creates a table called zipcodes if it does not alread exist
   Includes zipcodesImportCountry function to Download and import zipcodes from http://download.geonames.org/export/zip/
   They are updated monthly
country code      : iso country code, 2 characters
postal code       : varchar(20)
place name        : varchar(180)
admin name1       : 1. order subdivision (state) varchar(100)
admin code1       : 1. order subdivision (state) varchar(20)
admin name2       : 2. order subdivision (county/province) varchar(100)
admin code2       : 2. order subdivision (county/province) varchar(20)
admin name3       : 3. order subdivision (community) varchar(100)
admin code3       : 3. order subdivision (community) varchar(20)
latitude          : estimated latitude (wgs84)
longitude         : estimated longitude (wgs84)
accuracy          : accuracy of lat/lng from 1=estimated to 6=centroid

*/
//Load the zipfile extra so we can extract the .zip files
loadExtras('zipfile');
//create the table if it does not exist
if(!isDBTable('zipcodes')){zipcodesCreateTable();}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function zipcodesCreateTable(){
	$table='zipcodes';
	$fields=array(
	  	'community_code'	=> 'varchar(20) NULL',
		'community_name'	=> 'varchar(100) NULL',
		'country_code'		=> 'char(2) NOT NULL',
		'county_code'		=> 'varchar(20) NULL',
		'county_name'		=> 'varchar(100) NULL',
		'latitude'			=> 'float(18,12) NULL',
		'longitude'			=> 'float(18,12) NULL',
		'name'				=> 'varchar(180) NULL',
		'postal_code'		=> 'varchar(20) NOT NULL',
		'state_code'		=> 'varchar(20) NULL',
		'state_name'		=> 'varchar(100) NULL',
		'updated'			=> 'date NULL'
		);
	$ok = createDBTable($table,$fields);
	//indexes
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name"));
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"country_code,postal_code",'-unique'=>true));
	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"updated"));
}

//---------- begin function zipcodesImportCountry ----------
/**
* @describe imports specified countries into the zipcodes table
* @param countries string - comma separated list of countries
* @param truncate boolean - truncate the table first - defaults to false
* @return mixed - true on success and an error message on failure
* @usage
*	<?php
*	loadExtras('zipcodes');
*	$rtn=zipcodesImportCountry('US,CA,AU');
*	?>
*/
function zipcodesImportCountry($country_codes,$truncate=false){
	global $USER;
	global $CONFIG;
	global $zipcodesRecs;
	$zipcodesRecs=array();
	ini_set('max_execution_time', 5000);
	set_time_limit(5500);
	//truncate?
	if($truncate){$ok=truncateDBTable('zipcodes');}
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
		return "Unable to create {$progpath}/temp";
	}
	foreach($country_codes as $country){
		$country=strtoupper($country);
		$remote_file="http://download.geonames.org/export/zip/{$country}.zip";
		$local_file="{$progpath}/temp/zipcodes_{$country}.zip";
		if(copy($remote_file,$local_file)){
			if(!$truncate){
				//clean out the zipcodes for this country
				$ok=executeSQL("delete from zipcodes where country_code='{$country}'");
			}
			$zipcodesRecs=array();
			$files=zipExtract($local_file);
			foreach($files as $file){
				$fname=getFileName($file,1);
				if($fname==$country){
					$rtn[$country]['file']=$file;
					$rtn[$country]['lines_total']=processCSVFileLines($file,'zipcodesProcessLine',array(
						'-separator'=>"\t",
						'-fields'	=> array(
							'country_code','postal_code','name',
							'state_name','state_code',
							'county_name','county_code',
							'community_name','community_code',
							'latitude','longitude','accuracy'
						),
						'updated'	=> $cdate,
						'_cdate'	=> date('Y-m-d H:i:s'),
						'_cuser'	=> $cuser
					));
				}
			}
			//cleanup
			cleanDir("{$progpath}/temp/zipcodes_{$country}");
			rmdir("{$progpath}/temp/zipcodes_{$country}");
			$addopts=array('-recs'=>$zipcodesRecs);
			$ok=dbAddRecords($CONFIG['database'],'zipcodes',$addopts);
			//echo "dbAddRecords".printValue($ok).printValue($addopts);exit;
		}
	}
	return $country_codes;
}
//---------- begin function zipcodesGetClosestRecords ----------
/**
* @describe gets the closest records to a latitude,longitude
* @param latitude float - latitude
* @param longitude float - longitude
* @param limit integer - number of records to return - defaults to 1
* @param distance integer - max number of miles to go - defaults to 25
* @return array - record set
* @usage
*	<?php
*	loadExtras('zipcodes');
*	$rec=zipcodesGetClosestRecords($latitude,$longitude);
*	?>
*/
function zipcodesGetClosestRecords($latitude,$longitude,$limit=1,$distance=25){
	$query=<<<ENDOFQUERY
	SELECT *,
	3956 * 2 * ASIN(SQRT( POWER(SIN(({$latitude} -
	abs(
	dest.latitude)) * pi()/180 / 2),2) + COS({$latitude} * pi()/180 ) * COS(
	abs
	(dest.latitude) *  pi()/180) * POWER(SIN(({$longitude} -dest.longitude) *  pi()/180 / 2), 2) ))
	as distance
	FROM zipcodes dest
	having distance < {$distance}
	ORDER BY distance limit {$limit}
ENDOFQUERY;
	$recs=getDBRecords(array('-query'=>$query));
	if(count($recs)==1){
		//$recs[0]['_query']=$query;
		return $recs[0];
		}
	return $recs;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function zipcodesProcessLine($rec){
	global $zipcodesRecs;
	//echo printValue($rec);exit;
	$zipcodesRecs[]=$rec['line'];
	return;
}
?>