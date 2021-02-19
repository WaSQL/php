<?php
/*
	sqlsrv requires php 7.2 or greater.
	in order to install sqlsrv you will need pecl and unixodbc 
	apt install php-pear
	apt install unixodbc
	apt install unixodbc-dev
	pecl install sqlsrv
	pecl install pdo_sqlsrv
	you will also need the ODBC driver for sqlsrv
	https://docs.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server?view=sql-server-2017

*/
//settings for old PHP versions that use mssql_connect
global $CONFIG;
if((integer)phpversion() < 7){
	ini_set('mssql.charset', 'UTF-8');
	ini_set('mssql.max_persistent',5);
	ini_set('mssql.secure_connection ',0);
	ini_set ( 'mssql.textlimit' , '65536' );
	ini_set ( 'mssql.textsize' , '65536' );
	$CONFIG['mssql_old']=1;
}
global $mssql;
$mssql=array();
//---------- begin function mssqlAddDBRecords--------------------
/**
* @describe inserts a record with said id in said table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
* @return count int
* @usage $ok=mssqlAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=mssqlAddDBRecords('comments',array('-recs'=>$recs);
*/
function mssqlAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("mssqlAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("mssqlAddDBRecords Error: either -csv or -recs is required");
	}
	$popts=array(
		'table'=>$table,
		'-chunk'=>$params['-chunk']
	);
	if(isset($params['-map'])){
		$popts['-map']=$params['-map'];
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("mssqlAddDBRecords Error: no such file: {$params['-csv']}");
		}
		$ok=processCSVLines($params['-csv'],'mssqlAddDBRecordsProcess',$popts);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("mssqlAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("mssqlAddDBRecords Error: no recs");
		}
		return mssqlAddDBRecordsProcess($params['-recs'],$popts);
	}
}
function mssqlAddDBRecordsProcess($recs,$params=array()){
	if(!isset($params['table'])){
		return debugValue("mssqlAddDBRecordsProcess Error: no table"); 
	}
	$table=$params['table'];
	$fieldinfo=mssqlGetDBFieldInfo($table,1);
	//if -map then remap specified fields
	if(isset($params['-map'])){
		foreach($recs as $i=>$rec){
			foreach($rec as $k=>$v){
				if(isset($params['-map'][$k])){
					unset($recs[$i][$k]);
					$k=$params['-map'][$k];
					$recs[$i][$k]=$v;
				}
			}
		}
	}
	$fields=array();
	foreach($recs as $i=>$rec){
		foreach($rec as $k=>$v){
			if(!isset($fieldinfo[$k])){continue;}
			if(!in_array($k,$fields)){$fields[]=$k;}
		}
	}
	$fieldstr=implode(',',$fields);
	$query="INSERT INTO {$table} ({$fieldstr}) VALUES ".PHP_EOL;
	$values=array();
	foreach($recs as $i=>$rec){
		foreach($rec as $k=>$v){
			if(!in_array($k,$fields)){
				unset($rec[$k]);
				continue;
			}
			if(!strlen($v)){
				$rec[$k]='NULL';
			}
			else{
				$v=mssqlEscapeString($v);
				$rec[$k]="'{$v}'";
			}
		}
		$values[]='('.implode(',',array_values($rec)).')';
	}
	$query.=implode(','.PHP_EOL,$values);
	//echo $query;exit;
	$ok=mssqlExecuteSQL($query);
	return count($values);
}
function mssqlEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function mssqlGetTableDDL ----------
/**
* @describe returns create script for specified table. NOTE: requires sp_GETDDL that you can get from https://www.stormrage.com/SQLStuff/sp_GetDDL_Latest.txt
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=mssqlGetTableDDL('sample');
*/
function mssqlGetTableDDL($table,$schema=''){
	if(!strlen($schema)){
		$schema=mssqlGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('mssqlGetTableDDL error: schema is not defined in config.xml');
		return null;
	}
	$schema=strtoupper($schema);
	$table=strtoupper($table);
	$query=<<<ENDOFQUERY
		exec sp_GetDDL '[{$schema}].[{$table}]' as ddl
ENDOFQUERY;
	$recs=mssqlQueryResults($query);
	if(isset($recs[0]['ddl'])){
		return $recs[0]['ddl'];
	}
	return $recs;
}
//---------- begin function mssqlGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=mssqlGetAllTableFields();
*/
function mssqlGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'mssqlGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=mssqlGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('mssqlGetAllTableFields error: schema is not defined in config.xml');
		return null;
	}
	$query=<<<ENDOFQUERY
		SELECT
			table_name as table_name,
			column_name as field_name,
			data_type as type_name
		FROM information_schema.columns
		WHERE
			table_schema='{$schema}'
		ORDER BY table_name,column_name
