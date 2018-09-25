<?php
/*
	References:
		https://en.wikibooks.org/wiki/Converting_MySQL_to_PostgreSQL
		https://www.convert-in.com/mysql-to-postgres-types-mapping.htm
*/
//---------- begin function postgreListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function postgreListRecords($params=array()){
	$params['-database']='postgre';
	return databaseListRecords($params);
}
//---------- begin function postgreParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=postgreParseConnectParams($params);
*/
function postgreParseConnectParams($params=array()){
	global $CONFIG;
	//dbhost
	if(!isset($params['-dbhost'])){
		if(isset($CONFIG['dbhost_postgre'])){
			$params['-dbhost']=$CONFIG['dbhost_postgre'];
			$params['-dbhost_source']="CONFIG dbhost_postgre";
		}
		elseif(isset($CONFIG['postgre_dbhost'])){
			$params['-dbhost']=$CONFIG['postgre_dbhost'];
			$params['-dbhost_source']="CONFIG postgre_dbhost";
		}
	}
	else{
		$params['-dbhost_source']="passed in";
	}
	$CONFIG['postgre_dbhost']=$params['-dbhost'];
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_postgre'])){
			$params['-dbuser']=$CONFIG['dbuser_postgre'];
			$params['-dbuser_source']="CONFIG dbuser_postgre";
		}
		elseif(isset($CONFIG['postgre_dbuser'])){
			$params['-dbuser']=$CONFIG['postgre_dbuser'];
			$params['-dbuser_source']="CONFIG postgre_dbuser";
		}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_postgre'])){
			$params['-dbpass']=$CONFIG['dbpass_postgre'];
			$params['-dbpass_source']="CONFIG dbpass_postgre";
		}
		elseif(isset($CONFIG['postgre_dbpass'])){
			$params['-dbpass']=$CONFIG['postgre_dbpass'];
			$params['-dbpass_source']="CONFIG postgre_dbpass";
		}
	}
	else{
		$params['-dbpass_source']="passed in";
	}

	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['postgre_connect'])){
			$params['-connect']=$CONFIG['postgre_connect'];
			$params['-connect_source']="CONFIG postgre_connect";
		}
		elseif(isset($CONFIG['connect_postgre'])){
			$params['-connect']=$CONFIG['connect_postgre'];
			$params['-connect_source']="CONFIG connect_postgre";
		}
		else{
			//build connect - https://docs.microsoft.com/en-us/sql/connect/php/connection-options
			//database - dbname
			$dbname='';
			if(isset($params['-dbname'])){$dbname=$params['-dbname'];}
			elseif(isset($CONFIG['postgre_dbname'])){$dbname=$CONFIG['postgre_dbname'];}
			elseif(isset($CONFIG['dbname_postgre'])){$dbname=$CONFIG['dbname_postgre'];}
			$CONFIG['postgre_dbname']=$dbname;
			if(!strlen($dbname)){return $params;}
			//character_set
			$character_set='UTF-8';
			if(isset($params['-character_set'])){$character_set=$params['-character_set'];}
			elseif(isset($CONFIG['postgre_character_set'])){$character_set=$CONFIG['postgre_character_set'];}
			elseif(isset($CONFIG['character_set_postgre'])){$character_set=$CONFIG['character_set_postgre'];}
			//return_dates_as_strings
			$return_dates_as_strings=true;
			if(isset($params['-return_dates_as_strings'])){$return_dates_as_strings=$params['-return_dates_as_strings'];}
			elseif(isset($CONFIG['postgre_return_dates_as_strings'])){$return_dates_as_strings=$CONFIG['postgre_return_dates_as_strings'];}
			elseif(isset($CONFIG['return_dates_as_strings_postgre'])){$return_dates_as_strings=$CONFIG['return_dates_as_strings_postgre'];}
			$connect_data = array(
				'Database'				=> $dbname,
				'CharacterSet' 			=> $character_set,
				'ReturnDatesAsStrings' 	=> $return_dates_as_strings
			);
			//application_intent - ReadOnly or readWrite - Defaults to ReadWrite
			if(isset($params['-application_intent'])){$connect_data['ApplicationIntent']=$params['-application_intent'];}
			elseif(isset($CONFIG['postgre_application_intent'])){$connect_data['ApplicationIntent']=$CONFIG['postgre_application_intent'];}
			elseif(isset($CONFIG['application_intent_postgre'])){$connect_data['ApplicationIntent']=$CONFIG['application_intent_postgre'];}
			//Encrypt - communication with SQL Server is encrypted (1 or true) or unencrypted (0 or false) - defaults to 0
			if(isset($params['-encrypt'])){$connect_data['Encrypt']=$params['-encrypt'];}
			elseif(isset($CONFIG['postgre_encrypt'])){$connect_data['Encrypt']=$CONFIG['postgre_encrypt'];}
			elseif(isset($CONFIG['encrypt_postgre'])){$connect_data['Encrypt']=$CONFIG['encrypt_postgre'];}
			//LoginTimeout - Specifies the number of seconds to wait before failing the connection attempt - defaults to no timeout
			if(isset($params['-login_timeout'])){$connect_data['LoginTimeout']=$params['-login_timeout'];}
			elseif(isset($CONFIG['postgre_login_timeout'])){$connect_data['LoginTimeout']=$CONFIG['postgre_login_timeout'];}
			elseif(isset($CONFIG['login_timeout_postgre'])){$connect_data['LoginTimeout']=$CONFIG['login_timeout_postgre'];}
			//TraceFile - Specifies the path for the file used for trace data
			if(isset($params['-trace_file'])){$connect_data['TraceFile']=$params['-trace_file'];}
			elseif(isset($CONFIG['postgre_trace_file'])){$connect_data['TraceFile']=$CONFIG['postgre_trace_file'];}
			elseif(isset($CONFIG['trace_file_postgre'])){$connect_data['TraceFile']=$CONFIG['trace_file_postgre'];}
			//TraceOn - ODBC tracing is enabled (1 or true) or disabled (0 or false) - defaults to false
			if(isset($params['-trace_on'])){$connect_data['TraceOn']=$params['-trace_on'];}
			elseif(isset($CONFIG['postgre_trace_on'])){$connect_data['TraceOn']=$CONFIG['postgre_trace_on'];}
			elseif(isset($CONFIG['trace_on_postgre'])){$connect_data['TraceOn']=$CONFIG['trace_on_postgre'];}
			//WSID - the name of the computer for tracing.
			if(isset($params['-wsid'])){$connect_data['WSID']=$params['-wsid'];}
			elseif(isset($CONFIG['postgre_wsid'])){$connect_data['WSID']=$CONFIG['postgre_wsid'];}
			elseif(isset($CONFIG['wsid_postgre'])){$connect_data['WSID']=$CONFIG['wsid_postgre'];}
			//TrustServerCertificate - trust (1 or true) or reject (0 or false) a self-signed server certificate - defaults to false
			if(isset($params['-trust_server_certificate'])){$connect_data['TrustServerCertificate']=$params['-trust_server_certificate'];}
			elseif(isset($CONFIG['postgre_trust_server_certificate'])){$connect_data['TrustServerCertificate']=$CONFIG['postgre_trust_server_certificate'];}
			elseif(isset($CONFIG['trust_server_certificate_postgre'])){$connect_data['TrustServerCertificate']=$CONFIG['trust_server_certificate_postgre'];}
			$params['-connect']=$connect_data;
			$params['-connect_source']="manual";
		}
	}
	else{
		$params['-connect_source']="passed in";
	}
	if(isset($params['-connect']) && !is_array($params['-connect']) && strlen($params['-connect'])){
		//turn string into an array - key=value;key=value;
		$parts=preg_split('/[\;\,\&]+/',$params['-connect']);
		$params['-connect']=array();
		foreach($parts as $part){
			list($k,$v)=preg_split('/\=/',$part);
			$k=trim($k);
			$v=trim($v);
			$params['-connect'][$k]=$v;
		}
	}
	if(!isset($CONFIG['postgre_dbname']) && isset($params['-dbname'])){
		$CONFIG['postgre_dbname']=$params['-dbname'];
	}
	$CONFIG['postgre_dbname']=$dbname;
	return $params;
}


