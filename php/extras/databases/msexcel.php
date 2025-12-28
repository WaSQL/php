<?php
/*
	msexcel.php - a collection of Microsoft Excel Database functions for use by WaSQL.

	<database
        group="MS Excel"
        dbicon="icon-application-excel"
        name="msexcel_delegate"
        displayname="MS Excel Delegate"
        dbname="d:/temp/delegate.xlsx"
        dbtype="msexcel"
    />
	
	References:
		https://stackoverflow.com/questions/5029531/using-microsoft-excel-via-an-odbc-driver-with-php/5029625
		https://m.php.cn/manual/view/1903.html
		https://www.youtube.com/watch?v=l-7P-9VVjw

	Required PHP Extensions:
		- odbc: For database connectivity
		- zip: For reading xlsx files (ZipArchive class recommended, fallback to deprecated zip_* functions)
*/

// Check for required PHP extensions
if(!extension_loaded('odbc')){
	trigger_error('msexcel.php requires the PHP odbc extension to be loaded', E_USER_WARNING);
}
if(!extension_loaded('zip')){
	trigger_error('msexcel.php requires the PHP zip extension for xlsx file support', E_USER_WARNING);
}
if(!class_exists('ZipArchive')){
	trigger_error('msexcel.php: ZipArchive class not available, will fallback to deprecated zip_* functions', E_USER_NOTICE);
}


