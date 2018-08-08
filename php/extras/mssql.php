<?php
//settings for old PHP versions that use mssql_connect
if((integer)phpversion() < 7){
	ini_set('mssql.charset', 'UTF-8');
	ini_set('mssql.max_persistent',5);
	ini_set('mssql.secure_connection ',0);
	ini_set ( 'mssql.textlimit' , '65536' );
	ini_set ( 'mssql.textsize' , '65536' );
}
global $mssql;
$mssql=array();
//---------- begin function mssqlListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function mssqlListRecords($params=array()){
	$params['-database']='mssql';
	return databaseListRecords($params);
}
//---------- begin function mssqlParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=mssqlParseConnectParams($params);
*/
function mssqlParseConnectParams($params=array()){
	global $CONFIG;
	//dbhost
	if(!isset($params['-dbhost'])){
		if(isset($CONFIG['dbhost_mssql'])){
			$params['-dbhost']=$CONFIG['dbhost_mssql'];
			$params['-dbhost_source']="CONFIG dbhost_mssql";
		}
		elseif(isset($CONFIG['mssql_dbhost'])){
			$params['-dbhost']=$CONFIG['mssql_dbhost'];
			$params['-dbhost_source']="CONFIG mssql_dbhost";
		}
	}
	else{
		$params['-dbhost_source']="passed in";
	}
	$CONFIG['mssql_dbhost']=$params['-dbhost'];
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_mssql'])){
			$params['-dbuser']=$CONFIG['dbuser_mssql'];
			$params['-dbuser_source']="CONFIG dbuser_mssql";
		}
		elseif(isset($CONFIG['mssql_dbuser'])){
			$params['-dbuser']=$CONFIG['mssql_dbuser'];
			$params['-dbuser_source']="CONFIG mssql_dbuser";
		}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_mssql'])){
			$params['-dbpass']=$CONFIG['dbpass_mssql'];
			$params['-dbpass_source']="CONFIG dbpass_mssql";
		}
		elseif(isset($CONFIG['mssql_dbpass'])){
			$params['-dbpass']=$CONFIG['mssql_dbpass'];
			$params['-dbpass_source']="CONFIG mssql_dbpass";
		}
	}
	else{
		$params['-dbpass_source']="passed in";
	}

	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['mssql_connect'])){
			$params['-connect']=$CONFIG['mssql_connect'];
			$params['-connect_source']="CONFIG mssql_connect";
		}
		elseif(isset($CONFIG['connect_mssql'])){
			$params['-connect']=$CONFIG['connect_mssql'];
			$params['-connect_source']="CONFIG connect_mssql";
		}
		else{
			//build connect - https://docs.microsoft.com/en-us/sql/connect/php/connection-options
			//database - dbname
			$dbname='';
			if(isset($params['-dbname'])){$dbname=$params['-dbname'];}
			elseif(isset($CONFIG['mssql_dbname'])){$dbname=$CONFIG['mssql_dbname'];}
			elseif(isset($CONFIG['dbname_mssql'])){$dbname=$CONFIG['dbname_mssql'];}
			$CONFIG['mssql_dbname']=$dbname;
			if(!strlen($dbname)){return $params;}
			//character_set
			$character_set='UTF-8';
			if(isset($params['-character_set'])){$character_set=$params['-character_set'];}
			elseif(isset($CONFIG['mssql_character_set'])){$character_set=$CONFIG['mssql_character_set'];}
			elseif(isset($CONFIG['character_set_mssql'])){$character_set=$CONFIG['character_set_mssql'];}
			//return_dates_as_strings
			$return_dates_as_strings=true;
			if(isset($params['-return_dates_as_strings'])){$return_dates_as_strings=$params['-return_dates_as_strings'];}
			elseif(isset($CONFIG['mssql_return_dates_as_strings'])){$return_dates_as_strings=$CONFIG['mssql_return_dates_as_strings'];}
			elseif(isset($CONFIG['return_dates_as_strings_mssql'])){$return_dates_as_strings=$CONFIG['return_dates_as_strings_mssql'];}
			$connect_data = array(
				'Database'				=> $dbname,
				'CharacterSet' 			=> $character_set,
				'ReturnDatesAsStrings' 	=> $return_dates_as_strings
			);
			//application_intent - ReadOnly or readWrite - Defaults to ReadWrite
			if(isset($params['-application_intent'])){$connect_data['ApplicationIntent']=$params['-application_intent'];}
			elseif(isset($CONFIG['mssql_application_intent'])){$connect_data['ApplicationIntent']=$CONFIG['mssql_application_intent'];}
			elseif(isset($CONFIG['application_intent_mssql'])){$connect_data['ApplicationIntent']=$CONFIG['application_intent_mssql'];}
			//Encrypt - communication with SQL Server is encrypted (1 or true) or unencrypted (0 or false) - defaults to 0
			if(isset($params['-encrypt'])){$connect_data['Encrypt']=$params['-encrypt'];}
			elseif(isset($CONFIG['mssql_encrypt'])){$connect_data['Encrypt']=$CONFIG['mssql_encrypt'];}
			elseif(isset($CONFIG['encrypt_mssql'])){$connect_data['Encrypt']=$CONFIG['encrypt_mssql'];}
			//LoginTimeout - Specifies the number of seconds to wait before failing the connection attempt - defaults to no timeout
			if(isset($params['-login_timeout'])){$connect_data['LoginTimeout']=$params['-login_timeout'];}
			elseif(isset($CONFIG['mssql_login_timeout'])){$connect_data['LoginTimeout']=$CONFIG['mssql_login_timeout'];}
			elseif(isset($CONFIG['login_timeout_mssql'])){$connect_data['LoginTimeout']=$CONFIG['login_timeout_mssql'];}
			//TraceFile - Specifies the path for the file used for trace data
			if(isset($params['-trace_file'])){$connect_data['TraceFile']=$params['-trace_file'];}
			elseif(isset($CONFIG['mssql_trace_file'])){$connect_data['TraceFile']=$CONFIG['mssql_trace_file'];}
			elseif(isset($CONFIG['trace_file_mssql'])){$connect_data['TraceFile']=$CONFIG['trace_file_mssql'];}
			//TraceOn - ODBC tracing is enabled (1 or true) or disabled (0 or false) - defaults to false
			if(isset($params['-trace_on'])){$connect_data['TraceOn']=$params['-trace_on'];}
			elseif(isset($CONFIG['mssql_trace_on'])){$connect_data['TraceOn']=$CONFIG['mssql_trace_on'];}
			elseif(isset($CONFIG['trace_on_mssql'])){$connect_data['TraceOn']=$CONFIG['trace_on_mssql'];}
			//WSID - the name of the computer for tracing.
			if(isset($params['-wsid'])){$connect_data['WSID']=$params['-wsid'];}
			elseif(isset($CONFIG['mssql_wsid'])){$connect_data['WSID']=$CONFIG['mssql_wsid'];}
			elseif(isset($CONFIG['wsid_mssql'])){$connect_data['WSID']=$CONFIG['wsid_mssql'];}
			//TrustServerCertificate - trust (1 or true) or reject (0 or false) a self-signed server certificate - defaults to false
			if(isset($params['-trust_server_certificate'])){$connect_data['TrustServerCertificate']=$params['-trust_server_certificate'];}
			elseif(isset($CONFIG['mssql_trust_server_certificate'])){$connect_data['TrustServerCertificate']=$CONFIG['mssql_trust_server_certificate'];}
			elseif(isset($CONFIG['trust_server_certificate_mssql'])){$connect_data['TrustServerCertificate']=$CONFIG['trust_server_certificate_mssql'];}
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
	if(!isset($CONFIG['mssql_dbname']) && isset($params['-dbname'])){
		$CONFIG['mssql_dbname']=$params['-dbname'];
	}
	$CONFIG['mssql_dbname']=$dbname;
	return $params;
}


