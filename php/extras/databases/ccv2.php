<?php

/*
	dbhost: https://backoffice.c1kdh3pw2n-doterrain1-d1-public.model-t.cc.commerce.ondemand.com

	ccv2 can be queried using SQL like a database.
	Usage:
		<database
	        name="ccv2"
	        dbhost="{backoffice URL}"
	        dbtype="ccv2"
	        dbuser="{username}"
	        dbpass="{password}"
	    />
*/

//---------- begin function ccv2GetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=ccv2GetTableDDL('sample');
*/
function ccv2GetTableDDL($table,$schema=''){
	$recs=array();
	return $recs;
}

function ccv2GetDBIndexes($tablename=''){
	return ccv2GetDBTableIndexes($tablename);
}

function ccv2GetDBTableIndexes($tablename=''){
	global $DATABASE;
	global $CONFIG;
	//echo $query.printValue($params);exit;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'function'=>'ccv2GetDBTables'
	);
	$db=$DATABASE[$CONFIG['db']];
	if(stringContains($tablename,'.')){
		list($schema,$tablename)=preg_split('/\./',$tablename,2);
	}
	else{$schema='DBO';}
	$query=<<<ENDOFQUERY
	SELECT 
	     t.name as table_name,
	     ind.name as key_name,
	     col.name as column_name,
	     ind.is_primary_key as is_primary,
	     ind.is_unique,
	     ic.key_ordinal as seq_in_index
	FROM 
	     sys.indexes ind 
	INNER JOIN 
	     sys.index_columns ic ON  ind.object_id = ic.object_id and ind.index_id = ic.index_id 
	INNER JOIN 
	     sys.columns col ON ic.object_id = col.object_id and ic.column_id = col.column_id 
	INNER JOIN 
	     sys.tables t ON ind.object_id = t.object_id 
	WHERE 
	     t.is_ms_shipped = 0 
	     and upper(schema_name(t.schema_id))='{$schema}'
	     and upper(t.name)='{$tablename}'
	ORDER BY 
	     ind.is_primary_key desc,ic.key_ordinal
ENDOFQUERY;
	$recs=ccv2QueryResults($query);
	//echo $query.printValue($recs);exit;
	return $recs;
}

//---------- begin function ccv2IsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_ccv2,dbuser_ccv2, and dbpass_ccv2
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(ccv2IsDBTable('abc')){...}
*/
function ccv2IsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "ccv2IsDBTable error: No table";
		return null;
	}
	$tables=ccv2GetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function ccv2GetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_ccv2,dbuser_ccv2, and dbpass_ccv2
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of table names
* @usage $tables=ccv2GetDBTables();
*/
function ccv2GetDBTables($params=array()){
	global $DATABASE;
	global $CONFIG;
	//echo $query.printValue($params);exit;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'function'=>'ccv2GetDBTables'
	);
	$db=$DATABASE[$CONFIG['db']];
	$query="
	SELECT
		table_catalog as dbname
        ,table_schema as owner
        ,table_name as name
        ,table_type as type
	FROM
		information_schema.tables
	WHERE
		table_type='BASE TABLE'
		and table_catalog='{$db['dbname']}'
	ORDER BY
		table_name
	";
	$recs = ccv2QueryResults($query,$params);
	$tables=array();
	foreach($recs as $rec){$tables[]=$rec['owner'].'.'.$rec['name'];}
	return $tables;
}
//---------- begin function ccv2GetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_ccv2,dbuser_ccv2, and dbpass_ccv2
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*	[-getmeta] - boolean - if true returns info in _fielddata table for these fields - defaults to false
*	[-field] - if this has a value return only this field - defaults to blank
*	[-force] - clear cache and refetch info
* @return boolean returns true on success
* @usage $fieldinfo=ccv2GetDBFieldInfo('abcschema.abc');
*/
function ccv2GetDBFieldInfo($table,$params=array()){
	global $DATABASE;
	global $CONFIG;
	$db=$DATABASE[$CONFIG['db']];
	$parts=preg_split('/\./',$table);
	if(count($parts)==2){
		$schema=$parts[0];
		$table=$parts[1];
	}
	$schema=strtolower($schema);
	$table=strtolower($table);
	$query="
		SELECT
			table_schema
			,column_name
			,numeric_precision
			,column_default
			,is_nullable
			,data_type
			,character_maximum_length
			,COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') as identity_field
		FROM
			information_schema.columns (nolock)
		WHERE
			table_catalog = '{$db['dbname']}'
			and LOWER(table_name) = '{$table}'
		ORDER BY ordinal_position
		";
	$recs=ccv2QueryResults($query,$params);
	$fields=array();
	//$pkeys=ccv2GetDBTablePrimaryKeys($table,$params);
	$pkeys=array();
	foreach($recs as $rec){
		$name=strtolower($rec['column_name']);
		$field=array(
			'schema'=>$rec['table_schema'],
			'table'	=> $table,
			'_dbtable'	=> $table,
			'name'	=> $name,
			'_dbfield'	=> strtolower($name),
			'type'	=> $rec['data_type'],
			'_dbtype'	=> $rec['data_type'],
			'precision'	=> $rec['numeric_precision'],
			'length'	=> $rec['character_maximum_length'],
			'_dblength'	=> $rec['character_maximum_length'],
			'default'	=> $rec['column_default'],
			'nullable'	=> $rec['is_nullable'],
			'identity' 	=> $rec['identity_field']
		);
		//add primary_key flag
		if(in_array($name,$pkeys)){
			$field['primary_key']=true;
		}
		else{
			$field['primary_key']=false;
		}
		$field['_dbtype']=$field['_dbtype_ex']=strtolower($field['type']);
		if($field['precision'] > 0){
			$field['_dbtype_ex']=strtolower("{$field['type']}({$field['precision']})");
		}
		elseif($field['length'] > 0 && preg_match('/(char|text|blob)/i',$field['_dbtype'])){
			$field['_dbtype_ex']=strtolower("{$field['type']}({$field['length']})");
		}
		$field['_dblength']=$field['length'];
	    $fields[$name]=$field;
	}
    ksort($fields);
	return $fields;
}

