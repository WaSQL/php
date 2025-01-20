<?php

/*
	mysql Database functions
		https://dev.mysql.com/doc/refman/8.0/en/
		https://www.php.net/manual/en/ref.mysql.php
*/

//---------- begin function mysqlAddDBFields--------------------
/**
* @describe adds fields to given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return array - name,type,query,result for each field set
* @usage
*	$ok=mysqlAddDBFields('comments',array('comment'=>"varchar(1000) NULL"));
*/
function mysqlAddDBFields($table,$fields=array(),$maintain_order=1){
	$recs=array();
	foreach($fields as $name=>$type){
		$crec=array('name'=>$name,'type'=>$type);
		$fieldstr="{$name} {$type}";
		$crec['query']="ALTER TABLE {$table} ADD ({$fieldstr})";
		$crec['result']=mysqlExecuteSQL($crec['query']);
		$recs[]=$crec;
	}
	return $recs;
}
//---------- begin function mysqlDropDBFields--------------------
/**
* @describe drops fields to given table
* @param table string - name of table to alter
* @param params array - list of fields
* @return array - name,query,result for each field
* @usage
*	$ok=mysqlDropDBFields('comments',array('comment','age'));
*/
function mysqlDropDBFields($table,$fields=array()){
	$recs=array();
	foreach($fields as $name){
		$crec=array('name'=>$name);
		$crec['query']="ALTER TABLE {$table} DROP ({$name})";
		$crec['result']=mysqlExecuteSQL($crec['query']);
		$recs[]=$crec;
	}
	return $recs;
}		
//---------- begin function mysqlAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
*	[-map] array - old/new field map  'old_field'=>'new_field'
* @return count int
* @usage $ok=mysqlAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=mysqlAddDBRecords('comments',array('-recs'=>$recs);
*/
function mysqlAddDBRecords($table='',$params=array()){
	global $mysqlAddDBRecordsArr;
	global $mysqlAddDBRecordsResults;
	$mysqlAddDBRecordsArr=array();
	$mysqlAddDBRecordsResults=array();

	if(!commonStrlen($table)){
		return debugValue("mysqlAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	$params['-table']=$table;
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		$mysqlAddDBRecordsResults['errors'][]="mysqlAddDBRecords Error: either -csv or -recs is required";
		debugValue($mysqlAddDBRecordsResults['errors']);
		$DATABASE['_lastquery']['error']='query error: mysqlAddDBRecords Error: either -csv or -recs is required';
		return 0;
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			$err="mysqlAddDBRecords Error: no such file: {$params['-csv']}";
			debugValue($err);
		return $err;
		}
		return processCSVLines($params['-csv'],'mysqlAddDBRecordsProcess',$params);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			$err="mysqlAddDBRecords Error: no recs";
			debugValue($err);
			return $err;
		}
		elseif(!count($params['-recs'])){
			$err="mysqlAddDBRecords Error: no recs";
			debugValue($err);
			return $err;
		}
		return mysqlAddDBRecordsProcess($params['-recs'],$params);
	}
}
function mysqlAddDBRecordsProcess($recs,$params=array()){
	global $USER;
	global $dbh_mysql;
	global $DATABASE;
	global $mysqlAddDBRecordsResults;
	if(!isset($params['-table'])){
		debugValue("mysqlAddDBRecordsProcess Error: no table"); 
		return 0;
	}
	if(!is_array($recs) || !count($recs)){
		debugValue("mysqlAddDBRecordsProcess Error: recs is empty"); 
		return 0;
	}
	$table=$params['-table'];
	if(isset($params['-fieldinfo']) && is_array($params['-fieldinfo'])){
		$fieldinfo=$params['-fieldinfo'];
	}
	else{
		$tries=0;
		while($tries < 10){
			$fieldinfo=mysqlGetDBFieldInfo($table,1);
			if(is_array($fieldinfo) && count($fieldinfo)){
				break;
			}
			$tries+=1;
			sleep(5);	
		}
	}
	if(!is_array($fieldinfo) || !count(($fieldinfo))){
		debugValue(array(
			'function'=>'mysqlAddDBRecordsProcess',
			'message'=>'No fieldinfo'
		));
		return 0;
	}
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
	//ignore or upsert?
	$ignore='';
	if(isset($params['-upsert'])){
		if(!is_array($params['-upsert'])){
			if(strtolower($params['-upsert'])=='ignore'){
				$ignore=' IGNORE';
				unset($params['-upsert']);
			}
			else{
				$params['-upsert']=preg_split('/\,/',$params['-upsert']);
			}
		}
	}
	//are their any required fields in fieldinfo that cannot be null and do not have a default
	$rfields=array();
	foreach($fieldinfo as $f=>$info){
		if(
			isset($info['_dbnull']) 
			&& strtolower($info['_dbnull'])=='no' 
			&& !isset($info['default'])
			&& !stringContains($info['_dbflags'],'auto_increment')
		){
			$rfields[]=$f;
		}
	}
	//echo "rfields".printValue($rfields);exit;
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
	//check to confirm requried fields have a value. Fix if possible.
	if(count($rfields)){
		foreach($rfields as $rfld){
			foreach($recs as $i=>$rec){
				if(!isset($rec[$rfld]) || !strlen($rec[$rfld])){
					switch(strtolower($rfld)){
						case '_cuser':
							$recs[$i][$rfld]=isset($USER['_id'])?$USER['_id']:0;
							if(!in_array($rfld,$fields)){$fields[]=$rfld;}
						break;
						case '_euser':
							$recs[$i][$rfld]=isset($USER['_id'])?$USER['_id']:0;
							if(!in_array($rfld,$fields)){$fields[]=$rfld;}
						break;
						case '_cdate':
							$recs[$i][$rfld]='CURRENT_TIMESTAMP';
							if(!in_array($rfld,$fields)){$fields[]=$rfld;}
						break;
						case '_edate':
							$recs[$i][$rfld]='CURRENT_TIMESTAMP';
							if(!in_array($rfld,$fields)){$fields[]=$rfld;}
						break;
						default:
							debugValue(array(
								'function'=>'mysqlAddDBRecordsProcess',
								'message'=>$rfld.' cannot be null',
								'rec'=>$rec,
								'fieldinfo'=>$fieldinfo[$rfld]
							));
							return 0;
						break;
					}
				}
			}
		}
	}
	//confirm there are fields
	if(!count($fields)){
		debugValue(array(
			'function'=>'mysqlAddDBRecordsProcess',
			'message'=>'No fields in first_rec that match fieldinfo',
			'first_rec'=>$first_rec,
			'fieldinfo_keys'=>array_keys($fieldinfo)
		));
		return 0;
	}
	//verify we can connect to the db
	$dbh_mysql='';
	while($tries < 4){
		$dbh_mysql='';
		$dbh_mysql=mysqlDBConnect($params);
		if(is_resource($dbh_mysql) || is_object($dbh_mysql)){
			break;
		}
		sleep(2);
	}
	if(!is_resource($dbh_mysql) && !is_object($dbh_mysql)){
		debugValue(array(
			'function'=>'mysqlAddDBRecordsProcess',
			'message'=>'mysqlDBConnect error',
			'error'=>"Connect Error" . pg_last_error(),
		));
		return 0;
	}
	$fieldstr=implode(',',$fields);
	//echo "DEBUG".printValue($recs);exit;
	//if possible use the JSON way so we can insert more efficiently
	$jsonstr=encodeJSON($recs,JSON_UNESCAPED_UNICODE);
	if(strlen($jsonstr)){
		//define field_defs
		//Acceptable datatypes for regular column of JSON table are VARCHAR(n), NVARCHAR(n), INT, BIGINT, DOUBLE, DECIMAL, SMALLDECIMAL, TIMESTAMP, SECONDDATE, DATE and TIME
		$field_defs=array();
		foreach($fields as $field){
			switch(strtolower($fieldinfo[$field]['_dbtype'])){
				case 'char':
				case 'nchar':
					$type=str_replace('char','varchar',$fieldinfo[$field]['_dbtype_ex']);
				break;
				case 'varchar':
				case 'nvarchar':
					$type=$fieldinfo[$field]['_dbtype_ex'];
				break;
				case 'tinyint':
				case 'smallint':
				case 'integer':
					$type='int';
				break;
				default:
					$type=$fieldinfo[$field]['_dbtype'];
				break;
			}
			$parts=preg_split('/\ +/',trim($type));
			$type=$parts[0];
			$type=trim($type);
			$field_defs[]="		{$field} {$type} PATH '\$.{$field}'";
		}
		//build and test selectquery
		$selectquery="SELECT {$fieldstr} FROM JSON_TABLE(?,'\$[*]' COLUMNS (".PHP_EOL;
		//insert field_defs into query 
    	$selectquery.=implode(','.PHP_EOL,$field_defs); 
    	$selectquery.="		)".PHP_EOL; 
		$selectquery.="	) AS new".PHP_EOL;
		
		$query="INSERT{$ignore} INTO {$table} ({$fieldstr})".PHP_EOL;
		$query.="	".$selectquery;
		//upsert?
		if(isset($params['-upsert'][0])){
			$query.=" ON DUPLICATE KEY UPDATE";
			$upserts=$params['-upsert'];
			//VALUES() to refer to the new row is deprecated with version 8.0.20+
			$recs=mysqlQueryResults('SELECT VERSION() AS value');
			$version=$recs[0];
			list($v1,$v2,$v3)=preg_split('/\./',$version['value'],3);
			if((integer)$v1>8 || ((integer)$v1==8 && (integer)$v2 > 0) || ((integer)$v1==8 && (integer)$v2==0 && (integer)$v3 >=20)){
				//mysql version 8 and newer
				$flds=array();
				foreach($upserts as $fld){
					$flds[]="{$fld}=new.{$fld}";
				}
				$query.=PHP_EOL.implode(', ',$flds);
				if(isset($params['-upsertwhere']) && commonStrlen($params['-upsertwhere'])){
					$query.=" WHERE {$params['-upsertwhere']}";
				}
				//echo printValue($params);exit;
			}
			else{
				//before mysql version 8.0.20
				$flds=array();
				foreach($upserts as $fld){
					$flds[]="{$fld}=VALUES({$fld})";
				}
				$query.=PHP_EOL.implode(', ',$flds);
				//Note: Mysql does not support WHERE in an insert statement yet before version 8
			}
		}
		//echo "<pre>{$query}</pre>";exit;
		//prepare and execute
		$stmt=mysqli_prepare($dbh_mysql,$query);
		if(!is_resource($stmt) &&  !is_object($stmt)){
			$DATABASE['_lastquery']['error']=mysqli_error($dbh_mysql);
			debugValue(array($DATABASE['_lastquery']['error'],$query,$fieldinfo));
			return 0;
		}
		if(mysqli_stmt_bind_param($stmt, 's',$jsonstr)){
			try{
				mysqli_stmt_execute($stmt);
			}
			catch (Exception $e) {
				$DATABASE['_lastquery']['error']=mysqli_error($dbh_mysql);
				debugValue(array($DATABASE['_lastquery']['error'],$query,$fieldinfo));
				return 0;
			}
			return count($recs);
		}
		return 0;
	}
	//JSON method did not work, try standard prepared statement method	
	$query="INSERT{$ignore} INTO {$table} ({$fieldstr}) VALUES ";
	$values=array();
	$types=array();
	$valuesets=array();
	foreach($recs as $i=>$rec){
		$placeholders=array();
		foreach($fields as $k){
			//make sure this record has a value for every field in fields
			if(!isset($rec[$k])){$rec[$k]='';}
			//set value and keys
			$v=$rec[$k];
			if(!commonStrlen($v)){
				if(isset($fieldinfo[$k]['default'])){
					$values[]=$fieldinfo[$k]['default'];
				}
				else{$values[]=null;}
				$placeholders[]='?';
			}
			else{
				switch($fieldinfo[$k]['_dbtype']){
					case 'datetime':
						$v=date('Y-m-d H:i:s',strtotime($v));
					break;
					case 'date':
						$v=date('Y-m-d',strtotime($v));
					break;
					case 'time':
						$v=date('H:i:s',strtotime($v));
					break;
				}
				$values[]=trim($v);
				$placeholders[]='?';
				//$v=databaseEscapeString($v);
				//$rec[$k]="'{$v}'";
			}
			$types[]='s';
		}
		$valuesets[]='('.implode(',',$placeholders).')';
	}
	$query.=implode(', ',$valuesets);
	if(isset($params['-upsert'][0])){
		$upserts=$params['-upsert'];
		//VALUES() to refer to the new row is deprecated with version 8.0.20+
		$recs=mysqlQueryResults('SELECT VERSION() AS value');
		$version=$recs[0];
		list($v1,$v2,$v3)=preg_split('/\./',$version['value'],3);
		if((integer)$v1>8 || ((integer)$v1==8 && (integer)$v2 > 0) || ((integer)$v1==8 && (integer)$v2==0 && (integer)$v3 >=20)){
			//mysql version 8 and newer
			$query.=PHP_EOL."AS new"." ON DUPLICATE KEY UPDATE";
			$flds=array();
			foreach($upserts as $fld){
				$flds[]="{$fld}=new.{$fld}";
			}
			$query.=PHP_EOL.implode(', ',$flds);
			if(isset($params['-upsertwhere']) && commonStrlen($params['-upsertwhere'])){
				$query.=" WHERE {$params['-upsertwhere']}";
			}
			//echo printValue($params);exit;
		}
		else{
			//before mysql version 8.0.20
			$query.=PHP_EOL." ON DUPLICATE KEY UPDATE";
			$flds=array();
			foreach($upserts as $fld){
				$flds[]="{$fld}=VALUES({$fld})";
			}
			$query.=PHP_EOL.implode(', ',$flds);
			if(isset($params['-upsertwhere']) && commonStrlen($params['-upsertwhere'])){
				//NOTE: Mysql does not support WHERE in an insert statement yet
				//$query.=" WHERE {$params['-upsertwhere']}";
			}
		}
	}
	if(is_resource($dbh_mysql) || is_object($dbh_mysql)){
		//if(mysqli_ping($dbh_mysql)){mysqli_close($dbh_mysql);}
	}

	$dbh_mysql='';
	$dbh_mysql=mysqlDBConnect();
	if(!is_resource($dbh_mysql) && !is_object($dbh_mysql)){
		$mysqlAddDBRecordsResults['errors'][]=mysqli_connect_error();
		debugValue(mysqli_connect_error());
    		return 0;
	}
	$stmt=mysqli_prepare($dbh_mysql,$query);
	if(!is_resource($stmt) &&  !is_object($stmt)){
		$DATABASE['_lastquery']['error']=mysqli_error($dbh_mysql);
		debugValue(array($DATABASE['_lastquery']['error'],$query));
		return 0;
	}
	//echo "HERE".printValue($stmt).mysqli_error($dbh_mysql).printValue($types).printValue($values);exit;
	if(mysqli_stmt_bind_param($stmt, implode('',$types),...$values)){
		mysqli_stmt_execute($stmt);
		return $rec_cnt;
	}
	return 0;
}
//---------- begin function mysqlGetDDL ----------
/**
* @describe returns create script for specified table
* @param type string - object type
* @param name string - object name
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=mysqlGetDDL('table','sample');
*/
function mysqlGetDDL($type,$name){
	$type=strtoupper($type);
	$name=strtoupper($name);
	$query="SHOW CREATE {$type} {$name}";
	$field='create_'.strtolower($name);
	$recs=mysqlQueryResults($query);
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
//---------- begin function mysqlGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param name string - tablename
* @return string
* @usage $createsql=mysqlGetTableDDL('sample');
*/
function mysqlGetTableDDL($name){
	return mysqlGetDDL('table',$name);
}
//---------- begin function mysqlGetFunctionDDL ----------
/**
* @describe returns create script for specified function
* @param name string - function name
* @return string
* @usage $createsql=mysqlGetFunctionDDL('sample');
*/
function mysqlGetFunctionDDL($name){
	return mysqlGetDDL('function',$name);
}
//---------- begin function mysqlGetProcedureDDL ----------
/**
* @describe returns create script for specified procedure
* @param name string - procedure name
* @return string
* @usage $createsql=mysqlGetProcedureDDL('sample');
*/
function mysqlGetProcedureDDL($name){
	return mysqlGetDDL('procedure',$name);
}
//---------- begin function mysqlGetPackageDDL ----------
/**
* @describe returns create script for specified package
* @param name string - package name
* @return string
* @usage $createsql=mysqlGetPackageDDL('sample');
*/
function mysqlGetPackageDDL($name){
	return mysqlGetDDL('package',$name);
}
//---------- begin function mysqlGetTriggerDDL ----------
/**
* @describe returns create script for specified trigger
* @param name string - trigger name
* @return string
* @usage $createsql=mysqlGetTriggerDDL('sample');
*/
function mysqlGetTriggerDDL($name){
	return mysqlGetDDL('trigger',$name);
}
//---------- begin function mysqlGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=mysqlGetAllTableFields();
*/
function mysqlGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'mysqlGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!commonStrlen($schema)){
		$schema=mysqlGetDBSchema();
	}
	if(!commonStrlen($schema)){
		debugValue('mysqlGetAllTableFields error: schema is not defined in config.xml');
		return null;
	}
	$query=<<<ENDOFQUERY
		SELECT
			table_name as table_name,
			column_name as field_name,
			column_type as type_name
		FROM information_schema.columns
		WHERE
			table_schema='{$schema}'
		ORDER BY table_name,column_name