//---------- begin function mssqlDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of database.
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return connection resource and sets the global $dbh_mssql variable.
* @usage $dbh_mssql=mssqlDBConnect($params);
*/
function mssqlDBConnect($params=array()){
	$params=mssqlParseConnectParams($params);
	if(!isset($params['-dbname']) && !isset($params['-connect'])){
		echo "mssqlDBConnect error: no connect params".printValue($params);
		exit;
	}
	//php 7 and greater no longer use mssql_connect
	if((integer)phpversion()>=7){
		//$serverName = "serverName\sqlexpress"; //serverName\instanceName
		//If values for the UID and PWD keys are not specified, the connection will be attempted using Windows Authentication.
		if(!isset($params['-connect'])){
			echo "mssqlDBConnect error: no connect params".printValue($params);
			exit;
		}
		$params['phpversion']=phpversion();
		if(isset($params['-dbuser']) && strlen($params['-dbuser'])){
			$params['-connect']['UID']=$params['-dbuser'];
		}
		if(isset($params['-dbpass']) && strlen($params['-dbpass'])){
			$params['-connect']['PWD']=$params['-dbpass'];
		}
		if(isset($params['-single'])){
			$params['-connect']['ConnectionPooling']=false;
		}
		try{
			$dbh_mssql = sqlsrv_connect( $params['-dbhost'], $params['-connect']);
			if(!is_resource($dbh_mssql)){
				$errs=sqlsrv_errors();
				echo "mssqlDBConnect error:".printValue($errs).printValue($params);
				exit;
			}
			return $dbh_mssql;
		}
		catch (Exception $e) {
			echo "mssqlDBConnect exception" . printValue($e);
			exit;
		}
	}
	//php is not 7 or greater - use mssql_connect
	if(isset($params['-single'])){
		$dbh_single = mssql_connect($params['-dbhost'],$params['-dbuser'],$params['-dbpass']);
		if(!is_resource($dbh_single)){
			$err=mssql_get_last_message();
			echo "mssqlDBConnect single connect error:{$err}".printValue($params);
			exit;
		}
		return $dbh_single;
	}
	global $dbh_mssql;
	if(is_resource($dbh_mssql)){return $dbh_mssql;}

	try{
		$dbh_mssql = mssql_pconnect($params['-dbhost'],$params['-dbuser'],$params['-dbpass']);
		if(!is_resource($dbh_mssql)){
			$err=mssql_get_last_message();
			echo "mssqlDBConnect error:{$err}".printValue($params);
			exit;

		}
		if(isset($params['-dbname'])){
			$ok=mssql_select_db($params['-dbname'],$dbh_mssql);
			if (!$ok) {
				$err=mssql_get_last_message();
				echo "mssqlDBConnect error:{$err}".printValue($params);
				exit;
			}
		return $dbh_mssql;
		}
	}
	catch (Exception $e) {
		echo "mssqlDBConnect exception" . printValue($e);
		exit;
	}
}
//---------- begin function mssqlAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*   -table - name of the table to add to
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=mssqlAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function mssqlAddDBRecord($params=array()){
	global $USER;
	if(!isset($params['-table'])){return 'mssqlAddRecord error: No table specified.';}
	$fields=mssqlGetDBFieldInfo($params['-table'],$params);
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
	$recs=mssqlQueryResults($query,$params);
	if(isset($recs[0][$output_field])){return $recs[0][$output_field];}
	return $recs;
}
//---------- begin function mssqlEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*   -table - name of the table to add to
*   -where - filter criteria
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage $id=mssqlEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function mssqlEditDBRecord($params=array()){
	if(!isset($params['-table'])){return 'mssqlEditRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'mssqlEditRecord error: No where specified.';}
	global $USER;
	$fields=mssqlGetDBFieldInfo($params['-table'],$params);
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
	mssqlQueryResults($query,$params);
	return;
}
//---------- begin function mssqlGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=mssqlGetDBRecord(array('-table'=>'tesl));
*/
function mssqlGetDBRecord($params=array()){
	$recs=mssqlGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function mssqlGetDBRecords ----------
/**
* @describe retrieves records from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordsets
* @usage $ok=mssqlGetDBRecords(array('-table'=>'tesl));
*/
function mssqlGetDBRecords($params=array()){
	if(!is_array($params) && is_string($params)){
    	$params=array('-query'=>$params);
	}
	if(isset($params['-query'])){$query=$params['-query'];}
	else{
		if(!isset($params['-table'])){return 'mssqlGetRecords error: No table specified.';}
		if(!isset($params['-fields'])){$params['-fields']='*';}
		$fields=mssqlGetDBFieldInfo($params['-table'],$params);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!strlen(trim($v))){continue;}
			if(!isset($fields[$k])){continue;}
			if(is_array($params[$k])){
				$params[$k]=implode(':',$params[$k]);
			}
			$params[$k]=str_replace("'","''",$params[$k]);
			$ands[]="upper({$k})=upper('{$params[$k]}')";
		}
		$wherestr='';
		if(count($ands)){
			$wherestr='WHERE '.implode(' and ',$ands);
		}
		$query="SELECT {$params['-fields']}  FROM {$params['-table']} {$wherestr}";
		if(isset($params['-order'])){
			$query .= " ORDER BY {$params['-order']}";
		}
		if(isset($params['-limit'])){
			$query .= " limit {$params['-limit']}";
		}
	}
	return mssqlQueryResults($query,$params);
	}
