<?php
/*
	Show record icon if log queries is on
		set at config, database level
			log_queries=1
			log_queries_user
			log_queries_time
			log_queries_days


*/
	global $CONFIG;
	global $DATABASE;
	global $_SESSION;
	global $USER;
	global $recs;
	global $sqlpromptCaptureFirstRows_count;
	$sqlpromptCaptureFirstRows_count=0;
	global $wasql_debugValueContent;
	global $db;
	//echo "CONTROLLER".printValue($_REQUEST);exit;
	$recs=array();
	if(isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$db=$DATABASE[$_REQUEST['db']];
		if(isset($_REQUEST['schema']) && strlen($_REQUEST['schema']) && isset($DATABASE[$db['name']])){
			$db['dbschema']=$DATABASE[$db['name']]['dbschema']=$_REQUEST['schema'];
		}
		$_SESSION['db']=$db;
	}
	elseif(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
		if(isset($_REQUEST['schema']) && strlen($_REQUEST['schema']) && isset($DATABASE[$db['name']])){
			$db['dbschema']=$DATABASE[$db['name']]['dbschema']=$_REQUEST['schema'];
		}
		$_SESSION['db']=$db;
	}
	ksort($DATABASE); 
	if(!isset($db['name'])){
		$db=array(
			'name'=>$CONFIG['dbname'],
			'displayname'=>$CONFIG['dbname'],
			'dbname'=>$CONFIG['dbname'],
			'dbtype'=>$CONFIG['dbtype'],
			'dbuser'=>$CONFIG['dbuser'],
			'dbpass'=>$CONFIG['dbpass'],
			'dbhost'=>$CONFIG['dbhost']
		);
		$DATABASE[$CONFIG['dbname']]=$db;
		$CONFIG['database']=$CONFIG['dbname'];
		$_SESSION['db']=$db;
	}
	switch(strtolower($_REQUEST['func'])){
		case 'show_history':
			$db=$_REQUEST['db'];
			setView('show_history',1);
			return;
		break;
		case 'show_history_list':
			//echo printValue($_REQUEST);exit;
			$db=$_REQUEST['db'];
			setView('show_history_list',1);
			return;
		break;
		case 'toast_query':
			$recs=dbQueryResults($_REQUEST['db'],$_REQUEST['query']);
			setView('toast_query',1);
			return;
		break;
		case 'last_records':
			$table=addslashes($_REQUEST['table']);
			//echo "TABLE: {$table}";exit;
			switch(strtolower($db['dbtype'])){
				case 'memgraph':
					$sql="MATCH (n:{$table}) RETURN n LIMIT 5";
					$_SESSION['sql_last']=$sql;
				break;
				case 'mssql':
					$sql="SELECT * FROM {$table} OFFSET 0 ROWS FETCH NEXT 5 ROWS ONLY";
				break;
				case 'oracle':
					$sql="SELECT * FROM {$table} OFFSET 0 ROWS FETCH NEXT 5 ROWS ONLY";
				break;
				case 'firebird':
					$sql="SELECT * FROM {$table} OFFSET 0 ROWS FETCH NEXT 5 ROWS ONLY";
				break;
				case 'ctree':
					$sql=sqlpromptBuildTop5Ctree($db,$table);
				break;
				case 'msexcel':
				case 'mscsv':
				case 'msaccess':
					$sql="SELECT TOP 5 * FROM {$table}";
				break;
				case 'duckdb':
					loadExtras('duckdb');
					$CONFIG['db']=$db['name'];
					if(duckdbIsFileMode()){
						// Get file path - if folder mode, pass table name
						$filepath=duckdbGetDataFilePath($table);
						$readfunc=duckdbGetReadFunction($filepath);
						$escaped_path=str_replace("'","''",$filepath);
						// Get column names and create aliases for normalized names
						$schema_recs=dbQueryResults($db['name'],"DESCRIBE SELECT * FROM {$readfunc}('{$escaped_path}')");
						if(is_array($schema_recs) && count($schema_recs)){
							$cols=array();
							foreach($schema_recs as $rec){
								$original=$rec['column_name'];
								$normalized=sqlpromptNormalizeColumnName($original);
								// If only difference is case, just use lowercase (DuckDB is case-insensitive)
								if(strtolower($original)==$normalized){
									$cols[]="\t{$normalized}";
								}
								else{
									// Has spaces or special chars - need alias
									$quoted_orig=$original;
									if(preg_match('/[^a-z0-9_]/i',$original)){
										$quoted_orig="\"{$original}\"";
									}
									$cols[]="\t{$quoted_orig} AS {$normalized}";
								}
							}
							$sql="SELECT\n".implode(",\n",$cols)."\nFROM {$readfunc}('{$escaped_path}')\nLIMIT 5";
						}
						else{
							$sql="SELECT * FROM {$readfunc}('{$escaped_path}') LIMIT 5";
						}
					}
					else{
						$sql="SELECT * FROM {$table} LIMIT 5";
					}
				break;
				case 'postgresql':
				case 'postgres':
					if(strlen($db['dbschema'])){
						$table="{$db['dbschema']}.{$table}";
					}
					$sql="SELECT * FROM {$table} LIMIT 5";
				break;
				case 'gigya':
					$sql="SELECT * FROM {$table} LIMIT 5";
				break;
				case 'splunk':
					$sql="SELECT * FROM {$table} LIMIT 5";
				break;
				case 'ccv2':
					$sql="SELECT TOP 5 * FROM {$table}";
				break;
				case 'elastic':
					$sql="SELECT * FROM {$table} LIMIT 5";
				break;
				default:
					$sql="SELECT * FROM {$table} LIMIT 5";
				break;
			}
			setView('monitor_sql',1);
			return;
		break;
		case 'list_records':
			$table=addslashes($_REQUEST['table']);
			setView('list_records',1);
			return;
		break;
		case 'count_records':
			$table=addslashes($_REQUEST['table']);
			switch(strtolower($db['dbtype'])){
				case 'memgraph':
					$sql="MATCH (n:{$table}) RETURN count(n) AS cnt";
					$_SESSION['sql_last']=$sql;
				break;
				case 'ctree':
					$sql="SELECT COUNT(*) AS cnt FROM admin.{$table}";
				break;
				case 'duckdb':
					loadExtras('duckdb');
					$CONFIG['db']=$db['name'];
					if(duckdbIsFileMode()){
						// Get file path - if folder mode, pass table name
						$filepath=duckdbGetDataFilePath($table);
						$readfunc=duckdbGetReadFunction($filepath);
						$escaped_path=str_replace("'","''",$filepath);
						$sql="SELECT COUNT(*) AS cnt FROM {$readfunc}('{$escaped_path}')";
					}
					else{
						$sql="SELECT COUNT(*) AS cnt FROM {$table}";
					}
				break;
				case 'mysql':
				case 'mysqli':
					$sql="SELECT COUNT(*) AS cnt FROM {$table}";
				break;
				default:
					$parts=preg_split('/\./',$table,2);
					if(count($parts)==2){
						$schema=$parts[0];
						$table=$parts[1];
					}
					else{
						$schema=$db['dbschema'];
					}

					if(strlen($schema)){
						$table="{$schema}.{$table}";
					}
					$sql="SELECT COUNT(*) AS cnt FROM {$table}";
				break;
			}
			setView('monitor_sql',1);
			return;
		break;
		case 'ddl':
			$table=addslashes($_REQUEST['table']);
			$db=$_REQUEST['db'];
			if(strtolower($DATABASE[$db]['dbtype'])=='duckdb'){
				loadExtras('duckdb');
				$CONFIG['db']=$db;
				if(duckdbIsFileMode()){
					// Get file path - if folder mode, pass table name
					$filepath=duckdbGetDataFilePath($table);
					$readfunc=duckdbGetReadFunction($filepath);
					$escaped_path=str_replace("'","''",$filepath);
					// Get schema information
					$schema_recs=dbQueryResults($db,"DESCRIBE SELECT * FROM {$readfunc}('{$escaped_path}')");
					if(is_array($schema_recs) && count($schema_recs)){
						// Generate CREATE TABLE statement
						$tablename=getFileName($filepath,1); // filename without extension
						$lines=array();
						$lines[]="-- Schema for: {$filepath}";
						$lines[]="-- Format: ".strtoupper(getFileExtension($filepath));
						$lines[]="-- Note: Use this CREATE TABLE statement in your target database (MySQL, PostgreSQL, etc.) to prepare for importing this file's data";
						$lines[]="";
						$lines[]="CREATE TABLE {$tablename} (";
						$cols=array();
						foreach($schema_recs as $rec){
							$colname=$rec['column_name'];
							$coltype=$rec['column_type'];
							// Normalize column name (lowercase, underscores)
							$colname=sqlpromptNormalizeColumnName($colname);
							// Convert DuckDB types to more generic SQL types
							$coltype=preg_replace('/^BIGINT$/i','BIGINT',$coltype);
							$coltype=preg_replace('/^INTEGER$/i','INTEGER',$coltype);
							$coltype=preg_replace('/^DOUBLE$/i','DOUBLE PRECISION',$coltype);
							$coltype=preg_replace('/^TIMESTAMP$/i','TIMESTAMP',$coltype);
							$coltype=preg_replace('/^DATE$/i','DATE',$coltype);
							$coltype=preg_replace('/^BOOLEAN$/i','BOOLEAN',$coltype);
							$coltype=preg_replace('/^VARCHAR$/i','VARCHAR(255)',$coltype);
							// Quote column names that might be reserved words
							if(in_array($colname,array('index','order','group','user','date','time','key','value','type','name'))){
								$colname="`{$colname}`";
							}
							$cols[]="\t{$colname} {$coltype}";
						}
						$lines[]=implode(",\n",$cols);
						$lines[]=");";
						$sql=implode("\n",$lines);
					}
					else{
						$sql="-- Unable to retrieve schema for: {$filepath}";
					}
					setView('monitor_sql_norun',1);
					return;
				}
			}
			$parts=preg_split('/\./',$table,2);
			if(count($parts)==2){
				$sql=dbGetTableDDL($db,$parts[1],$parts[0]);
			}
			else{
				$sql=dbGetTableDDL($db,$table);
			}
			if(is_array($content)){
				$sql=printValue($content);
			}
			setView('monitor_sql_norun',1);
			return;
		break;
		case 'desc':
			$table=addslashes($_REQUEST['table']);
			$db=$_REQUEST['db'];
			if(strtolower($DATABASE[$db]['dbtype'])=='duckdb'){
				loadExtras('duckdb');
				$CONFIG['db']=$db;
				if(duckdbIsFileMode()){
					// Get file path - if folder mode, pass table name
					$filepath=duckdbGetDataFilePath($table);
					$readfunc=duckdbGetReadFunction($filepath);
					$escaped_path=str_replace("'","''",$filepath);
					// Get column names and create aliases for normalized names
					$schema_recs=dbQueryResults($db,"DESCRIBE SELECT * FROM {$readfunc}('{$escaped_path}')");
					if(is_array($schema_recs) && count($schema_recs)){
						$cols=array();
						foreach($schema_recs as $rec){
							$original=$rec['column_name'];
							$normalized=sqlpromptNormalizeColumnName($original);
							// If only difference is case, just use lowercase (DuckDB is case-insensitive)
							if(strtolower($original)==$normalized){
								$cols[]="\t{$normalized}";
							}
							else{
								// Has spaces or special chars - need alias
								$quoted_orig=$original;
								if(preg_match('/[^a-z0-9_]/i',$original)){
									$quoted_orig="\"{$original}\"";
								}
								$cols[]="\t{$quoted_orig} AS {$normalized}";
							}
						}
						$sql="DESCRIBE\nSELECT\n".implode(",\n",$cols)."\nFROM {$readfunc}('{$escaped_path}')";
					}
					else{
						$sql="DESCRIBE SELECT * FROM {$readfunc}('{$escaped_path}')";
					}
					setView('monitor_sql',1);
					return;
				}
			}
			$parts=preg_split('/\./',$table,2);
			if(count($parts)==2){
				$sql="desc {$table}";
			}
			else{
				$sql="desc {$table}";
			}
			setView('monitor_sql',1);
			return;
		break;
		case 'monitor':
			switch(strtolower($_REQUEST['type'])){
				case 'optimizations':
					$listopts=array(
						'priority_class'=>'w_nowrap',
						'advice_style'=>'white-space: unset;',
						'details_style'=>'white-space: unset;',
					);
					$recs=dbOptimizations($_REQUEST['db']);
					setView('showlist',1);
					return;
				break;
			}
			$sql=sqlpromptBuildQuery($_REQUEST['db'],$_REQUEST['type']);
			setView('monitor_sql',1);
			return;
		break;
		case 'setdb':
			$tables=sqlpromptGetTables($db['name']);
			//echo "setdb".printValue($tables);exit;
			setView(array('tables_fields','prompt_load'),1);
			return;
		break;
		case 'load_prompt':
			$prompt='sql_prompt_'.$_REQUEST['db'];
			$rec=getDBRecord(array('-table'=>'_prompts','_cuser'=>$USER['_id'],'name'=>$prompt));
			if(isset($rec['_id'])){
				$load_prompt=$rec['body'];
			}
			else{
				$load_prompt='';
			}
			setView('load_prompt',1);
			return;
		break;
		case 'paginate':
			$tpath=getWasqlPath('php/temp');
			if(isset($_REQUEST['sql_sha']) && strlen($_REQUEST['sql_sha'])){
				$shastr=$_REQUEST['sql_sha'];
				$recs_count=$_REQUEST['sql_cnt'];
				//echo "REQ: {$shastr}";exit;
			}
			elseif(isset($_SESSION['sql_last']) && strlen($_SESSION['sql_last'])){
				$shastr=$_SESSION['sql_last'];
				$recs_count=$_SESSION['sql_last_count'];
			}
			else{
				$lastquery['error']='Unable to find cached csv file for this query';
				setView('error',1);
				return;
			}
			$uid=isset($USER['_id'])?$USER['_id']:'unknown';
			$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
			$afile="{$tpath}/{$filename}";
			if(!file_exists($afile)){
				$lastquery['error']="Missing cached file. Unable to paginate. Try running the query again.<br><br>{$afile}";
				setView('error',1);
				return;
			}
			$begin=microtime(true);
			$offset=(integer)$_REQUEST['offset'];
			$limit=30;
			$recs=getCSVRecords($afile,array(
				'-start'=>$offset,
				'-maxrows'=>$limit
			));
			$qtime=microtime(true)-$begin;
			if($qtime < 1){
				$qtime_verbose=number_format($qtime,3).' seconds';
			}
			else{
				$qtime_verbose=verboseTime($qtime);
			}
			$listopts=array();
			if(stringContains($_SESSION['sql_last'],'listopts:')){
				$lines=preg_split('/[\r\n]+/',trim($_SESSION['sql_last']));
				foreach($lines as $line){
					if(!stringBeginsWith('-- listopts:')){continue;}
					$line=preg_replace('/^\-\-\ listopts\:/','',trim($line));
					$sets=preg_split('/[\;]/',$line);
					foreach($sets as $set){
						list($k,$v)=preg_split('/\=/',$set,2);
						if(stringEndsWith($k,'_options')){$v=decodeJSON($v);}
						$listopts[$k]=$v;
					}
				}
			}
			setView(array('results','success'),1);
			return;
		break;
		case 'explain':
			$view='block_results';
			$_SESSION['sql_full']=$_REQUEST['sql_full'];
			$sql_select=stripslashes($_REQUEST['sql_select']);
			$sql_full=stripslashes($_REQUEST['sql_full']);
			if(strlen($sql_select) && $sql_select != $sql_full){
				$_SESSION['sql_last']=$sql_select;
			}
			else{
				$_SESSION['sql_last']=$sql_full;
			}
			switch(strtolower($db['dbtype'])){
				case 'hana':
					$stmt_name='hep_'.encodeBase64(microtime(true));
					$sql="EXPLAIN PLAN SET statement_name='{$stmt_name}' FOR ".PHP_EOL.$_SESSION['sql_last'];
					$recs=dbQueryResults($db['name'],$sql);
					$sql="SELECT * FROM sys.explain_plan_table WHERE statement_name='{$stmt_name}'";
					$recs=dbQueryResults($db['name'],$sql);
					foreach($recs as $i=>$rec){
						unset($recs[$i]['statement_name']);
					}
				break;
				case 'mysql':
				case 'mysqli':
				case 'postgres':
					$sql="EXPLAIN".PHP_EOL.$_SESSION['sql_last'];
					//echo $sql;exit;
					$recs=dbQueryResults($db['name'],$sql);
				break;
				case 'snowflake':
					$sql="EXPLAIN USING TABULAR".PHP_EOL.$_SESSION['sql_last'];
					//echo $sql;exit;
					$recs=dbQueryResults($db['name'],$sql);
				break;
				case 'ctree':
					//EXPLAIN PLAN FOR <SQL statement>
					$sql="EXPLAIN PLAN FOR ".$_SESSION['sql_last'];
					//echo $sql;exit;
					$recs=dbQueryResults($db['name'],$sql);
				break;
				case 'sqlite':
					$sql="EXPLAIN QUERY PLAN".PHP_EOL.$_SESSION['sql_last'];
					//echo $sql;exit;
					$recs=dbQueryResults($db['name'],$sql);
				break;
				case 'duckdb':
					$sql="EXPLAIN".PHP_EOL.$_SESSION['sql_last'];
					//echo $sql;exit;
					$recs=dbQueryResults($db['name'],$sql);
				break;
				case 'mssql':
					$sql=str_replace("'","''",$_SESSION['sql_last']);
					$sql = "EXEC sp_executesql N'SET STATISTICS PROFILE ON; {$sql}; SET STATISTICS PROFILE OFF'";
					$recs=dbQueryResults($db['name'],$sql);
				break;
				case 'oracle':
					$stmt_id='oep_'.encodeBase64(microtime(true));
					$sql="EXPLAIN PLAN SET statement_id='{$stmt_id}' INTO plan_table FOR".PHP_EOL.$_SESSION['sql_last'];
					$recs=dbQueryResults($db['name'],$sql);
					$sql=<<<ENDOFQUERY
SELECT id, LPAD(' ',2*(LEVEL-1))||operation operation, options,
   object_name, object_alias, position 
FROM plan_table 
START WITH id = 0 AND statement_id = '{$stmt_id}'
CONNECT BY PRIOR id = parent_id AND statement_id = '{$stmt_id}'
ORDER BY id
ENDOFQUERY;
					$recs=dbQueryResults($db['name'],$sql);
				break;
				default:
					echo "EXPLAIN Plans are not yet supported for {$db['dbtype']} yet.";exit;
				break;
			}
			setView(array('results','success'),1);
			return;
		break;
		case 'sql':
			$view='block_results';
			$_SESSION['sql_full']=$_REQUEST['sql_full'];
			$sql_select=stripslashes($_REQUEST['sql_select']);
			$sql_full=stripslashes($_REQUEST['sql_full']);
			if(strlen($sql_full)){
				$prompt='sql_prompt_'.$db['name'];
				$editor_content=$_REQUEST['editor_content'];
				$ok=addDBRecord(array(
					'-table'=>'_prompts',
					'name'=>$prompt,
					'body'=>$editor_content,
					'-upsert'=>'body'
				));
				if(isset($rec['_id'])){
					$_SESSION['sql_full']=$rec['body'];
				}
			}
			
			if(strlen($sql_select) && $sql_select != $sql_full){
				$_SESSION['sql_last']=$sql_select;
				$view='block_results';
			}
			else{
				$_SESSION['sql_last']=$sql_full;
				//run the query where the cursor position is
				$queries=preg_split('/\;/',$sql_full);
				//echo printValue($queries);exit;
				$cpos=$_REQUEST['cursor_pos'];
				if(count($queries) > 1){
					$p=0;
					foreach($queries as $query){
						$end=$p+strlen($query);
						if($cpos > $p && $cpos < $end){
							$_SESSION['sql_last']=$query;
							$view='block_results';
							break;
						}
						$p=$end;
					}
				}
				else{
					$_SESSION['sql_last']=$sql_full;
					$view='results';
				}
			}
			$afile='';
			$nrecs=sqlpromptNamedQueries();
			$names=array();
			foreach($nrecs as $n=>$nrec){
				unset($nrecs[$n]['icon']);
				$k=strtolower(trim($nrec['name']));
				$names[$k]=1;
				$k=strtolower(trim($nrec['code']));
				$names[$k]=1;
				if($k=='running_queries'){
					$names['running']=1;
					$names['queries']=1;
				}
			}
			
			
			
			//echo printValue($nrecs);exit;
			//Run the Query
			//if this is code and
			if(preg_match('/^php\>(.+)/is',trim($_SESSION['sql_last']),$m)){
				if(!isAdmin()){
					echo "You must have admin rights to execute php on this server";
					exit;
				}
				$str='<?'.'php'.PHP_EOL.$m[1].PHP_EOL.'?'.'>';
				echo evalPHP($str);
				exit;
			}
			elseif(preg_match('/^py\>(.+)/is',trim($_SESSION['sql_last']),$m)){
				if(!isAdmin()){
					echo "You must have admin rights to execute python on this server";
					exit;
				}
				$str='<?'.'py'.PHP_EOL.$m[1].PHP_EOL.'?'.'>';
				echo evalPHP($str);
				exit;
			}
			elseif(preg_match('/^json\>(.+)/is',trim($_SESSION['sql_last']),$m)){
				$arr=decodeJSON($m[1]);
				echo printValue($arr);
				exit;
			}
			elseif(preg_match('/^cmd\>(.+)/',trim($_SESSION['sql_last']),$m)){
				if(!isAdmin()){
					echo "You must have admin rights to execute commands on this server";
					exit;
				}
				$out=cmdResults($m[1]);
				echo "CMD: {$out['cmd']}, DIR: {$out['cmd']}, RUNTIME: {$out['runtime']}, RTNCODE: {$out['rtncode']}".PHP_EOL;
				echo "=========================================================================================".PHP_EOL;
				if(isset($out['stdout'])){
					echo $out['stdout'].PHP_EOL;
				}
				if(isset($out['stderr'])){
					echo $out['stderr'].PHP_EOL;
				}
				exit;
			}
			$skip=0;
			$lcq=strtolower(trim($_SESSION['sql_last']));
			switch($lcq){
				case 'help':
				case 'commands':
					$recs=array(
						array(
							'command'=>'help',
							'description'=>'Display this help menu'
						),
						array(
							'command'=>'commands',
							'description'=>'show only commands without descriptions'
						),
						array(
							'command'=>'history',
							'description'=>'show history of commands you have ran'
						),
						array(
							'command'=>'db',
							'description'=>'Display database connection info'
						),
						array(
							'command'=>'versions',
							'description'=>'Show software versions on server'
						),
						array(
							'command'=>'grade {query}',
							'description'=>'Grades selected query for correct format and returns the grade'
						),
						array(
							'command'=>'ddl {tablename}',
							'description'=>'Returns DDL (create statement) for specified table'
						),
						array(
							'command'=>'tables [{filter}]',
							'description'=>'Returns tables [filtered by filter]'
						),
						array(
							'command'=>'fields (fld) {tablename} [{filter}]',
							'description'=>'Returns fields for specified table  [filtered by filter]'
						),
						array(
							'command'=>'idx {tablename}',
							'description'=>'Returns indexes for specified table'
						),
						array(
							'command'=>'calc>{math expression}',
							'description'=>'Returns the value of a math expression'
						),
						array(
							'command'=>'cal [YearMonth]',
							'description'=>'Returns a calendar'
						)
					);
					foreach($nrecs as $nrec){
						$recs[]=array(
							'command'=>$nrec['code'],
							'description'=>$nrec['name']
						);
					}
					if(isAdmin()){
						$recs[]=array(
							'command'=>'kill {sessionID}',
							'description'=>"kills the query session specified"
						);
						$recs[]=array(
							'command'=>'php>{PHP code}',
							'description'=>"Admins only: run PHP code on the server and return the results"
						);
						$recs[]=array(
							'command'=>'py>{python code}',
							'description'=>"Admins only: run python code on the server and return the results"
						);
						$recs[]=array(
							'command'=>'cmd>{some command}',
							'description'=>"Admins only: run command on the server and return the results"
						);
						$recs[]=array(
							'command'=>'drives (df)',
							'description'=>"Admins only: show server drive info"
						);
						$recs[]=array(
							'command'=>'uptime (top)',
							'description'=>"Admins only: show server uptime and load"
						);
						$recs[]=array(
							'command'=>'memory (mem)',
							'description'=>"Admins only: show server memory"
						);
						$recs[]=array(
							'command'=>'server (os)',
							'description'=>"Admins only: show server os info"
						);
						$recs[]=array(
							'command'=>'processes (ps)',
							'description'=>"Admins only: show server drive info"
						);
					}
					if($lcq=='commands'){
						foreach($recs as $i=>$rec){
							unset($recs[$i]['description']);
						}
					}
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
				case 'versions':
					$recs=array();
					$versions=getAllVersions();
					foreach($versions as $k=>$v){
						if(in_array($k,array('curl_version'))){continue;}
						$recs[]=array(
							'name'=>$k,
							'version'=>$v
						);
					}
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
				case 'last':
				case 'last_query':
					$rec=getDBRecord(array('-table'=>'_queries','_cuser'=>$USER['_id'],'function_name'=>'dasql_prompt','-fields'=>'_id,query'));
					if(isset($rec['_id'])){
						echo $rec['query'];
						exit;
					}
					echo "No last query found for {$USER['_id']}";
					exit;
				break;
				case 'history':
					$recopts=array(
						'-table'=>'_queries',
						'user_id'=>$USER['_id'],
						'-fields'=>'_id,_cdate,query,row_count,run_length',
						'-order'=>'_id desc',
						'-limit'=>100,
						'-notimestamp'=>1
					);
					if(isset($_REQUEST['db'])){
						$recopts['tablename']="DB:{$_REQUEST['db']}";
					}
					//echo printValue($_REQUEST);exit;
					$recs=getDBRecords($recopts);
					if(is_array($recs)){
						foreach($recs as $i=>$rec){
							$recs[$i]['query']='<div class="w_small w_pre">'.$rec['query'].'</div>';
						}
					}
					$recs=sortArrayByKeys($recs,array('_id'=>SORT_DESC));
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
				case 'uptime':
				case 'top':
					if(!isAdmin()){
						echo "You must have admin rights to show uptime on this server";
						exit;
					}
					loadExtras('system');
					$recs=array();
					$info=getServerUptime();
					foreach($info as $k=>$v){
						$recs[]=array(
							'name'=>$k,
							'value'=>$v
						);
					}
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
				case 'drives':
				case 'df':
					if(!isAdmin()){
						echo "You must have admin rights to show drive info on this server";
						exit;
					}
					loadExtras('system');
					$recs=systemGetDriveSpace(1);
					foreach($recs as $i=>$rec){
						if(isset($rec['size'])){$recs[$i]['size']=verboseSize($rec['size']);}
						if(isset($rec['used'])){$recs[$i]['used']=verboseSize($rec['used']);}
						if(isset($rec['available'])){$recs[$i]['available']=verboseSize($rec['available']);}
					}
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
				case 'memory':
				case 'mem':
					if(!isAdmin()){
						echo "You must have admin rights to show memory info on this server";
						exit;
					}
					loadExtras('system');
					$rec=systemGetMemory();
					$recs=array($rec);
					foreach($recs as $i=>$rec){
						if(isset($rec['total'])){$recs[$i]['total']=verboseSize($rec['total']);}
						if(isset($rec['free'])){$recs[$i]['free']=verboseSize($rec['free']);}
						if(isset($rec['used'])){$recs[$i]['used']=verboseSize($rec['used']);}
					}
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
				case 'server':
				case 'os':
					if(!isAdmin()){
						echo "You must have admin rights to show OS info on this server";
						exit;
					}
					loadExtras('system');
					$recs=systemGetOSInfo();
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
				case 'ps':
				case 'processes':
					if(!isAdmin()){
						echo "You must have admin rights to show processes on this server";
						exit;
					}
					loadExtras('system');
					$recs=systemGetProcessList();
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
				case 'db':
					$recs=array();
					foreach($db as $k=>$v){
						if(strtolower($k)=='dbpass'){
							$v=str_repeat('*',strlen($v));
						}
						$recs[]=array('name'=>$k,'value'=>$v);
					}
					$csv=arrays2CSV($recs);
					$tpath=getWasqlPath('php/temp');
					$shastr=sha1($_SESSION['sql_last']);
					$uid=isset($USER['_id'])?$USER['_id']:'unknown';
					$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
					$afile="{$tpath}/{$filename}";
					if(is_file($afile)){unlink($afile);}
					$ok=setFileContents($afile,$csv);
					$skip=1;
					$recs_count=count($recs);
				break;
			}
			if($skip==0 && preg_match('/^ddl\ (.+)$/is',$lcq,$m)){
				$parts=preg_split('/\./',$m[1],2);
				if(count($parts)==2){
					$sql=dbGetTableDDL($db['name'],$parts[1],$parts[0]);
				}
				else{
					$sql=dbGetTableDDL($db['name'],$m[1]);
				}
				if(is_array($sql)){echo printValue($sql);}
				else{
					$sql=sqlpromtFormatSQL($sql);
					if(!isset($_REQUEST['format'])){$sql="<pre style=\"font-size:0.8rem;margin-top:15px;\">{$sql}</pre>";}
					echo $sql;
				}
				exit;
			}
			elseif($skip==0 && preg_match('/^tables(.*)$/is',$lcq,$m)){
				$recs=dbQueryResults($db['name'],$lcq);
				$csv=arrays2CSV($recs);
				$tpath=getWasqlPath('php/temp');
				$shastr=sha1($_SESSION['sql_last']);
				$uid=isset($USER['_id'])?$USER['_id']:'unknown';
				$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
				$afile="{$tpath}/{$filename}";
				if(is_file($afile)){unlink($afile);}
				$ok=setFileContents($afile,$csv);
				$skip=1;
				$recs_count=count($recs);
			}
			elseif($skip==0 && preg_match('/^(calc|math)\>(.*)$/is',$lcq,$m)){
				$ok=loadExtras('evalmath.class');
				$recs=array();
				$em = new EvalMath;
				$em->suppress_errors = true;
				$recs[]=array(
					'expression'=>$m[2],
					'result'=>$em->evaluate($m[2])
				);
				$csv=arrays2CSV($recs);
				$tpath=getWasqlPath('php/temp');
				$shastr=sha1($_SESSION['sql_last']);
				$uid=isset($USER['_id'])?$USER['_id']:'unknown';
				$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
				$afile="{$tpath}/{$filename}";
				if(is_file($afile)){unlink($afile);}
				$ok=setFileContents($afile,$csv);
				$skip=1;
				$recs_count=count($recs);
			}
			elseif($skip==0 && preg_match('/^cal\ (.*)$/is',$lcq,$m)){
				/*
					cal - current month
					cal -3  last, current, next month
					cal -y  full year 
				*/
				$calendar=getCalendar(trim($m[1]));
				//echo printValue($calendar);exit;
				$days=$calendar['daynames']['med'];
				foreach($calendar['weeks'] as $week){
					$rec=array();
					foreach($days as $d=>$dayname){
						$v=$week[$d]['day'];
						$rec[$dayname]=$v;
					}
					$recs[]=$rec;
				}
				//echo printValue($recs);exit;
				$csv=arrays2CSV($recs);
				$tpath=getWasqlPath('php/temp');
				$shastr=sha1($_SESSION['sql_last']);
				$uid=isset($USER['_id'])?$USER['_id']:'unknown';
				$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
				$afile="{$tpath}/{$filename}";
				if(is_file($afile)){unlink($afile);}
				$ok=setFileContents($afile,$csv);
				$skip=1;
				$recs_count=count($recs);
			}
			elseif($skip==0 && preg_match('/^kill\ (.+)$/is',$lcq,$m)){
				$_SESSION['sql_last']=sqlpromptBuildQuery($db['name'],'kill',$m[1]);
			}
			elseif($skip==0 && preg_match('/^(fields|fld)\ (.+)$/is',$lcq,$m)){
				$recs=dbQueryResults($db['name'],$lcq);
				$csv=arrays2CSV($recs);
				$tpath=getWasqlPath('php/temp');
				$shastr=sha1($_SESSION['sql_last']);
				$uid=isset($USER['_id'])?$USER['_id']:'unknown';
				$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
				$afile="{$tpath}/{$filename}";
				if(is_file($afile)){unlink($afile);}
				$ok=setFileContents($afile,$csv);
				$skip=1;
				$recs_count=count($recs);
			}
			elseif($skip==0 && preg_match('/^idx\ (.+)$/is',$lcq,$m)){
				//echo "Get Indexes of {$m[1]}, skip:{$skip}, lcq: {$lcq}";exit;
				$recs=dbQueryResults($db['name'],$lcq);
				$csv=arrays2CSV($recs);
				$tpath=getWasqlPath('php/temp');
				$shastr=sha1($_SESSION['sql_last']);
				$uid=isset($USER['_id'])?$USER['_id']:'unknown';
				$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
				$afile="{$tpath}/{$filename}";
				if(is_file($afile)){unlink($afile);}
				$ok=setFileContents($afile,$csv);
				$skip=1;
				$recs_count=count($recs);
			}
			elseif($skip==0 && preg_match('/^grade(.+)$/is',$lcq,$m)){
				$_SESSION['sql_last']=preg_replace('/^grade/is','',trim($_SESSION['sql_last']));
				$grade=databaseGradeSQL($_SESSION['sql_last'],0);
				echo "Grade: {$grade}%";exit;
			}
			elseif($skip==0 && preg_match('/^format(.+)$/is',$lcq,$m)){
				$_SESSION['sql_last']=preg_replace('/^format/is','',trim($_SESSION['sql_last']));
				$sql=sqlpromtFormatSQL($_SESSION['sql_last']);
				if(!isset($_REQUEST['format'])){$sql="<pre style=\"font-size:0.8rem;margin-top:15px;\">{$sql}</pre>";}
				echo $sql;exit;
			}
			elseif($skip==0 && isset($names[$lcq])){
				$_SESSION['sql_last']=sqlpromptBuildQuery($db['name'],$lcq);
			}
			if($skip==1 && $recs_count==0){
				if($recs_count==0){
					$recs=array();
					if(isset($_REQUEST['format'])){
						switch(strtolower($_REQUEST['format'])){
							case 'json':
								echo encodeJson("{\"result\":\"no results\"}");
								exit;
							break;
							case 'xml':
								echo arrays2XML(array("{\"result\":\"no results\"}"));
								exit;
							break;
							case 'table':
								setView('no_results_table',1);
								return;
							break;
							case 'html':
								setView('no_results_html',1);
								return;
							break;
							case 'csv':
							case 'dos':
								setView('no_results_dos',1);
								return;
							break;
						}
					}
					else{
						setView('no_results',1);
						return;
					}
					
				}
			}
			if($skip==0 && isset($_REQUEST['py']) && $_REQUEST['py']==1){
				$qstart=microtime(true);
				$afile=pyQueryResults($db['name'],$_SESSION['sql_last'],array('-csv'=>1));
				$qstop=microtime(true);
				$lastquery=array();
				$lastquery['time']=round(($qstop-$qstart),3);
				if(!is_file($afile)){
					$error=$afile;
					setView(array('error'),1);
					return;
				}
				$shastr=sha1($_SESSION['sql_last']);
				$uid=isset($USER['_id'])?$USER['_id']:'unknown';
				$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
				$tpath=getWasqlPath('php/temp');
				if(rename($afile,"{$tpath}/{$filename}")){
					$afile="{$tpath}/{$filename}";
				}
				$recs_count=getFileLineCount($afile)-1;
				//echo $afile;exit;
			}
			elseif($skip==0){
				$tpath=getWasqlPath('php/temp');
				$shastr=sha1($_SESSION['sql_last']);
				$uid=isset($USER['_id'])?$USER['_id']:'unknown';
				$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
				$logname="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.log";
				$afile="{$tpath}/{$filename}";
				$lfile="{$tpath}/{$logname}";
				if(is_file($afile)){unlink($afile);}
				if(is_file($lfile)){unlink($lfile);}
				$params=array(
					//'-binmode'=>ODBC_BINMODE_PASSTHRU,
					'-longreadlen'=>131027,
					'-filename'=>$afile,
					'-logfile'=>$lfile,
					//'-cursor'=>SQL_CUR_USE_ODBC,
					'-query'=>$_SESSION['sql_last'],
					//'-process'=>'sqlpromptCaptureFirstRows'
					'-ignore_case'=>1
				);

				$grade=databaseGradeSQL($params['-query'],0);
				//echo $grade.PHP_EOL;
				$recs_show=30;
				$recs=array();
				$qstart=microtime(true);
				$_SESSION['debugValue_lastm']=array();
				//echo printValue($params);exit;
				$recs_count=$_SESSION['sql_last_count']=dbGetRecords($db['name'],$params);
				$qstop=microtime(true);
				

				$lastquery=dbGetLast();

				if(!is_array($lastquery)){$lastquery=array();}
				$lastquery['time']=round(($qstop-$qstart),3);
				if(is_array($_SESSION['debugValue_lastm'])){$_SESSION['debugValue_lastm']=encodeJson($_SESSION['debugValue_lastm']);}
				if(strlen($_SESSION['debugValue_lastm']) && $_SESSION['debugValue_lastm'] != '[]'){
					$lastquery['error']=$_SESSION['debugValue_lastm'];
				}
				//echo printValue($lastquery);exit;
				if(isset($lastquery['error']) && strlen($lastquery['error'])){
					if(isset($_REQUEST['format'])){
						switch(strtolower($_REQUEST['format'])){
							case 'json':
								echo encodeJson($lastquery);
								exit;
							break;
							case 'xml':
								echo arrays2XML(array($lastquery));
								exit;
							break;
							case 'table':
								echo databaseListRecords(array(
									'-list'=>array($lastquery),
									'-hidesearch'=>1,
									'-tableclass'=>'wacss_table striped bordered condensed'
								));
								exit;
							break;
							case 'html':
								echo sqlpromptHTMLHead();
								echo databaseListRecords(array(
									'-list'=>array($lastquery),
									'-hidesearch'=>1,
									'-tableclass'=>'wacss_table striped bordered condensed'
								));
								echo '</div>'.PHP_EOL;
								echo '</body>'.PHP_EOL;
								echo '</html>'.PHP_EOL;
								exit;
							break;
							case 'csv':
							case 'dos':
								if(!is_string($lastquery['error'])){
									$lastquery['error']=encodeJson($lastquery['error']);
								}
								echo $lastquery['error'];
								exit;
							break;
						}
					}
					else{
						if(!is_string($lastquery['error'])){
							$lastquery['error']=encodeJson($lastquery['error']);
						}
						if(strlen($lastquery['error'])){
							setView(array('error'),1);
							return;
						}
					}
				}
				if($recs_count==0){
					$recs=array();
					if(isset($_REQUEST['format'])){
						switch(strtolower($_REQUEST['format'])){
							case 'json':
								echo encodeJson("{\"result\":\"no results\"}");
								exit;
							break;
							case 'xml':
								echo arrays2XML(array("{\"result\":\"no results\"}"));
								exit;
							break;
							case 'table':
								setView('no_results_table',1);
								return;
							break;
							case 'html':
								setView('no_results_html',1);
								return;
							break;
							case 'csv':
							case 'dos':
								setView('no_results_dos',1);
								return;
							break;
						}
					}
					else{
						setView('no_results',1);
						return;
					}
					
				}
			}
			if(isset($_REQUEST['format'])){
				$opts=array(
					'-table'=>'_queries',
					'page_id'=>0,
					'query'=>$_SESSION['sql_last'],
					'function_name'=>'dasql_prompt',
					'user_id'=>$USER['_id'],
					'tablename'=>"DB:{$db['name']}",
				);
				$id=addDBRecord($opts);
				//echo printValue($id).printValue($opts);exit;
				switch(strtolower($_REQUEST['format'])){
					case 'json':
						echo encodeJson(getCSVRecords($afile));
						exit;
					break;
					case 'xml':
						echo arrays2XML(getCSVRecords($afile));
						exit;
					break;
					case 'table':
						echo databaseListRecords(array(
							'-csv'=>$afile,
							'-hidesearch'=>1,
							'-tableclass'=>'wacss_table striped bordered condensed'
						));
						exit;
					break;
					case 'html':
						echo sqlpromptHTMLHead();
						echo databaseListRecords(array(
							'-csv'=>$afile,
							'-hidesearch'=>1,
							'-tableclass'=>'wacss_table striped bordered condensed'
						));
						echo '</div>'.PHP_EOL;
						echo '</body>'.PHP_EOL;
						echo '</html>'.PHP_EOL;
						exit;
					break;
					case 'csv':
						readfile($afile);
						exit;
					break;
					case 'dos':
						$recs=getCSVRecords($afile);
						$maxlengths=array();
						foreach($recs as $i=>$rec){
							foreach($rec as $k=>$v){
								if(!isset($maxlengths[$k])){
									$maxlengths[$k]=strlen($k);
								}
								if(strlen($k) > $maxlengths[$k]){
									$maxlengths[$k]=strlen($k);
								}
								if(strlen($v) > $maxlengths[$k]){
									$maxlengths[$k]=strlen($v);
								}
							}
						}
						$fields=array_keys($maxlengths);
						$tlen=array_sum($maxlengths)+count($maxlengths)*3;
						$vals=array();
						foreach($fields as $k){
							$v=str_pad($k,$maxlengths[$k]);
							$vals[]=$v;
						}
						echo implode(' | ',$vals).PHP_EOL;
						echo str_repeat('=',$tlen).PHP_EOL;
						foreach($recs as $i=>$rec){
							$vals=array();
							foreach($rec as $k=>$v){
								$v=str_pad($v,$maxlengths[$k]);
								$vals[]=$v;
							}
							echo implode(' | ',$vals).PHP_EOL;
						}
						exit;
					break;
				}
			}
			$qtime=isset($lastquery['time'])?$lastquery['time']:0;
			$offset=(integer)$_REQUEST['offset'];
			$limit=30;
			$next=$offset+$limit;
			$recs=getCSVRecords($afile,array(
				'-start'=>$offset,
				'-maxrows'=>$limit
			));
			//unlink($afile);
			//echo $afile.printValue($recs);exit;
			
			/* log queries? */
			if(isset($CONFIG['log_queries']) && $CONFIG['log_queries']==1 && isset($recs[0]) && is_array($recs[0])){
				$log=1;
				//log_queries_user?
				if(isset($CONFIG['log_queries_user']) && strlen($CONFIG['log_queries_user'])){
					if(!isset($USER['_id'])){$log=0;}
					$query_users=preg_split('/[\r\n\s\,\;]+/',strtolower($CONFIG['log_queries_user']));
					if(!in_array($USER['_id'],$query_users) || !in_array($USER['username'],$query_users)){$log=0;}
				}
				//log_queries_time?
				if(isset($CONFIG['log_queries_time']) && isNum($CONFIG['log_queries_time']) && $lastquery['time'] < $CONFIG['log_queries_time']){$log=0;}
				//log_queries_days
				if(isset($CONFIG['log_queries_days']) && isNum($CONFIG['log_queries_days'])){
					$qdays=(integer)$CONFIG['log_queries_days'];
					if($qdays > 0){
						$query="DELETE FROM _queries WHERE function_name='sql_prompt' and _cdate < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL {$qdays} DAY))";
						$ok=executeSQL($query);
					}
				}
				if($log==1){
					$id=addDBRecord(array(
						'-table'=>'_queries',
						'page_id'=>0,
						'query'=>$params['-query'],
						'function_name'=>'sql_prompt',
						'run_length'=>$qtime,
						'row_count'=>$recs_count,
						'user_id'=>$USER['_id'],
						'tablename'=>"DB:{$db['name']}",
						'filename'=>getFileName($afile),
						'fields'=>implode(',',array_keys($recs[0])),
						'field_count'=>count(array_keys($recs[0]))
					));
					//echo "Logged".printValue($id);exit;
				}
			}
			if($qtime < 1){
				$qtime_verbose=number_format($qtime,3).' seconds';
			}
			else{
				$qtime_verbose=verboseTime($qtime);
			}
			$listopts=array();
			if(stringContains($_SESSION['sql_last'],'listopts:')){
				$lines=preg_split('/[\r\n]+/',trim($_SESSION['sql_last']));
				foreach($lines as $line){
					if(!stringBeginsWith('-- listopts:')){continue;}
					$line=preg_replace('/^\-\-\ listopts\:/','',trim($line));
					list($k,$v)=preg_split('/\=/',$line,2);
					if(stringEndsWith($k,'_options')){$v=decodeJSON($v);}
					$listopts[$k]=$v;
				}
				//echo printValue($listopts);exit;
			}
			if(!isNum($recs_count)){
				$error=$recs_count;
				$recs_count='ERROR';
				setView(array('results','failure'),1);
				if(is_file($afile)){unlink($afile);}
			}
			elseif($recs_count < 30){
				$recs_show=$recs_count;
				setView(array('results','success'),1);
			}
			else{
				setView(array('results','success'),1);
			}
			return;
		break;
		case 'export':
			$tpath=getWasqlPath('php/temp');
			if(isset($_REQUEST['sql_sha']) && strlen($_REQUEST['sql_sha'])){
				$shastr=$_REQUEST['sql_sha'];
				$recs_count=$_REQUEST['sql_cnt'];
				//echo "REQ: {$shastr}";exit;
			}
			elseif(isset($_SESSION['sql_last']) && strlen($_SESSION['sql_last'])){
				$shastr=$_SESSION['sql_last'];
				$recs_count=$_SESSION['sql_last_count'];
			}
			else{$shastr=time();}
			$uid=isset($USER['_id'])?$USER['_id']:'unknown';
			$filename="sqlprompt_{$db['name']}_u{$uid}_{$shastr}.csv";
			$afile="{$tpath}/{$filename}";
			if(file_exists($afile)){
				$fname=getFileName($afile);
				$srec=getDBRecord(array(
					'-table'=>'_queries',
					'filename'=>$fname,
					'user_id'=>$USER['_id']
				));
				if(isset($srec['_id'])){
					$ecnt=(integer)($srec['exported'])+1;
					$ok=editDBRecordById('_queries',$srec['_id'],array('exported'=>$ecnt));
				}
				pushFile($afile);
				exit;
			}

			$_SESSION['sql_full']=$_REQUEST['sql_full'];
			$sql_select=stripslashes($_REQUEST['sql_select']);
			$sql_full=stripslashes($_REQUEST['sql_full']);
			if(strlen($sql_select) && $sql_select != $sql_full){
				$_SESSION['sql_last']=$sql_select;
				$view='block_results';
			}
			else{
				$_SESSION['sql_last']=$sql_full;
				//run the query where the cursor position is
				$queries=preg_split('/\;/',$sql_full);
				//echo printValue($queries);exit;
				$cpos=$_REQUEST['cursor_pos'];
				if(count($queries) > 1){
					$p=0;
					foreach($queries as $query){
						$end=$p+strlen($query);
						if($cpos > $p && $cpos < $end){
							$_SESSION['sql_last']=$query;
							$view='block_results';
							break;
						}
						$p=$end;
					}
				}
				else{
					$_SESSION['sql_last']=$sql_full;
					$view='results';
				}
			}
			$tpath=getWasqlPath('php/temp');
			$filename='wqr_'.sha1($_SESSION['sql_last']).'.csv';
			$afile="{$tpath}/{$filename}";
			if(is_file($afile)){
				$mtime=filemtime($afile);
				$dtime=time()-$mtime;
				//echo "mtime:{$mtime}, dtime:{$dtime}";exit;
				if($dtime < 120){
					pushFile($afile);
					exit;
				}
			}
			if(is_file($afile)){
				unlink($afile);
			}
			$params=array(
				//'-binmode'=>ODBC_BINMODE_CONVERT,
				'-longreadlen'=>131027,
				'-filename'=>$afile,
				//'-cursor'=>SQL_CUR_USE_ODBC,
				'-query'=>$_SESSION['sql_last'],
			);
			$recs=array();
			$recs_count=dbGetRecords($db['name'],$params);
			if(is_file($afile)){
				pushFile($afile);
				exit;
			}
			unset($params['-filename']);
			//echo printValue($db).$_SESSION['sql_last'];exit;
			$recs=dbGetRecords($db['name'],$params);
			$csv=arrays2CSV($recs);
			pushData($csv,'csv');
			exit;
		break;
		case 'fields':
			$table=addslashes($_REQUEST['table']);
			$fields=dbGetTableFields($db['name'],$table);
			$indexes=dbGetTableIndexes($db['name'],$table);
			//echo printValue($db);exit;
			setView('tabledetails',1);
			return;
		break;
		default:
			$showtabs=array();
			if(isset($CONFIG['sql_prompt_dbs']) && strlen($CONFIG['sql_prompt_dbs'])){
				$showtabs=preg_split('/[\:\,]+/',$CONFIG['sql_prompt_dbs']);
			}
			elseif(isset($CONFIG['databases']) && strlen($CONFIG['databases'])){
				$showtabs=preg_split('/[\:\,]+/',$CONFIG['databases']);
			}
			$tabs=array();
			$groups=array();
			foreach($DATABASE as $d=>$cdb){
				if($CONFIG['database']==$d){
					$_SESSION['db']=$cdb;
					continue;
				}
				if(count($showtabs) && !in_array($d,$showtabs)){continue;}
				//access?
				if(isset($cdb['access']) && strtolower($cdb['access']) != 'all'){
					$access_users=preg_split('/\,/',strtolower($cdb['access']));
					if(!in_array($USER['username'],$access_users)){continue;}
				}
				//specific user and pass
				if(isset($cdb["dbuser_{$USER['username']}"])){
					$cdb['dbuser']=$cdb["dbuser_{$USER['username']}"];
				}
				if(isset($cdb["dbpass_{$USER['username']}"])){
					$cdb['dbpass']=$cdb["dbpass_{$USER['username']}"];
				}
				//group?
				if(isset($cdb['group'])){
					$group=$cdb['group'];
				}
				else{
					$group=ucfirst($cdb['dbtype']);
				}
				if(!isset($cdb['group_icon'])){
					$cdb['group_icon']=$cdb['dbicon'];
				}
				$cdb['group']=$group;
				$dbschemas=preg_split('/[\:\,\ ]+/',$cdb['dbschema']);
				if(count($dbschemas) > 1){
					$cdb['dbschemas']=array();
					foreach($dbschemas as $dbschema){
						$dbschema=trim($dbschema);
						$cdb['dbschemas'][]=array(
							'name'=>$dbschema,
							'dbname'=>$cdb['name']
						);
					}
					//echo '<xmp>'.implode(PHP_EOL,$cdb['dbschemas']).'</xmp>';exit;
				}
				$groups[$group][]=$cdb;
				$tabs[]=$cdb;
			}
			$tabs=array();
			foreach($groups as $group=>$dbs){
				$tabs[]=array(
					'group'=>$group,
					'group_icon'=>$dbs[0]['group_icon'],
					'count'=>count($dbs),
					'dbs'=>$dbs
				);
			}
			//make sure _queries exists and has a filename field
			$qfields=getDBFieldInfo('_queries');
			if(!isset($qfields['filename'])){
				$ok=executeSQL('ALTER TABLE _queries ADD filename varchar(255) NULL');
			}
			if(!isset($qfields['exported'])){
				$ok=executeSQL('ALTER TABLE _queries ADD exported integer NULL');
			}
			if(isset($db['name'])){
				$_SESSION['db']=$db;
				$CONFIG['database']=$db['name'];
				$tables=sqlpromptGetTables($db['name']);
				setView(array('default','tables_fields','prompt_load'),1);
				
			}
			else{
				$tables=sqlpromptGetTables();
				setView('default',1);
			}
		break;
	}
	setView('default',1);
?>
