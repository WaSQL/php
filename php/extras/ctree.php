<?php
/*
	ctree.php - a collection of cTREE Database functions for use by WaSQL.
	
	References:
		https://www.yumpu.com/en/document/read/30279025/sql-reference-guide-cove-systems
		https://docs.faircom.com/doc/sqlref/sqlref.pdf
*/

//---------- begin function ctreeDBConnect ----------
/**
* @describe returns connection resource
* @param $params array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
*	[-host] - ctree server to connect to
* 	[-dbname] - name of database.
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return connection resource and sets the global $dbh_ctree variable.
* @usage $dbh_ctree=ctreeDBConnect($params);
*/
function ctreeDBConnect(){
	$params=ctreeParseConnectParams();
	//echo "ctreeDBConnect".printValue($params);exit;
	if(!isset($params['-connect'])){
		echo "ctreeDBConnect error: no connect params".printValue($params);
		exit;
	}
	global $dbh_ctree;
	if(is_object($dbh_ctree)){return $dbh_ctree;}
	try{
		$dbh_ctree = new PDO($params['-connect'],$params['-dbuser'],$params['-dbpass'],array(PDO::ATTR_PERSISTENT => true));
		$dbh_ctree->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh_ctree;
	}
	catch (Exception $e) {
		$error=array("ctreeDBConnect Exception",$e,$params);
	    debugValue($error);
	    return json_encode($error);
	}
}
//---------- begin function ctreeExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* @return boolean returns true if query succeeded
* @usage $ok=ctreeExecuteSQL("truncate table abc");
*/
function ctreeExecuteSQL($query,$return_error=1){
	global $dbh_ctree;
	$dbh_ctree=ctreeDBConnect();
	if(!is_object($dbh_ctree)){
		$err=array(
			'function'=>'ctreeExecuteSQL',
			'message'=>'connect failed',
			'query'=>$query
		);
		debugValue($err);
		if($return_error==1){return $err;}
    	return 0;
	}
	try{
		$stmt = $dbh_ctree->prepare($query);
		$stmt->execute();
		$stmt->closeCursor(); // this is not even required
		$stmt = null; // doing this is mandatory for connection to get closed
		$dbh_ctree = null;
	}
	catch (Exception $e) {
		$err=array(
			'function'=>'ctreeExecuteSQL',
			'message'=>'try catch failed',
			'error'=>$e->errorInfo,
			'query'=>$query
		);
		debugValue($err);
		if($return_error==1){return $err;}
		return 0;
	}
	return 0;
}
//---------- begin function ctreeGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
*	[-host] -  server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array
* @usage $cnt=ctreeGetDBCount(array('-table'=>'states'));
*/
function ctreeGetDBCount($params=array()){
	if(!isset($params['-table'])){return null;}
	if(!stringContains($params['-table'],'.')){
		$schema=ctreeGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	//echo printValue($params);exit;
	$params['-fields']="count(*) as cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	//$params['-debug']=1;
	$recs=ctreeGetDBRecords($params);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue(array(
			'function'=>'ctreeGetDBCount',
			'message'=>'get count failed',
			'error'=>$recs,
			'params'=>$params
		));
		return 0;
	}
	return $recs[0]['cnt'];
}

