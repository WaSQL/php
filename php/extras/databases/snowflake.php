<?php
global $dbh_snowflake;
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
//---------- begin function snowflakeAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
* @return count int
* @usage $ok=snowflakeAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=snowflakeAddDBRecords('comments',array('-recs'=>$recs);
*/
function snowflakeAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		return debugValue("snowflakeAddDBRecords Error: No Table");
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	$params['-table']=$table;
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		return debugValue("snowflakeAddDBRecords Error: either -csv or -recs is required");
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
			return debugValue("snowflakeAddDBRecords Error: no such file: {$params['-csv']}");
		}
		return processCSVLines($params['-csv'],'snowflakeAddDBRecordsProcess',$params);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			return debugValue("snowflakeAddDBRecords Error: no recs");
		}
		elseif(!count($params['-recs'])){
			return debugValue("snowflakeAddDBRecords Error: no recs");
		}
		return snowflakeAddDBRecordsProcess($params['-recs'],$params);
	}
}
function snowflakeAddDBRecordsProcess($recs,$params=array()){
	if(!isset($params['-table'])){
		return debugValue("snowflakeAddDBRecordsProcess Error: no table"); 
	}
	$table=$params['-table'];
	$fieldinfo=snowflakeGetDBFieldInfo($table,1);
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
				$v=snowflakeEscapeString($v);
				$rec[$k]="'{$v}'";
			}
		}
		$values[]='('.implode(',',array_values($rec)).')';
	}
	$query.=implode(','.PHP_EOL,$values);
	$ok=snowflakeExecuteSQL($query);
	if(isset($params['-debug'])){
		return printValue($ok).$query;
	}
	return count($values);
}
function snowflakeEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
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


