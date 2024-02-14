<?php

/*
	sqlite3 Database functions
		http://php.net/manual/en/sqlite3.query.php
		*
*/

//---------- begin function sqliteAddDBIndex--------------------
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
*	$ok=sqliteAddDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=sqliteAddDBIndex(array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function sqliteAddDBIndex($params=array()){
	if(!isset($params['-table'])){return 'sqliteAddDBIndex Error: No table';}
	if(!isset($params['-fields'])){return 'sqliteAddDBIndex Error: No fields';}
	if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,+/',$params['-fields']);}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=sqliteGetDBSchema();
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
	return sqliteExecuteSQL($query);
}

//---------- begin function sqliteAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
* @return count int
* @usage $ok=sqliteAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=sqliteAddDBRecords('comments',array('-recs'=>$recs);
*/
function sqliteAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("sqliteAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	$params['-table']=$table;
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("sqliteAddDBRecords Error: either -csv or -recs is required");
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("sqliteAddDBRecords Error: no such file: {$params['-csv']}");
		}
		return processCSVLines($params['-csv'],'sqliteAddDBRecordsProcess',$params);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("sqliteAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("sqliteAddDBRecords Error: no recs");
		}
		return sqliteAddDBRecordsProcess($params['-recs'],$params);
	}
}
function sqliteAddDBRecordsProcess($recs,$params=array()){
	if(!isset($params['-table'])){
		return debugValue("sqliteAddDBRecordsProcess Error: no table"); 
	}
	$table=$params['-table'];
	$fieldinfo=sqliteGetDBFieldInfo($table,1);
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
				$v=sqliteEscapeString($v);
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
	$ok=sqliteExecuteSQL($query);
	return count($values);
}
//---------- begin function sqliteAddDBFields--------------------
/**
* @describe adds fields to given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return array - name,type,query,result for each field set
* @usage
*	$ok=sqliteAddDBFields('comments',array('comment'=>"varchar(1000) NULL"));
*/
function sqliteAddDBFields($table,$fields=array(),$maintain_order=1){
	$recs=array();
	foreach($fields as $name=>$type){
		$crec=array('name'=>$name,'type'=>$type);
		$fieldstr="{$name} {$type}";
		$crec['query']="ALTER TABLE {$table} ADD ({$fieldstr})";
		$crec['result']=sqliteExecuteSQL($crec['query']);
		$recs[]=$crec;
	}
	return $recs;
}
//---------- begin function sqliteDropDBFields--------------------
/**
* @describe drops fields to given table
* @param table string - name of table to alter
* @param params array - list of fields
* @return array - name,query,result for each field
* @usage
*	$ok=sqliteDropDBFields('comments',array('comment','age'));
*/
function sqliteDropDBFields($table,$fields=array()){
	$recs=array();
	foreach($fields as $name){
		$crec=array('name'=>$name);
		$crec['query']="ALTER TABLE {$table} DROP ({$name})";
		$crec['result']=sqliteExecuteSQL($crec['query']);
		$recs[]=$crec;
	}
	return $recs;
}
//---------- begin function sqliteAlterDBTable--------------------
/**
* @describe alters fields in given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=sqliteAlterDBTable('comments',array('comment'=>"varchar(1000) NULL"));
*/
function sqliteAlterDBTable($table,$fields=array(),$maintain_order=1){
	$info=sqliteGetDBFieldInfo($table);
	if(!is_array($info) || !count($info)){
		debugValue("sqliteAlterDBTable - {$table} is missing or has no fields".printValue($table));
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
		$ok=sqliteExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	if(count($addfields)){
		$fieldstr=implode(', ',$addfields);
		$query="ALTER TABLE {$table} ADD ({$fieldstr})";
		$ok=sqliteExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	return $rtn;
}
function sqliteEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function sqliteGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=sqliteGetTableDDL('sample');
*/
function sqliteGetTableDDL($table,$schema=''){
	$table=strtolower($table);
	$query=<<<ENDOFQUERY
		SELECT sql
		FROM sqlite_master
		WHERE lower(name)='{$table}'
ENDOFQUERY;
	$recs=sqliteQueryResults($query);
	if(isset($recs[0]['sql'])){
		return $recs[0]['sql'];
	}
	return $recs;
}
//---------- begin function sqliteGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=sqliteGetAllTableFields();
*/
function sqliteGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'sqliteGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	$query=<<<ENDOFQUERY
		SELECT 
			m.tbl_name as table_name,
			pti.name as field_name,
			pti.type as field_type
		FROM sqlite_master AS m,
		    pragma_table_info(m.tbl_name) AS pti
		WHERE 
			m.type = 'table'
		GROUP BY
			m.tbl_name,
			pti.name,
			pti.type
		ORDER BY 1,2
ENDOFQUERY;
	$recs=sqliteQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$field=strtolower($rec['field_name']);
		$type=strtolower($rec['type_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function sqliteGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=sqliteGetAllTableIndexes();
*/
function sqliteGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'sqliteGetAllTableIndexes');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	//key_name,column_name,seq_in_index,non_unique
	$query=<<<ENDOFQUERY
	SELECT 
		m.tbl_name as table_name,
		il.name as index_name,
		group_concat(ii.name) as index_keys,
		il.[unique] as is_unique
  	FROM sqlite_master AS m,
	    pragma_index_list(m.name) AS il,
	    pragma_index_info(il.name) AS ii
 	WHERE 
 		m.type = 'table'
 	GROUP BY
 		m.tbl_name,
		il.name,
		il.[unique]
 	ORDER BY 1,2
ENDOFQUERY;
	$recs=sqliteQueryResults($query);
	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>'.printValue($recs);exit;
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
function sqliteGetDBSchema(){
	global $CONFIG;
	global $DATABASE;
	$params=sqliteParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	elseif(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['sqlite_dbschema'])){return $CONFIG['sqlite_dbschema'];}
	elseif(isset($CONFIG['sqlite_schema'])){return $CONFIG['sqlite_schema'];}
	return '';
}
//---------- begin function sqliteGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=sqliteGetDBRecordById('comments',7);
*/
function sqliteGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "sqliteGetDBRecordById Error: No Table";}
	if($id == 0){return "sqliteGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=sqliteGetDBRecord($recopts);
	return $rec;
}
//---------- begin function sqliteEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=sqliteEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function sqliteEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("sqliteEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("sqliteEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("sqliteEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("sqliteEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return sqliteEditDBRecord($params);
}
//---------- begin function sqliteDelDBRecord ----------
/**
* @describe deletes records in table that match -where clause
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
* @return boolean
* @usage $id=sqliteDelDBRecord(array('-table'=> '_tabledata','-where'=> "_id=4"));
*/
function sqliteDelDBRecord($params=array()){
	global $USER;
	if(!isset($params['-table'])){return 'sqliteDelDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'sqliteDelDBRecord Error: No where';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=sqliteGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$query="delete from {$params['-table']} where " . $params['-where'];
	return sqliteExecuteSQL($query);
}
//---------- begin function sqliteDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=sqliteDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function sqliteDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("sqliteDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("sqliteDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return sqliteDelDBRecord($params);
}
//---------- begin function sqliteCreateDBTable--------------------
/**
* @describe creates table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=sqliteCreateDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function sqliteCreateDBTable($table='',$fields=array()){
	if(strlen($table)==0){return "sqliteCreateDBTable error: No table";}
	if(count($fields)==0){return "sqliteCreateDBTable error: No fields";}
	if(sqliteIsDBTable($table)){return 0;}
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
	$query_result=sqliteExecuteSQL($query);
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

function sqliteGetDBIndexes($tablename=''){
	return sqliteGetDBTableIndexes($tablename);
}
function sqliteGetDBTableIndexes($tablename=''){
	global $databaseCache;
	if(isset($databaseCache['sqliteGetDBTableIndexes'][$tablename])){
		return $databaseCache['sqliteGetDBTableIndexes'][$tablename];
	}
	$filter='';
	if(strlen($tablename)){
		$filter="and m.tbl_name = '{$tablename}'";
	}
	$query=<<<ENDOFQUERY
	SELECT 
		m.tbl_name as table_name,
		il.name as key_name,
		ii.name as column_name,
		CASE il.origin when 'pk' then 1 else 0 END as is_primary,
		CASE il.[unique] when 1 then 0 else 1 END as non_unique,
		il.[unique] as is_unique,
		il.partial,
		il.seq as seq_in_index
  	FROM sqlite_master AS m,
	    pragma_index_list(m.name) AS il,
	    pragma_index_info(il.name) AS ii
 	WHERE 
 		m.type = 'table'
 		{$filter}
 	GROUP BY
 		m.tbl_name,
		il.name,
		ii.name,
		il.origin,
		il.partial,
		il.seq
 	ORDER BY 1,6
ENDOFQUERY;
	$recs=sqliteQueryResults($query);
	$databaseCache['sqliteGetDBTableIndexes'][$tablename]=$recs;
	return $databaseCache['sqliteGetDBTableIndexes'][$tablename];
}

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
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^sqlite/i',$k)){unset($CONFIG[$k]);}
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
		else{return 'sqliteParseConnectParams Error: No dbname set'.printValue($CONFIG);}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	//readonly
	if(!isset($params['-sqlite_readonly']) && isset($CONFIG['sqlite_readonly'])){
		$params['-readonly']=$CONFIG['sqlite_readonly'];
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
	global $CONFIG;
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=sqliteParseConnectParams($params);
	if(!isset($params['-dbname'])){
		$CONFIG['sqlite_error']="dbname not set";
		debugValue("sqliteDBConnect error: no dbname set".printValue($params));
		return null;
	}
	if(!isset($params['-mode'])){$params['-mode']=0666;}
	//echo printValue($params).printValue($_SERVER);exit;
	//check to see if the sqlite database is available. Find it if possible
	if(!file_exists($params['-dbname'])){
		$CONFIG['sqlite_error']="dbname does not exist";
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
	$CONFIG['sqlite_dbname_realpath']=$params['-dbname'];
	//echo printValue($params);exit;
	global $dbh_sqlite;
	if($dbh_sqlite){return $dbh_sqlite;}
	try{
		if(isset($params['-readonly']) && $params['-readonly']==1){
			$dbh_sqlite = new SQLite3($params['-dbname'],SQLITE3_OPEN_READONLY);
		}
		else{
			$dbh_sqlite = new SQLite3($params['-dbname'],SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);	
		}
		
		$dbh_sqlite->busyTimeout(5000);
		//register some PHP functions so we can use them in queries
		if(!$dbh_sqlite->createFunction("config_value", "configValue", 1)){
			debugValue("unable to create config_value function");
		}
		if(!$dbh_sqlite->createFunction("user_value", "userValue", 1)){
			debugValue("unable to create user_value function");
		}
		if(!$dbh_sqlite->createFunction("is_user", "isUser", 0)){
			debugValue("unable to create is_user function");
		}
		if(!$dbh_sqlite->createFunction("is_admin", "isAdmin", 0)){
			debugValue("unable to create is_admin function");
		}
		if(!$dbh_sqlite->createFunction("verbose_size", "verboseSize", -1)){
			debugValue("unable to create verbose_size function");
		}
		if(!$dbh_sqlite->createFunction("verbose_time", "verboseTime", -1)){
			debugValue("unable to create verbose_time function");
		}
		if(!$dbh_sqlite->createFunction("verbose_number", "verboseNumber", -1)){
			debugValue("unable to create verbose_number function");
		}
		if(!$dbh_sqlite->createFunction("format_phone", "commonFormatPhone", 1)){
			debugValue("unable to create format_phone function");
		}
		if(!$dbh_sqlite->createFunction("string_contains", "stringContains", 2)){
			debugValue("unable to create string_contains function");
		}
		if(!$dbh_sqlite->createFunction("php_version", "phpversion")){
			debugValue("unable to create php_version function");
		}

		// WAL mode has better control over concurrency.
		// Source: https://www.sqlite.org/wal.html
		$dbh_sqlite->exec('PRAGMA journal_mode = wal;');
		return $dbh_sqlite;
	}
	catch (Exception $e) {
		$err=$e->getMessage();
		$CONFIG['sqlite_error']=$err;
		debugValue("sqliteDBConnect exception - {$err}");
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
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$query="SELECT name FROM {$parts[0]}.sqlite_master WHERE type='table' and name = '{$table}'";
		$table=$parts[1];
	}
	else{
		$query="SELECT name FROM sqlite_master WHERE type='table' and name = '{$table}'";
	}
	$recs=sqliteQueryResults($query);
	if(isset($recs[0]['name'])){return true;}
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
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'sqliteExecuteSQL'
	);
	$dbh_sqlite=sqliteDBConnect($params);
	//enable exceptions
	$dbh_sqlite->enableExceptions(true);
	try{
		$result=$dbh_sqlite->exec($query);
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
	$fields=sqliteGetDBFieldInfo($params['-table']);
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
		$err=array(
			'msg'=>"sqliteAddDBRecord error",
			'error'	=> $dbh_sqlite->lastErrorMsg(),
			'query'	=> $query
		);
    	debugValue(array("sqliteAddDBRecord Connect Error",$err));
    	return;
	}
	//enable exceptions
	$dbh_sqlite->enableExceptions(true);
	try{
		$stmt=$dbh_sqlite->prepare($query);
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
		$msg=$e->getMessage();
		debugValue(array(
			'function'=>'sqliteAddDBRecord',
			'message'=>'query failed',
			'error'=>$msg,
			'query'=>$query,
			'params'=>$params
		));
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
function sqliteEditDBRecord($params,$id=0,$opts=array()){
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'sqliteEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'sqliteEditDBRecord error: No where specified.';}
	global $USER;
	$fields=sqliteGetDBFieldInfo($params['-table']);
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
    	$err=array(
			'msg'=>"sqliteEditDBRecord error",
			'error'	=> $dbh_sqlite->lastErrorMsg(),
			'query'	=> $query
		);
    	debugValue(array("sqliteEditDBRecord Connect Error",$err));
    	return;
	}
	//enable exceptions
	$dbh_sqlite->enableExceptions(true);
	try{
		$stmt=$dbh_sqlite->prepare($query);
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
		$msg=$e->getMessage();
		debugValue(array(
			'function'=>'sqliteEditDBRecord',
			'message'=>'query failed',
			'error'=>$msg,
			'query'=>$query,
			'params'=>$params
		));
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
* @return array returns array of table names
* @usage $tables=sqliteGetDBTables();
*/
function sqliteGetDBTables($params=array()){
	$tables=array();
	$query=<<<ENDOFQUERY
	SELECT 
	    name
	FROM 
	    sqlite_master 
	WHERE 
	    type ='table' AND 
	    name NOT LIKE 'sqlite_%'
	ORDER BY name
ENDOFQUERY;
	if(isset($params['-queryonly'])){
		return $query;
	}
	$recs=sqliteQueryResults($query);
	//echo $query.printValue($recs);exit;
	foreach($recs as $rec){
		$tables[]=strtolower($rec['name']);
	}
	return $tables;
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
	if(!strlen(trim($tablename))){return;}
	global $sqliteGetDBFieldInfoCache;
	$key=$tablename.encodeCRC(json_encode($params));
	//clear cache?
	if(isset($params['-force']) && $params['-force'] && $sqliteGetDBFieldInfoCache[$key]){
		unset($sqliteGetDBFieldInfoCache[$key]);
	}
	//check cache
	if(isset($sqliteGetDBFieldInfoCache[$key])){return $sqliteGetDBFieldInfoCache[$key];}
	//echo "sqliteGetDBFieldInfo:{$key}({$tablename})".printValue($params).PHP_EOL;
	//check for dbname.tablename
	$parts=preg_split('/\./',$tablename,2);
	if(count($parts)==2){
		$query="PRAGMA {$parts[0]}.table_info({$parts[1]})";	
	}
	else{
		$query="PRAGMA table_info({$tablename})";
	}
	$recs=array();
	//echo "{$query}<br><br>".PHP_EOL;
	$xrecs=sqliteQueryResults($query);
	foreach($xrecs as $rec){
		$name=strtolower($rec['name']);
		$recs[$name]=array(
			'table'=>$tablename,
			'_dbtable'=>$tablename,
			'name'=>$rec['name'],
			'_dbfield'=>strtolower($rec['name']),
			'_dbtype'=>$rec['type'],
			'_dbdefault'=>$rec['dflt_value'],
			'_dbnull'=>$rec['notnull']==0?'NO':'YES',
			'_dbprimarykey'=>$rec['pk']==0?'NO':'YES'
		);
		$recs[$name]['type']=$recs[$name]['_dbtype'];
		$recs[$name]['_dbtype_ex']=strtolower($recs[$name]['_dbtype']);
	}
	//echo printValue($recs);exit;
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
		$meta_recs=sqliteGetDBRecords($metaopts);
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
//---------- begin function sqliteGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage $cnt=sqliteGetDBCount(array('-table'=>'states'));
*/
function sqliteGetDBCount($params=array()){
	if(!isset($params['-table'])){return null;}
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$params['-queryonly']=1;
	$query=sqliteGetDBRecords($params);
	if(!stringContains($query,'where')){
	 	$query1="SELECT tbl,stat FROM sqlite_stat1 where tbl='{$params['-table']}' limit 1";
	 	$recs=sqliteQueryResults($query1);
	 	//echo "HERE".$query.printValue($recs);
	 	if(isset($recs[0]['stat']) && strlen($recs[0]['stat'])){
	 		$parts=preg_split('/\ /',$recs[0]['stat'],2);
	 		return (integer)$parts[0];
	 	}
	}
	//echo "HERE".$query.printValue($params);
	$recs=sqliteQueryResults($query);
	//echo "HERE2".$query.printValue($recs);exit;
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function sqliteListDBDatatypes ----
/**
* @describe returns the data types for sqlite
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function sqliteListDBDatatypes(){
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
		$result=sqliteExecuteSQL("DELETE FROM {$table}");
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
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'sqliteQueryResults'
	);
	global $dbh_sqlite;
	if(!$dbh_sqlite){
		$dbh_sqlite=sqliteDBConnect($params);
	}
	if(!$dbh_sqlite){
		$DATABASE['_lastquery']['error']='connect error';
		debugValue($DATABASE['_lastquery']);
    	return array();
	}
	//enable exceptions
	$dbh_sqlite->enableExceptions(true);
	try{
		$results=$dbh_sqlite->query($query);
		if(!is_object($results)){
			$DATABASE['_lastquery']['error']='query error';
			debugValue($DATABASE['_lastquery']);
			return array();
		}
		$recs=sqliteEnumQueryResults($results,$params);
		$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
		return $recs;
	}
	catch (Exception $e) {
		$DATABASE['_lastquery']['error']=$e->getMessage();
		debugValue($DATABASE['_lastquery']);
		return array();
	}
}
//---------- begin function sqliteEnumQueryResults ----------
/**
* @describe enumerates through the data from a pg_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function sqliteEnumQueryResults($data,$params=array()){
	global $sqliteStopProcess;
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
			return 'sqliteQueryResults error: Failed to open '.$params['-filename'];
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
	while ($xrec = $data->fetchArray(SQLITE3_ASSOC)) {
		//check for sqliteStopProcess request
		if(isset($sqliteStopProcess) && $sqliteStopProcess==1){
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
	//echo "sqliteGetDBRecord".printValue($params).printValue($recs);
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
*	sqliteGetDBRecords(array('-table'=>'notes'));
*	sqliteGetDBRecords("select * from myschema.mytable where ...");
*/
function sqliteGetDBRecords($params){
	global $USER;
	global $CONFIG;
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
		$fields=sqliteGetDBFieldInfo($params['-table']);
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
	$recs=sqliteQueryResults($query,$params);
	//echo '<hr>'.$query.printValue($params).printValue($recs);
	return $recs;
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
function sqliteNamedQueryList(){
	return array(
		array(
			'code'=>'tables',
			'icon'=>'icon-table',
			'name'=>'Tables'
		)
	);
}
//---------- begin function sqliteNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function sqliteNamedQuery($name){
	$schema=sqliteGetDBSchema();
	switch(strtolower($name)){
		case 'tables':
			return <<<ENDOFQUERY
SELECT 
    name as table_name
FROM 
    sqlite_master 
WHERE 
    type ='table' AND 
    name NOT LIKE 'sqlite_%'
ORDER BY name
ENDOFQUERY;
		break;
	}
}
