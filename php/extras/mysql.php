<?php

/*
	mysql Database functions
		https://dev.mysql.com/doc/refman/8.0/en/
		https://www.php.net/manual/en/ref.mysql.php
*/
//---------- begin function mysqlParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=mysqlParseConnectParams($params);
*/
function mysqlParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			$params["-{$k}"]=$v;
		}
	}
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_mysql'])){
			$params['-dbname']=$CONFIG['dbname_mysql'];
			$params['-dbname_source']="CONFIG dbname_mysql";
		}
		elseif(isset($CONFIG['mysql_dbname'])){
			$params['-dbname']=$CONFIG['mysql_dbname'];
			$params['-dbname_source']="CONFIG mysql_dbname";
		}
		elseif(isset($CONFIG['dbname'])){
			$params['-dbname']=$CONFIG['dbname'];
			$params['-dbname_source']="CONFIG dbname";
		}
		else{return 'mysqlParseConnectParams Error: No dbname set'.printValue($CONFIG);}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	//readonly
	if(!isset($params['-mysql_readonly']) && isset($CONFIG['mysql_readonly'])){
		$params['-readonly']=$CONFIG['mysql_readonly'];
	}
	//dbmode
	if(!isset($params['-dbmode'])){
		if(isset($CONFIG['dbmode_mysql'])){
			$params['-dbmode']=$CONFIG['dbmode_mysql'];
			$params['-dbmode_source']="CONFIG dbname_mysql";
		}
		elseif(isset($CONFIG['mysql_dbmode'])){
			$params['-dbmode']=$CONFIG['mysql_dbmode'];
			$params['-dbmode_source']="CONFIG mysql_dbname";
		}
	}
	else{
		$params['-dbmode_source']="passed in";
	}
	return $params;
}
//---------- begin function mysqlDBConnect ----------
/**
* @describe connects to a mysql database and returns the handle resource
* @param $param array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
* 	[-dbname] - name of ODBC connection
*   [-single] - if you pass in -single it will connect using mysql_connect instead of mysql_pconnect and return the connection
* @return $dbh_mysql resource - returns the mysql connection resource
* @usage $dbh_mysql=mysqlDBConnect($params);
* @usage  example of using -single
*
	$conn=mysqlDBConnect(array('-single'=>1));
	mysql_autocommit($conn, FALSE);

	mysql_exec($conn, $query1);
	mysql_exec($conn, $query2);

	if (!mysql_error()){
		mysql_commit($conn);
	}
	else{
		mysql_rollback($conn);
	}
	mysql_close($conn);
*
*/
function mysqlDBConnect($params=array()){
	global $CONFIG;
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=mysqlParseConnectParams($params);
	if(!isset($params['-dbname'])){
		$CONFIG['mysql_error']="dbname not set";
		debugValue("mysqlDBConnect error: no dbname set".printValue($params));
		return null;
	}
	if(!isset($params['-mode'])){$params['-mode']=0666;}
	//echo printValue($params).printValue($_SERVER);exit;
	//check to see if the mysql database is available. Find it if possible
	if(!file_exists($params['-dbname'])){
		$CONFIG['mysql_error']="dbname does not exist";
		$cfiles=array(
			realpath("{$_SERVER['DOCUMENT_ROOT']}{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}../{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}../../{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}/{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}/../{$params['-dbname']}"),
			realpath("{$_SERVER['DOCUMENT_ROOT']}/../../{$params['-dbname']}"),
			realpath("../{$params['-dbname']}")
		);
		foreach($cfiles as $cfile){
			if(file_exists($cfile)){
				$params['-dbname']=$cfile;
			}
		}
		//echo printValue($cfiles).printValue($params);exit;
	}
	$CONFIG['mysql_dbname_realpath']=$params['-dbname'];
	//echo printValue($params);exit;
	global $dbh_mysql;
	if($dbh_mysql){return $dbh_mysql;}
	try{
		if(isset($params['-readonly']) && $params['-readonly']==1){
			$dbh_mysql = new mysql3($params['-dbname'],mysql3_OPEN_READONLY);
		}
		else{
			$dbh_mysql = new mysql3($params['-dbname'],mysql3_OPEN_READWRITE | mysql3_OPEN_CREATE);	
		}
		
		$dbh_mysql->busyTimeout(5000);
		// WAL mode has better control over concurrency.
		// Source: https://www.mysql.org/wal.html
		$dbh_mysql->exec('PRAGMA journal_mode = wal;');
		return $dbh_mysql;
	}
	catch (Exception $e) {
		$err=$e->getMessage();
		$CONFIG['mysql_error']=$err;
		debugValue("mysqlDBConnect exception - {$err}");
		return null;

	}
}
//---------- begin function mysqlIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(mysqlIsDBTable('abc')){...}
*/
function mysqlIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "mysqlIsDBTable error: No table";
		return null;
	}
	$table=strtolower($table);
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$query="SELECT name FROM {$parts[0]}.mysql_master WHERE type='table' and name = '{$table}'";
		$table=$parts[1];
	}
	else{
		$query="SELECT name FROM mysql_master WHERE type='table' and name = '{$table}'";
	}
	$recs=mysqlQueryResults($query);
	if(isset($recs[0]['name'])){return true;}
	return false;
}
//---------- begin function mysqlGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_mysql,dbuser_mysql, and dbpass_mysql
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of table names
* @usage $tables=mysqlGetDBTables();
*/
function mysqlGetDBTables($params=array()){
	$tables=array();
	$query="SELECT name FROM mysql_master WHERE type='table'";
	$recs=mysqlQueryResults($query);
	foreach($recs as $rec){
		$tables[]=strtolower($rec['name']);
	}
	return $tables;
}