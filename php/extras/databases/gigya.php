<?php

/*
	https://help.sap.com/docs/SAP_CUSTOMER_DATA_CLOUD/8b8d6fffe113457094a17701f63e3d6a/4143815a70b21014bbc5a10ce4041860.html
	gigya can be queried using SQL like a database.
	Usage:
		<database
	        name="gigya"
	        dbhost="{data-center}"
	        dbtype="gigya"
	        dbuser="{userKey}"
	        dbpass="{secretKey}"
	        dbkey="{apiKey}"
	    />
		
*/

function gigyaEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function gigyaGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=gigyaGetTableDDL('sample');
*/
function gigyaGetTableDDL($table,$schema=''){
	$recs=array();
	return $recs;
}
//---------- begin function gigyaGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=gigyaGetAllTableFields();
*/
function gigyaGetAllTableFields($schema=''){
	return array();
}
//---------- begin function gigyaGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=gigyaGetAllTableIndexes();
*/
function gigyaGetAllTableIndexes($schema=''){
	return array();
}
function gigyaGetDBSchema(){
	global $CONFIG;
	global $DATABASE;
	$params=gigyaParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	elseif(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['gigya_dbschema'])){return $CONFIG['gigya_dbschema'];}
	elseif(isset($CONFIG['gigya_schema'])){return $CONFIG['gigya_schema'];}
	return '';
}

function gigyaGetDBIndexes($tablename=''){
	return gigyaGetDBTableIndexes($tablename);
}

function gigyaGetDBTableIndexes($tablename=''){
	$recs=array();
	return $recs;
}

//---------- begin function gigyaIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(gigyaIsDBTable('abc')){...}
*/
function gigyaIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "gigyaIsDBTable error: No table";
		return null;
	}
	$tables=gigyaGetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function gigyaGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of table names
* @usage $tables=gigyaGetDBTables();
*/
function gigyaGetDBTables($params=array()){
	$tables=array(
		'accounts',
		'auditlog',
		'datastore',
		'dataflow',
		'dataflow_draft',
		'dataflow_draft_version',
		'dataflow_draft_version',
		'scheduling',
		'idx_job_status',
		'script'
	);
	return $tables;
}
//---------- begin function gigyaGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*	[-getmeta] - boolean - if true returns info in _fielddata table for these fields - defaults to false
*	[-field] - if this has a value return only this field - defaults to blank
*	[-force] - clear cache and refetch info
* @return boolean returns true on success
* @usage $fieldinfo=gigyaGetDBFieldInfo('abcschema.abc');
*/
function gigyaGetDBFieldInfo($tablename,$params=array()){
	$xrecs=gigyaQueryResults("SELECT * FROM {$tablename} limit 1");
	$recs=array();
	foreach($xrecs[0] as $k=>$v){
		$rec=array(
			'_dbfield'=>$k
		);
		$jv=decodeJson($v);
		if(is_array($jv)){
			$rec['_dbtype_ex']='json';
		}
		else{
			$rec['_dbtype_ex']='string';
		}
		$recs[]=$rec;
	}
	return $recs;
}

