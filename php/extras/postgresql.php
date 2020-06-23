<?php
/*
	postgresql.php - a collection of postgresqls functions for use by WaSQL.
	
	References:
		https://en.wikibooks.org/wiki/Converting_MySQL_to_PostgreSQL
		https://www.convert-in.com/mysql-to-postgresqls-types-mapping.htm
		https://medium.com/coding-blocks/creating-user-database-and-adding-access-on-postgresqlsql-8bfcd2f4a91e
		https://stackoverflow.com/questions/15520361/permission-denied-for-relation

	NOTE: make sure pgsql.auto_reset_persistent in php.ini is set to On.  this will get rid of "server closed the connection unexpectedly" errors

	Json_table equivilent in PostgreSQL
		drop TYPE json_test;
		create  TYPE json_test AS (id_item int, id_menu varchar(100));
		select * from json_populate_recordset(null::json_test,'[{"id_item":1,"id_menu":"34"},{"id_item":2,"id_menu":"35"}]')

*/

//---------- begin function postgresqlAddDBIndex--------------------
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
*	$ok=postgresqlAddDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=postgresqlAddDBIndex(array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function postgresqlAddDBIndex($params=array()){
	if(!isset($params['-table'])){return 'postgresqlAddDBIndex Error: No table';}
	if(!isset($params['-fields'])){return 'postgresqlAddDBIndex Error: No fields';}
	if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,+/',$params['-fields']);}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=postgresqlGetDBSchema();
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
	return postgresqlExecuteSQL($query);
}


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
	global $CONFIG;
	if(!isset($params['-table'])){return 'postgresqlAddRecord error: No table specified.';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$finfo=postgresqlGetDBFieldInfo($params['-table'],$params);

	$sequence='';
	$opts=array();
	if(isset($finfo['cdate'])){
		$params['cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	elseif(isset($finfo['_cdate'])){
		$params['_cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($finfo['cuser'])){
		$params['cuser']=(function_exists('isUser') && isUser())?$USER['_id']:0;
	}
	elseif(isset($finfo['_cuser'])){
		$params['_cuser']=(function_exists('isUser') && isUser())?$USER['_id']:0;
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
	$more='';
	if(isset($params['-ignore'])){
		$precs=postgresqlGetDBTableIndexes($params['-table']);
		if(is_array($precs)){
			$pflds=array();
			foreach($precs as $prec){
				//echo printValue($prec);exit;
				if($prec['is_unique']=='t'){
					//echo printValue($prec);
					$rflds=json_decode($prec['index_keys']);
					foreach($rflds as $rfld){
						if($rfld != '_id' && !in_array($rfld,$pflds)){
							$pflds[]=$rfld;
						}
					}
				}
			}
			$pfieldstr=implode(',',$pflds);
			$more="ON CONFLICT ({$pfieldstr}) DO NOTHING";
		}
	}
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			({$fieldstr})
		VALUES(
			{$preparestr}
		)
		{$more}
		{$output}

ENDOFQUERY;


	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect();
	}
	if(!$dbh_postgresql){
		debugValue(array(
			'function'=>'postgresqlAddDBRecord',
			'message'=>'connect failed',
			'error'=>pg_last_error(),
			'query'=>$query,
			'params'=>$params
		));
    	return null;
	}
	$result=pg_query_params($dbh_postgresql,$query,$values);
	if(!is_resource($result)){
		$err=pg_last_error($dbh_postgresql);
		debugValue(array(
			'function'=>'postgresqlAddDBRecord',
			'message'=>'pg_query_params failed',
			'error'=>$err,
			'query'=>$query,
			'values'=>$values,
			'params'=>$params
		));
		pg_close($dbh_postgresql);
		//exit;
		return null;
	}
	$recs = postgresqlEnumQueryResults($result,$params);
	// debugValue(array(
	// 	'function'=>'postgresqlAddDBRecord',
	// 	'query'=>$query,
	// 	'values'=>$values,
	// 	'params'=>$params
	// ));
	pg_close($dbh_postgresql);
	if(isset($recs[0][$output_field])){return $recs[0][$output_field];}
	return $recs;
}
//---------- begin function postgresqlGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=postgresqlGetDBRecordById('comments',7);
*/
function postgresqlGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "postgresqlGetDBRecordById Error: No Table";}
	if($id == 0){return "postgresqlGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=postgresqlGetDBRecord($recopts);
	return $rec;
}
//---------- begin function postgresqlEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=postgresqlEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function postgresqlEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("postgresqlEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("postgresqlEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("postgresqlEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("postgresqlEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return postgresqlEditDBRecord($params);
}
//---------- begin function postgresqlDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=postgresqlEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function postgresqlDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("postgresqlDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("postgresqlDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return postgresqlDelDBRecord($params);
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
	//check for schema name
	if(!stringContains($table,'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$table="{$schema}.{$table}";
		}
	}
	if(postgresqlIsDBTable($table)){return 0;}
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
	$schema=postgresqlGetDBSchema();
	if(strlen($schema) && !stringContains($table,$schema)){
		$table="{$schema}.{$table}";
	}
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

    global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect();
	}
	if(!$dbh_postgresql){
		debugValue(array(
			'function'=>'postgresqlCreateDBTable',
			'message'=>'connect failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
    	return;
	}
	$query_result=pg_query($dbh_postgresql,$query);
	//echo $query . printValue($query_result).PHP_EOL.PHP_EOL;
	//clear the cache
	clearDBCache(array('postgresqlGetDBTables','postgresqlGetDBFieldInfo','postgresqlIsDBTable'));
  	if(!isset($query_result['error']) && $query_result==true){
		//success creating table.  Now go through the fields and create any instant meta data found
		foreach($fields as $field=>$attributes){
        	instantDBMeta($ori_table,$field,$attributes);
		}
		return 1;
	}
  	else{
		return setWasqlError(debug_backtrace(),getDBError(),$query);
	}
	return 1;
}
//---------- begin function postgresqlDropDBIndex--------------------
/**
* @describe drop an index previously created
* @param params array
*	-table
*	-name
* @return boolean
* @usage $ok=addDBIndex(array('-table'=>$table,'-name'=>"myindex"));
*/
function postgresqlDropDBIndex($params=array()){
	if(!isset($params['-table'])){return 'postgresqlDropDBIndex Error: No table';}
	if(!isset($params['-name'])){return 'postgresqlDropDBIndex Error: No name';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$params['-table']=strtolower($params['-table']);
	global $databaseCache;
	if(isset($databaseCache['postgresqlGetDBTableIndexes'][$params['-table']])){
		unset($databaseCache['postgresqlGetDBTableIndexes'][$params['-table']]);
	}
	//build and execute
	$query="alter table {$params['-table']} drop index {$params['-name']}";
	return postgresqlExecuteSQL($query);
}
//---------- begin function postgresqlDropDBTable--------------------
/**
* @describe drops the specified table
* @param table string - name of table to drop
* @param [meta] boolean - also remove metadata in _fielddata and _tabledata tables associated with this table. defaults to true
* @return 1
* @usage $ok=dropDBTable('comments',1);
*/
function postgresqlDropDBTable($table='',$meta=1){
	if(!strlen($table)){return 0;}
	//check for schema name
	if(!stringContains($table,'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$table="{$schema}.{$table}";
		}
	}
	//drop indexes first
	$recs=postgresqlGetDBTableIndexes($table);
	if(is_array($recs)){
		foreach($recs as $rec){
	    	$key=$rec['key_name'];
	    	$ok=postgresqlDropDBIndex($table,$key);
		}
	}
	$result=postgresqlExecuteSQL("drop table {$table}");
	$ok=postgresqlDelDBRecord(array('-table'=>'_tabledata','-where'=>"tablename = '{$table}'"));
	$ok=postgresqlDelDBRecord(array('-table'=>"_fielddata",'-where'=>"tablename = '{$table}'"));
    return 1;
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
function postgresqlDBConnect(){
	$params=postgresqlParseConnectParams();
	//echo "postgresqlDBConnect".printValue($params);exit;
	if(!isset($params['-connect'])){
		echo "postgresqlDBConnect error: no connect params".printValue($params);
		exit;
	}
	//echo printValue($params);exit;
	global $dbh_postgresql;
	if(is_resource($dbh_postgresql)){return $dbh_postgresql;}
	try{
		$dbh_postgresql = pg_pconnect($params['-connect']);
		if(!is_resource($dbh_postgresql)){
			$err=@pg_last_error();
			echo "postgresqlDBConnect error:{$err}".printValue($params);
			exit;

		}
		//$umeta=pg_meta_data($dbh_postgresql,'_users');
		//echo "Connected".printValue($umeta);exit;
		return $dbh_postgresql;
	}
	catch (Exception $e) {
		echo "postgresqlDBConnect exception" . printValue($e);
		exit;
	}
}
//---------- begin function postgresqlDelDBRecord ----------
/**
* @describe deletes records in table that match -where clause
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
*	[-model] boolean - set to false to disable model functionality
* @return boolean
* @usage $id=postgresqlDelDBRecord(array('-table'=> '_tabledata','-where'=> "_id=4"));
*/
function postgresqlDelDBRecord($params=array()){
	global $USER;
	if(!isset($params['-table'])){return 'postgresqlDelDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'postgresqlDelDBRecord Error: No where';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$query="delete from {$params['-table']} where " . $params['-where'];
	return postgresqlExecuteSQL($query,$params);
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
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	global $USER;
	$finfo=postgresqlGetDBFieldInfo($params['-table']);
	$opts=array();
	if(isset($finfo['edate'])){
		$params['edate']=strtoupper(date('Y-M-d H:i:s'));
	}
	elseif(isset($finfo['_edate'])){
		$params['_edate']=strtoupper(date('Y-M-d H:i:s'));
	}
	if(isset($finfo['euser'])){
		$params['euser']=(function_exists('isUser') && isUser())?$USER['_id']:0;
	}
	elseif(isset($finfo['_euser'])){
		$params['_euser']=(function_exists('isUser') && isUser())?$USER['_id']:0;
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
		$dbh_postgresql=postgresqlDBConnect();
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
	if(!pg_prepare($dbh_postgresql,'',$query)){
		debugValue(array(
			'function'=>'postgresqlEditDBRecord',
			'message'=>'pg_prepare failed',
			'error'=>pg_last_error(),
			'query'=>$query
		));
		pg_close($dbh_postgresql);
		return;
	}
	$data=pg_execute($dbh_postgresql,'',$values);
	$err=pg_result_error($data);
	if(strlen($err)){
		debugValue(array(
			'function'=>'postgresqlEditDBRecord',
			'message'=>'pg_execute failed',
			'error'=>$err,
			'query'=>$query,
			'values'=>$values,
		));
		pg_close($dbh_postgresql);
		return null;
	}
	$recs = postgresqlEnumQueryResults($data,$params);
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
function postgresqlExecuteSQL($query,$return_error=1){
	global $dbh_postgresql;
	$dbh_postgresql=postgresqlDBConnect();
	if(!$dbh_postgresql){
		$err=array(
			'function'=>'postgresqlExecuteSQL',
			'message'=>'connect failed',
			'error'=>pg_last_error(),
			'query'=>$query
		);
		debugValue($err);
		if($return_error==1){return $err;}
    	return 0;
	}
	try{
		$result=@pg_query($dbh_postgresql,$query);
		if(!$result){
			$err=array(
				'function'=>'postgresqlExecuteSQL',
				'message'=>'pg_query failed',
				'error'=>pg_last_error(),
				'query'=>$query
			);
			debugValue($err);
			pg_close($dbh_postgresql);
			if(stringContains($err['error'],'server closed the connection unexpectedly')){
				//try one more time
				usleep(100);
				$dbh_postgresql='';
				$dbh_postgresql=postgresqlDBConnect();
				$result=@pg_query($dbh_postgresql,$query);
				if(!$result){
					$err=array(
						'function'=>'postgresqlExecuteSQL',
						'message'=>'pg_query failed twice',
						'error'=>pg_last_error(),
						'query'=>$query
					);
					debugValue($err);
					pg_close($dbh_postgresql);
					if($return_error==1){return $err;}
					return 0;
				}
			}
			if($return_error==1){return $err;}
			return 0;
		}
		pg_close($dbh_postgresql);
		return 0;
	}
	catch (Exception $e) {
		$err=array(
			'function'=>'postgresqlExecuteSQL',
			'message'=>'try catch failed',
			'error'=>$e->errorInfo,
			'query'=>$query
		);
		debugValue($err);
		if($return_error==1){return $err;}
		return 0;
	}
	return 0;
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
	if(!isset($params['-table'])){return null;}
	if(!stringContains($params['-table'],'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	//echo printValue($params);exit;
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	//$params['-debug']=1;
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
//---------- begin function postgresqlGetDBFields ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=postgresqlGetDBFieldInfo('test');
*/
function postgresqlGetDBFields($table,$allfields=0){
	$table=strtolower($table);
	global $databaseCache;
	$key=$table.$allfields;
	if(isset($databaseCache['postgresqlGetDBFields'][$key])){
		return $databaseCache['postgresqlGetDBFields'][$key];
	}
	global $CONFIG;
	global $USER;
	//check for schema name
	if(!stringContains($table,'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$table="{$schema}.{$table}";
		}
	}
	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect();
	}
	if(!$dbh_postgresql){
		debugValue(array(
			'function'=>'postgresqlGetDBFields',
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
	//echo $query;exit;
	$res=@pg_query($dbh_postgresql,$query);
	$fieldnames=array();
	$i = pg_num_fields($res);
	//echo $i.printValue($res);exit;
	for ($j = 0; $j < $i; $j++) {
		$name=strtolower(pg_field_name($res, $j));
		if(!$allfields && preg_match('/^\_/',$name)){continue;}
	    if(!in_array($name,$fieldnames)){$fieldnames[]=$name;}
	}
	pg_close($dbh_postgresql);
	ksort($fieldnames);
	$databaseCache['postgresqlGetDBFields'][$key]=$fieldnames;
	return $databaseCache['postgresqlGetDBFields'][$key];
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
function postgresqlGetDBFieldInfo($table,$getmeta=0,$field='',$force=0){
	$table=strtolower($table);
	$cachekey=$table.$getmeta.$field;
	global $databaseCache;
	if(isset($databaseCache['postgresqlGetDBFieldInfo'][$cachekey])){
		return $databaseCache['postgresqlGetDBFieldInfo'][$cachekey];
	}
	//check for schema name
	if(!stringContains($table,'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$table="{$schema}.{$table}";
		}
	}
	global $CONFIG;
	global $USER;
	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect();
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
	
	
	//echo printValue($idfields);exit;
	$query="SELECT * from {$table} where 1=0";
	//echo $query;exit;
	$res=@pg_query($dbh_postgresql,$query);
	$fields=array();
	$i = pg_num_fields($res);
	for ($j = 0; $j < $i; $j++) {
	    $fieldname = pg_field_name($res, $j);
		$field=array(
			'_dbtable'	=> $table,
			'table'		=> $table,
			'name'		=> $fieldname,
		 	'_dbfield'	=> strtolower($fieldname),
		 	'_dbtype'	=> pg_field_type($res, $j),
			'length'	=> pg_field_prtlen($res, $j),
			'num'		=> pg_field_num($res, $j),
			'size'		=> pg_field_size($res, $j),
			'nullable'	=> pg_field_is_null($res, $j),
		);
		$field['_dblength']=$field['length'];
		$field['_dbtype']=$field['_dbtype_ex']=$field['type']=strtolower($field['_dbtype']);
		if($field['size'] > 0){
			$field['_dbtype_ex']=strtolower("{$field['_dbtype']}({$field['size']})");
		}
		$fields[$fieldname]=$field;
	}
	pg_close($dbh_postgresql);
	//check for identity fields
	$recs=postgresqlQueryResults("select column_name from information_schema.columns where table_name='{$table}' and is_identity='YES'");
	$idfields=array();
	foreach($recs as $rec){
		$fields[$rec['column_name']]['identity']=1;
	}
	ksort($fields);
	//meta fields?
	if($getmeta){
	    //Get a list of the metadata for this table
	    $query="select * from _fielddata where tablename='{$table}'";
	    if(strlen($field)){$query .= " and fieldname='{$field}";}
	    //echo printValue($metaopts);exit;
	    $meta_recs=postgresqlQueryResults($query);
	    //echo $query.printValue($meta_recs);exit;
	    if(is_array($meta_recs)){
			foreach($meta_recs as $meta_rec){
				$name=$meta_rec['fieldname'];
				if(!isset($fields[$name]['_dbtype'])){continue;}
				foreach($meta_rec as $key=>$val){
					if(preg_match('/^\_/',$key)){continue;}
					$fields[$name][$key]=$val;
				}
        	}
    	}
	}
	
	$databaseCache['postgresqlGetDBFieldInfo'][$cachekey]=$fields;
	return $databaseCache['postgresqlGetDBFieldInfo'][$cachekey];
}
function postgresqlGetDBIndexes($tablename=''){
	return postgresqlGetDBTableIndexes($tablename);
}
function postgresqlGetDBTableIndexes($tablename=''){
	global $databaseCache;
	if(isset($databaseCache['postgresqlGetDBTableIndexes'][$tablename])){
		return $databaseCache['postgresqlGetDBTableIndexes'][$tablename];
	}
	$query=<<<ENDOFQUERY
	SELECT
  		U.usename AS user_name,
	  	ns.nspname               AS schema_name,
	  	idx.indrelid :: REGCLASS AS table_name,
	  	i.relname                AS index_name,
	  	idx.indisunique          AS is_unique,
	  	idx.indisprimary         AS is_primary,
	  	am.amname                AS index_type,
	  	idx.indkey,
       	to_json(array(
           SELECT pg_get_indexdef(idx.indexrelid, k + 1, TRUE)
           FROM
             generate_subscripts(idx.indkey, 1) AS k
           ORDER BY k
       	)) AS index_keys,
  		(idx.indexprs IS NOT NULL) OR (idx.indkey::int[] @> array[0]) AS is_functional,
  		idx.indpred IS NOT NULL AS is_partial
	FROM pg_index AS idx
  		JOIN pg_class AS i ON i.oid = idx.indexrelid
  		JOIN pg_am AS am ON i.relam = am.oid
  		JOIN pg_namespace AS NS ON i.relnamespace = NS.OID
  		JOIN pg_user AS U ON i.relowner = U.usesysid
	WHERE NOT nspname LIKE 'pg%'
ENDOFQUERY;
	if(strlen($tablename)){
		$query .= " and idx.indrelid ='{$tablename}' :: REGCLASS ";
	}
	$databaseCache['postgresqlGetDBTableIndexes'][$tablename]=postgresqlQueryResults($query);
	return $databaseCache['postgresqlGetDBTableIndexes'][$tablename];
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
		$params=trim($params);
		if(preg_match('/^(select|exec|with|explain|returning|show|call)[\t\s\ \r\n]/i',$params)){
			//they just entered a query
			$query=$params;
			$params=array();
		}
		else{
			echo $params.PHP_EOL."REQUEST: ".PHP_EOL.printValue($_REQUEST);exit;
			$ok=postgresqlExecuteSQL($params);
			return $ok;
		}
	}
	elseif(isset($params['-query'])){
		return postgresqlQueryResults($params['-query'],$params);
	}
	else{
		if(empty($params['-table'])){
			debugValue(array(
				'function'=>'postgresqlGetDBRecords',
				'message'=>'no table',
				'params'=>$params
			));
	    	return null;
		}
		//check for schema name
		if(!stringContains($params['-table'],'.')){
			$schema=postgresqlGetDBSchema();
			if(strlen($schema)){
				$params['-table']="{$schema}.{$params['-table']}";
			}
		}
		//determine fields to return
		if(!empty($params['-fields'])){
			if(!is_array($params['-fields'])){
				$params['-fields']=preg_split('/\,/',$params['-fields']);
			}
			$params['-fields']=implode(',',$params['-fields']);
		}
		if(empty($params['-fields'])){$params['-fields']='*';}
		$fields=postgresqlGetDBFieldInfo($params['-table'],$params);
		//echo printValue($fields);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!strlen(trim($v))){continue;}
			if(!isset($fields[$k])){continue;}
			if(is_array($params[$k])){
	            $params[$k]=implode(':',$params[$k]);
			}
	        $params[$k]=str_replace("'","''",$params[$k]);
	        switch(strtolower($fields[$k])){
	        	case 'char':
	        	case 'varchar':
	        		$v=strtoupper($params[$k]);
	        		$ands[]="upper({$k})='{$v}'";
	        	break;
	        	case 'int':
	        	case 'int4':
	        	case 'numeric':
	        		$ands[]="{$k}={$v}";
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
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postgresqlGetDBVersion(){
	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect();
	}
	return pg_version($dbh_postgresql);	
}
//---------- begin function postgresqlGrepDBTables ----------
/**
* grepDBTables - searches across tables for a specified value
* @param search string
* @param tables array - optional. defaults to all tables except for _changelog,_cronlog, and _errors
* @return  array of arrays - tablename,_id,fieldname,search_count
* @usage $results=postgresqlGrepDBTables('searchstring');
*/
function postgresqlGrepDBTables($search,$tables=array(),$dbname=''){
	if(!is_array($tables)){
		if(strlen($tables)){$tables=array($tables);}
		else{$tables=array();}
	}
	if(!count($tables)){
		$tables=postgresqlGetDBTables($dbname);
		//ignore _changelog
		foreach($tables as $i=>$table){
			if(in_array($table,array('_changelog','_cronlog','_errors'))){unset($tables[$i]);}
		}
	}
	//return $tables;
	$search=trim($search);
	if(!strlen($search)){return "grepDBTables Error: no search value";}
	$results=array();
	$search=str_replace("'","''",$search);
	$search=strtolower($search);
	foreach($tables as $table){
		if(strlen($dbname)){$table=$dbname.'.'.$table;}
		if(!postgresqlIsDBTable($table)){return "grepDBTables Error: {$table} is not a table";}
		$info=postgresqlGetDBFieldInfo($table);
		$wheres=array();
		$fields=array();
		foreach($info as $field=>$finfo){
			switch($info[$field]['_dbtype']){
				case 'int':
				case 'integer':
				case 'number':
				case 'float':
					if(isNum($search)){
						$wheres[]="{$field}={$search}";
						$fields[]=$field;
					}
				break;
				case 'varchar':
				case 'char':
				case 'string':
				case 'blob':
				case 'text':
				case 'mediumtext':
					$wheres[]="{$field} like '%{$search}%'";
					$fields[]=$field;
				break;
			}
		}
		if(!count($wheres)){continue;}
		if(!in_array('_id',$fields)){array_unshift($fields,'_id');}
		$where=implode(' or ',$wheres);
		$fields=implode(',',$fields);
		$recopts=array('-table'=>$table,'-where'=>$where,'-fields'=>$fields);
		$recs=postgresqlGetDBRecords($recopts);
		if(is_array($recs)){
			$cnt=count($recs);
			foreach($recs as $rec){
				$vals=array();
				foreach($rec as $key=>$val){
					if(stringContains($val,$search)){
						$results[]=array(
							'tablename'=>$table,
							'_id'		=> $rec['_id'],
							'fieldname' => $key,
							'search_count'=> substr_count(strtolower($val),$search)
						);
					}
				}
			}
		}
	}
	return $results;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postgresqlTranslateDataType($str){
	$parts=preg_split('/[,()]/',$str);
	$name=strtolower($parts[0]);
	//echo "postgresqlTranslateDataType({$str}):{$name}".printValue($parts).'<hr>'.PHP_EOL;
	switch(strtolower($name)){
		case 'tinyint':return 'int2';break;
		case 'smallint':return 'int4';break;
    	case 'bigint':return 'bigint';break;
    	case 'real':
    		if(count($parts)==3){return "numeric({$parts[1]},{$parts[2]})";}
    		elseif(count($parts)==2){return "numeric({$parts[1]})";}
    		else{return 'numeric';}
    	break;
    	case 'integer':
    	case 'int':
    		return 'integer';
    	break;
    	case 'json':return 'json';break;
    	case 'date':
    	case 'seconddate':
    		return 'date';
    	break;
    	case 'time':return 'time';break;
    	case 'datetime':
    	case 'timestamp':
    		return 'timestamp';
    	break;
    	case 'numeric':
    	case 'decimal':
    	case 'number':
    		if(count($parts)==3){return "decimal({$parts[1]},{$parts[2]})";}
    		elseif(count($parts)==2){return "decimal({$parts[1]})";}
    		else{return 'decimal';}
    	break;
    	case 'money':return 'money';break;
    	case 'tinytext':
    	case 'mediumtext':
    	case 'longtext':
			return 'text';
		break;
		case 'varchar':
		case 'nvarchar':
		case 'varchar2':
			if(count($parts)==2){return "varchar({$parts[1]})";}
    		else{return 'varchar(255)';}
    	break;
    	case 'char':
    	case 'nchar':
			if(count($parts)==2){
				//use char ONLY if the len is 1
				if($parts[1]==1){return "char({$parts[1]})";}
				else{return "varchar({$parts[1]})";}
			}
    		else{return 'varchar(255)';}
    	break;
	}
	return $str;
}
//---------- begin function postgresqlIsDBTable ----------
/**
* @describe returns true if table already exists
* @param table string
* @return boolean
* @usage if(postgresqlIsDBTable('_users')){...}
*/
function postgresqlIsDBTable($table='',$force=0){
	global $databaseCache;
	$table=strtolower($table);
	if($force==0 && isset($databaseCache['postgresqlIsDBTable'][$table])){
		return $databaseCache['postgresqlIsDBTable'][$table];
	}
	$tables=postgresqlGetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function postgresqlGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
* @return array returns array of tables
* @usage $tables=postgresqlGetDBTables();
*/
function postgresqlGetDBTables($params=array()){
	global $databaseCache;
	if(isset($databaseCache['postgresqlGetDBTables'][0])){
		return $databaseCache['postgresqlGetDBTables'];
	}
	$databaseCache['postgresqlGetDBTables']=array();
	global $CONFIG;
	$include_schema=1;
	$schema=postgresqlGetDBSchema();
	if(strlen($schema)){
		$query="SELECT schemaname,tablename FROM pg_catalog.pg_tables where schemaname='{$schema}' order by tablename";
	}
	else{
		$query="SELECT schemaname,tablename FROM pg_catalog.pg_tables order by tablename";
	}
	$recs = postgresqlQueryResults($query);
	//echo $query;exit;
	foreach($recs as $rec){
		$databaseCache['postgresqlGetDBTables'][]=strtolower($rec['tablename']);
	}
	return $databaseCache['postgresqlGetDBTables'];
}
//---------- begin function postgresqlGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param table string - specified table
* @return array returns array of primary key fields
* @usage $fields=postgresqlGetDBTablePrimaryKeys($table);
*/
function postgresqlGetDBTablePrimaryKeys($table){
	$table=strtolower($table);
	global $databaseCache;
	if(isset($databaseCache['postgresqlGetDBTablePrimaryKeys'][$table])){
		return $databaseCache['postgresqlGetDBTablePrimaryKeys'][$table];
	}
	//check for schema name
	if(!stringContains($table,'.')){
		$schema=postgresqlGetDBSchema();
		if(strlen($schema)){
			$table="{$schema}.{$table}";
		}
	}

	$databaseCache['postgresqlGetDBTablePrimaryKeys'][$table]=array();
	$parts=preg_split('/\./',$table,2);
	$where='';
	if(count($parts)==2){
		$where = " and kc.table_schema='{$parts[0]}' and kc.table_name='{$parts[1]}'";
	}
	else{
		$where = " and kc.table_name='{$parts[0]}'";
	}
	global $CONFIG;
	$dbname=postgresqlGetConfigValue('dbname');
	$query=<<<ENDOFQUERY
		SELECT 	
			kc.table_schema,
			kc.table_name,
			kc.column_name,
			kc.constraint_name,
			kc.ordinal_position
		FROM  
		    information_schema.table_constraints tc,  
		    information_schema.key_column_usage kc  
		where 
		    tc.constraint_type = 'PRIMARY KEY' 
		    and kc.table_name = tc.table_name 
		    and kc.table_schema = tc.table_schema
		    and tc.table_catalog = '{$dbname}'
		    and kc.constraint_name = tc.constraint_name
		    {$where}
		order by kc.ordinal_position
ENDOFQUERY;
	$tmp = postgresqlQueryResults($query);
	foreach($tmp as $rec){
		$databaseCache['postgresqlGetDBTablePrimaryKeys'][$table][]=$rec['column_name'];
    }
	return $databaseCache['postgresqlGetDBTablePrimaryKeys'][$table];
}
function postgresqlGetDBSchema(){
	global $CONFIG;
	$params=postgresqlParseConnectParams();
	if(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['postgresql_dbschema'])){return $CONFIG['postgresql_dbschema'];}
	elseif(isset($CONFIG['postgresql_schema'])){return $CONFIG['postgresql_schema'];}
	return '';
}

function postgresqlGetConfigValue($field){
	//dbschema, dbname
	global $CONFIG;
	switch(strtolower($CONFIG['dbtype'])){
		case 'postgres':
		case 'postgresql':
			if(isset($CONFIG[$field])){return $CONFIG[$field];}
			elseif(isset($CONFIG["postgresql_{$field}"])){return $CONFIG["postgresql_{$field}"];}
		break;
		default:
			if(isset($CONFIG["postgresql_{$field}"])){return $CONFIG["postgresql_{$field}"];}
		break;
	}
	return null;
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
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^postgres/i',$k)){unset($CONFIG[$k]);}
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
	//echo "HERE".printValue($params);exit;
	if(isPostgreSQL()){
		$params['-dbhost']=$CONFIG['dbhost'];
		if(isset($CONFIG['dbname'])){
			$params['-dbname']=$CONFIG['dbname'];
		}
		if(isset($CONFIG['dbuser'])){
			$params['-dbuser']=$CONFIG['dbuser'];
		}
		if(isset($CONFIG['dbpass'])){
			$params['-dbpass']=$CONFIG['dbpass'];
		}
		if(isset($CONFIG['dbport'])){
			$params['-dbport']=$CONFIG['dbport'];
		}
		if(isset($CONFIG['dbconnect'])){
			$params['-connect']=$CONFIG['dbconnect'];
		}
	}
	//dbhost
	if(!isset($params['-dbhost'])){
		if(isset($CONFIG['dbhost_postgresql'])){
			$params['-dbhost']=$CONFIG['dbhost_postgresql'];
			//$params['-dbhost_source']="CONFIG dbhost_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbhost'])){
			$params['-dbhost']=$CONFIG['postgresql_dbhost'];
			//$params['-dbhost_source']="CONFIG postgresql_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		//$params['-dbhost_source']="passed in";
	}
	$CONFIG['postgresql_dbhost']=$params['-dbhost'];
	
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_postgresql'])){
			$params['-dbuser']=$CONFIG['dbuser_postgresql'];
			//$params['-dbuser_source']="CONFIG dbuser_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbuser'])){
			$params['-dbuser']=$CONFIG['postgresql_dbuser'];
			//$params['-dbuser_source']="CONFIG postgresql_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['postgresql_dbuser']=$params['-dbuser'];
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_postgresql'])){
			$params['-dbpass']=$CONFIG['dbpass_postgresql'];
			//$params['-dbpass_source']="CONFIG dbpass_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbpass'])){
			$params['-dbpass']=$CONFIG['postgresql_dbpass'];
			//$params['-dbpass_source']="CONFIG postgresql_dbpass";
		}
	}
	else{
		//$params['-dbpass_source']="passed in";
	}
	$CONFIG['postgresql_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_postgresql'])){
			$params['-dbname']=$CONFIG['dbname_postgresql'];
			//$params['-dbname_source']="CONFIG dbname_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbname'])){
			$params['-dbname']=$CONFIG['postgresql_dbname'];
			//$params['-dbname_source']="CONFIG postgresql_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['postgresql_dbname'];
			//$params['-dbname_source']="set to username";
		}
	}
	else{
		//$params['-dbname_source']="passed in";
	}
	$CONFIG['postgresql_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_postgresql'])){
			$params['-dbport']=$CONFIG['dbport_postgresql'];
			//$params['-dbport_source']="CONFIG dbport_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbport'])){
			$params['-dbport']=$CONFIG['postgresql_dbport'];
			//$params['-dbport_source']="CONFIG postgresql_dbport";
		}
		else{
			$params['-dbport']=5432;
			//$params['-dbport_source']="default port";
		}
	}
	else{
		//$params['-dbport_source']="passed in";
	}
	$CONFIG['postgresql_dbport']=$params['-dbport'];
	//dbschema
	if(!isset($params['-dbschema'])){
		if(isset($CONFIG['dbschema_postgresql'])){
			$params['-dbschema']=$CONFIG['dbschema_postgresql'];
			//$params['-dbuser_source']="CONFIG dbuser_postgresql";
		}
		elseif(isset($CONFIG['postgresql_dbschema'])){
			$params['-dbschema']=$CONFIG['postgresql_dbschema'];
			//$params['-dbuser_source']="CONFIG postgresql_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['postgresql_dbschema']=$params['-dbschema'];
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['postgresql_connect'])){
			$params['-connect']=$CONFIG['postgresql_connect'];
			//$params['-connect_source']="CONFIG postgresql_connect";
		}
		elseif(isset($CONFIG['connect_postgresql'])){
			$params['-connect']=$CONFIG['connect_postgresql'];
			//$params['-connect_source']="CONFIG connect_postgresql";
		}
		else{
			//build connect - http://php.net/manual/en/function.pg-connect.php
			//$conn_string = "host=sheep port=5432 dbname=test user=lamb password=bar";
			//echo printValue($CONFIG);exit;
			$params['-connect']="host={$CONFIG['postgresql_dbhost']} port={$CONFIG['postgresql_dbport']} dbname={$CONFIG['postgresql_dbname']} user={$CONFIG['postgresql_dbuser']} password={$CONFIG['postgresql_dbpass']}";
			//$params['-connect_source']="manual";
		}
		//add application_name
		if(!stringContains($params['-connect'],'options')){
			if(isset($params['-application_name'])){
				$appname=$params['-application_name'];
			}
			elseif(isset($CONFIG['postgres_application_name'])){
				$appname=$CONFIG['postgres_application_name'];
			}
			else{
				$appname='WaSQL_on_'.$_SERVER['HTTP_HOST'];
			}
			$appname=str_replace(' ','_',$appname);
			$params['-connect'].=" options='--application_name={$appname}'";
		}
		//add connect_timeout
		if(!stringContains($params['-connect'],'connect_timeout')){
			$params['-connect'].=" connect_timeout=5";
		}
	}
	else{
		//$params['-connect_source']="passed in";
	}
	//echo printValue($params);exit;
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
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return array - returns records
*/
function postgresqlQueryResults($query='',$params=array()){
	$query=trim($query);
	global $USER;
	global $dbh_postgresql;
	if(!is_resource($dbh_postgresql)){
		$dbh_postgresql=postgresqlDBConnect();
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
	$err=pg_result_error($data);
	if(strlen($err)){
		debugValue(array(
			'function'=>'postgresqlQueryResults',
			'message'=>'pq_query failed',
			'error'=>$err,
			'query'=>$query,
			'values'=>$values,
		));
		pg_close($dbh_postgresql);
		return null;
	}
	if(!$data){
		//lets try one more time
		usleep(100);
		$dbh_postgresql='';
		$dbh_postgresql=postgresqlDBConnect();
		$data=@pg_query($dbh_postgresql,$query);
		$err=pg_result_error($data);
		if(strlen($err)){
			debugValue(array(
				'function'=>'postgresqlQueryResults',
				'message'=>'pq_query failed twice',
				'error'=>$err,
				'query'=>$query,
				'values'=>$values,
			));
			pg_close($dbh_postgresql);
			return null;
		}
	}
	if(!$data){return "postgresqlQueryResults Query Error: " . pg_last_error();}
	if(preg_match('/^insert /i',$query) && !stringContains($query,' returning ')){
    	//return the id inserted on insert statements

    	$id=databaseAffectedRows($data);
    	pg_close($dbh_postgresql);
    	return $id;
	}

	$results = postgresqlEnumQueryResults($data,$params);
	pg_close($dbh_postgresql);
	if(!is_array($results) && stringContains($results,'server closed the connection unexpectedly')){
		$results .= " **NOTICE** make sure pgsql.auto_reset_persistent in php.ini is set to On. This usually resolved this issue.";
	}
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
function postgresqlEnumQueryResults($data,$params=array()){
	if(!is_resource($data)){return null;}
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
			pg_free_result($result);
			return 'postgresqlEnumQueryResults error: Failed to open '.$params['-filename'];
			exit;
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	$i=0;
	while ($row = @pg_fetch_assoc($data)){
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
//---------- begin function postgresqlListDBDatatypes ----
/**
* @describe returns the data types for postgres
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function postgresqlListDBDatatypes(){
	//default to mysql
	return <<<ENDOFDATATYPES
<div class="w_bold w_blue w_padtop">Text Types</div>
<div class="w_padleft">CHAR( ) A fixed length string with a maximum size of 1 GB.</div>
<div class="w_padleft">VARCHAR( ) A variable length string with a maximum size of 1 GB.</div>
<div class="w_padleft">TEXT A string with a maximum size of 1 GB.</div>
<div class="w_padleft">JSON textual JSON data with a maximum size of 1 GB</div>
<div class="w_padleft">XML textual XML data with a maximum size of 1 GB</div>

<div class="w_bold w_blue w_padtop">Number Types</div>
<div class="w_padleft">INT2 - signed two-byte integer</div>
<div class="w_padleft">INT4 or INT - signed four-byte integer</div>
<div class="w_padleft">INT8 or BIGINT - signed eight-byte integer</div>
<div class="w_padleft">FLOAT8 - double precision floating-point number (8 bytes)</div>
<div class="w_padleft">Float4 or REAL - single precision floating-point number (4 bytes)</div>
<div class="w_padleft">NUMERIC( , ) OR DECIMAL( , ) - exact numeric of selectable precision</div>

<div class="w_bold w_blue w_padtop">Date Types</div>
<div class="w_padleft">DATE YYYY-MM-DD.</div>
<div class="w_padleft">TIMESTAMP YYYYMMDDHHMMSS.</div>
<div class="w_padleft">TIME HH:MM:SS.</div>
ENDOFDATATYPES;
}
//---------- begin function postgresqlNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function postgresqlNamedQuery($name){
	switch(strtolower($name)){
		case 'running_queries':
			return <<<ENDOFQUERY
SELECT
  S.pid,
  age(clock_timestamp(), query_start),
  usename,
  query,
  L.mode,
  L.locktype,
  L.granted
FROM pg_stat_activity S
inner join pg_locks L on S.pid = L.pid 
order by L.granted, L.pid DESC
ENDOFQUERY;
		break;
		case 'sessions':
			return <<<ENDOFQUERY
select pid as process_id, 
       usename as username, 
       datname as database_name, 
       client_addr as client_address, 
       application_name,
       backend_start,
       state,
       state_change
from pg_stat_activity
order by state,application_name,database_name,username
ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY
select pid, 
       usename, 
       pg_blocking_pids(pid) as blocked_by, 
       query as blocked_query
from pg_stat_activity
where cardinality(pg_blocking_pids(pid)) > 0
ENDOFQUERY;
		break;
		case 'functions':
			return <<<ENDOFQUERY
SELECT 
	routines.specific_schema,
	routines.routine_catalog,
	routines.routine_name, 
	parameters.data_type, 
	parameters.ordinal_position,
	routines.routine_definition
FROM information_schema.routines
    LEFT JOIN information_schema.parameters ON routines.specific_name=parameters.specific_name
WHERE routines.routine_type='FUNCTION' and routines.specific_schema not in ('information_schema','pg_catalog')
ORDER BY routines.routine_name, parameters.ordinal_position;
ENDOFQUERY;
		break;
		case 'procedures':
			return <<<ENDOFQUERY
SELECT 
	routines.specific_schema,
	routines.routine_catalog,
	routines.routine_name, 
	parameters.data_type, 
	parameters.ordinal_position,
	routines.routine_definition
FROM information_schema.routines
    LEFT JOIN information_schema.parameters ON routines.specific_name=parameters.specific_name
WHERE routines.routine_type='PROCEDURE' and routines.specific_schema not in ('information_schema','pg_catalog')
ORDER BY routines.routine_name, parameters.ordinal_position;
ENDOFQUERY;
		break;
	}
}
