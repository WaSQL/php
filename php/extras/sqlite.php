<?php

/*
	sqlite3 Database functions
		http://php.net/manual/en/sqlite3.query.php
		*
*/
//---------- begin function sqliteListRecords
/**
* @describe returns an html table of records
* @param params array - requires either -list or -table or a raw query instead of params
*	[-list] array - getDBRecords array to use
*	[-table] string - table name.  Use this with other field/value params to filter the results
*	[-table{class|style|id|...}] string - sets specified attribute on table
*	[-thead{class|style|id|...}] string - sets specified attribute on thead
*	[-tbody{class|style|id|...}] string - sets specified attribute on tbody
*	[-tbody_onclick] - wraps the column name in an anchor with onclick. %field% is replaced with the current field. i.e "return pageSortByColumn('%field%');" 
*	[-tbody_href] - wraps the column name in an anchor with onclick. %field% is replaced with the current field. i.e "/mypage/sortby/%field%"
*	[-listfields] -  subset of fields to list from the list returned.
*	[-limit] mixed - query record limit
*	[-offset] mixed - query offset limit
*	[{field}_eval] - php code to return based on current record values.  i.e "return setClassBasedOnAge('%age%');"
*	[{field}_onclick] - wrap in onclick anchor tag, replacing any %{field}% values   i.e "return pageShowThis('%age%');"
*	[{field}_href] - wrap in anchor tag, replacing any %{field}% values   i.e "/abc/def/%age%"
*	[-host] - server to connect to
* 	[-dbname] - name of database
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return string - html table to display
* @usage
*	<?=sqliteListRecords(array('-table'=>'notes'));?>
*	<?=sqliteListRecords(array('-list'=>$recs));?>
*	<?=sqliteListRecords("select * from myschema.mytable where ...");?>
*/
function sqliteListRecords($params=array()){
	//require -table or -list
	if(empty($params['-table']) && empty($params['-list'])){
		if(!empty($params[0])){
			//they are passing in the list without any other params.
			$params=array('-list'=>$params);
		}
		elseif(!is_array($params) && (stringBeginsWith($params,"select ") || stringBeginsWith($params,"with "))){
			//they just entered a query. convert it to a list
			$params=array('-list'=>sqliteGetDBRecords($params));
		}
	}
	if(!empty($params['-table'])){
		//get the list from the table. First lets get the table fields
		$info=sqliteGetDBFieldInfo($params['-table']);
		if(!is_array($info) || !count($info)){
			return "sqliteListRecords error: No fields found for {$params['-table']}";
		}
		$params['-forceheader']=1;
		$params['-list']=sqliteGetDBRecords($params);
	}
	//verify we have records in $params['-list']
	if(!is_array($params['-list']) || count($params['-list'])==0){
		return "sqliteListRecords error: ".$params['-list'];
	}
	//determine -listfields
	if(!empty($params['-listfields'])){
		if(!is_array($params['-listfields'])){
			$params['-listfields']=str_replace(' ','',$params['-listfields']);
			$params['-listfields']=preg_split('/\,/',$params['-listfields']);
		}
	}
	if(empty($params['-listfields'])){
		//get fields from -list
		$params['-listfields']=array();
		foreach($params['-list'] as $rec){
			foreach($rec as $k=>$v){
				if(isWasqlField($k) && $k != '_id'){continue;}
				$params['-listfields'][]=$k;
			}
			break;
		}
	}
	$rtn='';
	//Check for -total to determine if we should show the searchFilterForm
	if(!empty($params['-total'])){
		if(empty($params['-searchfields'])){
			$params['-searchfields']=array();
			foreach($params['-listfields'] as $field){
				$params['-searchfields'][]=$field;
			}
		}
		$rtn .= commonSearchFiltersForm($params);
	}
	//lets make us a table from the list we have
	$rtn.='<table ';
	//add any table attributes pass in with -table
	$atts=array();
	foreach($params as $k=>$v){
		if(preg_match('/^-table(.+)$/',$k,$m)){
			$atts[$m[1]]=$v;
		}
	}
	$rtn .= setTagAttributes($atts);
	$rtn .= '>'.PHP_EOL;
	//build the thead
	$rtn.='<thead ';
	//add any thead attributes pass in with -thead
	$atts=array();
	foreach($params as $k=>$v){
		if(preg_match('/^-thead(.+)$/',$k,$m)){
			$atts[$m[1]]=$v;
		}
	}
	$rtn .= setTagAttributes($atts);
	$rtn .= '>'.PHP_EOL;
	$rtn .= '		<tr>'.PHP_EOL;
	foreach($params['-listfields'] as $field){
		$name=ucfirst(str_replace('_',' ',$field));
		$rtn .= '			<th>';
		//TODO: build in ability to sort by column
		if(!empty($params['-thead_onclick'])){
			$href=$params['-thead_onclick'];
			$replace='%field%';
            $href=str_replace($replace,$field,$href);
            $name='<a href="#" onclick="'.$href.'">'.$name.'</a>';
		}
		elseif(!empty($params['-thead_href'])){
			$href=$params['-thead_href'];
			$replace='%field%';
            $href=str_replace($replace,$field,$href);
            $name='<a href="'.$href.'">'.$name.'</a>';
		}
		$rtn .=$name;
		$rtn .='</th>'.PHP_EOL;
	}
	$rtn .= '		<tr>'.PHP_EOL;
	$rtn .= '	</thead>'.PHP_EOL;
	//build the tbody
	$rtn.='<tbody ';
	//add any tbody attributes pass in with -tbody
	$atts=array();
	foreach($params as $k=>$v){
		if(preg_match('/^-tbody(.+)$/',$k,$m)){
			$atts[$m[1]]=$v;
		}
	}
	$rtn .= setTagAttributes($atts);
	$rtn .= '>'.PHP_EOL;
	foreach($params['-list'] as $rec){
		$rtn .= '		<tr>'.PHP_EOL;
		foreach($params['-listfields'] as $fld){
			$value=$rec[$fld];
			//check for {field}_eval
			if(!empty($params[$fld."_eval"])){
				$evalstr=$params[$fld."_eval"];
				//substitute and %{field}% with its value in this record
				foreach($rec as $recfld=>$recval){
					if(is_array($recfld) || is_array($recval)){continue;}
					$replace='%'.$recfld.'%';
                    $evalstr=str_replace($replace,$rec[$recfld],$evalstr);
                }
                $value=evalPHP('<?' . $evalstr .'?>');
			}
			//check for {field}_onclick and {field}_href
			if(!empty($params[$fld."_onclick"])){
				$href=$params[$fld."_onclick"];
				//substitute and %{field}% with its value in this record
				foreach($rec as $recfld=>$recval){
					if(is_array($recfld) || is_array($recval)){continue;}
					$replace='%'.$recfld.'%';
                    $href=str_replace($replace,$rec[$recfld],$href);
                }
                $value='<a href="#" onclick="'.$href.'">'.$value.'</a>';
			}
			elseif(!empty($params[$fld."_href"])){
				$href=$params[$fld."_onclick"];
				//substitute and %{field}% with its value in this record
				foreach($rec as $recfld=>$recval){
					if(is_array($recfld) || is_array($recval)){continue;}
					$replace='%'.$recfld.'%';
                    $href=str_replace($replace,$rec[$recfld],$href);
                }
                $value='<a href="'.$href.'">'.$value.'</a>';
			}
			$rtn .= '			<td>'.$value.'</td>'.PHP_EOL;
		}
		$rtn .= '		</tr>'.PHP_EOL;
	}
	$rtn .= '	</tbody>'.PHP_EOL;
	$rtn .= '</table>'.PHP_EOL;
	return $rtn;
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
function sqliteGetDBCount($params=array()){
	$params['-count']=1;
	return sqliteQueryResults($query,$params);
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
//---------- begin function sqliteGetDBRecords ----------
/**
* @describe retrieves records from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordsets
* @usage $ok=sqliteGetDBRecords(array('-table'=>'tesl));
*/
function sqliteGetDBRecords($params=array()){
	//check for just a query instead of a params array and convert it getDBRecords($query)
	if(!is_array($params) && is_string($params)){
    	$params=array('-query'=>$params);
	}
	if(isset($params['-query'])){$query=$params['-query'];}
	else{
		if(!isset($params['-table'])){return 'sqliteGetDBRecords error: No table specified.';}
		if(!isset($params['-fields'])){$params['-fields']='*';}
		if(is_array($params['-fields'])){
			$params['-fields']=implode(', ',$params['-fields']);
		}
		$wherestr='';
		if(isset($params['-where'])){
			$wherestr= " WHERE {$params['-where']}";
		}
		else{
			$fields=sqliteGetDBFieldInfo($params['-table'],$params);
			$ands=array();
			foreach($params as $k=>$v){
				$k=strtolower($k);
				if(!isset($fields[$k])){continue;}
				if(!strlen(trim($v))){continue;}
				if(is_array($params[$k])){
					$params[$k]=implode(':',$params[$k]);
				}
				$params[$k]=str_replace("'","''",$params[$k]);
				$val=strtoupper($params[$k]);
				$ands[]="upper({$k})='{$val}'";
			}

			if(count($ands)){
				$wherestr='WHERE '.implode(' and ',$ands);
			}
		}
		$query="SELECT {$params['-fields']}  FROM {$params['-table']} {$wherestr}";
		if(isset($params['-order'])){
			$query .= " ORDER BY {$params['-order']}";
		}
		if(isset($params['-limit'])){
			if(stringContains($params['-limit'],',')){
				$parts=preg_split('/\,/',$params['-limit'],2);
				$params['-offset']=$parts[0];
				$params['-limit']=$parts[1];
			}
			$query .= " limit {$params['-limit']}";
		}
		if(isset($params['-offset'])){
			$query .= " offset {$params['-offset']}";
		}
	}
	//echo "[{$query}]<br />".PHP_EOL;
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
