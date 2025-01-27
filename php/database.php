<?php
/* Last Updated: 2017-04-08 */
$progpath=dirname(__FILE__);
include_once("{$progpath}/common.php");
include_once("{$progpath}/config.php");
//create a global variable for storing queries that have already happened
global $databaseCache;
if(!is_array($databaseCache)){$databaseCache=array();}
/* Connect to the database for this host */
global $dbh;
global $CONFIG;
try{
	$dbh=databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
}
catch(Exception $e){
	$dbh=false;
}
if(!$dbh){
	$error=databaseError();
	if(isPostgreSQL()){$error .= "<br>PostgreSQL does not allow CREATE DATABASE inside a transaction block. Create the database first.";}
	$msg = '<div>'.PHP_EOL;
	$msg .= '	<div class="w_bigger w_red"><img src="/wfiles/iconsets/32/abort.png" style="vertical-align:middle;" alt="abort" /> Failed to connect to the <b>'.$CONFIG['dbtype'].'</b> service on <b>'.$CONFIG['dbhost'].'</b></div>'.PHP_EOL;
	$msg .= "	<div>{$error}</div>".PHP_EOL;
	$msg .= '</div>'.PHP_EOL;
	echo $msg;
	exit;
	}
//select database
$sel=databaseSelectDb($CONFIG['dbname']);
//create the db if it does not exist
if(!$sel){
	if(databaseQuery("create database {$CONFIG['dbname']}")){
		if(isPostgreSQL()){
        	$dbh=databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
		}
		$sel=databaseSelectDb($CONFIG['dbname']);
	}
}
if(!$sel){
	$error=databaseError();
	$msg = '<div>'.PHP_EOL;
	$msg .= '	<div class="w_bigger w_red"><img src="/wfiles/iconsets/32/abort.png" style="vertical-align:middle;" alt="abort" /> Failed to select database named <b>'.$CONFIG['dbname'].'</b> in <b>'.$CONFIG['dbtype'].'</b> on <b>'.$CONFIG['dbhost'].'</b></div>'.PHP_EOL;
	$msg .= "	<div>{$error}</div>".PHP_EOL;
	$msg .= '</div>'.PHP_EOL;
	abort($msg);
}
/* Load settings in _config table */
$recs=getDBRecords(array('-table'=>'_config','-fields'=>'name,current_value'));
if(isset($recs[0])){
	foreach($recs as $rec){
		if(is_null($rec['current_value']) || !strlen(trim($rec['current_value']))){continue;}
		if(is_null($rec['name']) || !strlen(trim($rec['name']))){continue;}
		$key=strtolower(trim($rec['name']));
		$CONFIG[$key]=trim($rec['current_value']);
	}
}
/* Load_pages as specified in the conf settings */
if(!isset($_REQUEST['_return'])){$_REQUEST['_return']='';}
if(isset($_REQUEST['_action']) && strtoupper($_REQUEST['_action'])=='EDIT' && strtoupper($_REQUEST['_return'])=='XML' && isset($_REQUEST['apikey'])){}
elseif(isset($_REQUEST['apimethod']) && $_REQUEST['apimethod']=='posteditxml' && isset($_REQUEST['apikey'])){}
elseif(isset($_REQUEST['apimethod']) && $_REQUEST['apimethod']=='posteditxmlfromjson' && isset($_REQUEST['apikey'])){}
elseif(isset($_REQUEST['apimethod']) && $_REQUEST['apimethod']=='posteditsha' && isset($_REQUEST['apikey'])){}
elseif(isset($CONFIG['load_pages']) && strlen($CONFIG['load_pages'])){
	$loads=explode(',',$CONFIG['load_pages']);
	foreach($loads as $load){
		$getopts=array('-table'=>'_pages','-field'=>"body");
		if(isNum($load)){$getopts['-where']="_id={$load}";}
		else{$getopts['-where']="name = '{$load}'";}
		$ok=includeDBOnce($getopts);
		if(!isNum($ok) || $ok==0){abort("Load_Pages failed to load {$load} - {$ok}");}
	}
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function dbTuner($db='',$pargs=array()){
	if(!is_array($pargs) && strlen($pargs)){
		$pargs=array($pargs);
	}
	global $CONFIG;
	global $DATABASE;
	if(!strlen($db)){$db=$CONFIG['database'];}
	if(!isset($DATABASE[$db])){return "Invalid db:{$db}";}
	$db=$DATABASE[$db];
	$path=getWasqlPath('php/extras/databases');
	$cmd_args=array();
	switch(strtolower($db['dbtype'])){
		case 'mysql':
		case 'mysqli':
			$cmd_args[]="\"{$path}/mysqltuner.pl\"";
			$cmd_args[]='--noinfo';
			$cmd_args[]='--nocolor';
		break;
		case 'postgres':
		case 'postgresql':
			$cmd_args[]="\"{$path}/postgresqltuner.pl\"";
			$cmd_args[]='--nocolor';
		break;
		default:
			return "invalid db type";
		break;
	}
	foreach($pargs as $parg){
		$cmd_args[]=$parg;
	}
	if(isset($db['dbuser'])){
		$cmd_args[]="\"--user={$db['dbuser']}\"";
	}
	if(isset($db['dbpass'])){
		$cmd_args[]="\"--pass={$db['dbpass']}\"";
	}
	if(isset($db['dbhost'])){
		$cmd_args[]="\"--host={$db['dbhost']}\"";
	}
	$cmd_argstr=implode(' ',$cmd_args);
	$cmd="perl {$cmd_argstr}";
	$out=cmdResults($cmd);
	if(strlen($out['stderr'])){
		return printValue($out);
	}
	$lines=preg_split('/[\r\n]+/',$out['stdout']);
	array_unshift($lines,$cmd);
	foreach($lines as $i=>$line){
		if(stringBeginsWith($line,'[--]') || stringBeginsWith($line,'[INFO]')){
			$lines[$i]='<div style="color:#bfbfbf;">'.$line.'</div>';
		}
		elseif(stringBeginsWith($line,'[OK]')){
			$lines[$i]='<div style="color:#17bf17;">'.$line.'</div>';
		}
		elseif(stringBeginsWith($line,'[WARN]')){
			$lines[$i]='<div style="color:#e49b03;">'.$line.'</div>';
		}
		elseif(stringBeginsWith($line,'[!!]') || stringBeginsWith($line,'[BAD]')){
			$lines[$i]='<div style="color:#c60000;">'.$line.'</div>';
		}
		elseif(stringBeginsWith($line,'-------- Recommendations')){
			$lines[$i]='<div style="font-weight:bold;font-size:1.2rem;">'.$line.'</div>';
		}
		else{
			$lines[$i]='<div>'.$line.'</div>';
		}
	}
	return implode(PHP_EOL,$lines);
}
//---------- begin function dbISQL
/**
* @describe calls isql command line to execute query and returns results
* @param dsn string - dsn
* @param user string - username
* @param pass string - password
* @param query string - query
* @return recs array
* @usage $recs=dbISQL($dsn,$user,$pass,$query);
*/
function dbISQL($dsn,$user,$pass,$query){
	//isql prd3t1hana tableau T8bl3au123 -d| -c </var/www/wasql_stage/php/temp/q.sql
	$path=getWasqlPath('php/temp');
	$b64=base64_encode($dsn.$user.$query);
	$b64=str_replace('=','',$b64);
	$qfile="{$path}/{$b64}.sql";
	$ok=setFileContents($qfile,$query);
	$cmd="isql {$dsn} {$user} {$pass} -d'|' -c <'{$qfile}'";
	$out=cmdResults($cmd);
	unlink($qfile);
	$lines=preg_split('/[\r\n]/',$out['stdout']);
	$found=0;
	while($found==0 && count($lines)){
		if(stringBeginsWith($lines[0], 'SQL>')){
			$found=1;
		}
		array_shift($lines);
	}
	//remove the ending SQL> prompt
	$last=count($lines)-1;
	if(stringBeginsWith($lines[$last], 'SQL>')){
		array_pop($lines);
	}
	//lowercase the fields (first line)
	$lines[0]=strtolower($lines[0]);
	$recs=csv2Arrays($lines,array('separator'=>'|'));
	return $recs;
}
//---------- db functions that allow you to pass in what database first
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function dbFunctionCall($func,$db,$args1='',$args2='',$args3='',$args4=''){
	global $CONFIG;
	global $DATABASE;
	global $dbh_postgres;
	global $dbh_oracle;
	global $dbh_mssql;
	global $dbh_hana;
	global $dbh_odbc;
	global $dbh_snowflake;
	global $dbh_sqlite;
	global $dbh_ctree;
	global $dbh_msaccess;
	global $dbh_msexcel;
	global $dbh_mscsv;
	global $dbh_firebird;
	if(is_null($db)){
		return "Invalid  db: null";
	}
	$db=strtolower(trim($db));
	if(!isset($DATABASE[$db])){
		return "Invalid db: {$db}";
	}
	$CONFIG['db']=$db;
	switch(strtolower($DATABASE[$db]['dbtype'])){
		case 'postgresql':
		case 'postgres':
			loadExtras('postgresql');
			$dbh_postgres='';
			$func="postgresql".ucfirst($func);
		break;
		case 'oracle':
			loadExtras('oracle');
			$dbh_oracle='';
			$func="oracle".ucfirst($func);
		break;
		case 'mssql':
			loadExtras('mssql');
			$dbh_mssql='';
			$func="mssql".ucfirst($func);
		break;
		case 'hana':
			loadExtras('hana');
			$dbh_hana='';
			$func="hana".ucfirst($func);
		break;
		case 'odbc':
			loadExtras('odbc');
			$dbh_odbc='';
			$func="odbc".ucfirst($func);
		break;
		case 'snowflake':
			loadExtras('snowflake');
			$dbh_snowflake='';
			$func="snowflake".ucfirst($func);
		break;
		case 'sqlite':
			loadExtras('sqlite');
			$dbh_sqlite='';
			$func="sqlite".ucfirst($func);
		break;
		case 'gigya':
			loadExtras('gigya');
			$func="gigya".ucfirst($func);
		break;
		case 'splunk':
			loadExtras('splunk');
			$func="splunk".ucfirst($func);
		break;
		case 'ccv2':
			loadExtras('ccv2');
			$func="ccv2".ucfirst($func);
		break;
		case 'elastic':
			loadExtras('elastic');
			$func="elastic".ucfirst($func);
		break;
		case 'ctree':
			loadExtras('ctree');
			$dbh_ctree='';
			$func="ctree".ucfirst($func);
		break;
		case 'msaccess':
			loadExtras('msaccess');
			$dbh_msaccess='';
			$func="msaccess".ucfirst($func);
		break;
		case 'msexcel':
			loadExtras('msexcel');
			$dbh_msexcel='';
			$func="msexcel".ucfirst($func);
		break;
		case 'mscsv':
			loadExtras('mscsv');
			$dbh_mscsv='';
			$func="mscsv".ucfirst($func);
		break;
		case 'firebird':
			loadExtras('firebird');
			$dbh_firebird='';
			$func="firebird".ucfirst($func);
		break;
		default:
			loadExtras('mysql');
			$mysql_func="mysql".ucfirst($func);
			if(function_exists($mysql_func)){
				$func=$mysql_func;
			}
			else{$func=lcfirst($func);}
			//return executeSQL($sql);
		break;
	}
	if(!function_exists($func)){
		debugValue("Function '{$func}' does not exist");
		return 0;
	}
	elseif(!empty($args1) && !empty($args2) && !empty($args3) && !empty($args4)){
		return call_user_func($func,$args1,$args2,$args3,$args4);
	}
	elseif(!empty($args1) && !empty($args2) && !empty($args3)){
		return call_user_func($func,$args1,$args2,$args3);
	}
	elseif(!empty($args1) && !empty($args2)){
		return call_user_func($func,$args1,$args2);
	}
	elseif(!empty($args1)){
		return call_user_func($func,$args1);
	}
	else{
		return call_user_func($func);
	}
}

//---------- begin function dbAddIndex
/**
* @describe adds an index to a table
* @param db string - database name as specified in the database section of config.xml
* @param params array
*	-table
*	-fields
*	[-fulltext]
*	[-unique]
*	[-name] name of the index
* @return boolean
* @usage
*	$ok=dbAddIndex($db,array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=dbAddIndex($db,array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function dbAddIndex($db,$params=array()){
	return dbFunctionCall('addDBIndex',$db,$params);
}
//---------- begin function dbAddRecord
/**
* @describe adds a record
* @param db string - database name as specified in the database section of config.xml
* @param $params array
*   -table - name of the table to add to
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage $id=dbAddRecord($db,array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function dbAddRecord($db,$params=array()){
	return dbFunctionCall('addDBRecord',$db,$params);
}
//---------- begin function dbAddRecords
/**
* @describe adds a record
* @param db string - database name as specified in the database section of config.xml
* @param $table string - table name to add records to
* @param $params array - 
*	[-recs] - arrray of records to add
* 	[-csv] - path to csv file of records to add
* 	[-map] - array of key=>newkey mapping
* 	[-upsert]  string - comma separated list of fields to update if the record already exists.  
* 	[-upserton]  string - comma separated list of primary fields (needed for some databases and not others).
* @return integer returns the number of records added
* @usage $cnt=dbAddRecords($db,$table,array('-recs'=>$recs));
*/
function dbAddRecords($db,$table='',$params=array()){
	return dbFunctionCall('addDBRecords',$db,$table,$params);
}
//---------- begin function dbAddFields
/**
* @describe adds specified fields to specified table
* @param db string - database name as specified in the database section of config.xml
* @param table string - tablename
* @param params array - field=>dbtype pairs to add
* @return boolean
* @usage $ok=dbAddFields($db,'test',array('name'=>'varchar(25) NULL'));
*/
function dbAddFields($db,$table,$fields=array()){
	return dbFunctionCall('addDBFields',$db,$table,$fields);
}
//---------- begin function dbDropFields
/**
* @describe drops specified fields from specified table
* @param db string - database name as specified in the database section of config.xml
* @param table string - tablename
* @param params array - field1,field2,...
* @return boolean
* @usage $ok=dbDropFields($db,'test',array('name','age'));
*/
function dbDropFields($db,$table,$fields=array()){
	return dbFunctionCall('dropDBFields',$db,$table,$fields);
}
//---------- begin function dbAlterTable
/**
* @describe updates a table schema
* @param db string - database name as specified in the database section of config.xml
* @param table string - tablename
* @param params array - field=>dbtype pairs for this table. NOTE: Fields not specified will be dropped
* @return boolean
* @usage $ok=dbAlterTable($db,'test',array('name'=>'varchar(25) NULL'));
*/
function dbAlterTable($db,$table,$fields=array(),$maintain_order=1){
	return dbFunctionCall('alterDBTable',$db,$table,$fields,$maintain_order);
}
//---------- begin function dbConnect
/**
* @describe returns a handle to a database
* @param db string - database name as specified in the database section of config.xml
* @return array params
* @usage
*	$pgdbh=dbConnect('pg_local');
*/
function dbConnect($db,$params=array()){
	global $CONFIG;
	global $DATABASE;
	global $dbh_postgres;
	global $dbh_oracle;
	global $dbh_mssql;
	global $dbh_hana;
	global $dbh_odbc;
	global $dbh_snowflake;
	global $dbh_sqlite;
	global $dbh_ctree;
	global $dbh_msaccess;
	if(is_null($db)){
		return "Invalid  db: null";
	}
	$db=strtolower(trim($db));
	if(!isset($DATABASE[$db])){
		return "Invalid db: {$db}";
	}
	$CONFIG['db']=$db;
	switch(strtolower($DATABASE[$db]['dbtype'])){
		case 'mysql':
		case 'mysqli':
			loadExtras('mysql');
			try{
				return databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
			}
			catch(Exception $e){
				return false;
			}
		break;
		default:
			return dbFunctionCall('dbConnect',$db,$params);
		break;
	}
	return "Invalid dbtype: {$db['dbtype']}";
}
//---------- begin function dbCreateTable
/**
* @describe creates a table with the specified fields
* @param db string - database name as specified in the database section of config.xml
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage $ok=dbCreateTable($db,$table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function dbCreateTable($db,$table,$fields=array()){
	return dbFunctionCall('createDBTable',$db,$table,$fields);
}
//---------- begin function dbDelRecord
/**
* @describe deletes a record
* @param db string - database name as specified in the database section of config.xml
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
*	[-trigger] boolean - set to false to disable trigger functionality
* @return boolean
* @usage $id=dbDelRecord($db,array('-table'=> '_tabledata','-where'=>"_id=4"));
*/
function dbDelRecord($db,$params=array()){
	return dbFunctionCall('delDBRecord',$db,$params);
}
//---------- begin function dbDelRecordById
/**
* @describe deletes a record with said id in said table
* @param db string - database name as specified in the database section of config.xml
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=dbDelRecordById($db,'comments',7);
*/
function dbDelRecordById($db,$table='',$id=0){
	return dbFunctionCall('delDBRecordById',$db,$table,$id);
}
//---------- begin function dbDropIndex
/**
* @describe adds an index to a table
* @param db string - database name as specified in the database section of config.xml
* @param indexname string
* @param [tablename] string optional on some databases
* @return boolean
* @usage
*	$ok=dbDropIndex($db,$indexname,$tablename);
*/
function dbDropIndex($db,$indexname,$tablename=''){
	return dbFunctionCall('dropDBIndex',$db,$indexname,$tablename);
}
//---------- begin function dbDropTable
/**
* @describe drops a table
* @param db string - database name as specified in the database section of config.xml
* @param table string - name of table to drop
* @param [meta] boolean - also remove metadata in _fielddata and _tabledata tables associated with this table. defaults to true
* @return 1
* @usage $ok=dbDropTable($db,'comments',1);
*/
function dbDropTable($db,$table,$meta=1){
	return dbFunctionCall('dropDBTable',$db,$table,$meta);
}
//---------- begin function dbEditRecord
/**
* @describe edits a record
* @param db string - database name as specified in the database section of config.xml
* @param $params array - These can also be set in the CONFIG file with dbname_postgresql,dbuser_postgresql, and dbpass_postgresql
*   -table - name of the table to add to
*   -where - filter criteria
*	[-host] - postgresql server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage $id=dbEditRecord($db,array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function dbEditRecord($db,$params=array()){
	return dbFunctionCall('editDBRecord',$db,$params);
}
//---------- begin function dbEditRecordById
/**
* @describe edits a record with said id in said table
* @param db string - database name as specified in the database section of config.xml
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=dbEditRecordById($db,'comments',7,array('name'=>'bob'));
*/
function dbEditRecordById($db,$table='',$id=0,$params=array()){
	return dbFunctionCall('editDBRecordById',$db,$table,$id,$params);
}
//---------- begin function dbEnumQueryResults
/**
* @describe edits a record with said id in said table
* @param db string - database name as specified in the database section of config.xml
* @param result object - Query result from execute command
* @param params array - field=>value pairs to edit in this record
* @return recs array
* @usage $recs=dbEnumQueryResults($db,$data);
*/
function dbEnumQueryResults($db,$result,$params=array()){
	return dbFunctionCall('enumQueryResults',$db,$result,$params);
}
//---------- begin function dbExecuteSQL
/**
* @describe returns an records set from a database
* @param db string - database name as specified in the database section of config.xml
* @param sql string - query to execute

* @return array recordsets
* @usage
*	$recs=dbExecuteSQL('pg_local',"truncate table test");
*/
function dbExecuteSQL($db,$sql,$return_error=1){
	return dbFunctionCall('executeSQL',$db,$sql,$return_error);
}
//---------- begin function dbGetCount
/**
* @describe returns tables from a database
* @param db string - database name as specified in the database section of config.xml
* @return array params
* @usage
*	$recs=dbGetCount('pg_local',array('-table'=>'states','country'=>'US'));
*/
function dbGetCount($db,$params){
	return dbFunctionCall('getDBCount',$db,$params);
}
//---------- begin function dbGetProcedureText
/**
* @describe returns sql to create the specified table
* @param db string - database name as specified in the database section of config.xml
* @param table string - name of table to alter
* @return string
* @usage $createsql=dbGetProcedureText($db,$table);
*/
function dbGetProcedureText($db,$name,$type,$schema=''){
	return dbFunctionCall('getProcedureText',$db,$name,$type,$schema);
}
//---------- begin function dbGetDDL
/**
* @describe returns DDL to specified object type and name
* @param db string - database name as specified in the database section of config.xml
* @param type string - object type
* @param name string - object name
* @return string
* @usage $createsql=dbGetDDL($db,$table);
*/
function dbGetDDL($db,$table,$schema){
	return dbFunctionCall('getDDL',$db,$table,$schema);
}
//---------- begin function dbGetTableDDL
/**
* @describe returns sql to create the specified table
* @param db string - database name as specified in the database section of config.xml
* @param table string - name of table to alter
* @return string
* @usage $createsql=dbGetTableDDL($db,$table);
*/
function dbGetTableDDL($db,$table,$schema=''){
	return dbFunctionCall('getTableDDL',$db,$table,$schema);
}
//---------- begin function dbGetTableIndexes
/**
* @describe returns a list of indexes for the specified table
* @param db string - database name as specified in the database section of config.xml
* @param table string - name of table to alter
* @return array
* @usage $recs=dbGetTableIndexes($db,$table);
*/
function dbGetTableIndexes($db,$table,$schema=''){
	return dbFunctionCall('getDBTableIndexes',$db,$table,$schema);
}
//---------- begin function dbGetTablePrimaryKeys
/**
* @describe drops a table
* @param db string - database name as specified in the database section of config.xml
* @param table string - specified table
* @return array returns array of primary key fields
* @usage $fields=dbGetTablePrimaryKeys($db,$table);
*/
function dbGetTablePrimaryKeys($db,$table,$meta=1){
	return dbFunctionCall('getDBTablePrimaryKeys',$db,$table,$meta);
}
//---------- begin function dbGetRecord
/**
* @describe returns an records set from a database
* @param db string - database name as specified in the database section of config.xml
* @param params mixed - params or a SQL query

* @return array recordset
* @usage
*	$rec=dbGetRecord('pg_local',array('-table'=>'notes','_id'=>4545));
*/
function dbGetRecord($db,$params){
	$recs=dbGetRecords($db,$params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function dbGetRecordById
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param db string - database name as specified in the database section of config.xml
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=dbGetRecordById($db,'comments',7);
*/
function dbGetRecordById($db,$table='',$id=0,$relate=1,$fields=''){
	return dbFunctionCall('getDBRecordById',$db,$table,$id,$relate,$fields);
}
//---------- begin function dbNamedQuery
/**
* @describe returns a named query
* @param db string - database name as specified in the database section of config.xml
* @param name string
* @return string
* @usage
*	$query=dbNamedQuery($db,'functions');
*/
function dbNamedQuery($db,$name){
	return dbFunctionCall('namedQuery',$db,$name);
}

//---------- begin function dbGetAllProcedures
/**
* @describe returns all procedures
* @param db string - database name as specified in the database section of config.xml
* @return array
* @usage
*	$procs=dbGetAllProcedures($db);
*/
function dbGetAllProcedures($db){
	return dbFunctionCall('getAllProcedures',$db);
}
//---------- begin function dbGetAllTableFields
/**
* @describe returns fields of all tables with the table name as the index
* @param db string - database name as specified in the database section of config.xml
* @return array
* @usage
*	$tfields=dbGetAllTableFields('pg_local');
*/
function dbGetAllTableFields($db){
	return dbFunctionCall('getAllTableFields',$db);
}
//---------- begin function dbGetAllTableIndexes
/**
* @describe returns indexes of all tables with the table name as the index
* @param db string - database name as specified in the database section of config.xml
* @return array
* @usage
*	$tindexes=dbGetAllTableIndexes('pg_local');
*/
function dbGetAllTableIndexes($db,$schema=''){
	return dbFunctionCall('getAllTableIndexes',$db,$schema);
}
//---------- begin function dbGetAllTableConstraints
/**
* @describe returns constraints of all tables with the table name as the index
* @param db string - database name as specified in the database section of config.xml
* @return array
* @usage
*	$tindexes=dbGetAllTableConstraints('pg_local');
*/
function dbGetAllTableConstraints($db,$schema=''){
	return dbFunctionCall('getAllTableConstraints',$db,$schema);
}
//---------- begin function dbGetTableFields
/**
* @describe returns fields of specified table
* @param db string - database name as specified in the database section of config.xml
* @param table string - table (and possibly schema) name
* @return array recordsets
* @usage
*	$recs=dbGetTableFields('pg_local');
*/
//---------- begin function dbGetRecords
/**
* @describe returns an records set from a database
* @param db string - database name as specified in the database section of config.xml
* @param params mixed - params or a SQL query

* @return array recordsets
* @usage
*	$recs=dbGetRecords('pg_local',array('-table'=>'notes','-order'=>'_cdate','-limit'=>10));
*	$recs=dbGetRecords('pg_local','select * from postgres.notes order by _cdate limit 10')
*/
function dbGetRecords($db,$params){
	return dbFunctionCall('getDBRecords',$db,$params);
}
//---------- begin function dbGetAllProcedures
/**
* @describe returns fields of all tables with the table name as the index
* @param db string - database name as specified in the database section of config.xml
* @return array
* @usage
*	$procedures=dbGetAllProcedures('pg_local');
*/
function dbGetTableFields($db,$table){
	return dbFunctionCall('getDBFieldInfo',$db,$table);
}
//---------- begin function dbGetSchemas
/**
* @describe returns schemas from a database
* @param db string - database name as specified in the database section of config.xml
* @return array recordsets
* @usage
*	$recs=dbGetSchemas('pg_local');
*/
function dbGetTables($db){
	return dbFunctionCall('getDBTables',$db);
}
//---------- begin function dbGrep
/**
* @describe searches across tables for a specified value
* @param db string - database name as specified in the database section of config.xml
* @param search string
* @param tables array - optional. defaults to all tables except for _changelog,_cron_log, and _errors
* @return  array of arrays - tablename,_id,fieldname,search_count
* @usage $results=dbGrep($db,'searchstring');
*/
function dbGrep($db,$search,$tables=array()){
	return dbFunctionCall('grepDBTables',$db,$search,$tables);
}
//---------- begin function dbIsTable
/**
* @describe returns true if table already exists
* @param db string - database name as specified in the database section of config.xml
* @describe returns true if table already exists
* @param table string
* @return boolean
* @usage if(dbIsTable($db,'_users')){...}
*/
function dbIsTable($db,$table=''){
	return dbFunctionCall('isDBTable',$db,$table);
}

function dbSetLast($params=array()){
	global $DATABASE;
	if(!isset($DATABASE['_last_']['start'])){
		$DATABASE['_last_']=array(
			'start'=>microtime(true),
			'stop'=>0,
			'time'=>0,
			'error'=>'',
			'count'=>0,
			'function'=>'',
		);
	}
	foreach($params as $k=>$v){
		$DATABASE['_last_'][$k]=$v;
	}
}
//---------- begin function dbGetLast([key])
/**
* @describe returns the last query info: keys are start,stop,time,error,count,function,[p1],[p2],[p3],[p4]
* @param key string - key to return, otherwise it return the entire set
* @return mixed - key value or the entire set

* @return array recordset
* @usage
* 	$recs=dbQueryResult('mydb','select * from abc');
* 	if(strlen(dbGetLast('error'))){
* 		echo "Query Error: ".dbGetLast('error');
* 	}
* 	else{
*		echo "Query Time: ".dbGetLast('time');
* 	}
* 
* 	or
* 
* 	$last=dbGetLast();
* 	echo "Query Time: {$last['time']}";
*/
function dbGetLast($key=''){
	global $DATABASE;
	if(!isset($DATABASE['_last_'])){return '';}
	if(strlen($key)){
		if(!isset($DATABASE['_last_'][$key])){return '';}
		return $DATABASE['_last_'][$key];
	}
	return $DATABASE['_last_'];
}
//---------- begin function dbLastError
/**
* @describe returns the last database error
* @return string
* @usage
*	$err=dbLastError();
*/
function dbGetLastError(){
	return dbGetLast('error');
}
function dbGetLastQuery(){
	return dbGetLast('query');
}
function dbLastQuery(){
	return dbGetLast('query');
}
//---------- begin function dbListRecords
/**
* @describe returns an records set from a database
* @param db string - database name as specified in the database section of config.xml
* @param params mixed - params or a SQL query

* @return array recordset
* @usage
*	$rec=dbListRecords('pg_local',array('-table'=>'notes','_id'=>4545));
*/
function dbListRecords($db,$params){
	return dbFunctionCall('listRecords',$db,$params);
}
//---------- begin function dbOptimizations
/**
* @describe returns a list of items to consider when optimizing the db
* @param db string - database name as specified in the database section of config.xml
* @param params array - params
* @return array recordsets
* @usage
*	$recs=dbOptimizations('pg_local');
*/
function dbOptimizations($db,$params=array()){
	$recs=dbFunctionCall('optimizations',$db,$params);
	//check for single ref cursor that returns a table
	if(isset($recs[0]) && count(array_keys($recs[0]))==1){
		foreach($recs[0] as $k=>$v){
			if(is_array($v)){
				$recs=$v;
				break;
			}
		}
	}
	return $recs;
}
//---------- begin function dbQueryResults
/**
* @describe returns an records set from a database
* @param db string - database name as specified in the database section of config.xml
* @param query string - SQL query

* @return array recordsets
* @usage
*	$recs=dbQueryResults('pg_local',$query);
*	$recs=dbQueryResults('pg_local','select * from postgres.notes order by _cdate limit 10')
*/
function dbQueryResults($db,$query,$params=array()){
	//check for shortcuts
	if(preg_match('/^(fields|fld)\ (.+)$/is',$query,$m)){
		$parts=preg_split('/\ /',$m[2],2);
		$filter='';
		if(count($parts)==2){
			$table=$parts[0];
			$filter=$parts[1];
		}
		else{
			$table=$m[2];
		}
		$finfo=dbGetTableFields($db,$table);
		$recs=array();
		foreach($finfo as $k=>$info){
			$rec=array();
			//name
			if(isset($info['name'])){$rec['name']=$info['name'];}
			elseif(isset($info['_dbfield'])){$rec['name']=$info['_dbfield'];}
			else{$rec['name']='';}

			//type
			if(isset($info['_dbtype_ex'])){$rec['type']=$info['_dbtype_ex'];}
			elseif(isset($info['_dbtype'])){$rec['type']=$info['_dbtype'];}
			elseif(isset($info['type'])){$rec['type']=$info['type'];}
			else{$rec['type']='';}
			if(strlen($filter) && (!stringContains($rec['name'],$filter) && !stringContains($rec['type'],$filter))){continue;}
			if(isset($params['-index']) && isset($rec[$params['-index']])){
				$recs[$rec[$params['-index']]]=$rec;
			}
			else{
				$recs[]=$rec;
			}
		}
		return $recs;
	}
	elseif(preg_match('/^idx\ (.+)$/is',$query,$m)){
		$xrecs=dbGetTableIndexes($db,$m[1]);
		$recs=array();
		foreach($xrecs as $rec){
			if(isset($params['-index']) && isset($rec[$params['-index']])){
				$recs[$rec[$params['-index']]]=$rec;
			}
			else{
				$recs[]=$rec;
			}
		}
		return $recs;
	}
	elseif(preg_match('/^tables(.*)$/is',$query,$m)){
		$filter='';
		if(isset($m[1])){$filter=trim($m[1]);}
		$recs=array();
		$xrecs=dbGetTables($db);
		foreach($xrecs as $name){
			if(strlen($filter) && !stringContains($name,$filter)){continue;}
			$rec=array('name'=>$name);
			if(isset($params['-index']) && isset($rec[$params['-index']])){
				$recs[$rec[$params['-index']]]=$rec;
			}
			else{
				$recs[]=$rec;
			}
		}
		return $recs;
	}
	//call respective DB function
	$recs=dbFunctionCall('queryResults',$db,$query,$params);
	//check for single ref cursor that returns a table
	if(isset($recs[0]) && is_array($recs[0]) && count(array_keys($recs[0]))==1){
		foreach($recs[0] as $k=>$v){
			if(is_array($v)){
				$recs=$v;
				break;
			}
		}
	}
	return $recs;
}
//---------- begin function pyQueryResults
/**
* @describe returns an records set from a database using python
* @param db string - database name as specified in the database section of config.xml
* @param query string - SQL query

* @return array recordsets
* @usage
*	$recs=pyQueryResults('pg_local',$query);
*	$recs=pyQueryResults('pg_local','select * from postgres.notes order by _cdate limit 10')
*/
function pyQueryResults($db,$query,$params=array()){
	global $CONFIG;
	if(!isset($CONFIG['python'])){}
	//echo "commonSnowflakeQueryResults";exit;
	$sha=sha1($query);
	$path=getWasqlPath();
	$path=str_replace("\\",'/',$path);
	$path=preg_replace('/\/$/','',$path);
	$afile="{$path}/php/temp/{$sha}.sql";
	$ok=file_put_contents($afile, $query);
	$args=" -B \"{$path}/python/db2csv.py\" \"{$db}\" \"{$afile}\"";
	$out=cmdResults('python3',$args);
	//echo printValue($out);exit;
	if($out['rtncode'] != 0 || stringBeginsWith($out['stdout'],'Error')){
		echo "Failed to process<br>";
		echo printValue($out);
		exit;
	}
	unlink($afile);
	//return printValue($out);
	//unlink($afile);
	$csvfile=$out['stdout'];
	//success with spit out the csv file, otherwise err message
	if(!is_file($csvfile)){
		return $csvfile;
	}
	if(isset($params['-csv'])){
		return $csvfile;	
	}
	$recs=getCSVRecords($csvfile);
	unlink($csvfile);
	return $recs;
}
//---------- begin function databaseGradeSQL
/**
* @describe returns a SQL Grade for your query
* @param query string - SQL query
* @param htm int - if 1 then returns html bar instead of pcnt
* @return mixed - percent or html bar 
* @usage
*	$pcnt=databaseGradeSQL($query);
*	$htm=databaseGradeSQL($query,1);
*/
function databaseGradeSQL($sql,$htm=1){
	$words=preg_split('/[^a-zA-Z0-9\_\.]+/is',$sql);
	$ucwords=array(
		'ABS','ACOS','ADD_MONTHS','ALL','AND','AS','ASC','ASIN','ATAN','AVG',
		'BETWEEN','BY',
		'CASE','CAST','CEIL','CHAR_LENGTH','COALESCE','CONCAT','CONCAT_WS','COS','COT','COUNT','CURRENT_DATE','CURRENT_TIME',
		'DAYS_BETWEEN','DECODE','DEGREES','DESC','DISTINCT',
		'ELSE','END','EXP','EXTRACT',
		'FETCH','FIND_IN_SET',
		'FLOOR','FORMAT','FOUND_ROWS','FROM','FROM_DAYS','FROM_UNIXTIME',
		'GET_LOCK','GREATEST','GROUP',
		'HAVING','HEX','IF',
		'IFNULL','IN','INET_ATON','INET_NTOA','INITCAP','INNER','INSTR','INTERVAL','ISNULL',
		'JOIN',
		'LAG','LAST_DAY','LAST_INSERT_ID','LEAST','LEFT','LENGTH','LIKE','LIMIT','LN','LOCATE','LOG','LOWER','LPAD','LTRIM',
		'MAKE_SET','MATCH','MAX','MD','MICROSECOND','MID','MIN','MINUTE','MOD','MONTH','MONTHNAME','MONTHS_BETWEEN',
		'NEW_TIME','NEXT','NEXT_DAY','NOT','NOW','NULLIF','NUMTODSINTERVAL','NVL',
		'OCT','OFFSET','ON','ONLY','ORD','ORDER','OUTER','OVER',
		'PARTITION','PASSWORD','PI','POSITION','POW','POWER',
		'QUALIFY','QUARTER','QUOTE',
		'RADIANS','RAND','RELEASE_LOCK','REPEAT','REPLACE','REVERSE','RIGHT','ROUND','ROWS','ROW_COUNT','ROW_NUMBER','RPAD','RTRIM',
		'SECOND','SEC_TO_TIME','SELECT','SHA','SIGN','SIN','SOUNDEX','SPACE','SQRT','STRCMP','STR_TO_DATE','SUBDATE','SUBSTR','SUBSTRING','SUM','SYSDATE',
		'TAN','THEN','TIME','TIMESTAMPADD','TIMESTAMPDIFF','TOP','TO_CHAR','TO_DATE','TO_DAYS','TO_NUMBER','TO_SECONDS','TO_TIMESTAMP','TO_VARCHAR','TRANSLATE','TRIM','TRUNC','TRUNCATE',
		'UCASE','UNHEX','UNION','UNIX_TIMESTAMP','UPPER','USER',
		'VERSION',
		'WEEK','WEEKDAY','WHEN','WHERE','WITH',
		'YEAR'
	);
	$possible=0;
	$wrong=0;
	$wrong_words=array();
	$pcnt=0;
	for($i=0;$i<count($words);$i++){
		if(in_array(strtoupper($words[$i]),$ucwords)){
			//words in ucwords array should be upper case
			$possible+=1;
			if($words[$i] !=strtoupper($words[$i])){
				if(!in_array($words[$i],$wrong_words)){
					$wrong_words[]=$words[$i];
				}
				$wrong+=1;
			}
		}
		elseif(strtolower($words[$i]) != $words[$i]){
			//other words should be lower case
			$possible+=1;
			if(!in_array($words[$i],$wrong_words)){
				$wrong_words[]=$words[$i];
			}
			$wrong+=1;
		}
	}
	//console.log('posible: '+possible+', wrong:'+wrong);
	if($possible > 0){
		$pcnt=round((($possible-$wrong)/$possible)*100,0);
	}
	if($htm==0){return $pcnt;}
	$color='is-light';
	if($pcnt > 90){$color='is-success';}
	else if($pcnt > 70){$color='is-warning';}
	else{$color='is-danger';}
	$bar='';
	if($pcnt < 55){
		$bar.='<span style="margin-right:10px;font-size:1.2rem;" class="icon-emo-unhappy w_danger"></span>';
	}
	$bar.='<progress style="width:200px;height:14px;display:inline-flex;margin-bottom:0px;" class="show-value progress '+$color+'" value="'+$pcnt+'" max="100">'+$pcnt+'%</progress>';
	if($pcnt==100){
		$bar.='<span style="margin-left:10px;font-size:1.2rem;" class="icon-emo-happy w_success"></span>';
		$bar.='<span style="margin-left:2px;color:#1d5484;font-size:1.2rem;" class="icon-award-filled"></span>';
	}
	else{
		$bar.='	 <span data-tip="id:query_standards" data-tip_position="bottom" class="w_danger w_bold" style="margin-left:10px;">Fix These:</span> '+implode(', ',$wrong_words);
	}
	return $bar;
}
//---------- begin function databaseListRecords
/**
* @describe returns an html table of records
* @param params array - 
*  One of the following are required in order to generate the list of records to display
* 	[-list] array - getDBRecords array to use
* 	[-recs] array - getDBRecords array to use (same as -list)
* 	[-query] string - query to run to get getDBRecords array 
*	[-csv] file - csv file to load
*	[-table] string - table name.  Use this with other field/value params to filter the results
* 	[-css] string - appends custom css before the table in style tags
* The following parameters adjust the css of the table
*	[-tableheight] string - sets max height of table (i.e 80vh)
*	[-table{class|style|id|...}] string - sets specified attribute on table
*	[-thead{class|style|id|...}] string - sets specified attribute on thead
*	[-tbody{class|style|id|...}] string - sets specified attribute on tbody
* 
* The following parameters allow custom attributes
*	[-tr_{attr}] string - sets the tr attribute {attr}. %field% is replaced with the current field value
*	[-td_{attr}] string - sets the td attribute {attr}. %field% is replaced with the current field value
*	[-th_{attr}] string - sets the th attribute {attr}.
* 
* The following parameters set actions
*	[-tbody_onclick] - wraps the column name in an anchor with onclick. %field% is replaced with the current field. i.e "return pageSortByColumn('%field%');" 
*	[-tbody_href] - wraps the column name in an anchor with onclick. %field% is replaced with the current field. i.e "/mypage/sortby/%field%"
* 
* The following parameters adjust the display of the table
*	[-listfields] -  subset of fields to list from the list returned.
*	[-hidefields] - subset of fields to exclude
*   [-translate] - translate column names (displaynames)
*	[-anchormap] - str - field name to build an achormap from based on first letter or number of the value
*	[-sorting] - 1 - allow columns to be sorted
*	[-export] - 1 - show export option  
*	[-exportfields] -  subset of fields to export.
* 	[-export_displaynames] - 1 - use displaynames in csv header row
* 
* The following parameters adjust the form attributes
* 	[-action] string - set action of pagination form
* 	[-onsubmit] string - set onsubmit of pagination form. Defaults to pagingSubmit(this). set to pagingSubmit(this,'{divid}') when using ajax
* 
*	[-limit] mixed - query record limit
*	[-offset] mixed - query offset limit
*	[-ajaxid] str - ajax id to wrap the table in
*	[-sumfields] string or array - list of fields to sum
*	[-avgfields] string or array - list of fields to average
*	[-editfields] - in-cell edit fields.  * will make all cells editable
*	[-editfunction] - javascript edit function to call if -editfields is set. return indexEditField('%event_id%','%fieldname%');
*	[-editid] - id to assign to the table cell.  include %fieldname% if you want teh fieldname as part of the id. edit_%fieldname%_%event_id%
*	[{field}_eval] - php code to return based on current record values.  i.e "return setClassBasedOnAge('%age%');"
*	[{field}_onclick] - wrap in onclick anchor tag, replacing any %{field}% values   i.e "return pageShowThis('%age%');"
*	[{field}_href] - wrap in anchor tag, replacing any %{field}% values   i.e "/abc/def/%age%"
* 	[{field}_image] - create thumbnail image 
* 	[{field}_image_size] - sets thumnail image size - defaults to 28px
* 	[{field}_image_radius] - sets thumnail image borde radius - defaults to 18px
*	[{field}_checkbox] - 1 - adds a checkbox before the field value that holds the field value
*	[{field}_checkbox_onclick] - string - adds a onclick value if checkbox was specifid
*	[{field}_checkbox_id] - string - sets id of checkbox
*	[{field}_checkbox_value] - string - sets value of checkbox
*	[{field}_checkbox_checked] - string - checks the box if string equals checkbox id
*	[{field}_badge] - wraps the value in a span class="badge"
*	[{field}_badge_class] - add additional class value to badge
*	[{field}_append] - appends the specified value to the end
*	[{field}_prepend] - prepends the specified value to the end
*	[{field}_substr] - only returns a substring of the original value 
*	[{field}_phone] - formats value as a phone number
*	[{field}_verboseSize] - converts value to a verboseSize
*	[{field}_verboseTime] - converts value to a verboseTime
*	[{field}_verboseNumber] - converts value to a verboseNumber
*	[{field}_DateFormat] - converts value with said format to date(fmt,strtotime(value))
*	[{field}_ucwords] - uppercase each word in value
*	[{field}_strtoupper] - lowercase value
*	[{field}_strtolower] - uppercase value
*	[{field}_trim] - trim value
*	[{field}_rtrim] - right trim value
*	[{field}_ltrim] - left trim value
*	[{field}_number_format] - applies the number_format function to value passing this attribute value as the number of decimals 
*	[{field}_radio] - 1 - adds a radio button before the field value that holds the field value
*	[{field}_radio_onclick] - string - adds a onclick value if radio was specifid
*	[{field}_radio_id] - string - sets id of radio
*	[{field}_radio_value] - string - sets value of radio
*	[{field}_radio_checked] - string - checks the box if string equals radio id
*	[{field}_checkmark] - 1 - shows checkmark if value is 1
*	[{field}_checkmark_icon] - sets a custom icon class instead of icon-mark
*	[{field}_map] - array - shows mapped value instead.  i.e. array(0=>'',1=>'<span class="icon-mark"></span>')
*	[-database] - database type. oracle,hand,mssql,sqlite, or mysql.  defaults to mysql
*	[-results_eval] - function name to send the results to before displaying. The array of records will be sent to this function. It MUST return the array back
*	[-results_eval_params] - additonal params to send the the results_eval function as a second param
*	[-order] - string - comma separated list of fields to order the results by. Ignored if you are using -csv or -list
*	[-searchclass]  - sets the search form section class
*	[-search_select_class]  - sets the class for the select fields in the search form
*	[-search_input_class]  - sets the class for the input fields in the search form
*	[-search_button_class]  - sets the class for the button fields in the search form
*	[-quickfilters] - creates quickfilter button name=>string pairs where string is field oper val, semicolon separate pairs. Can also be an array with the following values: icon,name,filter,data-,onclick,class - or any other attribute you want to be included in the button
*	[-quickfilters_class] - set quickfilter button class
*	[-presearch]  - HTML/text content to add just before the search form
*	[-pretable]  - HTML/text content to add just before the table begins
*	[-posttable]  - HTML/text to add just after the table ends
*   [-prehead]  - HTML/text content to add before the TR header row - needs to be a valid <tr> HTML row
* 	[-posthead]  - HTML/text content to add after the TR header row - needs to be a valid <tr> HTML row
*	[-listview] - HTML/text to use instead of building a table row for each recordset.  Use [field] in your HTML to show value
*	[-hidesearch] integer - 1 hide search completely
*	[-simplesearch] str - search field - shows simple search bar - only supports one field
*	[-navonly] integer - 1=only show navigation buttons, not search
* @return string - html table to display
* @usage
*	databaseListRecords(array('-table'=>'notes'));
*	databaseListRecords(array('-list'=>$recs));
*	databaseListRecords("select * from myschema.mytable where ...");
*/
function databaseListRecords($params=array()){
	global $CONFIG;
	$info=array();
	$allfields=0;
	//check to see if params is a query
	if(!is_array($params) && (stringBeginsWith($params,"select ") || stringBeginsWith($params,"with "))){
		$params=array(
			'-query'=>$params,
			'-tableclass'=>'table striped bordered condensed',
			'-hidesearch'=>1
		);
	}
	//check for translate
	$translate=0;
	if(isset($params['-translate'])){$translate=1;}
	foreach($params as $k=>$v){
		if(preg_match('/\_translate$/i',$k)){$translate=1;break;}
	}
	if($translate==1){
		global $databaseCache;
		if(!isset($databaseCache['loadExtras']['translate'])){
			loadExtras('translate');
		}
	}
	//require -table or -list or -query
	if(isset($params['-db'])){
		$CONFIG['db']=$params['-db'];
	}
	//get -list
	if(isset($params['-list'])){
		$params['-hidesearch']=1;
		$allfields=1;
		$csv=arrays2csv($params['-list']);
		//add UTF-8 byte order mark to the beginning of the csv
		$csv="\xEF\xBB\xBF".$csv;
		$epath=getWasqlTempPath();
		$ename=sha1($csv).'.csv';
		$efile="{$epath}/".$ename;
		setFileContents($efile,$csv);
		$url='/php/index.php?-destroy=1&_pushfile='.encodeBase64($efile);
		if(isset($params['-export']) && $params['-export']==1){
			$pretable=<<<ENDOFPRETABLE
		<div style="display:flex;justify-content:flex-end;">
			<a href="{$url}" title="Export current results to CSV file" class="btn"><span class="icon-export w_bold"></span></a>
		</div>
ENDOFPRETABLE;
		}
		if(!isset($params['-pretable'])){
			$params['-pretable']=$pretable;
		}
		else{
			$params['-pretable']=$pretable.$params['-pretable'];
		}
		//check for -export and filter_export
		if(!empty($params['-export']) && !empty($params['-export_now']) && $params['-export_now']==1){
			//remove limit temporarily
			$fields=$params['-fields'];
			if(isset($params['-exportfields'])){
				//exportfields may have non-table fields - remove them as they are enriched later
				$exportfields=$params['-exportfields'];
				if(!is_array($exportfields)){$exportfields=preg_split('/\,/',$exportfields);}
			}
			$recs=$params['-list'];
			//echo printValue($recs);exit;
			//check for results_eval
			if(isset($params['-results_eval']) && function_exists($params['-results_eval'])){
				$rparams='';
				if(isset($params['-results_eval_params'])){
					$recs=call_user_func($params['-results_eval'],$recs,$params['-results_eval_params']);
				}
				else{
					$recs=call_user_func($params['-results_eval'],$recs);
				}
			}
			//echo printValue($recs);exit;
			if(is_array($exportfields)){
				//only exportfields
				$xrecs=array();
				foreach($recs as $i=>$rec){
					$xrec=array();
					foreach($exportfields as $efld){
						$xrec[$efld]=$rec[$efld];
					}
					$xrecs[]=$xrec;
					unset($recs[$i]);
				}
				$recs=$xrecs;
				unset($xrecs);
			}
			//echo printValue($recs);exit;
			//strip tags
			foreach($recs as $i=>$rec){
				foreach($rec as $k=>$v){
					$recs[$i][$k]=strip_tags($v);
				}
			}
			//create a csv file
			if(isset($params['-export_displaynames'])){
				$csv=arrays2csv($recs,$params);
			}
			else{
				$csv=arrays2csv($recs);
			}
			//add UTF-8 byte order mark to the beginning of the csv
			$csv="\xEF\xBB\xBF".$csv;
			$epath=getWasqlTempPath();
			$ename=sha1($csv).'.csv';
			$efile="{$epath}/".$ename;
			setFileContents($efile,$csv);
			//clean up any csv files in this folder older than 1 hour
			$ok=cleanupDirectory($epath,1,'hours','csv');
			return $efile;
		}
	}
	elseif(isset($params['-csv'])){
		$allfields=1;
		$params['-list']=$recs=getCSVRecords($params['-csv']);
		$params['-hidesearch']=1;
		//strip tags
		foreach($recs as $i=>$rec){
			foreach($rec as $k=>$v){
				$recs[$i][$k]=encodeHtml($v);
			}
		}
		//echo printValue($params);exit;
	}
	elseif(isset($params['-recs'])){
		$allfields=1;
		$params['-list']=$recs=$params['-recs'];
		$params['-hidesearch']=1;
		//strip tags
		foreach($recs as $i=>$rec){
			foreach($rec as $k=>$v){
				$recs[$i][$k]=encodeHtml($v);
			}
		}
	}
	elseif(isset($params['-query'])){
		switch(strtolower($params['-database'])){
			case 'ctree':
				if(!function_exists('ctreeQueryResults')){
					loadExtras('ctree');
				}
				$params['-list']=ctreeQueryResults($params['-query']);
			break;
			case 'firebird':
				if(!function_exists('firebirdQueryResults')){
					loadExtras('firebird');
				}
				$params['-list']=firebirdQueryResults($params['-query']);
			break;
			case 'hana':
				if(!function_exists('hanaQueryResults')){
					loadExtras('hana');
				}
				$params['-list']=hanaQueryResults($params['-query']);
			break;
			case 'msaccess':
				if(!function_exists('msaccessQueryResults')){
					loadExtras('msaccess');
				}
				$params['-list']=msaccessQueryResults($params['-query']);
			break;
			case 'mscsv':
				if(!function_exists('mscsvQueryResults')){
					loadExtras('mscsv');
				}
				$params['-list']=mscsvQueryResults($params['-query']);
			break;
			case 'msexcel':
				if(!function_exists('msexcelQueryResults')){
					loadExtras('msexcel');
				}
				$params['-list']=msexcelQueryResults($params['-query']);
			break;
			case 'mssql':
				if(!function_exists('mssqlQueryResults')){
					loadExtras('mssql');
				}
				$params['-list']=mssqlQueryResults($params['-query']);
			break;
			case 'mysql':
				if(!function_exists('mysqlQueryResults')){
					loadExtras('mysql');
				}
				$params['-list']=mysqlQueryResults($params['-query']);
			break;
			case 'odbc':
				if(!function_exists('odbcQueryResults')){
					loadExtras('odbc');
				}
				$params['-list']=odbcQueryResults($params['-query']);
			break;
			case 'oracle':
				if(!function_exists('oracleQueryResults')){
					loadExtras('oracle');
				}
				$params['-list']=oracleQueryResults($params['-query']);
			break;
			case 'postgresql':
				if(!function_exists('postgresqlQueryResults')){
					loadExtras('postgresql');
				}
				$params['-list']=postgresqlQueryResults($params['-query']);
			break;
			case 'snowflake':
				if(!function_exists('snowflakeQueryResults')){
					loadExtras('snowflake');
				}
				$params['-list']=snowflakeQueryResults($params['-query']);
			break;
			case 'sqlite':
				if(!function_exists('sqliteQueryResults')){
					loadExtras('sqlite');
				}
				$params['-list']=sqliteQueryResults($params['-query']);
			break;
			case 'gigya':
				if(!function_exists('gigyaQueryResults')){
					loadExtras('gigya');
				}
				$params['-list']=gigyaQueryResults($params['-query']);
			break;
			case 'splunk':
				if(!function_exists('splunkQueryResults')){
					loadExtras('splunk');
				}
				$params['-list']=splunkQueryResults($params['-query'],$params);
			break;
			case 'ccv2':
				if(!function_exists('ccv2QueryResults')){
					loadExtras('ccv2');
				}
				$params['-list']=ccv2QueryResults($params['-query']);
			break;
			case 'elastic':
				if(!function_exists('elasticQueryResults')){
					loadExtras('elastic');
				}
				$params['-list']=elasticQueryResults($params['-query']);
			break;
			default:
				$params['-list']=getDBRecords($params['-query']);
			break;
		}
		unset($params['-query']);
	}
	elseif(isset($params['-table'])){
		//get the list from the table. First lets get the table fields
		if(!isset($params['-database'])){$params['-database']='';}
		switch(strtolower($params['-database'])){
			case 'ctree':
				if(!function_exists('ctreeGetDBFieldInfo')){
					loadExtras('ctree');
				}
				$info=ctreeGetDBFieldInfo($params['-table']);
			break;
			case 'firebird':
				if(!function_exists('firebirdGetDBFieldInfo')){
					loadExtras('firebird');
				}
				$info=firebirdGetDBFieldInfo($params['-table']);
			break;
			case 'hana':
				if(!function_exists('hanaGetDBFieldInfo')){
					loadExtras('hana');
				}
				$info=hanaGetDBFieldInfo($params['-table']);
			break;
			case 'msaccess':
				if(!function_exists('msaccessGetDBFieldInfo')){
					loadExtras('msaccess');
				}
				$info=msaccessGetDBFieldInfo($params['-table']);
			break;
			case 'mscsv':
				if(!function_exists('mscsvGetDBFieldInfo')){
					loadExtras('mscsv');
				}
				$info=mscsvGetDBFieldInfo($params['-table']);
			break;
			case 'msexcel':
				if(!function_exists('msexcelGetDBFieldInfo')){
					loadExtras('msexcel');
				}
				$info=msexcelGetDBFieldInfo($params['-table']);
			break;
			case 'mssql':
				if(!function_exists('mssqlGetDBFieldInfo')){
					loadExtras('mssql');
				}
				$info=mssqlGetDBFieldInfo($params['-table']);
			break;
			case 'mysql':
				if(!function_exists('mysqlGetDBFieldInfo')){
					loadExtras('mysql');
				}
				$info=mysqlGetDBFieldInfo($params['-table']);
			break;
			case 'odbc':
				if(!function_exists('odbcGetDBFieldInfo')){
					loadExtras('odbc');
				}
				$info=odbcGetDBFieldInfo($params['-table']);
			break;
			case 'oracle':
				if(!function_exists('oracleGetDBFieldInfo')){
					loadExtras('oracle');
				}
				$info=oracleGetDBFieldInfo($params['-table']);
			break;
			case 'postgresql':
				if(!function_exists('postgresqlGetDBFieldInfo')){
					loadExtras('postgresql');
				}
				$info=postgresqlGetDBFieldInfo($params['-table']);
			break;
			case 'snowflake':
				if(!function_exists('snowflakeGetDBFieldInfo')){
					loadExtras('snowflake');
				}
				$info=snowflakeGetDBFieldInfo($params['-table']);
			break;
			case 'sqlite':
				if(!function_exists('sqliteGetDBFieldInfo')){
					loadExtras('sqlite');
				}
				$info=sqliteGetDBFieldInfo($params['-table']);
			break;
			case 'gigya':
				if(!function_exists('gigyaGetDBFieldInfo')){
					loadExtras('gigya');
				}
				$info=gigyaGetDBFieldInfo($params['-table']);
			break;
			case 'splunk':
				if(!function_exists('splunkGetDBFieldInfo')){
					loadExtras('splunk');
				}
				$info=splunkGetDBFieldInfo($params['-table']);
			break;
			case 'ccv2':
				if(!function_exists('ccv2GetDBFieldInfo')){
					loadExtras('ccv2');
				}
				$info=ccv2GetDBFieldInfo($params['-table']);
			break;
			case 'elastic':
				if(!function_exists('elasticGetDBFieldInfo')){
					loadExtras('elastic');
				}
				$info=elasticGetDBFieldInfo($params['-table']);
			break;
			default:
				$info=getDBFieldInfo($params['-table']);
			break;
		}
		if(!is_array($info) || !count($info)){
			$rtn='';
			if(isset($params['-predata'])){
				$rtn .= $params['-predata'];
			}
			elseif(isset($params['-pretable'])){
				$rtn .= $params['-pretable'];
			}
			$rtn .= "databaseListRecords error: No fields found for {$params['-table']}";
			return $rtn;
		}
		$params['-info']=$info;
		//look for _filters
		if(isset($_REQUEST['_filters'])){
			$params['-filters']=preg_split('/[\r\n\;]/',$_REQUEST['_filters']);					
		}
		if(isset($_REQUEST['filter_order']) && strlen($_REQUEST['filter_order'])){
			$params['-order']=$_REQUEST['filter_order'];					
		}
		//check for -filters
		if(!empty($params['-filters'])){
			$wheres=databaseParseFilters($params);	
			if(!empty($params['-where'])){
				$wheres[]="({$params['-where']})";
			}
			if(count($wheres)){
				$params['-where']=implode(' and ',$wheres);
			}
		}
		$params['-forceheader']=1;
		//check for -bulkedit and filter_bulkedit before running query
		if(!empty($params['-bulkedit']) && !empty($_REQUEST['filter_bulkedit']) && $_REQUEST['filter_bulkedit']==1){
			$bulk=array('-table'=>$params['-table']);
			if(!empty($params['-where'])){$bulk['-where']=$params['-where'];}
			else{$bulk['-where']='1=1';}
			$bulk[$_REQUEST['filter_field']]=$_REQUEST['filter_value'];
			switch(strtolower($params['-database'])){
				case 'ctree':
					if(!function_exists('ctreeEditDBRecord')){
						loadExtras('ctree');
					}
					$ok=ctreeEditDBRecord($bulk);
				break;
				case 'firebird':
					if(!function_exists('firebirdEditDBRecord')){
						loadExtras('firebird');
					}
					$ok=firebirdEditDBRecord($bulk);
				break;
				case 'hana':
					if(!function_exists('hanaEditDBRecord')){
						loadExtras('hana');
					}
					$ok=hanaEditDBRecord($bulk);
				break;
				case 'msaccess':
					if(!function_exists('msaccessEditDBRecord')){
						loadExtras('msaccess');
					}
					$ok=msaccessEditDBRecord($bulk);
				break;
				case 'mscsv':
					if(!function_exists('mscsvEditDBRecord')){
						loadExtras('mscsv');
					}
					$ok=mscsvEditDBRecord($bulk);
				break;
				case 'msexcel':
					if(!function_exists('msexcelEditDBRecord')){
						loadExtras('msexcel');
					}
					$ok=msexcelEditDBRecord($bulk);
				break;
				case 'mssql':
					if(!function_exists('mssqlEditDBRecord')){
						loadExtras('mssql');
					}
					$ok=mssqlEditDBRecord($bulk);
				break;
				case 'mysql':
					if(!function_exists('mysqlEditDBRecord')){
						loadExtras('mysql');
					}
					$ok=mysqlEditDBRecord($bulk);
				break;
				case 'odbc':
					if(!function_exists('odbcEditDBRecord')){
						loadExtras('odbc');
					}
					$ok=odbcEditDBRecord($bulk);
				break;
				case 'oracle':
					if(!function_exists('oracleEditDBRecord')){
						loadExtras('oracle');
					}
					$ok=oracleEditDBRecord($bulk);
				break;
				case 'postgresql':
					if(!function_exists('postgresqlEditDBRecord')){
						loadExtras('postgresql');
					}
					$ok=postgresqlEditDBRecord($bulk);
				break;
				case 'snowflake':
					if(!function_exists('snowflakeEditDBRecord')){
						loadExtras('snowflake');
					}
					$ok=snowflakeEditDBRecord($bulk);
				break;
				case 'sqlite':
					if(!function_exists('sqliteEditDBRecord')){
						loadExtras('sqlite');
					}
					$ok=sqliteEditDBRecord($bulk);
				break;
				default:
					$ok=editDBRecord($bulk);
				break;
			}
		}
		//limit
		if(!isset($params['-limit'])){
			if(isset($CONFIG['paging']) && isNum($CONFIG['paging'])){
				$params['-limit']=$CONFIG['paging'];
			}
			else{
				$params['-limit']=15;
			}
		}
		//check for -export and filter_export
		if(isset($params['-export']) && isset($params['-export_now']) && $params['-export_now']==1){
			//remove limit temporarily
			$limit=$params['-limit'];
			$fields=isset($params['-fields'])?$params['-fields']:'';
			if(isset($params['-exportfields'])){
				//exportfields may have non-table fields - remove them as they are enriched later
				$params['-listfields']=$params['-exportfields'];
				$exportfields=$params['-exportfields'];
				if(!is_array($exportfields)){$exportfields=preg_split('/\,/',$exportfields);}
				$exportfields_ori=$exportfields;
				foreach($exportfields as $x=>$exportfield){
					if(!isset($info[$exportfield])){
						unset($exportfields[$x]);
					}
				}
				$params['-fields']=$exportfields;
				$exportfields=$exportfields_ori;
			}
			$params['-limit']=$params['-total'];
			if(!isNum($params['-limit'])){
				//set limit 1,000,000 if not specified.
				$params['-limit']=1000000;
			}
			//run query to get records for export
			switch(strtolower($params['-database'])){
				case 'ctree':
					if(!function_exists('ctreeGetDBRecords')){
						loadExtras('ctree');
					}
					$recs=ctreeGetDBRecords($params);
				break;
				case 'firebird':
					if(!function_exists('firebirdGetDBRecords')){
						loadExtras('firebird');
					}
					$recs=firebirdGetDBRecords($params);
				break;
				case 'hana':
					if(!function_exists('hanaGetDBRecords')){
						loadExtras('hana');
					}
					$recs=hanaGetDBRecords($params);
				break;
				case 'msaccess':
					if(!function_exists('msaccessGetDBRecords')){
						loadExtras('msaccess');
					}
					$recs=msaccessGetDBRecords($params);
				break;
				case 'mscsv':
					if(!function_exists('mscsvGetDBRecords')){
						loadExtras('mscsv');
					}
					$recs=mscsvGetDBRecords($params);
				break;
				case 'msexcel':
					if(!function_exists('msexcelGetDBRecords')){
						loadExtras('msexcel');
					}
					$recs=msexcelGetDBRecords($params);
				break;
				case 'mssql':
					if(!function_exists('mssqlGetDBRecords')){
						loadExtras('mssql');
					}
					$recs=mssqlGetDBRecords($params);
				break;
				case 'mysql':
					if(!function_exists('mysqlGetDBRecords')){
						loadExtras('mysql');
					}
					$recs=mysqlGetDBRecords($params);
				break;
				case 'odbc':
					if(!function_exists('odbcGetDBRecords')){
						loadExtras('odbc');
					}
					$recs=odbcGetDBRecords($params);
				break;
				case 'oracle':
					if(!function_exists('oracleQueryResults')){
						loadExtras('oracle');
					}
					$recs=oracleGetDBRecords($params);
				break;
				case 'postgresql':
					if(!function_exists('postgresqlGetDBRecords')){
						loadExtras('postgresql');
					}
					$recs=postgresqlGetDBRecords($params);
				break;
				case 'snowflake':
					if(!function_exists('snowflakeGetDBRecords')){
						loadExtras('snowflake');
					}
					$recs=snowflakeGetDBRecords($params);
				break;
				case 'sqlite':
					if(!function_exists('sqliteGetDBRecords')){
						loadExtras('sqlite');
					}
					$recs=sqliteGetDBRecords($params);
				break;
				default:
					$recs=getDBRecords($params);
				break;
			}
			//check for results_eval
			if(isset($params['-results_eval']) && function_exists($params['-results_eval'])){
				$rparams='';
				if(isset($params['-results_eval_params'])){
					$recs=call_user_func($params['-results_eval'],$recs,$params['-results_eval_params']);
				}
				else{
					$recs=call_user_func($params['-results_eval'],$recs);
				}
			}
			if(isset($exportfields) && is_array($exportfields)){
				//only exportfields
				$xrecs=array();
				foreach($recs as $i=>$rec){
					$xrec=array();
					foreach($exportfields as $efld){
						$xrec[$efld]=$rec[$efld];
					}
					$xrecs[]=$xrec;
					unset($recs[$i]);
				}
				$recs=$xrecs;
				unset($xrecs);
			}
			//strip tags
			foreach($recs as $i=>$rec){
				foreach($rec as $k=>$v){
					if(is_null($v)){$v='';}
					$recs[$i][$k]=strip_tags($v);
				}
			}
			
			//set limit back
			$params['-limit']=$limit;
			$params['-fields']=$fields;
			//create a csv file
			if(isset($params['-export_displaynames'])){
				$csv=arrays2csv($recs,$params);
			}
			else{
				$csv=arrays2csv($recs);
			}
			//add UTF-8 byte order mark to the beginning of the csv
			$csv="\xEF\xBB\xBF".$csv;
			$epath=getWasqlTempPath();
			$ename=sha1($csv).'.csv';
			$efile="{$epath}/".$ename;
			setFileContents($efile,$csv);
			//$params['-export_file']="/php/temp/{$ename}";
			//clean up any csv files in this folder older than 1 hour
			$ok=cleanupDirectory($epath,1,'hours','csv');
			return $efile;
		}
		//get total number of records
		if(empty($params['-total'])){
			switch(strtolower($params['-database'])){
				case 'ctree':
					if(!function_exists('ctreeGetDBCount')){
						loadExtras('ctree');
					}
					$params['-total']=ctreeGetDBCount($params);
				break;
				case 'firebird':
					if(!function_exists('firebirdGetDBCount')){
						loadExtras('firebird');
					}
					$params['-total']=firebirdGetDBCount($params);
				break;
				case 'hana':
					if(!function_exists('hanaGetDBCount')){
						loadExtras('hana');
					}
					$params['-total']=hanaGetDBCount($params);
				break;
				case 'msaccess':
					if(!function_exists('msaccessGetDBCount')){
						loadExtras('msaccess');
					}
					$params['-total']=msaccessGetDBCount($params);
				break;
				case 'mscsv':
					if(!function_exists('mscsvGetDBCount')){
						loadExtras('mscsv');
					}
					$params['-total']=mscsvGetDBCount($params);
				break;
				case 'msexcel':
					if(!function_exists('msexcelGetDBCount')){
						loadExtras('msexcel');
					}
					$params['-total']=msexcelGetDBCount($params);
				break;
				case 'mssql':
					if(!function_exists('mssqlGetDBCount')){
						loadExtras('mssql');
					}
					$params['-total']=mssqlGetDBCount($params);
				break;
				case 'mysql':
				case 'mysqli':
					if(!function_exists('mysqlGetDBCount')){
						loadExtras('mysql');
					}
					$params['-total']=mysqlGetDBCount($params);
				break;
				case 'odbc':
					if(!function_exists('odbcGetDBCount')){
						loadExtras('odbc');
					}
					$params['-total']=odbcGetDBCount($params);
				break;
				case 'oracle':
					if(!function_exists('oracleQueryResults')){
						loadExtras('oracle');
					}
					$params['-total']=oracleGetDBCount($params);
				break;
				case 'postgresql':
					if(!function_exists('postgresqlGetDBCount')){
						loadExtras('postgresql');
					}
					$params['-total']=postgresqlGetDBCount($params);
				break;
				case 'snowflake':
					if(!function_exists('snowflakeGetDBCount')){
						loadExtras('snowflake');
					}
					$params['-total']=snowflakeGetDBCount($params);
				break;
				case 'sqlite':
					if(!function_exists('sqliteGetDBCount')){
						loadExtras('sqlite');
					}
					$params['-total']=sqliteGetDBCount($params);
				break;
				default:
					$params['-total']=getDBCount($params);
				break;
			}
		}
		//check for filter_offset
		if(!isset($params['-offset']) && !empty($_REQUEST['filter_offset'])){
			$params['-offset']=$_REQUEST['filter_offset'];
		}
		//get the list of records to display
		switch(strtolower($params['-database'])){
			case 'ctree':
				if(!function_exists('ctreeQueryResults')){
					loadExtras('ctree');
				}
				$params['-list']=ctreeGetDBRecords($params);
			break;
			case 'firebird':
				if(!function_exists('firebirdQueryResults')){
					loadExtras('firebird');
				}
				$params['-list']=firebirdGetDBRecords($params);
			break;
			case 'hana':
				if(!function_exists('hanaGetDBRecords')){
					loadExtras('hana');
				}
				$params['-list']=hanaGetDBRecords($params);
			break;
			case 'msaccess':
				if(!function_exists('msaccessGetDBRecords')){
					loadExtras('msaccess');
				}
				$params['-list']=msaccessGetDBRecords($params);
			break;
			case 'mscsv':
				if(!function_exists('mscsvGetDBRecords')){
					loadExtras('mscsv');
				}
				$params['-list']=mscsvGetDBRecords($params);
			break;
			case 'msexcel':
				if(!function_exists('msexcelGetDBRecords')){
					loadExtras('msexcel');
				}
				$params['-list']=msexcelGetDBRecords($params);
			break;
			case 'mssql':
				if(!function_exists('mssqlGetDBRecords')){
					loadExtras('mssql');
				}
				$params['-list']=mssqlGetDBRecords($params);
			break;
			case 'mysql':
				if(!function_exists('mysqlGetDBRecords')){
					loadExtras('mysql');
				}
				$params['-list']=mysqlGetDBRecords($params);
			break;
			case 'odbc':
				if(!function_exists('odbcGetDBRecords')){
					loadExtras('odbc');
				}
				$params['-list']=odbcGetDBRecords($params);
			break;
			case 'oracle':
				if(!function_exists('oracleQueryResults')){
					loadExtras('oracle');
				}
				$params['-list']=oracleGetDBRecords($params);
			break;
			case 'postgresql':
				if(!function_exists('postgresqlGetDBRecords')){
					loadExtras('postgresql');
				}
				$params['-list']=postgresqlGetDBRecords($params);
			break;
			case 'snowflake':
				if(!function_exists('snowflakeGetDBRecords')){
					loadExtras('snowflake');
				}
				$params['-list']=snowflakeGetDBRecords($params);
			break;
			case 'sqlite':
				if(!function_exists('sqliteGetDBRecords')){
					loadExtras('sqlite');
				}
				$params['-list']=sqliteGetDBRecords($params);
			break;
			default:
				//echo printValue($params);exit;
				$params['-list']=getDBRecords($params);
			break;
		}
		if(!is_array($params['-list']) && empty($params['-listfields'])){
			$params['-listfields']=array();
			foreach($info as $field=>$finfo){
				if(isWasqlField($field) && $field != '_id'){continue;}
				$params['-listfields'][]=$field;
			}
		}
	}
	//verify we have records in $params['-list']
	if(!is_array($params['-list']) && strlen($params['-list'])){
		$rtn='';
		if(isset($params['-predata'])){
			$rtn .= $params['-predata'];
		}
		elseif(isset($params['-pretable'])){
			$rtn .= $params['-pretable'];
		}
		$rtn.="databaseListRecords error: ".$params['-list'];
		return $rtn;
	}
	if(!isset($params['-listfields']) && isset($params['-fields'])){
		$params['-listfields']=array_keys($params['-list'][0]);
	}
	//determine -listfields
	
	if(!empty($params['-listfields'])){
		if(!is_array($params['-listfields'])){
			$params['-listfields']=str_replace(' ','',$params['-listfields']);
			$params['-listfields']=preg_split('/\,/',$params['-listfields']);
		}
	}
	if(empty($params['-listfields'])){
		//get fields from -list
		$params['-listfields']=array();
		foreach($params['-list'] as $rec){
			foreach($rec as $k=>$v){
				if($allfields==0 && isWasqlField($k) && $k != '_id'){continue;}
				$params['-listfields'][]=$k;
			}
			break;
		}
	}
	//-hidefields
	if(isset($params['-hidefields'])){
		if(!is_array($params['-hidefields'])){
			$params['-hidefields']=str_replace(' ','',$params['-hidefields']);
			$params['-hidefields']=preg_split('/\,/',$params['-hidefields']);
		}
		foreach($params['-listfields'] as $s=>$fld){
			if(in_array($fld,$params['-hidefields'])){
				unset($params['-listfields'][$s]);
			}
		}
	}
	$rtn='';
	if(isset($params['-css'])){
		$rtn .= '<style>'.$params['-css'].'</style>';
	}
	if(isset($params['-presearch'])){
		$rtn .= '<div class="dblistrecords_presearch">'.$params['-presearch'].'</div>';
	}
	if(isset($params['-ajaxid']) && strlen($params['-ajaxid'])){
		$rtn .= '<div id="'.$params['-ajaxid'].'">'.PHP_EOL;
	}
	//Check for -total to determine if we should show the searchFilterForm
	if(empty($params['-formname'])){
		$params['-formname']='searchfiltersform';
	}
	if(!isset($params['-hidesearch']) || $params['-hidesearch']==0){
		if(empty($params['-searchfields'])){
			$params['-searchfields']=array();
			foreach($params['-listfields'] as $field){
				$params['-searchfields'][]=$field;
			}
		}
		$rtn .= '<div class="dblistrecords_search">'.PHP_EOL;
		$rtn .= commonSearchFiltersForm($params);
		$rtn .= '</div>'.PHP_EOL;
	}
	$rtn .= '<div class="dblistrecords_list">'.PHP_EOL;
	//check for -anchormap
	$anchor_values=array();
	if(isset($params['-anchormap'])){
		$afield=$params['-anchormap'];
		foreach($params['-list'] as $rec){
			if(!isset($rec[$afield]) || !strlen($rec[$afield])){continue;}
			if(isset($params['-anchormap_full'])){
				$ch=trim(removeHtml($rec[$afield]));	
			}
			else{
				$ch=strtoupper(substr(trim(removeHtml($rec[$afield])),0,1));
			}
			if(!strlen($ch) || $ch=='-'){continue;}
			if(!in_array($ch,$anchor_values)){
				$anchor_values[]=$ch;
			}
		}
		if(count($anchor_values)){
			sort($anchor_values);
			$rtn .= '<div style="display: flex;flex-direction: row;align-items:center;justify-content: space-between;padding:3px 10px;border:1px solid #ccc;">'.PHP_EOL;
			if(!empty($params[$afield."_displayname"])){
				$name=$params[$afield."_displayname"];
			}
			else{
				$name=ucwords(trim(str_replace('_',' ',$afield)));
			}
			$rtn .= '<span class="w_bold">'.$name.': </span>';
			foreach($anchor_values as $v){
				$rtn .= '	<a class="w_link w_gray" href="#anchormap_'.$v.'">'.$v.'</a>'.PHP_EOL;
			}
			$rtn .= '</div>'.PHP_EOL;
		}
	}
	//check for pretable content
	if(isset($params['-predata'])){
		$rtn .= $params['-predata'];
	}
	elseif(isset($params['-pretable'])){
		$rtn .= $params['-pretable'];
	}
	//check for listview
	if(isset($params['-listview'])){
		//view name or content?
		if($params['-listview'] == strip_tags($params['-listview'])){
			$params['-listview']=getView($params['-listview']);
		}
		if(!is_array($params['-list']) || !count($params['-list'])){
			$params['-list']=array();
		}
		//check for results_eval
		if(isset($params['-results_eval']) && function_exists($params['-results_eval'])){
			$rparams='';
			if(isset($params['-results_eval_params'])){
				$params['-list']=call_user_func($params['-results_eval'],$params['-list'],$params['-results_eval_params']);
			}
			else{
				$params['-list']=call_user_func($params['-results_eval'],$params['-list']);
			}
		}
		//loop through each row 
		foreach($params['-list'] as $rec){
			$crow=$params['-listview'];	
			//return printValue($rec);
			foreach($rec as $cfield=>$cvalue){
				if(is_array($cvalue)){
					$cvalue=json_encode($cvalue);
				}
				if(!is_string($cvalue) && !is_numeric($cvalue)){
					$cvalue=json_encode($cvalue);
				}
				$crow=str_replace("[{$cfield}]", $cvalue, $crow);
			}
			$rtn .= $crow.PHP_EOL;
		}
		//check for posttable
		if(isset($params['-postdata'])){
			$rtn .= $params['-postdata'];
		}
		elseif(isset($params['-posttable'])){
			$rtn .= $params['-posttable'];
		}
		$rtn .= '</div>'.PHP_EOL;
		if(isset($params['-ajaxid']) && strlen($params['-ajaxid'])){
			$rtn .= '</div>'.PHP_EOL;
		}
		return $rtn;
	}
	if(isset($params['-tableheight']) && strlen($params['-tableheight'])){
		$rtn .= '<div data-param="tableheight" style="max-height:'.$params['-tableheight'].';overflow:auto;position:relative;">'.PHP_EOL;
	}
	//lets make us a table from the list we have
	$rtn.='<table ';
	//add any table attributes pass in with -table
	$atts=array();
	foreach($params as $k=>$v){
		if(preg_match('/^-table\_(.+)$/',$k,$m)){
			$atts[$m[1]]=$v;
		}
		elseif(preg_match('/^-table(.+)$/',$k,$m)){
			$atts[$m[1]]=$v;
		}
	}
	$rtn .= setTagAttributes($atts);
	$rtn .= '>'.PHP_EOL;
	//build the thead
	$rtn.='	<thead ';
	//add any thead attributes pass in with -thead
	$atts=array();
	foreach($params as $k=>$v){
		if(preg_match('/^-thead(.+)$/',$k,$m)){
			$atts[$m[1]]=$v;
		}
	}
	$rtn .= setTagAttributes($atts);
	$rtn .= '>'.PHP_EOL;
	//look for -prehead
	if(isset($params['-prehead'])){
		$rtn.=$params['-prehead'];
	}
	$rtn .= '		<tr>'.PHP_EOL;
	foreach($params['-listfields'] as $field){
		//check for field options
		if(isset($params[$field."_options"]) && is_array($params[$field."_options"])){
			foreach($params[$field."_options"] as $k=>$v){
				$params[$field."_{$k}"]=$v;
			}
			unset($params[$field."_options"]);
		}
		if(!empty($params[$field."_displayname"])){
			$name=$params[$field."_displayname"];
		}
		else{
			$name=ucwords(trim(str_replace('_',' ',$field)));
		}
		//check for translate
		if(isset($params['-translate'])){
			$name=translateText($name);
		}
		$rtn .= '			<th';
		$atts=array();
		foreach($params as $k=>$v){
			if(preg_match('/^'.$field.'_(onclick|eval|href|class|style)$/i',$k)){continue;}
			elseif(preg_match('/^'.$field.'_(.+)$/',$k,$m)){
				$atts[$m[1]]=$v;
			}
		}
		foreach($params as $k=>$v){
			if(is_array($v)){continue;}
			if(stringContains($v,'</')){continue;}
			if(preg_match('/^\-th\_'.$field.'\_(.+)$/i',$k,$m)){
				$v=str_replace('%field%',$field,$v);
				if(!isset($atts[$m[1]])){$atts[$m[1]]=$v;}
			}
			elseif(preg_match('/^\-th\_(.+)$/i',$k,$m)){
				$v=str_replace('%field%',$field,$v);
				if(!isset($atts[$m[1]])){$atts[$m[1]]=$v;}
			}
			elseif(preg_match('/^-th(.+)$/',$k,$m)){
				$v=str_replace('%field%',$field,$v);
				if(!isset($atts[$m[1]])){$atts[$m[1]]=$v;}
			}
		}
		if(!isset($params[$field.'_class']) && !isset($atts['class'])){$atts['class']='w_nowrap';}
		$rtn .= setTagAttributes($atts);
		$rtn .='>';
		//TODO: build in ability to sort by column  pagingSetOrder(document.searchfiltersform,'%field%');
		$cansort=1;
		if(!isset($info[$field]) && isset($params['-table'])){$cansort=0;}
		if(!empty($params['-sorting']) && $params['-sorting']==1 && $cansort==1){
			$name='<a href="#" onclick="return pagingSetOrder(document.'.$params['-formname'].',\''.$field.'\');">'.$name;
			//show sorting icon
			if(!empty($_REQUEST['filter_order'])){
				switch(strtolower($_REQUEST['filter_order'])){
					case "{$field} desc":
						$name .= ' <span class="icon-dir-down"></span>';
					break;
					case $field:
						$name .= ' <span class="icon-dir-up"></span>';
					break;
				}
			}
			$name .= '</a>';
		}
		elseif(!empty($params['-thead_onclick'])){
			$href=$params['-thead_onclick'];
			$replace='%field%';
            $href=str_replace($replace,$field,$href);
            $name='<a href="#" onclick="'.$href.'">'.$name.'</a>';
		}
		elseif(!empty($params['-thead_href'])){
			$href=$params['-thead_href'];
			$replace='%field%';
            $href=str_replace($replace,$field,$href);
            $name='<a href="'.$href.'">'.$name.'</a>';
		}
		elseif(!empty($params[$field."_checkbox"])){
			$cname=$name;
			$name=buildFormCheckAll('data-group',"{$field}_checkbox",array('-label'=>$name,'style'=>'font-weight:600'));
		}
		$rtn .=$name;
		$rtn .='</th>'.PHP_EOL;
	}
	$rtn .= '		</tr>'.PHP_EOL;
	if(isset($params['-posthead'])){
		$rtn.=$params['-posthead'];
	}
	$rtn .= '	</thead>'.PHP_EOL;
	if(!is_array($params['-list']) || !count($params['-list'])){
		if(isset($params['-results_eval']) && function_exists($params['-results_eval'])){
			$rparams='';
			if(isset($params['-results_eval_params'])){
				$params['-list']=call_user_func($params['-results_eval'],array(),$params['-results_eval_params']);
			}
			else{
				$params['-list']=call_user_func($params['-results_eval'],array());
			}
			if(!is_array($params['-list']) || !count($params['-list'])){
				$rtn .= '</table>'.PHP_EOL;
				//check for postdata content
				if(isset($params['-postdata'])){
					$rtn .= $params['-postdata'];
				}
				elseif(isset($params['-posttable'])){
					$rtn .= $params['-posttable'];
				}
				if(isset($params['-tableheight']) && strlen($params['-tableheight'])){
					$rtn .= '</div>'.PHP_EOL;
				}
				if(isset($params['-ajaxid']) && strlen($params['-ajaxid'])){
					$rtn .= '</div>'.PHP_EOL;
				}
				$rtn .= '</div>'.PHP_EOL;
				return $rtn;
			}
		}
		else{
			$rtn .= '</table>'.PHP_EOL;
			//check for postdata content
			if(isset($params['-postdata'])){
				$rtn .= $params['-postdata'];
			}
			elseif(isset($params['-posttable'])){
				$rtn .= $params['-posttable'];
			}
			if(isset($params['-tableheight']) && strlen($params['-tableheight'])){
				$rtn .= '</div>'.PHP_EOL;
			}
			if(isset($params['-ajaxid']) && strlen($params['-ajaxid'])){
				$rtn .= '</div>'.PHP_EOL;
			}
			$rtn .= '</div>'.PHP_EOL;
			return $rtn;
		}
	}
	//check for -results_eval
	if(isset($params['-results_eval']) && function_exists($params['-results_eval'])){
		if(isset($params['-results_eval_params'])){
			$params['-list']=call_user_func($params['-results_eval'],$params['-list'],$params['-results_eval_params']);
		}
		else{
			$params['-list']=call_user_func($params['-results_eval'],$params['-list']);
		}
		
	}
	//build the tbody
	$rtn.='	<tbody ';
	//add any tbody attributes pass in with -tbody
	$atts=array();
	foreach($params as $k=>$v){
		if(preg_match('/^-tbody(.+)$/',$k,$m)){
			$atts[$m[1]]=$v;
		}
	}
	$rtn .= setTagAttributes($atts);
	$rtn .= '>'.PHP_EOL;
	$sums=array();
	if(isset($params['-sumfields'])){
		if(!is_array($params['-sumfields'])){
			$params['-sumfields']=preg_split('/\,/',$params['-sumfields']);
		}
		foreach($params['-sumfields'] as $sfld){
			$sums[$sfld]=0;
		}
	}
	$avgs=array();
	if(isset($params['-avgfields'])){
		if(!is_array($params['-avgfields'])){
			$params['-avgfields']=preg_split('/\,/',$params['-avgfields']);
		}
		foreach($params['-avgfields'] as $afld){
			$avgs[$afld]=array();
		}
	}
	//check for -editfields
	if(isset($params['-editfields'])){
		if(!is_array($params['-editfields'])){
			if($params['-editfields']=='*'){
				$params['-editfields']=array();
				foreach($params['-listfields'] as $fld){
					//skip wasql internal fields
					if(!preg_match('/^\_/',$fld)){
						$params['-editfields'][]=$fld;
					}
				}
			}
			else{
				$params['-editfields']=preg_split('/\,/',$params['-editfields']);
			}
		}
	}
	if(!is_array($params['-list'])){$params['-list']=array(array('status'=>'no results'));}
	foreach($params['-list'] as $row=>$rec){
		if(isset($rec['_id'])){
			$recid=removeHtml($rec['_id']);
			$recid=(integer)$recid;
		}
		else{$recid=0;}
		$rtn .= '		<tr data-row="'.$row.'"';
		if(!empty($params['-onclick'])){
			$href=$params['-onclick'];
			//substitute and %{field}% with its value in this record
			foreach($rec as $recfld=>$recval){
				if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
				$replace='%'.$recfld.'%';
                $href=str_replace($replace,strip_tags($rec[$recfld]),$href);
            }
            $rtn .=" onclick=\"{$href}\"";
		}
		//check for -tr_class, -tr_style, -tr_data-..., -tr*
		//echo printValue($params).printValue($rec);exit;
		foreach($params as $pk=>$pv){
			if($pk=='-translate'){continue;}
			if($pk=='-truecount'){continue;}
			if(preg_match('/^\-tr_(.+)$/i',$pk,$pm)){
				$patt_name=$pm[1];
				//check for [$row]
				if(preg_match('/\[([0-9]+)\]$/',$patt_name,$rm)){
					if($rm[1] != $row){continue;}
					else{
						$patt_name=preg_replace('/\[([0-9]+)\]$/','',$patt_name);
					}
				}
				$patt_val=$pv;
				//substitute and %{field}% with its value in this record
				foreach($rec as $recfld=>$recval){
					if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
					$replace='%'.$recfld.'%';
                    $patt_val=str_replace($replace,strip_tags($rec[$recfld]),$patt_val);
                }
                $rtn .= " {$patt_name}=\"{$patt_val}\"";
			}
			elseif(preg_match('/^\-tr(.+)$/i',$pk,$pm)){
				$patt_name=$pm[1];
				$patt_val=$pv;
				//substitute and %{field}% with its value in this record
				foreach($rec as $recfld=>$recval){
					if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
					$replace='%'.$recfld.'%';
                    $patt_val=str_replace($replace,strip_tags($rec[$recfld]),$patt_val);
                }
                $rtn .= " {$patt_name}=\"{$patt_val}\"";
			}
		}
		$rtn .='>'.PHP_EOL;
		foreach($params['-listfields'] as $fld){
			if(!isset($rec[$fld])){$rec[$fld]='';}
			if(is_array($rec[$fld])){
				$rec[$fld]=json_encode($rec[$fld],JSON_INVALID_UTF8_SUBSTITUTE);
			}
			$value=$rec[$fld];

			// is this a sum field?
			if(isset($sums[$fld])){
				$sval=trim(removeHtml($value));
				$sval=str_replace(',','',$sval);
				$sval=str_replace('$','',$sval);
				if(isNum($sval)){$sums[$fld]+=$sval;}
			}
			elseif(isset($avgs[$fld])){
				$sval=trim(removeHtml($value));
				$sval=str_replace(',','',$sval);
				$sval=str_replace('$','',$sval);
				if(isNum($sval)){$avgs[$fld][]=$sval;}
			}
			//check for {field}_eval
			if(!empty($params[$fld."_eval"])){
				$evalstr=$params[$fld."_eval"];
				$evalstr=str_replace('%fieldname%',$fld,$evalstr);
				//substitute and %{field}% with its value in this record
				foreach($rec as $recfld=>$recval){
					if(is_array($recfld) || is_array($recval)){continue;}
					if(is_null($recval) || !strlen($recval)){$recval='';}
					$replace='%'.$recfld.'%';
                    $evalstr=str_replace($replace,strip_tags($recval),$evalstr);
                }
                $value=evalPHP('<?' . $evalstr .'?>');
			}
			//number_format
			if(isset($params[$fld."_number_format"])){
				$p=(integer)$params[$fld."_number_format"];
				$value=preg_replace('/[^0-9\.]+/','',$value);
				if(strlen($value)){
					if($p==0){
						if(function_exists('gmp_init')){
							$bigInt = gmp_init($value);
					   		$value = gmp_intval($bigInt);
					   	}
					   	else{
							$value=(integer)$value;
						}
					}	
					$value=number_format($value,$p);
				}
			}
			if(!empty($params[$fld."_translate"])){
				$value=translateText($value);
			}
			if(isset($params[$fld."_map"]) && is_array($params[$fld."_map"]) && isset($params[$fld."_map"][$value])){
				$value=$params[$fld."_map"][$value];
            }
            if(isset($params[$fld."_substr"])){
            	$sparts=preg_split('/\,/',$params[$fld."_substr"]);
            	switch(count($sparts)){
            		case 1:$value=substr($value,0,(integer)$sparts[0]);break;
            		case 2:$value=substr($value,(integer)$sparts[0],(integer)$sparts[1]);break;
            	}
			}
			//phone
            if(isset($params[$fld."_phone"])){
                $value=commonFormatPhone($value);
			}
			//ucwords
            if(isset($params[$fld."_ucwords"])){
                $value=ucwords($value);
			}
			//dateFormat
            if(isset($params[$fld."_dateFormat"])){
				if(strlen($value)){	
					$fmt=$params[$fld."_dateFormat"];
					$value=date($fmt,strtotime($value));
				}
			}
			//verboseSize
            if(isset($params[$fld."_verboseSize"])){
                $value=preg_replace('/[^0-9\.]+/','',$value);
				if(strlen($value)){	
					$fmt=$params[$fld."_verboseSize"];
					$value=verboseSize($value,$fmt);
				}
			}
			//verboseTime
            if(isset($params[$fld."_verboseTime"])){
            	$value=preg_replace('/[^0-9\.]+/','',$value);
				if(strlen($value)){	
					list($notate,$nosecs)=preg_split('/\,/',$params[$fld."_verboseTime"],2);
					$value=verboseTime($value,$notate,$nosecs);
				}
			}
			//verboseNumber
            if(isset($params[$fld."_verboseNumber"])){
            	$value=preg_replace('/[^0-9\.]+/','',$value);
				if(strlen($value)){	
					$fmt=$params[$fld."_verboseNumber"];
					$value=verboseNumber($value,$fmt);
				}
			}
			//strtolower
            if(isset($params[$fld."_strtolower"])){
                $value=strtolower($value);
			}
			//strtoupper
            if(isset($params[$fld."_strtoupper"])){
                $value=strtoupper($value);
			}
			//trim
            if(isset($params[$fld."_trim"])){
                $value=trim($value);
			}
			//rtrim
            if(isset($params[$fld."_rtrim"])){
                $value=rtrim($value);
			}
			//ltrim
            if(isset($params[$fld."_ltrim"])){
                $value=ltrim($value);
			}
			//ucwords
            if(isset($params[$fld."_ucwords"])){
                $value=ucwords($value);
			}
            //append and prepend
            if(isset($params[$fld."_append"])){
                $value.=$params[$fld."_append"];
			}
			if(isset($params[$fld."_prepend"])){
                $value=$params[$fld."_prepend"].$value;
			}
			//check for {field}_onclick and {field}_href
			if(!empty($params[$fld."_onclick"]) && !isset($params[$fld."_checkmark"])){
				$href=$params[$fld."_onclick"];
				$href=str_replace('%fieldname%',$fld,$href);
				//substitute and %{field}% with its value in this record
				foreach($rec as $recfld=>$recval){
					if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
					$replace='%'.$recfld.'%';
                    $href=str_replace($replace,strip_tags($rec[$recfld]),$href);
                }
                $value='<a href="#" onclick="'.$href.'">'.$value.'</a>';
			}
			elseif(!empty($params[$fld."_checkbox"])){
				$cval=$value;
				$checkbox_id=$fld.'_checkbox_'.$row;
				if(!empty($params[$fld."_checkbox_id"])){
					$checkbox_id=$params[$fld."_checkbox_id"];
					//substitute and %{field}% with its value in this record
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $checkbox_id=str_replace($replace,strip_tags($rec[$recfld]),$checkbox_id);
	                }
				}
				$checkbox_value=$value;
				if(!empty($params[$fld."_checkbox_value"])){
					$checkbox_value=$params[$fld."_checkbox_value"];
					//substitute and %{field}% with its value in this record
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $checkbox_value=str_replace($replace,strip_tags($rec[$recfld]),$checkbox_value);
	                }
				}
				$value='<input type="checkbox" data-group="'.$fld.'_checkbox" id="'.$checkbox_id.'" name="'.$fld.'[]" value="'.$checkbox_value.'"';
				//data-
				if(isset($params[$fld.'_options']) && is_array($params[$fld.'_options'])){
					$used[$fld.'_options']=1;
					//echo printValue($params[$fld.'_options']);exit;
					foreach($params[$fld.'_options'] as $okey=>$oval){
						if($okey=='class'){
							$value.=" {$okey}=\"{$oval}\"";
							$used[$fld.'_'.$okey]=1;
						}
						elseif(stringBeginsWith($okey,'data-')){
							$data=$oval;
							foreach($rec as $recfld=>$recval){
								if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
								$replace='%'.$recfld.'%';
			                    $data=str_replace($replace,strip_tags($rec[$recfld]),$data);
			                }
			                $data=trim($data);
							$value.=" {$okey}=\"{$data}\"";
							$used[$fld.'_'.$okey]=1;
						}
					}
					unset($params[$fld.'_options']);
				}
				if(!empty($params[$fld."_checkbox_onclick"])){
					$onclick=$params[$fld."_checkbox_onclick"];
					//substitute and %{field}% with its value in this record
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $onclick=str_replace($replace,strip_tags($rec[$recfld]),$onclick);
	                }
					$value .= ' onclick="'.$onclick.'"';
				}
				if(!empty($params[$fld."_checkbox_checked"])){
					$checkbox_checked=$params[$fld."_checkbox_checked"];
					//substitute %{field}% with its value in this record
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $checkbox_checked=str_replace($replace,strip_tags($rec[$recfld]),$checkbox_checked);
	                }
	                if($checkbox_checked==$checkbox_id){
	                	$value .= ' checked';
	                }
				}
				elseif($cval==1){
					$value .= ' checked';
				}
				$value.=' /> ';
				if(!isNum($cval)){$value .= '<label for="'.$checkbox_id.'">'.$cval.'</label>';}
			}
			elseif(!empty($params[$fld."_radio"])){
				$cval=$value;
				$radio_id=$fld.'_radio_'.$row;
				if(!empty($params[$fld."_radio_id"])){
					$radio_id=$params[$fld."_radio_id"];
					//substitute and %{field}% with its value in this record
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $radio_id=str_replace($replace,strip_tags($rec[$recfld]),$radio_id);
	                }
				}
				$radio_value=$value;
				if(!empty($params[$fld."_radio_value"])){
					$radio_value=$params[$fld."_radio_value"];
					//substitute and %{field}% with its value in this record
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $radio_value=str_replace($replace,strip_tags($rec[$recfld]),$radio_value);
	                }
				}
				$value='<input type="radio" data-group="'.$fld.'_radio" id="'.$radio_id.'" name="'.$fld.'[]" value="'.$radio_value.'"';
				//data-
				if(isset($params[$fld.'_options']) && is_array($params[$fld.'_options'])){
					$used[$fld.'_options']=1;
					foreach($params[$fld.'_options'] as $okey=>$oval){
						if($okey=='class'){
							$value.=" {$okey}=\"{$oval}\"";
							$used[$fld.'_'.$okey]=1;
						}
						elseif(stringBeginsWith($okey,'data-')){
							$data=$oval;
							foreach($rec as $recfld=>$recval){
								if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
								$replace='%'.$recfld.'%';
			                    $data=str_replace($replace,strip_tags($rec[$recfld]),$data);
			                }
			                $data=trim($data);
							$value.=" {$okey}=\"{$data}\"";
							$used[$fld.'_'.$okey]=1;
						}
					}
					unset($params[$fld.'_options']);
				}
				//onclick
				if(!empty($params[$fld."_radio_onclick"])){
					$onclick=$params[$fld."_radio_onclick"];
					//substitute and %{field}% with its value in this record
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $onclick=str_replace($replace,strip_tags($rec[$recfld]),$onclick);
	                }
					$value .= ' onclick="'.$onclick.'"';
				}
				if(!empty($params[$fld."_radio_checked"])){
					$radio_checked=$params[$fld."_radio_checked"];
					//substitute and %{field}% with its value in this record
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $radio_checked=str_replace($replace,strip_tags($rec[$recfld]),$radio_checked);
	                }
	                if($radio_checked==$radio_id){
	                	$value .= ' checked';
	                }
				}
				elseif($cval==1){
					$value .= ' checked';
				}
				$value.=' /> ';
				if(!isNum($cval)){$value .= '<label for="'.$radio_id.'">'.$cval.'</label>';}
			}
			elseif(!empty($params[$fld."_href"])){
				$href=$params[$fld."_href"];
				//substitute and %{field}% with its value in this record
				$href=str_replace('%fieldname%',$fld,$href);
				foreach($rec as $recfld=>$recval){
					if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
					$replace='%'.$recfld.'%';
                    $href=str_replace($replace,strip_tags($rec[$recfld]),$href);
                }
                $hvalue=$value;
                $value='<a href="'.$href.'"';
                if(!empty($params[$fld."_target"])){
                	$value .= ' target="'.$params[$fld."_target"].'"';
                }
                //data-
				if(isset($params[$fld.'_options']) && is_array($params[$fld.'_options'])){
					$used[$fld.'_options']=1;
					foreach($params[$fld.'_options'] as $okey=>$oval){
						if($okey=='class'){
							$value.=" {$okey}=\"{$oval}\"";
							$used[$fld.'_'.$okey]=1;
						}
						elseif(stringBeginsWith($okey,'data-')){
							$data=$oval;
							foreach($rec as $recfld=>$recval){
								if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
								$replace='%'.$recfld.'%';
			                    $data=str_replace($replace,strip_tags($rec[$recfld]),$data);
			                }
			                $data=trim($data);
							$value.=" {$okey}=\"{$data}\"";
							$used[$fld.'_'.$okey]=1;
						}
					}
					unset($params[$fld.'_options']);
				}
                $value .= '>'.$hvalue.'</a>';
                unset($hvalue);
			}
			elseif(isset($params[$fld."_checkmark"]) && $params[$fld."_checkmark"]==1){
				//
				if(!in_array($rec[$fld],array('','1','0'))){
				}
				elseif($value==0){
					$mark='';
					if(isset($params[$fld."_checkmark_icon_0"])){
						$mark=$params[$fld."_checkmark_icon_0"];
					}
					elseif(isset($params[$fld."_icon_0"])){
						$mark=$params[$fld."_icon_0"];
					}
					if(!empty($params[$fld."_onclick"])){
						$href=$params[$fld."_onclick"];
						$href=str_replace('%fieldname%',$fld,$href);
						//substitute and %{field}% with its value in this record
						foreach($rec as $recfld=>$recval){
							if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
							$replace='%'.$recfld.'%';
		                    $href=str_replace($replace,strip_tags($rec[$recfld]),$href);
		                }
		                $value='<div class="text-center"><a href="#" onclick="'.$href.'"><span class="'.$mark.'"></span></a></div>';
					}
					else{
						$value='<span class="'.$mark.'"></span>';
					}
				}
				elseif($value==1){
					$mark='icon-mark';
					if(isset($params[$fld."_checkmark_icon"])){
						$mark=$params[$fld."_checkmark_icon"];
					}
					elseif(isset($params[$fld."_checkmark_icon_1"])){
						$mark=$params[$fld."_checkmark_icon_1"];
					}
					elseif(isset($params[$fld."_icon_1"])){
						$mark=$params[$fld."_icon_1"];
					}
					if(!empty($params[$fld."_onclick"])){
						$href=$params[$fld."_onclick"];
						$href=str_replace('%fieldname%',$fld,$href);
						//substitute and %{field}% with its value in this record
						foreach($rec as $recfld=>$recval){
							if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
							$replace='%'.$recfld.'%';
		                    $href=str_replace($replace,strip_tags($rec[$recfld]),$href);
		                }
		                $value='<div class="text-center"><a href="#" onclick="'.$href.'"><span class="'.$mark.'"></span></a></div>';
					}
					else{
						$value='<div class="text-center"><span class="'.$mark.'"></span></div>';
					}
				}
            }
            elseif(!empty($params[$fld."_badge"])){
				$class=isset($params[$fld."_badge_class"])?$params[$fld."_badge_class"]:'';
                $value='<span class="badge '.$class.'">'.$value.'</span>';
			}
            elseif(isset($params[$fld."_verboseTime"]) && strlen($params[$fld."_verboseTime"])){
            	$value=preg_replace('/[^0-9\.]+/','',$value);
				if(strlen($value)){	
					list($notate,$nosecs)=preg_split('/\,/',$params[$fld."_verboseTime"],2);
					$value=verboseTime($value,$notate,$nosecs);
				}
            }
            elseif(isset($params[$fld."_verboseSize"]) && strlen($params[$fld."_verboseSize"])){
            	$value=preg_replace('/[^0-9\.]+/','',$value);
				if(strlen($value)){	
					$fmt=$params[$fld."_verboseSize"];
					$value=verboseSize($value,$fmt);
				}
            }
            elseif(isset($params[$fld."_dateFormat"]) && strlen($params[$fld."_dateFormat"])){
            	if(strlen($value)){	
					$fmt=$params[$fld."_dateFormat"];
					$value=date($fmt,strtotime($value));
				}
            }
            elseif(isset($params[$fld."_verboseNumber"]) && strlen($params[$fld."_verboseNumber"])){
            	$value=preg_replace('/[^0-9\.]+/','',$value);
				if(strlen($value)){	
					$value=verboseNumber($value);
				}
            }
			elseif(isset($params[$fld."_image"]) && $params[$fld."_image"]==1 && strlen($value)){
				$image_size=$params[$fld."_image_size"] ?? $params['-image_size'] ?? '28px';
				$image_radius=$params[$fld."_image_radius"] ?? $params['-image_radius'] ?? '18px';
				$image_shadow=$params[$fld."_image_shadow"] ?? $params['-image_shadow'] ?? '0 2px 4px 0 rgba(0, 0, 0, 0.2), 0 3px 10px 0 rgba(0, 0, 0, 0.19)';
				$style="max-height:{$image_size};max-width:{$image_size};border-radius:{$image_radius};box-shadow:{$image_shadow};";
				if(isset($params[$fld."_style"])){$style=$params[$fld."_image"];}
                $value='<img src="'.$value.'" alt="" style="'.$style.'" />';
			}
			$rtn .= '			<td';
			$atts=array();
			foreach($params as $k=>$v){
				if(preg_match('/^'.$fld.'_(onclick|eval|href|target)$/i',$k)){continue;}
				if(preg_match('/^'.$fld.'_(.+)$/',$k,$m)){
					$v=str_replace('%fieldname%',$fld,$v);
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $v=str_replace($replace,strip_tags($rec[$recfld]),$v);
	                }
	                $v=str_replace('"','',$v);
					$atts[$m[1]]=$v;
				}
			}
			foreach($params as $k=>$v){
				if(preg_match('/^-td_(.+)$/',$k,$m)){
					if(!isset($atts[$m[1]])){
						$v=str_replace('%fieldname%',$fld,$v);
						foreach($rec as $recfld=>$recval){
							if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
							$replace='%'.$recfld.'%';
		                    $v=str_replace($replace,strip_tags($rec[$recfld]),$v);
		                }
		                $v=str_replace('"','',$v);
						$atts[$m[1]]=$v;
					}
				}
				elseif(preg_match('/^-td(.+)$/',$k,$m)){
					if(!isset($atts[$m[1]])){
						$v=str_replace('%fieldname%',$fld,$v);
						foreach($rec as $recfld=>$recval){
							if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
							$replace='%'.$recfld.'%';
		                    $v=str_replace($replace,strip_tags($rec[$recfld]),$v);
		                }
		                $v=str_replace('"','',$v);
						$atts[$m[1]]=$v;
					}
				}
			}
			if(isset($params['-editfields']) && isset($params['-table']) && in_array($fld,$params['-editfields']) && ($recid > 0 || isset($params['-editid']))){
				if(isset($params['-editid'])){
					$editv=$params['-editid'];
					$editv=str_replace('%fieldname%',$fld,$editv);
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $editv=str_replace($replace,strip_tags($rec[$recfld]),$editv);
	                }
	                $editv=str_replace('"','',$editv);
	                $atts['id']=$editv;
				}
				else{
					
					$atts['id']="editfield_{$fld}_{$recid}";
				}
				
			}
			$rtn .= setTagAttributes($atts);
			$rtn .='>';
			if(isset($params['-anchormap']) && $fld==$params['-anchormap']){
				if(isset($params['-anchormap_full'])){
					$ch=trim(removeHtml($value));	
				}
				else{
					$ch=strtoupper(substr(trim(removeHtml($value)),0,1));
				}
				 
				if($ch==$anchor_values[0]){
					$ch=array_shift($anchor_values);
					$rtn .= '<a name="anchormap_'.$ch.'"></a>';
				}
			}
			
			if(isset($params['-editfields']) && isset($params['-table']) && in_array($fld,$params['-editfields'])){
				if(isset($params['-editfunction'])){
					$editv=$params['-editfunction'];
					$editv=str_replace('%fieldname%',$fld,$editv);
					foreach($rec as $recfld=>$recval){
						if(is_array($recfld) || is_array($recval) || is_null($recval) || !strlen($recval)){continue;}
						$replace='%'.$recfld.'%';
	                    $editv=str_replace($replace,strip_tags($rec[$recfld]),$editv);
	                }
	                $editv=str_replace('"','',$editv);
	                $rtn .= ' <sup class="icon-edit w_right w_smallest w_gray w_pointer" style="margin-left:4px;" onclick="'.$editv.'"></sup>';
				}
				else{
					$rtn .= ' <sup class="icon-edit w_right w_smallest w_gray w_pointer" style="margin-left:4px;" onclick="ajaxEditField(\''.$params['-table'].'\',\''.$recid.'\',\''.$fld.'\',{div:\''.$atts['id'].'\'});"></sup>';
				}
				
			}
			$rtn .=$value;
			$rtn .='</td>'.PHP_EOL;
		}
		$rtn .= '		</tr>'.PHP_EOL;
	}
	if(count($sums) || count($avgs)){
		$rtn .= '		<tr>'.PHP_EOL;
		foreach($params['-listfields'] as $fld){
			$rtn .= '			<th';
			$atts=array();
			foreach($params as $k=>$v){
				if(preg_match('/^'.$fld.'_(onclick|eval|href)$/i',$k)){continue;}
				if(preg_match('/^'.$fld.'_(.+)$/',$k,$m)){
					$atts[$m[1]]=$v;
				}
			}
			foreach($params as $k=>$v){
				if(preg_match('/^-th(.+)$/',$k,$m)){
					if(!isset($atts[$m[1]])){$atts[$m[1]]=$v;}
				}
			}
			$rtn .= setTagAttributes($atts);
			$rtn .='>';
			if(isset($sums[$fld])){
				if(!empty($params[$fld."_eval"])){
					$evalstr=$params[$fld."_eval"];
					$evalstr=str_replace('%fieldname%',$fld,$evalstr);
					$evalstr=str_replace("%{$fld}%",$sums[$fld],$evalstr);
	                $rtn .=evalPHP('<?' . $evalstr .'?>');
				}
				else{
					//figure out how many decimal places are in this number
					$d=strlen(substr(strrchr($sums[$fld], "."), 1));
					//format it to add commas
					$rtn .= number_format($sums[$fld],$d);
				}
			}
			elseif(isset($avgs[$fld])){
				$a = array_filter($avgs[$fld]);
				if(count($a)){
					$average = array_sum($a)/count($a);
					$average = number_format($average,2);
				}
				else{$average='';}
				$rtn .= $average;
			}
			$rtn .='</th>'.PHP_EOL;
		}
		$rtn .= '		</tr>'.PHP_EOL;
	}
	$rtn .= '	</tbody>'.PHP_EOL;
	$rtn .= '</table>'.PHP_EOL;
	//check for tableheight
	if(isset($params['-tableheight']) && strlen($params['-tableheight'])){
		$rtn .= '</div>'.PHP_EOL;
	}	
	//check for postdata content
	if(isset($params['-postdata'])){
		$rtn .= $params['-postdata'];
	}
	elseif(isset($params['-posttable'])){
		$rtn .= $params['-posttable'];
	}
	$rtn .= '</div>'.PHP_EOL;
	if(isset($params['-ajaxid']) && strlen($params['-ajaxid'])){
		$rtn .= '</div>'.PHP_EOL;
	}
	return $rtn;
}
//---------- begin function databaseParseFilters
/**
* @describe function to check for required fields in certain wasql pages
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseParseFilters($params=array()){
	$wheres=array();
	if(!isset($params['-filters'])){return array();}
	if(!is_array($params['-filters'])){
		$params['-filters']=preg_split('/[\r\n\ ]+/',trim($params['-filters']));
	}
	if(count($params['-filters'])==1 && stringBeginsWith($params['-filters'][0],'1-ct-')){
		//this is a simplesearch
		list($field,$oper,$val)=preg_split('/\-/',$params['-filters'][0],3);
		$val=trim($val);
		$lval=strtolower($val);
		$val=str_replace("'","''",$val);
		$lval=str_replace("'","''",$lval);
		$ors=array();
		foreach($params['-info'] as $field=>$info){
			switch(strtolower($params['-database'])){
				case 'oracle':
				case 'hana':
				case 'odbc':
				case 'mssql':
				case 'sqlite':
					$ors[]="lower({$field}) like '%{$lval}%'";
				break;
				case 'postgres':
				case 'postgresql':
					$ors[]="lower(cast({$field} as text)) like '%{$lval}%'";
				break;
				case 'snowflake':
					$ors[]="{$field} ilike '%{$val}%'";
				break;
				default:
					//mysql is case insensitive
					$ors[]="{$field} like '%{$val}%'";
				break;
			}
		}
		return array(implode(' or ',$ors));
	}
	foreach($params['-filters'] as $filter){
		if(is_array($filter) || !strlen($filter)){continue;}
		list($field,$oper,$val)=preg_split('/\-/',$filter,3);
		$val=trim($val);
		$lval=strtolower($val);
		$val=str_replace("'","''",$val);
		$lval=str_replace("'","''",$lval);
		switch(strtolower($oper)){
			case 'ct':
				//contains
				switch(strtolower($params['-database'])){
					case 'oracle':
					case 'hana':
					case 'odbc':
					case 'mssql':
					case 'sqlite':
						$wheres[]="lower({$field}) like '%{$lval}%'";
					break;
					case 'postgres':
					case 'postgresql':
						$wheres[]="lower(cast({$field} as text)) like '%{$lval}%'";
					break;
					case 'snowflake':
						$wheres[]="{$field} ilike '%{$val}%'";
					break;
					default:
						//mysql is case insensitive
						$wheres[]="{$field} like '%{$val}%'";
					break;
				}
			break;
			case 'nct':
				//not contains
				switch(strtolower($params['-database'])){
					case 'oracle':
					case 'hana':
					case 'odbc':
					case 'mssql':
					case 'sqlite':
						$wheres[]="lower({$field}) not like '%{$lval}%'";
					break;
					case 'postgres':
					case 'postgresql':
						$wheres[]="lower(cast({$field} as text)) not like '%{$lval}%'";
					break;
					case 'snowflake':
						$wheres[]="{$field} not ilike '%{$val}%'";
					break;
					default:
						//mysql is case insensitive
						$wheres[]="{$field} not like '%{$val}%'";
					break;
				}
			break;
			case 'ca':
				//contains any
				$ors=array();
				$cvals=preg_split('/\,/',$val);
				$lcvals=preg_split('/\,/',$lval);
				switch(strtolower($params['-database'])){
					case 'oracle':
					case 'hana':
					case 'odbc':
					case 'mssql':
					case 'sqlite':
						foreach($lcvals as $lcval){
							$ors[]="lower({$field}) like '%{$lcval}%'";
						}
					break;
					case 'postgres':
					case 'postgresql':
						foreach($lcvals as $lcval){
							$ors[]="lower(cast({$field} as text)) like '%{$lcval}%'";
						}
					break;
					case 'snowflake':
						foreach($cvals as $cval){
							$ors[]="{$field} ilike '%{$cval}%'";
						}
					break;
					default:
						//mysql is case insensitive
						foreach($cvals as $cval){
							$ors[]="{$field} like '%{$cval}%'";
						}
					break;
				}
				$wheres[]='('.implode(' or ',$ors).')';
			break;
			case 'nca':
				//not contains any
				$ands=array();
				$cvals=preg_split('/\,/',$val);
				$lcvals=preg_split('/\,/',$lval);
				switch(strtolower($params['-database'])){
					case 'oracle':
					case 'hana':
					case 'odbc':
					case 'mssql':
					case 'sqlite':
						foreach($lcvals as $lcval){
							$ands[]="lower(cast({$field} as text)) not like '%{$lcval}%'";
						}
					break;
					case 'postgres':
					case 'postgresql':
						foreach($lcvals as $lcval){
							$ands[]="lower(cast({$field} as text)) not like '%{$lcval}%'";
						}
					break;
					case 'snowflake':
						foreach($cvals as $cval){
							$ands[]="{$field} not ilike '%{$cval}%'";
						}
					break;
					default:
						//mysql is case insensitive
						foreach($cvals as $cval){
							$ands[]="{$field} not like '%{$cval}%'";
						}
					break;
				}
				$wheres[]='('.implode(' and ',$ands).')';
			break;
			case 'eq':
				//equals
				switch(strtolower($params['-database'])){
					case 'oracle':
					case 'hana':
					case 'odbc':
					case 'mssql':
					case 'sqlite':
						if(isNum($val)){
							$wheres[]="{$field} = {$val}";
						}
						elseif(stringContains($val,'(') && stringContains($val,')')){
							//val is a function
							$wheres[]="{$field} = {$val}";
						}
						else{
							$wheres[]="lower(cast({$field} as text)) = '{$lval}'";
						}
					break;
					case 'postgres':
					case 'postgresql':
						if(isNum($val)){
							$wheres[]="{$field} = {$val}";
						}
						elseif(stringContains($val,'(') && stringContains($val,')')){
							//val is a function
							$wheres[]="{$field} = {$val}";
						}
						else{
							$wheres[]="lower(cast({$field} as text)) = '{$lval}'";
						}
					break;
					case 'snowflake':
						if(isNum($val)){
							$wheres[]="{$field} = {$val}";
						}
						elseif(stringContains($val,'(') && stringContains($val,')')){
							//val is a function
							$wheres[]="{$field} = {$val}";
						}
						else{
							$wheres[]="{$field} ilike '{$val}'";
						}
					break;
					default:
						if(stringContains($val,'(') && stringContains($val,')')){
							//val is a function
							$wheres[]="{$field} = {$val}";
						}
						else{
							//mysql is case insensitive
							$wheres[]="{$field} = '{$val}'";
						}
					break;
				}
			break;
			case 'neq':
				//not equals
				switch(strtolower($params['-database'])){
					case 'oracle':
					case 'hana':
					case 'odbc':
					case 'mssql':
					case 'sqlite':
						if(isNum($val)){
							$wheres[]="{$field} != {$val}";
						}
						elseif(stringContains($val,'(') && stringContains($val,')')){
							//val is a function
							$wheres[]="{$field} != {$val}";
						}
						else{
							$wheres[]="lower({$field}) != '{$lval}'";
						}
					break;
					case 'postgres':
					case 'postgresql':
						if(isNum($val)){
							$wheres[]="{$field} != {$val}";
						}
						elseif(stringContains($val,'(') && stringContains($val,')')){
							//val is a function
							$wheres[]="{$field} != {$val}";
						}
						else{
							$wheres[]="lower(cast({$field} as text)) != '{$lval}'";
						}
					break;
					case 'snowflake':
						if(isNum($val)){
							$wheres[]="{$field} != {$val}";
						}
						elseif(stringContains($val,'(') && stringContains($val,')')){
							//val is a function
							$wheres[]="{$field} != {$val}";
						}
						else{
							$wheres[]="{$field} not ilike '{$val}'";
						}
					break;
					default:
						if(stringContains($val,'(') && stringContains($val,')')){
							//val is a function
							$wheres[]="{$field} != {$val}";
						}
						else{
							//mysql is case insensitive
							$wheres[]="{$field} != '{$val}'";
						}
					break;
				}
			break;
			case 'ea':
				//equals any
				$ors=array();
				$cvals=preg_split('/\,/',$val);
				$lcvals=preg_split('/\,/',$lval);
				switch(strtolower($params['-database'])){
					case 'oracle':
					case 'hana':
					case 'odbc':
					case 'mssql':
					case 'sqlite':
						foreach($lcvals as $lcval){
							if(isNum($lcval)){
								$ors[]="{$field} = {$lcval}";
							}
							else{
								$ors[]="lower({$field}) = '{$lcval}'";
							}
						}
					break;
					case 'postgres':
					case 'postgresql':
						foreach($lcvals as $lcval){
							if(isNum($lcval)){
								$ors[]="{$field} = {$lcval}";
							}
							else{
								$ors[]="lower(cast({$field} as text)) = '{$lcval}'";
							}
						}
					break;
					case 'snowflake':
						foreach($cvals as $cval){
							if(isNum($cval)){
								$ors[]="{$field} = {$cval}";
							}
							else{
								$ors[]="{$field} ilike '{$cval}'";
							}
						}
					break;
					default:
						//mysql is case insensitive
						foreach($cvals as $cval){
							if(isNum($cval)){
								$ors[]="{$field} = {$cval}";
							}
							else{
								$ors[]="{$field} = '{$cval}'";
							}
						}
					break;
				}
				
				$wheres[]='('.implode(' or ',$ors).')';
			break;
			case 'nea':
				//not equals any
				$ands=array();
				$cvals=preg_split('/\,/',$val);
				$lcvals=preg_split('/\,/',$lval);
				switch(strtolower($params['-database'])){
					case 'oracle':
					case 'hana':
					case 'odbc':
					case 'mssql':
					case 'sqlite':
						foreach($lcvals as $lcval){
							if(isNum($lcval)){
								$ands[]="{$field} != '{$lcval}'";
							}
							else{
								$ands[]="lower({$field}) != '{$lcval}'";
							}
						}
					break;
					case 'postgres':
					case 'postgresql':
						foreach($lcvals as $lcval){
							if(isNum($lcval)){
								$ands[]="{$field} != '{$lcval}'";
							}
							else{
								$ands[]="lower(cast({$field} as text)) != '{$lcval}'";
							}
						}
					break;
					case 'snowflake':
						foreach($cvals as $cval){
							if(isNum($cval)){
								$ands[]="{$field} != '{$cval}'";
							}
							else{
								$ands[]="{$field} not ilike '{$cval}'";
							}
						}
					break;
					default:
						//mysql is case insensitive
						foreach($cvals as $cval){
							if(isNum($cval)){
								$ands[]="{$field} != '{$cval}'";
							}
							else{
								$ands[]="{$field} != '{$cval}'";
							}
						}
					break;
				}
				$wheres[]='('.implode(' and ',$ands).')';
			break;
			case 'gt':
				//greater than
				if(isNum($val)){
					$wheres[]="{$field} > {$val}";
				}
				elseif(stringContains($val,'(') && stringContains($val,')')){
					//val is a function
					$wheres[]="{$field} > {$val}";
				}
			break;
			case 'lt':
				//less than
				if(isNum($val)){
					$wheres[]="{$field} < {$val}";
				}
				elseif(stringContains($val,'(') && stringContains($val,')')){
					//val is a function
					$wheres[]="{$field} < {$val}";
				}
			break;
			case 'egt':
				//equals or greater than
				if(isNum($val)){
					$wheres[]="{$field} >= {$val}";
				}
				elseif(stringContains($val,'(') && stringContains($val,')')){
					//val is a function
					$wheres[]="{$field} >= {$val}";
				}
			break;
			case 'elt':
				//equals or less than
				if(isNum($val)){
					$wheres[]="{$field} <= {$val}";
				}
				elseif(stringContains($val,'(') && stringContains($val,')')){
					//val is a function
					$wheres[]="{$field} <= {$val}";
				}
			break;
			case 'ib':
				//is blank
				if(!isNum($val)){
					switch(strtolower($params['-database'])){
						case 'postgres':
						case 'postgresql':
							$wheres[]="({$field} is null or LENGTH({$field}::text) = 0)";
						break;
						default:
							$wheres[]="({$field} is null or LENGTH({$field}) = 0)";
						break;
					}
				}
			break;
			case 'nb':
				//is not blank
				if(!isNum($val)){
					switch(strtolower($params['-database'])){
						case 'postgres':
						case 'postgresql':
							$wheres[]="{$field} is not null and LENGTH({$field}::text) > 0";
						break;
						default:
							$wheres[]="{$field} is not null and LENGTH({$field}) > 0";
						break;
					}
				}
			break;
			case 'db':
				//date between
				$dates=preg_split('/\,/',$val);
				if(count($dates)==2){
					switch(strtolower($params['-database'])){
						case 'hana':
						case 'oracle':
							if(stringContains($val,'(') && stringContains($val,')')){
								//val is a function
								$wheres[]="to_date({$field}) between {$dates[0]} and {$dates[1]}";
							}
							else{
								$wheres[]="to_date({$field}) between '{$dates[0]}' and '{$dates[1]}'";
							}
							
						break;
						default:
							if(stringContains($val,'(') && stringContains($val,')')){
								//val is a function
								$wheres[]="date({$field}) between {$dates[0]} and {$dates[1]}";
							}
							else{
								$wheres[]="date({$field}) between '{$dates[0]}' and '{$dates[1]}'";
							}
						break;
					}
				}
				//echo printValue($wheres);
			break;
		}
	}
	return $wheres;
}
//---------- begin function checkDBTables
/**
* @describe function to check table status
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function checkDBTables($tables=array()){
	if(!isset($tables[0])){
		$tables=getDBTables();
	}
	$recs=array();
	foreach($tables as $table){
		$q="check table {$table}";
		$rec=getDBRecord($q);
		//echo $q.printValue($rec);exit;
		$recs[]=$rec;
	}
	return $recs;
}
//---------- begin function delDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=delDBRecordById('comments',7,array('name'=>'bob'));
*/
function delDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("delDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("delDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return delDBRecord($params);
}
//---------- begin function checkDBTableSchema
/**
* @describe function to check for required fields in certain wasql pages
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function checkDBTableSchema($wtable){
	global $CONFIG;
	$finfo=getDBFieldInfo($wtable);
	$recs=getDBIndexes(array($wtable));
	$index=array();
	foreach($recs as $rec){
    	$key=$rec['column_name'];
    	$index[$key]=$rec;
	}
	$rtn='';
    if($wtable=='_pages'){
		if(!isset($finfo['_amem'])){
			$query="ALTER TABLE {$wtable} ADD _amem ".databaseDataType('bigint')." NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _amem to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['_counter'])){
			$query="ALTER TABLE {$wtable} ADD _counter integer NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added _amem to _pages table<br />".PHP_EOL;
        }
        if(isset($CONFIG['minify_css']) && $CONFIG['minify_css']==1 && !isset($finfo['css_min'])){
			$query="ALTER TABLE {$wtable} ADD css_min text NULL;";
			$ok=executeSQL($query);
			$rtn .= " added css_min to {$wtable} table<br />".PHP_EOL;
        }
        if(isset($CONFIG['minify_js']) && $CONFIG['minify_js']==1 && !isset($finfo['js_min'])){
			$query="ALTER TABLE {$wtable} ADD js_min text NULL;";
			$ok=executeSQL($query);
			$rtn .= " added js_min to {$wtable} table<br />".PHP_EOL;
        }
        if(!isset($finfo['_cache'])){
			$query="ALTER TABLE {$wtable} ADD _cache ".databaseDataType('tinyint')." NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added _cache to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['controller'])){
			$query="ALTER TABLE {$wtable} ADD controller text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='controller'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'controller','displayname'=>'Controller',
				'inputtype'=>'textarea','width'=>700,'height'=>100,'behavior'=>"phpeditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added controller to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['functions'])){
			$query="ALTER TABLE {$wtable} ADD functions ".databaseDataType('mediumtext')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='functions'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'functions','displayname'=>'Controller Functions',
				'inputtype'=>'textarea','width'=>700,'height'=>100,'behavior'=>"phpeditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added functions to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['permalink'])){
			$query="ALTER TABLE {$wtable} ADD permalink varchar(255) NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='permalink'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'permalink','displayname'=>'Permalink',
				'inputtype'=>'text','width'=>700,'inputmax'=>255
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added permalink to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['js'])){
			$query="ALTER TABLE {$wtable} ADD js text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='js'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'js','displayname'=>'Javascript',
				'inputtype'=>'textarea','width'=>700,'height'=>120,'behavior'=>"jseditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added js to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['css'])){
			$query="ALTER TABLE {$wtable} ADD css text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='css'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'css','displayname'=>'CSS / Styles',
				'inputtype'=>'textarea','width'=>700,'height'=>120,'behavior'=>"csseditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added css to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['title'])){
			$query="ALTER TABLE {$wtable} ADD title varchar(255) NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='title'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'title','displayname'=>'Page Title',
				'inputtype'=>'text','width'=>200,'inputmax'=>255
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added title to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['sort_order'])){
			$query="ALTER TABLE {$wtable} ADD sort_order int NOT NULL Default 0;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='sort_order'"));

			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'sort_order','displayname'=>'Sort Order',
				'inputtype'=>'text','width'=>200,'inputmax'=>255,'mask'=>'integer'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added sort_order to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['parent'])){
			$query="ALTER TABLE {$wtable} ADD parent int NOT NULL Default 0;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='parent'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'parent','displayname'=>'Parent Page',
				'inputtype'=>'select',
				'tvals'=>"select _id from _pages order by permalink,name,_id",
				'dvals'=>"select name,permalink from _pages order by permalink,name,_id"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added parent to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['user_content'])){
			$query="ALTER TABLE {$wtable} ADD user_content text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='user_content'"));
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $wtable,
				'fieldname'		=> 'user_content','displayname'=>'User Content',
				'description'	=> 'A place for user driven content without logic',
				'inputtype'		=> 'textarea',
				'width'			=> '700',
				'height'		=> '120'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added user_content to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['postedit'])){
			$query="ALTER TABLE {$wtable} ADD postedit ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='postedit'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'postedit',
				'description'=>'if not checked then do not show in postedit',
				'inputtype'=>'checkbox',
				'tvals'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added postedit to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['synchronize'])){
			$query="ALTER TABLE {$wtable} ADD synchronize ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='synchronize'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'synchronize',
				'description'=>'if not checked then do not synchronize with live db',
				'inputtype'=>'checkbox',
				'tvals'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added synchronize to _pages table<br />".PHP_EOL;
        }
		if(!isset($finfo['settings'])){
			$query="ALTER TABLE {$wtable} ADD settings text NULL;";
			$ok=executeSQL($query);
        }
        if(!isset($finfo['_env'])){
			$query="ALTER TABLE {$wtable} ADD _env text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_env'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_env','displayname'=>'Environment',
				'inputtype'=>'textarea','width'=>500,'height'=>100
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _env to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['_adate'])){
			$query="ALTER TABLE {$wtable} ADD _adate ".databaseDataType('datetime')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_adate'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_adate','displayname'=>'Access Date',
				'inputtype'=>'datetime'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _adate to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['_auser'])){
			$query="ALTER TABLE {$wtable} ADD _auser integer NOT NULL Default 0;";
			$ok=executeSQL($query);;
			$rtn .= " added _auser to _pages table<br />".PHP_EOL;
        }
        if(!isset($finfo['_aip'])){
			$query="ALTER TABLE {$wtable} ADD _aip char(45) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _aip to _pages table<br />".PHP_EOL;
        }
        //check indexes
        if(!isset($index['permalink'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>"permalink"));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />".PHP_EOL;
		}
	}
	//make sure _templates table has functions
    if($wtable=='_templates'){
		if(isset($CONFIG['minify_css']) && $CONFIG['minify_css']==1 && !isset($finfo['css_min'])){
			$query="ALTER TABLE {$wtable} ADD css_min text NULL;";
			$ok=executeSQL($query);
			$rtn .= " added css_min to {$wtable} table<br />".PHP_EOL;
        }
        if(isset($CONFIG['minify_js']) && $CONFIG['minify_js']==1 && !isset($finfo['js_min'])){
			$query="ALTER TABLE {$wtable} ADD js_min text NULL;";
			$ok=executeSQL($query);
			$rtn .= " added js_min to {$wtable} table<br />".PHP_EOL;
        }
        if(!isset($finfo['functions'])){
			$query="ALTER TABLE {$wtable} ADD functions ".databaseDataType('mediumtext')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='functions'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'functions','displayname'=>'Controller Functions',
				'inputtype'=>'textarea','width'=>400,'height'=>100,'behavior'=>"phpeditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added functions to _templates table<br />".PHP_EOL;
        }
        if(!isset($finfo['js'])){
			$query="ALTER TABLE {$wtable} ADD js text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='js'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'js','displayname'=>'Javascript',
				'inputtype'=>'textarea','width'=>400,'height'=>120,'behavior'=>"jseditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added js to _templates table<br />".PHP_EOL;
        }
        if(!isset($finfo['css'])){
			$query="ALTER TABLE {$wtable} ADD css text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='css'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'css','displayname'=>'CSS / Styles',
				'inputtype'=>'textarea','width'=>400,'height'=>120,'behavior'=>"csseditor"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added css to _templates table<br />".PHP_EOL;
        }
        if(!isset($finfo['_adate'])){
			$query="ALTER TABLE {$wtable} ADD _adate ".databaseDataType('datetime')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_adate'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_adate','displayname'=>'Access Date',
				'inputtype'=>'datetime'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _adate to _templates table<br />".PHP_EOL;
        }
        if(!isset($finfo['_auser'])){
			$query="ALTER TABLE {$wtable} ADD _auser integer NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added _auser to _templates table<br />".PHP_EOL;
        }
        if(!isset($finfo['_aip'])){
			$query="ALTER TABLE {$wtable} ADD _aip char(45) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _aip to _templates table<br />".PHP_EOL;
        }
        if(!isset($finfo['postedit'])){
			$query="ALTER TABLE {$wtable} ADD postedit ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='postedit'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'postedit',
				'description'=>'if not checked then do not show in postedit',
				'inputtype'=>'checkbox',
				'tvals'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added postedit to _templates table<br />".PHP_EOL;
        }
        if(!isset($finfo['synchronize'])){
			$query="ALTER TABLE {$wtable} ADD synchronize ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='synchronize'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'synchronize',
				'description'=>'if not checked then do not synchronize with live db',
				'inputtype'=>'checkbox',
				'tvals'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added synchronize to _templates table<br />".PHP_EOL;
        }
	}
	//make sure _users table has _env
    if($wtable=='_users'){
        if(!isset($finfo['_env'])){
			$query="ALTER TABLE {$wtable} ADD _env text NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_env'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_env','displayname'=>'Environment',
				'inputtype'=>'textarea','width'=>500,'height'=>100
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _env to _users table<br />".PHP_EOL;
        }
        if(isset($CONFIG['facebook_appid'])){
        	if(!isset($finfo['facebook_id'])){
				$query="ALTER TABLE {$wtable} ADD facebook_id varchar(50) NULL;";
				$ok=executeSQL($query);
				$rtn .= " added facebook_id to _users table<br />".PHP_EOL;
	        }
	        if(!isset($finfo['facebook_email'])){
				$query="ALTER TABLE {$wtable} ADD facebook_email varchar(255) NULL;";
				$ok=executeSQL($query);
				$rtn .= " added facebook_email to _users table<br />".PHP_EOL;
	        }
		}
		if(isset($CONFIG['google_appid'])){
        	if(!isset($finfo['google_id'])){
				$query="ALTER TABLE {$wtable} ADD google_id varchar(500) NULL;";
				$ok=executeSQL($query);
				$rtn .= " added google_id to _users table<br />".PHP_EOL;
	        }
	        if(!isset($finfo['google_email'])){
				$query="ALTER TABLE {$wtable} ADD google_email varchar(255) NULL;";
				$ok=executeSQL($query);
				$rtn .= " added google_email to _users table<br />".PHP_EOL;
	        }
		}

        if(!isset($finfo['_sid'])){
			$query="ALTER TABLE {$wtable} ADD _sid varchar(150) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _sid to _users table<br />".PHP_EOL;
        }
        if(!isset($finfo['_adate'])){
			$query="ALTER TABLE {$wtable} ADD _adate ".databaseDataType('datetime')." NULL;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='_adate'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'_adate','displayname'=>'Access Date',
				'inputtype'=>'datetime'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added _adate to _users table<br />".PHP_EOL;
        }
        if(!isset($finfo['_aip'])){
			$query="ALTER TABLE {$wtable} ADD _aip char(45) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _aip to _users table<br />".PHP_EOL;
        }
        if(!isset($finfo['_apage'])){
			$query="ALTER TABLE {$wtable} ADD _apage INT NULL;";
			$ok=executeSQL($query);
			$rtn .= " added _apage to _users table<br />".PHP_EOL;
        }
	}
	//make sure _synchronize table has review_user, review_pass, review_user_id
    if($wtable=='_synchronize'){
		$finfo=getDBFieldInfo($wtable);
        if(isset($finfo['review_user'])){
			$query="DROP TABLE {$wtable}";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}'"));
			$ok=delDBRecord(array('-table'=>'_tabledata','-where'=>"tablename='{$wtable}'"));
			$ok=createWasqlTable($wtable);
			$rtn .= " added review_user to _synchronize table<br />".PHP_EOL;
        }
	}
	//make sure _cron table has a cron_pid field
	if($wtable=='_cron'){
		$finfo=getDBFieldInfo($wtable);
		if(!isset($finfo['cron_pid'])){
			$query="ALTER TABLE {$wtable} ADD cron_pid integer NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added cron_pid to _cron table<br />".PHP_EOL;
        }
        if(!isset($finfo['run_as'])){
			$query="ALTER TABLE {$wtable} ADD run_as integer NOT NULL Default 0;";
			$ok=executeSQL($query);
			$rtn .= " added run_as to _cron table<br />".PHP_EOL;
			$ok=editDBRecord(array(
				'-table'		=> '_tabledata',
				'-where'		=> "tablename='{$wtable}'",
				'formfields'	=> "name active begin_date end_date\r\nfrequency run_format run_values\r\nrun_cmd\r\nrun_as running run_date run_length\r\nrun_result"
			));
			echo $ok."updated run_as tabledata<br>".PHP_EOL;
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_cron',
				'fieldname'		=> 'run_as',
				'inputtype'		=> 'select',
				'required'		=> 0,
				'displayname'	=> "Run As",
				'tvals'			=> "SELECT _id FROM _users WHERE active=1 order by firstname,lastname,_id",
				'dvals'			=> "SELECT firstname,lastname FROM _users WHERE active=1 ORDER BY firstname,lastname,_id"
			));
			echo $id."added run_as fieldinfo<br>".PHP_EOL;
        }
        if(isset($finfo['logfile'])){
			$ok=dropDBColumn($wtable,array('run_log','logfile','logfile_maxsize'));
			$ok=editDBRecord(array(
				'-table'	=> '_tabledata',
				'-where'	=> "tablename='{$wtable}'",
				'formfields'	=> "name active begin_date end_date\r\nfrequency run_format run_values\r\nrun_cmd\r\nrunning run_date run_length\r\nrun_result",
				'listfields'	=> "name\r\ncron_pid\r\nactive\r\nrunning\r\nfrequency\r\nrun_format\r\nrun_values\r\nrun_cmd\r\nrun_date\r\nrun_length\r\nbegin_date\r\nend_date"
			));
			$rtn .= " removed logfile from _cron table<br />".PHP_EOL;
        }
        //check indexes
        if(!isset($index['name'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'active,name'));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />".PHP_EOL;
		}
    }
    //make sure _queries table has a tablename field
    if($wtable=='_queries'){
		$finfo=getDBFieldInfo($wtable);
		if(!isset($finfo['tablename'])){
			$query="ALTER TABLE {$wtable} ADD tablename varchar(255) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added tablename to _queries table<br />".PHP_EOL;
        }
        //check indexes
        if(!isset($index['tablename'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'tablename'));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />".PHP_EOL;
		}
    }
    //make sure _tabledata table has a tablename field
    if($wtable=='_tabledata'){
		$finfo=getDBFieldInfo($wtable);
		if(!isset($finfo['tablegroup'])){
			$query="ALTER TABLE {$wtable} ADD tablegroup varchar(255) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added tablegroup to _tabledata table<br />".PHP_EOL;
        }
		if(!isset($finfo['synchronize'])){
			$query="ALTER TABLE {$wtable} ADD synchronize ".databaseDataType('tinyint')." NOT NULL Default 0";
			$ok=executeSQL($query);
			global $SETTINGS;
			$stables=array('_cron','_reports','_pages','_templates','_tabledata','_fielddata');
			$oldstables=preg_split('/[\r\n]+/',trim($CONFIG['wasql_synchronize_tables']));
			if(is_array($oldstables)){
				foreach($oldstables as $stable){
	            	if(!in_array($stable,$stables)){$stables[]=$stable;}
				}
			}
			$stablestr=implode("','",$stables);
			$query="update _tabledata set synchronize=1 where tablename in ('{$stablestr}')";
			$ok=executeSQL($query);
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $wtable,
				'fieldname'		=> 'synchronize',
				'description'	=> 'if not checked then do not synchronize with live db',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1'
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added synchronize to _tabledata table<br />".PHP_EOL;
        }
        if(!isset($finfo['websockets'])){
			$query="ALTER TABLE {$wtable} ADD websockets ".databaseDataType('tinyint')." NOT NULL Default 0";
			$ok=executeSQL($query);
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $wtable,
				'fieldname'		=> 'websockets',
				'description'	=> 'if checked then send changes to the websocket server',
				'inputtype'		=> 'checkbox',
				'tvals'			=> '1'
				));
			$rtn .= " added websockets to _tabledata table<br />".PHP_EOL;
        }
        if(!isset($finfo['tabledesc'])){
			$query="ALTER TABLE {$wtable} ADD tabledesc varchar(500) NULL;";
			$ok=executeSQL($query);
			$rtn .= " added tabledesc to _tabledata table<br />".PHP_EOL;
        }
    }
    //make sure _fielddata table has a description field
    if($wtable=='_fielddata'){
		$finfo=getDBFieldInfo($wtable,1);
		//check for slider control
		if(!stringContains($finfo['inputtype']['tvals'],'wasqlGetInputtypes')){
			$id=editDBRecord(array('-table'=>'_fielddata',
				'-where'		=> "tablename='_fielddata' and fieldname='inputtype'",
				'tvals'			=> '<?='.'wasqlGetInputtypes();'.'?>',
				'dvals'			=> '<?='.'wasqlGetInputtypes(1);'.'?>',
				'onchange'		=> "fielddataChange(this);"
			));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " updated dvals and dvals of inputtype field in _fielddata table to be dynamic<br />".PHP_EOL;
		}
		if(!isset($finfo['synchronize'])){
			$query="ALTER TABLE {$wtable} ADD synchronize ".databaseDataType('tinyint')." NOT NULL Default 1;";
			$ok=executeSQL($query);
			$ok=delDBRecord(array('-table'=>'_fielddata','-where'=>"tablename='{$wtable}' and fieldname='synchronize'"));
			$id=addDBRecord(array('-table'=>'_fielddata','tablename'=>$wtable,
				'fieldname'=>'synchronize','defaultval'=>1,
				'inputtype'=>'checkbox','tvals'=>1,'editlist'=>1
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$cronfields=array('active','cron_pid','run_date','run_date_utime','running','run_length','run_result');
			foreach($cronfields as $cronfield){
				$query="update _fielddata set synchronize=0 where tablename='_cron' and fieldname='{$cronfield}'";
				$ok=executeSQL($query);
			}
			$id=editDBRecord(array('-table'=>'_tabledata',
				'-where'		=> "tablename='_fielddata'",
				'formfields'	=> "tablename fieldname\r\ndescription\r\ndisplayname\r\ninputtype mask behavior\r\nwidth height inputmax required editlist synchronize\r\nonchange\r\ntvals\r\ndvals\r\ndefaultval\r\nhelp",
				'listfields'	=> "tablename\r\nfieldname\r\ninputtype\r\nwidth\r\nheight\r\ninputmax\r\neditlist\r\nsynchronize\r\ndescription"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added synchronize to _fielddata table<br />".PHP_EOL;
        }
		if(!isset($finfo['description'])){
			$query="ALTER TABLE {$wtable} ADD description varchar(255) NULL;";
			$ok=executeSQL($query);
			//modify tabledata to include description
			$id=editDBRecord(array('-table'=>'_tabledata',
				'-where'		=> "tablename='_fielddata'",
				'formfields'	=> "tablename fieldname\r\ndescription\r\ndisplayname\r\ninputtype related_table behavior\r\nwidth height inputmax required editlist mask\r\nonchange\r\ntvals\r\ndvals\r\ndefaultval\r\nhelp",
				'listfields'	=> "tablename\r\nfieldname\r\ninputtype\r\nwidth\r\nheight\r\ninputmax\r\neditlist\r\ndescription"
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			//add fielddata metadata and make it in editlist
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> '_fielddata',
				'fieldname'		=> 'description',
				'inputtype'		=> 'text',
				'editlist'		=> 1,
				'width'			=> 400,
				'inputmax'		=> 255,
				));
			adminSynchronizeRecord('_fielddata',$id,isDBStage());
			$rtn .= " added description to _fielddata table<br />".PHP_EOL;
        }
        //check indexes
        if(!isset($index['tablename'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'tablename,fieldname','-unique'=>true));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />".PHP_EOL;
		}
    }
    //make sure _queries table has a tablename field
    if($wtable=='_settings'){
		$finfo=getDBFieldInfo($wtable);
		if($finfo['key_value']['_dblength'] > 0 && $finfo['key_value']['_dblength'] < 5000){
			if(isPostgreSQL()){
				$query="ALTER TABLE {$wtable} ALTER COLUMN key_value TYPE varchar(5000);";
			}
			else{
				$query="ALTER TABLE {$wtable} MODIFY key_value varchar(5000) NULL;";
			}
			$ok=executeSQL($query);
			$rtn .= " changed key_value field length from {$finfo['key_value']['_dblength']} to 5000 in _settings table<br />".PHP_EOL;
			$rtn .= $query;
        }
        //check indexes
        if(!isset($index['key_name'])){
        	$ok=addDBIndex(array('-table'=>$wtable,'-fields'=>'key_name,user_id'));
        	$rtn .= " added indexes to {$wtable} table ".printValue($ok)."<br />".PHP_EOL;
		}
    }
    return $rtn;
}
//---------- begin function clearDBCache
/**
* @describe clears the database cache array used so the same query does not happen twice
* @exclude  - this function is for internal use only and thus excluded from the manual
* @usage
*	clearDBCache();
*	clearDBCache('getDBRecords');
*/
function clearDBCache($names){
	global $databaseCache;
	if(!is_array($names)){$names=array($names);}
	if(!count($names)){return 0;}
	foreach($names as $name){
		if(isset($databaseCache[$name])){unset($databaseCache[$name]);}
	}
	return 1;
}
//---------- begin function addDBAccess
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function addDBAccess(){
	global $PAGE;
	global $SETTINGS;
	global $CONFIG;
	if(!isset($CONFIG['wasql_access']) || $CONFIG['wasql_access']!=1){return;}
	//ignore bot requests
	if((!isset($CONFIG['wasql_access_bot']) || $CONFIG['wasql_access_bot']!=1) && strlen($_SERVER['REMOTE_OS']) && stringBeginsWith($_SERVER['REMOTE_OS'],"BOT:")){return;}
	$access_days=32;
	$access_log=confValue('access_log');
	$access_dbname=confValue('access_dbname');
	$table='_access';
	$sumtable='_access_summary';
	if(isset($CONFIG['wasql_access_dbname']) && strlen($CONFIG['wasql_access_dbname'])){
		$table="{$CONFIG['wasql_access_dbname']}.{$table}";
		$sumtable="{$CONFIG['wasql_access_dbname']}.{$sumtable}";
    	}
	$fields=getDBFields($table);
	$opts=array();
	foreach($fields as $field){
		$ufield=strtoupper($field);
		if(isset($_REQUEST[$field])){$opts[$field]=$_REQUEST[$field];}
		elseif(isset($_REQUEST[$ufield])){$opts[$field]=$_REQUEST[$ufield];}
		elseif(isset($_SERVER[$field])){$opts[$field]=$_SERVER[$field];}
		elseif(isset($_SERVER[$ufield])){$opts[$field]=$_SERVER[$ufield];}
        }
    $opts['page']=$PAGE['name'];
    $opts['session_id']=session_id();
    $opts['xml']=request2XML($_REQUEST);
    $opts['-table']=$table;
	$id=addDBRecord($opts);
	if(!isNum($id)){
		setWasqlError(debug_backtrace(),$id);
		}
	//add this request to the summary table - this is probably what takes the longest
	$finfo=getDBFieldInfo($sumtable,1);
	$parts=array();
	foreach($fields as $field){
		if(isset($finfo["{$field}_unique"])){
			$parts[]="count(distinct({$field})) as {$field}_unique";
        	}
		}
	if(in_array('guid',$fields)){$parts[]="count(distinct(guid)) as visits_unique";}
	$query="select http_host,count(_id) as visits,".implode(',',$parts)." from {$table} where YEAR(_cdate)=YEAR(NOW()) and MONTH(_cdate)=MONTH(NOW()) group by http_host";
	$recs=getDBRecords(array('-query'=>$query));
	if(is_array($recs)){
        foreach($recs as $rec){
			$opts=array('-table'=>$sumtable);
			foreach($rec as $key=>$val){
				$opts[$key]=$val;
	        	}
			$rec=getDBRecord(array('-table'=>$sumtable,'-where'=>"http_host = '{$rec['http_host']}' and YEAR(_cdate)=YEAR(NOW()) and MONTH(_cdate)=MONTH(NOW())"));
			if(is_array($rec)){
				$opts['-where']="_id={$rec['_id']}";
				$ok=editDBRecord($opts);
				}
			else{
				$opts['accessdate']=date("Y-m-d");
				$id=addDBRecord($opts);
				if(!isNum($id)){
					setWasqlError(debug_backtrace(),$id);
					}
				}
			}
		}
	//remove _access records older than 2 years
	if(!isset($_SERVER['addDBAccess'])){
		$ok=cleanupDBRecords($table,730);
		$_SERVER['addDBAccess']=1;
		}
    return true;
}
//---------- begin function cleanupDBRecords
/**
* @describe - add changes to the _changelog table
* @param table string  - table name
* @param length int  - number of days to leave - defaults to 30
* @param field string  - date field  - defaults to _cdate
* @usage
*	$ok=cleanupDBRecords('comments');
* 	$ok=cleanupDBRecords('comments',60);
* 	$ok=cleanupDBRecords('log',90,'logdate');
*/
function cleanupDBRecords($table,$length=30,$field='_cdate'){
    $opts=array(
		'-table'	=> $table,
		'-where'	=> "{$field} < (CURRENT_TIMESTAMP() - INTERVAL {$length} DAY )"
	);
    return delDBRecord($opts);
}
//---------- begin function addDBChangeLog
/**
* @describe - add changes to the _changelog table
* @exclude  - this function is for internal use only and thus excluded from the manual
*	// tablename, record_id, diff, guid, changes
*/
function addDBChangeLog($table,$id,$diff){
	if(!isDBTable("_changelog") || $table=='_changelog'){return false;}
	if(preg_match('/^_(history|changelog|access)$/is',$table)){return false;}
	$rec=getDBRecord(array('-table'=>$table,'_id'=>$id));
	if(!isset($rec['_id'])){return false;}

	//valid action values: add,edit,del,
	if(isDBTable("_history")){
		$info=getDBFieldInfo($table,1);
		if(!isset($info['xmldata'])){return false;}
		$action=strtolower(trim($action));
		$recs=getDBRecords(array('-table'=>$table,'-where'=>$where));
		if(is_array($recs)){
			foreach($recs as $rec){
				ksort($rec);
				$md5=md5(implode('',array_values($rec)));
				//don't update it nothing changed
				if(getDBCount(array('-table'=>"_history",'tablename'=>$table,'record_id'=>$rec['_id'],'md5'=>$md5))){continue;}
				$opts=array(
					'-table'	=> "_history",
					'action'	=> $action,
					'tablename'	=> $table,
					'record_id'	=> $rec['_id'],
					'xmldata'	=> request2XML($rec,array()),
					'md5'		=> $md5
					);
				if(isNum($PAGE['_id'])){$opts['page_id']=$PAGE['_id'];}
				$id=addDBRecord($opts);
				if(!isNum($id)){
					setWasqlError(debug_backtrace(),$id);
					}
            	}
        	}
        }
	return false;
}
//---------- begin function addDBHistory
/**
* @describe - add an action to the _history table
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function addDBHistory($action,$table,$where){
	if(!isDBTable("_history")){return false;}
	//is history turned off in th config file?
	$history_log=confValue('history_log');
	if(strlen($history_log) && preg_match('/^(false|0|off)$/i',$history_log)){return;}
	if(preg_match('/^_(history|users|access)$/is',$table)){return false;}
	global $PAGE;
	//valid action values: add,edit,del,
	if(isDBTable("_history")){
		$info=getDBFieldInfo($table,1);
		if(!isset($info['xmldata'])){return false;}
		$action=strtolower(trim($action));
		$recs=getDBRecords(array('-table'=>$table,'-where'=>$where));
		if(is_array($recs)){
			foreach($recs as $rec){
				ksort($rec);
				$md5=md5(implode('',array_values($rec)));
				//don't update it nothing changed
				if(getDBCount(array('-table'=>"_history",'tablename'=>$table,'record_id'=>$rec['_id'],'md5'=>$md5))){continue;}
				$opts=array(
					'-table'	=> "_history",
					'action'	=> $action,
					'tablename'	=> $table,
					'record_id'	=> $rec['_id'],
					'xmldata'	=> request2XML($rec,array()),
					'md5'		=> $md5
					);
				if(isNum($PAGE['_id'])){$opts['page_id']=$PAGE['_id'];}
				$id=addDBRecord($opts);
				if(!isNum($id)){
					setWasqlError(debug_backtrace(),$id);
					}
            	}
        	}
        }
	return false;
}
//---------- begin function addDBIndex--------------------
/**
* @describe add an index to a table
* @param params array
*	-table
*	-fields
*	[-fulltext]
*	[-unique]
*	[-name] name of the index
* @return boolean
* @usage
*	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name",'-unique'=>true));
* 	$ok=addDBIndex(array('-table'=>$table,'-fields'=>"name,number",'-unique'=>true));
*/
function addDBIndex($params=array()){
	if(!isset($params['-table'])){return 'addDBIndex Error: No table';}
	if(!isset($params['-fields'])){return 'addDBIndex Error: No fields';}
	if(!is_array($params['-fields'])){$params['-fields']=preg_split('/\,+/',$params['-fields']);}
	//fulltext or unique
	$fulltext=isset($params['-fulltext']) && $params['-fulltext']?' FULLTEXT':'';
	$unique=isset($params['-unique']) && $params['-unique']?' UNIQUE':'';
	//prefix
	$prefix='';
	if(strlen($unique)){$prefix .= 'u';}
	if(strlen($fulltext)){$prefix .= 'f';}
	$prefix.='idx';
	//name
	$fieldstr=implode('_',$params['-fields']);
	//index names cannot be longer than 64 chars long
	if(strlen($fieldstr) > 60){
    	$fieldstr=substr($fieldstr,0,60);
	}
	if(!isset($params['-name'])){$params['-name']="{$prefix}_{$params['-table']}_{$fieldstr}";}
	//index names can be up to 64 chars long.
	if(strlen($params['-name']) > 64){
		$md5=md5($fieldstr);
		$params['-name']="{$prefix}_{$params['-table']}_{$md5}";
		//if still to long then chop it off
		if(strlen($params['-name']) > 64){
			$params['-name']=substr($params['-name'],0,64);
		}
	}
	//build and execute
	$fieldstr=implode(", ",$params['-fields']);
	$query="CREATE {$unique}{$fulltext} INDEX {$params['-name']} ON {$params['-table']}({$fieldstr})";
	//echo $query.printValue($params);exit;
	return executeSQL($query);
}
//---------- begin function dropDBIndex--------------------
/**
* @describe drop an index previously created
* @param indexname string
* @param tablename string
* @return boolean
* @usage $ok=dropDBIndex($indexname,$tablename);
*/
function dropDBIndex($indexname,$tablename){
	//backward compatible...
	if(is_array($indexname) && isset($indexname['-table'])){
		$tablename=$indexname['-table'];
		$indexname=$indexname['-name'];
	}
	if(!strlen($indexname)){return 'dropDBIndex Error: No indexname';}
	if(!strlen($tablename)){return 'dropDBIndex Error: No tablename';}
	//build and execute
	$query="drop index {$indexname} on {$tablename}";
	$ok=executeSQL($query);
	//echo $query.printValue($ok);exit;
}
//---------- begin function addEditDBForm--------------------
/**
* @describe
*	returns html form used to enter data into specified table
* @param params array
*	-table string - the name of the table
*	[_id] integer - turns the form into an edit form, editing the record with this ID. Can also use -id
*	[-where] string - turns the form into an edit form, editing the record that matches the where clause
*	[-fields] string - specifies the fields to use in the form.  A comma denotes a new row, a colon denotes on the same row. You can also call getView({viewname}) to use a view to define the fields.
* 	[-editfields] string - use to override comma separated list of fields to edit on the form
* 	[-collection_field] string - single json field to store response in
*	[-method] string - POST or GET - defaults to POST
*	[-class] string - class attribute for form tag - defaults to w_form
*	[-name] string - name attribute for form tag - defaults to addedit
*	[-action] string - action attribute for form tag - defaults to $PAGE['name']
*	[-onsubmit] string - onsubmit attribute for form tag - defaults to 'return submitForm(this);'
*	[-ajax] string - changes the onsubmit attribute to ajaxSubmitForm(this,'{$params['-ajax']}');return false;"
* 	[-preform] string - prepends string before form tag
* 	[-postform] string - appends string after form tag
*	[-enctype] string - defaults to application/x-www-form-urlencoded. If the form has an file upload field changes to multipart/form-data
*	[-autocomplete] string - sets the autocomplete attribute for form tag (off|on)
*	[-id] string - sets the id attribute for form tag
*	[-accept-charset] - sets the accept-charset attribute - defaults to the database charset
*	[-utf8] - sets the accept-charset attribute to "utf-8"
*	[-template] - adds _template to the form. On ajax calls set to the blank template, normally 1
*	[-honeypot] string - name of the honeypot.  Use this to eliminate spam posts
*	[-save] string - name of the submit button - defaults to Save
*	[-hide] string - comma separated list of submit buttons to hide. i.e reset, delete, clone
*	[-custombutton] string - html for custom button to show in button list at bottom of form
*	[-focus] string - field name to set focus to
*	[-readonly] boolean - show values but not form
*	[-rec_eval] - function name to send the record to for additional processing
*	[-rec_eval_params] - additonal params to send the the rec_eval function as a second param
*	[{fieldname}_{option}] string - sets {option} for {field}.  age_displayname=>'Your Age'
*	[{fieldname}_options] array - sets multiple options for {field}  age_options=>array('displayname'=>Your Age','style'=>'...')
* @note if a {field} is a json datatype you can manually create fields to populate it as follows: {field}>{subfield}>{subsubfield}
* @return string - HTML form
* @usage addEditDBForm(array('-table'=>"comments"));
*	-------- OR ---------
*		$opts=array(
*			'-table'=>'test',
*			'-fields'=>getView('test_fields'),
*			'memberof>challenges>physical>activities_options'=>array(
*				'inputtype'=>'checkbox',
*				'tvals'=>array('pushups','squats','burpees','jumping jacks','russion stars','running')
*			),
*			'memberof>challenges>physical>active_options'=>array(
*				'inputtype'=>'checkbox',
*				'tvals'=>array(1)
*			)
*		);
*		return addEditDBForm($opts);
*/
function addEditDBForm($params=array(),$customcode=''){
	if(!isset($params['-table'])){return 'addEditDBForm Error: No table';}
	if(!isDBTable($params['-table'])){return "addEditDBForm Error: No table named '{$params['-table']}'";}
	unset($rec);
	if(isset($params['-id']) && isNum($params['-id']) && !isset($params['_id'])){
		$params['_id']=$params['-id'];
	}
	if(isset($params['_id']) && isNum($params['_id'])){
		$rec=getDBRecord(array('-table'=>$params['-table'],'_id'=>$params['_id']));
    }
    elseif(isset($params['-where']) && strlen($params['-where'])){
		$rec=getDBRecord(array('-table'=>$params['-table'],'-where'=>$params['-where']));
    }
    $preview='';
    $editmode=0;
    if(isset($rec) && is_array($rec)){
    	//check for -rec_eval
    	if(isset($params['-rec_eval']) && function_exists($params['-rec_eval'])){
			if(isset($params['-rec_eval_params'])){
				$rec=call_user_func($params['-rec_eval'],$rec,$params['-rec_eval_params']);
			}
			else{
				$rec=call_user_func($params['-rec_eval'],$rec);
			}
		}
		if($params['-table']=='_pages'){$preview=$rec['name'];}
		foreach($rec as $key=>$val){$_REQUEST[$key]=$val;}
		if(isset($params['-collection_field'])){
			$cfield=strtolower($params['-collection_field']);
			//return $cfield.printValue($rec);
			if(isset($rec[$cfield])){
				$json=json_decode($rec[$cfield],true);
				foreach($json as $k=>$v){
					$_REQUEST[$k]=$v;
				}
			}
		}
		$editmode=1;
    }
	global $USER;
	global $PAGE;
	$includeFields=array();
	$rtn='';
	$geolocation='';
	if(isset($params['-geolocation'])){
		$geolocation=$params['-geolocation'];
	}
	//get table info for this table
	$info=getDBTableInfo(array('-table'=>$params['-table'],'-fieldinfo'=>1));
	//return printValue($info);
	if(isset($params['-formfields'])){$params['-fields']=$params['-formfields'];}
	if(isset($params['-fields']) && is_array($params['-fields']) && count($params['-fields']) > 0){
		$info['formfields']=$params['-fields'];
	}
    elseif(isset($params['-fields']) && strlen($params['-fields']) > 0){
		$info['formfields']=array();
		if(isXML($params['-fields']) || isset($params['-xml'])){
			$rows=preg_split('/[\r\n]+/',$params['-fields']);
		}
		else{
			$rows=preg_split('/[\r\n\,]+/',$params['-fields']);
		}
		foreach($rows as $row){
			if(isXML((string)$row) || isset($params['-xml'])){$line=$row;}
			else{$line=preg_split('/[\t\s\:]+/',$row);}
			array_push($info['formfields'],$line);
        }
    }
    if(!isset($info['formfields'])){
    	$info['formfields']=array();
    	foreach($info['fieldinfo'] as $fld=>$finfo){
    		if(isWasqlField($fld)){continue;}
    		if(isset($finfo['_dbtype_ex']) && stringContains($finfo['_dbtype_ex'],' stored ')){continue;}
    		if(in_array($fld,$info['formfields'])){continue;}
    		$info['formfields'][]=$fld;
    	}
    }
    //echo printValue($info['formfields']);exit;
    //check for bootstrap
    if(isExtraCss('bootstrap')){$params['-bootstrap'] = 1;}

    //if formfields is not set - use the default in the backend table metadata
    if(!isset($info['formfields']) || !is_array($info['formfields']) || count($info['formfields'])==0){
		$info['formfields']=$info['default_formfields'];
	}
	//echo printValue($info);exit;
    //Build the form fields
    $rtn .= "".PHP_EOL;
    $method=isset($params['-method'])?$params['-method']:'POST';
    //form class
    $formclass=isset($params['-class'])?$params['-class']:'w_form';
    //form name
    if(isset($params['-name'])){$formname=$params['-name'];}
    elseif(isset($params['-formname'])){$formname=$params['-formname'];}
    else{$formname='addedit';}
    //form action
    $action=isset($params['-action'])?$params['-action']:'/'.$PAGE['name'].'.phtm';
    //form onsubmit
    $onsubmit='return submitForm(this);';
	if(isset($params['-onsubmit'])){$onsubmit=$params['-onsubmit'];}
	elseif(isset($params['-ajax']) && strlen($params['-ajax'])){$onsubmit="ajaxSubmitForm(this,'{$params['-ajax']}');return false;";}
	//onchange
	if(!isset($params['-onchange'])){
		$params['-onchange']="wacss.formChanged(this);";
	}
    //form enctype
    if(isset($params['-enctype'])){$enctype=$params['-enctype'];}
	else{$enctype="application/x-www-form-urlencoded";}
    //check to see if there are any file upload fields, if so change the enctype
    foreach($info['formfields'] as $fields){
		if(is_array($fields)){
			foreach($fields as $field){
				if(isset($info['fieldinfo'][$field]['_dbtype_ex']) && stringContains($info['fieldinfo'][$field]['_dbtype_ex'],' stored ')){continue;}
				if(isset($info['fieldinfo'][$field]['inputtype']) && $info['fieldinfo'][$field]['inputtype']=='file'){
	                $enctype="multipart/form-data";
	                break;
            	}
        	}
		}
		elseif(isset($info['fieldinfo'][$fields]['_dbtype_ex']) && stringContains($info['fieldinfo'][$fields]['_dbtype_ex'],' stored ')){
	        continue;
	    }
		elseif(isset($info['fieldinfo'][$fields]['inputtype']) && $info['fieldinfo'][$fields]['inputtype']=='file'){
	        $enctype="multipart/form-data";
	        break;
	    }
		if($enctype=="multipart/form-data"){break;}
	}
	if(isset($params['-preform'])){
    	$rtn.=$params['-preform'];
    }
    $rtn .= '<form name="'.$formname.'" class="'.$formclass.'" method="'.$method.'" action="'.$action.'" ';
    //id
    if(isset($params['-id']) && $params['-id']){
		$rtn .= ' id="'.$params['-id'].'"';
	}
	//enctype
	if($enctype != "none"){
    	$rtn .= ' enctype="'.$enctype.'"';
	}
	//autocomplete
	if(isset($params['-autocomplete']) && strlen($params['-autocomplete'])){
		$rtn .= ' autocomplete="'.$params['-autocomplete'].'"';
	}
    //charset - if not set, look at the database and see what it is using to set charset
    //charset reference: http://www.w3schools.com/tags/ref_charactersets.asp
	if(isset($params['-utf8'])){
		$rtn .= ' accept-charset="utf-8"';
	}
	elseif(isset($params['-accept-charset'])){
		$rtn .= ' accept-charset="'.$params['-accept-charset'].'"';
	}
	else{
    	$charset=getDBCharset();
    	switch(strtolower($charset)){
            case 'utf8':
            case 'utf-8':
            	//Unicode Standard - is the preferred encoding for e-mail and web pages
            	$rtn .= ' accept-charset="UTF-8"';
            	break;
            case 'latin1':
            case 'iso-8859-1':
            	//North America, Western Europe, Latin America, the Caribbean, Canada, Africa
            	$rtn .= ' accept-charset="ISO-8859-1"';
            	break;
            case 'latin2':
            case 'iso-8859-2':
            	//Eastern Europe
            	$rtn .= ' accept-charset="ISO-8859-2"';
            	break;
		}
	}
	$rtn .= ' onsubmit="'.$onsubmit.'"';
	$rtn .= ' onchange="'.$params['-onchange'].'"';
	$rtn .= '>'.PHP_EOL;
	//upload progress
	$upload_progress_enabled=ini_get("session.upload_progress.enabled");
	if($upload_progress_enabled && $enctype=="multipart/form-data"){
		$upload_progress_name=ini_get("session.upload_progress.name");
		$rtn .= '<input type="hidden" name="show_upload_progress" value="Uploading...">'.PHP_EOL;
		$rtn .= '<input type="hidden" name="'.$upload_progress_name.'" value="123">'.PHP_EOL;
	}
	//template?
	if(isset($params['-template'])){
		$rtn .= '<input type="hidden" name="_template" value="'.$params['-template'].'">'.PHP_EOL;
	}
	elseif(isset($params['_template'])){
		$rtn .= '<input type="hidden" name="_template" value="'.$params['_template'].'">'.PHP_EOL;
	}
    $rtn .= '<input type="hidden" name="_table" value="'.$params['-table'].'">'.PHP_EOL;
    $rtn .= '<input type="hidden" name="_formname" value="'.$formname.'">'.PHP_EOL;
    $rtn .= '<input type="hidden" name="_enctype" value="'.$enctype.'">'.PHP_EOL;
    if(!isset($params['_action'])){$params['_action']='';}
    $rtn .= '<input type="hidden" name="_action" value="'.$params['_action'].'">'.PHP_EOL;
	if(isset($params['-auth_required'])){
		$rtn .= '<input type="hidden" name="_auth_required" value="1">'.PHP_EOL;
	}
    if(strlen($preview)){$rtn .= '<input type="hidden" name="_preview" value="'.$preview.'">'.PHP_EOL;}
    $fieldlist=array();

    $used=array();
    if(isset($_REQUEST['_sort'])){
    	$rtn .= '<input type="hidden" name="_sort" value="'.$_REQUEST['_sort'].'">'.PHP_EOL;

    	$used['_sort']=1;
		}
	$hasBehaviors=0;
	if(isset($params['-honeypot'])){
		$honeypot=$params['-honeypot'];
		$rtn .= '<input type="hidden" name="_honeypot" value="'.$honeypot.'">'.PHP_EOL;
		$rtn .= '<div style="display:none"><input type="text" name="'.$honeypot.'" value=""></div>'.PHP_EOL;
		}
	$forcedatts=array(
		'id','name','class','style','onclick','onchange','onmouseover','onmouseout','onmousedown','onmouseup','onkeypress','onkeyup','onkeydown','onblur','_behavior','data-behavior','display','onfocus','title','alt','tabindex',
		'accesskey','required','readonly','requiredmsg','mask','maskmsg','displayname','size','maxlength','wrap',
		'behavior','defaultval','tvals','dvals','width','height','inputtype','message','inputmax','mask','required','tablename','fieldname','help','autofocus','autocomplete',
		'group_id','group_class','group_style','checkclass','checkclasschecked',
		'spellcheck','max','min','pattern','placeholder','readonly','step','min_displayname','max_displayname','data-labelmap','text','path','autonumber'
		);
	//data opts
	$dataopts=array();
	//check for data- options for this field
	foreach($params as $pkey=>$pval){
    	if(preg_match('/^(.+?)\_data\-(.+)$/i',$pkey,$pmatch)){
			$fkey=strtolower($pmatch[1]);
			$dkey='data-'.strtolower($pmatch[2]);
        	$dataopts[$fkey][$dkey]=$pval;
		}
	}
	$editable_fields=array();
    foreach($info['formfields'] as $fields){
		if(isset($params['-xml']) || (!is_array($fields) && isXML((string)$fields))){
			$customrow=trim((string)$fields);
			if(isset($params['-xml'])){$customrow.=PHP_EOL;}
			if(preg_match('/\<\?(.+?)\?\>/is',$customrow)){$customrow = trim(evalPHP($customrow));}
			//convert [{field}] to getDBFieldTags
			unset($cm);
			preg_match_all('/\[(.+?)\]/sm',$customrow,$cm);
			$cnt=count($cm[1]);
			$jsonmaps=array();
			for($ex=0;$ex<$cnt;$ex++){
				$cfield=$cm[1][$ex];
				if(!isset($params['-geolocation']) && in_array($cfield,array('latlong','geolocation'))){
					$geolocation="document.{$formname}.{$cfield}";
				}
				//make sure cfield is not a pattern
				if(preg_match('/^(0\-9|a\-z)/i',$cfield)){continue;}
				if($editmode==0 && isset($info['fieldinfo'][$cfield]['defaultval'])){
    				$_REQUEST[$cfield]=$info['fieldinfo'][$cfield]['defaultval'];
    			}
    			//look for json fields  meta>file1  meta>1>file
    			if(preg_match('/^([a-z0-9\_\-]+?)\>([a-z0-9\_\-]+?)$/i',$cfield,$jm)){
    				//meta>file1
    				if(!isset($jsonmaps[$jm[1]])){
    					$jsonmaps[$jm[1]]=json_decode($_REQUEST[$jm[1]],true);
    				}
    				if(isset($jsonmaps[$jm[1]][$jm[2]])){
    					$_REQUEST[$cfield]=$jsonmaps[$jm[1]][$jm[2]];
    				}
    			}
    			elseif(preg_match('/^([a-z0-9\_\-]+?)\>([a-z0-9\_\-]+?)\>([a-z0-9\_\-]+?)$/i',$cfield,$jm)){
    				//meta>1>file
    				if(!isset($jsonmaps[$jm[1]])){
    					$jsonmaps[$jm[1]]=json_decode($_REQUEST[$jm[1]],true);
    				}
    				if(isset($jsonmaps[$jm[1]][$jm[2]][$jm[3]])){
    					$_REQUEST[$cfield]=$jsonmaps[$jm[1]][$jm[2]][$jm[3]];
    				}
    			}
    			//echo $cfield.printValue($_REQUEST);exit;
				$value=isset($params[$cfield])?$params[$cfield]:$_REQUEST[$cfield];
				
				$opts=array('-table'=>$params['-table'],'-field'=>$cfield,'-formname'=>$formname,'value'=>$value);
				if(isset($params['-viewonly']) || isset($params['-readonly']) || isset($params[$cfield.'_viewonly']) || isset($params[$cfield.'_readonly'])){
					//$value=isset($opts['value'])?$opts['value']:$_REQUEST[$field];
                	//$rtn .= '			<label class="control-label w_viewonly" id="'.$field_dname.'">'.$dname.'</label>'.PHP_EOL;
					//$rtn .= '			<div class="w_viewonly" id="'.$field_content.'">'.nl2br($value).'</div>'.PHP_EOL;
					$opts['readonly']=1;
				}
				//dataopts
				if(isset($dataopts[$cfield])){
					foreach($dataopts[$cfield] as $k=>$v){$opts[$k]=$v;}
				}
				//forcedatts
				foreach($forcedatts as $copt){
					if(isset($params[$cfield.'_'.$copt])){
						$opts[$copt]=$params[$cfield.'_'.$copt];
						$used[$cfield.'_'.$copt]=1;
					}
				}
				//check for -class_all
				if(isset($params['-class_all']) && !isset($params[$cfield.'_class'])){
					$opts['class']=$params['-class_all'];
				}
				//checkfor -style_all
				if(isset($params['-style_all']) && !isset($params[$cfield.'_style'])){
					$opts['style']=$params['-style_all'];
				}
				//check for field_options array - the easier, new way to override options
				if(isset($params[$cfield.'_options']) && is_array($params[$cfield.'_options'])){
					$used[$cfield.'_options']=1;
					foreach($params[$cfield.'_options'] as $okey=>$oval){
						$opts[$okey]=$oval;
						$used[$cfield.'_'.$okey]=1;
					}
					unset($params[$cfield.'_options']);
				}
				if(isset($params[$cfield.'_checkall'])){
					$opts['-checkall']=1;
					$used[$cfield.'_checkall']=1;
				}
				if(isset($params[$cfield.'_wrap'])){
					$opts['wrap']=$params[$cfield.'_wrap'];
					$used[$cfield.'_wrap']=1;
					}
				if(isset($params[$cfield.'_tvals'])){
					$opts['tvals']=$params[$cfield.'_tvals'];
					$used[$cfield.'_tvals']=1;
				}
				if(isset($params[$cfield.'_dvals'])){
					$opts['dvals']=$params[$cfield.'_dvals'];
					$used[$field.'_dvals']=1;
				}
				if(isset($params['-bootstrap'])){
					switch(strtolower($info['fieldinfo'][$cfield]['inputtype'])){
						case 'text':
						case 'textarea':
						case 'wysiwyg':
						case 'password':
						case 'select':
						case 'combo':
						case 'multiselect':
						case 'signature':
						case 'whiteboard':
							$opts['style']='width:100%';
						break;
					}
				}
				
				if(!isset($params['-focus']) && !isset($params['-nofocus'])){$params['-focus']=$cfield;}
				$cval=getDBFieldTag($opts);
				//$cval= $cfield.printValue($_REQUEST).printValue($opts);
				$customrow=str_replace($cm[0][$ex],$cval,$customrow);
				if(!isset($params['-readonly']) && !isset($params[$cfield.'_viewonly'])){$fieldlist[]=$cfield;}
				if(!isset($used[$cfield])){$used[$cfield]=1;}
				else{$used[$cfield]+=1;}
            }
			$rtn .= $customrow;
			continue;
		}
		//set required string
		$required_char=isset($params['-required'])?$params['-required']:'*';
		$required = '			<b class="w_required" title="Required Field">'.$required_char.'</b>'.PHP_EOL;
		//row
		if(is_array($fields) && count($fields)==1 && !strlen($fields[0])){
			$fields=array();
		}
		if(is_array($fields)){
			$rtn .= '<div style="display:flex;flex-direction:row;width:100%;flex-wrap:wrap;justify-content:flex-start;align-items:flex-start; align-content:flex-start;">'.PHP_EOL;
			foreach($fields as $field){
				if(!isset($field) || !strlen($field)){continue;}
				$includeFields[$field]=1;
				//check for geolocation
				if(!isset($params['-geolocation']) && in_array($field,array('latlong','geolocation'))){
					$geolocation="document.{$formname}.{$field}";
				}
				$opts=array('-table'=>$params['-table'],'-field'=>$field,'-formname'=>$formname);
				if(isset($params['-bootstrap'])){
					switch(strtolower($info['fieldinfo'][$field]['inputtype'])){
						case 'text':
						case 'textarea':
						case 'wysiwyg':
						case 'password':
						case 'select':
						case 'combo':
						case 'multiselect':
						case 'signature':
						case 'whiteboard':
							$opts['style']='width:100%';
						break;
					}
				}
				//dataopts
				if(isset($dataopts[$field])){
					foreach($dataopts[$field] as $k=>$v){$opts[$k]=$v;}
				}
				if(isset($params['_id']) && isNum($params['_id'])){$opts['-editmode']=true;}
				
				if(isset($params[$field])){$opts['value']=$params[$field];}
				if(!isset($params['-readonly']) && !isset($params[$field.'_viewonly'])){$fieldlist[]=$field;}
				//opts
				foreach($forcedatts as $copt){
					if(isset($params[$field.'_'.$copt])){
						$opts[$copt]=$params[$field.'_'.$copt];
						$used[$field.'_'.$copt]=1;
					}
				}
				//check for -class_all
				if(isset($params['-class_all']) && !isset($params[$cield.'_class'])){$opts['class']=$params['-class_all'];}
				//check for -style_all
				if(isset($params['-style_all']) && !isset($params[$field.'_style'])){$opts['style']=$params['-style_all'];}
				//check for field_options array - the easier, new way to override options
				if(isset($params[$field.'_options']) && is_array($params[$field.'_options'])){
					$used[$field.'_options']=1;
					foreach($params[$field.'_options'] as $okey=>$oval){
						$opts[$okey]=$oval;
						$used[$field.'_'.$okey]=1;
					}
					unset($params[$field.'_options']);
				}
				//LOAD form-control if bootstrap is loaded
				if(!isset($opts['class'])){$opts['class']='';}
				//displayname
				if(isset($opts['displayname'])){
					$dname=$opts['displayname'];
					$used[$field.'_displayname']=1;
				}
				elseif(isset($params[$field.'_dname'])){
					$dname=$params[$field.'_dname'];
					$used[$field.'_dname']=1;
				}
				elseif(isset($params[$field.'_displayname'])){
					$dname=$params[$field.'_displayname'];
					$used[$field.'_displayname']=1;
				}
				elseif(isset($info['fieldinfo'][$field]['displayname']) && strlen($info['fieldinfo'][$field]['displayname'])){$dname=$info['fieldinfo'][$field]['displayname'];
				}
				else{
					$dname=str_replace('_',' ',ucfirst($field));
				}
				//if it is a slider control build a data map from tvals and dvals if given
				if(isset($info['fieldinfo'][$field]['inputtype']) && $info['fieldinfo'][$field]['inputtype']=='slider'){
                	if(strlen($info['fieldinfo'][$field]['tvals']) && strlen($info['fieldinfo'][$field]['tvals'])){
                    	$opts['data-labelmap']=mapDBDvalsToTvals($params['-table'],$field);
					}
				}
				
				if(isset($params[$field.'_checkall'])){
					$opts['-checkall']=1;
					$used[$field.'_checkall']=1;
				}
				//column
				//add to displayname class
				if(isset($params[$field.'_displayname_class'])){
                	$class = ' '.$params[$field.'_displayname_class'];
                	$used[$field.'_displayname_class']=1;
				}
				elseif(isset($params['-class'])){$class=$params['-class'];}
				else{$class="w_arial w_smallerds";}
				if(isset($info['fieldinfo'][$field]['inputtype']) && $info['fieldinfo'][$field]['inputtype']=='slider'){}
				elseif(isset($info['fieldinfo'][$field]['_required']) && $info['fieldinfo'][$field]['_required']==1){
	                $class .= ' w_required';
	            }
	            elseif(isset($info['fieldinfo'][$field]['required']) && $info['fieldinfo'][$field]['required']==1){
	                $class .= ' w_required';
	            }
				$rtn .= '		<div style="margin:10px 10px 0 0;display:flex;flex-direction:column;flex:1;" class="'.$class.'">'.PHP_EOL;
	            //default value for add forms
	            if((!isset($rec) || !is_array($rec))){
					if(isset($params[$field])){$opts['value']=$params[$field];}
					elseif(isset($params[$field.'_defaultval'])){
						$opts['value']=$params[$field.'_defaultval'];
						$used[$field.'_defaultval']=1;
					}
					elseif(isset($info['fieldinfo'][$field]['defaultval']) && strlen($info['fieldinfo'][$field]['defaultval'])){
						$opts['value']=$info['fieldinfo'][$field]['defaultval'];
						if(preg_match('/^\<\?(.+?)\?\>$/is',$opts['value'])){$opts['value'] = trim(evalPHP($opts['value']));}
					}
                }
	            //behaviors?
	            $current_value=isset($opts['value']) && strlen($opts['value'])?$opts['value']:'';
	            if(!strlen($current_value) && isset($_REQUEST[$field])){$current_value=$_REQUEST[$field];}
	            if(isset($info['fieldinfo'][$field]['behavior']) && strlen($info['fieldinfo'][$field]['behavior'])){$opts['behavior']=$info['fieldinfo'][$field]['behavior'];}
		     	if(!isset($opts['behavior'])){$opts['behavior']='';}
				if(stringContains($opts['behavior'],'editor')){
					$opts['data-ajaxid']='centerpopSQL';
				}
				//debugValue($dname);
				//debugValue($opts);
				if(strlen($preview)){$opts['-preview']=$preview;}
	            if(isset($info['fieldinfo'][$field]['behavior']) && strlen($info['fieldinfo'][$field]['behavior'])){
					$hasBehaviors++;
	            	if($info['fieldinfo'][$field]['behavior']=='html'){
						//show html preview
						$previewID=$dname.'_previw';
						$dname .= ' <img title="Click to preview html" onclick="popUpDiv(document.'.$formname.'.'.$field.'.value,{center:1,drag:1});" src="/wfiles/iconsets/16/webpage.png" width="16" height="16" style="cursor:pointer;vertical-align:middle;" alt="preview" />';
	                }
				}
				if(isset($params[$field.'_wrap'])){
					$opts['wrap']=$params[$field.'_wrap'];
					$used[$field.'_wrap']=1;
					}
                if(isset($params[$field.'_tvals'])){
					$opts['tvals']=$params[$field.'_tvals'];
					$used[$field.'_tvals']=1;
					}
				if(isset($params[$field.'_dvals'])){
					$opts['dvals']=$params[$field.'_dvals'];
					$used[$field.'_dvals']=1;
					}
				if(!isset($opts['id']) || !strlen($opts['id'])){
					$opts['id']="{$formname}_{$field}";
				}
				$field_dname=$opts['id'].'_dname';
				$field_content=$opts['id'].'_content';
				if(isset($params['-viewonly']) || isset($params['-readonly']) || isset($params[$field.'_viewonly']) || isset($params[$field.'_readonly'])){
					//$value=isset($opts['value'])?$opts['value']:$_REQUEST[$field];
                	//$rtn .= '			<label class="control-label w_viewonly" id="'.$field_dname.'">'.$dname.'</label>'.PHP_EOL;
					//$rtn .= '			<div class="w_viewonly" id="'.$field_content.'">'.nl2br($value).'</div>'.PHP_EOL;
					$opts['readonly']=1;
				}
				//displayif?
				$displayif='';
				if(isset($opts['data-displayif'])){
					$displayif = ' data-displayif="'.$opts['data-displayif'].'"';
				}
				elseif(isset($info['fieldinfo'][$field]['displayif'])){
					$displayif = ' data-displayif="'.$info['fieldinfo'][$field]['displayif'].'"';
				}
				elseif(isset($info['fieldinfo'][$field]['data-displayif'])){
					$displayif = ' data-displayif="'.$info['fieldinfo'][$field]['displayif'].'"';
				}
				elseif(isset($params[$field.'_data-displayif'])){
					$displayif = ' data-displayif="'.$params[$field.'_data-displayif'].'"';
				}
				if(isset($params[$field.'_group_id'])){
					$group_id = $params[$field.'_group_id'];
					$used[$field.'_group_id']=1;
					$rtn .= '		<div id="'.$group_id.'"';
					if(isset($params[$field.'_group_style'])){
						$rtn .= ' style="'.$params[$field.'_group_style'].'"';
						$used[$field.'_group_style']=1;
						}
					if(isset($params[$field.'_group_class'])){
						$rtn .= ' class="'.$params[$field.'_group_class'].'"';
						$used[$field.'_group_class']=1;
						}
					$rtn .= '>'.PHP_EOL;
					if(isset($params[$field.'_group_custom'])){
						$rtn .= $params[$field.'_group_custom'];
						$used[$field.'_group_custom']=1;
						}
					if(!in_array($info['fieldinfo'][$field]['inputtype'],array('signature','whiteboard'))){
						$rtn .= '			<label class="control-label"'.$displayif.' id="'.$field_dname.'">'.$dname.'</label>'.PHP_EOL;
					}
					$rtn .= '			<div id="'.$field_content.'">'.getDBFieldTag($opts).'</div>'.PHP_EOL;
					$rtn .= '		</div>'.PHP_EOL;
					}
				else{
					if(!isset($info['fieldinfo'][$field]['inputtype']) || !in_array($info['fieldinfo'][$field]['inputtype'],array('signature','whiteboard'))){
						$rtn .= '			<label class="control-label"'.$displayif.' id="'.$field_dname.'">'.$dname.'</label>'.PHP_EOL;
					}
					$rtn .= '			<div id="'.$field_content.'">'.getDBFieldTag($opts).'</div>'.PHP_EOL;
                	}
				$rtn .= '		</div>'.PHP_EOL;
				if(!isset($used[$field])){$used[$field]=1;}
				else{$used[$field]+=1;}
				if(!isset($params['-focus']) && !isset($params['-nofocus'])){$params['-focus']=$field;}
	        	}
				$rtn .= '</div>'.PHP_EOL;
			}
        else{
			if(isset($params['-tableclass'])){
        		$rtn .= '<table class="'.$params['-tableclass'].'">'.PHP_EOL;
			}
			else{
				$rtn .= '<table class="w_table">'.PHP_EOL;
			}
			$rtn .= '	<tr class="w_top">'.PHP_EOL;
			$field=(string)$fields;
			if(!strlen($field)){continue;}
			$includeFields[$field]=1;
			//check for geolocation
			if(!isset($params['-geolocation']) && in_array($field,array('latlong','geolocation'))){
				$geolocation="document.{$formname}.{$field}";
			}
			$opts=array('-table'=>$params['-table'],'-field'=>$field,'-formname'=>$formname);
			if(isset($params['_id']) && isNum($params['_id'])){$opts['-editmode']=true;}
			//check for -class_all
			if(isset($params['-class_all']) && !isset($params[$field.'_class'])){$opts['class']=$params['-class_all'];}
			//check for -style_all
			if(isset($params['-style_all']) && !isset($params[$field.'_style'])){$opts['style']=$params['-style_all'];}
			if(isset($params[$field])){$opts['value']=$params[$field];}
			if(!isset($params['-readonly']) && !isset($params[$field.'_viewonly'])){$fieldlist[]=$field;}
			//check for field_options array - the easier, new way to override options
			if(isset($params[$field.'_options']) && is_array($params[$field.'_options'])){
				foreach($params[$field.'_options'] as $okey=>$oval){
					$used[$field.'_options']=1;
					if(stringBeginsWith($okey,'data-') || in_array($okey,$forcedatts)){$opts[$okey]=$oval;}
				}
				unset($params[$field.'_options']);
			}
			//displayname
			if(isset($opts['displayname'])){
				$dname=$opts['displayname'];
				$used[$field.'_displayname']=1;
			}
			elseif(isset($params[$field.'_dname'])){
				$dname=$params[$field.'_dname'];
				$used[$field.'_dname']=1;
			}
			elseif(isset($params[$field.'_displayname'])){
				$dname=$params[$field.'_displayname'];
				$used[$field.'_displayname']=1;
			}
			elseif(isset($info['fieldinfo'][$field]['displayname']) && strlen($info['fieldinfo'][$field]['displayname'])){$dname=$info['fieldinfo'][$field]['displayname'];
			}
			else{
				$dname=str_replace('_',' ',ucfirst($field));
			}
			//opts
			$forcedatts=array(
				'id','name','class','style','onclick','onchange','onmouseover','onmouseout','onkeypress','onkeyup','onkeydown','onblur','_behavior','data-behavior','display','onfocus','title','alt','tabindex',
				'accesskey','required','readonly','requiredmsg','mask','maskmsg','displayname','size','maxlength','wrap',
				'behavior','defaultval','tvals','dvals','width','height','inputtype','message','inputmax','mask','required','tablename','fieldname','help',
				'group_id','group_class','group_style','checkclass','checkclasschecked',
				'spellcheck','max','min','pattern','placeholder','readonly','step'
				);
			foreach($forcedatts as $copt){
				if(isset($params[$field.'_'.$copt])){
					$opts[$copt]=$params[$field.'_'.$copt];
					$used[$field.'_'.$copt]=1;
				}
			}
			//displayif?
			$displayif='';
			if(isset($opts['data-displayif'])){
				$displayif = ' data-displayif="'.$opts['data-displayif'].'"';
			}
			if(isset($params[$field.'_checkall'])){
				$opts['-checkall']=1;
				$used[$field.'_checkall']=1;
			}
			if((!isset($rec) || !is_array($rec)) && isset($info['fieldinfo'][$field]['defaultval']) && strlen($info['fieldinfo'][$field]['defaultval'])){
				$opts['value']=$info['fieldinfo'][$field]['defaultval'];
				if(preg_match('/^\<\?(.+?)\?\>$/is',$opts['value'])){$opts['value'] = trim(evalPHP($opts['value']));}
            }
			//column
			$class="w_arial w_smaller";
			if(isset($params['-class'])){$class=$params['-class'];}
			elseif(isset($info['fieldinfo'][$field]['inputtype']) && $info['fieldinfo'][$field]['inputtype']=='slider'){}
			elseif(isset($info['fieldinfo'][$field]['_required']) && $info['fieldinfo'][$field]['_required']==1){
                $class .= ' w_required';
            }
            elseif(isset($info['fieldinfo'][$field]['required']) && $info['fieldinfo'][$field]['required']==1){
                $class .= ' w_required';
            }
			$rtn .= '		<td class="'.$class.'">'.PHP_EOL;
            //behaviors?
            if(isset($info['fieldinfo'][$field]['behavior']) && strlen($info['fieldinfo'][$field]['behavior'])){
				$hasBehaviors++;
            	if($info['fieldinfo'][$field]['behavior']=='html'){
					//show html preview
					$previewID=$dname.'_previw';
					$dname .= ' <img title="Click to preview html" onclick="popUpDiv(document.'.$formname.'.'.$field.'.value,{center:1,drag:1});" src="/wfiles/iconsets/16/webpage.png" width="16" height="16" style="cursor:pointer;vertical-align:middle;" alt="preview" />';
                    }
				}

			if(!isset($opts['id']) || !strlen($opts['id'])){
				$opts['id']="{$formname}_{$field}";
			}
			$field_dname=$opts['id'].'_dname';
			$field_content=$opts['id'].'_content';
			if(isset($params['-readonly']) || isset($params[$field.'_viewonly'])){
				$value=isset($opts['value'])?$opts['value']:$_REQUEST[$field];
                $rtn .= '			<label class="control-label w_viewonly"'.$displayif.' id="'.$field_dname.'">'.$dname.'</label>'.PHP_EOL;
				$rtn .= '			<div class="w_viewonly"'.$displayif.' id="'.$field_content.'">'.nl2br($value).'</div>'.PHP_EOL;
			}
            elseif(isset($params[$field.'_group_id'])){
				$used[$field.'_group_id']=1;
				$group_id = $params[$field.'_group_id'];
				$rtn .= '		<div id="'.$group_id.'"';
				if(isset($params[$field.'_group_style'])){
					$rtn .= ' style="'.$params[$field.'_group_style'].'"';
					$used[$field.'_group_style']=1;
					}
				if(isset($params[$field.'_group_class'])){
					$rtn .= ' class="'.$params[$field.'_group_class'].'"';
					$used[$field.'_group_class']=1;
					}
				$rtn .= '>'.PHP_EOL;
				if(isset($params[$field.'_group_custom'])){
					$rtn .= $params[$field.'_group_custom'];
					$used[$field.'_group_custom']=1;
					}
				if(!in_array($info['fieldinfo'][$field]['inputtype'],array('signature','whiteboard'))){
					$rtn .= '			<label class="control-label"'.$displayif.' id="'.$field_dname.'">'.$dname.'</label>'.PHP_EOL;
				}
				$rtn .= '			<div id="'.$field_content.'">'.getDBFieldTag($opts).'</div>'.PHP_EOL;
				$rtn .= '		</div>'.PHP_EOL;
				}
			else{
				if($info['fieldinfo'][$field]['_dbtype']=='json' && !isset($opts['displayname'])){
					$rtn .= '<div class="w_right w_pointer" title="Format JSON" style="margin-right:5px;"><span class="icon-json-pretty w_primary" data-id="'.$opts['id'].'" onclick="formJsonPretty(this.dataset.id);"></span></div>';
				}
				if(!isset($info['fieldinfo'][$field]['inputtype']) || !in_array($info['fieldinfo'][$field]['inputtype'],array('signature','whiteboard'))){
					$rtn .= '			<label class="control-label"'.$displayif.' id="'.$field_dname.'">'.$dname.'</label>'.PHP_EOL;
				}
				$rtn .= '			<div id="'.$field_content.'">'.getDBFieldTag($opts).'</div>'.PHP_EOL;
			}
			$rtn .= '		</td>'.PHP_EOL;
			if(!isset($used[$field])){$used[$field]=1;}
			$used[$field]+=1;
			if(!isset($params['-focus']) && !isset($params['-nofocus'])){$params['-focus']=$field;}
			$rtn .= '	</tr>'.PHP_EOL;
			$rtn .= '</table>'.PHP_EOL;
            }
    	}
    if(isset($rec['_id']) && isNum($rec['_id'])){
		$rtn .= '<input type="hidden" name="_id" value="'.$rec['_id'].'">'.PHP_EOL;
		if(isset($params['-editfields'])){
        	if(is_array($params['-editfields'])){$params['-editfields']=implode(',',$params['-editfields']);}
        	$rtn .= '<input type="hidden" name="_fields" value="'.$params['-editfields'].'">'.PHP_EOL;
		}
		else{
			$rtn .= '<input type="hidden" name="_fields" value="'.implode(',',$fieldlist).'">'.PHP_EOL;
		}
    }
    //Add any other valid inputs
    $rtn .= '<div id="other_inputs" style="display:none;">'.PHP_EOL;
    if(is_array($params)){
	    foreach($params as $key=>$val){
	    	if($key=='_filters'){
	    		$rtn .= '	<textarea name="'.$key.'">'.$val.'</textarea>'.PHP_EOL;
	    		continue;
	    	}
	    	elseif($key=='-collection_field'){
	    		$rtn .= '<input type="hidden" name="_collection_field" value="'.$val.'">'.PHP_EOL;
	    		continue;
	    	}
			elseif(isset($used[$key])){
				$rtn .= '<!--Skipped Used:'.$key.'-->'.PHP_EOL;
				continue;
			}
			elseif(stringContains($key,'<')){
				$rtn .= '<!--Skipped LT:'.$key.'-->'.PHP_EOL;
				continue;
			}
			elseif(stringEndsWith($key,'_options') && is_array($val)){
				$rtn .= '<!--Skipped Options:'.$key.'-->'.PHP_EOL;
				continue;
			}
			if(preg_match('/^[_-]/',$key) && !preg_match('/^\_(marker|menu|search|sort|start|table\_)$/is',$key)){
				$rtn .= '<!--Skipped Reserved:'.$key.'-->'.PHP_EOL;
				continue;
			}
			if(preg_match('/^(GUID|PHPSESSID|AjaxRequestUniqueId)$/i',$key)){
				//$rtn .= '<!--Skipped PHPID:'.$key.'-->'.PHP_EOL;
				continue;
			}
			if(!is_array($val) && strlen(trim($val))==0){
				$rtn .= '<!--Skipped Blank:'.$key.'-->'.PHP_EOL;
				continue;
			}
			//check for geolocation
			if(!isset($params['-geolocation']) && in_array($key,array('latlong','geolocation'))){
				$geolocation="document.{$formname}.{$key}";
			}
			if(!isset($used[$key])){$used[$key]=1;}
			else{$used[$key]+=1;}
			if(is_array($val)){
            	foreach($val as $cval){
                	$rtn .= '	<input type="hidden" name="'.$key.'[]" value="'.$cval.'">'.PHP_EOL;
				}
			}
			elseif(isNum($val)){
            	$rtn .= '	<input type="hidden" name="'.$key.'" value="'.$val.'">'.PHP_EOL;
			}
			else{
				$rtn .= '	<textarea name="'.$key.'">'.$val.'</textarea>'.PHP_EOL;
			}
	    }
	}
    $rtn .= '</div>'.PHP_EOL;
    if(!isset($params['-readonly'])){
	    //buttons
	    $rtn .= '<table class="w_table">'.PHP_EOL;
		$rtn .= '	<tr>'.PHP_EOL;
	    $save=isset($params['-save'])?$params['-save']:'Save';
	    if(isset($params['-savebutton'])){
			$rtn .= '		<td>'.$params['-savebutton'].'</td>'.PHP_EOL;
		}
	    elseif(isset($rec['_id']) && isNum($rec['_id'])){
			$class=isset($params['-save_class'])?$params['-save_class']:'btn w_white';
			if(!isset($params['-hide']) || !preg_match('/save/i',$params['-hide'])){
				$action=isset($params['-nosave'])?'':'Edit';
				//$rtn .= '		<td><input class="'.$class.'" type="submit" id="savebutton" onClick="document.'.$formname.'._action.value=\''.$action.'\';" value="'.$save.'"></td>'.PHP_EOL;
				if(isset($params['disable_on_submit']) && $params['disable_on_submit'] != 0){
					$class.= " w_disable_on_submit";
				}
				$rtn .= '		<td><button data-navigate-focus="Ctrl+s" data-navigate="1" class="'.$class.'" type="submit" id="'.$formname.'_savebutton" onclick="document.'.$formname.'._action.value=\''.$action.'\';">'.$save.'</button></td>'.PHP_EOL;
				}
			if(!isset($params['-hide']) || !preg_match('/reset/i',$params['-hide'])){
				$reset=isset($params['-reset'])?$params['-reset']:'Reset';
				$rtn .= '		<td><button class="'.$class.' w_disable_on_submit" type="reset" id="'.$formname.'_resetbutton">'.$reset.'</button></td>'.PHP_EOL;
				}
			if(!isset($params['-hide']) || !preg_match('/delete/i',$params['-hide'])){
				$action=isset($params['-nosave'])?'':'Delete';
				$delete=isset($params['-delete'])?$params['-delete']:'Delete';
				$rtn .= '		<td><button class="'.$class.' w_disable_on_submit" type="submit" id="'.$formname.'_deletebutton" onClick="if(!confirm(\'Delete this record?\')){return false;}document.'.$formname.'._action.value=\''.$action.'\';">'.$delete.'</button></td>'.PHP_EOL;
				}
			if(!isset($params['-hide']) || !preg_match('/clone/i',$params['-hide'])){
				$action=isset($params['-nosave'])?'':'Add';
				$clone=isset($params['-clone'])?$params['-clone']:'Clone';
				$rtn .= '		<td><button class="'.$class.' w_disable_on_submit" type="submit" id="'.$formname.'_clonebutton" onClick="if(!confirm(\'Clone this record?\')){return false;}document.'.$formname.'._id.value=\'\';document.'.$formname.'._action.value=\''.$action.'\';">'.$clone.'</button></td>'.PHP_EOL;
				}
			}
		elseif(!isset($params['-hide']) || !preg_match('/save/i',$params['-hide'])){
			$class=isset($params['-save_class'])?$params['-save_class']:'btn';
			$action=isset($params['-nosave'])?'':'Add';
	    	$rtn .= '		<td><button data-navigate-focus="Ctrl+s" data-navigate="1" class="'.$class.' w_disable_on_submit" type="submit" id="'.$formname.'_savebutton" onClick="document.'.$formname.'._action.value=\''.$action.'\';">'.$save.'</button></td>'.PHP_EOL;
	    	//$rtn .= '		<td><input type="reset" value="Reset"></td>'.PHP_EOL;
	    	}
	    //add custom button(s)
	    if(isset($params['-custombutton'])){
	    	if(!is_array($params['-custombutton'])){
	    		$params['-custombutton']=array($params['-custombutton']);
	    	}
	    	foreach($params['-custombutton'] as $button){
	    		$rtn .= '		<td>'.$button.'</td>'.PHP_EOL;	
	    	}
			
		}
	    $rtn .= '	</tr>'.PHP_EOL;
	    $rtn .= '</table>'.PHP_EOL;
	}
    $rtn .= $customcode;
    $rtn .= '</form>'.PHP_EOL;
    //-postform
    if(isset($params['-postform'])){
    	$rtn.=$params['-postform'];
    }
    //initBehaviors?
    if($hasBehaviors && isset($_REQUEST['AjaxRequestUniqueId'])){
		$rtn .= buildOnLoad("initBehaviors();");
    }
    //geolocation?
    if(strlen($geolocation)){
    	$rtn .= buildOnLoad("getGeoLocation({$geolocation});");
    }
    //set focus field?
    if(isset($params['-focus']) && strlen($params['-focus']) && $params['-focus'] != 0){
		$rtn .= buildOnLoad("document.{$formname}['{$params['-focus']}'].focus();");
	}
    return $rtn;
	}
//---------- begin function addDBRecord--------------------
/**
* @describe adds record to table and returns the ID of record added
* @param params array
*	-table string - name of table
*	[-ignore] boolean - set to 1 to ignore if record already exists
*	[-upsert] mixed - list of fields to update if record already exits. comma separated list or array of fields.
*	[-trigger] boolean - set to false to disable trigger functionality
*	treats other params as field/value pairs
* @return array
* @usage
*	$id=addDBRecord(array(
*		'-table'		=> '_tabledata',
*		'tablename'		=> '_history',
*		'formfields'	=> "_cuser action page_id record_id\r\ntablename\r\nxmldata",
*		'listfields'	=> '_cuser action page_id record_id tablename',
*		'sortfields'	=> '_cdate desc'
*	));
*/
function addDBRecord($params=array()){
	if(isPostgreSQL()){return postgresqlAddDBRecord($params);}
	elseif(isSqlite()){return sqliteAddDBRecord($params);}
	elseif(isOracle()){return oracleAddDBRecord($params);}
	elseif(isMssql()){return mssqlAddDBRecord($params);}
	$function='addDBRecord';
	if(!isset($params['-table'])){return 'addDBRecord Error: No table';}
	$table=$params['-table'];
	if($table=='_files' && isset($_SERVER['HTTP_X_CHUNK_NUMBER']) && isset($_SERVER['HTTP_X_CHUNK_TOTAL']) && stringContains($params['file'],'chunk')){
    	//disable adding files if we are still uploading a chunked file.
    	return;
	}
	global $USER;
	global $CONFIG;
	//trigger
	if(!isset($params['-trigger']) || ($params['-trigger'])){
		$trigger=getDBTableTrigger($table);
		$trigger_table=$table;
	}
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$params['-dbname']=array_shift($table_parts);
		$trigger_table=implode('.',$table_parts);
		}
	if(isset($trigger['functions']) && !isset($params['-notrigger']) && strlen(trim($trigger['functions']))){
    	$ok=includePHPOnce($trigger['functions'],"{$trigger_table}-trigger_functions");
    	//look for Before trigger
    	$trigger['check']=1;
    	if(function_exists("{$trigger_table}AddBefore")){
			unset($params['-error']);
        	$params=call_user_func("{$trigger_table}AddBefore",$params);
        	if(isset($params['-error'])){
				if(!isset($params['-nodebug'])){
					debugValue($params['-error']);
				}
				return $params['-error'];
			}
        	if(!isset($params['-table'])){return "{$trigger_table}AddBefore Error: No Table".printValue($params);}
		}
	}
	//wpass?
	if($params['-table']=='_wpass'){
        $params=wpassEncryptArray($params);
	}
	//get field info for this table
	unset($info);
	$info=getDBFieldInfo($params['-table'],1);
	if(!is_array($info)){return $info;}

	//If a value is colon separated and is for a virtual field, change it to an array 
	$jsonfields=array();
	foreach($info as $k=>$i){
    	if($i['_dbtype']=='json' && isset($params[$k])){
        	$jsonfields[]=$k;
        	if(is_array($params[$k])){
        		$params[$k]=json_encode($params[$k],JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
        	}
        	else{
        		$jval=json_decode($params[$k],true);
	        	if(!is_array($jval)){
	        		if(stringContains($params[$k],':')){
	        			$arr=preg_split('/\:/',$params[$k]);
	        			$params[$k]=json_encode($arr,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
	        		}
	        		else{
	        			$params[$k]=json_encode(array($params[$k]),JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
	        		}
	        	}
        	}
		}
	}
	//echo printValue($jsonfields).printValue($params).printValue($_REQUEST);exit;
	if(count($jsonfields)){
		foreach($params as $key=>$val){
			if(isset($info[$key]['_dbextra']) && stringContains($info[$key]['_dbextra'],' generated')){
				unset($params[$key]);
			    $j=getDBExpression($params['-table'],$info[$key]['_dbfield']);
			    if(strlen($j)){
					$j=str_replace(' from ','',$j);
			        $parts=preg_split('/\./',$j);
			        $jfield=array_shift($parts);
			        if(in_array($jfield,$jsonfields) && isset($params[$jfield])){
                    	$jarray=json_decode(trim($params[$jfield]),true);
                    	//echo printValue($parts).printValue($jarray);
                        switch(count($parts)){
							case 1:$jarray[$parts[0]]=$val;break;
							case 2:$jarray[$parts[0]][$parts[1]]=$val;break;
							case 3:$jarray[$parts[0]][$parts[1]][$parts[2]]=$val;break;
							case 4:$jarray[$parts[0]][$parts[1]][$parts[2]][$parts[3]]=$val;break;
							case 5:$jarray[$parts[0]][$parts[1]][$parts[2]][$parts[3]][$parts[4]]=$val;break;
							case 6:$jarray[$parts[0]][$parts[1]][$parts[2]][$parts[3]][$parts[4]][$parts[5]]=$val;break;
						}
						$params[$jfield]=json_encode($jarray,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
					}

				}
			}
		}
	}


	if(isset($info['_cuser']) && !isset($params['_cuser'])){
		$params['_cuser']=(function_exists('isUser') && isUser())?$USER['_id']:0;
    }
    if(isset($info['_cdate']) && (!isset($params['_cdate']) || !strlen(trim($params['_cdate'])))){
		$params['_cdate']='NOW()';
    }
    /* Add values for fields that match $_SERVER keys */
    foreach($info as $field=>$rec){
		if(stringBeginsWith($field,'_')){continue;}
		if(isset($params[$field])){continue;}
    	$ucfield=strtoupper($field);
		if(isset($_SERVER[$ucfield])){$params[$field]=$_SERVER[$ucfield];}
	}
	//remove guid in _users table so we do not have duplicate guids.
	if(strtolower($params['-table'])=='_users' && isset($params['guid'])){unset($params['guid']);}
	/* Filter the query based on params */
	$fields=array();
	$vals=array();
	$json_sets=array();
	foreach($params as $key=>$val){
		//skip keys that begin with a dash
		if(preg_match('/^\-/',$key)){continue;}
		//ignore params that do not match a field
		if(!isset($info[$key]['_dbtype'])){
			if(isset($params["{$key}_field"])){
				$keyfield=$params["{$key}_field"];
				if(isset($info[$keyfield]['_dbtype']) && strtolower($info[$keyfield]['_dbtype'])=='json'){
					if(isNum($val)){
						$json_sets[$keyfield][]="'\$.{$key}',{$val}";
					}
					elseif(strtoupper($val)=='NULL'){
						//do nothing
					}
					else{
						$val=str_replace("'","''",$val);
						$json_sets[$keyfield][]="'\$.{$key}','{$val}'";
					}
				}
			}
			//echo "HERE:{$key}={$val}, keyfield={$keyfield}<br>".printValue($info);exit;
			continue;
		}
		//null check
		if(!is_array($val) && strlen($val)==0 && preg_match('/not_null/',$info[$key]['_dbflags'])){
			return 'addDBRecord Datatype Null Error: Field "'.$key.'" cannot be null';
        }
        //expression fields derived from json fields
		if(isset($info[$key]['expression']) && preg_match('/json_extract\((.+?)\)/',$info[$key]['expression'],$m)){
			//user json_set to set expression fields
			//json_extract(`how_many`,_utf8mb4'$.ihop')
			//json_extract(`response`,'$.shopper_id')
			list($tfield,$jfield)=preg_split('/\,/',$m[1],2);
			$tfield=str_replace('`','',$tfield);
			$tfield=str_replace("'",'',$tfield);
			if(preg_match('/\'(.+?)\'/',$jfield,$j)){
				$jfield=$j[1];
			}
			$jfield=preg_replace('/^\$\./','',$jfield);
			$jfield=str_replace('`','',$jfield);
			$jfield=str_replace("'",'',$jfield);
			$json_sets[$tfield][$jfield]=$val;
			continue;
		}
		array_push($fields,$key);
		//date field?
		if(strlen($val) && preg_match('/^<sql>(.+)<\/sql>$/i',$val,$pm)){
			array_push($vals,$pm[1]);
			if(isset($upserts[$key])){$upserts[$key]=$pm[1];}
		}
		elseif(($info[$key]['_dbtype'] =='date')){
			if(preg_match('/(CURRENT|NOW|DATE|TIME)/i',$val)){
				array_push($vals,$val);
				if(isset($upserts[$key])){$upserts[$key]=$val;}
			}
			else{
				if(preg_match('/^[0-9]{2,2}\-[0-9]{2,2}\-[0-9]{4,4}$/',$val)){$val=str_replace('-','/',$val);}
				if(preg_match('/^([a-z\_0-9]+)\(\)$/is',$val)){
	            	array_push($vals,$val);
					if(isset($upserts[$key])){$upserts[$key]=$val;}
	            }
				else{
					$val=date("Y-m-d",strtotime($val));
					array_push($vals,"'{$val}'");
					if(isset($upserts[$key])){$upserts[$key]="'{$val}'";}
				}
			}
		}
		elseif(isset($info[$key]['inputtype']) && $info[$key]['inputtype'] =='date'){
			if(preg_match('/(CURRENT|NOW|DATE|TIME)/i',$val)){
				array_push($vals,$val);
				if(isset($upserts[$key])){$upserts[$key]=$val;}
			}
			else{
				if(preg_match('/^[0-9]{2,2}\-[0-9]{2,2}\-[0-9]{4,4}$/',$val)){$val=str_replace('-','/',$val);}
				if(preg_match('/^([a-z\_0-9]+)\(\)$/is',$val)){
	            	array_push($vals,$val);
					if(isset($upserts[$key])){$upserts[$key]=$val;}
	            }
				else{
					$val=date("Y-m-d",strtotime($val));
					array_push($vals,"'{$val}'");
					if(isset($upserts[$key])){$upserts[$key]="'{$val}'";}
				}
			}
        }
		elseif($info[$key]['_dbtype'] =='int' || $info[$key]['_dbtype'] =='tinyint' || $info[$key]['_dbtype'] =='real'){
			if(is_array($val)){$val=(integer)$val[0];}
			if(!is_numeric($val) && strtolower($val) != 'null'){return 'addDBRecord Datatype Mismatch: numeric field "'.$key.'" is type "'.$info[$key]['_dbtype'].'" and requires a numeric value';}
			array_push($vals,$val);
			if(isset($upserts[$key])){$upserts[$key]=$val;}
		}
		elseif($info[$key]['_dbtype'] =='datetime'){
			unset($dmatch);
			if(preg_match('/(CURRENT|NOW|DATE|TIME)/i',$val)){
				array_push($vals,$val);
				if(isset($upserts[$key])){$upserts[$key]=$val;}
			}
			else{
				if(is_array($val)){
					if(preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4,4})$/s',$val[0],$dmatch)){
						if(strlen($dmatch[1])==1){$dmatch[1]="0{$dmatch[1]}";}
						if(strlen($dmatch[2])==1){$dmatch[2]="0{$dmatch[2]}";}
						$newval=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
					}
					else{$newval=$val[0];}
					if($val[3]=='pm' && $val[1] < 12){$val[1]+=12;}
					elseif($val[3]=='am' && $val[1] ==12){$val[1]='00';}
					$newval .=" {$val[1]}:{$val[2]}:00";
					$val=$newval;
	            }
	            $val=date("Y-m-d H:i:s",strtotime($val));
	            array_push($vals,"'{$val}'");
	            if(isset($upserts[$key])){$upserts[$key]="'{$val}'";}
	        }
		}
		elseif($info[$key]['_dbtype'] =='time'){
			if(preg_match('/(CURRENT|NOW|DATE|TIME)/i',$val)){
				array_push($vals,$val);
				if(isset($upserts[$key])){$upserts[$key]=$val;}
			}
			else{
				if(is_array($val)){
					if($val[2]=='pm' && $val[0] < 12){$val[0]+=12;}
					elseif($val[2]=='am' && $val[0] ==12){$val[0]='00';}
					$val="{$val[0]}:{$val[1]}:00";
	            }
	            $val=date("H:i:s",strtotime($val));
	            array_push($vals,"'{$val}'");
	            if(isset($upserts[$key])){$upserts[$key]="'{$val}'";}
	        }
		}
		else{
			if($val != 'NULL'){
				$val=databaseEscapeString($val);
				array_push($vals,"'{$val}'");
				if(isset($upserts[$key])){$upserts[$key]="'{$val}'";}
			}
			else{
            	array_push($vals,$val);
            	if(isset($upserts[$key])){$upserts[$key]=$val;}
			}
        }
        if(isset($info[$key.'_sha1']) && !isset($params[$key.'_sha1'])){
			$val=sha1($val);
			array_push($fields,$key.'_sha1');
			array_push($vals,"'{$val}'");
		}
		if(isset($info[$key.'_size']) && !isset($params[$key.'_size'])){
			$val=strlen($val);
			array_push($fields,$key.'_size');
			array_push($vals,"'{$val}'");
		}
    }
    //return if no updates were found
	if(!count($fields)){
		//failure
		if(isset($trigger['functions']) && !isset($params['-notrigger'])){
	    	//look for Failure trigger
	    	if(function_exists("{$trigger_table}AddFailure")){
				$params['-error']="addDBRecord Error: No Fields";
	        	$params=call_user_func("{$trigger_table}AddFailure",$params);
			}
		}
		return "addDBRecord Error: No Fields" . printValue($params) . printValue($info);
	}
	$upserts=array(); //field to update on duplicate key
	if(isset($params['-upsert'])){
    	if(!is_array($params['-upsert'])){
    		$params['-upsert']=preg_split('/\,/',$params['-upsert']);
    	}
    	if(isset($info['_edate'])){
    		$upserts['_edate']=1;
    	}
    	if(isset($info['_euser'])){
    		$upserts['_euser']=1;
    	}
    	foreach($params['-upsert'] as $key){
    		$key=strtolower(trim($key));
    		if(!in_array($key,$fields)){continue;}
    		if(!isset($info[$key]['_dbtype']) || !strlen($info[$key]['_dbtype'])){continue;}
    		$upserts[$key]='';
    	}
    }
    if(count($json_sets)){
    	foreach($fields as $i=>$field){
    		if(isset($json_sets[$field])){
    			$vals[$i]=preg_replace('/^\'/','',$vals[$i]);
    			$vals[$i]=preg_replace('/\'$/','',$vals[$i]);
    			$json=json_decode(stripslashes($vals[$i]),true);
    			//echo "<div>{$field}:::{$vals[$i]}</div>".printValue($json).PHP_EOL;
    			foreach($json_sets[$field] as $jkey=>$jval){
    				$json[$jkey]=$jval;
    			}
    			$vals[$i]="'".json_encode($json,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE)."'";
    			unset($json_sets[$field]);
    		}
    	}
    }
    if(count($json_sets)){
    	foreach($json_sets as $tfield=>$tvals){
    		$fields[]=$tfield;
    		$vals[]="'".json_encode($tvals,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE)."'";
    	}
    }
    $fieldstr=implode(",",$fields);
    $valstr=implode(",",$vals);
    $table=$params['-table'];
    if(isMssql()){$table="[{$table}]";}
    $ignore='';
    if(isset($params['-ignore']) && $params['-ignore']){$ignore=' ignore';}
    $query = 'INSERT'.$ignore.' INTO ' . $table .PHP_EOL. '(' . $fieldstr . ')'.PHP_EOL.'VALUES (' . $valstr .')'.PHP_EOL;
   //  if(count($upserts)){
   //  	$query .= 'ON DUPLICATE KEY UPDATE'.PHP_EOL;
   //  	$updates=array();
   //  	foreach($upserts as $k=>$v){
   //  		if(!strlen($v)){
   //  			if(isset($info[$k]['default']) && strlen($info[$k]['default'])){
			// 		$val=$info[$k]['default'];
			// 	}
			// 	else{$val='NULL';}
			// }
   //  		$updates[]="	{$k} = {$v}";
   //  	}
   //  	$query .= implode(','.PHP_EOL,$updates);
   //  }
    if(count($upserts)){
		//VALUES() to refer to the new row is deprecated with version 8
		$euser=isset($USER['_id'])?(integer)$USER['_id']:0;
		//VALUES() to refer to the new row is deprecated with version 8.0.20+
		$version=getDBRecord("SHOW VARIABLES LIKE 'version'");
		list($v1,$v2,$v3)=preg_split('/\./',$version['value'],3);
		if((integer)$v1>8 || ((integer)$v1==8 && (integer)$v2 > 0) || ((integer)$v1==8 && (integer)$v2==0 && (integer)$v3 >=20)){
			$query.=PHP_EOL."AS new"." ON DUPLICATE KEY UPDATE";
			$flds=array();
			
			foreach($upserts as $k=>$v){
				switch(strtolower($k)){
					case '_edate':$flds[]="{$k}=now()";break;
					case '_euser':$flds[]="{$k}={$euser}";break;
					default:$flds[]="{$k}=new.{$k}";break;
				}
			}
			$query.=PHP_EOL.implode(', ',$flds);
			//echo $query;exit;
		}
		else{
			//before mysql version 8
			$query.=PHP_EOL." ON DUPLICATE KEY UPDATE";
			$flds=array();
			foreach($upserts as $k=>$v){
				switch(strtolower($k)){
					case '_edate':$flds[]="{$k}=now()";break;
					case '_euser':$flds[]="{$k}={$euser}";break;
					default:$flds[]="{$k}=VALUES({$k})";break;
				}
			}
			$query.=PHP_EOL.implode(', ',$flds);
		}
	}
	//echo printValue($json_sets)."<div>{$query}</div>".PHP_EOL;
	// execute sql - return the number of rows affected
	$start=microtime(true);
	$query_result=@databaseQuery($query);
  	if($query_result){
		//get the ID
		$id=databaseInsertId($query_result);
    	databaseFreeResult($query_result);
    	//get table info
		$tinfo=getDBTableInfo(array('-table'=>$params['-table'],'-fieldinfo'=>0));
		if((isset($tinfo['websockets']) && $tinfo['websockets']==1) || isset($trigger['functions'])){
			$params['-record']=getDBRecord(array('-table'=>$table,'_id'=>$id));
		}
		//check for websockets
		if(isset($tinfo['websockets']) && $tinfo['websockets']==1){
        	loadExtras('websockets');
        	$wrec=$params['-record'];
        	$wrec['_action']='add';
        	wsSendDBRecord($params['-table'],$wrec);
		}
    	if(isset($trigger['functions']) && !isset($params['-notrigger'])){
	    	//look for Success trigger
	    	if(function_exists("{$trigger_table}AddSuccess") && !isset($params['-notrigger'])){
				unset($params['-error']);
	        	$params=call_user_func("{$trigger_table}AddSuccess",$params);
	        	if(isset($params['-error'])){
					if(!isset($params['-nodebug'])){
						debugValue($params['-error']);
					}
				}
			}
		}
    	//if queries are turned on, log this query
    	if($params['-table'] != '_queries' && (!isset($params['-nolog']) || $params['-nolog'] != 1)){
			logDBQuery($query,$start,$function,$params['-table']);
		}
    	return $id;
  	}
  	else{
		$error=getDBError();
		if(isset($trigger['functions']) && !isset($params['-notrigger'])){
	    	//look for Failure trigger
	    	if(function_exists("{$trigger_table}AddFailure")){
				$params['-error']="addDBRecord Error:".printValue($error);
	        	$params=call_user_func("{$trigger_table}AddFailure",$params);
			}
		}
		if(!isset($params['-nodebug'])){
			return setWasqlError(debug_backtrace(),$error,$query);
		}
		else{
			return $error;
		}
  	}
}
//---------- begin function alterDBTable--------------------
/**
* @describe alters fields in given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=alterDBTable('comments',array('comment'=>"varchar(1000) NULL"));
*/
function alterDBTable($table='',$params=array(),$engine=''){
	if(isPostgreSQL()){return postgresqlAlterDBTable($table,$params);}
	elseif(isSqlite()){return sqliteAlterDBTable($table,$params);}
	elseif(isOracle()){return oracleAlterDBTable($table,$params);}
	elseif(isMssql()){return mssqlAlterDBTable($table,$params);}
	$function='alterDBTable';
	if(!isDBTable($table)){return "No such table: {$table}";}
	if(count($params)==0 && !strlen($engine)){return "No params";}
	global $CONFIG;
	//get current database fields
	$current=array();
	$fields=getDBSchema(array($table));
	foreach($fields as $field){
		$name=strtolower(trim($field['field']));
		$name=str_replace(' ','_',$name);
		$type=$field['type'];
		if(preg_match('/^_/',$name)){
			$type=preg_replace('/unsigned$/i','',trim($type));
			$type=trim($type);
			}
		if($field['null']=='NO'){$type .= ' NOT NULL';}
		else{$type .= ' NULL';}
		if($field['key']=='PRI'){$type .= ' Primary Key';}
		elseif($field['key']=='UNI'){$type .= ' UNIQUE';}
		if(strlen($field['default'])){
			$type .= " Default {$field['default']}";
			}
		if(strlen($field['extra'])){$type .= " {$field['extra']}";}
		if(strlen($field['comment'])){$type .= " COMMENT '{$field['comment']}'";}
		$current[$name]=$type;
        }
    $currentSet=$current;
    $ori_table=$table;
    /*
		MSSQL Syntax:
			ALTER TABLE table ALTER COLUMN column_name new_data_type
		NOTE: in MSSQL you cannot alter multiple columns with a single statement
	*/
    if(isMssql()){$table="[{$table}]";}
	$query = "alter table {$table} ";
	if(count($params)==0 && strlen($engine)){
    	$query .= " ENGINE = {$engine}";
		$query_result=@databaseQuery($query);
		if($query_result==true){return 1;}
		return 0;
	}
	ksort($params);
	$sets=array();
	$vsets=array();
	$changed=array();
	foreach($params as $field=>$type){
		//handle virtual generated fields shortcut
		//post_status varchar(25) GENERATED ALWAYS AS (JSON_EXTRACT(c, '$.id')),
		//post_status varchar(25) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.post_status'))),
		//alter table surveys_responses add location_code varchar(25) GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(response,'$.location_code'))) STORED
		if(preg_match('/^(.+?)\ from\ (.+?)$/i',$type,$m)){
			list($efield,$jfield)=preg_split('/\./',$m[2],2);
			if(!strlen($jfield)){$jfield=$field;}
			//quote the field if it contains colons or dashes
			if(preg_match('/[\:\-]+/',$jfield)){
				$jfield='"'.$jfield.'"';
			}
            $type="{$m[1]} GENERATED ALWAYS AS (TRIM(BOTH '\"' FROM json_extract({$efield},'$.{$jfield}'))) STORED";
		}
		if(isset($current[$field])){
			if(isWasqlField($field) && stringBeginsWith($current[$field],'int') && stringBeginsWith($type,'int')){
				unset($current[$field]);
				continue;
				}
			if(strtolower($current[$field]) != strtolower($type)){
				if(isPostgreSQL()){$sets[]="ALTER COLUMN {$field} TYPE {$type}";}
				else{
					$sets[]="modify {$field} {$type}";
					}
				$changed[$field]=$type;
				}
			unset($current[$field]);
			}
		else{
			$sets[]="add {$field} {$type}";
			$changed[$field]=$type;
        	}
    	}
    foreach($current as $field=>$type){
		array_push($sets,"drop {$field}");
    	}
    if(count($sets)==0 && !strlen($engine)){return "Nothing changed";}
    //echo "sets".printValue($sets);
	$query .= implode(",",$sets);
	if(strlen($engine) && (isMysql() || isMysqli())){$query .= " ENGINE = {$engine}";}
	$query_result=@databaseQuery($query);
	//vsets
	if(count($vsets)){
		$vquery = "alter table {$table} ";
		$vquery .= implode(",",$vsets);
		$vquery_result=@databaseQuery($vquery);
		if(!$vquery_result){
        	setWasqlError(debug_backtrace(),getDBError(),$vquery);
		}
	}
	//echo $query.printValue($query_result);
  	if($query_result==true){
		foreach($changed as $field=>$attributes){
        	instantDBMeta($ori_table,$field,$attributes);
		}
    	return 1;
  		}
  	else{
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	}
//---------- begin function getDBExpression
/**
* @describe gets field expression for json type fields
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBExpression($table,$field,$schema=''){
	global $CONFIG;
	if(!strlen($schema)){$schema=$CONFIG['dbname'];}
$query=<<<ENDOFSQL
	SELECT
		generation_expression as exp
	FROM
		information_schema.columns
	WHERE
		table_schema='{$schema}'
		and table_name='{$table}'
		and column_name='{$field}'
ENDOFSQL;
	$rec=getDBRecord(array('-query'=>$query));
	if(!isset($rec['exp'])){return '';}
	$rec['exp']=stripslashes($rec['exp']);
	//$rec['exp']=str_replace('_utf8mb4','',$rec['exp']);
	return $rec['exp'];
	//TRIM(BOTH '"' FROM json_extract(jdoc,'$.post_status'))
	if(preg_match('/json\_extract\((.+?)\,\'\$\.(.+?)\'\)/i',$rec['exp'],$m)){
    	return " from {$m[1]}.{$m[2]}";
	}
	return '';
}
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseAddMultipleTables($schemas=''){
	$lines=preg_split('/[\r\n]+/',trim($schemas));
	if(!count($lines)){
		return array(
			'status'=>'ERROR',
			'info'=>'No schema to process'
		);
	}
	$schemas=array();
	$ctable='';
	foreach($lines as $line){
		if(!preg_match('/^[\t\s]/',$line)){
        	$ctable=trim($line);
        	continue;
		}
		if(!strlen($ctable)){continue;}
		if(!strlen(trim($line))){continue;}
		$schemas[$ctable] .= trim($line) . "\r\n";
	}
	if(!count($schemas)){
		return array(
			'status'=>'ERROR',
			'info'=>'No tables found to process'
		);
	}
	$results=array();
	foreach($schemas as $table=>$fieldstr){
		$result=array('table'=>$table);
		unset($databaseCache['isDBTable'][$table]);
		if(isDBTable($table)){
			$ok=updateDBSchema($table,$fieldstr);
		}
		else{
			$ok=createDBTableFromText($table,$fieldstr);
		}
		if(!isNum($ok)){
			$result['status']='FAILED';
			$result['info']=$ok;
		}
		else{
			$result['status']='SUCCESS';
			$result['info']=nl2br($fieldstr);
		}
		$results[]=$result;
	}
	return $results;
}
//---------- begin function createDBTable--------------------
/**
* @describe creates table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=createDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function createDBTable($table='',$fields=array(),$engine=''){
	//echo $table.printValue($fields);exit;
	if(isPostgreSQL()){return postgresqlCreateDBTable($table,$fields);}
	elseif(isSqlite()){return sqliteCreateDBTable($table,$fields);}
	elseif(isOracle()){return oracleCreateDBTable($table,$fields);}
	elseif(isMssql()){return mssqlCreateDBTable($table,$fields);}
	global $databaseCache;
	$function='createDBTable';
	if(strlen($table)==0){return "createDBTable error: No table";}
	if(count($fields)==0){return "createDBTable error: No fields";}
	if(isDBTable($table)){return 0;}
	global $CONFIG;
	//verify the wasql fields are there. if not add them
	if(!isset($fields['_id'])){$fields['_id']=databasePrimaryKeyFieldString();}
	if(!isset($fields['_cdate'])){
		$fields['_cdate']=databaseDataType('datetime').databaseDateTimeNow();
	}
	if(!isset($fields['_cuser'])){$fields['_cuser']="INT NOT NULL";}
	if(!isset($fields['_edate'])){
		$fields['_edate']=databaseDataType('datetime')." NULL";
	}
	if(!isset($fields['_euser'])){$fields['_euser']="INT NULL";}
	//lowercase the tablename and replace spaces with underscores
	$table=strtolower(trim($table));
	$table=str_replace(' ','_',$table);
	$ori_table=$table;
	if(isMssql()){$table="[{$table}]";}
	$query="create table {$table} (";
	foreach($fields as $field=>$attributes){
		//handle virual generated json field shortcut
		if(preg_match('/^(.+?)\ from\ (.+?)$/i',$attributes,$m)){
			list($efield,$jfield)=preg_split('/\./',$m[2],2);
			if(!strlen($jfield)){$jfield=$field;}
			//quote the field if it contains colons or dashes
			if(preg_match('/[\:\-]+/',$jfield)){
				$jfield='"'.$jfield.'"';
			}
            $attributes="{$m[1]} GENERATED ALWAYS AS (TRIM(BOTH '\"' FROM json_extract({$efield},'$.{$jfield}'))) STORED";
		}
		//lowercase the fieldname and replace spaces with underscores
		$field=strtolower(trim($field));
		$field=str_replace(' ','_',$field);
		$query .= "{$field} {$attributes},";
   	}
    $query=preg_replace('/\,$/','',$query);
    $query .= ")";
    if(strlen($engine) && (isMysql() || isMysqli())){$query .= " ENGINE = {$engine}";}
    //echo $query.'<hr><hr>'.PHP_EOL;
	$query_result=@databaseQuery($query);
	//clear the cache
	clearDBCache(array('databaseTables','getDBFieldInfo','isDBTable'));
  	if(!isset($query_result['error']) && $query_result==true){
		//success creating table.  Now to through the fields and create any instant meta data found
		foreach($fields as $field=>$attributes){
        	instantDBMeta($ori_table,$field,$attributes);
		}
		return 1;
	}
  	else{
		return setWasqlError(debug_backtrace(),getDBError(),$query);
	}
}
//---------- begin function createDBTableFromText ----
/**
* @author slloyd
* @describe creates specified table by parsing specified text for field names and properties
* @param table string - name of table to alter
* @param text string - table fields. one per line
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=createDBTableFromText($table,$fieldstr);
 */
function createDBTableFromText($table,$fieldstr){
	if(isDBTable($table)){
		return "{$table} already exists";
	}
	$lines=preg_split('/[\r\n]+/',trim($fieldstr));
	if(!count($lines)){
		return "no fields defined for {$table}";
	}
	//common fields to all wasql tables
	$cfields=array(
		'_id'	=> databasePrimaryKeyFieldString(),
		'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'=> "int NOT NULL",
		'_edate'=> databaseDataType('datetime')." NULL",
		'_euser'=> "int NULL",
		);
	$fields=array();
	$errors=array();
	foreach($lines as $line){
		$line=trim($line);
		if(!strlen($line)){continue;}
		list($name,$type)=preg_split('/[\s\t]+/',$line,2);
		if(!strlen($type)){
			$errors[]="Missing field type for {$line}";
			}
        elseif(!strlen($name)){
			$errors[]="Invalid line: {$line}";
			}
		else{$fields[$name]=$type;}
        }
    if(count($errors)){
		return "Field errors for {$table}:".printValue($errors);
		}
	//add common fields
	foreach($cfields as $key=>$val){$fields[$key]=$val;}
    $ok = createDBTable($table,$fields);
    return $ok;
}
//---------- begin function createDBTableFromFile--------------------
/**
* @describe creates a new table from the data in given file
* @param file string - full path to file to import
* @param params array - list of field/attributes to add
* @return info array - returns an array with filename and count values
* @usage
*	$ok=createDBTableFromFile($afile);
*/
function createDBTableFromFile($afile,$params=array()){
	if(!isset($params['-delimiter'])){$params['-delimiter']=',';}
	if(!isset($params['-enclosure'])){$params['-enclosure']='"';}
	if(!isset($params['-escape'])){$params['-escape']="\\";}
	if(!isset($params['-table'])){$params['-table']=getFileName($afile,1);}
	$params['-table']=preg_replace('/[^a-z0-9\-\ \_]+/','',strtolower($params['-table']));
	$params['-table']=preg_replace('/[\-\ \_]+/','_',$params['-table']);
	//only allow csv and txt files
	$ext=getFileExtension($afile);
	switch(strtolower($ext)){
    	case 'txt':$params['-delimiter']="\t";break;
	}
	//get the fields
	$fields=array(
		'_id'	=> databasePrimaryKeyFieldString(),
		'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'=> "int NOT NULL",
		'_edate'=> databaseDataType('datetime')." NULL",
		'_euser'=> "int NULL",
	);
	//$lines=file($afile);echo printValue($lines);
	$handle = fopen_utf8($afile, "rb");
	if(!$handle){return 'Unable to open file';}
	$removefirst=false;
	if(!isset($params['-fields']) || !is_array($params['-fields'])){
		$removefirst=true;
		$params['-fields'] = fgetcsv($handle, 9000, $params['-delimiter'],$params['-enclosure'],$params['-escape']);
	}
	//clean up the field names
	foreach($params['-fields'] as $i=>$field){
    	$field=preg_replace('/[^a-z0-9\_\ ]+/','',trim(strtolower($field)));
    	$field=preg_replace('/\ +/','_',$field);
    	$params['-fields'][$i]=$field;
	}
	//determine the datatype and length of each field
	$params['-stats']=array();
	$params['count']=0;
	while (($line = fgetcsv($handle, 9000, $params['-delimiter'],$params['-enclosure'],$params['-escape'])) !== false) {
		$params['count']+=1;
		$cnt=count($line);
		for($x=0;$x<$cnt;$x++){
        	$key=$params['-fields'][$x];
        	$val=$line[$x];
        	$len=strlen($val);
        	//min
        	if(!isset($params['-stats'][$key]['min']) || $len < $params['-stats'][$key]['min']){
            	$params['-stats'][$key]['min']=$len;
			}
			//max
        	if(!isset($params['-stats'][$key]['max']) || $len > $params['-stats'][$key]['max']){
            	$params['-stats'][$key]['max']=$len;
			}
			//types
			if(isNum($val)){
				if(stringContains($val,'.')){
                	//real
                	if(!isset($params['-stats'][$key]['types']) || !in_array('real',$params['-stats'][$key]['types'])){
						$params['-stats'][$key]['types'][]='real';
					}
					$parts=preg_split('/\./',$val,2);
					if(!isset($params['-stats'][$key]['decimals']) || strlen($parts[1]) > $params['-stats'][$key]['decimals']){
		            	$params['-stats'][$key]['decimals']=strlen($parts[1]);
					}
				}
				elseif(!isset($params['-stats'][$key]['types']) || !in_array('int',$params['-stats'][$key]['types'])){
					$params['-stats'][$key]['types'][]='int';
				}
			}
			elseif(isDateTime($val) && !in_array('datetime',$params['-stats'][$key]['types'])){$params['-stats'][$key]['types'][]='datetime';}
			elseif(isDate($val) && !in_array('date',$params['-stats'][$key]['types'])){$params['-stats'][$key]['types'][]='date';}
			elseif(!in_array('varchar',$params['-stats'][$key]['types'])){$params['-stats'][$key]['types'][]='varchar';}
			//null
			if(!$len){$params['-stats'][$key]['nulls']=1;}
		}
	}
	//setup fields
	foreach($params['-stats'] as $field=>$stat){
    	if(count($stat['types'])==1){
			$type=$stat['types'][0];
		}
    	elseif($stat['max'] <= 10){$type='char';}
    	elseif($stat['max'] > 65535){$type='mediumtext';}
    	elseif($stat['max'] > 255){$type='text';}
    	else{$type='varchar';}
    	switch($type){
        	case 'int':
				$type='integer';
        	break;
        	case 'real':
        		$len=roundToNearestMultiple($stat['max'],5);
        		if($len==0){$len=5;}
        		$dec=roundToNearestMultiple($stat['decimals'],2);
        		$type="real({$len},{$dec})";
        	break;
        	case 'char':
        	case 'varchar':
        		$len=roundToNearestMultiple($stat['max'],25);
        		if($len==0){$len=10;}
        		$type="{$type}({$len})";
        	break;
		}
		//if($stat['nulls']==1){$type.=' NULL';}
		//else{$type.=' NOT NULL';}
		$fields[$field]=$type;
	}
	//unset($params['-stats']);
	$params['-schema']=$fields;
	ksort($params['-schema']);
	rewind($handle);
	if($removefirst){
		//remove the first line again.
		fgetcsv($handle, 9000, $params['-delimiter'],$params['-enclosure'],$params['-escape']);
	}
	//create the table
	if(!isDBTable($params['-table'])){
		$ok = createDBTable($params['-table'],$fields);
			if(!isNum($ok)){
	    	$params['error']=$ok;
	    	return $params;
		}
	}
	else{
		if(isset($params['-clean']) && $params['-clean']){truncateDBTable($params['-table']);}
		$ok=alterDBTable($params['-table'],$fields);
		if($ok != 1 && $ok != 'Nothing changed'){
			$params['error']=$ok;
	    	return $params;
		}
	}
	//load the data
	while (($line = fgetcsv($handle, 9000, $params['-delimiter'],$params['-enclosure'],$params['-escape'])) !== false) {
		$cnt=count($line);
		$opts=array();
		for($x=0;$x<$cnt;$x++){
			if(isset($params['-fields'][$x])){
	        	$key=$params['-fields'][$x];
	        	$val=trim($line[$x]);
	        	if(strlen($val)){$opts[$key]=$val;}
			}
		}
		if(count($opts)){
        	$opts['-table']=$params['-table'];
        	$id=addDBRecord($opts);
        	if(!isNum($id)){
            	$params['-errors'][]=$id.printValue($opts);
            	echo printValue($params);exit;
			}
		}
	}
	fclose($handle);
	ksort($params);
	return $params;
}
//---------- begin function roundToNearestMultiple--------------------
/**
* @describe 50 outputs 50, 52 outputs 55, 50.25 outputs 55
* @param num number - number to round
* @param multiple integer - round up to what? defaults to 5
* @return num integer - 50 outputs 50, 52 outputs 55, 50.25 outputs 55
* @usage
*	$ok=roundToNearestMultiple(7,5); - returns 10
*/
function roundToNearestMultiple($n,$x=5) {
    return (ceil($n)%$x === 0) ? ceil($n) : round(($n+$x/2)/$x)*$x;
}
//---------- begin function insertDBFile
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function insertDBFile($params,$e=false){
	global $USER;
	if(!is_array($params) && isNum($params)){
    	$id=$params;
    	$params=array('_id'	=> $id,'-edit'=>$e);
	}
	$fields=getDBFields('_files',1);
	$getopts=array('-table'=>"_files");
	foreach($fields as $field){
    	if(isset($params[$field])){$getopts[$field]=$params[$field];}
	}
	$rec=getDBRecord($getopts);
	if(!is_array($rec)){return null;}
	$rtn='';
	if(preg_match('/^image/i',$rec['file_type'])){
    	//image
    	if(!isset($params['width']) && isNum($rec['file_width'])){$params['width']=$rec['file_width'];}
    	if(!isset($params['height']) && isNum($rec['file_height'])){$params['height']=$rec['file_height'];}
    	if(!isset($params['border'])){$params['border']=0;}
		if(!isset($params['class'])){$params['class']='w_middle';}
		$rtn .= '<img src="'.$rec['file'].'" ';
		$rtn .= setTagAttributes($params);
		$rtn .= '>';
		//build an html5 upload window to replace this image - exclude IE since they do not support it.
		if(isset($params['-edit']) && $params['-edit']==true && $_SERVER['REMOTE_BROWSER'] != 'msie'){
			$path=getFilePath($rec['file']);
        	$rtn .= '<div id="fileupload" data-behavior="fileupload" _table="_files" _action="EDIT" _onsuccess="location.reload(true);" file_remove="1" _id="'.$rec['_id'].'"  path="'.$path.'" style="width:'.round(($params['width']-5),0).'px;height:30px;border:1px inset #000;background:#eaeaea;">Upload to Replace</div>'.PHP_EOL;
		}
	}
	return $rtn;
}
//---------- begin function instantDBMeta
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function instantDBMeta($tablename,$fieldname,$attributes){
	if(stringContains($tablename,'.')){return false;}
	if(!isDBTable('_tabledata')){
		createWasqlTable('_tabledata');
	}
	if(!isDBTable('_fielddata')){
		createWasqlTable('_fielddata');
	}
	//skip if already exists
	if(getDBCount(array('-table'=>"_fielddata",'tablename'=>$tablename,'fieldname'=>$fieldname))){return 0;}
	//required value
	$required=0;
	if(preg_match('/NOT NULL/i',$attributes) && !preg_match('/Default/i',$attributes) && !preg_match('/bit\(1\)/i',$attributes)){
    	$required=1;
	}
	//defaultval value if any
	$defaultval='';
	if(preg_match('/default(.+)/i',$attributes,$m)){
		$defaultval=trim($m[1]);
    	$defaultval=preg_replace('/^[\'\"]/','',$defaultval);
    	$defaultval=preg_replace('/[\'\"]$/','',$defaultval);
	}
	switch(strtolower($fieldname)){
		case 'user_id':
		case 'users_id':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'inputtype'		=> "select",
				'required'		=> $required,
				'displayname'	=> "User",
				'defaultval'	=> $defaultval,
				'tvals'			=> "select _id from _users order by firstname,lastname,_id",
				'dvals'			=> "select firstname,lastname from _users order by firstname,lastname,_id"
				));
			return 1;
			break;
		case 'state':
		case 'state_code':
			if(preg_match('/char\(2\)/i',$attributes)){
				$id=addDBRecord(array('-table'=>'_fielddata',
					'tablename'		=> $tablename,
					'fieldname'		=> $fieldname,
					'inputtype'		=> "select",
					'required'		=> $required,
					'defaultval'	=> $defaultval,
					'tvals'			=> '<?='.'wasqlGetStates();'.'?>',
					'dvals'			=> '<?='.'wasqlGetStates(1);'.'?>'
					));
			}
			return 1;
			break;
		case 'country':
		case 'country_code':
			if(preg_match('/char\(2\)/i',$attributes)){
				$id=addDBRecord(array('-table'=>'_fielddata',
					'tablename'		=> $tablename,
					'fieldname'		=> $fieldname,
					'inputtype'		=> "select",
					'required'		=> $required,
					'defaultval'	=> 'US',
					'onchange'		=> "redrawField('state',this);",
					'tvals'			=> '<?='.'wasqlGetCountries();'.'?>',
					'dvals'			=> '<?='.'wasqlGetCountries(1);'.'?>'
					));
			}
			return 1;
			break;
		case 'email':
		case 'email_address':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'text',
				'width'			=> '220',
				'inputmax'		=> 255,
				'defaultval'	=> $defaultval,
				'mask'			=> 'email',
				));
			return 1;
			break;
		case 'body':
		case 'comments':
		case 'notes':
		case 'message':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'textarea',
				'width'			=> '600',
				'height'		=> '200',
				'defaultval'	=> $defaultval
				));
			return 1;
			break;
		case 'signature':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'signature',
				'width'			=> 400,
				'height'		=> 125,
				'defaultval'	=> '',
				));
			return 1;
		break;
		case 'whiteboard':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'whiteboard',
				'width'			=> 600,
				'height'		=> 300,
				'defaultval'	=> '',
				));
			return 1;
		break;
		case 'zip':
		case 'zipcode':
		case 'zip_code':
		case 'postalcode':
		case 'postal_code':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'text',
				'width'			=> '80',
				'inputmax'		=> 15,
				'defaultval'	=> $defaultval
				));
			return 1;
			break;
		case 'active':
			$id=addDBRecord(array('-table'=>'_fielddata',
				'tablename'		=> $tablename,
				'fieldname'		=> $fieldname,
				'required'		=> $required,
				'inputtype'		=> 'checkbox',
				'defaultval'	=> $defaultval,
				'tvals'			=> '1',
				));
			return 1;
			break;
	}
	//check for tinyint fields
	if(preg_match('/boolean/i',$attributes) || preg_match('/tinyint\(1\)/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'checkbox',
			'defaultval'	=> $defaultval,
			'tvals'			=> 1,
			));
		return 1;
	}
	//check for blob types
	if(preg_match('/(blob|text|mediumtext|smalltext|largetext)/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'textarea',
			'width'			=> 600,
			'height'		=> 200,
			'defaultval'	=> $defaultval,
			'tvals'			=> '1',
			));
		return 1;
	}
	//check for date and datetime datatypes
	if(preg_match('/datetime/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'datetime',
			'defaultval'	=> $defaultval,
			));
		return 1;
	}
	if(preg_match('/date/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'date',
			'defaultval'	=> $defaultval,
			));
		return 1;
	}
	if(preg_match('/(timestamp|integer|smallint|bigint)/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'text',
			'width'			=> 120,
			'defaultval'	=> $defaultval,
			'mask'			=> 'integer',
			));
		return 1;
	}
	if(preg_match('/time/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'time',
			'defaultval'	=> $defaultval,
			));
		return 1;
	}
	if(preg_match('/(float|real|decimal|number|numeric)/i',$attributes)){
		$id=addDBRecord(array('-table'=>'_fielddata',
			'tablename'		=> $tablename,
			'fieldname'		=> $fieldname,
			'required'		=> $required,
			'inputtype'		=> 'text',
			'width'			=> 100,
			'mask'			=> 'number',
			'defaultval'	=> $defaultval,
			));
		return 1;
	}
	return 0;
}
//---------- begin function buildDBPaging
/**
* @describe
* 	builds the html for data paging
*	requires you to pass in the result of getDBPaging so call that first
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function buildDBPaging($paging=array()){
	$rtn='';
	global $PAGE;
	if(!isset($paging['-filters'])){$paging['-filters']=1;}
	//action
	if(isset($paging['-action'])){$action=$paging['-action'];}
	elseif(preg_match('/\.php$/i',$PAGE['name'])){$action="/{$PAGE['name']}";}
	else{$action="/{$PAGE['name']}.phtm";}
	//_search
	if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){$paging['_search']=$_REQUEST['_search'];}
	//_sort
	if(isset($paging['-order'])){$paging['_sort']=$paging['-order'];}
	elseif(isset($_REQUEST['_sort']) && strlen($_REQUEST['_sort'])){$paging['_sort']=$_REQUEST['_sort'];}
	$start=isset($_REQUEST['start']) && isNum($_REQUEST['start'])?$_REQUEST['start']:0;
	//formname
	if(isset($paging['-formname'])){
		$formname=$paging['-formname'];
		$rtn .= '<input type="hidden" name="_start" value="'.$start.'">'.PHP_EOL;
	}
	elseif(isset($paging['-ajaxid'])){
		if(isset($paging['-pagingformname'])){$formname=$paging['-pagingformname'];}
		else{$formname='form_' . $paging['-ajaxid'];}
		$onsubmit=isset($paging['-onsubmit'])?$paging['-onsubmit']:"ajaxSubmitForm(this,'{$paging['-ajaxid']}');return false;";
		if(isset($paging['-bulkedit'])){$onsubmit="pagingAddFilter(this);pagingSetFilters(this);pagingClearBulkEdit(this);{$onsubmit}";}
		elseif(isset($paging['-filters'])){$onsubmit="pagingAddFilter(this);pagingSetFilters(this);{$onsubmit}";}
		$rtn .= buildFormBegin($action,array('-name'=>$formname,'-onsubmit'=>$onsubmit,'_start'=>$start));
	}
	else{
		if(isset($paging['-pagingformname'])){$formname=$paging['-pagingformname'];}
		else{$formname='s' . time();}
		$onsubmit=isset($paging['-onsubmit'])?$paging['-onsubmit']:'return true;';
		if(isset($paging['-filters'])){$onsubmit="pagingAddFilter(this);pagingSetFilters(this);{$onsubmit}";}
		$rtn .= buildFormBegin($action,array('-name'=>$formname,'-onsubmit'=>$onsubmit,'_start'=>$start));
	}
	//hide other inputs
	if(!isset($paging['-formname'])){
		$rtn .= '<div style="display:none;" id="'.$formname.'_inputs">'.PHP_EOL;
		foreach($paging as $pkey=>$pval){
			if(preg_match('/^\-/',$pkey)){continue;}
			if($pkey=='_action' && $pval=='multi_update'){continue;}
			if(preg_match('/^(x|y)$/i',$pkey)){continue;}
			if(preg_match('/^\_(start|id\_href|search|filters|bulkedit|export|viewfield)$/i',$pkey)){continue;}
			if(preg_match('/\_(onclick|href|eval|editlist)$/i',$pkey)){continue;}
			$rtn .= '	<textarea name="'.$pkey.'">'.$pval.'</textarea>'.PHP_EOL;
	    	}
	    $rtn .= '</div>'.PHP_EOL;
	}
	//search?
	if(isset($paging['-search'])){
		if(isset($paging['-table'])){
			$fields=getDBFields($paging['-table'],1);
			if(isset($paging['-bulkedit']) && !isset($paging['-filters'])){$paging['-filters']=1;}
			if(isset($paging['-filters'])){
            	//new options to allow user to set multiple filters
            	$rtn .= '<div class="row padtop">'.PHP_EOL;
            	$rtn .= '<div style="display:none;">'.PHP_EOL;
				$rtn .= '	<textarea name="_filters">'.$paging['-filters'].'</textarea>'.PHP_EOL;
				if(isset($paging['-bulkedit'])){
					$rtn .= '	<input type="hidden" name="_bulkedit" value="" />'.PHP_EOL;
				}
				$rtn .= '	<input type="hidden" name="_export" value="" />'.PHP_EOL;
				$rtn .= '</div>'.PHP_EOL;
            	//$rtn .= '	<b>Filters:</b>'.PHP_EOL;
            	//fields
            	$vals=array('*'=>'Any Field');
				foreach($fields as $field){
			    	if(isWasqlField($field) && $field != '_id'){continue;}
			    	$vals[$field]=ucfirst($field);
				}
				$opts=array('class'=>'form-control input-sm');
				$rtn .= buildFormSelect('filter_field',$vals,$opts);
				//operaters
				$vals=array(
					'ct'	=> 'Contains',
					'nct'	=> 'Not Contains',
					'ca'	=> 'Contains Any of These',
					'nca'	=> 'Not Contain Any of These',
					'eq'	=> 'Equals',
					'neq'	=> 'Not Equals',
					'ea'	=> 'Equals Any of These',
					'nea'	=> 'Not Equals Any of These',
					'gt'	=> 'Greater Than',
					'lt'	=> 'Less Than',
					'egt'	=> 'Equals or Greater than',
					'elt'	=> 'Less than or Equals',
					'ib'	=> 'Is Blank',
					'nb'	=> 'Is Not Blank'
				);
				$opts=array('class'=>'form-control input-sm');
				$rtn .= buildFormSelect('filter_operator',$vals,$opts);
				//value
				$rtn .= '	<input name="filter_value" id="'.$formname.'_filter_value" type="text" placeholder="Value" class="w_form-control input-sm" />'.PHP_EOL;
				$rtn .= '	<button type="submit" class="btn w_btn-sm icon-search"> Search</button>'.PHP_EOL;
				$rtn .= '	<button type="button" class="btn w_btn-sm" title="Add Filter" onclick="pagingAddFilter(document.'.$formname.');"><span class="icon-filter-add w_big w_grey"></span></button>'.PHP_EOL;
				if(isset($paging['-bulkedit'])){
                	$rtn .= '	<button type="button" title="Bulk Edit" class="btn" onclick="pagingBulkEdit(document.'.$formname.');"><span class="icon-edit w_big w_danger w_bold"></span></button>'.PHP_EOL;
				}
				if(!isset($paging['-noexport'])){
                	$rtn .= '	<a href="#export" title="Export" class="icon-export w_primary" onclick="setProcessing(this);return pagingExport(document.'.$formname.');"> export</a>'.PHP_EOL;
                	//export
					if(isset($_REQUEST['_export']) && $_REQUEST['_export']==1){
			        	$where=getDBWhere($paging);
			        	$recs=getDBRecords(array(
			        		'-table'	=> $paging['-table'],
							'-where'	=> $where,
						));
						//check for eval
						$evals=array();
						foreach($paging as $pk=>$pv){
                        	if(preg_match('/^(.+)\_eval$/',$pk,$m)){
                            	$evals[$m[1]]=$pv;
							}
						}
						if(count($evals)){
							//echo printValue($evals);exit;
							foreach($recs as $i=>$rec){
								foreach($evals as $fld=>$evalstr){
									foreach($rec as $xfld=>$xval){
										if(is_array($xfld) || is_array($xval)){continue;}
										$replace='%'.$xfld.'%';
					                    $evalstr=str_replace($replace,$rec[$xfld],$evalstr);
					                	}
					                $val=evalPHP('<?' . $evalstr .'?>');
					                $recs[$i][$fld]=$val;
					                //echo $fld.$evalstr.$val;exit;
								}
							}
						}
						//echo $where.printValue($paging).printValue($recs);exit;
						if(is_array($recs)){
			        		$csv=arrays2csv($recs);
			        		$sha=sha1($where);
			        		$progpath=dirname(__FILE__);
			        		$file="{$params['-table']}_export_{$sha}.csv";
			        		$afile="{$progpath}/temp/{$file}";
			        		setFileContents($afile,$csv);
			        		$rtn .= '<a href="/php/temp/'.$file.'" class="icon-file-excel w_success w_bold">file</a>'.PHP_EOL;
			        		//exit;
						}
					}
				}
				$rtn .= '</div>'.PHP_EOL;
				$rtn .= '<div class="row" style="min-height:30px;max-height:90px;overflow:auto;">'.PHP_EOL;
				$rtn .= '	<div id="'.$formname.'_send_to_filters">'.PHP_EOL;
				if(strlen($paging['-filters']) && $paging['-filters'] != 1){
                	//field-oper-value
                	$sets=preg_split('/[\r\n]+/',$paging['-filters']);
                	foreach($sets as $set){
                    	list($field,$oper,$val)=preg_split('/\-/',$set,3);
                    	if($field=='null' || $val=='null'){continue;}
                    	$fid=$field.$oper.$val;
                    	$dfield=$field;
						if($dfield=='*'){$dfield='Any Field';}
                    	$doper=$oper;
						$dval="'{$val}'";
						switch($oper){
				        	case 'ct': $doper='Contains';break;
				        	case 'nct': $doper='Not Contains';break;
				        	case 'ca': $doper='Contains Any of These';break;
				        	case 'nca': $doper='Not Contain Any of These';break;
							case 'eq': $doper='Equals';break;
							case 'neq': $doper='Not Equals';break;
							case 'ea': $doper='Equals Any of These';break;
							case 'nea': $doper='Not Equals Any of These';break;
							case 'gt': $doper='Greater Than';break;
							case 'lt': $doper='Less Than';break;
							case 'egt': $doper='Equals or Greater than';break;
							case 'elt': $doper='Less than or Equals';break;
							case 'ib': $doper='Is Blank';$dval='';break;
							case 'nb': $doper='Is Not Blank';$dval='';break;
						}
						$dstr="{$dfield} {$doper} {$dval}";
                    	$rtn .= '<div class="w_pagingfilter" data-field="'.$field.'" data-operator="'.$oper.'" data-value="'.$val.'" id="'.$fid.'"><span class="icon-filter w_grey"></span> '.$dstr.' <span class="icon-cancel w_danger w_pointer" onclick="removeId(\''.$fid.'\');"></span></div>'.PHP_EOL;
					}
					$rtn .= '<div id="'.$formname.'_paging_clear_filters" class="w_pagingfilter icon-erase w_big w_danger" title="Clear All Filters" onclick="pagingClearFilters();"></div>'.PHP_EOL;
				}
				$rtn .= '	</div>'.PHP_EOL;
				$rtn .= '</div>'.PHP_EOL;
			}
			else{
				$indexes=getDBIndexes(array($paging['-table']));
				$opts=array();
				foreach($indexes as $rec){
					if(!in_array($rec['column_name'],$fields)){continue;}
					$tval=$rec['column_name'];
					$dval=str_replace('_',' ',$tval);
					$dval=ucwords(trim($dval));
	            	$opts[$tval]=$dval;
				}
				$rtn .= '<table class="w_nopad"><tr>'.PHP_EOL;
				$rtn .= '	<td>'.buildFormSelect('_searchfield',$opts,array('message'=>"-- any field --")).'</td>'.PHP_EOL;
				$rtn .= '	<td><input type="text" class="w_form-control" name="_search" onFocus="this.select();" style="width:200px;" value="'.requestValue('_search').'"></td>'.PHP_EOL;
				$rtn .= '	<td><button type="submit" class="btn icon-search">Search</button></td>'.PHP_EOL;
				$rtn .= '</tr></table>'.PHP_EOL;
			}
		}
		else{
        	$rtn .= '<input type="text" class="w_form-control" name="_search" onFocus="this.select();" style="width:250px;" value="'.requestValue('_search').'">'.PHP_EOL;
			$rtn .= buildFormSubmit('Search');
		}

		if(isset($paging['-daterange']) && $paging['-daterange']==1){
			$rangeid='dr'.time();
			$rtn .= '<table class="w_nopad"><tr><td>'.PHP_EOL;
			$checked=(isset($_REQUEST['date_range']) && $_REQUEST['date_range']==1)?' checked':'';
			$rtn .= '<div style="font-size:9pt;"><input type="checkbox" name="date_range" value="1"'.$checked.' onClick="showHide(\''.$rangeid.'\',this.checked);"> Filter by Date Range</div>'.PHP_EOL;
			if(strlen($checked)){
				$rtn .= '<div style="font-size:9pt;" align="center" id="'.$rangeid.'">'.PHP_EOL;
				}
			else{
				$rtn .= '<div style="font-size:9pt;display:none;" align="center" id="'.$rangeid.'">'.PHP_EOL;
	        	}
			$rtn .= '<table class="w_nopad">'.PHP_EOL;
			$rtn .= '	<tr>'.PHP_EOL;
			if(is_array($paging['-datefield'])){
				$paging['-formname']=$formname;
				$rtn .= '		<td>'.getDBFieldTag($paging['-datefield']).'</td>'.PHP_EOL;
	        	}
			$rtn .= '		<td>'.getDBFieldTag(array('-formname'=>$formname,'-table'=>'_users','inputtype'=>'date',"-field"=>'_cdate','name'=>'date_from')).'</td>'.PHP_EOL;
			$rtn .= '		<td>To</td>'.PHP_EOL;
			$rtn .= '		<td>'.getDBFieldTag(array('-formname'=>$formname,'-table'=>'_users','inputtype'=>'date',"-field"=>'_cdate','name'=>'date_to')).'</td>'.PHP_EOL;
			$rtn .= '	</tr>'.PHP_EOL;
			$rtn .= '</table>'.PHP_EOL;
			$rtn .= '</div>'.PHP_EOL;
			$rtn .= '</td></tr></table>'.PHP_EOL;
			}
    	}
    //limit?
    $onsubmit=isset($paging['-onsubmit'])?$paging['-onsubmit']:'';
    if(stringContains($onsubmit,'(this')){
    	$onsubmit=str_replace('(this',"(document.{$formname}",$onsubmit);
	}
	if(isset($paging['-limit'])){
		$rtn .= '<table class="w_nopad" ><tr class="w_middle">'.PHP_EOL;
		$rtn .= '	<th><div style="width:35px;">';
		if(isset($paging['-first'])){
			$arr=array();
			foreach($_REQUEST as $key=>$val){
				if(preg_match('/\_[0-9]+$/i',$key)){continue;}
				if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
				if(preg_match('/\_id$/i',$key)){continue;}
				if($key=='_fields' && preg_match('/\:/i',$val)){continue;}
				if($key=='_action' && $val=='multi_update'){continue;}
				$arr[$key]=$val;
	        	}
			$arr['_start']=$paging['-first'];
			$rtn .= '<button type="submit" onclick="setProcessing(this);document.'.$formname.'._start.value='.$paging['-first'].';'.$onsubmit.'" class="btn icon-first" title="first" style="margin:3px;font-size:1.4em;padding:0px;"></button>'.PHP_EOL;
            }
        $rtn .= '</div></th>'.PHP_EOL;
		$rtn .= '	<th><div style="width:35px;">';
		if(isset($paging['-prev'])){
			$arr=array();
			foreach($_REQUEST as $key=>$val){
				if(preg_match('/\_[0-9]+$/i',$key)){continue;}
				if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
				if(preg_match('/\_id$/i',$key)){continue;}
				if($key=='_fields' && preg_match('/\:/i',$val)){continue;}
				if($key=='_action' && $val=='multi_update'){continue;}
				$arr[$key]=$val;
	        	}
			$rtn .= '<button type="submit" onclick="setProcessing(this);document.'.$formname.'._start.value='.$paging['-prev'].';'.$onsubmit.'" class="btn icon-left" title="prev" style="margin:3px;font-size:1.4em;padding:0px;"></button>'.PHP_EOL;
            }
        $rtn .= '</div></th>'.PHP_EOL;

        if(isset($paging['-text'])){
            $rtn .= '		<td align="center"><div class="w_paging">'.$paging['-text'].' records</div></td>'.PHP_EOL;
		}
        if(isset($paging['-next'])){
			$arr=array();
			foreach($_REQUEST as $key=>$val){
				if(preg_match('/\_[0-9]+$/i',$key)){continue;}
				if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
				if(preg_match('/\_id$/i',$key)){continue;}
				if($key=='_fields' && preg_match('/\:/i',$val)){continue;}
				if($key=='_action' && $val=='multi_update'){continue;}
				$arr[$key]=$val;
	        }
			//$rtn .= '<td><input type="image" onclick="document.'.$formname.'._start.value='.$paging['-next'].';'.$onsubmit.'" src="/wfiles/icons/next.png"></td>'.PHP_EOL;
			$rtn .= '<td><button type="submit" onclick="setProcessing(this);document.'.$formname.'._start.value='.$paging['-next'].';'.$onsubmit.'" class="btn icon-right" title="next" style="margin:3px;font-size:1.4em;padding:0px;"></button></td>'.PHP_EOL;
        }
        if(isset($paging['-last'])){
			$arr=array();
			foreach($_REQUEST as $key=>$val){
				if(preg_match('/\_[0-9]+$/i',$key)){continue;}
				if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
				if(preg_match('/\_id$/i',$key)){continue;}
				if($key=='_fields' && preg_match('/\:/i',$val)){continue;}
				if($key=='_action' && $val=='multi_update'){continue;}
				$arr[$key]=$val;
	        }
			//$rtn .= '<td><input type="image" onclick="document.'.$formname.'._start.value='.$paging['-last'].';'.$onsubmit.'" src="/wfiles/icons/last.png"></td>'.PHP_EOL;
			$rtn .= '<td><button type="submit" onclick="setProcessing(this);document.'.$formname.'._start.value='.$paging['-last'].';'.$onsubmit.'" class="btn icon-last" title="last" style="margin:3px;font-size:1.4em;padding:0px;"></button></td>'.PHP_EOL;
        }
        $rtn .= '</tr></table>'.PHP_EOL;
	}
	elseif(isset($paging['-text'])){
        $rtn .= '	<div class="w_paging">'.$paging['-text'].' records</div>'.PHP_EOL;
	}
	if(!isset($paging['-formname'])){
		$rtn .= buildFormEnd();
	}
	if(isset($paging['-search'])){
		if(isset($paging['-filters'])){
        	$rtn .= buildOnLoad("document.{$formname}.filter_value.focus();");
		}
		else{
			$rtn .= buildOnLoad("document.{$formname}._search.focus();");
		}
	}
	return $rtn;
	}
//---------- begin function buildDBProgressChart
/**
* @describe builds an html progress chart based on database values
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function buildDBProgressChart($params=array()){
	if(!isParams(array('-table','-field'),$params)){return "buildDBProgressChart Error: missing required param";}
	$sum=setValue($params['-sum'],"sum({$params['-field']})");
	$where=buildDBWhere($params);
	//return $target . '<hr>' . $where;
	$query="select {$sum} as qsum from {$params['-table']} $where";
	$recs=getDBRecords(array('-query'=>$query));
	$sum=0;
	if(is_array($recs)){
		if(isParams(array('error','query'),$recs)){return printValue($recs);}
		foreach($recs as $rec){$sum += $rec['qsum'];}
    	}
    //build the progress control
    $rtn='';
    $background=setValue($params['-background'],'#c4c5c6');
    $forground=setValue($params['-forground'],'#007100');
    $color=setValue($params['-color'],'#FFFFFF');
	$border=setValue($params['-border'],'1px solid #000000');
	$direction=strtolower(setValue($params['-direction'],'horizontal'));
	$format=setValue($params['-format'],'integer');
	$id=setValue($params['-id'],'w_progresschart');
	if($direction=='horizontal'){
		//horizontal bar
		$height=setValue($params['-height'],20);
		$width=setValue($params['-width'],200);
		//if target is specified
		$rtn .= '<div id="'.$id.'">'.PHP_EOL;
		$rtn .= '	<div>'.PHP_EOL;
		if(isset($params['-addlink'])){
			$rtn .= '		<a title="Add" href="#" onclick="'.$params['-addlink'].'return false;"><img src="/wfiles/iconsets/16/plus.png" alt="add" /></a>'.PHP_EOL;
			}
		if(isset($params['-listlink'])){
			$rtn .= '		<a title="List" href="#" onclick="'.$params['-listlink'].'return false;"><img src="/wfiles/iconsets/16/list.png" alt="list" /></a>'.PHP_EOL;
			}
		if(isset($params['-title'])){$rtn .= '		<b>'.$params['-title'].'</b>'.PHP_EOL;}
		$rtn .= '	</div>'.PHP_EOL;
		if(isNum($params['-target'])){
			$pcnt=round(($sum/$params['-target']),2);
			$pwidth=round($pcnt*$width,0);
			if($format=='money'){$sum="$" . formatMoney($sum);}
			$rtn .= '<div style="position:relative;width:'."{$width}px;height:{$height}px;border:{$border};background:{$background};".'">'.PHP_EOL;
			$rtn .= '	<div style="position:absolute;left:0px;bottom:0px;width:'."{$pwidth}px;background:{$forground};height:{$height}px;color:{$color}".'" align="center">'.$sum.'</div>'.PHP_EOL;
			$rtn .= '</div>'.PHP_EOL;
			}
		else{
			$pcnt=round(($sum/$width),2);
			$pwidth=round($pcnt*$width,0);
			$rtn .= '	<div style="width:'."{$pwidth}px;background:{$forground};height:{$height}px;".'"></div>'.PHP_EOL;
			}
		$rtn .= '</div>'.PHP_EOL;
    	}
    else{
		//vertical bar
		$height=setValue($params['-height'],200);
		$width=setValue($params['-width'],20);
		$rwidth=$width+20;
		$rtn .= '<div id="'.$id.'" style="position:relative;width:'.$rwidth.'px;height:'.$height.'px;">'.PHP_EOL;
		$bottom=0;
		if(isset($params['-listlink'])){
			$rtn .= '		<div style="position:absolute;bottom:'.$bottom.'px;left:2px;height:18px;width:18px;"><a title="List" href="#" onclick="'.$params['-listlink'].'return false;"><img src="/wfiles/iconsets/16/list.png" alt="list" /></a></div>'.PHP_EOL;
			$bottom+=18;
			}
		if(isset($params['-addlink'])){
			$rtn .= '		<div style="position:absolute;bottom:'.$bottom.'px;left:2px;height:18px;width:18px;"><a title="Add" href="#" onclick="'.$params['-addlink'].'return false;"><img src="/wfiles/iconsets/16/plus.png" alt="add" /></a></div>'.PHP_EOL;
			$bottom+=18;
			}
		if(isset($params['-title'])){
			$pheight=$height-$bottom;
			$rtn .= '		<div style="position:absolute;bottom:'.$bottom.'px;left:2px;height:'.$pheight.'px;width:18px;"><b class="w_rotatetext w_bold">'.$params['-title'].'</b></div>'.PHP_EOL;
			$bottom+=18;
			}
		//if target is specified
		if(isNum($params['-target'])){
			$pcnt=round(($sum/$params['-target']),2);
			$pheight=round($pcnt*$height,0);
			$rtn .= '<div id="progresscontrol" style="position:absolute;bottom:0px;left:20px;width:'."{$width}px;height:{$height}px;border:{$border};background:{$background};".'">'.PHP_EOL;
			$rtn .= '	<div style="position:absolute;bottom:0px;left:0px;width:'."{$width}px;background:{$forground};height:{$pheight}px;".'"></div>'.PHP_EOL;
			$rtn .= '</div>'.PHP_EOL;
			}
		else{
			$pcnt=round((($sum/$width)*100),2);
			$pwidth=round($pcnt*$width,0);
			$rtn .= '	<div style="width:'."{$width}px;background:{$forground};height:{$pheight}px;".'"></div>'.PHP_EOL;
			}
		$rtn .= '</div>'.PHP_EOL;
    	}
    return $rtn;
	}
//---------- begin function buildDBWhere
/**
* @describe returns the where clause including the where text based on params.
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function buildDBWhere($params=array()){
	$info=getDBFieldInfo($params['-table']);
	$query='';
	if(isset($params['-where'])){$query .= $params['-where'];}
	else{
		$ands=array();
		/* Filter the query based on params */
		foreach($params as $key=>$val){
			//skip keys that begin with a dash
			if(preg_match('/^\-/',$key)){continue;}
			if(!isset($info[$key]['_dbtype'])){continue;}
			if($info[$key]['_dbtype'] =='int' || $info[$key]['_dbtype'] =='real'){
				$ands[] = "{$key}=$val";
				}
			else{
				//like
				$ands[] = "{$key} = '{$val}'";
	        	}
	    	}
	    if(count($ands)){$query .= implode(" and ",$ands);}
	    }
    if(isset($params['-search'])){
		if(preg_match('/^where (.+)/i',$params['-search'],$smatch)){
			$query .= ' and ('.$smatch[1].')';
            }
		else{
			$ors=array();
			foreach(array_keys($info) as $field){
				if(preg_match('/(int|real)/',$info[$field]['_dbtype'])){
					if(isNum($params['-search'])){}
                	}
                else{array_push($ors,"{$field} like '%{$params['-search']}%'");}
				}
			if(count($ors)){
				$query .= ' and ('.implode(' or ',$ors).')';
            	}
            }
        }
    if(strlen($query)){$query=" where {$query}";}
    return $query;
	}
//---------- begin function delDBRecord--------------------
/**
* @describe deletes records in table that match -where clause
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
*	[-trigger] boolean - set to false to disable trigger functionality
* @return boolean
* @usage
*	$id=delDBRecord(array(
*		'-table'		=> '_tabledata',
*		'-where'		=> "_id=4"
*	));
*/
function delDBRecord($params=array()){
	if(isPostgreSQL()){return postgresqlDelDBRecord($table,$fields);}
	elseif(isSqlite()){return sqliteDelDBRecord($table,$fields);}
	elseif(isOracle()){return oracleDelDBRecord($table,$fields);}
	elseif(isMssql()){return mssqlDelDBRecord($table,$fields);}
	$function='delDBRecord';
	if(!isset($params['-table'])){return 'editDBRecord Error: No table';}
	if(!isset($params['-where'])){return 'editDBRecord Error: No where';}
	$table=$params['-table'];
	//trigger
	if(!isset($params['-trigger']) || ($params['-trigger'])){
		$trigger=getDBTableTrigger($table);
		$trigger_table=$table;
	}
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$params['-dbname']=array_shift($table_parts);
		$trigger_table=implode('.',$table_parts);
	}
	//echo printValue($trigger);exit;
	if(isset($trigger['functions']) && !isset($params['-notrigger']) && strlen(trim($trigger['functions']))){
    	$ok=includePHPOnce($trigger['functions'],"{$trigger_table}-trigger_functions");
    	//look for Before trigger
    	if(function_exists("{$trigger_table}DeleteBefore")){
			$trigger['check']=1;
			unset($params['-error']);
        	$params=call_user_func("{$trigger_table}DeleteBefore",$params);
        	if(isset($params['-error'])){
				debugValue($params['-error']);
				return $params['-error'];
			}
        	if(!isset($params['-table'])){return "{$trigger_table}DeleteBefore Error: No Table".printValue($params);}
		}
	}
	if(isMssql()){$table="[{$table}]";}
	$query="delete from {$table} where " . $params['-where'];
	// execute sql - return the number of rows affected
	$start=microtime(true);
	$query_result=@databaseQuery($query);
  	if($query_result){
		databaseFreeResult($query_result);
		if(!isset($params['-nolog']) || $params['-nolog'] != 1){logDBQuery($query,$start,$function,$params['-table']);}
    	if(isset($trigger['functions']) && !isset($params['-notrigger'])){
	    	//look for Success trigger
	    	if(function_exists("{$trigger_table}DeleteSuccess")){
				unset($params['-error']);
	        	$params=call_user_func("{$trigger_table}DeleteSuccess",$params);
	        	if(isset($params['-error'])){
					debugValue($params['-error']);
				}
			}
		}
    	return true;
  		}
  	else{
		$error=getDBError();
		if(isset($trigger['functions']) && !isset($params['-notrigger'])){
	    	//look for Failure trigger
	    	if(function_exists("{$trigger_table}DeleteFailure")){
				$params['-error']="No updates found";
	        	$params=call_user_func("{$trigger_table}DeleteFailure",$params);
			}
		}
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	}
//---------- begin function dropDBColumn--------------------
/**
* @describe drops the specified column(s)
* @param table string - name of table
* @param mixed - column(s) to drop
* @return boolean
* @usage $ok=dropDBColumn('comments','test');
* @usage $ok=dropDBColumn('comments',array('test','age'));
*/
function dropDBColumn($table,$columns){
	if(!isDBTable($table)){return "No such table: {$table}";}
	if(!is_array($columns)){$columns=array($columns);}
	/*
	Oracle:
		ALTER TABLE table_name DROP (column_name1, column_name2);
	MS SQL:
		ALTER TABLE table_name DROP COLUMN column_name1, column_name2
	MySql:
		ALTER TABLE table_name DROP column_name1, DROP column_name2;
	Postgre SQL
		ALTER TABLE table_name DROP COLUMN column_name1, DROP COLUMN column_name2;
	*/
	if(isMysql() || isMysqli()){
    	$query="ALTER TABLE {$table} ";
    	foreach($columns as $column){
        	$query .= " DROP {$column},";
		}
		$query=preg_replace('/\,$/','',trim($query));
	}
	elseif(isMssql()){
		$table="[{$table}]";
		$columnstr=implode(', ',$columns);
		$query="ALTER TABLE {$table} DROP COLUMN {$columnstr}";
	}
	elseif(isPostgreSQL()){
    	$query="ALTER TABLE {$table} ";
    	foreach($columns as $column){
        	$query .= " DROP COLUMN {$column},";
		}
		$query=preg_replace('/\,$/','',trim($query));
	}
	elseif(isOracle()){
		$columnstr=implode(', ',$columns);
		$query="ALTER TABLE {$table} DROP ({$columnstr})";
	}
	$result=executeSQL($query);
	if(isset($result['error'])){
		debugValue($result);
		return false;
    }
    return true;
}
//---------- begin function dropDBTable--------------------
/**
* @describe drops the specified table
* @param table string - name of table to drop
* @param [meta] boolean - also remove metadata in _fielddata and _tabledata tables associated with this table. defaults to true
* @return 1
* @usage $ok=dropDBTable('comments',1);
*/
function dropDBTable($table='',$meta=1){
	if(isPostgreSQL()){return postgresqlDropDBTable($table,$meta);}
	elseif(isSqlite()){return sqliteDropDBTable($table,$meta);}
	elseif(isOracle()){return oracleDropDBTable($table,$meta);}
	elseif(isMssql()){return mssqlDropDBTable($table,$meta);}
	if(!isDBTable($table)){return "No such table: {$table}";}

	//drop indexes first
	$recs=getDBIndexes(array($table));
	if(is_array($recs)){
		foreach($recs as $rec){
	    	$key=$rec['key_name'];
	    	if(strtolower($key)=='primary'){continue;}
	    	//echo "Index:{$table},{$key}<br>";
	    	$ok=dropDBIndex($key,$table);
		}
	}
	$result=executeSQL("drop table {$table}");
	if(isset($result['error'])){
		debugValue($result['error']);
        }
    if($meta){
		$ok=delDBRecord(array('-table'=>'_tabledata','-where'=>"tablename = '{$table}'"));
		$ok=delDBRecord(array('-table'=>"_fielddata",'-where'=>"tablename = '{$table}'"));
    	}
    return 1;
	}
//---------- begin function dumpDB--------------------
/**
* @describe performs a mysqldump and saves file in the sh/backups directory
* @param [table] string - name of table to limit dump to
* @return array - $dump['success'] on success
* @usage $dump=dumpDB();
*/
function dumpDB($table=''){
	global $CONFIG;
	$dump=array();
	$dump['path']=getWasqlPath('sh/backups');
	if(!is_dir($dump['path'])){buildDir($dump['path']);}
	$dump['file'] = $CONFIG['dbname'].'__' . date("Y-m-d_H-i-s")  . '.sql';
	$dump['afile']=isWindows()?"{$dump['path']}\\{$dump['file']}":"{$dump['path']}/{$dump['file']}";
	$version=getDBRecord("SHOW VARIABLES LIKE 'version'");
	list($v1,$v2,$v3)=preg_split('/\./',$version['value']);
	if(isset($CONFIG['backup_command'])){
		$dump['command'] = $CONFIG['backup_command'];
		if($v1 >=8 && ($v2>0 || $v3>=32)){
			$dump['command'] .= " --single-transaction=TRUE";
		}
		$dump['command'] .= " -h {$CONFIG['dbhost']}";
		if(strlen($CONFIG['dbuser'])){
			$dump['command'] .= " -u {$CONFIG['dbuser']}";
			}
		if(strlen($CONFIG['dbpass'])){
			if(stringContains($CONFIG['dbpass'],'$')){
				$dump['command'] .= " -p'{$CONFIG['dbpass']}'";	
			}
			else{
				$dump['command'] .= " -p\"{$CONFIG['dbpass']}\"";
			}
			
		}
		//if version 8 or greater
		if($v1 >=8){
			$dump['command'] .= " --set-gtid-purged=OFF --column-statistics=0";
		}
		$dump['command'] .= " --max_allowed_packet=128M {$CONFIG['dbname']}";
		if(strlen($table)){
			$dump['command'] .= " {$table}";
			$dump['file'] = $CONFIG['dbname'].'.'.$table.'_' . date("Y-m-d_H-i-s")  . '.sql';
			$dump['afile']=isWindows()?"{$dump['path']}\\{$dump['file']}":"{$dump['path']}/{$dump['file']}";
		}
	}
	elseif(isMysql() || isMysqli()){
		//mysqldump
		$dump['command'] = isWindows()?'mysqldump.exe':'mysqldump';
		if($v1 >=8 && ($v2>0 || $v3>=32)){
			$dump['command'] .= " --single-transaction=TRUE";
		}
		$dump['command'] .= " -h {$CONFIG['dbhost']}";
		if(strlen($CONFIG['dbuser'])){
			$dump['command'] .= " -u {$CONFIG['dbuser']}";
			}
		if(strlen($CONFIG['dbpass'])){
			if(stringContains($CONFIG['dbpass'],'$')){
				$dump['command'] .= " -p'{$CONFIG['dbpass']}'";	
			}
			else{
				$dump['command'] .= " -p\"{$CONFIG['dbpass']}\"";
			}
		}
		//if version 8 or greater
		if($v1 >=8){
			$dump['command'] .= " --set-gtid-purged=OFF --column-statistics=0";
		}
		$dump['command'] .= " --max_allowed_packet=128M {$CONFIG['dbname']}";
		if(strlen($table)){
			$dump['command'] .= " {$table}";
			$dump['file'] = $CONFIG['dbname'].'.'.$table.'_' . date("Y-m-d_H-i-s")  . '.sql';
			$dump['afile']=isWindows()?"{$dump['path']}\\{$dump['file']}":"{$dump['path']}/{$dump['file']}";
		}
	}
	elseif(isPostgreSQL()){
    	//PostgreSQL - pg_dump dbname > outfile
    	$dump['command'] = isWindows()?"pg_dump.exe":"pg_dump";
    	if(strlen($CONFIG['dbpass']) && strlen($CONFIG['dbuser'])){
			$dump['command'] .=" \"--dbname=postgresql://{$CONFIG['dbuser']}:{$CONFIG['dbpass']}@{$CONFIG['dbhost']}:5432/{$CONFIG['dbname']}\"";
		}
		else{
			$dump['command'] .= " -h {$CONFIG['dbhost']} -Fp -c";	
		}
		if(strlen($table)){
			$dump['command'] .= " -t {$table}";
			$dump['file'] = $CONFIG['dbname'].'.'.$table.'_' . date("Y-m-d_H-i-s")  . '.sql';
			$dump['afile']=isWindows()?"{$dump['path']}\\{$dump['file']}":"{$dump['path']}/{$dump['file']}";
		}
		
		
	}
	$dump['iswindows']=isWindows();
	if(!isWindows() || (isset($CONFIG['gzip']) && $CONFIG['gzip']==1)){
    	$dump['command'] .= " | gzip -9";
    	$dump['afile']=preg_replace('/\.sql$/i','.sql.gz',$dump['afile']);
	}
	$dump['command'] .= "  > \"{$dump['afile']}\"";
	//echo printValue($dump).printValue($CONFIG);exit;
	$dump['result']=cmdResults($dump['command']);
	if(is_file($dump['afile']) && !filesize($dump['afile'])){
    	unlink($dump['afile']);
	}
	//check for errors
	if(is_file($dump['afile'])){
		if($handle = fopen($dump['afile'],"r")){
			$sql .= fgets($handle);
			$sql .= fgets($handle);
			fclose($handle);
			if(preg_match('/^Usage\:/i',$sql)){
				$dump['error']=$sql;
				unlink($dump['afile']);
			}
			else{$dump['success']=1;}
		}
		else{
        	$dump['error']="unable to read sql file";
		}
	}
	else{$dump['error']='Unable to create database dump.'.printValue($dump);}
	//echo printValue($dump);exit;
	return $dump;
	}
//---------- begin function optimizeDB--------------------
/**
* @describe performs a mysqlcheck -o -v -h
* @return array
* @usage $ok=optimizeDB();
*/
function optimizeDB(){
	if(!isMysql() && !isMysqli()){
		//only supported in Mysql
		return false;
		}
	global $CONFIG;
	$progpath=dirname(__FILE__);
	$rtn=array();
	$rtn['command'] = "mysqlcheck -o -v -h {$CONFIG['dbhost']}";
	if(strlen($CONFIG['dbuser'])){
		$rtn['command'] .= " -u {$CONFIG['dbuser']}";
		}
	if(strlen($CONFIG['dbuser'])){
		if(stringContains($CONFIG['dbpass'],'$')){
			$rtn['command'] .= " -p'{$CONFIG['dbpass']}'";	
		}
		else{
			$rtn['command'] .= " -p\"{$CONFIG['dbpass']}\"";
		}
	}
	$rtn['command'] .= " {$CONFIG['dbname']}";
	ob_start();
	passthru($rtn['command']);
	$rtn['result'] = ob_get_contents();
	ob_end_clean();
	return $rtn;
	}
//---------- begin function editDBRecord--------------------
/**
* @describe adds record to table and returns the ID of record added
* @param params array
*	-table string - name of table
*	-where string - where clause to determine what record(s) to edit
*	[-trigger] boolean - set to false to disable trigger functionality
*	treats other params as field/value pairs to edit
* @return array
* @usage
*	$ok=editDBRecord(array(
*		'-table'	=> 'notes',
*		'-where'	=> "_id=3",
*		'title'		=> 'Test Note Title',
*		'category'	=> 'QA'
*	));
*/
function editDBRecord($params=array(),$id=0,$opts=array()){
	if(isPostgreSQL()){return postgresqlEditDBRecord($params,$id,$opts);}
	elseif(isSqlite()){return sqliteEditDBRecord($params,$id,$opts);}
	elseif(isOracle()){return oracleEditDBRecord($params,$id,$opts);}
	elseif(isMssql()){return mssqlEditDBRecord($params,$id,$opts);}
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && isDBTable($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	$function='editDBRecord';
	if(!isset($params['-table'])){return 'editDBRecord Error: No table <br>' . printValue($params);}
	if(!isset($params['-where'])){return 'editDBRecord Error: No where <br>' . printValue($params);}
	global $USER;
	$table=$params['-table'];
	//if($params['-table']=='_files'){echo printValue($params);exit;}
	
	//trigger
	if(!isset($params['-trigger']) || ($params['-trigger'])){
		$trigger=getDBTableTrigger($table);
		$trigger_table=$table;
	}
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$params['-dbname']=array_shift($table_parts);
		$trigger_table=implode('.',$table_parts);
	}
	if(isset($trigger['functions']) && !isset($params['-notrigger']) && strlen(trim($trigger['functions']))){
    	$ok=includePHPOnce($trigger['functions'],"{$trigger_table}-trigger_functions");
    	//look for Before trigger
    	if(function_exists("{$trigger_table}EditBefore")){
			$trigger['check']=1;
			unset($params['-error']);
        	$params=call_user_func("{$trigger_table}EditBefore",$params);
        	if(isset($params['-error'])){
				debugValue($params['-error']);
				return $params['-error'];
			}
        	if(!isset($params['-table'])){return "{$trigger_table}EditBefore Error: No Table".printValue($params);}
		}
	}
	//wpass?
	if($params['-table']=='_wpass'){
        $params=wpassEncryptArray($params);
	}
	//get field info for this table
	$info=getDBFieldInfo($params['-table']);
	if(!isset($params['-noupdate'])){
		if(isset($info['_euser'])){
			$params['_euser']=(function_exists('isUser') && isUser())?$USER['_id']:0;
	    }
	    if(isset($info['_edate'])){
			//$params['_edate']=date("Y-m-d H:i:s");
			$params['_edate']='NOW()';
	    }
	}
	/* Filter the query based on params */
	$updates=array();
	//if($params['-table']=='jsontest'){echo printValue($info);exit;}
	//check for virtual fields
	//echo "HERE".printValue($params);
	$jsonfields=array();
	foreach($info as $k=>$i){
    	if($i['_dbtype']=='json' && isset($params[$k])){
        	$jsonfields[]=$k;
        	if(is_array($params[$k])){
        		$params[$k]=json_encode($params[$k],JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
        	}
        	else{
        		$jval=json_decode($params[$k],true);
	        	if(!is_array($jval)){
	        		if(stringContains($params[$k],':')){
	        			$arr=preg_split('/\:/',$params[$k]);
	        			$params[$k]=json_encode($arr,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
	        		}
	        		else{
	        			$params[$k]=json_encode(array($params[$k]),JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
	        		}
	        	}
        	}
		}
	}
	//if($params['-table']=='test'){echo "HERE".printValue($jsonfields).printValue($params).printValue($info);exit;}
	if(count($jsonfields)){
		$jsonfields[]='_id';
		$recs=getDBRecords(array(
			'-table'=>$params['-table'],
			'-where'=>$params['-where'],
			'-fields'=>implode(',',$jsonfields)
		));
		if(is_array($recs) && isset($recs[0]) && count($recs) < 1000){
			foreach($recs as $i=>$rec){
				$jchanges=array();
				foreach($params as $key=>$val){
					if(isset($info[$key]['_dbextra']) && stringContains($info[$key]['_dbextra'],' generated')){
						unset($params[$key]);
			        	$j=getDBExpression($params['-table'],$info[$key]['_dbfield']);
			        	if(strlen($j)){
							$j=str_replace(' from ','',$j);
			            	$parts=preg_split('/\./',$j);
			            	$jfield=array_shift($parts);
			            	if(isset($info[$jfield])){
								$jkey=implode('.',$parts);
								$jchanges[$jfield][$jkey]=$val;
							}
						}
					}
				}
				//echo "jchanges".printValue($jchanges).printValue($_REQUEST);exit;
				if(count($jchanges)){
					$rchanges=array();
                	foreach($jchanges as $jfield=>$jchange){
                    	$jarray=json_decode(trim($rec[$jfield]),true);
                    	if(is_array($jarray)){
							foreach($jchange as $jkey=>$jval){
                        		$parts=preg_split('/\./',$jkey);
                        		switch(count($parts)){
									case 1:
										$jarray[$parts[0]]=$jval;
									break;
									case 2:$jarray[$parts[0]][$parts[1]]=$jval;break;
									case 3:$jarray[$parts[0]][$parts[1]][$parts[2]]=$jval;break;
									case 4:$jarray[$parts[0]][$parts[1]][$parts[2]][$parts[3]]=$jval;break;
									case 5:$jarray[$parts[0]][$parts[1]][$parts[2]][$parts[3]][$parts[4]]=$jval;break;
									case 6:$jarray[$parts[0]][$parts[1]][$parts[2]][$parts[3]][$parts[4]][$parts[5]]=$jval;break;
								}
							}
							$rchanges[$jfield]=json_encode($jarray,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
						}
					}
					//echo "rchanges".printValue($rchanges);exit;
					if(count($rchanges)){
                    	$rchanges['-table']=$params['-table'];
                    	$rchanges['-where']="_id={$rec['_id']}";
                    	$ok=editDBRecord($rchanges);
					}
				}
			}
		}
	}
	$json_sets=array();
	$json_removes=array();
	foreach($params as $key=>$val){
		//skip keys that begin with a dash
		if(preg_match('/^\-/',$key)){continue;}
		//ignore params that do not match a field
		if(!isset($info[$key]['_dbtype'])){
			if(isset($params["{$key}_field"])){
				$keyfield=$params["{$key}_field"];
				if(isset($info[$keyfield]['_dbtype']) && strtolower($info[$keyfield]['_dbtype'])=='json'){
					if(isNum($val)){
						$json_sets[$keyfield][]="'\$.{$key}',{$val}";
					}
					elseif(strtoupper($val)=='NULL'){
						$json_removes[$keyfield][]="\$.{$key}";
					}
					else{
						$val=str_replace("'","''",$val);
						$json_sets[$keyfield][]="'\$.{$key}','{$val}'";
					}
				}
			}
			//echo "HERE:{$key}={$val}, keyfield={$keyfield}<br>".printValue($info);exit;
			continue;
		}
		//expression fields derived from json fields
		if(isset($info[$key]['expression']) && preg_match('/json_extract\((.+?)\)/',$info[$key]['expression'],$m)){
			//user json_set to set expression fields
			//json_extract(`how_many`,_utf8mb4'$.ihop')
			//json_extract(`response`,'$.shopper_id')
			list($tfield,$jfield)=preg_split('/\,/',$m[1],2);
			$tfield=str_replace('`','',$tfield);
			$tfield=str_replace("'",'',$tfield);
			if(preg_match('/\'(.+?)\'/',$jfield,$j)){
				$jfield=$j[1];
			}
			$jfield=str_replace('`','',$jfield);
			$jfield=str_replace("'",'',$jfield);
			if(isNum($val)){
				$json_sets[$tfield][]="'{$jfield}',{$val}";
			}
			elseif(strtoupper($val)=='NULL'){
				$json_removes[$tfield][]=$jfield;
			}
			else{
				$val=str_replace("'","''",$val);
				$json_sets[$tfield][]="'{$jfield}','{$val}'";
			}
		}
		elseif(!is_array($val) && preg_match('/^<sql>(.+)<\/sql>$/i',$val,$pm)){
			array_push($updates,"{$key}={$pm[1]}");
			}
		else{
			$noticks=0;
			if(isset($info[$key.'_size'])){$opts[$field.'_size']=setValue(array($_REQUEST[$field.'_size'],strlen($_REQUEST[$field])));}
			if(($info[$key]['_dbtype']=='date')){
				if(preg_match('/(CURRENT|NOW|DATE|TIME)/i',$val)){
					$val=$val;
	            	$noticks=1;
				}
				else{
					if(preg_match('/^[0-9]{2,2}\-[0-9]{2,2}\-[0-9]{4,4}$/',$val)){$val=str_replace('-','/',$val);}
					if(!is_array($val) && !strlen(trim($val))){$val='NULLDATE';}
					elseif(preg_match('/^([a-z\_0-9]+)\(\)$/is',$val)){
		            	$val=$val;
		            	$noticks=1;
		            }
					else{$val=date("Y-m-d",strtotime($val));}
				}
			}
			elseif($info[$key]['_dbtype'] =='time'){
				if(preg_match('/(CURRENT|NOW|DATE|TIME)/i',$val)){
					$val=$val;
	            	$noticks=1;
				}
				else{
					if(is_array($val)){
						if($val[2]=='pm' && $val[0] < 12){$val[0]+=12;}
						elseif($val[2]=='am' && $val[0] ==12){$val[0]='00';}
						$val="{$val[0]}:{$val[1]}:00";
		            }
		            if(!is_array($val) && !strlen(trim($val))){$val='NULLDATE';}
		            elseif(preg_match('/^([a-z\_0-9]+)\(\)$/is',$val)){
		            	$val=$val;
		            	$noticks=1;
		            }
					else{$val=date("H:i:s",strtotime($val));}
				}
			}
			elseif($info[$key]['_dbtype'] =='datetime'){
				unset($dmatch);
				if(preg_match('/(CURRENT|NOW|DATE|TIME)/i',$val)){
					$val=$val;
	            	$noticks=1;
				}
				else{
					$newval='';
					if(is_array($val)){
						if(preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4,4})$/s',$val[0],$dmatch)){
							if(strlen($dmatch[1])==1){$dmatch[1]="0{$dmatch[1]}";}
							if(strlen($dmatch[2])==1){$dmatch[2]="0{$dmatch[2]}";}
							$newval=$dmatch[3] . "-" . $dmatch[1] . "-" . $dmatch[2];
						}
						else{$newval=$val[0];}
						if($val[3]=='pm' && $val[1] < 12){$val[1]+=12;}
						elseif($val[3]=='am' && $val[1] ==12){$val[1]='00';}
						if(!strlen(trim($val[1])) && !strlen(trim($val[2]))){$newval='NULLDATE';}
						else{
							$newval .=" {$val[1]}:{$val[2]}:00";
						}
		            }
		            else{$newval=$val;}
		            $val=$newval;
		            if(!is_array($val) && !strlen(trim($val))){$val='NULLDATE';}
		            elseif(preg_match('/^([a-z\_0-9]+)\(\)$/is',$val)){
		            	$val=$val;
		            	$noticks=1;
		            }
					else{$val=date("Y-m-d H:i:s",strtotime($val));}
				}
			}
			if($info[$key]['_dbtype'] =='int' || $info[$key]['_dbtype'] =='tinyint' || $info[$key]['_dbtype'] =='real'){
				if(is_array($val)){$val=(integer)$val[0];}
				if(strlen($val)==0){
					if(isset($info[$key]['_dbflags']) && strlen($info[$key]['_dbflags']) && stristr("not_null",$info[$key]['_dbflags'])){
						if(isset($info[$key]['default']) && strlen($info[$key]['default'])){
							$val=$info[$key]['default'];
						}
						else{$val=0;}
					}
					else{
						$val='NULL';
					}
				}
				array_push($updates,"{$key}=$val");
			}
			else{
				//echo "[{$key}]".printValue($val)."\n".PHP_EOL;
				if(is_array($val)){$val=implode(':',$val);}
				$val=databaseEscapeString($val);
				if(strlen($val)==0){
					if(isset($info[$key]['default']) && strlen($info[$key]['default'])){
						$val=$info[$key]['default'];
					}
					else{
						$val='NULL';
					}
				}
				if($val=='NULLDATE' || $val=='NULL'){
					array_push($updates,"{$key}=NULL");
				}
				elseif($noticks==1){
					array_push($updates,"{$key}={$val}");
				}
				else{
					array_push($updates,"{$key}='{$val}'");
				}
	        }
	        //add sha and size if needed
	        if(isset($info[$key.'_sha1']) && !isset($params[$key.'_sha1'])){
				$sha=sha1($val);
				array_push($updates,"{$key}_sha1='{$sha}'");
			}
			if(isset($info[$key.'_size']) && !isset($params[$key.'_size'])){
				$size=strlen($val);
				array_push($updates,"{$key}_size={$size}");
			}
	    }
    }
    if(count($json_sets)){
		//update surveys_responses set response=JSON_SET(response, '$.ihop', 4,'$.wendys',7,'$.abc',55) where _id=2
		foreach($json_sets as $tfield=>$sets){
			$updates[]="{$tfield}=json_set({$tfield},".implode(', ',$sets).")";
		}
	}
	if(count($json_removes)){
		//update test set data=json_remove(data,'$.b','$.c') where id=2
		foreach($json_removes as $tfield=>$sets){
			$updates[]="{$tfield}=json_remove({$tfield},".implode(', ',$sets).")";
		}
	}
    //return if no updates were found
	if(!count($updates)){
		if(isset($trigger['functions']) && !isset($params['-notrigger'])){
	    	//look for Failure trigger
	    	if(function_exists("{$trigger_table}EditFailure")){
				$params['-error']="No updates found";
	        	$params=call_user_func("{$trigger_table}EditFailure",$params);
			}
		}
		return 0;
	}
    $fieldstr=implode(', ',$updates);
    $table=$params['-table'];
    if(isMssql()){$table="[{$table}]";}
	$query="update {$table} set $fieldstr where " . $params['-where'];
	if(isset($params['-limit'])){$query .= ' limit '.$params['-limit'];}
	//echo "<div>{$query}</div>".PHP_EOL;
	// execute sql - return the number of rows affected
	if(isset($params['-echo'])){echo $query;}
	$start=microtime(true);
	$query_result=@databaseQuery($query);
  	if($query_result){
    	$id=databaseAffectedRows($query_result);
    	databaseFreeResult($query_result);
    	logDBQuery($query,$start,$function,$params['-table']);
    	//dirty the w_min cache if a template  or page record is updated
    	$w_min='';
    	switch(strtolower($params['-table'])){
    		case '_pages':
    		case '_templates':
    			//clean w_min files for this database
    			if(function_exists('minifyCleanMin')){
    				$ok=minifyCleanMin();
    			}
    		break;
    	}
    	//addDBHistory('edit',$params['-table'],$params['-where']);
    	//get table info
		$tinfo=getDBTableInfo(array('-table'=>$params['-table'],'-fieldinfo'=>0));
		if((isset($tinfo['websockets']) && $tinfo['websockets']==1) || isset($trigger['functions'])){
			$params['-records']=getDBRecords(array('-table'=>$table,'-where'=>$params['-where']));
		}
		//check for websockets
		if(isset($tinfo['websockets']) && $tinfo['websockets']==1){
        	loadExtras('websockets');
        	$wrec=$params;
        	$wrec['_action']='edit';
        	$wrec['where']=$params['-where'];
        	wsSendDBRecord($params['-table'],$wrec);
		}
    	if(isset($trigger['functions']) && !isset($params['-notrigger'])){
	    	//look for Success trigger
	    	if(function_exists("{$trigger_table}EditSuccess")){
				unset($params['-error']);

	        	$params=call_user_func("{$trigger_table}EditSuccess",$params);
	        	if(isset($params['-error'])){
					debugValue($params['-error']);
				}
			}
		}
    	return $id;
  		}
  	else{
		$error=getDBError();
		if(isset($trigger['functions']) && !isset($params['-notrigger'])){
	    	//look for Failure trigger
	    	if(function_exists("{$trigger_table}EditFailure")){
				$params['-error']=$error;
	        	$params=call_user_func("{$trigger_table}EditFailure",$params);
			}
		}
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  		}
	}
//---------- begin function editDBUser--------------------
/**
* @describe edits the specified _user record with specified parameters
* @param id integer - _user record ID to edit
* @param params array - field/value pairs to edit
* @return boolean
* @usage $ok=editDBUser(34,array('lastname'=>'Smith'));
*/
function editDBUser($id='',$opts=array()){
	if(isNum($id)){
		$editopts=array('-table'=>'_users','-where'=>"_id={$id}");
    	}
    else{
		$editopts=array('-table'=>'_users','-where'=>"username = '{$id}'");
    	}
    foreach($opts as $key=>$val){$editopts[$key]=$val;}
    return editDBRecord($editopts);
	}
//---------- begin function executeSQL--------------------
/**
* @describe execute a SQL statement. returns an array with 'error' on failure
* @param query string - query to execute
* @return array
* @usage $ok=executeSQL($query);
*/
function executeSQL($query=''){
	if(isPostgreSQL()){return postgresqlExecuteSQL($query);}
	elseif(isSqlite()){return sqliteExecuteSQL($query);}
	elseif(isOracle()){return oracleExecuteSQL($query);}
	elseif(isMssql()){return mssqlExecuteSQL($query);}
	$rtn=array();
	$rtn['query'] = $query;
	$function='executeSQL';
	$query_result=@databaseQuery($query);
  	if($query_result){
		$rtn['result']=$query_result;
		return $rtn;
  	}
  	else{
		//echo $query.printValue($query_result).getDBError();exit;
		if(function_exists('setWasqlError')){
			return setWasqlError(debug_backtrace(),getDBError(),$query);
		}
		else{
        	echo $query.printValue($query_result).getDBError();exit;
		}
  	}
}
//---------- begin function expandDBKey
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function expandDBKey($key='',$val='',$table='default'){
	if(!strlen(trim($key))){return null;}
	if(!strlen(trim($val))){return null;}
	$cachekey=$key.$val.$table;
	if(isset($_SERVER['_cache_']['expandDBKey'][$cachekey])){
		return $_SERVER['_cache_']['expandDBKey'][$cachekey];
		}
	unset($rmatch);
	if($table != 'default'){
		unset($tmp);
		$tmp=getDBRecord(array('-table'=>$table,'_id'=>$val));
		if(is_array($tmp)){
			foreach($tmp as $tkey=>$tval){
				if(preg_match('/^\_/',$tkey) || preg_match('/^(password|apikey|guid|utype)$/i',$tkey) || !strlen($tval)){unset($tmp[$tkey]);}
				}
			$tmp['_table']=$table;
			ksort($tmp);
			$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
			return $tmp;
			}
        }
	elseif(preg_match('/^(.+?)\_id$/',$key,$rmatch)){
		$rfield=$rmatch[1];
		$rfield2="{$rfield}s";
		$rfield3=preg_replace('/y$/i','ies',$rfield);
		$rfield4="_{$rfield}s";
		$list[$x][$key.'_ex']=$related[$key][$val];
		if(isDBTable($rfield)){
			unset($tmp);
			$tmp=getDBRecord(array('-table'=>$rfield,'_id'=>$val));
			if(is_array($tmp)){
				foreach($tmp as $tkey=>$tval){
					if(preg_match('/^\_/',$tkey) || !strlen($tval) || ($rfield=='_users' && preg_match('/^(password|apikey|guid|utype)$/i',$tkey))){unset($tmp[$tkey]);}
					}
				$tmp['_table']=$rfield;
				ksort($tmp);
				$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
				return $tmp;
				}
            }
        elseif(isDBTable($rfield2)){
			unset($tmp);
			$tmp=getDBRecord(array('-table'=>$rfield2,'_id'=>$val));
			if(is_array($tmp)){
				foreach($tmp as $tkey=>$tval){
					if(preg_match('/^\_/',$tkey) || !strlen($tval) || ($rfield2=='_users' && preg_match('/^(password|apikey|guid|utype)$/i',$tkey))){unset($tmp[$tkey]);}
					}
				$tmp['_table']=$rfield2;
				ksort($tmp);
				$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
				return $tmp;
				}
            }
        elseif($rfield3 !== $rfield && isDBTable($rfield3)){
			unset($tmp);
			$tmp=getDBRecord(array('-table'=>$rfield3,'_id'=>$val));
			if(is_array($tmp)){
				foreach($tmp as $tkey=>$tval){
					if(preg_match('/^\_/',$tkey) || !strlen($tval) || ($rfield3=='_users' && preg_match('/^(password|apikey|guid|utype)$/i',$tkey))){unset($tmp[$tkey]);}
					}
				$tmp['_table']=$rfield3;
				ksort($tmp);
				$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
				return $tmp;
				}
            }
        elseif(isDBTable($rfield4)){
			unset($tmp);
			$tmp=getDBRecord(array('-table'=>$rfield4,'_id'=>$val));
			if(is_array($tmp)){
				foreach($tmp as $tkey=>$tval){
					if(preg_match('/^\_/',$tkey) || !strlen($tval) || ($rfield4=='_users' && preg_match('/^(password|apikey|guid|utype)$/i',$tkey))){unset($tmp[$tkey]);}
					}
				$tmp['_table']=$rfield4;
				ksort($tmp);
				$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
				return $tmp;
				}
            }
        }
    elseif(preg_match('/^\_(cuser|euser)$/',$key,$rmatch)){
		unset($tmp);
		$tmp=getDBRecord(array('-table'=>'_users','_id'=>$val));
		if(is_array($tmp)){
			foreach($tmp as $tkey=>$tval){
				if(preg_match('/^\_/',$tkey) || preg_match('/^(password|apikey|guid|utype)$/i',$tkey) || !strlen($tval)){unset($tmp[$tkey]);}
				}
			$tmp['_table']="_users";
			ksort($tmp);
			$_SERVER['_cache_']['expandDBKey'][$cachekey]=$tmp;
			return $tmp;
			}
        }
    return null;
	}
//---------- begin function exportDBRecords ----------
/**
* @describe exports getDBRecords results into csv, tab, or xml format
* @param param array - same parameters as getDBRecords except for:
*	-format - csv,tab, or xml - defaults to csv
*	-filename - name of the exported file  - defaults to output
* @return file
*	file pushed to client
* @usage $ok=exportDBRecords(array('-table'=>"_users",'active'=>1));
*/
function exportDBRecords($params=array()){
	global $PAGE;
	global $USER;
	global $CONFIG;
	$idfield=isset($params['-id'])?$params['-id']:'_id';
    //determine sort
    $possibleSortVals=array($params['-order'],$params['-orderby'],$_REQUEST['_sort'],'none');
    $sort=setValue($possibleSortVals);
    if($sort=='none'){
		$sort='';
    	if(isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(isAdmin()){
				if(is_array($tinfo['sortfields'])){$sort=implode(',',$tinfo['sortfields']);}
				}
			else{
				if(is_array($tinfo['sortfields_mod'])){$sort=implode(',',$tinfo['sortfields_mod']);}
            	}
			}
		}
	if(strlen($sort)){$params['-order']=$sort;}
	if(isset($_REQUEST['_sort']) && !strlen(trim($_REQUEST['_sort']))){unset($_REQUEST['_sort']);}
	if(isset($_REQUEST['_sort'])){$params['-order']=$_REQUEST['_sort'];}
	if(isset($params['-list']) && is_array($params['-list'])){$list=$params['-list'];}
	else{
		if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
			$params['-search']=$_REQUEST['_search'];
        }
        if(isset($_REQUEST['_filters']) && strlen($_REQUEST['_filters'])){
			$params['-filters']=$_REQUEST['_filters'];
        }
        if(isset($_REQUEST['_searchfield']) && strlen($_REQUEST['_searchfield'])){
			$params['-searchfield']=$_REQUEST['_searchfield'];
        	}
        if(isset($_REQUEST['date_range']) && $_REQUEST['date_range']==1){
			$filterfield="_cdate";
			$wheres=array();
			if(isset($_REQUEST['date_from'])){
				$sdate=date2Mysql($_REQUEST['date_from']);
				$wheres[] = "DATE({$filterfield}) >= '{$sdate}'";
				}
			if(isset($_REQUEST['date_to'])){
				$sdate=date2Mysql($_REQUEST['date_to']);
				$wheres[] = "DATE({$filterfield}) <= '{$sdate}'";
				}
			$opts=array('_formname'=>"ticket");
			if(count($wheres)){
				$params['-where']=implode(' and ',$wheres);
				}
        	}
        if(!isset($_REQUEST['_sort']) && !isset($_REQUEST['-order']) && !isset($params['-order'])){
			$params['-order']="{$idfield} desc";
        	}
		if(!isset($params['-fields']) && isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(is_array($tinfo)){
				$xfields=array();
				if(isset($tinfo['listfields']) && is_array($tinfo['listfields'])){
					$xfields=$tinfo['listfields'];
					}
				elseif(isset($tinfo['default_listfields']) && is_array($tinfo['default_listfields'])){
					$xfields=$tinfo['default_listfields'];
					}
				if(count($xfields)){
					array_unshift($xfields,$idfield);
					$params['-fields']=implode(',',$xfields);
					}
	            }
	        if(isset($params['-fields'])){
				$params['-fields']=preg_replace('/\,+$/','',$params['-fields']);
				$params['-fields']=preg_replace('/^\,+/','',$params['-fields']);
	        	$params['-fields']=preg_replace('/\,+/',',',$params['-fields']);
				}
	        }
		$list=getDBRecords($params);
		}
	if(isset($list['error'])){
		echo $list['error'];
		exit;
		}
	if(!is_array($list)){
		$no_records=isset($params['-norecords'])?$params['-norecords']:'No records found';
		echo $no_records;
		exit;
		}
	$filename='output';
	if(isset($params['-filename'])){$filename=$params['-filename'];}
	elseif(isset($params['-table'])){$filename=$params['-table'].'_output';}
	if(!isset($params['-format'])){$params['-format']='csv';}
	//echo "here".printValue($params).printValue($list);
	switch(strtolower($params['-format'])){
    	case 'tab':
    		$filename.='.txt';
    		$data=arrays2TAB($list,$params);
    		break;
    	case 'xml':
    		$filename.='.xml';
    		$data=arrays2XML($list,$params);
    		break;
    	default:
    		//default to csv
    		$filename.='.csv';
    		$data=arrays2CSV($list,$params);
    		break;
	}
	pushData($data,$params['-format'],$filename);
	return;
}

//---------- begin function getDBTableTrigger
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBTableTrigger($table){
	global $databaseCache;
	if(!isset($databaseCache['getDBTableTrigger'])){
		$databaseCache['getDBTableTrigger']=array();
	}
	if(isset($databaseCache['getDBTableTrigger'][$table])){
		return $databaseCache['getDBTableTrigger'][$table];
	}
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	$originaltable=$table;
	$trigger_table='_triggers';
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$table=implode('.',$table_parts);
		$trigger_table="{$dbname}._triggers";
		}

	if(!isDBTable($trigger_table)){
		$databaseCache['getDBTableTrigger'][$table]=null;
		return $databaseCache['getDBTableTrigger'][$table];
	}
	$recopts=array('-table'=>$trigger_table,'name'=>$table,'active'=>1);
	$recs=getDBRecord($recopts);
	if(!is_array($recs)){$recs='';}
	$databaseCache['getDBTableTrigger'][$table]=$recs;
	return $databaseCache['getDBTableTrigger'][$table];
}

//---------- begin function getDBAdminSettings
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBAdminSettings(){
	$setfield='_admin_settings_';
	$settings=getDBSettings($setfield,'-1',1);
	//Defaults
	$defaults=getDBAdminDefaultSettings();
	foreach($defaults as $key=>$val){
		if(!isset($settings[$key]) || !strlen($settings[$key])){$settings[$key]=$val;}
	}
	//mainmenu
	$settings['mainmenu_icons']=1;
	$settings['mainmenu_text']=1;
	if(is_array($settings['mainmenu_toggle'])){
		if(!in_array('TXT',$settings['mainmenu_toggle'])){$settings['mainmenu_text']=0;}
		if(!in_array('ICO',$settings['mainmenu_toggle'])){$settings['mainmenu_icons']=0;}
	}
	elseif(strlen($settings['mainmenu_toggle'])){
		if($settings['mainmenu_toggle']=='TXT'){$settings['mainmenu_icons']=0;}
		if($settings['mainmenu_toggle']=='ICO'){$settings['mainmenu_text']=0;}
	}
	elseif(is_array($ConfigSettings) && !isset($settings['mainmenu_toggle'])){
		$settings['mainmenu_text']=0;
		$settings['mainmenu_icons']=0;
	}
	//actionmenu
	$settings['actionmenu_text']=1;
	$settings['actionmenu_icons']=1;
	if(is_array($settings['actionmenu_toggle'])){
		if(!in_array('TXT',$settings['actionmenu_toggle'])){$settings['actionmenu_text']=0;}
		if(!in_array('ICO',$settings['actionmenu_toggle'])){$settings['actionmenu_icons']=0;}
	}
	elseif(strlen($settings['actionmenu_toggle'])){
		if($settings['actionmenu_toggle']=='TXT'){$settings['actionmenu_icons']=0;}
		if($settings['actionmenu_toggle']=='ICO'){$settings['actionmenu_text']=0;}
	}
	elseif(is_array($settings) && !isset($settings['actionmenu_toggle'])){
		$settings['actionmenu_text']=0;
		$settings['actionmenu_icons']=0;
	}
	return $settings;
}
//---------- begin function getDBAdminDefaultSettings
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBAdminDefaultSettings(){
	$settings=array(
		'mainmenu_fade_color_top'	=> '#d6e2f8',
		'mainmenu_fade_color_bot'	=> '#a4bfee',
		'mainmenu_border_color_bot'	=> '#7a93df',
		'mainmenu_text_color'		=> '#3465a4',
		'mainmenu_hover_background'	=> '#e4eaed',
		'mainmenu_toggle'			=> 'ICO',
		'logo'						=> '/wfiles/iconsets/16/webpage.png',
		'logo_width'				=> 16,
		'logo_height'				=> 16,
		'logo_text'					=> $_SERVER['HTTP_HOST'],
		'actionmenu_toggle'			=> 'ICO',
		'actionmenu_text_color'		=> '#3465a4',
		'actionmenu_hover_background'=>'#e4eaed',
		'table_even_background'		=> '#f0f3f4',
		'table_header_text'			=> '#ffffff',
		'table_header_background'	=> '#3465a4',
		'content_position'			=> 'full',
		);
	return $settings;
}
//---------- begin function getDBCharsets ----------
/**
* @describe returns an array of all available charsets in current database
* @return array
* @usage $charsets=getDBCharsets();
*/
function getDBCharsets(){
	global $databaseCache;
	if(isset($databaseCache['getDBCharsets'])){return $databaseCache['getDBCharsets'];}
	$recs=getDBRecords(array('-query'=>"show character set"));
	//echo printValue($recs);
	$charsets=array();
	foreach($recs as $rec){
		$set=$rec['charset'];
		if($rec['maxlen'] > 1){
			$rec['maxlen'] .= ' bytes';
		}
		else{
			$rec['maxlen'] .= ' byte';
		}
		$charsets[$set]="{$rec['charset']} - {$rec['description']} ({$rec['maxlen']})";
    	}
    $databaseCache['getDBCharsets']=$charsets;
    return $charsets;
	}
//---------- begin function getDBCharset--------------------
/**
* @describe returns the current default charset of the database
* @return string
* @usage $charset=getDBCharset()
*/
function getDBCharset(){
	global $databaseCache;
	if(isset($databaseCache['getDBCharset'])){return $databaseCache['getDBCharset'];}
	global $CONFIG;
	if(isMysql() || isMysqli()){
		$recs=getDBRecords(array('-query'=>"SHOW CREATE DATABASE {$CONFIG['dbname']}"));
		if(count($recs)==1 && preg_match('/DEFAULT CHARACTER SET(.+)/i',$recs[0]['create database'],$chmatch)){
			$charset=trim($chmatch[1]);
			$charset=preg_replace('/[\s\*\/]+$/','',$charset);
			$databaseCache['getDBCharset']=$charset;
			return $charset;
	    }
	}
	$databaseCache['getDBCharset']='unknown';
    return "unknown";
}
//---------- begin function  ----------
/**
* @describe returns number of records that match params criteria
* @param param array
* -table|-countquery|-query - either a table or a count query
*  [-truecount] if set, use count(*) to determine the table count instead of sys tables
* @return integer - number of records
* @usage $ok=getDBCount(array('-table'=>$table,'field1'=>$val1...))
*/
function getDBCount($params=array()){
	global $CONFIG;
	if(isPostgreSQL()){return postgresqlGetDBCount($params);}
	elseif(isSqlite()){return sqliteGetDBCount($params);}
	elseif(isOracle()){return oracleGetDBCount($params);}
	elseif(isMssql()){return mssqlGetDBCount($params);}
	$function='getDBCount';
	$cnt=0;
	//echo printValue($params);exit;
	if(isset($params['-table'])){
		$params['-fields']="count(*) as cnt";
		unset($params['-order']);
		$query=getDBQuery($params);
		//if no where clause, get the count from information_schema.tables
		if(!isset($params['-truecount']) && !stringContains($query,'where')){
		 	$query="select table_rows from information_schema.tables where table_schema='{$CONFIG['dbname']}' and table_name='{$params['-table']}'";
		 	$recs=getDBRecords(array('-query'=>$query,'-nolog'=>1,'-nocache'=>1));
		 	//echo $query.printValue($recs);exit;
		 	if(isset($recs[0]['table_rows']) && isNum($recs[0]['table_rows'])){
		 		return (integer)$recs[0]['table_rows'];
		 	}
		}
		$recs=getDBRecords(array('-query'=>$query,'-nolog'=>1,'-nocache'=>1));
		if(!isset($recs[0]['cnt'])){
			debugValue($recs);
			return 0;
		}
		$cnt=$recs[0]['cnt'];
    	}
    elseif(isset($params['-countquery'])){
		$recs=getDBRecords(array('-query'=>$query,'-nolog'=>1));
		if(!is_array($recs)){return $recs;}
		if(isset($recs[0]['cnt'])){
			$cnt=$recs[0]['cnt'];
		}
		elseif(isset($recs[0]['count'])){
			$cnt=$recs[0]['count'];
		}
		elseif(isset($recs[0])){
			foreach($recs[0] as $key=>$val){
            	$cnt=$val;
            	break;
			}
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		// Perform Query
		$start=microtime(true);
		$query_result=@databaseQuery($query);
		if($query_result){
    		$cnt=databaseNumRows($query_result);
    		databaseFreeResult($query_result);
    		logDBQuery($query,$start,$function,isset($params['-table'])?$params['-table']:'');
			}
		}
    return $cnt;
	}
//---------- begin function getDBError
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBError(){
	return databaseError();
	}
//---------- begin function getDBFieldMeta--------------------
/**
* @describe returns the meta data from the _fielddata table for said table and fields
* @param table string - table name
* @param [fields] string - comma separated list of fields to return - defaults to blank
* @param [fieldname] string - specific field to retrieve only - defaults to blank
* @return array
* @usage $fields=getDBFieldMeta('notes');
*/
function getDBFieldMeta($table,$fields='',$fieldname=''){
	global $databaseCache;
	$dbcachekey=strtolower($table.'_'.$fields.'_'.$fieldname);
	if(isset($databaseCache['getDBFieldMeta'][$dbcachekey])){return $databaseCache['getDBFieldMeta'][$dbcachekey];}
	$getrecs=array(
		'-table'=>"_fielddata",
		'-index'=>'fieldname',
		'-where'=>"tablename='{$table}'",
		'-notimestamp'=>1
		);
	//check to see if they passed a databasename with table
	$table_parts=preg_split('/\./', $table);
	$originaltable=$table;
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$getrecs['-table']="{$dbname}._fielddata";
		$table=implode('.',$table_parts);
		$getrecs['-where']="tablename='{$table}'";
		}
	if(strlen($fields)){
		//make sure fieldname is in the fields list so we can index by it
		$list=preg_split('/[,;]+/',$fields);
		if(!in_array('fieldname',$list)){$list[]='fieldname';}
    	$getrecs['-fields']=implode(',',$list);
	}
	if(strlen($fieldname)){
    	$getrecs['-where']="tablename='{$table}' and fieldname='{$fieldname}'";
	}
	$rtn = getDBRecords($getrecs);
	if(!is_array($rtn)){$rtn='';}
	$databaseCache['getDBFieldMeta'][$dbcachekey]=$rtn;
	return $rtn;
}
//---------- begin function getDBFieldTag
/**
* @describe returns the HTML tag associated with this field. Other tag attributes can be passed in to override
* @param params array - requires either -list or -table or -query
*	-table string - table name
*	-field string - field name
*	[-formname] -  name of the parent form tag
*	other field/value pairs override defaults in the _fielddata table
* @return string - html tag to display
* @usage getDBFieldTag('-table'=>'notes','-field'=>'comments','width'=>'400'));
*/
function getDBFieldTag($params=array()){
    if(!isset($params['-table'])){return 'getDBFieldTag Error: No table' . printValue($params);}
    if(!isDBTable($params['-table'])){return 'getDBFieldTag Error: table does not exist' . printValue($params);}
    if(!isset($params['-field'])){return 'getDBFieldTag Error: No field for '.$params['-table'] . printValue($params);}
    $field=$params['-field'];
    //get the information from the db
    //Valid _dbtype values: string, real, int, date, time, datetime, blob
    $info=array();
    $info[$field]=array();
    $info=getDBFieldInfo($params['-table'],1,$field);
    if(!is_array($info) && strlen($info)){return $info;}
    //return printValue($params).printValue($info);
    //echo printValue($info);
    //echo printValue($params);
    $styles=array();
    //overrides that are passed in
    foreach($params as $key=>$val){
		if($key=='-table'){continue;}
		if($key=='-field'){continue;}
		$info[$field][$key]=$val;
    }
    if(isset($params['-formname'])){$info[$field]['-formname']=$params['-formname'];}
    //set value
    if(isset($params['value'])){$info[$field]['value']=$params['value'];}
	else{
		$vfield=(isset($params['name']) && strlen($params['name']))?$params['name']:$field;
		if(isset($_REQUEST[$vfield])){$info[$field]['value']=$_REQUEST[$vfield];}
		elseif(isset($_SESSION[$vfield])){$info[$field]['value']=$_SESSION[$vfield];}
		elseif(isset($params['defaultval'])){$info[$field]['value']=$params['defaultval'];}
    	}
    //view only?
    if(isset($params['-view']) && isNum($params['-view']) && $params['-view']==1){
		//view only - return the value instead of the tag
		if(in_array($info[$field]['inputtype'],array('select','checkbox'))){
			$selections=getDBFieldSelections($info[$field]);
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
                    //selected?
                    if(isset($_REQUEST[$field]) && ($_REQUEST[$field]==$selections['tvals'][$x] || $_REQUEST[$field]==$selections['dvals'][$x])){
						return $selections['dvals'][$x];
					}
                    elseif(isset($info[$field]['value']) && ($info[$field]['value']==$selections['tvals'][$x] || $info[$field]['value']==$selections['dvals'][$x])){
						return $selections['dvals'][$x];
					}
                }
            }
		}
		return $info[$field]['value'];
    }
    //set inputmax if not defined
    if(!isset($info[$field]['inputmax']) && isset($info[$field]['_dblength'])){$info[$field]['inputmax']=$info[$field]['_dblength'];}
    //set inputtype if not defined
    if(!isset($info[$field]['inputtype'])){
		//assign a valid inputtype based on _dbtype
		//Valid inputtypes:
		//	checkbox , combobox, date, file, formula, hidden, multi-select, password
		//	radio, select, text, textarea, time
		switch ($info[$field]['_dbtype']) {
			case 'string':
				$info[$field]['inputtype']=$info[$field]['_dblength']<256?'text':'textarea';
				if(!isset($info[$field]['width'])){$info[$field]['width']=200;}
				break;
			case 'blob':
				$info[$field]['inputtype']='textarea';
				if(!isset($info[$field]['width'])){$info[$field]['width']=400;}
				if(!isset($info[$field]['height'])){$info[$field]['height']=100;}
				break;
			case 'date':
				$info[$field]['inputtype']='date';
				break;
			case 'time':
				$info[$field]['inputtype']='time';
				break;
			case 'datetime':
				$info[$field]['inputtype']='datetime';
				break;
			default:
				$info[$field]['inputtype']='text';
				if(!isset($info[$field]['width'])){$info[$field]['width']=200;}
				break;
			}
		}
	if(!strlen($info[$field]['inputtype'])){return "Unknown inputtype for fieldname ".$field;}
	//return printValue($params).printValue($info[$field]);
	//set a few special fields
	switch ($info[$field]['inputtype']){
		//Checkbox
		case 'text':
			unset($info[$field]['height']);
			break;
		case 'password':
			unset($info[$field]['height']);
			//$info[$field]['onfocus']="this.select();";
			break;
		case 'file':
			if(!isset($info[$field]['width']) || $info[$field]['width']==0){$info[$field]['width']=300;}
			unset($info[$field]['height']);
			break;
        case 'select':
			unset($info[$field]['height']);
			if(isset($info[$field]['width']) && (!isNum($info[$field]['width']) || $info[$field]['width']==0)){unset($info[$field]['width']);}
			break;
		case 'combo':
			unset($info[$field]['height']);
			if($params['-table']=='_fielddata' && $field=='behavior'){
            	$info[$field]['tvals']=wasqlGetBehaviors();
            	$info[$field]['dvals']=wasqlGetBehaviors();
			}
			break;
		case 'time':
			unset($info[$field]['height']);
			unset($info[$field]['width']);
			break;
		case 'color':
			unset($info[$field]['height']);
			unset($info[$field]['width']);
			break;
		case 'slider':
			$info[$field]['min']=$info[$field]['height'];
			$info[$field]['max']=$info[$field]['inputmax'];
			$info[$field]['step']=$info[$field]['required'];
			unset($info[$field]['height']);
			unset($info[$field]['inputmax']);
			unset($info[$field]['required']);
			break;
		}
	//set tag name
	if(!isset($info[$field]['name'])){$info[$field]['name']=$field;}
	//set displayname
	if(!isset($info[$field]['displayname']) || !strlen($info[$field]['displayname'])){$info[$field]['displayname']=ucfirst($field);}
    //set the width and height in the style attribute
    // style="width:100px;height:100px;font-size:

	if(isset($params['style'])){
    	$setstyles=preg_split('/\;/',$params['style']);
    	foreach($setstyles as $setstyle){
			$parts=preg_split('/\:/',$setstyle);
			$key=trim($parts[0]);
			if(strlen($key) && strlen(trim($parts[1]))){
				$styles[$key]=trim($parts[1]);
			}
        }
    	unset($setstyles);
 	}
 	$stylekeys=array('width','height');
 	foreach($stylekeys as $stylekey){
		if(!isset($styles[$stylekey]) && isset($info[$field][$stylekey]) && $info[$field][$stylekey] != 0){
			if(!preg_match('/(\%|px)$/i',$info[$field][$stylekey])){
				$info[$field][$stylekey].='px';
			}
            $styles[$stylekey]=$info[$field][$stylekey];
        	}
    	}
    $info[$field]['style']=setStyleAttribues($styles);
	//Build the HTML tag
	//change the required attribute to _required since it messes up HTML5
	if(isset($info[$field]['required']) && $info[$field]['required']==1){
		unset($info[$field]['required']);
		$info[$field]['_required']=1;
    	}
    if(!isset($info[$field]['class'])){$info[$field]['class']='';}
    if(isExtraCss('bootstrap') && !stringContains($info[$field]['class'],'form-control')){$info[$field]['class'] .= ' form-control';}
    if(!isset($info[$field]['fieldname'])){$info[$field]['fieldname']=$field;}
    if(!isset($info[$field]['name'])){$info[$field]['name']=$field;}
	$tag='';
	switch(strtolower($info[$field]['inputtype'])){
		//Checkbox - NOTE: use arrayColumns function to order vertically rather than horizontally.
		case 'checkbox':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			//echo printValue($info[$field]);exit;
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $tag=buildFormCheckbox($info[$field]['fieldname'],$options,$info[$field]);
			break;
		case 'color':
			$tag=buildFormColor($info[$field]['name'],$info[$field]);
		break;
		case 'color_box':
			$tag=buildFormColorBox($info[$field]['name'],$info[$field]);
		break;
		case 'color_hexagon':
			$tag=buildFormColorHexagon($info[$field]['name'],$info[$field]);
		break;
		case 'combo':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $tag=buildFormCombo($info[$field]['fieldname'],$options,$info[$field]);
			break;
		case 'date':
			$name=$info[$field]['name'];
			$tagopts=$info[$field];
			if(isset($params['-value'])){$tagopts['-value']=$params['-value'];}
			elseif(isset($params[$field])){$tagopts['-value']=$params[$field];}
			elseif(isset($info[$field]['value'])){$tagopts['-value']=$info[$field]['value'];}
			elseif(isset($_REQUEST[$field])){$tagopts['-value']=$_REQUEST[$field];}
			if(isset($params['-formname'])){$tagopts['-formname']=$params['-formname'];}
			//check for required
			if(isset($info[$field]['_required']) && $info[$field]['_required'] ==1){
				$tagopts['-required']=1;
			}
			$tag .= buildFormDate($name,$tagopts);
			break;
		case 'datetime':
			$name=$info[$field]['name'];
			//date part
			$tagopts=$info[$field];
			//check for value
			if(isset($params['-value'])){$tagopts['-value']=$params['-value'];}
			elseif(isset($params[$field])){$tagopts['-value']=$params[$field];}
			elseif(isset($info[$field]['value'])){$tagopts['-value']=$info[$field]['value'];}
			elseif(isset($_REQUEST[$field])){$tagopts['-value']=$_REQUEST[$field];}
			//set prefix to formname
			if(isset($params['-formname'])){$tagopts['-formname']=$params['-formname'];}
			//check for required
			if(isset($info[$field]['_required']) && $info[$field]['_required'] ==1){
				$tagopts['-required']=1;
			}
			$tag .= buildFormDateTime($name,$tagopts);
			break;
		case 'file':
			$tag=buildFormFile($info[$field]['name'],$info[$field]);
		break;
		case 'formula':
		break;
		case 'recorder_audio':
			$tag=buildFormRecorderAudio($info[$field]['name'],$info[$field]);
		break;
		case 'geolocationmap':
			$tag=buildFormGeoLocationMap($info[$field]['name'],$info[$field]);
		break;
		case 'latlon':
			$tag=buildFormLatLon($info[$field]['name'],$info[$field]);
		break;
		case 'hidden':
			$tag=buildFormHidden($info[$field]['name'],$info[$field]);
			break;
		case 'multiselect':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $tag=buildFormMultiSelect($info[$field]['fieldname'],$options,$info[$field]);
		break;
		case 'multiinput':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $tag=buildFormMultiInput($info[$field]['fieldname'],$options,$info[$field]);
            //$tag=printValue($info[$field]);
		break;
		case 'buttonselect':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $tag=buildFormButtonSelect($info[$field]['fieldname'],$options,$info[$field]);
		break;
		case 'buttonselect_m':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $tag=buildFormButtonSelectMultiple($info[$field]['fieldname'],$options,$info[$field]);
		break;
		//select_database
		case 'select_database':
			$tag=buildFormSelectDatabase($info[$field]['name'],$info[$field]);
		break;
		//Password
		case 'password':
			$tag=buildFormPassword($info[$field]['name'],$info[$field]);
		break;
		//Radio
		case 'radio':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $tag=buildFormRadio($info[$field]['fieldname'],$options,$info[$field]);
			break;
		//Select
		case 'select':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $dname=ucwords(str_replace('_',' ',$name));
            if(!isset($info[$field]['message'])){
            	$info[$field]['message']="-- {$dname} --";
            }
            $tag=buildFormSelect($name,$options,$info[$field]);
		break;
		case 'selectcustom':
		case 'select_custom':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
	            }
	        }
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $dname=ucwords(str_replace('_',' ',$name));
            $info[$field]['message']="-- {$dname} --";
            $tag=buildFormSelectCustom($name,$options,$info[$field]);
		break;
		case 'select_color':
			if(!isset($info[$field]['message'])){$info[$field]['message']='-- Color --';}
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormSelectColor($name,$info[$field]);
		break;
		case 'select_country':
			if(!isset($info[$field]['message'])){$info[$field]['message']='-- Country --';}
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormSelectCountry($name,$info[$field]);
		break;
		case 'select_database':
			if(!isset($info[$field]['message'])){$info[$field]['message']='-- Database --';}
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormSelectDatabase($name,$info[$field]);
		break;
		case 'select_font':
			if(!isset($info[$field]['message'])){$info[$field]['message']='-- Font --';}
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormSelectFont($name,$info[$field]);
		break;
		case 'select_host':
			if(!isset($info[$field]['message'])){$info[$field]['message']='-- Host --';}
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormSelectHost($name,$info[$field]);
		break;
		case 'select_month':
			if(!isset($info[$field]['message'])){$info[$field]['message']='-- Month --';}
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormSelectMonth($name,$info[$field]);
		break;
		case 'select_onoff':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormSelectOnOff($name,$info[$field]);
		break;
		case 'select_yesno':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormYesNo($name,$info[$field]);
		break;
		case 'select_state':
			if(!isset($info[$field]['message'])){$info[$field]['message']='-- State --';}
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $tag=buildFormSelectState($name,$info[$field]);
		break;
		case 'select_timezone':
		case 'timezone':
			if(!isset($info[$field]['message'])){$info[$field]['message']='-- Time Zone --';}
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];} 
            $tag=buildFormSelectTimezone($name,$info[$field]);
		break;
		case 'select_year':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            $dname=ucwords(str_replace('_',' ',$name));
            if(!isset($info[$field]['message'])){
            	$info[$field]['message']="-- {$dname} --";
            }
            $tag=buildFormSelectYear($name,$info[$field]);
		break;
        case 'starrating':
			$tag=buildFormStarRating($info[$field]['name'],$info[$field]);
		break;
		case 'frequency':
			$tag=buildFormFrequency($info[$field]['name'],$info[$field]);
		break;
		case 'toggle_f':
		case 'toggle_r':
			if(isset($params['-translate'])){$info[$field]['-translate']=$params['-translate'];}
			$selections=getDBFieldSelections($info[$field]);
			$options=array();
			if(is_array($selections['tvals'])){
				$cnt=count($selections['tvals']);
				for($x=0;$x<$cnt;$x++){
					$tval=$selections['tvals'][$x];
					$dval=isset($selections['dvals'][$x])?$selections['dvals'][$x]:$tval;
					$options[$tval]=$dval;
					if(count($options)==2){break;}
	            }
	        }
            $name=$field;
            if(isset($info[$field]['name'])){$name=$info[$field]['name'];}
            switch(strtolower($info[$field]['inputtype'])){
            	case 'toggle_f':$info[$field]['-format']='flip';break;
            	default:$info[$field]['-format']='round';break;
			}
            $tag=buildFormToggleButton($name,$options,$info[$field]);
			break;
		case 'signature':
			$tag=buildFormSignature($info[$field]['name'],$info[$field]);
		break;
		case 'whiteboard':
			$tag=buildFormWhiteboard($info[$field]['name'],$info[$field]);
		break;
		case 'slider':
		case 'range':
			//load html5slider.js to enable slider support in FF, etc. Still will not work in IE.
			$tag .= buildFormSlider($info[$field]['name'],$info[$field]);
			//echo "HERE".printValue($tag).printValue($info[$field]);exit;
			break;
		case 'textarea':
			$tag=buildFormTextarea($info[$field]['name'],$info[$field]);
			break;
		case 'wysiwyg':
			$tag=buildFormWYSIWYG($info[$field]['name'],$info[$field]);
			$tag .= buildOnLoad("wacss.init();");
			break;
		case 'time':
			$tagopts=$info[$field];
			//check for value
			if(isset($params['-value'])){$tagopts['-value']=$params['-value'];}
			elseif(isset($params[$field])){$tagopts['-value']=$params[$field];}
			elseif(isset($info[$field]['value'])){$tagopts['-value']=$info[$field]['value'];}
			elseif(isset($_REQUEST[$field])){$tagopts['-value']=$_REQUEST[$field];}
			//style
			if(isset($info[$field]['style'])){
				$tagopts['style']=$info[$field]['style'];
			}
			//check for required
			if(isset($info[$field]['_required']) && $info[$field]['_required'] ==1){
				$tagopts['-required']=1;
			}
			$tag .= buildFormTime($info[$field]['name'],$tagopts);
			break;
		//Text
		case 'text':
		default:
			if(isset($params['-bootstrap'])){
				$info[$field]['-bootstrap']=$params['-bootstrap'];
			}
			elseif(isset($params['-materialize'])){
				$info[$field]['-materialize']=$params['-materialize'];
			}
			$tag=buildFormText($info[$field]['name'],$info[$field]);
			break;
    	}
    //not done here yet...
    return $tag;
	}
//---------- begin function getDBFieldSelections
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBFieldSelections($info=array()){
	$selections=array();
	if(isset($info['tvals']) && is_string($info['tvals']) && strtolower(trim($info['tvals']))=='&getdbtables'){
		$selections['tvals']=getDBTables();
		$selections['dvals']=$selections['tvals'];
		if(isset($info['-translate']) && $info['-translate']==1){
			loadExtras('translate');
			foreach($selections['dvals'] as $i=>$val){
				$selections['dvals'][$i]=translateText($val);
			}
		}
		return $selections;
    }
	if(isset($info['dvals']) && is_array($info['dvals'])){
		if(!count($info['dvals'])){$info['dvals']=$info['tvals'];}
	}
	elseif(!isset($info['dvals']) || !strlen($info['dvals'])){
		if(isset($info['tvals'])){
			$info['dvals']=$info['tvals'];
		}
	}
    if(isset($info['tvals'])){
		if(is_array($info['tvals'])){$tvals=$info['tvals'];}
		else{
			$tvals=trim($info['tvals']);
			if(!strlen($tvals)){return;}
			if(preg_match('/\<\?(.+?)\?\>/is',$tvals)){
				$tvals = evalPHP($tvals);
			}
		}
		if(is_array($info['tvals'])){$dvals=$info['dvals'];}
		else{
			$dvals=trim($info['dvals']);
			if(strlen($dvals) && preg_match('/\<\?(.+?)\?\>/is',$dvals)){
				$dvals = evalPHP($dvals);
			}
		}
		if(is_array($tvals) && is_array($dvals)){
			$selections['tvals']=$tvals;
			$selections['dvals']=$dvals;
			if(isset($info['-translate']) && $info['-translate']==1){
				loadExtras('translate');
				foreach($selections['dvals'] as $i=>$val){
					$selections['dvals'][$i]=translateText($val);
				}
			}
			return $selections;
		}
		if(preg_match('/^select[\ \r\n]/i',$tvals)){
			$tvalresults=getDBRecords($tvals);
			if(is_array($tvalresults)){
				//check for tval,dval in select
				if(isset($tvalresults[0]['tval']) && isset($tvalresults[0]['dval'])){
					$selections['tvals']=array();
					$selections['dvals']=array();
					foreach($tvalresults as $rec){
						$selections['tvals'][]=$rec['tval'];
						$selections['dvals'][]=$rec['dval'];
					}
				}
				else{
					if($tvals==$dvals){$dvalresults=$tvalresults;}
					else{
	                	$dvalresults=getDBRecords($dvals);
					}
					//if($info['fieldname']=='email'){echo $tvals.printValue($tvalresults);exit;}
					if(is_array($dvalresults)){
						//parse through the results and build the tval/dval array.
		                $tvalues=array();
		                foreach($tvalresults as $tvalresult){
							$vals=array();
	                        foreach($tvalresult as $rkey=>$rval){
								array_push($vals,$rval);
	                        }
							$val=implode(' ',$vals);
							unset($vals);
							$tvalues[]=$val;
	                    }
	                    $dvalues=array();
		                foreach($dvalresults as $dvalresult){
							$vals=array();
	                        foreach($dvalresult as $rkey=>$rval){
								array_push($vals,$rval);
	                        }
							$val=implode(' ',$vals);
							unset($vals);
							$dvalues[]=$val;
	                    }
						$selections['tvals']=$tvalues;
						$selections['dvals']=$dvalues;
						//if($info['fieldname']=='email'){echo printValue($dvalresults);exit;}
		            }
		        }
            }

        }
        elseif(preg_match('/^([0-9]+?)\.\.([0-9]+)$/',$tvals,$tvmatch)){
			$selections['tvals']=array();
			$start=(integer)$tvmatch[1];
			$end=(integer)$tvmatch[2];
			for($x=$start;$x <= $end;$x++){
                array_push($selections['tvals'],$x);
            }
            $selections['dvals']=$selections['tvals'];
        }
		else{
			//Parse values in tvals and dvals
			$selections['tvals']=preg_split('/[\r\n\,]+/',$tvals);
			$selections['dvals']=preg_split('/[\r\n\,]+/',$dvals);
        }
        if(isset($info['-translate']) && $info['-translate']==1){
			loadExtras('translate');
			foreach($selections['dvals'] as $i=>$val){
				$selections['dvals'][$i]=translateText($val);
			}
		}
        return $selections;
    }
	return '';
}
//---------- begin function getDBList--------------------
/**
* @describe returns an array of databases that the dbuser has rights to see
* @return array
* @usage $dbs=getDBList();
*/
function getDBList(){
	return databaseListDbs();
}
//---------- begin function getDBProcesses--------------------
/**
* @describe returns an array of current database processes/threads
* @return array
* @usage $procs=getDBProcesses();
*/
function getDBProcesses(){
	$db_list = databaseListProcesses();
	$procs=array();
	while ($row = databaseFetchObject($db_list)){
		$proc=array();
		foreach($row as $key=>$val){$proc[$key]=$val;}
		$procs[]=$proc;
	}
	return $procs;
}
//---------- begin function getDBPaging--------------------
/**
* @describe returns an array of paging information needed for buildDBPaging
* @param recs_count integer - total record count
* @param [page_count] - numbers of records to display - defaults to 20
* @param [limit_start] - record number to start paging at - defaults to 0
* @return array
* @usage $procs=getDBPaging($cnt);
*/
function getDBPaging($recs_count,$page_count=20,$limit_start=0){
	if(!isNum($page_count)){$page_count=20;}
	$paging=array();
	if($recs_count <= $page_count){
		$paging['-text']=$recs_count;
		return $paging;
	}

	if(isset($_REQUEST['_start']) && isNum($_REQUEST['_start'])){
		$limit_start=(integer)$_REQUEST['_start'];
	}
	$limit_cnt=$page_count+$limit_start;
	if($limit_cnt > $recs_count){$limit_cnt = $recs_count;}
	$paging['-start']=$limit_start;
	$paging['-offset']=$page_count;
	$paging['-limit']="{$limit_start},{$page_count}";
	//previous
	if($limit_start > 0){
		$prev=$limit_start-$page_count;
		if($prev < 0){$prev=0;}
		$paging['-prev']=$prev;
		if($prev > 0){
			$paging['-first']=0;
		}
	}
	//next
	if($limit_cnt < $recs_count){
		$next=$limit_start+$page_count;
		$paging['-next']=$next;
		$last=$recs_count-$page_count;
		if($last > 0 && $last > $next){
			$paging['-last']=$last;
		}
	}
	//text
	$paging['-text']=round(($limit_start+1),0) . " - {$limit_cnt} of {$recs_count}";
	return $paging;
}
//---------- begin function loadDBFunctions---------------------------------------
/**
* @describe loads functions in pages. Returns the load times for each loaded page.
* @param $names string|array
*    page name or lists of page names.
* @param $field string
*  	page field to be loaded
* @return string
* 	returns an html comment showing the load times for each loaded page.
* @usage
*	loadDBFunctions('sampleFunctionsPage'); //This would load and process the body segment of 'sampleFunctionsPage'.
* 	loadFunctions('locations','functions'); //This would load and process the functions segment of the 'locations' page.
* @author slloyd
* @history bbarten 2014-01-07 added documentation
*/
function loadDBFunctions($names,$field='body'){
	if(!is_array($names)){
		$names=preg_split('/\,/',trim(str_replace(' ','',$names)));
	}
	$errors=array();
	$rtn='<!-- loadDBFunctions'.PHP_EOL;
	foreach($names as $name){
		$start=microtime(1);
		$table="_pages";
		$tname=$name;
		$opts=array('-table'=>$table,'-field'=>$field);
		if(isNum($name)){$opts['-where']="_id={$name}";}
		else{$opts['-where']="name = '{$name}'";}

		$ok=includeDBOnce($opts);
		$stop=microtime(1);
		$loadtime=$stop-$start;
		if(!isNum($ok)){
			$rtn .= "	{$tname} ERRORS: {$ok}".PHP_EOL;
			debugValue($ok);
		}
		else{$rtn .= "	{$tname} took {$loadtime} seconds".PHP_EOL;}
    }
    $rtn .= ' -->'.PHP_EOL;
	return $rtn;
}
//---------- begin function logDBQuery
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function logDBQuery($query,$start,$function,$tablename='',$fields='',$rowcount=0){
	global $SETTINGS;
	global $USER;
	global $PAGE;
	global $CONFIG;
	if(isset($_SERVER['WaSQL_AdminUserID']) && isNum($_SERVER['WaSQL_AdminUserID'])){return;}
	if(!isset($CONFIG['wasql_queries']) || $CONFIG['wasql_queries']!=1){return;}
	if(preg_match('/\_(queries|fielddata)/i',$query)){return;}
	if(preg_match('/^desc /i',$query)){return;}
	//only run if user?
	if(isset($CONFIG['wasql_queries_user']) && strlen($CONFIG['wasql_queries_user'])){
		if(!isset($USER['_id'])){return;}
		$query_users=preg_split('/[\r\n\s\,\;]+/',strtolower($CONFIG['wasql_queries_user']));
		if(!in_array($USER['_id'],$query_users) || !in_array($USER['username'],$query_users)){return;}
	}
	$stop=microtime(true);
	$run_length=round(($stop-$start),3);
	if(isNum($CONFIG['wasql_queries_time']) && $run_length < $CONFIG['wasql_queries_time']){return;}
	$addopts=array('-table'=>"_queries",
		'function_name'		=> $function,
		'function'		=> $function,
		'query'			=> $query,
		'row_count'		=> $rowcount,
		'run_length'	=> $run_length,
	);
	if(strlen($tablename)){$addopts['tablename']=$tablename;}
	if(!is_array($fields)){$fields=preg_split('/[\,\;\ ]+/',$fields);}
	$addopts['fields']=implode(',',$fields);
	$addopts['field_count']=count($fields);
	if(isNum($USER['_id'])){$addopts['user_id']=$USER['_id'];}
	if(isNum($PAGE['_id'])){$addopts['page_id']=$PAGE['_id'];}
	$ok=addDBRecord($addopts);

	//wasql_queries_days
	$qdays=(integer)$CONFIG['wasql_queries_days'];
	if($qdays==0){$qdays=10;}
	if($qdays > 0){
		$query="DELETE FROM _queries WHERE function_name!='sql_prompt' and _cdate < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL {$qdays} DAY))";
		$ok=executeSQL($query);
	}
	return $ok;
}
//---------- begin function includeDBOnce
/**
* @describe function to load database records as php you can include dynamic functions
* @exclude  - this function will be depreciated and thus excluded from the manual
*/
function includeDBOnce($params=array()){
	global $CONFIG;
	global $TEMPLATE;
	global $PAGE;
	$names=array();
	// need table, field, where
	if(!isset($params['-table'])){return 'includeDBOnce Error: No table' . printValue($params);}
	if(!isset($params['-field'])){return 'includeDBOnce Error: No field' . printValue($params);}
	if(!isset($params['-where'])){return 'includeDBOnce Error: No where' . printValue($params);}
	$field=$params['-field'];
	if($params['-table']=='_templates' && isset($TEMPLATE['_id']) && stringContains($params['-where'],"_id={$TEMPLATE['_id']}")){
		$rec=$TEMPLATE;
		$names[]='template';
		$names[]=$TEMPLATE['name'];
	}
	elseif($params['-table']=='_pages' && isset($PAGE['_id']) && stringContains($params['-where'],"_id={$PAGE['_id']}")){
		$rec=$PAGE;
		$names[]='page';
		$names[]=$PAGE['name'];
	}
	else{
		$params['-where']=str_replace(' like ',' = ',$params['-where']);
		$opts=array('-table'=>$params['-table'],'-notimestamp'=>1,'-where'=>$params['-where'],'-fields'=>array('_id',$field));
		if(isset($params['-dbname'])){$opts['-dbname']=$params['-dbname'];}
		switch(strtolower($params['-table'])){
			case '_pages':$names[]='page';$opts['-fields'][]='name';break;
			case '_templates':$names[]='template';$opts['-fields'][]='name';break;
			default:$names[]=$params['-table'];break;
		}
		$rec=getDBRecord($opts);
		
		if(isset($rec['name'])){
			$names[]=$rec['name'];
		}
	}
	if(!is_array($rec)){
		echo 'includeDBOnce Error: No record. ' .$rec. printValue($params);exit;
	}
	$names[]=$rec['_id'];
	$content=trim($rec[$field]);
	//echo printValue($names);
	/* load contents based on tag  - php, python, etc */
	$ok=commonIncludeFunctionCode($content,implode('_',$names));
	return 0;
}
//---------- begin function mapDBDvalsToTvals--------------------
/**
* @describe returns a key/value array map so if you know a tval you can derive the dval
* @param table string - table name
* @param field string - field name in table
* @param params array - filters to apply
* @param [min] - skip tval if less than min
* @param [max] - skip tval if more than max
* @param [contains] - skip if tval does not contain
* @param [equals] - skip if tval does not equal
* @param [in] array - skip if tval is not in this array of values
* @return array with tval as the index
* @usage $map=mapDBDvalsToTvals('states','code');
*/
function mapDBDvalsToTvals($table,$field,$params=array()){
	global $databaseCache;
	$cachekey=$table.'_'.$field;
	if(count($params)){$cachekey .= '_'.sha1(json_encode($params,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE));}
	if(isset($databaseCache['mapDBDvalsToTvals'][$cachekey])){
		return $databaseCache['mapDBDvalsToTvals'][$cachekey];
	}
	$info=getDBFieldMeta($table,"tvals,dvals",$field);
	if(!isset($info[$field])){return '';}
	$selections=getDBFieldSelections($info[$field]);
	if(isset($selections['tvals']) && is_array($selections['tvals'])){
		$tdmap=array();
		$tcount=count($selections['tvals']);
		for($x=0;$x<$tcount;$x++){
			$tval=$selections['tvals'][$x];
			if(isset($selections['dvals'][$x]) && strlen($selections['dvals'][$x])){$dval=$selections['dvals'][$x];}
			else{$dval=$tval;}
			//check params - min, max, contains, equals
			if(isset($params['min']) && isNum($params['min']) && isNum($tval) && $tval < $params['min']){continue;}
			if(isset($params['max']) && isNum($params['max']) && isNum($tval) && $tval > $params['max']){continue;}
			if(isset($params['contains']) && strlen($params['contains']) && strlen($tval) && !stringContains($tval,$params['contains'])){continue;}
			if(isset($params['equals']) && strlen($params['equals']) && strlen($tval) && $tval != $params['equals']){continue;}
			if(isset($params['in']) && is_array($params['in']) && strlen($tval) && !in_array($tval,$params['in'])){continue;}
			$tdmap[$tval]=$dval;
        	}
        $databaseCache['mapDBDvalsToTvals'][$cachekey]=$tdmap;
        return $tdmap;
    	}
    return '';
    }
//---------- begin function mapDBTvalsToDvals--------------------
/**
* @describe returns a key/value array map so if you know a dval you can derive the tval
* @param table string - table name
* @param field string - field name in table
* @param params array - filters to apply
* @param [min] - skip dval if less than min
* @param [max] - skip dval if more than max
* @param [contains] - skip if dval does not contain
* @param [equals] - skip if dval does not equal
* @param [in] array - skip if dval is not in this array of values
* @return array with dval as the index
* @usage $map=mapDBTvalsToDvals('states','code');
*/
function mapDBTvalsToDvals($table,$field){
	global $databaseCache;
	$cachekey=$table.$field;
	if(isset($databaseCache['mapDBTvalsToDvals'][$cachekey])){
		return $databaseCache['mapDBTvalsToDvals'][$cachekey];
		}
	$info=getDBFieldMeta($table,"tvals,dvals",$field);
	$selections=getDBFieldSelections($info[$field]);
	if(isset($selections['tvals']) && is_array($selections['tvals'])){
		$tdmap=array();
		$tcount=count($selections['tvals']);
		for($x=0;$x<$tcount;$x++){
			$tval=$selections['tvals'][$x];
			if(isset($selections['dvals'][$x]) && strlen($selections['dvals'][$x])){$dval=$selections['dvals'][$x];}
			else{$dval=$tval;}
			//check params - min, max, contains, equals
			if(isset($params['min']) && isNum($params['min']) && isNum($dval) && $dval < $params['min']){continue;}
			if(isset($params['max']) && isNum($params['max']) && isNum($dval) && $dval > $params['max']){continue;}
			if(isset($params['contains']) && strlen($params['contains']) && strlen($dval) && !stringContains($dval,$params['contains'])){continue;}
			if(isset($params['equals']) && strlen($params['equals']) && strlen($dval) && $dval != $params['equals']){continue;}
			if(isset($params['in']) && is_array($params['in']) && strlen($dval) && !in_array($dval,$params['in'])){continue;}
			$tdmap[$dval]=$tval;
        	}
        $databaseCache['mapDBDvalsToTvals'][$cachekey]=$tdmap;
        return $tdmap;
    	}
    return null;
    }
//---------- begin function syncDBAccess
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function syncDBAccess($sync_url,$update=1){
	//exclude: true
	global $PAGE;
	//status: 1=sync, 0=nosync
	//make sure you are not syncing the url you are on
	$hosts=array($_SERVER['HTTP_HOST'],$_SERVER['UNIQUE_HOST']);
	$parts=preg_split('/[\/]+/',$sync_url);
	foreach($hosts as $host){
		if(strtolower($parts[0]) == strtolower($host)){return "syncDBAccess Error - {$sync_url} same host as {$host}";}
		if(strtolower($parts[1]) == strtolower($host)){return "syncDBAccess Error - {$sync_url} same host as {$host}";}
    	}
	$count=0;
	//remove access records from this page
	$ok=delDBRecord(array('-table'=>"_access",'-where'=>"page = '{$PAGE['name']}'"));
	$recs=getDBRecords(array('-table'=>"_access",'-where'=>"status=1 and not(page = '{$PAGE['name']}')",'-order'=>'_cdate'));
	//return printValue($recs);
	foreach($recs as $rec){
		$xmldata=xmldata2Array($rec['xml']);
		if(isset($xmldata['counts'])){
			$urlopts=$xmldata['data'];
			if(isset($xmldata['user']['apikey']) && isset($xmldata['user']['username'])){
				$urlopts['username']=$xmldata['user']['username'];
				$urlopts['apikey']=$xmldata['user']['apikey'];
				$urlopts['_noguid']=1;
            	}
            $urlopts['_noguid']=1;
            $rtn=postURL($sync_url,$urlopts);
            //return $sync_url . printValue($urlopts).printValue($rtn);
            if(isset($rtn['body'])){$count++;}
			}
		if($update==1){
			$ok=editDBRecord(array('-table'=>"_access",'-where'=>"_id={$rec['_id']}",'status'=>0));
			}
    	}
    return $count;
	}
//---------- begin function getDBFields--------------------
/**
* @describe returns table fields. If $allfields is true returns internal fields also
* @param table string - table name
* @param allfields boolean - if true returns internal fields also - defaults to false
* @return array
* @usage $fields=getDBFields('notes');
*/
function getDBFields($table='',$allfields=0){
	if(isPostgreSQL()){return postgresqlGetDBFields($table,$allfields);}
	elseif(isSqlite()){return sqliteGetDBFields($table,$allfields);}
	elseif(isOracle()){return oracleGetDBFields($table,$allfields);}
	elseif(isMssql()){return mssqlGetDBFields($table,$allfields);}
	global $databaseCache;
	$dbcachekey=strtolower($table);
	if($allfields){$dbcachekey.='_true';}
	if(isset($databaseCache['getDBFields'][$dbcachekey])){
		return $databaseCache['getDBFields'][$dbcachekey];
	}
	$table_parts=preg_split('/\./', $table);
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$tablename=implode('.',$table_parts);
		if(strlen($dbname)){$dbname .= '.';}
	}
	else{
		$dbname='';
		$tablename=$table;
    }
    $fieldnames=array();
	if(isMssql()){$tablename="[{$tablename}]";}
	$query="SELECT * FROM {$dbname}{$tablename} where 1=0";
	$query_result=@databaseQuery($query);
	//echo $query.printValue($query_result);exit;
  	if(!$query_result){
		return setWasqlError(debug_backtrace(),getDBError(),$query);
  	}
	//mysqli does not have a mysqli_field_name function
	if(isMysqli()){
		while ($finfo = mysqli_fetch_field($query_result)) {
	        $name = (string)$finfo->name;
	        if(!$allfields && preg_match('/^\_/',$name)){continue;}
	        if(!in_array($name,$fieldnames)){$fieldnames[]=$name;}
	    }
	}
	elseif(isPostgreSQL()){
    	$i = pg_num_fields($query_result);
  		for ($j = 0; $j < $i; $j++) {
      		$name = pg_field_name($query_result, $j);
      		if(!$allfields && preg_match('/^\_/',$name)){continue;}
	        if(!in_array($name,$fieldnames)){$fieldnames[]=$name;}
      		//$clen = pg_field_prtlen($result, $name); //char length
      		//$blen = pg_field_size($result, $j); //byte length
      		//$type = pg_field_type($result, $j); // type
  		}
	}
	else{
		$cnt = databaseNumFields($query_result);
		for ($i=0; $i < $cnt; $i++) {
			$name  = (string) databaseFieldName($query_result, $i);
			if(!$allfields && preg_match('/^\_/',$name)){continue;}
			if(!in_array($name,$fieldnames)){$fieldnames[]=$name;}
		}
	}
	databaseFreeResult($query_result);
	sort($fieldnames);
	$databaseCache['getDBFields'][$dbcachekey]=$fieldnames;
	return $fieldnames;
}
//---------- begin function getDBFieldInfo--------------------
/**
* @describe returns an array containing type,length, and flags for each field in said table
* @param table string - table name
* @param [getmeta] boolean - if true returns info in _fielddata table for these fields - defaults to false
* @param [field] string - if this has a value return only this field - defaults to blank
* @param [getmeta] boolean - if true forces a refresh - defaults to false
* @return array
* @usage $fields=getDBFieldInfo('notes');
*/
function getDBFieldInfo($table='',$getmeta=0,$field='',$force=0){
	if(isPostgreSQL()){return postgresqlGetDBFieldInfo($table,$getmeta,$field,$force);}
	elseif(isSqlite()){return sqliteGetDBFieldInfo($table,$getmeta,$field,$force);}
	elseif(isOracle()){return oracleGetDBFieldInfo($table,$getmeta,$field,$force);}
	elseif(isMssql()){return mssqlGetDBFieldInfo($table,$getmeta,$field,$force);}
	global $databaseCache;
	$dbcachekey=strtolower($table.'_'.$getmeta.'_'.$field);
	if($force==0 && isset($databaseCache['getDBFieldInfo'][$dbcachekey])){
		return $databaseCache['getDBFieldInfo'][$dbcachekey];
	}
	if(!isDBTable($table)){
		$databaseCache['getDBFieldInfo'][$dbcachekey]=null;
		return null;
	}
	$query="show full columns from {$table}";
	if(strlen($field)){
		$query .= " like '%{$field}%'";
	}
	if(preg_match('/^(.+?)\.(.+)$/',$table,$m)){
    	$vtable=$m[2];
	}
	else{$vtable=$table;}
	$recopts=array('-query'=>$query,'-nolimit'=>1,'-index'=>'field');
	$recs=getDBRecords($recopts);
	if(!is_array($recs)){
		$databaseCache['getDBFieldInfo'][$dbcachekey]=null;
		return null;
	}
	$info=array();
	foreach($recs as $key=>$rec){
    	if(preg_match('/(VIRTUAL|STORED) GENERATED/i',$rec['extra'])){
			$info[$key]['expression']=getDBExpression($vtable,$rec['field']);
		}
		$info[$key]['_dbfield']=$rec['field'];
		$info[$key]['name']=$rec['field'];
		$info[$key]['_dbnull']=$rec['null'];
		$info[$key]['_dbprivileges']=$rec['privileges'];
		$info[$key]['_dbtablename']=$table;
		$info[$key]['_dbtable']=$table;
		$info[$key]['table']=$table;
		if(preg_match('/^(.+?)\((.+)\)$/',$rec['type'],$m)){
			$info[$key]['type']=$info[$key]['_dbtype']=$m[1];
			$parts=preg_split('/\,/',$m[2],2);
			$len=$parts[0];
			if(isset($parts[1])){
				$dec=$parts[1];
			}
			else{
				$dec=0;
			}
			
			$info[$key]['length']=$recs[$key]['_dblength']=$len;
			$info[$key]['_dbtype_ex']=$rec['type'];
		}
		else{
			$info[$key]['type']=$info[$key]['_dbtype']=$info[$key]['_dbtype_ex']=$rec['type'];
		}
		//flags
		 $flags=array();
		 if(strtolower($rec['null'])=='no'){
		 	$info[$key]['_dbtype_ex'] .= ' NOT NULL';
		 	$flags[]='not_null';
		 }
		 switch(strtolower($rec['key'])){
		 	case 'pri':
		 		$flags[]='primary_key';
		 		$info[$key]['_dbtype_ex'] .= ' PRIMARY KEY';
		 	break;
		 	case 'uni':
		 		$flags[]='unique_key';
		 		$info[$key]['_dbtype_ex'] .= ' UNIQUE';
		 	break;
		 }
		 switch(strtolower($rec['extra'])){
		 	case 'auto_increment':
		 		$flags[]='auto_increment';
		 		$info[$key]['_dbtype_ex'] .= ' auto_increment';
		 	break;
		 	case 'stored generated':
		 		$flags[]='virtual';
		 		$info[$key]['_dbtype_ex'] .= ' STORED GENERATED';
		 	break;
		 	case 'virtual generated':
		 	case 'virtual from':
		 		$flags[]='virtual';
		 		$info[$key]['_dbtype_ex'] .= ' VIRTUAL GENERATED';
		 	break;
		 }
		 $info[$key]['flags']=$info[$key]['_dbflags']=implode(' ',$flags);
		 //default
		 if(isset($rec['default']) && strlen($rec['default'])){
		 	$info[$key]['_dbdef']=$info[$key]['default']=$rec['default'];
		 	if(isNum($rec['default'])){
		 		$info[$key]['_dbtype_ex'] .= " Default {$rec['default']}";
		 	}
		 	else{
		 		$info[$key]['_dbtype_ex'] .= " Default '{$rec['default']}'";
		 	}
		 }
		 ksort($info[$key]);
	}
	//if(stringContains($table,'_users')){echo printValue($info).printValue($recs);exit;}
	if($getmeta){
	    //Get a list of the metadata for this table
	    $metaopts=array('-table'=>"_fielddata",'-notimestamp'=>1,'tablename'=>$table);
	    if(strlen($field)){$metaopts['fieldname']=$field;}
	    $meta_recs=getDBRecords($metaopts);
	    if(is_array($meta_recs)){
			foreach($meta_recs as $meta_rec){
				$name=$meta_rec['fieldname'];
				if(!isset($info[$name]['_dbtype'])){continue;}
				foreach($meta_rec as $key=>$val){
					if(preg_match('/^\_/',$key)){continue;}
					$info[$name][$key]=$val;
				}
            }
        }
	}
	ksort($info);
	if(count($info)){$databaseCache['getDBFieldInfo'][$dbcachekey]=$info;}
	return $info;
	}
//---------- begin function mysqlTableInfo
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function mysqlTableInfo($table){
	global $CONFIG;
	/*
		in 5.7+ need to convert generated column to
			GENERATED ALWAYS AS (JSON_EXTRACT(params, "$.block_message"))
				generation_expression holds the additional generated info:
					json_extract(jdoc,'$.post_status')
				extra is virtual generated
				column_key is MUL
	*/
    $query="SELECT
    	*
    FROM
		information_schema.columns
    WHERE
		table_schema='{$CONFIG['dbname']}'
		and table_catalog='def'
		and table_name='{$table}'
	";
    $recs=getDBRecords(array('-query'=>$query,'-index'=>'column_name'));
    //echo $query.printValue($recs);exit;
    $info=array();
    foreach($recs as $field=>$rec){
    	$info[$field]=array(
			'_dbtable' => $table,
			'_dbtype'	=> $rec['data_type'],
			'_dbcoltype'=> $rec['column_type'],
			'_dbsize'	=> strlen($rec['character_maximum_size'])?$rec['character_maximum_size']:$rec['numeric_precision']
		);
		if(strtolower($rec['is_nullable'])=='no'){$info[$field]['_dbflags']='not_null';}
		$rec['_dblength']=$rec['_dbsize'];

	}
	return $info;
}
//---------- begin function getDBFieldNames---------------------------------------
/**
* @deprecated use getDBFields instead
* @exclude  - this function is deprecated and thus excluded from the manual
*/
function getDBFieldNames($table='',$allfields=0){
	return getDBFields($table,$allfields);
}
//---------- begin function getDBFormRecord
/**
* @describe single record wrapper for getDBFormRecords - returns first record from query
* @param params array - requires either -list or -table or -query
*	_formname string - form name to return from the _forms table
*	other field/value pairs filter the query results. Any field or XML key can be used
* @return array - field/value recordset
* @usage $recs=getDBFormRecord(array('_formname'=>"contact",'gender'=>'F')); return records where _formname=contacts and gender is F
*/
function getDBFormRecord($params=array()){
	$recs=getDBFormRecords($params);
	if(is_array($recs)){
		foreach($recs as $rec){return $rec;}
    	}
    return $recs;
	}
//---------- begin function getDBFormRecords
/**
* @describe returns records and xml data from the _forms table. Any key in the xml data can be used to limit results
* @param params array - requires either -list or -table or -query
*	_formname string - form name to return from the _forms table
*	other field/value pairs filter the query results. Any field or XML key can be used
* @return array - array of field/value recordsets
* @usage $recs=getDBFormRecords(array('_formname'=>"contact",'gender'=>'F')); return records where _formname=contacts and gender is F
*/
function getDBFormRecords($params=array()){
    if(!isset($params['_formname']) && !isset($params['_id'])){return 'getDBFormRecords Error: No formname';}
    $opts=array('-table'=>"_forms",'-notimestamp'=>1);
    $fields=getDBFields("_forms",1);
    $filtered=array();
    foreach($fields as $field){
		if(isset($params[$field])){
			$opts[$field]=$params[$field];
			$filtered[$field]++;
			}
    	}
    //add -order and -where to query opts
    foreach($params as $key=>$val){
		if(!preg_match('/^\-/',$key)){continue;}
		$opts[$key]=$val;
		$filtered[$key]++;
		}
    $forms=getDBRecords($opts);
	if(!is_array($forms)){return "No records";}
	$recs=array();
	//$allkeys=array();
	//$errorcnt=0;
	foreach($forms as $form){
		$rec=array();
		$xml_array=xml2Array($form['_xmldata']);
		if(isset($xml_array['request']['server'])){
			foreach($xml_array['request']['server'] as $key=>$val){
				$rec[$key]=trim($val);
			}
		}
		if(isset($xml_array['request']['data'])){
			foreach($xml_array['request']['data'] as $key=>$val){
				if(preg_match('/^\_(action|honeypot|botcheck|xmldata)$/',$key)){continue;}
				if(preg_match('/^u\_\_/',$key)){continue;}
				if(is_array($val)){$val=implode(':',$val);}
				else{$val=trim(removeHtml($val));}
				if(strlen($val)==0){continue;}
				$rec[$key]=$val;
			}
		}
		//load table column values
		foreach($form as $key=>$val){
			if(preg_match('/^\_(action|honeypot|botcheck|xmldata)$/',$key)){continue;}
			if(strlen($val)){$rec[$key]=$val;}
		}
		//check params for additional filters
		$skip=0;
		foreach($params as $key=>$val){
			if(isset($filtered[$key])){continue;}
			if(!isset($rec[$key])){$skip++;continue;}
			if(preg_match('/^\%(.+?)\%$/',$val,$smatch)){
				if(!stristr($rec[$key],$smatch[1])){$skip++;continue;}
			}
			elseif(preg_match('/(.+?)\%$/',$val,$smatch)){
				if(!stringBeginsWith($rec[$key],$smatch[1])){$skip++;continue;}
			}
			elseif(preg_match('/^%(.+?)/',$val,$smatch)){
				if(!stringEndsWith($rec[$key],$smatch[1])){$skip++;continue;}
			}
			elseif(strtolower($rec[$key]) != strtolower($val)){$skip++;continue;}
        }
        if($skip==0){array_push($recs,$rec);}
	}
	unset($filtered);
	unset($fields);
	return $recs;
}
//---------- begin function getDBIndexes
/**
* @describe returns indexes for specified (or all if none specified) tables
* @param [tables] mixed - array of table names or a single table name
* @param [dbname] string - name of database - defaults to current database
* @return array
* @usage $indexes=getDBIndexes(array('note'));
*/
function getDBTableIndexes($table){
	return getDBIndexes($table);
}
/**
* @exclude  - depricated - use getDBTableIndexes
*/
function getDBIndexes($tables=array(),$dbname=''){
	if(!is_array($tables)){$tables=array($tables);}
	$indexes=array();
	if(count($tables)==0){$tables=getDBTables($dbname);}
	foreach($tables as $table){
		$recs=databaseIndexes($table);
		if(is_array($recs)){
			foreach($recs as $rec){
				$rec['tablename']=$table;
				array_push($indexes,$rec);
	        	}
			}
    	}
    return $indexes;
	}
//---------- begin function getDBRelatedRecords
/**
* @describe returns all records in $table with $values as _id - used in getting related records
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBRelatedRecords($table,$values){
	global $databaseCache;
	if(!is_array($values)){$values=preg_split('/[\,\;\:]+/',$values);}
	sort($values);
	$values=implode(',',$values);
	$cachekey=strtolower($table).sha1($values);
	if(isset($databaseCache['getDBRelatedRecords'][$cachekey])){
		return $databaseCache['getDBRelatedRecords'][$cachekey];
	}
	$getopts=array('-table'=>$table,'-notimestamp'=>1,'-where'=>"_id in ({$values})",'-index'=>'_id');
	$recs=getDBRecords($getopts);
	if(!is_array($recs)){$recs='';}
	$databaseCache['getDBRelatedRecords'][$cachekey]=$recs;
	return $recs;
}
//---------- begin function getDBRelatedTable
/**
* @describe returns table that matches related field
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBRelatedTable($rfield,$dbname=''){
	$rfield=strtolower($rfield);
	if(strlen($dbname)){$dbname.='.';}
	$tablenames=array(
		$rfield,
		"{$rfield}s",
		preg_replace('/y$/i','ies',$rfield),
		preg_replace('/\_id$/i','',$rfield),
		preg_replace('/\_id$/i','s',$rfield),
		"_{$rfield}s"
	);
	foreach($tablenames as $tablename){
		if(isDBTable($tablename)){return $dbname.$tablename;}
		if(isDBTable("_{$tablename}")){return "{$dbname}_{$tablename}";}
		}
	switch($rfield){
		case '_cuser':
		case '_euser':
		case '_auser':
		case 'user_id':
		case 'owner_id':
		case 'manager_id':
			return $dbname.'_users';
			break;
    }
	return '';
}
//---------- begin function getDBSiteStats
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBSiteStats(){
	$rtn='';
	$currentDay=date("Y-m-d");
	$currentWeek=date("W");
	$currentMonth=date("m");
	$quarters=array(
		'1'=>1,'2'=>1,'3'=>1,
		'4'=>2,'5'=>2,'6'=>2,
		'7'=>3,'8'=>3,'9'=>3,
		'10'=>4,'11'=>4,'12'=>4
		);
	$currentQuarter=$quarters[(integer)$currentMonth];
	$currentYear=date("Y");
	if(!isDBTable('_access')){
		return '<div class="w_bold w_big">No access table found</div>'.PHP_EOL;
		}
	$recs=getDBRecords(array('-table'=>"_access",'-where'=>"YEAR(_cdate)=YEAR(NOW())"));
	if(!is_array($recs) || !count($recs)){
		return '<div class="w_bold w_big">No access records yet</div>'.PHP_EOL;
		}
	$fields=getDBFields("_access");
	$stats=array();
	$totals=array();
	foreach($recs as $rec){
		$page=getDBRecord(array('-table'=>'_pages','name'=>$rec['page']));
		//day
		$cdate=date("Y-m-d",$rec['_cdate_utime']);
		//week
		$cweek=date("W",$rec['_cdate_utime']);
		//month
		$cmonth=date("m",$rec['_cdate_utime']);
		//quarter
		$cquarter=$quarters[(integer)$cmonth];
		//year
		$cyear=date("Y",$rec['_cdate_utime']);
		//http_host
		$field='host';
		$val=$rec['http_host'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		//page name
		$field='page';
		$val=$page['name'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		//remote_browser
		$field='browser';
		$val=ucwords($rec['remote_browser']).' '.$rec['remote_browser_version'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		//remote_os
		$field='operating system';
		$val=$rec['remote_os'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		//remote_lang
		$field='language';
		$val=$rec['remote_lang'];
		$stats[$field][$val]['day'][$cdate]+=1;
		$stats[$field][$val]['week'][$cweek]+=1;
		$stats[$field][$val]['month'][$cmonth]+=1;
		$stats[$field][$val]['quarter'][$cquarter]+=1;
		$stats[$field][$val]['year'][$cyear]+=1;
		$totals[$field]['day'][$cdate]+=1;
		$totals[$field]['week'][$cweek]+=1;
		$totals[$field]['month'][$cmonth]+=1;
		$totals[$field]['quarter'][$cquarter]+=1;
		$totals[$field]['year'][$cyear]+=1;
		}
	$rowdates=array();
	$days=array(6,5,4,3,2,1,0);
	$rtn .= '<table class="w_table w_pad w_border">'.PHP_EOL;
	//Table Header Row
	$rtn .= '	<tr class="w_top">'.PHP_EOL;
	$rtn .= '		<th colspan="2">Stats</th>'.PHP_EOL;
	//show the last 7 days
	foreach($days as $num){
		$ctime=strtotime("{$num} days ago");
		$cdate=date('D\<\b\r\>d',$ctime);
		$rtn .= '		<th>'.$cdate.'</th>'.PHP_EOL;
		array_push($rowdates,date("Y-m-d",$ctime));
		}
	//Week
	$rtn .= '		<th>Week<br>'.$currentWeek.'</th>'.PHP_EOL;
	//show last three months
	$months=array(2,1,0);
	foreach($months as $month){
		$ctime=strtotime("{$month} months ago");
		$cdate=date('M',$ctime);
		$rtn .= '		<th>'.$cdate.'</th>'.PHP_EOL;
		}
	//show quarters
	$quarters=array(1,2,3,4);
	foreach($quarters as $quarter){
		$rtn .= '		<th>QTD<br>Q'.$quarter.'</th>'.PHP_EOL;
		}
	//Year
	$rtn .= '		<th>YTD<br>'.$currentYear.'</th>'.PHP_EOL;
	$rtn .= '	</tr>'.PHP_EOL;
	//Rows
	$types=array_keys($stats);
	sort($types);
	$row=0;
	$ctype='';
	foreach($types as $type){
		$titles=array_keys($stats[$type]);
		sort($titles);
		foreach($titles as $title){
			$row++;
			$rtn .= '	<tr align="right"';
			if(isFactor($row,2)){$rtn .= ' bgcolor="#e8e8e8"';}
			$rtn .= '>'.PHP_EOL;
			$xtype=ucwords($type);
			if($ctype==$type){$xtype='';}
			$ctype=$type;
			$rtn .= '		<td class="w_align_left"><b>'.$xtype.'</b></td>'.PHP_EOL;
			$rtn .= '		<td class="w_align_left">'.$title.'</td>'.PHP_EOL;
			//days
			foreach($rowdates as $rdate){
				$rtn .= '		<td';
				if($rdate==$currentDay){$rtn .= ' bgcolor="#dbe7f2"';}
				$rtn .= '>';
				if(isset($stats[$type][$title]['day'][$rdate])){
					$rtn .= numberFormat($stats[$type][$title]['day'][$rdate]);
					}
				$rtn .= '</td>'.PHP_EOL;
				}
			//week
			$rtn .= '<td>';
			if(isset($stats[$type][$title]['week'][$currentWeek])){
				$rtn .= numberFormat($stats[$type][$title]['week'][$currentWeek]);
				}
			$rtn .= '</td>';
			//month
			foreach($months as $month){
				$ctime=strtotime("{$month} months ago");
				$cmonth=date('m',$ctime);
				$rtn .= '		<td';
				if($cmonth==$currentMonth){$rtn .= ' bgcolor="#dbe7f2"';}
				$rtn .= '>';
				if(isset($stats[$type][$title]['month'][$cmonth])){
					$rtn .= numberFormat($stats[$type][$title]['month'][$cmonth]);
					}
				$rtn .= '</td>'.PHP_EOL;
				}
			//quarters
			foreach($quarters as $quarter){
				$rtn .= '		<td';
				if($quarter==$currentQuarter){$rtn .= ' bgcolor="#dbe7f2"';}
				$rtn .= '>';
				if(isset($stats[$type][$title]['quarter'][$quarter])){
					$rtn .= numberFormat($stats[$type][$title]['quarter'][$quarter]);
					}
				$rtn .= '</td>'.PHP_EOL;
				}
			//Year
			$rtn .= '		<td>';
			if(isset($stats[$type][$title]['year'][$currentYear])){
				$rtn .= numberFormat($stats[$type][$title]['year'][$currentYear]);
				}
			$rtn .= '</td>'.PHP_EOL;
			$rtn .= '	</tr>'.PHP_EOL;
			}
		//total row for this type
		$rtn .= '	<tr align="right>"';
		$xtype=ucwords($type);
		if($ctype==$type){$xtype='';}
		$ctype=$type;
		$rtn .= '		<th align="right" colspan="2" align="right">'.ucwords($type).' Totals</th>'.PHP_EOL;
		//days
		foreach($rowdates as $rdate){
			$rtn .= '		<th align="right"';
			if($rdate==$currentDay){$rtn .= ' bgcolor="#dbe7f2"';}
			$rtn .= '>';
			if(isset($totals[$type]['day'][$rdate])){
				$rtn .= numberFormat($totals[$type]['day'][$rdate]);
				}
			$rtn .= '</th>'.PHP_EOL;
			}
		//week
		$rtn .= '<th align="right">';
		if(isset($totals[$type]['week'][$currentWeek])){
			$rtn .= numberFormat($totals[$type]['week'][$currentWeek]);
			}
		$rtn .= '</th>';
		//month
		foreach($months as $month){
			$ctime=strtotime("{$month} months ago");
			$cmonth=date('m',$ctime);
			$rtn .= '		<th align="right"';
			if($cmonth==$currentMonth){$rtn .= ' bgcolor="#dbe7f2"';}
			$rtn .= '>';
			if(isset($totals[$type]['month'][$cmonth])){
				$rtn .= numberFormat($totals[$type]['month'][$cmonth]);
				}
			$rtn .= '</th>'.PHP_EOL;
			}
		//quarters
		foreach($quarters as $quarter){
			$rtn .= '		<th align="right"';
			if($quarter==$currentQuarter){$rtn .= ' bgcolor="#dbe7f2"';}
			$rtn .= '>';
			if(isset($totals[$type]['quarter'][$quarter])){
				$rtn .= numberFormat($totals[$type]['quarter'][$quarter]);
				}
			$rtn .= '</th>'.PHP_EOL;
			}
		//Year
		$rtn .= '		<th align="right">'.numberFormat($totals[$type]['year'][$currentYear]).'</th>'.PHP_EOL;
		$rtn .= '	</tr>'.PHP_EOL;
		}
	$rtn .= '</table>'.PHP_EOL;
	return $rtn;
	}
//---------- begin function getDBTableInfo
/**
* @describe returns meta data associated with table
* @param params array
*	-table string -  table name
*	[-fieldinfo] boolean - return field meta data also - defaults to false
* @return array
* @usage $info=getDBTableInfo(array('-table'=>'note'));
*/
function getDBTableInfo($params=array()){
    if(!isset($params['-table'])){return 'getDBTableInfo Error: No table' . printValue($params);}
    global $USER;
    $table_parts=preg_split('/\./', $params['-table']);
	$infotable="_tabledata";
	$infotablename=$params['-table'];
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$infotable="{$dbname}.{$infotable}";
		$infotablename=implode('.',$table_parts);
		}
    $info=getDBRecord(array('-table'=>$infotable,'tablename'=>$infotablename));
    $info['fields'] = getDBFields($params['-table']);
    $info['table'] = $params['-table'];
	if(isset($params['-fieldinfo']) && $params['-fieldinfo']==true){
		$info['fieldinfo']=getDBFieldInfo($params['-table'],1);
    	}
    //Is the user administrator or not?
    if(isAdmin()){$info['isadmin']=true;}

    else{$info['isadmin']=false;}
	//turn table field data into arrays
	$flds=array('listfields','listfields_mod');
	foreach($flds as $fld){
		if(isset($info[$fld]) && strlen($info[$fld])){
			$info[$fld]=preg_split('/[\r\n\t\s\,\:\ ]+/',$info[$fld]);
			}
		else{$info["default_{$fld}"]=$info['fields'];}
    	}
    $flds=array('sortfields','sortfields_mod');
	foreach($flds as $fld){
		if(isset($info[$fld]) && strlen($info[$fld])){
			$info[$fld]=preg_split('/[\r\n\t\,\:]+/',$info[$fld]);
			}
		else{$info["default_{$fld}"]=$info['fields'][0];}
    	}
    $flds=array('formfields','formfields_mod');
	foreach($flds as $fld){
		if(isset($info[$fld]) && strlen($info[$fld])){
			$rows=preg_split('/[\r\n\,]+/',$info[$fld]);
			$info[$fld]=array();
			foreach($rows as $row){
				//check for html and php
				$row=trim($row);
				if(isXML($row)){array_push($info[$fld],$row);}
				else{
					$line=preg_split('/[\t\s\:]+/',$row);
					array_push($info[$fld],$line);
					}
	            }
			}
		else{$info["default_{$fld}"]=$info['fields'];}
    	}
    //echo printValue($info);
    return $info;
	}
//---------- begin function getDBTableStatus
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBTableStatus(){
	global $PAGE;
	$rtn='';
	$recs=getDBRecords(array('-query'=>"show table status"));
	//add some extra data to the result
	$cnt=count($recs);
	for($i=0;$i<$cnt;$i++){
		$indexes=getDBIndexes(array($recs[$i]['name']));
		$recs[$i]['indexes']=count($indexes);
		$fields=getDBFields($recs[$i]['name']);
		$recs[$i]['fields']=count($fields);
		$recs[$i]['records']=getDBCount(array('-table'=>$recs[$i]['name']));
    }
	$rtn .= buildTableBegin(2,1);
	$sort=$_REQUEST['_sort'];
	unset($_REQUEST['_sort']);
	$tlink=buildUrl($_REQUEST);
	$rtn .= buildTableTH(array(
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=name">Table</a>',
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=records">Records</a>',
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=data_length">Size</a>',
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=fields">Fields</a>',
		'<a class="w_link w_white" href="/'.$PAGE['name'].'?'.$tlink.'&_sort=indexes">Indexes</a>',
		'Created','Updated','Next ID','Format','Char Set'
		));
	$totals=array();
	if(isset($sort)){$recs=sortArrayByKeys($recs,array($sort=>SORT_ASC));}
	foreach($recs as $rec){
		$totals['records']+=$rec['records'];
		$totals['size']+=$rec['data_length'];
		$totals['indexes']+=$rec['indexes'];
		$totals['fields']+=$rec['fields'];
		$rtn .= '	<tr align="right">'.PHP_EOL;
		$rtn .= '		<td class="w_align_left">'.$rec['name'].'</td>'.PHP_EOL;
		$rtn .= '		<td>'.$rec['records'].'</td>'.PHP_EOL;
		$rtn .= '		<td>'.verboseSize($rec['data_length']).'</td>'.PHP_EOL;
		$rtn .= '		<td>'.$rec['fields'].'</td>'.PHP_EOL;
		$rtn .= '		<td>'.$rec['indexes'].'</td>'.PHP_EOL;
		$rtn .= '		<td>'.$rec['create'].'</td>'.PHP_EOL;
		$rtn .= '		<td>'.$rec['update_time'].'</td>'.PHP_EOL;
		$rtn .= '		<td>'.$rec['auto_increment'].'</td>'.PHP_EOL;
		$rtn .= '		<td>'.$rec['row_format'].'</td>'.PHP_EOL;
		$rtn .= '		<td>'.$rec['collation'].'</td>'.PHP_EOL;
		$rtn .= '	</tr>'.PHP_EOL;
    }
    $rtn .= buildTableTH(array('Totals:',$totals['records'],verboseSize($totals['size']),$totals['fields'],$totals['indexes'],'','','','',''),array('align'=>"right"));
	$rtn .= buildTableEnd();
	return $rtn;
}
//---------- begin function getDBTime--------------------
/**
* @describe returns the current database time
* @return integer - timestamp
* @usage $t=getDBTime();
*/
function getDBTime(){
	return dbGetTime();
}
function dbGetTime(){
	$nrec=getDBRecord("select now() as now");
	return strtotime($nrec['now']);
}
function getDBTimezone(){
	$rec=getDBRecord("SELECT @@session.time_zone as stz");
	if($rec['stz']=='SYSTEM'){
		if(isWindows()){
			$out=cmdResults("tzutil /g");
			return trim($out['stdout']);
		}
		$out=cmdResults("date +\"%Z %z\"");
		$tz=preg_replace('/[+0-9]+$/','',trim($out['stdout']));
		return trim($tz);
	}
	return $rec['stz'];
}
//---------- begin function getDBQuery--------------------
/**
* @describe builds a database query based on params
* @param params array
*	[-table] string - table to query
*	[-notimestamp] - turn off building _utime fields for date and time fields
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return string - query string
* @usage $query=getDBQuery(array('-table'=>$table,'field1'=>$val1...));
*/
function getDBQuery($params=array()){
	if(!isset($params['-table'])){return 'getDBQuery Error: No table' . printValue($params);}
	//get field info for this table
	$info=getDBFieldInfo($params['-table']);
	if(!is_array($info)){return $info;}
	$loopfields=array();
	if(isset($params['-fields'])){
		if(is_array($params['-fields'])){$loopfields=$params['-fields'];}
		else{$loopfields=preg_split('/\,+/',trim($params['-fields']));}
    }
    else{$loopfields=array_keys($info);}
    //now add the _utime to any date fields
    //if($params['-table']=='events_data' && count($loopfields)>1){echo printValue($loopfields);exit;}
    $fields=array();
    foreach ($loopfields as $field){
		array_push($fields,$field);
		//add timestamp unless $params['-notimestamp'] is set
		if(!isset($params['-notimestamp']) && isset($info[$field]['_dbtype'])){
			if(preg_match('/^(datetime|date|timestamp)$/i',$info[$field]['_dbtype'])){
				if(isMysqli() || isMysql()){
					array_push($fields,'UNIX_TIMESTAMP('.$field.') as '.$field.'_utime');
				}
				elseif(isPostgreSQL()){
					array_push($fields,$field.'::abstime::int4 as '.$field.'_utime');
					}
				elseif(isMssql()){
					//MS SQL Unix_timestamp equivilent: select datediff(s, '19700101', <fieldname>)
					array_push($fields,'datediff(s, \'19700101\', '.$field.') as '.$field.'_utime');
				}
			}
			elseif(preg_match('/^time$/i',$info[$field]['_dbtype'])){
				if(isMysqli() || isMysql()){
					array_push($fields,'TIME_TO_SEC('.$field.') as '.$field.'_seconds');
				}
				elseif(isMssql()){
					//MS SQL Unix_timestamp equivilent: select datediff(s, '19700101', <fieldname>)
					array_push($fields,'datediff(s, \'19700101\', '.$field.') as '.$field.'_seconds');
				}
			}
		}
	}
	$query='select ';
	$query .= implode(',',$fields).' from ' . $params['-table'];
	//build where clause
	$where = getDBWhere($params);
	if(strlen($where)){$query .= " where {$where}";}
	//Set order by if defined
    if(isset($params['-group'])){$query .= ' group by '.$params['-group'];}
	//Set order by if defined
    if(isset($params['-order'])){$query .= ' order by '.$params['-order'];}
    //Set limit if defined
    if(isset($params['-limit'])){$query .= ' limit '.$params['-limit'];}
    //Set offset if defined
    if(isset($params['-offset'])){$query .= ' offset '.$params['-offset'];}
    //if($params['-table']=='events_data' && count($loopfields)>1){echo printValue($loopfields).$query;exit;}
    return $query;
}
//---------- begin function getDBWhere--------------------
/**
* @describe builds a database query where clause only
* @param params array
*	[-table] string - table to query
*	[-notimestamp] - turn off building _utime fields for date and time fields
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return string - query where string
* @usage $where=getDBWhere(array('-table'=>$table,'field1'=>$val1...));
*/
function getDBWhere($params,$info=array()){
	if(!isset($info) || !count($info)){
		$info=getDBFieldInfo($params['-table']);
	}
	$ands=array();
	//echo printValue($info);exit;
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!isset($info[$k])){continue;}
		if(is_null($params[$k])){unset($params[$k]);continue;}
		if(is_array($params[$k])){
            $params[$k]=implode(':',$params[$k]);
		}
		if(!strlen(trim($params[$k]))){continue;}
        $params[$k]=str_replace("'","''",$params[$k]);
        $v=databaseEscapeString($params[$k]);
        switch(strtolower($info[$k]['_dbtype'])){
        	case 'int':
        	case 'integer':
        	case 'tinyint':
        	case 'number':
        	case 'float':
        		if(isNum($v)){
        			$ands[]="{$k}={$v}";
        		}
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
	return implode(' and ',$ands);
}
//---------- begin function getDBFiltersString
/**
* @describe returns filters as a string to use in a where clause
* @param table string - tablename
* @param filters string - multiline string with filters in field-oper-val format
* @return string
* @usage $filters=getDBFiltersString($table,$filters));
*/
function getDBFiltersString($table,$filters){
	$tfields=getDBFields($table);
	$sets=preg_split('/[\r\n]+/',$filters);
    $wheres=array();
    foreach($sets as $set){
        list($field,$oper,$val)=preg_split('/\-/',$set,3);
        if(!strlen($field) ||  !strlen($oper) || $field=='null'){continue;}
        if($field=='*'){
			$fields=array('_id');
			$fields=array_merge($fields,$tfields);
		}
        else{$fields=array($field);}
		switch($oper){
        	case 'ct':
        		//contains
        		$ors=array();
        		foreach($fields as $field){
					$ors[]="{$field} like '%{$val}%'";
				}
				$orstr=implode(" or ",$ors);
				$wheres[]="({$orstr})";
			break;
			case 'nct':
        		//not contains
        		$ors=array();
        		foreach($fields as $field){
					$ors[]="{$field} not like '%{$val}%'";
				}
				$orstr=implode(" or ",$ors);
				$wheres[]="({$orstr})";
			break;
			case 'ca':
				//Contains Any of These
				$vals=preg_split('/\,/',$val);
				$ors=array();
				foreach($vals as $val){
					$val=trim($val);
					foreach($fields as $field){
                    	$ors[]="{$field} like '%{$val}%'";
					}
				}
				if(count($ors)){
					$orstr=implode(' or ',$ors);
					$wheres[]="({$orstr})";
				}
			break;
			case 'nca':
				//Not contain any of these
				$vals=preg_split('/\,/',$val);
				foreach($vals as $val){
					$val=trim($val);
					foreach($fields as $field){
                    	$wheres[]="{$field} not like '%{$val}%'";
					}
				}
			break;
			case 'eq':
				//equals
				foreach($fields as $field){
					$wheres[]="{$field} = '{$val}'";
				}
			break;
			case 'neq':
				//equals
				foreach($fields as $field){
					$wheres[]="{$field} != '{$val}'";
				}
			break;
			case 'ea':
				//Equals Any of These
				$vals=preg_split('/\,/',$val);
				$ors=array();
				foreach($vals as $val){
					$val=trim($val);
					foreach($fields as $field){
                    	$ors[]="{$field} = '{$val}'";
					}
				}
				if(count($ors)){
					$orstr=implode(' or ',$ors);
					$wheres[]="({$orstr})";
				}
			break;
			case 'nea':
				//Not equals any of these
				$vals=preg_split('/\,/',$val);
				foreach($vals as $val){
					$val=trim($val);
					foreach($fields as $field){
                    	$wheres[]="{$field} != '{$val}'";
					}
				}
			break;
			case 'gt':
				//greater than
				foreach($fields as $field){
					$wheres[]="{$field} > '{$val}'";
				}
			break;
			case 'lt':
				//less than
				foreach($fields as $field){
					$wheres[]="{$field} < '{$val}'";
				}
			break;
			case 'egt':
				//Equals or Greater than
				foreach($fields as $field){
					$wheres[]="{$field} >= '{$val}'";
				}
			break;
			case 'elt':
				//Less than or Equals
				foreach($fields as $field){
					$wheres[]="{$field} =< '{$val}'";
				}
			break;
			case 'ib':
				//Is Blank
				foreach($fields as $field){
					$wheres[]="({$field} is null or {$field}='')";
				}
			break;
			case 'nb':
				//Is Not Blank
				foreach($fields as $field){
					$wheres[]="({$field} is not null and {$field} != '')";
				}
			break;
		}
	}
	if(count($wheres)){
		return implode(' and ',$wheres);
	}
	return '';
}
//---------- begin function getDBRecord-------------------
/**
* @describe returns a single multi-dimensional record based on params
* @param params array - returns a key/value array for each recordset found
*	[-table] string - table to query
*	[-query] string - exact query to use instead of passing in params
*	[-where] string - where clause to filter results by
*	[-index] string - field to use as index.  i.e  '-index'=>'_id'
*	[-json] array - fields to decode as json.  returns decoded json values into field_json key
*	[-random] integer  - number of random records to return from the results
*	[-trigger] boolean - process results through the GetRecord trigger function
*	[-relate] mixed - field/table pairs of fields to get related records from other tables
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return array
* @usage $rec=getDBRecord(array('-table'=>$table,'field1'=>$val1...));
*/
function getDBRecord($params=array(),$id=0,$flds=''){
	if(isPostgreSQL()){return postgresqlGetDBRecord($params,$id,$flds);}
	elseif(isSqlite()){return sqliteGetDBRecord($params,$id,$flds);}
	elseif(isOracle()){return oracleGetDBRecord($params,$id,$flds);}
	elseif(isMssql()){return mssqlGetDBRecord($params,$id,$flds);}

	//check for shortcut hack
	if(!is_array($params) && isset($id) && $id > 0){
		$params=array(
			'-table'=>$params,
			'_id'=>$id
		);
		if(isset($flds) && strlen($flds)){
			$params['-fields']=$flds;
		}
		unset($id);
	}
	if(!is_array($params) && is_string($params)){
    	$params=array('-query'=>$params);
	}
	if(!isset($params['-table']) && !isset($params['-query'])){return "getDBRecord Error: no table or query defined" . printValue($params);}
	//if(isset($params['-table'])){echo printValue($params);}
	if(isset($params['-random'])){
		$params['-random']=1;
		unset($params['-limit']);
	}
    else{$params['-limit']=1;}
	$list=getDBRecords($params);
	//echo printValue($params).printValue($list);
	if(!is_array($list)){return $list;}
	if(!count($list)){return '';}
	if(!isset($list[0])){return '';}
	$rec=array();
	//get the first record and return it. the index may not be zero if they indexed it differently.
	foreach($list as $index=>$crec){
		foreach($crec as $key=>$val){
			$rec[$key]=$val;
		}
    	break;
	}
	if(count($rec)){
		if(isset($params['-table']) && $params['-table']=='_wpass'){
        	return wpassDecryptArray($rec);
		}
		return $rec;
		}
	return null;
	}
//---------- begin function getDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=getDBRecordById('comments',7);
*/
function getDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "getDBRecordById Error: No Table";}
	if($id == 0){return "getDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=getDBRecord($recopts);
	return $rec;
}
//---------- begin function editDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=editDBRecordById('comments',7,array('name'=>'bob'));
*/
function editDBRecordById($table='',$id=0,$params=array(),$debug=0){
	if(!strlen($table)){
		return debugValue("editDBRecordById Error: No Table");
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
	if(!count($ids)){return debugValue("getDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("getDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("getDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$params['-nocache']=1;
	if($debug==1){return "Debug editDBRecordById: ".printValue($params);}
	return editDBRecord($params);
}
//---------- begin function processDBRecords --------------------
/**
* @describe process table records through a function. Returns number of records processed
* @param func string - function to call.  The rec array will be passed to this function
* @param params array - params to filter recordset by - same params as getDBRecords
* @return cnt integer - number of records processed
* @usage $cnt=processDBRecords('myCustomFunction',array('-table'=>'states'));
*/
function processDBRecords($func_name,$params=array()){
	$params['-process']=$func_name;
	return getDBRecords($params);
}
//---------- begin function getDBRecords-------------------
/**
* @describe returns a multi-dimensional array of records found
* @param params array - returns a key/value array for each recordset found
*	[-table] string - table to query
*	[-query] string - exact query to use instead of passing in params
*	[-where] string - where clause to filter results by
*	[-index] string - field to use as index.  i.e  '-index'=>'_id'
*	[-json] array - fields to decode as json.  returns decoded json values into field_json key
*	[-random] integer  - number of random records to return from the results
*	[-trigger] boolean - process results through the GetRecord trigger function
*	[-relate] mixed - field/table pairs of fields to get related records from other tables
*	[-notimestamp] boolean - if true disables adding extra _utime data to date and datetime fields. Defaults to false
*	[-trigger] boolean - set to false to disable trigger functionality
* 	[-process] string - function to run results through instead of returning them.  If this is set the number of records processed will be returned
*	Other key value pairs passed in are used to filter the results.  i.e. 'active'=>1
* @return array
* @usage $recs=getDBRecords(array('-table'=>$table,'field1'=>$val1...));
*/
function getDBRecords($params=array()){
	if(isPostgreSQL()){return postgresqlGetDBRecords($params,$id,$flds);}
	elseif(isSqlite()){return sqliteGetDBRecords($params,$id,$flds);}
	elseif(isOracle()){return oracleGetDBRecords($params,$id,$flds);}
	elseif(isMssql()){return mssqlGetDBRecords($params,$id,$flds);}
	$function='getDBRecords';
	global $CONFIG;
	global $databaseCache;
	//check for just a query instead of a params array and convert it getDBRecords($query)
	if(!is_array($params) && is_string($params)){
    	$params=array('-query'=>$params);
	}
	//check for -process
	if(isset($params['-process']) && !function_exists($params['-process'])){
		return "Error: Function is not loaded to process with: {$params['-process']}";
	}
	
	if(isset($params['-query'])){$query=$params['-query'];}
	elseif(isset($params['-table'])){
		if(!isDBTable($params['-table'])){
			debugValue("getDBRecords Error: No table: {$params['-table']}");
			return array();
		}
		$query=getDBQuery($params);
	}
	else{
		setWasqlError(debug_backtrace(),"No table: ".json_encode($params,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE));
		return "No table. ".json_encode($params,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE);
	}
	//do we already have a query for this stored?
	$query_sha=sha1($CONFIG['dbname'].$query);
	if(!isset($params['-nocache']) && isset($databaseCache['getDBRecords'][$query_sha]) && is_array($databaseCache['getDBRecords'][$query_sha])){
		return $databaseCache['getDBRecords'][$query_sha];
    }
    $start=microtime(true);
    //-json
    if(isset($params['-json'])){
		$jsonfields=array();
		if(!is_array($params['-json'])){
        	$params['-json']=preg_split('/\,/',trim($params['-json']));
		}
		foreach($params['-json'] as $jsonfield){
        	$jsonfields[$jsonfield]=1;
		}
	}
	// Perform Query
	//echo "{$query}<hr>".PHP_EOL;exit;
	$query_result=@databaseQuery($query);
	//echo "{$query}<hr>".printValue($query_result).PHP_EOL;exit;
  	if(!$query_result){
		$e=getDBError();
		//check to see if we can fix the error
		if(isset($params['-query']) && stringContains($params['-query'],'from _sessions') && stringContains($e,"Unknown column 'json'")){
        	$query="ALTER TABLE _sessions ADD json ".databaseDataType('tinyint')." NOT NULL DEFAULT 1;";
			$ok=executeSQL($query);
			return getDBRecords($params);
		}
		if(isset($params['-dbname']) && strlen($CONFIG['dbname'])){
			if(!databaseSelectDb($CONFIG['dbname'])){
				return setWasqlError(debug_backtrace(),getDBError(),$query);
				}
			}
		return setWasqlError(debug_backtrace(),$e,$query);
  	}
	$rows   = databaseNumRows($query_result);
	//echo "{$rows} - {$query}<hr>".PHP_EOL;exit; 
	if(!$rows){
		if(isset($params['-dbname']) && strlen($CONFIG['dbname'])){
			if(!databaseSelectDb($CONFIG['dbname'])){return setWasqlError(debug_backtrace(),getDBError(),$query);}
		}
		return null;
	}
	$list=array();
	$x=0;
	$randompick=0;
	$random=array();
	if(isset($params['-random']) && isNum($params['-random'])){
		$cnt=databaseNumRows($query_result);
		$max=$params['-random'];
		while((count($random) < $cnt) && (count($random) < $max)){
			$r=rand(0,$cnt-1);
			if(isNum($r) && !in_array($r,$random)){$random[]=$r;}
        }
        $randompick=1;
        //echo "random:".printValue($random);
	}
	$rx=0;
	while ($row = databaseFetchAssoc($query_result)) {
		//echo printValue($row);
		if($randompick==1){
			if(count($list) >= count($random)){
				break;
			}
			//echo "rx:{$rx}<br />";
			if(!in_array($rx,$random)){
				$rx+=1;
				continue;
			}
			
			//get out of the loop once we have filled our random count
        }
        if(isset($params['-process'])){
			$ok=call_user_func($params['-process'],$row);
			$x++;
			continue;
		}
		if(!isset($params['-lowercase']) || $params['-lowercase'] != false){
			$row=array_change_key_case($row);
		}
		foreach($row as $key=>$val){
			if(isset($params['-eval']) && is_callable($params['-eval'])){
				if(isset($params['-noeval'])){
					if(!is_array($params['-noeval'])){$params['-noeval']=preg_split('/\,/',$params['-noeval']);}
					if(!in_array($key,$params['-noeval'])){
						$val=call_user_func($params['-eval'],$val);
					}
				}
				else{
					$val=call_user_func($params['-eval'],$val);
				}
			}
			elseif(isset($params["-eval_{$key}"]) && is_callable($params["-eval_{$key}"])){
				$val=call_user_func($params["-eval_{$key}"],$val);
			}
			if(isset($params['-index'])){
				$index='';
				if(is_array($params['-index']) && count($params['-index'])){
					$indexes=array();
					foreach($params['-index'] as $fld){
						$indexes[] = $row[$fld];
					}
					$index=implode(',',$indexes);
					$index=strtolower($index);
					$list[$index][$key]=$val;
                }
				elseif(strlen($params['-index']) && !isNum($params['-index']) && isset($row[$params['-index']])){
					$index=$row[$params['-index']];
					$index=strtolower($index);
					$list[$index][$key]=$val;
                }
                else{
					$list[$x][$key]=$val;
					//-json?
	                if(isset($jsonfields[$key])){
						$list[$x]["{$key}_json"]=json_decode($val,true);
					}
				}
                //-json?
                if(strlen($index) && isset($jsonfields[$key])){
					$list[$index]["{$key}_json"]=json_decode($val,true);
				}
            }
			else{
				$list[$x][$key]=$val;
				//-json?
                if(isset($jsonfields[$key])){
					$list[$x]["{$key}_json"]=json_decode($val,true);
				}
			}
		}
		if($randompick==1 && count($list) >= $params['-random']){break;}
		//if(isset($list[$x]) && is_array($list[$x])){ksort($list[$x]);}
		$x++;
		$rx+=1;
	}
	//Free the resources associated with the result set
	databaseFreeResult($query_result);
	if(isset($params['-process'])){return $x;}
	//determine fields returned
	$fields=array();
	foreach($list as $i=>$r){
		$fields=array_keys($r);
		break;
	}
	if(!isset($params['-nolog']) || $params['-nolog'] != 1){
		$fieldstr=implode(',',$fields);
		$row_count=count($list);
		if(isset($params['-table'])){
			logDBQuery($query,$start,$function,$params['-table'],$fieldstr,$row_count);
		}
	}
	if(isset($params['-dbname']) && strlen($CONFIG['dbname'])){
		if(!databaseSelectDb($CONFIG['dbname'])){
			return setWasqlError(debug_backtrace(),getDBError());
		}
	}
	//get related
	$related=array();
	if(isset($params['-relate'])){
		$table_parts=preg_split('/\./', $params['-table']);
		$dbname='';
		if(count($table_parts) > 1){
			$dbname=array_shift($table_parts);
		}
		if(isset($params['-table']) && isNum($params['-relate']) && $params['-relate']==1){
			$xinfo=getDBFieldMeta($params['-table'],"tvals,dvals,inputtype");
			//check for -norelate fields to skip
			$skipfields=array();
			if(isset($params['-norelate'])){
            	if(is_array($params['-norelate'])){$skipfields=$params['-norelate'];}
            	else{
                	$skipfields=preg_split('/[,:;]+/',$params['-norelate']);
				}
			}
			foreach($fields as $field){
				//skip field if it is not a valid field or if it is a -norelate field
				if(!isset($xinfo[$field]) || !is_array($xinfo[$field]) || in_array($field,$skipfields)){continue;}
				$tvals=isset($xinfo[$field]['tvals'])?trim($xinfo[$field]['tvals']):'';
				$dvals=isset($xinfo[$field]['dvals'])?trim($xinfo[$field]['dvals']):'';
				if(preg_match('/(select|checkbox)/i',$xinfo[$field]['inputtype']) && strlen($tvals) && strlen($dvals) && !preg_match('/^select/i',$tvals)){
                	//simple select list - not a query
                	$tmap=array();
                	$tmap=mapDBDvalsToTvals($params['-table'],$field);
                	reset($list);
					foreach($list as $i=>$r){
						$rval=$r[$field];
						if(is_null($rval)){$rval='';}
						if(isset($tmap[$rval])){$related[$field][$rval]=$tmap[$rval];}
						elseif(strlen($rval) && preg_match('/\:/',$rval)){
                        	$rvals=preg_split('/\:/',$rval);
                        	$dvals=array();
                        	foreach($rvals as $rval){
								 if(isset($tmap[$rval])){$dvals[$rval]=$tmap[$rval];}
							}
							if(count($dvals)){$related[$field][$r[$field]]=$dvals;}
						}
					}
				}
				else{
					$rtable=getDBRelatedTable($field,$dbname);
					if(strlen($rtable)){
						$ids=array();
						reset($list);
						foreach($list as $i=>$r){
							if(!isset($r[$field])){continue;}
							if(isNum($r[$field]) && $r[$field] > 0 && !in_array($r[$field],$ids)){$ids[]=$r[$field];}
							elseif(is_string($r[$field]) && strlen($r[$field]) && preg_match('/\:/',$r[$field])){
	                        	$rvals=preg_split('/\:/',$r[$field]);
	                        	$dvals=array();
	                        	foreach($rvals as $rval){
									if(isNum($rval) && $rval > 0 && !in_array($rval,$ids)){$ids[]=$rval;}
								}
							}
						}
						if(count($ids)){
							$related[$field]=getDBRelatedRecords($rtable,$ids);
						}
	                }
	            }
            }
        }
        elseif(is_array($params['-relate'])){
			foreach($params['-relate'] as $field=>$rtable){
				if(isDBTable($rtable)){
					$ids=array();
					reset($list);
					foreach($list as $i=>$r){
						if(isNum($r[$field]) && !in_array($r[$field],$ids)){$ids[]=$r[$field];}
						}
					if(count($ids)){
						$related[$field]=getDBRelatedRecords($rtable,$ids);
						}
                	}
            	}
        	}
    	}
    if(count($related)){
		foreach($related as $rfield=>$recs){
			reset($list);
			foreach($list as $i=>&$r){
				if(!isset($r[$rfield]) || is_null($r[$rfield]) || !strlen(trim($r[$rfield]))){continue;}
				$rval=$r[$rfield];
				if(strlen($rval) && preg_match('/\:/',$rval)){
					$xrvals=preg_split('/\:/',$rval);
					foreach($xrvals as $xrval){
						if(isset($recs[$rval][$xrval])){$r["{$rfield}_ex"][$xrval]=$recs[$rval][$xrval];}
						else{$r["{$rfield}_ex"][$xrval]=$recs[$xrval];}
                    }
				}
				elseif(isset($recs[$rval])){
					$r["{$rfield}_ex"]=$recs[$rval];
				}
				ksort($r);
            }
        }
    }
    //cache internal table select queries
    if(!isset($params['-nocache']) && !isset($_SERVER['WaSQL_AdminUserID']) && isset($params['-table']) && preg_match('/^select/i',$query) && preg_match('/^\_/',$params['-table']) && !preg_match('/^\_(pages|templates)/i',$params['-table'])){
    	$databaseCache['getDBRecords'][$query_sha]=$list;
		}
	if(is_array($list) && count($list)){
		//check for -indexes
		if(isset($params['-indexes'])){
			$indexes=$params['-indexes'];
			$newlist=array();
			foreach($list as $rec){
				if(is_array($indexes)){
					//$grouplist[$key1][$key2][$key3...]=$rec
                	$keys=array();
                	foreach($indexes as $key){
                    	$keys[]=$rec[$key];
					}
					switch(count($keys)){
                    	case 1:$newlist[$keys[0]][]=$rec;break;
                    	case 2:$newlist[$keys[0]][$keys[1]][]=$rec;break;
                    	case 3:$newlist[$keys[0]][$keys[1]][$keys[2]][]=$rec;break;
                    	case 4:$newlist[$keys[0]][$keys[1]][$keys[2]][$keys[3]][]=$rec;break;
                    	case 5:$newlist[$keys[0]][$keys[1]][$keys[2]][$keys[3]][$keys[4]][]=$rec;break;
					}
					continue;
				}
				$key=$rec[$indexes];
				$newlist[$key][]=$rec;
            }
            return $newlist;
		}

		//process GetRecord trigger functions as long as -trigger is not false
		if(isset($params['-table']) && (!isset($params['-trigger']) || ($params['-trigger']))){
			$trigger=getDBTableTrigger($params['-table']);
			$trigger_table=$params['-table'];
			//check to see if they passed a databasename with table
			$table_parts=preg_split('/\./', $trigger_table);
			if(count($table_parts) > 1){
				$dbname=array_shift($table_parts);
				$trigger_table=implode('.',$table_parts);
				}
			if(isset($trigger['functions']) && !isset($params['-notrigger']) && strlen(trim($trigger['functions']))){
		    	$ok=includePHPOnce($trigger['functions'],"{$trigger_table}-trigger_functions");
		    	//look for Before trigger
		    	if(function_exists("{$trigger_table}GetRecord")){
					foreach($list as $i=>$rec){
		        		$rec=call_user_func("{$trigger_table}GetRecord",$rec);
		        		$list[$i]=$rec;
					}
				}
			}
		}
		//echo "HERE - {$query}<hr>".printValue($list).PHP_EOL;exit;
		//echo "{$query}<br />".PHP_EOL;
		return $list;
		}
	return '';
	}
//---------- begin function getDBSchema--------------------
/**
* @describe returns schema array for specified (or all if none specified) tables
* @param tables array - if not specifed then all tables are returned
* @param force boolean - force cache to be cleared
* @return array
* @usage
*	$schema=getDBSchema('comments');
*	$schema=getDBSchema(array('comments','notes'));
*/
function getDBSchema($tables=array(),$force=0){
	if(!is_array($tables)){$tables=array($tables);}
	global $databaseCache;
	$schema=array();
	if(count($tables)==0){$tables=getDBTables();}
	$cachekey=implode(',',$tables);
	if($force==0 && isset($databaseCache['getDBSchema'][$cachekey])){
		return $databaseCache['getDBSchema'][$cachekey];
		}
	foreach($tables as $table){
		$recs=databaseDescribeTable($table);
		if(isset($recs['error'])){return $recs['error'];}
		elseif(!is_array($recs)){return "{$table} does not exist";}
		$i=0;
		foreach($recs as $rec){
			$i++;
			$rec['_id']=$i;
			$rec['tablename']=$table;
			if(strlen($rec['default']) && !isNum($rec['default'])){
				$rec['default']="'{$rec['default']}'";
			}
			$schema[]=$rec;
        }
    }
    $sortfield=isset($_REQUEST['_sort'])?$_REQUEST['_sort']:'field';
    $direction='SORT_ASC';
    if(preg_match('/^(.+?)\ (asc|desc)/i',$sortfield,$smatch)){
		$sortfield=$smatch[1];
		$direction='SORT_'.strtoupper($smatch[2]);
    	}
    if(function_exists('sortArrayByKeys')){
    	$schema=sortArrayByKeys($schema, array($sortfield=>$direction));
	}
    if($force==0){$databaseCache['getDBSchema'][$cachekey]=$schema;}
    return $schema;
	}
//---------- begin function getDBSchemaText--------------------
/**
* @describe returns schema text for specified table
* @param tables string - tablename
* @param force boolean - force cache to be cleared
* @return text
* @usage $schema=getDBSchemaText('comments');
*/
function getDBSchemaText($table,$force=0){
	if(!is_array($table)){$table=array($table);}
	$list=getDBSchema($table,$force);
	$txt='';
	//echo printValue($list);
	foreach($list as $field){
		if(preg_match('/^\_/',$field['field'])){continue;}
		$type=$field['type'];
		if($field['null']=='NO'){$type .= ' NOT NULL';}
		else{$type .= ' NULL';}
		if($field['key']=='PRI'){$type .= ' Primary Key';}
		elseif($field['key']=='UNI'){$type .= ' UNIQUE';}
		if(strlen($field['default'])){
			$type .= ' Default '.$field['default'];
			}
		if(strlen($field['extra'])){$type .= ' '.$field['extra'];}
		//if(strlen($field['expression'])){$type .= ' '.$field['expression'];}
		$txt .= trim("{$field['field']} {$type}")."".PHP_EOL;
        }
    return $txt;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function updateDBSchema($table,$lines,$new=0){
	if(!is_array($lines)){
		$lines=preg_split('/[\r\n]+/',trim($lines));
	}
	$cfields=array(
		'_id'	=> databasePrimaryKeyFieldString(),
		'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
		'_cuser'=> "int NOT NULL",
		'_edate'=> databaseDataType('datetime')." NULL",
		'_euser'=> "int NULL"
	);
	$table_parts=preg_split('/\./', $table);
	$updatetable=$table;
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		$table=implode('.',$table_parts);
	}
	switch(strtolower($table)){
		case '_forms':
			$cfields['_formname']="varchar(100) NOT NULL";
			$cfields['_xmldata']="text NOT NULL";
			break;
		case '_users':
			$cfields['_adate']=databaseDataType('datetime')." NULL";
			$cfields['_aip']="char(45) NULL";
			$cfields['_env']="text NULL";
			$cfields['_sid']="varchar(150) NULL";
			if(isPostgreSQL()){$cfields['_adate']=str_replace('datetime','timestamp',$cfields['_adate']);}
			break;
		case '_pages':
			$cfields['_adate']=databaseDataType('datetime')." NULL";
			$cfields['_aip']="char(45) NULL";
			$cfields['_amem']=databaseDataType('bigint')." NULL";
			$cfields['_auser']="integer NOT NULL Default 0";
			$cfields['_env']="text NULL";
			$cfields['_template']="integer NOT NULL Default 1";
			break;
		case '_templates':
			$cfields['_adate']=databaseDataType('datetime')." NULL";
			$cfields['_aip']="char(45) NULL";
			$cfields['_auser']="integer NOT NULL Default 0";
			break;
    }
    //remove virtual columns and add them in after.
    $virtual=array();
	$fields=array();
	foreach($lines as $line){
		if(!strlen(trim($line))){continue;}
		$oriline=$line;
		list($name,$type)=preg_split('/[\s\t]+/',$line,2);
		$name=strtolower($name);
		if(!strlen($type)){continue;}
		if(!strlen($name)){continue;}
		if(preg_match('/^\_/',$name)){continue;}
		if(preg_match('/^(.+?)STORED GENERATED(.*)$/i',$type,$m)){
			$type=preg_replace('/STORED GENERATED/i','',$type);
			$type=preg_replace('/NULL/i','',$type);
			$type=trim($type);
			$exp=getDBExpression($table,$name);
        	$virtual[$name]="ALTER table {$updatetable} ADD {$name} {$type} GENERATED ALWAYS AS ({$exp}) STORED";
        	continue;
		}
		elseif(preg_match('/^(.+?)VIRTUAL GENERATED(.*)$/i',$type,$m)){
			$type=preg_replace('/VIRTUAL GENERATED/i','',$type);
			$type=preg_replace('/NULL/i','',$type);
			$type=trim($type);
			$exp=getDBExpression($table,$name);
        	$virtual[$name]="ALTER table {$updatetable} ADD {$name} {$type} GENERATED ALWAYS AS ({$exp}) STORED";
        	continue;
		}
		//virtual from jdoc.Report.value
		if(preg_match('/^(.+?)VIRTUAL FROM(.*)$/i',$type,$m)){
			//GENERATED ALWAYS AS (TRIM(BOTH '"' FROM json_extract(jdoc,'$.shipping.address.city')));
			$type=preg_replace('/NULL/i','',$m[1]);
			$type=trim($type);
			$parts=preg_split('/\./',$m[2]);
			$field=array_shift($parts);
			$exp=implode('.',$parts);
        	$virtual[$name]="ALTER table {$updatetable} ADD {$name} {$type} GENERATED ALWAYS AS (TRIM(BOTH '\"' FROM json_extract({$field},'\$.{$exp}'))) STORED";
        	continue;
		}
		$fields[$name]=$type;
    }
    $rtn=0;
    if(count($fields)){
		//add common fields
		foreach($cfields as $key=>$val){$fields[$key]=$val;}
		//echo $new.printValue($fields);
		if($new==1){
        	$ok = createDBTable($updatetable,$fields);
		}
		else{
        	$ok = alterDBTable($updatetable,$fields);
		}
		$rtn++;
    }
    //echo "virtual".printValue($virtual);
    if(count($virtual)){
    	foreach($virtual as $field=>$sql){
			$ok=executeSQL("ALTER table {$updatetable} DROP {$field}");
        	//echo printValue($ok).printValue($sql);
			$ok=executeSQL($sql);
        	//echo printValue($ok).printValue($sql);
		}
		$rtn++;
	}
    return $rtn;
}

//---------- begin function getDBVersion--------------------
/**
* @describe returns database version
* @return string
* @usage $version=getDBVersion();
*/
function getDBVersion(){
	return databaseVersion();
}
//---------- begin getDBUser
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function getDBUser($params=array()){
	if(!count($params)){return null;}
	$params['-table']="_users";
	return getDBRecord($params);
}
//---------- begin function getDBUserById
/**
* @describe returns user record with recordID specified
* @param id integer - record ID to return
* @param [fields] array - array of fields to return. return values are separated by a space
* @return mixed
* @usage
*	$fullname=getDBUserById(10,array('firstname','lastname')); - returns "jon doe";
*	$user_rec=getDBUserById(10); - returns the entire record array
*/
function getDBUserById($id=0,$fields=array()){
	if($id == 0){return null;}
	$opts=array('-table'=>'_users','_id'=>$id);
	if(is_array($fields) && count($fields)){
		$opts['-fields']=implode(',',$fields);
	}
	$cuser=getDBRecord($opts);
	if(!is_array($cuser)){return null;}
	if(is_array($fields) && count($fields)){
		//only return certain fields
		$vals=array();
		foreach($fields as $fld){array_push($vals,$cuser[$fld]);}
		return implode(' ',$vals);
    	}
	return $cuser;
	}
//---------- begin function listDBRecords
/**
* @describe returns an html table of records
* @param params array - requires either -list or -table
*	[-list] array - getDBRecords array to use
*	[-table] string - table name
*	[-hidesearch] -  hide the search form
*	[-limit] mixed - query record limit
* 	[-header_class] string - class to set header row to
* 	[-rowclass] string - class to set data row to. It can also in php code
* 	[-limit] mixed - query record limit
*	other field/value pairs filter the query results
* @param [customcode] string - html code to append to end - defaults to blank
* @return string - html table to display
* @usage
*	listDBRecords(array('-table'=>'notes'));
*	listDBRecords(array('-list'=>$recs));
*/
function listDBRecords($params=array(),$customcode=''){
	if(isset($params[0])){
		//they are passing in the list without any other params.
		$params=array('-list'=>$params);
	}
	global $PAGE;
	global $USER;
	global $CONFIG;
	$skips=array('align','style','color','class','bgcolor','displayname','nowrap','inputtype',
		'tvals','dvals','relate','ex','checkmark','checkbox','check','image','dateformat','dateage',
		'onmouseover','onmouseout','onhover','href','onclick','eval','title',
		'spellcheck','max','min','pattern','placeholder','readonly','step'
		);
	$idfield=isset($params['-id'])?$params['-id']:'_id';
	$rtn='';
	if(isset($params['-table']) && $params['-table']=='_cron'){$rtn .= '<div id="cronlist">'.PHP_EOL;}
	elseif(isset($params['-ajax']) && (integer)$params['-ajax']==1){
		$params['-ajaxid']='list_'.sha1(json_encode($params,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_UNICODE));
		$rtn .= '<div id="'.$params['-ajaxid'].'">'.PHP_EOL;
	}
	elseif(isset($params['-ajaxid'])){
		$rtn .= '<div id="'.$params['-ajaxid'].'">'.PHP_EOL;
	}
    //determine sort
    $sort='none';
    if(isset($params['-order']) && strlen($params['-order'])){$sort=$params['-order'];}
    elseif(isset($params['-orderby']) && strlen($params['-orderby'])){$sort=$params['-orderby'];}
    elseif(isset($_REQUEST['_sort']) && strlen($_REQUEST['_sort'])){$sort=$_REQUEST['_sort'];}
    if($sort=='none'){
		$sort='';
    	if(isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(isAdmin()){
				if(isset($tinfo['sortfields']) && is_array($tinfo['sortfields'])){$sort=implode(',',$tinfo['sortfields']);}
				}
			else{
				if(isset($tinfo['sortfields_mod']) && is_array($tinfo['sortfields_mod'])){$sort=implode(',',$tinfo['sortfields_mod']);}
            	}
			}
		}
	if(strlen($sort)){$params['-order']=$sort;}
	if(isset($_REQUEST['_sort']) && !strlen(trim($_REQUEST['_sort']))){unset($_REQUEST['_sort']);}
	if(isset($_REQUEST['_sort'])){$params['-order']=$_REQUEST['_sort'];}
	if(isset($params['-list']) && is_array($params['-list'])){$list=$params['-list'];}
	else{
		if(isset($_REQUEST['_search']) && strlen($_REQUEST['_search'])){
			$params['-search']=$_REQUEST['_search'];
        }
        if(isset($_REQUEST['_filters']) && strlen($_REQUEST['_filters'])){
			$params['-filters']=$_REQUEST['_filters'];
        }
        if(isset($_REQUEST['_searchfield']) && strlen($_REQUEST['_searchfield'])){
			$params['-searchfield']=$_REQUEST['_searchfield'];
        	}
        if(isset($_REQUEST['date_range']) && $_REQUEST['date_range']==1){
			$filterfield="_cdate";
			$wheres=array();
			if(isset($_REQUEST['date_from'])){
				$sdate=date2Mysql($_REQUEST['date_from']);
				$wheres[] = "DATE({$filterfield}) >= '{$sdate}'";
				}
			if(isset($_REQUEST['date_to'])){
				$sdate=date2Mysql($_REQUEST['date_to']);
				$wheres[] = "DATE({$filterfield}) <= '{$sdate}'";
				}
			$opts=array('_formname'=>"ticket");
			if(count($wheres)){
				$params['-where']=implode(' and ',$wheres);
				}
        	}

        if(isset($_REQUEST['_filters'])){
        	$params['-filters']=$_REQUEST['_filters'];
		}
		//bulkedit
		if(isset($_REQUEST['_bulkedit']) && $_REQUEST['_bulkedit']==1){
        	$params['-bulkedit']=1;
        	$where=getDBWhere($params);
        	if(strlen($_REQUEST['filter_field'])){
				$val=addslashes($_REQUEST['filter_value']);
				$field=addslashes($_REQUEST['filter_field']);
				if(!strlen($where)){$where='1=1';}
            	$ok=editDBRecord(array(
					'-table'	=> $params['-table'],
					'-where'	=> $where,
					$field		=> $val
				));
			}
		}
		$rec_count=getDBCount($params);
		if(isset($params['-limit']) && isNum($params['-limit']) && $params['-limit'] > 0){
			$paging=getDBPaging($rec_count,$params['-limit']);
        	}
        elseif(isset($USER['paging']) && isNum($USER['paging'])){
			$paging=getDBPaging($rec_count,$USER['paging']);
        	}
        elseif(isset($CONFIG['paging']) && isNum($CONFIG['paging'])){
			$paging=getDBPaging($rec_count,$CONFIG['paging']);
        	}
		else{$paging=getDBPaging($rec_count);}
		//$rtn .= $rec_count . printValue($paging);
		if(isset($paging['-limit'])){
			$params['-limit']=$paging['-limit'];
			}
		if(!isset($params['-hidesearch'])){$paging['-search']=true;}
		//add any filters - excluding keys that end in _{skip}
		foreach($params as $pkey=>$pval){
			$skipkey=0;
			foreach($skips as $skip){
            	if(stringEndsWith($pkey,"_{$skip}")){$skipkey=1;break;}
			}
			if($skipkey==1){continue;}
			if(preg_match('/^\-/',$pkey) && !preg_match('/^\-(ajaxid|search|where|order|pagingformname|formname)/i',$pkey)){continue;}
			if(preg_match('/\_(align|style|color|c)$/',$pkey)){continue;}
			$paging[$pkey]=$pval;
			}
		if(!isset($params['-order']) && isset($_REQUEST['_sort'])){
        	$paging['-order']=$_REQUEST['_sort'];
		}
		if(!isset($params['-search']) && isset($_REQUEST['_search'])){
        	$paging['-search']=$_REQUEST['_search'];
		}
		if(isset($_REQUEST['_filters']) && strlen($_REQUEST['_filters'])){
			$params['-filters']=$_REQUEST['_filters'];
        }
		foreach($_REQUEST as $pkey=>$pval){
        	if(stringBeginsWith($pkey,'_')){$paging[$pkey]=$_REQUEST[$pkey];}
		}
		if(isset($params['-table'])){
        	$paging['-table']=$params['-table'];
		}
		if(isset($params['-action'])){
        	$paging['-action']=$params['-action'];
		}
		if(isset($params['-method'])){
        	$paging['-method']=$params['-method'];
		}
		if(isset($params['-filters'])){
        	$paging['-filters']=$params['-filters'];
		}
		if(isset($params['-bulkedit'])){
        	$paging['-bulkedit']=$params['-bulkedit'];
		}
		foreach($params as $pk=>$pv){
        	if(preg_match('/\_eval$/',$pk)){$paging[$pk]=$pv;}
		}
		//$rtn .= printValue($paging).printValue($params);
		$rtn .= buildDBPaging($paging);
		if(!isset($params['-fields']) && isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			 if(!in_array($idfield,$tinfo['fields']) && in_array('id',$tinfo['fields'])){
				$idfield='id';
			}
			if(is_array($tinfo)){
				$xfields=array();
				if(isset($tinfo['listfields']) && is_array($tinfo['listfields'])){
					$xfields=$tinfo['listfields'];
					}
				elseif(isset($tinfo['default_listfields']) && is_array($tinfo['default_listfields'])){
					$xfields=$tinfo['default_listfields'];
					}
				if(count($xfields)){
					if(!isset($params['-list']) && ($idfield == '_id' || !in_array($idfield,$xfields))){
						array_unshift($xfields,$idfield);
					}
					$params['-fields']=implode(',',$xfields);
				}
	        }
	        if(isset($params['-fields'])){
				$params['-fields']=preg_replace('/\,+$/','',$params['-fields']);
				$params['-fields']=preg_replace('/^\,+/','',$params['-fields']);
	        	$params['-fields']=preg_replace('/\,+/',',',$params['-fields']);
				}
	        }
	    if(!isset($_REQUEST['_sort']) && !isset($_REQUEST['-order']) && !isset($params['-order'])){
			$params['-order']="{$idfield} desc";
        }
		//secondary sort
		if(isset($params['-order']) && isset($params['-order2'])){
			$params['-orderX']=$params['-order'];
	    	$params['-order'] .= ", {$params['-order2']}";
		}
		$list=getDBRecords($params);
		if(isset($params['-orderX'])){
			$params['-order']=$params['-orderX'];
		}
		//echo printValue($list) . printValue($params);
		}
	if(isset($list['error'])){return $list['error'];}
	if(!is_array($list)){
		$no_records=isset($params['-norecords'])?$params['-norecords']:'No records found';
		$rtn .= "<div>{$no_records}</div>";
		if(strlen($list)){
			$rtn .= $list;
			//$rtn .= printValue($params);
			}
		if(isset($params['-table']) && $params['-table']=='_cron'){$rtn .= '</div>'.PHP_EOL;}
		return $rtn;
		}
	$list_cnt=count($list);
	$listform=0;
	if(!isset($params['-list'])){
		$fields=array($idfield);
	}
	else{
		$fields=array();
	}
	if(isset($params['-fields'])){
		if(is_array($params['-fields'])){$fields=$params['-fields'];}
		else{$fields=explode(',',$params['-fields']);}
    	}
    elseif(isset($params['-query'])){
		$fields=array();
		foreach($list as $rec){
			foreach($rec as $key=>$val){
				$fields[]=$key;
				}
			break;
        	}
        //echo $params['-query'] . printValue($fields);
    	}
    else{
		//get fields in the _tabledata
		$tdata=0;
		if(isset($params['-table'])){
			$tinfo=getDBTableInfo(array('-table'=>$params['-table']));
			if(is_array($tinfo)){
				//echo printValue($tinfo);
				if(is_array($tinfo['listfields'])){
					$fields=$tinfo['listfields'];
					//echo "listfields";
					}
				elseif(is_array($tinfo['default_listfields'])){
					//echo "default";
					$fields=$tinfo['default_listfields'];
					}
				if(!isset($params['-list'])){
					array_unshift($fields,$idfield);
				}
				$tdata=1;
            	}
            }
        if($tdata==0){
			//no fields defined so get all user defined fields, except for blob data
			//echo "table:{$table}" . printValue($list);
			foreach ($list[0] as $field=>$val){
				if(preg_match('/^\_/',$field)){continue;}
					$fields[]=$field;
		    	}
		    	if(isset($list[0]['_id'])){
					array_unshift($fields,'_id');
				}
			}
    	}
    if(!isset($params['-action'])){
		$params['-action']='';
	}
    //remove fields that are not valid
	if(isset($params['-table']) && !isset($params['-formname'])){
		$info=getDBFieldMeta($params['-table'],"displayname,editlist");
		$parts=array();
	    foreach($_REQUEST as $key=>$val){
			if(preg_match('/^(edit|add)\_(result|id|table)$/i',$key)){continue;}
			if($key=='_action' && $val=='multi_update'){continue;}
			if(preg_match('/^(GUID|PHPSESSID)$/i',$key)){continue;}
			if(preg_match('/\_[0-9]+$/i',$key)){continue;}
			if(preg_match('/\_([0-9]+?)\_prev$/i',$key)){continue;}
			if(preg_match('/^(x|y)$/i',$key)){continue;}
			if(preg_match('/^\_(start|id\_href|search|bulkedit|export|viewfield)$/i',$key)){continue;}
			if(preg_match('/\_(onclick|href|eval|editlist)$/i',$key)){continue;}
			if(is_array($val) || strlen($val) > 255){continue;}
			$parts[$key]=$val;
	    }
	    $parts['_action']="multi_update";
	    $parts['_table']=$params['-table'];
	    $parts['_fields']=implode(':',$fields);
	    if(isset($params['-onsubmit'])){$parts['-onsubmit']=$params['-onsubmit'];}
	    if(strlen($params['-action'])){
			if(isset($parts['_template'])){unset($parts['_template']);}
			if(isset($parts['_view'])){unset($parts['_view']);}
	    	if(isset($parts['undefined'])){unset($parts['undefined']);}
			if(isset($parts['AjaxRequestUniqueId'])){unset($parts['AjaxRequestUniqueId']);}
		}

		$rtn .= buildFormBegin($params['-action'],$parts);
		$listform=1;
    	}
    elseif(isset($params['-form']) && is_array($params['-form'])){
		if(isset($params['-onsubmit'])){$parts['-onsubmit']=$params['-onsubmit'];}
		if(strlen($params['-action'])){
			if(isset($parts['_template'])){unset($parts['_template']);}
			if(isset($parts['_view'])){unset($parts['_view']);}
	    	if(isset($parts['undefined'])){unset($parts['undefined']);}
			if(isset($parts['AjaxRequestUniqueId'])){unset($parts['AjaxRequestUniqueId']);}
		}
		$rtn .= buildFormBegin($params['-action'],$params['-form']);
    	}
    //set table class
	$tableclass='table table-bordered table-striped table-responsive';
	//add the sortable class if there is only one page of records or is sorting is turned off
	if(!isset($paging['-next']) || isset($params['-nosort'])){
		$tableclass .= ' sortable';
		$params['-nosort']=1;
		}
	//check for tableclass override
	if(isset($params['-tableclass'])){
		$tableclass=$params['-tableclass'];
	}
	//table
	$rtn .= '<table class="'.$tableclass.'"';
	//id
	if(isset($params['-tableid'])){
		$rtn .=' id="'.$params['-tableid'].'"';
	}
	//style
	if(isset($params['-tablestyle'])){
		$rtn.=' style="'.$params['-tablestyle'].'"';
	}
	//check for -table_data-
	foreach($params as $k=>$v){
		if(preg_match('/^\-table\_data\-(.+)$/i',$k,$m)){
			$datakey=$m[1];
			$rtn.=' data-'.$datakey.'="'.$v.'"';
		}
		elseif(preg_match('/^\-tabledata\-(.+)$/i',$k,$m)){
			$datakey=$m[1];
			$rtn.=' data-'.$datakey.'="'.$v.'"';
		}
	}
	$rtn .='>'.PHP_EOL;

    //build header row
    $rtn .= "	<thead><tr>".PHP_EOL;
    if(isset($params['-table']) && $params['-table']=='_users' && $params['-icons']){
		$rtn .= '		<td><span class="icon-user w_grey w_big"></span></td>'.PHP_EOL;
    	}
    //allow user to pass in what fields to display as -listfields
    if(isset($params['-listfields'])){
    	if(is_array($params['-listfields'])){$listfields=$params['-listfields'];}
    	else{$listfields=preg_split('/\,/',$params['-listfields']);}
	}
	else{$listfields=$fields;}
	foreach($listfields as $fld){
		if(isset($info[$fld]['displayname']) && strlen($info[$fld]['displayname'])){$col=$info[$fld]['displayname'];}
		elseif(isset($params[$fld."_displayname"])){$col=$params[$fld."_displayname"];}
		elseif(isset($params['-header_eval'])){
			$evalstr=$params['-header_eval'];
			$replace='%field%';
			$evalstr=str_replace($replace,$fld,$evalstr);
			$col=evalPHP('<?' . $evalstr .'?>');
			}
		else{
			$col=preg_replace('/\_+/',' ',$fld);
			$col=ucwords($col);
			}
		$arr=array();
		foreach($_REQUEST as $key=>$val){
        	if(preg_match('/^(GUID|PHPSESSID)$/i',$key)){continue;}
			if(preg_match('/^(x|y)$/i',$key)){continue;}
			if(preg_match('/^\_(filters|bulkedit|export|viewfield)$/i',$key)){continue;}
			if(preg_match('/\_(onclick|href|eval|editlist)$/i',$key)){continue;}
        	$arr[$key]=$val;
		}
		if(isset($_REQUEST['add_result']) || isset($_REQUEST['edit_result'])){$arr=array();}
		foreach($arr as $key=>$val){
			if(is_array($val) || strlen($val)>255 || isXML($val)){unset($arr[$key]);}
			}
		foreach($params as $key=>$val){
        	if(preg_match('/^\-/',$key)){continue;}
        	if(preg_match('/^(GUID|PHPSESSID)$/i',$key)){continue;}
			if(preg_match('/^(x|y)$/i',$key)){continue;}
			if(preg_match('/^\_(filters|bulkedit|export|viewfield)$/i',$key)){continue;}
			if(preg_match('/\_(onclick|href|eval|editlist)$/i',$key)){continue;}
        	$arr[$key]=$val;
		}
		$arr['_sort']=$fld;
		foreach($fields as $ufld){
			unset($arr[$ufld]);
			foreach($skips as $skip){
            	$sfield=$ufld.'_'.$skip;
            	unset($arr[$sfield]);
			}
		}
		unset($arr['x']);
		unset($arr['y']);
		$arrow='';
		if(isset($_REQUEST['_sort'])){
			if($_REQUEST['_sort']==$fld){
				$arr['_sort'] .= ' desc';
				$arrow=' <span class="icon-sort-up"></span>';
				}
			elseif($_REQUEST['_sort']== "{$fld} desc"){
				$arrow=' <span class="icon-sort-down"></span>';
				}
			}
		elseif(isset($params['-order'])){
            if($params['-order']==$fld){
				$arr['order'] .= ' desc';
				$arrow=' <span class="icon-sort-up"></span>';
				}
			elseif($params['-order']== "{$fld} desc"){
				$arrow=' <span class="icon-sort-down"></span>';
				}
        	}
        $title=isset($params[$fld."_title"])?' title="'.$params[$fld."_title"].'"':'';
        $class='w_nowrap';
        if(isset($params[$fld."_header_class"])){$class=$params[$fld."_header_class"];}
        elseif(isset($params["-header_class"])){$class=$params["-header_class"];}
        if(isset($params[$fld."_checkbox"]) && $params[$fld."_checkbox"]==1){
        	$rtn .= '		<th'.$title.' class="'.$class.'">'.PHP_EOL;
			$rtn .= '			<input type="checkbox" id="'.$fld.'_checkbox_all" data-type="checkbox" style="display:none" onclick="checkAllElements(\'data-group\',\''.$fld.'_checkbox\', this.checked);">'.PHP_EOL;
			$rtn .= '			<label for="'.$fld.'_checkbox_all" class="icon-mark"></label>'.PHP_EOL;
			$rtn .= '			<label for="'.$fld.'_checkbox_all"> '.$col.'</label>'.PHP_EOL;
			$rtn .= '		</th>'.PHP_EOL;
		}
        elseif(isset($params['-nosort']) || isset($params[$fld."_nolink"])){
			$rtn .= '		<th'.$title.' class="'.$class.'">' . "{$col}</th>".PHP_EOL;
        	}
        elseif(isset($params['-sortlink'])){
			$href=$params['-sortlink'];
			$replace='%col%';
            $href=str_replace($replace,$col,$href);
			$rtn .= '		<th'.$title.' class="'.$class.'"><a class="w_link " href="/'.$href.'">' . $col. "</a></th>".PHP_EOL;
        	}
        elseif(isset($params['-sortclick'])){
			$onclick=$params['-sortclick'];
			$replace='%col%';
            $onclick=str_replace($replace,$col,$onclick);
			$rtn .= '		<th'.$title.' class="'.$class.'"><a class="w_link " href="#'.$col.'" onclick="/'.$onclick.'">' . $col. "</a></th>".PHP_EOL;
        	}
        else{
	        if(preg_match('/\.(php|htm|phtm)$/i',$PAGE['name'])){$href=$PAGE['name'].'?'.buildURL($arr);}
	        else{$href=$PAGE['name'].'/?'.buildURL($arr);}
			$rtn .= '		<th'.$title.' class="'.$class.'"><a class="w_link " href="/'.$href.'">' . $col. "{$arrow}</a></th>".PHP_EOL;
			}
		}
	if(isset($params['-row_actions'])){
    	$rtn .= '		<th class="w_nowrap">Actions</th>'.PHP_EOL;
	}
	$rtn .= "\t</tr></thead><tbody>".PHP_EOL;
	$row=0;
	$editlist=0;
	if(isset($params['-sumfields']) && !is_array($params['-sumfields'])){
		$params['-sumfields']=preg_split('/[\,\;]+/',$params['-sumfields']);
	}
	if(isset($params['-sumfields']) && is_array($params['-sumfields'])){
		$sums=array();
		foreach($params['-sumfields'] as $sumfield){$sums[$sumfield]=0;}
	}
	if(isset($params['-avgfields']) && !is_array($params['-avgfields'])){
		$params['-avgfields']=preg_split('/[\,\;]+/',$params['-avgfields']);
	}
	if(isset($params['-avgfields']) && is_array($params['-avgfields'])){
		$avgs=array();
		foreach($params['-avgfields'] as $avgfield){$avgs[$avgfield]=array();}
	}
	foreach($list as $rec){
		$row++;
		$cronalert=0;
		if(isset($params['-table']) && $params['-table']=='_cron'){
			if($rec['active']==1 && $rec['running']==0 && isNum($rec['frequency'])){
				$frequency=$rec['frequency']*60;
				$age=time()-strtotime($rec['run_date']);
				if($age > $frequency){
					$cronalert=1;
            	}
			}
			elseif($rec['active']==0){
            	$cronalert=2;
			}
			elseif($rec['running']==1){
            	$cronalert=3;
			}
        }
        if($cronalert==1){
			//overdue to run
			$bgcolor=isFactor($row,2)?'#ffd2d2':'#ffa6a6';
			$rtn .= '	<tr class="w_top" bgcolor="'.$bgcolor.'">'.PHP_EOL;
        }
        elseif($cronalert==2){
			//Not Active - grey out
			$bgcolor=isFactor($row,2)?'#d2d2d2':'#e9e9e9';
			$rtn .= '	<tr class="w_top" bgcolor="'.$bgcolor.'">'.PHP_EOL;
        }
        elseif($cronalert==3){
			//Currently running
			$bgcolor=isFactor($row,2)?'#ffffc1':'#ffff9f';
			$rtn .= '	<tr class="w_top" bgcolor="'.$bgcolor.'">'.PHP_EOL;
        }
		elseif(isset($params['-altcolor']) && isFactor($row,2)){
			$rtn .= '	<tr class="w_top" bgcolor="'.$params['-altcolor'].'">'.PHP_EOL;
		}
		else{
			//check for row params
			$rowid='';
			if(isset($params['-rowid'])){
				$rowid=$params['-rowid'];
            	foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $rowid=str_replace($replace,$rec[$xfld],$rowid);
                }
                $rowid=evalPHP($rowid);
                $rowid=' id="'.$rowid.'"';
			}
			$rowclass=' class="w_top"';
			if(isset($params['-rowclass'])){
				$rowclass=$params['-rowclass'];
            	foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $rowclass=str_replace($replace,$rec[$xfld],$rowclass);
                }
                $rowclass=evalPHP($rowclass);
                $rowclass=' class="'.$rowclass.'"';
			}
			if(isset($params['-sync']) && $params['-sync']==1 && $rec['user_stage']==$USER['username']){
				$bgcolor=isFactor($row,2)?'#fefdc5':'#fefc9c';
        		$params['-rowstyle']="background-color:{$bgcolor};";
			}
			$rowstyle='';
			if(isset($params['-rowstyle'])){
				$rowstyle=$params['-rowstyle'];
            	foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $rowstyle=str_replace($replace,$rec[$xfld],$rowstyle);
                }
                $rowstyle=evalPHP($rowstyle);
            	$rowstyle=' style="'.$rowstyle.'"';
			}
			$onclick='';
			if(isset($params['-onclick'])){
				$onclick=$params['-onclick'];
            	foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $onclick=str_replace($replace,$rec[$xfld],$onclick);
                }
                $onclick=evalPHP($onclick);
            	$onclick=' onclick="'.$onclick.'"';
			}
			$rtn .= '	<tr '.$rowid.$rowclass.$rowstyle.$onclick.'>'.PHP_EOL;
		}
		if(isset($params['-table']) && $params['-table']=='_users' && $params['-icons']){
			//echo "rec:".printValue($rec);
			$uinfo=getUserInfo($rec);
			$rtn .= '		<td><span class="'.$uinfo['class'].'"></span></td>'.PHP_EOL;
    		}
    	if(isset($params['-sumfields']) && is_array($params['-sumfields'])){
			foreach($params['-sumfields'] as $sumfield){
				$amt=trim(removeHtml($rec[$sumfield]));
				$amt=(float)str_replace(',','',$amt);
				$sums[$sumfield]+=$amt;
			}
		}
		if(isset($params['-avgfields']) && is_array($params['-avgfields'])){
			foreach($params['-avgfields'] as $avgfield){
				$amt=trim(removeHtml($rec[$avgfield]));
				$amt=(float)str_replace(',','',$amt);
				$avgs[$avgfield][]=$amt;
			}
		}
    	$tabindex=0;
		foreach($listfields as $fld){
			//Show editlist?
			$editlist_field=0;
			if(isset($info[$fld]['editlist']) && $info[$fld]['editlist']==1){$editlist_field=1;}
			if(isset($params["{$fld}_editlist"])){$editlist_field=$params["{$fld}_editlist"];}
			if($listform==1 && $editlist_field==1 ){
				$rtn .= '<td>'.PHP_EOL;
				$fldopts=array('-table'=>$params['-table'],'-field'=>$fld,'name'=>"{$fld}_{$rec[$idfield]}",'value'=>$rec[$fld]);
				foreach($params as $pkey=>$pval){
					if(preg_match('/^'.$fld.'_(.+)$/',$pkey,$m)){
						if($m[1]=='tabindex'){$pval += $tabindex;$tabindex++;}
						$fldopts[$m[1]]=$pval;
					}
                }
                if(!isset($fldopts['class'])){$fldopts['class']='form-control';}
                if(isExtraCss('bootstrap') && !stringContains($fldopts['class'],'form-control')){$fldopts['class'] .= ' form-control';}
				$rtn .= '<div style="display:none"><textarea name="'."{$fld}_{$rec[$idfield]}_prev".'">'.$rec[$fld].'</textarea></div>'.PHP_EOL;
				$rtn .= getDBFieldTag($fldopts);
				$rtn .= '</td>'.PHP_EOL;
				$editlist++;
				continue;
            }
            $val=isset($rec[$fld])?$rec[$fld]:null;
			if($fld=='password'){
				$val=preg_replace('/./','*',$val);
            }
            //relate?
			if(isset($params[$fld."_relate"])){
				$relflds=preg_split('/[\,\ \;\:]+/',$params[$fld."_relate"]);
				$rvals=array();
				foreach($relflds as $relfld){
					if(isset($rec["{$fld}_ex"][$relfld])){$rvals[]=$rec["{$fld}_ex"][$relfld];}
					}
                $val=count($rvals)?implode(' ',$rvals):$val;
				}
			//eval?
			if(isset($params[$fld."_eval"])){
				$evalstr=$params[$fld."_eval"];
				foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $evalstr=str_replace($replace,$rec[$xfld],$evalstr);
                	}
                $val=evalPHP('<?' . $evalstr .'?>');
				}
			//link, check, or image?
			if(isset($params[$fld."_target"])){
                $target=$params[$fld."_target"];
                foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $target=str_replace($replace,$rec[$xfld],$target);
                	}
				$target=" target=\"{$target}\"";
			}
			elseif(isset($params['-target'])){
                $target=$params['-target'];
                foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $target=str_replace($replace,$rec[$xfld],$target);
                	}
				$target=" target=\"{$target}\"";
			}
			else{$target='';}
			//link
			if(isset($params[$fld."_link"]) && $params[$fld."_link"]==1){
				$val='<a class="w_link" href="'.$val.'"'.$target.'>'.$val.'</a>';
				}
			elseif(isset($params[$fld."_href"])){
                $href=$params[$fld."_href"];
                foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $href=str_replace($replace,$rec[$xfld],$href);
                	}
				$val='<a class="w_link" href="'.$href.'"'.$target.'>'.$val.'</a>';
				}
			elseif(isset($params['-href'])){
                $href=$params['-href'];
                foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
                    $href=str_replace($replace,$rec[$xfld],$href);
                	}
				$val='<a class="w_link" href="'.$href.'"'.$target.'>'.$val.'</a>';
				}
			elseif(isset($params[$fld."_onclick"])){
                $href=$params[$fld."_onclick"];
                foreach($list[0] as $xfld=>$xval){
					if(is_array($xfld) || is_array($xval)){continue;}
					$replace='%'.$xfld.'%';
					//echo "replacing {$replace} with {$rec[$xfld]}<br>".PHP_EOL;
                    $href=str_replace($replace,$rec[$xfld],$href);
                	}
				$val='<a class="w_link" href="#" onClick="'.$href.'">'.$val.'</a>';
				}
			elseif(isset($params[$fld."_checkbox"]) && $params[$fld."_checkbox"]==1){
				//if($val==0){$val='<div class="text-center"><span class="icon-checkbox-empty"></span></div>';}
				//else{$val='<div class="text-center"><span class="icon-checkbox"></span></div>';}
				$cval=$val;
				$val='<input type="checkbox" data-type="checkbox" style="display:none" data-group="'.$fld.'_checkbox" id="'.$fld.'_checkbox_'.$row.'" name="'.$fld.'[]" value="'.$val.'"> ';
				$val .= '<label for="'.$fld.'_checkbox_'.$row.'" class="icon-mark"></label>'.PHP_EOL;
				if(!isNum($cval)){$val .= '<label for="'.$fld.'_checkbox_'.$row.'">'.$cval.'</label>';}
            }
			elseif(isset($params[$fld."_check"]) && $params[$fld."_check"]==1){
				if($val==0){$val='';}
				elseif($val==1){$val='<div class="text-center"><span class="icon-check"></span></div>';}
            }
            elseif(isset($params[$fld."_checkmark"]) && $params[$fld."_checkmark"]==1){
				if($val==0){$val='';}
				elseif($val==1){
					$mark='icon-mark';
					if(isset($params[$fld."_checkmark_icon"])){
						$mark=$params[$fld."_checkmark_icon"];
					}
					$val='<div class="text-center"><span class="'.$mark.'"></span></div>';
				}
            }
            elseif(isset($params[$fld."_image"]) && $params[$fld."_image"]==1){
				$val='<center><img src="'.$val.'" alt="" /></center>';
            	}
            elseif(isset($params[$fld."_email"]) && $params[$fld."_email"]==1){
				$val='<a class="w_link" href="mailto:'.$val.'">'.$val.'</a>';
            	}
            elseif(isset($params[$fld."_dateFormat"]) && strlen(trim($val)) && strlen($params[$fld."_dateFormat"])){
				$val=date($params[$fld."_dateFormat"],strtotime($val));
            	}
            elseif(isset($params[$fld."_dateage"]) && $params[$fld."_dateage"]==1){
				if(isNum($rec["{$fld}_utime"])){
					$age=time()-strtotime($rec[$fld]);
					$val=verboseTime($age);
					}
				else{$val='';}
            	}
            if(isNum($val)){$params[$fld."_align"]="right";}
			//write the cell with any custom attributes
			$rtn .= "\t\t<td";

			if(!isset($params[$fld."_align"])){
				if(preg_match('/^('.$idfield.'|recid)$/i',$fld)){$params[$fld."_align"]="right";}
				elseif(preg_match('/^(tablename|code|fieldname)$/i',$fld)){$params[$fld."_align"]="center";}
				}
			if(isset($params[$fld."_align"])){$rtn .= ' align="'.$params[$fld."_align"].'"';}
			if(isset($params[$fld."_valign"])){$rtn .= ' valign="'.$params[$fld."_valign"].'"';}
			if(isset($params[$fld."_bgcolor"])){$rtn .= ' bgcolor="'.$params[$fld."_bgcolor"].'"';}
			if(isset($params[$fld."_class"])){
				$class=$params[$fld."_class"];
				//check to see if the class value matches a field
				if(isset($rec[$class])){$class=$rec[$class];}
				$rtn .= ' class="'.$class.'"';
			}
			elseif(isset($params[$fld."_style"])){$rtn .= ' style="'.$params[$fld."_style"].'"';}
			if(isset($params[$fld."_nowrap"]) && $params[$fld."_nowrap"]==1){$rtn .= ' nowrap';}
			$rtn .= ">" . $val . "</td>".PHP_EOL;
			}
		if(isset($params['-row_actions']) && is_array($params['-row_actions']) && count($params['-row_actions'])){
			$rtn .= '	<td align="right">'.PHP_EOL;
			foreach($params['-row_actions'] as $action){
            	if(!is_array($action)){$action=array($action);}
            	$action_value=array_shift($action);
            	$show=1;
            	//criteria?
                while(count($action)){
					$key=array_shift($action);
					$val=array_shift($action);
					if(isset($rec[$key])){
                    	if($rec[$key]!=$val){$show=0;}
					}
				}
				if($show==1){
					foreach($list[0] as $xfld=>$xval){
						if(is_array($xfld) || is_array($xval)){continue;}
						$replace='%'.$xfld.'%';
                    	$action_value=str_replace($replace,$rec[$xfld],$action_value);
                	}
                	$rtn .='		'.$action_value." ".PHP_EOL;
				}
			}
			$rtn .= '	</td>'.PHP_EOL;
		}
		$rtn .= "\t</tr>".PHP_EOL;
    	}
    $rtn .= '</tbody>'.PHP_EOL;
    if(
    	(isset($params['-sumfields']) && is_array($params['-sumfields']))
    	|| (isset($params['-avgfields']) && is_array($params['-avgfields']))
    ){
		$rtn .= '	<tfoot><tr>'.PHP_EOL;
		foreach($fields as $fld){
        	if(isset($sums[$fld])){$val=$sums[$fld];}
        	elseif(isset($avgs[$fld])){
        		$a = array_filter($avgs[$fld]);
        		if(count($a)){
					$val = array_sum($a)/count($a);
					$average = number_format($average,2);
				}
				else{$val='';}
        	}
        	else{$val='';}
        	$rtn .= '		<th align="right">'.$val.'</th>'.PHP_EOL;
		}
		$rtn .= '	</tr></tfoot>'.PHP_EOL;
	}
    $rtn .= "</table>".PHP_EOL;
    if($listform==1){
		if($editlist > 0){$rtn .= buildFormSubmit("Update");}
		$rtn .= $customcode;
    	$rtn .= buildFormEnd();
		}
	elseif(isset($params['-form']) && is_array($params['-form'])){
    	$rtn .= $customcode;
    	$rtn .= buildFormEnd();
	}
	else{$rtn .= $customcode;}
	if(isset($params['-table']) && $params['-table']=='_cron'){$rtn .= '</div>'.PHP_EOL;}
	elseif(isset($params['-ajaxid'])){
		$rtn .= '</div>'.PHP_EOL;
	}
	return $rtn;
	}

//---------- begin function listDBResults
/**
* @describe wrapper function for listDBRecords. the results of listDBRecords based on query passed in
* @param query string - SQL query
* @param params array - requires either -list or -table or -query
*	[-list] array - getDBRecords array to use
*	[-table] string - table name
*	[-query] string- SQL query string
*	[-hidesearch] -  hide the search form
*	[-limit] mixed - query record limit
*	other field/value pairs filter the query results
* @param [customcode] string - html code to append to end - defaults to blank
* @return string - html table to display
* @usage  listDBResults('select title,note_date from notes');
*/
function listDBResults($query='',$params=array()){
	$params['-query']=$query;
	return listDBRecords($params);
	}
//---------- begin function getDBTables --------------------
/**
* @describe returns table names.
* @param [dbname] string - database name - defaults to current database
* @param [force] boolean - if true forces the cache to be cleared - defaults to false
* @return array
* @usage $tables=getDBTables();
*/
function getDBTables($dbname='',$force=0){
	return databaseTables($dbname,$force);
	}
//---------- begin function isDBReservedWord ----------
/**
* @describe returns true if word is a reserved database word.. ie - dont use it
* @param word string
* @return boolean
*	returns true if word is a reserved database word.. ie - dont use it
* @usage if(isDBReservedWord('select')){...}
*/
function isDBReservedWord($word=''){
	$word=trim($word);
	//return 1 if word starts with a number since those fields are not allowed in xml
	if(preg_match('/^[0-9]/',$word)){return true;}
	$reserved=array(
		'action','add','all','allfields','alter','and','as','asc','auto_increment','between','bigint','bit','binary','blob','both','by',
		'cascade','char','character','change','check','column','columns','create',
		'data','database','databases','date','datetime','day','day_hour','day_minute','day_second','dayofweek','dec','decimal','default','delete','desc','describe','distinct','double','drop','escaped','enclosed',
		'enum','explain','fields','float','float4','float8','foreign','from','for','full',
		'grant','group','having','hour','hour_minute','hour_second',
		'ignore','in','index','infile','insert','int','integer','interval','int1','int2','int3','int4','int8','into','is','inshift','in1',
		'join','key','keys','leading','left','like','lines','limit','lock','load','long','longblob','longtext',
		'match','mediumblob','mediumtext','mediumint','middleint','minute','minute_second','mod','month',
		'natural','numeric','no','not','null','on','option','optionally','or','order','outer','outfile',
		'partial','precision','primary','procedure','privileges',
		'read','real','references','rename','regexp','repeat','replace','restrict','rlike',
		'select','set','show','smallint','sql_big_tables','sql_big_selects','sql_select_limit','sql_log_off','straight_join','starting',
		'table','tables','terminated','text','time','timestamp','tinyblob','tinytext','tinyint','trailing','to',
		'use','using','unique','unlock','unsigned','update','usage',
		'values','varchar','varying','varbinary','with','write','where',
		'year','year_month','zerofill'
		);
	if(in_array($word,$reserved)){return true;}
	return false;
	}
//---------- begin function isDBStage ----------
/**
* @describe returns true if you are in the staging database
* @return boolean
*	returns true if you are in the staging database
* @usage if(isDBStage()){...}
*/
function isDBStage(){
	global $databaseCache;
	if(isset($databaseCache['isDBStage'])){return $databaseCache['isDBStage'];}
	global $CONFIG;
	if(isset($CONFIG['stage'])){
    	$rtn=$CONFIG['stage'];
	}
	else{
		$xset=settingsValues(0);
		if(isset($xset['wasql_synchronize']) && $xset['wasql_synchronize']==0){
	    	$rtn=0;
		}
		elseif(!isset($xset['wasql_synchronize_master']) || !strlen($xset['wasql_synchronize_master'])){
	    	$rtn=0;
		}
		elseif(!isset($xset['wasql_synchronize_slave']) || !strlen($xset['wasql_synchronize_slave'])){
	    	$rtn=0;
		}
		else{
			$rtn=0;
			if(isset($xset['wasql_synchronize_master']) && $xset['wasql_synchronize_master']==$CONFIG['dbname']){$rtn = 1;}
		}
	}
	$databaseCache['isDBStage']=$rtn;
	return $rtn;
}
//---------- begin function isDBPage ----------
/**
* @describe returns true if said page already exists already exists in the _pages table
* @param mixed - page name, permalink or id
* @return boolean
*	returns true if said page already exists already exists in the _pages table
* @usage if(isDBPage('index')){...}
*/
function isDBPage($str){
	if(!strlen($str)){
		debugValue('isDBPage Error: nothing passed in');
		return false;
	}
	if(is_array($str)){
    	debugValue('isDBPage Error: does not support arrays');
    	return false;
	}
	$rec=getDBRecord(array(
		'-table'	=> '_pages',
		'-where'	=> "_id='{$str}' or name='{$str}' or permalink='{$str}'",
		'-fields'	=> '_id'
	));
	if(isset($rec['_id'])){return true;}
	return false;
}
//---------- begin function isDBTable ----------
/**
* @describe returns true if table already exists
* @param table string
* @return boolean
*	returns true if table already exists
* @usage if(isDBTable('_users')){...}
*/
function isDBTable($table='',$force=0){
	if(isPostgreSQL()){return postgresqlIsDBTable($table,$force);}
	elseif(isSqlite()){return sqliteIsDBTable($table,$force);}
	elseif(isOracle()){return oracleIsDBTable($table,$force);}
	elseif(isMssql()){return mssqlIsDBTable($table,$force);}
	global $databaseCache;
	$table=strtolower($table);
	if(isset($databaseCache['isDBTable'][$table])){return $databaseCache['isDBTable'][$table];}
	$databaseCache['isDBTable']=array();
	$table_parts=preg_split('/\./', $table);
	$dbname='';
	if(count($table_parts) > 1){
		$dbname=array_shift($table_parts);
		//$table=implode('.',$table_parts);
		}
	$tables=getDBTables($dbname,$force);
	$databaseCache['isDBTable'][$table]=false;
	if(!is_array($tables)){return false;}
	foreach($tables as $ctable){
		$ctable=strtolower($ctable);
		if(strlen($dbname)){$ctable="{$dbname}.{$ctable}";}
		$databaseCache['isDBTable'][$ctable]=true;
		}
	return $databaseCache['isDBTable'][$table];
	}
//---------- begin function truncateDBTable ----------
/**
* @describe removes all records in specified table and resets the auto-incriment field to zero
* @param table string
* @return mixed - return true on success or errmsg on failure
* @usage $ok=truncateDBTable('comments');
*/
function truncateDBTable($table){
	if(isPostgreSQL()){return postgresqlTruncateDBTable($table);}
	elseif(isSqlite()){return sqliteTruncateDBTable($table);}
	elseif(isOracle()){return oracleTruncateDBTable($table);}
	elseif(isMssql()){return mssqlTruncateDBTable($table);}
	if(is_array($table)){$tables=$table;}
	else{$tables=array($table);}
	foreach($tables as $table){
		if(!isDBTable($table)){return "No such table: {$table}.";}
		$result=executeSQL("truncate {$table}");
		if(isset($result['error'])){
			return $result['error'];
	        }
	    }
    return 1;
	}

/*  ############################################################################
	Database Independant function calls
	- currently supports MySQL, PostgreSQL, and MS SQL
	############################################################################
*/
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseAffectedRows($resource=''){
	global $dbh;
	//Open a connection to a dabase Server - supports multiple database types
	if(isMysqli()){return mysqli_affected_rows($dbh);}
	elseif(isPostgreSQL()){return pg_affected_rows($resource);}
	elseif(isMysql()){return mysql_affected_rows();}
	elseif(isPostgreSQL()){return mysql_affected_rows($resource);}
	elseif(isMssql()){
		$val = null;
		$res = databaseQuery('SELECT @@rowcount as rows');
		if ($row = mssql_fetch_row($res)) {
			$val = trim($row[0]);
			}
		mssql_free_result($res);
		return $val;
		}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseConnect($host,$user,$pass,$dbname=''){

	//clear the cache so we are not getting false cached data from another database
	global $databaseCache;
	$databaseCache=array();
	//Open a connection to a dabase Server - supports multiple database types
	if(isMysqli()){
		try{
			//$host=gethostbyname($host);
			$dbh=@mysqli_connect($host, $user, $pass, $dbname);
		}
		catch(Exception $e){
			$dbh=false;
		}
		if(!$dbh && strlen($dbname)){
			//try connecting without specifiying dbname
			try{
				$dbh=mysqli_connect($host, $user, $pass);
			}
			catch(Exception $e){
				$dbh=false;
			}
			if($dbh){
				//try creating the database
				$sql = "CREATE DATABASE {$dbname}";
				if(mysqli_query($dbh, $sql)){
					mysqli_select_db($dbh,$dbname);
					return $dbh;
				}
			}
		}
		return $dbh;
	}
	elseif(isMysql()){return mysql_connect($host, $user, $pass);}
	elseif(isMssql()){return mssql_connect($host, $user, $pass);}
	elseif(isOracle()){
          $db = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT=1521))(CONNECT_DATA=(SERVER=DEDICATED)(SERVICE_NAME={$dbname})))";
                return oci_connect($user, $pass, $db);
                }
	elseif(isPostgreSQL()){
		//Open a persistent PostgreSQL connection
		loadExtras('postgresql');
		return postgresqlDBConnect();
		}
	elseif(isSqlite()){
		global $dbh;
		$dbh=sqliteDBConnect();
		return $dbh;
	}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseDataType($str){
	if(isPostgreSQL()){return postgresqlTranslateDataType($str);}
	elseif(isSqlite()){return sqliteTranslateDataType($str);}
	elseif(isOracle()){return oracleTranslateDataType($str);}
	elseif(isMssql()){return mssqlTranslateDataType($str);}
	//integer, real(8,2), int(8)
	//PostgreSQL does not have a the same data types as mysql
	//http://en.wikibooks.org/wiki/Converting_MySQL_to_PostgreSQL
	$parts=preg_split('/[,()]/',$str);
	$name=strtolower($parts[0]);
	if(isSqlite()){
		switch(strtolower($name)){
			case 'int':
			case 'integer':
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
	        case 'bigint':
				return 'integer';
			break;
	        case 'char':
			case 'nchar':
			case 'varchar':
			case 'nvarchar':
			case 'text':
			case 'smalltext':
	        case 'mediumtext':
			case 'clob':
				return 'text';
			break;
			case 'real':
			case 'double':
			case 'float':
				return 'real';
			break;
			case 'numeric':
			case 'decimal':
			case 'boolean':
			case 'date':
	        case 'datetime':
				return 'numeric';
			break;
			default:
				return 'blob';
			break;
		}
	}
	return $str;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseDateTimeNow(){
	$version=databaseVersion();
	if(isMysqli() || isMysql()){
		if(stringBeginsWith($version,'5.6')){
			return " NOT NULL Default NOW()";
		}
		return " NOT NULL Default CURRENT_TIMESTAMP";
	}
	elseif(isPostgreSQL()){
		return " NOT NULL Default CURRENT_DATE";
	}
	elseif(isOracle()){
		return " NOT NULL Default CURRENT_TIMESTAMP";
	}
	elseif(isMssql()){
		return " NOT NULL Default GETDATE()";
	}
	return " NOT NULL";
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseDescribeTable($table){
	global $dbh;
	global $databaseCache;
	$cachekey=strtolower($table);
	if(isset($databaseCache['databaseDescribeTable'][$cachekey])){
		return $databaseCache['databaseDescribeTable'][$cachekey];
		}
	$recs=array();
	if(isMysqli() || isMysql()){
		//$query="desc {$table}";
		$query="show full columns from {$table}";
		if(preg_match('/^(.+?)\.(.+)$/',$table,$m)){
        	$vtable=$m[2];
		}
		else{$vtable=$table;}
		$recs=getDBRecords(array('-query'=>$query));
		foreach($recs as $i=>$rec){
        	if(preg_match('/(VIRTUAL|STORED) GENERATED/i',$rec['extra'])){
				$recs[$i]['expression']=getDBExpression($vtable,$rec['field']);
			}
		}
	}
	elseif(isOracle()){
		//SELECT owner,column_name,data_type,data_length,data_precision,nullable,default_length,data_default,character_set_name,default_on_null FROM dba_tab_columns where owner='DOT_DATA' and  table_name='ASH'
    		$query="
			SELECT
				column_name as field,
				CASE LENGTH(data_length)
          			when 0 THEN data_type
          			else data_type || '(' || data_length ||')'
        			END as  \"type\",
				'' as extra,
        			nullable as \"null\",
        			data_default as \"default\"
			FROM
				dba_tab_columns
			WHERE
				table_name = '{$table}'
			ORDER BY column_name
		";
		$recs=getDBRecords(array('-query'=>$query));
	}
	elseif(isPostgreSQL()){
		//field,type,null,key,default,extra
    		$query="
			SELECT
				a.attname as field,
				format_type(a.atttypid, a.atttypmod) as type,
				d.adsrc as extra,
		    	a.attnotnull as null
			FROM
				pg_attribute a LEFT JOIN pg_attrdef d
			ON
				A.attrelid = d.adrelid AND a.attnum = d.adnum
			WHERE
				a.attrelid = '{$table}'::regclass AND a.attnum > 0 AND NOT a.attisdropped
			ORDER BY a.attnum
		";
		$recs=getDBRecords(array('-query'=>$query));
	}
	elseif(isMssql()){
		$trecs=getDBRecords(array('-query'=>"exec sp_columns [{$table}]"));
		//echo printValue($trecs);
		foreach($trecs as $trec){
			$rec=array(
				'field'		=> $trec['column_name'],
				'type'		=> preg_match('/char$/i',$trec['type_name'])?"{$trec['type_name']}({$trec['precision']})":"{$trec['type_name']}",
				'null'		=> $trec['is_nullable']
				);
			$recs[]=$rec;
        	}
    	}
    if(!is_array($recs)){$recs='';}
    $databaseCache['databaseDescribeTable'][$cachekey]=$recs;
	return $recs;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseError(){
	//Returns the text of the error message from previous MySQL operation - supports multiple database types
	global $dbh;
	if(isMysqli()){
		if(!$dbh){return 'connection failure';}
		return mysqli_error($dbh);
		}
	elseif(isMysql()){return mysql_error();}
	elseif(isOracle()){return oci_error($dbh);}
	elseif(isPostgreSQL()){return @pg_last_error();}
	elseif(isMssql()){
		$err=mysql_error();
		$info=@mysql_info();
		if(strlen($info)){
 			$err .= "<br>\n". mysql_info();
			}
		return $err;
    	}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseEscapeString($str){
	global $dbh;
	global $CONFIG;
	$info=commonObjectInfo($dbh);
	if(isMysqli()){
		if($info['class']!='mysqli'){
			$dbh=databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
		};
		$str = function_exists('mysqli_real_escape_string')?mysqli_real_escape_string($dbh,$str):mysqli_escape_string($dbh,$str);
	}
	elseif(isMysql()){
		$str = function_exists('mysql_real_escape_string')?mysql_real_escape_string($str):mysql_escape_string($str);
	}
	elseif(isPostgreSQL()){return pg_escape_string($str);}
	else{
		//MS SQL does not have a specific escape string function
		$str = str_replace("'","''",$str);
	}
	return $str;
}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFetchAssoc($query_result){
	//Returns an associative array of the current row in the result - supports multiple database types
	if(isMysqli()){return mysqli_fetch_assoc($query_result);}
	elseif(isMysql()){return mysql_fetch_assoc($query_result);}
	elseif(isOracle()){return oci_fetch_assoc($query_result);}
	elseif(isPostgreSQL()){return pg_fetch_assoc($query_result);}
	elseif(isMssql()){return mssql_fetch_assoc($query_result);}
	elseif(isSqlite()){return $query_result->fetchArray(SQLITE3_ASSOC);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFetchObject($query_result){
	//Fetch row as object - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_fetch_object($dbh,$query_result);}
	elseif(isMysql()){return mysql_fetch_object($query_result);}
	elseif(isOracle()){return oci_fetch_object($query_result);}
	elseif(isPostgreSQL()){return pg_fetch_object($query_result);}
	elseif(isMssql()){return mssql_fetch_object($query_result);}
	elseif(isSqlite()){
		//sqlite does not really handle this so lets just do it ourselves
		$obj=$query_result->fetchArray(SQLITE3_ASSOC);
		if($obj){return (object)$obj;}
		return $obj;
	}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFetchRow($query_result){
	//Get a result row as an enumerated array - supports multiple database types
	if(isMysqli()){return mysqli_fetch_row($query_result);}
	elseif(isMysql()){return mysql_fetch_row($query_result);}
	elseif(isOracle()){return oci_fetch_row($query_result);}
	elseif(isPostgreSQL()){return pg_fetch_row($query_result);}
	elseif(isMssql()){return mssql_fetch_row($query_result);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFieldFlags($query_result,$i=-1){
	//Returns the length of the specified field - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_field_flags($dbh,$query_result,$i);}
	elseif(isMysql()){return mysql_field_flags($query_result,$i);}
	elseif(isOracle()){return oci_field_precision($query_result,$i);}
	elseif(isPostgreSQL()){}
	elseif(isMssql()){
		return null;
		return mssql_field_flags($query_result,$i);
		}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFieldLength($query_result,$i=-1){
	//Returns the length of the specified field - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_field_len($dbh,$query_result,$i);}
	elseif(isMysql()){return mysql_field_len($query_result,$i);}
	elseif(isOracle()){return oci_field_size($query_result,$i);}
	elseif(isPostgreSQL()){return pg_field_prtlen($query_result,$i);}
	elseif(isMssql()){return mssql_field_length($query_result,$i);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFieldName($query_result,$i=-1){
	//Get the name of the specified field in a result - supports multiple database types
	global $dbh;
	//mysqli does not have a mysqli_field_name function
	if(isMysqli()){return abort("mysqli_field_name does not exist!");}
	elseif(isMysql()){return mysql_field_name($query_result,$i);}
	elseif(isOracle()){return oci_field_name($query_result,$i);}
	elseif(isPostgreSQL()){return pg_field_name($query_result,$i);}
	elseif(isMssql()){return mssql_field_name($query_result,$i);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFieldType($query_result,$i=-1){
	//Get the type of the specified field in a result - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_field_type($dbh,$query_result,$i);}
	elseif(isMysql()){return mysql_field_type($query_result,$i);}
	elseif(isOracle()){return oci_field_type($query_result,$i);}
	elseif(isPostgreSQL()){return pg_field_type($query_result,$i);}
	elseif(isMssql()){return mssql_field_type($query_result,$i);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseFreeResult($query_result){
	//Free result memory - supports multiple database types
	global $dbh;
	if(!is_resource($query_result) && !is_object($query_result)){return;}
	if(isMysqli()){return mysqli_free_result($query_result);}
	elseif(isMysql()){return mysql_free_result($query_result);}
	elseif(isOracle()){return oci_free_statement($query_result);}
	elseif(isPostgreSQL()){return pg_free_result($query_result);}
	elseif(isMssql()){return mssql_free_result($query_result);}
	return null;
	}
//---------- begin function databaseIndexes ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseIndexes($table){
	if(isPostgreSQL()){return postgresqlGetDBTableIndexes($table);}
	elseif(isSqlite()){return sqliteGetDBTableIndexes($table);}
	elseif(isOracle()){return oracleGetDBTableIndexes($table);}
	elseif(isMssql()){return mssqlGetDBTableIndexes($table);}
	//Get the ID generated in the last query - supports multiple database types
	return getDBRecords(array('-query'=>"show index from {$table}"));

}
//---------- begin function databaseInsertId ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseInsertId($query_result=''){
	//Get the ID generated in the last query - supports multiple database types
	global $dbh;
	if(isMysqli()){return mysqli_insert_id($dbh);}
	elseif(isMysql()){return mysql_insert_id();}
	elseif(isSqlite()){return sqlite_last_insert_rowid();}
	elseif(isPostgreSQL()){return pg_last_oid($query_result);}
	elseif(isMssql()){
		//MSSQL does not have an insert_id function like mysql does
		$id = null;
		$res = databaseQuery('SELECT @@identity AS id');
		if ($row = mssql_fetch_row($res)) {
			$id = trim($row[0]);
			}
		mssql_free_result($res);
		return $id;
    	}
    return null;
	}
//---------- begin function databaseListDbs ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseListDbs(){
	global $dbh;
	$dbs=array();
	if(isMysqli()){
		//mysqli does not have a mysqli_list_dbs function
		$query="show databases";
		$recs=getDBRecords(array('-query'=>$query));
		foreach($recs as $rec){
			if(preg_match('/^(mysql|performance_schema|information_schema)$/',$rec['database'])){continue;}
			$dbs[]=$rec['database'];
			}
	}
	elseif(isMysql()){
		$db_list=mysql_list_dbs($dbh);
		while ($row = databaseFetchObject($db_list)) {
			$name=(string)$row->Database;
			if(preg_match('/^(mysql|performance_schema|information_schema)$/',$name)){continue;}
			$dbs[]=$name;
		}
	}
	elseif(isMssql()){
		$db_list=databaseQuery("exec sp_databases");
		while ($row = databaseFetchObject($db_list)) {
			$name=(string)$row->DATABASE_NAME;
			if(preg_match('/^(master|model|msdb|tempdb)$/',$name)){continue;}
			$dbs[]=$name;
		}
	}
	elseif(isOracle()){
    	$query="select distinct owner from dba_tab_columns WHERE table_name not like '%$&'";
		$recs=getDBRecords(array('-query'=>$query));
		foreach($recs as $rec){$dbs[]=$rec['name'];}
	}
	elseif(isPostgreSQL()){
    	$query="SELECT datname as name FROM pg_database WHERE datistemplate IS FALSE AND datallowconn IS TRUE AND datname != 'postgres'";
		$recs=getDBRecords(array('-query'=>$query));
		foreach($recs as $rec){$dbs[]=$rec['name'];}
	}
	sort($dbs);
	return $dbs;
}
//---------- begin function databaseListProcesses ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseListProcesses(){
	global $dbh;
	if(isMysqli()){return mysqli_list_processes($dbh);}
	elseif(isMysql()){return mysql_list_processes();}
	elseif(isOracle()){
		$query="SELECT sess.process, sess.status, sess.username, sess.schemaname, sql.sql_text
  			FROM v\$session sess,
       		v\$sql     sql
 			WHERE sql.sql_id(+) = sess.sql_id
   			AND sess.type     = 'USER'
			AND sess.status='ACTIVE'
			";
		return getDBRecords(array('-query'=>$query));
	}
	elseif(isPostgreSQL()){
		$query="select * from pg_stat_activity";
		return getDBRecords(array('-query'=>$query));
	}
	elseif(isMssql()){
		//not sure how for MS SQL
		return null;
		}
	return null;
	}
//---------- begin function databaseNumFields ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseNumFields($query_result){
	//Free result memory - supports multiple database types
	if(isMysqli()){return mysqli_num_fields($query_result);}
	elseif(isMysql()){return mysql_num_fields($query_result);}
	elseif(isOracle()){return oci_num_fields($query_result);}
	elseif(isPostgreSQL()){return pg_num_fields($query_result);}
	elseif(isMssql()){return mssql_num_fields($query_result);}
	return null;
	}
//---------- begin function databaseNumRows ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseNumRows($query_result){
	//Free result memory - supports multiple database types
	if(isMysqli()){return mysqli_num_rows($query_result);}
	elseif(isMysql()){return mysql_num_rows($query_result);}
	elseif(isOracle()){return oci_num_rows($query_result);}
	elseif(isPostgreSQL()){return pg_num_rows($query_result);}
	elseif(isMssql()){return mssql_num_rows($query_result);}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databasePrimaryKeyFieldString(){
	if(isMysqli() || isMysql()){return "int NOT NULL Primary Key auto_increment";}
	elseif(isOracle()){return 'NUMBER GENERATED BY DEFAULT AS IDENTITY';}
	elseif(isPostgreSQL()){return "serial PRIMARY KEY";}
	elseif(isMssql()){return "INT NOT NULL IDENTITY(1,1)";}
	elseif(isSqlite()){return "INTEGER PRIMARY KEY";}
	}
//---------- begin function databaseQuery ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseQuery($query){
	//Free result memory - supports multiple database types
	global $dbh;
	global $CONFIG;
	if(!$dbh){
		$dbh=databaseConnect($CONFIG['dbhost'], $CONFIG['dbuser'], $CONFIG['dbpass'], $CONFIG['dbname']);
	}
	if(!$dbh){return null;}
	if(isMysqli()){
		try{return mysqli_query($dbh,$query);}
		catch (Exception $e) {return null;}
	}
	elseif(isMysql()){
		try{return mysql_query($query);}
		catch (Exception $e) {return null;}
	}
	elseif(isOracle()){
		$stid=oci_parse($dbh,$query);
		oci_execute($stid, OCI_DEFAULT);
		return $stid;
	}
	elseif(isPostgreSQL()){
		try{return pg_query($dbh,$query);}
		catch (Exception $e) {return null;}
	}
	elseif(isMssql()){
		try{return mssql_query($query);}
		catch (Exception $e) {return null;}
	}
	elseif(isSqlite()){
		global $dbh_sqlite;
		if(!$dbh_sqlite){
			$dbh_sqlite=sqliteDBConnect($params);
		}
		if(!$dbh_sqlite){
			return null;
		}
		try{
			$results=$dbh_sqlite->query($query);
			if(!is_object($results)){
				return null;
			}
			return $results;
		}
		catch (Exception $e) {
			return null;
		}
		return null;	
	}
	}
//---------- begin function databaseRestoreDb ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseRestoreDb(){
	global $CONFIG;
	return databaseSelectDb($CONFIG['dbname']);
	}
//---------- begin function databaseSelectDb ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseSelectDb($dbname){
	global $CONFIG;
	global $dbh;
	if(!strlen($dbname)){return false;}
	if(isset($CONFIG['_current_dbname_']) && $dbname === $CONFIG['_current_dbname_']){return true;}
	global $dbh;
	//Free result memory - supports multiple database types
	if(isMysqli()){$rtn = mysqli_select_db($dbh,$dbname);}
	elseif(isMysql()){$rtn = mysql_select_db($dbname);}
	elseif(isPostgreSQL()){$rtn=$dbh?true:false;}
	elseif(isMssql()){$rtn = mssql_select_db($dbname);}
	elseif(isSqlite()){$rtn=1;}
	if($rtn){$CONFIG['_current_dbname_']=$dbname;}
	return $rtn;
	}
//---------- begin function databaseTables ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseTables($dbname='',$force=0){
	if(isPostgreSQL()){return postgresqlGetDBTables($dbname,$force);}
	elseif(isSqlite()){return sqliteGetDBTables($dbname,$force);}
	elseif(isOracle()){return oracleGetDBTables($dbname,$force);}
	elseif(isMssql()){return mssqlGetDBTables($dbname,$force);}
	global $databaseCache;
	global $CONFIG;
	$dbcachekey=strlen($dbname)?strtolower($dbname):$CONFIG['dbname'];
	if(!$force && isset($databaseCache['databaseTables'][$dbcachekey])){
		return $databaseCache['databaseTables'][$dbcachekey];
		}
	//returns array of user tables - supports multiple database types
	$tables=array();
	//set query string
	if(isMysqli() || isMysql()){
		$query = "SHOW TABLES";
		if(strlen($dbname)){$query .= " from {$dbname}";}
		}
	elseif(isPostgreSQL()){
    	$query="SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
	}
	elseif(isMssql()){
		global $CONFIG;
		$query = "select name from sysobjects where xtype = 'U';";
		}
	elseif(isSqlite()){
		return sqliteGetDBTables();
	}
	else{return null;}
	//run query
	$query_result=@databaseQuery($query);
  	if(!$query_result){return $query . databaseError();}
  	//build tables array
	while ($row = databaseFetchRow($query_result)) {
    	array_push($tables,$row[0]);
		}
	databaseFreeResult($query_result);
	//return sorted tables array
	sort($tables);
	$databaseCache['databaseTables'][$dbcachekey]=$tables;
	return $tables;
	}
//---------- begin function databaseVersion ----------
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseVersion(){
	if(isPostgreSQL()){return postgresqlGetDBVersion();}
	elseif(isSqlite()){return sqliteGetDBVersion();}
	elseif(isOracle()){return oracleGetDBVersion();}
	elseif(isMssql()){return mssqlGetDBVersion();}
	//Returns an associative array of the current row in the result - supports multiple database types
	if(isMysqli() || isMysql() || isPostgreSQL()){
		$recs=getDBRecords(array('-query'=>"select version() as version"));
		if(isset($recs[0]['version'])){return $recs[0]['version'];}
		return printValue($recs);
    	}
	elseif(isMssql()){
		$recs=getDBRecords(array('-query'=>"select @@version as version"));
		if(isset($recs[0]['version'])){return $recs[0]['version'];}
		return printValue($recs);
    	}
	return null;
	}
//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function databaseType(){
	//Returns an associative array of the current row in the result - supports multiple database types
	if(isMysql()){return 'Mysql';}
	elseif(isMysqli()){return 'Mysqli';}
	elseif(isPostgreSQL()){return 'PostgreSQL';}
	elseif(isMssql()){return 'MS SQL';}
	elseif(isSqlite()){return 'SQLite';}
	elseif(isOracle()){return 'Oracle';}
	return 'Unknown';
	}
//---------- begin function isMysql ----------
/**
* @describe returns true if database driver is MySQL
* @return boolean
* @usage if(isMysql()){...}
*/
function isMysql(){
	global $isMysqlCache;
	if(isset($isMysqlCache)){return $isMysqlCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='mysql'){$isMysqlCache=true;}
	else{$isMysqlCache=false;}
	return $isMysqlCache;
	}
//---------- begin function isOracle ----------
/**
* @describe returns true if database driver is Oracle
* @return boolean
* @usage if(isOracle()){...}
*/
function isOracle(){
	global $isOracleCache;
	if(isset($isOracleCache)){return $isOracleCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='oracle'){
		loadExtras('oracle');
		$isOracleCache=true;
	}
	else{$isOracleCache=false;}
	return $isOracleCache;
}
//---------- begin function isMysqli ----------
/**
* @describe returns true if database driver is MySQLi
* @return boolean
* @usage if(isMysqli()){...}
*/
function isMysqli(){
	global $isMysqliCache;
	if(isset($isMysqliCache)){return $isMysqliCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='mysqli'){$isMysqliCache=true;}
	else{$isMysqliCache=false;}
	return $isMysqliCache;
	}
//---------- begin function isPostgreSQL ----------
/**
* @describe returns true if database driver is PostgreSQL
* @return boolean
* @usage if(isPostgreSQL()){...}
*/
function isPostgreSQL(){
	global $isPostgreSQLCache;
	if(isset($isPostgreSQLCache)){return $isPostgreSQLCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='postgres'){$isPostgreSQLCache=true;}
	elseif($dbtype=='postgresql'){$isPostgreSQLCache=true;}
	else{$isPostgreSQLCache=false;}
	return $isPostgreSQLCache;
}
/**
* @exclude  - depricated - use isPostgreSQL
*/
function isPostgres(){return isPostgreSQL();}
//---------- begin function isSqlite ----------
/**
* @describe returns true if database driver is Sqlite
* @return boolean
* @usage if(isSqlite()){...}
*/
function isSqlite(){
	global $isSqliteCache;
	if(isset($isSqliteCache)){return $isSqliteCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='sqlite'){
		loadExtras('sqlite');
		$isSqliteCache=true;
	}
	else{$isSqliteCache=false;}
	return $isSqliteCache;
}
//---------- begin function isCtree ----------
/**
* @describe returns true if database driver is cTREE
* @return boolean
* @usage if(isCtree()){...}
*/
function isCtree(){
	global $isCtreeCache;
	if(isset($isCtreeCache)){return $isCtreeCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='ctree'){
		loadExtras('ctree');
		$isCtreeCache=true;
	}
	else{$isCtreeCache=false;}
	return $isCtreeCache;
}
//---------- begin function isFirebird ----------
/**
* @describe returns true if database driver is cTREE
* @return boolean
* @usage if(isFirebird()){...}
*/
function isFirebird(){
	global $isFirebirdCache;
	if(isset($isFirebirdCache)){return $isFirebirdCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='firebird'){
		loadExtras('firebird');
		$isFirebirdCache=true;
	}
	else{$isFirebirdCache=false;}
	return $isFirebirdCache;
}
//---------- begin function isMsaccess ----------
/**
* @describe returns true if database driver is MS Access
* @return boolean
* @usage if(isMsaccess()){...}
*/
function isMsaccess(){
	global $isMsaccessCache;
	if(isset($isMsaccessCache)){return $isMsaccessCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='msaccess'){
		loadExtras('msaccess');
		$isMsaccessCache=true;
	}
	else{$isMsaccessCache=false;}
	return $isMsaccessCache;
}
//---------- begin function isMsaccess ----------
/**
* @describe returns true if database driver is MS Access
* @return boolean
* @usage if(isMsaccess()){...}
*/
function isMsexcel(){
	global $isMsexcelCache;
	if(isset($isMsexcelCache)){return $isMsexcelCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='msexcel'){
		loadExtras('msexcel');
		$isMsexcelCache=true;
	}
	else{$isMsexcelCache=false;}
	return $isMsexcelCache;
}
//---------- begin function isMscsv ----------
/**
* @describe returns true if database driver is MS CSV
* @return boolean
* @usage if(isMscsv()){...}
*/
function isMscsv(){
	global $isMscsvCache;
	if(isset($isMscsvCache)){return $isMscsvCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='mscsv'){
		loadExtras('mscsv');
		$isMscsvCache=true;
	}
	else{$isMscsvCache=false;}
	return $isMscsvCache;
}
//---------- begin function isMssql ----------
/**
* @describe returns true if database driver is MS SQL
* @return boolean
* @usage if(isMssql()){...}
*/
function isMssql(){
	global $isMssqlCache;
	if(isset($isMssqlCache)){return $isMssqlCache;}
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='mssql'){
		loadExtras('mssql');
		$isMssqlCache=true;
	}
	else{$isMssqlCache=false;}
	return $isMssqlCache;
}
//---------- begin function isODBC ----------
/**
* @describe returns true if database driver is ODBC
* @return boolean
* @usage if(isODBC()){...}
*/
function isODBC(){
	global $CONFIG;
	$dbtype=strtolower(trim($CONFIG['dbtype']));
	if($dbtype=='odbc'){return true;}
	return false;
	}
//---------- begin function setDBSettings ----------
/**
* setDBSettings - sets a value in the settings table
* @param key_name string
* @param key_value string
* @param user_id int - optional. defaults to current USER id or 0
* @return
* 	<p>@usage setDBSettings($key_name,$key_value,$userid);</p>
*/
function setDBSettings($name,$value,$userid){
	global $USER;
	if(!isNum($userid)){
		$userid=setValue(array($USER['_id'],0));
		}
	$rec=getDBRecord(array('-table'=>'_settings','key_name'=>$name,'user_id'=>$userid));
	if(is_array($rec)){
		return editDBRecord(array('-table'=>'_settings','-where'=>"_id={$rec['_id']}",'key_value'=>$value));
    }
    else{
		return addDBRecord(array('-table'=>'_settings','key_name'=>$name,'user_id'=>$userid,'key_value'=>$value));
	}
}
//---------- begin function getDBSettings ----------
/**
* getDBSettings - retrieves a value in the settings table
* @param key_name string
* @param user_id int - optional. defaults to current USER id or 0
* @param collapse boolean - optional. defaults to false. If true, collapses XML array results
* @return  if key_value is xml it returns an array, otherwise a string
* 	<p>@usage $val=getDBSettings($key_name,$userid);</p>
*/
function getDBSettings($name,$userid,$collapse=0){
	global $USER;
	if(!isNum($userid)){
		$userid=setValue(array($USER['_id'],0));
		}
	$settings=null;
	$rec=getDBRecord(array('-table'=>'_settings','key_name'=>$name,'user_id'=>$userid));
	if(is_array($rec)){
		if(isXML($rec['key_value'])){
			$settings = xml2Array($rec['key_value']);
			if($collapse && is_array($settings['request'])){
				$settings=$settings['request'];
				if(is_array($settings)){
                	foreach($settings as $key=>$val){
                    	if(isset($settings[$key]['values']) && is_array($settings[$key]['values'])){
                        	$settings[$key]=$settings[$key]['values'];
						}
					}
				}
			}
		}
		else{$settings=$rec['key_value'];}
    }
    return $settings;
}
//---------- begin function grepDBTables ----------
/**
* grepDBTables - searches across tables for a specified value
* @param search string
* @param tables array - optional. defaults to all tables except for _changelog,_cron_log, and _errors
* @return  array of arrays - tablename,_id,fieldname,search_count
* @usage $results=grepDBTables('searchstring');
*/
function grepDBTables($search,$tables=array(),$dbname=''){
	if(isPostgreSQL()){return postgresqlGrepDBTables($search,$tables,$dbname);}
	elseif(isSqlite()){return sqliteGrepDBTables($search,$tables,$dbname);}
	elseif(isOracle()){return oracleGrepDBTables($search,$tables,$dbname);}
	elseif(isMssql()){return mssqlGrepDBTables($search,$tables,$dbname);}
	if(!is_array($tables)){
		if(strlen($tables)){$tables=array($tables);}
		else{$tables=array();}
	}
	if(!count($tables)){
		$tables=getDBTables($dbname);
		//ignore _changelog
		foreach($tables as $i=>$table){
			if(in_array($table,array('_changelog','_cron_log','_errors'))){unset($tables[$i]);}
		}
	}
	//return $tables;
	$search=trim($search);
	if(!strlen($search)){return "grepDBTables Error: no search value";}
	$results=array();
	$search=str_replace("'","''",$search);
	$search=strtolower($search);
	foreach($tables as $table){
		if(strlen($dbname)){$table=$dbname.'.'.$table;}
		if(!isDBTable($table)){return "grepDBTables Error: {$table} is not a table";}
		$info=getDBFieldInfo($table);
		$wheres=array();
		$fields=array();
		foreach($info as $field=>$finfo){
			switch($info[$field]['_dbtype']){
				case 'int':
				case 'integer':
				case 'number':
				case 'float':
					if(isNum($search)){
						$wheres[]="{$field}={$search}";
						$fields[]=$field;
					}
				break;
				case 'varchar':
				case 'char':
				case 'string':
				case 'blob':
				case 'text':
				case 'mediumtext':
					$wheres[]="{$field} like '%{$search}%'";
					$fields[]=$field;
				break;
			}
		}
		if(!count($wheres)){continue;}
		$fields_checked=implode(', ',$fields);
		if(!in_array('_id',$fields)){array_unshift($fields,'_id');}
		$where=implode(' or ',$wheres);
		$fields=implode(',',$fields);
		$recopts=array('-table'=>$table,'-where'=>$where,'-fields'=>$fields);
		$recs=getDBRecords($recopts);
		if(is_array($recs)){
			$cnt=count($recs);
			foreach($recs as $rec){
				$vals=array();
				foreach($rec as $key=>$val){
					if(stringContains($val,$search)){
						$results[]=array(
							'tablename'=>$table,
							'_id'		=> $rec['_id'],
							'fieldname' => $key,
							'search_count'=> substr_count(strtolower($val),$search),
							'fields_checked'=>$fields_checked
						);
					}
				}
			}
		}
	}
	return $results;
}
//---------- begin function listDBDatatypes ----
/**
* @describe returns the data types for mysql
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function listDBDatatypes(){
	//default to mysql
	return <<<ENDOFDATATYPES
<div class="w_bold w_blue w_padtop">Text Types</div>
<div class="w_padleft">CHAR( ) A fixed section from 0 to 255 characters long.</div>
<div class="w_padleft">VARCHAR( ) A variable section from 0 to 255 characters long.</div>
<div class="w_padleft">TINYTEXT A string with a maximum length of 255 characters.</div>
<div class="w_padleft">TEXT A string with a maximum length of 65535 characters.</div>
<div class="w_padleft">MEDIUMTEXT A string with a maximum length of 16777215 characters.</div>
<div class="w_padleft">LONGTEXT A string with a maximum length of 4294967295 characters.</div>

<div class="w_bold w_blue w_padtop">Number Types</div>
<div class="w_padleft">TINYINT( ) -128 to 127 normal or 0 to 255 UNSIGNED.</div>
<div class="w_padleft">SMALLINT( ) -32768 to 32767 normal or 0 to 65535 UNSIGNED.</div>
<div class="w_padleft">MEDIUMINT( ) -8388608 to 8388607 normal or 0 to 16777215 UNSIGNED.</div>
<div class="w_padleft">INT( ) -2147483648 to 2147483647 normal or 0 to 4294967295 UNSIGNED.</div>
<div class="w_padleft">BIGINT( ) -9223372036854775808 to 9223372036854775807 normal or 0 to 18446744073709551615 UNSIGNED.</div>
<div class="w_padleft">FLOAT A small number with a floating decimal point.</div>
<div class="w_padleft">DOUBLE( , ) A large number with a floating decimal point.</div>
<div class="w_padleft">DECIMAL( , ) A DOUBLE stored as a string , allowing for a fixed decimal point.</div>

<div class="w_bold w_blue w_padtop">Date Types</div>
<div class="w_padleft">DATE YYYY-MM-DD.</div>
<div class="w_padleft">DATETIME YYYY-MM-DD HH:MM:SS.</div>
<div class="w_padleft">TIMESTAMP YYYYMMDDHHMMSS.</div>
<div class="w_padleft">TIME HH:MM:SS.</div>
ENDOFDATATYPES;
}
//---------- begin function showDBCronPanel ----
/**
* @describe shows the cron panel and updates automatically
* @return string
* @author slloyd
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function showDBCronPanel($ajax=0,$frequency=60){
	$rtn='';
	if($ajax==0){
		$rtn .= '<div class="w_pad w_round w_smaller w_right w_border w_tip" style="width:170px;z-index:999;position:absolute;top:5px;right:10px;">'.PHP_EOL;
		$rtn .= '	<div class="w_right"><img src="/wfiles/iconsets/16/close.png" style="cursor:pointer;" onclick="removeId(\'cronpanel\');" alt="close" /></div>'.PHP_EOL;
		$rtn .= '	<div class="w_bold"><img src="/wfiles/_cron.png" width="16" height="16" style="vertical-align:middle;" alt="cron info panel" /> Cron Information Panel</div>'.PHP_EOL;
		$rtn .= '	<div id="cronpanel">'.PHP_EOL;
		}
	//show date updated
	$rtn .= '			<table class="w_table w_nopad"><tr><td><div style="color:#CCC;font-size:10pt;" align="center">'.date("F j, Y, g:i a").'</div></td><td style="padding-left:5px;"><div style="color:#CCC;font-size:10pt;padding:1px 2px 1px 2px;border:1px solid #CCC;" id="crontimer" data-behavior="countdown">'.$frequency.'</div></td></tr></table>'.PHP_EOL;
	$rtn .= '			<hr size="1">'.PHP_EOL;
	$recs=getDBRecords(array('-table'=>"_cron"));
	//collect some stats
	$stats=array('cron_pid'=>array(),'active'=>0,'running'=>array());
	foreach($recs as $rec){
		if($rec['active']==1){$stats['active']+=1;}
		if($rec['running']==1){$stats['running'][]=$rec;}
		$stats['cron_pid'][$rec['cron_pid']]+=1;
		if(!isset($stats['lastrun']) || $rec['run_date_utime'] > $stats['lastrun']['run_date_utime']){$stats['lastrun']=$rec;}
		}
	//how many crons are running?
	if(!count($stats['cron_pid'])){
		$rtn .= '		<div><img src="/wfiles/iconsets/16/warning.png" style="vertical-align:middle" alt="warning" /><b class="w_red">WARNING!</b> NO cron servers are listening. At least one cron server must be running in order for cron jobs to work.</div>'.PHP_EOL;
		}
	else{
		$rtn .= '		<div><img src="/wfiles/iconsets/16/checkmark.png" width="16" height="16" style="vertical-align:bottom;" alt="" /> '.count($stats['cron_pid']).' Cron servers listening</div>'.PHP_EOL;
		}
	//last run
	if(is_array($stats['lastrun'])){
		$elapsed=time()-$stats['lastrun']['run_date_utime'];
		$rtn .= '		<div class="w_bold w_pad">"'.$stats['lastrun']['name'].'" ran '.verboseTime($elapsed).' ago</div>'.PHP_EOL;
		}
	//running crons list
	if(count($stats['running'])){
		$rtn .= '		<div class="w_bold w_pad">'.count($recs).' Cron Jobs Running</div>'.PHP_EOL;
		foreach($stats['running'] as $rec){
			$rtn .= '		<div style="margin-left:15px;">'.PHP_EOL;
			$rtn .= "			{$rec['_id']}.  {$rec['name']}";
			$rtn .= '		</div>'.PHP_EOL;
            }
		}
    else{$rtn .= '		<div class="w_bold w_pad">NO Cron Jobs Running</div>'.PHP_EOL;}
	$frequency=$frequency*1000;
	$sort=encodeURL($_REQUEST['_sort']);
	$rtn .= '			' . buildOnLoad("initBehaviors();scheduleAjaxGet('cronpanel','php/index.php','cronpanel','_action=cronpanel&_sort={$sort}&freq={$frequency}',{$frequency},1);");
	if($ajax==0){
		$rtn .= '	</div>'.PHP_EOL;
		$rtn .= '</div>'.PHP_EOL;
		}
	return $rtn;
}