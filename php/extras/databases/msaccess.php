<?php
/*
	msaccess.php - a collection of Microsoft Access Database functions for use by WaSQL.
	
	References:
		https://stackoverflow.com/questions/19807081/how-to-connect-php-with-microsoft-access-database
		https://docs.faircom.com/doc/sqlref/sqlref.pdf
		https://stackoverflow.com/questions/29786865/showing-primary-key-via-php-and-ms-access-2010
		https://www.xspdf.com/resolution/21251422.html
*/
//---------- begin function msaccessGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=msaccessGetAllTableFields();
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
function msaccessGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'msaccessGetAllTableFields');
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
	$recs=msaccessQueryResults($query);
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
//---------- begin function msaccessGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=msaccessGetAllTableIndexes();
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
function msaccessGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'msaccessGetAllTableIndexes');
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
	$recs=msaccessQueryResults($query);
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
//---------- begin function msaccessDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_msaccess,dbuser_msaccess, and dbpass_msaccess
*	[-host] - msaccess server to connect to
* 	[-dbname] - name of database.
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return connection resource and sets the global $dbh_msaccess variable.
* @usage $dbh_msaccess=msaccessDBConnect($params);
*/
function msaccessDBConnect(){
	$params=msaccessParseConnectParams();
	//echo "msaccessDBConnect".printValue($params);exit;
	if(!isset($params['-connect'])){
		echo "msaccessDBConnect error: no connect params".printValue($params);
		exit;
	}
	global $dbh_msaccess;
	if(is_object($dbh_msaccess)){return $dbh_msaccess;}
	try{
		//set options.  https://www.php.net/manual/en/pdo.setattribute.php
		$dbh_msaccess = new PDO($params['-connect'],$params['-dbuser'],$params['-dbpass']);
		$dbh_msaccess->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh_msaccess;
	}
	catch (Exception $e) {
		$error=array("msaccessDBConnect Exception",$e,$params);
	    debugValue($error);
	    return json_encode($error);
	}
}
//---------- begin function msaccessExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_msaccess,dbuser_msaccess, and dbpass_msaccess
* @return boolean returns true if query succeeded
* @usage $ok=msaccessExecuteSQL("truncate table abc");
*/
function msaccessExecuteSQL($query,$return_error=1){
	global $dbh_msaccess;
	$dbh_msaccess=msaccessDBConnect();
	if(!is_object($dbh_msaccess)){
		$err=array(
			'function'=>'msaccessExecuteSQL',
			'message'=>'connect failed',
			'query'=>$query
		);
		debugValue($err);
		if($return_error==1){return $err;}
    	return 0;
	}
	try{
		$stmt = $dbh_msaccess->prepare($query);
		$stmt->execute();
		$stmt->closeCursor(); // this is not even required
		$stmt = null; // doing this is mandatory for connection to get closed
		$dbh_msaccess = null;
	}
	catch (Exception $e) {
		$err=array(
			'function'=>'msaccessExecuteSQL',
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
//---------- begin function msaccessGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=msaccessGetDBCount(array('-table'=>'states'));
*/
function msaccessGetDBCount($params=array()){
	if(!isset($params['-table'])){return null;}
	if(!stringContains($params['-table'],'.')){
		$schema=msaccessGetDBSchema();
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
	$recs=msaccessGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue(array(
			'function'=>'msaccessGetDBCount',
			'message'=>'get count failed',
			'error'=>$recs,
			'params'=>$params
		));
		return 0;
	}
	return $recs[0]['cnt'];
}

//---------- begin function msaccessGetDBFields ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_msaccess,dbuser_msaccess, and dbpass_msaccess
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=msaccessGetDBFieldInfo('test');
*/
function msaccessGetDBFields($table,$allfields=0){
	$table=strtolower($table);
	$query="select * from {$table} where 1=0";
	$params=msaccessParseConnectParams();
	//echo "msaccessDBConnect".printValue($params);exit;
	global $dbh_msaccess;
	$dbh_msaccess='';
	$fields=array();
	try{
		//set options.  https://www.php.net/manual/en/pdo.setattribute.php
		$dbh_msaccess = odbc_connect("Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq={$params['-dbname']};", $params['-dbuser'],$params['-dbpass']);
		$cols = odbc_exec($dbh_msaccess, $query);
    	$ncols = odbc_num_fields($cols);
		for($n=1; $n<=$ncols; $n++) {
      		$name = odbc_field_name($cols, $n);
     		$fields[]=$name;
    	}
		$dbh_msaccess='';
		return $fields;
	}
	catch (Exception $e) {
		$error=array("msaccessGetDBFields Exception",$e,$params);
	    debugValue($error);
	    $dbh_msaccess='';
	    return json_encode($error);
	}
	$dbh_msaccess='';
	return array();
}
//---------- begin function msaccessGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_msaccess,dbuser_msaccess, and dbpass_msaccess
* @return boolean returns true on success
* @usage $fieldinfo=msaccessGetDBFieldInfo('test');
*/
function msaccessGetDBFieldInfo($table){
	$table=strtolower($table);
	$query="select * from {$table} where 1=0";
	$params=msaccessParseConnectParams();
	//echo "msaccessDBConnect".printValue($params);exit;
	global $dbh_msaccess;
	$dbh_msaccess='';
	$fields=array();
	try{
		//set options.  https://www.php.net/manual/en/pdo.setattribute.php
		$dbh_msaccess = odbc_connect("Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq={$params['-dbname']};", $params['-dbuser'],$params['-dbpass']);
		$result = odbc_exec($dbh_msaccess, $query);
		$recs=array();
		for($i=1;$i<=odbc_num_fields($result);$i++){
			$field=strtolower(odbc_field_name($result,$i));
	        $recs[$field]=array(
	        	'table'		=> $table,
	        	'_dbtable'	=> $table,
				'name'		=> $field,
				'_dbfield'	=> $field,
				'type'		=> strtolower(odbc_field_type($result,$i)),
				'scale'		=> strtolower(odbc_field_scale($result,$i)),
				'precision'	=> strtolower(odbc_field_precision($result,$i)),
				'length'	=> strtolower(odbc_field_len($result,$i)),
				'num'		=> strtolower(odbc_field_num($result,$i))
			);
			$recs[$field]['_dbtype']=$recs[$field]['type'];
			$recs[$field]['_dbtype_ex']=$recs[$field]['type'];
			$recs[$field]['_dblength']=$recs[$field]['length'];
	    }
	    odbc_free_result($result);
	    $dbh_msaccess='';
		return $recs;
	}
	catch (Exception $e) {
		$error=array("msaccessGetDBFieldInfo Exception",$e,$params);
	    debugValue($error);
	    $dbh_msaccess='';
	    return json_encode($error);
	}
	$dbh_msaccess='';
	return array();
}
function msaccessGetDBIndexes($table=''){
	return msaccessGetDBTableIndexes($table);
}
function msaccessGetDBTableIndexes($table=''){
	$table=strtolower($table);
	$params=msaccessParseConnectParams();
	//echo "msaccessDBConnect".printValue($params);exit;
	global $dbh_msaccess;
	$dbh_msaccess='';
	$fields=array();
	try{
		//set options.  https://www.php.net/manual/en/pdo.setattribute.php
		$dbh_msaccess = odbc_connect("Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq={$params['-dbname']};", $params['-dbuser'],$params['-dbpass']);
		$statistics = odbc_statistics($dbh_msaccess, '', '', $table, SQL_INDEX_ALL, SQL_QUICK);
		echo printValue($statistics);exit;
		while (($row = odbc_fetch_array($statistics))) {
		    echo printValue($row);exit;
		}
		
		$dbh_msaccess='';
		return $fields;
	}
	catch (Exception $e) {
		$error=array("msaccessGetDBTableIndexes Exception",$e,$params);
	    debugValue($error);
	    $dbh_msaccess='';
	    return json_encode($error);
	}
	$dbh_msaccess='';
	return array();
}
//---------- begin function msaccessGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=msaccessGetDBRecord(array('-table'=>'tesl));
*/
function msaccessGetDBRecord($params=array()){
	$recs=msaccessGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function msaccessGetDBRecords
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
*	<?=msaccessGetDBRecords(array('-table'=>'notes'));?>
*	<?=msaccessGetDBRecords("select * from myschema.mytable where ...");?>
*/
function msaccessGetDBRecords($params){
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
			$ok=msaccessExecuteSQL($params);
			return $ok;
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
	}
	else{
		if(empty($params['-table'])){
			debugValue(array(
				'function'=>'msaccessGetDBRecords',
				'message'=>'no table',
				'params'=>$params
			));
	    	return null;
		}
		//check for schema name
		if(!stringContains($params['-table'],'.')){
			$schema=msaccessGetDBSchema();
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
		$fields=msaccessGetDBFieldInfo($params['-table'],$params);
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
	return msaccessQueryResults($query,$params);
}

//---------- begin function msaccessIsDBTable ----------
/**
* @describe returns true if table already exists
* @param table string
* @return boolean
* @usage if(msaccessIsDBTable('_users')){...}
*/
function msaccessIsDBTable($table='',$force=0){
	global $databaseCache;
	$table=strtolower($table);
	if($force==0 && isset($databaseCache['msaccessIsDBTable'][$table])){
		return $databaseCache['msaccessIsDBTable'][$table];
	}
	$tables=msaccessGetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function msaccessGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_msaccess,dbuser_msaccess, and dbpass_msaccess
* @return array returns array of tables
* @usage $tables=msaccessGetDBTables();
*/
function msaccessGetDBTables($params=array()){
	$params=msaccessParseConnectParams();
	//echo "msaccessDBConnect".printValue($params);exit;
	global $dbh_msaccess;
	$dbh_msaccess='';
	$tables=array();
	try{
		//set options.  https://www.php.net/manual/en/pdo.setattribute.php
		$dbh_msaccess = odbc_connect("Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq={$params['-dbname']};", $params['-dbuser'],$params['-dbpass']);
		$result = odbc_tables($dbh_msaccess);
		$tblRow = 1;
		while (odbc_fetch_row($result)){
			if(odbc_result($result,"TABLE_TYPE")=="TABLE"){
		    	$tables[]= odbc_result($result,"TABLE_NAME");
		  	}  
		}
		sort($tables);
		$dbh_msaccess='';
		return $tables;
	}
	catch (Exception $e) {
		$error=array("msaccessGetDBTables Exception",$e,$params);
	    debugValue($error);
	    $dbh_msaccess='';
	    return json_encode($error);
	}
	$dbh_msaccess='';
	return array();
}
//---------- begin function msaccessGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param table string - specified table
* @return array returns array of primary key fields
* @usage $fields=msaccessGetDBTablePrimaryKeys($table);
*/
function msaccessGetDBTablePrimaryKeys($table){
	$query=<<<ENDOFQUERY
		SELECT
			colname
		FROM admin.sysindexes
		WHERE
			tbl='{$table}'
			and idxtype='U'
ENDOFQUERY;
	return msaccessQueryResults($query);
	
}
function msaccessGetDBSchema(){
	global $CONFIG;
	$params=msaccessParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	if(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['msaccess_dbschema'])){return $CONFIG['msaccess_dbschema'];}
	elseif(isset($CONFIG['msaccess_schema'])){return $CONFIG['msaccess_schema'];}
	return '';
}

function msaccessGetConfigValue($field){
	//dbschema, dbname
	global $CONFIG;
	switch(strtolower($CONFIG['dbtype'])){
		case 'msaccess':
			if(isset($CONFIG[$field])){return $CONFIG[$field];}
			elseif(isset($CONFIG["msaccess_{$field}"])){return $CONFIG["msaccess_{$field}"];}
		break;
		default:
			if(isset($CONFIG["msaccess_{$field}"])){return $CONFIG["msaccess_{$field}"];}
		break;
	}
	return null;
}
//---------- begin function msaccessListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function msaccessListRecords($params=array()){
	$params['-database']='msaccess';
	return databaseListRecords($params);
}
//---------- begin function msaccessParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_msaccess,dbuser_msaccess, and dbpass_msaccess
*	[-host] - msaccess server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=msaccessParseConnectParams($params);
*/
function msaccessParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^msaccess/i',$k)){unset($CONFIG[$k]);}
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
	if(isMsaccess()){
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
		if(isset($CONFIG['dbhost_msaccess'])){
			$params['-dbhost']=$CONFIG['dbhost_msaccess'];
			//$params['-dbhost_source']="CONFIG dbhost_msaccess";
		}
		elseif(isset($CONFIG['msaccess_dbhost'])){
			$params['-dbhost']=$CONFIG['msaccess_dbhost'];
			//$params['-dbhost_source']="CONFIG msaccess_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		//$params['-dbhost_source']="passed in";
	}
	$CONFIG['msaccess_dbhost']=$params['-dbhost'];
	
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_msaccess'])){
			$params['-dbuser']=$CONFIG['dbuser_msaccess'];
			//$params['-dbuser_source']="CONFIG dbuser_msaccess";
		}
		elseif(isset($CONFIG['msaccess_dbuser'])){
			$params['-dbuser']=$CONFIG['msaccess_dbuser'];
			//$params['-dbuser_source']="CONFIG msaccess_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['msaccess_dbuser']=$params['-dbuser'];
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_msaccess'])){
			$params['-dbpass']=$CONFIG['dbpass_msaccess'];
			//$params['-dbpass_source']="CONFIG dbpass_msaccess";
		}
		elseif(isset($CONFIG['msaccess_dbpass'])){
			$params['-dbpass']=$CONFIG['msaccess_dbpass'];
			//$params['-dbpass_source']="CONFIG msaccess_dbpass";
		}
	}
	else{
		//$params['-dbpass_source']="passed in";
	}
	$CONFIG['msaccess_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_msaccess'])){
			$params['-dbname']=$CONFIG['dbname_msaccess'];
			//$params['-dbname_source']="CONFIG dbname_msaccess";
		}
		elseif(isset($CONFIG['msaccess_dbname'])){
			$params['-dbname']=$CONFIG['msaccess_dbname'];
			//$params['-dbname_source']="CONFIG msaccess_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['msaccess_dbname'];
			//$params['-dbname_source']="set to username";
		}
	}
	else{
		//$params['-dbname_source']="passed in";
	}
	$CONFIG['msaccess_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_msaccess'])){
			$params['-dbport']=$CONFIG['dbport_msaccess'];
			//$params['-dbport_source']="CONFIG dbport_msaccess";
		}
		elseif(isset($CONFIG['msaccess_dbport'])){
			$params['-dbport']=$CONFIG['msaccess_dbport'];
			//$params['-dbport_source']="CONFIG msaccess_dbport";
		}
		else{
			$params['-dbport']=5432;
			//$params['-dbport_source']="default port";
		}
	}
	else{
		//$params['-dbport_source']="passed in";
	}
	$CONFIG['msaccess_dbport']=$params['-dbport'];
	//dbschema
	if(!isset($params['-dbschema'])){
		if(isset($CONFIG['dbschema_msaccess'])){
			$params['-dbschema']=$CONFIG['dbschema_msaccess'];
			//$params['-dbuser_source']="CONFIG dbuser_msaccess";
		}
		elseif(isset($CONFIG['msaccess_dbschema'])){
			$params['-dbschema']=$CONFIG['msaccess_dbschema'];
			//$params['-dbuser_source']="CONFIG msaccess_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['msaccess_dbschema']=$params['-dbschema'];
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['msaccess_connect'])){
			$params['-connect']=$CONFIG['msaccess_connect'];
		}
		elseif(isset($CONFIG['connect_msaccess'])){
			$params['-connect']=$CONFIG['connect_msaccess'];
		}
		else{
			//ODBC;DSN=REPL01;HOST=repl01.dot.infotraxsys.com;UID=dot_dels;DATABASE=liveSQL;SERVICE=6597;CHARSET NAME=;MAXROWS=;OPTIONS=;;PRSRVCUR=OFF;;FILEDSN=;SAVEFILE=;FETCH_SIZE=;QUERY_TIMEOUT=;SCROLLCUR=OF
			$params['-connect']="odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq={$CONFIG['msaccess_dbname']};";
		}
	}
	else{
		//$params['-connect_source']="passed in";
	}
	//echo printValue($params);exit;
	return $params;
}
//---------- begin function msaccessQueryResults ----------
/**
* @describe returns the msaccess record set
* @param query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_msaccess,dbuser_msaccess, and dbpass_msaccess
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return array - returns records
*/
function msaccessQueryResults($query='',$params=array()){
	$query=trim($query);
	global $USER;
	global $dbh_msaccess;
	if(!is_object($dbh_msaccess)){
		$dbh_msaccess=msaccessDBConnect();
	}
	if(!$dbh_msaccess){
		$error=array("msaccessQueryResults Connect Error",$query);
	    debugValue($error);
	    return json_encode($error);
	}
	try{
		$data = $dbh_msaccess->query($query);
		$recs = msaccessEnumQueryResults($data,$params,$query);
		$dbh_msaccess=null;
		return $recs;
	}
	catch (Exception $e) {
		$error=array("msaccessQueryResults Connect Error",$e,$query);
	    debugValue($error);
	    return json_encode($error);
	}
}
//---------- begin function msaccessEnumQueryResults ----------
/**
* @describe enumerates through the data from a msaccess query
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function msaccessEnumQueryResults($data,$params=array(),$query=''){
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
			$data=null;
			$error=array("msaccessEnumQueryResults File Open Error",$params,$query);
		    debugValue($error);
		    return json_encode($error);
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	$rowcount=0;
	for($i=0; $row = $data->fetch(PDO::FETCH_ASSOC); $i++){
		$rec=array();
		foreach($row as $key=>$val){
			$key=strtolower($key);
			$rec[$key]=trim($val);
			$rec[$key]=preg_replace('/[\r\n]+/',' ', $rec[$key]);
			$rec[$key]=str_replace(chr(8),'',$rec[$key]);
			$rec[$key]=trim($val);
			if(preg_match('/\_(id|rank|number)$/is',$key) && preg_match('/^([0-9\.]+)/',$rec[$key],$m)){
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
    	$rowcount+=1;
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
			if(isset($params['-logfile']) && file_exists($params['-logfile']) && $rowcount % 5000 == 0){
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
	$data=null;
	if(isset($fh) && is_resource($fh)){
		@fclose($fh);
		if(isset($params['-logfile']) && file_exists($params['-logfile'])){
			$elapsed=microtime(true)-$starttime;
			appendFileContents($params['-logfile'],"Line count:{$rowcount}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
		}
		return $i;
	}
	return $recs;
}

//---------- begin function msaccessNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function msaccessNamedQuery($name){
	switch(strtolower($name)){
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
