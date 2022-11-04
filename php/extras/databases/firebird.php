<?php

/*
	firebird Database functions
		https://firebird-driver.readthedocs.io/en/latest/getting-started.html#quick-start-guide
    	https://firebirdsql.org/file/documentation/html/en/firebirddocs/qsg3/firebird-3-quickstartguide.html

		$host = 'localhost:d:/data/examples.fdb';
		$username='myuser';
		$password='mypass';
		$dbh = ibase_connect($host, $username, $password);
		echo "dbh".printValue($dbh);
		$stmt = 'SELECT CUSTOMER_ID, NAME, ADDRESS, ZIPCODE, PHONE FROM CUSTOMER offset 0 rows fetch next 5 rows only';
		$sth = ibase_query($dbh, $stmt);
		while ($row = ibase_fetch_object($sth)) {
		    echo printValue($row);
		}
		ibase_free_result($sth);
		ibase_close($dbh);


*/
//---------- begin function firebirdAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
*	[-map] array - old/new field map  'old_field'=>'new_field'
* @return count int
* @usage $ok=firebirdAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=firebirdAddDBRecords('comments',array('-recs'=>$recs);
*/
function firebirdAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("firebirdAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	$params['-table']=$table;
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("firebirdAddDBRecords Error: either -csv or -recs is required");
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("firebirdAddDBRecords Error: no such file: {$params['-csv']}");
		}
		return processCSVLines($params['-csv'],'firebirdAddDBRecordsProcess',$params);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("firebirdAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("firebirdAddDBRecords Error: no recs");
		}
		$chunks=array_chunk($params['-recs'], $params['-chunk']);
		$rtn=array();
		foreach($chunks as $chunk){
			$rtn[]=firebirdAddDBRecordsProcess($chunk,$params);
		}
		return $rtn;
	}
}
function firebirdAddDBRecordsProcess($recs,$params=array()){
	global $USER;
	if(!isset($params['-table'])){
		$err="firebirdAddDBRecordsProcess Error: no table";
		debugValue($err); 
		return $err;
	}
	$table=$params['-table'];
	$fieldinfo=firebirdGetDBFieldInfo($table,1);
	//add _cdate and _cuser if table has those fields
	if(isset($fieldinfo['_cuser'])){
		$cdate=date('Y-m-d H:i:s');
		foreach($recs as $i=>$rec){
			if(!isset($recs[$i]['_cuser'])){
				$recs[$i]['_cuser']=$USER['_id'];
			}
			if(!isset($recs[$i]['_cdate'])){
				$recs[$i]['_cdate']=$cdate;
			}
		}
	}
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
	if(isset($params['-upsert'])){
		if(!is_array($params['-upsert'])){
			$params['-upsert']=preg_split('/\,/',$params['-upsert']);
		}
	}
	if(isset($params['-ignore']) && !isset($params['-upsert'])){
		$params['-upsert']=array('ignore');
	}
	$ignore='';
	if(isset($params['-upsert'][0]) && strtolower($params['-upsert'][0])=='ignore'){
		$ignore='IGNORE';
		unset($params['-upsert']);
	}
	elseif(isset($params['-upsert'][0]) && isset($fieldinfo['_euser'])){
		//update _edate and _euser if editing the record
		$edate=date('Y-m-d H:i:s');
		$eu=in_array('_euser',$params['-upsert']);
		$ed=in_array('_edate',$params['-upsert']);
		foreach($recs as $i=>$rec){
			if($eu && !isset($recs[$i]['_euser'])){
				$recs[$i]['_euser']=$USER['_id'];
			}
			if($ed && !isset($recs[$i]['_edate'])){
				$recs[$i]['_edate']=$edate;
			}
		}
	}
	$query="INSERT {$ignore} INTO {$table} ({$fieldstr}) VALUES ".PHP_EOL;
	$values=array();
	foreach($recs as $i=>$rec){
		$vals=array();
		foreach($fields as $field){
			$val='NULL';
			if(isset($rec[$field]) && strlen($rec[$field])){
				$val=firebirdEscapeString($rec[$field]);
				switch($fieldinfo[$field]['_dbtype']){
					case 'date':
					case 'time':
					case 'datetime':
						if(preg_match('/^([a-z\_0-9]+)\(\)$/is',$val)){
							//val is a function - do not put quotes around it
						}
						else{
							$val="'{$val}'";
						}
					break;
					default:
						$val="'{$val}'";
					break;
				}
			}
			$vals[]=$val;
		}
		$values[]='('.implode(',',$vals).')';
	}
	$query.=implode(','.PHP_EOL,$values);
	if(isset($params['-upsert'][0])){
		//VALUES() to refer to the new row is deprecated with version 8.0.20+
		$version=getDBRecord("SHOW VARIABLES LIKE 'version'");
		list($v1,$v2,$v3)=preg_split('/\./',$version['value'],3);
		if((integer)$v1>8 || ((integer)$v1==8 && (integer)$v2 > 0) || ((integer)$v1==8 && (integer)$v2==0 && (integer)$v3 >=20)){
			//firebird version 8 and newer
			$query.=PHP_EOL."AS new"." ON DUPLICATE KEY UPDATE";
			$flds=array();
			foreach($params['-upsert'] as $fld){
				$flds[]="{$fld}=new.{$fld}";
			}
			$query.=PHP_EOL.implode(', ',$flds);
			if(isset($params['-upsertwhere'])){
				$query.=" WHERE {$params['-upsertwhere']}";
			}
		}
		else{
			//before firebird version 8.0.20
			$query.=PHP_EOL." ON DUPLICATE KEY UPDATE";
			$flds=array();
			foreach($params['-upsert'] as $fld){
				$flds[]="{$fld}=VALUES({$fld})";
			}
			$query.=PHP_EOL.implode(', ',$flds);
			if(isset($params['-upsertwhere'])){
				$query.=" WHERE {$params['-upsertwhere']}";
			}
		}
	}
	//echo printValue($params).$query;exit;
	$ok=firebirdExecuteSQL($query);
	//echo printValue($ok).$query;exit;
	if(isset($params['-debug'])){
		return printValue($ok).$query;
	}
	return count($values);
}
//---------- begin function firebirdGetDDL ----------
/**
* @describe returns create script for specified table
* @param type string - object type
* @param name string - object name
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=firebirdGetDDL('table','sample');
*/
function firebirdGetDDL($type,$name){
	$type=strtoupper($type);
	$name=strtoupper($name);
	$query="SHOW CREATE {$type} {$name}";
	$field='create_'.strtolower($name);
	$recs=firebirdQueryResults($query);
	//echo $query.printValue($recs);exit;
	if(isset($recs[0]['create table'])){
		return $recs[0]['create table'];
	}
	if(isset($recs[0]['create_table'])){
		return $recs[0]['create_table'];
	}
	if(isset($recs[0][$field])){
		return $recs[0][$field];
	}
	return $recs;
}
//---------- begin function firebirdGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param name string - tablename
* @return string
* @usage $createsql=firebirdGetTableDDL('sample');
*/
function firebirdGetTableDDL($name){
	return firebirdGetDDL('table',$name);
}
//---------- begin function firebirdGetFunctionDDL ----------
/**
* @describe returns create script for specified function
* @param name string - function name
* @return string
* @usage $createsql=firebirdGetFunctionDDL('sample');
*/
function firebirdGetFunctionDDL($name){
	return firebirdGetDDL('function',$name);
}
//---------- begin function firebirdGetProcedureDDL ----------
/**
* @describe returns create script for specified procedure
* @param name string - procedure name
* @return string
* @usage $createsql=firebirdGetProcedureDDL('sample');
*/
function firebirdGetProcedureDDL($name){
	return firebirdGetDDL('procedure',$name);
}
//---------- begin function firebirdGetPackageDDL ----------
/**
* @describe returns create script for specified package
* @param name string - package name
* @return string
* @usage $createsql=firebirdGetPackageDDL('sample');
*/
function firebirdGetPackageDDL($name){
	return firebirdGetDDL('package',$name);
}
//---------- begin function firebirdGetTriggerDDL ----------
/**
* @describe returns create script for specified trigger
* @param name string - trigger name
* @return string
* @usage $createsql=firebirdGetTriggerDDL('sample');
*/
function firebirdGetTriggerDDL($name){
	return firebirdGetDDL('trigger',$name);
}
//---------- begin function firebirdGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=firebirdGetAllTableFields();
*/
function firebirdGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'firebirdGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=firebirdGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('firebirdGetAllTableFields error: schema is not defined in config.xml');
		return null;
	}
	$query=<<<ENDOFQUERY
	SELECT
		rf.rdb\$relation_name as table_name,
		rf.rdb\$field_name as field_name,
		CASE f.rdb\$field_type
		 WHEN 7 THEN
		   CASE f.rdb\$field_sub_type
		     WHEN 0 THEN 'smallint'
		     WHEN 1 THEN 'numeric(' || f.rdb\$field_precision || ', ' || (-f.rdb\$field_scale) || ')'
		     WHEN 2 THEN 'decimal'
		   END
		 WHEN 8 THEN
		   CASE f.rdb\$field_sub_type
		     WHEN 0 THEN 'integer'
		     WHEN 1 THEN 'numeric('  || f.rdb\$field_precision || ', ' || (-f.rdb\$field_scale) || ')'
		     WHEN 2 THEN 'decimal'
		   END
		 WHEN 9 THEN 'quad'
		 WHEN 10 THEN 'float'
		 WHEN 12 THEN 'date'
		 WHEN 13 THEN 'time'
		 WHEN 14 THEN 'char(' || (trunc(f.rdb\$field_length / ch.rdb\$bytes_per_character)) || ') '
		 WHEN 16 THEN
		   case f.rdb\$field_sub_type
		     WHEN 0 THEN 'bigint'
		     WHEN 1 THEN 'numeric(' || f.rdb\$field_precision || ', ' || (-f.rdb\$field_scale) || ')'
		     WHEN 2 THEN 'decimal'
		   END
		 WHEN 27 THEN 'double'
		 WHEN 35 THEN 'timestamp'
		 WHEN 37 THEN 'varchar(' || (trunc(f.rdb\$field_length / ch.rdb\$bytes_per_character)) || ')'
		 WHEN 40 THEN 'cstring' || (trunc(f.rdb\$field_length / ch.rdb\$bytes_per_character)) || ')'
		 WHEN 45 THEN 'blob_id'
		 WHEN 261 THEN 'blob sub_type ' || f.rdb\$field_sub_type
		 ELSE 'rdb\$field_type: ' || f.rdb\$field_type || '?'
		END as type_name,
		iif(coalesce(rf.rdb\$null_flag, 0) = 0, null, 'not null') AS field_null,
		ch.rdb\$character_set_name as field_charset,
		dco.rdb\$collation_name as field_collation,
		coalesce(rf.rdb\$default_source, f.rdb\$default_source) AS field_default
	FROM rdb\$relation_fields rf
	JOIN rdb\$fields f on (f.rdb\$field_name = rf.rdb\$field_source)
	LEFT OUTER JOIN rdb\$character_sets ch on (ch.rdb\$character_set_id = f.rdb\$character_set_id)
	LEFT OUTER JOIN rdb\$collations dco on ((dco.rdb\$collation_id = f.rdb\$collation_id) AND (dco.rdb\$character_set_id = f.rdb\$character_set_id))
	WHERE (coalesce(rf.rdb\$system_flag, 0) = 0)
	ORDER BY 
		rf.rdb\$relation_name,
		rf.rdb\$field_position