ENDOFQUERY;
	$recs=mssqlQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$field=strtolower($rec['field_name']);
		$type=strtolower($rec['type_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function mssqlGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=mssqlGetAllTableIndexes();
*/
function mssqlGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'mssqlGetAllTableIndexes');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=mssqlGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('mssqlGetAllTableIndexes error: schema is not defined in config.xml');
		return null;
	}
	//key_name,column_name,seq_in_index,non_unique
	$query=<<<ENDOFQUERY
	SELECT 
		t.name as table_name,
		i.name as index_name,
	    substring(column_names, 1, len(column_names)-1) as index_keys,
	    i.is_unique
	FROM sys.objects t
	    INNER JOIN sys.indexes i on t.object_id = i.object_id
	    CROSS APPLY (SELECT col.[name] + ', '
	                    FROM sys.index_columns ic
	                        INNER JOIN sys.columns col
	                            on ic.object_id = col.object_id
	                            and ic.column_id = col.column_id
	                    WHERE ic.object_id = t.object_id
	                        and ic.index_id = i.index_id
	                        and ic.is_included_column = 0
	                    ORDER BY key_ordinal
	                            for xml path ('') ) D (column_names)
	WHERE t.is_ms_shipped <> 1
		and index_id > 0
		and t.type = 'U'
		and schema_name(t.schema_id)='{$schema}'
	ORDER BY 1,2


