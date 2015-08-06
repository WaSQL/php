<?php
ini_set('oci8.max_persistent',5);
ini_set('oci8.persistent_timeout',60);
ini_set('oci8.statement_cache_size',50);
global $oracle;
//---------- begin function oracleDBConnect ----------
/**
* @describe returns connection resource
* @param params array
*	-host - mssql server to connect to
*	-user - username
*	-pass - password
*	-name - name of database to connect to
*	[-port] - port defaults to 1521
*	[-charset] - character set - defaults to 'AL32UTF8'
* @return connection resource and sets the global $oracle variables, conn and mssqlDBConnect.
*/
function oracleDBConnect($params=array()){
	if(!isset($params['-host'])){return "mssqlDBConnect Error: no host specified";}
	if(!isset($params['-user'])){return "mssqlDBConnect Error: no user specified";}
	if(!isset($params['-pass'])){return "mssqlDBConnect Error: no pass specified";}
	if(!isset($params['-name'])){return "mssqlDBConnect Error: no pass specified";}
	if(!isset($params['-port'])){$params['-port']=1521;}
	if(!isset($params['-charset'])){$params['-charset']='AL32UTF8';}
	global $oracle;
	$connection_str="(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$params['-host']})(PORT={$params['-port']}))(CONNECT_DATA=(SERVER=DEDICATED)(SERVICE_NAME={$params['-name']})))";
	$oracle['oracleDBConnect']=$params;
	$oracle['conn'] = oci_pconnect($params['-user'],$params['-pass'],$connection_str,$params['-pass']);
	if (!$oracle['conn']) {
    		$e = oci_error();
    		return "oracleDBConnect Error: ".printValue($e);
	}
	return $oracle['conn'];
}