//---------- begin function gigyaQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage $recs=gigyaQueryResults('select top 50 * from abcschema.abc');
*/
function gigyaQueryResults($query,$params=array()){
	global $DATABASE;
	global $CONFIG;

	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'gigyaQueryResults'
	);
	$db=$DATABASE[$CONFIG['db']];
	$table='';
	if(preg_match('/([\s\r\n]+)from([\s\r\n]+)([a-z\_]+)/is',$query,$m)){
		switch(strtolower(trim($m[3]))){
			case 'accounts':
				$table='accounts';
			break;
			case 'audit':
			case 'auditlog':
				$table='audit';
			break;
			case 'ds':
			case 'data':
			case 'datastore':
				$table='ds';
			break;
			case 'dataflow':
			case 'dataflow_draft':
			case 'dataflow_version':
			case 'dataflow_draft_version':
			case 'scheduling':
			case 'idx_job_status':
			case 'script':
				$table='idx';
			break;
		}
	}
	if(!strlen($table)){return "Invalid Table name: ".printValue($m);}
	//echo "gigyaQueryResults".$query.printValue();exit;
	$url="https://{$table}.us1.gigya.com/{$table}.search";
	/*
		"totalCount": 10109796,
        "statusCode": 200,
        "errorCode": 0,
        "statusReason": "OK",
        "callId": "05a743768f25416d8f12835ff4f8dd3c",
        "time": "2023-09-09T14:17:58.294Z",
        "objectsCount": 300
        "results":[{},{},....]

        //to paginate use START and LIMIT
        	select * from auditlog START 10000 limit 10000

	*/
    //set START and LIMIT to paginate the results
    $offset=0;
    $limit=0;
    if(preg_match('/([\s\r\n]+)limit([\s\r\n]+)(.+)$/is',$query,$m)){
    	$limit=(integer)trim($m[3]);
    	$query=preg_replace('/([\s\r\n]+)limit([\s\r\n]+)(.+)$/is','',$query);
    }
    if(preg_match('/([\s\r\n]+)start([\s\r\n]+)(.+)$/is',$query,$m)){
    	$offset=(integer)trim($m[3]);
    	$query=preg_replace('/([\s\r\n]+)start([\s\r\n]+)(.+)$/is','',$query);
    }
    $poffset=0;
    $plimit=5000;
    //set a maxloops as a safety precaution - this will limit the total number of records to 25M rows
    $maxloops=5000;
    if($limit > 0 && $limit < $plimit){
    	$plimit=$limit;
    }
    $recs_count=0;
    $recs=array();
    if(isset($params['-filename']) && file_exists($params['-filename'])){
    	unlink($params['-filename']);
    }
    $loop=0;
    while($loop < $maxloops){
    	$loop+=1;
    	$cquery=$query." START {$poffset} LIMIT {$plimit}";
    	$postopts=array(
			'-method'=>'POST',
			'apiKey'=>$db['dbkey'],
			'userKey'=>$db['dbuser'],
			'secret'=>$db['dbpass'],
			'query'=>$cquery,
			'format'=>'json',
			'-json'=>1
		);
		//echo printValue($postopts);exit;
		$post=postURL($url,$postopts);
		//echo printValue($postopts).printValue($post);exit;
		//check for errorMessage
		if(isset($post['json_array']['errorMessage'])){
			return <<<ENDOFERROR
			<div class="w_red">{$post['json_array']['errorCode']} - {$post['json_array']['errorMessage']}</div>
			{$post['json_array']['errorDetails']}
ENDOFERROR;
		}
		if(!isset($post['json_array']['results']) && isset($post['json_array']['result'])){
			$post['json_array']['results']=$post['json_array']['result'];
		}
		if(isset($post['json_array']['resultCount']) && $post['json_array']['resultCount']==0){
			break;
		}
		if(!isset($post['json_array']['results'][0])){
			break;
		}
		if(!isset($post['json_array']['statusCode']) || $post['json_array']['statusCode'] != 200){
			echo printValue($post);exit;
			break;
		}
		if(isset($post['json_array']['results'])){
			foreach($post['json_array']['results'] as $result){
				$rec=array();
				foreach($result as $k=>$v){
					switch(strtolower($k)){
						case 'count':
						case 'count(*)':
							$k='count';
						break;
					}
					$k=str_replace('UID','uid',$k);
					$k=str_replace('ID','Id',$k);
					$k = strtolower(preg_replace('/([A-Z])/', '_$1', $k));
					if(is_array($v)){$v=encodeJson($v);}
					$rec[$k]=$v;
				}
				$recs[]=$rec;
				$recs_count+=1;
				if($limit > 0 and $recs_count >= $limit){
					break;
				}
			}
			if(isset($params['-filename'])){
				if(file_exists($params['-filename'])){
					$csv=arrays2CSV($recs,array('-noheader'=>1)).PHP_EOL;
					setFileContents($params['-filename'],$csv,1);
				}
				else{
					//new file - set utf8 bit and include header row
					$csv="\xEF\xBB\xBF".arrays2CSV($recs).PHP_EOL;
					setFileContents($params['-filename'],$csv);
				}
			}
		}
		if(stringContains($query,'count(*)')){
			break;
		}
		if(isset($post['json_array']['objectsCount']) && $post['json_array']['objectsCount'] < $plimit){
			break;
		}
		if($limit > 0 and $recs_count >= $limit){
			break;
		}
		$poffset+=$plimit;
	}
	if(isset($params['-filename'])){
		return $recs_count;
	}
	return $recs;
}

