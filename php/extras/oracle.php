<?php
//http://php.net/manual/en/oci8.configuration.php
//connection class to enable better connection pooling
ini_set('oci8.connection_class','WaSQL');
//event (FAN)
ini_set('oci8.events','ON');
//max number of persistent connections to the database
ini_set('oci8.max_persistent',50);
//seconds a persistent connection will stay alive
ini_set('oci8.persistent_timeout',-1);
//number of rows in each DB round trip to cache
ini_set('oci8.default_prefetch',100);
//number of statements to cache
ini_set('oci8.statement_cache_size',20);

//---------- begin function oracleAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
* @return count int
* @usage $ok=oracleAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=oracleAddDBRecords('comments',array('-recs'=>$recs);
*/
function oracleAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("oracleAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("oracleAddDBRecords Error: either -csv or -recs is required");
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("oracleAddDBRecords Error: no such file: {$params['-csv']}");
		}
		$ok=processCSVLines($table,'oracleAddDBRecordsProcess',array(
			'table'=>$table,
			'-chunk'=>$params['-chunk']
		));
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("oracleAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("oracleAddDBRecords Error: no recs");
		}
		return oracleAddDBRecordsProcess($params['-recs'],array('table'=>$table));
	}
}
function oracleAddDBRecordsProcess($recs,$params=array()){
	if(!isset($params['table'])){
		return debugValue("oracleAddDBRecordsProcess Error: no table"); 
	}
	$table=$params['table'];
	$fieldinfo=oracleGetDBFieldInfo($table,1);
	$fields=array();
	foreach($recs as $i=>$rec){
		foreach($rec as $k=>$v){
			if(!isset($fieldinfo[$k])){continue;}
			if(!in_array($k,$fields)){$fields[]=$k;}
		}
	}
	$fieldstr=implode(',',$fields);
	$query="INSERT INTO {$table} ({$fieldstr}) WITH vals AS ( ".PHP_EOL;
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
				$v=oracleEscapeString($v);
				$rec[$k]="'{$v}'";
			}
		}
		$recstr=implode(',',array_values($rec));
		$values[]="select {$recstr} from dual";
	}
	$query.=implode(PHP_EOL.'UNION ALL'.PHP_EOL,$values);
	$query.=PHP_EOL.') select * from vals';
	//echo nl2br($query);exit;
	$ok=oracleExecuteSQL($query);
	return count($values);
}
function oracleEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function oracleGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=oracleGetTableDDL('sample');
*/
function oracleGetTableDDL($table,$schema=''){
	$table=strtoupper($table);
	if(!strlen($schema)){
		$schema=oracleGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('oracleGetTableDDL error: schema is not defined in config.xml');
		return null;
	}
	$schema=strtoupper($schema);
	$query=<<<ENDOFQUERY
		SELECT 
			DBMS_METADATA.GET_DDL('TABLE','{$table}','{$schema}') as ddl 
		FROM DUAL
ENDOFQUERY;
	$recs=oracleQueryResults($query);
	//echo $query.printValue($recs);exit;
	if(isset($recs[0]['ddl'])){
		return $recs[0]['ddl'];
	}
	return $recs;
}
//---------- begin function oracleGetProcedureText ----------
/**
* @describe returns procedure text
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $txt=oracleGetProcedureText('sample');
*/
function oracleGetProcedureText($name='',$type='',$schema=''){
	$table=strtoupper($table);
	if(!strlen($schema)){
		$schema=oracleGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('oracleGetTableDDL error: schema is not defined in config.xml');
		return null;
	}
	$schema=strtoupper($schema);
	$query=<<<ENDOFQUERY
		select text
		from all_source
		where 
			owner='{$schema}'
			and name='{$name}'
			and type='{$type}'
		order by line
ENDOFQUERY;
	$recs=oracleQueryResults($query);
	$lines=array();
	foreach($recs as $rec){
		$lines[]=trim($rec['text']);
	}
	return $lines;
}
//---------- begin function oracleGetAllProcedures ----------
/**
* @describe returns all procedures in said schema
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allprocedures=oracleGetAllProcedures();
*/
function oracleGetAllProcedures($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'oracleGetAllProcedures');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=oracleGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('oracleGetAllProcedures error: schema is not defined in config.xml');
		return null;
	}
	//key_name,column_name,seq_in_index,non_unique
	$schema=strtoupper($schema);
	//get source
	$query=<<<ENDOFQUERY
	select name,type,sum(ora_hash(text)) as hash
	from all_source
	where owner='{$schema}'
	group by owner,name,type
ENDOFQUERY;
	$recs=oracleQueryResults($query);
	$hashes=array();
	foreach($recs as $rec){
		$key=$rec['name'].$rec['type'];
		$hashes[$key]=$rec['hash'];
	}
	$query=<<<ENDOFQUERY
	SELECT 
    ap.object_name
    ,ap.object_type
    ,ap.overload
    ,listagg(aa.argument_name,', ') within group (ORDER BY aa.position) args
FROM all_procedures ap
   LEFT OUTER JOIN all_arguments aa
      on aa.object_name=ap.object_name
         and nvl(aa.overload,0)=nvl(ap.overload,0)
         and aa.owner=ap.owner
WHERE
	ap.owner='{$schema}'
GROUP BY 
	ap.object_name
    ,ap.object_type
    ,ap.overload
ENDOFQUERY;
	$recs=oracleQueryResults($query);

	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>';
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$key=$rec['object_name'].$rec['object_type'];
		if(isset($hashes[$key])){
			$rec['hash']=$hashes[$key];
		}
		else{
			$rec['hash']='';
		}
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function oracleGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=oracleGetAllTableFields();
*/
function oracleGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'oracleGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=oracleGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('oracleGetAllTableFields error: schema is not defined in config.xml');
		return null;
	}
	$schema=strtoupper($schema);
	$query=<<<ENDOFQUERY
		SELECT table_name,column_name field_name,
        DECODE (data_type,
                'LONG',       'LONG',
                'LONG RAW',   'LONG RAW',
                'RAW',        'RAW',
                'DATE',       'DATE',
                'CHAR',       'CHAR' || '(' || data_length || ')',
                'VARCHAR2',   'VARCHAR2' || '(' || data_length || ')',
                'NUMBER',     'NUMBER' ||
                DECODE (NVL(data_precision,0),0, ' ',' (' || data_precision ||
                DECODE (NVL(data_scale, 0),0, ') ',',' || DATA_SCALE || ')'))) ||
        DECODE (NULLABLE,'N', ' NOT NULL','') type_name
   		FROM all_tab_cols
  		WHERE owner='{$schema}'
  		and table_name in (select table_name from all_tables)
    	ORDER BY table_name,column_id,column_name
ENDOFQUERY;
	$recs=oracleQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		//$field=strtolower($rec['field_name']);
		//$type=strtolower($rec['type_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
