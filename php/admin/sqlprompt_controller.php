<?php
	global $CONFIG;
	global $DATABASE;
	global $_SESSION;
	global $USER;
	global $recs;
	$recs=array();
	if(isset($_REQUEST['db']) && isset($DATABASE[$_REQUEST['db']])){
		$db=$DATABASE[$_REQUEST['db']];
		$_SESSION['db']=$db;
	}
	elseif(isset($CONFIG['database']) && isset($DATABASE[$CONFIG['database']])){
		$db=$DATABASE[$CONFIG['database']];
		$_SESSION['db']=$db;
	}
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
			switch(strtolower($db['dbtype'])){
				case 'mssql':
					$sql="select * from {$table} order by 1 offset 0 rows fetch next 5 rows only";
				break;
				case 'oracle':
					$sql="select * from {$table} order by 1 desc offset 0 rows fetch next 5 rows only";
				break;
				case 'ctree':
					$sql="select top 5 * from admin.{$table} order by 1 desc";
				break;
				case 'postgresql':
				case 'postgres':
					if(strlen($db['dbschema'])){
						$table="{$db['dbschema']}.{$table}";
					}
					$sql="select * from {$table} order by 1 desc limit 5";
				break;
				default:
					$sql="select * from {$table} order by 1 desc limit 5";
				break;
			}
			setView('monitor_sql',1);
			return;
		break;
		case 'ddl':
			$table=addslashes($_REQUEST['table']);
			$db=$_REQUEST['db'];
			$title="DDL for {$table}";
			$parts=preg_split('/\./',$table,2);
			if(count($parts)==2){
				$content=dbGetTableDDL($db,$parts[1],$parts[0]);
			}
			else{
				$content=dbGetTableDDL($db,$table);
			}
			if(is_array($content)){
				$content=printValue($content);
			}
			setView('centerpop',1);
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
			$tables=dbGetTables($db['name']);
			if(!is_array($tables)){
				echo $tables;exit;
			}
			//echo $db['name'].printValue($tables);exit;
			setView('tables_fields',1);
			return;
		break;
		case 'sql':
			$view='block_results';
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
			if(file_exists($afile)){
				unlink($afile);
			}
			$params=array(
				'-binmode'=>ODBC_BINMODE_CONVERT,
				'-longreadlen'=>65535,
				'-filename'=>$afile,
				'-query'=>$_SESSION['sql_last'],
				'-process'=>'sqlpromptCaptureFirstRows'
			);
			$recs_show=30;
			$recs=array();
			$begin=microtime(true);
			$recs_count=dbGetRecords($db['name'],$params);
			$qtime=microtime(true)-$begin;
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
				if(file_exists($afile)){
					unlink($afile);
				}
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
			if(file_exists($afile)){
				$mtime=filemtime($afile);
				$dtime=time()-$mtime;
				//echo "mtime:{$mtime}, dtime:{$dtime}";exit;
				if($dtime < 120){
					pushFile($afile);
					exit;
				}
			}
			if(file_exists($afile)){
				unlink($afile);
			}
			$params=array(
				'-binmode'=>ODBC_BINMODE_CONVERT,
				'-longreadlen'=>65535,
				'-filename'=>$afile,
				'-query'=>$_SESSION['sql_last'],
			);
			$recs=array();
			$recs_count=dbGetRecords($db['name'],$params);
			if(file_exists($afile)){
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
			if(isset($CONFIG['sql_prompt_dbs'])){
				$showtabs=preg_split('/\,/',$CONFIG['sql_prompt_dbs']);
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
				$groups[$group][]=$db;
				$tabs[]=$db;
			}
			$tabs=array();
			foreach($groups as $group=>$dbs){
				$tabs[]=array(
					'group'=>$group,
					'group_icon'=>$dbs[0]['group_icon'],
					'dbs'=>$dbs
				);
			}
			//echo printValue($tabs);exit;
			$tables=getDBTables();
			if(isset($CONFIG['sqlprompt_tables'])){
				if(!is_array($CONFIG['sqlprompt_tables'])){
					$CONFIG['sqlprompt_tables']=preg_split('/\,/',$CONFIG['sqlprompt_tables']);
				}
				foreach($tables as $i=>$table){
					if(!in_array($table,$CONFIG['sqlprompt_tables'])){
						unset($tables[$i]);
					}
				}
			}
			if(isset($CONFIG['sqlprompt_tables_filter'])){
				if(!is_array($CONFIG['sqlprompt_tables_filter'])){
					$CONFIG['sqlprompt_tables_filter']=preg_split('/\,/',$CONFIG['sqlprompt_tables_filter']);
				}
				//echo printValue($CONFIG['sqlprompt_tables_filter']);
				foreach($tables as $i=>$table){
					$found=0;
					foreach($CONFIG['sqlprompt_tables_filter'] as $filter){
						if(stringContains($table,$filter)){$found+=1;}
					}
					if($found==0){
						unset($tables[$i]);
					}
				}
			}
			setView('default',1);
		break;
	}
	setView('default',1);
?>
