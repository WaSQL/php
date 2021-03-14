<?php

/*
	snowflake PDO
		https://github.com/snowflakedb/pdo_snowflake

*/
//---------- begin function snowflakeAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
* @return count int
* @usage $ok=snowflakeAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=snowflakeAddDBRecords('comments',array('-recs'=>$recs);
*/
function snowflakeAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("snowflakeAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	$params['-table']=$table;
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("snowflakeAddDBRecords Error: either -csv or -recs is required");
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("snowflakeAddDBRecords Error: no such file: {$params['-csv']}");
		}
		return processCSVLines($params['-csv'],'snowflakeAddDBRecordsProcess',$params);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("snowflakeAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("snowflakeAddDBRecords Error: no recs");
		}
		return snowflakeAddDBRecordsProcess($params['-recs'],$params);
	}
}
function snowflakeAddDBRecordsProcess($recs,$params=array()){
	if(!isset($params['-table'])){
		return debugValue("snowflakeAddDBRecordsProcess Error: no table"); 
	}
	$table=$params['-table'];
	$fieldinfo=snowflakeGetDBFieldInfo($table,1);
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
	//fields
	$fields=array();
	foreach($recs as $i=>$rec){
		foreach($rec as $k=>$v){
			if(!isset($fieldinfo[$k])){continue;}
			if(!in_array($k,$fields)){$fields[]=$k;}
		}
	}
	$fieldstr=implode(',',$fields);
	//values
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
				$v=snowflakeEscapeString($v);
				$rec[$k]="'{$v}'";
			}
		}
		$values[]='('.implode(',',array_values($rec)).')';
	}
	if(isset($params['-upsert']) && isset($params['-upserton'])){
		if(!is_array($params['-upsert'])){
			$params['-upsert']=preg_split('/\,/',$params['-upsert']);
		}
		if(!is_array($params['-upserton'])){
			$params['-upserton']=preg_split('/\,/',$params['-upserton']);
		}
		/*
			MERGE INTO Sales.SalesReason AS Target  
			USING (VALUES ('Recommendation','Other'), ('Review', 'Marketing'),
			              ('Internet', 'Promotion'))  
			       AS Source (NewName, NewReasonType)  
			ON Target.Name = Source.NewName  
			WHEN MATCHED THEN  
			UPDATE SET ReasonType = Source.NewReasonType  
			WHEN NOT MATCHED BY TARGET THEN  
			INSERT (Name, ReasonType) VALUES (NewName, NewReasonType)
		*/
		$query="MERGE INTO {$table} T1 USING ( VALUES ".PHP_EOL;
		$query.=implode(','.PHP_EOL,$values);
		$query.=') T2 ON ( ';
		$onflds=array();
		foreach($params['-upserton'] as $fld){
			$onflds[]="T1.{$fld}=T2.{$fld}";
		}
		$query .= implode(' AND ',$onflds).PHP_EOL;
		$query .= ') WHEN MATCHED THEN UPDATE SET ';
		$flds=array();
		foreach($params['-upsert'] as $fld){
			$flds[]="T1.{$fld}=T2.{$fld}";
		}
		$query.=PHP_EOL.implode(', ',$flds);
		$query .= " WHEN NOT MATCHED THEN INSERT ({$fieldstr}) VALUES ( ";
		$flds=array();
		foreach($params['-upsert'] as $fld){
			$flds[]="T2.{$fld}";
		}
		$query.=PHP_EOL.implode(', ',$flds);
		$query .= ')';
	}
	else{
		$query="INSERT INTO {$table} ({$fieldstr}) VALUES ".PHP_EOL;
		$query.=implode(','.PHP_EOL,$values);
	}
	$ok=snowflakeExecuteSQL($query);
	return count($values);
}
function snowflakeEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function snowflakeGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=snowflakeGetTableDDL('sample');
*/
function snowflakeGetTableDDL($table,$schema=''){
	if(!strlen($schema)){
		$schema=snowflakeGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('snowflakeGetTableDDL error: schema is not defined in config.xml');
		return null;
	}
	$schema=strtoupper($schema);
	$table=strtoupper($table);
	$query=<<<ENDOFQUERY
		SELECT GET_DDL('table','{$schema}.{$table}') as ddl
ENDOFQUERY;
	$recs=snowflakeQueryResults($query);
	if(isset($recs[0]['ddl'])){
		return $recs[0]['ddl'];
	}
	return $recs;
}
//---------- begin function snowflakeDropDBIndex--------------------
/**
* @describe drop an index previously created
* @param params array
*	-table
*	-name
* @return boolean
* @usage $ok=addDBIndex(array('-table'=>$table,'-name'=>"myindex"));
*/
function snowflakeDropDBIndex($params=array()){
	if(!isset($params['-table'])){return 'snowflakeDropDBIndex Error: No table';}
	if(!isset($params['-name'])){return 'snowflakeDropDBIndex Error: No name';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=snowflakeGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$params['-table']=strtolower($params['-table']);
	global $databaseCache;
	if(isset($databaseCache['snowflakeGetDBTableIndexes'][$params['-table']])){
		unset($databaseCache['snowflakeGetDBTableIndexes'][$params['-table']]);
	}
	//build and execute
	$query="alter table {$params['-table']} drop index {$params['-name']}";
	return snowflakeExecuteSQL($query);
}
//---------- begin function snowflakeDropDBTable--------------------
/**
* @describe drops the specified table
* @param table string - name of table to drop
* @param [meta] boolean - also remove metadata in _fielddata and _tabledata tables associated with this table. defaults to true
* @return 1
* @usage $ok=dropDBTable('comments',1);
*/
function snowflakeDropDBTable($table='',$meta=1){
	if(!strlen($table)){return 0;}
	//check for schema name
	if(!stringContains($table,'.')){
		$schema=snowflakeGetDBSchema();
		if(strlen($schema)){
			$table="{$schema}.{$table}";
		}
	}
	$result=snowflakeExecuteSQL("drop table {$table}");
    return 1;
}

//---------- begin function snowflakeDelDBRecord ----------
/**
* @describe deletes records in table that match -where clause
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
*	[-model] boolean - set to false to disable model functionality
* @return boolean
* @usage $id=snowflakeDelDBRecord(array('-table'=> '_tabledata','-where'=> "_id=4"));
*/
function snowflakeDelDBRecord(){
	global $USER;
	if(!isset($params['-table'])){return 'snowflakeDelDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'snowflakeDelDBRecord Error: No where';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=snowflakeGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$query="delete from {$params['-table']} where " . $params['-where'];
	return snowflakeExecuteSQL($query);
}

//---------- begin function snowflakeCreateDBTable--------------------
/**
* @describe creates table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=snowflakeCreateDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function snowflakeCreateDBTable($table='',$fields=array()){
	if(strlen($table)==0){return "snowflakeCreateDBTable error: No table";}
	if(count($fields)==0){return "snowflakeCreateDBTable error: No fields";}
	if(snowflakeIsDBTable($table)){return 0;}
	//check for schema name
	if(!stringContains($table,'.')){
		$schema=snowflakeGetDBSchema();
		if(strlen($schema)){
			$table="{$schema}.{$table}";
		}
	}
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
	$query_result=snowflakeExecuteSQL($query);
	return $query_result;
}

//---------- begin function snowflakeAddDBIndex--------------------
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
*	$ok=snowflakeAddDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=snowflakeAddDBIndex(array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function snowflakeAddDBIndex($params=array()){
	if(!isset($params['-table'])){return 'snowflakeAddDBIndex Error: No table';}
	if(!isset($params['-fields'])){return 'snowflakeAddDBIndex Error: No fields';}
	if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,+/',$params['-fields']);}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=snowflakeGetDBSchema();
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
	return snowflakeExecuteSQL($query);
}
function snowflakeGetDBSchema(){
	global $CONFIG;
	$params=snowflakeParseConnectParams();
	if(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['snowflake_dbschema'])){return $CONFIG['snowflake_dbschema'];}
	elseif(isset($CONFIG['snowflake_schema'])){return $CONFIG['snowflake_schema'];}
	return '';
}
//---------- begin function snowflakeGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=snowflakeGetDBRecordById('comments',7);
*/
function snowflakeGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "snowflakeGetDBRecordById Error: No Table";}
	if($id == 0){return "snowflakeGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=snowflakeGetDBRecord($recopts);
	return $rec;
}
//---------- begin function snowflakeEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=snowflakeEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function snowflakeEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("snowflakeEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("snowflakeEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("snowflakeEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("snowflakeEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return snowflakeEditDBRecord($params);
}
//---------- begin function snowflakeDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=snowflakeDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function snowflakeDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("snowflakeDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("snowflakeDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return snowflakeDelDBRecord($params);
}
//---------- begin function snowflakeListRecords
/**
* @describe returns an html table of records from a snowflake database. refer to databaseListRecords
*/
function snowflakeListRecords($params=array()){
	$params['-database']='snowflake';
	return databaseListRecords($params);
}


