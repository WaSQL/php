<?php

/*
	Snowflake database via their ODBC driver
		Download the snowfilke ODBC drivers here:
			https://sfc-repo.snowflakecomputing.com/odbc/index.html
		Documentation
			https://docs.snowflake.net/manuals/user-guide-connecting.html


*/

//---------- begin function snowflakeGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=snowflakeGetDBRecordById('comments',7);
*/
function snowflakeGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "snowflakeGetDBRecordById Error: No Table";}
	if($id == 0){return "snowflakeGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=snowflakeGetDBRecord($recopts);
	return $rec;
}
//---------- begin function snowflakeEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=snowflakeEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function snowflakeEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("snowflakeEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("snowflakeEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("snowflakeEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("snowflakeEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return snowflakeEditDBRecord($params);
}
//---------- begin function snowflakeDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=snowflakeDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function snowflakeDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("snowflakeDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("snowflakeDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return snowflakeDelDBRecord($params);
}
//---------- begin function snowflakeListRecords
/**
* @describe returns an html table of records from a snowflake database. refer to databaseListRecords
*/
function snowflakeListRecords($params=array()){
	$params['-database']='snowflake';
	return databaseListRecords($params);
}

//---------- begin function snowflakeAddDBRecordsFromCSV ----------
/**
* @describe imports data from csv into a Snowflake table
* @param $table string - table to import into
* @param $csv - csvfile to import (path visible by the Snowflake server)
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-localfile] - path to localfile if different than csvfile that DB can see.
* 	[-delim] - delimiter. Default is ,
* 	[-enclose] - enclosed by. Default is "
* 	[-eol] - end of line char.  Default is \n
* 	[-threads] - number of threads. Default is 10
* 	[-batch] - number of records to commit in batch. Default is 10,000
* 	[-skip] - number of top rows to skip if any
* 	[-columns] - comma separated list of column name list. Defaults to use first row as column name list
* 	[-date] - format of date columns in csv file. Y=year, MM=month, MON=name of month, DD=day
* 	[-time] - format of time columns in csv file. HH24=hour, MI=minute, SS=second
* 	[-timestamp] format of timestamp columns in csv file. Y=year, MM=month, MON=name of month, DD=day, HH24=hour, MI=minute, SS=second
* 	[-nofail] - continue instead of failing on errors. Defaults to failing on errors.
* 	[-checktype] - check data types on insert. Defaults to not check.
* @return $errors array
* @usage 
*	loadExtras('snowflake');
*	$ok=snowflakeAddDBRecordsFromCSV('stgschema.testtable','/mnt/dtxsnowflake/test.csv');
*/
function snowflakeAddDBRecordsFromCSV($table,$csvfile,$params=array()){
	//error log with same name as csvfile in same path so Snowflake can write to it.
	$error_log= preg_replace('/\.csv$/i', '.errors', $csvfile);
	if(!isset($params['-localfile'])){$params['-localfile']=$csvfile;}
	$local_error_log= preg_replace('/\.csv$/i', '.errors', $params['-localfile']);
	/*
	 * References
	 * 	https://help.sap.com/viewer/4fe29514fd584807ac9f2a04f6754767/2.0.00/en-US/20f712e175191014907393741fadcb97.html
	 * 	https://blogs.sap.com/2013/04/08/best-practices-for-sap-snowflake-data-loads/
	 * 	https://blogs.sap.com/2014/02/12/8-tips-on-pre-processing-flat-files-with-sap-snowflake/
	 *
	 * THREADS and BATCH provide high loading performance by enabling parallel loading and also by committing many records at once.
	 * In general, for column tables, a good setting to use is 10 parallel loading threads, with a commit frequency of 10,000 records or greater
	 *
	THREADS <number_of_threads>
	* 	Specifies the number of threads that can be used for concurrent import. The default value is 1 and maximum allowed is 256
	BATCH <number_of_records_of_each_commit>
	* 	Specifies the number of records to be inserted in each commit
	TABLE LOCK
	* 	Provides faster data loading for column store tables. Use this option carefully as it incurs table locks in exclusive mode as well as explicit hard merges and save points after data loading is finished.
	NO TYPE CHECK
	* 	Specifies that the records are inserted without checking the type of each field.
	SKIP FIRST <number_of_rows_to_skip>
	* 	Skips the specified number of rows in the import file.
	COLUMN LIST IN FIRST ROW [<with_schema_flexibility>]
	* 	Indicates that the column list is stored in the first row of the CSV import file.
	* 	WITH SCHEMA FLEXIBILITY creates missing columns in flexible tables during CSV imports, as specified in the header (first row) of the CSV file or column list.
	* 	By default, missing columns in flexible tables are not created automatically during data imports.
	COLUMN LIST ( <column_name_list> ) [<with_schema_flexibility>]
	* 	Specifies the column list for the data being imported.
	* 	WITH SCHEMA FLEXIBILITY creates missing columns in flexible tables during CSV imports, as specified in the header (first row) of the CSV file or column list.
	RECORD DELIMITED BY <string_for_record_delimiter>
	* 	Specifies the record delimiter used in the CSV file being imported.
	FIELD DELIMITED BY <string_for_field_delimiter>
	* 	Specifies the field delimiter of the CSV file.
	OPTIONALLY ENCLOSED BY <character_for_optional_enclosure>
	* 	Specifies the optional enclosure character used to delimit field data.
	DATE FORMAT <string_for_date_format>
	* 	Specifies the format that date strings are encoded with in the import data.
	* 	Y : year, MM : month, MON : name of month, DD : day
	TIME FORMAT <string_for_time_format>
	* 	Specifies the format that time strings are encoded with in the import data.
	* 	HH24 : hour, MI : minute, SS : second
	TIMESTAMP FORMAT <string_for_timestamp_format>
	* 	Specifies the format that timestamp strings are encoded with in the import data.  (YYYY-MM-DD HH24:MI:SS)
	ERROR LOG <file_path_of_error_log>
	* 	Specifies that a log file of errors generated is stored in this file. Ensure the file path you use is writable by the database.
	FAIL ON INVALID DATA
	* 	Specifies that the IMPORT FROM command fails unless all the entries import successfully.
	 * */
	if(!isset($params['-delim'])){$params['-delim']=',';}
	if(!isset($params['-enclose'])){$params['-enclose']='"';}
	if(!isset($params['-eol'])){$params['-eol']='\\n';}
	if(!isset($params['-threads'])){$params['-threads']=10;}
	if(!isset($params['-batch'])){$params['-batch']=10000;}
	$with='';
	if(isset($params['-skip'])){
		$with.= "SKIP FIRST ({$params['-skip']}) ".PHP_EOL;
	}
	if(isset($params['-columns'])){
		$with.= "COLUMN LIST '{$params['-columns']}' WITH SCHEMA FLEXIBILITY ".PHP_EOL;
	}
	else{
		$with.= "COLUMN LIST IN FIRST ROW WITH SCHEMA FLEXIBILITY ". PHP_EOL;
	}
	if(isset($params['-date'])){
		$with.= "DATE FORMAT ({$params['-date']}) ".PHP_EOL;
	}
	if(isset($params['-time'])){
		$with.= "TIME FORMAT '{$params['-time']}' ".PHP_EOL;
	}
	if(isset($params['-timestamp'])){
		$with.= "TIMESTAMP FORMAT '{$params['-timestamp']}' ".PHP_EOL;
	}
	if(!isset($params['-nofail'])){
		$with.= "FAIL ON INVALID DATA ".PHP_EOL;
	}
	if(!isset($params['-checktype'])){
		$with.= "NO TYPE CHECK ".PHP_EOL;
	}
	$query=<<<ENDOFQUERY
	IMPORT FROM CSV FILE '{$csvfile}'
	INTO {$table}
	WITH
		RECORD DELIMITED BY '{$params['-eol']}'
		FIELD DELIMITED BY '{$params['-delim']}'
		OPTIONALLY ENCLOSED BY '{$params['-enclose']}'
		{$with}
	THREADS {$params['-threads']}
	BATCH {$params['-batch']}
	ERROR LOG '{$error_log}'
ENDOFQUERY;
	setFileContents($error_log,'');
	//set to a single so we can turn off autocommit
	$params['-single']=1;
	$conn=snowflakeDBConnect($params);
	odbc_autocommit($conn, FALSE);

	odbc_exec($conn, $query);

	if (!odbc_error()){
		odbc_commit($conn);
	}
	else{
		odbc_rollback($conn);
	}
	odbc_close($conn);
	return file($local_error_log);

}
//---------- begin function snowflakeParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @exclude  - this function is for internal use and thus excluded from the manual
* @return $params array
* @usage 
*	loadExtras('snowflake');
*	$params=snowflakeParseConnectParams($params);
*/
function snowflakeParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^snowflake/i',$k)){unset($CONFIG[$k]);}
		}
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			if(strtolower($k)=='cursor'){
				switch(strtoupper($v)){
					case 'SQL_CUR_USE_ODBC':$params['-cursor']=SQL_CUR_USE_ODBC;break;
					case 'SQL_CUR_USE_IF_NEEDED':$params['-cursor']=SQL_CUR_USE_IF_NEEDED;break;
					case 'SQL_CUR_USE_DRIVER':$params['-cursor']=SQL_CUR_USE_DRIVER;break;
				}
			}
			else{
				$params["-{$k}"]=$v;
			}
			
		}
	}
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['snowflake_connect'])){
			$params['-dbname']=$CONFIG['snowflake_connect'];
			$params['-dbname_source']="CONFIG snowflake_connect";
		}
		elseif(isset($CONFIG['dbname_snowflake'])){
			$params['-dbname']=$CONFIG['dbname_snowflake'];
			$params['-dbname_source']="CONFIG dbname_snowflake";
		}
		elseif(isset($CONFIG['snowflake_dbname'])){
			$params['-dbname']=$CONFIG['snowflake_dbname'];
			$params['-dbname_source']="CONFIG snowflake_dbname";
		}
		else{return 'snowflakeParseConnectParams Error: No dbname set';}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_snowflake'])){
			$params['-dbuser']=$CONFIG['dbuser_snowflake'];
			$params['-dbuser_source']="CONFIG dbuser_snowflake";
		}
		elseif(isset($CONFIG['snowflake_dbuser'])){
			$params['-dbuser']=$CONFIG['snowflake_dbuser'];
			$params['-dbuser_source']="CONFIG snowflake_dbuser";
		}
		else{return 'snowflakeParseConnectParams Error: No dbuser set';}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_snowflake'])){
			$params['-dbpass']=$CONFIG['dbpass_snowflake'];
			$params['-dbpass_source']="CONFIG dbpass_snowflake";
		}
		elseif(isset($CONFIG['snowflake_dbpass'])){
			$params['-dbpass']=$CONFIG['snowflake_dbpass'];
			$params['-dbpass_source']="CONFIG snowflake_dbpass";
		}
		else{return 'snowflakeParseConnectParams Error: No dbpass set';}
	}
	else{
		$params['-dbpass_source']="passed in";
	}
	if(isset($CONFIG['snowflake_cursor'])){
		switch(strtoupper($CONFIG['snowflake_cursor'])){
			case 'SQL_CUR_USE_ODBC':$params['-cursor']=SQL_CUR_USE_ODBC;break;
			case 'SQL_CUR_USE_IF_NEEDED':$params['-cursor']=SQL_CUR_USE_IF_NEEDED;break;
			case 'SQL_CUR_USE_DRIVER':$params['-cursor']=SQL_CUR_USE_DRIVER;break;
		}
	}
	return $params;
}
//---------- begin function snowflakeDBConnect ----------
/**
* @describe connects to a Snowflake database via odbc and returns the odbc resource
* @param $param array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*   [-single] - if you pass in -single it will connect using odbc_connect instead of odbc_pconnect and return the connection
* @return $dbh_snowflake resource - returns the odbc connection resource
* @exclude  - this function is for internal use and thus excluded from the manual
* @usage 
*	loadExtras('snowflake');
*	$dbh_snowflake=snowflakeDBConnect($params);
*/
function snowflakeDBConnect($params=array()){
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=snowflakeParseConnectParams($params);
	if(isset($params['-connect'])){
		$connect_name=$params['-connect'];
	}
	elseif(isset($params['-dbname'])){
		$connect_name=$params['-dbname'];
	}
	else{
		echo "snowflakeDBConnect error: no dbname or connect param".printValue($params);
		exit;
	}
	if(isset($params['-single'])){
		if(isset($params['-cursor'])){
			$dbh_snowflake_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_snowflake_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'] );
		}
		if(!is_resource($dbh_snowflake_single)){
			$err=odbc_errormsg();
			$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
			echo "snowflakeDBConnect single connect error:{$err}".printValue($params);
			exit;
		}
		return $dbh_snowflake_single;
	}
	global $dbh_snowflake;
	if(is_resource($dbh_snowflake)){return $dbh_snowflake;}

	try{
		if(isset($params['-cursor'])){
			$dbh_snowflake = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_snowflake = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],SQL_CUR_USE_ODBC);
		}
		if(!is_resource($dbh_snowflake)){
			//wait a few seconds and try again
			sleep(2);
			if(isset($params['-cursor'])){
				$dbh_snowflake = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
			}
			else{
				$dbh_snowflake = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'] );
			}
			if(!is_resource($dbh_snowflake)){
				$err=odbc_errormsg();
				$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
				echo "snowflakeDBConnect error:{$err}".printValue($params);
				exit;
			}
		}
		return $dbh_snowflake;
	}
	catch (Exception $e) {
		echo "snowflakeDBConnect exception" . printValue($e);
		exit;

	}
}
//---------- begin function snowflakeIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage 
*	loadExtras('snowflake');
*	if(snowflakeIsDBTable('abc','abcschema')){...}
*/
function snowflakeIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "snowflakeIsDBTable error: No table";
		exit;
	}
	//split out table and schema
	$parts=preg_split('/\./',$table);
	switch(count($parts)){
		case 1:
			echo "snowflakeIsDBTable error: no schema defined in tablename";
			exit;
		break;
		case 2:
			$schema=$parts[0];
			$table=$parts[1];
		break;
		default:
			echo "snowflakeIsDBTable error: to many parts";
		break;
	}
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
		echo "snowflakeDBConnect error".printValue($params);
		exit;
	}
	try{
		$result=odbc_tables($dbh_snowflake);
		if(!$result){
        	$err=array(
        		'error'	=> odbc_errormsg($dbh_snowflake)
			);
			echo "snowflakeIsDBTable error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "snowflakeIsDBTable error: exception".printValue($err);
		exit;
	}
	while(odbc_fetch_row($result)){
		if(odbc_result($result,"TABLE_TYPE")!="TABLE"){continue;}
		if(strlen($schema) && odbc_result($result,"TABLE_SCHEM") != strtoupper($schema)){continue;}
		$schem=odbc_result($result,"TABLE_SCHEM");
		$name=odbc_result($result,"TABLE_NAME");
		if(strtolower($table) == strtolower($name)){
			odbc_free_result($result);
			return true;
		}
	}
	odbc_free_result($result);
    return false;
}
//---------- begin function snowflakeClearConnection ----------
/**
* @describe clears the snowflake database connection
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('snowflake');
*	$ok=snowflakeClearConnection();
*/
function snowflakeClearConnection(){
	global $dbh_snowflake;
	$dbh_snowflake='';
	return true;
}
//---------- begin function snowflakeExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('snowflake');
*	$ok=snowflakeExecuteSQL("truncate table abc");
*/
function snowflakeExecuteSQL($query,$params=array()){
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		//wait a couple of seconds and try again
		sleep(2);
		$dbh_snowflake=snowflakeDBConnect($params);
		if(!is_resource($dbh_snowflake)){
			$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
			debugValue("snowflakeDBConnect error".printValue($params));
			return false;
		}
		else{
			debugValue("snowflakeDBConnect recovered connection ");
		}
	}
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
			$err=odbc_errormsg($dbh_snowflake);
			debugValue($err);
			return false;
		}
		odbc_free_result($result);
		return true;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue($err);
		return false;
	}
	return true;
}
//---------- begin function snowflakeAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @exclude - old
* @usage 
*	loadExtras('snowflake');
*	$id=snowflakeAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function snowflakeAddDBRecordsOLD($table,$recs){
	global $USER;
	if(!strlen($table)){return 'snowflakeAddDBRecords error: No table.';}
	$fields=snowflakeGetDBFieldInfo($table,$params);
	$opts=array();
	foreach($recs as $i=>$rec){
		if(isset($fields['cdate'])){
			$recs[$i]['cdate']=strtoupper(date('d-M-Y  H:i:s'));
		}
		elseif(isset($fields['_cdate'])){
			$recs[$i]['_cdate']=strtoupper(date('d-M-Y  H:i:s'));
		}
		if(isset($fields['cuser'])){
			$recs[$i]['cuser']=$USER['username'];
		}
		elseif(isset($fields['_cuser'])){
			$recs[$i]['_cuser']=$USER['username'];
		}
		foreach($rec as $k=>$v){
			$k=strtolower($k);
			if(!strlen(trim($v))){continue;}
			if(!isset($fields[$k])){continue;}
			//fix array values
			if(is_array($v)){$v=implode(':',$v);}
			switch(strtolower($fields[$k]['type'])){
				case 'date':
					if($k=='cdate' || $k=='_cdate'){
						$v=date('Y-m-d',strtotime($v));
					}
				break;
				case 'nvarchar':
				case 'nchar':
					$v=snowflakeConvert2UTF8($v);
				break;
			}
			//support for nextval
			if(preg_match('/\.nextval$/',$v)){
				$opts['bindings'][]=$v;
				$opts['fields'][]=trim(strtoupper($k));
			}
			else{
				$opts['values'][]=$v;
				$opts['bindings'][]='?';
				$opts['fields'][]=trim(strtoupper($k));
			}
		}
		
	}
	
	$valstr='';
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		switch(strtolower($fields[$k]['type'])){
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$v=date('Y-m-d',strtotime($v));
				}
        	break;
			case 'nvarchar':
			case 'nchar':
				$v=snowflakeConvert2UTF8($v);
        	break;
		}
		//support for nextval
		if(preg_match('/\.nextval$/',$v)){
			$opts['bindings'][]=$v;
        	$opts['fields'][]=trim(strtoupper($k));
		}
		else{
			$opts['values'][]=$v;
			$opts['bindings'][]='?';
        	$opts['fields'][]=trim(strtoupper($k));
		}
	}
	$fieldstr=implode('","',$opts['fields']);
	$bindstr=implode(',',$opts['bindings']);
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			("{$fieldstr}")
		VALUES
			({$bindstr})
