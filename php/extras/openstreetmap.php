<?php
/*
	OpenStreetMap/Nominatim wrapper functions
	Documentation: https://nominatim.org/release-docs/latest/api/
	Usage Policy: Please respect the rate limit of 1 request per second for public API
	Cache Table openstreetmap is automatically created for you.

	Usage Example:
	loadExtras('openstreetmap');
	$location=[40.3888786, -111.7499857];
	$address=osmReverse($location,array('-table'=>'openstreetmap'));
	echo printValue($address);

*/
if(!isDBTable('openstreetmap')){
	$ok=createDBTable('openstreetmap',array('hash_value'=>"varchar(64) NOT NULL UNIQUE",'data'=>"JSON"));
}
//---------- begin function osmReverse --------------------
/**
 * @describe Generates an address from coordinates (reverse geocoding)
 * @documentation https://nominatim.org/release-docs/latest/api/Reverse/
 * @param location mixed - ["51.21709661403662","6.7782883744862374"] OR '51.21709661403662,6.7782883744862374' OR ['lat'=>'51.21709661403662','lon'=>'6.7782883744862374']
 * @param params array - Optional parameters:
 *   - format: string - xml, json, jsonv2, geojson, geocodejson (default: json)
 *   - zoom: integer - 0-18 (default: 18) - 3=country, 5=state, 8=county, 10=city, 14=neighborhood, 16=street, 18=building
 *   - layer: string - address, poi, railway, natural, manmade
 *   - email: string - Valid email address for identifying requests (recommended for heavy usage)
 *   - addressdetails: int - 0|1 (default: 1) - Include address breakdown
 *   - extratags: int - 0|1 - Include additional database information (wikipedia, opening hours, etc.)
 *   - namedetails: int - 0|1 - Include full list of names (language variants, older names, etc.)
 *   - accept-language: string - Preferred language order (e.g., 'en,de')
 *   - -table: string - Database table name for caching results (requires hash_value varchar(64) UNIQUE, data JSON)
 *   - -maxage number - days to cache the record locally
 *   - -user_agent string - User-Agent identifying the application
 * @return array|string - Address object on success, error string on failure
 * @usage 
 *   $address = osmReverse(["51.21709661403662","6.7782883744862374"]);
 *   $address = osmReverse(['lat'=>'51.217','lon'=>'6.778'], ['zoom'=>14, 'email'=>'you@example.com']);
 */