ENDOFQUERY;
	$recs=mysqlQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$field=strtolower($rec['field_name']);
		$type=strtolower($rec['type_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function mysqlGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=mysqlGetAllTableIndexes();
*/
function mysqlGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'mysqlGetAllTableIndexes');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!commonStrlen($schema)){
		$schema=mysqlGetDBSchema();
	}
	if(!commonStrlen($schema)){
		debugValue('mysqlGetAllTableIndexes error: schema is not defined in config.xml');
		return null;
	}
	//key_name,column_name,seq_in_index,non_unique
	$query=<<<ENDOFQUERY
SELECT 
	table_name,
   	index_name,
   	JSON_ARRAYAGG(column_name) AS index_keys,
   	CASE non_unique
      	WHEN 1 THEN 0
        	ELSE 1
        	END AS is_unique
FROM information_schema.statistics
WHERE 
	table_schema NOT IN ('information_schema','mysql','performance_schema','sys')
    	AND index_schema = '{$schema}'
GROUP BY 
	index_schema,
	index_name,
	non_unique,
	table_name
ORDER BY 1,2
ENDOFQUERY;
	$recs=mysqlQueryResults($query);
	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>'.printValue($recs);exit;
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$table=str_replace("{$schema}.",'',$table);
		$index_keys=json_decode($rec['index_keys'],true);
		sort($index_keys);
		$rec['index_keys']=json_encode($index_keys);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function mysqlGetAllProcedures ----------