ENDOFQUERY;
	$recs=firebirdQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		//trim values
		foreach($rec as $k=>$v){
			$rec[$k]=trim($v);
		}
		$table=strtolower($rec['table_name']);
		$field=strtolower($rec['field_name']);
		$type=strtolower($rec['type_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function firebirdGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=firebirdGetAllTableIndexes();
*/
function firebirdGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'firebirdGetAllTableIndexes');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=firebirdGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('firebirdGetAllTableIndexes error: schema is not defined in config.xml');
		return null;
	}
	//key_name,column_name,seq_in_index,non_unique
	$query=<<<ENDOFQUERY
	SELECT
	    rc.rdb\$relation_name as table_name,
	    ix.rdb\$index_name as key_name,
	    sg.rdb\$field_name as column_name,
	    CASE rc.rdb\$constraint_type WHEN 'PRIMARY KEY' THEN 1 ELSE 0 END as is_primary,
	    ix.rdb\$unique_flag as is_unique,
	    sg.rdb\$field_position as seq_in_index
	FROM
	    rdb\$indices ix
	    LEFT JOIN rdb\$index_segments sg on ix.rdb\$index_name = sg.rdb\$index_name
	    LEFT JOIN rdb\$relation_constraints rc on rc.rdb\$index_name = ix.rdb\$index_name
	ORDER BY 1,2,6