function oracleAddRecord($params){
	global $USER;
	if(!isset($params['-table'])){return 'oracleAddRecord error: No table specified.';}
	$fields=oracleGetTableFields($params['-table']);
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
		$opts['fields'][]='CDATE';
		$opts['values'][]=strtoupper(date('d-M-Y'));
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
		if($k=='cuser' || $k=='cdate'){continue;}
		if(is_array($params[$k])){
            	$params[$k]=implode(':',$params[$k]);
		}
		switch(strtolower($fields[$k]['type'])){
        	case 'integer':
        	case 'number':
        		$opts['values'][]=$params[$k];
        	break;
        	case 'date':
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
	global $oracle;
	if(!$oracle['oracleDBConnect']){
		return "oracleQueryResults Error: No connection. call oracleDBConnect first.";
	}
	$oracle['conn']=oracleDBConnect($oracle['oracleDBConnect']);
	if(!$oracle['conn']){
		return "oracleQueryResults Error: No connection. call oracleDBConnect first.";
	}

	oci_rollback($oracle['conn']);
	$stid = oci_parse($oracle['conn'], $query);
	if (!$stid) {
    	$e = oci_error($oracle['conn']);
		debugValue(array('oracleAddRecord Parse Error',$e));
		oci_close($oracle['conn']);
    	return;
	}
	$r = oci_execute($stid,OCI_COMMIT_ON_SUCCESS);
	if (!$r) {
		$e = oci_error($stid);
	    debugValue(array("oracleAddRecord Execute Error",$e));
	    oci_free_statement($stid);
		oci_close($oracle['conn']);
    	return 'error';
	}
	//sequence?
	if(strlen($sequence)){
		$xquery="select \"{$owner}\".\"{$sequence}\".currval as cval from dual";
		$stid = oci_parse($oracle['conn'], $xquery);
		if (!$stid) {
	    	$e = oci_error($oracle['conn']);
			debugValue(array('oracleAddRecord Parse Error',$e,$query));
			oci_close($oracle['conn']);
	    	return;
		}
		$r = oci_execute($stid);
		if (!$r) {
			$e = oci_error($stid);
		    debugValue(array("oracleAddRecord Execute Error",$e));
		    oci_free_statement($stid);
			oci_close($oracle['conn']);
	    	return 'error';
		}
		//read results into a recordset array
		$recs=array();
		$id=0;
		while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
			$rec=array();
			foreach ($row as $field=>$val){
				$field=strtolower($field);
				$rec[$field]=$val;
			}
			$recs[]=$rec;
		}
		oci_free_statement($stid);
		oci_close($oracle['conn']);
		return $recs[0]['cval'];
	}
	return 'error_3';
}
function oracleEditRecord($params){
	if(!isset($params['-table'])){return 'oracleEditRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'oracleEditRecord error: No where specified.';}
	global $USER;
	$fields=oracleGetTableFields($params['-table']);
	$opts=array();
	if(isset($fields['edate'])){
		$opts['edate']=strtoupper(date('d-M-Y'));
	}
	if(isset($fields['euser'])){
		$opts['euser']=$USER['username'];
	}
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		if($k=='cuser' || $k=='cdate'){continue;}
		if(is_array($params[$k])){
            	$params[$k]=implode(':',$params[$k]);
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
	oracleQueryResults($query);
	return;
}
function oracleGetRecords($params){
	if(!isset($params['-table'])){return 'oracleGetRecords error: No table specified.';}
	if(!isset($params['-fields'])){$params['-fields']='*';}
	$fields=oracleGetTableFields($params['-table']);
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
	return oracleQueryResults($query);
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
//---------- begin function oracleGetTables ----------
/**
* @describe returns connection resource
* @return array
*	dbname, owner, name, type
*/
function oracleGetTables(){
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
function oracleGetTablePrimaryKeys($table,$owner){
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
function oracleGetTableFields($table,$owner=''){
	$table=str_replace("'","''",$table);
	$table=strtoupper($table);
	global $oracle;
	if(!$oracle['oracleDBConnect']){
		return "oracleQueryResults Error: No connection. call oracleDBConnect first.";
	}
	$oracle['conn']=oracleDBConnect($oracle['oracleDBConnect']);
	if(!$oracle['conn']){
		return "oracleQueryResults Error: No connection. call oracleDBConnect first.";
	}
	if(strlen($owner)){
		$owner=str_replace("'","''",$owner);
		$owner=strtoupper($owner);
		$owner.='.';
		}
	$stid = oci_parse($oracle['conn'], "select * from {$owner}{$table} where 1=0");
	oci_execute($stid, OCI_DESCRIBE_ONLY);
	$ncols = oci_num_fields($stid);
	$fields=array();
	for ($i = 1; $i <= $ncols; $i++) {
		$field=array(
			'table'	=> $table,
			'owner'	=> $owner,
			'name'	=> oci_field_name($stid, $i),
			'type'	=> oci_field_type($stid, $i),
			'precision'	=> oci_field_precision($stid, $i),
			'scale'	=> oci_field_scale($stid, $i),
			'size'	=> oci_field_size($stid, $i),
			'type_raw'	=> oci_field_type_raw($stid, $i),
		);
	    $fields[]=$field;
	}
	oci_free_statement($stid);
	oci_close($oracle['conn']);
	//add field descriptions
	$map=oracleGetColDescriptions($table,$owner);
	foreach($fields as $i=>$field){
		if(isset($map[$field['name']])){
          	$fields[$i]['description']=$map[$field['name']];
		}
	}
	//sort by name
	$fields=sortArrayByKey($fields,'name',SORT_ASC);
	//make index the field name
	$tmp=$fields;
	$fields=array();
	foreach($tmp as $field){
		$key=strtolower($field['name']);
    	$fields[$key]=$field;
	}
	//echo printValue($fields);exit;
	return $fields;
}
//---------- begin function oracleQueryResults ----------
/**
* @describe returns the oracle record set
* @param query string
*	SQL query to execute
* @return array
*	returns records
*/
function oracleQueryResults($query='',$params=array()){
	global $USER;
	global $oracle;
	if(!$oracle['oracleDBConnect']){
		return "oracleQueryResults Error: No connection. call oracleDBConnect first.";
	}
	$oracle['conn']=oracleDBConnect($oracle['oracleDBConnect']);
	if(!$oracle['conn']){
		return "oracleQueryResults Error: No connection. call oracleDBConnect first.";
	}
	oci_rollback($oracle['conn']);
	if(!isset($params['setmodule'])){$params['setmodule']=true;}
	$stid = oci_parse($oracle['conn'], $query);
	if (!$stid) {
    	$e = oci_error($oracle['conn']);
		debugValue(array('OracleQueryResults Parse Error',$e));
		oci_close($oracle['conn']);
    	return;
	}
	if($params['setmodule']){
		if(!isset($params['module'])){$params['module']='reporTERRA';}
		if(!isset($params['action'])){
			if(isset($_REQUEST['AjaxRequestUniqueId'])){$params['action']=$_REQUEST['AjaxRequestUniqueId'];}
			else{$params['action']='oracleQueryResults';}
		}
		if(!isset($params['id'])){$params['id']=$USER['username'];}
		//echo $query.printValue($params).printValue($USER);exit;
		oci_set_module_name($oracle['conn'], $params['module']);
		oci_set_action($oracle['conn'], $params['action']);
		oci_set_client_identifier($oracle['conn'], $params['id']);
	}
	// check for non-select query
	if(preg_match('/^(update|insert|alter)/is',trim($query))){
		$r = oci_execute($stid,OCI_COMMIT_ON_SUCCESS);
    	oci_free_statement($stid);
		oci_close($oracle['conn']);
    	return;
	}
	$r = oci_execute($stid);
	if (!$r) {
		$e = oci_error($stid);
	    debugValue(array("oracleQueryResults Execute Error",$e));
	    oci_free_statement($stid);
		oci_close($oracle['conn']);
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
		oci_set_module_name($oracle['conn'], 'idle');
		oci_set_action($oracle['conn'], 'idle');
		oci_set_client_identifier($oracle['conn'], 'idle');
	}
	oci_close($oracle['conn']);
	return $recs;
}
