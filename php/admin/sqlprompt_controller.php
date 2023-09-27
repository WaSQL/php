<?php
	global $CONFIG;
	global $DATABASE;
	global $_SESSION;
	global $USER;
	global $recs;
	global $sqlpromptCaptureFirstRows_count;
	$sqlpromptCaptureFirstRows_count=0;
	global $wasql_debugValueContent;
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
					$sql="select * from {$table} order by 1 offset 0 rows fetch next 5 rows only";
				break;
				case 'oracle':
					$sql="select * from {$table} order by 1 desc offset 0 rows fetch next 5 rows only";
				break;
				case 'firebird':
					$sql="select * from {$table} order by 1 desc offset 0 rows fetch next 5 rows only";
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
					$sql="select * from {$table} order by 1 desc limit 5";
				break;
				case 'gigya':
					$sql="select * from {$table} limit 5";
				break;
				default:
					$sql="select * from {$table} order by 1 desc limit 5";
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
					$sql="select count(*) as cnt from admin.{$table} order by 1 desc";
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
			$filename="sqlprompt_{$shastr}.csv";
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
			//Run the Query
			//is this query python?
			if(isset($_REQUEST['py']) && $_REQUEST['py']==1){
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
			else{
				$tpath=getWasqlPath('php/temp');
				$shastr=sha1($_SESSION['sql_last']);
				$filename="sqlprompt_{$shastr}.csv";
				$afile="{$tpath}/{$filename}";
				$logname="sqlprompt_{$shastr}.log";
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
				$recs_show=30;
				$recs=array();
				$qstart=microtime(true);
				$recs_count=$_SESSION['sql_last_count']=dbGetRecords($db['name'],$params);
				$qstop=microtime(true);
				$lastquery=dbGetLast();
				if(!is_array($lastquery)){$lastquery=array();}
				$lastquery['time']=round(($qstop-$qstart),3);
				//echo "lastquery".printValue($lastquery);exit;
				if(isset($lastquery['error'])){
					if(!is_string($lastquery['error'])){
						$lastquery['error']=encodeJson($lastquery['error']);
					}
					if(strlen($lastquery['error'])){
						setView(array('error'),1);
						return;
					}
				}
				if($recs_count==0){
					$recs=array();
					setView(array('no_results'),1);
					return;
				}
			}
			if(isset($_REQUEST['format'])){
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
				if(isNum($CONFIG['log_queries_time']) && $lastquery['time'] < $CONFIG['log_queries_time']){$log=0;}
				//log_queries_days
				if(isNum($CONFIG['log_queries_days'])){
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
			$filename="sqlprompt_{$shastr}.csv";
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