ENDOFQUERY;
	$recs=firebirdQueryResults($query);
	//group the key_names
	$xrecs=array();
	foreach($recs as $rec){
		//trim values
		foreach($rec as $k=>$v){
			$rec[$k]=trim($v);
		}
		$ikey=strtolower($rec['table_name']).strtolower($rec['key_name']);
		if(!isset($xrecs[$ikey])){
			$rec['column_names']=array($rec['column_name']);
			$xrecs[$ikey]=$rec;
		}
		else{
			$xrecs[$ikey]['column_names'][]=$rec['column_name'];
		}
	}
	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>'.printValue($recs);exit;
	$databaseCache[$cachekey]=array();
	foreach($xrecs as $ikey=>$rec){
		$rec['column_names']=json_encode($rec['column_names']);
		$table=$rec['table_name'];
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function firebirdGetDBTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=firebirdGetAllTableIndexes();
*/
function firebirdGetDBTableIndexes($tablename=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'firebirdGetDBTableIndexes'.$tablename);
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	$tablename=strtoupper($tablename);
	//key_name,column_name,is_primary,is_unique,seq_in_index
	$query=<<<ENDOFQUERY
	SELECT
	    rc.rdb\$relation_name as table_name,
	    ix.rdb\$index_name as key_name,
	    sg.rdb\$field_name as column_name,
	    CASE rc.rdb\$constraint_type WHEN 'PRIMARY KEY' THEN 1 ELSE 0 END as is_primary,
	    ix.rdb\$unique_flag as is_unique,
	    sg.rdb\$field_position as seq_in_index
	FROM
	    rdb\$indices ix
	    LEFT JOIN rdb\$index_segments sg on ix.rdb\$index_name = sg.rdb\$index_name
	    LEFT JOIN rdb\$relation_constraints rc on rc.rdb\$index_name = ix.rdb\$index_name
	WHERE
	    rc.rdb\$relation_name='{$tablename}'
	ORDER BY 2,6
ENDOFQUERY;
	$recs=firebirdQueryResults($query);
	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>'.printValue($recs);exit;
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		foreach($rec as $k=>$v){
			$rec[$k]=trim($v);
		}
		$databaseCache[$cachekey][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function firebirdGetAllProcedures ----------
/**
* @describe returns all procedures in said schema
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allprocedures=firebirdGetAllProcedures();
*/
function firebirdGetAllProcedures($dbname=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'firebirdGetAllProcedures');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($dbname)){
		$dbname=firebirdGetDBName();
	}
	if(!strlen($dbname)){
		debugValue('firebirdGetAllProcedures error: dbname is not defined in config.xml');
		return null;
	}
	$query=<<<ENDOFQUERY
SELECT 
    r.routine_name as object_name
    ,r.routine_type as object_type
    ,MD5(r.routine_definition) as hash
    ,group_concat( distinct p.parameter_name ORDER BY p.parameter_name SEPARATOR ', ')  args

FROM information_schema.routines r
   LEFT OUTER JOIN information_schema.parameters p
      on p.specific_name=r.specific_name and p.parameter_mode='IN'
WHERE r.routine_schema='{$dbname}'
GROUP BY
	r.routine_name,
	r.routine_type,
	MD5(r.routine_definition)
ENDOFQUERY;
	$recs=firebirdQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $i=>$rec){
		$key=$rec['object_name'].$rec['object_type'];
		$rec['overload']='';
		$databaseCache[$cachekey][$key][]=$rec;
	}
	//echo $query.printValue($recs);exit;
	return $databaseCache[$cachekey];
}