//---------- begin function gigyaGetDBRecords
/**
* @describe returns and array of records
* @param params array - requires either -table or a raw query instead of params
*	[-table] string - table name.  Use this with other field/value params to filter the results
*	[-limit] mixed - query record limit.  Defaults to CONFIG['paging'] if set in config.xml otherwise 25
*	[-offset] mixed - query offset limit
*	[-fields] mixed - fields to return
*	[-where] string - string to add to where clause
*	[-filter] string - string to add to where clause
*	[-host] - server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - set of records
* @usage
*	gigyaGetDBRecords(array('-table'=>'notes'));
*	gigyaGetDBRecords("select * from myschema.mytable where ...");
*/
function gigyaGetDBRecords($params){
	global $USER;
	global $CONFIG;
	//echo "gigyaGetDBRecords".printValue($params);exit;
	if(empty($params['-table']) && !is_array($params) && preg_match('/^(with|select|pragma)\ /is',trim($params))){
		//they just entered a query
		$query=$params;
		$params=array();
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
	}
	else{
		//determine fields to return
		if(!empty($params['-fields'])){
			if(!is_array($params['-fields'])){;
				$params['-fields']=preg_split('/\,/',$params['-fields']);
				foreach($params['-fields'] as $i=>$field){
					$params['-fields'][$i]=trim($field);
				}
			}
			$params['-fields']=implode(',',$params['-fields']);
		}
		if(empty($params['-fields'])){$params['-fields']='*';}
		//echo printValue($params);
		$fields=gigyaGetDBFieldInfo($params['-table']);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!strlen(trim($v))){continue;}
			if(!isset($fields[$k])){continue;}
			if(is_array($params[$k])){
	            $params[$k]=implode(':',$params[$k]);
			}
	        $params[$k]=str_replace("'","''",$params[$k]);
	        $v=strtoupper($params[$k]);
	        $ands[]="upper({$k})='{$v}'";
		}
		//check for -where
		if(!empty($params['-where'])){
			$ands[]= "({$params['-where']})";
		}
		if(isset($params['-filter'])){
			$ands[]= "({$params['-filter']})";
		}
		$wherestr='';
		if(count($ands)){
			$wherestr='WHERE '.implode(' and ',$ands);
		}
	    $query="SELECT {$params['-fields']} FROM {$params['-table']} {$wherestr}";
	    if(isset($params['-order'])){
    		$query .= " ORDER BY {$params['-order']}";
    	}
    	//offset and limit
    	if(!isset($params['-nolimit'])){
	    	$offset=isset($params['-offset'])?$params['-offset']:0;
	    	$limit=25;
	    	if(!empty($params['-limit'])){$limit=$params['-limit'];}
	    	elseif(!empty($CONFIG['paging'])){$limit=$CONFIG['paging'];}
	    	$query .= " LIMIT {$limit} OFFSET {$offset}";
	    }
	}
	if(isset($params['-debug'])){return $query;}
	if(isset($params['-queryonly'])){return $query;}
	$recs=gigyaQueryResults($query,$params);
	//echo '<hr>'.$query.printValue($params).printValue($recs);
	return $recs;
}
//---------- begin function gigyaGetDBRecordsCount ----------
/**
* @describe retrieves records from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordsets
* @usage $cnt=gigyaGetDBRecordsCount(array('-table'=>'tesl));
*/
function gigyaGetDBRecordsCount($params=array()){
	$params['-fields']='count(*) cnt';
	if(isset($params['-order'])){unset($params['-order']);}
	if(isset($params['-limit'])){unset($params['-limit']);}
	if(isset($params['-offset'])){unset($params['-offset']);}
	$recs=gigyaGetDBRecords($params);
	return $recs[0]['cnt'];
}
function gigyaNamedQueryList(){
	return array();
}
//---------- begin function gigyaNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function gigyaNamedQuery($name){
	$schema=gigyaGetDBSchema();
	switch(strtolower($name)){
		case 'tables':
			return <<<ENDOFQUERY
ENDOFQUERY;
		break;
	}
}
