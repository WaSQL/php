<?php
/*
	postgre.php - a collection of postgres functions for use by WaSQL.
	
	References:
		https://en.wikibooks.org/wiki/Converting_MySQL_to_PostgreSQL
		https://www.convert-in.com/mysql-to-postgres-types-mapping.htm
		https://medium.com/coding-blocks/creating-user-database-and-adding-access-on-postgresql-8bfcd2f4a91e
*/

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
	//echo printValue($params);exit;
	try{
		$dbh_postgre = pg_connect($params['-connect']);
		if(!is_resource($dbh_postgre)){
			$err=pg_last_error();
			echo "postgreDBConnect error:{$err}".printValue($params);
			exit;

		}
		return $dbh_postgre;
	}
	catch (Exception $e) {
		echo "postgreDBConnect exception" . printValue($e);
		exit;
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
    	$e=pg_last_error();
    	debugValue(array("postgreExecuteSQL Connect Error",$e));
    	return;
	}
	try{
		$result=@pg_query($query,$dbh_postgre);
		if(!$result){
			$e=pg_last_error();
			pg_close($dbh_postgre);
			debugValue(array("postgreExecuteSQL Connect Error",$e));
			return;
		}
		pg_close($dbh_postgre);
		return true;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue($err);
		return false;
	}
	return true;
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
	$query=<<<ENDOFQUERY
		SELECT datname as name 
		FROM pg_database
		WHERE datistemplate = false
ENDOFQUERY;
	$recs = postgreQueryResults($query,$params);
	return $recs;
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
	$query=<<<ENDOFQUERY
		SELECT
			column_name,
			data_type,
			character_maximum_length,
			numeric_precision,
			numeric_precision_radix,
			ordinal_position,
			column_default,
			is_nullable,
			is_identity
		FROM
			INFORMATION_SCHEMA.COLUMNS
		WHERE
			table_catalog = '{$CONFIG['postgre_dbname']}'
    		and table_schema = 'dbo'
			and table_name = '{$table}'
		ORDER BY 
			ordinal_position
ENDOFQUERY;
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
//---------- begin function postgreGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgre,dbuser_postgre, and dbpass_postgre
*	[-host] - postgre server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of primary key fields
* @usage $fields=postgreGetDBTablePrimaryKeys();
*/
function postgreGetDBTablePrimaryKeys($table,$params=array()){
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
		    tc.table_catalog = '{$CONFIG['postgre_dbname']}'
		    and kc.constraint_name = tc.constraint_name
		order by 1, 2;
ENDOFQUERY;
	$tmp = postgreQueryResults($query,$params);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
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
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
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
	$CONFIG['postgre_dbuser']=$params['-dbuser'];
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
	$CONFIG['postgre_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_postgre'])){
			$params['-dbname']=$CONFIG['dbname_postgre'];
			$params['-dbname_source']="CONFIG dbname_postgre";
		}
		elseif(isset($CONFIG['postgre_dbname'])){
			$params['-dbname']=$CONFIG['postgre_dbname'];
			$params['-dbname_source']="CONFIG postgre_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['postgre_dbuser'];
			$params['-dbname_source']="set to username";
		}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	$CONFIG['postgre_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_postgre'])){
			$params['-dbport']=$CONFIG['dbport_postgre'];
			$params['-dbport_source']="CONFIG dbport_postgre";
		}
		elseif(isset($CONFIG['postgre_dbport'])){
			$params['-dbport']=$CONFIG['postgre_dbport'];
			$params['-dbport_source']="CONFIG postgre_dbport";
		}
		else{
			$params['-dbport']=5432;
			$params['-dbport_source']="default port";
		}
	}
	else{
		$params['-dbport_source']="passed in";
	}
	$CONFIG['postgre_dbport']=$params['-dbport'];
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
			//build connect - http://php.net/manual/en/function.pg-connect.php
			//$conn_string = "host=sheep port=5432 dbname=test user=lamb password=bar";
			$params['-connect']="host={$CONFIG['postgre_dbhost']} port={$CONFIG['postgre_dbport']} dbname={$CONFIG['postgre_dbname']} user={$CONFIG['postgre_dbuser']} password={$CONFIG['postgre_dbpass']}";
			$params['-connect_source']="manual";
		}
	}
	else{
		$params['-connect_source']="passed in";
	}
	return $params;
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
    	$e=pg_last_error();
    	debugValue(array("postgreQueryResults Connect Error",$e));
    	return;
	}
	$data=@pg_query($dbh_postgre,$query);
	if(!$data){return "Postgres Query Error: " . pg_last_error();}
	if(preg_match('/^insert /i',$query)){
    	//return the id inserted on insert statements
    	$id=databaseAffectedRows($data);
    	pg_close($dbh_postgre);
    	return $id;
	}
	$results = postgreEnumQueryResults($data);
	pg_close($dbh_postgre);
	return $results;
}
//---------- begin function postgreEnumQueryResults ----------
/**
* @describe enumerates through the data from a pg_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function postgreEnumQueryResults($data,$showblank=0,$fieldmap=array()){
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