//---------- begin function mssqlGetDBDatabases ----------
/**
* @describe returns an array of databases
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of databases
* @usage $tables=mssqlGetDBDatabases();
*/
function mssqlGetDBDatabases($params=array()){
	$query="exec sp_helpdb";
	$recs = mssqlQueryResults($query,$params);
	return $recs;
}
//---------- begin function mssqlGetSpaceUsed ----------
/**
* @describe returns an array of database_name,database_size,unallocated space as keys
* @param [$table] - returns name,rows,reserved,data,index_size,unused as keys if table is passed in
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of databases
* @usage $info=mssqlGetSpaceUsed();
*/
function mssqlGetSpaceUsed($table='',$params=array()){
	$query="exec sp_spaceused";
	if(strlen($table)){$query .= " {$table}";}
	$recs = mssqlQueryResults($query,$params);
	return $recs[0];
}
//---------- begin function mssqlGetServerInfo ----------
/**
* @describe returns an array of server info
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns a recordset of server info
* @usage $info=mssqlGetServerInfo();
*/
function mssqlGetServerInfo($params=array()){
	$query="exec sp_server_info";
	$recs = mssqlQueryResults($query,$params);
	$info=array();
	foreach($recs as $rec){
		$k=strtolower($rec['attribute_name']);
		$info[$k]=$rec['attribute_value'];
	}
	return $info;
}
//---------- begin function mssqlGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of tables
* @usage $tables=mssqlGetDBTables();
*/
function mssqlGetDBTables($params=array()){
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
		and table_catalog='{$CONFIG['mssql_dbname']}'
	ORDER BY
		table_name
	";
	$recs = mssqlQueryResults($query,$params);
	$tables=array();
	foreach($recs as $rec){$tables[]=$rec['name'];}
	return $tables;
}