//---------- begin function ctreeGetDBFields ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage $fieldinfo=ctreeGetDBFieldInfo('test');
*/
function ctreeGetDBFields($table,$allfields=0){
	$table=strtolower($table);
	$query=<<<ENDOFQUERY
		SELECT col
		WHERE tbl='{$table}'
		order by collation
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	$fields=array();
	foreach($recs as $rec){
		$fields[]=$rec['col'];
	}
	return $fields;
}
//---------- begin function ctreeGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name, type, length, num, default
* @param $params array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* @return boolean returns true on success
* @usage $fieldinfo=ctreeGetDBFieldInfo('test');
*/
function ctreeGetDBFieldInfo($table){
	$table=strtolower($table);
	$query=<<<ENDOFQUERY
		SELECT
		c.col
		,c.coltype
		,c.width
		,c.scale
		,c.nullflag
		,max(case when i.idxtype='U' then 'P' when length(i.idxtype) > 0 then 'I' else '' end) as findex
	FROM
		admin.syscolumns c
		left outer join admin.sysindexes i on c.col=i.colname and i.tbl=c.tbl
	WHERE
		c.tbl='{$tablename}'
	GROUP BY
		c.col
		,c.coltype
		,c.width
		,c.scale
		,c.nullflag
	ORDER BY 6 desc,1
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	$fields=array();
	foreach($recs as $rec){
	    $fieldname = strtolower($rec['col']);
		$field=array(
			'_dbtable'	=> $table,
			'table'		=> $table,
			'name'		=> $fieldname,
		 	'_dbfield'	=> strtolower($fieldname),
		 	'_dbtype'	=> $rec['coltype'],
			'length'	=> $rec['width'],
			'num'		=> $rec['width'],
			'size'		=> $rec['scale'],
			'nullable'	=> $rec['nullflag'],
			'findex'	=> $rec['findex']
		);
		$field['_dblength']=$field['length'];
		$field['_dbtype']=$field['_dbtype_ex']=$field['type']=strtolower($field['_dbtype']);
		if($field['size'] > 0){
			$field['_dbtype_ex']=strtolower("{$field['_dbtype']}({$field['size']})");
		}
		$fields[$fieldname]=$field;
	}
	ksort($fields);
	//meta fields?
	$databaseCache['ctreeGetDBFieldInfo'][$cachekey]=$fields;
	return $databaseCache['ctreeGetDBFieldInfo'][$cachekey];
}
function ctreeGetDBIndexes($tablename=''){
	return ctreeGetDBTableIndexes($tablename);
}
function ctreeGetDBTableIndexes($tablename=''){
	$query=<<<ENDOFQUERY
		SELECT
			idxname,
			colname,
			idxtype,
			idxseq
		FROM admin.sysindexes
		WHERE tbl='{$tablename}'
		ORDER BY idxorder
ENDOFQUERY;
	return ctreeQueryResults($query);
}
//---------- begin function ctreeGetDBRecord ----------
/**
* @describe retrieves a single record from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordset
* @usage $rec=ctreeGetDBRecord(array('-table'=>'tesl));
*/
function ctreeGetDBRecord($params=array()){
	$recs=ctreeGetDBRecords($params);
	if(isset($recs[0])){return $recs[0];}
	return null;
}
//---------- begin function ctreeGetDBRecords
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
*	<?=ctreeGetDBRecords(array('-table'=>'notes'));?>
*	<?=ctreeGetDBRecords("select * from myschema.mytable where ...");?>
*/
function ctreeGetDBRecords($params){
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
			echo $params.PHP_EOL."REQUEST: ".PHP_EOL.printValue($_REQUEST);exit;
			$ok=ctreeExecuteSQL($params);
			return $ok;
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
	}
	else{
		if(empty($params['-table'])){
			debugValue(array(
				'function'=>'ctreeGetDBRecords',
				'message'=>'no table',
				'params'=>$params
			));
	    	return null;
		}
		//check for schema name
		if(!stringContains($params['-table'],'.')){
			$schema=ctreeGetDBSchema();
			if(strlen($schema)){
				$params['-table']="{$schema}.{$params['-table']}";
			}
		}
		//determine fields to return
		if(!empty($params['-fields'])){
			if(!is_array($params['-fields'])){
				$params['-fields']=preg_split('/\,/',$params['-fields']);
			}
			$params['-fields']=implode(',',$params['-fields']);
		}
		if(empty($params['-fields'])){$params['-fields']='*';}
		$fields=ctreeGetDBFieldInfo($params['-table'],$params);
		//echo printValue($fields);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!strlen(trim($v))){continue;}
			if(!isset($fields[$k])){continue;}
			if(is_array($params[$k])){
	            $params[$k]=implode(':',$params[$k]);
			}
	        $params[$k]=str_replace("'","''",$params[$k]);
	        switch(strtolower($fields[$k])){
	        	case 'char':
	        	case 'varchar':
	        		$v=strtoupper($params[$k]);
	        		$ands[]="upper({$k})='{$v}'";
	        	break;
	        	case 'int':
	        	case 'int4':
	        	case 'numeric':
	        		$ands[]="{$k}={$v}";
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
		$wherestr='';
		if(count($ands)){
			$wherestr='WHERE '.implode(' and ',$ands);
		}
		//offset and limit
		$paginate='';
    	if(!isset($params['-nolimit'])){
	    	$offset=isset($params['-offset'])?$params['-offset']:0;
	    	$limit=25;
	    	if(!empty($params['-limit'])){$limit=$params['-limit'];}
	    	elseif(!empty($CONFIG['paging'])){$limit=$CONFIG['paging'];}
	    	$paginate = "SKIP {$offset} top {$limit}";
	    }

	    $query="SELECT {$paginate} {$params['-fields']} FROM {$params['-table']} {$wherestr}";
	    if(isset($params['-order'])){
    		$query .= " ORDER BY {$params['-order']}";
    	}
	}
	if(isset($params['-debug'])){return $query;}
	return ctreeQueryResults($query,$params);
}