/*
ALTER TABLE table_name 
ADD (
    column_name_1 data_type constraint,
    column_name_2 data_type constraint,
    ...
);

*/
function oracleAlterDBTable($table,$fields=array(),$maintain_order=1){
	$info=oracleGetDBFieldInfo($table);
	if(!is_array($info) || !count($info)){
		debugValue("oracleAlterDBTable - {$table} is missing or has no fields".printValue($table));
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
		$ok=oracleExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	if(count($addfields)){
		$fieldstr=implode(', ',$addfields);
		$query="ALTER TABLE {$table} ADD ({$fieldstr})";
		$ok=oracleExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	if($maintain_order==1){
		/* In 12c you can make use of the fact that columns which are set from invisible to visible are displayed as the last column of the table */
		$mfields=array_keys($fields);
		$first=array_shift($mfields);
		//set them all to invisible
		$ifields=array();
		foreach($mfields as $field){
			$ifields[]="{$field} invisible";
		}
		$ifieldstr=implode(', ',$ifields);
		$query="ALTER TABLE {$table} MODIFY ({$ifieldstr})";
		$ok=oracleExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
		//now make them visible
		foreach($mfields as $field){
			$query="ALTER TABLE {$table} MODIFY ({$field} visible)";
			$ok=oracleExecuteSQL($query);
			$rtn[]=$query;
			$rtn[]=$ok;
		}
	}
	return $rtn;
}
//---------- begin function oracleAddDBIndex--------------------
/**
* @describe add an index to a table
* @param params array
*	-table
*	-fields
*	[-fulltext]
*	[-unique]
*	[-name] name of the index
* @return boolean
* @link https://www.w3schools.com/sql/sql_ref_create_index.asp
* @usage
*	$ok=oracleAddDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=oracleAddDBIndex(array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function oracleAddDBIndex($params=array()){
	if(!isset($params['-table'])){return 'oracleAddDBIndex Error: No table';}
	if(!isset($params['-fields'])){return 'oracleAddDBIndex Error: No fields';}
	if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,+/',$params['-fields']);}

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
	if(!isset($params['-name'])){$params['-name']="{$prefix}_{$params['-table']}_{$fieldstr}";}
	//build and execute
	$fieldstr=strtolower(implode(", ",$params['-fields']));
	$query="CREATE {$fulltext}{$unique} INDEX {$params['-name']} ON {$params['-table']} ({$fieldstr})";
	$ok=oracleExecuteSQL($query);
	return array($ok,$query);
}
//---------- begin function oracleDropDBIndex--------------------
/**
* @describe drop an index previously created
* @param indexname string
* @return boolean
* @link https://www.w3schools.com/sql/sql_ref_drop_index.asp
* @usage $ok=oracleDropDBIndex($indexname);
*/
function oracleDropDBIndex($indexname){
	if(!strlen($indexname)){return 'oracleDropDBIndex Error: No indexname';}
	//build and execute
	$query="DROP INDEX {$indexname}";
	$ok=oracleExecuteSQL($query);
	return array($ok,$query);
}
//---------- begin function oracleGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=oracleGetAllTableIndexes();
*/
function oracleGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'oracleGetAllTableIndexes');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=oracleGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('oracleGetAllTableIndexes error: schema is not defined in config.xml');
		return null;
	}
	//key_name,column_name,seq_in_index,non_unique
	$schema=strtoupper($schema);
	$query=<<<ENDOFQUERY
	SELECT 
		a.table_name,
       	a.index_name,
       	JSON_ARRAYAGG(b.column_name order by column_position RETURNING VARCHAR2(100)) as index_keys,
       	CASE a.uniqueness WHEN 'UNIQUE' then 1 else 0 END as is_unique,
       	a.generated
	FROM sys.all_indexes a
		INNER JOIN sys.all_ind_columns b on a.owner = b.index_owner and a.index_name = b.index_name
	WHERE a.owner = '{$schema}'
	GROUP BY 
		a.table_name,
	    a.index_name,
	    case a.uniqueness when 'UNIQUE' then 1 else 0 end,
	    generated
	ORDER BY 1,2
ENDOFQUERY;
	$recs=oracleQueryResults($query);
	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>';
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$table=str_replace("{$schema}.",'',$table);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
function oracleGetDBTableIndexes($tablename=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'oracleGetDBTableIndexes'.$tablename);
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	$schema=oracleGetDBSchema();
	if(!strlen($schema)){
		debugValue('oracleGetAllTableIndexes error: schema is not defined in config.xml');
		return null;
	}
	$schema=strtoupper($schema);
	$tablename=strtoupper($tablename);
	$tablename=str_replace("{$schema}.",'',$tablename);
	$query=<<<ENDOFQUERY
	SELECT 
		a.table_name,
       	a.index_name,
       	JSON_ARRAYAGG(b.column_name order by column_position RETURNING VARCHAR2(100)) as index_keys,
       	CASE a.uniqueness WHEN 'UNIQUE' then 1 else 0 END as is_unique
	FROM sys.all_indexes a
		INNER JOIN sys.all_ind_columns b on a.owner = b.index_owner and a.index_name = b.index_name
	WHERE a.owner = '{$schema}' and a.table_name='{$tablename}'
	GROUP BY 
		a.table_name,
	    a.index_name,
	    case a.uniqueness when 'UNIQUE' then 1 else 0 end
	ORDER BY 1,2