//---------- begin function msexcelCloseDBConnection ----------
/**
* @describe properly closes a msexcel database connection
* @param $conn resource - connection resource to close
* @return boolean - true if closed successfully
* @usage msexcelCloseDBConnection($dbh_msexcel);
*/
function msexcelCloseDBConnection($conn=null){
	global $dbh_msexcel;
	if($conn!==null && is_resource($conn)){
		@odbc_close($conn);
		return true;
	}
	if(isset($dbh_msexcel) && is_resource($dbh_msexcel)){
		@odbc_close($dbh_msexcel);
		$dbh_msexcel=null;
		return true;
	}
	return false;
}
//---------- begin function msexcelValidateIdentifier ----------
/**
* @describe validates SQL identifiers (table names, field names) to prevent SQL injection
* @param $identifier string - identifier to validate
* @return string - validated identifier or empty string if invalid
* @usage $safe_table=msexcelValidateIdentifier($table);
*/
function msexcelValidateIdentifier($identifier){
	// Remove any characters that aren't alphanumeric, underscore, dollar sign, or brackets
	// Excel sheet names can contain spaces and special characters, wrapped in brackets
	$identifier=trim($identifier);
	// Check for null bytes and other control characters
	if(preg_match('/[\x00-\x1F\x7F]/',$identifier)){
		return '';
	}
	// For Excel sheet names in bracket notation like [Sheet1$]
	if(preg_match('/^\[[\w\s\-\.]+\$\]$/',$identifier)){
		return $identifier;
	}
	// For regular identifiers (letters, numbers, underscore, dollar)
	if(preg_match('/^[\w\$]+$/',$identifier)){
		return $identifier;
	}
	return '';
}
//---------- begin function msexcelGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=msexcelGetAllTableFields();
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
function msexcelGetAllTableFields(){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'msexcelGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	$databaseCache[$cachekey]=array();
	$tables=msexcelGetDBTables();
	foreach($tables as $table){
		$finfo=msexcelGetDBFieldInfo($table);
		foreach($finfo as $field=>$info){
			$databaseCache[$cachekey][$table][]=array(
				'table_name'=>$table,
				'field_name'=>$field,
				'type_name'=>$info['_dbtype_ex']
			);
		}	
	}
	ksort($databaseCache[$cachekey]);
	return $databaseCache[$cachekey];
}
//---------- begin function msexcelGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=msexcelGetAllTableIndexes();
*/
function msexcelGetAllTableIndexes($schema=''){
	return array();
}
//---------- begin function msexcelDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_msexcel,dbuser_msexcel, and dbpass_msexcel
* @return connection resource and sets the global $dbh_msexcel variable.
* @usage $dbh_msexcel=msexcelDBConnect($params);
*/
function msexcelDBConnect(){
	global $dbh_msexcel;
	$params=msexcelParseConnectParams();

	// Validate database file exists
	if(empty($params['-dbname'])){
		debugValue(array(
			'function'=>'msexcelDBConnect',
			'message'=>'Database file name not specified',
			'params'=>$params
		));
		return false;
	}
	if(!file_exists($params['-dbname'])){
		debugValue(array(
			'function'=>'msexcelDBConnect',
			'message'=>'Database file does not exist',
			'file'=>$params['-dbname']
		));
		return false;
	}

	// Validate file extension
	$ext=strtolower(getFileExtension($params['-dbname']));
	$valid_extensions=array('xls','xlsx','xlsm','xlsb');
	if(!in_array($ext,$valid_extensions)){
		debugValue(array(
			'function'=>'msexcelDBConnect',
			'message'=>'Invalid file extension. Must be xls, xlsx, xlsm, or xlsb',
			'file'=>$params['-dbname'],
			'extension'=>$ext
		));
		return false;
	}

	// Validate file is readable
	if(!is_readable($params['-dbname'])){
		debugValue(array(
			'function'=>'msexcelDBConnect',
			'message'=>'Database file is not readable',
			'file'=>$params['-dbname']
		));
		return false;
	}

	$dir=getFilePath($params['-dbname']);
	/*
		DriverID=790;
		FIL=Excel 12.0;DriverID=1046;
		ImportMixedTypes=Text; ReadOnly=false;
		HDR=YES - indicates that the first row contains column names, not data
		MaxScanRows  - rows to test before deciding the data type of the column  1 - 16
		Extended Properties="Mode=ReadWrite;ReadOnly=false;MaxScanRows=16HDR=YES"

		$connection.GetSchema('TABLES')
		$connection.GetSchema('DATATYPES')
		$connection.GetSchema('DataSourceInformation')
		$connection.GetSchema('Restrictions')
		$connection.GetSchema('ReservedWords')
		$connection.GetSchema('Columns')
		$connection.GetSchema('Indexes')
		$connection.GetSchema('Views') 

	*/
	$parts=array(
		'Driver={Microsoft Excel Driver (*.xls, *.xlsx, *.xlsm, *.xlsb)}',
		'FIL=Excel 12.0',
		'DriverID=1046', //1046 supports .xls, .xlsx, .xlsm, and .xlsb. 790 only supports xls
		"Dbq={$params['-dbname']}",
		"DefaultDir={$dir}",
		'ImportMixedTypes=Text',
		'ReadOnly=false',
		'IMEX=1',
		'MaxScanRows=2',
		'Extended Properties="Mode=ReadWrite;ReadOnly=false;MaxScanRows=2;HDR=YES"',
	);
	$params['-connect']=implode(';',$parts);

	// Suppress ODBC warnings and handle errors gracefully
	$dbh_msexcel = @odbc_connect($params['-connect'], '','');

	if(!$dbh_msexcel){
		$error_msg = '';
		if(function_exists('odbc_errormsg')){
			$error_msg = odbc_errormsg();
		}
		debugValue(array(
			'function'=>'msexcelDBConnect',
			'message'=>'ODBC connection failed',
			'file'=>$params['-dbname'],
			'odbc_error'=>$error_msg
		));
		return false;
	}

	return $dbh_msexcel;
}
//---------- begin function msexcelExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param @return int returns 1 if query succeeded, else 0
* @usage $ok=msexcelExecuteSQL("truncate table abc");
*/
function msexcelExecuteSQL($query){
	global $dbh_msexcel;
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'msexcelExecuteSQL'
	);
	$local_conn=null;
	try{
		$local_conn = msexcelDBConnect();
		if(!$local_conn){
			$DATABASE['_lastquery']['error']='Failed to connect to database';
			$DATABASE['_lastquery']['stop']=microtime(true);
			$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
			debugValue($DATABASE['_lastquery']);
			return 0;
		}
		$result = odbc_exec($local_conn, $query);
		msexcelCloseDBConnection($local_conn);
		if(!$result){
			$DATABASE['_lastquery']['error']='Query execution failed';
			$DATABASE['_lastquery']['stop']=microtime(true);
			$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
			debugValue($DATABASE['_lastquery']);
			return 0;
		}
		$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
		return 1;
	}
	catch (Exception $e) {
		$DATABASE['_lastquery']['error']=$e->getMessage();
		$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
		debugValue($DATABASE['_lastquery']);
	    msexcelCloseDBConnection($local_conn);
	    return 0;
	}
}
//---------- begin function msexcelGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage $cnt=msexcelGetDBCount(array('-table'=>'states'));
*/
function msexcelGetDBCount($params=array()){
	if(!isset($params['-table'])){return null;}
	//echo printValue($params);exit;
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	//$params['-debug']=1;
	$recs=msexcelGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue(array(
			'function'=>'msexcelGetDBCount',
			'message'=>'get count failed',
			'error'=>$recs,
			'params'=>$params
		));
		return 0;
	}
	return $recs[0]['cnt'];
}