ENDOFQUERY;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeAddDBRecord Connect Error",$e));
    	return "snowflakeAddDBRecord Connect Error".printValue($e);
	}
	try{
		$snowflake_stmt    = odbc_prepare($dbh_snowflake, $query);
		if(!is_resource($snowflake_stmt)){
			$e=odbc_errormsg();
			$err=array("snowflakeAddDBRecord prepare Error",$e,$query);
			debugValue($err);
    		return printValue($err);
		}
		$success = odbc_execute($snowflake_stmt,$opts['values']);
		if(!$success){
			$e=odbc_errormsg();
			debugValue(array("snowflakeAddDBRecord Execute Error",$e));
    		return "snowflakeAddDBRecord Execute Error".printValue($e);
		}
		if(isset($params['-noidentity'])){return $success;}
		$result2=odbc_exec($dbh_snowflake,"SELECT top 1 ifnull(CURRENT_IDENTITY_VALUE(),0) as cval from {$params['-table']};");
		$row=odbc_fetch_array($result2,0);
		odbc_free_result($result2);
		$row=array_change_key_case($row);
		if(isset($row['cval'])){return $row['cval'];}
		return "snowflakeAddDBRecord Identity Error".printValue($row).printValue($opts);
		//echo "result2:".printValue($row);
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		$recs=array($err);
		debugValue(array("snowflakeAddDBRecord Try Error",$e));
		return "snowflakeAddDBRecord Try Error".printValue($err);
	}
	return 0;
}
function snowflakeAddDBRecords($params=array()){
	if(!isset($params['-table'])){
		debugValue(array(
    		'function'=>"snowflakeAddDBRecords",
    		'error'=>'No table specified'
    	));
    	return false;
    }
    if(!isset($params['-list']) || !is_array($params['-list'])){
		debugValue(array(
    		'function'=>"snowflakeAddDBRecords",
    		'error'=>'No records (list) specified'
    	));
    	return false;
    }
    //defaults
    if(!isset($params['-dateformat'])){
    	$params['-dateformat']='YYYY-MM-DD HH24:MI:SS';
    }
	$j=array("items"=>$params['-list']);
    $json=json_encode($j);
    $info=snowflakeGetDBFieldInfo($params['-table']);
    $fields=array();
    $jfields=array();
    $defines=array();
    foreach($recs[0] as $field=>$value){
    	if(!isset($info[$field])){continue;}
    	$fields[]=$field;
    	switch(strtolower($info[$field]['_dbtype'])){
    		case 'timestamp':
    		case 'date':
    			//date types have to be converted into a format that snowflake understands
    			$jfields[]="to_date(substr({$field},1,19),'{$params['-dateformat']}' ) as {$field}";
    		break;
    		default:
    			$jfields[]=$field;
    		break;
    	}
    	$defines[]="{$field} varchar(255) PATH '\$.{$field}'";
    }
    if(!count($fields)){return 'No matching Fields';}
    $fieldstr=implode(',',$fields);
    $jfieldstr=implode(',',$jfields);
    $definestr=implode(','.PHP_EOL,$defines);
    $query .= <<<ENDOFQ
    INSERT INTO {$params['-table']}
    	({$fieldstr})
    SELECT 
    	{$jfieldstr}
	FROM JSON_TABLE(
		?
		, '\$'
		COLUMNS (
			nested path '\$.items[*]'
			COLUMNS(
				{$definestr}
			)
		)
	)
ENDOFQ;
	$dbh_snowflake=snowflakeDBConnect($params);
	$stmt = odbc_prepare($dbh_snowflake, $query);
	if(!is_resource($snowflakeAddDBRecordCache[$params['-table']]['stmt'])){
		$e=odbc_errormsg();
		$err=array("snowflakeAddDBRecords prepare Error",$e,$query);
		debugValue($err);
		return false;
	}
	
	$success = odbc_execute($stmt,array($json));
	if(!$success){
		$e=odbc_errormsg();
		debugValue(array("snowflakeAddDBRecords Execute Error",$e,$opts));
		return false;
	}
	return true;
}