ENDOFQUERY;
	$recs=oracleQueryResults($query);
	$xrecs=array();
	foreach($recs as $rec){
		$cols=json_decode($rec['index_keys'],true);
		foreach($cols as $i=>$col){
			$xrec=$rec;
			$xrec['key_name']=$rec['index_name'];
			$xrec['column_name']=$col;
			$xrec['seq_in_index']=$i;
			$xrecs[]=$xrec;
		}
	}
	//echo $query.printValue($recs).printValue($xrecs);exit;
	$databaseCache[$cachekey]=$xrecs;
	return $databaseCache[$cachekey];
}
function oracleGetDBSchema(){
	global $CONFIG;
	global $DATABASE;
	$params=oracleParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	elseif(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['oracle_dbschema'])){return $CONFIG['oracle_dbschema'];}
	elseif(isset($CONFIG['oracle_schema'])){return $CONFIG['oracle_schema'];}
	return '';
}
//---------- begin function oracleAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_oracle,dbuser_oracle, and dbpass_oracle
*   -table - name of the table to add to
*	[-return] - name of the field you want to return from the inserted record. For instance, id
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=oracleAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function oracleAddDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){
		$out=array(
    		'function'=>"oracleAddDBRecord",
    		'error'=>'No table specified'
    	);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
    }
	//connect
	$dbh_oracle=oracleDBConnect($params);
	$fields=oracleGetDBFieldInfo($params['-table'],$params);
	//populate cdate and cuser fields
	if(isset($fields['cdate']) && !isset($params['cdate'])){
		$params['cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	elseif(isset($fields['_cdate']) && !isset($params['_cdate'])){
		$params['_cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($fields['cuser']) && !isset($params['cuser'])){
		$params['cuser']=$USER['username'];
	}
	elseif(isset($fields['_cuser']) && !isset($params['_cuser'])){
		$params['_cuser']=$USER['username'];
	}
	$values=array();
	$bindvars=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		if($k=='euser' || $k=='edate'){continue;}
		if($k=='_euser' || $k=='_edate'){continue;}
		if(is_array($params[$k])){
            	$params[$k]=implode(':',$params[$k]);
		}
		$bindvars[$k]=':b_'.preg_replace('/[^a-z]/i','',$k);
		switch(strtolower($fields[$k]['_dbtype'])){
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$params[$k]=date('d-M-Y',strtotime($v));
				}
				//set the template for the to_date
				if(preg_match('/^([0-9]{2,2}?)\-([a-z]{3,3}?)\-([0-9]{2,4})/i',$params[$k],$m)){
					//already in the right format: 02-MAR-2019
					$values[$k]="{$m[1]}-{$m[2]}-{$m[3]}";
				}
				elseif(preg_match('/^([0-9]{4,4}?)\-([0-9]{2,2}?)\-([0-9]{2,2})/i',$params[$k],$m)){
					//2018-11-07
					$values[$k]=date('d-M-Y',strtotime("{$m[1]}-{$m[2]}-{$m[3]}"));
				}
				else{
					$values[$k]=date('d-M-Y',strtotime($v));
				}
        	break;
        	default:
        		$values[$k]=$v;
        	break;
		}
	}
	//build the query with bind variables
	$fields=array_keys($values);
	foreach($fields as $i=>$field){
		if(oracleIsReservedWord($field)){
			$fields[$i]='"'.strtoupper($field).'"';
		}
	}
	$fieldstr=implode(', ',$fields);
	$bindstr=implode(', ',array_values($bindvars));
    $query="INSERT INTO {$params['-table']} ({$fieldstr}) values ({$bindstr})";
    if(isset($params['-return'])){
    	$query .= " RETURNING {$params['-return']} INTO :returnval";
    }
    $stid = oci_parse($dbh_oracle, $query);
    if (!is_resource($stid)){
    	$out=array(
    		'function'=>"oracleAddDBRecord",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_parse',
    		'error'=>oci_error($dbh_oracle),
    		'query'=>$query
    	);
    	oci_close($dbh_oracle);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	
    	return false;
    }
    //bind the variables
    foreach($values as $k=>$v){
    	$bind=$bindvars[$k];
    	switch(strtolower($fields[$k]['_dbtype'])){
    		case 'clob':
    			// treat clobs differently so we can insert large amounts of data
    			$descriptor[$k] = oci_new_descriptor($dbh_oracle, OCI_DTYPE_LOB);
				if(!oci_bind_by_name($stid, $bind, $descriptor[$k], -1, SQLT_CLOB)){
					$out=array(
			    		'function'=>"oracleAddDBRecord",
			    		'connection'=>$dbh_oracle,
			    		'stid'=>$stid,
			    		'action'=>'oci_bind_by_name',
			    		'error'=>oci_error($stid),
			    		'query'=>$query,
			    		'field'=>$k,
			    		'_dbtype'=>$fields[$k]['_dbtype'],
			    		'bind'=>$bind,
			    		'value'=>$values[$k]
			    	);
			    	if(isset($params['-return_errors'])){
			    		return $out;
			    	}
					debugValue($out);
			    	return false;
				}
				$descriptor[$k]->writeTemporary($values[$k]);
    		break;
    		case 'blob':
    			if(!oci_bind_by_name($stid, $bind, $values[$k], strlen($values[$k]), OCI_B_BLOB )){
			    	$out=array(
			    		'function'=>"oracleAddDBRecord",
			    		'connection'=>$dbh_oracle,
			    		'stid'=>$stid,
			    		'action'=>'oci_bind_by_name',
			    		'error'=>oci_error($stid),
			    		'query'=>$query,
			    		'field'=>$k,
			    		'_dbtype'=>$fields[$k]['_dbtype'],
			    		'bind'=>$bind,
			    		'value'=>$values[$k]
			    	);
			    	if(isset($params['-return_errors'])){
			    		return $out;
			    	}
					debugValue($out);
			    	return false;
				}
    		break;
    		default:
    			if(!oci_bind_by_name($stid, $bind, $values[$k], strlen($values[$k]))){
			    	$out=array(
			    		'function'=>"oracleAddDBRecord",
			    		'connection'=>$dbh_oracle,
			    		'stid'=>$stid,
			    		'action'=>'oci_bind_by_name',
			    		'error'=>oci_error($stid),
			    		'query'=>$query,
			    		'field'=>$k,
			    		'_dbtype'=>$fields[$k]['_dbtype'],
			    		'bind'=>$bind,
			    		'value'=>$values[$k]
			    	);
			    	if(isset($params['-return_errors'])){
			    		return $out;
			    	}
					debugValue($out);
			    	return false;
				}
    		break;
    	}
    }
    if(isset($params['-return'])){
    	if(!oci_bind_by_name($stid, ':returnval', $returnval, -1, SQLT_INT)){
			$out=array(
	    		'function'=>"oracleAddDBRecord",
	    		'connection'=>$dbh_oracle,
	    		'stid'=>$stid,
	    		'action'=>'oci_bind_by_name',
	    		'error'=>oci_error($stid),
	    		'query'=>$query,
	    		'field'=>$k,
	    		'_dbtype'=>$fields[$k]['_dbtype'],
	    		'bind'=>$bind
	    	);
	    	if(isset($params['-return_errors'])){
	    		return $out;
	    	}
			debugValue($out);
	    	return false;
		}
    }
	$r = oci_execute($stid);
	$e=oci_error($stid);
	if (!$r) {
		$out=array(
    		'function'=>"oracleAddDBRecord",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_execute',
    		'stid'=>$stid,
    		'error'=>$e,
    		'query'=>$query
    	);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
	}
	if(isset($params['-return'])){
		return $returnval;
	}
	return true;
}
//---------- begin function oracleAutoCommit ----------
/**
* @describe turn autocommit on or off
* @param $stid resource - statement id
* @param $onoff boolean - set to 0 or 'off' to turn autocommit off
* @return connection resource and sets the global $dbh_oracle variable
* @usage $ok=oracleAutoCommit($stid,'off');
*/
function oracleAutoCommit($stid,$onoff=0){
	switch(strtolower($onoff)){
		case 0:
		case 'off':
			//turn OFF autocommit
			$r = oci_execute($stid, OCI_NO_AUTO_COMMIT);
		break;
		default:
			//turn ON autocommit
			$r = oci_execute($stid, OCI_COMMIT_ON_SUCCESS );
		break;
	}
	if (!$r) {
		$out=array(
    		'function'=>"oracleAutoCommit",
    		'stid'=>$stid,
    		'action'=>'oci_execute',
    		'error'=>oci_error($stid),
    		'onoff'=>$onoff
    	);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
		return false;
	}
	return true;
}
//---------- begin function oracleCommit ----------
/**
* @describe commit any transactions that have not been committed
* @param [$conn] resource - connection. defaults to $dbh_oracle global
* @return boolean
* @usage $ok=oracleCommit();
*/
function oracleCommit($conn=''){
	if(is_resource($conn)){
		global $dbh_oracle;
		$conn=$dbh_oracle;
	}
	return oci_commit($conn);
}
//---------- begin function oracleDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_oracle,dbuser_oracle, and dbpass_oracle
*	[-host] - oracle server to connect to
* 	[-dbname] - name of database.
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return connection resource and sets the global $dbh_oracle variable
* @usage $dbh_oracle=oracleDBConnect($params);
* @usage singe query usage
* 	$conn=oracleDBConnect(array('-single'=>1));
* 		$stid = oci_parse($conn, 'select 1,2,3 from dual');
* 		oci_execute($stid);
* 		while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
* 			echo printValue($row);
* 		}
* 	oci_close($conn);
*/
function oracleDBConnect($params=array()){
	if(!isset($params['-port'])){$params['-port']=1521;}
	if(!isset($params['-charset'])){$params['-charset']='AL32UTF8';}
	$params=oracleParseConnectParams($params);
	if(!isset($params['-connect'])){
		$params['-dbpass']=preg_replace('/./','*',$params['-dbpass']);
		$params['-dbuser']=preg_replace('/./','*',$params['-dbuser']);
		echo "oracleDBConnect error: no connect params".printValue($params);
		exit;
	}
	if(isset($params['-single'])){
		$dbh_single = oci_connect($params['-dbuser'],$params['-dbpass'],$params['-connect'],$params['-charset']);
		if(!is_resource($dbh_single)){
			$err=json_encode(oci_error());
			$params['-dbpass']=preg_replace('/./','*',$params['-dbpass']);
			$params['-dbuser']=preg_replace('/./','*',$params['-dbuser']);
			echo "oracleDBConnect single connect error:{$err}".printValue($params);
			exit;
		}
		return $dbh_single;
	}
	global $dbh_oracle;
	if(is_resource($dbh_oracle)){return $dbh_oracle;}
	try{
		$dbh_oracle = oci_pconnect($params['-dbuser'],$params['-dbpass'],$params['-connect'],$params['-charset']);
		if(!is_resource($dbh_oracle)){
			$err=oci_error();
			$params['-dbpass']=preg_replace('/./','*',$params['-dbpass']);
			$params['-dbuser']=preg_replace('/./','*',$params['-dbuser']);
			echo "oracleDBConnect resource error. ".printValue($err).printValue($params);
			exit;

		}
		return $dbh_oracle;
	}
	catch (Exception $e) {
		echo "oracleDBConnect exception" . printValue($e);
		exit;
	}
}
//---------- begin function oracleEditDBRecord ----------
/**
* @describe edits a records from params passed in. must have a -table and a -where
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_oracle,dbuser_oracle, and dbpass_oracle
*   -table - name of the table to add to
*   -where - filter to limit edit by.  i.e "id=4"
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to specify what fields and values to edit
* @return integer return 1 on success
* @usage $id=oracleEditDBRecord(array('-table'=>'abc','-where'=>'id=4',name'=>'bob','age'=>25));
*/
function oracleEditDBRecord($params,$id=0,$opts=array()){
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){
		$out=array(
    		'function'=>"oracleEditDBRecord",
    		'error'=>'No table specified'
    	);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
    }
	if(!isset($params['-where'])){
		$out=array(
    		'function'=>"oracleEditDBRecord",
    		'error'=>'No where specified'
    	);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
    }
	global $USER;
	//get the database handle
	$dbh_oracle=oracleDBConnect($params);
	$fields=oracleGetDBFieldInfo($params['-table'],$params);
	$values=array();
	$bindars=array();
	if(isset($fields['edate']) && !isset($params['edate'])){
		$params['edate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	elseif(isset($fields['_edate']) && !isset($params['_edate'])){
		$params['_edate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($fields['euser']) && !isset($params['euser'])){
		$params['euser']=$USER['username'];
	}
	elseif(isset($fields['_euser']) && !isset($params['_euser'])){
		$params['_euser']=$USER['username'];
	}
	$values=array();
	$bindvars=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		if($k=='cuser' || $k=='cdate'){continue;}
		if($k=='_cuser' || $k=='_cdate'){continue;}
		if(is_array($params[$k])){
            $params[$k]=implode(':',$params[$k]);
		}
		$bindvars[$k]=':b'.preg_replace('/[^a-z]/i','',$k);
		switch(strtolower($fields[$k]['_dbtype'])){
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$params[$k]=date('d-M-Y',strtotime($v));
				}
				//set the template for the to_date
				if(preg_match('/^([0-9]{2,2}?)\-([a-z]{3,3}?)\-([0-9]{2,4})/i',$params[$k],$m)){
					//already in the right format: 02-MAR-2019
					$values[$k]="{$m[1]}-{$m[2]}-{$m[3]}";
				}
				elseif(preg_match('/^([0-9]{4,4}?)\-([0-9]{2,2}?)\-([0-9]{2,2})/i',$params[$k],$m)){
					//2018-11-07
					$values[$k]=date('d-M-Y',strtotime("{$m[1]}-{$m[2]}-{$m[3]}"));
				}
				else{
					$values[$k]=date('d-M-Y',strtotime($v));
				}
        	break;
        	default:
        		$values[$k]=$v;
        	break;
		}
	}
	//build the query with bind variables
	$sets=array();
	foreach($values as $k=>$v){
		$sets[]="{$k}={$bindvars[$k]}";
	}
	$setstr=implode(',',$sets);
    $query="update {$params['-table']} set {$setstr} where {$params['-where']}";
    $stid = oci_parse($dbh_oracle, $query);
    //check for parse errors
    if(!is_resource($stid)){
    	$out=array(
    		'function'=>"oracleEditDBRecord",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_parse',
    		'error'=>oci_error($dbh_oracle),
    		'query'=>$query
    	);

    	oci_close($dbh_oracle);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
    }
    //bind the variables
    foreach($bindvars as $k=>$bind){
    	switch(strtolower($fields[$k]['_dbtype'])){
    		case 'clob':
    			// treat clobs differently so we can insert large amounts of data
    			$descriptor[$k] = oci_new_descriptor($dbh_oracle, OCI_DTYPE_LOB);
				if(!oci_bind_by_name($stid, $bind, $descriptor[$k], -1, SQLT_CLOB)){
					$out=array(
			    		'function'=>"oracleEditDBRecord",
			    		'connection'=>$dbh_oracle,
			    		'stid'=>$stid,
			    		'action'=>'oci_bind_by_name',
			    		'error'=>oci_error($stid),
			    		'query'=>$query,
			    		'field'=>$k,
			    		'_dbtype'=>$fields[$k]['_dbtype'],
			    		'bind'=>$bind,
			    		'value'=>$values[$k]
			    	);
			    	if(isset($params['-return_errors'])){
			    		return $out;
			    	}
					debugValue($out);
			    	return false;
				}
				$descriptor[$k]->writeTemporary($values[$k]);
    		break;
    		case 'blob':
    			if(!oci_bind_by_name($stid, $bind, $values[$k], strlen($values[$k]), OCI_B_BLOB )){
			    	$out=array(
			    		'function'=>"oracleEditDBRecord",
			    		'connection'=>$dbh_oracle,
			    		'stid'=>$stid,
			    		'action'=>'oci_bind_by_name',
			    		'error'=>oci_error($stid),
			    		'query'=>$query,
			    		'field'=>$k,
			    		'_dbtype'=>$fields[$k]['_dbtype'],
			    		'bind'=>$bind,
			    		'value'=>$values[$k]
			    	);
			    	if(isset($params['-return_errors'])){
			    		return $out;
			    	}
					debugValue($out);
			    	return false;
				}
    		break;
    		default:
    			if(!oci_bind_by_name($stid, $bind, $values[$k], strlen($values[$k]))){
			    	$out=array(
			    		'function'=>"oracleEditDBRecord",
			    		'connection'=>$dbh_oracle,
			    		'stid'=>$stid,
			    		'action'=>'oci_bind_by_name',
			    		'error'=>oci_error($stid),
			    		'query'=>$query,
			    		'field'=>$k,
			    		'_dbtype'=>$fields[$k]['_dbtype'],
			    		'bind'=>$bind,
			    		'value'=>$values[$k]
			    	);
			    	if(isset($params['-return_errors'])){
			    		return $out;
			    	}
					debugValue($out);
			    	return false;
				}
    		break;
    	}
    }
	$r = oci_execute($stid);
	$e=oci_error($stid);
	if (!$r) {
		$out=array(
    		'function'=>"oracleEditDBRecord",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_execute',
    		'stid'=>$stid,
    		'error'=>$e,
    		'query'=>$query
    	);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
	}
	return true;
}
//---------- begin function oracleExecuteSQL ----------
/**
* @describe executes query and returns succes or error
* @param $query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_oracle,dbuser_oracle, and dbpass_oracle
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[module] - module to set query against. Defaults to 'waSQL'
* 	[action] - action to set query against. Defaults to 'oracleExecuteSQL'
* 	[id] - identifier to set query against. Defaults to current user
* 	[setmodule] boolean - set to false to not set module, action, and id. Defaults to true
* 	[-idcolumn] boolean - set to true to include row number as _id column
* @return array - returns boolean or error
* @usage $ok=oracleExecuteSQL($query);
*/
function oracleExecuteSQL($query='',$params=array()){
	global $USER;
	//connect
	$dbh_oracle=oracleDBConnect($params);
	if(!isset($params['setmodule'])){$params['setmodule']=true;}
	$stid = oci_parse($dbh_oracle, $query);
	if (!$stid) {
		$out=array(
    		'function'=>"oracleExecuteSQL",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_parse',
    		'error'=>oci_error($dbh_oracle),
    		'query'=>$query
    	);
    	oci_close($dbh_oracle);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return;
	}
	if($params['setmodule']){
		if(!isset($params['module'])){$params['module']='waSQL';}
		if(!isset($params['action'])){
			if(isset($_REQUEST['AjaxRequestUniqueId'])){$params['action']='oracleExecuteSQL (AJAX): '.$_REQUEST['AjaxRequestUniqueId'];}
			else{$params['action']='oracleExecuteSQL';}
		}
		if(!isset($params['id'])){$params['id']=$USER['username'];}
		oci_set_module_name($dbh_oracle, $params['module']);
		oci_set_action($dbh_oracle, $params['action']);
		oci_set_client_identifier($dbh_oracle, $params['id']);
	}
	//log this query
	// check for non-select query
	$start=microtime(true);
	if(preg_match('/^(truncate|create|drop|update|insert|alter)/is',trim($query))){
		$r = oci_execute($stid,OCI_COMMIT_ON_SUCCESS);
		$e=oci_error($stid);
		if(function_exists('logDBQuery')){
			logDBQuery($query,$start,'oracleExecuteSQL','oracle');
		}
    	if($params['setmodule']){
			oci_set_module_name($dbh_oracle, 'idle');
			oci_set_action($dbh_oracle, 'idle');
			oci_set_client_identifier($dbh_oracle, 'idle');
		}
		if (!$r){
			$out=array(
	    		'function'=>"oracleExecuteSQL",
	    		'connection'=>$dbh_oracle,
	    		'action'=>'oci_execute',
	    		'error'=>$e,
	    		'query'=>$query
	    	);
	    	oci_free_statement($stid);
	    	oci_close($dbh_oracle);
	    	if(isset($params['-return_errors'])){
	    		return $out;
	    	}
			debugValue($out);
	    	return false;
		}
		oci_free_statement($stid);
	    oci_close($dbh_oracle);
		return true;
	}
	$r = oci_execute($stid);
	$e=oci_error($stid);
	if(function_exists('logDBQuery')){
		logDBQuery($query,$start,'oracleExecuteSQL','oracle');
	}
    if($params['setmodule']){
		oci_set_module_name($dbh_oracle, 'idle');
		oci_set_action($dbh_oracle, 'idle');
		oci_set_client_identifier($dbh_oracle, 'idle');
	}
	if (!$r){
		$out=array(
    		'function'=>"oracleExecuteSQL",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_execute',
    		'error'=>$e,
    		'query'=>$query
    	);
    	oci_free_statement($stid);
		oci_close($dbh_oracle);
		if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
	}
	oci_free_statement($stid);
	oci_close($dbh_oracle);
	return true;
}
//---------- begin function oracleGetActiveSessionCount
/**
* @describe returns and array of records
* @param params array - requires either -list or -table or a raw query instead of params
*	[-seconds] integer - seconds since  - LAST_CALL_ET
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return integer - number of sessions
* @usage
*	$cnt=oracleGetActiveSessionCount(array('-seconds'=>60));
*/
function oracleGetActiveSessionCount($params=array()){
	$query="
		SELECT
			count(*) cnt
		FROM v\$session sess
		WHERE
			sess.type='USER'
			and sess.status='ACTIVE'
	";
	if(isset($params['-seconds']) && isNum($params['-seconds'])){
		$query .= " AND sess.LAST_CALL_ET >= {$params['-seconds']}";
	}
	$recs=oracleQueryResults($query,$params);
	if(isset($recs[0]['cnt'])){return $recs[0]['cnt'];}
	return $query;
}
//---------- begin function oracleGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=oracleGetDBCount(array('-table'=>'states'));
*/
function oracleGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=oracleGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		$out=array(
    		'function'=>"oracleGetDBCount",
    		'error'=>$recs,
    	);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function oracleTruncateDBTable--------------------
