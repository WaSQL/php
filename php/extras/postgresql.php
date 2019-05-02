<?php
/*
	postgresql.php - a collection of postgresqls functions for use by WaSQL.
	
	References:
		https://en.wikibooks.org/wiki/Converting_MySQL_to_PostgreSQL
		https://www.convert-in.com/mysql-to-postgresqls-types-mapping.htm
		https://medium.com/coding-blocks/creating-user-database-and-adding-access-on-postgresqlsql-8bfcd2f4a91e
		https://stackoverflow.com/questions/15520361/permission-denied-for-relation

	Json_table equivilent in PostgreSQL
		drop TYPE json_test;
		create  TYPE json_test AS (id_item int, id_menu varchar(100));
		select * from json_populate_recordset(null::json_test,'[{"id_item":1,"id_menu":"34"},{"id_item":2,"id_menu":"35"}]')

*/

//---------- begin function postgresqlAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*   -table - name of the table to add to
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=postgresqlAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function postgresqlAddDBRecord($params=array()){
	global $USER;
	if(!isset($params['-table'])){return 'postgresqlAddRecord error: No table specified.';}
	$finfo=postgresqlGetDBFieldInfo($params['-table'],$params);
	$sequence='';
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
	$fields=array();
	$values=array();
	$prepares=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($finfo[$k])){continue;}
		if(is_array($params[$k])){
            	$params[$k]=implode(':',$params[$k]);
		}
		$fields[]=$k;
		$values[]=$params[$k];
        $prepares[]='$'.count($values);
	}
	$fieldstr=implode(',',$fields);
	$preparestr=implode(',',$prepares);
	//determine output field - identity column to return
	/*
		PostgreSQL will automatically generate and populate values into the SERIAL column. 
		This is similar to AUTO_INCREMENT column in MySQL or AUTOINCREMENT column in SQLite.
		You can create inserts that return a value... so you can return the identity field
	*/
	$output='';
	$output_field='_id';
	foreach($finfo as $field=>$info){
		if($info['identity']==1){
			$output=" returning {$field}";
			$output_field=$field;
			break;
		}
		elseif($info['sequence']==1){
			$output=" returning {$field}";
			$output_field=$field;
			break;
		}
		elseif($field=='_id'){
			$output=" returning {$field}";
			break;
		}
	}
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			({$fieldstr})
		VALUES(
			{$preparestr}
		)
		{$output}