function osmReverse($location, $params = array()) {
	// Validate and set defaults
	if (!isset($params['format'])) { $params['format'] = 'json'; }
	if (!isset($params['zoom'])) { $params['zoom'] = 18; }
	if (!isset($params['addressdetails'])) { $params['addressdetails'] = 1; }
	if (!isset($params['-table'])) {$params['-table']='openstreetmap';}
	if (!isset($params['-maxage'])) {$params['-maxage'] = 90;}
	if (!isset($params['-user_agent'])) {$params['-user_agent'] = 'WaSQL-OSM-Wrapper/1.3';}
	//sanatize maxage
	$params['-maxage'] = (int)$params['-maxage'];
	// Parse location parameter
	if (!is_array($location)) {
		// Handle string input
		if (strpos($location, ',') !== false) {
			$parts = explode(',', $location);
			$location = ['lat' => trim($parts[0]), 'lon' => trim($parts[1])];
		} else {
			$location = decodeJSON($location);
		}
	}
	// Extract lat/lon
	$lat = commonCoalesce($location['lat'] ?? null, $location[0] ?? null);
	$lon = commonCoalesce($location['lon'] ?? null, $location[1] ?? null);
	// Validate coordinates
	if (empty($lat) || empty($lon)) {
		return "osmReverse ERROR: Invalid location - lat and lon are required";
	}
	if (!is_numeric($lat) || !is_numeric($lon)) {
		return "osmReverse ERROR: Coordinates must be numeric";
	}
	if ($lat < -90 || $lat > 90) {
		return "osmReverse ERROR: Latitude must be between -90 and 90";
	}
	if ($lon < -180 || $lon > 180) {
		return "osmReverse ERROR: Longitude must be between -180 and 180";
	}
	// Check cache table
	$hash_value = hash('sha256', $lat . ',' . $lon . ',' . ($params['zoom'] ?? 18));
	$rec = getDBRecord(array(
		'-table' => $params['-table'],
		'-where' => "hash_value='{$hash_value}' AND COALESCE(_edate, _cdate) >= DATE_SUB(NOW(), INTERVAL {$params['-maxage']} DAY)"
	));
	if (isset($rec['_id']) && !empty($rec['data'])) {
		$rtn=decodeJSON($rec['data']);
		ksort($rtn);
		return $rtn;
	}
	$url = "https://nominatim.openstreetmap.org/reverse";
	//postopts
	$postopts = array(
		'-method' => 'GET',
		'-nossl' => 1,
		'-json' => 1,
		'-user_agent' => $params['-user_agent'],
		'lat' => $lat,
		'lon' => $lon
	);
	// Apply optional params (exclude internal ones)
	foreach ($params as $k => $v) {
		if (substr($k, 0, 1) !== '-') {
			$postopts[$k] = $v;
		}
	}
	// Make the request
	$post = postURL($url, $postopts);
	// Check for errors
	if (!isset($post['json_array'])) {
		return "osmReverse ERROR: " . ($post['body'] ?? 'Unknown error');
	}
	$rtn = $post['json_array'];
	// Check for API errors
	if (isset($rtn['error'])) {
		return "osmReverse ERROR: " . $rtn['error'];
	}
	// Set convenience fields if address data exists
	if (isset($rtn['address'])) {
		$addr = $rtn['address'];
		$house = $addr['house_number'] ?? '';
		$road = $addr['road'] ?? '';
		$town = $addr['town'] ?? $addr['city'] ?? $addr['village'] ?? '';
		$state = $addr['state'] ?? '';
		$postcode = $addr['postcode'] ?? '';
		
		$rtn['addr_1'] = trim("{$house} {$road}");
		$rtn['addr_2'] = trim("{$town}, {$state} {$postcode}", ', ');
	}
	// Save to cache
	addDBRecord(array(
		'-table' => $params['-table'],
		'hash_value' => $hash_value,
		'-upsert' => 'data',
		'data' => $rtn
	));
	ksort($rtn);
	return $rtn;
}

//---------- begin function osmSearch --------------------
/**
 * @describe Searches for locations by name or address (forward geocoding)
 * @documentation https://nominatim.org/release-docs/latest/api/Search/
 * @param query mixed - Search string OR array with structured query parameters
 * @param params array - Optional parameters:
 *   - format: string - xml, json, jsonv2, geojson, geocodejson (default: json)
 *   - addressdetails: int - 0|1 (default: 0) - Include address breakdown
 *   - extratags: int - 0|1 - Include additional database information
 *   - namedetails: int - 0|1 - Include full list of names
 *   - limit: int - Max number of results (default: 10, max: 50)
 *   - countrycodes: string - Comma-separated ISO 3166-1alpha2 codes (e.g., 'us,ca')
 *   - viewbox: string - Preferred area to find results (minlon,minlat,maxlon,maxlat)
 *   - bounded: int - 0|1 - Restrict results to viewbox
 *   - dedupe: int - 0|1 (default: 1) - Remove duplicate results
 *   - email: string - Valid email address for identifying requests
 *   - accept-language: string - Preferred language order
 *   - -table: string - Database table name for caching results
 *   - -maxage number - days to cache the record locally
 *   - -user_agent string - User-Agent identifying the application
 * @return array|string - Array of location results on success, error string on failure
 * @usage 
 *   $results = osmSearch("1600 Pennsylvania Avenue, Washington DC");
 *   $results = osmSearch(['street'=>'1600 Pennsylvania Ave', 'city'=>'Washington', 'country'=>'USA']);
 *   $results = osmSearch("pizza", ['countrycodes'=>'us', 'limit'=>5]);
 */