/**
* @describe truncates the specified table
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=oracleTruncateDBTable('myschema.mytable');
*/
function oracleTruncateDBTable($table,$params=array()){
	return oracleExecuteSQL("truncate table {$table}",$params);
}
//---------- begin function oracleGetDBFields--------------------
/**
* @describe returns an array of fields for said table
* @param table string - table name
* @param params array - requires either -list or -table or a raw query instead of params
*	[-getmeta] string - table name.  Use this with other field/value params to filter the results
*	[-field] mixed - query record limit
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $fields=oracleGetDBFields('notes');
*/
function oracleGetDBFields($table,$params=array()){
	$info=oracleGetDBFieldInfo($table,$params);
	return array_keys($info);
}
//---------- begin function oracleGetDBFieldInfo--------------------
/**
* @describe returns an array containing type,length, and flags for each field in said table
* @param table string - table name
* @param params array - requires either -list or -table or a raw query instead of params
*	[-getmeta] string - table name.  Use this with other field/value params to filter the results
*	[-field] mixed - query record limit
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $fields=oracleGetDBFieldInfo('notes');
*/
function oracleGetDBFieldInfo($table,$params=array()){
	//connect
	$dbh_oracle=oracleDBConnect($params);
	//primary keys
	$pkeys=oracleGetDBTablePrimaryKeys($table,$params);
	//echo $table.printValue($pkeys);exit;
	$query="select * from {$table} where 0=".rand(1,1000);
	$stid = oci_parse($dbh_oracle, $query);
	if(!$stid){
		$out=array(
    		'function'=>"oracleGetDBFieldInfo",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_parse',
    		'error'=>oci_error($dbh_oracle),
    		'query'=>$query
    	);
    	oci_close($dbh_oracle);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
	}
	oci_execute($stid, OCI_DESCRIBE_ONLY);
	$ncols = oci_num_fields($stid);
	//echo "here".$ncols;exit;
	$fields=array();
	for ($i = 1; $i <= $ncols; $i++) {
		$name=oci_field_name($stid, $i);
		$field=array(
			'table'	=> $table,
			'_dbtable'	=> $table,
			'name'	=> $name,
			'_dbfield'	=> strtolower($name),
			'type'	=> oci_field_type($stid, $i),
			'precision'	=> oci_field_precision($stid, $i),
			'scale'	=> oci_field_scale($stid, $i),
			'length'	=> oci_field_size($stid, $i),
			//'type_raw'	=> oci_field_type_raw($stid, $i),
		);
		$field['_dbtype']=$field['_dbtype_ex']=strtolower($field['type']);
		$field['_dblength']=$field['length'];
		if($field['precision'] > 0){
			$field['_dbtype_ex']=strtolower("{$field['type']}({$field['precision']})");
		}
		elseif($field['length'] > 0 && preg_match('/(char|text|blob)/i',$field['_dbtype'])){
			$field['_dbtype_ex']=strtolower("{$field['type']}({$field['length']})");
		}
		$field['_dblength']=$field['length'];
		if(in_array($name,$pkeys)){
			$field['primary_key']=true;
		}
		else{
			$field['primary_key']=false;
		}
		$name=strtolower($name);
	    $fields[$name]=$field;
	}
	oci_free_statement($stid);
	oci_close($dbh_oracle);
	return $fields;
}
//---------- begin function oracleGetDBRecord--------------------
/**
* @describe returns a record based on params
* @param params array 
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=oracleGetDBRecord(array('-table'=>'states','code'=>'UT'));
*/
function oracleGetDBRecord($params=array()){
	$params['-limit']=1;
	$recs=oracleGetDBRecords($params);
	if(!isset($recs[0])){
		if(isset($params['-return_errors'])){
    		return json_encode($recs);
    	}
		debugValue($recs);
		return array();
	}
	return $recs[0];
}
//---------- begin function oracleGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=oracleGetDBRecordById('comments',7);
*/
function oracleGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "oracleGetDBRecordById Error: No Table";}
	if($id == 0){return "oracleGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=oracleGetDBRecord($recopts);
	return $rec;
}
//---------- begin function oracleEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=oracleEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function oracleEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("oracleEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("oracleEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("oracleEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("oracleEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return oracleEditDBRecord($params);
}
//---------- begin function oracleDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=oracleDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function oracleDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("oracleDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("oracleDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return oracleDelDBRecord($params);
}