//---------- begin function ctreeIsDBTable ----------
/**
* @describe returns true if table already exists
* @param table string
* @return boolean
* @usage if(ctreeIsDBTable('_users')){...}
*/
function ctreeIsDBTable($table='',$force=0){
	global $databaseCache;
	$table=strtolower($table);
	if($force==0 && isset($databaseCache['ctreeIsDBTable'][$table])){
		return $databaseCache['ctreeIsDBTable'][$table];
	}
	$tables=ctreeGetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function ctreeGetDBTables ----------
/**
* @describe returns an array of tables
* @param [$params] array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* @return array returns array of tables
* @usage $tables=ctreeGetDBTables();
*/
function ctreeGetDBTables($params=array()){
	$query=<<<ENDOFQUERY
		SELECT tbl
		FROM admin.systables
ENDOFQUERY;
	$recs=ctreeQueryResults($query);
	$tables=array();
	foreach($recs as $rec){
		$tables[]=$rec['tbl'];
	}
	sort($tables);
	return $tables;
}
//---------- begin function ctreeGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param table string - specified table
* @return array returns array of primary key fields
* @usage $fields=ctreeGetDBTablePrimaryKeys($table);
*/
function ctreeGetDBTablePrimaryKeys($table){
	$query=<<<ENDOFQUERY
		SELECT
			colname
		FROM admin.sysindexes
		WHERE
			tbl='{$table}'
			and idxtype='U'
ENDOFQUERY;
	return ctreeQueryResults($query);
	
}
function ctreeGetDBSchema(){
	global $CONFIG;
	$params=ctreeParseConnectParams();
	if(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['ctree_dbschema'])){return $CONFIG['ctree_dbschema'];}
	elseif(isset($CONFIG['ctree_schema'])){return $CONFIG['ctree_schema'];}
	return '';
}

