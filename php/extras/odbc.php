<?php
global $dbh_odbc;
/*
	ODBC Drivers for Snowflake
		https://sfc-repo.snowflakecomputing.com/odbc/index.html

	ODBC System Tables
		http://sapbw.optimieren.de/odbc/odbc/html/monitor_views.html

		Sequences:
		--select * from sequences where sequence_oid like '%1157363%'
		--select * from sequences where sequence_name like '%1157363%'
		--select table_name,column_name, column_id from table_columns where table_name ='SAP_FLASH_CARDS' and column_name='ID'
		SELECT BICOMMON."_SYS_SEQUENCE_1157363_#0_#".CURRVAL FROM DUMMY;

*/
//---------- begin function odbcAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
* @return count int
* @usage $ok=odbcAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=odbcAddDBRecords('comments',array('-recs'=>$recs);
*/
function odbcAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("odbcAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("odbcAddDBRecords Error: either -csv or -recs is required");
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("odbcAddDBRecords Error: no such file: {$params['-csv']}");
		}
		$ok=processCSVLines($params['-csv'],'odbcAddDBRecordsProcess',array(
			'table'=>$table,
			'-chunk'=>$params['-chunk']
		));
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("odbcAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("odbcAddDBRecords Error: no recs");
		}
		return odbcAddDBRecordsProcess($params['-recs'],array('table'=>$table));
	}
}
function odbcAddDBRecordsProcess($recs,$params=array()){
	if(!isset($params['table'])){
		return debugValue("odbcAddDBRecordsProcess Error: no table"); 
	}
	$table=$params['table'];
	$fieldinfo=odbcGetDBFieldInfo($table,1);

	$fields=array();
	foreach($recs as $i=>$rec){
		foreach($rec as $k=>$v){
			if(!isset($fieldinfo[$k])){continue;}
			if(!in_array($k,$fields)){$fields[]=$k;}
		}
	}
	$fieldstr=implode(',',$fields);
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
				$v=odbcEscapeString($v);
				$rec[$k]="'{$v}'";
			}
		}
		$values[]='('.implode(',',array_values($rec)).')';
	}
	$query.=implode(','.PHP_EOL,$values);
	$ok=odbcExecuteSQL($query);
	return count($values);
}
function odbcEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function odbcGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=odbcGetDBRecordById('comments',7);
*/
function odbcGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "odbcGetDBRecordById Error: No Table";}
	if($id == 0){return "odbcGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=odbcGetDBRecord($recopts);
	return $rec;
}
//---------- begin function odbcEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=odbcEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function odbcEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("odbcEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("odbcEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("odbcEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("odbcEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return odbcEditDBRecord($params);
}
//---------- begin function odbcDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=odbcDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function odbcDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("odbcDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("odbcDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return odbcDelDBRecord($params);
}
//---------- begin function odbcListRecords
/**
* @describe returns an html table of records from a odbc database. refer to databaseListRecords
*/
function odbcListRecords($params=array()){
	$params['-database']='odbc';
	return databaseListRecords($params);
}