function osmSearch($query, $params = array()) {
	// Validate and set defaults
	if (!isset($params['format'])) { $params['format'] = 'json'; }
	if (!isset($params['limit'])) { $params['limit'] = 10; }
	if (!isset($params['-table'])) {$params['-table']='openstreetmap';}
	if (!isset($params['-maxage'])) {$params['-maxage'] = 90;}
	if (!isset($params['-user_agent'])) {$params['-user_agent'] = 'WaSQL-OSM-Wrapper/1.3';}
	//sanatize maxage
	$params['-maxage'] = (int)$params['-maxage'];
	// Validate query
	if (empty($query)) {
		return "osmSearch ERROR: Query parameter is required";
	}
	// Build query hash for caching
	$query_string = is_array($query) ? encodeJSON($query) : $query;
	// Check cache
	$hash_value = hash('sha256', $query_string . encodeJSON($params));
	$rec = getDBRecord(array(
		'-table' => $params['-table'],
		'-where' => "hash_value='{$hash_value}' AND COALESCE(_edate, _cdate) >= DATE_SUB(NOW(), INTERVAL {$params['-maxage']} DAY)"
	));
	if (isset($rec['_id']) && !empty($rec['data'])) {
		$rtn=decodeJSON($rec['data']);
		ksort($rtn);
		return $rtn;
	}

	$url = "https://nominatim.openstreetmap.org/search";
	//postopts
	$postopts = array(
		'-method' => 'GET',
		'-nossl' => 1,
		'-json' => 1,
		'-user_agent' => $params['-user_agent']
	);
	// Handle structured vs free-form query
	if (is_array($query)) {
		// Structured query
		foreach ($query as $k => $v) {
			$postopts[$k] = $v;
		}
	} else {
		// Free-form query
		$postopts['q'] = $query;
	}
	// Apply optional params (exclude internal ones)
	foreach ($params as $k => $v) {
		if (substr($k, 0, 1) !== '-') {
			$postopts[$k] = $v;
		}
	}
	// Make the request
	$post = postURL($url, $postopts);
	// Check for errors
	if (!isset($post['json_array'])) {
		return "osmSearch ERROR: " . ($post['body'] ?? 'Unknown error');
	}
	$rtn = $post['json_array'];
	// Check for API errors
	if (isset($rtn['error'])) {
		return "osmSearch ERROR: " . $rtn['error'];
	}
	// Save to cache
	addDBRecord(array(
		'-table' => $params['-table'],
		'hash_value' => $hash_value,
		'-upsert' => 'data',
		'data' => encodeJSON($rtn)
	));
	ksort($rtn);
	return $rtn;
}

//---------- begin function osmLookup --------------------
/**
 * @describe Looks up address details for OSM objects by their IDs
 * @documentation https://nominatim.org/release-docs/latest/api/Lookup/
 * @param osm_ids mixed - Single OSM ID string (e.g., 'R146656') OR array of IDs (e.g., ['R146656', 'W104393803'])
 * @param params array - Optional parameters:
 *   - format: string - xml, json, jsonv2, geojson, geocodejson (default: json)
 *   - addressdetails: int - 0|1 (default: 0) - Include address breakdown
 *   - extratags: int - 0|1 - Include additional database information
 *   - namedetails: int - 0|1 - Include full list of names
 *   - email: string - Valid email address for identifying requests
 *   - accept-language: string - Preferred language order
 *   - -table: string - Database table name for caching results
 *   - -maxage number - days to cache the record locally
 *   - -user_agent string - User-Agent identifying the application
 * @return array|string - Array of location objects on success, error string on failure
 * @usage 
 *   $details = osmLookup('R146656');
 *   $details = osmLookup(['R146656', 'W104393803', 'N240109189']);
 */
