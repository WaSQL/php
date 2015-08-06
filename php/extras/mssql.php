<?php

ini_set('mssql.charset', 'UTF-8'); 
ini_set('mssql.max_persistent',5);
ini_set('mssql.secure_connection ',0);
ini_set ( 'mssql.textlimit' , '65536' );
ini_set ( 'mssql.textsize' , '65536' );
global $mssql;
$mssql=array();
//---------- begin function mssqlDBConnect ----------
/**
* @describe returns connection resource
* @param params array
*	-host - mssql server to connect to
*	-user - username
*	-pass - password
*	-name - name of database to connect to
* @return connection resource and sets the global $mssql variables, conn and mssqlDBConnect.
*/
function mssqlDBConnect($params=array()){
	if(!isset($params['-host'])){return "mssqlDBConnect Error: no host specified";}
	if(!isset($params['-user'])){return "mssqlDBConnect Error: no user specified";}
	if(!isset($params['-pass'])){return "mssqlDBConnect Error: no pass specified";}
	if(!isset($params['-name'])){return "mssqlDBConnect Error: no pass specified";}
	global $mssql;
	$mssql['mssqlDBConnect']=$params;
	$mssql['conn'] = mssql_pconnect($params['-host'],$params['-user'],$params['-pass']);
	if (!$mssql['conn']) {
    		$e = mssql_get_last_message();
    		return "mssqlDBConnect Error: ".printValue($e);
	}
	$ok=mssql_select_db($params['-name'],$mssql['conn']);
	if (!$ok) {
    		$e = mssql_get_last_message();
    		return "mssqlDBConnect Error: ".printValue($e);
	}
	return $mssql['conn'];
}

function mssqlAddRecord($params){
	global $USER;
	if(!isset($params['-table'])){return 'mssqlAddRecord error: No table specified.';}
	$fields=mssqlGetTableFields($params['-table']);
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
		VALUES(
			{$valstr}
		)
ENDOFQUERY;
	return mssqlQueryResults($query);
}
function mssqlEditRecord($params){
	if(!isset($params['-table'])){return 'mssqlEditRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'mssqlEditRecord error: No where specified.';}
	global $USER;
	$fields=mssqlGetTableFields($params['-table']);
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
	mssqlQueryResults($query);
	return;
}
function mssqlGetRecords($params){
	if(!isset($params['-table'])){return 'mssqlGetRecords error: No table specified.';}
	if(!isset($params['-fields'])){$params['-fields']='*';}
	$fields=mssqlGetTableFields($params['-table']);
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
	return mssqlQueryResults($query);
	}

//---------- begin function mssqlGetTables ----------
/**
* @describe returns connection resource
* @return array
*	dbname, owner, name, type
*/
function mssqlGetTables(){
	$query="
	SELECT
		table_catalog as dbname
        ,table_schema as owner
        ,table_name as name
        ,table_type as type
	FROM 
		information_schema.tables (nolock)
	ORDER BY
		table_name
	";
	$recs = mssqlQueryResults($query);
	return $recs;
}

function mssqlGetTablePrimaryKeys($table,$catalog='TASKEAssist',$owner='dbo'){
	$query="
	SELECT
		ccu.column_name
		,ccu.constraint_name
	FROM 
		information_schema.table_constraints (nolock) as tc
    INNER JOIN 
		information_schema.constraint_column_usage as ccu 
		ON tc.constraint_name = ccu.constraint_name
	WHERE 
		tc.table_catalog = '{$catalog}'
    	and tc.table_schema = '{$owner}'
    	and tc.table_name = '{$table}'
    	and tc.constraint_type = 'PRIMARY KEY'
	";
	$tmp = mssqlQueryResults($query);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
function mssqlGetTableFields($table,$catalog='TASKEAssist',$owner='dbo'){
	$query="
		SELECT 
			COLUMN_NAME
			,ORDINAL_POSITION
			,COLUMN_DEFAULT
			,IS_NULLABLE
			,DATA_TYPE
			,CHARACTER_MAXIMUM_LENGTH 
		FROM 
			INFORMATION_SCHEMA.COLUMNS (nolock) 
		WHERE 
			table_catalog = '{$catalog}'
    		and table_schema = '{$owner}'
			and table_name = '{$table}'
		";
	$recs=mssqlQueryResults($query);
	$fields=array();
	foreach($recs as $rec){
		$name=strtolower($rec['column_name']);
		$fields[$name]=array(
			'_dbseq'	=> $rec['ordinal_position'],
		 	'_dbname'	=> $name,
		 	'_dbdefault'=> $rec['column_default'],
			'_dbnull'	=> $rec['is_nullable'],
			'_dbmax'	=> $rec['character_maximum_length'],
			'_dbtype'	=> $rec['data_type'],
			'_dblen'	=> $rec['character_maximum_length'],
			);
		}
    ksort($fields);
	return $fields;
}
//---------- begin function mssqlQueryResults ----------
/**
* @describe returns the mssql record set
* @param query string
*	SQL query to execute
* @return array
*	returns records
*/
function mssqlQueryResults($query='',$params=array()){
	global $USER;
	global $mssql;
	if(!$mssql['mssqlDBConnect']){
		return "mssqlQueryResults Error: No connection. call mssqlDBConnect first.";
	}
	$mssql['conn']=mssqlDBConnect($mssql['mssqlDBConnect']);
	if(!$mssql['conn']){
		return "mssqlQueryResults Error: No connection. call mssqlDBConnect first.";
	}
	mssql_query("SET ANSI_NULLS ON");
	mssql_query("SET ANSI_WARNINGS ON");
	$data=@mssql_query($query,$mssql['conn']);
	if(!$data){return "MS SQL Query Error: " . mssql_get_last_message();}
	if(preg_match('/^insert /i',$query)){
    	//return the id inserted on insert statements
    	$id=databaseAffectedRows($data);
    	mssql_close($mssql['conn']);
    	return $id;
	}
	$results = mssqlEnumQueryResults($data,$showblank);
	mssql_close($mssql['conn']);
	return $results;
}
//---------- begin function mssqlEnumQueryResults ----------
/**
* @describe enumerates through the data from a mssql_query call
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function mssqlEnumQueryResults($data,$showblank=0,$fieldmap=array()){
	$results=array();
	do {
		while ($row = @mssql_fetch_assoc($data)){
			$crow=array();
			foreach($row as $key=>$val){
				if(!$showblank && !strlen(trim($val))){continue;}
				$key=strtolower($key);
				if(isset($fieldmap[$key])){$key=$fieldmap[$key];}
				if(preg_match('/(.+?)\:[0-9]{3,3}(PM|AM)$/i',$val,$tmatch)){
					$newval=$tmatch[1].' '.$tmatch[2];
					$crow[$key."_zulu"]=$val;
					$crow[$key."_utime"]=strtotime($newval);
					$val=date("Y-m-d H:i:s",$crow[$key."_utime"]);
            		}
				$crow[$key]=$val;
				}
			//ksort($crow);
	        $results[] = $crow;
	    	}
		}
	while ( @mssql_next_result($data) );
	return $results;
}