//---------- begin function snowflakeParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @exclude  - this function is for internal use and thus excluded from the manual
* @return $params array
* @usage 
*	loadExtras('snowflake');
*	$params=snowflakeParseConnectParams($params);
*/
function snowflakeParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^snowflake/i',$k)){unset($CONFIG[$k]);}
		}
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			$params["-{$k}"]=$v;	
		}
	}
	//check for user specific
	if(strlen($USER['username'])){
		foreach($params as $k=>$v){
			if(stringEndsWith($k,"_{$USER['username']}")){
				$nk=str_replace("_{$USER['username']}",'',$k);
				unset($params[$k]);
				$params[$nk]=$v;
			}
		}
	}
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['snowflake_connect'])){
			$params['-dbname']=$CONFIG['snowflake_connect'];
			$params['-dbname_source']="CONFIG snowflake_connect";
		}
		elseif(isset($CONFIG['dbname_snowflake'])){
			$params['-dbname']=$CONFIG['dbname_snowflake'];
			$params['-dbname_source']="CONFIG dbname_snowflake";
		}
		elseif(isset($CONFIG['snowflake_dbname'])){
			$params['-dbname']=$CONFIG['snowflake_dbname'];
			$params['-dbname_source']="CONFIG snowflake_dbname";
		}
		else{return 'snowflakeParseConnectParams Error: No dbname set';}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_snowflake'])){
			$params['-dbuser']=$CONFIG['dbuser_snowflake'];
			$params['-dbuser_source']="CONFIG dbuser_snowflake";
		}
		elseif(isset($CONFIG['snowflake_dbuser'])){
			$params['-dbuser']=$CONFIG['snowflake_dbuser'];
			$params['-dbuser_source']="CONFIG snowflake_dbuser";
		}
		else{return 'snowflakeParseConnectParams Error: No dbuser set';}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_snowflake'])){
			$params['-dbpass']=$CONFIG['dbpass_snowflake'];
			$params['-dbpass_source']="CONFIG dbpass_snowflake";
		}
		elseif(isset($CONFIG['snowflake_dbpass'])){
			$params['-dbpass']=$CONFIG['snowflake_dbpass'];
			$params['-dbpass_source']="CONFIG snowflake_dbpass";
		}
		else{return 'snowflakeParseConnectParams Error: No dbpass set';}
	}
	else{
		$params['-dbpass_source']="passed in";
	}
	return $params;
}
//---------- begin function snowflakeDBConnect ----------
/**
* @describe connects to a snowflake database via snowflake and returns the snowflake resource
* @param $param array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*   [-single] - if you pass in -single it will connect using snowflake_connect instead of snowflake_pconnect and return the connection
* @return $dbh_snowflake resource - returns the snowflake connection resource
* @exclude  - this function is for internal use and thus excluded from the manual
* @usage 
*	loadExtras('snowflake');
*	$dbh_snowflake=snowflakeDBConnect($params);
*/
function snowflakeDBConnect($params=array()){
	$params=snowflakeParseConnectParams($params);
	if(!isset($params['-connect'])){
		echo "snowflakeDBConnect error: no connect param".printValue($params);
		exit;
	}
	//confirm valid driver
	$available_drivers=PDO::getAvailableDrivers();
	list($driver,$str)=preg_split('/\:/',$params['-connect'],2);
	if($driver != 'snowflake'){
		echo "snowflakeDBConnect error: invalid connect string: {$params['-connect']}";
		exit; 
	}
	if(!in_array($driver,$available_drivers)){
		echo "snowflakeDBConnect error: pdo_snowflake is not installed: {$params['-connect']}";
		exit; 
	}
	global $dbh_snowflake;
	if(is_resource($dbh_snowflake)){return $dbh_snowflake;}
	//echo "snowflakeDBConnect".printValue($params);exit;
	try{
		$dbh_snowflake = new PDO($params['-connect'],$params['-dbuser'],$params['-dbpass']);
		$dbh_snowflake->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh_snowflake;
	}
	catch (Exception $e) {
		echo "snowflakeDBConnect exception" .$e->getMessage();
		echo printValue($params);
		exit;

	}
}
//---------- begin function snowflakeIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage 
*	loadExtras('snowflake');
*	if(snowflakeIsDBTable('abc','abcschema')){...}
*/
function snowflakeIsDBTable($table){
	$tables=snowflakeGetDBTables();
	$tables = array_map('strtolower', $tables);
	$table=strtolower($table);
	if(in_array($table,$tables)){return true;}
	return false;
}
//---------- begin function snowflakeClearConnection ----------
/**
* @describe clears the snowflake database connection
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('snowflake');
*	$ok=snowflakeClearConnection();
*/
function snowflakeClearConnection(){
	global $dbh_snowflake;
	$dbh_snowflake=null;
	return true;
}
//---------- begin function snowflakeExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('snowflake');
*	$ok=snowflakeExecuteSQL("truncate table abc");
*/
function snowflakeExecuteSQL($query){
	$dbh_snowflake=snowflakeDBConnect();
	if(!is_resource($dbh_snowflake)){
		//wait a couple of seconds and try again
		sleep(2);
		$dbh_snowflake=snowflakeDBConnect($params);
		if(!is_resource($dbh_snowflake)){
			$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
			debugValue("snowflakeDBConnect error".printValue($params));
			return false;
		}
		else{
			debugValue("snowflakeDBConnect recovered connection ");
		}
	}
	try{
		$result=pdo_exec($dbh_snowflake,$query);
		if(!$result){
			$err=pdo_errormsg($dbh_snowflake);
			debugValue($err);
			return false;
		}
		pdo_free_result($result);
		return true;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue($err);
		return false;
	}
	return true;
}