//---------- begin function msexcelGetDBFields ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_msexcel,dbuser_msexcel, and dbpass_msexcel
* @return boolean returns true on success
* @usage $fieldinfo=msexcelGetDBFieldInfo('test');
*/
function msexcelGetDBFields($table,$allfields=0){
	$table=strtolower($table);
	// Validate table name to prevent SQL injection
	$validated_table=msexcelValidateIdentifier($table);
	if(empty($validated_table)){
		debugValue(array(
			'function'=>'msexcelGetDBFields',
			'message'=>'invalid table name',
			'table'=>$table
		));
		return array();
	}
	$table=$validated_table;
	$query="select top 2 * from {$table}";
	$local_conn=null;
	$fields=array();
	try{
		$local_conn = msexcelDBConnect();
		if(!$local_conn){
			debugValue(array(
				'function'=>'msexcelGetDBFields',
				'message'=>'Failed to connect to database',
				'table'=>$table
			));
			return array();
		}
		$cols = odbc_exec($local_conn, $query);
		if(!$cols){
			msexcelCloseDBConnection($local_conn);
			debugValue(array(
				'function'=>'msexcelGetDBFields',
				'message'=>'Query execution failed',
				'table'=>$table,
				'query'=>$query
			));
			return array();
		}
    	$ncols = odbc_num_fields($cols);
		for($n=1; $n<=$ncols; $n++) {
      		$name = odbc_field_name($cols, $n);
     		$fields[]=$name;
    	}
    	odbc_free_result($cols);
		msexcelCloseDBConnection($local_conn);
		return $fields;
	}
	catch (Exception $e) {
		$error=array(
			"function"=>"msexcelGetDBFields",
			"message"=>"Exception occurred",
			"exception"=>$e->getMessage(),
			"table"=>$table,
			"query"=>$query
		);
	    debugValue($error);
	    msexcelCloseDBConnection($local_conn);
	    return array();
	}
}
//---------- begin function msexcelGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_msexcel,dbuser_msexcel, and dbpass_msexcel
* @return boolean returns true on success
* @usage $fieldinfo=msexcelGetDBFieldInfo('test');
*/
function msexcelGetDBFieldInfo($table){
	$table=strtolower($table);
	// Validate table name to prevent SQL injection
	$validated_table=msexcelValidateIdentifier($table);
	if(empty($validated_table)){
		debugValue(array(
			'function'=>'msexcelGetDBFieldInfo',
			'message'=>'invalid table name',
			'table'=>$table
		));
		return array();
	}
	$table=$validated_table;
	$query="select top 2 * from {$table}";
	$local_conn=null;
	$fields=array();
	try{
		$local_conn = msexcelDBConnect();
		if(!$local_conn){
			debugValue(array(
				'function'=>'msexcelGetDBFieldInfo',
				'message'=>'Failed to connect to database',
				'table'=>$table
			));
			return array();
		}
		$result = odbc_exec($local_conn, $query);
		if(!$result){
			msexcelCloseDBConnection($local_conn);
			debugValue(array(
				'function'=>'msexcelGetDBFieldInfo',
				'message'=>'Query execution failed',
				'table'=>$table,
				'query'=>$query
			));
			return array();
		}
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
	    msexcelCloseDBConnection($local_conn);
		return $recs;
	}
	catch (Exception $e) {
		$error=array(
			"function"=>"msexcelGetDBFieldInfo",
			"message"=>"Exception occurred",
			"exception"=>$e->getMessage(),
			"table"=>$table,
			"query"=>$query
		);
	    debugValue($error);
	    msexcelCloseDBConnection($local_conn);
	    return array();
	}
}
function msexcelGetDBIndexes($table=''){
	return msexcelGetDBTableIndexes($table);
}
function msexcelGetDBTableIndexes($table=''){
	return array();
}
//---------- begin function msexcelGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* @return array recordset
* @usage $rec=msexcelGetDBRecord(array('-table'=>'tesl));
*/
function msexcelGetDBRecord($params=array()){
	$recs=msexcelGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function msexcelGetDBRecords
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
*	msexcelGetDBRecords(array('-table'=>'notes'));
*	msexcelGetDBRecords("select * from myschema.mytable where ...");
*/
function msexcelGetDBRecords($params){
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
			debugValue(array(
				'function'=>'msexcelGetDBRecords',
				'message'=>'invalid params - not a table or query',
				'params'=>$params
			));
			return null;
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
	}
	else{
		if(empty($params['-table'])){
			debugValue(array(
				'function'=>'msexcelGetDBRecords',
				'message'=>'no table',
				'params'=>$params
			));
	    	return null;
		}
		//check for schema name
		if(stringContains($params['-table'],'.')){
			$parts=preg_split('/\./',$params['-table'],2);
			$params['-table']=$parts[1];
		}
		// Validate table name to prevent SQL injection
		$validated_table=msexcelValidateIdentifier($params['-table']);
		if(empty($validated_table)){
			debugValue(array(
				'function'=>'msexcelGetDBRecords',
				'message'=>'invalid table name',
				'table'=>$params['-table']
			));
			return null;
		}
		$params['-table']=$validated_table;
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
		$fields=msexcelGetDBFieldInfo($params['-table'],$params);
		//echo printValue($fields);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!strlen(trim($v))){continue;}
			if(!isset($fields[$k])){continue;}
			if(is_array($params[$k])){
	            $params[$k]=implode(':',$params[$k]);
			}
	        // SQL Injection Prevention: Escape single quotes by doubling them (SQL standard)
	        // Additional validation: Remove null bytes and control characters
	        $params[$k]=str_replace("\0",'',$params[$k]); // Remove null bytes
	        $params[$k]=str_replace("'","''",$params[$k]); // Escape single quotes
	        switch(strtolower($fields[$k]['_dbtype'])){
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
		$paginate='';
    	if(!isset($params['-nolimit'])){
	    	$offset=isset($params['-offset'])?$params['-offset']:0;
	    	$limit=25;
	    	if(!empty($params['-limit'])){$limit=$params['-limit'];}
	    	elseif(!empty($CONFIG['paging'])){$limit=$CONFIG['paging'];}
	    	$paginate=$offset+$limit;
	    }
	    //$query="SELECT {$paginate} {$params['-fields']} FROM {$params['-table']} {$wherestr}";
	    $orderby=1;
	    if(isset($params['-order'])){
    		$orderby = "{$params['-order']}";
    	}
    	if(isNum($paginate)){
    		$query=<<<ENDOFQUERY
				SELECT *  FROM (
					SELECT Top {$limit} * FROM (
				        SELECT TOP {$paginate} {$params['-fields']}
				        FROM {$params['-table']}
				        ORDER BY {$orderby}
				    ) sub
				   ORDER BY {$orderby} DESC
				) subOrdered
				ORDER BY {$orderby}
ENDOFQUERY;
			//echo $query;exit;
		}
		else{
			$query="SELECT {$params['-fields']} FROM {$params['-table']} {$wherestr}";
		}
	}
	if(isset($params['-debug'])){return $query;}
	if(isset($params['-queryonly'])){return $query;}
	return msexcelQueryResults($query,$params);
}

//---------- begin function msexcelIsDBTable ----------
/**
* @describe returns true if table already exists
* @param table string
* @return boolean
* @usage if(msexcelIsDBTable('_users')){...}
*/
function msexcelIsDBTable($table='',$force=0){
	global $databaseCache;
	$table=strtolower($table);
	if($force==0 && isset($databaseCache['msexcelIsDBTable'][$table])){
		return $databaseCache['msexcelIsDBTable'][$table];
	}
	$tables=msexcelGetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function msexcelGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_msexcel,dbuser_msexcel, and dbpass_msexcel
* @return array returns array of tables
* @usage $tables=msexcelGetDBTables();
*/
function msexcelGetDBTables($params=array()){
	$params=msexcelParseConnectParams();
	$ext=strtolower(getFileExtension($params['-dbname']));
	//for xlsx, we can get the sheet names from the zip
	if($ext=='xlsx'){
		$tables=msexcelGetSheetNamesFromXlsx($params['-dbname']);
		return $tables;
	}
	// XLS format requires ODBC table listing - not yet fully supported
	debugValue(array(
		'function'=>'msexcelGetDBTables',
		'message'=>'XLS format table listing not fully supported, use XLSX format',
		'ext'=>$ext
	));
	// Continue with ODBC method for xls files
	$local_conn=null;
	$tables=array();
	try{
		$local_conn = msexcelDBConnect();
		if(!$local_conn){
			debugValue(array(
				'function'=>'msexcelGetDBTables',
				'message'=>'Failed to connect to database',
				'params'=>$params
			));
			return array();
		}
		$result = odbc_tables($local_conn);
		if(!$result){
			msexcelCloseDBConnection($local_conn);
			debugValue(array(
				'function'=>'msexcelGetDBTables',
				'message'=>'Failed to retrieve table list',
				'params'=>$params
			));
			return array();
		}
		$tblRow = 1;
		while (odbc_fetch_row($result)){
			if(odbc_result($result,"TABLE_TYPE")=="TABLE"){
		    	$tables[]= odbc_result($result,"TABLE_NAME");
		  	}
		}
		odbc_free_result($result);
		sort($tables);
		msexcelCloseDBConnection($local_conn);
		return $tables;
	}
	catch (Exception $e) {
		$error=array(
			"function"=>"msexcelGetDBTables",
			"message"=>"Exception occurred",
			"exception"=>$e->getMessage(),
			"params"=>$params
		);
	    debugValue($error);
	    msexcelCloseDBConnection($local_conn);
	    return array();
	}
}
function msexcelGetSheetNamesFromXlsx($file){
	// Validate file exists and is readable
	if(!file_exists($file)){
		debugValue(array(
			'function'=>'msexcelGetSheetNamesFromXlsx',
			'message'=>'File does not exist',
			'file'=>$file
		));
		return array();
	}
	if(!is_readable($file)){
		debugValue(array(
			'function'=>'msexcelGetSheetNamesFromXlsx',
			'message'=>'File is not readable',
			'file'=>$file
		));
		return array();
	}

	// Validate file extension
	$ext=strtolower(getFileExtension($file));
	if($ext!=='xlsx'){
		debugValue(array(
			'function'=>'msexcelGetSheetNamesFromXlsx',
			'message'=>'Invalid file extension. Must be xlsx',
			'file'=>$file,
			'extension'=>$ext
		));
		return array();
	}

	// Validate file is actually a zip file (xlsx files are zip archives)
	// Check for ZIP file signature (PK\x03\x04 or PK\x05\x06)
	$fh = @fopen($file, 'rb');
	if($fh){
		$header = fread($fh, 4);
		fclose($fh);
		if(substr($header, 0, 2) !== 'PK'){
			debugValue(array(
				'function'=>'msexcelGetSheetNamesFromXlsx',
				'message'=>'File is not a valid zip/xlsx file (missing ZIP signature). File may be HTML or another format with wrong extension.',
				'file'=>$file,
				'header'=>bin2hex($header)
			));
			return array();
		}
	}

	$worksheetNames = array();

	// Use ZipArchive instead of deprecated zip_* functions
	if(class_exists('ZipArchive')){
		$zip = new ZipArchive();
		$result = $zip->open($file);
		if($result !== true){
			debugValue(array(
				'function'=>'msexcelGetSheetNamesFromXlsx',
				'message'=>'Failed to open zip file with ZipArchive',
				'file'=>$file,
				'error_code'=>$result
			));
			return array();
		}

		$xml = $zip->getFromName('xl/workbook.xml');
		$zip->close();

		if($xml === false){
			debugValue(array(
				'function'=>'msexcelGetSheetNamesFromXlsx',
				'message'=>'Failed to read workbook.xml from xlsx file',
				'file'=>$file
			));
			return array();
		}

		$workbook = @simplexml_load_string($xml);
		if($workbook === false){
			debugValue(array(
				'function'=>'msexcelGetSheetNamesFromXlsx',
				'message'=>'Failed to parse workbook.xml',
				'file'=>$file
			));
			return array();
		}

		foreach($workbook->sheets as $sheets){
			foreach($sheets as $sheet){
				$attributes = (array)$sheet->attributes();
				if(isset($attributes['@attributes']['name'])){
					$worksheetNames[] = '['.$attributes['@attributes']['name'].'$]';
				}
			}
		}
	}
	else{
		// Fallback to deprecated zip_* functions if ZipArchive not available
		$zip = @zip_open($file);
		if(is_int($zip)){
			debugValue(array(
				'function'=>'msexcelGetSheetNamesFromXlsx',
				'message'=>'Failed to open zip file (ZipArchive not available, using deprecated zip_open)',
				'file'=>$file,
				'error_code'=>$zip
			));
			return array();
		}

		while($entry = zip_read($zip)){
			$entry_name = zip_entry_name($entry);
			if($entry_name == 'xl/workbook.xml'){
				if(zip_entry_open($zip, $entry, "r")){
					$buf = zip_entry_read($entry, zip_entry_filesize($entry));
					$workbook = @simplexml_load_string($buf);
					if($workbook !== false){
						foreach($workbook->sheets as $sheets){
							foreach($sheets as $sheet){
								$attributes = (array)$sheet->attributes();
								if(isset($attributes['@attributes']['name'])){
									$worksheetNames[] = '['.$attributes['@attributes']['name'].'$]';
								}
							}
						}
					}
					zip_entry_close($entry);
				}
				break;
			}
		}
		zip_close($zip);
	}

	return $worksheetNames;
}
//---------- begin function msexcelGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param table string - specified table
* @return array returns array of primary key fields
* @usage $fields=msexcelGetDBTablePrimaryKeys($table);
*/
function msexcelGetDBTablePrimaryKeys($table){
	return array();
}
function msexcelGetDBSchema(){
	return '';
}

function msexcelGetConfigValue($field){
	//dbschema, dbname
	global $CONFIG;
	switch(strtolower($CONFIG['dbtype'])){
		case 'msexcel':
			if(isset($CONFIG[$field])){return $CONFIG[$field];}
			elseif(isset($CONFIG["msexcel_{$field}"])){return $CONFIG["msexcel_{$field}"];}
		break;
		default:
			if(isset($CONFIG["msexcel_{$field}"])){return $CONFIG["msexcel_{$field}"];}
		break;
	}
	return null;
}
//---------- begin function msexcelListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function msexcelListRecords($params=array()){
	$params['-database']='msexcel';
	return databaseListRecords($params);
}
//---------- begin function msexcelParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_msexcel,dbuser_msexcel, and dbpass_msexcel
* @return $params array
* @usage $params=msexcelParseConnectParams($params);
*/
function msexcelParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^msexcel/i',$k)){unset($CONFIG[$k]);}
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
	if(isMsexcel()){
		if(isset($CONFIG['dbhost'])){
			$params['-dbhost']=$CONFIG['dbhost'];
		}
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
		if(isset($CONFIG['dbhost_msexcel'])){
			$params['-dbhost']=$CONFIG['dbhost_msexcel'];
			//$params['-dbhost_source']="CONFIG dbhost_msexcel";
		}
		elseif(isset($CONFIG['msexcel_dbhost'])){
			$params['-dbhost']=$CONFIG['msexcel_dbhost'];
			//$params['-dbhost_source']="CONFIG msexcel_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		//$params['-dbhost_source']="passed in";
	}
	if(isset($params['-dbhost'])){
		$CONFIG['msexcel_dbhost']=$params['-dbhost'];
	}

	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_msexcel'])){
			$params['-dbuser']=$CONFIG['dbuser_msexcel'];
			//$params['-dbuser_source']="CONFIG dbuser_msexcel";
		}
		elseif(isset($CONFIG['msexcel_dbuser'])){
			$params['-dbuser']=$CONFIG['msexcel_dbuser'];
			//$params['-dbuser_source']="CONFIG msexcel_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	if(isset($params['-dbuser'])){
		$CONFIG['msexcel_dbuser']=$params['-dbuser'];
	}
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_msexcel'])){
			$params['-dbpass']=$CONFIG['dbpass_msexcel'];
			//$params['-dbpass_source']="CONFIG dbpass_msexcel";
		}
		elseif(isset($CONFIG['msexcel_dbpass'])){
			$params['-dbpass']=$CONFIG['msexcel_dbpass'];
			//$params['-dbpass_source']="CONFIG msexcel_dbpass";
		}
	}
	else{
		//$params['-dbpass_source']="passed in";
	}
	if(isset($params['-dbpass'])){
		$CONFIG['msexcel_dbpass']=$params['-dbpass'];
	}
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_msexcel'])){
			$params['-dbname']=$CONFIG['dbname_msexcel'];
			//$params['-dbname_source']="CONFIG dbname_msexcel";
		}
		elseif(isset($CONFIG['msexcel_dbname'])){
			$params['-dbname']=$CONFIG['msexcel_dbname'];
			//$params['-dbname_source']="CONFIG msexcel_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['msexcel_dbname'];
			//$params['-dbname_source']="set to username";
		}
	}
	else{
		//$params['-dbname_source']="passed in";
	}
	if(isset($params['-dbname'])){
		$CONFIG['msexcel_dbname']=$params['-dbname'];
	}
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_msexcel'])){
			$params['-dbport']=$CONFIG['dbport_msexcel'];
			//$params['-dbport_source']="CONFIG dbport_msexcel";
		}
		elseif(isset($CONFIG['msexcel_dbport'])){
			$params['-dbport']=$CONFIG['msexcel_dbport'];
			//$params['-dbport_source']="CONFIG msexcel_dbport";
		}
		else{
			$params['-dbport']=5432;
			//$params['-dbport_source']="default port";
		}
	}
	else{
		//$params['-dbport_source']="passed in";
	}
	if(isset($params['-dbport'])){
		$CONFIG['msexcel_dbport']=$params['-dbport'];
	}
	//dbschema
	if(!isset($params['-dbschema'])){
		if(isset($CONFIG['dbschema_msexcel'])){
			$params['-dbschema']=$CONFIG['dbschema_msexcel'];
			//$params['-dbuser_source']="CONFIG dbuser_msexcel";
		}
		elseif(isset($CONFIG['msexcel_dbschema'])){
			$params['-dbschema']=$CONFIG['msexcel_dbschema'];
			//$params['-dbuser_source']="CONFIG msexcel_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	if(isset($params['-dbschema'])){
		$CONFIG['msexcel_dbschema']=$params['-dbschema'];
	}
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['msexcel_connect'])){
			$params['-connect']=$CONFIG['msexcel_connect'];
		}
		elseif(isset($CONFIG['connect_msexcel'])){
			$params['-connect']=$CONFIG['connect_msexcel'];
		}
		else{
			//ODBC;DSN=REPL01;HOST=repl01.dot.infotraxsys.com;UID=dot_dels;DATABASE=liveSQL;SERVICE=6597;CHARSET NAME=;MAXROWS=;OPTIONS=;;PRSRVCUR=OFF;;FILEDSN=;SAVEFILE=;FETCH_SIZE=;QUERY_TIMEOUT=;SCROLLCUR=OF
			$dir=getFilePath($CONFIG['msexcel_dbname']);
			$params['-connect']="odbc:Driver={Microsoft Excel Driver (*.xls, *.xlsx, *.xlsm, *.xlsb)};DriverId=790;Dbq={$CONFIG['msexcel_dbname']};DefaultDir={$dir};";
		}
	}
	else{
		//$params['-connect_source']="passed in";
	}
	//echo printValue($params);exit;
	return $params;
}
//---------- begin function msexcelQueryResults ----------
/**
* @describe returns the msexcel record set
* @param query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_msexcel,dbuser_msexcel, and dbpass_msexcel
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return array - returns records
*/
function msexcelQueryResults($query='',$params=array()){
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'msexcelQueryResults'
	);

	// Validate query input
	$query=trim($query);
	if(empty($query)){
		$DATABASE['_lastquery']['error']='Empty query provided';
		$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
		debugValue($DATABASE['_lastquery']);
		return array();
	}

	$local_conn=null;
	try{
		$local_conn=msexcelDBConnect();
		if(!$local_conn){
			$DATABASE['_lastquery']['error']='Failed to connect to database';
			$DATABASE['_lastquery']['stop']=microtime(true);
			$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
			debugValue($DATABASE['_lastquery']);
			return array();
		}

		$result=odbc_exec($local_conn,$query);
		if(!$result){
			$DATABASE['_lastquery']['error']=odbc_errormsg($local_conn);
			$DATABASE['_lastquery']['stop']=microtime(true);
			$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
			debugValue($DATABASE['_lastquery']);
			msexcelCloseDBConnection($local_conn);
			return array();
		}

		$results=msexcelEnumQueryResults($result,$params,$query);
		msexcelCloseDBConnection($local_conn);

		$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
		return $results;
	}
	catch (Exception $e) {
		$DATABASE['_lastquery']['error']=$e->getMessage();
		$DATABASE['_lastquery']['stop']=microtime(true);
		$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
		debugValue($DATABASE['_lastquery']);
		msexcelCloseDBConnection($local_conn);
		return array();
	}
}
//---------- begin function msexcelEnumQueryResults ----------
/**
* @describe enumerates through the data from a msexcel query
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function msexcelEnumQueryResults($result,$params=array(),$query=''){
	global $msexcelStopProcess;
	$i=0;

	// Validate result resource
	if(!is_resource($result)){
		debugValue(array(
			'function'=>'msexcelEnumQueryResults',
			'message'=>'Invalid result resource provided'
		));
		return array();
	}

	if(isset($params['-filename'])){
		$starttime=microtime(true);
		$header=0;

		// Validate filename
		if(empty($params['-filename'])){
			odbc_free_result($result);
			debugValue(array(
				'function'=>'msexcelEnumQueryResults',
				'message'=>'Empty filename provided'
			));
			return 0;
		}

		// Validate directory exists
		$dir=dirname($params['-filename']);
		if(!is_dir($dir)){
			odbc_free_result($result);
			debugValue(array(
				'function'=>'msexcelEnumQueryResults',
				'message'=>'Directory does not exist',
				'directory'=>$dir
			));
			return 0;
		}

		if(isset($params['-append'])){
			//append
    		$fh = @fopen($params['-filename'],"ab");
    		$header=1; // Don't write header when appending
		}
		else{
			if(file_exists($params['-filename'])){
				if(!@unlink($params['-filename'])){
					odbc_free_result($result);
					debugValue(array(
						'function'=>'msexcelEnumQueryResults',
						'message'=>'Failed to delete existing file',
						'file'=>$params['-filename']
					));
					return 0;
				}
			}
    		$fh = @fopen($params['-filename'],"wb");
		}
    	if(!isset($fh) || !is_resource($fh)){
			odbc_free_result($result);
			debugValue(array(
				'function'=>'msexcelEnumQueryResults',
				'message'=>'Failed to open file for writing',
				'file'=>$params['-filename']
			));
			return 0;
		}
		if(isset($params['-logfile'])){
			$logdir=dirname($params['-logfile']);
			if(is_dir($logdir)){
				setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
			}
		}

	}
	else{$recs=array();}
	while(odbc_fetch_row($result)){
		//check for msexcelStopProcess request
		if(isset($msexcelStopProcess) && $msexcelStopProcess==1){
			break;
		}
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
		elseif(isset($params['-index']) && isset($rec[$params['-index']])){
			$recs[$rec[$params['-index']]]=$rec;
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
function msexcelNamedQueryList(){
	return array(
		
	);
}
//---------- begin function msexcelNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function msexcelNamedQuery($name){
	return '';
}