//---------- begin function ccv2QueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_ccv2,dbuser_ccv2, and dbpass_ccv2
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage $recs=ccv2QueryResults('select top 50 * from abcschema.abc');
*/
function ccv2QueryResults($query,$params=array()){
	global $DATABASE;
	global $CONFIG;
	//echo $query.printValue($params);exit;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'ccv2QueryResults'
	);
	$db=$DATABASE[$CONFIG['db']];
	$evalstr="return ccv2GetAuthHeaders();";
	$headers=getStoredValue($evalstr,1,1.0);
	if(!is_array($headers)){
		$progpath=dirname(__FILE__);
		$local="{$progpath}/temp/" . md5($CONFIG['name'].$evalstr) . '.gsv';
		unlink($local);
		return $headers;
	}
	$url=$db['dbhost'].'/hac/console/flexsearch/execute';
	$ccv2params=array(
		'-method'=>'POST',
		'-headers'=>$headers,
		'-json'=>1,
		"flexibleSearchQuery"=>"",
	    "sqlQuery"=>$query,
	    "user"=>$db['dbuser'],
	    "locale"=>"en",
	    "commit"=>"False",
	    "maxCount"=>"5000"
	);
	$json=encodeJSON($json);
	$post=postURL($url,$ccv2params);
	if($post['curl_info']['http_code'] >= 400){return 'ERROR:'.printValue($post);}
	if(isset($post['json_array']['exception']['sqlserverError'])){
		return 'ERROR: '.printValue($post['json_array']['exception']['sqlserverError']);
	}
	$recs_count=0;
    $recs=array();
    if(isset($params['-filename']) && file_exists($params['-filename'])){
    	unlink($params['-filename']);
    }
    $loop=0;
    $fieldinfo=array();
	if(isset($post['json_array']['resultList'][0]) && isset($post['json_array']['headers'][0])){
		foreach($post['json_array']['resultList'] as $vals){
			$rec=array();
			foreach($post['json_array']['headers'] as $i=>$field){
				$rec[$field]=$vals[$i];
			}
			$recs[]=$rec;
			$recs_count+=1;
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
			$recs=array();
		}
	}
	if(isset($params['-filename'])){
		return $recs_count;
	}
	return $recs;
}

