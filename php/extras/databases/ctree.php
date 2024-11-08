<?php
/*
	ctree.php - a collection of cTREE Database functions for use by WaSQL.
	
	References:
		https://www.yumpu.com/en/document/read/30279025/sql-reference-guide-cove-systems
		https://docs.faircom.com/doc/sqlref/sqlref.pdf
*/

ini_set('max_execution_time', 86400);
set_time_limit(86400);

//---------- begin function ctreeGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=ctreeGetAllTableFields();
syscolumns fields
	charset	nvarchar
	col	nvarchar
	collation	nvarchar
	coltype	nvarchar
	dflt_value	nvarchar
	id	integer
	logicalid	integer
	nullflag	nvarchar
	owner	nvarchar
	scale	integer
	tbl	nvarchar
	width	integer
*/
function ctreeGetAllTableFields($schema=''){
	$ok=dbSetLast(array(
		'function'=>'ctreeGetAllTableFields',
		'p1'=>$schema,
	));
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'ctreeGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	$query=<<<ENDOFQUERY
		SELECT
			sc.tbl as table_name, 
			sc.col as field_name,
			sc.coltype as type_name
		FROM admin.syscolumns sc, admin.systables st
		WHERE sc.tbl = st.tbl AND st.tbltype != 'S'
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		//$field=strtolower($rec['field_name']);
		//$type=strtolower($rec['type_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	ksort($databaseCache[$cachekey]);
	return $databaseCache[$cachekey];
}
//---------- begin function ctreeGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=ctreeGetAllTableIndexes();
colname
id
idxcompress
idxmethod
idxname
idxorder
idxowner
idxsegid
idxseq
idxtype
rssid
tbl
tblowner
*/
function ctreeGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'ctreeGetAllTableIndexes');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	//key_name,column_name,seq_in_index,non_unique
	$query=<<<ENDOFQUERY
	SELECT
		tbl as table_name,
		idxname as key_name,
		colname as column_name,
		idxtype as index_type,
		idxseq as seq_in_index,
		tblowner as table_owner
		FROM admin.sysindexes
		WHERE tblowner='admin'
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	//group by table and key
	$indexes=array();
	foreach($recs as $rec){
		$key=$rec['table_name'].$rec['key_name'];
		$indexes[$key][]=$rec;
	}
	ksort($indexes);
	//echo printValue($indexes);exit;
	//json_agg
	$recs=array();
	foreach($indexes as $key=>$krecs){
		$index_keys=array();
		$krecs=sortArrayByKeys($krecs, array('seq_in_index'=>SORT_ASC));
		foreach($krecs as $krec){$index_keys[]=$krec['column_name'];}
		$is_unique=$krecs[0]['index_type']=='U'?1:0;
		$rec=array(
			'table_name'=>$krecs[0]['table_name'],
			'key_name'=>$krecs[0]['key_name'],
			'index_keys'=>json_encode($index_keys),
			'is_unique'=>$is_unique
		);
		$recs[]=$rec;
	}
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function ctreeDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
*	[-host] - ctree server to connect to
* 	[-dbname] - name of database.
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return connection resource and sets the global $dbh_ctree variable.
* @usage $dbh_ctree=ctreeDBConnect($params);
*/
function ctreeDBConnect($params=array()){
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=ctreeParseConnectParams($params);
	if(isset($params['-connect'])){
		$connect_name=$params['-connect'];
	}
	elseif(isset($params['-dbname'])){
		$connect_name=$params['-dbname'];
	}
	else{
		echo "ctreeDBConnect error: no dbname or connect param".printValue($params);
		exit;
	}
	//ctree does not support pooling do it in the ODBC Connection manager
	//$params['-single']=1;
	//echo printValue($params);exit;
	if(isset($params['-single'])){
		if(isset($params['-cursor'])){
			$dbh_ctree_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_ctree_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'] );
		}
		if(!is_resource($dbh_ctree_single)){
			$err=odbc_errormsg();
			$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
			echo "ctreeDBConnect single connect error:{$err}".printValue($params);
			if(is_dir('c:/bin') && stringContains($err,'Maximum users exceeded')){
				//Maximum users exceeded
				$ok=file_put_contents("C:\bin\ctree_failed.txt", $err);
			}
			exit;
		}
		return $dbh_ctree_single;
	}
	global $dbh_ctree;
	if(is_resource($dbh_ctree)){return $dbh_ctree;}

	try{
		if(isset($params['-cursor'])){
			$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass']);
		}
		if(!is_resource($dbh_ctree)){
			//wait a few seconds and try again
			sleep(2);
			if(isset($params['-cursor'])){
				$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
			}
			else{
				$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'] );
			}
			if(!is_resource($dbh_ctree)){
				$err=odbc_errormsg();
				$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
				echo "ctreeDBConnect error:{$err}".printValue($params);
				exit;
			}
		}
		return $dbh_ctree;
	}
	catch (Exception $e) {
		echo "ctreeDBConnect exception" . printValue($e);
		exit;

	}
}
function ctreeDBConnectOLD(){
	$ok=dbSetLast(array(
		'function'=>'ctreeDBConnect',
	));
	$params=ctreeParseConnectParams();
	if(!isset($params['-connect'])){
		$ok=dbSetLast(array('error'=>"missing -connect"));
		debugValue(dbGetLast());
		return false;
	}
	global $dbh_ctree;
	if(is_object($dbh_ctree) || is_resource($dbh_ctree)){return $dbh_ctree;}
	//try a few times to connect
	$tries=0;
	$exc='';
	while($tries < 5){
		$dbh_ctree = odbc_connect($params['-connect'],$params['-dbuser'],$params['-dbpass']);
		if(is_object($dbh_ctree) || is_resource($dbh_ctree)){return $dbh_ctree;}
		$tries+=1;
		sleep(5);
	}
	if(!is_object($dbh_ctree) && !is_resource($dbh_ctree)){
		$ok=dbSetLast(array('error'=>odbc_errormsg()));
		debugValue(dbGetLast());
		return false;
	}
}
//---------- begin function ctreeExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* @return boolean returns true if query succeeded
* @usage $ok=ctreeExecuteSQL("truncate table abc");
*/
function ctreeExecuteSQL($query,$return_error=1){
	if(!commonStrlen($query)){return 0;}
	global $USER;
	$ok=dbSetLast(array(
		'function'=>'ctreeExecuteSQL',
		'p1'=>$query,
		'p2'=>$return_error,
	));
	global $dbh_ctree;
	$dbh_ctree=ctreeDBConnect();
	if(commonStrlen(dbGetLast('error'))){return 0;}
	$ok=dbSetLast(array('query'=>$query));
	if($resource = odbc_prepare($dbh_ctree, $query)){
		if(odbc_execute($resource)){
			if(is_resource($resource)){odbc_free_result($resource);}
			$resource=null;
			if(is_resource($dbh_ctree) || is_object($dbh_ctree)){odbc_close($dbh_ctree);}
			$dbh_ctree=null;
			return true;
		}
	}
	odbc_close($dbh_ctree);
	$ok=dbSetLast(array('error'=>odbc_errormsg()));
	debugValue(dbGetLast());
	return null;
}
//---------- begin function ctreeGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=ctreeGetDBCount(array('-table'=>'states'));
*/
function ctreeGetDBCount($params=array()){
	if(!isset($params['-table'])){return null;}
	if(!stringContains($params['-table'],'.')){
		$schema=ctreeGetDBSchema();
		if(commonStrlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	//echo printValue($params);exit;
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	//$params['-debug']=1;
	$recs=ctreeGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		return 0;
	}
	return $recs[0]['cnt'];
}

//---------- begin function ctreeGetDBFields ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=ctreeGetDBFieldInfo('test');
*/
function ctreeGetDBFields($table,$allfields=0){
	$table=strtolower($table);
	$query=<<<ENDOFQUERY
		SELECT
			sc.tbl as tablename, 
			sc.col as column,
			sc.coltype as datatype, 
			sc.width as datasize
		FROM admin.syscolumns sc, admin.systables st
		WHERE sc.tbl = st.tbl AND st.tbltype = 'S'
		and sc.tbl = '{$table}'
		ORDER BY sc.tbl, sc.col
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	//echo $query.printValue($recs)
	$fields=array();
	foreach($recs as $rec){
		$fields[]=$rec['column'];
	}
	return $fields;
}
//---------- begin function ctreeGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=ctreeGetTableDDL('admin.sample');
*/
function ctreeGetTableDDL($table,$schema='admin'){
	$table=strtoupper($table);
	if(!strlen($schema)){
		$schema=ctreeGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('ctreeGetTableDDL error: schema is not defined in config.xml');
		return null;
	}
	$schema=strtolower($schema);
	$table=strtolower($table);

	$fieldinfo=ctreeGetDBFieldInfo($table);
	$fields=array();
	foreach($fieldinfo as $field=>$info){
		$fld=" {$info['_dbfield']} {$info['_dbtype_ex']}";
		//primary key
		if(isset($info['findex']) && strtoupper($info['findex'])=='P'){
			$fld.=' PRIMARY KEY';
		}
		//nullable
		if(isset($info['nullable']) && strtoupper($info['nullable'])=='N'){
			$fld.=' NOT NULL';
		}
		else{
			$fld.=' NULL';
		}
		if(strlen($info['default'])){
			if(stringBeginsWith($info['default'],'nextval(')){
				if($info['_dbtype']=='bigint'){
					$fld=str_replace(' bigint',' bigserial',$fld);
				}
				elseif($info['_dbtype']=='int'){
					$fld=str_replace(' int',' serial',$fld);
				}
			}
			else{
				$fld.=" DEFAULT {$info['default']}";
			}
		}
		$fields[]=$fld;
	}
	$ddl="CREATE TABLE {$schema}.{$table} (".PHP_EOL;
	$ddl.=implode(','.PHP_EOL,$fields);
	$ddl.=PHP_EOL.')'.PHP_EOL;
	return $ddl;
}
//---------- begin function ctreeGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* @return boolean returns true on success
* @usage $fieldinfo=ctreeGetDBFieldInfo('test');
*/
function ctreeGetDBFieldInfo($table){
	$table=strtolower($table);
	$table=str_replace('admin.','',$table);
	$query=<<<ENDOFQUERY
	SELECT
		c.col
		,c.coltype
		,c.width
		,c.scale
		,c.nullflag
		,max(case when i.idxtype='U' then 'P' when length(i.idxtype) > 0 then 'I' else ' ' end) as findex
	FROM
		admin.syscolumns c
		left outer join admin.sysindexes i on c.col=i.colname and i.tbl=c.tbl
	WHERE
		c.tbl='{$table}'
	GROUP BY
		c.col
		,c.coltype
		,c.width
		,c.scale
		,c.nullflag
	ORDER BY 6 desc,1
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	//echo $query.printValue($recs);exit;
	$fields=array();
	foreach($recs as $rec){
	    $fieldname = strtolower($rec['col']);
		$field=array(
			'_dbtable'	=> $table,
			'table'		=> $table,
			'name'		=> $fieldname,
		 	'_dbfield'	=> strtolower($fieldname),
		 	'_dbtype'	=> $rec['coltype'],
			'length'	=> $rec['width'],
			'num'		=> $rec['width'],
			'size'		=> $rec['scale'],
			'nullable'	=> $rec['nullflag'],
			'findex'	=> trim($rec['findex'])
		);
		$field['_dblength']=$field['length'];
		$field['_dbtype']=$field['_dbtype_ex']=$field['type']=strtolower($field['_dbtype']);
		if($field['size'] > 0){
			$field['_dbtype_ex']=strtolower("{$field['_dbtype']}({$field['size']})");
		}
		elseif($field['length'] > 0){
			$field['_dbtype_ex']=strtolower("{$field['_dbtype']}({$field['length']})");
		}
		$fields[$fieldname]=$field;
	}
	ksort($fields);
	//meta fields?
	$databaseCache['ctreeGetDBFieldInfo'][$cachekey]=$fields;
	return $databaseCache['ctreeGetDBFieldInfo'][$cachekey];
}
function ctreeGetDBIndexes($tablename=''){
	return ctreeGetDBTableIndexes($tablename);
}
function ctreeGetDBTableIndexes($tablename=''){
	//key_name,column_name,seq_in_index,non_unique
	$query=<<<ENDOFQUERY
		SELECT
			idxname as key_name,
			colname as column_name,
			idxtype as index_type,
			idxseq as seq_in_index
		FROM admin.sysindexes
		WHERE tbl='{$tablename}'
		ORDER BY idxname,idxseq
ENDOFQUERY;
	return ctreeQueryResults($query);
}
//---------- begin function ctreeGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=ctreeGetDBRecord(array('-table'=>'tesl));
*/
function ctreeGetDBRecord($params=array()){
	$recs=ctreeGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function ctreeGetDBRecords
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
*	ctreeGetDBRecords(array('-table'=>'notes'));
*	ctreeGetDBRecords("select * from myschema.mytable where ...");
*/
function ctreeGetDBRecords($params){
	$ok=dbSetLast(array(
		'function'=>'ctreeGetDBRecords',
		'p1'=>$params,
	));
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
			$ok=ctreeExecuteSQL($params);
			return $ok;
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
	}
	else{
		if(empty($params['-table'])){
			$ok=dbSetLast(array('error'=>"no table"));
			debugValue(dbGetLast());
	    	return null;
		}
		//check for schema name
		if(!stringContains($params['-table'],'.')){
			$schema=ctreeGetDBSchema();
			if(commonStrlen($schema)){
				$params['-table']="{$schema}.{$params['-table']}";
			}
		}
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
		$fields=ctreeGetDBFieldInfo($params['-table'],$params);
		//echo printValue($fields);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!commonStrlen(trim($v))){continue;}
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
		//offset and limit
		$paginate='';
    	if(!isset($params['-nolimit'])){
	    	$offset=isset($params['-offset'])?$params['-offset']:0;
	    	$limit=25;
	    	if(!empty($params['-limit'])){$limit=$params['-limit'];}
	    	elseif(!empty($CONFIG['paging'])){$limit=$CONFIG['paging'];}
	    	$paginate = "TOP {$limit} SKIP {$offset}";
	    }

	    $query="SELECT {$paginate} {$params['-fields']} FROM {$params['-table']} {$wherestr}";
	    if(isset($params['-order'])){
    		$query .= " ORDER BY {$params['-order']}";
    	}
	}
	if(isset($params['-debug'])){return $query;}
	return ctreeQueryResults($query,$params);
}

//---------- begin function ctreeIsDBTable ----------
/**
* @describe returns true if table already exists
* @param table string
* @return boolean
* @usage if(ctreeIsDBTable('_users')){...}
*/
function ctreeIsDBTable($table='',$force=0){
	global $databaseCache;
	$table=strtolower($table);
	if($force==0 && isset($databaseCache['ctreeIsDBTable'][$table])){
		return $databaseCache['ctreeIsDBTable'][$table];
	}
	$query=<<<ENDOFQUERY
		SELECT trim(tbl) as tbl
		FROM admin.systables
		where tbl='{$table}'
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	if(isset($recs[0]['tbl'])){
		$databaseCache['ctreeIsDBTable'][$table]=true;
	}
	else{
		$databaseCache['ctreeIsDBTable'][$table]=false;
	}
	return $databaseCache['ctreeIsDBTable'][$table];
}

//---------- begin function ctreeGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* @return array returns array of tables
* @usage $tables=ctreeGetDBTables();
*/
function ctreeGetDBTables($params=array()){
	$query=<<<ENDOFQUERY
		SELECT trim(tbl) as tbl
		FROM admin.systables
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	$tables=array();
	foreach($recs as $rec){
		$tables[]=$rec['tbl'];
	}
	sort($tables);
	return $tables;
}
//---------- begin function ctreeGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param table string - specified table
* @return array returns array of primary key fields
* @usage $fields=ctreeGetDBTablePrimaryKeys($table);
*/
function ctreeGetDBTablePrimaryKeys($table){
	$query=<<<ENDOFQUERY
		SELECT
			colname
		FROM admin.sysindexes
		WHERE
			tbl='{$table}'
			and idxtype='U'
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	$pkeys=array();
	foreach($recs as $rec){
		$pkeys[]=$rec['colname'];
	}
	return $recs;
	
}
function ctreeGetDBSchema(){
	global $CONFIG;
	$params=ctreeParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	if(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['ctree_dbschema'])){return $CONFIG['ctree_dbschema'];}
	elseif(isset($CONFIG['ctree_schema'])){return $CONFIG['ctree_schema'];}
	return '';
}

