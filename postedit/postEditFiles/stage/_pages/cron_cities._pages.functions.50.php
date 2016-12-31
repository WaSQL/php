<?php
/*
	lookahead - get count first - once count is under 100 then return list of cities
	SELECT count(distinct city) FROM cities WHERE city like 'ple%'
	SELECT distinct city FROM cities WHERE city like 'pleas%' order by city
*/
function pageGetCityByState($state){
	$url="http://gomashup.com/json.php?fds=geo/usa/zipcode/state/{$state}";
	$post=postURL($url,array('-method'=>'GET'));
	$str=trim($post['body']);
	$str=preg_replace('/^\(/','',$str);
	$str=preg_replace('/\)$/','',$str);
	$str=trim($str);
	//echo $str;exit;
	$recs=json_decode($str,1);
	return $recs['result'];
}
function pageGetStates($country){
	$recs=getDBRecords(array(
		'-table'=>'states',
		'country'=>$country,
		'-fields'=>'code',
		'-index'=>'code'
	));
	return array_keys($recs);
}
/*
	schema
lat DECIMAL(10, 8) NOT NULL, lng DECIMAL(11, 8) NOT NULL
longitude DECIMAL(11, 8)
zipcode char(5)
zipclass varchar(30)
county varchar(255)
city varchar(255) NOT NULL
state char(2) NOT NULL
latitude decimal(10,8)

( => -110.435974
            [Zipcode] => 84001
            [ZipClass] => STANDARD
            [County] => DUCHESNE
            [City] => ALTAMONT
            [State] => UT
            [Latitude] => +40.320728
*/
?>