//---------- begin function oracleGetDBRecords
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
*	<?=oracleGetDBRecords(array('-table'=>'notes'));?>
*	<?=oracleGetDBRecords("select * from myschema.mytable where ...");?>
*/
function oracleGetDBRecords($params){
	global $USER;
	global $CONFIG;
	if(empty($params['-table']) && !is_array($params)){
		$params=trim($params);
		if(preg_match('/^(select|exec|with|explain|returning|show|call)[\t\s\ \r\n]/i',$params)){
			//they just entered a query
			$query=$params;
			$params=array('-lobs'=>1);
		}
		else{
			$ok=oraclelExecuteSQL($params);
			return $ok;
		}
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
		$fields=oracleGetDBFieldInfo($params['-table'],$params);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!strlen(trim($v))){continue;}
			if(!isset($fields[$k])){continue;}
			//check for lobs
			if($fields[$k]['_dbtype']=='clob' && !isset($params['-lobs'])){$params['-lobs']=1;}
			if(is_array($params[$k])){
	            $params[$k]=implode(':',$params[$k]);
			}
	        $params[$k]=str_replace("'","''",$params[$k]);
	        $v=strtoupper($params[$k]);
	        if(isNum($v)){
	        	$ands[]="{$k} = {$v}";
	        }
	        else{
	        	$ands[]="upper({$k})='{$v}'";
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
	return oracleQueryResults($query,$params);
}
//---------- begin function oracleGetDBTables ----------
/**
* @describe returns all valid table names
* @param params array
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - valid table names 
*/
function oracleGetDBTables($params=array()){
	global $CONFIG;
	global $DATABASE;
	$owner=strtoupper($DATABASE[$CONFIG['db']]['dbschema']);
	if(!strlen($owner)){
		return array("ERROR: No dbschema specified");
	}
	$query=<<<ENDOFQUERY
		SELECT 
			owner,table_name,last_analyzed,num_rows,pct_free 
		FROM 
			all_tables 
		WHERE 
			owner ='{$owner}' 
			and tablespace_name not in ('SYS','SYSAUX','SYSTEM') 
			and status='VALID'
		ORDER BY 
			owner,table_name
		offset 0 rows fetch next 5000 rows only
ENDOFQUERY;
	$recs = oracleQueryResults($query,$params);
	$tables=array();
	foreach($recs as $rec){
		$tables[]="{$rec['owner']}.{$rec['table_name']}";
	}
	return $tables;
}
//---------- begin function oracleGetDBTablePrimaryKeys
/**
* @describe returns a list of primary key fields for specified table
* @param table string - table name
* @param params array
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - primary key fields
* @usage
*	$pkeys=oracleGetDBTablePrimaryKeys('people');?>
*/
function oracleGetDBTablePrimaryKeys($table,$params=array()){
	$parts=preg_split('/\./',$table);
	$table=array_pop($parts);
	$table=str_replace("'","''",$table);
	$table=strtoupper($table);
	if(count($parts)){
		$owner=array_pop($parts);
		$owner=str_replace("'","''",$owner);
		$owner=strtoupper($owner);
		$owner_filter="AND upper(cols.owner) = '{$owner}'";
	}
	else{$owner_filter='';}
	$query=<<<ENDOFQUERY
	SELECT
  		cols.column_name
		,cols.position
		,cons.status
		,cons.owner
	FROM all_constraints cons, all_cons_columns cols
	WHERE
		upper(cols.table_name) = '{$table}'
		{$owner_filter}
		AND cons.constraint_type = 'P'
		AND cons.constraint_name = cols.constraint_name
		AND cons.owner = cols.owner
	ORDER BY cols.position
ENDOFQUERY;
	$tmp = oracleQueryResults($query);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
//---------- begin function oracleEnumQueryResults ----------
/**
* @describe returns the results from a query resource
* @param $resource resource - resource handle from query call
* @param $params array - These can also be set in the CONFIG file with dbname_oracle,dbuser_oracle, and dbpass_oracle
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $recs array
* @usage $recs=oracleEnumQueryResults($resource);
*/
function oracleEnumQueryResults($res,$params=array()){
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
			return 'oracleEnumQueryResults error: Failed to open '.$params['-filename'];
			exit;
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	$i=0;
	$fetchopts=OCI_ASSOC+OCI_RETURN_NULLS;
	if(isset($params['-lobs'])){$fetchopts=OCI_ASSOC+OCI_RETURN_NULLS+OCI_RETURN_LOBS;}
	while ($row = oci_fetch_array($res, $fetchopts)) {
		$rec=array();
		foreach ($row as $field=>$val){
			$field=strtolower($field);
			if(is_resource($val)){
				oci_execute($val);
				//get the fields
				$xfields=array();
				$icount=oci_num_fields($val);
				for($i=1;$i<=$icount;$i++){
					$xfield=strtolower(oci_field_name($val,$i));
					$xfields[]=$xfield;
				}
				$rec[$field]=oracleEnumQueryResults($val,$params);
				if(!count($rec[$field]) && isset($params['-forceheader'])){
					$xrec=array();
					foreach($xfields as $xfield){
						$xrec[$xfield]='';
					}
					$rec[$field]=array($xrec);
				}
				oci_free_statement($val);
			}
			else{
				$rec[$field]=$val;
			}
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
		else{$recs[]=$rec;}
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
//---------- begin function oracleListRecords
/**
* @describe returns an html table of records from a oracle database. refer to databaseListRecords
*/
function oracleListRecords($params=array()){
	$params['-database']='oracle';
	//check for clobs
	if(isset($params['-table']) && !isset($params['-lobs'])){
		$fields=oracleGetDBFieldInfo($params['-table'],$params);
		foreach($fields as $k=>$info){
			if($fields[$k]['_dbtype']=='clob'){
				$params['-lobs']=1;
				break;
			}
		}
	}
	return databaseListRecords($params);
}

//---------- begin function oracleParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_oracle,dbuser_oracle, and dbpass_oracle
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=oracleParseConnectParams($params);
*/
function oracleParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(!is_array($params)){$params=array();}
	if(!isset($CONFIG['db']) && isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$CONFIG['db']=$_REQUEST['db'];
	}
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^oracle/i',$k)){unset($CONFIG[$k]);}
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
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_oracle'])){
			$params['-dbuser']=$CONFIG['dbuser_oracle'];
			$params['-dbuser_source']="CONFIG dbuser_oracle";
		}
		elseif(isset($CONFIG['oracle_dbuser'])){
			$params['-dbuser']=$CONFIG['oracle_dbuser'];
			$params['-dbuser_source']="CONFIG oracle_dbuser";
		}
		else{return "oracleParseConnectParams Error: No dbuser set. DB:{$CONFIG['db']}";}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_oracle'])){
			$params['-dbpass']=$CONFIG['dbpass_oracle'];
			$params['-dbpass_source']="CONFIG dbpass_oracle";
		}
		elseif(isset($CONFIG['oracle_dbpass'])){
			$params['-dbpass']=$CONFIG['oracle_dbpass'];
			$params['-dbpass_source']="CONFIG oracle_dbpass";
		}
		else{return 'oracleParseConnectParams Error: No dbpass set';}
	}
	else{
		$params['-dbpass_source']="passed in";
	}
	//
	if(isset($CONFIG['oracle_single'])){$params['-single']=$CONFIG['oracle_single'];}
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['oracle_connect'])){
			$params['-connect']=$CONFIG['oracle_connect'];
			$params['-connect_source']="CONFIG oracle_connect";
		}
		elseif(isset($CONFIG['connect_oracle'])){
			$params['-connect']=$CONFIG['connect_oracle'];
			$params['-connect_source']="CONFIG connect_oracle";
		}
		else{
			//build connect
			$dbhost='';
			if(isset($params['-dbhost'])){$dbhost=$params['-dbhost'];}
			elseif(isset($CONFIG['oracle_dbhost'])){$dbhost=$CONFIG['oracle_dbhost'];}
			elseif(isset($CONFIG['dbhost_oracle'])){$dbhost=$CONFIG['dbhost_oracle'];}
			if(!strlen($dbhost)){return $params;}
			$protocol='TCP';
			if(isset($params['-protocol'])){$tcp=$params['-protocol'];}
			elseif(isset($CONFIG['oracle_protocol'])){$tcp=$CONFIG['oracle_protocol'];}
			elseif(isset($CONFIG['protocol_oracle'])){$tcp=$CONFIG['protocol_oracle'];}
			$port='1521';
			if(isset($params['-port'])){$port=$params['-port'];}
			elseif(isset($params['-dbport'])){$port=$params['-dbport'];}
			elseif(isset($CONFIG['oracle_port'])){$port=$CONFIG['oracle_port'];}
			elseif(isset($CONFIG['port_oracle'])){$port=$CONFIG['port_oracle'];}
			$connect_data='';
			//sid - identify the Oracle8 database instance by its Oracle System Identifier (SID)
			if(isset($params['-sid'])){$connect_data.="(SID={$params['-sid']})";}
			elseif(isset($params['-dbsid'])){$connect_data.="(SID={$params['-dbsid']})";}
			elseif(isset($CONFIG['oracle_sid'])){$connect_data.="(SID={$CONFIG['oracle_sid']})";}
			elseif(isset($CONFIG['sid_oracle'])){$connect_data.="(SID={$CONFIG['sid_oracle']})";}
			//service_name - identify the Oracle9i or Oracle8 database service to access
			if(isset($params['-service_name'])){$connect_data.="(SERVICE_NAME={$params['-service_name']})";}
			elseif(isset($params['-dbservice_name'])){$connect_data.="(SERVICE_NAME={$params['-dbservice_name']})";}
			elseif(isset($CONFIG['oracle_service_name'])){$connect_data.="(SERVICE_NAME={$CONFIG['oracle_service_name']})";}
			elseif(isset($CONFIG['service_name_oracle'])){$connect_data.="(SERVICE_NAME={$CONFIG['service_name_oracle']})";}
			//instance_name - identify the database instance to access
			if(isset($params['-instance_name'])){$connect_data.="(INSTANCE_NAME={$params['-instance_name']})";}
			elseif(isset($CONFIG['oracle_instance_name'])){$connect_data.="(INSTANCE_NAME={$CONFIG['oracle_instance_name']})";}
			elseif(isset($CONFIG['instance_name_oracle'])){$connect_data.="(INSTANCE_NAME={$CONFIG['instance_name_oracle']})";}
			//server_name
			if(isset($params['-server_name'])){$connect_data.="(SERVER_NAME={$params['-server_name']})";}
			elseif(isset($CONFIG['oracle_server_name'])){$connect_data.="(SERVER_NAME={$CONFIG['oracle_server_name']})";}
			elseif(isset($CONFIG['server_name_oracle'])){$connect_data.="(SERVER_NAME={$CONFIG['server_name_oracle']})";}
			//global_name - identify the Oracle Rdb database.
			if(isset($params['-global_name'])){$connect_data.="(GLOBAL_NAME={$params['-global_name']})";}
			elseif(isset($CONFIG['oracle_global_name'])){$connect_data.="(GLOBAL_NAME={$CONFIG['oracle_global_name']})";}
			elseif(isset($CONFIG['global_name_oracle'])){$connect_data.="(GLOBAL_NAME={$CONFIG['global_name_oracle']})";}
			//server - instruct the listener to connect the client to a specific type of service handler, dedicated or shared
			if(isset($params['-server'])){$connect_data.="(SERVER={$params['-server']})";}
			elseif(isset($CONFIG['oracle_server'])){$connect_data.="(SERVER={$CONFIG['oracle_server']})";}
			elseif(isset($CONFIG['server_oracle'])){$connect_data.="(SERVER={$CONFIG['server_oracle']})";}
			//rdb_database - specify the file name of an Oracle Rdb database
			if(isset($params['-rdb_database'])){$connect_data.="(RDB_DATABASE={$params['-rdb_database']})";}
			elseif(isset($CONFIG['oracle_rdb_database'])){$connect_data.="(RDB_DATABASE={$CONFIG['oracle_rdb_database']})";}
			elseif(isset($CONFIG['rdb_database_oracle'])){$connect_data.="(RDB_DATABASE={$CONFIG['rdb_database_oracle']})";}
			if(!strlen($connect_data)){return $params;}
			$params['-connect']="(DESCRIPTION=(ADDRESS=(PROTOCOL = {$protocol})(HOST = {$dbhost})(PORT={$port}))(CONNECT_DATA={$connect_data}))";
			$params['-connect_source']="tcp,host,port";
		}
	}
	else{
		$params['-connect_source']="passed in";
	}
	return $params;
}

