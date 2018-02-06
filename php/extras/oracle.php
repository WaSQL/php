<?php
ini_set('oci8.max_persistent',5);
ini_set('oci8.persistent_timeout',60);
ini_set('oci8.statement_cache_size',50);
//---------- begin function oracleParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_oracle,dbuser_oracle, and dbpass_oracle
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=oracleParseConnectParams($params);
*/
function oracleParseConnectParams($params=array()){
	global $CONFIG;
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_oracle'])){
			$params['-dbuser']=$CONFIG['dbuser_oracle'];
			$params['-dbuser_source']="CONFIG dbuser_oracle";
		}
		elseif(isset($CONFIG['oracle_dbuser'])){
			$params['-dbuser']=$CONFIG['oracle_dbuser'];
			$params['-dbuser_source']="CONFIG oracle_dbuser";
		}
		else{return 'oracleParseConnectParams Error: No dbuser set';}
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
			elseif(isset($CONFIG['oracle_port'])){$port=$CONFIG['oracle_port'];}
			elseif(isset($CONFIG['port_oracle'])){$port=$CONFIG['port_oracle'];}
			$connect_data='';
			//sid - identify the Oracle8 database instance by its Oracle System Identifier (SID)
			if(isset($params['-sid'])){$connect_data.="(SID={$params['-sid']})";}
			elseif(isset($CONFIG['oracle_sid'])){$connect_data.="(SID={$CONFIG['oracle_sid']})";}
			elseif(isset($CONFIG['sid_oracle'])){$connect_data.="(SID={$CONFIG['sid_oracle']})";}
			//service_name - identify the Oracle9i or Oracle8 database service to access
			if(isset($params['-service_name'])){$connect_data.="(SERVICE_NAME={$params['-service_name']})";}
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
		echo "oracleDBConnect error: no connect params".printValue($params);
		exit;
	}
	if(isset($params['-single'])){
		$dbh_single = oci_connect($params['-dbuser'],$params['-dbpass'],$params['-connect'],'AL32UTF8');
		if(!is_resource($dbh_single)){
			$err=json_encode(oci_error());
			echo "oracleDBConnect single connect error:{$err}".printValue($params);
			exit;
		}
		return $dbh_single;
	}
	global $dbh_oracle;
	if(is_resource($dbh_oracle)){return $dbh_oracle;}
	try{
		$dbh_oracle = oci_pconnect($params['-dbuser'],$params['-dbpass'],$params['-connect'],'AL32UTF8');
		if(!is_resource($dbh_oracle)){
			$err=json_encode(oci_error());
			echo "oracleDBConnect error:{$err}".printValue($params);
			exit;

		}
		return $dbh_oracle;
	}
	catch (Exception $e) {
		echo "oracleDBConnect exception" . printValue($e);
		exit;
	}
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
		$err=json_encode(oci_error($stid));
		echo "oracleAutoCommit error:{$err}";
		exit;
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
//---------- begin function oracleAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_oracle,dbuser_oracle, and dbpass_oracle
*   -table - name of the table to add to
*	[-host] - oracle server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=oracleAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function oracleAddDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){return 'oracleAddRecord error: No table specified.';}
	$fields=oracleGetDBFieldInfo($params['-table'],$params);
	$sequence='';
	$owner='';
	foreach($fields as $field){
    	if(isset($field['sequence'])){
        	$sequence=$field['sequence'];
        	$owner=$field['owner'];
        	break;
		}
	}
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
		if(is_array($params[$k])){
            	$params[$k]=implode(':',$params[$k]);
		}
		switch(strtolower($fields[$k]['type'])){
        	case 'integer':
        	case 'number':
        		$opts['values'][]=$params[$k];
        	break;
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$params[$k]=date('Y-m-d',strtotime($v));
				}
        		$opts['values'][]="todate('{$params[$k]}')";
        	break;
        	default:
        		$opts['values'][]="'{$params[$k]}'";
        	break;
		}
        $params[$k]=str_replace("'","''",$params[$k]);

        $opts['fields'][]=trim(strtoupper($k));
	}
	$fieldstr=implode('","',$opts['fields']);
	$valstr=implode(',',$opts['values']);
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
		("{$fieldstr}")
		values({$valstr})
