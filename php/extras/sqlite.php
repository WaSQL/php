<?php

/*
	SQLITE Database functions
		http://php.net/manual/en/function.sqlite-query.php
*/
if(!function_exists('sqlite_popen')){
	echo "sqlite library is not loaded";
	exit;
}

//---------- begin function sqliteParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=sqliteParseConnectParams($params);
*/
function sqliteParseConnectParams($params=array()){
	global $CONFIG;
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_sqlite'])){
			$params['-dbname']=$CONFIG['dbname_sqlite'];
			$params['-dbname_source']="CONFIG dbname_sqlite";
		}
		elseif(isset($CONFIG['sqlite_dbname'])){
			$params['-dbname']=$CONFIG['sqlite_dbname'];
			$params['-dbname_source']="CONFIG sqlite_dbname";
		}
		else{return 'sqliteParseConnectParams Error: No dbname set';}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	//dbmode
	if(!isset($params['-dbmode'])){
		if(isset($CONFIG['dbmode_sqlite'])){
			$params['-dbmode']=$CONFIG['dbmode_sqlite'];
			$params['-dbmode_source']="CONFIG dbname_sqlite";
		}
		elseif(isset($CONFIG['sqlite_dbmode'])){
			$params['-dbmode']=$CONFIG['sqlite_dbmode'];
			$params['-dbmode_source']="CONFIG sqlite_dbname";
		}
	}
	else{
		$params['-dbmode_source']="passed in";
	}
	return $params;
}
//---------- begin function sqliteDBConnect ----------
/**
* @describe connects to a SQLITE database and returns the handle resource
* @param $param array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
*   [-single] - if you pass in -single it will connect using sqlite_connect instead of sqlite_pconnect and return the connection
* @return $dbh_sqlite resource - returns the sqlite connection resource
* @usage $dbh_sqlite=sqliteDBConnect($params);
* @usage  example of using -single
*
	$conn=sqliteDBConnect(array('-single'=>1));
	sqlite_autocommit($conn, FALSE);

	sqlite_exec($conn, $query1);
	sqlite_exec($conn, $query2);

	if (!sqlite_error()){
		sqlite_commit($conn);
	}
	else{
		sqlite_rollback($conn);
	}
	sqlite_close($conn);
*
*/
function sqliteDBConnect($params=array()){
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=sqliteParseConnectParams($params);
	if(!isset($params['-dbname'])){return $params;}
	if(!isset($params['-mode'])){$params['-mode']=0666;}
	if(isset($params['-single'])){
		$dbh_sqlite_single = sqlite_open($params['-dbname'],$params['-mode'],$err );
		if(!is_resource($dbh_sqlite_single)){
			echo "sqliteDBConnect single connect error:{$err}".printValue($params);
			exit;
		}
		return $dbh_sqlite_single;
	}
	global $dbh_sqlite;
	if(is_resource($dbh_sqlite)){return $dbh_sqlite;}

	try{
		$dbh_sqlite = sqlite_popen($params['-dbname'],$params['-mode'],$err );
		if(!is_resource($dbh_sqlite)){
			echo "sqliteDBConnect error:{$err}".printValue($params);
			exit;

		}
		return $dbh_sqlite;
	}
	catch (Exception $e) {
		echo "sqliteDBConnect exception" . printValue($e);
		exit;

	}
}
//---------- begin function sqliteIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(sqliteIsDBTable('abc')){...}
*/
function sqliteIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "sqliteIsDBTable error: No table";
		exit;
	}
	$table=strtolower($table);
	$dbh_sqlite=sqliteDBConnect($params);
	if(!is_resource($dbh_sqlite)){
		echo "sqliteDBConnect error".printValue($params);
		exit;
	}
	try{
		$result = sqlite_query($dbhandle, "SELECT name FROM sqlite_master WHERE type='table' and name = '{$table}';");
		while ($rec = sqlite_fetch_array($result, SQLITE_ASSOC)) {
			if(strtolower($rec['name']) == $table){return true;}
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "sqliteIsDBTable error: exception".printValue($err);
		exit;
	}
    return false;
}
//---------- begin function sqliteClearConnection ----------
/**
* @describe clears the sqlite database connection
* @return boolean returns true if query succeeded
* @usage $ok=sqliteClearConnection();
*/
function sqliteClearConnection(){
	global $dbh_sqlite;
	$dbh_sqlite='';
	return true;
}
//---------- begin function sqliteExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage $ok=sqliteExecuteSQL("truncate table abc");
*/
function sqliteExecuteSQL($query,$params=array()){
	$dbh_sqlite=sqliteDBConnect($params);
	if(!is_resource($dbh_sqlite)){
		echo "sqliteDBConnect error".printValue($params);
		exit;
	}
	try{
		$result=sqlite_exec($dbh_sqlite,$query,$err);
		if(!$result){
			debugValue($err);
			return false;
		}
		
		return true;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue($err);
		return false;
	}
	return true;
}
//---------- begin function sqliteAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=sqliteAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function sqliteAddDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){return 'sqliteAddRecord error: No table specified.';}
	$fields=sqliteGetDBFieldInfo($params['-table'],$params);
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
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		//take care of single quotes in value
		$v=str_replace("'","''",$v);
		switch(strtolower($fields[$k]['type'])){
        	case 'integer':
        	case 'number':
        		$opts['values'][]=$v;
        	break;
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$v=date('Y-m-d',strtotime($v));
				}
				$opts['values'][]="'{$v}'";
        	break;
        	default:
        		$opts['values'][]="'{$v}'";
        	break;
		}
        $opts['fields'][]=trim(strtoupper($k));
	}
	$fieldstr=implode('","',$opts['fields']);
	$valstr=implode(',',$opts['values']);
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			("{$fieldstr}")
		VALUES
			({$valstr})
