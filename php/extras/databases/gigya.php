<?php

/*
	gigya3 Database functions
		http://php.net/manual/en/gigya3.query.php
		*
*/

//---------- begin function gigyaAddDBIndex--------------------
/**
* @describe add an index to a table
* @param params array
*	-table
*	-fields
*	[-fulltext]
*	[-unique]
*	[-name] name of the index
* @return boolean
* @usage
*	$ok=gigyaAddDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=gigyaAddDBIndex(array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function gigyaAddDBIndex($params=array()){
	if(!isset($params['-table'])){return 'gigyaAddDBIndex Error: No table';}
	if(!isset($params['-fields'])){return 'gigyaAddDBIndex Error: No fields';}
	if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,+/',$params['-fields']);}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=gigyaGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	
	//fulltext or unique
	$fulltext=$params['-fulltext']?' FULLTEXT':'';
	$unique=$params['-unique']?' UNIQUE':'';
	//prefix
	$prefix='';
	if(strlen($unique)){$prefix .= 'U';}
	if(strlen($fulltext)){$prefix .= 'F';}
	$prefix.='IDX';
	//name
	$fieldstr=implode('_',$params['-fields']);
	//index names cannot be longer than 64 chars long
	if(strlen($fieldstr) > 60){
    	$fieldstr=substr($fieldstr,0,60);
	}
	if(!isset($params['-name'])){$params['-name']=str_replace('.','_',"{$prefix}_{$params['-table']}_{$fieldstr}");}
	//build and execute
	$fieldstr=implode(", ",$params['-fields']);
	$query="CREATE {$unique} INDEX IF NOT EXISTS {$params['-name']} on {$params['-table']} ({$fieldstr})";
	return gigyaExecuteSQL($query);
}

//---------- begin function gigyaAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
* @return count int
* @usage $ok=gigyaAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=gigyaAddDBRecords('comments',array('-recs'=>$recs);
*/
function gigyaAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("gigyaAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	$params['-table']=$table;
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("gigyaAddDBRecords Error: either -csv or -recs is required");
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("gigyaAddDBRecords Error: no such file: {$params['-csv']}");
		}
		return processCSVLines($params['-csv'],'gigyaAddDBRecordsProcess',$params);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("gigyaAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("gigyaAddDBRecords Error: no recs");
		}
		return gigyaAddDBRecordsProcess($params['-recs'],$params);
	}
}
function gigyaAddDBRecordsProcess($recs,$params=array()){
	if(!isset($params['-table'])){
		return debugValue("gigyaAddDBRecordsProcess Error: no table"); 
	}
	$table=$params['-table'];
	$fieldinfo=gigyaGetDBFieldInfo($table,1);
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
				$v=gigyaEscapeString($v);
				$rec[$k]="'{$v}'";
			}
		}
		$values[]='('.implode(',',array_values($rec)).')';
	}
	$query.=implode(','.PHP_EOL,$values);
	if(isset($params['-upsert']) && isset($params['-upserton'])){
		if(!is_array($params['-upsert'])){
			$params['-upsert']=preg_split('/\,/',$params['-upsert']);
		}
		/*
			ON CONFLICT (id) DO UPDATE SET 
			  id=excluded.id, username=excluded.username,
			  password=excluded.password, level=excluded.level,email=excluded.email
		*/
		if(strtolower($params['-upsert'][0])=='ignore'){
			$query.=PHP_EOL."ON CONFLICT ({$params['-upserton']}) DO NOTHING";
		}
		else{
			$query.=PHP_EOL."ON CONFLICT ({$params['-upserton']}) DO UPDATE SET";
			$flds=array();
			foreach($params['-upsert'] as $fld){
				$flds[]="{$fld}=excluded.{$fld}";
			}
			$query.=PHP_EOL.implode(', ',$flds);
			if(isset($params['-upsertwhere'])){
				$query.=" WHERE {$params['-upsertwhere']}";
			}
		}
	}
	$ok=gigyaExecuteSQL($query);
	return count($values);
}
//---------- begin function gigyaAlterDBTable--------------------
/**
* @describe alters fields in given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=gigyaAlterDBTable('comments',array('comment'=>"varchar(1000) NULL"));
*/
function gigyaAlterDBTable($table,$fields=array(),$maintain_order=1){
	$info=gigyaGetDBFieldInfo($table);
	if(!is_array($info) || !count($info)){
		debugValue("gigyaAlterDBTable - {$table} is missing or has no fields".printValue($table));
		return false;
	}
	$rtn=array();
	//$rtn[]=$info;
	$addfields=array();
	foreach($fields as $name=>$type){
		$lname=strtolower($name);
		$uname=strtoupper($name);
		if(isset($info[$name]) || isset($info[$lname]) || isset($info[$uname])){continue;}
		$addfields[]="{$name} {$type}";
	}
	$dropfields=array();
	foreach($info as $name=>$finfo){
		$lname=strtolower($name);
		$uname=strtoupper($name);
		if(!isset($fields[$name]) && !isset($fields[$lname]) && !isset($fields[$uname])){
			$dropfields[]=$name;
		}
	}
	if(count($dropfields)){
		$fieldstr=implode(', ',$dropfields);
		$query="ALTER TABLE {$table} DROP ({$fieldstr})";
		$ok=gigyaExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	if(count($addfields)){
		$fieldstr=implode(', ',$addfields);
		$query="ALTER TABLE {$table} ADD ({$fieldstr})";
		$ok=gigyaExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	return $rtn;
}
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
//---------- begin function gigyaGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=gigyaGetDBRecordById('comments',7);
*/
function gigyaGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "gigyaGetDBRecordById Error: No Table";}
	if($id == 0){return "gigyaGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=gigyaGetDBRecord($recopts);
	return $rec;
}
//---------- begin function gigyaEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=gigyaEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function gigyaEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("gigyaEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("gigyaEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("gigyaEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("gigyaEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return gigyaEditDBRecord($params);
}
//---------- begin function gigyaDelDBRecord ----------
/**
* @describe deletes records in table that match -where clause
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
* @return boolean
* @usage $id=gigyaDelDBRecord(array('-table'=> '_tabledata','-where'=> "_id=4"));
*/
function gigyaDelDBRecord($params=array()){
	global $USER;
	if(!isset($params['-table'])){return 'gigyaDelDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'gigyaDelDBRecord Error: No where';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=gigyaGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$query="delete from {$params['-table']} where " . $params['-where'];
	return gigyaExecuteSQL($query);
}
//---------- begin function gigyaDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=gigyaDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function gigyaDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("gigyaDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("gigyaDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return gigyaDelDBRecord($params);
}
//---------- begin function gigyaCreateDBTable--------------------
/**
* @describe creates table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=gigyaCreateDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function gigyaCreateDBTable($table='',$fields=array()){
	if(strlen($table)==0){return "gigyaCreateDBTable error: No table";}
	if(count($fields)==0){return "gigyaCreateDBTable error: No fields";}
	if(gigyaIsDBTable($table)){return 0;}
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
	$query="create table {$table} (";
	//echo printValue($fields);exit;
	foreach($fields as $field=>$attributes){
		//lowercase the fieldname and replace spaces with underscores
		$field=strtolower(trim($field));
		$field=str_replace(' ','_',$field);
		$query .= "{$field} {$attributes},";
   	}
    $query=preg_replace('/\,$/','',$query);
    $query .= ")";
	$query_result=gigyaExecuteSQL($query);
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

function gigyaGetDBIndexes($tablename=''){
	return gigyaGetDBTableIndexes($tablename);
}
function gigyaGetDBTableIndexes($tablename=''){
	$recs=array();
	return $recs;
}

//---------- begin function gigyaListRecords
/**
* @describe returns an html table of records from a gigya database. refer to databaseListRecords
*/
function gigyaListRecords($params=array()){
	$params['-database']='gigya';
	return databaseListRecords($params);
}
//---------- begin function gigyaParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=gigyaParseConnectParams($params);
*/
function gigyaParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^gigya/i',$k)){unset($CONFIG[$k]);}
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
		if(isset($CONFIG['dbname_gigya'])){
			$params['-dbname']=$CONFIG['dbname_gigya'];
			$params['-dbname_source']="CONFIG dbname_gigya";
		}
		elseif(isset($CONFIG['gigya_dbname'])){
			$params['-dbname']=$CONFIG['gigya_dbname'];
			$params['-dbname_source']="CONFIG gigya_dbname";
		}
		elseif(isset($CONFIG['dbname'])){
			$params['-dbname']=$CONFIG['dbname'];
			$params['-dbname_source']="CONFIG dbname";
		}
		else{return 'gigyaParseConnectParams Error: No dbname set'.printValue($CONFIG);}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	//readonly
	if(!isset($params['-gigya_readonly']) && isset($CONFIG['gigya_readonly'])){
		$params['-readonly']=$CONFIG['gigya_readonly'];
	}
	//dbmode
	if(!isset($params['-dbmode'])){
		if(isset($CONFIG['dbmode_gigya'])){
			$params['-dbmode']=$CONFIG['dbmode_gigya'];
			$params['-dbmode_source']="CONFIG dbname_gigya";
		}
		elseif(isset($CONFIG['gigya_dbmode'])){
			$params['-dbmode']=$CONFIG['gigya_dbmode'];
			$params['-dbmode_source']="CONFIG gigya_dbname";
		}
	}
	else{
		$params['-dbmode_source']="passed in";
	}
	return $params;
}
//---------- begin function gigyaDBConnect ----------
/**
* @describe connects to a gigya database and returns the handle resource
* @param $param array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
*   [-single] - if you pass in -single it will connect using gigya_connect instead of gigya_pconnect and return the connection
* @return $dbh_gigya resource - returns the gigya connection resource
* @usage $dbh_gigya=gigyaDBConnect($params);
* @usage  example of using -single
*
	$conn=gigyaDBConnect(array('-single'=>1));
	gigya_autocommit($conn, FALSE);

	gigya_exec($conn, $query1);
	gigya_exec($conn, $query2);

	if (!gigya_error()){
		gigya_commit($conn);
	}
	else{
		gigya_rollback($conn);
	}
	gigya_close($conn);
*
*/
function gigyaDBConnect($params=array()){
	global $CONFIG;
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=gigyaParseConnectParams($params);
	if(!isset($params['-dbname'])){
		$CONFIG['gigya_error']="dbname not set";
		debugValue("gigyaDBConnect error: no dbname set".printValue($params));
		return null;
	}
	if(!isset($params['-mode'])){$params['-mode']=0666;}
	//echo printValue($params).printValue($_SERVER);exit;
	//check to see if the gigya database is available. Find it if possible
	if(!file_exists($params['-dbname'])){
		$CONFIG['gigya_error']="dbname does not exist";
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
	$CONFIG['gigya_dbname_realpath']=$params['-dbname'];
	//echo printValue($params);exit;
	global $dbh_gigya;
	if($dbh_gigya){return $dbh_gigya;}
	try{
		if(isset($params['-readonly']) && $params['-readonly']==1){
			$dbh_gigya = new gigya3($params['-dbname'],gigya3_OPEN_READONLY);
		}
		else{
			$dbh_gigya = new gigya3($params['-dbname'],gigya3_OPEN_READWRITE | gigya3_OPEN_CREATE);	
		}
		
		$dbh_gigya->busyTimeout(5000);
		//register some PHP functions so we can use them in queries
		if(!$dbh_gigya->createFunction("config_value", "configValue", 1)){
			debugValue("unable to create config_value function");
		}
		if(!$dbh_gigya->createFunction("user_value", "userValue", 1)){
			debugValue("unable to create user_value function");
		}
		if(!$dbh_gigya->createFunction("is_user", "isUser", 0)){
			debugValue("unable to create is_user function");
		}
		if(!$dbh_gigya->createFunction("is_admin", "isAdmin", 0)){
			debugValue("unable to create is_admin function");
		}
		if(!$dbh_gigya->createFunction("verbose_size", "verboseSize", -1)){
			debugValue("unable to create verbose_size function");
		}
		if(!$dbh_gigya->createFunction("verbose_time", "verboseTime", -1)){
			debugValue("unable to create verbose_time function");
		}
		if(!$dbh_gigya->createFunction("verbose_number", "verboseNumber", -1)){
			debugValue("unable to create verbose_number function");
		}
		if(!$dbh_gigya->createFunction("format_phone", "commonFormatPhone", 1)){
			debugValue("unable to create format_phone function");
		}
		if(!$dbh_gigya->createFunction("string_contains", "stringContains", 2)){
			debugValue("unable to create string_contains function");
		}
		if(!$dbh_gigya->createFunction("php_version", "phpversion")){
			debugValue("unable to create php_version function");
		}

		// WAL mode has better control over concurrency.
		// Source: https://www.gigya.org/wal.html
		$dbh_gigya->exec('PRAGMA journal_mode = wal;');
		return $dbh_gigya;
	}
	catch (Exception $e) {
		$err=$e->getMessage();
		$CONFIG['gigya_error']=$err;
		debugValue("gigyaDBConnect exception - {$err}");
		return null;

	}
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
//---------- begin function gigyaClearConnection ----------
/**
* @describe clears the gigya database connection
* @return boolean returns true if query succeeded
* @usage $ok=gigyaClearConnection();
*/
function gigyaClearConnection(){
	global $dbh_gigya;
	$dbh_gigya=null;
	return true;
}
//---------- begin function gigyaExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage $ok=gigyaExecuteSQL("truncate table abc");
*/
function gigyaExecuteSQL($query,$params=array()){
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'gigyaExecuteSQL'
	);
	$dbh_gigya=gigyaDBConnect($params);
	//enable exceptions
	$dbh_gigya->enableExceptions(true);
	try{
		$result=$dbh_gigya->exec($query);
		$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
		return 1;
	}
	catch (Exception $e) {
		$DATABASE['_lastquery']['error']='connect failed: '.$e->getMessage();
		debugValue($DATABASE['_lastquery']);
		return 0;
	}
	$DATABASE['_lastquery']['stop']=microtime(true);
	$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
	return 1;
}
//---------- begin function gigyaAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=gigyaAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function gigyaAddDBRecord($params){
	//echo "gigyaAddDBRecord".printValue($params);exit;
	global $USER;
	if(!isset($params['-table'])){return 'gigyaAddRecord error: No table specified.';}
	$fields=gigyaGetDBFieldInfo($params['-table']);
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
		debugValue(array("gigyaAddDBRecord Error",$e));
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
	$dbh_gigya=gigyaDBConnect($params);
	if(!$dbh_gigya){
		$err=array(
			'msg'=>"gigyaAddDBRecord error",
			'error'	=> $dbh_gigya->lastErrorMsg(),
			'query'	=> $query
		);
    	debugValue(array("gigyaAddDBRecord Connect Error",$err));
    	return;
	}
	//enable exceptions
	$dbh_gigya->enableExceptions(true);
	try{
		$stmt=$dbh_gigya->prepare($query);
		foreach($vals as $i=>$v){
			$fld=$flds[$i];
			$x=$i+1;
			//echo "{$x}::{$v}::{$fields[$fld]['type']}<br>".PHP_EOL;
			switch(strtolower($fields[$fld]['type'])){
				case 'integer':
					$stmt->bindParam($x,$vals[$i],gigya3_INTEGER);
				break;
				case 'float':
					$stmt->bindParam($x,$vals[$i],gigya3_FLOAT);
				break;
				case 'blob':
					$stmt->bindParam($x,$vals[$i],gigya3_BLOB);
				break;
				case 'null':
					$stmt->bindParam($x,$vals[$i],gigya3_NULL);
				break;
				default:
					$stmt->bindParam($x,$vals[$i],gigya3_TEXT);
				break;
			}
		}
		$results=$stmt->execute();
		return $dbh_gigya->lastInsertRowID();;
	}
	catch (Exception $e) {
		$msg=$e->getMessage();
		debugValue(array(
			'function'=>'gigyaAddDBRecord',
			'message'=>'query failed',
			'error'=>$msg,
			'query'=>$query,
			'params'=>$params
		));
		return null;
	}
	return 0;
}
//---------- begin function gigyaEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
*   -table - name of the table to add to
*   -where - filter criteria
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage $id=gigyaEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function gigyaEditDBRecord($params,$id=0,$opts=array()){
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'gigyaEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'gigyaEditDBRecord error: No where specified.';}
	global $USER;
	$fields=gigyaGetDBFieldInfo($params['-table']);
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
		debugValue(array("gigyaEditDBRecord Error",$e));
    	return;
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	$dbh_gigya=gigyaDBConnect($params);
	if(!$dbh_gigya){
    	$err=array(
			'msg'=>"gigyaEditDBRecord error",
			'error'	=> $dbh_gigya->lastErrorMsg(),
			'query'	=> $query
		);
    	debugValue(array("gigyaEditDBRecord Connect Error",$err));
    	return;
	}
	//enable exceptions
	$dbh_gigya->enableExceptions(true);
	try{
		$stmt=$dbh_gigya->prepare($query);
		foreach($vals as $i=>$v){
			$fld=$flds[$i];
			$x=$i+1;
			//echo "{$x}::{$v}::{$fields[$fld]['type']}<br>".PHP_EOL;
			switch(strtolower($fields[$fld]['type'])){
				case 'integer':
					$stmt->bindParam($x,$vals[$i],gigya3_INTEGER);
				break;
				case 'float':
					$stmt->bindParam($x,$vals[$i],gigya3_FLOAT);
				break;
				case 'blob':
					$stmt->bindParam($x,$vals[$i],gigya3_BLOB);
				break;
				case 'null':
					$stmt->bindParam($x,$vals[$i],gigya3_NULL);
				break;
				default:
					$stmt->bindParam($x,$vals[$i],gigya3_TEXT);
				break;
			}
		}
		$results=$stmt->execute();
		if($results){
			$results->finalize();
		}
		else{
			$err=$dbh_gigya->lastErrorMsg();
			if(strtolower($err) != 'not an error'){
				debugValue("gigyaEditDBRecord execute error: {$err}");
			}
			else{
				debugValue("gigyaEditDBRecord execute error: unknown reason");
			}
		}
		return 1;
	}
	catch (Exception $e) {
		$msg=$e->getMessage();
		debugValue(array(
			'function'=>'gigyaEditDBRecord',
			'message'=>'query failed',
			'error'=>$msg,
			'query'=>$query,
			'params'=>$params
		));
		return null;
	}
	return 0;
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
* switch(strtolower($m[1])){
			case 'accounts':$table='accounts';break;
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
//---------- begin function gigyaGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage $cnt=gigyaGetDBCount(array('-table'=>'states'));
*/
function gigyaGetDBCount($params=array()){
	if(!isset($params['-table'])){return null;}
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$params['-queryonly']=1;
	$query=gigyaGetDBRecords($params);
	if(!stringContains($query,'where')){
	 	$query1="SELECT tbl,stat FROM gigya_stat1 where tbl='{$params['-table']}' limit 1";
	 	$recs=gigyaQueryResults($query1);
	 	//echo "HERE".$query.printValue($recs);
	 	if(isset($recs[0]['stat']) && strlen($recs[0]['stat'])){
	 		$parts=preg_split('/\ /',$recs[0]['stat'],2);
	 		return (integer)$parts[0];
	 	}
	}
	//echo "HERE".$query.printValue($params);
	$recs=gigyaQueryResults($query);
	//echo "HERE2".$query.printValue($recs);exit;
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function gigyaListDBDatatypes ----
/**
* @describe returns the data types for gigya
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function gigyaListDBDatatypes(){
	//default to 
	return <<<ENDOFDATATYPES
<div class="w_bold w_blue w_padtop">Text Types</div>
<div class="w_padleft">TEXT - a text string, stored using the database encoding (UTF-8, UTF-16BE or UTF-16LE)</div>

<div class="w_bold w_blue w_padtop">Number Types</div>
<div class="w_padleft">INTEGER - signed integer, stored in 1, 2, 3, 4, 6, or 8 bytes depending on the magnitude of the value.</div>
<div class="w_padleft">NUMERIC - may contain values using all five storage classes.</div>
<div class="w_padleft">REAL( , ) - a floating point value, stored as an 8-byte IEEE floating point number.</div>

<div class="w_bold w_blue w_padtop">Other Types</div>
<div class="w_padleft">BLOB -  a blob of data, stored exactly as it was input.</div>
ENDOFDATATYPES;
}
//---------- begin function gigyaTruncateDBTable ----------
/**
* @describe truncates the specified table
* @param $table mixed - the table to truncate or and array of tables to truncate
* @return boolean integer
* @usage $cnt=gigyaTruncateDBTable('test');
*/
function gigyaTruncateDBTable($table){
	if(is_array($table)){$tables=$table;}
	else{$tables=array($table);}
	foreach($tables as $table){
		if(!gigyaIsDBTable($table)){return "No such table: {$table}.";}
		$result=gigyaExecuteSQL("DELETE FROM {$table}");
		if(isset($result['error'])){
			return $result['error'];
	        }
	    }
    return 1;
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
	//echo $url;exit;
	$post=postURL($url,array(
		'-method'=>'POST',
		'apiKey'=>$db['dbkey'],
		'userKey'=>$db['dbuser'],
		'secret'=>$db['dbpass'],
		'query'=>$query,
		'format'=>'json',
		'-json'=>1
	));
	//echo printValue($post);exit;
	if(!isset($post['json_array']['results']) && isset($post['json_array']['result'])){
		$post['json_array']['results']=$post['json_array']['result'];
	}
	if(isset($post['json_array']['results'])){
		//echo printValue($post['json_array']['results']);exit;
		$recs=array();
		foreach($post['json_array']['results'] as $result){
			//echo printValue($result);exit;
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
		}
		if(isset($params['-filename'])){
			$csv=arrays2CSV($recs);
			setFileContents($params['-filename'],$csv);
			return count($recs);
		}
		return $recs;
	}
	elseif(isset($post['json_array']['resultCount']) && $post['json_array']['resultCount']==0){
		return null;
	}
	elseif(isset($post['json_array'])){
		$recs=array();
		foreach($post['json_array'] as $k=>$v){
			$k=str_replace('UID','uid',$k);
			$k=str_replace('ID','Id',$k);
			$k = strtolower(preg_replace('/([A-Z])/', '_$1', $k));
			if(is_array($v)){$v=encodeJson($v);}
			$rec[$k]=$v;
		}
		$recs[]=$rec;
		if(isset($params['-filename'])){
			$csv=arrays2CSV($recs);
			setFileContents($params['-filename'],$csv);
			return count($recs);
		}
		return $recs;
	}
	echo printValue($post);exit;
}
//---------- begin function gigyaEnumQueryResults ----------
/**
* @describe enumerates through the data from a pg_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function gigyaEnumQueryResults($data,$params=array()){
	global $gigyaStopProcess;
	if(!is_object($data)){return null;}
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
			odbc_free_result($result);
			return 'gigyaQueryResults error: Failed to open '.$params['-filename'];
			exit;
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	$i=0;
	$writefile=0;
	if(isset($fh) && is_resource($fh)){
		$writefile=1;
	}
	while ($xrec = $data->fetchArray(gigya3_ASSOC)) {
		//check for gigyaStopProcess request
		if(isset($gigyaStopProcess) && $gigyaStopProcess==1){
			break;
		}
		$rec=array();
		foreach($xrec as $k=>$v){
			$k=strtolower($k);
			$rec[$k]=$v;
		}
    	if($writefile==1){
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
		elseif(isset($params['-index']) && isset($rec[$params['-index']])){
			$recs[$rec[$params['-index']]]=$rec;
		}
		else{
			$recs[]=$rec;
		}
	}
	$data->finalize();
	if($writefile==1){
		@fclose($fh);
		if(isset($params['-logfile']) && file_exists($params['-logfile'])){
			$elapsed=microtime(true)-$starttime;
			appendFileContents($params['-logfile'],"Line count:{$i}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
		}
		return $i;
	}
	return $recs;
}
//---------- begin function gigyaGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=gigyaGetDBRecord(array('-table'=>'tesl));
*/
function gigyaGetDBRecord($params=array()){
	$recs=gigyaGetDBRecords($params);
	//echo "gigyaGetDBRecord".printValue($params).printValue($recs);
	if(isset($recs[0])){return $recs[0];}
	return null;
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
