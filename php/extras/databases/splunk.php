<?php

/*
	
	splunk can be queried using SQL like a database.
	Usage:
		<database
	        name="splunk"
	        dbhost="{host}"
	        dbtype="splunk"
	        dbuser="{userKey}"
	        dbpass="{secretKey}"
	        dbkey="{apiKey or token}"
	    />
*/
//---------- begin function splunkQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_splunk,dbuser_splunk, and dbpass_splunk
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage $recs=splunkQueryResults('select top 50 * from abcschema.abc');
*/
function splunkQueryResults($query,$params=array()){
	global $DATABASE;
	global $CONFIG;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'splunkQueryResults'
	);
	$db=$DATABASE[$CONFIG['db']];

	// Configuration
    $searchUrl = "https://{$db['dbhost']}/services/search/v2/jobs";
    $resultsUrl = "https://{$db['dbhost']}/services/search/v2/results";
	
    // Prepare the search query data
    $searchData = [
        'search' => $query,
        'earliest_time' => '-24h',  // Last 24 hours by default
        'output_mode' => 'json',
        '-headers'=>array(
        	'Authorization: Bearer ' . $db['dbkey'],
            'Content-Type: application/x-www-form-urlencoded'
        ),
        '-json'=>1,
        '-nossl'=>1
    ];
    // Execute search job request
    $spost=postURL($searchUrl,$searchData);
    //get sid
    if(!isset($spost['json_array']['sid'])){
    	return "FAILED search".printValue($searchData);
    }
    $sid=$spost['json_array']['sid'];

    // Poll for job completion
    $complete = false;
    $maxAttempts = 50;
    $attempts = 0;
    $url=$searchUrl . '/' . $sid;
    while (!$complete && $attempts < $maxAttempts) {
        sleep(2); // Wait 2 seconds between checks
        
        $post=postURL($url,array('-method'=>'GET','-json'=>1));
        if(isset($post['json_array']['isDone']) && $post['json_array']['isDone']){
        	$complete=true;
        }
        
        $attempts++;
    }

    if(!$complete){return "job timed out";}

   	// Get all results with pagination
    $recs = [];
    $totalCount = null;
    $offset=commonCoalesce($params['offset'],0);
    $limit=commonCoalesce($params['limit'],1000);
    do {
        // Get results with pagination parameters
        $paginatedUrl = $resultsUrl . '?sid=' . $sid . 
                       '&offset=' . $offset . 
                       '&count=' . $limit;
        
        $post=postURL($paginatedUrl,array(
        	'-json'=>1,
        	'-method'=>'GET'
        ));
        if(!isset($post['json_array']['results'][0])){
        	break;
        }
        $recs=array_merge($recs,$post['json_array']['results']);
        
        // Store total count on first iteration
        if ($totalCount === null) {
            $totalCount = $post['json_array']['totalResults'];
        }

        // Move offset for next iteration
        $offset += $limit;
        
    } while ($offset < $totalCount);
    
    return $recs;
}

function splunkNamedQueryList(){
	return array();
}
//---------- begin function splunkNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function splunkNamedQuery($name){
	switch(strtolower($name)){
		case 'tables':
			return <<<ENDOFQUERY
ENDOFQUERY;
		break;
	}
}