//---------- begin function snowflakeAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
*   -table - name of the table to add to
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('snowflake');
*	$id=snowflakeAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function snowflakeAddDBRecord($params){
	global $snowflakeAddDBRecordCache;
	global $USER;
	if(!isset($params['-table'])){return 'snowflakeAddDBRecord error: No table.';}
	$fields=snowflakeGetDBFieldInfo($params['-table'],$params);
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
		switch(strtolower($fields[$k]['type'])){
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$v=date('Y-m-d',strtotime($v));
				}
        	break;
			case 'nvarchar':
			case 'nchar':
				$v=snowflakeConvert2UTF8($v);
        	break;
		}
		//support for nextval
		if(preg_match('/\.nextval$/',$v)){
			$opts['bindings'][]=$v;
        	$opts['fields'][]=trim(strtoupper($k));
		}
		else{
			$opts['values'][]=$v;
			$opts['bindings'][]='?';
        	$opts['fields'][]=trim(strtoupper($k));
		}
	}
	$fieldstr=implode('","',$opts['fields']);
	$bindstr=implode(',',$opts['bindings']);
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			("{$fieldstr}")
		VALUES
			({$bindstr})
ENDOFQUERY;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!$dbh_snowflake){
    	$e=snowflake_errormsg();
    	debugValue(array("snowflakeAddDBRecord Connect Error",$e));
    	return "snowflakeAddDBRecord Connect Error".printValue($e);
	}
	try{
		if(!isset($snowflakeAddDBRecordCache[$params['-table']]['stmt'])){
			$snowflakeAddDBRecordCache[$params['-table']]['stmt']    = pdo_prepare($dbh_snowflake, $query);
			if(!is_resource($snowflakeAddDBRecordCache[$params['-table']]['stmt'])){
				$e=pdo_errormsg();
				$err=array("snowflakeAddDBRecord prepare Error",$e,$query);
				debugValue($err);
				return printValue($err);
			}
		}
		
		$success = pdo_execute($snowflakeAddDBRecordCache[$params['-table']]['stmt'],$opts['values']);
		if(!$success){
			$e=pdo_errormsg();
			debugValue(array("snowflakeAddDBRecord Execute Error",$e,$opts));
    		return "snowflakeAddDBRecord Execute Error".printValue($e);
		}
		if(isset($params['-noidentity'])){return $success;}
		$result2=pdo_exec($dbh_snowflake,"SELECT top 1 ifnull(CURRENT_IDENTITY_VALUE(),0) as cval from {$params['-table']};");
		$row=pdo_fetch_array($result2,0);
		pdo_free_result($result2);
		$row=array_change_key_case($row);
		if(isset($row['cval'])){return $row['cval'];}
		return "snowflakeAddDBRecord Identity Error".printValue($row).printValue($opts);
		//echo "result2:".printValue($row);
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		$recs=array($err);
		debugValue(array("snowflakeAddDBRecord Try Error",$e));
		return "snowflakeAddDBRecord Try Error".printValue($err);
	}
	return 0;
}
//---------- begin function snowflakeEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
*   -table - name of the table to add to
*   -where - filter criteria
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage 
*	loadExtras('snowflake');
*	$id=snowflakeEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function snowflakeEditDBRecord($params,$id=0,$opts=array()){
	mb_internal_encoding("UTF-8");
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'snowflakeEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'snowflakeEditDBRecord error: No where specified.';}
	global $USER;
	$fields=snowflakeGetDBFieldInfo($params['-table'],$params);
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
	$vals=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!isset($fields[$k])){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		switch(strtolower($fields[$k]['type'])){
			case 'nvarchar':
			case 'nchar':
				$v=snowflakeConvert2UTF8($v);
			break;
        	case 'date':
				if($k=='edate' || $k=='_edate'){
					$v=date('Y-m-d',strtotime($v));
				}
        	break;
		}
		$updates[]="{$k}=?";
		$vals[]=$v;
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	global $dbh_snowflake;
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!$dbh_snowflake){
    	$e=pdo_errormsg();
    	debugValue(array("snowflakeEditDBRecord2 Connect Error",$e));
    	return;
	}
	try{
		$snowflake_stmt    = pdo_prepare($dbh_snowflake, $query);
		if(!is_resource($snowflake_stmt)){
			$e=pdo_errormsg();
			debugValue(array("snowflakeEditDBRecord2 prepare Error",$e));
    		return 1;
		}
		$success = pdo_execute($snowflake_stmt,$vals);
		//echo $vals[5].$query.printValue($success).printValue($vals);
	}
	catch (Exception $e) {
		debugValue(array("snowflakeEditDBRecord2 try Error",$e));
    	return;
	}
	return 0;
}