function ctreeGetConfigValue($field){
	//dbschema, dbname
	global $CONFIG;
	switch(strtolower($CONFIG['dbtype'])){
		case 'ctree':
			if(isset($CONFIG[$field])){return $CONFIG[$field];}
			elseif(isset($CONFIG["ctree_{$field}"])){return $CONFIG["ctree_{$field}"];}
		break;
		default:
			if(isset($CONFIG["ctree_{$field}"])){return $CONFIG["ctree_{$field}"];}
		break;
	}
	return null;
}
//---------- begin function ctreeListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function ctreeListRecords($params=array()){
	$params['-database']='ctree';
	return databaseListRecords($params);
}
//---------- begin function ctreeParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
*	[-host] - ctree server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=ctreeParseConnectParams($params);
*/
function ctreeParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	//default cursor to SQL_CUR_USE_ODBC
	$params['-cursor']=SQL_CUR_USE_ODBC;
	//default pool to 1
	$params['-pool']=10;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^ctree/i',$k)){unset($CONFIG[$k]);}
		}
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			switch(strtolower($k)){
				case 'cursor':
				case 'dbcursor':
					switch(strtoupper($v)){
						case 'SQL_CUR_USE_ODBC':$params['-cursor']=SQL_CUR_USE_ODBC;break;
						case 'SQL_CUR_USE_IF_NEEDED':$params['-cursor']=SQL_CUR_USE_IF_NEEDED;break;
						case 'SQL_CUR_USE_DRIVER':$params['-cursor']=SQL_CUR_USE_DRIVER;break;
					}
				break;
				case 'pool':
				case 'dbpool':
					$params['-pool']=(integer)$v;
				break;
				default:
					$params["-{$k}"]=$v;
				break;
			}
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
	//echo "HERE".printValue($params);exit;
	$params['-single']=0;
	if(isset($CONFIG['dbpool'])){
		$params['-dbpool']=$CONFIG['dbpool'];
	}
	if(isctree()){
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
		if(isset($CONFIG['dbhost_ctree'])){
			$params['-dbhost']=$CONFIG['dbhost_ctree'];
			//$params['-dbhost_source']="CONFIG dbhost_ctree";
		}
		elseif(isset($CONFIG['ctree_dbhost'])){
			$params['-dbhost']=$CONFIG['ctree_dbhost'];
			//$params['-dbhost_source']="CONFIG ctree_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		//$params['-dbhost_source']="passed in";
	}
	$CONFIG['ctree_dbhost']=$params['-dbhost'];
	
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_ctree'])){
			$params['-dbuser']=$CONFIG['dbuser_ctree'];
			//$params['-dbuser_source']="CONFIG dbuser_ctree";
		}
		elseif(isset($CONFIG['ctree_dbuser'])){
			$params['-dbuser']=$CONFIG['ctree_dbuser'];
			//$params['-dbuser_source']="CONFIG ctree_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['ctree_dbuser']=$params['-dbuser'];
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_ctree'])){
			$params['-dbpass']=$CONFIG['dbpass_ctree'];
			//$params['-dbpass_source']="CONFIG dbpass_ctree";
		}
		elseif(isset($CONFIG['ctree_dbpass'])){
			$params['-dbpass']=$CONFIG['ctree_dbpass'];
			//$params['-dbpass_source']="CONFIG ctree_dbpass";
		}
	}
	else{
		//$params['-dbpass_source']="passed in";
	}
	$CONFIG['ctree_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_ctree'])){
			$params['-dbname']=$CONFIG['dbname_ctree'];
			//$params['-dbname_source']="CONFIG dbname_ctree";
		}
		elseif(isset($CONFIG['ctree_dbname'])){
			$params['-dbname']=$CONFIG['ctree_dbname'];
			//$params['-dbname_source']="CONFIG ctree_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['ctree_dbname'];
			//$params['-dbname_source']="set to username";
		}
	}
	else{
		//$params['-dbname_source']="passed in";
	}
	$CONFIG['ctree_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_ctree'])){
			$params['-dbport']=$CONFIG['dbport_ctree'];
			//$params['-dbport_source']="CONFIG dbport_ctree";
		}
		elseif(isset($CONFIG['ctree_dbport'])){
			$params['-dbport']=$CONFIG['ctree_dbport'];
			//$params['-dbport_source']="CONFIG ctree_dbport";
		}
		else{
			$params['-dbport']=5432;
			//$params['-dbport_source']="default port";
		}
	}
	else{
		//$params['-dbport_source']="passed in";
	}
	$CONFIG['ctree_dbport']=$params['-dbport'];
	//dbschema
	if(!isset($params['-dbschema'])){
		if(isset($CONFIG['dbschema_ctree'])){
			$params['-dbschema']=$CONFIG['dbschema_ctree'];
			//$params['-dbuser_source']="CONFIG dbuser_ctree";
		}
		elseif(isset($CONFIG['ctree_dbschema'])){
			$params['-dbschema']=$CONFIG['ctree_dbschema'];
			//$params['-dbuser_source']="CONFIG ctree_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['ctree_dbschema']=$params['-dbschema'];
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['ctree_connect'])){
			$params['-connect']=$CONFIG['ctree_connect'];
		}
		elseif(isset($CONFIG['connect_ctree'])){
			$params['-connect']=$CONFIG['connect_ctree'];
		}
		else{
			//ODBC;DSN=REPL01;HOST=repl01.dot.infotraxsys.com;UID=dot_dels;DATABASE=liveSQL;SERVICE=6597;CHARSET NAME=;MAXROWS=;OPTIONS=;;PRSRVCUR=OFF;;FILEDSN=;SAVEFILE=;FETCH_SIZE=;QUERY_TIMEOUT=;SCROLLCUR=OF
			$params['-connect']="odbc:Driver={c-treeACE ODBC Driver};Host={$CONFIG['ctree_dbhost']};Database={$CONFIG['ctree_dbname']};Port={$CONFIG['ctree_dbport']}";
		}
		//add connect_timeout
		if(!stringContains($params['-connect'],'connect_timeout')){
			$params['-connect'].=";CONNECT_TIMEOUT=10";
		}
	}
	else{
		//$params['-connect_source']="passed in";
	}
	//echo printValue($params);exit;
	return $params;
}
//---------- begin function ctreeQueryResults ----------
/**
* @describe returns the ctree record set
* @param query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* 	[-webhook_url] - specify a webhook to call instead of returning records
* 	[-webhook_rowcount] - how many rows to send to the webhook at a time. Defaults to 1000
* @return array - returns records
*/
function ctreeQueryResults($query='',$params=array()){
	if(!commonStrlen($query)){
		return null;
	}
	$ok=dbSetLast(array(
		'function'=>'ctreeQueryResults',
		'p1'=>$query,
		'p2'=>$params,
	));
	$query=trim($query);
	global $USER;
	global $dbh_ctree;
	global $ctreeQueryResultsTemp;
	$ctreeQueryResultsTemp=array();
	$dbh_ctree=ctreeDBConnect();
	if(commonStrlen(dbGetLast('error'))){return array();}
	$ok=dbSetLast(array('query'=>$query));
	//chunk this up so that it works with larger datasets
	$allrecs=array();
	$allcounts=0;
	$skip=0;
	$top=10000;
	if(isset($params['batch_count'])){$top=(integer)$params['batch_count'];}
	$ctreeQueryResultsTemp['-linecount']=0;
	$ctreeQueryResultsTemp['-header']=0;
	$ctreeQueryResultsTemp['-showsql']=1;
	while(1){
		$cquery=trim($query);
		$selecttop='';
		if(stringBeginsWith($cquery,'SELECTPAGINATE') && !stringContains($cquery,' TOP ')){
			$selecttop="SELECT TOP {$top} SKIP {$skip}";
			$cquery=preg_replace('/SELECTPAGINATE/is',$selecttop,$cquery);
			if(isset($params['-logfile'])){
				appendFileContents($params['-logfile'],date('H:i:s').", {$selecttop} ...".PHP_EOL);
			}
		}
		if(isset($params['-logfile']) && $ctreeQueryResultsTemp['-showsql']==1){
			appendFileContents($params['-logfile'],date('H:i:s').", {$cquery} ...".PHP_EOL);
			$ctreeQueryResultsTemp['-showsql']=0;
		}
		$breakout=0;
		
		if($resource = odbc_prepare($dbh_ctree, $cquery)){
			if(odbc_execute($resource)){
				$crecs = ctreeEnumQueryResults($resource,$params,$cquery);
				if(is_resource($resource)){odbc_free_result($resource);}
				$resource=null;
				//echo "HERE:{$breakout}:".$cquery.printValue($params);exit;
				if(isset($params['-filename']) || isset($params['-webhook_url']) || isset($params['-process'])){
					if($crecs==0){break;}
					$allcounts+=$crecs;
				}
				else{
					if(!is_array($crecs)){
						$breakout=1;
						break;
					}
					$ccnt=0;
					foreach($crecs as $crec){
						$allrecs[]=$crec;
						$ccnt+=1;
						$allcounts+=1;
					}
					if($ccnt < $top){
						$breakout=1;
						break;
					}
				}
			}
			else{
				$ok=dbSetLast(array('error'=>odbc_errormsg()));
				debugValue(dbGetLast());
				$breakout=1;
				break;
			}
		}
		else{
			$ok=dbSetLast(array('error'=>odbc_errormsg()));
			debugValue(dbGetLast());
			$breakout=1;
			break;
		}
		//echo "HERE:{$breakout}:".printValue($recs);exit;
		if($breakout==1){
			break;
		}
		if(strlen($selecttop)){
			$skip+=$top;
		}
		else{
			break;
		}
	}
	if(is_resource($dbh_ctree) || is_object($dbh_ctree)){odbc_close($dbh_ctree);}
	$dbh_ctree=null;
	if(isset($params['-logfile']) && file_exists($params['-logfile'])){
		//unlink($params['-logfile']);
	}
	if(isset($params['-filename'])){
		return $allcounts;
	}
	return $allrecs;
}
//---------- begin function ctreeEnumQueryResults ----------
/**
* @describe enumerates through the data from a ctree query
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function ctreeEnumQueryResults($result,$params=array(),$query=''){
	$ok=dbSetLast(array(
		'function'=>'ctreeEnumQueryResults'
	));
	global $dbh_ctree;
	global $ctreeStopProcess;
	global $ctreeQueryResultsTemp;
	if(!is_object($result) && !is_resource($result)){return null;}
	unset($fh);
	$starttime=microtime(true);
	$recs=array();
	if(!isset($params['-filename_writecount'])){
		$params['-filename_writecount']=1000;
	}
	$params['-filename_writecount']=(integer)$params['-filename_writecount'];
	if($params['-filename_writecount'] < 100){
		$params['-filename_writecount']=100;
	}
	if(isset($params['-webhook_url'])){
		if(!isset($params['-webhook_rowcount'])){
			$params['-webhook_rowcount']=1000;
		}
		if(!isset($params['-webhook_format'])){
			$params['-webhook_format']='json';
		}
	}
	$i=0;
	while(1){
		$row=odbc_fetch_array($result);
		if(!is_array($row) || !count($row)){
			break;
		}
		//check for ctreeStopProcess request
		if(isset($ctreeStopProcess) && $ctreeStopProcess==1){
			if(isset($params['-logfile']) && file_exists($params['-logfile'])){
				appendFileContents($params['-logfile'],"ctreeStopProcess called".PHP_EOL);
			}
			break;
		}
		$i++;
		//build the rec
		$rec=array();
		foreach($row as $key=>$val){
			$key=strtolower($key);
			if(is_string($val)){
				$rec[$key]=trim($val);
				$rec[$key]=preg_replace('/[\r\n]+/',' ', $rec[$key]);
				$rec[$key]=str_replace(chr(8),'',$rec[$key]);
				$rec[$key]=trim($val);
				if(preg_match('/\_(id|rank)$/is',$key) && preg_match('/^([0-9\.]+)/',$rec[$key],$m)){
					//these are integers
					$rec[$key]=$m[1];
				}
				elseif(preg_match('/^(status)$/is',$key) && preg_match('/^([0-9\.]+)/',$rec[$key],$m)){
					//these are integers
					$rec[$key]=$m[1];
				}
				elseif(preg_match('/\_phone$/i',$key)){
					//remove anything but numbers, dashes, periods, and plus
					$rec[$key]=preg_replace('/[^0-9\.\-\+]/','', $rec[$key]);
				}
			}
			else{
				$rec[$key]=$val;
			}
    	}
    	$ctreeQueryResultsTemp['-linecount']+=1;
    	if(isset($params['-process'])){
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
		$rec_count=count($recs);
		if(isset($params['-filename']) && $rec_count==$params['-filename_writecount']){
			if(isset($params['-logfile'])){
				appendFileContents($params['-logfile'],date('H:i:s').",writing {$rec_count} lines. Total Line Count: {$ctreeQueryResultsTemp['-linecount']}".PHP_EOL);
			}
			if($ctreeQueryResultsTemp['-header']==0){
            	$csv=arrays2CSV($recs);
            	$ctreeQueryResultsTemp['-header']=1;
            	//add UTF-8 byte order mark to the beginning of the csv
				$csv="\xEF\xBB\xBF".$csv;
				$csv=preg_replace('/[\r\n]+$/','',$csv);
				$ok=setFileContents($params['-filename'],$csv.PHP_EOL.PHP_EOL);
			}
			else{
            	$csv=arrays2CSV($recs,array('-noheader'=>1));
            	$csv=preg_replace('/[\r\n]+$/','',$csv);
            	$ok=appendFileContents($params['-filename'],$csv.PHP_EOL.PHP_EOL);
			}
			$recs=array();
		}
		if(isset($params['-webhook_url']) && $rec_count==$params['-webhook_rowcount']){
			$payload=json_encode($recs,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
			$params['-webhook_count']+=count($recs);
			if(isset($params['-logfile']) && file_exists($params['-logfile'])){
				appendFileContents($params['-logfile'],date('H:i:s').",{$i},Calling webhook".PHP_EOL);
			}
			$post=postJSON($params['-webhook_url'],$payload);
			if(isset($params['-logfile']) && file_exists($params['-logfile'])){
				appendFileContents($params['-logfile'],date('H:i:s').",{$i},Returned {$post['body']},Running Total: {$params['-webhook_count']}".PHP_EOL);
			}
			$recs=array();
			
		}
	}
	@odbc_fetch_row($result, 0);   // reset cursor
	if(is_resource($resource)){odbc_free_result($result);}
	$result=null;
	if(isset($params['-filename']) && count($recs)){
		if($ctreeQueryResultsTemp['-header']==0){
        	$csv=arrays2CSV($recs);
        	$ctreeQueryResultsTemp['-header']=1;
        	//add UTF-8 byte order mark to the beginning of the csv
			$csv="\xEF\xBB\xBF".$csv;
			$csv=preg_replace('/[\r\n]+$/','',$csv);
			$ok=setFileContents($params['-filename'],$csv.PHP_EOL.PHP_EOL);
		}
		else{
        	$csv=arrays2CSV(array($rec),array('-noheader'=>1));
        	$csv=preg_replace('/[\r\n]+$/','',$csv);
        	$ok=appendFileContents($params['-filename'],$csv.PHP_EOL.PHP_EOL);
		}
	}
	//send last payload to webhook if specified
	if(isset($params['-webhook_url'])){
		if(count($recs)){
			$payload=json_encode($recs,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
			$params['-webhook_count']+=count($recs);
			if(isset($params['-logfile']) && file_exists($params['-logfile'])){
				appendFileContents($params['-logfile'],date('H:i:s').",{$i},Calling webhook".PHP_EOL);
			}
			$post=postJSON($params['-webhook_url'],$payload);
			if(isset($params['-logfile']) && file_exists($params['-logfile'])){
				appendFileContents($params['-logfile'],date('H:i:s').",{$i},Returned {$post['body']},Running Total: {$params['-webhook_count']}".PHP_EOL);
			}
		}
		$recs=array();
		//webhook_onfinish
		if(isset($params['-webhook_onfinish']) && commonStrlen($params['-webhook_onfinish'])){
			$post=postURL($params['-webhook_onfinish'],array('-method'=>'GET'));
			if(isset($params['-logfile']) && file_exists($params['-logfile'])){
				$elapsed=microtime(true)-$starttime;
				appendFileContents($params['-logfile'],"Called webhook_onfinish:{$params['-webhook_onfinish']}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
			}
		}
	}
	
	//close filehandle if -filename was given
	if(isset($params['-filename']) || isset($params['-webhook_url']) || isset($params['-process'])){
		if(isset($params['-logfile']) && file_exists($params['-logfile'])){
			$elapsed=microtime(true)-$starttime;
			appendFileContents($params['-logfile'],"Line count:{$ctreeQueryResultsTemp['-linecount']}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
		}
		return $i;
	}
	return $recs;
}
//https://docs.faircom.com/doc/sqlops/41040.htm
function ctreeNamedQueryList(){
	return array(
		array(
			'code'=>'fc_get_userlist',
			'icon'=>'icon-users',
			'name'=>'User List'
		),
		array(
			'code'=>'procedures',
			'icon'=>'icon-th-thumb-empty',
			'name'=>'Procedures'
		),
		array(
			'code'=>'fc_get_transtats',
			'icon'=>'icon-transfer',
			'name'=>'Transaction Stats'
		),
		array(
			'code'=>'fc_get_lockstats',
			'icon'=>'icon-lock',
			'name'=>'Lock Stats'
		),
		array(
			'code'=>'fc_get_connstats',
			'icon'=>'icon-handshake',
			'name'=>'Connection Stats'
		),
		array(
			'code'=>'fc_get_memstats',
			'icon'=>'icon-hardware-memory',
			'name'=>'Memory Stats'
		),
		array(
			'code'=>'fc_get_sqlstats',
			'icon'=>'icon-sql',
			'name'=>'SQL Stats'
		),
	);
}
//---------- begin function ctreeNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function ctreeNamedQuery($name){
	switch(strtolower($name)){
		case 'fc_get_filestats':
		case 'fc_get_transtats':
		case 'fc_get_lockstats':
		case 'fc_get_cachestats':
		case 'fc_get_iostats':
		case 'fc_get_isamstats':
		case 'fc_get_sqlstats':
		case 'fc_get_replstats':
		case 'fc_get_memstats':
		case 'fc_get_connstats':
		case 'fc_get_userlist':
			return "call {$name}()";
		break;
		case 'running':
		case 'queries':
		case 'running_queries':
			return <<<ENDOFQUERY

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

ENDOFQUERY;
		break;
		case 'procedures':
			return <<<ENDOFQUERY
SELECT 
	creator,
	has_resultset,
	has_return_val,
	owner,
	proc_id,
	proc_name,
	proc_type,
	rssid
FROM admin.sysprocedures

ENDOFQUERY;
		break;
	}
}
