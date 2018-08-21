<?php

/*
	sqlite3 Database functions
		http://php.net/manual/en/sqlite3.query.php
		*
*/
//---------- begin function sqliteListRecords
/**
* @describe returns an html table of records from a sqlite database. refer to databaseListRecords
*/
function sqliteListRecords($params=array()){
	$params['-database']='sqlite';
	return databaseListRecords($params);
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
		elseif(isset($CONFIG['dbname'])){
			$params['-dbname']=$CONFIG['dbname'];
			$params['-dbname_source']="CONFIG dbname";
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
	if(!isset($params['-dbname'])){
		debugValue("sqliteDBConnect error: no dbname set");
		return null;
	}
	if(!isset($params['-mode'])){$params['-mode']=0666;}
	//echo printValue($params).printValue($_SERVER);exit;
	//check to see if the sqlite database is available. Find it if possible
	if(!file_exists($params['-dbname'])){
		$cfiles=array(
			realpath("{$_SERVER['DOCUMENT_ROOT']}{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}../{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}../../{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}/{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}/../{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}/../../{$params['-dbname']}"),
			realpath("../{$params['-dbname']}")
		);
		foreach($cfiles as $cfile){
			if(file_exists($cfile)){
				$params['-dbname']=$cfile;
			}
		}
		//echo printValue($cfiles).printValue($params);exit;
	}
	global $dbh_sqlite;
	if($dbh_sqlite){return $dbh_sqlite;}
	try{
		$dbh_sqlite = new SQLite3($params['-dbname'],SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
		$dbh_sqlite->busyTimeout(5000);
		// WAL mode has better control over concurrency.
		// Source: https://www.sqlite.org/wal.html
		$dbh_sqlite->exec('PRAGMA journal_mode = wal;');
		return $dbh_sqlite;
	}
	catch (Exception $e) {
		debugValue("sqliteDBConnect exception" . $e->getMessage());
		return null;

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
		return null;
	}
	$table=strtolower($table);
	$dbh_sqlite=sqliteDBConnect($params);
	if(!is_object($dbh_sqlite)){
		echo("sqliteDBConnect error:{$table}".printValue($dbh_sqlite));exit;
		return null;
	}
	try{
		//check for dbname.tablename
		$parts=preg_split('/\./',$table,2);
		if(count($parts)==2){
			$query="SELECT name FROM {$parts[0]}.sqlite_master WHERE type='table' and name = ?";
			$table=$parts[1];
		}
		else{
		$query="SELECT name FROM sqlite_master WHERE type='table' and name = ?";
		}
		$vals=array($table);
		$stmt=$dbh_sqlite->prepare($query);
		if(!$stmt){
			$err=array(
				'msg'=>"sqliteIsDBTable error",
				'error'	=> $dbh_sqlite->lastErrorMsg(),
				'query'	=> $query,
				'vals'	=> $vals
				);
			debugValue($err);
			return null;
		}
		$stmt->bindParam(1,$vals[0],SQLITE3_TEXT);
		$results=$stmt->execute();
		while ($rec = $results->fetchArray(SQLITE3_ASSOC)) {
			if(strtolower($rec['name']) == $table){
				$results->finalize();
				return true;
			}
		}
		$results->finalize();
		return false;
	}
	catch (Exception $e) {
		$err=$e->getMessage();
		debugValue("sqliteIsDBTable error: exception".printValue($err));
		return null;
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
	$dbh_sqlite=null;
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
	try{
		$result=$dbh_sqlite->exec($query);
		if(!$result){
			$err=array(
				'msg'=>"sqliteExecuteSQL error",
				'error'	=> $dbh_sqlite->lastErrorMsg(),
				'query'	=> $query
				);
			debugValue($err);
			return false;
		}

		return true;
	}
	catch (Exception $e) {
		$err=$e->getMessage();
		debugValue("sqliteExecuteSQL error: {$err}");
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
	//echo "sqliteAddDBRecord".printValue($params);exit;
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
		$params['cuser']=isset($USER['username'])?$USER['username']:0;
		
	}
	elseif(isset($fields['_cuser'])){
		$params['_cuser']=isset($USER['username'])?$USER['username']:0;
	}
	$binds=array();
	$vals=array();
	$flds=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!isset($fields[$k])){continue;}
		$vals[]=$v;
		$flds[]=$k;
        $binds[]='?';
	}
	if(!count($flds)){
		$e="No fields";
		debugValue(array("sqliteAddDBRecord Error",$e));
    	return;
	}
	$fldstr=implode(', ',$flds);
	$bindstr=implode(',',$binds);

    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			({$fldstr})
		VALUES
			({$bindstr})
ENDOFQUERY;
	$dbh_sqlite=sqliteDBConnect($params);
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteAddDBRecord Connect Error",$e));
    	return;
	}
	try{
		$stmt=$dbh_sqlite->prepare($query);
		if(!$stmt){
			$err=array(
				'msg'=>"sqliteAddDBRecord error",
				'error'	=> $dbh_sqlite->lastErrorMsg(),
				'query'	=> $query,
				'vals'	=> $vals
				);
			debugValue($err);
			return null;
		}
		foreach($vals as $i=>$v){
			$fld=$flds[$i];
			$x=$i+1;
			//echo "{$x}::{$v}::{$fields[$fld]['type']}<br>".PHP_EOL;
			switch(strtolower($fields[$fld]['type'])){
				case 'integer':
					$stmt->bindParam($x,$vals[$i],SQLITE3_INTEGER);
				break;
				case 'float':
					$stmt->bindParam($x,$vals[$i],SQLITE3_FLOAT);
				break;
				case 'blob':
					$stmt->bindParam($x,$vals[$i],SQLITE3_BLOB);
				break;
				case 'null':
					$stmt->bindParam($x,$vals[$i],SQLITE3_NULL);
				break;
				default:
					$stmt->bindParam($x,$vals[$i],SQLITE3_TEXT);
				break;
			}
		}
		$results=$stmt->execute();
		return $dbh_sqlite->lastInsertRowID();;
	}
	catch (Exception $e) {
		$err=$e->getMessage();
		debugValue("sqliteAddDBRecord error: {$err}");
		return null;
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
	$updates=array();
	$vals=array();
	$flds=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!isset($fields[$k])){continue;}
		$vals[]=$v;
		$flds[]=$k;
        $updates[]="{$k}=?";
	}
	if(!count($flds)){
		$e="No fields";
		debugValue(array("sqliteEditDBRecord Error",$e));
    	return;
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	$dbh_sqlite=sqliteDBConnect($params);
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteEditDBRecord Connect Error",$e));
    	return;
	}
	try{
		$stmt=$dbh_sqlite->prepare($query);
		if(!$stmt){
			$err=array(
				'msg'=>"sqliteEditDBRecord prepare error",
				'error'	=> $dbh_sqlite->lastErrorMsg(),
				'query'	=> $query
				);
			debugValue($err);
			return;
		}
		foreach($vals as $i=>$v){
			$fld=$flds[$i];
			$x=$i+1;
			//echo "{$x}::{$v}::{$fields[$fld]['type']}<br>".PHP_EOL;
			switch(strtolower($fields[$fld]['type'])){
				case 'integer':
					$stmt->bindParam($x,$vals[$i],SQLITE3_INTEGER);
				break;
				case 'float':
					$stmt->bindParam($x,$vals[$i],SQLITE3_FLOAT);
				break;
				case 'blob':
					$stmt->bindParam($x,$vals[$i],SQLITE3_BLOB);
				break;
				case 'null':
					$stmt->bindParam($x,$vals[$i],SQLITE3_NULL);
				break;
				default:
					$stmt->bindParam($x,$vals[$i],SQLITE3_TEXT);
				break;
			}
		}
		$results=$stmt->execute();
		if($results){
			$results->finalize();
		}
		else{
			$err=$dbh_sqlite->lastErrorMsg();
			if(strtolower($err) != 'not an error'){
				debugValue("sqliteEditDBRecord execute error: {$err}");
			}
			else{
				debugValue("sqliteEditDBRecord execute error: unknown reason");
			}
		}
		return 1;
	}
	catch (Exception $e) {
		$err=$e->getMessage();
		debugValue("sqliteEditDBRecord exception: {$err}");
		return null;
	}
	return 0;
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
    	debugValue(array("sqliteGetDBTables Error",$e));
    	return;
	}
	try{
		$query="SELECT name FROM sqlite_master WHERE type='table';";
		$results=$dbh_sqlite->query($query);
		while ($rec = $results->fetchArray(SQLITE3_ASSOC)) {
			$tables[]=strtolower($rec['name']);
		}
		$results->finalize();
		return $tables;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue("sqliteIsDBTable error: exception".printValue($err));
		return null;
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
*	[-getmeta] - boolean - if true returns info in _fielddata table for these fields - defaults to false
*	[-field] - if this has a value return only this field - defaults to blank
*	[-force] - clear cache and refetch info
* @return boolean returns true on success
* @usage $fieldinfo=sqliteGetDBFieldInfo('abcschema.abc');
*/
function sqliteGetDBFieldInfo($tablename,$params=array()){
	global $sqliteGetDBFieldInfoCache;
	$key=$tablename.encodeCRC(json_encode($params));
	//clear cache?
	if(isset($params['-force']) && $params['-force'] && $sqliteGetDBFieldInfoCache[$key]){
		unset($sqliteGetDBFieldInfoCache[$key]);
	}
	//check cache
	if(isset($sqliteGetDBFieldInfoCache[$key])){return $sqliteGetDBFieldInfoCache[$key];}
	$dbh_sqlite=sqliteDBConnect($params);
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteGetDBSchemas Connect Error",$e));
    	return;
	}
	//check for dbname.tablename
	$parts=preg_split('/\./',$tablename,2);
	if(count($parts)==2){
		$query="PRAGMA {$parts[0]}.table_info({$parts[1]})";	
	}
	else{
	$query="PRAGMA table_info({$tablename})";
	}
	try{
		$results=$dbh_sqlite->query($query);
		$recs=array();
		while ($xrec = $results->fetchArray(SQLITE3_ASSOC)) {
			$field=strtolower($xrec['name']);
			$xrec['_dbfield']=$field;
			$xrec['_dbtype']=$xrec['_dbtype_ex']=$xrec['type'];
			$xrec['_dbnull']=$xrec['notnull']==0?'NO':'YES';
			$xrec['_dbdefault']=$xrec['dflt_value'];
			$recs[$field]=$xrec;
		}
		$results->finalize();
		if(isset($params['-getmeta']) && $params['-getmeta']){
			//Get a list of the metadata for this table
			$metaopts=array('-table'=>"_fielddata",'-notimestamp'=>1,'tablename'=>$tablename);
			if(isset($params['-field']) && strlen($params['-field'])){
				$metaopts['fieldname']=$params['-field'];
			}
			if(count($parts)==2){
				$metaopts['-table']="{$parts[0]}._fielddata";
				$metaopts['tablename']=$parts[1];
			}
			$meta_recs=getDBRecords($metaopts);
			if(is_array($meta_recs)){
				foreach($meta_recs as $meta_rec){
					$name=$meta_rec['fieldname'];
					if(!isset($recs[$name]['_dbtype'])){continue;}
					foreach($meta_rec as $key=>$val){
						if(preg_match('/^\_/',$key)){continue;}
						$recs[$name][$key]=$val;
					}
            	}
        	}
		}
		$sqliteGetDBFieldInfoCache[$key]=$recs;
		return $recs;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue("sqliteGetDBFieldInfo error: exception".printValue($err));
		return null;
	}
	return array();
}
//---------- begin function sqliteGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage $cnt=sqliteGetDBCount(array('-table'=>'states'));
*/
function sqliteGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=sqliteGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function sqliteTruncateDBTable ----------
/**
* @describe truncates the specified table
* @param $table mixed - the table to truncate or and array of tables to truncate
* @return boolean integer
* @usage $cnt=sqliteTruncateDBTable('test');
*/
function sqliteTruncateDBTable($table){
	if(is_array($table)){$tables=$table;}
	else{$tables=array($table);}
	foreach($tables as $table){
		if(!sqliteIsDBTable($table)){return "No such table: {$table}.";}
		$result=executeSQL("DELETE FROM {$table}");
		if(isset($result['error'])){
			return $result['error'];
	        }
	    }
    return 1;
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
	if(!$dbh_sqlite){
		$dbh_sqlite=sqliteDBConnect($params);
	}
	if(!$dbh_sqlite){
    	$e=sqlite_error_string(sqlite_last_error());
    	debugValue(array("sqliteQueryResults Connect Error",$e));
    	return null;
	}
	try{
		$results=$dbh_sqlite->query($query);
		if(!is_object($results)){
			echo "sqliteQueryResults error".printValue($query);exit;
		}
		$recs=array();
		while ($xrec = $results->fetchArray(SQLITE3_ASSOC)) {
			$rec=array();
			foreach($xrec as $k=>$v){
				$k=strtolower($k);
				$rec[$k]=$v;
			}
			if(isset($params['-process'])){
				$ok=call_user_func($params['-process'],$rec);
				$x++;
				continue;
			}
			$recs[]=$rec;
		}
		$results->finalize();
		return $recs;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue("sqliteQueryResults error: exception".printValue($err));
		return null;
	}
}
//---------- begin function sqliteGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=sqliteGetDBRecord(array('-table'=>'tesl));
*/
function sqliteGetDBRecord($params=array()){
	$recs=sqliteGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function sqliteGetDBRecords
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
*	<?=sqliteGetDBRecords(array('-table'=>'notes'));?>
*	<?=sqliteGetDBRecords("select * from myschema.mytable where ...");?>
*/
function sqliteGetDBRecords($params){
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
		$fields=sqliteGetDBFieldInfo($params['-table'],$params);
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
	return sqliteQueryResults($query,$params);
}
//---------- begin function sqliteGetDBRecordsCount ----------
/**
* @describe retrieves records from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordsets
* @usage $cnt=sqliteGetDBRecordsCount(array('-table'=>'tesl));
*/
function sqliteGetDBRecordsCount($params=array()){
	$params['-fields']='count(*) cnt';
	if(isset($params['-order'])){unset($params['-order']);}
	if(isset($params['-limit'])){unset($params['-limit']);}
	if(isset($params['-offset'])){unset($params['-offset']);}
	$recs=sqliteGetDBRecords($params);
	return $recs[0]['cnt'];
}
