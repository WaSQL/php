<?php

/*
	mysql Database functions
		https://dev.mysql.com/doc/refman/8.0/en/
		https://www.php.net/manual/en/ref.mysql.php
*/
//---------- begin function mysqlGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=mysqlGetDBRecordById('comments',7);
*/
function mysqlGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "mysqlGetDBRecordById Error: No Table";}
	if($id == 0){return "mysqlGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=mysqlGetDBRecord($recopts);
	return $rec;
}
//---------- begin function mysqlEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=mysqlEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function mysqlEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("mysqlEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("mysqlEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("mysqlEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("mysqlEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return mysqlEditDBRecord($params);
}
//---------- begin function mysqlDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=mysqlDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function mysqlDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("mysqlDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("mysqlDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return mysqlDelDBRecord($params);
}
//---------- begin function mysqlParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=mysqlParseConnectParams($params);
*/
function mysqlParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^mysql/i',$k)){unset($CONFIG[$k]);}
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
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_mysql'])){
			$params['-dbname']=$CONFIG['dbname_mysql'];
			$params['-dbname_source']="CONFIG dbname_mysql";
		}
		elseif(isset($CONFIG['mysql_dbname'])){
			$params['-dbname']=$CONFIG['mysql_dbname'];
			$params['-dbname_source']="CONFIG mysql_dbname";
		}
		elseif(isset($CONFIG['dbname'])){
			$params['-dbname']=$CONFIG['dbname'];
			$params['-dbname_source']="CONFIG dbname";
		}
		else{return 'mysqlParseConnectParams Error: No dbname set'.printValue($CONFIG);}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	//readonly
	if(!isset($params['-mysql_readonly']) && isset($CONFIG['mysql_readonly'])){
		$params['-readonly']=$CONFIG['mysql_readonly'];
	}
	//dbmode
	if(!isset($params['-dbmode'])){
		if(isset($CONFIG['dbmode_mysql'])){
			$params['-dbmode']=$CONFIG['dbmode_mysql'];
			$params['-dbmode_source']="CONFIG dbname_mysql";
		}
		elseif(isset($CONFIG['mysql_dbmode'])){
			$params['-dbmode']=$CONFIG['mysql_dbmode'];
			$params['-dbmode_source']="CONFIG mysql_dbname";
		}
	}
	else{
		$params['-dbmode_source']="passed in";
	}
	return $params;
}
//---------- begin function mysqlDBConnect ----------
/**
* @describe connects to a mysql database and returns the handle resource
* @param $param array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
* 	[-dbname] - name of ODBC connection
*   [-single] - if you pass in -single it will connect using mysql_connect instead of mysql_pconnect and return the connection
* @return $dbh_mysql resource - returns the mysql connection resource
* @usage $dbh_mysql=mysqlDBConnect($params);
* @usage  example of using -single
*
	$conn=mysqlDBConnect(array('-single'=>1));
	mysql_autocommit($conn, FALSE);

	mysql_exec($conn, $query1);
	mysql_exec($conn, $query2);

	if (!mysql_error()){
		mysql_commit($conn);
	}
	else{
		mysql_rollback($conn);
	}
	mysql_close($conn);
*
*/
function mysqlDBConnect($params=array()){
	global $CONFIG;
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=mysqlParseConnectParams($params);
	if(!isset($params['-dbname'])){
		$CONFIG['mysql_error']="dbname not set";
		debugValue("mysqlDBConnect error: no dbname set".printValue($params));
		return null;
	}
	global $dbh_mysql;
	if($dbh_mysql){return $dbh_mysql;}
	try{
		$dbh_mysql = mysqli_connect($params['-dbhost'],$params['-dbuser'],$params['-dbpass'],$params['-dbname']);
		if(!is_object($dbh_mysql)){
			$err=@mysqli_connect_error();
			echo "mysqlDBConnect error:{$err}".printValue($params);
			exit;

		}
		return $dbh_mysql;
	}
	catch (Exception $e) {
		echo "dbh_mysql exception" . printValue($e);
		exit;
	}
}
//---------- begin function mysqlExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
*	[-host] - mysql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage $ok=mysqlExecuteSQL("truncate table abc");
*/
function mysqlExecuteSQL($query,$params=array()){
	global $dbh_mysql;
	if(!is_resource($dbh_mysql)){
		$dbh_mysql=mysqlDBConnect();
	}
	if(!$dbh_mysql){
		debugValue(array(
			'function'=>'mysqlQueryResults',
			'message'=>'connect failed',
			'error'=>mysqli_connect_error(),
			'query'=>$query
		));
    	return;
	}
	$result=@mysqli_query($dbh_mysql,$query);
	if(!$result){
		debugValue(array(
			'function'=>'mysqlQueryResults',
			'message'=>'mysqli_query failed',
			'error'=>mysqli_error($dbh_mysql),
			'query'=>$query
		));
		mysqli_close($dbh_mysql);
		return null;
	}
	mysqli_close($dbh_mysql);
	return true;
}
//---------- begin function mysqlGetDBFieldInfo--------------------
/**
* @describe returns an array containing type,length, and flags for each field in said table
* @param table string - table name
* @param [getmeta] boolean - if true returns info in _fielddata table for these fields - defaults to false
* @param [field] string - if this has a value return only this field - defaults to blank
* @param [getmeta] boolean - if true forces a refresh - defaults to false
* @return array
* @usage $fields=mysqlGetDBFieldInfo('notes');
*/
function mysqlGetDBFieldInfo($table=''){
	$query="show full columns from {$table}";
	$recs=mysqlQueryResults($query);
	$info=array();
	foreach($recs as $key=>$rec){
    	if(preg_match('/(VIRTUAL|STORED) GENERATED/i',$rec['extra'])){
			$info[$key]['expression']=mysqlGetDBExpression($vtable,$rec['field']);
		}
		$info[$key]['_dbfield']=$rec['field'];
		$info[$key]['name']=$rec['field'];
		$info[$key]['_dbnull']=$rec['null'];
		$info[$key]['_dbprivileges']=$rec['privileges'];
		$info[$key]['_dbtablename']=$table;
		$info[$key]['_dbtable']=$table;
		$info[$key]['table']=$table;
		if(preg_match('/^(.+?)\((.+)\)$/',$rec['type'],$m)){
			$info[$key]['type']=$info[$key]['_dbtype']=$m[1];
			list($len,$dec)=preg_split('/\,/',$m[2]);
			$info[$key]['length']=$recs[$key]['_dblength']=$len;
			$info[$key]['_dbtype_ex']=$rec['type'];
		}
		else{
			$info[$key]['type']=$info[$key]['_dbtype']=$info[$key]['_dbtype_ex']=$rec['type'];
		}
		//flags
		 $flags=array();
		 if(strtolower($rec['null'])=='no'){
		 	$info[$key]['_dbtype_ex'] .= ' NOT NULL';
		 	$flags[]='not_null';
		 }
		 switch(strtolower($rec['key'])){
		 	case 'pri':
		 		$flags[]='primary_key';
		 		$info[$key]['_dbtype_ex'] .= ' PRIMARY KEY';
		 	break;
		 	case 'uni':
		 		$flags[]='unique_key';
		 		$info[$key]['_dbtype_ex'] .= ' UNIQUE';
		 	break;
		 }
		 switch(strtolower($rec['extra'])){
		 	case 'auto_increment':
		 		$flags[]='auto_increment';
		 		$info[$key]['_dbtype_ex'] .= ' auto_increment';
		 	break;
		 	case 'stored generated':
		 		$flags[]='virtual';
		 		$info[$key]['_dbtype_ex'] .= ' STORED GENERATED';
		 	break;
		 	case 'virtual generated':
		 	case 'virtual from':
		 		$flags[]='virtual';
		 		$info[$key]['_dbtype_ex'] .= ' VIRTUAL GENERATED';
		 	break;
		 }
		 $info[$key]['flags']=$info[$key]['_dbflags']=implode(' ',$flags);
		 //default
		 if(strlen($rec['default'])){
		 	$info[$key]['_dbdef']=$info[$key]['default']=$rec['default'];
		 	if(isNum($rec['default'])){
		 		$info[$key]['_dbtype_ex'] .= " Default {$rec['default']}";
		 	}
		 	else{
		 		$info[$key]['_dbtype_ex'] .= " Default '{$rec['default']}'";
		 	}
		 }
		 ksort($info[$key]);
	}
	ksort($info);
	return $info;
}
//---------- begin function getDBExpression
/**
* @describe gets field expression for json type fields
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function mysqlGetDBExpression($table,$field,$schema=''){
	global $CONFIG;
	global $DATABASE;
	if(!isset($CONFIG['db']) && isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$CONFIG['db']=$_REQUEST['db'];
	}
	$dbname=strtolower($DATABASE[$CONFIG['db']]['dbname']);
$query=<<<ENDOFSQL
	SELECT
		generation_expression as exp
	FROM
		information_schema.columns
	WHERE
		table_schema='{$dbname}'
		and table_name='{$table}'
		and column_name='{$field}'
ENDOFSQL;
	$rec=mysqlQueryResults($query);
	if(!isset($rec['exp'])){return '';}
	$rec['exp']=stripslashes($rec['exp']);
	return $rec['exp'];
}
//---------- begin function mysqlGetDBQuery--------------------
/**
* @describe builds a database query based on params
* @param params array
*	[-table] string - table to query
*	[-notimestamp] - turn off building _utime fields for date and time fields
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return string - query string
* @usage $query=mysqlGetDBQuery(array('-table'=>$table,'field1'=>$val1...));
*/
function mysqlGetDBQuery($params=array()){
	if(!isset($params['-table'])){return 'getDBQuery Error: No table' . printValue($params);}
	//get field info for this table
	$info=mysqlGetDBFieldInfo($params['-table']);
	if(!is_array($info)){return $info;}
	$loopfields=array();
	if(isset($params['-fields'])){
		if(is_array($params['-fields'])){$loopfields=$params['-fields'];}
		else{$loopfields=preg_split('/\,+/',trim($params['-fields']));}
    }
    else{$loopfields=array_keys($info);}
    //now add the _utime to any date fields
    //if($params['-table']=='events_data' && count($loopfields)>1){echo printValue($loopfields);exit;}
    $fields=array();
    foreach ($loopfields as $field){
		array_push($fields,$field);
		//add timestamp unless $params['-notimestamp'] is set
		if(!isset($params['-notimestamp']) && isset($info[$field]['_dbtype'])){
			if(preg_match('/^(datetime|date|timestamp)$/i',$info[$field]['_dbtype'])){
				array_push($fields,'UNIX_TIMESTAMP('.$field.') as '.$field.'_utime');
			}
			elseif(preg_match('/^time$/i',$info[$field]['_dbtype'])){
				array_push($fields,'TIME_TO_SEC('.$field.') as '.$field.'_seconds');
			}
		}
	}
	$query='select ';
	$query .= implode(',',$fields).' from ' . $params['-table'];
	//build where clause
	$where = mysqlGetDBWhere($params);
	if(strlen($where)){$query .= " where {$where}";}
	//Set order by if defined
    if(isset($params['-group'])){$query .= ' group by '.$params['-group'];}
	//Set order by if defined
    if(isset($params['-order'])){$query .= ' order by '.$params['-order'];}
    //Set limit if defined
    if(isset($params['-limit'])){$query .= ' limit '.$params['-limit'];}
    //Set offset if defined
    if(isset($params['-offset'])){$query .= ' offset '.$params['-offset'];}
    //if($params['-table']=='events_data' && count($loopfields)>1){echo printValue($loopfields).$query;exit;}
    return $query;
}
//---------- begin function mysqlGetDBRecords
/**
* @describe returns and array of records
* @param params array - requires either -table or a raw query instead of params
*	[-table] string - table name.  Use this with other field/value params to filter the results
*	[-limit] mixed - query record limit.  Defaults to CONFIG['paging'] if set in config.xml otherwise 25
*	[-offset] mixed - query offset limit
*	[-fields] mixed - fields to return
*	[-where] string - string to add to where clause
*	[-filter] string - string to add to where clause
* @return array - set of records
* @usage
*	<?=mysqlGetDBRecords(array('-table'=>'notes'));?>
*	<?=mysqlGetDBRecords("select * from myschema.mytable where ...");?>
*/
function mysqlGetDBRecords($params){
	global $USER;
	global $CONFIG;
	//echo "mysqlGetDBRecords".printValue($params);exit;
	if(empty($params['-table']) && !is_array($params)){
		$params=trim($params);
		if(preg_match('/^(select|exec|with|explain|returning|show|call)[\t\s\ \r\n]/i',$params)){
			//they just entered a query
			$query=$params;
			$params=array();
			return mysqlQueryResults($query,$params);
		}
		else{
			$ok=mysqlExecuteSQL($params);
			return $ok;
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
		return mysqlQueryResults($query,$params);
	}
	else{
		if(empty($params['-table'])){
			debugValue(array(
				'function'=>'mysqlGetDBRecords',
				'message'=>'no table',
				'params'=>$params
			));
	    	return null;
		}
		$query=mysqlGetDBQuery($params);
		return mysqlQueryResults($query,$params);
	}
}
//---------- begin function mysqlGetDBWhere--------------------
/**
* @describe builds a database query where clause only
* @param params array
*	[-table] string - table to query
*	[-notimestamp] - turn off building _utime fields for date and time fields
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return string - query where string
* @usage $where=mysqlGetDBWhere(array('-table'=>$table,'field1'=>$val1...));
*/
function mysqlGetDBWhere($params,$info=array()){
	if(!isset($info) || !count($info)){
		$info=mysqlGetDBFieldInfo($params['-table']);
	}
	$ands=array();
	//echo printValue($info);exit;
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(is_array($params[$k])){
            $params[$k]=implode(':',$params[$k]);
		}
		if(!strlen(trim($params[$k]))){continue;}
		if(!isset($info[$k])){continue;}
        $params[$k]=str_replace("'","''",$params[$k]);
        $v=mysqlEscapeString($params[$k]);
        switch(strtolower($info[$k]['_dbtype'])){
        	case 'int':
        	case 'integer':
        	case 'tinyint':
        	case 'number':
        	case 'float':
        		if(isNum($v)){
        			$ands[]="{$k}={$v}";
        		}
        	break;
        	default:
        		$ands[]="{$k}='{$v}'";
        	break;
        } 
	}
	//check for -where
	if(!empty($params['-where'])){
		$ands[]= "({$params['-where']})";
	}
	if(isset($params['-filter'])){
		$ands[]= "({$params['-filter']})";
	}
	return implode(' and ',$ands);
}
function mysqlEscapeString($str){
	global $dbh_mysql;
	if(!is_resource($dbh_mysql)){
		$dbh_mysql=mysqlDBConnect();
	}
	if(!$dbh_mysql){
		debugValue(array(
			'function'=>'mysqlQueryResults',
			'message'=>'connect failed',
			'error'=>mysqli_connect_error(),
			'query'=>$query
		));
    	return;
	}
	$str = function_exists('mysqli_real_escape_string')?mysqli_real_escape_string($dbh_mysql,$str):mysqli_escape_string($dbh_mysql,$str);
	return $str;
}
//---------- begin function mysqlIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(mysqlIsDBTable('abc')){...}
*/
function mysqlIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "mysqlIsDBTable error: No table";
		return null;
	}
	$table=strtolower($table);
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$query="SELECT name FROM {$parts[0]}.mysql_master WHERE type='table' and name = '{$table}'";
		$table=$parts[1];
	}
	else{
		$query="SELECT name FROM mysql_master WHERE type='table' and name = '{$table}'";
	}
	$recs=mysqlQueryResults($query);
	if(isset($recs[0]['name'])){return true;}
	return false;
}
//---------- begin function mysqlGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of table names
* @usage $tables=mysqlGetDBTables();
*/
function mysqlGetDBTables($params=array()){
	global $CONFIG;
	global $DATABASE;
	if(!isset($CONFIG['db']) && isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$CONFIG['db']=$_REQUEST['db'];
	}
	$dbname=strtolower($DATABASE[$CONFIG['db']]['dbname']);
	$tables=array();
	$query="show tables from {$dbname}";
	$recs=mysqlQueryResults($query);
	$k="tables_in_{$dbname}";
	foreach($recs as $rec){
		$tables[]=strtolower($rec[$k]);
	}
	return $tables;
}
//---------- begin function mysqlQueryResults ----------
/**
* @describe returns the mysql record set
* @param query string - SQL query to execute
* @param [$params] array
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return array - returns records
*/
function mysqlQueryResults($query='',$params=array()){
	$query=trim($query);
	global $USER;
	global $dbh_mysql;
	if(!is_resource($dbh_mysql)){
		$dbh_mysql=mysqlDBConnect();
	}
	if(!$dbh_mysql){
		debugValue(array(
			'function'=>'mysqlQueryResults',
			'message'=>'connect failed',
			'error'=>mysqli_connect_error(),
			'query'=>$query
		));
    	return;
	}
	$result=@mysqli_query($dbh_mysql,$query);
	if(!$result){
		debugValue(array(
			'function'=>'mysqlQueryResults',
			'message'=>'mysqli_query failed',
			'error'=>mysqli_error($dbh_mysql),
			'query'=>$query
		));
		mysqli_close($dbh_mysql);
		return null;
	}
	if(preg_match('/^insert /i',$query) && !stringContains($query,' returning ')){
    	//return the id inserted on insert statements

    	$id=databaseAffectedRows($result);
    	mysqli_close($dbh_mysql);
    	return $id;
	}
	$results = mysqlEnumQueryResults($result,$params);
	mysqli_close($dbh_mysql);
	return $results;
}
//---------- begin function mysqlEnumQueryResults ----------
/**
* @describe enumerates through the data from a mysqli_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function mysqlEnumQueryResults($data,$params=array()){
	if(!$data){return null;}
	$header=0;
	unset($fh);
	//write to file or return a recordset?
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
			mysqli_free_result($result);
			return 'mysqlEnumQueryResults error: Failed to open '.$params['-filename'];
			exit;
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	$i=0;
	while ($row = @mysqli_fetch_assoc($data)){
		$rec=array();
		foreach($row as $key=>$val){
			$key=strtolower($key);
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
			$recs[]=$rec;
		}
	}
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

//---------- begin function mysqlNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function mysqlNamedQuery($name){
	global $CONFIG;
	global $DATABASE;
	$dbname=strtoupper($DATABASE[$CONFIG['db']]['dbname']);
	if(isset($CONFIG['dbname'])){
		$dbname=$CONFIG['dbname'];
	}
	else{
		$dbname=strtoupper($DATABASE[$CONFIG['db']]['dbname']);
	}
	switch(strtolower($name)){
		case 'running_queries':
			return <<<ENDOFQUERY
show processlist
ENDOFQUERY;
		break;
		case 'sessions':
			return <<<ENDOFQUERY
SELECT 
	id, user, host, db, command, time, state, info 
FROM information_schema.processlist
ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY
SHOW OPEN TABLES WHERE In_use > 0
ENDOFQUERY;
		break;
		case 'functions':
			return <<<ENDOFQUERY
SELECT 
	routine_catalog,
	routine_schema,
	routine_name,
	created,
	last_altered,
	definer
FROM information_schema.ROUTINES 
WHERE routine_type = 'FUNCTION' and routine_schema = '{$dbname}'
ENDOFQUERY;
		break;
		case 'procedures':
			return <<<ENDOFQUERY
SELECT 
	routine_catalog,
	routine_schema,
	routine_name,
	created,
	last_altered,
	definer
FROM information_schema.ROUTINES 
WHERE routine_type = 'PROCEDURE' and routine_schema = '{$dbname}'
ENDOFQUERY;
		break;
		case 'tables':
			return <<<ENDOFQUERY
SELECT
	t.table_name as name,
	t.table_rows as row_count,
	c.field_count,
	round(((data_length + index_length) / 1024 / 1024), 2) as mb_size,
	t.auto_increment,
	t.create_time,
	t.update_time,
	t.table_collation,
	t.table_comment
FROM information_schema.tables t,
(select count(*) field_count,table_name from information_schema.columns where table_schema='{$dbname}' group by table_name ) c 
WHERE
	t.table_name =c.table_name 
	and t.table_schema='{$dbname}'
ENDOFQUERY;
		break;
	}
}