function firebirdGetDBSchema(){
	global $CONFIG;
	global $DATABASE;
	$params=firebirdParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	elseif(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['firebird_dbschema'])){return $CONFIG['firebird_dbschema'];}
	elseif(isset($CONFIG['firebird_schema'])){return $CONFIG['firebird_schema'];}
	return '';
}
function firebirdGetDBName(){
	global $CONFIG;
	global $DATABASE;
	$params=firebirdParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbname'])){
		return $DATABASE[$CONFIG['db']]['dbname'];
	}
	elseif(isset($CONFIG['dbname'])){return $CONFIG['dbname'];}
	elseif(isset($CONFIG['-dbname'])){return $CONFIG['-dbname'];}
	elseif(isset($CONFIG['firebird_dbname'])){return $CONFIG['firebird_dbname'];}
	return '';
}
//---------- begin function firebirdGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=firebirdGetDBRecordById('comments',7);
*/
function firebirdGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "firebirdGetDBRecordById Error: No Table";}
	if($id == 0){return "firebirdGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=firebirdGetDBRecord($recopts);
	return $rec;
}
//---------- begin function firebirdEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=firebirdEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function firebirdEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("firebirdEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("firebirdEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("firebirdEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("firebirdEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return firebirdEditDBRecord($params);
}
//---------- begin function firebirdDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=firebirdDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function firebirdDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("firebirdDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("firebirdDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return firebirdDelDBRecord($params);
}
//---------- begin function firebirdListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function firebirdListRecords($params=array()){
	$params['-database']='firebird';
	return databaseListRecords($params);
}
//---------- begin function firebirdParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_firebird,dbuser_firebird, and dbpass_firebird
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=firebirdParseConnectParams($params);
*/
function firebirdParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^firebird/i',$k)){unset($CONFIG[$k]);}
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
	if(isfirebird()){
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
		if(isset($CONFIG['dbhost_firebird'])){
			$params['-dbhost']=$CONFIG['dbhost_firebird'];
			//$params['-dbhost_source']="CONFIG dbhost_firebird";
		}
		elseif(isset($CONFIG['firebird_dbhost'])){
			$params['-dbhost']=$CONFIG['firebird_dbhost'];
			//$params['-dbhost_source']="CONFIG firebird_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		//$params['-dbhost_source']="passed in";
	}
	$CONFIG['firebird_dbhost']=$params['-dbhost'];
	
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_firebird'])){
			$params['-dbuser']=$CONFIG['dbuser_firebird'];
			//$params['-dbuser_source']="CONFIG dbuser_firebird";
		}
		elseif(isset($CONFIG['firebird_dbuser'])){
			$params['-dbuser']=$CONFIG['firebird_dbuser'];
			//$params['-dbuser_source']="CONFIG firebird_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['firebird_dbuser']=$params['-dbuser'];
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_firebird'])){
			$params['-dbpass']=$CONFIG['dbpass_firebird'];
			//$params['-dbpass_source']="CONFIG dbpass_firebird";
		}
		elseif(isset($CONFIG['firebird_dbpass'])){
			$params['-dbpass']=$CONFIG['firebird_dbpass'];
			//$params['-dbpass_source']="CONFIG firebird_dbpass";
		}
	}
	else{
		//$params['-dbpass_source']="passed in";
	}
	$CONFIG['firebird_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_firebird'])){
			$params['-dbname']=$CONFIG['dbname_firebird'];
			//$params['-dbname_source']="CONFIG dbname_firebird";
		}
		elseif(isset($CONFIG['firebird_dbname'])){
			$params['-dbname']=$CONFIG['firebird_dbname'];
			//$params['-dbname_source']="CONFIG firebird_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['firebird_dbname'];
			//$params['-dbname_source']="set to username";
		}
	}
	else{
		//$params['-dbname_source']="passed in";
	}
	$CONFIG['firebird_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_firebird'])){
			$params['-dbport']=$CONFIG['dbport_firebird'];
			//$params['-dbport_source']="CONFIG dbport_firebird";
		}
		elseif(isset($CONFIG['firebird_dbport'])){
			$params['-dbport']=$CONFIG['firebird_dbport'];
			//$params['-dbport_source']="CONFIG firebird_dbport";
		}
		else{
			$params['-dbport']=5432;
			//$params['-dbport_source']="default port";
		}
	}
	else{
		//$params['-dbport_source']="passed in";
	}
	$CONFIG['firebird_dbport']=$params['-dbport'];
	//dbschema
	if(!isset($params['-dbschema'])){
		if(isset($CONFIG['dbschema_firebird'])){
			$params['-dbschema']=$CONFIG['dbschema_firebird'];
			//$params['-dbuser_source']="CONFIG dbuser_firebird";
		}
		elseif(isset($CONFIG['firebird_dbschema'])){
			$params['-dbschema']=$CONFIG['firebird_dbschema'];
			//$params['-dbuser_source']="CONFIG firebird_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['firebird_dbschema']=$params['-dbschema'];
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['firebird_connect'])){
			$params['-connect']=$CONFIG['firebird_connect'];
			//$params['-connect_source']="CONFIG firebird_connect";
		}
		elseif(isset($CONFIG['connect_firebird'])){
			$params['-connect']=$CONFIG['connect_firebird'];
			//$params['-connect_source']="CONFIG connect_firebird";
		}
		else{
			//build connect - http://php.net/manual/en/function.pg-connect.php
			//$conn_string = "host=sheep port=5432 dbname=test user=lamb password=bar";
			//echo printValue($CONFIG);exit;
			$params['-connect']="host={$CONFIG['firebird_dbhost']} port={$CONFIG['firebird_dbport']} dbname={$CONFIG['firebird_dbname']} user={$CONFIG['firebird_dbuser']} password={$CONFIG['firebird_dbpass']}";
			//$params['-connect_source']="manual";
		}
		//add application_name
		if(!stringContains($params['-connect'],'options')){
			if(isset($params['-application_name'])){
				$appname=$params['-application_name'];
			}
			elseif(isset($CONFIG['firebird_application_name'])){
				$appname=$CONFIG['firebird_application_name'];
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
//---------- begin function firebirdDBConnect ----------
/**
* @describe connects to a firebird database and returns the handle resource
* @param $param array - These can also be set in the CONFIG file with dbname_firebird,dbuser_firebird, and dbpass_firebird
* 	[-dbname] - name of ODBC connection
*   [-single] - if you pass in -single it will connect using firebird_connect instead of firebird_pconnect and return the connection
* @return $dbh_firebird resource - returns the firebird connection resource
* @usage $dbh_firebird=firebirdDBConnect($params);
* @usage  example of using -single
*
	$conn=firebirdDBConnect(array('-single'=>1));
	firebird_autocommit($conn, FALSE);

	firebird_exec($conn, $query1);
	firebird_exec($conn, $query2);

	if (!firebird_error()){
		firebird_commit($conn);
	}
	else{
		firebird_rollback($conn);
	}
	firebird_close($conn);
*
*/
function firebirdDBConnect($params=array()){
	global $CONFIG;
	$params=firebirdParseConnectParams($params);
	global $dbh_firebird;
	if($dbh_firebird){return $dbh_firebird;}
	try{
		$dbh_firebird = ibase_connect($params['-connect'],$params['-dbuser'],$params['-dbpass']);

		if(!is_resource($dbh_firebird)){
			$err=@ibase_errmsg();
			echo "firebirdDBConnect error:{$err}".printValue($params);
			exit;

		}
		return $dbh_firebird;
	}
	catch (Exception $e) {
		echo "dbh_firebird exception" . printValue($e);
		exit;
	}
}
//---------- begin function firebirdExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_firebird,dbuser_firebird, and dbpass_firebird
*	[-host] - firebird server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage $ok=firebirdExecuteSQL("truncate table abc");
*/
function firebirdExecuteSQL($query,$params=array()){
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'firebirdExecuteSQL',
		'params'=>$params
	);
	$query=trim($query);
	global $USER;
	global $dbh_firebird;
	$dbh_firebird='';
	$dbh_firebird=firebirdDBConnect();
	if(!$dbh_firebird){
		$DATABASE['_lastquery']['error']=ibase_errmsg();
		debugValue($DATABASE['_lastquery']);
    	return array();
	}
	$result=@ibase_query($dbh_firebird,$query);
	$err=ibase_errmsg();
	if(is_array($err) || strlen($err)){
		ibase_close($dbh_firebird);
		$DATABASE['_lastquery']['error']=$err;
		debugValue($DATABASE['_lastquery']);
    	return array();
	}
	if(preg_match('/^insert /i',$query) && !stringContains($query,' returning ')){
    	//return the id inserted on insert statements
    	$id=databaseAffectedRows($result);
    	ibase_close($dbh_firebird);
    	$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
    	return $id;
	}
	$results = firebirdEnumQueryResults($result,$params);
	ibase_close($dbh_firebird);
	$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
	return true;
}
//---------- begin function firebirdGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage $cnt=firebirdGetDBCount(array('-table'=>'states'));
*/
function firebirdGetDBCount($params=array()){
	global $CONFIG;
	global $DATABASE;
	$dbname=strtoupper($DATABASE[$CONFIG['db']]['dbname']);
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$params['-queryonly']=1;
	$query=firebirdGetDBRecords($params);
	//echo "HERE".$query.printValue($params);exit;
	if(!stringContains($query,'where')){
	 	$query="select table_rows from information_schema.tables where table_schema='{$dbname}' and table_name='{$params['-table']}'";
	 	$recs=getDBRecords(array('-query'=>$query,'-nolog'=>1));
	 	if(isset($recs[0]['table_rows']) && isNum($recs[0]['table_rows'])){
	 		return (integer)$recs[0]['table_rows'];
	 	}
	}
	$recs=firebirdQueryResults($query);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function firebirdGetDBFieldInfo--------------------
/**
* @describe returns an array containing type,length, and flags for each field in said table
* @param table string - table name
* @param [getmeta] boolean - if true returns info in _fielddata table for these fields - defaults to false
* @param [field] string - if this has a value return only this field - defaults to blank
* @param [getmeta] boolean - if true forces a refresh - defaults to false
* @return array
* @usage $fields=firebirdGetDBFieldInfo('notes');
*/
function firebirdGetDBFieldInfo($table=''){
	$table=strtolower($table);
	$query=<<<ENDOFQUERY
	SELECT
		RF.RDB\$RELATION_NAME as table_name,
		RF.RDB\$FIELD_NAME as field_name,
		CASE F.RDB\$FIELD_TYPE
		 WHEN 7 THEN
		   CASE F.RDB\$FIELD_SUB_TYPE
		     WHEN 0 THEN 'SMALLINT'
		     WHEN 1 THEN 'NUMERIC(' || F.RDB\$FIELD_PRECISION || ', ' || (-F.RDB\$FIELD_SCALE) || ')'
		     WHEN 2 THEN 'DECIMAL'
		   END
		 WHEN 8 THEN
		   CASE F.RDB\$FIELD_SUB_TYPE
		     WHEN 0 THEN 'INTEGER'
		     WHEN 1 THEN 'NUMERIC('  || F.RDB\$FIELD_PRECISION || ', ' || (-F.RDB\$FIELD_SCALE) || ')'
		     WHEN 2 THEN 'DECIMAL'
		   END
		 WHEN 9 THEN 'QUAD'
		 WHEN 10 THEN 'FLOAT'
		 WHEN 12 THEN 'DATE'
		 WHEN 13 THEN 'TIME'
		 WHEN 14 THEN 'CHAR(' || (TRUNC(F.RDB\$FIELD_LENGTH / CH.RDB\$BYTES_PER_CHARACTER)) || ') '
		 WHEN 16 THEN
		   CASE F.RDB\$FIELD_SUB_TYPE
		     WHEN 0 THEN 'BIGINT'
		     WHEN 1 THEN 'NUMERIC(' || F.RDB\$FIELD_PRECISION || ', ' || (-F.RDB\$FIELD_SCALE) || ')'
		     WHEN 2 THEN 'DECIMAL'
		   END
		 WHEN 27 THEN 'DOUBLE'
		 WHEN 35 THEN 'TIMESTAMP'
		 WHEN 37 THEN 'VARCHAR(' || (TRUNC(F.RDB\$FIELD_LENGTH / CH.RDB\$BYTES_PER_CHARACTER)) || ')'
		 WHEN 40 THEN 'CSTRING' || (TRUNC(F.RDB\$FIELD_LENGTH / CH.RDB\$BYTES_PER_CHARACTER)) || ')'
		 WHEN 45 THEN 'BLOB_ID'
		 WHEN 261 THEN 'BLOB SUB_TYPE ' || F.RDB\$FIELD_SUB_TYPE
		 ELSE 'RDB\$FIELD_TYPE: ' || F.RDB\$FIELD_TYPE || '?'
		END as type_name,
		IIF(COALESCE(RF.RDB\$NULL_FLAG, 0) = 0, NULL, 'NOT NULL') as field_null,
		CH.RDB\$CHARACTER_SET_NAME as field_charset,
		DCO.RDB\$COLLATION_NAME as field_collation,
		COALESCE(RF.RDB\$DEFAULT_SOURCE, F.RDB\$DEFAULT_SOURCE) as field_default
	FROM RDB\$RELATION_FIELDS RF
	JOIN RDB\$FIELDS F ON (F.RDB\$FIELD_NAME = RF.RDB\$FIELD_SOURCE)
	LEFT OUTER JOIN RDB\$CHARACTER_SETS CH ON (CH.RDB\$CHARACTER_SET_ID = F.RDB\$CHARACTER_SET_ID)
	LEFT OUTER JOIN RDB\$COLLATIONS DCO ON ((DCO.RDB\$COLLATION_ID = F.RDB\$COLLATION_ID) AND (DCO.RDB\$CHARACTER_SET_ID = F.RDB\$CHARACTER_SET_ID))
	WHERE lower(RF.RDB\$RELATION_NAME)='{$table}' and (COALESCE(RF.RDB\$SYSTEM_FLAG, 0) = 0)
	ORDER BY 
		RF.RDB\$RELATION_NAME,
		RF.RDB\$FIELD_POSITION
ENDOFQUERY;
	$recs=firebirdQueryResults($query);
	//echo $query.printValue($recs);exit;
	$info=array();
	foreach($recs as $i=>$rec){
		//trim values
		foreach($rec as $k=>$v){
			$rec[$k]=trim($v);
		}
		$key=strtolower($rec['field_name']);
		$info[$key]['_dbfield']=$rec['field_name'];
		$info[$key]['name']=$rec['field_name'];
		$info[$key]['_dbnull']=$rec['field_null'];
		$info[$key]['_dbprivileges']='';
		$info[$key]['_dbtablename']=$table;
		$info[$key]['_dbtable']=$table;
		$info[$key]['table']=$table;
		if(preg_match('/^(.+?)\((.+)\)$/',$rec['type_name'],$m)){
			$info[$key]['type']=$info[$key]['_dbtype']=$m[1];
			list($len,$dec)=preg_split('/\,/',$m[2]);
			$info[$key]['length']=$recs[$key]['_dblength']=$len;
			$info[$key]['_dbtype_ex']=$rec['type_name'];
		}
		else{
			$info[$key]['type']=$info[$key]['_dbtype']=$info[$key]['_dbtype_ex']=$rec['type_name'];
		}
		 //default
		 if(strlen($rec['field_default'])){
		 	$info[$key]['_dbdef']=$info[$key]['default']=$rec['field_default'];
		 	if(isNum($rec['field_default'])){
		 		$info[$key]['_dbtype_ex'] .= " Default {$rec['field_default']}";
		 	}
		 	else{
		 		$info[$key]['_dbtype_ex'] .= " Default '{$rec['field_default']}'";
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
function firebirdGetDBExpression($table,$field,$schema=''){
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
	$rec=firebirdQueryResults($query);
	if(!isset($rec['exp'])){return '';}
	$rec['exp']=stripslashes($rec['exp']);
	return $rec['exp'];
}
//---------- begin function firebirdGetDBQuery--------------------
/**
* @describe builds a database query based on params
* @param params array
*	[-table] string - table to query
*	[-notimestamp] - turn off building _utime fields for date and time fields
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return string - query string
* @usage $query=firebirdGetDBQuery(array('-table'=>$table,'field1'=>$val1...));
*/
function firebirdGetDBQuery($params=array()){
	if(!isset($params['-table'])){return 'getDBQuery Error: No table' . printValue($params);}
	//get field info for this table
	$info=firebirdGetDBFieldInfo($params['-table']);
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
	$where = firebirdGetDBWhere($params);
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
//---------- begin function firebirdGetDBRecords
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
*	<?=firebirdGetDBRecords(array('-table'=>'notes'));?>
*	<?=firebirdGetDBRecords("select * from myschema.mytable where ...");?>
*/
function firebirdGetDBRecords($params){
	global $USER;
	global $CONFIG;
	//echo "firebirdGetDBRecords".printValue($params);exit;
	if(empty($params['-table']) && !is_array($params)){
		$params=trim($params);
		if(preg_match('/^(desc|select|exec|with|explain|returning|show|call)[\t\s\ \r\n]/i',$params)){
			//they just entered a query
			$query=$params;
			$params=array();
			return firebirdQueryResults($query,$params);
		}
		else{
			$ok=firebirdExecuteSQL($params);
			return $ok;
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
		return firebirdQueryResults($query,$params);
	}
	else{
		if(empty($params['-table'])){
			debugValue(array(
				'function'=>'firebirdGetDBRecords',
				'message'=>'no table',
				'params'=>$params
			));
	    	return null;
		}
		$query=firebirdGetDBQuery($params);
		if(isset($params['-debug'])){return $query;}
		if(isset($params['-queryonly'])){return $query;}
		return firebirdQueryResults($query,$params);
	}
}
//---------- begin function firebirdGetDBWhere--------------------
/**
* @describe builds a database query where clause only
* @param params array
*	[-table] string - table to query
*	[-notimestamp] - turn off building _utime fields for date and time fields
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return string - query where string
* @usage $where=firebirdGetDBWhere(array('-table'=>$table,'field1'=>$val1...));
*/
function firebirdGetDBWhere($params,$info=array()){
	if(!isset($info) || !count($info)){
		$info=firebirdGetDBFieldInfo($params['-table']);
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
        $v=firebirdEscapeString($params[$k]);
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
function firebirdEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function firebirdIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_firebird,dbuser_firebird, and dbpass_firebird
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(firebirdIsDBTable('abc')){...}
*/
function firebirdIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "firebirdIsDBTable error: No table";
		return null;
	}
	$table=strtolower($table);
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$query="SELECT name FROM {$parts[0]}.firebird_master WHERE type='table' and name = '{$table}'";
		$table=$parts[1];
	}
	else{
		$query="SELECT name FROM firebird_master WHERE type='table' and name = '{$table}'";
	}
	$recs=firebirdQueryResults($query);
	if(isset($recs[0]['name'])){return true;}
	return false;
}
//---------- begin function firebirdGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_firebird,dbuser_firebird, and dbpass_firebird
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of table names
* @usage $tables=firebirdGetDBTables();
*/
function firebirdGetDBTables($params=array()){
	global $CONFIG;
	global $DATABASE;
	if(!isset($CONFIG['db']) && isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$CONFIG['db']=$_REQUEST['db'];
	}
	$dbname=strtolower($DATABASE[$CONFIG['db']]['dbname']);
	$tables=array();
	$query=<<<ENDOFQUERY
		SELECT a.RDB\$RELATION_NAME as name
		FROM RDB\$RELATIONS a
		WHERE 
			COALESCE(RDB\$SYSTEM_FLAG, 0) = 0 AND RDB\$RELATION_TYPE = 0
ENDOFQUERY;
	$recs=firebirdQueryResults($query);
	$k="name";
	foreach($recs as $rec){
		$tables[]=strtolower(trim($rec[$k]));
	}
	return $tables;
}
//---------- begin function firebirdGetDBViews ----------
/**
* @describe returns an array of views
* @return array returns array of table views
* @usage $views=firebirdGetDBViews();
*/
function firebirdGetDBViews($params=array()){
	global $CONFIG;
	global $DATABASE;
	if(!isset($CONFIG['db']) && isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$CONFIG['db']=$_REQUEST['db'];
	}
	$dbname=strtolower($DATABASE[$CONFIG['db']]['dbname']);
	$tables=array();
	$query="show FULL tables FROM {$dbname} WHERE TABLE_TYPE = 'VIEW'";
	$recs=firebirdQueryResults($query);
	$k="tables_in_{$dbname}";
	foreach($recs as $rec){
		$tables[]=strtolower(trim($rec[$k]));
	}
	return $tables;
}
//---------- begin function firebirdQueryResults ----------
/**
* @describe returns the firebird record set
* @param query string - SQL query to execute
* @param [$params] array
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return array - returns records

*/
function firebirdQueryResults($query='',$params=array()){
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'firebirdExecuteSQL',
		'params'=>$params
	);
	$query=trim($query);
	global $USER;
	global $dbh_firebird;
	$dbh_firebird='';
	$dbh_firebird=firebirdDBConnect();
	if(!$dbh_firebird){
		$DATABASE['_lastquery']['error']='connect failed: '.ibase_errmsg();
		debugValue($DATABASE['_lastquery']);
		return array();
	}
	$result=@ibase_query($dbh_firebird,$query);
	$err=ibase_errmsg();
	if(is_array($err) || strlen($err)){
		ibase_close($dbh_firebird);
		$DATABASE['_lastquery']['error']=$err;
		debugValue($DATABASE['_lastquery']);
		return array();
	}
	if(preg_match('/^insert /i',$query) && !stringContains($query,' returning ')){
    	//return the id inserted on insert statements
    	$id=databaseAffectedRows($result);
    	ibase_close($dbh_firebird);
    	$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
    	return $id;
	}
	$results = firebirdEnumQueryResults($result,$params);
	ibase_close($dbh_firebird);
	$DATABASE['_lastquery']['stop']=microtime(true);
	$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
	return $results;
}
//---------- begin function firebirdEnumQueryResults ----------
/**
* @describe enumerates through the data from a ibase_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function firebirdEnumQueryResults($data,$params=array()){
	global $firebirdStopProcess;
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
			ibase_free_result($result);
			return 'firebirdEnumQueryResults error: Failed to open '.$params['-filename'];
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
	while ($row = @ibase_fetch_assoc($data)){
		//check for firebirdStopProcess request
		if(isset($firebirdStopProcess) && $firebirdStopProcess==1){
			break;
		}
		$rec=array();
		foreach($row as $key=>$val){
			$key=strtolower($key);
			$rec[$key]=$val;
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

//---------- begin function firebirdNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function firebirdNamedQuery($name){
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
SELECT * FROM RDB\$TRANSACTIONS
ENDOFQUERY;
		break;
		case 'sessions':
			return <<<ENDOFQUERY

ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY

ENDOFQUERY;
		break;
		case 'functions':
			return <<<ENDOFQUERY
SELECT * 
FROM RDB\$FUNCTIONS 
WHERE RDB\$SYSTEM_FLAG = 0
ENDOFQUERY;
		break;
		case 'procedures':
			return <<<ENDOFQUERY
SELECT 
	rdb\$procedure_id as id
	,rdb\$procedure_name as name
	,rdb\$procedure_inputs as inputs
	,rdb\$procedure_outputs as outputs
	,rdb\$description as description
	,rdb\$owner_name as owner
FROM rdb\$procedures 
WHERE rdb\$system_flag = 0
ENDOFQUERY;
		break;
		case 'tables':
			return <<<ENDOFQUERY
SELECT lower(rdb\$relation_name) as name
FROM rdb\$relations
WHERE 
	rdb\$view_blr is null
	AND (rdb\$system_flag is null or rdb\$system_flag = 0)
ENDOFQUERY;
		break;
		case 'triggers':
			return <<<ENDOFQUERY
SELECT * 
FROM RDB\$TRIGGERS 
WHERE RDB\$SYSTEM_FLAG = 0
ENDOFQUERY;
		break;
		case 'views':
			return <<<ENDOFQUERY
SELECT rdb\$relation_name
FROM rdb\$relations
WHERE 
	rdb\$view_blr is not null
	and (rdb\$system_flag is null or rdb\$system_flag = 0)
ENDOFQUERY;
		break;
	}
}
/*
	https://github.com/acropia/firebird-Tuner-PHP/blob/master/mt.php

*/
function firebirdOptimizations($params=array()){
	$results=array();
	//version
	$recs=firebirdQueryResults('SELECT version() as val');
	$results['version']=$recs[0]['val'];
	//get status
	$recs=firebirdQueryResults("show global status");
	foreach($recs as $rec){
		$key=strtolower($rec['variable_name']);
		$results[$key]=strtolower($rec['value']);
	}
	//variables
	$recs=firebirdQueryResults("show global variables");
	foreach($recs as $rec){
		$key=strtolower($rec['variable_name']);
		$results[$key]=strtolower($rec['value']);
	}
	$results['avg_qps']=$results['questions']/$results['uptime'];
	$results['slow_queries_pcnt']=$results['slow_queries']/$results['questions'];
	$results['thread_cache_hit_rate']=100 - (($results['threads_created']/$results['connections'])*100);
	$results['aborted_connects_pcnt']=$results['aborted_connects']/$results['connections'];
	//engines
	$recs=firebirdQueryResults("SELECT Engine, Support, Comment, Transactions, XA, Savepoints FROM information_schema.ENGINES ORDER BY Engine ASC");
	foreach($recs as $rec){
		$key=strtolower($rec['engine']);
		$xrecs=firebirdQueryResults("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_type = 'BASE TABLE' and ENGINE = '{$rec['engine']}'");
		$rec['count']=$xrecs[0]['count'];
		$results['engines'][$key]=$rec;
	}
	//aria
	$recs=firebirdQueryResults("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS val FROM information_schema.TABLES WHERE ENGINE='Aria'");
	$results['aria_index_length']=(integer)$recs[0]['val'];
	if(isset($results['aria_pagecache_read_requests'])){
		$results['aria_keys_from_memory_pcnt']=(integer)(100 - (($results['aria_pagecache_reads']/$results['aria_pagecache_read_requests'])*100));
	}
	//innoDB
	$results['innodb_buffer_pool_read_ratio']=$results['innodb_buffer_pool_reads'] * 100 / $results['innodb_buffer_pool_read_requests'];
	$recs=firebirdQueryResults("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS val from information_schema.TABLES where ENGINE='InnoDB'");
	$results['innodb_index_length']=(integer)$recs[0]['val'];
	$recs=firebirdQueryResults("SELECT IFNULL(SUM(DATA_LENGTH),0) AS data_length from information_schema.TABLES where ENGINE='InnoDB'");
	$results['innodb_data_length']=(integer)$recs[0]['val'];
	if($results['innodb_index_length'] > 0){
		$results['innodb_buffer_pool_free_pcnt']=$results['innodb_buffer_pool_pages_free']/$results['innodb_buffer_pool_pages_total'];
	}
	if(!isset($results['log_bin']) || $results['log_bin']!='on'){
		$results['binlog_cache_size']=0;
	}
	if($results['max_heap_table_size'] < $results['tmp_table_size']){
		$results['effective_tmp_table_size']=(integer)$results['max_heap_table_size'];
	}
	else{
		$results['effective_tmp_table_size']=$results['tmp_table_size'];
	}
	$results['per_thread_buffer_size']=$results['read_buffer_size']+$results['read_rnd_buffer_size']+$results['sort_buffer_size']+$results['thread_stack']+$results['net_buffer_length']+$results['join_buffer_size']+$results['binlog_cache_size'];
	$results['per_thread_buffers']=$results['per_thread_buffer_size']*$results['max_connections'];
	$results['per_thread_max_buffers']=$results['per_thread_buffer_size']*$results['max_used_connections'];
	$results['innodb_buffer_pool_size']=(integer)$results['innodb_buffer_pool_size'];
	$results['innodb_additional_mem_pool_size']=(integer)$results['innodb_additional_mem_pool_size'];
	$results['innodb_log_buffer_size']=(integer)$results['innodb_log_buffer_size'];
	$results['query_cache_size']=(integer)$results['query_cache_size'];
	$results['global_buffer_size']=$results['tmp_table_size']+$results['innodb_buffer_pool_size']+$results['innodb_additional_mem_pool_size']+$results['innodb_log_buffer_size']+$results['key_buffer_size']+$results['query_cache_size']+$results['aria_pagecache_buffer_size'];
	//max_memory
	$results['max_memory']=$results['global_buffer_size']+$results['per_thread_max_buffers'];
	//total_memory
	$results['total_memory']=$results['global_buffer_size']+$results['per_thread_buffers'];
	//key buffer size
	if((integer)$results['key_reads']==0){
		$results['key_cache_miss_rate']=0;
		$results['key_buffer_free_pcnt']=$results['key_blocks_unused']*$results['key_cache_block_size']/$results['key_buffer_size']*100;
		$results['key_buffer_used_pcnt']=100-$results['key_buffer_free_pcnt'];
		$results['key_buffer_used']=$results['key_buffer_size']-(($results['key_buffer_size']/100)*$results['key_buffer_free_pcnt']);
	}
	else{
		$results['key_cache_miss_rate']=$results['key_read_requests']/$results['key_reads'];
		if(!empty($results['key_blocks_unused'])){
			$results['key_buffer_free_pcnt']=$results['key_blocks_unused']*$results['key_cache_block_size']/$results['key_buffer_size']*100;
			$results['key_buffer_used_pcnt']=100-$results['key_buffer_free_pcnt'];
			$results['key_buffer_used']=$results['key_buffer_size']-(($results['key_buffer_size']/100)*$results['key_buffer_free_pcnt']);
		}
		else{
			$results['key_buffer_free_pcnt']='unknown';
		}
	}
	/* MyISAM Index Length */
	$recs=firebirdQueryResults("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS val FROM information_schema.TABLES WHERE ENGINE='MyISAM'");
	$results['myisam_index_length']=$recs[0]['val'];
	
	echo printValue($results);exit;
	$recs=array();
	//order by priority
	$recs=sortArrayByKeys($recs,array('priority'=>SORT_ASC));
	return $recs;
}