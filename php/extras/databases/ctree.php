<?php
/*
	ctree.php - a collection of cTREE Database functions for use by WaSQL.
	
	References:
		https://www.yumpu.com/en/document/read/30279025/sql-reference-guide-cove-systems
		https://docs.faircom.com/doc/sqlref/sqlref.pdf
*/

ini_set('max_execution_time', 1800);
set_time_limit(1800);

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
		//report through dbSetLast rather than echo+exit - callers already test dbGetLast('error')
		//	right after connecting, and killing the request here just dumps raw text into whatever
		//	page (or ajax/json response) asked for the query
		$ok=dbSetLast(array('error'=>'ctreeDBConnect error: no dbname or connect param'));
		//mask the password first - debugValue() feeds the debug buffer that callers such as
		//	php/admin/sqlprompt render straight into the error they show the user
		if(isset($params['-dbpass'])){$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);}
		debugValue($params);
		return null;
	}
	//ctree does not support pooling do it in the ODBC Connection manager
	//$params['-single']=1;
	//echo printValue($params);exit;
	//----- persistent connections: OPT IN PER CONNECTION, off by default -----
	//Add persistent="1" to the <database> tag in config.xml to try it on one connection at a time
	//	(every attribute on that tag arrives here as -{attr}).
	//Why it is opt-in, and why it bit us before:
	//	1. FairCom licenses CONCURRENT SESSIONS. A persistent handle is held by the php worker for
	//	   the life of that worker, idle or not, so the ceiling is (workers x connections), NOT
	//	   (simultaneous queries). More apache/php-fpm workers than licensed seats gives you
	//	   "Maximum users exceeded" - the failure this file already writes a flag file for.
	//	   Only enable this where the worker count is at or under the seat count.
	//	2. Pooling only pays off if we STOP closing per query. odbc_close() drops a persistent
	//	   handle out of php's persistent list, so pconnect+close-per-query is all cost and no
	//	   benefit - and on PHP 8.4 it hands the NEXT request a closed handle. So the close sites
	//	   in ctreeQueryResults()/ctreeExecuteSQL() go through ctreeReleaseConnection(), which
	//	   skips the close when the handle is persistent.
	//	3. A pooled handle outlives server restarts and idle timeouts, so a reused one is probed
	//	   for liveness before it is handed back (see ctreeConnectionIsLive).
	$persistent=0;
	if(!empty($params['-persistent'])){$persistent=1;}
	if(!empty($params['-dbpersistent'])){$persistent=1;}
	if($persistent){
		global $dbh_ctree;
		global $dbh_ctree_persistent;
		global $dbh_ctree_name;
		//reuse only a LIVE handle that belongs to the connection actually being asked for - the
		//	global is shared by every ctree connection in the request, so the name has to match
		if(commonIsResourceOrObject($dbh_ctree) && $dbh_ctree_name==$connect_name){
			if(ctreeConnectionIsLive($dbh_ctree)){
				$dbh_ctree_persistent=1;
				return $dbh_ctree;
			}
			//dead handle out of the pool - drop it and reconnect below
			$ok=ctreeCloseConnection($dbh_ctree);
			$dbh_ctree=null;
		}
		if(isset($params['-cursor'])){
			$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor']);
		}
		else{
			$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass']);
		}
		if(!commonIsResourceOrObject($dbh_ctree)){
			$err=odbc_errormsg();
			$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
			$ok=dbSetLast(array('error'=>"ctreeDBConnect persistent connect error: {$err}"));
			debugValue($params);
			if(is_dir('c:/bin') && stringContains($err,'Maximum users exceeded')){
				//more workers holding persistent handles than the FairCom licence allows - see (1)
				$ok=file_put_contents("C:\bin\ctree_failed.txt", $err);
			}
			$dbh_ctree=null;
			$dbh_ctree_persistent=0;
			$dbh_ctree_name='';
			return null;
		}
		$ok=ctreeSetTimeouts($dbh_ctree);
		$dbh_ctree_persistent=1;
		$dbh_ctree_name=$connect_name;
		return $dbh_ctree;
	}
	//----- default: a fresh non-persistent connection, closed at the end of every query -----
	//NOTE this is isset(), not a truthiness test, and ctreeParseConnectParams() always sets
	//	-single (to 0), so with persistent off EVERY connection lands here and gets its own
	//	odbc_connect(). The odbc_pconnect() block further down is legacy and unreachable - the
	//	supported way to get a persistent handle is persistent="1" in config.xml, handled above.
	global $dbh_ctree_persistent;
	$dbh_ctree_persistent=0;
	if(isset($params['-single'])){
		if(isset($params['-cursor'])){
			$dbh_ctree_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_ctree_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'] );
		}
		if(!commonIsResourceOrObject($dbh_ctree_single)){
			$err=odbc_errormsg();
			$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
			$ok=dbSetLast(array('error'=>"ctreeDBConnect single connect error: {$err}"));
			debugValue($params);
			if(is_dir('c:/bin') && stringContains($err,'Maximum users exceeded')){
				//Maximum users exceeded
				$ok=file_put_contents("C:\bin\ctree_failed.txt", $err);
			}
			return null;
		}
		$ok=ctreeSetTimeouts($dbh_ctree_single);
		return $dbh_ctree_single;
	}
	global $dbh_ctree;
	if(commonIsResourceOrObject($dbh_ctree)){return $dbh_ctree;}

	try{
		if(isset($params['-cursor'])){
			$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass']);
		}
		if(!commonIsResourceOrObject($dbh_ctree)){
			//wait a few seconds and try again
			sleep(2);
			if(isset($params['-cursor'])){
				$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
			}
			else{
				$dbh_ctree = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'] );
			}
			if(!commonIsResourceOrObject($dbh_ctree)){
				$err=odbc_errormsg();
				$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
				$ok=dbSetLast(array('error'=>"ctreeDBConnect error: {$err}"));
				debugValue($params);
				return null;
			}
		}
		$ok=ctreeSetTimeouts($dbh_ctree);
		return $dbh_ctree;
	}
	catch (Exception $e) {
		$ok=dbSetLast(array('error'=>'ctreeDBConnect exception: '.$e->getMessage()));
		debugValue($e->getMessage());
		return null;

	}
}
function ctreeSetTimeouts($dbh) {
    // SQL_ATTR_CONNECTION_TIMEOUT (option 113) applies to connection establishment only, not query execution
    // Query timeout for FairCom must be set via QUERY_TIMEOUT= in the connection string (see ctreeParseConnectParams)
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
	if(commonIsResourceOrObject($dbh_ctree)){return $dbh_ctree;}
	//try a few times to connect
	$tries=0;
	$exc='';
	while($tries < 5){
		$dbh_ctree = odbc_connect($params['-connect'],$params['-dbuser'],$params['-dbpass']);
		if(commonIsResourceOrObject($dbh_ctree)){return $dbh_ctree;}
		$tries+=1;
		sleep(5);
	}
	if(!commonIsResourceOrObject($dbh_ctree)){
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
		'error'=>'',
		'p1'=>$query,
		'p2'=>$return_error,
	));
	global $dbh_ctree;
	$dbh_ctree=ctreeDBConnect();
	if(commonStrlen(dbGetLast('error'))){return 0;}
	$ok=dbSetLast(array('query'=>$query));
	if($resource = odbc_prepare($dbh_ctree, $query)){
		odbc_setoption($resource, 2, 0, 1800);  // SQL_QUERY_TIMEOUT = 30min on statement handle (FairCom may ignore; rely on QUERY_TIMEOUT in connection string)
		if(odbc_execute($resource)){
			$ok=ctreeFreeResult($resource);
			$resource=null;
			ctreeReleaseConnection();
			return true;
		}
	}
	//grab the error BEFORE closing - odbc_errormsg() has no connection to report on afterwards
	$ok=dbSetLast(array('error'=>odbc_errormsg($dbh_ctree)));
	ctreeReleaseConnection();
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
	global $DATABASE;
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
					$params['-pool']=(int)$v;
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
	//NOTE: this is unconditional, and ctreeDBConnect() gates on isset(-single) - so setting it to
	//	0 here does not turn single/non-persistent connections OFF, it turns them permanently ON.
	//	See the WARNING in ctreeDBConnect() before touching either side.
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
		//add connect_timeout (connection establishment timeout)
		if(!stringContains($params['-connect'],'CONNECT_TIMEOUT') && !stringContains($params['-connect'],'connect_timeout')){
			$params['-connect'].=";CONNECT_TIMEOUT=30";
		}
		//add query_timeout - FairCom ODBC driver honors this in the connection string (odbc_setoption is ignored)
		if(!stringContains($params['-connect'],'QUERY_TIMEOUT') && !stringContains($params['-connect'],'query_timeout')){
			$params['-connect'].=";QUERY_TIMEOUT=1800";
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
		//dbSetLast() MERGES into a request-global that never clears 'error' on its own, so an
		//	error left behind by ANY earlier db call would make the bail-out below return an empty
		//	array without ever running this query. Start each call with a clean slate.
		'error'=>'',
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
	if(isset($params['-batch_count'])){$top=(int)$params['-batch_count'];}
	if($top < 1){$top=10000;}
	//SELECTPAGINATE paging is driven entirely by the rows the server hands back, so a source that
	//	ignores SKIP would return the same batch forever and grow $allrecs until the request dies.
	//	-maxrows caps it explicitly; -batch_limit caps the number of round trips.
	$maxrows=isset($params['-maxrows']) ? (int)$params['-maxrows'] : 0;
	$batchlimit=isset($params['-batch_limit']) ? (int)$params['-batch_limit'] : 10000;
	$batches=0;
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
		//echo $cquery.printValue($params);exit;
		if($resource = odbc_prepare($dbh_ctree, $cquery)){
			odbc_setoption($resource, 2, 0, 1800);  // SQL_QUERY_TIMEOUT = 30min on statement handle (FairCom may ignore; rely on QUERY_TIMEOUT in connection string)
			if(odbc_execute($resource)){
				$crecs = ctreeEnumQueryResults($resource,$params,$cquery);
				$ok=ctreeFreeResult($resource);
				$resource=null;
				//echo "HERE:{$crecs}:".$cquery.printValue($params);exit;
				if(isset($params['-filename']) || isset($params['-webhook_url']) || isset($params['-process'])){
					$allcounts+=$crecs;
					if($crecs==0 || $selecttop==''){
						$breakout=1;
						break;
					}
				}
				else{
					if(!is_array($crecs)){
						$breakout=1;
						break;
					}
					$ccnt=count($crecs);
					foreach($crecs as $crec){
						$allrecs[]=$crec;
					}
					$allcounts+=$ccnt;
					//drop the batch NOW - otherwise it stays live while the next {$top} rows are
					//	fetched, so peak memory carries two full batches on top of $allrecs
					unset($crecs,$crec);
					if($ccnt < $top){
						$breakout=1;
						break;
					}
				}
			}
			else{
				$ok=dbSetLast(array('error'=>odbc_errormsg($dbh_ctree)));
				debugValue(dbGetLast());
				//the statement prepared but never ran - it still holds a handle, so free it here
				$ok=ctreeFreeResult($resource);
				$resource=null;
				$breakout=1;
				break;
			}
		}
		else{
			$ok=dbSetLast(array('error'=>odbc_errormsg($dbh_ctree)));
			debugValue(dbGetLast());
			$breakout=1;
			break;
		}
		//echo "HERE:{$breakout}:".printValue($recs);exit;
		if($breakout==1){
			break;
		}
		$batches+=1;
		if($batchlimit>0 && $batches>=$batchlimit){
			$ok=dbSetLast(array('error'=>"ctreeQueryResults: stopped after {$batches} batches (-batch_limit) at {$allcounts} rows - the source may be ignoring SKIP"));
			debugValue(dbGetLast());
			break;
		}
		if($maxrows>0 && $allcounts>=$maxrows){
			break;
		}
		//bail with a real error rather than letting the accumulator hit the OOM fatal
		if(ctreeMemoryPressure()){
			$ok=dbSetLast(array('error'=>"ctreeQueryResults: stopped at {$allcounts} rows - approaching the PHP memory_limit. Use -filename to stream to CSV, -process for a row callback, or raise -batch_count/memory_limit."));
			debugValue(dbGetLast());
			break;
		}
		if(strlen($selecttop)){
			$skip+=$top;
		}
		else{
			break;
		}
	}
	ctreeReleaseConnection();
	if(isset($params['-logfile']) && file_exists($params['-logfile'])){
		//unlink($params['-logfile']);
	}
	if(isset($params['-filename'])){
		return $allcounts;
	}
	return $allrecs;
}
//---------- begin function ctreeReleaseConnection ----------
/**
* @describe ends a query's use of $dbh_ctree - closes and nulls a normal connection, but LEAVES a
*	persistent (pooled) one open so the next request can reuse it.
* @exclude - used for internal use only
* @return boolean - true if the connection was actually closed
* @usage ctreeReleaseConnection();
* NOTE: closing a persistent handle drops it out of php's persistent list, so pooling would cost
*	more than it saves, and on PHP 8.4 the next request would be handed a closed handle.
*/
function ctreeReleaseConnection(){
	global $dbh_ctree;
	global $dbh_ctree_persistent;
	if(!empty($dbh_ctree_persistent)){return false;}
	$ok=ctreeCloseConnection($dbh_ctree);
	//null the cached global - PHP 8.4 leaves a CLOSED Odbc\Connection object here, which
	//	ctreeDBConnect() would happily hand back, and the next odbc_prepare() on it throws
	//	"ODBC connection has already been closed"
	$dbh_ctree=null;
	return $ok;
}
//---------- begin function ctreeConnectionIsLive ----------
/**
* @describe returns true if an odbc connection handle still answers. Used before handing a POOLED
*	(persistent) handle back out - it may be left over from an earlier request and since have been
*	dropped by a server restart or an idle timeout.
* @exclude - used for internal use only
* @param dbh mixed - odbc connection handle
* @return boolean
* @usage if(!ctreeConnectionIsLive($dbh)){...reconnect...}
*/
function ctreeConnectionIsLive($dbh){
	if(!commonIsResourceOrObject($dbh)){return false;}
	if(version_compare(PHP_VERSION,'7.0','>=')){
		return ctreeConnectionIsLiveCatch($dbh);
	}
	return ctreeConnectionProbe($dbh);
}
//---------- begin function ctreeConnectionIsLiveCatch ----------
/**
* @describe PHP 7+ only - probes a connection inside a try/catch. Call ctreeConnectionIsLive().
* @exclude - used for internal use only
* @param dbh mixed - odbc connection handle
* @return boolean
* @usage $ok=ctreeConnectionIsLiveCatch($dbh);
*/
function ctreeConnectionIsLiveCatch($dbh){
	try{
		return ctreeConnectionProbe($dbh);
	}
	catch(Throwable $e){
		//8.4 throws when the handle is already closed - which is exactly what we are testing for
		return false;
	}
}
//---------- begin function ctreeConnectionProbe ----------
/**
* @describe the actual liveness probe. Call ctreeConnectionIsLive() instead.
* @exclude - used for internal use only
* @param dbh mixed - odbc connection handle
* @return boolean
* @usage $ok=ctreeConnectionProbe($dbh);
*/
function ctreeConnectionProbe($dbh){
	//SQLTables rather than a SELECT on purpose - it needs no table, no schema and no SQL dialect,
	//	and the driver answers it without materialising rows. Freed immediately.
	$res=@odbc_tables($dbh);
	if(!commonIsResourceOrObject($res)){return false;}
	$ok=ctreeFreeResult($res);
	return true;
}
//---------- begin function ctreeMemoryPressure ----------