//---------- begin function snowflakeAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('snowflake');
*	$id=snowflakeAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function snowflakeAddDBRecord($params){
	global $snowflakeAddDBRecordCache;
	global $USER;
	if(!isset($params['-table'])){return 'snowflakeAddDBRecord error: No table.';}
	$fields=snowflakeGetDBFieldInfo($params['-table'],$params);
	$opts=array();
	if(isset($fields['cdate'])){
		$params['cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	elseif(isset($fields['_cdate'])){
		$params['_cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($fields['cuser'])){
		$params['cuser']=$USER['username'];
	}
	elseif(isset($fields['_cuser'])){
		$params['_cuser']=$USER['username'];
	}
	$valstr='';
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		switch(strtolower($fields[$k]['type'])){
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$v=date('Y-m-d',strtotime($v));
				}
        	break;
			case 'nvarchar':
			case 'nchar':
				$v=snowflakeConvert2UTF8($v);
        	break;
		}
		//support for nextval
		if(preg_match('/\.nextval$/',$v)){
			$opts['bindings'][]=$v;
        	$opts['fields'][]=trim(strtoupper($k));
		}
		else{
			$opts['values'][]=$v;
			$opts['bindings'][]='?';
        	$opts['fields'][]=trim(strtoupper($k));
		}
	}
	$fieldstr=implode('","',$opts['fields']);
	$bindstr=implode(',',$opts['bindings']);
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			("{$fieldstr}")
		VALUES
			({$bindstr})
ENDOFQUERY;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeAddDBRecord Connect Error",$e));
    	return "snowflakeAddDBRecord Connect Error".printValue($e);
	}
	try{
		if(!isset($snowflakeAddDBRecordCache[$params['-table']]['stmt'])){
			$snowflakeAddDBRecordCache[$params['-table']]['stmt']    = odbc_prepare($dbh_snowflake, $query);
			if(!is_resource($snowflakeAddDBRecordCache[$params['-table']]['stmt'])){
				$e=odbc_errormsg();
				$err=array("snowflakeAddDBRecord prepare Error",$e,$query);
				debugValue($err);
				return printValue($err);
			}
		}
		
		$success = odbc_execute($snowflakeAddDBRecordCache[$params['-table']]['stmt'],$opts['values']);
		if(!$success){
			$e=odbc_errormsg();
			debugValue(array("snowflakeAddDBRecord Execute Error",$e,$opts));
    		return "snowflakeAddDBRecord Execute Error".printValue($e);
		}
		if(isset($params['-noidentity'])){return $success;}
		$result2=odbc_exec($dbh_snowflake,"SELECT top 1 ifnull(CURRENT_IDENTITY_VALUE(),0) as cval from {$params['-table']};");
		$row=odbc_fetch_array($result2,0);
		odbc_free_result($result2);
		$row=array_change_key_case($row);
		if(isset($row['cval'])){return $row['cval'];}
		return "snowflakeAddDBRecord Identity Error".printValue($row).printValue($opts);
		//echo "result2:".printValue($row);
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		$recs=array($err);
		debugValue(array("snowflakeAddDBRecord Try Error",$e));
		return "snowflakeAddDBRecord Try Error".printValue($err);
	}
	return 0;
}
//---------- begin function snowflakeEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
*   -table - name of the table to add to
*   -where - filter criteria
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage 
*	loadExtras('snowflake');
*	$id=snowflakeEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function snowflakeEditDBRecord($params,$id=0,$opts=array()){
	mb_internal_encoding("UTF-8");
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'snowflakeEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'snowflakeEditDBRecord error: No where specified.';}
	global $USER;
	$fields=snowflakeGetDBFieldInfo($params['-table'],$params);
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
	$vals=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!isset($fields[$k])){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		switch(strtolower($fields[$k]['type'])){
			case 'nvarchar':
			case 'nchar':
				$v=snowflakeConvert2UTF8($v);
			break;
        	case 'date':
				if($k=='edate' || $k=='_edate'){
					$v=date('Y-m-d',strtotime($v));
				}
        	break;
		}
		$updates[]="{$k}=?";
		$vals[]=$v;
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	global $dbh_snowflake;
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeEditDBRecord2 Connect Error",$e));
    	return;
	}
	try{
		$snowflake_stmt    = odbc_prepare($dbh_snowflake, $query);
		if(!is_resource($snowflake_stmt)){
			$e=odbc_errormsg();
			debugValue(array("snowflakeEditDBRecord2 prepare Error",$e));
    		return 1;
		}
		$success = odbc_execute($snowflake_stmt,$vals);
		//echo $vals[5].$query.printValue($success).printValue($vals);
	}
	catch (Exception $e) {
		debugValue(array("snowflakeEditDBRecord2 try Error",$e));
    	return;
	}
	return 0;
}
//---------- begin function snowflakeReplaceDBRecord ----------
/**
* @describe updates or adds a record from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add/edit to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('snowflake');
*	$id=snowflakeReplaceDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function snowflakeReplaceDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){return 'snowflakeAddRecord error: No table specified.';}
	$fields=snowflakeGetDBFieldInfo($params['-table'],$params);
	$opts=array();
	if(isset($fields['cdate'])){
		$opts['fields'][]='CDATE';
		$opts['values'][]=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($fields['cuser'])){
		$opts['fields'][]='CUSER';
		$opts['values'][]=$USER['username'];
	}
	$valstr='';
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		//skip cuser and cdate - already set
		if($k=='cuser' || $k=='cdate'){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		//take care of single quotes in value
		$v=str_replace("'","''",$v);
		switch(strtolower($fields[$k]['type'])){
        	case 'integer':
        	case 'number':
        		$opts['values'][]=$v;
        	break;
			case 'nvarchar':
			case 'nchar':
				//add N before the value to handle utf8 inserts
				$opts['values'][]="N'{$v}'";
			break;
        	default:
        		$opts['values'][]="'{$v}'";
        	break;
		}
        $opts['fields'][]=trim(strtoupper($k));
	}
	$fieldstr=implode('","',$opts['fields']);
	$valstr=implode(',',$opts['values']);
    $query=<<<ENDOFQUERY
		REPLACE {$params['-table']}
		("{$fieldstr}")
		values({$valstr})
		WITH PRIMARY KEY
ENDOFQUERY;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeReplaceDBRecord Connect Error",$e));
    	return;
	}
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
        	$err=array(
        		'error'	=> odbc_errormsg($dbh_snowflake),
				'query'	=> $query
			);
			debugValue($err);
			return "snowflakeReplaceDBRecord Error".printValue($err).$query;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		odbc_free_result($result);
		debugValue(array("snowflakeReplaceDBRecord Connect Error",$e));
		return "snowflakeReplaceDBRecord Error".printValue($err);
	}
	odbc_free_result($result);
	return true;
}
//---------- begin function snowflakeManageDBSessions ----------
/**
* @describe cleans up idle sessions since Snowflake does not clean up for you.
* @param $user string - defaults to current username.  Set to "all" to clean up all users (requires session admin privileges)
* @param $idle integer - number of milliseconds session has to be idle for
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage 
*	loadExtras('snowflake');
*	$cnt=snowflakeManageDBSessions('all');
*/
function snowflakeManageDBSessions($username='',$idle=1800000,$params=array()){
	global $USER;
	if(!strlen($username)){$username=$USER['username'];}
	if(strtolower($username)=='all'){$username='user_name';}
	else{$username="'{$username}'";}
	//ALTER SYSTEM DISCONNECT SESSION '<connection_id>'
	$query=<<<ENDOFQUERY
SELECT connection_id
FROM M_CONNECTIONS
WHERE
	user_name={$username}
	and connection_status = 'IDLE'
	AND connection_type = 'Remote'
	AND idle_time > {$idle}
ORDER BY idle_time DESC
ENDOFQUERY;
	$recs=snowflakeQueryResults($query,$params);
	foreach($recs as $rec){
    	$query="ALTER SYSTEM DISCONNECT SESSION '{$rec['connection_id']}'";
    	$ok=snowflakeQueryResults($query,$params);
	}
	return count($recs);
}
//---------- begin function snowflakeGetDBRecords
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
*	loadExtras('snowflake');
*	$recs=snowflakeGetDBRecords(array('-table'=>'notes','name'=>'bob'));
*	$recs=snowflakeGetDBRecords("select * from notes where name='bob'");
*/
function snowflakeGetDBRecords($params){
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
			$ok=snowflakeExecuteSQL($params);
			return $ok;
		}
	}
	else{
		//determine fields to return
		if(!empty($params['-fields'])){
			if(!is_array($params['-fields'])){
				$params['-fields']=str_replace(' ','',$params['-fields']);
				$params['-fields']=preg_split('/\,/',$params['-fields']);
			}
			$params['-fields']=implode(',',$params['-fields']);
		}
		if(empty($params['-fields'])){$params['-fields']='*';}
		$fields=snowflakeGetDBFieldInfo($params['-table'],$params);
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
	return snowflakeQueryResults($query,$params);
}
//---------- begin function snowflakeGetDBSystemTables ----------
/**
* @describe returns an array of system tables
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage 
*	loadExtras('snowflake');
*	$systemtables=snowflakeGetDBSystemTables();
*/
function snowflakeGetDBSystemTables($params=array()){
	$params['-table_schema']='S';
	return snowflakeGetDBTables($params);
}
//---------- begin function snowflakeGetDBSchemas ----------
/**
* @describe returns an array of system tables
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage
*	loadExtras('snowflake'); 
*	$schemas=snowflakeGetDBSchemas();
*/
function snowflakeGetDBSchemas($params=array()){
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeGetDBSchemas Connect Error",$e));
    	return;
	}
	try{
		$result=odbc_tables($dbh_snowflake);
		if(!$result){
        	$err=array(
        		'error'	=> odbc_errormsg($dbh_snowflake)
			);
			echo "snowflakeIsDBTable error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "snowflakeIsDBTable error: exception".printValue($err);
		exit;
	}
	$recs=array();
	while(odbc_fetch_row($result)){
		if(odbc_result($result,"TABLE_TYPE")!="TABLE"){continue;}
		$schem=odbc_result($result,"TABLE_SCHEM");
		if(in_array($schem,$recs)){continue;}
		$recs[]=$schem;
	}
	odbc_free_result($result);
    return $recs;
}
//---------- begin function snowflakeGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage 
*	loadExtras('snowflake');
*	$tables=snowflakeGetDBTables();
*/
function snowflakeGetDBTables($params=array()){
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeGetDBSchemas Connect Error",$e));
    	return;
	}
	try{
		$result=odbc_tables($dbh_snowflake);
		if(!$result){
        	$err=array(
        		'error'	=> odbc_errormsg($dbh_snowflake)
			);
			echo "snowflakeIsDBTable error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "snowflakeIsDBTable error: exception".printValue($err);
		exit;
	}
	$tables=array();
	while(odbc_fetch_row($result)){
		if(odbc_result($result,"TABLE_TYPE")!="TABLE"){continue;}
		if(isset($params['-schema']) && strlen($params['-schema']) && strtoupper(odbc_result($result,"TABLE_SCHEM")) != strtoupper($params['-schema'])){continue;}
		$schem=odbc_result($result,"TABLE_SCHEM");
		$name=odbc_result($result,"TABLE_NAME");
		$tables[]="{$schem}.{$name}";
	}
	odbc_free_result($result);
    return $tables;
}
//---------- begin function snowflakeGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage
*	loadExtras('snowflake'); 
*	$fieldinfo=snowflakeGetDBFieldInfo('abcschema.abc');
*/
function snowflakeGetDBFieldInfo($table,$params=array()){
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeGetDBSchemas Connect Error",$e));
    	return;
	}
	$query="select * from {$table} where 1=0";
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
        	$err=array(
        		'error'	=> odbc_errormsg($dbh_snowflake),
        		'query'	=> $query
			);
			echo "snowflakeGetDBFieldInfo error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "snowflakeGetDBFieldInfo error: exception".printValue($err);
		exit;
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
		$recs[$field]['_dblength']=$recs[$field]['length'];
    }
    odbc_free_result($result);
	return $recs;
}
//---------- begin function snowflakeGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage
*	loadExtras('snowflake'); 
*	$cnt=snowflakeGetDBCount(array('-table'=>'states','-where'=>"code like 'M%'"));
*/
function snowflakeGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=snowflakeGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function snowflakeQueryHeader ----------
/**
* @describe returns a single row array with the column names
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array a single row array with the column names
* @usage
*	loadExtras('snowflake'); 
*	$recs=snowflakeQueryHeader($query);
*/
function snowflakeQueryHeader($query,$params=array()){
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeGetDBSchemas Connect Error",$e));
    	return;
	}
	if(!preg_match('/limit\ /is',$query)){
		$query .= " limit 0";
	}
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
        	$err=array(
        		'error'	=> odbc_errormsg($dbh_snowflake),
        		'query'	=> $query
			);
			echo "snowflakeQueryHeader error:".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "snowflakeQueryHeader error: exception".printValue($err);
		exit;
	}
	$fields=array();
	for($i=1;$i<=odbc_num_fields($result);$i++){
		$field=strtolower(odbc_field_name($result,$i));
        $fields[]=$field;
    }
    odbc_free_result($result);
    $rec=array();
    foreach($fields as $field){
		$rec[$field]='';
	}
    $recs=array($rec);
	return $recs;
}
//---------- begin function snowflakeQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage
*	loadExtras('snowflake'); 
*	$recs=snowflakeQueryResults('select top 50 * from abcschema.abc');
*/
function snowflakeQueryResults($query,$params=array()){
	global $dbh_snowflake;
	$starttime=microtime(true);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!$dbh_snowflake){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeQueryResults Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
			$errstr=odbc_errormsg($dbh_snowflake);
			if(!strlen($errstr)){return array();}
        	$err=array(
        		'error'	=> $errstr,
        		'query' => $query
			);
			if(stringContains($errstr,'session not connected')){
				$dbh_snowflake='';
				sleep(1);
				odbc_close_all();
				$dbh_snowflake=snowflakeDBConnect($params);
				$result=odbc_exec($dbh_snowflake,$query);
				if(!$result){
					$errstr=odbc_errormsg($dbh_snowflake);
					if(!strlen($errstr)){return array();}
					$err=array(
						'error'	=> $errstr,
						'query' => $query,
						'retry'	=> 1
					);
					return json_encode($err);
				}
			}
			else{
				return json_encode($err);
			}
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		return json_encode($err);
	}
	$rowcount=odbc_num_rows($result);
	if($rowcount==0 && isset($params['-forceheader'])){
		$fields=array();
		for($i=1;$i<=odbc_num_fields($result);$i++){
			$field=strtolower(odbc_field_name($result,$i));
			$fields[]=$field;
		}
		odbc_free_result($result);
		$rec=array();
		foreach($fields as $field){
			$rec[$field]='';
		}
		$recs=array($rec);
		return $recs;
	}
	if(isset($params['-count'])){
		odbc_free_result($result);
    	return $rowcount;
	}
	$header=0;
	unset($fh);
	//write to file or return a recordset?
	if(isset($params['-filename'])){
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
			return 'snowflakeQueryResults error: Failed to open '.$params['-filename'];
			exit;
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],"Rowcount:".$rowcount.PHP_EOL.$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	if(isset($params['-binmode'])){
		odbc_binmode($result, $params['-binmode']);
	}
	if(isset($params['-longreadlen'])){
		odbc_longreadlen($result,$params['-longreadlen']);
	}
	$i=0;
	while(odbc_fetch_row($result)){
		$rec=array();
	    for($z=1;$z<=odbc_num_fields($result);$z++){
			$field=strtolower(odbc_field_name($result,$z));
	        $rec[$field]=odbc_result($result,$z);
	    }
	    if(isset($params['-results_eval'])){
			$rec=call_user_func($params['-results_eval'],$rec);
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
	odbc_free_result($result);
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
//---------- begin function snowflakeGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param [$params] array - These can also be set in the CONFIG file with dbname_snowflake,dbuser_snowflake, and dbpass_snowflake
*	[-host] - snowflake server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of primary key fields
* @usage
*	loadExtras('snowflake'); 
*	$fields=snowflakeGetDBTablePrimaryKeys();
*/
function snowflakeGetDBTablePrimaryKeys($table,$params=array()){
	$parts=preg_split('/\./',strtoupper($table),2);
	$where='';
	if(count($parts)==2){
		$query = "SELECT column_name FROM constraints WHERE SCHEMA_NAME = '{$parts[0]}' and table_name='{$parts[1]}'";
	}
	else{
		$query = "SELECT column_name FROM constraints WHERE table_name='{$parts[1]}'";
	}
	$tmp = snowflakeQueryResults($query,$params);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
//---------- begin function snowflakeBuildPreparedInsertStatement ----------
/**
* @describe creates the query needed for a prepared Insert Statement
* @param $table string - tablename
* @param $fieldinfo array - field info obtained from snowflakeGetDBFieldInfo function
* @return $query string
* @usage
*	loadExtras('snowflake'); 
*	$query=snowflakeBuildPreparedInsertStatement($table,$fieldinfo,$primary_keys);
*/
function snowflakeBuildPreparedInsertStatement($table,$fieldinfo=array(),$params=array()){
	if(!is_array($fieldinfo)){
		$fieldinfo=snowflakeGetDBFieldInfo($table,$params);
	}
	$fields=array();
	$binds=array();
	foreach($fieldinfo as $field=>$info){
		//handle special fields
		switch(strtolower($field)){
			case 'limit':$field='"'.strtoupper($field).'"';break;
		}
		$fields[]=$field;
		$binds[]='?';
	}
	$fieldstr=implode(', ',$fields);
	$bindstr=implode(', ',$binds);
	$query="INSERT INTO {$table} ({$fieldstr}) VALUES ({$bindstr})";
	return $query;
}
//---------- begin function snowflakeBuildPreparedReplaceStatement ----------
/**
* @describe creates the query needed for a prepared Upsert Statement
* @param $table string - tablename
* @param $fieldinfo array - field info obtained from snowflakeGetDBFieldInfo function
* @param $primary_keys array - array of primary keys
* @return $query string
* @usage 
*	loadExtras('snowflake');
*	$query=snowflakeBuildPreparedReplaceStatement($table,$fieldinfo,$primary_keys);
*/
function snowflakeBuildPreparedReplaceStatement($table,$fieldinfo=array(),$keys=array(),$params=array()){
	if(!is_array($keys) || !count($keys)){
		echo "snowflakeBuildPreparedReplaceStatement error - missing keys.  Table: {$table}";
		exit;
	}
	if(!is_array($fieldinfo)){
		$fieldinfo=snowflakeGetDBFieldInfo($table,$params);
	}
	$sets=array();
	$wheres=array();
	foreach($fieldinfo as $field=>$info){
		$bind='?';
		//handle special fields
		switch(strtolower($field)){
			case 'limit':$field='"'.strtoupper($field).'"';break;
		}
		if(in_array($field,$keys)){
			$wheres[]="{$field}={$bind}";
		}
		$fields[]=$field;
		$binds[]='?';
	}
	$fieldstr=implode(', ',$fields);
	$bindstr=implode(', ',$binds);
	$wherestr=implode(' and ',$wheres);
	$query="REPLACE {$table} ({$fieldstr}) VALUES ({$bindstr}) WHERE {$wherestr}";
	return $query;
}
//---------- begin function snowflakeBuildPreparedUpdateStatement ----------
/**
* @describe creates the query needed for a prepared Update Statement
* @param $table string - tablename
* @param $fieldinfo array - field info obtained from snowflakeGetDBFieldInfo function
* @param $primary_keys array - array of primary keys
* @return $query string
* @usage 
*	loadExtras('snowflake');
*	$query=snowflakeBuildPreparedUpdateStatement($table,$fieldinfo,$primary_keys);
*/
function snowflakeBuildPreparedUpdateStatement($table,$fieldinfo=array(),$keys=array(),$params=array()){
	if(!is_array($keys) || !count($keys)){
		echo "snowflakeBuildPreparedUpdateStatement error - missing keys.  Table: {$table}";
		exit;
	}
	if(!is_array($fieldinfo)){
		$fieldinfo=snowflakeGetDBFieldInfo($table,$params);
	}
	$sets=array();
	$wheres=array();
	foreach($fieldinfo as $field=>$info){
		$bind='?';
		//handle special fields
		switch(strtolower($field)){
			case 'limit':$field='"'.strtoupper($field).'"';break;
		}
		if(in_array($field,$keys)){
			$wheres[]="{$field}={$bind}";
		}
		else{
			$sets[]="{$field}={$bind}";
		}
	}
	$setstr=implode(', ',$sets);
	$wherestr=implode(' and ',$wheres);
	$query="UPDATE {$table} SET {$setstr} WHERE {$wherestr}";
	return $query;
}
//---------- begin function snowflakeBuildPreparedDeleteStatement ----------
/**
* @describe creates the query needed for a prepared Delete Statement
* @param $table string - tablename
* @param $fieldinfo array - field info obtained from snowflakeGetDBFieldInfo function
* @param $primary_keys array - array of primary keys
* @return $query string
* @usage 
*	loadExtras('snowflake');
*	$query=snowflakeBuildPreparedDeleteStatement($table,$fieldinfo,$primary_keys);
*/
function snowflakeBuildPreparedDeleteStatement($table,$fieldinfo=array(),$keys=array(),$params=array()){
	if(!is_array($keys) || !count($keys)){
		echo "snowflakeBuildPreparedDeleteStatement error - missing keys.  Table: {$table}";
		exit;
	}
	if(!is_array($fieldinfo)){
		$fieldinfo=snowflakeGetDBFieldInfo($table,$params);
	}
	$wheres=array();
	foreach($fieldinfo as $field=>$info){
		$bind='?';
		//handle special fields
		switch(strtolower($field)){
			case 'limit':$field='"'.strtoupper($field).'"';break;
		}
		if(in_array($field,$keys)){
			$wheres[]="{$field}={$bind}";
		}
	}
	$wherestr=implode(' and ',$wheres);
	$query="DELETE FROM {$table} WHERE {$wherestr}";
	return $query;
}
function snowflakeConvert2UTF8($content) { 
    if(!mb_check_encoding($content, 'UTF-8') 
        OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {

        $content = mb_convert_encoding($content, 'UTF-8'); 

        if (mb_check_encoding($content, 'UTF-8')) { 
            // log('Converted to UTF-8'); 
        } else { 
            // log('Could not converted to UTF-8'); 
        } 
    } 
    return $content; 
}
//---------- begin function snowflakeNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function snowflakeNamedQuery($name){
	switch(strtolower($name)){
		case 'running_queries':
			return <<<ENDOFQUERY
SELECT
	c.host, 
	c.user_name, 
	c.connection_status, 
	c.transaction_id, 
	s.last_executed_time,
	round(s.allocated_memory_size/1024/1024/1024,2) as allocated_memory_gb,
	round(s.used_memory_size/1024/1024/1024,2) as used_mem_gb, s.statement_string
FROM
	m_connections c, m_prepared_statements s
WHERE
	s.connection_id = c.connection_id 
	and c.connection_status != 'IDLE'
ORDER BY
	s.allocated_memory_size desc
ENDOFQUERY;
		break;
		case 'sessions':
			return <<<ENDOFQUERY
SELECT 
	host, 
	port, 
	connection_id, 
	connection_status, 
	connection_type,
	transaction_id, 
	idle_time, 
	auto_commit,
	client_host,
	user_name,
	current_schema_name,
	fetched_record_count
FROM 
	m_connections 
ORDER BY connection_status
ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY
SELECT * from m_table_locks
ENDOFQUERY;
		break;
	}
}