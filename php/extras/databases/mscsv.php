<?php
/*
	mscsv.php - a collection of Microsoft Access Database functions for use by WaSQL.
	
	<database
        group="MS CSV"
        dbicon="icon-application-excel"
        name="mscsv_delegate"
        displayname="MS Excel Delegate"
        dbname="d:/temp/delegate.csv"
        dbtype="mscsv"
    />

    select top 5 * from all_colors.csv where code like '%alice%'
	insert into all_colors.csv (code,name,hex,red,green,blue) values('alice_test','test','#111222',1,2,3)

	NOTE: Delete and Update are not supported - only select and inserts
	
	References:
		https://www.connectionstrings.com/microsoft-text-odbc-driver/
		https://docs.querona.io/quickstart/how-to/text-microsoft-odbc.html
0*/
//---------- begin function mscsvGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=mscsvGetAllTableFields();
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
function mscsvGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'mscsvGetAllTableFields');
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
	$recs=mscsvQueryResults($query);
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
//---------- begin function mscsvGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=mscsvGetAllTableIndexes();
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
function mscsvGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'mscsvGetAllTableIndexes');
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
	$recs=mscsvQueryResults($query);
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
//---------- begin function mscsvDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_mscsv,dbuser_mscsv, and dbpass_mscsv
*	[-host] - mscsv server to connect to
* 	[-dbname] - name of database.
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return connection resource and sets the global $dbh_mscsv variable.
* @usage $dbh_mscsv=mscsvDBConnect($params);
*/
function mscsvDBConnect(){
	global $dbh_mscsv;
	$params=mscsvParseConnectParams();
	$dir=getFilePath($params['-dbname']);
	/*


	*/
	if(PHP_INT_SIZE===8){
		$driver='Driver={Microsoft Access Text Driver (*.txt, *.csv)}';
	}
	else{
		$driver='Driver={Microsoft Text Driver (*.txt; *.csv)}';	
	}
	$parts=array(
		$driver,
		"Dbq={$params['-dbname']}",
		'FIL=text',
		'DriverId=27',
		'Extensions=asc,csv,tab,txt',
		'ImportMixedTypes=Text',
		'ReadOnly=false',
		'Extended Properties="Mode=ReadWrite;ReadOnly=false;MaxScanRows=16;HDR=YES"',
	);
	$params['-connect']=implode(';',$parts);
	$dbh_mscsv = odbc_connect($params['-connect'], '','');
	return $dbh_mscsv;
}
//---------- begin function mscsvExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_mscsv,dbuser_mscsv, and dbpass_mscsv
* @return boolean returns true if query succeeded
* @usage $ok=mscsvExecuteSQL("truncate table abc");
*/
function mscsvExecuteSQL($query,$return_error=1){
	global $dbh_mscsv;
	$dbh_mscsv=mscsvDBConnect();
	if(!is_object($dbh_mscsv)){
		$err=array(
			'function'=>'mscsvExecuteSQL',
			'message'=>'connect failed',
			'query'=>$query
		);
		debugValue($err);
		if($return_error==1){return $err;}
    	return 0;
	}
	try{
		$stmt = $dbh_mscsv->prepare($query);
		$stmt->execute();
		$stmt->closeCursor(); // this is not even required
		$stmt = null; // doing this is mandatory for connection to get closed
		$dbh_mscsv = null;
	}
	catch (Exception $e) {
		$err=array(
			'function'=>'mscsvExecuteSQL',
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
//---------- begin function mscsvGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=mscsvGetDBCount(array('-table'=>'states'));
*/
function mscsvGetDBCount($params=array()){
	if(!isset($params['-table'])){return null;}
	if(!stringContains($params['-table'],'.')){
		$schema=mscsvGetDBSchema();
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
	$recs=mscsvGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue(array(
			'function'=>'mscsvGetDBCount',
			'message'=>'get count failed',
			'error'=>$recs,
			'params'=>$params
		));
		return 0;
	}
	return $recs[0]['cnt'];
}

//---------- begin function mscsvGetDBFields ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_mscsv,dbuser_mscsv, and dbpass_mscsv
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=mscsvGetDBFieldInfo('test');
*/
function mscsvGetDBFields($table,$allfields=0){
	$table=strtolower($table);
	$query="select * from {$table} where 1=0";
	global $dbh_mscsv;
	$dbh_mscsv='';
	$fields=array();
	try{
		$dbh_mscsv = mscsvDBConnect();
		$cols = odbc_exec($dbh_mscsv, $query);
    	$ncols = odbc_num_fields($cols);
		for($n=1; $n<=$ncols; $n++) {
      		$name = odbc_field_name($cols, $n);
     		$fields[]=$name;
    	}
		$dbh_mscsv='';
		return $fields;
	}
	catch (Exception $e) {
		$error=array("mscsvGetDBFields Exception",$e,$params);
	    debugValue($error);
	    $dbh_mscsv='';
	    return json_encode($error);
	}
	$dbh_mscsv='';
	return array();
}
//---------- begin function mscsvGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_mscsv,dbuser_mscsv, and dbpass_mscsv
* @return boolean returns true on success
* @usage $fieldinfo=mscsvGetDBFieldInfo('test');
*/
function mscsvGetDBFieldInfo($table){
	$table=strtolower($table);
	$query="select * from {$table} where 1=0";
	//echo "mscsvDBConnect".printValue($params);exit;
	global $dbh_mscsv;
	$dbh_mscsv='';
	$fields=array();
	try{
		$dbh_mscsv = mscsvDBConnect();
		$result = odbc_exec($dbh_mscsv, $query);
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
	    $dbh_mscsv='';
		return $recs;
	}
	catch (Exception $e) {
		$error=array("mscsvGetDBFieldInfo Exception",$e,$params);
	    debugValue($error);
	    $dbh_mscsv='';
	    return json_encode($error);
	}
	$dbh_mscsv='';
	return array();
}
function mscsvGetDBIndexes($table=''){
	return mscsvGetDBTableIndexes($table);
}
function mscsvGetDBTableIndexes($table=''){
	return array();
}
//---------- begin function mscsvGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=mscsvGetDBRecord(array('-table'=>'tesl));
*/
function mscsvGetDBRecord($params=array()){
	$recs=mscsvGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function mscsvGetDBRecords
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
*	<?=mscsvGetDBRecords(array('-table'=>'notes'));?>
*	<?=mscsvGetDBRecords("select * from myschema.mytable where ...");?>
*/
function mscsvGetDBRecords($params){
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
			$ok=mscsvExecuteSQL($params);
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
				'function'=>'mscsvGetDBRecords',
				'message'=>'no table',
				'params'=>$params
			));
	    	return null;
		}
		//check for schema name
		if(!stringContains($params['-table'],'.')){
			$schema=mscsvGetDBSchema();
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
		$fields=mscsvGetDBFieldInfo($params['-table'],$params);
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
	return mscsvQueryResults($query,$params);
}

//---------- begin function mscsvIsDBTable ----------
/**
* @describe returns true if table already exists
* @param table string
* @return boolean
* @usage if(mscsvIsDBTable('_users')){...}
*/
function mscsvIsDBTable($table='',$force=0){
	global $databaseCache;
	$table=strtolower($table);
	if($force==0 && isset($databaseCache['mscsvIsDBTable'][$table])){
		return $databaseCache['mscsvIsDBTable'][$table];
	}
	$tables=mscsvGetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function mscsvGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_mscsv,dbuser_mscsv, and dbpass_mscsv
* @return array returns array of tables
* @usage $tables=mscsvGetDBTables();
*/
function mscsvGetDBTables($params=array()){
	$params=mscsvParseConnectParams();
	$dir=getFilePath($params['-dbname']);
	$files=listFilesEx($params['-dbname'],array('ext'=>'csv'));
	$tables=array();
	foreach($files as $file){
		$tables[]=$file['name'];
	}
	sort($tables);
	return $tables;
}
function mscsvGetSheetNamesFromXlsx($file){
	$worksheetNames = array ();
    $zip = zip_open ( $file );
    while ( $entry = zip_read ( $zip ) ) {
        $entry_name = zip_entry_name ( $entry );
        if ($entry_name == 'xl/workbook.xml') {
            if (zip_entry_open ( $zip, $entry, "r" )) {
                $buf = zip_entry_read ( $entry, zip_entry_filesize ( $entry ) );
                $workbook = simplexml_load_string ( $buf );
                foreach ( $workbook->sheets as $sheets ) {
                    foreach( $sheets as $sheet) {
                        $attributes=(array)$sheet->attributes();
                        $worksheetNames[]='['.$attributes['@attributes']['name'].'$]';
                    }
                }
                zip_entry_close ( $entry );
            }
            break;
        }

    }
    zip_close ( $zip );
    return $worksheetNames;
}
//---------- begin function mscsvGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param table string - specified table
* @return array returns array of primary key fields
* @usage $fields=mscsvGetDBTablePrimaryKeys($table);
*/
function mscsvGetDBTablePrimaryKeys($table){
	$query=<<<ENDOFQUERY
		SELECT
			colname
		FROM admin.sysindexes
		WHERE
			tbl='{$table}'
			and idxtype='U'
ENDOFQUERY;
	return mscsvQueryResults($query);
	
}
function mscsvGetDBSchema(){
	global $CONFIG;
	$params=mscsvParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	if(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['mscsv_dbschema'])){return $CONFIG['mscsv_dbschema'];}
	elseif(isset($CONFIG['mscsv_schema'])){return $CONFIG['mscsv_schema'];}
	return '';
}

function mscsvGetConfigValue($field){
	//dbschema, dbname
	global $CONFIG;
	switch(strtolower($CONFIG['dbtype'])){
		case 'mscsv':
			if(isset($CONFIG[$field])){return $CONFIG[$field];}
			elseif(isset($CONFIG["mscsv_{$field}"])){return $CONFIG["mscsv_{$field}"];}
		break;
		default:
			if(isset($CONFIG["mscsv_{$field}"])){return $CONFIG["mscsv_{$field}"];}
		break;
	}
	return null;
}
//---------- begin function mscsvListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function mscsvListRecords($params=array()){
	$params['-database']='mscsv';
	return databaseListRecords($params);
}
//---------- begin function mscsvParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_mscsv,dbuser_mscsv, and dbpass_mscsv
*	[-host] - mscsv server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=mscsvParseConnectParams($params);
*/
function mscsvParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^mscsv/i',$k)){unset($CONFIG[$k]);}
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
	if(isMscsv()){
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
		if(isset($CONFIG['dbhost_mscsv'])){
			$params['-dbhost']=$CONFIG['dbhost_mscsv'];
			//$params['-dbhost_source']="CONFIG dbhost_mscsv";
		}
		elseif(isset($CONFIG['mscsv_dbhost'])){
			$params['-dbhost']=$CONFIG['mscsv_dbhost'];
			//$params['-dbhost_source']="CONFIG mscsv_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		//$params['-dbhost_source']="passed in";
	}
	$CONFIG['mscsv_dbhost']=$params['-dbhost'];
	
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_mscsv'])){
			$params['-dbuser']=$CONFIG['dbuser_mscsv'];
			//$params['-dbuser_source']="CONFIG dbuser_mscsv";
		}
		elseif(isset($CONFIG['mscsv_dbuser'])){
			$params['-dbuser']=$CONFIG['mscsv_dbuser'];
			//$params['-dbuser_source']="CONFIG mscsv_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['mscsv_dbuser']=$params['-dbuser'];
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_mscsv'])){
			$params['-dbpass']=$CONFIG['dbpass_mscsv'];
			//$params['-dbpass_source']="CONFIG dbpass_mscsv";
		}
		elseif(isset($CONFIG['mscsv_dbpass'])){
			$params['-dbpass']=$CONFIG['mscsv_dbpass'];
			//$params['-dbpass_source']="CONFIG mscsv_dbpass";
		}
	}
	else{
		//$params['-dbpass_source']="passed in";
	}
	$CONFIG['mscsv_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_mscsv'])){
			$params['-dbname']=$CONFIG['dbname_mscsv'];
			//$params['-dbname_source']="CONFIG dbname_mscsv";
		}
		elseif(isset($CONFIG['mscsv_dbname'])){
			$params['-dbname']=$CONFIG['mscsv_dbname'];
			//$params['-dbname_source']="CONFIG mscsv_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['mscsv_dbname'];
			//$params['-dbname_source']="set to username";
		}
	}
	else{
		//$params['-dbname_source']="passed in";
	}
	$CONFIG['mscsv_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_mscsv'])){
			$params['-dbport']=$CONFIG['dbport_mscsv'];
			//$params['-dbport_source']="CONFIG dbport_mscsv";
		}
		elseif(isset($CONFIG['mscsv_dbport'])){
			$params['-dbport']=$CONFIG['mscsv_dbport'];
			//$params['-dbport_source']="CONFIG mscsv_dbport";
		}
		else{
			$params['-dbport']=5432;
			//$params['-dbport_source']="default port";
		}
	}
	else{
		//$params['-dbport_source']="passed in";
	}
	$CONFIG['mscsv_dbport']=$params['-dbport'];
	//dbschema
	if(!isset($params['-dbschema'])){
		if(isset($CONFIG['dbschema_mscsv'])){
			$params['-dbschema']=$CONFIG['dbschema_mscsv'];
			//$params['-dbuser_source']="CONFIG dbuser_mscsv";
		}
		elseif(isset($CONFIG['mscsv_dbschema'])){
			$params['-dbschema']=$CONFIG['mscsv_dbschema'];
			//$params['-dbuser_source']="CONFIG mscsv_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['mscsv_dbschema']=$params['-dbschema'];
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['mscsv_connect'])){
			$params['-connect']=$CONFIG['mscsv_connect'];
		}
		elseif(isset($CONFIG['connect_mscsv'])){
			$params['-connect']=$CONFIG['connect_mscsv'];
		}
		else{
			//ODBC;DSN=REPL01;HOST=repl01.dot.infotraxsys.com;UID=dot_dels;DATABASE=liveSQL;SERVICE=6597;CHARSET NAME=;MAXROWS=;OPTIONS=;;PRSRVCUR=OFF;;FILEDSN=;SAVEFILE=;FETCH_SIZE=;QUERY_TIMEOUT=;SCROLLCUR=OF
			$dir=getFilePath($CONFIG['mscsv_dbname']);
			$params['-connect']="odbc:Driver={Microsoft Excel Driver (*.xls, *.xlsx, *.xlsm, *.xlsb)};DriverId=790;Dbq={$CONFIG['mscsv_dbname']};DefaultDir={$dir};";
		}
	}
	else{
		//$params['-connect_source']="passed in";
	}
	//echo printValue($params);exit;
	return $params;
}
//---------- begin function mscsvQueryResults ----------
/**
* @describe returns the mscsv record set
* @param query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_mscsv,dbuser_mscsv, and dbpass_mscsv
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return array - returns records
*/
function mscsvQueryResults($query='',$params=array()){
	$query=trim($query);
	global $USER;
	global $dbh_mscsv;
	$dbh_mscsv=mscsvDBConnect();
	try{
		$result=odbc_exec($dbh_mscsv,$query);
		if(!$result){
			$e=odbc_errormsg($dbh_mscsv);
			$error=array("odbcQueryResults Error",$e,$query);
			debugValue($error);
			return json_encode($error);
		}
		$results=mscsvEnumQueryResults($result,$params);
		return $results;
	}
	catch (Exception $e) {
		$error=array("mscsvQueryResults Connect Error",$e,$query);
	    debugValue($error);
	    return json_encode($error);
	}
	return array();
}
//---------- begin function mscsvEnumQueryResults ----------
/**
* @describe enumerates through the data from a mscsv query
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function mscsvEnumQueryResults($result,$params=array(),$query=''){
	$i=0;
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
	while(odbc_fetch_row($result)){
		$rec=array();
	    for($z=1;$z<=odbc_num_fields($result);$z++){
			$field=strtolower(odbc_field_name($result,$z));
	        $rec[$field]=odbc_result($result,$z);
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
			$i++;
			continue;
		}
		else{
			$recs[]=$rec;
		}
	}
	odbc_free_result($result);
	if(isset($fh) && is_resource($fh)){
		@fclose($fh);
		if(isset($params['-logfile']) && file_exists($params['-logfile'])){
			$elapsed=microtime(true)-$starttime;
			appendFileContents($params['-logfile'],"Line count:{$i}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
		}
		return $i;
	}
	elseif(isset($params['-process'])){
		return $i;
	}
	return $recs;
}

//---------- begin function mscsvNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function mscsvNamedQuery($name){
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