//---------- begin function snowflakeGetDBRecords
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
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - set of records
* @usage
*	loadExtras('snowflake');
*	$recs=snowflakeGetDBRecords(array('-table'=>'notes','name'=>'bob'));
*	$recs=snowflakeGetDBRecords("select * from notes where name='bob'");
*/
function snowflakeGetDBRecords($params){
	global $USER;
	global $CONFIG;
	if(empty($params['-table']) && !is_array($params)){
		$params=trim($params);
		if(preg_match('/^(select|exec|with|explain|returning|show|call)[\t\s\ \r\n]/i',$params)){
			//they just entered a query
			$query=$params;
			$params=array();
		}
		else{
			$ok=snowflakeExecuteSQL($params);
			return $ok;
		}
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
		$fields=snowflakeGetDBFieldInfo($params['-table'],$params);
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
	return snowflakeQueryResults($query,$params);
}
//---------- begin function snowflakeGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage 
*	loadExtras('snowflake');
*	$tables=snowflakeGetDBTables();
*/
function snowflakeGetDBTables($dirty=0){
	global $snowflakeGetDBTablesCache;
	if($dirty==0 && is_array($snowflakeGetDBTablesCache) && count($snowflakeGetDBTablesCache)){
		return $snowflakeGetDBTablesCache;
	}
	$schema=snowflakeGetDBSchema();
	$query=<<<ENDOFQUERY
		SELECT table_name
		FROM information_schema.tables 
		WHERE 
			table_type = 'BASE TABLE'
			and  table_schema='{$schema}'
		ORDER BY table_name 
ENDOFQUERY;
	$recs=snowflakeQueryResults($query);
	$tables=array();
	foreach($recs as $rec){
		$tables[]=$rec['table_name'];
	}
	$snowflakeGetDBTablesCache=$tables;
    return $tables;
}
//---------- begin function snowflakeGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage
*	loadExtras('snowflake'); 
*	$fieldinfo=snowflakeGetDBFieldInfo('abcschema.abc');
*/
function snowflakeGetDBFieldInfo($table,$params=array()){
	global $CONFIG;
	if(!isset($CONFIG['dbschema'])){
		echo "snowflakeGetDBTables error: no dbschema defined";
		exit;
	}
	$schema=strtoupper($CONFIG['dbschema']);
	$query=<<<ENDOFQUERY
		SELECT 
			ordinal_position as position,
       		column_name,
       		data_type,
       		numeric_precision,
       		case when character_maximum_length is not null
            	then character_maximum_length
            	else numeric_precision 
            	end as max_length,
       		is_nullable,
       		column_default as default_value
		FROM information_schema.columns
		WHERE 
			table_schema = '{$schema}'
       		and table_name ilike '{$table}'
		ORDER BY ordinal_position;
ENDOFQUERY;
	$recs=snowflakeQueryResults($query);
	$info=array();
	foreach($recs as $rec){
		$field=strtolower($rec['column_name']);
        $info[$field]=array(
        	'table'		=> $table,
        	'_dbtable'	=> $table,
			'name'		=> $field,
			'_dbfield'	=> $field,
			'type'		=> strtolower($rec['data_type']),
			'precision'	=> strtolower($rec['numeric_precision']),
			'length'	=> strtolower($rec['max_length']),
		);
		$recs[$field]['_dbtype']=$recs[$field]['type'];
		$recs[$field]['_dblength']=$recs[$field]['length'];
	}
	return $info;
}
//---------- begin function snowflakeGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage
*	loadExtras('snowflake'); 
*	$cnt=snowflakeGetDBCount(array('-table'=>'states','-where'=>"code like 'M%'"));
*/
function snowflakeGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=snowflakeGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function snowflakeQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of snowflake connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage
*	loadExtras('snowflake'); 
*	$recs=snowflakeQueryResults('select top 50 * from abcschema.abc');
*/
function snowflakeQueryResults($query,$params=array()){
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect();
	try{
		$stmt = $dbh_snowflake->query($query);
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$msg="snowflakeQueryResults ERROR: ".implode('-',$err);
		//echo $msg;
		return $msg;
	}
	$header=0;
	if(isset($fh)){unset($fh);}
	if(isset($params['-filename'])){
		if(file_exists($params['-filename'])){unlink($params['-filename']);}
		$logfile=str_replace('.csv','.log',$params['-filename']);
		setFileContents($logfile,$query.PHP_EOL.PHP_EOL);
    	$fh = fopen($params['-filename'],"wb");
    	if(!$fh){
    		appendFileContents($logfile,'failed to open'.PHP_EOL);
    		echo 'Failed to open '.$params['-filename'];
    		exit;
    	}
	}
	else{$recs=array();}
	for($i=0; $rec = $stmt->fetch(PDO::FETCH_ASSOC); $i++){
		foreach($rec as $k=>$v){
			$rec[$k]=trim($v);
		}
		if(isset($params['-results_eval'])){
			$rec=call_user_func($params['-results_eval'],$rec);;
		}
		if(isset($params['-filename']) && !isset($fh)){
	    	$fh = fopen($params['-filename'],"a");
	    	if(!$fh){
	    		echo 'Failed to open '.$params['-filename'];
	    		appendFileContents($logfile,'failed to open'.PHP_EOL);
	    		exit;
	    	}
		}
		if(isset($fh)){
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
			//@fclose($fh);echo "HERE";exit;
			//write to the log file every 1000
			if($i % 5000 == 0){
				appendFileContents($logfile,$i.PHP_EOL);
			}
			continue;
		}
		elseif(isset($params['-function'])){
			if(isset($params['-addfields']) && is_array($params['-addfields'])){
            	foreach($params['-addfields'] as $k=>$v){
                	$rec[$k]=$v;
				}
			}
			$ok=call_user_func($params['-function'],$rec);
		}
		elseif(isset($params['-process'])){
			if(isset($params['-addfields']) && is_array($params['-addfields'])){
            	foreach($params['-addfields'] as $k=>$v){
                	$rec[$k]=$v;
				}
			}
			$ok=call_user_func($params['-process'],$rec);
		}
		else{
			$recs[]=$rec;
		}
	}
	$stmt=null;
	$dbh_snowflake=null;
	if($fh){
		@fclose($fh);
		return $i;
	}
	return $recs;
}
function snowflakeConvert2UTF8($content) { 
    if(!mb_check_encoding($content, 'UTF-8') 
        OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {

        $content = mb_convert_encoding($content, 'UTF-8'); 

        if (mb_check_encoding($content, 'UTF-8')) { 
            // log('Converted to UTF-8'); 
        } else { 
            // log('Could not converted to UTF-8'); 
        } 
    } 
    return $content; 
}
//---------- begin function snowflakeNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function snowflakeNamedQuery($name){
	switch(strtolower($name)){
		case 'running_queries':
			return <<<ENDOFQUERY
			show transactions
ENDOFQUERY;
		break;
		case 'sessions':
			return <<<ENDOFQUERY
ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY
			show locks
ENDOFQUERY;
		break;
	}
}