/**
* @describe returns all procedures in said schema
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allprocedures=mysqlGetAllProcedures();
*/
function mysqlGetAllProcedures($dbname=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'mysqlGetAllProcedures');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!commonStrlen($dbname)){
		$dbname=mysqlGetDBName();
	}
	if(!commonStrlen($dbname)){
		debugValue('mysqlGetAllProcedures error: dbname is not defined in config.xml');
		return null;
	}
	$query=<<<ENDOFQUERY
SELECT 
    r.routine_name AS object_name
    ,r.routine_type AS object_type
    ,MD5(r.routine_definition) AS hash
    ,GROUP_CONCAT( DISTINCT p.parameter_name ORDER BY p.parameter_name SEPARATOR ', ')  args
FROM information_schema.routines r
   LEFT OUTER JOIN information_schema.parameters p ON p.specific_name=r.specific_name AND p.parameter_mode='IN'
WHERE r.routine_schema='{$dbname}'
GROUP BY
	r.routine_name,
	r.routine_type,
	MD5(r.routine_definition)
ENDOFQUERY;
	$recs=mysqlQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $i=>$rec){
		$key=$rec['object_name'].$rec['object_type'];
		$rec['overload']='';
		$databaseCache[$cachekey][$key][]=$rec;
	}
	//echo $query.printValue($recs);exit;
	return $databaseCache[$cachekey];
}