ENDOFQUERY;
	return oracleQueryResults($query,$params);
}
function oracleEditDBRecord($params){
	if(!isset($params['-table'])){return 'oracleEditRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'oracleEditRecord error: No where specified.';}
	global $USER;
	global $dbh_oracle;
	$fields=oracleGetDBFieldInfo($params['-table'],$params);
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
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		if($k=='cuser' || $k=='cdate'){continue;}
		if(is_array($params[$k])){
            $params[$k]=implode(':',$params[$k]);
		}
		switch(strtolower($fields[$k]['type'])){
        	case 'date':
				if($k=='edate' || $k=='_edate'){
					$params[$k]=date('Y-m-d',strtotime($v));
				}
        	break;
		}
        $params[$k]=str_replace("'","''",$params[$k]);
        $updates[]="upper({$k})=upper('{$params[$k]}')";
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	return oracleQueryResults($query,$params);
	return;
}
function oracleGetDBRecords($params){
	if(!isset($params['-table'])){return 'oracleGetRecords error: No table specified.';}
	if(!isset($params['-fields'])){$params['-fields']='*';}
	$fields=oracleGetDBFieldInfo($params['-table'],$params);
	$ands=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		if(is_array($params[$k])){
            $params[$k]=implode(':',$params[$k]);
		}
        $params[$k]=str_replace("'","''",$params[$k]);
        $ands[]="upper({$k})=upper('{$params[$k]}')";
	}
	$wherestr='';
	if(count($ands)){
		$wherestr='WHERE '.implode(' and ',$ands);
	}
    $query=<<<ENDOFQUERY
		SELECT
			{$params['-fields']}
		FROM
			{$params['-table']}
		{$wherestr}
ENDOFQUERY;
	if(isset($params['-order'])){
    	$query .= " ORDER BY {$params['-order']}";
	}
	return oracleQueryResults($query,$params);
}

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
//---------- begin function oracleGetDBTables ----------
/**
* @describe returns connection resource
* @return array
*	dbname, owner, name, type
*/
function oracleGetDBTables($params=array()){
	global $CONFIG;
	$query=<<<ENDOFQUERY
		SELECT 
			owner,table_name,last_analyzed,num_rows,pct_free 
		FROM 
			all_tables 
		WHERE 
			owner not in ('SYS','SYSTEM') 
			and tablespace_name not in ('SYS','SYSAUX','SYSTEM') 
			and status='VALID'
		ORDER BY 
			owner,table_name
ENDOFQUERY;
	$recs = oracleQueryResults($query,$params);
	$tables=array();
	foreach($recs as $rec){
		$tables[]="{$rec['owner']}.{$rec['table_name']}";
	}
	return $tables;
}
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
function oracleGetDBFieldInfo($table,$params=array()){
	global $dbh_oracle;
	if(!is_resource($dbh_oracle)){
		$dbh_oracle=oracleDBConnect($params);
	}
	if(!$dbh_oracle){
    	$e=json_encode(oci_error());
    	debugValue(array("oracleGetDBFieldInfo Connect Error",$e));
    	return;
	}
	$pkeys=oracleGetDBTablePrimaryKeys($table,$params);
	//echo $table.printValue($pkeys);exit;
	$query="select * from {$table} where 1=0";
	$stid = oci_parse($dbh_oracle, $query);
	if(!$stid){
    	$e=json_encode(oci_error());
    	debugValue(array("oracleGetDBFieldInfo Parse Error",$query,$e));
    	return;
	}
	oci_execute($stid, OCI_DESCRIBE_ONLY);
	$ncols = oci_num_fields($stid);
	$fields=array();
	for ($i = 1; $i <= $ncols; $i++) {
		$name=oci_field_name($stid, $i);
		$field=array(
			'table'	=> $table,
			'name'	=> $name,
			'type'	=> oci_field_type($stid, $i),
			'precision'	=> oci_field_precision($stid, $i),
			'scale'	=> oci_field_scale($stid, $i),
			'length'	=> oci_field_size($stid, $i),
			//'type_raw'	=> oci_field_type_raw($stid, $i),
		);
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
//---------- begin function oracleQueryResults ----------
/**
* @describe returns the oracle records from query
* @param $query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[module] - module to set query against. Defaults to 'waSQL'
* 	[action] - action to set query against. Defaults to 'oracleQueryResults'
* 	[id] - identifier to set query against. Defaults to current user
* 	[setmodule] boolean - set to false to not set module, action, and id. Defaults to true
* 	[-idcolumn] boolean - set to true to include row number as _id column
* @return array - returns records
*/
function oracleQueryResults($query='',$params=array()){
	global $USER;
	global $dbh_oracle;
	if(!is_resource($dbh_oracle)){
		$dbh_oracle=oracleDBConnect($params);
	}
	if(!$dbh_oracle){
    	$e=json_encode(oci_error());
    	debugValue(array("oracleQueryResults Connect Error",$e));
    	return;
	}
	oci_rollback($dbh_oracle);
	if(!isset($params['setmodule'])){$params['setmodule']=true;}
	$stid = oci_parse($dbh_oracle, $query);
	if (!$stid) {
    	$e = json_encode(oci_error($dbh_oracle));
		debugValue(array('OracleQueryResults Parse Error',$e));
		oci_close($dbh_oracle);
    	return;
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
	if(preg_match('/^(update|insert|alter)/is',trim($query))){
		$r = oci_execute($stid,OCI_COMMIT_ON_SUCCESS);
    	oci_free_statement($stid);
    	if($params['setmodule']){
			oci_set_module_name($dbh_oracle, 'idle');
			oci_set_action($dbh_oracle, 'idle');
			oci_set_client_identifier($dbh_oracle, 'idle');
		}
		oci_close($dbh_oracle);
    	return;
	}
	$r = oci_execute($stid);
	if (!$r) {
		$e = json_encode(oci_error($stid));
	    debugValue(array("oracleQueryResults Execute Error",$e));
	    oci_free_statement($stid);
	    if($params['setmodule']){
			oci_set_module_name($dbh_oracle, 'idle');
			oci_set_action($dbh_oracle, 'idle');
			oci_set_client_identifier($dbh_oracle, 'idle');
		}
		oci_close($dbh_oracle);
    	return;
	}
	//read results into a recordset array
	$rowcount=oci_num_rows($stid);
	if($rowcount==0 && isset($params['-forceheader'])){
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
		return $recs;
	}
	$recs=array();
	$id=0;
	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
		$rec=array();
		if(isset($params['-idcolumn'])){$rec['_id']=$id;}
		foreach ($row as $field=>$val){
			$field=strtolower($field);
			$rec[$field]=$val;
		}
		$id++;
		$recs[]=$rec;
	}

	oci_free_statement($stid);
	if($params['setmodule']){
		oci_set_module_name($dbh_oracle, 'idle');
		oci_set_action($dbh_oracle, 'idle');
		oci_set_client_identifier($dbh_oracle, 'idle');
	}
	oci_close($dbh_oracle);
	return $recs;
}
//---------- begin function oracleExecuteSQL ----------
/**
* @describe executes query and returns succes or error
* @param $query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_mssql,dbuser_mssql, and dbpass_mssql
*	[-host] - mssql server to connect to
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
	global $dbh_oracle;
	if(!is_resource($dbh_oracle)){
		$dbh_oracle=oracleDBConnect($params);
	}
	if(!$dbh_oracle){
    	$e=json_encode(oci_error());
    	debugValue(array("oracleQueryResults Connect Error",$e));
    	return;
	}
	oci_rollback($dbh_oracle);
	if(!isset($params['setmodule'])){$params['setmodule']=true;}
	$stid = oci_parse($dbh_oracle, $query);
	if (!$stid) {
    	$e = json_encode(oci_error($dbh_oracle));
		debugValue(array('OracleQueryResults Parse Error',$e));
		oci_close($dbh_oracle);
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
	// check for non-select query
	if(preg_match('/^(update|insert|alter)/is',trim($query))){
		$r = oci_execute($stid,OCI_COMMIT_ON_SUCCESS);
    	oci_free_statement($stid);
    	if($params['setmodule']){
			oci_set_module_name($dbh_oracle, 'idle');
			oci_set_action($dbh_oracle, 'idle');
			oci_set_client_identifier($dbh_oracle, 'idle');
		}
		oci_close($dbh_oracle);
    	return;
	}
	$r = oci_execute($stid);
	if (!$r) {
		$e = json_encode(oci_error($stid));
	    
	    oci_free_statement($stid);
	    if($params['setmodule']){
			oci_set_module_name($dbh_oracle, 'idle');
			oci_set_action($dbh_oracle, 'idle');
			oci_set_client_identifier($dbh_oracle, 'idle');
		}
		oci_close($dbh_oracle);
    	return;
	}
	return true;
}
