<?php
/*
	Geonames API methods
		http://www.geonames.org/export/ws-overview.html
*/
$progpath=dirname(__FILE__);
loadExtras('zipfile');
//---------- begin function geonamesPostalCodeSearch--------------------
/**
* @describe calls the geonames.org API and returns information about a postal code
* @param postalcode string - postalcode to lookup
* @param country string - defaults to US
* @param username string - Your username with maxminds. defaults to 'demo'
* @param maxrows integer - defaults to 10
* @return mixed - error msg on failure or an array.
*	name
*	latitude
*	longitude
*	postal_code
*	country_code
*	state_name
*	state_code
*	county_name
*	county_code
*	[community_name]
*	[community_code]
* @usage
*	//get info about 84321
*	$info=geonamesPostalCodeSearch(84321);
*
*	Returns Array
*	(
*	    [0] => Array
*	        (
*	            [county_name] => Cache
*	            [county_code] => 005
*	            [state_code] => UT
*	            [postal_code] => 84321
*	            [country_code] => US
*	            [longitude] => -111.822613
*	            [name] => Logan
*	            [latitude] => 41.747025
*	            [state_name] => Utah
*	        )
*	)
*
*/
function geonamesPostalCodeSearch($postalcode,$country='US',$username='demo',$maxrows=10){
	//http://api.geonames.org/postalCodeSearchJSON?formatted=true&postalcode=84062&country=US&maxRows=10&username=demo&style=full
	$url='http://api.geonames.org/postalCodeSearchJSON';
	$opts=array(
		'-method'		=> 'GET',
		'formatted'		=> 'true',
		'postalcode'	=> $postalcode,
		'country'		=> $country,
		'maxRows'		=> $maxrows,
		'username'		=> $username,
		'style'			=> 'full'
	);
	$post=postURL($url,$opts);
	$json=json_decode($post['body'],true);
	$map=array(
		'placeName'		=>'name',
		'lat'			=> 'latitude',
		'lng'			=> 'longitude',
		'postalCode'	=> 'postal_code',
		'countryCode'	=> 'country_code',
		'adminName1'	=> 'state_name',
		'adminCode1'	=> 'state_code',
		'adminName2'	=> 'county_name',
		'adminCode2'	=> 'county_code',
		'adminName3'	=> 'community_name',
		'adminCode3'	=> 'community_code'
	);
	if(isset($json['status']['message'])){
    	return "geonamesPostalCodeSearch Error {$json['status']['value']}: {$json['status']['message']}";
	}
	$codes=array();
	foreach($json['postalCodes'] as $rec){
		$code=array();
    	foreach($rec as $key=>$val){
        	$code[$map[$key]]=$val;
		}
		$codes[]=$code;
	}
	return $codes;
}
//---------- begin function geonamesImportZipcodes--------------------
/**
* @describe imports zipcodes into the zipcodes table. Creates the table if it does not exist
* @param countries array - list of 2 character country codes to import
* @param params array
*	-truncate boolean - truncates the zipcodes table if true
* @return array - array for each country imported.
*	country string
*	record_count int - record count
*	download_time float - download time in seconds
*	import_time float - import time in seconds
*	total_time float - total time in seconds to process this country
*	[errors] array - error messages if any
* @usage
*	<?php
*	//import United States and Canada zipcodes and truncate the table each time
*	$rtn=geonamesImportZipcodes(array('US','CA'),array('-truncate'=>true));
*	?>
*	---
*	<?php
*	//import United Stateszipcodes without removing other countries from the zipcodes table
*	$rtn=geonamesImportZipcodes(array('US'));
*	?>
*/
function geonamesImportZipcodes($countries=array(),$params=array()){
	$progpath=dirname(__FILE__);
	global $logfile;
	$logfile="{$progpath}/geonamesImportZipcodes.log";
	setFileContents($logfile,"started".PHP_EOL);
	$rtn=array();
	//create schema if it does not exist in this database
	if(!isDBTable('zipcodes')){
    	$fields=array(
    		'community_code'=>'varchar(20) NULL',
			'community_name'=>'varchar(100) NULL',
			'country_code'=>'char(2) NOT NULL',
			'county_code'=>'varchar(20) NULL',
			'county_name'=>'varchar(100) NULL',
			'latitude'=>'float(18,12) NULL',
			'longitude'=>'float(18,12) NULL',
			'name'=>'varchar(180) NULL',
			'postal_code'=>'varchar(20) NOT NULL',
			'state_code'=>'varchar(20) NULL',
			'state_name'=>'varchar(100) NULL'
		);
		$ok = createDBTable('zipcodes',$fields);
		if(!isDBTable('zipcodes')){return $ok;}
	}
	if($params['-truncate']){
		$ok=truncateDBTable('zipcodes');
	}
	if(!is_array($countries)){$countries=array($countries);}
	foreach($countries as $country){
		$start_time=$last=getRunTime();
		//uppercase the country code
		$country=strtoupper($country);
		$remote_file="http://download.geonames.org/export/zip/{$country}.zip";
		$local_file="{$progpath}/zipcodes_{$country}.zip";
		appendFileContents($logfile,"downloading {$local_file}".PHP_EOL);
		if(copy($remote_file,$local_file)){
			appendFileContents($logfile,"{$local_file} downloaded".PHP_EOL);
			$rtn[$country]['download_time']=$last=getRunTime()-$last;
			$files=zipExtract($local_file);
			appendFileContents($logfile,"{$local_file} extracted".PHP_EOL);
			foreach($files as $file){
				$fname=getFileName($file,1);
				if($fname==$country){
					//drop this country from the table
					$ok=executeSQL("delete from zipcodes where country_code='{$country}'");
					//add al
					appendFileContents($logfile,"processing {$country} records in {$file}".PHP_EOL);
					$rtn[$country]=processCSVFileLines($file,'geonamesImportZipcode',array(
						'separator'=>"\t",
						'fields'	=> array(
							'country_code','postal_code','name',
							'state_name','state_code',
							'county_name','county_code',
							'community_name','community_code',
							'latitude','longitude','accuracy'
						)
					));
					appendFileContents($logfile,"{$country} records in {$file} processed".PHP_EOL);
					$rtn[$country]['import_time']=$last=getRunTime()-$last;
				}
			}
			appendFileContents($logfile,"cleaning up".PHP_EOL);
			//cleanup
			cleanDir("{$progpath}/zipcodes_{$country}");
			rmdir("{$progpath}/zipcodes_{$country}");
			unlink($local_file);
			$rtn[$country]['total_time']=getRunTime()-$start_time;
		}
		else{
        	$rtn[$country]['errors'][]='Unable to download';
		}
	}
	appendFileContents($logfile,"finished".PHP_EOL);
	return $rtn;
}
//---------- begin function geonamesImportZipcode
/**
* @description used by geonamesImportZipcodes function to add records into the zipcodes table
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function geonamesImportZipcode($rec){
	global $logfile;
	$opts=$rec['line'];
	$opts['-table']="zipcodes";
	$id=addDBRecord($opts);
}
