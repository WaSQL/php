<?php

/*
	
	splunk can be queried using SQL like a database.
	Usage:
		<database
	        name="my_splunk"
	        dbhost="{yoursplunkID}.splunkcloud.com:8089"
	        dbtype="splunk"
	        dbkey="{apiKey or token}"
	    />
*/

function splunkGetDBRecords($params=array()){
	return splunkQueryResults($params['-query'],$params);
}

function splunkGetDBTables($params=array()){
    $query=<<<ENDOFQUERY
| metadata type=sourcetypes index=* 
| table sourcetype
ENDOFQUERY;
    $recs=splunkQueryResults($query,$params);
    $k="sourcetype";
    foreach($recs as $rec){
        $tables[]=strtolower($rec[$k]);
    }
    return $tables; 
}
function splunkGetDBFieldInfo($table,$params=array()){
    $query=<<<ENDOFQUERY
| search sourcetype={$table} 
| fieldsummary 
| eval _dbfield=field, _dbtype_ex=typeof(field) 
| table _dbfield _dbtype_ex
ENDOFQUERY;
    $recs=splunkQueryResults($query,$params);
    return $recs; 
}
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
    if(!strlen(trim($query))){
        return array(array('function'=>'splunkQueryResults','error'=>'No query specified'));
    }
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
	if(preg_match('/\#earliest_time\:([a-z0-9\+\-\@]+)/',$query,$m)){
        $earliest_time=$m[1];
    }
    else{$earliest_time='-24@h';}
    if(preg_match('/\#latest_time\:([a-z0-9\+\-\@]+)/',$query,$m)){
        $latest_time=$m[1];
    }
    else{$latest_time='now';}
    // Prepare the search query data
    $searchData = [
        'search'        => $query,
         'earliest_time' => $earliest_time,  // Last 24 hours by default
         'latest_time'   => $latest_time,
        'output_mode'   => 'json',
        '-headers'      => array(
        	'Authorization: Bearer ' . $db['dbkey'],
            'Content-Type: application/x-www-form-urlencoded'
        ),
        '-json'         => 1,
        '-nossl'        => 1
    ];
    // Execute search job request
    $spost=postURL($searchUrl,$searchData);
    //get sid
    if(!isset($spost['json_array']['sid'])){
    	return "FAILED search".printValue($spost['json_array']);
    }
    $sid=$spost['json_array']['sid'];

    // Poll for job completion
    $complete = false;
    $maxAttempts = 20;
    $attempts = 0;
    $url=$searchUrl . '/' . $sid;
    $sleepx=1;
    $sleepcnt=0;
    $debug=array();
    while (!$complete && $attempts < $maxAttempts) {
        sleep($sleepx); // Wait 2 seconds between checks
        $sleepcnt+=$sleepx;
        if($sleepx > 3){$sleepx=1;}
        else{$sleepx+=1;}
        $post=postURL($url,array(
            '-method'       =>'GET',
            'output_mode'   => 'json',
            '-json'         => 1,
            '-headers'      => array(
                'Authorization: Bearer ' . $db['dbkey'],
                'Content-Type: application/x-www-form-urlencoded'
            ),
            '-nossl'        => 1
        ));
        if($post['curl_info']['http_code'] >=400){
            return "FAILED search".printValue($post['json_array']);
        }
        $isDone=0;
        if(isset($post['json_array']['isDone'])){$isDone=$post['json_array']['isDone'];}
        elseif(isset($post['json_array']['entry'][0]['content']['isDone'])){$isDone=$post['json_array']['entry'][0]['content']['isDone'];}
        if($isDone || $isDone=='true' || $isDone==1){$isDone=1;}
        $debug[]="Attempt:{$attempts}. doneProgress:{$post['json_array']['entry'][0]['content']['doneProgress']}";
        //echo $isDone.printValue($post['json_array']);exit;
        if($isDone==1){
        	$complete=true;
            break;
        }
        
        $attempts++;
    }

    if(!$complete){
        echo "job timed out after {$attempts} attempts and {$sleepcnt} seconds".printValue($debug);exit;
        return "job timed out after {$attempts} attempts and {$sleepcnt} seconds";
    }

   	// Get all results with pagination
    if(isset($fh)){unset($fh);}
    if(isset($params['-filename'])){
        $starttime=microtime(true);
        if(isset($params['-append'])){
            //append
            $fh = fopen($params['-filename'],"ab");
        }
        else{
            if(file_exists($params['-filename'])){unlink($params['-filename']);}
            $fh = fopen($params['-filename'],"wb");
        }
        if(!isset($fh) || !is_resource($fh)){
            return "Failed to create {$params['-filename']}";
        }
        if(isset($params['-logfile'])){
            setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
        }
    }
    else{$recs=array();}
    $totalCount = null;
    $header=0;
    $offset=commonCoalesce($params['offset'],0);
    $limit=commonCoalesce($params['limit'],1000);
    $pcount=1;
    do {
        // Get results with pagination parameters
        $paginatedUrl = $searchUrl . "/{$sid}/results";
        
        $post=postURL($paginatedUrl,array(
        	'-method'=>'GET',
            '-json'         => 1,
            'offset'        => $offset,
            'count'         => $limit,
            'output_mode'   => 'json',
            '-headers'      => array(
                'Authorization: Bearer ' . $db['dbkey'],
                'Content-Type: application/x-www-form-urlencoded'
            ),
            '-nossl'        => 1
        ));
        //echo "HERE".printValue($post['json_array']);exit;
        if(isset($post['json_array']['results'][0])){
            $pcount=count($post['json_array']['results']);
            $rowcount+=$pcount;
            if(isset($params['-filename']) && isset($fh)){
                $csv=arrays2CSV($post['json_array']['results']);
                if($header==0){
                    $csv="\xEF\xBB\xBF".$csv;
                    $header=1;
                }
                $csv=preg_replace('/[\r\n]+$/','',$csv);
                fwrite($fh,$csv."\r\n");    
            }
            else{
                $recs=array_merge($recs,$post['json_array']['results']);
            }
            if(isset($params['-logfile'])){
                setFileContents($params['-logfile'],"Rowcount:".$rowcount.PHP_EOL);
            }
        }
        else{
            $pcount=0;
        }

        // Move offset for next iteration
        $offset += $limit;
        
    } while ($pcount > 0);
    //close file if specified
    if(isset($params['-filename']) && isset($fh)){
        @fclose($fh);
        return $rowcount;
    }
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