//---------- begin function snowflakeParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array 
* @exclude  - this function is for internal use and thus excluded from the manual
* @return $params array
* @usage 
*	loadExtras('snowflake');
*	$params=snowflakeParseConnectParams($params);
*/
function snowflakeParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
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
* @describe connects to a snowflake database via odbc and returns the odbc resource
* @param $param array 
*   [-single] - if you pass in -single it will connect using odbc_connect instead of odbc_pconnect and return the connection
* @return $dbh_snowflake resource - returns the snowflake connection resource
* @exclude  - this function is for internal use and thus excluded from the manual
* @usage 
*	loadExtras('snowflake');
*	$dbh_snowflake=snowflakeDBConnect($params);
*/
function snowflakeDBConnect($params=array()){
	global $dbh_snowflake;
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
			$dbh_snowflake_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'],SQL_CUR_USE_ODBC);
		}
		if(!is_resource($dbh_snowflake_single)){
			$e=odbc_errormsg();
			$error=array("snowflakeDBConnect Error",$e);
	    	debugValue($error);
	    	return json_encode($error);
		}
		return $dbh_snowflake_single;
	}
	
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
				$e=odbc_errormsg();
				$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
				$error=array("snowflakeDBConnect Error",$e,$params);
			    debugValue($error);
			    return json_encode($error);
			}
		}
		return $dbh_snowflake;
	}
	catch (Exception $e) {
		$error=array("snowflakeDBConnect Exception",$e);
	    debugValue($error);
	    return json_encode($error);
	}
}
//---------- begin function snowflakeIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array
* @return boolean returns true if table exists
* @usage 
*	loadExtras('snowflake');
*	if(snowflakeIsDBTable('abc','abcschema')){...}
*/
function snowflakeIsDBTable($table,$params=array()){
	if(!strlen($table)){
		$error=array("snowflakeIsDBTable Error","No table");
	    debugValue($error);
	    return false;
	}
	//split out table and schema
	$parts=preg_split('/\./',$table);
	switch(count($parts)){
		case 1:
			$error=array("snowflakeIsDBTable Error","No schema defined in tablename");
	    	debugValue($error);
	    	return false;
		break;
		case 2:
			$schema=$parts[0];
			$table=$parts[1];
		break;
		default:
			$error=array("snowflakeIsDBTable Error","To many parts");
	    	debugValue($error);
	    	return false;
		break;
	}
	$tables=snowflakeGetDBTables($params);
	foreach($tables as $name){
		if(strtolower($table) == strtolower($name)){return true;}
	}
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
* @param $params array
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('snowflake');
*	$ok=snowflakeExecuteSQL("truncate table abc");
*/
function snowflakeExecuteSQL($query,$params=array()){
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeExecuteSQL Connect Error",$e));
    	return json_encode($e);
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
* @param $params array
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('snowflake');
*	$id=snowflakeAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function snowflakeAddDBRecord($params){
	global $snowflakeAddDBRecordCache;
	global $USER;
	global $dbh_snowflake;
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
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeAddRecord Connect Error",$e));
    	return json_encode($e);
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
* @param $params array
*   -table - name of the table to add to
*   -where - filter criteria
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
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeEditRecord Connect Error",$e));
    	return json_encode($e);
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
* @param $params array
*   -table - name of the table to add to
* 	other field=>value pairs to add/edit to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('snowflake');
*	$id=snowflakeReplaceDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function snowflakeReplaceDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){
		$error=array("snowflakeReplaceDBRecord Error",'No table');
		debugValue($error);
		return json_encode($error);
	}
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
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeReplaceDBRecord Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
			$error=array("snowflakeReplaceDBRecord Error",odbc_errormsg($dbh_snowflake),$query);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		if($result){odbc_free_result($result);}
		$error=array("snowflakeReplaceDBRecord Error",$e,$query);
		debugValue($error);
		return json_encode($error);
	}
	odbc_free_result($result);
	return true;
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
	if(isset($params['-queryonly'])){return $query;}
	return snowflakeQueryResults($query,$params);
}
//---------- begin function snowflakeGetDBSchemas ----------
/**
* @describe returns an array of system tables
* @param $params array
* @return boolean returns true on success
* @usage
*	$schemas=snowflakeGetDBSchemas();
*/
function snowflakeGetDBSchemas($params=array()){
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeGetDBSchemas Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_tables($dbh_snowflake);
		if(!$result){
			$e=odbc_errormsg($dbh_snowflake);
			$error=array("snowflakeGetDBSchemas Error",$e,$query);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		$error=array("snowflakeGetDBSchemas Error",$e,$query);
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
//---------- begin function snowflakeGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array
* @return boolean returns true on success
* @usage 
*	loadExtras('snowflake');
*	$tables=snowflakeGetDBTables();
*/
function snowflakeGetDBTables($params=array()){
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeGetDBTables Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_tables($dbh_snowflake);
		if(!is_resource($result)){
			$e=odbc_errormsg($dbh_snowflake);
			$error=array("snowflakeGetDBTables Error",$e);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		$error=array("snowflakeGetDBSchemas Error",$e,$query);
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
//---------- begin function snowflakeGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array
* @return boolean returns true on success
* @usage
*	loadExtras('snowflake'); 
*	$fieldinfo=snowflakeGetDBFieldInfo('abcschema.abc');
*/
function snowflakeGetDBFieldInfo($table,$params=array()){
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeGetDBFieldInfo Connect Error",$e));
    	return json_encode($e);
	}
	$query="select * from {$table} where 1=0";
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
			$e=odbc_errormsg($dbh_snowflake);
			$error=array("snowflakeGetDBSchemas Error",$e,$query);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		$error=array("snowflakeGetDBSchemas Error",$e,$query);
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
		//_dbtype_ex
		switch(strtolower($recs[$field]['_dbtype'])){
			case 'bigint':
			case 'integer':
			case 'timestamp':
			case 'date':
			case 'datetime':
			case 'time':
			case 'timestampntz':
				$recs[$field]['_dbtype_ex']=$recs[$field]['_dbtype'];
			break;
			case 'decimal':
			case 'number':
				$recs[$field]['_dbtype_ex']="{$recs[$field]['_dbtype']}({$recs[$field]['precision']},{$recs[$field]['scale']})";
			break;
			default:
				$recs[$field]['_dbtype_ex']="{$recs[$field]['_dbtype']}({$recs[$field]['precision']})";
			break;
		}
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
		$error=array("snowflakeGetDBCount Error",$e,$query);
		debugValue($error);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function snowflakeQueryHeader ----------
/**
* @describe returns a single row array with the column names
* @param $params array
* @return array a single row array with the column names
* @usage
*	loadExtras('snowflake'); 
*	$recs=snowflakeQueryHeader($query);
*/
function snowflakeQueryHeader($query,$params=array()){
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeQueryHeader Connect Error",$e));
    	return json_encode($e);
	}
	if(!preg_match('/limit\ /is',$query)){
		$query .= " limit 0";
	}
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
			$e=odbc_errormsg($dbh_snowflake);
			$error=array("snowflakeQueryHeader Error",$e,$query);
			debugValue($error);
			return json_encode($error);
		}
	}
	catch (Exception $e) {
		$error=array("snowflakeGetDBSchemas Error",$e,$query);
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
//---------- begin function snowflakeQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage
*	loadExtras('snowflake'); 
*	$recs=snowflakeQueryResults('select top 50 * from abcschema.abc');
*/
function snowflakeQueryResults($query,$params=array()){
	global $snowflakeStopProcess;
	$starttime=microtime(true);
	global $dbh_snowflake;
	$dbh_snowflake=snowflakeDBConnect($params);
	if(!is_resource($dbh_snowflake)){
		$dbh_snowflake='';
		usleep(100);
		$dbh_snowflake=snowflakeDBConnect($params);
	}
	if(!is_resource($dbh_snowflake)){
    	$e=odbc_errormsg();
    	debugValue(array("snowflakeQueryResults Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=odbc_exec($dbh_snowflake,$query);
		if(!$result){
			$e=odbc_errormsg($dbh_snowflake);
			$error=array("snowflakeQueryResults Error",$e,$query);
			debugValue($error);
			if(!strlen($e)){return json_encode($error);}
			if(stringContains($e,'session not connected') || stringContains($e,'Receive Error')){
				$dbh_snowflake='';
				usleep(200);
				odbc_close_all();
				$dbh_snowflake=snowflakeDBConnect($params);
				$result=odbc_exec($dbh_snowflake,$query);
				if(!$result){
					$e=odbc_errormsg($dbh_snowflake);
					$error=array("snowflakeQueryResults Error",$e,$query);
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
		$error=array("snowflakeQueryResults Error",$e,$query);
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
			$error=array("snowflakeQueryResults Error",'Failed to open file',$query,$params);
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
		//check for snowflakeStopProcess request
		if(isset($snowflakeStopProcess) && $snowflakeStopProcess==1){
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
	database_name
	,schema_name
	,query_type
	,user_name
	,role_name
	,warehouse_name
	,start_time
	,query_text
FROM table(information_schema.query_history())
WHERE execution_status='RUNNING'
ORDER BY start_time desc
ENDOFQUERY;
		break;
		case 'sessions':
			//https://docs.snowflake.com/en/sql-reference/functions/query_history.html
			return <<<ENDOFQUERY
SELECT 
	database_name
	,schema_name
	,query_type
	,session_id
	,user_name
	,role_name
	,warehouse_name
	,start_time
	,query_text
FROM table(information_schema.query_history_by_session())
ORDER BY start_time 
ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY
SHOW locks 
ENDOFQUERY;
		break;
		case 'tables':
			return <<<ENDOFQUERY
--SHOW tables
SHOW terse tables
ENDOFQUERY;
		break;
		case 'functions':
			//https://docs.snowflake.com/en/sql-reference/sql/show-user-functions.html
			return <<<ENDOFQUERY
--SHOW external functions
SHOW user functions
ENDOFQUERY;
		break;
		case 'procedures':
			//https://docs.snowflake.com/en/sql-reference/sql/show-procedures.html
			return <<<ENDOFQUERY
SHOW procedures
ENDOFQUERY;
		break;
	}
}