/**
* @describe returns true when this request is close enough to the PHP memory_limit that
*	accumulating another batch of rows would risk the fatal "Allowed memory size exhausted"
* @param pct float - fraction of memory_limit that counts as too close. Defaults to .85
* @return boolean
* @usage if(ctreeMemoryPressure()){...}
*/
function ctreeMemoryPressure($pct=.85){
	$limit=trim(ini_get('memory_limit'));
	//-1 means unlimited, and an empty value means we cannot tell - either way, do not interfere
	if(!commonStrlen($limit) || $limit=='-1'){return false;}
	$bytes=(float)$limit;
	switch(strtolower(substr($limit,-1))){
		case 'g': $bytes=$bytes*1024*1024*1024; break;
		case 'm': $bytes=$bytes*1024*1024; break;
		case 'k': $bytes=$bytes*1024; break;
	}
	if($bytes <= 0){return false;}
	return (memory_get_usage(true) > ($bytes*$pct));
}
//---------- begin function ctreeFreeResult ----------
/**
* @describe frees an odbc result handle, tolerating one that has already been freed
* @exclude - used for internal use only
* @param resource mixed - odbc result handle
* @return boolean - true if this call freed it, false if there was nothing to free
* @usage $ok=ctreeFreeResult($resource);
* NOTE: as of PHP 8.4 odbc_prepare/odbc_exec return an Odbc\Result OBJECT rather than a
*	resource, and a freed result stays an object - so commonIsResourceOrObject() can no longer
*	tell you whether it is still open, and a second odbc_free_result() throws
*	"Error: ODBC result has already been closed" instead of quietly failing like it did on 8.3.
*	Always free through this so double-frees stay harmless. Safe back to PHP 5.
*/
function ctreeFreeResult($resource){
	if(!commonIsResourceOrObject($resource)){return false;}
	//PHP 7+ can THROW here, so it needs a try/catch - but naming Throwable in a catch block
	//	would be a fatal on PHP 5 if it ever had to match, so that lives in its own function
	//	that PHP 5 never calls. On PHP 5 an odbc result is a plain resource and freeing a stale
	//	one is only a warning, so @ is all it takes.
	if(version_compare(PHP_VERSION,'7.0','>=')){
		return ctreeFreeResultCatch($resource);
	}
	return @odbc_free_result($resource);
}
//---------- begin function ctreeFreeResultCatch ----------
/**
* @describe PHP 7+ only - frees an odbc result inside a try/catch. Call ctreeFreeResult() instead.
* @exclude - used for internal use only
* @param resource mixed - odbc result handle
* @return boolean
* @usage $ok=ctreeFreeResultCatch($resource);
*/
function ctreeFreeResultCatch($resource){
	try{
		return @odbc_free_result($resource);
	}
	catch(Throwable $e){
		//already closed (or no longer a live result) - nothing to do
		return false;
	}
}
//---------- begin function ctreeCloseConnection ----------
/**
* @describe closes an odbc connection handle, tolerating one that is already closed
* @exclude - used for internal use only
* @param dbh mixed - odbc connection handle
* @return boolean - true if this call closed it
* @usage $ok=ctreeCloseConnection($dbh_ctree); $dbh_ctree=null;
* NOTE: always null the caller's handle afterwards - see ctreeFreeResult().
*/
function ctreeCloseConnection($dbh){
	if(!commonIsResourceOrObject($dbh)){return false;}
	if(version_compare(PHP_VERSION,'7.0','>=')){
		return ctreeCloseConnectionCatch($dbh);
	}
	@odbc_close($dbh);
	return true;
}
//---------- begin function ctreeCloseConnectionCatch ----------
/**
* @describe PHP 7+ only - closes an odbc connection inside a try/catch. Call ctreeCloseConnection() instead.
* @exclude - used for internal use only
* @param dbh mixed - odbc connection handle
* @return boolean
* @usage $ok=ctreeCloseConnectionCatch($dbh);
*/
function ctreeCloseConnectionCatch($dbh){
	try{
		@odbc_close($dbh);
		return true;
	}
	catch(Throwable $e){
		//already closed - nothing to do
		return false;
	}
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
	if(!commonIsResourceOrObject($result)){return null;}
	unset($fh);
	$starttime=microtime(true);
	$recs=array();
	if(!isset($params['-filename_writecount'])){
		$params['-filename_writecount']=1000;
	}
	$params['-filename_writecount']=(int)$params['-filename_writecount'];
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
		if(!isset($params['-webhook_count'])){
			$params['-webhook_count']=0;
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
				//re-trim the CLEANED value - trimming $val again here would throw away the
				//	newline and chr(8) scrubbing above, which then breaks CSV output for any row
				//	with an embedded newline
				$rec[$key]=trim($rec[$key]);
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
			continue;
		}
		elseif(isset($params['-index']) && isset($rec[$params['-index']])){
			$recs[$rec[$params['-index']]]=$rec;
		}
		else{
			$recs[]=$rec;
		}
		$rec_count=count($recs);
		//>= not == : with -index in play $recs can jump past the threshold (or stall on repeated
		//	keys), and a missed flush means $recs grows for the whole result set
		if(isset($params['-filename']) && $rec_count>=$params['-filename_writecount']){
			
			if($ctreeQueryResultsTemp['-header']==0){
            	$csv=arrays2CSV($recs);
            	$ctreeQueryResultsTemp['-header']=1;
            	//add UTF-8 byte order mark to the beginning of the csv
				$csv="\xEF\xBB\xBF".$csv;
				$csv=preg_replace('/[\r\n]+$/','',$csv);
				$ok=setFileContents($params['-filename'],$csv.PHP_EOL.PHP_EOL);
				if(isset($params['-logfile'])){
					appendFileContents($params['-logfile'],date('H:i:s')."CSV FILE: {$params['-filename']} ,writing {$rec_count} lines. Total Line Count: {$ctreeQueryResultsTemp['-linecount']}".PHP_EOL);
				}
			}
			else{
            	$csv=arrays2CSV($recs,array('-noheader'=>1));
            	$csv=preg_replace('/[\r\n]+$/','',$csv);
            	if(isset($params['-logfile'])){
					appendFileContents($params['-logfile'],date('H:i:s').",writing {$rec_count} lines. Total Line Count: {$ctreeQueryResultsTemp['-linecount']}".PHP_EOL);
				}
            	$ok=appendFileContents($params['-filename'],$csv.PHP_EOL.PHP_EOL);
			}
			$recs=array();
		}
		if(isset($params['-webhook_url']) && $rec_count>=$params['-webhook_rowcount']){
			//JSON_INVALID_UTF8_SUBSTITUTE is PHP 7.2+ - on anything older an undefined constant is
			//	treated as its own name (a string), and OR-ing two strings yields garbage flags
			$jsonflags=JSON_UNESCAPED_UNICODE;
			if(defined('JSON_INVALID_UTF8_SUBSTITUTE')){$jsonflags=$jsonflags|JSON_INVALID_UTF8_SUBSTITUTE;}
			$payload=json_encode($recs,$jsonflags);
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
	$ok=ctreeFreeResult($result);
	$result=null;
	$rec_count=count($recs);
	if(isset($params['-filename']) && $rec_count>0){
		if($ctreeQueryResultsTemp['-header']==0){
        	$csv=arrays2CSV($recs);
        	$ctreeQueryResultsTemp['-header']=1;
        	//add UTF-8 byte order mark to the beginning of the csv
			$csv="\xEF\xBB\xBF".$csv;
			$csv=preg_replace('/[\r\n]+$/','',$csv);
			if(isset($params['-logfile'])){
				appendFileContents($params['-logfile'],date('H:i:s')."CSV FILE: {$params['-filename']} ,writing {$rec_count} lines. Total Line Count: {$ctreeQueryResultsTemp['-linecount']}".PHP_EOL);
			}
			$ok=setFileContents($params['-filename'],$csv.PHP_EOL.PHP_EOL);
		}
		else{
        	$csv=arrays2CSV($recs,array('-noheader'=>1));
        	$csv=preg_replace('/[\r\n]+$/','',$csv);
        	if(isset($params['-logfile'])){
				appendFileContents($params['-logfile'],date('H:i:s').",writing {$rec_count} lines. Total Line Count: {$ctreeQueryResultsTemp['-linecount']}".PHP_EOL);
			}
        	$ok=appendFileContents($params['-filename'],$csv.PHP_EOL.PHP_EOL);
		}
	}
	//send last payload to webhook if specified
	if(isset($params['-webhook_url'])){
		if(count($recs)){
			//JSON_INVALID_UTF8_SUBSTITUTE is PHP 7.2+ - on anything older an undefined constant is
			//	treated as its own name (a string), and OR-ing two strings yields garbage flags
			$jsonflags=JSON_UNESCAPED_UNICODE;
			if(defined('JSON_INVALID_UTF8_SUBSTITUTE')){$jsonflags=$jsonflags|JSON_INVALID_UTF8_SUBSTITUTE;}
			$payload=json_encode($recs,$jsonflags);
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
/*
	The following function are used when jsondb is set to 1 in the config.xml database container
	global $CONFIG;
	$CONFIG['db']='ctree_birepl01_us';
	$ok=ctreeJsonDBGetAuthToken();
	echo printValue($ok);
*/
function ctreeJsonDBGetAuthToken($params=array()){
	global $ctreeJsonDBGetAuthTokenCache;
	if(strlen($ctreeJsonDBGetAuthTokenCache)){return $ctreeJsonDBGetAuthTokenCache;}
	$params=ctreeParseConnectParams($params);
	$json=array(
		'api'=>'admin',
		'action'=>'createSession',
		'params'=>array(
			'username'=>$params['-dbuser'],
			'password'=>$params['-dbpass'],
			//'permanentSession'=>true,
			'defaultDatabaseName'=>$params['-dbname'],
			'defaultOwnerName'=>$params['-dbuser'],
			'description'=>"API key for {$params['-name']})"
		)
	);
	$jsonstr=encodeJSON($json);
	$url=ctreeJsonDBBaseURL();
	$port=commonCoalesce($params['-jsondbport'],8443);
	//echo $url.printValue($params).printValue($json);exit;
	$post=postJSON($url,$jsonstr,array('-port'=>$port,'-nossl'=>1));
 	if(isset($post['json_array']['authToken'])){
 		$ctreeJsonDBGetAuthTokenCache=$post['json_array']['authToken'];
 		return $ctreeJsonDBGetAuthTokenCache;
	}
	else{
		echo "Failed to get authtoken";
	}
	echo printValue($post);
	exit;
}
function ctreeJsonDBBaseURL(){
	$params=ctreeParseConnectParams($params);
	$url=commonCoalesce($params['-jsondbhost'],$params['-dbhost']);
	if(!stringBeginsWith($url,'http')){$url="https://{$url}/api";}
	return $url;
}
function ctreeJsonDBRequestId(){
	global $ctreeJsonDBRequestId_last;
	if(!isset($ctreeJsonDBRequestId_last) || !strlen($ctreeJsonDBRequestId_last)){
		$ctreeJsonDBRequestId_last=time();
	}
	$ctreeJsonDBRequestId_last+=1;
	return $ctreeJsonDBRequestId_last;
}
function ctreeJsonDBCloseCursor(){
	return ctreeJsonDBCallAPI('db','closeCursor',array(
		'cursorId'=>null
	));
}
function ctreeJsonDBQueryResults($query='',$params=array()){
	$params=ctreeParseConnectParams($params);
	//run SQL and request a cursor
	$post=ctreeJsonDBCallAPI('db','getRecordsUsingSQL',array(
		'sql'=>$query,
		'returnCursor'=>true,
		'forceRecordCount'=>true
	));
	if(!isset($post['cursorId'])){
		echo "ctreeJsonDBQueryResults Error - no cursor".printValue($post);exit;
	}
	$cursorid=$post['cursorId'];
	$total_cnt=commonCoalesce($post['totalRecordCount'],0);
	$fetch = commonCoalesce($params['dbfetch'],1000);
	if($total_cnt > 0 && $total_cnt < $fetch){$fetch=$total_cnt;}
	$recs=array();
	$recs_cnt=0;
	$options=array(
		'cursorId'=>$cursorid,
		'fetchRecords'=>$fetch,
		'databaseName'=>$params['-dbname']
	);
	$response_options=array(
		'dataFormat' => 'arrays',
		'numberFormat'=>'number'
	);
	do {
		$xrecs=ctreeJsonDBCallAPI('db','getRecordsFromCursor',$options,$response_options);
		if(isset($recs['errorMessage'])){echo printValue($xrecs);exit;}
		if(!is_array($xrecs)){$recs=array();}
		$xrecs_cnt = count($xrecs);
		$recs=array_merge($recs,$xrecs);
		$recs_cnt=count($recs);
		if($total_cnt > 0 && $recs_cnt == $total_cnt){$xrecs_cnt=0;}
	} while ($xrecs_cnt > 0);
	//close cursor
	$ok=ctreeJsonDBCloseCursor();
	return $recs;
}
//dumps table to a csv file and returns the csv file
function ctreeJsonDBDumpTable($tablename,$params=array()){
	//echo "ctreeJsonDBDumpTable<br>".PHP_EOL;
	$params=ctreeParseConnectParams($params);
	//echo "params".printValue($params);exit;
	//call params
	$options=array(
		'tableName'=>$tablename,
		'databaseName'=>$params['-dbname'],
		'returnCursor'=>true
	);
	if(isset($params['-filter'])){
		$options['tableFilter']=$params['-filter'];
	}
	//response options
	$response_options=array(
		'numberFormat'=>'number',
		'dataFormat'=>'arrays'
	);
	//-fields
	if(isset($params['-fields'])){
		if(!is_array($params['-fields'])){
			$params['-fields']=preg_split('/\,/',$params['-fields']);
		}
		//trim and uppercase each field
		$params['-fields'] = array_map(function($v) {
    		return strtoupper(trim($v));
		}, $params['-fields']);
		$response_options['includeFields']=$params['-fields'];
	}
	$tpath=getWasqlTempPath();
	$fname="{$tablename}_dump_".date('YmdHis').'.csv';
	$logname=str_replace('.csv','.log',$fname);
	$afile="{$tpath}/{$fname}";
	$logfile="{$tpath}/{$logname}";
	$fetch = commonCoalesce($params['dbfetch'],5000);
	$ctime=date('H:i:s');
	$ok=setFileContents($logfile,"{$ctime} -- Started. Fetch: {$fetch}".PHP_EOL);
	$post=ctreeJsonDBCallAPI('db','getRecordsByTable',$options,$response_options);
	$cursorid=commonCoalesce($post['result']['cursorId'],$post['cursorId'],'');
	//echo printValue($recs);exit;
	if(!strlen($cursorid)){
		echo "ctreeJsonDBQueryResults Error - no cursor".printValue($post);
		exit;
	}
	$total_cnt=commonCoalesce($post['result']['totalRecordCount'],$post['totalRecordCount'],0);
	if($total_cnt > 0 && $total_cnt < $fetch){$fetch=$total_cnt;}
	$ctime=date('H:i:s');
	$ok=appendFileContents($logfile,"{$ctime} -- Total Cnt: {$total_cnt}".PHP_EOL);
	$recs_cnt=0;
	$output = fopen($afile, 'a');
	$header=0;
	$doloop=0;
	//echo printValue($post);
	do {
		$doloop+=1;
		$ctime=date('H:i:s');
		$btime=microtime(true);
		$cpost=ctreeJsonDBCallAPI('db','getRecordsFromCursor',array(
			'cursorId'=>$cursorid,
			'fetchRecords'=>$fetch
		),array(
			'dataFormat' => 'arrays',
			'numberFormat'=>'number'
		));
		$etime=number_format((microtime(true)-$btime),2);
		$ok=appendFileContents($logfile,"{$ctime} -- Loop {$doloop} ctreeJsonDBCallAPI took {$etime} seconds {$total_cnt}".PHP_EOL);
		//echo "xrecs".printValue($xrecs);$ok=ctreeJsonDBCloseCursor();exit;
		if(!is_array($cpost['data'])){
			$ok=appendFileContents($logfile,"{$ctime} -- Error: {$cpost['errorCode']}- {$cpost['errorMessage']}".PHP_EOL);
			$xrecs_cnt=0;
			break;
		}
		if($header==0){
			$header=1;
			$fields=[];
			foreach($cpost['fields'] as $field){
				$fields[]=strtolower($field['name']);
			}
			$fields = array_map('trim', $fields);
			fputcsv($output, $fields);
		}
		$xrecs_cnt = count($cpost['data']);
		$ctime=date('H:i:s');
		$ok=appendFileContents($logfile,"{$ctime} -- Loop:{$doloop}, xrecs_cnt:{$xrecs_cnt}, recs_cnt:{$recs_cnt}".PHP_EOL);
		//fputcsv() automatically adds a newline character at the end of each row.
		foreach ($cpost['data'] as $row) {
			$row = array_map('trim', $row);
      		fputcsv($output, $row);
      		$recs_cnt+=1;
  		}
		if($total_cnt > 0 && $xrecs_cnt == $total_cnt){
			$ok=appendFileContents($logfile,"{$ctime} -- Exit Loop A: {$total_cnt}".PHP_EOL);
			$xrecs_cnt=0;
		}
		elseif($total_cnt > 0 && $recs_cnt >= $total_cnt){
			$ok=appendFileContents($logfile,"{$ctime} -- Exit Loop B: {$total_cnt}".PHP_EOL);
			$xrecs_cnt=0;
		}
		$ok=appendFileContents($logfile,"--------------".PHP_EOL);
	} while ($xrecs_cnt > 0 && $doloop < 300);
	//close file
	 fclose($output);
	//close cursor
	$ok=ctreeJsonDBCloseCursor();
	return $afile;
}
function ctreeJsonDBCallAPI($api,$action,$params=array(),$responseOptions=array()){
	$url=ctreeJsonDBBaseURL();
	$json=array(
		'api'=>$api,
		'action'=>$action,
		'authToken'=>ctreeJsonDBGetAuthToken($params),
		'requestId'=>ctreeJsonDBRequestId()
	);
	//echo printValue($json);
	if(is_array($params) && count($params)){
		$json['params']=$params;
	}
	if(is_array($responseOptions) && count($responseOptions)){
		$json['responseOptions']=$responseOptions;
	}
	$params=array(
		'-nossl'=>1,
		'-port'=>commonCoalesce($params['-jsondbport'],8443)
	);

	$jsonstr=encodeJSON($json,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
	if(!strlen($jsonstr)){
		echo "ctreeJsonDBCallAPI ERROR: invalid json".printValue($json);exit;
	}
	$post=postJSON($url,$jsonstr,$params);
	//if($action=='getRecordsByTable'){echo printValue($post);exit;}
	if($post['code']==400){
		//$ok=fmqDeleteSession();
		echo "ctreeJsonDBCallAPI ERROR: ".PHP_EOL.PHP_EOL.$jsonstr.PHP_EOL.PHP_EOL.printValue($post);exit;
	}
	//echo printValue($post['json_array']);
	if(!isset($post['json_array'])){
		return array();
		//$ok=fmqDeleteSession();
		echo "ctreeJsonDBCallAPI ERROR: Failed to call".printValue($post);exit;
	}
	if(!stringContains($action,'delete') && isset($post['json_array']['errorMessage']) && strlen($post['json_array']['errorMessage'])){
		return $post['json_array'];
		//$ok=fmqDeleteSession();
		echo "ctreeJsonDBCallAPI {$action} Error {$post['json_array']['errorCode']}: {$post['json_array']['errorMessage']}";
		echo printValue($post);exit;

	}
	if(isset($post['json_array']['result']) && $action=='getRecordsFromCursor'){
		return $post['json_array']['result'];
	}
	if(isset($post['json_array']['result']['fields']) && isset($post['json_array']['result']['data'])){
		
		//convert to a normal recordset
		$fields=[];
		foreach($post['json_array']['result']['fields'] as $field){
			$fields[]=strtolower($field['name']);
		}
		$recs=[];
		foreach($post['json_array']['result']['data'] as $data){
			$rec=[];
			//echo printValue($fields).printValue($data);exit;
			foreach($data as $i=>$v){
				$rec[$fields[$i]]=trim($v);
			}
			$recs[]=$rec;
		}
		return $recs;
	}
	if(isset($post['json_array']['result']['data'])){
		//$ok=fmqDeleteSession();
		return $post['json_array']['result']['data'];
	}
	if(isset($post['json_array']['result']['messages'])){
		//$ok=fmqDeleteSession();
		return $post['json_array']['result']['messages'];
	}
	if(isset($post['json_array']['authToken'])){
		return $post['json_array'];
	}
	if(isset($post['json_array']['result'])){
		//$ok=fmqDeleteSession();
		return $post['json_array']['result'];
	}
	if(isset($post['json_array'])){
		//$ok=fmqDeleteSession();
		return $post['json_array'];
	}
	//$ok=fmqDeleteSession();
	//echo "ctreeJsonDBCallAPI ERROR:  Failed to call".printValue($post);exit;
}