function mssqlGetDBTablePrimaryKeys($table,$params=array()){
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
		tc.table_catalog = '{$CONFIG['mssql_dbname']}'
    	and tc.table_schema = 'dbo'
    	and tc.table_name = '{$table}'
    	and tc.constraint_type = 'PRIMARY KEY'
	";
	$tmp = mssqlQueryResults($query,$params);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
//---------- begin function mssqlGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=mssqlGetDBFieldInfo('test');
*/
function mssqlGetDBFieldInfo($table,$params=array()){
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
			table_catalog = '{$CONFIG['mssql_dbname']}'
    		and table_schema = 'dbo'
			and table_name = '{$table}'
		";
	$recs=mssqlQueryResults($query,$params);
	$fields=array();
	$pkeys=mssqlGetDBTablePrimaryKeys($table,$params);
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
//---------- begin function mssqlQueryResults ----------
/**
* @describe returns the mssql record set
* @param query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - returns records
*/
function mssqlQueryResults($query='',$params=array()){
	global $USER;
	global $dbh_mssql;
	if(!is_resource($dbh_mssql)){
		$dbh_mssql=mssqlDBConnect($params);
	}
	if(!$dbh_mssql){
    	$e=mssql_get_last_message();
    	debugValue(array("mssqlQueryResults Connect Error",$e));
    	return;
	}
	//php 7 and greater no longer use mssql_connect
	if((integer)phpversion()>=7){
		$data = sqlsrv_query($dbh_mssql,"SET ANSI_NULLS ON");
		$data = sqlsrv_query($dbh_mssql,"SET ANSI_WARNINGS ON");
		$data = sqlsrv_query($dbh_mssql,$query);
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
			$stmt=sqlsrv_query($dbh_mssql,"SELECT @@rowcount as rows");
			while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC) ) {
				$id=$row[0];
				break;
			}
			sqlsrv_free_stmt( $stmt);
			return $id;
		}
		$results = mssqlEnumQueryResults($data);
		return $results;
	}
	mssql_query("SET ANSI_NULLS ON",$dbh_mssql);
	mssql_query("SET ANSI_WARNINGS ON",$dbh_mssql);
	$data=@mssql_query($query,$dbh_mssql);
	if(!$data){return "MS SQL Query Error: " . mssql_get_last_message();}
	if(preg_match('/^insert /i',$query)){
    	//return the id inserted on insert statements
    	$id=databaseAffectedRows($data);
    	mssql_close($dbh_mssql);
    	return $id;
	}
	$results = mssqlEnumQueryResults($data);
	mssql_close($dbh_mssql);
	return $results;
}
//---------- begin function mssqlEnumQueryResults ----------
/**
* @describe enumerates through the data from a mssql_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function mssqlEnumQueryResults($data,$showblank=0,$fieldmap=array()){
	$results=array();
	//php 7 and greater no longer use mssql_connect
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
	//PHP is lower than 7 use mssql_fetch to retrieve
	do {
		while ($row = @mssql_fetch_assoc($data)){
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
	while ( @mssql_next_result($data) );
	return $results;
}
//---------- begin function mssqlExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage $ok=mssqlExecuteSQL("truncate table abc");
*/
function mssqlExecuteSQL($query,$params=array()){
	global $dbh_mssql;
	if(!is_resource($dbh_mssql)){
		$dbh_mssql=mssqlDBConnect($params);
	}
	if(!$dbh_mssql){
    	$e=mssql_get_last_message();
    	debugValue(array("mssqlExecuteSQL Connect Error",$e));
    	return;
	}
	try{
		$result=@mssql_query($query,$dbh_mssql);
		if(!$result){
			$e=mssql_get_last_message();
			mssql_close($dbh_mssql);
			debugValue(array("mssqlExecuteSQL Connect Error",$e));
			return;
		}
		mssql_close($dbh_mssql);
		return true;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue($err);
		return false;
	}
	return true;
}