//---------- begin function postgreDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of database.
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return connection resource and sets the global $dbh_postgre variable.
* @usage $dbh_postgre=postgreDBConnect($params);
*/
function postgreDBConnect($params=array()){
	$params=postgreParseConnectParams($params);
	if(!isset($params['-dbname']) && !isset($params['-connect'])){
		echo "postgreDBConnect error: no connect params".printValue($params);
		exit;
	}
	global $dbh_postgre;
	if(is_resource($dbh_postgre)){return $dbh_postgre;}

	try{
		$dbh_postgre = pg_connect($params['-dbhost'],$params['-dbuser'],$params['-dbpass']);
		if(!is_resource($dbh_postgre)){
			$err=postgre_get_last_message();
			echo "postgreDBConnect error:{$err}".printValue($params);
			exit;

		}
		if(isset($params['-dbname'])){
			$ok=postgre_select_db($params['-dbname'],$dbh_postgre);
			if (!$ok) {
				$err=postgre_get_last_message();
				echo "postgreDBConnect error:{$err}".printValue($params);
				exit;
			}
		return $dbh_postgre;
		}
	}
	catch (Exception $e) {
		echo "postgreDBConnect exception" . printValue($e);
		exit;
	}
}
//---------- begin function postgreAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*   -table - name of the table to add to
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=postgreAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function postgreAddDBRecord($params=array()){
	global $USER;
	if(!isset($params['-table'])){return 'postgreAddRecord error: No table specified.';}
	$fields=postgreGetDBFieldInfo($params['-table'],$params);
	$sequence='';
	$owner='';
	foreach($fields as $field){
    	if(isset($field['sequence'])){
        	$sequence=$field['sequence'];
        	$owner=$field['owner'];
        	break;
		}
	}
	$opts=array();
	if(isset($fields['cdate'])){
		$params['cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	elseif(isset($fields['_cdate'])){
		$params['_cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($fields['cuser'])){
		$params['cuser']=$USER['username'];
	}
	elseif(isset($fields['_cuser'])){
		$params['_cuser']=$USER['username'];
	}
	$valstr='';
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		if(is_array($params[$k])){
            	$params[$k]=implode(':',$params[$k]);
		}
		switch(strtolower($fields[$k]['type'])){
        	case 'integer':
        	case 'number':
        		$opts['values'][]=$params[$k];
        	break;
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$params[$k]=date('Y-m-d',strtotime($v));
				}
        		$opts['values'][]="'{$params[$k]}'";
        	break;
        	default:
        		$opts['values'][]="'{$params[$k]}'";
        	break;
		}
        $params[$k]=str_replace("'","''",$params[$k]);

        $opts['fields'][]=trim(strtoupper($k));
	}
	$fieldstr=implode('","',$opts['fields']);
	$valstr=implode(',',$opts['values']);
	//determine output field - identity column to return
	$output='';
	foreach($fields as $output_field=>$info){
		if($info['identity']==1){
			$output=" OUTPUT INSERTED.{$output_field}";
			break;
		}
	}
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			("{$fieldstr}")
		{$output}
		VALUES(
			{$valstr}
		)
ENDOFQUERY;
	$recs=postgreQueryResults($query,$params);
	if(isset($recs[0][$output_field])){return $recs[0][$output_field];}
	return $recs;
}
//---------- begin function postgreCreateDBTable--------------------
/**
* @describe creates postgre table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=postgreCreateDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function postgreCreateDBTable($table='',$fields=array()){
	$function='createDBTable';
	if(strlen($table)==0){return "postgreCreateDBTable error: No table";}
	if(count($fields)==0){return "postgreCreateDBTable error: No fields";}
	if(isDBTable($table)){return 0;}
	global $CONFIG;
	//verify the wasql fields are there. if not add them
	if(!isset($fields['_id'])){$fields['_id']=databasePrimaryKeyFieldString();}
	if(!isset($fields['_cdate'])){
		$fields['_cdate']=databaseDataType('datetime').databaseDateTimeNow();
	}
	if(!isset($fields['_cuser'])){$fields['_cuser']="INT NOT NULL";}
	if(!isset($fields['_edate'])){
		$fields['_edate']=databaseDataType('datetime')." NULL";
	}
	if(!isset($fields['_euser'])){$fields['_euser']="INT NULL";}
	//lowercase the tablename and replace spaces with underscores
	$table=strtolower(trim($table));
	$table=str_replace(' ','_',$table);
	$ori_table=$table;
	if(isMssql()){$table="[{$table}]";}
	$query="create table {$table} (".PHP_EOL;
	//echo printValue($fields);exit;
	$lines=array();
	foreach($fields as $field=>$attributes){
		//datatype conversions
		$attributes=str_replace('tinyint','smallint',$attributes);
		$attributes=str_replace('mediumint','integer',$attributes);
		$attributes=str_replace('datetime','timestamp',$attributes);
		$attributes=str_replace('float','real',$attributes);
		$attributes=str_replace('tinyint','smallint',$attributes);
		//handle virual generated json field shortcut
		if(preg_match('/^(.+?)\ from\ (.+?)$/i',$attributes,$m)){
			list($efield,$jfield)=preg_split('/\./',$m[2],2);
			if(!strlen($jfield)){$jfield=$field;}
            $attributes="{$m[1]} GENERATED ALWAYS AS (TRIM(BOTH '\"' FROM json_extract({$efield},'$.{$jfield}')))";
		}
		//lowercase the fieldname and replace spaces with underscores

		$field=strtolower(trim($field));
		$field=str_replace(' ','_',$field);
		$lines[]= "	{$field} {$attributes}";
   	}
    $query .= implode(','.PHP_EOL,$lines).PHP_EOL;
    $query .= ")".PHP_EOL;
    echo $query;exit;
	$query_result=@databaseQuery($query);
	//echo $query . printValue($query_result);exit;
	//clear the cache
	clearDBCache(array('databaseTables','getDBFieldInfo','isDBTable'));
  	if(!isset($query_result['error']) && $query_result==true){
		//success creating table.  Now to through the fields and create any instant meta data found
		foreach($fields as $field=>$attributes){
        	instantDBMeta($ori_table,$field,$attributes);
		}
		return 1;
	}
  	else{
		return setWasqlError(debug_backtrace(),getDBError(),$query);
	}
}
//---------- begin function postgreEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*   -table - name of the table to add to
*   -where - filter criteria
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage $id=postgreEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function postgreEditDBRecord($params=array()){
	if(!isset($params['-table'])){return 'postgreEditRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'postgreEditRecord error: No where specified.';}
	global $USER;
	$fields=postgreGetDBFieldInfo($params['-table'],$params);
	$opts=array();
	if(isset($fields['edate'])){
		$params['edate']=strtoupper(date('Y-M-d H:i:s'));
	}
	elseif(isset($fields['_edate'])){
		$params['_edate']=strtoupper(date('Y-M-d H:i:s'));
	}
	if(isset($fields['euser'])){
		$params['euser']=$USER['username'];
	}
	elseif(isset($fields['_euser'])){
		$params['_euser']=$USER['username'];
	}
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		if($k=='cuser' || $k=='cdate'){continue;}
		if(is_array($params[$k])){
            $params[$k]=implode(':',$params[$k]);
		}
		switch(strtolower($fields[$k]['type'])){
        	case 'date':
				if($k=='edate' || $k=='_edate'){
					$params[$k]=date('Y-m-d',strtotime($v));
				}
        	break;
		}
        $params[$k]=str_replace("'","''",$params[$k]);
        $updates[]="upper({$k})=upper('{$params[$k]}')";
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	postgreQueryResults($query,$params);
	return;
}
//---------- begin function postgreGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=postgreGetDBCount(array('-table'=>'states'));
*/
function postgreGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=postgreGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function postgreGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=postgreGetDBRecord(array('-table'=>'tesl));
*/
function postgreGetDBRecord($params=array()){
	$recs=postgreGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function postgreGetDBRecords
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
*	<?=postgreGetDBRecords(array('-table'=>'notes'));?>
*	<?=postgreGetDBRecords("select * from myschema.mytable where ...");?>
*/
function postgreGetDBRecords($params){
	global $USER;
	global $CONFIG;
	if(empty($params['-table']) && !is_array($params) && (stringBeginsWith($params,"select ") || stringBeginsWith($params,"with "))){
		//they just entered a query
		$query=$params;
	}
	else{
		//determine fields to return
		if(!empty($params['-fields'])){
			if(!is_array($params['-fields'])){
				$params['-fields']=str_replace(' ','',$params['-fields']);
				$params['-fields']=preg_split('/\,/',$params['-fields']);
			}
			$params['-fields']=implode(',',$params['-fields']);
		}
		if(empty($params['-fields'])){$params['-fields']='*';}
		$fields=postgreGetDBFieldInfo($params['-table'],$params);
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
	    	$query .= " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
	    }
	}
	if(isset($params['-debug'])){return $query;}
	return postgreQueryResults($query,$params);
}
//---------- begin function postgreGetDBDatabases ----------
/**
* @describe returns an array of databases
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of databases
* @usage $tables=postgreGetDBDatabases();
*/
function postgreGetDBDatabases($params=array()){
	$query="exec sp_helpdb";
	$recs = postgreQueryResults($query,$params);
	return $recs;
}
//---------- begin function postgreGetSpaceUsed ----------
/**
* @describe returns an array of database_name,database_size,unallocated space as keys
* @param [$table] - returns name,rows,reserved,data,index_size,unused as keys if table is passed in
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of databases
* @usage $info=postgreGetSpaceUsed();
*/
function postgreGetSpaceUsed($table='',$params=array()){
	$query="exec sp_spaceused";
	if(strlen($table)){$query .= " {$table}";}
	$recs = postgreQueryResults($query,$params);
	return $recs[0];
}
//---------- begin function postgreGetServerInfo ----------
/**
* @describe returns an array of server info
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns a recordset of server info
* @usage $info=postgreGetServerInfo();
*/
function postgreGetServerInfo($params=array()){
	$query="exec sp_server_info";
	$recs = postgreQueryResults($query,$params);
	$info=array();
	foreach($recs as $rec){
		$k=strtolower($rec['attribute_name']);
		$info[$k]=$rec['attribute_value'];
	}
	return $info;
}
//---------- begin function postgreGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of tables
* @usage $tables=postgreGetDBTables();
*/
function postgreGetDBTables($params=array()){
	global $CONFIG;
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
		and table_catalog='{$CONFIG['postgre_dbname']}'
	ORDER BY
		table_name
	";
	$recs = postgreQueryResults($query,$params);
	$tables=array();
	foreach($recs as $rec){$tables[]=$rec['name'];}
	return $tables;
}

function postgreGetDBTablePrimaryKeys($table,$params=array()){
	global $CONFIG;
	$query="
	SELECT
		ccu.column_name
		,ccu.constraint_name
	FROM
		information_schema.table_constraints (nolock) as tc
    INNER JOIN
		information_schema.constraint_column_usage as ccu
		ON tc.constraint_name = ccu.constraint_name
	WHERE
		tc.table_catalog = '{$CONFIG['postgre_dbname']}'
    	and tc.table_schema = 'dbo'
    	and tc.table_name = '{$table}'
    	and tc.constraint_type = 'PRIMARY KEY'
	";
	$tmp = postgreQueryResults($query,$params);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
//---------- begin function postgreGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=postgreGetDBFieldInfo('test');
*/
function postgreGetDBFieldInfo($table,$params=array()){
	global $CONFIG;
	$query="
		SELECT
			COLUMN_NAME
			,ORDINAL_POSITION
			,COLUMN_DEFAULT
			,IS_NULLABLE
			,DATA_TYPE
			,CHARACTER_MAXIMUM_LENGTH
			,COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') as identity_field
		FROM
			INFORMATION_SCHEMA.COLUMNS (nolock)
		WHERE
			table_catalog = '{$CONFIG['postgre_dbname']}'
    		and table_schema = 'dbo'
			and table_name = '{$table}'
		";
	$recs=postgreQueryResults($query,$params);
	$fields=array();
	$pkeys=postgreGetDBTablePrimaryKeys($table,$params);
	foreach($recs as $rec){
		$name=strtolower($rec['column_name']);
		//name, type, length, num, default
		$fields[$name]=array(
		 	'name'		=> $name,
		 	'type'		=> $rec['data_type'],
			'length'	=> $rec['character_maximum_length'],
			'num'		=> $rec['ordinal_position'],
			'default'	=> $rec['column_default'],
			'nullable'	=> $rec['is_nullable'],
			'identity' 	=> $rec['identity_field']
		);
		//add primary_key flag
		if(in_array($name,$pkeys)){
			$fields[$name]['primary_key']=true;
		}
		else{
			$fields[$name]['primary_key']=false;
		}
	}
    ksort($fields);
	return $fields;
}
//---------- begin function postgreQueryResults ----------
/**
* @describe returns the postgre record set
* @param query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - returns records
*/
function postgreQueryResults($query='',$params=array()){
	global $USER;
	global $dbh_postgre;
	if(!is_resource($dbh_postgre)){
		$dbh_postgre=postgreDBConnect($params);
	}
	if(!$dbh_postgre){
    	$e=postgre_get_last_message();
    	debugValue(array("postgreQueryResults Connect Error",$e));
    	return;
	}
	//php 7 and greater no longer use postgre_connect
	if((integer)phpversion()>=7){
		$data = sqlsrv_query($dbh_postgre,"SET ANSI_NULLS ON");
		$data = sqlsrv_query($dbh_postgre,"SET ANSI_WARNINGS ON");
		$data = sqlsrv_query($dbh_postgre,$query);
		if( $data === false ) {
			$errs=sqlsrv_errors();
			if(isset($errs[0]['message'])){
				debugValue(array($errs[0]['message'],$params));
				return $errs[0]['message'];
			}
			debugValue(array($errs,$params));
			return printValue(array($errs,$params));
		}
		if(preg_match('/^insert /i',$query)){
			$stmt=sqlsrv_query($dbh_postgre,"SELECT @@rowcount as rows");
			while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC) ) {
				$id=$row[0];
				break;
			}
			sqlsrv_free_stmt( $stmt);
			return $id;
		}
		$results = postgreEnumQueryResults($data);
		return $results;
	}
	postgre_query("SET ANSI_NULLS ON",$dbh_postgre);
	postgre_query("SET ANSI_WARNINGS ON",$dbh_postgre);
	$data=@postgre_query($query,$dbh_postgre);
	if(!$data){return "MS SQL Query Error: " . postgre_get_last_message();}
	if(preg_match('/^insert /i',$query)){
    	//return the id inserted on insert statements
    	$id=databaseAffectedRows($data);
    	postgre_close($dbh_postgre);
    	return $id;
	}
	$results = postgreEnumQueryResults($data);
	postgre_close($dbh_postgre);
	return $results;
}
//---------- begin function postgreEnumQueryResults ----------
/**
* @describe enumerates through the data from a postgre_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function postgreEnumQueryResults($data,$showblank=0,$fieldmap=array()){
	$results=array();
	//php 7 and greater no longer use postgre_connect
	if((integer)phpversion()>=7){
		while( $row = sqlsrv_fetch_array( $data, SQLSRV_FETCH_ASSOC) ) {
			$crow=array();
			foreach($row as $key=>$val){
				if(!$showblank && !strlen(trim($val))){continue;}
				$key=strtolower($key);
				if(isset($fieldmap[$key])){$key=$fieldmap[$key];}
				if(preg_match('/(.+?)\:[0-9]{3,3}(PM|AM)$/i',$val,$tmatch)){
					$newval=$tmatch[1].' '.$tmatch[2];
					$crow[$key."_zulu"]=$val;
					$crow[$key."_utime"]=strtotime($newval);
					$val=date("Y-m-d H:i:s",$crow[$key."_utime"]);
            		}
				$crow[$key]=$val;
				}
	        $results[] = $crow;
		}
		sqlsrv_free_stmt( $data);
		return $results;
	}
	//PHP is lower than 7 use postgre_fetch to retrieve
	do {
		while ($row = @postgre_fetch_assoc($data)){
			$crow=array();
			foreach($row as $key=>$val){
				if(!$showblank && !strlen(trim($val))){continue;}
				$key=strtolower($key);
				if(isset($fieldmap[$key])){$key=$fieldmap[$key];}
				if(preg_match('/(.+?)\:[0-9]{3,3}(PM|AM)$/i',$val,$tmatch)){
					$newval=$tmatch[1].' '.$tmatch[2];
					$crow[$key."_zulu"]=$val;
					$crow[$key."_utime"]=strtotime($newval);
					$val=date("Y-m-d H:i:s",$crow[$key."_utime"]);
            		}
				$crow[$key]=$val;
				}
			//ksort($crow);
	        $results[] = $crow;
	    	}
		}
	while ( @postgre_next_result($data) );
	return $results;
}
//---------- begin function postgreExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage $ok=postgreExecuteSQL("truncate table abc");
*/
function postgreExecuteSQL($query,$params=array()){
	global $dbh_postgre;
	if(!is_resource($dbh_postgre)){
		$dbh_postgre=postgreDBConnect($params);
	}
	if(!$dbh_postgre){
    	$e=postgre_get_last_message();
    	debugValue(array("postgreExecuteSQL Connect Error",$e));
    	return;
	}
	try{
		$result=@postgre_query($query,$dbh_postgre);
		if(!$result){
			$e=postgre_get_last_message();
			postgre_close($dbh_postgre);
			debugValue(array("postgreExecuteSQL Connect Error",$e));
			return;
		}
		postgre_close($dbh_postgre);
		return true;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue($err);
		return false;
	}
	return true;
}