function ctreeGetConfigValue($field){
	//dbschema, dbname
	global $CONFIG;
	switch(strtolower($CONFIG['dbtype'])){
		case 'ctree':
			if(isset($CONFIG[$field])){return $CONFIG[$field];}
			elseif(isset($CONFIG["ctree_{$field}"])){return $CONFIG["ctree_{$field}"];}
		break;
		default:
			if(isset($CONFIG["ctree_{$field}"])){return $CONFIG["ctree_{$field}"];}
		break;
	}
	return null;
}
//---------- begin function ctreeListRecords
/**
* @describe returns an html table of records from a mmsql database. refer to databaseListRecords
*/
function ctreeListRecords($params=array()){
	$params['-database']='ctree';
	return databaseListRecords($params);
}
//---------- begin function ctreeParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param [$params] array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
*	[-host] - ctree server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return $params array
* @usage $params=ctreeParseConnectParams($params);
*/
function ctreeParseConnectParams($params=array()){
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^ctree/i',$k)){unset($CONFIG[$k]);}
		}
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			$params["-{$k}"]=$v;
		}
	}
	//check for user specific
	if(isUser() && strlen($USER['username'])){
		foreach($params as $k=>$v){
			if(stringEndsWith($k,"_{$USER['username']}")){
				$nk=str_replace("_{$USER['username']}",'',$k);
				unset($params[$k]);
				$params[$nk]=$v;
			}
		}
	}
	//echo "HERE".printValue($params);exit;
	if(isctree()){
		$params['-dbhost']=$CONFIG['dbhost'];
		if(isset($CONFIG['dbname'])){
			$params['-dbname']=$CONFIG['dbname'];
		}
		if(isset($CONFIG['dbuser'])){
			$params['-dbuser']=$CONFIG['dbuser'];
		}
		if(isset($CONFIG['dbpass'])){
			$params['-dbpass']=$CONFIG['dbpass'];
		}
		if(isset($CONFIG['dbport'])){
			$params['-dbport']=$CONFIG['dbport'];
		}
		if(isset($CONFIG['dbconnect'])){
			$params['-connect']=$CONFIG['dbconnect'];
		}
	}
	//dbhost
	if(!isset($params['-dbhost'])){
		if(isset($CONFIG['dbhost_ctree'])){
			$params['-dbhost']=$CONFIG['dbhost_ctree'];
			//$params['-dbhost_source']="CONFIG dbhost_ctree";
		}
		elseif(isset($CONFIG['ctree_dbhost'])){
			$params['-dbhost']=$CONFIG['ctree_dbhost'];
			//$params['-dbhost_source']="CONFIG ctree_dbhost";
		}
		else{
			$params['-dbhost']=$params['-dbhost_source']='localhost';
		}
	}
	else{
		//$params['-dbhost_source']="passed in";
	}
	$CONFIG['ctree_dbhost']=$params['-dbhost'];
	
	//dbuser
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_ctree'])){
			$params['-dbuser']=$CONFIG['dbuser_ctree'];
			//$params['-dbuser_source']="CONFIG dbuser_ctree";
		}
		elseif(isset($CONFIG['ctree_dbuser'])){
			$params['-dbuser']=$CONFIG['ctree_dbuser'];
			//$params['-dbuser_source']="CONFIG ctree_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['ctree_dbuser']=$params['-dbuser'];
	//dbpass
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_ctree'])){
			$params['-dbpass']=$CONFIG['dbpass_ctree'];
			//$params['-dbpass_source']="CONFIG dbpass_ctree";
		}
		elseif(isset($CONFIG['ctree_dbpass'])){
			$params['-dbpass']=$CONFIG['ctree_dbpass'];
			//$params['-dbpass_source']="CONFIG ctree_dbpass";
		}
	}
	else{
		//$params['-dbpass_source']="passed in";
	}
	$CONFIG['ctree_dbpass']=$params['-dbpass'];
	//dbname
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['dbname_ctree'])){
			$params['-dbname']=$CONFIG['dbname_ctree'];
			//$params['-dbname_source']="CONFIG dbname_ctree";
		}
		elseif(isset($CONFIG['ctree_dbname'])){
			$params['-dbname']=$CONFIG['ctree_dbname'];
			//$params['-dbname_source']="CONFIG ctree_dbname";
		}
		else{
			$params['-dbname']=$CONFIG['ctree_dbname'];
			//$params['-dbname_source']="set to username";
		}
	}
	else{
		//$params['-dbname_source']="passed in";
	}
	$CONFIG['ctree_dbname']=$params['-dbname'];
	//dbport
	if(!isset($params['-dbport'])){
		if(isset($CONFIG['dbport_ctree'])){
			$params['-dbport']=$CONFIG['dbport_ctree'];
			//$params['-dbport_source']="CONFIG dbport_ctree";
		}
		elseif(isset($CONFIG['ctree_dbport'])){
			$params['-dbport']=$CONFIG['ctree_dbport'];
			//$params['-dbport_source']="CONFIG ctree_dbport";
		}
		else{
			$params['-dbport']=5432;
			//$params['-dbport_source']="default port";
		}
	}
	else{
		//$params['-dbport_source']="passed in";
	}
	$CONFIG['ctree_dbport']=$params['-dbport'];
	//dbschema
	if(!isset($params['-dbschema'])){
		if(isset($CONFIG['dbschema_ctree'])){
			$params['-dbschema']=$CONFIG['dbschema_ctree'];
			//$params['-dbuser_source']="CONFIG dbuser_ctree";
		}
		elseif(isset($CONFIG['ctree_dbschema'])){
			$params['-dbschema']=$CONFIG['ctree_dbschema'];
			//$params['-dbuser_source']="CONFIG ctree_dbuser";
		}
	}
	else{
		//$params['-dbuser_source']="passed in";
	}
	$CONFIG['ctree_dbschema']=$params['-dbschema'];
	//connect
	if(!isset($params['-connect'])){
		if(isset($CONFIG['ctree_connect'])){
			$params['-connect']=$CONFIG['ctree_connect'];
		}
		elseif(isset($CONFIG['connect_ctree'])){
			$params['-connect']=$CONFIG['connect_ctree'];
		}
		else{
			$params['-connect']="host={$CONFIG['ctree_dbhost']} port={$CONFIG['ctree_dbport']} dbname={$CONFIG['ctree_dbname']} user={$CONFIG['ctree_dbuser']} password={$CONFIG['ctree_dbpass']}";
		}
		//add application_name
		if(!stringContains($params['-connect'],'options')){
			if(isset($params['-application_name'])){
				$appname=$params['-application_name'];
			}
			elseif(isset($CONFIG['ctree_application_name'])){
				$appname=$CONFIG['ctree_application_name'];
			}
			else{
				$appname='WaSQL_on_'.$_SERVER['HTTP_HOST'];
			}
			$appname=str_replace(' ','_',$appname);
			$params['-connect'].=" options='--application_name={$appname}'";
		}
		//add connect_timeout
		if(!stringContains($params['-connect'],'connect_timeout')){
			$params['-connect'].=" connect_timeout=5";
		}
	}
	else{
		//$params['-connect_source']="passed in";
	}
	//echo printValue($params);exit;
	return $params;
}
//---------- begin function ctreeQueryResults ----------
/**
* @describe returns the ctree record set
* @param query string - SQL query to execute
* @param [$params] array - These can also be set in the CONFIG file with dbname_ctree,dbuser_ctree, and dbpass_ctree
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return array - returns records
*/
function ctreeQueryResults($query='',$params=array()){
	$query=trim($query);
	global $USER;
	global $dbh_ctree;
	if(!is_object($dbh_ctree)){
		$dbh_ctree=ctreeDBConnect();
	}
	if(!$dbh_ctree){
		$error=array("ctreeQueryResults Connect Error",$query);
	    debugValue($error);
	    return json_encode($error);
	}
	try{
		$data = $dbh_ctree->query($query);
		$recs = ctreeEnumQueryResults($data,$params);
		$dbh_ctree=null;
		return $recs;
	}
	catch (Exception $e) {
		$error=array("ctreeQueryResults Connect Error",$e,$query);
	    debugValue($error);
	    return json_encode($error);
	}
}
//---------- begin function ctreeEnumQueryResults ----------
/**
* @describe enumerates through the data from a ctree query
* @exclude - used for internal user only
* @param data resource
* @return array
*	returns records
*/
function ctreeEnumQueryResults($data,$params=array()){
	if(!is_object($data)){return null;}
	$header=0;
	unset($fh);
	//write to file or return a recordset?
	if(isset($params['-filename'])){
		$starttime=microtime(true);
		if(isset($params['-append'])){
			//append
    		$fh = fopen($params['-filename'],"ab");
		}
		else{
			if(file_exists($params['-filename'])){unlink($params['-filename']);}
    		$fh = fopen($params['-filename'],"wb");
		}
    	if(!isset($fh) || !is_object($fh)){
			$data=null;
			$error=array("ctreeEnumQueryResults File Open Error",$params,$query);
		    debugValue($error);
		    return json_encode($error);
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	$i=0;
	for($i=0; $row = $data->fetch(PDO::FETCH_ASSOC); $i++){
		$rec=array();
		foreach($row as $key=>$val){
			$key=strtolower($key);
			$rec[$key]=trim($val);
			$rec[$key]=preg_replace('/[\r\n]+/',' ', $rec[$key]);
			if(preg_match('/\_(id|rank)$/i',$key) && preg_match('/^([0-9]+)/',$rec[$key],$m)){
				//these are integers
				$rec[$key]=$m[1];
			}
			elseif(preg_match('/\_phone$/i',$key)){
				//remove anything but numbers, dashes, periods, and plus
				$rec[$key]=preg_replace('/[^0-9\.\-\+]/','', $rec[$key]);
			}
    	}
    	if(isset($fh) && is_object($fh)){
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
	$data=null;
	if(isset($fh) && is_object($fh)){
		@fclose($fh);
		if(isset($params['-logfile']) && file_exists($params['-logfile'])){
			$elapsed=microtime(true)-$starttime;
			appendFileContents($params['-logfile'],"Line count:{$i}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
		}
		return $i;
	}
	return $recs;
}

//---------- begin function ctreeNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function ctreeNamedQuery($name){
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
		case 'functions':
			return <<<ENDOFQUERY

ENDOFQUERY;
		break;
		case 'procedures':
			return <<<ENDOFQUERY
SELECT 
	creator,
	has_resultset,
	has_return_val,
	owner,
	proc_id,
	proc_name,
	proc_type,
	rssid
FROM admin.sysprocedures

ENDOFQUERY;
		break;
	}
}