//---------- begin function ccv2GetDBRecords
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
* @return array - set of records
* @usage
*	ccv2GetDBRecords(array('-table'=>'notes'));
*	ccv2GetDBRecords("select * from myschema.mytable where ...");
*/
function ccv2GetDBRecords($params){
	global $USER;
	global $CONFIG;
	//echo "ccv2GetDBRecords".printValue($params);exit;
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
		$fields=ccv2GetDBFieldInfo($params['-table']);
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
	$recs=ccv2QueryResults($query,$params);
	//echo '<hr>'.$query.printValue($params).printValue($recs);
	return $recs;
}
//---------- begin function ccv2GetDBRecordsCount ----------
/**
* @describe retrieves records from DB based on params
* @param $params array
* 	-table 	  - table to query
* @return array recordsets
* @usage $cnt=ccv2GetDBRecordsCount(array('-table'=>'tesl));
*/
function ccv2GetDBRecordsCount($params=array()){
	$params['-fields']='count(*) cnt';
	if(isset($params['-order'])){unset($params['-order']);}
	if(isset($params['-limit'])){unset($params['-limit']);}
	if(isset($params['-offset'])){unset($params['-offset']);}
	$recs=ccv2GetDBRecords($params);
	return $recs[0]['cnt'];
}
function ccv2NamedQueryList(){
	return array(
		array(
			'code'=>'running_queries',
			'icon'=>'icon-spin4',
			'name'=>'Running Queries'
		),
		array(
			'code'=>'sessions',
			'icon'=>'icon-spin8',
			'name'=>'Sessions'
		),
		array(
			'code'=>'table_locks',
			'icon'=>'icon-lock',
			'name'=>'Table Locks'
		),
	);
}
//---------- begin function mssqlNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function ccv2NamedQuery($name,$str=''){
	switch(strtolower($name)){
		case 'kill':
			return "KILL {$str}";
		break;
		case 'running':
		case 'queries':
		case 'running_queries':
			return <<<ENDOFQUERY
SELECT
    p.spid, p.status, p.hostname, p.loginame, p.cpu, r.start_time, r.command,
    p.program_name, text 
FROM
    sys.dm_exec_requests AS r,
    master.dbo.sysprocesses AS p 
    CROSS APPLY sys.dm_exec_sql_text(p.sql_handle)
WHERE
    p.status NOT IN ('sleeping', 'background','suspended') 
AND r.session_id = p.spid
ENDOFQUERY;
		break;
		case 'sessions':
			return 'exec sp_who';
		break;
		case 'table_locks':
			//https://www.mssqltips.com/sqlservertip/2732/different-techniques-to-identify-blocking-in-sql-server/
			return <<<ENDOFQUERY
SELECT * from sys.dm_tran_locks
WHERE request_status = 'CONVERT'
ENDOFQUERY;
		break;
	}
}
/**
* @describe returns the headers needed to authenticate
* @return array
*/
function ccv2GetAuthHeaders(){
	global $ccv2GetAuthHeadersCache;
	global $DATABASE;
	global $CONFIG;
	$db=$DATABASE[$CONFIG['db']];
	$errors=array();
	if(!isset($db['dbuser'])){$errors[]='missing dbuser';}
	if(!isset($db['dbpass'])){$errors[]='missing dbpass';}
	if(!isset($db['dbhost'])){$errors[]='missing dbhost';}
	if(count($errors)){return 'ERRORS: '.implode(', ',$errors);}
	//check for cache
	if(isset($ccv2GetAuthHeadersCache[0])){
		return $ccv2GetAuthHeadersCache;
	}
	$url=$db['dbhost'].'/hac/login';
	$post=getURL($url,array('-method'=>'GET'));
	if($post['curl_info']['http_code'] >= 400){return 'ERROR:'.printValue($post);}
	/* get CSRF */
	$document = new DomDocument();
	$document->loadHTML($post['body']);
	$xpath = new DOMXPath($document);
	$meta = $xpath->evaluate('//meta[@name="_csrf"]/@content')->item(0);
	$csrf=$meta->value;
	//echo printValue($csrf->value).printValue($post);exit;
	$cookies=$post['headers']['set-cookie'];
	$headers=array();
	foreach($cookies as $cookie){
		if(stringContains($cookie,'session')){
			$headers[]="COOKIE:{$cookie}";
		}
	}
	/* j_spring_security_check */
	$url=$db['dbhost'].'/hac/j_spring_security_check';
	$params=array(
		'-method'=>'POST',
		'-headers'=>$headers,
		'j_username'=>$db['dbuser'],
		'j_password'=>$db['dbpass'],
		'_csrf'=>$csrf
	);
	$post=postURL($url,$params);
	if($post['curl_info']['http_code'] >= 400){return 'ERROR:'.printValue($post);}

	/* flexsearch */
	$cookies=$post['headers']['set-cookie'];
	$headers=array();
	foreach($cookies as $cookie){
		if(stringContains($cookie,'session')){
			$headers[]="COOKIE:{$cookie}";
		}
	}
	$params=array(
		'-method'=>'GET',
		'-headers'=>$headers
	);

	$url=$db['dbhost'].'/hac/console/flexsearch';
	$post=postURL($url,$params);
	if($post['curl_info']['http_code'] >= 400){return 'ERROR:'.printValue($post);}
	//echo printValue($post);exit;
	$document = new DomDocument();
	$document->loadHTML($post['body']);
	$xpath = new DOMXPath($document);
	$meta = $xpath->evaluate('//meta[@name="_csrf"]/@content')->item(0);
	$csrf=$meta->value;

	/* execute */
	$headers[]="X-CSRF-TOKEN:{$csrf}";
	$ccv2GetAuthHeadersCache=$headers;
	return $headers;
}
