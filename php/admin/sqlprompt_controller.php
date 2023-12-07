<?php
	global $CONFIG;
	global $DATABASE;
	global $_SESSION;
	global $USER;
	global $recs;
	global $sqlpromptCaptureFirstRows_count;
	$sqlpromptCaptureFirstRows_count=0;
	global $wasql_debugValueContent;
	global $db;
	//echo printValue($_REQUEST);exit;
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
		case 'last_records':
			$table=addslashes($_REQUEST['table']);
			//echo "TABLE: {$table}";exit;
			switch(strtolower($db['dbtype'])){
				case 'mssql':
					$sql="select * from {$table} offset 0 rows fetch next 5 rows only";
				break;
				case 'oracle':
					$sql="select * from {$table} offset 0 rows fetch next 5 rows only";
				break;
				case 'firebird':
					$sql="select * from {$table} offset 0 rows fetch next 5 rows only";
				break;
				case 'ctree':
					$sql=sqlpromptBuildTop5Ctree($db,$table);
				break;
				case 'msexcel':
				case 'mscsv':
				case 'msaccess':
					$sql="select top 5 * from {$table}";
				break;
				case 'postgresql':
				case 'postgres':
					if(strlen($db['dbschema'])){
						$table="{$db['dbschema']}.{$table}";
					}
					$sql="select * from {$table} limit 5";
				break;
				case 'gigya':
					$sql="select * from {$table} limit 5";
				break;
				default:
					$sql="select * from {$table} limit 5";
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
				case 'ctree':
					$sql="select count(*) as cnt from admin.{$table}";
				break;
				case 'mysql':
				case 'mysqli':
					$sql="select count(*) as cnt from {$table}";
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
					$sql="select count(*) as cnt from {$table}";
				break;
			}
			setView('monitor_sql',1);
			return;
		break;
		case 'ddl':
			$table=addslashes($_REQUEST['table']);
			$db=$_REQUEST['db'];
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
			//echo printValue($db);exit;
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
					$recs=getDBRecords(array(
						'-table'=>'_queries',
						'_cuser'=>$USER['_id'],
						'function_name'=>'dasql_prompt',
						'-fields'=>'_id,_cdate,query',
						'-limit'=>100,
						'-notimestamp'=>1
					));
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
				$filter=trim($m[1]);
				$recs=array();
				if(isset($names['tables'])){
					$query=sqlpromptBuildQuery($db['name'],'tables');
					$xrecs=dbGetRecords($db['name'],$query);
					foreach($xrecs as $rec){
						if(strlen($filter) && !stringContains($rec['name'],$filter)){continue;}
						$recs[]=array(
							'name'=>$rec['name'],
							'row_count'=>$rec['row_count'],
							'field_count'=>$rec['field_count'],
							'mb_size'=>$rec['mb_size']
						);
					}
				}
				else{
					$xrecs=dbGetTables($db['name']);
					foreach($xrecs as $name){
						if(strlen($filter) && !stringContains($name,$filter)){continue;}
						$recs[]=array(
							'name'=>$name
						);
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
			}
			elseif($skip==0 && preg_match('/^(fields|fld)\ (.+)$/is',$lcq,$m)){
				$parts=preg_split('/\ /',$m[2],2);
				$filter='';
				if(count($parts)==2){
					$table=$parts[0];
					$filter=$parts[1];
				}
				else{
					$table=$m[2];
				}
				$finfo=dbGetTableFields($db['name'],$table);
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
					$recs[]=$rec;
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
			}
			elseif($skip==0 && preg_match('/^idx\ (.+)$/is',$lcq,$m)){
				$parts=preg_split('/\./',$m[1],2);
				if(count($parts)==2){
					$recs=dbGetTableIndexes($db['name'],$parts[1],$parts[0]);
				}
				else{
					$recs=dbGetTableIndexes($db['name'],$m[1]);
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
				$recs_count=1;
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
									'-tableclass'=>'table striped bordered condensed'
								));
								exit;
							break;
							case 'html':
								echo sqlpromptHTMLHead();
								echo databaseListRecords(array(
									'-list'=>array($lastquery),
									'-hidesearch'=>1,
									'-tableclass'=>'table striped bordered condensed'
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
					setView(array('no_results'),1);
					return;
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
							'-tableclass'=>'table striped bordered condensed'
						));
						exit;
					break;
					case 'html':
						echo sqlpromptHTMLHead();
						echo databaseListRecords(array(
							'-csv'=>$afile,
							'-hidesearch'=>1,
							'-tableclass'=>'table striped bordered condensed'
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
			//echo printValue($indexes);exit;
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
			foreach($DATABASE as $d=>$db){
				if($CONFIG['database']==$d){
					$_SESSION['db']=$db;
					continue;
				}
				if(count($showtabs) && !in_array($d,$showtabs)){continue;}
				//access?
				if(isset($db['access']) && strtolower($db['access']) != 'all'){
					$access_users=preg_split('/\,/',strtolower($db['access']));
					if(!in_array($USER['username'],$access_users)){continue;}
				}
				//specific user and pass
				if(isset($db["dbuser_{$USER['username']}"])){
					$db['dbuser']=$db["dbuser_{$USER['username']}"];
				}
				if(isset($db["dbpass_{$USER['username']}"])){
					$db['dbpass']=$db["dbpass_{$USER['username']}"];
				}
				//group?
				if(isset($db['group'])){
					$group=$db['group'];
				}
				else{
					$group=ucfirst($db['dbtype']);
				}
				if(!isset($db['group_icon'])){
					$db['group_icon']=$db['dbicon'];
				}
				$db['group']=$group;
				$dbschemas=preg_split('/[\:\,\ ]+/',$db['dbschema']);
				if(count($dbschemas) > 1){
					$db['dbschemas']=array();
					foreach($dbschemas as $dbschema){
						$dbschema=trim($dbschema);
						$db['dbschemas'][]='<a  style="text-align:center;border-radius:15px;border:1px solid #ccc;padding:3px 5px;margin-bottom:2px;" href="#" onclick="wacss.setActiveTab(document.querySelector(\'#db_'.$db['name'].'\'));sqlpromptSetDB('."'{$db['name']}','{$dbschema}'".')">'.$dbschema.'</a>';
					}
					//echo '<xmp>'.implode(PHP_EOL,$db['dbschemas']).'</xmp>';exit;
				}
				$groups[$group][]=$db;
				$tabs[]=$db;
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
			//echo printValue($tabs);exit;
			$tables=sqlpromptGetTables();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