function osmLookup($osm_ids, $params = array()) {
	// Validate and set defaults
	if (!isset($params['format'])) { $params['format'] = 'json'; }
	if (!isset($params['-table'])) {$params['-table']='openstreetmap';}
	if (!isset($params['-maxage'])) {$params['-maxage'] = 90;}
	if (!isset($params['-user_agent'])) {$params['-user_agent'] = 'WaSQL-OSM-Wrapper/1.3';}
	//sanatize maxage
	$params['-maxage'] = (int)$params['-maxage'];
	// Validate input
	if (empty($osm_ids)) {
		return "osmLookup ERROR: OSM IDs are required";
	}
	// Convert to comma-separated string if array
	if (is_array($osm_ids)) {
		$osm_ids_string = implode(',', $osm_ids);
	} else {
		$osm_ids_string = $osm_ids;
	}
	// Check cache
	$hash_value = hash('sha256', $osm_ids_string . encodeJSON($params));
	$rec = getDBRecord(array(
		'-table' => $params['-table'],
		'-where' => "hash_value='{$hash_value}' AND COALESCE(_edate, _cdate) >= DATE_SUB(NOW(), INTERVAL {$params['-maxage']} DAY)"
	));
	if (isset($rec['_id']) && !empty($rec['data'])) {
		$rtn=decodeJSON($rec['data']);
		ksort($rtn);
		return $rtn;
	}

	$url = "https://nominatim.openstreetmap.org/lookup";
	//postopts
	$postopts = array(
		'-method' => 'GET',
		'-nossl' => 1,
		'-json' => 1,
		'-user_agent' => $params['-user_agent'],
		'osm_ids' => $osm_ids_string
	);
	// Apply optional params (exclude internal ones)
	foreach ($params as $k => $v) {
		if (substr($k, 0, 1) !== '-') {
			$postopts[$k] = $v;
		}
	}
	// Make the request
	$post = postURL($url, $postopts);
	// Check for errors
	if (!isset($post['json_array'])) {
		return "osmLookup ERROR: " . ($post['body'] ?? 'Unknown error');
	}
	$rtn = $post['json_array'];
	// Check for API errors
	if (isset($rtn['error'])) {
		return "osmLookup ERROR: " . $rtn['error'];
	}
	// Save to cache if -table is specified
	addDBRecord(array(
		'-table' => $params['-table'],
		'hash_value' => $hash_value,
		'-upsert' => 'data',
		'data' => encodeJSON($rtn)
	));
	ksort($rtn);
	return $rtn;
}

//---------- begin function osmDetails --------------------
/**
 * @describe Returns detailed information about a single OSM place
 * @documentation https://nominatim.org/release-docs/latest/api/Details/
 * @param place_id mixed - Nominatim place_id OR array with one of: ['osmtype'=>'N|W|R', 'osmid'=>123] OR ['place_id'=>123]
 * @param params array - Optional parameters:
 *   - format: string - json (default), html - Note: Details API has limited format options
 *   - addressdetails: int - 0|1 (default: 0) - Include address breakdown
 *   - keywords: int - 0|1 - Include keywords for the place
 *   - linkedplaces: int - 0|1 (default: 1) - Include linked places
 *   - hierarchy: int - 0|1 - Include hierarchy of places
 *   - group_hierarchy: int - 0|1 - Group hierarchy by type
 *   - polygon_geojson: int - 0|1 - Include geometry as GeoJSON
 *   - email: string - Valid email address for identifying requests
 *   - accept-language: string - Preferred language order
 *   - -table: string - Database table name for caching results
 *   - -maxage number - days to cache the record locally
 *   - -user_agent string - User-Agent identifying the application
 * @return array|string - Detailed place object on success, error string on failure
 * @usage 
 *   $details = osmDetails(123456);
 *   $details = osmDetails(['osmtype'=>'W', 'osmid'=>104393803]);
 *   $details = osmDetails(['place_id'=>123456], ['hierarchy'=>1, 'addressdetails'=>1]);
 */