ENDOFQUERY;
	$dbh_sqlite=sqliteDBConnect($params);
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteAddDBRecord Connect Error",$e));
    	return;
	}
	try{
		$result=sqlite_exec($dbh_sqlite,$query);
		if(!$result){
        	$err=array(
        		'error'	=> sqlite_errormsg($dbh_sqlite),
				'query'	=> $query
			);
			debugValue($err);
			return "sqliteAddDBRecord Error".printValue($err);
		}
		return sqlite_last_insert_rowid($dbh_sqlite);
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		$recs=array($err);
		//return $recs;
		
		debugValue(array("sqliteAddDBRecord Connect Error",$e));
		return "sqliteAddDBRecord Error".printValue($err);
	}
	
	return 0;
}
//---------- begin function sqliteEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
*   -table - name of the table to add to
*   -where - filter criteria
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage $id=sqliteEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function sqliteEditDBRecord($params){
	if(!isset($params['-table'])){return 'sqliteEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'sqliteEditDBRecord error: No where specified.';}
	global $USER;
	$fields=sqliteGetDBFieldInfo($params['-table'],$params);
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
		if(!isset($fields[$k])){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		//take care of single quotes in value
		$v=str_replace("'","''",$v);
		switch(strtolower($fields[$k]['type'])){
        	case 'date':
				if($k=='edate' || $k=='_edate'){
					$v=date('Y-m-d',strtotime($v));
				}
        	break;
		}

        $updates[]="{$k}='{$v}'";
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	return sqliteExecuteSQL($query,$params);
}
//---------- begin function sqliteReplaceDBRecord ----------
/**
* @describe updates or adds a record from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add/edit to the record
* @return integer returns the autoincriment key
* @usage $id=sqliteReplaceDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function sqliteReplaceDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){return 'sqliteAddRecord error: No table specified.';}
	$fields=sqliteGetDBFieldInfo($params['-table'],$params);
	$opts=array();
	if(isset($fields['cdate'])){
		$opts['fields'][]='CDATE';
		$opts['values'][]=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($fields['cuser'])){
		$opts['fields'][]='CUSER';
		$opts['values'][]=$USER['username'];
	}
	$valstr='';
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		//skip cuser and cdate - already set
		if($k=='cuser' || $k=='cdate'){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		//take care of single quotes in value
		$v=str_replace("'","''",$v);
		switch(strtolower($fields[$k]['type'])){
        	case 'integer':
        	case 'number':
        		$opts['values'][]=$v;
        	break;
        	default:
        		$opts['values'][]="'{$v}'";
        	break;
		}
        $opts['fields'][]=trim(strtoupper($k));
	}
	$fieldstr=implode('","',$opts['fields']);
	$valstr=implode(',',$opts['values']);
    $query=<<<ENDOFQUERY
		REPLACE INTO {$params['-table']}
		("{$fieldstr}")
		values({$valstr})
ENDOFQUERY;
	$dbh_sqlite=sqliteDBConnect($params);
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteReplaceDBRecord Connect Error",$e));
    	return;
	}
	try{
		$result=sqlite_exec($dbh_sqlite,$query);
		if(!$result){
        	$err=array(
        		'error'	=> sqlite_errormsg($dbh_sqlite),
				'query'	=> $query
			);
			debugValue($err);
			return "sqliteReplaceDBRecord Error".printValue($err).$query;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		
		debugValue(array("sqliteReplaceDBRecord Connect Error",$e));
		return "sqliteReplaceDBRecord Error".printValue($err);
	}
	
	return true;
}
//---------- begin function sqliteGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $tables=sqliteGetDBTables();
*/
function sqliteGetDBTables($params=array()){
	$dbh_sqlite=sqliteDBConnect($params);
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteGetDBSchemas Connect Error",$e));
    	return;
	}
	try{
		$result = sqlite_query($dbhandle, "SELECT name FROM sqlite_master WHERE type='table';");
		$tables=array();
		while ($rec = sqlite_fetch_array($result, SQLITE_ASSOC)) {
			$tables[]=strtolower($rec['name']);
		}
		return $tables;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "sqliteIsDBTable error: exception".printValue($err);
		exit;
	}
	return array();
}
//---------- begin function sqliteGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=sqliteGetDBFieldInfo('abcschema.abc');
*/
function sqliteGetDBFieldInfo($table,$params=array()){
	$dbh_sqlite=sqliteDBConnect($params);
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteGetDBSchemas Connect Error",$e));
    	return;
	}
	$query="select * from {$table} where 1=0";
	try{
		$result=sqlite_exec($dbh_sqlite,$query);
		if(!$result){
        	$err=array(
        		'error'	=> sqlite_errormsg($dbh_sqlite),
        		'query'	=> $query
			);
			echo "sqliteGetDBFieldInfo error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "sqliteGetDBFieldInfo error: exception".printValue($err);
		exit;
	}
	$recs=array();
	for($i=1;$i<=sqlite_num_fields($result);$i++){
		$field=strtolower(sqlite_field_name($result,$i));
        $recs[$field]=array(
			'name'		=> $field,
			'type'		=> strtolower(sqlite_field_type($result,$i)),
			'scale'		=> strtolower(sqlite_field_scale($result,$i)),
			'precision'	=> strtolower(sqlite_field_precision($result,$i)),
			'length'	=> strtolower(sqlite_field_len($result,$i)),
			'num'		=> strtolower(sqlite_field_num($result,$i))
		);
    }
    
	return $recs;
}
//---------- begin function sqliteGetDBCount ----------
/**
* @describe returns the count of any query without actually getting the data
* @param $query string - the query to run
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $count integer
* @usage $cnt=sqliteGetDBCount('select * from abcschema.abc');
*/
function sqliteGetDBCount($query,$params){
	$params['-count']=1;
	return sqliteQueryResults($query,$params);
}
//---------- begin function sqliteQueryHeader ----------
/**
* @describe returns a single row array with the column names
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array a single row array with the column names
* @usage $recs=sqliteQueryHeader($query);
*/
function sqliteQueryHeader($query,$params=array()){
	$dbh_sqlite=sqliteDBConnect($params);
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteGetDBSchemas Connect Error",$e));
    	return;
	}
	if(!preg_match('/limit\ /is',$query)){
		$query .= " limit 0";
	}
	try{
		$result=sqlite_exec($dbh_sqlite,$query);
		if(!$result){
        	$err=array(
        		'error'	=> sqlite_errormsg($dbh_sqlite),
        		'query'	=> $query
			);
			echo "sqliteQueryHeader error:".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "sqliteQueryHeader error: exception".printValue($err);
		exit;
	}
	$fields=array();
	for($i=1;$i<=sqlite_num_fields($result);$i++){
		$field=strtolower(sqlite_field_name($result,$i));
        $fields[]=$field;
    }
    
    $rec=array();
    foreach($fields as $field){
		$rec[$field]='';
	}
    $recs=array($rec);
	return $recs;
}
//---------- begin function sqliteQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_sqlite,dbuser_sqlite, and dbpass_sqlite
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage $recs=sqliteQueryResults('select top 50 * from abcschema.abc');
*/
function sqliteQueryResults($query,$params=array()){
	global $dbh_sqlite;
	if(!is_resource($dbh_sqlite)){
		$dbh_sqlite=sqliteDBConnect($params);
	}
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteQueryResults Connect Error",$e));
    	return;
	}
	try{
		$result=sqlite_exec($dbh_sqlite,$query);
		if(!$result){
			$errstr=sqlite_errormsg($dbh_sqlite);
			if(!strlen($errstr)){return array();}
        	$err=array(
        		'error'	=> $errstr,
        		'query' => $query
			);
			
			echo "sqliteQueryResults error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "sqliteQueryResults error: exception".printValue($err);
		exit;
	}
	$rowcount=sqlite_num_rows($result);
	if($rowcount==0 && isset($params['-forceheader'])){
		$fields=array();
		for($i=1;$i<=sqlite_num_fields($result);$i++){
			$field=strtolower(sqlite_field_name($result,$i));
			$fields[]=$field;
		}
		
		$rec=array();
		foreach($fields as $field){
			$rec[$field]='';
		}
		$recs=array($rec);
		return $recs;
	}
	if(isset($params['-count'])){
    	return $rowcount;
	}
	$header=0;
	unset($fh);
	$recs=array();
	while ($crec = sqlite_fetch_array($result, SQLITE_ASSOC)) {
		$rec=array();
		foreach($crec as $k=>$v){
			$k=strtolower($k);
			$rec[$k]=$v;
		}
		$recs[]=$rec;
	}
	return $recs;
}