ENDOFQUERY;
	$recs=mssqlQueryResults($query);
	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>';
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$table=str_replace("{$schema}.",'',$table);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
function mssqlGetDBSchema(){
	global $CONFIG;
	global $DATABASE;
	$params=mssqlParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	elseif(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['mssql_dbschema'])){return $CONFIG['mssql_dbschema'];}
	elseif(isset($CONFIG['mssql_schema'])){return $CONFIG['mssql_schema'];}
	return 'dbo';
}
//---------- begin function mssqlGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=mssqlGetDBRecordById('comments',7);
*/
function mssqlGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "mssqlGetDBRecordById Error: No Table";}
	if($id == 0){return "mssqlGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=mssqlGetDBRecord($recopts);
	return $rec;
}
//---------- begin function mssqlEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=mssqlEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function mssqlEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("mssqlEditDBRecordById Error: No Table");
	}
	//allow id to be a number or a set of numbers
	$ids=array();
	if(is_array($id)){
		foreach($id as $i){
			if(isNum($i) && !in_array($i,$ids)){$ids[]=$i;}
		}
	}
	else{
		$id=preg_split('/[\,\:]+/',$id);
		foreach($id as $i){
			if(isNum($i) && !in_array($i,$ids)){$ids[]=$i;}
		}
	}
	if(!count($ids)){return debugValue("mssqlEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("mssqlEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("mssqlEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return mssqlEditDBRecord($params);
}
//---------- begin function mssqlDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=mssqlDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function mssqlDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("mssqlDelDBRecordById Error: No Table");
	}
	//allow id to be a number or a set of numbers
	$ids=array();
	if(is_array($id)){
		foreach($id as $i){
			if(isNum($i) && !in_array($i,$ids)){$ids[]=$i;}
		}
	}
	else{
		$id=preg_split('/[\,\:]+/',$id);
		foreach($id as $i){
			if(isNum($i) && !in_array($i,$ids)){$ids[]=$i;}
		}
	}
	if(!count($ids)){return debugValue("mssqlDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return mssqlDelDBRecord($params);
}
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
	global $DATABASE;
	global $USER;
	if(!is_array($params)){$params=array();}
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^mssql/i',$k)){unset($CONFIG[$k]);}
		}
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			$params["-{$k}"]=$v;
		}
	}
	//check for user specific
	if(isUser() && strlen($USER['username'])){
		foreach($params as $k=>$v){
			if(stringEndsWith($k,"_{$USER['username']}")){
				$nk=str_replace("_{$USER['username']}",'',$k);
				unset($params[$k]);
				$params[$nk]=$v;
			}
		}
	}
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
//---------- begin function mssqlAddDBRecords ----------
/**
* @describe add multiple records at the same time using json_table.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*   -table - name of the table to add to
*   -list - list of records to add. Recommended list size in 500~1000 so that you keep the memory footprint small
*	[-dateformat] - string- format of date field values. defaults to 'YYYY-MM-DD HH24:MI:SS'
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return boolean
* @usage $ok=mssqlAddDBRecords(array('-table'=>'abc','-list'=>$list));
* @reference https://docs.microsoft.com/en-us/sql/relational-databases/json/json-data-sql-server?view=sql-server-2017
*/
function mssqlAddDBRecords_old($params=array()){
	if(!isset($params['-table'])){
		debugValue(array(
    		'function'=>"mssqlAddDBRecords",
    		'error'=>'No table specified'
    	));
    	return false;
    }
    if(!isset($params['-list']) || !is_array($params['-list'])){
		debugValue(array(
    		'function'=>"mssqlAddDBRecords",
    		'error'=>'No records (list) specified'
    	));
    	return false;
    }
    //defaults
    if(!isset($params['-dateformat'])){
    	$params['-dateformat']='YYYY-MM-DD HH24:MI:SS';
    }
	$j=array("items"=>$params['-list']);
    $json=json_encode($j);
    $info=mssqlGetDBFieldInfo($params['-table']);
    $fields=array();
    $jfields=array();
    $defines=array();
    foreach($recs[0] as $field=>$value){
    	if(!isset($info[$field])){continue;}
    	$fields[]=$field;
    	switch(strtolower($info[$field]['_dbtype'])){
    		case 'timestamp':
    		case 'date':
    			//date types have to be converted into a format that mssql understands
    			$jfields[]="to_date(substr({$field},1,19),'{$params['-dateformat']}' ) as {$field}";
    		break;
    		default:
    			$jfields[]=$field;
    		break;
    	}
    	$defines[]="{$field} varchar PATH '\$.{$field}'";
    }
    if(!count($fields)){return 'No matching Fields';}
    $fieldstr=implode(',',$fields);
    $jfieldstr=implode(',',$jfields);
    $definestr=implode(','.PHP_EOL,$defines);
    $query .= <<<ENDOFQ
    INSERT INTO {$params['-table']}
    	({$fieldstr})
    SELECT 
    	{$jfieldstr}
	FROM OPENJSON(
		?
		, '\$.items'
		WITH (
			{$definestr}
		)
	)
ENDOFQ;
	$dbh_mssql=mssqlDBConnect($params);
	$stmt = sqlsrv_prepare($dbh_mssql, $query,array($json));
	if (!($stmt)){
    	debugValue(array(
    		'function'=>"mssqlAddDBRecords",
    		'connection'=>$dbh_mssql,
    		'action'=>'oci_parse',
    		'mssql_error'=>sqlsrv_errors(),
    		'query'=>$query
    	));
    	oci_close($dbh_mssql);
    	return false;
    }
	if( sqlsrv_execute( $stmt ) === false ) {
		debugValue(array(
    		'function'=>"mssqlAddDBRecords",
    		'connection'=>$dbh_mssql,
    		'action'=>'oci_execute',
    		'stmt'=>$stmt,
    		'mssql_error'=>sqlsrv_errors(),
    		'query'=>$query
    	));
    	return false;
	}
	return true;
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
function mssqlEditDBRecord($params=array(),$id=0,$opts=array()){
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
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
//---------- begin function mssqlGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=mssqlGetDBCount(array('-table'=>'states'));
*/
function mssqlGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=mssqlGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
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
//---------- begin function mssqlGetDBRecords
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
*	<?=mssqlGetDBRecords(array('-table'=>'notes'));?>
*	<?=mssqlGetDBRecords("select * from myschema.mytable where ...");?>
*/
function mssqlGetDBRecords($params){
	global $USER;
	global $CONFIG;
	if(empty($params['-table']) && !is_array($params)){
		$params=trim($params);
		if(preg_match('/^(select|exec|with|explain|returning|show|call)[\t\s\ ]/i',$params)){
			//they just entered a query
			$query=$params;
			$params=array();
		}
		else{
			$ok=mssqlExecuteSQL($params);
			return $ok;
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
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
	    	//offset requires an order by
	    	if(!isset($params['-order'])){
	    		$query .= " ORDER BY 1";
	    	}
	    	$query .= " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
	    }
	}
	if(isset($params['-debug'])){return $query;}
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
	$params=mssqlParseConnectParams($params);
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
		and table_catalog='{$params['-dbname']}'
	ORDER BY
		table_name
	";
	$recs = mssqlQueryResults($query,$params);
	$tables=array();
	foreach($recs as $rec){$tables[]=$rec['name'];}
	return $tables;
}

function mssqlGetDBTablePrimaryKeys($table,$params=array()){
	$params=mssqlParseConnectParams($params);
	$schema=mssqlGetDBSchema();
	$parts=preg_split('/\./',$table);
	if(count($parts)==2){
		$schema=$parts[0];
		$table=$parts[1];
	}
	$schema=strtolower($schema);
	$table=strtolower($table);
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
		tc.table_catalog = '{$params['-dbname']}'
    	and lower(tc.table_schema) = '{$schema}'
    	and lower(tc.table_name) = '{$table}'
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
	$params=mssqlParseConnectParams($params);
	$schema=mssqlGetDBSchema();
	$parts=preg_split('/\./',$table);
	if(count($parts)==2){
		$schema=$parts[0];
		$table=$parts[1];
	}
	$schema=strtolower($schema);
	$table=strtolower($table);
	$query="
		SELECT
			COLUMN_NAME
			,numeric_precision
			,COLUMN_DEFAULT
			,IS_NULLABLE
			,DATA_TYPE
			,CHARACTER_MAXIMUM_LENGTH
			,COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') as identity_field
		FROM
			INFORMATION_SCHEMA.COLUMNS (nolock)
		WHERE
			table_catalog = '{$params['-dbname']}'
    		and lower(table_schema) = '{$schema}'
			and lower(table_name) = '{$table}'
		ORDER BY ORDINAL_POSITION
		";
	$recs=mssqlQueryResults($query,$params);
	$fields=array();
	$pkeys=mssqlGetDBTablePrimaryKeys($table,$params);
	foreach($recs as $rec){
		$name=strtolower($rec['column_name']);
		$field=array(
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
//---------- begin function mssqlListDBDatatypes ----
/**
* @describe returns the data types for MS SQL
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function mssqlListDBDatatypes(){
	//default to mssql
	return <<<ENDOFDATATYPES
<div class="w_bold w_blue w_padtop">Text Types</div>
<div class="w_padleft">CHAR( n ) A fixed section from 0 to 255 characters long.</div>
<div class="w_padleft">VARCHAR( n ) A variable section from 0 to 255 characters long.</div>
<div class="w_padleft">TINYTEXT A string with a maximum length of 255 characters.</div>
<div class="w_padleft">TEXT A string with a maximum length of 65535 characters.</div>
<div class="w_padleft">MEDIUMTEXT A string with a maximum length of 16777215 characters.</div>
<div class="w_padleft">LONGTEXT A string with a maximum length of 4294967295 characters.</div>

<div class="w_bold w_blue w_padtop">Number Types</div>
<div class="w_padleft">TINYINT - Integer from 0 to 255</div>
<div class="w_padleft">SMALLINT - Integer from -32768 to 32767</div>
<div class="w_padleft">INT - Integer from -2147483648 to 2147483647</div>
<div class="w_padleft">BIGINT - Integer from -9223372036854775808 to 9223372036854775807</div>
<div class="w_padleft">FLOAT(n) - A small number with a floating decimal point.</div>
<div class="w_padleft">MONEY - Financial data type from -2^63 (-922 337 203 685 477.5808) to 2^63-1 (922 337 203 685 477.5807) with the precision of one ten-thousandth unit.</div>
<div class="w_padleft">SMALLMONEY - Financial data type from -2^31 (-214 748.3648) to 2^31-1 (214 748.3647) with the precision of one ten-thousandth unit.</div>
<div class="w_padleft">DECIMAL(p,s) or NUMERIC(p,s)  - Numeric data type with fixed precision and scale</div>
<div class="w_padleft">REAL - Numeric data type with float precision that is defined as a float(24).</div>

<div class="w_bold w_blue w_padtop">Date Types</div>
<div class="w_padleft">DATE YYYY-MM-DD.</div>
<div class="w_padleft">DATETIME YYYY-MM-DD HH:MM:SS.</div>
<div class="w_padleft">TIMESTAMP YYYYMMDDHHMMSS.</div>
<div class="w_padleft">TIME HH:MM:SS.</div>
ENDOFDATATYPES;
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
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
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
		$results = mssqlEnumQueryResults($data,$params);
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
	$results = mssqlEnumQueryResults($data,$params);
	mssql_close($dbh_mssql);
	return $results;
}
//---------- begin function mssqlEnumQueryResults ----------
/**
* @describe enumerates through the data from a mssql_query call
* @exclude - used for internal user only
* @param data resource
* @param params array - key/value pair or options
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* 	[-logfile] - if you pass in a logfile then it will write the query then the count every 5000 rows.
* @return array
*	returns records
*/
function mssqlEnumQueryResults($data,$params=array()){
	$header=0;
	unset($fh);
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
			odbc_free_result($result);
			return 'hanaQueryResults error: Failed to open '.$params['-filename'];
			exit;
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	$recs=array();
	$i=0;
	//php 7 and greater no longer use mssql_connect
	if((integer)phpversion()>=7){
		while( $row = sqlsrv_fetch_array( $data, SQLSRV_FETCH_ASSOC) ) {
			$rec=array();
			foreach($row as $key=>$val){
				$key=strtolower($key);
				if(preg_match('/(.+?)\:[0-9]{3,3}(PM|AM)$/i',$val,$tmatch)){
					$newval=$tmatch[1].' '.$tmatch[2];
					$crow[$key."_zulu"]=$val;
					$crow[$key."_utime"]=strtotime($newval);
					$val=date("Y-m-d H:i:s",$crow[$key."_utime"]);
            	}
				$rec[$key]=$val;
			}
			if(isset($fh) && is_resource($fh)){
	        	if($header==0){
	            	$csv=arrays2CSV(array($rec));
	            	$header=1;
	            	//add UTF-8 byte order mark to the beginning of the csv
					$csv="\xEF\xBB\xBF".$csv;
				}
				else{
	            	$csv=arrays2CSV(array($rec),array('-noheader'=>1));
				}
				$csv=preg_replace('/[\r\n]+$/','',$csv);
				fwrite($fh,$csv."\r\n");
				$i+=1;
				if(isset($params['-logfile']) && file_exists($params['-logfile']) && $i % 5000 == 0){
					appendFileContents($params['-logfile'],$i.PHP_EOL);
				}
				if(isset($params['-process'])){
					$ok=call_user_func($params['-process'],$rec);
				}
				continue;
			}
			elseif(isset($params['-process'])){
				$ok=call_user_func($params['-process'],$rec);
				$x++;
				continue;
			}
			else{
				$recs[] = $rec;
			}
	        
		}
		sqlsrv_free_stmt( $data);
		if(isset($fh) && is_resource($fh)){
			@fclose($fh);
			if(isset($params['-logfile']) && file_exists($params['-logfile'])){
				$elapsed=microtime(true)-$starttime;
				appendFileContents($params['-logfile'],"Line count:{$i}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
			}
			return $i;
		}
		return $recs;
	}
	//PHP is lower than 7 use mssql_fetch to retrieve
	do {
		while ($row = @mssql_fetch_assoc($data)){
			$rec=array();
			foreach($row as $key=>$val){
				$key=strtolower($key);
				if(preg_match('/(.+?)\:[0-9]{3,3}(PM|AM)$/i',$val,$tmatch)){
					$newval=$tmatch[1].' '.$tmatch[2];
					$crow[$key."_zulu"]=$val;
					$crow[$key."_utime"]=strtotime($newval);
					$val=date("Y-m-d H:i:s",$crow[$key."_utime"]);
            	}
				$rec[$key]=$val;
			}
			if(isset($fh) && is_resource($fh)){
	        	if($header==0){
	            	$csv=arrays2CSV(array($rec));
	            	$header=1;
	            	//add UTF-8 byte order mark to the beginning of the csv
					$csv="\xEF\xBB\xBF".$csv;
				}
				else{
	            	$csv=arrays2CSV(array($rec),array('-noheader'=>1));
				}
				$csv=preg_replace('/[\r\n]+$/','',$csv);
				fwrite($fh,$csv."\r\n");
				$i+=1;
				if(isset($params['-logfile']) && file_exists($params['-logfile']) && $i % 5000 == 0){
					appendFileContents($params['-logfile'],$i.PHP_EOL);
				}
				continue;
			}
			elseif(isset($params['-process'])){
				$ok=call_user_func($params['-process'],$rec);
				$x++;
				continue;
			}
	        else{
	        	$recs[] = $rec;
	        }
	    }
	}
	while ( @mssql_next_result($data) );
	//close file?
	if(isset($fh) && is_resource($fh)){
		@fclose($fh);
		if(isset($params['-logfile']) && file_exists($params['-logfile'])){
			$elapsed=microtime(true)-$starttime;
			appendFileContents($params['-logfile'],"Line count:{$i}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
		}
		return $i;
	}
	return $recs;
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
	//php 7 and greater no longer use mssql_connect
	if((integer)phpversion()>=7){
		$dbh_mssql=mssqlDBConnect($params);
		$stmt = sqlsrv_prepare($dbh_mssql, $query,array($json));
		if (!($stmt)){
	    	debugValue(array(
	    		'function'=>"mssqlExecuteSQL",
	    		'connection'=>$dbh_mssql,
	    		'action'=>'oci_parse',
	    		'mssql_error'=>sqlsrv_errors(),
	    		'query'=>$query
	    	));
	    	oci_close($dbh_mssql);
	    	return false;
	    }
		if( sqlsrv_execute( $stmt ) === false ) {
			debugValue(array(
	    		'function'=>"mssqlExecuteSQL",
	    		'connection'=>$dbh_mssql,
	    		'action'=>'oci_execute',
	    		'stmt'=>$stmt,
	    		'mssql_error'=>sqlsrv_errors(),
	    		'query'=>$query
	    	));
	    	return false;
		}
		return true;
	}
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
//---------- begin function mssqlNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function mssqlNamedQuery($name){
	switch(strtolower($name)){
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