//---------- begin function oracleQueryResults ----------
/**
* @describe returns the oracle records from query
* @param $query string - SQL query to execute
* @param [$params] array - 
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[module] - module to set query against. Defaults to 'waSQL'
* 	[action] - action to set query against. Defaults to 'oracleQueryResults'
* 	[id] - identifier to set query against. Defaults to current user
* 	[setmodule] boolean - set to false to not set module, action, and id. Defaults to true
* 	[-idcolumn] boolean - set to true to include row number as _id column
*	[-lobs] boolean - add OCI_RETURN_LOBS to the oci_fetch to return lobs in the data
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return array - returns records
*/
function oracleQueryResults($query='',$params=array()){
	global $USER;
	//connect
	$dbh_oracle=oracleDBConnect($params);
	//check for -process
	if(isset($params['-process']) && !function_exists($params['-process'])){
		$out=array(
    		'function'=>"oracleQueryResults",
    		'connection'=>$dbh_oracle,
    		'error'=>'Invalid process function',
    		'function'=>$params['-process'],
    		'query'=>$query
    	);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
		return false;
	}
	oci_rollback($dbh_oracle);
	if(!isset($params['setmodule'])){$params['setmodule']=true;}
	$stid = oci_parse($dbh_oracle, $query);
	if(!is_resource($stid)){
		$out=array(
    		'function'=>"oracleQueryResults",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_parse',
    		'error'=>oci_error($dbh_oracle),
    		'query'=>$query
    	);
    	oci_close($dbh_oracle);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
	}
	if($params['setmodule']){
		if(!isset($params['module'])){$params['module']='waSQL';}
		if(!isset($params['action'])){
			if(isset($_REQUEST['AjaxRequestUniqueId'])){$params['action']='oracleQueryResults (AJAX): '.$_REQUEST['AjaxRequestUniqueId'];}
			else{$params['action']='oracleQueryResults';}
		}
		if(!isset($params['id'])){$params['id']=$USER['username'];}
		oci_set_module_name($dbh_oracle, $params['module']);
		oci_set_action($dbh_oracle, $params['action']);
		oci_set_client_identifier($dbh_oracle, $params['id']);
	}
	// check for non-select query
	$start=microtime(true);
	if(preg_match('/^(select|exec|with|explain|returning|show|call)/is',trim($query))){
		$params['-lobs']=1;
	}
	elseif(preg_match('/^(create|drop|grant|truncate|update|insert|alter)/is',trim($query))){
		$r = oci_execute($stid,OCI_COMMIT_ON_SUCCESS);
		$e=oci_error($stid);
		if(function_exists('logDBQuery')){
			logDBQuery($query,$start,'oracleQueryResults','oracle');
		}
    	oci_free_statement($stid);
    	if($params['setmodule']){
			oci_set_module_name($dbh_oracle, 'idle');
			oci_set_action($dbh_oracle, 'idle');
			oci_set_client_identifier($dbh_oracle, 'idle');
		}
		oci_close($dbh_oracle);
		if (!$r){
			$out=array(
	    		'function'=>"oracleQueryResults",
	    		'connection'=>$dbh_oracle,
	    		'action'=>'oci_execute',
	    		'error'=>$e,
	    		'query'=>$query
	    	);
	    	if(isset($params['-return_errors'])){
	    		return $out;
	    	}
			debugValue($out);
	    	return false;
		}
		return true;
	}
	$r = oci_execute($stid);
	$e=oci_error($stid);
	if(function_exists('logDBQuery')){
		logDBQuery($query,$start,'oracleQueryResults','oracle');
	}
	if($params['setmodule']){
		oci_set_module_name($dbh_oracle, 'idle');
		oci_set_action($dbh_oracle, 'idle');
		oci_set_client_identifier($dbh_oracle, 'idle');
	}
	if (!$r) {
		$out=array(
    		'function'=>"oracleQueryResults",
    		'connection'=>$dbh_oracle,
    		'action'=>'oci_execute',
    		'error'=>$e,
    		'query'=>$query
    	);
    	oci_free_statement($stid);
		oci_close($dbh_oracle);
    	if(isset($params['-return_errors'])){
    		return $out;
    	}
		debugValue($out);
    	return false;
	}
	//read results into a recordset array	
	$recs=oracleEnumQueryResults($stid,$params);
	if(!count($recs) && isset($params['-forceheader'])){
		$fields=array();
		for($i=1;$i<=oci_num_fields($stid);$i++){
			$field=strtolower(oci_field_name($stid,$i));
			$fields[]=$field;
		}
		oci_free_statement($stid);
		$rec=array();
		foreach($fields as $field){
			$rec[$field]='';
		}
		$recs=array($rec);
	}
	oci_free_statement($stid);
	oci_close($dbh_oracle);
	return $recs;
}
//---------- begin function oracleNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function oracleNamedQuery($name){
	global $CONFIG;
	global $DATABASE;
	if(!isset($CONFIG['db']) && isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$CONFIG['db']=$_REQUEST['db'];
	}
	switch(strtolower($name)){
		case 'running_queries':
			return <<<ENDOFQUERY
SELECT
	a.sid, 
	a.username,
	b.sql_id, 
	b.sql_fulltext 
FROM v\$session a, v\$sql b
WHERE 
	a.sql_id = b.sql_id 
	and a.status = 'ACTIVE' 
	and a.username != 'SYS'
ENDOFQUERY;
		break;
		case 'sessions':
			return <<<ENDOFQUERY
SELECT 
	sid,
    serial#,
    osuser,
    machine,
    program,
    module
from v\$session
ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY
SELECT 
	b.owner, 
	b.object_name, 
	a.oracle_username, 
	a.os_user_name  
FROM v\$locked_object a, all_objects b
WHERE 
	a.object_id = b.object_id
ENDOFQUERY;
		break;
		case 'functions':
			$owner=strtoupper($DATABASE[$CONFIG['db']]['dbschema']);
			return <<<ENDOFQUERY
SELECT 
	owner, object_name, object_id, data_object_id, subobject_name status,created, last_ddl_time, timestamp
FROM ALL_OBJECTS 
WHERE OBJECT_TYPE = 'FUNCTION' 
and owner = '{$owner}'
ENDOFQUERY;
		break;
		case 'procedures':
			$owner=strtoupper($DATABASE[$CONFIG['db']]['dbschema']);
			return <<<ENDOFQUERY
SELECT 
	owner, object_name, object_id, data_object_id, subobject_name status,created, last_ddl_time, timestamp
FROM ALL_OBJECTS 
WHERE OBJECT_TYPE = 'PROCEDURE' 
and owner = '{$owner}'
ENDOFQUERY;
		break;
		case 'packages':
			$owner=strtoupper($DATABASE[$CONFIG['db']]['dbschema']);
			return <<<ENDOFQUERY
SELECT 
	owner, object_name, object_id, data_object_id, subobject_name status,created, last_ddl_time, timestamp
FROM ALL_OBJECTS 
WHERE OBJECT_TYPE = 'PACKAGE' 
and owner = '{$owner}'
ENDOFQUERY;
		break;
		case 'tables':
			$owner=strtoupper($DATABASE[$CONFIG['db']]['dbschema']);
			return <<<ENDOFQUERY
select 
	t.segment_name as name,
	r.row_count,
	c.field_count,
	round(t.bytes/1024/1024,2) as mb_size,
	t.segment_type,
	r.logging,
	r.backed_up,
	r.partitioned  
from dba_segments t,
(select count(*) field_count,table_name from all_tab_cols where owner='{$owner}' group by table_name ) c,
(select num_rows as row_count,table_name,logging,backed_up,partitioned from dba_tables where owner='{$owner}' ) r
where 
	t.segment_name =c.table_name
	and c.table_name=r.table_name
	and owner='{$owner}' and segment_type in ('TABLE','TABLE_PARTITION') 
order by round(bytes/1024/1024,2) desc
ENDOFQUERY;
		break;
	}
}
function oracleIsReservedWord($word){
	$word=strtolower($word);
	$reserved=preg_split('/[\r\n]+/',trim(oracleReservedWordsList()));
	if(in_array($word,$reserved)){return true;}
	return false;
}
function oracleReservedWordsList(){
	return <<<ENDOFLIST
access
account
activate
add
admin
advise
after
all
all_rows
allocate
alter
analyze
and
any
archive
archivelog
array
as
asc
at
audit
authenticated
authorization
autoextend
automatic
backup
become
before
begin
between
bfile
bitmap
blob
block
body
by
cache
cache_instances
cancel
cascade
cast
cfile
chained
change
char
char_cs
character
check
checkpoint
choose
chunk
clear
clob
clone
close
close_cached_open_cursors
cluster
coalesce
column
columns
comment
commit
committed
compatibility
compile
complete
composite_limit
compress
compute
connect
connect_time
constraint
constraints
contents
continue
controlfile
convert
cost
cpu_per_call
cpu_per_session
create
current
current_schema
curren_user
cursor
cycle
dangling
database
datafile
datafiles
dataobjno
date
dba
dbhigh
dblow
dbmac
deallocat
debug
dec
decimal
declare
default
deferrable
deferred
degree
delete
deref
desc
directory
disable
disconnect
dismount
distinct
distributed
dml
double
drop
dump
each
else
enable
end
enforce
entry
escape
except
exceptions
exchange
excluding
exclusive
execute
exists
expire
explain
extent
extents
externally
failed_login_attempts
false
fast
file
first_rows
flagger
float
flob
flush
for
force
foreign
freelist
freelists
from
full
function
global
globally
global_name
grant
group
groups
hash
hashkeys
having
header
heap
identified
idgenerators
idle_time
if
immediate
in
including
increment
index
indexed
indexes
indicator
ind_partition
initial
initially
initrans
insert
instance
instances
instead
int
integer
intermediate
intersect
into
is
isolation
isolation_level
keep
key
kill
label
layer
less
level
library
like
limit
link
list
lob
local
lock
locked
log
logfile
logging
logical_reads_per_call
logical_reads_per_session
long
manage
master
max
maxarchlogs
maxdatafiles
maxextents
maxinstances
maxlogfiles
maxloghistory
maxlogmembers
maxsize
maxtrans
maxvalue
min
member
minimum
minextents
minus
minvalue
mlslabel
mls_label_format
mode
modify
mount
move
mts_dispatchers
multiset
national
nchar
nchar_cs
nclob
needed
nested
network
new
next
noarchivelog
noaudit
nocache
nocompress
nocycle
noforce
nologging
nomaxvalue
nominvalue
none
noorder
nooverride
noparallel
noparallel
noreverse
normal
nosort
not
nothing
nowait
null
number
numeric
nvarchar2
object
objno
objno_reuse
of
off
offline
oid
oidindex
old
on
online
only
opcode
open
optimal
optimizer_goal
option
or
order
organization
oslabel
overflow
own
package
parallel
partition
password
password_grace_time
password_life_time
password_lock_time
password_reuse_max
password_reuse_time
password_verify_function
pctfree
pctincrease
pctthreshold
pctused
pctversion
percent
permanent
plan
plsql_debug
post_transaction
precision
preserve
primary
prior
private
private_sga
privilege
privileges
procedure
profile
public
purge
queue
quota
range
raw
rba
read
readup
real
rebuild
recover
recoverable
recovery
ref
references
referencing
refresh
rename
replace
reset
resetlogs
resize
resource
restricted
return
returning
reuse
reverse
revoke
role
roles
rollback
row
rowid
rownum
rows
rule
sample
savepoint
sb4
scan_instances
schema
scn
scope
sd_all
sd_inhibit
sd_show
segment
seg_block
seg_file
select
sequence
serializable
session
session_cached_cursors
sessions_per_user
set
share
shared
shared_pool
shrink
size
skip
skip_unusable_indexes
smallint
snapshot
some
sort
specification
split
sql_trace
standby
start
statement_id
statistics
stop
storage
store
structure
successful
switch
sys_op_enforce_not_null$
sys_op_ntcimg$
synonym
sysdate
sysdba
sysoper
system
table
tables
tablespace
tablespace_no
tabno
temporary
than
the
then
thread
timestamp
time
to
toplevel
trace
tracing
transaction
transitional
trigger
triggers
true
truncate
tx
type
ub2
uba
uid
unarchived
undo
union
unique
unlimited
unlock
unrecoverable
until
unusable
unused
updatable
update
usage
use
user
using
validate
validation
value
values
varchar
varchar2
varying
view
when
whenever
where
with
without
work
write
writedown
writeup
xid
year
zone
ENDOFLIST;
}