//---------- begin function odbcParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @exclude  - this function is for internal use and thus excluded from the manual
* @return $params array
* @usage 
*	loadExtras('odbc');
*	$params=odbcParseConnectParams($params);
*/
function odbcParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^odbc/i',$k)){unset($CONFIG[$k]);}
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
	//check for user specific
	if(strlen($USER['username'])){
		foreach($params as $k=>$v){
			if(stringEndsWith($k,"_{$USER['username']}")){
				$nk=str_replace("_{$USER['username']}",'',$k);
				unset($params[$k]);
				$params[$nk]=$v;
			}
		}
	}
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['odbc_connect'])){
			$params['-dbname']=$CONFIG['odbc_connect'];
			$params['-dbname_source']="CONFIG odbc_connect";
		}
		elseif(isset($CONFIG['dbname_odbc'])){
			$params['-dbname']=$CONFIG['dbname_odbc'];
			$params['-dbname_source']="CONFIG dbname_odbc";
		}
		elseif(isset($CONFIG['odbc_dbname'])){
			$params['-dbname']=$CONFIG['odbc_dbname'];
			$params['-dbname_source']="CONFIG odbc_dbname";
		}
		else{return 'odbcParseConnectParams Error: No dbname set';}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_odbc'])){
			$params['-dbuser']=$CONFIG['dbuser_odbc'];
			$params['-dbuser_source']="CONFIG dbuser_odbc";
		}
		elseif(isset($CONFIG['odbc_dbuser'])){
			$params['-dbuser']=$CONFIG['odbc_dbuser'];
			$params['-dbuser_source']="CONFIG odbc_dbuser";
		}
		else{return 'odbcParseConnectParams Error: No dbuser set';}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_odbc'])){
			$params['-dbpass']=$CONFIG['dbpass_odbc'];
			$params['-dbpass_source']="CONFIG dbpass_odbc";
		}
		elseif(isset($CONFIG['odbc_dbpass'])){
			$params['-dbpass']=$CONFIG['odbc_dbpass'];
			$params['-dbpass_source']="CONFIG odbc_dbpass";
		}
		else{return 'odbcParseConnectParams Error: No dbpass set';}
	}
	else{
		$params['-dbpass_source']="passed in";
	}
	if(isset($CONFIG['odbc_cursor'])){
		switch(strtoupper($CONFIG['odbc_cursor'])){
			case 'SQL_CUR_USE_ODBC':$params['-cursor']=SQL_CUR_USE_ODBC;break;
			case 'SQL_CUR_USE_IF_NEEDED':$params['-cursor']=SQL_CUR_USE_IF_NEEDED;break;
			case 'SQL_CUR_USE_DRIVER':$params['-cursor']=SQL_CUR_USE_DRIVER;break;
		}
	}
	return $params;
}
//---------- begin function odbcDBConnect ----------
/**
* @describe connects to a ODBC database via odbc and returns the odbc resource
* @param $param array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*   [-single] - if you pass in -single it will connect using odbc_connect instead of odbc_pconnect and return the connection
* @return $dbh_odbc resource - returns the odbc connection resource
* @exclude  - this function is for internal use and thus excluded from the manual
* @usage 
*	loadExtras('odbc');
*	$dbh_odbc=odbcDBConnect($params);
*/
function odbcDBConnect($params=array()){
	global $dbh_odbc;
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=odbcParseConnectParams($params);
	if(isset($params['-connect'])){
		$connect_name=$params['-connect'];
	}
	elseif(isset($params['-dbname'])){
		$connect_name=$params['-dbname'];
	}
	else{
		echo "odbcDBConnect error: no dbname or connect param".printValue($params);
		exit;
	}
	if(isset($params['-single'])){
		if(isset($params['-cursor'])){
			$dbh_odbc_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_odbc_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'],SQL_CUR_USE_ODBC);
		}
		if(!is_resource($dbh_odbc_single)){
			$e=odbc_errormsg();
			$error=array("odbcDBConnect Error",$e);
	    	debugValue($error);
	    	return json_encode($error);
		}
		return $dbh_odbc_single;
	}
	
	if(is_resource($dbh_odbc)){return $dbh_odbc;}

	try{
		if(isset($params['-cursor'])){
			$dbh_odbc = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_odbc = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],SQL_CUR_USE_ODBC);
		}
		if(!is_resource($dbh_odbc)){
			//wait a few seconds and try again
			sleep(2);
			if(isset($params['-cursor'])){
				$dbh_odbc = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
			}
			else{
				$dbh_odbc = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'] );
			}
			if(!is_resource($dbh_odbc)){
				$e=odbc_errormsg();
				$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
				$error=array("odbcDBConnect Error",$e,$params);
			    debugValue($error);
			    return json_encode($error);
			}
		}
		return $dbh_odbc;
	}
	catch (Exception $e) {
		$error=array("odbcDBConnect Exception",$e);
	    debugValue($error);
	    return json_encode($error);
	}
}
//---------- begin function odbcIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage 
*	loadExtras('odbc');
*	if(odbcIsDBTable('abc','abcschema')){...}
*/
function odbcIsDBTable($table,$params=array()){
	if(!strlen($table)){
		$error=array("odbcIsDBTable Error","No table");
	    debugValue($error);
	    return false;
	}
	//split out table and schema
	$parts=preg_split('/\./',$table);
	switch(count($parts)){
		case 1:
			$error=array("odbcIsDBTable Error","No schema defined in tablename");
	    	debugValue($error);
	    	return false;
		break;
		case 2:
			$schema=$parts[0];
			$table=$parts[1];
		break;
		default:
			$error=array("odbcIsDBTable Error","To many parts");
	    	debugValue($error);
	    	return false;
		break;
	}
	$tables=odbcGetDBTables($params);
	foreach($tables as $name){
		if(strtolower($table) == strtolower($name)){return true;}
	}
    return false;
}
//---------- begin function odbcClearConnection ----------
/**
* @describe clears the odbc database connection
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('odbc');
*	$ok=odbcClearConnection();
*/
function odbcClearConnection(){
	global $dbh_odbc;
	$dbh_odbc='';
	return true;
}
//---------- begin function odbcExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('odbc');
*	$ok=odbcExecuteSQL("truncate table abc");
*/
function odbcExecuteSQL($query,$params=array()){
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcExecuteSQL Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_exec($dbh_odbc,$query);
		if(!$result){
			$err=odbc_errormsg($dbh_odbc);
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
//---------- begin function odbcAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('odbc');
*	$id=odbcAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function odbcAddDBRecord($params){
	global $odbcAddDBRecordCache;
	global $USER;
	global $dbh_odbc;
	if(!isset($params['-table'])){return 'odbcAddDBRecord error: No table.';}
	$fields=odbcGetDBFieldInfo($params['-table'],$params);
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
				$v=odbcConvert2UTF8($v);
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
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcAddRecord Connect Error",$e));
    	return json_encode($e);
	}
	try{
		if(!isset($odbcAddDBRecordCache[$params['-table']]['stmt'])){
			$odbcAddDBRecordCache[$params['-table']]['stmt']    = odbc_prepare($dbh_odbc, $query);
			if(!is_resource($odbcAddDBRecordCache[$params['-table']]['stmt'])){
				$e=odbc_errormsg();
				$err=array("odbcAddDBRecord prepare Error",$e,$query);
				debugValue($err);
				return printValue($err);
			}
		}
		
		$success = odbc_execute($odbcAddDBRecordCache[$params['-table']]['stmt'],$opts['values']);
		if(!$success){
			$e=odbc_errormsg();
			debugValue(array("odbcAddDBRecord Execute Error",$e,$opts));
    		return "odbcAddDBRecord Execute Error".printValue($e);
		}
		if(isset($params['-noidentity'])){return $success;}
		$result2=odbc_exec($dbh_odbc,"SELECT top 1 ifnull(CURRENT_IDENTITY_VALUE(),0) as cval from {$params['-table']};");
		$row=odbc_fetch_array($result2,0);
		odbc_free_result($result2);
		$row=array_change_key_case($row);
		if(isset($row['cval'])){return $row['cval'];}
		return "odbcAddDBRecord Identity Error".printValue($row).printValue($opts);
		//echo "result2:".printValue($row);
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		$recs=array($err);
		debugValue(array("odbcAddDBRecord Try Error",$e));
		return "odbcAddDBRecord Try Error".printValue($err);
	}
	return 0;
}
//---------- begin function odbcEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
*   -table - name of the table to add to
*   -where - filter criteria
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage 
*	loadExtras('odbc');
*	$id=odbcEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function odbcEditDBRecord($params,$id=0,$opts=array()){
	mb_internal_encoding("UTF-8");
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'odbcEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'odbcEditDBRecord error: No where specified.';}
	global $USER;
	$fields=odbcGetDBFieldInfo($params['-table'],$params);
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
				$v=odbcConvert2UTF8($v);
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
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcEditRecord Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$odbc_stmt    = odbc_prepare($dbh_odbc, $query);
		if(!is_resource($odbc_stmt)){
			$e=odbc_errormsg();
			debugValue(array("odbcEditDBRecord2 prepare Error",$e));
    		return 1;
		}
		$success = odbc_execute($odbc_stmt,$vals);
		//echo $vals[5].$query.printValue($success).printValue($vals);
	}
	catch (Exception $e) {
		debugValue(array("odbcEditDBRecord2 try Error",$e));
    	return;
	}
	return 0;
}
//---------- begin function odbcReplaceDBRecord ----------
/**
* @describe updates or adds a record from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add/edit to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('odbc');
*	$id=odbcReplaceDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function odbcReplaceDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){
		$error=array("odbcReplaceDBRecord Error",'No table');
		debugValue($error);
		return json_encode($error);
	}
	$fields=odbcGetDBFieldInfo($params['-table'],$params);
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
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcReplaceDBRecord Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_exec($dbh_odbc,$query);
		if(!$result){
			$error=array("odbcReplaceDBRecord Error",odbc_errormsg($dbh_odbc),$query);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		if($result){odbc_free_result($result);}
		$error=array("odbcReplaceDBRecord Error",$e,$query);
		debugValue($error);
		return json_encode($error);
	}
	odbc_free_result($result);
	return true;
}
//---------- begin function odbcGetDBRecords
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
*	loadExtras('odbc');
*	$recs=odbcGetDBRecords(array('-table'=>'notes','name'=>'bob'));
*	$recs=odbcGetDBRecords("select * from notes where name='bob'");
*/
function odbcGetDBRecords($params){
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
			$ok=odbcExecuteSQL($params);
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
			if(!is_array($params['-fields'])){
				$params['-fields']=str_replace(' ','',$params['-fields']);
				$params['-fields']=preg_split('/\,/',$params['-fields']);
			}
			$params['-fields']=implode(',',$params['-fields']);
		}
		if(empty($params['-fields'])){$params['-fields']='*';}
		$fields=odbcGetDBFieldInfo($params['-table'],$params);
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
	return odbcQueryResults($query,$params);
}
//---------- begin function odbcGetDBSchemas ----------
/**
* @describe returns an array of system tables
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage
*	loadExtras('odbc'); 
*	$schemas=odbcGetDBSchemas();
*/
function odbcGetDBSchemas($params=array()){
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcGetDBSchemas Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_tables($dbh_odbc);
		if(!$result){
			$e=odbc_errormsg($dbh_odbc);
			$error=array("odbcGetDBSchemas Error",$e,$query);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		$error=array("odbcGetDBSchemas Error",$e,$query);
		debugValue($error);
		return json_encode($error);
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
//---------- begin function odbcGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage 
*	loadExtras('odbc');
*	$tables=odbcGetDBTables();
*/
function odbcGetDBTables($params=array()){
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcGetDBTables Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_tables($dbh_odbc);
		if(!is_resource($result)){
			$e=odbc_errormsg($dbh_odbc);
			$error=array("odbcGetDBTables Error",$e);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		$error=array("odbcGetDBSchemas Error",$e,$query);
		debugValue($error);
		return json_encode($error);
	}
	$tables=array();
	while($row=odbc_fetch_array($result)){
		if(!isset($row['TABLE_TYPE']) || $row['TABLE_TYPE'] != 'TABLE'){continue;}
		if(isset($row['TABLE_OWNER'])){
			$schema=$row['TABLE_OWNER'];
		}
		elseif(isset($row['TABLE_SCHEM'])){
			$schema=$row['TABLE_SCHEM'];
		}
		elseif(isset($row['TABLE_SCHEMA'])){
			$schema=$row['TABLE_SCHEMA'];
		}
		$name=$row['TABLE_NAME'];
		$tables[]="{$schema}.{$name}";
	}
	odbc_free_result($result);
    return $tables;
}
//---------- begin function odbcGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage
*	loadExtras('odbc'); 
*	$fieldinfo=odbcGetDBFieldInfo('abcschema.abc');
*/
function odbcGetDBFieldInfo($table,$params=array()){
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcGetDBFieldInfo Connect Error",$e));
    	return json_encode($e);
	}
	$query="select * from {$table} where 1=0";
	try{
		$result=odbc_exec($dbh_odbc,$query);
		if(!$result){
			$e=odbc_errormsg($dbh_odbc);
			$error=array("odbcGetDBSchemas Error",$e,$query);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		$error=array("odbcGetDBSchemas Error",$e,$query);
		debugValue($error);
		return json_encode($error);
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
//---------- begin function odbcGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage
*	loadExtras('odbc'); 
*	$cnt=odbcGetDBCount(array('-table'=>'states','-where'=>"code like 'M%'"));
*/
function odbcGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=odbcGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		$error=array("odbcGetDBCount Error",$e,$query);
		debugValue($error);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function odbcQueryHeader ----------
/**
* @describe returns a single row array with the column names
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array a single row array with the column names
* @usage
*	loadExtras('odbc'); 
*	$recs=odbcQueryHeader($query);
*/
function odbcQueryHeader($query,$params=array()){
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcQueryHeader Connect Error",$e));
    	return json_encode($e);
	}
	if(!preg_match('/limit\ /is',$query)){
		$query .= " limit 0";
	}
	try{
		$result=odbc_exec($dbh_odbc,$query);
		if(!$result){
			$e=odbc_errormsg($dbh_odbc);
			$error=array("odbcQueryHeader Error",$e,$query);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		$error=array("odbcGetDBSchemas Error",$e,$query);
		debugValue($error);
		return json_encode($error);
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
//---------- begin function odbcQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_odbc,dbuser_odbc, and dbpass_odbc
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage
*	loadExtras('odbc'); 
*	$recs=odbcQueryResults('select top 50 * from abcschema.abc');
*/
function odbcQueryResults($query,$params=array()){
	$starttime=microtime(true);
	global $dbh_odbc;
	$dbh_odbc=odbcDBConnect($params);
	if(!is_resource($dbh_odbc)){
		$dbh_odbc='';
		usleep(100);
		$dbh_odbc=odbcDBConnect($params);
	}
	if(!is_resource($dbh_odbc)){
    	$e=odbc_errormsg();
    	debugValue(array("odbcQueryResults Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_exec($dbh_odbc,$query);
		if(!$result){
			$e=odbc_errormsg($dbh_odbc);
			$error=array("odbcQueryResults Error",$e,$query);
			debugValue($error);
			if(!strlen($e)){return json_encode($error);}
			if(stringContains($e,'session not connected') || stringContains($e,'Receive Error')){
				$dbh_odbc='';
				usleep(200);
				odbc_close_all();
				$dbh_odbc=odbcDBConnect($params);
				$result=odbc_exec($dbh_odbc,$query);
				if(!$result){
					$e=odbc_errormsg($dbh_odbc);
					$error=array("odbcQueryResults Error",$e,$query);
					debugValue($error);
					return json_encode($error);
				}
			}
			else{
				return json_encode($error);
			}
		}
	}
	catch (Exception $e) {
		$error=array("odbcQueryResults Error",$e,$query);
		debugValue($error);
		return json_encode($error);
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
			$error=array("odbcQueryResults Error",'Failed to open file',$query,$params);
			debugValue($error);
			return json_encode($error);
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
function odbcConvert2UTF8($content) { 
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
//---------- begin function odbcNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function odbcNamedQuery($name){
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
	}
}