function mysqlGetDBSchema(){
	global $CONFIG;
	global $DATABASE;
	$params=mysqlParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	elseif(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['mysql_dbschema'])){return $CONFIG['mysql_dbschema'];}
	elseif(isset($CONFIG['mysql_schema'])){return $CONFIG['mysql_schema'];}
	return '';
}
function mysqlGetDBName(){
	global $CONFIG;
	global $DATABASE;
	$params=mysqlParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbname'])){
		return $DATABASE[$CONFIG['db']]['dbname'];
	}
	elseif(isset($CONFIG['dbname'])){return $CONFIG['dbname'];}
	elseif(isset($CONFIG['-dbname'])){return $CONFIG['-dbname'];}
	elseif(isset($CONFIG['mysql_dbname'])){return $CONFIG['mysql_dbname'];}
	return '';
}
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
	if(!commonStrlen($table)){return "mysqlGetDBRecordById Error: No Table";}
	if($id == 0){return "mysqlGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(commonStrlen($fields)){$recopts['-fields']=$fields;}
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
	if(!commonStrlen($table)){
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
	if(!commonStrlen($table)){
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
//---------- begin function mysqlListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function mysqlListRecords($params=array()){
	$params['-database']='mysql';
	return databaseListRecords($params);
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
	if(isUser() && commonStrlen($USER['username'])){
		foreach($params as $k=>$v){
			if(stringEndsWith($k,"_{$USER['username']}")){
				$nk=str_replace("_{$USER['username']}",'',$k);
				unset($params[$k]);
				$params[$nk]=$v;
			}
		}
	}
	if(isMysql()){
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
		if(isset($CONFIG['dbhost_mysql'])){
			$params['-dbhost']=$CONFIG['dbhost_mysql'];
			//$params['-dbhost_source']="CONFIG dbhost_mysql";
		}
		elseif(isset($CONFIG['mysql_dbhost'])){
			$params['-dbhost']=$CONFIG['mysql_dbhost'];
			//$params['-dbhost_source']="CONFIG mysql_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		//$params['-dbhost_source']="passed in";
	}
	$CONFIG['mysql_dbhost']=$params['-dbhost'];
	
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_mysql'])){
			$params['-dbuser']=$CONFIG['dbuser_mysql'];
			//$params['-dbuser_source']="CONFIG dbuser_mysql";
		}
		elseif(isset($CONFIG['mysql_dbuser'])){
			$params['-dbuser']=$CONFIG['mysql_dbuser'];
			//$params['-dbuser_source']="CONFIG mysql_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['mysql_dbuser']=$params['-dbuser'];
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_mysql'])){
			$params['-dbpass']=$CONFIG['dbpass_mysql'];
			//$params['-dbpass_source']="CONFIG dbpass_mysql";
		}
		elseif(isset($CONFIG['mysql_dbpass'])){
			$params['-dbpass']=$CONFIG['mysql_dbpass'];
			//$params['-dbpass_source']="CONFIG mysql_dbpass";
		}
	}
	else{
		//$params['-dbpass_source']="passed in";
	}
	$CONFIG['mysql_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_mysql'])){
			$params['-dbname']=$CONFIG['dbname_mysql'];
			//$params['-dbname_source']="CONFIG dbname_mysql";
		}
		elseif(isset($CONFIG['mysql_dbname'])){
			$params['-dbname']=$CONFIG['mysql_dbname'];
			//$params['-dbname_source']="CONFIG mysql_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['mysql_dbname'];
			//$params['-dbname_source']="set to username";
		}
	}
	else{
		//$params['-dbname_source']="passed in";
	}
	$CONFIG['mysql_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_mysql'])){
			$params['-dbport']=$CONFIG['dbport_mysql'];
			//$params['-dbport_source']="CONFIG dbport_mysql";
		}
		elseif(isset($CONFIG['mysql_dbport'])){
			$params['-dbport']=$CONFIG['mysql_dbport'];
			//$params['-dbport_source']="CONFIG mysql_dbport";
		}
		else{
			$params['-dbport']=5432;
			//$params['-dbport_source']="default port";
		}
	}
	else{
		//$params['-dbport_source']="passed in";
	}
	$CONFIG['mysql_dbport']=$params['-dbport'];
	//dbschema
	if(!isset($params['-dbschema'])){
		if(isset($CONFIG['dbschema_mysql'])){
			$params['-dbschema']=$CONFIG['dbschema_mysql'];
			//$params['-dbuser_source']="CONFIG dbuser_mysql";
		}
		elseif(isset($CONFIG['mysql_dbschema'])){
			$params['-dbschema']=$CONFIG['mysql_dbschema'];
			//$params['-dbuser_source']="CONFIG mysql_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['mysql_dbschema']=isset($params['-dbschema'])?$params['-dbschema']:'';
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['mysql_connect'])){
			$params['-connect']=$CONFIG['mysql_connect'];
			//$params['-connect_source']="CONFIG mysql_connect";
		}
		elseif(isset($CONFIG['connect_mysql'])){
			$params['-connect']=$CONFIG['connect_mysql'];
			//$params['-connect_source']="CONFIG connect_mysql";
		}
		else{
			//build connect - http://php.net/manual/en/function.pg-connect.php
			//$conn_string = "host=sheep port=5432 dbname=test user=lamb password=bar";
			//echo printValue($CONFIG);exit;
			$params['-connect']="host={$CONFIG['mysql_dbhost']} port={$CONFIG['mysql_dbport']} dbname={$CONFIG['mysql_dbname']} user={$CONFIG['mysql_dbuser']} password={$CONFIG['mysql_dbpass']}";
			//$params['-connect_source']="manual";
		}
		//add application_name
		if(!stringContains($params['-connect'],'options')){
			if(isset($params['-application_name'])){
				$appname=$params['-application_name'];
			}
			elseif(isset($CONFIG['mysql_application_name'])){
				$appname=$CONFIG['mysql_application_name'];
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
function mysqlParseConnectParamsOLD($params=array()){
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
	if(isUser() && commonStrlen($USER['username'])){
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
	global $dbh;
	$dbh_mysql='';
	$dbh='';
	try{
		if($params['-dbhost']=='localhost'){$host='127.0.0.1';}
		else{$host=$params['-dbhost'];}
		if(!commonStrlen($host)){$host='127.0.0.1';}
		$host=gethostbyname($host);
		$dbh_mysql = mysqli_connect($host,$params['-dbuser'],$params['-dbpass'],$params['-dbname']);
		if(!is_object($dbh_mysql)){
			$err=@mysqli_connect_error();
			echo "mysqlDBConnect error:{$err}".printValue($params);
			exit;

		}
		//note: this caused issues with vietnam language
		//$dbh_mysql->set_charset("utf8mb4");
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
* @return int - returns 1 if query succeeded, else 0
* @usage $ok=mysqlExecuteSQL("truncate table abc");
*/
function mysqlExecuteSQL($query,$params=array()){
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'mysqlExecuteSQL'
	);
	$query=trim($query);
	global $USER;
	global $dbh_mysql;
	$dbh_mysql='';
	$dbh_mysql=mysqlDBConnect();
	if(!$dbh_mysql){
		$DATABASE['_lastquery']['error']='connect failed: '.mysqli_connect_error();
		debugValue($DATABASE['_lastquery']);
    	return 0;
	}
	$result=@mysqli_query($dbh_mysql,$query);
	$err=mysqli_error($dbh_mysql);
	if(is_array($err) || commonStrlen($err)){
		$DATABASE['_lastquery']['error']='query err: '.$err;
		debugValue($DATABASE['_lastquery']);
		//mysqli_close($dbh_mysql);
		return 0;
	}
	if(preg_match('/^insert /i',$query) && !stringContains($query,' returning ')){
    	//return the id inserted on insert statements
    	$id=databaseAffectedRows($result);
    	//mysqli_close($dbh_mysql);
    	$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
    	return $id;
	}
	$results = mysqlEnumQueryResults($result,$params);
	//mysqli_close($dbh_mysql);
	$DATABASE['_lastquery']['stop']=microtime(true);
	$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
	return 1;
}
//---------- begin function mysqlGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage $cnt=mysqlGetDBCount(array('-table'=>'states'));
*/
function mysqlGetDBCount($params=array()){
	global $CONFIG;
	global $DATABASE;
	$dbname=strtoupper($DATABASE[$CONFIG['db']]['dbname']);
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$params['-queryonly']=1;
	$query=mysqlGetDBRecords($params);
	//echo "HERE".$query.printValue($params);exit;
	if(!stringContains($query,'where')){
	 	$query="SELECT table_rows FROM information_schema.tables WHERE table_schema='{$dbname}' AND table_name='{$params['-table']}'";
	 	$recs=getDBRecords(array('-query'=>$query,'-nolog'=>1));
	 	if(isset($recs[0]['table_rows']) && isNum($recs[0]['table_rows'])){
	 		return (integer)$recs[0]['table_rows'];
	 	}
	}
	$recs=mysqlQueryResults($query);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
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
	foreach($recs as $i=>$rec){
		$key=strtolower($rec['field']);
    	if(preg_match('/(VIRTUAL|STORED) GENERATED/i',$rec['extra'])){
			$info[$key]['expression']=mysqlGetDBExpression($table,$rec['field']);
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
			$mparts=preg_split('/\,/',trim($m[2]),2);
			$info[$key]['length']=$recs[$key]['_dblength']=$mparts[0];
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
		 if(commonStrlen($rec['default'])){
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
	$query='SELECT ';
	$query .= implode(',',$fields).' FROM ' . $params['-table'];
	//build where clause
	$where = mysqlGetDBWhere($params);
	if(commonStrlen($where)){$query .= " WHERE {$where}";}
	//Set order by if defined
    if(isset($params['-group'])){$query .= ' GROUP BY '.$params['-group'];}
	//Set order by if defined
    if(isset($params['-order'])){$query .= ' ORDER BY '.$params['-order'];}
    //Set limit if defined
    if(isset($params['-limit'])){$query .= ' LIMIT '.$params['-limit'];}
    //Set offset if defined
    if(isset($params['-offset'])){$query .= ' OFFSET '.$params['-offset'];}
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
*	mysqlGetDBRecords(array('-table'=>'notes'))
*	mysqlGetDBRecords("SELECT * FROM myschema.mytable WHERE ...")
*/
function mysqlGetDBRecords($params){
	global $USER;
	global $CONFIG;
	//echo "mysqlGetDBRecords".printValue($params);exit;
	if(empty($params['-table']) && !is_array($params)){
		$params=trim($params);
		if(preg_match('/^(desc|select|analyze|exec|with|explain|returning|show|call)[\t\s\ \r\n]/i',$params)){
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
		if(isset($params['-debug'])){return $query;}
		if(isset($params['-queryonly'])){return $query;}
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
			//echo $k.printValue($params);exit;
            $params[$k]=encodeJSON($params[$k]);
		}
		if(!commonStrlen(trim($params[$k]))){continue;}
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
	if(is_resource($dbh_mysql) || is_object($dbh_mysql)){
		if(function_exists('mysqli_real_escape_string')){
			$str=mysqli_real_escape_string($dbh_mysql,$str);
		}
		elseif(function_exists('mysqli_escape_string')){
			$str=mysqli_escape_string($dbh_mysql,$str);
		}
		else{
			$str = str_replace("'","''",$str);
		}
	}
	else{
		$str = str_replace("'","''",$str);
	}
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
	if(!commonStrlen($table)){
		echo "mysqlIsDBTable error: No table";
		return null;
	}
	$table=strtolower($table);
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$query="SHOW tables FROM {$parts[0]} WHERE tables_in_{$parts[0]} = '{$table}'";
		$recs=mysqlQueryResults($query);
		if(isset($recs[0])){return true;}
	}
	else{
		$query="SHOW TABLES";
		$recs=mysqlQueryResults($query);
		if(!isset($recs[0])){return false;}
		foreach($recs[0] as $field=>$v){break;}
		foreach($recs as $rec){
			if(strtolower($rec[$field])==$table){return true;}
		}
	}
	
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
	$query="show FULL tables FROM {$dbname} WHERE TABLE_TYPE = 'BASE TABLE'";
	$recs=mysqlQueryResults($query);
	$k="tables_in_{$dbname}";
	foreach($recs as $rec){
		$tables[]=strtolower($rec[$k]);
	}
	return $tables;
}
//---------- begin function mysqlGetDBViews ----------
/**
* @describe returns an array of views
* @return array returns array of table views
* @usage $views=mysqlGetDBViews();
*/
function mysqlGetDBViews($params=array()){
	global $CONFIG;
	global $DATABASE;
	if(!isset($CONFIG['db']) && isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$CONFIG['db']=$_REQUEST['db'];
	}
	$dbname=strtolower($DATABASE[$CONFIG['db']]['dbname']);
	$tables=array();
	$query="show FULL tables FROM {$dbname} WHERE TABLE_TYPE = 'VIEW'";
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
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'mysqlQueryResults',
		'params'=>$params
	);
	$query=trim($query);
	global $USER;
	global $dbh_mysql;
	$dbh_mysql='';
	$dbh_mysql=mysqlDBConnect();
	if(!$dbh_mysql){
		$DATABASE['_lastquery']['error']='connect failed: '.mysqli_connect_error();
		debugValue($DATABASE['_lastquery']);
    	return array();
	}
	try{
		$result=@mysqli_query($dbh_mysql,$query);
	}
	catch (Exception $e) {
		$err=array(
			'status'=>"Mysqli Prepare ERROR",
			'function'=>'importProcessCSVRecs',
			'error'=> mysqli_error($dbh_mysql),
			'exception'=>$e,
			'query'=>$query,
			'params'=>json_encode($params)
		);
		debugValue($err);
		$DATABASE['_lastquery']['error']='query error: '.mysqli_error($dbh_mysql);
		debugValue($DATABASE['_lastquery']);
		//mysqli_close($dbh_mysql);
		//echo printValue($err);exit;
		if(isset($params['-filename'])){return 0;}
		return array();
	}
	$err=mysqli_error($dbh_mysql);
	if(is_array($err) || commonStrlen($err)){
		$DATABASE['_lastquery']['error']='query error: '.$err;
		debugValue($DATABASE['_lastquery']);
		//mysqli_close($dbh_mysql);
		return array();
	}
	if(preg_match('/^insert /i',$query) && !stringContains($query,' returning ')){
    	//return the id inserted on insert statements
    	$id=databaseAffectedRows($result);
    	//mysqli_close($dbh_mysql);
    	$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
    	return $id;
	}
	$results = mysqlEnumQueryResults($result,$params);
	//mysqli_close($dbh_mysql);
	if(!is_array($results) && !isNum($results)){
		$DATABASE['_lastquery']['error']='query error: '.$results;
		debugValue($DATABASE['_lastquery']);
		return array();
	}
	$DATABASE['_lastquery']['stop']=microtime(true);
	$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
	return $results;
}
//---------- begin function mysqlEnumQueryResults ----------
/**
* @describe enumerates through the data from a mysqli_query call
* @exclude - used for internal user only
* @param data resource
* @return array - returns recordset array
*/
function mysqlEnumQueryResults($data,$params=array()){
	global $mysqlStopProcess;
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
		
	}
	else{$recs=array();}
	$i=0;
	$writefile=0;
	if(isset($fh) && is_resource($fh)){
		$writefile=1;
	}
	if(is_bool($data)){return array(array('status'=>$data));}
	while ($row = @mysqli_fetch_assoc($data)){
		//check for mysqlStopProcess request
		if(isset($mysqlStopProcess) && $mysqlStopProcess==1){
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
function mysqlNamedQueryList(){
	return array(
		array(
			'code'=>'stats',
			'icon'=>'icon-list',
			'name'=>'Stats'
		),
		array(
			'code'=>'running_queries',
			'icon'=>'icon-spin4',
			'name'=>'Running Queries'
		),
		array(
			'code'=>'sessions',
			'icon'=>'icon-spin8',
			'name'=>'Sessions'
		),
		array(
			'code'=>'table_locks',
			'icon'=>'icon-lock',
			'name'=>'Table Locks'
		),
		array(
			'code'=>'tables',
			'icon'=>'icon-table',
			'name'=>'Tables'
		),
		array(
			'code'=>'views',
			'icon'=>'icon-table',
			'name'=>'Views'
		),
		array(
			'code'=>'indexes',
			'icon'=>'icon-marker',
			'name'=>'Indexes'
		),
		array(
			'code'=>'functions',
			'icon'=>'icon-th-thumb',
			'name'=>'Functions'
		),
		array(
			'code'=>'procedures',
			'icon'=>'icon-th-thumb-empty',
			'name'=>'Procedures'
		)
	);
}
//---------- begin function mysqlNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function mysqlNamedQuery($name,$str=''){
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
		case 'kill':
			return "KILL {$str}";
		break;
		case 'stats':
			return <<<ENDOFQUERY
-- ------------------ SQL -------------------------------
SELECT 'Threads_connected' AS name, FORMAT(VARIABLE_VALUE,0) AS value
FROM performance_schema.global_status
WHERE VARIABLE_NAME = 'Threads_connected'

UNION ALL

SELECT 'Threads_running' AS name, FORMAT(VARIABLE_VALUE,0) AS value
FROM performance_schema.global_status
WHERE VARIABLE_NAME = 'Threads_running'

UNION ALL

SELECT 'Connections' AS name, FORMAT(VARIABLE_VALUE,0) AS value
FROM performance_schema.global_status
WHERE VARIABLE_NAME = 'Connections'

UNION ALL

SELECT 'Uptime' AS name, FORMAT(VARIABLE_VALUE,0) AS value
FROM performance_schema.global_status
WHERE VARIABLE_NAME = 'Uptime'

UNION ALL

SELECT 'server_name' AS name, @@hostname AS value
UNION ALL
SELECT 'mysql_version' AS name, @@version AS value
UNION ALL
SELECT 'data_directory' AS name, @@datadir AS value
UNION ALL
SELECT 'tmp_directory' AS name, @@tmpdir AS value
UNION ALL
SELECT 'os_name' AS name, @@version_compile_os AS value;

ENDOFQUERY;			
		break;
		case 'running':
		case 'queries':
		case 'running_queries':
			return <<<ENDOFQUERY
-- ----------------- RUNNING QUERIES --------------------------------
-- listopts:query_options={"class":"w_pre w_smaller"}
-- listopts:time_options={"class":"align-right","verboseTime":""}
-- ------------------ SQL -------------------------------
SELECT 
    processlist_id AS pid,
    processlist_time as time,
    processlist_host AS host,
    processlist_db AS db,
    processlist_user AS user,
    processlist_info AS query,
    processlist_state AS state
FROM performance_schema.threads 
WHERE 
    processlist_command = 'Query'
    AND processlist_id != CONNECTION_ID()
ORDER BY processlist_time DESC
ENDOFQUERY;
		break;
		case 'sessions':
			return <<<ENDOFQUERY
-- ----------------- SESSIONS --------------------------------
-- listopts:time_options={"class":"align-right","verboseTime":""}
-- ------------------ SQL -------------------------------
SELECT 
	id, 
	user, 
	host, 
	db, 
	command, 
	time, 
	state
FROM information_schema.processlist
ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY
SHOW OPEN TABLES WHERE in_use > 0
ENDOFQUERY;
		break;
		case 'functions':
			return <<<ENDOFQUERY
-- ----------------- Functions --------------------------------
-- listopts:definition_options={"class":"w_pre w_smaller"}
-- ------------------ SQL -------------------------------
SELECT 
	r.routine_name AS name,
	r.created,
	r.last_altered AS modified,
	GROUP_CONCAT(pi.parameter_name, ' ', pi.data_type) AS inputs,
	po.data_type AS output,
	definer AS created_by,
	r.routine_definition AS definition 
FROM information_schema.routines r
LEFT JOIN information_schema.parameters pi 
	on pi.specific_schema=r.routine_schema and pi.specific_name=r.routine_name and pi.parameter_mode='IN'
LEFT JOIN information_schema.parameters po 
	on po.specific_schema=r.routine_schema and po.specific_name=r.routine_name and po.parameter_mode is null
WHERE 
	r.routine_type = 'FUNCTION' 
	AND r.routine_schema = '{$dbname}'
GROUP BY 
    r.routine_name, r.created, r.last_altered, po.data_type, definer, r.routine_definition
ENDOFQUERY;
		break;
		case 'procedures':
			return <<<ENDOFQUERY
-- ----------------- Procedures --------------------------------
-- listopts:definition_options={"class":"w_pre w_smaller"}
-- ------------------ SQL -------------------------------
SELECT 
	r.routine_name AS name,
	r.created,
	r.last_altered AS modified,
	GROUP_CONCAT(pi.parameter_name, ' ', pi.data_type) AS inputs,
	po.data_type AS output,
	definer AS created_by,
	r.routine_definition AS definition 
FROM information_schema.routines r
LEFT JOIN information_schema.parameters pi 
	on pi.specific_schema=r.routine_schema and pi.specific_name=r.routine_name and pi.parameter_mode='IN'
LEFT JOIN information_schema.parameters po 
	on po.specific_schema=r.routine_schema and po.specific_name=r.routine_name and po.parameter_mode is null
WHERE 
	r.routine_type = 'PROCEDURE' 
	AND r.routine_schema = '{$dbname}'
GROUP BY 
    r.routine_name, r.created, r.last_altered, po.data_type, definer, r.routine_definition
ENDOFQUERY;
		break;
		case 'tables':
			return <<<ENDOFQUERY
-- ----------------- TABLES --------------------------------
-- listopts:row_count_options={"class":"align-right","number_format":"0"}
-- listopts:field_count_options={"class":"align-right","number_format":"0"}
-- listopts:mb_size_options={"class":"align-right","number_format":"2"}
-- listopts:auto_increment_options={"class":"align-right","number_format":"0"}
-- listopts:create_time_options={"class":"align-right","dateFormat":"m/d/Y"}
-- ------------------ SQL -------------------------------
SELECT
	t.table_name AS name,
	t.table_rows AS row_count,
	c.field_count,
	ROUND(((data_length + index_length) / 1024 / 1024), 2) AS mb_size,
	t.auto_increment,
	t.create_time,
	t.update_time,
	t.table_collation,
	t.table_comment
FROM information_schema.tables t,
(SELECT COUNT(*) field_count,table_name FROM information_schema.columns WHERE table_schema='{$dbname}' GROUP BY table_name ) c 
WHERE
	t.table_name =c.table_name 
	AND t.table_schema='{$dbname}'
ENDOFQUERY;
		break;
		case 'views':
			return <<<ENDOFQUERY
-- ----------------- Views --------------------------------
-- listopts:definition_options={"class":"w_pre w_smaller"}
-- ------------------ SQL -------------------------------
SELECT
	table_name AS name,
	view_definition as definition
FROM information_schema.views
WHERE
	table_schema='{$dbname}'
ENDOFQUERY;
		break;
		case 'indexes':
			return <<<ENDOFQUERY
-- ----------------- INDEXES --------------------------------
-- listopts:is_unique_options={"checkmark":"1","checkmark_icon":"icon-mark w_blue"}
-- listopts:is_primary_options={"checkmark":"1","checkmark_icon":"icon-mark w_red"}
-- ------------------ SQL -------------------------------
SELECT 
	table_name,
  	index_name,
  	JSON_ARRAYAGG(column_name) AS index_keys,
  	CASE non_unique
     	WHEN 1 THEN 0
     	ELSE 1
    END AS is_unique,
    CASE index_name
        WHEN 'PRIMARY' THEN 1
        ELSE 0
    END AS is_primary
FROM information_schema.statistics
WHERE 
	table_schema NOT IN ('information_schema', 'mysql','performance_schema', 'sys')
	AND index_schema = '{$dbname}'
GROUP BY 
	index_schema,
	index_name,
	non_unique,
	table_name
ORDER BY table_name,index_name,is_primary DESC,is_unique DESC
ENDOFQUERY;
		break;
	}
}
/*
	https://github.com/acropia/MySQL-Tuner-PHP/blob/master/mt.php

*/
function mysqlOptimizations($params=array()){
	$results=array();
	//version
	$recs=mysqlQueryResults('SELECT version() AS val');
	$results['version']=$recs[0]['val'];
	//get status
	$recs=mysqlQueryResults("show global status");
	foreach($recs as $rec){
		$key=strtolower($rec['variable_name']);
		$results[$key]=strtolower($rec['value']);
	}
	//variables
	$recs=mysqlQueryResults("show global variables");
	foreach($recs as $rec){
		$key=strtolower($rec['variable_name']);
		$results[$key]=strtolower($rec['value']);
	}
	$results['avg_qps']=$results['questions']/$results['uptime'];
	$results['slow_queries_pcnt']=$results['slow_queries']/$results['questions'];
	$results['thread_cache_hit_rate']=100 - (($results['threads_created']/$results['connections'])*100);
	$results['aborted_connects_pcnt']=$results['aborted_connects']/$results['connections'];
	//engines
	$recs=mysqlQueryResults("SELECT Engine, Support, Comment, Transactions, XA, Savepoints FROM information_schema.ENGINES ORDER BY Engine ASC");
	foreach($recs as $rec){
		$key=strtolower($rec['engine']);
		$xrecs=mysqlQueryResults("SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND ENGINE = '{$rec['engine']}'");
		$rec['count']=$xrecs[0]['count'];
		$results['engines'][$key]=$rec;
	}
	//aria
	$recs=mysqlQueryResults("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS val FROM information_schema.TABLES WHERE ENGINE='Aria'");
	$results['aria_index_length']=(integer)$recs[0]['val'];
	if(isset($results['aria_pagecache_read_requests'])){
		$results['aria_keys_from_memory_pcnt']=(integer)(100 - (($results['aria_pagecache_reads']/$results['aria_pagecache_read_requests'])*100));
	}
	//innoDB
	$results['innodb_buffer_pool_read_ratio']=$results['innodb_buffer_pool_reads'] * 100 / $results['innodb_buffer_pool_read_requests'];
	$recs=mysqlQueryResults("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS val FROM information_schema.TABLES WHERE ENGINE='InnoDB'");
	$results['innodb_index_length']=(integer)$recs[0]['val'];
	$recs=mysqlQueryResults("SELECT IFNULL(SUM(DATA_LENGTH),0) AS data_length FROM information_schema.TABLES WHERE ENGINE='InnoDB'");
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
	$recs=mysqlQueryResults("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS val FROM information_schema.TABLES WHERE ENGINE='MyISAM'");
	$results['myisam_index_length']=$recs[0]['val'];
	
	echo printValue($results);exit;
	$recs=array();
	//order by priority
	$recs=sortArrayByKeys($recs,array('priority'=>SORT_ASC));
	return $recs;
}