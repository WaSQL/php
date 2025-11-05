<?php
/*
	https://www.geoapify.com/reverse-geocoding-api/
	They offer a free tier that gives you 3000 hits per day free
*/
$progpath=dirname(__FILE__);
//
function geoapifyReverseGeocoding($location){return geoapifyLatLon2Address($location);}
function geoapifyLatLon2Address($location){
	//
	global $CONFIG;
	if(!isset($CONFIG['geoapify_apikey'])){
		debugValue("geoapifyLatLon2Address Error: missing geoapify_apikey in config.xml. Go to https://www.geoapify.com/reverse-geocoding-api/ to obtain one");
		return array();
	}
	$url='https://api.geoapify.com/v1/geocode/reverse';
	$latlon=decodeJSON($location);
	$postopts=array(
		'-method'=>'GET',
		'-nossl'=>1,
		'-json'=>1,
		'lat'=>$latlon[0],
		'lon'=>$latlon[1],
		'apiKey'=>$CONFIG['geoapify_apikey']
	);
	$post=postURL($url,$postopts);
	if(!isset($post['json_array']['features'][0])){return array();}
	$address=$post['json_array']['features'][0]['properties'];
	$address['location']=$location;
	return $address;
}
?>