ENDOFQUERY;

	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect($params);
	}
	if(!$dbh_postgresql){
		debugValue(array(
			'function'=>'postgresqlAddDBRecord',
			'message'=>'connect failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
    	return;
	}
	if(!pg_prepare($dbh_postgresql,'postgresqlAddDBRecord',$query)){
		debugValue(array(
			'function'=>'postgresqlAddDBRecord',
			'message'=>'pg_prepare failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
		pg_close($dbh_postgresql);
		return;
	}
	$data=pg_execute('postgresqlAddDBRecord',$values);
	$recs = postgresqlEnumQueryResults($data);
	pg_close($dbh_postgresql);
	if(isset($recs[0][$output_field])){return $recs[0][$output_field];}
	return $recs;
}
//---------- begin function postgresqlCreateDBTable--------------------
/**
* @describe creates postgresql table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=postgresqlCreateDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function postgresqlCreateDBTable($table='',$fields=array()){
	$function='createDBTable';
	if(strlen($table)==0){return "postgresqlCreateDBTable error: No table";}
	if(count($fields)==0){return "postgresqlCreateDBTable error: No fields";}
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
//---------- begin function postgresqlDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of database.
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return connection resource and sets the global $dbh_postgresql variable.
* @usage $dbh_postgresql=postgresqlDBConnect($params);
*/
function postgresqlDBConnect($params=array()){
	$params=postgresqlParseConnectParams($params);
	if(!isset($params['-dbname']) && !isset($params['-connect'])){
		echo "postgresqlDBConnect error: no connect params".printValue($params);
		exit;
	}
	global $dbh_postgresql;
	if(is_resource($dbh_postgresql)){return $dbh_postgresql;}
	//echo printValue($params);exit;
	try{
		$dbh_postgresql = pg_connect($params['-connect']);
		if(!is_resource($dbh_postgresql)){
			$err=pg_last_error();
			echo "postgresqlDBConnect error:{$err}".printValue($params);
			exit;

		}
		return $dbh_postgresql;
	}
	catch (Exception $e) {
		echo "postgresqlDBConnect exception" . printValue($e);
		exit;
	}
}

//---------- begin function postgresqlEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*   -table - name of the table to add to
*   -where - filter criteria
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage $id=postgresqlEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function postgresqlEditDBRecord($params=array(),$id=0,$opts=array()){
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'postgresqlEditRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'postgresqlEditRecord error: No where specified.';}
	global $USER;
	$finfo=postgresqlGetDBFieldInfo($params['-table'],$params);
	$opts=array();
	if(isset($finfo['edate'])){
		$params['edate']=strtoupper(date('Y-M-d H:i:s'));
	}
	elseif(isset($finfo['_edate'])){
		$params['_edate']=strtoupper(date('Y-M-d H:i:s'));
	}
	if(isset($finfo['euser'])){
		$params['euser']=$USER['username'];
	}
	elseif(isset($finfo['_euser'])){
		$params['_euser']=$USER['username'];
	}
	$sets=array();
	$values=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($finfo[$k])){continue;}
		if(is_array($params[$k])){
            $params[$k]=implode(':',$params[$k]);
		}
		$p=count($sets)+1;
		$sets[]="{$k}=\${$p}";
		$values[]=$params[$k];
	}
	$setstr=implode(', ',$sets);
	$output='';
	$output_field='_id';
	foreach($finfo as $field=>$info){
		if($info['identity']==1){
			$output=" returning {$field}";
			$output_field=$field;
			break;
		}
		elseif($info['sequence']==1){
			$output=" returning {$field}";
			$output_field=$field;
			break;
		}
		elseif($field=='_id'){
			$output=" returning {$field}";
			break;
		}
	}
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$setstr}
		WHERE {$params['-where']}
		{$output}
ENDOFQUERY;
	//echo $query;exit;
	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect($params);
	}
	if(!$dbh_postgresql){
		debugValue(array(
			'function'=>'postgresqlEditDBRecord',
			'message'=>'connect failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
    	return;
	}
	if(!pg_prepare($dbh_postgresql,'postgresqlEditDBRecord',$query)){
		debugValue(array(
			'function'=>'postgresqlEditDBRecord',
			'message'=>'pg_prepare failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
		pg_close($dbh_postgresql);
		return;
	}
	$data=pg_execute('postgresqlEditDBRecord',$values);
	$recs = postgresqlEnumQueryResults($data);
	pg_close($dbh_postgresql);
	return $recs;
}
//---------- begin function postgresqlExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage $ok=postgresqlExecuteSQL("truncate table abc");
*/
function postgresqlExecuteSQL($query,$params=array()){
	global $dbh_postgresql;
	$dbh_postgresql=postgresqlDBConnect($params);
	if(!$dbh_postgresql){
		debugValue(array(
			'function'=>'postgresqlExecuteSQL',
			'message'=>'connect failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
    	return;
	}
	try{
		$result=@pg_query($dbh_postgresql,$query);
		if(!$result){
			debugValue(array(
				'function'=>'postgresqlExecuteSQL',
				'message'=>'pg_query failed',
				'error'=>pg_last_error(),
				'query'=>$query
			));
			pg_close($dbh_postgresql);
			return;
		}
		pg_close($dbh_postgresql);
		return true;
	}
	catch (Exception $e) {
		debugValue(array(
			'function'=>'postgresqlExecuteSQL',
			'message'=>'try catch failed',
			'error'=>$e->errorInfo,
			'query'=>$query
		));
		return false;
	}
	return true;
}
//---------- begin function postgresqlGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=postgresqlGetDBCount(array('-table'=>'states'));
*/
function postgresqlGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=postgresqlGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue(array(
			'function'=>'postgresqlGetDBCount',
			'message'=>'get count failed',
			'error'=>$recs,
			'params'=>$params
		));
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function postgresqlGetDBDatabases ----------
/**
* @describe returns an array of databases
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of databases
* @usage $tables=postgresqlGetDBDatabases();
*/
function postgresqlGetDBDatabases($params=array()){
	$query=<<<ENDOFQUERY
		SELECT datname as name 
		FROM pg_database
		WHERE datistemplate = false
ENDOFQUERY;
	$recs = postgresqlQueryResults($query,$params);
	return $recs;
}
//---------- begin function postgresqlGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=postgresqlGetDBFieldInfo('test');
*/
function postgresqlGetDBFieldInfo($table,$params=array()){
	global $CONFIG;
	global $USER;
	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect($params);
	}
	if(!$dbh_postgresql){
		debugValue(array(
			'function'=>'postgresqlGetDBFieldInfo',
			'message'=>'connect failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
    	return;
	}
	//check for identity fields
	$table=strtolower($table);
	
	//echo printValue($idfields);exit;
	$query="SELECT * from {$table} where 1=0";
	$res=@pg_query($dbh_postgresql,$query);
	$fields=array();
	$i = pg_num_fields($res);
	for ($j = 0; $j < $i; $j++) {
	    $fieldname = pg_field_name($res, $j);
		$field=array(
		 	'_dbfield'	=> $fieldname,
		 	'_dbtype'	=> pg_field_type($res, $j),
			'length'	=> pg_field_prtlen($res, $j),
			'num'		=> pg_field_num($res, $j),
			'size'		=> pg_field_size($res, $j),
			'nullable'	=> pg_field_is_null($res, $j),
		);
		$field['_dbtype']=$field['_dbtype_ex']=strtolower($field['_dbtype']);
		if($field['size'] > 0){
			$field['_dbtype_ex']=strtolower("{$field['_dbtype']}({$field['size']})");
		}
		$fields[$fieldname]=$field;
	}
	pg_close($dbh_postgresql);
	$recs=postgresqlQueryResults("select column_name from information_schema.columns where table_name='{$table}' and is_identity='YES'");
	$idfields=array();
	foreach($recs as $rec){
		$fields[$rec['column_name']]['identity']=1;
	}
	ksort($fields);
	return $fields;
}
//---------- begin function postgresqlGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=postgresqlGetDBRecord(array('-table'=>'tesl));
*/
function postgresqlGetDBRecord($params=array()){
	$recs=postgresqlGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function postgresqlGetDBRecords
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
*	<?=postgresqlGetDBRecords(array('-table'=>'notes'));?>
*	<?=postgresqlGetDBRecords("select * from myschema.mytable where ...");?>
*/
function postgresqlGetDBRecords($params){
	global $USER;
	global $CONFIG;
	if(empty($params['-table']) && !is_array($params)){
		if(stringBeginsWith($params,"select ") || stringBeginsWith($params,"show ") || stringBeginsWith($params,"with ") || stringBeginsWith($params,"explain ") || stringContains($params,' returning ')){
			//they just entered a query
			$query=$params;
			$params=array();
		}
		else{
			$ok=postgresqlExecuteSQL($params);
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
		$fields=postgresqlGetDBFieldInfo($params['-table'],$params);
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
	return postgresqlQueryResults($query,$params);
}
//---------- begin function postgresqlGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of tables
* @usage $tables=postgresqlGetDBTables();
*/
function postgresqlGetDBTables($params=array()){
	global $CONFIG;
	if(!isset($CONFIG['postgresql_schema'])){$CONFIG['postgresql_schema']='public';}
	$query="SELECT tablename as name from pg_tables where schemaname='{$CONFIG['postgresql_schema']}' ORDER BY tablename";
	$recs = postgresqlQueryResults($query,$params);
	//echo $query.printValue($recs);exit;
	$tables=array();
	foreach($recs as $rec){$tables[]=$rec['name'];}
	return $tables;
}
//---------- begin function postgresqlGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of primary key fields
* @usage $fields=postgresqlGetDBTablePrimaryKeys();
*/
function postgresqlGetDBTablePrimaryKeys($table,$params=array()){
	global $CONFIG;
	$query=<<<ENDOFQUERY
		SELECT 
			kc.column_name,
			kc.constraint_name
		FROM  
		    information_schema.table_constraints tc,  
		    information_schema.key_column_usage kc  
		where 
		    tc.constraint_type = 'PRIMARY KEY' 
		    and kc.table_name = tc.table_name 
		    and kc.table_schema = tc.table_schema
		    tc.table_catalog = '{$CONFIG['postgresql_dbname']}'
		    and kc.constraint_name = tc.constraint_name
		order by 1, 2;
ENDOFQUERY;
	$tmp = postgresqlQueryResults($query,$params);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
//---------- begin function postgresqlListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function postgresqlListRecords($params=array()){
	$params['-database']='postgresql';
	return databaseListRecords($params);
}
//---------- begin function postgresqlParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=postgresqlParseConnectParams($params);
*/
function postgresqlParseConnectParams($params=array()){
	global $CONFIG;
	//dbhost
	if(!isset($params['-dbhost'])){
		if(isset($CONFIG['dbhost_postgresql'])){
			$params['-dbhost']=$CONFIG['dbhost_postgresql'];
			$params['-dbhost_source']="CONFIG dbhost_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbhost'])){
			$params['-dbhost']=$CONFIG['postgresql_dbhost'];
			$params['-dbhost_source']="CONFIG postgresql_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		$params['-dbhost_source']="passed in";
	}
	$CONFIG['postgresql_dbhost']=$params['-dbhost'];
	
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_postgresql'])){
			$params['-dbuser']=$CONFIG['dbuser_postgresql'];
			$params['-dbuser_source']="CONFIG dbuser_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbuser'])){
			$params['-dbuser']=$CONFIG['postgresql_dbuser'];
			$params['-dbuser_source']="CONFIG postgresql_dbuser";
		}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	$CONFIG['postgresql_dbuser']=$params['-dbuser'];
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_postgresql'])){
			$params['-dbpass']=$CONFIG['dbpass_postgresql'];
			$params['-dbpass_source']="CONFIG dbpass_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbpass'])){
			$params['-dbpass']=$CONFIG['postgresql_dbpass'];
			$params['-dbpass_source']="CONFIG postgresql_dbpass";
		}
	}
	else{
		$params['-dbpass_source']="passed in";
	}
	$CONFIG['postgresql_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_postgresql'])){
			$params['-dbname']=$CONFIG['dbname_postgresql'];
			$params['-dbname_source']="CONFIG dbname_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbname'])){
			$params['-dbname']=$CONFIG['postgresql_dbname'];
			$params['-dbname_source']="CONFIG postgresql_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['postgresql_dbuser'];
			$params['-dbname_source']="set to username";
		}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	$CONFIG['postgresql_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_postgresql'])){
			$params['-dbport']=$CONFIG['dbport_postgresql'];
			$params['-dbport_source']="CONFIG dbport_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbport'])){
			$params['-dbport']=$CONFIG['postgresql_dbport'];
			$params['-dbport_source']="CONFIG postgresql_dbport";
		}
		else{
			$params['-dbport']=5432;
			$params['-dbport_source']="default port";
		}
	}
	else{
		$params['-dbport_source']="passed in";
	}
	$CONFIG['postgresql_dbport']=$params['-dbport'];
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['postgresql_connect'])){
			$params['-connect']=$CONFIG['postgresql_connect'];
			$params['-connect_source']="CONFIG postgresql_connect";
		}
		elseif(isset($CONFIG['connect_postgresql'])){
			$params['-connect']=$CONFIG['connect_postgresql'];
			$params['-connect_source']="CONFIG connect_postgresql";
		}
		else{
			//build connect - http://php.net/manual/en/function.pg-connect.php
			//$conn_string = "host=sheep port=5432 dbname=test user=lamb password=bar";
			$params['-connect']="host={$CONFIG['postgresql_dbhost']} port={$CONFIG['postgresql_dbport']} dbname={$CONFIG['postgresql_dbname']} user={$CONFIG['postgresql_dbuser']} password={$CONFIG['postgresql_dbpass']}";
			$params['-connect_source']="manual";
		}
	}
	else{
		$params['-connect_source']="passed in";
	}
	return $params;
}
//---------- begin function postgresqlQueryResults ----------
/**
* @describe returns the postgresql record set
* @param query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - returns records
*/
function postgresqlQueryResults($query='',$params=array()){
	$query=trim($query);
	global $USER;
	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect($params);
	}
	if(!$dbh_postgresql){
		debugValue(array(
			'function'=>'postgresqlQueryResults',
			'message'=>'connect failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
    	return;
	}
	$data=@pg_query($dbh_postgresql,$query);
	if(!$data){return "postgresqlQueryResults Query Error: " . pg_last_error();}
	if(preg_match('/^insert /i',$query) && !stringContains($query,' returning ')){
    	//return the id inserted on insert statements

    	$id=databaseAffectedRows($data);
    	pg_close($dbh_postgresql);
    	return $id;
	}

	$results = postgresqlEnumQueryResults($data);
	pg_close($dbh_postgresql);
	return $results;
}
//---------- begin function postgresqlEnumQueryResults ----------
/**
* @describe enumerates through the data from a pg_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function postgresqlEnumQueryResults($data,$showblank=0,$fieldmap=array()){
	$results=array();
	while ($row = @pg_fetch_assoc($data)){
		$crow=array();
		foreach($row as $key=>$val){
			if(!$showblank && !strlen(trim($val))){continue;}
			$key=strtolower($key);
			if(isset($fieldmap[$key])){$key=$fieldmap[$key];}
			$crow[$key]=$val;
    	}
    	$results[] = $crow;
	}
	return $results;
}

