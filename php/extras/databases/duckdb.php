<?php

/*
	duckdb Database functions
		http://php.net/manual/en/duckdb3.query.php
		*
*/
//---------- begin function duckdbAddDBIndex--------------------
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
*	$ok=duckdbAddDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=duckdbAddDBIndex(array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function duckdbAddDBIndex($params=array()){
	if(!isset($params['-table'])){return 'duckdbAddDBIndex Error: No table';}
	if(!isset($params['-fields'])){return 'duckdbAddDBIndex Error: No fields';}
	if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,+/',$params['-fields']);}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=duckdbGetDBSchema();
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
	return duckdbExecuteSQL($query);
}

//---------- begin function duckdbAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
* @return count int
* @usage $ok=duckdbAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=duckdbAddDBRecords('comments',array('-recs'=>$recs);
*/
function duckdbAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("duckdbAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	$params['-table']=$table;
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("duckdbAddDBRecords Error: either -csv or -recs is required");
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("duckdbAddDBRecords Error: no such file: {$params['-csv']}");
		}
		return processCSVLines($params['-csv'],'duckdbAddDBRecordsProcess',$params);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("duckdbAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("duckdbAddDBRecords Error: no recs");
		}
		return duckdbAddDBRecordsProcess($params['-recs'],$params);
	}
}
function duckdbAddDBRecordsProcess($recs,$params=array()){
	//echo 'duckdbAddDBRecordsProcess'.count($recs).printValue($params);exit;
	if(!isset($params['-table'])){
		return debugValue("duckdbAddDBRecordsProcess Error: no table"); 
	}
	if(!is_array($recs) || !count($recs)){
		return debugValue("duckdbAddDBRecordsProcess Error: recs is empty"); 
	}
	$table=$params['-table'];
	$fieldinfo=duckdbGetDBFieldInfo($table,1);
	//indexes must be normal - fix if not
	$xrecs=array();
	foreach($recs as $rec){$xrecs[]=$rec;}
	$recs=$xrecs;
	unset($xrecs);
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
	//if -map2json then map the whole record to this field
	if(isset($params['-map2json'])){
		$jsonkey=$params['-map2json'];
		foreach($recs as $i=>$rec){
			$recs[$i]=array($jsonkey=>$rec);
		}
	}
	//fields
	$fields=array();
	foreach($recs as $i=>$first_rec){
		foreach($first_rec as $k=>$v){
			if(!isset($fieldinfo[$k])){
				unset($recs[$i][$k]);
				continue;
			}
			if(!in_array($k,$fields)){$fields[]=$k;}
		}
		break;
	}
	if(!count($fields)){
		debugValue(array(
			'function'=>'duckdbAddDBRecordsProcess',
			'message'=>'No fields in first_rec that match fieldinfo',
			'first_rec'=>$first_rec,
			'fieldinfo_keys'=>array_keys($fieldinfo)
		));
		return 0;
	}
	$fieldstr=implode(',',$fields);
	//if possible use the JSON way so we can insert more efficiently
	$jsonstr=encodeJSON($recs,JSON_UNESCAPED_UNICODE);
	if(strlen($jsonstr)){
		
		$extracts=array();
		foreach($fields as $fld){
			$extracts[]="JSON_EXTRACT(value,'\$.{$fld}') as {$fld}";
		}
		$extractstr=implode(','.PHP_EOL,$extracts);
		$query=<<<ENDOFQUERY
			INSERT OR REPLACE INTO {$table} ($fieldstr)
			  SELECT
			    {$extractstr}
			  FROM JSON_EACH(?)
			RETURNING *;
ENDOFQUERY;
		$dbh_duckdb=duckdbDBConnect($params);
		if(!$dbh_duckdb){
			$err=array(
				'msg'=>"duckdbAddDBRecordsProcess error",
				'error'	=> $dbh_duckdb->lastErrorMsg(),
				'query'	=> $query
			);
	    	debugValue(array("duckdbAddDBRecord Connect Error",$err));
	    	return 0;
		}
		//enable exceptions
		$dbh_duckdb->enableExceptions(true);
		try{
			$stmt=$dbh_duckdb->prepare($query);
			//bind the jsonstring to the prepared statement
			$stmt->bindParam(1,$jsonstr,duckdb3_TEXT);
			$results=$stmt->execute();
			$recs=duckdbEnumQueryResults($results,$params);
			return count($recs);
		}
		catch (Exception $e) {
			$msg=$e->getMessage();
			debugValue(array(
				'function'=>'duckdbAddDBRecordsProcess',
				'message'=>'query failed',
				'error'=>$msg,
				'query'=>$query,
				'params'=>$params
			));
			return 0;
		}
	}
	//JSON method did not work, try standard prepared statement method
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
				$v=duckdbEscapeString($v);
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
	$ok=duckdbExecuteSQL($query);
	return count($values);
}
//---------- begin function duckdbAddDBFields--------------------
/**
* @describe adds fields to given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return array - name,type,query,result for each field set
* @usage
*	$ok=duckdbAddDBFields('comments',array('comment'=>"varchar(1000) NULL"));
*/
function duckdbAddDBFields($table,$fields=array(),$maintain_order=1){
	$recs=array();
	foreach($fields as $name=>$type){
		$crec=array('name'=>$name,'type'=>$type);
		$fieldstr="{$name} {$type}";
		$crec['query']="ALTER TABLE {$table} ADD ({$fieldstr})";
		$crec['result']=duckdbExecuteSQL($crec['query']);
		$recs[]=$crec;
	}
	return $recs;
}
//---------- begin function duckdbDropDBFields--------------------
/**
* @describe drops fields to given table
* @param table string - name of table to alter
* @param params array - list of fields
* @return array - name,query,result for each field
* @usage
*	$ok=duckdbDropDBFields('comments',array('comment','age'));
*/
function duckdbDropDBFields($table,$fields=array()){
	$recs=array();
	foreach($fields as $name){
		$crec=array('name'=>$name);
		$crec['query']="ALTER TABLE {$table} DROP ({$name})";
		$crec['result']=duckdbExecuteSQL($crec['query']);
		$recs[]=$crec;
	}
	return $recs;
}
//---------- begin function duckdbAlterDBTable--------------------
/**
* @describe alters fields in given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=duckdbAlterDBTable('comments',array('comment'=>"varchar(1000) NULL"));
*/
function duckdbAlterDBTable($table,$fields=array(),$maintain_order=1){
	$info=duckdbGetDBFieldInfo($table);
	if(!is_array($info) || !count($info)){
		debugValue("duckdbAlterDBTable - {$table} is missing or has no fields".printValue($table));
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
		$ok=duckdbExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	if(count($addfields)){
		$fieldstr=implode(', ',$addfields);
		$query="ALTER TABLE {$table} ADD ({$fieldstr})";
		$ok=duckdbExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	return $rtn;
}
function duckdbEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function duckdbGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=duckdbGetTableDDL('sample');
*/
function duckdbGetTableDDL($table,$schema=''){
	global $DATABASE;
	global $CONFIG;
	$db=$CONFIG['db'];
	if(isset($DATABASE[$db]['dbtype']) && $DATABASE[$db]['dbtype']=='duckdb'){
		$DATABASE['_lastquery']['dbname']=$DATABASE[$db]['dbname'];
	}
	else{
		$DATABASE['_lastquery']['dbname']='';
	}
	$cmd="duckdb -c \".schema {$table}\"";
	if(strlen($DATABASE['_lastquery']['dbname'])){$cmd.=" \"{$DATABASE['_lastquery']['dbname']}\"";}
	$out=cmdResults($cmd);
	//echo "<pre>{$query}</pre>".printValue($cmd);exit;
	if(isset($out['stderr']) && strlen($out['stderr'])){
		$DATABASE['_lastquery']['error']=$out['stderr'];
		debugValue($DATABASE['_lastquery']);
		return $out['stderr'];
	}
	return $out['stdout'];
}
//---------- begin function duckdbGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=duckdbGetAllTableFields();
*/
function duckdbGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'duckdbGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	$query=<<<ENDOFQUERY
	SELECT 
		table_name,
		column_name AS field_name,
		data_type AS type_name 
	FROM information_schema.columns
	ORDER BY table_name,ordinal_position
ENDOFQUERY;
	$recs=duckdbQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$field=strtolower($rec['field_name']);
		$type=strtolower($rec['type_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function duckdbGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=duckdbGetAllTableIndexes();
*/
function duckdbGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'duckdbGetAllTableIndexes');
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
  	FROM duckdb_master AS m,
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
	$recs=duckdbQueryResults($query);
	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>'.printValue($recs);exit;
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
function duckdbGetDBSchema(){
	global $CONFIG;
	global $DATABASE;
	$params=duckdbParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	elseif(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['duckdb_dbschema'])){return $CONFIG['duckdb_dbschema'];}
	elseif(isset($CONFIG['duckdb_schema'])){return $CONFIG['duckdb_schema'];}
	return '';
}
//---------- begin function duckdbGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=duckdbGetDBRecordById('comments',7);
*/
function duckdbGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "duckdbGetDBRecordById Error: No Table";}
	if($id == 0){return "duckdbGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=duckdbGetDBRecord($recopts);
	return $rec;
}
//---------- begin function duckdbEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=duckdbEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function duckdbEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("duckdbEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("duckdbEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("duckdbEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("duckdbEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return duckdbEditDBRecord($params);
}
//---------- begin function duckdbDelDBRecord ----------
/**
* @describe deletes records in table that match -where clause
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
* @return boolean
* @usage $id=duckdbDelDBRecord(array('-table'=> '_tabledata','-where'=> "_id=4"));
*/
function duckdbDelDBRecord($params=array()){
	global $USER;
	if(!isset($params['-table'])){return 'duckdbDelDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'duckdbDelDBRecord Error: No where';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=duckdbGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$query="delete from {$params['-table']} where " . $params['-where'];
	return duckdbExecuteSQL($query);
}
//---------- begin function duckdbDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=duckdbDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function duckdbDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("duckdbDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("duckdbDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return duckdbDelDBRecord($params);
}
//---------- begin function duckdbCreateDBTable--------------------
/**
* @describe creates table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=duckdbCreateDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function duckdbCreateDBTable($table='',$fields=array()){
	if(strlen($table)==0){return "duckdbCreateDBTable error: No table";}
	if(count($fields)==0){return "duckdbCreateDBTable error: No fields";}
	if(duckdbIsDBTable($table)){return 0;}
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
	$query_result=duckdbExecuteSQL($query);
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

function duckdbGetDBIndexes($tablename=''){
	return duckdbGetDBTableIndexes($tablename);
}
function duckdbGetDBTableIndexes($tablename=''){
	global $databaseCache;
	if(isset($databaseCache['duckdbGetDBTableIndexes'][$tablename])){
		return $databaseCache['duckdbGetDBTableIndexes'][$tablename];
	}
	$filter='';
	if(strlen($tablename)){
		$filter="and table_name = '{$tablename}'";
	}
	$query=<<<ENDOFQUERY
	SELECT 
		table_name as table_name,
		index_name as key_name,
		expressions as column_name,
		is_primary,
		CASE is_unique when 1 then 0 else 1 END as non_unique,
		is_unique
  	FROM duckdb_indexes() 
  	WHERE 
  		schema_name='main'
 		{$filter}
 	ORDER BY 1,2
ENDOFQUERY;
	$recs=duckdbQueryResults($query);
	$databaseCache['duckdbGetDBTableIndexes'][$tablename]=$recs;
	return $databaseCache['duckdbGetDBTableIndexes'][$tablename];
}

//---------- begin function duckdbListRecords
/**
* @describe returns an html table of records from a duckdb database. refer to databaseListRecords
*/
function duckdbListRecords($params=array()){
	$params['-database']='duckdb';
	return databaseListRecords($params);
}
//---------- begin function duckdbParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_duckdb,dbuser_duckdb, and dbpass_duckdb
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=duckdbParseConnectParams($params);
*/
function duckdbParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^duckdb/i',$k)){unset($CONFIG[$k]);}
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
		if(isset($CONFIG['dbname_duckdb'])){
			$params['-dbname']=$CONFIG['dbname_duckdb'];
			$params['-dbname_source']="CONFIG dbname_duckdb";
		}
		elseif(isset($CONFIG['duckdb_dbname'])){
			$params['-dbname']=$CONFIG['duckdb_dbname'];
			$params['-dbname_source']="CONFIG duckdb_dbname";
		}
		elseif(isset($CONFIG['dbname'])){
			$params['-dbname']=$CONFIG['dbname'];
			$params['-dbname_source']="CONFIG dbname";
		}
		else{return 'duckdbParseConnectParams Error: No dbname set'.printValue($CONFIG);}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	//readonly
	if(!isset($params['-duckdb_readonly']) && isset($CONFIG['duckdb_readonly'])){
		$params['-readonly']=$CONFIG['duckdb_readonly'];
	}
	//dbmode
	if(!isset($params['-dbmode'])){
		if(isset($CONFIG['dbmode_duckdb'])){
			$params['-dbmode']=$CONFIG['dbmode_duckdb'];
			$params['-dbmode_source']="CONFIG dbname_duckdb";
		}
		elseif(isset($CONFIG['duckdb_dbmode'])){
			$params['-dbmode']=$CONFIG['duckdb_dbmode'];
			$params['-dbmode_source']="CONFIG duckdb_dbname";
		}
	}
	else{
		$params['-dbmode_source']="passed in";
	}
	return $params;
}
//---------- begin function duckdbDBConnect ----------
/**
* @describe connects to a duckdb database and returns the handle resource
* @param $param array - These can also be set in the CONFIG file with dbname_duckdb,dbuser_duckdb, and dbpass_duckdb
* 	[-dbname] - name of ODBC connection
*   [-single] - if you pass in -single it will connect using duckdb_connect instead of duckdb_pconnect and return the connection
* @return $dbh_duckdb resource - returns the duckdb connection resource
* @usage $dbh_duckdb=duckdbDBConnect($params);
* @usage  example of using -single
*
	$conn=duckdbDBConnect(array('-single'=>1));
	duckdb_autocommit($conn, FALSE);

	duckdb_exec($conn, $query1);
	duckdb_exec($conn, $query2);

	if (!duckdb_error()){
		duckdb_commit($conn);
	}
	else{
		duckdb_rollback($conn);
	}
	duckdb_close($conn);
*
*/
function duckdbDBConnect($params=array()){
	global $CONFIG;
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=duckdbParseConnectParams($params);
	if(!isset($params['-dbname'])){
		$CONFIG['duckdb_error']="dbname not set";
		debugValue("duckdbDBConnect error: no dbname set".printValue($params));
		return null;
	}
	if(!isset($params['-mode'])){$params['-mode']=0666;}
	//echo printValue($params).printValue($_SERVER);exit;
	//check to see if the duckdb database is available. Find it if possible
	if(!file_exists($params['-dbname'])){
		$CONFIG['duckdb_error']="dbname does not exist";
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
	$CONFIG['duckdb_dbname_realpath']=$params['-dbname'];
	//echo printValue($params);exit;
	global $dbh_duckdb;
	if($dbh_duckdb){return $dbh_duckdb;}
	try{
		if(isset($params['-readonly']) && $params['-readonly']==1){
			$dbh_duckdb = new duckdb3($params['-dbname'],duckdb3_OPEN_READONLY);
		}
		else{
			$dbh_duckdb = new duckdb3($params['-dbname'],duckdb3_OPEN_READWRITE | duckdb3_OPEN_CREATE);	
		}
		
		$dbh_duckdb->busyTimeout(5000);
		//register some PHP functions so we can use them in queries
		if(!$dbh_duckdb->createFunction("config_value", "configValue", 1)){
			debugValue("unable to create config_value function");
		}
		if(!$dbh_duckdb->createFunction("user_value", "userValue", 1)){
			debugValue("unable to create user_value function");
		}
		if(!$dbh_duckdb->createFunction("is_user", "isUser", 0)){
			debugValue("unable to create is_user function");
		}
		if(!$dbh_duckdb->createFunction("is_admin", "isAdmin", 0)){
			debugValue("unable to create is_admin function");
		}
		if(!$dbh_duckdb->createFunction("verbose_size", "verboseSize", -1)){
			debugValue("unable to create verbose_size function");
		}
		if(!$dbh_duckdb->createFunction("verbose_time", "verboseTime", -1)){
			debugValue("unable to create verbose_time function");
		}
		if(!$dbh_duckdb->createFunction("verbose_number", "verboseNumber", -1)){
			debugValue("unable to create verbose_number function");
		}
		if(!$dbh_duckdb->createFunction("format_phone", "commonFormatPhone", 1)){
			debugValue("unable to create format_phone function");
		}
		if(!$dbh_duckdb->createFunction("string_contains", "stringContains", 2)){
			debugValue("unable to create string_contains function");
		}
		if(!$dbh_duckdb->createFunction("php_version", "phpversion")){
			debugValue("unable to create php_version function");
		}

		// WAL mode has better control over concurrency.
		// Source: https://www.duckdb.org/wal.html
		$dbh_duckdb->exec('PRAGMA journal_mode = wal;');
		return $dbh_duckdb;
	}
	catch (Exception $e) {
		$err=$e->getMessage();
		$CONFIG['duckdb_error']=$err;
		debugValue("duckdbDBConnect exception - {$err}");
		return null;

	}
}
//---------- begin function duckdbIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_duckdb,dbuser_duckdb, and dbpass_duckdb
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(duckdbIsDBTable('abc')){...}
*/
function duckdbIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "duckdbIsDBTable error: No table";
		return null;
	}
	$table=strtolower($table);
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$query="SELECT name FROM {$parts[0]}.duckdb_master WHERE type='table' and name = '{$table}'";
		$table=$parts[1];
	}
	else{
		$query="SELECT name FROM duckdb_master WHERE type='table' and name = '{$table}'";
	}
	$recs=duckdbQueryResults($query);
	if(isset($recs[0]['name'])){return true;}
	return false;
}
//---------- begin function duckdbClearConnection ----------
/**
* @describe clears the duckdb database connection
* @return boolean returns true if query succeeded
* @usage $ok=duckdbClearConnection();
*/
function duckdbClearConnection(){
	global $dbh_duckdb;
	$dbh_duckdb=null;
	return true;
}
//---------- begin function duckdbExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param $params array - These can also be set in the CONFIG file with dbname_duckdb,dbuser_duckdb, and dbpass_duckdb
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage $ok=duckdbExecuteSQL("truncate table abc");
*/
function duckdbExecuteSQL($query,$params=array()){
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'duckdbExecuteSQL'
	);
	$dbh_duckdb=duckdbDBConnect($params);
	//enable exceptions
	$dbh_duckdb->enableExceptions(true);
	try{
		$result=$dbh_duckdb->exec($query);
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
//---------- begin function duckdbAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_duckdb,dbuser_duckdb, and dbpass_duckdb
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=duckdbAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function duckdbAddDBRecord($params){
	//echo "duckdbAddDBRecord".printValue($params);exit;
	global $USER;
	if(!isset($params['-table'])){return 'duckdbAddRecord error: No table specified.';}
	$fields=duckdbGetDBFieldInfo($params['-table']);
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
		debugValue(array("duckdbAddDBRecord Error",$e));
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
	$dbh_duckdb=duckdbDBConnect($params);
	if(!$dbh_duckdb){
		$err=array(
			'msg'=>"duckdbAddDBRecord error",
			'error'	=> $dbh_duckdb->lastErrorMsg(),
			'query'	=> $query
		);
    	debugValue(array("duckdbAddDBRecord Connect Error",$err));
    	return;
	}
	//enable exceptions
	$dbh_duckdb->enableExceptions(true);
	try{
		$stmt=$dbh_duckdb->prepare($query);
		foreach($vals as $i=>$v){
			$fld=$flds[$i];
			$x=$i+1;
			//echo "{$x}::{$v}::{$fields[$fld]['type']}<br>".PHP_EOL;
			switch(strtolower($fields[$fld]['type'])){
				case 'integer':
					$stmt->bindParam($x,$vals[$i],duckdb3_INTEGER);
				break;
				case 'float':
					$stmt->bindParam($x,$vals[$i],duckdb3_FLOAT);
				break;
				case 'blob':
					$stmt->bindParam($x,$vals[$i],duckdb3_BLOB);
				break;
				case 'null':
					$stmt->bindParam($x,$vals[$i],duckdb3_NULL);
				break;
				default:
					$stmt->bindParam($x,$vals[$i],duckdb3_TEXT);
				break;
			}
		}
		$results=$stmt->execute();
		return $dbh_duckdb->lastInsertRowID();;
	}
	catch (Exception $e) {
		$msg=$e->getMessage();
		debugValue(array(
			'function'=>'duckdbAddDBRecord',
			'message'=>'query failed',
			'error'=>$msg,
			'query'=>$query,
			'params'=>$params
		));
		return null;
	}
	return 0;
}
//---------- begin function duckdbEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_duckdb,dbuser_duckdb, and dbpass_duckdb
*   -table - name of the table to add to
*   -where - filter criteria
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage $id=duckdbEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function duckdbEditDBRecord($params,$id=0,$opts=array()){
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'duckdbEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'duckdbEditDBRecord error: No where specified.';}
	global $USER;
	$fields=duckdbGetDBFieldInfo($params['-table']);
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
		debugValue(array("duckdbEditDBRecord Error",$e));
    	return;
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	$dbh_duckdb=duckdbDBConnect($params);
	if(!$dbh_duckdb){
    	$err=array(
			'msg'=>"duckdbEditDBRecord error",
			'error'	=> $dbh_duckdb->lastErrorMsg(),
			'query'	=> $query
		);
    	debugValue(array("duckdbEditDBRecord Connect Error",$err));
    	return;
	}
	//enable exceptions
	$dbh_duckdb->enableExceptions(true);
	try{
		$stmt=$dbh_duckdb->prepare($query);
		foreach($vals as $i=>$v){
			$fld=$flds[$i];
			$x=$i+1;
			//echo "{$x}::{$v}::{$fields[$fld]['type']}<br>".PHP_EOL;
			switch(strtolower($fields[$fld]['type'])){
				case 'integer':
					$stmt->bindParam($x,$vals[$i],duckdb3_INTEGER);
				break;
				case 'float':
					$stmt->bindParam($x,$vals[$i],duckdb3_FLOAT);
				break;
				case 'blob':
					$stmt->bindParam($x,$vals[$i],duckdb3_BLOB);
				break;
				case 'null':
					$stmt->bindParam($x,$vals[$i],duckdb3_NULL);
				break;
				default:
					$stmt->bindParam($x,$vals[$i],duckdb3_TEXT);
				break;
			}
		}
		$results=$stmt->execute();
		if($results){
			$results->finalize();
		}
		else{
			$err=$dbh_duckdb->lastErrorMsg();
			if(strtolower($err) != 'not an error'){
				debugValue("duckdbEditDBRecord execute error: {$err}");
			}
			else{
				debugValue("duckdbEditDBRecord execute error: unknown reason");
			}
		}
		return 1;
	}
	catch (Exception $e) {
		$msg=$e->getMessage();
		debugValue(array(
			'function'=>'duckdbEditDBRecord',
			'message'=>'query failed',
			'error'=>$msg,
			'query'=>$query,
			'params'=>$params
		));
		return null;
	}
	return 0;
}
//---------- begin function duckdbGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_duckdb,dbuser_duckdb, and dbpass_duckdb
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of table names
* @usage $tables=duckdbGetDBTables();
*/
function duckdbGetDBTables($params=array()){
	$tables=array();
	$query=<<<ENDOFQUERY
	SELECT table_name AS name 
	FROM information_schema.tables 
	WHERE 
		table_schema = 'main'
	ORDER BY table_name
ENDOFQUERY;
	if(isset($params['-queryonly'])){
		return $query;
	}
	$recs=duckdbQueryResults($query);
	//echo $query.printValue($recs);exit;
	foreach($recs as $rec){
		$tables[]=strtolower($rec['name']);
	}
	return $tables;
}
//---------- begin function duckdbGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_duckdb,dbuser_duckdb, and dbpass_duckdb
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*	[-getmeta] - boolean - if true returns info in _fielddata table for these fields - defaults to false
*	[-field] - if this has a value return only this field - defaults to blank
*	[-force] - clear cache and refetch info
* @return boolean returns true on success
* @usage $fieldinfo=duckdbGetDBFieldInfo('abcschema.abc');
*/
function duckdbGetDBFieldInfo($tablename,$params=array()){
	if(!strlen(trim($tablename))){return;}
	global $duckdbGetDBFieldInfoCache;
	$key=$tablename.encodeCRC(json_encode($params));
	//clear cache?
	if(isset($params['-force']) && $params['-force'] && $duckdbGetDBFieldInfoCache[$key]){
		unset($duckdbGetDBFieldInfoCache[$key]);
	}
	//check cache
	if(isset($duckdbGetDBFieldInfoCache[$key])){return $duckdbGetDBFieldInfoCache[$key];}
	//echo "duckdbGetDBFieldInfo:{$key}({$tablename})".printValue($params).PHP_EOL;
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
	$xrecs=duckdbQueryResults($query);
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
		$meta_recs=duckdbGetDBRecords($metaopts);
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
	$duckdbGetDBFieldInfoCache[$key]=$recs;
	return $recs;
}
//---------- begin function duckdbGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage $cnt=duckdbGetDBCount(array('-table'=>'states'));
*/
function duckdbGetDBCount($params=array()){
	if(!isset($params['-table'])){return null;}
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$params['-queryonly']=1;
	$query=duckdbGetDBRecords($params);
	if(!stringContains($query,'where')){
	 	$query1="SELECT tbl,stat FROM duckdb_stat1 where tbl='{$params['-table']}' limit 1";
	 	$recs=duckdbQueryResults($query1);
	 	//echo "HERE".$query.printValue($recs);
	 	if(isset($recs[0]['stat']) && strlen($recs[0]['stat'])){
	 		$parts=preg_split('/\ /',$recs[0]['stat'],2);
	 		return (integer)$parts[0];
	 	}
	}
	//echo "HERE".$query.printValue($params);
	$recs=duckdbQueryResults($query);
	//echo "HERE2".$query.printValue($recs);exit;
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function duckdbListDBDatatypes ----
/**
* @describe returns the data types for duckdb
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function duckdbListDBDatatypes(){
	//default to 
	return <<<ENDOFDATATYPES
<div class="w_bold w_blue w_padtop">Numeric Types</div>
<div class="w_padleft">BOOLEAN - true/false values</div>
<div class="w_padleft">TINYINT - 8-bit integer</div>
<div class="w_padleft">SMALLINT - 16-bit integer</div>
<div class="w_padleft">INTEGER or INT - 32-bit integer</div>
<div class="w_padleft">BIGINT - 64-bit integer</div>
<div class="w_padleft">HUGEINT - 128-bit integer</div>
<div class="w_padleft">FLOAT - 32-bit floating point</div>
<div class="w_padleft">DOUBLE - 64-bit floating point</div>
<div class="w_padleft">DECIMAL(precision, scale) - Fixed-point number with user-defined precision</div>

<div class="w_bold w_blue w_padtop">String Types</div>
<div class="w_padleft">VARCHAR or TEXT - Variable-length character strings</div>
<div class="w_padleft">CHAR(n) - Fixed-length character strings</div>

<div class="w_bold w_blue w_padtop">Binary Types</div>
<div class="w_padleft">BLOB - Binary large object</div>
<div class="w_padleft">BIT - Bit string</div>

<div class="w_bold w_blue w_padtop">Temporal Types</div>
<div class="w_padleft">DATE - Calendar date (year, month, day)</div>
<div class="w_padleft">TIME - Time of day (hour, minute, second, microsecond)</div>
<div class="w_padleft">TIMESTAMP - Date and time</div>
<div class="w_padleft">TIMESTAMP WITH TIME ZONE - Date and time with timezone</div>
<div class="w_padleft">INTERVAL - Time interval</div>

<div class="w_bold w_blue w_padtop">Structured Types</div>
<div class="w_padleft">LIST - Ordered collection of values</div>
<div class="w_padleft">STRUCT - Record with named fields</div>
<div class="w_padleft">MAP - Collection of key-value pairs</div>
<div class="w_padleft">UNION - Tagged union of multiple possible types</div>
<div class="w_padleft">ENUM - Enumeration of possible string values</div>
<div class="w_padleft">UUID - Universally unique identifier</div>

<div class="w_bold w_blue w_padtop">Special Types</div>
<div class="w_padleft">JSON - JSON document</div>
ENDOFDATATYPES;
}
//---------- begin function duckdbTruncateDBTable ----------
/**
* @describe truncates the specified table
* @param $table mixed - the table to truncate or and array of tables to truncate
* @return boolean integer
* @usage $cnt=duckdbTruncateDBTable('test');
*/
function duckdbTruncateDBTable($table){
	if(is_array($table)){$tables=$table;}
	else{$tables=array($table);}
	foreach($tables as $table){
		if(!duckdbIsDBTable($table)){return "No such table: {$table}.";}
		$result=duckdbExecuteSQL("DELETE FROM {$table}");
		if(isset($result['error'])){
			return $result['error'];
	        }
	    }
    return 1;
}
//---------- begin function duckdbQueryResults
/*
* @describe returns record sets from a duckdb query. Supports reading (and joining) csv, orc, json, avro, parquet, xlsx, arrow files, mysql, postgres, and sqlite using config.xml settings. NOTE: duckdb must be installed and in your PATH
* @param query string - query to send to duckdb
* @return array recordsets
* @usage
*	$recs=duckdbQueryResults("SELECT * FROM read_xlsx('d:/temp3/ideas.xlsx')");
*	$recs=duckdbQueryResults("SELECT * FROM read_csv('d:/temp3/ideas.csv')");
* 	Example of a query that joins multiple data sources and types
*	SELECT
*		c.distid,c.name,c.email,
*		j.age,j.color,
*		m.code,m.name as country_name,
*		count(o.ordernumber) as order_count,
*		sum(o.order_amount) as order_total,
*		count(oi.itemid) as item_count
*	-- CSV file
*	FROM read_csv('d:/temp3/names.csv') c
*	-- JSON file
*	JOIN read_json('d:/temp3/ages.json') j on j.distid=c.distid 
*	-- SQLite DB
*	JOIN sqlite_orders.orders o on o.dist_id=c.distid
*	-- Postgres DB
*	JOIN pg_local_master.order_items oi on oi.distid=c.distid
*	-- Mysql DB
*	JOIN dis_live.states m on m.code=o.state and m.country='US'
*	GROUP BY 
*		c.distid,c.name,c.email,
*		j.age,j.color,
*		m.code,m.name
*/
function duckdbQueryResults($query,$params=array()){
	global $DATABASE;
	global $CONFIG;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'duckdbQueryResults',
		'params'=>$params
	);
	$db=$CONFIG['db'];
	if(isset($DATABASE[$db]['dbtype']) && $DATABASE[$db]['dbtype']=='duckdb'){
		$DATABASE['_lastquery']['dbname']=$DATABASE[$db]['dbname'];
	}
	else{
		$DATABASE['_lastquery']['dbname']='';
	}
	//return printValue($DATABASE);

	$tpath=getWasqlTempPath();
	$tpath=str_replace("\\","/",$tpath);
	//add preloads. csv, json, orc files do not need preloads
	$preloads=array();
	//excel xlsx files
	if(stringContains($query,'FROM read_xlsx(') && !stringContains($query,'LOAD excel')){
		$preloads[]="INSTALL excel; LOAD excel;";
	}
	//avro files
	if(stringContains($query,'FROM read_avro(') && !stringContains($query,'LOAD avro')){
		$preloads[]="INSTALL avro; LOAD avro;";
	}
	//arrow files
	if(stringContains($query,'FROM read_arrow(') && !stringContains($query,'LOAD nanoarrow')){
		$preloads[]="INSTALL nanoarrow; LOAD nanoarrow;";
	}
	//remote files
	if(preg_match('/\(\'(http|https|s3)\:/is',$query)){
		$preloads[]="INSTALL httpfs; LOAD httpfs;";
	}
	//DATABASE
	foreach($DATABASE as $name=>$db){
		if(stringContains($query," {$name}.")){
			switch(strtolower($db['dbtype'])){
				case 'postgresql':
				case 'postgres':
					//tested: 2025-03-31
					if(!isset($db['dbport'])){$db['dbport']=5432;}
					$preloads[]="INSTALL postgres_scanner; LOAD postgres_scanner;";
					$preloads[]="ATTACH 'host={$db['dbhost']} port={$db['dbport']} user={$db['dbuser']} password={$db['dbpass']} dbname={$db['dbname']}' AS {$name} (TYPE postgres);";
				break;
				case 'mysql':
				case 'mysqli':
					//tested: 2025-03-31
					$preloads[]="INSTALL mysql; LOAD mysql;";
					$preloads[]="ATTACH 'host={$db['dbhost']} user={$db['dbuser']} password={$db['dbpass']} database={$db['dbname']}' AS {$name} (TYPE mysql);";
				break;
				case 'sqlite':
					//tested: 2025-04-01
					$preloads[]="INSTALL sqlite; LOAD sqlite;";
					$preloads[]="ATTACH '{$db['dbname']}' AS {$name} (TYPE sqlite);";
				break;
			}
		}
	}
	if(isset($params['-filename']) && strlen($params['-filename'])){
		$csvfile=str_replace("\\","/",$params['-filename']);
		$preloads[]=".mode csv";
		$preloads[]=".output {$csvfile}";
		if(count($preloads)){
			$query=implode(PHP_EOL,$preloads).PHP_EOL.$query;
		}
		$filename='duckdb_'.sha1($query).'.sql';
		$afile="{$tpath}/{$filename}";
		$ok=setFileContents($afile,$query);
		$cmd="duckdb -csv -c \".read {$afile}\"";
		if(strlen($DATABASE['_lastquery']['dbname'])){$cmd.=" \"{$DATABASE['_lastquery']['dbname']}\"";}
		$out=cmdResults($cmd);
		//echo "<pre>{$query}</pre>".printValue($cmd);exit;
		if(isset($out['stderr']) && strlen($out['stderr'])){
			$DATABASE['_lastquery']['error']=$out['stderr'];
			debugValue($DATABASE['_lastquery']);
    		return 0;
		}
		unlink($afile);
		//call duckdb again to return the count
		$cmd="duckdb -json -c \"SELECT count(*) AS cnt FROM read_csv('{$csvfile}');\"";
		$out=cmdResults($cmd);
		if(isset($out['stderr']) && strlen($out['stderr'])){
			$DATABASE['_lastquery']['error']=$out['stderr'];
			debugValue($DATABASE['_lastquery']);
    		return 0;
		}
		$recs=decodeJSON($out['stdout']);
		return $recs[0]['cnt'];
	}
	else{
		if(count($preloads)){
			$query=implode(PHP_EOL,$preloads).PHP_EOL.$query;
		}
		$filename='duckdb_'.sha1($query).'.sql';
		$afile="{$tpath}/{$filename}";
		$ok=setFileContents($afile,$query);
		$cmd="duckdb -json -c \".read {$afile}\"";
		if(strlen($DATABASE['_lastquery']['dbname'])){$cmd.=" \"{$DATABASE['_lastquery']['dbname']}\"";}
		$out=cmdResults($cmd);
		if(isset($out['stderr']) && strlen($out['stderr'])){
			$DATABASE['_lastquery']['error']=$out['stderr'];
			debugValue($DATABASE['_lastquery']);
    		return array();
		}
		$recs=decodeJSON($out['stdout']);
		unlink($afile);
		return $recs;
	}
}
//---------- begin function duckdbGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* @return array recordset
* @usage $rec=duckdbGetDBRecord(array('-table'=>'tesl));
*/
function duckdbGetDBRecord($params=array()){
	$recs=duckdbGetDBRecords($params);
	//echo "duckdbGetDBRecord".printValue($params).printValue($recs);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function duckdbGetDBRecords
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
*	duckdbGetDBRecords(array('-table'=>'notes'));
*	duckdbGetDBRecords("select * from myschema.mytable where ...");
*/
function duckdbGetDBRecords($params){
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
		$fields=duckdbGetDBFieldInfo($params['-table']);
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
	$recs=duckdbQueryResults($query,$params);
	//echo '<hr>'.$query.printValue($params).printValue($recs);
	return $recs;
}
//---------- begin function duckdbGetDBRecordsCount ----------
/**
* @describe retrieves records from DB based on params
* @param $params array
* 	-table 	  - table to query
* @return array recordsets
* @usage $cnt=duckdbGetDBRecordsCount(array('-table'=>'tesl));
*/
function duckdbGetDBRecordsCount($params=array()){
	$params['-fields']='count(*) cnt';
	if(isset($params['-order'])){unset($params['-order']);}
	if(isset($params['-limit'])){unset($params['-limit']);}
	if(isset($params['-offset'])){unset($params['-offset']);}
	$recs=duckdbGetDBRecords($params);
	return $recs[0]['cnt'];
}
function duckdbNamedQueryList(){
	return array(
		array(
			'code'=>'tables',
			'icon'=>'icon-table',
			'name'=>'Tables'
		)
	);
}
//---------- begin function duckdbNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function duckdbNamedQuery($name){
	$schema=duckdbGetDBSchema();
	switch(strtolower($name)){
		case 'tables':
			return <<<ENDOFQUERY
SELECT 
	table_schema AS schema,
	table_name AS name,
	table_comment AS comment 
FROM information_schema.tables 
WHERE 
	table_schema = 'main'
ORDER BY table_name
ENDOFQUERY;
		break;
	}
}
