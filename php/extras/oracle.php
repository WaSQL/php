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
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_oracle'])){
			$params['-dbname']=$CONFIG['dbname_oracle'];
			$params['-dbname_source']="CONFIG dbname_oracle";
		}
		elseif(isset($CONFIG['oracle_dbname'])){
			$params['-dbname']=$CONFIG['oracle_dbname'];
			$params['-dbname_source']="CONFIG oracle_dbname";
		}
		else{return 'oracleParseConnectParams Error: No dbname set';}
	}
	else{
		$params['-dbname_source']="passed in";
	}
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
*/
function oracleDBConnect($params=array()){
	if(!isset($params['-port'])){$params['-port']=1521;}
	if(!isset($params['-charset'])){$params['-charset']='AL32UTF8';}
	$params=oracleParseConnectParams($params);
	if(!isset($params['-dbname'])){return $params;}
	if(isset($params['-single'])){
		$connection_str="(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$params['-dbhost']})(PORT={$params['-port']}))(CONNECT_DATA=(SERVER=DEDICATED)(SERVICE_NAME={$params['-dbname']})))";
		$dbh_single = oci_connect($params['-dbuser'],$params['-dbpass'],$connection_str);
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
		$connection_str="(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$params['-dbhost']})(PORT={$params['-port']}))(CONNECT_DATA=(SERVER=DEDICATED)(SERVICE_NAME={$params['-dbname']})))";
		$dbh_oracle = oci_pconnect($params['-dbuser'],$params['-dbpass'],$connection_str);
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
	global $dbh_oracle;
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

function oracleGetActiveSessionCount($seconds=0){
	$query="
		SELECT
			count(*) cnt
		FROM v\$session sess
		WHERE
			sess.type='USER'
			and sess.status='ACTIVE'
	";
	if($seconds > 0){
		$query .= " AND sess.LAST_CALL_ET >= {$seconds}";
	}
	$recs=oracleQueryResults($query);
	if(isset($recs[0]['cnt'])){return $recs[0]['cnt'];}
	return $query;
}
//---------- begin function oracleGetDBTables ----------
/**
* @describe returns connection resource
* @return array
*	dbname, owner, name, type
*/
function oracleGetDBTables(){
	$query="
		SELECT
			distinct table_name,owner
		FROM
			dba_tab_columns
		ORDER BY
			owner,table_name
	";
	$query .= ' ';
	$tmp = oracleQueryResults($query);
	$tmp2=array();
	foreach($tmp as $rec){
     	if(preg_match('/\$/i',$rec['table_name'])){continue;}
     	if(!isset($recs[$rec['owner']]['name'])){
          	$tmp2[$rec['owner']]['owner']=$rec['owner'];
		}
     	$tmp2[$rec['owner']]['tables'][]=array(
		 	'owner'	=> $rec['owner'],
		 	'name'	=> $rec['table_name']
		 );
	}
	$recs=array();
	foreach($tmp2 as $tmp){$recs[]=$tmp;}
	return $recs;
}
function oracleGetDBTablePrimaryKeys($table,$owner){
	$table=str_replace("'","''",$table);
	$table=strtoupper($table);
	$owner=str_replace("'","''",$owner);
	$owner=strtoupper($owner);
	$query="
	SELECT
  		cols.column_name
		,cols.position
		,cons.status
		,cons.owner
	FROM all_constraints cons, all_cons_columns cols
	WHERE
		upper(cols.table_name) = '{$table}'
		AND upper(cols.owner) = '{$owner}'
		AND cons.constraint_type = 'P'
		AND cons.constraint_name = cols.constraint_name
		AND cons.owner = cols.owner
	ORDER BY cols.position
	";
	$tmp = oracleQueryResults($query);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
function oracleGetDBFieldInfo($table,$owner=''){
	$table=str_replace("'","''",$table);
	$table=strtoupper($table);
	global $oracle;
	if(!$oracle['oracleDBConnect']){
		return "oracleQueryResults Error: No connection. call oracleDBConnect first.";
	}
	$dbh_oracle=oracleDBConnect($oracle['oracleDBConnect']);
	if(!$dbh_oracle){
		return "oracleQueryResults Error: No connection. call oracleDBConnect first.";
	}
	if(strlen($owner)){
		$owner=str_replace("'","''",$owner);
		$owner=strtoupper($owner);
		$owner.='.';
		}
	$stid = oci_parse($dbh_oracle, "select * from {$owner}{$table} where 1=0");
	oci_execute($stid, OCI_DESCRIBE_ONLY);
	$ncols = oci_num_fields($stid);
	$fields=array();
	for ($i = 1; $i <= $ncols; $i++) {
		$name=oci_field_name($stid, $i);
		$field=array(
			'table'	=> $table,
			'owner'	=> $owner,
			'name'	=> $name,
			'type'	=> oci_field_type($stid, $i),
			'precision'	=> oci_field_precision($stid, $i),
			'scale'	=> oci_field_scale($stid, $i),
			'length'	=> oci_field_size($stid, $i),
			//'type_raw'	=> oci_field_type_raw($stid, $i),
		);
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
    	$e = oci_error($dbh_oracle);
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
		$e = oci_error($stid);
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
	$recs=array();
	$id=0;
	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
		$rec=array();
		if($params['-idcolumn']){$rec['_id']=$id;}
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
