<?php

/*
	PDO - PHP Data Objects
		https://www.lds.org/study/manual/come-follow-me-for-individuals-and-families-new-testament-2019/16?lang=eng

*/

//---------- begin function pdoGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=pdoGetDBRecordById('comments',7);
*/
function pdoGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "pdoGetDBRecordById Error: No Table";}
	if($id == 0){return "pdoGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=pdoGetDBRecord($recopts);
	return $rec;
}
//---------- begin function pdoEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=pdoEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function pdoEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("pdoEditDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("pdoEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("pdoEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("pdoEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return pdoEditDBRecord($params);
}
//---------- begin function pdoDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=pdoDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function pdoDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("pdoDelDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("pdoDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return pdoDelDBRecord($params);
}
//---------- begin function pdoListRecords
/**
* @describe returns an html table of records from a pdo database. refer to databaseListRecords
*/
function pdoListRecords($params=array()){
	$params['-database']='pdo';
	return databaseListRecords($params);
}


//---------- begin function pdoParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @exclude  - this function is for internal use and thus excluded from the manual
* @return $params array
* @usage 
*	loadExtras('pdo');
*	$params=pdoParseConnectParams($params);
*/
function pdoParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^pdo/i',$k)){unset($CONFIG[$k]);}
		}
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			if(strtolower($k)=='cursor'){
				switch(strtoupper($v)){
					case 'SQL_CUR_USE_pdo':$params['-cursor']=SQL_CUR_USE_pdo;break;
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
		if(isset($CONFIG['pdo_connect'])){
			$params['-dbname']=$CONFIG['pdo_connect'];
			$params['-dbname_source']="CONFIG pdo_connect";
		}
		elseif(isset($CONFIG['dbname_pdo'])){
			$params['-dbname']=$CONFIG['dbname_pdo'];
			$params['-dbname_source']="CONFIG dbname_pdo";
		}
		elseif(isset($CONFIG['pdo_dbname'])){
			$params['-dbname']=$CONFIG['pdo_dbname'];
			$params['-dbname_source']="CONFIG pdo_dbname";
		}
		else{return 'pdoParseConnectParams Error: No dbname set';}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_pdo'])){
			$params['-dbuser']=$CONFIG['dbuser_pdo'];
			$params['-dbuser_source']="CONFIG dbuser_pdo";
		}
		elseif(isset($CONFIG['pdo_dbuser'])){
			$params['-dbuser']=$CONFIG['pdo_dbuser'];
			$params['-dbuser_source']="CONFIG pdo_dbuser";
		}
		else{return 'pdoParseConnectParams Error: No dbuser set';}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_pdo'])){
			$params['-dbpass']=$CONFIG['dbpass_pdo'];
			$params['-dbpass_source']="CONFIG dbpass_pdo";
		}
		elseif(isset($CONFIG['pdo_dbpass'])){
			$params['-dbpass']=$CONFIG['pdo_dbpass'];
			$params['-dbpass_source']="CONFIG pdo_dbpass";
		}
		else{return 'pdoParseConnectParams Error: No dbpass set';}
	}
	else{
		$params['-dbpass_source']="passed in";
	}
	if(isset($CONFIG['pdo_cursor'])){
		switch(strtoupper($CONFIG['pdo_cursor'])){
			case 'SQL_CUR_USE_pdo':$params['-cursor']=SQL_CUR_USE_pdo;break;
			case 'SQL_CUR_USE_IF_NEEDED':$params['-cursor']=SQL_CUR_USE_IF_NEEDED;break;
			case 'SQL_CUR_USE_DRIVER':$params['-cursor']=SQL_CUR_USE_DRIVER;break;
		}
	}
	return $params;
}
//---------- begin function pdoDBConnect ----------
/**
* @describe connects to a pdo database via pdo and returns the pdo resource
* @param $param array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*   [-single] - if you pass in -single it will connect using pdo_connect instead of pdo_pconnect and return the connection
* @return $dbh_pdo resource - returns the pdo connection resource
* @exclude  - this function is for internal use and thus excluded from the manual
* @usage 
*	loadExtras('pdo');
*	$dbh_pdo=pdoDBConnect($params);
*/
function pdoDBConnect($params=array()){
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=pdoParseConnectParams($params);
	if(isset($params['-connect'])){
		$connect_name=$params['-connect'];
		echo "pdoDBConnect error: no connect param".printValue($params);
		exit;
	}
	//confirm valid driver
	$available_drivers=PDO::getAvailableDrivers();
	list($driver,$str)=preg_split('/\:/',$params['-connect'],2);
	if(!in_array($driver,$available_drivers)){
		echo "pdoDBConnect error: PDO driver {$driver} is not available on this server".printValue($params);
		exit; 
	}
	global $dbh_pdo;
	if(is_resource($dbh_pdo)){return $dbh_pdo;}

	try{
		$dbh_pdo = new PDO($params['-connect'],$params['-dbuser'],$params['-dbpass']);
		$dbh_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh_pdo;
	}
	catch (Exception $e) {
		echo "pdoDBConnect exception" .$e->getMessage();
		exit;

	}
}
//---------- begin function pdoIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage 
*	loadExtras('pdo');
*	if(pdoIsDBTable('abc','abcschema')){...}
*/
function pdoIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "pdoIsDBTable error: No table";
		exit;
	}
	//split out table and schema
	$parts=preg_split('/\./',$table);
	switch(count($parts)){
		case 1:
			echo "pdoIsDBTable error: no schema defined in tablename";
			exit;
		break;
		case 2:
			$schema=$parts[0];
			$table=$parts[1];
		break;
		default:
			echo "pdoIsDBTable error: to many parts";
		break;
	}
	$dbh_pdo=pdoDBConnect($params);
	if(!is_resource($dbh_pdo)){
		$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
		echo "pdoDBConnect error".printValue($params);
		exit;
	}
	try{
		$result=pdo_tables($dbh_pdo);
		if(!$result){
        	$err=array(
        		'error'	=> pdo_errormsg($dbh_pdo)
			);
			echo "pdoIsDBTable error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "pdoIsDBTable error: exception".printValue($err);
		exit;
	}
	while(pdo_fetch_row($result)){
		if(pdo_result($result,"TABLE_TYPE")!="TABLE"){continue;}
		if(strlen($schema) && pdo_result($result,"TABLE_SCHEM") != strtoupper($schema)){continue;}
		$schem=pdo_result($result,"TABLE_SCHEM");
		$name=pdo_result($result,"TABLE_NAME");
		if(strtolower($table) == strtolower($name)){
			pdo_free_result($result);
			return true;
		}
	}
	pdo_free_result($result);
    return false;
}
//---------- begin function pdoClearConnection ----------
/**
* @describe clears the pdo database connection
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('pdo');
*	$ok=pdoClearConnection();
*/
function pdoClearConnection(){
	global $dbh_pdo;
	$dbh_pdo='';
	return true;
}
//---------- begin function pdoExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('pdo');
*	$ok=pdoExecuteSQL("truncate table abc");
*/
function pdoExecuteSQL($query,$params=array()){
	$dbh_pdo=pdoDBConnect($params);
	if(!is_resource($dbh_pdo)){
		//wait a couple of seconds and try again
		sleep(2);
		$dbh_pdo=pdoDBConnect($params);
		if(!is_resource($dbh_pdo)){
			$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
			debugValue("pdoDBConnect error".printValue($params));
			return false;
		}
		else{
			debugValue("pdoDBConnect recovered connection ");
		}
	}
	try{
		$result=pdo_exec($dbh_pdo,$query);
		if(!$result){
			$err=pdo_errormsg($dbh_pdo);
			debugValue($err);
			return false;
		}
		pdo_free_result($result);
		return true;
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue($err);
		return false;
	}
	return true;
}
function pdoAddDBRecords($params=array()){
	if(!isset($params['-table'])){
		debugValue(array(
    		'function'=>"pdoAddDBRecords",
    		'error'=>'No table specified'
    	));
    	return false;
    }
    if(!isset($params['-list']) || !is_array($params['-list'])){
		debugValue(array(
    		'function'=>"pdoAddDBRecords",
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
    $info=pdoGetDBFieldInfo($params['-table']);
    $fields=array();
    $jfields=array();
    $defines=array();
    foreach($recs[0] as $field=>$value){
    	if(!isset($info[$field])){continue;}
    	$fields[]=$field;
    	switch(strtolower($info[$field]['_dbtype'])){
    		case 'timestamp':
    		case 'date':
    			//date types have to be converted into a format that pdo understands
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
	$dbh_pdo=pdoDBConnect($params);
	$stmt = pdo_prepare($dbh_pdo, $query);
	if(!is_resource($pdoAddDBRecordCache[$params['-table']]['stmt'])){
		$e=pdo_errormsg();
		$err=array("pdoAddDBRecords prepare Error",$e,$query);
		debugValue($err);
		return false;
	}
	
	$success = pdo_execute($stmt,array($json));
	if(!$success){
		$e=pdo_errormsg();
		debugValue(array("pdoAddDBRecords Execute Error",$e,$opts));
		return false;
	}
	return true;
}

//---------- begin function pdoAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
*   -table - name of the table to add to
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('pdo');
*	$id=pdoAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function pdoAddDBRecord($params){
	global $pdoAddDBRecordCache;
	global $USER;
	if(!isset($params['-table'])){return 'pdoAddDBRecord error: No table.';}
	$fields=pdoGetDBFieldInfo($params['-table'],$params);
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
				$v=pdoConvert2UTF8($v);
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
	$dbh_pdo=pdoDBConnect($params);
	if(!$dbh_pdo){
    	$e=pdo_errormsg();
    	debugValue(array("pdoAddDBRecord Connect Error",$e));
    	return "pdoAddDBRecord Connect Error".printValue($e);
	}
	try{
		if(!isset($pdoAddDBRecordCache[$params['-table']]['stmt'])){
			$pdoAddDBRecordCache[$params['-table']]['stmt']    = pdo_prepare($dbh_pdo, $query);
			if(!is_resource($pdoAddDBRecordCache[$params['-table']]['stmt'])){
				$e=pdo_errormsg();
				$err=array("pdoAddDBRecord prepare Error",$e,$query);
				debugValue($err);
				return printValue($err);
			}
		}
		
		$success = pdo_execute($pdoAddDBRecordCache[$params['-table']]['stmt'],$opts['values']);
		if(!$success){
			$e=pdo_errormsg();
			debugValue(array("pdoAddDBRecord Execute Error",$e,$opts));
    		return "pdoAddDBRecord Execute Error".printValue($e);
		}
		if(isset($params['-noidentity'])){return $success;}
		$result2=pdo_exec($dbh_pdo,"SELECT top 1 ifnull(CURRENT_IDENTITY_VALUE(),0) as cval from {$params['-table']};");
		$row=pdo_fetch_array($result2,0);
		pdo_free_result($result2);
		$row=array_change_key_case($row);
		if(isset($row['cval'])){return $row['cval'];}
		return "pdoAddDBRecord Identity Error".printValue($row).printValue($opts);
		//echo "result2:".printValue($row);
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		$recs=array($err);
		debugValue(array("pdoAddDBRecord Try Error",$e));
		return "pdoAddDBRecord Try Error".printValue($err);
	}
	return 0;
}
//---------- begin function pdoEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
*   -table - name of the table to add to
*   -where - filter criteria
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage 
*	loadExtras('pdo');
*	$id=pdoEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function pdoEditDBRecord($params,$id=0,$opts=array()){
	mb_internal_encoding("UTF-8");
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'pdoEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'pdoEditDBRecord error: No where specified.';}
	global $USER;
	$fields=pdoGetDBFieldInfo($params['-table'],$params);
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
				$v=pdoConvert2UTF8($v);
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
	global $dbh_pdo;
	if(!is_resource($dbh_pdo)){
		$dbh_pdo=pdoDBConnect($params);
	}
	if(!$dbh_pdo){
    	$e=pdo_errormsg();
    	debugValue(array("pdoEditDBRecord2 Connect Error",$e));
    	return;
	}
	try{
		$pdo_stmt    = pdo_prepare($dbh_pdo, $query);
		if(!is_resource($pdo_stmt)){
			$e=pdo_errormsg();
			debugValue(array("pdoEditDBRecord2 prepare Error",$e));
    		return 1;
		}
		$success = pdo_execute($pdo_stmt,$vals);
		//echo $vals[5].$query.printValue($success).printValue($vals);
	}
	catch (Exception $e) {
		debugValue(array("pdoEditDBRecord2 try Error",$e));
    	return;
	}
	return 0;
}
//---------- begin function pdoReplaceDBRecord ----------
/**
* @describe updates or adds a record from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
*   -table - name of the table to add to
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add/edit to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('pdo');
*	$id=pdoReplaceDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function pdoReplaceDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){return 'pdoAddRecord error: No table specified.';}
	$fields=pdoGetDBFieldInfo($params['-table'],$params);
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
	$dbh_pdo=pdoDBConnect($params);
	if(!$dbh_pdo){
    	$e=pdo_errormsg();
    	debugValue(array("pdoReplaceDBRecord Connect Error",$e));
    	return;
	}
	try{
		$result=pdo_exec($dbh_pdo,$query);
		if(!$result){
        	$err=array(
        		'error'	=> pdo_errormsg($dbh_pdo),
				'query'	=> $query
			);
			debugValue($err);
			return "pdoReplaceDBRecord Error".printValue($err).$query;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		pdo_free_result($result);
		debugValue(array("pdoReplaceDBRecord Connect Error",$e));
		return "pdoReplaceDBRecord Error".printValue($err);
	}
	pdo_free_result($result);
	return true;
}
//---------- begin function pdoGetDBRecords
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
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - set of records
* @usage
*	loadExtras('pdo');
*	$recs=pdoGetDBRecords(array('-table'=>'notes','name'=>'bob'));
*	$recs=pdoGetDBRecords("select * from notes where name='bob'");
*/
function pdoGetDBRecords($params){
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
			$ok=pdoExecuteSQL($params);
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
		$fields=pdoGetDBFieldInfo($params['-table'],$params);
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
	return pdoQueryResults($query,$params);
}
//---------- begin function pdoGetDBSchemas ----------
/**
* @describe returns an array of system tables
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage
*	loadExtras('pdo'); 
*	$schemas=pdoGetDBSchemas();
*/
function pdoGetDBSchemas($params=array()){
	$dbh_pdo=pdoDBConnect($params);
	if(!$dbh_pdo){
    	$e=pdo_errormsg();
    	debugValue(array("pdoGetDBSchemas Connect Error",$e));
    	return;
	}
	try{
		$result=pdo_tables($dbh_pdo);
		if(!$result){
        	$err=array(
        		'error'	=> pdo_errormsg($dbh_pdo)
			);
			echo "pdoIsDBTable error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "pdoIsDBTable error: exception".printValue($err);
		exit;
	}
	$recs=array();
	while(pdo_fetch_row($result)){
		if(pdo_result($result,"TABLE_TYPE")!="TABLE"){continue;}
		$schem=pdo_result($result,"TABLE_SCHEM");
		if(in_array($schem,$recs)){continue;}
		$recs[]=$schem;
	}
	pdo_free_result($result);
    return $recs;
}
//---------- begin function pdoGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage 
*	loadExtras('pdo');
*	$tables=pdoGetDBTables();
*/
function pdoGetDBTables($params=array()){
	$dbh_pdo=pdoDBConnect($params);
	if(!$dbh_pdo){
    	$e=pdo_errormsg();
    	debugValue(array("pdoGetDBSchemas Connect Error",$e));
    	return;
	}
	try{
		$result=pdo_tables($dbh_pdo);
		if(!$result){
        	$err=array(
        		'error'	=> pdo_errormsg($dbh_pdo)
			);
			echo "pdoIsDBTable error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "pdoIsDBTable error: exception".printValue($err);
		exit;
	}
	$tables=array();
	while($row=pdo_fetch_array($result)){
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
	pdo_free_result($result);
    return $tables;
}
//---------- begin function pdoGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage
*	loadExtras('pdo'); 
*	$fieldinfo=pdoGetDBFieldInfo('abcschema.abc');
*/
function pdoGetDBFieldInfo($table,$params=array()){
	$dbh_pdo=pdoDBConnect($params);
	if(!$dbh_pdo){
    	$e=pdo_errormsg();
    	debugValue(array("pdoGetDBSchemas Connect Error",$e));
    	return;
	}
	$query="select * from {$table} where 1=0";
	try{
		$result=pdo_exec($dbh_pdo,$query);
		if(!$result){
        	$err=array(
        		'error'	=> pdo_errormsg($dbh_pdo),
        		'query'	=> $query
			);
			echo "pdoGetDBFieldInfo error: No result".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "pdoGetDBFieldInfo error: exception".printValue($err);
		exit;
	}
	$recs=array();
	for($i=1;$i<=pdo_num_fields($result);$i++){
		$field=strtolower(pdo_field_name($result,$i));
        $recs[$field]=array(
        	'table'		=> $table,
        	'_dbtable'	=> $table,
			'name'		=> $field,
			'_dbfield'	=> $field,
			'type'		=> strtolower(pdo_field_type($result,$i)),
			'scale'		=> strtolower(pdo_field_scale($result,$i)),
			'precision'	=> strtolower(pdo_field_precision($result,$i)),
			'length'	=> strtolower(pdo_field_len($result,$i)),
			'num'		=> strtolower(pdo_field_num($result,$i))
		);
		$recs[$field]['_dbtype']=$recs[$field]['type'];
		$recs[$field]['_dblength']=$recs[$field]['length'];
    }
    pdo_free_result($result);
	return $recs;
}
//---------- begin function pdoGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage
*	loadExtras('pdo'); 
*	$cnt=pdoGetDBCount(array('-table'=>'states','-where'=>"code like 'M%'"));
*/
function pdoGetDBCount($params=array()){
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$recs=pdoGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function pdoQueryHeader ----------
/**
* @describe returns a single row array with the column names
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array a single row array with the column names
* @usage
*	loadExtras('pdo'); 
*	$recs=pdoQueryHeader($query);
*/
function pdoQueryHeader($query,$params=array()){
	$dbh_pdo=pdoDBConnect($params);
	if(!$dbh_pdo){
    	$e=pdo_errormsg();
    	debugValue(array("pdoGetDBSchemas Connect Error",$e));
    	return;
	}
	if(!preg_match('/limit\ /is',$query)){
		$query .= " limit 0";
	}
	try{
		$result=pdo_exec($dbh_pdo,$query);
		if(!$result){
        	$err=array(
        		'error'	=> pdo_errormsg($dbh_pdo),
        		'query'	=> $query
			);
			echo "pdoQueryHeader error:".printValue($err);
			exit;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		echo "pdoQueryHeader error: exception".printValue($err);
		exit;
	}
	$fields=array();
	for($i=1;$i<=pdo_num_fields($result);$i++){
		$field=strtolower(pdo_field_name($result,$i));
        $fields[]=$field;
    }
    pdo_free_result($result);
    $rec=array();
    foreach($fields as $field){
		$rec[$field]='';
	}
    $recs=array($rec);
	return $recs;
}
//---------- begin function pdoQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_pdo,dbuser_pdo, and dbpass_pdo
* 	[-dbname] - name of pdo connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage
*	loadExtras('pdo'); 
*	$recs=pdoQueryResults('select top 50 * from abcschema.abc');
*/
function pdoQueryResults($query,$params=array()){
	global $dbh_pdo;
	$starttime=microtime(true);
	if(!is_resource($dbh_pdo)){
		$dbh_pdo=pdoDBConnect($params);
	}
	if(!$dbh_pdo){
    	$e=pdo_errormsg();
    	debugValue(array("pdoQueryResults Connect Error",$e));
    	return json_encode($e);
	}
	try{
		$result=pdo_exec($dbh_pdo,$query);
		if(!$result){
			$errstr=pdo_errormsg($dbh_pdo);
			if(!strlen($errstr)){return array();}
        	$err=array(
        		'error'	=> $errstr,
        		'query' => $query
			);
			if(stringContains($errstr,'session not connected')){
				$dbh_pdo='';
				sleep(1);
				pdo_close_all();
				$dbh_pdo=pdoDBConnect($params);
				$result=pdo_exec($dbh_pdo,$query);
				if(!$result){
					$errstr=pdo_errormsg($dbh_pdo);
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
	$rowcount=pdo_num_rows($result);
	if($rowcount==0 && isset($params['-forceheader'])){
		$fields=array();
		for($i=1;$i<=pdo_num_fields($result);$i++){
			$field=strtolower(pdo_field_name($result,$i));
			$fields[]=$field;
		}
		pdo_free_result($result);
		$rec=array();
		foreach($fields as $field){
			$rec[$field]='';
		}
		$recs=array($rec);
		return $recs;
	}
	if(isset($params['-count'])){
		pdo_free_result($result);
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
			pdo_free_result($result);
			return 'pdoQueryResults error: Failed to open '.$params['-filename'];
			exit;
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],"Rowcount:".$rowcount.PHP_EOL.$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	if(isset($params['-binmode'])){
		pdo_binmode($result, $params['-binmode']);
	}
	if(isset($params['-longreadlen'])){
		pdo_longreadlen($result,$params['-longreadlen']);
	}
	$i=0;
	while(pdo_fetch_row($result)){
		$rec=array();
	    for($z=1;$z<=pdo_num_fields($result);$z++){
			$field=strtolower(pdo_field_name($result,$z));
	        $rec[$field]=pdo_result($result,$z);
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
	pdo_free_result($result);
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
function pdoConvert2UTF8($content) { 
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
//---------- begin function pdoNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function pdoNamedQuery($name){
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