function osmDetails($place_id, $params = array()) {
	// Validate and set defaults
	if (!isset($params['format'])) { $params['format'] = 'json'; }
	if (!isset($params['-table'])) {$params['-table']='openstreetmap';}
	if (!isset($params['-maxage'])) {$params['-maxage'] = 90;}
	if (!isset($params['-user_agent'])) {$params['-user_agent'] = 'WaSQL-OSM-Wrapper/1.3';}
	//sanatize maxage
	$params['-maxage'] = (int)$params['-maxage'];
	// Validate input
	if (empty($place_id)) {
		return "osmDetails ERROR: Place identifier is required";
	}
	$url = "https://nominatim.openstreetmap.org/details";
	//postopts
	$postopts = array(
		'-method' => 'GET',
		'-nossl' => 1,
		'-json' => 1,
		'-user_agent' => $params['-user_agent'],
	);
	// Handle different place identifier types
	if (is_array($place_id)) {
		if (isset($place_id['place_id'])) {
			$postopts['place_id'] = $place_id['place_id'];
			$cache_key = 'place_id:' . $place_id['place_id'];
		} elseif (isset($place_id['osmtype']) && isset($place_id['osmid'])) {
			$postopts['osmtype'] = strtoupper($place_id['osmtype']);
			$postopts['osmid'] = $place_id['osmid'];
			$cache_key = 'osm:' . $postopts['osmtype'] . ':' . $postopts['osmid'];
		} else {
			return "osmDetails ERROR: Place array must contain either 'place_id' or both 'osmtype' and 'osmid'";
		}
	} else {
		$postopts['place_id'] = $place_id;
		$cache_key = 'place_id:' . $place_id;
	}
	// Check cache
	$hash_value = hash('sha256', $cache_key . encodeJSON($params));
	$rec = getDBRecord(array(
		'-table' => $params['-table'],
		'-where' => "hash_value='{$hash_value}' AND COALESCE(_edate, _cdate) >= DATE_SUB(NOW(), INTERVAL {$params['-maxage']} DAY)"
	));
	if (isset($rec['_id']) && !empty($rec['data'])) {
		$rtn=decodeJSON($rec['data']);
		ksort($rtn);
		return $rtn;
	}
	// Apply optional params (exclude internal ones)
	foreach ($params as $k => $v) {
		if (substr($k, 0, 1) !== '-') {
			$postopts[$k] = $v;
		}
	}
	// Make the request
	$post = postURL($url, $postopts);
	// Check for errors
	if (!isset($post['json_array'])) {
		return "osmDetails ERROR: " . ($post['body'] ?? 'Unknown error');
	}
	$rtn = $post['json_array'];
	// Check for API errors
	if (isset($rtn['error'])) {
		return "osmDetails ERROR: " . $rtn['error'];
	}
	// Save to cache if -table is specified
	addDBRecord(array(
		'-table' => $params['-table'],
		'hash_value' => $hash_value,
		'-upsert' => 'data',
		'data' => encodeJSON($rtn)
	));
	ksort($rtn);
	return $rtn;
}

//---------- begin function osmStatus --------------------
/**
 * @describe Checks the status of the Nominatim server
 * @documentation https://nominatim.org/release-docs/latest/api/Status/
 * @param params array - Optional parameters:
 *   - format: string - json (default), text
 *   - email: string - Valid email address for identifying requests
 *   - -user_agent string - User-Agent identifying the application
 * @return array|string - Status object with 'status' (0=OK, error code otherwise), 'message', and 'data_updated' timestamp
 * @usage 
 *   $status = osmStatus();
 *   $status = osmStatus(['format'=>'json']);
 */
function osmStatus($params = array()) {
	// Validate and set defaults
	if (!isset($params['format'])) { $params['format'] = 'json'; }
	if (!isset($params['-user_agent'])) {$params['-user_agent'] = 'WaSQL-OSM-Wrapper/1.3';}
	$url = "https://nominatim.openstreetmap.org/status";
	$postopts = array(
		'-method' => 'GET',
		'-nossl' => 1,
		'-json' => 1,
		'-user_agent' => $params['-user_agent'],
	);
	// Apply optional params
	foreach ($params as $k => $v) {
		if (substr($k, 0, 1) !== '-') {
			$postopts[$k] = $v;
		}
	}
	// Make the request
	$post = postURL($url, $postopts);
	// Check for errors
	if (!isset($post['json_array'])) {
		return "osmStatus ERROR: " . ($post['body'] ?? 'Unknown error');
	}
	$rtn = $post['json_array'];
	// Add human-readable interpretation
	if (isset($rtn['status'])) {
		$rtn['status_ok'] = ($rtn['status'] === 0);
		$rtn['status_message'] = ($rtn['status'] === 0) ? 'Service is operational' : 'Service error: ' . ($rtn['message'] ?? 'Unknown error');
	}
	ksort($rtn);
	return $rtn;
}

?>
